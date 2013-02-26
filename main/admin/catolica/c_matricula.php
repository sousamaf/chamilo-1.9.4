<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
// we connect to localhost at port 3307

require_once '../../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
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
            return "|CC308|CA308|CHAMILO";
            break;

    }

}

function get_right_course($coursecode, $course, $classroom, $time)
{
    switch ($course)
    {
                
        
        case '202' :  // zootecnia
             switch($coursecode)
            {
                    case '2020232' : // agrupamento a pedido - Yara
                        return "201234"; // agrupamento a pedido - Evandro Reina
                        break;

                    case '2020255' :
                        return "201245"; // agrupamento a pedido - Evandro Reina
                        break;

                    case '2020252' :
                        return "201228"; // agrupamento a pedido - Evandro Reina
                        break;
                    
                case '2020238' : // agrupamento a pedido do claudecir
                    return "201259";
                    break;        

                case '2020233' : // agrupamento a pedido do vailton
                    return "201283";
                    break;        

                
                default : 
                        return $coursecode;

             }
             break;

         
        case '205' :  // gestao ambiental
             switch($coursecode)
            {

                case '205156' : // agrupamento a pedido do antonio rafael
                    return "301164";
                    break;        

                case '205154' : // agrupamento a pedido do claudecir
                    return "201259";
                    break;        

                case '205175' : // agrupamento a pedido - suzana
                    return "301192"; 
                    break;

                    default : 
                        return $coursecode;

             }
             break;

        case '206' :  // gestao ambiental
             switch($coursecode)
            {

                case '206026' : // agrupamento a pedido do claudecir
                    return "302122";
                    break;        

                case '206011' : // mudança de grade
                    switch ($classroom)
                    {
                        case '206N3A' : // Diana
                            return "206011";
                            break;
                        case '206N2A' : // Marcos Oliveira
                            return "206011B";
                            break;

                        default : 
                             return "206011";
                    }
                    break;        
                
                default : 
                        return $coursecode;

             }
             break;


        case '207' :  // gestao ambiental
             switch($coursecode)
            {

                case '207006' : // mudança de grade
                    switch ($classroom)
                    {
                        case '207N1A' : // Marcos Oliveira
                            return "207006";
                            break;
                        case '207N2A' : // Gustavo
                            return "302115";
                            break;

                        default : 
                             return "206011";
                    }
                    break;        
                
                default : 
                        return $coursecode;

             }
             break;

        case '209' :  // engenharia civil
             switch($coursecode)
            {
                case '209006' : // mudança de grade - calculo I
                    switch ($classroom)
                    {
                        case '209N2A' : // Gustavo
                            return "209006";
                            break;
                        case '209N1A' : // Antonio Rafael
                            return "209006B";
                            break;

                        default : 
                             return "209010";
                    }
                    break;        

                case '209010' : // mudança de grade
                    switch ($classroom)
                    {
                        case '209N1A' : // Diana
                            return "209010";
                            break;
                        case '209N2A' : // Fabrício
                            return "209010B";
                            break;

                        default : 
                             return "209010";
                    }
                    break;        
                
                default : 
                        return $coursecode;

             }
             break;

        case '301' : // administracao
            switch($coursecode){

                case '301161' : // agrupamento a pedido do antonio rafael
                    return "3040115";
                    break;        

                case '301156' : // agrupamento a pedido do antonio rafael
                    return "3040111";
                    break;

                case '301159' : // agrupamento a pedido do claudecir
                    return "3040118";
                    break;

                case '301177' : // agrupamento a pedido do pacheco
                    return "207018";
                    break;

                default : 
                    return $coursecode;

            }
            break;

         
        case '302' :  // sistemas
             switch($coursecode)
            {

                case '302110' : // a pedido da rachel
                    return "301157";
                    break;

                case '302118' : // a pedido do andre rincon # mudar nome da disciplina
                    return "302119";
                    break;

                case '302147' : // a pedido do vailton
                    return "301178";
                    break;

                case '302150' :
                    return "TCC";
                    break;

                case '302161' :
                    return "EST";
                    break;

                case '302162' :
                    return "EST";
                    break;

                case '302163' :
                    return "TCC";
                    break;

                case '302165' :
                    return "EST";
                    break;

                case '302166' :
                    return "EST";
                    break;

                default :
                    return $coursecode;
            
            }
            break;
        
        case '304' :  // contabeis
             switch($coursecode)
            {
                case '3040112' : // agrupamento a pedido - eliane ferreira
                    return "301155";
                    break;

                case '3040116' : // agrupamento a pedido - maria joaquina
                    return "301162"; 
                    break;
                
                case '3040117' : // agrupamento a pedido - maria joaquina
                    return "301163"; 
                    break;

                case '3040120' : // agrupamento a pedido - maria leonice
                    return "301160"; 
                    break;
	
                case '3040121' : // agrupamento a pedido - suzana
                    return "301167"; 
                    break;

                case '3040124' : // agrupamento a pedido - maria leonice
                    return "301172"; 
                    break;

                case '3040165' : // agrupamento a pedido - claudemir
                    return "301188"; 
                    break;

                    default : 
                        return $coursecode;

             }
             break;

        case '308' : // direito
            switch($coursecode){
                case '308093' :
                    switch ($classroom)
                    {
/*                        case '307M4A' :
                            return "308093";
                            break;
                        case '307M9A' :
                            return "308093M9A";
                            break;
                        case '308N4A' :
                            return "308093N";
                            break;
                        case '308N9A' :
                            return "308093N9A";
                            break;
  */
                            default : 
                             return "308093N";
                    }
                    break;
                case '308076':
                    switch ($classroom)
                    {
                        case '308N1A' :
                            return "308076N";
                            break;

                        case '307M1A' :
                            return "308076";
                            break;

                        default: 
                            return "308076";
                    }
                        
                    break;

                case '308077' : // agrupamento a pedido - evandro
                    return "308077";
                    break;

		case '308081' : // agrupamento a pedido - maria leonice
			return "308081";
		    break; 

                case '308116' : // agrupamento a pedido - evandro
                    return "308116";
                    break;

                case '308117' : // agrupamento a pedido - maria joaquina
                    return "308117";
                    break;

                case '308123' : // agrupamento a pedido - evandro
                    return "308123";
                    break;

                case '308140' :
                    return "308140";
                    break;

                case '308131' :
                    return "308131N";
                    break;

                case '308130' :
                    return "308130N";
                    break;
                
                default :
                    
                    if($time == "NOTURNO")
                        return $coursecode."N";
                    else
                        return $coursecode;
                }
                break;
         
        default : 
            return $coursecode;
    }

}



// Formulate Query
// This is the best way to perform a SQL query
// For more examples, see mysql_real_escape_string()
//$query = "SELECT * FROM testes.matriculados20121 where disciplinacodigo = 302129 order by nome";

//$query = "SELECT * FROM testes.matriculados20121 where cursocodigo = 308 and cursohorario = 'NOTURNO-50MIN' order by nome;";

$table_matriculados = Database::get_main_table(TABLE_GRADE_MATRICULADOS);
$query = "SELECT * FROM ". $table_matriculados ." WHERE semestre = '".SEMESTRE."' order by nome;";

// Perform Query
$result = mysql_query($query);

// Check result
// This shows the actual query sent to MySQL, and the error. Useful for debugging.
if (!$result) {
    $message  = 'Invalid query: ' . mysql_error() . "\n";
    $message .= 'Whole query: ' . $query;
    die($message);
}

$registros = array();
$registro = array();
$codigoCurso = array();
$matricula = null;
while ($row = mysql_fetch_assoc($result)) { //echo "<pre>"; print_r($row); echo "</pre>"; exit();

    if(get_username_from_cpf($row['cpf']) == (-1 | -2) ) {
        if($row['matricula'] != $matricula)
        {
            if(!empty ($matricula) )
            {
                $registros[] = $registro;
            }
//echo "entrou";
//print_r($resgistro);
            $matricula = $row['matricula'];
            $registro['matricula'] = $matricula;
            $nome = array();
            $nome = explode(" ", $row['nome']);

            $registro['firstname'] = ucfirst(mb_strtolower($nome[0], "latin1"));

            if(strlen($nome[1])<4)
                $registro['lastname'] = mb_strtolower($nome[1], "latin1");
            else
                $registro['lastname'] = ucfirst(mb_strtolower($nome[1], "latin1"));

            for($i = 2; $i < count($nome); $i++){
                if(strlen($nome[$i])<4)
                    $registro['lastname'] = $registro['lastname'] . " " . mb_strtolower($nome[$i], "latin1");
                else
                    $registro['lastname'] = $registro['lastname'] . " " . ucfirst(mb_strtolower($nome[$i], "latin1"));
            }

            $registro['fullname'] = $registro['firstname']. " ". $registro['lastname'];
            $userName = mb_strtolower($nome[0], "latin1") .".". mb_strtolower($nome[1][0], "latin1");
            if(!UserManager::is_username_available($userName))
            {
                if(count($nome)>=3)
                {
                    $userName = mb_strtolower($nome[0], "latin1") .".". mb_strtolower($nome[1][0], "latin1").mb_strtolower($nome[2][0], "latin1");
                    if(!UserManager::is_username_available($userName))
                    {

                        $userName = "NAODISPONIVEL";
                    }
                }
                else
                {
                    $userName = "NAODISPONIVEL";
                }

            }

            $registro['UserName'] = $userName;

            $cpf = $row['cpf'];
            $tamcpf = strlen($cpf);
            if($tamcpf!=11)
            {
                $difftam = 11 - $tamcpf;
                for($i = 0; $i < $difftam; $i++)
                    $zero = $zero . "0";
                $cpf = $zero.$cpf;
                unset($zero);
            }
            $registro['OfficialCode'] = $cpf;
            $registro['curso'] = $row['cursocodigo'];

            $registro['email'] = mb_strtolower($row['email'], "latin1");
            $registro['matricula'] = $matricula;
            $registro['PhoneNumber'] = $row['fone1']. " / ". $row['fone2'];
            //$registro['disciplina'] = $row["disciplinacodigo"];

            $coursecode = $row['disciplinacodigo'];
            $course = substr($row['disciplinacodigo'], 0, 3);
            $classroom = $row['disciplinaturma'];
            $time = $row['cursohorario'];
            $registro['disciplina'] = get_right_course($coursecode, $course, $classroom, $time);
//print_r($registro);
            unset($nome);
        }
        else
        {
            $coursecode = $row['disciplinacodigo'];
            $course = substr($row['disciplinacodigo'], 0, 3);
            $classroom = $row['disciplinaturma'];
            $time = $row['cursohorario'];
            $disciplinacodigo = get_right_course($coursecode, $course, $classroom, $time);
            $registro['disciplina'] = $registro['disciplina'] . "|" . $disciplinacodigo;
        }
    }
    else{

        $soMatricula['username'] = get_username_from_cpf($row['cpf']);
        $soMatricula['cursocodigo'] = $row['cursocodigo'];

        $coursecode = $row['disciplinacodigo'];
        $course = substr($row['disciplinacodigo'], 0, 3);
        $classroom = $row['disciplinaturma'];
        $time = $row['cursohorario'];
        $soMatricula['disciplina'] = get_right_course($coursecode, $course, $classroom, $time);

        $soMatriculas[] = $soMatricula;

    }
}

if( isset($_GET['acao']) && ($_GET['acao'] == "matricula") )
{
        echo "UserName;CourseCode;Status<br>";
        $academico = "";
	foreach($soMatriculas as $m)
        {
            $tmpAcademico = $m['username'];
            if($academico != $tmpAcademico)
            {
                $codigos = get_default_courses($m['cursocodigo']);
                $codigos = explode("|", $codigos);
                foreach ($codigos as $c)
                {
		    if(!empty($c)) 
                    echo $m['username'].";". $c.";5<br>";
                }


                $academico = $tmpAcademico;
            }
            echo $m['username'].";". $m['disciplina'].";5<br>";

//echo "<pre>"; print_r($m); print_r($codigos); echo "</pre>";
//if($m['username'] == "adeany.f") exit();
        }

        echo "<br><br><br><br>Inclusao:<br>";
}

echo "LastName;FirstName;Email;UserName;AuthSource;OfficialCode;PhoneNumber;Status;matricula;fullname;Courses<br>";

foreach ($registros as $r)
{
    $cursos = get_default_courses($r['curso']);
    //echo $r['lastname'].";".$r['firstname'].";".$r['email'].";". $r['UserName'].';'."platform;".$r['OfficialCode'].";". $r['PhoneNumber'].";"."user;".$r['matricula'].";".$r['fullname'].";".$r['disciplina'].$cursos."<br>";
	echo $r['lastname'].";".$r['firstname'].";".$r['email'].";". $r['UserName'].';'."platform;".$r['OfficialCode'].";". $r['PhoneNumber'].";"."user;".$r['matricula'].";".$r['fullname'].";".$cursos."<br>";
//	echo $r['lastname'].";".$r['firstname'].";".$r['email'].";". $r['UserName'].';'."platform;".$r['OfficialCode'].";". $r['PhoneNumber'].";"."user;".$r['matricula'].";".$r['fullname'].";".$cursos."<br>";
}

//echo "<pre>"; print_r($registros); echo "</pre>";

mysql_free_result($result);
//mysql_close($con);

?>


