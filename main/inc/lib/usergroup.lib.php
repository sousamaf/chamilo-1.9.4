<?php
/* For licensing terms, see /license.txt */
/**
 * This class provides methods for the UserGroup management.
 * Include/require it in your code to use its features.
 * @package chamilo.library
 */
/**
 * Code
 */
require_once 'model.lib.php';

/**
 * Class
 * @package chamilo.library
 */
class UserGroup extends Model
{
    var $columns = array('id', 'name', 'description');

    public function __construct()
    {
        $this->table = Database::get_main_table(TABLE_USERGROUP);
        $this->usergroup_rel_user_table = Database::get_main_table(TABLE_USERGROUP_REL_USER);
        $this->usergroup_rel_course_table = Database::get_main_table(TABLE_USERGROUP_REL_COURSE);
        $this->usergroup_rel_session_table = Database::get_main_table(TABLE_USERGROUP_REL_SESSION);
        $this->table_course = Database::get_main_table(TABLE_MAIN_COURSE);
    }

    public function get_count()
    {
        $row = Database::select('count(*) as count', $this->table, array(), 'first');
        return $row['count'];
    }

    public function get_usergroup_by_course_with_data_count($course_id)
    {
        $row = Database::select('count(*) as count', $this->usergroup_rel_course_table, array('where' => array('course_id = ?' => $course_id)), 'first');
        return $row['count'];
    }

    public function get_id_by_name($name)
    {
        $row = Database::select('id', $this->table, array('where' => array('name = ?' => $name)), 'first');
        return $row['id'];
    }

    /**
     * Displays the title + grid
     */
    function display()
    {
        // action links
        echo '<div class="actions">';
        echo '<a href="../admin/index.php">'.Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('PlatformAdmin'), '', '32').'</a>';

        echo '<a href="'.api_get_self().'?action=add">'.Display::return_icon('new_class.png', get_lang('AddClasses'), '', '32').'</a>';

        echo Display::url(Display::return_icon('import_csv.png', get_lang('Import'), array(), ICON_SIZE_MEDIUM), 'usergroup_import.php');
        echo Display::url(Display::return_icon('export_csv.png', get_lang('Export'), array(), ICON_SIZE_MEDIUM), 'usergroup_export.php');

        echo '</div>';
        echo Display::grid_html('usergroups');
    }

    function display_teacher_view()
    {
        // action links
        echo Display::grid_html('usergroups');
    }

    /**
     * Gets a list of course ids by user group
     * @param   int user group id
     * @return  array
     */
    public function get_courses_by_usergroup($id)
    {
        $results = Database::select('course_id', $this->usergroup_rel_course_table, array('where' => array('usergroup_id = ?' => $id)));
        $array = array();
        if (!empty($results)) {
            foreach ($results as $row) {
                $array[] = $row['course_id'];
            }
        }
        return $array;
    }

    public function get_usergroup_in_course($options = array())
    {
        $sql = "SELECT u.* FROM {$this->usergroup_rel_course_table} usergroup
                INNER JOIN  {$this->table} u
                ON (u.id = usergroup.usergroup_id)
                INNER JOIN {$this->table_course} c
                ON (usergroup.course_id = c.id)
               ";
        $conditions = Database::parse_conditions($options);
        $sql .= $conditions;
        $result = Database::query($sql);
        $array = Database::store_result($result, 'ASSOC');
        return $array;
    }

    public function get_usergroup_not_in_course($options = array())
    {
        $course_id = null;
        if (isset($options['course_id'])) {
            $course_id = intval($options['course_id']);
            unset($options['course_id']);
        }
        if (empty($course_id)) {
            return false;
        }
        $sql = "SELECT DISTINCT u.id, name
                FROM {$this->table} u
                LEFT OUTER JOIN {$this->usergroup_rel_course_table} urc
                ON (u.id = urc.usergroup_id AND course_id = $course_id)
        ";
        $conditions = Database::parse_conditions($options);
        $sql .= $conditions;
        $result = Database::query($sql);
        $array = Database::store_result($result, 'ASSOC');
        return $array;
    }

    public function get_usergroup_by_course($course_id)
    {
        $options = array('where' => array('course_id = ?' => $course_id));
        $results = Database::select('usergroup_id', $this->usergroup_rel_course_table, $options);
        $array = array();
        if (!empty($results)) {
            foreach ($results as $row) {
                $array[] = $row['usergroup_id'];
            }
        }
        return $array;
    }

    public function usergroup_was_added_in_course($usergroup_id, $course_id)
    {
        $results = Database::select('usergroup_id', $this->usergroup_rel_course_table, array('where' => array('course_id = ? AND usergroup_id = ?' => array($course_id, $usergroup_id))));
        if (empty($results)) {
            return false;
        }
        return true;
    }

    /**
     * Gets a list of session ids by user group
     * @param   int     user group id
     * @return  array
     */
    public function get_sessions_by_usergroup($id)
    {
        $results = Database::select('session_id', $this->usergroup_rel_session_table, array('where' => array('usergroup_id = ?' => $id)));
        $array = array();
        if (!empty($results)) {
            foreach ($results as $row) {
                $array[] = $row['session_id'];
            }
        }
        return $array;
    }

    /**
     * Gets a list of user ids by user group
     * @param   int     user group id
     * @return  array   with a list of user ids
     */
    public function get_users_by_usergroup($id = null)
    {
        if (empty($id)) {
            $conditions = array();
        } else {
            $conditions = array('where' => array('usergroup_id = ?' => $id));
        }
        $results = Database::select('user_id', $this->usergroup_rel_user_table, $conditions, true);
        $array = array();
        if (!empty($results)) {
            foreach ($results as $row) {
                $array[] = $row['user_id'];
            }
        }
        return $array;
    }

    /**
     * Gets the usergroup id list by user id
     * @param   int user id
     */
    public function get_usergroup_by_user($id)
    {
        $results = Database::select('usergroup_id', $this->usergroup_rel_user_table, array('where' => array('user_id = ?' => $id)));
        $array = array();
        if (!empty($results)) {
            foreach ($results as $row) {
                $array[] = $row['usergroup_id'];
            }
        }
        return $array;
    }

    /**
     * Subscribes sessions to a group  (also adding the members of the group in the session and course)
     * @param   int     usergroup id
     * @param   array   list of session ids
     */
    function subscribe_sessions_to_usergroup($usergroup_id, $list)
    {
        $current_list = self::get_sessions_by_usergroup($usergroup_id);
        $user_list = self::get_users_by_usergroup($usergroup_id);

        $delete_items = $new_items = array();
        if (!empty($list)) {
            foreach ($list as $session_id) {
                if (!in_array($session_id, $current_list)) {
                    $new_items[] = $session_id;
                }
            }
        }
        if (!empty($current_list)) {
            foreach ($current_list as $session_id) {
                if (!in_array($session_id, $list)) {
                    $delete_items[] = $session_id;
                }
            }
        }

        //Deleting items
        if (!empty($delete_items)) {
            foreach ($delete_items as $session_id) {
                if (!empty($user_list)) {
                    foreach ($user_list as $user_id) {
                        SessionManager::unsubscribe_user_from_session($session_id, $user_id);
                    }
                }
                Database::delete($this->usergroup_rel_session_table, array('usergroup_id = ? AND session_id = ?' => array($usergroup_id, $session_id)));
            }
        }

        //Addding new relationships
        if (!empty($new_items)) {
            foreach ($new_items as $session_id) {
                $params = array('session_id' => $session_id, 'usergroup_id' => $usergroup_id);
                Database::insert($this->usergroup_rel_session_table, $params);

                if (!empty($user_list)) {
                    SessionManager::suscribe_users_to_session($session_id, $user_list, null, false);
                }
            }
        }
    }

    /**
     * Subscribes courses to a group (also adding the members of the group in the course)
     * @param   int     usergroup id
     * @param   array   list of course ids (integers)
     */
    function subscribe_courses_to_usergroup($usergroup_id, $list, $delete_groups = true)
    {
        $current_list = self::get_courses_by_usergroup($usergroup_id);
        $user_list = self::get_users_by_usergroup($usergroup_id);

        $delete_items = $new_items = array();
        if (!empty($list)) {
            foreach ($list as $id) {
                if (!in_array($id, $current_list)) {
                    $new_items[] = $id;
                }
            }
        }

        if (!empty($current_list)) {
            foreach ($current_list as $id) {
                if (!in_array($id, $list)) {
                    $delete_items[] = $id;
                }
            }
        }

        if ($delete_groups) {
            self::unsubscribe_courses_from_usergroup($usergroup_id, $delete_items);
        }

        //Addding new relationships
        if (!empty($new_items)) {
            foreach ($new_items as $course_id) {
                $course_info = api_get_course_info_by_id($course_id);
                if (!empty($user_list)) {
                    foreach ($user_list as $user_id) {
                        CourseManager::subscribe_user($user_id, $course_info['code']);
                    }
                }
                $params = array('course_id' => $course_id, 'usergroup_id' => $usergroup_id);
                Database::insert($this->usergroup_rel_course_table, $params);
            }
        }
    }

    function unsubscribe_courses_from_usergroup($usergroup_id, $delete_items)
    {
        //Deleting items
        if (!empty($delete_items)) {
            $user_list = self::get_users_by_usergroup($usergroup_id);
            foreach ($delete_items as $course_id) {
                $course_info = api_get_course_info_by_id($course_id);
                if (!empty($user_list)) {
                    foreach ($user_list as $user_id) {
                        CourseManager::unsubscribe_user($user_id, $course_info['code']);
                    }
                }
                Database::delete($this->usergroup_rel_course_table, array('usergroup_id = ? AND course_id = ?' => array($usergroup_id, $course_id)));
            }
        }
    }

    /**
     * Subscribes users to a group
     * @param   int     usergroup id
     * @param   array   list of user ids
     */
    function subscribe_users_to_usergroup($usergroup_id, $list, $delete_users_not_present_in_list = true)
    {
        $current_list = self::get_users_by_usergroup($usergroup_id);
        $course_list = self::get_courses_by_usergroup($usergroup_id);
        $session_list = self::get_sessions_by_usergroup($usergroup_id);

        $delete_items = array();
        $new_items = array();

        if (!empty($list)) {
            foreach ($list as $user_id) {
                if (!in_array($user_id, $current_list)) {
                    $new_items[] = $user_id;
                }
            }
        }

        if (!empty($current_list)) {
            foreach ($current_list as $user_id) {
                if (!in_array($user_id, $list)) {
                    $delete_items[] = $user_id;
                }
            }
        }

        //Deleting items
        if (!empty($delete_items) && $delete_users_not_present_in_list) {
            foreach ($delete_items as $user_id) {
                //Removing courses
                if (!empty($course_list)) {
                    foreach ($course_list as $course_id) {
                        $course_info = api_get_course_info_by_id($course_id);
                        CourseManager::unsubscribe_user($user_id, $course_info['code']);
                    }
                }
                //Removing sessions
                if (!empty($session_list)) {
                    foreach ($session_list as $session_id) {
                        SessionManager::unsubscribe_user_from_session($session_id, $user_id);
                    }
                }
                Database::delete($this->usergroup_rel_user_table, array('usergroup_id = ? AND user_id = ?' => array($usergroup_id, $user_id)));
            }
        }

        //Addding new relationships
        if (!empty($new_items)) {
            //Adding sessions
            if (!empty($session_list)) {
                foreach ($session_list as $session_id) {
                    SessionManager::suscribe_users_to_session($session_id, $new_items, null, false);
                }
            }

            foreach ($new_items as $user_id) {
                //Adding courses
                if (!empty($course_list)) {
                    foreach ($course_list as $course_id) {
                        $course_info = api_get_course_info_by_id($course_id);
                        CourseManager::subscribe_user($user_id, $course_info['code']);
                    }
                }
                $params = array('user_id' => $user_id, 'usergroup_id' => $usergroup_id);
                Database::insert($this->usergroup_rel_user_table, $params);
            }
        }
    }

    function usergroup_exists($name)
    {
        $sql = "SELECT * FROM $this->table WHERE name='".Database::escape_string($name)."'";
        $res = Database::query($sql);
        return Database::num_rows($res) != 0;
    }

}