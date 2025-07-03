<?php
session_start();
require_once '../config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$orderId = isset($_GET['id']) ? sanitizeInput($_GET['id']) : '';
if (!$orderId) {
    header('Location: orders.php');
    exit;
}

$message = '';
$messageType = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = sanitizeInput($_POST['new_status']);
    
    try {
        $pdo = getDBConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
            $result = $stmt->execute([$newStatus, $orderId]);
            
            if ($result) {
                $message = "Status pesanan berhasil diperbarui";
                $messageType = 'success';
            } else {
                $message = "Gagal memperbarui status pesanan";
                $messageType = 'error';
            }
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = 'error';
    }
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Get order details
    $stmt = $pdo->prepare("
        SELECT o.*, s.name as service_name, s.price as service_price, s.estimated_time
        FROM orders o 
        JOIN services s ON o.service_id = s.id 
        WHERE o.id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    
    if (!$order) {
        header('Location: orders.php');
        exit;
    }
    
} catch (Exception $e) {
    $error = 'Failed to load order details: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan <?php echo htmlspecialchars($orderId); ?> - Admin</title>
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h2>CareShoe</h2>
                <p>Admin Panel</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="orders.php" class="active">📋 Pesanan</a></li>
                <li><a href="services.php">🧽 Layanan</a></li>
                <li><a href="reports.php">📈 Laporan</a></li>
                <li><a href="logout.php">🚪 Logout</a></li>
            </ul>
        </nav>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="content-header">
                <h1>Detail Pesanan</h1>
                <p>ID: <?php echo htmlspecialchars($orderId); ?></p>
                <a href="orders.php" class="btn btn-secondary">← Kembali ke Daftar Pesanan</a>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php else: ?>
            
            <!-- Order Information -->
            <div class="content-section">
                <h2>Informasi Pesanan</h2>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <div>
                        <div class="detail-item">
                            <strong>ID Pesanan:</strong>
                            <span><?php echo htmlspecialchars($order['id']); ?></span>
                        </div>
                        <div class="detail-item">
                            <strong>Nama Pelanggan:</strong>
                            <span><?php echo htmlspecialchars($order['full_name']); ?></span>
                        </div>
                        <div class="detail-item">
                            <strong>WhatsApp:</strong>
                            <span><?php echo htmlspecialchars($order['whatsapp_number']); ?></span>
                        </div>
                        <div class="detail-item">
                            <strong>Jenis Sepatu:</strong>
                            <span><?php echo htmlspecialchars($order['shoe_type'] ?: '-'); ?></span>
                        </div>
                        <div class="detail-item">
                            <strong>Layanan:</strong>
                            <span><?php echo htmlspecialchars($order['service_name']); ?></span>
                        </div>
                        <div class="detail-item">
                            <strong>Estimasi Waktu:</strong>
                            <span><?php echo htmlspecialchars($order['estimated_time']); ?></span>
                        </div>
                    </div>
                    <div>
                        <div class="detail-item">
                            <strong>Metode Pengantaran:</strong>
                            <span><?php echo htmlspecialchars($order['delivery_method']); ?></span>
                        </div>
                        <div class="detail-item">
                            <strong>Alamat:</strong>
                            <span><?php echo htmlspecialchars($order['address'] ?: '-'); ?></span>
                        </div>
                        <div class="detail-item">
                            <strong>Jadwal:</strong>
                            <span><?php echo date('d/m/Y H:i', strtotime($order['pickup_delivery_schedule'])); ?></span>
                        </div>
                        <div class="detail-item">
                            <strong>Total Harga:</strong>
                            <span><?php echo formatPrice($order['total_price']); ?></span>
                        </div>
                        <div class="detail-item">
                            <strong>Metode Pembayaran:</strong>
                            <span><?php echo htmlspecialchars($order['payment_option'] ?: 'Belum dipilih'); ?></span>
                        </div>
                        <div class="detail-item">
                            <strong>Tanggal Pesanan:</strong>
                            <span><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Status Update -->
            <div class="content-section">
                <h2>Update Status</h2>
                <div style="display: flex; align-items: center; gap: 2rem;">
                    <div>
                        <strong>Status Saat Ini:</strong>
                        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $order['order_status'])); ?>">
                            <?php echo htmlspecialchars($order['order_status']); ?>
                        </span>
                    </div>
                    <form method="POST" style="display: flex; gap: 1rem; align-items: end;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="new_status">Status Baru:</label>
                            <select id="new_status" name="new_status" required>
                                <option value="Diterima" <?php echo $order['order_status'] === 'Diterima' ? 'selected' : ''; ?>>Diterima</option>
                                <option value="Dijemput" <?php echo $order['order_status'] === 'Dijemput' ? 'selected' : ''; ?>>Dijemput</option>
                                <option value="Sedang Dicuci" <?php echo $order['order_status'] === 'Sedang Dicuci' ? 'selected' : ''; ?>>Sedang Dicuci</option>
                                <option value="Selesai" <?php echo $order['order_status'] === 'Selesai' ? 'selected' : ''; ?>>Selesai</option>
                                <option value="Dalam Pengiriman" <?php echo $order['order_status'] === 'Dalam Pengiriman' ? 'selected' : ''; ?>>Dalam Pengiriman</option>
                                <option value="Selesai Diantar" <?php echo $order['order_status'] === 'Selesai Diantar' ? 'selected' : ''; ?>>Selesai Diantar</option>
                            </select>
                        </div>
                        <button type="submit" name="update_status" class="btn">Update Status</button>
                    </form>
                </div>
            </div>
            
            <!-- Photo -->
            <?php if ($order['photo_path']): ?>
            <div class="content-section">
                <h2>Foto Sepatu</h2>
                <div style="text-align: center;">
                    <img src="../../uploads/<?php echo htmlspecialchars($order['photo_path']); ?>" 
                         alt="Foto Sepatu" 
                         style="max-width: 400px; max-height: 300px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Contact Customer -->
            <div class="content-section">
                <h2>Kontak Pelanggan</h2>
                <div style="display: flex; gap: 1rem;">
                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $order['whatsapp_number']); ?>" 
                       target="_blank" 
                       class="btn btn-success">
                        💬 Chat WhatsApp
                    </a>
                    <a href="tel:<?php echo htmlspecialchars($order['whatsapp_number']); ?>" 
                       class="btn">
                        📞 Telepon
                    </a>
                </div>
            </div>
            
            <?php endif; ?>
        </main>
    </div>
    
    <style>
        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 1rem;
            margin-bottom: 0.5rem;
            background: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #000;
        }
        
        .detail-item strong {
            color: #000;
            min-width: 150px;
        }
        
        .detail-item span {
            text-align: right;
            flex: 1;
        }
        
        @media (max-width: 768px) {
            .content-section > div[style*="grid"] {
                grid-template-columns: 1fr !important;
            }
            
            .detail-item {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .detail-item span {
                text-align: left;
            }
        }
    </style>
</body>
</html>

