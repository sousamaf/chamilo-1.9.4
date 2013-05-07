<?php
/* For licensing terms, see /license.txt */

/**
* Template (view in MVC pattern) used for displaying blocks for dashboard
* @author Christian Fasanando <christian1827@gmail.com>
* @author Marco Sousa <marco.volare@gmail.com>
* @package chamilo.dashboard
*/

// protect script
api_block_anonymous_users();

// menu actions for dashboard views
$views = array('help', 'report', 'list');

if(isset($_GET['view']) && in_array($_GET['view'], $views)){
	$avaliacao_view = $_GET['view'];
}
$user_id = api_get_user_id();
$cpf = CatolicaDoTocantins::ct_getCpfFromUserid($user_id);
$link_help_view = $link_list_view = $link_report_view = null;

$link_help_view = '<a href="'.api_get_self().'?view=help">'.Display::return_icon('info.png',get_lang('HelpAvaliacaoInstitucionalTitulo'),'',ICON_SIZE_MEDIUM).'</a>';
if(AvaliacaoInstitucional::isViewedHelp($user_id))
	$link_list_view = '<a href="'.api_get_self().'?view=list">'.Display::return_icon('view_text.png',get_lang('ActionListaDeAvaliacao'),'',ICON_SIZE_MEDIUM).'</a>';
if(AvaliacaoInstitucional::isViewedHelp($user_id) && AvaliacaoInstitucional::isAllSurveyDone($user_id, $cpf))
	$link_report_view = '<a href="'.api_get_self().'?view=report">'.Display::return_icon('stats.png',get_lang('ActionStatistics'),'',ICON_SIZE_MEDIUM).'</a>';

$configuration_link = null;
if (api_is_platform_admin()) {
	$configuration_link = '<a href="'.api_get_path(WEB_CODE_PATH).'admin/settings.php?category=Plugins">'
	.Display::return_icon('settings.png',get_lang('ConfigureDashboardPlugin'),'',ICON_SIZE_MEDIUM).'</a>';
}

echo '<div class="actions">';
echo $link_help_view.$link_list_view.$link_report_view.$configuration_link;
echo '</div>';

// block dashboard view
if (isset($avaliacao_view) && $avaliacao_view == 'report') {

	if ((AvaliacaoInstitucional::isActiveTeacher($cpf) && AvaliacaoInstitucional::isSurveyDone($user_id, ENQUETEPROFESSOR)) OR ((AvaliacaoInstitucional::isActiveStudent($cpf) && AvaliacaoInstitucional::isAllSurveyDone($user_id, $cpf))))
	{
		$columns = array();
		// group content html by number of column
		if (is_array($blocks)) {
			$tmp_columns = array();
			foreach ($blocks as $block) {
				$tmp_columns[] = $block['column'];
				if (in_array($block['column'], $tmp_columns)) {
					$columns['column_'.$block['column']][] = $block['content_html'];
				}
			}
		}

		echo '<div id="columns">';
		$survey_code = '202N10A2020262';
		$survey_questions = AvaliacaoInstitucional::report_get_list_of_questions($survey_code);
		
		if (count($survey_questions) > 0) {
				echo '<ul id="column1" class="column">';
				foreach ($survey_questions as $survey_question) {
					Display::display_normal_message($survey_question['survey_question'], false);
					 
					if($survey_question['type'] == 'score')
					{
						$option = AvaliacaoInstitucional::report_get_list_of_questions_options_score($survey_question['c_id'], $survey_question['survey_id'], $survey_question['question_id']);
						echo $option['text'];
					}
				}
				echo '</ul>';
			} else {
				echo '<ul id="column1" class="column">';
				echo '&nbsp;';
				echo '</ul>';
			}
			// blocks for column 2
			if (in_array('column_2',$columns_name)) {
				// blocks for column 1
				echo '<ul id="column2" class="column">';
					foreach ($columns['column_2'] as $content) {
						echo $content;
					}
				echo '</ul>';
			} else {
				echo '<ul id="column2" class="column">';
				echo '&nbsp;';
				echo '</ul>';
			}
			echo '</div>';
	} else {
		echo '<div style="margin-top:20px;">'.get_lang('ReportNaoHaDadosSuficientes').'</div>';
	}

} else if (isset($avaliacao_view) && $avaliacao_view == 'list') {
	// block dashboard list
	if (isset($success)) {
		Display::display_confirmation_message(get_lang('HelpConfirmacaoSucesso'));
	}
	$user_id = api_get_user_id();
	AvaliacaoInstitucional::display_user_avaliacao_list($user_id);

} else { // help
	$user_id = api_get_user_id();
	AvaliacaoInstitucional::display_avaliacao_help($user_id);
}
