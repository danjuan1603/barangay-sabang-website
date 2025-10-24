<?php 
session_start();
include 'config.php';

// ✅ Always return JSON
header("Content-Type: application/json");

// ✅ Ensure user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['userid'])) {
    echo json_encode([
        "status" => "error", 
        "message" => "Unauthorized. Please log in first."
    ]);
    exit;
}

// ✅ Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userid  = intval($_SESSION['userid']);
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Collect errors
    $errors = [];

    if ($subject === '') {
        $errors['subject'] = "Subject is required.";
    }
    if ($message === '') {
        $errors['message'] = "Message is required.";
    }

    // If there are errors, return them
    if (!empty($errors)) {
        echo json_encode([
            "status"  => "error", 
            "message" => "Please correct the errors below.",
            "errors"  => $errors
        ]);
        exit;
    }

    // ✅ Insert into database
    $stmt = $conn->prepare(
        "INSERT INTO suggestions (userid, subject, message, created_at) VALUES (?, ?, ?, NOW())"
    );
    $stmt->bind_param("iss", $userid, $subject, $message);

    if ($stmt->execute()) {
        echo json_encode([
            "status"  => "success", 
            "message" => "✅ Message Sent!"
        ]);
    } else {
        echo json_encode([
            "status"  => "error", 
            "message" => "❌ Failed to save message."
        ]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

// 🚨 Fallback if not POST
echo json_encode([
    "status" => "error", 
    "message" => "Invalid request."
]);
exit;
