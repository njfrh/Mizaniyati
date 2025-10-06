<?php
$host = "127.0.0.1";
$port = 3306;
$db   = "mizaniyati";
$user = "root";
$pass = "root";

$conn = new mysqli($host, $user, $pass, $db, $port);

// التأكد من الاتصال
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ضبط الترميز لدعم العربية
$conn->set_charset("utf8mb4");
?>