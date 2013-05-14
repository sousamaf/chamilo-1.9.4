<?php
/* For licensing terms, see /license.txt */
/**
 * This file contains a class used like library provides functions for
 * Avaliacao Institucional da Faculdade Catolica do Tocantins
 * @author Marco Antonio Firmino de Sousa <marco.volare@gmail.com>
 * @package chamilo.avaliacao_institucional
 */
require_once (dirname(__FILE__) . '/database.lib.php'); 
require_once (dirname(__FILE__) . '/usermanager.lib.php'); 
require_once api_get_path(LIBRARY_PATH).'survey.lib.php';
//require_once api_get_path(SYS_CODE_PATH).'survey2/survey2.lib.php';

define('ENQUETEALUNO', "20131aluno");
define('ENQUETEPROFESSOR', "20131professor");
define('ENQUETEALUNOPROFESSOR',"8"); // survey_id

/*
 * Tabela de confirmacao de visualizacao do help
 CREATE TABLE `track_e_avaliacao_help` (
  `help_id` int(11) NOT NULL AUTO_INCREMENT,
  `help_user_id` int(10) unsigned DEFAULT NULL,
  `help_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `help_semestre` varchar(8) DEFAULT NULL,
  PRIMARY KEY (`help_id`),
  KEY `help_user_id` (`help_user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=8492 DEFAULT CHARSET=utf8;
 * 
 * Alterar a tabela grade_curricular para criar o campo estagio, sendo 0 para disciplina regular
 * e 1 para estagios ou TCC.
 */
 
 class AvaliacaoInstitucional {
	/**
	 * Constructor
	 */
	public function __construct(){}

	public static function isActiveStudent($cpf)
	{
		// @TODO: filtrar disciplinas de estagio e TCC.
		$table_grade_matriculados = Database::get_main_table(TABLE_GRADE_MATRICULADOS);
		$sql = "SELECT count(disciplinacodigo) AS qtd FROM ".$table_grade_matriculados." WHERE cpf = '".$cpf."' AND semestre = '".SEMESTRE."'";

		$sql_result = Database::query($sql);
		$qtd = Database::fetch_array($sql_result);
		if($qtd['qtd'] > 0)
		{
			return true;
		} else {
			return false;
		}
	}

	public function getCoursesOfTeacher($cpf)
	{
		$table_grade_matriculados = Database::get_main_table(TABLE_GRADE_MATRICULADOS);
		$table_grade_curricular = Database::get_main_table(TABLE_GRADE_CURRICULAR);
		$table_grade_professores = Database::get_main_table(TABLE_GRADE_PROFESSORES);
		
		$sql = "SELECT c.disciplinaCodigo as codigo, p.CourseTurma as turma, c.disciplinaNome as disciplina, p.CourseHorario as turno, c.cursoNome as curso, p.TeacherName as professor FROM
				".$table_grade_matriculados." as m, 
				".$table_grade_curricular." as c,
				".$table_grade_professores." as p
				WHERE p.TeacherOfficialCode = '".$cpf."'
				and m.disciplinacodigo = c.disciplinaCodigo
				and p.CourseCode = c.disciplinaCodigo
				and m.disciplinaturma = p.CourseTurma
				and m.semestre = '".SEMESTRE."'
				and c.estagio = 0
				GROUP BY p.CourseTurma 
				ORDER BY c.disciplinaCodigo"; 
		$sql_result = Database::query($sql);
		$dados = array();
		while($dado = Database::fetch_array($sql_result))
		{
			$d['codigo'] = $dado['codigo'];
			$d['turma'] = $dado['turma'];
			$d['disciplina'] = $dado['disciplina'];
			$d['turno'] = $dado['turno'];
			$d['curso'] = $dado['curso'];
			$d['professor'] = $dado['professor'];
			
			$dados[] = $d;
		}
		return $dados;
	}

	public function getCoursesOfResponsaveis($course)
	{
		$table_grade_matriculados = Database::get_main_table(TABLE_GRADE_MATRICULADOS);
		$table_grade_curricular = Database::get_main_table(TABLE_GRADE_CURRICULAR);
		$table_grade_professores = Database::get_main_table(TABLE_GRADE_PROFESSORES);
		
		$condicao = "";
		if(is_array($course))
		{
			if(in_array("ALL", $course))
			{
				$condicao = " 1 ";
			} else {
				$condicao = "p.Course IN ('".implode("','",$course)."')";
			}
		}
		
		$sql = "SELECT c.disciplinaCodigo as codigo, p.CourseTurma as turma, c.disciplinaNome as disciplina, c.cursoNome as curso, p.CourseHorario as turno, p.TeacherName as professor FROM
				".$table_grade_matriculados." as m, 
				".$table_grade_curricular." as c,
				".$table_grade_professores." as p
				WHERE $condicao
				and m.disciplinacodigo = c.disciplinaCodigo
				and p.CourseCode = c.disciplinaCodigo
				and m.disciplinaturma = p.CourseTurma
				and m.semestre = '".SEMESTRE."'
				and c.estagio = 0
				GROUP BY (c.disciplinaCodigo + p.CourseTurma) 
				ORDER BY p.CourseTurma, c.disciplinaCodigo"; 
				
		$sql_result = Database::query($sql);
		$dados = array();
		while($dado = Database::fetch_array($sql_result))
		{
			$d['codigo'] = $dado['codigo'];
			$d['turma'] = $dado['turma'];
			$d['disciplina'] = $dado['disciplina'];
			$d['turno'] = $dado['turno'];
			$d['curso'] = $dado['curso'];
			$d['professor'] = $dado['professor'];
			
			$dados[] = $d;
		}
		return $dados;
	}


	public function getStudentsTeacher($cpf)
	{
		$table_grade_matriculados = Database::get_main_table(TABLE_GRADE_MATRICULADOS);
		$table_grade_curricular = Database::get_main_table(TABLE_GRADE_CURRICULAR);
		$table_grade_professores = Database::get_main_table(TABLE_GRADE_PROFESSORES);
		
		$sql = "SELECT c.disciplinaCodigo as codigo, p.CourseTurma as turma, c.disciplinaNome as disciplina, c.cursoNome as curso, p.TeacherName as professor FROM
				".$table_grade_matriculados." as m, 
				".$table_grade_curricular." as c,
				".$table_grade_professores." as p
				WHERE cpf = '".$cpf."'
				and m.disciplinacodigo = c.disciplinaCodigo
				and p.CourseCode = c.disciplinaCodigo
				and m.disciplinaturma = p.CourseTurma
				and m.semestre = '".SEMESTRE."'
				and c.estagio = 0";
		$sql_result = Database::query($sql);
		$dados = array();
		while($dado = Database::fetch_array($sql_result))
		{
			$d['codigo'] = $dado['codigo'];
			$d['turma'] = $dado['turma'];
			$d['disciplina'] = $dado['disciplina'];
			$d['curso'] = $dado['curso'];
			$d['professor'] = $dado['professor'];
			
			$dados[] = $d;
		}
		return $dados;
	}

	public function getEnqueteInfo($cpf, $enquete)
	{
		$table_grade_matriculados = Database::get_main_table(TABLE_GRADE_MATRICULADOS);
		$table_grade_curricular = Database::get_main_table(TABLE_GRADE_CURRICULAR);
		$table_grade_professores = Database::get_main_table(TABLE_GRADE_PROFESSORES);
		
		$sql = "SELECT c.disciplinaCodigo as codigo, p.CourseTurma as turma, c.disciplinaNome as disciplina, p.CourseHorario as turno, c.cursoNome as curso, p.TeacherName as professor FROM
				".$table_grade_matriculados." as m, 
				".$table_grade_curricular." as c,
				".$table_grade_professores." as p
				WHERE cpf = '".$cpf."'
				and m.disciplinacodigo = c.disciplinaCodigo
				and p.CourseCode = c.disciplinaCodigo
				and m.disciplinaturma = p.CourseTurma
				and m.semestre = '".SEMESTRE."'
				and c.estagio = 0
				and CONCAT(p.CourseTurma, c.disciplinacodigo) = '".$enquete."'";
		$sql_result = Database::query($sql);
		$dado = Database::fetch_array($sql_result);
		return $dado;
	}
	
	public function getLegenda($num)
	{
		switch ($num) {
			case '1':
				return "Muito Insatisfeito";
				break;
			case '2':
				return "Insatisfeito";
				break;
			case '3':
				return "Pouco Satisfeito";
				break;
			case '4':
				return "Satisfeito";
				break;
			case '5':
				return "Muito Satisfeito";
				break;
		}
	}
	
	public function isActiveTeacher($cpf)
	{
		return CatolicaDoTocantins::ct_isTeacherOnRegularCourse($cpf);
	}
	
	public function getCoordenacoesResponsaveis($user_id)
	{
		$coordenacoes = CatolicaDoTocantins::ct_getAllCoordenacoes();
		$coord = array();
		foreach ($coordenacoes as $value) {
			if(CourseManager::is_course_teacher($user_id, $value['codigo']))
			{
				$coord[] = $value['curso'];
			}
		}
		$avaliacoes = self::getCoursesOfResponsaveis($coord);
		return $avaliacoes; 
	}
		
	public function isViewedHelp($user_id)
	{
		$table_avaliacao_help = Database::get_main_table(TABLE_STATISTIC_TRACK_E_AVALIACAO_HELP);
		$sql = "SELECT count(help_id) AS qtd FROM ".$table_avaliacao_help." WHERE help_user_id = '".$user_id."' AND help_semestre = '".SEMESTRE."'";

		$sql_result = Database::query($sql);
		$qtd = Database::fetch_array($sql_result);
		if($qtd['qtd'] > 0)
		{
			return true;
		} else {
			return false;
		}
	}
	public function setViewedHelp($user_id)
	{
		$table_avaliacao_help = Database::get_main_table(TABLE_STATISTIC_TRACK_E_AVALIACAO_HELP);
		
		$user_id = Database::escape_string($user_id);
		
		$sql = "INSERT INTO " . $table_avaliacao_help . " (help_user_id, help_date, help_semestre) VALUES ('" . $user_id . "', NOW(), '". SEMESTRE ."')";
		
		Database::query($sql);

	}

	public function isSurveyDone($user_id, $survey_code)
	{
		$table_survey_invitation = Database::get_course_table(TABLE_SURVEY_INVITATION);
		$sql = "SELECT 1 FROM ".$table_survey_invitation." WHERE user = '".$user_id."' AND survey_code = '".$survey_code."' AND answered = 1";
		$sql_result = Database::query($sql);
		if (Database::num_rows($sql_result) > 0)
		{
			return true;
		} else {
			return false;
		}

	}

	public function isAllSurveyDone($user_id, $cpf)
	{
		$enquetes = self::getStudentsTeacher($cpf);
		$table_survey_invitation = Database::get_course_table(TABLE_SURVEY_INVITATION);
		
		foreach ($enquetes as $v) {
			$sql = "SELECT 1 FROM ".$table_survey_invitation." WHERE user = '".$user_id."' AND survey_code = '".$v['turma'].$v['codigo']."' AND answered = 1";
			$sql_result = Database::query($sql);
			if(Database::num_rows($sql_result) < 1)
				return false;
		}
		return true;
	}
	
	public function report_get_list_of_questions($survey_code)
	{
		$table_survey = Database::get_course_table(TABLE_SURVEY);
		$table_survey_question = Database::get_course_table(TABLE_SURVEY_QUESTION);
		
		$sql = "SELECT survey.c_id, survey.survey_id, question.question_id, question.survey_question, question.type FROM ".$table_survey." as survey, ".$table_survey_question." as question WHERE code = '".$survey_code."'
			AND question.type <> 'comment'
			AND survey.c_id = question.c_id
			AND survey.survey_id = question.survey_id";
		$sql_result = Database::query($sql);
		$dados = array();
		while($dado = Database::fetch_array($sql_result))
		{
			$d['c_id'] = $dado['c_id'];
			$d['survey_id'] = $dado['survey_id'];
			$d['question_id'] = $dado['question_id'];
			$d['survey_question'] = $dado['survey_question'];
			$d['type'] = $dado['type'];
			
			$dados[] = $d;
		}
		return $dados;
	}

	public function report_get_list_of_questions_options_score($course_id, $survey_id, $question_id)
	{
		$table_survey_question_option = Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION);
		$table_survey_answer = Database::get_course_table(TABLE_SURVEY_ANSWER);
		
		$sql = "SELECT q_option.question_option_id as o_id, q_option.option_text as text FROM $table_survey_question_option as q_option 
				WHERE q_option.c_id = $course_id
				AND q_option.survey_id = $survey_id
				AND q_option.question_id = $question_id";  
		$sql_result = Database::query($sql);
		$dados = array();
		while($dado = Database::fetch_array($sql_result))
		{
			$d['o_id'] = $dado['o_id'];
			$d['text'] = $dado['text'];
			$sql2 = "SELECT a.option_id, a.value, count(a.value) as qtd FROM $table_survey_answer as a
					WHERE a.c_id = $course_id
					AND a.survey_id = $survey_id
					AND a.question_id = $question_id
					AND a.option_id = " . $dado['o_id'] ."
					group by a.option_id, a.value"; 
			$sql_result2 = Database::query($sql2);
			$score = array();
			while($s = Database::fetch_array($sql_result2))
			{
				$score_option['value'] = $s['value'];
				$score_option['qtd'] = $s['qtd'];
				$score[] = $score_option;
			}
			$d['score'] = $score;
			$dados[] = $d;
		} 
		return $dados;
			
	}

	public function report_get_list_of_open_answered($course_id, $survey_id, $question_id)
	{
		$table_survey_answer = Database::get_course_table(TABLE_SURVEY_ANSWER);
		
		$sql = "SELECT a.option_id as answered FROM $table_survey_answer as a
				WHERE a.c_id = $course_id
				AND a.survey_id = $survey_id
				AND a.question_id = $question_id
				AND a.option_id <> ''"; 
		$sql_result = Database::query($sql);
		$dados = array();
		while($dado = Database::fetch_array($sql_result))
		{
			$d['opiniao'] = $dado['answered'];
			$dados[] = $d;
		} 
		return $dados;
	}

	public function report_get_qtd_unsurvey($curso)
	{
		$table_grade_matriculados = Database::get_main_table(TABLE_GRADE_MATRICULADOS);
		$table_main_user = Database::get_main_table(TABLE_MAIN_USER);
		$sql = "SELECT m.nome, m.matricula, m.cpf, u.user_id FROM ".$table_grade_matriculados." as m, ".$table_main_user." as u where m.semestre = '".SEMESTRE."'
			AND m.cursocodigo = '".$curso."'
			AND m.cpf = u.official_code
			group by u.user_id
			order by m.nome";

		$sql_result = Database::query($sql);
		$dados = array();
		while($dado = Database::fetch_array($sql_result))
		{
			if(!self::isAllSurveyDone($dado['user_id'], $dado['cpf']))
			{
				$d['user_id'] = $dado['user_id'];
				$d['cpf'] = $dado['cpf'];
				$d['matricula'] = $dado['matricula'];
				$d['nome'] = $dado['nome'];
				
				$dados[] = $d;
			}
		}
		return $dados;		
	}

	public function display_avaliacao_help($user_id)
	{
		$cpf = CatolicaDoTocantins::ct_getCpfFromUserid($user_id);
		echo '<div style="margin-top:20px">';
		echo '<div><strong>'.get_lang('HelpAvaliacaoInstitucionalTitulo').'</strong></div><br />';
		echo '<form name="dashboard_list" method="post" action="index.php?action=help_done">';
		if(self::isActiveTeacher($cpf)){
			echo get_lang('HelpAvaliacaoInstitucionalMensagemDocente');
		}
		else {
			echo get_lang('HelpAvaliacaoInstitucionalMensagemDiscente');
		}
		echo '<br />';
		if(!self::isViewedHelp($user_id))
			echo '<button class="save" type="submit" name="submit_dashboard_list" value="'.get_lang('HelpConfirmarParticipacao').'">'.get_lang('HelpConfirmarParticipacao').'</button></form>';
		echo '</div>';
	}

	public function display_user_avaliacao_list($user_id)
	{
		$cpf = CatolicaDoTocantins::ct_getCpfFromUserid($user_id);
		
		if(self::isActiveStudent($cpf))
			self::display_user_avaliacao_aluno($user_id);
		if(self::isActiveTeacher($cpf))
			self::display_user_avaliacao_professor($user_id); 
		if(self::isActiveStudent($cpf) && self::isSurveyDone($user_id, ENQUETEALUNO))
			self::display_professores_do_aluno($cpf, $user_id);
	}
	
	public function display_user_avaliacao_aluno($user_id)
	{
		$message = "Autoavaliação:  "; 
		if(self::isSurveyDone($user_id, ENQUETEALUNO)){
			$message .= "realizada.";
		} else {
			$message.= '<a href='.api_get_path(WEB_CODE_PATH).'survey2/fillsurvey.php?course=CPA&invitationcode=auto&scode='.ENQUETEALUNO.'>'.get_lang('ListaAutoAvaliacaoAluno').'</a>';
		}
		echo Display::return_message($message, 'warning', false);
	}
	public function display_user_avaliacao_professor($user_id)
	{
		$message = "Após a realização de sua autoavaliação você poderá acompanhar o que seus alunos pensam de sua aula. Participe: ";
		if(self::isSurveyDone($user_id, ENQUETEPROFESSOR)){
			$message .= "Autoavaliação realizada.";
		} else { 
			$message.= '<a href='.api_get_path(WEB_CODE_PATH).'survey2/fillsurvey.php?course=CPA&invitationcode=auto&scode='.ENQUETEPROFESSOR.'>'.get_lang('ListaAutoAvaliacaoProfessor').'</a>';
		}
		echo Display::return_message($message, 'warning', false);
	}
	public function display_professores_do_aluno($cpf, $user_id)
	{
		$dados = self::getStudentsTeacher($cpf);
		$message = "Avaliação docente: <br>";
		$surveys = array();
		$surveys_data = survey_manager::get_surveys('CPA');
		$allavailable = 1;
		foreach ($surveys_data as $value) {
			$surveys[] = $value['code'];
		}
		
		foreach ($dados as $v) {
			$enquete = $v['turma'].$v['codigo'];
			if(!in_array($enquete,$surveys))
			{
				
				$survey_id = ENQUETEALUNOPROFESSOR;
				
				$structure = survey_manager::get_survey($survey_id, 0, 'CPA');
				$course_id = $structure['c_id'];
				$structure['questions'] = survey_manager::get_questions($survey_id, $structure['c_id']);
				$structure['survey_code'] = $enquete;
				$structure['code'] = $v['turma'].$v['codigo'];
				$structure['survey_id'] = '';
				$structure['survey_title'].= "Professor: " . $v['professor']."<br>";
				$structure['survey_title'].= "Disciplina: " . $v['disciplina'];

				$new_data = survey_manager::store_survey($structure, $course_id);
				$structure['survey_id'] = $new_data['id'];
				foreach ($structure['questions'] as $question) {
					$question['question_id'] = ''; 
					$question['survey_id'] = $structure['survey_id'];
					$new_question = survey_manager::save_question($question, $structure, $course_id);	
				}
				
			}
			if(self::isSurveyDone($user_id, $v['turma'].$v['codigo'])){
				$message.= $v['disciplina'].' ('.$v['professor'].') | Código: '.$v['codigo'] . ' | Turma: '. $v['turma'].'<br>';
				$allavailable = 0;
			} else {
				$message.= '<a href='.api_get_path(WEB_CODE_PATH).'survey2/fillsurvey.php?course=CPA&invitationcode=auto&scode='.$enquete.'>'.$v['disciplina'].' ('.$v['professor'].') | Código: '.$v['codigo'] . ' | Turma: '. $v['turma'].'</a><br>';	
			}
				
		}
		if($allavailable && self::isSurveyDone($user_id, ENQUETEALUNO))
		{
			$orientacao_inicial = get_lang('ListOrientacaoInicial');
			echo Display::return_message($orientacao_inicial, 'error', false);
		}
		echo Display::return_message($message, 'warning', false);
	}

	public function display_report_teacher_courses($course_code)
	{
		$cpf = CatolicaDoTocantins::ct_getCpfFromUserid($user_id);
		echo '<div style="margin-top:20px">';
		echo '<div><strong>'.get_lang('HelpAvaliacaoInstitucionalTitulo').'</strong></div><br />';
		echo '<form name="dashboard_list" method="post" action="index.php?action=help_done">';
		if(self::isActiveTeacher($cpf)){
			echo get_lang('HelpAvaliacaoInstitucionalMensagemDocente');
		}
		else {
			echo get_lang('HelpAvaliacaoInstitucionalMensagemDiscente');
		}
		echo '<br />';
		if(!self::isViewedHelp($user_id))
			echo '<button class="save" type="submit" name="submit_dashboard_list" value="'.get_lang('HelpConfirmarParticipacao').'">'.get_lang('HelpConfirmarParticipacao').'</button></form>';
		echo '</div>';
	}

	public function display_graph_pizza_score($dados, $chart)
	{
		$htmlHeadXtra[] = api_get_js('jqplot/jquery.jqplot.min.js');
		$htmlHeadXtra[] = api_get_js('jqplot/plugins/jqplot.pieRenderer.min.js');
		$htmlHeadXtra[] = api_get_js('jqplot/plugins/jqplot.donutRenderer.min.js');
		$htmlHeadXtra[] = api_get_css(api_get_path(WEB_LIBRARY_PATH).'javascript/jqplot/jquery.jqplot.min.css');

		$session = array();
		$session_list = SessionManager::get_session_by_course($course_code);
		
		$total_participantes = 0;
		foreach ($dados as $d) {
			$total_participantes += $d['qtd'];
		}

		$legenda = array();
		for($i = 0; $i < 5; $i++) {
			$d['value'] = $i+1;
			$d['qtd'] = 0;
			$legenda[$i+1] = $d;
		}

		foreach ($dados as $d) {
			$quota_percentage = round($d['qtd']/$total_participantes, 2)*100;
			$legenda[$d['value']]['qtd'] = $quota_percentage;
		}

		for($i = 0; $i < 5; $i++) {
			$session[] = array(addslashes(self::getLegenda($i+1)), $legenda[$i+1]['qtd']);
		}
						
		$quota_data = json_encode($session);
		
		$htmlHeadXtra[] = "
		<script>
		$(document).ready(function(){
		  var data = ".$quota_data.";
		  var plot1 = jQuery.jqplot ('chart".$chart."', [data], {
		  	  title:'Participantes: ".$total_participantes."',
		      seriesDefaults: {
		        // Make this a pie chart
		        renderer: jQuery.jqplot.PieRenderer,
		        rendererOptions: {
		          // Put data labels on the pie slices.
		          // By default, labels show the percentage of the slice.
		          showDataLabels: true
		        }
		      },
		      legend: { show:true, location: 'e' }
		    }
		  );
		});
		</script>";
		
		return $htmlHeadXtra;
	}
 }
