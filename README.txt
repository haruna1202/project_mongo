# 🕊️ Vô Ưu Quán – Website Bán Hàng PHP + MongoDB

Website thương mại điện tử chuyên bán vật phẩm phong thủy & Phật giáo.  
Dự án gồm **2 giao diện**:

- **Người dùng**: xem sản phẩm, xem chi tiết, thêm giỏ hàng, thanh toán.
- **Admin**: quản lý sản phẩm, xem tồn kho, biểu đồ doanh số, quản lý khách hàng.

Dự án được phát triển bằng PHP thuần kết hợp cơ sở dữ liệu **MongoDB**.
# 🛠️ 2. Yêu cầu hệ thống

Để chạy project hoàn chỉnh, bạn cần chuẩn bị:

## ✔ PHP & Web Server
| Thành phần | Phiên bản khuyến nghị |
|-----------|------------------------|
| **PHP**   | 8.1 – 8.2 |
| **XAMPP** | 8.2.x |
| Mở module | `openssl`, `mongodb`, `json`, `session` |

---

## ✔ MongoDB
- **MongoDB Community Server** 6.x hoặc 7.x  
- **MongoDB Compass** (khuyến nghị)
- **PHP MongoDB Driver**
  - File: `php_mongodb.dll`
  - Đã cài vào: `xampp/php/ext/`
  - Thêm dòng sau vào `php.ini`:
    ```
    extension=php_mongodb.dll
    ```

---

## ✔ Composer (nếu cần)
Dự án có file `composer.json`, nên cài Composer để đảm bảo các package PHP hoạt động đúng.

---

## ✔ Kiến thức cơ bản
- PHP procedural
- MongoDB CRUD cơ bản
- HTML/CSS (Quicksand + FontAwesome)
- ChartJS (cho dashboard admin)

---

# 🏗️ 3. Hướng dẫn cài đặt & chạy dự án

## **Bước 1 — Clone hoặc copy project vào XAMPP**
### Cách 1: Clone GitHub  
```bash
git clone https://github.com/<username>/project-mongo.git
cd project-mongo

🔐 4. Tài khoản mẫu
user
Email: vana@example.com
Password: 123456
admin
Email: admin@vouuquan.local
Password: admin123
