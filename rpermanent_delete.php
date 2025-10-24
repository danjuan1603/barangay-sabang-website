<?php
session_start();
include 'config.php';

// ✅ Ensure admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: index.php?admin_login=required");
    exit();
}

$admin_username = $_SESSION['admin_username']; // current admin

if (isset($_GET['id'])) {
    $id = (int)$_GET['id']; // ensure integer

    // ✅ Fetch resident info before permanent delete (optional, for logging)
    $stmt = $conn->prepare("SELECT surname, first_name FROM deleted_residents WHERE unique_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resident = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // ✅ Delete permanently
    $stmt = $conn->prepare("DELETE FROM deleted_residents WHERE unique_id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // ✅ Log admin action
        if ($resident) {
            $action = "Permanently deleted resident: {$resident['surname']}, {$resident['first_name']} (ID: $id)";
        } else {
            $action = "Permanently deleted resident with ID: $id";
        }

        $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
        $log->bind_param("ss", $admin_username, $action);
        $log->execute();
        $log->close();

        if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        } else {
            header("Location: rdeleted_residents.php?msg=Resident permanently deleted");
        }
    } else {
        if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Error deleting resident']);
            exit;
        } else {
            header("Location: rdeleted_residents.php?msg=Error deleting resident");
        }
    }

    $stmt->close();
} else {
    die("Invalid request.");
}

$conn->close();
exit;
?>
