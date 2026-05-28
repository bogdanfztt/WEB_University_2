<?php
class Validator {
    public function validate($data) {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors['name'] = 'Имя обязательно';
        } elseif (strlen($data['name']) < 2) {
            $errors['name'] = 'Имя минимум 2 символа';
        }
        
        if (empty($data['phone'])) {
            $errors['phone'] = 'Телефон обязателен';
        } else {
            $phoneClean = preg_replace('/[^0-9]/', '', $data['phone']);
            if (strlen($phoneClean) < 10) {
                $errors['phone'] = 'Некорректный телефон';
            }
        }
        
        if (empty($data['email'])) {
            $errors['email'] = 'Email обязателен';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Некорректный email';
        }
        
        return $errors;
    }
    
    public function validatePartial($data) {
        $errors = [];
        
        if (isset($data['name']) && strlen($data['name']) < 2) {
            $errors['name'] = 'Имя минимум 2 символа';
        }
        
        if (isset($data['phone'])) {
            $phoneClean = preg_replace('/[^0-9]/', '', $data['phone']);
            if (strlen($phoneClean) < 10) {
                $errors['phone'] = 'Некорректный телефон';
            }
        }
        
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Некорректный email';
        }
        
        return $errors;
    }
}