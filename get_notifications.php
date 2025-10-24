<?php
session_start();
include 'config.php';
header('Content-Type: application/json');

// Collect notifications
$response = [];
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] && isset($_SESSION['userid'])) {
    $resident_id = $_SESSION['userid'];
    // Certificate notifications
    $sql = "SELECT certificate_type, created_at FROM certificate_requests WHERE resident_unique_id=? AND status='Printed' ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $resident_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $response[] = [
            'type' => 'certificate',
            'message' => "Your certificate '{$row['certificate_type']}' is ready!",
            'date' => date('M d, Y h:i A', strtotime($row['created_at']))
        ];
    }
    $stmt->close();

    // Unread admin chat notification
    $chat_sql = "SELECT COUNT(*) AS unread_count FROM admin_chats WHERE userid=? AND sender='admin' AND is_read=0";
    $chat_stmt = $conn->prepare($chat_sql);
    $chat_stmt->bind_param('i', $resident_id);
    $chat_stmt->execute();
    $chat_result = $chat_stmt->get_result();
    $chat_row = $chat_result->fetch_assoc();
    $unread_admin_chats = (int)$chat_row['unread_count'];
    if ($unread_admin_chats > 0) {
        $response[] = [
            'type' => 'admin_chat',
            'message' => "You have $unread_admin_chats unread message(s) from Admin.",
            'date' => date('M d, Y h:i A')
        ];
    }
    $chat_stmt->close();

      
}
echo json_encode($response);