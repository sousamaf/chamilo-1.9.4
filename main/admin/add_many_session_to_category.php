<?php
/* For licensing terms, see /license.txt */
/**
*	@package chamilo.admin
* 	@todo use formvalidator
*/

// name of the language file that needs to be included
$language_file='admin';
// resetting the course id
$cidReset = true;

require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'add_many_session_to_category_functions.lib.php';
require_once api_get_path(LIBRARY_PATH).'sessionmanager.lib.php';

$xajax = new xajax();
$xajax -> registerFunction ('search_courses');

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_admin_script(true);

// setting breadcrumbs
$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array('url' => 'session_list.php','name' => get_lang('SessionList'));

// Database Table Definitions
$tbl_session_rel_course_rel_user	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
$tbl_session						= Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_category				= Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);
$tbl_session_rel_user				= Database::get_main_table(TABLE_MAIN_SESSION_USER);
$tbl_session_rel_course				= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_course							= Database::get_main_table(TABLE_MAIN_COURSE);

// setting the name of the tool
$tool_name= get_lang('SubscribeSessionsToCategory');
$id_session=intval($_GET['id_session']);

$add_type = 'multiple';
if(isset($_GET['add_type']) && $_GET['add_type']!=''){
	$add_type = Security::remove_XSS($_REQUEST['add_type']);
}

if (!api_is_platform_admin() && !api_is_session_admin()) {
	$sql = 'SELECT session_admin_id FROM '.Database :: get_main_table(TABLE_MAIN_SESSION).' WHERE id='.$id_session;
	$rs = Database::query($sql);
	if (Database::result($rs,0,0)!=$_user['user_id']) {
		api_not_allowed(true);
	}
}

$xajax -> processRequests();
$htmlHeadXtra[] = $xajax->getJavascript('../inc/lib/xajax/');
$htmlHeadXtra[] = '
<script type="text/javascript">
function add_course_to_session (code, content) {
	document.getElementById("course_to_add").value = "";
	document.getElementById("ajax_list_courses_single").innerHTML = "";
	destination = document.getElementById("destination");
	for (i=0;i<destination.length;i++) {
		if(destination.options[i].text == content) {
				return false;
		}
	}
	destination.options[destination.length] = new Option(content,code);
	destination.selectedIndex = -1;
	sortOptions(destination.options);
}
function send() {
	if (document.formulaire.CategorySessionId.value!=0) {
		//alert(document.formulaire.CategorySessionId.value);
		document.formulaire.formSent.value=0;
		document.formulaire.submit();
	}
}
function remove_item(origin)
{
	for(var i = 0 ; i<origin.options.length ; i++) {
		if(origin.options[i].selected) {
			origin.options[i]=null;
			i = i-1;
		}
	}
}
</script>';

$formSent=0;
$errorMsg = $firstLetterCourse = $firstLetterSession='';
$CourseList=$SessionList=array();
$courses=$sessions=array();
$Categoryid = intval($_POST['CategorySessionId']);

if ($_POST['formSent']) {
	$formSent=$_POST['formSent'];
	$SessionCategoryList = $_POST['SessionCategoryList'];

	if($Categoryid != 0 && count($SessionCategoryList)>0 ){
		$session_id = join(',', $SessionCategoryList);
		$sql = "UPDATE $tbl_session SET session_category_id = $Categoryid WHERE id in ($session_id) ";
		Database::query($sql);
		//header('Location: session_list.php?id_category='.$Categoryid);
		header('Location: add_many_session_to_category.php?id_category='.$Categoryid.'&msg=ok');
		exit;
	} else {
		header('Location: add_many_session_to_category.php?msg=error');
		exit;
	}
}

if (isset($_GET['id_category'])) {
	$Categoryid = intval($_GET['id_category']);
}

if(isset($_GET['msg']) && $_GET['msg']=='error'){
	$errorMsg = get_lang('MsgErrorSessionCategory');
}

if(isset($_GET['msg']) && $_GET['msg']=='ok'){
	$OkMsg = get_lang('SessionCategoryUpdate');
}


// display the dokeos header
Display::display_header($tool_name);


$where ='';
$rows_category_session = array();
if((isset($_POST['CategorySessionId']) && $_POST['formSent'] == 0) || isset($_GET['id_category']) ) {
	
	$where = 'WHERE session_category_id !='.$Categoryid;
	$sql = 'SELECT id, name  FROM '.$tbl_session .' WHERE session_category_id ='.$Categoryid.' ORDER BY name';
	$result=Database::query($sql);
	$rows_category_session = Database::store_result($result);
}

$rows_session_category = SessionManager::get_all_session_category();
if (empty($rows_session_category)) {
    Display::display_warning_message(get_lang('YouNeedToAddASessionCategoryFirst'));
    Display::display_footer();
    exit;
}

$sql = "SELECT id, name  FROM $tbl_session $where ORDER BY name";
$result=Database::query($sql);
$rows_session = Database::store_result($result);
?>
<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>?page=<?php echo Security::remove_XSS($_GET['page']); if(!empty($_GET['add'])) echo '&add=true' ; ?>" style="margin:0px;" <?php if($ajax_search){echo ' onsubmit="valide();"';}?>>
    <?php echo '<legend>'.$tool_name.'</legend>'; ?>
<input type="hidden" name="formSent" value="1" />
<?php
if(!empty($errorMsg)) {
	Display::display_error_message($errorMsg); //main API
}

if(!empty($OkMsg)) {
	Display::display_confirmation_message($OkMsg); //main API
}

/* 
 * 
 * The a/b/c Filter is not a priority 
 *  
 * <td width="45%" align="center">
 <?php echo get_lang('FirstLetterCourse'); ?> :
     <select name="firstLetterCourse" onchange = "xajax_search_courses(this.value,'multiple')">
      <option value="%">--</option>
      <?php
      echo Display :: get_alphabet_options();
      echo Display :: get_numeric_options(0,9,'');
      ?>
     </select>
</td>

 */
?>
<table border="0" cellpadding="5" cellspacing="0" width="100%" align="center">
<tr>
	<td align="left"></td>
	<td align="left"></td>
	<td  align="center"> 
	<b><?php echo get_lang('SessionCategoryName') ?> :</b><br />
	<select name="CategorySessionId" style="width: 320px;" onchange="javascript:send();" >
		<option value="0" ></option>
		<?php
		if (!empty($rows_session_category)) {
    		foreach($rows_session_category as $category) {
    			if($category['id'] == $Categoryid)
      				echo '<option value="'.$category['id'].'" selected>'.$category['name'].'</option>';
      			else
      				echo '<option value="'.$category['id'].'">'.$category['name'].'</option>';
    		}
		}
  		?>
  	</select>
	</td>
</tr>
<tr>
  <td width="45%" align="center"><b><?php echo get_lang('SessionListInPlatform') ?> :</b></td>
  <td width="10%">&nbsp;</td>
  <td align="center" width="45%"><b><?php echo get_lang('SessionListInCategory') ?> :</b></td>
</tr>

<?php if($add_type == 'multiple') { ?>
<tr>
<td>&nbsp;</td></tr>
<?php } ?>
<tr>
  <td width="45%" align="center">
	<div id="ajax_list_courses_multiple">
	<select id="origin" name="NoSessionCategoryList[]" multiple="multiple" size="20" style="width:320px;">
	<?php
	foreach($rows_session as $enreg) {
	?>
		<option value="<?php echo $enreg['id']; ?>" <?php echo 'title="'.htmlspecialchars($enreg['name'],ENT_QUOTES).'"'; if(in_array($enreg['id'],$CourseList)) echo 'selected="selected"'; ?>><?php echo $enreg['name']; ?></option>
	<?php } ?>
	</select></div>
<?php unset($nosessionCourses); ?>
  </td>
  <td width="10%" valign="middle" align="center">
  <?php
  if ($ajax_search) {
  ?>
  	<button class="arrowl" type="button" onclick="remove_item(document.getElementById('destination'))"></button>
  <?php
  } else {
  ?>
  	<button class="arrowr" type="button" onclick="moveItem(document.getElementById('origin'), document.getElementById('destination'))" onclick="moveItem(document.getElementById('origin'), document.getElementById('destination'))"></button>
	<br /><br />
	<button class="arrowl" type="button" onclick="moveItem(document.getElementById('destination'), document.getElementById('origin'))" onclick="moveItem(document.getElementById('destination'), document.getElementById('origin'))"></button>
  <?php
  }
  ?>
	<br /><br /><br /><br /><br /><br />
	<?php
		echo '<button class="save" type="button" value="" onclick="valide()" >'.get_lang('SubscribeSessionsToCategory').'</button>';
	?>
  </td>
  <td width="45%" align="center">
  <select id='destination' name="SessionCategoryList[]" multiple="multiple" size="20" style="width:320px;">
	<?php
	foreach($rows_category_session as $enreg) { ?>
		<option value="<?php echo $enreg['id']; ?>" <?php echo 'title="'.htmlspecialchars($enreg['name'],ENT_QUOTES).'"'; if(in_array($enreg['id'],$CourseList)) echo 'selected="selected"'; ?>><?php echo $enreg['name']; ?></option>
	<?php } ?>
  </select></td>
</tr>
</table>

</form>
<script type="text/javascript">
<!--
function moveItem(origin , destination) {
	for(var i = 0 ; i<origin.options.length ; i++) {
		if(origin.options[i].selected) {
			destination.options[destination.length] = new Option(origin.options[i].text,origin.options[i].value);
			origin.options[i]=null;
			i = i-1;
		}
	}
	destination.selectedIndex = -1;
	sortOptions(destination.options);
}

function sortOptions(options) {
	newOptions = new Array();
	for (i = 0 ; i<options.length ; i++) {
		newOptions[i] = options[i];
	}

	newOptions = newOptions.sort(mysort);
	options.length = 0;

	for(i = 0 ; i < newOptions.length ; i++){
		options[i] = newOptions[i];
	}
}

function mysort(a, b){
	if(a.text.toLowerCase() > b.text.toLowerCase()){
		return 1;
	}
	if(a.text.toLowerCase() < b.text.toLowerCase()){
		return -1;
	}
	return 0;
}

function valide(){
	var options = document.getElementById('destination').options;
	for (i = 0 ; i<options.length ; i++)
		options[i].selected = true;

	document.forms.formulaire.submit();
}
-->

</script>
<?php
/*		FOOTER	*/
Display::display_footer();