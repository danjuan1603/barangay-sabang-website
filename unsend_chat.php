<?php
session_start();
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// Get the chat ID from POST request
$chat_id = isset($_POST['chat_id']) ? intval($_POST['chat_id']) : 0;
$user_id = $_SESSION['userid'];

if ($chat_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid chat ID']);
    exit;
}

// Verify that the message belongs to the current user and delete it
$stmt = $conn->prepare("DELETE FROM admin_chats WHERE chat_id = ? AND userid = ? AND sender = 'user'");
$stmt->bind_param("ii", $chat_id, $user_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
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
