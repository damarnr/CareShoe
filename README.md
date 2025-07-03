# CareShoe Website

Website lengkap untuk layanan cuci sepatu dengan fitur pemesanan online, tracking pesanan, dan admin panel.

## Fitur Utama

### Frontend (Customer)
- **Halaman Beranda**: Banner utama, overview layanan, testimoni, galeri before-after
- **Halaman Layanan**: Detail lengkap semua layanan dengan harga dan estimasi waktu
- **Formulir Pemesanan**: Form booking dengan upload foto sepatu dan pilihan metode pengantaran
- **Konfirmasi Pembayaran**: Pilihan metode pembayaran (Transfer Bank, QRIS, COD)
- **Tracking Pesanan**: Lacak status pesanan berdasarkan ID atau nomor HP

### Backend (Admin)
- **Dashboard**: Statistik pesanan, pendapatan, dan status overview
- **Manajemen Pesanan**: Lihat, filter, dan update status pesanan
- **Sistem Login**: Autentikasi admin yang aman
- **Laporan**: Export data pesanan

### Database
- **MySQL**: Database dengan tabel services, orders, dan admin_users
- **API Endpoints**: RESTful API untuk semua operasi

## Teknologi yang Digunakan

- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Styling**: CSS Grid, Flexbox, Responsive Design
- **Color Scheme**: Hitam dan Putih (sesuai permintaan)

## Struktur Direktori

```
shoe_laundry_website/
├── frontend/
│   ├── index.html          # Halaman beranda
│   ├── services.html       # Halaman layanan
│   ├── booking.html        # Formulir pemesanan
│   ├── confirmation.html   # Konfirmasi pembayaran
│   ├── tracking.html       # Tracking pesanan
│   ├── style.css          # CSS utama
│   └── script.js          # JavaScript utama
├── backend/
│   ├── config.php         # Konfigurasi database
│   ├── process_booking.php # Proses pemesanan
│   ├── process_payment.php # Proses pembayaran
│   ├── track_order.php    # API tracking
│   ├── get_services.php   # API layanan
│   └── admin/
│       ├── login.php      # Login admin
│       ├── dashboard.php  # Dashboard admin
│       ├── orders.php     # Manajemen pesanan
│       ├── admin_style.css # CSS admin
│       └── logout.php     # Logout admin
├── database/
│   ├── schema.sql         # Skema database
│   └── install.php        # Installer database
└── uploads/               # Folder upload foto
```

## Instalasi

### 1. Persiapan Server
Pastikan server memiliki:
- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi
- Apache/Nginx web server
- Extension PHP: PDO, PDO_MySQL, GD

### 2. Setup Database
1. Jalankan installer database:
   ```bash
   cd database/
   php install.php
   ```
   
2. Atau import manual:
   ```sql
   CREATE DATABASE shoe_laundry_db;
   USE shoe_laundry_db;
   SOURCE schema.sql;
   ```

### 3. Konfigurasi
1. Edit `backend/config.php` sesuai dengan setting database Anda:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USERNAME', 'your_username');
   define('DB_PASSWORD', 'your_password');
   define('DB_NAME', 'shoe_laundry_db');
   ```

2. Pastikan folder `uploads/` memiliki permission write (755 atau 777)

### 4. Akses Website
- **Frontend**: `http://your-domain.com/frontend/`
- **Admin Panel**: `http://your-domain.com/backend/admin/`

## Kredensial Default Admin

- **Username**: admin
- **Password**: admin123

⚠️ **Penting**: Ganti password default setelah instalasi!

## Layanan yang Tersedia

1. **Deep Clean** - Rp 50.000 (2-3 hari)
2. **Fast Clean** - Rp 30.000 (1 hari)
3. **Unyellowing** - Rp 75.000 (3-5 hari)
4. **Repaint** - Rp 100.000 (5-7 hari)
5. **Leather Care** - Rp 60.000 (2-3 hari)

## Alur Pemesanan

1. **Customer** mengisi formulir pemesanan
2. **System** generate ID pesanan dan redirect ke konfirmasi
3. **Customer** pilih metode pembayaran
4. **Admin** update status pesanan melalui admin panel
5. **Customer** dapat tracking pesanan kapan saja

## Status Pesanan

- **Diterima**: Pesanan baru masuk
- **Dijemput**: Sepatu sudah dijemput
- **Sedang Dicuci**: Dalam proses pembersihan
- **Selesai**: Pembersihan selesai
- **Dalam Pengiriman**: Sedang diantar
- **Selesai Diantar**: Pesanan selesai

## API Endpoints

### Customer API
- `GET /backend/get_services.php` - Daftar layanan
- `POST /backend/process_booking.php` - Buat pesanan baru
- `POST /backend/process_payment.php` - Proses pembayaran
- `GET /backend/track_order.php` - Tracking pesanan

### Admin API
- Login melalui session-based authentication
- CRUD operations untuk pesanan dan layanan

## Fitur Keamanan

- Input sanitization dan validation
- SQL injection protection dengan prepared statements
- XSS protection dengan htmlspecialchars
- File upload validation (type, size)
- Admin session management

## Responsive Design

Website fully responsive dan mobile-friendly:
- Mobile-first approach
- Touch-friendly interface
- Optimized untuk semua ukuran layar

## Browser Support

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+

## Troubleshooting

### Database Connection Error
- Periksa kredensial database di `config.php`
- Pastikan MySQL service berjalan
- Periksa firewall dan port 3306

### File Upload Error
- Periksa permission folder `uploads/`
- Periksa setting `upload_max_filesize` di php.ini
- Pastikan extension GD terinstall

### Admin Login Error
- Pastikan tabel `admin_users` terisi
- Reset password dengan query manual jika perlu

## Customization

### Menambah Layanan Baru
1. Insert ke tabel `services`
2. Update dropdown di `booking.html`

### Mengubah Warna Theme
Edit variabel CSS di `style.css` dan `admin_style.css`

### Menambah Status Pesanan
1. Update enum di database
2. Update array status di PHP files
3. Update CSS untuk status badge baru

## Support

Untuk pertanyaan teknis atau bug report, silakan hubungi developer.

## License

Copyright © 2024 CareShoe. All rights reserved.

