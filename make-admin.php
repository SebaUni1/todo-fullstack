<?php
require 'db.php';

// Add role column if it does not exist
$columns = $db->query("PRAGMA table_info(users)")
              ->fetchAll(PDO::FETCH_COLUMN, 1);

if (!in_array('role', $columns, true)) {
    $db->exec("ALTER TABLE users ADD COLUMN role TEXT NOT NULL DEFAULT 'user'");
}

// Make Saba an admin
$stmt = $db->prepare("UPDATE users SET role = 'admin' WHERE email = ?");
$stmt->execute(['saba@gmail.com']);

if ($stmt->rowCount() > 0) {
    echo "Admin updated successfully!";
} else {
    echo "No matching user found!";
}
?>
