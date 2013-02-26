<?php
/* For licensing terms, see /license.txt */
/**
*	Statement (?) administration
*	This script allows to manage the statements of questions.
* 	It is included from the script admin.php
*	@package chamilo.exercise
* 	@author Olivier Brouckaert
* 	@version $Id: question_admin.inc.php 22126 2009-07-15 22:38:39Z juliomontoya $
*/
/**
 * Code
 */

// INIT QUESTION
if (isset($_GET['editQuestion'])) {
	$objQuestion = Question::read ($_GET['editQuestion']);
	$action = api_get_self()."?".api_get_cidreq()."&myid=1&modifyQuestion=".$modifyQuestion."&editQuestion=".$objQuestion->id;
} else {
	$objQuestion = Question :: getInstance($_REQUEST['answerType']);
	$action = api_get_self()."?".api_get_cidreq()."&modifyQuestion=".$modifyQuestion."&newQuestion=".$newQuestion;
}

if (is_object($objQuestion)) {
	//FORM CREATION
	$form = new FormValidator('question_admin_form','post', $action);    
	if (isset($_GET['editQuestion'])) {
		$class="btn save";
		$text=get_lang('ModifyQuestion');
		$type = Security::remove_XSS($_GET['type']);
	} else {
		$class="btn add";
		$text=get_lang('AddQuestionToExercise');
		$type = $_REQUEST['answerType'];
	}

	$types_information = Question::get_question_type_list();
	$form_title_extra = get_lang($types_information[$type][1]);

	// form title
	$form->addElement('header', $text.': '.$form_title_extra);
    
	// question form elements
	$objQuestion->createForm($form);

	// answer form elements
    
	$objQuestion->createAnswersForm($form);

	// this variable  $show_quiz_edition comes from admin.php blocks the exercise/quiz modifications    
	if ($objExercise->edit_exercise_in_lp == false) {
		$form->freeze();
	}
	
	// FORM VALIDATION
	if (isset($_POST['submitQuestion']) && $form->validate()) {

		// question
	    $objQuestion->processCreation($form, $objExercise);
        
	    // answers        
	    $objQuestion->processAnswersCreation($form, $nb_answers);

        // TODO: maybe here is the better place to index this tool, including answers text

	    // redirect
	    if ($objQuestion->type != HOT_SPOT && $objQuestion->type != HOT_SPOT_DELINEATION) {	    	
	    	if(isset($_GET['editQuestion'])) {
	    		echo '<script type="text/javascript">window.location.href="admin.php?exerciseId='.$exerciseId.'&message=ItemUpdated"</script>';
	    	} else {
	    		//New question
	    		echo '<script type="text/javascript">window.location.href="admin.php?exerciseId='.$exerciseId.'&message=ItemAdded"</script>';
	    	}
	    } else {
	    	echo '<script type="text/javascript">window.location.href="admin.php?exerciseId='.$exerciseId.'&hotspotadmin='.$objQuestion->id.'"</script>';
	    }
	} else {	 
		echo '<h3>'.$questionName.'</h3>';
		if(!empty($pictureName)){
			echo '<img src="../document/download.php?doc_url=%2Fimages%2F'.$pictureName.'" border="0">';
		}
		if(!empty($msgErr)) {
			Display::display_normal_message($msgErr); //main API
		}
		// display the form
		$form->display();
	}
}
