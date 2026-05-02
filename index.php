<?php session_start(); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>09 Homestay | Nghỉ dưỡng tuyệt vời</title>
    <link rel="stylesheet" href="style.css">
    <!-- Thư viện icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <!-- Gọi Header -->
    <?php include 'header.php'; ?>

    <!-- Hero Banner & Form Kiểm tra phòng -->
    <section class="hero">
        <div class="search-box">
            <h2>Chào mừng đến với 09 Homestay</h2>
            <p>Tận hưởng không gian bình yên và dịch vụ tuyệt vời cho kỳ nghỉ của bạn.</p>
            
            <form id="searchForm" class="search-form">
                <div class="input-group">
                    <i class="far fa-calendar-alt"></i>
                    <input type="date" id="checkIn" required title="Ngày nhận phòng">
                </div>
                <div class="input-group">
                    <i class="far fa-calendar-check"></i>
                    <input type="date" id="checkOut" required title="Ngày trả phòng">
                </div>
                <div class="input-group">
                    <i class="fas fa-user-friends"></i>
                    <input type="number" id="guests" placeholder="Số người" min="1" value="2">
                </div>
                <button type="button" class="btn-search" onclick="checkAvailability()">KIỂM TRA GIÁ</button>
            </form>
            
            <div id="searchResultMsg" class="search-msg"></div>
        </div>
    </section>

    <main>
        <!-- THANH TÌM KIẾM CÓ GỢI Ý (LIVE SEARCH) --> 
        <div style="max-width: 1200px; margin: 40px auto 20px; padding: 0 20px;">
            <div style="position: relative; max-width: 600px; margin: 0 auto;">
                <form method="GET" action="index.php" style="display: flex; box-shadow: 0 4px 15px rgba(0,0,0,0.08); border-radius: 50px; background: white; border: 1px solid #ddd; z-index: 2; position: relative;">
                    
                    <input type="text" name="keyword" id="searchInput" autocomplete="off" onkeyup="showSuggestions(this.value)" placeholder="Tìm theo tên phòng, tiện ích (VD: ban công, cửa sổ)..." 
                           value="<?php echo isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : ''; ?>" 
                           style="flex: 1; padding: 16px 25px; border: none; outline: none; font-size: 16px; border-radius: 50px 0 0 50px; background: transparent;">
                    
                    <button type="submit" style="padding: 0 35px; background: #0071c2; color: white; border: none; font-size: 16px; cursor: pointer; font-weight: bold; border-radius: 0 50px 50px 0;">
                        <i class="fas fa-search"></i>
                    </button>
                </form>

                <!-- Hộp hiển thị gợi ý -->
                <div id="suggestionBox" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); z-index: 1000; overflow: hidden; margin-top: 5px; border: 1px solid #eee;">
                </div>
            </div>
        </div>

        <h3 class="section-title">Các hạng phòng tại 09 Homestay</h3>

        <div class="room-grid">
            <?php
            // 1. CẤU HÌNH KẾT NỐI DATABASE
            $servername = "localhost";
            $username = "root";
            $password = ""; 
            $dbname = "09homestay_db";

            $conn = new mysqli($servername, $username, $password, $dbname);
            mysqli_set_charset($conn, 'utf8mb4');

            if ($conn->connect_error) {
                die("Kết nối Database thất bại: " . $conn->connect_error);
            }

            // 2. XỬ LÝ LỌC PHÒNG NẾU KHÁCH CÓ TÌM KIẾM
            $keyword = "";
            if (isset($_GET['keyword']) && !empty($_GET['keyword'])) {
                $keyword = $conn->real_escape_string($_GET['keyword']);
                $sql = "SELECT * FROM rooms WHERE name LIKE '%$keyword%' OR features LIKE '%$keyword%'";
                
                // Hiển thị thông báo đang lọc theo từ khóa
                echo '<div style="text-align:center; margin-bottom: 20px; width: 100%; grid-column: 1/-1; color: #555;">
                        Kết quả tìm kiếm cho: <strong>"'.htmlspecialchars($_GET['keyword']).'"</strong> 
                        <a href="index.php" style="color: #e12d2d; text-decoration: none; margin-left: 10px; font-weight: bold;"><i class="fas fa-times"></i> Xóa tìm kiếm</a>
                      </div>';
            } else {
                $sql = "SELECT * FROM rooms";
            }

            $result = $conn->query($sql);

            // 3. IN DỮ LIỆU PHÒNG RA GIAO DIỆN
            if ($result->num_rows > 0) {
                while($room = $result->fetch_assoc()) {
                    $priceVND = number_format($room["price"], 0, ',', '.') . ' ₫';
                    
                    echo '
                    <div class="room-card">
                        <img src="'.$room["img"].'" alt="'.$room["name"].'">
                        
                        <div class="room-info">
                            <div class="room-name">'.$room["name"].'</div>
                            <div class="room-features"><i class="fas fa-check-circle"></i> '.$room["features"].'</div>
                            <div class="room-capacity"><i class="fas fa-users"></i> Phù hợp cho: '.$room["capacity"].'</div>
                            
                            <div class="price-container">
                                <div class="room-price" data-baseprice="'.$room["price"].'">'.$priceVND.'</div>
                                <div class="price-label" style="font-size: 13px; color: #666; margin-bottom: 10px;">Giá 1 đêm</div>
                                
                                <a href="detail.php?id='.$room["id"].'" class="btn-primary btn-book" style="display:block; text-align:center; text-decoration:none;">Xem & Đặt phòng</a>
                            </div>
                        </div>
                    </div>';
                }
            } else {
                // Hiển thị khi không tìm thấy phòng
                echo '<div style="text-align:center; padding: 50px 20px; width: 100%; grid-column: 1 / -1;">
                        <i class="fas fa-search-minus" style="font-size: 60px; color: #ddd; margin-bottom: 20px;"></i>
                        <h3 style="color: #666;">Rất tiếc, không tìm thấy phòng nào phù hợp!</h3>
                        <a href="index.php" style="display: inline-block; margin-top: 20px; padding: 10px 25px; background: #0071c2; color: white; text-decoration: none; border-radius: 30px;">Xem tất cả phòng</a>
                      </div>';
            }
            $conn->close();
            ?>
        </div>
    </main>

    <!-- Gọi Footer -->
    <?php include 'footer.php'; ?>

    <!-- Popup Đăng nhập -->
    <div class="modal" id="loginModal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('loginModal')">&times;</span>
            <h2>Đăng nhập</h2>
            <input type="email" id="loginEmail" placeholder="Email của bạn">
            <input type="password" id="loginPassword" placeholder="Mật khẩu">
            <button class="btn-primary full-width" onclick="login()">Đăng nhập</button>
            <p class="switch-modal">Chưa có tài khoản? <span onclick="switchModal('loginModal', 'registerModal')">Đăng ký ngay</span></p>
        </div>
    </div>

    <!-- Popup Đăng ký -->
    <div class="modal" id="registerModal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('registerModal')">&times;</span>
            <h2>Tạo tài khoản</h2>
            <input type="text" id="regName" placeholder="Họ và tên">
            <input type="email" id="regEmail" placeholder="Email">
            <input type="password" id="regPassword" placeholder="Mật khẩu">
            <button class="btn-primary full-width" onclick="register()">Đăng ký</button>
            <p class="switch-modal">Đã có tài khoản? <span onclick="switchModal('registerModal', 'loginModal')">Đăng nhập</span></p>
        </div>
    </div>

    <script src="script.js"></script>

    <!-- KỊCH BẢN ĐIỀU KHIỂN GỢI Ý TÌM KIẾM (AJAX) -->
    <script>
        function showSuggestions(str) {
            if (str.length == 0) {
                document.getElementById("suggestionBox").innerHTML = "";
                document.getElementById("suggestionBox").style.display = "none";
                return;
            }
            
            const xhttp = new XMLHttpRequest();
            xhttp.onload = function() {
                document.getElementById("suggestionBox").innerHTML = this.responseText;
                document.getElementById("suggestionBox").style.display = "block";
            }
            xhttp.open("GET", "suggest.php?q=" + str, true);
            xhttp.send();
        }

        document.addEventListener('click', function(event) {
            const searchInput = document.getElementById('searchInput');
            const suggestionBox = document.getElementById('suggestionBox');
            if (event.target !== searchInput && !suggestionBox.contains(event.target)) {
                suggestionBox.style.display = 'none';
            }
        });
        
        document.getElementById('searchInput').addEventListener('click', function() {
            if(this.value.length > 0) {
                showSuggestions(this.value);
            }
        });
    </script>
</body>
</html>