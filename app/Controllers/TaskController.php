<?php

namespace App\Controllers;
use App\Models\TaskModel;
use Firebase\JWT\JWT;
use CodeIgniter\HTTP\Response;
use Config\App;
use Firebase\JWT\Key;

class TaskController extends BaseController {

    private $secret_key;

    // Constructor: Initializes the secret key for JWT validation
    public function __construct() {
        $config = new App();
        $this->secret_key = $config->JWTSecretKey;  // Retrieve JWT secret key from the app configuration
    }

    private function getUserIdFromToken() {
        // Get the Authorization header from the request
        $authHeader = $this->request->getHeader('Authorization');
        $token = str_replace('Bearer ', '', $authHeader->getValue());  // Remove 'Bearer' from the token

        try {
            // Decode the token using the secret key and retrieve user ID
            $decoded = JWT::decode($token, new Key($this->secret_key, 'HS256'));
            return $decoded->id;  // Return user ID from the decoded token
        } catch (\Exception $e) {
            // If token is invalid or decoding fails, return null
            return null;
        }
    }

    public function createTask() {
        $user_id = $this->getUserIdFromToken();  // Get user ID from the token
        if (!$user_id) {
            // If no valid user ID, return 401 Unauthorized
            return $this->response->setJSON(['error' => 'Unauthorized'])->setStatusCode(401);
        }
    
        // Prepare the task data for saving
        $taskModel = new TaskModel();
        $data = [
            'title' => $this->request->getVar('title'),
            'description' => $this->request->getVar('description'),
            'status' => !empty($this->request->getVar('status')) ? $this->request->getVar('status') : 'pending',  // Default status is 'pending'
            'due_date' => $this->request->getVar('due_date'),
            'user_id' => $user_id,  // Associate the task with the user ID
        ];
    
        // Save task
        if ($taskModel->save($data)) {
            // Retrieve the newly created task's ID
            $taskId = $taskModel->getInsertID();
    
            // Prepare the response data including the task ID
            $responseData = [
                'id' => $taskId,
                'title' => $data['title'],
                'description' => $data['description'],
                'status' => $data['status'],
                'due_date' => $data['due_date'],
            ];
    
            return $this->response->setJSON($responseData)->setStatusCode(201);  // Task created
        }
    
        // Return 500 Internal Server Error if task creation fails
        return $this->response->setJSON(['error' => 'Unable to create task'])->setStatusCode(500);
    }

    public function getAllTasks() {
        $user_id = $this->getUserIdFromToken();  // Get user ID from the token
        if (!$user_id) {
            // If no valid user ID, return 401 Unauthorized
            return $this->response->setJSON(['error' => 'Unauthorized'])->setStatusCode(401);
        }
    
        // Retrieve all tasks associated with the user ID
        $taskModel = new TaskModel();
        $tasks = $taskModel->where('user_id', $user_id)->findAll();
    
        // Check if tasks are empty
        if (empty($tasks)) {
            // Return 404 Not Found if no tasks are found
            return $this->response->setJSON(['message' => 'No tasks found'])->setStatusCode(404);
        }
    
        // Format the tasks to match the response structure
        $formattedTasks = array_map(function($task) {
            return [
                'id' => $task['id'],
                'title' => $task['title'],
                'description' => $task['description'],
                'status' => $task['status'],
                'due_date' => $task['due_date'],
            ];
        }, $tasks);
    
        // Return the tasks with 200 OK status
        return $this->response->setJSON(['tasks' => $formattedTasks])->setStatusCode(200);
    }

    public function getTask($id) {
        $user_id = $this->getUserIdFromToken();  // Get user ID from the token
        if (!$user_id) {
            // If no valid user ID, return 401 Unauthorized
            return $this->response->setJSON(['error' => 'Unauthorized'])->setStatusCode(401);
        }
    
        // Retrieve the task for the given ID and user ID
        $taskModel = new TaskModel();
        $task = $taskModel->where(['id' => $id, 'user_id' => $user_id])->first();

        if (!empty($task)) {
            // Format the task to match the response structure
            $formattedTask = [
                'id' => $task['id'],
                'title' => $task['title'],
                'description' => $task['description'],
                'status' => $task['status'],
                'due_date' => $task['due_date'],
            ];
    
            // Return the task with 200 OK status if found
            return $this->response->setJSON(['task' => $formattedTask])->setStatusCode(200);
        }
    
        // Return 404 Not Found if task does not exist
        return $this->response->setJSON(['error' => 'Task not found'])->setStatusCode(404);
    }

    public function updateTask($id) {
        $user_id = $this->getUserIdFromToken();  // Get user ID from the token
        if (!$user_id) {
            // If no valid user ID, return 401 Unauthorized
            return $this->response->setJSON(['error' => 'Unauthorized'])->setStatusCode(401);
        }

        // Find the task by ID and user ID
        $taskModel = new TaskModel();
        $task = $taskModel->where(['id' => $id, 'id' => $user_id])->first();

        if (!$task) {
            // If task not found, return 404 Not Found
            return $this->response->setJSON(['error' => 'Task not found'])->setStatusCode(404);
        }

        // Prepare the updated task data
        $taskData = [
            'title' => $this->request->getVar('title'),
            'description' => $this->request->getVar('description'),
            'status' => $this->request->getVar('status'),
            'due_date' => $this->request->getVar('due_date'),
        ];

        // Update the task and return 200 OK if successful
        if ($taskModel->update($id, $taskData)) {
            return $this->response->setJSON(['task' => $taskData])->setStatusCode(200);
        }

        // Return 500 Internal Server Error if update fails
        return $this->response->setJSON(['error' => 'Unable to update task'])->setStatusCode(500);
    }

    public function deleteTask($id) {
        $user_id = $this->getUserIdFromToken();  // Get user ID from the token
        if (!$user_id) {
            // If no valid user ID, return 401 Unauthorized
            return $this->response->setJSON(['error' => 'Unauthorized'])->setStatusCode(401);
        }

        // Find the task by ID and user ID
        $taskModel = new TaskModel();
        $task = $taskModel->where(['id' => $id, 'id' => $user_id])->first();

        if (!$task) {
            // If task not found, return 404 Not Found
            return $this->response->setJSON(['error' => 'Task not found'])->setStatusCode(404);
        }

        // Delete the task and return 204 No Content if successful
        if ($taskModel->delete($id)) {
            return $this->response->setStatusCode(204);  // 204 No Content (success, no content to return)
        }

        // Return 500 Internal Server Error if deletion fails
        return $this->response->setJSON(['error' => 'Unable to delete task'])->setStatusCode(500);
    }
}
