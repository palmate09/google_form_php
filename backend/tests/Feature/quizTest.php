<?php

    require_once __DIR__ . '/../../vendor/autoload.php';

    // tests/QuizApiTest.php

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

            // Temporarily disable foreign key checks to allow truncation.
            $conn->exec("SET FOREIGN_KEY_CHECKS = 0;");
            $conn->exec("TRUNCATE TABLE users;");
            $conn->exec("TRUNCATE TABLE quizzes;");
            $conn->exec("TRUNCATE TABLE questions;");
            $conn->exec("TRUNCATE TABLE options;");
            $conn->exec("SET FOREIGN_KEY_CHECKS = 1;");

        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    });

    
    function createAndLoginAdmin()
    {
        $client = getClient();
        $email = 'admin_' . uniqid() . '@example.com';

        $client->post('/register', ['json' => [
            'username' => 'testadmin' . uniqid(),
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

        return [
            'client' => $client,
            'token' => $loginBody['token'],
            'id' => $payload['sub']
        ];
    }



    function getQuizId($admin){
        $client = $admin['client'];
        $quizData = ['title' => 'My First Quiz', 'description' => 'A Quiz about general knowledge.'];
        $quiz_response = $client->post('/quiz/new_quiz', [
            'headers' => ['Authorization' => 'Bearer ' . $admin['token']],
            'json' => $quizData
        ]);
        $getAllQuizzes = $client->get('/quiz/get_all_quizzes', ["headers" => ['Authorization' => 'Bearer ' . $admin['token']]]);
        $response = json_decode($getAllQuizzes->getBody()->getContents(), true);
        if (empty($response['data'])) {
            throw new Exception("Failed to create or retrieve a quiz to get an ID.");
        }
        return ["quiz_id" => $response['data'][0]['Id'], "quiz_response" => $quiz_response];
    }


    function getQuestionIds($admin){
        $client = $admin['client'];
        $quiz_info = getQuizId($admin);
        $quiz_id = $quiz_info['quiz_id'];

        $client->post("/question/new_question?quiz_id=$quiz_id", [
            "headers" => ["Authorization" => "Bearer " . $admin['token']],
            "json" => ["question_text" => 'A temporary question to get an Id']
        ]);

        $getAllQuestions = $client->get("/question/get_all_question?quiz_id=$quiz_id", [
            "headers" => ["Authorization" => "Bearer " . $admin['token']]
        ]);

        $response = json_decode($getAllQuestions->getBody()->getContents(), true);

        if (empty($response['data'])) {
            throw new Exception("Helper function could not find any questions after creating one.");
        }

        return ['quiz_id' => $quiz_id, 'question_id' => $response['data'][0]['Id']];
    }

    
    function getOptionIds($admin){
        $client = $admin['client'];
        $ids = getQuestionIds($admin);
        $quiz_id = $ids['quiz_id'];
        $question_id = $ids['question_id'];

        $client->post("/option/add_option?quiz_id=$quiz_id&question_id=$question_id", [
            "headers" => ["Authorization" => "Bearer " . $admin['token']],
            "json" => ["option_text" => "Paris", "is_correct" => 1]
        ]);

        $getAllOptions = $client->get("/option/get_all_options?quiz_id=$quiz_id&question_id=$question_id", ["headers" => ["Authorization" => "Bearer " . $admin['token']]]);
        $response = json_decode($getAllOptions->getBody()->getContents(), true);

        if (empty($response['data'])) {
            throw new Exception("Helper function could not find any options after creating one.");
        }

        return ['quiz_id' => $quiz_id, 'question_id' => $question_id, 'option_id' => $response['data'][0]['Id']];
    }


    // --- API TESTS ---

    describe('Quiz API', function () {
        beforeEach(function () {
            $this->admin = createAndLoginAdmin();
            $this->client = $this->admin['client'];
        });

        test('POST /quiz/new_quiz creates a quiz successfully', function () {
            $response = getQuizId($this->admin)['quiz_response'];
            expect($response->getStatusCode())->toBe(201);
        });

        test('POST /quiz/new_quiz fails for unauthenticated requests', function () {
            $response = getClient()->post('/quiz/new_quiz', ['json' => ['title' => 'Test']]);
            expect($response->getStatusCode())->toBe(401);
        });

        describe('when a quiz exists', function () {
            beforeEach(function () {
                $this->quiz_id = getQuizId($this->admin)['quiz_id'];
            });

            test('GET /quiz/get_quiz fetches a specific quiz', function () {
                $response = $this->client->get("/quiz/get_quiz?quiz_id={$this->quiz_id}");
                $body = json_decode($response->getBody()->getContents(), true);
                expect($response->getStatusCode())->toBe(200);
                expect($body['data']['title'])->toBe('My First Quiz');
            });

            test('GET /quiz/get_all_quizzes fetches all quizzes for an admin', function () {
                getQuizId($this->admin); // Create a second quiz
                $response = $this->client->get('/quiz/get_all_quizzes', ["headers" => ["Authorization" => "Bearer " . $this->admin['token']]]);
                $body = json_decode($response->getBody()->getContents(), true);
                expect($response->getStatusCode())->toBe(200);
                expect(count($body['data']))->toBe(2);
            });

            test('PUT /quiz/update_quiz updates a specific quiz', function () {
                $updateData = ["title" => "Updated Title"];
                $response = $this->client->put("/quiz/update_quiz?quiz_id={$this->quiz_id}", [
                    "headers" => ["Authorization" => "Bearer " . $this->admin['token']],
                    "json" => $updateData
                ]);
                $body = json_decode($response->getBody()->getContents(), true);
                expect($response->getStatusCode())->toBe(200);
                expect($body['data']['title'])->toBe('Updated Title');
            });

            test('DELETE /quiz/delete_quiz deletes a specific quiz', function () {
                $response = $this->client->delete("/quiz/delete_quiz?quiz_id={$this->quiz_id}", ["headers" => ["Authorization" => "Bearer " . $this->admin['token']]]);
                expect($response->getStatusCode())->toBe(200);
            });

            test('DELETE /quiz/delete_all_quizzes deletes all quizzes for an admin', function () {
                $response = $this->client->delete('/quiz/delete_all_quizzes', ["headers" => ["Authorization" => "Bearer " . $this->admin['token']]]);
                expect($response->getStatusCode())->toBe(200);
            });
        });
    });

    describe('Question API', function () {
        beforeEach(function () {
            $this->admin = createAndLoginAdmin();
            $this->client = $this->admin['client'];
            
            // Create a single, correctly-related stack of admin -> quiz -> question for the tests that need it.
            $ids = getQuestionIds($this->admin);
            $this->quiz_id = $ids['quiz_id'];
            $this->question_id = $ids['question_id'];
        });

        test('POST /question/new_question creates another question successfully', function () {
            // We use the quiz created in beforeEach to add a second question
            $response = $this->client->post("/question/new_question?quiz_id={$this->quiz_id}", [
                "headers" => ["Authorization" => "Bearer " . $this->admin['token']],
                "json" => ["question_text" => 'Another new question text?']
            ]);
            expect($response->getStatusCode())->toBe(201);
        });

        test('PUT /question/update_question updates a question', function () {
            $response = $this->client->put("/question/update_question?quiz_id={$this->quiz_id}&question_id={$this->question_id}", [
                'headers' => ["Authorization" => "Bearer " . $this->admin['token']],
                'json' => ["question_text" => "Updated question text?"]
            ]);
            expect($response->getStatusCode())->toBe(200);
        });

        test('GET /question/get_question fetches a specific question', function () {
            $response = $this->client->get("/question/get_question?quiz_id={$this->quiz_id}&question_id={$this->question_id}", ["headers" => ["Authorization" => "Bearer " . $this->admin['token']]]);
            expect($response->getStatusCode())->toBe(200);
        });

        test('GET /question/get_all_question fetches all questions', function () {
            // The beforeEach block already created ONE question, so we expect the count to be 1.
            $response = $this->client->get("/question/get_all_question?quiz_id={$this->quiz_id}", ["headers" => ["Authorization" => "Bearer " . $this->admin['token']]]);
            $body = json_decode($response->getBody()->getContents(), true);
            expect($response->getStatusCode())->toBe(200);
            expect(count($body['data']))->toBe(1);
        });

        test('DELETE /question/delete_question deletes a specific question', function () {
            $response = $this->client->delete("/question/delete_question?quiz_id={$this->quiz_id}&question_id={$this->question_id}", ["headers" => ["Authorization" => "Bearer " . $this->admin['token']]]);
            expect($response->getStatusCode())->toBe(200);
        });

        test('DELETE /question/delete_all_questions deletes all questions', function () {
            $response = $this->client->delete("/question/delete_all_question?quiz_id={$this->quiz_id}", ["headers" => ["Authorization" => "Bearer " . $this->admin['token']]]);
            expect($response->getStatusCode())->toBe(200);
        });
    });



    describe('Option API', function () {
        beforeEach(function () {
            $this->admin = createAndLoginAdmin();
            $this->client = $this->admin['client'];
            $ids = getQuestionIds($this->admin);
            $this->quiz_id = $ids['quiz_id'];
            $this->question_id = $ids['question_id'];
        });

        test('POST /option/add_option creates an option successfully', function () {
            $response = $this->client->post("/option/add_option?quiz_id={$this->quiz_id}&question_id={$this->question_id}", [
                "headers" => ["Authorization" => "Bearer " . $this->admin['token']],
                "json" => ["option_text" => "An option", "is_correct" => 1]
            ]);
            expect($response->getStatusCode())->toBe(201);
        });

        describe('when an option exists', function () {
            beforeEach(function () {
                $this->client->post("/option/add_option?quiz_id={$this->quiz_id}&question_id={$this->question_id}", [
                    "headers" => ["Authorization" => "Bearer " . $this->admin['token']],
                    "json" => ["option_text" => "Initial Option", "is_correct" => 1]
                ]);

                $getAllOptions = $this->client->get("/option/get_all_option?quiz_id={$this->quiz_id}&question_id={$this->question_id}", [
                    "headers" => ["Authorization" => "Bearer " . $this->admin['token']]
                ]);
                $response = json_decode($getAllOptions->getBody()->getContents(), true);
                $this->option_id = $response['data'][0]['Id'];
            });

            test('PUT /option/update_option updates an option', function () {
                $response = $this->client->put("/option/update_option?quiz_id={$this->quiz_id}&question_id={$this->question_id}&option_id={$this->option_id}", [
                    'headers' => ["Authorization" => "Bearer " . $this->admin['token']],
                    'json' => ["option_text" => "Updated Option Text", "is_correct" => 0]
                ]);

                expect($response->getStatusCode())->toBe(200);
                $body = json_decode($response->getBody()->getContents(), true);
                expect($body['data']['option_text'])->toBe('Updated Option Text');
            });

            test('GET /option/get_option fetches a specific option', function () {
                $response = $this->client->get("/option/get_option?quiz_id={$this->quiz_id}&question_id={$this->question_id}&option_id={$this->option_id}", [
                    "headers" => ["Authorization" => "Bearer " . $this->admin['token']]
                ]);
                expect($response->getStatusCode())->toBe(200);
                $body = json_decode($response->getBody()->getContents(), true);
                expect($body['data']['Id'])->toBe($this->option_id);
            });

            test('GET /option/get_all_options fetches all options', function () {
                $response = $this->client->get("/option/get_all_option?quiz_id={$this->quiz_id}&question_id={$this->question_id}", [
                    "headers" => ["Authorization" => "Bearer " . $this->admin['token']]
                ]);
                $body = json_decode($response->getBody()->getContents(), true);
                expect($response->getStatusCode())->toBe(200);
                expect(count($body['data']))->toBeGreaterThanOrEqual(1);
            });

            test('DELETE /option/delete_option deletes a specific option', function () {
                $response = $this->client->delete("/option/delete_option?quiz_id={$this->quiz_id}&question_id={$this->question_id}&option_id={$this->option_id}", [
                    "headers" => ["Authorization" => "Bearer " . $this->admin['token']]
                ]);
                expect($response->getStatusCode())->toBe(200);
            });

            test('DELETE /option/delete_all_options deletes all options', function () {
                $response = $this->client->delete("/option/delete_all_options?quiz_id={$this->quiz_id}&question_id={$this->question_id}", [
                    "headers" => ["Authorization" => "Bearer " . $this->admin['token']]
                ]);
                expect($response->getStatusCode())->toBe(200);
            });
        });
    });

    


