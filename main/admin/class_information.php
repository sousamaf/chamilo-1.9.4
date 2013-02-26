<?php
/* For licensing terms, see /license.txt */
/**
*	@package chamilo.admin
*/
/**
 * Code
*	@author	 Bart Mollet
*/

// Language files that should be included.
$language_file = 'admin';

$cidReset = true;

require_once '../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

require_once api_get_path(LIBRARY_PATH).'classmanager.lib.php';

if (!isset($_GET['id'])) {
    api_not_allowed();
}

$interbreadcrumb[] = array ('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array ('url' => 'class_list.php', 'name' => get_lang('AdminClasses'));

$class_id = $_GET['id'];
$class = ClassManager::get_class_info($class_id);

$tool_name = $class['name'];
Display::display_header($tool_name);
//api_display_tool_title($tool_name);

/**
 * Show all users subscribed in this class.
 */
echo '<h4>'.get_lang('Users').'</h4>';
$users = ClassManager::get_users($class_id);
if (count($users) > 0) {
    $is_western_name_order = api_is_western_name_order();
    $table_header[] = array (get_lang('OfficialCode'), true);
    if ($is_western_name_order) {
        $table_header[] = array (get_lang('FirstName'), true);
        $table_header[] = array (get_lang('LastName'), true);
    } else {
        $table_header[] = array (get_lang('LastName'), true);
        $table_header[] = array (get_lang('FirstName'), true);
    }
    $table_header[] = array (get_lang('Email'), true);
    $table_header[] = array (get_lang('Status'), true);
    $table_header[] = array ('', false);
    $data = array();
    foreach($users as $index => $user) {
        $username = api_htmlentities(sprintf(get_lang('LoginX'), $user['username']), ENT_QUOTES);
        $row = array ();
        $row[] = $user['official_code'];
        if ($is_western_name_order) {
            $row[] = $user['firstname'];
            $row[] = "<span title='$username'>".$user['lastname']."</span>";
        } else {
            $row[] = "<span title='$username'>".$user['lastname']."</span>";
            $row[] = $user['firstname'];
        }
        $row[] = Display :: encrypted_mailto_link($user['email'], $user['email']);
        $row[] = $user['status'] == 5 ? get_lang('Student') : get_lang('Teacher');
        $row[] = '<a href="user_information.php?user_id='.$user['user_id'].'">'.Display::return_icon('synthese_view.gif', get_lang('Info')).'</a>';
        $data[] = $row;
    }
    Display::display_sortable_table($table_header,$data,array(),array(),array('id'=>$_GET['id']));
} else {
    echo get_lang('NoUsersInClass');
}

/**
 * Show all courses in which this class is subscribed.
 */
$courses = ClassManager::get_courses($class_id);
if (count($courses) > 0) {
    $header[] = array (get_lang('Code'), true);
    $header[] = array (get_lang('Title'), true);
    $header[] = array ('', false);
    $data = array ();
    foreach( $courses as $index => $course) {
        $row = array ();
        $row[] = $course['visual_code'];
        $row[] = $course['title'];
        $row[] = '<a href="course_information.php?code='.$course['code'].'">'.Display::return_icon('info_small.gif', get_lang('Delete')).'</a>'.
                    '<a href="'.api_get_path(WEB_COURSE_PATH).$course['directory'].'">'.Display::return_icon('course_home.gif', get_lang('CourseHome')).'</a>' .
                    '<a href="course_edit.php?course_code='.$course['code'].'">'.Display::return_icon('edit.gif', get_lang('Edit')).'</a>';
        $data[] = $row;
    }
    echo '<p><b>'.get_lang('Courses').'</b></p>';
    echo '<blockquote>';
    Display :: display_sortable_table($header, $data, array (), array (), array('id'=>$_GET['id']));
    echo '</blockquote>';
} else {
    echo '<p>'.get_lang('NoCoursesForThisClass').'</p>';
}

// Displaying the footer.
Display::display_footer();
