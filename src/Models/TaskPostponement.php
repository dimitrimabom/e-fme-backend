<?php
// src/Models/TaskPostponement.php

namespace App\Models;

use App\Database\Connection;
use PDO;

class TaskPostponement {
    private $db;

    public function __construct() {
        $this->db = Connection::getInstance()->getConnection();
    }

    public function getAll($filters = [], $limit = 100, $offset = 0) {
        $where = [];
        $params = [];
        
        if (!empty($filters['pm_task_id'])) {
            $where[] = 'tp.pm_task_id = :pm_task_id';
            $params['pm_task_id'] = $filters['pm_task_id'];
        }
        if (!empty($filters['approval_status'])) {
            $where[] = 'tp.approval_status = :approval_status';
            $params['approval_status'] = $filters['approval_status'];
        }
        if (!empty($filters['requested_by'])) {
            $where[] = 'tp.requested_by = :requested_by';
            $params['requested_by'] = $filters['requested_by'];
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $stmt = $this->db->prepare("
            SELECT tp.*,
                   pt.title as task_title,
                   u1.name as requested_by_name,
                   u2.name as approved_by_name
            FROM task_postponement tp
            LEFT JOIN pm_tasks pt ON tp.pm_task_id = pt.id
            LEFT JOIN users u1 ON tp.requested_by = u1.id
            LEFT JOIN users u2 ON tp.approved_by = u2.id
            $whereClause
            ORDER BY tp.requested_date DESC
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
            SELECT tp.*,
                   pt.title as task_title,
                   u1.name as requested_by_name,
                   u2.name as approved_by_name
            FROM task_postponement tp
            LEFT JOIN pm_tasks pt ON tp.pm_task_id = pt.id
            LEFT JOIN users u1 ON tp.requested_by = u1.id
            LEFT JOIN users u2 ON tp.approved_by = u2.id
            WHERE tp.id = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $id = $this->generateId();
        $stmt = $this->db->prepare("
            INSERT INTO task_postponement (
                id, pm_task_id, requested_by, requested_date,
                new_planned_date, justification, approval_status
            ) VALUES (
                :id, :pm_task_id, :requested_by, NOW(),
                :new_planned_date, :justification, :approval_status
            )
        ");
        
        $stmt->execute([
            'id' => $id,
            'pm_task_id' => $data['pm_task_id'],
            'requested_by' => $data['requested_by'],
            'new_planned_date' => $data['new_planned_date'],
            'justification' => $data['justification'],
            'approval_status' => $data['approval_status'] ?? 'pending'
        ]);
        
        return $this->getById($id);
    }

    public function approve($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE task_postponement 
            SET approval_status = :approval_status,
                approved_by = :approved_by,
                approved_at = NOW()
            WHERE id = :id
        ");
        
        $stmt->execute([
            'id' => $id,
            'approval_status' => $data['approval_status'],
            'approved_by' => $data['approved_by']
        ]);
        
        return $this->getById($id);
    }

    public function update($id, $data) {
        $fields = [];
        $params = ['id' => $id];
        
        $allowedFields = ['new_planned_date', 'justification', 'approval_status'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return $this->getById($id);
        }
        
        $sql = "UPDATE task_postponement SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $this->getById($id);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM task_postponement WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    private function generateId() {
        return uniqid('postpone_', true);
    }
}