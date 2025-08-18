
<?php

    require_once __DIR__ . "/../config/dbconnection.php";
    require_once __DIR__ . "/../controller/quiz.php";
    require_once __DIR__ . "/../middleware/authmiddlware.php";
    require_once __DIR__ . '/../utils/helpers.php';
    header("Content-Type: application/json");


    $input = json_encode(file_get_contents('php://input'), true); 

    // input handling and finding the error
    function input_handler($conn, $input, $requiredText = true){

        $quiz = check_quiz($conn);
        $quiz_id = $quiz['Id'] ?? null;
        $question_text = $input['question_text'] ?? null;
        $id = $_GET['question_id'] ?? null;
        
        $message = null; 
        switch(true){
            case !$quiz_id:
                $message = 'quiz id is required'; 
                break; 
            case $requiredText && !$question_text:
                $message = 'question text is required'; 
                break;
            case !$id & !$requiredText:
                $message = 'question id is required'; 
                break; 
        }

        if(!empty($message)){
            http_response_code(401); 
            echo json_encode([
                "status" => "error", 
                "message" => $message
            ]); 
            exit; 
        }
        
        return [
            'quiz_id'=>$quiz_id,
            'question_text'=>$question_text,
            'question_id' => $id
        ]; 
    }
    
    //create the question
    //url:- /question/new_question
    function create_question($conn, $input){

        $identifier = input_handler($conn, $input, true); 
        $quiz_id = $identifier['quiz_id']; 
        $question_text = $identifier['question_text']; 

        try{

            $stmt = $conn->prepare('INSERT INTO questions(quiz_id, question_text) VALUES (?, ?)');
            $stmt->execute([$quiz_id, $question_text]);  

            if($stmt->rowCount() === 0){
                http_response_code(401); 
                echo json_encode([
                    "status" => "error", 
                    "message" => "question is not created"
                ]); 
                exit; 
            }

            http_response_code(201); 
            echo json_encode([
                "status" => "success", 
                "message" => "question has been successfully created"
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

    // update the question
    // url:- /question/update_question 
    function update_question($conn, $input){

        $identifier = input_handler($conn, $input, true); 
        $quiz_id = $identifier['quiz_id']; 
        $question_text = $identifier['question_text']; 
        $id = $identifier['question_id']; 

    
        try{
            // update the question 
            $stmt = $conn->prepare('UPDATE questions SET question_text = ? WHERE Id = ? AND quiz_id = ?');
            $stmt->execute([$question_text, $id, $quiz_id]); 

            // show the updated question 
            $stmt = $conn->prepare('SELECT * FROM questions WHERE quiz_id = ? AND Id = ?'); 
            $stmt->execute([$quiz_id, $id]); 
            $question = $stmt->fetch(PDO::FETCH_ASSOC); 

            http_response_code(201); 
            echo json_encode([
                'status'  => 'success', 
                'message' => 'question data have been updated successfully!',
                "data" => $question
            ]);
            exit; 
        }
        catch(Exception $e){
            http_response_code(500); 
            echo json_encode([
                "status"  => "error", 
                "message" => $e->getMessage()
            ]); 
            exit; 
        }
    }

    // get the question
    // url:- /question/get_question 
    function get_question($conn){
        $identifier = input_handler($conn, $input, false);
        $quiz_id = $identifier['quiz_id'];  
        $id = $identifier['question_id'];

        try{

            $stmt = $conn->prepare('SELECT * FROM questions WHERE id = ? AND quiz_id = ?');
            $stmt->execute([$id, $quiz_id]); 
            $question = $stmt->fetch(PDO::FETCH_ASSOC); 

            if(!$question){
                http_response_code(401); 
                echo json_encode([
                    "status" => "error", 
                    "message" => "question not recieved"
                ]);
                exit; 
            }

            http_response_code(200); 
            echo json_encode([
                "status" => "success", 
                "message" => "question of this id:- $id has been successfully received", 
                "data" => $question
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

    // get all the questions
    // url:- /question/get_all_question
    function get_all_questions($conn){

        $identifier = input_handler($conn, $input, false); 
        $quiz_id = $identifier['quiz_id']; 

        try{

            $stmt = $conn->prepare('SELECT * FROM questions WHERE quiz_id = ?'); 
            $stmt->execute([$quiz_id]); 
            $questions_data = $stmt->fetchAll(PDO::FETCH_ASSOC); 

            if(!$questions_data){
                http_response_code(401); 
                echo json_encode([
                    "status"  => "error", 
                    "message" => "questions not recieved"
                ]);
                exit; 
            }

            http_response_code(200); 
            echo json_encode([
                "status" => "success", 
                "message" => "questions of this quiz id :- $quiz_id are recieved succesfully", 
                "data" => $questions_data
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

    //delete the particular question of the quiz
    // url:- /question/delete_question 
    function delete_question($conn){

        $identifier = input_handler($conn, $input, false); 
        $id = $identifier['question_id']; 
        $quiz_id = $identifier['quiz_id']; 

        try{

            $stmt = $conn->prepare('DELETE FROM questions WHERE quiz_id = ? AND id = ?');
            $stmt->execute([$quiz_id, $id]); 
            $question = $stmt->fetch(PDO::FETCH_ASSOC); 

            if($question){
                http_response_code(401); 
                echo json_encode([
                    "status" => "error", 
                    "message" => "question of id :- $id is not deleted"
                ]);
                exit; 
            }
            
            http_response_code(200); 
            echo json_encode([
                "status" => "success", 
                "message" => "question of id:- $id is deleted successfully"
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

    // delete all the questions
    // url:- /question/delete_all_question
    function delete_all_question($conn){

        $identifier = input_handler($conn, $input, false); 
        $quiz_id = $identifier['quiz_id']; 

        try{

            $stmt = $conn->prepare('DELETE FROM questions WHERE quiz_id = ?'); 
            $stmt->execute([$quiz_id]); 
            
            $stmt = $conn->prepare('SELECT * FROM questions WHERE quiz_id = ?');
            $stmt->execute([$quiz_id]);
            $question = $stmt->fetchAll(PDO::FETCH_ASSOC);  

            if(!empty($question)){
                http_response_code(401); 
                echo json_encode([
                    "status" => "error", 
                    "message" => "questions of this quiz id :- $quiz_id not deleted"
                ]); 
                exit; 
            }
            
            http_response_code(200); 
            echo json_encode([
                "status" => "success", 
                "message" => "question of this quiz id :- $quiz_id are deleted successfully" 
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