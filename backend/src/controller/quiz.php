
<?php

    require_once __DIR__ . "/../config/dbconnection.php";
    require_once __DIR__ . "/../middleware/authmiddlware.php";
    require_once __DIR__ . '/../utils/helpers.php';
    header("Content-Type: application/json");


    $input = json_decode(file_get_contents('php://input'), true);
    

    // validate input helper function 
    function validateInput(array $fields){
        
        foreach($fields as $fieldName => $value){
            if(empty($value)){
                sendResponse(400, [
                    "status" => "error", 
                    "message" => "$fieldName is required to fill"
                ]); 
            }
        }
    }


    function sendResponse(int $statusCode, array $data){
        http_response_code($statusCode); 
        echo json_encode($data); 
        exit;     
    }

    // create the quiz
    // url:- /quiz/new_quiz 
    function newQuiz($conn, $input){
        try{
            $auth    = authmiddlware(); 
            $adminId = $auth['sub']; 
            $quizId      = generateUUID(); 
            $title       = $input['title']; 
            $description = $input['description']; 

            validateInput([
                'adminId' => $adminId, 
                'quizId' => $quizId, 
                'title' => $title, 
                'description' => $description
            ]); 

            $stmt = $conn->prepare('INSERT INTO quizzes (Id, creator_id, title, description) VALUES(?, ?, ?, ?)'); 
            $stmt->execute([ $quizId, $adminId, $title, $description]); 

            sendResponse(201, [
                "status" => "success", 
                "message" => 'quiz has been successfully created'
            ]); 
        }
        catch(Exception $e){
            sendResponse(500, [
                "status" => "error", 
                "message" => $e->getMessage()
            ]); 
        }
    }

    // update the quiz
    // url:- /quiz/:quiz_id/update_quiz
    function updateQuiz($conn, $input){
        
        $admin = roleCheck($conn); 
        $adminId = $admin['userId'];
        $id = $_GET["quiz_id"];
        $title = $input["title"];
        $description = $input["description"]; 

        validateInput(['admin_id' => $adminId, 'quiz_id' => $id]); 

        $updateFields = [];
        $params = [];

        if(isset($input['title'])){
            $updateFields[] = 'title = ?'; 
            $params[] = $title;  
        }

        if(isset($input['description'])){
            $updateFields[] = 'description = ?'; 
            $params[] = $description; 
        }

        if(empty($updateFields)){
            sendResponse(400, [
                "status" => "error", 
                "message" => 'No fileds to update provided'
            ]); 
        }

        $setClause = implode(', ',  $updateFields); 


        $params[] = $id; 
        $params[] = $adminId; 

        try{
            //update the quiz
            $stmt = $conn->prepare("UPDATE quizzes SET $setClause WHERE Id = ? AND creator_id = ?");
            $stmt->execute($params);

            //show the quiz
            $stmt = $conn->prepare('SELECT * FROM quizzes WHERE creator_id = ?');
            $stmt->execute([$adminId]);
            $updatedData = $stmt->fetch(PDO::FETCH_ASSOC); 

            sendResponse(200, [
                "status" => "success", 
                "message" => "quiz has been updated successfully", 
                "data" => $updatedData
            ]); 
        }
        catch(Exception $e){
            sendResponse(500, [
                "status" => "error", 
                "message" => $e->getMessage()
            ]); 
        }
    }

    // get the particular quiz by Id
    // url :- /quiz/get_quiz?quiz_id
    function getQuiz($conn){

        $id = $_GET['quiz_id']; 

        validateInput([
            "quiz_id" => $id
        ]);

        try{

            $stmt = $conn->prepare('SELECT * FROM quizzes WHERE Id = ?');
            $stmt->execute([$id]); 
            $quizData = $stmt->fetch(PDO::FETCH_ASSOC); 

            if(!is_array($quizData)){
                sendResponse(400, [
                    "status" => "error", 
                    "message" => "quiz data not found"
                ]); 
            }

            sendResponse(200, [
                "status" => "success", 
                "message" => "quiz data has been successfully found", 
                "data" => $quizData
            ]);  
        }
        catch(Exception $e){
            sendResponse(500, [
                "status" => "error", 
                "message" => $e->getMessage()
            ]); 
        }
    }


    // get all the quizzes of specific admin
    // url :- /quiz/get_all_quizzes
    function getAllQuizzes($conn){

        $admin = roleCheck($conn); 
        $adminId = $admin["userId"]; 

        validateInput([
            "admin id" => $adminId
        ]); 

        try{

            $stmt = $conn->prepare('SELECT * FROM quizzes WHERE creator_id = ?'); 
            $stmt->execute([$adminId]); 
            $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC); 

            if(!$quizzes){
                sendResponse(400, [
                    "status" => "error", 
                    "message" => "quiz data not found"
                ]); 
            }

            sendResponse(200, [
                "status" => "success", 
                "message" => "Got all the quizzes", 
                "data" => $quizzes
            ]);
        }
        catch(Exception $e){
            sendResponse(500, [
                "status" => "error", 
                "message" => $e->getMessage()
            ]);  
        }
    }

    // delete all the quizzes of specific admin
    // url :- /quiz/delete_all_quizzes
    function deleteAllQuizzes($conn){
        
        $admin = roleCheck($conn); 
        $adminId = $admin['userId']; 

        validateInput([
            "admin id" => $adminId
        ]); 

        try{

            $stmt = $conn->prepare('DELETE FROM quizzes WHERE creator_id = ?');
            $stmt->execute([$adminId]); 
            
            sendResponse(200, [
                "status" => "success",  
                "message" => "quizzes have been successfully deleted"
            ]);  
        }
        catch(Exception $e){
            sendResponse(500, [
                "status" => "error", 
                "message" => $e->getMessage()
            ]); 
        }
    }

    // delete the specific quiz of specific admin
    // url :- /quiz/:quiz_id/delete_quiz
    function deleteQuiz($conn){

        $id = $_GET['quiz_id']; 
        $admin = roleCheck($conn); 
        $admin_id = $admin['userId'];

        validateInput([
            "quiz id"=>$id, 
            "admin id" => $admin_id
        ]); 
        
        try{
            $stmt = $conn->prepare('DELETE FROM quizzes WHERE Id = ? AND creator_id = ?');
            $stmt->execute([$id, $admin_id]);

            sendResponse(200, [
                "status" => "success", 
                "message" => "Users data has been deleted Successfully"
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
