<?php
// src/Controllers/TaskPostponementController.php

namespace App\Controllers;

use App\Models\TaskPostponement;
use App\Models\PMTask;
use App\Utils\Response;
use App\Middleware\AuthMiddleware;

class TaskPostponementController {
    private $model;
    private $taskModel;

    public function __construct() {
        $this->model = new TaskPostponement();
        $this->taskModel = new PMTask();
    }

    public function index() {
        $filters = [
            'pm_task_id' => $_GET['pm_task_id'] ?? null,
            'approval_status' => $_GET['approval_status'] ?? null,
            'requested_by' => $_GET['requested_by'] ?? null
        ];
        $limit = $_GET['limit'] ?? 100;
        $offset = $_GET['offset'] ?? 0;
        
        $postponements = $this->model->getAll($filters, $limit, $offset);
        Response::success($postponements);
    }

    public function show($id) {
        $postponement = $this->model->getById($id);
        
        if (!$postponement) {
            Response::notFound('Postponement not found');
        }
        
        Response::success($postponement);
    }

    public function requestPostponement($taskId) {
        $task = $this->taskModel->getById($taskId);
        if (!$task) {
            Response::notFound('Task not found');
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $currentUser = AuthMiddleware::getCurrentUser();
        
        if (!isset($data['new_planned_date']) || !isset($data['justification'])) {
            Response::error('new_planned_date and justification are required', 400);
        }
        
        $postponementData = [
            'pm_task_id' => $taskId,
            'requested_by' => $currentUser['user_id'],
            'new_planned_date' => $data['new_planned_date'],
            'justification' => $data['justification'],
            'approval_status' => 'pending'
        ];
        
        $postponement = $this->model->create($postponementData);
        Response::success($postponement, 'Postponement request created successfully', 201);
    }

    public function approve($id) {
        $postponement = $this->model->getById($id);
        if (!$postponement) {
            Response::notFound('Postponement not found');
        }
        
        $currentUser = AuthMiddleware::getCurrentUser();
        
        $updateData = [
            'approval_status' => 'approved',
            'approved_by' => $currentUser['user_id']
        ];
        
        $updatedPostponement = $this->model->approve($id, $updateData);
        
        // Update the task's planned_date
        $this->taskModel->update($postponement['pm_task_id'], [
            'planned_date' => $postponement['new_planned_date']
        ]);
        
        Response::success($updatedPostponement, 'Postponement approved successfully');
    }

    public function reject($id) {
        $postponement = $this->model->getById($id);
        if (!$postponement) {
            Response::notFound('Postponement not found');
        }
        
        $currentUser = AuthMiddleware::getCurrentUser();
        
        $updateData = [
            'approval_status' => 'rejected',
            'approved_by' => $currentUser['user_id']
        ];
        
        $updatedPostponement = $this->model->approve($id, $updateData);
        Response::success($updatedPostponement, 'Postponement rejected successfully');
    }

    public function store() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $required = ['pm_task_id', 'requested_by', 'new_planned_date', 'justification'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                Response::error("Missing required field: $field", 400);
            }
        }
        
        $postponement = $this->model->create($data);
        Response::success($postponement, 'Postponement created successfully', 201);
    }

    public function update($id) {
        $postponement = $this->model->getById($id);
        if (!$postponement) {
            Response::notFound('Postponement not found');
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $updatedPostponement = $this->model->update($id, $data);
        
        Response::success($updatedPostponement, 'Postponement updated successfully');
    }

    public function destroy($id) {
        $postponement = $this->model->getById($id);
        if (!$postponement) {
            Response::notFound('Postponement not found');
        }
        
        $this->model->delete($id);
        Response::success(null, 'Postponement deleted successfully');
    }
}