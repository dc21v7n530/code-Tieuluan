<?php
session_start();
include 'db.php';

// Chặn người chưa đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ==========================================
// XỬ LÝ LỆNH HỦY ĐƠN HÀNG KHI BẤM NÚT "HỦY"
// ==========================================
if (isset($_GET['action']) && $_GET['action'] == 'cancel' && isset($_GET['id'])) {
    $cancel_id = (int)$_GET['id'];
    
    // Xóa đơn có ID tương ứng khỏi CSDL
    $del_sql = "DELETE FROM bookings WHERE id = ? AND user_id = ?";
    $del_stmt = $conn->prepare($del_sql);
    $del_stmt->bind_param("ii", $cancel_id, $user_id);
    
    if ($del_stmt->execute()) {
        // Đổi câu thông báo thành "Đã hủy"
        echo "<script>alert('Đã hủy đơn đặt phòng thành công!'); window.location.href='history.php';</script>";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử đặt phòng | 09 Homestay</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f5f5f5; }
        .history-container { max-width: 900px; margin: 40px auto; padding: 0 20px; }
        .booking-card { background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); display: flex; gap: 20px; border-left: 6px solid #0071c2; position: relative; }
        .booking-img { width: 180px; height: 120px; object-fit: cover; border-radius: 8px; }
        .booking-info { flex: 1; }
        .booking-info h3 { color: #003580; margin-bottom: 10px; font-size: 20px; }
        .badge { background: #e6f7ff; color: #0071c2; padding: 5px 10px; border-radius: 4px; font-size: 13px; font-weight: bold; border: 1px solid #91d5ff; }
        .price-text { font-size: 22px; color: #e12d2d; font-weight: bold; text-align: right; margin-bottom: 10px;}
        
        .btn-action { padding: 8px 15px; border-radius: 5px; font-size: 14px; font-weight: bold; cursor: pointer; text-decoration: none; border: none; display: inline-flex; align-items: center; gap: 5px; transition: 0.2s;}
        
        /* Đổi màu nút Hủy cho nhạt hơn một chút để tránh làm khách hàng sợ */
        .btn-cancel { background: white; color: #dc3545; border: 1px solid #dc3545; }
        .btn-cancel:hover { background: #dc3545; color: white; }
        
        .btn-pay { background: #0071c2; color: white; }
        .btn-pay:hover { background: #003580; }
        .actions-group { display: flex; justify-content: flex-end; gap: 10px; }
    </style>
</head>
<body>

    <!-- Header -->
    <header>
        <div class="logo"><i class="fas fa-home"></i> 09 Homestay</div>
        <div class="user-actions">
            <div style="display: flex; align-items: center; gap: 15px;">
                <span style="font-weight:bold; color: var(--primary-blue);">
                    <i class="fas fa-user-circle"></i> <?php echo $_SESSION['user_name']; ?>
                </span>
                <a href="index.php" class="btn-outline" style="text-decoration:none;">Về Trang Chủ</a>
            </div>
        </div>
    </header>

    <div class="history-container">
        <h2 style="margin-bottom: 25px; color: #333; text-align: center;">Đơn đặt phòng của bạn</h2>

        <?php
        $sql = "SELECT b.*, r.name as room_name, r.img as room_img 
                FROM bookings b 
                JOIN rooms r ON b.room_id = r.id 
                WHERE b.user_id = ? 
                ORDER BY b.id DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $priceVND = number_format($row['total_price'], 0, ',', '.') . ' ₫';
                $inDate = date('d/m/Y', strtotime($row['check_in']));
                $outDate = date('d/m/Y', strtotime($row['check_out']));
                $status = isset($row['status']) ? $row['status'] : 'Đang xử lý';

                echo '
                <div class="booking-card">
                    <img src="'.$row['room_img'].'" alt="Ảnh phòng" class="booking-img">
                    <div class="booking-info">
                        <h3>'.$row['room_name'].'</h3>
                        <div style="margin-bottom: 8px; font-size: 14px;">
                            <span>Mã đơn: <strong>'.$row['order_code'].'</strong></span>
                        </div>
                        <div style="margin-bottom: 8px; font-size: 14px;">
                            <i class="far fa-calendar-alt" style="color:#666;"></i> Từ <strong>'.$inDate.'</strong> đến <strong>'.$outDate.'</strong> ('.$row['total_nights'].' đêm)
                        </div>
                        <div style="margin-bottom: 12px; font-size: 14px;">
                            <i class="far fa-credit-card" style="color:#666;"></i> '.$row['payment_method'].'
                        </div>
                        <span class="badge">Trạng thái: '.$status.'</span>
                    </div>
                    
                    <div style="display: flex; flex-direction: column; justify-content: flex-end; align-items: flex-end; min-width: 180px;">
                        <span style="font-size: 13px; color: #666; margin-bottom: 5px;">Tổng thanh toán</span>
                        <div class="price-text">'.$priceVND.'</div>
                        
                        <div class="actions-group">';
                        
                        // Nút Thanh Toán
                        if ($row['payment_method'] == 'Chuyển khoản') {
                            echo '<button onclick="showQR(\''.$row['order_code'].'\', \''.$priceVND.'\')" class="btn-action btn-pay"><i class="fas fa-qrcode"></i> Thanh toán</button>';
                        }
                        
                        // ĐÃ SỬA THÀNH NÚT "HỦY ĐƠN"
                        echo '<a href="history.php?action=cancel&id='.$row['id'].'" onclick="return confirm(\'Bạn có chắc chắn muốn hủy đơn hàng ('.$row['order_code'].') này không?\');" class="btn-action btn-cancel"><i class="fas fa-times-circle"></i> Hủy đơn</a>';

                echo '  </div>
                    </div>
                </div>';
            }
        } else {
            echo '<div style="text-align:center; padding:50px; background:white; border-radius:12px;">
                    <i class="fas fa-box-open" style="font-size:50px; color:#ccc; margin-bottom:15px;"></i>
                    <h3 style="color:#666;">Bạn chưa có đơn đặt phòng nào!</h3>
                    <a href="index.php" class="btn-primary" style="text-decoration:none; display:inline-block; margin-top:15px; padding: 10px 20px;">Đi đặt phòng ngay</a>
                  </div>';
        }
        ?>
    </div>

    <!-- HỘP THOẠI POPUP HIỆN MÃ QR -->
    <div id="qrModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6);">
        <div style="background-color: white; margin: 10% auto; padding: 30px; border-radius: 12px; width: 90%; max-width: 400px; position: relative; text-align: center; box-shadow: 0 5px 20px rgba(0,0,0,0.2);">
            <span onclick="document.getElementById('qrModal').style.display='none'" style="position: absolute; right: 20px; top: 15px; font-size: 28px; font-weight: bold; cursor: pointer; color: #888;">&times;</span>
            <h2 style="color: #003580; margin-bottom: 20px;">Quét mã thanh toán</h2>
            
            <img src="https://upload.wikimedia.org/wikipedia/commons/d/d0/QR_code_for_mobile_English_Wikipedia.svg" alt="Mã QR" style="width: 200px; height: 200px; margin-bottom: 15px; border: 1px solid #eee; padding: 10px; border-radius: 10px;">
            
            <div style="background: #f0f8ff; padding: 15px; border-radius: 8px; text-align: left;">
                <p style="margin-bottom: 8px; font-size: 15px;">Ngân hàng: <strong>Vietcombank</strong></p>
                <p style="margin-bottom: 8px; font-size: 15px;">Chủ TK: <strong>09 HOMESTAY</strong></p>
                <p style="margin-bottom: 8px; font-size: 15px;">Số TK: <strong>0123456789</strong></p>
                <hr style="border: 0; border-top: 1px dashed #ccc; margin: 10px 0;">
                <p style="margin-bottom: 8px; font-size: 15px;">Nội dung CK: <strong style="color: #e12d2d;" id="qrOrderCode"></strong></p>
                <p style="font-size: 16px;">Số tiền: <strong style="color: #e12d2d; font-size: 18px;" id="qrAmount"></strong></p>
            </div>
            <button onclick="document.getElementById('qrModal').style.display='none'" class="btn-primary" style="margin-top: 20px; width: 100%; padding: 12px; font-size: 16px; border:none;">Đã thanh toán</button>
        </div>
    </div>

    <script>
        function showQR(orderCode, amount) {
            document.getElementById('qrOrderCode').innerText = "Thanh toan " + orderCode;
            document.getElementById('qrAmount').innerText = amount;
            document.getElementById('qrModal').style.display = 'block';
        }
    </script>
</body>
</html>