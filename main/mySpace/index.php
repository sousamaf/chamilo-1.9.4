<?php
/* For licensing terms, see /license.txt */
/**
 * Homepage for the MySpace directory
 * @package chamilo.reporting
 */
/**
 * code
 */
$language_file = array('registration', 'index', 'tracking');

// resetting the course id
$cidReset = true;

require_once '../inc/global.inc.php';

// including additional libraries
require_once api_get_path(LIBRARY_PATH).'export.lib.inc.php';
require_once 'myspace.lib.php';

// the section (for the tabs)
$this_section = SECTION_TRACKING;
unset($_SESSION['this_section']);//for hmtl editor repository

ob_start();

$export_csv  = isset($_GET['export']) && $_GET['export'] == 'csv' ? true : false;
$display 	 = isset($_GET['display']) ? Security::remove_XSS($_GET['display']) : null;
$csv_content = array();
$nameTools   		= get_lang('MySpace');

$user_id 	 		= api_get_user_id();
$is_coach 			= api_is_coach($_GET['session_id']); // This is used?

$session_id = isset($_GET['session_id']) ? intval($_GET['session_id']) : 0;

$is_platform_admin 	= api_is_platform_admin();
$is_drh 			= api_is_drh();
$is_session_admin 	= api_is_session_admin();

$count_sessions 	= 0;
$count_courses		= 0;
$title 				= null;

// access control
api_block_anonymous_users();

if (!$export_csv) {
	Display :: display_header($nameTools);
} else {
	if ($_GET['view'] == 'admin') {
		if($display == 'useroverview') {
			MySpace::export_tracking_user_overview();
			exit;
		} else if($display == 'sessionoverview') {
			MySpace::export_tracking_session_overview();
			exit;
		} else if($display == 'courseoverview') {
			MySpace::export_tracking_course_overview();
			exit;
		}
	}
}

// Database table definitions
$tbl_user 					= Database :: get_main_table(TABLE_MAIN_USER);
$tbl_sessions 				= Database :: get_main_table(TABLE_MAIN_SESSION);
$tbl_session_course_user 	= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);


/* * FUNCTIONS */
function count_coaches() {
	global $total_no_coaches;
	return $total_no_coaches;
}

function sort_users($a, $b) {
	return api_strcmp(trim(api_strtolower($a[$_SESSION['tracking_column']])), trim(api_strtolower($b[$_SESSION['tracking_column']])));
}

function rsort_users($a, $b) {
	return api_strcmp(trim(api_strtolower($b[$_SESSION['tracking_column']])), trim(api_strtolower($a[$_SESSION['tracking_column']])));
}

function count_sessions_coached() {
	global $count_sessions;
	return $count_sessions;
}

function sort_sessions($a, $b) {
	global $tracking_column;
	if ($a[$tracking_column] > $b[$tracking_column]) {
		return 1;
	} else {
		return -1;
	}
}

function rsort_sessions($a, $b) {
	global $tracking_column;
	if ($b[$tracking_column] > $a[$tracking_column]) {
		return 1;
	} else {
		return -1;
	}
}

/* * MAIN CODE  */

if ($is_session_admin) {
	header('location:session.php');
	exit;
}

// Get views
$views = array('admin', 'teacher', 'coach', 'drh');
$view  = 'teacher';
if (isset($_GET['view']) && in_array($_GET['view'], $views)) {
	$view = $_GET['view'];
}

$menu_items = array();
global $_configuration;

//If is a teacher or admin
if (api_is_allowed_to_create_course() || api_is_drh()) {
}

if ($is_platform_admin) {	
	if ($view == 'admin') {
		$title = get_lang('CoachList');
		$menu_items[] = Display::url(Display::return_icon('teacher.png', get_lang('TeacherInterface'), array(), 32), api_get_self().'?view=teacher');
		$menu_items[] = Display::url(Display::return_icon('star_na.png', get_lang('AdminInterface'), array(), 32), api_get_self().'?view=admin');		
        $menu_items[] = Display::url(Display::return_icon('quiz.png', get_lang('ExamTracking'), array(), 32), api_get_path(WEB_CODE_PATH).'tracking/exams.php');        
        $menu_items[] = Display::url(Display::return_icon('statistics.png', get_lang('CurrentCoursesReport'), array(), 32), api_get_path(WEB_CODE_PATH).'mySpace/current_courses.php');        
	} else {		
		$menu_items[] = Display::return_icon('teacher_na.png', get_lang('TeacherInterface'), array(), 32);		
		$menu_items[] = Display::url(Display::return_icon('star.png', get_lang('AdminInterface'), array(), 32), api_get_self().'?view=admin');
        $menu_items[] = Display::url(Display::return_icon('quiz.png', get_lang('ExamTracking'), array(), 32), api_get_path(WEB_CODE_PATH).'tracking/exams.php');
        $menu_items[] = Display::url(Display::return_icon('statistics.png', get_lang('CurrentCoursesReport'), array(), 32), api_get_path(WEB_CODE_PATH).'mySpace/current_courses.php');
	}
}

if ($is_drh) {
	$view = 'drh';
	$menu_items[] = Display::return_icon('user_na.png', get_lang('Students'), array(), 32);
	$menu_items[] = Display::url(Display::return_icon('teacher.png', get_lang('Trainers'), array(), 32), 'teachers.php');
	$menu_items[] = Display::url(Display::return_icon('course.png', get_lang('Courses'), array(), 32), 'course.php');
	$menu_items[] = Display::url(Display::return_icon('session.png', get_lang('Sessions'), array(), 32), 'session.php');
}

echo '<div id="actions" class="actions">';

echo '<span style="float:right">';

if ($display == 'useroverview' || $display == 'sessionoverview' || $display == 'courseoverview') {    
	echo '<a href="'.api_get_self().'?display='.$display.'&export=csv&view='.$view.'">';
    echo Display::return_icon("export_csv.png", get_lang('ExportAsCSV'),array(), 32);
    echo '</a>';        
}
echo '<a href="javascript: void(0);" onclick="javascript: window.print()">'.Display::return_icon('printer.png', get_lang('Print'),'',ICON_SIZE_MEDIUM).'</a>';
echo '</span>';
    
if (!empty($session_id)) {
	echo '<a href="index.php">'.Display::return_icon('back.png', get_lang('Back'),'',ICON_SIZE_MEDIUM).'</a>';
} else {
	echo Display::url(Display::return_icon('stats.png', get_lang('MyStats'),'',ICON_SIZE_MEDIUM),api_get_path(WEB_CODE_PATH)."auth/my_progress.php" );
} 

// Actions menu
$nb_menu_items = count($menu_items);
if (empty($session_id)) {
	if ($nb_menu_items > 1) {
    	foreach ($menu_items as $key => $item) {
	    	echo $item;    			
		}
	}
}		
echo '</div>';

if (empty($session_id)) {
	
	//Getting courses followed by a coach (No session courses)
	$courses  = CourseManager::get_course_list_as_coach($user_id, false);	
	
	if (isset($courses[0])) {
		$courses = $courses[0]; 
	}
	
	//Getting students from courses and courses in sessions (To show the total students that the user follows)
	$students 		= CourseManager::get_user_list_from_courses_as_coach($user_id);
    		
	// Sessions for the coach
	$sessions 	 	= Tracking::get_sessions_coached_by_user($user_id);
	
	//If is drh	
	if ($is_drh) {
		$students = array_keys(UserManager::get_users_followed_by_drh($user_id, STUDENT));
		$courses_of_the_platform = CourseManager :: get_courses_followed_by_drh($user_id);                
                
		foreach ($courses_of_the_platform as $course) {
			$courses[$course['code']] = $course['code'];
		}        
        $sessions 	 	= SessionManager::get_sessions_followed_by_drh($user_id);  
	}		
	
	//Courses for the user
	$count_courses = count($courses);
	
	//Sessions for the user
	$count_sessions = count($sessions);
		
	//Students	
	$nb_students = count($students);	
	
	$total_time_spent 			= 0;
	$total_courses 				= 0;
	$avg_total_progress 		= 0;
	$avg_results_to_exercises 	= 0;
	$nb_inactive_students 		= 0;
	$nb_posts = $nb_assignments = 0;
	    
	if (!empty($students))
	foreach ($students as $student_id) {
		// inactive students
		$last_connection_date = Tracking :: get_last_connection_date($student_id, true, true);
		if ($last_connection_date !== false) {
			if (time() - (3600 * 24 * 7) > $last_connection_date) {
				$nb_inactive_students++;
			}
		} else {
			$nb_inactive_students++;
		}
	
		$total_time_spent += Tracking :: get_time_spent_on_the_platform($student_id);
		$total_courses += Tracking :: count_course_per_student($student_id);
		$avg_student_progress   = 0;
        $avg_student_score      = 0;
		$nb_courses_student     = 0;
		foreach ($courses as $course_code) {
			if (CourseManager :: is_user_subscribed_in_course($student_id, $course_code, true)) {
				$nb_courses_student++;
				$nb_posts 			   += Tracking :: count_student_messages($student_id, $course_code);
				$nb_assignments 	   += Tracking :: count_student_assignments($student_id, $course_code);
				$avg_student_progress  += Tracking :: get_avg_student_progress($student_id, $course_code);
				$myavg_temp 			= Tracking :: get_avg_student_score($student_id, $course_code);
	
				 if (is_numeric($myavg_temp))
				 	$avg_student_score += $myavg_temp;
	
				if ($nb_posts !== null && $nb_assignments !== null && $avg_student_progress !== null && $avg_student_score !== null) {
					//if one of these scores is null, it means that we had a problem connecting to the right database, so don't count it in
					$nb_courses_student++;
				}
			}
		}
		// average progress of the student
		$avg_student_progress = $nb_courses_student ?$avg_student_progress / $nb_courses_student:0;
		$avg_total_progress += $avg_student_progress;
	
		// average test results of the student
		$avg_student_score = $avg_student_score?$avg_student_score / $nb_courses_student:0;
		$avg_results_to_exercises += $avg_student_score;
	}

	if ($nb_students > 0 && $view != 'admin') {
		
		// average progress
		$avg_total_progress = $avg_total_progress / $nb_students;
		// average results to the tests
		$avg_results_to_exercises = $avg_results_to_exercises / $nb_students;
		// average courses by student
		$avg_courses_per_student = round($count_courses / $nb_students, 2);
		// average time spent on the platform
		$avg_time_spent = $total_time_spent / $nb_students;
		// average assignments
		$nb_assignments = $nb_assignments / $nb_students;
		// average posts
		$nb_posts = $nb_posts / $nb_students;       
        
        echo Display::page_subheader('<img src="'.api_get_path(WEB_IMG_PATH).'teachers.gif">&nbsp;'.get_lang('Overview'));
        
        echo '<div class="report_section">
					<table class="table table-bordered">
                        <tr>
							<td>'.get_lang('FollowedUsers').'</td>
							<td align="right">'.$nb_students.'</td>
						</tr>
                        <tr>
							<td>'.get_lang('FollowedCourses').'</td>
							<td align="right">'.$count_courses.'</td>
						</tr>
                        <tr>
							<td>'.get_lang('FollowedSessions').'</td>
							<td align="right">'.$count_sessions.'</td>
						</tr>
                        </table>';
        echo '</div>';
                        
		echo Display::page_subheader('<img src="'.api_get_path(WEB_IMG_PATH).'students.gif">&nbsp;'.get_lang('Students').' ('.$nb_students.')');
        
		if ($export_csv) {
			//csv part
			$csv_content[] = array(get_lang('Students', ''));
			$csv_content[] = array(get_lang('InactivesStudents', ''), $nb_inactive_students );
			$csv_content[] = array(get_lang('AverageTimeSpentOnThePlatform', ''), $avg_time_spent);
			$csv_content[] = array(get_lang('AverageCoursePerStudent', ''), $avg_courses_per_student);
			$csv_content[] = array(get_lang('AverageProgressInLearnpath', ''), is_null($avg_total_progress) ? null : round($avg_total_progress, 2).'%');
			$csv_content[] = array(get_lang('AverageResultsToTheExercices', ''), is_null($avg_results_to_exercises) ? null : round($avg_results_to_exercises, 2).'%');
			$csv_content[] = array(get_lang('AveragePostsInForum', ''), $nb_posts);
			$csv_content[] = array(get_lang('AverageAssignments', ''), $nb_assignments);
			$csv_content[] = array();
		} else {
			// html part
			echo '<div class="report_section">
					<table class="table table-bordered">     
                        <tr>
							<td>'.get_lang('AverageCoursePerStudent').'</td>
							<td align="right">'.(is_null($avg_courses_per_student) ? '' : $avg_courses_per_student).'</td>
						</tr>                        
                        <tr>
							<td>'.get_lang('InactivesStudents').'</td>
							<td align="right">'.$nb_inactive_students.'</td>
						</tr>
                        
						<tr>
							<td>'.get_lang('AverageTimeSpentOnThePlatform').'</td>
							<td align="right">'.(is_null($avg_time_spent) ? '' : api_time_to_hms($avg_time_spent)).'</td>
						</tr>
						
						<tr>
							<td>'.get_lang('AverageProgressInLearnpath').'</td>
							<td align="right">'.(is_null($avg_total_progress) ? '' : round($avg_total_progress, 2).'%').'</td>
						</tr>
						<tr>
							<td>'.get_lang('AvgCourseScore').'</td>
							<td align="right">'.(is_null($avg_results_to_exercises) ? '' : round($avg_results_to_exercises, 2).'%').'</td>
						</tr>
						<tr>
							<td>'.get_lang('AveragePostsInForum').'</td>
							<td align="right">'.(is_null($nb_posts) ? '' : round($nb_posts, 2)).'</td>
						</tr>
						<tr>
							<td>'.get_lang('AverageAssignments').'</td>
							<td align="right">'.(is_null($nb_assignments) ? '' : round($nb_assignments, 2)).'</td>
						</tr>
					</table>
					<a href="student.php">'.get_lang('SeeStudentList').'</a>
				 </div><br />';
		}		
	} else {
		$avg_total_progress = null;
		$avg_results_to_exercises = null;
		$avg_courses_per_student = null;
		$avg_time_spent = null;
		$nb_assignments = null;
		$nb_posts = null;
	}
} else {
	$courses = Tracking::get_courses_followed_by_coach($user_id, $session_id);
    	   
    //If is drh	
	if ($is_drh) {
        $courses_of_the_platform = CourseManager :: get_courses_followed_by_drh($user_id);    
                        
        $courses = array();
        $courses_from_session = SessionManager::get_course_list_by_session_id($session_id);
        
        foreach ($courses_from_session as $course_item) {            
            if (isset($courses_of_the_platform[$course_item['code']])) {            
                $courses[$course_item['code']] = $course_item['code'];
            }
        }  
        
        if (empty($courses)) {
            Display::display_warning_message(get_lang('NoResults'));
        }
	}
    
    //Courses for the user
    $count_courses = count($courses);
    
    //Sessions for the user
	$count_sessions = count($sessions);
    
    
        
}

if ($count_courses || $count_sessions) {
	//If we are in course
	if (empty($session_id)) {
		if ($count_courses) {
			$title = '<img src="'.api_get_path(WEB_IMG_PATH).'course.gif"> '.get_lang('Courses').' ('.$count_courses.') ';
		}
	} else {
		//If we are in Course Session
		$session_name = api_get_session_name($session_id);
		$title = Display::return_icon('session.png', get_lang('Session'), array(), ICON_SIZE_SMALL).' '.$session_name;
		$menu_items[] = '<a href="'.api_get_self().'?view=teacher">'.get_lang('TeacherInterface').'</a>';
	}
}

if ((api_is_allowed_to_create_course() || api_is_drh()) && in_array($view, array('teacher', 'drh'))) {
	
	//Courses
	if ($count_courses) {
		
		echo Display::page_subheader($title);		
		
		$table = new SortableTable('courses_my_space', 'get_number_of_courses', array('MySpace','get_course_data'));
		$parameters['view'] = 'teacher';
		$parameters['class'] = 'data_table';
		$table->set_additional_parameters($parameters);
		$table -> set_header(0, get_lang('CourseTitle'), false);
		$table -> set_header(1, get_lang('NbStudents'), false);
		$table -> set_header(2, get_lang('AvgTimeSpentInTheCourse').' '.Display :: return_icon('info3.gif', get_lang('TimeOfActiveByTraining'), array('align' => 'absmiddle', 'hspace' => '3px')), false);
		$table -> set_header(3, get_lang('AvgStudentsProgress').' '.Display :: return_icon('info3.gif', get_lang('AvgAllUsersInAllCourses'), array('align' => 'absmiddle', 'hspace' => '3px')), false);
		$table -> set_header(4, get_lang('AvgCourseScore').' '.Display :: return_icon('info3.gif', get_lang('AvgAllUsersInAllCourses'), array('align' => 'absmiddle', 'hspace' => '3px')), false);
		$table -> set_header(5, get_lang('AvgExercisesScore').' '.Display :: return_icon('info3.gif', get_lang('AvgAllUsersInAllCourses'), array('align' => 'absmiddle', 'hspace' => '3px')), false);
		$table -> set_header(6, get_lang('AvgMessages'), false);
		$table -> set_header(7, get_lang('AverageAssignments'), false);
		$table -> set_header(8, get_lang('Details'), false);

		$csv_content[] = array (
			get_lang('CourseTitle', ''),
			get_lang('NbStudents', ''),
			get_lang('AvgTimeSpentInTheCourse', ''),
			get_lang('AvgStudentsProgress', ''),
			get_lang('AvgCourseScore', ''),
			get_lang('AvgExercisesScore', ''),
			get_lang('AvgMessages', ''),
			get_lang('AverageAssignments', '')
		);
		$table->display();
	}

	// Display list of sessions
	if ($count_sessions > 0 && !isset($_GET['session_id'])) {
        
		echo Display::page_subheader('<img src="'.api_get_path(WEB_IMG_PATH).'session.png">&nbsp;'.get_lang('Sessions').' ('.$count_sessions.')');
        
		$table = new SortableTable('tracking_sessions_myspace', 'count_sessions_coached');
		$table->set_header(0, get_lang('Title'), false);
        $table->set_header(1, get_lang('Date'), false);		
        $table->set_header(2, get_lang('NbStudentPerSession'), false);
		$table->set_header(3, get_lang('NbCoursesPerSession'), false);
		$table->set_header(4, get_lang('Details'), false);

		$all_data = array();
		foreach ($sessions as $session) {			
			$count_courses_in_session = count(Tracking::get_courses_followed_by_coach($user_id, $session['id']));
            $count_users_in_session = count(SessionManager::get_users_by_session($session['id'], 0));
			$row = array();
			$row[] = $session['name'];

            $session_date = array();
			if (!empty($session['date_start']) && $session['date_start'] != '0000-00-00') {
                $session_date[] = get_lang('From').' '.api_format_date($session['date_start'], DATE_FORMAT_SHORT);
            }
            
            if (!empty($session['date_end']) && $session['date_end'] != '0000-00-00') {
                $session_date[] = get_lang('Until').' '.api_format_date($session['date_end'], DATE_FORMAT_SHORT);
            }
            
            if (empty($session_date)) {
                $session_date_string = '-';
            } else {
                $session_date_string = implode(' ', $session_date);
            }
            
            $row[] = $session_date_string;            
            $row[] = $count_courses_in_session;
            $row[] = $count_users_in_session;
			
			$row[] = '<a href="'.api_get_self().'?session_id='.$session['id'].'"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a>';
			$all_data[] = $row;
		}

		if (!isset($tracking_column)) {
			$tracking_column = 0;
		}

		if (isset($_GET['tracking_direction']) &&  $_GET['tracking_direction'] == 'DESC') {
			usort($all_data, 'rsort_sessions');
		} else {
			usort($all_data, 'sort_sessions');
		}

		if ($export_csv) {
			usort($csv_content, 'sort_sessions');
		}

		foreach ($all_data as $row) {
			$table -> addRow($row);
		}

		/*  Start session over view stats */
		
		$nb_sessions_past = $nb_sessions_current = 0;
		$courses = array();
        
		foreach ($sessions as $session) {            
            $visibility = api_get_session_visibility($session['id']);            
            if ($visibility == SESSION_AVAILABLE) {
                $nb_sessions_current ++;
            } else {
                $nb_sessions_past++;
            } 
			$courses = array_merge($courses, Tracking::get_courses_list_from_session($session['id']));
		}
		
        $nb_courses_per_session     = null; 
        $nb_students_per_session    = null;
            
		if ($count_sessions > 0) {
			$nb_courses_per_session = round(count($courses) / $count_sessions, 2);
			$nb_students_per_session = round($nb_students / $count_sessions, 2);
		}
        
		if ($export_csv) {
			//csv part
			$csv_content[] = array(get_lang('Sessions', ''));
			$csv_content[] = array(get_lang('NbActiveSessions', '').';'.$nb_sessions_current);
			$csv_content[] = array(get_lang('NbInactiveSessions', '').';'.$nb_sessions_past);
			//$csv_content[] = array(get_lang('NbFutureSessions', '').';'.$nb_sessions_future);
            $csv_content[] = array(get_lang('NbCoursesPerSession', '').';'.$nb_courses_per_session);
			$csv_content[] = array(get_lang('NbStudentPerSession', '').';'.$nb_students_per_session);			
			$csv_content[] = array();
		} else {
			// html part
            
            			/*<tr>
						<td>'.get_lang('NbFutureSessions').'</td>
						<td align="right">'.$nb_sessions_future.'</td>
					</tr>*/
			echo '
			<div class="report_section">				
				<table class="table table-bordered">
					<tr>
						<td>'.get_lang('NbActiveSessions').'</td>
						<td align="right">'.$nb_sessions_current.'</td>
					</tr>
					<tr>
						<td>'.get_lang('NbInactiveSessions').'</td>
						<td align="right">'.$nb_sessions_past.'</td>
					</tr>		
				</table>				
			</div>';
		}
		/*  End session overview */				 
		$table -> display();
	}
}


if ($is_platform_admin && $view == 'admin' && $display != 'yourstudents') {
    
	echo '<a href="'.api_get_self().'?view=admin&amp;display=coaches">'.get_lang('DisplayCoaches').'</a> | ';
	echo '<a href="'.api_get_self().'?view=admin&amp;display=useroverview">'.get_lang('DisplayUserOverview').'</a>';
	if ($display == 'useroverview') {
		echo ' ( <a href="'.api_get_self().'?view=admin&amp;display=useroverview&amp;export=options">'.get_lang('ExportUserOverviewOptions').'</a> )';
	}
	echo ' | <a href="'.api_get_self().'?view=admin&amp;display=sessionoverview">'.get_lang('DisplaySessionOverview').'</a>';
	echo ' | <a href="'.api_get_self().'?view=admin&amp;display=courseoverview">'.get_lang('DisplayCourseOverview').'</a>';    
    echo ' | <a href="'.api_get_path(WEB_CODE_PATH).'tracking/question_course_report.php?view=admin">'.get_lang('LPQuestionListResults').'</a>';    
    echo ' | <a href="'.api_get_path(WEB_CODE_PATH).'tracking/course_session_report.php?view=admin">'.get_lang('LPExerciseResultsBySession').'</a>';
    
    
	echo '<br /><br />';
	if ($display === 'useroverview') {
		MySpace::display_tracking_user_overview();
	} else if($display == 'sessionoverview') {
		MySpace::display_tracking_session_overview();
	} else if($display == 'courseoverview') {
		MySpace::display_tracking_course_overview();
	} else {
		if ($export_csv) {
			$is_western_name_order = api_is_western_name_order(PERSON_NAME_DATA_EXPORT);
		} else {
			$is_western_name_order = api_is_western_name_order();
		}
		$sort_by_first_name = api_sort_by_first_name();
		$tracking_column = isset($_GET['tracking_list_coaches_column']) ? $_GET['tracking_list_coaches_column'] : ($is_western_name_order xor $sort_by_first_name) ? 1 : 0;
		$tracking_direction = (isset($_GET['tracking_list_coaches_direction']) && in_array(strtoupper($_GET['tracking_list_coaches_direction']), array('ASC', 'DESC', 'ASCENDING', 'DESCENDING', '0', '1'))) ? $_GET['tracking_list_coaches_direction'] : 'DESC';
		// Prepare array for column order - when impossible, use some of user names.
		if ($is_western_name_order) {
			$order = array(0 => 'firstname', 1 => 'lastname', 2 => ($sort_by_first_name ? 'firstname' : 'lastname'), 3 => 'login_date', 4 => ($sort_by_first_name ? 'firstname' : 'lastname'), 5 => ($sort_by_first_name ? 'firstname' : 'lastname'));
		} else {
			$order = array(0 => 'lastname', 1 => 'firstname', 2 => ($sort_by_first_name ? 'firstname' : 'lastname'), 3 => 'login_date', 4 => ($sort_by_first_name ? 'firstname' : 'lastname'), 5 => ($sort_by_first_name ? 'firstname' : 'lastname'));
		}
		$table = new SortableTable('tracking_list_coaches_myspace', 'count_coaches', null, ($is_western_name_order xor $sort_by_first_name) ? 1 : 0);
		$parameters['view'] = 'admin';
		$table->set_additional_parameters($parameters);
		if ($is_western_name_order) {
			$table -> set_header(0, get_lang('FirstName'), true);
			$table -> set_header(1, get_lang('LastName'), true);
		} else {
			$table -> set_header(0, get_lang('LastName'), true);
			$table -> set_header(1, get_lang('FirstName'), true);
		}
		$table -> set_header(2, get_lang('TimeSpentOnThePlatform'), false);
		$table -> set_header(3, get_lang('LastConnexion'), false);
		$table -> set_header(4, get_lang('NbStudents'), false);
		$table -> set_header(5, get_lang('CountCours'), false);
		$table -> set_header(6, get_lang('NumberOfSessions'), false);
		$table -> set_header(7, get_lang('Sessions'), false);

		if ($is_western_name_order) {
			$csv_header[] = array (
				get_lang('FirstName', ''),
				get_lang('LastName', ''),
				get_lang('TimeSpentOnThePlatform', ''),
				get_lang('LastConnexion', ''),
				get_lang('NbStudents', ''),
				get_lang('CountCours', ''),
				get_lang('NumberOfSessions', '')
			);
		} else {
			$csv_header[] = array (
				get_lang('LastName', ''),
				get_lang('FirstName', ''),
				get_lang('TimeSpentOnThePlatform', ''),
				get_lang('LastConnexion', ''),
				get_lang('NbStudents', ''),
				get_lang('CountCours', ''),
				get_lang('NumberOfSessions', '')
			);
		}

		$tbl_track_login = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);

		$sqlCoachs = "SELECT DISTINCT scu.id_user as id_coach, user_id, lastname, firstname, MAX(login_date) as login_date
			FROM $tbl_user, $tbl_session_course_user scu, $tbl_track_login
			WHERE scu.id_user=user_id AND scu.status=2  AND login_user_id=user_id
			GROUP BY user_id ";

		if ($_configuration['multiple_access_urls']) {
			$tbl_session_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
			$access_url_id = api_get_current_access_url_id();
			if ($access_url_id != -1) {
				$sqlCoachs = "SELECT DISTINCT scu.id_user as id_coach, user_id, lastname, firstname, MAX(login_date) as login_date
					FROM $tbl_user, $tbl_session_course_user scu, $tbl_track_login , $tbl_session_rel_access_url session_rel_url
					WHERE scu.id_user=user_id AND scu.status=2 AND login_user_id=user_id AND access_url_id = $access_url_id AND session_rel_url.session_id=id_session
					GROUP BY user_id ";
			}
		}
		if (!empty($order[$tracking_column])) {
			$sqlCoachs .= "ORDER BY ".$order[$tracking_column]." ".$tracking_direction;
		}

		$result_coaches = Database::query($sqlCoachs);
		$total_no_coaches = Database::num_rows($result_coaches);
		$global_coaches = array();
		while ($coach = Database::fetch_array($result_coaches)) {
			$global_coaches[$coach['user_id']] = $coach;
		}

		$sql_session_coach = 'SELECT session.id_coach, user_id, lastname, firstname, MAX(login_date) as login_date
			FROM '.$tbl_user.','.$tbl_sessions.' as session,'.$tbl_track_login.'
			WHERE id_coach=user_id AND login_user_id=user_id
			GROUP BY user_id
			ORDER BY login_date '.$tracking_direction;

		if ($_configuration['multiple_access_urls']) {
			$tbl_session_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
			$access_url_id = api_get_current_access_url_id();
			if ($access_url_id != -1) {
				$sql_session_coach = 'SELECT session.id_coach, user_id, lastname, firstname, MAX(login_date) as login_date
					FROM '.$tbl_user.','.$tbl_sessions.' as session,'.$tbl_track_login.' , '.$tbl_session_rel_access_url.' as session_rel_url
					WHERE id_coach=user_id AND login_user_id=user_id  AND access_url_id = '.$access_url_id.' AND  session_rel_url.session_id=session.id
					GROUP BY user_id
					ORDER BY login_date '.$tracking_direction;
			}
		}

		$result_sessions_coach = Database::query($sql_session_coach);
		$total_no_coaches += Database::num_rows($result_sessions_coach);
		while ($coach = Database::fetch_array($result_sessions_coach)) {
			$global_coaches[$coach['user_id']] = $coach;
		}

		$all_datas = array();

		foreach ($global_coaches as $id_coach => $coaches) {

			$time_on_platform   = api_time_to_hms(Tracking :: get_time_spent_on_the_platform($coaches['user_id']));
			$last_connection    = Tracking :: get_last_connection_date($coaches['user_id']);
			$nb_students        = count(Tracking :: get_student_followed_by_coach($coaches['user_id']));
			$nb_courses         = count(Tracking :: get_courses_followed_by_coach($coaches['user_id']));
			$nb_sessions        = count(Tracking :: get_sessions_coached_by_user($coaches['user_id']));

			$table_row = array();
			if ($is_western_name_order) {
				$table_row[] = $coaches['firstname'];
				$table_row[] = $coaches['lastname'];
			} else {
				$table_row[] = $coaches['lastname'];
				$table_row[] = $coaches['firstname'];
			}
			$table_row[] = $time_on_platform;
			$table_row[] = $last_connection;
			$table_row[] = $nb_students;
			$table_row[] = $nb_courses;
			$table_row[] = $nb_sessions;
			$table_row[] = '<a href="session.php?id_coach='.$coaches['user_id'].'"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a>';
			$all_datas[] = $table_row;

			if ($is_western_name_order) {
				$csv_content[] = array(
					api_html_entity_decode($coaches['firstname'], ENT_QUOTES, $charset),
					api_html_entity_decode($coaches['lastname'], ENT_QUOTES, $charset),
					$time_on_platform,
					$last_connection,
					$nb_students,
					$nb_courses,
					$nb_sessions
				);
			} else {
				$csv_content[] = array(
					api_html_entity_decode($coaches['lastname'], ENT_QUOTES, $charset),
					api_html_entity_decode($coaches['firstname'], ENT_QUOTES, $charset),
					$time_on_platform,
					$last_connection,
					$nb_students,
					$nb_courses,
					$nb_sessions
				);
			}
		}

		if ($tracking_column != 3) {
			if ($tracking_direction == 'DESC') {
				usort($all_datas, 'rsort_users');
			} else {
				usort($all_datas, 'sort_users');
			}
		}

		if ($export_csv && $tracking_column != 3) {
			usort($csv_content, 'sort_users');
		}
		if ($export_csv) {
			$csv_content = array_merge($csv_header, $csv_content);
		}

		foreach ($all_datas as $row) {
			$table -> addRow($row, 'align="right"');
		}
/*
		$table -> updateColAttributes(0, array('align' => 'left'));
		$table -> updateColAttributes(1, array('align' => 'left'));
		$table -> updateColAttributes(3, array('align' => 'left'));
		$table -> updateColAttributes(7, array('align' => 'center'));*/
		$table -> display();
	}
}

// send the csv file if asked
if ($export_csv) {
	ob_end_clean();
	Export :: export_table_csv($csv_content, 'reporting_index');
	exit;
}

//footer
if (!$export_csv) {
	Display::display_footer();
}
/**
 * Get number of courses for sortable with pagination
 * @return int
 */
function get_number_of_courses() {
	global $courses;
	return count($courses);
}
