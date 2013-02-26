<?php
/* For licensing terms, see /license.txt */
/**
 * Defines a gradebook LearnpathLink object.
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 * @author Bert Steppé
 * @package chamilo.gradebook
 */
/**
 * Class
 * @package chamilo.gradebook
 */
class LearnpathLink extends AbstractLink
{
    // INTERNAL VARIABLES
    private $course_info = null;
    private $learnpath_table = null;
    private $learnpath_data = null;

    // CONSTRUCTORS
    function __construct() {
    	parent::__construct();
    	$this->set_type(LINK_LEARNPATH);
    }
    
    // FUNCTIONS IMPLEMENTING ABSTRACTLINK

	/**
	 * Generate an array of learnpaths that a teacher hasn't created a link for.
	 * @return array 2-dimensional array - every element contains 2 subelements (id, name)
	 */
    public function get_not_created_links() {
        return false;
    	if (empty($this->course_code))
    		die('Error in get_not_created_links() : course code not set');

    	$tbl_grade_links = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINK);

		$sql = 'SELECT id, name from '.$this->get_learnpath_table().' lp 
				WHERE c_id = '.$this->course_id.' AND id NOT IN '
				.' (SELECT ref_id FROM '.$tbl_grade_links
				.' WHERE type = '.LINK_LEARNPATH
				." AND course_code = '".$this->get_course_code()."'"
				.') AND lp.session_id='.api_get_session_id().'';

		$result = Database::query($sql);

		$cats=array();
		while ($data=Database::fetch_array($result)) {
			$cats[] = array ($data['id'], $data['name']);
		}
		return $cats;
    }
	/**
	 * Generate an array of all learnpaths available.
	 * @return array 2-dimensional array - every element contains 2 subelements (id, name)
	 */
    public function get_all_links() {
    	if (empty($this->course_code))
    		die('Error in get_not_created_links() : course code not set');
        
        $session_id = api_get_session_id();
        if (empty($session_id)) {
            $session_condition = api_get_session_condition(0, true);
        } else {
            $session_condition = api_get_session_condition($session_id, true, true);
        }

		$sql = 'SELECT id, name FROM '.$this->get_learnpath_table().' 
                WHERE c_id = '.$this->course_id.' '.$session_condition.' ';
		$result = Database::query($sql);

		$cats = array();
		while ($data=Database::fetch_array($result)) {
			$cats[] = array ($data['id'], $data['name']);
		}
		return $cats;
    }


    /**
     * Has anyone used this learnpath yet ?
     */
    public function has_results() {
    	$tbl_stats = Database::get_course_table(TABLE_LP_VIEW);
		$sql = "SELECT count(id) AS number FROM $tbl_stats
				WHERE c_id = ".$this->course_id." AND lp_id = ".$this->get_ref_id();
    	$result = Database::query($sql);
		$number = Database::fetch_array($result,'NUM');
		return ($number[0] != 0);
    }

    /**
	 * Get the progress of this learnpath. Only the last attempt are taken into account.
	 * @param $stud_id student id (default: all students who have results - then the average is returned)
	 * @return	array (score, max) if student is given
	 * 			array (sum of scores, number of scores) otherwise
	 * 			or null if no scores available
	 */
    public function calc_score($stud_id = null) {    	
    	$tbl_stats = Database::get_course_table(TABLE_LP_VIEW);    	
        $session_id = api_get_session_id();
        
    	$sql = "SELECT * FROM $tbl_stats 
                WHERE   c_id = ".$this->course_id." AND 
                        lp_id = ".$this->get_ref_id()." AND
                        session_id = $session_id ";

    	if (isset($stud_id))
    		$sql .= ' AND user_id = '.intval($stud_id);

    	// order by id, that way the student's first attempt is accessed first
		$sql .= ' ORDER BY view_count DESC';		
    	$scores = Database::query($sql);
		// for 1 student
    	if (isset($stud_id)) {
    		if ($data = Database::fetch_array($scores)) {
    			return array ($data['progress'], 100);
    		} else
    			return null;
    	}
    	// all students -> get average
    	else {
    		$students=array();  // user list, needed to make sure we only
    							// take first attempts into account
			$rescount = 0;
			$sum = 0;

			while ($data=Database::fetch_array($scores)) {
				if (!(array_key_exists($data['user_id'], $students))) {
					$students[$data['user_id']] = $data['progress'];
					$rescount++;
					$sum += ($data['progress'] / 100);
				}
			}

			if ($rescount == 0)
				return null;
			else
				return array ($sum , $rescount);
    	}
    }

    /**
     * Get URL where to go to if the user clicks on the link.
     */
	public function get_link() {
		$url = api_get_path(WEB_PATH).'main/newscorm/lp_controller.php?cidReq='.$this->get_course_code().'&gradebook=view';
        $session_id = api_get_session_id();
		if (!api_is_allowed_to_edit() || $this->calc_score(api_get_user_id()) == null) {
			$url .= '&action=view&session_id='.$session_id.'&lp_id='.$this->get_ref_id();
		} else {
			$url .= '&action=build&session_id='.$session_id.'&lp_id='.$this->get_ref_id();
		}
		return $url;
	}

    /**
     * Get name to display: same as learnpath title
     */
    public function get_name()
    {
    	$data = $this->get_learnpath_data();
    	return $data['name'];
    }

    /**
     * Get description to display: same as learnpath description
     */
    public function get_description()
    {
    	$data = $this->get_learnpath_data();
    	return $data['description'];
    }

    /**
     * Check if this still links to a learnpath
     */
    public function is_valid_link() {
    	$sql = 'SELECT count(id) FROM '.$this->get_learnpath_table().' 
                WHERE c_id = '.$this->course_id.' AND id = '.$this->get_ref_id().' ';
		$result = Database::query($sql);
		$number = Database::fetch_row($result,'NUM');
		return ($number[0] != 0);
    }

    public function get_type_name() {
    	return get_lang('LearningPaths');
    }

	public function needs_name_and_description() {
		return false;
	}

	public function needs_max() {
		return false;
	}

	public function needs_results() {
		return false;
	}

	public function is_allowed_to_change_name() {
		return false;
	}

    // INTERNAL FUNCTIONS

    /**
     * Lazy load function to get the database table of the learnpath
     */
    private function get_learnpath_table() {
    	$this->learnpath_table = Database :: get_course_table(TABLE_LP_MAIN);
   		return $this->learnpath_table;
    }

    /**
     * Lazy load function to get the database contents of this learnpath
     */
    private function get_learnpath_data() {
    	if (!isset($this->learnpath_data)) {
			$sql = 'SELECT * FROM '.$this->get_learnpath_table().' 
                    WHERE c_id = '.$this->course_id.' AND id = '.$this->get_ref_id().' ';
			$result = Database::query($sql);
			$this->learnpath_data=Database::fetch_array($result);
    	}
    	return $this->learnpath_data;
    }

    public function get_icon_name() {
		return 'learnpath';
	}
}