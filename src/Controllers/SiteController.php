<?php
// src/Controllers/SiteController.php

namespace App\Controllers;

use App\Models\Site;
use App\Utils\Response;

class SiteController {
    private $model;

    public function __construct() {
        $this->model = new Site();
    }

    public function index() {
        $limit = $_GET['limit'] ?? 100;
        $offset = $_GET['offset'] ?? 0;
        
        $sites = $this->model->getAll($limit, $offset);
        Response::success($sites);
    }

    public function show($id) {
        $site = $this->model->getById($id);
        
        if (!$site) {
            Response::notFound('Site not found');
        }
        
        Response::success($site);
    }

    public function store() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $required = ['name', 'code_site'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                Response::error("Missing required field: $field", 400);
            }
        }
        
        $site = $this->model->create($data);
        Response::success($site, 'Site created successfully', 201);
    }

    public function update($id) {
        $site = $this->model->getById($id);
        if (!$site) {
            Response::notFound('Site not found');
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $updatedSite = $this->model->update($id, $data);
        
        Response::success($updatedSite, 'Site updated successfully');
    }

    public function destroy($id) {
        $site = $this->model->getById($id);
        if (!$site) {
            Response::notFound('Site not found');
        }
        
        $this->model->delete($id);
        Response::success(null, 'Site deleted successfully');
    }
}