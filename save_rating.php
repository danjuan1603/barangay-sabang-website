<?php
session_start();
include 'config.php';

if (!isset($_SESSION['userid'])) {
    echo json_encode(["status" => "error", "error" => "Not logged in"]);
    exit;
}

$raterId   = $_SESSION['userid'];
$residentId = $_POST['receiver_id'] ?? null;
$rating    = isset($_POST['rating']) ? (int) $_POST['rating'] : null;
$comment   = isset($_POST['comment']) ? trim($_POST['comment']) : '';

header('Content-Type: application/json');

// Check required fields
if ($residentId === null || $rating === null) {
    echo json_encode(["status" => "error", "error" => "Missing data."]);
    exit;
}

// Validate rating (1–5)
if ($rating < 1 || $rating > 5) {
    echo json_encode(["status" => "error", "error" => "Invalid rating value."]);
    exit;
}

// Insert query
$stmt = $conn->prepare("INSERT INTO chat_ratings (userid, receiver_id, rating, comment) VALUES (?, ?, ?, ?)");
if (!$stmt) {
    echo json_encode(["status" => "error", "error" => $conn->error, "debug" => $_POST]);
    exit;
}

$stmt->bind_param("iiis", $raterId, $residentId, $rating, $comment);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Rating submitted successfully!", "comment" => $comment]);
} else {
    echo json_encode(["status" => "error", "error" => $stmt->error, "debug" => $_POST]);
}
$stmt->close();
exit;
?>
