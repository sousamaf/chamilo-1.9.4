<?php
/* For licensing terms, see /license.txt */
/**
 * Gradebook link to student publication item
 * @author Bert Steppé
 * @package chamilo.gradebook
 */
/**
 * Class
 * @package chamilo.gradebook
 */
class StudentPublicationLink extends AbstractLink
{

    // INTERNAL VARIABLES
    private $studpub_table = null;
    private $itemprop_table = null;

    // CONSTRUCTORS
    public function __construct() {
    	parent::__construct();
    	$this->set_type(LINK_STUDENTPUBLICATION);
    }

    /**
     * 
     * Returns the URL of a document
     * This funcion is loaded when using a gradebook as a tab (gradebook = -1), see issue #2705
     * 
     */

	public function get_view_url ($stud_id) {
		// find a file uploaded by the given student,
		// with the same title as the evaluation name

    	$eval = $this->get_evaluation();
        $stud_id = intval($stud_id);

		$sql = 'SELECT pub.url FROM '.$this->get_itemprop_table().' prop, '.$this->get_studpub_table().' pub'
				." WHERE
					prop.c_id = ".$this->course_id." AND
					pub.c_id = ".$this->course_id." AND  
					prop.tool = 'work'"
				.' AND prop.insert_user_id = '.$stud_id
				.' AND prop.ref = pub.id'
				." AND pub.title = '".Database::escape_string($eval->get_name())."' AND pub.session_id=".api_get_session_id()."";

		$result = Database::query($sql);
		if ($fileurl = Database::fetch_row($result)) {
	    	$course_info = Database :: get_course_info($this->get_course_code());
			//$url = api_get_path(WEB_PATH).'main/gradebook/open_document.php?file='.$course_info['directory'].'/'.$fileurl[0];
			//return $url;
            return null;      
		 } else {
			return null;
		}
	}

    public function get_type_name() {
    	return get_lang('Works');
    }

	public function is_allowed_to_change_name() {
		return false;
	}

    // FUNCTIONS IMPLEMENTING ABSTRACTLINK

	/**
	 * Generate an array of exercises that a teacher hasn't created a link for.
	 * @return array 2-dimensional array - every element contains 2 subelements (id, name)
	 */
    public function get_not_created_links() {
        return false;
    	if (empty($this->course_code)) {
    		die('Error in get_not_created_links() : course code not set');
    	}
    	$tbl_grade_links = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINK);

		$sql = 'SELECT id, url from '.$this->get_studpub_table()
				.' pup WHERE c_id = '.$this->course_id.' AND has_properties != '."''".' AND id NOT IN'
				.' (SELECT ref_id FROM '.$tbl_grade_links
				.' WHERE type = '.LINK_STUDENTPUBLICATION
				." AND course_code = '".Database::escape_string($this->get_course_code())."'"
				.') AND pub.session_id='.api_get_session_id().'';

		$result = Database::query($sql);

		$cats=array();
		while ($data=Database::fetch_array($result)) {
			$cats[] = array ($data['id'], $data['url']);
		}
		return $cats;
    }
    
	/**
	 * Generate an array of all exercises available.
	 * @return array 2-dimensional array - every element contains 2 subelements (id, name)
	 */
    public function get_all_links() {
        
    	if (empty($this->course_code)) {
     		die('Error in get_not_created_links() : course code not set');
    	}    	
    	$tbl_grade_links = Database :: get_course_table(TABLE_STUDENT_PUBLICATION);
        
        $session_id = api_get_session_id();
        /*
        if (empty($session_id)) {
            $session_condition = api_get_session_condition(0, true);
        } else {
            $session_condition = api_get_session_condition($session_id, true, true);
        }     
		$sql = "SELECT id, url, title FROM $tbl_grade_links         
				WHERE c_id = {$this->course_id}  AND filetype='folder' AND active = 1 $session_condition ";*/
        
        //Only show works from the session
        //AND has_properties != ''
        $sql = "SELECT id, url, title FROM $tbl_grade_links 
				WHERE c_id = {$this->course_id} AND active = 1 AND filetype='folder' AND session_id = ".api_get_session_id()."";
	                

		$result = Database::query($sql);
		while ($data = Database::fetch_array($result)) {
            $work_name = $data['title'];
            if (empty($work_name)) {
                $work_name = basename($data['url']);
            }            
			$cats[] = array ($data['id'], $work_name);
		}
		$cats=isset($cats) ? $cats : array();
		return $cats;
    }

    /**
     * Has anyone done this exercise yet ?
     */
    public function has_results() {
    	$tbl_grade_links = Database :: get_course_table(TABLE_STUDENT_PUBLICATION);
		$sql = 'SELECT count(*) AS number FROM '.$tbl_grade_links." 
				WHERE 	c_id 		= {$this->course_id} AND 
						parent_id 	= '".intval($this->get_ref_id())."' AND 
						session_id	=".api_get_session_id()."";
    	$result = Database::query($sql);
		$number = Database::fetch_row($result);
		return ($number[0] != 0);
    }

    public function calc_score($stud_id = null) {
    	$stud_id 	= intval($stud_id);
		$tbl_stats 	= Database::get_course_table(TABLE_STUDENT_PUBLICATION);		
    	$sql = 'SELECT * FROM '.$tbl_stats." 
    			WHERE 	c_id 		= {$this->course_id} AND  
    					id 			= '".intval($this->get_ref_id())."' AND 
    					session_id	= ".api_get_session_id()."";
		$query = Database::query($sql);
		$assignment = Database::fetch_array($query);

    	if (count($assignment)==0) {
    		 $v_assigment_id ='0';
    	} else {
    		 $v_assigment_id = $assignment['id'];
    	}
    	$sql = 'SELECT * FROM '.$tbl_stats.' 
    			WHERE c_id = '.$this->course_id.' AND active = 1 AND parent_id ="'.$v_assigment_id.'" AND session_id='.api_get_session_id().'';
    	
    	if (!empty($stud_id)) {
    		$sql .= " AND user_id = $stud_id ";
    	}
    	// order by id, that way the student's first attempt is accessed first
		$sql .= ' ORDER BY id';
        
    	$scores = Database::query($sql);

		// for 1 student
    	if (!empty($stud_id)) {
    		if ($data = Database::fetch_array($scores)) {
     			return array($data['qualification'], $assignment['qualification']);
    		} else {
     			return '';
    		}
    	} else {
    		$students = array();  // user list, needed to make sure we only
    							// take first attempts into account
			$rescount = 0;
			$sum = 0;
			while ($data = Database::fetch_array($scores)) {
				if (!(array_key_exists($data['user_id'], $students))) {
					if ($assignment['qualification'] != 0) {
						$students[$data['user_id']] = $data['qualification'];
						$rescount++;
						$sum += $data['qualification'] / $assignment['qualification'];
					}
				}
			}

			if ($rescount == 0) {
				return null;
			} else {
				return array ($sum , $rescount);
			}
    	}
    }

	// INTERNAL FUNCTIONS

    /**
     * Lazy load function to get the database table of the student publications
     */
    private function get_studpub_table() {    	
		return $this->studpub_table = Database :: get_course_table(TABLE_STUDENT_PUBLICATION);
    }

    /**
     * Lazy load function to get the database table of the item properties
     */
    private function get_itemprop_table () {
    	return $this->itemprop_table = Database :: get_course_table(TABLE_ITEM_PROPERTY);
    }

   	public function needs_name_and_description() {
		return false;
	}

	public function get_name() {
    	$this->get_exercise_data();    	
    	return (isset($this->exercise_data['title']) && !empty($this->exercise_data['title'])) ? $this->exercise_data['title'] : get_lang('Untitled');
    }

    public function get_description() {
    	$this->get_exercise_data();
    	return isset($this->exercise_data['description']) ? $this->exercise_data['description'] : null;
    }

    public function get_test_id() {
    	return 'DEBUG:ID';
    }

    public function get_link() {
        $session_id = api_get_session_id();
		$url = api_get_path(WEB_PATH).'main/work/work.php?session_id='.$session_id.'&cidReq='.$this->get_course_code().'&id='.$this->exercise_data['id'].'&gradebook=view';		
		return $url;
	}

	private function get_exercise_data() {
		$tbl_name = $this->get_studpub_table();
		$course_info = Database :: get_course_info($this->get_course_code());
		if ($tbl_name=='') {
			return false;
		} elseif (!isset($this->exercise_data)) {
    		$sql = 'SELECT * FROM '.$this->get_studpub_table()." WHERE c_id ='".$course_info['real_id']."' AND id = '".intval($this->get_ref_id())."' ";
			$query = Database::query($sql);
			$this->exercise_data = Database::fetch_array($query);
    	}
    	return $this->exercise_data;
    }

    public function needs_max() {
		return false;
	}

	public function needs_results() {
		return false;
	}

    public function is_valid_link() {    	    	
    	$sql = 'SELECT count(id) FROM '.$this->get_studpub_table().' 
    			WHERE c_id = "'.$this->course_id.'" AND id = '.intval($this->get_ref_id()).'';
		$result = Database::query($sql);
		$number = Database::fetch_row($result);
		return ($number[0] != 0);
    }

    public function get_icon_name() {
		return 'studentpublication';
	}
    
    function save_linked_data() {
        $weight = (float)$this->get_weight();        
        $ref_id = $this->get_ref_id();
        
        if (!empty($ref_id)) {
            //Cleans works            
            $sql = 'UPDATE '.$this->get_studpub_table().' SET weight= '.$weight.'
                    WHERE c_id = '.$this->course_id.' AND id ='.$ref_id;
            Database::query($sql);
        }
    }
    
    function delete_linked_data() {
        $ref_id = $this->get_ref_id();
        if (!empty($ref_id)) {
            //Cleans works            
            $sql = 'UPDATE '.$this->get_studpub_table().' SET weight=0
                    WHERE c_id = '.$this->course_id.' AND id ='.$ref_id;
            Database::query($sql);
        }
    }
}