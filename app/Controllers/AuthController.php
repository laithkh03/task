<?php

namespace App\Controllers;

use App\Models\UserModel;
use Firebase\JWT\JWT;
use CodeIgniter\HTTP\Response;
use Config\App;

class AuthController extends BaseController {

    private $secret_key;

    // Constructor: Initializes the JWT secret key for token generation
    public function __construct() {
        $config = new App();
        $this->secret_key = $config->JWTSecretKey;  // Retrieve the secret key from the app configuration
    }

    public function anotherTest() {
        echo "This is another test page!";
        die;
    }

    public function register() {
        // Define validation rules for user registration
        $rules = [
            'username' => 'required|min_length[3]|is_unique[users.username]',  // Username must be unique and at least 3 characters
            'password' => 'required|min_length[6]'  // Password must be at least 6 characters
        ];

        // Check if the request passes validation
        if (!$this->validate($rules)) {
            // If validation fails, return a 400 Bad Request response with an error message
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'User name and password validation error'
            ])->setStatusCode(400);  // 400 Bad Request
        }

        // Instantiate the UserModel to interact with the users table
        $userModel = new UserModel();

        // Prepare the user data for insertion
        $data = [
            'username' => $this->request->getVar('username'),  // Retrieve username from the request
            'password' => password_hash($this->request->getVar('password'), PASSWORD_BCRYPT)  // Hash the password before saving
        ];

        try {
            // Attempt to save the user data in the database
            if (!$userModel->save($data)) {
                // If saving fails, return a 500 Internal Server Error response
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'User registration failed'
                ])->setStatusCode(500);  // 500 Internal Server Error
            }
        } catch (\Exception $e) {
            // Handle any exceptions that occur during the registration process
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'An error occurred during registration: ' . $e->getMessage()
            ])->setStatusCode(500);  // 500 Internal Server Error
        }

        // If registration is successful, return a 201 Created response
        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'User registered successfully'
        ])->setStatusCode(201);  // 201 Created
    }

    public function login() {
        $userModel = new UserModel();
        // Retrieve the username and password from the request
        $username = $this->request->getVar('username');
        $password = $this->request->getVar('password');

        // Fetch the user data from the database by username
        $user = $userModel->where('username', $username)->first();

        // Generate a JWT token upon successful login
        try {
            // Encode the user's ID and username in the JWT token
            $token = JWT::encode(
                ['id' => $user['id'], 'username' => $user['username']],
                $this->secret_key,  // Use the secret key to sign the token
                'HS256'  // Specify the hashing algorithm
            );
        } catch (\Exception $e) {
            // If token generation fails, return a 500 Internal Server Error response
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Token generation failed: ' . $e->getMessage()
            ])->setStatusCode(500);  // 500 Internal Server Error
        }
        // If user is not found or password doesn't match, return a 401 Unauthorized response
        if (empty($token)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Invalid credentials'
            ])->setStatusCode(401);  // 401 Unauthorized
        }
        // If login is successful, return the JWT token with a 200 OK response
        return $this->response->setJSON([
            'status' => 'success',
            'token' => $token  // Return the generated token
        ])->setStatusCode(200);  // 200 OK
    }
}
