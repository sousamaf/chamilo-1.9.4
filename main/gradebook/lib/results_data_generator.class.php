<?php
/* For licensing terms, see /license.txt */
/**
 * Class to select, sort and transform object data into array data,
 * used for the teacher's evaluation results view
 * @author Bert Steppé
 * @package chamilo.gradebook
 */
/**
 * Class
 * @package chamilo.gradebook
 */
class ResultsDataGenerator
{

	// Sorting types constants
	const RDG_SORT_LASTNAME = 1;
	const RDG_SORT_FIRSTNAME = 2;
	const RDG_SORT_SCORE = 4;
	const RDG_SORT_MASK = 8;

	const RDG_SORT_ASC = 16;
	const RDG_SORT_DESC = 32;


	private $evaluation;
	private $results;
	private $is_course_ind;
	private $include_edit;


	/**
	 * Constructor
	 */
    function ResultsDataGenerator ( $evaluation,
    								$results = array(),
    								$include_edit = false) {
    	$this->evaluation = $evaluation;
		$this->results = (isset($results) ? $results : array());
    }


	/**
	 * Get total number of results (rows)
	 */
	public function get_total_results_count () {
		return count($this->results);
	}


	/**
	 * Get actual array data
	 * @return array 2-dimensional array - each array contains the elements:
	 * 0 ['id']        : user id
	 * 1 ['result_id'] : result id
	 * 2 ['lastname']  : user lastname
	 * 3 ['firstname'] : user firstname
	 * 4 ['score']     : student's score
	 * 5 ['display']   : custom score display (only if custom scoring enabled)
	 */
	public function get_data ($sorting = 0, $start = 0, $count = null, $ignore_score_color = false, $pdf=false) {

		// do some checks on count, redefine if invalid value
		$number_decimals = api_get_setting('gradebook_number_decimals');
		if (!isset($count)) {
			$count = count ($this->results) - $start;
		}
		if ($count < 0) {
			$count = 0;
		}
		$scoredisplay = ScoreDisplay :: instance();
		// generate actual data array
		$table = array();
		foreach($this->results as $result) {
			$user = array();
			$info = get_user_info_from_id($result->get_user_id());
			$user['id'] = $result->get_user_id();
			if ($pdf){
				$user['username'] = $info['username'];
			}
			$user['result_id'] = $result->get_id();
			$user['lastname'] = $info['lastname'];
			$user['firstname'] = $info['firstname'];
			if ($pdf) {
				$user['score'] = $result->get_score();
			} else {
				$user['score'] = $this->get_score_display($result->get_score(),true, $ignore_score_color);
			}
            $user['percentage_score'] = intval($scoredisplay->display_score(array($result->get_score(), $this->evaluation->get_max()), SCORE_PERCENT, SCORE_BOTH, true));
			if ($pdf && $number_decimals == null){				
				$user['scoreletter'] = $result->get_score();
			}			
			if ($scoredisplay->is_custom()) {				
				$user['display'] = $this->get_score_display($result->get_score(), false, $ignore_score_color);				
			}			
			$table[] = $user;
		}


		// sort array
		if ($sorting & self :: RDG_SORT_LASTNAME) {
			usort($table, array('ResultsDataGenerator', 'sort_by_last_name'));
		} elseif ($sorting & self :: RDG_SORT_FIRSTNAME) {
			usort($table, array('ResultsDataGenerator', 'sort_by_first_name'));
		} elseif ($sorting & self :: RDG_SORT_SCORE) {            
			usort($table, array('ResultsDataGenerator', 'sort_by_score'));
		} elseif ($sorting & self :: RDG_SORT_MASK) {
			usort($table, array('ResultsDataGenerator', 'sort_by_mask'));
		}
		if ($sorting & self :: RDG_SORT_DESC) {
			$table = array_reverse($table);
		}
		$return = array_slice($table, $start, $count);		
		return $return;

	}

	private function get_score_display ($score, $realscore, $ignore_score_color) {
		if ($score != null) {
			$scoredisplay = ScoreDisplay :: instance();
			$type = SCORE_CUSTOM;
			if ($realscore === true) {
			    $type = SCORE_DIV_PERCENT ; 
			}			
			return $scoredisplay->display_score(array($score, $this->evaluation->get_max()), $type, SCORE_BOTH, $ignore_score_color);
        }
        return '';		
	}

	// Sort functions - used internally
	function sort_by_last_name($item1, $item2) {
		return api_strcmp($item1['lastname'], $item2['lastname']);
	}

	function sort_by_first_name($item1, $item2) {
		return api_strcmp($item1['firstname'], $item2['firstname']);
	}

	function sort_by_score($item1, $item2) {
		if ($item1['percentage_score'] == $item2['percentage_score']) {
			return 0;
		} else {
			return ($item1['percentage_score'] < $item2['percentage_score'] ? -1 : 1);
		}
	}

	function sort_by_mask ($item1, $item2) {
		$score1 = (isset($item1['score']) ? array($item1['score'],$this->evaluation->get_max()) : null);
		$score2 = (isset($item2['score']) ? array($item2['score'],$this->evaluation->get_max()) : null);
		return ScoreDisplay :: compare_scores_by_custom_display($score1, $score2);
	}
}
