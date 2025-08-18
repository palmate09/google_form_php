
<?php

    require_once __DIR__ . "/../utils/helpers.php"; 
    require_once __DIR__ . "/../config/dbconnection.php"; 
    header("Content-Type: application/json"); 


    $input = json_encode(file_get_contents('php://input'), true); 

    function input_handler($conn, $input){

        $question_id = check_question($conn); 
        $option_id = check_option($conn); 
        $submission_id = check_submission($conn);
        $answer_id = $_GET['answer_id'];  


        switch(true){
            case !$question_id && !$option_id && !$submission_id && !$answer_id:
                $message = 'all fields are required'; 
                break; 
            case !$question_id: 
                $message = 'question id required';
                break; 
            case !$option_id:
                $message = 'option id required'; 
                break; 
            case !$submission_id:
                $message = 'submission id required';
                break; 
            case !$answer_id:
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

    // save the answer
    function add_answer($conn, $input){

        $identifier = input_handler($conn, $input); 
        $option_id = check_option($conn); 
        $submission_id = check_submission($conn); 
        $question_id = check_question($conn); 

        try{

            // add the answer
            $stmt = $conn->prepare('INSERT INTO answers(question_id, option_id, submission_id) VALUES (?, ?, ?)');
            $stmt->execute([$question_id, $option_id, $submission_id]); 
            
            http_response_code(201); 
            echo json_encode([
                "status" => "success", 
                "message" => "answer has been successfully saved"
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
    function get_answer($conn, $input){

        $identifier = input_handler($conn, $input);
        $answer_id = $identifier['answer_id'];
        
        try{

            $stmt = $conn->prepare('SELECT * FROM answers WHERE answer_id = ?');
            $stmt->execute([$answer_id]); 
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


?>
