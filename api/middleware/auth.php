<?php
require_once __DIR__ . '/../utils/jwt.php';

function getBearerToken() {
    $headers = getallheaders();
    foreach ($headers as $key => $value) {
        if (strtolower($key) === 'authorization') {
            if (preg_match('/Bearer\s(\S+)/', $value, $matches)) {
                return $matches[1];
            }
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
function verificarJWT($requiredRole = null) {
    try {
        $token = getBearerToken();
        $decoded = decodeJWT($token);

        // Validar que el token tenga 'sub' (ID de usuario) y 'rol'
        if (!isset($decoded['sub']) || !isset($decoded['rol'])) {
            throw new Exception('Token inválido', 401);
        }

        // Si se requiere un rol específico, validarlo
        if ($requiredRole && $decoded['rol'] !== $requiredRole) {
            throw new Exception('Acceso no autorizado', 403);
        }

        return $decoded; // Devuelve el payload con ID y rol
    } catch (Exception $e) {
        throw new Exception('Unauthorized: ' . $e->getMessage(), 401);
    }
}
