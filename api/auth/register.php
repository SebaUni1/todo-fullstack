<?php

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if ($name === '' || $email === '' || $password === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'All fields are required'
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid email address'
    ]);
    exit;
}

if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Password must be at least 8 characters'
    ]);
    exit;
}

$stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
$stmt->execute([':email' => $email]);

if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode([
        'success' => false,
        'message' => 'Email already exists'
    ]);
    exit;
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Every new account is a normal user
$role = 'user';

$stmt = $db->prepare("
    INSERT INTO users (name, email, password, role)
    VALUES (:name, :email, :password, :role)
");

$stmt->execute([
    ':name' => $name,
    ':email' => $email,
    ':password' => $hashedPassword,
    ':role' => $role
]);

$userId = $db->lastInsertId();

$user = [
    'id' => $userId,
    'email' => $email,
    'role' => $role
];

$token = generateToken($user);

http_response_code(201);

echo json_encode([
    'success' => true,
    'message' => 'Registration successful',
    'token' => $token,
    'user' => [
        'id' => $userId,
        'name' => $name,
        'email' => $email,
        'role' => $role
    ]
]);

?>