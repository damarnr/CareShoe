<?php
require_once 'config.php';

// Handle CORS preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit;
}

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
    
    // Validate required fields
    $requiredFields = ['order_id', 'payment_method'];
    $errors = validateRequiredFields($_POST, $requiredFields);
    
    if (!empty($errors)) {
        sendJSONResponse([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $errors
        ], 400);
    }
    
    // Sanitize input data
    $orderId = sanitizeInput($_POST['order_id']);
    $paymentMethod = sanitizeInput($_POST['payment_method']);
    
    // Validate order exists
    $stmt = $pdo->prepare("
        SELECT o.*, s.name as service_name, s.price as service_price 
        FROM orders o 
        JOIN services s ON o.service_id = s.id 
        WHERE o.id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    
    if (!$order) {
        sendJSONResponse([
            'success' => false,
            'message' => 'Order not found'
        ], 404);
    }
    
    // Validate payment method
    $validPaymentMethods = ['transfer_bank', 'qris', 'cod'];
    if (!in_array($paymentMethod, $validPaymentMethods)) {
        sendJSONResponse([
            'success' => false,
            'message' => 'Invalid payment method'
        ], 400);
    }
    
    // Update order with payment method
    $stmt = $pdo->prepare("UPDATE orders SET payment_option = ? WHERE id = ?");
    $result = $stmt->execute([$paymentMethod, $orderId]);
    
    if (!$result) {
        throw new Exception('Failed to update payment method');
    }
    
    // Prepare response based on payment method
    $response = [
        'success' => true,
        'message' => 'Payment method confirmed',
        'order_id' => $orderId,
        'payment_method' => $paymentMethod,
        'order_details' => $order
    ];
    
    switch ($paymentMethod) {
        case 'transfer_bank':
            $response['payment_info'] = [
                'type' => 'bank_transfer',
                'banks' => [
                    [
                        'name' => 'Bank BCA',
                        'account_number' => '1234567890',
                        'account_name' => 'CareShoe Indonesia'
                    ],
                    [
                        'name' => 'Bank Mandiri',
                        'account_number' => '0987654321',
                        'account_name' => 'CareShoe Indonesia'
                    ]
                ],
                'amount' => $order['total_price'],
                'instructions' => 'Transfer sesuai nominal yang tertera dan kirim bukti pembayaran melalui WhatsApp ke +62 812-3456-7890'
            ];
            break;
            
        case 'qris':
            $response['payment_info'] = [
                'type' => 'qris',
                'qr_code_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=QRIS_PAYMENT_' . $orderId,
                'amount' => $order['total_price'],
                'instructions' => 'Scan QR Code menggunakan aplikasi mobile banking atau e-wallet Anda'
            ];
            break;
            
        case 'cod':
            $response['payment_info'] = [
                'type' => 'cash_on_delivery',
                'amount' => $order['total_price'],
                'instructions' => 'Pembayaran dilakukan saat sepatu dijemput atau diantar. Siapkan uang pas sesuai nominal.'
            ];
            break;
    }
    
    // Add WhatsApp redirect URL for manual confirmation
    $whatsappMessage = urlencode(
        "Halo, saya ingin konfirmasi pesanan:\n" .
        "ID Pesanan: {$orderId}\n" .
        "Nama: {$order['full_name']}\n" .
        "Layanan: {$order['service_name']}\n" .
        "Total: " . formatPrice($order['total_price']) . "\n" .
        "Metode Pembayaran: {$paymentMethod}"
    );
    
    $response['whatsapp_url'] = "https://wa.me/6281234567890?text={$whatsappMessage}";
    $response['redirect_url'] = "../frontend/tracking.html?order_id={$orderId}";
    
    sendJSONResponse($response, 200);
    
} catch (Exception $e) {
    error_log("Payment processing error: " . $e->getMessage());
    
    sendJSONResponse([
        'success' => false,
        'message' => 'Failed to process payment: ' . $e->getMessage()
    ], 500);
}
?>

