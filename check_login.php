<?php
session_start();
header('Content-Type: application/json');

if (isset($_SESSION['login'])) {
    echo json_encode([
        'loggedin' => true,
        'tentaikhoan' => $_SESSION['login']
    ]);
} else {
    echo json_encode([
        'loggedin' => false
    ]);
}
?>