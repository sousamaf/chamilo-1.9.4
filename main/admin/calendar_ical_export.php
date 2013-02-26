<?php // $id: $
/* For licensing terms, see /license.txt */
/**
*	@package chamilo.admin
*	@author Carlos Vargas
*	This file is the calendar/ical_export.php
*/
/**
 * Initialisation
 */
// name of the language file that needs to be included
$language_file = 'agenda';
// we are not inside a course, so we reset the course id
$cidReset = true;
// setting the global file that gets the general configuration, the databases, the languages, ...
require_once '../inc/global.inc.php';
$this_section = SECTION_MYAGENDA;
api_block_anonymous_users();
require_once (api_get_path(LIBRARY_PATH).'groupmanager.lib.php');
require_once (api_get_path(LIBRARY_PATH).'icalcreator/iCalcreator.class.php');
// setting the name of the tool
$nameTools = get_lang('MyAgenda');

// the variables for the days and the months
// Defining the shorts for the days
$DaysShort = api_get_week_days_short();
// Defining the days of the week to allow translation of the days
$DaysLong = api_get_week_days_long();
// Defining the months of the year to allow translation of the months
$MonthsLong = api_get_months_long();

if(!empty($_GET['id']) && $_GET['id']==strval(intval($_GET['id'])))
{
	define('ICAL_LANG',api_get_language_isocode());
	if(!empty($_GET['type']))
	{
		$ical = new vcalendar();
		$ical->setConfig('unique_id',api_get_path(WEB_PATH));
		$ical->setProperty( 'method', 'PUBLISH' );
		$ical->setConfig('url',api_get_path(WEB_PATH));
		$vevent = new vevent();
		switch($_GET['class'])
		{
			case 'public':
				$vevent->setClass('PUBLIC');
				break;
			case 'private':
				$vevent->setClass('PRIVATE');
				break;
			case 'confidential':
				$vevent->setClass('CONFIDENTIAL');
				break;
			default:
				$vevent->setClass('PRIVATE');
				break;
		}

		switch($_GET['type'])
		{
			case 'personal':
				//require_once (api_get_path(SYS_CODE_PATH).'calendar/myagenda.inc.php');
				$ai = get_personal_agenda_item($_GET['id']);
                $vevent->setProperty( 'summary', api_convert_encoding($ai['title'],'UTF-8',$charset));
				if(empty($ai['date'])){header('location:'.$_SERVER['REFERER_URI']);}
				list($y,$m,$d,$h,$M,$s) = preg_split('/[\s:-]/',$ai['date']);
				$vevent->setProperty('dtstart',array('year'=>$y,'month'=>$m,'day'=>$d,'hour'=>$h,'min'=>$M,'sec'=>$s));
				if(empty($ai['enddate']))
				{
					$y2=$y;$m2=$m;$d2=$d;$h2=$h;$M2=$M+15;$s2=$s;
					if($M2>60){$M2=$M2-60;$h2+=1;}
				}
				else
				{
					list($y2,$m2,$d2,$h2,$M2,$s2) = preg_split('/[\s:-]/',$ai['enddate']);
				}
				$vevent->setProperty('dtend',array('year'=>$y2,'month'=>$m2,'day'=>$d2,'hour'=>$h2,'min'=>$M2,'sec'=>$s2));
				//$vevent->setProperty( 'LOCATION', get_lang('Unknown') ); // property name - case independent
				$vevent->setProperty( 'description', api_convert_encoding($ai['text'],'UTF-8',$charset));
				//$vevent->setProperty( 'comment', 'This is a comment' );
				$user = api_get_user_info($ai['user']);
				$vevent->setProperty('organizer',$user['mail']);
				$vevent->setProperty('attendee',$user['mail']);
				//$vevent->setProperty( 'rrule', array( 'FREQ' => 'WEEKLY', 'count' => 4));// occurs also four next weeks
				$ical->setConfig('filename',$y.$m.$d.$h.$M.$s.'-'.rand(1,1000).'.ics');
				$ical->setComponent ($vevent); // add event to calendar
				$ical->returnCalendar();
				break;
			case 'course':
				$TABLEAGENDA 			=Database::get_main_table(TABLE_MAIN_SYSTEM_CALENDAR);
				require_once api_get_path(SYS_CODE_PATH).'admin/calendar.lib.php';
				$ai = get_agenda_item($_GET['id']);
		        $vevent->setProperty( 'summary', api_convert_encoding($ai['title'],'UTF-8',$charset));
        		if(empty($ai['start_date'])){header('location:'.$_SERVER['REFERER_URI']);}
				list($y,$m,$d,$h,$M,$s) = preg_split('/[\s:-]/',$ai['start_date']);
				$vevent->setProperty('dtstart',array('year'=>$y,'month'=>$m,'day'=>$d,'hour'=>$h,'min'=>$M,'sec'=>$s));
				if(empty($ai['end_date']))
				{
					$y2=$y;$m2=$m;$d2=$d;$h2=$h;$M2=$M+15;$s2=$s;
					if($M2>60){$M2=$M2-60;$h2+=1;}
				}
				else
				{
					list($y2,$m2,$d2,$h2,$M2,$s2) = preg_split('/[\s:-]/',$ai['end_date']);
				}
				$vevent->setProperty('dtend',array('year'=>$y2,'month'=>$m2,'day'=>$d2,'hour'=>$h2,'min'=>$M2,'sec'=>$s2));
				$vevent->setProperty( 'description', api_convert_encoding($ai['content'],'UTF-8',$charset));
				//$vevent->setProperty( 'comment', 'This is a comment' );
				$user = api_get_user_info($ai['user']);
				$vevent->setProperty('organizer',$user['mail']);
				//$vevent->setProperty('attendee',$user['mail']);
				$course = api_get_course_info();
				$vevent->setProperty('location', $course['name']); // property name - case independent
                if($ai['repeat'])
                {
                	$trans = array('daily'=>'DAILY','weekly'=>'WEEKLY','monthlyByDate'=>'MONTHLY','yearly'=>'YEARLY');
                    $freq = $trans[$ai['repeat_type']];
                    list($e_y,$e_m,$e_d) = split('/',date('Y/m/d',$ai['repeat_end']));
                	$vevent->setProperty('rrule',array('FREQ'=>$freq,'UNTIL'=>array('year'=>$e_y,'month'=>$e_m,'day'=>$e_d),'INTERVAL'=>'1'));
                }
				//$vevent->setProperty( 'rrule', array( 'FREQ' => 'WEEKLY', 'count' => 4));// occurs also four next weeks
				$ical->setConfig('filename',$y.$m.$d.$h.$M.$s.'-'.rand(1,1000).'.ics');
				$ical->setComponent ($vevent); // add event to calendar
				$ical->returnCalendar();
				break;
			default:
				header('location:'.$_SERVER['REFERER_URI']);
				die();
		}
	}
}
else
{
	header('location:'.$_SERVER['REFERER_URI']);
	die();
}
?>
