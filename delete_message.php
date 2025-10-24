<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'error' => 'Not authenticated']);
    exit;
}

$currentUserId = $_SESSION['userid'];

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$messageId = $input['message_id'] ?? null;

if (!$messageId) {
    echo json_encode(['status' => 'error', 'error' => 'Message ID required']);
    exit;
}

// Verify the message belongs to the current user before deleting
$checkStmt = $conn->prepare("SELECT sender_id FROM messages WHERE id = ?");
$checkStmt->bind_param("i", $messageId);
$checkStmt->execute();
$checkStmt->bind_result($senderId);
$checkStmt->fetch();
$checkStmt->close();

if ($senderId !== $currentUserId) {
    echo json_encode(['status' => 'error', 'error' => 'Unauthorized to delete this message']);
    exit;
}

// Delete the message
$deleteStmt = $conn->prepare("DELETE FROM messages WHERE id = ? AND sender_id = ?");
$deleteStmt->bind_param("is", $messageId, $currentUserId);

if ($deleteStmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Message deleted successfully']);
} else {
    echo json_encode(['status' => 'error', 'error' => 'Failed to delete message']);
}

$deleteStmt->close();
$conn->close();
?>
