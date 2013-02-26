<?php
/* For licensing terms, see /license.txt */
/**
 * Student report
 * @package chamilo.reporting
 */
/**
 * Code
 */
 // name of the language file that needs to be included
$language_file = array ('registration', 'index', 'tracking', 'admin');
$cidReset = true;

require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'export.lib.inc.php';

api_block_anonymous_users();

$export_csv = isset($_GET['export']) && $_GET['export'] == 'csv' ? true : false;
if ($export_csv) {
	ob_start();
}
$csv_content = array();

if (isset($_GET['id_coach']) && intval($_GET['id_coach']) != 0) {
	$nameTools = get_lang("CoachStudents");
	$sql = 'SELECT lastname, firstname FROM '.Database::get_main_table(TABLE_MAIN_USER).' WHERE user_id='.intval($_GET['id_coach']);
	$rs = Database::query($sql);
	$coach_name = api_get_person_name(Database::result($rs, 0, 1), Database::result($rs, 0, 0));
	$page_title = get_lang('Students').' - '.$coach_name;
} else {
	$nameTools = get_lang("Students");
	$page_title = get_lang('Students');
}

$this_section = SECTION_TRACKING;

$interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('MySpace'));

if (isset($_GET["user_id"]) && $_GET["user_id"] != "" && !isset($_GET["type"])) {
	$interbreadcrumb[] = array ("url" => "teachers.php", "name" => get_lang('Teachers'));
}

if (isset($_GET["user_id"]) && $_GET["user_id"]!="" && isset($_GET["type"]) && $_GET["type"] == "coach") {
 	$interbreadcrumb[] = array ("url" => "coaches.php", "name" => get_lang('Tutors'));
}

Display :: display_header($nameTools);
/*
  	FUNCTION
  */

function count_student_coached() {
	global $students;
	return count($students);
}

function sort_users($a, $b) {
	global $tracking_column;
	if ($a[$tracking_column] > $b[$tracking_column]) {
		return 1;
	} else {
		return -1;
	}
}

function rsort_users($a, $b) {
	global $tracking_column;
	if ($b[$tracking_column] > $a[$tracking_column]) {
		return 1;
	} else {
		return -1;
	}
}

/* MAIN CODE	 */

//if ($isCoach || api_is_platform_admin() || api_is_drh()) {
if (api_is_allowed_to_create_course() || api_is_drh()) {		
	if ($export_csv) {
		$is_western_name_order = api_is_western_name_order(PERSON_NAME_DATA_EXPORT);
	} else {
		$is_western_name_order = api_is_western_name_order();
	}
	$sort_by_first_name = api_sort_by_first_name();

	if (api_is_drh()) {
        $menu_items = array();
		$menu_items[] = Display::url(Display::return_icon('stats.png', get_lang('MyStats'),'',ICON_SIZE_MEDIUM),api_get_path(WEB_CODE_PATH)."auth/my_progress.php" );
		$menu_items[] = Display::return_icon('user_na.png', get_lang('Students'), array(), 32);
		$menu_items[] = Display::url(Display::return_icon('teacher.png', get_lang('Trainers'), array(), 32), 'teachers.php');
		$menu_items[] = Display::url(Display::return_icon('course.png', get_lang('Courses'), array(), 32), 'course.php');
		$menu_items[] = Display::url(Display::return_icon('session.png', get_lang('Sessions'), array(), 32), 'session.php');
		
		echo '<div class="actions">';
		$nb_menu_items = count($menu_items);
		if ($nb_menu_items > 1) {
			foreach ($menu_items as $key => $item) {
				echo $item;			
			}
		}
        
		//if (count($students) > 0) {		//
			echo '<span style="float:right">';
			echo Display::url(Display::return_icon('printer.png', get_lang('Print'), array(), 32), 'javascript: void(0);', array('onclick'=>'javascript: window.print();'));
			echo Display::url(Display::return_icon('export_csv.png', get_lang('ExportAsCSV'), array(), 32), api_get_self().'?export=csv');
			echo '</span>';	
		//}
		echo '</div>';		
	} else {		
		echo '<div class="actions"><div style="float:right;">
				<a href="javascript: void(0);" onclick="javascript: window.print();"><img align="absbottom" src="../img/printmgr.gif">&nbsp;'.get_lang('Print').'</a>
				<a href="'.api_get_self().'?export=csv"><img align="absbottom" src="../img/excel.gif">&nbsp;'.get_lang('ExportAsCSV').'</a>
			  </div></div>';        
	}
    
    echo Display::page_subheader($page_title);

	if (isset($_GET['id_coach'])) {
		$coach_id = intval($_GET['id_coach']);
	} else {
		$coach_id = api_get_user_id();
	}
	if (api_is_drh()) {
		$page_title = get_lang('YourStudents');
		if (!isset($_GET['id_session'])) {
		
			if (isset($_GET['user_id'])) {
				$user_id = intval($_GET['user_id']);
				$user_info = api_get_user_info($user_id);
				$page_title = api_get_person_name($user_info['firstname'], $user_info['lastname']).' : '.get_lang('Students');
				$courses_by_teacher  = CourseManager::get_course_list_of_user_as_course_admin($user_id);
				$students_by_course = array();
				if (!empty($courses_by_teacher)) {
					foreach ($courses_by_teacher as $course) {
						$students_by_course = array_keys(CourseManager::get_student_list_from_course_code($course['course_code']));
						if (count($students_by_course) > 0) {
							foreach ($students_by_course as $student_by_course) {
								$students[] = $student_by_course;
							}
						}
					}
				}
				if (!empty($students)) {
					$students = array_unique($students);
				}
			} else {
				$students = array_keys(UserManager::get_users_followed_by_drh(api_get_user_id() , STUDENT));
			}
		
			$courses_of_the_platform = CourseManager :: get_real_course_list();
			foreach ($courses_of_the_platform as $course) {
				$courses[$course['code']] = $course['code'];
			}
		}
	} else {
		if (!isset($_GET['id_session'])) {	
			//Getting courses
			$courses  = CourseManager::get_course_list_as_coach($coach_id, false);
			if (isset($courses[0])) {
				$courses = $courses[0];
			}			
			//Getting students			
			$students = CourseManager::get_user_list_from_courses_as_coach($coach_id);
					
		} else {
			$students = Tracking :: get_student_followed_by_coach_in_a_session($_GET['id_session'], $coach_id);
		}
	}

	$tracking_column 	= isset($_GET['tracking_column']) ? $_GET['tracking_column'] : ($is_western_name_order xor $sort_by_first_name) ? 1 : 0;
	$tracking_direction = isset($_GET['tracking_direction']) ? $_GET['tracking_direction'] : DESC;
	
	if (count($students) > 0) {
		$table = new SortableTable('tracking_student', 'count_student_coached', null, ($is_western_name_order xor $sort_by_first_name) ? 1 : 0);
		if ($is_western_name_order) {
			$table -> set_header(0, get_lang('FirstName'), false);
			$table -> set_header(1, get_lang('LastName'), false);
		} else {
			$table -> set_header(0, get_lang('LastName'), false);
			$table -> set_header(1, get_lang('FirstName'), false);
		}
	/*	$table -> set_header(2, get_lang('Time'), false);
		$table -> set_header(3, get_lang('Progress'), false);
		$table -> set_header(4, get_lang('Score'), false);
		$table -> set_header(5, get_lang('Student_publication'), false);
		$table -> set_header(6, get_lang('Messages'), false);*/
		$table -> set_header(2, get_lang('FirstLogin'), false);
		$table -> set_header(3, get_lang('LatestLogin'), false);
		$table -> set_header(4, get_lang('Details'), false);

		if ($export_csv) {
			if ($is_western_name_order) {
				$csv_header[] = array (
					get_lang('FirstName', ''),
					get_lang('LastName', ''),
					//get_lang('Time', ''),
					//get_lang('Progress', ''),
					//get_lang('Score', ''),
					//get_lang('Student_publication', ''),
					//get_lang('Messages', ''),
					get_lang('FirstLogin', ''),
					get_lang('LatestLogin', '')
				);
			} else {
				$csv_header[] = array (
					get_lang('LastName', ''),
					get_lang('FirstName', ''),
					//get_lang('Time', ''),
					//get_lang('Progress', ''),
					//get_lang('Score', ''),
					//get_lang('Student_publication', ''),
					//get_lang('Messages', ''),
					get_lang('FirstLogin', ''),
					get_lang('LatestLogin', '')
				);
			}
		}

	    $all_datas = array();	  
	      
		foreach ($students as $student_id) {
			$student_data = UserManager :: get_user_info_by_id($student_id);
			if (isset($_GET['id_session'])) {
				$courses = Tracking :: get_course_list_in_session_from_student($student_id, $_GET['id_session']);				
			}			

			$avg_time_spent = $avg_student_score = $avg_student_progress = $total_assignments = $total_messages = 0;
			$nb_courses_student = 0;
			foreach ($courses as $course_code) {
				if (CourseManager :: is_user_subscribed_in_course($student_id, $course_code, true)) {
					$avg_time_spent 	+= Tracking :: get_time_spent_on_the_course($student_id, $course_code, $_GET['id_session']);					
					$my_average 		 = Tracking :: get_avg_student_score($student_id, $course_code);
					if (is_numeric($my_average)) {
						$avg_student_score += $my_average;
					}
					$avg_student_progress += Tracking :: get_avg_student_progress($student_id, $course_code);
					$total_assignments += Tracking :: count_student_assignments($student_id, $course_code);
					$total_messages += Tracking :: count_student_messages($student_id, $course_code);
					$nb_courses_student++;
				}
			}

			if ($nb_courses_student > 0) {
				$avg_time_spent = $avg_time_spent / $nb_courses_student;
				$avg_student_score = $avg_student_score / $nb_courses_student;
				$avg_student_progress = $avg_student_progress / $nb_courses_student;
			} else {
				$avg_time_spent = null;
				$avg_student_score = null;
				$avg_student_progress = null;
			}

			$row = array();
			if ($is_western_name_order) {
				$row[] = $student_data['firstname'];
				$row[] = $student_data['lastname'];
			} else {
				$row[] = $student_data['lastname'];
				$row[] = $student_data['firstname'];
			}
			
			/*
			$row[] = api_time_to_hms($avg_time_spent);
			$row[] = is_null($avg_student_progress) ? null : round($avg_student_progress, 2).'%';
			$row[] = is_null($avg_student_score) ? null : round($avg_student_score, 2).'%';
			$row[] = $total_assignments;
			$row[] = $total_messages;
			*/

			$string_date = Tracking :: get_last_connection_date($student_id, true);
			$first_date = Tracking :: get_first_connection_date($student_id);
			$row[] = $first_date;
			$row[] = $string_date;

			if ($export_csv) {			    
			    $row[count($row) - 1] = strip_tags($row[count($row) - 1]);
                $row[count($row) - 2] = strip_tags($row[count($row) - 2]);
				$csv_content[] = $row;
			}

			if (isset($_GET['id_coach']) && intval($_GET['id_coach']) != 0) {
				$row[] = '<a href="myStudents.php?student='.$student_id.'&id_coach='.$coach_id.'&id_session='.$_GET['id_session'].'"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a>';
			} else {
				$row[] = '<a href="myStudents.php?student='.$student_id.'"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a>';
			}

			$all_datas[] = $row;
		}

		if ($tracking_direction == 'ASC') {
			usort($all_datas, 'rsort_users');
		} else {
			usort($all_datas, 'sort_users');
		}

		if ($export_csv) {
			usort($csv_content, 'sort_users');
			$csv_content = array_merge($csv_header, $csv_content);
		}

		foreach ($all_datas as $row) {
			$table -> addRow($row, 'align="right"');
		}
		$table -> display();
	} else {
		echo Display::display_warning_message(get_lang('NoStudent'));
	}

	// send the csv file if asked
	if ($export_csv) {
		ob_end_clean();
		Export :: export_table_csv($csv_content, 'reporting_student_list');
		exit;
	}
}

/*		FOOTER	*/
Display :: display_footer();
