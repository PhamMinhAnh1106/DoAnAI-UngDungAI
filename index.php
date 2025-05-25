<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Đọc Tiểu Thuyết - Dành Cho Người Bận Rộn</title>
    <link rel="stylesheet" href="./css/css4trangchu.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="navbar-brand">
            <img src="./image/iconlogo.png" class="brand-icon" alt="Logo"> Web Đọc Tiểu Thuyết
        </div>
        <div class="navbar-buttons">
            <input type="file" id="fileInput" style="display: none;" accept=".pdf">
            <button class="navbar-btn" onclick="document.getElementById('fileInput').click()">Upload Tiểu Thuyết</button>
            <button class="navbar-btn" onclick="bookmarkPage()">Đánh Dấu Trang</button>
            <button class="navbar-btn" onclick="goToBookmark()">Đi đến trang đánh dấu</button>
            <button class="navbar-btn" onclick="toggleNightMode()">Chế Độ Đọc</button>
            <button class="navbar-btn" onclick="toggleHistory()">Lịch Sử Đọc</button>
            
            <!-- Thêm phần user info và logout -->
            <div id="userInfo" class="user-info-container">
                <!-- Nội dung sẽ được cập nhật động bởi JavaScript -->
                <a href="signin.php" class="btn btn-login">Đăng nhập</a>
            </div>
        </div>
    </div>

    <!-- Main Container -->
    <div class="chat-container">
        <!-- Cột nội dung PDF -->
        <div class="column pdf-column">
            <div class="header">
                <img src="./image/iconlogo.png" alt="Logo">
                <h3>Nội Dung Tiểu Thuyết</h3>
            </div>
            <div id="pdfContent">
                <p>Chào mừng bạn đến với Web Đọc Tiểu Thuyết! Hãy upload file PDF để bắt đầu đọc.</p>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <button class="btn-nav" onclick="previousPage()">Trang Trước</button>
                <button class="btn-nav" onclick="nextPage()">Trang Sau</button>
            </div>
        </div>
        <!-- Cột tóm tắt tự động -->
        <div class="column summary-column">
            <div class="header">
                <img src="./image/iconphanhoi.png" alt="Tóm tắt">
                <h3>Tóm Tắt Trang Hiện Tại</h3>
            </div>
            <div id="summaryContent">
                <p>Tóm tắt sẽ hiển thị tự động sau khi bạn upload và xem trang.</p>
            </div>
            <button class="btn-toggle-summary" onclick="toggleSummaryMode()">Chuyển Chế Độ Tóm Tắt (6-9 câu)</button>
        </div>
    </div>

    <!-- Modal lịch sử đọc -->
    <div class="history-modal" id="historyModal">
        <div class="history-content">
            <span class="close-btn" onclick="toggleHistory()">×</span>
            <h5>Lịch Sử Đọc</h5>
            <div id="historyList"></div>
        </div>
    </div>
    <script src="./js/js4trangchu.js"></script>
</body>
</html>