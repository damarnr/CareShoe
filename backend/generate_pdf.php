<?php
require_once 'config.php'; // Pastikan formatPrice(), getDBConnection(), dll. ada di sini
require_once 'fpdf/fpdf.php'; // Library FPDF

class BookingPDF extends FPDF
{
    // ... (Seluruh isi Class BookingPDF Anda sudah bagus, tidak perlu diubah) ...
    // ... Cukup salin-tempel dari kode asli Anda ...
}

function generateBookingPDF($orderId)
{
    try {
        $pdo = getDBConnection();
        if (!$pdo) { throw new Exception('Koneksi database gagal'); }
        
        // PENYESUAIAN: Menggunakan query yang konsisten untuk mengambil semua data
        $stmt = $pdo->prepare("
            SELECT o.*, s.name as service_name, s.estimated_time
            FROM orders o 
            JOIN services s ON o.service_id = s.id 
            WHERE o.id = ?
        ");
        $stmt->execute([$orderId]);
        $orderData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$orderData) {
            throw new Exception('Pesanan tidak ditemukan');
        }
        
        $pdf = new BookingPDF($orderData); // Class BookingPDF dari kode Anda
        $pdf->AddPage();
        $pdf->generateBookingDetails();
        
        $pdfDir = '../pdfs/';
        if (!file_exists($pdfDir)) {
            // PENYESUAIAN: Menggunakan izin folder yang lebih aman
            mkdir($pdfDir, 0755, true);
        }
        
        $filename = 'struk_booking_' . $orderId . '.pdf';
        $filepath = $pdfDir . $filename;
        $pdf->Output('F', $filepath); // Simpan file ke server
        
        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'download_url' => '../pdfs/' . $filename
        ];
        
    } catch (Exception $e) {
        error_log("PDF generation error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// Blok ini untuk dieksekusi saat diakses langsung dari URL (misal dari halaman konfirmasi)
if (isset($_GET['order_id'])) {
    $orderId = sanitizeInput($_GET['order_id']); // Pastikan fungsi sanitizeInput ada
    $result = generateBookingPDF($orderId);
    
    if ($result['success']) {
        // Paksa browser untuk mengunduh file
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
        header('Content-Length: ' . filesize($result['filepath']));
        readfile($result['filepath']);
        unlink($result['filepath']); // SARAN: Hapus file setelah diunduh untuk menghemat ruang
        exit;
    } else {
        // Jika gagal, tampilkan pesan error
        header('Content-Type: application/json');
        echo json_encode($result);
    }
}
?>