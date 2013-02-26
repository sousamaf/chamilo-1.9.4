<?php
/* For licensing terms, see /license.txt */

// name of the language file that needs to be included
$language_file = array ('index', 'help', 'userInfo', 'trad4all');
// we are not inside a course, so we reset the course id

$cidReset = true;

// setting the global file that gets the general configuration, the databases, the languages, ...
require_once '../inc/global.inc.php';
$this_section = SECTION_RMCORPORE;

api_block_anonymous_users();

require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';


// setting the name of the tool
$nameTools = get_lang('RMCorpore');
$alias = "CorporeRM";
// showing the header

Display::display_header(get_lang('RMCorpore'));

$userinfo = api_get_user_info();
$user_id = $userinfo['user_id'];

$user_extra = UserManager::get_extra_user_data($user_id);

$loginrmcorpore = $user_extra['loginrmcorpore'];

echo '<section id="main_content">';
    echo '<div class="well">';
        echo '<div class="row">';

                        //social-info

                            echo '<div class="span7">';
                                    echo get_lang('HRMCorporeContent');
                            echo '</div>';



                            echo '<div class="span3">';
                                echo '<div class="well sidebar-nav" id="profile_block">';
                                    echo "
                                        <style>
                                            input.image{
                                                    border-style:none;
                                            }
                                    </style>

                                    <form style='font-size: 12px;  font-family: Arial; height: 100%' action='http://portalrm.ucb.br/Corpore.Net/Login.aspx?AutoLoginType=ExternalLogin' method='post' name='form1' target='windowTop'>
                                    <br />
                                    &nbsp;Matrícula Acadêmica:<br />
                                    &nbsp;<input style='font-size: 12px; font-family: Arial' name='user' type='text' value='".$loginrmcorpore."' /><br />
                                    &nbsp;Senha:<br />
                                    &nbsp;<input style='font-size: 12px; font-family: Arial' type='password' name='pass' /><input type='image' class='image' src='".api_get_path(WEB_PATH)."main/img/bt_seta_portalacademico.png' align='middle' onClick='submit' />
                                    <br />
                                    <input type=hidden name='alias' value='".$alias."' /><br>
                                    </form>
                                    <iframe name='corporerm' frameborder='0' src='http://rm2.ucb.br/Corpore.Net/Login.aspx?AutoLoginType=ExternalLogin' width='1' height='1'></iframe></body>
                                    ";
                                    echo '</div>';
                            echo '</div>';
        echo '</div>'; // fim do row 
    echo '</div>'; // fim do well
echo '</section>';

Display :: display_footer();
?>

