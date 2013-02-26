<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
// we connect to localhost at port 3307

require_once '../../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';

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
                return -2;
            break;
    }


}

function get_default_courses($course)
{
    switch($course){
        case "201" : // agronomia
            return "|CC201|CHAMILO";
            break;

        case "202" : // zootecnia
            return "|CC202|CHAMILO";
            break;

        case "205" : // gestao ambiental
            return "|CC205|CHAMILO";
            break;

        case "206" : // eng eletrica
            return "|CC206|CHAMILO";
            break;

        case "207" : // eng producao
            return "|CC207|CHAMILO";
            break;

        case "209" : // eng civil
            return "|CC209|CHAMILO";
            break;

        case "301" : // administracao
            return "|CC301|CHAMILO";
            break;

        case "302" : // sistemas de informacao
            return "|CC302|CASI|CHAMILO";
            break;

        case "304" : // contabeis
            return "|CC304|CHAMILO";
            break;

        case "308" : // direito
            return "|CC308|CADFACTO|CHAMILO";
            break;

    }

}


// Formulate Query
// This is the best way to perform a SQL query
// For more examples, see mysql_real_escape_string()
//$query = "SELECT * FROM sacu.matriculados20111 where cursocodigo = 201 order by nome";

//$query = "SELECT * FROM testes.matriculados20111 where cursocodigo = 308 and cursohorario = 'NOTURNO-50MIN' order by nome;";

$query = "SELECT * FROM testes.matriculados order by nome;";
//echo $query;
// Perform Query
$result = mysql_query($query);

// Check result
// This shows the actual query sent to MySQL, and the error. Useful for debugging.
if (!$result) {
    $message  = 'Invalid query: ' . mysql_error() . "\n";
    $message .= 'Whole query: ' . $query;
    die($message);
}

$nomesCriados =array();
$registros = array();
$codigoCurso = array();
$matricula = null;
while ($row = mysql_fetch_assoc($result)) { //echo "<pre>"; print_r($row); echo "</pre>"; exit();
    if(get_username_from_cpf($row['cpf']) == (-1 | -2) ) {
        if($row['matricula'] != $matricula)
        {
            if(!empty ($matricula) )
                $registros[] = $registro;

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

            if($userName != "NAODISPONIVEL")
            {
                if(in_array($userName, $nomesCriados))
                {
                    $userName = "NAODISPONIVEL";
                }
                else
                {
                    $nomesCriados[] = $userName;
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

            if($registro['curso'] == '308' && $row['cursohorario'] == "NOTURNO")
            {
                $registro['disciplina'] = $row["disciplinacodigo"]."N";
            }
            else
            {
                $registro['disciplina'] = $row["disciplinacodigo"];
            }

            unset($nome);
        }
        else
        {
            if($registro['curso'] == '308' && $row['cursohorario'] == "NOTURNO")
            {
                $registro['disciplina'] = $registro['disciplina'] . "|" . $row['disciplinacodigo']."N";
            }
            else
            {
                $registro['disciplina'] = $registro['disciplina'] . "|" . $row['disciplinacodigo'];
            }
        }
    }
}

	echo "LastName;FirstName;Email;UserName;AuthSource;OfficialCode;PhoneNumber;Status;matricula;fullname;Courses;<br>";

	foreach ($registros as $r)
        {
            $cursos = get_default_courses($r['curso']);
		echo $r['lastname'].";".$r['firstname'].";".$r['email'].";". $r['UserName'].';'."platform;".$r['OfficialCode'].";". $r['PhoneNumber'].";"."user;".$r['matricula'].";".$r['fullname'].";".$r['disciplina'].$cursos."<br>";
        }

//echo "<pre>"; print_r($registros); echo "</pre>";

mysql_free_result($result);
//mysql_close($con);

?>
