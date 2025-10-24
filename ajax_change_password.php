<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$userid = $_SESSION['userid'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $old_password = trim($_POST['old_password'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    // Validate fields are not empty
    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    // Fetch current password
    $sql = "SELECT password FROM useraccounts WHERE userid=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $userid);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    // Check if old password is correct (handle both hashed and plain text)
    $password_correct = false;
    if ($result) {
        // Try hashed password first
        if (password_verify($old_password, $result['password'])) {
            $password_correct = true;
        } 
        // If verification fails, check if it's plain text (legacy)
        else if ($old_password === $result['password']) {
            $password_correct = true;
        }
    }

    if ($password_correct) {
        if ($new_password === $confirm_password) {
            // Store password as plain text (not hashed)
            $update = $conn->prepare("UPDATE useraccounts SET password=? WHERE userid=?");
            $update->bind_param("ss", $new_password, $userid);
            
            if ($update->execute()) {
                echo json_encode(['success' => true, 'message' => 'Password changed successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update password']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'New password and confirmation do not match']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Old password is incorrect']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
