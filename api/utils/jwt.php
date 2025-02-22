<?php
function generateJWT($userId, $secret = 'your-secret-key', $expiresIn = 3600) {
    $header = base64_encode(json_encode([
        'typ' => 'JWT',
        'alg' => 'HS256'
    ]));

    $payload = base64_encode(json_encode([
        'sub' => $userId,
        'iat' => time(),
        'exp' => time() + $expiresIn
    ]));

    $signature = base64_encode(hash_hmac('sha256', "$header.$payload", $secret, true));

    return "$header.$payload.$signature";
}

function decodeJWT($token, $secret = 'your-secret-key') {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        throw new Exception('Invalid token format');
    }

    $payload = json_decode(base64_decode($parts[1]));
    if (!$payload) {
        throw new Exception('Invalid payload');
    }

    if (time() >= $payload->exp) {
        throw new Exception('Token expired');
    }

    $signature = base64_encode(hash_hmac('sha256', "{$parts[0]}.{$parts[1]}", $secret, true));
    if ($signature !== $parts[2]) {
        throw new Exception('Invalid signature');
    }

    return $payload;
}