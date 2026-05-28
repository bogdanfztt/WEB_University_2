<?php
require_once 'User.php';

class UserController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    public function create($data) {
        return $this->userModel->create($data);
    }
    
    public function update($id, $data) {
        return $this->userModel->update($id, $data);
    }
    
    public function getById($id) {
        return $this->userModel->findById($id);
    }
    
    public function auth($login, $password) {
        return $this->userModel->authenticate($login, $password);
    }
}