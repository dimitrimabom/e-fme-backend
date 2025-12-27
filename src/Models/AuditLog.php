<?php
// src/Models/AuditLog.php

namespace App\Models;

use App\Database\Connection;
use PDO;

class AuditLog {
    private $db;

    public function __construct() {
        $this->db = Connection::getInstance()->getConnection();
    }

    public function getAll($filters = [], $limit = 100, $offset = 0) {
        $where = [];
        $params = [];
        
        if (!empty($filters['user_id'])) {
            $where[] = 'al.user_id = :user_id';
            $params['user_id'] = $filters['user_id'];
        }
        if (!empty($filters['action'])) {
            $where[] = 'al.action = :action';
            $params['action'] = $filters['action'];
        }
        if (!empty($filters['entity'])) {
            $where[] = 'al.entity = :entity';
            $params['entity'] = $filters['entity'];
        }
        if (!empty($filters['entity_id'])) {
            $where[] = 'al.entity_id = :entity_id';
            $params['entity_id'] = $filters['entity_id'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'al.created_at >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'al.created_at <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $stmt = $this->db->prepare("
            SELECT al.*,
                   u.name as user_name,
                   u.email as user_email
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            $whereClause
            ORDER BY al.created_at DESC
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
            SELECT al.*,
                   u.name as user_name,
                   u.email as user_email
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE al.id = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $id = $this->generateId();
        $stmt = $this->db->prepare("
            INSERT INTO audit_logs (
                id, user_id, action, entity, entity_id, created_at
            ) VALUES (
                :id, :user_id, :action, :entity, :entity_id, NOW()
            )
        ");
        
        $stmt->execute([
            'id' => $id,
            'user_id' => $data['user_id'],
            'action' => $data['action'],
            'entity' => $data['entity'],
            'entity_id' => $data['entity_id'] ?? null
        ]);
        
        return $this->getById($id);
    }

    private function generateId() {
        return uniqid('audit_', true);
    }
}