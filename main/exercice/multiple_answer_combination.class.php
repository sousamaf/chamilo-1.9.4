<?php
/* For licensing terms, see /license.txt */
/**
*	File containing the MultipleAnswer class.
*	@package chamilo.exercise
* 	@author Eric Marguin
* 	@version $Id: admin.php 10680 2007-01-11 21:26:23Z pcool $
*/
/**
 * Code
 */
if(class_exists('MultipleAnswerCombination')) { return true; }

/**
	CLASS MultipleAnswer
 *
 *	This class allows to instantiate an object of type MULTIPLE_ANSWER (MULTIPLE CHOICE, MULTIPLE ANSWER),
 *	extending the class question
 *
 *	@author Eric Marguin
 *	@package chamilo.exercise
 **/

class MultipleAnswerCombination extends Question {

	static $typePicture = 'mcmac.gif';
	static $explanationLangVar = 'MultipleSelectCombination';

	/**
	 * Constructor
	 */
	function MultipleAnswerCombination(){
		parent::question();
		$this -> type = MULTIPLE_ANSWER_COMBINATION;
		$this -> isContent = $this-> getIsContent();
	}

	/**
	 * function which redifines Question::createAnswersForm
	 * @param the formvalidator instance
	 * @param the answers number to display
	 */
	function createAnswersForm ($form) {
		$nb_answers = isset($_POST['nb_answers']) ? $_POST['nb_answers'] : 2;
		$nb_answers += (isset($_POST['lessAnswers']) ? -1 : (isset($_POST['moreAnswers']) ? 1 : 0));
		$obj_ex = $_SESSION['objExercise'];
		$html= '<table class="data_table">
					<tr style="text-align: center;">
						<th width="10px">
							'.get_lang('Number').'
						</th>
						<th width="10px">
							'.get_lang('True').'
						</th>
						<th width="50%">
							'.get_lang('Answer').'
						</th>';				
						// show column comment when feedback is enable						
						if ($obj_ex->selectFeedbackType() != EXERCISE_FEEDBACK_TYPE_EXAM ) {	
							$html .='<th>'.get_lang('Comment').'</th>';
						}
						$html .= '</tr>';
        $form -> addElement ('label', get_lang('Answers').'<br /> <img src="../img/fill_field.png">', $html);

		$defaults = array();
		$correct = 0;
		if(!empty($this -> id))	{
			$answer = new Answer($this -> id);
			$answer -> read();
			if(count($answer->nbrAnswers)>0 && !$form->isSubmitted()) {
				$nb_answers = $answer->nbrAnswers;
			}
		}

		$form -> addElement('hidden', 'nb_answers');
		$boxes_names = array();
		
		if ($nb_answers < 1) {
			$nb_answers = 1;
			Display::display_normal_message(get_lang('YouHaveToCreateAtLeastOneAnswer'));
		}		

		for($i = 1 ; $i <= $nb_answers ; ++$i) {
			if(is_object($answer)) {
				$defaults['answer['.$i.']'] = $answer -> answer[$i];
				$defaults['comment['.$i.']'] = $answer -> comment[$i];
				$defaults['weighting['.$i.']'] = float_format($answer -> weighting[$i], 1);
				$defaults['correct['.$i.']'] = $answer -> correct[$i];
			} else {
				$defaults['answer[1]']  = get_lang('DefaultMultipleAnswer2');
				$defaults['comment[1]'] = get_lang('DefaultMultipleComment2');
				$defaults['correct[1]'] = true;
				$defaults['weighting[1]'] = 10;

				$defaults['answer[2]']  = get_lang('DefaultMultipleAnswer1');
				$defaults['comment[2]'] = get_lang('DefaultMultipleComment1');
				$defaults['correct[2]'] = false;				
			}
			$renderer = & $form->defaultRenderer();
			
            $renderer->setElementTemplate('<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>', 'correct['.$i.']');  
            $renderer->setElementTemplate('<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>', 'counter['.$i.']');  
            $renderer->setElementTemplate('<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>', 'answer['.$i.']');  
            $renderer->setElementTemplate('<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>', 'comment['.$i.']');  
                    

			$answer_number=$form->addElement('text', 'counter['.$i.']', null,'value="'.$i.'"');
			$answer_number->freeze();

			$form->addElement('checkbox', 'correct['.$i.']', null, null, 'class="checkbox" style="margin-left: 0em;"');
			$boxes_names[] = 'correct['.$i.']';

			$form->addElement('html_editor', 'answer['.$i.']',null, 'style="vertical-align:middle"', array('ToolbarSet' => 'TestProposedAnswer', 'Width' => '100%', 'Height' => '100'));
			$form->addRule('answer['.$i.']', get_lang('ThisFieldIsRequired'), 'required');
			
			if ($obj_ex->selectFeedbackType() != EXERCISE_FEEDBACK_TYPE_EXAM) {
				$form->addElement('html_editor', 'comment['.$i.']',null, 'style="vertical-align:middle"', array('ToolbarSet' => 'TestProposedAnswer', 'Width' => '100%', 'Height' => '100'));
			}
			//only 1 answer the all deal ...			
			//$form->addElement('text', 'weighting['.$i.']',null, 'style="vertical-align:middle;margin-left: 0em;" size="5" value="10"');
				
			$form -> addElement ('html', '</tr>');
		}
		$form -> addElement ('html', '</table>');
		$form -> addElement ('html', '<br />');

		$form -> add_multiple_required_rule ($boxes_names , get_lang('ChooseAtLeastOneCheckbox') , 'multiple_required');		
						
		//only 1 answer the all deal ...
		$form->addElement('text', 'weighting[1]', get_lang('Score'), array('class' => "span1", 'value' => '10'));
				
		$navigator_info = api_get_navigator();
		global $text, $class;
		//ie6 fix
		if ($obj_ex->edit_exercise_in_lp == true) {
			if ($navigator_info['name']=='Internet Explorer' &&  $navigator_info['version']=='6') {
                
                $form->addElement('submit', 'lessAnswers', get_lang('LessAnswer'),'class="btn minus"');
                $form->addElement('submit', 'moreAnswers', get_lang('PlusAnswer'),'class="btn plus"');
                $form->addElement('submit', 'submitQuestion', $text, 'class="'.$class.'"');				
			} else {
                
                $form->addElement('style_submit_button', 'lessAnswers', get_lang('LessAnswer'),'class="btn minus"');
                $form->addElement('style_submit_button', 'moreAnswers', get_lang('PlusAnswer'),'class="btn plus"');
                $form->addElement('style_submit_button', 'submitQuestion',$text, 'class="'.$class.'"');
				
				// setting the save button here and not in the question class.php
				
			}
		}
		$renderer->setElementTemplate('{element}&nbsp;','lessAnswers');
		$renderer->setElementTemplate('{element}&nbsp;','submitQuestion');
		$renderer->setElementTemplate('{element}&nbsp;','moreAnswers');
		$form -> addElement ('html', '</div></div>');
		$defaults['correct'] = $correct;
		if (!empty($this->id)) {
			$form -> setDefaults($defaults);
		} else {
			if ($this -> isContent == 1) {
				$form -> setDefaults($defaults);
			}
		}
		$form->setConstants(array('nb_answers' => $nb_answers));
	}


	/**
	 * abstract function which creates the form to create / edit the answers of the question
	 * @param the formvalidator instance
	 * @param the answers number to display
	 */
	function processAnswersCreation($form) {

		$questionWeighting = $nbrGoodAnswers = 0;

		$objAnswer = new Answer($this->id);

		$nb_answers = $form -> getSubmitValue('nb_answers');
		
		for($i=1 ; $i <= $nb_answers ; $i++)
        {
        	$answer = trim($form -> getSubmitValue('answer['.$i.']'));
            $comment = trim($form -> getSubmitValue('comment['.$i.']'));
            if ($i == 1)
            	$weighting = trim($form -> getSubmitValue('weighting['.$i.']'));
            else {
            	$weighting = 0;
            }
            $goodAnswer = trim($form -> getSubmitValue('correct['.$i.']'));

			if($goodAnswer){
    			$weighting = abs($weighting);
			} else {
				$weighting = abs($weighting);
			//	$weighting = -$weighting;
			}
    		if($weighting > 0)
            {
                $questionWeighting += $weighting;
            }
        	$objAnswer -> createAnswer($answer,$goodAnswer,$comment,$weighting,$i);
        }

    	// saves the answers into the data base
        $objAnswer -> save();

        // sets the total weighting of the question
        $this -> updateWeighting($questionWeighting);
        $this -> save();
	}
	
	function return_header($feedback_type = null, $counter = null, $score = null) {
	    $header = parent::return_header($feedback_type, $counter, $score);
	    $header .= '<table class="'.$this->question_table_class .'">			
			<tr>
				<th>'.get_lang("Choice").'</th>
				<th>'. get_lang("ExpectedChoice").'</th>
				<th>'. get_lang("Answer").'</i></th>';
				if ($feedback_type != EXERCISE_FEEDBACK_TYPE_EXAM) { 
    				$header .= '<th>'.get_lang("Comment").'</th>';
				} else { 
					$header .= '<th>&nbsp;</th>';
				}
        $header .= '</tr>';
        return $header;	  
	}
}
