<?php
/* For licensing terms, see /license.txt */
/**
 * Reporting page on the user's own progress
 * @package chamilo.tracking
 */
/**
 * Code
 */
// name of the language file that needs to be included
$language_file = array('registration', 'tracking', 'exercice', 'admin', 'learnpath');

$cidReset = true;
require_once '../inc/global.inc.php';
require_once api_get_path(SYS_CODE_PATH).'newscorm/learnpath.class.php';
require_once api_get_path(SYS_CODE_PATH).'exercice/exercise.lib.php';

$this_section = SECTION_TRACKING;

$nameTools = get_lang('MyProgress');

api_block_anonymous_users();

$htmlHeadXtra[] = api_get_js('jquery.timelinr-0.9.5.js');

$htmlHeadXtra[] = '
<script language="javascript">
$(function() {
    $().timelinr();
    $(".dialog").dialog("destroy");
    $(".dialog").dialog({
            autoOpen: false,
            show: "blind",
            resizable: false,
            height:300,
            width:550,
            modal: true
     });

    $(".opener").click(function() {
        var my_id = $(this).attr(\'id\');
        var big_image = \'#main_graph_\' + my_id;
        $( big_image ).dialog("open");
        return false;
    });
});
</script>';
require_once api_get_path(SYS_CODE_PATH).'mySpace/myspace.lib.php';

$user_id = api_get_user_id();

$course_user_list = CourseManager::get_courses_list_by_user_id($user_id);
$dates = $issues = '';

if (!empty($course_user_list)) {
    $items = MySpace::get_connections_from_course_list($user_id, $course_user_list);

    $first = null;
    $last = null;
    $last_item = count($items);
    $count = 1;
    foreach ($items as $result) {
        $login = $result['login'];
        if ($count == 1) {
            $first = '<a href="#'.$login.'">'.get_lang('First').'</a>';
        }
        if ($count == $last_item) {
            $last = '<a href="#'.$login.'">'.get_lang('Last').'</a>';
        }
        $course_info = api_get_course_info($result['course_code']);
        $course_image = '<img src="'.$course_info['course_image'].'">';
        $dates .= '<li><a href="#'.$login.'">'.api_get_utc_datetime($login).'</a></li>';
        $issues .= '<li id ="'.$login.'">
                        <div class="row">
                            <div class="span2"><div class="thumbnail">'.$course_image.'</div>
                        </div>
                        <div class="span3">'.sprintf(get_lang('YouHaveEnteredTheCourseXInY'), $result['course_code'], api_convert_and_format_date($login, DATE_FORMAT_LONG)).'</div>
                    </li>';
        $count++;
    }
}

$content .= Tracking::show_user_progress(api_get_user_id());
$content .= Tracking::show_course_detail(api_get_user_id(), $_GET['course'], $_GET['session_id']);

if (!empty($dates)) {
    if (!empty($content)) {
        $content .= '<br /><br />';
    }
    $content .= '<div class="row"><div class="span12">'.Display::page_subheader(get_lang('Timeline')).'</div>';

    $content .= '<div id="my_timeline">
        <div class="actions">
            <a href="#" id="prev"></a> <!-- optional -->
            <a href="#" id="next"></a> <!-- optional -->
        </div>

    <ul id="dates">
        '.$dates.'
    </ul>
    <ul id="issues">
        '.$issues.'
    </ul>
    </div></div>';
}

if (empty($content)) {
    $message = Display::return_message(get_lang('NoDataAvailable'), 'warning');
}

$tpl = new Template($tool_name);

$tpl->assign('message', $message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();