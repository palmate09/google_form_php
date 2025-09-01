<?php

    require_once __DIR__ . '/../controller/questions.php';
    require_once __DIR__ . '/../config/dbconnection.php'; 
    require_once __DIR__ . '/../utils/helpers.php'; 
    header("Content-Type: application/json");

    $input = json_decode(file_get_contents('php://input'), true); 

    // create the options for the particular question by admin only 
    function add_option($conn, $input){
        $question = check_question($conn);  
        $question_id = $question['Id']; 
        $quiz_id = $question['quiz_id']; 
        $option_text = $input['option_text']; 
        $is_correct  = $input['is_correct'] ?? 0;
        $is_correct = filter_var($is_correct, FILTER_VALIDATE_BOOLEAN) ? 1:0; 


        validateInput([
            "question id" => $question_id, 
            "option text" => $option_text,  
            "quiz id" => $quiz_id
        ]); 
        
        try{
            // create the option for the question 
            $stmt = $conn->prepare('INSERT INTO options(question_id, quiz_id, option_text, is_correct) VALUES (?, ?, ?, ?)');
            $stmt->execute([$question_id, $quiz_id, $option_text, $is_correct]); 

            sendResponse(201, [
                "status" => "success", 
                "message" => "option has been created successfully" 
            ]);  
        }
        catch(Exception $e){
            sendResponse(500, [
                "status" => "error", 
                "message" => $e->getMessage()
            ]); 
        }
    }

    // update the option for the particular question by admin only
    function update_option($conn, $input){

        $question       = check_question($conn);  
        $question_id    =  $question['Id'];
        $quiz_id        =  $question['quiz_id'];  
        $option_text    =  $input['option_text']; 
        $is_correct     =  $input['is_correct'];  
        $option_id      =  $_GET['option_id']; 

        validateInput([
            "question id" => $question_id, 
            "quiz id" => $quiz_id,  
            "option id" => $option_id, 
        ]); 

        $updatedFields = [];
        $params = []; 

        if(isset($input['option_text'])){
            $updatedFields[] = 'option_text = ?'; 
            $params[] = $option_text; 
        }

        if(isset($input['is_correct'])){
            $updatedFields[] = 'is_correct = ?'; 
            $params[] = $is_correct; 
        }

        if(empty($updatedFields)){
            sendResponse(400, [
                "status" => "error", 
                "message" => "NO fields to update provided"
            ]); 
        }

        $setClause = implode(', ', $updatedFields); 

        $params[] =  $question_id;
        $params[] = $option_id;
        $params[] = $quiz_id;  


        try{
            // update the option
            $stmt = $conn->prepare("UPDATE options SET $setClause WHERE question_id = ? AND Id = ? AND quiz_id = ?");
            $stmt->execute($params);
            
            // show the updated option 
            $stmt = $conn->prepare('SELECT * FROM options WHERE Id = ?');
            $stmt->execute([$option_id]);
            $updatedOption = $stmt->fetch(PDO::FETCH_ASSOC); 

            sendResponse(200, [
                "status" => "success", 
                "message" => "option has been successfully updated", 
                "data" => $updatedOption
            ]);  
        }
        catch(Exception $e){
            sendResponse(500, [
                "status" => "error", 
                "message" => $e->getMessage()
            ]); 
        }
    }

    // get the options for the particular question by anyone
    function get_all_options($conn, $input){

        $question_id = $_GET['question_id'];
        $quiz_id = $_GET['quiz_id']; 

        validateInput([
            "question id" => $question_id, 
            "quiz id" => $quiz_id
        ]); 

        try{

            $stmt = $conn->prepare('SELECT * FROM options WHERE question_id = ? AND quiz_id = ?'); 
            $stmt->execute([$question_id, $quiz_id]); 
            $options_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if(empty($options_data) || $options_data === null){
                sendResponse(400, [
                    "status" => "error", 
                    "message" => "options not received"
                ]); 
            }

            sendResponse(200, [
                "status" => "success", 
                "message" => "options have successfully received",
                "data" => $options_data
            ]);  
        }
        catch(Exception $e){
            sendResponse(500, [
                "status" => "error", 
                "message" => $e->getMessage()
            ]); 
        }
    }

    // get the particular option 
    function get_option($conn, $input){
        $quiz_id = $_GET['quiz_id']; 
        $question_id = $_GET['question_id'];
        $option_id = $_GET['option_id'];
        
        validateInput([
            "question id" => $question_id, 
            "option id" => $option_id
        ]); 
        
        try{

            $stmt = $conn->prepare('SELECT * FROM options WHERE quiz_id = ? AND question_id = ? AND Id = ?');
            $stmt->execute([$quiz_id,$question_id, $option_id]); 
            $option_data = $stmt->fetch(PDO::FETCH_ASSOC);

            if(empty($option_data)){
                sendResponse(401, [
                    "status" => "error",
                    "message" => "option not found" 
                ]); 
            }

            sendResponse(200, [
                "status" => "success", 
                "message" => "option found successfully", 
                "data" => $option_data
            ]);  
        }
        catch(Exception $e){
            sendResponse(500, [
                "status" => "error", 
                "message" => $e->getMessage()
            ]); 
        }
    }
    
    // delete the option for the particular question by admin only
    function delete_option($conn){
        $question = check_question($conn);  
        $question_id = $question['Id']; 
        $option_id = $_GET['option_id']; 

        validateInput([
            "option id" => $option_id
        ]); 

        try{
            // delete the option
            $stmt = $conn->prepare('DELETE FROM options WHERE Id = ? AND question_id = ?'); 
            $stmt->execute([$option_id, $question_id]); 
            
            // show the option 
            $stmt = $conn->prepare('SELECT * FROM options WHERE Id = ? AND question_id = ?');
            $stmt->execute([$option_id, $question_id]); 
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            sendResponse(200, [
                "status" => "success", 
                "message" => "option has been deleted"
            ]); 
        }
        catch(Exception $e){
            sendResponse(500, [
                "status" => "error", 
                "message" => $e->getMessage()
            ]); 
        }
    }

    // delete all the options of the particualr question
    function delete_all_options($conn){

        $question = check_question($conn);  
        $question_id = $question['Id']; 
        
        try{
            // delete all the options 
            $stmt = $conn->prepare('DELETE FROM options WHERE question_id = ?'); 
            $stmt->execute([$question_id]); 

            // check weather it is deleted or not
            $stmt = $conn->prepare('SELECT * FROM options WHERE question_id = ?');
            $stmt->execute([$question_id]); 
            $options = $stmt->fetch(PDO::FETCH_ASSOC);


            sendResponse(200, [
                "status" => "success", 
                "message" => "options of the question id:- $question_id is deleted successfully!"
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
