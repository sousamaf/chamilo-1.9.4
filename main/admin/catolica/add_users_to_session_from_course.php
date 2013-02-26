<?php

require_once '../../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'course.lib.php';
require_once api_get_path(LIBRARY_PATH) . 'sessionmanager.lib.php';
require_once api_get_path(LIBRARY_PATH) . 'database.lib.php';
require_once api_get_path(LIBRARY_PATH) . 'usermanager.lib.php';

require_once '../../forum/forumconfig.inc.php';
require_once '../../forum/forumfunction.inc.php';

api_protect_admin_script(true);

$debug = false; //true;

$user_to_session = array();
$session_id = intval($_GET['session_id']);
$course_code = $_GET['course_code'];
$course_code = Database::escape_string($course_code);

$defaultprof_id = 1;
$defaultprof_name = "Admin System";

echo "Curso: " . $course_code . "<br>";
$table_main = Database::get_main_database();

$table = Database::get_main_table(TABLE_MAIN_COURSE);


$sql = "SELECT * FROM $table WHERE code='" . $course_code . "'";
if($debug) echo $sql. "<br>";

$res = Database::query($sql);
$course = Database::fetch_object($res);

$course_id = $course->id;
$course_list = array();
$course_list[] = $course->code;
$course_title = $course->title;
$course_prof = array();
$course_prof = CourseManager::get_teacher_list_from_course_code($course_code);

if($debug) { echo "<pre>"; print_r($course); print_r($course_prof); echo "</pre>"; }

$course_prof_id = array();

foreach ($course_prof as $prof) {
    $course_prof_id[] = $prof['user_id'];
    if($debug)
        echo "Prof: " . $prof['firstname'] . "<br>";
}

if (empty($session_id)) {
    $name = $course_code . ".2012.2";
    $year_start = "2012";
    $month_start = "7";
    $day_start = "28";
    $year_end = "2012";
    $month_end = "12";
    $day_end = "18";
    $nb_days_acess_before = "0";
    $nb_days_acess_after = "0";
    $nolimit = "";
    $coach_username = "tutor";
    $id_session_category = "5"; // Verificar codigo da categoria.
    $id_visibility = 1;
    if (!$debug) {
        $session_id = SessionManager::create_session($name, $year_start, $month_start, $day_start, $year_end, $month_end, $day_end, $nb_days_acess_before, $nb_days_acess_after, $nolimit, $coach_username, $id_session_category, $id_visibility);
        echo "Criou sessao: " . $session_id . "<br>";
    }
    global $_configuration;
    require_once (api_get_path(LIBRARY_PATH) . 'urlmanager.lib.php');
    if ($_configuration['multiple_access_urls']) {
        $tbl_user_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $access_url_id = api_get_current_access_url_id();
        if (!$debug) {
            UrlManager::add_session_to_url($session_id, $access_url_id);
        }
    } else {
        // we are filling by default the access_url_rel_session table
        if (!$debug) {
            UrlManager::add_session_to_url($session_id, 1);
        }
    }
}

if (!empty($session_id) && $session_id != 0) {
    
    // arquivamento do conteúdo do curso.
    $tables = array();
    $tables[] = Database::get_course_table(TABLE_ANNOUNCEMENT);
    $tables[] = Database::get_course_table(TABLE_ATTENDANCE);
    $tables[] = Database::get_course_table(TABLE_AGENDA);
    $tables[] = Database::get_course_table(TABLE_CHAT_CONNECTED);
    $tables[] = Database::get_course_table(TABLE_DROPBOX_CATEGORY);
    $tables[] = Database::get_course_table(TABLE_DROPBOX_FILE);
    $tables[] = Database::get_course_table(TABLE_DROPBOX_POST);
    $tables[] = Database::get_course_table(TABLE_FORUM);
    $tables[] = Database::get_course_table(TABLE_FORUM_CATEGORY);
    $tables[] = Database::get_course_table(TABLE_FORUM_THREAD);
    $tables[] = Database::get_course_table(TABLE_FORUM_THREAD_QUALIFY);
    $tables[] = Database::get_course_table(TABLE_FORUM_THREAD_QUALIFY_LOG);
    $tables[] = Database::get_course_table(TABLE_NOTEBOOK);
    $tables[] = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $tables[] = Database::get_course_table(TABLE_SURVEY);
    $tables[] = Database::get_course_table(TABLE_SURVEY_INVITATION);
    $tables[] = Database::get_course_table(TABLE_THEMATIC);
    $tables[] = Database::get_course_table(TABLE_QUIZ_TEST);
    
    foreach ($tables as $table)
    {
        $sql = "UPDATE " . $table . " SET session_id =" . $session_id . " WHERE session_id=0 AND c_id =" . $course_id . ";";
        if (!$debug) {
            Database::query($sql);
        } else {
            echo $sql . "<br>";
        }
    }
    // Limpa a rota de aprendizagem de itens arquivados.
    $table = Database::get_course_table(TABLE_LP_ITEM);
    $sql = "DELETE FROM " . $table . " WHERE item_type='forum' AND c_id =" . $course_id . ";";
    if (!$debug) {
        Database::query($sql);
    } else {
        echo $sql . "<br>";
    }
    
    $sql = "DELETE FROM " . $table . " WHERE item_type='student_publication' AND c_id =" . $course_id . ";";
    if (!$debug) {
        Database::query($sql);
    } else {
        echo $sql . "<br>";
    }
    // fim da limpeza da rota de aprendizagem.
    
    $table = Database::get_course_table(TABLE_ITEM_PROPERTY);
    $sql = "UPDATE " . $table . " SET id_session =" . $session_id . " WHERE id_session=0 AND tool='work' AND c_id =". $course_id .";";
    if (!$debug) {
        Database::query($sql);
    } else {
        echo $sql . "<br>";
    }
    $sql = "UPDATE " . $table. " SET id_session =" . $session_id . " WHERE id_session=0 AND tool='survey' AND c_id =". $course_id .";";
    if (!$debug) {
        Database::query($sql);
    } else {
        echo $sql . "<br>";
    }
    $sql = "UPDATE " . $table . " SET id_session =" . $session_id . " WHERE id_session=0 AND tool='forum_thread' AND c_id =". $course_id .";";
    if (!$debug) {
        Database::query($sql);
    } else {
        echo $sql . "<br>";
    }
    $sql = "UPDATE " . $table . " SET id_session =" . $session_id . " WHERE id_session=0 AND tool='forum_category' AND c_id =". $course_id .";";
    if (!$debug) {
        Database::query($sql);
    } else {
        echo $sql . "<br>";
    }
    $sql = "UPDATE " . $table . " SET id_session =" . $session_id . " WHERE id_session=0 AND tool='forum_attachment' AND c_id =". $course_id .";";
    if (!$debug) {
        Database::query($sql);
    } else {
        echo $sql . "<br>";
    }
    $sql = "UPDATE " . $table . " SET id_session =" . $session_id . " WHERE id_session=0 AND tool='forum' AND c_id =". $course_id .";";
    if (!$debug) {
        Database::query($sql);
    } else {
        echo $sql . "<br>";
    }
    $sql = "UPDATE " . $table . " SET id_session =" . $session_id . " WHERE id_session=0 AND tool='dropbox' AND c_id =". $course_id .";";
    if (!$debug) {
        Database::query($sql);
    } else {
        echo $sql . "<br>";
    }
    $sql = "UPDATE " . $table . " SET id_session =" . $session_id . " WHERE id_session=0 AND tool='announcement' AND c_id =". $course_id .";";
    if (!$debug) {
        Database::query($sql);
    } else {
        echo $sql . "<br>";
    }
    $sql = "UPDATE " . $table . " SET id_session =" . $session_id . " WHERE id_session=0 AND tool='attendance' AND c_id =". $course_id .";";
    if (!$debug) {
        Database::query($sql);
    } else {
        echo $sql . "<br>";
    }
    $sql = "UPDATE " . $table . " SET id_session =" . $session_id . " WHERE id_session=0 AND tool='calendar_event' AND c_id =". $course_id .";";
    if (!$debug) {
        Database::query($sql);
    } else {
        echo $sql . "<br>";
    }
    $sql = "UPDATE " . $table . " SET id_session =" . $session_id . " WHERE id_session=0 AND tool='quiz' AND c_id =". $course_id .";";
    if (!$debug) {
        Database::query($sql);
    } else {
        echo $sql . "<br>";
    }


    // historico de uso
    $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
    $sql = "UPDATE " . $table. " SET session_id=" . $session_id . ", visible=1 WHERE (session_id=0 OR session_id is NULL) AND course_code='" . $course_code . "';";
    if (!$debug) {
        Database::query($sql);
    } else {
        echo $sql . "<br>";
    }
    
    $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
    $sql = "UPDATE " . $table . " SET session_id=" . $session_id . " WHERE (session_id=0 OR session_id is NULL) AND exe_cours_id='" . $course_code . "';";
    if (!$debug) {
        Database::query($sql);
    } else {
        echo $sql . "<br>";
    }
    
    $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
    $sql = "UPDATE " . $table . " SET session_id=" . $session_id . " WHERE (session_id=0 OR session_id is NULL) AND course_code='" . $course_code . "';";
    if (!$debug) {
        Database::query($sql);
    } else {
        echo $sql . "<br>";
    }
    
    //buscar os attemp para incluir aqui

    $sql = "SELECT distinct exe_id FROM " . $table . " WHERE course_code='" . $course_code . "' AND (session_id=0 OR session_id is NULL)";
    if($debug) echo $sql. "<br>";
    $res = Database::query($sql);
    
    $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING);
    while ($tentativasExercicios = Database::fetch_array($res)) {
        $sql = "UPDATE " . $table . " SET session_id=" . $session_id . " WHERE exe_id='" . $tentativasExercicios['exe_id'] . "';";
        if (!$debug) {
            Database::query($sql);
        } else {
            echo $sql . "<br>";
        }
    }

    $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ACCESS);
    $sql = "UPDATE " . $table . " SET access_session_id=" . $session_id . " WHERE (access_session_id=0 OR access_session_id is NULL) AND access_cours_code='" . $course_code . "';";
    if (!$debug) {
        Database::query($sql);
    } else {
        echo $sql . "<br>";
    }
    
    $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_DOWNLOADS);
    $sql = "UPDATE " . $table . " SET down_session_id=" . $session_id . " WHERE (down_session_id=0 OR down_session_id is NULL) AND down_cours_id='" . $course_code . "';";
    if (!$debug) {
        Database::query($sql);
    } else {
        echo $sql . "<br>";
    }

    $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ITEM_PROPERTY);
    $sql = "UPDATE " . $table . " SET session_id=" . $session_id . " WHERE (session_id=0 OR session_id is NULL) AND course_id='" . $course_code . "';";
    if (!$debug) {
        Database::query($sql);
    } else {
        echo $sql . "<br>";
    }

    $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LASTACCESS);
    $sql = "UPDATE " . $table. " SET access_session_id=" . $session_id . " WHERE (access_session_id=0 OR access_session_id is NULL) AND access_cours_code='" . $course_code . "';";
    if (!$debug) {
        Database::query($sql);
    } else {
        echo $sql . "<br>";
    }

    $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_UPLOADS);
    $sql = "UPDATE " . $table . " SET upload_session_id=" . $session_id . " WHERE (upload_session_id=0 OR upload_session_id is NULL) AND upload_cours_id='" . $course_code . "';";
    if (!$debug) {
        Database::query($sql);
    } else {
        echo $sql . "<br>";
    }

    // retira a galera
    if (!$debug) {
        SessionManager::add_courses_to_session($session_id, $course_list, false);

        foreach ($course_prof_id as $prof_id) {
            SessionManager::set_coach_to_course_session($prof_id, $session_id, $course_code);
            CourseManager::unsubscribe_user($prof_id, $course_code, 0);
        }
        CourseManager::update_attribute($course_id, "tutor_name", $defaultprof_name);
        CourseManager::add_user_to_course($defaultprof_id, $course_code, COURSEMANAGER);
    }
    $list_course_users = CourseManager::get_student_list_from_course_code($course_code);
    foreach ($list_course_users as $user) {
        $user_to_session[] = $user['user_id'];
        if ($debug) {
            echo "Aluno: " . $user['user_id'] . "<br>";
        }
    }

    if (!$debug) {
        SessionManager::suscribe_users_to_session($session_id, $user_to_session, true, false);

        foreach ($list_course_users as $user)
            CourseManager::unsubscribe_user($user['user_id'], $course_code);
    }
    // fim da remocao da galera
    
    if (!isset($_GET['forum'])) {
        // criar novo forum
        // busca numero de ordem da categoria
        $table_categories = Database :: get_course_table(TABLE_FORUM_CATEGORY);

        // find the max cat_order. The new forum category is added at the end => max cat_order + &
        $sql = "SELECT MAX(cat_order) as sort_max FROM " . Database::escape_string($table_categories) . " WHERE c_id = ". $course_id;
        if($debug) echo $sql. "<br>";
        
        $result = Database::query($sql);
        $row = Database::fetch_array($result);
        $new_max = $row['sort_max'] + 1;

        $donodoforum = UserManager::get_user_id_from_username("tutor");

        $basic_course_session_id = 0;

        $sql = "INSERT INTO " . $table_categories . " (c_id, cat_title, cat_comment, cat_order, session_id) VALUES ('" . $course_id . "','" . $course_title . "','','" . Database::escape_string($new_max) . "','" . Database::escape_string($basic_course_session_id) . "')";
        if($debug) echo $sql. "<br>";
        
        if(!$debug)
            Database::query($sql);
        
        $last_id = Database::insert_id();
        if ($last_id > 0) {
            api_item_property_update(api_get_course_info($course_code), TOOL_FORUM_CATEGORY, $last_id, 'ForumCategoryAdded', $donodoforum);
        }
        
        
        
        // adiciona o novo forum geral
        $table_forums = Database::get_course_table(TABLE_FORUM);
        $forum_title = "Fórum Geral";
        $sql_image = '';
        $sql = "INSERT INTO " . $table_forums . "
            (c_id, forum_title, forum_image, forum_comment, forum_category, allow_anonymous, allow_edit, approval_direct_post, allow_attachments, allow_new_threads, default_view, forum_of_group, forum_group_public_private, forum_order, session_id)
            VALUES ('". $course_id ."', '" . $forum_title . "',
                    '" . $sql_image . "',
                    '',
                    '" . $last_id . "',
                    '',
                    '0',
                    '',
                    '0',
                    '1',
                    'flat',
                    '0',
                    'public',
                    '1',
                    " . intval($basic_course_session_id) . ")";
        if($debug) echo $sql . "<br>";
        if(!$debug)
        {
            Database::query($sql);

            $last_id = Database::insert_id();
            if ($last_id > 0) {
                api_item_property_update(api_get_course_info($course_code), TOOL_FORUM, $last_id, 'ForumAdded', $donodoforum);
            }
        }
    }
}
echo "<pre>";
echo "Session criada! Impacto em " . count($user_to_session) . " estudantes.";
echo "</pre>";



