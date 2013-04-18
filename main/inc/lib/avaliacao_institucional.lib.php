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
require_once api_get_path(SYS_CODE_PATH).'survey2/survey.lib.php';

define('ENQUETEALUNO', "20131aluno");
define('ENQUETEPROFESSOR', "20131professores");
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

	public function isActiveStudent($cpf)
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
		
		$sql = "SELECT c.disciplinaCodigo as codigo, p.CourseTurma as turma, c.disciplinaNome as disciplina, c.cursoNome as curso, p.TeacherName as professor FROM
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
	
	public function isActiveTeacher($cpf)
	{
		return CatolicaDoTocantins::ct_isTeacherOnRegularCourse($cpf);
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

	public function display_avaliacao_help($user_id)
	{
		echo '<div style="margin-top:20px">';
		echo '<div><strong>'.get_lang('HelpAvaliacaoInstitucionalTitulo').'</strong></div><br />';
		echo '<form name="dashboard_list" method="post" action="index.php?action=help_done">';
		echo 'help';
		echo '<br />';
		if(!AvaliacaoInstitucional::isViewedHelp($user_id))
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
					$new_question = survey_manager::save_question($structure, $question, $course_id);	
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

 }
