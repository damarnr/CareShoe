<?php
session_start();
require_once '../config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$message = '';
$messageType = '';

// Handle service update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_service'])) {
    $serviceId = (int)$_POST['service_id'];
    $name = sanitizeInput($_POST['name']);
    $price = (float)$_POST['price'];
    $estimatedTime = sanitizeInput($_POST['estimated_time']);
    
    try {
        $pdo = getDBConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("UPDATE services SET name = ?, price = ?, estimated_time = ? WHERE id = ?");
            $result = $stmt->execute([$name, $price, $estimatedTime, $serviceId]);
            
            if ($result) {
                $message = "Layanan berhasil diperbarui";
                $messageType = 'success';
            } else {
                $message = "Gagal memperbarui layanan";
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
    
    // Get all services
    $stmt = $pdo->query("SELECT * FROM services ORDER BY id ASC");
    $services = $stmt->fetchAll();
    
    // Get service statistics
    $stmt = $pdo->query("
        SELECT s.name, COUNT(o.id) as order_count, SUM(o.total_price) as total_revenue
        FROM services s
        LEFT JOIN orders o ON s.id = o.service_id
        GROUP BY s.id, s.name
        ORDER BY order_count DESC
    ");
    $serviceStats = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = 'Failed to load services: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Layanan - Admin CareShoe</title>
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
        <a href="dashboard.php">
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
        <a href="services.php" class="active">
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
                <h1>Manajemen Layanan</h1>
                <p>Kelola layanan cuci sepatu dan harga</p>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <!-- Service Statistics -->
            <div class="content-section">
                <h2>Statistik Layanan</h2>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Layanan</th>
                                <th>Jumlah Pesanan</th>
                                <th>Total Pendapatan</th>
                                <th>Rata-rata per Pesanan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($serviceStats as $stat): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($stat['name']); ?></td>
                                    <td><?php echo $stat['order_count']; ?></td>
                                    <td><?php echo formatPrice($stat['total_revenue'] ?: 0); ?></td>
                                    <td>
                                        <?php 
                                        $avg = $stat['order_count'] > 0 ? $stat['total_revenue'] / $stat['order_count'] : 0;
                                        echo formatPrice($avg);
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Services Management -->
            <div class="content-section">
                <h2>Daftar Layanan</h2>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Layanan</th>
                                <th>Harga</th>
                                <th>Estimasi Waktu</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($services as $service): ?>
                                <tr>
                                    <td><?php echo $service['id']; ?></td>
                                    <td><?php echo htmlspecialchars($service['name']); ?></td>
                                    <td><?php echo formatPrice($service['price']); ?></td>
                                    <td><?php echo htmlspecialchars($service['estimated_time']); ?></td>
                                    <td>
                                        <button onclick="editService(<?php echo htmlspecialchars(json_encode($service)); ?>)" 
                                                class="btn btn-sm btn-warning">
                                            Edit
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Edit Service Modal -->
    <div id="serviceModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 10px; min-width: 500px;">
            <h3>Edit Layanan</h3>
            <form method="POST" style="margin-top: 1rem;">
                <input type="hidden" id="modalServiceId" name="service_id">
                
                <div class="form-group">
                    <label for="modalName">Nama Layanan</label>
                    <input type="text" id="modalName" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="modalPrice">Harga (Rp)</label>
                    <input type="number" id="modalPrice" name="price" min="0" step="1000" required>
                </div>
                
                <div class="form-group">
                    <label for="modalTime">Estimasi Waktu</label>
                    <input type="text" id="modalTime" name="estimated_time" placeholder="contoh: 2-3 hari" required>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" onclick="closeServiceModal()" class="btn btn-secondary">Batal</button>
                    <button type="submit" name="update_service" class="btn">Update</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function editService(service) {
            document.getElementById('modalServiceId').value = service.id;
            document.getElementById('modalName').value = service.name;
            document.getElementById('modalPrice').value = service.price;
            document.getElementById('modalTime').value = service.estimated_time;
            document.getElementById('serviceModal').style.display = 'block';
        }
        
        function closeServiceModal() {
            document.getElementById('serviceModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        document.getElementById('serviceModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeServiceModal();
            }
        });
    </script>
</body>
</html>

