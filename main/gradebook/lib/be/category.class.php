<?php
/* For licensing terms, see /license.txt */
/**
 * Defines a gradebook Category object
 * @package chamilo.gradebook
 */
/**
 * Class
 * @package chamilo.gradebook
 */

require_once api_get_path(LIBRARY_PATH).'skill.lib.php';
require_once api_get_path(LIBRARY_PATH).'gradebook.lib.php';
require_once api_get_path(LIBRARY_PATH).'grade_model.lib.php';

class Category implements GradebookItem
{

    // PROPERTIES
	private $id;
	private $name;
	private $description;
	private $user_id;
	private $course_code;
	private $parent;
	private $weight;
	private $visible;
	private $certificate_min_score;
    private $session_id;
    private $skills = array();
    private $grade_model_id;

    function __construct() {
    }

    // GETTERS AND SETTERS

	public function get_id() {
		return $this->id;
	}

	public function get_name() {
		return $this->name;
	}

	public function get_description() {
		return $this->description;
	}

	public function get_user_id() {
		return $this->user_id;
	}

	public function get_certificate_min_score () {
		if(!empty($this->certificate_min_score)) {
			return $this->certificate_min_score;
		} else {
			return null;
		}
	}

	public function get_course_code() {
		return $this->course_code;
	}

	public function get_parent_id() {
		return $this->parent;
	}

	public function get_weight() {
		return $this->weight;
	}

    public function is_locked() {
		return isset($this->locked) && $this->locked == 1 ? true : false ;
	}

	public function is_visible() {
		return $this->visible;
	}

	public function set_id ($id) {
		$this->id = $id;
	}

	public function set_name ($name) {
		$this->name = $name;
	}

	public function set_description ($description) {
		$this->description = $description;
	}

	public function set_user_id ($user_id) {
		$this->user_id = $user_id;
	}

	public function set_course_code ($course_code) {
		$this->course_code = $course_code;
	}

	public function set_certificate_min_score ($min_score=null) {
		$this->certificate_min_score = $min_score;
	}

	public function set_parent_id ($parent) {
		$this->parent = intval($parent);
	}
	/**
     * Filters to int and sets the session ID
     * @param   int     The session ID from the Dokeos course session
	 */
    public function set_session_id ($session_id = 0) {
        $this->session_id = (int)$session_id;
    }
	public function set_weight ($weight) {
		$this->weight = $weight;
	}

	public function set_visible ($visible) {
		$this->visible = $visible;
	}

    public function set_grade_model_id ($id) {
		$this->grade_model_id = $id;
	}
    public function set_locked ($locked) {
		$this->locked = $locked;
	}
    public function get_grade_model_id () {
		return $this->grade_model_id;
	}

	public function get_type() {
		return 'category';
	}

    public function get_skills($from_db = true) {
        if ($from_db) {
            $cat_id = $this->get_id();

            $gradebook = new Gradebook();
            $skills = $gradebook->get_skills_by_gradebook($cat_id);
        } else {
            $skills = $this->skills;
        }
        return $skills;
    }

    public function get_skills_for_select() {
        $skills = $this->get_skills();
        $skill_select = array();
        if (!empty($skills)) {
            foreach($skills as $skill) {
                $skill_select[$skill['id']] = $skill['name'];
            }
        }
        return $skill_select;
    }

    // CRUD FUNCTIONS

	/**
	 * Retrieve categories and return them as an array of Category objects
	 * @param int      category id
	 * @param int      user id (category owner)
	 * @param string   course code
	 * @param int      parent category
	 * @param bool     visible
     * @param int      session id (in case we are in a session)
     * @param bool     Whether to show all "session" categories (true) or hide them (false) in case there is no session id
	 */


	public static function load($id = null, $user_id = null, $course_code = null, $parent_id = null, $visible = null, $session_id = null, $order_by = null) {
        //if the category given is explicitly 0 (not null), then create
        // a root category object (in memory)
		if ( isset($id) && (int)$id === 0 ) {
			$cats = array();
			$cats[] = Category::create_root_category();
			return $cats;
		}

		$tbl_grade_categories = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
		$sql = 'SELECT * FROM '.$tbl_grade_categories;
		$paramcount = 0;
		if (isset($id)) {
			$id = Database::escape_string($id);
			$sql.= ' WHERE id = '.intval($id);
			$paramcount ++;
		}
		if (isset($user_id)) {
			$user_id = intval($user_id);
			if ($paramcount != 0) {
                $sql .= ' AND';
			} else {
                $sql .= ' WHERE';
			}
			$sql .= ' user_id = '.intval($user_id);
			$paramcount ++;
		}

		if (isset($course_code)) {
			$course_code = Database::escape_string($course_code);
			if ($paramcount != 0) {
                $sql .= ' AND';
            } else {
                $sql .= ' WHERE';
            }

			if ($course_code == '0') {
                $sql .= ' course_code is null ';
            } else {
                $sql .= " course_code = '".Database::escape_string($course_code)."'";
            }

            /*if ($show_session_categories !== true) {
                // a query on the course should show all
                // the categories inside sessions for this course
                // otherwise a special parameter is given to ask explicitely
                $sql .= " AND (session_id IS NULL OR session_id = 0) ";
            } else {*/

                if (empty($session_id)) {
                    $sql .= ' AND (session_id IS NULL OR session_id = 0) ';
                } else {
                    $sql .= ' AND session_id = '.(int) $session_id.' ';
                }
            //}
			$paramcount ++;
		}

		if (isset($parent_id)) {
			$parent_id = Database::escape_string($parent_id);
			if ($paramcount != 0) {
                $sql .= ' AND ';
			} else {
                $sql .= ' WHERE ';
			}
			$sql .= ' parent_id = '.intval($parent_id);
			$paramcount ++;
		}
		if (isset($visible)) {
			$visible = Database::escape_string($visible);
			if ($paramcount != 0) {
			$sql .= ' AND';
			} else {
			$sql .= ' WHERE';
			}
			$sql .= ' visible = '.intval($visible);
			$paramcount ++;
		}

        if (!empty($order_by)) {
            if (!empty($order_by) && $order_by != '') {
                $sql .= ' '.$order_by;
            }
        }
		$result = Database::query($sql);
        $allcat = array();
		if (Database::num_rows($result) > 0) {
			$allcat = Category::create_category_objects_from_sql_result($result);
		}
		return $allcat;
	}

	private function create_root_category() {
		$cat= new Category();
		$cat->set_id(0);
		$cat->set_name(get_lang('RootCat'));
		$cat->set_description(null);
		$cat->set_user_id(0);
		$cat->set_course_code(null);
		$cat->set_parent_id(null);
		$cat->set_weight(0);
		$cat->set_visible(1);
		return $cat;
	}

	private static function create_category_objects_from_sql_result($result) {
		$allcat=array();
		while ($data=Database::fetch_array($result)) {
			$cat= new Category();
			$cat->set_id($data['id']);
			$cat->set_name($data['name']);
			$cat->set_description($data['description']);
			$cat->set_user_id($data['user_id']);
			$cat->set_course_code($data['course_code']);
			$cat->set_parent_id($data['parent_id']);
			$cat->set_weight($data['weight']);
			$cat->set_visible($data['visible']);
            $cat->set_session_id($data['session_id']);
			$cat->set_certificate_min_score($data['certif_min_score']);
            $cat->set_grade_model_id($data['grade_model_id']);
            $cat->set_locked($data['locked']);
			$allcat[]=$cat;
		}
		return $allcat;
	}

    /**
     * Insert this category into the database
     */
	public function add() {
		if ( isset($this->name) && '-1'==$this->name) {
			return false;
		}

		if (isset($this->name) && isset($this->user_id)) {
			$tbl_grade_categories = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
			$sql = 'INSERT INTO '.$tbl_grade_categories.' (name,user_id,weight,visible';
			if (isset($this->description)) {
				$sql .= ',description';
			}
			if (isset($this->course_code)) {
				$sql .= ',course_code';
			}
			if (isset($this->parent)) {
				 $sql .= ',parent_id';
			}
            if (!empty($this->session_id)) {
            	$sql .= ', session_id';
            }

            if (isset($this->grade_model_id)) {
                $sql .= ', grade_model_id ';
            }

            /*
            $setting = api_get_setting('tool_visible_by_default_at_creation');
            $visible = 1;
            if (isset($setting['gradebook'])) {
                if ($setting['gradebook'] == 'false') {
                    $visible = 0;
                }
            }*/

            $visible = intval($this->is_visible());

			$sql .= ") VALUES ('".Database::escape_string($this->get_name())."'"
					.','.intval($this->get_user_id())
					.','.Database::escape_string($this->get_weight())
					.','.$visible;
			if (isset($this->description)) {
				$sql .= ",'".Database::escape_string($this->get_description())."'";
			}
			if (isset($this->course_code)) {
				$sql .= ",'".Database::escape_string($this->get_course_code())."'";
			}
			if (isset($this->parent)) {
				 $sql .= ','.intval($this->get_parent_id());
			}
            if (!empty($this->session_id)) {
                $sql .= ', '.intval($this->get_session_id());
            }
            if (isset($this->grade_model_id)) {
                $sql .= ', '.intval($this->get_grade_model_id());
            }
			$sql .= ')';
			Database::query($sql);
			$id = Database::insert_id();
			$this->set_id($id);

            if (!empty($id)) {

                $parent_id = $this->get_parent_id();
                $grade_model_id = $this->get_grade_model_id();
                if ($parent_id == 0) {
                    //do something
                    if (isset($grade_model_id) && !empty($grade_model_id) && $grade_model_id != '-1') {
                        $obj = new GradeModel();
                        $components = $obj->get_components($grade_model_id);
                        $default_weight_setting = api_get_setting('gradebook_default_weight');
                        $default_weight = 100;
                        if (isset($default_weight_setting)) {
                            $default_weight = $default_weight_setting;
                        }
                        foreach ($components as $component) {
                            $gradebook =  new Gradebook();
                            $params = array();

                            $params['name']             = $component['acronym'];
                            $params['description']      = $component['title'];
                            $params['user_id']          = api_get_user_id();
                            $params['parent_id']        = $id;
                            $params['weight']           = $component['percentage']/100*$default_weight;
                            $params['session_id']       = api_get_session_id();
                            $params['course_code']      = $this->get_course_code();

                            $gradebook->save($params);
                        }
                    }
                }
            }

            $gradebook= new Gradebook();
            $gradebook->update_skills_to_gradebook($this->id, $this->get_skills(false));

			return $id;
		}
	}

	/**
	 * Update the properties of this category in the database
     * @todo fix me
	 */
	public function save() {
		$tbl_grade_categories = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);

		$sql = 'UPDATE '.$tbl_grade_categories." SET name = '".Database::escape_string($this->get_name())."'".', description = ';
		if (isset($this->description)) {
			$sql .= "'".Database::escape_string($this->get_description())."'";
		} else {
			$sql .= 'null';
		}
		$sql .= ', user_id = '.intval($this->get_user_id())
			.', course_code = ';
		if (isset($this->course_code)) {
			$sql .= "'".Database::escape_string($this->get_course_code())."'";
		} else {
			$sql .= 'null';
		}
		$sql .=	', parent_id = ';
		if (isset($this->parent)) {
			$sql .= intval($this->get_parent_id());
		} else {
			$sql .= 'null';
		}
		$sql .= ', certif_min_score = ';
		if (isset($this->certificate_min_score) && !empty($this->certificate_min_score)) {
			$sql .= Database::escape_string($this->get_certificate_min_score());
		} else {
			$sql .= 'null';
		}
        if (isset($this->grade_model_id)) {
            $sql .= ', grade_model_id = '.intval($this->get_grade_model_id());
        }
		$sql .= ', weight = '.Database::escape_string($this->get_weight())
			.', visible = '.intval($this->is_visible())
			.' WHERE id = '.intval($this->id);

		Database::query($sql);

        if (!empty($this->id)) {
            $parent_id = $this->get_parent_id();
            $grade_model_id = $this->get_grade_model_id();
            if ($parent_id == 0) {

                if (isset($grade_model_id) && !empty($grade_model_id) && $grade_model_id != '-1') {
                    $obj = new GradeModel();
                    $components = $obj->get_components($grade_model_id);
                    $default_weight_setting = api_get_setting('gradebook_default_weight');
                    $default_weight = 100;
                    if (isset($default_weight_setting)) {
                        $default_weight = $default_weight_setting;
                    }
                    $final_weight = $this->get_weight();
                    if (!empty($final_weight)) {
                        $default_weight = $this->get_weight();
                    }
                    foreach ($components as $component) {
                        $gradebook = new Gradebook();
                        $params = array();

                        $params['name']             = $component['acronym'];
                        $params['description']      = $component['title'];
                        $params['user_id']          = api_get_user_id();
                        $params['parent_id']        = $this->id;
                        $params['weight']           = $component['percentage']/100*$default_weight;
                        $params['session_id']       = api_get_session_id();
                        $params['course_code']      = $this->get_course_code();

                        $gradebook->save($params);
                    }
                }
            }
        }




        $gradebook= new Gradebook();
        $gradebook->update_skills_to_gradebook($this->id, $this->get_skills(false));

	}

    /**
     * Update link weights see #5168
     * @param type $new_weight
     */
    function update_children_weight($new_weight) {

        //$evals = $this->get_evaluations();
        $links = $this->get_links();
        $old_weight = $this->get_weight();

        if (!empty($links)) {
            foreach ($links as $link_item) {
                if (isset($link_item)) {
                    $new_item_weight =  $new_weight * $link_item->get_weight() / $old_weight;
                    $link_item->set_weight($new_item_weight);
                    $link_item->save();
                }
            }
        }
    }


	/**
	 * Delete this evaluation from the database
	 */
	public function delete() {
		$tbl_grade_categories = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
		$sql = 'DELETE FROM '.$tbl_grade_categories.' WHERE id = '.intval($this->id);
		Database::query($sql);
	}
	/**
	 * Not delete this category from the database,when visible=3 is category eliminated
	 */
	public function update_category_delete($course_id){
		$tbl_grade_categories = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
		$sql = 'UPDATE '.$tbl_grade_categories.' SET visible=3 WHERE course_code ="'.Database::escape_string($course_id).'"';
		Database::query($sql);
	}
	/**
	 * Show message resource delete
	 */
	public function show_message_resource_delete($course_id) {
		$tbl_grade_categories = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
		$sql = 'SELECT count(*) AS num from '.$tbl_grade_categories.' WHERE course_code ="'.Database::escape_string($course_id).'" AND visible=3';
		$res=Database::query($sql);
		$option=Database::fetch_array($res,'ASSOC');
		if ($option['num']>=1) {
			return '&nbsp;&nbsp;<span class="resource-deleted">(&nbsp;'.get_lang('ResourceDeleted').'&nbsp;)</span>';
		} else {
			return false;
		}
	}

	/**
	 * Shows all information of an category
	 */
	 public function shows_all_information_an_category ($selectcat='') {
	 	if($selectcat=='') {
	 		return null;
	 	} else {
		 	$tbl_category=Database :: get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
		 	$sql='SELECT name,description,user_id,course_code,parent_id,weight,visible,certif_min_score,session_id FROM '.$tbl_category.' c WHERE c.id='.intval($selectcat);
		 	$result=Database::query($sql);
		 	$row=Database::fetch_array($result,'ASSOC');
		 	return $row;
	 	}
	 }
// OTHER FUNCTIONS

	/**
	 * Check if a category name (with the same parent category) already exists
	 * @param $name name to check (if not given, the name property of this object will be checked)
	 * @param $parent parent category
	 */
	public function does_name_exist($name, $parent) {
		if (!isset ($name)) {
			$name = $this->name;
			$parent = $this->parent;
		}
		$tbl_grade_categories = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
		$sql = 'SELECT count(id) AS number'
			 .' FROM '.$tbl_grade_categories
			 ." WHERE name = '".Database::escape_string($name)."'";

		if (api_is_allowed_to_edit()) {
			$parent = Category::load($parent);
			$code = $parent[0]->get_course_code();
			if (isset($code) && $code != '0') {
				$main_course_user_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
				$sql .= ' AND user_id IN ('
						.' SELECT user_id FROM '.$main_course_user_table
						." WHERE course_code = '".Database::escape_string($code)."'"
						.' AND status = '.COURSEMANAGER
						.')';
			} else {
				$sql .= ' AND user_id = '.api_get_user_id();
			}

		} else {
			$sql .= ' AND user_id = '.api_get_user_id();
		}
		if (!isset ($parent)) {
			$sql.= ' AND parent_id is null';
		} else {
			$sql.= ' AND parent_id = '.intval($parent);
		}

    	$result = Database::query($sql);
		$number = Database::fetch_row($result);
		return ($number[0] != 0);
	}

	/**
	 * Checks if the certificate is available for the given user in this category
	 * @param	integer	User ID
	 * @return	boolean	True if conditions match, false if fails
	 */
	public function is_certificate_available($user_id) {
		$score = $this->calc_score($user_id, $this->course_code);
        if (isset($score)) {
            $certification_score = ($score[0]/$score[1])*100; //get a percentage score to compare to minimum certificate score
    		if ($certification_score >= $this->certificate_min_score) {
    			return true;
    		}
        }
		return false;
	}

	/**
	 * Is this category a course ?
	 * A category is a course if it has a course code and no parent category.
	 */
    public function is_course() {
    	return (isset($this->course_code) && !empty($this->course_code)
    		&& (!isset($this->parent) || $this->parent == 0));
    }

	/**
	 * Calculate the score of this category
	 * @param $stud_id student id (default: all students - then the average is returned)
	 * @return	array (score sum, weight sum)
	 * 			or null if no scores available
	 */
	public function calc_score ($stud_id = null, $course_code = '', $session_id = null) {
		// get appropriate subcategories, evaluations and links
		if (!empty($course_code)) {
			$cats  = $this->get_subcategories($stud_id, $course_code, $session_id);
			$evals = $this->get_evaluations($stud_id, false, $course_code);
			$links = $this->get_links($stud_id, false, $course_code);
		} else {
			$cats  = $this->get_subcategories($stud_id);
			$evals = $this->get_evaluations($stud_id);
			$links = $this->get_links($stud_id);
		}

		// calculate score
		$rescount     = 0;
		$ressum       = 0;
		$weightsum    = 0;
        /*$debug = false;

        if ($stud_id == 11) {
            $debug = true;
        }
        if ($debug) var_dump($links);*/

		if (!empty($cats)) {
			foreach ($cats as $cat) {
				$catres = $cat->calc_score($stud_id, $course_code, $session_id);     // recursive call
				if ($cat->get_weight() != 0) {
					$catweight = $cat->get_weight();
					$rescount++;
					$weightsum += $catweight;
				}
                if (isset($catres)) {
                    $ressum += (($catres[0]/$catres[1]) * $catweight);
                }
			}
		}

		if (!empty($evals)) {
			foreach ($evals as $eval) {
				$evalres = $eval->calc_score($stud_id);
				if (isset($evalres) && $eval->get_weight() != 0) {
					$evalweight = $eval->get_weight();
					$rescount++;
					$weightsum += $evalweight;
					$ressum += (($evalres[0]/$evalres[1]) * $evalweight);
				} else {

				}
			}
		}

		if (!empty($links)) {
			foreach ($links as $link) {
				$linkres = $link->calc_score($stud_id);
                //if ($debug) var_dump($linkres);

				if (isset($linkres) && $link->get_weight() != 0) {
					$linkweight     = $link->get_weight();
					$link_res_denom = ($linkres[1]==0) ? 1 : $linkres[1];
					$rescount++;
					$weightsum += $linkweight;
					$ressum += (($linkres[0]/$link_res_denom) * $linkweight);
				} else {
                    //adding if result does not exists
                    if ($link->get_weight() != 0) {
                        $linkweight     = $link->get_weight();
                        $weightsum += $linkweight;
                    }
                }
			}
		}

		if ($rescount == 0) {
			return null;
		} else {
			return array ($ressum, $weightsum);
		}
	}

	/**
	 * Delete this category and every subcategory, evaluation and result inside
	 */
	public function delete_all () {
		$cats = Category::load(null, null, $this->course_code, $this->id, null);
		$evals = Evaluation::load(null, null, $this->course_code, $this->id, null);
		$links = LinkFactory::load(null,null,null,null,$this->course_code,$this->id,null);
		if (!empty($cats)) {
			foreach ($cats as $cat) {
				$cat->delete_all();
				$cat->delete();
			}
		}
		if (!empty($evals)) {
			foreach ($evals as $eval) {
				$eval->delete_with_results();
			}
		}
		if (!empty($links)) {
			foreach ($links as $link) {
				$link->delete();
			}
		}
		$this->delete();
	}


	/**
	 * Return array of Category objects where a student is subscribed to.
	 * @param int       student id
     * @param string    Course code
     * @param int       Session id
	 */
	public function get_root_categories_for_student ($stud_id, $course_code = null, $session_id = null) {
		// courses

		$main_course_user_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
		$tbl_grade_categories = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);

		$sql = 'SELECT *'
				.' FROM '.$tbl_grade_categories
				.' WHERE parent_id = 0';
		if (!api_is_allowed_to_edit()) {
			$sql .= ' AND visible = 1';
            //proceed with checks on optional parameters course & session
            if (!empty($course_code)) {
                // TODO: considering it highly improbable that a user would get here
                // if he doesn't have the rights to view this course and this
                // session, we don't check his registration to these, but this
                // could be an improvement
                if (!empty($session_id)) {
                    $sql .= " AND course_code  = '".Database::escape_string($course_code)."'"
                            ." AND session_id = ".(int)$session_id;
                } else {
                    $sql .= " AND course_code  = '".Database::escape_string($course_code)."' AND session_id is null OR session_id=0";
                }
            } else {
                //no optional parameter, proceed as usual
                $sql .= ' AND course_code in'
                    .' (SELECT course_code'
                    .' FROM '.$main_course_user_table
                    .' WHERE user_id = '.intval($stud_id)
                    .' AND status = '.STUDENT
                    .')';
            }
        } elseif (api_is_allowed_to_edit() && !api_is_platform_admin()) {
            //proceed with checks on optional parameters course & session
            if (!empty($course_code)) {
                // TODO: considering it highly improbable that a user would get here
                // if he doesn't have the rights to view this course and this
                // session, we don't check his registration to these, but this
                // could be an improvement
                $sql .= " AND course_code  = '".Database::escape_string($course_code)."'";
                if (!empty($session_id)) {
                    $sql .= " AND session_id = ".(int)$session_id;
                } else {
                	$sql .="AND session_id IS NULL OR session_id=0";
                }
            } else {
    			$sql .= ' AND course_code in'
    					.' (SELECT course_code'
    					.' FROM '.$main_course_user_table
    					.' WHERE user_id = '.api_get_user_id()
    					.' AND status = '.COURSEMANAGER
    					.')';
            }
        }elseif (api_is_platform_admin()) {
        	if (isset($session_id) && $session_id!=0) {
        		$sql.=' AND session_id='.intval($session_id);
        	} else {
        		$sql.=' AND coalesce(session_id,0)=0';
        	}


        }
		$result = Database::query($sql);
		$cats = Category::create_category_objects_from_sql_result($result);

		// course independent categories
        if (empty($course_code)) {
		  $cats = Category::get_independent_categories_with_result_for_student (0, $stud_id, $cats);
        }
		return $cats;

	}

	/**
	 * Return array of Category objects where a teacher is admin for.
	 * @param int user id (to return everything, use 'null' here)
     * @param string course code (optional)
     * @param int session id (optional)
	 */
	public function get_root_categories_for_teacher ($user_id, $course_code = null, $session_id = null) {
		if ($user_id == null) {
			return Category::load(null,null,$course_code,0,null,$session_id);
        }

		// courses

		$main_course_user_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
		$tbl_grade_categories = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);

		$sql = 'SELECT *'
				.' FROM '.$tbl_grade_categories
				.' WHERE parent_id = 0';
        if (!empty($course_code)) {
            $sql .= " AND course_code = '".Database::escape_string($course_code)."' ";
            if (!empty($session_id)) {
            	$sql .= " AND session_id = ".(int)$session_id;
            }
        } else {
			$sql .= ' AND course_code in'
				.' (SELECT course_code'
				.' FROM '.$main_course_user_table
				.' WHERE user_id = '.intval($user_id)
				.')';
        }
		$result = Database::query($sql);
		$cats = Category::create_category_objects_from_sql_result($result);
		// course independent categories
		if (isset($course_code)) {
			$indcats = Category::load(null,$user_id,$course_code,0,null,$session_id);
			$cats = array_merge($cats, $indcats);
		}
		return $cats;
	}


	/**
	 * Can this category be moved to somewhere else ?
	 * The root and courses cannot be moved.
	 */
	public function is_movable () {
		return (!(!isset ($this->id) || $this->id == 0 || $this->is_course()));
	}

	/**
	 * Generate an array of possible categories where this category can be moved to.
	 * Notice: its own parent will be included in the list: it's up to the frontend
	 * to disable this element.
	 * @return array 2-dimensional array - every element contains 3 subelements (id, name, level)
	 */
	public function get_target_categories() {
		// the root or a course -> not movable
		if (!$this->is_movable()) {
			return null;
		}
		// otherwise:
		// - course independent category
		//   -> movable to root or other independent categories
		// - category inside a course
		//   -> movable to root, independent categories or categories inside the course
		else {
			$user = (api_is_platform_admin() ? null : api_get_user_id());
			$targets = array();
			$level = 0;

			$root = array(0, get_lang('RootCat'), $level);
			$targets[] = $root;

			if (isset($this->course_code) && !empty($this->course_code)) {
				$crscats = Category::load(null,null,$this->course_code,0);
				foreach ($crscats as $cat) {
					if ($this->can_be_moved_to_cat($cat)) {
						$targets[] = array ($cat->get_id(), $cat->get_name(), $level+1);
						$targets = $this->add_target_subcategories($targets, $level+1, $cat->get_id());
					}
				}
			}

			$indcats = Category::load(null,$user,0,0);
			foreach ($indcats as $cat) {
				if ($this->can_be_moved_to_cat($cat)) {
					$targets[] = array ($cat->get_id(), $cat->get_name(), $level+1);
					$targets = $this->add_target_subcategories($targets, $level+1, $cat->get_id());
				}
			}

			return $targets;
		}
	}

	/**
	 * Internal function used by get_target_categories()
	 */
	private function add_target_subcategories($targets, $level, $catid) {
		$subcats = Category::load(null,null,null,$catid);
		foreach ($subcats as $cat) {
			if ($this->can_be_moved_to_cat($cat)) {
				$targets[] = array ($cat->get_id(), $cat->get_name(), $level+1);
				$targets = $this->add_target_subcategories($targets, $level+1, $cat->get_id());
			}
		}
		return $targets;
	}

	/**
	 * Internal function used by get_target_categories() and add_target_subcategories()
	 * Can this category be moved to the given category ?
	 * Impossible when origin and target are the same... children won't be processed
	 * either. (a category can't be moved to one of its own children)
	 */
	private function can_be_moved_to_cat ($cat) {
		return ($cat->get_id() != $this->get_id());
	}

	/**
	 * Move this category to the given category.
	 * If this category moves from inside a course to outside,
	 * its course code must be changed, as well as the course code
	 * of all underlying categories and evaluations. All links will
	 * be deleted as well !
	 */
	public function move_to_cat ($cat) {
		$this->set_parent_id($cat->get_id());
		if ($this->get_course_code() != $cat->get_course_code()) {
			$this->set_course_code($cat->get_course_code());
			$this->apply_course_code_to_children();
		}
		$this->save();
	}

	/**
	 * Internal function used by move_to_cat()
	 */
	private function apply_course_code_to_children () {
		$cats = Category::load(null, null, null, $this->id, null);
		$evals = Evaluation::load(null, null, null, $this->id, null);
		$links = LinkFactory::load(null,null,null,null,null,$this->id,null);

		foreach ($cats as $cat) {
			$cat->set_course_code($this->get_course_code());
			$cat->save();
			$cat->apply_course_code_to_children();
		}

		foreach ($evals as $eval) {
			$eval->set_course_code($this->get_course_code());
			$eval->save();
		}

		foreach ($links as $link) {
			$link->delete();
		}

	}



	/**
	 * Generate an array of all categories the user can navigate to
	 */
	public function get_tree () {
		$targets = array();
		$level = 0;

		$root = array(0, get_lang('RootCat'), $level);
		$targets[] = $root;

		// course or platform admin
		if (api_is_allowed_to_edit()) {
			$user = (api_is_platform_admin() ? null : api_get_user_id());

			$cats = Category::get_root_categories_for_teacher($user);
			foreach ($cats as $cat) {
				$targets[] = array ($cat->get_id(), $cat->get_name(), $level+1);
				$targets = Category::add_subtree($targets, $level+1, $cat->get_id(),null);
			}
		}
		else {	// student
			$cats = Category::get_root_categories_for_student(api_get_user_id());
			foreach ($cats as $cat) {
				$targets[] = array ($cat->get_id(), $cat->get_name(), $level+1);
				$targets = Category::add_subtree($targets, $level+1, $cat->get_id(), 1);
			}
		}

		return $targets;
	}

	/**
	 * Internal function used by get_tree()
	 */
	private function add_subtree ($targets, $level, $catid, $visible) {
		$subcats = Category::load(null,null,null,$catid,$visible);

		if (!empty($subcats)) {
			foreach ($subcats as $cat) {
				$targets[] = array ($cat->get_id(), $cat->get_name(), $level+1);
				$targets = Category::add_subtree($targets, $level+1, $cat->get_id(),$visible);
			}
		}
		return $targets;
	}

	/**
	 * Generate an array of courses that a teacher hasn't created a category for.
	 * @return array 2-dimensional array - every element contains 2 subelements (code, title)
	 */
	public function get_not_created_course_categories ($user_id) {
		$tbl_main_courses = Database :: get_main_table(TABLE_MAIN_COURSE);
		$tbl_main_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
		$tbl_grade_categories = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);

		$sql = 'SELECT DISTINCT(code), title FROM '.$tbl_main_courses.' cc, '.$tbl_main_course_user.' cu'
				.' WHERE cc.code = cu.course_code'
				.' AND cu.status = '.COURSEMANAGER;
		if (!api_is_platform_admin()) {
		$sql .= ' AND cu.user_id = '.$user_id;
		}
		$sql .= ' AND cc.code NOT IN'
				.' (SELECT course_code FROM '.$tbl_grade_categories
				.' WHERE parent_id = 0'
//				.' AND user_id = '.$user_id
				.' AND course_code IS NOT null)';
		$result = Database::query($sql);

		$cats=array();
		while ($data=Database::fetch_array($result)) {
			$cats[] = array ($data['code'], $data['title']);
		}
		return $cats;

	}

	/**
	 * Generate an array of all courses that a teacher is admin of.
	 * @return array 2-dimensional array - every element contains 2 subelements (code, title)
	 */
	public function get_all_courses ($user_id) {
		$tbl_main_courses = Database :: get_main_table(TABLE_MAIN_COURSE);
		$tbl_main_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
		$tbl_grade_categories = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);

		$sql = 'SELECT DISTINCT(code), title FROM '.$tbl_main_courses.' cc, '.$tbl_main_course_user.' cu'
				.' WHERE cc.code = cu.course_code'
				.' AND cu.status = '.COURSEMANAGER;
		if (!api_is_platform_admin()) {
		$sql .= ' AND cu.user_id = '.intval($user_id);
		}

		$result = Database::query($sql);

		$cats=array();
		while ($data=Database::fetch_array($result)) {
			$cats[] = array ($data['code'], $data['title']);
		}
		return $cats;

	}

	/**
	 * Apply the same visibility to every subcategory, evaluation and link
	 */
	public function apply_visibility_to_children () {
		$cats = Category::load(null, null, null, $this->id, null);
		$evals = Evaluation::load(null, null, null, $this->id, null);
		$links = LinkFactory::load(null,null,null,null,null,$this->id,null);
		if (!empty($cats)) {
			foreach ($cats as $cat) {
				$cat->set_visible($this->is_visible());
				$cat->save();
				$cat->apply_visibility_to_children();
			}
		}
		if (!empty($evals)) {
			foreach ($evals as $eval) {
				$eval->set_visible($this->is_visible());
				$eval->save();
			}
		}
		if (!empty($links)) {
			foreach ($links as $link) {
				$link->set_visible($this->is_visible());
				$link->save();
			}
		}
	}


	/**
	 * Check if a category contains evaluations with a result for a given student
	 */
	public function has_evaluations_with_results_for_student ($stud_id) {
		$evals = Evaluation::get_evaluations_with_result_for_student($this->id, $stud_id);
		if (count($evals) != 0) {
			return true;
		} else {
			$cats = Category::load(null, null, null, $this->id,
						api_is_allowed_to_edit() ? null : 1);
			foreach ($cats as $cat) {
				if ($cat->has_evaluations_with_results_for_student ($stud_id)) {
					return true;
				}

			}
			return false;
		}
	}

	/**
	 * Retrieve all categories inside a course independent category
	 * that should be visible to a student.
	 * @param $cat_id parent category
	 * @param $stud_id student id
	 * @param $cats optional: if defined, the categories will be added to this array
	 */
    public function get_independent_categories_with_result_for_student ($cat_id, $stud_id, $cats = array()) {
    	$creator = (api_is_allowed_to_edit() && !api_is_platform_admin()) ? api_get_user_id() : null;

		$crsindcats = Category::load(null,$creator,'0',$cat_id,
						api_is_allowed_to_edit() ? null : 1);

		if (!empty($crsindcats)) {
			foreach ($crsindcats as $crsindcat) {
				if ($crsindcat->has_evaluations_with_results_for_student($stud_id)) {
					$cats[] = $crsindcat;
				}
			}
		}
		return $cats;
    }

    /**
     * Return the session id (in any case, even if it's null or 0)
     * @return  int Session id (can be null)
     */
    public function get_session_id() {
        return $this->session_id;
    }


	/**
	 * Get appropriate subcategories visible for the user (and optionally the course and session)
	 * @param int      $stud_id student id (default: all students)
     * @param string   Course code (optional)
     * @param int      Session ID (optional)
     * @return  array   Array of subcategories
	 */
	public function get_subcategories ($stud_id = null, $course_code = null, $session_id = null, $order = null) {
		// 1 student
 		if (isset($stud_id)) {
			// special case: this is the root
			if ($this->id == 0) {
				return Category::get_root_categories_for_student ($stud_id, $course_code, $session_id);
			} else {
				return Category::load(null,null, $course_code, $this->id, api_is_allowed_to_edit() ? null : 1, $session_id, $order);
			}
		} else {// all students
			// course admin
			if (api_is_allowed_to_edit() && !api_is_platform_admin()) {
				// root
				if ($this->id == 0) {
					return $this->get_root_categories_for_teacher(api_get_user_id(), $course_code, $session_id, false);
				// inside a course
                } elseif (!empty($this->course_code)) {
					return Category::load(null, null, $this->course_code, $this->id, null, $session_id, $order);
                } elseif (!empty($course_code)) {
                    return Category::load(null, null, $course_code, $this->id, null, $session_id, $order);
				// course independent
                } else {
					return Category::load(null, api_get_user_id(), 0, $this->id, null);
                }
			} elseif (api_is_platform_admin()) {
                // platform admin
                //we explicitly avoid listing subcats from another session
				return Category::load(null, null, $course_code, $this->id, null, $session_id, $order);
            }
		}
		return array();
	}

	/**
	 * Get appropriate evaluations visible for the user
	 * @param int $stud_id student id (default: all students)
	 * @param boolean $recursive process subcategories (default: no recursion)
	 */
	public function get_evaluations ($stud_id = null, $recursive = false, $course_code = '') {
		$evals = array();

		if (empty($course_code)) {
			$course_code = api_get_course_id();
		}

		// 1 student
		if (isset($stud_id) && !empty($stud_id)) {
			// special case: this is the root
			if ($this->id == 0) {
				$evals = Evaluation::get_evaluations_with_result_for_student(0,$stud_id);
			} else {
                $evals = Evaluation::load(null,null, $course_code, $this->id, api_is_allowed_to_edit() ? null : 1);
			}
		} else {// all students
			// course admin
			if ((api_is_allowed_to_edit() || api_is_drh() || api_is_session_admin()) && !api_is_platform_admin()) {
				// root
				if ($this->id == 0) {
					$evals = Evaluation::load(null, api_get_user_id(), null, $this->id, null);
				} elseif (isset($this->course_code) && !empty($this->course_code)) {
                    // inside a course
					$evals = Evaluation::load(null, null, $course_code, $this->id, null);
				} else {
                    // course independent
					$evals = Evaluation::load(null, api_get_user_id(), null, $this->id, null);
				}
			} elseif (api_is_platform_admin()) {
                //platform admin
				$evals = Evaluation::load(null, null, $course_code, $this->id, null);
			}
		}

		if ($recursive) {
			$subcats = $this->get_subcategories($stud_id, $course_code);
			if (!empty($subcats)) {
				foreach ($subcats as $subcat) {
					$subevals = $subcat->get_evaluations($stud_id, true, $course_code);
					//$this->debugprint($subevals);
					$evals = array_merge($evals, $subevals);
				}
			}
		}
		return $evals;

	}

	/**
	 * Get appropriate links visible for the user
	 * @param int $stud_id student id (default: all students)
	 * @param boolean $recursive process subcategories (default: no recursion)
	 */
	public function get_links ($stud_id = null, $recursive = false, $course_code = '') {
		$links = array();

		if (empty($course_code)) {
			$course_code = api_get_course_id();
		}

		// no links in root or course independent categories
		if ($this->id == 0) {

		} elseif (isset($stud_id)) {
            // 1 student $stud_id
			$links = LinkFactory::load(null,null,null,null,empty($this->course_code)?null:$course_code, $this->id,
						api_is_allowed_to_edit() ? null : 1);
 		} elseif (api_is_allowed_to_edit() || api_is_drh() || api_is_session_admin()) {
            // all students -> only for course/platform admin
			$links = LinkFactory::load(null,null,null,null,empty($this->course_code)?null:$this->course_code,$this->id, null);
		}


		if ($recursive) {
			$subcats = $this->get_subcategories($stud_id, $course_code);
			if (!empty($subcats)) {
				foreach ($subcats as $subcat) {
					$sublinks = $subcat->get_links($stud_id, false, $course_code);
					$links = array_merge($links, $sublinks);
				}
			}
		}
		return $links;

	}


// Other methods implementing GradebookItem

	public function get_item_type() {
		return 'C';
	}

    public function set_skills($skills) {
        $this->skills = $skills;
    }

	public function get_date() {
		return null;
	}

	public function get_icon_name() {
		return 'cat';
	}
	 /**
     * Find category by name
     * @param string $name_mask search string
     * @return array category objects matching the search criterium
     */
    public function find_category ($name_mask,$allcat) {
		$foundcats = array();
		foreach ($allcat as $search_cat) {
			if (!(strpos(strtolower($search_cat->get_name()), strtolower($name_mask)) === false)) {
				$foundcats[] = $search_cat;
			}
		}
		return $foundcats;
    }

    /**
  	 * This function, locks a category , only one who can unlock it is the platform administrator.
  	 * @param int locked 1 or unlocked 0
  	 * @return bool
  	 *
  	 * */
  	function lock($locked) {
  		$table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
  		$sql = "UPDATE $table SET locked = '".intval($locked)."' WHERE id='".intval($this->id)."'";
  		Database::query($sql);
  	}

    function lock_all_items($locked) {
        if (api_get_setting('gradebook_locking_enabled') == 'true') {
            $this->lock($locked);

            $evals_to_lock = $this->get_evaluations();

            if (!empty($evals_to_lock)) {
                foreach ($evals_to_lock as $item) {
                    $item->lock($locked);
                }
            }

            $link_to_lock= $this->get_links();
            if (!empty($link_to_lock)) {
                foreach ($link_to_lock as $item ) {
                    $item->lock($locked);
                }
            }

            $event_type = LOG_GRADEBOOK_UNLOCKED;
            if ($locked == 1) {
                $event_type = LOG_GRADEBOOK_LOCKED;
            }
            event_system($event_type, LOG_GRADEBOOK_ID, $this->id);
        }
    }

    static function register_user_certificate($category_id, $user_id) {
        // generating the total score for a course
        $cats_course     = Category :: load($category_id, null, null, null, null, null, false);

        $alleval_course  = $cats_course[0]->get_evaluations($user_id, true);
        $alllink_course  = $cats_course[0]->get_links($user_id, true);

        $evals_links = array_merge($alleval_course, $alllink_course);

        $item_total = 0;

        //@todo move these in a function
        $sum_categories_weight_array = array();
        if (isset($cats_course) && !empty($cats_course)) {
            $categories = Category::load(null, null, null, $category_id);
            if (!empty($categories)) {
                foreach($categories as $category) {
                    $sum_categories_weight_array[$category->get_id()] = $category->get_weight();
                }
            } else {
                $sum_categories_weight_array[$category_id] = $cats_course[0]->get_weight();
            }
        }

        $main_weight = $cats_course[0]->get_weight();

        $item_total_value = 0;
        $item_value = 0;

        for ($count=0; $count < count($evals_links); $count++) {
            $item = $evals_links[$count];
            $score = $item->calc_score($user_id);
            $divide			= ( ($score[1])==0 ) ? 1 : $score[1];
            $sub_cat_percentage = $sum_categories_weight_array[$item->get_category_id()];
            $item_value     = $score[0]/$divide*$item->get_weight()*$sub_cat_percentage/$main_weight;
            $item_total_value   += $item_value;
        }
        $item_total_value = (float)$item_total_value;

        $cattotal = Category :: load($category_id);

        $scoretotal= $cattotal[0]->calc_score($user_id);

        //Do not remove this the gradebook/lib/fe/gradebooktable.class.php file load this variable as a global
        $scoredisplay = ScoreDisplay :: instance();

        $my_score_in_gradebook = $scoredisplay->display_score($scoretotal, SCORE_SIMPLE);

        //Show certificate
        $certificate_min_score = $cats_course[0]->get_certificate_min_score();

        $scoretotal_display = $scoredisplay->display_score($scoretotal, SCORE_DIV_PERCENT); //a student always sees only the teacher's repartition

        if (isset($certificate_min_score) && $item_total_value >= $certificate_min_score) {
            $my_certificate = get_certificate_by_user_id($cats_course[0]->get_id(), $user_id);
            if (empty($my_certificate)) {
                register_user_info_about_certificate($category_id, $user_id, $my_score_in_gradebook, api_get_utc_datetime());
                $my_certificate = get_certificate_by_user_id($cats_course[0]->get_id(), $user_id);
            }

            if (!empty($my_certificate)) {
                $certificate_obj = new Certificate($my_certificate['id']);

                $url  = api_get_path(WEB_PATH) .'certificates/index.php?id='.$my_certificate['id'];
                $certificates = Display::url(Display::return_icon('certificate.png', get_lang('Certificates'), array(), 32), $url, array('target'=>'_blank'));
                $html = '<div class="actions" align="right">';
                $html .= Display::url($url, $url, array('target'=>'_blank'));
                $html .= $certificates;
                $html .= '</div>';
                return $html;
            }
        } else {
            return false;
        }
    }
}
