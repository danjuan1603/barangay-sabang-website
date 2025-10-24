<?php
session_start();
include 'config.php';

header("Content-Type: application/json");

if (!isset($_SESSION['userid'])) {
    http_response_code(401);
    echo json_encode(["error" => "Not logged in"]);
    exit;
}

if (!isset($_GET['other_id']) || $_GET['other_id'] === '') {
    http_response_code(400);
    echo json_encode(["error" => "Missing other_id"]);
    exit;
}

$user_id  = $_SESSION['userid'];
$other_id = trim($_GET['other_id']);

// Mark partner→you messages as read
$updateSql = "
    UPDATE messages
    SET is_read = 1
    WHERE sender_id = ? AND receiver_id = ? AND is_read = 0
";
$update = $conn->prepare($updateSql);
if ($update) {
    $update->bind_param("ss", $other_id, $user_id);
    $update->execute();
    $update->close();
}

// Fetch messages with profile images
$sql = "
    SELECT m.id, m.sender_id, m.receiver_id, m.message, m.image_path, m.timestamp, m.is_read,
           r.profile_image as sender_profile_image
    FROM messages m
    LEFT JOIN residents r ON m.sender_id = r.unique_id
    WHERE (m.sender_id = ? AND m.receiver_id = ?)
       OR (m.sender_id = ? AND m.receiver_id = ?)
    ORDER BY m.timestamp ASC
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["error" => "SELECT prepare failed", "sql_error" => $conn->error]);
    exit;
}
$stmt->bind_param("ssss", $user_id, $other_id, $other_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $profileImage = (!empty($row['sender_profile_image'])) 
        ? $row['sender_profile_image'] 
        : "default_avatar.png";
    
    $messages[] = [
        'id' => $row['id'],
        'sender_id' => $row['sender_id'],
        'message' => $row['message'],
        'image_path' => $row['image_path'],
        'datetime' => $row['timestamp'],
        'is_read' => (int)$row['is_read'],
        'profile_image' => $profileImage
    ];
}

echo json_encode($messages);
?>
