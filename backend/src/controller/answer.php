
<?php

    require_once __DIR__ . "/../utils/helpers.php"; 
    require_once __DIR__ . "/../config/dbconnection.php"; 
    header("Content-Type: application/json"); 


    $input = json_encode(file_get_contents('php://input'), true); 


    // give the answers of particular question 
    function add_answer($conn, $input ){

        $submission_id = check_submission($conn)['id'];
        $question_id = check_question($conn)['Id'];
        $option_id = check_option($conn)['Id'];
        $score = 0;  


        try{

            // find the option from the option id
            $stmt = $conn->prepare('SELECT * FROM options WHERE Id = ? AND question_id = ?'); 
            $stmt->execute([$option_id, $question_id]);
            $option_data = $stmt->fetch(PDO::FETCH_ASSOC); 

            if(empty($option_data) || $option_data === null){
                sendResponse(400, [
                    "status" => "error", 
                    "message" => "option data not found"
                ]); 
            }

            // check the option is correct or not
            if($option_data['is_correct'] == true){
                $score = $score + 1; 
            }
            else{
                $score = $score - 1; 
            }

            // add the answer
            $stmt = $conn->prepare('INSERT INTO answers(question_id, option_id, submission_id, score) VALUES (?, ?, ?, ?)');
            $stmt->execute([$question_id, $option_id, $submission_id, $score]);
            
            
            sendResponse(201, [
                "status" => "success", 
                "message" => "answer has been successfully added"
            ]);  
        }
        catch(Exception $e){
            sendResponse(500, [
                "status" => "error", 
                "message" => $e->getMessage()
            ]); 
        }
    }

    // fetch the answer data 
    // this endpoint is not working 
    function get_answer($conn){

        // $identifier = answer_input_handler($conn, false);
        $submission_id = check_submission($conn)['id']; 
        $answer_id = $_GET['answer_id'];

        validateInput([
            "answer id" => $answer_id
        ]); 
        
        try{

            $stmt = $conn->prepare('SELECT * FROM answers WHERE id = ? AND submission_id = ?');
            $stmt->execute([$answer_id, $submission_id]); 
            $answer_data = $stmt->fetch(PDO::FETCH_ASSOC); 

            if(empty($answer_data)){
                sendResponse(400, [
                    "status" => "error", 
                    "message" => "answer not received"
                ]); 
            }

            sendResponse(200, [
                "status" => "success", 
                "message" => "answer received successfully", 
                "data" => $answer_data
            ]); 
        }
        catch(Exception $e){
            sendResponse(500, [
                "status" => "error", 
                "message" => $e->getMessage()
            ]); 
        }
    }


    // get all the answers of the particular submission with questions;
    // response: - question_id :- ?  and  answer_id = ?  then your score = ? 
    function get_all_answers($conn){

        // $identifier = answer_input_handler($conn, false); 
        $submission_id = check_submission($conn)['id'];  

        try{

            $stmt = $conn->prepare('SELECT * FROM answers WHERE submission_id = ?');
            $stmt->execute([$submission_id]);
            $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    
            if(empty($answers)){
                sendResponse(401, [
                    "status" => "error", 
                    "message" => "answers not found of this submission id:= $submission_id"
                ]);
            }

            $ans = [];

            for($i = 0; $i<count($answers); $i++){
                $data = [
                    "question_id" => $answers[$i]['question_id'], 
                    "score" => $answers[$i]['score']
                ];

                $ans[] = $data;  
            }

             
            sendResponse(200, [
                "status" => "success", 
                "message" => "all the answers received of this submission id :- $submission_id", 
                "data" => $ans
            ]);  
        }
        catch(Exception $e){
            sendResponse(500, [
                "status" => "error", 
                "message" => $e->getMessage()
            ]);
        }
    }
?>
