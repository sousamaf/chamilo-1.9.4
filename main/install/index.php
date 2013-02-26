<?php
/* For licensing terms, see /license.txt */

/**
 * Chamilo installation
 *
 * As seen from the user, the installation proceeds in 6 steps.
 * The user is presented with several webpages where he/she has to make choices
 * and/or fill in data.
 *
 * The aim is, as always, to have good default settings and suggestions.
 *
 * @todo reduce high level of duplication in this code
 * @todo (busy) organise code into functions
 * @package chamilo.install
 */

/*		CONSTANTS */

use \ChamiloSession as Session;


define('SYSTEM_INSTALLATION',                   1);
define('INSTALL_TYPE_UPDATE',                   'update');
define('FORM_FIELD_DISPLAY_LENGTH',             40);
define('DATABASE_FORM_FIELD_DISPLAY_LENGTH',    25);
define('MAX_FORM_FIELD_LENGTH',                 80);

/*		PHP VERSION CHECK */

// Including necessary libraries.
require_once '../inc/lib/main_api.lib.php';

api_check_php_version('../inc/');

/*		INITIALIZATION SECTION */

ob_implicit_flush(true);
session_start();

require_once api_get_path(SYS_PATH).'main/inc/autoload.inc.php';
require_once api_get_path(LIBRARY_PATH).'database.lib.php';
require_once api_get_path(LIBRARY_PATH).'log.class.php';
require_once 'install.lib.php';
require_once 'install.class.php';
require_once 'i_database.class.php';

// This value is use in database::query in order to prompt errors in the error log (course databases)
Database::$log_queries = true;

// The function api_get_setting() might be called within the installation scripts.
// We need to provide some limited support for it through initialization of the
// global array-type variable $_setting.
$_setting = array(
	'platform_charset' => 'UTF-8',
	'server_type' => 'production', // 'production' | 'test'
	'permissions_for_new_directories' => '0770',
	'permissions_for_new_files' => '0660',
	'stylesheets' => 'chamilo'
);

// Determination of the language during the installation procedure.
if (!empty($_POST['language_list'])) {
	$search = array('../', '\\0');
	$install_language = str_replace($search, '', urldecode($_POST['language_list']));
	Session::write('install_language',$install_language);
} elseif (isset($_SESSION['install_language']) && $_SESSION['install_language']) {
	$install_language = $_SESSION['install_language'];
} else {
	// Trying to switch to the browser's language, it is covenient for most of the cases.
	$install_language = detect_browser_language();
}

// Language validation.
if (!array_key_exists($install_language, get_language_folder_list())) {
	$install_language = 'english';
}

// Loading language files.
require api_get_path(SYS_LANG_PATH).'english/trad4all.inc.php';
require api_get_path(SYS_LANG_PATH).'english/admin.inc.php';
require api_get_path(SYS_LANG_PATH).'english/install.inc.php';
if ($install_language != 'english') {
	include_once api_get_path(SYS_LANG_PATH).$install_language.'/trad4all.inc.php';
	include_once api_get_path(SYS_LANG_PATH).$install_language.'/install.inc.php';
    include_once api_get_path(SYS_LANG_PATH).$install_language.'/admin.inc.php';
}

// These global variables must be set for proper working of the function get_lang(...) during the installation.
$language_interface = $install_language;
$language_interface_initial_value = $install_language;

// Character set during the installation, it is always to be 'UTF-8'.
$charset = 'UTF-8';

// Initialization of the internationalization library.
api_initialize_internationalization();
// Initialization of the default encoding that will be used by the multibyte string routines in the internationalization library.
api_set_internationalization_default_encoding($charset);

// Page encoding initialization.
header('Content-Type: text/html; charset='. api_get_system_encoding());

// Setting the error reporting levels.
error_reporting(E_ALL);

// Overriding the timelimit (for large campusses that have to be migrated).
@set_time_limit(0);

// Upgrading from any subversion of 1.6 is just like upgrading from 1.6.5
$update_from_version_6 = array('1.6', '1.6.1', '1.6.2', '1.6.3', '1.6.4', '1.6.5');
// Upgrading from any subversion of 1.8 avoids the additional step of upgrading from 1.6
$update_from_version_8 = array('1.8', '1.8.2', '1.8.3', '1.8.4', '1.8.5', '1.8.6', '1.8.6.1', '1.8.6.2','1.8.7','1.8.7.1','1.8.8','1.8.8.2', '1.8.8.4', '1.8.8.6', '1.9.0', '1.9.2');

$my_old_version = '';
$tmp_version = get_config_param('dokeos_version');
if (empty($tmp_version)) {
	$tmp_version = get_config_param('system_version');
}
if (!empty($_POST['old_version'])) {
	$my_old_version = $_POST['old_version'];
} elseif (!empty($tmp_version)) {
    $my_old_version = $tmp_version;
} elseif (!empty($dokeos_version)) { //variable coming from installedVersion, normally
	$my_old_version = $dokeos_version;
}

require_once __DIR__.'/version.php';

// A protection measure for already installed systems.

if (is_already_installed_system()) {
	// The system has already been installed, so block re-installation.
	$global_error_code = 6;
	require '../inc/global_error_message.inc.php';
	die();
}

/*		STEP 1 : INITIALIZES FORM VARIABLES IF IT IS THE FIRST VISIT */

// Is valid request
$is_valid_request = isset($_REQUEST['is_executable']) ? $_REQUEST['is_executable'] : null;
foreach ($_POST as $request_index => $request_value) {
	if (substr($request_index, 0, 4) == 'step') {
		if ($request_index != $is_valid_request) {
			unset($_POST[$request_index]);
		}
	}
}

$badUpdatePath = false;
$emptyUpdatePath = true;
$proposedUpdatePath = '';
if (!empty($_POST['updatePath'])) {
	$proposedUpdatePath = $_POST['updatePath'];
}

if (@$_POST['step2_install'] || @$_POST['step2_update_8'] || @$_POST['step2_update_6']) {
	if (@$_POST['step2_install']) {
		$installType = 'new';
		$_POST['step2'] = 1;
	} else {
		$installType = 'update';
		if (@$_POST['step2_update_8']) {
			$emptyUpdatePath = false;
			$proposedUpdatePath = api_add_trailing_slash(empty($_POST['updatePath']) ? api_get_path(SYS_PATH) : $_POST['updatePath']);
			if (file_exists($proposedUpdatePath)) {
				if (in_array($my_old_version, $update_from_version_8)) {
					$_POST['step2'] = 1;
				} else {
					$badUpdatePath = true;
				}
			} else {
				$badUpdatePath = true;
			}
		} else { //step2_update_6, presumably
			if (empty($_POST['updatePath'])) {
				$_POST['step1'] = 1;
			} else {
				$emptyUpdatePath = false;
				$_POST['updatePath'] = api_add_trailing_slash($_POST['updatePath']);
				if (file_exists($_POST['updatePath'])) {
					//1.6.x
					$my_old_version = get_config_param('clarolineVersion', $_POST['updatePath']);
					if (in_array($my_old_version, $update_from_version_6)) {
						$_POST['step2'] = 1;
						$proposedUpdatePath = $_POST['updatePath'];
					} else {
						$badUpdatePath = true;
					}
				} else {
					$badUpdatePath = true;
				}
			}
		}
	}
} elseif (@$_POST['step1']) {
	$_POST['updatePath'] = '';
	$installType = '';
	$updateFromConfigFile = '';
	unset($_GET['running']);
} else {
	$installType = isset($_GET['installType']) ? $_GET['installType'] : null;
	$updateFromConfigFile = isset($_GET['updateFromConfigFile']) ? $_GET['updateFromConfigFile'] : false;
}

if ($installType == 'update' && in_array($my_old_version, $update_from_version_8)) {
	// This is the main configuration file of the system before the upgrade.
	include api_get_path(CONFIGURATION_PATH).'configuration.php'; // Don't change to include_once
}

if (!isset($_GET['running'])) {

	$dbHostForm		= 'localhost';
	$dbUsernameForm = 'root';
	$dbPassForm		= '';
 	$dbPrefixForm   = '';
	$dbNameForm		= 'chamilo';

	$dbStatsForm    = 'chamilo';
	$dbScormForm    = 'chamilo';
	$dbUserForm		= 'chamilo';

	// Extract the path to append to the url if Chamilo is not installed on the web root directory.
	$urlAppendPath  = api_remove_trailing_slash(api_get_path(REL_PATH));
  	$urlForm 		= api_get_path(WEB_PATH);
	$pathForm 		= api_get_path(SYS_PATH);

	$emailForm      = $_SERVER['SERVER_ADMIN'];
	$email_parts = explode('@', $emailForm);
	if (isset($email_parts[1]) && $email_parts[1] == 'localhost') {
		$emailForm .= '.localdomain';
	}
	$adminLastName	= 'Doe';
	$adminFirstName	= 'John';
	$loginForm		= 'admin';
	$passForm		= api_generate_password();

	$campusForm		= 'My campus';
	$educationForm	= 'Albert Einstein';
	$adminPhoneForm	= '(000) 001 02 03';
	$institutionForm    = 'My Organisation';
	$institutionUrlForm = 'http://www.chamilo.org';
	// TODO: A better choice to be tested:
	//$languageForm	    = 'english';
	$languageForm	    = api_get_interface_language();

	$checkEmailByHashSent	= 0;
	$ShowEmailnotcheckedToStudent = 1;
	$userMailCanBeEmpty		= 1;
	$allowSelfReg			= 1;
	$allowSelfRegProf		= 1;
	$enableTrackingForm		= 1;
	$singleDbForm			= 0;
	$encryptPassForm		= 'sha1';
	$session_lifetime		= 360000;
} else {
	foreach ($_POST as $key => $val) {
		$magic_quotes_gpc = ini_get('magic_quotes_gpc');
		if (is_string($val)) {
			if ($magic_quotes_gpc) {
				$val = stripslashes($val);
			}
			$val = trim($val);
			$_POST[$key] = $val;
		} elseif (is_array($val)) {
			foreach ($val as $key2 => $val2) {
				if ($magic_quotes_gpc) {
					$val2 = stripslashes($val2);
				}
				$val2 = trim($val2);
				$_POST[$key][$key2] = $val2;
			}
		}
		$GLOBALS[$key] = $_POST[$key];
	}
}

/*		NEXT STEPS IMPLEMENTATION */

$total_steps = 7;
if (!$_POST) {
	$current_step = 1;
} elseif (!empty($_POST['language_list']) or !empty($_POST['step1']) or ((!empty($_POST['step2_update_8']) or (!empty($_POST['step2_update_6'])))  && ($emptyUpdatePath or $badUpdatePath))) {
	$current_step = 2;
} elseif (!empty($_POST['step2']) or (!empty($_POST['step2_update_8']) or (!empty($_POST['step2_update_6'])) )) {
	$current_step = 3;
} elseif (!empty($_POST['step3'])) {
	$current_step = 4;
} elseif (!empty($_POST['step4'])) {
	$current_step = 5;
} elseif (!empty($_POST['step5'])) {
	$current_step = 6;
}

// Managing the $encryptPassForm
if ($encryptPassForm == '1') {
	$encryptPassForm = 'sha1';
} elseif ($encryptPassForm == '0') {
	$encryptPassForm = 'none';
}
?>
<!DOCTYPE html>
<head>
	<title>&mdash; <?php echo get_lang('ChamiloInstallation').' &mdash; '.get_lang('Version_').' '.$new_version; ?></title>
	<style type="text/css" media="screen, projection">
		/*<![CDATA[*/
		@import "../css/base.css";
		@import "../css/<?php echo api_get_visual_theme(); ?>/default.css";
		/*]]>*/
	</style>
	<script type="text/javascript" src="../inc/lib/javascript/jquery.min.js"></script>
	<script type="text/javascript" >
		$(document).ready( function() {

            $("#button_please_wait").hide();

			 //checked
			if ($('#singleDb1').attr('checked')==false) {
					//$('#dbStatsForm').removeAttr('disabled');
					//$('#dbUserForm').removeAttr('disabled');
					$('#dbStatsForm').attr('value','chamilo_main');
				    $('#dbUserForm').attr('value','chamilo_main');
			} else if($('#singleDb1').attr('checked')==true){
			        //$('#dbStatsForm').attr('disabled','disabled');
					//$('#dbUserForm').attr('disabled','disabled');
					$('#dbStatsForm').attr('value','chamilo_main');
					$('#dbUserForm').attr('value','chamilo_main');
			}

			$("button").addClass('btn');

    		//Allow Chamilo install in IE
    		$("button").click(function() {
    			$("#is_executable").attr("value",$(this).attr("name"));
    		});

			//Blocking step6 button
    		$("#button_step6").click(function() {
            	$("#button_step6").hide();
    			$("#button_please_wait").html('<?php echo addslashes(get_lang('PleaseWait'));?>');
                $("#button_please_wait").show();
                $("#button_please_wait").attr('disabled', true);
    			$("#is_executable").attr("value",'step6');
        	});
	 	});

		function show_hide_tracking_and_user_db (my_option) {
			if (my_option=='singleDb1') {
				$('#optional_param2').hide();
				$('#optional_param4').hide();

				$('#dbStatsForm').attr('value','chamilo_main');
				$('#dbUserForm').attr('value','chamilo_main');
			} else if (my_option=='singleDb0') {
				$('#optional_param2').show();
				$('#optional_param4').show();

				$('#dbStatsForm').attr('value','chamilo_main');
				$('#dbUserForm').attr('value','chamilo_main');
			}
		}

		init_visibility=0;
		function show_hide_option() {
			if (init_visibility == 0) {
				$('#optional_param1').show();

				if ($('#singleDb1').attr("checked") == true) {
					//$('#optional_param2').hide();
					//$('#optional_param4').hide();
					$('#optional_param5').hide();
				} else {
					//$('#optional_param2').show();
					//$('#optional_param4').show();
					$('#optional_param5').show();
                }

				//document.getElementById('optional_param2').style.display = '';
				if (document.getElementById('optional_param3')) {
					document.getElementById('optional_param3').style.display = '';
				}

				//document.getElementById('optional_param5').style.display = '';
				//document.getElementById('optional_param6').style.display = '';
				init_visibility = 1;
				document.getElementById('optionalparameters').innerHTML='<img style="vertical-align:middle;" src="../img/div_hide.gif" alt="" /> <?php echo get_lang('OptionalParameters', ''); ?>';
			} else {
				document.getElementById('optional_param1').style.display = 'none';
				/*document.getElementById('optional_param2').style.display = 'none';
				if (document.getElementById('optional_param3')) {
					document.getElementById('optional_param3').style.display = 'none';
				}
				document.getElementById('optional_param4').style.display = 'none';
				*/
				document.getElementById('optional_param5').style.display = 'none';
				//document.getElementById('optional_param6').style.display = 'none';
				document.getElementById('optionalparameters').innerHTML='<img style="vertical-align:middle;" src="../img/div_show.gif" alt="" /> <?php echo get_lang('OptionalParameters', ''); ?>';
				init_visibility = 0;
			}
			return false;
		}

        $(document).ready( function() {
            $(".advanced_parameters").click(function() {
                if ($("#id_contact_form").css("display") == "none") {
                        $("#id_contact_form").css("display","block");
                        $("#img_plus_and_minus").html('&nbsp;<img src="<?php echo api_get_path(WEB_IMG_PATH) ?>div_hide.gif" alt="<?php echo get_lang('Hide') ?>" title="<?php echo get_lang('Hide')?>" style ="vertical-align:middle" >&nbsp;<?php echo get_lang('ContactInformation') ?>');
                } else {
                        $("#id_contact_form").css("display","none");
                        $("#img_plus_and_minus").html('&nbsp;<img src="<?php echo api_get_path(WEB_IMG_PATH) ?>div_show.gif" alt="<?php echo get_lang('Show') ?>" title="<?php echo get_lang('Show') ?>" style ="vertical-align:middle" >&nbsp;<?php echo get_lang('ContactInformation') ?>');
                }
            });
        });

        function send_contact_information() {
            var data_post = "";
            data_post += "person_name="+$("#person_name").val()+"&";
            data_post += "person_email="+$("#person_email").val()+"&";
            data_post += "company_name="+$("#company_name").val()+"&";
            data_post += "company_activity="+$("#company_activity option:selected").val()+"&";
            data_post += "person_role="+$("#person_role option:selected").val()+"&";
            data_post += "company_country="+$("#country option:selected").val()+"&";
            data_post += "company_city="+$("#company_city").val()+"&";
            data_post += "language="+$("#language option:selected").val()+"&";
            data_post += "financial_decision="+$("input[@name='financial_decision']:checked").val();

            $.ajax({
                    contentType: "application/x-www-form-urlencoded",
                    beforeSend: function(objeto) {},
                    type: "POST",
                    url: "<?php echo api_get_path(WEB_AJAX_PATH) ?>install.ajax.php?a=send_contact_information",
                    data: data_post,
                    success: function(datos) {
                        if (datos == 'required_field_error') {
                            message = "<?php echo get_lang('FormHasErrorsPleaseComplete') ?>";
                        } else if (datos == '1') {
                            message = "<?php echo get_lang('ContactInformationHasBeenSent') ?>";
                        } else {
                            message = "<?php echo get_lang('Error').': '.get_lang('ContactInformationHasNotBeenSent') ?>";
                        }
                        alert(message);
                    }
            });
        }
    </script>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo api_get_system_encoding(); ?>" />
</head>
<body dir="<?php echo api_get_text_direction(); ?>">

<div id="wrapper">
<div id="main" class="container">
    <header>
		<div class="row">
            <div id="header_left" class="span4">
                <div id="logo">
                    <img src="../css/chamilo/images/header-logo.png" hspace="10" vspace="10" alt="Chamilo" />
                </div>
            </div>
        </div>
        <div class="navbar subnav">
            <div class="navbar-inner">
                <div class="container">
                    <div class="nav-collapse">
                        <ul class="nav nav-pills">
                            <li id="current" class="active">
                                <a target="_top" href="index.php"><?php echo get_lang('Homepage'); ?></a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
	</header>
    <br />
    
    <?php 
    echo '<div class="page-header"><h1>'.get_lang('ChamiloInstallation').' &ndash; '.get_lang('Version_').' '.$new_version.'</h1></div>';
    ?>
    <div class="row">
        <div class="span3">
            <div class="well">
                <ol>
                    <li <?php step_active('1'); ?>><?php echo get_lang('InstallationLanguage'); ?></li>
                    <li <?php step_active('2'); ?>><?php echo get_lang('Requirements'); ?></li>
                    <li <?php step_active('3'); ?>><?php echo get_lang('Licence'); ?></li>
                    <li <?php step_active('4'); ?>><?php echo get_lang('DBSetting'); ?></li>
                    <li <?php step_active('5'); ?>><?php echo get_lang('CfgSetting'); ?></li>
                    <li <?php step_active('6'); ?>><?php echo get_lang('PrintOverview'); ?></li>
                    <li <?php step_active('7'); ?>><?php echo get_lang('Installing'); ?></li>
                </ol>
            </div>
            <div id="note">
				<a class="btn" href="../../documentation/installation_guide.html" target="_blank">
                    <?php echo get_lang('ReadTheInstallationGuide'); ?>
                </a>
			</div>
        </div>
        
        <div class="span9">
            
<form class="form-horizontal" id="install_form" style="padding: 0px; margin: 0px;" method="post" action="<?php echo api_get_self(); ?>?running=1&amp;installType=<?php echo $installType; ?>&amp;updateFromConfigFile=<?php echo urlencode($updateFromConfigFile); ?>">
<?php   

    $instalation_type_label = '';
    if ($installType == 'new'){
        $instalation_type_label  = get_lang('NewInstallation');
    }elseif ($installType == 'update') {
        $update_from_version = isset($update_from_version) ? $update_from_version : null;
        $instalation_type_label = get_lang('UpdateFromDokeosVersion').(is_array($update_from_version) ? implode('|', $update_from_version) : '');
    }
    if (!empty($instalation_type_label) && empty($_POST['step6'])) {
    	echo '<div class="page-header"><h2>'.$instalation_type_label.'</h2></div>';
    }
    ?>
	<input type="hidden" name="updatePath"           value="<?php if (!$badUpdatePath) echo api_htmlentities($proposedUpdatePath, ENT_QUOTES); ?>" />
	<input type="hidden" name="urlAppendPath"        value="<?php echo api_htmlentities($urlAppendPath, ENT_QUOTES); ?>" />
	<input type="hidden" name="pathForm"             value="<?php echo api_htmlentities($pathForm, ENT_QUOTES); ?>" />
	<input type="hidden" name="urlForm"              value="<?php echo api_htmlentities($urlForm, ENT_QUOTES); ?>" />
	<input type="hidden" name="dbHostForm"           value="<?php echo api_htmlentities($dbHostForm, ENT_QUOTES); ?>" />
	<input type="hidden" name="dbUsernameForm"       value="<?php echo api_htmlentities($dbUsernameForm, ENT_QUOTES); ?>" />
	<input type="hidden" name="dbPassForm"           value="<?php echo api_htmlentities($dbPassForm, ENT_QUOTES); ?>" />
	<input type="hidden" name="singleDbForm"         value="<?php echo api_htmlentities($singleDbForm, ENT_QUOTES); ?>" />
	<input type="hidden" name="dbPrefixForm"         value="<?php echo api_htmlentities($dbPrefixForm, ENT_QUOTES); ?>" />
	<input type="hidden" name="dbNameForm"           value="<?php echo api_htmlentities($dbNameForm, ENT_QUOTES); ?>" />
<?php
	if ($installType == 'update' OR $singleDbForm == 0) {
?>
	<input type="hidden" name="dbStatsForm"          value="<?php echo api_htmlentities($dbStatsForm, ENT_QUOTES); ?>" />
	<input type="hidden" name="dbScormForm"          value="<?php echo api_htmlentities($dbScormForm, ENT_QUOTES); ?>" />
	<input type="hidden" name="dbUserForm"           value="<?php echo api_htmlentities($dbUserForm, ENT_QUOTES); ?>" />
<?php
	} else {
?>
	<input type="hidden" name="dbStatsForm"          value="<?php echo api_htmlentities($dbNameForm, ENT_QUOTES); ?>" />
	<input type="hidden" name="dbUserForm"           value="<?php echo api_htmlentities($dbNameForm, ENT_QUOTES); ?>" />
<?php
	}
?>
	<input type="hidden" name="enableTrackingForm"   value="<?php echo api_htmlentities($enableTrackingForm, ENT_QUOTES); ?>" />
	<input type="hidden" name="allowSelfReg"         value="<?php echo api_htmlentities($allowSelfReg, ENT_QUOTES); ?>" />
	<input type="hidden" name="allowSelfRegProf"     value="<?php echo api_htmlentities($allowSelfRegProf, ENT_QUOTES); ?>" />
	<input type="hidden" name="emailForm"            value="<?php echo api_htmlentities($emailForm, ENT_QUOTES); ?>" />
	<input type="hidden" name="adminLastName"        value="<?php echo api_htmlentities($adminLastName, ENT_QUOTES); ?>" />
	<input type="hidden" name="adminFirstName"       value="<?php echo api_htmlentities($adminFirstName, ENT_QUOTES); ?>" />
	<input type="hidden" name="adminPhoneForm"       value="<?php echo api_htmlentities($adminPhoneForm, ENT_QUOTES); ?>" />
	<input type="hidden" name="loginForm"            value="<?php echo api_htmlentities($loginForm, ENT_QUOTES); ?>" />
	<input type="hidden" name="passForm"             value="<?php echo api_htmlentities($passForm, ENT_QUOTES); ?>" />
	<input type="hidden" name="languageForm"         value="<?php echo api_htmlentities($languageForm, ENT_QUOTES); ?>" />
	<input type="hidden" name="campusForm"           value="<?php echo api_htmlentities($campusForm, ENT_QUOTES); ?>" />
	<input type="hidden" name="educationForm"        value="<?php echo api_htmlentities($educationForm, ENT_QUOTES); ?>" />
	<input type="hidden" name="institutionForm"      value="<?php echo api_htmlentities($institutionForm, ENT_QUOTES); ?>" />
	<input type="hidden" name="institutionUrlForm"   value="<?php echo api_stristr($institutionUrlForm, 'http://', false) ? api_htmlentities($institutionUrlForm, ENT_QUOTES) : api_stristr($institutionUrlForm, 'https://', false) ? api_htmlentities($institutionUrlForm, ENT_QUOTES) : 'http://'.api_htmlentities($institutionUrlForm, ENT_QUOTES); ?>" />
	<input type="hidden" name="checkEmailByHashSent" value="<?php echo api_htmlentities($checkEmailByHashSent, ENT_QUOTES); ?>" />
	<input type="hidden" name="ShowEmailnotcheckedToStudent" value="<?php echo api_htmlentities($ShowEmailnotcheckedToStudent, ENT_QUOTES); ?>" />
	<input type="hidden" name="userMailCanBeEmpty"   value="<?php echo api_htmlentities($userMailCanBeEmpty, ENT_QUOTES); ?>" />
	<input type="hidden" name="encryptPassForm"      value="<?php echo api_htmlentities($encryptPassForm, ENT_QUOTES); ?>" />
	<input type="hidden" name="session_lifetime"     value="<?php echo api_htmlentities($session_lifetime, ENT_QUOTES); ?>" />
	<input type="hidden" name="old_version"          value="<?php echo api_htmlentities($my_old_version, ENT_QUOTES); ?>" />
	<input type="hidden" name="new_version"          value="<?php echo api_htmlentities($new_version, ENT_QUOTES); ?>" />
<?php
if (@$_POST['step2']) {
	//STEP 3 : LICENSE
	display_license_agreement();
} elseif (@$_POST['step3']) {
	//STEP 4 : MYSQL DATABASE SETTINGS
	display_database_settings_form($installType, $dbHostForm, $dbUsernameForm, $dbPassForm, $dbPrefixForm, $enableTrackingForm, $singleDbForm, $dbNameForm, $dbStatsForm, $dbScormForm, $dbUserForm);
} elseif (@$_POST['step4']) {
	//STEP 5 : CONFIGURATION SETTINGS

	//if update, try getting settings from the database...
	if ($installType == 'update') {
		$db_name = $dbNameForm;

		$tmp = get_config_param_from_db($dbHostForm, $dbUsernameForm, $dbPassForm, $db_name, 'platformLanguage');
		if (!empty($tmp)) $languageForm = $tmp;

		$tmp = get_config_param_from_db($dbHostForm, $dbUsernameForm, $dbPassForm, $db_name, 'emailAdministrator');
		if (!empty($tmp)) $emailForm = $tmp;

		$tmp = get_config_param_from_db($dbHostForm, $dbUsernameForm, $dbPassForm, $db_name, 'administratorName');
		if (!empty($tmp)) $adminFirstName = $tmp;

		$tmp = get_config_param_from_db($dbHostForm, $dbUsernameForm, $dbPassForm, $db_name, 'administratorSurname');
		if (!empty($tmp)) $adminLastName = $tmp;

		$tmp = get_config_param_from_db($dbHostForm, $dbUsernameForm, $dbPassForm, $db_name, 'administratorTelephone');
		if (!empty($tmp)) $adminPhoneForm = $tmp;

		$tmp = get_config_param_from_db($dbHostForm, $dbUsernameForm, $dbPassForm, $db_name, 'siteName');
		if (!empty($tmp)) $campusForm = $tmp;

		$tmp = get_config_param_from_db($dbHostForm, $dbUsernameForm, $dbPassForm, $db_name, 'Institution');
		if (!empty($tmp)) $institutionForm = $tmp;

		$tmp = get_config_param_from_db($dbHostForm, $dbUsernameForm, $dbPassForm, $db_name, 'InstitutionUrl');
		if (!empty($tmp)) $institutionUrlForm = $tmp;

		if (in_array($my_old_version, $update_from_version_6)) {   //for version 1.6
			$urlForm = get_config_param('rootWeb');
			$encryptPassForm = get_config_param('userPasswordCrypted');
			if (empty($encryptPassForm)) {
				$encryptPassForm = get_config_param('password_encryption');
			}
			// Managing the $encryptPassForm
			if ($encryptPassForm == '1') {
				$encryptPassForm = 'sha1';
			} elseif ($encryptPassForm == '0') {
				$encryptPassForm = 'none';
			}

			$allowSelfReg = get_config_param('allowSelfReg');
			$allowSelfRegProf = get_config_param('allowSelfRegProf');

		} else {   //for version 1.8
			$urlForm = $_configuration['root_web'];
			$encryptPassForm = get_config_param('userPasswordCrypted');
			// Managing the $encryptPassForm
			if ($encryptPassForm == '1') {
				$encryptPassForm = 'sha1';
			} elseif ($encryptPassForm == '0') {
				$encryptPassForm = 'none';
			}

			$allowSelfReg = false;
			$tmp = get_config_param_from_db($dbHostForm, $dbUsernameForm, $dbPassForm, $db_name, 'allow_registration');
			if (!empty($tmp)) $allowSelfReg = $tmp;

			$allowSelfRegProf = false;
			$tmp = get_config_param_from_db($dbHostForm, $dbUsernameForm, $dbPassForm, $db_name, 'allow_registration_as_teacher');
			if (!empty($tmp)) $allowSelfRegProf = $tmp;
		}
	}
	display_configuration_settings_form($installType, $urlForm, $languageForm, $emailForm, $adminFirstName, $adminLastName, $adminPhoneForm, $campusForm, $institutionForm, $institutionUrlForm, $encryptPassForm, $allowSelfReg, $allowSelfRegProf, $loginForm, $passForm);

} elseif (@$_POST['step5']) {
	//STEP 6 : LAST CHECK BEFORE INSTALL
?>
    <div class="RequirementHeading">
		<h2><?php echo display_step_sequence().get_lang('LastCheck'); ?></h2>
	</div>
    <div class="RequirementContent">
		<?php echo get_lang('HereAreTheValuesYouEntered'); ?>
	</div><br />

	<blockquote>
    <?php if ($installType == 'new'): ?>
	<?php echo get_lang('AdminLogin').' : <strong>'.$loginForm; ?></strong><br />
	<?php echo get_lang('AdminPass').' : <strong>'.$passForm; /* TODO: Maybe this password should be hidden too? */ ?></strong><br /><br />
	<?php else: ?>
	<?php endif;

	if (api_is_western_name_order()) {
		echo get_lang('AdminFirstName').' : '.$adminFirstName, '<br />', get_lang('AdminLastName').' : '.$adminLastName, '<br />';
	} else {
		echo get_lang('AdminLastName').' : '.$adminLastName, '<br />', get_lang('AdminFirstName').' : '.$adminFirstName, '<br />';
	}

    echo get_lang('AdminEmail').' : '.$emailForm; ?><br />
	<?php echo get_lang('AdminPhone').' : '.$adminPhoneForm; ?><br />
	<?php echo get_lang('MainLang').' : '.$languageForm; ?><br /><br />
	<?php echo get_lang('DBHost').' : '.$dbHostForm; ?><br />
	<?php echo get_lang('DBLogin').' : '.$dbUsernameForm; ?><br />
	<?php echo get_lang('DBPassword').' : '.str_repeat('*', api_strlen($dbPassForm)); ?><br />
	<?php //echo get_lang('DbPrefixForm').' : '.$dbPrefixForm.'<br />'; ?>
	<?php echo get_lang('MainDB').' : <strong>'.$dbNameForm; ?></strong>

	<?php
	if (!$singleDbForm) {
		//Showing this data only in case a user migrates from a 3 main databases (main, user, tracking)
		//@todo should be removed
		if ($installType == 'update') {
			echo '<br />';
			echo get_lang('StatDB').' : <strong>'.$dbStatsForm.'</strong>';
			if ($installType == 'new') {
				echo ' (<font color="#cc0033">'.get_lang('ReadWarningBelow').'</font>)';
			}
			echo '<br />';
			echo get_lang('UserDB').' : <strong>'.$dbUserForm.'</strong>';
			if ($installType == 'new') {
				echo ' (<font color="#cc0033">'.get_lang('ReadWarningBelow').'</font>)';
			}
			echo '<br />';
		}
	}

	//echo get_lang('EnableTracking').' : '.($enableTrackingForm ? get_lang('Yes') : get_lang('No')); ?>
	<?php //echo get_lang('SingleDb').' : '.($singleDbForm ? get_lang('One') : get_lang('Several')); ?><br /><br />
	<?php echo get_lang('AllowSelfReg').' : '.($allowSelfReg ? get_lang('Yes') : get_lang('No')); ?><br />
	<?php echo get_lang('EncryptMethodUserPass').' : ';
  	echo $encryptPassForm;
	?>
    <br /><br />

	<?php echo get_lang('CampusName').' : '.$campusForm; ?><br />
	<?php echo get_lang('InstituteShortName').' : '.$institutionForm; ?><br />
	<?php echo get_lang('InstituteURL').' : '.$institutionUrlForm; ?><br />
	<?php echo get_lang('ChamiloURL').' : '.$urlForm; ?><br />

	</blockquote>

	<?php if ($installType == 'new'): ?>
	<div style="background-color:#FFFFFF">
		<div class="warning-message">
            <center>
                <h3><?php echo get_lang('Warning'); ?> !</h3>
                <?php echo get_lang('TheInstallScriptWillEraseAllTables'); ?>
            </center>
		</div>
	</div>
	<?php endif; ?>

	<table width="100%">
        <tr>
            <td>
                <button type="submit" class="back" name="step4" value="&lt; <?php echo get_lang('Previous'); ?>" /><?php echo get_lang('Previous'); ?></button>
            </td>
            <td align="right">
                <input type="hidden" name="is_executable" id="is_executable" value="-" />
                <input type="hidden" name="step6" value="1" />
                <button id="button_step6" class="save" type="submit" name="button_step6" value="<?php echo get_lang('InstallChamilo'); ?>">
                    <?php echo get_lang('InstallChamilo'); ?>
                </button>
                <button class="save" id="button_please_wait"></button>
            </td>
        </tr>
	</table>

<?php
} elseif (@$_POST['step6']) {

	//STEP 6 : INSTALLATION PROCESS

    $current_step = 7;
    $msg = get_lang('InstallExecution');
    if ($installType == 'update') {
        $msg = get_lang('UpdateExecution');
    }
    echo '<div class="RequirementHeading">
          <h2>'.display_step_sequence().$msg.'</h2>
          <div id="pleasewait" class="warning-message">'.get_lang('PleaseWaitThisCouldTakeAWhile').'</div>
          </div>';
    
         
    // Push the web server to send these strings before we start the real
    // installation process
    flush(); 
    ob_flush();
    
	if ($installType == 'update') {

		require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
		remove_memory_and_time_limits();
		database_server_connect();
		// Initialization of the database connection encoding intentionaly is not done.
		// This is the old style for connecting to the database server, that is implemented here.

		// Inializing global variables that are to be used by the included scripts.
		$dblist = Database::get_databases();
		$perm = api_get_permissions_for_new_directories();
		$perm_file = api_get_permissions_for_new_files();

		if (empty($my_old_version)) { $my_old_version = '1.8.6.2'; } //we guess

		$_configuration['main_database'] = $dbNameForm;
		//$urlAppendPath = get_config_param('urlAppend');
        Log::notice('Starting migration process from '.$my_old_version.' ('.time().')');

    	if ($userPasswordCrypted == '1') {
			$userPasswordCrypted = 'md5';
		} elseif ($userPasswordCrypted == '0') {
			$userPasswordCrypted = 'none';
		}
        
        //Setting the single db form
        if (in_array($_POST['old_version'], $update_from_version_6)) {            
            $singleDbForm   	= get_config_param('singleDbEnabled');            
        } else {
            $singleDbForm   	= isset($_configuration['single_database']) ? $_configuration['single_database'] : false;            
        }
        
        Log::notice("singledbForm: '$singleDbForm'");
        
		Database::query("SET storage_engine = MYISAM;");

		if (version_compare($my_old_version, '1.8.7', '>=')) {
			Database::query("SET SESSION character_set_server='utf8';");
			Database::query("SET SESSION collation_server='utf8_general_ci';");
			//Database::query("SET CHARACTER SET 'utf8';"); // See task #1802.
			Database::query("SET NAMES 'utf8';");
		}

		switch ($my_old_version) {
			case '1.6':
			case '1.6.0':
			case '1.6.1':
			case '1.6.2':
			case '1.6.3':
			case '1.6.4':
			case '1.6.5':
				include 'update-db-1.6.x-1.8.0.inc.php';
				include 'update-files-1.6.x-1.8.0.inc.php';
				//intentionally no break to continue processing
			case '1.8':
			case '1.8.0':
				include 'update-db-1.8.0-1.8.2.inc.php';
				//intentionally no break to continue processing
			case '1.8.2':
				include 'update-db-1.8.2-1.8.3.inc.php';
				//intentionally no break to continue processing
			case '1.8.3':
				include 'update-db-1.8.3-1.8.4.inc.php';
				include 'update-files-1.8.3-1.8.4.inc.php';
			case '1.8.4':
				include 'update-db-1.8.4-1.8.5.inc.php';
                include 'update-files-1.8.4-1.8.5.inc.php';
			case '1.8.5':
				include 'update-db-1.8.5-1.8.6.inc.php';
                include 'update-files-1.8.5-1.8.6.inc.php';
            case '1.8.6':
                include 'update-db-1.8.6-1.8.6.1.inc.php';
                include 'update-files-1.8.6-1.8.6.1.inc.php';
            case '1.8.6.1':
                include 'update-db-1.8.6.1-1.8.6.2.inc.php';
                include 'update-files-1.8.6.1-1.8.6.2.inc.php';
            case '1.8.6.2':
                include 'update-db-1.8.6.2-1.8.7.inc.php';
                include 'update-files-1.8.6.2-1.8.7.inc.php';
                // After database conversion to UTF-8, new encoding initialization is necessary
                // to be used for the next upgrade 1.8.7[.1] -> 1.8.8.
                Database::query("SET SESSION character_set_server='utf8';");
                Database::query("SET SESSION collation_server='utf8_general_ci';");
                //Database::query("SET CHARACTER SET 'utf8';"); // See task #1802.
                Database::query("SET NAMES 'utf8';");

            case '1.8.7':
            case '1.8.7.1':
                include 'update-db-1.8.7-1.8.8.inc.php';
                include 'update-files-1.8.7-1.8.8.inc.php';
            case '1.8.8':
            case '1.8.8.2':
                //Only updates the configuration.inc.php with the new version
                include 'update-configuration.inc.php';
            case '1.8.8.4':
            case '1.8.8.6':
                include 'update-db-1.8.8-1.9.0.inc.php';
                //include 'update-files-1.8.8-1.9.0.inc.php';
                //Only updates the configuration.inc.php with the new version
                include 'update-configuration.inc.php';

                break;
            case '1.9.0':
            default:
                break;
        }
    } else {
		set_file_folder_permissions();
		database_server_connect();

		// Initialization of the database encoding to be used.
		Database::query("SET storage_engine = MYISAM;");
		Database::query("SET SESSION character_set_server='utf8';");
		Database::query("SET SESSION collation_server='utf8_general_ci';");
		//Database::query("SET CHARACTER SET 'utf8';"); // See task #1802.
		Database::query("SET NAMES 'utf8';");

		include 'install_db.inc.php';
		include 'install_files.inc.php';
	}
    display_after_install_message($installType);
    //Hide the "please wait" message sent previously
    echo '<script>$(\'#pleasewait\').hide(\'fast\');</script>';

} elseif (@$_POST['step1'] || $badUpdatePath) {
	//STEP 1 : REQUIREMENTS
    //make sure that proposed path is set, shouldn't be necessary but...
    if (empty($proposedUpdatePath)) { $proposedUpdatePath = $_POST['updatePath']; }
    display_requirements($installType, $badUpdatePath, $proposedUpdatePath, $update_from_version_8, $update_from_version_6);
} else {
	// This is the start screen.
    display_language_selection();
}
?>
</form>
</div>                  <!-- span9-->
</div>  <!-- row -->
</div> <!-- main end-->
<div class="push"></div>
</div><!-- wrapper end-->
<footer></footer>
</body>
</html>
