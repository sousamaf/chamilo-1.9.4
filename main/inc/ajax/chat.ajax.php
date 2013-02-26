<?php
/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls
 */

$_dont_save_user_course_access  = true;

require_once '../global.inc.php';

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;

if (api_is_anonymous()) {
	exit;
}

if (api_get_setting('allow_global_chat') == 'false') {
	exit;
}

$to_user_id = isset($_REQUEST['to']) ? $_REQUEST['to'] : null;
$message	= isset($_REQUEST['message']) ? $_REQUEST['message'] : null;

if (!isset($_SESSION['chatHistory'])) {
	$_SESSION['chatHistory'] = array();
}

if (!isset($_SESSION['openChatBoxes'])) {
	$_SESSION['openChatBoxes'] = array();
}

$chat = new Chat();

if ($chat->is_chat_blocked_by_exercises()) {
    //Desconnecting the user
    $chat->set_user_status(0);
    exit;
}

switch ($action) {
	case 'chatheartbeat':
		$chat->heartbeat();
		break;
	case 'closechat':
		$chat->close();
		break;
	case 'sendchat':
		$chat->send(api_get_user_id(), $to_user_id, $message);
		break;
	case 'startchatsession':
		$chat->start_session();
		break;
    case 'set_status':
        $status = isset($_REQUEST['status']) ? intval($_REQUEST['status']) : 0;
		$chat->set_user_status($status);
		break;
	default:
        echo '';
}
exit;