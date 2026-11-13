const API_URL = 'api/tasks/index.php';
const BULK_URL = 'api/tasks/bulk-actions.php';
const BULK_ADD_URL = 'api/tasks/bulk.php';
const AUTH_URL = 'api/auth';
const ADMIN_URL = 'api/admin';

let selectedTasks = new Set();

// Read JSON response safely
async function readResponse(response) {
    const text = await response.text();

    if (!text) {
        return {};
    }

    try {
        return JSON.parse(text);
    } catch (error) {
        throw new Error('Server returned an invalid response');
    }
}

// Show login
function showLogin() {
    document.getElementById('loginSection').style.display = 'block';
    document.getElementById('registerSection').style.display = 'none';
    document.getElementById('tasksSection').style.display = 'none';
    document.getElementById('adminSection').style.display = 'none';
}

// Show register
function showRegister() {
    document.getElementById('loginSection').style.display = 'none';
    document.getElementById('registerSection').style.display = 'block';
    document.getElementById('tasksSection').style.display = 'none';
    document.getElementById('adminSection').style.display = 'none';
}

// Show tasks
function showTasks() {
    document.getElementById('loginSection').style.display = 'none';
    document.getElementById('registerSection').style.display = 'none';
    document.getElementById('tasksSection').style.display = 'block';

    const user = JSON.parse(localStorage.getItem('user'));

    if (!user) {
        logout();
        return;
    }

    document.getElementById('welcomeMessage').textContent =
        'Welcome, ' + user.name;

    if (user.role === 'admin') {
        document.getElementById('adminSection').style.display = 'block';
        loadAdminData();
    } else {
        document.getElementById('adminSection').style.display = 'none';
    }
}

// Register
async function register() {
    const name = document.getElementById('registerName').value.trim();
    const email = document.getElementById('registerEmail').value.trim();
    const password = document.getElementById('registerPassword').value;

    if (!name || !email || !password) {
        alert('Please fill in all fields');
        return;
    }

    try {
        const response = await fetch(AUTH_URL + '/register.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({name, email, password})
        });

        const result = await readResponse(response);

        if (!response.ok || !result.success) {
            alert(result.message || 'Registration failed');
            return;
        }

        localStorage.setItem('token', result.token);
        localStorage.setItem('user', JSON.stringify(result.user));

        alert('Registration successful!');
        showTasks();
        await loadTasks();

    } catch (error) {
        console.error(error);
        alert('Could not connect to the server: ' + error.message);
    }
}

// Login
async function login() {
    const email = document.getElementById('loginEmail').value.trim();
    const password = document.getElementById('loginPassword').value;

    if (!email || !password) {
        alert('Please enter email and password');
        return;
    }

    try {
        const response = await fetch(AUTH_URL + '/login.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({email, password})
        });

        const result = await readResponse(response);

        if (!response.ok || !result.success) {
            alert(result.message || 'Login failed');
            return;
        }

        localStorage.setItem('token', result.token);
        localStorage.setItem('user', JSON.stringify(result.user));

        alert('Login successful!');
        showTasks();
        await loadTasks();

    } catch (error) {
        console.error(error);
        alert('Could not connect to the server: ' + error.message);
    }
}

// Logout
function logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');

    selectedTasks.clear();

    document.getElementById('taskList').innerHTML = '';
    document.getElementById('adminTasksList').innerHTML = '';
    document.getElementById('adminUsersList').innerHTML = '';

    showLogin();
}

// Authentication headers
function getAuthHeaders() {
    return {
        'Authorization': 'Bearer ' + localStorage.getItem('token'),
        'Content-Type': 'application/json'
    };
}

// Update statistics
function updateStatistics(tasks) {
    const completed = tasks.filter(task =>
        Number(task.is_completed) === 1
    ).length;

    const completedElement = document.getElementById('completedTasks');

    if (completedElement) {
        completedElement.textContent = completed;
    }
}

// Load tasks
async function loadTasks() {
    try {
        const response = await fetch(API_URL, {
            method: 'GET',
            headers: getAuthHeaders()
        });

        const result = await readResponse(response);

        if (response.status === 401) {
            alert('Please login again');
            logout();
            return;
        }

        if (!response.ok || !result.success) {
            alert(result.message || 'Failed to load tasks');
            return;
        }

        const tasks = Array.isArray(result.data) ? result.data : [];

        const list = document.getElementById('taskList');

        if (!list) {
            throw new Error('Task list element was not found in index.php');
        }

        list.innerHTML = '';
        selectedTasks.clear();

        updateStatistics(tasks);

        tasks.forEach(task => {
            const li = document.createElement('li');
            li.dataset.taskId = task.id;

            // Multi-select checkbox
            const selectCheckbox = document.createElement('input');
            selectCheckbox.type = 'checkbox';
            selectCheckbox.className = 'task-select';
            selectCheckbox.title = 'Select task';

            selectCheckbox.onchange = function () {
                if (selectCheckbox.checked) {
                    selectedTasks.add(Number(task.id));
                } else {
                    selectedTasks.delete(Number(task.id));
                }

                updateBulkControls();
            };

            // Task title
            const title = document.createElement('span');
            title.className = 'task-title';
            title.textContent = task.title;

            // Task status
            const status = document.createElement('span');
            status.className = 'task-status';

            const isCompleted = Number(task.is_completed) === 1;

            if (isCompleted) {
                li.classList.add('task-done');
                status.textContent = 'Completed';
                status.classList.add('completed');
            } else {
                status.textContent = 'Pending';
            }

            // Action buttons
            const actions = document.createElement('div');
            actions.className = 'task-actions';

            const editButton = document.createElement('button');
            editButton.textContent = 'Edit';
            editButton.className = 'edit-btn';
            editButton.onclick = function () {
                editTask(task.id, task.title);
            };

            const completeButton = document.createElement('button');

            if (isCompleted) {
                completeButton.textContent = 'Undo';
                completeButton.className = 'undo-btn';
            } else {
                completeButton.textContent = 'Complete';
                completeButton.className = 'complete-btn';
            }

            completeButton.onclick = function () {
                updateTask(task.id, {
                    is_completed: isCompleted ? 0 : 1
                });
            };

            const deleteButton = document.createElement('button');
            deleteButton.textContent = 'Delete';
            deleteButton.className = 'delete-btn';
            deleteButton.onclick = function () {
                deleteTask(task.id, task.title);
            };

            actions.append(editButton, completeButton, deleteButton);

            li.append(
                selectCheckbox,
                title,
                status,
                actions
            );

            list.appendChild(li);
        });

        updateBulkControls();

    } catch (error) {
        console.error('Load tasks error:', error);
        alert('Could not load tasks: ' + error.message);
    }
}

// Update bulk controls
function updateBulkControls() {
    const count = selectedTasks.size;

    const countElement = document.getElementById('selectedCount');
    const buttons = document.querySelectorAll('.bulk-action');

    if (countElement) {
        countElement.textContent = count + ' selected';
    }

    buttons.forEach(button => {
        button.disabled = count === 0;
    });

    const selectAll = document.getElementById('selectAllTasks');

    if (selectAll) {
        const checkboxes = document.querySelectorAll('.task-select');

        selectAll.checked =
            checkboxes.length > 0 &&
            count === checkboxes.length;
    }
}

// Select all tasks
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAllTasks');
    const checkboxes = document.querySelectorAll('.task-select');

    if (!selectAll) return;

    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;

        const taskId = Number(
            checkbox.closest('li').dataset.taskId
        );

        if (selectAll.checked) {
            selectedTasks.add(taskId);
        } else {
            selectedTasks.delete(taskId);
        }
    });

    updateBulkControls();
}

// Bulk actions: delete, complete, incomplete
async function performBulkAction(action) {
    if (selectedTasks.size === 0) {
        alert('Please select at least one task');
        return;
    }

    const actionNames = {
        delete: 'delete',
        complete: 'mark as completed',
        incomplete: 'mark as incomplete'
    };

    const confirmed = confirm(
        'Are you sure you want to ' +
        actionNames[action] +
        ' ' + selectedTasks.size + ' selected tasks?'
    );

    if (!confirmed) return;

    try {
        const response = await fetch(BULK_URL, {
            method: 'POST',
            headers: getAuthHeaders(),
            body: JSON.stringify({
                action: action,
                ids: Array.from(selectedTasks)
            })
        });

        const result = await readResponse(response);

        if (response.status === 401) {
            alert('Please login again');
            logout();
            return;
        }

        if (!response.ok || !result.success) {
            alert(result.message || 'Bulk action failed');
            return;
        }

        selectedTasks.clear();

        alert('Bulk action completed successfully!');

        await loadTasks();

    } catch (error) {
        console.error(error);
        alert('Could not perform bulk action: ' + error.message);
    }
}

// Add one task
async function addTask() {
    const input = document.getElementById('taskInput');
    const taskTitle = input.value.trim();

    if (!taskTitle) {
        alert('Please enter a task');
        return;
    }

    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: getAuthHeaders(),
            body: JSON.stringify({title: taskTitle})
        });

        const result = await readResponse(response);

        if (response.status === 401) {
            alert('Please login again');
            logout();
            return;
        }

        if (!response.ok || !result.success) {
            alert(result.message || 'Failed to add task');
            return;
        }

        input.value = '';

        await loadTasks();

        const user = JSON.parse(localStorage.getItem('user'));

        if (user && user.role === 'admin') {
            await loadAdminTasks();
        }

    } catch (error) {
        console.error(error);
        alert('Could not add task: ' + error.message);
    }
}

// Add multiple tasks using the original bulk API
async function addMultipleTasks() {
    const input = document.getElementById('multipleTasksInput');

    if (!input) {
        alert('The multiple tasks field was not found');
        return;
    }

    const taskTitles = input.value
        .split('\n')
        .map(task => task.trim())
        .filter(task => task !== '');

    if (taskTitles.length === 0) {
        alert('Please enter at least one task');
        return;
    }

    if (taskTitles.length > 50) {
        alert('You can add a maximum of 50 tasks at once');
        return;
    }

    try {
        const response = await fetch(BULK_ADD_URL, {
            method: 'POST',
            headers: getAuthHeaders(),
            body: JSON.stringify({
                titles: taskTitles
            })
        });

        const result = await readResponse(response);

        if (response.status === 401) {
            alert('Please login again');
            logout();
            return;
        }

        if (!response.ok || !result.success) {
            alert(result.message || 'Failed to add multiple tasks');
            return;
        }

        input.value = '';

        alert(taskTitles.length + ' tasks added successfully!');

        await loadTasks();

        const user = JSON.parse(localStorage.getItem('user'));

        if (user && user.role === 'admin') {
            await loadAdminTasks();
        }

    } catch (error) {
        console.error(error);
        alert('Could not add multiple tasks: ' + error.message);
    }
}

// Edit task
async function editTask(taskId, oldTitle) {
    const newTitle = prompt('Edit task:', oldTitle);

    if (newTitle === null) return;

    if (!newTitle.trim()) {
        alert('Task title cannot be empty');
        return;
    }

    try {
        const response = await fetch(API_URL + '?id=' + taskId, {
            method: 'PUT',
            headers: getAuthHeaders(),
            body: JSON.stringify({title: newTitle.trim()})
        });

        const result = await readResponse(response);

        if (response.status === 401) {
            alert('Please login again');
            logout();
            return;
        }

        if (!response.ok || !result.success) {
            alert(result.message || 'Failed to edit task');
            return;
        }

        await loadTasks();

    } catch (error) {
        console.error(error);
        alert('Could not edit task: ' + error.message);
    }
}

// Delete task
async function deleteTask(taskId, taskTitle) {
    if (!confirm('Delete this task: ' + taskTitle + '?')) return;

    try {
        const response = await fetch(API_URL + '?id=' + taskId, {
            method: 'DELETE',
            headers: getAuthHeaders()
        });

        if (response.status === 401) {
            alert('Please login again');
            logout();
            return;
        }

        if (!response.ok) {
            const result = await readResponse(response);
            alert(result.message || 'Failed to delete task');
            return;
        }

        await loadTasks();

    } catch (error) {
        console.error(error);
        alert('Could not delete task: ' + error.message);
    }
}

// Update task
async function updateTask(taskId, values) {
    try {
        const response = await fetch(API_URL + '?id=' + taskId, {
            method: 'PUT',
            headers: getAuthHeaders(),
            body: JSON.stringify(values)
        });

        const result = await readResponse(response);

        if (response.status === 401) {
            alert('Please login again');
            logout();
            return;
        }

        if (!response.ok || !result.success) {
            alert(result.message || 'Failed to update task');
            return;
        }

        await loadTasks();

    } catch (error) {
        console.error(error);
        alert('Could not update task: ' + error.message);
    }
}

// Load admin data
async function loadAdminData() {
    await loadAdminTasks();
    await loadAdminUsers();
}

// Load all tasks for admin
async function loadAdminTasks() {
    try {
        const response = await fetch(ADMIN_URL + '/tasks.php', {
            method: 'GET',
            headers: getAuthHeaders()
        });

        const result = await readResponse(response);

        if (response.status === 401) {
            alert('Please login again');
            logout();
            return;
        }

        if (!response.ok || !result.success) {
            alert(result.message || 'Could not load admin tasks');
            return;
        }

        const container = document.getElementById('adminTasksList');
        container.innerHTML = '';

        if (!result.tasks || result.tasks.length === 0) {
            container.textContent = 'No tasks found.';
            return;
        }

        result.tasks.forEach(task => {
            const item = document.createElement('div');
            item.className = 'admin-item';

            const details = document.createElement('span');

            details.textContent =
                task.title + ' | Owner: ' +
                (task.owner_name || 'Unknown') + ' (' +
                (task.owner_email || 'No email') + ')';

            item.appendChild(details);

            const deleteButton = document.createElement('button');
            deleteButton.className = 'delete-btn';
            deleteButton.textContent = 'Delete';

            deleteButton.onclick = function () {
                deleteAdminTask(task.id, task.title);
            };

            item.appendChild(deleteButton);
            container.appendChild(item);
        });

    } catch (error) {
        console.error(error);
        alert('Could not load admin tasks: ' + error.message);
    }
}

// Admin deletes any task
async function deleteAdminTask(taskId, taskTitle) {
    const confirmed = confirm(
        'Are you sure you want to delete this task: ' + taskTitle + '?'
    );

    if (!confirmed) return;

    try {
        const response = await fetch(ADMIN_URL + '/tasks.php', {
            method: 'DELETE',
            headers: getAuthHeaders(),
            body: JSON.stringify({id: taskId})
        });

        const result = await readResponse(response);

        if (response.status === 401) {
            alert('Please login again');
            logout();
            return;
        }

        if (!response.ok || !result.success) {
            alert(result.message || 'Failed to delete task');
            return;
        }

        alert('Task deleted successfully!');

        await loadAdminTasks();
        await loadTasks();

    } catch (error) {
        console.error(error);
        alert('Could not delete task: ' + error.message);
    }
}

// Load all users
async function loadAdminUsers() {
    try {
        const response = await fetch(ADMIN_URL + '/users.php', {
            method: 'GET',
            headers: getAuthHeaders()
        });

        const result = await readResponse(response);

        if (response.status === 401) {
            alert('Please login again');
            logout();
            return;
        }

        if (!response.ok || !result.success) {
            alert(result.message || 'Could not load users');
            return;
        }

        const container = document.getElementById('adminUsersList');
        container.innerHTML = '';

        const currentUser = JSON.parse(localStorage.getItem('user'));

        if (!result.users || result.users.length === 0) {
            container.textContent = 'No users found.';
            return;
        }

        result.users.forEach(user => {
            const item = document.createElement('div');
            item.className = 'admin-item';

            const details = document.createElement('span');

            details.textContent =
                user.name + ' | ' + user.email + ' | Role: ' + user.role;

            item.appendChild(details);

            if (
                currentUser &&
                Number(user.id) !== Number(currentUser.id)
            ) {
                const deleteButton = document.createElement('button');
                deleteButton.className = 'delete-btn';
                deleteButton.textContent = 'Delete';

                deleteButton.onclick = function () {
                    deleteUser(user.id, user.name);
                };

                item.appendChild(deleteButton);
            }

            container.appendChild(item);
        });

    } catch (error) {
        console.error(error);
        alert('Could not load users: ' + error.message);
    }
}

// Delete user
async function deleteUser(userId, userName) {
    const confirmed = confirm(
        'Are you sure you want to delete ' +
        userName + ' and their tasks?'
    );

    if (!confirmed) return;

    try {
        const response = await fetch(ADMIN_URL + '/users.php', {
            method: 'DELETE',
            headers: getAuthHeaders(),
            body: JSON.stringify({id: userId})
        });

        const result = await readResponse(response);

        if (response.status === 401) {
            alert('Please login again');
            logout();
            return;
        }

        if (!response.ok || !result.success) {
            alert(result.message || 'Failed to delete user');
            return;
        }

        alert('User deleted successfully!');

        await loadAdminData();

    } catch (error) {
        console.error(error);
        alert('Could not delete user: ' + error.message);
    }
}

// Check login when page opens
window.addEventListener('DOMContentLoaded', function () {
    const token = localStorage.getItem('token');
    const user = localStorage.getItem('user');

    if (token && user) {
        try {
            showTasks();
            loadTasks();
        } catch (error) {
            console.error(error);
            logout();
        }
    } else {
        showLogin();
    }
});