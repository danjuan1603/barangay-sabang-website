<?php
session_start();
include 'config.php'; // ✅ Database connection

// ✅ Set user as offline when logging out
if (!empty($_SESSION['userid'])) {
    $userid = $_SESSION['userid'];
    $update_status = $conn->prepare("UPDATE useraccounts SET is_online = 0, last_active = NOW() WHERE userid = ?");
    $update_status->bind_param("s", $userid);
    $update_status->execute();
    $update_status->close();
}

// ✅ Set admin as offline when logging out
if (!empty($_SESSION['admin_loggedin']) && $_SESSION['admin_loggedin'] === true) {
    if (!empty($_SESSION['role']) && $_SESSION['role'] !== 'regular' && !empty($_SESSION['admin_id'])) {
        // Main admin
        $admin_id = $_SESSION['admin_id'];
        $update_status = $conn->prepare("UPDATE main_admin SET is_online = 0, last_active = NOW() WHERE id = ?");
        $update_status->bind_param("i", $admin_id);
        $update_status->execute();
        $update_status->close();
    } elseif (!empty($_SESSION['admin_id'])) {
        // Regular admin
        $admin_id = $_SESSION['admin_id'];
        $update_status = $conn->prepare("UPDATE admin_accounts SET is_online = 0, last_active = NOW() WHERE admin_id = ?");
        $update_status->bind_param("s", $admin_id);
        $update_status->execute();
        $update_status->close();
    }
}

if (!empty($_SESSION['log_id'])) {
    $log_id = (int) $_SESSION['log_id']; // always points to login row

    // ✅ Update only the login record’s logout_time
    $stmt = $conn->prepare("
        UPDATE admin_logs 
        SET logout_time = NOW()
        WHERE id = ?
        LIMIT 1
    ");

    if ($stmt) {
        $stmt->bind_param("i", $log_id);
        $stmt->execute();
        $stmt->close();
    }
}

// ✅ End session
session_unset();
session_destroy();
$conn->close();

// ✅ Redirect back to login page
header("Location: index.php");
exit();
?>
