<?php
ob_start(); // Start output buffering
error_reporting(0); // Suppress errors for AJAX
session_start();

// Check if this is an AJAX request - set flag early
$isAjax = isset($_GET['ajax']) && $_GET['ajax'] == '1';

try {
    include 'config.php'; // includes DB + logAction()
} catch (Exception $e) {
    if ($isAjax) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
        exit;
    }
    die('Database connection failed');
}

// Clean buffer for AJAX requests
if ($isAjax) {
    ob_clean();
}

if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Not logged in']);
        exit;
    }
    header("Location: index.php?admin_login=required");
    exit();
}

$username = $_SESSION['admin_id']; // current admin

if (isset($_GET['id'])) {
    $id = $_GET['id']; // Keep as string to match database type

    try {
        // ✅ Fetch resident before deleting - use string type for unique_id
        $stmt = $conn->prepare("SELECT * FROM residents WHERE unique_id = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $resident = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } catch (Exception $e) {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
            exit;
        }
        die('Database error');
    }

    if ($resident) {
        try {
            // ✅ Insert into deleted_residents (all columns)
            $stmt = $conn->prepare("INSERT INTO deleted_residents 
                (unique_id, surname, first_name, middle_name, age, sex, education, address, household_id, relationship, is_head, birthdate, place_of_birth, civil_status, citizenship, occupation_skills, is_pwd, pending_email, profile_image, skill_description, deleted_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param(
                "ssssisssssssssssssss",
                $resident['unique_id'], $resident['surname'], $resident['first_name'], $resident['middle_name'],
                $resident['age'], $resident['sex'], $resident['education'], $resident['address'], $resident['household_id'],
                $resident['relationship'], $resident['is_head'], $resident['birthdate'], $resident['place_of_birth'],
                $resident['civil_status'], $resident['citizenship'], $resident['occupation_skills'], $resident['is_pwd'],
                $resident['pending_email'], $resident['profile_image'], $resident['skill_description']
            );
            $stmt->execute();
            $stmt->close();

            // ✅ Delete from useraccounts first - use string type
            $stmt = $conn->prepare("DELETE FROM useraccounts WHERE userid = ?");
            $stmt->bind_param("s", $id);
            $stmt->execute();
            $stmt->close();

            // ✅ Then delete from residents - use string type
            $stmt = $conn->prepare("DELETE FROM residents WHERE unique_id = ?");
            $stmt->bind_param("s", $id);
            $stmt->execute();
            $stmt->close();

            // ✅ Log action properly
            $action = "Deleted Resident: {$resident['surname']}, {$resident['first_name']} (ID: {$resident['unique_id']})";
            $stmt = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
            $stmt->bind_param("ss", $username, $action);
            $stmt->execute();
            $stmt->close();

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
                exit;
            } else {
                header("Location: admin_dashboard.php?panel=view-residents&msg=Resident moved to recycle bin");
                exit;
            }
        } catch (Exception $e) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Delete failed: ' . $e->getMessage()]);
                exit;
            }
            die('Delete failed');
        }
    } else {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Resident not found']);
            exit;
        } else {
            header("Location: admin_dashboard.php?panel=view-residents&msg=Resident not found");
            exit;
        }
    }
}

// If no ID provided
if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'No ID provided']);
    exit;
}
?>
