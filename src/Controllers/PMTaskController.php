<?php
// src/Controllers/PMTaskController.php

namespace App\Controllers;

use App\Models\PMTask;
use App\Utils\Response;

class PMTaskController {
    private $model;

    public function __construct() {
        $this->model = new PMTask();
    }

    public function index() {
        $filters = [
            'status' => $_GET['status'] ?? null,
            'site_id' => $_GET['site_id'] ?? null,
            'assigned_to' => $_GET['assigned_to'] ?? null
        ];
        
        $limit = $_GET['limit'] ?? 100;
        $offset = $_GET['offset'] ?? 0;
        
        $tasks = $this->model->getAll($filters, $limit, $offset);
        Response::success($tasks);
    }

    public function show($id) {
        $task = $this->model->getById($id);
        
        if (!$task) {
            Response::notFound('Task not found');
        }
        
        Response::success($task);
    }

    public function store() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $required = ['title', 'site_id', 'planned_date', 'created_by'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                Response::error("Missing required field: $field", 400);
            }
        }
        
        $task = $this->model->create($data);
        Response::success($task, 'Task created successfully', 201);
    }

    public function update($id) {
        $task = $this->model->getById($id);
        if (!$task) {
            Response::notFound('Task not found');
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $updatedTask = $this->model->update($id, $data);
        
        Response::success($updatedTask, 'Task updated successfully');
    }

    public function destroy($id) {
        $task = $this->model->getById($id);
        if (!$task) {
            Response::notFound('Task not found');
        }
        
        $this->model->delete($id);
        Response::success(null, 'Task deleted successfully');
    }

    public function getDashboardStats() {
        $stats = $this->model->getStatistics();
        Response::success($stats);
    }
}