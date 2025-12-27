<?php
// src/Models/Equipment.php

namespace App\Models;

use App\Database\Connection;
use PDO;

class Equipment {
    private $db;

    public function __construct() {
        $this->db = Connection::getInstance()->getConnection();
    }

    public function getAll($filters = [], $limit = 100, $offset = 0) {
        $where = [];
        $params = [];
        
        if (!empty($filters['site_id'])) {
            $where[] = 'e.site_id = :site_id';
            $params['site_id'] = $filters['site_id'];
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $stmt = $this->db->prepare("
            SELECT e.*, s.name as site_name, s.code_site
            FROM equipment e
            LEFT JOIN sites s ON e.site_id = s.id
            $whereClause
            ORDER BY e.created_at DESC
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
            SELECT e.*, s.name as site_name, s.code_site
            FROM equipment e
            LEFT JOIN sites s ON e.site_id = s.id
            WHERE e.id = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function getBySiteId($siteId) {
        $stmt = $this->db->prepare("
            SELECT * FROM equipment WHERE site_id = :site_id ORDER BY name
        ");
        $stmt->execute(['site_id' => $siteId]);
        return $stmt->fetchAll();
    }

    public function create($data) {
        $id = $this->generateId();
        $stmt = $this->db->prepare("
            INSERT INTO equipment (id, site_id, name, reference, created_at)
            VALUES (:id, :site_id, :name, :reference, NOW())
        ");
        
        $stmt->execute([
            'id' => $id,
            'site_id' => $data['site_id'],
            'name' => $data['name'],
            'reference' => $data['reference'] ?? null
        ]);
        
        return $this->getById($id);
    }

    public function update($id, $data) {
        $fields = [];
        $params = ['id' => $id];
        
        $allowedFields = ['site_id', 'name', 'reference'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return $this->getById($id);
        }
        
        $sql = "UPDATE equipment SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $this->getById($id);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM equipment WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    private function generateId() {
        return uniqid('equip_', true);
    }
}