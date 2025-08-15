
<?php
    require __DIR__ . '/src/controller/userauth.php';


    $method = $_SERVER["REQUEST_METHOD"];
    $path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH); 
    $input = json_decode(file_get_contents('php://input'), true); 


    if($method === 'POST' && $path === '/register'){
        registerUser($conn, $input); 
    }
    else if($method === 'POST' && $path === '/login'){
        loginUser($conn, $input); 
    }
    else if ($method === "GET" && $path === "/profile"){
        getProfile($conn); 
    }
    else if ($method === "POST" && $path === "/password_resets"){
        forgotPassRequest($conn, $input); 
    }
    else if ($method === "POST" && $path === '/reset_password'){
        forgotPass($conn, $input);
    }
    else if ($method === "PUT" && $path === "/profile"){
        updateProfile($conn, $input); 
    }
    else if($method === "DELETE" && $path === "/profile"){
        deleteProfile($conn); 
    }
    else{
        http_response_code(404); 
        echo json_encode(["status" => "error", "message" => "Enpoint not found"]); 
    }
?>