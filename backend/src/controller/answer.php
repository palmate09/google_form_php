
<?php

    require_once __DIR__ . "/../utils/helpers.php"; 
    require_once __DIR__ . "/../config/dbconnection.php"; 
    header("Content-Type: application/json"); 


    $input = json_encode(file_get_contents('php://input'), true); 

    function input_handler($conn, $input){

        $question_id = check_question($conn); 
        $option_id = check_option($conn); 
        $submission_id = $_GET['submission_id']; 

    }


?>
