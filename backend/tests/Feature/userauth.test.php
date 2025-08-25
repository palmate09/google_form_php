<?php

use PHPUnit\Framework\TestCase;

// Include necessary files for testing
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/config/dbconnection.php';
require_once __DIR__ . '/../../src/middleware/authmiddlware.php';
require_once __DIR__ . '/../../src/utils/helpers.php';

// Load environment variables for testing
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Include the userauth functions
require_once __DIR__ . '/../../src/controller/userauth.php';

// Test database connection
beforeEach(function () {
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $name = $_ENV['DB_NAME'] ?? 'test_db';
    $user = $_ENV['DB_USER'] ?? 'root';
    $pass = $_ENV['DB_PASS'] ?? '';

    try {
        $this->conn = new PDO("mysql:host=$host;dbname=$name;charset=utf8", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
    } catch (PDOException $e) {
        test()->markTestSkipped("Database connection failed: " . $e->getMessage());
    }

    $this->testUser = [
        'email' => 'test@example.com',
        'username' => 'testuser',
        'password' => 'testpassword123',
        'role' => 'user'
    ];
});

afterEach(function () {
    // Clean up test data
    if (isset($this->conn)) {
        $stmt = $this->conn->prepare("DELETE FROM users WHERE email = ?");
        $stmt->execute([$this->testUser['email']]);
        
        $stmt = $this->conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmt->execute([$this->testUser['email']]);
    }
});

test('can register user with valid data', function () {
    // Capture output
    ob_start();
    
    // Test registration with valid data
    $input = $this->testUser;
    registerUser($this->conn, $input);
    
    $output = ob_get_clean();
    $response = json_decode($output, true);
    
    expect($response['status'])->toBe('success');
    expect($response['message'])->toBe('User registered successfully');
    
    // Verify user was actually created in database
    $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$this->testUser['email']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    expect($user)->not->toBeNull();
    expect($user['username'])->toBe($this->testUser['username']);
    expect($user['email'])->toBe($this->testUser['email']);
    expect(password_verify($this->testUser['password'], $user['password']))->toBeTrue();
});

test('cannot register user with missing fields', function () {
    // Capture output
    ob_start();
    
    // Test registration with missing fields
    $input = ['email' => 'test@example.com']; // Missing password, username, role
    registerUser($this->conn, $input);
    
    $output = ob_get_clean();
    $response = json_decode($output, true);
    
    expect($response['status'])->toBe('error');
    expect($response['message'])->toBe('All fields are required');
});

test('can login user with valid credentials', function () {
    // First register a user
    registerUser($this->conn, $this->testUser);
    
    // Capture output
    ob_start();
    
    // Test login with valid credentials
    $input = [
        'email' => $this->testUser['email'],
        'password' => $this->testUser['password']
    ];
    loginUser($this->conn, $input);
    
    $output = ob_get_clean();
    $response = json_decode($output, true);
    
    expect($response)->toHaveKey('token');
    expect($response['message'])->toBe('Login Successfully');
    expect($response['token'])->not->toBeEmpty();
});

test('cannot login user with invalid credentials', function () {
    // Capture output
    ob_start();
    
    // Test login with invalid credentials
    $input = [
        'email' => 'nonexistent@example.com',
        'password' => 'wrongpassword'
    ];
    loginUser($this->conn, $input);
    
    $output = ob_get_clean();
    $response = json_decode($output, true);
    
    expect($response['status'])->toBe('success');
    expect($response['message'])->toBe('Invalid email or password');
});

test('cannot login user with missing fields', function () {
    // Capture output
    ob_start();
    
    // Test login with missing fields
    $input = ['email' => 'test@example.com']; // Missing password
    loginUser($this->conn, $input);
    
    $output = ob_get_clean();
    $response = json_decode($output, true);
    
    expect($response['status'])->toBe('error');
    expect($response['message'])->toBe('All fields are required');
});

test('can reset password with valid token', function () {
    // First register a user and create a password reset token
    registerUser($this->conn, $this->testUser);
    
    // Manually insert a password reset token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    $stmt = $this->conn->prepare('INSERT INTO password_resets(email, token, expires_at) VALUES(?,?,?)');
    $stmt->execute([$this->testUser['email'], $token, $expires]);
    
    // Capture output
    ob_start();
    
    $input = [
        'token' => $token,
        'password' => 'newpassword123',
        'confirm_password' => 'newpassword123'
    ];
    forgotPass($this->conn, $input);
    
    $output = ob_get_clean();
    $response = json_decode($output, true);
    
    expect($response['status'])->toBe('success');
    expect($response['message'])->toBe('Password has been successfully updated');
    
    // Verify password was actually updated
    $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$this->testUser['email']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    expect(password_verify('newpassword123', $user['password']))->toBeTrue();
});

test('cannot reset password with invalid token', function () {
    // Capture output
    ob_start();
    
    $input = [
        'token' => 'invalid_token',
        'password' => 'newpassword123',
        'confirm_password' => 'newpassword123'
    ];
    forgotPass($this->conn, $input);
    
    $output = ob_get_clean();
    $response = json_decode($output, true);
    
    expect($response['status'])->toBe('error');
    expect($response['message'])->toBe('Invalid or token exipred');
});

test('cannot reset password with mismatched passwords', function () {
    // Capture output
    ob_start();
    
    $input = [
        'token' => 'some_token',
        'password' => 'newpassword123',
        'confirm_password' => 'differentpassword'
    ];
    forgotPass($this->conn, $input);
    
    $output = ob_get_clean();
    $response = json_decode($output, true);
    
    expect($response['status'])->toBe('error');
    expect($response['message'])->toBe('password does not match');
});

// Helper function to run tests manually
function runUserAuthTests() {
    echo "Running UserAuth Tests with Pest...\n";
    
    // You can run this function to execute tests manually
    // or use the Pest command: ./vendor/bin/pest tests/Feature/userauth.test.php
}

// Uncomment to run tests directly
// runUserAuthTests();
?>
