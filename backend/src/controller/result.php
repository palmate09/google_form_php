

<?php

    require_once __DIR__ . "/../utils/helpers.php"; 
    require_once __DIR__ . "/../config/dbconnection.php"; 
    header('Content-Type: application/json'); 

    $input = json_encode(file_get_contents('php://input'), true); 

    function result_input_handler($conn){

        $id = generateUUID(); 
        $quiz = check_quiz_for_user($conn);
        $quiz_id = $quiz['Id'];
        $user = check_user($conn);
        $user_id = $user['userId']; 
        $submission = check_submission($conn);
        $submission_id = $submission['id'];
        
        $message = null; 
        switch(true){
            case !$quiz_id && !$user_id && !$result_id && !$submission_id:
                $message = "All fields are required to fill"; 
                break;  
            case !$quiz_id:
                $message = 'quiz id is required to fill';
                break; 
            case !$user_id: 
                $message = 'user id is required to fill'; 
                break; 
            case !$submission_id:
                $message = 'submission id is required to fill'; 
                break;
            case !$id:
                $message = 'id is required to fill'; 
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

    // show the result
    function show_result($conn){

        $identifier = result_input_handler($conn);
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