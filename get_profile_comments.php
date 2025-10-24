<?php
include 'config.php';
header('Content-Type: application/json');
if (!isset($_GET['userid'])) {
    echo json_encode([]);
    exit;
}
$userid = $_GET['userid'];
$stmt = $conn->prepare("SELECT cr.comment, cr.rating, cr.created_at, r.first_name, r.surname 
                        FROM chat_ratings cr 
                        LEFT JOIN residents r ON cr.userid = r.unique_id 
                        WHERE cr.receiver_id = ? 
                        AND cr.comment IS NOT NULL 
                        AND cr.comment != '' 
                        ORDER BY cr.created_at DESC 
                        LIMIT 10");
$stmt->bind_param("s", $userid);
$stmt->execute();
$result = $stmt->get_result();
$comments = [];
while ($row = $result->fetch_assoc()) {
    $comments[] = [
        "comment" => $row['comment'],
        "rating" => intval($row['rating'] ?? 0),
        "name" => ($row['first_name'] ?? 'Anonymous') . ' ' . ($row['surname'] ?? ''),
        "date" => date('M d, Y', strtotime($row['created_at']))
    ];
}
$stmt->close();
echo json_encode($comments);
exit;
