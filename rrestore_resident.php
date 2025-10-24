<?php
include 'config.php';
session_start();

// ✅ Ensure admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: index.php?admin_login=required");
    exit();
}

$admin_username = $_SESSION['admin_username']; // current admin

if (isset($_GET['id'])) {
    $id = (int)$_GET['id']; // ensure integer

    // ✅ Fetch deleted resident
    $stmt = $conn->prepare("SELECT * FROM deleted_residents WHERE unique_id = ?");
    $stmt->bind_param("i", $id); // unique_id is INT
    $stmt->execute();
    $resident = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($resident) {
        // ✅ Insert back into residents (all columns)
        $stmt = $conn->prepare("INSERT INTO residents 
            (unique_id, surname, first_name, middle_name, age, sex, education, address, household_id, relationship, is_head, birthdate, place_of_birth, civil_status, citizenship, occupation_skills, is_pwd, pending_email, profile_image, skill_description) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("isssisssssssssssssss",
            $resident['unique_id'], 
            $resident['surname'], 
            $resident['first_name'], 
            $resident['middle_name'],
            $resident['age'], 
            $resident['sex'], 
            $resident['education'], $resident['address'], $resident['household_id'],
            $resident['relationship'], $resident['is_head'], $resident['birthdate'], $resident['place_of_birth'],
            $resident['civil_status'], $resident['citizenship'], $resident['occupation_skills'], $resident['is_pwd'],
            $resident['pending_email'], $resident['profile_image'], $resident['skill_description']
        );

        if ($stmt->execute()) {
            $stmt->close();

            // ✅ Insert into useraccounts if not exists
            $check = $conn->prepare("SELECT userid FROM useraccounts WHERE userid = ?");
            $check->bind_param("i", $resident['unique_id']);
            $check->execute();
            $check->store_result();
            if ($check->num_rows === 0) {
                $check->close();
                $acc = $conn->prepare("INSERT INTO useraccounts (userid, password, surname) VALUES (?, NULL, ?)");
                $acc->bind_param("is", $resident['unique_id'], $resident['surname']);
                $acc->execute();
                $acc->close();
            } else {
                $check->close();
            }

            // ✅ Remove from recycle bin
            $stmt = $conn->prepare("DELETE FROM deleted_residents WHERE unique_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            // ✅ Log restore action
            $action = "Restored Resident: {$resident['surname']}, {$resident['first_name']} (ID: {$resident['unique_id']})";
            $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
            $log->bind_param("ss", $admin_username, $action);
            $log->execute();
            $log->close();

            if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
                exit;
            } else {
                header("Location: rdeleted_residents.php?msg=Resident restored successfully");
                exit;
            }
        } else {
            $error = $stmt->error;
            $stmt->close();
            if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $error]);
                exit;
            } else {
                header("Location: rdeleted_residents.php?msg=Restore failed: " . urlencode($error));
                exit;
            }
        }
    } else {
        header("Location: rdeleted_residents.php?msg=Resident not found");
        exit;
    }
}
?>
