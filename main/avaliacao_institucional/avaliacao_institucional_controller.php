<?php
/* For licensing terms, see /license.txt */

/**
 * This file contains class used like controller, it should be included inside a dispatcher file (e.g: index.php)
 * @author Christian Fasanando <christian1827@gmail.com>
 * @author Marco Sousa <marco.volare@gmail.com>
 * @package chamilo.avaliacao_institucional
 */

/**
 * Controller script. Prepares the common background variables to give to the scripts corresponding to
 * the requested action
 */
class AvaliacaoInstitucionalController { // extends Controller {

	private $toolname;
	private $view;
	private $user_id;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->user_id = api_get_user_id();
		$this->toolname = 'avaliacao_institucional';
		$this->view = new View($this->toolname);
	}

	/**
	 * Display blocks from dashboard plugin paths
	 * @param string message (optional)
	 * render to dashboard.php view
	 */
	public function display($msg = false) {

		$data = array();
		$user_id = $this->user_id;


		if ($msg) {
			$data['msg'] = $msg;
		}
		if(AvaliacaoInstitucional::isViewedHelp($user_id)) {
			$data['avaliacao_view'] = 'report';
		}
		else {
			$data['avaliacao_view'] = 'help';
		}
		
		// render to the view
		$this->view->set_data($data); 
		$this->view->set_layout('layout'); 
		$this->view->set_template('avaliacao_institucional'); 
		$this->view->render(); 
	}

	/**
	 * This method allow store user blocks from dashboard manager
	 * render to dashboard.php view
	 */
	public function store_help_done() {

		$data = array();
		$user_id = $this->user_id;
		if (strtoupper($_SERVER['REQUEST_METHOD']) == "POST") {
			AvaliacaoInstitucional::setViewedHelp($user_id);
			$data['success'] = true;
		}

		$data['avaliacao_view'] = 'list';

		// render to the view
		$this->view->set_data($data);
		$this->view->set_layout('layout');
		$this->view->set_template('avaliacao_institucional');
		$this->view->render();
	}
}
?>
