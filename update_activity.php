<?php
session_start();
include 'config.php';

// ✅ Set timezone to Philippines (UTC+8)
date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json');

if (!isset($_SESSION['userid'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$userid = $_SESSION['userid'];

// Update is_online and last_active timestamp
$stmt = $conn->prepare("UPDATE useraccounts SET is_online = 1, last_active = NOW() WHERE userid = ?");
$stmt->bind_param("s", $userid);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update activity']);
}

$stmt->close();
$conn->close();
?>
