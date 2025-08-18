<?php

    require_once __DIR__ . "/../config/dbconnection.php"; 
    require_once __DIR__ . "/../utils/helpers.php"; 
    header("Content-Type: application/json");

    $input = json_encode(file_get_contents('php://input'), true); 


    function input_handler($conn){

        $quiz_id = check_quiz($conn);
        $user = authmiddlware();  
        if($user['role'] !== 'admin'){
            $user_id = $user['sub']; 
        }
        $id = generateUUID();
        $score = 0; 

        switch(true){
            case !$user_id && !$quiz_id && !$id: 
                $message = "All fields are required to fill"; 
                break; 
            case !$quiz_id:
                $message = "quiz id not received";
                break; 
            case !$user_id:
                $message = "user id not received"; 
                break;
            case !$id: 
                $message = 'id not received'; 
                break;
            case !$score:
                $message = 'score is required to fill'; 
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
            "quiz_id" => $quiz_id, 
            "user_id" => $user_id, 
            "id" => $id,
            "score" => $score
        ];
    }

    // create the new submission for a quiz
    function add_submission($conn){

        $identifier = input_handler($conn);
        $submission_id = $identifier['id'];  
        $quiz_id = $identifier['quiz_id']; 
        $user_id = $identifier['user_id']; 
        $score = $identifier['score']; 

        try{

            $stmt = $conn->prepare("INSERT INTO submissions(id, quiz_id, user_id, score) VALUES(?,?,?,?)");
            $stmt->execute([$submission_id, $quiz_id, $user_id, $score]); 

            http_response_code(201); 
            echo json_encode([
                "status" => "success",
                "message" => "submission is added or started successfully"
            ]);
        }
        catch(Exception $e){
            http_response_code(401); 
            echo json_encode([
                "status" => "error", 
                "message" => $e->getMessage()
            ]); 
            exit; 
        }
    }
    
    // get all the submissions for the user
    function get_submission($conn){

        $identifier = input_handler(); 
        $user_id = $identifier['user_id']; 
        
        try{
            
            $stmt = $conn->prepare('SELECT * FROM submissions WHERE user_id = ?'); 
            $stmt->execute([$user_id]); 
            $submissions = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$submissions){
                http_response_code(401); 
                echo json_encode([
                    "status" => "error", 
                    "message" => "submissions are not received"
                ]); 
                exit; 
            }

            http_response_code(200); 
            echo json_encode([
                "status" => "success", 
                "message" => "all the submission are sucessfully received", 
                "data" => $submissions
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

    // get the particular submisssion 
    function get_particular_submission($conn){

        $identifier = input_handler(); 
        $user_id = $identifier['user_id']; 
        $submission_id = $identifier['id']; 

        try{

            $stmt = $conn->prepare('SELECT * FROM submissions WHERE user_id = ? , id = ?');
            $stmt->execute([$user_id, $submission_id]); 
            $submission = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$submission){
                http_response_code(401); 
                echo json_encode([
                    "status" => "error", 
                    "message" => "submission is not received"
                ]); 
                exit; 
            }

            http_response_code(200); 
            echo json_encode([
                "status" => "success", 
                "message" => "submission is received successfully", 
                "data" => $submission
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