<?php
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../db.php';

// Read task ID from URL
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

// Read JSON data
function getInput() {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!is_array($input) || json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid JSON data'
        ]);
        exit;
    }

    return $input;
}

// GET: Get all tasks or one task
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if ($id !== null) {
        $stmt = $db->prepare("SELECT * FROM tasks WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$task) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Task not found'
            ]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'data' => $task
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $stmt = $db->query("SELECT * FROM tasks ORDER BY id DESC");
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $tasks
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// POST: Add a new task
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $input = getInput();
    $title = trim($input['title'] ?? '');

    if ($title === '') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Task title is required'
        ]);
        exit;
    }

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

    try {
        $stmt = $db->prepare(
            "INSERT INTO tasks (title, is_completed)
             VALUES (:title, 0)"
        );

        $stmt->execute(['title' => $title]);

        $newId = $db->lastInsertId();

        $stmt = $db->prepare("SELECT * FROM tasks WHERE id = :id");
        $stmt->execute(['id' => $newId]);

        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        http_response_code(201);

        echo json_encode([
            'success' => true,
            'data' => $task
        ], JSON_UNESCAPED_UNICODE);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to add task'
        ]);
    }

    exit;
}

// PUT: Edit task title or completion status
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {

    if ($id === null || $id <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Valid task ID is required'
        ]);
        exit;
    }

    $input = getInput();

    $stmt = $db->prepare("SELECT * FROM tasks WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$task) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Task not found'
        ]);
        exit;
    }

    $title = $task['title'];
    $isCompleted = (int) $task['is_completed'];

    if (array_key_exists('title', $input)) {
        $title = trim($input['title']);

        if ($title === '') {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Task title is required'
            ]);
            exit;
        }

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
    }

    if (array_key_exists('is_completed', $input)) {
        $value = $input['is_completed'];

        if (!in_array($value, [0, 1, true, false, '0', '1'], true)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid completion status'
            ]);
            exit;
        }

        $isCompleted = (int) $value;
    }

    try {
        $stmt = $db->prepare(
            "UPDATE tasks
             SET title = :title,
                 is_completed = :is_completed,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id"
        );

        $stmt->execute([
            'title' => $title,
            'is_completed' => $isCompleted,
            'id' => $id
        ]);

        $stmt = $db->prepare("SELECT * FROM tasks WHERE id = :id");
        $stmt->execute(['id' => $id]);

        $updatedTask = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => $updatedTask
        ], JSON_UNESCAPED_UNICODE);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update task'
        ]);
    }

    exit;
}

// DELETE: Delete a task
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {

    if ($id === null || $id <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Valid task ID is required'
        ]);
        exit;
    }

    $stmt = $db->prepare("DELETE FROM tasks WHERE id = :id");
    $stmt->execute(['id' => $id]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Task not found'
        ]);
        exit;
    }

    http_response_code(204);
    exit;
}

// Other methods are not allowed
http_response_code(405);

echo json_encode([
    'success' => false,
    'message' => 'Method not allowed'
]);
?>