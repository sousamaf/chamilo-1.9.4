<?php
/* For licensing terms, see /license.txt */

/**
 *  @author: Julio Montoya <gugli100@gmail.com> Implementing a real agenda lib
 */

class Agenda {
	var $events = array();
	var $type   = 'personal'; // personal, admin or course

	function __construct() {
		//Table definitions
		$this->tbl_global_agenda 	= Database::get_main_table(TABLE_MAIN_SYSTEM_CALENDAR);
		$this->tbl_personal_agenda 	= Database::get_user_personal_table(TABLE_PERSONAL_AGENDA);
		$this->tbl_course_agenda 	= Database::get_course_table(TABLE_AGENDA);

		//Setting the course object if we are in a course
		$this->course = null;
		$course_info = api_get_course_info();
		if (!empty($course_info)) {
			$this->course = $course_info;
		}

		$this->events				= array();

		//Event colors
		$this->event_platform_color = 'red';//red
		$this->event_course_color 	= '#458B00'; //green
		$this->event_group_color 	= '#A0522D'; //siena
		$this->event_session_color 	= '#00496D'; // kind of green
		$this->event_personal_color = 'steel blue'; //steel blue
	}

    function set_course($course_info) {
        $this->course = $course_info;
    }

	/**
	 *
	 * Adds an event to the calendar
     *
	 * @param   string  start datetime format: 2012-06-14 09:00:00
	 * @param   string  end datetime format: 2012-06-14 09:00:00
     * @param   string  all day (true, false)
	 * @param   string  view agendaDay, agendaWeek, month @todo seems not to be used
     * @param   string  title
     * @param   string  content
     * @param   array   users to send array('everyone') or a list of user ids
     * @param   bool    add event as a *course* announcement
     *
	 */
	function add_event($start, $end, $all_day, $view, $title, $content, $users_to_send = array(), $add_as_announcement = false) {
		$start 		= api_get_utc_datetime($start);
        $end 		= api_get_utc_datetime($end);
		$all_day 	= isset($all_day) && $all_day == 'true' ? 1:0;

		$attributes = array();
		$id = null;
		switch ($this->type) {
			case 'personal':
				$attributes['user'] 	= api_get_user_id();
				$attributes['title'] 	= $title;
				$attributes['text'] 	= $content;
				$attributes['date'] 	= $start;
				$attributes['enddate'] 	= $end;
				$attributes['all_day'] 	= $all_day;
				$id = Database::insert($this->tbl_personal_agenda, $attributes);
				break;
			case 'course':
				$attributes['title'] 		= $title;
				$attributes['content'] 		= $content;
				$attributes['start_date'] 	= $start;
				$attributes['end_date'] 	= $end;
				$attributes['all_day'] 		= $all_day;
				$attributes['session_id'] 	= api_get_session_id();
				$attributes['c_id'] 		= $this->course['real_id'];

				//simple course event
				$id = Database::insert($this->tbl_course_agenda, $attributes);

                if ($id) {
                    $group_id = api_get_group_id();

                    if ((!is_null($users_to_send)) or (!empty($group_id))) {
                        $send_to = self::separate_users_groups($users_to_send);
                        if (isset($send_to['everyone']) && $send_to['everyone']) {
                            api_item_property_update($this->course, TOOL_CALENDAR_EVENT, $id, "AgendaAdded", api_get_user_id(), $group_id ,'', $start, $end);
                        } else {
                            // storing the selected groups
                            if (isset($send_to['groups']) && is_array($send_to['groups'])) {
                                foreach ($send_to['groups'] as $group) {
                                    api_item_property_update($this->course, TOOL_CALENDAR_EVENT, $id, "AgendaAdded", api_get_user_id(), $group,0, $start, $end);
                                }
                            }

                            // storing the selected users
                            if (isset($send_to['groups']) && is_array($send_to['users'])) {
                                foreach ($send_to['users'] as $to_user_id) {
                                    api_item_property_update($this->course, TOOL_CALENDAR_EVENT, $id, "AgendaAdded", api_get_user_id(), $group_id, $to_user_id, $start, $end);
                                }
                            }
                        }
                    }

                    if (isset($add_as_announcement) && !empty($add_as_announcement)) {
                        self::store_agenda_item_as_announcement($id, $users_to_send);
                    }
                }
				break;
			case 'admin':
				$attributes['title'] 		= $title;
				$attributes['content'] 		= $content;
				$attributes['start_date'] 	= $start;
				$attributes['end_date'] 	= $end;
				$attributes['all_day'] 		= $all_day;
				$attributes['access_url_id']= api_get_current_access_url_id();
				$id = Database::insert($this->tbl_global_agenda, $attributes);
				break;
		}
		return $id;
	}



    /**
     * @param agenda_id
     * @sent agenda event to
     **/

    function store_agenda_item_as_announcement($item_id, $sent_to = array()){
        $table_agenda  = Database::get_course_table(TABLE_AGENDA);
        $course_id     = api_get_course_int_id();

        //check params
        if (empty($item_id) or $item_id != strval(intval($item_id))) { return -1; }
        //get the agenda item

        $item_id = Database::escape_string($item_id);
        $sql = "SELECT * FROM $table_agenda WHERE c_id = $course_id AND id = ".$item_id;
        $res = Database::query($sql);

        if (Database::num_rows($res)>0) {
            $row = Database::fetch_array($res, 'ASSOC');

            //Sending announcement
            if (!empty($sent_to)) {
                $id = AnnouncementManager::add_announcement($row['title'], $row['content'], $sent_to, null, null, $row['end_date']);
                AnnouncementManager::send_email($id);
            }
            return $id;
        }
        return -1;
    }

    /**
     * Edits an event
     *
     * @param int       event id
  	 * @param string    start datetime format: 2012-06-14 09:00:00
	 * @param string    end datetime format: 2012-06-14 09:00:00
     * @param int       event is all day? 1|0
     * @param string    view
     * @param string    event title
     * @param string    event content
     */
	function edit_event($id, $start, $end, $all_day, $view, $title, $content) {
		$start = api_get_utc_datetime($start);

		if ($all_day == '0') {
			$end = api_get_utc_datetime($end);
		}
		$all_day = isset($all_day) && $all_day == '1' ? 1 : 0;

		$attributes = array();

		switch($this->type) {
			case 'personal':
				$attributes['title'] 	= $title;
				$attributes['text'] 	= $content;
				$attributes['date'] 	= $start;
				$attributes['enddate'] 	= $end;
				Database::update($this->tbl_personal_agenda, $attributes, array('id = ?' => $id));
				break;
			case 'course':
                $course_id = api_get_course_int_id();
				$attributes['title'] 		= $title;
				$attributes['content'] 		= $content;
				$attributes['start_date'] 	= $start;
				$attributes['end_date'] 	= $end;
                if (!empty($course_id)) {
                    Database::update($this->tbl_course_agenda, $attributes, array('id = ? AND c_id = ?' => array($id, $course_id)));
                }
				break;
			case 'admin':
				$attributes['title'] 		= $title;
				$attributes['content'] 		= $content;
				$attributes['start_date'] 	= $start;
				$attributes['end_date'] 	= $end;
				Database::update($this->tbl_global_agenda, $attributes, array('id = ?' => $id));
				break;
				break;
		}
	}

	function delete_event($id) {
		switch($this->type) {
			case 'personal':
				Database::delete($this->tbl_personal_agenda, array('id = ?' => $id));
				break;
			case 'course':
                $course_id = api_get_course_int_id();
                if (!empty($course_id)) {
                    Database::delete($this->tbl_course_agenda, array('id = ? AND c_id = ?' => array($id, $course_id)));
                }
				break;
			case 'admin':
				Database::delete($this->tbl_global_agenda, array('id = ?' => $id));
				break;
		}
	}

	/**
	 *
	 * Get agenda events
	 * @param	int		start tms
	 * @param	int		end tms
	 * @param	int		course id *integer* not the course code
	 *
	 */
	function get_events($start, $end, $course_id = null, $group_id = null) {

		switch ($this->type) {
			case 'admin':
				$this->get_platform_events($start, $end);
				break;
			case 'course':
                $session_id = api_get_session_id();
				$course_info = api_get_course_info_by_id($course_id);
				$this->get_course_events($start, $end, $course_info, $group_id, $session_id);
				break;
			case 'personal':
			default:
                //Getting personal events
				$this->get_personal_events($start, $end);

                //Getting platform/admin events
				$this->get_platform_events($start, $end);

                //Getting course events
				$my_course_list = array();

				if (!api_is_anonymous()) {
                    $session_list = SessionManager::get_sessions_by_user(api_get_user_id());
					$my_course_list = CourseManager::get_courses_list_by_user_id(api_get_user_id(), true);
				}

                if (!empty($session_list)) {
                    foreach ($session_list as $session_item) {
                        $my_courses = $session_item['courses'];
                        $my_session_id = $session_item['session_id'];
                        if (!empty($my_courses)) {
                            foreach ($my_courses as $course_item) {
                                $course_info = api_get_course_info($course_item['code']);
                                $this->get_course_events($start, $end, $course_info, 0, $my_session_id);
                            }
                        }
                    }
                }

				if (!empty($my_course_list)) {
					foreach ($my_course_list as $course_info_item) {
						if (isset($course_id) && !empty($course_id)) {
							if ($course_info_item['real_id'] == $course_id) {
								$this->get_course_events($start, $end, $course_info_item);
							}
						} else {
							$this->get_course_events($start, $end, $course_info_item);
						}
					}
				}
				break;
		}
		if (!empty($this->events)) {
			return json_encode($this->events);
		}
		return '';
	}

    function resize_event($id, $day_delta, $minute_delta) {
        // we convert the hour delta into minutes and add the minute delta
		$delta = ($day_delta * 60 * 24) + $minute_delta;
		$delta = intval($delta);

		$event = $this->get_event($id);
        if (!empty($event)) {
			switch($this->type) {
				case 'personal':
					$sql = "UPDATE $this->tbl_personal_agenda SET all_day = 0, enddate = DATE_ADD(enddate, INTERVAL $delta MINUTE)
							WHERE id=".intval($id);
					$result = Database::query($sql);
					break;
				case 'course':
					$sql = "UPDATE $this->tbl_course_agenda SET all_day = 0,  end_date = DATE_ADD(end_date, INTERVAL $delta MINUTE)
							WHERE c_id = ".$this->course['real_id']." AND id=".intval($id);
					$result = Database::query($sql);
					break;
				case 'admin':
					$sql = "UPDATE $this->tbl_global_agenda SET all_day = 0, end_date = DATE_ADD(end_date, INTERVAL $delta MINUTE)
							WHERE id=".intval($id);
					$result = Database::query($sql);
					break;
			}
		}
		return 1;

    }

	function move_event($id, $day_delta, $minute_delta) {
		// we convert the hour delta into minutes and add the minute delta
		$delta = ($day_delta * 60 * 24) + $minute_delta;
		$delta = intval($delta);

		$event = $this->get_event($id);

        $all_day = 0;
        if ($day_delta == 0 && $minute_delta == 0) {
            $all_day = 1;
        }

		if (!empty($event)) {
			switch($this->type) {
				case 'personal':
					$sql = "UPDATE $this->tbl_personal_agenda SET all_day = $all_day, date = DATE_ADD(date, INTERVAL $delta MINUTE), enddate = DATE_ADD(enddate, INTERVAL $delta MINUTE)
							WHERE id=".intval($id);
					$result = Database::query($sql);
					break;
				case 'course':
					$sql = "UPDATE $this->tbl_course_agenda SET all_day = $all_day, start_date = DATE_ADD(start_date,INTERVAL $delta MINUTE), end_date = DATE_ADD(end_date, INTERVAL $delta MINUTE)
							WHERE c_id = ".$this->course['real_id']." AND id=".intval($id);
					$result = Database::query($sql);
					break;
				case 'admin':
					$sql = "UPDATE $this->tbl_global_agenda SET all_day = $all_day, start_date = DATE_ADD(start_date,INTERVAL $delta MINUTE), end_date = DATE_ADD(end_date, INTERVAL $delta MINUTE)
							WHERE id=".intval($id);
					$result = Database::query($sql);
					break;
			}
		}
		return 1;
	}

	/**
	 * Gets a single event
	 * @param int event id
	 */
	function get_event($id) {
		// make sure events of the personal agenda can only be seen by the user himself
		$id = intval($id);
		$event = null;
		switch ($this->type) {
			case 'personal':
				$sql = " SELECT * FROM ".$this->tbl_personal_agenda." WHERE id = $id AND user = ".api_get_user_id();
				$result = Database::query($sql);
				if (Database::num_rows($result)) {
					$event = Database::fetch_array($result, 'ASSOC');
                    $event['description'] = $event['text'];
				}
				break;
			case 'course':
                if (!empty($this->course['real_id'])) {
                    $sql = " SELECT * FROM ".$this->tbl_course_agenda." WHERE c_id = ".$this->course['real_id']." AND id = ".$id;
                    $result = Database::query($sql);
                    if (Database::num_rows($result)) {
                        $event = Database::fetch_array($result, 'ASSOC');
                        $event['description'] = $event['content'];
                    }
                }
				break;
			case 'admin':
            case 'platform':
				$sql = " SELECT * FROM ".$this->tbl_global_agenda." WHERE id=".$id;
				$result = Database::query($sql);
				if (Database::num_rows($result)) {
					$event = Database::fetch_array($result, 'ASSOC');
                    $event['description'] = $event['content'];
				}
			break;
		}
		return $event;
	}

	/**
	 *
	 * Gets personal events
	 * @param int 	start date tms
	 * @param int	end   date tms
	 */
	function get_personal_events($start, $end) {
		$start 	= intval($start);
		$end	= intval($end);
		$start  = api_get_utc_datetime($start);
		$end  	= api_get_utc_datetime($end);
		$user_id = api_get_user_id();

		$sql 	= "SELECT * FROM ".$this->tbl_personal_agenda."
				   WHERE date >= '".$start."' AND (enddate <='".$end."' OR enddate IS NULL) AND user = $user_id";

		$result = Database::query($sql);
        $my_events = array();
		if (Database::num_rows($result)) {
			while ($row = Database::fetch_array($result, 'ASSOC')) {
				$event = array();
				$event['id'] 	  		= 'personal_'.$row['id'];
				$event['title'] 		= $row['title'];
				$event['className'] 	= 'personal';
				$event['borderColor'] 	= $event['backgroundColor'] = $this->event_personal_color;
				$event['editable'] 		= true;

                $event['sent_to']       = get_lang('Me');
                $event['type']          = 'personal';

				if (!empty($row['date']) && $row['date'] != '0000-00-00 00:00:00') {
					$event['start'] = $this->format_event_date($row['date']);
				}

				if (!empty($row['enddate']) && $row['enddate'] != '0000-00-00 00:00:00') {
					$event['end'] = $this->format_event_date($row['enddate']);
				}
				$event['description'] = $row['text'];
				$event['allDay'] = isset($row['all_day']) && $row['all_day'] == 1 ? $row['all_day'] : 0;
				$my_events[] = $event;
				$this->events[]= $event;
			}
		}
		return $my_events;
	}

	function get_course_events($start, $end, $course_info, $group_id = 0, $session_id = 0) {
		$course_id = $course_info['real_id'];
        $group_list = GroupManager::get_group_list(null, $course_info['code']);

        $group_name_list = array();
        if (!empty($group_list)) {
            foreach($group_list as $group) {
                $group_name_list[$group['id']]= $group['name'];
            }
        }

        if (!api_is_allowed_to_edit()) {
            $group_memberships 	= GroupManager::get_group_ids($course_id, api_get_user_id());
        } else {
            $group_memberships = array_keys($group_name_list);
        }

		$tlb_course_agenda	= Database::get_course_table(TABLE_AGENDA);
		$tbl_property 		= Database::get_course_table(TABLE_ITEM_PROPERTY);

		$user_id = api_get_user_id();

        if (!empty($group_id)) {
            $group_memberships = array($group_id);
        }

        $session_id = intval($session_id);

		if (is_array($group_memberships) && count($group_memberships) > 0) {
		    if (api_is_allowed_to_edit()) {
		        $where_condition = "( ip.to_group_id is null OR ip.to_group_id IN (0, ".implode(", ", $group_memberships).") ) ";
            } else {
                $where_condition = "( ip.to_user_id = $user_id OR ip.to_group_id IN (0, ".implode(", ", $group_memberships).") ) ";
            }

            $sql = "SELECT DISTINCT agenda.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.ref, to_user_id
                    FROM ".$tlb_course_agenda." agenda, ".$tbl_property." ip
                    WHERE   agenda.id       = ip.ref  AND
                            ip.tool         ='".TOOL_CALENDAR_EVENT."' AND
                            $where_condition AND
                            ip.visibility   = '1' AND
                            agenda.c_id     = $course_id AND
                            ip.c_id         = $course_id
                    GROUP BY id";

		} else {
		    if (api_is_allowed_to_edit()) {
		        $where_condition = "";
            } else {
                $where_condition = "( ip.to_user_id=$user_id OR ip.to_group_id='0') AND ";
            }

            $sql = "SELECT DISTINCT agenda.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.ref, to_user_id
                    FROM ".$tlb_course_agenda." agenda, ".$tbl_property." ip
                    WHERE   agenda.id = ip.ref AND
                            ip.tool='".TOOL_CALENDAR_EVENT."' AND
                            $where_condition
                            ip.visibility='1' AND
                            agenda.c_id = $course_id AND
                            ip.c_id = $course_id AND
                            agenda.session_id = $session_id AND
                            ip.id_session = $session_id
                    ";

		}

		$result = Database::query($sql);
		$events = array();
		if (Database::num_rows($result)) {
            $events_added = array();
			while ($row = Database::fetch_array($result, 'ASSOC')) {
                //to gather sent_tos
                $sql = "SELECT to_user_id, to_group_id
                    FROM ".$tbl_property." ip
                    WHERE   ip.tool         = '".TOOL_CALENDAR_EVENT."' AND
                            ref             = {$row['ref']} AND
                            ip.visibility   = '1' AND
                            ip.c_id         = $course_id";
                $sent_to_result = Database::query($sql);
                $user_to_array = array();
                $group_to_array = array();
                while ($row_send_to = Database::fetch_array($sent_to_result, 'ASSOC')) {
                    if (!empty($row_send_to['to_group_id'])) {
                        $group_to_array[] = $row_send_to['to_group_id'];
                    }
                    if (!empty($row_send_to['to_user_id'])) {
                        $user_to_array[]  = $row_send_to['to_user_id'];
                    }
                }

				//Only show events from the session
				/*if (api_get_course_int_id()) {
					if ($row['session_id'] != api_get_session_id()) {
						continue;
					}
				}*/

				$event = array();

				$event['id'] 	  		= 'course_'.$row['id'];

                //To avoid doubles
                if (in_array($row['id'], $events_added)) {
                    continue;
                }

                $events_added[] = $row['id'];

                $attachment = get_attachment($row['id'], $course_id);

                $has_attachment = '';

                if (!empty($attachment)) {
                    $has_attachment =  Display::return_icon('attachment.gif',get_lang('Attachment'));
                    $user_filename  = $attachment['filename'];
                    $full_file_name = 'download.php?file='.$attachment['path'].'&course_id='.$course_id;
                    $event['attachment'] = $has_attachment.Display::url($user_filename, $full_file_name);
                } else {
                    $event['attachment'] = '';
                }

				$event['title'] 		= $row['title'];
				$event['className'] 	= 'course';
				$event['allDay'] 	  	= 'false';

                $event['course_id'] 	= $course_id;

				$event['borderColor'] 	= $event['backgroundColor'] = $this->event_course_color;
				if (isset($row['session_id']) && !empty($row['session_id'])) {
					$event['borderColor'] 	= $event['backgroundColor'] = $this->event_session_color;
				}

				if (isset($row['to_group_id']) && !empty($row['to_group_id'])) {
					$event['borderColor'] 	= $event['backgroundColor'] = $this->event_group_color;
				}

				$event['editable'] 		= false;

				if (api_is_allowed_to_edit() && $this->type == 'course') {
					$event['editable'] 		= true;
				}

				if (!empty($row['start_date']) && $row['start_date'] != '0000-00-00 00:00:00') {
					$event['start'] = $this->format_event_date($row['start_date']);
				}
				if (!empty($row['end_date']) && $row['end_date'] != '0000-00-00 00:00:00') {
					$event['end'] = $this->format_event_date($row['end_date']);
				}

                $event['sent_to'] = '';
                //$event['type']    = $this->type;
                $event['type']    = 'course';
                if ($row['session_id'] != 0) {
                    $event['type']    = 'session';
                }

                //Event Sent to a group?
                if (isset($row['to_group_id']) && !empty($row['to_group_id'])) {
                    $sent_to = array();
                    if (!empty($group_to_array)) {
                        foreach($group_to_array as $group_item) {
                            $sent_to[] = $group_name_list[$group_item];
                        }
                    }
                    $sent_to = implode('@@', $sent_to);
                    $sent_to =  str_replace('@@', '</div><div class="label_tag notice">', $sent_to);
                    $event['sent_to'] = '<div class="label_tag notice">'.$sent_to.'</div>';
                    $event['type']    = 'group';
                }

                //Event sent to a user?
                if (isset($row['to_user_id'])) {
                    $sent_to = array();
                    if (!empty($user_to_array)) {
                        foreach($user_to_array as $item) {
                            $user_info = api_get_user_info($item);
                            // add username as tooltip for $event['sent_to'] - ref #4226
                            $username = api_htmlentities(sprintf(get_lang('LoginX'), $user_info['username']), ENT_QUOTES);
                            $sent_to[] = "<span title='".$username."'>".$user_info['complete_name']."</span>";
                        }
                    }
                    $sent_to = implode('@@', $sent_to);
                    $sent_to =  str_replace('@@', '</div><div class="label_tag notice">', $sent_to);
                    $event['sent_to'] = '<div class="label_tag notice">'.$sent_to.'</div>';
                }

                //Event sent to everyone!
                if (empty($event['sent_to'])) {
                    $event['sent_to'] = '<div class="label_tag notice">'.get_lang('Everyone').'</div>';
                }

				$event['description'] = $row['content'];
				$event['allDay'] = isset($row['all_day']) && $row['all_day'] == 1 ? $row['all_day'] : 0;

				$this->events[] = $event;
			}
		}
		return $events;
	}

	function get_platform_events($start, $end) {
		$start 	= intval($start);
		$end	= intval($end);

		$start  = api_get_utc_datetime($start);
		$end  	= api_get_utc_datetime($end);

		$access_url_id 	= api_get_current_access_url_id();

		$sql 	= "SELECT * FROM ".$this->tbl_global_agenda."
				   WHERE start_date >= '".$start."' AND end_date <= '".$end."' AND access_url_id = $access_url_id ";

		$result = Database::query($sql);
		$my_events = array();
		if (Database::num_rows($result)) {
			while ($row = Database::fetch_array($result, 'ASSOC')) {
				$event = array();
				$event['id'] 	  		= 'platform_'.$row['id'];
				$event['title'] 		= $row['title'];
				$event['className'] 	= 'platform';
				$event['allDay'] 	  	= 'false';
				$event['borderColor'] 	= $event['backgroundColor'] = $this->event_platform_color;
				$event['editable'] 		= false;

                $event['type']          = 'admin';

				if (api_is_platform_admin() && $this->type == 'admin') {
					$event['editable'] 		= true;
				}

				if (!empty($row['start_date']) && $row['start_date'] != '0000-00-00 00:00:00') {
					$event['start'] = $this->format_event_date($row['start_date']);
				}
				if (!empty($row['end_date']) && $row['end_date'] != '0000-00-00 00:00:00') {
					$event['end'] = $this->format_event_date($row['end_date']);
				}

				$event['description'] = $row['content'];
				$event['allDay'] = isset($row['all_day']) && $row['all_day'] == 1 ? $row['all_day'] : 0;

				$my_events[] = $event;
				$this->events[]= $event;
			}
		}
		return $my_events;
	}

    /**
     * Format needed for the Fullcalendar js lib
     *  @param string UTC time
     */
	function format_event_date($utc_time) {
        return date('c', api_strtotime(api_get_local_time($utc_time)));
	}

    /**
    * this function shows the form with the user that were not selected
    * @author: Patrick Cool <patrick.cool@UGent.be>, Ghent University
    * @return html code
    */
    static function construct_not_selected_select_form($group_list = null, $user_list = null, $to_already_selected = array()) {
        $html = '<select id="users_to_send_id" data-placeholder="'.get_lang('Select').'" name="users_to_send[]" multiple="multiple" style="width:250px" class="chzn-select">';

        $html .=  '<option value="everyone">'.get_lang('Everyone').'</option>';

        if (is_array($group_list)) {
            $html .= '<optgroup label="'.get_lang('Groups').'">';
            foreach ($group_list as $this_group) {
                if (!is_array($to_already_selected) || !in_array("GROUP:".$this_group['id'], $to_already_selected)) {
                    // $to_already_selected is the array containing the groups (and users) that are already selected
                    $count_users = isset($this_group['count_users']) ? $this_group['count_users'] : $this_group['userNb'];
                    $count_users = " &ndash; $count_users ".get_lang('Users');

                    $html .= '<option value="GROUP:'.$this_group['id'].'"> '.$this_group['name'].$count_users.'</option>';
                    //$html .= "<option value=\"GROUP:".$this_group['id']."\"> ".$this_group['name']." ".get_lang('Users')."</option>";
                }
            }
            $html .= '</optgroup>';
        }

        // adding the individual users to the select form
        if (is_array($group_list)) {
            $html .= '<optgroup label="'.get_lang('Users').'">';
        }
        foreach ($user_list as $this_user) {
            // $to_already_selected is the array containing the users (and groups) that are already selected
            if (!is_array($to_already_selected) || !in_array("USER:".$this_user['user_id'],$to_already_selected)) {
                $username = api_htmlentities(sprintf(get_lang('LoginX'), $this_user['username']), ENT_QUOTES);
                // @todo : add title attribute $username in the jqdialog window. wait for a chosen version to inherit title attribute
                // from <option> to <li>
                $html .= '<option title="'.$username.'" value="USER:'.$this_user['user_id'].'">'.api_get_person_name($this_user['firstname'], $this_user['lastname']).' ('.$this_user['username'].') </option>';
            }
        }
        if (is_array($group_list)) {
            $html .= '</optgroup>';
            $html .=  "</select>";
        }
        return $html;
    }

    static function construct_not_selected_select_form_validator($form, $group_list = null, $user_list = null, $to_already_selected = array()) {

        $params = array(
            'id' => 'users_to_send_id',
            'data-placeholder'=> get_lang('Select'),
            'multiple' => 'multiple',
            'style' => 'width:250px',
            'class' => 'chzn-select'
        );

        $select = $form->addElement('select', 'users_to_send', get_lang('To'), null, $params);

        $select->addOption(get_lang('Everyone'), 'everyone');

        $options = array();
        if (is_array($group_list)) {
            foreach ($group_list as $this_group) {
                if (!is_array($to_already_selected) || !in_array("GROUP:".$this_group['id'], $to_already_selected)) {
                    // $to_already_selected is the array containing the groups (and users) that are already selected
                    $count_users = isset($this_group['count_users']) ? $this_group['count_users'] : $this_group['userNb'];
                    $count_users = " &ndash; $count_users ".get_lang('Users');
                    $options[] = array('text' => $this_group['name'].$count_users, 'value' => "GROUP:".$this_group['id']);
                }
            }
            $select->addOptGroup($options, get_lang('Groups'));
            $html .= '</optgroup>';
        }

        // adding the individual users to the select form
        if (is_array($group_list)) {
            $options = array();
            foreach ($user_list as $this_user) {
                // $to_already_selected is the array containing the users (and groups) that are already selected
                if (!is_array($to_already_selected) || !in_array("USER:".$this_user['user_id'],$to_already_selected)) {
                    //$username = api_htmlentities(sprintf(get_lang('LoginX'), $this_user['username']), ENT_QUOTES);
                    // @todo : add title attribute $username in the jqdialog window. wait for a chosen version to inherit title attribute
                    // from <option> to <li>
                    //$html .= '<option title="'.$username.'" value="USER:'.$this_user['user_id'].'">'.api_get_person_name($this_user['firstname'], $this_user['lastname']).' ('.$this_user['username'].') </option>';
                    $options[] = array('text' => api_get_person_name($this_user['firstname'], $this_user['lastname']).' ('.$this_user['username'].')',
                                       'value' => "USER:".$this_user['user_id']);
                }
            }
            $select->addOptGroup($options, get_lang('Users'));
        }
    }

    /**
    * This function separates the users from the groups
    * users have a value USER:XXX (with XXX the dokeos id
    * groups have a value GROUP:YYY (with YYY the group id)
    * @author: Patrick Cool <patrick.cool@UGent.be>, Ghent University
    * @return array
    */
    function separate_users_groups($to) {
        $grouplist = array();
        $userlist  = array();
        $send_to = null;

        $send_to['everyone'] = false;
        if (is_array($to) && count($to)>0) {
            foreach ($to as $to_item) {
                if ($to_item == 'everyone') {
                    $send_to['everyone'] = true;
                } else {
                    list($type, $id) = explode(':', $to_item);
                    switch ($type) {
                        case 'GROUP':
                            $grouplist[] =$id;
                        break;
                        case 'USER':
                            $userlist[] =$id;
                        break;
                    }
                }
            }
            $send_to['groups']=$grouplist;
            $send_to['users']=$userlist;

        }
        return $send_to;
    }

    static function show_form($params = array()) {
        $form = new FormValidator('add_event', 'POST', api_get_self().'?'.api_get_cidreq(), null, array('enctype' => 'multipart/form-data'));
        $id = isset($params['id']) ? $params['id'] : null;

        if ($id) {
            $form_title = get_lang('ModifyCalendarItem');
            $button = get_lang('ModifyEvent');
        } else {
            $form_title = get_lang('AddCalendarItem');
            $button = get_lang('AgendaAdd');
        }

        $form->addElement('header', $form_title);
        $form->addElement('hidden', 'id', $id);
        $form->addElement('hidden', 'action', $params['action']);
        $form->addElement('hidden', 'id_attach', $params['id_attach']);

        $form->addElement('text', 'title', get_lang('ItemTitle'));

        $group_id = api_get_group_id();

        if (isset ($group_id) && !empty($group_id)) {
            $form->addElement('hidden', 'selected_form[0]', "GROUP:'.$group_id.'");
            $form->addElement('hidden', 'to', 'true');
        } else {
            self::show_to_form($form, $to);
        }

        $form->addElement('text', 'start_date', get_lang('StartDate'));
        $form->addElement('text', 'end_date', get_lang('EndDate'));

        if (empty($id)) {
            $form->addElement('advanced_settings', '<a href="javascript://" onclick="return plus_repeated_event();"><span id="plus2">
                                   <img style="vertical-align:middle;" src="../img/div_show.gif" alt="" />&nbsp;'.get_lang('RepeatEvent').'</span>
                                </a>');
            $form->addElement('html', '<div style="display:block">');

            $form->addElement('checkbox', 'repeat', null, get_lang('RepeatEvent'));

            $repeat_events = array(
                'daily' => get_lang('RepeatDaily'),
                'weekly' => get_lang('RepeatWeekly'),
                'monthlyByDate' => get_lang('RepeatMonthlyByDate'),
                'yearly' => get_lang('RepeatYearly')
            );

            $form->addElement('select', 'repeat_type', get_lang('RepeatType'), $repeat_events);
            $form->addElement('text', 'repeat_end_day', get_lang('RepeatEnd'));
            $form->addElement('html', '</div>');

            if(!api_is_allowed_to_edit(null,true)) {
                $toolbar = 'AgendaStudent';
            } else {
                $toolbar = 'Agenda';
            }
            //$form->addElement('html_editor', 'content', get_lang('Description'), null, array('ToolbarSet' => $toolbar, 'Width' => '100%', 'Height' => '200'));

            $form->addElement('file', 'user_upload', get_lang('AddAnAttachment'));
            $form->addElement('text', 'file_comment', get_lang('Comment'));
        }

        $form->addElement('button', 'submit', $button);
        $form->display();
    }


    static function show_to_form($form, $to_already_selected) {
        $order = 'lastname';
        if (api_is_western_name_order()) {
            $order = 'firstname';
        }
        $user_list  = CourseManager::get_user_list_from_course_code(api_get_course_id(), api_get_session_id(), null, $order);
        $group_list = CourseManager::get_group_list_of_course(api_get_course_id(), api_get_session_id());

        self::construct_not_selected_select_form_validator($form, $group_list, $user_list, $to_already_selected);
    }


}