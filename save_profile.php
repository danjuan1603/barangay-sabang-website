<?php
session_start();
include 'config.php';

// ✅ Check if admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    echo "⛔ Login first to access this page. <a href='login.html'>Login Here</a>";
    exit;
}
if (!isset($_SESSION['admin_username'])) {
    echo "⛔ Admin username not found in session. Please login again.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ✅ Collect and sanitize input data
    $surname            = trim($_POST['surname'] ?? '');
    $first_name         = trim($_POST['first_name'] ?? '');
    $middle_name        = trim($_POST['middle_name'] ?? '');
    $birthdate          = $_POST['birthdate'] ?? null;
    $age                = (int)($_POST['age'] ?? 0);
    $sex                = $_POST['sex'] ?? '';
    $education          = $_POST['education'] ?? '';
    $address            = trim($_POST['address'] ?? '');
    $household_id       = trim($_POST['household_id'] ?? '');
    $relationship       = trim($_POST['relationship'] ?? '');
    $is_head            = isset($_POST['is_head']) ? "Yes" : "No";
    $place_of_birth     = trim($_POST['place_of_birth'] ?? '');
    $civil_status       = $_POST['civil_status'] ?? '';
    $citizenship        = trim($_POST['citizenship'] ?? '');
    $occupation_skills  = trim($_POST['occupation_skills'] ?? '');
    $skill_description  = trim($_POST['skill_description'] ?? '');
    $is_pwd             = isset($_POST['is_pwd']) ? "Yes" : "No";

    // ✅ Default values for non-form columns
    $can_request   = 1;
    $email         = null;
    $pending_email = null;
    $verify_token  = null;
    $is_verified   = 0;
    $profile_image = null;

    // ✅ Validation (basic)
    if (empty($surname) || empty($first_name) || empty($birthdate) || empty($sex) || empty($address)) {
        echo "<script>alert('⚠ Please fill out all required fields before saving.');history.back();</script>";
        exit;
    }
// ✅ Prepare INSERT with all columns
$stmt = $conn->prepare("INSERT INTO residents (
    surname, first_name, middle_name, birthdate, age, sex, education, address, 
    household_id, relationship, is_head, place_of_birth, civil_status, citizenship, 
    occupation_skills, skill_description, is_pwd, 
    can_request, email, pending_email, verify_token, is_verified, profile_image
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param(
    "ssssissssssssssssssssis",
    $surname, $first_name, $middle_name, $birthdate, $age, $sex, $education, $address,
    $household_id, $relationship, $is_head, $place_of_birth, $civil_status, $citizenship,
    $occupation_skills, $skill_description, $is_pwd,
    $can_request, $email, $pending_email, $verify_token, $is_verified, $profile_image
);



    // ✅ Execute and handle result
    if ($stmt->execute()) {
        $new_id = $conn->insert_id;

        // Ensure unique_id column is set for the resident row (safe fallback matching by identifying fields)
        $upd = $conn->prepare("UPDATE residents SET unique_id = ? WHERE (unique_id IS NULL OR unique_id = '') AND surname = ? AND first_name = ? AND (middle_name = ? OR middle_name IS NULL) AND birthdate = ? LIMIT 1");
        if ($upd) {
            $mname = $middle_name ?? '';
            $bdate = $birthdate ?? null;
            $upd->bind_param('issss', $new_id, $surname, $first_name, $mname, $bdate);
            @$upd->execute();
            $upd->close();
        }

        // ✅ Create user account for this resident
        $acc = $conn->prepare("INSERT INTO useraccounts (userid, password, surname) VALUES (?, NULL, ?)");
        $acc->bind_param("is", $new_id, $surname);
        $acc->execute();

        // ✅ Log admin action
        $admin_username = $_SESSION['admin_username'];
        $action = "Added resident: $surname, $first_name (ID: $new_id)";
        $log = $conn->prepare("INSERT INTO admin_logs (username, action, action_time) VALUES (?, ?, NOW())");
        $log->bind_param("ss", $admin_username, $action);
        $log->execute();

        // ✅ Success modal + redirect
        echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Success</title>
</head>
<body>
<div id="successModal" style="display:flex;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);z-index:99999;align-items:center;justify-content:center;animation:fadeIn 0.3s ease;">
    <div style="background:#fff;padding:0;border-radius:20px;box-shadow:0 10px 40px rgba(25,118,210,0.2);max-width:450px;width:90%;overflow:hidden;animation:slideUp 0.4s ease;">
        <div style="padding:40px 30px;background:linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);text-align:center;">
            <div style="width:80px;height:80px;margin:0 auto 20px;background:linear-gradient(135deg,#1976d2,#43e97b);border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 8px 20px rgba(25,118,210,0.3);">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="white" viewBox="0 0 16 16">
                    <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/>
                </svg>
            </div>
            <h2 style="color:#1976d2;font-weight:700;margin-bottom:12px;font-size:1.5rem;">Profile Saved Successfully!</h2>
            <p style="color:#0369a1;font-size:1.05rem;margin-bottom:8px;font-weight:500;">Resident: '.htmlspecialchars($surname.', '.$first_name).'</p>
            <p style="color:#64748b;font-size:0.95rem;margin-bottom:0;">Redirecting to residents list...</p>
        </div>
    </div>
</div>
<style>
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes slideUp {
    from { transform: translateY(30px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
</style>
<script>
// Play success sound
try {
    var audio = new Audio("ChatNotif.mp3");
    audio.play().catch(function(e) { console.log("Audio play failed:", e); });
} catch(e) {}

setTimeout(function(){ 
    window.location.href = "admin_dashboard.php?panel=view-residents"; 
}, 2200);
</script>
</body>
</html>';
        exit;
    } else {
        echo "❌ Database Error: " . $stmt->error;
    }
}
?>
