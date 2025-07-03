<?php
require_once 'config.php';

// Handle CORS preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit;
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJSONResponse([
        'success' => false,
        'message' => 'Only GET method is allowed'
    ], 405);
}

try {
    // Get database connection
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Get all services
    $stmt = $pdo->prepare("SELECT * FROM services ORDER BY id ASC");
    $stmt->execute();
    $services = $stmt->fetchAll();
    
    // Process services data
    $processedServices = [];
    foreach ($services as $service) {
        $processedServices[] = [
            'id' => $service['id'],
            'name' => $service['name'],
            'price' => $service['price'],
            'formatted_price' => formatPrice($service['price']),
            'estimated_time' => $service['estimated_time']
        ];
    }
    
    sendJSONResponse([
        'success' => true,
        'message' => 'Services retrieved successfully',
        'services' => $processedServices,
        'total_services' => count($processedServices)
    ], 200);
    
} catch (Exception $e) {
    error_log("Get services error: " . $e->getMessage());
    
    sendJSONResponse([
        'success' => false,
        'message' => 'Failed to retrieve services: ' . $e->getMessage()
    ], 500);
}
?>

