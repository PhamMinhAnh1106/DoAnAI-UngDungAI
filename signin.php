<?php
require_once 'config/database.php'; // Kết nối database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'login') {
            $username = $_POST["username"];
            $password = md5($_POST["password"]);

            $result = $pdo->prepare("SELECT * FROM taikhoan WHERE username = :username AND password = :password");
            $result->bindValue("username", $username);
            $result->bindValue("password", $password);
            $result->execute();
            $row = $result->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $_SESSION['login'] = $row['username'];
                $_SESSION['matk'] = $row['matk'];
                header("Location: index.php");
                exit;
            } else {
                $_SESSION['error'] = 'Tên đăng nhập hoặc mật khẩu không đúng!';
            }
        } elseif ($_POST['action'] == 'register') {
            $username = trim($_POST["username"]);
            $tentaikhoan = trim($_POST["tentaikhoan"]); // Thêm tên tài khoản
            $password = trim($_POST["password"]);

            $check = $pdo->prepare("SELECT COUNT(*) FROM taikhoan WHERE username = ?");
            $check->execute([$username]);
            if ($check->fetchColumn() > 0) {
                $_SESSION['error'] = 'Tên đăng nhập đã tồn tại!';
            } else {
                $hashed_password = md5($password);
                $stmt = $pdo->prepare("INSERT INTO taikhoan (username, tentaikhoan, password) VALUES (?, ?, ?)");
                if ($stmt->execute([$username, $tentaikhoan, $hashed_password])) {
                    $_SESSION['login'] = $username;
                    $_SESSION['success'] = 'Đăng ký thành công!';
                } else {
                    $_SESSION['error'] = 'Đăng ký thất bại, vui lòng thử lại!';
                }
            }
        }
    } else {
        $_SESSION['error'] = 'Hành động không hợp lệ!';
    }
    header("Location: signin.php");
    exit;
}

$success_message = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$error_message = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['success']);
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/signin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <title>NovelReader Login Page</title>
</head>
<body>
    <div class="container" id="container" class="<?php echo $success_message ? '' : 'active'; ?>">
        <?php if ($success_message): ?>
            <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <div class="form-container sign-up">
            <form method="post">
                <h1>Tạo Tài Khoản</h1>
                <div class="social-icons">
                    <a href="#" class="icon"><i class="fa-brands fa-google-plus-g"></i></a>
                    <a href="#" class="icon"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" class="icon"><i class="fa-brands fa-github"></i></a>
                    <a href="#" class="icon"><i class="fa-brands fa-linkedin-in"></i></a>
                </div>
                <input type="hidden" name="action" value="register">
                <input type="text" name="username" placeholder="Username" required>
                <input type="text" name="tentaikhoan" placeholder="Tên tài khoản" required> <!-- Thêm input cho tên tài khoản -->
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Đăng Ký</button>
            </form>
        </div>
        <div class="form-container sign-in">
            <form method="post">
                <h1>Đăng Nhập</h1>
                <div class="social-icons">
                    <a href="#" class="icon"><i class="fa-brands fa-google-plus-g"></i></a>
                    <a href="#" class="icon"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" class="icon"><i class="fa-brands fa-github"></i></a>
                    <a href="#" class="icon"><i class="fa-brands fa-linkedin-in"></i></a>
                </div>
                <input type="hidden" name="action" value="login">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <a href="#">Quên Mật Khẩu ?</a>
                <button type="submit">Đăng Nhập</button>
            </form>
        </div>
        <div class="toggle-container">
            <div class="toggle">
                <div class="toggle-panel toggle-left">
                    <h1>Xin Chào bạn mới!</h1>
                    <p>Hãy đăng nhập để sử dụng dịch vụ tóm tắt tiểu thuyết, truyện nào !</p>
                    <button class="hidden" id="login">Đăng Nhập</button>
                </div>
                <div class="toggle-panel toggle-right">
                    <h1>Xin Chào đọc giả</h1>
                    <p>Hãy đăng ký nếu chưa có tài khoản để mang lại trải nghiệm tốt nhất nhé!</p>
                    <button class="hidden" id="register">Đăng Ký</button>
                </div>
            </div>
        </div>
    </div>
    <script src="js/main.js"></script>
</body>
</html>