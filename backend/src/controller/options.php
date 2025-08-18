<?php

    require_once __DIR__ . '/../controller/questions.php';
    require_once __DIR__ . '/../config/dbconnection.php'; 
    require_once __DIR__ . '/../utils/helpers.php'; 
    header("Content-Type: application/json");

    $input = json_encode(file_get_contents('php://input'), true); 

    function option_input_handler($conn, $input, $requiredField = true){

        $admin = roleCheck($conn);
        $id = $_GET['option_id'] ?? null;  
        $question_data = check_question($conn);
        $question_id = $question_data['Id'];   
        $option_text = $input['option_text'] ?? null; 
        $is_correct = isset($input['is_correct']) ? filter_var($input['is_correct'], FILTER_VALIDATE_BOOLEAN): null; 

        $message = null; 
        switch(true){
            case !$is_correct && !$question_id && !$option_text && !$option_id:
                $message = 'option choice and question id and option text is required to fill'; 
                break;  
            case !$question_id:
                $message = 'question id is not recieved'; 
                break; 
            case $requiredField && !$option_text:
                $message = 'option text is required to fill'; 
                break; 
            case $requiredField && !isset($input['is_correct']): 
                $message = 'option choice is required to fill';
                break;
            case !$option_id && !$requiredField:
                $message = 'option id is not recieved'; 
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
            'question_id' => $question_id,
            'option_text' => $option_text, 
            'option_choice' => $is_correct, 
            'option_id' => $id
        ]; 
    }

    // create the options for the particular question by admin only 
    function add_option($conn, $input){

        $identifier  = option_input_handler($conn, $input, true); 
        $question_id = $identifier['question_id']; 
        $option_text = $identifier['option_text']; 
        $is_correct  =  $identifier['option_choice'];
        

        try{

            $stmt = $conn->prepare('INSERT INTO options(question_id, option_text, is_correct) VALUES (?, ?, ?)');
            $stmt->execute([$question_id, $option_text, $is_correct]); 

            http_response_code(201); 
            echo json_encode([
                "status" => "success", 
                "message" => "option has been created successfully" 
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

    // update the option for the particular question by admin only
    function update_option($conn, $input){

        $identifier  =  option_input_handler($conn, $input, true); 
        $question_id =  $identifier['question_id']; 
        $option_text =  $identifier['option_text']; 
        $option_choice  =  $identifier['option_choice'];  
        $option_id   =  $identifier['option_id']; 

        if(!$option_text && !$option_choice){
            http_response_code(401); 
            echo json_encode([
                "status" => "error", 
                "message" => "option text and option choice is required"
            ]);
            exit;
        }

        try{
            // update the option
            $stmt = $conn->prepare('UPDATE options SET option_text = ? , is_correct = ? WHERE question_id = ? AND Id = ?');
            $stmt->execute([$option_text, $option_choice, $question_id, $option_id]);
            
            // show the updated option 
            $stmt = $conn->prepare('SELECT * FROM options WHERE Id = ?, question_id = ?');
            $stmt->execute([$option_id, $question_id]);
            $updatedOption = $stmt->fetch(PDO::FETCH_ASSOC); 

            if(!$updatedOption){
                http_response_code(401); 
                echo json_encode([
                    "status" => "error", 
                    "message" => "option has not updated"
                ]); 
                exit; 
            }

            http_response_code(200); 
            echo json_encode([
                "status" => "success", 
                "message" => "option has been successfully updated"
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

    // get the options for the particular question by anyone
    function get_all_options($conn){

        $identifier = option_input_handler($conn, $input, false); 
        $question_id = $identifier['question_id'];

        try{

            $stmt = $conn->prepare('SELECT * FROM options WHERE question_id = ?'); 
            $stmt->execute([$question_id]); 
            $options_data = $stmt->fetchAll(PDO::FETCH_ASSOC); 

            if(!$options_data){
                http_response_code(401); 
                echo json_encode([
                    "status" => "error", 
                    "message" => "options not received"
                ]);
                exit; 
            }

            http_response_code(200); 
            echo json_encode([
                "status" => "success", 
                "message" => "options have successfully received"
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

    // get the particular option 
    function get_option($conn){

        $identifier = option_input_handler($conn, $input, false);
        $question_id = $identifier['question_id']; 
        $option_id = $identifier['option_id']; 
        
        try{

            $stmt = $conn->prepare('SELECT * FROM options WHERE question_id = ? , Id = ?');
            $stmt->execute([$question_id, $option_id]); 
            $option_data = $stmt->fetch(PDO::FETCH_ASSOC);

            if(empty($option_data)){
                http_response_code(401); 
                echo json_encode([
                    "status" => "error",
                    "message" => "option not found" 
                ]); 
                exit; 
            }

            http_response_code(200); 
            echo json_encode([
                "status" => "success", 
                "message" => "option found successfully", 
                "data" => $option_data
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
    
    // delete the option for the particular question by admin only
    function delete_option($conn){

        $identifier = option_input_handler($conn, $input, false); 
        $option_id = $identifier['option_id']; 
        $question_id = $identifier['question_id']; 

        try{

            $stmt = $conn->prepare('DELETE FROM options WHERE Id = ? , question_id = ?'); 
            $stmt->execute([$option_id, $question_id]); 
            $options_data = $stmt->fetch(PDO::FETCH_ASSOC); 

            if($option_data){
                http_response_code(401); 
                echo json_encode([
                    "status" => "error", 
                    "message" => "option has not deleted"
                ]);
                exit; 
            }
            
            http_response_code(200); 
            echo json_encode([
                "status" => "success", 
                "message" => "option has been deleted"
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

    // delete all the options of the particualr question
    function delete_all_options($conn){

        $identifier = option_input_handler($conn, $input, false); 
        $question_id = $identifier['question_id']; 
        
        try{
            // delete all the options 
            $stmt = $conn->prepare('DELETE FROM options WHERE question_id = ?'); 
            $stmt->execute([$question_id]); 

            // check weather it is deleted or not
            if($stmt->rowCount() !== 0){
                http_response_code(401); 
                echo json_encode([
                    "status" => "error", 
                    "message" => "options of the question id :- $question_id not deleted yet"
                ]);
                exit;
            }

            http_response_code(200); 
            echo json_encode([
                "status" => "success", 
                "message" => "options of the question id:- $question_id is deleted successfully!"
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
