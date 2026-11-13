<?php

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../auth.php';

$user = getAuthenticatedUser();
$userId = (int) $user->id;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON data'
    ]);
    exit;
}

$action = $data['action'] ?? '';
$ids = $data['ids'] ?? [];

$allowedActions = ['delete', 'complete', 'incomplete'];

if (!in_array($action, $allowedActions, true)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action'
    ]);
    exit;
}

if (!is_array($ids) || count($ids) === 0 || count($ids) > 50) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Select between 1 and 50 tasks'
    ]);
    exit;
}

$taskIds = [];

foreach ($ids as $id) {
    $validId = filter_var($id, FILTER_VALIDATE_INT);

    if ($validId === false || $validId <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid task ID'
        ]);
        exit;
    }

    $taskIds[] = $validId;
}

$taskIds = array_values(array_unique($taskIds));
$placeholders = implode(',', array_fill(0, count($taskIds), '?'));

try {
    if ($action === 'delete') {
        $stmt = $db->prepare(
            "DELETE FROM tasks
             WHERE id IN ($placeholders)
             AND user_id = ?"
        );

        $stmt->execute([...$taskIds, $userId]);

    } else {
        $status = ($action === 'complete') ? 1 : 0;

        $stmt = $db->prepare(
            "UPDATE tasks
             SET is_completed = ?
             WHERE id IN ($placeholders)
             AND user_id = ?"
        );

        $stmt->execute([$status, ...$taskIds, $userId]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Bulk action completed successfully',
        'affected_rows' => $stmt->rowCount()
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database operation failed'
    ]);
}
?>