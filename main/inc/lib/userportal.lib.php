<?php
/* For licensing terms, see /license.txt */

use \ChamiloSession as Session;

class IndexManager {
	var $tpl 	= false; //An instance of the template engine
	var $name 	= '';

	var $home			= '';
	var $default_home 	= 'home/';

	function __construct($title) {
		$this->tpl = new Template($title);
		$this->home     = api_get_home_path();
		$this->user_id  = api_get_user_id();
		$this->load_directories_preview = false;

		if (api_get_setting('show_documents_preview') == 'true') {
			$this->load_directories_preview = true;
		}
	}

	function set_login_form() {
		global $loginFailed;

		$login_form = '';

		if (!($this->user_id) || api_is_anonymous($this->user_id)) {

			// Only display if the user isn't logged in.
			$this->tpl->assign('login_language_form', api_display_language_form(true));
			$this->tpl->assign('login_form',  self::display_login_form());

			if ($loginFailed) {
				$this->tpl->assign('login_failed',  self::handle_login_failed());
			}

			if (api_get_setting('allow_lostpassword') == 'true' || api_get_setting('allow_registration') == 'true') {
				$login_form .= '<ul class="nav nav-list">';
				if (api_get_setting('allow_registration') != 'false') {
					$login_form .= '<li><a href="main/auth/inscription.php">'.get_lang('Reg').'</a></li>';
				}
				if (api_get_setting('allow_lostpassword') == 'true') {
					$login_form .= '<li><a href="main/auth/lostPassword.php">'.get_lang('LostPassword').'</a></li>';
				}
				$login_form .= '</ul>';
			}
			$this->tpl->assign('login_options',  $login_form);
		}
	}


	function return_exercise_block($personal_course_list) {
		require_once api_get_path(SYS_CODE_PATH).'exercice/exercise.lib.php';
		$exercise_list = array();
		if (!empty($personal_course_list)) {
			foreach($personal_course_list as  $course_item) {
				$course_code 	= $course_item['c'];
				$session_id 	= $course_item['id_session'];

				$exercises = get_exercises_to_be_taken($course_code, $session_id);

				foreach($exercises as $exercise_item) {
					$exercise_item['course_code'] 	= $course_code;
					$exercise_item['session_id'] 	= $session_id;
					$exercise_item['tms'] 	= api_strtotime($exercise_item['end_time'], 'UTC');

					$exercise_list[] = $exercise_item;
				}
			}
			if (!empty($exercise_list)) {
				$exercise_list = msort($exercise_list, 'tms');
				$my_exercise = $exercise_list[0];
				$url = Display::url($my_exercise['title'], api_get_path(WEB_CODE_PATH).'exercice/overview.php?exerciseId='.$my_exercise['id'].'&cidReq='.$my_exercise['course_code'].'&id_session='.$my_exercise['session_id']);
				$this->tpl->assign('exercise_url', $url);
				$this->tpl->assign('exercise_end_date', api_convert_and_format_date($my_exercise['end_time'], DATE_FORMAT_SHORT));
			}
		}
	}

	function return_announcements($show_slide = true) {
		// Display System announcements
		$announcement = isset($_GET['announcement']) ? $_GET['announcement'] : null;
		$announcement = intval($announcement);

		if (!api_is_anonymous() && $this->user_id) {
			$visibility = api_is_allowed_to_create_course() ? SystemAnnouncementManager::VISIBLE_TEACHER : SystemAnnouncementManager::VISIBLE_STUDENT;
			if ($show_slide) {
				$announcements = SystemAnnouncementManager :: display_announcements_slider($visibility, $announcement);
			} else {
				$announcements = SystemAnnouncementManager :: display_all_announcements($visibility, $announcement);
			}
		} else {
			if ($show_slide) {
				$announcements = SystemAnnouncementManager :: display_announcements_slider(SystemAnnouncementManager::VISIBLE_GUEST, $announcement);
			} else {
				$announcements = SystemAnnouncementManager :: display_all_announcements(SystemAnnouncementManager::VISIBLE_GUEST, $announcement);
			}
		}
		return $announcements;
	}

	/**
     * Alias for the online_logout() function
	 */
	function logout() {
        online_logout($this->user_id, true);
	}

	/**
	 * This function checks if there are courses that are open to the world in the platform course categories (=faculties)
	 *
	 * @param string $category
	 * @return boolean
	 */
	function category_has_open_courses($category) {
		$setting_show_also_closed_courses = api_get_setting('show_closed_courses') == 'true';
		$main_course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
        $category = Database::escape_string($category);
		$sql_query = "SELECT * FROM $main_course_table WHERE category_code='$category'";
		$sql_result = Database::query($sql_query);
		while ($course = Database::fetch_array($sql_result)) {
			if (!$setting_show_also_closed_courses) {
				if ((api_get_user_id() > 0 && $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM) || ($course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD)) {
					return true; //at least one open course
				}
			} else {
				if (isset($course['visibility'])) {
					return true; // At least one course (it does not matter weither it's open or not because $setting_show_also_closed_courses = true).
				}
			}
		}
		return false;
	}


	/**
	 * Displays the right-hand menu for anonymous users:
	 * login form, useful links, help section
	 * Warning: function defines globals
	 * @version 1.0.1
	 * @todo does $_plugins need to be global?
	 */
	function display_anonymous_right_menu() {
		global $loginFailed, $_user;
		$display_add_course_link	= api_is_allowed_to_create_course() && ($_SESSION['studentview'] != 'studentenview');
		$current_user_id        	= api_get_user_id();

		echo self::set_login_form(false);
		echo self::return_teacher_link();
		echo self::return_notice();
	}

	function return_teacher_link() {
		$html = '';
		if (!empty($this->user_id)) {
			// tabs that are deactivated are added here

			$show_menu = false;
			$show_create_link = false;
			$show_course_link = false;

			if (api_is_platform_admin() || api_is_course_admin() || api_is_allowed_to_create_course()) {
				$show_menu = true;
				$show_course_link = true;
			} else {
				if (api_get_setting('allow_students_to_browse_courses') == 'true') {
					$show_menu = true;
					$show_course_link = true;
				}
			}

			if ($show_menu && ($show_create_link || $show_course_link )) {
				$show_menu = true;
			} else {
				$show_menu = false;
			}
		}

		// My Account section

		if ($show_menu) {
			$html .= '<ul class="nav nav-list">';
			if ($show_create_link) {
				$html .= '<li><a href="main/create_course/add_course.php" class="add course">'.(api_get_setting('course_validation') == 'true' ? get_lang('CreateCourseRequest') : get_lang('CourseCreate')).'</a></li>';
			}

			if ($show_course_link) {
				if (!api_is_drh() && !api_is_session_admin()) {
					$html .=  '<li><a href="main/auth/courses.php" class="list course">'.get_lang('CourseCatalog').'</a></li>';
				} else {
					$html .= '<li><a href="main/dashboard/index.php">'.get_lang('Dashboard').'</a></li>';
				}
			}
			$html .= '</ul>';
		}

		if (!empty($html)) {
			$html = self::show_right_block(get_lang('Courses'), $html, 'teacher_block');
		}
		return $html;
	}

	/* Includes a created page */
	function return_home_page() {

		// Including the page for the news
		$html = '';

		if (!empty($_GET['include']) && preg_match('/^[a-zA-Z0-9_-]*\.html$/', $_GET['include'])) {
			$open = @(string)file_get_contents(api_get_path(SYS_PATH).$this->home.$_GET['include']);
			$html = api_to_system_encoding($open, api_detect_encoding(strip_tags($open)));
		} else {
			if (!empty($_SESSION['user_language_choice'])) {
				$user_selected_language = $_SESSION['user_language_choice'];
			} elseif (!empty($_SESSION['_user']['language'])) {
				$user_selected_language = $_SESSION['_user']['language'];
			} else {
				$user_selected_language = api_get_setting('platformLanguage');
			}
			if (!file_exists($this->home.'home_news_'.$user_selected_language.'.html')) {
				if (file_exists($this->home.'home_top.html')) {
					$home_top_temp = file($this->home.'home_top.html');
				} else {
					$home_top_temp = file($this->default_home.'home_top.html');
				}
				$home_top_temp = implode('', $home_top_temp);
			} else {
				if (file_exists($this->home.'home_top_'.$user_selected_language.'.html')) {
					$home_top_temp = file_get_contents($this->home.'home_top_'.$user_selected_language.'.html');
				} else {
					$home_top_temp = file_get_contents($this->home.'home_top.html');
				}
			}
			if (trim($home_top_temp) == '' && api_is_platform_admin()) {
				$home_top_temp = get_lang('PortalHomepageDefaultIntroduction');
			}
			$open = str_replace('{rel_path}', api_get_path(REL_PATH), $home_top_temp);
			$html = api_to_system_encoding($open, api_detect_encoding(strip_tags($open)));
		}
		return $html;
	}

	function return_notice() {
		$sys_path               = api_get_path(SYS_PATH);
		$user_selected_language = api_get_interface_language();

		$html = '';
		// Notice
		$home_notice = @(string)file_get_contents($sys_path.$this->home.'home_notice_'.$user_selected_language.'.html');
		if (empty($home_notice)) {
			$home_notice = @(string)file_get_contents($sys_path.$this->home.'home_notice.html');
		}

		if (!empty($home_notice)) {
			$home_notice = api_to_system_encoding($home_notice, api_detect_encoding(strip_tags($home_notice)));
            $home_notice = Display::div($home_notice, array('class'  => 'homepage_notice'));
			$html = self::show_right_block(get_lang('Notice'), $home_notice, 'notice_block');
		}
        return $html;
    }

    function return_help() {
        $user_selected_language = api_get_interface_language();
        $sys_path               = api_get_path(SYS_PATH);
        $platformLanguage       = api_get_setting('platformLanguage');

		// Help section.
		/* Hide right menu "general" and other parts on anonymous right menu. */

		if (!isset($user_selected_language)) {
			$user_selected_language = $platformLanguage;
		}

        $html = null;
		$home_menu = @(string)file_get_contents($sys_path.$this->home.'home_menu_'.$user_selected_language.'.html');
		if (!empty($home_menu)) {
			$home_menu_content = '<ul class="nav nav-list">';
			$home_menu_content .= api_to_system_encoding($home_menu, api_detect_encoding(strip_tags($home_menu)));
			$home_menu_content .= '</ul>';
			$html .= self::show_right_block(get_lang('MenuGeneral'), $home_menu_content, 'help_block');
		}
		return $html;
	}

    function return_skills_links() {
        $html = '';
        if (api_get_setting('allow_skills_tool') == 'true') {
            $content = '<ul class="nav nav-list">';

            $content .= Display::tag('li', Display::url(get_lang('MySkills'), api_get_path(WEB_CODE_PATH).'social/skills_wheel.php'));

            if (api_get_setting('allow_hr_skills_management') == 'true' || api_is_platform_admin()) {
                $content .= Display::tag('li', Display::url(get_lang('ManageSkills'), api_get_path(WEB_CODE_PATH).'admin/skills_wheel.php'));
            }
            $content .= '</ul>';
            $html = self::show_right_block(get_lang("Skills"), $content, 'skill_block');
        }
        return $html;
    }

	/**
	 * Reacts on a failed login:
	 * Displays an explanation with a link to the registration form.
	 *
	 * @version 1.0.1
	 */
	function handle_login_failed() {
        $message = get_lang('InvalidId');

		if (!isset($_GET['error'])) {
			if (api_is_self_registration_allowed()) {
				$message = get_lang('InvalidForSelfRegistration');
			}
		} else {
			switch ($_GET['error']) {
				case '':
					if (api_is_self_registration_allowed()) {
						$message = get_lang('InvalidForSelfRegistration');
					}
					break;
				case 'account_expired':
					$message = get_lang('AccountExpired');
					break;
				case 'account_inactive':
					$message = get_lang('AccountInactive');
					break;
				case 'user_password_incorrect':
					$message = get_lang('InvalidId');
					break;
				case 'access_url_inactive':
					$message = get_lang('AccountURLInactive');
					break;
                case 'unrecognize_sso_origin':
                    //$message = get_lang('SSOError');
                    break;
			}
		}
		return Display::return_message($message, 'error');
	}

	/**
	 * Display list of courses in a category.
	 * (for anonymous users)
	 *
	 * @version 1.1
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University - refactoring and code cleaning
     * @author Julio Montoya <gugli100@gmail.com>, Beeznest template modifs
	 */
	function return_courses_in_categories() {
        $result = '';
		$ctok = $_SESSION['sec_token'];
		$stok = Security::get_token();

		// Initialization.
		$user_identified = (api_get_user_id() > 0 && !api_is_anonymous());
		$web_course_path = api_get_path(WEB_COURSE_PATH);
		$category = Database::escape_string($_GET['category']);
		$setting_show_also_closed_courses = api_get_setting('show_closed_courses') == 'true';

		// Database table definitions.
		$main_course_table      = Database :: get_main_table(TABLE_MAIN_COURSE);
		$main_category_table    = Database :: get_main_table(TABLE_MAIN_CATEGORY);

		// Get list of courses in category $category.
		$sql_get_course_list = "SELECT * FROM $main_course_table cours
	                                WHERE category_code = '".Database::escape_string($_GET['category'])."'
	                                ORDER BY title, UPPER(visual_code)";

		// Showing only the courses of the current access_url_id.
		global $_configuration;
		if ($_configuration['multiple_access_urls']) {
			$url_access_id = api_get_current_access_url_id();
			if ($url_access_id != -1) {
				$tbl_url_rel_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
				$sql_get_course_list = "SELECT * FROM $main_course_table as course INNER JOIN $tbl_url_rel_course as url_rel_course
	                    ON (url_rel_course.course_code=course.code)
	                    WHERE access_url_id = $url_access_id AND category_code = '".Database::escape_string($_GET['category'])."' ORDER BY title, UPPER(visual_code)";
			}
		}

		// Removed: AND cours.visibility='".COURSE_VISIBILITY_OPEN_WORLD."'
		$sql_result_courses = Database::query($sql_get_course_list);

		while ($course_result = Database::fetch_array($sql_result_courses)) {
			$course_list[] = $course_result;
		}

		$platform_visible_courses = '';
		// $setting_show_also_closed_courses
		if ($user_identified) {
			if ($setting_show_also_closed_courses) {
				$platform_visible_courses = '';
			} else {
				$platform_visible_courses = "  AND (t3.visibility='".COURSE_VISIBILITY_OPEN_WORLD."' OR t3.visibility='".COURSE_VISIBILITY_OPEN_PLATFORM."' )";
			}
		} else {
			if ($setting_show_also_closed_courses) {
				$platform_visible_courses = '';
			} else {
				$platform_visible_courses = "  AND (t3.visibility='".COURSE_VISIBILITY_OPEN_WORLD."' )";
			}
		}
		$sqlGetSubCatList = "
	                SELECT t1.name,t1.code,t1.parent_id,t1.children_count,COUNT(DISTINCT t3.code) AS nbCourse
	                FROM $main_category_table t1
	                LEFT JOIN $main_category_table t2 ON t1.code=t2.parent_id
	                LEFT JOIN $main_course_table t3 ON (t3.category_code=t1.code $platform_visible_courses)
	                WHERE t1.parent_id ". (empty ($category) ? "IS NULL" : "='$category'")."
	                GROUP BY t1.name,t1.code,t1.parent_id,t1.children_count ORDER BY t1.tree_pos, t1.name";


		// Showing only the category of courses of the current access_url_id
		if ($_configuration['multiple_access_urls']) {
			$url_access_id = api_get_current_access_url_id();
			if ($url_access_id != -1) {
				$tbl_url_rel_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
				$sqlGetSubCatList = "
	                SELECT t1.name,t1.code,t1.parent_id,t1.children_count,COUNT(DISTINCT t3.code) AS nbCourse
	                FROM $main_category_table t1
	                LEFT JOIN $main_category_table t2 ON t1.code=t2.parent_id
	                LEFT JOIN $main_course_table t3 ON (t3.category_code=t1.code $platform_visible_courses)
	                INNER JOIN $tbl_url_rel_course as url_rel_course
	                    ON (url_rel_course.course_code=t3.code)
	                WHERE access_url_id = $url_access_id AND t1.parent_id ".(empty($category) ? "IS NULL" : "='$category'")."
	                GROUP BY t1.name,t1.code,t1.parent_id,t1.children_count ORDER BY t1.tree_pos, t1.name";
			}
		}

		$resCats = Database::query($sqlGetSubCatList);
		$thereIsSubCat = false;
		if (Database::num_rows($resCats) > 0) {
			$htmlListCat = Display::page_header(get_lang('CatList'));
            $htmlListCat .= '<ul>';
			while ($catLine = Database::fetch_array($resCats)) {
				if ($catLine['code'] != $category) {
					$category_has_open_courses = self::category_has_open_courses($catLine['code']);
					if ($category_has_open_courses) {
						// The category contains courses accessible to anonymous visitors.
						$htmlListCat .= '<li>';
						$htmlListCat .= '<a href="'.api_get_self().'?category='.$catLine['code'].'">'.$catLine['name'].'</a>';
						if (api_get_setting('show_number_of_courses') == 'true') {
							$htmlListCat .= ' ('.$catLine['nbCourse'].' '.get_lang('Courses').')';
						}
						$htmlListCat .= "</li>";
						$thereIsSubCat = true;
					} elseif ($catLine['children_count'] > 0) {
						// The category has children, subcategories.
						$htmlListCat .= '<li>';
						$htmlListCat .= '<a href="'.api_get_self().'?category='.$catLine['code'].'">'.$catLine['name'].'</a>';
						$htmlListCat .= "</li>";
						$thereIsSubCat = true;
					}
					/* End changed code to eliminate the (0 courses) after empty categories. */
					elseif (api_get_setting('show_empty_course_categories') == 'true') {
						$htmlListCat .= '<li>';
						$htmlListCat .= $catLine['name'];
						$htmlListCat .= "</li>";
						$thereIsSubCat = true;
					} // Else don't set thereIsSubCat to true to avoid printing things if not requested.
				} else {
					$htmlTitre = '<p>';
					if (api_get_setting('show_back_link_on_top_of_tree') == 'true') {
						$htmlTitre .= '<a href="'.api_get_self().'">&lt;&lt; '.get_lang('BackToHomePage').'</a>';
					}
					if (!is_null($catLine['parent_id']) || (api_get_setting('show_back_link_on_top_of_tree') != 'true' && !is_null($catLine['code']))) {
						$htmlTitre .= '<a href="'.api_get_self().'?category='.$catLine['parent_id'].'">&lt;&lt; '.get_lang('Up').'</a>';
					}
					$htmlTitre .= "</p>";
					if ($category != "" && !is_null($catLine['code'])) {
						$htmlTitre .= '<h3>'.$catLine['name']."</h3>";
					} else {
						$htmlTitre .= '<h3>'.get_lang('Categories')."</h3>";
					}
				}
			}
			$htmlListCat .= "</ul>";
		}
		$result .= $htmlTitre;
		if ($thereIsSubCat) {
			$result .=  $htmlListCat;
		}
		while ($categoryName = Database::fetch_array($resCats)) {
			$result .= '<h3>' . $categoryName['name'] . "</h3>\n";
		}
		$numrows = Database::num_rows($sql_result_courses);
		$courses_list_string = '';
		$courses_shown = 0;
		if ($numrows > 0) {

			$courses_list_string .= Display::page_header(get_lang('CourseList'));
            $courses_list_string .= "<ul>";

			if (api_get_user_id()) {
				$courses_of_user = self::get_courses_of_user(api_get_user_id());
			}

			foreach ($course_list as $course) {
				// $setting_show_also_closed_courses
				if (!$setting_show_also_closed_courses) {
					// If we do not show the closed courses
					// we only show the courses that are open to the world (to everybody)
					// and the courses that are open to the platform (if the current user is a registered user.
					if( ($user_identified && $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM) || ($course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD)) {
						$courses_shown++;
						$courses_list_string .= "<li>\n";
						$courses_list_string .= '<a href="'.$web_course_path.$course['directory'].'/">'.$course['title'].'</a><br />';
                        $course_details = array();
						if (api_get_setting('display_coursecode_in_courselist') == 'true') {
							$course_details[] = $course['visual_code'];
						}
						if (api_get_setting('display_teacher_in_courselist') == 'true') {
							$course_details[] = $course['tutor_name'];
						}
						if (api_get_setting('show_different_course_language') == 'true' && $course['course_language'] != api_get_setting('platformLanguage')) {
							$course_details[] = $course['course_language'];
						}
                        $courses_list_string .= implode(' - ', $course_details);
						$courses_list_string .= "</li>\n";
					}
				} else {
                    // We DO show the closed courses.
                    // The course is accessible if (link to the course homepage):
                    // 1. the course is open to the world (doesn't matter if the user is logged in or not): $course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD);
                    // 2. the user is logged in and the course is open to the world or open to the platform: ($user_identified && $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM);
                    // 3. the user is logged in and the user is subscribed to the course and the course visibility is not COURSE_VISIBILITY_CLOSED;
                    // 4. the user is logged in and the user is course admin of te course (regardless of the course visibility setting);
                    // 5. the user is the platform admin api_is_platform_admin().
                    //
                    $courses_shown++;
					$courses_list_string .= "<li>\n";
					if ($course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD
                        || ($user_identified && $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM)
                        || ($user_identified && key_exists($course['code'], $courses_of_user) && $course['visibility'] != COURSE_VISIBILITY_CLOSED)
                        || $courses_of_user[$course['code']]['status'] == '1'
                        || api_is_platform_admin()) {
                            $courses_list_string .= '<a href="'.$web_course_path.$course['directory'].'/">';
                        }
                        $courses_list_string .= $course['title'];
                    if ($course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD
						|| ($user_identified && $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM)
						|| ($user_identified && key_exists($course['code'], $courses_of_user) && $course['visibility'] != COURSE_VISIBILITY_CLOSED)
	                        || $courses_of_user[$course['code']]['status'] == '1'
						|| api_is_platform_admin()) {
                        $courses_list_string .= '</a><br />';
                    }
                    $course_details = array();
                    if (api_get_setting('display_coursecode_in_courselist') == 'true') {
                        $course_details[] = $course['visual_code'];
                    }
//						if (api_get_setting('display_coursecode_in_courselist') == 'true' && api_get_setting('display_teacher_in_courselist') == 'true') {
//	                    $courses_list_string .= ' - ';
//				}
                    if (api_get_setting('display_teacher_in_courselist') == 'true') {
						$course_details[] = $course['tutor_name'];
	                }
	                if (api_get_setting('show_different_course_language') == 'true' && $course['course_language'] != api_get_setting('platformLanguage')) {
						$course_details[] = $course['course_language'];
	                }
                    if (api_get_setting('show_different_course_language') == 'true' && $course['course_language'] != api_get_setting('platformLanguage')) {
	                    $course_details[] = $course['course_language'];
	                }

                    $courses_list_string .= implode(' - ', $course_details);
					// We display a subscription link if:
	                // 1. it is allowed to register for the course and if the course is not already in the courselist of the user and if the user is identiefied
	                // 2.
                    if ($user_identified && !key_exists($course['code'], $courses_of_user)) {
                        if ($course['subscribe'] == '1') {
                        $courses_list_string .= '<form action="main/auth/courses.php?action=subscribe&category='.Security::remove_XSS($_GET['category']).'" method="post">';
                        $courses_list_string .= '<input type="hidden" name="sec_token" value="'.$stok.'">';
                        $courses_list_string .= '<input type="hidden" name="subscribe" value="'.$course['code'].'" />';
                            $courses_list_string .= '<input type="image" name="unsub" src="main/img/enroll.gif" alt="'.get_lang('Subscribe').'" />'.get_lang('Subscribe').'</form>';
                        } else {
                            $courses_list_string .= '<br />'.get_lang('SubscribingNotAllowed');
                        }
                    }
                    $courses_list_string .= "</li>";
	            } //end else
	        } // end foreach
	        $courses_list_string .= "</ul>";
        }
        if ($courses_shown > 0) {
            // Only display the list of courses and categories if there was more than
                    // 0 courses visible to the world (we're in the anonymous list here).
            $result .=  $courses_list_string;
        }
		if ($category != '') {
			$result .=  '<p><a href="'.api_get_self().'"> ' . Display :: return_icon('back.png', get_lang('BackToHomePage')) . get_lang('BackToHomePage') . '</a></p>';
		}
        return $result;
	}

	/**
	* retrieves all the courses that the user has already subscribed to
		* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
	* @param int $user_id: the id of the user
	* @return array an array containing all the information of the courses of the given user
		*/
	function get_courses_of_user($user_id) {
		$table_course       = Database::get_main_table(TABLE_MAIN_COURSE);
    	$table_course_user  = Database::get_main_table(TABLE_MAIN_COURSE_USER);
		// Secondly we select the courses that are in a category (user_course_cat <> 0) and sort these according to the sort of the category
		$user_id = intval($user_id);
		$sql_select_courses = "SELECT course.code k, course.visual_code  vc, course.subscribe subscr, course.unsubscribe unsubscr,
    		course.title i, course.tutor_name t, course.db_name db, course.directory dir, course_rel_user.status status,
    		course_rel_user.sort sort, course_rel_user.user_course_cat user_course_cat
    		FROM    $table_course       course,
    		$table_course_user  course_rel_user
    		WHERE course.code = course_rel_user.course_code
    		AND   course_rel_user.user_id = '".$user_id."'
                                    AND course_rel_user.relation_type<>".COURSE_RELATION_TYPE_RRHH."
                                    ORDER BY course_rel_user.sort ASC";
	    $result = Database::query($sql_select_courses);
	    $courses = array();
	    while ($row = Database::fetch_array($result)) {
	        // We only need the database name of the course.
	        $courses[$row['k']] = array('db' => $row['db'], 'code' => $row['k'], 'visual_code' => $row['vc'], 'title' => $row['i'], 'directory' => $row['dir'], 'status' => $row['status'], 'tutor' => $row['t'], 'subscribe' => $row['subscr'], 'unsubscribe' => $row['unsubscr'], 'sort' => $row['sort'], 'user_course_category' => $row['user_course_cat']);
	    }
		return $courses;
	}

    /**
     * @todo use the template system
     */
	function show_right_block($title, $content, $id = null, $params = null) {
	    if (!empty($id)) {
            $params['id'] = $id;
        }
        $params['class'] = 'well sidebar-nav';
        $html = null;
		if (!empty($title)) {
			$html.= '<h4>'.$title.'</h4>';
		}
		$html.= $content;
        $html = Display::div($html, $params);
		return $html;
	}

	/**
	 * Adds a form to let users login
	 * @version 1.1
	 */
	function display_login_form() {
		$form = new FormValidator('formLogin', 'POST', null,  null, array('class'=>'form-vertical'));
        // 'placeholder'=>get_lang('UserName')
        //'autocomplete'=>"off",

		$form->addElement('text', 'login', get_lang('UserName'), array('class' => 'span2 autocapitalize_off', 'autofocus' => 'autofocus'));
		$form->addElement('password', 'password', get_lang('Pass'), array('class' => 'span2'));
		$form->addElement('style_submit_button','submitAuth', get_lang('LoginEnter'), array('class' => 'btn'));
		$html = $form->return_form();
		if (api_get_setting('openid_authentication') == 'true') {
			include_once 'main/auth/openid/login.php';
			$html .= '<div>'.openid_form().'</div>';
		}
		return $html;
	}

	function return_search_block() {
		$html = '';
		if (api_get_setting('search_enabled') == 'true') {
			$html .= '<div class="searchbox">';
			$search_btn = get_lang('Search');
			$search_content = '<br />
		    	<form action="main/search/" method="post">
		    	<input type="text" id="query" class="span2" name="query" value="" />
		    	<button class="save" type="submit" name="submit" value="'.$search_btn.'" />'.$search_btn.' </button>
		    	</form></div>';
			$html .= self::show_right_block(get_lang('Search'), $search_content, 'search_block');
		}
		return $html;
	}

	function return_classes_block() {
		$html = '';
		if (api_get_setting('show_groups_to_users') == 'true') {
			require_once api_get_path(LIBRARY_PATH).'usergroup.lib.php';
			$usergroup = new Usergroup();
			$usergroup_list = $usergroup->get_usergroup_by_user(api_get_user_id());
			$classes = '';
			if (!empty($usergroup_list)) {
				foreach($usergroup_list as $group_id) {
					$data = $usergroup->get($group_id);
					$data['name'] = Display::url($data['name'], api_get_path(WEB_CODE_PATH).'user/classes.php?id='.$data['id']);
					$classes .= Display::tag('li', $data['name']);
				}
			}
			if (api_is_platform_admin()) {
				$classes .= Display::tag('li',  Display::url(get_lang('AddClasses') ,api_get_path(WEB_CODE_PATH).'admin/usergroups.php?action=add'));
			}
			if (!empty($classes)) {
				$classes = Display::tag('ul', $classes, array('class'=>'nav nav-list'));
				$html .= self::show_right_block(get_lang('Classes'), $classes, 'classes_block');
			}
		}
		return $html;
	}

	function return_reservation_block() {
		$html = '';
		if (api_get_setting('allow_reservation') == 'true' && api_is_allowed_to_create_course()) {
			$booking_content .='<ul class="nav nav-list">';
			$booking_content .='<a href="main/reservation/reservation.php">'.get_lang('ManageReservations').'</a><br />';
			$booking_content .='</ul>';
			$html .= self::show_right_block(get_lang('Booking'), $booking_content, 'reservation_block');
		}
		return $html;
	}

    function return_user_image_block() {
		$img_array = UserManager::get_user_picture_path_by_id(api_get_user_id(), 'web', true, true);
		$img_array = UserManager::get_picture_user(api_get_user_id(), $img_array['file'], 50, USER_IMAGE_SIZE_MEDIUM, ' width="90" height="90" ');
        $profile_content = null;
        if (api_get_setting('allow_social_tool') == 'true') {
            $profile_content .='<a style="text-align:center" href="'.api_get_path(WEB_PATH).'main/social/home.php"><img src="'.$img_array['file'].'"  '.$img_array['style'].' ></a>';
        } else {
            $profile_content .='<a style="text-align:center"  href="'.api_get_path(WEB_PATH).'main/auth/profile.php"><img title="'.get_lang('EditProfile').'" src="'.$img_array['file'].'" '.$img_array['style'].'></a>';
        }
        $html = self::show_right_block(null, $profile_content, 'user_image_block', array('style' => 'text-align:center;'));
        return $html;
    }

	function return_profile_block() {
		$user_id = api_get_user_id();

		if (empty($user_id)) {
			return;
		}

		$profile_content = '<ul class="nav nav-list">';

		//  @todo Add a platform setting to add the user image.
		if (api_get_setting('allow_message_tool') == 'true') {
			require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';

			// New messages.
			$number_of_new_messages             = MessageManager::get_new_messages();
			// New contact invitations.
			$number_of_new_messages_of_friend   = SocialManager::get_message_number_invitation_by_user_id(api_get_user_id());

			// New group invitations sent by a moderator.
			$group_pending_invitations = GroupPortalManager::get_groups_by_user(api_get_user_id(), GROUP_USER_PERMISSION_PENDING_INVITATION, false);
			$group_pending_invitations = count($group_pending_invitations);

			$total_invitations = $number_of_new_messages_of_friend + $group_pending_invitations;
            $cant_msg = Display::badge($number_of_new_messages);

			$link = '';
			if (api_get_setting('allow_social_tool') == 'true') {
				$link = '?f=social';
			}
			$profile_content .= '<li><a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php'.$link.'">'.get_lang('Inbox').$cant_msg.' </a></li>';
			$profile_content .= '<li><a href="'.api_get_path(WEB_PATH).'main/messages/new_message.php'.$link.'">'.get_lang('Compose').' </a></li>';

			if (api_get_setting('allow_social_tool') == 'true') {
				$total_invitations = Display::badge($total_invitations);
				$profile_content .= '<li><a href="'.api_get_path(WEB_PATH).'main/social/invitations.php">'.get_lang('PendingInvitations').$total_invitations.'</a></li>';
			}
        }
        $profile_content .= '<li><a href="'.api_get_path(WEB_PATH).'main/auth/profile.php">'.get_lang('EditProfile').'</a></li>';
        $profile_content .= '</ul>';
		$html = self::show_right_block(get_lang('Profile'), $profile_content, 'profile_block');
		return $html;
	}

	function return_navigation_links() {
		$html = '';

		// Deleting the myprofile link.
		if (api_get_setting('allow_social_tool') == 'true') {
			unset($this->tpl->menu_navigation['myprofile']);
		}

		// Main navigation section.
		// Tabs that are deactivated are added here.
		if (!empty($this->tpl->menu_navigation)) {
			$content = '<ul class="nav nav-list">';
			foreach ($this->tpl->menu_navigation as $section => $navigation_info) {
				$current = $section == $GLOBALS['this_section'] ? ' id="current"' : '';
				$content .= '<li'.$current.'>';
				$content .= '<a href="'.$navigation_info['url'].'" target="_self">'.$navigation_info['title'].'</a>';
				$content .= '</li>';
			}
			$content .= '</ul>';
			$html = self::show_right_block(get_lang('MainNavigation'), $content, 'navigation_link_block');
		}
		return $html;
	}

	function return_course_block() {
		$html = '';

		$show_create_link = false;
		$show_course_link = false;

		if ((api_get_setting('allow_users_to_create_courses') == 'false' && !api_is_platform_admin()) || api_is_student()) {
            $display_add_course_link = false;
        } else {
            $display_add_course_link = true;
        }
        //$display_add_course_link = api_is_allowed_to_create_course() && ($_SESSION['studentview'] != 'studentenview');

		if ($display_add_course_link) {
			$show_create_link = true;
		}

		if (api_is_platform_admin() || api_is_course_admin() || api_is_allowed_to_create_course()) {
			$show_course_link = true;
		} else {
			if (api_get_setting('allow_students_to_browse_courses') == 'true') {
				$show_course_link = true;
			}
		}

		// My account section
		$my_account_content = '<ul class="nav nav-list">';

		if ($show_create_link) {
			$my_account_content .= '<li><a href="main/create_course/add_course.php" class="add course">'.(api_get_setting('course_validation') == 'true' ? get_lang('CreateCourseRequest') : get_lang('CourseCreate')).'</a></li>';
		}

        //Sort courses
        $url = api_get_path(WEB_CODE_PATH).'auth/courses.php?action=sortmycourses';
        $my_account_content .= '<li>'.Display::url(get_lang('SortMyCourses'), $url, array('class' => 'sort course')).'</li>';

        //Course management
		if ($show_course_link) {
			if (!api_is_drh()) {
				$my_account_content .= '<li><a href="main/auth/courses.php" class="list course">'.get_lang('CourseCatalog').'</a></li>';

                if (isset($_GET['history']) && intval($_GET['history']) == 1) {
                    $my_account_content .= '<li><a href="user_portal.php">'.get_lang('DisplayTrainingList').'</a></li>';
                } else {
                    $my_account_content .= '<li><a href="user_portal.php?history=1"  class="history course">'.get_lang('HistoryTrainingSessions').'</a></li>';
                }

			} else {
				$my_account_content .= '<li><a href="main/dashboard/index.php">'.get_lang('Dashboard').'</a></li>';
			}
		}

		$my_account_content .= '</ul>';

		if (!empty($my_account_content)) {
			$html =  self::show_right_block(get_lang('Courses'), $my_account_content, 'course_block');
		}
		return $html;
	}

	/**
	 * The most important function here, prints the session and course list (user_portal.php)
	 *
	 * */
	function return_courses_and_sessions($user_id) {
        $session_categories = array();
        $load_history = (isset($_GET['history']) && intval($_GET['history']) == 1) ? true : false;

		if ($load_history) {
            //Load sessions in category in *history*
			$session_categories = UserManager::get_sessions_by_category($user_id, true);
		} else {
            //Load sessions in category
			$session_categories = UserManager::get_sessions_by_category($user_id, false);
		}

        $html = '';

        //Showing history title

		if ($load_history) {
			$html .= Display::page_subheader(get_lang('HistoryTrainingSession'));
			if (empty($session_categories)) {
				$html .=  get_lang('YouDoNotHaveAnySessionInItsHistory');
			}
		}

        $courses_html = '';
        $special_courses = '';

        // If we're not in the history view...
        if (!isset($_GET['history'])) {
            //Display special courses
            $special_courses = CourseManager::display_special_courses($user_id, $this->load_directories_preview);
            //Display courses
            $courses_html .= CourseManager::display_courses($user_id, $this->load_directories_preview);
        }

        $sessions_with_category = '';
        $sessions_with_no_category = '';

		if (is_array($session_categories)) {
            foreach ($session_categories as $session_category) {
                $session_category_id = $session_category['session_category']['id'];
                // Sessions and courses that are not in a session category
                if ($session_category_id == 0) {

                    // Independent sessions
                    foreach ($session_category['sessions'] as $session) {
                        $session_id = $session['session_id'];

                        // Don't show empty sessions.
                        if (count($session['courses']) < 1) {
                            continue;
                        }

                        // Courses inside the current session.
                        $date_session_start = $session['date_start'];
                        $days_access_before_beginning  = $session['nb_days_access_before_beginning'];
                        $days_access_after_end  = $session['nb_days_access_after_end'];
                        $date_session_end = $session['date_end'];
                        $session_now = time();
                        $html_courses_session = '';
                        $count_courses_session = 0;

                        foreach ($session['courses'] as $course) {
                            $is_coach_course = api_is_coach($session_id, $course['code']);
                            $allowed_time = 0;
                            $dif_time_after = 0;
                            if ($date_session_start != '0000-00-00') {
                                if ($is_coach_course) {
                                    $allowed_time = api_strtotime($date_session_start) - ($days_access_before_beginning*86400);
                                    if ($session_now > $date_session_end) {
                                        $dif_time_after = $session_now - api_strtotime($date_session_end);
                                        $dif_time_after = round($dif_time_after/86400);
                                    }
                                } else {
                                    $allowed_time = api_strtotime($date_session_start);
                                }
                            }
                            if ($session_now > $allowed_time && $days_access_after_end >= $dif_time_after-1) {
                                //read only and accesible
                                if (api_get_setting('hide_courses_in_sessions') == 'false') {
                                    $c = CourseManager :: get_logged_user_course_html($course, $session_id, 'session_course_item', true, $this->load_directories_preview);
                                    $html_courses_session .= $c[1];
                                }
                                $count_courses_session++;
                            }
                        }

                        if ($count_courses_session > 0) {
                            $params = array();

                            $session_box = Display :: get_session_title_box($session_id);

                            $params['icon'] =  Display::return_icon('window_list.png', $session_box['title'], array('id' => 'session_img_'.$session_id), ICON_SIZE_LARGE);
                            $extra_info = !empty($session_box['coach']) ? $session_box['coach'] : null;
                            $extra_info .= !empty($session_box['coach']) ? ' - '.$session_box['dates'] : $session_box['dates'];

                            if (api_is_drh()) {
                                $session_link = $session_box['title'];
                                $params['link'] = null;
                            } else {
                                $session_link = Display::tag('a', $session_box['title'], array('href'=>api_get_path(WEB_CODE_PATH).'session/index.php?session_id='.$session_id));
                                $params['link'] = api_get_path(WEB_CODE_PATH).'session/index.php?session_id='.$session_id;
                            }

                            $params['title'] = $session_link;
                            $params['subtitle'] = $extra_info;

                            $params['right_actions'] = '';
                            if (api_is_platform_admin()) {
                                $params['right_actions'] .= '<a href="'.api_get_path(WEB_CODE_PATH).'admin/resume_session.php?id_session='.$session_id.'">';
                                $params['right_actions'] .= Display::return_icon('edit.png', get_lang('Edit'), array('align' => 'absmiddle'), ICON_SIZE_SMALL).'</a>';
                            }

                            if (api_get_setting('hide_courses_in_sessions') == 'false') {
                            //	$params['extra'] .=  $html_courses_session;
                            }
                            $sessions_with_no_category .= CourseManager::course_item_parent(CourseManager::course_item_html($params, true), $html_courses_session);
                        }
                    }
				} else {
					// All sessions included in
                    $count_courses_session = 0;
                    $html_sessions = '';
                    foreach ($session_category['sessions'] as $session) {
                        $session_id = $session['session_id'];
                        //var_dump($session);var_dump($session_category);
                        // Don't show empty sessions.
                        if (count($session['courses']) < 1) {
                            continue;
                        }
                        $date_session_start             = $session['date_start'];
                        //api_get_session_visibility($session_id);
                        $days_access_before_beginning   = $session['nb_days_access_before_beginning'];
                        $days_access_after_end  = $session['nb_days_access_after_end'];
                        $date_session_end = $session['date_end'];
                        $session_now = time();
                        $html_courses_session = '';
                        $count = 0;

                        foreach ($session['courses'] as $course) {
                            $is_coach_course = api_is_coach($session_id, $course['code']);
                            $dif_time_after = 0;
                            if ($is_coach_course) {
                                $allowed_time = api_strtotime($date_session_start) - ($days_access_before_beginning*86400);
                                if ($session_now > $date_session_end) {
                                        $dif_time_after = $session_now - api_strtotime($date_session_end);
                                        $dif_time_after = round($dif_time_after/86400);
                                }
                            } else {
                                $allowed_time = api_strtotime($date_session_start);
                            }
                            if ($session_now > $allowed_time && $days_access_after_end >= $dif_time_after-1) {
                                if (api_get_setting('hide_courses_in_sessions') == 'false') {
                                    $c = CourseManager :: get_logged_user_course_html($course, $session_id, 'session_course_item');
                                    $html_courses_session .= $c[1];
                                }
                                $count_courses_session++;
                                $count++;
                            }
                        }

                        $params = array();

                        if ($count > 0) {
                            $session_box = Display :: get_session_title_box($session_id);
                            $params['icon'] = Display::return_icon('window_list.png', $session_box['title'], array('width' => '48px', 'align' => 'absmiddle', 'id' => 'session_img_'.$session_id)) . ' ';

                            if (api_is_drh()) {
                                $session_link = $session_box['title'];
                                $params['link'] = null;
                            } else {
                                $session_link   = Display::tag('a', $session_box['title'], array('href'=>api_get_path(WEB_CODE_PATH).'session/index.php?session_id='.$session_id));
                                $params['link'] =  api_get_path(WEB_CODE_PATH).'session/index.php?session_id='.$session_id;
                            }

                            $params['title'] .=  $session_link;

                            $params['subtitle'] =  (!empty($session_box['coach']) ? $session_box['coach'].' | ' : '').$session_box['dates'];

                            if (api_is_platform_admin()) {
                                $params['right_actions'] .=  '<a href="'.api_get_path(WEB_CODE_PATH).'admin/resume_session.php?id_session='.$session_id.'">'.Display::return_icon('edit.png', get_lang('Edit'), array('align' => 'absmiddle'), ICON_SIZE_SMALL).'</a>';
                            }
                            $html_sessions .= CourseManager::course_item_html($params, true).$html_courses_session;
                        }
                    }

                    if ($count_courses_session > 0) {
                        $params = array();
                        $params['icon'] = Display::return_icon('folder_blue.png', $session_category['session_category']['name'], array(), ICON_SIZE_LARGE);

                        if (api_is_platform_admin()) {
                            $params['right_actions'] .= '<a href="'.api_get_path(WEB_CODE_PATH).'admin/session_category_edit.php?&id='.$session_category['session_category']['id'].'">'.Display::return_icon('edit.png', get_lang('Edit'), array(), ICON_SIZE_SMALL).'</a>';
                        }

                        $params['title'] .= $session_category['session_category']['name'];

                        if (api_is_platform_admin()) {
                            $params['link']   = api_get_path(WEB_CODE_PATH).'admin/session_category_edit.php?&id='.$session_category['session_category']['id'];
                        }

                        $session_category_start_date = $session_category['session_category']['date_start'];
                        $session_category_end_date = $session_category['session_category']['date_end'];

                        if (!empty($session_category_start_date) && $session_category_start_date != '0000-00-00' && !empty($session_category_end_date) && $session_category_end_date != '0000-00-00' ) {
                            $params['subtitle'] = sprintf(get_lang('FromDateXToDateY'), $session_category['session_category']['date_start'], $session_category['session_category']['date_end']);
                        } else {
                            if (!empty($session_category_start_date) && $session_category_start_date != '0000-00-00') {
                                 $params['subtitle'] = get_lang('From').' '.$session_category_start_date;
                            }
                            if (!empty($session_category_end_date) && $session_category_end_date != '0000-00-00') {
                                $params['subtitle'] = get_lang('Until').' '.$session_category_end_date;
                            }
                        }
                        $sessions_with_category .= CourseManager::course_item_parent(CourseManager::course_item_html($params, true), $html_sessions);
                    }

				}
			}
		}
        return $sessions_with_category.$sessions_with_no_category.$courses_html.$special_courses;
	}

    /**
     * Shows a welcome message when the user doesn't have any content in the course list
     */
    function return_welcome_to_course_block() {
        $count_courses = CourseManager::count_courses();
        $tpl = $this->tpl->get_template('layout/welcome_to_course.tpl');

        $course_catalog_url = api_get_path(WEB_CODE_PATH).'auth/courses.php';
        $course_list_url = api_get_path(WEB_PATH).'user_portal.php';

        $this->tpl->assign('course_catalog_url', $course_catalog_url);
        $this->tpl->assign('course_list_url', $course_list_url);
        $this->tpl->assign('course_catalog_link', Display::url(get_lang('here'), $course_catalog_url));
        $this->tpl->assign('course_list_link', Display::url(get_lang('here'), $course_list_url));
        $this->tpl->assign('count_courses', $count_courses);

        return $this->tpl->fetch($tpl);
    }

	function return_hot_courses() {
		return CourseManager::return_hot_courses();
	}
}
