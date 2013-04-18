<?php
/**
 * This script is a configuration file for the date plugin. You can use it as a master for other platform plugins (course plugins are slightly different).
 * These settings will be used in the administration interface for plugins (Chamilo configuration settings->Plugins)
 * @package chamilo.plugin
 * @author Julio Montoya <gugli100@gmail.com>
 * @author Marco Sousa <marco.volare@gmail.com>
 */
/**
 * Plugin details (must be present)
 */

/* Plugin config */

//the plugin title
$plugin_info['title']       = 'Avaliação Institucional';
//the comments that go with the plugin
$plugin_info['comment']     = "Link para a avaliação institucional continuada";
//the plugin version
$plugin_info['version']     = '1.0';
//the plugin author
$plugin_info['author']      = 'Marco Sousa';


/* Plugin optional settings */ 

/* 
 * This form will be showed in the plugin settings once the plugin was installed 
 * in the plugin/avaliacao_institucional/index.php you can have access to the value: $plugin_info['settings']['avaliacao_institucional_show_type']
*/

$params = array('variable = ?' =>  'avaliacao_institucional_show_type');
$data = api_get_settings_params($params);

if(empty($data)) {
	$data = 'avaliacao_institucional_off';
} else {
	$data = array_values($data);
	$default_value = $data[0]['selected_value']; 
}
	

$form = new FormValidator('avaliacao_institucional_form');

//A simple select
$options = array('avaliacao_institucional_off' => 'Desativada', 'avaliacao_institucional_externa' => 'Áreas externas', 'avaliacao_institucional_cursos' =>'Áreas dos cursos', 'avaliacao_institucional_global' =>'Global');
$form->addElement('select', 'show_type', 'Modo de operação da Avaliação Institucional', $options)->setValue($default_value);
$form->addElement('style_submit_button', 'submit_button', get_lang('Save'));  

$plugin_info['settings_form'] = $form;