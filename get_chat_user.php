<?php
session_start();
include 'config.php';

// Security check
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$userid = isset($_GET['userid']) ? intval($_GET['userid']) : 0;

if ($userid <= 0) {
    echo json_encode(['error' => 'Invalid user ID']);
    exit();
}

// Fetch resident info
$stmt = $conn->prepare("
    SELECT u.userid, CONCAT(r.surname, ' ', r.first_name) AS fullname, r.profile_image, 
           COALESCE(r.jobfinder_verified, 0) AS is_verified,
           COALESCE(u.is_online, 0) AS is_online, u.last_active
    FROM useraccounts u
    JOIN residents r ON u.userid = r.unique_id
    WHERE u.userid = ?
");
$stmt->bind_param("i", $userid);
$stmt->execute();
$resident = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Mark messages as read
$stmt = $conn->prepare("UPDATE admin_chats SET is_read = 1 WHERE userid = ? AND sender = 'user'");
$stmt->bind_param("i", $userid);
$stmt->execute();
$stmt->close();

echo json_encode([
    'success' => true,
    'resident' => $resident
]);
?>
