<?php
/* For licensing terms, see /license.txt */
/**
* This is the class library for this application.
* @package	 chamilo.library
*/
/**
 * Code
 */
class ClassManager
{
	/**
	 * Get class information
	 * note: This function can't be named get_class() because that's a standard
	 * php-function.
	 */
	function get_class_info($class_id) {
        $class_id = intval($class_id);        
		$table_class = Database :: get_main_table(TABLE_MAIN_CLASS);
		$sql = "SELECT * FROM $table_class WHERE id='".$class_id."'";
		$res = Database::query($sql);
		return Database::fetch_array($res, 'ASSOC');
	}
	/**
	 * Change the name of a class
	 * @param string $name The new name
	 * @param int $class_id The class id
	 */
	function set_name($name, $class_id) {
        $class_id = intval($class_id);        
		$table_class = Database :: get_main_table(TABLE_MAIN_CLASS);
		$sql = "UPDATE $table_class SET name='".Database::escape_string($name)."' WHERE id='".$class_id."'";
		$res = Database::query($sql);
	}
	/**
	 * Create a class
	 * @param string $name
	 */
	function create_class($name) {
		$table_class = Database :: get_main_table(TABLE_MAIN_CLASS);
		$sql = "INSERT INTO $table_class SET name='".Database::escape_string($name)."'";
		Database::query($sql);
		return Database::affected_rows() == 1;
	}
	/**
	 * Check if a classname is allready in use
	 * @param string $name
	 */
	function class_name_exists($name) {
		$table_class = Database :: get_main_table(TABLE_MAIN_CLASS);
		$sql = "SELECT * FROM $table_class WHERE name='".Database::escape_string($name)."'";
		$res = Database::query($sql);
		return Database::num_rows($res) != 0;
	}
	/**
	 * Delete a class
	 * @param int $class_id
	 * @todo Add option to unsubscribe class-members from the courses where the
	 * class was subscibed to
	 */
	function delete_class($class_id) {
        $class_id = intval($class_id);        
		$table_class = Database :: get_main_table(TABLE_MAIN_CLASS);
		$table_class_course = Database :: get_main_table(TABLE_MAIN_COURSE_CLASS);
		$table_class_user = Database :: get_main_table(TABLE_MAIN_CLASS_USER);
		$sql = "DELETE FROM $table_class_user WHERE class_id = '".$class_id."'";
		Database::query($sql);
		$sql = "DELETE FROM $table_class_course WHERE class_id = '".$class_id."'";
		Database::query($sql);
		$sql = "DELETE FROM $table_class WHERE id = '".$class_id."'";
		Database::query($sql);
	}
	/**
	 * Get all users from a class
	 * @param int $class_id
	 * @return array
	 */
	function get_users($class_id) {        
        $class_id = intval($class_id);        
		$table_class_user = Database :: get_main_table(TABLE_MAIN_CLASS_USER);
		$table_user = Database :: get_main_table(TABLE_MAIN_USER);
		$sql = "SELECT * FROM $table_class_user cu, $table_user u WHERE cu.class_id = '".$class_id."' AND cu.user_id = u.user_id";
		$res = Database::query($sql);
		$users = array ();
		while ($user = Database::fetch_array($res, 'ASSOC')) {
			$users[] = $user;
		}
		return $users;
	}
	/**
	 * Add a user to a class. If the class is subscribed to a course, the new
	 * user will also be subscribed to that course.
	 * @param int $user_id The user id
	 * @param int $class_id The class id
	 */
	function add_user($user_id, $class_id) {
		$table_class_user = Database :: get_main_table(TABLE_MAIN_CLASS_USER);
        $user_id  = intval($user_id);
        $class_id = intval($class_id);
		$sql = "INSERT IGNORE INTO $table_class_user SET user_id = '".$user_id."', class_id='".$class_id."'";
		Database::query($sql);
		$courses = ClassManager :: get_courses($class_id);
		foreach ($courses as $index => $course) {
			CourseManager :: subscribe_user($user_id, $course['course_code']);
		}
	}
	/**
	 * Unsubscribe a user from a class. If the class is also subscribed in a
	 * course, the user will be unsubscribed from that course
	 * @param int $user_id The user id
	 * @param int $class_id The class id
	 */
	function unsubscribe_user($user_id, $class_id) {
        $class_id = intval($class_id);        
        $user_id  = intval($user_id);
        
		$table_class_user = Database :: get_main_table(TABLE_MAIN_CLASS_USER);
		$table_course_class = Database :: get_main_table(TABLE_MAIN_COURSE_CLASS);
		$courses = ClassManager :: get_courses($class_id);
		if (count($courses) != 0) {
			$course_codes = array ();
			foreach ($courses as $index => $course) {
				$course_codes[] = $course['course_code'];
				$sql = "SELECT DISTINCT user_id FROM $table_class_user t1, $table_course_class t2 WHERE t1.class_id=t2.class_id AND course_code = '".$course['course_code']."' AND user_id = $user_id AND t2.class_id<>'$class_id'";
				$res = Database::query($sql);
				if (Database::num_rows($res) == 0 && CourseManager :: get_user_in_course_status($user_id, $course['course_code']) == STUDENT)
				{
					CourseManager :: unsubscribe_user($user_id, $course['course_code']);
				}
			}
		}
		$sql = "DELETE FROM $table_class_user WHERE user_id='".$user_id."' AND class_id = '".$class_id."'";
		Database::query($sql);
	}
	/**
	 * Get all courses in which a class is subscribed
	 * @param int $class_id
	 * @return array
	 */
	function get_courses($class_id) {
        $class_id = intval($class_id);       
		$table_class_course = Database :: get_main_table(TABLE_MAIN_COURSE_CLASS);
		$table_course = Database :: get_main_table(TABLE_MAIN_COURSE);
		$sql = "SELECT * FROM $table_class_course cc, $table_course c WHERE cc.class_id = '".$class_id."' AND cc.course_code = c.code";
		$res = Database::query($sql);
		$courses = array ();
		while ($course = Database::fetch_array($res, 'ASSOC')) {
			$courses[] = $course;
		}
		return $courses;
	}
	/**
	 * Subscribe all members of a class to a course
	 * @param  int $class_id The class id
	 * @param string $course_code The course code
	 */
	function subscribe_to_course($class_id, $course_code) {
		$tbl_course_class = Database :: get_main_table(TABLE_MAIN_COURSE_CLASS);
		$tbl_class_user = Database :: get_main_table(TABLE_MAIN_CLASS_USER);
		$tbl_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
		$sql = "INSERT IGNORE INTO $tbl_course_class SET course_code = '".Database::escape_string($course_code)."', class_id = '".Database::escape_string($class_id)."'";
		Database::query($sql);
		$sql = "SELECT user_id FROM $tbl_class_user WHERE class_id = '".Database::escape_string($class_id)."'";
		$res = Database::query($sql);
		while ($user = Database::fetch_object($res)) {
			CourseManager :: subscribe_user($user->user_id, $course_code);
		}
	}
	/**
	 * Unsubscribe a class from a course.
	 * Only students are unsubscribed. If a user is member of 2 classes which
	 * are both subscribed to the course, the user stays in the course.
	 * @param int $class_id The class id
	 * @param string $course_code The course code
	 */
	function unsubscribe_from_course($class_id, $course_code)
	{
		$tbl_course_class = Database :: get_main_table(TABLE_MAIN_COURSE_CLASS);
		$tbl_class_user = Database :: get_main_table(TABLE_MAIN_CLASS_USER);
		$sql = "SELECT cu.user_id,COUNT(cc.class_id) FROM $tbl_course_class cc, $tbl_class_user cu WHERE  cc.class_id = cu.class_id AND cc.course_code = '".Database::escape_string($course_code)."' GROUP BY cu.user_id HAVING COUNT(cc.class_id) = 1";
		$single_class_users = Database::query($sql);
		while ($single_class_user = Database::fetch_object($single_class_users))
		{
			$sql = "SELECT * FROM $tbl_class_user WHERE class_id = '".Database::escape_string($class_id)."' AND user_id = '".Database::escape_string($single_class_user->user_id)."'";
			$res = Database::query($sql);
			if (Database::num_rows($res) > 0)
			{
				if (CourseManager :: get_user_in_course_status($single_class_user->user_id, $course_code) == STUDENT)
				{
					CourseManager :: unsubscribe_user($single_class_user->user_id, $course_code);
				}
			}
		}
		$sql = "DELETE FROM $tbl_course_class WHERE course_code = '".Database::escape_string($course_code)."' AND class_id = '".Database::escape_string($class_id)."'";
		Database::query($sql);
	}

	/**
	 * Get the class-id
	 * @param string $name The class name
	 * @return int the ID of the class
	 */
	function get_class_id($name) {
        $name = Database::escape_string($name);
		$table_class = Database :: get_main_table(TABLE_MAIN_CLASS);
		$sql = "SELECT * FROM $table_class WHERE name='".$name."'";
		$res = Database::query($sql);
		$obj = Database::fetch_object($res);
		return $obj->id;
	}
	/**
	 * Get all classes subscribed in a course
	 * @param string $course_code
	 * @return array An array with all classes (keys: 'id','code','name')
	 */
	function get_classes_in_course($course_code) {
		$table_class = Database :: get_main_table(TABLE_MAIN_CLASS);
		$table_course_class = Database :: get_main_table(TABLE_MAIN_COURSE_CLASS);
		$sql = "SELECT cl.* FROM $table_class cl, $table_course_class cc WHERE cc.course_code = '".Database::escape_string($course_code)."' AND cc.class_id = cl.id";
		$res = Database::query($sql);
		$classes = array ();
		while ($class = Database::fetch_array($res, 'ASSOC')) {
			$classes[] = $class;
		}
		return $classes;
	}
}