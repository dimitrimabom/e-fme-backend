<?php
// src/Models/PMTask.php

namespace App\Models;

use App\Database\Connection;
use PDO;

class PMTask {
    private $db;

    public function __construct() {
        $this->db = Connection::getInstance()->getConnection();
    }

    public function getAll($filters = [], $limit = 100, $offset = 0) {
        $where = [];
        $params = [];
        
        if (!empty($filters['status'])) {
            $where[] = 'status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['site_id'])) {
            $where[] = 'site_id = :site_id';
            $params['site_id'] = $filters['site_id'];
        }
        if (!empty($filters['assigned_to'])) {
            $where[] = 'assigned_to = :assigned_to';
            $params['assigned_to'] = $filters['assigned_to'];
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $stmt = $this->db->prepare("
            SELECT pm.*, 
                   s.name as site_name, 
                   e.name as equipment_name,
                   u.name as assigned_to_name
            FROM pm_tasks pm
            LEFT JOIN sites s ON pm.site_id = s.id
            LEFT JOIN equipment e ON pm.equipment_id = e.id
            LEFT JOIN users u ON pm.assigned_to = u.id
            $whereClause
            ORDER BY pm.planned_date DESC
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT pm.*, 
                   s.name as site_name, 
                   e.name as equipment_name,
                   u.name as assigned_to_name
            FROM pm_tasks pm
            LEFT JOIN sites s ON pm.site_id = s.id
            LEFT JOIN equipment e ON pm.equipment_id = e.id
            LEFT JOIN users u ON pm.assigned_to = u.id
            WHERE pm.id = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $id = $this->generateId();
        $stmt = $this->db->prepare("
            INSERT INTO pm_tasks (
                id, title, description, site_id, equipment_id, 
                assigned_to, planned_date, status, priority, 
                created_by, created_at, updated_at
            ) VALUES (
                :id, :title, :description, :site_id, :equipment_id,
                :assigned_to, :planned_date, :status, :priority,
                :created_by, NOW(), NOW()
            )
        ");
        
        $stmt->execute([
            'id' => $id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'site_id' => $data['site_id'],
            'equipment_id' => $data['equipment_id'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? null,
            'planned_date' => $data['planned_date'],
            'status' => $data['status'] ?? 'pending',
            'priority' => $data['priority'] ?? 'medium',
            'created_by' => $data['created_by']
        ]);
        
        return $this->getById($id);
    }

    public function update($id, $data) {
        $fields = [];
        $params = ['id' => $id];
        
        $allowedFields = [
            'title', 'description', 'site_id', 'equipment_id',
            'assigned_to', 'planned_date', 'status', 'priority'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }
        
        $fields[] = 'updated_at = NOW()';
        
        $sql = "UPDATE pm_tasks SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $this->getById($id);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM pm_tasks WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function getStatistics() {
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total_tasks,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_tasks,
                SUM(CASE WHEN planned_date < CURDATE() AND status != 'completed' THEN 1 ELSE 0 END) as overdue_tasks,
                SUM(CASE WHEN planned_date = CURDATE() AND status != 'completed' THEN 1 ELSE 0 END) as due_today
            FROM pm_tasks
        ");
        
        return $stmt->fetch();
    }

    private function generateId() {
        return uniqid('task_', true);
    }
}