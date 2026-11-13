<?php

require 'db.php';

echo "Database connected successfully!" . PHP_EOL;

$columns = $db->query("PRAGMA table_info(tasks)")
              ->fetchAll(PDO::FETCH_COLUMN, 1);

print_r($columns);

?>