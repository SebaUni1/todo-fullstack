<?php
include('db.php');

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">

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

        .progress-section {
            margin-bottom: 25px;
        }

        .progress-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .progress-bar {
            background: #e5e7eb;
            height: 12px;
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-fill {
            background: #28a745;
            height: 100%;
            width: 0%;
            transition: width 0.3s;
        }

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

        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .add-button {
            background: #28a745;
            color: white;
        }

        .bulk-section {
            background: #f3f6fb;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }

        .bulk-section h3 {
            color: #2c3e50;
            margin-top: 0;
        }

        .bulk-section textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 7px;
            font-size: 14px;
            resize: vertical;
            font-family: Arial, sans-serif;
        }

        .bulk-button {
            background: #2563eb;
            color: white;
            width: 100%;
        }

        .message {
            padding: 10px;
            border-radius: 7px;
            margin-bottom: 12px;
            display: none;
        }

        .message.success {
            display: block;
            background: #e2f0d9;
            color: #385723;
        }

        .message.error {
            display: block;
            background: #fce8e6;
            color: #c53929;
        }

        ul {
            padding: 0;
            list-style: none;
        }

        li {
            background: white;
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            border: 1px solid #ddd;
            border-right: 4px solid #28a745;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
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
            margin-left: 5px;
        }

        .actions {
            display: flex;
            gap: 7px;
            flex-shrink: 0;
        }

        .btn {
            padding: 7px 10px;
            border-radius: 5px;
            font-size: 12px;
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

        .empty {
            text-align: center;
            color: #999;
            padding: 25px;
            display: block;
        }

        .loading {
            text-align: center;
            color: #666;
            padding: 20px;
        }

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
            <h3 id="total-count">0</h3>
            <p>Total Tasks</p>
        </div>

        <div class="stat-box">
            <h3 id="completed-count">0</h3>
            <p>Completed</p>
        </div>

        <div class="stat-box">
            <h3 id="remaining-count">0</h3>
            <p>Remaining</p>
        </div>

    </div>

    <!-- Progress Bar -->
    <div class="progress-section">

        <div class="progress-info">
            <span>Daily Progress</span>
            <strong id="progress-number">0%</strong>
        </div>

        <div class="progress-bar">
            <div class="progress-fill" id="progress-fill"></div>
        </div>

    </div>

    <!-- Add Task -->
    <form class="task-form" id="single-task-form">

        <input
            type="text"
            id="single-task-input"
            placeholder="Enter a new task..."
            maxlength="200"
            required
        >

        <button type="submit" class="add-button" id="add-button">
            Add Task
        </button>

    </form>

    <!-- Bulk Add Tasks -->
    <div class="bulk-section">

        <h3>Add Multiple Tasks</h3>

        <div id="bulk-message" class="message"></div>

        <form id="bulk-form">

            <textarea
                id="bulk-tasks"
                rows="5"
                placeholder="Enter one task per line..."
            ></textarea>

            <p>
                Tasks to add:
                <strong id="task-count">0</strong>
            </p>

            <button
                type="submit"
                id="bulk-button"
                class="bulk-button"
            >
                Add All
            </button>

        </form>

    </div>

    <!-- Display Tasks -->
    <div id="loading" class="loading">
        Loading tasks...
    </div>

    <div id="error-message" class="message"></div>

    <ul id="task-list"></ul>

</div>

<!-- API Functions -->
<script src="api.js"></script>

<script>

// Get elements
const taskForm = document.getElementById('single-task-form');
const taskInput = document.getElementById('single-task-input');
const taskList = document.getElementById('task-list');

const totalCount = document.getElementById('total-count');
const completedCount = document.getElementById('completed-count');
const remainingCount = document.getElementById('remaining-count');

const progressNumber = document.getElementById('progress-number');
const progressFill = document.getElementById('progress-fill');

const loading = document.getElementById('loading');
const errorMessage = document.getElementById('error-message');

// Store tasks
let tasks = [];

// Show error message
function showError(message) {
    errorMessage.textContent = message;
    errorMessage.className = 'message error';
}

// Update statistics
function updateStatistics() {

    const total = tasks.length;

    const completed = tasks.filter(function(task) {
        return Number(task.is_completed) === 1;
    }).length;

    const remaining = total - completed;

    const progress = total > 0
        ? Math.round((completed / total) * 100)
        : 0;

    totalCount.textContent = total;
    completedCount.textContent = completed;
    remainingCount.textContent = remaining;

    progressNumber.textContent = progress + '%';
    progressFill.style.width = progress + '%';
}

// Display tasks
function renderTasks() {

    taskList.innerHTML = '';

    updateStatistics();

    if (tasks.length === 0) {
        const emptyItem = document.createElement('li');
        emptyItem.className = 'empty';
        emptyItem.textContent = 'No tasks available. Start by adding your first task!';
        taskList.appendChild(emptyItem);
        return;
    }

    tasks.forEach(function(task, index) {

        const isCompleted = Number(task.is_completed) === 1;

        const li = document.createElement('li');

        const taskText = document.createElement('div');
        taskText.className = 'task-text';

        const title = document.createElement('span');
        title.textContent = (index + 1) + '. ' + task.title;

        if (isCompleted) {
            title.className = 'completed-task';
        }

        const status = document.createElement('span');
        status.className = 'status-badge';
        status.textContent = isCompleted ? 'Completed' : 'Pending';

        taskText.appendChild(title);
        taskText.appendChild(status);

        const actions = document.createElement('div');
        actions.className = 'actions';

        // Edit button
        const editButton = document.createElement('button');
        editButton.className = 'btn edit-btn';
        editButton.textContent = 'Edit';

        editButton.addEventListener('click', async function() {

            const newTitle = prompt('Enter the new task title:', task.title);

            if (newTitle === null) {
                return;
            }

            if (newTitle.trim() === '') {
                alert('Task title is required');
                return;
            }

            try {
                await updateTask(task.id, {
                    title: newTitle.trim()
                });

                await loadTasks();

            } catch (error) {
                showError(error.message);
            }
        });

        // Complete or Undo button
        const completeButton = document.createElement('button');
        completeButton.className = 'btn complete-btn';
        completeButton.textContent = isCompleted ? 'Undo' : 'Complete';

        completeButton.addEventListener('click', async function() {

            try {
                await updateTask(task.id, {
                    is_completed: isCompleted ? 0 : 1
                });

                await loadTasks();

            } catch (error) {
                showError(error.message);
            }
        });

        // Delete button
        const deleteButton = document.createElement('button');
        deleteButton.className = 'btn delete-btn';
        deleteButton.textContent = 'Delete';

        deleteButton.addEventListener('click', async function() {

            const confirmed = confirm('Are you sure you want to delete this task?');

            if (!confirmed) {
                return;
            }

            try {
                await deleteTask(task.id);
                await loadTasks();

            } catch (error) {
                showError(error.message);
            }
        });

        actions.appendChild(editButton);
        actions.appendChild(completeButton);
        actions.appendChild(deleteButton);

        li.appendChild(taskText);
        li.appendChild(actions);

        taskList.appendChild(li);
    });
}

// Load tasks from API
async function loadTasks() {

    loading.style.display = 'block';
    errorMessage.className = 'message';

    try {

        tasks = await getTasks();

        renderTasks();

    } catch (error) {

        showError(error.message);

    } finally {

        loading.style.display = 'none';
    }
}

// Add one task
taskForm.addEventListener('submit', async function(event) {

    event.preventDefault();

    const title = taskInput.value.trim();

    if (title === '') {
        alert('Please enter a task.');
        return;
    }

    const button = document.getElementById('add-button');

    button.disabled = true;
    button.textContent = 'Adding...';

    try {

        await createTask(title);

        taskInput.value = '';

        await loadTasks();

    } catch (error) {

        showError(error.message);

    } finally {

        button.disabled = false;
        button.textContent = 'Add Task';
    }
});

// Bulk Add Tasks
const bulkInput = document.getElementById('bulk-tasks');
const taskCount = document.getElementById('task-count');
const bulkForm = document.getElementById('bulk-form');
const bulkButton = document.getElementById('bulk-button');
const bulkMessage = document.getElementById('bulk-message');

// Count tasks
function updateTaskCount() {

    const titles = bulkInput.value
        .split('\n')
        .map(function(title) {
            return title.trim();
        })
        .filter(function(title) {
            return title !== '';
        });

    taskCount.textContent = titles.length;
}

// Update count while typing
bulkInput.addEventListener('input', updateTaskCount);

// Add multiple tasks
bulkForm.addEventListener('submit', async function(event) {

    event.preventDefault();

    const titles = bulkInput.value
        .split('\n')
        .map(function(title) {
            return title.trim();
        })
        .filter(function(title) {
            return title !== '';
        });

    if (titles.length === 0) {
        bulkMessage.textContent = 'Please enter at least one task.';
        bulkMessage.className = 'message error';
        return;
    }

    if (titles.length > 50) {
        bulkMessage.textContent = 'You can add a maximum of 50 tasks.';
        bulkMessage.className = 'message error';
        return;
    }

    bulkButton.disabled = true;
    bulkButton.textContent = 'Adding...';

    try {

        await bulkCreateTasks(titles);

        bulkMessage.textContent = 'Tasks added successfully!';
        bulkMessage.className = 'message success';

        bulkInput.value = '';
        updateTaskCount();

        await loadTasks();

    } catch (error) {

        bulkMessage.textContent = error.message;
        bulkMessage.className = 'message error';

    } finally {

        bulkButton.disabled = false;
        bulkButton.textContent = 'Add All';
    }
});

// Load tasks when page opens
loadTasks();

</script>

</body>
</html>