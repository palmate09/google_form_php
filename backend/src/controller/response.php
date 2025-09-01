<?php

    require_once __DIR__ . "/../config/dbconnection.php";
    require_once __DIR__ . "/../middleware/authmiddlware.php";
    require_once __DIR__ . "/../controller/quiz.php"; 
    require_once __DIR__ . '/../utils/helpers.php';
    header("Content-Type: application/json");

    $input = json_decode(file_get_contents('php://input'), true);

    
    // show the complete quiz with certain questions and options for it
    // /quiz/showQuiz/:quizId
    function showQuiz($conn, $input){

        $quiz_id = $_GET['quiz_id']; 

        validateInput([
            "quiz id" => $quiz_id
        ]);

        try{

            $sql = "SELECT 
                        q.id AS quiz_id, q.title, 
                        u.userId AS creator_id, u.username, 
                        ques.id AS question_id, ques.question_text,
                        o.id AS option_id, o.option_text, o.is_correct
                    FROM quizzes q
                    JOIN users u ON q.creator_id = u.userId
                    LEFT JOIN questions ques ON ques.quiz_id = q.id
                    LEFT JOIN options o ON o.question_id = ques.id
                    WHERE q.id = ?;"; 

            $stmt = $conn->prepare($sql);
            $stmt->execute([$quiz_id]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC); 

            if(empty($data)){
                sendResponse(404, [
                    "status" => "error", 
                    "message" => "quiz not found"
                ]); 
                return; 
            }

            $quiz = [
                "quiz_id" => $data[0]["quiz_id"], 
                "title"   => $data[0]["title"], 
                "creator" => [
                    "creator_id" => $data[0]["creator_id"], 
                    "username"   => $data[0]["username"]
                ], 
                "question" => []
            ]; 

            $questions = []; 
            foreach($data as $d){
                $qId = $d["question_id"]; 
                if($qId && !isset($questions[$qId])){
                    $questions[$qId] = [
                        "question_id"   => $qId, 
                        "question_text" => $d["question_text"], 
                        "options"       => []    
                    ]; 
                }

                if($d["option_id"]){
                    $questions[$qId]["options"][] = [
                        "option_id"   => $d["option_id"], 
                        "option_text" => $d["option_text"], 
                        "is_correct"  => (bool) $d["is_correct"]
                    ]; 
                }
            }

            $quiz["question"] = array_values($questions); 

            sendResponse(200, [
                "status" => "success", 
                "data" => $quiz
            ]); 
        }
        catch(Exception $e){
            sendResponse(500, [
                "status" => "error", 
                "message" => $e->getMessage() 
            ]); 
        }
    }

    // add the response for the question of the quiz by the user only 
    // /response/addResponse/:quizId/:questionId/
    // here i am giving the option_id as the input so then i will check weather it is correct or not
    function addResponse($conn, $input){
        $quiz_id = check_quiz_for_user($conn)['Id']; 
        $user_id = check_user($conn)['userId'];  
        $optionIds = $input['optionIds']; 

        validateInput([
            "options" => $optionIds, 
            "quiz id" => $quiz_id
        ]);

        try{

            // check whether the submission is created or not 
            $stmt = $conn->prepare("SELECT id FROM submissions WHERE quiz_id = ? AND user_id = ?");
            $stmt->execute([$quiz_id, $user_id]);
            $existingSubmission = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingSubmission) {
                sendResponse(400, [
                    "status" => "error",
                    "message" => "You have already submitted this quiz"
                ]);
                return;
            }

            // create the submission 
            $submissionId = generateUUID(); 
            $stmt = $conn->prepare('INSERT INTO submissions(id, quiz_id, user_id) VALUES(?,?,?)'); 
            $stmt->execute([$submissionId, $quiz_id, $user_id]); 
            

            $totalScore = 0; 
            $answerData = []; 

            foreach($optionIds as $optionId){
                $stmt = $conn->prepare('SELECT question_id, is_correct FROM options WHERE id = ?'); 
                $stmt->execute([$optionId]); 
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if(!$row) continue; 

                $questionId = $row['question_id'];
                $score = $row['is_correct'] ? 1 : -1; 
                $totalScore += $score;
                
                $answerData[] = [
                    'submission_id' => $submissionId, 
                    'question_id' => $questionId, 
                    'option_id' => $optionId, 
                    'score' => $score
                ]; 
            }


            // bulk insert answers; 
            $stmtInsert = $conn->prepare("
                INSERT INTO answers(question_id, option_id, score, submission_id) VALUES (?,?,?,?)
            ");

            foreach($answerData as $a){
                $stmtInsert->execute([$a["question_id"], $a["option_id"], $a["score"],$a["submission_id"]]); 
            }

            // insert total score in the result table
            $resultId = generateUUID(); 
            $stmt = $conn->prepare("
                INSERT INTO result(id, total_score, user_id, quiz_id, submission_id) VALUES(?,?,?,?,?)
            ");
            $stmt->execute([$resultId, $totalScore, $user_id, $quiz_id, $submissionId]); 

            sendResponse(201, [
                "status" => "success", 
                "message" => "Quiz has been successfully submitted"    
            ]); 
        }
        catch(Exception $e){
            sendResponse(500, [
                "status" => "error", 
                "message" => $e->getMessage()
            ]); 
        }
    }

    // /response/showResponse
    // show the response
    function showResponse($conn){
        $quiz_id = check_quiz_for_user($conn)['Id']; 

        try{
            // get submission id from quiz id 
            $sql = "SELECT
                        q.id AS quiz_id,
                        q.title,
                        s.id AS submission_id,
                        r.id AS result_id,
                        r.total_score,
                        ans.id AS answer_id,
                        ans.score
                    FROM quizzes q
                    LEFT JOIN submissions s ON s.quiz_id = q.id
                    LEFT JOIN result r ON r.submission_id = s.id
                    LEFT JOIN answers ans ON ans.submission_id = s.id  
                    WHERE q.id = ? ";

            
            $stmt = $conn->prepare($sql); 
            $stmt->execute([$quiz_id]); 
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC); 

            if(empty($data)){
                sendResponse(400, [
                    "status" => "Error", 
                    "message" => "quiz not found"
                ]); 
                return ; 
            }
            
            $quiz = [
                "quiz_id" => $data[0]["quiz_id"], 
                "title" => $data[0]["title"], 
                "submission_id" => $data[0]["submission_id"],
                "result" => [
                    "result_id" => $data[0]["result_id"], 
                    "total_score" => $data[0]["total_score"]
                ],
                "answers" => []
            ];

            $answers = [];
            foreach ($data as $row) {
                $answers[] = [
                    "answer_id" => $row["answer_id"],
                    "score"     => $row["score"]
                ];
            }

            $quiz['answers'] = array_values($answers); 

            sendResponse(200, [
                "status" => "success", 
                "data" => $quiz 
            ]); 
        }
        catch(Exception $e){
            sendResponse(500, [
                "status" => "error", 
                "message" => $e->getMessage()
            ]);
        }
    }

    





