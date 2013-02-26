<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
// we connect to localhost at port 3307

require_once '../../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'catolicaDoTocantins.lib.php';
define('SEMESTRE', '2013/1');

function get_username_from_cpf($cpf)
{
    $table = Database::get_main_table(TABLE_MAIN_USER);

    $sql = "SELECT username FROM $table WHERE official_code='".$cpf."'";
    $res = Database::query($sql);
    $qtd = Database::num_rows($res);
    if($qtd > 1)
        $qtd = 2; // vários usuários com mesmo cpf

    switch ($qtd) {
        case 0:
            // Não cadastrado;
                return -1;
            break;
        case 1:
                $user = mysql_fetch_array($res);
                return $user['username'];
            break;

        case 2:
                // mais de um usuário com mesmo cpf
                return "-2 ".$cpf;
            break;
    }


}


function get_default_courses($course)
{
    switch($course){
        case '201' : // agronomia
            return "|CC201|CHAMILO";
            break;

        case '202' : // zootecnia
            return "|CC202|CHAMILO";
            break;

        case '205' : // gestao ambiental
            return "|CC205|CHAMILO";
            break;

        case '206' : // eng eletrica
            return "|CC206|CHAMILO";
            break;

        case '207' : // eng producao
            return "|CC207|CHAMILO";
            break;

        case '208' : // eng ambiental
            return "|CC208|CHAMILO";
            break;

        case '209' : // eng civil
            return "|CC209|CHAMILO";
            break;

        case '301' : // administracao
            return "|CC301|CHAMILO";
            break;

        case '302' : // sistemas de informacao
           return "|CC302|CA302|CHAMILO";
//            return "|CHAMILO";
            break;

        case '304' : // contabeis
            return "|CC304|304ENADE|CHAMILO";
            break;

        case '308' : // direito
            return "|308NPJ|CC308|CA308|CHAMILO";
            break;

    }

}


// Formulate Query
// This is the best way to perform a SQL query
// For more examples, see mysql_real_escape_string()
//$query = "SELECT * FROM testes.matriculados20121 where disciplinacodigo = 302129 order by nome";

//$query = "SELECT * FROM testes.matriculados20121 where cursocodigo = 308 and cursohorario = 'NOTURNO-50MIN' order by nome;";

$table_matriculados = Database::get_main_table(TABLE_GRADE_MATRICULADOS);
$query = "SELECT DISTINCT(cpf) FROM ". $table_matriculados ." WHERE semestre = '".SEMESTRE."' AND cursocodigo = '308' order by nome;";

// Perform Query
$result = mysql_query($query);

// Check result
// This shows the actual query sent to MySQL, and the error. Useful for debugging.
if (!$result) {
    $message  = 'Invalid query: ' . mysql_error() . "\n";
    $message .= 'Whole query: ' . $query;
    die($message);
}

$nao_cadastrados = array();
while ($row = mysql_fetch_assoc($result)) { //echo "<pre>"; print_r($row); echo "</pre>"; exit();

	$username = get_username_from_cpf($row['cpf']); 
    if($username != (-1 | -2) )
    {
		$user_id = UserManager::get_user_id_from_username($username);  
		//CatolicaDoTocantins::ct_removerCursoErrado($user_id); 
		$courses = array();
		$courses = CatolicaDoTocantins::ct_getCoordenacoesAluno($row['cpf']);
		$courses[] .= "308NPJ";
		$courses[] .= "CHAMILO";
		CatolicaDoTocantins::ct_incluirCursoCerto($user_id, $courses, false); 
		//CatolicaDoTocantins::ct_subscribe_users($user_id, 1, 660); 
		echo $username. " ". $row['cpf'] . "<br>";
		
    } else {
    	$nao_cadastrados[] .= $row['cpf'];
    }
}

echo "Não cadastrados <pre>";
	print_r($nao_cadastrados);
echo "</pre>";

mysql_free_result($result);
//mysql_close($con);

?>


