<?php

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../auth.php';

// Only admin can access this file
$admin = requireRole('admin');

// Get all users
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $stmt = $db->query("
        SELECT id, name, email, role, created_at
        FROM users
        ORDER BY id DESC
    ");

    echo json_encode([
        'success' => true,
        'users' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);

    exit;
}

// Delete a user
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {

    $data = json_decode(file_get_contents('php://input'), true);

    $userId = intval($data['id'] ?? 0);

    if ($userId <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid user ID'
        ]);
        exit;
    }

    if ($userId == $admin->id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'You cannot delete your own account'
        ]);
        exit;
    }

    $stmt = $db->prepare("DELETE FROM tasks WHERE user_id = ?");
    $stmt->execute([$userId]);

    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);

    echo json_encode([
        'success' => true,
        'message' => 'User and tasks deleted successfully'
    ]);

    exit;
}

http_response_code(405);

echo json_encode([
    'success' => false,
    'message' => 'Method not allowed'
]);

?>