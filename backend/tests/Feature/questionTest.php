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
            // $conn->exec("TRUNCATE TABLE password_resets;");
            $conn->exec("SET FOREIGN_KEY_CHECKS = 1;");

        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    });

    //
