<?php
/* For licensing terms, see /license.txt */
/**
*	This script allows platform admins to add users to urls.
*	It displays a list of users and a list of courses;
*	you can select multiple users and courses and then click on
*	@package chamilo.admin
*	@author Julio Montoya <gugli100@gmail.com>
*/

// name of the language file that needs to be included
$language_file = 'admin';
$cidReset = true;
require_once '../inc/global.inc.php';
$this_section=SECTION_PLATFORM_ADMIN;

require_once (api_get_path(LIBRARY_PATH).'urlmanager.lib.php');
api_protect_global_admin_script();
if (!api_get_multiple_access_url()) {
	header('Location: index.php');
	exit;
}

/*
	Global constants and variables
*/

$form_sent = 0;
$first_letter_course = '';
$courses = array ();
$url_list = array();
$users = array();

$tbl_access_url_rel_course 	= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
$tbl_access_url 			= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
$tbl_user 					= Database :: get_main_table(TABLE_MAIN_USER);
$tbl_course					= Database :: get_main_table(TABLE_MAIN_COURSE);

/*	Header   */
$tool_name = get_lang('AddCoursesToURL');
$interbreadcrumb[] = array ('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array ('url' => 'access_urls.php', 'name' => get_lang('MultipleAccessURLs'));

/*		MAIN CODE   */

Display :: display_header($tool_name);

echo '<div class="actions">';
echo Display::url(Display::return_icon('edit.gif',get_lang('EditCoursesToURL'),''), api_get_path(WEB_CODE_PATH).'admin/access_url_edit_courses_to_url.php"'); 
echo '</div>';

api_display_tool_title($tool_name);

if ($_POST['form_sent']) {
	$form_sent = $_POST['form_sent'];
	$courses = is_array($_POST['course_list']) ? $_POST['course_list'] : array() ;
	$url_list = is_array($_POST['url_list']) ? $_POST['url_list'] : array() ;
	$first_letter_course = $_POST['first_letter_course'];

	foreach($users as $key => $value) {
		$users[$key] = intval($value);
	}

	if ($form_sent == 1) {
		if ( count($courses) == 0 || count($url_list) == 0) {
			Display :: display_error_message(get_lang('AtLeastOneCourseAndOneURL'));
		} else {
			UrlManager::add_courses_to_urls($courses,$url_list);
			Display :: display_confirmation_message(get_lang('CourseBelongURL'));
		}
	}
}



/*	Display GUI */

if(empty($first_letter_user)) {
	$sql = "SELECT count(*) as num_courses FROM $tbl_course";
	$result = Database::query($sql);
	$num_row = Database::fetch_array($result);
	if($num_row['num_courses']>1000)
	{//if there are too much num_courses to gracefully handle with the HTML select list,
	 // assign a default filter on users names
		$first_letter_user = 'A';
	}
	unset($result);
}

$first_letter_course = Database::escape_string($first_letter_course);
$sql = "SELECT code, title FROM $tbl_course
		WHERE title LIKE '".$first_letter_course."%' OR title LIKE '".api_strtolower($first_letter_course)."%'
		ORDER BY title, code DESC ";

$result = Database::query($sql);
$db_courses = Database::store_result($result);
unset($result);

$sql = "SELECT id, url FROM $tbl_access_url  WHERE active=1 ORDER BY url";
$result = Database::query($sql);
$db_urls = Database::store_result($result);
unset($result);
?>

<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>" style="margin:0px;">
 <input type="hidden" name="form_sent" value="1"/>
  <table border="0" cellpadding="5" cellspacing="0" width="100%">
   <tr>
    <td width="40%" align="center">
     <b><?php echo get_lang('CourseList'); ?></b>
     <br/><br/>
     <?php echo get_lang('FirstLetterCourse'); ?> :
     <select name="first_letter_course" onchange="javascript:document.formulaire.form_sent.value='2'; document.formulaire.submit();">
      <option value="">--</option>
      <?php
        echo Display :: get_alphabet_options($first_letter_course);
        echo Display :: get_numeric_options(0,9,$first_letter_course);
      ?>
     </select>
    </td>
        <td width="20%">&nbsp;</td>
    <td width="40%" align="center">
     <b><?php echo get_lang('URLList'); ?> :</b>
    </td>
   </tr>
   <tr>
    <td width="40%" align="center">
     <select name="course_list[]" multiple="multiple" size="20" style="width:400px;">
		<?php
		foreach ($db_courses as $course) {
			?>
			<option value="<?php echo $course['code']; ?>" <?php if(in_array($course['code'],$courses)) echo 'selected="selected"'; ?>><?php echo $course['title'].' ('.$course['code'].')'; ?></option>
			<?php
		}
		?>
    </select>
   </td>
   <td width="20%" valign="middle" align="center">    
    <button type="submit" class="add"> <?php echo get_lang('AddCoursesToThatURL'); ?> </button>
   </td>
   <td width="40%" align="center">
    <select name="url_list[]" multiple="multiple" size="20" style="width:300px;">
		<?php
		foreach ($db_urls as $url_obj) {
			?>
			<option value="<?php echo $url_obj['id']; ?>" <?php if(in_array($url_obj['id'],$url_list)) echo 'selected="selected"'; ?>><?php echo $url_obj['url']; ?></option>
			<?php
		}
		?>
    </select>
   </td>
  </tr>
 </table>
</form>
<?php
/*
		FOOTER
*/
Display :: display_footer();
?>