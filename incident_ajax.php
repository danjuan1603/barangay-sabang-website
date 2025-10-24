<?php
// incident_ajax.php
include 'config.php';
session_start();
$admin_username = $_SESSION['admin_username'] ?? 'Unknown';
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'update_status') {
    $id = intval($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    // Normalize legacy 'Priority' value to 'In Review'
    if ($status === 'Priority') $status = 'In Review';
    if ($id && $status) {
        if ($status === 'Resolved') {
            $check = $conn->prepare("SELECT * FROM incident_reports WHERE id = ?");
            $check->bind_param("i", $id);
            $check->execute();
            $incident = $check->get_result()->fetch_assoc();
            $check->close();
            $comment = trim($_POST['comment'] ?? '');
            if ($incident) {
                $conn->begin_transaction();
                try {
                    // Check if archived_incident_reports has an admin_comment column
                    $hasCommentCol = false;
                    $colCheck = $conn->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'archived_incident_reports' AND COLUMN_NAME = 'admin_comment' LIMIT 1");
                    $dbName = $conn->query('select database()')->fetch_row()[0];
                    $colCheck->bind_param('s', $dbName);
                    $colCheck->execute();
                    $colRes = $colCheck->get_result();
                    if ($colRes && $colRes->num_rows > 0) $hasCommentCol = true;
                    $colCheck->close();

                    if ($hasCommentCol) {
                        $insert = $conn->prepare("
                            INSERT INTO archived_incident_reports 
                                (userid, incident_type, contact_number, incident_description, incident_image, created_at, status, date_ended, seen, admin_comment)
                            VALUES (?, ?, ?, ?, ?, ?, 'Resolved', NOW(), ?, ?)
                        ");
                        // userid (i), incident_type (s), contact_number (s), incident_description (s), incident_image (s), created_at (s), seen (i), comment (s)
                        $insert->bind_param(
                            "isssssis",
                            $incident['userid'],
                            $incident['incident_type'],
                            $incident['contact_number'],
                            $incident['incident_description'],
                            $incident['incident_image'],
                            $incident['created_at'],
                            $incident['seen'],
                            $comment
                        );
                    } else {
                        $insert = $conn->prepare("
                            INSERT INTO archived_incident_reports 
                                (userid, incident_type, contact_number, incident_description, incident_image, created_at, status, date_ended, seen)
                            VALUES (?, ?, ?, ?, ?, ?, 'Resolved', NOW(), ?)
                        ");
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
                    }
                    $insert->execute();
                    $insert->close();
                    $delete = $conn->prepare("DELETE FROM incident_reports WHERE id = ?");
                    $delete->bind_param("i", $id);
                    $delete->execute();
                    $delete->close();
                    $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
                    $logText = "Archived resolved incident report ID $id (AJAX)";
                    $log->bind_param("ss", $admin_username, $logText);
                    $log->execute();
                    $log->close();
                    $conn->commit();
                    echo json_encode(['success'=>true]);
                    exit;
                } catch (Exception $e) {
                    $conn->rollback();
                    echo json_encode(['success'=>false, 'error'=>$e->getMessage()]);
                    exit;
                }
            }
        } else {
            // Ensure only allowed statuses are saved (defensive): Pending, In Review, Resolved
            $allowed = ['Pending','In Review','Resolved'];
            if (!in_array($status, $allowed)) $status = 'Pending';
            $update = $conn->prepare("UPDATE incident_reports SET status = ? WHERE id = ?");
            $update->bind_param("si", $status, $id);
            $update->execute();
            $update->close();
            echo json_encode(['success'=>true]);
            exit;
        }
    }
    echo json_encode(['success'=>false, 'error'=>'Invalid data']);
    exit;
}

if ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    $archived = ($_POST['archived'] ?? '0') === '1';
    if ($id) {
        if ($archived) {
            $stmt = $conn->prepare("DELETE FROM archived_incident_reports WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $conn->prepare("DELETE FROM incident_reports WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }
        $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
        $logText = "Deleted incident report ID $id (AJAX)";
        $log->bind_param("ss", $admin_username, $logText);
        $log->execute();
        $log->close();
        echo json_encode(['success'=>true]);
        exit;
    }
    echo json_encode(['success'=>false, 'error'=>'Invalid ID']);
    exit;
}

if ($action === 'get_table') {
    ob_start();
    $iview = $_GET['iview'] ?? 'active';
    $isearch = $_GET['isearch'] ?? '';
    $istatus = $_GET['istatus'] ?? '';
    $istart_date = $_GET['istart_date'] ?? '';
    $iend_date   = $_GET['iend_date'] ?? '';
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
    if (!empty($iparams)) {
        $istmt->bind_param($itypes, ...$iparams);
    }
    $istmt->execute();
    $iresult = $istmt->get_result();
    include 'incident_table_partial.php';
    $html = ob_get_clean();
    echo json_encode(['success'=>true, 'html'=>$html]);
    exit;
}

echo json_encode(['success'=>false, 'error'=>'Invalid action']);
