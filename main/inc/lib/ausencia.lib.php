<?php

/* For licensing terms, see /license.txt */
/**
 * This is the class library for this application.
 * @package	 chamilo.library
 */
//include(dirname(__FILE__).'/../global.inc.php');

/**
 * Code
 */
class Ausencia {

    /**
     * Get class information
     * note: This function can't be named get_class() because that's a standard
     * php-function.
     */
    function add_ocorrencia($c_id, $a_id, $tipo, $user_id, $data_aula, $data_reposicao, $comentario) {
        $table_ocorrencia = Database :: get_main_table(TABLE_AUSENCIA);
        $c_id = intval($c_id);
        $a_id = intval($a_id);
        $user_id = intval($user_id);
        $register_time = api_get_utc_datetime();
        $comentario = Database::escape_string($comentario);
        
        $sql = "INSERT INTO $table_ocorrencia SET c_id = '". $c_id ."', a_id = '". $a_id ."', register_time = '". $register_time . "',tipo = '". $tipo ."', user_id = '" . $user_id . "', data_aula='" . $data_aula . "', data_reposicao = '". $data_reposicao . "', comentario = '". $comentario . "'";
        
        Database::query($sql);
        return Database::affected_rows() == 1;
    }
    
    /*
     * Verifica se é uma ocorrencia. 
     * Utilização no formulário.
     */
    function is_ocorrencia($tipo)
    {
        return $tipo != "selecione";
    }
    
    /*
     * Recuperar a lista de usuários observadores.
     */
    
    function get_usuarios_observadores($c_id)
    {
        
        $course_code = CourseManager::get_course_code_from_course_id($c_id);
        $table_grade_curricular = Database :: get_main_table(TABLE_GRADE_CURRICULAR);
        $table_grade_rel_agrupamento = Database :: get_main_table(TABLE_GRADE_REL_AGRUPAMENTO);
        $coordenacoes = Array();
        $usuarios_id = Array();
        // recupara a os responsaveis da coordenacao
        $sql = "SELECT coordenacao, agrupamento FROM ". $table_grade_curricular ." WHERE disciplinaCodigo ='".$course_code. "';";

        $res = Database::query($sql);
        if (Database::num_rows($res)) {
            $row = Database::fetch_array($res);
            $coordenacao = $row['coordenacao'];
            $users_id = CourseManager::get_teacher_list_from_course_code($coordenacao);
            foreach ($users_id as $user)
            {
                $usuarios_id[] .= "USER:".$user['user_id'];
            }
            
            // em caso de agrupamento deve-se buscar as outros possiveis observadores
            if($course_code == $row['agrupamento'])
            {
                $sql ="SELECT coordenacao, agrupamento FROM ". $table_grade_curricular ." WHERE disciplinaCodigo = (SELECT hospedada FROM ". $table_grade_rel_agrupamento ." WHERE hospedeira = '".$course_code."');";
                $rs = Database::query($sql);
                while ($row_a = Database::fetch_array($rs)) {
                    // no caso da juncao ser de disciplinas de mais de um curso
                    if($row_a['coordenacao'] != $coordenacao)
                    {
                        $coordenacoes[] .= $row_a['coordenacao'];
                        $users_id = CourseManager::get_teacher_list_from_course_code($row_a['coordenacao']);
                        foreach ($users_id as $user)
                        {
                            $usuarios_id[] .= "USER:".$user['user_id'];
                        }

                    }
                }
                $coordenacoes[] .= $coordenacao;
            }
            // buscar observadores das secretarias e direcao
            foreach ($coordenacoes as $coord)
            {   
                $table_ausencia_categoria = Database :: get_main_table(TABLE_AUSENCIA_CATEGORIA);
                $table_ausencia_rel_user = Database :: get_main_table(TABLE_AUSENCIA_REL_USER);
                $code = $coord;
                while(!empty($code))
                {
                    $sql = "SELECT id, parent_id FROM ". $table_ausencia_categoria ." WHERE code = '".$code."'";
                    
                    $categorias = Database::query($sql);
                    while ($row_cat = Database::fetch_array($categorias))
                    {
                        $sql = "SELECT id_usuario FROM ". $table_ausencia_rel_user ." WHERE id_categoria = ' " .$row_cat['id']."'";
                        $rs_observadores = Database::query($sql);
                        while($observadores = Database::fetch_array($rs_observadores))
                        {
                            $usuarios_id[] .= "USER:" . $observadores['id_usuario'];
                        }   
                        $code = $row_cat['parent_id'];
                        // @TODO: rever código $code. sua atribuicao nao esta em local adequado.
                    }
                }
            }
        }
        return $usuarios_id;
    }

    function get_num_total_ocorrencias()
    { 
        $c_id = api_get_course_int_id();
        $u_id = api_get_user_id();
        // @TODO: implementar contagem de ocorrencias consideranco contexto do usuario
        $sql = "SELECT count(id) as total_number_of_items FROM ausencia ";
        

	//	if (isset($_GET['keyword'])) {
	//		$keyword = Database::escape_string(trim($_GET['keyword']));
	//		$sql .= " AND (user.username LIKE '%".$keyword."%' OR lastedit_type LIKE '%".$keyword."%' OR tool LIKE '%".$keyword."%')";
	//	}

	//	$sql .= " AND tool IN ('document', 'learnpath', 'quiz', 'glossary', 'link', 'course_description', 'announcement', 'thematic', 'thematic_advance', 'thematic_plan')";
                
		$res = Database::query($sql);
		$obj = Database::fetch_object($res);
                
		return $obj->total_number_of_items;
    }
    
    function get_tipo_ocorrencia($ocorrencia)
    {
        // tipos existentes nos formularios de anuncio.
        switch ($ocorrencia)
        {
            case 'visitatecnica':
                return get_lang('ComunicadoDeAusenciaVisitaTecnica');
                break;
            case 'programada':
                return get_lang('ComunicadoDeAusenciaProgramadaShort');
                break;
            case 'naoprogramada':
                return get_lang('ComunicadoDeAusenciaNaoProgramadaShort');
                break;
            case 'naocomunicado':
                return get_lang('ComunicadoDeAusenciaSemContatoShort');
                break;
            default:
                return "Inexistente";
        }
        
    }

    
    function get_data_total_ocorrencia($from, $number_of_items, $column, $direction)
    {
        // @TODO: verificar se o limit é aplicável neste caso. Como será o comportamento quando houver muitas ocorrências.
        $dados = array();
        $table_ausencia = Database :: get_main_table(TABLE_AUSENCIA);
        $table_cursos = Database::get_main_table(TABLE_MAIN_COURSE);
        $table_user = Database::get_main_table(TABLE_MAIN_USER);
        $sql = "SELECT c.title as disciplina, u.firstname as nome, a.tipo, a.register_time as datahoraregistro, a.data_aula as aula, a.data_reposicao as reposicao, a.comentario, a.c_id as disciplina_cod, a.id, a.a_id as anuncio 
            FROM ".$table_cursos." as c 
            INNER JOIN ".$table_ausencia." as a ON a.c_id = c.id 
            INNER JOIN ".$table_user." as u ON a.user_id = u.user_id ";
            
		if (isset($_GET['keyword'])) { 
			$keyword = Database::escape_string(trim($_GET['keyword']));
			$sql .= " WHERE (u.firstname LIKE '%".$keyword."%' OR a.comentario LIKE '%".$keyword."%' OR c.title LIKE '%".$keyword."%' OR a.tipo LIKE '%".$keyword."%')";
		}
        switch ($column)
        {
            case 0:   
                $sql .= " ORDER BY c.title " . $direction;
                break;
           case 1:   
                $sql .= " ORDER BY u.firstname " . $direction;
                break;
           case 2:   
                $sql .= " ORDER BY a.tipo " . $direction;
                break;
           case 3:   
                $sql .= " ORDER BY a.register_time " . $direction;
                break;
           case 4:   
                $sql .= " ORDER BY a.data_aula " . $direction;
                break;
           case 5:   
                $sql .= " ORDER BY a.data_reposicao " . $direction;
                break;

        }

        $res = Database::query($sql);
        while($obj = Database::fetch_array($res))
        {
            //$curso = CourseManager::get_course_information_by_id($obj['disciplina']);
            $dado[0] =  $obj['disciplina']; //
            $dado[1] =  $obj['nome'];
            $dado[2] =  Ausencia::get_tipo_ocorrencia($obj['tipo']);
            $dado[3] =  $obj['datahoraregistro'];
            $dado[4] =  $obj['aula'];
            $dado[5] =  $obj['reposicao'];
            $dado[6] =  $obj['comentario'];
            $dado['curso_id'] =  $obj['curso'];
            $dado['anuncio_id'] =  $obj['anuncio'];
            //$dados[9] =  $obj['disciplina'];
            $dados[] = $dado;
            
        }
        /*
        echo "<pre>";
        print_r(CourseManager::get_course_information_by_id("1"));
        print_r($dados);
        echo "</pre>"; 
         * 
         */
        return $dados;
    }    
    
}

//echo "<pre>";
//print_r(Ausencia::get_usuarios_observadores(1));
//echo "</pre>";