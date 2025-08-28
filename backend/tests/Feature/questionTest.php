<?php

    require_once __DIR__ . '/../../vendor/autoload.php';
    // tests/AuthApiTest.php

    use GuzzleHttp\Client;
    use GuzzleHttp\Exception\RequestException;

    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();

    // --- Test Setup ---

    function getClient(){
        return new Client(['base_uri' => 'http://localhost:8000', 'http_errors' => false]);
    }

    beforeEach(function () {
        $host = '127.0.0.1';
        $db   = 'mydb';
        $user = $_ENV['DB_USER'];
        $pass = $_ENV['DB_PASS'];
        

        try {
            $conn = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);

            $conn->exec("SET FOREIGN_KEY_CHECKS = 0;");
            $conn->exec("TRUNCATE TABLE users;"); 
            $conn->exec("TRUNCATE TABLE quizzes;");
            $conn->exec("SET FOREIGN_KEY_CHECKS = 1;");

        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    });

    function createAndLoginAdmin(){
        $client = getClient(); 
        $email = 'admin_'.uniqid().'@example.com'; 

        $client->post('/register', ['json' => [
            'username' => 'testadmin'. uniqid(), 
            'email' => $email, 
            'password' => 'password123', 
            'role' => 'admin'
        ]]); 

        $loginResponse = $client->post('/login', ['json' => [
            'email' => $email, 
            'password' => 'password123'
        ]]); 

        $loginBody = json_decode($loginResponse->getBody()->getContents(), true); 
        $tokenParts = explode('.', $loginBody['token']);
        $payload = json_decode(base64_decode($tokenParts[1]), true);
        
        return[
            'token' => $loginBody['token'], 
            'id' => $payload['sub']
        ];
    }


    //helper function to get the quiz id
    function quizId($client, $admin){
        $quizData = [ 
            'title' => 'My First Quiz', 
            'description' => 'A Quiz about general knowledge.'
        ]; 

        $client->post('/quiz/new_quiz', [
            'headers' => ['Authorization' => 'Bearer '. $admin['token']],
            'json' => $quizData
        ]);

        $getAllQuizzes = $client->get('/quiz/get_all_quizzes', [
            "headers" => ["Authorization" => "Bearer ".$admin['token']] 
        ]); 

        $response = json_decode($getAllQuizzes->getBody()->getContents(), true); 
        $quiz_id = $response['data'][0]['Id'];
        
        return $quiz_id;
    }

    // helper function to get the question id 
    function questionId($client, $admin){
        $quiz_id = quizId($client, $admin);
        
        $client->post("/question/new_question?quiz_id=$quiz_id", [
            "headers" => ["Authorization" => "Bearer ".$admin['token']], 
            "json" => ["question_text" => 'A temporary question to get and Id']
        ]); 

        $getAllQuestions = $client->get("/question/get_all_question?quiz_id=$quiz_id", [
            "headers" => ["Authorization" => "Bearer ".$admin['token']]
        ]);

        $response = json_decode($getAllQuestions->getBody()->getContents(), true); 
        $question_id = $response['data'][0]['Id']; 

        return $question_id;
    }

    //--test to create the question--
    test('POST /question/new_question to create the question', function(){
        $client = getClient(); 
        $admin = createAndLoginAdmin(); 
        $quiz_id = quizId($client, $admin); 
        
        $question_data = [
            "question_text" => 'this is very important question'
        ];
        
        $response = $client->post("/question/new_question?quiz_id=$quiz_id", [
            "headers" => ["Authorization" => "Bearer ".$admin['token']],
            "json" => $question_data 
        ]); 

        expect($response->getStatusCode())->toBe(201);
        $body = json_decode($response->getBody()->getContents(), true); 
        expect($body['status'])->toBe('success');  
    }); 

    //--test to update the question--
    test('PUT /question/update_question to create the question', function(){
        $client = getClient(); 
        $admin = createAndLoginAdmin();
        $quiz_id = quizId($client, $admin); 
        $question_id = questionId($client, $admin); 
        
        $updatedData = [
            "question_text" => "this is my first question"
        ]; 

        $response = $client->put("/question/update_question?quiz_id=$quiz_id&question_id=$question_id", [
            'headers' => ["Authorization" => "Bearer ".$admin['token']], 
            'json' => $updatedData
        ]); 

        expect($response->getStatusCode())->toBe(200); 
        $body = json_decode($response->getBody()->getContents(), true); 
        expect($body['status'])->toBe('success'); 
    }); 

    // ---tests to get the question---
    test('GET /question/get_question fetch the question of specific quiz', function(){

        $client = getClient(); 
        $admin = createAndLoginAdmin(); 
        $quiz_id = quizId($client, $admin); 
        $question_id = questionId($client, $admin); 

        $response = $client->get("/question/get_question?quiz_id=$quiz_id&question_id=$question_id", [
            "headers" => ["Authorization" => "Bearer ".$admin['token']]
        ]); 

        expect($response->getStatusCode())->toBe(200); 
        $body = json_decode($response->getBody()->getContents(), true); 
        expect($body['status'])->toBe('success'); 
    }); 

    // --tests to get all the questions--- 
    test('GET /question/get_all_question fetch all the questions of specific quiz', function(){
        $client = getClient(); 
        $admin  = createAndLoginAdmin(); 
        $quiz_id = quizId($client, $admin); 

        $response = $client->get("/question/get_all_question?quiz_id=$quiz_id", [
            "headers" => ["Authorization" => "Bearer ".$admin['token']]
        ]); 

        expect($response->getStatusCode())->toBe(200); 
        $body = json_decode($response->getBody()->getContents(), true);
        expect($body['status'])->toBe('success'); 
    }); 

    // --tests to delete all the questions-- 
    test('DELETE /question/delete_all_question delete all the question of specific quiz', function(){
        $client = getClient(); 
        $admin = createAndLoginAdmin(); 
        $quiz_id = quizId($client, $admin); 
        
        $response = $client->delete("/question/delete_all_question?quiz_id=$quiz_id", [
            "headers" => ["Authorization" => "Bearer ".$admin['token']]
        ]);
        
        expect($response->getStatusCode())->toBe(200); 
        $body = json_decode($response->getBody()->getContents(), true); 
        expect($body['status'])->toBe('success'); 
    });
    
    // ---tests to delete the question--
    test('DELETE /question/delete_question delete the specific question of quiz', function(){
        $client = getClient(); 
        $admin = createAndLoginAdmin(); 
        $quiz_id = quizId($client, $admin);
        $question_id = questionId($client, $admin); 

        $response = $client->delete("/question/delete_question?quiz_id=$quiz_id&question_id=$question_id", [
            "headers" => ["Authorization" => "Bearer ".$admin['token']]
        ]); 

        expect($response->getStatusCode())->toBe(200); 
        $body = json_decode($response->getBody()->getContents(), true); 
        expect($body['status'])->toBe('success'); 
    }); 
