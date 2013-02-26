<?php
/* For licensing terms, see /license.txt */
/**
 * Management of legal conditions
 * @package chamilo.admin
 */
/**
 * Code
 */
$language_file = array('admin','registration');
$cidReset = true;
require_once '../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

// Create the form
$form = new FormValidator('addlegal');

$defaults=array();
if( $form->validate()) {
	$check = Security::check_token('post');
		if ($check) {
			$values  = $form->getSubmitValues();
			$lang 	 = $values['language'];
			//language id
			$lang = api_get_language_id($lang);

			$type 	 = $values['type'];
			$content = $values['content'];
			$changes = $values['changes'];
			$navigator_info = api_get_navigator();

			if ($navigator_info['name']=='Internet Explorer' &&  $navigator_info['version']=='6') {
				if (isset($values['preview'])) {
					$submit	='preview';
				} elseif (isset($values['save'])) {
					$submit	='save';
				} elseif (isset($values['back'])) {
					$submit	='back';
				}
			} else {
				$submit  = $values['send'];
			}

			$default['content']=$content;
			if (isset($values['language'])) {
				if($submit=='back') {
					header('Location: legal_add.php');
					exit;
				} elseif($submit=='save') {
					$insert_result = LegalManager::add($lang,$content,$type,$changes);
					if ($insert_result )
						$message = get_lang('TermAndConditionSaved');
					else
						$message = get_lang('TermAndConditionNotSaved');
					Security::clear_token();
					$tok = Security::get_token();
					header('Location: legal_list.php?action=show_message&message='.urlencode($message).'&sec_token='.$tok);
					exit();
				} elseif($submit=='preview') {
					$defaults['type']=$type;
					$defaults['content']=$content;
					$defaults['changes']=$changes;
					$term_preview = $defaults;
					$term_preview['type'] = intval($_POST['type']);
				} else {
					$my_lang = $_POST['language'];
					if (isset($_POST['language'])){
						$all_langs = api_get_languages();
						if (in_array($my_lang, $all_langs['folder'])){
							$language = api_get_language_id($my_lang);
							$term_preview = LegalManager::get_last_condition($language);
							$defaults = $term_preview;
							if (!$term_preview) {
								// there are not terms and conditions
								$term_preview['type']=-1;
								$defaults['type']=0;
							}
						}
					}
				}
			}
		}
}

$form->setDefaults($default);

if(isset($_POST['send'])) {
	Security::clear_token();
}
$token = Security::get_token();

$form->addElement('hidden','sec_token');
$form->setConstants(array('sec_token' => $token));
$form->addElement('header', get_lang('DisplayTermsConditions'));

if (isset($_POST['language'])) {

	$form->addElement('static', Security::remove_XSS($_POST['language']));
	$form->addElement('hidden', 'language',Security::remove_XSS($_POST['language']));
	$form->add_html_editor('content', get_lang('Content'), true, false, array('ToolbarSet' => 'terms_and_conditions', 'Width' => '100%', 'Height' => '250'));

	$form->addElement('radio', 'type', '', get_lang('HTMLText') ,'0');
	$form->addElement('radio', 'type', '', get_lang('PageLink') ,'1');
	$form->addElement('textarea', 'changes', get_lang('ExplainChanges'),array('width'=>'20'));

	$preview = LegalManager::show_last_condition($term_preview);

	if ($term_preview['type']!=-1) {
		$form->addElement('label', get_lang('Preview'),  $preview);
	}

	// Submit & preview button
    $navigator_info = api_get_navigator();

    //ie6 fix
	if ($navigator_info['name']=='Internet Explorer' &&  $navigator_info['version']=='6') {
		$buttons = '<div class="row" align="center">
				<div class="formw">
				<input type="submit" name="back"  value="'.get_lang('Back').'"/>
				<input type="submit" name="preview"  value="'.get_lang('Preview').'"/>
				<input type="submit" name="save"  value="'.get_lang('Save').'"/>
				</div>
			</div>';
			$form->addElement('html',$buttons);
	} else {
        $buttons = '<div class="row" align="center">
					<div class="formw">
					<button type="submit" class="back" 	 name="send" value="back">'.get_lang('Back').'</button>
					<button type="submit" class="search" name="send" value="preview">'.get_lang('Preview').'</button>
					<button type="submit" class="save" 	 name="send" value="save">'.get_lang('Save').'</button>
					</div>
				</div>';
		$form->addElement('html',$buttons);
	}
} else {
	$form->addElement('select_language', 'language', get_lang('Language'),null,array());
	$form->addElement('button', 'send', get_lang('Load'));

}

$tool_name = get_lang('AddTermsAndConditions');
$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));
Display :: display_header($tool_name);

echo '<script>
function sendlang(){
	//document.addlegal.send.value=\'load\';
	document.addlegal.sec_token.value=\''.$token.'\';
	document.addlegal.submit();
}
</script>';

// action menu
echo '<div class="actions">';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/legal_list.php">'.Display::return_icon('search.gif',get_lang('EditTermsAndConditions'),'').get_lang('AllVersions').'</a>';
echo '</div>';

if (isset ($_GET['action'])) {
	switch ($_GET['action']) {
		case 'show_message' :
			Display :: display_normal_message(stripslashes($_GET['message']));
			break;
	}
}

$form->setDefaults($defaults);
$form->display();
Display :: display_footer();
