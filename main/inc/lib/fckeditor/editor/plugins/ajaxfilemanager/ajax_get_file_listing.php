<?php

/**
 * the php script used to get the list of file or folders under a specific folder
 * @author Logan Cai (cailongqun [at] yahoo [dot] com [dot] cn)
 * @link www.phpletter.com
 * @since 22/May/2007
 *
 * Modify for Chamilo
 * @author Juan Carlos Ra�a Trabado
 * @since 31/December/2008
 */
require_once '../../../../../../inc/global.inc.php'; // Integrating with Chamilo

if (!isset($manager)) {
    /**
     *  this is part of  script for processing file paste
     */
    //$_GET = $_POST;
    include_once dirname(__FILE__).DIRECTORY_SEPARATOR."inc".DIRECTORY_SEPARATOR."config.php";
    include_once CLASS_PAGINATION;
    $pagination = new pagination(false);
    $search_folder = null;
    if (isset($_GET['search_folder'])) {
        $search_folder = str_replace("'", "", $_GET['search_folder']); //security fix for Chamilo by cfasanando
    }

    if (!empty($_GET['search'])) {
        include_once(CLASS_SEARCH);

        $search = new Search($search_folder); //security fix for Chamilo by cfasanando
        $search->addSearchKeyword('recursive', @$_GET['search_recursively']);
        $search->addSearchKeyword('mtime_from', @$_GET['search_mtime_from']);
        $search->addSearchKeyword('mtime_to', @$_GET['search_mtime_to']);
        $search->addSearchKeyword('size_from', @$_GET['search_size_from']);
        $search->addSearchKeyword('size_to', @$_GET['search_size_to']);
        $search->addSearchKeyword('recursive', @$_GET['search_recursively']);
        $search->addSearchKeyword('name', @$_GET['search_name']);
        $search->doSearch();
        $fileList = $search->getFoundFiles();
        $folderInfo = $search->getRootFolderInfo();
    } else {
        include_once(CLASS_MANAGER);
        include_once(CLASS_SESSION_ACTION);
        $sessionAction = new SessionAction();
        include_once(DIR_AJAX_INC."class.manager.php");

        $manager = new manager();
        $manager->setSessionAction($sessionAction);

        $fileList = $manager->getFileList();
        $folderInfo = $manager->getFolderInfo();
    }
    $pagination->setUrl(CONFIG_URL_FILEnIMAGE_MANAGER);
} else {
    include_once(CLASS_PAGINATION);
    $pagination = new pagination(false);
}

$pagination->setTotal(sizeof($fileList));
$pagination->setFirstText(PAGINATION_FIRST);
$pagination->setPreviousText(PAGINATION_PREVIOUS);
$pagination->setNextText(PAGINATION_NEXT);
$pagination->setLastText(PAGINATION_LAST);
$pagination->setLimit(!empty($_GET['limit']) ? intval($_GET['limit']) : CONFIG_DEFAULT_PAGINATION_LIMIT);
echo $pagination->getPaginationHTML();

// Chamilo fix for count hidden folders
$count_hideItem = 0;

$deleted_by_Chamilo_file = ' DELETED '; // ' DELETED ' not '_DELETED_' because in $file['name'] _ is replaced with blank see class.manager.php
$deleted_by_Chamilo_folder = '_DELETED_';
$css_folder_Chamilo = 'css';
$hotpotatoes_folder_Chamilo = 'HotPotatoes_files';
$chat_files_Chamilo = 'chat_files';
$certificates_Chamilo = 'certificates';

//end previous fix for count hidden folders

echo "<script>";

echo "parentFolder = {path_base64:'".base64_encode(getParentFolderPath($folderInfo['path']))."', path:'".getParentFolderPath($folderInfo['path'])."'};";
echo 'currentFolder ={';
$count = 1;
foreach ($folderInfo as $k => $v) {
    echo ($count++ == 1 ? '' : ',')."'".$k."':'".($k == 'ctime' || $k == 'mtime' ? date(DATE_TIME_FORMAT, $v) : $v)."'";
}
echo "};";

// moved below for Chamilo integration $fileList = array_slice($fileList, $pagination->getPageOffset(), $pagination->getLimit());
echo 'numRows = '.sizeof($fileList).";\n";
echo "files = {\n";
$count = 1;

foreach ($fileList as $file) {
    //show group's directory only if I'm member. Or if I'm a teacher. TODO: check groups not necessary because the student dont have access to main folder documents (only to document/group or document/shared_folder). Teachers can access to all groups ?
    $group_folder = '_groupdocs';
    $hide_doc_group = false;
    if (preg_match("/$group_folder/", $file['path'])) {
        $hide_doc_group = true;
        if ($is_user_in_group || ( $to_group_id != 0 && api_is_allowed_to_edit())) {
            $hide_doc_group = false;
        }
    }

    if ((!preg_match("/$deleted_by_Chamilo_file/", $file['name']) ||
        !preg_match("/$deleted_by_Chamilo_folder/", $file['path'])) ||
        preg_match("/$css_folder_Chamilo/", $file['path']) ||
        preg_match("/$hotpotatoes_folder_Chamilo/", $file['path']) ||
        preg_match("/$chat_files_Chamilo/", $file['path']) ||
        preg_match("/$certificates_Chamilo/", $file['path']) ||
        $hide_doc_group ||
        $file['name'][0] == '.') { //Chamilo fix for hidden items.
        $count_hideItem = $count_hideItem + 1;
    }

    echo (($count > 1) ? "," : '').$count++.":{";
    $j = 1;
    foreach ($file as $k => $v) {
        if ($k == 'ctime' || $k == 'mtime') {
            $v = @date(DATE_TIME_FORMAT, $v);
        }
        if ($k == 'size') {
            $v = transformFileSize($v);
        }
        echo (($j++ > 1) ? "," : '')."'".$k."':'".$v."'";
    }
    echo (($j++ > 1) ? "," : '')."'url':'".getFileUrl($file['path'])."'";
    echo "}\n";
}
$fileList = array_slice($fileList, $pagination->getPageOffset(), $pagination->getLimit()); //Chamilo fix for hidden files added +$count_hideItem

echo "};</script>";
if (!empty($_GET['view'])) {
    switch ($_GET['view']) {
        case 'detail':
        case 'thumbnail':
        case 'text':
            $view = $_GET['view'];
            break;
        default:
            $view = CONFIG_DEFAULT_VIEW;
    }
} else {
    $view = CONFIG_DEFAULT_VIEW;
}

switch ($view) {
    case 'text':
        //list file name only
        include_once(DIR_AJAX_ROOT.'_ajax_get_text_listing.php');
        break;
    case 'thumbnail':
        //list file with thumbnail
        include_once DIR_AJAX_ROOT.'_ajax_get_thumbnail_listing.php';
        break;
    case 'detail':
    default:
        include_once(DIR_AJAX_ROOT.'_ajax_get_details_listing.php');
}