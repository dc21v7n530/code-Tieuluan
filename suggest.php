<?php
include 'db.php'; // Kết nối Database

if (isset($_GET['q'])) {
    $q = $conn->real_escape_string($_GET['q']);
    
    // Nếu khung tìm kiếm trống thì không làm gì cả
    if (empty($q)) {
        exit();
    }

    // Tìm tối đa 5 phòng khớp tên hoặc tiện ích
    $sql = "SELECT id, name, img, price FROM rooms WHERE name LIKE '%$q%' OR features LIKE '%$q%' LIMIT 5";
    $result = $conn->query($sql);

    // Thêm chút CSS cho hiệu ứng di chuột (hover)
    echo '<style>
            .suggest-item { display: flex; align-items: center; padding: 12px 15px; border-bottom: 1px solid #eee; text-decoration: none; color: #333; transition: 0.2s; background: #fff; }
            .suggest-item:hover { background-color: #f0f8ff; padding-left: 20px; }
          </style>';

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $price = number_format($row['price'], 0, ',', '.') . ' ₫';
            
            // In ra từng thẻ gợi ý (Click vào là qua trang detail.php luôn)
            echo '<a href="detail.php?id='.$row['id'].'" class="suggest-item">';
            echo '  <img src="'.$row['img'].'" alt="ảnh" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px; margin-right: 15px;">';
            echo '  <div>';
            echo '      <div style="font-weight: bold; color: #003580; font-size: 15px; margin-bottom: 3px;">'.$row['name'].'</div>';
            echo '      <div style="color: #e12d2d; font-size: 13px; font-weight: bold;">'.$price.' <span style="color:#999; font-weight:normal; font-size:12px;">/ đêm</span></div>';
            echo '  </div>';
            echo '</a>';
        }
    } else {
        // Nếu gõ bậy bạ không ra phòng nào
        echo '<div style="padding: 15px; text-align: center; color: #888; font-size: 14px;">
                <i class="fas fa-search-minus"></i> Không tìm thấy phòng gợi ý.
              </div>';
    }
    
    $conn->close();
}
?>