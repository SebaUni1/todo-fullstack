<?php

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../auth.php';

// Only admin can access this file
requireRole('admin');

// GET: View all tasks with their owners
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $stmt = $db->query("
        SELECT
            tasks.*,
            users.name AS owner_name,
            users.email AS owner_email
        FROM tasks
        LEFT JOIN users ON tasks.user_id = users.id
        ORDER BY tasks.id DESC
    ");

    echo json_encode([
        'success' => true,
        'tasks' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// DELETE: Delete any task
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {

    $data = json_decode(file_get_contents('php://input'), true);
    $taskId = (int) ($data['id'] ?? 0);

    if ($taskId <= 0) {
        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'Valid task ID is required'
        ]);

        exit;
    }

    $stmt = $db->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->execute([$taskId]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Task not found'
        ]);

        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Task deleted successfully'
    ]);

    exit;
}

// Other methods are not allowed
http_response_code(405);

echo json_encode([
    'success' => false,
    'message' => 'Method not allowed'
]);

?>