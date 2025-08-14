<?php

    require_once __DIR__. '/../../vendor/autoload.php'; 

    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();


    use Firebase\JWT\JWT; 
    use Firebase\JWT\Key; 

    function authmiddlware(){
        $headers = getallheaders(); 

        if(!isset($headers['Authorization'])){
            http_response_code(401); 
            echo json_encode(["error" => "Unauthorized"]); 
            exit; 
        }

        $authHeader = $headers["Authorization"] ?? ""; 
        $token = trim(str_replace("Bearer", "", $authHeader));

        try{
            $jwt_secrect = trim($_ENV["JWT_SECRECT"]);  
            $decoded = JWT::decode($token, new Key($jwt_secrect, 'HS256')); 
            return (array)$decoded;
        }
        catch(Exception $e){
            http_response_code(401); 
            echo json_encode(["error" => $e->getMessage()]); 
            exit; 
        }
    }
?>