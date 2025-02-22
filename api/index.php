<?php
header('Content-Type: application/json'); # Indica que la API responde con JSON
header('Access-Control-Allow-Origin: *'); #Permite que cualquier dominio acceda a la API
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE'); #Define qué métodos HTTP están permitidos
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Para debug, habilita la visualización de errores.
error_reporting(E_ALL);
ini_set('display_errors', 1);

//Estos archivos contienen funciones esenciales para el funcionamiento de la API
require_once __DIR__ . '/routes.php';
require_once __DIR__ . '/utils/json_handler.php';
require_once __DIR__ . '/middleware/auth.php';

// Obtener el método HTTP y la ruta solicitada
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Debug
error_log("Original URI: " . $uri);

// Remover la parte base de la URL
$baseUri = '/unitech-api/api/';
if (strpos($uri, $baseUri) === 0) {
    $uri = substr($uri, strlen($baseUri));
}

// Dividir la URI en segmentos
$uri = trim($uri, '/');
$uri = $uri ? explode('/', $uri) : [];

// Debug
error_log("Processed URI: " . print_r($uri, true));

// Manejar OPTIONS request para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Enrutamiento
try {
    $response = route($method, $uri);
    echo json_encode($response);
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
}