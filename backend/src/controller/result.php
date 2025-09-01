

<?php

    require_once __DIR__ . "/../utils/helpers.php"; 
    require_once __DIR__ . "/../config/dbconnection.php";
    header('Content-Type: application/json'); 

    $input = json_encode(file_get_contents('php://input'), true); 


    // show the result
    function show_result($conn){

        $user = check_user($conn);
        $user_id = $user['userId']; 
        $id = $_GET['id'];

        validateInput([
            "result id" => $id
        ]); 
        
        try{

            // show the result 
            $stmt = $conn->prepare("
                SELECT 
                    R.id AS result_id,R.total_score, 
                    u.userId AS user_id,u.username, 
                    q.Id AS quiz_id,q.title, 
                    s.id AS submission_id,s.submitted_at
                FROM result R
                LEFT JOIN users u ON R.user_id = u.userId
                LEFT JOIN quizzes q ON R.quiz_id = q.Id
                LEFT JOIN submissions s ON R.submission_id = s.id
                WHERE R.user_id = ? AND R.id = ?
            ");
            $stmt->execute([$user_id, $id]); 
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if($data->num_rows > 0 || empty($data)){
                sendResponse(400, [
                    "status" => "error", 
                    "message" => "result data not found"
                ]); 
            }


            $result = [
                "result id" => $data[0]["result_id"], 
                "total score" => $data[0]["total_score"], 
                "user" => $data[0]["username"], 
                "quiz" => $data[0]["title"],
                "submitted_at" => $data[0]["submitted_at"]
            ];

            sendResponse(200, [
                "status" => "success", 
                "message" => "result data found successfully", 
                "data" => $result
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