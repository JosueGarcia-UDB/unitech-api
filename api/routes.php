<?php
function matchRoute($method, $uri) {
    $routes = [
        // Rutas públicas
        ['GET', ['escuelas'], 'escuelas', 'getEscuelas', false],
        ['GET', ['escuelas', '{categoria}'], 'escuelas', 'getEscuelasByCategoria', false],
        ['GET', ['escuelas', '{categoria}', '{curso}'], 'escuelas', 'getCursoDetalle', false],
        ['GET', ['cursos'], 'cursos', 'getCursos', false],
        ['GET', ['blog'], 'blog', 'getBlogPosts', false],
        ['GET', ['blog', '{id}'], 'blog', 'getBlogPost', false],
        ['POST', ['usuarios', 'registro'], 'usuarios', 'registrarUsuario', false],
        ['POST', ['usuarios', 'login'], 'usuarios', 'loginUsuario', false],

        // Rutas protegidas (requieren JWT)
        ['POST', ['cursos', 'inscribirse'], 'cursos', 'inscribirseEnCurso', true],
        ['GET', ['usuarios', 'perfil'], 'usuarios', 'getPerfil', true],
        ['PUT', ['usuarios', 'actualizar'], 'usuarios', 'actualizarPerfil', true],
        ['PUT', ['usuarios', 'cambiar-password'], 'usuarios', 'cambiarPassword', true],
        ['GET', ['premium'], 'premium', 'getContenidoPremium', true],
        ['POST', ['blog', 'comentar'], 'blog', 'comentarBlog', true],
        ['POST', ['cursos', 'completar', '{id}'], 'cursos', 'marcarCursoCompletado', true],
        ['DELETE', ['usuarios', 'eliminar'], 'usuarios', 'eliminarCuenta', true],
        ['DELETE', ['cursos', 'desinscribirse', '{id}'], 'cursos', 'desinscribirseDeCurso', true],
    ];

    foreach ($routes as [$routeMethod, $routeUri, $controller, $handler, $requiresAuth]) {
        if ($method === $routeMethod && matchUriPattern($uri, $routeUri)) {
            return [
                'controller' => $controller,
                'handler' => $handler,
                'requiresAuth' => $requiresAuth
            ];
        }
    }
    return null;
}

function matchUriPattern($uri, $pattern)
{
    if (count($uri) !== count($pattern)) {
        return false;
    }

    foreach ($uri as $i => $segment) {
        if (preg_match('/^{.+}$/', $pattern[$i])) {
            continue;
        }
        if ($segment !== $pattern[$i]) {
            return false;
        }
    }
    return true;
}
