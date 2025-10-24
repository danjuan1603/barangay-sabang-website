<?php
session_start();
require_once 'config.php';

header("Content-Type: application/json");

if (!isset($_SESSION['userid'])) {
    http_response_code(401);
    echo json_encode(["error" => "Not logged in"]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $sender_id   = $_SESSION['userid'];
    $receiver_id = isset($_POST['receiver_id']) ? trim($_POST['receiver_id']) : '';
    $message     = isset($_POST['message']) ? trim($_POST['message']) : '';

    // Handle image upload if present
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $newName = uniqid('img_', true) . '.' . $ext;
            $image_path = $uploadDir . $newName;
            move_uploaded_file($_FILES['image']['tmp_name'], $image_path);
        } else {
            http_response_code(400);
            echo json_encode(["error" => "Invalid image type"]);
            exit;
        }
    }

    // Prevent accidental duplicates (same sender/receiver/message within 2 seconds)
    if ($message !== '') {
        $checkSql = "SELECT id FROM messages 
                     WHERE sender_id = ? AND receiver_id = ? AND message = ? 
                     AND timestamp > (NOW() - INTERVAL 2 SECOND) 
                     LIMIT 1";
        $checkStmt = $conn->prepare($checkSql);
        if ($checkStmt) {
            $checkStmt->bind_param("iis", $sender_id, $receiver_id, $message);
            $checkStmt->execute();
            $checkStmt->store_result();
            if ($checkStmt->num_rows > 0) {
                echo json_encode(["status" => "duplicate"]);
                $checkStmt->close();
                $conn->close();
                exit;
            }
            $checkStmt->close();
        }
    }

    // Insert message
    $sql = "INSERT INTO messages (sender_id, receiver_id, message, image_path, timestamp, is_read) 
            VALUES (?, ?, ?, ?, NOW(), 0)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(["error" => "SQL Error", "details" => $conn->error]);
        exit;
    }
    $stmt->bind_param("ssss", $sender_id, $receiver_id, $message, $image_path);

    if ($stmt->execute()) {
        // Get the datetime of the created message and the inserted message ID
        $created_at = date('Y-m-d H:i:s');
        $message_id = $stmt->insert_id;
        echo json_encode(['status' => 'success', 'datetime' => $created_at, 'message_id' => $message_id]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => $stmt->error]);
    }

    $stmt->close();
    $conn->close();
}
?>
