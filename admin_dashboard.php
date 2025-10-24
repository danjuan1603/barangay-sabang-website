<?php
// index.php
ob_start(); // Start output buffering to catch any unwanted output

require_once 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

date_default_timezone_set('Asia/Manila');

// Helper function to calculate time ago
if (!function_exists('getTimeAgo')) {
    function getTimeAgo($datetime) {
        $timestamp = strtotime($datetime);
        $diff = time() - $timestamp;
        
        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } else {
            return date('M d, Y', $timestamp);
        }
    }
}

// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'config.php';

// Handle AJAX Get Pending Tasks
if (isset($_GET['action']) && $_GET['action'] === 'get_pending_tasks') {
    ob_clean();
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
        echo json_encode(['success' => false, 'message' => 'Admin login required']);
        exit;
    }
    
    $pendingIncidents = 0;
    $pendingCertificates = 0;
    $unreadMessages = 0;
    $pendingChatReports = 0;
    
    // Count unsolved incidents (Pending status)
    $incidentQuery = $conn->query("SELECT COUNT(*) as count FROM incident_reports WHERE status = 'Pending'");
    if ($incidentQuery) {
        $pendingIncidents = $incidentQuery->fetch_assoc()['count'];
    }
    
    // Count pending and approved certificates (Pending and Approved status)
    $certQuery = $conn->query("SELECT COUNT(*) as count FROM certificate_requests WHERE status IN ('Pending', 'Approved')");
    if ($certQuery) {
        $pendingCertificates = $certQuery->fetch_assoc()['count'];
    }
    
    // Count unread messages sent to admin (from users in admin_chats table)
    $messageQuery = $conn->query("SELECT COUNT(*) as count FROM admin_chats WHERE sender = 'user' AND is_read = 0");
    if ($messageQuery) {
        $unreadMessages = $messageQuery->fetch_assoc()['count'];
    }
    
    // Count pending chat reports (Pending and Reviewed status)
    $chatReportsQuery = $conn->query("SELECT COUNT(*) as count FROM chat_reports WHERE status IN ('pending', 'reviewed')");
    if ($chatReportsQuery) {
        $pendingChatReports = $chatReportsQuery->fetch_assoc()['count'];
    }
    
    $totalTasks = $pendingIncidents + $pendingCertificates + $unreadMessages + $pendingChatReports;
    
    echo json_encode([
        'success' => true,
        'pendingIncidents' => $pendingIncidents,
        'pendingCertificates' => $pendingCertificates,
        'unreadMessages' => $unreadMessages,
        'pendingChatReports' => $pendingChatReports,
        'totalTasks' => $totalTasks
    ]);
    exit;
}

// Handle AJAX Update Certificate Options
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['panel']) && $_GET['panel'] === 'certificates' && 
    !isset($_POST['action']) && // Only handle if no action parameter (to avoid conflict with content management)
    !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    ob_clean();
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
        echo json_encode(['success' => false, 'message' => 'Admin login required']);
        exit;
    }
    
    // Get enabled certificates (empty array if none selected)
    $enabled = isset($_POST['enabled']) ? $_POST['enabled'] : [];
    
    // Disable all certificates first
    $conn->query("UPDATE certificate_options SET is_enabled = 0");
    
    // Enable selected certificates
    if (!empty($enabled)) {
        $ids = implode(',', array_map('intval', $enabled));
        $conn->query("UPDATE certificate_options SET is_enabled = 1 WHERE id IN ($ids)");
    }
    
    $message = "Certificate availability updated.";
    
    echo json_encode(['success' => true, 'message' => $message]);
    exit;
}

// Handle AJAX Get Certificate Content
if (isset($_GET['panel']) && $_GET['panel'] === 'certificates' && 
    isset($_GET['action']) && $_GET['action'] === 'get_certificate_content') {
    
    ob_clean();
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
        echo json_encode(['success' => false, 'message' => 'Admin login required']);
        exit;
    }
    
    // Check if table exists, if not create it
    $tableCheck = $conn->query("SHOW TABLES LIKE 'certificate_content'");
    if ($tableCheck->num_rows == 0) {
        $conn->query("CREATE TABLE certificate_content (
            id INT PRIMARY KEY AUTO_INCREMENT,
            barangay_captain VARCHAR(255) DEFAULT 'Hon. Kenneth S. Saria',
            barangay_name VARCHAR(255) DEFAULT 'Barangay Sabang',
            city VARCHAR(255) DEFAULT 'Dasmariñas City, Cavite',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        $conn->query("INSERT INTO certificate_content (id) VALUES (1)");
    }
    
    $result = $conn->query("SELECT * FROM certificate_content WHERE id = 1");
    if ($result && $result->num_rows > 0) {
        $content = $result->fetch_assoc();
        echo json_encode(['success' => true, 'content' => $content]);
    } else {
        // Return defaults if no record exists
        echo json_encode(['success' => true, 'content' => [
            'barangay_captain' => 'Hon. Kenneth S. Saria',
            'barangay_name' => 'Barangay Sabang',
            'city' => 'Dasmariñas City, Cavite'
        ]]);
    }
    exit;
}

// Handle AJAX Update Certificate Content
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_certificate_content' && 
    isset($_GET['panel']) && $_GET['panel'] === 'certificates' && 
    !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    
    ob_clean();
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
        echo json_encode(['success' => false, 'message' => 'Admin login required']);
        exit;
    }
    
    $barangay_captain = $_POST['barangay_captain'] ?? 'Hon. Kenneth S. Saria';
    $barangay_name = $_POST['barangay_name'] ?? 'Barangay Sabang';
    $city = $_POST['city'] ?? 'Dasmariñas City, Cavite';
    
    // Check if table exists, if not create it
    $tableCheck = $conn->query("SHOW TABLES LIKE 'certificate_content'");
    if ($tableCheck->num_rows == 0) {
        $conn->query("CREATE TABLE certificate_content (
            id INT PRIMARY KEY AUTO_INCREMENT,
            barangay_captain VARCHAR(255) DEFAULT 'Hon. Kenneth S. Saria',
            barangay_name VARCHAR(255) DEFAULT 'Barangay Sabang',
            city VARCHAR(255) DEFAULT 'Dasmariñas City, Cavite',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
    }
    
    // Update or insert
    $stmt = $conn->prepare("INSERT INTO certificate_content (id, barangay_captain, barangay_name, city) 
                           VALUES (1, ?, ?, ?) 
                           ON DUPLICATE KEY UPDATE 
                           barangay_captain = VALUES(barangay_captain),
                           barangay_name = VALUES(barangay_name),
                           city = VALUES(city)");
    
    $stmt->bind_param("sss", $barangay_captain, $barangay_name, $city);
    
    if ($stmt->execute()) {
        $admin_username = $_SESSION['admin_username'] ?? 'Unknown';
        $action_log = "Updated certificate content settings";
        $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
        $log->bind_param("ss", $admin_username, $action_log);
        $log->execute();
        $log->close();
        
        echo json_encode(['success' => true, 'message' => 'Certificate content updated successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update certificate content']);
    }
    
    $stmt->close();
    exit;
}

// Handle AJAX Get Resident Data
if (isset($_GET['get_resident_ajax']) && $_GET['get_resident_ajax'] == 1 && isset($_GET['id'])) {
    ob_clean();
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
        echo json_encode(['success' => false, 'message' => 'Admin login required']);
        exit;
    }
    
    $unique_id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM residents WHERE unique_id = ?");
    $stmt->bind_param("s", $unique_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $resident = $result->fetch_assoc();
    $stmt->close();
    
    if ($resident) {
        echo json_encode(['success' => true, 'resident' => $resident]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Resident not found']);
    }
    exit;
}

// Handle AJAX Get Residents Count
if (isset($_GET['action']) && $_GET['action'] === 'get_residents_count') {
    ob_clean();
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
        echo json_encode(['success' => false, 'message' => 'Admin login required']);
        exit;
    }
    
    $countQuery = $conn->query("SELECT COUNT(*) as count FROM residents");
    $count = 0;
    if ($countQuery) {
        $row = $countQuery->fetch_assoc();
        $count = $row['count'];
    }
    
    echo json_encode(['success' => true, 'count' => $count]);
    exit;
}

// Handle AJAX Get Dashboard Stats with Time Period Filter
if (isset($_GET['action']) && $_GET['action'] === 'get_dashboard_stats') {
    ob_clean();
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
        echo json_encode(['success' => false, 'message' => 'Admin login required']);
        exit;
    }
    
    $period = $_GET['period'] ?? 'month';
    
    // Calculate date range based on period
    $whereClause = "";
    $prevWhereClause = "";
    
    switch($period) {
        case 'today':
            $whereClause = "DATE(created_at) = CURDATE()";
            $prevWhereClause = "DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            break;
        case 'week':
            $whereClause = "YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";
            $prevWhereClause = "YEARWEEK(created_at, 1) = YEARWEEK(DATE_SUB(CURDATE(), INTERVAL 1 WEEK), 1)";
            break;
        case 'month':
            $whereClause = "YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())";
            $prevWhereClause = "YEAR(created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND MONTH(created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))";
            break;
        case 'year':
            $whereClause = "YEAR(created_at) = YEAR(CURDATE())";
            $prevWhereClause = "YEAR(created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 YEAR))";
            break;
        case 'all':
        default:
            $whereClause = "1=1";
            $prevWhereClause = "1=0"; // No previous period for all time
            break;
    }
    
    // Count total requests (active + archived)
    $requestsQuery = $conn->query("
        SELECT COUNT(*) as count FROM (
            SELECT created_at FROM certificate_requests WHERE $whereClause
            UNION ALL
            SELECT created_at FROM archived_certificate_requests WHERE $whereClause
        ) AS all_requests
    ");
    $totalRequests = $requestsQuery ? $requestsQuery->fetch_assoc()['count'] : 0;
    
    // Count previous period requests for trend (active + archived)
    $prevRequestsQuery = $conn->query("
        SELECT COUNT(*) as count FROM (
            SELECT created_at FROM certificate_requests WHERE $prevWhereClause
            UNION ALL
            SELECT created_at FROM archived_certificate_requests WHERE $prevWhereClause
        ) AS all_prev_requests
    ");
    $prevRequests = $prevRequestsQuery ? $prevRequestsQuery->fetch_assoc()['count'] : 0;
    
    // Calculate trend
    $requestsTrend = "+0%";
    if ($prevRequests > 0) {
        $trendPercent = (($totalRequests - $prevRequests) / $prevRequests) * 100;
        $requestsTrend = ($trendPercent >= 0 ? "+" : "") . number_format($trendPercent, 1) . "%";
    } elseif ($totalRequests > 0) {
        $requestsTrend = "+100%";
    }
    
    // Count total incidents (active + archived)
    $incidentsQuery = $conn->query("
        SELECT COUNT(*) as count FROM (
            SELECT created_at FROM incident_reports WHERE $whereClause
            UNION ALL
            SELECT created_at FROM archived_incident_reports WHERE $whereClause
        ) AS all_incidents
    ");
    $totalIncidents = $incidentsQuery ? $incidentsQuery->fetch_assoc()['count'] : 0;
    
    // Count previous period incidents (active + archived)
    $prevIncidentsQuery = $conn->query("
        SELECT COUNT(*) as count FROM (
            SELECT created_at FROM incident_reports WHERE $prevWhereClause
            UNION ALL
            SELECT created_at FROM archived_incident_reports WHERE $prevWhereClause
        ) AS all_prev_incidents
    ");
    $prevIncidents = $prevIncidentsQuery ? $prevIncidentsQuery->fetch_assoc()['count'] : 0;
    
    // Calculate trend
    $incidentsTrend = "+0%";
    if ($prevIncidents > 0) {
        $trendPercent = (($totalIncidents - $prevIncidents) / $prevIncidents) * 100;
        $incidentsTrend = ($trendPercent >= 0 ? "+" : "") . number_format($trendPercent, 1) . "%";
    } elseif ($totalIncidents > 0) {
        $incidentsTrend = "+100%";
    }
    
    // Calculate average response time using completed_at timestamp
    $avgTimeQuery = $conn->query("
        SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, completed_at)) as avg_hours 
        FROM (
            SELECT created_at, completed_at FROM certificate_requests 
            WHERE status = 'Printed' AND completed_at IS NOT NULL AND $whereClause
            UNION ALL
            SELECT created_at, completed_at FROM archived_certificate_requests 
            WHERE status = 'Printed' AND completed_at IS NOT NULL AND $whereClause
        ) AS combined
    ");
    
    if ($avgTimeQuery && $row = $avgTimeQuery->fetch_assoc()) {
        $avgHours = $row['avg_hours'];
        if ($avgHours !== null && $avgHours > 0) {
            if ($avgHours < 1) {
                $avgResponseTime = round($avgHours * 60) . "m";
            } elseif ($avgHours < 24) {
                $avgResponseTime = round($avgHours, 1) . "h";
            } else {
                $avgResponseTime = round($avgHours / 24, 1) . "d";
            }
        } else {
            $avgResponseTime = "< 1h";
        }
    } else {
        $avgResponseTime = "N/A";
    }
    
    // Calculate completion rate (Printed / Total) - includes archived
    $completedQuery = $conn->query("
        SELECT COUNT(*) as count FROM (
            SELECT status FROM certificate_requests WHERE status = 'Printed' AND $whereClause
            UNION ALL
            SELECT status FROM archived_certificate_requests WHERE status = 'Printed' AND $whereClause
        ) AS all_completed
    ");
    $completedCount = $completedQuery ? $completedQuery->fetch_assoc()['count'] : 0;
    
    $completionRate = "0%";
    if ($totalRequests > 0) {
        $completionRate = number_format(($completedCount / $totalRequests) * 100, 0) . "%";
    }
    
    echo json_encode([
        'success' => true,
        'totalRequests' => $totalRequests,
        'requestsTrend' => $requestsTrend,
        'totalIncidents' => $totalIncidents,
        'incidentsTrend' => $incidentsTrend,
        'avgResponseTime' => $avgResponseTime,
        'completionRate' => $completionRate
    ]);
    exit;
}

// Handle AJAX Get Recent Activity (for dashboard Recent Activity panel)
if (isset($_GET['action']) && $_GET['action'] === 'get_recent_activity') {
    ob_clean();
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
        echo json_encode(['success' => false, 'message' => 'Admin login required']);
        exit;
    }
    
    try {
        $activities = [];
        
        // Check if database connection exists
        if (!isset($conn)) {
            echo json_encode(['success' => false, 'message' => 'Database connection not available']);
            exit;
        }
        
        // Fetch recent incident reports (last 10)
        $incidentQuery = "
            SELECT 
                ir.id,
                ir.incident_type,
                ir.incident_description,
                ir.created_at,
                ir.status,
                r.first_name,
                r.surname
            FROM incident_reports ir
            LEFT JOIN residents r ON ir.userid = r.unique_id
            ORDER BY ir.created_at DESC
            LIMIT 10
        ";
        
        $incidentResult = $conn->query($incidentQuery);
        if ($incidentResult === false) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
            exit;
        }
        
        if ($incidentResult && $incidentResult->num_rows > 0) {
            while ($row = $incidentResult->fetch_assoc()) {
                $reporterName = ($row['first_name'] && $row['surname']) 
                    ? $row['first_name'] . ' ' . $row['surname'] 
                    : 'Anonymous';
                
                $timeAgo = getTimeAgo($row['created_at']);
                
                $activities[] = [
                    'type' => 'incident',
                    'icon' => 'alert-triangle',
                    'color' => '#dc3545',
                    'title' => 'Incident Reported',
                    'desc' => htmlspecialchars($row['incident_type']) . ' - ' . htmlspecialchars($reporterName),
                    'time' => $timeAgo,
                    'status' => $row['status'],
                    'timestamp' => strtotime($row['created_at'])
                ];
            }
        }
        
        // Fetch recent certificate requests (last 10)
        $certQuery = "
            SELECT 
                cr.id,
                cr.certificate_type,
                cr.created_at,
                cr.status,
                r.first_name,
                r.surname
            FROM certificate_requests cr
            LEFT JOIN residents r ON cr.resident_unique_id = r.unique_id
            ORDER BY cr.created_at DESC
            LIMIT 10
        ";
        
        $certResult = $conn->query($certQuery);
        if ($certResult === false) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
            exit;
        }
        
        if ($certResult && $certResult->num_rows > 0) {
            while ($row = $certResult->fetch_assoc()) {
                $requesterName = ($row['first_name'] && $row['surname']) 
                    ? $row['first_name'] . ' ' . $row['surname'] 
                    : 'Unknown';
                
                $timeAgo = getTimeAgo($row['created_at']);
                
                $iconColor = '#667eea';
                $iconName = 'file-text';
                $title = 'Certificate Request';
                
                if ($row['status'] === 'Approved') {
                    $iconColor = '#43e97b';
                    $iconName = 'check-circle';
                    $title = 'Certificate Approved';
                } elseif ($row['status'] === 'Printed') {
                    $iconColor = '#14ad0f';
                    $iconName = 'check-circle';
                    $title = 'Certificate Printed';
                }
                
                $activities[] = [
                    'type' => 'certificate',
                    'icon' => $iconName,
                    'color' => $iconColor,
                    'title' => $title,
                    'desc' => htmlspecialchars($row['certificate_type']) . ' - ' . htmlspecialchars($requesterName),
                    'time' => $timeAgo,
                    'status' => $row['status'],
                    'timestamp' => strtotime($row['created_at'])
                ];
            }
        }
        
        // Fetch recent admin chat messages (last 10)
        $chatQuery = "
            SELECT 
                ac.chat_id as id,
                ac.message,
                ac.created_at,
                ac.sender,
                r.first_name,
                r.surname
            FROM admin_chats ac
            LEFT JOIN residents r ON ac.userid = r.unique_id
            WHERE ac.sender = 'user'
            ORDER BY ac.created_at DESC
            LIMIT 10
        ";
        
        $chatResult = $conn->query($chatQuery);
        if ($chatResult === false) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
            exit;
        }
        
        if ($chatResult && $chatResult->num_rows > 0) {
            while ($row = $chatResult->fetch_assoc()) {
                $senderName = ($row['first_name'] && $row['surname']) 
                    ? $row['first_name'] . ' ' . $row['surname'] 
                    : 'Unknown User';
                
                $timeAgo = getTimeAgo($row['created_at']);
                $messagePreview = mb_substr($row['message'], 0, 50) . (mb_strlen($row['message']) > 50 ? '...' : '');
                
                $activities[] = [
                    'type' => 'chat',
                    'icon' => 'chat-dots',
                    'color' => '#8b5cf6',
                    'title' => 'New Message',
                    'desc' => htmlspecialchars($senderName) . ': ' . htmlspecialchars($messagePreview),
                    'time' => $timeAgo,
                    'timestamp' => strtotime($row['created_at'])
                ];
            }
        }
        
        // Sort all activities by timestamp (most recent first)
        if (count($activities) > 0) {
            usort($activities, function($a, $b) {
                $timeA = isset($a['timestamp']) ? $a['timestamp'] : 0;
                $timeB = isset($b['timestamp']) ? $b['timestamp'] : 0;
                return $timeB - $timeA;
            });
            
            // Limit to 10 most recent activities
            $activities = array_slice($activities, 0, 10);
        }
        
        // Create a hash of the activities to detect changes
        $dataHash = md5(json_encode($activities));
        
        echo json_encode([
            'success' => true,
            'activities' => $activities,
            'dataHash' => $dataHash
        ]);
        exit;
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}

// Handle AJAX Get Blocked Users (for incidents panel)
if (isset($_GET['panel']) && $_GET['panel'] === 'incidents' && 
    isset($_GET['action']) && $_GET['action'] === 'get_blocked_users') {
    
    ob_clean();
    // ... (rest of the code remains the same)
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
        echo json_encode(['success' => false, 'message' => 'Admin login required']);
        exit;
    }
    
    $sql = "SELECT r.unique_id, r.first_name, r.middle_name, r.surname 
            FROM residents r 
            WHERE r.can_submit_incidents = 0 
            ORDER BY r.surname, r.first_name";
    
    $result = $conn->query($sql);   
    
    if ($result && $result->num_rows > 0) {
        $html = '';
        while ($row = $result->fetch_assoc()) {
            $fullname = trim($row['first_name'] . ' ' . ($row['middle_name'] ? $row['middle_name'] . ' ' : '') . $row['surname']);
            
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($row['unique_id']) . '</td>';
            $html .= '<td>' . htmlspecialchars($fullname) . '</td>';
            $html .= '<td class="text-center">';
            $html .= '<button class="btn btn-success btn-sm unblock-user-from-modal-btn" data-userid="' . htmlspecialchars($row['unique_id']) . '">';
            $html .= '<i class="bi bi-person-check me-1"></i>Unblock';
            $html .= '</button>';
            $html .= '</td>';
            $html .= '</tr>';
        }
        echo json_encode(['success' => true, 'html' => $html]);
    } else {
        $html = '<tr><td colspan="3" class="text-center text-muted py-4">';
        $html .= '<i class="bi bi-info-circle me-2"></i>No blocked users found.';
        $html .= '</td></tr>';
        echo json_encode(['success' => true, 'html' => $html]);
    }
    exit;
}

// Handle AJAX Get Chat Reports
if (isset($_GET['action']) && $_GET['action'] === 'get_chat_reports') {
    ob_clean();
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
        echo json_encode(['success' => false, 'message' => 'Admin login required']);
        exit;
    }
    
    $sql = "SELECT cr.*, 
            CONCAT(r1.surname, ', ', r1.first_name) as reporter_name,
            CONCAT(r2.surname, ', ', r2.first_name) as reported_name
            FROM chat_reports cr
            LEFT JOIN residents r1 ON cr.reporter_id = r1.unique_id
            LEFT JOIN residents r2 ON cr.reported_id = r2.unique_id
            WHERE cr.status NOT IN ('dismissed', 'resolved')
            ORDER BY cr.created_at DESC";
    
    $result = $conn->query($sql);
    
    if ($result) {
        $reports = [];
        while ($row = $result->fetch_assoc()) {
            $reports[] = [
                'id' => $row['id'],
                'reporter_id' => $row['reporter_id'],
                'reporter_name' => $row['reporter_name'] ?? 'Unknown',
                'reported_id' => $row['reported_id'],
                'reported_name' => $row['reported_name'] ?? 'Unknown',
                'reason' => $row['reason'],
                'details' => $row['details'],
                'status' => $row['status'],
                'created_at' => date('M d, Y h:i A', strtotime($row['created_at']))
            ];
        }
        echo json_encode(['success' => true, 'reports' => $reports]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error fetching reports']);
    }
    exit;
}

// Handle AJAX Get Dismissed Reports
if (isset($_GET['action']) && $_GET['action'] === 'get_dismissed_reports') {
    ob_clean();
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
        echo json_encode(['success' => false, 'message' => 'Admin login required']);
        exit;
    }
    
    $sql = "SELECT cr.*, 
            CONCAT(r1.surname, ', ', r1.first_name) as reporter_name,
            CONCAT(r2.surname, ', ', r2.first_name) as reported_name
            FROM chat_reports cr
            LEFT JOIN residents r1 ON cr.reporter_id = r1.unique_id
            LEFT JOIN residents r2 ON cr.reported_id = r2.unique_id
            WHERE cr.status = 'dismissed'
            ORDER BY cr.created_at DESC";
    
    $result = $conn->query($sql);
    
    if ($result) {
        $reports = [];
        while ($row = $result->fetch_assoc()) {
            $reports[] = [
                'id' => $row['id'],
                'reporter_id' => $row['reporter_id'],
                'reporter_name' => $row['reporter_name'] ?? 'Unknown',
                'reported_id' => $row['reported_id'],
                'reported_name' => $row['reported_name'] ?? 'Unknown',
                'reason' => $row['reason'],
                'details' => $row['details'],
                'status' => $row['status'],
                'created_at' => date('M d, Y h:i A', strtotime($row['created_at']))
            ];
        }
        echo json_encode(['success' => true, 'reports' => $reports]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error fetching dismissed reports']);
    }
    exit;
}

// Handle AJAX Get Pending Reports Count
if (isset($_GET['action']) && $_GET['action'] === 'get_pending_reports_count') {
    ob_clean();
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
        echo json_encode(['success' => false, 'count' => 0]);
        exit;
    }
    
    $sql = "SELECT COUNT(*) as count FROM chat_reports WHERE status IN ('pending', 'reviewed')";
    $result = $conn->query($sql);
    
    if ($result) {
        $row = $result->fetch_assoc();
        echo json_encode(['success' => true, 'count' => (int)$row['count']]);
    } else {
        echo json_encode(['success' => false, 'count' => 0]);
    }
    exit;
}

// Handle AJAX Get Resolved Reports
if (isset($_GET['action']) && $_GET['action'] === 'get_resolved_reports') {
    ob_clean();
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
        echo json_encode(['success' => false, 'message' => 'Admin login required']);
        exit;
    }
    
    $sql = "SELECT cr.*, 
            CONCAT(r1.surname, ', ', r1.first_name) as reporter_name,
            CONCAT(r2.surname, ', ', r2.first_name) as reported_name
            FROM chat_reports cr
            LEFT JOIN residents r1 ON cr.reporter_id = r1.unique_id
            LEFT JOIN residents r2 ON cr.reported_id = r2.unique_id
            WHERE cr.status = 'resolved'
            ORDER BY cr.created_at DESC";
    
    $result = $conn->query($sql);
    
    if ($result) {
        $reports = [];
        while ($row = $result->fetch_assoc()) {
            $reports[] = [
                'id' => $row['id'],
                'reporter_id' => $row['reporter_id'],
                'reporter_name' => $row['reporter_name'] ?? 'Unknown',
                'reported_id' => $row['reported_id'],
                'reported_name' => $row['reported_name'] ?? 'Unknown',
                'reason' => $row['reason'],
                'details' => $row['details'],
                'status' => $row['status'],
                'created_at' => date('M d, Y h:i A', strtotime($row['created_at']))
            ];
        }
        echo json_encode(['success' => true, 'reports' => $reports]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error fetching resolved reports']);
    }
    exit;
}

// Handle AJAX Update Report Status
if (isset($_POST['action']) && $_POST['action'] === 'update_report_status') {
    ob_clean();
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
        echo json_encode(['success' => false, 'message' => 'Admin login required']);
        exit;
    }
    
    $report_id = $_POST['report_id'] ?? null;
    $status = $_POST['status'] ?? null;
    
    if (!$report_id || !$status) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
        exit;
    }
    
    if (!in_array($status, ['pending', 'reviewed', 'resolved', 'dismissed'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }
    
    $admin_id = $_SESSION['admin_id'] ?? null;
    $stmt = $conn->prepare("UPDATE chat_reports SET status = ?, reviewed_at = NOW(), reviewed_by = ? WHERE id = ?");
    $stmt->bind_param("sii", $status, $admin_id, $report_id);
    
    if ($stmt->execute()) {
        // Log the action
        $admin_username = $_SESSION['admin_username'] ?? 'Unknown';
        $action_log = "Updated chat report #$report_id to status: $status";
        $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
        $log->bind_param("ss", $admin_username, $action_log);
        $log->execute();
        $log->close();
        
        echo json_encode(['success' => true, 'message' => 'Report status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating report status']);
    }
    $stmt->close();
    exit;
}

// Handle AJAX Get Blocked Users (for certificates panel)
if (isset($_GET['panel']) && $_GET['panel'] === 'certificates' && 
    isset($_GET['action']) && $_GET['action'] === 'get_blocked_cert_users') {
    
    ob_clean();
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
        echo json_encode(['success' => false, 'message' => 'Admin login required']);
        exit;
    }
    
    $sql = "SELECT r.unique_id, r.first_name, r.middle_name, r.surname 
            FROM residents r 
            WHERE r.can_request = 0 
            ORDER BY r.surname, r.first_name";
    
    $result = $conn->query($sql);   
    
    if ($result && $result->num_rows > 0) {
        $html = '';
        while ($row = $result->fetch_assoc()) {
            $fullname = trim($row['first_name'] . ' ' . ($row['middle_name'] ? $row['middle_name'] . ' ' : '') . $row['surname']);
            
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($row['unique_id']) . '</td>';
            $html .= '<td>' . htmlspecialchars($fullname) . '</td>';
            $html .= '<td class="text-center">';
            $html .= '<button class="btn btn-success btn-sm unblock-cert-user-from-modal-btn" data-userid="' . htmlspecialchars($row['unique_id']) . '">';
            $html .= '<i class="bi bi-person-check me-1"></i>Unblock';
            $html .= '</button>';
            $html .= '</td>';
            $html .= '</tr>';
        }
        echo json_encode(['success' => true, 'html' => $html]);
    } else {
        $html = '<tr><td colspan="3" class="text-center text-muted py-4">';
        $html .= '<i class="bi bi-info-circle me-2"></i>No blocked users found.';
        $html .= '</td></tr>';
    }
    
    exit;
}

// Handle AJAX Search All Residents (for blocking any user)
if (isset($_GET['panel']) && $_GET['panel'] === 'jobfinder' && 
    isset($_GET['action']) && $_GET['action'] === 'search_all_residents') {
    
    ob_clean();
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
        echo json_encode([]);
        exit;
    }
    
    $query = isset($_GET['q']) ? trim($_GET['q']) : '';
    
    if (strlen($query) < 2) {
        echo json_encode([]);
        exit;
    }
    
    // Ensure column exists
    $check_column = $conn->query("SHOW COLUMNS FROM residents LIKE 'blocked_from_jobfinder'");
    if ($check_column->num_rows == 0) {
        $conn->query("ALTER TABLE residents ADD COLUMN blocked_from_jobfinder TINYINT(1) DEFAULT 0");
    }
    
    // Search all residents (not just those with skills)
    $search_param = '%' . $query . '%';
    $sql = "SELECT r.unique_id, r.surname, r.first_name, r.occupation_skills, r.profile_image,
            COALESCE(r.blocked_from_jobfinder, 0) as blocked_from_jobfinder
            FROM residents r 
            WHERE (r.unique_id LIKE ? 
               OR r.surname LIKE ? 
               OR r.first_name LIKE ? 
               OR r.occupation_skills LIKE ?)
            ORDER BY r.surname ASC, r.first_name ASC
            LIMIT 50";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $search_param, $search_param, $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $residents = [];
    while ($row = $result->fetch_assoc()) {
        $residents[] = [
            'unique_id' => $row['unique_id'],
            'name' => $row['surname'] . ', ' . $row['first_name'],
            'occupation_skills' => $row['occupation_skills'],
            'profile_image' => $row['profile_image'],
            'blocked_from_jobfinder' => $row['blocked_from_jobfinder']
        ];
    }
    
    $stmt->close();
    echo json_encode($residents);
    exit;
}

// Handle AJAX Get Blocked Jobfinder Users
if (isset($_GET['panel']) && $_GET['panel'] === 'jobfinder' && 
    isset($_GET['action']) && $_GET['action'] === 'get_blocked_jobfinder_users') {
    
    ob_clean();
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
        echo json_encode(['success' => false, 'message' => 'Admin login required']);
        exit;
    }
    
    // Ensure column exists
    $check_column = $conn->query("SHOW COLUMNS FROM residents LIKE 'blocked_from_jobfinder'");
    if ($check_column->num_rows == 0) {
        $conn->query("ALTER TABLE residents ADD COLUMN blocked_from_jobfinder TINYINT(1) DEFAULT 0");
    }
    
    $sql = "SELECT r.unique_id, r.surname, r.first_name, r.occupation_skills, r.profile_image 
            FROM residents r 
            WHERE r.blocked_from_jobfinder = 1 
            ORDER BY r.surname ASC";
    
    $result = $conn->query($sql);
    $blocked_users = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $blocked_users[] = [
                'unique_id' => $row['unique_id'],
                'name' => $row['surname'] . ', ' . $row['first_name'],
                'occupation_skills' => $row['occupation_skills'] ?? 'No skills listed',
                'profile_image' => $row['profile_image']
            ];
        }
    }
    
    echo json_encode($blocked_users);
    exit;
}

// Handle AJAX Block/Unblock Jobfinder User
if (isset($_POST['ajax_jobfinder_action']) && isset($_POST['resident_id'])) {
    ob_clean();
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
        echo json_encode(['success' => false, 'message' => 'Admin login required']);
        exit;
    }
    
    $resident_id = $_POST['resident_id'];
    $action = $_POST['ajax_jobfinder_action'];
    
    // Ensure columns exist
    $check_column = $conn->query("SHOW COLUMNS FROM residents LIKE 'blocked_from_jobfinder'");
    if ($check_column->num_rows == 0) {
        $conn->query("ALTER TABLE residents ADD COLUMN blocked_from_jobfinder TINYINT(1) DEFAULT 0");
    }
    
    $check_verified = $conn->query("SHOW COLUMNS FROM residents LIKE 'jobfinder_verified'");
    if ($check_verified->num_rows == 0) {
        $conn->query("ALTER TABLE residents ADD COLUMN jobfinder_verified TINYINT(1) DEFAULT 0");
    }
    
    if ($action === 'verify') {
        $stmt = $conn->prepare("UPDATE residents SET jobfinder_verified = 1 WHERE unique_id = ?");
        $stmt->bind_param("s", $resident_id);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Log the action
            $admin_username = $_SESSION['admin_username'] ?? 'Unknown';
            $action_log = "Verified resident $resident_id in Jobfinder";
            $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
            $log->bind_param("ss", $admin_username, $action_log);
            $log->execute();
            $log->close();
            
            echo json_encode(['success' => true, 'message' => 'Resident verified in Jobfinder successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to verify resident.']);
        }
        exit;
        
    } elseif ($action === 'unverify') {
        $stmt = $conn->prepare("UPDATE residents SET jobfinder_verified = 0 WHERE unique_id = ?");
        $stmt->bind_param("s", $resident_id);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Log the action
            $admin_username = $_SESSION['admin_username'] ?? 'Unknown';
            $action_log = "Unverified resident $resident_id in Jobfinder";
            $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
            $log->bind_param("ss", $admin_username, $action_log);
            $log->execute();
            $log->close();
            
            echo json_encode(['success' => true, 'message' => 'Resident unverified in Jobfinder successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to unverify resident.']);
        }
        exit;
        
    } elseif ($action === 'block') {
        $stmt = $conn->prepare("UPDATE residents SET blocked_from_jobfinder = 1 WHERE unique_id = ?");
        $stmt->bind_param("s", $resident_id);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Log the action
            $admin_username = $_SESSION['admin_username'] ?? 'Unknown';
            $action_log = "Blocked resident $resident_id from Jobfinder";
            $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
            $log->bind_param("ss", $admin_username, $action_log);
            $log->execute();
            $log->close();
            
            echo json_encode(['success' => true, 'message' => 'Resident blocked from Jobfinder successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to block resident.']);
        }
        exit;
        
    } elseif ($action === 'unblock') {
        $stmt = $conn->prepare("UPDATE residents SET blocked_from_jobfinder = 0 WHERE unique_id = ?");
        $stmt->bind_param("s", $resident_id);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Log the action
            $admin_username = $_SESSION['admin_username'] ?? 'Unknown';
            $action_log = "Unblocked resident $resident_id from Jobfinder";
            $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
            $log->bind_param("ss", $admin_username, $action_log);
            $log->execute();
            $log->close();
            
            echo json_encode(['success' => true, 'message' => 'Resident unblocked from Jobfinder successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to unblock resident.']);
        }
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit;
}

// Handle AJAX Get Resident Data (for loading edit form without reload)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax_get_resident']) && 
    !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
  
  ob_clean();
  
  if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Admin login required']);
    exit;
  }
  
  $unique_id = $_GET['ajax_get_resident'] ?? '';
  
  if ($unique_id) {
    $stmt = $conn->prepare("SELECT * FROM residents WHERE unique_id = ?");
    $stmt->bind_param("s", $unique_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $resident = $result->fetch_assoc();
    $stmt->close();
    
    if ($resident) {
      header('Content-Type: application/json');
      echo json_encode(['success' => true, 'resident' => $resident]);
    } else {
      header('Content-Type: application/json');
      echo json_encode(['success' => false, 'message' => 'Resident not found']);
    }
  } else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No resident ID provided']);
  }
  exit;
}

// Handle AJAX Edit Resident Update (must be before any HTML output)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_resident_dashboard']) && 
    !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
  
  ob_clean(); // Clear any output that may have been buffered
  
  // Ensure no previous output
  if (headers_sent($file, $line)) {
    error_log("Headers already sent in $file on line $line");
  }
  
  if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Admin login required']);
    exit;
  }
  
  $admin_username = $_SESSION['admin_username'];
  $unique_id = $_POST['unique_id'] ?? '';
  
  // Get resident data first
  $stmt = $conn->prepare("SELECT * FROM residents WHERE unique_id = ?");
  $stmt->bind_param("s", $unique_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $resident = $result->fetch_assoc();
  $stmt->close();
  
  if (!$resident) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Resident not found']);
    exit;
  }
  
  // Collect POST data
  $surname = $_POST['surname'];
  $first_name = $_POST['first_name'];
  $middle_name = $_POST['middle_name'];
  $age = $_POST['age'];
  $sex = $_POST['sex'];
  $birthdate = $_POST['birthdate'];
  $place_of_birth = $_POST['place_of_birth'];
  $civil_status = $_POST['civil_status'];
  $citizenship = $_POST['citizenship'];
  $occupation_skills = $_POST['occupation_skills'];
  $education = $_POST['education'];
  $is_pwd = $_POST['is_pwd'];
  $address = $_POST['address'];
  $household_id = $_POST['household_id'];
  $is_head = $_POST['is_head'] ?? 'No';
  
  // Keep existing values for fields not in form
  $relationship = $resident['relationship'] ?? '';
  $pending_email = $resident['pending_email'] ?? '';
  $profile_image = $resident['profile_image'] ?? '';
  $skill_description = $resident['skill_description'] ?? '';
  
  // Update query
  $stmt = $conn->prepare("UPDATE residents SET 
    surname = ?, 
    first_name = ?, 
    middle_name = ?, 
    age = ?, 
    sex = ?, 
    education = ?, 
    address = ?, 
    household_id = ?, 
    relationship = ?, 
    is_head = ?, 
    birthdate = ?, 
    place_of_birth = ?, 
    civil_status = ?, 
    citizenship = ?, 
    occupation_skills = ?, 
    is_pwd = ?, 
    pending_email = ?, 
    profile_image = ?, 
    skill_description = ?
    WHERE unique_id = ?");
  
  $stmt->bind_param("sssissssssssssssssss",
    $surname, 
    $first_name, 
    $middle_name,
    $age, 
    $sex, 
    $education, 
    $address, 
    $household_id,
    $relationship, 
    $is_head, 
    $birthdate, 
    $place_of_birth,
    $civil_status, 
    $citizenship, 
    $occupation_skills, 
    $is_pwd,
    $pending_email, 
    $profile_image, 
    $skill_description,
    $unique_id
  );
  
  if ($stmt->execute()) {
    $action = "Edited resident: $surname, $first_name (ID: $unique_id)";
    $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
    $log->bind_param("ss", $admin_username, $action);
    $log->execute();
    $log->close();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Resident updated successfully']);
  } else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error updating resident: ' . $stmt->error]);
  }
  $stmt->close();
  exit;
}

$admin_username = $_SESSION['admin_username'] ?? 'Unknown';
$admin_fullname = '';
if ($admin_username !== 'Unknown') {
  $stmt = $conn->prepare("SELECT admin_name FROM admin_accounts WHERE admin_id = ? LIMIT 1");
  $stmt->bind_param("s", $admin_username);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($row = $result->fetch_assoc()) {
    $admin_fullname = $row['admin_name'];
  }
  $stmt->close();
}


// Count all unread messages for admin (from users)
include_once 'config.php';
$unread_admin_chats = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS unread FROM admin_chats WHERE is_read = 0 AND sender = 'user'");
if ($stmt) {
  $stmt->execute();
  $result = $stmt->get_result();
  if ($row = $result->fetch_assoc()) {
    $unread_admin_chats = (int)$row['unread'];
  }
  $stmt->close();
}

// --- Save Official (Add/Edit) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['osave'])) {
  $oid          = isset($_POST['oid']) ? (int)$_POST['oid'] : 0;
  $name        = trim($_POST['oname']);
  $position    = trim($_POST['oposition']);
  $description = trim($_POST['odescription']);
  $start_date  = $_POST['ostart_date'];
  $end_date    = $_POST['oend_date'];
  $photoPath   = $_POST['ophoto'] ?? '';
  // Handle photo upload
  if (!empty($_FILES['ophoto_file']['name']) && $_FILES['ophoto_file']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['image/jpeg','image/png','image/gif'];
    if (in_array($_FILES['ophoto_file']['type'], $allowed)) {
      $dir = 'uploads/';
      if (!is_dir($dir)) mkdir($dir,0777,true);
      $fileName = time().'_'.basename($_FILES['ophoto_file']['name']);
      $dest = $dir.$fileName;
      if (move_uploaded_file($_FILES['ophoto_file']['tmp_name'],$dest)) {
        if ($oid > 0 && !empty($photoPath) && is_file($photoPath)) {
          unlink($photoPath);
        }
        $photoPath = $dest;
      }
    }
  }
  if ($oid > 0) {
    $stmt = $conn->prepare("UPDATE manage_brgy_officials SET name=?, position=?, description=?, photo=?, start_date=?, end_date=? WHERE id=?");
    $stmt->bind_param("ssssssi",$name,$position,$description,$photoPath,$start_date,$end_date,$oid);
    $action_log = "Edited official: $name (ID: $oid)";
  } else {
    $stmt = $conn->prepare("INSERT INTO manage_brgy_officials (name, position, description, photo, start_date, end_date) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("ssssss",$name,$position,$description,$photoPath,$start_date,$end_date);
    $action_log = "Added official: $name";
  }
  if ($stmt->execute()) {
    $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
    $log->bind_param("ss", $admin_username, $action_log);
    $log->execute();
    $log->close();
  }
  $stmt->close();
  header("Location: admin_dashboard.php?panel=officials");
  exit;
}

$currentDate = date("l, F j, Y");

// --- Begin: Batch Excel upload logic (moved to top) ---
$excelUploadSuccess = false;
$excelUploadMessage = '';

// Debug log
error_log("POST upload_excel: " . (isset($_POST['upload_excel']) ? 'YES' : 'NO'));
error_log("FILES excel_file: " . (isset($_FILES['excel_file']) ? 'YES' : 'NO'));

if (isset($_POST['upload_excel']) && isset($_FILES['excel_file'])) {
  error_log("Excel upload processing started");
  
  $fileTmpPath = $_FILES['excel_file']['tmp_name'];
  $fileName = $_FILES['excel_file']['name'];
  $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
  
  if (!in_array($ext, ['xls', 'xlsx'])) {
    $excelUploadMessage = '❌ Invalid file type. Please upload .xls or .xlsx only.';
  } else {
    $spreadsheet = IOFactory::load($fileTmpPath);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();
    $header = array_map('strtolower', $rows[0]);
    $key = array_search('unique_id', $header);
    $hasUniqueIdColumn = ($key !== false);
    if ($hasUniqueIdColumn) unset($header[$key]);
    
    $success = 0; $fail = 0; $failRows = [];
    
    if ($hasUniqueIdColumn) {
      $stmt = $conn->prepare("INSERT INTO residents (unique_id, surname, first_name, middle_name, birthdate, age, email, sex, address, place_of_birth, civil_status, citizenship, occupation_skills, education, household_id, relationship, is_head, is_pwd) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    } else {
      $stmt = $conn->prepare("INSERT INTO residents (surname, first_name, middle_name, birthdate, age, email, sex, address, place_of_birth, civil_status, citizenship, occupation_skills, education, household_id, relationship, is_head, is_pwd) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    }
    
    for ($i = 1; $i < count($rows); $i++) {
      $row = $rows[$i];
      if ($key !== false && count($row) > count($header)) {
        $unique_id = $row[$key];
        array_splice($row, $key, 1);
      } else {
        $unique_id = null;
      }
      
      $data = array_combine($header, $row);
      $data['occupation_skills'] = (isset($data['occupation_skills']) && trim($data['occupation_skills']) !== '') ? $data['occupation_skills'] : '';
      $data['email'] = (isset($data['email']) && trim($data['email']) !== '') ? $data['email'] : '';
      $data['is_head'] = $data['is_head'] ?? 'No';
      $data['is_pwd'] = $data['is_pwd'] ?? 'No';
      
      $duplicate = false;
      if ($unique_id) {
        $check = $conn->prepare("SELECT COUNT(*) FROM residents WHERE unique_id = ?");
        $check->bind_param("s", $unique_id);
        $check->execute();
        $check->bind_result($count);
        $check->fetch();
        $check->close();
        if ($count > 0) $duplicate = true;
      }
      
      if (!$duplicate) {
        if ($hasUniqueIdColumn) {
          $uidVal = ($unique_id !== null) ? $unique_id : '';
          $stmt->bind_param("ssssisssssssssssss", $uidVal, $data['surname'], $data['first_name'], $data['middle_name'], $data['birthdate'], $data['age'], $data['email'], $data['sex'], $data['address'], $data['place_of_birth'], $data['civil_status'], $data['citizenship'], $data['occupation_skills'], $data['education'], $data['household_id'], $data['relationship'], $data['is_head'], $data['is_pwd']);
        } else {
          $stmt->bind_param("ssssissssssssssss", $data['surname'], $data['first_name'], $data['middle_name'], $data['birthdate'], $data['age'], $data['email'], $data['sex'], $data['address'], $data['place_of_birth'], $data['civil_status'], $data['citizenship'], $data['occupation_skills'], $data['education'], $data['household_id'], $data['relationship'], $data['is_head'], $data['is_pwd']);
        }
        
        if ($stmt->execute()) {
          $success++;
          $new_resident_id = $conn->insert_id;
          if (empty($unique_id)) {
            $upd = $conn->prepare("UPDATE residents SET unique_id = ? WHERE (unique_id IS NULL OR unique_id = '') AND surname = ? AND first_name = ? AND (middle_name = ? OR middle_name IS NULL) AND birthdate = ? LIMIT 1");
            if ($upd) {
              $mname = $data['middle_name'] ?? '';
              $bdate = $data['birthdate'] ?? null;
              $upd->bind_param('issss', $new_resident_id, $data['surname'], $data['first_name'], $mname, $bdate);
              $upd->execute();
              $upd->close();
            }
          }
          $surname = $data['surname'];
          $acc = $conn->prepare("INSERT INTO useraccounts (userid, password, surname) VALUES (?, NULL, ?)");
          $acc->bind_param("is", $new_resident_id, $surname);
          $acc->execute();
          $acc->close();
          
          $admin_username = $_SESSION['admin_username'] ?? 'unknown';
          $action = "Added resident: {$data['surname']}, {$data['first_name']} (ID: $unique_id)";
          $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
          $log->bind_param("ss", $admin_username, $action);
          $log->execute();
          $log->close();
        } else {
          $fail++;
          $failRows[] = "Row ".($i+1)." (".$stmt->error.")";
        }
      } else {
        $fail++;
        $failRows[] = "Row ".($i+1)." (Duplicate unique_id: $unique_id)";
      }
    }
    
    $stmt->close();
    
    if ($success > 0) {
      $excelUploadMessage .= "✅ Successfully registered $success resident(s). ";
      $excelUploadSuccess = true;
      error_log("Excel upload SUCCESS: $success residents");
    }
    if ($fail > 0) {
      $excelUploadMessage .= "❌ Failed to register $fail resident(s): ".implode(' | ', $failRows);
      error_log("Excel upload FAIL: $fail residents");
    }
    
    // Log the final message
    error_log("Excel upload message: " . $excelUploadMessage);
    error_log("Excel upload success flag: " . ($excelUploadSuccess ? 'TRUE' : 'FALSE'));
  }
}
// --- End: Batch Excel upload logic ---

// DEBUG: Show visible message if upload was processed

if (!empty($excelUploadMessage)) {
    $bgColor = $excelUploadSuccess ? '#d1e7dd' : '#f8d7da'; // greenish or reddish background
    $borderColor = $excelUploadSuccess ? '#0f5132' : '#842029';
    $title = $excelUploadSuccess ? '✅ Upload Successful' : '❌ Upload Failed';
    $textColor = $excelUploadSuccess ? '#0f5132' : '#842029';

    echo "
    <div id='uploadMessageModal' style=\"
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex; align-items: center; justify-content: center;
        z-index: 99999;
    \">
        <div style=\"
            background: {$bgColor};
            border: 2px solid {$borderColor};
            border-radius: 10px;
            padding: 25px 30px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            max-width: 400px;
            width: 90%;
            color: {$textColor};
            font-family: Arial, sans-serif;
            animation: fadeIn 0.3s ease;
        \">
            <h3 style='margin-top:0;'>{$title}</h3>
            <p><strong>Message:</strong> " . htmlspecialchars($excelUploadMessage) . "</p>
            <p><strong>Status:</strong> " . ($excelUploadSuccess ? 'SUCCESS' : 'FAILED') . "</p>
            <button onclick=\"document.getElementById('uploadMessageModal').remove();\" 
                style=\"
                    background: {$borderColor};
                    color: white;
                    border: none;
                    padding: 10px 20px;
                    border-radius: 6px;
                    cursor: pointer;
                    margin-top: 10px;
                \">OK</button>
        </div>
    </div>

    <style>
    @keyframes fadeIn { from {opacity:0;} to {opacity:1;} }
    </style>

    <script>
    // Auto-hide after 3 seconds if successful
    if (" . ($excelUploadSuccess ? 'true' : 'false') . ") {
        setTimeout(() => {
            const modal = document.getElementById('uploadMessageModal');
            if (modal) modal.remove();
        }, 3000);
    }
    </script>
    ";
}


// --- Announcement logic from manage_announcement.php ---
$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755);
$ann_action = $_POST['ann_action'] ?? $_GET['ann_action'] ?? 'list';
$ann_id = $_POST['ann_id'] ?? $_GET['ann_id'] ?? '';
$ann_error = '';

// Auto-archive old announcements (except status=new)
$conn->query("
  INSERT INTO archived_announcements (id, title, content, image, date_posted, archived_at, status)
  SELECT id, title, content, image, date_posted, NOW(), status
  FROM announcements
  WHERE status='normal' AND date_posted < (NOW() - INTERVAL 7 DAY)
");
$conn->query("
  DELETE FROM announcements 
  WHERE status='normal' AND date_posted < (NOW() - INTERVAL 7 DAY)
");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ann_submit'])) {
  $title = trim($_POST['title'] ?? '');
  $content = trim($_POST['content'] ?? '');
  $status = $_POST['status'] ?? 'normal';
  $imageFile = $_FILES['image'] ?? null;
  $imageName = null;
  
  // Debug logging
  error_log("Announcement submission - Title: $title, Status: $status, Action: $ann_action");

  if ($title === '' || $content === '') {
    $ann_error = 'Please fill both fields.';
  } else {
    if ($imageFile && $imageFile['tmp_name']) {
      $ext = strtolower(pathinfo($imageFile['name'], PATHINFO_EXTENSION));
      if (!in_array($ext, ['jpg','jpeg','png','gif'])) {
        $ann_error = 'Only JPG, PNG, GIF allowed for images.';
      } else {
        $imageName = uniqid('img_', true) . '.' . $ext;
        move_uploaded_file($imageFile['tmp_name'], $uploadDir . $imageName);
      }
    }

    if (!$ann_error) {
      if ($ann_action === 'add') {
        $stmt = $conn->prepare("INSERT INTO announcements (id, title, content, image, date_posted, status) VALUES (?, ?, ?, ?, NOW(), ?)");
        if (!$stmt) {
          $ann_error = 'Database error: ' . $conn->error;
        } else {
          $newId = uniqid('ann_', true);
          $stmt->bind_param("sssss", $newId, $title, $content, $imageName, $status);
          if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            $stmt->close();
            
            // Log the action
            $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
            $logText = "Added announcement: $title (ID: $newId, affected rows: $affected)";
            $log->bind_param("ss", $admin_username, $logText);
            $log->execute();
            $log->close();
            
            // Success - redirect to list view
            header('Location: admin_dashboard.php?panel=announcements&msg=added');
            exit;
          } else {
            $ann_error = 'Failed to save announcement: ' . $stmt->error;
            $stmt->close();
          }
        }
      } elseif ($ann_action === 'edit' && $ann_id) {
        $stmtOld = $conn->prepare("SELECT image FROM announcements WHERE id=?");
        $stmtOld->bind_param("s", $ann_id);
        $stmtOld->execute();
        $resOld = $stmtOld->get_result()->fetch_assoc();
        $stmtOld->close();
        if ($imageName && $resOld['image'] && file_exists($uploadDir . $resOld['image'])) {
          unlink($uploadDir . $resOld['image']);
        } elseif (!$imageName) {
          $imageName = $resOld['image'] ?? null;
        }

        $stmt = $conn->prepare("UPDATE announcements SET title=?, content=?, image=?, status=?, date_posted=NOW() WHERE id=?");
        $stmt->bind_param("sssss", $title, $content, $imageName, $status, $ann_id);
        if ($stmt->execute()) {
          $stmt->close();
          $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
          $logText = "Edited announcement: $title (ID: $ann_id)";
          $log->bind_param("ss", $admin_username, $logText);
          $log->execute();
          $log->close();
          // Success - redirect to list view
          header('Location: admin_dashboard.php?panel=announcements&msg=updated');
          exit;
        } else {
          $ann_error = 'Failed to update announcement.';
        }
      }
    }
  }
} elseif ($ann_action === 'delete' && $ann_id) {
  $stmt = $conn->prepare("SELECT title, image FROM announcements WHERE id=?");
  $stmt->bind_param("s", $ann_id);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  if ($row && $row['image'] && file_exists($uploadDir . $row['image'])) {
    unlink($uploadDir . $row['image']);
  }
  $stmtDel = $conn->prepare("DELETE FROM announcements WHERE id=?");
  $stmtDel->bind_param("s", $ann_id);
  if ($stmtDel->execute()) {
    $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
    $logText = "Deleted announcement: {$row['title']} (ID: $ann_id)";
    $log->bind_param("ss", $admin_username, $logText);
    $log->execute();
    $log->close();
    
    // Check if this is an AJAX request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
      header('Content-Type: application/json');
      echo json_encode(['success' => true, 'message' => 'Announcement deleted successfully!']);
      exit;
    }
    
    header('Location: admin_dashboard.php?panel=announcements&msg=deleted');
  }
  exit;
} elseif ($ann_action === 'archive' && $ann_id) {
  $stmt = $conn->prepare("SELECT * FROM announcements WHERE id=?");
  $stmt->bind_param("s", $ann_id);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();

  if ($row) {
    $stmtArchive = $conn->prepare("INSERT INTO archived_announcements (id, title, content, image, date_posted, archived_at, status) VALUES (?, ?, ?, ?, ?, NOW(), ?)");
    $stmtArchive->bind_param("ssssss", $row['id'], $row['title'], $row['content'], $row['image'], $row['date_posted'], $row['status']);
    $stmtArchive->execute();

    $stmtDel = $conn->prepare("DELETE FROM announcements WHERE id=?");
    $stmtDel->bind_param("s", $ann_id);
    if ($stmtDel->execute()) {
      $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
      $logText = "Archived announcement: {$row['title']} (ID: $ann_id)";
      $log->bind_param("ss", $admin_username, $logText);
      $log->execute();
      $log->close();
      
      // Check if this is an AJAX request
      if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Announcement archived successfully!']);
        exit;
      }
      
      header('Location: admin_dashboard.php?panel=announcements&msg=archived');
    }
  }
  exit;
} elseif ($ann_action === 'restore' && $ann_id) {
  $stmt = $conn->prepare("SELECT * FROM archived_announcements WHERE id=?");
  $stmt->bind_param("s", $ann_id);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();

  if ($row) {
    $stmtRestore = $conn->prepare("INSERT INTO announcements (id, title, content, image, date_posted, status) VALUES (?, ?, ?, ?, NOW(), ?)");
    $stmtRestore->bind_param("sssss", $row['id'], $row['title'], $row['content'], $row['image'], $row['status']);
    $stmtRestore->execute();

    $stmtDel = $conn->prepare("DELETE FROM archived_announcements WHERE id=?");
    $stmtDel->bind_param("s", $ann_id);
    $stmtDel->execute();

    // Check if this is an AJAX request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
      header('Content-Type: application/json');
      echo json_encode(['success' => true, 'message' => 'Announcement restored successfully!']);
      exit;
    }
    
    header('Location: admin_dashboard.php?panel=announcements&ann_action=archives&msg=restored');
  }
  exit;
} elseif ($ann_action === 'delete_archive' && $ann_id) {
  $stmt = $conn->prepare("SELECT image FROM archived_announcements WHERE id=?");
  $stmt->bind_param("s", $ann_id);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();

  if ($row && $row['image'] && file_exists($uploadDir . $row['image'])) {
    unlink($uploadDir . $row['image']);
  }

  $stmtDel = $conn->prepare("DELETE FROM archived_announcements WHERE id=?");
  $stmtDel->bind_param("s", $ann_id);
  if ($stmtDel->execute()) {
    $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
    $logText = "Permanently deleted archived announcement (ID: $ann_id)";
    $log->bind_param("ss", $admin_username, $logText);
    $log->execute();
    $log->close();
    
    // Check if this is an AJAX request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
      header('Content-Type: application/json');
      echo json_encode(['success' => true, 'message' => 'Announcement permanently deleted!']);
      exit;
    }
    
    header('Location: admin_dashboard.php?panel=announcements&ann_action=archives&msg=deleted');
  }
  exit;
}

// Fetch lists
$anns = [];
if ($ann_action === 'list') {
  $sql = "SELECT * FROM announcements ORDER BY date_posted DESC";
  $result = $conn->query($sql);
  while ($result && $row = $result->fetch_assoc()) $anns[] = $row;
} elseif ($ann_action === 'archives') {
  $sql = "SELECT * FROM archived_announcements ORDER BY archived_at DESC";
  $result = $conn->query($sql);
  while ($result && $row = $result->fetch_assoc()) $anns[] = $row;
}

// If editing
$editing = null;
if ($ann_action === 'edit' && $ann_id) {
  $stmtEdit = $conn->prepare("SELECT * FROM announcements WHERE id=?");
  $stmtEdit->bind_param("s", $ann_id);
  $stmtEdit->execute();
  $editing = $stmtEdit->get_result()->fetch_assoc();
}

// ...existing dashboard queries below...
/* QUERY 1: Requests per Month */
$sql1 = "SELECT DATE_FORMAT(created_at, '%M %Y') AS month_name, COUNT(*) AS total
    FROM (
      SELECT created_at FROM certificate_requests
      UNION ALL
      SELECT created_at FROM archived_certificate_requests
    ) AS combined
    GROUP BY month_name
    ORDER BY MIN(created_at) ASC";
$result1 = $conn->query($sql1);
$months = [];
$monthCounts = [];
while ($row = $result1->fetch_assoc()) {
  $months[] = $row['month_name'];   
  $monthCounts[] = $row['total'];
}

/* QUERY 2: Requests per Certificate Type (Current Month) */
$sql2 = "SELECT certificate_type, COUNT(*) AS total 
    FROM (
      SELECT certificate_type FROM certificate_requests
      WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
        AND YEAR(created_at) = YEAR(CURRENT_DATE())
      UNION ALL
      SELECT certificate_type FROM archived_certificate_requests
      WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
        AND YEAR(created_at) = YEAR(CURRENT_DATE())
    ) AS combined
    GROUP BY certificate_type 
    ORDER BY total DESC";
$result2 = $conn->query($sql2);
$types = [];
$typeCounts = [];
while ($row = $result2->fetch_assoc()) {
  $types[] = $row['certificate_type'];
  $typeCounts[] = $row['total'];
}

/* QUERY 3: Incident Reports per Month (Active + Archived) */
$sql3 = "
  SELECT DATE_FORMAT(created_at, '%M %Y') AS month_name, COUNT(*) AS total
  FROM (
    SELECT created_at FROM incident_reports
    UNION ALL
    SELECT created_at FROM archived_incident_reports
  ) AS combined
  GROUP BY month_name
  ORDER BY MIN(created_at) ASC
";
$result3 = $conn->query($sql3);
$incidentMonths = [];
$incidentCounts = [];
while ($row = $result3->fetch_assoc()) {
  $incidentMonths[] = $row['month_name'];
  $incidentCounts[] = $row['total'];
}

/* QUERY 4: User Account Usage */
$sql4 = "SELECT 
      CASE 
        WHEN password IS NULL OR password = '' THEN 'login'
        ELSE 'not login'
      END AS status,
      COUNT(*) AS total_users
    FROM useraccounts
    GROUP BY status";
$result4 = $conn->query($sql4);
$labels = [];
$data = [];
$total = 0;
while ($row = $result4->fetch_assoc()) {
  $labels[] = ucfirst($row['status']);
  $data[] = $row['total_users'];
  $total += $row['total_users'];
}

/* QUERY 5: Residents Statistics */
$sql5 = "SELECT 
      SUM(CASE WHEN sex = 'Male' THEN 1 ELSE 0 END) AS male_count,
      SUM(CASE WHEN sex = 'Female' THEN 1 ELSE 0 END) AS female_count,
      SUM(CASE WHEN age >= 60 THEN 1 ELSE 0 END) AS senior_count,
      SUM(CASE WHEN is_pwd = 'Yes' THEN 1 ELSE 0 END) AS pwd_count,
      COUNT(DISTINCT household_id) AS household_count
    FROM residents
    WHERE household_id IS NOT NULL AND household_id != ''";
$result5 = $conn->query($sql5);
$resData = $result5->fetch_assoc();

/* QUERY 6: Incident Reports by Status */
$sql6 = "SELECT status, COUNT(*) as total
    FROM (
      SELECT status FROM incident_reports
      UNION ALL
      SELECT status FROM archived_incident_reports
    ) AS combined
    WHERE status NOT IN ('Priority', 'Resolved')
    GROUP BY status";
$result6 = $conn->query($sql6);
$incidentStatuses = [];
$incidentStatusCounts = [];
while ($row = $result6->fetch_assoc()) {
  $incidentStatuses[] = $row['status'];
  $incidentStatusCounts[] = $row['total'];
}

/* QUERY 7: Certificate Requests by Status */
$sql7 = "SELECT status, COUNT(*) AS total
    FROM (
      SELECT status FROM certificate_requests
      UNION ALL
      SELECT status FROM archived_certificate_requests
    ) AS combined
    WHERE status NOT IN ('Printed', 'Rejected')
    GROUP BY status";
$result7 = $conn->query($sql7);
$certStatuses = [];
$certStatusCounts = [];
while ($row = $result7->fetch_assoc()) {
  $certStatuses[] = $row['status'];
  $certStatusCounts[] = $row['total'];
}

/* QUERY 8: Top 5 Incidents by Type (Current Month) - Supports English & Tagalog */
$sql8 = "SELECT 
    CASE 
      -- Fire related
      WHEN LOWER(incident_type) LIKE '%fire%' OR LOWER(incident_type) LIKE '%sunog%' THEN 'Fire Incident'
      WHEN LOWER(incident_type) LIKE '%explosion%' OR LOWER(incident_type) LIKE '%pagsabog%' THEN 'Explosion'
      WHEN LOWER(incident_type) LIKE '%gas leak%' OR LOWER(incident_type) LIKE '%tagas ng gas%' THEN 'Gas Leak'
      WHEN LOWER(incident_type) LIKE '%chemical spill%' OR LOWER(incident_type) LIKE '%pagtagas ng kemikal%' THEN 'Chemical Spill'
      
      -- Violence/Crime
      WHEN LOWER(incident_type) LIKE '%assault%' OR LOWER(incident_type) LIKE '%pananakit%' THEN 'Assault'
      WHEN LOWER(incident_type) LIKE '%homicide%' OR LOWER(incident_type) LIKE '%murder%' OR LOWER(incident_type) LIKE '%pagpatay%' THEN 'Homicide/Murder'
      WHEN LOWER(incident_type) LIKE '%shooting%' OR LOWER(incident_type) LIKE '%barilan%' THEN 'Shooting'
      WHEN LOWER(incident_type) LIKE '%stabbing%' OR LOWER(incident_type) LIKE '%saksakan%' THEN 'Stabbing'
      WHEN LOWER(incident_type) LIKE '%violent%' OR LOWER(incident_type) LIKE '%karahasan%' THEN 'Violence'
      WHEN LOWER(incident_type) LIKE '%domestic violence%' OR LOWER(incident_type) LIKE '%karahasan sa tahanan%' THEN 'Domestic Violence'
      WHEN LOWER(incident_type) LIKE '%kidnapping%' OR LOWER(incident_type) LIKE '%pagdukot%' THEN 'Kidnapping'
      WHEN LOWER(incident_type) LIKE '%child abuse%' OR LOWER(incident_type) LIKE '%pang-aabuso sa bata%' THEN 'Child Abuse'
      WHEN LOWER(incident_type) LIKE '%sexual assault%' OR LOWER(incident_type) LIKE '%panghahalay%' THEN 'Sexual Assault'
      
      -- Medical/Emergency
      WHEN LOWER(incident_type) LIKE '%medical%' OR LOWER(incident_type) LIKE '%medikal%' THEN 'Medical Emergency'
      WHEN LOWER(incident_type) LIKE '%emergency%' OR LOWER(incident_type) LIKE '%emerhensiya%' THEN 'Emergency'
      WHEN LOWER(incident_type) LIKE '%heart attack%' OR LOWER(incident_type) LIKE '%atake sa puso%' THEN 'Heart Attack'
      WHEN LOWER(incident_type) LIKE '%stroke%' THEN 'Stroke'
      WHEN LOWER(incident_type) LIKE '%unconscious%' OR LOWER(incident_type) LIKE '%walang malay%' THEN 'Unconscious Person'
      WHEN LOWER(incident_type) LIKE '%electrocution%' OR LOWER(incident_type) LIKE '%kuryente%' THEN 'Electrocution'
      
      -- Accidents
      WHEN LOWER(incident_type) LIKE '%car accident%' OR LOWER(incident_type) LIKE '%aksidente sa sasakyan%' THEN 'Car Accident'
      WHEN LOWER(incident_type) LIKE '%hit and run%' OR LOWER(incident_type) LIKE '%banggaan%' THEN 'Hit and Run'
      WHEN LOWER(incident_type) LIKE '%serious injury%' OR LOWER(incident_type) LIKE '%malubhang pinsala%' THEN 'Serious Injury'
      WHEN LOWER(incident_type) LIKE '%minor accident%' THEN 'Minor Accident'
      WHEN LOWER(incident_type) LIKE '%accident%' OR LOWER(incident_type) LIKE '%aksidente%' THEN 'Accident/Injury'
      
      -- Property crimes
      WHEN LOWER(incident_type) LIKE '%theft%' OR LOWER(incident_type) LIKE '%pagnanakaw%' THEN 'Theft'
      WHEN LOWER(incident_type) LIKE '%burglary%' OR LOWER(incident_type) LIKE '%pagnanakaw sa bahay%' THEN 'Burglary'
      WHEN LOWER(incident_type) LIKE '%robbery%' OR LOWER(incident_type) LIKE '%panghoholdap%' THEN 'Robbery'
      WHEN LOWER(incident_type) LIKE '%shoplifting%' OR LOWER(incident_type) LIKE '%pandurukot%' THEN 'Shoplifting'
      WHEN LOWER(incident_type) LIKE '%vandalism%' OR LOWER(incident_type) LIKE '%paninira%' THEN 'Vandalism'
      WHEN LOWER(incident_type) LIKE '%damage to property%' OR LOWER(incident_type) LIKE '%property damage%' OR LOWER(incident_type) LIKE '%pinsala sa ari-arian%' OR LOWER(incident_type) LIKE '%pinsala%' THEN 'Property Damage'
      
      -- Natural disasters
      WHEN LOWER(incident_type) LIKE '%earthquake%' OR LOWER(incident_type) LIKE '%lindol%' THEN 'Earthquake'
      WHEN LOWER(incident_type) LIKE '%flood%' OR LOWER(incident_type) LIKE '%baha%' THEN 'Flood'
      WHEN LOWER(incident_type) LIKE '%natural disaster%' OR LOWER(incident_type) LIKE '%kalikasan%' THEN 'Natural Disaster'
      WHEN LOWER(incident_type) LIKE '%building collapse%' OR LOWER(incident_type) LIKE '%pagguho ng gusali%' THEN 'Building Collapse'
      
      -- Public disturbances
      WHEN LOWER(incident_type) LIKE '%public disturbance%' OR LOWER(incident_type) LIKE '%istorbo sa publiko%' THEN 'Public Disturbance'
      WHEN LOWER(incident_type) LIKE '%public nuisance%' OR LOWER(incident_type) LIKE '%istorbo%' THEN 'Public Nuisance'
      WHEN LOWER(incident_type) LIKE '%noise complaint%' OR LOWER(incident_type) LIKE '%reklamo sa ingay%' THEN 'Noise Complaint'
      WHEN LOWER(incident_type) LIKE '%noise%' OR LOWER(incident_type) LIKE '%ingay%' THEN 'Noise'
      WHEN LOWER(incident_type) LIKE '%disorderly conduct%' OR LOWER(incident_type) LIKE '%gulo sa publiko%' THEN 'Disorderly Conduct'
      
      -- Harassment/Threats
      WHEN LOWER(incident_type) LIKE '%harassment%' OR LOWER(incident_type) LIKE '%pang-aasar%' THEN 'Harassment'
      WHEN LOWER(incident_type) LIKE '%threat%' OR LOWER(incident_type) LIKE '%banta%' THEN 'Threat'
      WHEN LOWER(incident_type) LIKE '%verbal abuse%' THEN 'Verbal Abuse'
      
      -- Missing/Lost
      WHEN LOWER(incident_type) LIKE '%missing person%' OR LOWER(incident_type) LIKE '%nawalang tao%' THEN 'Missing Person'
      WHEN LOWER(incident_type) LIKE '%lost item%' OR LOWER(incident_type) LIKE '%nawalang gamit%' THEN 'Lost Item'
      
      -- Fraud/Scams
      WHEN LOWER(incident_type) LIKE '%fraud%' OR LOWER(incident_type) LIKE '%panlilinlang%' THEN 'Fraud'
      WHEN LOWER(incident_type) LIKE '%scam%' OR LOWER(incident_type) LIKE '%panloloko%' THEN 'Scam'
      WHEN LOWER(incident_type) LIKE '%identity theft%' THEN 'Identity Theft'
      
      -- Trespassing
      WHEN LOWER(incident_type) LIKE '%trespassing%' OR LOWER(incident_type) LIKE '%panggagambala%' OR LOWER(incident_type) LIKE '%panggugulo%' THEN 'Trespassing'
      WHEN LOWER(incident_type) LIKE '%unauthorized entry%' OR LOWER(incident_type) LIKE '%hindi awtorisadong pagpasok%' THEN 'Unauthorized Entry'
      
      -- Traffic/Driving
      WHEN LOWER(incident_type) LIKE '%illegal parking%' OR LOWER(incident_type) LIKE '%illegal na paradahan%' THEN 'Illegal Parking'
      WHEN LOWER(incident_type) LIKE '%reckless driving%' OR LOWER(incident_type) LIKE '%pabaya sa pagmamaneho%' THEN 'Reckless Driving'
      WHEN LOWER(incident_type) LIKE '%jaywalking%' OR LOWER(incident_type) LIKE '%tumawid sa maling daan%' THEN 'Jaywalking'
      
      -- Public intoxication
      WHEN LOWER(incident_type) LIKE '%public intoxication%' OR LOWER(incident_type) LIKE '%pag-inom sa publiko%' THEN 'Public Intoxication'
      WHEN LOWER(incident_type) LIKE '%drunk in public%' OR LOWER(incident_type) LIKE '%lasing sa publiko%' THEN 'Drunk in Public'
      
      -- Minor violations
      WHEN LOWER(incident_type) LIKE '%loitering%' OR LOWER(incident_type) LIKE '%paglalaboy%' THEN 'Loitering'
      WHEN LOWER(incident_type) LIKE '%littering%' OR LOWER(incident_type) LIKE '%pagtatapon ng basura%' THEN 'Littering'
      WHEN LOWER(incident_type) LIKE '%illegal dumping%' OR LOWER(incident_type) LIKE '%basurang itinatapon%' THEN 'Illegal Dumping'
      WHEN LOWER(incident_type) LIKE '%illegal posting%' OR LOWER(incident_type) LIKE '%illegal na poster%' THEN 'Illegal Posting'
      WHEN LOWER(incident_type) LIKE '%curfew violation%' OR LOWER(incident_type) LIKE '%labag sa curfew%' THEN 'Curfew Violation'
      WHEN LOWER(incident_type) LIKE '%unauthorized selling%' OR LOWER(incident_type) LIKE '%walang permit na tindero%' THEN 'Unauthorized Selling'
      WHEN LOWER(incident_type) LIKE '%graffiti%' THEN 'Graffiti'
      
      -- Animal complaints
      WHEN LOWER(incident_type) LIKE '%animal complaint%' OR LOWER(incident_type) LIKE '%reklamo sa hayop%' THEN 'Animal Complaint'
      WHEN LOWER(incident_type) LIKE '%barking dog%' OR LOWER(incident_type) LIKE '%tahol ng aso%' THEN 'Barking Dog'
      
      -- Neighborhood
      WHEN LOWER(incident_type) LIKE '%neighborhood dispute%' OR LOWER(incident_type) LIKE '%alitan sa kapitbahay%' THEN 'Neighborhood Dispute'
      
      -- Generic/Other
      WHEN LOWER(incident_type) LIKE '%minor%' OR LOWER(incident_type) LIKE '%maliit%' THEN 'Minor'
      WHEN LOWER(incident_type) LIKE '%other%' OR LOWER(incident_type) LIKE '%iba pa%' THEN 'Other'
      
      ELSE incident_type
    END AS normalized_type,
    COUNT(*) AS total
    FROM (
      SELECT incident_type FROM incident_reports
      WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
        AND YEAR(created_at) = YEAR(CURRENT_DATE())
      UNION ALL
      SELECT incident_type FROM archived_incident_reports
      WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
        AND YEAR(created_at) = YEAR(CURRENT_DATE())
    ) AS combined
    GROUP BY normalized_type
    ORDER BY total DESC
    LIMIT 5";
$result8 = $conn->query($sql8);
$topIncidentTypes = [];
$topIncidentCounts = [];
while ($row = $result8->fetch_assoc()) {
  $topIncidentTypes[] = $row['normalized_type'];
  $topIncidentCounts[] = $row['total'];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
  <title>Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
  <link rel="stylesheet" href="https://unpkg.com/lucide-static@latest/dist/lucide.css">
  <script src="https://unpkg.com/lucide@latest"></script>
  <style>
    body { 
      font-family: 'Segoe UI', sans-serif; 
      margin: 0; 
      background: #f4f6f9; 
      display: flex;
      overflow-x: hidden;
      min-height: 100vh;
    }
    * {
      box-sizing: border-box;
    }
    /* --- Modern Sidebar --- */
    .sidebar {
      width: 280px;
      background: linear-gradient(160deg, #14ad0f 0%, #43e97b 100%);
      color: #fff;
      height: 100vh;
      position: fixed;
      top: 0; left: 0;
      padding: 28px 16px 20px 16px;
      display: flex;
      flex-direction: column;
      gap: 6px;
      border-right: none;
      z-index: 1002;
      box-shadow: 0 10px 40px rgba(20,173,15,0.2), 0 2px 8px rgba(67,233,123,0.15);
      transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.3s ease;
      backdrop-filter: blur(10px);
      border-radius: 0 24px 24px 0;
      overflow-x: visible;
      overflow-y: auto;
      animation: sidebarSlideIn 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    @keyframes sidebarSlideIn {
      0% { 
        opacity: 0; 
        transform: translateX(-60px) scale(0.95); 
      }
      60% {
        transform: translateX(10px) scale(1.02);
      }
      100% { 
        opacity: 1; 
        transform: translateX(0) scale(1); 
      }
    }
    .sidebar h2 {
      font-size: 1.45rem;
      margin-bottom: 32px;
      padding-bottom: 16px;
      font-weight: 700;
      text-align: center;
      letter-spacing: 1.2px;
      color: #fff;
  background: none;
  /* standard properties alongside vendor-prefixed ones for compatibility */
  background-clip: border-box;
  -webkit-background-clip: border-box;
  color: inherit; /* fallback for text color */
  -webkit-text-fill-color: unset;
      filter: drop-shadow(0 3px 10px rgba(0,0,0,0.2));
      position: relative;
      z-index: 1;
      border-bottom: 2px solid rgba(255,255,255,0.2);
      animation: fadeInDown 0.6s ease-out 0.2s both;
    }
    @keyframes fadeInDown {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    .nav-link {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 16px;
      border-radius: 14px;
      color: #fff;
      text-decoration: none;
      font-weight: 600;
      font-size: 0.98rem;
      margin-bottom: 6px;
      background: rgba(255,255,255,0.08);
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      transition: 
        all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
      position: relative;
      overflow: hidden;
      border: 1px solid rgba(255,255,255,0.1);
      cursor: pointer;
      animation: menuItemFadeIn 0.5s ease-out backwards;
    }
    @keyframes menuItemFadeIn {
      from {
        opacity: 0;
        transform: translateX(-20px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }
    .nav-link:nth-child(1) { animation-delay: 0.1s; }
    .nav-link:nth-child(2) { animation-delay: 0.15s; }
    .nav-link:nth-child(3) { animation-delay: 0.2s; }
    .nav-link:nth-child(4) { animation-delay: 0.25s; }
    .nav-link:nth-child(5) { animation-delay: 0.3s; }
    .nav-link:nth-child(6) { animation-delay: 0.35s; }
    .nav-link:nth-child(7) { animation-delay: 0.4s; }
    .nav-link:nth-child(8) { animation-delay: 0.45s; }
    .nav-link:nth-child(9) { animation-delay: 0.5s; }
    .nav-link:before {
      content: '';
      position: absolute;
      left: 0; top: 0; bottom: 0; right: 0;
      background: linear-gradient(135deg, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0.05) 100%);
      opacity: 0;
      transition: opacity 0.35s ease;
      z-index: 0;
      border-radius: 14px;
    }
    .nav-link:after {
      content: '';
      position: absolute;
      left: 0;
      top: 50%;
      transform: translateY(-50%);
      width: 4px;
      height: 0;
      background: #fff;
      border-radius: 0 4px 4px 0;
      transition: height 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
      z-index: 1;
    }
    .nav-link:hover, .nav-link.active {
      background: #fff !important;
      color: #14ad0f !important;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25), 0 2px 8px rgba(20,173,15,0.2);
      transform: translateX(8px) scale(1.03);
      border: 1px solid rgba(20,173,15,0.2);
    }
    .nav-link:hover:after, .nav-link.active:after {
      height: 70%;
    }
    .nav-link:hover:before, .nav-link.active:before {
      opacity: 0;
    }
    .nav-link:hover i, .nav-link.active i {
      color: #14ad0f !important;
      filter: drop-shadow(0 2px 4px rgba(20,173,15,0.3));
      transform: scale(1.1) rotate(5deg);
    }
    .nav-link i {
      margin-right: 0;
      vertical-align: middle;
      width: 20px;
      height: 20px;
      z-index: 2;
      filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
      transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .logout {
      margin-top: auto;
      padding: 14px 18px;
      border-radius: 16px;
      background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
      color: #fff;
      text-align: center;
      text-decoration: none;
      font-size: 1.05rem;
      font-weight: 700;
      box-shadow: 0 4px 16px rgba(220, 53, 69, 0.3);
      transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
      letter-spacing: 0.8px;
      border: 2px solid rgba(255,255,255,0.2);
      outline: none;
      cursor: pointer;
      position: relative;
      overflow: hidden;
    }
    .logout:before {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 0;
      height: 0;
      border-radius: 50%;
      background: rgba(255,255,255,0.2);
      transform: translate(-50%, -50%);
      transition: width 0.6s ease, height 0.6s ease;
    }
    .logout:hover:before {
      width: 300px;
      height: 300px;
    }
    .logout:hover {
      background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
      box-shadow: 0 8px 24px rgba(220, 53, 69, 0.5);
      transform: translateY(-2px) scale(1.05);
      border-color: rgba(255,255,255,0.4);
    }
    .main-content {
      margin-left: 280px;
      padding: 25px;
      flex: 1;
      transition: margin-left 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    /* --- Header Styles --- */
    .header {
      background: #fff;
      padding: 15px 20px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      margin-bottom: 20px;
    }
    .datetime {
      font-size: 1rem;
      font-weight: 600;
      color: #333;
      text-align: center;
    }
    /* --- Sidebar Scrollbar --- */
    .sidebar {
      scrollbar-width: thin;
      scrollbar-color: #43e97b #1976d2;
    }
    .sidebar::-webkit-scrollbar {
      width: 7px;
      background: #14ad0f;
      border-radius: 8px;
    }
    .sidebar::-webkit-scrollbar-thumb {
      background: #43e97b;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(20,173,15,0.13);
    }
    /* --- Responsive Sidebar --- */
    .burger-btn {
      display: none;
      position: fixed;
      top: 18px;
      left: 18px;
      z-index: 1100;
      background: #fff;
      border: none;
      border-radius: 12px;
      box-shadow: 0 2px 12px rgba(25,118,210,0.13);
      width: 48px;
      height: 48px;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: background 0.2s, box-shadow 0.2s;
    }
    .burger-btn:active { background: #e0e0e0; }
    .burger-icon {
      width: 30px;
      height: 30px;
      display: block;
      margin: auto;
    }
    @media (max-width: 900px) {
      .sidebar {
        transform: translateX(-110%);
        position: fixed;
        left: 0; top: 0; height: 100vh;
        box-shadow: 2px 0 24px rgba(25,118,210,0.13);
        border-radius: 0 22px 22px 0;
        overflow-y: auto;
        overflow-x: hidden;
        z-index: 1002;
      }
      .sidebar.active {
        transform: translateX(0);
      }
      .main-content {
        margin-left: 0 !important;
        padding: 18px 6px;
      }
      .burger-btn {
        display: flex;
      }
      .sidebar-backdrop {
        display: none;
        position: fixed;
        z-index: 1001;
        left: 0; top: 0; width: 100vw; height: 100vh;
        background: rgba(25,118,210,0.13);
        transition: opacity 0.2s;
        backdrop-filter: blur(2px);
      }
      .sidebar.active ~ .sidebar-backdrop {
        display: block;
      }
    }
    /* --- Sidebar Glow Effect --- */
    .sidebar:after {
      content: '';
      position: absolute;
      left: 0; right: 0; bottom: 0;
      height: 60px;
      background: linear-gradient(0deg,rgba(20,173,15,0.18),transparent);
      pointer-events: none;
      z-index: 2;
    }
    
    /* Chat Badge Animation */
    @keyframes badgePulse {
      0%, 100% { 
        transform: translateY(-50%) scale(1);
        box-shadow: 0 3px 12px rgba(255,71,87,0.4);
      }
      50% { 
        transform: translateY(-50%) scale(1.08);
        box-shadow: 0 4px 16px rgba(255,71,87,0.6);
      }
    }
    
    .chat-badge {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* --- Submenu Styles --- */
    #residentsSubMenu {
      margin-left: 20px;
      margin-top: 4px;
      padding-left: 12px;
      border-left: 2px solid rgba(255,255,255,0.2);
      animation: submenuSlideDown 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    
    @keyframes submenuSlideDown {
      from {
        opacity: 0;
        max-height: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        max-height: 500px;
        transform: translateY(0);
      }
    }
    
    #residentsSubMenu .nav-link {
      padding: 10px 14px;
      font-size: 0.92rem;
      margin-bottom: 4px;
      background: rgba(255,255,255,0.05);
      gap: 10px;
    }
    
    #residentsSubMenu .nav-link:hover {
      background: rgba(255,255,255,0.95) !important;
      transform: translateX(6px) scale(1.02);
      margin-left: 4px;
    }
    
    #residentsArrow {
      transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
      display: inline-block;
      font-size: 0.7rem;
    }

    /* --- Reports Panel Custom Styles --- */
#panel-reports {
  max-width: 100%;
  margin: 0;
  padding: 32px 24px;
  background: linear-gradient(160deg, #e8f5e9 0%, #c8e6c9 100%);
  min-height: calc(100vh - 80px);
}
#panel-reports .grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
  max-width: 1600px;
  margin: 0 auto;
}
@media (max-width: 1400px) {
  #panel-reports .grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
  }
}
@media (max-width: 900px) {
  #panel-reports {
    padding: 16px 12px;
  }
  #panel-reports .grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }
}
#panel-reports .modern-card {
  padding: 24px;
  border-radius: 16px;
  font-size: 1rem;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.3);
}
#panel-reports .card-graph-header h3 {
  font-size: 1.15rem;
  font-weight: 600;
}
#panel-reports canvas {
  max-height: 320px !important;
  height: 280px !important;
}

/* ========================================
   COMPREHENSIVE RESPONSIVE STYLES
   ======================================== */

/* Mobile-first responsive adjustments */
@media (max-width: 768px) {
  /* Body adjustments */
  body {
    flex-direction: column;
  }

  /* Main content padding */
  .main-content {
    padding: 15px 10px !important;
    margin-left: 0 !important;
    width: 100%;
    max-width: 100vw;
  }

  /* Header adjustments */
  .header {
    padding: 10px;
    text-align: center;
  }

  .datetime {
    font-size: 0.85rem;
    word-break: break-word;
  }

  /* All panel sections */
  .panel-section {
    padding: 10px !important;
    max-width: 100% !important;
    margin: 0 !important;
    max-height: calc(100vh - 80px);
    overflow-y: auto;
  }

  /* Headings */
  h2, .h2 {
    font-size: 1.4rem !important;
    text-align: center;
    word-wrap: break-word;
  }

  h3, .h3 {
    font-size: 1.2rem !important;
  }

  h4, .h4 {
    font-size: 1rem !important;
  }

  /* Cards */
  .card {
    margin-bottom: 15px;
    border-radius: 12px;
  }

  .card-body {
    padding: 12px !important;
  }

  .card-header {
    padding: 10px 12px !important;
    font-size: 1rem !important;
  }

  /* Tables - Make them responsive */
  .table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  table {
    font-size: 0.75rem !important;
    min-width: 100%;
  }

  table th,
  table td {
    padding: 6px 4px !important;
    font-size: 0.75rem !important;
    white-space: nowrap;
  }

  /* Buttons */
  .btn {
    font-size: 0.8rem !important;
    padding: 6px 10px !important;
    margin: 2px !important;
  }

  .btn-sm {
    font-size: 0.7rem !important;
    padding: 4px 8px !important;
  }

  /* Forms */
  .form-group {
    margin-bottom: 12px;
  }

  .form-label,
  label {
    font-size: 0.9rem !important;
    margin-bottom: 5px;
  }

  .form-control,
  .form-select,
  input,
  select,
  textarea {
    font-size: 0.9rem !important;
    padding: 8px 10px !important;
  }

  /* Button groups */
  .d-flex {
    flex-wrap: wrap !important;
    gap: 8px;
  }

  .justify-content-between {
    justify-content: center !important;
  }

  /* Action buttons in tables */
  .text-center.d-flex {
    flex-direction: column !important;
    gap: 4px !important;
  }

  /* Modals */
  .modal-dialog {
    margin: 10px;
    max-width: calc(100% - 20px) !important;
  }

  .modal-body {
    padding: 15px !important;
  }

  .modal-header {
    padding: 12px 15px !important;
  }

  .modal-footer {
    padding: 10px 15px !important;
    flex-wrap: wrap;
  }

  /* Container adjustments */
  .container {
    padding-left: 10px !important;
    padding-right: 10px !important;
    max-width: 100% !important;
  }

  /* Grid layouts */
  .row {
    margin-left: -5px !important;
    margin-right: -5px !important;
  }

  .col,
  [class*="col-"] {
    padding-left: 5px !important;
    padding-right: 5px !important;
  }

  /* Welcome panel */
  #panel-welcome {
    padding: 20px 15px !important;
    max-width: 95% !important;
  }

  #panel-welcome h1 {
    font-size: 1.5rem !important;
  }

  #panel-welcome p {
    font-size: 0.9rem !important;
  }

  /* Officials panel */
  #panel-officials .officials-grid {
    grid-template-columns: 1fr !important;
    gap: 15px !important;
  }

  /* Announcements panel */
  #panel-announcements .card {
    margin-bottom: 15px;
  }

  #panel-announcements img {
    max-width: 100%;
    height: auto;
  }

  /* Certificates panel */
  #panel-certificates .table-responsive {
    font-size: 0.75rem;
  }
  
  #panel-certificates .table-responsive::-webkit-scrollbar {
    width: 8px;
    height: 8px;
  }
  
  #panel-certificates .table-responsive::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
  }
  
  #panel-certificates .table-responsive::-webkit-scrollbar-thumb {
    background: #0d6efd;
    border-radius: 10px;
  }
  
  #panel-certificates .table-responsive::-webkit-scrollbar-thumb:hover {
    background: #0b5ed7;
  }
  
  #panel-certificates .table thead th {
    border-bottom: 2px solid #dee2e6;
  }

  /* Incidents panel */
  #panel-incidents .table-responsive {
    font-size: 0.75rem;
  }

  #panel-incidents .btn-group {
    flex-direction: column;
    width: 100%;
  }

  /* Residents panel */
  #panel-view-residents .table-responsive,
  #panel-add-residents .table-responsive {
    font-size: 0.75rem;
  }

  /* Edit forms */
  #panel-edit-resident,
  #panel-register-admin,
  #panel-reset-pass {
    max-width: 100% !important;
    padding: 10px !important;
  }

  /* Admin logs */
  #panel-admin-logs .table-responsive {
    font-size: 0.7rem;
  }

  /* Suggestions panel */
  #panel-suggestions .card {
    margin-bottom: 15px;
  }

  /* Households panel */
  #panel-households .table-responsive {
    font-size: 0.75rem;
  }

  /* Recycle bin */
  #panel-recycle-bin .table-responsive {
    font-size: 0.75rem;
  }

  /* Manage admin panel */
  #panel-manage-admin .table-responsive {
    font-size: 0.75rem;
  }

  /* Search and filter inputs */
  input[type="search"],
  input[type="text"].form-control {
    width: 100% !important;
    max-width: 100% !important;
  }

  /* Date inputs */
  input[type="date"] {
    width: 100% !important;
  }

  /* Alert messages */
  .alert {
    font-size: 0.85rem !important;
    padding: 10px !important;
    margin-bottom: 10px !important;
  }

  /* Pagination */
  .pagination {
    font-size: 0.8rem;
    flex-wrap: wrap;
    justify-content: center;
  }

  .page-link {
    padding: 5px 10px !important;
  }

  /* Badge adjustments */
  .badge {
    font-size: 0.7rem !important;
    padding: 4px 8px !important;
  }

  /* Image previews */
  img {
    max-width: 100%;
    height: auto;
  }

  /* Iframe (for admin chats) */
  #panel-admin-chats iframe {
    height: 80vh !important;
  }
  
  /* Make admin-chats panel non-scrollable */
  #panel-admin-chats {
    overflow: hidden !important;
    height: calc(100vh - 50px) !important;
    padding: 0 !important;
    margin: 0 !important;
  }
  
  #panel-admin-chats iframe {
    height: 100% !important;
    overflow: hidden !important;
  }

  /* File upload buttons */
  input[type="file"] {
    font-size: 0.8rem !important;
  }

  /* Action button groups in cards */
  .card-body .d-flex.gap-2 {
    flex-direction: column !important;
    gap: 8px !important;
  }

  /* Status badges in tables */
  td .badge {
    display: inline-block;
    margin: 2px 0;
  }

  /* Dropdown menus */
  .dropdown-menu {
    font-size: 0.85rem;
  }

  /* Text truncation for long content */
  .text-truncate {
    max-width: 150px;
  }
}

/* Tablet adjustments (768px - 1024px) */
@media (min-width: 769px) and (max-width: 1024px) {
  .main-content {
    padding: 20px 15px;
  }

  table th,
  table td {
    font-size: 0.85rem !important;
    padding: 8px 6px !important;
  }

  .btn {
    font-size: 0.85rem !important;
  }

  .card-body {
    padding: 15px !important;
  }

  h2 {
    font-size: 1.6rem !important;
  }

  .panel-section {
    padding: 15px !important;
  }
}

/* Extra small devices (phones in portrait, less than 576px) */
@media (max-width: 576px) {
  body {
    font-size: 14px;
  }

  .burger-btn {
    width: 42px;
    height: 42px;
    top: 12px;
    left: 12px;
  }

  .burger-icon {
    width: 24px;
    height: 24px;
  }

  /* Make all panels full width */
  .panel-section {
    width: 100% !important;
    max-width: 100% !important;
    margin: 0 !important;
    padding: 8px !important;
  }

  /* Stack all flex items */
  .d-flex {
    flex-direction: column !important;
  }

  /* Exception for specific button groups that should stay inline */
  .pagination,
  .btn-group,
  .modal-footer .d-flex {
    flex-direction: row !important;
    flex-wrap: wrap !important;
  }

  /* Full width buttons */
  .btn,
  button {
    width: 100% !important;
    margin-bottom: 8px !important;
  }

  /* Exception for buttons in button groups */
  .btn-group .btn,
  .pagination .btn,
  .modal-footer .btn {
    width: auto !important;
    flex: 1;
  }

  /* Smaller text in tables */
  table {
    font-size: 0.65rem !important;
  }

  table th,
  table td {
    padding: 4px 2px !important;
    font-size: 0.65rem !important;
  }

  /* Compact forms */
  .form-control,
  .form-select {
    font-size: 0.85rem !important;
    padding: 6px 8px !important;
  }

  /* Smaller headings */
  h1 {
    font-size: 1.4rem !important;
  }

  h2 {
    font-size: 1.2rem !important;
  }

  h3 {
    font-size: 1rem !important;
  }

  /* Compact cards */
  .card {
    border-radius: 8px;
  }

  .card-body {
    padding: 10px !important;
  }

  .card-header {
    padding: 8px 10px !important;
    font-size: 0.9rem !important;
  }

  /* Modal adjustments */
  .modal-dialog {
    margin: 5px;
    max-width: calc(100% - 10px) !important;
  }

  /* Smaller badges */
  .badge {
    font-size: 0.65rem !important;
    padding: 3px 6px !important;
  }

  /* Compact alerts */
  .alert {
    font-size: 0.8rem !important;
    padding: 8px !important;
  }
}

/* Landscape phone adjustments */
@media (max-width: 900px) and (orientation: landscape) {
  .sidebar {
    width: 240px;
  }

  .sidebar h2 {
    font-size: 1.2rem;
    margin-bottom: 20px;
    padding-bottom: 12px;
  }

  .nav-link {
    padding: 8px 12px;
    font-size: 0.9rem;
    gap: 10px;
  }
}

/* Print styles */
@media print {
  .sidebar,
  .burger-btn,
  .btn,
  button,
  .modal,
  .alert {
    display: none !important;
  }

  .main-content {
    margin-left: 0 !important;
    padding: 0 !important;
  }

  table {
    font-size: 10pt;
  }

  .panel-section {
    page-break-inside: avoid;
  }
}
  </style>
</head>
<body>
  <!-- Login Success Modal -->
  <div id="login-success-modal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(30,30,40,0.6); align-items:center; justify-content:center; z-index:999999; backdrop-filter:blur(8px);">
    <div class="modal-content" onclick="event.stopPropagation()" style="background:rgba(255,255,255,0.98); border-radius:22px; box-shadow:0 8px 32px rgba(0,0,0,0.18); padding:2.5rem 2rem; width:500px; max-width:95%; text-align:center; animation:scaleInModal 0.35s cubic-bezier(.4,2,.6,1); position:relative; z-index:1000000;">
      <div style="margin-bottom:1.5rem;">
        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="display:inline-block;">
          <circle cx="12" cy="12" r="10" fill="#28a745" opacity="0.2"/>
          <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" fill="#28a745"/>
        </svg>
      </div>
      <h3 style="font-size:1.8rem; font-weight:700; color:#28a745; margin-bottom:0.8rem; letter-spacing:0.5px;">Login Successful!</h3>
      <p style="color:#666; font-size:1rem; margin-bottom:1.5rem;">Welcome back, Admin! You have successfully logged in.</p>
      <button onclick="closeLoginSuccessModal()" style="background:#28a745; color:#fff; padding:12px 32px; border-radius:10px; border:none; font-size:1rem; font-weight:600; cursor:pointer; transition:all 0.3s; position:relative; z-index:1000001; box-shadow:0 4px 12px rgba(40,167,69,0.3);">Continue</button>
    </div>
  </div>
  <style>
    @keyframes scaleInModal {
      from { transform: scale(0.92); opacity: 0; }
      to { transform: scale(1); opacity: 1; }
    }
  </style>

  <!-- Burger Button for Mobile -->
  <audio id="chatNotifAudio" src="ChatNotif.mp3" preload="auto"></audio>
  <audio id="welcomeAudio" src="welcom.mp3" preload="auto"></audio>
<button class="burger-btn" id="burgerBtn" aria-label="Open sidebar">
  <svg class="burger-icon" viewBox="0 0 32 32">
    <rect y="6" width="32" height="4" rx="2" fill="#1976d2"/>
    <rect y="14" width="32" height="4" rx="2" fill="#1976d2"/>
    <rect y="22" width="32" height="4" rx="2" fill="#1976d2"/>
  </svg>
</button>
<!-- Sidebar -->
<div class="sidebar" id="sidebar">
  <style>
    /* Make sidebar a column flex container so logout can stick to bottom and enable scrolling */
    .sidebar {
      display: flex;
      flex-direction: column;
      height: 100vh; /* full viewport height */
      box-sizing: border-box;
      overflow-y: auto; /* enable vertical scroll when needed */
      padding: 20px 18px; /* comfortable padding */
      gap: 8px;
      -webkit-overflow-scrolling: touch;
    }

    /* Slightly smaller/tighter nav link sizes (user requested) */
    .sidebar .nav-link {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 14px 16px;
      font-size: 1.04rem; /* a bit smaller */
      min-height: 50px; /* slightly reduced touch target */
      border-radius: 12px;
      color: #fff;
      background: transparent;
      transition: all 0.16s ease;
      cursor: pointer;
      text-align: left;
      width: 100%;
      box-sizing: border-box;
      font-weight: 700;
      line-height: 1.08;  
    }

    .sidebar .nav-link:hover {
      transform: translateY(-1px);
      box-shadow: 0 6px 18px rgba(0,0,0,0.12);
      background: rgba(255,255,255,0.035);
    }

    /* Slightly smaller icon size */
    .sidebar svg, .sidebar i svg {
      width: 20px !important;
      height: 20px !important;
      flex: 0 0 20px;
      color: inherit;
    }

    /* Submenu buttons slightly smaller as well */
    #residentsSubMenu .nav-link {
      padding: 10px 12px;
      font-size: 0.98rem;
      width: 88%;
      min-height: 44px;
    }

    /* Slightly smaller logout button */
    .sidebar .logout {
      margin-top: auto; /* keep at bottom */
      padding: 12px 18px !important;
      font-size: 1.02rem !important;
      min-height: 52px;
      border-radius: 14px !important;
      display: flex !important;
      align-items: center; 
      justify-content: center;
      box-shadow: 0 6px 18px rgba(0,0,0,0.12) !important;
    }

    /* Adjust badge for the slightly smaller layout */
    .sidebar .chat-badge {
      top: 50%;
      transform: translateY(-50%);
      right: 14px;
      min-width: 26px;
      padding: 5px 8px;
      font-size: 0.8rem;
    }

    /* Make sure small screens still display well */
    @media (max-width: 768px) {
      .sidebar {
        padding: 14px;
      }
      .sidebar .nav-link { font-size: 1rem; padding: 10px 12px; }
      .sidebar svg, .sidebar i svg { width: 18px !important; height: 18px !important; }
    }
  </style>
  
  <!-- Barangay Logo -->
  <div style="text-align: center; margin-bottom: 20px; animation: fadeInDown 0.5s ease-out;">
    <img src="brgy1.png" alt="Barangay Logo" style="width: 100px; height: 100px; object-fit: contain; filter: drop-shadow(0 4px 12px rgba(0,0,0,0.3)); border-radius: 50%; background: rgba(255,255,255,0.95); padding: 8px; border: 3px solid rgba(255,255,255,0.4);">
  </div>
  
  <div class="admin-profile-badge" style="text-align: center; margin-bottom: 20px; padding: 12px 16px; background: linear-gradient(135deg, rgba(255,255,255,0.18) 0%, rgba(255,255,255,0.08) 100%); border-radius: 14px; box-shadow: 0 4px 16px rgba(0,0,0,0.2); border: 1.5px solid rgba(255,255,255,0.25); backdrop-filter: blur(12px); animation: fadeInDown 0.6s ease-out;">
    <div style="font-size: 0.92rem; color: #fff; font-weight: 700; text-shadow: 0 2px 4px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; gap: 8px;">
      <svg style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
      <span><?php echo htmlspecialchars($admin_fullname ?: $admin_username); ?></span>
    </div>
  </div>
  
  <h2>🌐 Admin Panel</h2>
  <style> 
    @keyframes fadeInDown {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    .logout:hover {
      background: linear-gradient(135deg, #c82333 0%, #dc3545 100%) !important;
      box-shadow: 0 6px 20px rgba(220, 53, 69, 0.6) !important;
      transform: scale(1.05) !important;
    }
  </style>
  <button id="toggleReportsBtn" class="nav-link" style="background:none;border:none;outline:none;cursor:pointer;text-align:left;width:100%;"><i data-lucide="layout-dashboard"></i> Dashboard</button>
  <button id="toggleAnnouncementsBtn" class="nav-link" style="background:none;border:none;outline:none;cursor:pointer;text-align:left;width:100%;"><i data-lucide="megaphone"></i> Announcements</button>
  <button id="toggleCertificatesBtn" class="nav-link" style="background:none;border:none;outline:none;cursor:pointer;text-align:left;width:100%;"><i data-lucide="file-text"></i> Certificates</button>
  <button id="toggleIncidentsBtn" class="nav-link" style="background:none;border:none;outline:none;cursor:pointer;text-align:left;width:100%;"><i data-lucide="alert-triangle"></i> Incidents</button>
  <button id="toggleOfficialsBtn" class="nav-link" style="background:none;border:none;outline:none;cursor:pointer;text-align:left;width:100%;"><i data-lucide="users"></i> Officials</button>
  <button id="toggleResidentsBtn" class="nav-link" style="background:none;border:none;outline:none;cursor:pointer;text-align:left;width:100%;"><i data-lucide="home"></i> Residents <span id="residentsArrow" style="float:right;transition:transform 0.2s;">&#9654;</span></button>
  <div id="residentsSubMenu" style="display:none; margin-left:18px;">
    <button id="toggleAddResidentsBtn" class="nav-link" style="background:none;border:none;outline:none;cursor:pointer;text-align:left;width:88%;padding:5px 8px;font-size:0.89rem;"><i data-lucide="user-plus"></i> Add New Residents</button>
    <button id="toggleViewResidentsBtn" class="nav-link" style="background:none;border:none;outline:none;cursor:pointer;text-align:left;width:88%;padding:5px 8px;font-size:0.89rem;"><i data-lucide="list"></i> View Residents</button>
    <button id="toggleJobfinderBtn" class="nav-link" style="background:none;border:none;outline:none;cursor:pointer;text-align:left;width:88%;padding:5px 8px;font-size:0.89rem;"><i data-lucide="briefcase-business"></i> Manage Jobfinder</button>

    <?php $role = $_SESSION['role'] ?? 'regular'; if ($role === 'main'): ?>
      <button id="toggleManageAdminBtn" class="nav-link" style="background:none;border:none;outline:none;cursor:pointer;text-align:left;width:88%;padding:5px 8px;font-size:0.89rem;"><i data-lucide="settings"></i> Manage Admin</button>
    <?php endif; ?>




  </div>
  <button id="toggleAdminChatsBtn" class="nav-link" style="background:none;border:none;outline:none;cursor:pointer;text-align:left;width:100%;position:relative;">
    <i data-lucide="message-square"></i> Chats
    <span id="chatBadge" class="chat-badge" style="display:<?= $unread_admin_chats > 0 ? 'inline-block' : 'none' ?>;background:linear-gradient(135deg, #ff4757 0%, #ff6348 100%);color:#fff;border-radius:20px;padding:3px 9px;font-size:0.75em;position:absolute;right:14px;top:50%;transform:translateY(-50%);font-weight:700;min-width:24px;text-align:center;z-index:2;box-shadow:0 3px 12px rgba(255,71,87,0.4);animation:badgePulse 2s infinite;">
      <?= $unread_admin_chats ?>
    </span>
  </button>

  <button id="toggleSuggestionsBtn" class="nav-link" style="background:none;border:none;outline:none;cursor:pointer;text-align:left;width:100%;"><i data-lucide="lightbulb"></i> Suggestions</button>

  <!-- Logout Button -->
  <a href="logout.php" class="logout" style="margin-top: auto; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 8px 12px; background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: #fff; text-decoration: none; border-radius: 10px; font-weight: 600; font-size: 0.9rem; box-shadow: 0 2px 10px rgba(220, 53, 69, 0.3); transition: all 0.3s ease;">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
    <span>Logout</span>
  </a>

</div>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div> 
<script>
  lucide.createIcons();

  // Residents submenu toggle logic
  const toggleResidentsBtn = document.getElementById('toggleResidentsBtn');
  const residentsSubMenu = document.getElementById('residentsSubMenu');
  const residentsArrow = document.getElementById('residentsArrow');
  // Hide all panels helper
  function hideAllPanels() {
    document.querySelectorAll('.panel-section').forEach(function(panel) { panel.style.display = 'none'; });
  }
  if (toggleResidentsBtn && residentsSubMenu && residentsArrow) {
    toggleResidentsBtn.addEventListener('click', function(e) {
      e.preventDefault();
      // Hide all panels when toggling submenu
      hideAllPanels();
      // Toggle submenu — keep it open until the Residents button is clicked again
      if (residentsSubMenu.style.display === 'none' || residentsSubMenu.style.display === '') {
        residentsSubMenu.style.display = 'block';
        residentsArrow.style.transform = 'rotate(90deg)';
      } else {
        residentsSubMenu.style.display = 'none';
        residentsArrow.style.transform = 'rotate(0deg)';
      }
    });
    
  }
// --- Chat Notification Sound Logic ---
let lastUnreadChats = <?= $unread_admin_chats ?>;
function checkNewChats() {
  fetch('get_chats.php?count_unread=1')
    .then(res => res.json())
    .then(data => {
      if (data && typeof data.unread !== 'undefined') {
        // Play sound if new messages arrived
        if (data.unread > lastUnreadChats) {
          document.getElementById('chatNotifAudio').play();
        }
        lastUnreadChats = data.unread;
        
        // Update badge
        const badge = document.getElementById('chatBadge');
        if (badge) {
          if (data.unread > 0) {
            badge.textContent = data.unread;
            badge.style.display = 'inline-block';
          } else {
            badge.style.display = 'none';
          }
        }
      }
    })
    .catch(() => {});
}
setInterval(checkNewChats, 3000); // check every 3 seconds
</script>

  <!-- Main Content -->
    <div class="main-content">
  <!-- Panels -->
<!-- Residents Panel ...existing code... -->

<!-- Recycle Bin Panel (hidden by default, after Residents panel) -->
<div id="panel-recycle-bin" class="panel-section" style="display:none; max-width:1000px; margin:0 auto;">
<?php
include 'config.php';
$sql = "SELECT * FROM deleted_residents ORDER BY deleted_at DESC";
$result = $conn->query($sql);
?>
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="fw-bold text-danger">🗑 Recycle Bin - Deleted Residents</h2>
    </div>
    <?php if (isset($_GET['msg'])): ?>
      <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>
    <div class="card">
      <div class="card-header" style="background: #f44336; color: white; font-size: 1.2rem; font-weight: bold;">
        <div class="d-flex justify-content-between align-items-center">
          <span>Deleted Residents</span>
          <input type="text" id="recycleBinSearchInput" class="form-control form-control-sm" placeholder="🔍 Search..." style="max-width: 250px; background: white; color: #333;">
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
          <table class="table table-hover align-middle mb-0">
            <thead style="position: sticky; top: 0; z-index: 10;">
              <tr>
                <th>Unique ID</th>
                <th>Full Name</th>
                <th>Sex</th>
                <th>Age</th>
                <th>Birthdate</th>
                <th>Deleted At</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody id="recycleBinTableBody">
              <?php if ($result->num_rows > 0): ?>
                <?php while ($resident = $result->fetch_assoc()): ?>
                  <?php $fullname = $resident['surname'] . ', ' . $resident['first_name'] . ' ' . $resident['middle_name']; ?>
                  <tr>
              <td><?= htmlspecialchars($resident['unique_id']) ?></td>
              <td><?= htmlspecialchars($fullname) ?></td>
              <td><?= htmlspecialchars($resident['sex']) ?></td>
              <td><?= htmlspecialchars($resident['age']) ?></td>
              <td><?= htmlspecialchars($resident['birthdate']) ?></td>
              <td><?= htmlspecialchars($resident['deleted_at']) ?></td>
              <td class="text-center">
               <button class="btn btn-sm btn-success recycle-restore-btn" style="border-radius:20px;" data-id="<?= $resident['unique_id'] ?>">Restore</button>
               <button class="btn btn-sm btn-danger recycle-delete-btn" style="border-radius:20px;" data-id="<?= $resident['unique_id'] ?>">Delete</button>
              </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="7" class="text-center text-muted">No deleted residents</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <style>
    #panel-recycle-bin .card {
      border-radius: 15px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      margin-bottom: 30px;
    }
    #panel-recycle-bin .card-header {
      background: #f44336;
      color: white;
      font-size: 1.05rem;
      font-weight: bold;
    }
    #panel-recycle-bin .table th {
      background-color: #ffebee;
      font-size: 11px;
      padding: 8px 6px;
      vertical-align: middle;
      position: sticky;
      top: 0;
      z-index: 10;
    }
    #panel-recycle-bin .table td {
      background: #fff;
      font-size: 11px;
      padding: 6px;
      vertical-align: middle;
    }
    #panel-recycle-bin .table {
      font-size: 11px;
      margin-bottom: 0;
    }
    #panel-recycle-bin .table-responsive {
      border-radius: 0 0 15px 15px;
    }
    #panel-recycle-bin .table-responsive::-webkit-scrollbar {
      width: 8px;
      height: 8px;
    }
    #panel-recycle-bin .table-responsive::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 10px;
    }
    #panel-recycle-bin .table-responsive::-webkit-scrollbar-thumb {
      background: #f44336;
      border-radius: 10px;
    }
    #panel-recycle-bin .table-responsive::-webkit-scrollbar-thumb:hover {
      background: #d32f2f;
    }
  </style>
</div>

<!-- Permanent Delete Confirmation Modal -->
<div class="modal fade" id="permanentDeleteModal" tabindex="-1" aria-labelledby="permanentDeleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="permanentDeleteModalLabel"><i class="bi bi-exclamation-triangle"></i> Confirm Permanent Delete</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-2">Are you sure you want to <strong class="text-danger">permanently delete</strong> this resident?</p>
        <p class="text-muted mb-0"><small><i class="bi bi-info-circle"></i> This action cannot be undone!</small></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="confirmPermanentDeleteBtn" class="btn btn-danger">Yes, Delete Permanently</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const panelRecycleBin = document.getElementById('panel-recycle-bin');
  const toggleRecycleBinBtn = document.getElementById('toggleRecycleBinBtn');
  const backToResidentsBtn2 = document.getElementById('backToResidentsBtn2');

  function showRecycleMsg(msg, type = 'success') {
    var msgDiv = document.createElement('div');
    msgDiv.className = 'alert alert-' + type;
    msgDiv.innerHTML = '<strong>' + (type === 'success' ? '&#10004;' : '&#9940;') + '</strong> ' + msg;
    msgDiv.style.position = 'fixed';
    msgDiv.style.top = '20px';
    msgDiv.style.right = '20px';
    msgDiv.style.zIndex = '9999';
    msgDiv.style.minWidth = '300px';
    msgDiv.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
    document.body.appendChild(msgDiv);
    setTimeout(function() { msgDiv.remove(); }, 3000);
  }

  function refreshRecycleBinTable() {
    fetch('admin_dashboard.php?panel=recycle-bin&ajax=1')
      .then(res => res.text())
      .then(html => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newTbody = doc.querySelector('#recycleBinTableBody');
        if (newTbody) {
          document.getElementById('recycleBinTableBody').innerHTML = newTbody.innerHTML;
          attachRecycleBinBtnEvents();
          // Re-apply search filter if there's a search value
          const searchInput = document.getElementById('recycleBinSearchInput');
          if (searchInput && searchInput.value) {
            searchInput.dispatchEvent(new Event('keyup'));
          }
        }
      });
  }

  function attachRecycleBinBtnEvents() {
    document.querySelectorAll('.recycle-restore-btn').forEach(function(btn) {
      btn.onclick = function() {
        const id = btn.getAttribute('data-id');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Restoring...';
        
        fetch('rrestore_resident.php?id=' + encodeURIComponent(id) + '&ajax=1', { method: 'GET' })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              showRecycleMsg('Resident restored successfully!', 'success');
              refreshRecycleBinTable();
            } else {
              showRecycleMsg(data.error || 'Restore failed.', 'danger');
              btn.disabled = false;
              btn.innerHTML = 'Restore';
            }
          })
          .catch(error => {
            showRecycleMsg('Error: ' + error.message, 'danger');
            btn.disabled = false;
            btn.innerHTML = 'Restore';
          });
      };
    });
    
    document.querySelectorAll('.recycle-delete-btn').forEach(function(btn) {
      btn.onclick = function() {
        const id = btn.getAttribute('data-id');
        const modal = new bootstrap.Modal(document.getElementById('permanentDeleteModal'));
        modal.show();
        
        const confirmBtn = document.getElementById('confirmPermanentDeleteBtn');
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
        
        newConfirmBtn.onclick = function() {
          newConfirmBtn.disabled = true;
          newConfirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Deleting...';
          
          fetch('rpermanent_delete.php?id=' + encodeURIComponent(id) + '&ajax=1', { method: 'GET' })
            .then(res => res.json())
            .then(data => {
              modal.hide();
              if (data.success) {
                showRecycleMsg('Resident permanently deleted!', 'success');
                refreshRecycleBinTable();
              } else {
                showRecycleMsg(data.error || 'Delete failed.', 'danger');
              }
              newConfirmBtn.disabled = false;
              newConfirmBtn.innerHTML = 'Yes, Delete Permanently';
            })
            .catch(error => {
              modal.hide();
              showRecycleMsg('Error: ' + error.message, 'danger');
              newConfirmBtn.disabled = false;
              newConfirmBtn.innerHTML = 'Yes, Delete Permanently';
            });
        };
      };
    });
  }

  attachRecycleBinBtnEvents();

  // Search functionality for Recycle Bin
  const recycleBinSearchInput = document.getElementById('recycleBinSearchInput');
  if (recycleBinSearchInput) {
    recycleBinSearchInput.addEventListener('keyup', function() {
      const searchValue = this.value.toLowerCase().trim();
      const tableRows = document.querySelectorAll('#recycleBinTableBody tr');
      
      tableRows.forEach(function(row) {
        const rowText = row.textContent.toLowerCase();
        if (rowText.includes(searchValue)) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    });
  }

  if (toggleRecycleBinBtn) {
    toggleRecycleBinBtn.addEventListener('click', function(e) {
      e.preventDefault();
      document.querySelectorAll('.panel-section').forEach(function(panel) { panel.style.display = 'none'; });
      panelRecycleBin.style.display = 'block';
      history.replaceState(null, '', '?panel=recycle-bin');
      // Refresh table when panel is opened
      refreshRecycleBinTable();
    });
  }
  if (backToResidentsBtn2) {
    backToResidentsBtn2.addEventListener('click', function(e) {
      e.preventDefault();
      document.querySelectorAll('.panel-section').forEach(function(panel) { panel.style.display = 'none'; });
      var viewPanel = document.getElementById('panel-view-residents');
      if (viewPanel) viewPanel.style.display = 'block';
      history.replaceState(null, '', '?panel=view-residents');
    });
  }
  // Show Recycle Bin panel if URL has panel=recycle-bin
  var params = new URLSearchParams(window.location.search);
  if (params.get('panel') === 'recycle-bin') {
    document.querySelectorAll('.panel-section').forEach(function(panel) { panel.style.display = 'none'; });
    var recyclePanel = document.getElementById('panel-recycle-bin');
    if (recyclePanel) recyclePanel.style.display = 'block';
  }
});
</script>
    <!-- Households Panel (hidden by default) -->
      <div id="panel-households" class="panel-section" style="display:none; max-width:1200px; margin:0 auto;">
      <?php
      include 'config.php';
      $filter_household = $_GET['household_id'] ?? '';
      $sql = "SELECT * FROM residents";
      if ($filter_household) {
          $sql .= " WHERE household_id = ?";
      }
      $stmt = $conn->prepare($sql);
      if ($filter_household) {
          $stmt->bind_param("s", $filter_household);
      }
      $stmt->execute();
      $result = $stmt->get_result();
      $households = [];
      while ($r = $result->fetch_assoc()) {
          $hid = $r['household_id'] ?? 'N/A';
          $households[$hid][] = $r;
      }
      $totalHouseholds = count($households);
      $stmt->close();
      $conn->close();
      ?>
        <div class="container py-4">
          <!-- Modern Header -->
          <div style="background:linear-gradient(135deg, #14ad0f 0%, #43e97b 100%);padding:40px;border-radius:20px;margin-bottom:32px;box-shadow:0 10px 40px rgba(20,173,15,0.3);">
            <div class="d-flex align-items-center gap-3 mb-2">
              <div style="width:60px;height:60px;background:rgba(255,255,255,0.2);border-radius:16px;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(10px);">
                <i class="bi bi-houses-fill" style="font-size:32px;color:white;"></i>
              </div>
              <div>
                <h1 class="text-white mb-0" style="font-size:32px;font-weight:800;letter-spacing:-0.5px;">Barangay Households</h1>
                <p class="text-white mb-0" style="opacity:0.9;font-size:15px;">Manage and view household information</p>
              </div>
            </div>
          </div>

          <!-- Search & Stats Section -->
          <div class="row g-4 mb-4">
            <!-- Search Card -->
            <div class="col-lg-8">
              <div class="card" style="border:none;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,0.08);overflow:hidden;">
                <div class="card-body p-4">
                  <h5 class="fw-bold mb-3" style="color:#14ad0f;font-size:16px;text-transform:uppercase;letter-spacing:0.5px;">
                    <i class="bi bi-search"></i> Search Household
                  </h5>
                  <form method="GET" class="row g-3" id="householdSearchForm">
                    <input type="hidden" name="panel" value="households">
                    <div class="col-md-8">
                      <input type="text" class="form-control" placeholder="Enter Household Number..." 
                             name="household_id" value="<?= htmlspecialchars($filter_household) ?>"
                             style="border-radius:10px;border:1.5px solid #e0e0e0;padding:12px 16px;font-size:14px;">
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                      <button type="submit" class="btn flex-fill" style="background:linear-gradient(135deg, #14ad0f 0%, #43e97b 100%);color:white;border:none;border-radius:10px;padding:12px;font-weight:600;box-shadow:0 4px 12px rgba(20,173,15,0.3);">
                        <i class="bi bi-search"></i> Search
                      </button>
                      <button type="button" id="resetHouseholdBtn" class="btn btn-outline-secondary" style="border-radius:10px;padding:12px 20px;border:1.5px solid #e0e0e0;">
                        <i class="bi bi-arrow-clockwise"></i>
                      </button>
                    </div>
                  </form>
                </div>
              </div>
            </div>

            <!-- Stats Card -->
            <div class="col-lg-4">
              <div class="card" style="border:none;border-radius:16px;background:linear-gradient(135deg, #14ad0f 0%, #43e97b 100%);box-shadow:0 4px 20px rgba(20,173,15,0.3);height:100%;">
                <div class="card-body p-4 d-flex flex-column justify-content-center text-center">
                  <div style="font-size:48px;font-weight:800;color:white;line-height:1;"><?= $totalHouseholds ?></div>
                  <div style="color:white;font-size:14px;font-weight:600;text-transform:uppercase;letter-spacing:1px;margin-top:8px;">Total Households</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Household Cards Grid -->
          <?php if ($households): ?>
            <div class="row g-4">
              <?php foreach ($households as $hid => $members): 
                $headMember = null;
                foreach ($members as $m) {
                  if (isset($m['is_head']) && $m['is_head'] === 'Yes') {
                    $headMember = $m;
                    break;
                  }
                }
              ?>
                <div class="col-lg-6">
                  <div class="card household-card-modern">
                    <div class="household-header">
                      <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                          <div class="household-icon">
                            <i class="bi bi-house-door-fill"></i>
                          </div>
                          <div>
                            <h5 class="mb-0" style="font-weight:700;font-size:18px;color:#333;">Household #<?= htmlspecialchars($hid) ?></h5>
                            <p class="mb-0" style="font-size:13px;color:#666;">
                              <i class="bi bi-people-fill"></i> <?= count($members) ?> member<?= count($members) > 1 ? 's' : '' ?>
                            </p>
                          </div>
                        </div>
                        <?php if ($headMember): ?>
                          <span class="badge" style="background:linear-gradient(135deg, #14ad0f 0%, #43e97b 100%);padding:8px 16px;border-radius:20px;font-size:11px;font-weight:600;">
                            <i class="bi bi-star-fill"></i> HEAD
                          </span>
                        <?php endif; ?>
                      </div>
                    </div>
                    <div class="household-body">
                      <?php foreach ($members as $idx => $m): ?>
                        <div class="resident-item">
                          <div class="d-flex align-items-center gap-3">
                            <div class="resident-avatar">
                              <?= strtoupper(substr($m['first_name'], 0, 1)) ?>
                            </div>
                            <div class="flex-grow-1">
                              <div class="resident-name">
                                <?= htmlspecialchars($m['first_name'] . ' ' . ($m['middle_name'] ?? '') . ' ' . $m['surname']) ?>
                                <?php if (isset($m['is_head']) && $m['is_head'] === 'Yes'): ?>
                                  <i class="bi bi-star-fill text-warning" style="font-size:12px;"></i>
                                <?php endif; ?>
                              </div>
                              <div class="resident-details">
                                <span><i class="bi bi-gender-ambiguous"></i> <?= htmlspecialchars($m['sex']) ?></span>
                                <span><i class="bi bi-calendar"></i> <?= htmlspecialchars($m['age']) ?> yrs</span>
                                <span><i class="bi bi-heart"></i> <?= htmlspecialchars($m['civil_status']) ?></span>
                              </div>
                            </div>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="text-center py-5">
              <div style="font-size:64px;color:#e0e0e0;margin-bottom:16px;">
                <i class="bi bi-house-x"></i>
              </div>
              <h5 style="color:#666;font-weight:600;">No households found</h5>
              <p style="color:#999;">Try adjusting your search criteria</p>
            </div>
          <?php endif; ?>
        </div>
        <style>
          .household-card-modern {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: all 0.3s ease;
            background: white;
          }
          .household-card-modern:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
          }
          .household-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px 24px;
            border-bottom: 2px solid #e0e0e0;
          }
          .household-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #14ad0f 0%, #43e97b 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
          }
          .household-body {
            padding: 16px 24px 24px;
          }
          .resident-item {
            padding: 16px 0;
            border-bottom: 1px solid #f0f0f0;
          }
          .resident-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
          }
          .resident-avatar {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #14ad0f 0%, #43e97b 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 16px;
            flex-shrink: 0;
          }
          .resident-name {
            font-weight: 600;
            font-size: 15px;
            color: #333;
            margin-bottom: 4px;
          }
          .resident-details {
            font-size: 12px;
            color: #666;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
          }
          .resident-details span {
            display: inline-flex;
            align-items: center;
            gap: 4px;
          }
          .resident-details i {
            font-size: 11px;
            opacity: 0.7;
          }
        </style>
      </div>
   <!-- Edit Resident Panel (hidden by default) -->
<div id="panel-edit-resident" class="panel-section" style="display:none; max-width:650px; margin:0 auto;">
<?php
// --- Begin: Edit Resident logic from redit_residents.php (adapted for dashboard panel) ---
include 'config.php';
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
  echo "<div class='alert alert-danger text-center'>⛔ Admin login required.</div>";
} else {
  $admin_username = $_SESSION['admin_username'];
  $unique_id = $_GET['editr_id'] ?? '';
  $resident = null;
  if ($unique_id) {
    $stmt = $conn->prepare("SELECT * FROM residents WHERE unique_id = ?");
    $stmt->bind_param("s", $unique_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $resident = $result->fetch_assoc();
    $stmt->close();
  }
  if (!$unique_id || !$resident) {
    echo "<div class='alert alert-warning text-center'>No resident selected. Please select a resident to edit from the Residents panel.</div>";
  } else {
    // Note: Form submission is handled at the top of the file for AJAX requests
?>
<div class="card shadow-lg mb-4" style="max-width:720px;margin:auto;background:#fff;border-radius:20px;overflow:hidden;border:none;">
  <!-- Modern Header -->
  <div style="background:linear-gradient(135deg, #14ad0f 0%, #43e97b 100%);padding:32px 40px;">
    <h2 class="fw-bold text-white mb-2" style="font-size:28px;letter-spacing:-0.5px;">Edit Resident</h2>
    <p class="text-white mb-0" style="opacity:0.9;font-size:14px;">Update resident information and details</p>
  </div>
  
  <form method="POST" id="editResidentForm" class="row g-4 p-4" style="padding:40px !important;">
    <input type="hidden" name="edit_resident_dashboard" value="1">
    <input type="hidden" name="unique_id" value="<?= htmlspecialchars($unique_id) ?>">
    <!-- Name Section -->
    <div class="col-12">
      <h5 class="fw-bold mb-3" style="color:#14ad0f;font-size:16px;text-transform:uppercase;letter-spacing:0.5px;border-bottom:2px solid #e8f5e9;padding-bottom:8px;">Personal Information</h5>
    </div>
    <div class="col-md-4">
      <label class="form-label fw-semibold" style="color:#333;font-size:13px;margin-bottom:8px;">Surname</label>
      <input type="text" name="surname" class="form-control" value="<?= htmlspecialchars($resident['surname']) ?>" required style="border-radius:10px;border:1.5px solid #e0e0e0;padding:12px 16px;font-size:14px;transition:all 0.3s;">
    </div>
    <div class="col-md-4">
      <label class="form-label fw-semibold" style="color:#333;font-size:13px;margin-bottom:8px;">First Name</label>
      <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($resident['first_name']) ?>" required style="border-radius:10px;border:1.5px solid #e0e0e0;padding:12px 16px;font-size:14px;transition:all 0.3s;">
    </div>
    <div class="col-md-4">
      <label class="form-label fw-semibold" style="color:#333;font-size:13px;margin-bottom:8px;">Middle Name</label>
      <input type="text" name="middle_name" class="form-control" value="<?= htmlspecialchars($resident['middle_name']) ?>" style="border-radius:10px;border:1.5px solid #e0e0e0;padding:12px 16px;font-size:14px;transition:all 0.3s;">
    </div>
    <div class="col-md-2">
      <label class="form-label fw-semibold" style="color:#333;font-size:13px;margin-bottom:8px;">Age</label>
      <input type="number" name="age" id="age" class="form-control" value="<?= htmlspecialchars($resident['age']) ?>" readonly style="border-radius:10px;border:1.5px solid #e0e0e0;padding:12px 16px;font-size:14px;background:#f5f5f5;">
    </div>
    <div class="col-md-3">
      <label class="form-label fw-semibold" style="color:#333;font-size:13px;margin-bottom:8px;">Gender</label>
      <select name="sex" class="form-select" style="border-radius:10px;border:1.5px solid #e0e0e0;padding:12px 16px;font-size:14px;transition:all 0.3s;">
        <option value="Male" <?= $resident['sex']==='Male'?'selected':'' ?>>Male</option>
        <option value="Female" <?= $resident['sex']==='Female'?'selected':'' ?>>Female</option>
        <option value="LGBTQ" <?= $resident['sex']==='LGBTQ'?'selected':'' ?>>LGBTQ</option>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label fw-semibold" style="color:#333;font-size:13px;margin-bottom:8px;">Birthdate</label>
      <input type="date" name="birthdate" id="birthdate" class="form-control" value="<?= htmlspecialchars($resident['birthdate']) ?>" style="border-radius:10px;border:1.5px solid #e0e0e0;padding:12px 16px;font-size:14px;transition:all 0.3s;">
    </div>
    <div class="col-md-4">
      <label class="form-label fw-semibold" style="color:#333;font-size:13px;margin-bottom:8px;">Place of Birth</label>
      <input type="text" name="place_of_birth" class="form-control" value="<?= htmlspecialchars($resident['place_of_birth']) ?>" style="border-radius:10px;border:1.5px solid #e0e0e0;padding:12px 16px;font-size:14px;transition:all 0.3s;">
    </div>
    <!-- Additional Details Section -->
    <div class="col-12 mt-3">
      <h5 class="fw-bold mb-3" style="color:#14ad0f;font-size:16px;text-transform:uppercase;letter-spacing:0.5px;border-bottom:2px solid #e8f5e9;padding-bottom:8px;">Additional Details</h5>
    </div>
    <div class="col-md-4">
      <label class="form-label fw-semibold" style="color:#333;font-size:13px;margin-bottom:8px;">Civil Status</label>
      <input type="text" name="civil_status" class="form-control" value="<?= htmlspecialchars($resident['civil_status']) ?>" style="border-radius:10px;border:1.5px solid #e0e0e0;padding:12px 16px;font-size:14px;transition:all 0.3s;">
    </div>
    <div class="col-md-4">
      <label class="form-label fw-semibold" style="color:#333;font-size:13px;margin-bottom:8px;">Citizenship</label>
      <input type="text" name="citizenship" class="form-control" value="<?= htmlspecialchars($resident['citizenship']) ?>" style="border-radius:10px;border:1.5px solid #e0e0e0;padding:12px 16px;font-size:14px;transition:all 0.3s;">
    </div>
    <div class="col-md-4">
      <label class="form-label fw-semibold" style="color:#333;font-size:13px;margin-bottom:8px;">Occupation / Skills</label>
      <input type="text" name="occupation_skills" class="form-control" value="<?= htmlspecialchars($resident['occupation_skills']) ?>" style="border-radius:10px;border:1.5px solid #e0e0e0;padding:12px 16px;font-size:14px;transition:all 0.3s;">
    </div>
    <div class="col-md-4">
      <label class="form-label fw-semibold" style="color:#333;font-size:13px;margin-bottom:8px;">Education</label>
      <input type="text" name="education" class="form-control" value="<?= htmlspecialchars($resident['education']) ?>" style="border-radius:10px;border:1.5px solid #e0e0e0;padding:12px 16px;font-size:14px;transition:all 0.3s;">
    </div>
    <div class="col-md-4">
      <label class="form-label fw-semibold" style="color:#333;font-size:13px;margin-bottom:8px;">PWD</label>
      <select name="is_pwd" class="form-select" style="border-radius:10px;border:1.5px solid #e0e0e0;padding:12px 16px;font-size:14px;transition:all 0.3s;">
        <option value="Yes" <?= $resident['is_pwd']==='Yes'?'selected':'' ?>>Yes</option>
        <option value="No" <?= $resident['is_pwd']==='No'?'selected':'' ?>>No</option>
      </select>
    </div>
    <!-- Address Section -->
    <div class="col-12 mt-3">
      <h5 class="fw-bold mb-3" style="color:#14ad0f;font-size:16px;text-transform:uppercase;letter-spacing:0.5px;border-bottom:2px solid #e8f5e9;padding-bottom:8px;">Address & Household</h5>
    </div>
    <div class="col-md-6">
      <label class="form-label fw-semibold" style="color:#333;font-size:13px;margin-bottom:8px;">Address</label>
      <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($resident['address']) ?>" style="border-radius:10px;border:1.5px solid #e0e0e0;padding:12px 16px;font-size:14px;transition:all 0.3s;">
    </div>
    <div class="col-md-3">
      <label class="form-label fw-semibold" style="color:#333;font-size:13px;margin-bottom:8px;">Household No.</label>
      <input type="text" name="household_id" class="form-control" value="<?= htmlspecialchars($resident['household_id']) ?>" style="border-radius:10px;border:1.5px solid #e0e0e0;padding:12px 16px;font-size:14px;transition:all 0.3s;">
    </div>
    <div class="col-md-3">
      <label class="form-label fw-semibold" style="color:#333;font-size:13px;margin-bottom:8px;">Head of Household</label>
      <select name="is_head" class="form-select" style="border-radius:10px;border:1.5px solid #e0e0e0;padding:12px 16px;font-size:14px;transition:all 0.3s;">
        <option value="Yes" <?= $resident['is_head']==='Yes'?'selected':'' ?>>Yes</option>
        <option value="No" <?= $resident['is_head']==='No'?'selected':'' ?>>No</option>
      </select>
    </div>
    <div class="col-12 d-flex justify-content-end gap-3 mt-4 pt-3" style="border-top:1px solid #e0e0e0;">
      <button type="button" id="cancelEditResidentBtn2" class="btn btn-secondary" style="padding:12px 32px;border-radius:10px;font-weight:600;font-size:14px;border:none;background:#6c757d;transition:all 0.3s;">Cancel</button>
      <button type="submit" id="updateResidentBtn" class="btn btn-success" style="padding:12px 32px;border-radius:10px;font-weight:600;font-size:14px;border:none;background:linear-gradient(135deg, #14ad0f 0%, #43e97b 100%);box-shadow:0 4px 12px rgba(20,173,15,0.3);transition:all 0.3s;">Update Resident</button>
    </div>
    <div id="editResidentMessage" class="col-12 mt-2" style="display:none;"></div>
  </form>
</div>
<script>
// Note: These functions are now defined globally below (after the PHP section)
// This script tag is kept for backward compatibility if someone loads the page with editr_id in URL
</script>
<?php }
}
?>
</div>
<script>
// Edit Resident panel logic
const panelEditResident = document.getElementById('panel-edit-resident');
const toggleEditResidentBtn = document.getElementById('toggleEditResidentBtn');
if (toggleEditResidentBtn) {
  toggleEditResidentBtn.addEventListener('click', function(e) {
    e.preventDefault();
    document.querySelectorAll('.panel-section').forEach(function(panel) { panel.style.display = 'none'; });
    panelEditResident.style.display = 'block';
    history.replaceState(null, '', '?panel=edit-resident');
  });
}

// Cancel/back button logic for Edit Resident (GLOBAL)
function goToResidentsTable() {
  // Hide edit panel, show residents table panel, update URL
  document.querySelectorAll('.panel-section').forEach(function(panel) { panel.style.display = 'none'; });
  var viewPanel = document.getElementById('panel-view-residents');
  if (viewPanel) viewPanel.style.display = 'block';
  var url = new URL(window.location.href);
  url.searchParams.set('panel', 'view-residents');
  url.searchParams.delete('editr_id');
  history.replaceState(null, '', url.toString());
  
  // Reload the residents table to show updated data
  if (typeof loadResidentsPage === 'function') {
    var currentPage = 1;
    var pageNumEl = document.getElementById('currentPageNum');
    if (pageNumEl) {
      currentPage = parseInt(pageNumEl.textContent) || 1;
    }
    loadResidentsPage(currentPage);
  }
  
  // Re-attach delete button events after returning from edit
  setTimeout(function() {
    if (typeof attachDeleteBtnEvents === 'function') {
      attachDeleteBtnEvents();
    }
  }, 100);
}

function attachCancelEditResidentBtnEvents() {
  var btn1 = document.getElementById('cancelEditResidentBtn');
  var btn2 = document.getElementById('cancelEditResidentBtn2');
  if (btn1) {
    btn1.onclick = function(e) {
      e.preventDefault();
      goToResidentsTable();
    };
  }
  if (btn2) {
    btn2.onclick = function(e) {
      e.preventDefault();
      goToResidentsTable();
    };
  }
}

// AJAX form submission for Edit Resident (GLOBAL)
function attachEditResidentFormHandler() {
  var form = document.getElementById('editResidentForm');
  if (form) {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      
      var submitBtn = document.getElementById('updateResidentBtn');
      var messageDiv = document.getElementById('editResidentMessage');
      var originalBtnText = submitBtn.innerHTML;
      
      // Show loading state
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Updating...';
      messageDiv.style.display = 'none';
      
      // Prepare form data
      var formData = new FormData(form);
      
      // Send AJAX request with XMLHttpRequest header
      fetch('admin_dashboard.php?panel=edit-resident&editr_id=' + formData.get('unique_id'), {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      .then(function(response) {
        if (!response.ok) {
          throw new Error('HTTP error! status: ' + response.status);
        }
        return response.text();
      })
      .then(function(text) {
        console.log('Response:', text);
        // Try to parse as JSON
        try {
          var data = JSON.parse(text);
          // Check if update was successful
          if (data.success) {
            // Show success message on the right (like officials)
            var successMsg = document.createElement('div');
            successMsg.className = 'alert alert-success';
            successMsg.innerHTML = '<strong>✓ Updated!</strong> ' + data.message;
            successMsg.style.position = 'fixed';
            successMsg.style.top = '20px';
            successMsg.style.right = '20px';
            successMsg.style.zIndex = '9999';
            document.body.appendChild(successMsg);
            
            // Auto-remove after 3 seconds
            setTimeout(function() {
              successMsg.remove();
            }, 3000);
            
            // Reset button
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
            
            // Redirect back to residents table after 1.5 seconds
            setTimeout(function() {
              goToResidentsTable();
            }, 1500);
          } else {
            // Show error message
            messageDiv.className = 'col-12 mt-2 alert alert-danger';
            messageDiv.innerHTML = '❌ ' + (data.message || 'Error updating resident. Please try again.');
            messageDiv.style.display = 'block';
            
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
          }
        } catch (e) {
          console.error('JSON parse error:', e);
          console.error('Response text:', text);
          console.error('Response length:', text.length);
          console.error('First 500 chars:', text.substring(0, 500));
          messageDiv.className = 'col-12 mt-2 alert alert-danger';
          messageDiv.innerHTML = '❌ Invalid response from server. Response: ' + text.substring(0, 100).replace(/</g, '&lt;').replace(/>/g, '&gt;');
          messageDiv.style.display = 'block';
          
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalBtnText;
        }
      })
      .catch(function(error) {
        console.error('Error:', error);
        messageDiv.className = 'col-12 mt-2 alert alert-danger';
        messageDiv.innerHTML = '❌ Network error: ' + error.message;
        messageDiv.style.display = 'block';
        
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
      });
    });
  }
}

// Attach Edit Resident button handler (for initial load and after AJAX table reload)
function attachEditResidentBtnEvents() {
  document.querySelectorAll('.edit-resident-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      var uniqueId = this.getAttribute('data-unique-id');
      
      // Show edit panel
      document.querySelectorAll('.panel-section').forEach(function(panel) { panel.style.display = 'none'; });
      var editPanel = document.getElementById('panel-edit-resident');
      if (editPanel) editPanel.style.display = 'block';
      
      // Show loading state
      editPanel.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-3">Loading resident data...</p></div>';
      
      // Update URL
      var url = new URL(window.location.href);
      url.searchParams.set('panel', 'edit-resident');
      url.searchParams.set('editr_id', uniqueId);
      history.replaceState(null, '', url.toString());
      
      // Fetch resident data via AJAX
      fetch('admin_dashboard.php?ajax_get_resident=' + uniqueId, {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      .then(function(response) {
        if (!response.ok) throw new Error('HTTP error! status: ' + response.status);
        return response.json();
      })
      .then(function(data) {
        if (data.success && data.resident) {
          // Populate the edit form with resident data
          loadEditForm(data.resident);
        } else {
          editPanel.innerHTML = '<div class="alert alert-danger m-4">' + (data.message || 'Error loading resident data') + '</div>';
        }
      })
      .catch(function(error) {
        console.error('Error:', error);
        editPanel.innerHTML = '<div class="alert alert-danger m-4">Error loading resident data: ' + error.message + '</div>';
      });
    });
  });
}

// Function to load the edit form with resident data
function loadEditForm(resident) {
  var editPanel = document.getElementById('panel-edit-resident');
  
  var inputStyle = 'border-radius:10px;border:1.5px solid #e0e0e0;padding:12px 16px;font-size:14px;transition:all 0.3s;';
  var labelStyle = 'color:#333;font-size:13px;margin-bottom:8px;';
  
  var formHtml = '<div class="card shadow-lg mb-4" style="max-width:900px;margin:auto;background:#fff;border-radius:16px;overflow:hidden;border:none;">' +
    '<div style="background:linear-gradient(135deg, #14ad0f 0%, #43e97b 100%);padding:20px 28px;">' +
    '<h2 class="fw-bold text-white mb-2" style="font-size:1.6rem;letter-spacing:0.3px;">Edit Resident</h2>' +
    '<p class="text-white mb-0" style="opacity:0.9;font-size:0.85rem;">Update resident information and details</p>' +
    '</div>' +
    '<form method="POST" id="editResidentForm" class="row g-3 p-4" style="padding:28px 24px !important;">' +
    '<input type="hidden" name="edit_resident_dashboard" value="1">' +
    '<input type="hidden" name="unique_id" value="' + escapeHtml(resident.unique_id) + '">' +
    
    '<!-- Row 1: Name Fields -->' +
    '<div class="col-md-4"><label class="form-label fw-semibold" style="' + labelStyle + '">Surname</label>' +
    '<input type="text" name="surname" class="form-control" value="' + escapeHtml(resident.surname) + '" required style="' + inputStyle + '"></div>' +
    '<div class="col-md-4"><label class="form-label fw-semibold" style="' + labelStyle + '">First Name</label>' +
    '<input type="text" name="first_name" class="form-control" value="' + escapeHtml(resident.first_name) + '" required style="' + inputStyle + '"></div>' +
    '<div class="col-md-4"><label class="form-label fw-semibold" style="' + labelStyle + '">Middle Name</label>' +
    '<input type="text" name="middle_name" class="form-control" value="' + escapeHtml(resident.middle_name || '') + '" style="' + inputStyle + '"></div>' +
    
    '<!-- Row 2: Birthdate, Age, Gender, Place of Birth -->' +
    '<div class="col-md-3"><label class="form-label fw-semibold" style="' + labelStyle + '">Birthdate</label>' +
    '<input type="date" name="birthdate" id="birthdate" class="form-control" value="' + escapeHtml(resident.birthdate || '') + '" style="' + inputStyle + '"></div>' +
    '<div class="col-md-2"><label class="form-label fw-semibold" style="' + labelStyle + '">Age</label>' +
    '<input type="number" name="age" id="age" class="form-control" value="' + escapeHtml(resident.age) + '" readonly style="border-radius:10px;border:1.5px solid #e0e0e0;padding:12px 16px;font-size:14px;background:#f5f5f5;"></div>' +
    '<div class="col-md-3"><label class="form-label fw-semibold" style="' + labelStyle + '">Gender</label>' +
    '<select name="sex" class="form-select" style="' + inputStyle + '">' +
    '<option value="Male"' + (resident.sex === 'Male' ? ' selected' : '') + '>Male</option>' +
    '<option value="Female"' + (resident.sex === 'Female' ? ' selected' : '') + '>Female</option>' +
    '<option value="Other"' + (resident.sex === 'Other' ? ' selected' : '') + '>Other</option>' +
    '</select></div>' +
    '<div class="col-md-4"><label class="form-label fw-semibold" style="' + labelStyle + '">Place of Birth</label>' +
    '<input type="text" name="place_of_birth" class="form-control" value="' + escapeHtml(resident.place_of_birth || '') + '" style="' + inputStyle + '"></div>' +
    
    '<!-- Row 3: Civil Status, Citizenship, Occupation, Education -->' +
    '<div class="col-md-3"><label class="form-label fw-semibold" style="' + labelStyle + '">Civil Status</label>' +
    '<input type="text" name="civil_status" class="form-control" value="' + escapeHtml(resident.civil_status || '') + '" style="' + inputStyle + '"></div>' +
    '<div class="col-md-3"><label class="form-label fw-semibold" style="' + labelStyle + '">Citizenship</label>' +
    '<input type="text" name="citizenship" class="form-control" value="' + escapeHtml(resident.citizenship || '') + '" style="' + inputStyle + '"></div>' +
    '<div class="col-md-3"><label class="form-label fw-semibold" style="' + labelStyle + '">Occupation / Skills</label>' +
    '<input type="text" name="occupation_skills" class="form-control" value="' + escapeHtml(resident.occupation_skills || '') + '" style="' + inputStyle + '"></div>' +
    '<div class="col-md-3"><label class="form-label fw-semibold" style="' + labelStyle + '">Education</label>' +
    '<input type="text" name="education" class="form-control" value="' + escapeHtml(resident.education || '') + '" style="' + inputStyle + '"></div>' +
    
    '<!-- Row 4: Address, Household No., Relationship -->' +
    '<div class="col-md-4"><label class="form-label fw-semibold" style="' + labelStyle + '">Address</label>' +
    '<input type="text" name="address" class="form-control" value="' + escapeHtml(resident.address || '') + '" style="' + inputStyle + '"></div>' +
    '<div class="col-md-4"><label class="form-label fw-semibold" style="' + labelStyle + '">Household No.</label>' +
    '<input type="text" name="household_id" class="form-control" value="' + escapeHtml(resident.household_id || '') + '" style="' + inputStyle + '"></div>' +
    '<div class="col-md-4"><label class="form-label fw-semibold" style="' + labelStyle + '">Relationship to Household Head</label>' +
    '<input type="text" name="relationship" class="form-control" value="' + escapeHtml(resident.relationship || '') + '" style="' + inputStyle + '"></div>' +
    
    '<!-- Row 5: Checkboxes -->' +
    '<div class="col-md-6"><label class="form-label fw-semibold" style="' + labelStyle + '">Head of Household</label>' +
    '<select name="is_head" class="form-select" style="' + inputStyle + '">' +
    '<option value="Yes"' + (resident.is_head === 'Yes' ? ' selected' : '') + '>Yes</option>' +
    '<option value="No"' + (resident.is_head === 'No' ? ' selected' : '') + '>No</option>' +
    '</select></div>' +
    '<div class="col-md-6"><label class="form-label fw-semibold" style="' + labelStyle + '">Person With Disability (PWD)</label>' +
    '<select name="is_pwd" class="form-select" style="' + inputStyle + '">' +
    '<option value="Yes"' + (resident.is_pwd === 'Yes' ? ' selected' : '') + '>Yes</option>' +
    '<option value="No"' + (resident.is_pwd === 'No' ? ' selected' : '') + '>No</option>' +
    '</select></div>' +
    
    '<div class="col-12 d-flex justify-content-end gap-3 mt-3 pt-3" style="border-top:1px solid #e0e0e0;">' +
    '<button type="button" id="cancelEditResidentBtn2" class="btn btn-secondary" style="padding:10px 24px;border-radius:10px;font-weight:600;font-size:14px;border:none;background:#6c757d;transition:all 0.3s;">Cancel</button>' +
    '<button type="submit" id="updateResidentBtn" class="btn btn-success" style="padding:10px 24px;border-radius:10px;font-weight:600;font-size:14px;border:none;background:linear-gradient(135deg, #14ad0f 0%, #43e97b 100%);box-shadow:0 4px 12px rgba(20,173,15,0.3);transition:all 0.3s;">Update Resident</button>' +
    '</div>' +
    '<div id="editResidentMessage" class="col-12 mt-2" style="display:none;"></div>' +
    '</form></div>';
  
  editPanel.innerHTML = formHtml;
  
  // Re-attach event handlers
  attachCancelEditResidentBtnEvents();
  attachEditResidentFormHandler();
  
  // Setup birthdate auto-age calculation
  setupBirthdateHandler();
}

// Helper function to escape HTML
function escapeHtml(text) {
  if (text === null || text === undefined) return '';
  var map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };
  return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}

// Setup birthdate change handler for auto age calculation
function setupBirthdateHandler() {
  var birthdateInput = document.getElementById('birthdate');
  var ageInput = document.getElementById('age');
  if (birthdateInput && ageInput) {
    birthdateInput.addEventListener('change', function() {
      if (this.value) {
        var birthDate = new Date(this.value);
        var today = new Date();
        var age = today.getFullYear() - birthDate.getFullYear();
        var monthDiff = today.getMonth() - birthDate.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
          age--;
        }
        ageInput.value = age >= 0 ? age : 0;
      }
    });
  }
}
document.addEventListener('DOMContentLoaded', function() {
  attachEditResidentBtnEvents();
  // Show edit panel if URL has panel=edit-resident
  var params = new URLSearchParams(window.location.search);
  if (params.get('panel') === 'edit-resident') {
    document.querySelectorAll('.panel-section').forEach(function(panel) { panel.style.display = 'none'; });
    var editPanel = document.getElementById('panel-edit-resident');
    if (editPanel) editPanel.style.display = 'block';
  }
});
// Also call after AJAX table reloads
function attachResidentsPaginationEvents() {
  const prevBtn = document.getElementById('prevPageBtn');
  const nextBtn = document.getElementById('nextPageBtn');
  if (prevBtn) prevBtn.onclick = function() {
    const current = parseInt(document.getElementById('currentPageNum').textContent);
    if (current > 1) loadResidentsPage(current - 1);
  };
  if (nextBtn) nextBtn.onclick = function() {
    const current = parseInt(document.getElementById('currentPageNum').textContent);
    const total = parseInt(document.getElementById('totalPagesNum').textContent);
    if (current < total) loadResidentsPage(current + 1);
  };
  attachEditResidentBtnEvents(); // Re-attach edit button events after table update
  attachCancelEditResidentBtnEvents(); // Re-attach cancel button events after table update
}
</script>
  <!-- Admin Chats Panel (hidden by default) -->
  <div id="panel-admin-chats" class="panel-section" style="display:none; overflow:hidden; height:calc(100vh - 50px); padding:0; margin:0;">
    <iframe src="admin_chats.php" style="width:100%; height:100%; border:none; background:#f5f6fa; overflow:hidden;"></iframe>
  </div>
<script>
// Admin Chats panel logic - will be handled by main showPanel function
</script>
<!-- Panels -->
<div id="panel-welcome" class="panel-section" style="padding: 20px;">
  <style>
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    @keyframes pulse {
      0%, 100% {
        transform: scale(1);
      }
      50% {
        transform: scale(1.05);
      }
    }
    
    @keyframes shimmer {
      0% {
        background-position: -1000px 0;
      }
      100% {
        background-position: 1000px 0;
      }
    }
    
    .dashboard-container {
      display: flex;
      justify-content: center;
      align-items: center;
      max-width: 1600px;
      margin: 0 auto;
      animation: fadeInUp 0.6s ease-out;
      padding: 40px 20px;
    }
    
    .task-card {
      background: white;
      border-radius: 16px;
      padding: 20px 24px;
      display: flex;
      align-items: center;
      gap: 16px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
      margin-bottom: 16px;
      cursor: pointer;
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
      border: 1px solid rgba(255,255,255,0.8);
    }
    
    .task-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
      transition: left 0.5s;
    }
    
    .task-card:hover::before {
      left: 100%;
    }
    
    .task-card:hover {
      transform: translateY(-4px) scale(1.02);
      box-shadow: 0 12px 40px rgba(0,0,0,0.15);
    }
    
    .task-icon {
      width: 56px;
      height: 56px;
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 28px;
      flex-shrink: 0;
      transition: transform 0.3s ease;
    }
    
    .task-card:hover .task-icon {
      transform: rotate(5deg) scale(1.1);
    }
    
    .task-content {
      flex: 1;
    }
    
    .task-title {
      font-weight: 700;
      font-size: 16px;
      margin-bottom: 6px;
      color: #333;
      letter-spacing: -0.3px;
    }
    
    .task-subtitle {
      font-size: 13px;
      color: #666;
      font-weight: 500;
    }
    
    .task-badge {
      width: 40px;
      height: 40px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      font-size: 16px;
      color: white;
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
      animation: pulse 2s infinite;
    }
    
    .welcome-center {
      background: linear-gradient(135deg, #14ad0f 0%, #43e97b 100%);
      border-radius: 24px;
      padding: 48px 36px;
      text-align: center;
      box-shadow: 0 20px 60px rgba(20, 173, 15, 0.4);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow: hidden;
      max-width: 800px;
      width: 100%;
    }
    
    .welcome-center::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
      animation: shimmer 8s infinite linear;
    }
    
    .welcome-illustration {
      width: 200px;
      height: 200px;
      background: rgba(255,255,255,0.2);
      backdrop-filter: blur(10px);
      border-radius: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 28px;
      box-shadow: 0 12px 40px rgba(0,0,0,0.2);
      position: relative;
      overflow: hidden;
      border: 2px solid rgba(255,255,255,0.3);
      z-index: 1;
    }
    
    .welcome-illustration::before {
      content: '';
      position: absolute;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 100%);
    }
    
    .illustration-icon {
      font-size: 90px;
      z-index: 1;
    }
    
    .welcome-title {
      font-size: 42px;
      font-weight: 800;
      color: white;
      margin-bottom: 16px;
      text-shadow: 0 4px 20px rgba(0,0,0,0.2);
      letter-spacing: -1px;
      z-index: 1;
    }
    
    .welcome-badge {
      display: inline-block;
      background: rgba(255,255,255,0.25);
      backdrop-filter: blur(10px);
      color: white;
      padding: 10px 28px;
      border-radius: 30px;
      font-weight: 700;
      font-size: 15px;
      margin-bottom: 24px;
      border: 1px solid rgba(255,255,255,0.3);
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      z-index: 1;
    }
    
    .welcome-description {
      color: rgba(255,255,255,0.95);
      font-size: 16px;
      line-height: 1.7;
      margin-bottom: 24px;
      max-width: 450px;
      font-weight: 500;
      z-index: 1;
    }
    
    .tip-box {
      background: rgba(255,255,255,0.2);
      backdrop-filter: blur(10px);
      border-left: 4px solid #43e97b;
      padding: 14px 20px;
      border-radius: 12px;
      display: inline-flex;
      align-items: center;
      gap: 10px;
      font-size: 14px;
      color: white;
      font-weight: 600;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      z-index: 1;
    }
    
    .quick-access-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
    }
    
    .quick-access-card {
      background: white;
      border-radius: 16px;
      padding: 24px;
      text-align: center;
      cursor: pointer;
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
      position: relative;
      overflow: hidden;
      border: 1px solid rgba(0,0,0,0.05);
    }
    
    .quick-access-card::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, rgba(255,255,255,0.5) 0%, transparent 100%);
      opacity: 0;
      transition: opacity 0.3s;
    }
    
    .quick-access-card:hover::after {
      opacity: 1;
    }
    
    .quick-access-card:hover {
      transform: translateY(-6px) scale(1.03);
      box-shadow: 0 12px 40px rgba(0,0,0,0.15);
    }
    
    .quick-access-icon {
      font-size: 40px;
      margin-bottom: 12px;
      filter: drop-shadow(0 4px 8px rgba(0,0,0,0.1));
      transition: transform 0.3s ease;
    }
    
    .quick-access-card:hover .quick-access-icon {
      transform: scale(1.2) rotate(5deg);
    }
    
    .quick-access-title {
      font-weight: 700;
      font-size: 15px;
      color: #333;
      letter-spacing: -0.2px;
    }
    
    .quick-access-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 16px;
      padding: 20px 24px;
      margin-bottom: 18px;
      box-shadow: 0 8px 30px rgba(102, 126, 234, 0.3);
      display: flex;
      align-items: center;
      gap: 14px;
    }
    
    .quick-access-header-icon {
      width: 48px;
      height: 48px;
      background: rgba(255,255,255,0.25);
      backdrop-filter: blur(10px);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 24px;
      border: 1px solid rgba(255,255,255,0.3);
    }
    
    .quick-access-header-title {
      font-weight: 800;
      font-size: 20px;
      color: white;
      letter-spacing: -0.5px;
    }
    
    .notification-dot {
      position: absolute;
      top: 10px;
      right: 10px;
      width: 28px;
      height: 28px;
      background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
      font-weight: 800;
      color: white;
      box-shadow: 0 4px 12px rgba(255,107,107,0.4);
      animation: pulse 2s infinite;
      z-index: 10;
    }
    
    @media (max-width: 1200px) {
      .dashboard-container {
        grid-template-columns: 1fr;
      }
      
      .welcome-center {
        order: -1;
      }
    }
    
    @media (max-width: 768px) {
      .quick-access-grid {
        grid-template-columns: 1fr;
      }
      
      .welcome-title {
        font-size: 32px;
      }
      
      .task-card {
        padding: 12px 10px;
        gap: 8px;
        flex-direction: column;
        text-align: center;
      }
      
      .task-icon {
        width: 40px;
        height: 40px;
        font-size: 20px;
      }
      
      .task-title {
        font-size: 11px;
        margin-bottom: 2px;
      }
      
      .task-subtitle {
        font-size: 9px;
      }
      
      .task-badge {
        width: 28px;
        height: 28px;
        font-size: 12px;
      }
    }
    
    @media (max-width: 480px) {
      .task-card {
        padding: 8px 6px;
        gap: 6px;
        border-radius: 12px;
      }
      
      .task-icon {
        width: 32px;
        height: 32px;
        font-size: 16px;
        border-radius: 8px;
      }
      
      .task-title {
        font-size: 9px;
        margin-bottom: 1px;
      }
      
      .task-subtitle {
        font-size: 7px;
        line-height: 1.2;
      }
      
      .task-badge {
        width: 24px;
        height: 24px;
        font-size: 10px;
        border-radius: 8px;
      }
    }
  </style>
  
  <div class="dashboard-container">
    
    <!-- Center - Welcome Section -->
    <div class="welcome-center">
      <div class="welcome-illustration">
        <img src="admin-panel.png" alt="Admin Panel" style="width:120px;height:120px;border-radius:20px;">
      </div>
      
      <h2 class="welcome-title">Welcome, <?php echo htmlspecialchars($admin_fullname ?: 'admin'); ?>!</h2>
      
      <div class="welcome-badge">Barangay Admin Dashboard</div>
      
      <p class="welcome-description">
        Manage residents, officials, certificates, incidents, and announcements with ease.
      </p>
      
      <div class="tip-box">
        <i class="bi bi-lightbulb" style="color: #43e97b;"></i>
        <span><strong>Tip:</strong> Use the sidebar to quickly access different modules</span>
      </div>
    </div>
  </div>
  
  <script>
    // Auto-calculate age based on birthdate
    function calculateAge(birthdateStr) {
      if (!birthdateStr) return '';
      const today = new Date();
      const birthDate = new Date(birthdateStr);
      let age = today.getFullYear() - birthDate.getFullYear();
      const m = today.getMonth() - birthDate.getMonth();
      if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
        age--;
      }
      return age;
    }
    
    const birthdateInput = document.getElementById('birthdate');
    if (birthdateInput) {
      birthdateInput.addEventListener('change', function() {
        const birthdateVal = this.value;
        const ageInput = document.getElementById('age');
        if (ageInput) {
          ageInput.value = calculateAge(birthdateVal);
        }
      });
      
      // On page load, set age if birthdate exists
      window.addEventListener('DOMContentLoaded', function() {
        if (birthdateInput && birthdateInput.value) {
          const ageInput = document.getElementById('age');
          if (ageInput) {
            ageInput.value = calculateAge(birthdateInput.value);
          }
        }
      });
    }
  </script>
</div>


<div id="panel-view-residents" class="panel-section" style="display:none;">
<?php
// --- Residents listing logic from rresidents.php ---
include 'config.php';
// -- Initialize variables so they always exist
$residents = [];
$senior_count = $pwd_count = $male_count = $female_count = $lgbtq_count = 0;

$filter_gender = $_GET['gender'] ?? '';
$filter_age_group = $_GET['age_group'] ?? '';
$filter_name = strtolower(trim($_GET['name'] ?? ''));
$pwd_filter = $_GET['pwd_filter'] ?? '';
$senior_filter = $_GET['senior_filter'] ?? '';
$filter_household = $_GET['household_id'] ?? '';
$filter_unique_id = $_GET['unique_id'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 7;
$offset = ($page - 1) * $limit;
$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM residents WHERE 1=1";
$params = [];
$types = "";
if ($filter_gender && $filter_gender !== 'All') {
    $sql .= " AND sex = ?";
    $params[] = $filter_gender;
    $types .= "s";
}
if ($filter_age_group) {
    if ($filter_age_group === 'below_18') $sql .= " AND age <= 18";
    if ($filter_age_group === 'above_18') $sql .= " AND age > 18";
    if ($filter_age_group === 'below_60') $sql .= " AND age <= 60";
    if ($filter_age_group === 'above_60') $sql .= " AND age > 60";
}
if ($filter_name !== '') {
    $sql .= " AND LOWER(CONCAT(surname, ' ', first_name, ' ', COALESCE(middle_name,''))) LIKE ?";
    $params[] = "%$filter_name%";
    $types .= "s";
}
if ($filter_household !== '') {
    $sql .= " AND household_id = ?";
    $params[] = $filter_household;
    $types .= "s";
}
if ($pwd_filter !== '') {
    $sql .= " AND is_pwd = ?";
    $params[] = $pwd_filter;
    $types .= "s";
}
if ($senior_filter === 'Yes') {
    $sql .= " AND age > 60";
} elseif ($senior_filter === 'No') {
    $sql .= " AND age <= 60";
}
if ($filter_unique_id !== '') {
    $sql .= " AND unique_id = ?";
    $params[] = $filter_unique_id;
    $types .= "s";
}
$sql .= " ORDER BY surname ASC LIMIT $limit OFFSET $offset";
if (!isset($conn) || !$conn) {
    $residents = [];
} else {
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if (!empty($params)) {
            $refs = [];
            $refs[] = &$types;
            for ($i = 0; $i < count($params); $i++) {
                $refs[] = &$params[$i];
            }
            call_user_func_array([$stmt, 'bind_param'], $refs);
        }
        if ($stmt->execute()) {
            if (method_exists($stmt, 'get_result')) {
                $result = $stmt->get_result();
                if ($result) {
                    $residents = $result->fetch_all(MYSQLI_ASSOC);
                } else {
                    $residents = [];
                }
            } else {
                $meta = $stmt->result_metadata();
                if ($meta) {
                    $columns = [];
                    $row = [];
                    while ($field = $meta->fetch_field()) {
                        $columns[] = $field->name;
                        $row[$field->name] = null;
                        $bind[] = &$row[$field->name];
                    }
                    call_user_func_array([$stmt, 'bind_result'], $bind);
                    $residents = [];
                    while ($stmt->fetch()) {
                        $r = [];
                        foreach ($row as $k => $v) $r[$k] = $v;
                        $residents[] = $r;
                    }
                } else {
                    $residents = [];
                }
            }
        } else {
            $residents = [];
        }
        $stmt->close();
    } else {
        if (empty($params)) {
            $res = $conn->query($sql);
            if ($res) {
                $residents = $res->fetch_all(MYSQLI_ASSOC);
            } else {
                $residents = [];
            }
        } else {
            $residents = [];
        }
    }
}
foreach ($residents as $res) {
    if (isset($res['age']) && is_numeric($res['age']) && $res['age'] > 60) $senior_count++;
    if (isset($res['is_pwd']) && $res['is_pwd'] === "Yes") $pwd_count++;
    if (isset($res['sex'])) {
        switch (strtolower($res['sex'])) {
            case 'male': $male_count++; break;
            case 'female': $female_count++; break;
            case 'lgbtq': $lgbtq_count++; break;
        }
    }
}
$total_rows = 0;
$total_pages = 1;
if (isset($conn) && $conn) {
    $result_total = $conn->query("SELECT FOUND_ROWS() as total");
    if ($result_total) {
        $row_total = $result_total->fetch_assoc();
        $total_rows = intval($row_total['total']);
        $total_pages = max(1, ceil($total_rows / $limit));
    }
}

// --- AJAX: Update resident from edit panel ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_resident_panel'])) {
  include 'config.php';
  session_start();
  $admin_username = $_SESSION['admin_username'] ?? 'Unknown';
  $unique_id = $_POST['unique_id'];
  $surname = $_POST['surname'];
  $first_name = $_POST['first_name'];
  $middle_name = $_POST['middle_name'];
  $age = $_POST['age'];
  $sex = $_POST['sex'];
  $birthdate = $_POST['birthdate'];
  $place_of_birth = $_POST['place_of_birth'];
  $civil_status = $_POST['civil_status'];
  $citizenship = $_POST['citizenship'];
  $occupation_skills = $_POST['occupation_skills'];
  $education = $_POST['education'];
  $is_pwd = $_POST['is_pwd'];
  $address = $_POST['address'];
  $household_id = $_POST['household_id'];
  $is_head = $_POST['is_head'] ?? 'No';
  $update = $conn->prepare("
    UPDATE residents 
    SET surname=?, first_name=?, middle_name=?, age=?, sex=?, birthdate=?, place_of_birth=?, civil_status=?, 
      citizenship=?, occupation_skills=?, education=?, is_pwd=?, address=?, household_id=?, is_head=? 
    WHERE unique_id=?
  ");
  $update->bind_param(
    "sssisissssssssss",
    $surname,
    $first_name,
    $middle_name,
    $age,
    $sex,
    $birthdate,
    $place_of_birth,
    $civil_status,
    $citizenship,
    $occupation_skills,
    $education,
    $is_pwd,
    $address,
    $household_id,
    $is_head,
    $unique_id
  );
  if ($update->execute()) {
    $action = "Edited resident: $surname, $first_name (ID: $unique_id)";
    $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
    $log->bind_param("ss", $admin_username, $action);
    $log->execute();
    $log->close();
    echo json_encode(['success'=>true]);
  } else {
    echo json_encode(['success'=>false, 'error'=>$conn->error]);
  }
  exit;
}
?>
<div class="container py-4">
  <div class="card shadow-lg p-3 mb-3" style="font-size:11px;">
    <h2 class="fw-bold text-success mb-2" style="font-size:1.05rem;"><i data-lucide="users"></i> Residents</h2>
  <form method="GET" class="filter-form mb-2" id="viewResidentsFilterForm" style="font-size:11px;">
    <div class="d-flex flex-wrap align-items-end gap-2 mb-2" style="row-gap:0.5rem; column-gap:1rem;">
      <div class="form-group">
        <label class="form-label mb-1" style="font-size:0.93em;">Search Name:</label>
        <input type="text" name="name" class="form-control form-control-sm" placeholder="Enter name" value="<?= htmlspecialchars($_GET['name'] ?? '') ?>" style="min-width:120px;max-width:140px;">
      </div>
      <div class="form-group">
        <label class="form-label mb-1" style="font-size:0.93em;">Resident ID:</label>
        <input type="text" name="unique_id" class="form-control form-control-sm" placeholder="Enter ID" value="<?= htmlspecialchars($_GET['unique_id'] ?? '') ?>" style="min-width:90px;max-width:110px;">
      </div>
      <div class="form-group">
        <label class="form-label mb-1" style="font-size:0.93em;">Household No.:</label>
        <input type="text" name="household_id" class="form-control form-control-sm" placeholder="Enter household number" value="<?= htmlspecialchars($filter_household) ?>" style="min-width:90px;max-width:120px;">
      </div>
      <div class="form-group">
        <label class="form-label mb-1" style="font-size:0.93em;">Gender:</label>
        <select name="gender" class="form-select form-select-sm" style="min-width:70px;">
          <option value="All" <?= ($filter_gender === 'All' || $filter_gender === '') ? 'selected' : '' ?>>All</option>
          <option value="Male" <?= $filter_gender === 'Male' ? 'selected' : '' ?>>Male</option>
          <option value="Female" <?= $filter_gender === 'Female' ? 'selected' : '' ?>>Female</option>
          <option value="LGBTQ" <?= $filter_gender === 'LGBTQ' ? 'selected' : '' ?>>LGBTQ</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label mb-1" style="font-size:0.93em;">Age Group:</label>
        <select name="age_group" class="form-select form-select-sm" style="min-width:90px;">
          <option value="" <?= $filter_age_group === '' ? 'selected' : '' ?>>All</option>
          <option value="below_18" <?= $filter_age_group === 'below_18' ? 'selected' : '' ?>>18 and below</option>
          <option value="above_18" <?= $filter_age_group === 'above_18' ? 'selected' : '' ?>>Above 18</option>
          <option value="below_60" <?= $filter_age_group === 'below_60' ? 'selected' : '' ?>>60 and below</option>
          <option value="above_60" <?= $filter_age_group === 'above_60' ? 'selected' : '' ?>>Above 60</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label mb-1" style="font-size:0.93em;">PWD:</label>
        <select name="pwd_filter" class="form-select form-select-sm" style="min-width:70px;">
          <option value="">All</option>
          <option value="Yes" <?= (isset($_GET['pwd_filter']) && $_GET['pwd_filter'] === 'Yes') ? 'selected' : '' ?>>PWD Only</option>
          <option value="No" <?= (isset($_GET['pwd_filter']) && $_GET['pwd_filter'] === 'No') ? 'selected' : '' ?>>Non-PWD Only</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label mb-1" style="font-size:0.93em;">Senior Citizen:</label>
        <select name="senior_filter" class="form-select form-select-sm" style="min-width:90px;">
          <option value="">All</option>
          <option value="Yes" <?= (isset($_GET['senior_filter']) && $_GET['senior_filter'] === 'Yes') ? 'selected' : '' ?>>Senior Only</option>
          <option value="No" <?= (isset($_GET['senior_filter']) && $_GET['senior_filter'] === 'No') ? 'selected' : '' ?>>Non-Senior Only</option>
        </select>
      </div>
      <div class="d-flex align-items-end gap-2 flex-wrap mt-2">
  <input type="submit" value="Filter" class="btn btn-sm btn-outline-dark" id="view-residents-filter-btn">
  <button type="button" class="btn btn-sm btn-outline-secondary" id="view-residents-reset-btn">Reset</button>
  <button type="button" id="toggleRecycleBinBtn" class="btn btn-sm" style="background:#f44336; color:white; font-weight:bold;">🗑️ View Recycle Bin</button>

  <button type="button" id="toggleHouseholdsBtn" class="btn btn-sm btn-primary">View Households</button>
      </div>
    </div>
    <script>
    // Handle filter form submission without page reload
    document.addEventListener('DOMContentLoaded', function() {
      var form = document.getElementById('viewResidentsFilterForm');
      if (form) {
        form.addEventListener('submit', function(e) {
          e.preventDefault();
          const params = new URLSearchParams(new FormData(form));
          params.set('panel', 'view-residents');
          
          // Show loading state
          var filterBtn = document.getElementById('view-residents-filter-btn');
          var originalText = filterBtn.value;
          filterBtn.disabled = true;
          filterBtn.value = 'Loading...';
          
          // Fetch and update table content
          fetch('admin_dashboard.php?' + params.toString(), { 
            method: 'GET', 
            credentials: 'same-origin' 
          })
          .then(function(response) { return response.text(); })
          .then(function(html) {
            // Parse the response and extract the table
            var parser = new DOMParser();
            var doc = parser.parseFromString(html, 'text/html');
            
            // Update table container
            var newTableContainer = doc.querySelector('#residentsTableContainer');
            var currentTableContainer = document.querySelector('#residentsTableContainer');
            if (newTableContainer && currentTableContainer) {
              currentTableContainer.innerHTML = newTableContainer.innerHTML;
              // Re-initialize Lucide icons if needed
              if (typeof lucide !== 'undefined') lucide.createIcons();
              
              // Re-attach pagination events
              if (typeof attachResidentsPaginationEvents === 'function') {
                attachResidentsPaginationEvents();
              }
              
              // Re-attach view button events after filter
              if (typeof attachViewBtnEvents === 'function') {
                attachViewBtnEvents();
              }
            }
            
            // Reset button
            filterBtn.disabled = false;
            filterBtn.value = originalText;
          })
          .catch(function(error) {
            console.error('Error fetching residents:', error);
            alert('Error loading data. Please refresh the page.');
            filterBtn.disabled = false;
            filterBtn.value = originalText;
          });
        });
        
        // Handle reset button
        var resetBtn = document.getElementById('view-residents-reset-btn');
        if (resetBtn) {
          resetBtn.addEventListener('click', function() {
            // Clear all form inputs
            form.querySelectorAll('input[type="text"]').forEach(function(input) {
              input.value = '';
            });
            form.querySelectorAll('select').forEach(function(select) {
              select.selectedIndex = 0;
            });
            
            // Trigger form submission to reload with no filters
            form.dispatchEvent(new Event('submit'));
          });
        }
      }
    });
    </script>
  </form>
      
    <?php if (empty($residents)): ?>
      <p>No residents found based on selected filters.</p>
    <?php else: ?>
      <div id="residentsTableContainer">
      <div class="table-responsive">
  <table class="table table-striped table-hover align-middle shadow rounded-4" style="overflow:hidden;font-size:0.93em;">
          <thead class="table-success" style="font-size:11px;">
            <tr>
              <th>ID</th>
              <th>Full Name</th>
              <th>Age</th>
              <th>Gender</th>
              <th>Birthdate</th>
              <th>Place of Birth</th>
              <th>Civil Status</th>
              <th>Citizenship</th>
              <th>Occupation / Skills</th>
              <th>Education</th>
              <th>PWD</th>
              <th>Address</th>
              <th>Household No.</th>
              <th>Relationship</th>
              <th>Head?</th>
              
              <th>Actions</th>
            </tr>
          </thead>
          <tbody style="font-size:11px;">
          <?php foreach ($residents as $res): ?>
            <tr class="<?= (isset($res['is_head']) && $res['is_head'] === 'Yes') ? 'table-light fw-bold' : '' ?>">
              <td><?= htmlspecialchars($res['unique_id'] ?? '') ?></td>
              <td><?= htmlspecialchars(($res['surname'] ?? '') . ', ' . ($res['first_name'] ?? '') . ' ' . ($res['middle_name'] ?? '')) ?></td>
              <td><?= htmlspecialchars($res['age'] ?? '') ?></td>
              <td><?= htmlspecialchars($res['sex'] ?? '') ?></td>
              <td><?= htmlspecialchars($res['birthdate'] ?? '') ?></td>
              <td><?= htmlspecialchars($res['place_of_birth'] ?? '') ?></td>
              <td><?= htmlspecialchars($res['civil_status'] ?? '') ?></td>
              <td><?= htmlspecialchars($res['citizenship'] ?? '') ?></td>
              <td><?= htmlspecialchars($res['occupation_skills'] ?? '') ?></td>
              <td><?= htmlspecialchars($res['education'] ?? '') ?></td>
              <td><?= htmlspecialchars($res['is_pwd'] ?? '') ?></td>
              <td><?= htmlspecialchars($res['address'] ?? '') ?></td>
              <td><?= htmlspecialchars($res['household_id'] ?? '') ?></td>
              <td><?= htmlspecialchars($res['relationship'] ?? '') ?></td>
              <td><?= (isset($res['is_head']) && $res['is_head'] === 'Yes') ? '<span class="badge bg-success">Head</span>' : '' ?></td>
              
              <td class="action-links text-center">
                <button type="button" class="btn btn-sm btn-outline-info view-resident-btn" 
                  data-unique-id="<?= htmlspecialchars($res['unique_id'] ?? '') ?>" 
                  data-bs-toggle="tooltip" data-bs-placement="top" title="View Resident">
                  <i class="bi bi-eye"></i>
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <!-- View Resident Modal -->
      <div class="modal fade" id="viewResidentModal" tabindex="-1" aria-labelledby="viewResidentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
          <div class="modal-content">
            <div class="modal-header bg-info text-white">
              <h5 class="modal-title" id="viewResidentModalLabel"><i class="bi bi-person-circle"></i> Resident Profile</h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="viewResidentModalBody">
              <div class="text-center p-5">
                <div class="spinner-border text-info" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3">Loading resident information...</p>
              </div>
            </div>
            <div class="modal-footer" style="border-top: 2px solid #e0e0e0; padding: 20px; background: #f8f9fa;">
              <button type="button" class="btn btn-warning" id="resetPasswordFromModal">
                <i class="bi bi-key"></i> Reset Password
              </button>
              <button type="button" class="btn btn-primary" id="editResidentFromModal">
                <i class="bi bi-pencil-square"></i> Edit
              </button>
              <button type="button" class="btn btn-danger" id="deleteResidentFromModal">
                <i class="bi bi-trash"></i> Delete
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Reset Password Confirmation Modal -->
      <div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
              <h5 class="modal-title" id="resetPasswordModalLabel"><i class="bi bi-key"></i> Reset Resident Password</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <p class="mb-3">You are about to reset the password for:</p>
              <div class="alert alert-info mb-3">
                <strong id="resetPasswordResidentName"></strong><br>
                <small class="text-muted">User ID: <span id="resetPasswordResidentId"></span></small>
              </div>
              <p class="text-danger mb-3"><i class="bi bi-exclamation-triangle"></i> <strong>Confirm User ID to proceed:</strong></p>
              <div class="input-group mb-2">
                <span class="input-group-text"><i class="bi bi-fingerprint"></i></span>
                <input type="text" class="form-control" id="confirmResetUserId" placeholder="Enter User ID to confirm">
              </div>
              <small class="text-muted">The resident will need to set a new password on their next login.</small>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="button" id="confirmResetPasswordBtn" class="btn btn-warning">Reset Password</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Delete Confirmation Modal -->
      <div class="modal fade" id="deleteResidentModal" tabindex="-1" aria-labelledby="deleteResidentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header bg-danger text-white">
              <h5 class="modal-title" id="deleteResidentModalLabel"><i class="bi bi-exclamation-triangle"></i> Confirm Delete</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              Are you sure you want to move <span id="residentNameToDelete" class="fw-bold text-danger"></span> to the recycle bin?
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <a href="#" id="confirmDeleteResidentBtn" class="btn btn-danger">Yes, Delete</a>
            </div>
          </div>
        </div>
      </div>
      <div style="margin-top:10px; text-align:center;">
        <button id="prevPageBtn" class="btn btn-outline-success me-2" <?= ($page <= 1) ? 'disabled' : '' ?>>Previous</button>
        <span class="fw-semibold">Page <span id="currentPageNum"><?= $page ?></span> of <span id="totalPagesNum"><?= $total_pages ?></span></span>
        <button id="nextPageBtn" class="btn btn-outline-success ms-2" <?= ($page >= $total_pages) ? 'disabled' : '' ?>>Next</button>
      </div>
      </div> <!-- end residentsTableContainer -->
      <script>
      // AJAX pagination for Residents table with re-attached event listeners and reliable container replacement
      function getFilterParams() {
        const form = document.getElementById('viewResidentsFilterForm');
        const params = new URLSearchParams(new FormData(form));
        params.set('panel', 'view-residents');
        return params;
      }
      function loadResidentsPage(page) {
        const params = getFilterParams();
        params.set('page', page);
        fetch('admin_dashboard.php?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
          .then(res => res.text())
          .then(html => {
            // Extract only the residentsTableContainer
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newContainer = doc.querySelector('#residentsTableContainer');
            if (newContainer) {
              document.getElementById('residentsTableContainer').replaceWith(newContainer);
              attachResidentsPaginationEvents(); // Re-attach events after DOM update
            }
          });
      }
      function attachResidentsPaginationEvents() {
        const prevBtn = document.getElementById('prevPageBtn');
        const nextBtn = document.getElementById('nextPageBtn');
        if (prevBtn) prevBtn.onclick = function() {
          const current = parseInt(document.getElementById('currentPageNum').textContent);
          if (current > 1) loadResidentsPage(current - 1);
        };
        if (nextBtn) nextBtn.onclick = function() {
          const current = parseInt(document.getElementById('currentPageNum').textContent);
          const total = parseInt(document.getElementById('totalPagesNum').textContent);
          if (current < total) loadResidentsPage(current + 1);
        };
        // Re-attach edit button events after table update
        if (typeof attachEditResidentBtnEvents === 'function') {
          attachEditResidentBtnEvents();
        }
      }
      document.addEventListener('DOMContentLoaded', function() {
        attachResidentsPaginationEvents();
      });

      // Function to attach delete button events
      function attachDeleteBtnEvents() {
        var deleteBtns = document.querySelectorAll('.delete-btn');
        var deleteModal = document.getElementById('deleteResidentModal');
        var residentNameToDelete = document.getElementById('residentNameToDelete');
        var confirmDeleteResidentBtn = document.getElementById('confirmDeleteResidentBtn');
        var deleteId = null;
        deleteBtns.forEach(function(btn) {
          btn.onclick = function() {
            deleteId = btn.getAttribute('data-id');
            var name = btn.getAttribute('data-name');
            residentNameToDelete.textContent = name;
            confirmDeleteResidentBtn.removeAttribute('href'); // prevent default navigation
            var modal = new bootstrap.Modal(deleteModal);
            modal.show();
          };
        });
        if (confirmDeleteResidentBtn) {
          // Remove any existing event listeners by cloning the button
          var newBtn = confirmDeleteResidentBtn.cloneNode(true);
          confirmDeleteResidentBtn.parentNode.replaceChild(newBtn, confirmDeleteResidentBtn);
          confirmDeleteResidentBtn = newBtn;
          
          confirmDeleteResidentBtn.onclick = function(e) {
            e.preventDefault();
            if (!deleteId) return;
            
            // Disable button to prevent double-clicks
            confirmDeleteResidentBtn.disabled = true;
            confirmDeleteResidentBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Deleting...';
            
            // AJAX delete
            fetch('rdelete_residents.php?id=' + encodeURIComponent(deleteId) + '&ajax=1', {
              method: 'GET',
              headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(res) {
              if (!res.ok) {
                throw new Error('HTTP error! status: ' + res.status);
              }
              return res.text();
            })
            .then(function(text) {
              console.log('Delete response:', text);
              try {
                var data = JSON.parse(text);
                
                // Hide modal
                var modal = bootstrap.Modal.getInstance(deleteModal);
                if (modal) modal.hide();
                
                // Reset button
                confirmDeleteResidentBtn.disabled = false;
                confirmDeleteResidentBtn.innerHTML = 'Delete';
                
                if (data.success) {
                  // Show success message
                  var successMsg = document.createElement('div');
                  successMsg.className = 'alert alert-success';
                  successMsg.innerHTML = '<strong>✓ Deleted!</strong> Resident moved to recycle bin.';
                  successMsg.style.position = 'fixed';
                  successMsg.style.top = '20px';
                  successMsg.style.right = '20px';
                  successMsg.style.zIndex = '9999';
                  document.body.appendChild(successMsg);
                  setTimeout(function() {
                    successMsg.remove();
                  }, 3000);
                  
                  // Refresh table
                  var current = 1;
                  var pageNumEl = document.getElementById('currentPageNum');
                  if (pageNumEl) current = parseInt(pageNumEl.textContent) || 1;
                  loadResidentsPage(current);
                } else {
                  alert('Error: ' + (data.error || 'Failed to delete resident'));
                }
              } catch (e) {
                console.error('JSON parse error:', e);
                console.error('Response text:', text);
                
                // Reset button
                confirmDeleteResidentBtn.disabled = false;
                confirmDeleteResidentBtn.innerHTML = 'Delete';
                
                alert('Invalid response from server. Check console for details.');
              }
            })
            .catch(function(error) {
              console.error('Delete error:', error);
              
              // Reset button
              confirmDeleteResidentBtn.disabled = false;
              confirmDeleteResidentBtn.innerHTML = 'Delete';
              
              // Show error message
              alert('Error deleting resident: ' + error.message);
            });
          };
        }
      }

      // Function to attach view button events
      function attachViewBtnEvents() {
        var viewBtns = document.querySelectorAll('.view-resident-btn');
        var viewModal = document.getElementById('viewResidentModal');
        var viewModalBody = document.getElementById('viewResidentModalBody');
        var currentResidentId = null;
        
        viewBtns.forEach(function(btn) {
          btn.onclick = function() {
            currentResidentId = btn.getAttribute('data-unique-id');
            
            // Show loading state
            viewModalBody.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-info" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-3">Loading resident information...</p></div>';
            
            // Show modal
            var modal = new bootstrap.Modal(viewModal);
            modal.show();
            
            // Fetch resident data
            fetch('admin_dashboard.php?get_resident_ajax=1&id=' + encodeURIComponent(currentResidentId), {
              method: 'GET',
              headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(res) {
              if (!res.ok) throw new Error('HTTP error! status: ' + res.status);
              return res.json();
            })
            .then(function(data) {
              if (data.success && data.resident) {
                displayResidentProfile(data.resident);
              } else {
                viewModalBody.innerHTML = '<div class="alert alert-danger m-4">Error loading resident data</div>';
              }
            })
            .catch(function(error) {
              console.error('Error:', error);
              viewModalBody.innerHTML = '<div class="alert alert-danger m-4">Error loading resident data: ' + error.message + '</div>';
            });
          };
        });
        
        // Handle Edit button in modal
        var editBtn = document.getElementById('editResidentFromModal');
        if (editBtn) {
          editBtn.onclick = function() {
            if (currentResidentId) {
              // Close view modal
              var modal = bootstrap.Modal.getInstance(viewModal);
              if (modal) modal.hide();
              
              // Trigger edit functionality
              var editBtnElement = document.querySelector('.edit-resident-btn[data-unique-id="' + currentResidentId + '"]');
              if (editBtnElement) {
                editBtnElement.click();
              } else {
                // Fallback: manually trigger edit panel
                document.querySelectorAll('.panel-section').forEach(function(panel) { panel.style.display = 'none'; });
                var editPanel = document.getElementById('panel-edit-resident');
                if (editPanel) {
                  editPanel.style.display = 'block';
                  editPanel.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-3">Loading resident data...</p></div>';
                  
                  var url = new URL(window.location.href);
                  url.searchParams.set('panel', 'edit-resident');
                  url.searchParams.set('editr_id', currentResidentId);
                  history.replaceState(null, '', url.toString());
                  
                  fetch('admin_dashboard.php?ajax_get_resident=' + currentResidentId, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                  })
                  .then(function(response) {
                    if (!response.ok) throw new Error('HTTP error! status: ' + response.status);
                    return response.json();
                  })
                  .then(function(data) {
                    if (data.success && data.resident) {
                      if (typeof loadEditForm === 'function') {
                        loadEditForm(data.resident);
                      }
                    } else {
                      editPanel.innerHTML = '<div class="alert alert-danger m-4">' + (data.message || 'Error loading resident data') + '</div>';
                    }
                  })
                  .catch(function(error) {
                    console.error('Error:', error);
                    editPanel.innerHTML = '<div class="alert alert-danger m-4">Error loading resident data: ' + error.message + '</div>';
                  });
                }
              }
            }
          };
        }
        
        // Handle Reset Password button in modal
        var resetPasswordBtn = document.getElementById('resetPasswordFromModal');
        if (resetPasswordBtn) {
          resetPasswordBtn.onclick = function() {
            if (currentResidentId) {
              // Close view modal
              var modal = bootstrap.Modal.getInstance(viewModal);
              if (modal) modal.hide();
              
              // Fetch resident data for reset password confirmation
              fetch('admin_dashboard.php?get_resident_ajax=1&id=' + encodeURIComponent(currentResidentId), {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
              })
              .then(function(res) { return res.json(); })
              .then(function(data) {
                if (data.success && data.resident) {
                  var residentName = data.resident.surname + ', ' + data.resident.first_name + ' ' + (data.resident.middle_name || '');
                  var residentId = data.resident.unique_id;
                  
                  // Show reset password confirmation modal
                  var resetModal = document.getElementById('resetPasswordModal');
                  var resetPasswordResidentName = document.getElementById('resetPasswordResidentName');
                  var resetPasswordResidentId = document.getElementById('resetPasswordResidentId');
                  var confirmResetUserId = document.getElementById('confirmResetUserId');
                  var confirmResetPasswordBtn = document.getElementById('confirmResetPasswordBtn');
                  
                  resetPasswordResidentName.textContent = residentName;
                  resetPasswordResidentId.textContent = residentId;
                  confirmResetUserId.value = '';
                  
                  // Set up reset password confirmation
                  var newBtn = confirmResetPasswordBtn.cloneNode(true);
                  confirmResetPasswordBtn.parentNode.replaceChild(newBtn, confirmResetPasswordBtn);
                  confirmResetPasswordBtn = newBtn;
                  
                  confirmResetPasswordBtn.onclick = function(e) {
                    e.preventDefault();
                    
                    // Validate user ID confirmation
                    var enteredId = confirmResetUserId.value.trim();
                    var residentIdStr = String(residentId).trim();
                    if (enteredId !== residentIdStr) {
                      alert('User ID does not match! Please enter the correct User ID to confirm.');
                      confirmResetUserId.focus();
                      return;
                    }
                    
                    confirmResetPasswordBtn.disabled = true;
                    confirmResetPasswordBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Resetting...';
                    
                    // Create form data
                    var formData = new FormData();
                    formData.append('reset_password_panel', '1');
                    formData.append('userid', residentId);
                    
                    fetch('admin_dashboard.php', {
                      method: 'POST',
                      body: formData,
                      headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(function(res) {
                      if (!res.ok) throw new Error('HTTP error! status: ' + res.status);
                      return res.text();
                    })
                    .then(function(text) {
                      var resetModalInstance = bootstrap.Modal.getInstance(resetModal);
                      if (resetModalInstance) resetModalInstance.hide();
                      
                      confirmResetPasswordBtn.disabled = false;
                      confirmResetPasswordBtn.innerHTML = 'Reset Password';
                      
                      // Show success message
                      var successMsg = document.createElement('div');
                      successMsg.className = 'alert alert-success';
                      successMsg.innerHTML = '<strong>✓ Success!</strong> Password reset for User ID: ' + residentId + '. Resident must set a new password on next login.';
                      successMsg.style.position = 'fixed';
                      successMsg.style.top = '20px';
                      successMsg.style.right = '20px';
                      successMsg.style.zIndex = '9999';
                      successMsg.style.minWidth = '350px';
                      successMsg.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
                      document.body.appendChild(successMsg);
                      setTimeout(function() { successMsg.remove(); }, 4000);
                    })
                    .catch(function(error) {
                      console.error('Reset password error:', error);
                      confirmResetPasswordBtn.disabled = false;
                      confirmResetPasswordBtn.innerHTML = 'Reset Password';
                      alert('Error resetting password: ' + error.message);
                    });
                  };
                  
                  var resetModalInstance = new bootstrap.Modal(resetModal);
                  resetModalInstance.show();
                }
              });
            }
          };
        }
        
        // Handle Delete button in modal
        var deleteBtn = document.getElementById('deleteResidentFromModal');
        if (deleteBtn) {
          deleteBtn.onclick = function() {
            if (currentResidentId) {
              // Close view modal
              var modal = bootstrap.Modal.getInstance(viewModal);
              if (modal) modal.hide();
              
              // Fetch resident name for delete confirmation
              fetch('admin_dashboard.php?get_resident_ajax=1&id=' + encodeURIComponent(currentResidentId), {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
              })
              .then(function(res) { return res.json(); })
              .then(function(data) {
                if (data.success && data.resident) {
                  var residentName = data.resident.surname + ', ' + data.resident.first_name;
                  
                  // Show delete confirmation modal
                  var deleteModal = document.getElementById('deleteResidentModal');
                  var residentNameToDelete = document.getElementById('residentNameToDelete');
                  var confirmDeleteResidentBtn = document.getElementById('confirmDeleteResidentBtn');
                  
                  residentNameToDelete.textContent = residentName;
                  
                  // Set up delete confirmation
                  var newBtn = confirmDeleteResidentBtn.cloneNode(true);
                  confirmDeleteResidentBtn.parentNode.replaceChild(newBtn, confirmDeleteResidentBtn);
                  confirmDeleteResidentBtn = newBtn;
                  
                  confirmDeleteResidentBtn.onclick = function(e) {
                    e.preventDefault();
                    confirmDeleteResidentBtn.disabled = true;
                    confirmDeleteResidentBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Deleting...';
                    
                    fetch('rdelete_residents.php?id=' + encodeURIComponent(currentResidentId) + '&ajax=1', {
                      method: 'GET',
                      headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(function(res) {
                      if (!res.ok) throw new Error('HTTP error! status: ' + res.status);
                      return res.text();
                    })
                    .then(function(text) {
                      try {
                        var data = JSON.parse(text);
                        var modal = bootstrap.Modal.getInstance(deleteModal);
                        if (modal) modal.hide();
                        
                        confirmDeleteResidentBtn.disabled = false;
                        confirmDeleteResidentBtn.innerHTML = 'Yes, Delete';
                        
                        if (data.success) {
                          var successMsg = document.createElement('div');
                          successMsg.className = 'alert alert-success';
                          successMsg.innerHTML = '<strong>✓ Deleted!</strong> Resident moved to recycle bin.';
                          successMsg.style.position = 'fixed';
                          successMsg.style.top = '20px';
                          successMsg.style.right = '20px';
                          successMsg.style.zIndex = '9999';
                          document.body.appendChild(successMsg);
                          setTimeout(function() { successMsg.remove(); }, 3000);
                          
                          var current = 1;
                          var pageNumEl = document.getElementById('currentPageNum');
                          if (pageNumEl) current = parseInt(pageNumEl.textContent) || 1;
                          loadResidentsPage(current);
                        } else {
                          alert('Error: ' + (data.error || 'Failed to delete resident'));
                        }
                      } catch (e) {
                        console.error('JSON parse error:', e);
                        confirmDeleteResidentBtn.disabled = false;
                        confirmDeleteResidentBtn.innerHTML = 'Yes, Delete';
                        alert('Invalid response from server.');
                      }
                    })
                    .catch(function(error) {
                      console.error('Delete error:', error);
                      confirmDeleteResidentBtn.disabled = false;
                      confirmDeleteResidentBtn.innerHTML = 'Yes, Delete';
                      alert('Error deleting resident: ' + error.message);
                    });
                  };
                  
                  var deleteModalInstance = new bootstrap.Modal(deleteModal);
                  deleteModalInstance.show();
                }
              });
            }
          };
        }
      }
      
      // Function to display resident profile in modal
      function displayResidentProfile(resident) {
        var viewModalBody = document.getElementById('viewResidentModalBody');
        
        // Debug: Log the resident data to console
        console.log('Resident data:', resident);
        console.log('Profile image:', resident.profile_image);
        
        // Determine profile image
        var profileImageHtml = '';
        if (resident.profile_image && resident.profile_image !== '' && resident.profile_image !== null) {
          // Try to construct the image path - check if it already includes 'uploads/'
          var imagePath = resident.profile_image;
          if (!imagePath.startsWith('uploads/')) {
            imagePath = 'uploads/' + imagePath;
          }
          
          profileImageHtml = '<div class="profile-avatar mb-3" style="width: 150px; height: 150px; margin: 0 auto; border-radius: 50%; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.2); border: 4px solid #fff;">' +
            '<img src="' + escapeHtml(imagePath) + '" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.parentElement.innerHTML=\'<div style=\\\'width: 150px; height: 150px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;\\\'><i class=\\\'bi bi-person-fill text-white\\\' style=\\\'font-size: 80px;\\\'></i></div>\';">' +
            '</div>';
        } else {
          profileImageHtml = '<div class="profile-avatar mb-3" style="width: 150px; height: 150px; margin: 0 auto; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">' +
            '<i class="bi bi-person-fill text-white" style="font-size: 80px;"></i>' +
            '</div>';
        }
        
        var profileHtml = '<div class="container-fluid p-4">' +
          '<div class="row">' +
          '<div class="col-md-4 text-center mb-4">' +
          profileImageHtml +
          '<h4 class="fw-bold text-dark mb-1">' + escapeHtml(resident.surname) + ', ' + escapeHtml(resident.first_name) + ' ' + escapeHtml(resident.middle_name || '') + '</h4>' +
          '<p class="text-muted mb-2"><i class="bi bi-fingerprint"></i> ID: ' + escapeHtml(resident.unique_id) + '</p>' +
          (resident.is_head === 'Yes' ? '<span class="badge bg-success mb-2"><i class="bi bi-house-fill"></i> Head of Household</span>' : '') +
          (resident.is_pwd === 'Yes' ? '<span class="badge bg-warning text-dark mb-2 ms-1"><i class="bi bi-universal-access"></i> PWD</span>' : '') +
          (resident.age > 60 ? '<span class="badge bg-info mb-2 ms-1"><i class="bi bi-person-badge"></i> Senior Citizen</span>' : '') +
          '</div>' +
          '<div class="col-md-8">' +
          '<h5 class="fw-bold text-success mb-3 pb-2 border-bottom"><i class="bi bi-person-lines-fill"></i> Personal Information</h5>' +
          '<div class="row mb-2">' +
          '<div class="col-6"><strong>Age:</strong></div><div class="col-6">' + escapeHtml(resident.age) + ' years old</div>' +
          '</div>' +
          '<div class="row mb-2">' +
          '<div class="col-6"><strong>Gender:</strong></div><div class="col-6">' + escapeHtml(resident.sex) + '</div>' +
          '</div>' +
          '<div class="row mb-2">' +
          '<div class="col-6"><strong>Birthdate:</strong></div><div class="col-6">' + escapeHtml(resident.birthdate || 'N/A') + '</div>' +
          '</div>' +
          '<div class="row mb-2">' +
          '<div class="col-6"><strong>Place of Birth:</strong></div><div class="col-6">' + escapeHtml(resident.place_of_birth || 'N/A') + '</div>' +
          '</div>' +
          '<div class="row mb-2">' +
          '<div class="col-6"><strong>Civil Status:</strong></div><div class="col-6">' + escapeHtml(resident.civil_status || 'N/A') + '</div>' +
          '</div>' +
          '<div class="row mb-2">' +
          '<div class="col-6"><strong>Citizenship:</strong></div><div class="col-6">' + escapeHtml(resident.citizenship || 'N/A') + '</div>' +
          '</div>' +
          '<h5 class="fw-bold text-success mb-3 pb-2 border-bottom mt-4"><i class="bi bi-briefcase-fill"></i> Additional Details</h5>' +
          '<div class="row mb-2">' +
          '<div class="col-6"><strong>Occupation/Skills:</strong></div><div class="col-6">' + escapeHtml(resident.occupation_skills || 'N/A') + '</div>' +
          '</div>' +
          '<div class="row mb-2">' +
          '<div class="col-6"><strong>Education:</strong></div><div class="col-6">' + escapeHtml(resident.education || 'N/A') + '</div>' +
          '</div>' +
          '<div class="row mb-2">' +
          '<div class="col-6"><strong>PWD Status:</strong></div><div class="col-6">' + escapeHtml(resident.is_pwd || 'No') + '</div>' +
          '</div>' +
          '<h5 class="fw-bold text-success mb-3 pb-2 border-bottom mt-4"><i class="bi bi-geo-alt-fill"></i> Address & Household</h5>' +
          '<div class="row mb-2">' +
          '<div class="col-6"><strong>Address:</strong></div><div class="col-6">' + escapeHtml(resident.address || 'N/A') + '</div>' +
          '</div>' +
          '<div class="row mb-2">' +
          '<div class="col-6"><strong>Household No.:</strong></div><div class="col-6">' + escapeHtml(resident.household_id || 'N/A') + '</div>' +
          '</div>' +
          '<div class="row mb-2">' +
          '<div class="col-6"><strong>Relationship:</strong></div><div class="col-6">' + escapeHtml(resident.relationship || 'N/A') + '</div>' +
          '</div>' +
          '</div>' +
          '</div>' +
          '</div>';
        
        viewModalBody.innerHTML = profileHtml;
      }

      // Update attachResidentsPaginationEvents to also call attachDeleteBtnEvents and attachViewBtnEvents
      function attachResidentsPaginationEvents() {
        const prevBtn = document.getElementById('prevPageBtn');
        const nextBtn = document.getElementById('nextPageBtn');
        if (prevBtn) prevBtn.onclick = function() {
          const current = parseInt(document.getElementById('currentPageNum').textContent);
          if (current > 1) loadResidentsPage(current - 1);
        };
        if (nextBtn) nextBtn.onclick = function() {
          const current = parseInt(document.getElementById('currentPageNum').textContent);
          const total = parseInt(document.getElementById('totalPagesNum').textContent);
          if (current < total) loadResidentsPage(current + 1);
        };
        // Re-attach edit button events after table update
        if (typeof attachEditResidentBtnEvents === 'function') {
          attachEditResidentBtnEvents();
        }
        // Re-attach delete button events after table update
        attachDeleteBtnEvents();
        // Re-attach view button events after table update
        if (typeof attachViewBtnEvents === 'function') {
          attachViewBtnEvents();
        }
      }
      </script>
    <?php endif; ?>
    
    <!-- View Button Events Script (outside conditional so it's always available) -->
    <script>
      // Function to attach view button events
      if (typeof attachViewBtnEvents === 'undefined') {
        function attachViewBtnEvents() {
          var viewBtns = document.querySelectorAll('.view-resident-btn');
          var viewModal = document.getElementById('viewResidentModal');
          var viewModalBody = document.getElementById('viewResidentModalBody');
          var currentResidentId = null;
          
          if (!viewModal || !viewModalBody) return;
          
          viewBtns.forEach(function(btn) {
            btn.onclick = function() {
              currentResidentId = btn.getAttribute('data-unique-id');
              
              // Show loading state
              viewModalBody.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-info" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-3">Loading resident information...</p></div>';
              
              // Show modal
              var modal = new bootstrap.Modal(viewModal);
              modal.show();
              
              // Fetch resident data
              fetch('admin_dashboard.php?get_resident_ajax=1&id=' + encodeURIComponent(currentResidentId), {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
              })
              .then(function(res) {
                if (!res.ok) throw new Error('HTTP error! status: ' + res.status);
                return res.json();
              })
              .then(function(data) {
                if (data.success && data.resident) {
                  displayResidentProfile(data.resident);
                } else {
                  viewModalBody.innerHTML = '<div class="alert alert-danger m-4">Error loading resident data</div>';
                }
              })
              .catch(function(error) {
                console.error('Error:', error);
                viewModalBody.innerHTML = '<div class="alert alert-danger m-4">Error loading resident data: ' + error.message + '</div>';
              });
            };
          });
          
          // Handle Edit button in modal
          var editBtn = document.getElementById('editResidentFromModal');
          if (editBtn) {
            editBtn.onclick = function() {
              if (currentResidentId) {
                // Close view modal
                var modal = bootstrap.Modal.getInstance(viewModal);
                if (modal) modal.hide();
                
                // Trigger edit functionality
                var editBtnElement = document.querySelector('.edit-resident-btn[data-unique-id="' + currentResidentId + '"]');
                if (editBtnElement) {
                  editBtnElement.click();
                } else {
                  // Fallback: manually trigger edit panel
                  document.querySelectorAll('.panel-section').forEach(function(panel) { panel.style.display = 'none'; });
                  var editPanel = document.getElementById('panel-edit-resident');
                  if (editPanel) {
                    editPanel.style.display = 'block';
                    editPanel.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-3">Loading resident data...</p></div>';
                    
                    var url = new URL(window.location.href);
                    url.searchParams.set('panel', 'edit-resident');
                    url.searchParams.set('editr_id', currentResidentId);
                    history.replaceState(null, '', url.toString());
                    
                    fetch('admin_dashboard.php?ajax_get_resident=' + currentResidentId, {
                      method: 'GET',
                      credentials: 'same-origin',
                      headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(function(response) {
                      if (!response.ok) throw new Error('HTTP error! status: ' + response.status);
                      return response.json();
                    })
                    .then(function(data) {
                      if (data.success && data.resident) {
                        if (typeof loadEditForm === 'function') {
                          loadEditForm(data.resident);
                        }
                      } else {
                        editPanel.innerHTML = '<div class="alert alert-danger m-4">' + (data.message || 'Error loading resident data') + '</div>';
                      }
                    })
                    .catch(function(error) {
                      console.error('Error:', error);
                      editPanel.innerHTML = '<div class="alert alert-danger m-4">Error loading resident data: ' + error.message + '</div>';
                    });
                  }
                }
              }
            };
          }
          
          // Handle Reset Password button in modal
          var resetPasswordBtn = document.getElementById('resetPasswordFromModal');
          if (resetPasswordBtn) {
            resetPasswordBtn.onclick = function() {
              if (currentResidentId) {
                // Close view modal
                var modal = bootstrap.Modal.getInstance(viewModal);
                if (modal) modal.hide();
                
                // Fetch resident data for reset password confirmation
                fetch('admin_dashboard.php?get_resident_ajax=1&id=' + encodeURIComponent(currentResidentId), {
                  method: 'GET',
                  headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                  if (data.success && data.resident) {
                    var residentName = data.resident.surname + ', ' + data.resident.first_name + ' ' + (data.resident.middle_name || '');
                    var residentId = data.resident.unique_id;
                    
                    // Show reset password confirmation modal
                    var resetModal = document.getElementById('resetPasswordModal');
                    var resetPasswordResidentName = document.getElementById('resetPasswordResidentName');
                    var resetPasswordResidentId = document.getElementById('resetPasswordResidentId');
                    var confirmResetUserId = document.getElementById('confirmResetUserId');
                    var confirmResetPasswordBtn = document.getElementById('confirmResetPasswordBtn');
                    
                    resetPasswordResidentName.textContent = residentName;
                    resetPasswordResidentId.textContent = residentId;
                    confirmResetUserId.value = '';
                    
                    // Set up reset password confirmation
                    var newBtn = confirmResetPasswordBtn.cloneNode(true);
                    confirmResetPasswordBtn.parentNode.replaceChild(newBtn, confirmResetPasswordBtn);
                    confirmResetPasswordBtn = newBtn;
                    
                    confirmResetPasswordBtn.onclick = function(e) {
                      e.preventDefault();
                      
                      // Validate user ID confirmation
                      var enteredId = confirmResetUserId.value.trim();
                      var residentIdStr = String(residentId).trim();
                      if (enteredId !== residentIdStr) {
                        alert('User ID does not match! Please enter the correct User ID to confirm.');
                        confirmResetUserId.focus();
                        return;
                      }
                      
                      confirmResetPasswordBtn.disabled = true;
                      confirmResetPasswordBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Resetting...';
                      
                      // Create form data
                      var formData = new FormData();
                      formData.append('reset_password_panel', '1');
                      formData.append('userid', residentId);
                      
                      fetch('admin_dashboard.php', {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                      })
                      .then(function(res) {
                        if (!res.ok) throw new Error('HTTP error! status: ' + res.status);
                        return res.text();
                      })
                      .then(function(text) {
                        var resetModalInstance = bootstrap.Modal.getInstance(resetModal);
                        if (resetModalInstance) resetModalInstance.hide();
                        
                        confirmResetPasswordBtn.disabled = false;
                        confirmResetPasswordBtn.innerHTML = 'Reset Password';
                        
                        // Show success message
                        var successMsg = document.createElement('div');
                        successMsg.className = 'alert alert-success';
                        successMsg.innerHTML = '<strong>✓ Success!</strong> Password reset for User ID: ' + residentId + '. Resident must set a new password on next login.';
                        successMsg.style.position = 'fixed';
                        successMsg.style.top = '20px';
                        successMsg.style.right = '20px';
                        successMsg.style.zIndex = '9999';
                        successMsg.style.minWidth = '350px';
                        successMsg.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
                        document.body.appendChild(successMsg);
                        setTimeout(function() { successMsg.remove(); }, 4000);
                      })
                      .catch(function(error) {
                        console.error('Reset password error:', error);
                        confirmResetPasswordBtn.disabled = false;
                        confirmResetPasswordBtn.innerHTML = 'Reset Password';
                        alert('Error resetting password: ' + error.message);
                      });
                    };
                    
                    var resetModalInstance = new bootstrap.Modal(resetModal);
                    resetModalInstance.show();
                  }
                });
              }
            };
          }
          
          // Handle Delete button in modal
          var deleteBtn = document.getElementById('deleteResidentFromModal');
          if (deleteBtn) {
            deleteBtn.onclick = function() {
              if (currentResidentId) {
                // Close view modal
                var modal = bootstrap.Modal.getInstance(viewModal);
                if (modal) modal.hide();
                
                // Fetch resident name for delete confirmation
                fetch('admin_dashboard.php?get_resident_ajax=1&id=' + encodeURIComponent(currentResidentId), {
                  method: 'GET',
                  headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                  if (data.success && data.resident) {
                    var residentName = data.resident.surname + ', ' + data.resident.first_name;
                    
                    // Show delete confirmation modal
                    var deleteModal = document.getElementById('deleteResidentModal');
                    var residentNameToDelete = document.getElementById('residentNameToDelete');
                    var confirmDeleteResidentBtn = document.getElementById('confirmDeleteResidentBtn');
                    
                    residentNameToDelete.textContent = residentName;
                    
                    // Set up delete confirmation
                    var newBtn = confirmDeleteResidentBtn.cloneNode(true);
                    confirmDeleteResidentBtn.parentNode.replaceChild(newBtn, confirmDeleteResidentBtn);
                    confirmDeleteResidentBtn = newBtn;
                    
                    confirmDeleteResidentBtn.onclick = function(e) {
                      e.preventDefault();
                      confirmDeleteResidentBtn.disabled = true;
                      confirmDeleteResidentBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Deleting...';
                      
                      fetch('rdelete_residents.php?id=' + encodeURIComponent(currentResidentId) + '&ajax=1', {
                        method: 'GET',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                      })
                      .then(function(res) {
                        if (!res.ok) throw new Error('HTTP error! status: ' + res.status);
                        return res.text();
                      })
                      .then(function(text) {
                        try {
                          var data = JSON.parse(text);
                          var modal = bootstrap.Modal.getInstance(deleteModal);
                          if (modal) modal.hide();
                          
                          confirmDeleteResidentBtn.disabled = false;
                          confirmDeleteResidentBtn.innerHTML = 'Yes, Delete';
                          
                          if (data.success) {
                            var successMsg = document.createElement('div');
                            successMsg.className = 'alert alert-success';
                            successMsg.innerHTML = '<strong>✓ Deleted!</strong> Resident moved to recycle bin.';
                            successMsg.style.position = 'fixed';
                            successMsg.style.top = '20px';
                            successMsg.style.right = '20px';
                            successMsg.style.zIndex = '9999';
                            document.body.appendChild(successMsg);
                            setTimeout(function() { successMsg.remove(); }, 3000);
                            
                            var current = 1;
                            var pageNumEl = document.getElementById('currentPageNum');
                            if (pageNumEl) current = parseInt(pageNumEl.textContent) || 1;
                            if (typeof loadResidentsPage === 'function') {
                              loadResidentsPage(current);
                            }
                          } else {
                            alert('Error: ' + (data.error || 'Failed to delete resident'));
                          }
                        } catch (e) {
                          console.error('JSON parse error:', e);
                          confirmDeleteResidentBtn.disabled = false;
                          confirmDeleteResidentBtn.innerHTML = 'Yes, Delete';
                          alert('Invalid response from server.');
                        }
                      })
                      .catch(function(error) {
                        console.error('Delete error:', error);
                        confirmDeleteResidentBtn.disabled = false;
                        confirmDeleteResidentBtn.innerHTML = 'Yes, Delete';
                        alert('Error deleting resident: ' + error.message);
                      });
                    };
                    
                    var deleteModalInstance = new bootstrap.Modal(deleteModal);
                    deleteModalInstance.show();
                  }
                });
              }
            };
          }
        }
      }
    </script>
  </div>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<script>
// Bootstrap tooltips
document.addEventListener('DOMContentLoaded', function() {
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.forEach(function (tooltipTriggerEl) {
    new bootstrap.Tooltip(tooltipTriggerEl);
  });
  // Attach delete button events on initial load
  if (typeof attachDeleteBtnEvents === 'function') {
    attachDeleteBtnEvents();
  }
  // Attach view button events on initial load
  if (typeof attachViewBtnEvents === 'function') {
    attachViewBtnEvents();
  }
});
  // Households panel logic
  document.addEventListener('DOMContentLoaded', function() {
    var panelHouseholds = document.getElementById('panel-households');
    var toggleHouseholdsBtn = document.getElementById('toggleHouseholdsBtn');
    var panelViewResidents = document.getElementById('panel-view-residents');
    var backToResidentsBtn = document.getElementById('backToResidentsBtn');
    var resetHouseholdBtn = document.getElementById('resetHouseholdBtn');

    if (toggleHouseholdsBtn) {
      toggleHouseholdsBtn.addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelectorAll('.panel-section').forEach(function(panel) { panel.style.display = 'none'; });
        if (panelHouseholds) panelHouseholds.style.display = 'block';
        history.replaceState(null, '', '?panel=households');
      });
    }
    if (backToResidentsBtn) {
      backToResidentsBtn.addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelectorAll('.panel-section').forEach(function(panel) { panel.style.display = 'none'; });
        if (panelViewResidents) panelViewResidents.style.display = 'block';
        history.replaceState(null, '', '?panel=view-residents');
      });
    }
    if (resetHouseholdBtn) {
      resetHouseholdBtn.addEventListener('click', function(e) {
        e.preventDefault();
        window.location.href = '?panel=households';
      });
    }

    // Intercept household search form submission to prevent page refresh
    var householdSearchForm = document.getElementById('householdSearchForm');
    if (householdSearchForm) {
      householdSearchForm.addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent form submission
        
        var householdInput = document.querySelector('input[name="household_id"]');
        var searchValue = householdInput ? householdInput.value.trim() : '';
        
        // Update URL without refresh
        var newUrl = '?panel=households';
        if (searchValue) {
          newUrl += '&household_id=' + encodeURIComponent(searchValue);
        }
        history.replaceState(null, '', newUrl);
        
        // Reload the panel content via AJAX
        fetch(newUrl)
          .then(res => res.text())
          .then(html => {
            var parser = new DOMParser();
            var doc = parser.parseFromString(html, 'text/html');
            var newPanelContent = doc.querySelector('#panel-households');
            if (newPanelContent && panelHouseholds) {
              panelHouseholds.innerHTML = newPanelContent.innerHTML;
              // Re-attach event listeners after content update
              attachHouseholdEventListeners();
            }
          })
          .catch(err => console.error('Search failed:', err));
      });
    }
    
    // Function to re-attach event listeners after AJAX update
    function attachHouseholdEventListeners() {
      var newResetBtn = document.getElementById('resetHouseholdBtn');
      if (newResetBtn) {
        newResetBtn.addEventListener('click', function(e) {
          e.preventDefault();
          document.querySelector('input[name="household_id"]').value = '';
          householdSearchForm.dispatchEvent(new Event('submit'));
        });
      }
      
      var newSearchForm = document.getElementById('householdSearchForm');
      if (newSearchForm) {
        newSearchForm.addEventListener('submit', function(e) {
          e.preventDefault();
          householdSearchForm.dispatchEvent(new Event('submit'));
        });
      }
    }

    // Show Households panel if URL has panel=households
    var params = new URLSearchParams(window.location.search);
    if (params.get('panel') === 'households') {
      document.querySelectorAll('.panel-section').forEach(function(panel) { panel.style.display = 'none'; });
      if (panelHouseholds) panelHouseholds.style.display = 'block';
    }
  });
</script>
</div>
<?php /* End residents panel */ ?>
</div>
<script>
    // Add View Residents panel logic
    const panelViewResidents = document.getElementById('panel-view-residents');
    const toggleViewResidentsBtn = document.getElementById('toggleViewResidentsBtn');
    if (toggleViewResidentsBtn) {
      toggleViewResidentsBtn.addEventListener('click', function(e) {
        e.preventDefault();
        showPanel(panelViewResidents);
        history.replaceState(null, '', '?panel=view-residents');
        
        // Re-attach view button events when panel is shown
        setTimeout(function() {
          if (typeof attachViewBtnEvents === 'function') {
            attachViewBtnEvents();
          }
        }, 100);
      });
    }

    // Auto-refresh View Residents panel when new data arrives
    let viewResidentsRefreshInterval = null;
    let lastResidentCount = 0;
    
    function checkForNewResidents() {
      // Only check if the panel is currently visible
      if (panelViewResidents && panelViewResidents.style.display !== 'none') {
        fetch('admin_dashboard.php?action=get_residents_count', {
          method: 'GET',
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            // If count changed, refresh the table
            if (lastResidentCount > 0 && data.count !== lastResidentCount) {
              console.log('New resident data detected, refreshing table...');
              
              // Get current page
              var currentPage = 1;
              var pageNumEl = document.getElementById('currentPageNum');
              if (pageNumEl) {
                currentPage = parseInt(pageNumEl.textContent) || 1;
              }
              
              // Refresh the table
              if (typeof loadResidentsPage === 'function') {
                loadResidentsPage(currentPage);
              }
              
              // Show notification
              var notifDiv = document.createElement('div');
              notifDiv.className = 'alert alert-info';
              notifDiv.innerHTML = '<strong><i class="bi bi-info-circle"></i></strong> Residents list updated with new data!';
              notifDiv.style.position = 'fixed';
              notifDiv.style.top = '20px';
              notifDiv.style.right = '20px';
              notifDiv.style.zIndex = '9999';
              notifDiv.style.minWidth = '300px';
              notifDiv.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
              document.body.appendChild(notifDiv);
              setTimeout(function() { notifDiv.remove(); }, 3000);
            }
            lastResidentCount = data.count;
          }
        })
        .catch(error => {
          console.error('Error checking for new residents:', error);
        });
      }
    }
    
    // Start checking when panel is opened
    if (toggleViewResidentsBtn) {
      toggleViewResidentsBtn.addEventListener('click', function() {
        // Initialize count
        lastResidentCount = 0;
        setTimeout(checkForNewResidents, 1000); // Initial check after 1 second
        
        // Start interval (check every 10 seconds)
        if (viewResidentsRefreshInterval) {
          clearInterval(viewResidentsRefreshInterval);
        }
        viewResidentsRefreshInterval = setInterval(checkForNewResidents, 10000);
      });
    }
    
    // Stop checking when navigating away
    document.addEventListener('DOMContentLoaded', function() {
      var allNavButtons = document.querySelectorAll('.nav-link, button[id^="toggle"]');
      allNavButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
          if (btn !== toggleViewResidentsBtn && viewResidentsRefreshInterval) {
            clearInterval(viewResidentsRefreshInterval);
            viewResidentsRefreshInterval = null;
          }
        });
      });
    });
</script>

<!-- Jobfinder Panel (hidden by default) -->
<div id="panel-jobfinder" class="panel-section" style="display:none;">
<?php
include 'config.php';

// Handle block/unblock actions
if (isset($_POST['jobfinder_action']) && isset($_POST['resident_id'])) {
    $resident_id = $_POST['resident_id'];
    $action = $_POST['jobfinder_action'];
    
    // First, ensure the column exists (create if not)
    $check_column = $conn->query("SHOW COLUMNS FROM residents LIKE 'blocked_from_jobfinder'");
    if ($check_column->num_rows == 0) {
        $conn->query("ALTER TABLE residents ADD COLUMN blocked_from_jobfinder TINYINT(1) DEFAULT 0");
    }
    
    if ($action === 'block') {
        $stmt = $conn->prepare("UPDATE residents SET blocked_from_jobfinder = 1 WHERE unique_id = ?");
        $stmt->bind_param("s", $resident_id);
        $stmt->execute();
        $stmt->close();
        
        // Log the action
        $admin_username = $_SESSION['admin_username'] ?? 'Unknown';
        $action_log = "Blocked resident $resident_id from Jobfinder";
        $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
        $log->bind_param("ss", $admin_username, $action_log);
        $log->execute();
        $log->close();
        
        $jobfinder_message = "Resident blocked from Jobfinder successfully.";
        $jobfinder_message_type = "success";
    } elseif ($action === 'unblock') {
        $stmt = $conn->prepare("UPDATE residents SET blocked_from_jobfinder = 0 WHERE unique_id = ?");
        $stmt->bind_param("s", $resident_id);
        $stmt->execute();
        $stmt->close();
        
        // Log the action
        $admin_username = $_SESSION['admin_username'] ?? 'Unknown';
        $action_log = "Unblocked resident $resident_id from Jobfinder";
        $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
        $log->bind_param("ss", $admin_username, $action_log);
        $log->execute();
        $log->close();
        
        $jobfinder_message = "Resident unblocked from Jobfinder successfully.";
        $jobfinder_message_type = "success";
    }
}

// Ensure the column exists before querying
$check_column = $conn->query("SHOW COLUMNS FROM residents LIKE 'blocked_from_jobfinder'");
if ($check_column->num_rows == 0) {
    $conn->query("ALTER TABLE residents ADD COLUMN blocked_from_jobfinder TINYINT(1) DEFAULT 0");
}

$check_verified = $conn->query("SHOW COLUMNS FROM residents LIKE 'jobfinder_verified'");
if ($check_verified->num_rows == 0) {
    $conn->query("ALTER TABLE residents ADD COLUMN jobfinder_verified TINYINT(1) DEFAULT 0");
}

// Fetch residents with skills (exclude blocked users), with average rating, sorted by rating desc
$sql = "
  SELECT r.unique_id, r.surname, r.first_name, r.age, r.occupation_skills, r.profile_image, r.skill_description,
       COALESCE(AVG(cr.rating), 0) AS avg_rating, COUNT(cr.rating) AS rating_count,
       COALESCE(r.blocked_from_jobfinder, 0) AS blocked_from_jobfinder,
       COALESCE(r.jobfinder_verified, 0) AS jobfinder_verified
  FROM residents r
  JOIN useraccounts u ON u.userid = r.unique_id
  LEFT JOIN chat_ratings cr ON cr.receiver_id = r.unique_id
  WHERE r.occupation_skills IS NOT NULL 
    AND r.occupation_skills != ''
    AND COALESCE(r.blocked_from_jobfinder, 0) = 0
  GROUP BY r.unique_id
  ORDER BY avg_rating DESC, r.surname ASC
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$jobfinder_residents = $stmt->get_result();
?>

<div class="container-fluid py-4">
  <!-- Success/Error Message -->
  <?php if (isset($jobfinder_message)): ?>
    <div class="alert alert-<?= $jobfinder_message_type ?> alert-dismissible fade show" role="alert">
      <strong><?= $jobfinder_message_type === 'success' ? '✓' : '⚠' ?></strong> <?= $jobfinder_message ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <!-- Modern Header -->
  <div class="jobfinder-header-modern mb-4 p-4 rounded-4 shadow-sm" style="background: linear-gradient(160deg, #14ad0f 0%, #43e97b 100%);">
    <div>
      <h2 class="text-white mb-2" style="font-weight:700;font-size:2rem;">
        <i data-lucide="briefcase-business" style="width:32px;height:32px;vertical-align:middle;margin-right:8px;"></i>
        Manage Jobfinder
      </h2>
      <p class="text-white mb-0" style="font-size:1rem;opacity:0.95;">View and manage residents with registered skills</p>
    </div>
  </div>

  <!-- Statistics Cards -->
  <div class="row mb-4">
    <div class="col-md-4 mb-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-shrink-0">
              <div class="rounded-circle p-3" style="background: linear-gradient(135deg, #14ad0f 0%, #43e97b 100%);">
                <i data-lucide="users" style="width:28px;height:28px;color:white;"></i>
              </div>
            </div>
            <div class="flex-grow-1 ms-3">
              <h6 class="text-muted mb-1" style="font-size:0.85rem;font-weight:600;">Total Skilled Residents</h6>
              <h3 class="mb-0" style="font-weight:700;color:#1a1a1a;"><?= $jobfinder_residents->num_rows ?></h3>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4 mb-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-shrink-0">
              <div class="rounded-circle p-3" style="background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);">
                <i data-lucide="star" style="width:28px;height:28px;color:white;"></i>
              </div>
            </div>
            <div class="flex-grow-1 ms-3">
              <h6 class="text-muted mb-1" style="font-size:0.85rem;font-weight:600;">Avg Rating</h6>
              <h3 class="mb-0" style="font-weight:700;color:#1a1a1a;">
                <?php
                $avg_sql = "SELECT COALESCE(AVG(rating), 0) as overall_avg FROM chat_ratings WHERE receiver_id IN (SELECT unique_id FROM residents WHERE occupation_skills IS NOT NULL AND occupation_skills != '')";
                $avg_result = $conn->query($avg_sql);
                $overall_avg = $avg_result->fetch_assoc()['overall_avg'];
                echo number_format($overall_avg, 1);
                ?>
              </h3>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4 mb-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-shrink-0">
              <div class="rounded-circle p-3" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                <i data-lucide="briefcase" style="width:28px;height:28px;color:white;"></i>
              </div>
            </div>
            <div class="flex-grow-1 ms-3">
              <h6 class="text-muted mb-1" style="font-size:0.85rem;font-weight:600;">Active Skills</h6>
              <h3 class="mb-0" style="font-weight:700;color:#1a1a1a;">
                <?php
                $skills_sql = "SELECT COUNT(DISTINCT occupation_skills) as skill_count FROM residents WHERE occupation_skills IS NOT NULL AND occupation_skills != ''";
                $skills_result = $conn->query($skills_sql);
                $skill_count = $skills_result->fetch_assoc()['skill_count'];
                echo $skill_count;
                ?>
              </h3>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Search and Filter -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <div class="d-flex gap-2 align-items-center">
        <div class="input-group flex-grow-1">
          <span class="input-group-text bg-white border-end-0">
            <i data-lucide="search" style="width:18px;height:18px;color:#6c757d;"></i>
          </span>
          <input type="text" id="jobfinderSearch" class="form-control border-start-0 ps-0" placeholder="Search by ID, name, or skill...">
        </div>
        <button class="btn btn-danger shadow-sm fw-semibold" id="block-any-user-btn" style="border-radius: 8px; white-space: nowrap;">
          <i data-lucide="ban" style="width:18px;height:18px;vertical-align:middle;margin-right:6px;"></i>
          Block Any User
        </button>
        <button class="btn btn-outline-danger shadow-sm fw-semibold" id="view-blocked-jobfinder-users-btn" style="border-radius: 8px; white-space: nowrap;">
          <i data-lucide="user-x" style="width:18px;height:18px;vertical-align:middle;margin-right:6px;"></i>
          Blocked Users
        </button>
        <button class="btn btn-outline-warning shadow-sm fw-semibold position-relative" id="view-chat-reports-btn" style="border-radius: 8px; white-space: nowrap;">
          <i data-lucide="alert-triangle" style="width:18px;height:18px;vertical-align:middle;margin-right:6px;"></i>
          View Reports
          <span id="pending-reports-badge" class="badge bg-danger rounded-pill ms-2" style="display:none;">0</span>
        </button>
        <button class="btn btn-outline-success shadow-sm fw-semibold" id="view-resolved-reports-btn" style="border-radius: 8px; white-space: nowrap;">
          <i data-lucide="check-circle" style="width:18px;height:18px;vertical-align:middle;margin-right:6px;"></i>
          Resolved Reports
        </button>
        <button class="btn btn-outline-secondary shadow-sm fw-semibold" id="view-dismissed-reports-btn" style="border-radius: 8px; white-space: nowrap;">
          <i data-lucide="archive" style="width:18px;height:18px;vertical-align:middle;margin-right:6px;"></i>
          Dismissed Reports
        </button>
      </div>
    </div>
  </div>

  <!-- Residents Table -->
  <div class="card border-0 shadow-sm">
    <div class="card-body p-0">
      <div style="max-height: 70vh; overflow-y: auto; overflow-x: hidden; scroll-behavior: smooth;">
        <table class="table table-hover mb-0" id="jobfinderTable" style="width: 100%;">
          <thead style="background: linear-gradient(160deg, #f8f9fa 0%, #e9ecef 100%); position: sticky; top: 0; z-index: 10;">
            <tr>
              <th class="border-0 py-3 px-3" style="font-weight:600;color:#495057;width:60px;">Photo</th>
              <th class="border-0 py-3 px-2" style="font-weight:600;color:#495057;width:90px;">ID</th>
              <th class="border-0 py-3 px-2" style="font-weight:600;color:#495057;width:140px;">Name</th>
              <th class="border-0 py-3 px-2" style="font-weight:600;color:#495057;width:80px;">Age</th>
              <th class="border-0 py-3 px-2" style="font-weight:600;color:#495057;width:150px;">Skills</th>
              <th class="border-0 py-3 px-2" style="font-weight:600;color:#495057;width:200px;">Description</th>
              <th class="border-0 py-3 px-2" style="font-weight:600;color:#495057;width:80px;">Rating</th>
              <th class="border-0 py-3 px-2" style="font-weight:600;color:#495057;width:70px;">Reviews</th>
              <th class="border-0 py-3 px-2" style="font-weight:600;color:#495057;width:70px;">Status</th>
              <th class="border-0 py-3 px-2" style="font-weight:600;color:#495057;width:90px;">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($jobfinder_residents->num_rows > 0): ?>
              <?php 
              $jobfinder_residents->data_seek(0); // Reset pointer
              while ($resident = $jobfinder_residents->fetch_assoc()): 
                $img = (!empty($resident['profile_image'])) ? htmlspecialchars($resident['profile_image']) : 'https://cdn-icons-png.flaticon.com/512/149/149071.png';
                $fullName = htmlspecialchars($resident['surname'] . ', ' . $resident['first_name']);
                $skills = htmlspecialchars($resident['occupation_skills']);
                $skillDesc = htmlspecialchars($resident['skill_description'] ?? 'No description');
                $age = htmlspecialchars($resident['age']);
                $userid = htmlspecialchars($resident['unique_id']);
                $avgRating = number_format($resident['avg_rating'], 1);
                $ratingCount = (int)$resident['rating_count'];
                $isVerified = (int)$resident['jobfinder_verified'];
              ?>
              <tr class="jobfinder-row" data-userid="<?= strtolower($userid) ?>" data-name="<?= strtolower($fullName) ?>" data-skills="<?= strtolower($skills) ?>">
                <td class="px-2 py-3">
                  <img src="<?= $img ?>" alt="<?= $fullName ?>" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;border:2px solid #e9ecef;">
                </td>
                <td class="px-2 py-3">
                  <span class="badge bg-success" style="font-size:0.75rem;font-weight:600;"><?= $userid ?></span>
                </td>
                <td class="px-2 py-3">
                  <div style="font-weight:600;color:#1a1a1a;font-size:0.9rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= $fullName ?>"><?= $fullName ?></div>
                </td>
                <td class="px-2 py-3">
                  <span class="text-muted" style="font-size:0.85rem;"><?= $age ?> years</span>
                </td>
                <td class="px-2 py-3">
                  <span class="badge" style="background: linear-gradient(135deg, #14ad0f 0%, #43e97b 100%);font-size:0.75rem;padding:4px 8px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:block;" title="<?= $skills ?>"><?= $skills ?></span>
                </td>
                <td class="px-2 py-3">
                  <div style="font-size:0.85rem;color:#6c757d;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= $skillDesc ?>">
                    <?= $skillDesc ?>
                  </div>
                </td>
                <td class="px-2 py-3">
                  <?php if ($avgRating > 0): ?>
                    <div class="d-flex align-items-center">
                      <i data-lucide="star" style="width:14px;height:14px;color:#ffc107;fill:#ffc107;margin-right:2px;"></i>
                      <span style="font-weight:600;color:#1a1a1a;font-size:0.9rem;"><?= $avgRating ?></span>
                    </div>
                  <?php else: ?>
                    <span class="text-muted" style="font-size:0.8rem;">No ratings</span>
                  <?php endif; ?>
                </td>
                <td class="px-2 py-3 text-center">
                  <span class="badge bg-secondary" style="font-size:0.85rem;"><?= $ratingCount ?></span>
                </td>
                <td class="px-2 py-3 text-center">
                  <?php if ($isVerified): ?>
                    <span class="badge bg-success" style="font-size:0.75rem;">
                      <i data-lucide="check-circle" style="width:12px;height:12px;vertical-align:middle;margin-right:2px;"></i>
                      Verified
                    </span>
                  <?php else: ?>
                    <span class="badge bg-secondary" style="font-size:0.75rem;">Not Verified</span>
                  <?php endif; ?>
                </td>
                <td class="px-2 py-3 text-center">
                  <div class="d-flex gap-1 justify-content-center">
                    <?php if (!$isVerified): ?>
                      <button class="btn btn-sm btn-success verify-jobfinder-btn" style="font-size:0.7rem;padding:3px 6px;" data-userid="<?= $userid ?>" data-name="<?= $fullName ?>" title="Verify">
                        <i data-lucide="check" style="width:12px;height:12px;vertical-align:middle;"></i>
                      </button>
                    <?php else: ?>
                      <button class="btn btn-sm btn-warning unverify-jobfinder-btn" style="font-size:0.7rem;padding:3px 6px;" data-userid="<?= $userid ?>" data-name="<?= $fullName ?>" title="Unverify">
                        <i data-lucide="x" style="width:12px;height:12px;vertical-align:middle;"></i>
                      </button>
                    <?php endif; ?>
                    <button class="btn btn-sm btn-danger block-jobfinder-btn" style="font-size:0.7rem;padding:3px 6px;" data-userid="<?= $userid ?>" data-name="<?= $fullName ?>" title="Block">
                      <i data-lucide="lock" style="width:12px;height:12px;vertical-align:middle;"></i>
                    </button>
                  </div>
                </td>
              </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="9" class="text-center py-5">
                  <i data-lucide="inbox" style="width:48px;height:48px;color:#dee2e6;margin-bottom:12px;"></i>
                  <p class="text-muted mb-0">No residents with skills found</p>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<style>
.jobfinder-row {
  transition: all 0.2s ease;
}
.jobfinder-row:hover {
  background-color: #f8f9fa;
  transform: scale(1.01);
}
</style>

<script>
// Search functionality
document.getElementById('jobfinderSearch').addEventListener('input', function(e) {
  const searchTerm = e.target.value.toLowerCase();
  const rows = document.querySelectorAll('.jobfinder-row');
  
  rows.forEach(row => {
    const userid = row.getAttribute('data-userid');
    const name = row.getAttribute('data-name');
    const skills = row.getAttribute('data-skills');
    
    if (userid.includes(searchTerm) || name.includes(searchTerm) || skills.includes(searchTerm)) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
});

// Initialize Lucide icons
lucide.createIcons();

// Function to update pending reports count badge
function updatePendingReportsCount() {
  fetch('admin_dashboard.php?action=get_pending_reports_count')
    .then(response => response.json())
    .then(data => {
      if (data.success && data.count > 0) {
        const badge = document.getElementById('pending-reports-badge');
        if (badge) {
          badge.textContent = data.count;
          badge.style.display = 'inline-block';
        }
      } else {
        const badge = document.getElementById('pending-reports-badge');
        if (badge) {
          badge.style.display = 'none';
        }
      }
    })
    .catch(error => {
      console.error('Error fetching pending reports count:', error);
    });
}

// Update count on page load
updatePendingReportsCount();

// Update count every 30 seconds
setInterval(updatePendingReportsCount, 30000);
</script>

<!-- Block Jobfinder User Confirmation Modal -->
<div class="modal fade" id="blockJobfinderModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header border-0 pb-2" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
        <div class="d-flex align-items-center gap-2">
          <div class="d-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-25" style="width: 40px; height: 40px;">
            <i data-lucide="lock" style="width:20px;height:20px;color:white;"></i>
          </div>
          <h5 class="modal-title text-white fw-bold mb-0">Block User from Jobfinder</h5>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div class="alert alert-warning border-0 d-flex align-items-start gap-2 mb-3" style="background-color: #fff3cd;">
          <i data-lucide="alert-triangle" style="width:20px;height:20px;color:#856404;margin-top:2px;"></i>
          <div>
            <strong class="text-warning d-block mb-1" style="color:#856404 !important;">Warning</strong>
            <small style="color:#856404;">This user will be removed from the Jobfinder and will not be visible to other residents.</small>
          </div>
        </div>
        <p class="mb-2"><strong>User ID:</strong> <span id="blockJobfinderUserId" class="badge bg-danger"></span></p>
        <p class="mb-0"><strong>Name:</strong> <span id="blockJobfinderUserName"></span></p>
      </div>
      <div class="modal-footer border-0 pt-0 px-4 pb-4">
        <button type="button" class="btn btn-secondary px-4 py-2 fw-semibold" data-bs-dismiss="modal">
          <i data-lucide="x-circle" style="width:16px;height:16px;vertical-align:middle;margin-right:4px;"></i>
          Cancel
        </button>
        <button type="button" class="btn btn-danger px-4 py-2 fw-semibold" id="confirmBlockJobfinderBtn">
          <i data-lucide="lock" style="width:16px;height:16px;vertical-align:middle;margin-right:4px;"></i>
          Block User
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Unblock Jobfinder User Confirmation Modal -->
<div class="modal fade" id="unblockJobfinderModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header border-0 pb-2" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
        <div class="d-flex align-items-center gap-2">
          <div class="d-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-25" style="width: 40px; height: 40px;">
            <i data-lucide="unlock" style="width:20px;height:20px;color:white;"></i>
          </div>
          <h5 class="modal-title text-white fw-bold mb-0">Unblock User from Jobfinder</h5>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div class="alert alert-success border-0 d-flex align-items-start gap-2 mb-3" style="background-color: #d1fae5;">
          <i data-lucide="check-circle" style="width:20px;height:20px;color:#065f46;margin-top:2px;"></i>
          <div>
            <strong class="d-block mb-1" style="color:#065f46;">Restore Access</strong>
            <small style="color:#065f46;">This user will be restored to the Jobfinder and will be visible to other residents again.</small>
          </div>
        </div>
        <p class="mb-2"><strong>User ID:</strong> <span id="unblockJobfinderUserId" class="badge bg-success"></span></p>
        <p class="mb-0"><strong>Name:</strong> <span id="unblockJobfinderUserName"></span></p>
      </div>
      <div class="modal-footer border-0 pt-0 px-4 pb-4">
        <button type="button" class="btn btn-secondary px-4 py-2 fw-semibold" data-bs-dismiss="modal">
          <i data-lucide="x-circle" style="width:16px;height:16px;vertical-align:middle;margin-right:4px;"></i>
          Cancel
        </button>
        <button type="button" class="btn btn-success px-4 py-2 fw-semibold" id="confirmUnblockJobfinderBtn">
          <i data-lucide="unlock" style="width:16px;height:16px;vertical-align:middle;margin-right:4px;"></i>
          Unblock User
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Verify Jobfinder User Confirmation Modal -->
<div class="modal fade" id="verifyJobfinderModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header border-0 pb-2" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
        <div class="d-flex align-items-center gap-2">
          <div class="d-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-25" style="width: 40px; height: 40px;">
            <i data-lucide="check-circle" style="width:20px;height:20px;color:white;"></i>
          </div>
          <h5 class="modal-title text-white fw-bold mb-0">Verify User in Jobfinder</h5>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div class="alert alert-success border-0 d-flex align-items-start gap-2 mb-3" style="background-color: #d1fae5;">
          <i data-lucide="shield-check" style="width:20px;height:20px;color:#065f46;margin-top:2px;"></i>
          <div>
            <strong class="d-block mb-1" style="color:#065f46;">Verify Profile</strong>
            <small style="color:#065f46;">This user will be marked as verified and will display a verification badge in Jobfinder.</small>
          </div>
        </div>
        <p class="mb-2"><strong>User ID:</strong> <span id="verifyJobfinderUserId" class="badge bg-success"></span></p>
        <p class="mb-0"><strong>Name:</strong> <span id="verifyJobfinderUserName"></span></p>
      </div>
      <div class="modal-footer border-0 pt-0 px-4 pb-4">
        <button type="button" class="btn btn-secondary px-4 py-2 fw-semibold" data-bs-dismiss="modal">
          <i data-lucide="x-circle" style="width:16px;height:16px;vertical-align:middle;margin-right:4px;"></i>
          Cancel
        </button>
        <button type="button" class="btn btn-success px-4 py-2 fw-semibold" id="confirmVerifyJobfinderBtn">
          <i data-lucide="check-circle" style="width:16px;height:16px;vertical-align:middle;margin-right:4px;"></i>
          Verify User
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Unverify Jobfinder User Confirmation Modal -->
<div class="modal fade" id="unverifyJobfinderModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header border-0 pb-2" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
        <div class="d-flex align-items-center gap-2">
          <div class="d-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-25" style="width: 40px; height: 40px;">
            <i data-lucide="shield-off" style="width:20px;height:20px;color:white;"></i>
          </div>
          <h5 class="modal-title text-white fw-bold mb-0">Remove Verification</h5>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div class="alert alert-warning border-0 d-flex align-items-start gap-2 mb-3" style="background-color: #fef3c7;">
          <i data-lucide="alert-circle" style="width:20px;height:20px;color:#92400e;margin-top:2px;"></i>
          <div>
            <strong class="d-block mb-1" style="color:#92400e;">Remove Verification</strong>
            <small style="color:#92400e;">The verification badge will be removed from this user's profile in Jobfinder.</small>
          </div>
        </div>
        <p class="mb-2"><strong>User ID:</strong> <span id="unverifyJobfinderUserId" class="badge bg-warning"></span></p>
        <p class="mb-0"><strong>Name:</strong> <span id="unverifyJobfinderUserName"></span></p>
      </div>
      <div class="modal-footer border-0 pt-0 px-4 pb-4">
        <button type="button" class="btn btn-secondary px-4 py-2 fw-semibold" data-bs-dismiss="modal">
          <i data-lucide="x-circle" style="width:16px;height:16px;vertical-align:middle;margin-right:4px;"></i>
          Cancel
        </button>
        <button type="button" class="btn btn-warning px-4 py-2 fw-semibold" id="confirmUnverifyJobfinderBtn">
          <i data-lucide="shield-off" style="width:16px;height:16px;vertical-align:middle;margin-right:4px;"></i>
          Remove Verification
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Blocked Jobfinder Users Modal -->
<div class="modal fade" id="blockedJobfinderUsersModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header border-0 pb-2" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
        <div class="d-flex align-items-center gap-2 w-100">
          <div class="d-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-25" style="width: 40px; height: 40px;">
            <i data-lucide="user-x" style="width:24px;height:24px;color:white;"></i>
          </div>
          <h5 class="modal-title text-white fw-bold mb-0">Blocked Users from Jobfinder</h5>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div class="alert alert-info border-0 d-flex align-items-start gap-2 mb-3" style="background-color: #e3f2fd;">
          <i data-lucide="info" style="width:20px;height:20px;color:#0288d1;margin-top:2px;"></i>
          <div>
            <strong class="text-primary d-block mb-1">Blocked Users List</strong>
            <small class="text-primary">These users are currently blocked from appearing in the Jobfinder. Click "Unblock" to restore their visibility.</small>
          </div>
        </div>
        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
          <table class="table table-hover align-middle">
            <thead class="table-light sticky-top">
              <tr>
                <th>Photo</th>
                <th>User ID</th>
                <th>Name</th>
                <th>Skills</th>
                <th class="text-center">Action</th>
              </tr>
            </thead>
            <tbody id="blockedJobfinderUsersTableBody">
              <tr>
                <td colspan="5" class="text-center py-4">
                  <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0 px-4 pb-4">
        <button type="button" class="btn btn-secondary px-4 py-2 fw-semibold" data-bs-dismiss="modal">
          <i data-lucide="x-circle" style="width:16px;height:16px;vertical-align:middle;margin-right:4px;"></i>
          Close
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Block Any User Modal (Search All Residents) -->
<div class="modal fade" id="blockAnyUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header border-0 pb-2" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);">
        <div class="d-flex align-items-center gap-2 w-100">
          <div class="d-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-25" style="width: 40px; height: 40px;">
            <i data-lucide="ban" style="width:24px;height:24px;color:white;"></i>
          </div>
          <h5 class="modal-title text-white fw-bold mb-0">Block Any User from Jobfinder</h5>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div class="alert alert-warning border-0 d-flex align-items-start gap-2 mb-3" style="background-color: #fff3cd;">
          <i data-lucide="alert-triangle" style="width:20px;height:20px;color:#856404;margin-top:2px;"></i>
          <div>
            <strong class="d-block mb-1" style="color:#856404;">Block Any Resident</strong>
            <small style="color:#856404;">Search and block any resident from using Jobfinder, even if they don't have skills listed. Useful for blocking users who send false reports.</small>
          </div>
        </div>
        
        <!-- Search Box -->
        <div class="mb-3">
          <label class="form-label fw-semibold">Search Resident</label>
          <div class="input-group">
            <span class="input-group-text bg-white">
              <i data-lucide="search" style="width:18px;height:18px;color:#6c757d;"></i>
            </span>
            <input type="text" id="blockAnyUserSearch" class="form-control" placeholder="Search by ID, name, or skill...">
          </div>
          <small class="text-muted">Type at least 2 characters to search</small>
        </div>
        
        <!-- Results Table -->
        <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
          <table class="table table-hover align-middle">
            <thead class="table-light sticky-top">
              <tr>
                <th>Photo</th>
                <th>User ID</th>
                <th>Name</th>
                <th class="text-center">Action</th>
              </tr>
            </thead>
            <tbody id="blockAnyUserSearchResults">
              <tr>
                <td colspan="4" class="text-center py-4 text-muted">
                  <i data-lucide="search" style="width:40px;height:40px;color:#ccc;"></i>
                  <p class="mb-0 mt-2">Start typing to search for residents</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0 px-4 pb-4">
        <button type="button" class="btn btn-secondary px-4 py-2 fw-semibold" data-bs-dismiss="modal">
          <i data-lucide="x-circle" style="width:16px;height:16px;vertical-align:middle;margin-right:4px;"></i>
          Close
        </button>
      </div>
    </div>
  </div>
</div>

<script>
// Handle "Blocked Users" button click for jobfinder
document.addEventListener('DOMContentLoaded', function() {
  var viewBlockedJobfinderUsersBtn = document.getElementById('view-blocked-jobfinder-users-btn');
  if (viewBlockedJobfinderUsersBtn) {
    viewBlockedJobfinderUsersBtn.addEventListener('click', function() {
      var modal = new bootstrap.Modal(document.getElementById('blockedJobfinderUsersModal'));
      
      // Load blocked users
      fetch('admin_dashboard.php?panel=jobfinder&action=get_blocked_jobfinder_users', {
        method: 'GET',
        credentials: 'same-origin'
      })
      .then(response => response.json())
      .then(data => {
        var tbody = document.getElementById('blockedJobfinderUsersTableBody');
        tbody.innerHTML = '';
        
        if (data.length === 0) {
          tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No blocked users found</td></tr>';
        } else {
          data.forEach(function(user) {
            var img = user.profile_image || 'https://cdn-icons-png.flaticon.com/512/149/149071.png';
            var skills = user.occupation_skills || '<span class="text-muted fst-italic">No skills listed</span>';
            var row = '<tr>' +
              '<td><img src="' + img + '" alt="' + user.name + '" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;"></td>' +
              '<td><span class="badge bg-danger">' + user.unique_id + '</span></td>' +
              '<td><strong>' + user.name + '</strong></td>' +
              '<td>' + skills + '</td>' +
              '<td class="text-center">' +
                '<button class="btn btn-sm btn-success unblock-jobfinder-btn" data-userid="' + user.unique_id + '" data-name="' + user.name + '">' +
                  '<i data-lucide="unlock" style="width:14px;height:14px;vertical-align:middle;margin-right:4px;"></i> Unblock' +
                '</button>' +
              '</td>' +
            '</tr>';
            tbody.innerHTML += row;
          });
          
          // Reinitialize Lucide icons
          lucide.createIcons();
          
          // Attach unblock handlers
          document.querySelectorAll('.unblock-jobfinder-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
              var userid = this.getAttribute('data-userid');
              var name = this.getAttribute('data-name');
              
              // Store for later use
              window.currentUnblockUserId = userid;
              window.currentUnblockUserName = name;
              window.currentUnblockBtn = this;
              
              // Update unblock modal content
              document.getElementById('unblockJobfinderUserId').textContent = userid;
              document.getElementById('unblockJobfinderUserName').textContent = name;
              
              // Close blocked users modal
              var blockedModal = bootstrap.Modal.getInstance(document.getElementById('blockedJobfinderUsersModal'));
              if (blockedModal) blockedModal.hide();
              
              // Show unblock confirmation modal
              var unblockModal = new bootstrap.Modal(document.getElementById('unblockJobfinderModal'));
              unblockModal.show();
              
              // Reinitialize Lucide icons
              setTimeout(() => lucide.createIcons(), 100);
            });
          });
        }
      })
      .catch(error => {
        console.error('Error loading blocked users:', error);
        var tbody = document.getElementById('blockedJobfinderUsersTableBody');
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-danger">Error loading blocked users</td></tr>';
      });
      
      modal.show();
      
      // Reinitialize Lucide icons after modal is shown
      setTimeout(() => lucide.createIcons(), 100);
    });
  }
  
  // Handle "Block Any User" button click
  var blockAnyUserBtn = document.getElementById('block-any-user-btn');
  if (blockAnyUserBtn) {
    blockAnyUserBtn.addEventListener('click', function() {
      var modal = new bootstrap.Modal(document.getElementById('blockAnyUserModal'));
      modal.show();
      
      // Focus on search input
      setTimeout(() => {
        document.getElementById('blockAnyUserSearch').focus();
        lucide.createIcons();
      }, 300);
    });
  }
  
  // Handle search in Block Any User modal
  var blockAnyUserSearch = document.getElementById('blockAnyUserSearch');
  if (blockAnyUserSearch) {
    let searchTimeout;
    blockAnyUserSearch.addEventListener('input', function() {
      clearTimeout(searchTimeout);
      var query = this.value.trim();
      
      if (query.length < 2) {
        document.getElementById('blockAnyUserSearchResults').innerHTML = 
          '<tr><td colspan="5" class="text-center py-4 text-muted">' +
          '<i data-lucide="search" style="width:40px;height:40px;color:#ccc;"></i>' +
          '<p class="mb-0 mt-2">Type at least 2 characters to search</p></td></tr>';
        lucide.createIcons();
        return;
      }
      
      // Show loading
      document.getElementById('blockAnyUserSearchResults').innerHTML = 
        '<tr><td colspan="5" class="text-center py-4">' +
        '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>' +
        '</td></tr>';
      
      // Debounce search
      searchTimeout = setTimeout(() => {
        fetch('admin_dashboard.php?panel=jobfinder&action=search_all_residents&q=' + encodeURIComponent(query), {
          method: 'GET',
          credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
          var tbody = document.getElementById('blockAnyUserSearchResults');
          tbody.innerHTML = '';
          
          if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted">No residents found</td></tr>';
          } else {
            data.forEach(function(user) {
              var img = user.profile_image || 'https://cdn-icons-png.flaticon.com/512/149/149071.png';
              var isBlocked = user.blocked_from_jobfinder == 1;
              
              var actionBtn = isBlocked ? 
                '<span class="badge bg-danger">Already Blocked</span>' :
                '<button class="btn btn-sm btn-danger block-any-user-action-btn" data-userid="' + user.unique_id + '" data-name="' + user.name + '">' +
                '<i data-lucide="ban" style="width:14px;height:14px;vertical-align:middle;margin-right:4px;"></i>Block</button>';
              
              var row = '<tr>' +
                '<td><img src="' + img + '" alt="' + user.name + '" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;"></td>' +
                '<td><span class="badge bg-primary">' + user.unique_id + '</span></td>' +
                '<td>' + user.name + '</td>' +
                '<td class="text-center">' + actionBtn + '</td>' +
                '</tr>';
              tbody.innerHTML += row;
            });
            
            // Reinitialize Lucide icons
            lucide.createIcons();
          }
        })
        .catch(error => {
          console.error('Error searching residents:', error);
          document.getElementById('blockAnyUserSearchResults').innerHTML = 
            '<tr><td colspan="4" class="text-center py-4 text-danger">Error searching residents</td></tr>';
        });
      }, 500);
    });
  }
  
  // Handle block action from search results
  document.addEventListener('click', function(e) {
    if (e.target.closest('.block-any-user-action-btn')) {
      var btn = e.target.closest('.block-any-user-action-btn');
      var userId = btn.getAttribute('data-userid');
      var userName = btn.getAttribute('data-name');
      
      // Close search modal
      var searchModal = bootstrap.Modal.getInstance(document.getElementById('blockAnyUserModal'));
      if (searchModal) searchModal.hide();
      
      // Show confirmation modal
      document.getElementById('blockJobfinderUserId').textContent = userId;
      document.getElementById('blockJobfinderUserName').textContent = userName;
      currentBlockUserId = userId;
      currentBlockUserName = userName;
      
      var confirmModal = new bootstrap.Modal(document.getElementById('blockJobfinderModal'));
      confirmModal.show();
      
      setTimeout(() => lucide.createIcons(), 100);
    }
  });
  
  // Handle block button clicks in main table
  var currentBlockUserId = null;
  var currentBlockUserName = null;
  
  // Store current verify/unverify user info
  var currentVerifyUserId = null;
  var currentVerifyUserName = null;
  var currentUnverifyUserId = null;
  var currentUnverifyUserName = null;
  
  document.addEventListener('click', function(e) {
    // Handle verify button
    if (e.target.closest('.verify-jobfinder-btn')) {
      var btn = e.target.closest('.verify-jobfinder-btn');
      currentVerifyUserId = btn.getAttribute('data-userid');
      currentVerifyUserName = btn.getAttribute('data-name');
      
      // Update modal content
      document.getElementById('verifyJobfinderUserId').textContent = currentVerifyUserId;
      document.getElementById('verifyJobfinderUserName').textContent = currentVerifyUserName;
      
      // Show modal
      var verifyModal = new bootstrap.Modal(document.getElementById('verifyJobfinderModal'));
      verifyModal.show();
      
      // Reinitialize Lucide icons
      setTimeout(() => lucide.createIcons(), 100);
    }
    
    // Handle unverify button
    if (e.target.closest('.unverify-jobfinder-btn')) {
      var btn = e.target.closest('.unverify-jobfinder-btn');
      currentUnverifyUserId = btn.getAttribute('data-userid');
      currentUnverifyUserName = btn.getAttribute('data-name');
      
      // Update modal content
      document.getElementById('unverifyJobfinderUserId').textContent = currentUnverifyUserId;
      document.getElementById('unverifyJobfinderUserName').textContent = currentUnverifyUserName;
      
      // Show modal
      var unverifyModal = new bootstrap.Modal(document.getElementById('unverifyJobfinderModal'));
      unverifyModal.show();
      
      // Reinitialize Lucide icons
      setTimeout(() => lucide.createIcons(), 100);
    }
    
    // Handle block button
    if (e.target.closest('.block-jobfinder-btn')) {
      var btn = e.target.closest('.block-jobfinder-btn');
      currentBlockUserId = btn.getAttribute('data-userid');
      currentBlockUserName = btn.getAttribute('data-name');
      
      // Update modal content
      document.getElementById('blockJobfinderUserId').textContent = currentBlockUserId;
      document.getElementById('blockJobfinderUserName').textContent = currentBlockUserName;
      
      // Show modal
      var blockModal = new bootstrap.Modal(document.getElementById('blockJobfinderModal'));
      blockModal.show();
      
      // Reinitialize Lucide icons
      setTimeout(() => lucide.createIcons(), 100);
    }
    
    // Handle resolve report button
    if (e.target.closest('.resolve-report-btn')) {
      var btn = e.target.closest('.resolve-report-btn');
      var reportId = btn.getAttribute('data-id');
      updateReportStatus(reportId, 'resolved', btn);
    }
    
    // Handle dismiss report button
    if (e.target.closest('.dismiss-report-btn')) {
      var btn = e.target.closest('.dismiss-report-btn');
      var reportId = btn.getAttribute('data-id');
      updateReportStatus(reportId, 'dismissed', btn);
    }
  });
  
  // Function to update report status
  function updateReportStatus(reportId, status, btn) {
    btn.disabled = true;
    var originalHtml = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    
    var formData = new FormData();
    formData.append('report_id', reportId);
    formData.append('status', status);
    formData.append('action', 'update_report_status');
    
    fetch('admin_dashboard.php', {
      method: 'POST',
      body: formData,
      credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
      btn.disabled = false;
      btn.innerHTML = originalHtml;
      lucide.createIcons();
      
      if (data.success) {
        showJobfinderToast('Report ' + status + ' successfully', 'success');
        // Refresh the reports list without closing the modal
        refreshChatReportsModal();
        // Update the pending reports count badge
        updatePendingReportsCount();
      } else {
        showJobfinderToast(data.message || 'Error updating report', 'danger');
      }
    })
    .catch(error => {
      btn.disabled = false;
      btn.innerHTML = originalHtml;
      lucide.createIcons();
      console.error('Error updating report:', error);
      showJobfinderToast('Error updating report. Please try again.', 'danger');
    });
  }
  
  // Function to refresh chat reports modal content without closing it
  function refreshChatReportsModal() {
    fetch('admin_dashboard.php?action=get_chat_reports')
      .then(response => response.json())
      .then(data => {
        var tbody = document.getElementById('chatReportsTableBody');
        tbody.innerHTML = '';
        
        if (data.success && data.reports && data.reports.length > 0) {
          data.reports.forEach(function(report) {
            var statusBadge = '';
            if (report.status === 'pending') {
              statusBadge = '<span class="badge bg-warning text-dark">Pending</span>';
            } else if (report.status === 'reviewed') {
              statusBadge = '<span class="badge bg-info">Reviewed</span>';
            }
            
            var row = '<tr>';
            row += '<td class="px-3 py-3">' + report.reporter_name + '<br><small class="text-muted">ID: ' + report.reporter_id + '</small></td>';
            row += '<td class="px-3 py-3">' + report.reported_name + '<br><small class="text-muted">ID: ' + report.reported_id + '</small></td>';
            row += '<td class="px-3 py-3"><span class="badge bg-danger">' + report.reason + '</span></td>';
            row += '<td class="px-3 py-3" style="max-width:200px;"><small>' + (report.details || 'N/A') + '</small></td>';
            row += '<td class="px-3 py-3">' + statusBadge + '</td>';
            row += '<td class="px-3 py-3"><small>' + report.created_at + '</small></td>';
            row += '<td class="px-3 py-3">';
            row += '<button class="btn btn-sm btn-success me-1 resolve-report-btn" data-id="' + report.id + '" title="Mark as Resolved"><i data-lucide="check" style="width:14px;height:14px;"></i></button>';
            row += '<button class="btn btn-sm btn-secondary dismiss-report-btn" data-id="' + report.id + '" title="Dismiss"><i data-lucide="x" style="width:14px;height:14px;"></i></button>';
            row += '</td>';
            row += '</tr>';
            tbody.innerHTML += row;
          });
          
          // Reinitialize Lucide icons
          setTimeout(() => lucide.createIcons(), 100);
        } else {
          tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">No reports found</td></tr>';
        }
      })
      .catch(error => {
        console.error('Error refreshing reports:', error);
        showJobfinderToast('Error refreshing reports. Please try again.', 'danger');
      });
  }
  
  // Function to refresh jobfinder table
  function refreshJobfinderTable() {
    // Reload the panel content
    fetch('admin_dashboard.php?panel=jobfinder', {
      method: 'GET',
      credentials: 'same-origin'
    })
    .then(response => response.text())
    .then(html => {
      // Extract the table from the response
      var parser = new DOMParser();
      var doc = parser.parseFromString(html, 'text/html');
      var newTable = doc.querySelector('#jobfinderTable tbody');
      var currentTable = document.querySelector('#jobfinderTable tbody');
      
      if (newTable && currentTable) {
        currentTable.innerHTML = newTable.innerHTML;
        lucide.createIcons();
      }
    })
    .catch(error => {
      console.error('Error refreshing table:', error);
    });
  }
  
  // Function to show toast notification
  function showJobfinderToast(message, type = 'success') {
    var alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-' + type + ' alert-dismissible fade show position-fixed';
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = '<strong>' + (type === 'success' ? '✓' : '⚠') + '</strong> ' + message +
      '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    
    document.body.appendChild(alertDiv);
    
    // Auto remove after 3 seconds
    setTimeout(function() {
      alertDiv.remove();
    }, 3000);
  }
  
  // Handle confirm block button
  var confirmBlockBtn = document.getElementById('confirmBlockJobfinderBtn');
  if (confirmBlockBtn) {
    confirmBlockBtn.addEventListener('click', function() {
      if (currentBlockUserId) {
        var btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Blocking...';
        
        // Send AJAX request
        var formData = new FormData();
        formData.append('resident_id', currentBlockUserId);
        formData.append('ajax_jobfinder_action', 'block');
        
        fetch('admin_dashboard.php', {
          method: 'POST',
          body: formData,
          credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
          btn.disabled = false;
          btn.innerHTML = '<i data-lucide="lock" style="width:16px;height:16px;vertical-align:middle;margin-right:4px;"></i> Block User';
          lucide.createIcons();
          
          // Close modal
          var blockModal = bootstrap.Modal.getInstance(document.getElementById('blockJobfinderModal'));
          if (blockModal) blockModal.hide();
          
          if (data.success) {
            showJobfinderToast(data.message, 'success');
            refreshJobfinderTable();
          } else {
            showJobfinderToast(data.message, 'danger');
          }
        })
        .catch(error => {
          btn.disabled = false;
          btn.innerHTML = '<i data-lucide="lock" style="width:16px;height:16px;vertical-align:middle;margin-right:4px;"></i> Block User';
          lucide.createIcons();
          console.error('Error blocking user:', error);
          showJobfinderToast('Error blocking user. Please try again.', 'danger');
        });
      }
    });
  }
  
  // Handle confirm verify button
  var confirmVerifyBtn = document.getElementById('confirmVerifyJobfinderBtn');
  if (confirmVerifyBtn) {
    confirmVerifyBtn.addEventListener('click', function() {
      if (currentVerifyUserId) {
        var btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Verifying...';
        
        // Send AJAX request
        var formData = new FormData();
        formData.append('resident_id', currentVerifyUserId);
        formData.append('ajax_jobfinder_action', 'verify');
        
        fetch('admin_dashboard.php', {
          method: 'POST',
          body: formData,
          credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
          btn.disabled = false;
          btn.innerHTML = '<i data-lucide="check-circle" style="width:16px;height:16px;vertical-align:middle;margin-right:4px;"></i> Verify User';
          lucide.createIcons();
          
          // Close modal
          var verifyModal = bootstrap.Modal.getInstance(document.getElementById('verifyJobfinderModal'));
          if (verifyModal) verifyModal.hide();
          
          if (data.success) {
            showJobfinderToast(data.message, 'success');
            refreshJobfinderTable();
          } else {
            showJobfinderToast(data.message, 'danger');
          }
        })
        .catch(error => {
          btn.disabled = false;
          btn.innerHTML = '<i data-lucide="check-circle" style="width:16px;height:16px;vertical-align:middle;margin-right:4px;"></i> Verify User';
          lucide.createIcons();
          console.error('Error verifying user:', error);
          showJobfinderToast('Error verifying user. Please try again.', 'danger');
        });
      }
    });
  }
  
  // Handle confirm unverify button
  var confirmUnverifyBtn = document.getElementById('confirmUnverifyJobfinderBtn');
  if (confirmUnverifyBtn) {
    confirmUnverifyBtn.addEventListener('click', function() {
      if (currentUnverifyUserId) {
        var btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Removing...';
        
        // Send AJAX request
        var formData = new FormData();
        formData.append('resident_id', currentUnverifyUserId);
        formData.append('ajax_jobfinder_action', 'unverify');
        
        fetch('admin_dashboard.php', {
          method: 'POST',
          body: formData,
          credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
          btn.disabled = false;
          btn.innerHTML = '<i data-lucide="shield-off" style="width:16px;height:16px;vertical-align:middle;margin-right:4px;"></i> Remove Verification';
          lucide.createIcons();
          
          // Close modal
          var unverifyModal = bootstrap.Modal.getInstance(document.getElementById('unverifyJobfinderModal'));
          if (unverifyModal) unverifyModal.hide();
          
          if (data.success) {
            showJobfinderToast(data.message, 'success');
            refreshJobfinderTable();
          } else {
            showJobfinderToast(data.message, 'danger');
          }
        })
        .catch(error => {
          btn.disabled = false;
          btn.innerHTML = '<i data-lucide="shield-off" style="width:16px;height:16px;vertical-align:middle;margin-right:4px;"></i> Remove Verification';
          lucide.createIcons();
          console.error('Error unverifying user:', error);
          showJobfinderToast('Error unverifying user. Please try again.', 'danger');
        });
      }
    });
  }
  
  // Handle confirm unblock button
  var confirmUnblockBtn = document.getElementById('confirmUnblockJobfinderBtn');
  if (confirmUnblockBtn) {
    confirmUnblockBtn.addEventListener('click', function() {
      if (window.currentUnblockUserId) {
        var btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Unblocking...';
        
        // Send AJAX request
        var formData = new FormData();
        formData.append('resident_id', window.currentUnblockUserId);
        formData.append('ajax_jobfinder_action', 'unblock');
        
        fetch('admin_dashboard.php', {
          method: 'POST',
          body: formData,
          credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
          btn.disabled = false;
          btn.innerHTML = '<i data-lucide="unlock" style="width:16px;height:16px;vertical-align:middle;margin-right:4px;"></i> Unblock User';
          lucide.createIcons();
          
          // Close unblock modal
          var unblockModal = bootstrap.Modal.getInstance(document.getElementById('unblockJobfinderModal'));
          if (unblockModal) unblockModal.hide();
          
          if (data.success) {
            // Remove the row from blocked users modal if it exists
            if (window.currentUnblockBtn) {
              window.currentUnblockBtn.closest('tr').remove();
              
              // Check if table is empty
              var tbody = document.getElementById('blockedJobfinderUsersTableBody');
              if (tbody && tbody.children.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No blocked users found</td></tr>';
              }
            }
            
            // Show success message
            showJobfinderToast(data.message, 'success');
            
            // Refresh main table
            refreshJobfinderTable();
          } else {
            showJobfinderToast(data.message, 'danger');
          }
        })
        .catch(error => {
          btn.disabled = false;
          btn.innerHTML = '<i data-lucide="unlock" style="width:16px;height:16px;vertical-align:middle;margin-right:4px;"></i> Unblock User';
          lucide.createIcons();
          console.error('Error unblocking user:', error);
          showJobfinderToast('Error unblocking user. Please try again.', 'danger');
        });
      }
    });
  }
  
  // Handle View Reports button click
  var viewReportsBtn = document.getElementById('view-chat-reports-btn');
  if (viewReportsBtn) {
    viewReportsBtn.addEventListener('click', function() {
      // Fetch chat reports
      fetch('admin_dashboard.php?action=get_chat_reports')
        .then(response => response.json())
        .then(data => {
          var tbody = document.getElementById('chatReportsTableBody');
          tbody.innerHTML = '';
          
          if (data.success && data.reports && data.reports.length > 0) {
            data.reports.forEach(function(report) {
              var statusBadge = '';
              if (report.status === 'pending') {
                statusBadge = '<span class="badge bg-warning text-dark">Pending</span>';
              } else if (report.status === 'reviewed') {
                statusBadge = '<span class="badge bg-info">Reviewed</span>';
              }
              
              var row = '<tr>';
              row += '<td class="px-3 py-3">' + report.reporter_name + '<br><small class="text-muted">ID: ' + report.reporter_id + '</small></td>';
              row += '<td class="px-3 py-3">' + report.reported_name + '<br><small class="text-muted">ID: ' + report.reported_id + '</small></td>';
              row += '<td class="px-3 py-3"><span class="badge bg-danger">' + report.reason + '</span></td>';
              row += '<td class="px-3 py-3" style="max-width:200px;"><small>' + (report.details || 'N/A') + '</small></td>';
              row += '<td class="px-3 py-3">' + statusBadge + '</td>';
              row += '<td class="px-3 py-3"><small>' + report.created_at + '</small></td>';
              row += '<td class="px-3 py-3">';
              row += '<button class="btn btn-sm btn-success me-1 resolve-report-btn" data-id="' + report.id + '" title="Mark as Resolved"><i data-lucide="check" style="width:14px;height:14px;"></i></button>';
              row += '<button class="btn btn-sm btn-secondary dismiss-report-btn" data-id="' + report.id + '" title="Dismiss"><i data-lucide="x" style="width:14px;height:14px;"></i></button>';
              row += '</td>';
              row += '</tr>';
              tbody.innerHTML += row;
            });
            
            // Reinitialize Lucide icons
            setTimeout(() => lucide.createIcons(), 100);
          } else {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">No reports found</td></tr>';
          }
          
          // Show modal
          var modal = new bootstrap.Modal(document.getElementById('chatReportsModal'));
          modal.show();
        })
        .catch(error => {
          console.error('Error loading reports:', error);
          showJobfinderToast('Error loading reports. Please try again.', 'danger');
        });
    });
  }
  
  // Handle View Resolved Reports button click
  var viewResolvedReportsBtn = document.getElementById('view-resolved-reports-btn');
  if (viewResolvedReportsBtn) {
    viewResolvedReportsBtn.addEventListener('click', function() {
      // Fetch resolved reports
      fetch('admin_dashboard.php?action=get_resolved_reports')
        .then(response => response.json())
        .then(data => {
          var tbody = document.getElementById('chatReportsTableBody');
          tbody.innerHTML = '';
          
          if (data.success && data.reports && data.reports.length > 0) {
            data.reports.forEach(function(report) {
              var statusBadge = '<span class="badge bg-success">Resolved</span>';
              
              var row = '<tr>';
              row += '<td class="px-3 py-3">' + report.reporter_name + '<br><small class="text-muted">ID: ' + report.reporter_id + '</small></td>';
              row += '<td class="px-3 py-3">' + report.reported_name + '<br><small class="text-muted">ID: ' + report.reported_id + '</small></td>';
              row += '<td class="px-3 py-3"><span class="badge bg-danger">' + report.reason + '</span></td>';
              row += '<td class="px-3 py-3" style="max-width:200px;"><small>' + (report.details || 'N/A') + '</small></td>';
              row += '<td class="px-3 py-3">' + statusBadge + '</td>';
              row += '<td class="px-3 py-3"><small>' + report.created_at + '</small></td>';
              row += '<td class="px-3 py-3"><small class="text-muted">No actions available</small></td>';
              row += '</tr>';
              tbody.innerHTML += row;
            });
            
            // Reinitialize Lucide icons
            setTimeout(() => lucide.createIcons(), 100);
          } else {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">No resolved reports found</td></tr>';
          }
          
          // Show modal with updated title
          var modal = new bootstrap.Modal(document.getElementById('chatReportsModal'));
          document.querySelector('#chatReportsModal .modal-title').textContent = 'Resolved Reports';
          modal.show();
          
          // Reset title when modal closes
          document.getElementById('chatReportsModal').addEventListener('hidden.bs.modal', function() {
            document.querySelector('#chatReportsModal .modal-title').textContent = 'JobFinder Reports';
          }, { once: true });
        })
        .catch(error => {
          console.error('Error loading resolved reports:', error);
          showJobfinderToast('Error loading resolved reports. Please try again.', 'danger');
        });
    });
  }
  
  // Handle View Dismissed Reports button click
  var viewDismissedReportsBtn = document.getElementById('view-dismissed-reports-btn');
  if (viewDismissedReportsBtn) {
    viewDismissedReportsBtn.addEventListener('click', function() {
      // Fetch dismissed reports
      fetch('admin_dashboard.php?action=get_dismissed_reports')
        .then(response => response.json())
        .then(data => {
          var tbody = document.getElementById('chatReportsTableBody');
          tbody.innerHTML = '';
          
          if (data.success && data.reports && data.reports.length > 0) {
            data.reports.forEach(function(report) {
              var statusBadge = '<span class="badge bg-secondary">Dismissed</span>';
              
              var row = '<tr>';
              row += '<td class="px-3 py-3">' + report.reporter_name + '<br><small class="text-muted">ID: ' + report.reporter_id + '</small></td>';
              row += '<td class="px-3 py-3">' + report.reported_name + '<br><small class="text-muted">ID: ' + report.reported_id + '</small></td>';
              row += '<td class="px-3 py-3"><span class="badge bg-danger">' + report.reason + '</span></td>';
              row += '<td class="px-3 py-3" style="max-width:200px;"><small>' + (report.details || 'N/A') + '</small></td>';
              row += '<td class="px-3 py-3">' + statusBadge + '</td>';
              row += '<td class="px-3 py-3"><small>' + report.created_at + '</small></td>';
              row += '<td class="px-3 py-3"><small class="text-muted">No actions available</small></td>';
              row += '</tr>';
              tbody.innerHTML += row;
            });
            
            // Reinitialize Lucide icons
            setTimeout(() => lucide.createIcons(), 100);
          } else {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">No dismissed reports found</td></tr>';
          }
          
          // Show modal with updated title
          var modal = new bootstrap.Modal(document.getElementById('chatReportsModal'));
          document.querySelector('#chatReportsModal .modal-title').textContent = 'Dismissed Reports';
          modal.show();
          
          // Reset title when modal closes
          document.getElementById('chatReportsModal').addEventListener('hidden.bs.modal', function() {
            document.querySelector('#chatReportsModal .modal-title').textContent = 'JobFinder Reports';
          }, { once: true });
        })
        .catch(error => {
          console.error('Error loading dismissed reports:', error);
          showJobfinderToast('Error loading dismissed reports. Please try again.', 'danger');
        });
    });
  }
});
</script>

<!-- Chat Reports Modal -->
<div class="modal fade" id="chatReportsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header border-0 pb-2" style="background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);">
        <div class="d-flex align-items-center gap-2">
          <div class="d-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-25" style="width: 40px; height: 40px;">
            <i data-lucide="alert-triangle" style="width:20px;height:20px;color:white;"></i>
          </div>
          <h5 class="modal-title text-white fw-bold mb-0">JobFinder Reports</h5>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <div style="max-height: 70vh; overflow-y: auto; overflow-x: hidden; scroll-behavior: smooth;">
          <table class="table table-hover mb-0">
            <thead style="background: linear-gradient(160deg, #f8f9fa 0%, #e9ecef 100%); position: sticky; top: 0; z-index: 10; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
              <tr>
                <th class="border-0 py-3 px-3" style="font-weight:600;color:#495057;">Reporter</th>
                <th class="border-0 py-3 px-3" style="font-weight:600;color:#495057;">Reported User</th>
                <th class="border-0 py-3 px-3" style="font-weight:600;color:#495057;">Reason</th>
                <th class="border-0 py-3 px-3" style="font-weight:600;color:#495057;">Details</th>
                <th class="border-0 py-3 px-3" style="font-weight:600;color:#495057;">Status</th>
                <th class="border-0 py-3 px-3" style="font-weight:600;color:#495057;">Date</th>
                <th class="border-0 py-3 px-3" style="font-weight:600;color:#495057;">Actions</th>
              </tr>
            </thead>
            <tbody id="chatReportsTableBody">
              <tr>
                <td colspan="7" class="text-center py-4">
                  <div class="spinner-border text-warning" role="status">
                    <span class="visually-hidden">Loading...</span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer border-0 pt-2 px-4 pb-4">
        <button type="button" class="btn btn-secondary px-4 py-2 fw-semibold" data-bs-dismiss="modal">
          <i data-lucide="x-circle" style="width:16px;height:16px;vertical-align:middle;margin-right:4px;"></i>
          Close
        </button>
      </div>
    </div>
  </div>
</div>

</div>


<!-- Manage Admin Panel (hidden by default) -->
<div id="panel-manage-admin" class="panel-section" style="display:none;">
<?php
include 'config.php';
$manage_admin_success = false;
$manage_admin_action = "";
$manage_admin_username = "";
$manage_admin_error = "";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'main') {
    echo "<div class='alert alert-danger text-center'>⛔ Access denied. Only the main admin can manage admins. <a href='admin_dashboard.php'>Back</a></div>";
} else {
    // Handle delete action
    if (isset($_GET['delete'])) {
        $username_to_delete = $_GET['delete'];
        if ($username_to_delete === 'admin') {
            $manage_admin_error = "You cannot delete the main admin.";
        } else {
            $stmt = $conn->prepare("DELETE FROM admin_accounts WHERE admin_id = ?");
            $stmt->bind_param("s", $username_to_delete);
            if ($stmt->execute()) {
                $manage_admin_success = true;
                $manage_admin_action = "deleted";
                $manage_admin_username = $username_to_delete;
                
                // Log the action
                $admin_username = $_SESSION['admin_username'] ?? 'Unknown';
                $action_log = "Deleted admin: $username_to_delete";
                $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
                $log->bind_param("ss", $admin_username, $action_log);
                $log->execute();
                $log->close();
            }
            $stmt->close();
        }
    }

    // Handle main admin password change
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_main_admin_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Fetch main admin password from main_admin table
        $stmt = $conn->prepare("SELECT password FROM main_admin WHERE username = 'admin'");
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result && $result['password'] === $current_password) {
            if ($new_password === $confirm_password) {
                $update = $conn->prepare("UPDATE main_admin SET password = ? WHERE username = 'admin'");
                $update->bind_param("s", $new_password);
                
                if ($update->execute()) {
                    $manage_admin_success = true;
                    $manage_admin_action = "password changed";
                    $manage_admin_username = "Main Admin";
                    
                    // Log the action
                    $admin_username = $_SESSION['admin_username'] ?? 'admin';
                    $action_log = "Changed main admin password";
                    $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
                    $log->bind_param("ss", $admin_username, $action_log);
                    $log->execute();
                    $log->close();
                } else {
                    $manage_admin_error = "Failed to update password.";
                }
                $update->close();
            } else {
                $manage_admin_error = "New password and confirmation do not match.";
            }
        } else {
            $manage_admin_error = "Current password is incorrect.";
        }
        $stmt->close();
    }

    // Handle edit form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_admin'])) {
        $old_username = $_POST['old_username'];
        $new_username = $_POST['admin_id'];
        $new_password = $_POST['admin_password'];
        $new_name     = $_POST['admin_name'];

        $stmt = $conn->prepare("UPDATE admin_accounts SET admin_id = ?, admin_name = ?, admin_password = ? WHERE admin_id = ?");
        $stmt->bind_param("ssss", $new_username, $new_name, $new_password, $old_username);

        if ($stmt->execute()) {
            $manage_admin_success = true;
            $manage_admin_action = "updated";
            $manage_admin_username = $new_username;
            
            // Log the action
            $admin_username = $_SESSION['admin_username'] ?? 'Unknown';
            $action_log = "Updated admin: $old_username to $new_username";
            $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
            $log->bind_param("ss", $admin_username, $action_log);
            $log->execute();
            $log->close();
        } else {
            $manage_admin_error = "Error updating admin.";
        }
        $stmt->close();
    }

    // Fetch all admins
    $result = $conn->query("SELECT admin_id, admin_name, admin_password FROM admin_accounts");
    $admins = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $admins[] = $row;
        }
    }
?>
  <div class="container py-4">
    <div class="card shadow-lg p-4 mb-4" style="max-width:900px;margin:auto;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2 class="fw-bold text-success mb-0">Registered Admin Accounts</h2>
        <button type="button" class="btn btn-success" onclick="showPanel(document.getElementById('panel-register-admin')); history.replaceState(null, '', '?panel=register-admin');">
          <i class="bi bi-plus-circle"></i> Add New Admin
        </button>
      </div>
      <div class="table-responsive" id="manage-admin-table-container">
        <table class="table table-bordered table-hover align-middle">
          <thead class="table-success">
            <tr>
              <th>Full Name</th>
              <th>Username</th>
              <th>Password</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Main Admin</td>
              <td>admin</td>
              <td><i>Hidden</i></td>
              <td>
                <button type="button" class="btn btn-sm btn-warning" onclick="showMainAdminPasswordModal()">
                  <i class="bi bi-key"></i> Change Password
                </button>
              </td>
            </tr>
            <?php foreach ($admins as $admin): ?>
              <?php if ($admin['admin_id'] !== 'admin'): ?>
                <tr>
                  <td><?= htmlspecialchars($admin['admin_name']) ?></td>
                  <td><?= htmlspecialchars($admin['admin_id']) ?></td>
                  <td>••••••</td>
                  <td>
                    <button type="button" class="btn btn-sm btn-primary admin-edit-btn" data-username="<?= htmlspecialchars($admin['admin_id']) ?>" data-name="<?= htmlspecialchars($admin['admin_name']) ?>" data-password="<?= htmlspecialchars($admin['admin_password']) ?>">Edit</button>
                    <button type="button" class="btn btn-sm btn-danger admin-delete-btn" data-username="<?= htmlspecialchars($admin['admin_id']) ?>">Delete</button>
                  </td>
                </tr>
              <?php endif; ?>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php
}
?>

  <!-- Main Admin Change Password Modal -->
  <div id="mainAdminPasswordModal" style="
      position: fixed;
      top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(0, 0, 0, 0.5);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 99999;
      animation: fadeIn 0.3s ease;
  ">
      <div style="
          background: white;
          border-radius: 15px;
          padding: 30px 40px;
          box-shadow: 0 8px 30px rgba(0,0,0,0.3);
          max-width: 500px;
          width: 90%;
          font-family: Arial, sans-serif;
          animation: slideDown 0.4s ease;
      ">
          <h3 style="margin: 0 0 20px 0; font-weight: 700; color: #f59e0b; text-align: center;">
            <i class="bi bi-key"></i> Change Main Admin Password
          </h3>
          <form method="POST" id="mainAdminPasswordForm" onsubmit="return false;">
              <input type="hidden" name="change_main_admin_password" value="1">
              
              <div style="margin-bottom: 15px;">
                  <label style="display: block; margin-bottom: 5px; font-weight: 600;">Current Password</label>
                  <input type="password" name="current_password" id="main_current_password" class="form-control" required>
              </div>

              <div style="margin-bottom: 15px;">
                  <label style="display: block; margin-bottom: 5px; font-weight: 600;">New Password</label>
                  <input type="password" name="new_password" id="main_new_password" class="form-control" required>
              </div>

              <div style="margin-bottom: 20px;">
                  <label style="display: block; margin-bottom: 5px; font-weight: 600;">Confirm New Password</label>
                  <input type="password" name="confirm_password" id="main_confirm_password" class="form-control" required>
              </div>

              <div id="mainAdminPasswordError" style="display: none; margin-bottom: 15px;" class="alert alert-danger"></div>

              <div style="text-align: center;">
                  <button type="button" class="btn btn-warning" style="padding: 10px 25px; margin-right: 10px;" id="change-main-password-btn">
                    <i class="bi bi-key"></i> Change Password
                  </button>
                  <button type="button" class="btn btn-secondary" style="padding: 10px 25px;" onclick="closeMainAdminPasswordModal()">Cancel</button>
              </div>
          </form>
      </div>
  </div>

  <!-- Edit Modal Popup -->
  <div id="editAdminModal" style="
      position: fixed;
      top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(0, 0, 0, 0.5);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 99999;
      animation: fadeIn 0.3s ease;
  ">
      <div style="
          background: white;
          border-radius: 15px;
          padding: 30px 40px;
          box-shadow: 0 8px 30px rgba(0,0,0,0.3);
          max-width: 500px;
          width: 90%;
          font-family: Arial, sans-serif;
          animation: slideDown 0.4s ease;
      ">
          <h3 style="margin: 0 0 20px 0; font-weight: 700; color: #2e7d32; text-align: center;">Edit Admin Account</h3>
          <form method="POST" id="editAdminForm" onsubmit="return false;">
              <input type="hidden" name="old_username" id="modal_old_username">
              <input type="hidden" name="update_admin" value="1">
              
              <div style="margin-bottom: 15px;">
                  <label style="display: block; margin-bottom: 5px; font-weight: 600;">Full Name</label>
                  <input type="text" name="admin_name" id="modal_admin_name" class="form-control" required>
              </div>

              <div style="margin-bottom: 15px;">
                  <label style="display: block; margin-bottom: 5px; font-weight: 600;">Username</label>
                  <input type="text" name="admin_id" id="modal_admin_id" class="form-control" required>
              </div>

              <div style="margin-bottom: 20px;">
                  <label style="display: block; margin-bottom: 5px; font-weight: 600;">Password</label>
                  <input type="text" name="admin_password" id="modal_admin_password" class="form-control" required>
              </div>

              <div style="text-align: center;">
                  <button type="button" class="btn btn-success" style="padding: 10px 25px; margin-right: 10px;" id="update-admin-btn">Update</button>
                  <button type="button" class="btn btn-secondary" style="padding: 10px 25px;" onclick="closeEditModal()">Cancel</button>
              </div>
          </form>
      </div>
  </div>

  <!-- Delete Admin Confirmation Modal -->
  <div class="modal fade" id="deleteAdminModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header border-0 pb-2" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
          <div class="d-flex align-items-center gap-2 w-100">
            <div class="d-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-25" style="width: 40px; height: 40px;">
              <i class="bi bi-exclamation-triangle-fill text-white fs-5"></i>
            </div>
            <h5 class="modal-title text-white fw-bold mb-0">Delete Admin Account</h5>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="alert alert-danger border-0 d-flex align-items-start gap-2 mb-3" style="background-color: #fee2e2;">
            <i class="bi bi-shield-exclamation text-danger fs-5 mt-1"></i>
            <div>
              <strong class="text-danger d-block mb-1">Warning: This action cannot be undone!</strong>
              <small class="text-danger">All data associated with this admin account will be permanently removed.</small>
            </div>
          </div>
          <p class="mb-2 fw-semibold text-secondary">Are you sure you want to delete this admin account?</p>
          <div class="bg-light p-3 rounded border">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-person-circle text-danger fs-4"></i>
              <div>
                <small class="text-muted d-block" style="font-size: 0.75rem;">Username</small>
                <strong id="deleteAdminUsername" class="text-dark"></strong>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0 px-4 pb-4">
          <button type="button" class="btn btn-light px-4 py-2 fw-semibold" data-bs-dismiss="modal" style="border: 2px solid #e0e0e0;">
            <i class="bi bi-x-circle me-1"></i>
            Cancel
          </button>
          <button type="button" id="confirmDeleteAdminBtn" class="btn btn-danger px-4 py-2 fw-semibold shadow-sm" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border: none;">
            <i class="bi bi-trash me-1"></i>
            Delete Admin
          </button>
        </div>
      </div>
    </div>
  </div>

  <style>
  #deleteAdminModal .modal-content {
    border-radius: 16px;
    overflow: hidden;
  }
  
  #deleteAdminModal .modal-header {
    padding: 1.5rem;
  }
  
  #confirmDeleteAdminBtn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4) !important;
    transition: all 0.3s ease;
  }
  
  #deleteAdminModal .btn-light:hover {
    background-color: #f8f9fa;
    border-color: #ef4444 !important;
    color: #ef4444;
    transition: all 0.3s ease;
  }
  </style>

  <script>
  function showEditModal(username, name, password) {
    document.getElementById('editAdminModal').style.display = 'flex';
    document.getElementById('modal_old_username').value = username;
    document.getElementById('modal_admin_id').value = username;
    document.getElementById('modal_admin_name').value = name;
    document.getElementById('modal_admin_password').value = password;
  }

  function closeEditModal() {
    document.getElementById('editAdminModal').style.display = 'none';
  }
  
  // Function to reload admin table
  function reloadAdminTable() {
    fetch('admin_dashboard.php?panel=manage-admin', {
      method: 'GET',
      credentials: 'same-origin'
    })
    .then(function(response) { return response.text(); })
    .then(function(html) {
      var parser = new DOMParser();
      var doc = parser.parseFromString(html, 'text/html');
      
      var newTable = doc.querySelector('#manage-admin-table-container');
      var currentTable = document.querySelector('#manage-admin-table-container');
      if (newTable && currentTable) {
        currentTable.innerHTML = newTable.innerHTML;
        attachManageAdminHandlers();
      }
    });
  }
  
  // Function to attach event handlers
  function attachManageAdminHandlers() {
    // Edit buttons
    document.querySelectorAll('.admin-edit-btn').forEach(function(btn) {
      btn.addEventListener('click', function() {
        var username = btn.getAttribute('data-username');
        var name = btn.getAttribute('data-name');
        var password = btn.getAttribute('data-password');
        showEditModal(username, name, password);
      });
    });
    
    // Delete buttons
    document.querySelectorAll('.admin-delete-btn').forEach(function(btn) {
      btn.addEventListener('click', function() {
        var username = btn.getAttribute('data-username');
        
        // Store username in global variable for modal
        window.currentDeleteAdminUsername = username;
        
        // Update modal content
        document.getElementById('deleteAdminUsername').textContent = username;
        
        // Show modal
        var modal = new bootstrap.Modal(document.getElementById('deleteAdminModal'));
        modal.show();
      });
    });
    
    // Update button
    var updateBtn = document.getElementById('update-admin-btn');
    if (updateBtn) {
      updateBtn.addEventListener('click', function() {
        var originalText = updateBtn.innerHTML;
        updateBtn.disabled = true;
        updateBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Updating...';
        
        var formData = new FormData(document.getElementById('editAdminForm'));
        
        fetch('admin_dashboard.php?panel=manage-admin', {
          method: 'POST',
          body: formData,
          credentials: 'same-origin'
        })
        .then(function(response) { return response.text(); })
        .then(function(html) {
          // Show success message
          var successMsg = document.createElement('div');
          successMsg.className = 'alert alert-success';
          successMsg.innerHTML = '<strong>✓ Updated!</strong> Admin updated successfully.';
          successMsg.style.position = 'fixed';
          successMsg.style.top = '20px';
          successMsg.style.right = '20px';
          successMsg.style.zIndex = '9999';
          document.body.appendChild(successMsg);
          
          setTimeout(function() {
            successMsg.remove();
          }, 3000);
          
          // Close modal and reload table
          closeEditModal();
          reloadAdminTable();
          
          updateBtn.disabled = false;
          updateBtn.innerHTML = originalText;
        })
        .catch(function(error) {
          console.error('Error updating admin:', error);
          alert('Error updating admin. Please try again.');
          updateBtn.disabled = false;
          updateBtn.innerHTML = originalText;
        });
      });
    }
  }
  
  // Initial attachment
  attachManageAdminHandlers();
  
  // Main Admin Password Change Functions
  function showMainAdminPasswordModal() {
    document.getElementById('mainAdminPasswordModal').style.display = 'flex';
    document.getElementById('mainAdminPasswordForm').reset();
    document.getElementById('mainAdminPasswordError').style.display = 'none';
  }
  
  function closeMainAdminPasswordModal() {
    document.getElementById('mainAdminPasswordModal').style.display = 'none';
    document.getElementById('mainAdminPasswordForm').reset();
    document.getElementById('mainAdminPasswordError').style.display = 'none';
  }
  
  // Handle main admin password change
  (function() {
    var changeBtn = document.getElementById('change-main-password-btn');
    if (changeBtn) {
      changeBtn.addEventListener('click', function() {
        var currentPassword = document.getElementById('main_current_password').value;
        var newPassword = document.getElementById('main_new_password').value;
        var confirmPassword = document.getElementById('main_confirm_password').value;
        var errorDiv = document.getElementById('mainAdminPasswordError');
        
        // Client-side validation
        if (!currentPassword || !newPassword || !confirmPassword) {
          errorDiv.textContent = 'All fields are required.';
          errorDiv.style.display = 'block';
          return;
        }
        
        if (newPassword !== confirmPassword) {
          errorDiv.textContent = 'New password and confirmation do not match.';
          errorDiv.style.display = 'block';
          return;
        }
        
        if (newPassword.length < 4) {
          errorDiv.textContent = 'Password must be at least 4 characters long.';
          errorDiv.style.display = 'block';
          return;
        }
        
        // Disable button and show loading
        var originalText = changeBtn.innerHTML;
        changeBtn.disabled = true;
        changeBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Changing...';
        errorDiv.style.display = 'none';
        
        var formData = new FormData(document.getElementById('mainAdminPasswordForm'));
        
        fetch('admin_dashboard.php?panel=manage-admin', {
          method: 'POST',
          body: formData,
          credentials: 'same-origin'
        })
        .then(function(response) { return response.text(); })
        .then(function(html) {
          // Check if there's an error in the response
          var parser = new DOMParser();
          var doc = parser.parseFromString(html, 'text/html');
          
          // Look for success or error indicators
          var hasSuccess = html.includes('Password Changed Successfully');
          
          // Check for error modal
          var hasErrorModal = html.includes('manageAdminErrorModal');
          
          if (hasSuccess) {
            // Close modal first
            closeMainAdminPasswordModal();
            
            // Show success message
            var successMsg = document.createElement('div');
            successMsg.className = 'alert alert-success alert-dismissible fade show';
            successMsg.innerHTML = '<strong>✓ Success!</strong> Main admin password changed successfully.<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            successMsg.style.position = 'fixed';
            successMsg.style.top = '20px';
            successMsg.style.right = '20px';
            successMsg.style.zIndex = '9999';
            document.body.appendChild(successMsg);
            
            setTimeout(function() {
              successMsg.remove();
            }, 5000);
          } else if (hasErrorModal) {
            // Extract error message from error modal
            var errorMatch = html.match(/Current password is incorrect|New password and confirmation do not match|Failed to update password/);
            if (errorMatch) {
              errorDiv.textContent = errorMatch[0];
              errorDiv.style.display = 'block';
            } else {
              errorDiv.textContent = 'An error occurred. Please try again.';
              errorDiv.style.display = 'block';
            }
          } else {
            // No clear success or error - might be a different issue
            console.log('Response HTML:', html.substring(0, 500));
            errorDiv.textContent = 'Unable to process request. Please check console for details.';
            errorDiv.style.display = 'block';
          }
          
          changeBtn.disabled = false;
          changeBtn.innerHTML = originalText;
        })
        .catch(function(error) {
          console.error('Error changing password:', error);
          errorDiv.textContent = 'Network error. Please try again.';
          errorDiv.style.display = 'block';
          changeBtn.disabled = false;
          changeBtn.innerHTML = originalText;
        });
      });
    }
  })();
  
  // Handle delete admin confirmation (attach once)
  (function() {
    var confirmDeleteAdminBtn = document.getElementById('confirmDeleteAdminBtn');
    if (confirmDeleteAdminBtn) {
      confirmDeleteAdminBtn.addEventListener('click', function() {
        var username = window.currentDeleteAdminUsername;
        
        if (!username) {
          console.error('No admin username to delete');
          alert('Error: No admin selected for deletion');
          return;
        }
        
        // Disable button and show loading
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Deleting...';
        
        // Close modal
        var modal = bootstrap.Modal.getInstance(document.getElementById('deleteAdminModal'));
        if (modal) modal.hide();
        
        console.log('Deleting admin:', username);
        
        fetch('admin_dashboard.php?panel=manage-admin&delete=' + encodeURIComponent(username), {
          method: 'GET',
          credentials: 'same-origin'
        })
        .then(function(response) { 
          return response.text(); 
        })
        .then(function(html) {
          // Reset button
          confirmDeleteAdminBtn.disabled = false;
          confirmDeleteAdminBtn.innerHTML = '<i class="bi bi-trash me-1"></i>Delete Admin';
          
          // Show success message
          var successMsg = document.createElement('div');
          successMsg.className = 'alert alert-success alert-dismissible fade show';
          successMsg.style.position = 'fixed';
          successMsg.style.top = '20px';
          successMsg.style.right = '20px';
          successMsg.style.zIndex = '9999';
          successMsg.innerHTML = '<strong>✓ Success!</strong> Admin "' + username + '" deleted successfully.<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
          document.body.appendChild(successMsg);
          
          setTimeout(function() {
            successMsg.remove();
          }, 3000);
          
          // Reload table
          reloadAdminTable();
          
          // Clear global variable
          window.currentDeleteAdminUsername = null;
        })
        .catch(function(error) {
          console.error('Error deleting admin:', error);
          
          // Reset button
          confirmDeleteAdminBtn.disabled = false;
          confirmDeleteAdminBtn.innerHTML = '<i class="bi bi-trash me-1"></i>Delete Admin';
          
          alert('Error deleting admin. Please try again.');
        });
      });
    }
  })();
  </script>

  <?php if ($manage_admin_success): ?>
  <!-- Success Popup Modal -->
  <div id="manageAdminSuccessModal" style="
      position: fixed;
      top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(0, 0, 0, 0.5);
      display: flex; align-items: center; justify-content: center;
      z-index: 99999;
      animation: fadeIn 0.3s ease;
  ">
      <div style="
          background: #d1e7dd;
          border: 2px solid #0f5132;
          border-radius: 15px;
          padding: 30px 40px;
          text-align: center;
          box-shadow: 0 8px 30px rgba(0,0,0,0.3);
          max-width: 450px;
          width: 90%;
          color: #0f5132;
          font-family: Arial, sans-serif;
          animation: slideDown 0.4s ease;
      ">
          <div style="font-size: 60px; margin-bottom: 15px;">✅</div>
          <h3 style="margin: 0 0 15px 0; font-weight: 700; color: #0f5132;">
              <?php 
                if ($manage_admin_action === 'updated') {
                    echo 'Admin Updated Successfully!';
                } elseif ($manage_admin_action === 'deleted') {
                    echo 'Admin Deleted Successfully!';
                } elseif ($manage_admin_action === 'password changed') {
                    echo 'Password Changed Successfully!';
                }
              ?>
          </h3>
          <p style="margin: 10px 0; font-size: 16px;">
              Admin <strong><?= htmlspecialchars($manage_admin_username) ?></strong> has been <?= $manage_admin_action ?>.
          </p>
          <button onclick="window.location.href='admin_dashboard.php?panel=manage-admin';" 
              style="
                  background: #0f5132;
                  color: white;
                  border: none;
                  padding: 12px 30px;
                  border-radius: 8px;
                  cursor: pointer;
                  margin-top: 15px;
                  font-size: 16px;
                  font-weight: 600;
                  transition: all 0.3s;
              "
              onmouseover="this.style.background='#198754'"
              onmouseout="this.style.background='#0f5132'">
              OK
          </button>
      </div>
  </div>

  <style>
  @keyframes fadeIn { 
      from {opacity:0;} 
      to {opacity:1;} 
  }
  @keyframes slideDown {
      from {opacity:0; transform:translateY(-50px);}
      to {opacity:1; transform:translateY(0);}
  }
  </style>

  <script>
  // Auto-hide after 5 seconds
  setTimeout(() => {
      window.location.href='admin_dashboard.php?panel=manage-admin';
  }, 5000);
  </script>
  <?php endif; ?>

  <?php if (!empty($manage_admin_error)): ?>
  <!-- Error Popup Modal -->
  <div id="manageAdminErrorModal" style="
      position: fixed;
      top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(0, 0, 0, 0.5);
      display: flex; align-items: center; justify-content: center;
      z-index: 99999;
      animation: fadeIn 0.3s ease;
  ">
      <div style="
          background: #f8d7da;
          border: 2px solid #842029;
          border-radius: 15px;
          padding: 30px 40px;
          text-align: center;
          box-shadow: 0 8px 30px rgba(0,0,0,0.3);
          max-width: 450px;
          width: 90%;
          color: #842029;
          font-family: Arial, sans-serif;
          animation: slideDown 0.4s ease;
      ">
          <div style="font-size: 60px; margin-bottom: 15px;">❌</div>
          <h3 style="margin: 0 0 15px 0; font-weight: 700; color: #842029;">Error</h3>
          <p style="margin: 10px 0; font-size: 16px;">
              <?= htmlspecialchars($manage_admin_error) ?>
          </p>
          <button onclick="document.getElementById('manageAdminErrorModal').remove();" 
              style="
                  background: #842029;
                  color: white;
                  border: none;
                  padding: 12px 30px;
                  border-radius: 8px;
                  cursor: pointer;
                  margin-top: 15px;
                  font-size: 16px;
                  font-weight: 600;
                  transition: all 0.3s;
              "
              onmouseover="this.style.background='#a02834'"
              onmouseout="this.style.background='#842029'">
              OK
          </button>
      </div>
  </div>
  <?php endif; ?>

</div>

<!-- Register Admin Panel (hidden by default) -->
<div id="panel-register-admin" class="panel-section" style="display:none;">
<?php
include 'config.php';
$register_success = false;
$register_username = "";
$register_error = "";
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'main') {
    echo "<div class='alert alert-danger text-center'>⛔ Only the MAIN ADMIN can register new admins. <a href='admin_dashboard.php'>Back</a></div>";
} else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST["register_admin_panel"])) {
    $new_username = $_POST['username'];
    $new_password = $_POST['password'];
    $new_name     = $_POST['name'];

    // Check if username already exists
    $stmt = $conn->prepare("SELECT * FROM admin_accounts WHERE admin_id = ?");
    $stmt->bind_param("s", $new_username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $register_error = "Username already exists.";
    } else {
        // Insert new admin
        $stmt = $conn->prepare("INSERT INTO admin_accounts (admin_id, admin_name, admin_password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $new_username, $new_name, $new_password); // ⚠️ use password_hash() in production!

        if ($stmt->execute()) {
            $register_success = true;
            $register_username = $new_username;
            
            // Log the action
            $admin_username = $_SESSION['admin_username'] ?? 'Unknown';
            $action_log = "Registered new admin: $new_username";
            $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
            $log->bind_param("ss", $admin_username, $action_log);
            $log->execute();
            $log->close();
        } else {
            $register_error = "Error: " . $conn->error;
        }
    }
}
?>
  <div class="container py-4">
    <div class="card shadow-lg p-4 mb-4" style="max-width:500px;margin:auto;">
      <h2 class="fw-bold text-success mb-3 text-center"><i data-lucide="user-plus"></i> Register New Admin</h2>
      <form id="registerAdminForm" method="POST" autocomplete="off">
        <input type="hidden" name="register_admin_panel" value="1">
        
        <div class="mb-3">
          <label class="form-label">Full Name:</label>
          <input type="text" name="name" class="form-control" required>
        </div>
        
        <div class="mb-3">
          <label class="form-label">Admin ID:</label>
          <input type="text" name="username" class="form-control" required>
        </div>
        
        <div class="mb-3">
          <label class="form-label">Password:</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        
        <div style="display: flex; gap: 10px;">
          <button type="submit" class="btn btn-success flex-fill" id="register-admin-btn">Register Admin</button>
          <button type="button" class="btn btn-secondary flex-fill" onclick="showPanel(document.getElementById('panel-manage-admin')); history.replaceState(null, '', '?panel=manage-admin');">Cancel</button>
        </div>
      </form>
      
      <div id="register-admin-message-area"></div>
    </div>
  </div>

  <?php if ($register_success): ?>
  <!-- Success Popup Modal -->
  <div id="registerSuccessModal" style="
      position: fixed;
      top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(0, 0, 0, 0.5);
      display: flex; align-items: center; justify-content: center;
      z-index: 99999;
      animation: fadeIn 0.3s ease;
  ">
      <div style="
          background: #d1e7dd;
          border: 2px solid #0f5132;
          border-radius: 15px;
          padding: 30px 40px;
          text-align: center;
          box-shadow: 0 8px 30px rgba(0,0,0,0.3);
          max-width: 450px;
          width: 90%;
          color: #0f5132;
          font-family: Arial, sans-serif;
          animation: slideDown 0.4s ease;
      ">
          <div style="font-size: 60px; margin-bottom: 15px;">✅</div>
          <h3 style="margin: 0 0 15px 0; font-weight: 700; color: #0f5132;">New Admin Registered Successfully!</h3>
          <p style="margin: 10px 0; font-size: 16px;">
              Admin <strong><?= htmlspecialchars($register_username) ?></strong> has been successfully registered.
          </p>
          <p style="margin: 10px 0; font-size: 14px; color: #666;">
              They can now log in with their credentials.
          </p>
          <button onclick="document.getElementById('registerSuccessModal').remove(); document.getElementById('registerAdminForm').reset();" 
              style="
                  background: #0f5132;
                  color: white;
                  border: none;
                  padding: 12px 30px;
                  border-radius: 8px;
                  cursor: pointer;
                  margin-top: 15px;
                  font-size: 16px;
                  font-weight: 600;
                  transition: all 0.3s;
              "
              onmouseover="this.style.background='#198754'"
              onmouseout="this.style.background='#0f5132'">
              OK
          </button>
      </div>
  </div>

  <style>
  @keyframes fadeIn { 
      from {opacity:0;} 
      to {opacity:1;} 
  }
  @keyframes slideDown {
      from {opacity:0; transform:translateY(-50px);}
      to {opacity:1; transform:translateY(0);}
  }
  </style>

  <script>
  // Auto-hide after 5 seconds and reset form
  setTimeout(() => {
      const modal = document.getElementById('registerSuccessModal');
      if (modal) {
          modal.remove();
          document.getElementById('registerAdminForm').reset();
      }
  }, 5000);
  </script>
  <?php endif; ?>
</div>

<script>
// Register Admin panel logic - will be handled by main showPanel function

const backToDashboardFromRegisterAdmin = document.getElementById('backToDashboardFromRegisterAdmin');
if (backToDashboardFromRegisterAdmin) {
  backToDashboardFromRegisterAdmin.addEventListener('click', function(e) {
    e.preventDefault();
    document.querySelectorAll('.panel-section').forEach(function(panel) { panel.style.display = 'none'; });
    document.getElementById('panel-welcome').style.display = 'block';
    history.replaceState(null, '', '?');
  });
}

// Handle Register Admin form submission
var registerAdminForm = document.getElementById('registerAdminForm');
if (registerAdminForm) {
  registerAdminForm.addEventListener('submit', function(e) {
    e.preventDefault();
    
    var registerBtn = document.getElementById('register-admin-btn');
    var originalText = registerBtn.innerHTML;
    registerBtn.disabled = true;
    registerBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Registering...';
    
    var formData = new FormData(registerAdminForm);
    
    fetch('admin_dashboard.php?panel=register-admin', {
      method: 'POST',
      body: formData,
      credentials: 'same-origin'
    })
    .then(function(response) { return response.text(); })
    .then(function(html) {
      var username = formData.get('username');
      
      // Show success message
      var successMsg = document.createElement('div');
      successMsg.className = 'alert alert-success';
      successMsg.innerHTML = '<strong>✓ Success!</strong> Admin "' + username + '" registered successfully. Redirecting to Manage Admin...';
      successMsg.style.position = 'fixed';
      successMsg.style.top = '20px';
      successMsg.style.right = '20px';
      successMsg.style.zIndex = '9999';
      document.body.appendChild(successMsg);
      
      // Reset form
      registerAdminForm.reset();
      registerBtn.disabled = false;
      registerBtn.innerHTML = originalText;
      
      // Navigate to manage-admin panel and reload table after 1.5 seconds
      setTimeout(function() {
        successMsg.remove();
        showPanel(document.getElementById('panel-manage-admin'));
        history.replaceState(null, '', '?panel=manage-admin');
        
        // Reload the admin table to show the new admin
        if (typeof reloadAdminTable === 'function') {
          reloadAdminTable();
        }
      }, 1500);
    })
    .catch(function(error) {
      console.error('Error registering admin:', error);
      alert('Error registering admin. Please try again.');
      registerBtn.disabled = false;
      registerBtn.innerHTML = originalText;
    });
  });
}
</script>
<!-- Residents Panel (hidden by default) -->
<div id="panel-add-residents" class="panel-section" style="display:none;">
  <div class="container py-2">
      <!-- Modern Header Section -->
      <div class="add-residents-header mb-3">
        <div class="header-content">
          <div class="header-icon-wrapper">
            <i class="bi bi-person-plus-fill"></i>
          </div>
          <div class="header-text">
            <h1 class="header-title">Add New Residents</h1>
            <p class="header-subtitle">Register residents individually or import multiple records via Excel</p>
          </div>
        </div>
      </div>

      <!-- Batch Register via Excel Card (separated) -->
      <div class="card shadow-lg mb-3 excel-upload-card animate-fadein" style="max-width:900px;margin:auto;background:#fff;border-radius:16px;border:none;overflow:hidden;">
        <div class="card-header" style="background:linear-gradient(135deg, #14ad0f 0%, #43e97b 100%);padding:16px 24px;border:none;">
          <h4 class="fw-bold text-white mb-0 d-flex align-items-center gap-2" style="font-size:1.1rem;">
            <i class="bi bi-file-earmark-excel" style="font-size:1.2rem;"></i> 
            Batch Register via Excel
          </h4>
          <p class="text-white mb-0 mt-1" style="opacity:0.95;font-size:0.85rem;">Upload an Excel file to register multiple residents at once</p>
        </div>
        <div class="card-body" style="padding:20px;">
          <form action="admin_dashboard.php" method="POST" enctype="multipart/form-data" id="batch-register-form">
            <div class="mb-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
              <label for="excel_file" class="form-label register-label mb-0 d-flex align-items-center gap-2" style="font-size:0.9rem;">
                <i class="bi bi-cloud-upload text-primary"></i>
                Choose Excel File:
              </label>
              <a href="resident_template.php" class="btn btn-outline-success d-flex align-items-center gap-2" style="border-radius:8px;padding:6px 14px;font-weight:600;border-width:2px;font-size:0.85rem;">
                <i class="bi bi-download"></i>
                Download Excel Template
              </a>
            </div>
            <div class="input-group mb-0" style="box-shadow:0 2px 8px rgba(0,0,0,0.08);border-radius:10px;overflow:hidden;">
              <input type="file" name="excel_file" id="excel_file" class="form-control register-input" accept=".xls,.xlsx" required style="border:2px solid #e0e0e0;padding:10px 14px;font-size:0.9rem;">
              <button type="submit" name="upload_excel" class="btn btn-success gradient-btn d-flex align-items-center gap-2" id="batch-register-btn" style="padding:10px 20px;font-size:0.95rem;">
                <i class="bi bi-upload"></i>
                Upload & Register
              </button>
            </div>  
          </form>
        </div>
      </div>

      <!-- Register Profile Card (separated) -->
      <div class="main register-modern-card animate-fadein">
        <div class="register-header-section mb-3">
          <div class="d-flex align-items-center gap-2">
            <div class="register-icon-circle">
              <i class="bi bi-person-plus-fill"></i>
            </div>
            <div>
              <h2 class="register-title mb-1">Register Resident</h2>
              <p class="register-subtitle mb-0">Fill in the details below to add a new resident</p>
            </div>
          </div>
        </div>
        <!-- Success Message Placeholder -->
        <div id="registerSuccessMsg" class="alert alert-success text-center" style="display:none;position:fixed;top:30px;left:50%;transform:translateX(-50%);z-index:9999;min-width:300px;box-shadow:0 4px 18px rgba(67,233,123,0.18);"></div>
        <form id="registerProfileForm" action="save_profile.php" method="POST" autocomplete="off">
          <div class="row g-3 align-items-end mt-2">
            <!-- Row 1: Name Fields -->
            <div class="col-lg-4 col-md-6">
              <label for="surname" class="form-label register-label">
                <i class="bi bi-person-badge text-success"></i> Surname:
              </label>
              <input type="text" name="surname" class="form-control register-input" placeholder="Enter surname" required>
            </div>
            <div class="col-lg-4 col-md-6">
              <label for="first_name" class="form-label register-label">
                <i class="bi bi-person text-success"></i> First Name:
              </label>
              <input type="text" name="first_name" class="form-control register-input" placeholder="Enter first name" required>
            </div>
            <div class="col-lg-4 col-md-6">
              <label for="middle_name" class="form-label register-label">
                <i class="bi bi-person text-success"></i> Middle Name:
              </label>
              <input type="text" name="middle_name" class="form-control register-input" placeholder="Enter middle name (optional)">
            </div>

            <!-- Row 2: Birthdate, Age, Gender, Place of Birth -->
            <div class="col-lg-3 col-md-6">
              <label for="birthdate" class="form-label register-label">
                <i class="bi bi-calendar-event text-success"></i> Birthdate:
              </label>
              <input type="date" name="birthdate" id="birthdate" class="form-control register-input" required>
            </div>
            <div class="col-lg-2 col-md-6">
              <label for="age" class="form-label register-label">
                <i class="bi bi-hash text-success"></i> Age:
              </label>
              <input type="number" name="age" id="age" class="form-control register-input" required readonly style="background:#f5f5f5;">
            </div>
            <div class="col-lg-3 col-md-6">
              <label for="sex" class="form-label register-label">
                <i class="bi bi-gender-ambiguous text-success"></i> Gender:
              </label>
              <select name="sex" class="form-control register-input" required>
                <option value="">-- Select --</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
              </select>
            </div>
            <div class="col-lg-4 col-md-6">
              <label for="place_of_birth" class="form-label register-label">
                <i class="bi bi-geo-alt text-success"></i> Place of Birth:
              </label>
              <input type="text" name="place_of_birth" class="form-control register-input" placeholder="Enter place of birth">
            </div>

            <!-- Row 3: Civil Status, Citizenship, Occupation, Education -->
            <div class="col-lg-3 col-md-6">
              <label for="civil_status" class="form-label register-label">
                <i class="bi bi-heart text-success"></i> Civil Status:
              </label>
              <select name="civil_status" class="form-control register-input">
                <option value="">-- Select --</option>
                <option value="Single">Single</option>
                <option value="Married">Married</option>
                <option value="Widowed">Widowed</option>
                <option value="Divorced">Divorced</option>
              </select>
            </div>
            <div class="col-lg-3 col-md-6">
              <label for="citizenship" class="form-label register-label">
                <i class="bi bi-flag text-success"></i> Citizenship:
              </label>
              <input type="text" name="citizenship" class="form-control register-input" placeholder="Enter citizenship">
            </div>
            <div class="col-lg-3 col-md-6">
              <label for="occupation_skills" class="form-label register-label">
                <i class="bi bi-briefcase text-success"></i> Occupation / Skills:
              </label>
              <input type="text" name="occupation_skills" class="form-control register-input" placeholder="Enter occupation or skills">
            </div>
            <div class="col-lg-3 col-md-6">
              <label for="education" class="form-label register-label">
                <i class="bi bi-mortarboard text-success"></i> Education:
              </label>
              <select name="education" class="form-control register-input">
                <option value="">-- Select --</option>
                <option value="Elementary">Elementary</option>
                <option value="High School">High School</option>
                <option value="College">College</option>
                <option value="Vocational">Vocational</option>
                <option value="Undergrad">Undergrad</option>
                <option value="Graduate">Graduate</option>
              </select>
            </div>

            <!-- Row 4: Address, Household No., Relationship -->
            <div class="col-lg-4 col-md-6">
              <label for="address" class="form-label register-label">
                <i class="bi bi-house text-success"></i> Address:
              </label>
              <input type="text" name="address" class="form-control register-input" placeholder="Enter complete address" required>
            </div>
            <div class="col-lg-4 col-md-6">
              <label for="household_id" class="form-label register-label">
                <i class="bi bi-building text-success"></i> Household No.:
              </label>
              <input type="text" name="household_id" class="form-control register-input" placeholder="e.g. 233">
            </div>
            <div class="col-lg-4 col-md-6">
              <label for="relationship" class="form-label register-label">
                <i class="bi bi-people text-success"></i> Relationship to Household Head:
              </label>
              <input type="text" name="relationship" class="form-control register-input" placeholder="e.g. Head, Son, Daughter, Wife">
            </div>

            <!-- Row 5: Checkboxes -->
            <div class="col-12">
              <div class="checkbox-group-modern">
                <div class="checkbox-item-modern">
                  <input type="checkbox" name="is_head" value="Yes" id="is_head_checkbox" class="modern-checkbox">
                  <label for="is_head_checkbox" class="checkbox-label-modern">
                    <i class="bi bi-star-fill text-warning"></i>
                    Mark as Household Head
                  </label>
                </div>
                <div class="checkbox-item-modern">
                  <input type="checkbox" name="is_pwd" value="Yes" id="is_pwd_checkbox" class="modern-checkbox">
                  <label for="is_pwd_checkbox" class="checkbox-label-modern">
                    <i class="bi bi-universal-access text-info"></i>
                    Person With Disability (PWD)
                  </label>
                </div>
              </div>
            </div>
          </div>
          <button type="submit" class="btn gradient-btn w-100 mt-3 d-flex align-items-center justify-content-center gap-2" style="font-size:1rem;padding:12px 24px;font-weight:700;" id="register-profile-btn">
            <i class="bi bi-check-circle-fill"></i>
            Save Profile
          </button>
        </form>
    </div>
  </div>
  <style>
    /* Header Section Styles */
    .add-residents-header {
      background: linear-gradient(135deg, #14ad0f 0%, #43e97b 100%);
      border-radius: 14px;
      padding: 20px 28px;
      box-shadow: 0 6px 18px rgba(20,173,15,0.2);
      animation: fadeInDown 0.6s cubic-bezier(.4,0,.2,1);
      max-width: 900px;
      margin: 0 auto;
    }
    .header-content {
      display: flex;
      align-items: center;
      gap: 16px;
    }
    .header-icon-wrapper {
      width: 50px;
      height: 50px;
      background: rgba(255,255,255,0.25);
      backdrop-filter: blur(10px);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }
    .header-icon-wrapper i {
      font-size: 1.5rem;
      color: #fff;
      filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
    }
    .header-title {
      font-size: 1.6rem;
      font-weight: 800;
      color: #fff;
      margin: 0;
      letter-spacing: 0.3px;
      text-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }
    .header-subtitle {
      font-size: 0.85rem;
      color: rgba(255,255,255,0.95);
      margin: 4px 0 0 0;
      font-weight: 400;
    }
    
    /* Excel Upload Card Animation */
    .excel-upload-card {
      animation-delay: 0.2s;
    }
    
    /* Register Card Styles */
    .register-modern-card {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 6px 24px rgba(0,0,0,0.08);
      padding: 28px 24px;
      max-width: 900px;
      margin: 0 auto;
      position: relative;
      border: 1px solid rgba(20,173,15,0.1);
      animation: fadeInUp 0.7s cubic-bezier(.4,0,.2,1);
      animation-delay: 0.4s;
      animation-fill-mode: both;
    }
    .register-header-section {
      padding-bottom: 16px;
      border-bottom: 2px solid #f0f0f0;
    }
    .register-icon-circle {
      width: 45px;
      height: 45px;
      background: linear-gradient(135deg, #14ad0f 0%, #43e97b 100%);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 3px 10px rgba(20,173,15,0.25);
    }
    .register-icon-circle i {
      font-size: 1.2rem;
      color: #fff;
    }
    .register-title {
      font-weight: 700;
      color: #2c3e50;
      letter-spacing: 0.3px;
      font-size: 1.3rem;
      margin: 0;
    }
    .register-subtitle {
      font-size: 0.8rem;
      color: #6c757d;
      font-weight: 400;
    }
    .register-label {
      color: #2c3e50;
      font-weight: 600;
      letter-spacing: 0.2px;
      font-size: 0.85rem;
      margin-bottom: 6px;
      display: flex;
      align-items: center;
      gap: 5px;
    }
    .register-input {
      border: 2px solid #e0e0e0;
      border-radius: 8px;
      padding: 8px 12px;
      font-size: 0.85rem;
      transition: all 0.3s ease;
      background: #fafafa;
    }
    .register-input:focus {
      border-color: #43e97b;
      box-shadow: 0 0 0 3px rgba(67,233,123,0.15);
      background: #fff;
      outline: none;
    }
    .register-section-title {
      font-family: 'Inter', Arial, sans-serif;
      font-weight: 600;
      color: #2e7d32;
      margin-top: 32px;
      margin-bottom: 18px;
      letter-spacing: 0.5px;
      font-size: 1.2rem;
    }
    .gradient-btn {
      background: linear-gradient(135deg,#14ad0f 0%,#43e97b 100%);
      color: #fff;
      border: none;
      border-radius: 12px;
      font-weight: 700;
      box-shadow: 0 4px 12px rgba(20,173,15,0.25);
      transition: all 0.3s ease;
      letter-spacing: 0.3px;
    }
    .gradient-btn:hover, .gradient-btn:focus {
      background: linear-gradient(135deg,#43e97b 0%,#14ad0f 100%);
      box-shadow: 0 6px 20px rgba(20,173,15,0.35);
      transform: translateY(-2px);
      color: #fff;
    }
    .animate-fadein { 
      animation: fadeInUp 0.7s cubic-bezier(.4,0,.2,1);
      animation-fill-mode: both;
    }
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(40px); }
      to { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeInDown {
      from { opacity: 0; transform: translateY(-30px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    /* Checkbox Styles */
    .checkbox-group-modern {
      display: flex;
      flex-wrap: wrap;
      gap: 16px;
      padding: 12px;
      background: #f8f9fa;
      border-radius: 10px;
      border: 2px solid #e9ecef;
    }
    .checkbox-item-modern {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .modern-checkbox {
      width: 20px;
      height: 20px;
      cursor: pointer;
      accent-color: #14ad0f;
    }
    .checkbox-label-modern {
      font-weight: 600;
      color: #2c3e50;
      cursor: pointer;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 0.85rem;
      user-select: none;
    }
    .checkbox-label-modern:hover {
      color: #14ad0f;
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
      .header-content {
        flex-direction: column;
        text-align: center;
      }
      .header-title {
        font-size: 1.75rem;
      }
      .header-subtitle {
        font-size: 0.95rem;
      }
      .add-residents-header {
        padding: 16px 16px;
      }
      .register-modern-card {
        padding: 20px 16px;
      }
      .checkbox-group-modern {
        flex-direction: column;
        gap: 16px;
      }
    }
  </style>
  <script>
  // Age auto-calc
  document.getElementById('birthdate').addEventListener('change', function() {
    const birthdate = this.value;
    if (birthdate) {
      const today = new Date();
      const birthDate = new Date(birthdate);
      if (birthDate > today) {
        this.setCustomValidity('Birthdate cannot be in the future.');
        this.reportValidity();
        document.getElementById('age').value = '';
        return;
      } else {
        this.setCustomValidity('');
      }
      let age = today.getFullYear() - birthDate.getFullYear();
      const m = today.getMonth() - birthDate.getMonth();
      if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
        age--;
      }
      document.getElementById('age').value = age >= 0 ? age : '';
    } else {
      document.getElementById('age').value = '';
      this.setCustomValidity('');
    }
  });

  // Auto-capitalize first letter of text inputs
  const textInputs = document.querySelectorAll('#registerProfileForm input[type="text"]');
  textInputs.forEach(function(input) {
    input.addEventListener('input', function(e) {
      const cursorPosition = this.selectionStart;
      const value = this.value;
      
      if (value.length > 0) {
        // Capitalize first letter
        this.value = value.charAt(0).toUpperCase() + value.slice(1);
        
        // Restore cursor position
        this.setSelectionRange(cursorPosition, cursorPosition);
      }
    });
  });

  // Listen for register-success message from save_profile.php
  window.addEventListener('message', function(event) {
    if (event.data && event.data.type === 'register-success') {
      const msg = document.getElementById('registerSuccessMsg');
      if (msg) {
        msg.textContent = event.data.msg || 'Profile registered successfully!';
        msg.style.display = 'block';
        setTimeout(() => { msg.style.display = 'none'; }, 3500);
      }
      // Reset both forms
      const form = document.getElementById('registerProfileForm');
      if (form) form.reset();
      const excelForm = document.querySelector('form[enctype="multipart/form-data"]');
      if (excelForm) excelForm.reset();
    }
  });
  </script>
</div>
<!-- End Add Residents Panel -->

<!-- Existing Residents Panel (hidden by default) -->
<div id="panel-residents" class="panel-section" style="display:none;">
<?php

include 'config.php';
// Check if admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: index.php?admin_login=required");
    exit();
}
$username = $_SESSION['admin_id'];
$role = $_SESSION['role'] ?? 'regular';
$isMainAdmin = ($role === "main");
?>
  <div class="container-fluid py-4">
    <!-- Modern Header with Gradient -->
    <div class="residents-header-modern mb-4 p-4 rounded-4 shadow-sm" style="background: linear-gradient(160deg, #14ad0f 0%, #43e97b 100%);">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="residents-icon-wrapper d-flex align-items-center justify-content-center rounded-circle" style="width: 60px; height: 60px; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); box-shadow: 0 4px 12px rgba(20,173,15,0.2);">
            <i class="bi bi-person-lines-fill text-white" style="font-size: 1.8rem; filter: drop-shadow(0 2px 4px rgba(20,173,15,0.3));"></i>
          </div>
          <div>
            <h2 class="fw-bold text-white mb-1" style="font-size: 1.75rem; text-shadow: 0 2px 8px rgba(20,173,15,0.3); letter-spacing: 0.5px;">
              Manage Residents
            </h2>
            <p class="text-white mb-0" style="font-size: 0.9rem; opacity: 0.9;">
              <i class="bi bi-info-circle me-1"></i>
              Empower your community by managing residents efficiently
            </p>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-light text-dark px-3 py-2" style="font-size: 0.9rem; border-radius: 10px;">
            <i class="bi bi-person-circle me-1"></i>
            <?= htmlspecialchars($username) ?> (<?= $isMainAdmin ? 'Main Admin' : 'Regular Admin' ?>)
          </span>
        </div>
      </div>
    </div>
    <div class="modal fade" id="excelSuccessModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content text-center p-3">
      <div class="modal-body">
        <h5 class="text-success mb-0">✅ Upload Successful</h5>
        <p class="mb-0">Your Excel file was imported successfully!</p>
      </div>
    </div>
  </div>
</div>

    <style>
      .resident-card {
        background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(20,173,15,0.08);
        border: 2px solid rgba(20,173,15,0.1);
        padding: 24px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        min-height: 180px;
        position: relative;
        overflow: hidden;
      }
      .resident-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #14ad0f 0%, #43e97b 100%);
        transform: scaleX(0);
        transition: transform 0.3s ease;
      }
      .resident-card:hover {
        box-shadow: 0 8px 24px rgba(20,173,15,0.15);
        transform: translateY(-6px);
        border-color: rgba(20,173,15,0.3);
      }
      .resident-card:hover::before {
        transform: scaleX(1);
      }
      .resident-icon {
        width: 64px;
        height: 64px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 16px;
        background: linear-gradient(135deg, #14ad0f 0%, #43e97b 100%);
        border-radius: 50%;
        box-shadow: 0 4px 12px rgba(20,173,15,0.2);
      }
      .resident-title {
        font-size: 17px;
        color: #222;
        font-weight: 600;
        text-align: center;
        margin-top: 8px;
        letter-spacing: 0.3px;
      }
      .resident-subtitle {
        font-size: 13px;
        color: #666;
        text-align: center;
        margin-top: 4px;
      }
    </style>  

    <!-- Resident Management Cards -->
    <div class="container-fluid">
      <div class="row g-4">
        <!-- Add New Residents Card -->
        <div class="col-md-4">
          <div class="resident-card" onclick="navigateToPanel('panel-add-residents')">
            <div class="resident-icon">
              <i class="bi bi-person-plus-fill text-white" style="font-size: 2rem;"></i>
            </div>
            <div class="resident-title">Add New Residents</div>
            <div class="resident-subtitle">Register new resident profiles</div>
          </div>
        </div>

        <!-- View Residents Card -->
        <div class="col-md-4">
          <div class="resident-card" onclick="navigateToPanel('panel-view-residents')">
            <div class="resident-icon">
              <i class="bi bi-people-fill text-white" style="font-size: 2rem;"></i>
            </div>
            <div class="resident-title">View Residents</div>
            <div class="resident-subtitle">Browse and manage resident records</div>
          </div>
        </div>

        <?php if ($isMainAdmin): ?>
        <!-- Manage Admin Card -->
        <div class="col-md-4">
          <div class="resident-card" onclick="navigateToPanel('panel-manage-admin')">
            <div class="resident-icon">
              <i class="bi bi-gear-fill text-white" style="font-size: 2rem;"></i>
            </div>
            <div class="resident-title">Manage Admin</div>
            <div class="resident-subtitle">View and manage admin accounts</div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Admin Logs Card -->
        <div class="col-md-4">
          <div class="resident-card" onclick="navigateToPanel('panel-admin-logs')">
            <div class="resident-icon">
              <i class="bi bi-journal-text text-white" style="font-size: 2rem;"></i>
            </div>
            <div class="resident-title">Admin Logs</div>
            <div class="resident-subtitle">View admin activity logs</div>
          </div>
        </div>

        <!-- Jobfinder Card -->
        <div class="col-md-4">
          <div class="resident-card" onclick="navigateToPanel('panel-jobfinder')">
            <div class="resident-icon">
              <i class="bi bi-briefcase-fill text-white" style="font-size: 2rem;"></i>
            </div>
            <div class="resident-title">Jobfinder</div>
            <div class="resident-subtitle">Manage residents with skills</div>
          </div>
        </div>

        <!-- Chatbot CMS Card -->
        <div class="col-md-4">
          <div class="resident-card" onclick="navigateToPanel('panel-chatbot-cms')">
            <div class="resident-icon">
              <i class="bi bi-robot text-white" style="font-size: 2rem;"></i>
            </div>
            <div class="resident-title">Chatbot CMS</div>
            <div class="resident-subtitle">Manage chatbot responses and FAQs</div>
          </div>
        </div>
      </div>
    </div>

    <script>
    // Function to navigate to a specific panel
    function navigateToPanel(panelId) {
      // Hide all panels
      document.querySelectorAll('.panel-section').forEach(function(panel) {
        panel.style.display = 'none';
      });
      
      // Show the target panel
      var targetPanel = document.getElementById(panelId);
      if (targetPanel) {
        targetPanel.style.display = 'block';
        
        // Update URL without page reload
        var panelName = panelId.replace('panel-', '');
        history.replaceState(null, '', '?panel=' + panelName);
      }
    }
    </script>

  </div>
<?php
if (isset($_GET['reset'])) {
    if ($_GET['reset'] === "success" && isset($_GET['userid'])) {
        echo "<script>alert('✅ Password reset successfully for User ID: {$_GET['userid']}');</script>";
    } elseif ($_GET['reset'] === "failed") {
        echo "<script>alert('⚠️ No user found with that ID.');</script>";
    } elseif ($_GET['reset'] === "invalid") {
        echo "<script>alert('⚠️ Please enter a valid User ID.');</script>";
    }
}
?>

<script>
// Handle Batch Register via Excel form submission
document.getElementById('batch-register-form').addEventListener('submit', function(e) {
  e.preventDefault();
  
  var batchBtn = document.getElementById('batch-register-btn');
  var originalText = batchBtn.innerHTML;
  batchBtn.disabled = true;
  batchBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Uploading...';
  
  var formData = new FormData(this);
  formData.append('upload_excel', '1');
  
  fetch('admin_dashboard.php', {
    method: 'POST',
    body: formData,
    credentials: 'same-origin'
  })
  .then(function(response) { return response.text(); })
  .then(function(html) {
    // Show success message
    var successMsg = document.createElement('div');
    successMsg.className = 'alert alert-success';
    successMsg.innerHTML = '<strong>✓ Success!</strong> Excel file uploaded and residents registered successfully.';
    successMsg.style.position = 'fixed';
    successMsg.style.top = '20px';
    successMsg.style.right = '20px';
    successMsg.style.zIndex = '9999';
    document.body.appendChild(successMsg);
    
    setTimeout(function() {
      successMsg.remove();
    }, 3000);
    
    // Reset form
    document.getElementById('batch-register-form').reset();
    batchBtn.disabled = false;
    batchBtn.innerHTML = originalText;
  })
  .catch(function(error) {
    console.error('Error uploading Excel:', error);
    alert('Error uploading file. Please try again.');
    batchBtn.disabled = false;
    batchBtn.innerHTML = originalText;
  });
});

// Handle Register Profile form submission
document.getElementById('registerProfileForm').addEventListener('submit', function(e) {
  e.preventDefault();
  
  var profileBtn = document.getElementById('register-profile-btn');
  var originalText = profileBtn.innerHTML;
  profileBtn.disabled = true;
  profileBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';
  
  var formData = new FormData(this);
  
  fetch('save_profile.php', {
    method: 'POST',
    body: formData,
    credentials: 'same-origin'
  })
  .then(function(response) { return response.text(); })
  .then(function(html) {
    // Show success message
    var successMsg = document.createElement('div');
    successMsg.className = 'alert alert-success';
    successMsg.innerHTML = '<strong>✓ Success!</strong> Resident profile saved successfully.';
    successMsg.style.position = 'fixed';
    successMsg.style.top = '20px';
    successMsg.style.right = '20px';
    successMsg.style.zIndex = '9999';
    document.body.appendChild(successMsg);
    
    setTimeout(function() {
      successMsg.remove();
    }, 3000);
    
    // Reset form
    document.getElementById('registerProfileForm').reset();
    profileBtn.disabled = false;
    profileBtn.innerHTML = originalText;
  })
  .catch(function(error) {
    console.error('Error saving profile:', error);
    alert('Error saving profile. Please try again.');
    profileBtn.disabled = false;
    profileBtn.innerHTML = originalText;
  });
});
</script>

</div>

<!-- Chatbot CMS Panel (hidden by default) -->
<div id="panel-chatbot-cms" class="panel-section" style="display:none;">
<?php
include 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: index.php?admin_login=required");
    exit();
}

$admin_username = $_SESSION['admin_username'] ?? 'Unknown';

// Create chatbot_responses table if it doesn't exist
$createTableSQL = "CREATE TABLE IF NOT EXISTS chatbot_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question VARCHAR(500) NOT NULL,
    answer TEXT NOT NULL,
    keywords TEXT,
    category VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by VARCHAR(100)
)";
$conn->query($createTableSQL);

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_response'])) {
        $question = trim($_POST['question']);
        $answer = trim($_POST['answer']);
        $keywords = trim($_POST['keywords']);
        $category = trim($_POST['category']);
        
        $stmt = $conn->prepare("INSERT INTO chatbot_responses (question, answer, keywords, category, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $question, $answer, $keywords, $category, $admin_username);
        if ($stmt->execute()) {
            $success_msg = "Chatbot response added successfully!";
            
            // Log action
            $action_log = "Added chatbot response: $question";
            $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
            $log->bind_param("ss", $admin_username, $action_log);
            $log->execute();
            $log->close();
        }
        $stmt->close();
    } elseif (isset($_POST['update_response'])) {
        $id = (int)$_POST['response_id'];
        $question = trim($_POST['question']);
        $answer = trim($_POST['answer']);
        $keywords = trim($_POST['keywords']);
        $category = trim($_POST['category']);
        
        $stmt = $conn->prepare("UPDATE chatbot_responses SET question=?, answer=?, keywords=?, category=? WHERE id=?");
        $stmt->bind_param("ssssi", $question, $answer, $keywords, $category, $id);
        if ($stmt->execute()) {
            $success_msg = "Chatbot response updated successfully!";
            
            // Log action
            $action_log = "Updated chatbot response ID: $id";
            $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
            $log->bind_param("ss", $admin_username, $action_log);
            $log->execute();
            $log->close();
        }
        $stmt->close();
    } elseif (isset($_POST['delete_response'])) {
        $id = (int)$_POST['response_id'];
        
        $stmt = $conn->prepare("DELETE FROM chatbot_responses WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success_msg = "Chatbot response deleted successfully!";
            
            // Log action
            $action_log = "Deleted chatbot response ID: $id";
            $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
            $log->bind_param("ss", $admin_username, $action_log);
            $log->execute();
            $log->close();
        }
        $stmt->close();
    } elseif (isset($_POST['toggle_status'])) {
        $id = (int)$_POST['response_id'];
        $status = (int)$_POST['status'];
        
        $stmt = $conn->prepare("UPDATE chatbot_responses SET is_active=? WHERE id=?");
        $stmt->bind_param("ii", $status, $id);
        if ($stmt->execute()) {
            $success_msg = "Status updated successfully!";
        }
        $stmt->close();
    }
}

// Fetch all chatbot responses
$responses = $conn->query("SELECT * FROM chatbot_responses ORDER BY category, created_at DESC");
?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="chatbot-header-modern mb-4 p-4 rounded-4 shadow-sm" style="background: linear-gradient(135deg, #14ad0f 0%, #43e97b 100%); box-shadow: 0 10px 40px rgba(20,173,15,0.3);">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="chatbot-icon-wrapper d-flex align-items-center justify-content-center rounded-circle" style="width: 60px; height: 60px; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px);">
                    <i class="bi bi-robot text-white" style="font-size: 1.8rem;"></i>
                </div>
                <div>
                    <h2 class="fw-bold text-white mb-1" style="font-size: 1.75rem;">Chatbot CMS</h2>
                    <p class="text-white mb-0" style="font-size: 0.9rem; opacity: 0.9;">
                        <i class="bi bi-info-circle me-1"></i>
                        Manage chatbot responses and FAQs
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages (will be populated via AJAX) -->
    <div id="chatbot-alert-container"></div>

    <!-- Add New Response Button -->
    <div class="mb-4">
        <button class="btn btn-lg" data-bs-toggle="modal" data-bs-target="#addResponseModal" style="background: linear-gradient(135deg, #14ad0f 0%, #43e97b 100%); color: white; border: none; border-radius: 12px; padding: 12px 28px; font-weight: 600; box-shadow: 0 4px 12px rgba(20,173,15,0.3); transition: all 0.3s;">
            <i class="bi bi-plus-circle me-2"></i>Add New Response
        </button>
    </div>

    <!-- Responses Table -->
    <div class="card shadow-sm" style="height: 700px; display: flex; flex-direction: column;">
        <div class="card-header bg-white py-3" style="flex-shrink: 0;">
            <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Chatbot Responses</h5>
        </div>
        <div class="card-body p-0" style="flex: 1; overflow: hidden; display: flex; flex-direction: column;">
            <div class="table-responsive" style="flex: 1; overflow-y: auto; max-height: 100%;">
                <table class="table table-hover mb-0">
                    <thead class="table-light" style="position: sticky; top: 0; z-index: 10; background-color: #f8f9fa;">
                        <tr>
                            <th style="background-color: #f8f9fa;">Question</th>
                            <th style="background-color: #f8f9fa;">Answer</th>
                            <th style="background-color: #f8f9fa;">Keywords</th>
                            <th style="background-color: #f8f9fa;">Category</th>
                            <th style="background-color: #f8f9fa;">Status</th>
                            <th style="background-color: #f8f9fa;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="chatbot-responses-tbody">
                        <?php if ($responses->num_rows > 0): ?>
                            <?php while($row = $responses->fetch_assoc()): ?>
                            <tr id="response-row-<?= $row['id'] ?>">
                                <td><?= htmlspecialchars(substr($row['question'], 0, 50)) ?>...</td>
                                <td><?= htmlspecialchars(substr($row['answer'], 0, 80)) ?>...</td>
                                <td><small class="text-muted"><?= htmlspecialchars($row['keywords']) ?></small></td>
                                <td><span class="badge" style="background: linear-gradient(135deg, #14ad0f 0%, #43e97b 100%); padding: 6px 12px; border-radius: 20px;"><?= htmlspecialchars($row['category']) ?></span></td>
                                <td>
                                    <button type="button" onclick="toggleStatus(<?= $row['id'] ?>, <?= $row['is_active'] ? 0 : 1 ?>)" class="btn btn-sm status-btn-<?= $row['id'] ?> <?= $row['is_active'] ? 'btn-success' : 'btn-secondary' ?>" style="border-radius: 8px; padding: 6px 16px; font-weight: 600; <?= $row['is_active'] ? 'background: linear-gradient(135deg, #14ad0f 0%, #43e97b 100%); border: none;' : '' ?>">
                                        <?= $row['is_active'] ? 'Active' : 'Inactive' ?>
                                    </button>
                                </td>
                                <td>
                                    <button class="btn btn-sm" onclick="viewResponse(<?= htmlspecialchars(json_encode($row)) ?>)" style="background: #0d6efd; color: white; border: none; border-radius: 8px; padding: 6px 12px; margin-right: 4px;">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button class="btn btn-sm" onclick="editResponse(<?= htmlspecialchars(json_encode($row)) ?>)" style="background: #ffc107; color: white; border: none; border-radius: 8px; padding: 6px 12px; margin-right: 4px;">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" onclick="deleteResponse(<?= $row['id'] ?>)" class="btn btn-sm" style="background: #dc3545; color: white; border: none; border-radius: 8px; padding: 6px 12px;">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr id="no-responses-row">
                                <td colspan="6" class="text-center py-4 text-muted">No chatbot responses yet. Add your first one!</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Response Modal -->
<div class="modal fade" id="addResponseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 16px; border: none; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #14ad0f 0%, #43e97b 100%); border: none; padding: 24px;">
                <h5 class="modal-title text-white fw-bold"><i class="bi bi-plus-circle me-2"></i>Add New Chatbot Response</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="addResponseForm">
                <div class="modal-body" style="padding: 24px;">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="color: #333; font-size: 13px;">Question</label>
                        <input type="text" name="question" id="add_question" class="form-control" required placeholder="e.g., What are the office hours?" style="border-radius: 10px; border: 1.5px solid #e0e0e0; padding: 12px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="color: #333; font-size: 13px;">Answer</label>
                        <textarea name="answer" id="add_answer" class="form-control" rows="4" required placeholder="Enter the chatbot's response" style="border-radius: 10px; border: 1.5px solid #e0e0e0; padding: 12px;"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="color: #333; font-size: 13px;">Keywords (comma-separated)</label>
                        <input type="text" name="keywords" id="add_keywords" class="form-control" placeholder="e.g., hours, time, schedule" style="border-radius: 10px; border: 1.5px solid #e0e0e0; padding: 12px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="color: #333; font-size: 13px;">Category</label>
                        <input type="text" name="category" id="add_category" class="form-control" placeholder="e.g., General, Services, Contact" style="border-radius: 10px; border: 1.5px solid #e0e0e0; padding: 12px;">
                    </div>
                </div>
                <div class="modal-footer" style="border: none; padding: 16px 24px; background: #f8f9fa;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 10px; padding: 10px 24px; font-weight: 600;">Cancel</button>
                    <button type="submit" class="btn" style="background: linear-gradient(135deg, #14ad0f 0%, #43e97b 100%); color: white; border: none; border-radius: 10px; padding: 10px 24px; font-weight: 600; box-shadow: 0 4px 12px rgba(20,173,15,0.3);">Add Response</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Response Modal -->
<div class="modal fade" id="editResponseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 16px; border: none; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #14ad0f 0%, #43e97b 100%); border: none; padding: 24px;">
                <h5 class="modal-title text-white fw-bold"><i class="bi bi-pencil me-2"></i>Edit Chatbot Response</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="editResponseForm">
                <input type="hidden" name="response_id" id="edit_response_id">
                <div class="modal-body" style="padding: 24px;">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="color: #333; font-size: 13px;">Question</label>
                        <input type="text" name="question" id="edit_question" class="form-control" required style="border-radius: 10px; border: 1.5px solid #e0e0e0; padding: 12px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="color: #333; font-size: 13px;">Answer</label>
                        <textarea name="answer" id="edit_answer" class="form-control" rows="4" required style="border-radius: 10px; border: 1.5px solid #e0e0e0; padding: 12px;"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="color: #333; font-size: 13px;">Keywords (comma-separated)</label>
                        <input type="text" name="keywords" id="edit_keywords" class="form-control" style="border-radius: 10px; border: 1.5px solid #e0e0e0; padding: 12px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="color: #333; font-size: 13px;">Category</label>
                        <input type="text" name="category" id="edit_category" class="form-control" style="border-radius: 10px; border: 1.5px solid #e0e0e0; padding: 12px;">
                    </div>
                </div>
                <div class="modal-footer" style="border: none; padding: 16px 24px; background: #f8f9fa;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 10px; padding: 10px 24px; font-weight: 600;">Cancel</button>
                    <button type="submit" class="btn" style="background: linear-gradient(135deg, #14ad0f 0%, #43e97b 100%); color: white; border: none; border-radius: 10px; padding: 10px 24px; font-weight: 600; box-shadow: 0 4px 12px rgba(20,173,15,0.3);">Update Response</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Response Modal -->
<div class="modal fade" id="viewResponseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 16px; border: none; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); border: none; padding: 24px;">
                <h5 class="modal-title text-white fw-bold"><i class="bi bi-eye me-2"></i>View Chatbot Response</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding: 24px;">
                <div class="mb-4">
                    <label class="form-label fw-bold text-primary" style="font-size: 14px;">
                        <i class="bi bi-question-circle me-2"></i>Question
                    </label>
                    <div class="p-3 bg-light rounded-3" id="view_question" style="border-left: 4px solid #0d6efd; font-size: 15px; color: #333;"></div>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold text-success" style="font-size: 14px;">
                        <i class="bi bi-chat-left-text me-2"></i>Answer
                    </label>
                    <div class="p-3 bg-light rounded-3" id="view_answer" style="border-left: 4px solid #14ad0f; font-size: 15px; color: #333; white-space: pre-wrap;"></div>
                </div>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-warning" style="font-size: 14px;">
                            <i class="bi bi-tags me-2"></i>Keywords
                        </label>
                        <div class="p-3 bg-light rounded-3" id="view_keywords" style="border-left: 4px solid #ffc107; font-size: 14px; color: #666;"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-info" style="font-size: 14px;">
                            <i class="bi bi-folder me-2"></i>Category
                        </label>
                        <div class="p-3 bg-light rounded-3" id="view_category" style="border-left: 4px solid #0dcaf0; font-size: 14px; color: #666;"></div>
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-secondary" style="font-size: 14px;">
                            <i class="bi bi-toggle-on me-2"></i>Status
                        </label>
                        <div class="p-3 bg-light rounded-3" id="view_status" style="border-left: 4px solid #6c757d; font-size: 14px;"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-secondary" style="font-size: 14px;">
                            <i class="bi bi-person me-2"></i>Created By
                        </label>
                        <div class="p-3 bg-light rounded-3" id="view_created_by" style="border-left: 4px solid #6c757d; font-size: 14px; color: #666;"></div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted" style="font-size: 13px;">
                            <i class="bi bi-clock me-2"></i>Created At
                        </label>
                        <div class="p-2 bg-light rounded-3" id="view_created_at" style="font-size: 13px; color: #888;"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted" style="font-size: 13px;">
                            <i class="bi bi-clock-history me-2"></i>Updated At
                        </label>
                        <div class="p-2 bg-light rounded-3" id="view_updated_at" style="font-size: 13px; color: #888;"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border: none; padding: 16px 24px; background: #f8f9fa;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 10px; padding: 10px 24px; font-weight: 600;">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// Show alert message
function showAlert(message, type = 'success') {
    const alertContainer = document.getElementById('chatbot-alert-container');
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert" style="border-radius: 12px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
            <i class="bi bi-${type === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill'} me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    alertContainer.innerHTML = alertHtml;
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        const alert = alertContainer.querySelector('.alert');
        if (alert) {
            alert.classList.remove('show');
            setTimeout(() => alertContainer.innerHTML = '', 150);
        }
    }, 5000);
}

// Add Response Form Submit
document.getElementById('addResponseForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('add_response', '1');
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(html => {
        // Close modal
        bootstrap.Modal.getInstance(document.getElementById('addResponseModal')).hide();
        
        // Show success message
        showAlert('Chatbot response added successfully!', 'success');
        
        // Reset form
        document.getElementById('addResponseForm').reset();
        
        // Reload the responses table
        loadResponses();
    })
    .catch(error => {
        showAlert('Error adding response. Please try again.', 'danger');
        console.error('Error:', error);
    });
});

// Edit Response Form Submit
document.getElementById('editResponseForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('update_response', '1');
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(html => {
        // Close modal
        bootstrap.Modal.getInstance(document.getElementById('editResponseModal')).hide();
        
        // Show success message
        showAlert('Chatbot response updated successfully!', 'success');
        
        // Reload the responses table
        loadResponses();
    })
    .catch(error => {
        showAlert('Error updating response. Please try again.', 'danger');
        console.error('Error:', error);
    });
});

// View Response Function
function viewResponse(data) {
    document.getElementById('view_question').textContent = data.question;
    document.getElementById('view_answer').textContent = data.answer;
    document.getElementById('view_keywords').textContent = data.keywords || 'N/A';
    document.getElementById('view_category').textContent = data.category || 'N/A';
    
    // Format status with badge
    const statusHtml = data.is_active == 1 
        ? '<span class="badge bg-success">Active</span>' 
        : '<span class="badge bg-secondary">Inactive</span>';
    document.getElementById('view_status').innerHTML = statusHtml;
    
    document.getElementById('view_created_by').textContent = data.created_by || 'N/A';
    document.getElementById('view_created_at').textContent = data.created_at || 'N/A';
    document.getElementById('view_updated_at').textContent = data.updated_at || 'N/A';
    
    var viewModal = new bootstrap.Modal(document.getElementById('viewResponseModal'));
    viewModal.show();
}

// Edit Response Function
function editResponse(data) {
    document.getElementById('edit_response_id').value = data.id;
    document.getElementById('edit_question').value = data.question;
    document.getElementById('edit_answer').value = data.answer;
    document.getElementById('edit_keywords').value = data.keywords || '';
    document.getElementById('edit_category').value = data.category || '';
    
    var editModal = new bootstrap.Modal(document.getElementById('editResponseModal'));
    editModal.show();
}

// Delete Response Function
function deleteResponse(id) {
    if (!confirm('Are you sure you want to delete this response?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('delete_response', '1');
    formData.append('response_id', id);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(html => {
        showAlert('Chatbot response deleted successfully!', 'success');
        
        // Remove the row from table
        const row = document.getElementById('response-row-' + id);
        if (row) {
            row.remove();
        }
        
        // Check if table is empty
        const tbody = document.getElementById('chatbot-responses-tbody');
        if (tbody.children.length === 0) {
            tbody.innerHTML = '<tr id="no-responses-row"><td colspan="7" class="text-center py-4 text-muted">No chatbot responses yet. Add your first one!</td></tr>';
        }
    })
    .catch(error => {
        showAlert('Error deleting response. Please try again.', 'danger');
        console.error('Error:', error);
    });
}

// Toggle Status Function
function toggleStatus(id, newStatus) {
    const formData = new FormData();
    formData.append('toggle_status', '1');
    formData.append('response_id', id);
    formData.append('status', newStatus);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(html => {
        showAlert('Status updated successfully!', 'success');
        
        // Update button appearance
        const btn = document.querySelector('.status-btn-' + id);
        if (btn) {
            if (newStatus == 1) {
                btn.classList.remove('btn-secondary');
                btn.classList.add('btn-success');
                btn.style.background = 'linear-gradient(135deg, #14ad0f 0%, #43e97b 100%)';
                btn.style.border = 'none';
                btn.textContent = 'Active';
                btn.onclick = function() { toggleStatus(id, 0); };
            } else {
                btn.classList.remove('btn-success');
                btn.classList.add('btn-secondary');
                btn.style.background = '';
                btn.style.border = '';
                btn.textContent = 'Inactive';
                btn.onclick = function() { toggleStatus(id, 1); };
            }
        }
    })
    .catch(error => {
        showAlert('Error updating status. Please try again.', 'danger');
        console.error('Error:', error);
    });
}

// Load Responses Function (for refreshing table)
function loadResponses() {
    // Reload the current page content without full page refresh
    fetch(window.location.href)
    .then(response => response.text())
    .then(html => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newTbody = doc.getElementById('chatbot-responses-tbody');
        
        if (newTbody) {
            document.getElementById('chatbot-responses-tbody').innerHTML = newTbody.innerHTML;
        }
    })
    .catch(error => {
        console.error('Error reloading responses:', error);
    });
}
</script>
</div>
<!-- End Chatbot CMS Panel -->

  <!-- Officials Panel (hidden by default) -->
  <div id="panel-officials" class="panel-section" style="display:none;">
    <?php
    // --- Begin manage_officials.php logic (adapted for dashboard panel) ---
    include 'config.php';
    $admin_username = $_SESSION['admin_username'] ?? 'Unknown';
    $oaction = $_GET['oaction'] ?? 'list';
    $oid     = isset($_GET['oid']) ? (int)$_GET['oid'] : 0;

    // --- Delete Official ---
    if ($oaction === 'delete' && $oid > 0) {
      $stmt = $conn->prepare("SELECT photo, name FROM manage_brgy_officials WHERE id=? LIMIT 1");
      $stmt->bind_param("i", $oid);
      $stmt->execute();
      $row = $stmt->get_result()->fetch_assoc();
      $stmt->close();
      if (!empty($row['photo']) && is_file($row['photo'])) {
        unlink($row['photo']);
      }
      $stmt = $conn->prepare("DELETE FROM manage_brgy_officials WHERE id=? LIMIT 1");
      $stmt->bind_param("i", $oid);
      if ($stmt->execute()) {
        $action_log = "Deleted official: {$row['name']} (ID: $oid)";
        $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
        $log->bind_param("ss", $admin_username, $action_log);
        $log->execute();
        $log->close();
      }
      $stmt->close();
      header("Location: admin_dashboard.php?panel=officials");
      exit;
    }
    // --- Delete Archived Official ---
    if ($oaction === 'delete_archived' && $oid > 0) {
      $stmt = $conn->prepare("SELECT photo, name FROM archived_brgy_officials WHERE id=? LIMIT 1");
      $stmt->bind_param("i", $oid);
      $stmt->execute();
      $row = $stmt->get_result()->fetch_assoc();
      $stmt->close();
      if (!empty($row['photo']) && is_file($row['photo'])) {
        unlink($row['photo']);
      }
      $stmt = $conn->prepare("DELETE FROM archived_brgy_officials WHERE id=? LIMIT 1");
      $stmt->bind_param("i", $oid);
      if ($stmt->execute()) {
        $action_log = "Deleted archived official: {$row['name']} (ID: $oid)";
        $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
        $log->bind_param("ss", $admin_username, $action_log);
        $log->execute();
        $log->close();
      }
      $stmt->close();
      header("Location: admin_dashboard.php?panel=officials&oaction=archived");
      exit;
    }
    // --- Save Official (Add/Edit) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['osave'])) {
      $oid          = isset($_POST['oid']) ? (int)$_POST['oid'] : 0;
      $name        = trim($_POST['oname']);
      $position    = trim($_POST['oposition']);
      $description = trim($_POST['odescription']);
      $start_date  = $_POST['ostart_date'];
      $end_date    = $_POST['oend_date'];
      $photoPath   = $_POST['ophoto'] ?? '';
      // Handle photo upload
      if (!empty($_FILES['ophoto_file']['name']) && $_FILES['ophoto_file']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg','image/png','image/gif'];
        if (in_array($_FILES['ophoto_file']['type'], $allowed)) {
          $dir = 'uploads/';
          if (!is_dir($dir)) mkdir($dir,0777,true);
          $fileName = time().'_'.basename($_FILES['ophoto_file']['name']);
          $dest = $dir.$fileName;
          if (move_uploaded_file($_FILES['ophoto_file']['tmp_name'],$dest)) {
            if ($oid > 0 && !empty($photoPath) && is_file($photoPath)) {
              unlink($photoPath);
            }
            $photoPath = $dest;
          }
        }
      }
      if ($oid > 0) {
        $stmt = $conn->prepare("UPDATE manage_brgy_officials SET name=?, position=?, description=?, photo=?, start_date=?, end_date=? WHERE id=?");
        $stmt->bind_param("ssssssi",$name,$position,$description,$photoPath,$start_date,$end_date,$oid);
        $action_log = "Edited official: $name (ID: $oid)";
      } else {
        $stmt = $conn->prepare("INSERT INTO manage_brgy_officials (name, position, description, photo, start_date, end_date) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("ssssss",$name,$position,$description,$photoPath,$start_date,$end_date);
        $action_log = "Added official: $name";
      }
      if ($stmt->execute()) {
        $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
        $log->bind_param("ss", $admin_username, $action_log);
        $log->execute();
        $log->close();
      }
      $stmt->close();
      header("Location: admin_dashboard.php?panel=officials");
      exit;
    }
    // --- Fetch Officials ---
    $osearch = strtolower($_GET['osearch'] ?? '');
    $osearchParam = "%$osearch%";
    $ostart_date = $_GET['ostart_date'] ?? '';
    $oend_date = $_GET['oend_date'] ?? '';
    $query = "SELECT * FROM manage_brgy_officials WHERE 1";
    $params = [];
    $types = '';
    if ($osearch !== '') {
      $query .= " AND (LOWER(name) LIKE ? OR LOWER(position) LIKE ?)";
      $params[] = $osearchParam;
      $params[] = $osearchParam;
      $types .= 'ss';
    }
    if ($ostart_date !== '') {
      $query .= " AND start_date >= ?";
      $params[] = $ostart_date;
      $types .= 's';
    }
    if ($oend_date !== '') {
      $query .= " AND end_date <= ?";
      $params[] = $oend_date;
      $types .= 's';
    }
    $stmt = $conn->prepare($query);
    if ($params) {
      $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $oofficials = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    // --- Auto-archive expired officials ---
    $today = date('Y-m-d');
    $stmt = $conn->prepare("SELECT * FROM manage_brgy_officials WHERE end_date < ?");
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $expired = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    foreach ($expired as $off) {
      $stmt = $conn->prepare("INSERT INTO archived_brgy_officials (name, position, description, photo, start_date, end_date, archived_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
      $stmt->bind_param("ssssss", $off['name'], $off['position'], $off['description'], $off['photo'], $off['start_date'], $off['end_date']);
      $stmt->execute();
      $stmt->close();
      $stmt = $conn->prepare("DELETE FROM manage_brgy_officials WHERE id=? LIMIT 1");
      $stmt->bind_param("i", $off['id']);
      $stmt->execute();
      $stmt->close();
      $action_log = "Archived official: {$off['name']} (ID: {$off['id']})";
      $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
      $log->bind_param("ss", $admin_username, $action_log);
      $log->execute();
      $log->close();
    }
    ?>
    <div class="container-fluid py-4" id="officials-content-area">
    <?php if ($oaction === 'list'): ?>
      <!-- Modern Header with Gradient -->
      <div class="officials-header-modern mb-4 p-4 rounded-4 shadow-sm" style="background: linear-gradient(160deg, #14ad0f 0%, #43e97b 100%);">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
          <div class="d-flex align-items-center gap-3">
            <div class="officials-icon-wrapper d-flex align-items-center justify-content-center rounded-circle" style="width: 60px; height: 60px; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); box-shadow: 0 4px 12px rgba(20,173,15,0.2);">
              <i class="bi bi-people-fill text-white" style="font-size: 1.8rem; filter: drop-shadow(0 2px 4px rgba(20,173,15,0.3));"></i>
            </div>
            <div>
              <h2 class="fw-bold text-white mb-1" id="officials-panel-title" style="font-size: 1.75rem; text-shadow: 0 2px 8px rgba(20,173,15,0.3); letter-spacing: 0.5px;">
                Barangay Officials
              </h2>
              <p class="text-white mb-0" style="font-size: 0.9rem; opacity: 0.9;">
                <i class="bi bi-info-circle me-1"></i>
                Manage barangay officials and their terms
              </p>
            </div>
          </div>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-light fw-semibold officials-nav-btn shadow-sm" data-oaction="add" style="border-radius: 12px; padding: 10px 20px;">
              <i class="bi bi-plus-circle me-1"></i> Add Official
            </button>
            <button type="button" class="btn btn-outline-light officials-nav-btn shadow-sm" data-oaction="archived" style="border-radius: 12px; padding: 10px 20px;">
              <i class="bi bi-archive me-1"></i> Archives
            </button>
          </div>
        </div>
      </div>

      <style>
        .officials-nav-btn:hover {
          transform: translateY(-2px);
          box-shadow: 0 4px 12px rgba(20,173,15,0.15) !important;
        }
        #officials-search-btn:hover {
          background: linear-gradient(90deg,#43e97b 0%,#14ad0f 100%) !important;
          box-shadow: 0 6px 18px rgba(20,173,15,0.18) !important;
          transform: scale(1.05);
        }
      </style>

      <div class="card p-4">
      <form method="GET" class="row g-2 mb-4" id="officials-search-form">
        <input type="hidden" name="panel" value="officials">
        <input type="hidden" name="oaction" value="list">
        <div class="col-md-3">
          <input type="text" name="osearch" class="form-control" placeholder="Search name or position" value="<?= htmlspecialchars($_GET['osearch'] ?? '') ?>" id="officials-search-input">
        </div>
        <div class="col-md-2">
          <input type="date" name="ostart_date" class="form-control" placeholder="Start Date" value="<?= htmlspecialchars($_GET['ostart_date'] ?? '') ?>" id="officials-start-date">
        </div>
        <div class="col-md-2">
          <input type="date" name="oend_date" class="form-control" placeholder="End Date" value="<?= htmlspecialchars($_GET['oend_date'] ?? '') ?>" id="officials-end-date">
        </div>
        <div class="col-auto">
          <button type="submit" class="btn" id="officials-search-btn" style="background: linear-gradient(90deg,#14ad0f 0%,#43e97b 100%); color: #fff; border: none; padding: 10px 20px; border-radius: 12px; font-weight: 600; box-shadow: 0 2px 12px rgba(20,173,15,0.13); transition: all 0.22s;"><i class="bi bi-funnel"></i> Search</button>
          <button type="button" class="btn btn-outline-secondary" id="officials-reset-btn" style="border: 2px solid #6c757d; border-radius: 10px; font-weight: 600; transition: all 0.22s;">Reset</button>
        </div>
      </form>
      </div>
      <div class="card card-custom p-4 mt-3" id="officials-table-container">
        <?php if (empty($oofficials)): ?>
          <div class="text-center py-5">
            <div class="mb-3">
              <i class="bi bi-people" style="font-size: 4rem; color: #dee2e6;"></i>
            </div>
            <h4 class="text-muted">No Officials Found</h4>
            <p class="text-muted">Add your first barangay official to get started.</p>
          </div>
        <?php else: ?>
          <div class="row g-4">
            <?php foreach ($oofficials as $off): ?>
              <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm border-0" style="transition: all 0.3s ease; overflow: hidden; display: flex; flex-direction: column;">
                  <div class="position-relative" style="background: linear-gradient(135deg, #14ad0f 0%, #43e97b 100%); height: 8px;"></div>
                  <div class="card-body p-4" style="display: flex; flex-direction: column; flex: 1;">
                    <div class="d-flex align-items-start gap-3 mb-3">
                      <div class="flex-shrink-0">
                        <?php if ($off['photo'] && file_exists($off['photo'])): ?>
                          <img src="<?= htmlspecialchars($off['photo']) ?>" class="rounded-circle shadow" style="width: 80px; height: 80px; object-fit: cover; border: 3px solid #f8f9fa;">
                        <?php else: ?>
                          <div class="rounded-circle bg-light d-flex align-items-center justify-content-center shadow-sm" style="width: 80px; height: 80px; border: 3px solid #f8f9fa;">
                            <i class="bi bi-person-fill text-muted" style="font-size: 2rem;"></i>
                          </div>
                        <?php endif; ?>
                      </div>
                      <div class="flex-grow-1">
                        <h5 class="mb-1 fw-bold" style="color: #1a1a1a;"><?= htmlspecialchars($off['name']) ?></h5>
                        <p class="mb-2 text-muted small fw-semibold">
                          <i class="bi bi-briefcase-fill me-1" style="color: #14ad0f;"></i>
                          <?= htmlspecialchars($off['position']) ?>
                        </p>
                      </div>
                    </div>
                    <div class="mb-3">
                      <p class="text-muted small mb-0" style="line-height: 1.6;"><?= nl2br(htmlspecialchars($off['description'])) ?></p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                      <span class="badge" style="background: linear-gradient(90deg, #14ad0f 0%, #43e97b 100%); padding: 6px 12px; font-weight: 500;">
                        <i class="bi bi-calendar-check me-1"></i><?= htmlspecialchars($off['start_date']) ?>
                      </span>
                      <span class="badge bg-secondary" style="padding: 6px 12px; font-weight: 500;">
                        <i class="bi bi-calendar-x me-1"></i><?= htmlspecialchars($off['end_date']) ?>
                      </span>
                    </div>
                    <div class="d-flex gap-2 pt-2 border-top mt-auto">
                      <button type="button" class="btn btn-sm btn-outline-primary flex-fill officials-edit-btn" data-oid="<?= $off['id'] ?>" style="border-radius: 8px; font-weight: 600;">
                        <i class="bi bi-pencil-square me-1"></i>Edit
                      </button>
                      <button type="button" class="btn btn-sm btn-outline-danger flex-fill officials-delete-btn" data-oid="<?= $off['id'] ?>" data-name="<?= htmlspecialchars($off['name']) ?>" style="border-radius: 8px; font-weight: 600;">
                        <i class="bi bi-trash me-1"></i>Delete
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        
        <style>
          #officials-table-container .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(20,173,15,0.15) !important;
          }
          #officials-table-container .btn-outline-primary:hover {
            background: linear-gradient(90deg, #0d6efd 0%, #0b5ed7 100%);
            border-color: transparent;
            color: white;
          }
          #officials-table-container .btn-outline-danger:hover {
            background: linear-gradient(90deg, #dc3545 0%, #bb2d3b 100%);
            border-color: transparent;
            color: white;
          }
        </style>
      </div>
    <?php elseif ($oaction === 'add' || $oaction === 'edit'):
    $official = ['id'=>'','name'=>'','position'=>'','description'=>'','photo'=>'','start_date'=>'','end_date'=>''];
    if ($oaction==='edit' && $oid>0){
      $stmt=$conn->prepare("SELECT * FROM manage_brgy_officials WHERE id=?");
      $stmt->bind_param("i",$oid);
      $stmt->execute();
      $official = $stmt->get_result()->fetch_assoc();
      $stmt->close();
    }
    ?>
      <div class="card card-custom shadow-lg border-0" style="border-radius: 16px; overflow: hidden;">
        <div class="card-header" style="background: linear-gradient(135deg, #14ad0f 0%, #43e97b 100%); padding: 24px; border: none;">
          <h2 class="mb-0 text-white fw-bold" style="font-size: 1.5rem;">
            <i class="bi bi-<?= $oaction==='edit' ? 'pencil-square' : 'plus-circle' ?> me-2"></i>
            <?= $oaction==='edit' ? 'Edit' : 'Add New' ?> Official
          </h2>
          <p class="mb-0 text-white" style="opacity: 0.9; font-size: 0.9rem; margin-top: 4px;">
            <?= $oaction==='edit' ? 'Update official information' : 'Add a new barangay official' ?>
          </p>
        </div>
        <div class="card-body p-4">
          <form method="POST" enctype="multipart/form-data" class="row g-4" id="officials-save-form">
            <input type="hidden" name="oid" value="<?= $official['id'] ?>">
            <input type="hidden" name="ophoto" value="<?= htmlspecialchars($official['photo']) ?>">
            
            <div class="col-12">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-semibold" style="color: #1a1a1a;">
                    <i class="bi bi-person-fill text-primary me-1"></i>Full Name
                  </label>
                  <input type="text" name="oname" class="form-control form-control-lg" value="<?= htmlspecialchars($official['name']) ?>" placeholder="Enter full name" required style="border-radius: 10px; border: 2px solid #e9ecef;">
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold" style="color: #1a1a1a;">
                    <i class="bi bi-briefcase-fill text-success me-1"></i>Position
                  </label>
                  <input type="text" name="oposition" class="form-control form-control-lg" value="<?= htmlspecialchars($official['position']) ?>" placeholder="Enter position" required style="border-radius: 10px; border: 2px solid #e9ecef;">
                </div>
              </div>
            </div>
            
            <div class="col-12">
              <label class="form-label fw-semibold" style="color: #1a1a1a;">
                <i class="bi bi-file-text-fill text-info me-1"></i>Description
              </label>
              <textarea name="odescription" class="form-control" rows="4" placeholder="Enter description or responsibilities" required style="border-radius: 10px; border: 2px solid #e9ecef;"><?= htmlspecialchars($official['description']) ?></textarea>
            </div>
            
            <div class="col-12">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-semibold" style="color: #1a1a1a;">
                    <i class="bi bi-calendar-check-fill text-success me-1"></i>Start Date
                  </label>
                  <input type="date" name="ostart_date" class="form-control form-control-lg" value="<?= htmlspecialchars($official['start_date']) ?>" required style="border-radius: 10px; border: 2px solid #e9ecef;">
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold" style="color: #1a1a1a;">
                    <i class="bi bi-calendar-x-fill text-danger me-1"></i>End Date
                  </label>
                  <input type="date" name="oend_date" class="form-control form-control-lg" value="<?= htmlspecialchars($official['end_date']) ?>" required style="border-radius: 10px; border: 2px solid #e9ecef;">
                </div>
              </div>
            </div>
            
            <div class="col-12">
              <label class="form-label fw-semibold" style="color: #1a1a1a;">
                <i class="bi bi-image-fill text-warning me-1"></i>Photo
              </label>
              <input type="file" name="ophoto_file" class="form-control form-control-lg" accept="image/*" style="border-radius: 10px; border: 2px solid #e9ecef;">
              <?php if ($official['photo'] && file_exists($official['photo'])): ?>
                <div class="mt-3 p-3 bg-light rounded" style="border-radius: 10px;">
                  <p class="text-muted small mb-2 fw-semibold">Current Photo:</p>
                  <img src="<?= htmlspecialchars($official['photo']) ?>" class="rounded shadow" style="max-width: 150px; height: auto; border: 3px solid white;">
                </div>
              <?php endif; ?>
            </div>
            
            <div class="col-12">
              <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                <button type="button" class="btn btn-lg btn-outline-secondary officials-back-btn" style="border-radius: 10px; padding: 12px 32px; font-weight: 600; border: 2px solid #6c757d;">
                  <i class="bi bi-arrow-left me-2"></i>Back
                </button>
                <button type="submit" name="osave" class="btn btn-lg" id="officials-save-btn" style="background: linear-gradient(90deg, #14ad0f 0%, #43e97b 100%); color: white; border: none; border-radius: 10px; padding: 12px 40px; font-weight: 600; box-shadow: 0 4px 12px rgba(20,173,15,0.2);">
                  <i class="bi bi-check-circle-fill me-2"></i>Save Official
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
      
      <style>
        #officials-save-form .form-control:focus {
          border-color: #14ad0f;
          box-shadow: 0 0 0 0.2rem rgba(20,173,15,0.15);
        }
        #officials-save-btn:hover {
          background: linear-gradient(90deg, #43e97b 0%, #14ad0f 100%) !important;
          transform: translateY(-2px);
          box-shadow: 0 6px 18px rgba(20,173,15,0.3) !important;
        }
        .officials-back-btn:hover {
          background-color: #6c757d;
          color: white;
          transform: translateY(-2px);
        }
      </style>
    <?php elseif ($oaction === 'archived'):
      $osearch = strtolower($_GET['osearch'] ?? '');
      $osearchParam = "%$osearch%";
      $ostart_date = $_GET['ostart_date'] ?? '';
      $oend_date = $_GET['oend_date'] ?? '';
      $query = "SELECT * FROM archived_brgy_officials WHERE 1";
      $params = [];
      $types = '';
      if ($osearch !== '') {
        $query .= " AND (LOWER(name) LIKE ? OR LOWER(position) LIKE ?)";
        $params[] = $osearchParam;
        $params[] = $osearchParam;
        $types .= 'ss';
      }
      if ($ostart_date !== '') {
        $query .= " AND start_date >= ?";
        $params[] = $ostart_date;
        $types .= 's';
      }
      if ($oend_date !== '') {
        $query .= " AND end_date <= ?";
        $params[] = $oend_date;
        $types .= 's';
      }
      $query .= " ORDER BY archived_at DESC";
      $stmt = $conn->prepare($query);
      if ($params) {
        $stmt->bind_param($types, ...$params);
      }
      $stmt->execute();
      $archived = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
      $stmt->close();
    ?>
      <!-- Modern Header with Gradient for Archived -->
      <div class="officials-header-modern mb-4 p-4 rounded-4 shadow-sm" style="background: linear-gradient(160deg, #14ad0f 0%, #43e97b 100%);">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
          <div class="d-flex align-items-center gap-3">
            <div class="officials-icon-wrapper d-flex align-items-center justify-content-center rounded-circle" style="width: 60px; height: 60px; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); box-shadow: 0 4px 12px rgba(20,173,15,0.2);">
              <i class="bi bi-archive-fill text-white" style="font-size: 1.8rem; filter: drop-shadow(0 2px 4px rgba(20,173,15,0.3));"></i>
            </div>
            <div>
              <h2 class="fw-bold text-white mb-1" style="font-size: 1.75rem; text-shadow: 0 2px 8px rgba(20,173,15,0.3); letter-spacing: 0.5px;">
                Archived Barangay Officials
              </h2>
              <p class="text-white mb-0" style="font-size: 0.9rem; opacity: 0.9;">
                <i class="bi bi-info-circle me-1"></i>
                View and manage archived officials
              </p>
            </div>
          </div>
          <div>
            <button type="button" class="btn btn-light fw-semibold officials-back-btn shadow-sm" style="border-radius: 12px; padding: 10px 20px;">
              <i class="bi bi-arrow-left me-1"></i> Back to Active
            </button>
          </div>
        </div>
      </div>

      <div class="card p-4">
      <form method="GET" class="row g-2 mb-4">
        <input type="hidden" name="panel" value="officials">
        <input type="hidden" name="oaction" value="archived">
        <div class="col-md-3">
          <input type="text" name="osearch" class="form-control" placeholder="Search name or position" value="<?= htmlspecialchars($_GET['osearch'] ?? '') ?>">
        </div>
        <div class="col-md-2">
          <input type="date" name="ostart_date" class="form-control" placeholder="Start Date" value="<?= htmlspecialchars($_GET['ostart_date'] ?? '') ?>">
        </div>
        <div class="col-md-2">
          <input type="date" name="oend_date" class="form-control" placeholder="End Date" value="<?= htmlspecialchars($_GET['oend_date'] ?? '') ?>">
        </div>
        <div class="col-auto">
          <button type="submit" class="btn" style="background: linear-gradient(90deg,#14ad0f 0%,#43e97b 100%); color: #fff; border: none; padding: 10px 20px; border-radius: 12px; font-weight: 600; box-shadow: 0 2px 12px rgba(20,173,15,0.13); transition: all 0.22s;"><i class="bi bi-funnel"></i> Search</button>
          <a href="admin_dashboard.php?panel=officials&oaction=archived" class="btn btn-outline-secondary" style="border: 2px solid #6c757d; border-radius: 10px; font-weight: 600; transition: all 0.22s;">Reset</a>
        </div>
      </form>
      </div>
      <div class="card card-custom p-4 mt-3">
        <?php if (empty($archived)): ?>
          <div class="text-center py-5">
            <div class="mb-3">
              <i class="bi bi-archive" style="font-size: 4rem; color: #dee2e6;"></i>
            </div>
            <h4 class="text-muted">No Archived Officials</h4>
            <p class="text-muted">Archived officials will appear here.</p>
          </div>
        <?php else: ?>
          <div class="row g-4">
            <?php foreach ($archived as $off): ?>
              <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm border-0" style="transition: all 0.3s ease; overflow: hidden; opacity: 0.9;">
                  <div class="position-relative" style="background: linear-gradient(135deg, #6c757d 0%, #adb5bd 100%); height: 8px;"></div>
                  <div class="card-body p-4">
                    <div class="d-flex align-items-start gap-3 mb-3">
                      <div class="flex-shrink-0">
                        <?php if ($off['photo'] && file_exists($off['photo'])): ?>
                          <img src="<?= htmlspecialchars($off['photo']) ?>" class="rounded-circle shadow" style="width: 80px; height: 80px; object-fit: cover; border: 3px solid #f8f9fa; filter: grayscale(30%);">
                        <?php else: ?>
                          <div class="rounded-circle bg-light d-flex align-items-center justify-content-center shadow-sm" style="width: 80px; height: 80px; border: 3px solid #f8f9fa;">
                            <i class="bi bi-person-fill text-muted" style="font-size: 2rem;"></i>
                          </div>
                        <?php endif; ?>
                      </div>
                      <div class="flex-grow-1">
                        <h5 class="mb-1 fw-bold" style="color: #495057;"><?= htmlspecialchars($off['name']) ?></h5>
                        <p class="mb-2 text-muted small fw-semibold">
                          <i class="bi bi-briefcase-fill me-1" style="color: #6c757d;"></i>
                          <?= htmlspecialchars($off['position']) ?>
                        </p>
                      </div>
                    </div>
                    <div class="mb-3">
                      <p class="text-muted small mb-0" style="line-height: 1.6;"><?= nl2br(htmlspecialchars($off['description'])) ?></p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                      <span class="badge bg-secondary" style="padding: 6px 12px; font-weight: 500;">
                        <i class="bi bi-calendar-check me-1"></i><?= htmlspecialchars($off['start_date']) ?>
                      </span>
                      <span class="badge bg-dark" style="padding: 6px 12px; font-weight: 500;">
                        <i class="bi bi-calendar-x me-1"></i><?= htmlspecialchars($off['end_date']) ?>
                      </span>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                      <small class="text-muted">
                        <i class="bi bi-archive me-1"></i>Archived: <?= htmlspecialchars($off['archived_at']) ?>
                      </small>
                    </div>
                    <div class="d-grid">
                      <button type="button" class="btn btn-outline-danger officials-delete-archived-btn" data-oid="<?= $off['id'] ?>" data-name="<?= htmlspecialchars($off['name']) ?>" style="border-radius: 8px; font-weight: 600;">
                        <i class="bi bi-trash me-1"></i>Permanently Delete
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
    </div>
    <!-- End manage_officials.php logic -->
    <script>
    // Function to load officials content
    function loadOfficialsContent(oaction, oid) {
      var urlParams = new URLSearchParams();
      urlParams.set('panel', 'officials');
      if (oaction) urlParams.set('oaction', oaction);
      if (oid) urlParams.set('oid', oid);
      
      fetch('admin_dashboard.php?' + urlParams.toString(), { 
        method: 'GET', 
        credentials: 'same-origin' 
      })
      .then(function(response) { return response.text(); })
      .then(function(html) {
        var parser = new DOMParser();
        var doc = parser.parseFromString(html, 'text/html');
        
        // Update content area
        var newContent = doc.querySelector('#officials-content-area');
        var currentContent = document.querySelector('#officials-content-area');
        if (newContent && currentContent) {
          currentContent.innerHTML = newContent.innerHTML;
          attachOfficialsEventListeners();
        }
      })
      .catch(function(error) {
        console.error('Error loading officials:', error);
        alert('Error loading content. Please refresh the page.');
      });
    }
    
    // Function to attach event listeners
    function attachOfficialsEventListeners() {
      // Navigation buttons (Add, Archived)
      document.querySelectorAll('.officials-nav-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
          var oaction = btn.getAttribute('data-oaction');
          loadOfficialsContent(oaction);
        });
      });
      
      // Edit buttons
      document.querySelectorAll('.officials-edit-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
          var oid = btn.getAttribute('data-oid');
          loadOfficialsContent('edit', oid);
        });
      });
      
      // Delete buttons
      var currentDeleteBtn = null;
      var currentDeleteOid = null;
      document.querySelectorAll('.officials-delete-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
          var oid = btn.getAttribute('data-oid');
          var name = btn.getAttribute('data-name');
          
          // Store references
          currentDeleteBtn = btn;
          currentDeleteOid = oid;
          
          // Set name in modal
          document.getElementById('deleteOfficialName').textContent = name;
          
          // Show modal
          var modal = new bootstrap.Modal(document.getElementById('deleteOfficialModal'));
          modal.show();
        });
      });
      
      // Handle delete confirmation
      var confirmDeleteBtn = document.getElementById('confirmDeleteOfficialBtn');
      if (confirmDeleteBtn) {
        confirmDeleteBtn.replaceWith(confirmDeleteBtn.cloneNode(true));
        document.getElementById('confirmDeleteOfficialBtn').addEventListener('click', function() {
          if (!currentDeleteBtn || !currentDeleteOid) return;
          
          // Close modal
          var modal = bootstrap.Modal.getInstance(document.getElementById('deleteOfficialModal'));
          modal.hide();
          
          currentDeleteBtn.disabled = true;
          currentDeleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
          
          fetch('admin_dashboard.php?panel=officials&oaction=delete&oid=' + currentDeleteOid, {
            method: 'GET',
            credentials: 'same-origin'
          })
          .then(function(response) { return response.text(); })
          .then(function(html) {
            // Show success message
            var successMsg = document.createElement('div');
            successMsg.className = 'alert alert-success';
            successMsg.innerHTML = '<strong>✓ Deleted!</strong> Official deleted successfully.';
            successMsg.style.position = 'fixed';
            successMsg.style.top = '20px';
            successMsg.style.right = '20px';
            successMsg.style.zIndex = '9999';
            document.body.appendChild(successMsg);
            
            setTimeout(function() {
              successMsg.remove();
            }, 3000);
            
            // Reload the list
            loadOfficialsContent('list');
            
            // Reset references
            currentDeleteBtn = null;
            currentDeleteOid = null;
          })
          .catch(function(error) {
            console.error('Error deleting official:', error);
            alert('Error deleting official. Please try again.');
            currentDeleteBtn.disabled = false;
            currentDeleteBtn.innerHTML = '🗑️ Delete';
          });
        });
      }
      
      // Delete archived buttons
      var currentDeleteArchivedBtn = null;
      var currentDeleteArchivedOid = null;
      document.querySelectorAll('.officials-delete-archived-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
          var oid = btn.getAttribute('data-oid');
          var name = btn.getAttribute('data-name');
          
          // Store references
          currentDeleteArchivedBtn = btn;
          currentDeleteArchivedOid = oid;
          
          // Set name in modal
          document.getElementById('deleteArchivedOfficialName').textContent = name;
          
          // Show modal
          var modal = new bootstrap.Modal(document.getElementById('deleteArchivedOfficialModal'));
          modal.show();
        });
      });
      
      // Handle delete archived confirmation
      var confirmDeleteArchivedBtn = document.getElementById('confirmDeleteArchivedOfficialBtn');
      if (confirmDeleteArchivedBtn) {
        confirmDeleteArchivedBtn.replaceWith(confirmDeleteArchivedBtn.cloneNode(true));
        document.getElementById('confirmDeleteArchivedOfficialBtn').addEventListener('click', function() {
          if (!currentDeleteArchivedBtn || !currentDeleteArchivedOid) return;
          
          // Close modal
          var modal = bootstrap.Modal.getInstance(document.getElementById('deleteArchivedOfficialModal'));
          modal.hide();
          
          currentDeleteArchivedBtn.disabled = true;
          currentDeleteArchivedBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
          
          fetch('admin_dashboard.php?panel=officials&oaction=delete_archived&oid=' + currentDeleteArchivedOid, {
            method: 'GET',
            credentials: 'same-origin'
          })
          .then(function(response) { return response.text(); })
          .then(function(html) {
            // Show success message
            var successMsg = document.createElement('div');
            successMsg.className = 'alert alert-success';
            successMsg.innerHTML = '<strong>✓ Deleted!</strong> Archived official deleted permanently.';
            successMsg.style.position = 'fixed';
            successMsg.style.top = '20px';
            successMsg.style.right = '20px';
            successMsg.style.zIndex = '9999';
            document.body.appendChild(successMsg);
            
            setTimeout(function() {
              successMsg.remove();
            }, 3000);
            
            // Reload the archived list
            loadOfficialsContent('archived');
            
            // Reset references
            currentDeleteArchivedBtn = null;
            currentDeleteArchivedOid = null;
          })
          .catch(function(error) {
            console.error('Error deleting archived official:', error);
            alert('Error deleting archived official. Please try again.');
            currentDeleteArchivedBtn.disabled = false;
            currentDeleteArchivedBtn.innerHTML = '🗑️ Delete';
          });
        });
      }
      
      // Back buttons
      document.querySelectorAll('.officials-back-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
          loadOfficialsContent('list');
        });
      });
      
      // Save form (Add/Edit)
      var saveForm = document.getElementById('officials-save-form');
      if (saveForm) {
        saveForm.addEventListener('submit', function(e) {
          e.preventDefault();
          
          var saveBtn = document.getElementById('officials-save-btn');
          var originalText = saveBtn.innerHTML;
          saveBtn.disabled = true;
          saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';
          
          var formData = new FormData(saveForm);
          formData.append('osave', '1');
          
          fetch('admin_dashboard.php?panel=officials', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
          })
          .then(function(response) { return response.text(); })
          .then(function(html) {
            // Show success message
            var successMsg = document.createElement('div');
            successMsg.className = 'alert alert-success';
            successMsg.innerHTML = '<strong>✓ Success!</strong> Official saved successfully.';
            successMsg.style.position = 'fixed';
            successMsg.style.top = '20px';
            successMsg.style.right = '20px';
            successMsg.style.zIndex = '9999';
            document.body.appendChild(successMsg);
            
            setTimeout(function() {
              successMsg.remove();
            }, 3000);
            
            // Load the list view
            loadOfficialsContent('list');
          })
          .catch(function(error) {
            console.error('Error saving official:', error);
            alert('Error saving official. Please try again.');
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
          });
        });
      }
      
      // Search form
      var officialsForm = document.getElementById('officials-search-form');
      if (officialsForm) {
        officialsForm.addEventListener('submit', function(e) {
          e.preventDefault();
          
          var searchInput = document.getElementById('officials-search-input').value;
          var startDate = document.getElementById('officials-start-date').value;
          var endDate = document.getElementById('officials-end-date').value;
          
          var urlParams = new URLSearchParams();
          urlParams.set('panel', 'officials');
          urlParams.set('oaction', 'list');
          if (searchInput) urlParams.set('osearch', searchInput);
          if (startDate) urlParams.set('ostart_date', startDate);
          if (endDate) urlParams.set('oend_date', endDate);
          
          var searchBtn = document.getElementById('officials-search-btn');
          var originalText = searchBtn.innerHTML;
          searchBtn.disabled = true;
          searchBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Loading...';
          
          fetch('admin_dashboard.php?' + urlParams.toString(), { 
            method: 'GET', 
            credentials: 'same-origin' 
          })
          .then(function(response) { return response.text(); })
          .then(function(html) {
            var parser = new DOMParser();
            var doc = parser.parseFromString(html, 'text/html');
            
            var newTableContainer = doc.querySelector('#officials-table-container');
            var currentTableContainer = document.querySelector('#officials-table-container');
            if (newTableContainer && currentTableContainer) {
              currentTableContainer.innerHTML = newTableContainer.innerHTML;
            }
            
            searchBtn.disabled = false;
            searchBtn.innerHTML = originalText;
          })
          .catch(function(error) {
            console.error('Error fetching officials:', error);
            alert('Error loading data. Please refresh the page.');
            searchBtn.disabled = false;
            searchBtn.innerHTML = originalText;
          });
        });
        
        // Reset button
        var resetBtn = document.getElementById('officials-reset-btn');
        if (resetBtn) {
          resetBtn.addEventListener('click', function() {
            document.getElementById('officials-search-input').value = '';
            document.getElementById('officials-start-date').value = '';
            document.getElementById('officials-end-date').value = '';
            officialsForm.dispatchEvent(new Event('submit'));
          });
        }
      }
    }
    
    // Initial attachment
    attachOfficialsEventListeners();
    </script>
    
    <!-- Delete Official Confirmation Modal -->
    <div class="modal fade" id="deleteOfficialModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-danger bg-opacity-10">
            <h5 class="modal-title">
              <i class="bi bi-trash"></i> Delete Official
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p class="mb-2">Are you sure you want to delete official:</p>
            <p class="mb-0"><strong id="deleteOfficialName"></strong></p>
            <small class="text-muted">This action will move the official to archives.</small>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-danger" id="confirmDeleteOfficialBtn">Delete</button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Delete Archived Official Confirmation Modal -->
    <div class="modal fade" id="deleteArchivedOfficialModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-danger bg-opacity-10">
            <h5 class="modal-title">
              <i class="bi bi-exclamation-triangle"></i> Permanently Delete Official
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p class="mb-2">Are you sure you want to <strong>permanently delete</strong> archived official:</p>
            <p class="mb-0"><strong id="deleteArchivedOfficialName"></strong></p>
            <small class="text-danger">⚠️ This action cannot be undone!</small>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-danger" id="confirmDeleteArchivedOfficialBtn">Permanently Delete</button>
          </div>
        </div>
      </div>
    </div>
  </div>
    <div id="panel-reports" class="panel-section" style="display:none;">
      <!-- Dashboard Header with Actions -->
      <div style="max-width: 1600px; margin: 0 auto 24px; background: linear-gradient(160deg, #14ad0f 0%, #43e97b 100%); padding: 32px; border-radius: 24px; box-shadow: 0 10px 40px rgba(20,173,15,0.2);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
          <div style="text-align: left; flex: 1;">
            <h2 style="font-weight:800;font-size:2.5rem;letter-spacing:0.5px;color:white;margin:0;text-shadow: 0 2px 8px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 12px;">
              <svg xmlns='http://www.w3.org/2000/svg' width='42' height='42' fill='white' viewBox='0 0 16 16' style='filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));'>
                <path d='M2 13.5V2.5a.5.5 0 0 1 1 0v11a.5.5 0 0 1-1 0zm3-7v7a.5.5 0 0 0 1 0V6.5a.5.5 0 0 0-1 0zm3 3v4a.5.5 0 0 0 1 0V9.5a.5.5 0 0 0-1 0zm3-2v6a.5.5 0 0 0 1 0V7.5a.5.5 0 0 0-1 0z'/>
              </svg>
              Analytics Dashboard
            </h2>
            <p style="color:rgba(255,255,255,0.95);font-size:1.05rem;margin:8px 0 0 0;font-weight:500;">Comprehensive overview of barangay operations and statistics</p>
            <div style="display:flex;gap:12px;margin-top:12px;flex-wrap:wrap;">
              <div style="background:rgba(255,255,255,0.15);backdrop-filter:blur(10px);padding:8px 16px;border-radius:10px;display:flex;align-items:center;gap:8px;border:1px solid rgba(255,255,255,0.2);box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="rgba(255,255,255,0.9)" viewBox="0 0 16 16">
                  <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/>
                </svg>
                <span id="dashboardCurrentDate" style="color:rgba(255,255,255,0.95);font-size:0.9rem;font-weight:500;letter-spacing:0.3px;">Loading...</span>
              </div>
              <div style="background:rgba(255,255,255,0.15);backdrop-filter:blur(10px);padding:8px 16px;border-radius:10px;display:flex;align-items:center;gap:8px;border:1px solid rgba(255,255,255,0.2);box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="rgba(255,255,255,0.9)" viewBox="0 0 16 16">
                  <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                  <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                </svg>
                <span id="dashboardClock" style="color:rgba(255,255,255,0.95);font-size:0.9rem;font-weight:500;font-family:monospace;letter-spacing:0.5px;">--:--:-- --</span>
              </div>
            </div>
          </div>
          <div>
            <select id="dashboardTimePeriod" style="background: rgba(255,255,255,0.95); color: #14ad0f; border: 2px solid rgba(255,255,255,0.3); padding: 12px 24px; border-radius: 12px; font-weight: 600; font-size: 1rem; cursor: pointer; box-shadow: 0 4px 16px rgba(0,0,0,0.15); transition: all 0.3s; backdrop-filter: blur(10px);" onchange="updateDashboardByPeriod(this.value)">
              <option value="today">📅 Today</option>
              <option value="week">📆 This Week</option>
              <option value="month" selected>📊 This Month</option>
              <option value="year">📈 This Year</option>
              <option value="all">🌐 All Time</option>
            </select>
          </div>
        </div>
      </div>
      
      <!-- Quick Stats Overview -->
      <div style="max-width: 1600px; margin: 0 auto 32px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 24px;">
          <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 24px; border-radius: 16px; box-shadow: 0 8px 24px rgba(102,126,234,0.25); position: relative; overflow: hidden;">
            <div style="position: absolute; top: -20px; right: -20px; width: 100px; height: 100px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
            <div style="position: relative; z-index: 1;">
              <div style="color: rgba(255,255,255,0.9); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; margin-bottom: 8px;">Total Certificate Requests</div>
              <div style="color: white; font-size: 2.5rem; font-weight: 800; margin-bottom: 4px;" id="totalRequestsCount">0</div>
              <div style="color: rgba(255,255,255,0.8); font-size: 0.8rem;">
                <i class="bi bi-graph-up" style="margin-right: 4px;"></i>
                <span id="requestsTrend">+0%</span> from last period
              </div>
            </div>
          </div>
          
          <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 24px; border-radius: 16px; box-shadow: 0 8px 24px rgba(240,147,251,0.25); position: relative; overflow: hidden;">
            <div style="position: absolute; top: -20px; right: -20px; width: 100px; height: 100px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
            <div style="position: relative; z-index: 1;">
              <div style="color: rgba(255,255,255,0.9); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; margin-bottom: 8px;">Total Incidents</div>
              <div style="color: white; font-size: 2.5rem; font-weight: 800; margin-bottom: 4px;" id="totalIncidentsCount">0</div>
              <div style="color: rgba(255,255,255,0.8); font-size: 0.8rem;">
                <i class="bi bi-graph-up" style="margin-right: 4px;"></i>
                <span id="incidentsTrend">+0%</span> from last period
              </div>
            </div>
          </div>
          
          <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); padding: 24px; border-radius: 16px; box-shadow: 0 8px 24px rgba(79,172,254,0.25); position: relative; overflow: hidden;">
            <div style="position: absolute; top: -20px; right: -20px; width: 100px; height: 100px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
            <div style="position: relative; z-index: 1;">
              <div style="color: rgba(255,255,255,0.9); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; margin-bottom: 8px;">Avg Response Time</div>
              <div style="color: white; font-size: 2.5rem; font-weight: 800; margin-bottom: 4px;" id="avgResponseTime">0h</div>
              <div style="color: rgba(255,255,255,0.8); font-size: 0.8rem;">
                <i class="bi bi-clock-history" style="margin-right: 4px;"></i>
                Processing speed
              </div>
            </div>
          </div>
          
          <div style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); padding: 24px; border-radius: 16px; box-shadow: 0 8px 24px rgba(67,233,123,0.25); position: relative; overflow: hidden;">
            <div style="position: absolute; top: -20px; right: -20px; width: 100px; height: 100px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
            <div style="position: relative; z-index: 1;">
              <div style="color: rgba(255,255,255,0.9); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; margin-bottom: 8px;">Completion Rate</div>
              <div style="color: white; font-size: 2.5rem; font-weight: 800; margin-bottom: 4px;" id="completionRate">0%</div>
              <div style="color: rgba(255,255,255,0.8); font-size: 0.8rem;">
                <i class="bi bi-check-circle" style="margin-right: 4px;"></i>
                Success rate
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <style>
      @keyframes fadeInDown {
        0% { transform: translateY(-20px); opacity: 0; }
        100% { transform: translateY(0); opacity: 1; }
      }
      
      #dashboardTimePeriod {
        transition: all 0.3s ease;
      }
      
      #dashboardTimePeriod:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.2) !important;
        border-color: #14ad0f !important;
      }
      
      #dashboardTimePeriod:focus {
        outline: none;
        border-color: #14ad0f !important;
        box-shadow: 0 0 0 3px rgba(20,173,15,0.2);
      }
      </style>
      
      <!-- JobFinder Statistics -->
      <div style="max-width: 1600px; margin: 0 auto 32px;">
        <h3 style="font-size: 1.5rem; font-weight: 700; color: #1a1a1a; margin-bottom: 20px; display: flex; align-items: center; gap: 12px;">
          <i class="bi bi-briefcase-fill" style="color: #14ad0f;"></i>
          JobFinder Statistics
        </h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 32px;">
          <?php
          // Get JobFinder statistics
          $skilled_residents_sql = "SELECT COUNT(*) as count FROM residents WHERE occupation_skills IS NOT NULL AND occupation_skills != '' AND COALESCE(blocked_from_jobfinder, 0) = 0";
          $skilled_residents_result = $conn->query($skilled_residents_sql);
          $skilled_residents_count = $skilled_residents_result->fetch_assoc()['count'];
          
          $avg_rating_sql = "SELECT COALESCE(AVG(rating), 0) as overall_avg FROM chat_ratings WHERE receiver_id IN (SELECT unique_id FROM residents WHERE occupation_skills IS NOT NULL AND occupation_skills != '')";
          $avg_rating_result = $conn->query($avg_rating_sql);
          $overall_avg_rating = $avg_rating_result->fetch_assoc()['overall_avg'];
          
          $active_skills_sql = "SELECT COUNT(DISTINCT occupation_skills) as skill_count FROM residents WHERE occupation_skills IS NOT NULL AND occupation_skills != ''";
          $active_skills_result = $conn->query($active_skills_sql);
          $active_skills_count = $active_skills_result->fetch_assoc()['skill_count'];
          ?>
          
          <!-- Total Skilled Residents Card -->
          <div style="background: white; padding: 24px; border-radius: 16px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 16px; transition: transform 0.3s, box-shadow 0.3s; cursor: pointer;" onclick="navigateToPanel('panel-jobfinder')" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.12)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 16px rgba(0,0,0,0.08)';">
            <div style="background: linear-gradient(135deg, #14ad0f 0%, #43e97b 100%); width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
              <i class="bi bi-people-fill" style="font-size: 24px; color: white;"></i>
            </div>
            <div>
              <div style="color: #6c757d; font-size: 0.85rem; font-weight: 600; margin-bottom: 4px;">Total Skilled Residents</div>
              <div style="color: #1a1a1a; font-size: 2rem; font-weight: 800; line-height: 1;"><?= $skilled_residents_count ?></div>
            </div>
          </div>
          
          <!-- Average Rating Card -->
          <div style="background: white; padding: 24px; border-radius: 16px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 16px; transition: transform 0.3s, box-shadow 0.3s; cursor: pointer;" onclick="navigateToPanel('panel-jobfinder')" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.12)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 16px rgba(0,0,0,0.08)';">
            <div style="background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%); width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
              <i class="bi bi-star-fill" style="font-size: 24px; color: white;"></i>
            </div>
            <div>
              <div style="color: #6c757d; font-size: 0.85rem; font-weight: 600; margin-bottom: 4px;">Avg Rating</div>
              <div style="color: #1a1a1a; font-size: 2rem; font-weight: 800; line-height: 1;"><?= number_format($overall_avg_rating, 1) ?></div>
            </div>
          </div>
          
          <!-- Active Skills Card -->
          <div style="background: white; padding: 24px; border-radius: 16px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 16px; transition: transform 0.3s, box-shadow 0.3s; cursor: pointer;" onclick="navigateToPanel('panel-jobfinder')" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.12)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 16px rgba(0,0,0,0.08)';">
            <div style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
              <i class="bi bi-briefcase-fill" style="font-size: 24px; color: white;"></i>
            </div>
            <div>
              <div style="color: #6c757d; font-size: 0.85rem; font-weight: 600; margin-bottom: 4px;">Active Skills</div>
              <div style="color: #1a1a1a; font-size: 2rem; font-weight: 800; line-height: 1;"><?= $active_skills_count ?></div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Quick Actions & Status Cards -->
      <div style="max-width: 1600px; margin: 0 auto 32px;">
        <h3 style="font-size: 1.5rem; font-weight: 700; color: #1a1a1a; margin-bottom: 20px; display: flex; align-items: center; gap: 12px;">
          <i class="bi bi-lightning-charge-fill" style="color: #14ad0f;"></i>
          Quick Actions & Pending Tasks
        </h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 32px; min-width: 0;">
          
          <!-- Unsolved Incidents Card -->
          <div class="task-card" id="reportsIncidentsCard" style="background: linear-gradient(135deg, #ffd89b 0%, #19547b 100%); cursor: pointer;" onclick="navigateToPanel('panel-incidents')">
            <div class="task-icon" style="background: rgba(255,255,255,0.3); color: white;">
              <i class="bi bi-exclamation-triangle"></i>
            </div>
            <div class="task-content">
              <div class="task-title" style="color: white;">Unsolved Incidents</div>
              <div class="task-subtitle" id="reportsIncidentsSubtitle" style="color: rgba(255,255,255,0.9);">2 incidents need attention</div>
            </div>
            <div class="task-badge" id="reportsIncidentsBadge" style="background: rgba(255,255,255,0.3);">2</div>
          </div>
          
          <!-- Pending & Approved Certificates Card -->
          <div class="task-card" id="reportsCertificatesCard" style="background: linear-gradient(135deg, #89f7fe 0%, #66a6ff 100%); cursor: pointer;" onclick="navigateToPanel('panel-certificates')">
            <div class="task-icon" style="background: rgba(255,255,255,0.3); color: white;">
              <i class="bi bi-file-earmark-text"></i>
            </div>
            <div class="task-content">
              <div class="task-title" style="color: white;">Pending & Approved Certificates</div>
              <div class="task-subtitle" id="reportsCertificatesSubtitle" style="color: rgba(255,255,255,0.9);">7 certificates to process</div>
            </div>
            <div class="task-badge" id="reportsCertificatesBadge" style="background: rgba(255,255,255,0.3);">7</div>
          </div>
          
          <!-- JobFinder Reports Card -->
          <div class="task-card" id="reportsChatReportsCard" style="background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); cursor: pointer;" onclick="navigateToPanel('panel-jobfinder')">
            <div class="task-icon" style="background: rgba(255,255,255,0.3); color: #d35400;">
              <i class="bi bi-chat-dots"></i>
            </div>
            <div class="task-content">
              <div class="task-title" style="color: #d35400;">JobFinder Reports</div>
              <div class="task-subtitle" id="reportsChatReportsSubtitle" style="color: #d35400;">1 reports need review</div>
            </div>
            <div class="task-badge" id="reportsChatReportsBadge" style="background: #d35400;">1</div>
          </div>
          
          <!-- Unread Messages Card -->
          <div class="task-card" id="reportsMessagesCard" style="background: linear-gradient(135deg, #fccb90 0%, #d57eeb 100%); cursor: pointer;" onclick="navigateToPanel('panel-admin-chats')">
            <div class="task-icon" style="background: rgba(255,255,255,0.3); color: white;">
              <i class="bi bi-envelope"></i>
            </div>
            <div class="task-content">
              <div class="task-title" style="color: white;">Unread Messages</div>
              <div class="task-subtitle" id="reportsMessagesSubtitle" style="color: rgba(255,255,255,0.9);">0 unread messages</div>
            </div>
            <div class="task-badge" id="reportsMessagesBadge" style="background: rgba(255,255,255,0.3);">0</div>
          </div>
          
        </div>
      </div>
      
      <!-- Recent Activity Timeline -->
      <div style="max-width: 1600px; margin: 0 auto 32px;">
        <h3 style="font-size: 1.5rem; font-weight: 700; color: #1a1a1a; margin-bottom: 20px; display: flex; align-items: center; gap: 12px;">
          <i class="bi bi-clock-history" style="color: #14ad0f;"></i>
          Recent Activity
        </h3>
        <div style="background: white; border-radius: 20px; padding: 28px; box-shadow: 0 10px 40px rgba(0,0,0,0.08);">
          <div id="recentActivityTimeline" style="max-height: 400px; overflow-y: auto;">
            <!-- Activity items will be populated here -->
            <div style="text-align: center; padding: 40px; color: #999;">
              <i class="bi bi-hourglass-split" style="font-size: 3rem; margin-bottom: 16px; display: block;"></i>
              <p>Loading recent activities...</p>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Analytics Charts Section -->
      <div style="max-width: 1600px; margin: 0 auto 32px;">
        <h3 style="font-size: 1.5rem; font-weight: 700; color: #1a1a1a; margin-bottom: 20px; display: flex; align-items: center; gap: 12px;">
          <i class="bi bi-bar-chart-line-fill" style="color: #14ad0f;"></i>
          Analytics & Insights
        </h3>
      </div>
      
      <div class="grid">
        <div class="card-graph modern-card" style="animation: fadeInUp 0.4s ease-out;">
          <div class="card-graph-header">
            <span class="icon-box" style="background:linear-gradient(160deg, #14ad0f 0%, #43e97b 100%);"><i data-lucide="calendar-range"></i></span>
            <div>
              <h3>Monthly Requests</h3>
              <p style="margin:0;font-size:0.85rem;color:#666;font-weight:400;">Certificate requests trend</p>
            </div>
          </div>
          <canvas id="requestsChart"></canvas>
        </div>
        <div class="card-graph modern-card" style="animation: fadeInUp 0.5s ease-out;">
          <div class="card-graph-header">
            <span class="icon-box" style="background:linear-gradient(160deg, #2e7d32 0%, #66bb6a 100%);"><i data-lucide="activity"></i></span>
            <div>
              <h3>Monthly Incidents</h3>
              <p style="margin:0;font-size:0.85rem;color:#666;font-weight:400;">Incident reports trend</p>
            </div>
          </div>
          <canvas id="incidentsChart"></canvas>
        </div>
        <div class="card-graph modern-card" style="animation: fadeInUp 0.9s ease-out;">
          <div class="card-graph-header">
            <span class="icon-box" style="background:linear-gradient(160deg, #d32f2f 0%, #ef5350 100%);"><i data-lucide="bar-chart-3"></i></span>
            <div>
              <h3>Top 5 Incidents</h3>
              <p style="margin:0;font-size:0.85rem;color:#666;font-weight:400;">By type (Current Month)</p>
            </div>
          </div>
          <canvas id="topIncidentsChart"></canvas>
        </div>
        <div class="card-graph modern-card" style="grid-column: 1 / -1; animation: fadeInUp 1.0s ease-out;">
          <div class="card-graph-header">
            <span class="icon-box" style="background:linear-gradient(160deg, #14ad0f 0%, #43e97b 100%);"><i data-lucide="users"></i></span>
            <div>
              <h3>Community Demographics & Accounts</h3>
              <p style="margin:0;font-size:0.85rem;color:#666;font-weight:400;">Population breakdown by gender, special groups, and account status</p>
            </div>
          </div>
          <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:16px;margin-top:20px;margin-bottom:24px;">
            <div style="background:linear-gradient(135deg,rgba(20,173,15,0.1),rgba(67,233,123,0.1));padding:16px;border-radius:12px;border-left:4px solid #14ad0f;box-shadow: 0 2px 8px rgba(20,173,15,0.1);">
              <div style="font-size:0.8rem;color:#2d5016;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">Total Residents</div>
              <div style="font-size:2rem;font-weight:800;color:#14ad0f;" id="totalResidents">-</div>
            </div>
            <div style="background:linear-gradient(135deg,rgba(46,125,50,0.1),rgba(102,187,106,0.1));padding:16px;border-radius:12px;border-left:4px solid #2e7d32;box-shadow: 0 2px 8px rgba(46,125,50,0.1);">
              <div style="font-size:0.8rem;color:#1b5e20;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">Active Accounts</div>
              <div style="font-size:2rem;font-weight:800;color:#2e7d32;" id="activeAccounts">-</div>
            </div>
            <div style="background:linear-gradient(135deg,rgba(76,175,80,0.1),rgba(129,199,132,0.1));padding:16px;border-radius:12px;border-left:4px solid #4caf50;box-shadow: 0 2px 8px rgba(76,175,80,0.1);">
              <div style="font-size:0.8rem;color:#2e7d32;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">Households</div>
              <div style="font-size:2rem;font-weight:800;color:#4caf50;" id="householdCount">-</div>
            </div>
            <div style="background:linear-gradient(135deg,rgba(56,142,60,0.1),rgba(129,199,132,0.1));padding:16px;border-radius:12px;border-left:4px solid #388e3c;box-shadow: 0 2px 8px rgba(56,142,60,0.1);">
              <div style="font-size:0.8rem;color:#2e7d32;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">Senior Citizens</div>
              <div style="font-size:2rem;font-weight:800;color:#388e3c;" id="seniorCount">-</div>
            </div>
            <div style="background:linear-gradient(135deg,rgba(67,160,71,0.1),rgba(165,214,167,0.1));padding:16px;border-radius:12px;border-left:4px solid #43a047;box-shadow: 0 2px 8px rgba(67,160,71,0.1);">
              <div style="font-size:0.8rem;color:#388e3c;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">PWD</div>
              <div style="font-size:2rem;font-weight:800;color:#43a047;" id="pwdCount">-</div>
            </div>
          </div>
          <canvas id="combinedChart" style="max-height: 380px !important; height: 340px !important;"></canvas>
        </div>
      </div>
      <style>
        @keyframes fadeInUp {
          0% { transform: translateY(30px); opacity: 0; }
          100% { transform: translateY(0); opacity: 1; }
        }
      </style>
      <style>
        .modern-card {
          border: none;
          box-shadow: 0 10px 40px rgba(0,0,0,0.08), 0 2px 8px rgba(0,0,0,0.04);
          padding: 28px 24px;
          border-radius: 20px;
          background: rgba(255, 255, 255, 0.95);
          backdrop-filter: blur(10px);
          transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
          position: relative;
          overflow: hidden;
        }
        .modern-card::before {
          content: '';
          position: absolute;
          top: 0;
          left: 0;
          right: 0;
          height: 4px;
          background: linear-gradient(90deg, #14ad0f 0%, #43e97b 50%, #66bb6a 100%);
          opacity: 0;
          transition: opacity 0.3s ease;
        }
        .modern-card:hover {
          box-shadow: 0 20px 60px rgba(20,173,15,0.15), 0 4px 16px rgba(20,173,15,0.1);
          transform: translateY(-8px);
        }
        .modern-card:hover::before {
          opacity: 1;
        }
        .card-graph-header {
          display: flex;
          align-items: flex-start;
          gap: 16px;
          margin-bottom: 20px;
        }
        .card-graph-header h3 {
          margin: 0;
          font-size: 1.2rem;
          font-weight: 700;
          color: #1a1a1a;
          letter-spacing: -0.3px;
          line-height: 1.3;
        }
        .icon-box {
          display: flex;
          align-items: center;
          justify-content: center;
          width: 48px;
          height: 48px;
          min-width: 48px;
          border-radius: 14px;
          color: #fff;
          font-size: 1.4rem;
          box-shadow: 0 4px 16px rgba(0,0,0,0.15);
          transition: transform 0.3s ease;
        }
        .modern-card:hover .icon-box {
          transform: scale(1.1) rotate(5deg);
        }
        .card-graph canvas {
          margin-top: 16px;
        }
        @media (max-width: 900px) {
          .modern-card { 
            padding: 20px 16px;
          }
          .card-graph-header h3 {
            font-size: 1.05rem;
          }
          .icon-box {
            width: 40px;
            height: 40px;
            min-width: 40px;
          }
        }
        
        /* Pulse animation for stats updates */
        @keyframes pulse {
          0% {
            transform: scale(1);
          }
          50% {
            transform: scale(1.1);
            color: #fff;
            text-shadow: 0 0 20px rgba(255,255,255,0.8);
          }
          100% {
            transform: scale(1);
          }
        }
      </style>
      <script>
        lucide.createIcons();
        
        // Store hash of current activities from server to detect changes
        let currentActivitiesHash = null;
        
        // Load recent activity timeline
        function loadRecentActivity(showLoading = true) {
          const timeline = document.getElementById('recentActivityTimeline');
          if (!timeline) return;
          
          // Show loading state only on initial load
          if (showLoading) {
            timeline.innerHTML = `
              <div style="text-align: center; padding: 40px; color: #999;">
                <i class="bi bi-hourglass-split" style="font-size: 3rem; margin-bottom: 16px; display: block;"></i>
                <p>Loading recent activities...</p>
              </div>
            `;
          }
          
          // Fetch real activity data from API
          fetch('admin_dashboard.php?action=get_recent_activity')
            .then(response => response.json())
            .then(data => {
              if (data.success && data.activities && data.activities.length > 0) {
                const activities = data.activities;
                
                // Use server-provided hash to detect changes
                const newHash = data.dataHash;
                
                // Only update UI if data has actually changed on the server
                if (newHash !== currentActivitiesHash) {
                  currentActivitiesHash = newHash;
                  
                  let html = '<div style="position: relative;">';
                  html += '<div style="position: absolute; left: 24px; top: 0; bottom: 0; width: 2px; background: linear-gradient(to bottom, #14ad0f, #43e97b);"></div>';
                  
                  activities.forEach((activity, index) => {
                    html += `
                      <div style="position: relative; padding-left: 60px; padding-bottom: 24px; animation: fadeInLeft 0.5s ease ${index * 0.1}s both;">
                        <div style="position: absolute; left: 0; top: 0; width: 48px; height: 48px; border-radius: 50%; background: ${activity.color}; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.15); border: 4px solid white;">
                          <i class="bi bi-${activity.icon}" style="color: white; font-size: 1.2rem;"></i>
                        </div>
                        <div style="background: #f8f9fa; padding: 16px; border-radius: 12px; border-left: 3px solid ${activity.color};">
                          <div style="font-weight: 600; color: #1a1a1a; margin-bottom: 4px;">${activity.title}</div>
                          <div style="font-size: 0.9rem; color: #666; margin-bottom: 8px;">${activity.desc}</div>
                          <div style="font-size: 0.8rem; color: #999;">
                            <i class="bi bi-clock" style="margin-right: 4px;"></i>${activity.time}
                          </div>
                        </div>
                      </div>
                    `;
                  });
                  
                  html += '</div>';
                  html += '<style>@keyframes fadeInLeft { 0% { opacity: 0; transform: translateX(-20px); } 100% { opacity: 1; transform: translateX(0); } }</style>';
                  
                  timeline.innerHTML = html;
                }
              } else {
                // No activities found
                const emptyHash = data.dataHash || 'empty';
                if (emptyHash !== currentActivitiesHash) {
                  currentActivitiesHash = emptyHash;
                  timeline.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #999;">
                      <i class="bi bi-inbox" style="font-size: 3rem; margin-bottom: 16px; display: block;"></i>
                      <p>No recent activities found</p>
                    </div>
                  `;
                }
              }
            })
            .catch(error => {
              console.error('Error loading recent activities:', error);
              timeline.innerHTML = `
                <div style="text-align: center; padding: 40px; color: #dc3545;">
                  <i class="bi bi-exclamation-triangle" style="font-size: 3rem; margin-bottom: 16px; display: block;"></i>
                  <p>Error loading recent activities</p>
                </div>
              `;
            });
        }
        
        // Check for new activities without showing loading state
        function checkForNewActivities() {
          loadRecentActivity(false);
        }
        
        // Update dashboard statistics based on time period
        function updateDashboardByPeriod(period) {
          console.log('Fetching dashboard stats for period:', period);
          
          // Fetch dashboard stats from server
          fetch('admin_dashboard.php?action=get_dashboard_stats&period=' + period)
            .then(response => {
              console.log('Response status:', response.status);
              return response.text();
            })
            .then(text => {
              console.log('Response text:', text);
              try {
                const data = JSON.parse(text);
                console.log('Parsed data:', data);
                
                if (data.success) {
                  // Helper function to update with animation
                  const updateWithAnimation = (elementId, newValue) => {
                    const element = document.getElementById(elementId);
                    if (element && element.textContent !== newValue) {
                      element.style.animation = 'pulse 0.5s ease';
                      element.textContent = newValue;
                      setTimeout(() => {
                        element.style.animation = '';
                      }, 500);
                    } else if (element) {
                      element.textContent = newValue;
                    }
                  };
                  
                  // Update Total Requests
                  updateWithAnimation('totalRequestsCount', data.totalRequests || 0);
                  updateWithAnimation('requestsTrend', data.requestsTrend || '+0%');
                  
                  // Update Total Incidents
                  updateWithAnimation('totalIncidentsCount', data.totalIncidents || 0);
                  updateWithAnimation('incidentsTrend', data.incidentsTrend || '+0%');
                  
                  // Update Avg Response Time
                  updateWithAnimation('avgResponseTime', data.avgResponseTime || '0h');
                  
                  // Update Completion Rate
                  updateWithAnimation('completionRate', data.completionRate || '0%');
                } else {
                  console.error('API returned error:', data.message);
                }
              } catch (e) {
                console.error('JSON parse error:', e);
                console.error('Raw response:', text);
              }
            })
            .catch(error => {
              console.error('Fetch error:', error);
            });
        }
        
        // Update dashboard statistics (default to month)
        function updateDashboardStats() {
          const period = document.getElementById('dashboardTimePeriod')?.value || 'month';
          updateDashboardByPeriod(period);
        }
        
        // Update dashboard date and time
        function updateDashboardDateTime() {
          const now = new Date();
          const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
          const dateStr = now.toLocaleDateString('en-US', options);
          const timeStr = now.toLocaleTimeString('en-US', { hour12: true });
          
          const dateElement = document.getElementById('dashboardCurrentDate');
          const clockElement = document.getElementById('dashboardClock');
          
          if (dateElement) dateElement.textContent = dateStr;
          if (clockElement) clockElement.textContent = timeStr;
        }
        
        // Initialize dashboard when panel is shown
        if (document.getElementById('panel-reports').style.display !== 'none') {
          loadRecentActivity();
          updateDashboardStats();
          updateDashboardDateTime();
          setInterval(updateDashboardDateTime, 1000);
        }
        
        // Also initialize when switching to reports panel
        let dashboardClockInterval = null;
        let dashboardStatsInterval = null;
        let recentActivityInterval = null;
        document.addEventListener('DOMContentLoaded', function() {
          const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
              if (mutation.target.id === 'panel-reports' && mutation.target.style.display !== 'none') {
                loadRecentActivity();
                updateDashboardStats();
                updateDashboardDateTime();
                
                // Clear existing intervals and start new ones
                if (dashboardClockInterval) clearInterval(dashboardClockInterval);
                if (dashboardStatsInterval) clearInterval(dashboardStatsInterval);
                if (recentActivityInterval) clearInterval(recentActivityInterval);
                
                dashboardClockInterval = setInterval(updateDashboardDateTime, 1000);
                // Auto-refresh dashboard stats every 30 seconds
                dashboardStatsInterval = setInterval(updateDashboardStats, 30000);
                // Check for new activities every 10 seconds (only updates UI if data changed)
                recentActivityInterval = setInterval(checkForNewActivities, 10000);
              } else if (mutation.target.id === 'panel-reports' && mutation.target.style.display === 'none') {
                // Clear intervals when panel is hidden to save resources
                if (dashboardStatsInterval) {
                  clearInterval(dashboardStatsInterval);
                  dashboardStatsInterval = null;
                }
                if (recentActivityInterval) {
                  clearInterval(recentActivityInterval);
                  recentActivityInterval = null;
                }
              }
            });
          });
          
          const reportsPanel = document.getElementById('panel-reports');
          if (reportsPanel) {
            observer.observe(reportsPanel, { attributes: true, attributeFilter: ['style'] });
          }
        });
      </script>
    </div>
  <!-- Announcements Panel (hidden by default) -->
    <div id="panel-announcements" class="panel-section" style="display:none;">
      <div class="container-fluid py-4">
        <!-- Modern Header with Gradient -->
        <div class="announcement-header-modern mb-4 p-4 rounded-4 shadow-sm" style="background: linear-gradient(160deg, #14ad0f 0%, #43e97b 100%);">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
              <div class="announcement-icon-wrapper d-flex align-items-center justify-content-center rounded-circle" style="width: 60px; height: 60px; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); box-shadow: 0 4px 12px rgba(20,173,15,0.2);">
                <i class="bi bi-megaphone-fill text-white" style="font-size: 1.8rem; filter: drop-shadow(0 2px 4px rgba(20,173,15,0.3));"></i>
              </div>
              <div>
                <h2 class="fw-bold text-white mb-1" id="announcement-panel-title" style="font-size: 1.75rem; text-shadow: 0 2px 8px rgba(20,173,15,0.3); letter-spacing: 0.5px;">
                  <?= $ann_action === 'archives' ? 'Archived Announcements' : 'Barangay Announcements' ?>
                </h2>
                <p class="text-white mb-0" style="font-size: 0.9rem; opacity: 0.9;">
                  <i class="bi bi-info-circle me-1"></i>
                  <?= $ann_action === 'archives' ? 'View and manage archived announcements' : 'Manage community announcements and updates' ?>
                </p>
              </div>
            </div>
            <div class="btn-group shadow-sm" role="group">
              <button class="btn announcement-view-toggle <?= $ann_action !== 'archives' ? 'btn-light fw-semibold active' : 'btn-outline-light' ?>" data-ann-action="" style="min-width: 100px; border-radius: 12px 0 0 12px;">
                <i class="bi bi-megaphone me-1"></i> Active
              </button>
              <button class="btn announcement-view-toggle <?= $ann_action === 'archives' ? 'btn-light fw-semibold active' : 'btn-outline-light' ?>" data-ann-action="archives" style="min-width: 100px; border-radius: 0 12px 12px 0;">
                <i class="bi bi-archive me-1"></i> Archives
              </button>
            </div>
          </div>
        </div>

        <div id="announcement-message-area"></div>

        <div id="announcement-content-area">
        <?php if ($ann_action === 'add' || $editing): ?>
          <!-- Add/Edit Form -->
          <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="card-header border-0 py-3" style="background: linear-gradient(160deg, #14ad0f 0%, #43e97b 100%); box-shadow: 0 4px 12px rgba(20,173,15,0.15);">
              <h4 class="mb-0 text-white fw-bold" style="text-shadow: 0 2px 4px rgba(20,173,15,0.2);">
                <i class="bi bi-<?= $ann_action === 'add' ? 'plus-circle' : 'pencil-square' ?> me-2"></i>
                <?= $ann_action === 'add' ? 'Add New Announcement' : 'Edit Announcement' ?>
              </h4>
            </div>
            <div class="card-body p-4">
              <form method="post" enctype="multipart/form-data" class="row g-3" id="announcement-form">
                <input type="hidden" name="ann_submit" value="1">
                <?php if ($editing): ?>
                <input type="hidden" name="ann_id" value="<?= htmlspecialchars($editing['id']) ?>">
                <input type="hidden" name="ann_action" value="edit">
                <?php else: ?>
                <input type="hidden" name="ann_action" value="add">
                <?php endif; ?>
                <div class="col-12">
                  <label class="form-label fw-semibold" style="color: #14ad0f;"><i class="bi bi-type me-2"></i>Title</label>
                  <input name="title" type="text" value="<?= htmlspecialchars($editing['title'] ?? '') ?>" class="form-control form-control-lg rounded-3" placeholder="Enter announcement title..." style="border: 2px solid #e0e0e0; transition: all 0.3s;" onfocus="this.style.borderColor='#14ad0f'; this.style.boxShadow='0 0 0 0.2rem rgba(20,173,15,0.15)'" onblur="this.style.borderColor='#e0e0e0'; this.style.boxShadow='none'" required>
                </div>
                <div class="col-12">
                  <label class="form-label fw-semibold" style="color: #14ad0f;"><i class="bi bi-text-paragraph me-2"></i>Content</label>
                  <textarea name="content" rows="6" class="form-control rounded-3" placeholder="Enter announcement content..." style="border: 2px solid #e0e0e0; transition: all 0.3s;" onfocus="this.style.borderColor='#14ad0f'; this.style.boxShadow='0 0 0 0.2rem rgba(20,173,15,0.15)'" onblur="this.style.borderColor='#e0e0e0'; this.style.boxShadow='none'" required><?= htmlspecialchars($editing['content'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold" style="color: #14ad0f;"><i class="bi bi-flag me-2"></i>Status</label>
                  <select name="status" class="form-select rounded-3" style="border: 2px solid #e0e0e0; transition: all 0.3s;" onfocus="this.style.borderColor='#14ad0f'; this.style.boxShadow='0 0 0 0.2rem rgba(20,173,15,0.15)'" onblur="this.style.borderColor='#e0e0e0'; this.style.boxShadow='none'">
                    <option value="normal" <?= ($editing['status'] ?? '') === 'normal' ? 'selected' : '' ?>>⏱️ Normal (expires in 7 days)</option>
                    <option value="news" <?= ($editing['status'] ?? '') === 'news' ? 'selected' : '' ?>>📰 News (no expiry)</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold" style="color: #14ad0f;"><i class="bi bi-image me-2"></i>Image (optional)</label>
                  <input type="file" name="image" accept="image/*" class="form-control rounded-3" style="border: 2px solid #e0e0e0; transition: all 0.3s;" onfocus="this.style.borderColor='#14ad0f'; this.style.boxShadow='0 0 0 0.2rem rgba(20,173,15,0.15)'" onblur="this.style.borderColor='#e0e0e0'; this.style.boxShadow='none'">
                  <?php if ($editing && $editing['image']): ?>
                    <img src="uploads/<?= htmlspecialchars($editing['image']) ?>" class="img-thumbnail mt-2" style="max-width:150px;">
                  <?php endif; ?>
                </div>
                <div class="col-12 d-flex justify-content-between gap-2 pt-3">
                  <button type="submit" class="btn btn-lg px-5 fw-semibold shadow-sm announcement-submit-btn" style="background: linear-gradient(90deg, #14ad0f 0%, #43e97b 100%); border: none; color: white; border-radius: 12px; transition: all 0.3s;">
                    <i class="bi bi-<?= $ann_action === 'add' ? 'send' : 'check-circle' ?> me-2"></i>
                    <?= $ann_action === 'add' ? 'Post Announcement' : 'Save Changes' ?>
                  </button>
                  <button type="button" class="btn btn-outline-secondary btn-lg px-4 announcement-cancel-btn" style="border-radius: 12px; border-width: 2px;">
                    <i class="bi bi-x-circle me-2"></i>Cancel
                  </button>
                </div>
              </form>
            </div>
          </div>
        <?php else: ?>
          <!-- List -->
          <div class="mb-4" id="announcement-add-btn-container">
            <?php if ($ann_action !== 'archives'): ?>
              <button class="btn btn-lg shadow-sm announcement-add-btn fw-semibold" data-ann-action="add" style="background: linear-gradient(90deg, #14ad0f 0%, #43e97b 100%); border: none; color: white; border-radius: 12px; letter-spacing: 0.5px;">
                <i class="bi bi-plus-circle me-2"></i>Add New Announcement
              </button>
            <?php endif; ?>
          </div>
          <div class="row g-4" id="announcement-list-container">
            <?php if (empty($anns)): ?>
              <div class="col-12">
                <div class="text-center py-5">
                  <div class="mb-3">
                    <i class="bi bi-inbox" style="font-size: 4rem; color: #e0e0e0;"></i>
                  </div>
                  <h5 class="text-muted">No <?= $ann_action === 'archives' ? 'archived' : 'active' ?> announcements yet</h5>
                  <p class="text-muted">Start by creating your first announcement!</p>
                </div>
              </div>
            <?php else: ?>
              <?php foreach ($anns as $a): ?>
                <div class="col-md-6 col-lg-4 col-xl-3">
                  <div class="card h-100 border-0 shadow-sm announcement-card-modern" style="border-radius: 16px; overflow: hidden; transition: all 0.3s ease;">
                    <?php if ($a['image']): ?>
                      <div class="position-relative" style="height: 180px; overflow: hidden;">
                        <img src="uploads/<?= htmlspecialchars($a['image']) ?>" class="w-100 h-100" style="object-fit: cover;">
                        <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.1) 100%);"></div>
                        <?php if ($a['status'] === 'news'): ?>
                          <span class="position-absolute top-0 end-0 m-2 badge" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); font-size: 0.75rem; padding: 0.4rem 0.8rem; border-radius: 20px;">
                            <i class="bi bi-newspaper me-1"></i>News
                          </span>
                        <?php endif; ?>
                      </div>
                    <?php else: ?>
                      <div class="position-relative d-flex align-items-center justify-content-center" style="height: 180px; background: linear-gradient(160deg, #14ad0f 0%, #43e97b 100%);">
                        <i class="bi bi-megaphone-fill text-white" style="font-size: 3rem; opacity: 0.3;"></i>
                        <?php if ($a['status'] === 'news'): ?>
                          <span class="position-absolute top-0 end-0 m-2 badge" style="background: linear-gradient(90deg, #14ad0f 0%, #43e97b 100%); font-size: 0.75rem; padding: 0.4rem 0.8rem; border-radius: 20px;">
                            <i class="bi bi-newspaper me-1"></i>News
                          </span>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>
                    <div class="card-body d-flex flex-column p-3">
                      <h6 class="card-title fw-bold mb-2" style="font-size: 1rem; color: #1f2937; line-height: 1.4;">
                        <?= htmlspecialchars($a['title']) ?>
                      </h6>
                      <p class="card-text text-muted mb-3" style="font-size: 0.875rem; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-clamp: 2;">
                        <?= htmlspecialchars(mb_substr(strip_tags($a['content']), 0, 100)) ?><?= mb_strlen($a['content']) > 100 ? '...' : '' ?>
                      </p>
                      <div class="mt-auto">
                        <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                          <i class="bi bi-<?= $ann_action === 'archives' ? 'archive' : 'calendar-event' ?>" style="color: #14ad0f;"></i>
                          <small class="text-muted" style="font-size: 0.8rem;">
                            <?= $ann_action === 'archives' ? "Archived: " . date('M d, Y', strtotime($a['archived_at'])) : "Posted: " . date('M d, Y', strtotime($a['date_posted'])) ?>
                          </small>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                          <button class="btn btn-sm announcement-view-btn flex-fill" style="background: linear-gradient(90deg, #14ad0f 0%, #43e97b 100%); border: none; color: white;" 
                                  data-title="<?= htmlspecialchars($a['title']) ?>"
                                  data-content="<?= htmlspecialchars($a['content']) ?>"
                                  data-image="<?= htmlspecialchars($a['image'] ?? '') ?>"
                                  data-date="<?= $ann_action === 'archives' ? date('M d, Y', strtotime($a['archived_at'])) : date('M d, Y', strtotime($a['date_posted'])) ?>"
                                  data-status="<?= htmlspecialchars($a['status']) ?>"
                                  style="font-size: 0.8rem; border-radius: 8px;">
                            <i class="bi bi-eye me-1"></i>View
                          </button>
                          <?php if ($ann_action === 'archives'): ?>
                            <button class="btn btn-sm btn-success announcement-restore-btn" data-ann-id="<?= htmlspecialchars($a['id']) ?>" style="font-size: 0.8rem; border-radius: 8px;">
                              <i class="bi bi-arrow-counterclockwise"></i>
                            </button>
                            <button class="btn btn-sm btn-danger announcement-delete-permanent-btn" data-ann-id="<?= htmlspecialchars($a['id']) ?>" style="font-size: 0.8rem; border-radius: 8px;">
                              <i class="bi bi-trash"></i>
                            </button>
                          <?php else: ?>
                            <button class="btn btn-sm btn-outline-primary announcement-edit-btn" data-ann-id="<?= htmlspecialchars($a['id']) ?>" style="font-size: 0.8rem; border-radius: 8px;">
                              <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger announcement-delete-btn" data-ann-id="<?= htmlspecialchars($a['id']) ?>" style="font-size: 0.8rem; border-radius: 8px;">
                              <i class="bi bi-trash"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-warning announcement-archive-btn" data-ann-id="<?= htmlspecialchars($a['id']) ?>" style="font-size: 0.8rem; border-radius: 8px;">
                              <i class="bi bi-archive"></i>
                            </button>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        <?php endif; ?>
        </div>
      </div>
      
      <!-- View Announcement Modal -->
      <div class="modal fade" id="viewAnnouncementModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="viewAnnouncementTitle"></h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div id="viewAnnouncementImage" class="text-center mb-3"></div>
              <div id="viewAnnouncementContent" style="white-space: pre-wrap;"></div>
              <hr>
              <small class="text-muted" id="viewAnnouncementDate"></small>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Archive Confirmation Modal -->
      <div class="modal fade" id="archiveAnnouncementModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header bg-warning bg-opacity-10">
              <h5 class="modal-title">
                <i class="bi bi-archive"></i> Archive Announcement
              </h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <p class="mb-0">Are you sure you want to archive this announcement?</p>
              <small class="text-muted">You can restore it later from the Archives section.</small>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="button" class="btn btn-warning" id="confirmArchiveBtn">Archive</button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Restore Confirmation Modal -->
      <div class="modal fade" id="restoreAnnouncementModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header bg-success bg-opacity-10">
              <h5 class="modal-title">
                <i class="bi bi-arrow-counterclockwise"></i> Restore Announcement
              </h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <p class="mb-0">Are you sure you want to restore this announcement?</p>
              <small class="text-muted">This will move it back to the active announcements.</small>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="button" class="btn btn-success" id="confirmRestoreBtn">Restore</button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Delete Announcement Modal (Active) -->
      <div class="modal fade" id="deleteAnnouncementModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-2" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
              <div class="d-flex align-items-center gap-2 w-100">
                <div class="d-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-25" style="width: 40px; height: 40px;">
                  <i class="bi bi-trash-fill text-white fs-5"></i>
                </div>
                <h5 class="modal-title text-white fw-bold mb-0">Delete Announcement</h5>
              </div>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
              <div class="alert alert-danger border-0 d-flex align-items-start gap-2 mb-3" style="background-color: #fee2e2;">
                <i class="bi bi-exclamation-triangle-fill text-danger fs-5 mt-1"></i>
                <div>
                  <strong class="text-danger d-block mb-1">Warning: This action cannot be undone!</strong>
                  <small class="text-danger">This announcement will be permanently deleted from the database.</small>
                </div>
              </div>
              <p class="mb-0 fw-semibold text-secondary">Are you sure you want to delete this announcement?</p>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4">
              <button type="button" class="btn btn-light px-4 py-2 fw-semibold" data-bs-dismiss="modal" style="border: 2px solid #e0e0e0;">
                <i class="bi bi-x-circle me-1"></i>
                Cancel
              </button>
              <button type="button" id="confirmDeleteAnnouncementBtn" class="btn btn-danger px-4 py-2 fw-semibold shadow-sm" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border: none;">
                <i class="bi bi-trash me-1"></i>
                Delete
              </button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Delete Announcement Modal (Permanent - from Archives) -->
      <div class="modal fade" id="deletePermanentAnnouncementModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-2" style="background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);">
              <div class="d-flex align-items-center gap-2 w-100">
                <div class="d-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-25" style="width: 40px; height: 40px;">
                  <i class="bi bi-exclamation-triangle-fill text-white fs-5"></i>
                </div>
                <h5 class="modal-title text-white fw-bold mb-0">Permanently Delete Announcement</h5>
              </div>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
              <div class="alert alert-danger border-0 d-flex align-items-start gap-2 mb-3" style="background-color: #fee2e2;">
                <i class="bi bi-shield-exclamation text-danger fs-5 mt-1"></i>
                <div>
                  <strong class="text-danger d-block mb-1">Warning: This action cannot be undone!</strong>
                  <small class="text-danger">This announcement will be permanently deleted from the database.</small>
                </div>
              </div>
              <p class="mb-0 fw-semibold text-secondary">Are you sure you want to permanently delete this announcement?</p>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4">
              <button type="button" class="btn btn-light px-4 py-2 fw-semibold" data-bs-dismiss="modal" style="border: 2px solid #e0e0e0;">
                <i class="bi bi-x-circle me-1"></i>
                Cancel
              </button>
              <button type="button" id="confirmDeletePermanentBtn" class="btn btn-danger px-4 py-2 fw-semibold shadow-sm" style="background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); border: none;">
                <i class="bi bi-trash me-1"></i>
                Delete Permanently
              </button>
            </div>
          </div>
        </div>
      </div>
      
      <style>
      /* Modern Announcement Panel Styles - Green Theme */
      .announcement-card-modern {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      }
      
      .announcement-card-modern:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(20, 173, 15, 0.2) !important;
      }
      
      .announcement-header-modern {
        transition: all 0.3s ease;
        box-shadow: 0 8px 32px rgba(20,173,15,0.15);
      }
      
      .announcement-icon-wrapper {
        transition: all 0.3s cubic-bezier(.4,0,.2,1);
      }
      
      .announcement-header-modern:hover .announcement-icon-wrapper {
        transform: rotate(10deg) scale(1.1);
        box-shadow: 0 6px 18px rgba(20,173,15,0.3) !important;
      }
      
      .announcement-add-btn {
        transition: all 0.3s cubic-bezier(.4,0,.2,1);
        box-shadow: 0 4px 12px rgba(20,173,15,0.2);
      }
      
      .announcement-add-btn:hover {
        transform: translateY(-2px) scale(1.02);
        box-shadow: 0 8px 20px rgba(20,173,15,0.3) !important;
        background: linear-gradient(90deg, #43e97b 0%, #14ad0f 100%) !important;
      }
      
      .announcement-submit-btn:hover {
        transform: translateY(-2px) scale(1.02);
        box-shadow: 0 8px 20px rgba(20,173,15,0.3) !important;
        background: linear-gradient(90deg, #43e97b 0%, #14ad0f 100%) !important;
      }
      
      .announcement-view-toggle {
        transition: all 0.3s cubic-bezier(.4,0,.2,1);
      }
      
      .announcement-view-toggle:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(255,255,255,0.3);
      }
      
      /* Delete Modal Styles */
      #deleteAnnouncementModal .modal-content,
      #deletePermanentAnnouncementModal .modal-content {
        border-radius: 16px;
        overflow: hidden;
      }
      
      #deleteAnnouncementModal .modal-header,
      #deletePermanentAnnouncementModal .modal-header {
        padding: 1.5rem;
      }
      
      #confirmDeleteAnnouncementBtn:hover,
      #confirmDeletePermanentBtn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4) !important;
        transition: all 0.3s ease;
      }
      
      #deleteAnnouncementModal .btn-light:hover,
      #deletePermanentAnnouncementModal .btn-light:hover {
        background-color: #f8f9fa;
        border-color: #ef4444 !important;
        color: #ef4444;
        transition: all 0.3s ease;
      }
      
      /* Card Image Hover Effect */
      .announcement-card-modern img {
        transition: transform 0.3s ease;
      }
      
      .announcement-card-modern:hover img {
        transform: scale(1.05);
      }
      
      /* Button Hover Effects */
      .announcement-card-modern .btn {
        transition: all 0.2s ease;
      }
      
      .announcement-card-modern .btn:hover {
        transform: scale(1.05);
      }
      </style>
      
      <script>
      // Handle view toggle (active/archives) without page reload for announcements
      function attachAnnouncementToggle() {
        document.querySelectorAll('.announcement-view-toggle').forEach(function(btn) {
          btn.addEventListener('click', function(e) {
            e.preventDefault();
            var targetAction = btn.getAttribute('data-ann-action');
            var currentActiveBtn = document.querySelector('.announcement-view-toggle.active');
            var currentAction = currentActiveBtn ? currentActiveBtn.getAttribute('data-ann-action') : '';
            
            // Don't reload if clicking the same view
            if (targetAction === currentAction) return;
            
            // Update button states
            document.querySelectorAll('.announcement-view-toggle').forEach(function(b) {
              b.classList.remove('active', 'btn-light', 'fw-semibold');
              b.classList.add('btn-outline-light');
            });
            
            btn.classList.remove('btn-outline-light');
            btn.classList.add('btn-light', 'fw-semibold', 'active');
            
            // Update title and subtitle
            var isGoingToArchives = targetAction === 'archives';
            var titleEl = document.querySelector('#announcement-panel-title');
            if (titleEl) {
              titleEl.innerHTML = isGoingToArchives ? 'Archived Announcements' : 'Barangay Announcements';
            }
            
            // Update subtitle
            var subtitleEl = titleEl ? titleEl.nextElementSibling : null;
            if (subtitleEl && subtitleEl.tagName === 'P') {
              subtitleEl.innerHTML = '<i class="bi bi-info-circle me-1"></i>' + 
                (isGoingToArchives ? 'View and manage archived announcements' : 'Manage community announcements and updates');
            }
            
            // Build URL
            var urlParams = new URLSearchParams(window.location.search);
            urlParams.set('panel', 'announcements');
            if (isGoingToArchives) {
              urlParams.set('ann_action', 'archives');
            } else {
              urlParams.delete('ann_action');
            }
            
            // Fetch and update content
            fetch('admin_dashboard.php?' + urlParams.toString(), { 
              method: 'GET', 
              credentials: 'same-origin' 
            })
            .then(function(response) { return response.text(); })
            .then(function(html) {
              // Parse the response
              var parser = new DOMParser();
              var doc = parser.parseFromString(html, 'text/html');
              
              // Update content area
              var newContent = doc.querySelector('#announcement-content-area');
              var currentContent = document.querySelector('#announcement-content-area');
              if (newContent && currentContent) {
                currentContent.innerHTML = newContent.innerHTML;
                // Re-attach event listeners
                attachAnnouncementAddBtn();
                attachAnnouncementEditBtns();
                attachAnnouncementViewBtns();
                attachAnnouncementArchiveBtns();
                attachAnnouncementRestoreBtns();
                attachAnnouncementDeleteBtns();
                attachAnnouncementDeletePermanentBtns();
              }
            })
            .catch(function(error) {
              console.error('Error fetching announcements:', error);
              alert('Error loading data. Please refresh the page.');
            });
          });
        });
      }
      
      // Handle Add Announcement button
      function attachAnnouncementAddBtn() {
        var addBtn = document.querySelector('.announcement-add-btn');
        if (addBtn) {
          addBtn.addEventListener('click', function(e) {
            e.preventDefault();
            loadAnnouncementForm('add');
          });
        }
      }
      
      // Handle Edit buttons
      function attachAnnouncementEditBtns() {
        document.querySelectorAll('.announcement-edit-btn').forEach(function(btn) {
          btn.addEventListener('click', function(e) {
            e.preventDefault();
            var annId = btn.getAttribute('data-ann-id');
            loadAnnouncementForm('edit', annId);
          });
        });
      }
      
      // Handle Cancel button
      function attachAnnouncementCancelBtn() {
        var cancelBtn = document.querySelector('.announcement-cancel-btn');
        if (cancelBtn) {
          cancelBtn.addEventListener('click', function(e) {
            e.preventDefault();
            clearAnnouncementMessage();
            loadAnnouncementList();
          });
        }
      }
      
      // Handle form submission
      function attachAnnouncementFormSubmit() {
        var form = document.querySelector('#announcement-form');
        if (form) {
          form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            var formData = new FormData(form);
            var submitBtn = form.querySelector('button[type="submit"]');
            var originalText = submitBtn.textContent;
            
            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving...';
            
            fetch('admin_dashboard.php?panel=announcements', {
              method: 'POST',
              body: formData,
              credentials: 'same-origin',
              redirect: 'follow'
            })
            .then(function(response) { 
              console.log('Response status:', response.status);
              console.log('Response URL:', response.url);
              return response.text(); 
            })
            .then(function(html) {
              console.log('Response HTML length:', html.length);
              
              // Check if submission was successful by looking for redirect or success
              var parser = new DOMParser();
              var doc = parser.parseFromString(html, 'text/html');
              
              // Check for error in the response (only in content area, not in modals)
              var contentArea = doc.querySelector('#announcement-content-area');
              var errorAlert = contentArea ? contentArea.querySelector('.alert-danger') : null;
              if (errorAlert) {
                console.log('Error found:', errorAlert.textContent.trim());
                showAnnouncementMessage('error', errorAlert.textContent.trim());
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
              } else {
                // Check if we got redirected (URL contains msg parameter)
                var urlParams = new URLSearchParams(window.location.search);
                var hasMsg = html.includes('msg=added') || html.includes('msg=updated');
                
                console.log('Success detected, hasMsg:', hasMsg);
                
                // Success - show message and reload list
                var action = formData.get('ann_action');
                var message = action === 'add' ? 'Announcement posted successfully!' : 'Announcement updated successfully!';
                showAnnouncementMessage('success', message);
                
                // Load the list after a short delay
                setTimeout(function() {
                  loadAnnouncementList();
                }, 1500);
              }
            })
            .catch(function(error) {
              console.error('Error submitting form:', error);
              showAnnouncementMessage('error', 'Error saving announcement. Please try again.');
              submitBtn.disabled = false;
              submitBtn.textContent = originalText;
            });
          });
        }
      }
      
      // Show message
      function showAnnouncementMessage(type, message) {
        var messageArea = document.querySelector('#announcement-message-area');
        if (messageArea) {
          var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
          messageArea.innerHTML = '<div class="alert ' + alertClass + ' text-center">' + message + '</div>';
          
          // Auto-hide success messages after 3 seconds
          if (type === 'success') {
            setTimeout(function() {
              clearAnnouncementMessage();
            }, 3000);
          }
        }
      }
      
      // Clear message
      function clearAnnouncementMessage() {
        var messageArea = document.querySelector('#announcement-message-area');
        if (messageArea) {
          messageArea.innerHTML = '';
        }
      }
      
      // Load announcement form (add or edit)
      function loadAnnouncementForm(action, annId) {
        var urlParams = new URLSearchParams();
        urlParams.set('panel', 'announcements');
        urlParams.set('ann_action', action);
        if (annId) {
          urlParams.set('ann_id', annId);
        }
        
        fetch('admin_dashboard.php?' + urlParams.toString(), { 
          method: 'GET', 
          credentials: 'same-origin' 
        })
        .then(function(response) { return response.text(); })
        .then(function(html) {
          var parser = new DOMParser();
          var doc = parser.parseFromString(html, 'text/html');
          
          // Update content area
          var newContent = doc.querySelector('#announcement-content-area');
          var currentContent = document.querySelector('#announcement-content-area');
          if (newContent && currentContent) {
            currentContent.innerHTML = newContent.innerHTML;
            attachAnnouncementCancelBtn();
            attachAnnouncementFormSubmit();
          }
        })
        .catch(function(error) {
          console.error('Error loading form:', error);
          alert('Error loading form. Please refresh the page.');
        });
      }
      
      // Load announcement list
      function loadAnnouncementList() {
        var urlParams = new URLSearchParams();
        urlParams.set('panel', 'announcements');
        
        fetch('admin_dashboard.php?' + urlParams.toString(), { 
          method: 'GET', 
          credentials: 'same-origin' 
        })
        .then(function(response) { return response.text(); })
        .then(function(html) {
          var parser = new DOMParser();
          var doc = parser.parseFromString(html, 'text/html');
          
          // Update title
          var titleEl = document.querySelector('#announcement-panel-title');
          if (titleEl) {
            titleEl.innerHTML = '📢 Barangay Announcements';
          }
          
          // Update button states to Active
          document.querySelectorAll('.announcement-view-toggle').forEach(function(b) {
            b.classList.remove('active');
            if (b.getAttribute('data-ann-action') === '') {
              b.classList.remove('btn-outline-primary');
              b.classList.add('btn-primary', 'active');
            } else {
              b.classList.remove('btn-secondary');
              b.classList.add('btn-outline-secondary');
            }
          });
          
          // Update content area
          var newContent = doc.querySelector('#announcement-content-area');
          var currentContent = document.querySelector('#announcement-content-area');
          if (newContent && currentContent) {
            currentContent.innerHTML = newContent.innerHTML;
            attachAnnouncementAddBtn();
            attachAnnouncementEditBtns();
            attachAnnouncementViewBtns();
            attachAnnouncementArchiveBtns();
            attachAnnouncementRestoreBtns();
            attachAnnouncementDeleteBtns();
            attachAnnouncementDeletePermanentBtns();
          }
        })
        .catch(function(error) {
          console.error('Error loading list:', error);
          alert('Error loading list. Please refresh the page.');
        });
      }
      
      // Handle Archive buttons
      var currentArchiveBtn = null;
      function attachAnnouncementArchiveBtns() {
        document.querySelectorAll('.announcement-archive-btn').forEach(function(btn) {
          btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Store reference to the clicked button
            currentArchiveBtn = btn;
            
            // Show modal
            var modal = new bootstrap.Modal(document.getElementById('archiveAnnouncementModal'));
            modal.show();
          });
        });
      }
      
      // Handle Archive confirmation
      document.getElementById('confirmArchiveBtn').addEventListener('click', function() {
        if (!currentArchiveBtn) return;
        
        var annId = currentArchiveBtn.getAttribute('data-ann-id');
        var originalText = currentArchiveBtn.textContent;
        
        // Close modal
        var modal = bootstrap.Modal.getInstance(document.getElementById('archiveAnnouncementModal'));
        modal.hide();
        
        // Disable button
        currentArchiveBtn.disabled = true;
        currentArchiveBtn.textContent = 'Archiving...';
        
        fetch('admin_dashboard.php?panel=announcements&ann_action=archive&ann_id=' + encodeURIComponent(annId), {
          method: 'GET',
          credentials: 'same-origin',
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
          if (data.success) {
            showAnnouncementMessage('success', data.message);
            // Reload active announcements list after a short delay
            setTimeout(function() {
              // Reload the active announcements view
              var urlParams = new URLSearchParams(window.location.search);
              urlParams.set('panel', 'announcements');
              urlParams.delete('ann_action');
              
              fetch('admin_dashboard.php?' + urlParams.toString(), { 
                method: 'GET', 
                credentials: 'same-origin' 
              })
              .then(function(response) { return response.text(); })
              .then(function(html) {
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                
                var newContent = doc.querySelector('#announcement-content-area');
                var currentContent = document.querySelector('#announcement-content-area');
                if (newContent && currentContent) {
                  currentContent.innerHTML = newContent.innerHTML;
                  attachAnnouncementAddBtn();
                  attachAnnouncementEditBtns();
                  attachAnnouncementViewBtns();
                  attachAnnouncementArchiveBtns();
                }
              })
              .catch(function(error) {
                console.error('Error reloading announcements:', error);
              });
            }, 1500);
          } else {
            showAnnouncementMessage('error', data.message || 'Error archiving announcement.');
            currentArchiveBtn.disabled = false;
            currentArchiveBtn.textContent = originalText;
          }
        })
        .catch(function(error) {
          console.error('Error archiving announcement:', error);
          showAnnouncementMessage('error', 'Error archiving announcement. Please try again.');
          currentArchiveBtn.disabled = false;
          currentArchiveBtn.textContent = originalText;
        });
        
        currentArchiveBtn = null;
      });
      
      // Handle Restore buttons
      var currentRestoreBtn = null;
      function attachAnnouncementRestoreBtns() {
        document.querySelectorAll('.announcement-restore-btn').forEach(function(btn) {
          btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Store reference to the clicked button
            currentRestoreBtn = btn;
            
            // Show modal
            var modal = new bootstrap.Modal(document.getElementById('restoreAnnouncementModal'));
            modal.show();
          });
        });
      }
      
      // Handle Restore confirmation
      document.getElementById('confirmRestoreBtn').addEventListener('click', function() {
        if (!currentRestoreBtn) return;
        
        var annId = currentRestoreBtn.getAttribute('data-ann-id');
        var originalText = currentRestoreBtn.textContent;
        
        // Close modal
        var modal = bootstrap.Modal.getInstance(document.getElementById('restoreAnnouncementModal'));
        modal.hide();
        
        // Disable button
        currentRestoreBtn.disabled = true;
        currentRestoreBtn.textContent = 'Restoring...';
        
        fetch('admin_dashboard.php?panel=announcements&ann_action=restore&ann_id=' + encodeURIComponent(annId), {
          method: 'GET',
          credentials: 'same-origin',
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
          if (data.success) {
            showAnnouncementMessage('success', data.message);
            // Reload archives list after a short delay
            setTimeout(function() {
              // Reload the archives view
              var urlParams = new URLSearchParams(window.location.search);
              urlParams.set('panel', 'announcements');
              urlParams.set('ann_action', 'archives');
              
              fetch('admin_dashboard.php?' + urlParams.toString(), { 
                method: 'GET', 
                credentials: 'same-origin' 
              })
              .then(function(response) { return response.text(); })
              .then(function(html) {
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                
                var newContent = doc.querySelector('#announcement-content-area');
                var currentContent = document.querySelector('#announcement-content-area');
                if (newContent && currentContent) {
                  currentContent.innerHTML = newContent.innerHTML;
                  attachAnnouncementAddBtn();
                  attachAnnouncementEditBtns();
                  attachAnnouncementViewBtns();
                  attachAnnouncementArchiveBtns();
                  attachAnnouncementRestoreBtns();
                }
              })
              .catch(function(error) {
                console.error('Error reloading archives:', error);
              });
            }, 1500);
          } else {
            showAnnouncementMessage('error', data.message || 'Error restoring announcement.');
            currentRestoreBtn.disabled = false;
            currentRestoreBtn.textContent = originalText;
          }
        })
        .catch(function(error) {
          console.error('Error restoring announcement:', error);
          showAnnouncementMessage('error', 'Error restoring announcement. Please try again.');
          currentRestoreBtn.disabled = false;
          currentRestoreBtn.textContent = originalText;
        });
        
        currentRestoreBtn = null;
      });
      
      // Handle Delete buttons (Active announcements - move to archive)
      var currentDeleteBtn = null;
      function attachAnnouncementDeleteBtns() {
        var deleteBtns = document.querySelectorAll('.announcement-delete-btn');
        console.log('Found delete buttons:', deleteBtns.length);
        
        deleteBtns.forEach(function(btn) {
          btn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Delete button clicked');
            
            // Store reference to the clicked button
            currentDeleteBtn = btn;
            
            // Show modal
            var modalEl = document.getElementById('deleteAnnouncementModal');
            console.log('Modal element:', modalEl);
            
            if (modalEl) {
              var modal = new bootstrap.Modal(modalEl);
              modal.show();
            } else {
              console.error('Delete modal not found!');
            }
          });
        });
      }
      
      // Handle Delete confirmation (Active)
      document.getElementById('confirmDeleteAnnouncementBtn').addEventListener('click', function() {
        if (!currentDeleteBtn) return;
        
        var annId = currentDeleteBtn.getAttribute('data-ann-id');
        var originalText = currentDeleteBtn.textContent;
        
        // Close modal
        var modal = bootstrap.Modal.getInstance(document.getElementById('deleteAnnouncementModal'));
        modal.hide();
        
        // Disable button
        currentDeleteBtn.disabled = true;
        currentDeleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        
        fetch('admin_dashboard.php?panel=announcements&ann_action=delete&ann_id=' + encodeURIComponent(annId), {
          method: 'GET',
          credentials: 'same-origin',
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
          if (data.success) {
            showAnnouncementMessage('success', data.message);
            // Reload active announcements list after a short delay
            setTimeout(function() {
              var urlParams = new URLSearchParams(window.location.search);
              urlParams.set('panel', 'announcements');
              urlParams.delete('ann_action');
              
              fetch('admin_dashboard.php?' + urlParams.toString(), { 
                method: 'GET', 
                credentials: 'same-origin' 
              })
              .then(function(response) { return response.text(); })
              .then(function(html) {
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                
                var newContent = doc.querySelector('#announcement-content-area');
                var currentContent = document.querySelector('#announcement-content-area');
                if (newContent && currentContent) {
                  currentContent.innerHTML = newContent.innerHTML;
                  attachAnnouncementAddBtn();
                  attachAnnouncementEditBtns();
                  attachAnnouncementViewBtns();
                  attachAnnouncementArchiveBtns();
                  attachAnnouncementDeleteBtns();
                }
              })
              .catch(function(error) {
                console.error('Error reloading announcements:', error);
              });
            }, 1500);
          } else {
            showAnnouncementMessage('error', data.message || 'Error deleting announcement.');
            currentDeleteBtn.disabled = false;
            currentDeleteBtn.innerHTML = originalText;
          }
        })
        .catch(function(error) {
          console.error('Error deleting announcement:', error);
          showAnnouncementMessage('error', 'Error deleting announcement. Please try again.');
          currentDeleteBtn.disabled = false;
          currentDeleteBtn.innerHTML = originalText;
        });
        
        currentDeleteBtn = null;
      });
      
      // Handle Delete Permanent buttons (Archived announcements)
      var currentDeletePermanentBtn = null;
      function attachAnnouncementDeletePermanentBtns() {
        var deletePermanentBtns = document.querySelectorAll('.announcement-delete-permanent-btn');
        console.log('Found delete permanent buttons:', deletePermanentBtns.length);
        
        deletePermanentBtns.forEach(function(btn) {
          btn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Delete permanent button clicked');
            
            // Store reference to the clicked button
            currentDeletePermanentBtn = btn;
            
            // Show modal
            var modalEl = document.getElementById('deletePermanentAnnouncementModal');
            console.log('Permanent delete modal element:', modalEl);
            
            if (modalEl) {
              var modal = new bootstrap.Modal(modalEl);
              modal.show();
            } else {
              console.error('Delete permanent modal not found!');
            }
          });
        });
      }
      
      // Handle Delete Permanent confirmation
      document.getElementById('confirmDeletePermanentBtn').addEventListener('click', function() {
        if (!currentDeletePermanentBtn) return;
        
        var annId = currentDeletePermanentBtn.getAttribute('data-ann-id');
        var originalText = currentDeletePermanentBtn.textContent;
        
        // Close modal
        var modal = bootstrap.Modal.getInstance(document.getElementById('deletePermanentAnnouncementModal'));
        modal.hide();
        
        // Disable button
        currentDeletePermanentBtn.disabled = true;
        currentDeletePermanentBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        
        fetch('admin_dashboard.php?panel=announcements&ann_action=delete_archive&ann_id=' + encodeURIComponent(annId), {
          method: 'GET',
          credentials: 'same-origin',
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
          if (data.success) {
            showAnnouncementMessage('success', data.message);
            // Reload archives list after a short delay
            setTimeout(function() {
              var urlParams = new URLSearchParams(window.location.search);
              urlParams.set('panel', 'announcements');
              urlParams.set('ann_action', 'archives');
              
              fetch('admin_dashboard.php?' + urlParams.toString(), { 
                method: 'GET', 
                credentials: 'same-origin' 
              })
              .then(function(response) { return response.text(); })
              .then(function(html) {
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                
                var newContent = doc.querySelector('#announcement-content-area');
                var currentContent = document.querySelector('#announcement-content-area');
                if (newContent && currentContent) {
                  currentContent.innerHTML = newContent.innerHTML;
                  attachAnnouncementAddBtn();
                  attachAnnouncementEditBtns();
                  attachAnnouncementViewBtns();
                  attachAnnouncementRestoreBtns();
                  attachAnnouncementDeletePermanentBtns();
                }
              })
              .catch(function(error) {
                console.error('Error reloading archives:', error);
              });
            }, 1500);
          } else {
            showAnnouncementMessage('error', data.message || 'Error deleting announcement permanently.');
            currentDeletePermanentBtn.disabled = false;
            currentDeletePermanentBtn.innerHTML = originalText;
          }
        })
        .catch(function(error) {
          console.error('Error deleting announcement permanently:', error);
          showAnnouncementMessage('error', 'Error deleting announcement. Please try again.');
          currentDeletePermanentBtn.disabled = false;
          currentDeletePermanentBtn.innerHTML = originalText;
        });
        
        currentDeletePermanentBtn = null;
      });
      
      // Handle View buttons
      function attachAnnouncementViewBtns() {
        document.querySelectorAll('.announcement-view-btn').forEach(function(btn) {
          btn.addEventListener('click', function(e) {
            e.preventDefault();
            var title = btn.getAttribute('data-title');
            var content = btn.getAttribute('data-content');
            var image = btn.getAttribute('data-image');
            var date = btn.getAttribute('data-date');
            var status = btn.getAttribute('data-status');
            
            // Set modal content
            document.getElementById('viewAnnouncementTitle').textContent = title;
            if (status === 'news') {
              document.getElementById('viewAnnouncementTitle').innerHTML = title + ' <span class="badge bg-success">News</span>';
            }
            
            document.getElementById('viewAnnouncementContent').textContent = content;
            document.getElementById('viewAnnouncementDate').textContent = 'Posted: ' + date;
            
            // Set image if exists
            var imageContainer = document.getElementById('viewAnnouncementImage');
            if (image) {
              imageContainer.innerHTML = '<img src="uploads/' + image + '" class="img-fluid" style="max-height: 400px;">';
            } else {
              imageContainer.innerHTML = '';
            }
            
            // Show modal
            var modal = new bootstrap.Modal(document.getElementById('viewAnnouncementModal'));
            modal.show();
          });
        });
      }
      
      // Initial attachment - wrap in DOMContentLoaded to ensure elements exist
      document.addEventListener('DOMContentLoaded', function() {
        attachAnnouncementToggle();
        attachAnnouncementAddBtn();
        attachAnnouncementEditBtns();
        attachAnnouncementCancelBtn();
        attachAnnouncementFormSubmit();
        attachAnnouncementViewBtns();
        attachAnnouncementArchiveBtns();
        attachAnnouncementRestoreBtns();
        attachAnnouncementDeleteBtns();
        attachAnnouncementDeletePermanentBtns();
      });
          </script>
      </div>
  <!-- Certificates Panel (hidden by default) -->
    <!-- Incidents Panel (hidden by default) -->
    <div id="panel-incidents" class="panel-section" style="display:none;">
      <style>
        /* Make incident table text smaller for better fit */
        #panel-incidents #incidentTable th, #panel-incidents #incidentTable td {
          font-size: 13px;
          padding: 6px 8px;
        }
        /* Row color coding by incident_type (keep in sync with styles.css) */
        table#incidentTable tbody tr.incident-urgent,
        table#incidentTable tbody tr.incident-urgent td {
          background-color: #ffcccc !important;
          border-left: 4px solid #e53935 !important;
        }
        table#incidentTable tbody tr.incident-moderate,
        table#incidentTable tbody tr.incident-moderate td {
          background-color: #ffe5b4 !important;
          border-left: 4px solid #fb8c00 !important;
        }
        table#incidentTable tbody tr.incident-minor,
        table#incidentTable tbody tr.incident-minor td {
          background-color: #ffffcc !important;
          border-left: 4px solid #fbc02d !important;
        }
      </style>
      <?php
      // --- Begin incident_reports.php logic (adapted for dashboard panel) ---
      include 'config.php';
      $admin_username = $_SESSION['admin_username'] ?? 'Unknown';
      $iview = $_GET['iview'] ?? 'active';
      $isearch = $_GET['isearch'] ?? '';
      $istatus = $_GET['istatus'] ?? '';
      $istart_date = $_GET['istart_date'] ?? '';
      $iend_date   = $_GET['iend_date'] ?? '';

      // --- Block/Unblock User from Incident Reports ---
      if (isset($_GET['block_incident_user'])) {
          $userid = intval($_GET['block_incident_user']);
          $conn->query("UPDATE residents SET can_submit_incidents = 0 WHERE unique_id = '$userid'");
          $action_log = "Blocked resident ID $userid from submitting incident reports";
          $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
          $log->bind_param("ss", $admin_username, $action_log);
          $log->execute();
          $log->close();
          echo "<div class='alert alert-success text-center'>User has been blocked from submitting incident reports.</div>";
          echo "<script>setTimeout(()=>location.href='admin_dashboard.php?panel=incidents',1200);</script>";
          exit;
      }
      if (isset($_GET['unblock_incident_user'])) {
          $userid = intval($_GET['unblock_incident_user']);
          $conn->query("UPDATE residents SET can_submit_incidents = 1 WHERE unique_id = '$userid'");
          $action_log = "Unblocked resident ID $userid from submitting incident reports";
          $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
          $log->bind_param("ss", $admin_username, $action_log);
          $log->execute();
          $log->close();
          echo "<div class='alert alert-success text-center'>User has been unblocked from submitting incident reports.</div>";
          echo "<script>setTimeout(()=>location.href='admin_dashboard.php?panel=incidents',1200);</script>";
          exit;
      }

      // --- Update Status (with archive on Resolved) ---
      if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['iupdate_status'])) {
          $id = intval($_POST['iid']);
          $status = $_POST['istatus'];
          if ($status === "Resolved") {
              $check = $conn->prepare("SELECT * FROM incident_reports WHERE id = ?");
              $check->bind_param("i", $id);
              $check->execute();
              $incident = $check->get_result()->fetch_assoc();
              $check->close();
              if ($incident) {
                  $conn->begin_transaction();
                  try {
                      $insert = $conn->prepare("
                          INSERT INTO archived_incident_reports 
                              (userid, incident_type, contact_number, incident_description, incident_image, created_at, status, date_ended, seen)
                          VALUES (?, ?, ?, ?, ?, ?, 'Resolved', NOW(), ?)
                      ");
                      if (!$insert) throw new Exception("Prepare failed: " . $conn->error);
                      $insert->bind_param(
                          "isssssi",
                          $incident['userid'],
                          $incident['incident_type'],
                          $incident['contact_number'],
                          $incident['incident_description'],
                          $incident['incident_image'],
                          $incident['created_at'],
                          $incident['seen']
                      );
                      if (!$insert->execute()) throw new Exception("Insert failed: " . $insert->error);
                      $insert->close();
                      $delete = $conn->prepare("DELETE FROM incident_reports WHERE id = ?");
                      $delete->bind_param("i", $id);
                      $delete->execute();
                      $delete->close();
                      $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
                      $logText = "Archived resolved incident report ID $id";
                      $log->bind_param("ss", $admin_username, $logText);
                      $log->execute();
                      $log->close();
                      $conn->commit();
                  } catch (Exception $e) {
                      $conn->rollback();
                      die("❌ Archiving failed: " . $e->getMessage());
                  }
              } else {
                  die("❌ Incident not found. Cannot archive.");
              }
          } else {
              $update = $conn->prepare("UPDATE incident_reports SET status = ? WHERE id = ?");
              $update->bind_param("si", $status, $id);
              $update->execute();
              $update->close();
          }
          $redirectUrl = basename($_SERVER['PHP_SELF']) . '?' . http_build_query(array_merge($_GET, ["panel"=>"incidents"]));
          header("Location: $redirectUrl");
          exit;
      }

      // --- Delete Report (from active or archived) ---
      if (isset($_GET['idelete'])) {
          $id = intval($_GET['idelete']);
          if ($iview === 'archived') {
              $stmt = $conn->prepare("SELECT incident_type FROM archived_incident_reports WHERE id=?");
              $stmt->bind_param("i", $id);
              $stmt->execute();
              $row = $stmt->get_result()->fetch_assoc();
              $stmt->close();
              $stmtDel = $conn->prepare("DELETE FROM archived_incident_reports WHERE id=?");
              $stmtDel->bind_param("i", $id);
              if ($stmtDel->execute()) {
                  $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
                  $logText = "Deleted archived_incident_report ID $id (Type: {$row['incident_type']})";
                  $log->bind_param("ss", $admin_username, $logText);
                  $log->execute();
                  $log->close();
                  echo "<div class='alert alert-success text-center'>Archived incident report deleted.</div>";
                  echo "<script>setTimeout(()=>location.href='admin_dashboard.php?panel=incidents&iview=archived',1200);</script>";
                  exit;
              }
              $stmtDel->close();
          } else {
              $stmt = $conn->prepare("SELECT incident_type FROM incident_reports WHERE id=?");
              $stmt->bind_param("i", $id);
              $stmt->execute();
              $row = $stmt->get_result()->fetch_assoc();
              $stmt->close();
              $stmtDel = $conn->prepare("DELETE FROM incident_reports WHERE id=?");
              $stmtDel->bind_param("i", $id);
              if ($stmtDel->execute()) {
                  $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
                  $logText = "Deleted incident_report ID $id (Type: {$row['incident_type']})";
                  $log->bind_param("ss", $admin_username, $logText);
                  $log->execute();
                  $log->close();
                  echo "<div class='alert alert-success text-center'>Incident report deleted.</div>";
                  echo "<script>setTimeout(()=>location.href='admin_dashboard.php?panel=incidents',1200);</script>";
                  exit;
              }
              $stmtDel->close();
          }
      }

      // --- Build SQL depending on view (active or archived) ---
      if ($iview === 'archived') {
          $isql = "SELECT * FROM archived_incident_reports WHERE 1=1";
      } else {
          $isql = "SELECT * FROM incident_reports WHERE 1=1";
      }
      $iparams = [];
      $itypes = "";
      if (!empty($isearch)) {
          $isql .= " AND (id LIKE ? OR incident_type LIKE ? OR incident_description LIKE ?)";
          $like = "%$isearch%";
          $iparams = array_merge($iparams, [$like, $like, $like]);
          $itypes .= "sss";
      }
      if (!empty($istatus)) {
          $isql .= " AND status = ?";
          $iparams[] = $istatus;
          $itypes .= "s";
      }
      if (!empty($istart_date) && !empty($iend_date)) {
          $isql .= " AND DATE(created_at) BETWEEN ? AND ?";
          $iparams[] = $istart_date;
          $iparams[] = $iend_date;
          $itypes .= "ss";
      } elseif (!empty($istart_date)) {
          $isql .= " AND DATE(created_at) >= ?";
          $iparams[] = $istart_date;
          $itypes .= "s";
      } elseif (!empty($iend_date)) {
          $isql .= " AND DATE(created_at) <= ?";
          $iparams[] = $iend_date;
          $itypes .= "s";
      }
      $isql .= " ORDER BY created_at DESC";
      $istmt = $conn->prepare($isql);
      if (!$istmt) die("❌ SQL Prepare failed (Select): " . $conn->error);
      if (!empty($iparams)) {
          $istmt->bind_param($itypes, ...$iparams);
      }
      $istmt->execute();
      $iresult = $istmt->get_result();
      
      // Count Pending and In Review statuses for active view
      $pendingCount = 0;
      $inReviewCount = 0;
      if ($iview === 'active') {
        $countSql = "SELECT status, COUNT(*) as count FROM incident_reports GROUP BY status";
        $countResult = $conn->query($countSql);
        while ($countRow = $countResult->fetch_assoc()) {
          if ($countRow['status'] === 'Pending') {
            $pendingCount = $countRow['count'];
          } elseif ($countRow['status'] === 'In Review' || $countRow['status'] === 'Priority') {
            $inReviewCount += $countRow['count'];
          }
        }
      }
      ?>
      <div class="container-fluid py-4">
        <!-- Modern Header with Gradient -->
        <div class="incident-header-modern mb-4 p-4 rounded-4 shadow-sm" style="background: linear-gradient(160deg, #14ad0f 0%, #43e97b 100%);">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
              <div class="incident-icon-wrapper d-flex align-items-center justify-content-center rounded-circle" style="width: 60px; height: 60px; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); box-shadow: 0 4px 12px rgba(20,173,15,0.2);">
                <i class="bi bi-exclamation-triangle-fill text-white" style="font-size: 1.8rem; filter: drop-shadow(0 2px 4px rgba(20,173,15,0.3));"></i>
              </div>
              <div>
                <h2 class="fw-bold text-white mb-1" id="incident-panel-title" style="font-size: 1.75rem; text-shadow: 0 2px 8px rgba(20,173,15,0.3); letter-spacing: 0.5px;">
                  <?= $iview === 'archived' ? 'Archived Incident Reports' : 'Manage Incident Reports' ?>
                </h2>
                <p class="text-white mb-0" style="font-size: 0.9rem; opacity: 0.9;">
                  <i class="bi bi-info-circle me-1"></i>
                  <?= $iview === 'archived' ? 'View and manage archived incident reports' : 'Track and manage incident reports efficiently' ?>
                </p>
              </div>
            </div>
            <div class="d-flex gap-2">
              <div class="btn-group shadow-sm" role="group">
                <button class="btn incident-view-toggle <?= $iview === 'active' ? 'btn-light fw-semibold active' : 'btn-outline-light' ?>" data-iview="active" style="min-width: 100px; border-radius: 12px 0 0 12px;">
                  <i class="bi bi-exclamation-circle me-1"></i> Active
                </button>
                <button class="btn incident-view-toggle <?= $iview === 'archived' ? 'btn-light fw-semibold active' : 'btn-outline-light' ?>" data-iview="archived" style="min-width: 100px; border-radius: 0 12px 12px 0;">
                  <i class="bi bi-archive me-1"></i> Archives
                </button>
              </div>
              <button class="btn btn-outline-light shadow-sm fw-semibold" id="view-blocked-users-btn" style="border-radius: 12px; min-width: 150px;">
                <i class="bi bi-person-x me-1"></i> Blocked Users
              </button>
            </div>
          </div>
        </div>

        <style>
          .incident-view-toggle:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(20,173,15,0.15) !important;
          }
          #incident-apply-btn:hover {
            background: linear-gradient(90deg,#43e97b 0%,#14ad0f 100%) !important;
            box-shadow: 0 6px 18px rgba(20,173,15,0.18) !important;
            transform: scale(1.05);
          }
        </style>

        <div class="card p-4">
          <?php if ($iview === 'active'): ?>
          <div class="mb-3 d-flex gap-3 justify-content-center align-items-center" style="font-size:1.1em;" id="incident-counts">
            <span class="fw-bold text-warning">⏳ Pending: <span class="badge bg-warning text-dark"><?= $pendingCount ?></span></span>
            <span class="fw-bold text-info">🔍 In Review: <span class="badge bg-info text-dark"><?= $inReviewCount ?></span></span>
          </div>
          <?php endif; ?>
          <form method="GET" class="row g-2 mb-4" action="admin_dashboard.php" id="incident-search-form">
            <input type="hidden" name="panel" value="incidents">
            <input type="hidden" name="iview" value="<?= htmlspecialchars($iview) ?>">
            <div class="col-md-3">
              <input type="text" class="form-control" name="isearch" placeholder="Search ID, type, or description" value="<?= htmlspecialchars($isearch) ?>" id="incident-search-input">
            </div>
            <div class="col-md-2">
              <select name="istatus" class="form-select" id="incident-status-select">
                <option value="">All Status</option>
                <option value="Pending" <?= ($istatus=="Pending") ? "selected" : "" ?>>Pending</option>
                <option value="In Review" <?= ($istatus=="In Review") ? "selected" : "" ?>>In Review</option>
                <option value="Resolved" <?= ($istatus=="Resolved") ? "selected" : "" ?>>Resolved</option>
              </select>
            </div>
            <div class="col-md-2">
              <input type="date" class="form-control" name="istart_date" value="<?= htmlspecialchars($istart_date) ?>" id="incident-start-date">
            </div>
            <div class="col-md-2">
              <input type="date" class="form-control" name="iend_date" value="<?= htmlspecialchars($iend_date) ?>" id="incident-end-date">
            </div>
            <div class="col-md-3 d-grid gap-2 d-md-flex">
              <button type="submit" class="btn" id="incident-apply-btn" style="background: linear-gradient(90deg,#14ad0f 0%,#43e97b 100%); color: #fff; border: none; padding: 10px 20px; border-radius: 12px; font-weight: 600; box-shadow: 0 2px 12px rgba(20,173,15,0.13); transition: all 0.22s;"><i class="bi bi-funnel"></i> Apply</button>
              <button type="button" class="btn btn-outline-secondary" id="incident-reset-btn" style="border: 2px solid #6c757d; border-radius: 10px; font-weight: 600; transition: all 0.22s;">Reset</button>
            </div>
          </form>
         
          <div class="table-responsive" style="max-height: 500px; overflow-y: auto;" id="incident-table-container">
  <table class="table table-hover align-middle" id="incidentTable">
    <thead class="table-light sticky-top">
        <tr>
        <th>User ID</th>
        <th>Type</th>
        <th>Contact Number</th>
        <th>Photo</th>
        <th>Description</th>
        <?php if ($iview === 'archived'): ?>
        <th>Comment</th>
        <?php endif; ?>
        <th>Date Submitted</th>
        <th>Status</th>
        <?php if ($iview === 'archived'): ?>
        <th>Date Ended</th>
        <?php endif; ?>
        <th class="text-center">Actions</th>
      </tr>
    </thead>
    <tbody id="incidentTableBody">
      <?php include 'incident_table_partial.php'; ?>
    </tbody>
  </table>
</div>

          <script>
          // Global variables for delete incident tracking
          var currentDeleteIncidentBtn = null;
          var currentDeleteIncidentId = null;
          var currentDeleteIncidentArchived = null;
          
          // AJAX status change
          function reloadIncidentTable() {
            const params = new URLSearchParams(window.location.search);
            params.set('action', 'get_table');
            
            // Get current view from active button
            var currentViewBtn = document.querySelector('.incident-view-toggle.active');
            var currentView = currentViewBtn ? currentViewBtn.getAttribute('data-iview') : 'active';
            params.set('iview', currentView);
            
            console.log('Reloading incident table with params:', params.toString());
            
            fetch('incident_ajax.php?' + params.toString(), { credentials: 'same-origin' })
              .then(r => r.json())
              .then(data => {
                console.log('Table reload response:', data);
                if (data.success) {
                  document.getElementById('incidentTableBody').innerHTML = data.html;
                  attachIncidentHandlers();
                }
              })
              .catch(error => {
                console.error('Error reloading table:', error);
              });
          }
          
          function attachIncidentHandlers() {
            document.querySelectorAll('.incident-status-select').forEach(function(sel) {
              // keep track of previous value so we can revert if admin cancels the comment modal
              sel.dataset.prev = sel.value;
              sel.onchange = function() {
                const id = this.getAttribute('data-id');
                const status = this.value;
                // If resolving, open modal to collect admin comment
                if (status === 'Resolved') {
                  // store current select element and id for modal handler
                  window.__resolvePending = { id: id, selectEl: this };
                  const modalEl = new bootstrap.Modal(document.getElementById('resolveModal'));
                  document.getElementById('resolveModalTextarea').value = '';
                  document.getElementById('resolveModalCharCount').textContent = '0/1000';
                  modalEl.show();
                } else {
                  // simple update for other statuses
                  fetch('incident_ajax.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=update_status&id=' + encodeURIComponent(id) + '&status=' + encodeURIComponent(status)
                  })
                  .then(r => r.json())
                  .then(data => { if (data.success) reloadIncidentTable(); else { alert('Failed to update status'); this.value = this.dataset.prev; } });
                }
              };
            });
            
            // Attach delete button handlers
            document.querySelectorAll('.incident-delete-btn').forEach(function(btn) {
              btn.onclick = function() {
                // Store references in global variables
                currentDeleteIncidentBtn = btn;
                currentDeleteIncidentId = btn.getAttribute('data-id');
                currentDeleteIncidentArchived = btn.getAttribute('data-archived');
                
                // Show modal
                var modal = new bootstrap.Modal(document.getElementById('deleteIncidentModal'));
                modal.show();
              };
            });
            
            // Attach block button handlers for incidents
            document.querySelectorAll('.incident-block-btn').forEach(function(btn) {
              btn.onclick = function() {
                const userid = btn.getAttribute('data-userid');
                window.currentBlockIncidentUserId = userid;
                const modal = new bootstrap.Modal(document.getElementById('blockIncidentUserModal'));
                modal.show();
              };
            });
          }
          
          // Initial attach
          attachIncidentHandlers();
          
          // Handle "Blocked Users" button click
          document.getElementById('view-blocked-users-btn').addEventListener('click', function() {
            // Show blocked users modal
            var modal = new bootstrap.Modal(document.getElementById('blockedUsersModal'));
            
            // Load blocked users
            fetch('admin_dashboard.php?panel=incidents&action=get_blocked_users', {
              method: 'GET',
              credentials: 'same-origin'
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
              if (data.success) {
                var tbody = document.getElementById('blockedUsersTableBody');
                tbody.innerHTML = data.html;
                
                // Attach unblock handlers
                document.querySelectorAll('.unblock-user-from-modal-btn').forEach(function(btn) {
                  btn.addEventListener('click', function() {
                    var userid = btn.getAttribute('data-userid');
                    window.currentUnblockIncidentUserId = userid;
                    
                    // Close blocked users modal first
                    var blockedModal = bootstrap.Modal.getInstance(document.getElementById('blockedUsersModal'));
                    if (blockedModal) blockedModal.hide();
                    
                    // Show unblock confirmation modal
                    var unblockModal = new bootstrap.Modal(document.getElementById('unblockIncidentUserModal'));
                    unblockModal.show();
                  });
                });
              } else {
                alert('Error loading blocked users');
              }
            })
            .catch(function(error) {
              console.error('Error:', error);
              alert('Error loading blocked users');
            });
            
            modal.show();
          });
          </script>
          <!-- Resolve comment modal -->
          <div class="modal fade" id="resolveModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 pb-2" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                  <div class="d-flex align-items-center gap-2 w-100">
                    <div class="d-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-25" style="width: 40px; height: 40px;">
                      <i class="bi bi-check-circle-fill text-white fs-5"></i>
                    </div>
                    <h5 class="modal-title text-white fw-bold mb-0">Add Resolution Comment</h5>
                  </div>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                  <div class="mb-3">
                    <label for="resolveModalTextarea" class="form-label fw-semibold text-secondary">
                      <i class="bi bi-chat-left-text me-1"></i>
                      Resolution Details
                    </label>
                    <p class="text-muted small mb-2">
                      Please explain briefly how the incident was resolved. <span class="text-danger">*</span>
                    </p>
                    <div class="position-relative">
                      <textarea 
                        id="resolveModalTextarea" 
                        class="form-control border-2 shadow-sm" 
                        rows="5" 
                        maxlength="1000"
                        placeholder="Describe the actions taken to resolve this incident..."
                        style="resize: none; border-color: #e0e0e0; transition: all 0.3s ease;"
                      ></textarea>
                      <div class="position-absolute bottom-0 end-0 p-2">
                        <span id="resolveModalCharCount" class="badge bg-light text-secondary border" style="font-size: 0.75rem;">0/1000</span>
                      </div>
                    </div>
                  </div>
                  <div class="alert alert-info border-0 d-flex align-items-start gap-2 mb-0" style="background-color: #e3f2fd;">
                    <i class="bi bi-info-circle-fill text-primary mt-1"></i>
                    <small class="text-primary mb-0">
                      This comment will be permanently saved with the incident record and cannot be edited later.
                    </small>
                  </div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                  <button type="button" class="btn btn-light px-4 py-2 fw-semibold" data-bs-dismiss="modal" style="border: 2px solid #e0e0e0;">
                    <i class="bi bi-x-circle me-1"></i>
                    Cancel
                  </button>
                  <button type="button" id="resolveModalSaveBtn" class="btn btn-primary px-4 py-2 fw-semibold shadow-sm" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none;">
                    <i class="bi bi-check-circle me-1"></i>
                    Save & Resolve
                  </button>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Resolution Error Modal -->
          <div class="modal fade" id="resolveErrorModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-sm">
              <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 pb-2" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                  <div class="d-flex align-items-center gap-2 w-100">
                    <div class="d-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-25" style="width: 40px; height: 40px;">
                      <i class="bi bi-exclamation-triangle-fill text-white fs-5"></i>
                    </div>
                    <h5 class="modal-title text-white fw-bold mb-0">Validation Error</h5>
                  </div>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                  <i class="bi bi-chat-left-text-fill text-danger mb-3" style="font-size: 3rem; opacity: 0.8;"></i>
                  <p class="mb-0 fw-semibold text-dark">Resolution comment cannot be empty.</p>
                  <p class="text-muted small mb-0 mt-2">Please provide a comment explaining how the incident was resolved.</p>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4 justify-content-center">
                  <button type="button" class="btn btn-danger px-4 py-2 fw-semibold shadow-sm" data-bs-dismiss="modal" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border: none;">
                    <i class="bi bi-check-circle me-1"></i>
                    OK
                  </button>
                </div>
              </div>
            </div>
          </div>
          
          <style>
          #resolveModalTextarea:focus {
            border-color: #10b981 !important;
            box-shadow: 0 0 0 0.2rem rgba(16, 185, 129, 0.25) !important;
          }
          
          #resolveModal .modal-content {
            border-radius: 16px;
            overflow: hidden;
          }
          
          #resolveModal .modal-header {
            padding: 1.5rem;
          }
          
          #resolveModalSaveBtn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4) !important;
            transition: all 0.3s ease;
          }
          
          #resolveModal .btn-light:hover {
            background-color: #f8f9fa;
            border-color: #10b981 !important;
            color: #10b981;
            transition: all 0.3s ease;
          }
          </style>

          <script>
          // Resolve modal handlers
          (function(){
            const txt = document.getElementById('resolveModalTextarea');
            const count = document.getElementById('resolveModalCharCount');
            const saveBtn = document.getElementById('resolveModalSaveBtn');
            const resolveModalEl = document.getElementById('resolveModal');
            if (txt) {
              txt.addEventListener('input', function(){ count.textContent = this.value.length + '/1000'; });
            }
            if (saveBtn) {
              saveBtn.addEventListener('click', function(){
                const modal = bootstrap.Modal.getInstance(resolveModalEl);
                const pending = window.__resolvePending;
                if (!pending) { modal.hide(); return; }
                const comment = txt.value.trim();
                if (!comment) { 
                  const errorModal = new bootstrap.Modal(document.getElementById('resolveErrorModal'));
                  errorModal.show();
                  return; 
                }
                // send AJAX with comment
                fetch('incident_ajax.php', {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                  body: 'action=update_status&id=' + encodeURIComponent(pending.id) + '&status=Resolved&comment=' + encodeURIComponent(comment)
                })
                .then(r => r.json())
                .then(data => {
                  if (data.success) {
                    modal.hide();
                    reloadIncidentTable();
                    window.__resolvePending = null;
                  } else {
                    alert('Failed to save resolution: ' + (data.error || 'unknown'));
                    modal.hide();
                    if (pending && pending.selectEl) pending.selectEl.value = pending.selectEl.dataset.prev;
                    window.__resolvePending = null;
                  }
                }).catch(err => { alert('Network error'); modal.hide(); if (pending && pending.selectEl) pending.selectEl.value = pending.selectEl.dataset.prev; window.__resolvePending = null; });
              });
            }
            // When modal hidden, if unresolved, revert select
            resolveModalEl.addEventListener('hidden.bs.modal', function(){
              const pending = window.__resolvePending;
              if (pending && pending.selectEl) {
                // if still set, user closed modal without saving
                pending.selectEl.value = pending.selectEl.dataset.prev;
                window.__resolvePending = null;
              }
            });
          })();

          // Handle view toggle (active/archived) without page reload for incidents
          document.querySelectorAll('.incident-view-toggle').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
              e.preventDefault();
              var targetView = btn.getAttribute('data-iview');
              var currentViewBtn = document.querySelector('.incident-view-toggle.active');
              var currentView = currentViewBtn ? currentViewBtn.getAttribute('data-iview') : 'active';
              
              // Don't reload if clicking the same view
              if (targetView === currentView) return;
              
              // Update button states
              document.querySelectorAll('.incident-view-toggle').forEach(function(b) {
                b.classList.remove('active', 'btn-light', 'fw-semibold');
                b.classList.add('btn-outline-light');
              });
              
              btn.classList.remove('btn-outline-light');
              btn.classList.add('active', 'btn-light', 'fw-semibold');
              
              // Update hidden input in search form
              var viewInput = document.querySelector('input[name="iview"]');
              if (viewInput) viewInput.value = targetView;
              
              // Update page title
              var headerTitle = document.getElementById('incident-panel-title');
              if (headerTitle) {
                headerTitle.textContent = targetView === 'archived' ? 'Archived Incident Reports' : 'Manage Incident Reports';
              }
              
              // Update subtitle
              var headerSubtitle = document.querySelector('.incident-header-modern p');
              if (headerSubtitle) {
                headerSubtitle.innerHTML = '<i class="bi bi-info-circle me-1"></i>' + 
                  (targetView === 'archived' ? 'View and manage archived incident reports' : 'Track and manage incident reports efficiently');
              }
              
              // Build URL with current filters
              var urlParams = new URLSearchParams(window.location.search);
              urlParams.set('panel', 'incidents');
              urlParams.set('iview', targetView);
              
              // Fetch and update content
              fetch('admin_dashboard.php?' + urlParams.toString(), { 
                method: 'GET', 
                credentials: 'same-origin' 
              })
              .then(function(response) { return response.text(); })
              .then(function(html) {
                // Parse the response
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                
                // Update table
                var newTableContainer = doc.querySelector('#incident-table-container');
                var currentTableContainer = document.querySelector('#incident-table-container');
                if (newTableContainer && currentTableContainer) {
                  currentTableContainer.innerHTML = newTableContainer.innerHTML;
                }
                
                // Update or hide counts section
                var newCounts = doc.querySelector('#incident-counts');
                var currentCounts = document.querySelector('#incident-counts');
                
                if (targetView === 'active') {
                  // Show counts for active view
                  if (newCounts) {
                    if (currentCounts) {
                      currentCounts.innerHTML = newCounts.innerHTML;
                      currentCounts.style.display = '';
                    } else {
                      // Insert counts if they don't exist
                      var toggleDiv = document.querySelector('.incident-view-toggle').parentElement;
                      toggleDiv.insertAdjacentHTML('afterend', newCounts.outerHTML);
                    }
                  }
                } else {
                  // Hide counts for archived view
                  if (currentCounts) {
                    currentCounts.style.display = 'none';
                  }
                }
                
                // Re-attach incident event handlers
                attachIncidentHandlers();
              })
              .catch(function(error) {
                console.error('Error fetching incidents:', error);
                alert('Error loading data. Please refresh the page.');
              });
            });
          });

          // Handle search form submission without page reload
          document.getElementById('incident-search-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            var searchInput = document.getElementById('incident-search-input').value;
            var statusSelect = document.getElementById('incident-status-select').value;
            var startDate = document.getElementById('incident-start-date').value;
            var endDate = document.getElementById('incident-end-date').value;
            var currentViewBtn = document.querySelector('.incident-view-toggle.active');
            var currentView = currentViewBtn ? currentViewBtn.getAttribute('data-iview') : 'active';
            
            // Build URL with search parameters
            var urlParams = new URLSearchParams();
            urlParams.set('panel', 'incidents');
            urlParams.set('iview', currentView);
            if (searchInput) urlParams.set('isearch', searchInput);
            if (statusSelect) urlParams.set('istatus', statusSelect);
            if (startDate) urlParams.set('istart_date', startDate);
            if (endDate) urlParams.set('iend_date', endDate);
            
            // Show loading state
            var applyBtn = document.getElementById('incident-apply-btn');
            var originalText = applyBtn.innerHTML;
            applyBtn.disabled = true;
            applyBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Loading...';
            
            // Fetch and update table content
            fetch('admin_dashboard.php?' + urlParams.toString(), { 
              method: 'GET', 
              credentials: 'same-origin' 
            })
            .then(function(response) { return response.text(); })
            .then(function(html) {
              // Parse the response and extract the table
              var parser = new DOMParser();
              var doc = parser.parseFromString(html, 'text/html');
              
              // Update table
              var newTableContainer = doc.querySelector('#incident-table-container');
              var currentTableContainer = document.querySelector('#incident-table-container');
              if (newTableContainer && currentTableContainer) {
                currentTableContainer.innerHTML = newTableContainer.innerHTML;
              }
              
              // Update counts if in active view
              if (currentView === 'active') {
                var newCounts = doc.querySelector('#incident-counts');
                var currentCounts = document.querySelector('#incident-counts');
                if (newCounts && currentCounts) {
                  currentCounts.innerHTML = newCounts.innerHTML;
                }
              }
              
              // Re-attach incident event handlers
              attachIncidentHandlers();
              
              // Reset button
              applyBtn.disabled = false;
              applyBtn.innerHTML = originalText;
            })
            .catch(function(error) {
              console.error('Error fetching incidents:', error);
              alert('Error loading data. Please refresh the page.');
              applyBtn.disabled = false;
              applyBtn.innerHTML = originalText;
            });
          });

          // Handle reset button
          document.getElementById('incident-reset-btn').addEventListener('click', function() {
            // Clear all form inputs
            document.getElementById('incident-search-input').value = '';
            document.getElementById('incident-status-select').value = '';
            document.getElementById('incident-start-date').value = '';
            document.getElementById('incident-end-date').value = '';
            
            // Trigger form submission to reload with no filters
            document.getElementById('incident-search-form').dispatchEvent(new Event('submit'));
          });
          </script>
          
          <!-- Delete Incident Report Confirmation Modal -->
          <div class="modal fade" id="deleteIncidentModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 pb-2" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                  <div class="d-flex align-items-center gap-2 w-100">
                    <div class="d-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-25" style="width: 40px; height: 40px;">
                      <i class="bi bi-trash-fill text-white fs-5"></i>
                    </div>
                    <h5 class="modal-title text-white fw-bold mb-0">Delete Incident Report</h5>
                  </div>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                  <div class="alert alert-danger border-0 d-flex align-items-start gap-2 mb-3" style="background-color: #fee2e2;">
                    <i class="bi bi-exclamation-triangle-fill text-danger fs-5 mt-1"></i>
                    <div>
                      <strong class="text-danger d-block mb-1">Warning: This action cannot be undone!</strong>
                      <small class="text-danger">This incident report will be permanently deleted from the database.</small>
                    </div>
                  </div>
                  <p class="mb-0 fw-semibold text-secondary">Are you sure you want to delete this incident report?</p>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                  <button type="button" class="btn btn-light px-4 py-2 fw-semibold" data-bs-dismiss="modal" style="border: 2px solid #e0e0e0;">
                    <i class="bi bi-x-circle me-1"></i>
                    Cancel
                  </button>
                  <button type="button" id="confirmDeleteIncidentBtn" class="btn btn-danger px-4 py-2 fw-semibold shadow-sm" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border: none;">
                    <i class="bi bi-trash me-1"></i>
                    Delete
                  </button>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Block User from Incidents Modal -->
          <div class="modal fade" id="blockIncidentUserModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 pb-2" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                  <div class="d-flex align-items-center gap-2 w-100">
                    <div class="d-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-25" style="width: 40px; height: 40px;">
                      <i class="bi bi-person-x-fill text-white fs-5"></i>
                    </div>
                    <h5 class="modal-title text-white fw-bold mb-0">Block User from Incident Reports</h5>
                  </div>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                  <div class="alert alert-warning border-0 d-flex align-items-start gap-2 mb-3" style="background-color: #fef3c7;">
                    <i class="bi bi-exclamation-triangle-fill text-warning fs-5 mt-1"></i>
                    <div>
                      <strong class="text-warning d-block mb-1">This user will be blocked from submitting incident reports</strong>
                      <small class="text-warning">They will not be able to submit new incident reports until unblocked.</small>
                    </div>
                  </div>
                  <p class="mb-0 fw-semibold text-secondary">Are you sure you want to block this user from submitting incident reports?</p>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                  <button type="button" class="btn btn-light px-4 py-2 fw-semibold" data-bs-dismiss="modal" style="border: 2px solid #e0e0e0;">
                    <i class="bi bi-x-circle me-1"></i>
                    Cancel
                  </button>
                  <button type="button" id="confirmBlockIncidentBtn" class="btn btn-warning px-4 py-2 fw-semibold shadow-sm" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border: none; color: white;">
                    <i class="bi bi-person-x me-1"></i>
                    Block User
                  </button>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Unblock User from Incidents Modal -->
          <div class="modal fade" id="unblockIncidentUserModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 pb-2" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                  <div class="d-flex align-items-center gap-2 w-100">
                    <div class="d-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-25" style="width: 40px; height: 40px;">
                      <i class="bi bi-person-check-fill text-white fs-5"></i>
                    </div>
                    <h5 class="modal-title text-white fw-bold mb-0">Unblock User from Incident Reports</h5>
                  </div>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                  <div class="alert alert-success border-0 d-flex align-items-start gap-2 mb-3" style="background-color: #d1fae5;">
                    <i class="bi bi-check-circle-fill text-success fs-5 mt-1"></i>
                    <div>
                      <strong class="text-success d-block mb-1">This user will be able to submit incident reports again</strong>
                      <small class="text-success">They will regain access to submit new incident reports.</small>
                    </div>
                  </div>
                  <p class="mb-0 fw-semibold text-secondary">Are you sure you want to unblock this user from submitting incident reports?</p>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                  <button type="button" class="btn btn-light px-4 py-2 fw-semibold" data-bs-dismiss="modal" style="border: 2px solid #e0e0e0;">
                    <i class="bi bi-x-circle me-1"></i>
                    Cancel
                  </button>
                  <button type="button" id="confirmUnblockIncidentBtn" class="btn btn-success px-4 py-2 fw-semibold shadow-sm" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none;">
                    <i class="bi bi-person-check me-1"></i>
                    Unblock User
                  </button>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Blocked Users Modal -->
          <div class="modal fade" id="blockedUsersModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
              <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 pb-2" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                  <div class="d-flex align-items-center gap-2 w-100">
                    <div class="d-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-25" style="width: 40px; height: 40px;">
                      <i class="bi bi-person-x-fill text-white fs-5"></i>
                    </div>
                    <h5 class="modal-title text-white fw-bold mb-0">Blocked Users from Incident Reports</h5>
                  </div>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                  <div class="alert alert-info border-0 d-flex align-items-start gap-2 mb-3" style="background-color: #e3f2fd;">
                    <i class="bi bi-info-circle-fill text-primary fs-5 mt-1"></i>
                    <div>
                      <strong class="text-primary d-block mb-1">Blocked Users List</strong>
                      <small class="text-primary">These users are currently blocked from submitting incident reports. Click "Unblock" to restore their access.</small>
                    </div>
                  </div>
                  <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-hover align-middle">
                      <thead class="table-light sticky-top">
                        <tr>
                          <th>User ID</th>
                          <th>Name</th>
                          <th class="text-center">Action</th>
                        </tr>
                      </thead>
                      <tbody id="blockedUsersTableBody">
                        <tr>
                          <td colspan="3" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                              <span class="visually-hidden">Loading...</span>
                            </div>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                  <button type="button" class="btn btn-secondary px-4 py-2 fw-semibold" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>
                    Close
                  </button>
                </div>
              </div>
            </div>
          </div>
          
          <style>
          #deleteIncidentModal .modal-content {
            border-radius: 16px;
            overflow: hidden;
          }
          
          #deleteIncidentModal .modal-header {
            padding: 1.5rem;
          }
          
          #confirmDeleteIncidentBtn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4) !important;
            transition: all 0.3s ease;
          }
          
          #deleteIncidentModal .btn-light:hover {
            background-color: #f8f9fa;
            border-color: #ef4444 !important;
            color: #ef4444;
            transition: all 0.3s ease;
          }
          </style>
          
          <script>
          // Handle delete confirmation (attach after modal is in DOM)
          (function() {
            var confirmDeleteIncidentBtn = document.getElementById('confirmDeleteIncidentBtn');
            if (!confirmDeleteIncidentBtn) {
              console.error('confirmDeleteIncidentBtn not found!');
              return;
            }
            
            console.log('Delete confirmation handler attached');
            
            confirmDeleteIncidentBtn.addEventListener('click', function() {
              console.log('Delete button clicked, currentDeleteIncidentId:', currentDeleteIncidentId);
              
              if (!currentDeleteIncidentId) {
                console.error('No incident ID to delete');
                alert('Error: No incident selected for deletion');
                return;
              }
              
              // Disable button and show loading
              this.disabled = true;
              this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Deleting...';
              
              // Close modal
              var modal = bootstrap.Modal.getInstance(document.getElementById('deleteIncidentModal'));
              if (modal) modal.hide();
              
              console.log('Sending delete request for ID:', currentDeleteIncidentId, 'archived:', currentDeleteIncidentArchived);
              
              // Perform delete
              fetch('incident_ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=delete&id=' + encodeURIComponent(currentDeleteIncidentId) + '&archived=' + encodeURIComponent(currentDeleteIncidentArchived)
              })
              .then(function(r) {
                console.log('Response status:', r.status);
                return r.json();
              })
              .then(function(data) {
                console.log('Delete response:', data);
                
                // Reset button
                confirmDeleteIncidentBtn.disabled = false;
                confirmDeleteIncidentBtn.innerHTML = 'Delete';
                
                if (data.success) {
                  // Show success message
                  var successMsg = document.createElement('div');
                  successMsg.className = 'alert alert-success alert-dismissible fade show';
                  successMsg.style.position = 'fixed';
                  successMsg.style.top = '20px';
                  successMsg.style.right = '20px';
                  successMsg.style.zIndex = '9999';
                  successMsg.innerHTML = '<strong>✓ Success!</strong> Incident report deleted.<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                  document.body.appendChild(successMsg);
                  setTimeout(function() { successMsg.remove(); }, 3000);
                  
                  // Reload table
                  reloadIncidentTable();
                  
                  // Reset references
                  currentDeleteIncidentBtn = null;
                  currentDeleteIncidentId = null;
                  currentDeleteIncidentArchived = null;
                } else {
                  alert('Error: ' + (data.error || 'Failed to delete incident'));
                }
              })
              .catch(function(error) {
                console.error('Delete error:', error);
                confirmDeleteIncidentBtn.disabled = false;
                confirmDeleteIncidentBtn.innerHTML = 'Delete';
                alert('Network error: ' + error.message);
              });
            });
          })();
          
          // Handle block incident user confirmation
          (function() {
            var confirmBlockBtn = document.getElementById('confirmBlockIncidentBtn');
            if (confirmBlockBtn) {
              confirmBlockBtn.addEventListener('click', function() {
                if (!window.currentBlockIncidentUserId) {
                  alert('Error: No user selected');
                  return;
                }
                
                // Disable button and show loading
                this.disabled = true;
                this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Blocking...';
                
                // Close modal
                var modal = bootstrap.Modal.getInstance(document.getElementById('blockIncidentUserModal'));
                if (modal) modal.hide();
                
                // Send AJAX request to block user
                fetch('admin_dashboard.php?panel=incidents&block_incident_user=' + window.currentBlockIncidentUserId, {
                  method: 'GET',
                  credentials: 'same-origin'
                })
                .then(function(response) { return response.text(); })
                .then(function(html) {
                  // Reset button
                  confirmBlockBtn.disabled = false;
                  confirmBlockBtn.innerHTML = '<i class="bi bi-person-x me-1"></i>Block User';
                  
                  // Show success message
                  var successMsg = document.createElement('div');
                  successMsg.className = 'alert alert-success alert-dismissible fade show';
                  successMsg.style.position = 'fixed';
                  successMsg.style.top = '20px';
                  successMsg.style.right = '20px';
                  successMsg.style.zIndex = '9999';
                  successMsg.innerHTML = '<strong>✓ Success!</strong> User has been blocked from submitting incident reports.<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                  document.body.appendChild(successMsg);
                  setTimeout(function() { successMsg.remove(); }, 3000);
                  
                  // Reload table
                  reloadIncidentTable();
                  
                  // Reset reference
                  window.currentBlockIncidentUserId = null;
                })
                .catch(function(error) {
                  console.error('Block error:', error);
                  confirmBlockBtn.disabled = false;
                  confirmBlockBtn.innerHTML = '<i class="bi bi-person-x me-1"></i>Block User';
                  alert('Error blocking user: ' + error.message);
                });
              });
            }
          })();
          
          // Handle unblock incident user confirmation
          (function() {
            var confirmUnblockBtn = document.getElementById('confirmUnblockIncidentBtn');
            if (confirmUnblockBtn) {
              confirmUnblockBtn.addEventListener('click', function() {
                if (!window.currentUnblockIncidentUserId) {
                  alert('Error: No user selected');
                  return;
                }
                
                // Disable button and show loading
                this.disabled = true;
                this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Unblocking...';
                
                // Close modal
                var modal = bootstrap.Modal.getInstance(document.getElementById('unblockIncidentUserModal'));
                if (modal) modal.hide();
                
                // Send AJAX request to unblock user
                fetch('admin_dashboard.php?panel=incidents&unblock_incident_user=' + window.currentUnblockIncidentUserId, {
                  method: 'GET',
                  credentials: 'same-origin'
                })
                .then(function(response) { return response.text(); })
                .then(function(html) {
                  // Reset button
                  confirmUnblockBtn.disabled = false;
                  confirmUnblockBtn.innerHTML = '<i class="bi bi-person-check me-1"></i>Unblock User';
                  
                  // Show success message
                  var successMsg = document.createElement('div');
                  successMsg.className = 'alert alert-success alert-dismissible fade show';
                  successMsg.style.position = 'fixed';
                  successMsg.style.top = '20px';
                  successMsg.style.right = '20px';
                  successMsg.style.zIndex = '9999';
                  successMsg.innerHTML = '<strong>✓ Success!</strong> User has been unblocked from submitting incident reports.<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                  document.body.appendChild(successMsg);
                  setTimeout(function() { successMsg.remove(); }, 3000);
                  
                  // Reload table
                  reloadIncidentTable();
                  
                  // Reset reference
                  window.currentUnblockIncidentUserId = null;
                })
                .catch(function(error) {
                  console.error('Unblock error:', error);
                  confirmUnblockBtn.disabled = false;
                  confirmUnblockBtn.innerHTML = '<i class="bi bi-person-check me-1"></i>Unblock User';
                  alert('Error unblocking user: ' + error.message);
                });
              });
            }
          })();
          </script>
        </div>
      </div>
      <!-- End incident_reports.php logic -->
    </div>
    <!-- Admin Logs Panel (hidden by default) -->
<div id="panel-admin-logs" class="panel-section" style="display:none;">
<?php
include 'config.php';
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
  echo "⛔ Login first to access this page. <a href='login.html'>Login Here</a>";
  exit;
}
// Handle search by date/time
$start = isset($_GET['start']) ? $_GET['start'] : '';
$end = isset($_GET['end']) ? $_GET['end'] : '';
$where = '';
if ($start && $end) {
  $where = "WHERE (login_time BETWEEN '" . $conn->real_escape_string($start) . "' AND '" . $conn->real_escape_string($end) . "' OR action_time BETWEEN '" . $conn->real_escape_string($start) . "' AND '" . $conn->real_escape_string($end) . "')";
}
$sql = "SELECT username, login_time, logout_time, action, action_time FROM admin_logs $where ORDER BY id DESC";
$result = $conn->query($sql);
?>
  <div class="container-fluid py-4">
    <!-- Modern Header with Gradient -->
    <div class="admin-logs-header-modern mb-4 p-4 rounded-4 shadow-sm" style="background: linear-gradient(160deg, #14ad0f 0%, #43e97b 100%);">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="admin-logs-icon-wrapper d-flex align-items-center justify-content-center rounded-circle" style="width: 60px; height: 60px; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); box-shadow: 0 4px 12px rgba(20,173,15,0.2);">
            <i class="bi bi-journal-text text-white" style="font-size: 1.8rem; filter: drop-shadow(0 2px 4px rgba(20,173,15,0.3));"></i>
          </div>
          <div>
            <h2 class="fw-bold text-white mb-1" style="font-size: 1.75rem; text-shadow: 0 2px 8px rgba(20,173,15,0.3); letter-spacing: 0.5px;">
              Admin Activity Logs
            </h2>
            <p class="text-white mb-0" style="font-size: 0.9rem; opacity: 0.9;">
              <i class="bi bi-info-circle me-1"></i>
              Track admin login, logout, and activity history
            </p>
          </div>
        </div>
      </div>
    </div>

    <style>
      #adminLogsSearchBtn:hover {
        background: linear-gradient(90deg,#43e97b 0%,#14ad0f 100%) !important;
        box-shadow: 0 6px 18px rgba(20,173,15,0.18) !important;
        transform: scale(1.05);
      }
    </style>

    <div class="card p-4">
      <form id="adminLogsSearchForm" class="row g-3 align-items-end mb-4">
        <div class="col-md-4">
          <label for="start" class="form-label fw-semibold">Start Date/Time</label>
          <input type="datetime-local" id="start" name="start" class="form-control" style="border-radius: 10px;">
        </div>
        <div class="col-md-4">
          <label for="end" class="form-label fw-semibold">End Date/Time</label>
          <input type="datetime-local" id="end" name="end" class="form-control" style="border-radius: 10px;">
        </div>
        <div class="col-md-4">
          <button type="submit" id="adminLogsSearchBtn" class="btn" style="background: linear-gradient(90deg,#14ad0f 0%,#43e97b 100%); color: #fff; border: none; padding: 10px 24px; border-radius: 12px; font-weight: 600; box-shadow: 0 2px 12px rgba(20,173,15,0.13); transition: all 0.22s;">
            <i class="bi bi-funnel me-1"></i> Search
          </button>
          <button type="button" id="adminLogsResetBtn" class="btn btn-outline-secondary ms-2" style="border: 2px solid #6c757d; border-radius: 10px; font-weight: 600; padding: 10px 24px;">Reset</button>
        </div>
      </form>
      
      <div id="adminLogsTableContainer" class="table-responsive" style="max-height:500px; overflow:auto; border-radius:10px; border: 1px solid #e0e0e0;">
        <!-- Table will be loaded here by JS -->
      </div>
    </div>
  </div>
</div>
<script>
// Admin Logs panel logic - will be handled by main showPanel function

function loadAdminLogsTable(start = '', end = '') {
  const container = document.getElementById('adminLogsTableContainer');
  container.innerHTML = '<div class="text-center py-4">Loading...</div>';
  fetch('get_admin_logs.php?start=' + encodeURIComponent(start) + '&end=' + encodeURIComponent(end))
    .then(r => r.text())
    .then(html => { container.innerHTML = html; });
}

document.getElementById('adminLogsSearchForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const start = document.getElementById('start').value;
  const end = document.getElementById('end').value;
  loadAdminLogsTable(start, end);
});
document.getElementById('adminLogsResetBtn').addEventListener('click', function() {
  document.getElementById('start').value = '';
  document.getElementById('end').value = '';
  loadAdminLogsTable();
});

// Initial load
loadAdminLogsTable(document.getElementById('start').value, document.getElementById('end').value);
</script>
    <div id="panel-suggestions" class="panel-section" style="display:none;">
      <?php
      // --- Begin admin_suggestions.php logic (adapted for dashboard panel) ---
      include 'config.php';
      
      // Check if admin is logged in
      if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
          header("Location: index.php?admin_login=required");
          exit();
      }
      $message = "";
      if (isset($_SESSION['flash'])) {
          $message = $_SESSION['flash'];
          unset($_SESSION['flash']);
      }
      $sql = "SELECT s.message_id, s.userid, s.subject, s.message, s.created_at, CONCAT(r.first_name, ' ', COALESCE(r.middle_name,''), ' ', r.surname) AS fullname FROM suggestions s JOIN useraccounts u ON s.userid = u.userid JOIN residents r ON u.userid = r.unique_id ORDER BY s.created_at DESC";
      $result = $conn->query($sql);
      if (!$result) {
          die("Error fetching suggestions: " . htmlspecialchars($conn->error));
      }
      ?>
      <div class="container-fluid py-4">
        <!-- Modern Header with Gradient -->
        <div class="suggestions-header-modern mb-4 p-4 rounded-4 shadow-sm" style="background: linear-gradient(160deg, #14ad0f 0%, #43e97b 100%);">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
              <div class="suggestions-icon-wrapper d-flex align-items-center justify-content-center rounded-circle" style="width: 60px; height: 60px; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); box-shadow: 0 4px 12px rgba(20,173,15,0.2);">
                <i class="bi bi-chat-square-text-fill text-white" style="font-size: 1.8rem; filter: drop-shadow(0 2px 4px rgba(20,173,15,0.3));"></i>
              </div>
              <div>
                <h2 class="fw-bold text-white mb-1" style="font-size: 1.75rem; text-shadow: 0 2px 8px rgba(20,173,15,0.3); letter-spacing: 0.5px;">
                  Resident Suggestions
                </h2>
                <p class="text-white mb-0" style="font-size: 0.9rem; opacity: 0.9;">
                  <i class="bi bi-info-circle me-1"></i>
                  View and manage community feedback and suggestions
                </p>
              </div>
            </div>
          </div>
        </div>

        <div class="card p-4">
        <?php if ($message): ?>
          <div class="alert <?= strpos($message, 'success') !== false ? 'alert-success' : 'alert-danger' ?> text-center">
            <?= htmlspecialchars($message) ?>
          </div>
        <?php endif; ?>
        <div id="suggestions-content-area">
        <?php if ($result->num_rows > 0): ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead class="table-success">
                <tr>
                  <th>User ID</th>
                  <th>User</th>
                  <th>Subject</th>
                  <th>Message</th>
                  <th>Date</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                  <tr>
                    <td><?= htmlspecialchars($row['userid']) ?></td>
                    <td><?= htmlspecialchars($row['fullname']) ?></td>
                    <td><?= htmlspecialchars($row['subject']) ?></td>
                    <td style="white-space:pre-wrap;max-width:400px;line-height:1.5;"><?= nl2br(htmlspecialchars($row['message'])) ?></td>
                    <td><?= date("M d, Y h:i A", strtotime($row['created_at'])) ?></td>
                    <td>
                      <button type="button" class="btn btn-danger btn-sm suggestion-delete-btn" data-id="<?= $row['message_id'] ?>">Delete</button>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p class="text-muted text-center">No suggestions submitted yet.</p>
        <?php endif; ?>
        </div>
        </div>
      </div>
      <!-- End admin_suggestions.php logic -->
      
      <!-- Delete Suggestion Modal -->
      <div class="modal fade" id="deleteSuggestionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-2" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
              <div class="d-flex align-items-center gap-2 w-100">
                <div class="d-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-25" style="width: 40px; height: 40px;">
                  <i class="bi bi-trash-fill text-white fs-5"></i>
                </div>
                <h5 class="modal-title text-white fw-bold mb-0">Delete Suggestion</h5>
              </div>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
              <div class="alert alert-danger border-0 d-flex align-items-start gap-2 mb-3" style="background-color: #fee2e2;">
                <i class="bi bi-exclamation-triangle-fill text-danger fs-5 mt-1"></i>
                <div>
                  <strong class="text-danger d-block mb-1">This action cannot be undone</strong>
                  <small class="text-danger">The suggestion will be permanently deleted from the system.</small>
                </div>
              </div>
              <p class="mb-0 fw-semibold text-secondary">Are you sure you want to delete this suggestion?</p>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4">
              <button type="button" class="btn btn-light px-4 py-2 fw-semibold" data-bs-dismiss="modal" style="border: 2px solid #e0e0e0;">
                <i class="bi bi-x-circle me-1"></i>Cancel
              </button>
              <button type="button" id="confirmDeleteSuggestionBtn" class="btn btn-danger px-4 py-2 fw-semibold shadow-sm" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border: none;">
                <i class="bi bi-trash me-1"></i>Delete Suggestion
              </button>
            </div>
          </div>
        </div>
      </div>
      
      <script>
      // Global variable for current suggestion to delete
      var currentDeleteSuggestionId = null;
      var currentDeleteSuggestionBtn = null;
      
      // Function to attach suggestion delete handlers
      function attachSuggestionDeleteHandlers() {
        document.querySelectorAll('.suggestion-delete-btn').forEach(function(btn) {
          btn.addEventListener('click', function() {
            currentDeleteSuggestionId = btn.getAttribute('data-id');
            currentDeleteSuggestionBtn = btn;
            
            // Show modal
            var modal = new bootstrap.Modal(document.getElementById('deleteSuggestionModal'));
            modal.show();
          });
        });
      }
      
      // Handle confirm delete button
      var confirmDeleteSuggestionBtn = document.getElementById('confirmDeleteSuggestionBtn');
      if (confirmDeleteSuggestionBtn) {
        confirmDeleteSuggestionBtn.addEventListener('click', function() {
          if (!currentDeleteSuggestionId) return;
          
          var confirmBtn = this;
          confirmBtn.disabled = true;
          confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Deleting...';
          
          if (currentDeleteSuggestionBtn) {
            currentDeleteSuggestionBtn.disabled = true;
            currentDeleteSuggestionBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
          }
            
          // Close modal
          var modal = bootstrap.Modal.getInstance(document.getElementById('deleteSuggestionModal'));
          if (modal) modal.hide();
          
          var formData = new FormData();
          formData.append('id', currentDeleteSuggestionId);
          
          fetch('delete_suggestion.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
          })
          .then(function(response) { return response.text(); })
          .then(function(html) {
            // Reset button
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="bi bi-trash me-1"></i>Delete Suggestion';
            
            // Show success message
            var successMsg = document.createElement('div');
            successMsg.className = 'alert alert-success alert-dismissible fade show';
            successMsg.innerHTML = '<strong>✓ Deleted!</strong> Suggestion deleted successfully.<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            successMsg.style.position = 'fixed';
            successMsg.style.top = '20px';
            successMsg.style.right = '20px';
            successMsg.style.zIndex = '9999';
            document.body.appendChild(successMsg);
            
            setTimeout(function() {
              successMsg.remove();
            }, 3000);
            
            // Reload suggestions content
            fetch('admin_dashboard.php?panel=suggestions', {
              method: 'GET',
              credentials: 'same-origin'
            })
            .then(function(response) { return response.text(); })
            .then(function(html) {
              var parser = new DOMParser();
              var doc = parser.parseFromString(html, 'text/html');
              var newContent = doc.querySelector('#panel-suggestions');
              if (newContent) {
                document.getElementById('panel-suggestions').innerHTML = newContent.innerHTML;
                attachSuggestionDeleteHandlers();
              }
            });
            
            // Reset variables
            currentDeleteSuggestionId = null;
            currentDeleteSuggestionBtn = null;
          })
          .catch(function(error) {
            console.error('Error deleting suggestion:', error);
            
            // Reset buttons
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="bi bi-trash me-1"></i>Delete Suggestion';
            
            if (currentDeleteSuggestionBtn) {
              currentDeleteSuggestionBtn.disabled = false;
              currentDeleteSuggestionBtn.innerHTML = 'Delete';
            }
            
            alert('Error deleting suggestion. Please try again.');
          });
        });
      }
      
      // Initial attachment
      attachSuggestionDeleteHandlers();
      </script>
    </div>
    <div id="panel-certificates" class="panel-section" style="display:none;">
      <?php
      // --- Begin manage_certificate.php logic ---
     
      include 'config.php';
      $admin_username = $_SESSION['admin_username'] ?? 'Unknown';
      if (isset($_GET['block_user'])) {
          $userid = $_GET['block_user'];
          $conn->query("UPDATE residents SET can_request = 0 WHERE unique_id = '$userid'");
          $action_log = "Blocked resident: $userid";
          $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
          $log->bind_param("ss", $admin_username, $action_log);
          $log->execute();
          $log->close();
          header("Location: admin_dashboard.php?panel=certificates");
          exit;
      }
      if (isset($_GET['unblock_user'])) {
          $userid = $_GET['unblock_user'];
          $conn->query("UPDATE residents SET can_request = 1 WHERE unique_id = '$userid'");
          $action_log = "Unblocked resident: $userid";
          $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
          $log->bind_param("ss", $admin_username, $action_log);
          $log->execute();
          $log->close();
          header("Location: admin_dashboard.php?panel=certificates");
          exit;
      }
      if (isset($_GET['approve'])) {
          $id = intval($_GET['approve']);
          $stmt = $conn->prepare("UPDATE certificate_requests SET status = 'Approved' WHERE id = ?");
          $stmt->bind_param("i", $id);
          if ($stmt->execute()) {
              $action_log = "Approved certificate request ID: $id";
              $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
              $log->bind_param("ss", $admin_username, $action_log);
              $log->execute();
              $log->close();
          }
          $stmt->close();
          header("Location: admin_dashboard.php?panel=certificates");
          exit;
      }
      if (isset($_GET['print'])) {
          $id = intval($_GET['print']);
      $conn->query("UPDATE certificate_requests SET status = 'Printed', completed_at = NOW() WHERE id = $id");
      $action_log = "Printed certificate request ID: $id";
      $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
      $log->bind_param("ss", $admin_username, $action_log);
      $log->execute();
      $log->close();
      header("Location: print_certificate.php?id=$id");
      exit;
    }

    // Archive Printed certificate
    if (isset($_GET['archive'])) {
      $id = intval($_GET['archive']);
      $conn->query("INSERT INTO archived_certificate_requests SELECT * FROM certificate_requests WHERE id = $id");
      $conn->query("DELETE FROM certificate_requests WHERE id = $id");
      $action_log = "Archived certificate request ID: $id (status Printed)";
      $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
      $log->bind_param("ss", $admin_username, $action_log);
      $log->execute();
      $log->close();
      header("Location: admin_dashboard.php?panel=certificates");
      exit;
      }
      if (isset($_GET['reject'])) {
          $id = intval($_GET['reject']);
          $conn->query("UPDATE certificate_requests SET status = 'Rejected' WHERE id = $id");
          $conn->query("INSERT INTO archived_certificate_requests SELECT * FROM certificate_requests WHERE id = $id");
          $conn->query("DELETE FROM certificate_requests WHERE id = $id");
          $action_log = "Rejected & Archived certificate request ID: $id";
          $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
          $log->bind_param("ss", $admin_username, $action_log);
          $log->execute();
          $log->close();
          header("Location: admin_dashboard.php?panel=certificates");
          exit;
      }
      $certOptions = $conn->query("SELECT * FROM certificate_options");
      $search = $_GET['search'] ?? '';
  $filter = $_GET['filter'] ?? '';
  $status_filter = $_GET['status_filter'] ?? '';
  $view = $_GET['view'] ?? 'active';
      if ($view === "active") {
     $sql = "SELECT cr.id, cr.certificate_type, cr.purpose, cr.description, cr.created_at, cr.status,
          r.unique_id AS userid,
          CONCAT(r.surname, ', ', r.first_name, ' ', IFNULL(r.middle_name, '')) AS resident_name
        FROM certificate_requests cr
        INNER JOIN residents r ON cr.resident_unique_id = r.unique_id
        WHERE cr.status != 'Archived'";
      } else {
          $sql = "SELECT acr.id, acr.certificate_type, acr.purpose, acr.description, acr.created_at, acr.completed_at, acr.status,
                         r.unique_id AS userid,
                         CONCAT(r.surname, ', ', r.first_name, ' ', IFNULL(r.middle_name, '')) AS resident_name
                  FROM archived_certificate_requests acr
                  INNER JOIN residents r ON acr.resident_unique_id = r.unique_id
                  WHERE 1";
      }
      $params = [];
      $types = "";
      if (!empty($search)) {
          $sql .= " AND (r.surname LIKE ? OR r.first_name LIKE ? OR r.unique_id LIKE ? OR certificate_type LIKE ?)";
          $searchTerm = "%$search%";
          $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
          $types .= "ssss";
      }
      if (!empty($filter)) {
      $sql .= " AND certificate_type = ?";
      $params[] = $filter;
      $types .= "s";
    }
    if (!empty($status_filter)) {
      $tableAlias = ($view === "active") ? "cr" : "acr";
      $sql .= " AND {$tableAlias}.status = ?";
      $params[] = $status_filter;
      $types .= "s";
      }
  $sql .= " ORDER BY created_at DESC";
  $stmt = $conn->prepare($sql);
      if (!$stmt) die("SQL Error: " . $conn->error);
      if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
      }
      $stmt->execute();
      $result = $stmt->get_result();
      // Sort certificate requests: Pending first, then Approved, then Printed
      $certs = [];
      $pendingCount = 0;
      $approvedCount = 0;
      $printedCount = 0;
      while ($row = $result->fetch_assoc()) {
        $certs[] = $row;
        if ($row['status'] === 'Pending') $pendingCount++;
        elseif ($row['status'] === 'Approved') $approvedCount++;
        elseif ($row['status'] === 'Printed') $printedCount++;
      }
      $statusOrder = [
        'Pending' => 1,
        'Approved' => 2,
        'Printed' => 3
      ];
      usort($certs, function($a, $b) use ($statusOrder) {
        $aOrder = $statusOrder[$a['status']] ?? 99;
        $bOrder = $statusOrder[$b['status']] ?? 99;
        return $aOrder <=> $bOrder;
      });
        
      $summarySql = "SELECT r.unique_id AS userid,
           CONCAT(r.surname, ', ', r.first_name, ' ', IFNULL(r.middle_name, '')) AS resident_name,
           SUM(CASE WHEN cr.certificate_type='Barangay Clearance' THEN 1 ELSE 0 END) AS barangay_clearance,
           SUM(CASE WHEN cr.certificate_type='Certificate of Indigency' THEN 1 ELSE 0 END) AS indigency,
           SUM(CASE WHEN cr.certificate_type='Certificate of Residency' THEN 1 ELSE 0 END) AS residency,
           COUNT(cr.id) AS total_requests
    FROM residents r
    LEFT JOIN (
        SELECT * FROM certificate_requests
        UNION ALL
        SELECT * FROM archived_certificate_requests
    ) cr ON r.unique_id = cr.resident_unique_id
    GROUP BY r.unique_id, resident_name
    ORDER BY total_requests DESC";
      $summaryResult = $conn->query($summarySql);
      ?>
      <!-- Begin manage_certificate.php HTML (with action/links updated to admin_dashboard.php?panel=certificates) -->
      <div class="container-fluid py-4">
        <!-- Modern Header with Gradient -->
        <div class="certificate-header-modern mb-4 p-4 rounded-4 shadow-sm" style="background: linear-gradient(160deg, #14ad0f 0%, #43e97b 100%);">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
              <div class="certificate-icon-wrapper d-flex align-items-center justify-content-center rounded-circle" style="width: 60px; height: 60px; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); box-shadow: 0 4px 12px rgba(20,173,15,0.2);">
                <i class="bi bi-file-earmark-text-fill text-white" style="font-size: 1.8rem; filter: drop-shadow(0 2px 4px rgba(20,173,15,0.3));"></i>
              </div>
              <div>
                <h2 class="fw-bold text-white mb-1" id="certificate-panel-title" style="font-size: 1.75rem; text-shadow: 0 2px 8px rgba(20,173,15,0.3); letter-spacing: 0.5px;">
                  <?= $view === 'archived' ? 'Archived Certificate Requests' : 'Manage Certificate Requests' ?>
                </h2>
                <p class="text-white mb-0" style="font-size: 0.9rem; opacity: 0.9;">
                  <i class="bi bi-info-circle me-1"></i>
                  <?= $view === 'archived' ? 'View and manage archived certificate requests' : 'Efficiently manage and process certificate requests' ?>
                </p>
              </div>
            </div>
            <div class="d-flex gap-2">
              <div class="btn-group shadow-sm" role="group">
                <button class="btn certificate-view-toggle <?= $view === 'active' ? 'btn-light fw-semibold active' : 'btn-outline-light' ?>" data-view="active" style="min-width: 100px; border-radius: 12px 0 0 12px;">
                  <i class="bi bi-file-earmark-check me-1"></i> Active
                </button>
                <button class="btn certificate-view-toggle <?= $view === 'archived' ? 'btn-light fw-semibold active' : 'btn-outline-light' ?>" data-view="archived" style="min-width: 100px; border-radius: 0 12px 12px 0;">
                  <i class="bi bi-archive me-1"></i> Archives
                </button>
              </div>
              <button class="btn btn-outline-light shadow-sm fw-semibold" id="view-blocked-cert-users-btn" style="border-radius: 12px; min-width: 150px;">
                <i class="bi bi-person-x me-1"></i> Blocked Users
              </button>
            </div>
          </div>
        </div>

        <style>
          #toggleSummaryBtn:hover, #toggleCertOptionsBtn:hover, #toggleContentManagementBtn:hover, #cert-apply-btn:hover {
            background: linear-gradient(90deg,#43e97b 0%,#14ad0f 100%) !important;
            box-shadow: 0 6px 18px rgba(20,173,15,0.18) !important;
            transform: scale(1.05);
          }
          .certificate-view-toggle:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(20,173,15,0.15) !important;
          }
          #closeSummaryModal:hover, #save-cert-options-btn:hover {
            background: linear-gradient(90deg,#43e97b 0%,#14ad0f 100%) !important;
            box-shadow: 0 6px 18px rgba(20,173,15,0.18) !important;
            transform: scale(1.05);
          }
          #closeCertModal:hover {
            background: #5a6268 !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
            transform: scale(1.05);
          }
        </style>

        <div class="card p-4">
          <div class="mb-3 text-center">
            <button class="btn" id="toggleSummaryBtn" style="background: linear-gradient(90deg,#14ad0f 0%,#43e97b 100%); color: #fff; border: none; padding: 10px 20px; border-radius: 12px; font-weight: 600; box-shadow: 0 2px 12px rgba(20,173,15,0.13); transition: all 0.22s;">
              <i class="bi bi-bar-chart-line"></i> View Certificate Requests Summary
            </button>

            <button class="btn" id="toggleCertOptionsBtn" style="background: linear-gradient(90deg,#14ad0f 0%,#43e97b 100%); color: #fff; border: none; padding: 10px 20px; border-radius: 12px; font-weight: 600; box-shadow: 0 2px 12px rgba(20,173,15,0.13); transition: all 0.22s; margin-left: 10px;">
              <i class="bi bi-gear"></i> Enable/Disable Certificates
            </button>

            <button class="btn" id="toggleContentManagementBtn" style="background: linear-gradient(90deg,#14ad0f 0%,#43e97b 100%); color: #fff; border: none; padding: 10px 20px; border-radius: 12px; font-weight: 600; box-shadow: 0 2px 12px rgba(20,173,15,0.13); transition: all 0.22s; margin-left: 10px;">
              <i class="bi bi-pencil-square"></i> Content Management
            </button>
          </div>
             <!-- Display total counts -->
             <div class="mb-3 d-flex gap-4 justify-content-start align-items-center" style="font-size:1.08em;" id="cert-counts">
            <span class="fw-bold text-warning">Pending: <span class="badge bg-warning text-dark"><?= $pendingCount ?></span></span>
            <span class="fw-bold text-success">Approved: <span class="badge bg-success"><?= $approvedCount ?></span></span>
            <span class="fw-bold text-secondary">Printed: <span class="badge bg-secondary"><?= $printedCount ?></span></span>
          </div>
          <form method="GET" class="row g-2 mb-4" action="admin_dashboard.php" id="cert-search-form">
            <input type="hidden" name="panel" value="certificates">
            <input type="hidden" name="view" value="<?= htmlspecialchars($view) ?>">
            <div class="col-md-4">
              <input type="text" class="form-control" name="search" placeholder="🔍 Search by name, ID, or type"
                     value="<?= htmlspecialchars($search) ?>" id="cert-search-input">
            </div>
            <div class="col-md-3">
              <select name="filter" class="form-select" id="cert-filter-select">
                <option value="">All Types</option>
                <option value="Barangay Clearance" <?= $filter === 'Barangay Clearance' ? 'selected' : '' ?>>Barangay Clearance</option>
                <option value="Indigency" <?= $filter === 'Indigency' ? 'selected' : '' ?>>Certificate of Indigency</option>
                <option value="Residency" <?= $filter === 'Residency' ? 'selected' : '' ?>>Certificate of Residency</option>
              </select>
            </div>
            <div class="col-md-3">
              <select name="status_filter" class="form-select" id="cert-status-select">
                <option value="">All Status</option>
                <option value="Pending" <?= $status_filter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                <option value="Approved" <?= $status_filter === 'Approved' ? 'selected' : '' ?>>Approved</option>
                <option value="Printed" <?= $status_filter === 'Printed' ? 'selected' : '' ?>>Printed</option>
                <option value="Rejected" <?= $status_filter === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
              </select>
            </div>
            <div class="col-md-2 d-grid">
              <button type="submit" class="btn" id="cert-apply-btn" style="background: linear-gradient(90deg,#14ad0f 0%,#43e97b 100%); color: #fff; border: none; padding: 10px 20px; border-radius: 12px; font-weight: 600; box-shadow: 0 2px 12px rgba(20,173,15,0.13); transition: all 0.22s;">
                <i class="bi bi-funnel"></i> Apply
              </button>
            </div>
          </form>
          <div class="table-responsive" style="max-height: 600px; overflow-y: auto;" id="cert-table-container">
            <table class="table table-hover align-middle">
              <thead style="position: sticky; top: 0; z-index: 10; background-color: #f8f9fa;">
                <tr>
                  <th>User ID</th>
                  <th>Resident Name</th>
                  <th>Type</th>
                  <th>Purpose</th>
                  <th>Description</th>
                  <th>Date Requested</th>
                  <?php if ($view === 'archived'): ?>
                  <th>Date Completed</th>
                  <?php endif; ?>
                  <th>Status</th>
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
              <?php if (empty($certs)): ?>
                <tr><td colspan="<?= $view === 'archived' ? '9' : '8' ?>" class="text-center text-muted">No records found</td></tr>
              <?php else: ?>
                <?php foreach ($certs as $cert): ?>
                  <?php
                  // Check if user is blocked
                  $canRequestRes = $conn->query("SELECT can_request FROM residents WHERE unique_id = '{$cert['userid']}'")->fetch_assoc();
                  $canRequest = $canRequestRes ? $canRequestRes['can_request'] : 1;
                  
                  // Skip this certificate if user is blocked
                  if ($canRequest == 0) {
                      continue;
                  }
                  ?>
                  <tr>
                    <td><?= htmlspecialchars($cert['userid']) ?></td>
                    <td><?= htmlspecialchars($cert['resident_name']) ?></td>
                    <td><?= htmlspecialchars($cert['certificate_type']) ?></td>
                    <td><?= htmlspecialchars($cert['purpose']) ?></td>
                    <td>
                      <?php if (!empty($cert['description'])): ?>
                        <?php if (strlen($cert['description']) > 50): ?>
                          <span class="text-muted" style="font-size: 0.9em;">
                            <?= htmlspecialchars(substr($cert['description'], 0, 50)) ?>...
                          </span>
                          <button type="button" class="btn btn-link btn-sm p-0 ms-1 view-description-btn" 
                                  data-description="<?= htmlspecialchars($cert['description']) ?>"
                                  data-cert-type="<?= htmlspecialchars($cert['certificate_type']) ?>"
                                  data-resident="<?= htmlspecialchars($cert['resident_name']) ?>"
                                  style="font-size: 0.85em; text-decoration: none;">
                            <i class="bi bi-eye"></i> View
                          </button>
                        <?php else: ?>
                          <span class="text-muted" style="font-size: 0.9em;">
                            <?= htmlspecialchars($cert['description']) ?>
                          </span>
                        <?php endif; ?>
                      <?php else: ?>
                        <span class="text-muted" style="font-size: 0.85em;">—</span>
                      <?php endif; ?>
                    </td>
                    <td><?= date("M d, Y h:i A", strtotime($cert['created_at'])) ?></td>
                    <?php if ($view === 'archived'): ?>
                    <td><?= isset($cert['completed_at']) && $cert['completed_at'] ? date("M d, Y h:i A", strtotime($cert['completed_at'])) : '<span class="text-muted">N/A</span>' ?></td>
                    <?php endif; ?>
                    <td>
                      <span class="badge bg-<?=
                  $cert['status'] === 'Approved' ? 'success' :
                  ($cert['status'] === 'Pending' ? 'warning' :
                  ($cert['status'] === 'Rejected' ? 'danger' : 'secondary')) ?>">
                        <?= htmlspecialchars($cert['status']) ?>
                      </span>
                    </td>
                    <td class="text-center" style="white-space: nowrap;">
                      <?php if ($view === "active"): ?>
                        <div class="d-inline-flex gap-1">
                        <?php if ($cert['status'] === 'Pending'): ?>
                          <button type="button" class="btn btn-success btn-sm cert-approve-btn" data-id="<?= $cert['id'] ?>" title="Approve" data-bs-toggle="tooltip">
                            <i class="bi bi-check-circle"></i>
                          </button>
                          <button type="button" class="btn btn-danger btn-sm cert-reject-btn" data-id="<?= $cert['id'] ?>" title="Reject" data-bs-toggle="tooltip">
                            <i class="bi bi-x-circle"></i>
                          </button>
                        <?php endif; ?>
                        <?php if ($cert['status'] === 'Approved'): ?>
                          <button type="button" class="btn btn-primary btn-sm cert-print-btn" data-id="<?= $cert['id'] ?>" title="Print & Archive" data-bs-toggle="tooltip">
                            <i class="bi bi-printer"></i>
                          </button>
                        <?php endif; ?>
                          <?php if ($cert['status'] === 'Printed'): ?>
                            <button type="button" class="btn btn-secondary btn-sm cert-archive-btn" data-id="<?= $cert['id'] ?>" title="Archive" data-bs-toggle="tooltip">
                              <i class="bi bi-archive"></i>
                            </button>
                          <?php endif; ?>
                          <button type="button" class="btn btn-danger btn-sm cert-block-btn" data-userid="<?= $cert['userid'] ?>" title="Block" data-bs-toggle="tooltip">
                            <i class="bi bi-person-x"></i>
                          </button>
                        </div>
                      <?php else: ?>
                        <span class="text-muted">Archived</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Modern Archive Confirmation Modal -->
      <div class="modal fade" id="archiveCertModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header border-0 pb-2" style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%);">
              <div class="d-flex align-items-center gap-2 w-100">
                <div class="d-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-25" style="width: 40px; height: 40px;">
                  <i class="bi bi-archive-fill text-white fs-5"></i>
                </div>
                <h5 class="modal-title text-white fw-bold mb-0">Archive Certificate</h5>
              </div>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
              <div class="alert alert-info border-0 d-flex align-items-start gap-2 mb-3" style="background-color: #e7f3ff;">
                <i class="bi bi-info-circle-fill text-info fs-5 mt-1"></i>
                <div>
                  <strong class="text-info d-block mb-1">Archive this certificate?</strong>
                  <small class="text-info">This printed certificate will be moved to the archives.</small>
                </div>
              </div>
              <p class="mb-0 fw-semibold text-secondary">Are you sure you want to archive this printed certificate?</p>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4">
              <button type="button" class="btn btn-light px-4 py-2 fw-semibold" data-bs-dismiss="modal" style="border: 2px solid #e0e0e0;">
                <i class="bi bi-x-circle me-1"></i>Cancel
              </button>
              <button type="button" id="confirmArchiveCertBtn" class="btn btn-secondary px-4 py-2 fw-semibold shadow-sm" style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%); border: none;">
                <i class="bi bi-archive me-1"></i>Archive
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Modern Block Resident Confirmation Modal -->
      <div class="modal fade" id="blockResidentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header border-0 pb-2" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
              <div class="d-flex align-items-center gap-2 w-100">
                <div class="d-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-25" style="width: 40px; height: 40px;">
                  <i class="bi bi-person-x-fill text-white fs-5"></i>
                </div>
                <h5 class="modal-title text-white fw-bold mb-0">Block Resident</h5>
              </div>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
              <div class="alert alert-danger border-0 d-flex align-items-start gap-2 mb-3" style="background-color: #fee2e2;">
                <i class="bi bi-exclamation-triangle-fill text-danger fs-5 mt-1"></i>
                <div>
                  <strong class="text-danger d-block mb-1">Warning: This will restrict access</strong>
                  <small class="text-danger">This resident will not be able to request certificates until unblocked.</small>
                </div>
              </div>
              <p class="mb-0 fw-semibold text-secondary">Are you sure you want to block this resident from requesting certificates?</p>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4">
              <button type="button" class="btn btn-light px-4 py-2 fw-semibold" data-bs-dismiss="modal" style="border: 2px solid #e0e0e0;">
                <i class="bi bi-x-circle me-1"></i>Cancel
              </button>
              <button type="button" id="confirmBlockBtn" class="btn btn-danger px-4 py-2 fw-semibold shadow-sm" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border: none;">
                <i class="bi bi-person-x me-1"></i>Block Resident
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Modern Unblock Resident Confirmation Modal -->
      <div class="modal fade" id="unblockResidentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header border-0 pb-2" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
              <div class="d-flex align-items-center gap-2 w-100">
                <div class="d-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-25" style="width: 40px; height: 40px;">
                  <i class="bi bi-person-check-fill text-white fs-5"></i>
                </div>
                <h5 class="modal-title text-white fw-bold mb-0">Unblock Resident</h5>
              </div>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
              <div class="alert alert-success border-0 d-flex align-items-start gap-2 mb-3" style="background-color: #d1fae5;">
                <i class="bi bi-check-circle-fill text-success fs-5 mt-1"></i>
                <div>
                  <strong class="text-success d-block mb-1">Restore Access</strong>
                  <small class="text-success">This resident will be able to request certificates again.</small>
                </div>
              </div>
              <p class="mb-0 fw-semibold text-secondary">Are you sure you want to unblock this resident?</p>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4">
              <button type="button" class="btn btn-light px-4 py-2 fw-semibold" data-bs-dismiss="modal" style="border: 2px solid #e0e0e0;">
                <i class="bi bi-x-circle me-1"></i>Cancel
              </button>
              <button type="button" id="confirmUnblockBtn" class="btn btn-success px-4 py-2 fw-semibold shadow-sm" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none;">
                <i class="bi bi-person-check me-1"></i>Unblock Resident
              </button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Reject Certificate Modal -->
      <div class="modal fade" id="rejectCertModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-2" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
              <div class="d-flex align-items-center gap-2 w-100">
                <div class="d-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-25" style="width: 40px; height: 40px;">
                  <i class="bi bi-x-circle-fill text-white fs-5"></i>
                </div>
                <h5 class="modal-title text-white fw-bold mb-0">Reject Certificate Request</h5>
              </div>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
              <div class="alert alert-danger border-0 d-flex align-items-start gap-2 mb-3" style="background-color: #fee2e2;">
                <i class="bi bi-exclamation-triangle-fill text-danger fs-5 mt-1"></i>
                <div>
                  <strong class="text-danger d-block mb-1">This request will be rejected and archived</strong>
                  <small class="text-danger">The resident will need to submit a new request if needed.</small>
                </div>
              </div>
              <p class="mb-0 fw-semibold text-secondary">Are you sure you want to reject this certificate request?</p>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4">
              <button type="button" class="btn btn-light px-4 py-2 fw-semibold" data-bs-dismiss="modal" style="border: 2px solid #e0e0e0;">
                <i class="bi bi-x-circle me-1"></i>Cancel
              </button>
              <button type="button" id="confirmRejectBtn" class="btn btn-danger px-4 py-2 fw-semibold shadow-sm" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border: none;">
                <i class="bi bi-x-circle me-1"></i>Reject Request
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- View Description Modal -->
      <div class="modal fade" id="viewDescriptionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header border-0 pb-2" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
              <div class="d-flex align-items-center gap-2 w-100">
                <div class="d-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-25" style="width: 40px; height: 40px;">
                  <i class="bi bi-file-text-fill text-white fs-5"></i>
                </div>
                <h5 class="modal-title text-white fw-bold mb-0">Certificate Description</h5>
              </div>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
              <div class="mb-3">
                <p class="mb-2"><strong>Resident:</strong> <span id="desc-resident-name" class="text-primary"></span></p>
                <p class="mb-2"><strong>Certificate Type:</strong> <span id="desc-cert-type" class="text-success"></span></p>
              </div>
              <div class="alert alert-light border" style="background-color: #f8f9fa;">
                <strong class="d-block mb-2 text-secondary"><i class="bi bi-info-circle me-1"></i>Additional Details:</strong>
                <p id="desc-full-text" class="mb-0" style="white-space: pre-wrap; word-break: break-word; line-height: 1.6;"></p>
              </div>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4">
              <button type="button" class="btn btn-primary px-4 py-2 fw-semibold" data-bs-dismiss="modal" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border: none;">
                <i class="bi bi-check-circle me-1"></i>Close
              </button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Blocked Certificate Users Modal -->
      <div class="modal fade" id="blockedCertUsersModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
          <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-2" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
              <div class="d-flex align-items-center gap-2 w-100">
                <div class="d-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-25" style="width: 40px; height: 40px;">
                  <i class="bi bi-person-x-fill text-white fs-5"></i>
                </div>
                <h5 class="modal-title text-white fw-bold mb-0">Blocked Users from Certificate Requests</h5>
              </div>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
              <div class="alert alert-info border-0 d-flex align-items-start gap-2 mb-3" style="background-color: #e3f2fd;">
                <i class="bi bi-info-circle-fill text-primary fs-5 mt-1"></i>
                <div>
                  <strong class="text-primary d-block mb-1">Blocked Users List</strong>
                  <small class="text-primary">These users are currently blocked from requesting certificates. Click "Unblock" to restore their access.</small>
                </div>
              </div>
              <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-hover align-middle">
                  <thead class="table-light sticky-top">
                    <tr>
                      <th>User ID</th>
                      <th>Name</th>
                      <th class="text-center">Action</th>
                    </tr>
                  </thead>
                  <tbody id="blockedCertUsersTableBody">
                    <tr>
                      <td colspan="3" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                          <span class="visually-hidden">Loading...</span>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4">
              <button type="button" class="btn btn-secondary px-4 py-2 fw-semibold" data-bs-dismiss="modal">
                <i class="bi bi-x-circle me-1"></i>
                Close
              </button>
            </div>
          </div>
        </div>
      </div>

<script>
// Global variables for modals
var archiveModal = null;
var blockModal = null;
var unblockModal = null;
var rejectModal = null;
var currentArchiveCertId = null;
var currentBlockUserId = null;
var currentUnblockUserId = null;
var currentRejectCertId = null;

// Initialize modals when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
  archiveModal = new bootstrap.Modal(document.getElementById('archiveCertModal'));
  blockModal = new bootstrap.Modal(document.getElementById('blockResidentModal'));
  unblockModal = new bootstrap.Modal(document.getElementById('unblockResidentModal'));
  rejectModal = new bootstrap.Modal(document.getElementById('rejectCertModal'));
  
  // Initial attachment of handlers after modals are ready
  attachCertHandlers();
  
  // Confirm archive button
  document.getElementById('confirmArchiveCertBtn').addEventListener('click', function() {
    if (!currentArchiveCertId) return;
    
    var btn = document.querySelector('.cert-archive-btn[data-id="' + currentArchiveCertId + '"]');
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    }
    
    fetch('admin_dashboard.php?panel=certificates&archive=' + currentArchiveCertId, {
      method: 'GET',
      credentials: 'same-origin'
    })
    .then(function(response) { return response.text(); })
    .then(function() {
      archiveModal.hide();
      
      var successMsg = document.createElement('div');
      successMsg.className = 'alert alert-success';
      successMsg.innerHTML = '<strong>✓ Archived!</strong> Certificate archived successfully.';
      successMsg.style.position = 'fixed';
      successMsg.style.top = '20px';
      successMsg.style.right = '20px';
      successMsg.style.zIndex = '9999';
      document.body.appendChild(successMsg);
      
      setTimeout(function() { successMsg.remove(); }, 3000);
      reloadCertTable();
      currentArchiveCertId = null;
    });
  });
  
  // Confirm block button
  document.getElementById('confirmBlockBtn').addEventListener('click', function() {
    if (!currentBlockUserId) return;
    
    var btn = document.querySelector('.cert-block-btn[data-userid="' + currentBlockUserId + '"]');
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    }
    
    fetch('admin_dashboard.php?panel=certificates&block_user=' + currentBlockUserId, {
      method: 'GET',
      credentials: 'same-origin'
    })
    .then(function(response) { return response.text(); })
    .then(function() {
      blockModal.hide();
      
      var successMsg = document.createElement('div');
      successMsg.className = 'alert alert-success';
      successMsg.innerHTML = '<strong>✓ Blocked!</strong> Resident blocked from requesting certificates.';
      successMsg.style.position = 'fixed';
      successMsg.style.top = '20px';
      successMsg.style.right = '20px';
      successMsg.style.zIndex = '9999';
      document.body.appendChild(successMsg);
      
      setTimeout(function() { successMsg.remove(); }, 3000);
      reloadCertTable();
      currentBlockUserId = null;
    });
  });
  
  // Confirm unblock button
  document.getElementById('confirmUnblockBtn').addEventListener('click', function() {
    if (!currentUnblockUserId) return;
    
    var btn = document.querySelector('.cert-unblock-btn[data-userid="' + currentUnblockUserId + '"]');
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    }
    
    fetch('admin_dashboard.php?panel=certificates&unblock_user=' + currentUnblockUserId, {
      method: 'GET',
      credentials: 'same-origin'
    })
    .then(function(response) { return response.text(); })
    .then(function() {
      unblockModal.hide();
      
      var successMsg = document.createElement('div');
      successMsg.className = 'alert alert-success';
      successMsg.innerHTML = '<strong>✓ Unblocked!</strong> Resident can now request certificates.';
      successMsg.style.position = 'fixed';
      successMsg.style.top = '20px';
      successMsg.style.right = '20px';
      successMsg.style.zIndex = '9999';
      document.body.appendChild(successMsg);
      
      setTimeout(function() { successMsg.remove(); }, 3000);
      reloadCertTable();
      currentUnblockUserId = null;
    });
  });
  
  // Handle view toggle (active/archived) without page reload
  document.querySelectorAll('.certificate-view-toggle').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      var targetView = btn.getAttribute('data-view');
      var currentView = document.querySelector('.certificate-view-toggle.active').getAttribute('data-view');
      
      // Don't reload if clicking the same view
      if (targetView === currentView) return;
      
      // Update button states
      document.querySelectorAll('.certificate-view-toggle').forEach(function(b) {
        b.classList.remove('active', 'btn-light', 'fw-semibold');
        b.classList.add('btn-outline-light');
      });
      
      btn.classList.remove('btn-outline-light');
      btn.classList.add('active', 'btn-light', 'fw-semibold');
      
      // Update hidden input in search form
      var viewInput = document.querySelector('input[name="view"]');
      if (viewInput) viewInput.value = targetView;
      
      // Update page title
      var headerTitle = document.getElementById('certificate-panel-title');
      if (headerTitle) {
        headerTitle.textContent = targetView === 'archived' ? 'Archived Certificate Requests' : 'Manage Certificate Requests';
      }
      
      // Update subtitle
      var headerSubtitle = document.querySelector('.certificate-header-modern p');
      if (headerSubtitle) {
        headerSubtitle.innerHTML = '<i class="bi bi-info-circle me-1"></i>' + 
          (targetView === 'archived' ? 'View and manage archived certificate requests' : 'Efficiently manage and process certificate requests');
      }
      
      // Build URL with current filters
      var urlParams = new URLSearchParams(window.location.search);
      urlParams.set('panel', 'certificates');
      urlParams.set('view', targetView);
      
      // Fetch and update table content
      fetch('admin_dashboard.php?' + urlParams.toString(), { 
        method: 'GET', 
        credentials: 'same-origin' 
      })
      .then(function(response) { return response.text(); })
      .then(function(html) {
        // Parse the response and extract the table
        var parser = new DOMParser();
        var doc = parser.parseFromString(html, 'text/html');
        
        // Update table
        var newTableContainer = doc.querySelector('#cert-table-container');
        var currentTableContainer = document.querySelector('#cert-table-container');
        if (newTableContainer && currentTableContainer) {
          currentTableContainer.innerHTML = newTableContainer.innerHTML;
        }
        
        // Update counts
        var newCounts = doc.querySelector('#cert-counts');
        var currentCounts = document.querySelector('#cert-counts');
        if (newCounts && currentCounts) {
          currentCounts.innerHTML = newCounts.innerHTML;
        }
        
        // Re-attach event listeners for the new buttons
        attachCertHandlers();
        attachCertificateEventListeners();
      })
      .catch(function(error) {
        console.error('Error fetching table:', error);
        alert('Error loading data. Please refresh the page.');
      });
    });
  });
  
  // Handle search form submission without page reload
  var certSearchForm = document.getElementById('cert-search-form');
  if (certSearchForm) {
    certSearchForm.addEventListener('submit', function(e) {
      e.preventDefault();
      
      var searchInput = document.getElementById('cert-search-input').value;
      var filterSelect = document.getElementById('cert-filter-select').value;
      var statusSelect = document.getElementById('cert-status-select').value;
      var currentView = document.querySelector('.certificate-view-toggle.active').getAttribute('data-view');
      
      // Build URL with search parameters
      var urlParams = new URLSearchParams();
      urlParams.set('panel', 'certificates');
      urlParams.set('view', currentView);
      if (searchInput) urlParams.set('search', searchInput);
      if (filterSelect) urlParams.set('filter', filterSelect);
      if (statusSelect) urlParams.set('status_filter', statusSelect);
      
      // Show loading state
      var applyBtn = document.getElementById('cert-apply-btn');
      var originalText = applyBtn.innerHTML;
      applyBtn.disabled = true;
      applyBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Loading...';
      
      // Fetch and update table
      fetch('admin_dashboard.php?' + urlParams.toString(), {
        method: 'GET',
        credentials: 'same-origin'
      })
      .then(function(response) { return response.text(); })
      .then(function(html) {
        var parser = new DOMParser();
        var doc = parser.parseFromString(html, 'text/html');
        
        // Update table
        var newTable = doc.querySelector('#cert-table-container');
        var currentTable = document.querySelector('#cert-table-container');
        if (newTable && currentTable) {
          currentTable.innerHTML = newTable.innerHTML;
        }
        
        // Update counts
        var newCounts = doc.querySelector('#cert-counts');
        var currentCounts = document.querySelector('#cert-counts');
        if (newCounts && currentCounts) {
          currentCounts.innerHTML = newCounts.innerHTML;
        }
        
        // Re-attach event listeners for the new buttons
        attachCertHandlers();
        attachCertificateEventListeners();
        
        // Reset button
        applyBtn.disabled = false;
        applyBtn.innerHTML = originalText;
      })
      .catch(function(error) {
        console.error('Error fetching table:', error);
        alert('Error loading data. Please refresh the page.');
        applyBtn.disabled = false;
        applyBtn.innerHTML = originalText;
      });
    });
  }
});

// Function to reload certificate table
function reloadCertTable() {
  var currentUrl = new URL(window.location.href);
  currentUrl.searchParams.set('panel', 'certificates');
  
  fetch(currentUrl.toString(), {
    method: 'GET',
    credentials: 'same-origin'
  })
  .then(function(response) { return response.text(); })
  .then(function(html) {
    var parser = new DOMParser();
    var doc = parser.parseFromString(html, 'text/html');
    
    var newTable = doc.querySelector('#cert-table-container');
    var currentTable = document.querySelector('#cert-table-container');
    if (newTable && currentTable) {
      currentTable.innerHTML = newTable.innerHTML;
      attachCertHandlers();
    }
    
    // Update counts
    var newCounts = doc.querySelector('#cert-counts');
    var currentCounts = document.querySelector('#cert-counts');
    if (newCounts && currentCounts) {
      currentCounts.innerHTML = newCounts.innerHTML;
    }
  });
}

// Function to attach certificate handlers
function attachCertHandlers() {
  // Approve buttons
  document.querySelectorAll('.cert-approve-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var certId = btn.getAttribute('data-id');
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
      
      fetch('admin_dashboard.php?panel=certificates&approve=' + certId, {
        method: 'GET',
        credentials: 'same-origin'
      })
      .then(function(response) { return response.text(); })
      .then(function() {
        var successMsg = document.createElement('div');
        successMsg.className = 'alert alert-success';
        successMsg.innerHTML = '<strong>✓ Approved!</strong> Certificate approved successfully.';
        successMsg.style.position = 'fixed';
        successMsg.style.top = '20px';
        successMsg.style.right = '20px';
        successMsg.style.zIndex = '9999';
        document.body.appendChild(successMsg);
        
        setTimeout(function() { successMsg.remove(); }, 3000);
        reloadCertTable();
      });
    });
  });
  
  // Reject buttons
  document.querySelectorAll('.cert-reject-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      currentRejectCertId = btn.getAttribute('data-id');
      rejectModal.show();
    });
  });
  
  // Confirm reject button
  document.getElementById('confirmRejectBtn').addEventListener('click', function() {
    if (!currentRejectCertId) return;
    
    var btn = document.querySelector('.cert-reject-btn[data-id="' + currentRejectCertId + '"]');
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    }
    
    rejectModal.hide();
    
    fetch('admin_dashboard.php?panel=certificates&reject=' + currentRejectCertId, {
      method: 'GET',
      credentials: 'same-origin'
    })
    .then(function(response) { return response.text(); })
    .then(function() {
      var successMsg = document.createElement('div');
      successMsg.className = 'alert alert-success alert-dismissible fade show';
      successMsg.innerHTML = '<strong>✓ Rejected!</strong> Certificate rejected and archived.<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
      successMsg.style.position = 'fixed';
      successMsg.style.top = '20px';
      successMsg.style.right = '20px';
      successMsg.style.zIndex = '9999';
      document.body.appendChild(successMsg);
      
      setTimeout(function() { successMsg.remove(); }, 3000);
      reloadCertTable();
      currentRejectCertId = null;
    })
    .catch(function(error) {
      console.error('Error rejecting certificate:', error);
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-x-circle"></i> Reject';
      }
      alert('Error rejecting certificate. Please try again.');
    });
  });
  
  // Handle "Blocked Users" button click for certificates
  var viewBlockedCertUsersBtn = document.getElementById('view-blocked-cert-users-btn');
  if (viewBlockedCertUsersBtn) {
    viewBlockedCertUsersBtn.addEventListener('click', function() {
      var modal = new bootstrap.Modal(document.getElementById('blockedCertUsersModal'));
      
      // Load blocked users
      fetch('admin_dashboard.php?panel=certificates&action=get_blocked_cert_users', {
        method: 'GET',
        credentials: 'same-origin'
      })
      .then(function(response) { return response.json(); })
      .then(function(data) {
        if (data.success) {
          var tbody = document.getElementById('blockedCertUsersTableBody');
          tbody.innerHTML = data.html;
          
          // Attach unblock handlers
          document.querySelectorAll('.unblock-cert-user-from-modal-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
              var userid = btn.getAttribute('data-userid');
              currentUnblockUserId = userid;
              
              // Close blocked users modal first
              var blockedModal = bootstrap.Modal.getInstance(document.getElementById('blockedCertUsersModal'));
              if (blockedModal) blockedModal.hide();
              
              // Show unblock confirmation modal
              unblockModal.show();
            });
          });
        } else {
          alert('Error loading blocked users');
        }
      })
      .catch(function(error) {
        console.error('Error:', error);
        alert('Error loading blocked users');
      });
      
      modal.show();
    });
  }
  
  // Print buttons
  document.querySelectorAll('.cert-print-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var certId = btn.getAttribute('data-id');
      window.open('print_certificate.php?id=' + certId, '_blank');
      
      // Mark as printed
      fetch('admin_dashboard.php?panel=certificates&print=' + certId, {
        method: 'GET',
        credentials: 'same-origin'
      })
      .then(function() {
        setTimeout(function() { reloadCertTable(); }, 1000);
      });
    });
  });
  
  // Archive buttons
  document.querySelectorAll('.cert-archive-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      currentArchiveCertId = btn.getAttribute('data-id');
      if (archiveModal) archiveModal.show();
    });
  });
  
  // Block buttons
  document.querySelectorAll('.cert-block-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      currentBlockUserId = btn.getAttribute('data-userid');
      if (blockModal) blockModal.show();
    });
  });
  
  // View description buttons
  document.querySelectorAll('.view-description-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var description = btn.getAttribute('data-description');
      var certType = btn.getAttribute('data-cert-type');
      var residentName = btn.getAttribute('data-resident');
      
      // Populate modal
      document.getElementById('desc-resident-name').textContent = residentName;
      document.getElementById('desc-cert-type').textContent = certType;
      document.getElementById('desc-full-text').textContent = description;
      
      // Show modal
      var viewDescModal = new bootstrap.Modal(document.getElementById('viewDescriptionModal'));
      viewDescModal.show();
    });
  });
}

// Function to attach event listeners to certificate action buttons
function attachCertificateEventListeners() {
  // Archive buttons (alternative class name)
  document.querySelectorAll('.archive-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      var certId = btn.getAttribute('data-id');
      currentArchiveCertId = certId;
      archiveModal.show();
    });
  });
}
</script>
          </div>
        </div>
      </div>
      
      <!-- Modal Overlay for Summary -->
      <div id="summaryModalOverlay" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.5); z-index:2000; overflow-y:auto; padding:20px 0;">
        <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:#fff; border-radius:16px; box-shadow:0 8px 32px rgba(20,173,15,0.2); padding:0; min-width:350px; max-width:900px; width:90%; max-height:90vh; display:flex; flex-direction:column;">
          <!-- Header with green gradient -->
          <div style="background: linear-gradient(135deg, #14ad0f 0%, #43e97b 100%); padding: 24px 30px; border-radius: 16px 16px 0 0; flex-shrink:0;">
            <h4 class="mb-0 text-white fw-bold" style="font-size: 1.4rem; letter-spacing: 0.5px;">📊 Certificate Requests Summary by Resident</h4>
          </div>
          <div style="padding: 30px; overflow-y:auto; flex:1; min-height:0;">
            <div class="mb-3">
              <input type="text" id="summarySearchInput" class="form-control" placeholder="🔍 Search by User ID or Resident Name (type to filter)"
                     value="" style="border: 2px solid #14ad0f; border-radius: 10px; padding: 10px 15px;">
            </div>
            <div class="table-responsive" style="max-height:350px; overflow-y:auto; border-radius: 10px; border: 1px solid #e0e0e0;">
              <table class="table table-bordered table-hover align-middle mb-0">
                <thead style="position: sticky; top: 0; z-index: 100; background: #14ad0f; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <tr>
                  <th class="text-white fw-semibold" style="background: #14ad0f; border-color: rgba(255,255,255,0.2); padding: 12px 8px; position: sticky; top: 0;">User ID</th>
                  <th class="text-white fw-semibold" style="background: #14ad0f; border-color: rgba(255,255,255,0.2); padding: 12px 8px; position: sticky; top: 0;">Resident Name</th>
                  <th class="text-white fw-semibold text-center" style="background: #14ad0f; border-color: rgba(255,255,255,0.2); padding: 12px 8px; white-space: nowrap; position: sticky; top: 0;">Barangay Clearance</th>
                  <th class="text-white fw-semibold text-center" style="background: #14ad0f; border-color: rgba(255,255,255,0.2); padding: 12px 8px; white-space: nowrap; position: sticky; top: 0;">Certificate of Indigency</th>
                  <th class="text-white fw-semibold text-center" style="background: #14ad0f; border-color: rgba(255,255,255,0.2); padding: 12px 8px; white-space: nowrap; position: sticky; top: 0;">Certificate of Residency</th>
                  <th class="text-white fw-semibold text-center" style="background: #14ad0f; border-color: rgba(255,255,255,0.2); padding: 12px 8px; white-space: nowrap; position: sticky; top: 0;">Total Requests</th>
                </tr>
              </thead>
               <tbody id="summaryTableBody">
                <?php if ($summaryResult->num_rows === 0): ?>
                  <tr>
                    <td colspan="6" class="text-center text-muted">No requests found</td>
                  </tr>
                <?php else: ?>
                  <?php while ($row = $summaryResult->fetch_assoc()): ?>
                    <tr>
                      <td><?= htmlspecialchars($row['userid']) ?></td>
                      <td><?= htmlspecialchars($row['resident_name']) ?></td>
                      <td class="text-center"><?= $row['barangay_clearance'] ?></td>
                      <td class="text-center"><?= $row['indigency'] ?></td>
                      <td class="text-center"><?= $row['residency'] ?></td>
                      <td class="text-center"><strong><?= $row['total_requests'] ?></strong></td>
                    </tr>
                  <?php endwhile; ?>
                <?php endif; ?>
              </tbody>
            </table>
            </div>
          </div>
          <!-- Footer with Close button -->
          <div style="padding: 20px 30px; border-top: 1px solid #e0e0e0; background: #f8f9fa; border-radius: 0 0 16px 16px; flex-shrink:0;">
            <div class="text-center">
              <button type="button" id="closeSummaryModal" class="btn" style="background: linear-gradient(90deg,#14ad0f 0%,#43e97b 100%); color: #fff; border: none; padding: 10px 30px; border-radius: 10px; font-weight: 600; box-shadow: 0 2px 12px rgba(20,173,15,0.13); transition: all 0.22s;">Close</button>
            </div>
          </div>
        </div>
      </div>
      <!-- Modal Overlay for Certificates -->
      <div id="certModalOverlay" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.5); z-index:2000;">
        <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:#fff; border-radius:16px; box-shadow:0 8px 32px rgba(20,173,15,0.2); padding:0; min-width:350px; max-width:600px; overflow:hidden;">
          <!-- Header with green gradient -->
          <div style="background: linear-gradient(135deg, #14ad0f 0%, #43e97b 100%); padding: 24px 30px; border-radius: 16px 16px 0 0;">
            <h3 class="mb-0 text-white fw-bold" style="font-size: 1.4rem; letter-spacing: 0.5px;">⚙️ Enable/Disable Certificates</h3>
          </div>
          <div style="padding: 30px;">
            <form method="POST" action="admin_dashboard.php?panel=certificates" id="cert-options-form">
              <?php while($row = $certOptions->fetch_assoc()): ?>
                <div class="form-check mb-3" style="padding: 12px; border-radius: 8px; background: rgba(20,173,15,0.05); border: 1px solid rgba(20,173,15,0.2);">
                  <input class="form-check-input" type="checkbox" name="enabled[]" value="<?= $row['id'] ?>"
                      id="cert<?= $row['id'] ?>" <?= $row['is_enabled'] ? 'checked' : '' ?> style="border: 2px solid #14ad0f; width: 20px; height: 20px;">
                  <label class="form-check-label fw-semibold" for="cert<?= $row['id'] ?>" style="margin-left: 8px; color: #333; font-size: 1.05rem;">
                    <?= htmlspecialchars($row['certificate_name']) ?>
                  </label>
                </div>
              <?php endwhile; ?>
              <div class="text-center mt-4">
                <button type="submit" class="btn" id="save-cert-options-btn" style="background: linear-gradient(90deg,#14ad0f 0%,#43e97b 100%); color: #fff; border: none; padding: 10px 30px; border-radius: 10px; font-weight: 600; box-shadow: 0 2px 12px rgba(20,173,15,0.13); transition: all 0.22s; margin-right: 10px;">Save Changes</button>
                <button type="button" id="closeCertModal" class="btn" style="background: #6c757d; color: #fff; border: none; padding: 10px 30px; border-radius: 10px; font-weight: 600; box-shadow: 0 2px 8px rgba(0,0,0,0.1); transition: all 0.22s;">Close</button>
              </div>
            </form>
            <div id="cert-options-message-area"></div>
          </div>
        </div>
      </div>

      <!-- Modal Overlay for Content Management -->
      <div id="contentManagementModalOverlay" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.5); z-index:2000;">
        <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:#fff; border-radius:16px; box-shadow:0 8px 32px rgba(20,173,15,0.2); padding:0; min-width:500px; max-width:700px; overflow:hidden;">
          <!-- Header with green gradient -->
          <div style="background: linear-gradient(135deg, #14ad0f 0%, #43e97b 100%); padding: 24px 30px; border-radius: 16px 16px 0 0;">
            <h3 class="mb-0 text-white fw-bold" style="font-size: 1.4rem; letter-spacing: 0.5px;">📝 Certificate Content Management</h3>
          </div>
          <div style="padding: 30px;">
            <form id="content-management-form">
              <div class="mb-3">
                <label for="barangay_captain" class="form-label fw-semibold" style="color: #333;">Barangay Captain</label>
                <input type="text" class="form-control" id="barangay_captain" name="barangay_captain" value="Hon. Kenneth S. Saria" style="border: 2px solid rgba(20,173,15,0.3); border-radius: 8px; padding: 10px;">
              </div>
              <div class="mb-3">
                <label for="barangay_name" class="form-label fw-semibold" style="color: #333;">Barangay Name</label>
                <input type="text" class="form-control" id="barangay_name" name="barangay_name" value="Barangay Sabang" style="border: 2px solid rgba(20,173,15,0.3); border-radius: 8px; padding: 10px;">
              </div>
              <div class="mb-3">
                <label for="city" class="form-label fw-semibold" style="color: #333;">City</label>
                <input type="text" class="form-control" id="city" name="city" value="Dasmariñas City, Cavite" style="border: 2px solid rgba(20,173,15,0.3); border-radius: 8px; padding: 10px;">
              </div>
              <div class="text-center mt-4">
                <button type="submit" class="btn" id="save-content-btn" style="background: linear-gradient(90deg,#14ad0f 0%,#43e97b 100%); color: #fff; border: none; padding: 10px 30px; border-radius: 10px; font-weight: 600; box-shadow: 0 2px 12px rgba(20,173,15,0.13); transition: all 0.22s; margin-right: 10px;">Save Changes</button>
                <button type="button" id="closeContentModal" class="btn" style="background: #6c757d; color: #fff; border: none; padding: 10px 30px; border-radius: 10px; font-weight: 600; box-shadow: 0 2px 8px rgba(0,0,0,0.1); transition: all 0.22s;">Close</button>
              </div>
            </form>
            <div id="content-management-message-area"></div>
          </div>
        </div>
      </div>

      <script>
      // Modal logic for Summary
      const summaryBtn = document.getElementById('toggleSummaryBtn');
      const summaryModalOverlay = document.getElementById('summaryModalOverlay');
      const closeSummaryModal = document.getElementById('closeSummaryModal');
      const summarySearchInput = document.getElementById('summarySearchInput');
      const summaryTableBody = document.getElementById('summaryTableBody');
      
      summaryBtn.addEventListener('click', () => {
        summaryModalOverlay.style.display = 'block';
      });
      
      closeSummaryModal.addEventListener('click', () => {
        summaryModalOverlay.style.display = 'none';
      });
      
      // Close modal when clicking outside
      summaryModalOverlay.addEventListener('click', (e) => {
        if (e.target === summaryModalOverlay) {
          summaryModalOverlay.style.display = 'none';
        }
      });
      
      // Summary search functionality
      function searchSummary() {
        const searchValue = summarySearchInput.value.toLowerCase().trim();
        const rows = summaryTableBody.querySelectorAll('tr');
        
        let visibleCount = 0;
        rows.forEach(row => {
          const cells = row.querySelectorAll('td');
          if (cells.length > 1) { // Not the "no results" row
            const userId = cells[0].textContent.toLowerCase();
            const residentName = cells[1].textContent.toLowerCase();
            
            if (userId.includes(searchValue) || residentName.includes(searchValue)) {
              row.style.display = '';
              visibleCount++;
            } else {
              row.style.display = 'none';
            }
          }
        });
        
        // Show "no results" message if no rows are visible
        if (visibleCount === 0 && rows.length > 0) {
          const noResultRow = rows[0];
          if (noResultRow.querySelector('td[colspan]')) {
            noResultRow.style.display = '';
          }
        }
      }
      
      // Real-time search as user types
      summarySearchInput.addEventListener('input', searchSummary);
      
      // Modal logic for Enable/Disable Certificates
      const certOptionsBtn = document.getElementById('toggleCertOptionsBtn');
      const certModalOverlay = document.getElementById('certModalOverlay');
      const closeCertModal = document.getElementById('closeCertModal');
      
      certOptionsBtn.addEventListener('click', () => {
        certModalOverlay.style.display = 'block';
      });
      
      closeCertModal.addEventListener('click', () => {
        certModalOverlay.style.display = 'none';
      });
      
      // Close modal when clicking outside
      certModalOverlay.addEventListener('click', (e) => {
        if (e.target === certModalOverlay) {
          certModalOverlay.style.display = 'none';
        }
      });
      
      // Handle Enable/Disable Certificates form submission
      var certOptionsForm = document.getElementById('cert-options-form');
      if (certOptionsForm) {
        certOptionsForm.addEventListener('submit', function(e) {
          e.preventDefault();
          
          var saveBtn = document.getElementById('save-cert-options-btn');
          var originalText = saveBtn.innerHTML;
          saveBtn.disabled = true;
          saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';
          
          var formData = new FormData(certOptionsForm);
          
          fetch('admin_dashboard.php?panel=certificates', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
              'X-Requested-With': 'XMLHttpRequest'
            }
          })
          .then(function(response) { return response.json(); })
          .then(function(data) {
            console.log('Certificate update response:', data);
            if (data.success) {
              // Show success message with enhanced styling
              var successMsg = document.createElement('div');
              successMsg.className = 'alert alert-success';
              successMsg.innerHTML = '<strong>✓ Success!</strong> ' + data.message + ' Changes will be reflected on the index page.';
              successMsg.style.position = 'fixed';
              successMsg.style.top = '20px';
              successMsg.style.right = '20px';
              successMsg.style.zIndex = '9999';
              successMsg.style.minWidth = '300px';
              successMsg.style.maxWidth = '500px';
              successMsg.style.boxShadow = '0 4px 18px rgba(67, 233, 123, 0.3)';
              successMsg.style.borderRadius = '12px';
              successMsg.style.border = 'none';
              successMsg.style.background = 'linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%)';
              successMsg.style.color = '#065f46';
              successMsg.style.fontWeight = '500';
              successMsg.style.padding = '16px 20px';
              successMsg.style.animation = 'slideInRight 0.4s ease-out';
              document.body.appendChild(successMsg);
              
              // Add slide-in/out animations if not already defined
              if (!document.getElementById('cert-success-animation-style')) {
                var style = document.createElement('style');
                style.id = 'cert-success-animation-style';
                style.textContent = '@keyframes slideInRight { from { transform: translateX(400px); opacity: 0; } to { transform: translateX(0); opacity: 1; } } @keyframes slideOutRight { from { transform: translateX(0); opacity: 1; } to { transform: translateX(400px); opacity: 0; } }';
                document.head.appendChild(style);
              }
              
              // Close modal
              certModalOverlay.style.display = 'none';
              
              saveBtn.disabled = false;
              saveBtn.innerHTML = originalText;
              
              // Auto-hide popup after display
              setTimeout(function() {
                successMsg.style.animation = 'slideOutRight 0.4s ease-in';
                setTimeout(function() {
                  successMsg.remove();
                }, 400);
              }, 3000);
            } else {
              throw new Error('Update failed');
            }
          })
          .catch(function(error) {
            console.error('Error updating certificate options:', error);
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
          });
        });
      }

      // Modal logic for Content Management
      const contentManagementBtn = document.getElementById('toggleContentManagementBtn');
      const contentManagementModalOverlay = document.getElementById('contentManagementModalOverlay');
      const closeContentModal = document.getElementById('closeContentModal');
      
      contentManagementBtn.addEventListener('click', () => {
        // Load current values from database
        fetch('admin_dashboard.php?panel=certificates&action=get_certificate_content', {
          method: 'GET',
          credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
          if (data.success && data.content) {
            document.getElementById('barangay_captain').value = data.content.barangay_captain || 'Hon. Kenneth S. Saria';
            document.getElementById('barangay_name').value = data.content.barangay_name || 'Barangay Sabang';
            document.getElementById('city').value = data.content.city || 'Dasmariñas City, Cavite';
          }
        })
        .catch(error => console.error('Error loading content:', error));
        
        contentManagementModalOverlay.style.display = 'block';
      });
      
      closeContentModal.addEventListener('click', () => {
        contentManagementModalOverlay.style.display = 'none';
      });
      
      // Close modal when clicking outside
      contentManagementModalOverlay.addEventListener('click', (e) => {
        if (e.target === contentManagementModalOverlay) {
          contentManagementModalOverlay.style.display = 'none';
        }
      });
      
      // Handle Content Management form submission
      var contentManagementForm = document.getElementById('content-management-form');
      if (contentManagementForm) {
        contentManagementForm.addEventListener('submit', function(e) {
          e.preventDefault();
          
          var saveBtn = document.getElementById('save-content-btn');
          var originalText = saveBtn.innerHTML;
          saveBtn.disabled = true;
          saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';
          
          var formData = new FormData(contentManagementForm);
          formData.append('action', 'update_certificate_content');
          
          fetch('admin_dashboard.php?panel=certificates', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
              'X-Requested-With': 'XMLHttpRequest'
            }
          })
          .then(function(response) { return response.json(); })
          .then(function(data) {
            console.log('Content update response:', data);
            if (data.success) {
              // Show success message
              var successMsg = document.createElement('div');
              successMsg.className = 'alert alert-success';
              successMsg.innerHTML = '<strong>✓ Success!</strong> ' + data.message;
              successMsg.style.position = 'fixed';
              successMsg.style.top = '20px';
              successMsg.style.right = '20px';
              successMsg.style.zIndex = '9999';
              successMsg.style.minWidth = '300px';
              successMsg.style.maxWidth = '500px';
              successMsg.style.boxShadow = '0 4px 18px rgba(67, 233, 123, 0.3)';
              successMsg.style.borderRadius = '12px';
              successMsg.style.border = 'none';
              successMsg.style.background = 'linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%)';
              successMsg.style.color = '#065f46';
              successMsg.style.fontWeight = '500';
              successMsg.style.padding = '16px 20px';
              successMsg.style.animation = 'slideInRight 0.4s ease-out';
              document.body.appendChild(successMsg);
              
              // Close modal
              contentManagementModalOverlay.style.display = 'none';
              
              saveBtn.disabled = false;
              saveBtn.innerHTML = originalText;
              
              // Auto-hide popup after display
              setTimeout(function() {
                successMsg.style.animation = 'slideOutRight 0.4s ease-in';
                setTimeout(function() {
                  successMsg.remove();
                }, 400);
              }, 3000);
            } else {
              throw new Error(data.message || 'Update failed');
            }
          })
          .catch(function(error) {
            console.error('Error updating certificate content:', error);
            
            // Show error message
            var errorMsg = document.createElement('div');
            errorMsg.className = 'alert alert-danger';
            errorMsg.innerHTML = '<strong>✗ Error!</strong> ' + error.message;
            errorMsg.style.position = 'fixed';
            errorMsg.style.top = '20px';
            errorMsg.style.right = '20px';
            errorMsg.style.zIndex = '9999';
            errorMsg.style.minWidth = '300px';
            errorMsg.style.maxWidth = '500px';
            errorMsg.style.boxShadow = '0 4px 18px rgba(239, 68, 68, 0.3)';
            errorMsg.style.borderRadius = '12px';
            errorMsg.style.border = 'none';
            errorMsg.style.background = 'linear-gradient(135deg, #fee2e2 0%, #fecaca 100%)';
            errorMsg.style.color = '#991b1b';
            errorMsg.style.fontWeight = '500';
            errorMsg.style.padding = '16px 20px';
            document.body.appendChild(errorMsg);
            
            setTimeout(function() {
              errorMsg.remove();
            }, 3000);
            
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
          });
        });
      }

// Auto-refresh certificates table when there are new entries
var lastCertCheckTime = null;
var certAutoRefreshInterval = null;

function checkForNewCertificates() {
  // Only check if we're on the certificates panel
  var certPanel = document.getElementById('panel-certificates');
  if (!certPanel || certPanel.style.display === 'none') {
    return;
  }
  
  var params = new URLSearchParams();
  params.set('action', 'check_new');
  if (lastCertCheckTime) {
    params.set('last_check', lastCertCheckTime);
  }
  
  fetch('check_new_certificates.php?' + params.toString(), {
    method: 'GET',
    credentials: 'same-origin'
  })
  .then(function(response) { return response.json(); })
  .then(function(data) {
    if (data.success) {
      // Update last check time
      if (data.latest_time) {
        lastCertCheckTime = data.latest_time;
      }
      
      // If there are new certificates, refresh the table
      if (data.has_new) {
        console.log('New certificate(s) detected. Refreshing table...');
        reloadCertTable();
        
        // Show notification
        var notification = document.createElement('div');
        notification.className = 'alert alert-info';
        notification.innerHTML = '<strong><i class="bi bi-info-circle"></i> New Certificate Request!</strong> The table has been updated with new entries.';
        notification.style.position = 'fixed';
        notification.style.top = '20px';
        notification.style.right = '20px';
        notification.style.zIndex = '9999';
        notification.style.minWidth = '300px';
        document.body.appendChild(notification);
        
        setTimeout(function() { 
          notification.remove(); 
        }, 4000);
      }
      
      // Update pending count badge if exists
      if (data.pending_count !== undefined) {
        var pendingBadge = document.querySelector('#cert-counts .badge.bg-warning');
        if (pendingBadge) {
          pendingBadge.textContent = data.pending_count;
        }
      }
    }
  })
  .catch(function(error) {
    console.error('Error checking for new certificates:', error);
  });
}

// Initialize auto-refresh when on certificates panel
document.addEventListener('DOMContentLoaded', function() {
  // Check if we're on certificates panel
  var urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('panel') === 'certificates') {
    // Initial check after 3 seconds
    setTimeout(function() {
      checkForNewCertificates();
      // Then check every 10 seconds
      certAutoRefreshInterval = setInterval(checkForNewCertificates, 10000);
    }, 3000);
  }
  
  // Start auto-refresh when navigating to certificates panel
  var toggleCertificatesBtn = document.getElementById('toggleCertificatesBtn');
  if (toggleCertificatesBtn) {
    toggleCertificatesBtn.addEventListener('click', function() {
      // Clear any existing interval
      if (certAutoRefreshInterval) {
        clearInterval(certAutoRefreshInterval);
      }
      // Start new interval after 3 seconds
      setTimeout(function() {
        checkForNewCertificates();
        certAutoRefreshInterval = setInterval(checkForNewCertificates, 10000);
      }, 3000);
    });
  }
});

// Stop auto-refresh when leaving certificates panel
function stopCertAutoRefresh() {
  if (certAutoRefreshInterval) {
    clearInterval(certAutoRefreshInterval);
    certAutoRefreshInterval = null;
  }
}

// Add event listeners to other panel buttons to stop cert auto-refresh
document.addEventListener('DOMContentLoaded', function() {
  var otherPanelButtons = [
    'toggleReportsBtn', 
    'toggleAnnouncementsBtn', 
    'toggleIncidentsBtn', 
    'toggleOfficialsBtn',
    'toggleResidentsBtn',
    'toggleSuggestionsBtn'
  ];
  
  otherPanelButtons.forEach(function(btnId) {
    var btn = document.getElementById(btnId);
    if (btn) {
      btn.addEventListener('click', stopCertAutoRefresh);
    }
  });
});
      </script>
      <!-- End manage_certificate.php HTML -->
    </div>
    </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
  <script>
    // Panel navigation logic
    document.addEventListener('DOMContentLoaded', function() {
      const panelWelcome = document.getElementById('panel-welcome');
      const panelReports = document.getElementById('panel-reports');
      const panelAnnouncements = document.getElementById('panel-announcements');
  const panelCertificates = document.getElementById('panel-certificates');
  const panelSuggestions = document.getElementById('panel-suggestions');
      const panelIncidents = document.getElementById('panel-incidents');
  const panelResidents = document.getElementById('panel-residents');
  const panelViewResidents = document.getElementById('panel-view-residents');
      const toggleReportsBtn = document.getElementById('toggleReportsBtn');
      const toggleAnnouncementsBtn = document.getElementById('toggleAnnouncementsBtn');
      const toggleCertificatesBtn = document.getElementById('toggleCertificatesBtn');
      const toggleIncidentsBtn = document.getElementById('toggleIncidentsBtn');
      const toggleOfficialsBtn = document.getElementById('toggleOfficialsBtn');
  const toggleResidentsBtn = document.getElementById('toggleResidentsBtn');
  const toggleSuggestionsBtn = document.getElementById('toggleSuggestionsBtn');

      window.showPanel = function(panel) {
        document.querySelectorAll('.panel-section').forEach(p => p.style.display = 'none');
        if (panel) panel.style.display = '';
        
        // Remove active class from all nav links
        document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
        
        // Add active class based on the panel shown
        if (panel) {
          const panelId = panel.id;
          let activeBtn = null;
          
          if (panelId === 'panel-welcome') {
            activeBtn = document.getElementById('toggleReportsBtn');
          } else if (panelId === 'panel-reports') {
            activeBtn = document.getElementById('toggleReportsBtn');
          } else if (panelId === 'panel-announcements') {
            activeBtn = document.getElementById('toggleAnnouncementsBtn');
          } else if (panelId === 'panel-certificates') {
            activeBtn = document.getElementById('toggleCertificatesBtn');
          } else if (panelId === 'panel-incidents') {
            activeBtn = document.getElementById('toggleIncidentsBtn');
          } else if (panelId === 'panel-officials') {
            activeBtn = document.getElementById('toggleOfficialsBtn');
          } else if (panelId === 'panel-residents') {
            activeBtn = document.getElementById('toggleResidentsBtn');
          } else if (panelId === 'panel-view-residents') {
            activeBtn = document.getElementById('toggleViewResidentsBtn');
          } else if (panelId === 'panel-add-residents') {
            activeBtn = document.getElementById('toggleAddResidentsBtn');
          } else if (panelId === 'panel-jobfinder') {
            activeBtn = document.getElementById('toggleJobfinderBtn');
          } else if (panelId === 'panel-register-admin') {
            activeBtn = document.getElementById('toggleManageAdminBtn');
          } else if (panelId === 'panel-manage-admin') {
            activeBtn = document.getElementById('toggleManageAdminBtn');
          } else if (panelId === 'panel-view-logs') {
            activeBtn = document.getElementById('toggleViewLogsBtn');
          } else if (panelId === 'panel-admin-chats') {
            activeBtn = document.getElementById('toggleAdminChatsBtn');
          } else if (panelId === 'panel-suggestions') {
            activeBtn = document.getElementById('toggleSuggestionsBtn');
          }
          
          if (activeBtn) {
            activeBtn.classList.add('active');
            
            // If it's a submenu item, also highlight the parent and expand submenu
            if (['panel-view-residents', 'panel-add-residents', 'panel-jobfinder', 'panel-register-admin', 'panel-manage-admin', 'panel-view-logs'].includes(panelId)) {
              const residentsBtn = document.getElementById('toggleResidentsBtn');
              const residentsSubMenu = document.getElementById('residentsSubMenu');
              const residentsArrow = document.getElementById('residentsArrow');
              
              if (residentsBtn) residentsBtn.classList.add('active');
              if (residentsSubMenu) residentsSubMenu.style.display = 'block';
              if (residentsArrow) residentsArrow.style.transform = 'rotate(90deg)';
            }
          }
        }
      }

      // Default: show welcome or panel from URL
      const urlParams = new URLSearchParams(window.location.search);
      const panelParam = urlParams.get('panel');
      if (panelParam === 'announcements') {
        showPanel(panelAnnouncements);
      } else if (panelParam === 'reports') {
        showPanel(panelReports);
      } else if (panelParam === 'certificates') {
        showPanel(panelCertificates);
      } else if (panelParam === 'incidents') {
        showPanel(panelIncidents);
      } else if (panelParam === 'officials') {
        showPanel(document.getElementById('panel-officials'));
      } else if (panelParam === 'residents') {
        showPanel(panelResidents);
      } else if (panelParam === 'view-residents') {
        showPanel(panelViewResidents);
      } else if (panelParam === 'add-residents') {
        showPanel(document.getElementById('panel-add-residents'));
      } else if (panelParam === 'edit-resident') {
        showPanel(document.getElementById('panel-edit-resident'));
      } else if (panelParam === 'register-admin') {
        showPanel(document.getElementById('panel-register-admin'));
      } else if (panelParam === 'manage-admin') {
        showPanel(document.getElementById('panel-manage-admin'));
      } else if (panelParam === 'suggestions') {
        showPanel(panelSuggestions);
      } else if (panelParam === 'jobfinder') {
        showPanel(document.getElementById('panel-jobfinder'));
      } else if (panelParam === 'chatbot-cms') {
        showPanel(document.getElementById('panel-chatbot-cms'));
      } else if (panelParam === 'admin-chats') {
        showPanel(document.getElementById('panel-admin-chats'));
      } else if (panelParam === 'admin-logs') {
        showPanel(document.getElementById('panel-admin-logs'));
        // Load admin logs table when panel is shown from URL
        setTimeout(() => {
          const start = document.getElementById('start')?.value || '';
          const end = document.getElementById('end')?.value || '';
          if (typeof loadAdminLogsTable === 'function') {
            loadAdminLogsTable(start, end);
          }
        }, 100);
      } else {
        showPanel(panelWelcome);
        // Play welcome sound when showing welcome panel on initial load
        playWelcomeSound();
      }

      // Function to play welcome sound
      function playWelcomeSound() {
        const audio = document.getElementById('welcomeAudio');
        if (audio) {
          // Reset audio to beginning
          audio.currentTime = 0;
          audio.volume = 0.7; // Set volume to 70%
          
          // Try to play immediately
          const playPromise = audio.play();
          
          if (playPromise !== undefined) {
            playPromise.then(function() {
              console.log('Welcome audio playing successfully');
            }).catch(function(error) {
              console.log('Autoplay prevented, trying with user interaction:', error);
              // Fallback: play on first user interaction
              const playOnInteraction = function() {
                audio.play().then(function() {
                  console.log('Welcome audio played after user interaction');
                }).catch(function(err) {
                  console.log('Audio play failed:', err);
                });
                // Remove listeners after first play
                document.removeEventListener('click', playOnInteraction);
                document.removeEventListener('keydown', playOnInteraction);
              };
              document.addEventListener('click', playOnInteraction, { once: true });
              document.addEventListener('keydown', playOnInteraction, { once: true });
            });
          }
        }
      }

      // Sidebar navigation
      const toggleDashboardBtn = document.getElementById('toggleDashboardBtn');
      if (toggleDashboardBtn) {
        toggleDashboardBtn.addEventListener('click', function(e) {
          e.preventDefault();
          showPanel(document.getElementById('panel-welcome'));
          history.replaceState(null, '', 'admin_dashboard.php');
        });
      }
      
      toggleReportsBtn.addEventListener('click', function(e) {
        e.preventDefault();
        showPanel(panelReports);
        history.replaceState(null, '', '?panel=reports');
      });
      if (toggleAnnouncementsBtn) {
        toggleAnnouncementsBtn.addEventListener('click', function(e) {
          e.preventDefault();
          showPanel(panelAnnouncements);
          history.replaceState(null, '', '?panel=announcements');
        });
      }
      if (toggleCertificatesBtn) {
        toggleCertificatesBtn.addEventListener('click', function(e) {
          e.preventDefault();
          showPanel(panelCertificates);
          history.replaceState(null, '', '?panel=certificates');
        });
      }
      if (toggleIncidentsBtn) {
        toggleIncidentsBtn.addEventListener('click', function(e) {
          e.preventDefault();
          showPanel(panelIncidents);
          history.replaceState(null, '', '?panel=incidents');
        });
      }
      if (toggleOfficialsBtn) {
        toggleOfficialsBtn.addEventListener('click', function(e) {
          e.preventDefault();
          showPanel(document.getElementById('panel-officials'));
          history.replaceState(null, '', '?panel=officials');
        });
      }
      if (toggleResidentsBtn) {
        toggleResidentsBtn.addEventListener('click', function(e) {
          e.preventDefault();
          showPanel(panelResidents);
          history.replaceState(null, '', '?panel=residents');
        });
      }
      const toggleViewResidentsBtn = document.getElementById('toggleViewResidentsBtn');
      if (toggleViewResidentsBtn) {
        toggleViewResidentsBtn.addEventListener('click', function(e) {
          e.preventDefault();
          showPanel(panelViewResidents);
          history.replaceState(null, '', '?panel=view-residents');
        });
      }
      const toggleAddResidentsBtn = document.getElementById('toggleAddResidentsBtn');
      if (toggleAddResidentsBtn) {
        toggleAddResidentsBtn.addEventListener('click', function(e) {
          e.preventDefault();
          showPanel(document.getElementById('panel-add-residents'));
          history.replaceState(null, '', '?panel=add-residents');
        });
      }
      const toggleJobfinderBtn = document.getElementById('toggleJobfinderBtn');
      if (toggleJobfinderBtn) {
        toggleJobfinderBtn.addEventListener('click', function(e) {
          e.preventDefault();
          showPanel(document.getElementById('panel-jobfinder'));
          history.replaceState(null, '', '?panel=jobfinder');
          // Reinitialize icons after panel is shown
          setTimeout(() => lucide.createIcons(), 100);
        });
      }
      if (toggleSuggestionsBtn) {
        toggleSuggestionsBtn.addEventListener('click', function(e) {
          e.preventDefault();
          showPanel(panelSuggestions);
          history.replaceState(null, '', '?panel=suggestions');
        });
      }
      
      // Admin Chats button
      const toggleAdminChatsBtn = document.getElementById('toggleAdminChatsBtn');
      if (toggleAdminChatsBtn) {
        toggleAdminChatsBtn.addEventListener('click', function(e) {
          e.preventDefault();
          showPanel(document.getElementById('panel-admin-chats'));
          history.replaceState(null, '', '?panel=admin-chats');
        });
      }
      
      // Manage Admin button
      const toggleManageAdminBtn = document.getElementById('toggleManageAdminBtn');
      if (toggleManageAdminBtn) {
        toggleManageAdminBtn.addEventListener('click', function(e) {
          e.preventDefault();
          showPanel(document.getElementById('panel-manage-admin'));
          history.replaceState(null, '', '?panel=manage-admin');
        });
      }
      
      // Register Admin button
      const toggleRegisterAdminBtn = document.getElementById('toggleRegisterAdminBtn');
      if (toggleRegisterAdminBtn) {
        toggleRegisterAdminBtn.addEventListener('click', function(e) {
          e.preventDefault();
          showPanel(document.getElementById('panel-register-admin'));
          history.replaceState(null, '', '?panel=register-admin');
        });
      }
      
      // View Logs button
      const toggleViewLogsBtn = document.getElementById('toggleViewLogsBtn');
      if (toggleViewLogsBtn) {
        toggleViewLogsBtn.addEventListener('click', function(e) {
          e.preventDefault();
          showPanel(document.getElementById('panel-admin-logs'));
          history.replaceState(null, '', '?panel=admin-logs');
          // Load admin logs table when panel is shown
          setTimeout(() => {
            const start = document.getElementById('start')?.value || '';
            const end = document.getElementById('end')?.value || '';
            loadAdminLogsTable(start, end);
          }, 100);
        });
      }
      
      // Optionally, highlight active sidebar item
    });
    // Clock
    setInterval(() => {
      document.getElementById('clock').textContent = new Date().toLocaleTimeString([], {hour:'2-digit',minute:'2-digit',second:'2-digit'});
    }, 1000);

    // Top 5 Incidents by Type (Current Month)
    new Chart(document.getElementById('topIncidentsChart'), {
      type:'bar',
      data:{
        labels:<?=json_encode($topIncidentTypes)?>,
        datasets:[{
          label:'Incidents',
          data:<?=json_encode($topIncidentCounts)?>,
          backgroundColor:'rgba(211, 47, 47, 0.8)',
          borderColor:'#d32f2f',
          borderWidth:2,
          borderRadius:8
        }]
      },
      options:{
        indexAxis:'y',
        responsive:true,
        maintainAspectRatio:true,
        plugins:{
          legend:{display:false},
          tooltip:{
            callbacks:{
              label:function(ctx){
                return `${ctx.label}: ${ctx.raw} incident${ctx.raw !== 1 ? 's' : ''}`;
              }
            }
          }
        },
        scales:{
          x:{
            beginAtZero:true,
            ticks:{precision:0},
            grid:{color:'rgba(0,0,0,0.05)'}
          },
          y:{
            grid:{display:false}
          }
        }
      }
    });

    // Residents & Accounts - Update stat cards
    const maleCount = <?=$resData['male_count']?>;
    const femaleCount = <?=$resData['female_count']?>;
    const seniorCount = <?=$resData['senior_count']?>;
    const pwdCount = <?=$resData['pwd_count']?>;
    const householdCount = <?=$resData['household_count']??0?>;
    const notLoginAccounts = <?=$data[0]??0?>;
    const loginAccounts = <?=$data[1]??0?>;
    
    document.getElementById('totalResidents').textContent = (maleCount + femaleCount).toLocaleString();
    document.getElementById('activeAccounts').textContent = loginAccounts.toLocaleString();
    document.getElementById('householdCount').textContent = householdCount.toLocaleString();
    document.getElementById('seniorCount').textContent = seniorCount.toLocaleString();
    document.getElementById('pwdCount').textContent = pwdCount.toLocaleString();
    
    new Chart(document.getElementById('combinedChart'), {
      type:'bar',
      data:{
        labels:['Male','Female','Households','Senior Citizens','PWD','Inactive Accounts','Active Accounts'],
        datasets:[{
          label:'Count',
          data:[maleCount, femaleCount, householdCount, seniorCount, pwdCount, notLoginAccounts, loginAccounts],
          backgroundColor:[
            'rgba(102,126,234,0.85)',
            'rgba(240,147,251,0.85)',
            'rgba(76,175,80,0.85)',
            'rgba(79,172,254,0.85)',
            'rgba(250,112,154,0.85)',
            'rgba(189,189,189,0.85)',
            'rgba(118,75,162,0.85)'
          ],
          borderColor:[
            'rgba(102,126,234,1)',
            'rgba(240,147,251,1)',
            'rgba(76,175,80,1)',
            'rgba(79,172,254,1)',
            'rgba(250,112,154,1)',
            'rgba(189,189,189,1)',
            'rgba(118,75,162,1)'
          ],
          borderWidth:2,
          borderRadius:10,
          hoverBackgroundColor:[
            'rgba(102,126,234,1)',
            'rgba(240,147,251,1)',
            'rgba(76,175,80,1)',
            'rgba(79,172,254,1)',
            'rgba(250,112,154,1)',
            'rgba(189,189,189,1)',
            'rgba(118,75,162,1)'
          ]
        }]
      },
      options:{
        responsive:true,
        maintainAspectRatio:false,
        plugins:{
          legend:{display:false},
          tooltip:{
            backgroundColor:'rgba(0,0,0,0.8)',
            padding:12,
            titleFont:{size:14,weight:'bold'},
            bodyFont:{size:13},
            cornerRadius:8
          }
        },
        scales:{
          y:{
            beginAtZero:true,
            grid:{color:'rgba(0,0,0,0.05)',drawBorder:false},
            ticks:{font:{size:12},color:'#666'}
          },
          x:{
            grid:{display:false,drawBorder:false},
            ticks:{font:{size:12},color:'#666'}
          }
        },
        animation:{
          duration:1000,
          easing:'easeOutQuart'
        }
      }
    });

    // Combined Requests per Month & Incident Reports Trend
    // Requests per Month Chart
    const reqChartCtx = document.getElementById('requestsChart').getContext('2d');
    const reqMonths = <?=json_encode($months)?>;
    const reqCounts = <?=json_encode($monthCounts)?>;
    new Chart(reqChartCtx, {
      type: 'line',
      data: {
        labels: reqMonths,
        datasets: [{
          label: 'Requests per Month',
          data: reqCounts,
          borderColor: '#1976d2',
          backgroundColor: 'rgba(25, 118, 210, 0.07)',
          fill: false,
          borderWidth: 3,
          tension: 0.5,
          pointBackgroundColor: '#1976d2',
          pointBorderColor: '#fff',
          pointRadius: 6,
          pointHoverRadius: 8
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { position: 'top', labels: { font: { size: 15, family: "'Segoe UI', Arial, sans-serif" }, boxWidth: 22, padding: 18 } },
          tooltip: { backgroundColor: '#fff', titleColor: '#333', bodyColor: '#333', borderColor: '#1976d2', borderWidth: 1, padding: 12, titleFont: { size: 16, weight: 'bold' }, bodyFont: { size: 15 } }
        },
        scales: {
          x: { grid: { color: '#e0e0e0', borderColor: '#bdbdbd' }, ticks: { font: { size: 14 } } },
          y: { beginAtZero: true, grid: { color: '#e0e0e0', borderColor: '#bdbdbd' }, ticks: { font: { size: 14 } } }
        }
      }
    });

    // Incident Reports per Month Chart
    const incChartCtx = document.getElementById('incidentsChart').getContext('2d');
    const incMonths = <?=json_encode($incidentMonths)?>;
    const incCounts = <?=json_encode($incidentCounts)?>;
    new Chart(incChartCtx, {
      type: 'line',
      data: {
        labels: incMonths,
        datasets: [{
          label: 'Incident Reports',
          data: incCounts,
          borderColor: '#e53935',
          backgroundColor: 'rgba(229, 57, 53, 0.07)',
          fill: false,
          borderWidth: 3,
          tension: 0.5,
          pointBackgroundColor: '#e53935',
          pointBorderColor: '#fff',
          pointRadius: 6,
          pointHoverRadius: 8
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { position: 'top', labels: { font: { size: 15, family: "'Segoe UI', Arial, sans-serif" }, boxWidth: 22, padding: 18 } },
          tooltip: { backgroundColor: '#fff', titleColor: '#333', bodyColor: '#333', borderColor: '#e53935', borderWidth: 1, padding: 12, titleFont: { size: 16, weight: 'bold' }, bodyFont: { size: 15 } }
        },
        scales: {
          x: { grid: { color: '#e0e0e0', borderColor: '#bdbdbd' }, ticks: { font: { size: 14 } } },
          y: { beginAtZero: true, grid: { color: '#e0e0e0', borderColor: '#bdbdbd' }, ticks: { font: { size: 14 } } }
        }
      }
    });

    // Incident Trend
    const ictx=document.getElementById('incidentChart').getContext('2d');
    const grad=ictx.createLinearGradient(0,0,0,400);grad.addColorStop(0,'rgba(75,192,192,0.4)');grad.addColorStop(1,'rgba(75,192,192,0)');
    new Chart(ictx,{type:'line',data:{labels:<?=json_encode($incidentMonths)?>,datasets:[{label:'Incidents',data:<?=json_encode($incidentCounts)?>,fill:true,backgroundColor:grad,borderColor:'#4bc0c0',borderWidth:3,tension:0.4,pointBackgroundColor:'#fff',pointBorderColor:'#4bc0c0',pointRadius:5}]},options:{plugins:{legend:{position:'top'}},scales:{y:{beginAtZero:true}}}});
  </script>
<!-- 🔔 Modern Red Popup Notification Modal -->
<!-- Per-priority sounds: urgent -> notif.mp3, moderate -> moderate_types.mp3, minor -> ChatNotif.mp3 -->
<audio id="urgentSound" src="notif.mp3" preload="auto"></audio>
<audio id="moderateSound" src="moderate_types.mp3" preload="auto"></audio>
<audio id="minorSound" src="ChatNotif.mp3" preload="auto"></audio>
<div class="modal fade" id="newReportModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header" style="background: linear-gradient(90deg, #e53935, #b71c1c); color: #fff; border-bottom: none;">
        <h5 class="modal-title">🚨 New Incident Report</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="incidentDetails" style="font-size: 0.95rem; line-height: 1.4; color:#333;">
        <!-- Dynamic content injected via JS -->
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
let modalEl = document.getElementById('newReportModal');
let modal = new bootstrap.Modal(modalEl);
let modalVisible = false;
let lastSeenReportIds = new Set(); // Track which reports we've already shown

// Function to refresh incident table
function refreshIncidentTable() {
    console.log("Refreshing incident table...");
    fetch('incident_table_partial.php')
        .then(res => res.text())
        .then(html => {
            const tbody = document.getElementById('incidentTableBody');
            if (tbody) {
                tbody.innerHTML = html;
                console.log("Incident table refreshed successfully");
            }
        })
        .catch(err => console.error("Error refreshing incident table:", err));
}

// Reset notifications function
function resetNotifications(callback) {
    fetch("check_new_reports.php?action=reset")
        .then(res => res.json())
        .then(data => {
            console.log("Reset notifications response:", data);
            if (data.success) {
                modalVisible = false; // allow modal to show again
                if (callback) callback();
            }
        })
        .catch(err => console.error("Reset error:", err));
}

// Attach hidden listener once (X button or modal close)
modalEl.addEventListener('hidden.bs.modal', () => {
    console.log("Modal closed - marking reports as seen");
    resetNotifications();
});

// Fetch new reports every 3 seconds
setInterval(() => {
    if(modalVisible) return; // skip if modal is open

    fetch("check_new_reports.php?action=check")
        .then(res => res.json())
        .then(data => {
            console.log("Check response:", data);
            const newReportsCount = Number(data.new_reports);
            
            // Check if we have truly new reports (not already shown)
            let hasNewReports = false;
            if (newReportsCount > 0 && data.reports.length > 0) {
                console.log("Found new reports from server, checking against local cache...");
                for (const rep of data.reports) {
                    console.log(`Report ID ${rep.id} - Already shown: ${lastSeenReportIds.has(rep.id)}`);
                    if (!lastSeenReportIds.has(rep.id)) {
                        hasNewReports = true;
                        lastSeenReportIds.add(rep.id);
                    }
                }
                console.log("hasNewReports:", hasNewReports);
            }
            
            if (hasNewReports) {
                console.log("Showing modal for new reports");
                
                // Refresh the incident table immediately
                refreshIncidentTable();
                
                let details = `<p style="font-weight:600; color:#b71c1c;">${newReportsCount} new report(s) submitted:</p><ul style="padding-left:18px;">`;
                data.reports.forEach(rep => {
                    details += `<li><strong style="color:#e53935;">${rep.incident_type}</strong>: ${rep.incident_description}</li>`;
                });
                details += `</ul><br><button type="button" id="viewReportsBtn" class="btn btn-sm btn-danger w-100">View Reports</button>`;

                document.getElementById("incidentDetails").innerHTML = details;


                const urgent_types = [
  // English
  'fire', 'fire incident', 'explosion', 'gas leak', 'chemical spill',
  'damage to property', 'assault', 'armed assault', 'homicide', 'murder',
  'shooting', 'stabbing', 'violent', 'emergency', 'medical', 'heart attack',
  'stroke', 'unconscious person', 'car accident', 'serious injury',
  'domestic violence', 'kidnapping', 'child abuse', 'sexual assault',
  'building collapse', 'natural disaster', 'earthquake', 'flood', 'electrocution',

  // Tagalog
  'sunog', 'pagsabog', 'tagas ng gas', 'pagtagas ng kemikal',
  'pinsala sa ari-arian', 'pananakit', 'armadong pananakit', 'pagpatay',
  'barilan', 'saksakan', 'karahasan', 'emerhensiya', 'medikal', 'atake sa puso',
  'stroke', 'walang malay', 'aksidente sa sasakyan', 'malubhang pinsala',
  'karahasan sa tahanan', 'pagdukot', 'pang-aabuso sa bata', 'panghahalay',
  'pagguho ng gusali', 'kalikasan', 'lindol', 'baha', 'kuryente'
];

const moderate_types = [
  // English
  'theft', 'vandalism', 'public disturbance', 'burglary', 'robbery',
  'damage', 'trespassing', 'hit and run', 'minor accident', 'property damage',
  'harassment', 'threat', 'missing person', 'fraud', 'illegal dumping',
  'shoplifting', 'verbal abuse', 'scam', 'identity theft', 'public intoxication',
  'illegal parking', 'reckless driving',

  // Tagalog
  'pagnanakaw', 'paninira', 'istorbo sa publiko', 'pagnanakaw sa bahay',
  'panghoholdap', 'pinsala', 'panggagambala', 'banggaan', 'nawalang tao',
  'panlilinlang', 'basurang itinatapon', 'pandurukot', 'pang-aasar', 'banta',
  'panloloko', 'pag-inom sa publiko', 'illegal na paradahan', 'pabaya sa pagmamaneho'
];

const minor_types = [
  // English
  'noise', 'noise complaint', 'minor', 'loitering', 'littering',
  'public nuisance', 'lost item', 'animal complaint', 'barking dog',
  'illegal posting', 'curfew violation', 'jaywalking', 'unauthorized selling',
  'other', 'graffiti', 'disorderly conduct', 'drunk in public',
  'trespassing (non-violent)', 'neighborhood dispute', 'unauthorized entry (non-violent)',

  // Tagalog
  'ingay', 'reklamo sa ingay', 'maliit', 'paglalaboy', 'pagtatapon ng basura',
  'istorbo', 'nawalang gamit', 'reklamo sa hayop', 'tahol ng aso',
  'illegal na poster', 'labag sa curfew', 'tumawid sa maling daan',
  'walang permit na tindero', 'iba pa', 'graffiti', 'gulo sa publiko',
  'lasing sa publiko', 'panggugulo', 'alitan sa kapitbahay', 'hindi awtorisadong pagpasok'
];
                function classifyReports(reports) {
                  let found = 'minor';
                  for (const r of reports) {
                    const it = (r.incident_type || '').toString().toLowerCase();
                    for (const kw of urgent_types) if (kw && it.indexOf(kw) !== -1) return 'urgent';
                    for (const kw of moderate_types) if (kw && it.indexOf(kw) !== -1) found = 'moderate';
                    for (const kw of minor_types) if (kw && it.indexOf(kw) !== -1 && found !== 'moderate') found = 'minor';
                  }
                  return found;
                }

                const priority = classifyReports(data.reports || []);
                // update modal header color based on priority
                const headerEl = modalEl.querySelector('.modal-header');
                if (headerEl) {
                  if (priority === 'urgent') {
                    headerEl.style.background = 'linear-gradient(90deg, #e53935, #b71c1c)';
                    headerEl.style.color = '#fff';
                  } else if (priority === 'moderate') {
                    headerEl.style.background = 'linear-gradient(90deg, #fb8c00, #e65100)';
                    headerEl.style.color = '#fff';
                  } else {
                    headerEl.style.background = 'linear-gradient(90deg, #ffeb3b, #fbc02d)';
                    headerEl.style.color = '#000';
                  }
                }

                // Show modal and play appropriate sound
                modal.show();
                modalVisible = true;
                const soundMap = {
                  'urgent': document.getElementById('urgentSound'),
                  'moderate': document.getElementById('moderateSound'),
                  'minor': document.getElementById('minorSound')
                };
                const chosenSound = soundMap[priority] || soundMap['minor'];
                if (chosenSound) {
                  chosenSound.currentTime = 0;
                  const playPromise = chosenSound.play();
                  if (playPromise !== undefined) {
                    playPromise.catch(() => {
                      document.body.addEventListener('click', function playOnce() {
                        chosenSound.play();
                        document.body.removeEventListener('click', playOnce);
                      });
                    });
                  }
                }

                // Use event delegation on the modal body instead of direct button click
                // This ensures the handler works even if button is dynamically created
                const modalBody = document.getElementById("incidentDetails");
                if (modalBody) {
                  // Remove any existing listeners first
                  const oldHandler = modalBody.getAttribute('data-click-attached');
                  if (!oldHandler) {
                    modalBody.addEventListener('click', function(e) {
                      console.log("Modal body clicked, target:", e.target, "target ID:", e.target.id);
                      if (e.target && e.target.id === 'viewReportsBtn') {
                        console.log("View Reports button detected!");
                        e.preventDefault();
                        e.stopPropagation();
                        console.log("Calling resetNotifications...");
                        resetNotifications(() => {
                          console.log("Reset complete, hiding modal...");
                          modal.hide();
                          // Switch to incidents panel without reloading - implement directly
                          const panelIncidents = document.getElementById('panel-incidents');
                          console.log("panelIncidents element:", panelIncidents);
                          if (panelIncidents) {
                            console.log("Switching to incidents panel...");
                            // Hide all panels
                            document.querySelectorAll('.panel-section').forEach(p => p.style.display = 'none');
                            // Show incidents panel
                            panelIncidents.style.display = '';
                            // Update URL
                            history.replaceState(null, '', '?panel=incidents');
                            console.log("Panel switched successfully!");
                          } else {
                            console.error("Cannot find panel-incidents element");
                          }
                        });
                      }
                    });
                    modalBody.setAttribute('data-click-attached', 'true');
                    console.log("Event delegation attached to modal body");
                  }
                }
            }
        })
        .catch(err => console.error("Fetch error:", err));
}, 3000);
</script>

<?php if (isset($_SESSION['flash'])): ?>
  <div id="flash-message" 
       style="background:#e0f7e9; color:#155724; padding:12px 20px; border-radius:8px;
              margin:15px auto; text-align:center; font-weight:500; max-width:600px;
              box-shadow:0 4px 12px rgba(0,0,0,0.1);">
      <?= $_SESSION['flash']; ?>
  </div>

  <script>
    setTimeout(() => {
      const flash = document.getElementById('flash-message');
      if (flash) {
        flash.style.transition = "opacity 0.6s ease, transform 0.6s ease";
        flash.style.opacity = "0";
        flash.style.transform = "translateY(-20px)";
        setTimeout(() => flash.remove(), 600);
      }
    }, 3000); // 3 seconds
  </script>
<?php unset($_SESSION['flash']); endif; ?>


<script>
// Responsive sidebar burger logic
document.addEventListener('DOMContentLoaded', function() {
  const burgerBtn = document.getElementById('burgerBtn');
  const sidebar = document.getElementById('sidebar');
  const sidebarBackdrop = document.getElementById('sidebarBackdrop');

  function openSidebar() {
    sidebar.classList.add('active');
    sidebarBackdrop.style.display = 'block';
    document.body.style.overflow = 'hidden';
  }
  function closeSidebar() {
    sidebar.classList.remove('active');
    sidebarBackdrop.style.display = 'none';
    document.body.style.overflow = '';
  }

  if (burgerBtn) {
    burgerBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      openSidebar();
    });
  }
  if (sidebarBackdrop) {
    sidebarBackdrop.addEventListener('click', function() {
      closeSidebar();
    });
  }
  // Close sidebar on navigation (mobile)
  document.querySelectorAll('.sidebar .nav-link').forEach(btn => {
    btn.addEventListener('click', () => {
      if (window.innerWidth <= 900) closeSidebar();
    });
  });
  // Hide sidebar on resize if desktop
  window.addEventListener('resize', () => {
    if (window.innerWidth > 900) {
      sidebar.classList.remove('active');
      sidebarBackdrop.style.display = 'none';
      document.body.style.overflow = '';
    }
  });
});
</script>

<!-- Floating To-Do List Widget -->
<?php
// Fetch pending tasks data
$pendingIncidents = 0;
$pendingCertificates = 0;
$unreadMessages = 0;
$pendingChatReports = 0;

// Count unsolved incidents (Pending status)
$incidentQuery = $conn->query("SELECT COUNT(*) as count FROM incident_reports WHERE status = 'Pending'");
if ($incidentQuery) {
    $pendingIncidents = $incidentQuery->fetch_assoc()['count'];
}

// Count pending and approved certificates (Pending and Approved status)
$certQuery = $conn->query("SELECT COUNT(*) as count FROM certificate_requests WHERE status IN ('Pending', 'Approved')");
if ($certQuery) {
    $pendingCertificates = $certQuery->fetch_assoc()['count'];
}

// Count unread messages sent to admin (from users in admin_chats table)
$messageQuery = $conn->query("SELECT COUNT(*) as count FROM admin_chats WHERE sender = 'user' AND is_read = 0");
if ($messageQuery) {
    $unreadMessages = $messageQuery->fetch_assoc()['count'];
}

// Count pending chat reports (Pending and Reviewed status)
$chatReportsQuery = $conn->query("SELECT COUNT(*) as count FROM chat_reports WHERE status IN ('pending', 'reviewed')");
if ($chatReportsQuery) {
    $pendingChatReports = $chatReportsQuery->fetch_assoc()['count'];
}

$totalTasks = $pendingIncidents + $pendingCertificates + $unreadMessages + $pendingChatReports;
?>

<div id="floatingTodoWidget" style="position:fixed; bottom:30px; right:30px; z-index:9999; transition:all 0.3s ease;">
  <!-- Floating Button -->
  <button id="todoToggleBtn" onclick="toggleTodoList()" style="
    width:60px;
    height:60px;
    border-radius:50%;
    background:linear-gradient(135deg, #14ad0f 0%, #43e97b 100%);
    border:none;
    box-shadow:0 8px 24px rgba(20,173,15,0.4);
    cursor:pointer;
    display:flex;
    align-items:center;
    justify-content:center;
    position:relative;
    transition:all 0.3s ease;
  " onmouseover="this.style.transform='scale(1.1) rotate(5deg)'" onmouseout="this.style.transform='scale(1) rotate(0deg)'">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
      <path d="M9 11l3 3L22 4"></path>
      <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
    </svg>
    <?php if ($totalTasks > 0): ?>
    <span id="todoBadge" style="
      position:absolute;
      top:-5px;
      right:-5px;
      background:#e53935;
      color:#fff;
      border-radius:50%;
      width:24px;
      height:24px;
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:0.75rem;
      font-weight:700;
      border:3px solid #fff;
      box-shadow:0 2px 8px rgba(229,57,53,0.4);
      animation:pulse 2s infinite;
    "><?= $totalTasks > 99 ? '99+' : $totalTasks ?></span>
    <?php else: ?>
    <span id="todoBadge" style="display:none;"></span>
    <?php endif; ?>
  </button>
  
  <!-- To-Do List Panel -->
  <div id="todoListPanel" style="
    position:absolute;
    bottom:75px;
    right:0;
    width:340px;
    max-height:500px;
    background:#fff;
    border-radius:16px;
    box-shadow:0 12px 40px rgba(0,0,0,0.15);
    display:none;
    flex-direction:column;
    overflow:hidden;
    animation:slideUp 0.3s ease;
  ">
    <!-- Header -->
    <div style="
      background:linear-gradient(135deg, #14ad0f 0%, #43e97b 100%);
      padding:16px 20px;
      color:#fff;
      display:flex;
      align-items:center;
      justify-content:space-between;
    ">
      <div style="display:flex; align-items:center; gap:10px;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5">
          <path d="M9 11l3 3L22 4"></path>
          <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
        </svg>
        <h3 style="margin:0; font-size:1.1rem; font-weight:700;">Pending Tasks</h3>
      </div>
      <button onclick="toggleTodoList()" style="
        background:rgba(255,255,255,0.2);
        border:none;
        color:#fff;
        width:28px;
        height:28px;
        border-radius:50%;
        cursor:pointer;
        display:flex;
        align-items:center;
        justify-content:center;
        transition:background 0.2s;
      " onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>
    
    <!-- Task List -->
    <div style="padding:16px; overflow-y:auto; max-height:400px;">
      <?php if ($totalTasks === 0): ?>
      <div style="text-align:center; padding:40px 20px; color:#999;">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5" style="margin-bottom:16px;">
          <path d="M9 11l3 3L22 4"></path>
          <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
        </svg>
        <p style="margin:0; font-size:1rem; font-weight:600; color:#666;">All caught up!</p>
        <p style="margin:4px 0 0 0; font-size:0.85rem; color:#999;">No pending tasks</p>
      </div>
      <?php else: ?>
        
        <?php if ($pendingIncidents > 0): ?>
        <div class="todo-item" onclick="navigateToPanel('panel-incidents')" style="
          background:#fff3cd;
          border-left:4px solid #ffc107;
          padding:14px;
          border-radius:10px;
          margin-bottom:12px;
          cursor:pointer;
          transition:all 0.2s;
        " onmouseover="this.style.transform='translateX(-4px)'; this.style.boxShadow='0 4px 12px rgba(255,193,7,0.2)'" onmouseout="this.style.transform='translateX(0)'; this.style.boxShadow='none'">
          <div style="display:flex; align-items:start; gap:12px;">
            <div style="
              width:40px;
              height:40px;
              min-width:40px;
              background:#ffc107;
              border-radius:10px;
              display:flex;
              align-items:center;
              justify-content:center;
            ">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
              </svg>
            </div>
            <div style="flex:1;">
              <div style="font-weight:700; color:#856404; font-size:0.95rem; margin-bottom:4px;">Unsolved Incidents</div>
              <div style="color:#856404; font-size:0.85rem; opacity:0.9;"><?= $pendingIncidents ?> incident<?= $pendingIncidents > 1 ? 's' : '' ?> need<?= $pendingIncidents > 1 ? '' : 's' ?> attention</div>
            </div>
            <div style="
              background:#ffc107;
              color:#fff;
              font-weight:700;
              font-size:0.9rem;
              padding:4px 10px;
              border-radius:20px;
              min-width:32px;
              text-align:center;
            "><?= $pendingIncidents ?></div>
          </div>
        </div>
        <?php endif; ?>
        
        <?php if ($pendingCertificates > 0): ?>
        <div class="todo-item" onclick="navigateToPanel('panel-certificates')" style="
          background:#e3f2fd;
          border-left:4px solid #2196f3;
          padding:14px;
          border-radius:10px;
          margin-bottom:12px;
          cursor:pointer;
          transition:all 0.2s;
        " onmouseover="this.style.transform='translateX(-4px)'; this.style.boxShadow='0 4px 12px rgba(33,150,243,0.2)'" onmouseout="this.style.transform='translateX(0)'; this.style.boxShadow='none'">
          <div style="display:flex; align-items:start; gap:12px;">
            <div style="
              width:40px;
              height:40px;
              min-width:40px;
              background:#2196f3;
              border-radius:10px;
              display:flex;
              align-items:center;
              justify-content:center;
            ">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
              </svg>
            </div>
            <div style="flex:1;">
              <div style="font-weight:700; color:#1565c0; font-size:0.95rem; margin-bottom:4px;">Pending & Approved Certificates</div>
              <div style="color:#1565c0; font-size:0.85rem; opacity:0.9;"><?= $pendingCertificates ?> certificate<?= $pendingCertificates > 1 ? 's' : '' ?> to process</div>
            </div>
            <div style="
              background:#2196f3;
              color:#fff;
              font-weight:700;
              font-size:0.9rem;
              padding:4px 10px;
              border-radius:20px;
              min-width:32px;
              text-align:center;
            "><?= $pendingCertificates ?></div>
          </div>
        </div>
        <?php endif; ?>
        
        <?php if ($pendingChatReports > 0): ?>
        <div class="todo-item" onclick="navigateToPanel('panel-jobfinder')" style="
          background:#fff8e1;
          border-left:4px solid #ff9800;
          padding:14px;
          border-radius:10px;
          margin-bottom:12px;
          cursor:pointer;
          transition:all 0.2s;
        " onmouseover="this.style.transform='translateX(-4px)'; this.style.boxShadow='0 4px 12px rgba(255,152,0,0.2)'" onmouseout="this.style.transform='translateX(0)'; this.style.boxShadow='none'">
          <div style="display:flex; align-items:start; gap:12px;">
            <div style="
              width:40px;
              height:40px;
              min-width:40px;
              background:#ff9800;
              border-radius:10px;
              display:flex;
              align-items:center;
              justify-content:center;
            ">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
              </svg>
            </div>
            <div style="flex:1;">
              <div style="font-weight:700; color:#e65100; font-size:0.95rem; margin-bottom:4px;">JobFinder Reports</div>
              <div style="color:#e65100; font-size:0.85rem; opacity:0.9;"><?= $pendingChatReports ?> report<?= $pendingChatReports > 1 ? 's' : '' ?> need<?= $pendingChatReports > 1 ? '' : 's' ?> review</div>
            </div>
            <div style="
              background:#ff9800;
              color:#fff;
              font-weight:700;
              font-size:0.9rem;
              padding:4px 10px;
              border-radius:20px;
              min-width:32px;
              text-align:center;
            "><?= $pendingChatReports ?></div>
          </div>
        </div>
        <?php endif; ?>
        
        <?php if ($unreadMessages > 0): ?>
        <div class="todo-item" onclick="navigateToPanel('panel-admin-chats')" style="
          background:#fce4ec;
          border-left:4px solid #e91e63;
          padding:14px;
          border-radius:10px;
          margin-bottom:12px;
          cursor:pointer;
          transition:all 0.2s;
        " onmouseover="this.style.transform='translateX(-4px)'; this.style.boxShadow='0 4px 12px rgba(233,30,99,0.2)'" onmouseout="this.style.transform='translateX(0)'; this.style.boxShadow='none'">
          <div style="display:flex; align-items:start; gap:12px;">
            <div style="
              width:40px;
              height:40px;
              min-width:40px;
              background:#e91e63;
              border-radius:10px;
              display:flex;
              align-items:center;
              justify-content:center;
            ">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
              </svg>
            </div>
            <div style="flex:1;">
              <div style="font-weight:700; color:#c2185b; font-size:0.95rem; margin-bottom:4px;">Unread Messages</div>
              <div style="color:#c2185b; font-size:0.85rem; opacity:0.9;"><?= $unreadMessages ?> unread message<?= $unreadMessages > 1 ? 's' : '' ?></div>
            </div>
            <div style="
              background:#e91e63;
              color:#fff;
              font-weight:700;
              font-size:0.9rem;
              padding:4px 10px;
              border-radius:20px;
              min-width:32px;
              text-align:center;
            "><?= $unreadMessages ?></div>
          </div>
        </div>
        <?php endif; ?>
        
      <?php endif; ?>
    </div>
  </div>
</div>

<style>
@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.1); }
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Mobile responsive */
@media (max-width: 768px) {
  #floatingTodoWidget {
    bottom: 20px;
    right: 20px;
  }
  
  #todoListPanel {
    width: calc(100vw - 40px) !important;
    right: -10px !important;
  }
  
  #todoToggleBtn {
    width: 56px !important;
    height: 56px !important;
  }
}

/* Scrollbar styling */
#todoListPanel > div:last-child::-webkit-scrollbar {
  width: 6px;
}

#todoListPanel > div:last-child::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

#todoListPanel > div:last-child::-webkit-scrollbar-thumb {
  background: #14ad0f;
  border-radius: 10px;
}

#todoListPanel > div:last-child::-webkit-scrollbar-thumb:hover {
  background: #0e7c00;
}
</style>

<script>
function toggleTodoList() {
  const panel = document.getElementById('todoListPanel');
  const btn = document.getElementById('todoToggleBtn');
  
  if (panel.style.display === 'none' || panel.style.display === '') {
    panel.style.display = 'flex';
    btn.style.transform = 'rotate(90deg)';
  } else {
    panel.style.display = 'none';
    btn.style.transform = 'rotate(0deg)';
  }
}

// Close panel when clicking outside
document.addEventListener('click', function(e) {
  const widget = document.getElementById('floatingTodoWidget');
  const panel = document.getElementById('todoListPanel');
  
  if (widget && !widget.contains(e.target) && panel.style.display === 'flex') {
    panel.style.display = 'none';
    document.getElementById('todoToggleBtn').style.transform = 'rotate(0deg)';
  }
});

// Navigate to panel and close todo list
function navigateToPanel(panelId) {
  // Hide all panels
  document.querySelectorAll('.panel-section').forEach(panel => {
    panel.style.display = 'none';
  });
  
  // Show target panel
  const targetPanel = document.getElementById(panelId);
  if (targetPanel) {
    targetPanel.style.display = 'block';
    
    // Update URL
    const panelName = panelId.replace('panel-', '');
    history.replaceState(null, '', '?panel=' + panelName);
  }
  
  // Close todo list
  document.getElementById('todoListPanel').style.display = 'none';
  document.getElementById('todoToggleBtn').style.transform = 'rotate(0deg)';
  
  // Scroll to top
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Function to refresh pending tasks
function refreshPendingTasks() {
  fetch('admin_dashboard.php?action=get_pending_tasks')
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Update badge on floating button
        const badge = document.getElementById('todoBadge');
        if (badge) {
          badge.textContent = data.totalTasks > 99 ? '99+' : data.totalTasks;
          if (data.totalTasks > 0) {
            badge.style.display = 'flex';
            badge.style.position = 'absolute';
            badge.style.top = '-5px';
            badge.style.right = '-5px';
            badge.style.background = '#e53935';
            badge.style.color = '#fff';
            badge.style.borderRadius = '50%';
            badge.style.width = '24px';
            badge.style.height = '24px';
            badge.style.alignItems = 'center';
            badge.style.justifyContent = 'center';
            badge.style.fontSize = '0.75rem';
            badge.style.fontWeight = '700';
            badge.style.border = '3px solid #fff';
            badge.style.boxShadow = '0 2px 8px rgba(229,57,53,0.4)';
            badge.style.animation = 'pulse 2s infinite';
          } else {
            badge.style.display = 'none';
          }
        }
        
        // Update task list content
        const taskListContainer = document.querySelector('#todoListPanel > div:nth-child(2)');
        if (taskListContainer) {
          let html = '';
          
          if (data.totalTasks === 0) {
            html = `
              <div style="text-align:center; padding:40px 20px; color:#999;">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5" style="margin-bottom:16px;">
                  <path d="M9 11l3 3L22 4"></path>
                  <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                </svg>
                <p style="margin:0; font-size:1rem; font-weight:600; color:#666;">All caught up!</p>
                <p style="margin:4px 0 0 0; font-size:0.85rem; color:#999;">No pending tasks</p>
              </div>
            `;
          } else {
            // Unsolved Incidents
            if (data.pendingIncidents > 0) {
              html += `
                <div class="todo-item" onclick="navigateToPanel('panel-incidents')" style="
                  background:#fff3cd;
                  border-left:4px solid #ffc107;
                  padding:14px;
                  border-radius:10px;
                  margin-bottom:12px;
                  cursor:pointer;
                  transition:all 0.2s;
                " onmouseover="this.style.transform='translateX(-4px)'; this.style.boxShadow='0 4px 12px rgba(255,193,7,0.2)'" onmouseout="this.style.transform='translateX(0)'; this.style.boxShadow='none'">
                  <div style="display:flex; align-items:start; gap:12px;">
                    <div style="
                      width:40px;
                      height:40px;
                      min-width:40px;
                      background:#ffc107;
                      border-radius:10px;
                      display:flex;
                      align-items:center;
                      justify-content:center;
                    ">
                      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                      </svg>
                    </div>
                    <div style="flex:1;">
                      <div style="font-weight:700; color:#856404; font-size:0.95rem; margin-bottom:4px;">Unsolved Incidents</div>
                      <div style="color:#856404; font-size:0.85rem; opacity:0.9;">${data.pendingIncidents} incident${data.pendingIncidents > 1 ? 's' : ''} need${data.pendingIncidents > 1 ? '' : 's'} attention</div>
                    </div>
                    <div style="
                      background:#ffc107;
                      color:#fff;
                      font-weight:700;
                      font-size:0.9rem;
                      padding:4px 10px;
                      border-radius:20px;
                      min-width:32px;
                      text-align:center;
                    ">${data.pendingIncidents}</div>
                  </div>
                </div>
              `;
            }
            
            // Pending & Approved Certificates
            if (data.pendingCertificates > 0) {
              html += `
                <div class="todo-item" onclick="navigateToPanel('panel-certificates')" style="
                  background:#e3f2fd;
                  border-left:4px solid #2196f3;
                  padding:14px;
                  border-radius:10px;
                  margin-bottom:12px;
                  cursor:pointer;
                  transition:all 0.2s;
                " onmouseover="this.style.transform='translateX(-4px)'; this.style.boxShadow='0 4px 12px rgba(33,150,243,0.2)'" onmouseout="this.style.transform='translateX(0)'; this.style.boxShadow='none'">
                  <div style="display:flex; align-items:start; gap:12px;">
                    <div style="
                      width:40px;
                      height:40px;
                      min-width:40px;
                      background:#2196f3;
                      border-radius:10px;
                      display:flex;
                      align-items:center;
                      justify-content:center;
                    ">
                      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                      </svg>
                    </div>
                    <div style="flex:1;">
                      <div style="font-weight:700; color:#1565c0; font-size:0.95rem; margin-bottom:4px;">Pending & Approved Certificates</div>
                      <div style="color:#1565c0; font-size:0.85rem; opacity:0.9;">${data.pendingCertificates} certificate${data.pendingCertificates > 1 ? 's' : ''} to process</div>
                    </div>
                    <div style="
                      background:#2196f3;
                      color:#fff;
                      font-weight:700;
                      font-size:0.9rem;
                      padding:4px 10px;
                      border-radius:20px;
                      min-width:32px;
                      text-align:center;
                    ">${data.pendingCertificates}</div>
                  </div>
                </div>
              `;
            }
            
            // Chat Reports
            if (data.pendingChatReports > 0) {
              html += `
                <div class="todo-item" onclick="navigateToPanel('panel-chat-reports')" style="
                  background:#fff3e0;
                  border-left:4px solid #ff9800;
                  padding:14px;
                  border-radius:10px;
                  margin-bottom:12px;
                  cursor:pointer;
                  transition:all 0.2s;
                " onmouseover="this.style.transform='translateX(-4px)'; this.style.boxShadow='0 4px 12px rgba(255,152,0,0.2)'" onmouseout="this.style.transform='translateX(0)'; this.style.boxShadow='none'">
                  <div style="display:flex; align-items:start; gap:12px;">
                    <div style="
                      width:40px;
                      height:40px;
                      min-width:40px;
                      background:#ff9800;
                      border-radius:10px;
                      display:flex;
                      align-items:center;
                      justify-content:center;
                    ">
                      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                      </svg>
                    </div>
                    <div style="flex:1;">
                      <div style="font-weight:700; color:#e65100; font-size:0.95rem; margin-bottom:4px;">JobFinder Reports</div>
                      <div style="color:#e65100; font-size:0.85rem; opacity:0.9;">${data.pendingChatReports} report${data.pendingChatReports > 1 ? 's' : ''} need${data.pendingChatReports > 1 ? '' : 's'} review</div>
                    </div>
                    <div style="
                      background:#ff9800;
                      color:#fff;
                      font-weight:700;
                      font-size:0.9rem;
                      padding:4px 10px;
                      border-radius:20px;
                      min-width:32px;
                      text-align:center;
                    ">${data.pendingChatReports}</div>
                  </div>
                </div>
              `;
            }
            
            // Unread Messages
            if (data.unreadMessages > 0) {
              html += `
                <div class="todo-item" onclick="navigateToPanel('panel-admin-chats')" style="
                  background:#fce4ec;
                  border-left:4px solid #e91e63;
                  padding:14px;
                  border-radius:10px;
                  margin-bottom:12px;
                  cursor:pointer;
                  transition:all 0.2s;
                " onmouseover="this.style.transform='translateX(-4px)'; this.style.boxShadow='0 4px 12px rgba(233,30,99,0.2)'" onmouseout="this.style.transform='translateX(0)'; this.style.boxShadow='none'">
                  <div style="display:flex; align-items:start; gap:12px;">
                    <div style="
                      width:40px;
                      height:40px;
                      min-width:40px;
                      background:#e91e63;
                      border-radius:10px;
                      display:flex;
                      align-items:center;
                      justify-content:center;
                    ">
                      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                      </svg>
                    </div>
                    <div style="flex:1;">
                      <div style="font-weight:700; color:#c2185b; font-size:0.95rem; margin-bottom:4px;">Unread Messages</div>
                      <div style="color:#c2185b; font-size:0.85rem; opacity:0.9;">${data.unreadMessages} unread message${data.unreadMessages > 1 ? 's' : ''}</div>
                    </div>
                    <div style="
                      background:#e91e63;
                      color:#fff;
                      font-weight:700;
                      font-size:0.9rem;
                      padding:4px 10px;
                      border-radius:20px;
                      min-width:32px;
                      text-align:center;
                    ">${data.unreadMessages}</div>
                  </div>
                </div>
              `;
            }
          }
          
          taskListContainer.innerHTML = html;
        }
        
        // Update dashboard cards
        updateDashboardCards(data);
      }
    })
    .catch(error => {
      console.error('Error refreshing pending tasks:', error);
    });
}

// Function to update dashboard cards
function updateDashboardCards(data) {
  // Update Unsolved Incidents card (Reports Panel)
  const reportsIncidentsBadge = document.getElementById('reportsIncidentsBadge');
  const reportsIncidentsSubtitle = document.getElementById('reportsIncidentsSubtitle');
  if (reportsIncidentsBadge && reportsIncidentsSubtitle) {
    reportsIncidentsBadge.textContent = data.pendingIncidents;
    reportsIncidentsSubtitle.textContent = `${data.pendingIncidents} incident${data.pendingIncidents !== 1 ? 's' : ''} need${data.pendingIncidents !== 1 ? '' : 's'} attention`;
  }
  
  // Update Certificates card (Reports Panel)
  const reportsCertificatesBadge = document.getElementById('reportsCertificatesBadge');
  const reportsCertificatesSubtitle = document.getElementById('reportsCertificatesSubtitle');
  if (reportsCertificatesBadge && reportsCertificatesSubtitle) {
    reportsCertificatesBadge.textContent = data.pendingCertificates;
    reportsCertificatesSubtitle.textContent = `${data.pendingCertificates} certificate${data.pendingCertificates !== 1 ? 's' : ''} to process`;
  }
  
  // Update Chat Reports card (Reports Panel)
  const reportsChatReportsBadge = document.getElementById('reportsChatReportsBadge');
  const reportsChatReportsSubtitle = document.getElementById('reportsChatReportsSubtitle');
  if (reportsChatReportsBadge && reportsChatReportsSubtitle) {
    reportsChatReportsBadge.textContent = data.pendingChatReports;
    reportsChatReportsSubtitle.textContent = `${data.pendingChatReports} report${data.pendingChatReports !== 1 ? 's' : ''} need${data.pendingChatReports !== 1 ? '' : 's'} review`;
  }
  
  // Update Unread Messages card (Reports Panel)
  const reportsMessagesBadge = document.getElementById('reportsMessagesBadge');
  const reportsMessagesSubtitle = document.getElementById('reportsMessagesSubtitle');
  if (reportsMessagesBadge && reportsMessagesSubtitle) {
    reportsMessagesBadge.textContent = data.unreadMessages;
    reportsMessagesSubtitle.textContent = `${data.unreadMessages} unread message${data.unreadMessages !== 1 ? 's' : ''}`;
  }
}

// Auto-refresh pending tasks every 10 seconds
setInterval(refreshPendingTasks, 10000);

// Initial refresh after page load
document.addEventListener('DOMContentLoaded', function() {
  // Refresh after 2 seconds to ensure page is fully loaded
  setTimeout(refreshPendingTasks, 2000);
});
</script>

<!-- 🔔 Pending Certificates Notification Modal -->
<div class="modal fade" id="pendingCertModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header" style="background: linear-gradient(90deg, #ff9800, #f57c00); color: #fff; border-bottom: none;">
        <h5 class="modal-title">⚠️ Pending Certificates Alert</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="pendingCertDetails" style="font-size: 0.95rem; line-height: 1.4; color:#333; max-height: 400px; overflow-y: auto;">
        <!-- Dynamic content injected via JS -->
      </div>
      <div class="modal-footer" style="border-top: 1px solid #e0e0e0;">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-warning" id="goToCertificatesBtn">
          <i class="bi bi-file-earmark-text"></i> Go to Certificates
        </button>
      </div>
    </div>
  </div>
</div>

<script>
// Check for pending certificates (2+ days old)
let pendingCertModalEl = document.getElementById('pendingCertModal');
let pendingCertModal = null;
let pendingCertModalShown = false;

// Initialize modal when Bootstrap is ready
if (typeof bootstrap !== 'undefined') {
    pendingCertModal = new bootstrap.Modal(pendingCertModalEl);
}

// Check for pending certificates every 30 seconds
function checkPendingCertificates() {
    if (pendingCertModalShown) return; // Don't check if modal is already shown
    
    fetch("check_pending_certificates.php?action=check")
        .then(res => res.json())
        .then(data => {
            if (data.success && data.pending_count > 0) {
                let details = `<div class="alert alert-warning mb-3">
                    <strong><i class="bi bi-exclamation-triangle"></i> ${data.pending_count} certificate(s)</strong> 
                    have been approved but not printed for 2 or more days.
                </div>`;
                
                details += '<div class="table-responsive"><table class="table table-hover table-sm">';
                details += '<thead class="table-light"><tr>';
                details += '<th>Resident Name</th>';
                details += '<th>Certificate Type</th>';
                details += '<th>Requested Date</th>';
                details += '<th>Days Pending</th>';
                details += '</tr></thead><tbody>';
                
                data.certificates.forEach(cert => {
                    let rowClass = cert.days_pending >= 7 ? 'table-danger' : (cert.days_pending >= 5 ? 'table-warning' : '');
                    details += `<tr class="${rowClass}">`;
                    details += `<td>${cert.resident_name}</td>`;
                    details += `<td>${cert.certificate_type}</td>`;
                    details += `<td>${cert.created_at}</td>`;
                    details += `<td><span class="badge bg-warning text-dark">${cert.days_pending} days</span></td>`;
                    details += '</tr>';
                });
                
                details += '</tbody></table></div>';
                
                document.getElementById("pendingCertDetails").innerHTML = details;
                
                // Show modal
                if (pendingCertModal) {
                    pendingCertModal.show();
                    pendingCertModalShown = true;
                }
            }
        })
        .catch(err => console.error("Pending cert check error:", err));
}

// Reset modal shown flag when modal is closed
if (pendingCertModalEl) {
    pendingCertModalEl.addEventListener('hidden.bs.modal', () => {
        pendingCertModalShown = false;
    });
}

// Handle "Go to Certificates" button click
document.addEventListener('DOMContentLoaded', function() {
    const goToCertBtn = document.getElementById('goToCertificatesBtn');
    if (goToCertBtn) {
        goToCertBtn.addEventListener('click', function() {
            // Close modal
            if (pendingCertModal) {
                pendingCertModal.hide();
            }
            
            // Navigate to certificates panel without reload
            navigateToPanel('panel-certificates');
        });
    }
});

// Check immediately on page load (after 3 seconds)
setTimeout(checkPendingCertificates, 3000);

// Then check every 1 hour
setInterval(checkPendingCertificates, 3600000);
</script>

</body>

<?php
// Render Excel Upload Success Modal if upload was successful
// Debug: Always show if there's a message (even on error)
error_log("Modal rendering check - Message: " . $excelUploadMessage);
error_log("Modal rendering check - Success flag: " . ($excelUploadSuccess ? 'TRUE' : 'FALSE'));

if (!empty($excelUploadMessage)) {
  error_log("RENDERING MODAL NOW");
  echo '
  <!-- ✅ Excel Upload Success Modal -->
  <div class="modal fade" id="excelSuccessModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content" style="border:none;border-radius:20px;box-shadow:0 10px 40px rgba(25,118,210,0.2);overflow:hidden;">
        <div class="modal-body text-center" style="padding:40px 30px;background:linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);">
          <div style="width:80px;height:80px;margin:0 auto 20px;background:linear-gradient(135deg,#1976d2,#43e97b);border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 8px 20px rgba(25,118,210,0.3);">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="white" viewBox="0 0 16 16"><path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/></svg>
          </div>
          <h3 style="color:#1976d2;font-weight:700;margin-bottom:12px;font-size:1.5rem;">Upload Successful!</h3>
          <p style="color:#0369a1;font-size:1.05rem;margin-bottom:8px;font-weight:500;">' . htmlspecialchars($excelUploadMessage) . '</p>
          <p style="color:#64748b;font-size:0.95rem;margin-bottom:0;">Redirecting to residents list...</p>
        </div>
      </div>
    </div>
  </div>

  <script>
  (function() {
    console.log("[ExcelModal] Script loaded");
    var msg = ' . json_encode($excelUploadMessage) . ';

    // Play success sound immediately
    try {
      var audio = new Audio("ChatNotif.mp3");
      audio.play().catch(function(e) { console.log("Audio play failed:", e); });
    } catch(e) {}

    function showModalNow() {
      console.log("[ExcelModal] showModalNow called");
      var modalEl = document.getElementById("excelSuccessModal");
      
      if (!modalEl) {
        console.error("[ExcelModal] Modal element not found!");
        alert(msg);
        setTimeout(function() {
          window.location.href = "admin_dashboard.php?panel=view-residents";
        }, 2000);
        return;
      }

      console.log("[ExcelModal] Modal element found, showing...");
      
      // Create backdrop
      var backdrop = document.createElement("div");
      backdrop.className = "modal-backdrop fade show";
      backdrop.style.cssText = "position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:99998;";
      document.body.appendChild(backdrop);

      // Show modal with inline styles (no Bootstrap dependency)
      modalEl.style.cssText = "display:block !important;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:99999;";
      modalEl.classList.add("show");
      modalEl.setAttribute("aria-hidden", "false");

      console.log("[ExcelModal] Modal displayed");

      // Redirect after 2.2 seconds
      setTimeout(function() {
        console.log("[ExcelModal] Redirecting...");
        window.location.href = "admin_dashboard.php?panel=view-residents";
      }, 2200);
    }

    // Show immediately, no waiting
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", showModalNow);
    } else {
      showModalNow();
    }
  })();
  </script>';
}
?>

<script>
// Handle login success modal
function closeLoginSuccessModal() {
  const successModal = document.getElementById('login-success-modal');
  if (successModal) {
    successModal.style.display = 'none';
    // Clean URL
    window.history.replaceState({}, document.title, window.location.pathname);
  }
}

window.addEventListener('DOMContentLoaded', () => {
  const urlParams = new URLSearchParams(window.location.search);
  
  if (urlParams.get('login') === 'success') {
    // Show success modal
    const successModal = document.getElementById('login-success-modal');
    if (successModal) {
      successModal.style.display = 'flex';
      // Auto-close after 3 seconds
      setTimeout(() => {
        successModal.style.display = 'none';
        // Clean URL
        window.history.replaceState({}, document.title, window.location.pathname);
      }, 3000);
    }
  }
});

</script>

<!-- Automatic logout script for logged-in admins -->
<?php if (isset($_SESSION['admin_loggedin']) && $_SESSION['admin_loggedin'] === true): ?>
<script src="auto_logout.js"></script>
<?php endif; ?>

</html>