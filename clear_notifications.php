<?php
session_start();
include 'config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['userid'])) {
    echo json_encode(["error" => "Not logged in"]);
    exit();
}

$userid = intval($_SESSION['userid']);

// Mark admin chats as read for this user
$stmt = $conn->prepare("UPDATE admin_chats SET is_read = 1 WHERE userid = ? AND sender = 'admin' AND is_read = 0");
$stmt->bind_param("i", $userid);
$stmt->execute();

$updated = $stmt->affected_rows;
$stmt->close();

echo json_encode(["success" => true, "updated" => $updated]);

$conn->close();

?>
