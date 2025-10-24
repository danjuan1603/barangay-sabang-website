<?php
include 'config.php';
header('Content-Type: application/json');
if (!isset($_GET['userid'])) {
    echo json_encode(["error" => "Missing userid"]);
    exit;
}
$userid = $_GET['userid'];
$stmt = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as rating_count FROM chat_ratings WHERE receiver_id = ?");
$stmt->bind_param("s", $userid);
$stmt->execute();
$stmt->bind_result($avg, $count);
$stmt->fetch();
$stmt->close();
echo json_encode([
    "avg_rating" => $avg ? round($avg, 2) : 0,
    "rating_count" => (int)$count
]);
exit;