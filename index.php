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
            margin: 0;
            padding: 40px 15px;
            background: #f3f6fb;
            color: #172033;
        }

        .container {
            width: 100%;
            max-width: 620px;
            margin: auto;
            padding: 28px;
            background: white;
            border-radius: 14px;
            box-shadow: 0 5px 22px rgba(0,0,0,0.07);
        }

        h2, h3 {
            text-align: center;
            color: #172033;
        }

        h2 {
            margin-top: 0;
        }

        input, textarea {
            width: 100%;
            padding: 11px;
            margin: 8px 0;
            border: 1px solid #dfe5ef;
            border-radius: 7px;
            font-size: 15px;
        }

        button {
            padding: 9px 14px;
            border: none;
            border-radius: 7px;
            background: #2563eb;
            color: white;
            cursor: pointer;
            font-size: 13px;
            white-space: nowrap;
        }

        button:hover {
            opacity: 0.88;
        }

        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .input-group {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 18px 0;
        }

        .input-group input {
            flex: 1;
            min-width: 0;
            margin: 0;
        }

        .input-group button {
            width: auto;
            padding: 9px 13px;
        }

        /* Add Multiple Tasks */
        .bulk-add {
            margin: 18px 0;
            padding: 16px;
            background: #f8fafc;
            border: 1px solid #e5eaf2;
            border-radius: 8px;
            text-align: left;
        }

        .bulk-add h3 {
            text-align: left;
            margin: 0 0 10px;
            font-size: 16px;
        }

        .bulk-add textarea {
            min-height: 85px;
            resize: vertical;
            display: block;
            margin: 0 0 10px;
        }

        .bulk-add button {
            display: block;
            margin: 0;
        }

        .auth-buttons {
            text-align: center;
            margin-top: 12px;
        }

        #tasksSection,
        #registerSection,
        #adminSection {
            display: none;
        }

        #welcomeMessage {
            text-align: left;
        }

        ul {
            list-style: none;
            padding: 0;
            margin-top: 18px;
        }

        li {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            padding: 13px;
            margin: 10px 0;
            background: #f8fafc;
            border: 1px solid #e5eaf2;
            border-radius: 8px;
        }

        li input[type="checkbox"] {
            width: auto;
            margin: 0;
        }

        .task-title {
            flex: 1;
            overflow-wrap: anywhere;
        }

        .task-status {
            font-size: 12px;
            color: #64748b;
        }

        .task-done .task-title {
            text-decoration: line-through;
            color: #94a3b8;
        }

        .task-status.completed {
            color: #16a34a;
        }

        .task-actions {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .edit-btn {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .complete-btn, .undo-btn {
            background: #dcfce7;
            color: #15803d;
        }

        .delete-btn {
            background: #fee2e2;
            color: #dc2626;
        }

        /* Hide Bulk Actions area */
        .bulk-controls {
            display: none;
        }

        .bulk-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .admin-box {
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #e5eaf2;
        }

        .admin-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            padding: 12px;
            margin: 8px 0;
            background: #f8fafc;
            border: 1px solid #e5eaf2;
            border-radius: 8px;
            overflow-wrap: anywhere;
        }

        @media (max-width: 600px) {
            body {
                padding: 15px 10px;
            }

            .container {
                padding: 18px;
            }

            .input-group {
                gap: 6px;
            }

            .input-group button {
                padding: 9px 11px;
            }

            li {
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <h2>My To-Do List</h2>

    <div id="loginSection">
        <h3>Login</h3>

        <input type="email" id="loginEmail" placeholder="Email">
        <input type="password" id="loginPassword" placeholder="Password">

        <div class="auth-buttons">
            <button onclick="login()">Login</button>
            <button onclick="showRegister()">Create Account</button>
        </div>
    </div>

    <div id="registerSection">
        <h3>Create Account</h3>

        <input type="text" id="registerName" placeholder="Name">
        <input type="email" id="registerEmail" placeholder="Email">
        <input type="password" id="registerPassword" placeholder="Password">

        <div class="auth-buttons">
            <button onclick="register()">Register</button>
            <button onclick="showLogin()">Back to Login</button>
        </div>
    </div>

    <div id="tasksSection">

        <h3 id="welcomeMessage"></h3>

        <button onclick="logout()">Logout</button>

        <div class="input-group">
            <input type="text" id="taskInput"
                   placeholder="Enter a new task...">

            <button onclick="addTask()">Add Task</button>
        </div>

        <div class="bulk-add">

            <h3>Add Multiple Tasks</h3>

            <textarea id="multipleTasksInput"
                placeholder="Write each task on a separate line..."></textarea>

            <button onclick="addMultipleTasks()">
                Add All Tasks
            </button>

        </div>

        <div class="bulk-controls">

            <p id="selectedCount">0 selected</p>

            <div class="bulk-buttons">

                <button class="bulk-action" disabled
                    onclick="performBulkAction('complete')">
                    Complete Selected
                </button>

                <button class="bulk-action" disabled
                    onclick="performBulkAction('incomplete')">
                    Mark Incomplete
                </button>

                <button class="bulk-action delete-btn" disabled
                    onclick="performBulkAction('delete')">
                    Delete Selected
                </button>

            </div>

        </div>

        <ul id="taskList"></ul>

    </div>

    <div id="adminSection" class="admin-box">

        <h2>Admin Dashboard</h2>

        <h3>All Tasks</h3>
        <div id="adminTasksList"></div>

        <h3>All Users</h3>
        <div id="adminUsersList"></div>

    </div>

</div>

<script src="api.js?v=8"></script>

</body>
</html>