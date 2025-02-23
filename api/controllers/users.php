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

        // Asignar rol (solo un admin puede crear otro admin)
        $rol = 'usuario'; // Por defecto
        if (isset($data['rol']) && $data['rol'] === 'admin') {
            // Verificar si el usuario autenticado es admin
            $token = getAuthToken();
            $payload = decodeJWT($token);
            if ($payload['rol'] !== 'admin') {
                throw new Exception('Solo un admin puede crear otro admin', 403);
            }
            $rol = 'admin';
        }

        // Hashear password
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        // Crear nuevo usuario
        $nuevoUsuario = [
            'id' => count($usuarios) + 1,
            'nombre' => $data['nombre'],
            'email' => $data['email'],
            'password' => $hashedPassword,
            'rol' => $rol, // Agregamos el rol
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
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['email']) || !isset($data['password'])) {
            throw new Exception('Email y password son requeridos', 400);
        }

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

        // Generar JWT incluyendo el rol
        $token = generateJWT([
            'sub' => $usuario['id'],
            'rol' => $usuario['rol']
        ]);

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
        $payload = verificarJWT(); // Validar token y obtener payload
        $userId = $payload['sub'];

        // Leer usuarios
        $usuarios = readJSONFile('../data/users.json');

        // Obtener los datos enviados por el usuario
        $inputData = json_decode(file_get_contents("php://input"), true);
        if (!$inputData) {
            throw new Exception('Datos inválidos', 400);
        }

        $usuarioActualizado = false;
        foreach ($usuarios as $key => $usuario) {
            if ($usuario['id'] === $userId) { // Solo puede actualizarse a sí mismo
                // Actualizar solo los campos permitidos (evitar cambios en ID)
                $usuarios[$key] = array_merge($usuario, array_intersect_key($inputData, $usuario));
                $usuarioActualizado = true;
                break;
            }
        }

        if (!$usuarioActualizado) {
            throw new Exception('Usuario no encontrado', 404);
        }

        // Guardar cambios en el archivo JSON
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
        // Obtener ID del usuario desde el token
        $payload = verificarJWT(); // Validar token y obtener payload
        $userId = $payload['sub'];

        // Leer usuarios
        $usuarios = readJSONFile('../data/users.json');

        // Verificar que el usuario existe
        $usuarioEliminado = false;
        foreach ($usuarios as $key => $usuario) {
            if ($usuario['id'] === $userId) { // Solo puede eliminarse a sí mismo
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