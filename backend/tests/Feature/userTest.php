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
            $conn->exec("TRUNCATE TABLE password_resets;");
            $conn->exec("SET FOREIGN_KEY_CHECKS = 1;");

        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    });


    // --- Registration Tests ---

    test('POST /register registers a user successfully', function () {
        $client = getClient();

        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'user', 
            'name' => 'test'
        ];

        $response = $client->post('/register', ['json' => $userData]);

        expect($response->getStatusCode())->toBe(201);
    });

    test('POST /register returns an error for missing fields', function () {
        $client = getClient();

        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
        ];

        $response = $client->post('/register', ['json' => $userData]);

        expect($response->getStatusCode())->toBe(404);
        $body = json_decode($response->getBody()->getContents(), true);
        expect($body['status'])->toBe('error');
    });


    // --- Login Tests ---

    test('POST /login logs in a user successfully', function () {
        $client = getClient();

        $client->post('/register', ['json' => [
            'username' => 'loginuser',
            'email' => 'login@example.com',
            'password' => 'password123',
        ]]);

        $loginData = [
            'email' => 'login@example.com',
            'password' => 'password123'
        ];

        $response = $client->post('/login', ['json' => $loginData]);

        expect($response->getStatusCode())->toBe(200);
        $body = json_decode($response->getBody()->getContents(), true);
        expect($body)->toHaveKey('token');
        expect($body['message'])->toBe('Login Successfully');
    });


    test('POST /login returns an error for invalid credentials', function () {
        $client = getClient();

        $loginData = [
            'email' => 'wrong@example.com',
            'password' => 'wrongpassword'
        ];

        $response = $client->post('/login', ['json' => $loginData]);
        
        $body = json_decode($response->getBody()->getContents(), true);
        expect($body['status'])->toBe('success');
        expect($body['message'])->toBe('Invalid email or password');
    });


    // --- Profile Tests ---

    test('GET /profile fetches user profile with a valid token', function () {
        $client = getClient();

        $client->post('/register', ['json' => ['username' => 'profileuser', 'email' => 'profile@example.com', 'password' => 'password123']]);
        $loginResponse = $client->post('/login', ['json' => ['email' => 'profile@example.com', 'password' => 'password123']]);
        $token = json_decode($loginResponse->getBody()->getContents(), true)['token'];

        $profileResponse = $client->get('/profile', ['headers' => ['Authorization' => 'Bearer ' . $token]]);

        expect($profileResponse->getStatusCode())->toBe(200);
        $body = json_decode($profileResponse->getBody()->getContents(), true);
        expect($body['status'])->toBe('success');
        expect($body['data']['email'])->toBe('profile@example.com');
    });


    test('GET /profile returns an error for an invalid token', function () {
        $client = getClient();

        $response = $client->get('/profile', ['headers' => ['Authorization' => 'Bearer invalidtoken']]);
        
        expect($response->getStatusCode())->toBe(401);
    });


    // --- Forgot Password Request Tests ---

    test('POST /forgot-password sends a reset link', function () {
        $client = getClient();

        $client->post('/register', ['json' => ['username' => 'forgotpass', 'email' => 'palmateshubham559@gmail.com', 'password' => 'password123']]);

        $response = $client->post('/password_resets', ['json' => ['email' => 'palmateshubham559@gmail.com']]);

        expect($response->getStatusCode())->toBe(201);
        $body = json_decode($response->getBody()->getContents(), true);
        expect($body['status'])->toBe('success');
        expect($body['message'])->toBe('Email sent successfully');
    });

    // --- Forgot password Tests --- 

    test('POST /updating the password after the request sent to email', function(){

        $client = getClient(); 

        $client->post('/register', ['json' => ['username' => 'shubham', 'email' => 'palmateshubham559@gmail.com', 'password' => 'password1234']]);
        $response = $client->post('/password_resets', ['json' => ['email' => 'palmateshubham559@gmail.com']]);
        $newResponse = json_decode($response->getBody()->getContents(), true); 
        $newBody = $newResponse['token']; 
        
        $another_response = $client->post('/reset_password', ['json' => ['token' => $newBody,'password' => 'pass', 'confirmPassword' => 'pass']]);

        expect($another_response->getStatusCode())->toBe(201);
    }); 


    // --- Update Profile Tests ---

    test('PUT /profile updates user profile', function () {
        $client = getClient();

        $client->post('/register', ['json' => ['username' => 'updateuser', 'email' => 'update@example.com', 'password' => 'password123']]);
        $loginResponse = $client->post('/login', ['json' => ['email' => 'update@example.com', 'password' => 'password123']]);
        $token = json_decode($loginResponse->getBody()->getContents(), true)['token'];

        $updateData = [
            'username' => 'updateduser',
            'email' => 'updated@example.com',
            'name' => 'Updated Name'
        ];

        $response = $client->put('/profile', [
            'headers' => ['Authorization' => 'Bearer ' . $token],
            'json' => $updateData
        ]);

        expect($response->getStatusCode())->toBe(200);
        $body = json_decode($response->getBody()->getContents(), true);
        expect($body['status'])->toBe('success');
        expect($body['data']['username'])->toBe('updateduser');
    });


    // --- Delete Profile Tests ---

    test('DELETE /profile deletes a user profile', function () {
        $client = getClient();

        $client->post('/register', ['json' => ['username' => 'deleteuser', 'email' => 'delete@example.com', 'password' => 'password123']]);
        $loginResponse = $client->post('/login', ['json' => ['email' => 'delete@example.com', 'password' => 'password123']]);
        $token = json_decode($loginResponse->getBody()->getContents(), true)['token'];

        $response = $client->delete('/profile', ['headers' => ['Authorization' => 'Bearer ' . $token]]);

        expect($response->getStatusCode())->toBe(200);
        $body = json_decode($response->getBody()->getContents(), true);
        expect($body['status'])->toBe('success');
        expect($body['message'])->toBe('user data has been successfully deleted');
    });