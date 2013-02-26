<?php
/* For licensing terms, see /license.txt */
/**
 * Sessions edition script
 * @package chamilo.admin
 */
/**
 * Code
 */

// name of the language file that needs to be included
$language_file ='admin';
$cidReset = true;
require_once '../inc/global.inc.php';

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;



$formSent = 0;

// Database Table Definitions
$tbl_user		= Database::get_main_table(TABLE_MAIN_USER);
$tbl_session	= Database::get_main_table(TABLE_MAIN_SESSION);

$id = intval($_GET['id']);

SessionManager::protect_session_edit($id);

$sql = "SELECT name,date_start,date_end,id_coach, session_admin_id, nb_days_access_before_beginning, nb_days_access_after_end, session_category_id, visibility
        FROM $tbl_session WHERE id = $id";
$result = Database::query($sql);

if (!$infos = Database::fetch_array($result)) {
    header('Location: session_list.php');
    exit();
}

$id_coach   = $infos['id_coach'];



$tool_name = get_lang('EditSession');

$interbreadcrumb[] = array('url' => 'index.php',"name" => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array('url' => "session_list.php","name" => get_lang('SessionList'));
$interbreadcrumb[] = array('url' => "resume_session.php?id_session=".$id,"name" => get_lang('SessionOverview'));

list($year_start,$month_start,$day_start)   = explode('-',$infos['date_start']);
list($year_end,$month_end,$day_end)         = explode('-',$infos['date_end']);

$end_year_disabled = $end_month_disabled = $end_day_disabled = '';

if ($_POST['formSent']) {
	$formSent = 1;

	$name                  = $_POST['name'];
	$year_start            = $_POST['year_start'];
	$month_start           = $_POST['month_start'];
	$day_start             = $_POST['day_start'];
	$year_end              = $_POST['year_end'];
	$month_end             = $_POST['month_end'];
	$day_end               = $_POST['day_end'];
	$nb_days_acess_before  = $_POST['nb_days_access_before'];
	$nb_days_acess_after   = $_POST['nb_days_access_after'];
	//$nolimit               = $_POST['nolimit'];
	$id_coach              = $_POST['id_coach'];
	$id_session_category   = $_POST['session_category'];
	$id_visibility         = $_POST['session_visibility'];

    $end_limit              = $_POST['end_limit'];
    $start_limit            = $_POST['start_limit'];

    if (empty($end_limit) && empty($start_limit)) {
        $nolimit = 1;
    } else {
        $nolimit = null;
    }

	$return = SessionManager::edit_session($id,$name,$year_start,$month_start,$day_start,$year_end,$month_end,$day_end,$nb_days_acess_before,$nb_days_acess_after,$nolimit, $id_coach, $id_session_category,$id_visibility,$start_limit,$end_limit);
	if ($return == strval(intval($return))) {
		header('Location: resume_session.php?id_session='.$return);
		exit();
	}
}

$order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname, username' : ' ORDER BY lastname, firstname, username';
$sql="SELECT user_id,lastname,firstname,username FROM $tbl_user WHERE status='1'".$order_clause;

if ($_configuration['multiple_access_urls']) {
	$table_access_url_rel_user= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
	$access_url_id = api_get_current_access_url_id();
	if ($access_url_id != -1) {
		$sql="SELECT DISTINCT u.user_id,lastname,firstname,username FROM $tbl_user u INNER JOIN $table_access_url_rel_user url_rel_user ON (url_rel_user.user_id = u.user_id)
			  WHERE status='1' AND access_url_id = '$access_url_id' $order_clause";
	}
}

$result     = Database::query($sql);
$Coaches    = Database::store_result($result);
$thisYear   = date('Y');

// display the header
Display::display_header($tool_name);

// display the tool title
// api_display_tool_title($tool_name);

if (!empty($return)) {
	Display::display_error_message($return,false);
}
?>

<form class="form-horizontal" method="post" name="form" action="<?php echo api_get_self(); ?>?page=<?php echo Security::remove_XSS($_GET['page']) ?>&id=<?php echo $id; ?>" style="margin:0px;">
<fieldset>
    <legend><?php echo $tool_name; ?></legend>
    <input type="hidden" name="formSent" value="1">

    <div class="control-group">
        <label class="control-label">
            <?php echo get_lang('SessionName') ?>
        </label>
        <div class="controls">
            <input type="text" name="name" class="span4" maxlength="50" value="<?php if($formSent) echo api_htmlentities($name,ENT_QUOTES,$charset); else echo api_htmlentities($infos['name'],ENT_QUOTES,$charset); ?>">
        </div>
    </div>
    <div class="control-group">
        <label class="control-label">
            <?php echo get_lang('CoachName') ?>
        </label>
        <div class="controls">
            <select class="chzn-select" name="id_coach" style="width:380px;" title="<?php echo get_lang('Choose'); ?>" >
                <option value="">----- <?php echo get_lang('None') ?> -----</option>
                <?php foreach($Coaches as $enreg) { ?>
                <option value="<?php echo $enreg['user_id']; ?>" <?php if(($enreg['user_id'] == $infos['id_coach']) || ($enreg['user_id'] == $id_coach)) echo 'selected="selected"'; ?>><?php echo api_get_person_name($enreg['firstname'], $enreg['lastname']).' ('.$enreg['username'].')'; ?></option>
                <?php
                }
                unset($Coaches);
                $Categories = SessionManager::get_all_session_category();
            ?>
        </select>
        </div>
    </div>
    <div class="control-group">
        <label class="control-label">
            <?php echo get_lang('SessionCategory') ?>
        </label>
        <div class="controls">
                <select class="chzn-select" id="session_category" name="session_category" style="width:380px;" title="<?php echo get_lang('Select'); ?>">
        <option value="0"><?php get_lang('None'); ?></option>
        <?php
          if (!empty($Categories)) {
              foreach($Categories as $Rows)  { ?>
                <option value="<?php echo $Rows['id']; ?>" <?php if($Rows['id'] == $infos['session_category_id']) echo 'selected="selected"'; ?>><?php echo $Rows['name']; ?></option>
        <?php }
          }
         ?>
    </select>
        </div>
    </div>
    <div class="control-group">
        <div class="controls">
            <a href="javascript://" onclick="if(document.getElementById('options').style.display == 'none'){document.getElementById('options').style.display = 'block';}else{document.getElementById('options').style.display = 'none';}"><?php echo get_lang('DefineSessionOptions') ?></a>
        </div>
    </div>
    <div class="control-group">
        <div class="controls">
            <div style="display:
            <?php
                if($formSent){
                    if($nb_days_access_before!=0 || $nb_days_access_after!=0)
                        echo 'block';
                    else echo 'none';
                }
                else{
                    if($infos['nb_days_access_before_beginning']!=0 || $infos['nb_days_access_after_end']!=0)
                        echo 'block';
                    else
                        echo 'none';
                }
            ?>
                ;" id="options">

            <input type="text" name="nb_days_access_before" value="<?php if($formSent) echo api_htmlentities($nb_days_access_before,ENT_QUOTES,$charset); else echo api_htmlentities($infos['nb_days_access_before_beginning'],ENT_QUOTES,$charset); ?>" style="width: 30px;">&nbsp;<?php echo get_lang('DaysBefore') ?>
                <br />
                <br />
            <input type="text" name="nb_days_access_after" value="<?php if($formSent) echo api_htmlentities($nb_days_access_after,ENT_QUOTES,$charset); else echo api_htmlentities($infos['nb_days_access_after_end'],ENT_QUOTES,$charset); ?>" style="width: 30px;">&nbsp;<?php echo get_lang('DaysAfter') ?>

            </div>
        </div>
    </div>

    <div class="clear"></div>
    <div class="control-group">
        <div class="controls">
            <label for="start_limit">
                <input id="start_limit" type="checkbox" name="start_limit" onchange="disable_starttime(this)" <?php if ($year_start!="0000") echo "checked"; ?>/>
            <?php echo get_lang('DateStartSession');?>
            </label>
            <div id="start_date" style="<?php echo ($year_start=="0000") ? "display:none" : "display:block" ; ?>">
            <br />
              <select name="day_start">
                <option value="1">01</option>
                <option value="2" <?php if($day_start == 2) echo 'selected="selected"'; ?> >02</option>
                <option value="3" <?php if($day_start == 3) echo 'selected="selected"'; ?> >03</option>
                <option value="4" <?php if($day_start == 4) echo 'selected="selected"'; ?> >04</option>
                <option value="5" <?php if($day_start == 5) echo 'selected="selected"'; ?> >05</option>
                <option value="6" <?php if($day_start == 6) echo 'selected="selected"'; ?> >06</option>
                <option value="7" <?php if($day_start == 7) echo 'selected="selected"'; ?> >07</option>
                <option value="8" <?php if($day_start == 8) echo 'selected="selected"'; ?> >08</option>
                <option value="9" <?php if($day_start == 9) echo 'selected="selected"'; ?> >09</option>
                <option value="10" <?php if($day_start == 10) echo 'selected="selected"'; ?> >10</option>
                <option value="11" <?php if($day_start == 11) echo 'selected="selected"'; ?> >11</option>
                <option value="12" <?php if($day_start == 12) echo 'selected="selected"'; ?> >12</option>
                <option value="13" <?php if($day_start == 13) echo 'selected="selected"'; ?> >13</option>
                <option value="14" <?php if($day_start == 14) echo 'selected="selected"'; ?> >14</option>
                <option value="15" <?php if($day_start == 15) echo 'selected="selected"'; ?> >15</option>
                <option value="16" <?php if($day_start == 16) echo 'selected="selected"'; ?> >16</option>
                <option value="17" <?php if($day_start == 17) echo 'selected="selected"'; ?> >17</option>
                <option value="18" <?php if($day_start == 18) echo 'selected="selected"'; ?> >18</option>
                <option value="19" <?php if($day_start == 19) echo 'selected="selected"'; ?> >19</option>
                <option value="20" <?php if($day_start == 20) echo 'selected="selected"'; ?> >20</option>
                <option value="21" <?php if($day_start == 21) echo 'selected="selected"'; ?> >21</option>
                <option value="22" <?php if($day_start == 22) echo 'selected="selected"'; ?> >22</option>
                <option value="23" <?php if($day_start == 23) echo 'selected="selected"'; ?> >23</option>
                <option value="24" <?php if($day_start == 24) echo 'selected="selected"'; ?> >24</option>
                <option value="25" <?php if($day_start == 25) echo 'selected="selected"'; ?> >25</option>
                <option value="26" <?php if($day_start == 26) echo 'selected="selected"'; ?> >26</option>
                <option value="27" <?php if($day_start == 27) echo 'selected="selected"'; ?> >27</option>
                <option value="28" <?php if($day_start == 28) echo 'selected="selected"'; ?> >28</option>
                <option value="29" <?php if($day_start == 29) echo 'selected="selected"'; ?> >29</option>
                <option value="30" <?php if($day_start == 30) echo 'selected="selected"'; ?> >30</option>
                <option value="31" <?php if($day_start == 31) echo 'selected="selected"'; ?> >31</option>
              </select>
              /
              <select name="month_start">
                <option value="1">01</option>
                <option value="2" <?php if($month_start == 2) echo 'selected="selected"'; ?> >02</option>
                <option value="3" <?php if($month_start == 3) echo 'selected="selected"'; ?> >03</option>
                <option value="4" <?php if($month_start == 4) echo 'selected="selected"'; ?> >04</option>
                <option value="5" <?php if($month_start == 5) echo 'selected="selected"'; ?> >05</option>
                <option value="6" <?php if($month_start == 6) echo 'selected="selected"'; ?> >06</option>
                <option value="7" <?php if($month_start == 7) echo 'selected="selected"'; ?> >07</option>
                <option value="8" <?php if($month_start == 8) echo 'selected="selected"'; ?> >08</option>
                <option value="9" <?php if($month_start == 9) echo 'selected="selected"'; ?> >09</option>
                <option value="10" <?php if($month_start == 10) echo 'selected="selected"'; ?> >10</option>
                <option value="11" <?php if($month_start == 11) echo 'selected="selected"'; ?> >11</option>
                <option value="12" <?php if($month_start == 12) echo 'selected="selected"'; ?> >12</option>
              </select>
              /
              <select name="year_start">

            <?php
            for($i=$thisYear-5;$i <= ($thisYear+5);$i++) { ?>
                <option value="<?php echo $i; ?>" <?php if($year_start == $i) echo 'selected="selected"'; ?> ><?php echo $i; ?></option>
            <?php
            }
            ?>
              </select>
          </div>
        </div>
    </div>

    <div class="control-group">
        <div class="controls">
            <label for="end_limit">
                <input id="end_limit" type="checkbox" name="end_limit" onchange="disable_endtime(this)" <?php if ($year_end!="0000") echo "checked"; ?>/>
            <?php echo get_lang('DateEndSession') ?>
            </label>
          <div id="end_date" style="<?php echo ($year_end=="0000") ? "display:none" : "display:block" ; ?>">
          <br />

          <select name="day_end" <?php echo $end_day_disabled; ?> >
        	<option value="1">01</option>
        	<option value="2" <?php if($day_end == 2) echo 'selected="selected"'; ?> >02</option>
        	<option value="3" <?php if($day_end == 3) echo 'selected="selected"'; ?> >03</option>
        	<option value="4" <?php if($day_end == 4) echo 'selected="selected"'; ?> >04</option>
        	<option value="5" <?php if($day_end == 5) echo 'selected="selected"'; ?> >05</option>
        	<option value="6" <?php if($day_end == 6) echo 'selected="selected"'; ?> >06</option>
        	<option value="7" <?php if($day_end == 7) echo 'selected="selected"'; ?> >07</option>
        	<option value="8" <?php if($day_end == 8) echo 'selected="selected"'; ?> >08</option>
        	<option value="9" <?php if($day_end == 9) echo 'selected="selected"'; ?> >09</option>
        	<option value="10" <?php if($day_end == 10) echo 'selected="selected"'; ?> >10</option>
        	<option value="11" <?php if($day_end == 11) echo 'selected="selected"'; ?> >11</option>
        	<option value="12" <?php if($day_end == 12) echo 'selected="selected"'; ?> >12</option>
        	<option value="13" <?php if($day_end == 13) echo 'selected="selected"'; ?> >13</option>
        	<option value="14" <?php if($day_end == 14) echo 'selected="selected"'; ?> >14</option>
        	<option value="15" <?php if($day_end == 15) echo 'selected="selected"'; ?> >15</option>
        	<option value="16" <?php if($day_end == 16) echo 'selected="selected"'; ?> >16</option>
        	<option value="17" <?php if($day_end == 17) echo 'selected="selected"'; ?> >17</option>
        	<option value="18" <?php if($day_end == 18) echo 'selected="selected"'; ?> >18</option>
        	<option value="19" <?php if($day_end == 19) echo 'selected="selected"'; ?> >19</option>
        	<option value="20" <?php if($day_end == 20) echo 'selected="selected"'; ?> >20</option>
        	<option value="21" <?php if($day_end == 21) echo 'selected="selected"'; ?> >21</option>
        	<option value="22" <?php if($day_end == 22) echo 'selected="selected"'; ?> >22</option>
        	<option value="23" <?php if($day_end == 23) echo 'selected="selected"'; ?> >23</option>
        	<option value="24" <?php if($day_end == 24) echo 'selected="selected"'; ?> >24</option>
        	<option value="25" <?php if($day_end == 25) echo 'selected="selected"'; ?> >25</option>
        	<option value="26" <?php if($day_end == 26) echo 'selected="selected"'; ?> >26</option>
        	<option value="27" <?php if($day_end == 27) echo 'selected="selected"'; ?> >27</option>
        	<option value="28" <?php if($day_end == 28) echo 'selected="selected"'; ?> >28</option>
        	<option value="29" <?php if($day_end == 29) echo 'selected="selected"'; ?> >29</option>
        	<option value="30" <?php if($day_end == 30) echo 'selected="selected"'; ?> >30</option>
        	<option value="31" <?php if($day_end == 31) echo 'selected="selected"'; ?> >31</option>
          </select>
          /
          <select name="month_end" <?php echo $end_month_disabled; ?> >
        	<option value="1">01</option>
        	<option value="2" <?php if($month_end == 2) echo 'selected="selected"'; ?> >02</option>
        	<option value="3" <?php if($month_end == 3) echo 'selected="selected"'; ?> >03</option>
        	<option value="4" <?php if($month_end == 4) echo 'selected="selected"'; ?> >04</option>
        	<option value="5" <?php if($month_end == 5) echo 'selected="selected"'; ?> >05</option>
        	<option value="6" <?php if($month_end == 6) echo 'selected="selected"'; ?> >06</option>
        	<option value="7" <?php if($month_end == 7) echo 'selected="selected"'; ?> >07</option>
        	<option value="8" <?php if($month_end == 8) echo 'selected="selected"'; ?> >08</option>
        	<option value="9" <?php if($month_end == 9) echo 'selected="selected"'; ?> >09</option>
        	<option value="10" <?php if($month_end == 10) echo 'selected="selected"'; ?> >10</option>
        	<option value="11" <?php if($month_end == 11) echo 'selected="selected"'; ?> >11</option>
        	<option value="12" <?php if($month_end == 12) echo 'selected="selected"'; ?> >12</option>
          </select>
          /
          <select name="year_end" <?php echo $end_year_disabled; ?>>

        <?php
        for($i=$thisYear-5;$i <= ($thisYear+5);$i++) {
        ?>
        	<option value="<?php echo $i; ?>" <?php if($year_end == $i) echo 'selected="selected"'; ?> ><?php echo $i; ?></option>
        <?php
        }
        ?>
          </select>
           <br />      <br />

            <?php echo get_lang('SessionVisibility') ?> <br />
            <select name="session_visibility" style="width:250px;">
                <?php
                $visibility_list = array(SESSION_VISIBLE_READ_ONLY=>get_lang('SessionReadOnly'), SESSION_VISIBLE=>get_lang('SessionAccessible'), SESSION_INVISIBLE=>api_ucfirst(get_lang('SessionNotAccessible')));
                foreach($visibility_list as $key=>$item): ?>
                <option value="<?php echo $key; ?>" <?php if($key == $infos['visibility']) echo 'selected="selected"'; ?>><?php echo $item; ?></option>
                <?php endforeach; ?>
            </select>
    </div>
    </div>
  </div>

    <div class="control-group">
        <div class="controls">
            <button class="save" type="submit" value="<?php echo get_lang('ModifyThisSession') ?>"><?php echo get_lang('ModifyThisSession') ?></button>
        </div>
    </div>
</fieldset>
</form>

<script type="text/javascript">

<?php if($year_start=="0000") echo "setDisable(document.form.nolimit);\r\n"; ?>

function setDisable(select){

	document.form.day_start.disabled = (select.checked) ? true : false;
	document.form.month_start.disabled = (select.checked) ? true : false;
	document.form.year_start.disabled = (select.checked) ? true : false;

	document.form.day_end.disabled = (select.checked) ? true : false;
	document.form.month_end.disabled = (select.checked) ? true : false;
	document.form.year_end.disabled = (select.checked) ? true : false;

	document.form.session_visibility.disabled = (select.checked) ? true : false;
	document.form.session_visibility.selectedIndex = 0;

    document.form.start_limit.disabled = (select.checked) ? true : false;
    document.form.start_limit.checked = false;
    document.form.end_limit.disabled = (select.checked) ? true : false;
    document.form.end_limit.checked = false;

    var end_div = document.getElementById('end_date');
    end_div.style.display = 'none';

    var start_div = document.getElementById('start_date');
    start_div.style.display = 'none';


}

function disable_endtime(select) {
    var end_div = document.getElementById('end_date');
    if (end_div.style.display == 'none')
        end_div.style.display = 'block';
     else
        end_div.style.display = 'none';
}

function disable_starttime(select) {
    var start_div = document.getElementById('start_date');
    if (start_div.style.display == 'none')
        start_div.style.display = 'block';
     else
        start_div.style.display = 'none';
}

</script>
<?php
Display::display_footer();
