<?php
/* For licensing terms, see /license.txt */

/**
 *	Functions and main code for the download folder feature
 *  @todo use ids instead of the path like the document tool
 *	@package chamilo.work
 */

$work_id = $_GET['id'];
require_once '../inc/global.inc.php';
$current_course_tool  = TOOL_STUDENTPUBLICATION;

//protection
api_protect_course_script(true);

require_once 'work.lib.php';

$work_data = get_work_data_by_id($work_id);
if (empty($work_data)) {
    exit;
}

//prevent some stuff
if (empty($path)) {
	$path = '/';
}

if (empty($_course) || empty($_course['path'])) {
    api_not_allowed();
}
$sys_course_path = api_get_path(SYS_COURSE_PATH);

//zip library for creation of the zipfile
require_once api_get_path(LIBRARY_PATH).'pclzip/pclzip.lib.php';

//Creating a ZIP file
$temp_zip_file = api_get_path(SYS_ARCHIVE_PATH).api_get_unique_id().".zip";

$zip_folder = new PclZip($temp_zip_file);

$tbl_student_publication = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
$prop_table              = Database::get_course_table(TABLE_ITEM_PROPERTY);

//Put the files in the zip
//2 possibilities: admins get all files and folders in the selected folder (except for the deleted ones)
//normal users get only visible files that are in visible folders

//admins are allowed to download invisible files
$files = array();
$course_id = api_get_course_int_id();

if (api_is_allowed_to_edit()) {
    //Search for all files that are not deleted => visibility != 2
    $sql = "SELECT url, title, description, insert_user_id, insert_date, contains_file
            FROM $tbl_student_publication AS work INNER JOIN $prop_table AS props
                ON (props.c_id = $course_id AND
                    work.c_id = $course_id AND
                    work.id = props.ref)
 			WHERE   props.tool='work' AND
 			        work.parent_id = $work_id AND
 			        work.filetype = 'file' AND
 			        props.visibility<>'2' ";

} else {
    //for other users, we need to create a zipfile with only visible files and folders
    $sql = "SELECT url, title, description, insert_user_id, insert_date, contains_file
            FROM $tbl_student_publication AS work INNER JOIN $prop_table AS props
                ON (props.c_id = $course_id AND
                    work.c_id = $course_id AND
                    work.id = props.ref)
           WHERE
                    props.tool='work' AND
                    work.accepted = 1 AND
                    work.parent_id = $work_id AND
                    work.filetype='file' AND
                    props.visibility = '1' AND
                    props.insert_user_id='".api_get_user_id()."'
            ";
}

$query = Database::query($sql);

//add tem to the zip file
while ($not_deleted_file = Database::fetch_assoc($query)) {

    $user_info = api_get_user_info($not_deleted_file['insert_user_id']);
    $insert_date = api_get_local_time($not_deleted_file['insert_date']);
    $insert_date = str_replace(array(':','-', ' '), '_', $insert_date);
    $filename = $insert_date.'_'.$user_info['username'].'_'.basename($not_deleted_file['title']);

    if (file_exists($sys_course_path.$_course['path'].'/'.$not_deleted_file['url']) && !empty($not_deleted_file['url'])) {
        $files[basename($not_deleted_file['url'])] = $filename;
        $zip_folder->add($sys_course_path.$_course['path'].'/'.$not_deleted_file['url'], PCLZIP_OPT_REMOVE_PATH, $sys_course_path.$_course['path'].'/work', PCLZIP_CB_PRE_ADD, 'my_pre_add_callback');
    }

    //Convert texts in html files
    if ($not_deleted_file['contains_file'] == 0) {
        $filename = trim($filename).".html";
        $work_temp = api_get_path(SYS_ARCHIVE_PATH).api_get_unique_id().'_'.$filename;
        file_put_contents($work_temp, $not_deleted_file['description']);
        $files[basename($work_temp)] = $filename;
        $zip_folder->add($work_temp, PCLZIP_OPT_REMOVE_PATH, api_get_path(SYS_ARCHIVE_PATH), PCLZIP_CB_PRE_ADD, 'my_pre_add_callback');
        @unlink($work_temp);
    }
}


if (!empty($files)) {
    //logging
    event_download(basename($work_data['title']).'.zip (folder)');

    //start download of created file
    $name = basename($work_data['title']).'.zip';

    if (Security::check_abs_path($temp_zip_file, api_get_path(SYS_ARCHIVE_PATH))) {
        DocumentManager::file_send_for_download($temp_zip_file, true, $name);
        @unlink($temp_zip_file);
        exit;
    }
} else {
    exit;
}

/*	Extra function (only used here) */

function my_pre_add_callback($p_event, &$p_header) {
	global $files;
	if (isset($files[basename($p_header['stored_filename'])])) {
		$p_header['stored_filename'] = $files[basename($p_header['stored_filename'])];
		return 1;
	}
	return 0;
}

/**
 * Return the difference between two arrays, as an array of those key/values
 * Use this as array_diff doesn't give the
 *
 * @param array $arr1 first array
 * @param array $arr2 second array
 * @return difference between the two arrays
 */
function diff($arr1, $arr2) {
	$res = array();
	$r = 0;
	foreach ($arr1 as $av) {
		if (!in_array($av, $arr2)) {
			$res[$r] = $av;
			$r++;
		}
	}
	return $res;
}