

<?php

    require_once __DIR__ . "/../utils/helpers.php"; 
    require_once __DIR__ . "/../config/dbconnection.php"; 
    header('Content-Type: application/json'); 

    $input = json_encode(file_get_contents('php://input'), true); 

    // this function is used to check weather this id's is received or not if not then give the error
    function result_input_handler($conn, $isRequired = true){

        $id = generateUUID(); // calling the uuid generation from helper
        $quiz = check_quiz_for_user($conn); // calling to check quiz can be accessed by the user only form helper 
        $quiz_id = $quiz['Id'] ?? null; 
        $user = check_user($conn); // calling check_user from helper to check the user is present or not from helper 
        $user_id = $user['userId'] ?? null; 
        $submission = check_submission($conn); // calling from the helper to check the submission is created or not from helper
        $submission_id = $submission['id'] ?? null;
        
        $message = null; 
        switch(true){
            case !$quiz_id && !$user_id && !$result_id && !$submission_id:
                $message = "All fields are required to fill"; 
                break;  
            case $isRequired && !$quiz_id:
                $message = 'quiz id is required to fill';
                break; 
            case !$user_id: 
                $message = 'user id is required to fill'; 
                break; 
            case $isRequired && !$submission_id:
                $message = 'submission id is required to fill'; 
                break;
            case $isRequired && !$id:
                $message = 'id is required to fill'; 
                break;   
        } // this switch is used to check each field should be present if not then give me the error

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

    function add_result($conn){

        $identifier = result_input_handler($conn, true);
        $user_id = $identifier['user_id']; 
        $quiz_id = $identifier['quiz_id']; 
        $submission_id = $identifier['submission_id']; 
        $id = $identifier['id']; 
        $total_score = 0; 

        try{
            // give the query to get the total_score of each user
            $stmt = $conn->prepare('SELECT submission_id , SUM(score) AS total_score FROM answers GROUP BY submission_id');
            $stmt->execute();
            $submission_data = $stmt->fetch(PDO::FETCH_ASSOC);
            $total_score += $submission_data['total_score'];
            
            // create the result
            $stmt = $conn->prepare('INSERT INTO result(id, total_score, user_id, quiz_id, submission_id) VALUES(?,?,?,?,?)');
            $stmt->execute([$id, $total_score, $user_id, $quiz_id, $submission_id]); 


            http_response_code(201); 
            echo json_encode([
                "status" => "success", 
                "message" => "result has been creted succesfully"
            ]); 

        }
        catch(Exception $e){
            http_response_code(500); 
            echo json_encode([
                "status" =>  "error", 
                "message" => $e->getMessage()
            ]); 
            exit;
        }
    }

    // show the result
    function show_result($conn){

        $user = check_user($conn);
        $user_id = $user['userId']; 
        $id = $_GET['id'] ?? null;
        
        try{

            // show the result 
            $stmt = $conn->prepare('SELECT * FROM result WHERE user_id = ? AND id = ?');
            $stmt->execute([$user_id, $id]); 
            $result_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($result_data->num_rows > 0 || empty($result_data)){
                http_response_code(401); 
                echo json_encode([
                    "status" => "error", 
                    "message" => "result data not found"
                ]); 
                exit; 
            }

            http_response_code(200); 
            echo json_encode([
                "status" => "success", 
                "message" => "result data found successfully", 
                "data" => $result_data
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