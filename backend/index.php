
<?php
    require __DIR__ . '/src/controller/userauth.php';
    require __DIR__ . '/src/controller/quiz.php';
    require __DIR__ . '/src/controller/questions.php';
    require __DIR__ . '/src/controller/options.php';  


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
    else if($method === "POST" && $path  === "/quiz/new_quiz"){
        newQuiz($conn, $input);
    }
    else if($method === 'GET'  && $path === '/quiz/get_quiz'){
        getQuiz($conn); 
    }
    else if($method === 'PUT' && $path === '/quiz/update_quiz'){
        updateQuiz($conn, $input); 
    }
    else if($method === 'GET' && $path === '/quiz/get_all_quizzes'){
        getAllQuizzes($conn); 
    }
    else if($method === 'DELETE' && $path === '/quiz/delete_all_quizzes'){
        deleteAllQuizzes($conn);
    }
    else if($method === 'DELETE' && $path === '/quiz/delete_quiz'){
        deleteQuiz($conn);
    }
    else if($method === 'POST' && $path === '/question/new_question'){
        create_question($conn, $input); 
    }
    else if($method === 'PUT' && $path === '/question/update_question'){
        update_question($conn, $input); 
    }
    else if($method === 'GET' && $path === '/question/get_question'){
        get_question($conn, $input);
    }
    else if($method === 'GET' && $path === '/question/get_all_question'){
        get_all_questions($conn); 
    }
    else if($method === 'DELETE' && $path === '/question/delete_question'){
        delete_question($conn);
    }
    else if($method === 'DELETE' && $path === '/question/delete_all_question'){
        delete_all_question($conn);
    }
    else if($method === 'POST' && $path === '/option/add_option'){
        add_option($conn, $input); 
    }
    else if($method === 'GET' && $path === '/option/get_option'){
        get_option($conn); 
    }
    else if($method === 'PUT' && $path === '/option/update_option'){
        update_option($conn, $input);
    }
    else if($method === 'DELETE' && $path === '/option/delete_option'){
        delete_option($conn, $input); 
    }
    else if($method === 'GET' && $path === '/option/get_all_option'){
        get_option($conn); 
    }
    else if($method === 'DELETE' && $path === '/option/delete_all_options'){
        get_all_options($conn); 
    }
    else{
        http_response_code(404); 
        echo json_encode(["status" => "error", "message" => "Enpoint not found"]); 
    }
?>