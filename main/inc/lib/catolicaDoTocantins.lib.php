<?php
/* For licensing terms, see /license.txt */
/**
 * This file contains a class used like library provides functions for
 * Faculdade Catolica do Tocantins
 * @author Marco Antonio Firmino de Sousa <marco.volare@gmail.com>
 * @package chamilo.catolicaDoTocantins
 */
/**
 * Code
 */
require_once (dirname(__FILE__) . '/course.lib.php');
require_once (dirname(__FILE__) . '/database.lib.php');
require_once (dirname(__FILE__) . '/usermanager.lib.php');
require_once (dirname(__FILE__) . '/groupmanager.lib.php');

define('SEMESTRE', '2013/1');

/**
 * @package chamilo.catolicaDoTocantins
 */
class CatolicaDoTocantins {
	/**
	 * Constructor
	 */
	public function __construct() {
	}

	public function ct_getCpfFromUsername($username) {
		$table = Database::get_main_table(TABLE_MAIN_USER);

		$sql = "SELECT official_code FROM " . $table . " WHERE username='" . $username . "'";
		$res = Database::query($sql);
		$user = mysql_fetch_array($res);
		return $user[official_code];
	}

	public function ct_getCpfFromUserid($user_id) {
		$table = Database::get_main_table(TABLE_MAIN_USER);

		$sql = "SELECT official_code FROM " . $table . " WHERE user_id='" . $user_id . "'";
		$res = Database::query($sql);
		$user = mysql_fetch_array($res);
		return $user[official_code];
	}

	public function ct_getUsernameFromCpf($cpf) {
		$table = Database::get_main_table(TABLE_MAIN_USER);

		$sql = "SELECT username FROM " . $table . " WHERE official_code='" . $cpf . "'";
		$res = Database::query($sql);
		$qtd = Database::num_rows($res);
		if ($qtd > 1)
			$qtd = 2;
		// vários usuários com mesmo cpf

		switch ($qtd) {
			case 0 :
				// Não cadastrado;
				return -1;
				break;
			case 1 :
				$user = mysql_fetch_array($res);
				return $user['username'];
				break;

			case 2 :
				// mais de um usuário com mesmo cpf
				return "-2 " . $cpf;
				break;
		}
	}

	/**
	 * Verifica se é um curso da grade curricular. Utiliza a tabela 'grade' no database teste.
	 */
	public function ct_isRegularCourse($course_id) {

		$table_grade_c = Database::get_main_table(TABLE_GRADE_CURRICULAR);
		$table_grade_a = Database::get_main_table(TABLE_GRADE_AGRUPAMENTO);
		// @TODO verificar códigos dos cursos de direito para remover o 'like' da clausula.
		$sql = "SELECT count(disciplinaCodigo) AS qtd FROM " . $table_grade_c . " WHERE disciplinaCodigo like '" . $course_id . "%';";
		//$sql = "SELECT count(disciplinaCodigo) AS qtd FROM ". $table_grade_c ." WHERE disciplinaCodigo = '".$course_id."'";
		$sql_result_c = Database::query($sql);
		$qtd_c = Database::fetch_array($sql_result_c);

		$sql = "SELECT count(codigoagrupamento) AS qtd FROM " . $table_grade_a . " WHERE codigoagrupamento = '" . $course_id . "'";
		$sql_result_a = Database::query($sql);
		$qtd_a = Database::fetch_array($sql_result_a);
		unset($sql_result_c);
		unset($sql_result_a);

		if ($qtd_c['qtd'] > 0 || $qtd_a['qtd'] > 0)
			return true;
		else
			return false;
	}


	/**
	 * Verifica se é um curso da grade curricular. Utiliza a tabela 'grade' no database teste.
	 */
	public function ct_getCursoNome($course_id) {

		$table_grade_c = Database::get_main_table(TABLE_GRADE_CURRICULAR);

		$sql = "SELECT cursoNome AS curso FROM " . $table_grade_c . " WHERE disciplinaCodigo like '" . $course_id . "%';";

		$sql_result_c = Database::query($sql);
		$nome_c = Database::fetch_array($sql_result_c);

		unset($sql_result_c);
		return $nome_c['curso'];
	}

	
	public function ct_removerCursoErrado($user_id) {
		if ($user_id == 284)
			echo "<pre>";
		$cpf = CatolicaDoTocantins::ct_getCpfFromUserid($user_id);
		$courses_matriculados = CatolicaDoTocantins::ct_getCodigosDisciplinasMatriculados($cpf);
		$courses_inscritos = CatolicaDoTocantins::ct_getCodigosDisciplinasInscritos($user_id);
		$courses_candidatos = array_diff($courses_inscritos, $courses_matriculados);
		$courses_list_incluir = array_diff($courses_matriculados, $courses_inscritos);

		if ($user_id == 284) {
			print_r($cpf);
			print_r($courses_matriculados);
			print_r($courses_inscritos);
			print_r($courses_candidatos);
			print_r($courses_list_incluir);
		}
		foreach ($courses_candidatos as $course_code) {
			if (CatolicaDoTocantins::ct_isRegularCourse($course_code))
				if (api_get_status_of_user_in_course($user_id, $course_code) == "5")
					CourseManager::unsubscribe_user($user_id, $course_code);
		}
		CatolicaDoTocantins::ct_incluirCursoCerto($user_id, $courses_list_incluir, false);
		if ($user_id == 284)
			echo "</pre>";
	}

	public function ct_incluirCursoCerto($user_id, $course_list, $like = false) {		
		foreach ($course_list as $course_code) {
			if($like)
				CourseManager::subscribe_user($user_id, $course_code, COURSEMANAGER);
			else
				CourseManager::subscribe_user($user_id, $course_code, STUDENT);
		}
	}

	public function ct_getCodigosDisciplinasMatriculados($cpf) {
		$table_matriculados = Database::get_main_table(TABLE_GRADE_MATRICULADOS);
		$table_grade_curricular = Database::get_main_table(TABLE_GRADE_CURRICULAR);
		$table_grade_agrupamento = Database::get_main_table(TABLE_GRADE_AGRUPAMENTO);
		// disciplinas sem agrupamentos
		$sql = "SELECT DISTINCT IF(m.cursocodigo = '308' AND m.cursohorario = 'NOTURNO', CONCAT(c.disciplinacodigo,'N'), c.disciplinacodigo) AS codigo
                        FROM " . $table_grade_curricular . " as c 
                        INNER JOIN " . $table_matriculados . " as m
                        ON m.disciplinacodigo = c.disciplinacodigo
                        WHERE c.agrupamento = 0 
                        AND m.semestre = '".SEMESTRE."'
                        AND m.cpf ='" . $cpf . "'";
		$sql_result = Database::query($sql);
		$return_codigos = array();
		while ($course_code = Database::fetch_array($sql_result)) {
			if(!CatolicaDoTocantins::ct_isAgrupada($course_code['codigo']))
				$return_codigos[] .= $course_code['codigo'];
		}
		$sql = "SELECT DISTINCT a.codigoagrupamento AS codigo
                        FROM " . $table_grade_agrupamento . " AS a
                        LEFT JOIN " . $table_matriculados . " AS m ON m.disciplinacodigo = a.disciplinacodigo
                        AND IF(a.disciplinaturma <> '', m.disciplinaturma = a.disciplinaturma, 1)
						AND m.semestre = '".SEMESTRE."'
                        WHERE m.cpf = '" . $cpf . "'"; 
		$sql_resultc = Database::query($sql);

		while ($course_code = Database::fetch_array($sql_resultc)) {
			$return_codigos[] .= $course_code['codigo'];
		}
		unset($sql_result);
		unset($sql_resultc);
		return $return_codigos;
	}

	private function ct_getCodigosDisciplinasInscritos($user_id) {
		$course_code = CourseManager::get_courses_list_by_user_id($user_id);
		$return_code = array();
		foreach ($course_code as $code) {
			$return_code[] .= $code[code];
		}
		return $return_code;
	}
	public function ct_syncCourseTeacher($user_id)
	{
		$cpf = CatolicaDoTocantins::ct_getCpfFromUserid($user_id);
		if(CatolicaDoTocantins::ct_isTeacherOnRegularCourse($cpf))
		{
			$courses_responsavel = CatolicaDoTocantins::ct_getCodigosDisciplinasResponsavel($cpf);
			$courses_inscritos = CatolicaDoTocantins::ct_getCodigosDisciplinasInscritos($user_id);
			$courses_candidatos_a_remocao = array_diff($courses_inscritos, $courses_responsavel);
			$courses_list_incluir = array_diff($courses_responsavel, $courses_inscritos);

			foreach ($courses_candidatos_a_remocao as $course_code) {
				if (CatolicaDoTocantins::ct_isRegularCourse($course_code))
					if (api_get_status_of_user_in_course($user_id, $course_code) == "1")
						CourseManager::unsubscribe_user($user_id, $course_code);
			}

			CatolicaDoTocantins::ct_incluirCursoCerto($user_id, $courses_list_incluir, true);
			
			// inclusao de todas as coordenacoes que faz parte.
			$coordenacoes = CatolicaDoTocantins::ct_getCoordenacoes($cpf);
			CatolicaDoTocantins::ct_incluirCursoCerto($user_id, $coordenacoes, false);
			CatolicaDoTocantins::ct_syncGruposProfessores($user_id, $coordenacoes);
		}
	}
	private function ct_isTeacherOnRegularCourse($cpf)
	{
		$table_grade_professores = Database::get_main_table(TABLE_GRADE_PROFESSORES);
		$sql = "SELECT count(CourseCode) AS qtd FROM ".$table_grade_professores." WHERE TeacherOfficialCode = '".$cpf."' AND semestre = '".SEMESTRE."'";

		$sql_result = Database::query($sql);
		$qtd = Database::fetch_array($sql_result);
		if($qtd['qtd'] > 0)
		{
			return true;
		} else {
			return false;
		}
	}
	private function ct_getCodigosDisciplinasResponsavel($cpf)
	{
		$table_grade_professores = Database::get_main_table(TABLE_GRADE_PROFESSORES);
		$table_grade_agrupamento = Database::get_main_table(TABLE_GRADE_AGRUPAMENTO);
		// disciplinas sem agrupamentos
		$sql = "SELECT DISTINCT IF(m.Course = '308' AND m.CourseHorario = 'NOTURNO', CONCAT(m.CourseCode,'N'), m.CourseCode) AS codigo
                        FROM " . $table_grade_professores . " as m 
                        WHERE m.semestre = '".SEMESTRE."'
                        AND m.TeacherOfficialCode ='" . $cpf . "'";

		$sql_result = Database::query($sql);
		$return_codigos = array();
		while ($course_code = Database::fetch_array($sql_result)) {
			if(!CatolicaDoTocantins::ct_isAgrupada($course_code['codigo'])) // disciplina nao agrupada sera incluida
				$return_codigos[] .= $course_code['codigo'];
		}

		$sql = "SELECT DISTINCT a.codigoagrupamento AS codigo
                        FROM " . $table_grade_agrupamento . " AS a
                        LEFT JOIN " . $table_grade_professores . " AS m ON m.CourseCode = a.disciplinacodigo
                        AND IF(a.disciplinaturma <> '', m.CourseTurma = a.disciplinaturma, 1)
						AND m.semestre = '".SEMESTRE."'
                        WHERE m.TeacherOfficialCode = '" . $cpf . "'"; 
		$sql_resultc = Database::query($sql);
		
		while ($course_code = Database::fetch_array($sql_resultc)) {
			$return_codigos[] .= $course_code['codigo'];
		}
		
		unset($sql_result);
		unset($sql_resultc);
		return $return_codigos;
	}
	/*
	 * Retorna a lista de coordenações que o professor faz parte.
	 */
	 
	 private function ct_getCoordenacoes($cpf)
	 {
		// Inclusão NADIME e Coordenações.
		// disciplinas sem agrupamentos
		$table_grade_professores = Database::get_main_table(TABLE_GRADE_PROFESSORES);
		
		$sql = "SELECT DISTINCT CONCAT('CC', m.Course) as codigo
                        FROM " . $table_grade_professores . " as m 
                        WHERE m.semestre = '".SEMESTRE."'
                        AND m.TeacherOfficialCode ='" . $cpf . "'";

		$sql_result = Database::query($sql);
		$return_codigos = array();
		while ($course_code = Database::fetch_array($sql_result)) {
			if(!CatolicaDoTocantins::ct_isAgrupada($course_code['codigo'])) // disciplina nao agrupada sera incluida
				$return_codigos[] .= $course_code['codigo'];
		}
		$return_codigos[].= "NADIME";
		
		unset($sql_result);
		return $return_codigos;
		// Fim da inclusão NADIME e Coordenações

	 } 

	/*
	 * Retorna a lista de coordenações que o aluno faz parte.
	 */
	 
	 public function ct_getCoordenacoesAluno($cpf)
	 {
		// Inclusão Coordenações.
		// disciplinas sem agrupamentos
		$table_grade_matriculados = Database::get_main_table(TABLE_GRADE_MATRICULADOS);
		$sql = "SELECT DISTINCT CONCAT('CC', m.cursocodigo) as codigo
                        FROM ". $table_grade_matriculados ." as m 
                        WHERE m.semestre = '".SEMESTRE."'
                        AND m.cpf ='".$cpf."'";
								
		$sql_result = Database::query($sql);
		$return_codigos = array();
		while ($course_code = Database::fetch_array($sql_result)) {
			if(!CatolicaDoTocantins::ct_isAgrupada($course_code['codigo'])) // disciplina nao agrupada sera incluida
				$return_codigos[] .= $course_code['codigo'];
		}
		
		unset($sql_result);
		return $return_codigos;
		// Fim da inclusão das Coordenações

	 } 


	/*
	 * Verifica se a disciplina matriculada foi agrupada. Em caso positivo, a disciplina nao sera incluida
	 * nas opcoes de inclusao.
	 */
	public function ct_isAgrupada($course_code)
	{
		$table_grade_agrupamento = Database::get_main_table(TABLE_GRADE_AGRUPAMENTO);
		$sql = "SELECT count(id) AS qtd FROM ".$table_grade_agrupamento." WHERE disciplinacodigo = '".$course_code."'";

		$sql_result = Database::query($sql);
		$qtd = Database::fetch_array($sql_result);
		if($qtd['qtd'] > 0)
		{
			return true;
		} else {
			return false;
		}		
	}
	
	/*
	 * Inclusao do professor a todos os grupos de professores das coordenacoes que faz parte.
	 */
	private function ct_syncGruposProfessores($user_id, $coordenacoes)
	{
		foreach ($coordenacoes as $value)
		{
			$course_info  = CourseManager::get_course_information($value);				
			$course_id = $course_info['id'];
			
			// pega o id da categoria para incluir o grupo de professores se nao existir.
			$categorias = GroupManager::get_categories($value);
			$categoria = 0;
			foreach ($categorias as $cat) {
				if($categoria == 0)
					if($cat['title'] == "Grupos predefinidos")
						$categoria = $cat['id'];
				if($cat['title'] == "Professores")
					$categoria = $cat['id'];
			}
			
			// pega grupo da categoria utilizada.
			$grupos = GroupManager::get_group_list($categoria, $value);
			$grupo = 0;
			
			foreach ($grupos as $gp) {
				if($gp['name'] == "Professores")
					$grupo = $gp['id']; 
			}

			if( empty($grupos) || ($grupo == 0 && !empty($grupos)) ) // se nao tem grupo, cria um.
			{
				$tutores = CourseManager::get_teacher_list_from_course_code($value);
				if(is_array($tutores) && !empty($tutores))
				{
					$user_ids = array();
					foreach ($tutores as $tutor) {
						$user_ids[] = $tutor['user_id'];
					}
					$grupo = CatolicaDoTocantins::ct_create_group($value, "Professores", $categoria, $user_ids[1], 0);
					if(count($userids) > 1)
					{
						array_shift($user_ids);
						CatolicaDoTocantins::ct_subscribe_tutors($user_ids, $grupo, $course_id);
					}
				}
			} 
			
			if($grupo != 0)
			{
				CatolicaDoTocantins::ct_subscribe_users($user_id, $grupo, $course_id);
			}
		}
	}

	private function ct_create_group($course, $name, $category_id, $tutor, $places)
	{
        $table_group = Database :: get_course_table(TABLE_GROUP);

        $session_id = api_get_session_id();
        $course_info  = CourseManager::get_course_information($course);
		
		$course_id = $course_info['id'];
       	$currentCourseRepository = api_get_path(SYS_COURSE_PATH).$course_info['directory']; // $_course['path'];
		$category = GroupManager::get_category($category_id, $course);

		if (intval($places) == 0) {
			//if the amount of users per group is not filled in, use the setting from the category
			$places = $category['max_student'] + 30;
		} else {
            if ($places > $category['max_student'] && $category['max_student'] != 0) {
                $places = $category['max_student'];
            }
        }

		 $sql = "INSERT INTO ".$table_group." SET
				c_id = $course_id ,
				category_id='".Database::escape_string($category_id)."',
				max_student = '".$places."',
				doc_state = '".$category['doc_state']."',
				calendar_state = '".$category['calendar_state']."',
				work_state = '".$category['work_state']."',
				announcements_state = '".$category['announcements_state']."',
				forum_state = '".$category['forum_state']."',
				wiki_state = '".$category['wiki_state']."',
				chat_state = '".$category['chat_state']."',
				self_registration_allowed = '".$category['self_reg_allowed']."',
				self_unregistration_allowed = '".$category['self_unreg_allowed']."',
				session_id='".Database::escape_string($session_id)."'";

		Database::query($sql);
		$lastId = Database::insert_id();

        if ($lastId) {
            $desired_dir_name= '/'.replace_dangerous_char($name,'strict').'_groupdocs';
            $my_path = api_get_path(SYS_COURSE_PATH).$currentCourseRepository.'/document';
            $unique_name = create_unexisting_directory($_course, api_get_user_id(), $session_id, $lastId, NULL, $my_path, $desired_dir_name);

            /* Stores the directory path into the group table */
            $sql = "UPDATE ".$table_group." SET name = '".Database::escape_string($name)."', secret_directory = '".$unique_name."'
                    WHERE c_id = $course_id AND id ='".$lastId."'";

            Database::query($sql);

            // create a forum if needed
            if ($category['forum_state'] >= 0) {
                require_once api_get_path(SYS_CODE_PATH).'forum/forumconfig.inc.php';
                require_once api_get_path(SYS_CODE_PATH).'forum/forumfunction.inc.php';

                $forum_categories = get_forum_categories();

                $values = array();
                $values['forum_title'] = $name;
                $values['group_id'] = $lastId;

                $counter = 0;
                foreach ($forum_categories as $key=>$value) {
                    if ($counter==0) {
                        $forum_category_id = $key;
                    }
                    $counter++;
                }
                // A sanity check.
                if (empty($forum_category_id)) {
                    $forum_category_id = 0;
                }
                $values['forum_category'] = $forum_category_id;
                $values['allow_anonymous_group']['allow_anonymous'] = 0;
                $values['students_can_edit_group']['students_can_edit'] = 0;
                $values['approval_direct_group']['approval_direct'] = 0;
                $values['allow_attachments_group']['allow_attachments'] = 1;
                $values['allow_new_threads_group']['allow_new_threads'] = 1;
                $values['default_view_type_group']['default_view_type']=api_get_setting('default_forum_view');
                $values['group_forum'] = $lastId;
                if ($category['forum_state'] == '1') {
                    $values['public_private_group_forum_group']['public_private_group_forum']='public';
                } elseif  ($category['forum_state'] == '2') {
                    $values['public_private_group_forum_group']['public_private_group_forum']='private';
                } elseif  ($category['forum_state'] == '0') {
                    $values['public_private_group_forum_group']['public_private_group_forum']='unavailable';
                }
                store_forum($values);
            }
        }

		return $lastId;
	}
	public function ct_subscribe_users($user_ids, $group_id, $course_id)
	{
		$user_ids = is_array($user_ids) ? $user_ids : array ($user_ids);
		$result = true;
		
		$table_group_user = Database :: get_course_table(TABLE_GROUP_USER);
		if (!empty($user_ids)) {
			foreach ($user_ids as $index => $user_id) {
				$user_id = Database::escape_string($user_id);
				$group_id = Database::escape_string($group_id);
				$sql = "INSERT INTO ".$table_group_user." (c_id, user_id, group_id) VALUES ('$course_id', '".$user_id."', '".$group_id."')"; 
				$result &= Database::query($sql);
			}
		}
		return $result;

	}
	public static function ct_subscribe_tutors ($user_ids, $group_id, $course_id) {
		$user_ids = is_array($user_ids) ? $user_ids : array ($user_ids);
		$result = true;
		
		$table_group_tutor = Database :: get_course_table(TABLE_GROUP_TUTOR);

		foreach ($user_ids as $index => $user_id) {
			$user_id = Database::escape_string($user_id);
			$group_id = Database::escape_string($group_id);
			$sql = "INSERT INTO ".$table_group_tutor." (c_id, user_id, group_id) VALUES ('$course_id', '".$user_id."', '".$group_id."')";
			$result &= Database::query($sql);
		}
		return $result;
	}

	/*
	 * Adiciona o registro de uma tentativa falha de login.
	 * Armazena username e datetime.
	 * Impacto: database.constants.inc.php, local.inc.php
	 * Tabela:
	 * 
	   CREATE TABLE `track_e_login_failed` (
		  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `username` varchar(100) DEFAULT '',
		  `login_date` datetime DEFAULT '0000-00-00 00:00:00',
		  `login_ip` varchar(39) DEFAULT NULL,
		  `type_error` varchar(40) DEFAULT NULL,
		  PRIMARY KEY (`id`)
		) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=
	 */
	public static function set_login_failed($username, $type_error)
	{
		$table_login_failed = Database :: get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN_FAILED);
		$ip = api_get_real_ip();
		$username = Database::escape_string($username);
		$type_error = Database::escape_string($type_error);
		$sql = "INSERT INTO " . $table_login_failed . " (username, login_date, login_ip, type_error) VALUES ('" . $username . "', NOW(), '". $ip ."', '". $type_error ."')";
		Database::query($sql);
	}
	
	public function debug($valor, $parar = false)
	{
		echo "<pre>";
		print_r($valor);
		echo "</pre>";
		if($parar)
			exit();
	}	
}
