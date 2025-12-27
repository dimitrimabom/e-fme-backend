<?php
// src/Models/Alert.php

namespace App\Models;

use App\Database\Connection;
use PDO;

class Alert {
    private $db;

    public function __construct() {
        $this->db = Connection::getInstance()->getConnection();
    }

    public function getAll($filters = [], $limit = 100, $offset = 0) {
        $where = [];
        $params = [];
        
        if (!empty($filters['user_id'])) {
            $where[] = 'a.user_id = :user_id';
            $params['user_id'] = $filters['user_id'];
        }
        if (isset($filters['is_read']) && $filters['is_read'] !== null) {
            $where[] = 'a.is_read = :is_read';
            $params['is_read'] = (int)$filters['is_read'];
        }
        if (!empty($filters['type'])) {
            $where[] = 'a.type = :type';
            $params['type'] = $filters['type'];
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $stmt = $this->db->prepare("
            SELECT a.*,
                   u.name as user_name,
                   pt.title as task_title
            FROM alerts a
            LEFT JOIN users u ON a.user_id = u.id
            LEFT JOIN pm_tasks pt ON a.pm_task_id = pt.id
            $whereClause
            ORDER BY a.created_at DESC
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
            SELECT a.*,
                   u.name as user_name,
                   pt.title as task_title
            FROM alerts a
            LEFT JOIN users u ON a.user_id = u.id
            LEFT JOIN pm_tasks pt ON a.pm_task_id = pt.id
            WHERE a.id = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $id = $this->generateId();
        $stmt = $this->db->prepare("
            INSERT INTO alerts (
                id, user_id, pm_task_id, type, is_read, created_at
            ) VALUES (
                :id, :user_id, :pm_task_id, :type, :is_read, NOW()
            )
        ");
        
        $stmt->execute([
            'id' => $id,
            'user_id' => $data['user_id'],
            'pm_task_id' => $data['pm_task_id'] ?? null,
            'type' => $data['type'],
            'is_read' => $data['is_read'] ?? false
        ]);
        
        return $this->getById($id);
    }

    public function markAsRead($id) {
        $stmt = $this->db->prepare("
            UPDATE alerts SET is_read = 1 WHERE id = :id
        ");
        $stmt->execute(['id' => $id]);
        
        return $this->getById($id);
    }

    public function markAllAsRead($userId) {
        $stmt = $this->db->prepare("
            UPDATE alerts SET is_read = 1 WHERE user_id = :user_id AND is_read = 0
        ");
        $stmt->execute(['user_id' => $userId]);
        
        return $stmt->rowCount();
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM alerts WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    private function generateId() {
        return uniqid('alert_', true);
    }
}