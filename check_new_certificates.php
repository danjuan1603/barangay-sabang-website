<?php
// check_new_certificates.php
session_start();
include 'config.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';
$lastCheckTime = $_GET['last_check'] ?? null;

if ($action === 'check_new') {
    // Get the latest certificate request timestamp
    $sql = "SELECT MAX(created_at) as latest_time, COUNT(*) as total_count 
            FROM certificate_requests 
            WHERE status = 'Pending'";
    
    $result = $conn->query($sql);
    
    if ($result && $row = $result->fetch_assoc()) {
        $latestTime = $row['latest_time'];
        $totalCount = $row['total_count'];
        
        // Check if there are new certificates since last check
        $hasNew = false;
        if ($lastCheckTime && $latestTime) {
            $hasNew = strtotime($latestTime) > strtotime($lastCheckTime);
        }
        
        echo json_encode([
            'success' => true,
            'has_new' => $hasNew,
            'latest_time' => $latestTime,
            'pending_count' => $totalCount
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'has_new' => false,
            'latest_time' => null,
            'pending_count' => 0
        ]);
    }
} elseif ($action === 'get_table_data') {
    // Get current filters from request
    $search = $_GET['search'] ?? '';
    $filter = $_GET['filter'] ?? '';
    $status_filter = $_GET['status_filter'] ?? '';
    $view = $_GET['view'] ?? 'active';
    
    // Build query based on filters
    $sql = "SELECT cr.id, cr.userid, cr.certificate_type, cr.purpose, cr.created_at, cr.status,
                   CONCAT(r.surname, ', ', r.first_name, ' ', IFNULL(r.middle_name, '')) AS resident_name
            FROM certificate_requests cr
            INNER JOIN residents r ON cr.resident_unique_id = r.unique_id
            WHERE 1=1";
    
    // Apply view filter
    if ($view === 'archived') {
        $sql .= " AND cr.archived = 1";
    } else {
        $sql .= " AND cr.archived = 0";
    }
    
    // Apply search filter
    if (!empty($search)) {
        $searchEsc = $conn->real_escape_string($search);
        $sql .= " AND (r.surname LIKE '%$searchEsc%' 
                  OR r.first_name LIKE '%$searchEsc%' 
                  OR r.middle_name LIKE '%$searchEsc%'
                  OR cr.userid LIKE '%$searchEsc%'
                  OR cr.certificate_type LIKE '%$searchEsc%')";
    }
    
    // Apply certificate type filter
    if (!empty($filter)) {
        $filterEsc = $conn->real_escape_string($filter);
        $sql .= " AND cr.certificate_type = '$filterEsc'";
    }
    
    // Apply status filter
    if (!empty($status_filter)) {
        $statusEsc = $conn->real_escape_string($status_filter);
        $sql .= " AND cr.status = '$statusEsc'";
    }
    
    $sql .= " ORDER BY cr.created_at DESC";
    
    $result = $conn->query($sql);
    
    $certificates = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Check if user is blocked
            $canRequestRes = $conn->query("SELECT can_request FROM residents WHERE unique_id = '{$row['userid']}'");
            $canRequestRow = $canRequestRes ? $canRequestRes->fetch_assoc() : null;
            $canRequest = $canRequestRow ? $canRequestRow['can_request'] : 1;
            
            // Skip blocked users
            if ($canRequest == 0) {
                continue;
            }
            
            $certificates[] = [
                'id' => $row['id'],
                'userid' => $row['userid'],
                'resident_name' => $row['resident_name'],
                'certificate_type' => $row['certificate_type'],
                'purpose' => $row['purpose'],
                'created_at' => $row['created_at'],
                'status' => $row['status']
            ];
        }
    }
    
    // Get counts
    $pendingCount = $conn->query("SELECT COUNT(*) as cnt FROM certificate_requests WHERE status='Pending' AND archived=0")->fetch_assoc()['cnt'];
    $approvedCount = $conn->query("SELECT COUNT(*) as cnt FROM certificate_requests WHERE status='Approved' AND archived=0")->fetch_assoc()['cnt'];
    $printedCount = $conn->query("SELECT COUNT(*) as cnt FROM certificate_requests WHERE status='Printed' AND archived=0")->fetch_assoc()['cnt'];
    
    echo json_encode([
        'success' => true,
        'certificates' => $certificates,
        'counts' => [
            'pending' => $pendingCount,
            'approved' => $approvedCount,
            'printed' => $printedCount
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

$conn->close();
?>
