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
        return !empty($decoded['sub']);
    } catch (Exception $e) {
        throw new Exception($e->getMessage(), 401);
    }
}

// combina getBearerToken y validateJWT
function verificarJWT() {
    try {
        $token = getBearerToken();
        return validateJWT($token);
    } catch (Exception $e) {
        throw new Exception('Unauthorized: ' . $e->getMessage(), 401);
    }
}