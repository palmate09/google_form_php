

<?php

    require_once __DIR__ . '/vendor/autoload.php'; 

    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load(); 


    $host = $_ENV['DB_HOST']; 
    $name = $_ENV['DB_NAME']; 
    $user = $_ENV['DB_USER']; 
    $pass = $_ENV['DB_PASS']; 


    $conn = new mysqli($host, $name, $user, $pass); 

    if($conn->connection_error()){
        die("Connection falied".$conn->connection_error()); 
    }

    echo "Connection successfully with database";
?>