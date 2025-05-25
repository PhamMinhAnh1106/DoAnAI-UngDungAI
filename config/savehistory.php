<?php
session_start();
header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['matk'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$matk = $_SESSION['matk'];
$data = json_decode(file_get_contents('php://input'), true);
$tentieuthuyet = $data['tentieuthuyet'] ?? '';
$tranghientai = isset($data['tranghientai']) ? (int)$data['tranghientai'] : 0;
$trangdanhdau = isset($data['trangdanhdau']) ? (int)$data['trangdanhdau'] : null;

if (empty($tentieuthuyet)) {
    echo json_encode(['success' => false, 'message' => 'Tên tiểu thuyết không hợp lệ']);
    exit;
}

// Kết nối database
$conn = new mysqli('127.0.0.1', 'root', '', 'novelreaderdb');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối database']);
    exit;
}

// Kiểm tra xem đã có bản ghi cho tiểu thuyết này chưa
$sql = "SELECT malichsu, trangdanhdau FROM lichsudoc WHERE matk = ? AND tentieuthuyet = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('is', $matk, $tentieuthuyet);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Cập nhật bản ghi hiện có
    $row = $result->fetch_assoc();
    $existing_trangdanhdau = $row['trangdanhdau'];

    if (isset($data['trangdanhdau'])) {
        // Nếu là đánh dấu trang, chỉ cập nhật trangdanhdau
        $sql = "UPDATE lichsudoc SET trangdanhdau = ? WHERE matk = ? AND tentieuthuyet = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iis', $trangdanhdau, $matk, $tentieuthuyet);
    } else {
        // Nếu là cập nhật trang hiện tại, giữ nguyên trangdanhdau
        $sql = "UPDATE lichsudoc SET tranghientai = ?, trangdanhdau = ? WHERE matk = ? AND tentieuthuyet = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iiis', $tranghientai, $existing_trangdanhdau, $matk, $tentieuthuyet);
    }
} else {
    // Thêm mới bản ghi
    $sql = "INSERT INTO lichsudoc (matk, tentieuthuyet, tranghientai, trangdanhdau) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('isii', $matk, $tentieuthuyet, $tranghientai, $trangdanhdau);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi lưu lịch sử đọc']);
}

$stmt->close();
$conn->close();
?>