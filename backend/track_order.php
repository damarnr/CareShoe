<?php
require_once 'config.php'; // Pastikan file ini berisi semua fungsi yang dibutuhkan

// Set header untuk semua respons
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Handle CORS preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit;
}

// Fungsi untuk membuat timeline status (bisa juga diletakkan di config.php)
function getStatusTimeline($currentStatus, $createdAt) {
    $statuses = [
        'Diterima' => 'Pesanan Diterima',
        'Dijemput' => 'Sepatu Dijemput',
        'Sedang Dicuci' => 'Sedang Dicuci',
        'Selesai' => 'Selesai',
        'Dalam Pengiriman' => 'Dalam Pengiriman',
        'Selesai Diantar' => 'Selesai Diantar'
    ];
    
    $statusOrder = array_keys($statuses);
    $currentIndex = array_search($currentStatus, $statusOrder);
    
    if ($currentIndex === false) {
        $currentIndex = -1; // Status tidak ditemukan
    }
    
    $timeline = [];
    foreach ($statusOrder as $index => $status) {
        $timeline[] = [
            'status' => $status,
            'label' => $statuses[$status],
            'completed' => $index <= $currentIndex,
            'active' => $index === $currentIndex,
            // Asumsikan timestamp sama dengan tanggal dibuat jika status sudah selesai
            'timestamp' => $index <= $currentIndex ? $createdAt : null 
        ];
    }
    return $timeline;
}


try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('Koneksi database gagal');
    }

    // Ambil parameter pencarian dari URL (GET request)
    $searchType = $_GET['type'] ?? null;
    $searchValue = $_GET['value'] ?? null;

    if (!$searchType || !$searchValue) {
        sendJSONResponse(['success' => false, 'message' => 'Tipe dan nilai pencarian wajib diisi'], 400);
    }
    
    if (!in_array($searchType, ['order_id', 'phone'])) {
        sendJSONResponse(['success' => false, 'message' => 'Tipe pencarian tidak valid'], 400);
    }
    
    // Query dasar untuk mengambil data
    $query = "
        SELECT o.*, s.name as service_name, s.estimated_time
        FROM orders o 
        JOIN services s ON o.service_id = s.id 
        WHERE ";
    
    $params = [];
    
    if ($searchType === 'order_id') {
        $query .= "o.id = ?";
        $params[] = $searchValue;
    } else { // searchType === 'phone'
        $cleanPhone = preg_replace('/[^0-9]/', '', $searchValue);
        $query .= "REPLACE(o.whatsapp_number, '+', '') LIKE ?";
        $params[] = "%{$cleanPhone}%";
    }
    
    $query .= " ORDER BY o.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($orders)) {
        sendJSONResponse(['success' => false, 'message' => 'Pesanan tidak ditemukan dengan informasi tersebut.'], 404);
    }
    
    // Proses setiap pesanan untuk menambahkan data timeline
    $processedOrders = array_map(function($order) {
        $order['status_timeline'] = getStatusTimeline($order['order_status'], $order['created_at']);
        return $order;
    }, $orders);
    
    // Sesuaikan format output berdasarkan tipe pencarian
    if ($searchType === 'order_id') {
        // Jika mencari berdasarkan ID, kembalikan satu objek 'order'
        sendJSONResponse(['success' => true, 'order' => $processedOrders[0]], 200);
    } else {
        // Jika berdasarkan telepon, kembalikan array 'orders'
        sendJSONResponse(['success' => true, 'orders' => $processedOrders], 200);
    }
    
} catch (Exception $e) {
    error_log("Track order error: " . $e->getMessage());
    sendJSONResponse(['success' => false, 'message' => 'Terjadi kesalahan pada server.'], 500);
}
?>
