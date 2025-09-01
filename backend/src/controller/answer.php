
<?php

    require_once __DIR__ . "/../utils/helpers.php"; 
    require_once __DIR__ . "/../config/dbconnection.php"; 
    header("Content-Type: application/json"); 


    $input = json_encode(file_get_contents('php://input'), true); 


    // fetch the answer data 
    // this endpoint is not working 
    function get_answer($conn){

        $submission_id = check_submission($conn)['id']; 
        $answer_id = $_GET['answer_id'];

        validateInput([
            "answer id" => $answer_id
        ]); 
        
        try{
            $sql = "SELECT
                        ans.id AS answer_id, 
                        q.id AS question_id, q.question_text,
                        o.id AS option_id, o.option_text, o.is_correct, 
                        ans.option_id AS selected_option_id, 
                        ans.score,
                        ans.submission_id
                    FROM answers ans
                    LEFT JOIN questions q ON ans.question_id = q.id
                    LEFT JOIN options o ON o.question_id = q.id
                    WHERE ans.submission_id = ? AND ans.id = ?
                    ORDER BY q.id, o.id"; 

            $stmt = $conn->prepare($sql);
            $stmt->execute([$submission_id, $answer_id]); 
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC); 

            if(empty($data)){
                sendResponse(400, [
                    "status" => "error", 
                    "message" => "answer not received"
                ]); 
            }

            $result = []; 

            foreach($data as $row){  // ✅ fixed $rows -> $data
                $qid = $row['question_id']; 

                if(!isset($result[$qid])){
                    $result[$qid] = [
                        'answer id' => $row['answer_id'],
                        'question id' => $row['question_id'],
                        'question text' => $row['question_text'], 
                        'options' => [], 
                        'selected_option' => $row['selected_option_id'],
                        'score' => $row['score'],
                        'submission_id' => $row['submission_id']
                    ];
                }

                $result[$qid]['options'][] = [
                    'option_id' => $row['option_id'],
                    'option_text' => $row['option_text'],
                    'is_correct' => $row['is_correct']
                ]; 
            }

            $result = array_values($result); 

            sendResponse(200, [
                "status" => "success", 
                "message" => "answer received successfully", 
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

    
    // get all the answers of the particular submission with questions;
    // response: - question_id :- ?  and  answer_id = ?  then your score = ? 
    function get_all_answers($conn){

        // $identifier = answer_input_handler($conn, false); 
        $submission_id = check_submission($conn)['id'];  

        try{

            $stmt = $conn->prepare('SELECT * FROM answers WHERE submission_id = ?');
            $stmt->execute([$submission_id]);
            $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    
            if(empty($answers)){
                sendResponse(401, [
                    "status" => "error", 
                    "message" => "answers not found of this submission id:= $submission_id"
                ]);
            }

            $ans = [];

            for($i = 0; $i<count($answers); $i++){
                $data = [
                    "question_id" => $answers[$i]['question_id'], 
                    "score" => $answers[$i]['score']
                ];

                $ans[] = $data;  
            }

             
            sendResponse(200, [
                "status" => "success", 
                "message" => "all the answers received of this submission id :- $submission_id", 
                "data" => $ans
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
