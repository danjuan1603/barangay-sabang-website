<?php
session_start();
include 'config.php';

// Set timezone to Philippines (UTC+8)
date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json');

if (!isset($_GET['userid'])) {
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit;
}

$userid = $_GET['userid'];

// Get user status from useraccounts
$stmt = $conn->prepare("
    SELECT COALESCE(is_online, 0) AS is_online, last_active 
    FROM useraccounts 
    WHERE userid = ?
");
$stmt->bind_param("s", $userid);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'is_online' => (int)$row['is_online'],
        'last_active' => $row['last_active']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'is_online' => 0,
        'last_active' => null
    ]);
}

$stmt->close();
$conn->close();
?>
