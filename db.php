<?php
try {
    // إنشاء أو الاتصال بقاعدة بيانات SQLite
$db = new PDO('sqlite:' . __DIR__ . '/todo.db');
    
    // تفعيل وضع الأخطاء لتظهر في حال وجود أي مشكلة
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // إنشاء جدول المهام بالأسماء الصحيحة والدقيقة
    $db->exec("CREATE TABLE IF NOT EXISTS tasks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        is_completed BOOLEAN NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit;
}
?>