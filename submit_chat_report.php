<?php
session_start();
require_once 'config.php';

header("Content-Type: application/json");

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    http_response_code(401);
    echo json_encode(["error" => "Not logged in"]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $reporter_id = $_SESSION['userid'];
    $reported_id = isset($_POST['reported_id']) ? trim($_POST['reported_id']) : '';
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
    $details = isset($_POST['details']) ? trim($_POST['details']) : '';

    // Validate inputs
    if (empty($reported_id)) {
        http_response_code(400);
        echo json_encode(["error" => "Reported user ID is required"]);
        exit;
    }

    if (empty($reason)) {
        http_response_code(400);
        echo json_encode(["error" => "Reason is required"]);
        exit;
    }

    // Prevent self-reporting
    if ($reporter_id === $reported_id) {
        http_response_code(400);
        echo json_encode(["error" => "You cannot report yourself"]);
        exit;
    }

    // Check if user has already reported this person recently (within 24 hours)
    $checkSql = "SELECT id FROM chat_reports 
                 WHERE reporter_id = ? AND reported_id = ? 
                 AND created_at > (NOW() - INTERVAL 24 HOUR) 
                 LIMIT 1";
    $checkStmt = $conn->prepare($checkSql);
    if ($checkStmt) {
        $checkStmt->bind_param("ss", $reporter_id, $reported_id);
        $checkStmt->execute();
        $checkStmt->store_result();
        if ($checkStmt->num_rows > 0) {
            http_response_code(400);
            echo json_encode(["error" => "You have already reported this user recently. Please wait 24 hours before submitting another report."]);
            $checkStmt->close();
            $conn->close();
            exit;
        }
        $checkStmt->close();
    }

    // Insert report
    $sql = "INSERT INTO chat_reports (reporter_id, reported_id, reason, details, status, created_at) 
            VALUES (?, ?, ?, ?, 'pending', NOW())";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(["error" => "Database error", "details" => $conn->error]);
        exit;
    }

    $stmt->bind_param("ssss", $reporter_id, $reported_id, $reason, $details);

    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success', 
            'message' => 'Report submitted successfully. The admin will review your report.'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to submit report", "details" => $stmt->error]);
    }

    $stmt->close();
    $conn->close();
} else {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
}
?>
