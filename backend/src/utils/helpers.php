<?php

    require_once __DIR__ . "/../config/dbconnection.php";
    require_once __DIR__ . "/../controller/userauth.php";
    require_once __DIR__ . "/../middleware/authmiddlware.php";
    header("Content-Type: application/json"); 


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

    function check_quiz($conn){
        $user = authmiddlware();
        if($user['role'] === 'admin'){
            $admin_id = $user['sub'];
        }
        $quiz_id = $_GET['quiz_id'];
        
        $identifier = $admin_id || $quiz_id; 

        if(!$identifier){
            http_response_code(401); 
            echo json_encode([
                "status" => "error", 
                "message" => "admin_id or quiz_id not found"
            ]); 
            exit; 
        }

        try{
            
            $stmt = $conn->prepare('SELECT * FROM quizzes WHERE Id = ? AND creator_id = ?');
            $stmt->execute([$quiz_id, $admin_id]); 
            $quiz = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$quiz){
                http_response_code(401); 
                echo json_encode([
                    "status" => "error", 
                    "message" => "Quiz not found"
                ]);
                exit; 
            }

            return $quiz; 
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

    function check_question($conn){

        $admin = roleCheck($conn); 
        $admin_id = $admin['userId'];  
        $question_id = $_GET['question_id'];  

        if(!$question_id){
            http_response_code(401); 
            echo json_encode([
                "status" => "error", 
                "message" => "question_id not found"
            ]);
            exit; 
        }

        try{

            $stmt = $conn->prepare('SELECT * FROM questions WHERE Id = ?');
            $stmt->execute([$question_id]); 
            $question_data = $stmt->fetch(PDO::FETCH_ASSOC); 

            if(!$question_data){
                http_response_code(401); 
                echo json_encode([
                    "status" => "error", 
                    "message" => "questions data not found"
                ]);
                exit; 
            }

            return $question_data; 
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

    function check_submission($conn){

        $submission_id = $_GET['id'];
        
        if(!$submission_id){
            http_response_code(401); 
            echo json_encode([
                "status" => "error",
                "message" => "quiz id is not received"
            ]);
            exit; 
        }

        try{

            $stmt =  $conn->prepare('SELECT * FROM submissions WHERE id = ?');
            $stmt->execute([$submission_id]);
            $submission_data = $stmt->fetch(PDO::FETCH_ASSOC); 

            if(!$submission_data){
                http_response_code(401); 
                echo json_encode([
                    "status" => "error", 
                    "message" => "submission data not found"
                ]);
                exit; 
            }

            return $submission_data;

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

    function check_user($conn){

        $user = authmiddlware(); 
        $user_id = $user['sub'];

        if(!$user_id){
            http_response_code(401); 
            echo json_encode([
                "status" => "error", 
                "message" => "user id not found"
            ]); 
            exit; 
        }

        try{

            $stmt = $conn->prepare('SELECT * FROM users WHERE userId = ?'); 
            $stmt->execute([$user_id]); 
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if($user['role'] !== 'user'){
                http_response_code(401); 
                echo json_encode([
                    "status" => "error", 
                    "message" => "this is not the user"
                ]);  
                exit; 
            }

            return $user; 
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

    function check_quiz_for_user($conn){
        $quiz_id = $_GET['quiz_id']; 

        if(!$quiz_id){
            http_response_code(401); 
            echo json_encode([
                "status" => "error", 
                "message" => "user_id or quiz_id not found"
            ]); 
            exit; 
        }
        
        try{

            $stmt = $conn->prepare('SELECT * FROM quizzes WHERE Id = ?');
            $stmt->execute([$quiz_id]);
            $quiz = $stmt->fetch(PDO::FETCH_ASSOC);  
            

            if(empty($quiz)){
                http_response_code(400); 
                json_encode([
                    "status" => "error", 
                    "message" => "quiz not found"
                ]); 
                exit; 
            }

            return $quiz; 

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