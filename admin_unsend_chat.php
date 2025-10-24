<?php
session_start();
include 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Not logged in as admin']);
    exit;
}

// Get the chat ID from POST request
$chat_id = isset($_POST['chat_id']) ? intval($_POST['chat_id']) : 0;

if ($chat_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid chat ID']);
    exit;
}

// Delete the admin message (only admin messages can be deleted by admin)
$stmt = $conn->prepare("DELETE FROM admin_chats WHERE chat_id = ? AND sender = 'admin'");
$stmt->bind_param("i", $chat_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        // Log admin action
        $admin_username = $_SESSION['admin_username'] ?? 'Unknown';
        $actionText = "Unsent message (Chat ID: $chat_id)";
        $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
        $log->bind_param("ss", $admin_username, $actionText);
        $log->execute();
        $log->close();
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Message not found or already deleted']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}

$stmt->close();
$conn->close();
?>
