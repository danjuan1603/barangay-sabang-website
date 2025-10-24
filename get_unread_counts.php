<?php
session_start();
include 'config.php';

// Security check
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Fetch unread counts for all users
$query = "
    SELECT u.userid, 
           SUM(CASE WHEN c.is_read = 0 AND c.sender = 'user' THEN 1 ELSE 0 END) AS unread_count
    FROM useraccounts u 
    JOIN residents r ON u.userid = r.unique_id
    LEFT JOIN admin_chats c ON u.userid = c.userid
    GROUP BY u.userid
    HAVING unread_count > 0 OR u.userid IN (
        SELECT DISTINCT userid FROM admin_chats
    )
";

$result = $conn->query($query);
$counts = [];

while ($row = $result->fetch_assoc()) {
    $counts[] = [
        'userid' => (int)$row['userid'],
        'unread_count' => (int)$row['unread_count']
    ];
}

header('Content-Type: application/json');
echo json_encode($counts);
?>
