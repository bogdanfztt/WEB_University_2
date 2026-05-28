<?php
class Storage {
    private static $instance = null;
    private $dataFile;
    private $users = [];
    
    private function __construct() {
        $this->dataFile = __DIR__ . '/users.json';
        $this->load();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Storage();
        }
        return self::$instance;
    }
    
    private function load() {
        if (file_exists($this->dataFile)) {
            $content = file_get_contents($this->dataFile);
            $data = json_decode($content, true);
            $this->users = $data['users'] ?? [];
        } else {
            $this->users = [];
        }
    }
    
    private function save() {
        $data = ['users' => $this->users];
        file_put_contents($this->dataFile, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    public function createUser($userData) {
        $maxId = 0;
        foreach ($this->users as $user) {
            if ($user['id'] > $maxId) $maxId = $user['id'];
        }
        
        $userData['id'] = $maxId + 1;
        $userData['created_at'] = date('Y-m-d H:i:s');
        $userData['updated_at'] = date('Y-m-d H:i:s');
        
        $this->users[] = $userData;
        $this->save();
        
        return $userData;
    }
    
    public function updateUser($id, $updatedData) {
        foreach ($this->users as $key => $user) {
            if ($user['id'] == $id) {
                foreach ($updatedData as $field => $value) {
                    if ($field !== 'id' && $field !== 'login' && $field !== 'password') {
                        $this->users[$key][$field] = $value;
                    }
                }
                $this->users[$key]['updated_at'] = date('Y-m-d H:i:s');
                $this->save();
                return true;
            }
        }
        return false;
    }
    
    public function findUserById($id) {
        foreach ($this->users as $user) {
            if ($user['id'] == $id) {
                return $user;
            }
        }
        return null;
    }
    
    public function findUserByLogin($login) {
        foreach ($this->users as $user) {
            if ($user['login'] === $login) {
                return $user;
            }
        }
        return null;
    }
    
    public function getAllUsers() {
        return $this->users;
    }
}