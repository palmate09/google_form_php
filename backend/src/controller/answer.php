
<?php

    require_once __DIR__ . "/../utils/helpers.php"; 
    require_once __DIR__ . "/../config/dbconnection.php"; 
    header("Content-Type: application/json"); 


    $input = json_encode(file_get_contents('php://input'), true); 

    function input_handler($conn, $input){

        $option = check_option($conn);  
        $option_id = $option['Id']; 
        $submission = check_submission($conn);
        $submission_id = $submission['id']; 
        $question = check_question($conn);
        $question_id = $question['Id'];
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

    // give the answers of particular question 
    function add_answer($conn, $input){

        $identifier = input_handler($conn, $input);
        $submission_id = $identifier['submission_id'];
        $question_id = $identifier['question_id'];
        $option_id = $identifier['option_id'];
        $answer_id = $identifier['answer_id'];
        $score = 0;  

        try{

            // add the answer
            $stmt = $conn->prepare('INSERT INTO answers(question_id, option_id, submission_id) VALUES (?, ?, ?)');
            $stmt->execute([$question_id, $option_id, $submission_id]);
            
            // check the option is correct or not
            if($option['is_correct'] == true){
                $score = $score + 1; 
            }
            else{
                $score = $score - 1; 
            }
            
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

    //update the answer 
    function update_answer($conn, $input){
        $option = check_option($conn);  
        $option_id = $option['Id']; 
        $submission = check_submission($conn);
        $submission_id = $submission['id']; 
        $question = check_question($conn);
        $question_id = $question['Id'];
        $answer_id = $_GET['answer_id'];

        $identifier = $option_id ?? $question_id ?? $submission_id;

        if($identifier === null || empty($identifier)){
            http_response_code(401); 
            echo json_encode([
                "status" => "error", 
                "message" => "atleast one field is required"
            ]);
            exit; 
        }
        
        try{    

            // update the user
            $stmt = $conn->prepare('UPDATE answers SET question_id = ?, submission_id = ?, option_id = ? WHERE id = ?');
            $stmt->execute([$question_id, $submission_id, $option_id, $answer_id]);
            
            //display the updated user
            $stmt = $conn->prepare('SELECT * FROM answers WHERE id = ?');
            $stmt->execute([$answer_id]);
            $showAnswer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            http_response_code(200);
            echo json_encode([
                "status" => "error", 
                "message" => "user has been successfully updated", 
                "data" => $showAnswer
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
