<?php

    require_once __DIR__ . "/../config/dbconnection.php"; 
    require_once __DIR__ . "/../utils/helpers.php"; 
    header("Content-Type: application/json");

    $input = json_encode(file_get_contents('php://input'), true); 


    function add_submission($conn){

        // $identifier = submission_input_handler($conn);
        $id = generateUUID();  
        $quiz_id = check_quiz_for_user($conn)['Id'];
        $user_id = check_user($conn)['userId']; 

        try{

            $stmt = $conn->prepare("INSERT INTO submissions(id, quiz_id, user_id) VALUES(?,?,?)");
            $stmt->execute([$id, $quiz_id, $user_id]); 

            sendResponse(201, [
                "status" => "success",
                "message" => "submission is added or started successfully"
            ]);
        }
        catch(Exception $e){
            sendResponse(500, [
                "status" => "error", 
                "message" => $e->getMessage()
            ]);
        }
    }
    
    // get all the submissions for the user
    // url :- /submission/get_submission
    function get_submission($conn){

        // $identifier = submission_input_handler($conn); 
        $user_id = check_user($conn)['userId']; 
        
        try{
            
            $stmt = $conn->prepare('SELECT * FROM submissions WHERE user_id = ?'); 
            $stmt->execute([$user_id]); 
            $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if(empty($submissions)){
                sendResponse(401, [
                    "status" => "error", 
                    "message" => "submissions are not received"
                ]);
            }

            sendResponse(200, [
                "status" => "success", 
                "message" => "all the submission are sucessfully received", 
                "data" => $submissions
            ]);
        }
        catch(Exception $e){
            sendResponse(500, [
                "status" => "error", 
                "message" => $e->getMessage()
            ]); 
        }
    }

    // get the particular submisssion
    // url:- /submission/get_particular_submission 
    function get_particular_submission($conn){

        // $identifier = submission_input_handler($conn, false); 
        $user_id = check_user($conn)['userId']; 
        $submission_id = $_GET['submission_id'];
        
        validateInput([
            "submission id" => $submission_id
        ]);

        try{

            $stmt = $conn->prepare('SELECT * FROM submissions WHERE user_id = ? AND id = ?');
            $stmt->execute([$user_id, $submission_id]); 
            $submission = $stmt->fetch(PDO::FETCH_ASSOC);

            if(empty($submission)){
                sendResponse(400, [
                    "status" => "error", 
                    "message" => "submission is not received"
                ]); 
            }

            sendResponse(200, [
                "status" => "success", 
                "message" => "submission is received successfully", 
                "data" => $submission
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