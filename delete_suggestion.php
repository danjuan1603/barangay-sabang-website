<?php
session_start();
include 'config.php';

// ✅ Only allow admin
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: admin_login.php");
    exit;
}

// Get logged-in admin username
$admin_username = $_SESSION['admin_username'] ?? 'Unknown';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id'])) {
    $id = intval($_POST['id']);

    // Fetch suggestion info (optional, useful for logs)
    $stmtInfo = $conn->prepare("SELECT subject, message FROM suggestions WHERE message_id = ?");
    $stmtInfo->bind_param("i", $id);
    $stmtInfo->execute();
    $info = $stmtInfo->get_result()->fetch_assoc();
    $stmtInfo->close();

    // Delete suggestion
    $stmt = $conn->prepare("DELETE FROM suggestions WHERE message_id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $_SESSION['flash'] = "Suggestion deleted successfully ✅";

        // --- Log admin action ---
        $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
        $actionText = "Deleted suggestion ID $id";
        if ($info) {
            $actionText .= " (Subject: {$info['subject']})";
        }   
        $log->bind_param("ss", $admin_username, $actionText);
        $log->execute();
        $log->close();

    } else {
        $_SESSION['flash'] = "Error deleting suggestion ❌";
    }

    $stmt->close();
    $conn->close();
}

// ✅ Redirect back (Post/Redirect/Get)
header("Location: admin_dashboard.php?panel=suggestions");
exit;
    