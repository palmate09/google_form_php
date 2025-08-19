
<?php

    require_once __DIR__ . "/../utils/helpers.php"; 
    require_once __DIR__ . "/../config/dbconnection.php"; 
    header("Content-Type: application/json"); 


    $input = json_encode(file_get_contents('php://input'), true); 

    function answer_input_handler($conn, $input , $requiredField = true){

        $option = check_option($conn);  
        $option_id = $option['Id'] ?? null; 
        $submission = check_submission($conn);
        $submission_id = $submission['id'] ?? null; 
        $question = check_question($conn);
        $question_id = $question['Id'] ?? null;
        $answer_id = $_GET['answer_id'] ?? null;  

        $message = null; 
        switch(true){
            case $requiredField && !$question_id: 
                $message = 'question id required';
                break; 
            case $requiredField && !$option_id:
                $message = 'option id required'; 
                break; 
            case !$requiredField && !$submission_id:
                $message = 'submission id required';
                break; 
            case !$requiredField && !$answer_id:
                $message = 'answer id required'; 
                break;
        }

        if($message){
            http_response_code(401); 
            echo json_encode([
                "status" => "error", 
                "message" => $message
            ]); 
            exit; 
        }

        return [
            "submission_id" => $submission_id, 
            "question_id" => $question_id, 
            "option_id" => $option_id, 
            "answer_id" => $answer_id 
        ];
    }

    // give the answers of particular question 
    function add_answer($conn, $input ){

        $identifier = answer_input_handler($conn, $input, true);
        $submission_id = $identifier['submission_id'];
        $question_id = $identifier['question_id'];
        $option_id = $identifier['option_id'];
        $score = 0;  

        try{

            // find the option from the option id
            $stmt = $conn->prepare('SELECT * FROM options WHERE Id = ? AND question_id = ?'); 
            $stmt->execute([$option_id, $question_id]);
            $option_data = $stmt->fetch(PDO::FETCH_ASSOC); 

            if(empty($option_data) || $option_data === null){
                http_response_code(400); 
                echo json_encode([
                    "status" => "error", 
                    "message" => "option data not found"
                ]); 
                exit; 
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
            
            
            http_response_code(201); 
            echo json_encode([
                "status" => "success", 
                "message" => "answer has been successfully added"
            ]); 
        }
        catch(Exception $e){
            http_response_code(500); 
            echo json_encode([
                "status" => "error", 
                "message" => $e->getMessage()
            ]); 
            exit; 
        }
    }

    // fetch the answer data 
    // this endpoint is not working 
    function get_answer($conn){

        // $identifier = answer_input_handler($conn, false);
        $answer_id = $_GET['answer_id'];
        $submission_id = $_GET['submission_id']; 
        
        try{

            $stmt = $conn->prepare('SELECT * FROM answers WHERE id = ? AND submission_id = ?');
            $stmt->execute([$answer_id, $submission_id]); 
            $answer_data = $stmt->fetch(PDO::FETCH_ASSOC); 

            if(!$answer_data){
                http_response_code(401); 
                echo json_encode([
                    "status" => "error", 
                    "message" => "answer not received"
                ]); 
                exit; 
            }

            http_response_code(200); 
            echo json_encode([
                "status" => "success", 
                "message" => "answer received successfully", 
                "data" => $answer_data
            ]); 
        }
        catch(Exception $e){
            http_response_code(500); 
            echo json_encode([
                "status" => "error", 
                "message" => $e->getMessage()
            ]);
            exit; 
        }
    }


    // get all the answers of the particular submission with questions;
    // response: - question_id :- ?  and  answer_id = ?  then your score = ? 
    function get_all_answers($conn){

        // $identifier = answer_input_handler($conn, false); 
        $submission_id = $_GET['submission_id'];  

        try{

            $stmt = $conn->prepare('SELECT * FROM answers WHERE submission_id = ?');
            $stmt->execute([$submission_id]);
            $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    
            if(empty($answers)){
                http_response_code(401); 
                echo json_encode([
                    "status" => "error", 
                    "message" => "answers not found of this submission id:= $submission_id"
                ]); 
                exit; 
            }

            $ans = [];

            for($i = 0; $i<count($answers); $i++){
                $data = [
                    "question_id" => $answers[$i]['question_id'], 
                    "score" => $answers[$i]['score']
                ];

                $ans[] = $data;  
            }

             
            http_response_code(200); 
            echo json_encode([
                "status" => "success", 
                "message" => "all the answers received of this submission id :- $submission_id", 
                "data" => $ans
            ]); 
        }
        catch(Exception $e){
            http_response_code(500); 
            echo json_encode([
                "status" => "error", 
                "message" => $e->getMessage()
            ]); 
            exit; 
        }
    }
?>
