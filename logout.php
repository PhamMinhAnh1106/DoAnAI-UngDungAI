<?php
session_start();

// Xóa tất cả biến session
$_SESSION = array();

// Hủy session
session_destroy();

// Trả về kết quả JSON
header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Đăng xuất thành công']);
exit;
?>