<?php
// src/Models/TaskExecution.php

namespace App\Models;

use App\Database\Connection;
use PDO;

class TaskExecution {
    private $db;

    public function __construct() {
        $this->db = Connection::getInstance()->getConnection();
    }

    public function getAll($filters = [], $limit = 100, $offset = 0) {
        $where = [];
        $params = [];
        
        if (!empty($filters['pm_task_id'])) {
            $where[] = 'te.pm_task_id = :pm_task_id';
            $params['pm_task_id'] = $filters['pm_task_id'];
        }
        if (!empty($filters['executed_by'])) {
            $where[] = 'te.executed_by = :executed_by';
            $params['executed_by'] = $filters['executed_by'];
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $stmt = $this->db->prepare("
            SELECT te.*,
                   pt.title as task_title,
                   u.name as executed_by_name
            FROM task_execution te
            LEFT JOIN pm_tasks pt ON te.pm_task_id = pt.id
            LEFT JOIN users u ON te.executed_by = u.id
            $whereClause
            ORDER BY te.execution_date DESC
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
            SELECT te.*,
                   pt.title as task_title,
                   u.name as executed_by_name
            FROM task_execution te
            LEFT JOIN pm_tasks pt ON te.pm_task_id = pt.id
            LEFT JOIN users u ON te.executed_by = u.id
            WHERE te.id = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function getByTaskId($taskId) {
        $stmt = $this->db->prepare("
            SELECT te.*,
                   u.name as executed_by_name
            FROM task_execution te
            LEFT JOIN users u ON te.executed_by = u.id
            WHERE te.pm_task_id = :task_id
            ORDER BY te.execution_date DESC
        ");
        $stmt->execute(['task_id' => $taskId]);
        return $stmt->fetchAll();
    }

    public function create($data) {
        $id = $this->generateId();
        $stmt = $this->db->prepare("
            INSERT INTO task_execution (
                id, pm_task_id, executed_by, execution_date,
                latitude, longitude, comment, synced, created_at
            ) VALUES (
                :id, :pm_task_id, :executed_by, NOW(),
                :latitude, :longitude, :comment, :synced, NOW()
            )
        ");
        
        $stmt->execute([
            'id' => $id,
            'pm_task_id' => $data['pm_task_id'],
            'executed_by' => $data['executed_by'],
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'comment' => $data['comment'] ?? null,
            'synced' => $data['synced'] ?? true
        ]);
        
        return $this->getById($id);
    }

    public function update($id, $data) {
        $fields = [];
        $params = ['id' => $id];
        
        $allowedFields = ['latitude', 'longitude', 'comment', 'synced'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return $this->getById($id);
        }
        
        $sql = "UPDATE task_execution SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $this->getById($id);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM task_execution WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    private function generateId() {
        return uniqid('exec_', true);
    }
}