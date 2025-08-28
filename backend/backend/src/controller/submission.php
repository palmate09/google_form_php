<?php

    require_once __DIR__ . "/../config/dbconnection.php"; 
    require_once __DIR__ . "/../utils/helpers.php"; 
    header("Content-Type: application/json");

    $input = json_encode(file_get_contents('php://input'), true); 


    function submission_input_handler($conn, $requiredField = true){

        $quiz = check_quiz_for_user($conn);
        $quiz_id = $quiz['Id'];  
        $user = authmiddlware();  
        if($user['role'] !== 'admin'){
            $user_id = $user['sub']; 
        }
        $id = generateUUID();
        $submission_id = $_GET['submission_id'];

        $message = null; 
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
            case !$id && $requiredField: 
                $message = 'id not received'; 
                break;
            case !$requiredField && !$submission_id:
                $message = 'submission id is required to fill';
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
            "submission_id" => $submission_id
        ];
    }

    // create the new submission for a quiz
    // url :- /submissions/add_submission
    function add_submission($conn){

        $identifier = submission_input_handler($conn);
        $id = $identifier['id'];  
        $quiz_id = $identifier['quiz_id'];
        $user_id = $identifier['user_id']; 

        try{

            $stmt = $conn->prepare("INSERT INTO submissions(id, quiz_id, user_id) VALUES(?,?,?)");
            $stmt->execute([$id, $quiz_id, $user_id]); 

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
    // url :- /submission/get_submission
    function get_submission($conn){

        $identifier = submission_input_handler($conn); 
        $user_id = $identifier['user_id']; 
        
        try{
            
            $stmt = $conn->prepare('SELECT * FROM submissions WHERE user_id = ?'); 
            $stmt->execute([$user_id]); 
            $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    // url:- /submission/get_particular_submission 
    function get_particular_submission($conn){

        $identifier = submission_input_handler($conn, false); 
        $user_id = $identifier['user_id']; 
        $submission_id = $identifier['submission_id']; 

        try{

            $stmt = $conn->prepare('SELECT * FROM submissions WHERE user_id = ? AND id = ?');
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