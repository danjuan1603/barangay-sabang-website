<?php
session_start();
include 'config.php';

// Set timezone to Philippines (UTC+8)
date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json');

// Check if any admin is online from either main_admin or admin_accounts
$stmt = $conn->prepare("
    SELECT 
        COALESCE(MAX(is_online), 0) AS is_online,
        MAX(last_active) AS last_active
    FROM (
        SELECT is_online, last_active FROM main_admin WHERE is_online = 1
        UNION ALL
        SELECT is_online, last_active FROM admin_accounts WHERE is_online = 1
    ) AS combined_admins
");
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    // If no online admin found, get the most recent last_active from all admins
    if ($row['is_online'] == 0) {
        $stmt2 = $conn->prepare("
            SELECT MAX(last_active) AS last_active
            FROM (
                SELECT last_active FROM main_admin
                UNION ALL
                SELECT last_active FROM admin_accounts
            ) AS all_admins
        ");
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        if ($result2 && $result2->num_rows > 0) {
            $row2 = $result2->fetch_assoc();
            $row['last_active'] = $row2['last_active'];
        }
        $stmt2->close();
    }
    
    echo json_encode([
        'success' => true,
        'is_online' => (int)$row['is_online'],
        'last_active' => $row['last_active']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'is_online' => 0,
        'last_active' => null
    ]);
}

$stmt->close();
$conn->close();
?>
