<?php
require_once __DIR__ . '/middleware/auth.php';
require_once __DIR__ . '/controllers/cursos.php';
require_once __DIR__ . '/controllers/users.php';
require_once __DIR__ . '/utils/jwt.php';

function route($method, $uri) {
    // Debug
    error_log("Method: $method, URI: " . print_r($uri, true));

    // Construir la ruta
    $route = "$method " . ($uri[0] ?? '');
    if (isset($uri[1])) {
        $route .= "/$uri[1]";
    }

    error_log("Route being matched: $route");

    switch ($route) {
        case 'GET cursos':
            verificarJWT('admin'); // Solo admin puede ver cursos
            return getCursos();
        case 'POST cursos':
            verificarJWT('admin'); // Solo admin puede agregar cursos
            return agregarCurso();
        case 'POST users':
            return registrarUsuario();
        case 'GET users':
            verificarJWT('admin'); // Solo admin puede ver usuarios
            return getUsers();
        case 'POST users/login':
            return loginUsuario();
        case 'PUT users':
            verificarJWT();
            return actualizarUsuario();
        case 'DELETE users':
            verificarJWT();
            return borrarUsuario();
        default:
            throw new Exception('Ruta no encontrada', 404);
    }

}