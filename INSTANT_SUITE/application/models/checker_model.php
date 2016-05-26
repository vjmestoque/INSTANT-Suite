<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
	class checker_model extends CI_Model{

		public function __construct(){
        	parent::__construct();
    	}

    	public function saveAnswer($exam_key, $stud_no, $question_id, $type, $student_answer, $score){

            $student_answer = strtolower($student_answer); //for convention

            //Update the answer of the student in the question in the exam_set given to him/her
                $this->updateAnswer($exam_key, $question_id, $student_answer);
                //$this->db->query("UPDATE exam_set SET answer = '$student_answer' WHERE exam_key = '$exam_key' AND question_id = '$question_id';");

            //Check the question automatically
                $this->automateChecking($exam_key, $question_id, $student_answer, $score, $type);

            //Check if question has considerations
                $consType = $this->db->query("SELECT consideration from questions WHERE question_id = '$question_id';")->row()->consideration; //Fetch the consideration column
                $initScore = $this->db->query("SELECT score from exam_set WHERE question_id = '$question_id' AND stud_answer = '$student_answer';")->row()->score; //Fetch the score column

                if($consType != NULL && $initScore == 0){ //If there is considerations and initial score is 0
                    $this->considerChecking($exam_key, $question_id, $student_answer, $score); //Recheck answer
                }
    	}

        //Update answer of student
        public function updateAnswer($exam_key, $question_id, $student_answer){
            $this->db->query("UPDATE exam_set SET stud_answer = '$student_answer' WHERE exam_key = '$exam_key' AND question_id = '$question_id';");
        }

        //Automatically check the student's answer
				public function automateChecking($exam_key, $question_id, $student_answer, $score, $type){

	            //Fetch the correct answer for the question
	    		$correctAns = $this->db->query("SELECT answer from questions WHERE question_id = '$question_id';")->row()->answer;

					if($type==6){
						$modelAnswer= explode("\"",$correctAns);
						$pyscript= "C:\\wamp\\www\\INSTANT_SUITE\\application\\models\\lsa.py";
			      $pypath="C:\\Python34\\python.exe";

						$filename="models.txt";
			      $myfile = fopen($filename, "w") or die("Unable to open file!");
						$counter=1;
						for($i=1;$i<=count($modelAnswer)-1;$i+=2){
							$counter+=1;
							$txt ="$modelAnswer[$i]\n";
							fwrite($myfile, $txt);
						}
						$txt ="$student_answer";
						fwrite($myfile, $txt);
						fclose($myfile);

			      $cmd = "$pypath $pyscript $filename $counter" ;
			      $output= exec("$cmd");
						$myfile = fopen("output.txt", "w") or die("Unable to open file!");
						$txt ="$cmd\n";
						fwrite($myfile, $txt);
						$txt ="$output\n";
						fwrite($myfile, $txt);
						fclose($myfile);

			      if($output>=50){
							$this->db->query("UPDATE exam_set SET score = 3 WHERE exam_key = '$exam_key' AND question_id = '$question_id';");
						}
						else if($output<50 && $output>=30 ){
							$this->db->query("UPDATE exam_set SET score = 2 WHERE exam_key = '$exam_key' AND question_id = '$question_id';");
						}
						else if($output<30 && $output>=15 ){
							$this->db->query("UPDATE exam_set SET score = 1 WHERE exam_key = '$exam_key' AND question_id = '$question_id';");
						}
						else{
							$this->db->query("UPDATE exam_set SET score = 0 WHERE exam_key = '$exam_key' AND question_id = '$question_id';");
						}

					}
					else{            //If correct answer, then update the score field in the answers table
		    		if($student_answer == $correctAns){
		    			$this->db->query("UPDATE exam_set SET score = '$score' WHERE exam_key = '$exam_key' AND question_id = '$question_id';");
		    		}
		            //Else then give 0 as score
		    		else{
		    			$this->db->query("UPDATE exam_set SET score = 0 WHERE exam_key = '$exam_key' AND question_id = '$question_id';");
		    		}
					}
	    	}


        //If there is a consideration in marking
        public function considerChecking($exam_key, $question_id, $student_answer, $score){

            $txtfile = $_SERVER['DOCUMENT_ROOT']."INSTANT_SUITE/consideredAnswers/".$question_id."considerations.txt";

            //Read the personal dictionary "consideredAnswers.txt"
            $myfile = fopen($txtfile, "r") or die("Unable to open file!");

            if($myfile){ //Get all considered answers from the dictionary
                $consAns = explode(PHP_EOL, fread($myfile, filesize($txtfile)));
            }
            fclose($myfile);

            //Check whether student's answer is within the considered answers list
            for($i=0;$i<count($consAns);$i++){
                if($student_answer == $consAns[$i]){
                    $this->db->query("UPDATE exam_set SET score = '$score' WHERE exam_key = '$exam_key' AND question_id = '$question_id';");
                    break; //Exit from loop
                }
            }
        }

        //Compute the accumulated points of the student
        public function computeTotalScore($exam_key, $student_no){
            //Compute for total score
            $sum = $this->db->query("SELECT sum(score) from exam_set WHERE student_no = '$student_no' AND exam_key = '$exam_key';")->row()->sum;

            return $sum;
        }

        //Function to save total score of student in a given exam
        public function saveResult($exam_key, $student_no, $computedScore){

            $resultsDetails = array(
                'exam_key' => $exam_key,
                'student_no' => $student_no,
                'total_score' => $computedScore,
                );

            $query = $this->db->insert('results',$resultsDetails);
        }

}
