<?php
// src/Models/Site.php

namespace App\Models;

use App\Database\Connection;
use PDO;

class Site {
    private $db;

    public function __construct() {
        $this->db = Connection::getInstance()->getConnection();
    }

    public function getAll($limit = 100, $offset = 0) {
        $stmt = $this->db->prepare("
            SELECT * FROM sites 
            ORDER BY created_at DESC 
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM sites WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $id = $this->generateId();
        $stmt = $this->db->prepare("
            INSERT INTO sites (id, name, code_site, latitude, longitude, radius_meters, created_at)
            VALUES (:id, :name, :code_site, :latitude, :longitude, :radius_meters, NOW())
        ");
        
        $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'code_site' => $data['code_site'],
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'radius_meters' => $data['radius_meters'] ?? 100
        ]);
        
        return $this->getById($id);
    }

    public function update($id, $data) {
        $fields = [];
        $params = ['id' => $id];
        
        $allowedFields = ['name', 'code_site', 'latitude', 'longitude', 'radius_meters'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return $this->getById($id);
        }
        
        $sql = "UPDATE sites SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $this->getById($id);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM sites WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    private function generateId() {
        return uniqid('site_', true);
    }
}