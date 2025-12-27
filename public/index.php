<?php
// public/index.php

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
use App\Utils\Env;
Env::load(__DIR__ . '/../.env');

// Load configuration
$config = require __DIR__ . '/../config/config.php';

// CORS Middleware
use App\Middleware\CorsMiddleware;

// Configure CORS based on config
if ($config['cors']['enabled']) {
    if ($config['app']['env'] === 'development') {
        CorsMiddleware::enableDevelopmentMode();
    } else {
        CorsMiddleware::enableProductionMode($config['cors']['allowed_origins']);
    }
    CorsMiddleware::handle();
}

use App\Controllers\UserController;
use App\Controllers\SiteController;
use App\Controllers\EquipmentController;
use App\Controllers\PMTaskController;
use App\Controllers\TaskExecutionController;
use App\Controllers\TaskPostponementController;
use App\Controllers\AlertController;
use App\Controllers\ReportController;
use App\Controllers\AuditLogController;
use App\Middleware\AuthMiddleware;
use App\Utils\Response;

// Simple Router
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = trim($path, '/');
$segments = explode('/', $path);

try {
    // ===== PUBLIC ROUTES =====
    
    // Login
    if ($path === 'api/login' && $method === 'POST') {
        $controller = new UserController();
        $controller->login();
        exit;
    }

    // API Info / Health Check
    if ($path === 'api' || $path === 'api/') {
        Response::success([
            'name' => 'e-FME REST API',
            'version' => '1.0.0',
            'status' => 'running',
            'endpoints' => [
                'auth' => '/api/login',
                'users' => '/api/users',
                'sites' => '/api/sites',
                'equipment' => '/api/equipment',
                'tasks' => '/api/tasks',
                'executions' => '/api/executions',
                'postponements' => '/api/postponements',
                'alerts' => '/api/alerts',
                'reports' => '/api/reports',
                'audit-logs' => '/api/audit-logs'
            ]
        ]);
        exit;
    }

    // ===== PROTECTED ROUTES (require authentication) =====
    AuthMiddleware::handle();

    // ===== USERS ROUTES =====
    if ($segments[0] === 'api' && $segments[1] === 'users') {
        $controller = new UserController();
        
        switch ($method) {
            case 'GET':
                if (isset($segments[2])) {
                    $controller->show($segments[2]);
                } else {
                    $controller->index();
                }
                break;
            case 'POST':
                $controller->store();
                break;
            case 'PUT':
                if (!isset($segments[2])) {
                    Response::error('User ID required', 400);
                }
                $controller->update($segments[2]);
                break;
            case 'DELETE':
                if (!isset($segments[2])) {
                    Response::error('User ID required', 400);
                }
                $controller->destroy($segments[2]);
                break;
            default:
                Response::error('Method not allowed', 405);
        }
        exit;
    }

    // ===== SITES ROUTES =====
    if ($segments[0] === 'api' && $segments[1] === 'sites') {
        $controller = new SiteController();
        
        // GET /api/sites/{id}/equipment - Get equipment for a specific site
        if ($method === 'GET' && isset($segments[2]) && isset($segments[3]) && $segments[3] === 'equipment') {
            $equipmentController = new EquipmentController();
            $equipmentController->getBySite($segments[2]);
            exit;
        }
        
        switch ($method) {
            case 'GET':
                if (isset($segments[2])) {
                    $controller->show($segments[2]);
                } else {
                    $controller->index();
                }
                break;
            case 'POST':
                $controller->store();
                break;
            case 'PUT':
                if (!isset($segments[2])) {
                    Response::error('Site ID required', 400);
                }
                $controller->update($segments[2]);
                break;
            case 'DELETE':
                if (!isset($segments[2])) {
                    Response::error('Site ID required', 400);
                }
                $controller->destroy($segments[2]);
                break;
            default:
                Response::error('Method not allowed', 405);
        }
        exit;
    }

    // ===== EQUIPMENT ROUTES =====
    if ($segments[0] === 'api' && $segments[1] === 'equipment') {
        $controller = new EquipmentController();
        
        switch ($method) {
            case 'GET':
                if (isset($segments[2])) {
                    $controller->show($segments[2]);
                } else {
                    $controller->index();
                }
                break;
            case 'POST':
                $controller->store();
                break;
            case 'PUT':
                if (!isset($segments[2])) {
                    Response::error('Equipment ID required', 400);
                }
                $controller->update($segments[2]);
                break;
            case 'DELETE':
                if (!isset($segments[2])) {
                    Response::error('Equipment ID required', 400);
                }
                $controller->destroy($segments[2]);
                break;
            default:
                Response::error('Method not allowed', 405);
        }
        exit;
    }

    // ===== PM TASKS ROUTES =====
    if ($segments[0] === 'api' && $segments[1] === 'tasks') {
        $controller = new PMTaskController();
        
        // POST /api/tasks/{id}/execute - Execute a task
        if ($method === 'POST' && isset($segments[2]) && isset($segments[3]) && $segments[3] === 'execute') {
            $executionController = new TaskExecutionController();
            $executionController->executeTask($segments[2]);
            exit;
        }
        
        // POST /api/tasks/{id}/postpone - Request postponement
        if ($method === 'POST' && isset($segments[2]) && isset($segments[3]) && $segments[3] === 'postpone') {
            $postponementController = new TaskPostponementController();
            $postponementController->requestPostponement($segments[2]);
            exit;
        }
        
        // GET /api/tasks/{id}/history - Get task execution history
        if ($method === 'GET' && isset($segments[2]) && isset($segments[3]) && $segments[3] === 'history') {
            $executionController = new TaskExecutionController();
            $executionController->getTaskHistory($segments[2]);
            exit;
        }
        
        switch ($method) {
            case 'GET':
                if (isset($segments[2])) {
                    $controller->show($segments[2]);
                } else {
                    $controller->index();
                }
                break;
            case 'POST':
                $controller->store();
                break;
            case 'PUT':
                if (!isset($segments[2])) {
                    Response::error('Task ID required', 400);
                }
                $controller->update($segments[2]);
                break;
            case 'DELETE':
                if (!isset($segments[2])) {
                    Response::error('Task ID required', 400);
                }
                $controller->destroy($segments[2]);
                break;
            default:
                Response::error('Method not allowed', 405);
        }
        exit;
    }

    // ===== TASK EXECUTIONS ROUTES =====
    if ($segments[0] === 'api' && $segments[1] === 'executions') {
        $controller = new TaskExecutionController();
        
        switch ($method) {
            case 'GET':
                if (isset($segments[2])) {
                    $controller->show($segments[2]);
                } else {
                    $controller->index();
                }
                break;
            case 'POST':
                $controller->store();
                break;
            case 'PUT':
                if (!isset($segments[2])) {
                    Response::error('Execution ID required', 400);
                }
                $controller->update($segments[2]);
                break;
            case 'DELETE':
                if (!isset($segments[2])) {
                    Response::error('Execution ID required', 400);
                }
                $controller->destroy($segments[2]);
                break;
            default:
                Response::error('Method not allowed', 405);
        }
        exit;
    }

    // ===== POSTPONEMENTS ROUTES =====
    if ($segments[0] === 'api' && $segments[1] === 'postponements') {
        $controller = new TaskPostponementController();
        
        // PUT /api/postponements/{id}/approve - Approve postponement
        if ($method === 'PUT' && isset($segments[2]) && isset($segments[3]) && $segments[3] === 'approve') {
            $controller->approve($segments[2]);
            exit;
        }
        
        // PUT /api/postponements/{id}/reject - Reject postponement
        if ($method === 'PUT' && isset($segments[2]) && isset($segments[3]) && $segments[3] === 'reject') {
            $controller->reject($segments[2]);
            exit;
        }
        
        switch ($method) {
            case 'GET':
                if (isset($segments[2])) {
                    $controller->show($segments[2]);
                } else {
                    $controller->index();
                }
                break;
            case 'POST':
                $controller->store();
                break;
            case 'PUT':
                if (!isset($segments[2])) {
                    Response::error('Postponement ID required', 400);
                }
                $controller->update($segments[2]);
                break;
            case 'DELETE':
                if (!isset($segments[2])) {
                    Response::error('Postponement ID required', 400);
                }
                $controller->destroy($segments[2]);
                break;
            default:
                Response::error('Method not allowed', 405);
        }
        exit;
    }

    // ===== ALERTS ROUTES =====
    if ($segments[0] === 'api' && $segments[1] === 'alerts') {
        $controller = new AlertController();
        
        // PUT /api/alerts/{id}/mark-read - Mark alert as read
        if ($method === 'PUT' && isset($segments[2]) && isset($segments[3]) && $segments[3] === 'mark-read') {
            $controller->markAsRead($segments[2]);
            exit;
        }
        
        // PUT /api/alerts/mark-all-read - Mark all alerts as read
        if ($method === 'PUT' && isset($segments[2]) && $segments[2] === 'mark-all-read') {
            $controller->markAllAsRead();
            exit;
        }
        
        switch ($method) {
            case 'GET':
                if (isset($segments[2])) {
                    $controller->show($segments[2]);
                } else {
                    $controller->index();
                }
                break;
            case 'POST':
                $controller->store();
                break;
            case 'DELETE':
                if (!isset($segments[2])) {
                    Response::error('Alert ID required', 400);
                }
                $controller->destroy($segments[2]);
                break;
            default:
                Response::error('Method not allowed', 405);
        }
        exit;
    }

    // ===== REPORTS ROUTES =====
    if ($segments[0] === 'api' && $segments[1] === 'reports') {
        $controller = new ReportController();
        
        // POST /api/reports/generate - Generate a new report
        if ($method === 'POST' && isset($segments[2]) && $segments[2] === 'generate') {
            $controller->generate();
            exit;
        }
        
        switch ($method) {
            case 'GET':
                if (isset($segments[2])) {
                    $controller->show($segments[2]);
                } else {
                    $controller->index();
                }
                break;
            case 'DELETE':
                if (!isset($segments[2])) {
                    Response::error('Report ID required', 400);
                }
                $controller->destroy($segments[2]);
                break;
            default:
                Response::error('Method not allowed', 405);
        }
        exit;
    }

    // ===== AUDIT LOGS ROUTES =====
    if ($segments[0] === 'api' && $segments[1] === 'audit-logs') {
        $controller = new AuditLogController();
        
        switch ($method) {
            case 'GET':
                if (isset($segments[2])) {
                    $controller->show($segments[2]);
                } else {
                    $controller->index();
                }
                break;
            default:
                Response::error('Method not allowed', 405);
        }
        exit;
    }

    // ===== DASHBOARD / STATISTICS ROUTES =====
    if ($segments[0] === 'api' && $segments[1] === 'dashboard') {
        if ($method === 'GET') {
            $taskController = new PMTaskController();
            $taskController->getDashboardStats();
        } else {
            Response::error('Method not allowed', 405);
        }
        exit;
    }

    // No route matched
    Response::notFound('Endpoint not found');

} catch (Exception $e) {
    Response::error($e->getMessage(), 500);
}