<?php
// api/controllers/cursos.php
function getCursos() {
    try {
        $cursos = readJsonFile('../data/cursos.json');
        return [
            'status' => 'success',
            'data' => $cursos
        ];
    } catch (Exception $e) {
        throw new Exception('Error al obtener los cursos', 500);
    }
}

function inscribirseEnCurso() {
    try {
        if ($_SERVER['CONTENT_TYPE'] !== 'application/json') {
            throw new Exception('Content-Type debe ser application/json', 400);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['cursoId'])) {
            throw new Exception('cursoId es requerido', 400);
        }

        $token = getBearerToken();
        $decoded = decodeJWT($token);
        $userId = $decoded->sub;

        // Leer datos actuales
        $usuarios = readJsonFile('usuarios.json');
        $cursos = readJsonFile('cursos.json');

        // Verificar que el curso existe
        $cursoExists = false;
        foreach ($cursos as $curso) {
            if ($curso['id'] === $data['cursoId']) {
                $cursoExists = true;
                break;
            }
        }
        if (!$cursoExists) {
            throw new Exception('Curso no encontrado', 404);
        }

        // Actualizar inscripciones del usuario
        foreach ($usuarios as &$usuario) {
            if ($usuario['id'] === $userId) {
                if (!isset($usuario['cursos_inscritos'])) {
                    $usuario['cursos_inscritos'] = [];
                }
                if (!in_array($data['cursoId'], $usuario['cursos_inscritos'])) {
                    $usuario['cursos_inscritos'][] = $data['cursoId'];
                }
                break;
            }
        }

        writeJsonFile('usuarios.json', $usuarios);

        return [
            'status' => 'success',
            'message' => 'Inscripción exitosa'
        ];
    } catch (Exception $e) {
        throw new Exception($e->getMessage(), $e->getCode());
    }
}

function marcarCursoCompletado($uri) {
    try {
        $cursoId = $uri[2] ?? null;
        if (!$cursoId) {
            throw new Exception('ID del curso es requerido', 400);
        }

        $token = getBearerToken();
        $decoded = decodeJWT($token);
        $userId = $decoded->sub;

        // Leer datos actuales
        $usuarios = readJsonFile('usuarios.json');
        $cursos = readJsonFile('cursos.json');

        // Verificar que el usuario está inscrito en el curso
        $usuarioEncontrado = false;
        foreach ($usuarios as &$usuario) {
            if ($usuario['id'] === $userId) {
                $usuarioEncontrado = true;
                if (!isset($usuario['cursos_completados'])) {
                    $usuario['cursos_completados'] = [];
                }
                if (!in_array($cursoId, $usuario['cursos_completados'])) {
                    $usuario['cursos_completados'][] = $cursoId;
                }
                break;
            }
        }

        if (!$usuarioEncontrado) {
            throw new Exception('Usuario no encontrado', 404);
        }

        writeJsonFile('usuarios.json', $usuarios);

        return [
            'status' => 'success',
            'message' => 'Curso marcado como completado'
        ];
    } catch (Exception $e) {
        throw new Exception($e->getMessage(), $e->getCode());
    }
}

function desinscribirseDeCurso($uri) {
    try {
        $cursoId = $uri[2] ?? null;
        if (!$cursoId) {
            throw new Exception('ID del curso es requerido', 400);
        }

        $token = getBearerToken();
        $decoded = decodeJWT($token);
        $userId = $decoded->sub;

        // Leer datos actuales
        $usuarios = readJsonFile('usuarios.json');

        // Remover el curso de la lista de inscritos
        foreach ($usuarios as &$usuario) {
            if ($usuario['id'] === $userId) {
                if (isset($usuario['cursos_inscritos'])) {
                    $usuario['cursos_inscritos'] = array_values(
                        array_filter($usuario['cursos_inscritos'],
                            fn($id) => $id !== $cursoId)
                    );
                }
                break;
            }
        }

        writeJsonFile('usuarios.json', $usuarios);

        return [
            'status' => 'success',
            'message' => 'Desinscripción exitosa'
        ];
    } catch (Exception $e) {
        throw new Exception($e->getMessage(), $e->getCode());
    }
}