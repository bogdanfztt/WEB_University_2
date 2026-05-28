<?php
require_once 'Storage.php';
require_once 'User.php';
require_once 'UserController.php';
require_once 'validator.php';

class Router {
    private $userController;
    
    public function __construct() {
        $this->userController = new UserController();
    }
    
    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? $_POST['action'] ?? '';
        
        switch ($action) {
            case 'register':
                $this->handleRegister();
                break;
            case 'auth':
                $this->handleAuth();
                break;
            case 'getUser':
                $this->handleGetUser();
                break;
            case 'updateUser':
                $this->handleUpdateUser();
                break;
            default:
                if ($method === 'POST') {
                    $this->handleRegister();
                } else {
                    $this->sendError('Unknown action. Available: register, auth, getUser, updateUser', 400);
                }
        }
    }
    
    private function handleRegister() {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;
        
        $validator = new Validator();
        $errors = $validator->validate($input);
        
        if (!empty($errors)) {
            $this->sendError('Validation failed', 400, $errors);
            return;
        }
        
        $result = $this->userController->create($input);
        
        if ($result) {
            $this->sendResponse([
                'success' => true,
                'message' => 'User created successfully',
                'id' => $result['id'],
                'login' => $result['login'],
                'password' => $result['password'],
                'profile_url' => "/project/api.php?action=getUser&id={$result['id']}"
            ], 201);
        } else {
            $this->sendError('Failed to create user', 400);
        }
    }
    
    private function handleAuth() {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;
        
        $login = $input['login'] ?? '';
        $password = $input['password'] ?? '';
        
        $user = $this->userController->auth($login, $password);
        
        if ($user) {
            $token = base64_encode($user['id'] . ':' . $user['login']);
            $this->sendResponse([
                'success' => true,
                'user' => $user,
                'token' => $token
            ]);
        } else {
            $this->sendError('Invalid credentials', 401);
        }
    }
    
    private function handleGetUser() {
        $id = $_GET['id'] ?? 0;
        $auth = $this->getAuthUser();
        
        if (!$auth || $auth['id'] != $id) {
            $this->sendError('Unauthorized', 401);
            return;
        }
        
        $user = $this->userController->getById($id);
        
        if ($user) {
            $this->sendResponse($user);
        } else {
            $this->sendError('User not found', 404);
        }
    }
    
    private function handleUpdateUser() {
        $id = $_GET['id'] ?? 0;
        $auth = $this->getAuthUser();
        
        if (!$auth || $auth['id'] != $id) {
            $this->sendError('Unauthorized', 401);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            parse_str(file_get_contents('php://input'), $input);
        }
        
        unset($input['login']);
        unset($input['password']);
        
        $validator = new Validator();
        $errors = $validator->validatePartial($input);
        
        if (!empty($errors)) {
            $this->sendError('Validation failed', 400, $errors);
            return;
        }
        
        $result = $this->userController->update($id, $input);
        
        if ($result) {
            $this->sendResponse(['success' => true, 'message' => 'User updated successfully']);
        } else {
            $this->sendError('Update failed', 400);
        }
    }
    
    private function getAuthUser() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
            $decoded = base64_decode($token);
            if ($decoded && strpos($decoded, ':') !== false) {
                list($id, $login) = explode(':', $decoded, 2);
                return ['id' => (int)$id, 'login' => $login];
            }
        }
        
        session_start();
        if (isset($_SESSION['user_id'])) {
            return ['id' => $_SESSION['user_id'], 'login' => $_SESSION['user_login']];
        }
        
        return null;
    }
    
    private function sendResponse($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    private function sendError($message, $status = 400, $details = null) {
        http_response_code($status);
        header('Content-Type: application/json');
        $response = ['error' => $message];
        if ($details) $response['details'] = $details;
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }
}