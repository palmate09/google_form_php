

<?php

    require_once __DIR__ . '/vendor/autoload.php'; 

    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load(); 


    $host = $_ENV['DB_HOST']; 
    $name = $_ENV['DB_NAME']; 
    $user = $_ENV['DB_USER']; 
    $pass = $_ENV['DB_PASS']; 


    try{
        $conn = new PDO($host, $name, $user, $pass);
        $conn->setAttribute(PDO::ATTER_ERRMODE, PDO::ERRORMODE_EXCEPTION);  
    }
    catch(PDOException $e){
        die("Connection failed: ". $e->getMessage()); 
    }
?>