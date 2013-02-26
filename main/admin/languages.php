<?php

/* For licensing terms, see /license.txt */
/**
 * This page allows the platform admin to decide which languages should
 * be available in the language selection menu in the login page. This can be
 * useful for countries with more than one official language (like Belgium:
 * Dutch, French and German) or international organisations that are active in
 * a limited number of countries.
 *
 * @author Patrick Cool, main author
 * @author Roan EMbrechts, code cleaning
 * @since Dokeos 1.6
 * @package chamilo.admin
 */
/**
 * 	   INIT SECTION
 */
// name of the language file that needs to be included
$language_file = 'admin';

// we are in the admin area so we do not need a course id
$cidReset = true;

// include global script
require_once '../inc/global.inc.php';
require_once 'sub_language.class.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

//Ajax request
if (isset($_POST['sent_http_request'])) {
    if (isset($_POST['visibility']) && $_POST['visibility'] == strval(intval($_POST['visibility'])) && $_POST['visibility'] == 0) {
        if (isset($_POST['id']) && $_POST['id'] == strval(intval($_POST['id']))) {            
            if (SubLanguageManager::check_if_language_is_used($_POST['id']) == false) {
                SubLanguageManager::make_unavailable_language($_POST['id']);
                echo 'set_hidden';
            } else {
                echo 'confirm:' . intval($_POST['id']);
            }
        }
    }
    if (isset($_POST['visibility']) && $_POST['visibility'] == strval(intval($_POST['visibility'])) && $_POST['visibility'] == 1) {
        if (isset($_POST['id']) && $_POST['id'] == strval(intval($_POST['id']))) {
            SubLanguageManager::make_available_language($_POST['id']);
            echo 'set_visible';
        }
    }
    exit;
}


$htmlHeadXtra[] = '<script>
 $(document).ready(function() {

 	//$(window).load(function () {
      $(".make_visible_and_invisible").attr("href","javascript:void(0)");
	//});

 	$("td .make_visible_and_invisible").click(function () {
		make_visible="visible.png";
		make_invisible="invisible.png";
		id_link_tool=$(this).attr("id");
		id_img_link_tool="img"+id_link_tool;
		path_name_of_imglinktool=$("#"+id_img_link_tool).attr("src");
		link_info_id=id_link_tool.split("linktool_");
		link_id=link_info_id[1];

		link_tool_info=path_name_of_imglinktool.split("/");
		my_image_tool=link_tool_info[link_tool_info.length-1];


		if (my_image_tool=="visible.png") {
			path_name_of_imglinktool=path_name_of_imglinktool.replace(make_visible,make_invisible);
			my_visibility=0;
		} else {
			path_name_of_imglinktool=path_name_of_imglinktool.replace(make_invisible,make_visible);
			my_visibility=1;
		}

		$.ajax({
			contentType: "application/x-www-form-urlencoded",
			beforeSend: function(objeto) {
				$("#id_content_message").html("<div class=\"normal-message\"><img src=\"' . api_get_path(WEB_PATH) . 'main/img/loading1.gif\" /></div>");
			},
			type: "POST",
			url: "../admin/languages.php",
			data: "id="+link_id+"&visibility="+my_visibility+"&sent_http_request=1",
			success: function(datos) {
            
                if (datos=="set_visible" || datos=="set_hidden") {
                    $("#"+id_img_link_tool).attr("src",path_name_of_imglinktool);                

                    if (my_image_tool=="visible.png") {
                        $("#"+id_img_link_tool).attr("alt","' . get_lang('MakeAvailable', '') . '");
                        $("#"+id_img_link_tool).attr("title","' . get_lang('MakeAvailable', '') . '");
                    } else {
                        $("#"+id_img_link_tool).attr("alt","' . get_lang('MakeUnavailable', '') . '");
                        $("#"+id_img_link_tool).attr("title","' . get_lang('MakeUnavailable', '') . '");
                    }

                    if (datos=="set_visible") {
                        $("#id_content_message").html("<div class=\"confirmation-message\">' . get_lang('LanguageIsNowVisible', '') . '</div>");
                    }

                    if (datos=="set_hidden") {
                        $("#id_content_message").html("<div class=\"confirmation-message\">' . get_lang('LanguageIsNowHidden', '') . '</div>");
                    }
                }
                
                var action = datos.split(":")[0];                
                if (action && action == "confirm") {
                    var id = datos.split(":")[1];
                    var sure = "<div class=\"warning-message\">'.get_lang('ThereAreUsersUsingThisLanguageYouWantToDisableThisLanguageAndSetUsersWithTheDefaultPortalLanguage').'</div><a href=\"languages.php?action=make_unavailable_confirmed&id="+id+"\" class=\"btn\">' . get_lang('MakeUnavailable') . '</a>";
                    $("#id_content_message").html(sure);
				}
		} });
	});

 });
</script>';
// setting the table that is needed for the styles management (there is a check if it exists later in this code)
$tbl_admin_languages = Database :: get_main_table(TABLE_MAIN_LANGUAGE);
$tbl_settings_current = Database :: get_main_table(TABLE_MAIN_SETTINGS_CURRENT);

/*
  ==============================================================================
  STORING THE CHANGES
  ==============================================================================
 */

// we change the availability
if ($_GET['action'] == 'makeunavailable') {
    if (isset($_GET['id']) && $_GET['id'] == strval(intval($_GET['id']))) {
        SubLanguageManager::make_unavailable_language($_GET['id']);
    }
}
if ($_GET['action'] == 'makeavailable') {
    if (isset($_GET['id']) && $_GET['id'] == strval(intval($_GET['id']))) {
        SubLanguageManager::make_available_language($_GET['id']);
    }
}
if ($_GET['action'] == 'setplatformlanguage') {
    if (isset($_GET['id']) && $_GET['id'] == strval(intval($_GET['id']))) {
        SubLanguageManager::set_platform_language($_GET['id']);
    }
}


if ($_POST['Submit']) {
    // changing the name
    $sql_update = "UPDATE $tbl_admin_languages SET original_name='{$_POST['txt_name']}' WHERE id='{$_POST['edit_id']}'";
    $result = Database::query($sql_update);
    // changing the Platform language
    if ($_POST['platformlanguage'] && $_POST['platformlanguage'] <> '') {
        //$sql_update_2 = "UPDATE $tbl_settings_current SET selected_value='{$_POST['platformlanguage']}' WHERE variable='platformLanguage'";
        //$result_2 = Database::query($sql_update_2);
        api_set_setting('platformLanguage', $_POST['platformlanguage'], null, null, $_configuration['access_url']);
    }
} elseif (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'makeavailable' :
            if (count($_POST['id']) > 0) {
                $ids = array();
                foreach ($_POST['id'] as $index => $id) {
                    $ids[] = Database::escape_string($id);
                }
                $sql = "UPDATE $tbl_admin_languages SET available='1' WHERE id IN ('" . implode("','", $ids) . "')";
                Database::query($sql);
            }
            break;
        case 'makeunavailable' :
            if (count($_POST['id']) > 0) {
                $ids = array();
                foreach ($_POST['id'] as $index => $id) {
                    $ids[] = Database::escape_string($id);
                }
                $sql = "UPDATE $tbl_admin_languages SET available='0' WHERE id IN ('" . implode("','", $ids) . "')";
                Database::query($sql);
            }
            break;
    }
}


/*
  ==============================================================================
  MAIN CODE
  ==============================================================================
 */
// setting the name of the tool
$tool_name = get_lang('PlatformLanguages');

// setting breadcrumbs
$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));

// including the header file (which includes the banner itself)
Display :: display_header($tool_name);
  
if (isset($_GET['action']) && $_GET['action'] == 'make_unavailable_confirmed') {    
    $language_info = SubLanguageManager::get_all_information_of_language($_GET['id']);
    if ($language_info['available'] == 1) {
        SubLanguageManager::make_unavailable_language($_GET['id']);        
        $platform_language = api_get_setting('platformLanguage');
        UserManager::update_all_user_languages($language_info['english_name'], $platform_language);
        Display::display_confirmation_message(get_lang('LanguageIsNowHidden'));
    }
}

// displaying the explanation for this tool
Display::display_normal_message(get_lang('PlatformLanguagesExplanation'));

// selecting all the languages
$sql_select = "SELECT * FROM $tbl_admin_languages";
$result_select = Database::query($sql_select);

$sql_select_lang = "SELECT * FROM $tbl_settings_current WHERE  category='Languages'";
$result_select_lang = Database::query($sql_select_lang);
$row_lang = Database::fetch_array($result_select_lang);

/*
  --------------------------------------
  DISPLAY THE TABLE
  --------------------------------------
 */

// the table data
$language_data = array();
while ($row = Database::fetch_array($result_select)) {

    $row_td = array();
    $row_td[] = $row['id'];
    // the first column is the original name of the language OR a form containing the original name
    if ($_GET['action'] == 'edit' and $row['id'] == $_GET['id']) {
        if ($row['english_name'] == api_get_setting('platformLanguage')) {
            $checked = ' checked="checked" ';
        }

        $row_td[] = '<input type="hidden" name="edit_id" value="' . Security::remove_XSS($_GET['id']) . '" /><input type="text" name="txt_name" value="' . $row['original_name'] . '" /> '
                . '<input type="checkbox" ' . $checked . 'name="platformlanguage" id="platformlanguage" value="' . $row['english_name'] . '" /><label for="platformlanguage">' . $row['original_name'] . ' ' . get_lang('AsPlatformLanguage') . '</label> <input type="submit" name="Submit" value="' . get_lang('Ok') . '" /><a name="value" />';
    } else {
        $row_td[] = $row['original_name'];
    }
    
    // the second column
    $row_td[] = $row['english_name'];
    
    // the third column
    $row_td[] = $row['dokeos_folder'];
    
    if ($row['english_name'] == $row_lang['selected_value']) {
        $setplatformlanguage = Display::return_icon('languages.png', get_lang('CurrentLanguagesPortal'), '', ICON_SIZE_SMALL);
    } else {
        $setplatformlanguage = "<a href=\"javascript:if (confirm('" . addslashes(get_lang('AreYouSureYouWantToSetThisLanguageAsThePortalDefault')) . "')) { location.href='" . api_get_self() . "?action=setplatformlanguage&id=" . $row['id'] . "'; }\">" . Display::return_icon('languages_na.png', get_lang('SetLanguageAsDefault'), '', ICON_SIZE_SMALL) . "</a>";
    }
    
    if (api_get_setting('allow_use_sub_language') == 'true') {

        $verified_if_is_sub_language = SubLanguageManager::check_if_language_is_sub_language($row['id']);

        if ($verified_if_is_sub_language === false) {
            $verified_if_is_father = SubLanguageManager::check_if_language_is_father($row['id']);
            $allow_use_sub_language = "&nbsp;<a href='sub_language_add.php?action=definenewsublanguage&id=" . $row['id'] . "'>" . Display::return_icon('new_language.png', get_lang('CreateSubLanguage'), array(), ICON_SIZE_SMALL) . "</a>";
            if ($verified_if_is_father === true) {
                //$allow_add_term_sub_language = "&nbsp;<a href='sub_language.php?action=registersublanguage&id=".$row['id']."'>".Display::return_icon('2rightarrow.gif', get_lang('AddWordForTheSubLanguage'),array('width'=>ICON_SIZE_SMALL,'height'=>ICON_SIZE_SMALL))."</a>";
                $allow_add_term_sub_language = '';
            } else {
                $allow_add_term_sub_language = '';
            }
        } else {
            $allow_use_sub_language = '';
            $all_information_of_sub_language = SubLanguageManager::get_all_information_of_language($row['id']);
            $allow_add_term_sub_language = "&nbsp;<a href='sub_language.php?action=registersublanguage&id=" . Security::remove_XSS($all_information_of_sub_language['parent_id']) . "&sub_language_id=" . Security::remove_XSS($row['id']) . "'>" . Display::return_icon('2rightarrow.gif', get_lang('AddWordForTheSubLanguage'), array('width' => ICON_SIZE_SMALL, 'height' => ICON_SIZE_SMALL)) . "</a>";
            $allow_delete_sub_language = "&nbsp;<a href='sub_language_add.php?action=deletesublanguage&id=" . Security::remove_XSS($all_information_of_sub_language['parent_id']) . "&sub_language_id=" . Security::remove_XSS($row['id']) . "'>" . Display::return_icon('delete.png', get_lang('DeleteSubLanguage'), array('width' => ICON_SIZE_SMALL, 'height' => ICON_SIZE_SMALL)) . "</a>";
        }
    } else {
        $allow_use_sub_language = '';
        $allow_add_term_sub_language = '';
    }
    
    if ($row['english_name'] == $row_lang['selected_value']) {
        $row_td[] = Display::return_icon('visible.png', get_lang('Visible'))."<a href='" . api_get_self() . "?action=edit&id=" . $row['id'] . "#value'>" . Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_SMALL) . "</a>
                     &nbsp;" . $setplatformlanguage . $allow_use_sub_language . $allow_add_term_sub_language . $allow_delete_sub_language;        
    } else {
        if ($row['available'] == 1) {
            $row_td[] = "<a class=\"make_visible_and_invisible\" id=\"linktool_" . $row['id'] . "\" href='" . api_get_self() . "?action=makeunavailable&id=" . $row['id'] . "'>" . Display::return_icon('visible.png', get_lang('MakeUnavailable'), array('id' => 'imglinktool_' . $row['id']), ICON_SIZE_SMALL) . "</a> <a href='" . api_get_self() . "?action=edit&id=" . $row['id'] . "#value'>" . Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_SMALL) . "</a>&nbsp;" . $setplatformlanguage . $allow_use_sub_language . $allow_add_term_sub_language . $allow_delete_sub_language;
        } else {
            $row_td[] = "<a class=\"make_visible_and_invisible\" id=\"linktool_" . $row['id'] . "\" href='" . api_get_self() . "?action=makeavailable&id=" . $row['id'] . "'>" . Display::return_icon('invisible.png', get_lang('MakeAvailable'), array('id' => 'imglinktool_' . $row['id']), ICON_SIZE_SMALL) . "</a> <a href='" . api_get_self() . "?action=edit&id=" . $row['id'] . "#value'>" . Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_SMALL) . "</a>&nbsp;" . $setplatformlanguage . $allow_use_sub_language . $allow_add_term_sub_language . $allow_delete_sub_language;
        }
    }
    $language_data[] = $row_td;
}

$table = new SortableTableFromArrayConfig($language_data, 1, count($language_data));
$table->set_header(0, '');
$table->set_header(1, get_lang('OriginalName'));
$table->set_header(2, get_lang('EnglishName'));
$table->set_header(3, get_lang('DokeosFolder'));
$table->set_header(4, get_lang('Properties'));
$form_actions = array();
$form_actions['makeavailable'] = get_lang('MakeAvailable');
$form_actions['makeunavailable'] = get_lang('MakeUnavailable');
$table->set_form_actions($form_actions);
echo '<div id="id_content_message">&nbsp;</div>';
$table->display();

/*
  ==============================================================================
  FOOTER
  ==============================================================================
 */
Display :: display_footer();
?>
