<?php
/* For licensing terms, see /license.txt */
/**
 * Script
 * @package chamilo.gradebook
 */
/**
 * Init
 */

$language_file = 'gradebook';

require_once '../inc/global.inc.php';
$current_course_tool  = TOOL_GRADEBOOK;

api_protect_course_script();

require_once 'lib/gradebook_functions.inc.php';
require_once 'lib/be.inc.php';
require_once 'lib/gradebook_data_generator.class.php';

//extra javascript functions for in html head:
$htmlHeadXtra[] =
"<script>
function confirmation() {
	if (confirm(\" ".trim(get_lang('AreYouSureToDelete'))." ?\"))
		{return true;}
	else
		{return false;} 
}
</script>";
api_block_anonymous_users();

if (!api_is_allowed_to_edit()) {
	api_not_allowed(true);
}

$cat_id = isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : null;
$action = isset($_GET['action']) && $_GET['action'] ? $_GET['action'] : null;
switch ($action) {
    case 'export_all_certificates':
        $params['orientation'] = 'landscape';
        $page_format = $params['orientation'] == 'landscape' ? 'A4-L' : 'A4';
        $pdf = new PDF($page_format, $params['orientation'], $params);

        $certificate_list = get_list_users_certificates($cat_id);
        $certificate_path_list = array();
        
        if (!empty($certificate_list)) {
            foreach ($certificate_list as $index=>$value) {
                $list_certificate = get_list_gradebook_certificates_by_user_id($value['user_id'], $cat_id);
                foreach ($list_certificate as $value_certificate) {
                    $certificate_obj = new Certificate($value_certificate['id']);
                    $certificate_obj->generate(array('hide_print_button' => true));
                    if ($certificate_obj->html_file_is_generated()) {
                        $certificate_path_list[]= $certificate_obj->html_file;
                    }
                }        
            }
        }
        if (!empty($certificate_path_list)) {
            // Print certificates (without the common header/footer/watermark 
            //  stuff) and return as one multiple-pages PDF
            $pdf->html_to_pdf($certificate_path_list, get_lang('Certificates'), null, false, false);
        }
        break;
    case 'generate_all_certificates':
        $user_list = Coursemanager::get_user_list_from_course_code(api_get_course_id(), api_get_session_id());
        if (!empty($user_list)) {
            foreach ($user_list as $user_info) {                  
                Category::register_user_certificate($cat_id, $user_info['user_id']);
            }
        }
        break;
    case 'delete_all_certificates':        
        $certificate_list = get_list_users_certificates($cat_id);
        if (!empty($certificate_list)) {
            foreach ($certificate_list as $index=>$value) {
                $list_certificate = get_list_gradebook_certificates_by_user_id($value['user_id'], $cat_id);
                foreach ($list_certificate as $value_certificate) {
                    $certificate_obj = new Certificate($value_certificate['id']);
                    $certificate_obj->delete(true);
                }
            }
        }
        break;
}

$course_code = api_get_course_id();

$interbreadcrumb[] = array ('url' => Security::remove_XSS($_SESSION['gradebook_dest']).'?',	'name' => get_lang('Gradebook'));
$interbreadcrumb[] = array ('url' => '#','name' => get_lang('GradebookListOfStudentsCertificates'));

$this_section = SECTION_COURSES;

Display::display_header('');

if ($_GET['action'] == 'delete') {
    $check = Security::check_token('get');
    if ($check) {
        $certificate = new Certificate($_GET['certificate_id']);
        $result = $certificate->delete(true);
        Security::clear_token();
        if ($result ==true) {
            Display::display_confirmation_message(get_lang('CertificateRemoved'));
        } else  {
            Display::display_error_message(get_lang('CertificateNotRemoved'));		
        }
    }
}

$token = Security::get_token();

echo Display::page_header(get_lang('GradebookListOfStudentsCertificates'));

//@todo replace all this code with something like get_total_weight()
$cats = Category :: load($cat_id, null, null, null, null, null, false);

if (!empty($cats)) {

    //with this fix the teacher only can view 1 gradebook
    if (api_is_platform_admin()) {
        $stud_id= (api_is_allowed_to_edit() ? null : api_get_user_id());
    } else {
        $stud_id= api_get_user_id();
    }

    $total_weight = $cats[0]->get_weight();

    $allcat  = $cats[0]->get_subcategories($stud_id, api_get_course_id(), api_get_session_id());
    $alleval = $cats[0]->get_evaluations($stud_id);
    $alllink = $cats[0]->get_links($stud_id);

    $datagen = new GradebookDataGenerator ($allcat,$alleval, $alllink);

    $total_resource_weight = 0;
    if (!empty($datagen)) {    
        $data_array = $datagen->get_data(GradebookDataGenerator :: GDG_SORT_NAME,0,null,true);

        if (!empty($data_array)) {
            $newarray = array();
            foreach ($data_array as $data) {
                $newarray[] = array_slice($data, 1);
            }

            foreach($newarray as $item) {
                $total_resource_weight = $total_resource_weight + $item['2'];
            }            
        }
    }    

    if ($total_resource_weight != $total_weight) {
        Display::display_warning_message(get_lang('SumOfActivitiesWeightMustBeEqualToTotalWeight'));
    }
}

$certificate_list = get_list_users_certificates($cat_id);

echo '<div class="btn-group">';
$url = api_get_self().'?action=generate_all_certificates'.'&'.api_get_cidReq().'&cat_id='.$cat_id;
echo Display::url(get_lang('GenerateCertificates'), $url, array('class' => 'btn'));

$url = api_get_self().'?action=delete_all_certificates'.'&'.api_get_cidReq().'&cat_id='.$cat_id;
echo Display::url(get_lang('DeleteAllCertificates'), $url, array('class' => 'btn'));

if (count($certificate_list) > 0) {
    $url = api_get_self().'?action=export_all_certificates'.'&'.api_get_cidReq().'&cat_id='.$cat_id;
    echo Display::url(get_lang('ExportAllCertificatesToPDF'), $url, array('class' => 'btn'));
}
echo '</div>';

if (count($certificate_list)==0) {
    echo Display::display_warning_message(get_lang('NoResultsAvailable'));    
} else {
    
    echo '<br /><br /><table class="data_table">';
    
    foreach ($certificate_list as $index=>$value) {
        echo '<tr>
                <td width="100%" class="actions">'.get_lang('Student').' : '.api_get_person_name($value['firstname'], $value['lastname']).' ('.$value['username'].')</td>';
		echo '</tr>';
        echo '<tr><td>
            <table class="data_table">';
		
		$list_certificate = get_list_gradebook_certificates_by_user_id($value['user_id'], $cat_id);
		foreach ($list_certificate as $value_certificate) {
			echo '<tr>';
			echo '<td width="50%">'.get_lang('Score').' : '.$value_certificate['score_certificate'].'</td>';
			echo '<td width="30%">'.get_lang('Date').' : '.api_convert_and_format_date($value_certificate['created_at']).'</td>';
			echo '<td width="20%">';			
            $url = api_get_path(WEB_PATH).'certificates/index.php?id='.$value_certificate['id'];
            $certificates = Display::url(get_lang('Certificate'), $url, array('target'=>'_blank', 'class' => 'btn'));            
            echo $certificates;            
			echo '<a onclick="return confirmation();" href="gradebook_display_certificate.php?sec_token='.$token.'&cidReq='.$course_code.'&action=delete&cat_id='.$cat_id.'&certificate_id='.$value_certificate['id'].'">
                    '.Display::return_icon('delete.png',get_lang('Delete')).'
                  </a>';
			echo '</td></tr>';			
		}
		echo '</table>';
        echo '</td></tr>';    
    }
    echo '</table>';
}
Display::display_footer();
