<?php


    // tests/AuthApiTest.php

    use GuzzleHttp\Client;
    use GuzzleHttp\Exception\RequestException;

    // --- Test Setup ---

    function getClient()
    {
        return new Client(['base_uri' => 'http://localhost:8000', 'http_errors' => false]);
    }

    beforeEach(function () {
        $host = '127.0.0.1';
        $db   = 'mydb';
        $user = 'root';
        $pass = '@Shubham09';
        

        try {
            $conn = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);

            $conn->exec("SET FOREIGN_KEY_CHECKS = 0;");
            $conn->exec("TRUNCATE TABLE users;"); 
            $conn->exec("TRUNCATE TABLE quizzes;");
            // $conn->exec("TRUNCATE TABLE password_resets;");
            $conn->exec("SET FOREIGN_KEY_CHECKS = 1;");

        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    });

    // helper function to create the admin 
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

    // helper function to create the quiz 
    function createQuiz(){
        $client = getClient(); 
        $admin = createAndLoginAdmin(); 

        $quizData = [ 
            'title' => 'My First Quiz', 
            'description' => 'A Quiz about general knowledge.'
        ]; 

        $response = $client->post('/quiz/new_quiz', [
            'headers' => ['Authorization' => 'Bearer '. $admin['token']],
            'json' => $quizData
        ]);

        return [
            "client" => $client, 
            "admin" => $admin,
            "response" => $response
        ]; 
    }

    // helper function to get the quiz id
    function getQuizId($client, $admin){
        $getAllQuizzes = $client->get('/quiz/get_all_quizzes', [
            "headers" => ['Authorization' => 'Bearer '.$admin['token']]
        ]); 

        $response = json_decode($getAllQuizzes->getBody()->getContents(), true); 
        $quiz_id = $response['data'][0]['Id'];
        
        return $quiz_id; 
    }

    // --- post quiz tests --- 
    test('POST /quiz/new_quiz creates a quiz successfully', function(){

        $response = createQuiz()['response']; 

        expect($response->getStatusCode())->toBe(201); 
    });

    test('POST /quiz/new_quiz fails for non-admin users or invalid token', function(){

        $client = getClient(); 
        $quizData = ['title' => 'Test Quiz', 'description' => '...']; 

        $response = $client->post('/quiz/new_quiz', ['json' => $quizData]); 
        expect($response->getStatusCode())->toBe(401);
    });

    //--- get quiz tests---
    test('GET /quiz/get_quiz fetch a specific quiz', function(){

        $quiz_response = createQuiz(); 
        $client = $quiz_response['client']; 
        $admin = $quiz_response['admin'];
        $quiz_id = getQuizId($client, $admin);  

        $response = $client->get("/quiz/get_quiz?quiz_id=$quiz_id"); 

        expect($response->getStatusCode())->toBe(200); 
        $body = json_decode($response->getBody()->getContents(), true);
        expect($body['status'])->toBe('success');
        expect($body['data']['title'])->toBe('My First Quiz');
        expect($body['message'])->toBe("quiz data has been successfully found");
    }); 

    //--- update quiz tests---
    test('PUT /quiz/update_quiz update a specific quiz', function(){

        $quiz_response = createQuiz();
        $admin = $quiz_response['admin']; 
        $client = $quiz_response['client']; 
        $quiz_id = getQuizId($client, $admin);  
        
        $updateData = [
            "title" => "my quiz", 
            "description" => "this is my quiz"
        ]; 

        $updateQuiz = $client->put("/quiz/update_quiz?quiz_id=$quiz_id", [
            "headers" => ["Authorization" => "Bearer ".$admin['token']], 
            "json" => $updateData
        ]); 

        expect($updateQuiz->getStatusCode())->toBe(200); 
        $body = json_decode($updateQuiz->getBody()->getContents(), true); 
        expect($body['status'])->toBe('success'); 
        expect($body['updatedData']['title'])->toBe('my quiz'); 
    });
    
    // ---Delete the specific quiz tests--- 
    test('Delete /quiz/delete_quiz delete the specific quiz', function(){
        $quiz_response = createQuiz(); 
        $admin = $quiz_response['admin']; 
        $client = $quiz_response['client']; 
        $quiz_id = getQuizId($client, $admin);
        
        $response = $client->delete("/quiz/delete_quiz?quiz_id=$quiz_id", [
            "headers" => ["Authorization" => "Bearer ".$admin['token']]
        ]);
        
        expect($response->getStatusCode())->toBe(200); 
        $body = json_decode($response->getBody()->getContents(), true);
        expect($body['status'])->toBe('success');  
    }); 

    // ---Delete the all quizzes of particular admin 
    test('Delete /quiz/delete_all_quizzes', function(){
        $client = getClient(); 
        $admin = createAndLoginAdmin(); 

        $addQuiz = $client->post('/quiz/new_quiz', [
            "headers" => ["Authorization" => "Bearer ".$admin['token']],
            "json"=>[[ 
                'title' => 'My First Quiz', 
                'description' => 'A Quiz about general knowledge.'
            ], [
                'title' => 'first project', 
                'description' => 'a first quiz project'
            ]] 
            ]); 

        $response = $client->delete('quiz/delete_all_quizzes', [
            "headers" => ["Authorization" => "Bearer ".$admin['token']]
        ]);

        expect($response->getStatusCode())->toBe(200); 
        $body = json_decode($response->getBody()->getContents(), true); 
        expect($body['status'])->toBe('success'); 
    }); 