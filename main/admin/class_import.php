<?php
/* For licensing terms, see /license.txt */
/**
*	@package chamilo.admin
*/
/**
 * Code
*   This     tool allows platform admins to add classes by uploading a CSV file
* @todo Add some langvars to DLTT
*/

/**
 * Validates imported data.
 */
function validate_data($classes) {
    $errors = array();
    foreach ($classes as $index => $class) {
        // 1. Check wheter ClassName is available.
        if (!isset($class['ClassName']) || strlen(trim($class['ClassName'])) == 0) {
            $class['line'] = $index + 2;
            $class['error'] = get_lang('MissingClassName');
            $errors[] = $class;
        }
        // 2. Check whether class doesn't exist yet.
        else {
            if (ClassManager::class_name_exists($class['ClassName'])) {
                $class['line'] = $index + 2;
                $class['error'] = get_lang('ClassNameExists').' <strong>'.$class['ClassName'].'</strong>';
                $errors[] = $class;
            }
        }
    }
    return $errors;
}

/**
 * Save imported class data to database
 */
function save_data($classes) {
    $number_of_added_classes = 0;
    foreach ($classes as $index => $class) {
        if (ClassManager::create_class($class['ClassName'])) {
            $number_of_added_classes++;
        }
    }
    return $number_of_added_classes;
}

// Language files that should be included.
$language_file = array ('admin', 'registration');

// Resetting the course id.
$cidReset = true;

// Including some necessary dokeos files.
include '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
require_once api_get_path(LIBRARY_PATH).'classmanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'import.lib.php';

// Setting the section (for the tabs).
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions.
api_protect_admin_script();

// setting breadcrumbs
$interbreadcrumb[] = array ('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array ('url' => 'class_list.php', 'name' => get_lang('Classes'));

// Database Table Definitions

// Setting the name of the tool.
$tool_name = get_lang('ImportClassListCSV');

// Displaying the header.
Display :: display_header($tool_name);
//api_display_tool_title($tool_name);

set_time_limit(0);

$form = new FormValidator('import_classes');
$form->addElement('file', 'import_file', get_lang('ImportCSVFileLocation'));
$form->addElement('style_submit_button', 'submit', get_lang('Import'), 'class="save"');

if ($form->validate()) {
    $classes = Import::csv_to_array($_FILES['import_file']['tmp_name']);
    $errors = validate_data($classes);
    if (count($errors) == 0) {
        $number_of_added_classes = save_data($classes);
        Display::display_normal_message($number_of_added_classes.' '.get_lang('ClassesCreated'));
    } else {
        $error_message = get_lang('ErrorsWhenImportingFile');
        $error_message .= '<ul>';
        foreach ($errors as $index => $error_class) {
            $error_message .= '<li>'.$error_class['error'].' ('.get_lang('Line').' '.$error_class['line'].')';
            $error_message .= '</li>';
        }
        $error_message .= '</ul>';
        $error_message .= get_lang('NoClassesHaveBeenCreated');
        Display :: display_error_message($error_message);
    }
}

$form->display();
?>
<p><?php echo get_lang('CSVMustLookLike').' ('.get_lang('MandatoryFields').')'; ?> :</p>

 <pre>
  <b>ClassName</b>
  <b>1A</b>
  <b>1B</b>
  <b>2A group 1</b>
  <b>2A group 2</b>
 </pre>
<?php

// Displaying the footer.
Display :: display_footer();