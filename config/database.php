<?php
require 'constants.php';
session_start();
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->query("set names 'UTF8'");
} catch (Exception $e) {
    echo "Lỗi kết nối MySQL: " . $e->getMessage();
}

?>
