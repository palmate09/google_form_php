
<?php

    require_once __DIR__ . "/../config/dbconnection.php";
    require_once __DIR__ . "/../middleware/authmiddlware.php";
    require_once __DIR__ . '/../utils/helpers.php';
    header("Content-Type: application/json");


    $input = json_encode(file_get_contents('php://input'), true);
    

    // create the quiz 
    function newQuiz($conn, $input){

        $auth    = authmiddlware(); 
        $adminId = $auth['sub']; 

        $title       = $input['title']; 
        $description = $input['description']; 
        $quizId      = generateUUID(); 

        if(empty($title) || empty($description) || empty($adminId) || empty($quizId)){
            http_response_code(401); 
            echo json_encode([
                "status" => "error", 
                "message" => "title, description, adminId and quizid are required"
            ]);
            exit;  
        }

        try{
            $admin = roleCheck($conn);  

            $stmt = $conn->prepare('INSERT INTO quizzes (Id, creator_id, title, description) VALUES(?, ?, ?, ?)'); 
            $stmt->execute([ $quizId, $adminId, $title, $description]); 

            http_response_code(201); 
            echo json_encode([
                "status" => "success", 
                "creator_id" => $adminId, 
                "message" => "new quiz has been successfully created"
            ]);
        }
        catch(Exception $e){
            http_response_code(500); 
            echo json_encode([
                'status' => 'error', 
                'message' => $e->getMessage()
            ]); 
        }
    }


    // update the quiz
    function updateQuiz($conn, $input){
        
        $admin = roleCheck($conn); 
        $adminId = $admin['sub']; 
        $id = $_GET["Id"];
        $title = $input["title"];
        $description = $input["description"]; 


        if(!$id || (empty($title) && empty($description)) || !$adminId){
            http_response_code(401); 
            echo json_encode([
                "status" => "error",
                "message" => "id not found"
            ]);
            exit; 
        }

        try{

            $stmt = $conn->prepare('UPDATE FROM quizzes(title, description) WHERE Id = ? AND adminId = ?');
            $stmt->execute([$id, $adminId]);
            $updatedData = $stmt->fetch(PDO::FETCH_ASSOC);

            http_response_code(200); 
            echo json_encode([
                "status" => "success", 
                "message" => "quiz data has been successfully updated", 
                "updatedData" => $updatedData
            ]);
        }
        catch(Exception $e){
            http_response_code(500); 
            echo json_encode([
                "status" => "Error",
                "message" => $e.getMessage()
            ]);
            exit; 
        }
    }

    // get the particular quiz by Id
    function getQuiz($conn){

        $id = $_GET['Id']; 

        if(!$id){
            http_response_code(401); 
            echo json_encode([
                "status" => "error", 
                "message" => "id not found"
            ]);
            exit; 
        }

        try{

            $stmt = $conn->prepare('SELECT * FROM quizzes WHERE Id = ?');
            $stmt->execute([$id]); 
            $quizData = $stmt->fetch(PDO::FETCH_ASSOC); 

            if(!is_array($quizData)){
                http_response_code(400); 
                echo json_encode([
                    "status" => "error", 
                    "message" => "quiz data not found"
                ]); 
                exit; 
            }

            http_response_code(200); 
            echo json_encode([
                "status" => "error", 
                "message" => "quiz data has been successfully found", 
                "data" => $quizData
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

    // get all the quizzes of specific admin
    function getAllQuizzes($conn){

        $admin = roleCheck($conn); 
        $adminId = $admin["Id"]; 

        if(!$adminId){
            http_response_code(401); 
            echo json_encode([
                "status" => "error", 
                "message" => "adminId not found"
            ]); 
            exit; 
        }

        try{

            $stmt = $conn->prepare('SELECT * FROM quizzes WHERE creator_id = ?'); 
            $stmt->execute([$adminId]); 
            $quizzes = $stmt->fetch(PDO::FETCH_ASSOC); 

            if(!$quizzes){
                http_response_code(401); 
                echo json_encode([
                    "status" => "error", 
                    "message" => "quizzes not found"
                ]); 
                exit; 
            }

            http_response_code(200); 
            echo json_encode([
                "status" => "error", 
                "message" => "Got all the quizzes", 
                "data" => $quizzes
            ]); 
        }
        catch(Exception $e){
            http_response_code(500); 
            echo json_encode([
                "status" => "error", 
                "message" => $e->getMessage()
            ]); 
        }

    }

    // delete all the quizzes of specific admin
    function deleteAllQuizzes($conn){
        
        $admin = roleCheck($conn); 
        $adminId = $admin['sub']; 

        if(!$adminId){
            http_response_code(401); 
            echo json_encode([
                "status" => "error", 
                "message" => "admin Id not found"
            ]); 
            exit; 
        }

        try{

            $stmt = $conn->prepare('DELETE FROM quizzes WHERE creator_id = ?');
            $stmt->execute([$adminId]); 
            
            http_response_code(200); 
            echo json_encode([
                "status" => "success",  
                "message" => "quizzes have been successfully deleted"
            ]); 

        }
        catch(Exception $e){
            http_response_code(500); 
            echo json_encode([
                "status" => "error", 
                "message" => $e->getMessage()
            ]);
        }
    }


    // delete the specific quiz 
    function deleteQuiz($conn){

    }
?>
