<?php
session_start();
include 'config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['userid'])) {
    echo json_encode(["error" => "Not logged in"]);
    exit();
}

$userid = intval($_SESSION['userid']);

$stmt = $conn->prepare("
    UPDATE admin_chats 
    SET is_read = 1 
    WHERE userid = ? AND sender = 'admin' AND is_read = 0
");
$stmt->bind_param("i", $userid);
$stmt->execute();

echo json_encode([
    "success" => true,
    "updated" => $stmt->affected_rows
]);

$stmt->close();
$conn->close();
?>
