<?php

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../auth.php';

// Check user authentication
$user = getAuthenticatedUser();
$userId = (int) $user->user_id;

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

// Read JSON data
$input = json_decode(file_get_contents('php://input'), true);

// Check titles array
if (!is_array($input) || !isset($input['titles']) || !is_array($input['titles'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Titles must be an array'
    ]);
    exit;
}

// Remove spaces and empty titles
$titles = [];

foreach ($input['titles'] as $title) {
    if (is_string($title) && trim($title) !== '') {
        $title = trim($title);

        $length = function_exists('mb_strlen')
            ? mb_strlen($title, 'UTF-8')
            : strlen($title);

        if ($length > 200) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Task title is too long'
            ]);
            exit;
        }

        $titles[] = $title;
    }
}

// Check number of titles
if (count($titles) === 0 || count($titles) > 50) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'You must send between 1 and 50 tasks'
    ]);
    exit;
}

// Add all tasks using one transaction
try {
    $db->beginTransaction();

    $stmt = $db->prepare(
        "INSERT INTO tasks (user_id, title, is_completed)
         VALUES (:user_id, :title, 0)"
    );

    $createdTasks = [];

    foreach ($titles as $title) {
        $stmt->execute([
            'user_id' => $userId,
            'title' => $title
        ]);

        $id = $db->lastInsertId();

        $createdTasks[] = [
            'id' => (int) $id,
            'user_id' => $userId,
            'title' => $title,
            'is_completed' => 0
        ];
    }

    $db->commit();

    http_response_code(201);

    echo json_encode([
        'success' => true,
        'data' => $createdTasks
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {

    if ($db->inTransaction()) {
        $db->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to add tasks'
    ]);
}

?>