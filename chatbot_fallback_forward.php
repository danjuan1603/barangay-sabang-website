<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

// Ensure user is logged in (we need userid to save chat)
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['userid'])) {
    echo json_encode(['success' => false, 'error' => 'unauthorized']);
    exit;
}

$userid = intval($_SESSION['userid']);
$message = trim($_POST['message'] ?? '');

if ($message === '') {
    echo json_encode(['success' => false, 'error' => 'empty_message']);
    exit;
}

// Insert as admin message so it appears on the admin side (sender = 'admin')
// and mark it as unread (is_read = 0)
$stmt = $conn->prepare("INSERT INTO admin_chats (userid, sender, message, created_at, is_read) VALUES (?, 'admin', ?, NOW(), 0)");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'db_prepare_failed']);
    exit;
}

$stmt->bind_param('is', $userid, $message);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'chat_id' => $stmt->insert_id]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$stmt->close();
$conn->close();

?>
