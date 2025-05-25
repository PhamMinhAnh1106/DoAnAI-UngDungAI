<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['matk'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$matk = $_SESSION['matk'];
$conn = new mysqli('127.0.0.1', 'root', '', 'novelreaderdb');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối database']);
    exit;
}

$sql = "SELECT tentieuthuyet, tranghientai, trangdanhdau FROM lichsudoc WHERE matk = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $matk);
$stmt->execute();
$result = $stmt->get_result();

$history = [];
while ($row = $result->fetch_assoc()) {
    $history[] = $row;
}

echo json_encode(['success' => true, 'history' => $history]);

$stmt->close();
$conn->close();
?>