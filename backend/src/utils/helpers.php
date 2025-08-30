<?php

    require_once __DIR__ . "/../config/dbconnection.php";
    require_once __DIR__ . "/../controller/userauth.php";
    require_once __DIR__ . "/../middleware/authmiddlware.php";
    header("Content-Type: application/json"); 


    // generating the user id
    function generateUUID() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, // version 4
            mt_rand(0, 0x3fff) | 0x8000, // variant
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    // check the this is admin or not
    function roleCheck($conn) {

        $auth = authmiddlware();

        if(!is_array($auth) || empty($auth['sub'])){
            http_response_code(401); 
            echo json_encode([
                "status" => "error", 
                "message" => "Unathorized"
            ]);
            exit; 
        }
        
        $adminId = $auth['sub']; 

        try{
            $stmt = $conn->prepare('SELECT * FROM users WHERE userId = ?');
            $stmt->execute([$adminId]); 
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$admin){
                http_response_code(401); 
                echo json_encode([
                    "status" => "error", 
                    "message" => "Admin not found"
                ]);
                exit; 
            }


            if($admin["role"] !== "admin"){
                http_response_code(401); 
                echo json_encode([
                    "status" => "error", 
                    "message" => "Access denied as you are not admin"
                ]);
                exit; 
            }

            return $admin; 
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

    // checking the quiz for the admin
    function check_quiz($conn){
        $admin = roleCheck($conn); 
        $adminId = $admin['userId'];  
        $quiz_id = $_GET['quiz_id'];
        
        validateInput([
            "admin id" => $adminId, 
            "quiz id" => $quiz_id
        ]);  


        try{
            $stmt = $conn->prepare('SELECT * FROM quizzes WHERE Id = ? AND creator_id = ?');
            $stmt->execute([$quiz_id, $adminId]); 
            $quiz = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$quiz){
                sendResponse(400, [
                    "status" => "error", 
                    "message" => "Quiz not found"
                ]);  
            }

            return $quiz; 
        }
        catch(Exception $e){
            sendResponse(500, [
                "status" => "error", 
                "message" => $e->getMessage()
            ]);  
        }
    }

    // check the question is present or not
    function check_question($conn){

        $admin = roleCheck($conn); 
        $admin_id = $admin['userId'];  
        $quiz = check_quiz($conn);
        $quiz_id = $quiz['Id'];  
        $question_id = $_GET['question_id']; 
        
        validateInput([
            "admin id" => $admin_id, 
            "quiz id" => $quiz_id, 
            "question id" => $question_id
        ]); 


        try{
            $stmt = $conn->prepare('SELECT * FROM questions WHERE Id = ? AND quiz_id = ?');
            $stmt->execute([$question_id, $quiz_id]); 
            $question_data = $stmt->fetch(PDO::FETCH_ASSOC); 

            if(empty($question_data)){
                sendResponse(401, [
                    "status" => "error", 
                    "message" => "questions data not found"
                ]); 
            }

            return $question_data; 
        }
        catch(Exception $e){
            sendResponse(500, [
                "status" => "error", 
                "message" => $e->getMessage()
            ]);
        }
    }

    // check the options is present or not
    function check_option($conn){

        $option_id = $_GET['option_id']; 

        if(!$option_id){
            http_response_code(401); 
            echo json_encode([
                "status" => "error", 
                "message" => "option id not found"
            ]); 
            exit; 
        }

        try{

            $stmt = $conn->prepare('SELECT * FROM options WHERE Id = ?');
            $stmt->execute([$option_id]); 
            $option_data = $stmt->fetch(PDO::FETCH_ASSOC); 

            if(!$option_data){
                http_response_code(401); 
                echo json_encode([
                    "status" => "error", 
                    "message" => "option data not found"
                ]); 
                exit; 
            }

            return $option_data; 
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

    // check the submission is created or not
    function check_submission($conn){

        $submission_id = $_GET['submission_id'];
        
        validateInput([
            "submission id" => $submission_id
        ]); 

        try{

            $stmt =  $conn->prepare('SELECT * FROM submissions WHERE id = ?');
            $stmt->execute([$submission_id]);
            $submission_data = $stmt->fetch(PDO::FETCH_ASSOC); 

            if(empty($submission_data)){
                sendResponse(401, [
                    "status" => "error", 
                    "message" => "submission data not found"
                ]); 
            }

            return $submission_data;

        }
        catch(Exception $e){
            sendResponse(500, [
                "status" => "error", 
                "message" => $e->getMessage()
            ]); 
        }
    }

    // check the it is user or not  
    function check_user($conn){

        $user = authmiddlware();
        $user_id = $user['sub'];

        validateInput([
            "user id" => $user_id
        ]); 

        try{

            $stmt = $conn->prepare('SELECT * FROM users WHERE userId = ?'); 
            $stmt->execute([$user_id]); 
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if($user['role'] !== 'user'){
                sendResponse(401, [
                    "status" => "error", 
                    "message" => "this is not the user"
                ]); 
            }

            return $user; 
        }
        catch(Exception $e){
            sendResponse(500, [
                "status" => "error", 
                "message" => $e->getMessage()
            ]); 
        }
    }

    // checking the quiz for the user
    function check_quiz_for_user($conn){
        $quiz_id = $_GET['quiz_id']; 

        validateInput([
            "quiz id" => $quiz_id
        ]); 
        
        try{

            $stmt = $conn->prepare('SELECT * FROM quizzes WHERE Id = ?');
            $stmt->execute([$quiz_id]);
            $quiz = $stmt->fetch(PDO::FETCH_ASSOC);  
            

            if(empty($quiz)){
                sendResponse(401, [
                    "status" => "error", 
                    "message" => "quiz not found"
                ]); 
            }

            return $quiz; 

        }
        catch(Exception $e){
            sendResponse(500, [
                "status" => "error", 
                "message" => $e->getMessage()
            ]); 
        }
    }
?>