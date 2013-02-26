<?php

/* For licensing terms, see /license.txt */
/**
 * Display part of the SCORM sub-process for upload. This script MUST BE included by upload/index.php
 * as it prepares most of the variables needed here.
 * @package chamilo.upload
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */

/**
 * Small function to list files in archive/
 */
function get_zip_files_in_garbage() {
    $list = array();
    $dh = opendir(api_get_path(SYS_ARCHIVE_PATH));
    if ($dh === false) {
        //ignore
    } else {
        while ($entry = readdir($dh)) {
            if (substr($entry, 0, 1) == '.') {/* ignore files starting with . */
            } else {
                if (preg_match('/^.*\.zip$/i', $entry)) {
                    $list[] = $entry;
                }
            }
        }
        natcasesort($list);
        closedir($dh);
    }
    return $list;
}

/**
 * Just display the form needed to upload a SCORM and give its settings
 */
$nameTools = get_lang("FileUpload");
$interbreadcrumb[] = array("url" => "../newscorm/lp_controller.php?action=list", "name" => get_lang("ToolLearnpath"));
Display::display_header($nameTools, "Path");

require_once '../newscorm/content_makers.inc.php';
require_once api_get_path(LIBRARY_PATH).'specific_fields_manager.lib.php';

echo '<div class="actions">';
echo '<a href="../newscorm/lp_controller.php?cidReq='.$_course['sysCode'].'">'.Display::return_icon('back.png', get_lang('ReturnToLearningPaths'), '', ICON_SIZE_MEDIUM).'</a>';
echo '</div>';

$form = new FormValidator('', 'POST', 'upload.php', '', 'id="upload_form" enctype="multipart/form-data" style="background-image: url(\'../img/scorm.jpg\'); background-repeat: no-repeat; background-position: 620px;"');
$form->addElement('header', '', $nameTools);
$form->addElement('hidden', 'curdirpath', $path);
$form->addElement('hidden', 'tool', $my_tool);

$form->addElement('file', 'user_file', get_lang('FileToUpload'));
$form->add_real_progress_bar('uploadScorm', 'user_file');
$form->addRule('user_file', get_lang('ThisFieldIsRequired'), 'required');

unset($content_origins[0]);
unset($content_origins[1]);

if (api_get_setting('search_enabled') == 'true') {
    $form->addElement('checkbox', 'index_document', '', get_lang('SearchFeatureDoIndexDocument'));
    $specific_fields = get_specific_field_list();
    foreach ($specific_fields as $specific_field) {
        $form->addElement('text', $specific_field['code'], $specific_field['name'].' : ');
    }
}

if (api_is_platform_admin()) {
    $form->addElement('checkbox', 'use_max_score', null, get_lang('UseMaxScore100'));
}

$form->addElement('style_submit_button', 'submit', get_lang('Send'), 'class="upload"');

$form->addElement('html', '<br /><br /><br />');

if (is_dir(api_get_path(PLUGIN_PATH)."/pens")) {
    require_once(api_get_path(PLUGIN_PATH)."/pens/chamilo_pens.php");
    $list = ChamiloPens::findAll();
    if (count($list) > 0) {
        $select_pens = $form->addElement('select', 'pens_package', get_lang('Or').' '.get_lang('select a PENS package'));
        foreach ($list as $package) {
            $select_pens->addOption($package->getPackageName(), $package->getPackageName());
        }
    }
}

// the default values for the form
$defaults = array('index_document' => 'checked="checked"', 'use_max_score' => 1);
$form->setDefaults($defaults);
Display::display_normal_message(Display::tag('strong', get_lang('SupportedScormContentMakers')).': '.implode(', ', $content_origins), false);
$form->display();

// footer
Display::display_footer();