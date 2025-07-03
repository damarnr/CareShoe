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

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = sanitizeInput($_POST['order_id']);
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
    
    // Get filter parameters
    $statusFilter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
    $searchQuery = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
    
    // Build query
    $query = "
        SELECT o.*, s.name as service_name, s.estimated_time
        FROM orders o 
        JOIN services s ON o.service_id = s.id 
        WHERE 1=1
    ";
    $params = [];
    
    if ($statusFilter) {
        $query .= " AND o.order_status = ?";
        $params[] = $statusFilter;
    }
    
    if ($searchQuery) {
        $query .= " AND (o.id LIKE ? OR o.full_name LIKE ? OR o.whatsapp_number LIKE ?)";
        $params[] = "%{$searchQuery}%";
        $params[] = "%{$searchQuery}%";
        $params[] = "%{$searchQuery}%";
    }
    
    $query .= " ORDER BY o.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
    
    // Get status counts for filter
    $stmt = $pdo->query("SELECT order_status, COUNT(*) as count FROM orders GROUP BY order_status");
    $statusCounts = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = 'Failed to load orders: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pesanan - Admin CareShoe</title>
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
                <h1>Manajemen Pesanan</h1>
                <p>Kelola semua pesanan cuci sepatu</p>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <!-- Filters -->
            <div class="content-section">
                <h2>Filter & Pencarian</h2>
                
                <form method="GET" class="filter-form">
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 1rem; align-items: end;">
                        <div class="form-group">
                            <label for="status">Filter Status</label>
                            <select id="status" name="status">
                                <option value="">Semua Status</option>
                                <option value="Diterima" <?php echo $statusFilter === 'Diterima' ? 'selected' : ''; ?>>Diterima</option>
                                <option value="Dijemput" <?php echo $statusFilter === 'Dijemput' ? 'selected' : ''; ?>>Dijemput</option>
                                <option value="Sedang Dicuci" <?php echo $statusFilter === 'Sedang Dicuci' ? 'selected' : ''; ?>>Sedang Dicuci</option>
                                <option value="Selesai" <?php echo $statusFilter === 'Selesai' ? 'selected' : ''; ?>>Selesai</option>
                                <option value="Dalam Pengiriman" <?php echo $statusFilter === 'Dalam Pengiriman' ? 'selected' : ''; ?>>Dalam Pengiriman</option>
                                <option value="Selesai Diantar" <?php echo $statusFilter === 'Selesai Diantar' ? 'selected' : ''; ?>>Selesai Diantar</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="search">Pencarian</label>
                            <input type="text" id="search" name="search" placeholder="ID, Nama, atau No. HP" value="<?php echo htmlspecialchars($searchQuery); ?>">
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn">Filter</button>
                        </div>
                        
                        <div class="form-group">
                            <a href="orders.php" class="btn btn-secondary">Reset</a>
                        </div>
                    </div>
                </form>
                
                <!-- Status Summary -->
                <div class="status-summary" style="margin-top: 1.5rem;">
                    <h3>Ringkasan Status</h3>
                    <div class="status-grid">
                        <?php foreach ($statusCounts as $status): ?>
                            <div class="status-item">
                                <span class="status-count"><?php echo $status['count']; ?></span>
                                <span class="status-label"><?php echo $status['order_status']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Orders Table -->
            <div class="content-section">
                <h2>Daftar Pesanan (<?php echo count($orders); ?> pesanan)</h2>
                
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID Pesanan</th>
                                <th>Nama</th>
                                <th>WhatsApp</th>
                                <th>Layanan</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 2rem; color: #666;">
                                        Tidak ada pesanan ditemukan
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($order['id']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($order['whatsapp_number']); ?></td>
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
                                            <button onclick="updateStatus('<?php echo $order['id']; ?>', '<?php echo $order['order_status']; ?>')" class="btn btn-sm btn-warning">Update</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Status Update Modal -->
    <div id="statusModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 10px; min-width: 400px;">
            <h3>Update Status Pesanan</h3>
            <form method="POST" style="margin-top: 1rem;">
                <input type="hidden" id="modalOrderId" name="order_id">
                <div class="form-group">
                    <label for="modalStatus">Status Baru</label>
                    <select id="modalStatus" name="new_status" required>
                        <option value="Diterima">Diterima</option>
                        <option value="Dijemput">Dijemput</option>
                        <option value="Sedang Dicuci">Sedang Dicuci</option>
                        <option value="Selesai">Selesai</option>
                        <option value="Dalam Pengiriman">Dalam Pengiriman</option>
                        <option value="Selesai Diantar">Selesai Diantar</option>
                    </select>
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">Batal</button>
                    <button type="submit" name="update_status" class="btn">Update</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function updateStatus(orderId, currentStatus) {
            document.getElementById('modalOrderId').value = orderId;
            document.getElementById('modalStatus').value = currentStatus;
            document.getElementById('statusModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('statusModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        document.getElementById('statusModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
    
    <style>
        .filter-form {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .btn-secondary {
            background-color: #6c757d;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .status-summary h3 {
            margin-bottom: 1rem;
            color: #000;
        }
        
        @media (max-width: 768px) {
            .filter-form > div {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</body>
</html>

