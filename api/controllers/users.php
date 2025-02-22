<?php
// controllers/usuarios.php

require_once __DIR__ . '/../utils/json_handler.php';

function getUsers() {
    try {
        $users = readJSONFile("users.json");
        return [
            'status' => 'success',
            'message' => 'Usuarios obtenidos exitosamente',
            'data' => $users
        ];
    } catch (Exception $e) {
        throw new Exception('Error al obtener los usuarios: ' . $e->getMessage(), 500);
    }
}

function registrarUsuario() {
    try {
        // Obtener datos del request
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            throw new Exception('No se recibieron datos del usuario', 400);
        }

        // Validar campos requeridos
        $camposRequeridos = ['nombre', 'email', 'password'];
        foreach ($camposRequeridos as $campo) {
            if (!isset($data[$campo]) || empty($data[$campo])) {
                throw new Exception("El campo '$campo' es requerido", 400);
            }
        }

        // Validar formato de email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Formato de email inválido', 400);
        }

        // Leer usuarios existentes
        $usuarios = readJSONFile('../data/users.json');

        // Verificar si el email ya existe
        foreach ($usuarios as $usuario) {
            if ($usuario['email'] === $data['email']) {
                throw new Exception('El email ya está registrado', 400);
            }
        }

        // Hashear password
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        // Crear nuevo usuario
        $nuevoUsuario = [
            'id' => count($usuarios) + 1,
            'nombre' => $data['nombre'],
            'email' => $data['email'],
            'password' => $hashedPassword,
            'fechaRegistro' => date('Y-m-d H:i:s')
        ];

        // Agregar nuevo usuario
        $usuarios[] = $nuevoUsuario;
        writeJSONFile('../data/users.json', $usuarios);

        // Remover password del response
        unset($nuevoUsuario['password']);

        return [
            'status' => 'success',
            'message' => 'Usuario registrado exitosamente',
            'data' => $nuevoUsuario
        ];
    } catch (Exception $e) {
        throw new Exception('Error al registrar usuario: ' . $e->getMessage(), $e->getCode() ?: 500);
    }
}

function loginUsuario() {
    try {
        // Obtener datos del request
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['email']) || !isset($data['password'])) {
            throw new Exception('Email y password son requeridos', 400);
        }

        // Leer usuarios
        $usuarios = readJSONFile('../data/users.json');

        // Buscar usuario por email
        $usuario = null;
        foreach ($usuarios as $u) {
            if ($u['email'] === $data['email']) {
                $usuario = $u;
                break;
            }
        }

        if (!$usuario || !password_verify($data['password'], $usuario['password'])) {
            throw new Exception('Credenciales inválidas', 401);
        }

        // Generar JWT
        $token = generateJWT($usuario['id']);

        // Remover password del response
        unset($usuario['password']);

        return [
            'status' => 'success',
            'message' => 'Login exitoso',
            'data' => [
                'usuario' => $usuario,
                'token' => $token
            ]
        ];
    } catch (Exception $e) {
        throw new Exception('Error en login: ' . $e->getMessage(), $e->getCode() ?: 500);
    }
}

function actualizarUsuario() {
    try {
        // Obtener datos del request
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            throw new Exception('No se recibieron datos para actualizar', 400);
        }

        // Obtener ID del usuario del token
        $token = getAuthToken();
        $payload = decodeJWT($token);
        $userId = $payload['sub'];

        // Leer usuarios
        $usuarios = readJSONFile('../data/users.json');

        // Encontrar y actualizar usuario
        $usuarioActualizado = false;
        foreach ($usuarios as &$usuario) {
            if ($usuario['id'] === $userId) {
                // Actualizar campos permitidos
                if (isset($data['nombre'])) $usuario['nombre'] = $data['nombre'];
                if (isset($data['email'])) {
                    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                        throw new Exception('Formato de email inválido', 400);
                    }
                    $usuario['email'] = $data['email'];
                }
                if (isset($data['password'])) {
                    $usuario['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
                }
                $usuarioActualizado = true;
                break;
            }
        }

        if (!$usuarioActualizado) {
            throw new Exception('Usuario no encontrado', 404);
        }

        // Guardar cambios
        writeJSONFile('../data/users.json', $usuarios);

        return [
            'status' => 'success',
            'message' => 'Usuario actualizado exitosamente'
        ];
    } catch (Exception $e) {
        throw new Exception('Error al actualizar usuario: ' . $e->getMessage(), $e->getCode() ?: 500);
    }
}

function borrarUsuario() {
    try {
        // Obtener ID del usuario del token
        $token = getAuthToken();
        $payload = decodeJWT($token);
        $userId = $payload['sub'];

        // Leer usuarios
        $usuarios = readJSONFile('../data/users.json');

        // Encontrar y eliminar usuario
        $usuarioEliminado = false;
        foreach ($usuarios as $key => $usuario) {
            if ($usuario['id'] === $userId) {
                unset($usuarios[$key]);
                $usuarioEliminado = true;
                break;
            }
        }

        if (!$usuarioEliminado) {
            throw new Exception('Usuario no encontrado', 404);
        }

        // Reindexar array y guardar cambios
        $usuarios = array_values($usuarios);
        writeJSONFile('../data/users.json', $usuarios);

        return [
            'status' => 'success',
            'message' => 'Usuario eliminado exitosamente'
        ];
    } catch (Exception $e) {
        throw new Exception('Error al eliminar usuario: ' . $e->getMessage(), $e->getCode() ?: 500);
    }
}

// Función auxiliar para obtener el token de autorización
function getAuthToken() {
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        throw new Exception('Token no proporcionado', 401);
    }

    $auth = $headers['Authorization'];
    if (strpos($auth, 'Bearer ') !== 0) {
        throw new Exception('Formato de token inválido', 401);
    }

    return substr($auth, 7);
}