<?php

require_once __DIR__ . '/vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Read JWT secret from .env
$envFile = __DIR__ . '/.env';

if (file_exists($envFile)) {
    $envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($envLines as $line) {
        if (strpos(trim($line), 'JWT_SECRET=') === 0) {
            $secretKey = trim(substr(trim($line), 11));
            break;
        }
    }
}

if (empty($secretKey)) {
    die('JWT_SECRET is missing from .env');
}

// Generate JWT token
function generateToken($user) {
    global $secretKey;

    $issuedAt = time();
    $expire = $issuedAt + 3600;

    $payload = [
        'iss' => 'todo-list',
        'iat' => $issuedAt,
        'exp' => $expire,
        'id' => $user['id'],
        'user_id' => $user['id'],
        'email' => $user['email'],
        'role' => $user['role'] ?? 'user'
    ];

    return JWT::encode($payload, $secretKey, 'HS256');
}

// Get authenticated user
function getAuthenticatedUser() {
    global $secretKey;

    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $authHeader = '';

    foreach ($headers as $name => $value) {
        if (strtolower($name) === 'authorization') {
            $authHeader = $value;
            break;
        }
    }

    if (!preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
        http_response_code(401);

        echo json_encode([
            'success' => false,
            'message' => 'Token is required'
        ]);

        exit;
    }

    try {
        $token = $matches[1];

        return JWT::decode(
            $token,
            new Key($secretKey, 'HS256')
        );

    } catch (Exception $e) {
        http_response_code(401);

        echo json_encode([
            'success' => false,
            'message' => 'Invalid or expired token'
        ]);

        exit;
    }
}

// Check user role
function requireRole($role) {
    $user = getAuthenticatedUser();

    if ($user->role !== $role) {
        http_response_code(403);

        echo json_encode([
            'success' => false,
            'message' => 'Access denied'
        ]);

        exit;
    }

    return $user;
}

?>