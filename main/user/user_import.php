<?php
/* For licensing terms, see /license.txt */

$language_file = array('registration', 'admin', 'userInfo');

require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'import.lib.php';

$this_section = SECTION_COURSES;

// notice for unauthorized people.
api_protect_course_script(true);

if (api_get_setting('allow_user_course_subscription_by_course_admin') == 'false') {
    if (!api_is_platform_admin()) {
        api_not_allowed(true);
    }
}

$tool_name = get_lang('ImportUsersToACourse');

$interbreadcrumb[] = array ("url" => "user.php", "name" => get_lang("Users"));
$interbreadcrumb[] = array ("url" => "#", "name" => get_lang("ImportUsersToACourse"));

$form = new FormValidator('user_import','post','user_import.php');
$form->addElement('header', $tool_name);
$form->addElement('file', 'import_file', get_lang('ImportCSVFileLocation'));

$form->addElement('checkbox', 'unsubscribe_users', null, get_lang('UnsubscribeUsersAlreadyAddedInCourse'));

$form->addElement('style_submit_button', 'submit', get_lang('Import'), 'class="save"');

$course_code = api_get_course_id();

if (empty($course_code)) {
    api_not_allowed(true);
}


$session_id = api_get_session_id();

$message = '';
$user_to_show = array();
$type = '';

if ($form->validate()) {
    if (isset($_FILES['import_file']['size']) && $_FILES['import_file']['size'] !== 0) {
        
        $unsubscribe_users = isset($_POST['unsubscribe_users']) ? true : false;
                
        $users  = Import::csv_to_array($_FILES['import_file']['tmp_name']);
        
        $invalid_users  = array();
        $clean_users    = array();
                
        if (!empty($users)) { 
            
            foreach ($users as $user_data) {
                $username = $user_data['username'];
                $user_id = UserManager::get_user_id_from_username($username);                
                $user_info = api_get_user_info($user_id);
                if ($user_id && !empty($user_info)) {
                    $clean_users[$user_id] = $user_info;                    
                } else {
                    $invalid_users[] = $user_id;
                }
            }
            
            if (empty($invalid_users)) {
                $type = 'confirmation';
                $message = get_lang('ListOfUsersSubscribedToCourse');
                
                if ($unsubscribe_users) {
                    $current_user_list = CourseManager::get_user_list_from_course_code($course_code, $session_id);
                    if (!empty($current_user_list)) {
                        $user_ids = array();
                        foreach ($current_user_list as $user) {
                            $user_ids[]= $user['user_id'];
                        }
                        CourseManager::unsubscribe_user($user_ids, $course_code, $session_id);
                    }                    
                }                
                foreach ($clean_users as $user_info) {      
                    $user_id = $user_info['user_id'];
                    CourseManager :: subscribe_user($user_id, $course_code, STUDENT, $session_id);
                    if (empty($session_id)) {
                        //just to make sure
                        if (CourseManager :: is_user_subscribed_in_course($user_id, $course_code)) {
                            $user_to_show[]= $user_info['complete_name'];
                        }
                    } else {
                        //just to make sure
                        if (CourseManager :: is_user_subscribed_in_course($user_id, $course_code, true, $session_id)) {
                            $user_to_show[]= $user_info['complete_name'];
                        }
                    }
                }   
            } else {
                $message = get_lang('CheckUsersWithId');
                $type = 'warning';                
                foreach ($invalid_users as $invalid_user) {                    
                    $user_to_show[]= $invalid_user;
                }        
            }            
        }
    }
}

Display::display_header();

if (!empty($message)) {
    if (!empty($user_to_show)) {
        if ($type == 'confirmation') {
            Display::display_confirmation_message($message.': <br />'.implode(', ', $user_to_show), false);
        } else {
            Display::display_warning_message($message.': '.implode(', ', $user_to_show));
        }
    } else {
        Display::display_error_message(get_lang('ErrorsWhenImportingFile'));
    }
}
    
$form->display();

echo get_lang('CSVMustLookLike');
echo '<blockquote><pre>
    username;
    jdoe;
    jmontoya;
</pre>  
</blockquote>';

Display::display_footer();