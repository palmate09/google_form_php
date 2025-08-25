<?php

// Simple test runner for userauth.php functions
// Run this file directly: php test_userauth.php

// Include necessary files
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/config/dbconnection.php';
require_once __DIR__ . '/src/middleware/authmiddlware.php';
require_once __DIR__ . '/src/utils/helpers.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Include the userauth functions
require_once __DIR__ . '/src/controller/userauth.php';

class SimpleUserAuthTest
{
    private $conn;
    private $testUser;
    private $passedTests = 0;
    private $failedTests = 0;

    public function __construct()
    {
        // Set up test database connection
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $name = $_ENV['DB_NAME'] ?? 'test_db';
        $user = $_ENV['DB_USER'] ?? 'root';
        $pass = $_ENV['DB_PASS'] ?? '';

        try {
            $this->conn = new PDO("mysql:host=$host;dbname=$name;charset=utf8", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            echo "✓ Database connection successful\n";
        } catch (PDOException $e) {
            die("✗ Database connection failed: " . $e->getMessage() . "\n");
        }

        $this->testUser = [
            'email' => 'test@example.com',
            'username' => 'testuser',
            'password' => 'testpassword123',
            'role' => 'user'
        ];
    }

    private function cleanUp()
    {
        // Clean up test data
        $stmt = $this->conn->prepare("DELETE FROM users WHERE email = ?");
        $stmt->execute([$this->testUser['email']]);
        
        $stmt = $this->conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmt->execute([$this->testUser['email']]);
    }

    private function assert($condition, $message)
    {
        if ($condition) {
            echo "✓ " . $message . "\n";
            $this->passedTests++;
        } else {
            echo "✗ " . $message . "\n";
            $this->failedTests++;
        }
    }

    public function testRegisterUser()
    {
        echo "\n=== Testing User Registration ===\n";
        
        // Test 1: Register with valid data
        ob_start();
        registerUser($this->conn, $this->testUser);
        $output = ob_get_clean();
        $response = json_decode($output, true);
        
        $this->assert(
            $response['status'] === 'success' && $response['message'] === 'User registered successfully',
            'Register user with valid data'
        );

        // Test 2: Register with missing fields
        ob_start();
        registerUser($this->conn, ['email' => 'test@example.com']);
        $output = ob_get_clean();
        $response = json_decode($output, true);
        
        $this->assert(
            $response['status'] === 'error' && $response['message'] === 'All fields are required',
            'Register user with missing fields'
        );

        // Test 3: Verify user was created in database
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$this->testUser['email']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assert(
            $user !== false && password_verify($this->testUser['password'], $user['password']),
            'User data stored correctly in database'
        );
    }

    public function testLoginUser()
    {
        echo "\n=== Testing User Login ===\n";
        
        // First register a user
        registerUser($this->conn, $this->testUser);
        
        // Test 1: Login with valid credentials
        ob_start();
        loginUser($this->conn, [
            'email' => $this->testUser['email'],
            'password' => $this->testUser['password']
        ]);
        $output = ob_get_clean();
        $response = json_decode($output, true);
        
        $this->assert(
            isset($response['token']) && $response['message'] === 'Login Successfully',
            'Login with valid credentials'
        );

        // Test 2: Login with invalid credentials
        ob_start();
        loginUser($this->conn, [
            'email' => 'wrong@example.com',
            'password' => 'wrongpassword'
        ]);
        $output = ob_get_clean();
        $response = json_decode($output, true);
        
        $this->assert(
            $response['status'] === 'success' && $response['message'] === 'Invalid email or password',
            'Login with invalid credentials'
        );

        // Test 3: Login with missing fields
        ob_start();
        loginUser($this->conn, ['email' => 'test@example.com']);
        $output = ob_get_clean();
        $response = json_decode($output, true);
        
        $this->assert(
            $response['status'] === 'error' && $response['message'] === 'All fields are required',
            'Login with missing fields'
        );
    }

    public function testPasswordReset()
    {
        echo "\n=== Testing Password Reset ===\n";
        
        // First register a user
        registerUser($this->conn, $this->testUser);
        
        // Test 1: Reset password with valid token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $stmt = $this->conn->prepare('INSERT INTO password_resets(email, token, expires_at) VALUES(?,?,?)');
        $stmt->execute([$this->testUser['email'], $token, $expires]);
        
        ob_start();
        forgotPass($this->conn, [
            'token' => $token,
            'password' => 'newpassword123',
            'confirm_password' => 'newpassword123'
        ]);
        $output = ob_get_clean();
        $response = json_decode($output, true);
        
        $this->assert(
            $response['status'] === 'success' && $response['message'] === 'Password has been successfully updated',
            'Reset password with valid token'
        );

        // Test 2: Reset password with invalid token
        ob_start();
        forgotPass($this->conn, [
            'token' => 'invalid_token',
            'password' => 'newpassword123',
            'confirm_password' => 'newpassword123'
        ]);
        $output = ob_get_clean();
        $response = json_decode($output, true);
        
        $this->assert(
            $response['status'] === 'error' && $response['message'] === 'Invalid or token exipred',
            'Reset password with invalid token'
        );

        // Test 3: Reset password with mismatched passwords
        ob_start();
        forgotPass($this->conn, [
            'token' => 'some_token',
            'password' => 'newpassword123',
            'confirm_password' => 'differentpassword'
        ]);
        $output = ob_get_clean();
        $response = json_decode($output, true);
        
        $this->assert(
            $response['status'] === 'error' && $response['message'] === 'password does not match',
            'Reset password with mismatched passwords'
        );
    }

    public function runAllTests()
    {
        echo "Starting UserAuth Tests...\n";
        echo "================================\n";
        
        try {
            $this->testRegisterUser();
            $this->testLoginUser();
            $this->testPasswordReset();
        } catch (Exception $e) {
            echo "✗ Test failed with exception: " . $e->getMessage() . "\n";
            $this->failedTests++;
        } finally {
            $this->cleanUp();
        }
        
        echo "\n================================\n";
        echo "Test Results:\n";
        echo "Passed: " . $this->passedTests . "\n";
        echo "Failed: " . $this->failedTests . "\n";
        echo "Total: " . ($this->passedTests + $this->failedTests) . "\n";
        
        if ($this->failedTests === 0) {
            echo "🎉 All tests passed!\n";
        } else {
            echo "❌ Some tests failed!\n";
        }
    }
}

// Run the tests if this file is executed directly
if (php_sapi_name() === 'cli') {
    $test = new SimpleUserAuthTest();
    $test->runAllTests();
} else {
    echo "This file should be run from the command line: php test_userauth.php\n";
}
?>
