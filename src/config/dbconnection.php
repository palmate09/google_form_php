

<?php

    require_once __DIR__ . '/../../vendor/autoload.php'; 

    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load(); 


    $host = $_ENV['DB_HOST']; 
    $name = $_ENV['DB_NAME']; 
    $user = $_ENV['DB_USER']; 
    $pass = $_ENV['DB_PASS']; 

    try{
        $conn = new PDO("mysql:host=$host;dbname=$name;charset=utf8", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
    }
    catch(PDOException $e){
        die("Connection failed: ". $e->getMessage()); 
    }
?>