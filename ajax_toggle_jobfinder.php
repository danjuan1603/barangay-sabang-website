<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$userid = $_SESSION['userid'];

// Fetch current status
$stmt = $conn->prepare("SELECT jobfinder_active FROM residents WHERE unique_id=?");
$stmt->bind_param("s", $userid);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

$current_status = isset($result['jobfinder_active']) ? $result['jobfinder_active'] : 1;
$new_status = $current_status == 1 ? 0 : 1;

$sql = "UPDATE residents SET jobfinder_active=? WHERE unique_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $new_status, $userid);

if ($stmt->execute()) {
    $message = $new_status == 1 
        ? 'Jobfinder profile activated! You will now appear in Jobfinder listings.'
        : 'Jobfinder profile deactivated. You will not appear in Jobfinder listings.';
    
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'new_status' => $new_status
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to toggle status']);
}
?>
