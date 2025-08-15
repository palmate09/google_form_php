<?php

    require_once __DIR__ . "/../config/dbconnection.php";
    require_once __DIR__ . "/../controller/userauth.php";
    require_once __DIR__ . "/../middleware/authmiddlware.php";
    header("Content-Type: application/json"); 


    function generateUUID() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, // version 4
            mt_rand(0, 0x3fff) | 0x8000, // variant
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    function roleCheck($conn) {

        $auth = authmiddlware();

        if(!is_array($auth) || empty($auth['sub'])){
            http_response_code(401); 
            echo json_encode([
                "status" => "error", 
                "message" => "Unathorized"
            ]);
            exit; 
        }
        
        $adminId = $auth['sub']; 

        try{
            $stmt = $conn->prepare('SELECT * FROM users WHERE userId = ?');
            $stmt->execute([$adminId]); 
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$admin){
                http_response_code(401); 
                echo json_encode([
                    "status" => "error", 
                    "message" => "Admin not found"
                ]);
                exit; 
            }

            if($admin["role"] !== "admin"){
                http_response_code(401); 
                echo json_encode([
                    "status" => "error", 
                    "message" => "Access denied as you are not admin"
                ]);
                exit; 
            }

            return $admin; 
        }
        catch(Exception $e){
            http_response_code(401); 
            echo json_encode([
                "status" => "error", 
                "message" => $e->getMessage()
            ]);
            exit; 
        }
    }
?>