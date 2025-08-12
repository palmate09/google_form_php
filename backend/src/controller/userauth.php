
<?php

    require_once __DIR__ . "/../config/dbconnection.php";
    header("Content-Type: application/json"); 


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

        if($user && password_verify($input["password"], $user["password"])){
            echo json_encode([
                "status" => "success", 
                "message" => "Login successfully"
            ]); 
        }
        else{
            echo json_encode([
                "status" => "success", 
                "message" => "Invalid email or password"
            ]); 
        }
    }

?>