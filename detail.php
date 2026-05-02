<?php
session_start();
include 'db.php'; 

// 1. KIỂM TRA ID PHÒNG TỪ URL
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}
$room_id = (int)$_GET['id'];

// LẤY THÔNG TIN PHÒNG TỪ CSDL
$stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Phòng này không tồn tại!");
}
$room = $result->fetch_assoc();

// Lấy ngày truyền từ trang chủ sang (nếu có), nếu không có thì để trống
$checkIn_default = isset($_GET['in']) ? $_GET['in'] : '';
$checkOut_default = isset($_GET['out']) ? $_GET['out'] : '';

// ---------------------------------------------------------
// XỬ LÝ LƯU ĐƠN HÀNG KHI BẤM "XÁC NHẬN ĐẶT PHÒNG"
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo "<script>alert('Vui lòng đăng nhập để đặt phòng!'); window.location.href='index.php';</script>";
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $phone = $_POST['phone'];
    $payment_method = $_POST['payment_method'];
    
    // Nhận ngày tháng khách vừa chọn từ Form
    $in_date = $_POST['check_in'];
    $out_date = $_POST['check_out'];
    
    // Tính toán số đêm bằng PHP để đảm bảo chính xác tuyệt đối
    $date1 = new DateTime($in_date);
    $date2 = new DateTime($out_date);
    $interval = $date1->diff($date2);
    $nights = $interval->days;

    if ($nights <= 0) {
         echo "<script>alert('Ngày trả phòng phải sau ngày nhận phòng!');</script>";
    } else {
        $totalPrice = $room['price'] * $nights;
        $order_code = "HD" . rand(1000, 9999);

        // Câu lệnh lưu vào bảng bookings
        $insert_sql = "INSERT INTO bookings (order_code, user_id, room_id, customer_phone, check_in, check_out, total_nights, total_price, payment_method) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_book = $conn->prepare($insert_sql);
        $stmt_book->bind_param("siisssiis", $order_code, $user_id, $room_id, $phone, $in_date, $out_date, $nights, $totalPrice, $payment_method);
        
        if ($stmt_book->execute()) {
            // Chuyển hướng thẳng sang history.php
            echo "<script>
                alert('🎉 ĐẶT PHÒNG THÀNH CÔNG! Mã đơn: $order_code.\\nHệ thống sẽ chuyển bạn đến Lịch sử đặt phòng.');
                window.location.href='history.php';
            </script>";
            exit();
        } else {
            echo "<script>alert('Lỗi: Không thể lưu đơn hàng. Vui lòng thử lại!');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết thanh toán | 09 Homestay</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f5f5f5; }
        .checkout-container { max-width: 1200px; margin: 40px auto; padding: 0 20px; display: flex; gap: 30px; flex-wrap: wrap; }
        .room-summary { flex: 1; min-width: 350px; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .room-summary img { width: 100%; height: 300px; object-fit: cover; }
        .summary-content { padding: 25px; }
        .summary-content h2 { color: #003580; margin-bottom: 15px; font-size: 24px; }
        .booking-dates { background: #f0f8ff; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: space-between; border: 1px solid #cce5ff; align-items: center;}
        .date-box { text-align: center; }
        .date-box span { display: block; font-size: 14px; color: #666; margin-bottom: 5px;}
        .date-input { border: 1px solid #ccc; padding: 8px; border-radius: 4px; font-weight: bold; color: #333; outline: none; width: 140px; font-family: inherit;}
        .date-input:focus { border-color: #0071c2; }
        .detail-row { display: flex; justify-content: space-between; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px dashed #ddd; font-size: 16px; }
        .total-price { font-size: 26px; color: #e12d2d; font-weight: bold; text-align: right; margin-top: 20px; transition: 0.3s; }

        .customer-form { flex: 1; min-width: 350px; background: white; border-radius: 12px; padding: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .customer-form h3 { margin-bottom: 20px; color: #333; border-bottom: 3px solid #fecb2e; display: inline-block; padding-bottom: 5px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; color: #555; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 15px; outline: none; }
        .btn-confirm { background: #0071c2; color: white; width: 100%; padding: 15px; border: none; border-radius: 6px; font-size: 18px; font-weight: bold; cursor: pointer; transition: 0.3s; margin-top: 10px;}
        .btn-confirm:hover { background: #003580; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #0071c2; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

    <header>
        <div class="logo"><i class="fas fa-home"></i> 09 Homestay</div>
        <div class="user-actions">
            <?php if(isset($_SESSION['user_name'])): ?>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <span style="font-weight:bold; color: var(--primary-blue);">
                        <i class="fas fa-user-circle"></i> <?php echo $_SESSION['user_name']; ?>
                    </span>
                    <a href="auth.php?action=logout" class="btn-outline" style="text-decoration:none;">Đăng xuất</a>
                </div>
            <?php else: ?>
                <span style="color: #e12d2d; font-weight: bold;">Bạn chưa đăng nhập!</span>
            <?php endif; ?>
        </div>
    </header>

    <div style="max-width: 1200px; margin: 20px auto 0; padding: 0 20px;">
        <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> Quay lại trang chủ</a>
    </div>

    <!-- FORM CHÍNH: Bao bọc cả 2 cột để gửi toàn bộ dữ liệu đi -->
    <form method="POST" action="" class="checkout-container" id="bookingForm">
        
        <!-- Cột Trái: Đơn hàng & Lịch -->
        <div class="room-summary">
            <img src="<?php echo $room['img']; ?>" alt="<?php echo $room['name']; ?>">
            <div class="summary-content">
                <h2><?php echo $room['name']; ?></h2>
                
                <div class="booking-dates">
                    <div class="date-box">
                        <span>Nhận phòng</span>
                        <input type="date" class="date-input" name="check_in" id="checkIn" value="<?php echo $checkIn_default; ?>" required>
                    </div>
                    <div><i class="fas fa-arrow-right" style="color: #0071c2;"></i></div>
                    <div class="date-box">
                        <span>Trả phòng</span>
                        <input type="date" class="date-input" name="check_out" id="checkOut" value="<?php echo $checkOut_default; ?>" required>
                    </div>
                </div>

                <div class="detail-row"><span>Số lượng khách:</span><strong>Tối đa <?php echo $room['capacity']; ?></strong></div>
                <div class="detail-row"><span>Số đêm lưu trú:</span><strong id="displayNights">0 đêm</strong></div>
                <div style="text-align: right; margin-top: 20px; font-size: 18px; color: #666;">Tổng thanh toán:</div>
                
                <div class="total-price" id="displayTotal">0 ₫</div>
            </div>
        </div>

        <!-- Cột Phải: Form thông tin người đặt -->
        <div class="customer-form">
            <h3>Thông tin người đặt</h3>
            <div class="form-group">
                <label>Họ và Tên</label>
                <input type="text" value="<?php echo isset($_SESSION['user_name']) ? $_SESSION['user_name'] : ''; ?>" readonly style="background: #eee;">
            </div>
            <div class="form-group">
                <label>Số điện thoại *</label>
                <input type="tel" name="phone" placeholder="Nhập số điện thoại để liên hệ" required>
            </div>
            
            <h3 style="margin-top: 10px;">Thanh toán</h3>
            <div class="form-group">
                <label>Chọn phương thức</label>
                <select name="payment_method" id="payMethod" onchange="togglePayment()">
                    <option value="Thanh toán tại chỗ (Tiền mặt)">Thanh toán tại chỗ (Tiền mặt)</option>
                    <option value="Chuyển khoản">Chuyển khoản ngân hàng</option>
                </select>
            </div>

            <!-- Khu vực hiện mã QR (Mặc định ẩn) -->
            <div id="qrCodeArea" style="display: none; text-align: center; background: #f0f8ff; padding: 15px; border-radius: 8px; border: 1px dashed #0071c2; margin-bottom: 20px;">
                <p style="font-weight: bold; color: #003580; margin-bottom: 10px;">Quét mã QR để thanh toán</p>
                <img src="https://upload.wikimedia.org/wikipedia/commons/d/d0/QR_code_for_mobile_English_Wikipedia.svg" alt="Mã QR" style="width: 150px; height: 150px; margin-bottom: 10px;">
                <p style="font-size: 14px; color: #333;">Ngân hàng: <strong>Vietcombank</strong><br>STK: <strong>0123456789</strong><br>Chủ TK: <strong>09 HOMESTAY</strong><br>Nội dung: <span style="color:#e12d2d; font-weight:bold;">Tên + SĐT</span></p>
            </div>

            <!-- NÚT XÁC NHẬN ĐẶT PHÒNG SẼ XUẤT HIỆN Ở ĐÂY NẾU ĐÃ ĐĂNG NHẬP -->
            <?php if(isset($_SESSION['user_name'])): ?>
                <button type="submit" class="btn-confirm">XÁC NHẬN ĐẶT PHÒNG</button>
            <?php else: ?>
                <div style="color: #e12d2d; font-weight: bold; text-align: center; padding: 15px; border: 1px dashed red;">
                    Vui lòng quay lại Trang Chủ để Đăng nhập trước khi chốt đơn!
                </div>
            <?php endif; ?>
        </div>

    </form>

    <!-- SCRIPT XỬ LÝ TÍNH TIỀN TỰ ĐỘNG BẰNG JAVASCRIPT -->
    <script>
        const basePrice = <?php echo $room['price']; ?>; // Lấy giá gốc từ PHP
        const checkInInput = document.getElementById('checkIn');
        const checkOutInput = document.getElementById('checkOut');
        const displayNights = document.getElementById('displayNights');
        const displayTotal = document.getElementById('displayTotal');

        // Không cho chọn ngày trong quá khứ
        const today = new Date().toISOString().split('T')[0];
        checkInInput.setAttribute('min', today);

        function updatePrice() {
            // Ràng buộc checkOut phải lớn hơn checkIn
            if(checkInInput.value) {
                let minOut = new Date(checkInInput.value);
                minOut.setDate(minOut.getDate() + 1);
                checkOutInput.setAttribute('min', minOut.toISOString().split('T')[0]);
            }

            if(!checkInInput.value || !checkOutInput.value) {
                displayTotal.innerText = "0 ₫";
                displayNights.innerText = "0 đêm";
                return;
            }

            // Lấy ngày đã có trong input (có thể do PHP điền sẵn từ URL)
            const inVal = checkInInput.value;
            const outVal = checkOutInput.value;

            if(!inVal || !outVal) {
                displayTotal.innerText = "0 ₫";
                displayNights.innerText = "0 đêm";
                return;
            }

            const inDate = new Date(inVal);
            const outDate = new Date(outVal);

            // Nếu ngày trả nhỏ hơn hoặc bằng ngày nhận -> Xóa ngày trả
            if(outDate <= inDate) {
                checkOutInput.value = "";
                displayTotal.innerText = "0 ₫";
                displayNights.innerText = "0 đêm";
                return;
            }

            // Tính số đêm và tính tiền
            const diffTime = Math.abs(outDate - inDate);
            const nights = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            displayNights.innerText = nights + " đêm";
            
            const total = basePrice * nights;
            displayTotal.innerText = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(total);
        }

        // Hàm hiện/ẩn QR code
        function togglePayment() {
            const method = document.getElementById('payMethod').value;
            document.getElementById('qrCodeArea').style.display = (method === "Chuyển khoản") ? "block" : "none";
        }
        
        // Cập nhật giá ban đầu
        updatePrice();

        // Lắng nghe sự kiện khi người dùng click chọn/đổi ngày
        checkInInput.addEventListener('change', updatePrice);
        checkOutInput.addEventListener('change', updatePrice);
        
        // Chạy ngay 1 lần để tính tiền từ ngày tháng mặc định
        updatePrice();
    </script>

</body>
</html>