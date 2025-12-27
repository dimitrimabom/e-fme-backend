<?php
// src/Controllers/AlertController.php

namespace App\Controllers;

use App\Models\Alert;
use App\Utils\Response;
use App\Middleware\AuthMiddleware;

class AlertController {
    private $model;

    public function __construct() {
        $this->model = new Alert();
    }

    public function index() {
        $currentUser = AuthMiddleware::getCurrentUser();
        
        $filters = [
            'user_id' => $_GET['user_id'] ?? $currentUser['user_id'],
            'is_read' => $_GET['is_read'] ?? null,
            'type' => $_GET['type'] ?? null
        ];
        $limit = $_GET['limit'] ?? 100;
        $offset = $_GET['offset'] ?? 0;
        
        $alerts = $this->model->getAll($filters, $limit, $offset);
        Response::success($alerts);
    }

    public function show($id) {
        $alert = $this->model->getById($id);
        
        if (!$alert) {
            Response::notFound('Alert not found');
        }
        
        Response::success($alert);
    }

    public function markAsRead($id) {
        $alert = $this->model->getById($id);
        if (!$alert) {
            Response::notFound('Alert not found');
        }
        
        $updatedAlert = $this->model->markAsRead($id);
        Response::success($updatedAlert, 'Alert marked as read');
    }

    public function markAllAsRead() {
        $currentUser = AuthMiddleware::getCurrentUser();
        $count = $this->model->markAllAsRead($currentUser['user_id']);
        
        Response::success([
            'marked_count' => $count
        ], 'All alerts marked as read');
    }

    public function store() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $required = ['user_id', 'type'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                Response::error("Missing required field: $field", 400);
            }
        }
        
        $alert = $this->model->create($data);
        Response::success($alert, 'Alert created successfully', 201);
    }

    public function destroy($id) {
        $alert = $this->model->getById($id);
        if (!$alert) {
            Response::notFound('Alert not found');
        }
        
        $this->model->delete($id);
        Response::success(null, 'Alert deleted successfully');
    }
}