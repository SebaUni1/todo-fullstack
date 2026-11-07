const API_URL = 'api/tasks';

// Get all tasks
async function getTasks() {
    const response = await fetch(API_URL + '/');
    const result = await response.json();

    if (!response.ok || !result.success) {
        throw new Error(result.message || 'Failed to get tasks');
    }

    return result.data;
}

// Add one task
async function createTask(title) {
    return sendRequest(API_URL + '/', 'POST', { title });
}

// Update a task
async function updateTask(id, data) {
    return sendRequest(API_URL + '/index.php?id=' + id, 'PUT', data);
}

// Delete a task
async function deleteTask(id) {
    const response = await fetch(
        API_URL + '/index.php?id=' + id,
        { method: 'DELETE' }
    );

    if (!response.ok) {
        throw new Error('Failed to delete task');
    }
}

// Add multiple tasks
async function bulkCreateTasks(titles) {
    return sendRequest(API_URL + '/bulk.php', 'POST', {
        titles: titles
    });
}

// Send POST or PUT request
async function sendRequest(url, method, data) {
    const response = await fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    });

    const result = await response.json();

    if (!response.ok || !result.success) {
        throw new Error(result.message || 'Request failed');
    }

    return result.data;
}