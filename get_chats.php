<?php
session_start();
include 'config.php';

// Set JSON header early
header('Content-Type: application/json; charset=utf-8');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

// Check if admin is requesting unread count
if (isset($_GET['count_unread']) && isset($_SESSION['admin_loggedin']) && $_SESSION['admin_loggedin'] === true) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS unread FROM admin_chats WHERE is_read = 0 AND sender = 'user'");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        echo json_encode(['unread' => (int)$row['unread']]);
        $stmt->close();
        $conn->close();
        exit();
    }
}

// Initialize user ID safely
$userid = 0;

// Admin view (view specific user's chat)
if (isset($_SESSION['admin_loggedin']) && $_SESSION['admin_loggedin'] === true) {
    if (isset($_GET['userid']) && is_numeric($_GET['userid'])) {
        $userid = intval($_GET['userid']);
    }
}
// Regular user view (only own chat)
elseif (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && isset($_SESSION['userid'])) {
    $userid = intval($_SESSION['userid']);
}

// Unauthorized access
if ($userid <= 0) {
    echo json_encode(["error" => "Unauthorized or Invalid User ID"]);
    exit();
}

// ✅ Fetch chats + resident profile image
$stmt = $conn->prepare("
    SELECT c.chat_id, c.userid, c.sender, c.message, c.created_at, c.is_read, r.profile_image
    FROM admin_chats c
    LEFT JOIN residents r ON c.userid = r.unique_id
    WHERE c.userid = ?
    ORDER BY c.chat_id ASC
");

if (!$stmt) {
    echo json_encode(["error" => "Database query failed"]);
    exit();
}

$stmt->bind_param("i", $userid);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    // Default avatar logic
    $profileImage = (!empty($row['profile_image'])) 
        ? $row['profile_image'] 
        : "default_avatar.png";

    // If sender is admin, override with admin avatar
    if ($row['sender'] === 'admin') {
        $profileImage = "admin_avatar.png";
    }

    $messages[] = [
        'chat_id'       => (int)$row['chat_id'],
        'userid'        => (int)$row['userid'],
        'sender'        => $row['sender'],
        'message'       => $row['message'],
        'created_at'    => $row['created_at'],
        'is_read'       => (int)$row['is_read'], 
        'profile_image' => $profileImage
    ];
}

$stmt->close();
$conn->close();

// Return the messages as JSON
echo json_encode($messages, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
?>
