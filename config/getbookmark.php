<?php
session_start();
require_once 'database.php';

// Kiểm tra người dùng đã đăng nhập chưa
if (!isset($_SESSION['login'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn chưa đăng nhập']);
    exit;
}

try {
    // Lấy matk từ session
    $username = $_SESSION['login'];
    $stmt = $pdo->prepare("SELECT matk FROM taikhoan WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Người dùng không tồn tại']);
        exit;
    }
    
    $matk = $user['matk'];

    // Lấy tất cả các trang đánh dấu của người dùng
    $stmt = $pdo->prepare("SELECT tentieuthuyet, trangdanhdau FROM lichsudoc WHERE matk = ?");
    $stmt->execute([$matk]);
    
    $bookmarks = [];
    while ($row = $stmt->fetch()) {
        $bookmarks[] = [
            'fileName' => $row['tentieuthuyet'],
            'bookmarkedPage' => $row['trangdanhdau']
        ];
    }
    
    echo json_encode(['success' => true, 'bookmarks' => $bookmarks]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi cơ sở dữ liệu: ' . $e->getMessage()]);
}
?>