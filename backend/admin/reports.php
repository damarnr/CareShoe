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
    
    // Get date range from form
    $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
    $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
    
    // Daily revenue report
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) as order_date, 
               COUNT(*) as total_orders,
               SUM(total_price) as daily_revenue
        FROM orders 
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY order_date DESC
    ");
    $stmt->execute([$startDate, $endDate]);
    $dailyReport = $stmt->fetchAll();
    
    // Service performance report
    $stmt = $pdo->prepare("
        SELECT s.name as service_name,
               COUNT(o.id) as order_count,
               SUM(o.total_price) as total_revenue,
               AVG(o.total_price) as avg_revenue
        FROM services s
        LEFT JOIN orders o ON s.id = o.service_id 
        WHERE o.created_at BETWEEN ? AND ?
        GROUP BY s.id, s.name
        ORDER BY order_count DESC
    ");
    $stmt->execute([$startDate, $endDate]);
    $serviceReport = $stmt->fetchAll();
    
    // Status distribution
    $stmt = $pdo->prepare("
        SELECT order_status, COUNT(*) as count
        FROM orders 
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY order_status
    ");
    $stmt->execute([$startDate, $endDate]);
    $statusReport = $stmt->fetchAll();
    
    // Summary statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_orders,
            SUM(total_price) as total_revenue,
            AVG(total_price) as avg_order_value,
            COUNT(DISTINCT DATE(created_at)) as active_days
        FROM orders 
        WHERE DATE(created_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$startDate, $endDate]);
    $summary = $stmt->fetch();
    
} catch (Exception $e) {
    $error = 'Failed to generate reports: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Admin CareShoe</title>
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
                <li><a href="orders.php">📋 Pesanan</a></li>
                <li><a href="services.php">🧽 Layanan</a></li>
                <li><a href="reports.php" class="active">📈 Laporan</a></li>
                <li><a href="logout.php">🚪 Logout</a></li>
            </ul>
        </nav>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="content-header">
                <h1>Laporan</h1>
                <p>Analisis performa bisnis dan statistik</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <!-- Date Range Filter -->
            <div class="content-section">
                <h2>Filter Periode</h2>
                <form method="GET" class="filter-form">
                    <div style="display: grid; grid-template-columns: 1fr 1fr auto auto; gap: 1rem; align-items: end;">
                        <div class="form-group">
                            <label for="start_date">Tanggal Mulai</label>
                            <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="end_date">Tanggal Akhir</label>
                            <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn">Filter</button>
                        </div>
                        
                        <div class="form-group">
                            <button type="button" onclick="exportReport()" class="btn btn-success">Export CSV</button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Summary Statistics -->
            <div class="content-section">
                <h2>Ringkasan Periode (<?php echo date('d/m/Y', strtotime($startDate)); ?> - <?php echo date('d/m/Y', strtotime($endDate)); ?>)</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📋</div>
                        <div class="stat-info">
                            <h3><?php echo $summary['total_orders']; ?></h3>
                            <p>Total Pesanan</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">💰</div>
                        <div class="stat-info">
                            <h3><?php echo formatPrice($summary['total_revenue'] ?: 0); ?></h3>
                            <p>Total Pendapatan</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">📊</div>
                        <div class="stat-info">
                            <h3><?php echo formatPrice($summary['avg_order_value'] ?: 0); ?></h3>
                            <p>Rata-rata per Pesanan</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">📅</div>
                        <div class="stat-info">
                            <h3><?php echo $summary['active_days']; ?></h3>
                            <p>Hari Aktif</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Daily Revenue Report -->
            <div class="content-section">
                <h2>Laporan Harian</h2>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Jumlah Pesanan</th>
                                <th>Pendapatan</th>
                                <th>Rata-rata per Pesanan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($dailyReport)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 2rem; color: #666;">
                                        Tidak ada data untuk periode ini
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($dailyReport as $day): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($day['order_date'])); ?></td>
                                        <td><?php echo $day['total_orders']; ?></td>
                                        <td><?php echo formatPrice($day['daily_revenue']); ?></td>
                                        <td><?php echo formatPrice($day['daily_revenue'] / $day['total_orders']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Service Performance -->
            <div class="content-section">
                <h2>Performa Layanan</h2>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Layanan</th>
                                <th>Jumlah Pesanan</th>
                                <th>Total Pendapatan</th>
                                <th>Rata-rata per Pesanan</th>
                                <th>Persentase</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $totalOrders = array_sum(array_column($serviceReport, 'order_count'));
                            foreach ($serviceReport as $service): 
                                $percentage = $totalOrders > 0 ? ($service['order_count'] / $totalOrders) * 100 : 0;
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($service['service_name']); ?></td>
                                    <td><?php echo $service['order_count']; ?></td>
                                    <td><?php echo formatPrice($service['total_revenue'] ?: 0); ?></td>
                                    <td><?php echo formatPrice($service['avg_revenue'] ?: 0); ?></td>
                                    <td><?php echo number_format($percentage, 1); ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Status Distribution -->
            <div class="content-section">
                <h2>Distribusi Status</h2>
                <div class="status-grid">
                    <?php foreach ($statusReport as $status): ?>
                        <div class="status-item">
                            <span class="status-count"><?php echo $status['count']; ?></span>
                            <span class="status-label"><?php echo $status['order_status']; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        function exportReport() {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            
            // Create CSV content
            let csv = 'Laporan CareShoe\n';
            csv += 'Periode: ' + startDate + ' sampai ' + endDate + '\n\n';
            
            csv += 'Ringkasan\n';
            csv += 'Total Pesanan,<?php echo $summary['total_orders']; ?>\n';
            csv += 'Total Pendapatan,<?php echo $summary['total_revenue']; ?>\n';
            csv += 'Rata-rata per Pesanan,<?php echo $summary['avg_order_value']; ?>\n\n';
            
            csv += 'Laporan Harian\n';
            csv += 'Tanggal,Jumlah Pesanan,Pendapatan,Rata-rata\n';
            <?php foreach ($dailyReport as $day): ?>
            csv += '<?php echo $day['order_date']; ?>,<?php echo $day['total_orders']; ?>,<?php echo $day['daily_revenue']; ?>,<?php echo $day['daily_revenue'] / $day['total_orders']; ?>\n';
            <?php endforeach; ?>
            
            csv += '\nPerforma Layanan\n';
            csv += 'Layanan,Jumlah Pesanan,Total Pendapatan,Rata-rata\n';
            <?php foreach ($serviceReport as $service): ?>
            csv += '<?php echo addslashes($service['service_name']); ?>,<?php echo $service['order_count']; ?>,<?php echo $service['total_revenue']; ?>,<?php echo $service['avg_revenue']; ?>\n';
            <?php endforeach; ?>
            
            // Download CSV
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', 'laporan_CareShoe_' + startDate + '_' + endDate + '.csv');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
    
    <style>
        .filter-form {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .filter-form > div {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</body>
</html>

