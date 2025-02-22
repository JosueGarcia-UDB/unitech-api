<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Para debug - comentar en producción
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/routes.php';
require_once __DIR__ . '/utils/json_handler.php';
require_once __DIR__ . '/middleware/auth.php';

// Obtener el método HTTP y la ruta solicitada
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = trim($uri, '/');
$uri = explode('/', $uri);

// Eliminar 'unitech-api' y 'api' del inicio de la URI si existen
if (isset($uri[0]) && $uri[0] === 'unitech-api') {
    array_shift($uri);
}
if (isset($uri[0]) && $uri[0] === 'api') {
    array_shift($uri);
}

error_log("URI procesada: " . print_r($uri, true));

// Enrutamiento
try {
    $route = matchRoute($method, $uri);
    if ($route) {
        // Verificar si la ruta requiere autenticación
        if ($route['requiresAuth']) {
            $token = getBearerToken();
            if (!validateJWT($token)) {
                throw new Exception('Unauthorized', 401);
            }
        }

        // Ejecutar el controlador correspondiente
        require_once __DIR__ . "/controllers/{$route['controller']}.php";
        $response = call_user_func($route['handler'], $uri);
        echo json_encode($response);
    } else {
        throw new Exception('Route not found', 404);
    }
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
}