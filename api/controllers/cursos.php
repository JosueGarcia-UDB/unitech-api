<?php
// controllers/cursos.php

require_once __DIR__ . '/../utils/json_handler.php';

function getCursos() {
    try {
        $cursos = readJSONFile("cursos.json");
        return [
            'status' => 'success',
            'message' => 'Cursos obtenidos exitosamente',
            'data' => $cursos
        ];
    } catch (Exception $e) {
        throw new Exception('Error al obtener los cursos: ' . $e->getMessage(), 500);
    }
}

function agregarCurso() {
    try {
        // Verificar que se recibieron datos
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            throw new Exception('No se recibieron datos del curso', 400);
        }

        // Validar campos requeridos
        $camposRequeridos = ['nombre', 'descripcion', 'instructor', 'duracion'];
        foreach ($camposRequeridos as $campo) {
            if (!isset($data[$campo]) || empty($data[$campo])) {
                throw new Exception("El campo '$campo' es requerido", 400);
            }
        }

        // Leer cursos existentes
        $cursos = readJSONFile('cursos.json');

        // Generar ID único para el nuevo curso
        $nuevoId = count($cursos) + 1;

        // Crear nuevo curso
        $nuevoCurso = [
            'id' => $nuevoId,
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'],
            'instructor' => $data['instructor'],
            'duracion' => $data['duracion'],
            'fechaCreacion' => date('Y-m-d H:i:s')
        ];

        // Agregar el nuevo curso al array
        $cursos[] = $nuevoCurso;

        // Guardar en el archivo
        writeJSONFile('cursos.json', $cursos);

        return [
            'status' => 'success',
            'message' => 'Curso agregado exitosamente',
            'data' => $nuevoCurso
        ];
    } catch (Exception $e) {
        throw new Exception('Error al agregar el curso: ' . $e->getMessage(), $e->getCode() ?: 500);
    }
}