<?php 
header('Content-Type: application/json'); // Always JSON
session_start();
include 'config.php';

// ✅ Ensure user logged in
if (!isset($_SESSION['userid'])) {
    echo json_encode(["success" => false, "error" => "Unauthorized - no session"]);
    exit;
}

$userid = intval($_SESSION['userid']);
$certificate = trim($_POST['certificate'] ?? '');
$purpose = trim($_POST['purpose'] ?? '');
$description = trim($_POST['description'] ?? '');

// ✅ Blocked user check
$res = $conn->query("SELECT can_request FROM residents WHERE unique_id = '$userid'")->fetch_assoc();
if ($res && $res['can_request'] == 0) {
    echo json_encode([
        "success" => false,
        "error" => "You are blocked from requesting certificates. Please contact the administrator."
    ]);
    exit;
}

// ✅ Validate input
if ($certificate === '' || $purpose === '') {
    echo json_encode(["success" => false, "error" => "Missing required fields"]);
    exit;
}

try {
    $stmt = $conn->prepare("
        INSERT INTO certificate_requests (resident_unique_id, certificate_type, purpose, description, created_at, status) 
        VALUES (?, ?, ?, ?, NOW(), 'Pending')
    ");
    if (!$stmt) {
        echo json_encode(["success" => false, "error" => "SQL prepare failed: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("isss", $userid, $certificate, $purpose, $description);

    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Your request for '$certificate' has been submitted successfully."
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "error" => "Database execute error: " . $stmt->error
        ]);
    }

    $stmt->close();
    $conn->close();

} catch (Throwable $e) {
    echo json_encode([
        "success" => false,
        "error" => "Unexpected server error: " . $e->getMessage()
    ]);
}
