<?php
/**
 * @package chamilo.plugin.avaliacal_institucional
 */

require_once api_get_path(LIBRARY_PATH).'catolicaDoTocantins.lib.php';
require_once api_get_path(LIBRARY_PATH).'avaliacao_institucional.lib.php';

$user_id = api_get_user_id();

if(true)
{
	$cpf = CatolicaDoTocantins::ct_getCpfFromUserid($user_id);
	
	if( AvaliacaoInstitucional::isActiveTeacher($cpf) && AvaliacaoInstitucional::isSurveyDone($user_id, ENQUETEPROFESSOR)) // OR ((AvaliacaoInstitucional::isActiveStudent($cpf) && !AvaliacaoInstitucional::isAllSurveyDone($user_id, $cpf))))
	{
		echo '<div class="well">';
	    echo "<h2>Avaliação Institucional</h2>";  
		
		//Using get_lang inside a plugin
		if(AvaliacaoInstitucional::isActiveTeacher($cpf)){
			echo get_lang('TratamentoDocente');
		} else {
			echo get_lang('TratamentoDiscente');
		}
		echo get_lang('BemvindoMensagem');
		echo '<a href='.api_get_path(WEB_CODE_PATH).'avaliacao_institucional/index.php>'.get_lang('LinkAvaliacao').'</a>';
		echo "<br>" . get_lang('Organizacao');
		echo '</div>';
	}
}