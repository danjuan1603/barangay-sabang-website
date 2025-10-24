<?php
/**
 * This script marks users as offline if they haven't been active for more than 5 minutes
 * You can run this as a cron job or call it periodically via AJAX
 */

include 'config.php';

// ✅ Set timezone to Philippines (UTC+8)
date_default_timezone_set('Asia/Manila');

// Mark users as offline if last_active is older than 5 minutes
$sql = "UPDATE useraccounts 
        SET is_online = 0 
        WHERE is_online = 1 
        AND last_active < DATE_SUB(NOW(), INTERVAL 5 MINUTE)";

if ($conn->query($sql)) {
    $affected = $conn->affected_rows;
    echo json_encode([
        'success' => true, 
        'message' => "$affected users marked as offline",
        'affected_rows' => $affected
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to update inactive users'
    ]);
}

$conn->close();
?>
