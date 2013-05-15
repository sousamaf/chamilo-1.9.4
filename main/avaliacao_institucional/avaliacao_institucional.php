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
$survey_code = "";
if(isset($_GET['survey'])){
	$survey_code = $_GET['survey'];
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
	$surveys = array();
	if ((AvaliacaoInstitucional::isActiveTeacher($cpf) && AvaliacaoInstitucional::isSurveyDone($user_id, ENQUETEPROFESSOR)) OR (AvaliacaoInstitucional::isGestor($user_id)) ) //OR ((AvaliacaoInstitucional::isActiveStudent($cpf) && AvaliacaoInstitucional::isAllSurveyDone($user_id, $cpf))))
	{
		$avaliacoes = array(); 
		$link_avaliacao = array();
		$link_avaliacao_coord = array();
		$info = array();
		$avaliacoes = AvaliacaoInstitucional::getCoursesOfTeacher($cpf); 
		$avaliacoes_coord = AvaliacaoInstitucional::getCoordenacoesResponsaveis($user_id);
		// group content html by number of column
		if (is_array($avaliacoes)) {
			foreach ($avaliacoes as $avaliacao) {
				$surveys[] = $avaliacao['turma'].$avaliacao['codigo'];
				$info[$avaliacao['turma'].$avaliacao['codigo']] = "Curso: " . $avaliacao['curso'] . "<br>Disciplina: " . $avaliacao['disciplina'] . "<br>Turma/Código: " . $avaliacao['turma']. "/". $avaliacao['codigo'];
				$link_avaliacao[] = '<a href="'.api_get_self().'?view=report&survey='.$avaliacao['turma'].$avaliacao['codigo'].'">'.$avaliacao['curso']. ': '.$avaliacao['disciplina'].' ('.$avaliacao['turno'].')</a><br>';
			}
		}
		if(is_array($avaliacoes_coord))
		{
			foreach ($avaliacoes_coord as $avaliacao_coord) {
				$surveys[] = $avaliacao_coord['turma'].$avaliacao_coord['codigo'];
				$info[$avaliacao_coord['turma'].$avaliacao_coord['codigo']] = "Curso: " . $avaliacao_coord['curso'] . "<br>Disciplina: " . $avaliacao_coord['disciplina'] ."<br>Professor: ".$avaliacao_coord['professor']. "<br>Turma/Código: " . $avaliacao_coord['turma']. "/". $avaliacao_coord['codigo'];
				$link_avaliacao_coord[] = '<a href="'.api_get_self().'?view=report&survey='.$avaliacao_coord['turma'].$avaliacao_coord['codigo'].'">'.$avaliacao_coord['curso']. ': '.$avaliacao_coord['disciplina'].' ('.$avaliacao_coord['turno'].')</a><br>'; 
			}
		}

		echo '<div id="columns">';
		if(empty($survey_code) || !in_array($survey_code, $surveys)){
			$survey_code = $surveys[0];
		} 
				
		$survey_questions = AvaliacaoInstitucional::report_get_list_of_questions($survey_code);
			// blocks for column 2
			if ( (count($survey_questions) > 0) OR (count($link_avaliacao_coord) > 1)) {
				// blocks for column 1
				echo '<ul id="column1" class="column">';
					if(count($link_avaliacao) > 0)
					{
						Display::display_warning_message("Disciplinas de sua responsabilidade:", false);
					}
					foreach ($link_avaliacao as $content) {
						echo $content;
					}
				if(count($link_avaliacao_coord) > 1)
				{
					Display::display_warning_message("Disciplinas de seus professores:", false);
					foreach ($link_avaliacao_coord as $content_coord) {
						echo $content_coord;
					}					
				}
				echo '</ul>';
			} else {
				echo '<ul id="column1" class="column">';
				echo '&nbsp;';
				echo '</ul>';
			}

		if (count($survey_questions) > 0) {
				echo '<ul id="column2" class="column">';
				$count = 0;
				
				Display::display_normal_message($info[$survey_code], false);
				foreach ($survey_questions as $survey_question) {
				
					Display::display_warning_message($survey_question['survey_question'], false);
					
					if($survey_question['type'] == 'score')
					{
						$option = AvaliacaoInstitucional::report_get_list_of_questions_options_score($survey_question['c_id'], $survey_question['survey_id'], $survey_question['question_id']);
						foreach ($option as $op) {
							$dados = $op['text'] . "<br>";
							
							// @TODO: remover POG htmlHeadXtra
							$htmlHeadXtra = AvaliacaoInstitucional::display_graph_pizza_score($op['score'], $count);
							foreach ($htmlHeadXtra as $value) {
								echo $value;
							}
							$dados .= Display::page_subheader3("Distribuição:").'<div id="chart'.$count.'"></div>';
							Display::display_normal_message($dados, false);
							$count++;
						}
					}
					if($survey_question['type'] == 'open')
					{
						$respostas = AvaliacaoInstitucional::report_get_list_of_open_answered($survey_question['c_id'], $survey_question['survey_id'], $survey_question['question_id']);
						
						foreach ($respostas as $v) {
							Display::display_normal_message($v['opiniao'], false);
						}
					}
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
