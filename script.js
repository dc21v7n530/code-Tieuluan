// ==========================================
// 1. QUẢN LÝ TÀI KHOẢN (ĐĂNG KÝ / ĐĂNG NHẬP)
// ==========================================

function openModal(id) { document.getElementById(id).style.display = "block"; }
function closeModal(id) { document.getElementById(id).style.display = "none"; }
function switchModal(closeId, openId) { closeModal(closeId); openModal(openId); }

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) event.target.style.display = "none";
}

function register() {
    const name = document.getElementById('regName').value.trim();
    const email = document.getElementById('regEmail').value.trim();
    const password = document.getElementById('regPassword').value;

    if (!name || !email || !password) return alert("Vui lòng nhập đủ thông tin!");

    let formData = new URLSearchParams();
    formData.append('action', 'register');
    formData.append('regName', name);
    formData.append('regEmail', email);
    formData.append('regPassword', password);

    fetch('auth.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        if(data.status === 'success') switchModal('registerModal', 'loginModal');
    })
    .catch(error => console.error('Lỗi kết nối:', error));
}

function login() {
    const email = document.getElementById('loginEmail').value.trim();
    const password = document.getElementById('loginPassword').value;

    if (!email || !password) return alert("Vui lòng nhập email và mật khẩu!");

    let formData = new URLSearchParams();
    formData.append('action', 'login');
    formData.append('loginEmail', email);
    formData.append('loginPassword', password);

    fetch('auth.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if(data.status === 'success') {
            alert("Đăng nhập thành công!");
            window.location.reload(); 
        } else {
            alert(data.message);
        }
    })
    .catch(error => console.error('Lỗi kết nối:', error));
}

// ==========================================
// 2. XỬ LÝ NGÀY THÁNG & TÌNH TRẠNG (TRANG CHỦ)
// ==========================================
const checkInInput = document.getElementById('checkIn');
const checkOutInput = document.getElementById('checkOut');

if (checkInInput && checkOutInput) {
    const today = new Date().toISOString().split('T')[0];
    checkInInput.setAttribute('min', today);

    checkInInput.addEventListener('change', function() {
        const minCheckOut = new Date(this.value);
        minCheckOut.setDate(minCheckOut.getDate() + 1);
        checkOutInput.setAttribute('min', minCheckOut.toISOString().split('T')[0]);
        if(checkOutInput.value && checkOutInput.value <= this.value) {
            checkOutInput.value = "";
        }
    });
}

// ==========================================
// 3. HÀM KIỂM TRA GIÁ VÀ CẬP NHẬT GIAO DIỆN LIVE
// ==========================================
function checkAvailability() {
    if (!checkInInput || !checkOutInput) return;

    if (!checkInInput.value || !checkOutInput.value) {
        alert("Vui lòng chọn ngày nhận và trả phòng!");
        return;
    }

    const inDate = new Date(checkInInput.value);
    const outDate = new Date(checkOutInput.value);

    if (outDate <= inDate) {
        alert("Ngày trả phòng phải sau ngày nhận phòng!");
        checkOutInput.value = ""; 
        return;
    }

    const currentNights = Math.ceil(Math.abs(outDate - inDate) / (1000 * 60 * 60 * 24)); 

    const msgBox = document.getElementById('searchResultMsg');
    if(msgBox) {
        msgBox.style.display = 'block';
        msgBox.innerHTML = `<i class="fas fa-check-circle"></i> Đã cập nhật giá phòng cho kỳ nghỉ <b>${currentNights} đêm</b> của bạn.`;
    }

    const priceContainers = document.querySelectorAll('.price-container');
    if (priceContainers.length === 0) return;

    priceContainers.forEach(container => {
        const priceEl = container.querySelector('.room-price');
        const labelEl = container.querySelector('.price-label');
        const bookBtn = container.querySelector('.btn-book');

        const basePrice = parseInt(priceEl.getAttribute('data-baseprice'));
        
        if(basePrice) {
            const totalPrice = basePrice * currentNights;
            priceEl.innerText = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(totalPrice);
            if (labelEl) labelEl.innerText = `Tổng giá cho ${currentNights} đêm`;
        }

        if(bookBtn) {
            let url = new URL(bookBtn.href, window.location.origin);
            url.searchParams.set('in', checkInInput.value);
            url.searchParams.set('out', checkOutInput.value);
            url.searchParams.set('nights', currentNights);
            bookBtn.href = url.toString(); 
        }
    });

    const roomGrid = document.querySelector('.room-grid');
    if (roomGrid) roomGrid.scrollIntoView({ behavior: 'smooth' });
}

