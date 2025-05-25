let readingHistory = [];
let pdfText = "";
let currentPage = 1;
let totalPages = 0;
let pdfDoc = null;
let summaryVisible = true;
let isDetailedMode = true; // Mặc định là chế độ chi tiết (6-9 câu)
let isLoggedIn = false; // Biến để kiểm tra trạng thái đăng nhập
let currentFileName = null; // Biến để lưu tên file hiện tại đang upload
let currentBookmark = null; // Biến để lưu trang đánh dấu tạm thời cho file hiện tại

window.onload = function () {
    document.getElementById('pdfContent').innerHTML = '<p>Chào mừng bạn đến với Web Đọc Tiểu Thuyết! Hãy upload file PDF để bắt đầu đọc.</p>';

    checkLoginStatus();
};

async function checkLoginStatus() {
    try {
        const response = await fetch('./check_login.php');
        const data = await response.json();
        isLoggedIn = data.loggedin;
        updateLoginUI(data);
        if (isLoggedIn) {
            await loadReadingHistoryFromServer(); // Đảm bảo lấy lịch sử trước khi cập nhật giao diện
        } else {
            readingHistory = []; // Không dùng localStorage khi chưa đăng nhập
            updateHistory();
        }
    } catch (error) {
        console.error('Lỗi kiểm tra đăng nhập:', error);
        isLoggedIn = false;
        readingHistory = [];
        updateLoginUI({});
        updateHistory();
    }
}

// Thêm hàm xử lý logout
async function logout() {
    try {
        const response = await fetch('./logout.php');
        const data = await response.json();
        if (data.success) {
            isLoggedIn = false;
            readingHistory = [];
            currentFileName = null;
            currentBookmark = null;
            updateLoginUI({});
            updateHistory();
            window.location.reload();
        }
    } catch (error) {
        console.error('Lỗi khi đăng xuất:', error);
    }
}

// Cập nhật giao diện đăng nhập
function updateLoginUI(userData) {
    const userInfoContainer = document.getElementById('userInfo');
    
    if (isLoggedIn && userData.tentaikhoan) {
        userInfoContainer.innerHTML = `
            <button class="navbar-btn btn-logout" onclick="logout()">Đăng xuất</button>
        `;
    } else {
        userInfoContainer.innerHTML = `
            <a href="signin.php" class="navbar-btn btn-login">Đăng nhập</a>
        `;
    }
}

// Tải lịch sử đọc từ server
async function loadReadingHistoryFromServer() {
    try {
        const response = await fetch('./config/gethistory.php');
        const data = await response.json();
        
        console.log('Dữ liệu từ gethistory.php:', data); // Log để kiểm tra dữ liệu trả về
        
        if (data.success && data.history && data.history.length > 0) {
            readingHistory = data.history.map(item => ({
                fileName: item.tentieuthuyet,
                currentPage: item.tranghientai,
                bookmarkedPage: item.trangdanhdau
            }));
            updateHistory(); // Cập nhật giao diện ngay sau khi lấy dữ liệu
        } else {
            console.warn('Không có lịch sử đọc hoặc lỗi từ server:', data.message);
            readingHistory = [];
            updateHistory(); // Vẫn cập nhật giao diện để hiển thị thông báo
        }
    } catch (error) {
        console.error('Lỗi khi tải lịch sử đọc:', error);
        readingHistory = [];
        updateHistory();
    }
}

async function uploadFile() {
    const fileInput = document.getElementById('fileInput');
    const file = fileInput.files[0];
    const pdfContentDiv = document.getElementById('pdfContent');

    if (!file) {
        pdfContentDiv.innerHTML = '<p>⚠️ Vui lòng chọn một file PDF để upload.</p>';
        return;
    }

    if (file.name.split('.').pop().toLowerCase() !== 'pdf') {
        pdfContentDiv.innerHTML = '<p>❌ Chỉ hỗ trợ file PDF.</p>';
        return;
    }

    pdfContentDiv.innerHTML = '<p>⏳ Đang xử lý file...</p>';

    const reader = new FileReader();
    reader.onload = async function () {
        try {
            const typedArray = new Uint8Array(this.result);
            pdfDoc = await pdfjsLib.getDocument(typedArray).promise;
            totalPages = pdfDoc.numPages;
            currentPage = 1;
            currentFileName = file.name;
            currentBookmark = null; // Reset trang đánh dấu tạm thời khi upload file mới

            displayPage(currentPage);
            if (isLoggedIn) {
                await saveHistoryToServer(file.name, { tranghientai: currentPage }); // Lưu trang hiện tại
                await loadReadingHistoryFromServer(); // Tải lại lịch sử để cập nhật
            }
            fileInput.value = '';
        } catch (error) {
            pdfContentDiv.innerHTML = '<p>❌ Lỗi khi đọc tệp PDF: ' + error.message + '</p>';
        }
    };
    reader.readAsArrayBuffer(file);
}

async function displayPage(pageNum) {
    if (!pdfDoc) return;
    const pdfContentDiv = document.getElementById('pdfContent');
    pdfContentDiv.innerHTML = '<p>⏳ Đang tải trang...</p>';
    try {
        const page = await pdfDoc.getPage(pageNum);
        const textContent = await page.getTextContent();
        const pageText = textContent.items.map(item => item.str).join(" ");
        pdfContentDiv.innerHTML = `<p><strong>Trang ${pageNum}/${totalPages}</strong></p><p>${pageText}</p>`;
        if (summaryVisible) {
            summarizePage(pageText, pageNum);
        }
    } catch (error) {
        pdfContentDiv.innerHTML = '<p>❌ Lỗi khi tải trang: ' + error.message + '</p>';
    }
}

async function summarizePage(pageText, pageNum) {
    const summaryContentDiv = document.getElementById('summaryContent');
    summaryContentDiv.innerHTML = '<p>⏳ Đang tóm tắt...</p>';

    try {
        pageText = pageText.normalize('NFKD').replace(/[^\x00-\x7F]/g, '');
        const response = await fetch('https://openrouter.ai/api/v1/chat/completions', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer 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',
                'HTTP-Referer': '#',
                'X-Title': 'Web_Doc_Tieu_Thuyet',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                model: 'deepseek/deepseek-chat:free',
                messages: [
                    {
                        role: 'system',
                        content: isDetailedMode 
                            ? 'Bạn là một trợ lý thông minh, hãy tóm tắt nội dung của một trang tiểu thuyết thành 6-9 câu, giữ nguyên ngữ cảnh, giai điệu và cảm xúc của văn bản gốc. Đảm bảo tóm tắt chi tiết, bao gồm các chi tiết quan trọng như nhân vật, sự kiện chính, bối cảnh, và giọng văn để phản ánh đúng tinh thần của trang.'
                            : 'Bạn là một trợ lý thông minh, hãy tóm tắt nội dung của một trang tiểu thuyết thành 3-5 câu, cô đọng nhưng vẫn giữ ý chính và ngữ cảnh cơ bản.'
                    },
                    {
                        role: 'user',
                        content: `Tóm tắt nội dung trang tiểu thuyết sau thành ${isDetailedMode ? '6-9' : '3-5'} câu: ${pageText}`
                    }
                ],
            }),
        });

        const data = await response.json();
        if (!response.ok) throw new Error(data.error?.message || 'Lỗi từ server API');
        summaryContentDiv.innerHTML = `<h4>Tóm Tắt Trang ${pageNum}</h4><p>${data.choices[0].message.content}</p>`;
        document.querySelector('.btn-toggle-summary').textContent = `Chuyển Chế Độ Tóm Tắt (${isDetailedMode ? '3-5 câu' : '6-9 câu'})`;
    } catch (error) {
        summaryContentDiv.innerHTML = `<p>❌ Lỗi khi tóm tắt: ${error.message}</p>`;
    }
}

function previousPage() {
    if (currentPage > 1) {
        currentPage--;
        displayPage(currentPage);
        if (isLoggedIn) {
            updateCurrentPageInHistory();
        }
    }
}

function nextPage() {
    if (currentPage < totalPages) {
        currentPage++;
        displayPage(currentPage);
        if (isLoggedIn) {
            updateCurrentPageInHistory();
        }
    }
}

async function updateCurrentPageInHistory() {
    if (!currentFileName) return;
    const existingIndex = readingHistory.findIndex(item => item.fileName === currentFileName);
    if (existingIndex !== -1) {
        readingHistory[existingIndex].currentPage = currentPage;
        await saveHistoryToServer(currentFileName, { tranghientai: currentPage });
        updateHistory();
    }
}

// Lưu lịch sử đọc lên server
async function saveHistoryToServer(fileName, data) {
    try {
        const response = await fetch('./config/savehistory.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                tentieuthuyet: fileName,
                ...data // Có thể chứa tranghientai hoặc trangdanhdau
            }),
        });
        
        const responseData = await response.json();
        if (!responseData.success) {
            console.error('Lỗi lưu lịch sử lên server:', responseData.message);
        }
    } catch (error) {
        console.error('Lỗi kết nối server:', error);
    }
}

// Cập nhật hàm đánh dấu trang
async function bookmarkPage() {
    if (!pdfDoc) {
        document.getElementById('pdfContent').innerHTML = '<p>⚠️ Vui lòng upload file PDF trước khi đánh dấu.</p>';
        setTimeout(() => { displayPage(currentPage); }, 1000);
        return;
    }
    
    if (!currentFileName) {
        document.getElementById('pdfContent').innerHTML = '<p>⚠️ Không tìm thấy thông tin sách.</p>';
        setTimeout(() => { displayPage(currentPage); }, 1000);
        return;
    }
    
    if (isLoggedIn) {
        const existingIndex = readingHistory.findIndex(item => item.fileName === currentFileName);
        if (existingIndex !== -1) {
            readingHistory[existingIndex].bookmarkedPage = currentPage;
        }
        await saveHistoryToServer(currentFileName, { trangdanhdau: currentPage }); // Lưu trang đánh dấu
        await loadReadingHistoryFromServer(); // Tải lại lịch sử để cập nhật
        document.getElementById('pdfContent').innerHTML = `<p>Đã đánh dấu trang ${currentPage}.</p>`;
    } else {
        currentBookmark = currentPage; // Lưu tạm thời cho file hiện tại
        document.getElementById('pdfContent').innerHTML = `
            <p>Đã đánh dấu trang ${currentPage} (chỉ áp dụng cho file hiện tại).</p>
            <p><small>Đăng nhập để lưu đánh dấu trên tất cả thiết bị.</small></p>
        `;
    }
    
    setTimeout(() => { displayPage(currentPage); }, 1000);
}

// Hàm đi đến trang đánh dấu
function goToBookmark() {
    if (!currentFileName) {
        document.getElementById('pdfContent').innerHTML = '<p>⚠️ Vui lòng upload file PDF trước.</p>';
        return;
    }
    
    if (isLoggedIn) {
        const existingIndex = readingHistory.findIndex(item => item.fileName === currentFileName);
        if (existingIndex !== -1 && readingHistory[existingIndex].bookmarkedPage) {
            currentPage = readingHistory[existingIndex].bookmarkedPage;
            displayPage(currentPage);
        } else {
            document.getElementById('pdfContent').innerHTML = '<p>⚠️ Sách này chưa có trang đánh dấu.</p>';
            setTimeout(() => { displayPage(currentPage); }, 1000);
        }
    } else if (currentBookmark) {
        currentPage = currentBookmark;
        displayPage(currentPage);
    } else {
        document.getElementById('pdfContent').innerHTML = '<p>⚠️ File hiện tại chưa có trang đánh dấu.</p>';
        setTimeout(() => { displayPage(currentPage); }, 1000);
    }
}

function toggleNightMode() {
    document.body.classList.toggle('night-mode');
}

function toggleSummaryMode() {
    isDetailedMode = !isDetailedMode;
    const toggleButton = document.querySelector('.btn-toggle-summary');
    toggleButton.textContent = `Chuyển Chế Độ Tóm Tắt (${isDetailedMode ? '3-5 câu' : '6-9 câu'})`;
    if (summaryVisible && pdfDoc) {
        displayPage(currentPage);
    }
}

async function updateHistoryForFile(fileName) {
    const existingIndex = readingHistory.findIndex(item => item.fileName === fileName);
    if (existingIndex !== -1) {
        readingHistory[existingIndex].currentPage = currentPage;
    } else {
        readingHistory.push({ fileName: fileName, currentPage: currentPage });
    }
    updateHistory();
}

function updateHistory() {
    const historyList = document.getElementById('historyList');
    historyList.innerHTML = '';
    if (isLoggedIn) {
        if (readingHistory.length > 0) {
            readingHistory.forEach((item, index) => {
                const div = document.createElement('div');
                div.className = 'history-item';
                div.innerHTML = `<strong>Tiểu Thuyết:</strong> ${item.fileName}<br>
                                <strong>Trang Hiện Tại:</strong> ${item.currentPage}<br>
                                ${item.bookmarkedPage ? `<strong>Trang Đánh Dấu:</strong> ${item.bookmarkedPage}` : ''}`;
                div.onclick = () => {
                    currentPage = item.currentPage;
                    currentFileName = item.fileName;
                    displayPage(currentPage);
                    toggleHistory();
                };
                historyList.appendChild(div);
            });
        } else {
            historyList.innerHTML = '<p>Chưa có lịch sử đọc nào.</p>';
        }
    } else {
        historyList.innerHTML = '<p>Đăng nhập để xem lịch sử đọc.</p>';
    }
}

function toggleHistory() {
    const modal = document.getElementById('historyModal');
    modal.style.display = modal.style.display === 'flex' ? 'none' : 'flex';
}

// Các sự kiện
document.getElementById('fileInput').addEventListener('change', uploadFile);