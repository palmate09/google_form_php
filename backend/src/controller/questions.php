
<?php

    require_once __DIR__ . "/../config/dbconnection.php";
    require_once __DIR__ . "/../controller/quiz.php";
    require_once __DIR__ . "/../middleware/authmiddlware.php";
    require_once __DIR__ . '/../utils/helpers.php';
    header("Content-Type: application/json");


    $input = json_encode(file_get_contents('php://input'), true);
    

    //create the question
    //url:- /question/new_question
    function create_question($conn, $input){
        $quiz = check_quiz($conn); 
        $quiz_id = $quiz['Id']; 
        $question_text = $input['question_text']; 

        validateInput([
            "quiz id" => $quiz_id, 
            "question text" => $question_text
        ]); 

        try{

            $stmt = $conn->prepare('INSERT INTO questions(quiz_id, question_text) VALUES (?, ?)');
            $stmt->execute([$quiz_id, $question_text]);  

            if($stmt->rowCount() === 0){
                sendResponse(400, [
                    "status" => "error", 
                    "message" => "question is not created"
                ]);  
            }

            sendResponse(201, [
                "status" => "success", 
                "message" => "question has been successfully created"
            ]);  
        }
        catch(Exception $e){
            sendResponse(500, [
                "status" => "error", 
                "message" => $e->getMessage()
            ]);  
        }
    }

    // update the question
    // url:- /question/update_question 
    function update_question($conn, $input){

        $quiz = check_quiz($conn); 
        $quiz_id = $quiz['Id'];  
        $question_text = $input['question_text']; 
        $id = $_GET['question_id'];
        
        validateInput([
            "quiz id" => $quiz_id, 
            "question text" => $question_text, 
            "question id" => $id 
        ]); 
    
        try{
            // update the question 
            $stmt = $conn->prepare('UPDATE questions SET question_text = ? WHERE Id = ? AND quiz_id = ?');
            $stmt->execute([$question_text, $id, $quiz_id]); 

            // show the updated question 
            $stmt = $conn->prepare('SELECT * FROM questions WHERE quiz_id = ? AND Id = ?'); 
            $stmt->execute([$quiz_id, $id]); 
            $question = $stmt->fetch(PDO::FETCH_ASSOC); 

            sendResponse(200, [
                'status'  => 'success', 
                'message' => 'question data have been updated successfully!',
                "data" => $question
            ]);  
        }
        catch(Exception $e){
            sendResponse(500, [
                "status"  => "error", 
                "message" => $e->getMessage()
            ]); 
        }
    }

    // get the question
    // url:- /question/get_question 
    function get_question($conn){
        $quiz = check_quiz($conn); 
        $quiz_id = $quiz['Id'];  
        $id = $_GET['question_id'];

        validateInput([
            "quiz id" => $quiz_id, 
            "question id" => $id
        ]); 

        try{

            $stmt = $conn->prepare('SELECT * FROM questions WHERE id = ? AND quiz_id = ?');
            $stmt->execute([$id, $quiz_id]); 
            $question = $stmt->fetch(PDO::FETCH_ASSOC); 

            sendResponse(200, [
                "status" => "success", 
                "message" => "question of this id:- $id has been successfully received", 
                "data" => $question
            ]);  
        }
        catch(Exception $e){
            sendResponse(500, [
                "status" => "error", 
                "message" => $e->getMessage()
            ]);  
        }
    }

    // get all the questions
    // url:- /question/get_all_question
    function get_all_questions($conn){
        $quiz = check_quiz($conn); 
        $quiz_id = $quiz['Id'];
        
        validateInput([
            "quiz id" => $quiz_id
        ]); 

        try{
            $stmt = $conn->prepare('SELECT * FROM questions WHERE quiz_id = ?'); 
            $stmt->execute([$quiz_id]); 
            $questions_data = $stmt->fetchAll(PDO::FETCH_ASSOC); 

            sendResponse(200, [
                "status" => "success", 
                "message" => "questions of this quiz id :- $quiz_id are recieved succesfully", 
                "data" => $questions_data
            ]);  
        }
        catch(Exception $e){
            sendResponse(500, [
                "status" => "error", 
                "message" => $e->getMessage()
            ]);  
        }
    }

    //delete the particular question of the quiz
    // url:- /question/delete_question 
    function delete_question($conn){
        $quiz = check_quiz($conn);  
        $quiz_id = $quiz['Id']; 
        $id = $_GET['question_id']; 

        validateInput([
            "quiz id"=>$quiz_id, 
            "question id" => $id
        ]); 

        try{

            $stmt = $conn->prepare('DELETE FROM questions WHERE quiz_id = ? AND id = ?');
            $stmt->execute([$quiz_id, $id]); 
            $question = $stmt->fetch(PDO::FETCH_ASSOC); 

            if($question){
                sendResponse(401, [
                    "status" => "error", 
                    "message" => "question of id :- $id is not deleted"
                ]);  
            }
            
            sendResponse(200, [
                "status" => "success", 
                "message" => "question of id:- $id is deleted successfully"
            ]); 
        }
        catch(Exception $e){
            sendResponse(500, [
                "status" => "error", 
                "message" => $e->getMessage()
            ]);  
        }
    }

    // delete all the questions
    // url:- /question/delete_all_question
    function delete_all_question($conn){
        $quiz = check_quiz($conn); 
        $quiz_id = $quiz['Id'];
        
        validateInput([
            "quiz id" => $quiz_id
        ]);

        try{

            $stmt = $conn->prepare('DELETE FROM questions WHERE quiz_id = ?'); 
            $stmt->execute([$quiz_id]); 
            
            $stmt = $conn->prepare('SELECT * FROM questions WHERE quiz_id = ?');
            $stmt->execute([$quiz_id]);
            $question = $stmt->fetchAll(PDO::FETCH_ASSOC);  
            
            sendResponse(200, [
                "status" => "success", 
                "message" => "question of this quiz id :- $quiz_id are deleted successfully" 
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