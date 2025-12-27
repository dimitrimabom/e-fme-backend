<?php
// src/Controllers/AuditLogController.php

namespace App\Controllers;

use App\Models\AuditLog;
use App\Utils\Response;

class AuditLogController {
    private $model;

    public function __construct() {
        $this->model = new AuditLog();
    }

    public function index() {
        $filters = [
            'user_id' => $_GET['user_id'] ?? null,
            'action' => $_GET['action'] ?? null,
            'entity' => $_GET['entity'] ?? null,
            'entity_id' => $_GET['entity_id'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null
        ];
        $limit = $_GET['limit'] ?? 100;
        $offset = $_GET['offset'] ?? 0;
        
        $logs = $this->model->getAll($filters, $limit, $offset);
        Response::success($logs);
    }

    public function show($id) {
        $log = $this->model->getById($id);
        
        if (!$log) {
            Response::notFound('Audit log not found');
        }
        
        Response::success($log);
    }
}