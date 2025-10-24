<?php
session_start();
include 'config.php';

header("Content-Type: application/json");

if (!isset($_SESSION['userid'])) {
    http_response_code(401);
    echo json_encode(["error" => "Not logged in"]);
    exit;
}

$user_id = $_SESSION['userid'];

// 🔹 Get all distinct partners
$sql = "
  SELECT DISTINCT 
    CASE 
      WHEN sender_id = ? THEN receiver_id 
      ELSE sender_id 
    END AS chat_partner
  FROM messages
  WHERE sender_id = ? OR receiver_id = ?
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["error" => "SQL prepare failed", "sql_error" => $conn->error]);
    exit;
}
$stmt->bind_param("sss", $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$partners = [];

// 🔹 Prepare lookups
$nameStmt = $conn->prepare("
    SELECT r.surname, r.first_name, r.profile_image, 
           COALESCE(u.is_online, 0) AS is_online, u.last_active,
           COALESCE(r.jobfinder_verified, 0) AS jobfinder_verified
    FROM residents r
    LEFT JOIN useraccounts u ON u.userid = r.unique_id
    WHERE r.unique_id = ?
");
$unreadStmt = $conn->prepare("
    SELECT COUNT(*) AS unread_count 
    FROM messages 
    WHERE sender_id = ? AND receiver_id = ? AND is_read = 0
");

while ($row = $result->fetch_assoc()) {
    $partnerId = $row['chat_partner'];
    $name = $partnerId;
    $profileImage = null;
    $isOnline = 0;
    $lastActive = null;
    $jobfinderVerified = 0;

    // ✅ Name, profile image, and active status lookup
    if ($nameStmt) {
        $nameStmt->bind_param("s", $partnerId);
        $nameStmt->execute();
        $res = $nameStmt->get_result()->fetch_assoc();
        if ($res) {
            $name = $res['surname'] . ", " . $res['first_name'];
            $profileImage = $res['profile_image'];
            $isOnline = (int)$res['is_online'];
            $lastActive = $res['last_active'];
            $jobfinderVerified = (int)$res['jobfinder_verified'];
        } else {
            $name = "Unknown User"; // fallback
        }
    }

    // ✅ Unread count
    $unread = 0;
    if ($unreadStmt) {
        $unreadStmt->bind_param("ss", $partnerId, $user_id);
        $unreadStmt->execute();
        $uRes = $unreadStmt->get_result()->fetch_assoc();
        if ($uRes) {
            $unread = (int)$uRes['unread_count'];
        }
    }

    $partners[] = [
        "id" => $partnerId,
        "name" => $name,
        "unread" => $unread,
        "profile_image" => $profileImage,
        "is_online" => $isOnline,
        "last_active" => $lastActive,
        "jobfinder_verified" => $jobfinderVerified
    ];
}

echo json_encode($partners);
