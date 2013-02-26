<?php
/* For licensing terms, see /license.txt */

/**
 * First initialisation file with initialisation of variables and
 * without outputting anything to browser.
 * 1. Calls global.inc.php and lang file
 * 2. Initialises $dropbox_cnf array with all relevant vars
 * 3. Often used functions
 *
 * @version 1.31
 * @copyright 2004-2005
 * @author Jan Bols <jan@ivpv.UGent.be>, main programmer
 * @author René Haentjens, severalcontributions <rene.haentjens@UGent.be>
 * @author Roan Embrechts, virtual course support
 * @author Patrick Cool <patrick.cool@UGent.be>
 				Chamilo Config Settings (AWACS)
 				Refactoring
 				tool introduction
 				folders
 				download file / folder (download icon)
 				same action on multiple documents
 				extended feedback
 * @package chamilo.dropbox
 */
/**
 * Code
 */
/*	INIT SECTION */

use \ChamiloSession as Session;

$language_file = array('dropbox', 'document');

// This var disables the link in the breadcrumbs on top of the page
//$noPHP_SELF = true;

// including the basic Chamilo initialisation file
require '../inc/global.inc.php';
$current_course_tool  = TOOL_DROPBOX;

// the dropbox configuration parameters
require_once 'dropbox_config.inc.php';

// the dropbox sanity files (adds a new table and some new fields)
//require_once 'dropbox_sanity.inc.php';

// the dropbox file that contains additional functions
require_once 'dropbox_functions.inc.php';

require_once api_get_path(LIBRARY_PATH).'mail.lib.inc.php';
require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';

// protecting the script
api_protect_course_script();


/*	Libraries */

// including the library for the sortable table
//require_once api_get_path(LIBRARY_PATH).'tablesort.lib.php';moved to autoload

// including the library for the dropbox
require_once 'dropbox_class.inc.php';

// including some libraries that are also used in the documents tool
require_once api_get_path(SYS_CODE_PATH).'document/document.inc.php';  // we use a function build_document_icon_tag
require_once api_get_path(LIBRARY_PATH).'fileDisplay.lib.php'; // the function choose_image is used
require_once api_get_path(LIBRARY_PATH).'document.lib.php';


/*	Virtual course support */

$user_id = api_get_user_id();
$course_code = api_get_course_id();
$course_info = Database::get_course_info($course_code);

$session_id = api_get_session_id();
if (empty($session_id)) {
    $is_course_member = CourseManager::is_user_subscribed_in_course($user_id, $course_code, false);
} else {
    $is_course_member = CourseManager::is_user_subscribed_in_course($user_id, $course_code, true, $session_id);
}

/*	Object Initialisation */

// we need this here because the javascript to re-upload the file needs an array
// off all the documents that have already been sent.
// @todo consider moving the javascripts in a function that displays the javascripts
// only when it is needed.
if ($_GET['action'] == 'add') {
	$dropbox_person = new Dropbox_Person($_user['user_id'], $is_courseAdmin, $is_courseTutor);
}

/*	Create javascript and htmlHeaders */

$javascript = "<script type=\"text/javascript\">
	function confirmsend ()
	{
		if (confirm(\"".get_lang('MailingConfirmSend', '')."\")){
			return true;
		} else {
			return false;
		}
		return true;
	}

	function confirmation (name)
	{
		if (confirm(\"".get_lang('ConfirmDelete', '')." : \"+ name )){
			return true;
		} else {
			return false;
		}
		return true;
	}

	function checkForm (frm)
	{
		if (frm.elements['recipients[]'].selectedIndex < 0){
			alert(\"".get_lang('NoUserSelected', '')."\");
			return false;
		} else if (frm.file.value == '') {
			alert(\"".get_lang('NoFileSpecified', '')."\");
			return false;
		} else {
			return true;
		}
	}
	";

if (dropbox_cnf('allowOverwrite')) {
	$javascript .= "
		var sentArray = new Array(";	//sentArray keeps list of all files still available in the sent files list
										//of the user.
										//This is used to show or hide the overwrite file-radio button of the upload form
	for ($i = 0; $i < count($dropbox_person->sentWork); $i++) {
		if ($i > 0) {
		    $javascript .= ", ";
		}
		$javascript .= "'".$dropbox_person->sentWork[$i]->title."'";
		//echo '***'.$dropbox_person->sentWork[$i]->title;
	}
	$javascript .= ");

		function checkfile(str)
		{

			ind = str.lastIndexOf('/'); //unix separator
			if (ind == -1) ind = str.lastIndexOf('\\\');	//windows separator
			filename = str.substring(ind+1, str.length);

			found = 0;
			for (i=0; i<sentArray.length; i++) {
				if (sentArray[i] == filename) found=1;
			}

			//always start with unchecked box
			el = getElement('cb_overwrite');
			el.checked = false;

			//show/hide checkbox
			if (found == 1) {
				displayEl('overwrite');
			} else {
				undisplayEl('overwrite');
			}
		}

		function getElement(id)
		{
			return document.getElementById ? document.getElementById(id) :
			document.all ? document.all(id) : null;
		}

		function displayEl(id)
		{
			var el = getElement(id);
			if (el && el.style) el.style.display = '';
		}

		function undisplayEl(id)
		{
			var el = getElement(id);
			if (el && el.style) el.style.display = 'none';
		}";
}

$javascript .= "
	</script>";

$htmlHeadXtra[] = $javascript;

$htmlHeadXtra[] =
"<script type=\"text/javascript\">
function confirmation (name)
{
	if (confirm(\" ". get_lang("AreYouSureToDelete") ." \"+ name + \" ?\"))
		{return true;}
	else
		{return false;}
}
</script>";

Session::write('javascript',$javascript);

$htmlHeadXtra[] = '<meta http-equiv="cache-control" content="no-cache">
	<meta http-equiv="pragma" content="no-cache">
	<meta http-equiv="expires" content="-1">';

$checked_files = false;
if (!$_GET['view'] OR $_GET['view'] == 'received') {
	$part = 'received';
} elseif ($_GET['view'] = 'sent') {
	$part = 'sent';
} else {
	header ('location: index.php?view='.$_GET['view'].'&error=Error');
}

if (($_POST['action'] == 'download_received' || $_POST['action'] == 'download_sent') and !$_POST['store_feedback']) {
	$checked_file_ids = $_POST['id'];
	if (!is_array($checked_file_ids) || count($checked_file_ids) == 0) {
		header ('location: index.php?view='.$_GET['view'].'&error=CheckAtLeastOneFile');
	} else {
		handle_multiple_actions();
	}
	exit;
}

/*
 *         AUTHORISATION SECTION
 * Prevents access of all users that are not course members
 */

if ((!$is_allowed_in_course || !$is_course_member) && !api_is_allowed_to_edit(null, true)) {
	if ($origin != 'learnpath') {
		api_not_allowed(true);//print headers/footers
	} else {
		api_not_allowed();
	}
	exit();
}

/*	BREADCRUMBS */

if ($_GET['view'] == 'received') {
	$interbreadcrumb[] = array('url' => '../dropbox/index.php', 'name' => get_lang('Dropbox', ''));
	$nameTools = get_lang('ReceivedFiles');

	if ($_GET['action'] == 'addreceivedcategory') {
		$interbreadcrumb[] = array('url' => '../dropbox/index.php?view=received', 'name' => get_lang('ReceivedFiles'));
		$nameTools = get_lang('AddNewCategory');
	}
}

if ($_GET['view'] == 'sent' OR empty($_GET['view'])) {
	$interbreadcrumb[] = array('url' => '../dropbox/index.php', 'name' => get_lang('Dropbox', ''));
	$nameTools = get_lang('SentFiles');

	if ($_GET['action'] == 'addsentcategory') {
		$interbreadcrumb[] = array('url' => '../dropbox/index.php?view=sent', 'name' => get_lang('SentFiles'));
		$nameTools = get_lang('AddNewCategory');
	}
	if ($_GET['action'] == 'add') {
		$interbreadcrumb[] = array ('url' => '../dropbox/index.php?view=sent', 'name' => get_lang('SentFiles'));
		$nameTools = get_lang('UploadNewFile');
	}
}

/*	HEADER & TITLE */

if ($origin != 'learnpath') {
    Display::display_header($nameTools, 'Dropbox');
} else { // if we come from the learning path we have to include the stylesheet and the required javascripts manually.
	echo '<link rel="stylesheet" type="text/css" href="', api_get_path(WEB_CODE_PATH), 'css/default.css">';
	echo $javascript;
}

// api_display_tool_title($nameTools);
