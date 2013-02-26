<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.social
 * @author Julio Montoya <gugli100@gmail.com>
 */
/**
 * Initialization
 */
$language_file= 'userInfo';
$cidReset=true;
require_once '../inc/global.inc.php';

api_block_anonymous_users();
if (api_get_setting('allow_social_tool') !='true') {
    api_not_allowed();
}

require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';

if (api_get_setting('allow_students_to_create_groups_in_social') == 'false' && !api_is_allowed_to_edit()) {
	api_not_allowed();
}

global $charset;
//jquery already called from main/inc/header.inc.php

$htmlHeadXtra[] = '<script type="text/javascript">
textarea = "";
num_characters_permited = 255;
function text_longitud(){
   num_characters = document.forms[0].description.value.length;
  if (num_characters > num_characters_permited){
      document.forms[0].description.value = textarea;
   }else{
      textarea = document.forms[0].description.value;
   }
}
</script>';

$table_message = Database::get_main_table(TABLE_MESSAGE);

$form = new FormValidator('add_group');

// name
$form->addElement('text', 'name', get_lang('Name'), array('class'=>'span5', 'maxlength'=>120));
$form->applyFilter('name', 'html_filter');
$form->applyFilter('name', 'trim');
$form->addRule('name', get_lang('ThisFieldIsRequired'), 'required');

// Description
$form->addElement('textarea', 'description', get_lang('Description'), array('class'=>'span5', 'cols'=>58, 'onKeyDown' => "text_longitud()", 'onKeyUp' => "text_longitud()"));
$form->applyFilter('description', 'html_filter');
$form->applyFilter('description', 'trim');

// url
$form->addElement('text', 'url', get_lang('URL'), array('class'=>'span5'));
$form->applyFilter('url', 'html_filter');
$form->applyFilter('url', 'trim');

// Picture
$form->addElement('file', 'picture', get_lang('AddPicture'));
$allowed_picture_types = array ('jpg', 'jpeg', 'png', 'gif');
$form->addRule('picture', get_lang('OnlyImagesAllowed').' ('.implode(',', $allowed_picture_types).')', 'filetype', $allowed_picture_types);

// Status
$status = array();
$status[GROUP_PERMISSION_OPEN] 		= get_lang('Open');
$status[GROUP_PERMISSION_CLOSED]	= get_lang('Closed');

$form->addElement('select', 'visibility', get_lang('GroupPermissions'), $status);
$form->addElement('style_submit_button','add_group', get_lang('AddGroup'),'class="save"');

$form->setRequiredNote(api_xml_http_response_encode('<span class="form_required">*</span> <small>'.get_lang('ThisFieldIsRequired').'</small>'));

if ($form->validate()) {
	$values = $form->exportValues();

	$picture_element = $form->getElement('picture');
	$picture 		= $picture_element->getValue();
	$picture_uri 	= '';
	$name 			= $values['name'];
	$description	= $values['description'];
	$url 			= $values['url'];
	$status 		= intval($values['visibility']);
	$picture 		= $_FILES['picture'];

	$group_id = GroupPortalManager::add($name, $description, $url, $status);
	GroupPortalManager::add_user_to_group(api_get_user_id(), $group_id,GROUP_USER_PERMISSION_ADMIN);

	if (!empty($picture['name'])) {
		$picture_uri = GroupPortalManager::update_group_picture($group_id, $_FILES['picture']['name'], $_FILES['picture']['tmp_name']);
		GroupPortalManager::update($group_id, $name, $description, $url,$status, $picture_uri);
	}
	header('Location: groups.php?id='.$group_id.'&action=show_message&message='.urlencode(get_lang('GroupAdded')));
	exit();
}

$nameTools = get_lang('AddGroup');
$this_section = SECTION_SOCIAL;

$interbreadcrumb[]= array ('url' =>'home.php','name' => get_lang('Social'));
$interbreadcrumb[]= array ('url' =>'groups.php','name' => get_lang('Groups'));
$interbreadcrumb[]= array ('url' =>'#','name' => $nameTools);

$social_left_content = SocialManager::show_social_menu('group_add');

$social_right_content = $form->return_form();

$tpl = new Template();
$tpl->set_help('Groups');
$tpl->assign('social_left_content', $social_left_content);
$tpl->assign('social_right_content', $social_right_content);
//$tpl->assign('actions', $actions);
//$tpl->assign('message', $show_message);
$social_layout = $tpl->get_template('layout/social_layout.tpl');
$tpl->display($social_layout);
