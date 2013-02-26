<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.social
 * @author Julio Montoya <gugli100@gmail.com>
 */
/**
 * Initialization
 */
$language_file = array('userInfo');
$cidReset = true;
require_once '../inc/global.inc.php';

api_block_anonymous_users();
if (api_get_setting('allow_social_tool') !='true') {
    api_not_allowed();
}

require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';

$this_section = SECTION_SOCIAL;
$interbreadcrumb[]= array ('url' =>'home.php','name' => get_lang('Social'));
$interbreadcrumb[] = array('url' => 'groups.php','name' => get_lang('Groups'));
$interbreadcrumb[] = array('url' => '#','name' => get_lang('MemberList'));

$group_id	= intval($_GET['id']);

//todo @this validation could be in a function in group_portal_manager
if (empty($group_id)) {
	api_not_allowed();
} else {
	$group_info = GroupPortalManager::get_group_data($group_id);
	if (empty($group_info)) {
		api_not_allowed();
	}
	$user_role = GroupPortalManager::get_user_group_role(api_get_user_id(), $group_id);
	if (!in_array($user_role, array(GROUP_USER_PERMISSION_ADMIN, GROUP_USER_PERMISSION_MODERATOR, GROUP_USER_PERMISSION_READER))) {
		api_not_allowed();
	}
}

$show_message	= '';
//if i'm a moderator
if (isset($_GET['action']) && $_GET['action']=='add') {
	// we add a user only if is a open group
	$user_join = intval($_GET['u']);
	//if i'm a moderator
	if (GroupPortalManager::is_group_moderator($group_id)) {
		GroupPortalManager::update_user_role($user_join, $group_id);
		$show_message = get_lang('UserAdded');
	}
}

if (isset($_GET['action']) && $_GET['action']=='delete') {
	// we add a user only if is a open group
	$user_join = intval($_GET['u']);
	//if i'm a moderator
	if (GroupPortalManager::is_group_moderator($group_id)) {
		GroupPortalManager::delete_user_rel_group($user_join, $group_id);
		$show_message = Display::return_message(get_lang('UserDeleted'));
	}
}

if (isset($_GET['action']) && $_GET['action']=='set_moderator') {
	// we add a user only if is a open group
	$user_moderator= intval($_GET['u']);
	//if i'm the admin
	if (GroupPortalManager::is_group_admin($group_id)) {
		GroupPortalManager::update_user_role($user_moderator, $group_id, GROUP_USER_PERMISSION_MODERATOR);
		$show_message = Display::return_message(get_lang('UserChangeToModerator'));
	}
}

if (isset($_GET['action']) && $_GET['action']=='delete_moderator') {
	// we add a user only if is a open group
	$user_moderator= intval($_GET['u']);
	//only group admins can do that
	if (GroupPortalManager::is_group_admin($group_id)) {
		GroupPortalManager::update_user_role($user_moderator, $group_id, GROUP_USER_PERMISSION_READER);
		$show_message = Display::return_message(get_lang('UserChangeToReader'));
	}
}

$users	= GroupPortalManager::get_users_by_group($group_id, false, array(GROUP_USER_PERMISSION_ADMIN, GROUP_USER_PERMISSION_READER, GROUP_USER_PERMISSION_MODERATOR), 0 , 1000);
$new_member_list = array();

$social_left_content = SocialManager::show_social_menu('member_list',$group_id);

$social_right_content = '<h2>'.$group_info['name'].'</h2>';

$social_right_content .= '<div style="width:90%">';

foreach($users as $user) {
    switch ($user['relation_type']) {
        case  GROUP_USER_PERMISSION_ADMIN:
            $user['link'] = Display::return_icon('social_group_admin.png', get_lang('Admin'));
        break;
        case  GROUP_USER_PERMISSION_READER:
            if (in_array($user_role, array(GROUP_USER_PERMISSION_ADMIN, GROUP_USER_PERMISSION_MODERATOR))) {
            $user['link'] = '<a href="group_members.php?id='.$group_id.'&u='.$user['user_id'].'&action=delete">'.Display::return_icon('delete.png', get_lang('DeleteFromGroup')).'</a>'.
                            '<a href="group_members.php?id='.$group_id.'&u='.$user['user_id'].'&action=set_moderator">'.Display::return_icon('social_moderator_add.png', get_lang('AddModerator')).'</a>';
            }
        break;
        case  GROUP_USER_PERMISSION_PENDING_INVITATION:
            $user['link'] = '<a href="group_members.php?id='.$group_id.'&u='.$user['user_id'].'&action=add">'.Display::return_icon('pending_invitation.png', get_lang('PendingInvitation')).'</a>';
        break;
        case  GROUP_USER_PERMISSION_MODERATOR:
            $user['link'] = Display::return_icon('social_group_moderator.png', get_lang('Moderator'));
            //only group admin can manage moderators
            if ($user_role == GROUP_USER_PERMISSION_ADMIN) {
                $user['link'] .='<a href="group_members.php?id='.$group_id.'&u='.$user['user_id'].'&action=delete_moderator">'.Display::return_icon('social_moderator_delete.png', get_lang('DeleteModerator')).'</a>';
            }
        break;
    }

    $image_path = UserManager::get_user_picture_path_by_id($user['user_id'], 'web', false, true);
    $picture = UserManager::get_picture_user($user['user_id'], $image_path['file'],80);
    $user['image'] = '<img src="'.$picture['file'].'"  width="50px" height="50px"  />';

    $new_member_list[] = $user;
}
if (count($new_member_list) > 0) {
    $social_right_content .= Display::return_sortable_grid('list_members', array(), $new_member_list, array('hide_navigation'=>true, 'per_page' => 100), $query_vars, false, array(true, false, true,true,false,true,true));
}
$social_right_content .= '</div>';

$tpl = new Template($tool_name);
$tpl->set_help('Groups');
$tpl->assign('social_left_content', $social_left_content);
$tpl->assign('social_left_menu', $social_left_menu);
$tpl->assign('social_right_content', $social_right_content);
$tpl->assign('actions', $actions);
$tpl->assign('message', $show_message);
$tpl->assign('content', $content);
$social_layout = $tpl->get_template('layout/social_layout.tpl');
$tpl->display($social_layout);

