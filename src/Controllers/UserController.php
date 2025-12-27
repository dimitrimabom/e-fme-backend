<?php
// src/Controllers/UserController.php

namespace App\Controllers;

use App\Models\User;
use App\Utils\Response;
use App\Utils\JWT;

class UserController {
    private $model;

    public function __construct() {
        $this->model = new User();
    }

    public function index() {
        $limit = $_GET['limit'] ?? 100;
        $offset = $_GET['offset'] ?? 0;
        
        $users = $this->model->getAll($limit, $offset);
        Response::success($users);
    }

    public function show($id) {
        $user = $this->model->getById($id);
        
        if (!$user) {
            Response::notFound('User not found');
        }
        
        Response::success($user);
    }

    public function store() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validation
        $validator = new \App\Utils\Validator($data);
        $validator->required(['name', 'email', 'password'])
                  ->min('name', 3)
                  ->max('name', 100)
                  ->email('email')
                  ->min('password', 8)
                  ->in('role', ['admin', 'manager', 'technician', 'user']);
        
        if ($validator->fails()) {
            Response::error('Validation failed', 422, $validator->getErrors());
        }
        
        // Vérifier l'unicité de l'email
        $existingUser = $this->model->getByEmail($data['email']);
        if ($existingUser) {
            Response::error('Email already exists', 409);
        }
        
        $user = $this->model->create($data);
        Response::success($user, 'User created successfully', 201);
    }

    public function update($id) {
        $user = $this->model->getById($id);
        if (!$user) {
            Response::notFound('User not found');
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            Response::error('Invalid email format', 400);
        }
        
        $updatedUser = $this->model->update($id, $data);
        Response::success($updatedUser, 'User updated successfully');
    }

    public function destroy($id) {
        $user = $this->model->getById($id);
        if (!$user) {
            Response::notFound('User not found');
        }
        
        $this->model->delete($id);
        Response::success(null, 'User deleted successfully');
    }

    public function login() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['email']) || !isset($data['password'])) {
            Response::error('Email and password are required', 400);
        }
        
        $user = $this->model->getByEmail($data['email']);
        
        if (!$user || !password_verify($data['password'], $user['password_hash'])) {
            Response::error('Invalid credentials', 401);
        }
        
        if (!$user['is_active']) {
            Response::error('Account is inactive', 403);
        }
        
        $payload = [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'exp' => time() + (24 * 60 * 60) // 24 hours
        ];
        
        $token = JWT::encode($payload);
        
        Response::success([
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ], 'Login successful');
    }
}