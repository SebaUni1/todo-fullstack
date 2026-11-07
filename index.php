<?php
include('db.php');

// حماية النصوص من الثغرات
function e($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// Add Task
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_task'])) {
    $task = trim($_POST['task']);
    
    if ($task != '') {
        $stmt = $db->prepare(
            "INSERT INTO tasks (title, is_completed) VALUES (:title, 0)"
        );
        $stmt->execute(['title' => $task]);
    }

    header('Location: index.php');
    exit;
}

// Edit Task
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_task'])) {
    $id = (int) $_POST['id'];
    $task = trim($_POST['task']);

    if ($id > 0 && $task != '') {
        $stmt = $db->prepare(
            "UPDATE tasks SET title = :title WHERE id = :id"
        );
        $stmt->execute([
            'title' => $task,
            'id' => $id
        ]);
    }

    header('Location: index.php');
    exit;
}

// Delete Task
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_task'])) {
    $id = (int) $_POST['delete_task'];

    $stmt = $db->prepare("DELETE FROM tasks WHERE id = :id");
    $stmt->execute(['id' => $id]);

    header('Location: index.php');
    exit;
}

// Complete or Undo Task (Toggle)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_task'])) {
    $id = (int) $_POST['toggle_task'];

    // تبديل قيمة is_completed بين 0 و 1
    $stmt = $db->prepare("
        UPDATE tasks
        SET is_completed = CASE
            WHEN is_completed = 1 THEN 0
            ELSE 1
        END
        WHERE id = :id
    ");

    $stmt->execute(['id' => $id]);

    header('Location: index.php');
    exit;
}

// Get Task for Editing
$editTask = null;
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];

    $stmt = $db->prepare("SELECT * FROM tasks WHERE id = :id");
    $stmt->execute(['id' => $id]);

    $editTask = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get All Tasks
$tasks = $db->query(
    "SELECT * FROM tasks ORDER BY id DESC"
)->fetchAll(PDO::FETCH_ASSOC);

// Calculate Statistics
$total = count($tasks);
$completed = 0;

foreach ($tasks as $row) {
    if (isset($row['is_completed']) && $row['is_completed'] == 1) {
        $completed++;
    }
}

$remaining = $total - $completed;
$progress = ($total > 0) ? round(($completed / $total) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My To-Do List</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f0f2f5;
            padding: 40px 15px;
            margin: 0;
        }

        .container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            max-width: 650px;
            margin: auto;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        h2 {
            text-align: center;
            color: #2c3e50;
        }

        /* Statistics */
        .stats {
            display: flex;
            gap: 12px;
            margin-bottom: 25px;
        }

        .stat-box {
            flex: 1;
            padding: 15px 8px;
            text-align: center;
            background: #f3f6fb;
            border-radius: 10px;
        }

        .stat-box h3 {
            margin: 0;
            color: #2563eb;
            font-size: 25px;
        }

        .stat-box p {
            color: #666;
            font-size: 13px;
        }

        /* Progress */
        .progress-section {
            margin-bottom: 25px;
        }

        .progress-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        /* شكل شريط التقدم */
        .progress-bar {
            background: #e5e7eb;
            height: 12px;
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-fill {
            background: #28a745;
            height: 100%;
            width: <?php echo $progress; ?>%;
        }

        /* Form */
        .task-form {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
        }

        input[type="text"] {
            flex: 1;
            min-width: 0;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 7px;
            font-size: 14px;
        }

        button {
            cursor: pointer;
            border: none;
            border-radius: 7px;
            font-weight: bold;
            padding: 10px 15px;
        }

        .add-button {
            background: #28a745;
            color: white;
        }

        .save-button {
            background: #2563eb;
            color: white;
        }

        /* Tasks */
        ul {
            padding: 0;
            list-style: none;
        }
li {
    background: #ffffff !important;
    margin-bottom: 15px !important;
    padding: 15px !important;
    border-radius: 8px !important;
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    border: 1px solid #ddd !important;
    border-right: 4px solid #28a745 !important;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05) !important;
}
        

        .task-text {
            color: #333;
            overflow-wrap: anywhere;
        }

        .completed-task {
            text-decoration: line-through;
            color: #999;
        }

        .status-badge {
            font-size: 12px;
            color: #666;
            background: #e9ecef;
            padding: 4px 7px;
            border-radius: 5px;
        }

        /* Buttons */
        .actions {
            display: flex;
            gap: 7px;
            flex-shrink: 0;
        }

        .actions form {
            margin: 0;
        }

        .btn {
            display: inline-block;
            padding: 7px 10px;
            border-radius: 5px;
            font-size: 12px;
            text-decoration: none;
            font-weight: bold;
            white-space: nowrap;
        }

        .complete-btn {
            background: #e2f0d9;
            color: #385723;
        }

        .delete-btn {
            background: #fce8e6;
            color: #c53929;
        }

        .edit-btn {
            background: #e0e7ff;
            color: #3730a3;
        }

        .cancel-btn {
            align-self: center;
            color: #666;
            text-decoration: none;
        }

        .empty {
            text-align: center;
            color: #999;
            padding: 25px;
        }

        /* Mobile */
        @media (max-width: 480px) {
            .container {
                padding: 20px 12px;
            }

            li {
                flex-wrap: wrap;
            }

            .actions {
                flex-wrap: wrap;
            }

            .task-form {
                flex-wrap: wrap;
            }

            .task-form input {
                flex-basis: 100%;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <h2>My To-Do List</h2>

    <!-- Statistics -->
    <div class="stats">
        <div class="stat-box">
            <h3><?php echo $total; ?></h3>
            <p>Total Tasks</p>
        </div>
        <div class="stat-box">
            <h3><?php echo $completed; ?></h3>
            <p>Completed</p>
        </div>
        <div class="stat-box">
            <h3><?php echo $remaining; ?></h3>
            <p>Remaining</p>
        </div>
    </div>

    <!-- Progress Bar -->
    <div class="progress-section">
        <div class="progress-info">
            <span>Daily Progress</span>
            <strong><?php echo $progress; ?>%</strong>
        </div>
        <div class="progress-bar">
            <div class="progress-fill"></div>
        </div>
    </div>

    <!-- Add or Edit Task -->
    <form method="POST" class="task-form">
        <input
            type="text"
            name="task"
            placeholder="أدخل مهمة جديدة..."
            value="<?php echo e($editTask['title'] ?? ''); ?>"
            maxlength="200"
            required
        >

        <?php if ($editTask): ?>
            <input type="hidden" name="id" value="<?php echo (int)$editTask['id']; ?>">
            <button type="submit" name="edit_task" class="save-button">حفظ التعديل</button>
            <a href="index.php" class="cancel-btn">إلغاء</a>
        <?php else: ?>
            <button type="submit" name="add_task" class="add-button">إضافة</button>
        <?php endif; ?>
    </form>

    <!-- Display Tasks -->
    <ul>
        <?php if ($total > 0): ?>
            <?php foreach ($tasks as $index => $row): ?>
                <?php
                $isCompleted = isset($row['is_completed']) && $row['is_completed'] == 1;
                ?>
                <li>
                    <div class="task-text">
                        <span class="<?php echo $isCompleted ? 'completed-task' : ''; ?>">
                            <?php echo ($index + 1) . '. ' . e($row['title']); ?>
                        </span>
                        <span class="status-badge">
                            <?php echo $isCompleted ? 'Completed' : 'Pending'; ?>
                        </span>
                    </div>

                    <div class="actions">
                        <a href="index.php?edit=<?php echo (int)$row['id']; ?>" class="btn edit-btn">Edit</a>

                        <form method="POST">
                            <button type="submit" name="toggle_task" value="<?php echo (int)$row['id']; ?>" class="btn complete-btn">
                                <?php echo $isCompleted ? 'Undo' : 'Complete'; ?>
                            </button>
                        </form>

                        <form method="POST" onsubmit="return confirm('هل أنت متأكد من حذف المهمة؟');">
                            <button type="submit" name="delete_task" value="<?php echo (int)$row['id']; ?>" class="btn delete-btn">
                                Delete
                            </button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <li class="empty">
                لا توجد مهام حالياً
                <br>
                ابدأ بإضافة أول مهمة!
            </li>
        <?php endif; ?>
    </ul>

</div>

</body>
</html>