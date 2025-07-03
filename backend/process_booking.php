<?php
require_once 'config.php';

// Handle CORS preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit;
}
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');


// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSONResponse([
        'success' => false,
        'message' => 'Only POST method is allowed'
    ], 405);
}

try {
    // Get database connection
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // --- (Validasi dan Sanitasi Input Anda Tetap Sama) ---
    // Validate required fields
    $requiredFields = ['full_name', 'whatsapp_number', 'service_id', 'delivery_method', 'pickup_delivery_schedule'];
    $errors = validateRequiredFields($_POST, $requiredFields);
    
    // Additional validation for address if delivery method is pickup
    if (isset($_POST['delivery_method']) && $_POST['delivery_method'] === 'dijemput_kurir') {
        if (!isset($_POST['address']) || empty(trim($_POST['address']))) {
            $errors[] = "Address is required for courier pickup";
        }
    }
    
    if (!empty($errors)) {
        sendJSONResponse([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $errors
        ], 400);
    }
    
    // Sanitize input data
    $fullName = sanitizeInput($_POST['full_name']);
    $whatsappNumber = sanitizeInput($_POST['whatsapp_number']);
    $shoeType = isset($_POST['shoe_type']) ? sanitizeInput($_POST['shoe_type']) : null;
    $serviceId = (int)$_POST['service_id'];
    $deliveryMethod = sanitizeInput($_POST['delivery_method']);
    $address = isset($_POST['address']) ? sanitizeInput($_POST['address']) : null;
    $schedule = sanitizeInput($_POST['pickup_delivery_schedule']);
    
    // Validate service exists and get price
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([$serviceId]);
    $service = $stmt->fetch();
    
    if (!$service) {
        sendJSONResponse(['success' => false, 'message' => 'Invalid service selected'], 400);
    }
    
    // Handle file upload if provided
    $photoPath = null;
    if (isset($_FILES['shoe_photo']) && $_FILES['shoe_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        try {
            $photoPath = handleFileUpload($_FILES['shoe_photo']);
        } catch (Exception $e) {
            sendJSONResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
    
    // --- PENYESUAIAN DIMULAI DI SINI ---
    
    // 1. HAPUS PEMBUAT ID KUSTOM
    // $orderId = generateOrderId(); // Baris ini tidak lagi digunakan

    // 2. UBAH PERINTAH INSERT (KOLOM 'id' DIHAPUS DARI QUERY)
    // Biarkan database yang mengisi ID secara otomatis.
    $stmt = $pdo->prepare("
        INSERT INTO orders (
            full_name, whatsapp_number, shoe_type, service_id, 
            photo_path, delivery_method, address, pickup_delivery_schedule, 
            total_price, order_status, created_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Diterima', NOW()
        )
    ");
    
    // 3. SESUAIKAN DATA YANG DIKIRIM (TANPA ID)
    $result = $stmt->execute([
        $fullName,
        $whatsappNumber,
        $shoeType,
        $serviceId,
        $photoPath,
        $deliveryMethod,
        $address,
        $schedule,
        $service['price']
    ]);
    
    if ($result) {
        // 4. AMBIL ID ANGKA YANG BARU DIBUAT OLEH DATABASE
        $newOrderId = $pdo->lastInsertId();
        
        // 5. KIRIM RESPON MENGGUNAKAN ID BARU
        sendJSONResponse([
            'success' => true,
            'message' => 'Order placed successfully',
            'order_id' => $newOrderId, // Menggunakan ID angka dari database
            'redirect_url' => '../frontend/confirmation.html?order_id=' . $newOrderId
        ], 201);

    } else {
        throw new Exception('Failed to create order');
    }
    
} catch (Exception $e) {
    error_log("Booking error: " . $e->getMessage());
    
    sendJSONResponse([
        'success' => false,
        'message' => 'Failed to process booking: ' . $e->getMessage()
    ], 500);
}
?>
