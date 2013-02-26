<?php
require_once(dirname(__FILE__).'/../inc/global.inc.php');
$libpath = api_get_path(LIBRARY_PATH);

require_once $libpath.'usermanager.lib.php';
require_once $libpath.'course.lib.php';

/**
 * Error returned by one of the methods of the web service. Contains an error code and an error message
 */
class WSCMError {
	/**
	 * Error handler. This needs to be a class that implements the interface WSErrorHandler
	 * 
	 * @var WSErrorHandler
	 */
	protected static $_handler;
	
	/**
	 * Error code
	 * 
	 * @var int
	 */
	public $code;
	
	/**
	 * Error message
	 * 
	 * @var string
	 */
	public $message;
	
	/**
	 * Constructor
	 * 
	 * @param int Error code
	 * @param string Error message
	 */
	public function __construct($code, $message) {
		$this->code = $code;
		$this->message = $message;
	}
	
	/**
	 * Sets the error handler
	 * 
	 * @param WSErrorHandler Error handler
	 */
	public static function setErrorHandler($handler) {
		if($handler instanceof WSErrorHandler) {
			self::$_handler = $handler;
		}
	}
	
	/**
	 * Returns the error handler
	 * 
	 * @return WSErrorHandler Error handler
	 */
	public static function getErrorHandler() {
		return self::$_handler;
	}
	
	/**
	 * Transforms the error into an array
	 * 
	 * @return array Associative array with code and message
	 */
	public function toArray() {
		return array('code' => $this->code, 'message' => $this->message);
	}
}

/**
 * Interface that must be implemented by any error handler
 */
interface WSCMErrorHandler {
	/**
	 * Handle method
	 * 
	 * @param WSError Error
	 */
	public function handle($error);
}

/**
 * Main class of the webservice. Webservice classes extend this class
 */
class WSCM {
	/**
	 * Chamilo configuration
	 * 
	 * @var array
	 */
	protected $_configuration;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->_configuration = $GLOBALS['_configuration'];
	}

	/**
	 * Verifies the API key
	 * 
	 * @param string Secret key
	 * @return mixed WSError in case of failure, null in case of success
	 */
	protected function verifyKey($secret_key) {
		$ip = trim($_SERVER['REMOTE_ADDR']);
		// if we are behind a reverse proxy, assume it will send the 
		// HTTP_X_FORWARDED_FOR header and use this IP instead
		if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		  list($ip1,$ip2) = split(',',$_SERVER['HTTP_X_FORWARDED_FOR']);
		  $ip = trim($ip1);
		}
		$security_key = $ip.$this->_configuration['security_key'];

		if(!api_is_valid_secret_key($secret_key, $security_key)) {
			return new WSCMError(1, "API key is invalid");
		} else {
			return null;
		}
	}

        /**
         * Verifies if the user is valid
         *
         * @param <String> $username of the user in chamilo
         * @param <String> $pass of the same user (in MD5 of SHA)
         *
         * return "valid" if username e password are correct! Else, return a message error
         */

        public function verifyUserPass($username, $pass) { 
            $login = $username;
            $password = $pass;

	    //lookup the user in the main database
            $user_table = Database::get_main_table(TABLE_MAIN_USER);
	    $sql = "SELECT user_id, username, password, auth_source, active, expiration_date
	            FROM $user_table
	            WHERE username = '".trim(addslashes($login))."'";
	    $result = Database::query($sql); 

            if (Database::num_rows($result) > 0) {
                $uData = Database::fetch_array($result);

                if ($uData['auth_source'] == PLATFORM_AUTH_SOURCE) {
                    $password = trim(stripslashes($password));
                    // Check the user's password
                        if ($password == $uData['password'] AND (trim($login) == $uData['username'])) {
                        // Check if the account is active (not locked)
                            if ($uData['active']=='1') {
                                // Check if the expiration date has not been reached
                                if ($uData['expiration_date']>date('Y-m-d H:i:s') OR $uData['expiration_date']=='0000-00-00 00:00:00') {
                                    return "valid";
                                }
                                else 
                                    return get_lang('AccountExpired');
                            }
                            else 
                                return get_lang('AccountInactive');
                        }
                        else
                            return get_lang('InvalidId');
                    }
                    else
                        return get_lang('AccountURLInactive');
                }
                return get_lang('InvalidId');
        }

        /**
         * Return the encrypted pass
         * @param <String> $pass
         * @return <String> $pass encrypted
         */
        public function encryptPass($pass){
            return api_get_encrypted_password($pass);
        }

        /**
	 * Gets the real user id based on the user id field name and value. Note that if the user id field name is "chamilo_user_id", it will use the user id
	 * in the system database
	 * 
	 * @param string User id field name
	 * @param string User id value
	 * @return mixed System user id if the user was found, WSError otherwise
	 */
	protected function getUserId($user_id_field_name, $user_id_value) {
		if($user_id_field_name == "chamilo_user_id") {
			if(UserManager::is_user_id_valid(intval($user_id_value))) {
				return intval($user_id_value);
			} else {
				return new WSCMError(100, "User not found");
			}
		} else {
			$user_id = UserManager::get_user_id_from_original_id($user_id_value, $user_id_field_name);
			if($user_id == 0) {
				return new WSCMError(100, "User not found");
			} else {
				return $user_id;
			}
		}
	}
	
	/**
	 * Gets the real course id based on the course id field name and value. Note that if the course id field name is "chamilo_course_id", it will use the course id
	 * in the system database
	 * 
	 * @param string Course id field name
	 * @param string Course id value
	 * @return mixed System course id if the course was found, WSError otherwise
	 */
	protected function getCourseId($course_id_field_name, $course_id_value) {
		if($course_id_field_name == "chamilo_course_id") {
			if(CourseManager::get_course_code_from_course_id(intval($course_id_value)) != null) {
				return intval($course_id_value);
			} else {
				return new WSCMError(200, "Course not found");
			}
		} else {
			$course_code = CourseManager::get_course_code_from_original_id($course_id_value, $course_id_field_name);
			if($course_code == 0) {
				return new WSCMError(200, "Course not found");
			} else {
				$course_info = CourseManager::get_course_information($course_code);
				return $course_info['id'];
			}
		}
	}
	
	/**
	 * Gets the real session id based on the session id field name and value. Note that if the session id field name is "chamilo_session_id", it will use the session id
	 * in the system database
	 * 
	 * @param string Session id field name
	 * @param string Session id value
	 * @return mixed System session id if the session was found, WSError otherwise
	 */
	protected function getSessionId($session_id_field_name, $session_id_value) {
		if($session_id_field_name == "chamilo_session_id") {
			$session = SessionManager::fetch((int)$session_id_value);
			if(!empty($session)) {
				return intval($session_id_value);
			} else {
				return new WSCMError(300, "Session not found");
			}
		} else {
			$session_id = SessionManager::get_session_id_from_original_id($session_id_value, $session_id_field_name);
			if($session_id == 0) {
				return new WSCMError(300, "Session not found");
			} else {
				return $session_id;
			}
		}
	}
	
	/**
	 * Handles an error by calling the WSError error handler
	 * 
	 * @param WSError Error
	 */
	protected function handleError($error) {
		$handler = WSCMError::getErrorHandler();
		$handler->handle($error);
	}
	
	/**
	 * Gets a successful result
	 * 
	 * @return array Array with a code of 0 and a message 'Operation was successful'
	 */
	protected function getSuccessfulResult() {
		return array('code' => 0, 'message' => 'Operation was successful');
	}
	
	/**
	 * Test function. Returns the string success
	 * 
	 * @return string Success
	 */
	public function test() {
		return "success";
	}

        /**
         * *Strictly* reverts PHP's nl2br() effects (whether it was used in XHTML mode or not)
         * @param <type> $string
         * @return <type> $string
         */
        public function nl2br_revert($string) {
            return preg_replace('`<br(?: /)?>([\\n\\r])`', '$1', $string);
        }
	
	
}

