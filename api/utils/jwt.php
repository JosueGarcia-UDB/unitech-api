<?php
// utils/jwt.php

function base64UrlEncode($data) {
    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
}

function base64UrlDecode($data) {
    $padding = strlen($data) % 4;
    if ($padding) {
        $data .= str_repeat('=', 4 - $padding);
    }
    return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
}

function generateJWT($payload, $secret = 's4lv4me n3gr31r4', $expiresIn = 3600) {
    $payload['iat'] = time();
    $payload['exp'] = time() + $expiresIn;

    $header = base64UrlEncode(json_encode([
        'typ' => 'JWT',
        'alg' => 'HS256'
    ]));

    $payloadEncoded = base64UrlEncode(json_encode($payload));

    $signature = hash_hmac('sha256', "$header.$payloadEncoded", $secret, true);
    $signatureEncoded = base64UrlEncode($signature);

    return "$header.$payloadEncoded.$signatureEncoded";
}

function decodeJWT($token, $secret = 's4lv4me n3gr31r4') {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        throw new Exception('Invalid token format', 401);
    }

    list($headerB64, $payloadB64, $signatureB64) = $parts;

    // Decodificar el payload usando base64UrlDecode
    $payload = json_decode(base64UrlDecode($payloadB64), true);
    if (!$payload) {
        throw new Exception('Invalid payload', 401);
    }

    if (time() >= $payload['exp']) {
        throw new Exception('Token expired', 401);
    }

    // Verificar la firma
    $signatureCheck = hash_hmac('sha256', "$headerB64.$payloadB64", $secret, true);
    $signatureCheckB64 = base64UrlEncode($signatureCheck);

    if (!hash_equals($signatureCheckB64, $signatureB64)) {
        throw new Exception('Invalid signature', 401);
    }

    return $payload;
}