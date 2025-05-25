<?php
require_once("database.php");
session_start();

header("Content-Type: application/json");

// Kiểm tra đăng nhập
if (!isset($_SESSION['login'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn chưa đăng nhập']);
    exit;
}

// Lấy dữ liệu từ request
$data = json_decode(file_get_contents('php://input'), true);

// Kiểm tra dữ liệu đầu vào
if (!$data || !isset($data['tentieuthuyet']) || !isset($data['trangdanhdau'])) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

$tentieuthuyet = $data['tentieuthuyet'];
$trangdanhdau = (int)$data['trangdanhdau'];

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

    // Kiểm tra xem đã có bookmark cho sách này chưa
    $stmt = $pdo->prepare("SELECT * FROM lichsudoc WHERE matk = ? AND tentieuthuyet = ?");
    $stmt->execute([$matk, $tentieuthuyet]);
    
    if ($stmt->rowCount() > 0) {
        // Cập nhật bookmark hiện có
        $updateStmt = $pdo->prepare("UPDATE lichsudoc SET trangdanhdau = ? WHERE matk = ? AND tentieuthuyet = ?");
        $result = $updateStmt->execute([$trangdanhdau, $matk, $tentieuthuyet]);
    } else {
        // Tạo bookmark mới
        $insertStmt = $pdo->prepare("INSERT INTO lichsudoc (matk, tentieuthuyet, trangdanhdau) VALUES (?, ?, ?)");
        $result = $insertStmt->execute([$matk, $tentieuthuyet, $trangdanhdau]);
    }
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => "Đã đánh dấu trang $trangdanhdau"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không thể lưu đánh dấu trang.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi cơ sở dữ liệu: ' . $e->getMessage()]);
}
?>