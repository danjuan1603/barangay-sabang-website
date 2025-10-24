<?php
session_start(); // ✅ Must be first line
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['userid'])) {
        die("❌ You must be logged in to submit an incident.");
    }

    $userid = $_SESSION['userid']; // Logged-in user ID
    $incident_type = trim($_POST['incident_type']);
    
    // If "Other" is selected, use the custom incident type
    if ($incident_type === 'Other' && !empty($_POST['incident_type_other'])) {
        $incident_type = 'Other: ' . trim($_POST['incident_type_other']);
    }
    
    $contact_number = trim($_POST['contact_number'] ?? ''); // ✅ get from form
    $incident_description = trim($_POST['incident_description']);
    $imagePath = NULL; // Default if no image uploaded

    // Check if user is blocked from submitting incident reports
    $blockCheck = $conn->prepare("SELECT can_submit_incidents FROM residents WHERE unique_id = ?");
    $blockCheck->bind_param("i", $userid);
    $blockCheck->execute();
    $blockResult = $blockCheck->get_result();
    $userData = $blockResult->fetch_assoc();
    $blockCheck->close();
    
    if ($userData && $userData['can_submit_incidents'] == 0) {
        $_SESSION['incident_error'] = 'You have been blocked from submitting incident reports. Please contact the barangay office.';
        header("Location: index.php#incident-reports-section");
        exit;
    }

    // Check if user has submitted a report within the last 1 hour (rate limiting)
    $rateLimitCheck = $conn->prepare("SELECT created_at FROM incident_reports WHERE userid = ? ORDER BY created_at DESC LIMIT 1");
    $rateLimitCheck->bind_param("i", $userid);
    $rateLimitCheck->execute();
    $rateLimitResult = $rateLimitCheck->get_result();
    
    if ($rateLimitResult->num_rows > 0) {
        $lastReport = $rateLimitResult->fetch_assoc();
        $lastReportTime = strtotime($lastReport['created_at']);
        $currentTime = time();
        $timeDifference = $currentTime - $lastReportTime;
        
        // If less than 1 hour (3600 seconds) has passed
        if ($timeDifference < 3600) {
            $remainingMinutes = ceil((3600 - $timeDifference) / 60);
            $_SESSION['incident_error'] = "You can only submit one incident report per hour. Please wait $remainingMinutes more minute(s) before submitting another report.";
            $rateLimitCheck->close();
            header("Location: index.php#incident-reports-section");
            exit;
        }
    }
    $rateLimitCheck->close();

    // --- Handle image upload ---
    if (isset($_FILES['incident_image']) && $_FILES['incident_image']['error'] === 0) {
        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = time() . "_" . basename($_FILES['incident_image']['name']);
        $targetFilePath = $uploadDir . $fileName;

        $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array(strtolower($fileType), $allowedTypes)) {
            if (move_uploaded_file($_FILES['incident_image']['tmp_name'], $targetFilePath)) {
                $imagePath = $targetFilePath; // Save path to DB
            } else {
                die("❌ Failed to upload image.");
            }
        } else {
            die("❌ Invalid image type. Allowed types: jpg, jpeg, png, gif.");
        }
    }

    // --- Insert into database ---
    $stmt = $conn->prepare("INSERT INTO incident_reports 
        (userid, incident_type, contact_number, incident_description, incident_image, created_at, status) 
        VALUES (?, ?, ?, ?, ?, NOW(), 'Pending')");

    if (!$stmt) {
        die("❌ SQL Prepare failed: " . $conn->error);
    }

    // ✅ Correct binding (int, string, string, string, string)
    $stmt->bind_param("issss", $userid, $incident_type, $contact_number, $incident_description, $imagePath);

    if ($stmt->execute()) {
        // redirect back with a success flag
        header("Location: index.php?incident_submitted=1#incident-reports-section");
        exit;
    } else {
        echo "❌ Error submitting report: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
