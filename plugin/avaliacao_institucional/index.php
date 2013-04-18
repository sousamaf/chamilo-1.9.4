<?php
/**
 * @package chamilo.plugin.avaliacal_institucional
 */

// See also the share_user_info plugin 

echo '<div class="well">';
if (!empty($plugin_info['settings']['avaliacao_institucional_show_type'])) {
    echo "<h2>".$plugin_info['settings']['avaliacao_institucional_show_type']."</h2>";
} else {
    echo "<h2>Avaliação Institucional</h2>";  
}

//Using get_lang inside a plugin
echo '<a href='.api_get_path(WEB_CODE_PATH).'avaliacao_institucional/index.php>'.get_lang('AvaliacaoInstitucionalPlugin').'</a>';

echo '</div>';