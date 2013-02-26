<?php
/* For licensing terms, see /license.txt */
/**
 * The certificates class is used to generate certificates from inside the
 * gradebook tool.
 * @package chamilo.library.certificates
 */
class Certificate extends Model {
    var $table;
    var $columns = array('id','cat_id','score_certificate','created_at','path_certificate');
    /**
     * Certification data 
     */
    var $certificate_data = array();
    
    /**
     * Student's certification path
     */    
    var $certification_user_path = null;  
    var $certification_web_user_path = null;  
    var $html_file     = null;
    var $qr_file     = null;
    var $user_id;
    
    //If true every time we enter to the certificate URL we would generate a new certificate
    // (good thing because we can edit the certificate and all users will have the latest certificate bad because we load the certificate everytime)
    var $force_certificate_generation = true;  //default true
    
    /**
     * Constructor
     * @param	int	ID of the certificate. If no ID given, take user_id and try to generate one
     */
    public function __construct($certificate_id = null) {
        $this->table             =  Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE);
        $this->certificate_data = null;
        
        if (isset($certificate_id)) {
            $this->certificate_data = $this->get($certificate_id);
            $this->user_id          = $this->certificate_data['user_id'];
        } else {
            //Try with the current user
            $this->user_id = api_get_user_id();
        }
        
        if ($this->user_id) { 
            
            //Need to be called before any operation
            $this->check_certificate_path();
                    
            //To force certification generation
            if ($this->force_certificate_generation) {
                $this->generate();
            }
            
            if (isset($this->certificate_data) && $this->certificate_data) {        
                if (empty($this->certificate_data['path_certificate'])) {
                    $this->generate();                    
                }            
            }
        }
        
        //Setting the qr and html variables
        if (isset($certificate_id) && !empty($this->certification_user_path)) {
            $pathinfo = pathinfo($this->certificate_data['path_certificate']);
            $this->html_file = $this->certification_user_path.basename($this->certificate_data['path_certificate']);
            $this->qr_file = $this->certification_user_path.$pathinfo['filename'].'_qr.png';
        }        
    }
    
    
    /**
     * Checks if the certificate user path directory is created
     */
    public function check_certificate_path() {
        $this->certification_user_path = null;
        
        //Setting certification path
        $path_info = UserManager::get_user_picture_path_by_id($this->user_id, 'system', true);
        
        $web_path_info = UserManager::get_user_picture_path_by_id($this->user_id, 'web', true);
        
        if (!empty($path_info) && isset($path_info['dir'])) {
            
            $this->certification_user_path = $path_info['dir'].'certificate/';
            $this->certification_web_user_path = $web_path_info['dir'].'certificate/';            
            
            if (!is_dir($path_info['dir'])) {
                mkdir($path_info['dir'], 0777, true);
            }
                    
            if (!is_dir($this->certification_user_path)) {
                mkdir($this->certification_user_path, 0777);
            }
        }        
    }
    
    /**
     * Deletes the current certificate object. This is generally triggered by
     * the teacher from the gradebook tool to re-generate the certificate because
     * the original version wa flawed.
     */
    public function delete($force_delete = false) {        
        if (!empty($this->certificate_data)) {
                         
            if (!is_null($this->html_file) || $this->html_file != '' || strlen($this->html_file)) {
                //Deleting HTML file                
                if (is_file($this->html_file)) {
                    @unlink($this->html_file);
                    if (is_file($this->html_file) === false) {
                        $delete_db = true;
                    } else {
                        $delete_db = false;
                    }
                }
                //Deleting QR code PNG image file                
                if (is_file($this->qr_file)) {
                    @unlink($this->qr_file);
                }
                if ($delete_db || $force_delete) {
                    return parent::delete($this->certificate_data['id']);
                }                
            } else {
                return parent::delete($this->certificate_data['id']);
            }
        }
        return false;
    }
    
    /** 
     *     Generates an HTML Certificate and fills the path_certificate field in the DB 
     * */
    
    public function generate($params = array()) {
        //The user directory should be set
        if (empty($this->certification_user_path) && $this->force_certificate_generation == false) {
            return false;
        }        
        require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be.inc.php';
        require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/gradebook_functions.inc.php';
        require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/scoredisplay.class.php';
        
        $params['hide_print_button'] = isset($params['hide_print_button']) ? true : false;
        
        $my_category = Category :: load($this->certificate_data['cat_id']);
                
        if (isset($my_category[0]) && $my_category[0]->is_certificate_available($this->user_id)) {
                        
            $user         = api_get_user_info($this->user_id);
            $scoredisplay = ScoreDisplay :: instance();
            $scorecourse  = $my_category[0]->calc_score($this->user_id);
    
            $scorecourse_display = (isset($scorecourse) ? $scoredisplay->display_score($scorecourse,SCORE_AVERAGE) : get_lang('NoResultsAvailable'));
                
            //Prepare all necessary variables:
            $organization_name     = api_get_setting('Institution');
            //$portal_name         = api_get_setting('siteName');
            $stud_fn             = $user['firstname'];
            $stud_ln             = $user['lastname'];
                
            //@todo this code is not needed
            $certif_text         = sprintf(get_lang('CertificateWCertifiesStudentXFinishedCourseYWithGradeZ'), $organization_name, $stud_fn.' '.$stud_ln, $my_category[0]->get_name(), $scorecourse_display);
            $certif_text         = str_replace("\\n","\n", $certif_text);
            
            //If the gradebook is related to skills we added the skills to the user
                                
            $skill = new Skill();
            $skill->add_skill_to_user($this->user_id, $this->certificate_data['cat_id']);            
    
            if (is_dir($this->certification_user_path)) {
                if (!empty($this->certificate_data)) { 
                    $new_content_html = get_user_certificate_content($this->user_id, $my_category[0]->get_course_code(), false, $params['hide_print_button']);
                                        
                    if ($my_category[0]->get_id() == strval(intval($this->certificate_data['cat_id']))) {
                        $name = $this->certificate_data['path_certificate'];
                        $my_path_certificate = $this->certification_user_path.basename($name);
                        if (file_exists($my_path_certificate) && !empty($name) && !is_dir($my_path_certificate) && $this->force_certificate_generation == false) {
                            //Seems that the file was already generated                            
                            return true;
                        } else {
                            //Creating new name
                            $name    = md5($this->user_id.$this->certificate_data['cat_id']).'.html';
                            $my_path_certificate = $this->certification_user_path.$name;                            
                            $path_certificate    ='/'.$name;
                            
                            //Getting QR filename
                            $file_info = pathinfo($path_certificate);
                            $qr_code_filename = $this->certification_user_path.$file_info['filename'].'_qr.png';                            
                            
                            $my_new_content_html = str_replace('((certificate_barcode))', Display::img($this->certification_web_user_path.$file_info['filename'].'_qr.png', 'QR'), $new_content_html['content']);
                            $my_new_content_html = mb_convert_encoding($my_new_content_html,'UTF-8', api_get_system_encoding());
                            
                            $result = @file_put_contents($my_path_certificate, $my_new_content_html);
                            if ($result) {                                
                                //Updating the path
                                self::update_user_info_about_certificate($this->certificate_data['cat_id'], $this->user_id, $path_certificate);                                
                                $this->certificate_data['path_certificate'] = $path_certificate;
                                
                                if ($this->html_file_is_generated()) {
                                    if (!empty($file_info)) {                             
                                        $text = $this->parse_certificate_variables($new_content_html['variables']);                                        
                                        $this->generate_qr($text, $qr_code_filename);
                                    }
                                }                                
                            }
                            return $result;
                        }                        
                    }
                }
            }
        }
        return false;
    }

    /**
    * update user info about certificate
    * @param int The category id
    * @param int The user id
    * @param string the path name of the certificate
    * @return void()
    */
    function update_user_info_about_certificate ($cat_id,$user_id,$path_certificate) {
        $table_certificate = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE);
        if (!UserManager::is_user_certified($cat_id,$user_id)) {
            $sql='UPDATE '.$table_certificate.' SET path_certificate="'.Database::escape_string($path_certificate).'"
                 WHERE cat_id="'.intval($cat_id).'" AND user_id="'.intval($user_id).'" ';
            Database::query($sql);
        }
    }
    
    /**
     * 
     * Check if the file was generated
     * 
     * @return boolean
     */
    function html_file_is_generated() {
        if (empty($this->certification_user_path)) {
            return false;
        }
        if (!empty($this->certificate_data) && isset($this->certificate_data['path_certificate']) && !empty($this->certificate_data['path_certificate'])) {
            return true;            
        }
        return false;
    } 
    
    /**
     * Generates a QR code for the certificate. The QR code embeds the text given
     * @param    string    Text to be added in the QR code
     * @param    string    file path of the image
     * */
    public function generate_qr($text, $path) {        
        //Make sure HTML certificate is generated
        if (!empty($text) && !empty($path)) {
            require_once api_get_path(LIBRARY_PATH).'phpqrcode/qrlib.php';
            //L low, M - Medium, L large error correction
            return QRcode::png($text, $path, 'M', 2, 2);
        }
        return false;
    }
    
    /**
     * Transforms certificate tags into text values. This function is very static
     * (it doesn't allow for much flexibility in terms of what tags are printed).
     * @param array Contains two array entris: first are the headers, second is an array of contents
     * @return string The translated string
     */
    public function parse_certificate_variables($array) {
        $text = '';        
        $headers = $array[0];
        $content = $array[1];
        $final_content = array();
        
        if (!empty($content)) {
            foreach($content as $key => $value) {                
                $my_header = str_replace(array('((', '))') , '', $headers[$key]);
                $final_content[$my_header] = $value;
            }
        }
        
        /* Certificate tags
         * 
          0 => string '((user_firstname))' (length=18)
          1 => string '((user_lastname))' (length=17)
          2 => string '((gradebook_institution))' (length=25)
          3 => string '((gradebook_sitename))' (length=22)
          4 => string '((teacher_firstname))' (length=21)
          5 => string '((teacher_lastname))' (length=20)
          6 => string '((official_code))' (length=17)
          7 => string '((date_certificate))' (length=20)
          8 => string '((course_code))' (length=15)
          9 => string '((course_title))' (length=16)
          10 => string '((gradebook_grade))' (length=19)
          11 => string '((certificate_link))' (length=20)
          12 => string '((certificate_link_html))' (length=25)
          13 => string '((certificate_barcode))' (length=23)          
         */
        
        $break_space = " \n\r ";
        
        $text = $final_content['gradebook_institution'].' - '.$final_content['gradebook_sitename'].' - '.get_lang('Certification').$break_space.
                get_lang('Student'). ': '.$final_content['user_firstname'].' '.$final_content['user_lastname'].$break_space.
                get_lang('Teacher'). ': '.$final_content['teacher_firstname'].' '.$final_content['teacher_lastname'].$break_space.
                get_lang('Date'). ': '.$final_content['date_certificate'].$break_space.
                get_lang('Score'). ': '.$final_content['gradebook_grade'].$break_space.
                'URL'. ': '.$final_content['certificate_link'];        
        return $text;
    }
    
    /**
    * Shows the student's certificate (HTML file). If the global setting 
    * allow_public_certificates is set to 'false', no certificate can be printed.
    * If the global allow_public_certificates is set to 'true' and the course
    * setting allow_public_certificates is set to 0, no certificate *in this
    * course* can be printed (for anonymous users). Connected users can always
    * print them.
    */
    public function show() {
        // Special rules for anonymous users
        $failed = false;
        if (api_is_anonymous()) {            
            if (api_get_setting('allow_public_certificates') != 'true') {
                // The "non-public" setting is set, so do not print
                $failed = true;
            } else {
                // Check the course-level setting to make sure the certificate
                //  can be printed publicly
                if (isset($this->certificate_data) && isset($this->certificate_data['cat_id'])) {
                    $gradebook = new Gradebook();
                    $gradebook_info = $gradebook->get($this->certificate_data['cat_id']);
                    if (!empty($gradebook_info['course_code'])) {
                        $allow_public_certificates = api_get_course_setting('allow_public_certificates', $gradebook_info['course_code']);
                        if ($allow_public_certificates == 0) {
                            // Printing not allowed
                            $failed = true;
                        }
                    } else {
                        // No course ID defined (should never get here)
                        Display :: display_reduced_header();
                        Display :: display_warning_message(get_lang('NoCertificateAvailable'));
                        exit;
                    }
                }
            }
        }
        if ($failed) {
            Display :: display_reduced_header();
            Display :: display_warning_message(get_lang('CertificateExistsButNotPublic'));
            exit;
        }
        //Read file or preview file
        if (!empty($this->certificate_data['path_certificate'])) {
            $user_certificate = $this->certification_user_path.basename($this->certificate_data['path_certificate']);
            if (file_exists($user_certificate)) {
                header('Content-Type: text/html; charset='. api_get_system_encoding());
                echo @file_get_contents($user_certificate);
            }
        } else {
            Display :: display_reduced_header();
            Display :: display_warning_message(get_lang('NoCertificateAvailable'));
        }
        exit;
    }    
}