
<?php

    require_once __DIR__ . "/../config/dbconnection.php";
    require_once __DIR__ . "/../middleware/authmiddlware.php"; 
    header("Content-Type: application/json"); 

    use Firebase\JWT\JWT; 
    use Firebase\JWT\key;


    $method = $_SERVER['REQUEST_METHOD']; 
    $input = json_encode(file_get_contents('php://input'), true); 

    //creating the uuid 
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

    // register endpoint
    function registerUser($conn, $input){
        if(empty($input["email"]) || empty($input["password"]) || empty($input['username']) || empty($input["role"])){
            echo json_encode(["status" => "error", "message" => "All fields are required"]); 
            exit; 
        }

        $userId = generateUUID(); 
        $hashedPassword = password_hash($input["password"], PASSWORD_BCRYPT); 
        $email = $input["email"]; 
        $username = $input["username"]; 
        $role = isset($input["role"])? $input["role"] : "user"; 

        $stmt = $conn->prepare('INSERT INTO users(username, password, email, role, userId) VALUES(?,?,?,?,?)'); 

        try{
            $stmt->execute([ $username, $hashedPassword, $email, $role, $userId]); 
            http_response_code(201); 
            echo json_encode([
                "status" => "success", 
                "message" => "User registered successfully"
            ]); 
        }
        catch(Exception $e){
            echo json_encode(["status" => "error", "message" => $e->getMessage()]); 
        }
    }

    //login endpoint
    function loginUser($conn, $input){
        if((empty($input["email"]) && empty($input['username'])) || empty($input["password"])){
            echo json_encode(["status" => "error", "message" => "All fields are required"]);
            exit; 
        }

        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR username = ?"); 
        $stmt->execute([$input["email"] ?? $input["username"], $input["email"] ?? $input["username"]]); 
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$user && !password_verify($input["password"], $user["password"])){
            echo json_encode([
                "status" => "success", 
                "message" => "Invalid email or password"
            ]);  
        }

        $payload = [
            'iss' => 'http://localhost:8000', 
            'iat' => time(), 
            'exp' => time() + (60* 60 * 60), 
            'sub' => $user['userId'], 
            'username' => $user['username'] 
        ];

        $jwt_secret = $_ENV["JWT_SECRECT"]; 

        if(!is_string($jwt_secret)){
            throw new Exception('jwt secrect is missing or not a string'); 
        } 

        $jwt = JWT::encode($payload, $jwt_secret, 'HS256');

        http_response_code(200);
        echo json_encode([
            'token' => $jwt, 
            'message' => 'Login Successfully'
        ]); 
    }
  

    // profile endpoint
    function getProfile($conn){
        
        $auth = authmiddlware();     
        
        $userId = $auth['sub']; 

        if(!$userId){
            http_response_code(401); 
            echo json_encode([
                "status" => "error", 
                "message" => "userId not found"
            ]); 
        }

        try{
            $stmt = $conn->prepare('SELECT * FROM users WHERE userId = ?'); 
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if($stmt->rowCount() === 0){
                http_response_code(401); 
                echo json_encode([
                    "status" => "error" , 
                    "message" => "User not found"
                ]); 
            }

            http_response_code(200);
            echo json_encode([
                "status" => "success", 
                "message" => "request has successfully passed", 
                "data" => $user
            ]);
        } 
        catch(Exception $e){
            http_response_code(500); 
            echo json_encode([
                "status" => "error", 
                "message" => $e->getMessage()
            ]);
        }
    }

    // forgot password rquest endpoint
    function forgotPassRequest($conn, $input){
        
        $email = $input['email']; 

        if(!$email){
            http_response_code(401); 
            echo json_decode([
                "status" => "error", 
                "message" => "email not found"
            ]);
            exit; 
        }

        try{

            $stmt = $conn->prepare('SELECT * FROM users WHERE email = ?'); 
            $stmt->execute([$email]); 
            $user = $stmt->fetch(PDO::FETCH_ASSOC); 
            
            if(!$user){
                http_response_code(401); 
                echo json_encode([
                    "status" => "error", 
                    "message" => "user not found"
                ]);
            }

            $token = bin2hex(random_bytes(32)); 
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour')); 

            $stmt = $conn->prepare('INSERT INTO password_resets(email, token , expires_at) VALUES(?,?,?)');
            $stmt->execute([$email, $token, $expires]);

            $resetLink = 'https://localhost:8000/reset_password?token='. $token; 

            var_dump($resetLink); 


            $subject = "password reset request"; 
            $message = "Click here to reset your password:- ". $resetLink; 
            $headers = "From: palmateeknath09@gmail.com"; 

            $sumFunction = mail($email, $subject, $message, $headers); 

            if(mail($email, $subject, $message, $headers)){
                http_response_code(201); 
                echo json_encode(["status" => "success", "message" => "password reset email sent"]); 
            }
            else {
                http_response_code(500);
                echo json_encode([
                    "status" => "error", 
                    "message" => "email sending failed"
                ]);
            }
        }
        catch(Exception $e){
            http_response_code(500); 
            echo json_encode([
                "status" => "error", 
                "message" => $e->getMessage()
            ]);
        }
        
    }

    // forgot password endpoint
    function forgotPass($conn, $input){



    }

    // update the profile 
    function updateProfile($conn, $input){

        $auth = authmiddlware(); 

        $userId = $auth['sub']; 

        if(!$userId || empty($input['email'] || empty($input['username']))){
            echo json_encode([
                "status" => "error", 
                "message" => "userId not found"
            ]); 
            exit; 
        }

        try{
            // update the user
            $stmt = $conn->prepare('UPDATE users SET email =? , username = ? WHERE userId = ?'); 
            $stmt->execute([$input['email'], $input['username'], $userId]); 
            
            // show the updated data
            $stmt = $conn->prepare('SELECT * FROM users WHERE userId = ?'); 
            $stmt->execute([$userId]); 
            $user = $stmt->fetch(PDO::FETCH_ASSOC); 

            http_response_code(200); 
            echo json_encode([
                "status" => "success", 
                "message" => "profile has been update successfully", 
                "data" => $user
            ]); 
        }
        catch(Exception $e){
            http_response_code(500); 
            echo json_encode([
                "status" => "error", 
                "message" => $e->getMessage()
            ]); 
        }      
    }

    // delete the user profile 
    function deleteProfile($conn){

        $auth = authmiddlware(); 

        $userId = $auth['sub']; 

        if(!$userId){
            http_response_code(401); 
            echo json_encode([
                "status" => "error", 
                "message" => "userId not found"
            ]); 

            exit; 
        }

        try{

            $stmt = $conn->prepare('DELETE FROM users WHERE userId = ?'); 
            $stmt->execute([$userId]); 
            
            http_response_code(200); 
            echo json_encode([
                "status" => "success", 
                "message" => "user data has been successfully deleted"
            ]);
        }
        catch(Exception $e){
            http_response_code(500); 
            echo json_encode([
                "status" => "error", 
                "message" => $e->getMessage()
            ]);
        }
    }
?>