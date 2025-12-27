<?php
// src/Controllers/EquipmentController.php

namespace App\Controllers;

use App\Models\Equipment;
use App\Utils\Response;

class EquipmentController {
    private $model;

    public function __construct() {
        $this->model = new Equipment();
    }

    public function index() {
        $filters = [
            'site_id' => $_GET['site_id'] ?? null
        ];
        $limit = $_GET['limit'] ?? 100;
        $offset = $_GET['offset'] ?? 0;
        
        $equipment = $this->model->getAll($filters, $limit, $offset);
        Response::success($equipment);
    }

    public function show($id) {
        $equipment = $this->model->getById($id);
        
        if (!$equipment) {
            Response::notFound('Equipment not found');
        }
        
        Response::success($equipment);
    }

    public function getBySite($siteId) {
        $equipment = $this->model->getBySiteId($siteId);
        Response::success($equipment);
    }

    public function store() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $required = ['name', 'site_id'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                Response::error("Missing required field: $field", 400);
            }
        }
        
        $equipment = $this->model->create($data);
        Response::success($equipment, 'Equipment created successfully', 201);
    }

    public function update($id) {
        $equipment = $this->model->getById($id);
        if (!$equipment) {
            Response::notFound('Equipment not found');
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $updatedEquipment = $this->model->update($id, $data);
        
        Response::success($updatedEquipment, 'Equipment updated successfully');
    }

    public function destroy($id) {
        $equipment = $this->model->getById($id);
        if (!$equipment) {
            Response::notFound('Equipment not found');
        }
        
        $this->model->delete($id);
        Response::success(null, 'Equipment deleted successfully');
    }
}