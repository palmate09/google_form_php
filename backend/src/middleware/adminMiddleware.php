<?php

    require_once __DIR__ . '/../../vendor/autoload.php'; 

    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../'); 
    $dotenv->load(); 

    use Firebase\JWT\JWT; 
    use Firebase\JWT\Key; 

    function adminMiddleware(){
        $headers = getallheaders(); 

        if(!isset($headers['admin_authorization'])){
            http_response_code(401); 
            echo json_encode([
                "error" => "Unatuhorized"
            ]); 
            exit; 
        }

        $authHeader = $headers["admin_authorization"] ?? ""; 
        $token = trim(str_replace("Bearer", "", $authHeader)); 

        try{
            $jwt_secret = trim($_ENV["JWT_SECRECT"]); 
            $decoded = JWT::decode($token, new Key($jwt_secret, "HS256")); 
            return (array)$decoded; 
        }
        catch(Exception $e){
            http_response_code(401); 
            echo json_encode([
                "error" => $e->getMessage()
            ]); 
            exit; 
        }
    }



?>