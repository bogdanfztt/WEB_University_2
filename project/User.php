<?php
require_once 'Storage.php';

class User {
    private $storage;
    
    public function __construct() {
        $this->storage = Storage::getInstance();
    }
    
    public function create($data) {
        $login = $this->generateLogin($data['name'] ?? 'user');
        $password = $this->generatePassword();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $userData = [
            'login' => $login,
            'password' => $hashedPassword,
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'message' => $data['message'] ?? ''
        ];
        
        $user = $this->storage->createUser($userData);
        
        return [
            'id' => $user['id'],
            'login' => $login,
            'password' => $password
        ];
    }
    
    public function update($id, $data) {
        return $this->storage->updateUser($id, $data);
    }
    
    public function findById($id) {
        $user = $this->storage->findUserById($id);
        if ($user) {
            unset($user['password']);
        }
        return $user;
    }
    
    public function authenticate($login, $password) {
        $user = $this->storage->findUserByLogin($login);
        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']);
            return $user;
        }
        return null;
    }
    
    private function generateLogin($name) {
        $base = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $name)));
        if (empty($base)) {
            $base = 'user';
        }
        $login = $base;
        $counter = 1;
        
        while ($this->storage->findUserByLogin($login)) {
            $login = $base . $counter;
            $counter++;
        }
        
        return $login;
    }
    
    private function generatePassword($length = 10) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
        return substr(str_shuffle($chars), 0, $length);
    }
}