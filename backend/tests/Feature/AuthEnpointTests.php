<?php

use function Pest\Faker\faker;


$mockConn = null; 

beforeEach(function(){

    global $mockConn; 
    $mockConn  = Mockery::mock('PDO'); 


    function authmiddlware(){
        return [
            'sub' => 'mock-user-id-123', 
            'username' => 'testuser'
        ]; 
    }
});


afterEach(function(){
    Mockery::close(); 
});


test('user can register successfully', function () {
    global $mockConn;

    $input = [
        'username' => faker()->userName,
        'email' => faker()->safeEmail,
        'password' => 'password123',
        'role' => 'user',
    ];

    $stmt = Mockery::mock('PDOStatement');
    $stmt->shouldReceive('execute')->once()->andReturn(true);

    $mockConn->shouldReceive('prepare')
        ->with('INSERT INTO users(username, password, email, role, userId) VALUES(?,?,?,?,?)')
        ->once()
        ->andReturn($stmt);
        
    // We capture the output because your functions use `echo`.
    ob_start();
    registerUser($mockConn, $input);
    $output = ob_get_clean();
    $response = json_decode($output, true);

    expect($response['status'])->toBe('success');
    expect($response['message'])->toBe('User registered successfully');
    // You can also check http_response_code() if you set it in your function.
});


test('registration fails when required fields are missing', function () {
    global $mockConn;
    $input = [
        'email' => faker()->safeEmail,
        // Missing username and password
    ];

    ob_start();
    registerUser($mockConn, $input);
    $output = ob_get_clean();
    $response = json_decode($output, true);

    expect($response['status'])->toBe('error');
    expect($response['message'])->toBe('All fields are required');
});
