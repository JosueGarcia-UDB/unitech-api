<?php
function readJsonFile($filename) {
    $path = __DIR__ . '/../data/' . $filename;
    if (!file_exists($path)) {
        throw new Exception("File not found: $filename at path: $path", 404);
    }
    $content = file_get_contents($path);
    return json_decode($content, true);
}

function writeJsonFile($filename, $data) {
    $path = __DIR__ . '/../data/' . $filename;
    if (!file_exists($path)) {
        throw new Exception("El archivo no existe: $filename", 404);
    }
    if (!is_writable($path)) {
        throw new Exception("El archivo no tiene permisos de escritura: $filename", 500);
    }
    $json = json_encode($data, JSON_PRETTY_PRINT);
    if (file_put_contents($path, $json) === false) {
        throw new Exception("Failed to write to file: $filename", 500);
    }
    return true;
}