<?php
session_start();
require_once '../config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Get dashboard statistics
    $stats = [];
    
    // Total orders
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders");
    $stats['total_orders'] = $stmt->fetch()['total'];
    
    // Orders by status
    $stmt = $pdo->query("SELECT order_status, COUNT(*) as count FROM orders GROUP BY order_status");
    $statusCounts = $stmt->fetchAll();
    $stats['status_counts'] = [];
    foreach ($statusCounts as $status) {
        $stats['status_counts'][$status['order_status']] = $status['count'];
    }
    
    // Today's orders
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE DATE(created_at) = CURDATE()");
    $stats['today_orders'] = $stmt->fetch()['total'];
    
    // Total revenue
    $stmt = $pdo->query("SELECT SUM(total_price) as total FROM orders WHERE order_status != 'Dibatalkan'");
    $stats['total_revenue'] = $stmt->fetch()['total'] ?: 0;
    
    // Recent orders
    $stmt = $pdo->prepare("
        SELECT o.*, s.name as service_name 
        FROM orders o 
        JOIN services s ON o.service_id = s.id 
        ORDER BY o.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $recentOrders = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = 'Failed to load dashboard: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - CareShoe</title>
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

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
            <li>
                <a href="dashboard.php" class="active">
                    <i class="bi bi-grid-1x2-fill"></i> 
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="orders.php">
                    <i class="bi bi-card-list"></i> 
                    <span>Pesanan</span>
                </a>
            </li>
            <li>
                <a href="services.php">
                    <i class="bi bi-box-seam-fill"></i> 
                    <span>Layanan</span>
                </a>
            </li>
            <li>
                <a href="reports.php">
                    <i class="bi bi-bar-chart-line-fill"></i> 
                    <span>Laporan</span>
                </a>
            </li>
            <li>
                <a href="logout.php">
                    <i class="bi bi-box-arrow-right"></i> 
                    <span>Logout</span>
                </a>
            </li>
            </ul>
        </nav>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="content-header">
                <h1>Dashboard</h1>
                <p>Selamat datang, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📋</div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_orders']; ?></h3>
                        <p>Total Pesanan</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📅</div>
                    <div class="stat-info">
                        <h3><?php echo $stats['today_orders']; ?></h3>
                        <p>Pesanan Hari Ini</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-info">
                        <h3><?php echo formatPrice($stats['total_revenue']); ?></h3>
                        <p>Total Pendapatan</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-info">
                        <h3><?php echo $stats['status_counts']['Sedang Dicuci'] ?? 0; ?></h3>
                        <p>Sedang Diproses</p>
                    </div>
                </div>
            </div>
            
            <!-- Status Overview -->
            <div class="content-section">
                <h2>Status Pesanan</h2>
                <div class="status-grid">
                    <?php
                    $statusLabels = [
                        'Diterima' => 'Pesanan Diterima',
                        'Dijemput' => 'Sepatu Dijemput',
                        'Sedang Dicuci' => 'Sedang Dicuci',
                        'Selesai' => 'Selesai',
                        'Dalam Pengiriman' => 'Dalam Pengiriman',
                        'Selesai Diantar' => 'Selesai Diantar'
                    ];
                    
                    foreach ($statusLabels as $status => $label):
                        $count = $stats['status_counts'][$status] ?? 0;
                    ?>
                        <div class="status-item">
                            <span class="status-count"><?php echo $count; ?></span>
                            <span class="status-label"><?php echo $label; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <div class="content-section">
                <h2>Pesanan Terbaru</h2>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID Pesanan</th>
                                <th>Nama</th>
                                <th>Layanan</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($order['id']); ?></td>
                                    <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($order['service_name']); ?></td>
                                    <td><?php echo formatPrice($order['total_price']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $order['order_status'])); ?>">
                                            <?php echo htmlspecialchars($order['order_status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="btn btn-sm">Detail</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="section-footer">
                    <a href="orders.php" class="btn">Lihat Semua Pesanan</a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

