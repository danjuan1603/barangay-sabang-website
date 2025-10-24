<?php
session_start();
include 'config.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? 'check';

// --- Helper function to return JSON and exit ---
function sendResponse($data) {
    echo json_encode($data);
    exit;
}

if ($action === 'check') {
    // Fetch unseen reports (seen = 0 or NULL)
    $stmt = $conn->prepare("
        SELECT id, incident_type, incident_description 
        FROM incident_reports 
        WHERE seen IS NULL OR seen = 0
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $result = $stmt->get_result();

    $newReports = [];
    while ($row = $result->fetch_assoc()) {
        $newReports[] = [
            "id" => (int)$row['id'], // ensure integer
            "incident_type" => $row['incident_type'],
            "incident_description" => $row['incident_description']
        ];
    }
    $stmt->close();

    // Return count as integer to avoid JS type issues
    sendResponse([
        "new_reports" => (int)count($newReports),
        "reports" => $newReports
    ]);
}

if ($action === 'reset') {
    // Mark all reports as seen
    $result = $conn->query("UPDATE incident_reports SET seen = 1 WHERE seen IS NULL OR seen = 0");
    
    if ($result) {
        $affected = $conn->affected_rows;
        error_log("Marked $affected incident reports as seen");
        sendResponse(["success" => true, "affected_rows" => $affected]);
    } else {
        error_log("Failed to update incident reports: " . $conn->error);
        sendResponse(["success" => false, "error" => $conn->error]);
    }
}

// --- Invalid action fallback ---
sendResponse([
    "error" => "Invalid action",
    "new_reports" => 0,
    "reports" => []
]);
?>
