<?php
/* For licensing terms, see /license.txt */
/**
*	@package chamilo.admin
*/

// Language files that should be included
$language_file = array('admin', 'registration');

$cidReset = true;

require_once '../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script(true);

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : intval($_POST['user_id']);

api_protect_super_admin($user_id, null, true);

$is_platform_admin = api_is_platform_admin() ? 1 : 0;

$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/tag/jquery.fcbkcomplete.js" type="text/javascript" language="javascript"></script>';
$htmlHeadXtra[] = '<link  href="'.api_get_path(WEB_LIBRARY_PATH).'javascript/tag/style.css" rel="stylesheet" type="text/css" />';
$htmlHeadXtra[] = '
<script>

var is_platform_id = "'.$is_platform_admin.'";

<!--
function enable_expiration_date() {
	document.user_edit.radio_expiration_date[0].checked=false;
	document.user_edit.radio_expiration_date[1].checked=true;
}

function password_switch_radio_button(){
	var input_elements = document.getElementsByTagName("input");
	for (var i = 0; i < input_elements.length; i++) {
		if(input_elements.item(i).name == "reset_password" && input_elements.item(i).value == "2") {
			input_elements.item(i).checked = true;
		}
	}
}

function display_drh_list(){
    var $radios = $("input:radio[name=platform_admin]");
	if (document.getElementById("status_select").value=='.COURSEMANAGER.') {
        if (is_platform_id == 1)
            document.getElementById("id_platform_admin").style.display="block";
	} else if (document.getElementById("status_select").value=='.STUDENT.') {
        if (is_platform_id == 1)
            document.getElementById("id_platform_admin").style.display="none";
        $radios.filter("[value=0]").attr("checked", true);
	} else {
        if (is_platform_id == 1)
            document.getElementById("id_platform_admin").style.display="none";
        $radios.filter("[value=0]").attr("checked", true);
	}
}

function show_image(image,width,height) {
	width = parseInt(width) + 20;
	height = parseInt(height) + 20;
	window_x = window.open(image,\'windowX\',\'width=\'+ width + \', height=\'+ height + \' , resizable=0\');
}
//-->
</script>';

$libpath = api_get_path(LIBRARY_PATH);
require_once $libpath.'fileManage.lib.php';
require_once $libpath.'fileUpload.lib.php';
require_once $libpath.'mail.lib.inc.php';

$noPHP_SELF = true;
$tool_name = get_lang('ModifyUserInfo');

$interbreadcrumb[] = array('url' => 'index.php',"name" => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array('url' => "user_list.php","name" => get_lang('UserList'));

$table_user = Database::get_main_table(TABLE_MAIN_USER);
$table_admin = Database::get_main_table(TABLE_MAIN_ADMIN);
$sql = "SELECT u.*, a.user_id AS is_admin FROM $table_user u LEFT JOIN $table_admin a ON a.user_id = u.user_id WHERE u.user_id = '".$user_id."'";
$res = Database::query($sql);
if (Database::num_rows($res) != 1) {
	header('Location: user_list.php');
	exit;
}

$user_data = Database::fetch_array($res, 'ASSOC');
$user_data['platform_admin'] = is_null($user_data['is_admin']) ? 0 : 1;
$user_data['send_mail'] = 0;
$user_data['old_password'] = $user_data['password'];
//Convert the registration date of the user

//@todo remove the date_default_timezone_get() see UserManager::create_user function
$user_data['registration_date'] = api_get_local_time($user_data['registration_date'], null, date_default_timezone_get());
unset($user_data['password']);
$extra_data = UserManager :: get_extra_user_data($user_id, true);
$user_data = array_merge($user_data, $extra_data);

// Create the form
$form = new FormValidator('user_edit', 'post', '', '', array('style' => 'width: 60%; float: '.($text_dir == 'rtl' ? 'right;' : 'left;')));
$form->addElement('header', '', $tool_name);
$form->addElement('hidden', 'user_id', $user_id);

if (api_is_western_name_order()) {
	// Firstname
	$form->addElement('text', 'firstname', get_lang('FirstName'));
	$form->applyFilter('firstname', 'html_filter');
	$form->applyFilter('firstname', 'trim');
	$form->addRule('firstname', get_lang('ThisFieldIsRequired'), 'required');
	// Lastname
	$form->addElement('text', 'lastname', get_lang('LastName'));
	$form->applyFilter('lastname', 'html_filter');
	$form->applyFilter('lastname', 'trim');
	$form->addRule('lastname', get_lang('ThisFieldIsRequired'), 'required');
} else {
	// Lastname
	$form->addElement('text', 'lastname', get_lang('LastName'));
	$form->applyFilter('lastname', 'html_filter');
	$form->applyFilter('lastname', 'trim');
	$form->addRule('lastname', get_lang('ThisFieldIsRequired'), 'required');
	// Firstname
	$form->addElement('text', 'firstname', get_lang('FirstName'));
	$form->applyFilter('firstname', 'html_filter');
	$form->applyFilter('firstname', 'trim');
	$form->addRule('firstname', get_lang('ThisFieldIsRequired'), 'required');
}

// Official code
$form->addElement('text', 'official_code', get_lang('OfficialCode'), array('size' => '40'));
$form->applyFilter('official_code', 'html_filter');
$form->applyFilter('official_code', 'trim');

// Email
$form->addElement('text', 'email', get_lang('Email'), array('size' => '40'));
$form->addRule('email', get_lang('EmailWrong'), 'email');
if (api_get_setting('registration', 'email') == 'true') {
    $form->addRule('email', get_lang('EmailWrong'), 'required');
}

if (api_get_setting('login_is_email') == 'true') {
    $form->addRule('email', sprintf(get_lang('UsernameMaxXCharacters'), (string)USERNAME_MAX_LENGTH), 'maxlength', USERNAME_MAX_LENGTH);
    $form->addRule('email', get_lang('UserTaken'), 'username_available', $user_data['username']);
}

// OpenID
if (api_get_setting('openid_authentication') == 'true') {
	$form->addElement('text', 'openid', get_lang('OpenIDURL'), array('size' => '40'));
}

// Phone
$form->addElement('text', 'phone', get_lang('PhoneNumber'));

// Picture
$form->addElement('file', 'picture', get_lang('AddPicture'));
$allowed_picture_types = array ('jpg', 'jpeg', 'png', 'gif');
$form->addRule('picture', get_lang('OnlyImagesAllowed').' ('.implode(',', $allowed_picture_types).')', 'filetype', $allowed_picture_types);
if (strlen($user_data['picture_uri']) > 0) {
	$form->addElement('checkbox', 'delete_picture', '', get_lang('DelImage'));
}

// Username

if (api_get_setting('login_is_email') != 'true') {
    $form->addElement('text', 'username', get_lang('LoginName'), array('maxlength' => USERNAME_MAX_LENGTH));
    $form->addRule('username', get_lang('ThisFieldIsRequired'), 'required');
    $form->addRule('username', sprintf(get_lang('UsernameMaxXCharacters'), (string)USERNAME_MAX_LENGTH), 'maxlength', USERNAME_MAX_LENGTH);
    $form->addRule('username', get_lang('OnlyLettersAndNumbersAllowed'), 'username');
    $form->addRule('username', get_lang('UserTaken'), 'username_available', $user_data['username']);
}

// Password
$form->addElement('radio', 'reset_password', get_lang('Password'), get_lang('DontResetPassword'), 0);
$nb_ext_auth_source_added = 0;
if (count($extAuthSource) > 0) {
	$auth_sources = array();
	foreach($extAuthSource as $key => $info) {
	    // @todo : make uniform external authentification configuration (ex : cas and external_login ldap)
	    // Special case for CAS. CAS is activated from Chamilo > Administration > Configuration > CAS
	    // extAuthSource always on for CAS even if not activated
	    // same action for file user_add.php
	    if (($key == CAS_AUTH_SOURCE && api_get_setting('cas_activate') === 'true') || ($key != CAS_AUTH_SOURCE)) {
    		$auth_sources[$key] = $key;
    		$nb_ext_auth_source_added++;
	    }
	}
	if ($nb_ext_auth_source_added > 0) {
	    // @todo check the radio button for external authentification and select the external authentification in the menu
	    $group[] =$form->createElement('radio', 'reset_password', null, get_lang('ExternalAuthentication').' ', 3);
	    $group[] =$form->createElement('select', 'auth_source', null, $auth_sources);
	    $group[] =$form->createElement('static', '', '', '<br />');
	    $form->addGroup($group, 'password', null, '', false);
	}
}
$form->addElement('radio', 'reset_password', null, get_lang('AutoGeneratePassword'), 1);
$group = array();
$group[] =$form->createElement('radio', 'reset_password', null, null, 2);
$group[] =$form->createElement('password', 'password', null, array('onkeydown' => 'javascript: password_switch_radio_button();'));
$form->addGroup($group, 'password', null, '', false);

// Status
$status = array();
$status[COURSEMANAGER] 	= get_lang('Teacher');
$status[STUDENT] 		= get_lang('Learner');
$status[DRH] 			= get_lang('Drh');
$status[SESSIONADMIN] 	= get_lang('SessionsAdmin');

$form->addElement('select', 'status', get_lang('Profile'), $status, array('id' => 'status_select', 'onchange' => 'javascript: display_drh_list();','class'=>'chzn-select'));

$display = isset($user_data['status']) && ($user_data['status'] == STUDENT || (isset($_POST['status']) && $_POST['status'] == STUDENT)) ? 'block' : 'none';

/*
$form->addElement('html', '<div id="drh_list" style="display:'.$display.';">');
$drh_select = $form->addElement('select', 'hr_dept_id', get_lang('Drh'), array(), 'id="drh_select"');
$drh_list = UserManager :: get_user_list(array('status' => DRH), api_sort_by_first_name() ? array('firstname', 'lastname') : array('lastname', 'firstname'));

if (count($drh_list) == 0) {
	$drh_select->addOption('- '.get_lang('ThereIsNotStillAResponsible', '').' -', 0);
} else {
	$drh_select->addOption('- '.get_lang('SelectAResponsible').' -', 0);
}

foreach($drh_list as $drh) {
	$drh_select->addOption(api_get_person_name($drh['firstname'], $drh['lastname']), $drh['user_id']);
}
$form->addElement('html', '</div>');
*/

// Platform admin
if (api_is_platform_admin()) {
	$group = array();
	$group[] =$form->createElement('radio', 'platform_admin', null, get_lang('Yes'), 1);
	$group[] =$form->createElement('radio', 'platform_admin', null, get_lang('No'), 0);

	$user_data['status'] == 1 ? $display = 'block':$display = 'none';

	$form->addElement('html', '<div id="id_platform_admin" style="display:'.$display.'">');
	$form->addGroup($group, 'admin', get_lang('PlatformAdmin'), null, false);
	$form->addElement('html', '</div>');
}

//Language
$form->addElement('select_language', 'language', get_lang('Language'));

// Send email
$group = array();
$group[] =$form->createElement('radio', 'send_mail', null, get_lang('Yes'), 1);
$group[] =$form->createElement('radio', 'send_mail', null, get_lang('No'), 0);
$form->addGroup($group, 'mail', get_lang('SendMailToNewUser'), '&nbsp;', false);

// Registration Date
$form->addElement('static', 'registration_date', get_lang('RegistrationDate'), $user_data['registration_date']);

if (!$user_data['platform_admin']) {
	// Expiration Date
	$form->addElement('radio', 'radio_expiration_date', get_lang('ExpirationDate'), get_lang('NeverExpires'), 0);
	$group = array ();
	$group[] = $form->createElement('radio', 'radio_expiration_date', null, get_lang('On'), 1);
	$group[] = $form->createElement('datepicker', 'expiration_date', null, array('form_name' => $form->getAttribute('name'), 'onchange' => 'javascript: enable_expiration_date();'));
	$form->addGroup($group, 'max_member_group', null, '', false);

	// Active account or inactive account
	$form->addElement('radio', 'active', get_lang('ActiveAccount'), get_lang('Active'), 1);
	$form->addElement('radio', 'active', '', get_lang('Inactive'), 0);
}


// EXTRA FIELDS
$return_params = UserManager::set_extra_fields_in_form($form, $extra_data, 'user_edit', true, $user_id);
$jquery_ready_content = $return_params['jquery_ready_content'];

// the $jquery_ready_content variable collects all functions that will be load in the $(document).ready javascript function
$htmlHeadXtra[] ='<script>
$(document).ready(function(){
	'.$jquery_ready_content.'
});
</script>';


// Submit button
$form->addElement('style_submit_button', 'submit', get_lang('ModifyInformation'), 'class="save"');

// Set default values
$user_data['reset_password'] = 0;
$expiration_date = $user_data['expiration_date'];

if ($expiration_date == '0000-00-00 00:00:00') {
	$user_data['radio_expiration_date'] = 0;
	$user_data['expiration_date'] = array();
	$user_data['expiration_date']['d'] = date('d');
	$user_data['expiration_date']['F'] = date('m');
	$user_data['expiration_date']['Y'] = date('Y');
} else {
	$user_data['radio_expiration_date'] = 1;

	$user_data['expiration_date'] = array();
	$user_data['expiration_date']['d'] = substr($expiration_date, 8, 2);
	$user_data['expiration_date']['F'] = substr($expiration_date, 5, 2);
	$user_data['expiration_date']['Y'] = substr($expiration_date, 0, 4);

    $user_data['expiration_date']['H'] = substr($expiration_date, 11, 2);
    $user_data['expiration_date']['i'] = substr($expiration_date, 14, 2);
}
$form->setDefaults($user_data);

$error_drh = false;
// Validate form
if ($form->validate()) {

	$user = $form->getSubmitValues();
	$is_user_subscribed_in_course = CourseManager::is_user_subscribed_in_course($user['user_id']);

	if ($user['status'] == DRH && $is_user_subscribed_in_course) {
		$error_drh = true;
	} else {
		$picture_element = $form->getElement('picture');
		$picture = $picture_element->getValue();

		$picture_uri = $user_data['picture_uri'];
		if ($user['delete_picture']) {
			$picture_uri = UserManager::delete_user_picture($user_id);
		} elseif (!empty($picture['name'])) {
			$picture_uri = UserManager::update_user_picture($user_id, $_FILES['picture']['name'], $_FILES['picture']['tmp_name']);
		}

		$lastname = $user['lastname'];
		$firstname = $user['firstname'];
        $password = $user['password'];
        $auth_source = $user['auth_source'];

		$official_code = $user['official_code'];
		$email = $user['email'];
		$phone = $user['phone'];
		$username = $user['username'];
		$status = intval($user['status']);
		$platform_admin = intval($user['platform_admin']);
		$send_mail = intval($user['send_mail']);
		$reset_password = intval($user['reset_password']);
		$hr_dept_id = intval($user['hr_dept_id']);
		$language = $user['language'];
		if ($user['radio_expiration_date'] == '1' && !$user_data['platform_admin']) {
            $expiration_date = return_datetime_from_array($user['expiration_date']);
		} else {
			$expiration_date = '0000-00-00 00:00:00';
		}

		$active = $user_data['platform_admin'] ? 1 : intval($user['active']);

        //If the user is set to admin the status will be overwrite by COURSEMANAGER = 1
        if ($platform_admin == 1) {
            $status = COURSEMANAGER;
        }

        if (api_get_setting('login_is_email') == 'true') {
            $username = $email;
        }
		UserManager::update_user($user_id, $firstname, $lastname, $username, $password, $auth_source, $email, $status, $official_code, $phone, $picture_uri, $expiration_date, $active, null, $hr_dept_id, null, $language, null, $send_mail, $reset_password);

		if (api_get_setting('openid_authentication') == 'true' && !empty($user['openid'])) {
			$up = UserManager::update_openid($user_id,$user['openid']);
		}
		if ($user_id != $_SESSION['_uid']) {
			if ($platform_admin == 1) {
                UserManager::add_user_as_admin($user_id);
			} else {
                UserManager::remove_user_admin($user_id);
			}
		}

		foreach ($user as $key => $value) {
			if (substr($key, 0, 6) == 'extra_') {
                //an extra field
                //@todo remove this as well as in the profile.php ad put it in a function
                if (is_array($value) && isset($value['Y']) && isset($value['F']) && isset($value['d'])) {
                    if (isset($value['H']) && isset($value['i'])) {
                        // extra field date time
                        $time = mktime($value['H'],$value['i'],0,$value['F'],$value['d'],$value['Y']);
                        $value = date('Y-m-d H:i:s',$time);
                    } else {
                        // extra field date
                        $time = mktime(0,0,0,$value['F'],$value['d'],$value['Y']);
                        $value = date('Y-m-d',$time);
                    }
                }
				UserManager::update_extra_field_value($user_id, substr($key, 6), $value);
			}
		}
		$tok = Security::get_token();
		header('Location: user_list.php?action=show_message&message='.urlencode(get_lang('UserUpdated')).'&sec_token='.$tok);
		exit();
	}
}

$message = null;
if ($error_drh) {
	$err_msg = get_lang('StatusCanNotBeChangedToHumanResourcesManager');
	$message = Display::return_message($err_msg, 'error');
}

// USER PICTURE
$image_path = UserManager::get_user_picture_path_by_id($user_id,'web');
$image_dir = $image_path['dir'];
$image = $image_path['file'];
$image_file = ($image != '' ? $image_dir.$image : api_get_path(WEB_CODE_PATH).'img/unknown.jpg');
$image_size = api_getimagesize($image_file);

$img_attributes = 'src="'.$image_file.'?rand='.time().'" '
	.'alt="'.api_get_person_name($user_data['firstname'], $user_data['lastname']).'" '
	.'style="float:'.($text_dir == 'rtl' ? 'left' : 'right').'; padding:5px;" ';

if ($image_size['width'] > 300) { //limit display width to 300px
	$img_attributes .= 'width="300" ';
}

// get the path,width and height from original picture
$big_image = $image_dir.'big_'.$image;
$big_image_size = api_getimagesize($big_image);
$big_image_width = $big_image_size['width'];
$big_image_height = $big_image_size['height'];
$url_big_image = $big_image.'?rnd='.time();

$content = null;
if ($image == '') {
	$content .= '<img '.$img_attributes.' />';
} else {
	$content .= '<input type="image" '.$img_attributes.' onclick="javascript: return show_image(\''.$url_big_image.'\',\''.$big_image_width.'\',\''.$big_image_height.'\');"/>';
}

// Display form
$content .= $form->return_form();

$tpl = new Template($tool_name);
$tpl->assign('message', $message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();