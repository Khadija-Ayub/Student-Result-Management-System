<?php
// ─────────────────────────────────────────
//  Database Configuration
//  Edit DB_PASS if your MySQL has a password
// ─────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'yourpassword');           // XAMPP default is empty
define('DB_NAME', 'result_system');
define('APP_NAME', 'ResultMS');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;padding:40px;color:#c0392b;">
         <h2>Database Connection Failed</h2>
         <p>' . $conn->connect_error . '</p>
         <p>Make sure XAMPP MySQL is running and you have imported <code>database/result_system.sql</code></p>
         </div>');
}

$conn->set_charset('utf8mb4');