<header>
    <div class="logo">
        <i class="fas fa-home"></i> 09 Homestay
    </div>
   <div class="user-actions">
           <?php if(isset($_SESSION['user_name'])): ?>
                <!-- ĐÃ ĐĂNG NHẬP -->
                <div style="display: flex; align-items: center; gap: 15px;">
                    <!-- Đã đổi lại thành a href="history.php" -->
                    <a href="history.php" class="btn-outline" style="border-color: #fecb2e; color: #003580; text-decoration: none;">
                        <i class="fas fa-list-alt"></i> Lịch sử
                    </a>
                    <span style="font-weight:bold; color: var(--primary-blue);">
                        <i class="fas fa-user-circle" style="font-size: 20px;"></i> <?php echo $_SESSION['user_name']; ?>
                    </span>
                    <a href="auth.php?action=logout" class="btn-outline" style="text-decoration:none;">Đăng xuất</a>
                </div>
            <?php else: ?>
                <!-- CHƯA ĐĂNG NHẬP -->
                <button class="btn-outline" onclick="openModal('loginModal')">Đăng nhập</button>
                <button class="btn-primary" onclick="openModal('registerModal')">Tạo tài khoản</button>
            <?php endif; ?>
        </div>
</header>