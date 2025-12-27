<?php
// src/Models/Report.php

namespace App\Models;

use App\Database\Connection;
use PDO;

class Report {
    private $db;

    public function __construct() {
        $this->db = Connection::getInstance()->getConnection();
    }

    public function getAll($filters = [], $limit = 100, $offset = 0) {
        $where = [];
        $params = [];
        
        if (!empty($filters['type'])) {
            $where[] = 'r.type = :type';
            $params['type'] = $filters['type'];
        }
        if (!empty($filters['generated_by'])) {
            $where[] = 'r.generated_by = :generated_by';
            $params['generated_by'] = $filters['generated_by'];
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $stmt = $this->db->prepare("
            SELECT r.*,
                   u.name as generated_by_name
            FROM reports r
            LEFT JOIN users u ON r.generated_by = u.id
            $whereClause
            ORDER BY r.generated_at DESC
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
            SELECT r.*,
                   u.name as generated_by_name
            FROM reports r
            LEFT JOIN users u ON r.generated_by = u.id
            WHERE r.id = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $id = $this->generateId();
        $stmt = $this->db->prepare("
            INSERT INTO reports (
                id, type, generated_by, file_path, generated_at
            ) VALUES (
                :id, :type, :generated_by, :file_path, NOW()
            )
        ");
        
        $stmt->execute([
            'id' => $id,
            'type' => $data['type'],
            'generated_by' => $data['generated_by'],
            'file_path' => $data['file_path'] ?? null
        ]);
        
        return $this->getById($id);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM reports WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    private function generateId() {
        return uniqid('report_', true);
    }
}