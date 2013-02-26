<?php
/* For licensing terms, see /license.txt */

/**
*	Main page for the group module.
*	This script displays the general group settings,
*	and a list of groups with buttons to view, edit...
*
*	@author Thomas Depraetere, Hugues Peeters, Christophe Gesche: initial versions
*	@author Bert Vanderkimpen, improved self-unsubscribe for cvs
*	@author Patrick Cool, show group comment under the group name
*	@author Roan Embrechts, initial self-unsubscribe code, code cleaning, virtual course support
*	@author Bart Mollet, code cleaning, use of Display-library, list of courseAdmin-tools, use of GroupManager
*	@author Isaac Flores, code cleaning and improvements
*	@package chamilo.group
*/
/*		INIT SECTION	*/
// Name of the language file that needs to be included
$language_file = 'group';

require_once '../inc/global.inc.php';
$this_section = SECTION_COURSES;
$current_course_tool  = TOOL_GROUP;

// Notice for unauthorized people.
api_protect_course_script(true);

$htmlHeadXtra[] = '<script>
$(document).ready( function() {
	for (i=0;i<$(".actions").length;i++) {
		if ($(".actions:eq("+i+")").html()=="<table border=\"0\"></table>" || $(".actions:eq("+i+")").html()=="" || $(".actions:eq("+i+")").html()==null) {
			$(".actions:eq("+i+")").hide();
		}
	}
 } );
 </script>';
$nameTools = get_lang('GroupManagement');
$course_id = api_get_course_int_id();

// Create default category if it doesn't exist when group categories aren't allowed
if (api_get_setting('allow_group_categories') == 'false') {
	$cat_table = Database::get_course_table(TABLE_GROUP_CATEGORY);
	$sql = "SELECT * FROM $cat_table WHERE c_id = $course_id AND id = '".GroupManager::DEFAULT_GROUP_CATEGORY."'";
	$res = Database::query($sql);
	$num = Database::num_rows($res);
	if ($num == 0) {
		$sql = "INSERT INTO ".$cat_table." ( c_id, id , title , description , forum_state, wiki_state, max_student, self_reg_allowed, self_unreg_allowed, groups_per_user, display_order) 
		VALUES ($course_id, '2', '".lang2db(get_lang('DefaultGroupCategory'))."', '', '1', '1', '8', '0', '0', '0', '0');";
		Database::query ($sql);
	}
}

/*	Header */

if (!isset ($_GET['origin']) || $_GET['origin'] != 'learnpath') {
	// So we are not in learnpath tool
	event_access_tool(TOOL_GROUP);
	if (!$is_allowed_in_course) {
		api_not_allowed(true);
	}
}

Display::display_header(get_lang('Groups'));

// Tool introduction
Display::display_introduction_section(TOOL_GROUP);

/*
 * Self-registration and unregistration
 */
 $my_group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : null;
 $my_msg	  = isset($_GET['msg']) ? Security::remove_XSS($_GET['msg']) : null;
 $my_group    = isset($_REQUEST['group']) ? Security::remove_XSS($_REQUEST['group']) : null;
 $my_get_id1  = isset($_GET['id1']) ? Security::remove_XSS($_GET['id1']) : null;
 $my_get_id2  = isset($_GET['id2']) ? Security::remove_XSS($_GET['id2']) : null;
 $my_get_id   = isset($_GET['id']) ? Security::remove_XSS($_GET['id']) : null;

if (isset($_GET['action'])) {
	switch ($_GET['action']) {
		case 'self_reg' :
			if (GroupManager :: is_self_registration_allowed($_SESSION['_user']['user_id'], $my_group_id)) {
				GroupManager :: subscribe_users($_SESSION['_user']['user_id'], $my_group_id);
				Display :: display_confirmation_message(get_lang('GroupNowMember'));
			}
			break;
		case 'self_unreg' :
			if (GroupManager :: is_self_unregistration_allowed($_SESSION['_user']['user_id'], $my_group_id)) {
				GroupManager :: unsubscribe_users($_SESSION['_user']['user_id'], $my_group_id);
				Display :: display_confirmation_message(get_lang('StudentDeletesHimself'));
			}
			break;
		case 'show_msg' :
			Display :: display_confirmation_message($my_msg);
			break;
        case 'warning_message' :
			Display :: display_warning_message($my_msg);
			break;
        case 'success_message' :
			Display :: display_confirmation_message($my_msg);
			break;
	}
}

/*
 * Group-admin functions
 */

if (api_is_allowed_to_edit(false, true)) {

	// Post-actions
	if (isset($_POST['action'])) {
		switch ($_POST['action']) {
			case 'delete_selected' :
				if (is_array($_POST['group'])) {
					GroupManager :: delete_groups($my_group);
					Display :: display_confirmation_message(get_lang('SelectedGroupsDeleted'));
				}
				break;
			case 'empty_selected' :
				if (is_array($_POST['group'])) {
                    GroupManager :: unsubscribe_all_users($my_group);
                    Display :: display_confirmation_message(get_lang('SelectedGroupsEmptied'));
				}
				break;
			case 'fill_selected' :
				if (is_array($_POST['group'])) {                    
                    GroupManager :: fill_groups($my_group);
                    Display :: display_confirmation_message(get_lang('SelectedGroupsFilled'));
				}
				break;
		}
	}

	// Get-actions
	if (isset($_GET['action'])) {
		switch ($_GET['action']) {
			case 'swap_cat_order':
				GroupManager :: swap_category_order($my_get_id1, $my_get_id2);
				Display :: display_confirmation_message(get_lang('CategoryOrderChanged'));
				break;
			case 'delete_one':
				GroupManager :: delete_groups($my_get_id);
				Display :: display_confirmation_message(get_lang('GroupDel'));
				break;
			case 'empty_one':
				GroupManager :: unsubscribe_all_users($my_get_id);
				Display :: display_confirmation_message(get_lang('GroupEmptied'));
				break;
			case 'fill_one':                
				GroupManager :: fill_groups($my_get_id);
				Display :: display_confirmation_message(get_lang('GroupFilledGroups'));
				break;
			case 'delete_category':
				GroupManager :: delete_category($my_get_id);
				Display :: display_confirmation_message(get_lang('CategoryDeleted'));
				break;
		}
	}
}

echo '<div class="actions">';
if (api_is_allowed_to_edit(false, true)) {
	echo '<a href="group_creation.php?'.api_get_cidreq().'">'.Display::return_icon('new_group.png', get_lang('NewGroupCreate'),'',ICON_SIZE_MEDIUM).'</a>';
	if (CourseManager::count_rows_course_table(Database::get_course_table(TABLE_GROUP),api_get_session_id(), api_get_course_int_id()) > 0) {		
		echo '<a href="group_overview.php?'.api_get_cidreq().'">'.Display::return_icon('group_summary.png', get_lang('GroupOverview'),'',ICON_SIZE_MEDIUM).'</a>';
	}
	if (api_get_setting('allow_group_categories') == 'true') {
		echo '<a href="group_category.php?'.api_get_cidreq().'&action=add_category">'.Display::return_icon('new_folder.png', get_lang('AddCategory'),'',ICON_SIZE_MEDIUM).'</a>';
	} else {
        echo '<a href="group_category.php?'.api_get_cidreq().'&id=2">'.Display::return_icon('settings.png', get_lang('PropModify'),'',ICON_SIZE_MEDIUM).'</a>';
	}	
	echo  '<a href="group_overview.php?'.api_get_cidreq().'&action=export&type=xls">'.Display::return_icon('export_excel.png', get_lang('ExportAsXLS'),'',ICON_SIZE_MEDIUM).'</a>';	
	echo '<a href="../user/user.php?'.api_get_cidreq().'">'.Display::return_icon('user.png', get_lang('GoTo').' '.get_lang('Users'),'',ICON_SIZE_MEDIUM).'</a>';	
}

$group_cats = GroupManager :: get_categories(api_get_course_id());

if (api_get_setting('allow_group_categories') == 'true' && count($group_cats) > 1) {	
	echo ' <a href="?'.api_get_cidreq().'&show_all=1">'.Display::return_icon('group.png',get_lang('ShowAll'),'',ICON_SIZE_MEDIUM).'</a>';
}
echo '</div>';

/*
 * List all categories
 */

if (api_get_setting('allow_group_categories') == 'true') {
    foreach ($group_cats as $index => $category) {
        if (isset ($_GET['show_all']) || (isset ($_GET['category']) && $_GET['category'] == $category['id'])) {
            //echo '<img src="../img/folder_group_category.gif" alt=""/>';
            //echo '<a href="group.php?'.api_get_cidreq().'&origin='.Security::remove_XSS($_GET['origin']).'">'.$category['title'].'</a>';            
        } else {
            //echo '<img src="../img/folder_document.gif" alt=""/>';
            //echo '<a href="group.php?'.api_get_cidreq().'&origin='.Security::remove_XSS($_GET['origin']).'&amp;category='.$category['id'].'">'.$category['title'].'</a>';
            //echo Display::page_header($category['title']);            
        }
        $group_list = GroupManager :: get_group_list($category['id']);
        $label = Display::label(count($group_list).' '.get_lang('ExistingGroups'), 'info');
        
        $actions = null;
        if (api_is_allowed_to_edit(false, true)) {
            $actions .= '<a href="group_category.php?'.api_get_cidreq().'&id='.$category['id'].'"  title="'.get_lang('Edit').'">'.Display::return_icon('edit.png', get_lang('EditGroup'),'',ICON_SIZE_SMALL).'</a>';
            $actions .= '<a href="group.php?'.api_get_cidreq().'&action=delete_category&amp;id='.$category['id'].'"  onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES))."'".')) return false;" title="'.get_lang('Delete').'">'.Display::return_icon('delete.png', get_lang('Delete'),'',ICON_SIZE_SMALL).'</a>';
            if ($index != 0) {
                $actions .=  ' <a href="group.php?'.api_get_cidreq().'&action=swap_cat_order&amp;id1='.$category['id'].'&amp;id2='.$group_cats[$index -1]['id'].'">'.Display::return_icon('up.png','&nbsp;','',ICON_SIZE_SMALL).'</a>';
            }
            if ($index != count($group_cats) - 1) {
                $actions .= ' <a href="group.php?'.api_get_cidreq().'&action=swap_cat_order&amp;id1='.$category['id'].'&amp;id2='.$group_cats[$index +1]['id'].'">'.Display::return_icon('down.png','&nbsp;','',ICON_SIZE_SMALL).'</a>';
            }
        }
        
        echo Display::page_header($category['title'].' '. $label.' '.$actions);
        
        echo '<p style="margin: 0px;margin-left: 50px;">'.$category['description'].'</p><p/>';
        
        GroupManager ::process_groups($group_list, $category['id']);        
    }
} else {
    $group_list = GroupManager :: get_group_list();            
    GroupManager ::process_groups($group_list);
}

/*	FOOTER */

if (!isset ($_GET['origin']) || $_GET['origin'] != 'learnpath') {
	Display::display_footer();
}
$_SESSION['_gid'] = 0;