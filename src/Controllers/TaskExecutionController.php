<?php
// src/Controllers/TaskExecutionController.php

namespace App\Controllers;

use App\Models\TaskExecution;
use App\Models\PMTask;
use App\Utils\Response;
use App\Middleware\AuthMiddleware;

class TaskExecutionController {
    private $model;
    private $taskModel;

    public function __construct() {
        $this->model = new TaskExecution();
        $this->taskModel = new PMTask();
    }

    public function index() {
        $filters = [
            'pm_task_id' => $_GET['pm_task_id'] ?? null,
            'executed_by' => $_GET['executed_by'] ?? null
        ];
        $limit = $_GET['limit'] ?? 100;
        $offset = $_GET['offset'] ?? 0;
        
        $executions = $this->model->getAll($filters, $limit, $offset);
        Response::success($executions);
    }

    public function show($id) {
        $execution = $this->model->getById($id);
        
        if (!$execution) {
            Response::notFound('Execution not found');
        }
        
        Response::success($execution);
    }

    public function getTaskHistory($taskId) {
        $executions = $this->model->getByTaskId($taskId);
        Response::success($executions);
    }

    public function executeTask($taskId) {
        $task = $this->taskModel->getById($taskId);
        if (!$task) {
            Response::notFound('Task not found');
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $currentUser = AuthMiddleware::getCurrentUser();
        
        $executionData = [
            'pm_task_id' => $taskId,
            'executed_by' => $currentUser['user_id'],
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'comment' => $data['comment'] ?? null,
            'synced' => $data['synced'] ?? true
        ];
        
        $execution = $this->model->create($executionData);
        
        // Update task status to completed
        $this->taskModel->update($taskId, ['status' => 'completed']);
        
        Response::success($execution, 'Task executed successfully', 201);
    }

    public function store() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $required = ['pm_task_id', 'executed_by'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                Response::error("Missing required field: $field", 400);
            }
        }
        
        $execution = $this->model->create($data);
        Response::success($execution, 'Execution created successfully', 201);
    }

    public function update($id) {
        $execution = $this->model->getById($id);
        if (!$execution) {
            Response::notFound('Execution not found');
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $updatedExecution = $this->model->update($id, $data);
        
        Response::success($updatedExecution, 'Execution updated successfully');
    }

    public function destroy($id) {
        $execution = $this->model->getById($id);
        if (!$execution) {
            Response::notFound('Execution not found');
        }
        
        $this->model->delete($id);
        Response::success(null, 'Execution deleted successfully');
    }
}