<?php
require_once __DIR__ . '/../utils/jwt.php';

function getBearerToken() {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
            return $matches[1];
        }
    }
    throw new Exception('No token provided', 401);
}

function validateJWT($token) {
    try {
        $decoded = decodeJWT($token);
        return !empty($decoded->sub);
    } catch (Exception $e) {
        return false;
    }
}