
<?php

    require_once __DIR__ . "/../config/dbconnection.php";
    require_once __DIR__ . "/../middleware/authmiddlware.php";
    require_once __DIR__ . '/../utils/helpers.php'; 
    header("Content-Type: application/json"); 

    use Firebase\JWT\JWT; 
    use Firebase\JWT\key;

    use PHPMailer\PHPMailer\PHPMailer; 
    use PHPMailer\PHPMailer\Exception; 

    $input = json_encode(file_get_contents('php://input'), true); 


    // register endpoint
    function registerUser($conn, $input){
        if(empty($input["email"]) || empty($input["password"]) || empty($input['username']) || empty($input["role"])){
            echo json_encode(["status" => "error", "message" => "All fields are required"]); 
            exit;
            // Specify the error & make a function
        }

        $userId = generateUUID(); 
        $hashedPassword = password_hash($input["password"], PASSWORD_BCRYPT); 
        $email = $input["email"]; 
        $username = $input["username"]; 
        $role = isset($input["role"])? $input["role"] : "user"; 

        $stmt = $conn->prepare('INSERT INTO users(username, password, email, role, userId) VALUES(?,?,?,?,?)'); 
        // sql/db columns must be snake cased

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
        $stmt->execute([$input["email"] ?? $input["username"]]); 
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
            throw new Exception('jwt secret is missing or not a string'); 
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
                "message" => "User Id not found"
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

        $mail = new PHPMailer(true); 

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
            date_default_timezone_set('Asia/Kolkata');
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour')); 

            $stmt = $conn->prepare('INSERT INTO password_resets(email, token , expires_at) VALUES(?,?,?)');
            $stmt->execute([$email, $token, $expires]);

            //server settings
            $mail->isSMTP(); 
            $mail->Host        = 'smtp.gmail.com';
            $mail->SMTPAuth    = true; 
            $mail->Username    = 'palmateeknath09@gmail.com'; 
            $mail->Password    = 'zpooynoedofdbqny'; 
            $mail->SMTPSecure  = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port        = 587; 
            // use mailtrap for email testings

            // Recipients
            $mail->setFrom('palmateeknath09@gmail.com', 'Eknath Palmate'); 
            $mail->addAddress('palmateshubham559@gmail.com', 'Shubham Palmate');

            $mail->isHTML(true); 
            $mail->Subject = 'Forgot password link';
            $mail->Body    = "<b>Hello!</b> This is a test email sent using PHPMailer with SMTP   Link :- https://localhost:8000/reset_password?token=.$token";
            
            $mail->send(); 

            http_response_code(201); 
            echo json_encode([
                "status" => "success", 
                "message" => "Email sent successfully"
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

    // forgot password endpoint
    function forgotPass($conn, $input){

        $token = $input["token"] ?? "";
        $newPassword = $input["password"] ?? '';
        $confirmPassword = $input['confirm_password'] ?? '';

        if(empty($token) || empty($newPassword) || empty($confirmPassword)){
            http_response_code(401); 
            echo json_encode([
                "status" => "error", 
                "message" => "Token, password, and confirm_password are required."
            ]);
            exit; 
        }

        if($newPassword !== $confirmPassword){
            http_response_code(401); 
            echo json_encode([
                "status" => "error", 
                "message" => "password does not match"
            ]);
            exit; 
        }

        // check token validity
        $stmt = $conn->prepare('SELECT * FROM password_resets WHERE token = ? AND expires_at >= NOW()');
        $stmt->execute([$token]); 
        $resetData = $stmt->fetch(PDO::FETCH_ASSOC);


        if(!$resetData){
            http_response_code(401); 
            echo json_encode([
                "status" => "error", 
                "message" => "Invalid or token exipred"
            ]);
            exit; 
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT); 

        $stmt = $conn->prepare('UPDATE users SET password = ? WHERE email = ?');
        $stmt->execute([$hashedPassword, $resetData['email']]); 

        $stmt = $conn->prepare('DELETE FROM password_resets WHERE email = ?');
        $stmt->execute([$resetData['email']]); 


        http_response_code(200); 
        echo json_encode([
            "status" => "success", 
            "message" => "Password has been successfully updated"
        ]);
    }

    // update the profile 
    function updateProfile($conn, $input){

        $auth = authmiddlware(); 

        $userId = $auth['sub']; 

        if(!$userId && empty($input['email'] && empty($input['username']) && empty($input["name"]))){
            echo json_encode([
                "status" => "error", 
                "message" => "userId not found"
            ]); 
            exit; 
        }

        try{
            // update the user
            $stmt = $conn->prepare('UPDATE users SET email =? , username = ?, name = ? WHERE userId = ?'); 
            $stmt->execute([$input['email'], $input['username'], $input['name'], $userId]); 
            
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