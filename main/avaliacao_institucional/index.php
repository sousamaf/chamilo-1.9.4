<?php
/* For licensing terms, see /license.txt */

/**
* Template (front controller in MVC pattern) used for distpaching to the controllers depend on the current action  
* @author Marco Sousa <marco.volare@gmail.com>
* @package chamilo.avaliacao_institucional
*/

// name of the language file that needs to be included
$language_file = array ('index', 'tracking', 'userInfo', 'gradebook', 'avaliacao_institucional');
$cidReset = true;

// including files 
require_once '../inc/global.inc.php'; 
require_once api_get_path(LIBRARY_PATH).'catolicaDoTocantins.lib.php';
require_once api_get_path(LIBRARY_PATH).'avaliacao_institucional.lib.php'; 
require_once api_get_path(LIBRARY_PATH).'dashboard.lib.php';
require_once api_get_path(LIBRARY_PATH).'app_view.php';
require_once 'avaliacao_institucional_controller.php';


// protect script
api_block_anonymous_users();

// defining constants

// current section
$this_section = SECTION_REPORTS;
unset($_SESSION['this_section']);//for hmtl editor repository

// get actions
$actions = array('listing', 'help_done');
$action = 'listing';
if (isset($_GET['action']) && in_array($_GET['action'],$actions)) {
	$action = $_GET['action'];
}

// course description controller object
$avaliacao_institucional_controller = new AvaliacaoInstitucionalController();


if (isset($_GET['path'])) {
	$data = $_GET['path'];
}

// distpacher actions to controller
switch ($action) {	
	case 'listing':	
		$avaliacao_institucional_controller->display();
		break;
	case 'help_done':	
		$avaliacao_institucional_controller->store_help_done();
		break;	
	default :		
		$avaliacao_institucional_controller->display();
}
?>