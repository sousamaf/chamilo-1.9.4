<?php

/* For licensing terms, see /license.txt */
/**
 * EXTRA FUNCTIONS FOR DOCUMENTS TOOL
 * @package chamilo.document
 */

/**
 * Builds the form thats enables the user to
 * select a directory to browse/upload in
 *
 * @param array 	An array containing the folders we want to be able to select
 * @param string	The current folder (path inside of the "document" directory, including the prefix "/")
 * @param string	Group directory, if empty, prevents documents to be uploaded (because group documents cannot be uploaded in root)
 * @param	boolean	Whether to change the renderer (this will add a template <span> to the QuickForm object displaying the form)
 * @todo this funcionality is really bad : jmontoya
 * @return string html form
 */
function build_directory_selector($folders, $document_id, $group_dir = '', $change_renderer = false) {
    $doc_table = Database::get_course_table(TABLE_DOCUMENT);
    $course_id = api_get_course_int_id();
    $folder_titles = array();

    if (is_array($folders)) {
        $escaped_folders = array();
        foreach ($folders as $key => & $val) {
            $escaped_folders[$key] = Database::escape_string($val);
        }
        $folder_sql = implode("','", $escaped_folders);

        $sql = "SELECT * FROM $doc_table WHERE filetype = 'folder' AND c_id = $course_id AND path IN ('" . $folder_sql . "')";
        $res = Database::query($sql);
        $folder_titles = array();
        while ($obj = Database::fetch_object($res)) {
            $folder_titles[$obj->path] = $obj->title;
        }
    }


    $form = new FormValidator('selector', 'GET', api_get_self() . '?' . api_get_cidreq());
    $form->addElement('hidden', 'cidReq', api_get_course_id());
    $parent_select = $form->addElement('select', 'id', get_lang('CurrentDirectory'), '', 'onchange="javascript: document.selector.submit();"');

    if ($change_renderer) {
        $renderer = $form->defaultRenderer();
        $renderer->setElementTemplate('<span>{label} : {element}</span> ', 'curdirpath');
    }

    // Group documents cannot be uploaded in the root
    if (empty($group_dir)) {
        $parent_select->addOption(get_lang('Documents'), '/');

        if (is_array($folders)) {
            foreach ($folders as $folder_id => & $folder) {
                $selected = ($document_id == $folder_id) ? ' selected="selected"' : '';
                $path_parts = explode('/', $folder);
                $folder_titles[$folder] = cut($folder_titles[$folder], 80);
                $counter = count($path_parts) - 2;
                if ($counter > 0) {
                    $label = str_repeat('&nbsp;&nbsp;&nbsp;', $counter) . ' &mdash; ' . $folder_titles[$folder];
                } else {
                    $label = ' &mdash; ' . $folder_titles[$folder];
                }
                $parent_select->addOption($label, $folder_id);
                if ($selected != '') {
                    $parent_select->setSelected($folder_id);
                }
            }
        }
    } else {
        if (!empty($folders)) {
            foreach ($folders as $folder_id => & $folder) {
                $selected = ($document_id == $folder_id) ? ' selected="selected"' : '';
                $label = $folder_titles[$folder];
                if ($folder == $group_dir) {
                    $label = get_lang('Documents');
                } else {
                    $path_parts = explode('/', str_replace($group_dir, '', $folder));
                    $label = cut($label, 80);
                    $label = str_repeat('&nbsp;&nbsp;&nbsp;', count($path_parts) - 2) . ' &mdash; ' . $label;
                }
                $parent_select->addOption($label, $folder_id);
                if ($selected != '') {
                    $parent_select->setSelected($folder_id);
                }
            }
        }
    }
    $html = $form->toHtml();
    return $html;
}

/**
 * Create a html hyperlink depending on if it's a folder or a file
 *
 * @param string $www
 * @param string $title
 * @param string $path
 * @param string $filetype (file/folder)
 * @param int $visibility (1/0)
 * @param int $show_as_icon - if it is true, only a clickable icon will be shown
 * @return string url
 */
function create_document_link($document_data, $show_as_icon = false, $counter = null, $visibility) {
    global $dbl_click_id;
    if (isset($_SESSION['_gid'])) {
        $req_gid = '&amp;gidReq=' . $_SESSION['_gid'];
    } else {
        $req_gid = '';
    }
    $course_info = api_get_course_info();
    $www = api_get_path(WEB_COURSE_PATH) . $course_info['path'] . '/document';

    // Get the title or the basename depending on what we're using
    if ($document_data['title'] != '') {
        $title = $document_data['title'];
    } else {
        $title = basename($document_data['path']);
    }

    $filetype = $document_data['filetype'];
    $size = $filetype == 'folder' ? get_total_folder_size($document_data['path'], api_is_allowed_to_edit(null, true)) : $document_data['size'];
    $path = $document_data['path'];

    $url_path = urlencode($document_data['path']);

    // Add class="invisible" on invisible files
    $visibility_class = ($visibility == false) ? ' class="muted"' : '';

    if (!$show_as_icon) {
        // Build download link (icon)
        $forcedownload_link = ($filetype == 'folder') ? api_get_self() . '?' . api_get_cidreq() . '&action=downloadfolder&id=' . $document_data['id'] : api_get_self() . '?' . api_get_cidreq() . '&amp;action=download&amp;id=' . $document_data['id'];
        // Folder download or file download?
        $forcedownload_icon = ($filetype == 'folder') ? 'save_pack.png' : 'save.png';
        // Prevent multiple clicks on zipped folder download
        $prevent_multiple_click = ($filetype == 'folder') ? " onclick=\"javascript: if(typeof clic_$dbl_click_id == 'undefined' || !clic_$dbl_click_id) { clic_$dbl_click_id=true; window.setTimeout('clic_" . ($dbl_click_id++) . "=false;',10000); } else { return false; }\"" : '';
    }

    $target = '_self';
    $is_browser_viewable_file = false;

    if ($filetype == 'file') {
        // Check the extension
        $ext = explode('.', $path);
        $ext = strtolower($ext[sizeof($ext) - 1]);

        // HTML-files an some other types are shown in a frameset by default.
        $is_browser_viewable_file = is_browser_viewable($ext);

        if ($is_browser_viewable_file) {
            //$url = 'showinframes.php?'.api_get_cidreq().'&amp;file='.$url_path.$req_gid;
            $url = 'showinframes.php?' . api_get_cidreq() . '&id=' . $document_data['id'] . $req_gid;
        } else {
            // url-encode for problematic characters (we may not call them dangerous characters...)
            $path = str_replace('%2F', '/', $url_path) . '?' . api_get_cidreq();
            //$new_path = '?id='.$document_data['id'];
            $url = $www . $path;
        }
        //$path = str_replace('%2F', '/',$url_path).'?'.api_get_cidreq();
        $path = str_replace('%2F', '/', $url_path); //yox view hack otherwise the image can't be well read 
        $url = $www . $path;

        // Disabled fragment of code, there is a special icon for opening in a new window.
        //// Files that we want opened in a new window
        //if ($ext == 'txt' || $ext == 'log' || $ext == 'css' || $ext == 'js') { // Add here
        //    $target = '_blank';
        //}
    } else {
        //$url = api_get_self().'?'.api_get_cidreq().'&amp;curdirpath='.$url_path.$req_gid;
        $url = api_get_self() . '?' . api_get_cidreq() . '&id=' . $document_data['id'] . $req_gid;
    }

    // The little download icon
    //$tooltip_title = str_replace('?cidReq='.$_GET['cidReq'], '', basename($path));
    $tooltip_title = explode('?', basename($path));

    $tooltip_title = $title;

    //Cut long titles
    //$title = cut($title, 120);

    $tooltip_title_alt = $tooltip_title;
    if ($path == '/shared_folder') {
        $tooltip_title_alt = get_lang('UserFolders');
    } elseif (strstr($path, 'shared_folder_session_')) {
        $tooltip_title_alt = get_lang('UserFolders') . ' (' . api_get_session_name(api_get_session_id()) . ')';
    } elseif (strstr($tooltip_title, 'sf_user_')) {
        $userinfo = Database::get_user_info_from_id(substr($tooltip_title, 8));
        $tooltip_title_alt = get_lang('UserFolder') . ' ' . api_get_person_name($userinfo['firstname'], $userinfo['lastname']);
    } elseif ($path == '/chat_files') {
        $tooltip_title_alt = get_lang('ChatFiles');
    } elseif ($path == '/learning_path') {
        $tooltip_title_alt = get_lang('LearningPaths');
    } elseif ($path == '/video') {
        $tooltip_title_alt = get_lang('Video');
    } elseif ($path == '/audio') {
        $tooltip_title_alt = get_lang('Audio');
    } elseif ($path == '/flash') {
        $tooltip_title_alt = get_lang('Flash');
    } elseif ($path == '/images') {
        $tooltip_title_alt = get_lang('Images');
    } elseif ($path == '/images/gallery') {
        $tooltip_title_alt = get_lang('DefaultCourseImages');
    }

    $current_session_id = api_get_session_id();
    $copy_to_myfiles = $open_in_new_window_link = null;

    $curdirpath = isset($_GET['curdirpath']) ? Security::remove_XSS($_GET['curdirpath']) : null;

    if (!$show_as_icon) {
        if ($filetype == 'folder') {
            if (api_is_allowed_to_edit() || api_is_platform_admin() || api_get_setting('students_download_folders') == 'true') {
                //filter when I am into shared folder, I can show for donwload only my shared folder
                if (is_shared_folder($curdirpath, $current_session_id)) {
                    if (preg_match('/shared_folder\/sf_user_' . api_get_user_id() . '$/', urldecode($forcedownload_link)) || preg_match('/shared_folder_session_' . $current_session_id . '\/sf_user_' . api_get_user_id() . '$/', urldecode($forcedownload_link)) || api_is_allowed_to_edit() || api_is_platform_admin()) {
                        $force_download_html = ($size == 0) ? '' : '<a href="' . $forcedownload_link . '" style="float:right"' . $prevent_multiple_click . '>' . Display::return_icon($forcedownload_icon, get_lang('Download'), array(), ICON_SIZE_SMALL) . '</a>';
                    }
                } elseif (!preg_match('/shared_folder/', urldecode($forcedownload_link)) || api_is_allowed_to_edit() || api_is_platform_admin()) {
                    $force_download_html = ($size == 0) ? '' : '<a href="' . $forcedownload_link . '" style="float:right"' . $prevent_multiple_click . '>' . Display::return_icon($forcedownload_icon, get_lang('Download'), array(), ICON_SIZE_SMALL) . '</a>';
                }
            }
        } else {
            $force_download_html = ($size == 0) ? '' : '<a href="' . $forcedownload_link . '" style="float:right"' . $prevent_multiple_click . '>' . Display::return_icon($forcedownload_icon, get_lang('Download'), array(), ICON_SIZE_SMALL) . '</a>';
        }

        //Copy files to users myfiles        
        if (api_get_setting('allow_social_tool') == 'true' && api_get_setting('users_copy_files') == 'true' && !api_is_anonymous()) {
            $copy_myfiles_link = ($filetype == 'file') ? api_get_self() . '?' . api_get_cidreq() . '&action=copytomyfiles&id=' . $document_data['id'] . $req_gid : api_get_self() . '?' . api_get_cidreq();

            if ($filetype == 'file') {
                $copy_to_myfiles = '<a href="' . $copy_myfiles_link . '" style="float:right"' . $prevent_multiple_click . '>' . Display::return_icon('briefcase.png', get_lang('CopyToMyFiles'), array(), ICON_SIZE_SMALL) . '&nbsp;&nbsp;</a>';
            }
            $send_to = '';
            if ($filetype == 'file') {
                $send_to = Portfolio::share('document', $document_data['id'], array('style' => 'float:right;'));
            }
        }

        $pdf_icon = '';
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        if (!api_is_allowed_to_edit() && api_get_setting('students_export2pdf') == 'true' && $filetype == 'file' && in_array($extension, array('html', 'htm'))) {
            $pdf_icon = ' <a style="float:right".' . $prevent_multiple_click . ' href="' . api_get_self() . '?' . api_get_cidreq() . '&action=export_to_pdf&id=' . $document_data['id'] . '">' . Display::return_icon('pdf.png', get_lang('Export2PDF'), array(), ICON_SIZE_SMALL) . '</a> ';
        }

        if ($is_browser_viewable_file) {
            $open_in_new_window_link = '<a href="' . $www . str_replace('%2F', '/', $url_path) . '?' . api_get_cidreq() . '" style="float:right"' . $prevent_multiple_click . ' target="_blank">' . Display::return_icon('open_in_new_window.png', get_lang('OpenInANewWindow'), array(), ICON_SIZE_SMALL) . '&nbsp;&nbsp;</a>';
        }
        //target="'.$target.'"
        if ($filetype == 'file') {
            //Sound preview with jplayer
            if (preg_match('/mp3$/i', urldecode($url)) ||
                    (preg_match('/wav$/i', urldecode($url)) && !preg_match('/_chnano_.wav$/i', urldecode($url))) ||
                    preg_match('/ogg$/i', urldecode($url))) {
                return '<span style="float:left" ' . $visibility_class . '>' . $title . '</span>' . $force_download_html . $send_to . $copy_to_myfiles . $open_in_new_window_link . $pdf_icon;
            } elseif (
                    //Show preview
                    //preg_match('/html$/i', urldecode($url))  || 
                    //preg_match('/htm$/i',  urldecode($url))  ||
                    preg_match('/swf$/i', urldecode($url)) ||
                    preg_match('/png$/i', urldecode($url)) ||
                    preg_match('/gif$/i', urldecode($url)) ||
                    preg_match('/jpg$/i', urldecode($url)) ||
                    preg_match('/jpeg$/i', urldecode($url)) ||
                    preg_match('/bmp$/i', urldecode($url)) ||
                    preg_match('/svg$/i', urldecode($url)) ||
                    (preg_match('/wav$/i', urldecode($url)) && preg_match('/_chnano_.wav$/i', urldecode($url)) && api_get_setting('enable_nanogong') == 'true')
            ) {
                //yox view
                //$url = 'showinframesmin.php?'.api_get_cidreq().'&id='.$document_data['id'].$req_gid;
                //Simpler version of showinframesmin.php with no headers 
                $url = 'show_content.php?' . api_get_cidreq() . '&id=' . $document_data['id'] . $req_gid . '&width=700&height=500';
                $class = 'ajax';
                if ($visibility == false) {
                    $class = "ajax invisible";
                }
                return '<a href="' . $url . '" class="' . $class . '" title="' . $tooltip_title_alt . '" style="float:left">' . $title . '</a>' . $force_download_html . $send_to . $copy_to_myfiles . $open_in_new_window_link . $pdf_icon;
            } else {
                $url = 'showinframes.php?' . api_get_cidreq() . '&id=' . $document_data['id'] . $req_gid;
                //No plugin just the old and good showinframes.php page 			
                return '<a href="' . $url . '" title="' . $tooltip_title_alt . '" style="float:left" ' . $visibility_class . ' >' . $title . '</a>' . $force_download_html . $send_to . $copy_to_myfiles . $open_in_new_window_link . $pdf_icon;
            }
        } else {
            return '<a href="' . $url . '" title="' . $tooltip_title_alt . '" ' . $visibility_class . ' style="float:left">' . $title . '</a>' . $force_download_html . $send_to . $copy_to_myfiles . $open_in_new_window_link . $pdf_icon;
        }
        //end copy files to users myfiles
    } else {
        //Icon column
        if (preg_match('/shared_folder/', urldecode($url)) && preg_match('/shared_folder$/', urldecode($url)) == false && preg_match('/shared_folder_session_' . $current_session_id . '$/', urldecode($url)) == false) {
            if ($filetype == 'file') {
                //Sound preview with jplayer
                if (preg_match('/mp3$/i', urldecode($url)) ||
                        (preg_match('/wav$/i', urldecode($url)) && !preg_match('/_chnano_.wav$/i', urldecode($url))) ||
                        preg_match('/ogg$/i', urldecode($url))) {
                    $sound_preview = DocumentManager::generate_media_preview($counter);
                    return $sound_preview;
                } elseif (
                    //Show preview
                    //preg_match('/html$/i', urldecode($url))  || 
                    //preg_match('/htm$/i',  urldecode($url))  ||
                    preg_match('/swf$/i', urldecode($url)) ||
                    preg_match('/png$/i', urldecode($url)) ||
                    preg_match('/gif$/i', urldecode($url)) ||
                    preg_match('/jpg$/i', urldecode($url)) ||
                    preg_match('/jpeg$/i', urldecode($url)) ||
                    preg_match('/bmp$/i', urldecode($url)) ||
                    preg_match('/svg$/i', urldecode($url)) ||
                    (preg_match('/wav$/i', urldecode($url)) && preg_match('/_chnano_.wav$/i', urldecode($url)) && api_get_setting('enable_nanogong') == 'true')
                ) {
                    $url = 'showinframes.php?' . api_get_cidreq() . '&id=' . $document_data['id'] . $req_gid;
                    return '<a href="' . $url . '" title="' . $tooltip_title_alt . '" ' . $visibility_class . ' style="float:left">' . build_document_icon_tag($filetype, $path) . Display::return_icon('shared.png', get_lang('ResourceShared'), array()) . '</a>';
                } else {
                    return '<a href="' . $url . '" title="' . $tooltip_title_alt . '" ' . $visibility_class . ' style="float:left">' . build_document_icon_tag($filetype, $path) . Display::return_icon('shared.png', get_lang('ResourceShared'), array()) . '</a>';
                }
            } else {
                return '<a href="' . $url . '" title="' . $tooltip_title_alt . '" target="' . $target . '"' . $visibility_class . ' style="float:left">' . build_document_icon_tag($filetype, $path) . Display::return_icon('shared.png', get_lang('ResourceShared'), array()) . '</a>';
            }
        } else {
            if ($filetype == 'file') {
                //Sound preview with jplayer
                if (preg_match('/mp3$/i', urldecode($url)) ||
                        (preg_match('/wav$/i', urldecode($url)) && !preg_match('/_chnano_.wav$/i', urldecode($url))) ||
                        preg_match('/ogg$/i', urldecode($url))) {
                    $sound_preview = DocumentManager::generate_media_preview($counter);
                    return $sound_preview;
                } elseif (
                        //Show preview
                        preg_match('/html$/i', urldecode($url)) ||
                        preg_match('/htm$/i', urldecode($url)) ||
                        preg_match('/swf$/i', urldecode($url)) ||
                        preg_match('/png$/i', urldecode($url)) ||
                        preg_match('/gif$/i', urldecode($url)) ||
                        preg_match('/jpg$/i', urldecode($url)) ||
                        preg_match('/jpeg$/i', urldecode($url)) ||
                        preg_match('/bmp$/i', urldecode($url)) ||
                        preg_match('/svg$/i', urldecode($url)) ||
                        (preg_match('/wav$/i', urldecode($url)) && preg_match('/_chnano_.wav$/i', urldecode($url)) && api_get_setting('enable_nanogong') == 'true')
                ) {
                    $url = 'showinframes.php?' . api_get_cidreq() . '&id=' . $document_data['id'] . $req_gid; //without preview
                    return '<a href="' . $url . '" title="' . $tooltip_title_alt . '" ' . $visibility_class . ' style="float:left">' . build_document_icon_tag($filetype, $path) . '</a>';
                } else {
                    return '<a href="' . $url . '" title="' . $tooltip_title_alt . '" ' . $visibility_class . ' style="float:left">' . build_document_icon_tag($filetype, $path) . '</a>';
                }
            } else {
                return '<a href="' . $url . '" title="' . $tooltip_title_alt . '" target="' . $target . '"' . $visibility_class . ' style="float:left">' . build_document_icon_tag($filetype, $path) . '</a>';
            }
        }
    }
}

/**
 * Builds an img html tag for the filetype
 *
 * @param string $type (file/folder)
 * @param string $path
 * @return string img html tag
 */
function build_document_icon_tag($type, $path) {
    $basename = basename($path);
    $current_session_id = api_get_session_id();
    $is_allowed_to_edit = api_is_allowed_to_edit(null, true);
    if ($type == 'file') {
        $icon = choose_image($basename);

        if (preg_match('/_chnano_.wav$/i', $basename)) {
            $icon = "jplayer_play.png";
            $basename = 'wav' . ' ' . '(Nanogong)';
        } else {
            $basename = substr(strrchr($basename, '.'), 1);
        }
    } else {
        if ($path == '/shared_folder') {
            $icon = 'folder_users.gif';
            if ($is_allowed_to_edit) {
                $basename = get_lang('HelpUsersFolder');
            } else {
                $basename = get_lang('UserFolders');
            }
        } elseif (strstr($basename, 'sf_user_')) {
            $userinfo = Database::get_user_info_from_id(substr($basename, 8));
            $image_path = UserManager::get_user_picture_path_by_id(substr($basename, 8), 'web', false, true);

            if ($image_path['file'] == 'unknown.jpg') {
                $icon = $image_path['file'];
            } else {
                $icon = '../upload/users/' . substr($basename, 8) . '/' . $image_path['file'];
            }

            $basename = get_lang('UserFolder') . ' ' . api_get_person_name($userinfo['firstname'], $userinfo['lastname']);
        } elseif (strstr($path, 'shared_folder_session_')) {
            if ($is_allowed_to_edit) {
                $basename = '***(' . api_get_session_name($current_session_id) . ')*** ' . get_lang('HelpUsersFolder');
            } else {
                $basename = get_lang('UserFolders') . ' (' . api_get_session_name($current_session_id) . ')';
            }
            $icon = 'folder_users.gif';
        } else {
            $icon = 'folder_document.gif';

            if ($path == '/audio') {
                $icon = 'folder_audio.gif';
                if (api_is_allowed_to_edit()) {
                    $basename = get_lang('HelpDefaultDirDocuments');
                } else {
                    $basename = get_lang('Audio');
                }
            } elseif ($path == '/flash') {
                $icon = 'folder_flash.gif';
                if (api_is_allowed_to_edit()) {
                    $basename = get_lang('HelpDefaultDirDocuments');
                } else {
                    $basename = get_lang('Flash');
                }
            } elseif ($path == '/images') {
                $icon = 'folder_images.gif';
                if (api_is_allowed_to_edit()) {
                    $basename = get_lang('HelpDefaultDirDocuments');
                } else {
                    $basename = get_lang('Images');
                }
            } elseif ($path == '/video') {
                $icon = 'folder_video.gif';
                if (api_is_allowed_to_edit()) {
                    $basename = get_lang('HelpDefaultDirDocuments');
                } else {
                    $basename = get_lang('Video');
                }
            } elseif ($path == '/images/gallery') {
                $icon = 'folder_gallery.gif';
                if (api_is_allowed_to_edit()) {
                    $basename = get_lang('HelpDefaultDirDocuments');
                } else {
                    $basename = get_lang('Gallery');
                }
            } elseif ($path == '/chat_files') {
                $icon = 'folder_chat.gif';
                if (api_is_allowed_to_edit()) {
                    $basename = get_lang('HelpFolderChat');
                } else {
                    $basename = get_lang('ChatFiles');
                }
            } elseif ($path == '/learning_path') {
                $icon = 'folder_learningpath.gif';
                if (api_is_allowed_to_edit()) {
                    $basename = get_lang('HelpFolderLearningPaths');
                } else {
                    $basename = get_lang('LearningPaths');
                }
            }
        }
    }

    return Display::return_icon($icon, $basename, array());
}

/**
 * Creates the row of edit icons for a file/folder
 *
 * @param string $curdirpath current path (cfr open folder)
 * @param string $type (file/folder)
 * @param string $path dbase path of file/folder
 * @param int $visibility (1/0)
 * @param int $id dbase id of the document
 * @return string html img tags with hyperlinks
 */
function build_edit_icons($document_data, $id, $is_template, $is_read_only = 0, $visibility) {
    if (isset($_SESSION['_gid'])) {
        $req_gid = '&gidReq=' . $_SESSION['_gid'];
    } else {
        $req_gid = '';
    }
    $document_id = $document_data['id'];

    $type = $document_data['filetype'];

    $is_read_only = $document_data['readonly'];
    $path = $document_data['path'];
    $parent_id = DocumentManager::get_document_id(api_get_course_info(), dirname($path));
    $curdirpath = dirname($document_data['path']);
    $is_certificate_mode = DocumentManager::is_certificate_mode($path);
    $curdirpath = urlencode($curdirpath);
    $extension = pathinfo($path, PATHINFO_EXTENSION);

    // Build URL-parameters for table-sorting
    $sort_params = array();
    if (isset($_GET['column'])) {
        $sort_params[] = 'column=' . Security::remove_XSS($_GET['column']);
    }
    if (isset($_GET['page_nr'])) {
        $sort_params[] = 'page_nr=' . Security::remove_XSS($_GET['page_nr']);
    }
    if (isset($_GET['per_page'])) {
        $sort_params[] = 'per_page=' . Security::remove_XSS($_GET['per_page']);
    }
    if (isset($_GET['direction'])) {
        $sort_params[] = 'direction=' . Security::remove_XSS($_GET['direction']);
    }
    $sort_params = implode('&amp;', $sort_params);
    $visibility_icon = ($visibility == 0) ? 'invisible' : 'visible';
    $visibility_command = ($visibility == 0) ? 'set_visible' : 'set_invisible';

    $modify_icons = '';

    // If document is read only *or* we're in a session and the document
    // is from a non-session context, hide the edition capabilities
    if ($is_read_only /* or ($session_id!=api_get_session_id()) */) {
        if (api_is_course_admin() || api_is_platform_admin()) {
            if ($extension == 'svg' && api_browser_support('svg') && api_get_setting('enabled_support_svg') == 'true') {
                $modify_icons = '<a href="edit_draw.php?' . api_get_cidreq() . '&id=' . $document_id . $req_gid . '">' . Display::return_icon('edit.png', get_lang('Modify'), '', ICON_SIZE_SMALL) . '</a>';
            } elseif ($extension == 'png' || $extension == 'jpg' || $extension == 'jpeg' || $extension == 'bmp' || $extension == 'gif' || $extension == 'pxd' && api_get_setting('enabled_support_pixlr') == 'true') {
                $modify_icons = '<a href="edit_paint.php?' . api_get_cidreq() . '&id=' . $document_id . $req_gid . '">' . Display::return_icon('edit.png', get_lang('Modify'), '', ICON_SIZE_SMALL) . '</a>';
            } else {
                $modify_icons = '<a href="edit_document.php?' . api_get_cidreq() . '&id=' . $document_id . $req_gid . '">' . Display::return_icon('edit.png', get_lang('Modify'), '', ICON_SIZE_SMALL) . '</a>';
            }
        } else {
            $modify_icons = Display::return_icon('edit_na.png', get_lang('Modify'), '', ICON_SIZE_SMALL);
        }
        $modify_icons .= '&nbsp;' . Display::return_icon('move_na.png', get_lang('Move'), array(), ICON_SIZE_SMALL);
        if (api_is_allowed_to_edit() || api_is_platform_admin()) {
            $modify_icons .= '&nbsp;' . Display::return_icon($visibility_icon . '.png', get_lang('VisibilityCannotBeChanged'), '', ICON_SIZE_SMALL);
        }
        $modify_icons .= '&nbsp;' . Display::return_icon('delete_na.png', get_lang('Delete'), array(), ICON_SIZE_SMALL);
    } else {
        //Edit button
        if (in_array($path, DocumentManager::get_system_folders())) {
            $modify_icons = Display::return_icon('edit_na.png', get_lang('Modify'), '', ICON_SIZE_SMALL);
        } elseif ($is_certificate_mode ) {
            // gradebook category doesn't seem to be taken into account
            $modify_icons = '<a href="edit_document.php?' . api_get_cidreq() . '&amp;id=' . $document_id . $req_gid . '&curdirpath=/certificates">' . Display::return_icon('edit.png', get_lang('Modify'), '', ICON_SIZE_SMALL) . '</a>';
        } else {
            if (api_get_session_id()) {
                if ($document_data['session_id'] == api_get_session_id()) {
                    if ($extension == 'svg' && api_browser_support('svg') && api_get_setting('enabled_support_svg') == 'true') {
                        $modify_icons = '<a href="edit_draw.php?' . api_get_cidreq() . '&amp;id=' . $document_id . $req_gid . '">' . Display::return_icon('edit.png', get_lang('Modify'), '', ICON_SIZE_SMALL) . '</a>';
                    } elseif ($extension == 'png' || $extension == 'jpg' || $extension == 'jpeg' || $extension == 'bmp' || $extension == 'gif' || $extension == 'pxd' && api_get_setting('enabled_support_pixlr') == 'true') {
                        $modify_icons = '<a href="edit_paint.php?' . api_get_cidreq() . '&amp;id=' . $document_id . $req_gid . '">' . Display::return_icon('edit.png', get_lang('Modify'), '', ICON_SIZE_SMALL) . '</a>';
                    } else {
                        $modify_icons = '<a href="edit_document.php?' . api_get_cidreq() . '&amp;id=' . $document_id . $req_gid . '">' . Display::return_icon('edit.png', get_lang('Modify'), '', ICON_SIZE_SMALL) . '</a>';
                    }
                } else {
                    $modify_icons .= '&nbsp;' . Display::return_icon('edit_na.png', get_lang('Edit'), array(), ICON_SIZE_SMALL) . '</a>';
                }
            } else {
                if ($extension == 'svg' && api_browser_support('svg') && api_get_setting('enabled_support_svg') == 'true') {
                    $modify_icons = '<a href="edit_draw.php?' . api_get_cidreq() . '&amp;id=' . $document_id . $req_gid . '">' . Display::return_icon('edit.png', get_lang('Modify'), '', ICON_SIZE_SMALL) . '</a>';
                } elseif ($extension == 'png' || $extension == 'jpg' || $extension == 'jpeg' || $extension == 'bmp' || $extension == 'gif' || $extension == 'pxd' && api_get_setting('enabled_support_pixlr') == 'true') {
                    $modify_icons = '<a href="edit_paint.php?' . api_get_cidreq() . '&amp;id=' . $document_id . $req_gid . '">' . Display::return_icon('edit.png', get_lang('Modify'), '', ICON_SIZE_SMALL) . '</a>';
                } else {
                    $modify_icons = '<a href="edit_document.php?' . api_get_cidreq() . '&amp;id=' . $document_id . $req_gid . '">' . Display::return_icon('edit.png', get_lang('Modify'), '', ICON_SIZE_SMALL) . '</a>';
                }
            }
        }
        
        //Move button        
        if ($is_certificate_mode || in_array($path, DocumentManager::get_system_folders())) {
            $modify_icons .= '&nbsp;' . Display::return_icon('move_na.png', get_lang('Move'), array(), ICON_SIZE_SMALL) . '</a>';            
        } else {
            if (api_get_session_id()) {
                if ($document_data['session_id'] == api_get_session_id()) {
                    $modify_icons .= '&nbsp;<a href="' . api_get_self() . '?' . api_get_cidreq() . '&amp;id=' . $parent_id . '&amp;move=' . $document_id . $req_gid . '">' . Display::return_icon('move.png', get_lang('Move'), array(), ICON_SIZE_SMALL) . '</a>';
                } else {
                    $modify_icons .= '&nbsp;' . Display::return_icon('move_na.png', get_lang('Move'), array(), ICON_SIZE_SMALL) . '</a>';
                }
            } else {
                $modify_icons .= '&nbsp;<a href="' . api_get_self() . '?' . api_get_cidreq() . '&amp;id=' . $parent_id . '&amp;move=' . $document_id . $req_gid . '">' . Display::return_icon('move.png', get_lang('Move'), array(), ICON_SIZE_SMALL) . '</a>';
            }
        }
        
        //Visibility button
        if ($is_certificate_mode) {
            $modify_icons .= '&nbsp;' . Display::return_icon($visibility_icon . '.png', get_lang('VisibilityCannotBeChanged'), array(), ICON_SIZE_SMALL) . '</a>';
        } else {
            if (api_is_allowed_to_edit() || api_is_platform_admin()) {
                if ($visibility_icon == 'invisible') {
                    $tip_visibility = get_lang('Show');
                } else {
                    $tip_visibility = get_lang('Hide');
                }
                $modify_icons .= '&nbsp;<a href="' . api_get_self() . '?' . api_get_cidreq() . '&amp;id=' . $parent_id . '&amp;' . $visibility_command . '=' . $id . $req_gid . '&amp;' . $sort_params . '">' . Display::return_icon($visibility_icon . '.png', $tip_visibility, '', ICON_SIZE_SMALL) . '</a>';
            }
        }
        
        //Delete button    
        if (in_array($path, DocumentManager::get_system_folders())) {
            $modify_icons .= '&nbsp;' . Display::return_icon('delete_na.png', get_lang('ThisFolderCannotBeDeleted'), array(), ICON_SIZE_SMALL);
        } else {
            if (isset($_GET['curdirpath']) && $_GET['curdirpath'] == '/certificates' && DocumentManager::get_default_certificate_id(api_get_course_id()) == $id) {
                $modify_icons .= '&nbsp;<a href="' . api_get_self() . '?' . api_get_cidreq() . '&amp;curdirpath=' . $curdirpath . '&amp;delete=' . urlencode($path) . $req_gid . '&amp;' . $sort_params . 'delete_certificate_id=' . $id . '" onclick="return confirmation(\'' . basename($path) . '\');">' . Display::return_icon('delete.png', get_lang('Delete'), array(), ICON_SIZE_SMALL) . '</a>';
            } else {
                if ($is_certificate_mode) {
                    $modify_icons .= '&nbsp;<a href="' . api_get_self() . '?' . api_get_cidreq() . '&amp;curdirpath=' . $curdirpath . '&amp;delete=' . urlencode($path) . $req_gid . '&amp;' . $sort_params . '" onclick="return confirmation(\'' . basename($path) . '\');">' . Display::return_icon('delete.png', get_lang('Delete'), array(), ICON_SIZE_SMALL) . '</a>';
                } else {
                    if (api_get_session_id()) {
                        if ($document_data['session_id'] == api_get_session_id()) {
                            $modify_icons .= '&nbsp;<a href="' . api_get_self() . '?' . api_get_cidreq() . '&amp;curdirpath=' . $curdirpath . '&amp;delete=' . urlencode($path) . $req_gid . '&amp;' . $sort_params . '" onclick="return confirmation(\'' . basename($path) . '\');">' . Display::return_icon('delete.png', get_lang('Delete'), array(), ICON_SIZE_SMALL) . '</a>';
                        } else {
                            $modify_icons .= '&nbsp;' . Display::return_icon('delete_na.png', get_lang('ThisFolderCannotBeDeleted'), array(), ICON_SIZE_SMALL);
                        }
                    } else {
                        $modify_icons .= '&nbsp;<a href="' . api_get_self() . '?' . api_get_cidreq() . '&amp;curdirpath=' . $curdirpath . '&amp;delete=' . urlencode($path) . $req_gid . '&amp;' . $sort_params . '" onclick="return confirmation(\'' . basename($path) . '\');">' . Display::return_icon('delete.png', get_lang('Delete'), array(), ICON_SIZE_SMALL) . '</a>';
                    }
                }
            }
        }
    }

    if ($type == 'file' && ($extension == 'html' || $extension == 'htm')) {
        if ($is_template == 0) {
            if ((isset($_GET['curdirpath']) && $_GET['curdirpath'] != '/certificates') || !isset($_GET['curdirpath'])) {
                $modify_icons .= '&nbsp;<a href="' . api_get_self() . '?' . api_get_cidreq() . '&amp;curdirpath=' . $curdirpath . '&amp;add_as_template=' . $id . $req_gid . '&amp;' . $sort_params . '">' . Display::return_icon('wizard.png', get_lang('AddAsTemplate'), array(), ICON_SIZE_SMALL) . '</a>';
            }
            if (isset($_GET['curdirpath']) && $_GET['curdirpath'] == '/certificates') {//allow attach certificate to course
                $visibility_icon_certificate = 'nocertificate';
                if (DocumentManager::get_default_certificate_id(api_get_course_id()) == $id) {
                    $visibility_icon_certificate = 'certificate';
                    $certificate = get_lang('DefaultCertificate');
                    $preview = get_lang('PreviewCertificate');
                    $is_preview = true;
                } else {
                    $is_preview = false;
                    $certificate = get_lang('NoDefaultCertificate');
                }
                if (isset($_GET['selectcat'])) {
                    $modify_icons .= '&nbsp;<a href="' . api_get_self() . '?' . api_get_cidreq() . '&amp;curdirpath=' . $curdirpath . '&amp;selectcat=' . Security::remove_XSS($_GET['selectcat']) . '&amp;set_certificate=' . $id . $req_gid . '&amp;' . $sort_params . '"><img src="../img/' . $visibility_icon_certificate . '.png" border="0" title="' . $certificate . '" alt="" /></a>';
                    if ($is_preview) {
                        $modify_icons .= '&nbsp;<a target="_blank"  href="' . api_get_self() . '?' . api_get_cidreq() . '&amp;curdirpath=' . $curdirpath . '&amp;set_preview=' . $id . $req_gid . '&amp;' . $sort_params . '" >' .
                                Display::return_icon('preview_view.png', $preview, '', ICON_SIZE_SMALL) . '</a>';
                    }
                }
            }
        } else {
            $modify_icons .= '&nbsp;<a href="' . api_get_self() . '?' . api_get_cidreq() . '&curdirpath=' . $curdirpath . '&amp;remove_as_template=' . $id . $req_gid . '&amp;' . $sort_params . '">' .
                    Display::return_icon('wizard_na.png', get_lang('RemoveAsTemplate'), '', ICON_SIZE_SMALL) . '</a>';
        }
        $modify_icons .= '&nbsp;<a href="' . api_get_self() . '?' . api_get_cidreq() . '&action=export_to_pdf&id=' . $id . '">' . Display::return_icon('pdf.png', get_lang('Export2PDF'), array(), ICON_SIZE_SMALL) . '</a>';
    }
    return $modify_icons;
}

function build_move_to_selector($folders, $curdirpath, $move_file, $group_dir = '') {

    $form = new FormValidator('move_to', 'post', api_get_self());

    // Form title
    $form->addElement('hidden', 'move_file', $move_file);

    $options = array();

    // Group documents cannot be uploaded in the root
    if ($group_dir == '') {
        if ($curdirpath != '/') {
            $options['/'] = get_lang('Documents');
        }

        if (is_array($folders)) {
            foreach ($folders as & $folder) {
                //Hide some folders
                if ($folder == '/HotPotatoes_files' || $folder == '/certificates' || basename($folder) == 'css') {
                    continue;
                }
                //Admin setting for Hide/Show the folders of all users
                if (api_get_setting('show_users_folders') == 'false' && (strstr($folder, '/shared_folder') || strstr($folder, 'shared_folder_session_'))) {
                    continue;
                }
                //Admin setting for Hide/Show Default folders to all users
                if (api_get_setting('show_default_folders') == 'false' && ($folder == '/images' || $folder == '/flash' || $folder == '/audio' || $folder == '/video' || strstr($folder, '/images/gallery') || $folder == '/video/flv')) {
                    continue;
                }
                //Admin setting for Hide/Show chat history folder
                if (api_get_setting('show_chat_folder') == 'false' && $folder == '/chat_files') {
                    continue;
                }

                // You cannot move a file to:
                // 1. current directory
                // 2. inside the folder you want to move
                // 3. inside a subfolder of the folder you want to move
                if (($curdirpath != $folder) && ($folder != $move_file) && (substr($folder, 0, strlen($move_file) + 1) != $move_file . '/')) {
                    $path_displayed = $folder;
                    // If document title is used, we have to display titles instead of real paths...                    
                    $path_displayed = get_titles_of_path($folder);

                    if (empty($path_displayed)) {
                        $path_displayed = get_lang('Untitled');
                    }
                    $options[$folder] = $path_displayed;
                    //$form .= '<option value="'.$folder.'">'.$path_displayed.'</option>';
                }
            }
        }
    } else {
        foreach ($folders as $folder) {
            if (($curdirpath != $folder) && ($folder != $move_file) && (substr($folder, 0, strlen($move_file) + 1) != $move_file . '/')) { // Cannot copy dir into his own subdir                
                $path_displayed = get_titles_of_path($folder);
                $display_folder = substr($path_displayed, strlen($group_dir));
                $display_folder = ($display_folder == '') ? get_lang('Documents') : $display_folder;
                //$form .= '<option value="'.$folder.'">'.$display_folder.'</option>';
                $options[$folder] = $display_folder;
            }
        }
    }
    $form->addElement('select', 'move_to', get_lang('MoveTo'), $options);
    $form->addElement('button', 'move_file_submit', get_lang('MoveElement'));
    return $form->return_form();
}

/**
 * Gets the path translated with title of docs and folders
 * @param string the real path
 * @return the path which should be displayed
 */
function get_titles_of_path($path) {
    global $tmp_folders_titles;
    $course_id = api_get_course_int_id();

    $nb_slashes = substr_count($path, '/');
    $tmp_path = '';
    $current_slash_pos = 0;
    $path_displayed = '';
    for ($i = 0; $i < $nb_slashes; $i++) {
        // For each folder of the path, retrieve title.
        $current_slash_pos = strpos($path, '/', $current_slash_pos + 1);
        $tmp_path = substr($path, strpos($path, '/', 0), $current_slash_pos);

        if (empty($tmp_path)) {
            // If empty, then we are in the final part of the path
            $tmp_path = $path;
        }

        if (!empty($tmp_folders_titles[$tmp_path])) {
            // If this path has soon been stored here we don't need a new query
            $path_displayed .= $tmp_folders_titles[$tmp_path];
        } else {
            $sql = 'SELECT title FROM ' . Database::get_course_table(TABLE_DOCUMENT) . ' 
                    WHERE c_id = ' . $course_id . ' AND path LIKE BINARY "' . $tmp_path . '"';
            $rs = Database::query($sql);
            $tmp_title = '/' . Database::result($rs, 0, 0);
            $path_displayed .= $tmp_title;
            $tmp_folders_titles[$tmp_path] = $tmp_title;
        }
    }
    return $path_displayed;
}

/**
 * This function displays the name of the user and makes the link tothe user tool.
 *
 * @param $user_id
 * @param $name
 * @return a link to the userInfo.php
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function display_user_link_document($user_id, $name) {
    if ($user_id != 0) {
        return '<a href="../user/userInfo.php?uInfo=' . $user_id . '">' . $name . '</a>';
    } else {
        return get_lang('Anonymous');
    }
}

/**
 * Creates form that asks for the directory name.
 * @return string	html-output text for the form
 */
function create_dir_form($current_dir_id) {
    global $document_id;

    $form = new FormValidator('create_dir_form', 'post', '', '', null, false);
    $form->addElement('hidden', 'create_dir', 1);
    $form->addElement('hidden', 'dir_id', intval($document_id));
    $form->addElement('hidden', 'id', intval($current_dir_id));
    $form->addElement('header', '', get_lang('CreateDir'));
    $form->addElement('text', 'dirname', get_lang('NewDir'));
    $form->addElement('style_submit_button', 'submit', get_lang('CreateFolder'), 'class="add"');
    $new_folder_text = $form->return_form();
    return $new_folder_text;
}

/**
 * Checks whether the user is in shared folder
 * @return return bool Return true when user is into shared folder
 */
function is_shared_folder($curdirpath, $current_session_id) {
    $clean_curdirpath = Security::remove_XSS($curdirpath);
    if ($clean_curdirpath == '/shared_folder') {
        return true;
    } elseif ($clean_curdirpath == '/shared_folder_session_' . $current_session_id) {
        return true;
    } else {
        return false;
    }
}

/**
 * Checks whether the user is into any user shared folder
 * @return return bool Return true when user is in any user shared folder
 */
function is_any_user_shared_folder($path, $current_session_id) {
    $clean_path = Security::remove_XSS($path);
    if (strpos($clean_path, 'shared_folder/sf_user_')) {
        return true;
    } elseif (strpos($clean_path, 'shared_folder_session_' . $current_session_id . '/sf_user_')) {
        return true;
    } else {
        return false;
    }
}

/**
 * Checks whether the user is into his shared folder or into a subfolder
 * @return return bool Return true when user is in his user shared folder or into a subforder
 */
function is_my_shared_folder($user_id, $path, $current_session_id) {
    $clean_path = Security::remove_XSS($path) . '/';
    $main_user_shared_folder = '/shared_folder\/sf_user_' . $user_id . '\//'; //for security does not remove the last slash
    $main_user_shared_folder_session = '/shared_folder_session_' . $current_session_id . '\/sf_user_' . $user_id . '\//'; //for security does not remove the last slash

    if (preg_match($main_user_shared_folder, $clean_path)) {
        return true;
    } elseif (preg_match($main_user_shared_folder_session, $clean_path)) {
        return true;
    } else {
        return false;
    }
}

/**
 * Check if the file name or folder searched exist
 * @return return bool Return true when exist
 */
function search_keyword($document_name, $keyword) {
    if (api_strripos($document_name, $keyword) !== false) {
        return true;
    } else {
        return false;
    }
}

/**
 * Checks whether a document can be previewed by using the browser.
 * @param string $file_extension    The filename extension of the document (it must be in lower case).
 * @return bool                     Returns TRUE or FALSE.
 */
function is_browser_viewable($file_extension) {
    static $allowed_extensions = array(
    'htm', 'html', 'xhtml',
     'gif', 'jpg', 'jpeg', 'png', 'tif', 'tiff',
     'pdf', 'svg', 'swf',
     'txt', 'log',
     'mp4', 'ogg', 'ogv', 'ogx', 'mpg', 'mpeg', 'mov', 'avi', 'webm', 'wmv',
     'mp3', 'oga', 'wav', 'au', 'wma', 'mid', 'kar'
    );

    /*
      //TODO: make a admin swich to strict mode
      1. global default $allowed_extensions only: 'htm', 'html', 'xhtml', 'gif', 'jpg', 'jpeg', 'png', 'bmp', 'txt', 'log'
      if (in_array($file_extension, $allowed_extensions)) { // Assignment + a logical check.
      return true;
      }
      2. check native support
      3. check plugins: quicktime, mediaplayer, vlc, acrobat, flash, java
     */

    if (!($result = in_array($file_extension, $allowed_extensions))) { // Assignment + a logical check.
        return false;
    }
    //check native support (Explorer, Opera, Firefox, Chrome, Safari)

    if ($file_extension == "pdf") {
        return api_browser_support('pdf');
    } elseif ($file_extension == "mp3") {
        return api_browser_support('mp3');
    } elseif ($file_extension == "mp4") {
        return api_browser_support('mp4');
    } elseif ($file_extension == "ogg" || $file_extension == "ogx" || $file_extension == "ogv" || $file_extension == "oga") {
        return api_browser_support('ogg');
    } elseif ($file_extension == "svg") {
        return api_browser_support('svg');
    } elseif ($file_extension == "mpg" || $file_extension == "mpeg") {
        return api_browser_support('mpg');
    } elseif ($file_extension == "mov") {
        return api_browser_support('mov');
    } elseif ($file_extension == "wav") {
        return api_browser_support('wav');
    } elseif ($file_extension == "mid" || $file_extension == "kar") {
        return api_browser_support('mid');
    } elseif ($file_extension == "avi") {
        return api_browser_support('avi');
    } elseif ($file_extension == "wma") {
        return api_browser_support('wma');
    } elseif ($file_extension == "wmv") {
        return api_browser_support('wmv');
    } elseif ($file_extension == "tif" || $file_extension == "tiff") {
        return api_browser_support('tif');
    } elseif ($file_extension == "mov") {
        return api_browser_support('mov');
    } elseif ($file_extension == "au") {
        return api_browser_support('au');
    } elseif ($file_extension == "webm") {
        return api_browser_support('webm');
    }
    return $result;
}
