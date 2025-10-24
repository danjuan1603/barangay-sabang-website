<?php
// check_pending_certificates.php
session_start();
include 'config.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'check') {
    // Check for certificates that are Approved but not printed for more than 2 days
    $sql = "SELECT cr.id, cr.certificate_type, cr.created_at, cr.status,
                   CONCAT(r.surname, ', ', r.first_name, ' ', IFNULL(r.middle_name, '')) AS resident_name,
                   DATEDIFF(NOW(), cr.created_at) AS days_pending
            FROM certificate_requests cr
            INNER JOIN residents r ON cr.resident_unique_id = r.unique_id
            WHERE cr.status = 'Approved' 
            AND DATEDIFF(NOW(), cr.created_at) >= 2
            ORDER BY cr.created_at ASC";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $certificates = [];
        while ($row = $result->fetch_assoc()) {
            $certificates[] = [
                'id' => $row['id'],
                'certificate_type' => $row['certificate_type'],
                'resident_name' => $row['resident_name'],
                'days_pending' => $row['days_pending'],
                'created_at' => date("M d, Y", strtotime($row['created_at']))
            ];
        }
        
        echo json_encode([
            'success' => true,
            'pending_count' => count($certificates),
            'certificates' => $certificates
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'pending_count' => 0,
            'certificates' => []
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

$conn->close();
?>
