<?php
// src/Controllers/ReportController.php

namespace App\Controllers;

use App\Models\Report;
use App\Utils\Response;
use App\Middleware\AuthMiddleware;

class ReportController {
    private $model;

    public function __construct() {
        $this->model = new Report();
    }

    public function index() {
        $filters = [
            'type' => $_GET['type'] ?? null,
            'generated_by' => $_GET['generated_by'] ?? null
        ];
        $limit = $_GET['limit'] ?? 100;
        $offset = $_GET['offset'] ?? 0;
        
        $reports = $this->model->getAll($filters, $limit, $offset);
        Response::success($reports);
    }

    public function show($id) {
        $report = $this->model->getById($id);
        
        if (!$report) {
            Response::notFound('Report not found');
        }
        
        Response::success($report);
    }

    public function generate() {
        $data = json_decode(file_get_contents('php://input'), true);
        $currentUser = AuthMiddleware::getCurrentUser();
        
        if (!isset($data['type'])) {
            Response::error('Report type is required', 400);
        }
        
        $reportData = [
            'type' => $data['type'],
            'generated_by' => $currentUser['user_id'],
            'file_path' => $this->generateReportFile($data)
        ];
        
        $report = $this->model->create($reportData);
        Response::success($report, 'Report generated successfully', 201);
    }

    private function generateReportFile($data) {
        // Logic to generate report file
        // This would typically create a PDF or Excel file
        $filename = 'report_' . uniqid() . '_' . date('Y-m-d') . '.pdf';
        return '/reports/' . $filename;
    }

    public function destroy($id) {
        $report = $this->model->getById($id);
        if (!$report) {
            Response::notFound('Report not found');
        }
        
        // Delete file if exists
        if ($report['file_path'] && file_exists(__DIR__ . '/../../public' . $report['file_path'])) {
            unlink(__DIR__ . '/../../public' . $report['file_path']);
        }
        
        $this->model->delete($id);
        Response::success(null, 'Report deleted successfully');
    }
}