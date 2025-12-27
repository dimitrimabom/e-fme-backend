<?php
// src/Middleware/AuthMiddleware.php

namespace App\Middleware;

use App\Utils\Response;
use App\Utils\JWT;

class AuthMiddleware {
    public static function handle() {
        $headers = getallheaders();
        
        if (!isset($headers['Authorization'])) {
            Response::unauthorized('No token provided');
        }
        
        $authHeader = $headers['Authorization'];
        
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            Response::unauthorized('Invalid token format');
        }
        
        $token = $matches[1];
        $payload = JWT::decode($token);
        
        if (!$payload) {
            Response::unauthorized('Invalid or expired token');
        }
        
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            Response::unauthorized('Token has expired');
        }
        
        // Store user info in global scope
        $GLOBALS['current_user'] = $payload;
        
        return true;
    }

    public static function getCurrentUser() {
        return $GLOBALS['current_user'] ?? null;
    }
}