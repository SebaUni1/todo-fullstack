<?php

try {
    // Connect to SQLite database
    $db = new PDO('sqlite:' . __DIR__ . '/todo.db');

    // Enable error reporting
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Enable foreign keys
    $db->exec("PRAGMA foreign_keys = ON");

    // Create users table
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create tasks table for new databases
    $db->exec("CREATE TABLE IF NOT EXISTS tasks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        is_completed BOOLEAN NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");

    // Check if user_id column exists
    $columns = $db->query("PRAGMA table_info(tasks)")
                  ->fetchAll(PDO::FETCH_COLUMN, 1);

    // Add user_id to old databases
    if (!in_array('user_id', $columns, true)) {
        $db->exec("ALTER TABLE tasks ADD COLUMN user_id INTEGER REFERENCES users(id)");
    }

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit;
}

?>