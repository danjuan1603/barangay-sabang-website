<?php
session_start();
include 'config.php';

// Load PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ . '/vendor/autoload.php';

// Mail settings
$mailHost = "smtp.gmail.com";
$mailUser = "mikronario@gmail.com";
$mailPass = "tzjg mxoh rgek vnuy";

function sendVerificationEmail($email, $userid, $token) {
    global $mailHost, $mailUser, $mailPass;

    $mail = new PHPMailer(true);
    try {
        // Enable verbose debug output (comment out in production)
        // $mail->SMTPDebug = 2;
        
        $mail->isSMTP();
        $mail->Host = $mailHost;
        $mail->SMTPAuth = true;
        $mail->Username = $mailUser;
        $mail->Password = $mailPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Set timeouts to fail faster if there are connection issues
        $mail->Timeout = 10; // Connection timeout (default is 300 seconds)
        $mail->SMTPKeepAlive = false; // Don't keep connection alive

        $mail->setFrom($mailUser, 'Barangay Email Verification');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = "Verify Your New Email Address";

        $verify_link = "http://localhost/Webs/verify_email.php?uid=" . urlencode($userid) . "&token=" . urlencode($token);
        
        // Better formatted email body
        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #15b300; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                .button { display: inline-block; padding: 12px 30px; background: #15b300; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Email Verification Required</h2>
                </div>
                <div class='content'>
                    <p>Hello,</p>
                    <p>You have requested to change your email address. Please click the button below to verify your new email address:</p>
                    <p style='text-align: center;'>
                        <a href='$verify_link' class='button'>Verify Email Address</a>
                    </p>
                    <p>Or copy and paste this link into your browser:</p>
                    <p style='word-break: break-all; background: #fff; padding: 10px; border-radius: 4px;'>$verify_link</p>
                    <p><strong>Note:</strong> This link will expire once used. If you did not request this change, please ignore this email.</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message from Barangay Web System. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Plain text alternative
        $mail->AltBody = "Click this link to verify your new email: $verify_link\n\nIf you did not request this change, please ignore this email.";

        $mail->send();
        error_log("Verification email sent successfully to: " . $email);
        return true;
    } catch (Exception $e) {
        // Show error for debugging
        $errorMsg = "Mailer Error: " . $mail->ErrorInfo;
        error_log($errorMsg);
        error_log("Exception: " . $e->getMessage());
        $GLOBALS['feedback'] = $errorMsg;
        return false;
    }
}

$resident = null;
$feedback = "";

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']) {
    $userid = $_SESSION['userid'];


    // Fetch resident
    $sql = "SELECT * FROM residents WHERE unique_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $userid);
    $stmt->execute();
    $resident = $stmt->get_result()->fetch_assoc();

        $emailWarning = "";
if (empty($resident['email'])) {
    $emailWarning = true;
}


    /* ===========================
       Handle password change
    ============================ */
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['old_password'], $_POST['new_password'], $_POST['confirm_password'])) {
        $old_password     = trim($_POST['old_password']);
        $new_password     = trim($_POST['new_password']);
        $confirm_password = trim($_POST['confirm_password']);

        $sql = "SELECT password FROM useraccounts WHERE userid=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $userid);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result && password_verify($old_password, $result['password'])) {
            if ($new_password === $confirm_password) {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE useraccounts SET password=? WHERE userid=?");
                $update->bind_param("ss", $hashed, $userid);
                $update->execute();
                $feedback = "<i class='fas fa-check-circle' style='color:#15b300;'></i> Password changed successfully.";
            } else {
                $feedback = "<i class='fas fa-times-circle' style='color:#e53935;'></i> New password and confirmation do not match.";
            }
        } else {
            $feedback = "<i class='fas fa-times-circle' style='color:#e53935;'></i> Old password is incorrect.";
        }
    }

    /* ===========================
       Handle profile update
    ============================ */
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
        $first_name        = trim($_POST['first_name']);
        $middle_name       = trim($_POST['middle_name']);
        $surname           = trim($_POST['surname']);
        $age               = intval($_POST['age']);
        $new_email         = trim($_POST['email']);
        $address           = trim($_POST['address']);
        $occupation_skills = trim($_POST['occupation_skills']);
        $education         = trim($_POST['education']);
        $skill_description = trim($_POST['skill_description']);
        $profile_image     = isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0
            ? "uploads/" . $userid . "_" . basename($_FILES["profile_image"]["name"])
            : $resident['profile_image'];

        // Handle image upload if new image is provided
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $target_file = $target_dir . $userid . "_" . basename($_FILES["profile_image"]["name"]);
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            $check = getimagesize($_FILES["profile_image"]["tmp_name"]);
            if ($check !== false && ($imageFileType == "jpg" || $imageFileType == "jpeg" || $imageFileType == "png")) {
                move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file);
                $profile_image = $target_file;
            }
        }

        // If email is changed → require verification
        if (!empty($new_email) && $new_email !== $resident['email']) {
            $verification_code = bin2hex(random_bytes(16)); // secure token

            $sql = "UPDATE residents 
                    SET first_name=?, middle_name=?, surname=?, age=?, address=?, 
                        occupation_skills=?, skill_description=?, education=?, pending_email=?, verify_token=?, is_verified=0, profile_image=? 
                    WHERE unique_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssisisssss", 
                $first_name, $middle_name, $surname, $age, $address, 
                $occupation_skills, $skill_description, $education, $new_email, $verification_code, $profile_image, $userid
            );
            $stmt->execute();

            if (sendVerificationEmail($new_email, $userid, $verification_code)) {
                $feedback = "<i class='fas fa-check-circle' style='color:#15b300;'></i> <strong>Profile updated successfully!</strong> A verification email has been sent to <strong>" . htmlspecialchars($new_email) . "</strong>. Please check your inbox and verify your email.";
                echo "<script>
                    window.onload = function() {
                        alert('✓ SUCCESS: Profile updated!\\n\\nA verification email has been sent to:\\n" . addslashes($new_email) . "\\n\\nPlease check your inbox and click the verification link to activate your new email address.');
                    }
                </script>";
            } else {
                $feedback = "<i class='fas fa-exclamation-triangle' style='color:#fbc02d;'></i> Profile updated but <strong>failed to send verification email</strong>. Please check your email address or contact support.";
                echo "<script>
                    window.onload = function() {
                        alert('⚠ WARNING: Profile updated but email verification failed.\\n\\nPlease contact support or try updating your email again.');
                    }
                </script>";
            }
        } else {
            // No email change → update directly
            $sql = "UPDATE residents 
                    SET first_name=?, middle_name=?, surname=?, age=?, address=?, 
                        occupation_skills=?, skill_description=?, education=?, profile_image=? 
                    WHERE unique_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssisissss", 
                $first_name, $middle_name, $surname, $age, $address, 
                $occupation_skills, $skill_description, $education, $profile_image, $userid
            );
            $stmt->execute();

            $feedback = "<i class='fas fa-check-circle' style='color:#15b300;'></i> Profile updated successfully.";
        }
    }

    /* ===========================
       Handle profile update (edit)
    ============================ */
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_profile'])) {
        // Allow updating email, name fields, civil_status, place_of_birth, and education
        $new_email         = trim($_POST['email']);
        $surname           = trim($_POST['surname']);
        $first_name        = trim($_POST['first_name']);
        $middle_name       = trim($_POST['middle_name']);
        $civil_status      = trim($_POST['civil_status']);
        $place_of_birth    = trim($_POST['place_of_birth']);
        $education         = trim($_POST['education']);
        $occupation_skills = trim($_POST['occupation_skills'] ?? '');
        $skill_description = trim($_POST['skill_description'] ?? '');
        $profile_image     = $resident['profile_image'];

        // Handle image upload if new image is provided
        if (
            isset($_FILES['profile_image']) &&
            $_FILES['profile_image']['error'] === UPLOAD_ERR_OK &&
            !empty($_FILES['profile_image']['tmp_name'])
        ) {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $target_file = $target_dir . $userid . "_" . basename($_FILES["profile_image"]["name"]);
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            $check = getimagesize($_FILES["profile_image"]["tmp_name"]);
            if ($check !== false && in_array($imageFileType, ["jpg", "jpeg", "png"])) {
                move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file);
                $profile_image = $target_file;
            }
        }

        // If email is changed → require verification
        if (!empty($new_email) && $new_email !== $resident['email']) {
            $verification_code = bin2hex(random_bytes(16)); // secure token

            $sql = "UPDATE residents 
                    SET surname=?, first_name=?, middle_name=?, civil_status=?, place_of_birth=?, education=?, 
                        occupation_skills=?, skill_description=?, pending_email=?, verify_token=?, is_verified=0, profile_image=? 
                    WHERE unique_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssssssss", 
                $surname, $first_name, $middle_name, $civil_status, $place_of_birth, $education,
                $occupation_skills, $skill_description, $new_email, $verification_code, $profile_image, $userid
            );
            $stmt->execute();

            if (sendVerificationEmail($new_email, $userid, $verification_code)) {
                $feedback = "<i class='fas fa-check-circle' style='color:#15b300;'></i> <strong>Profile updated successfully!</strong> A verification email has been sent to <strong>" . htmlspecialchars($new_email) . "</strong>. Please check your inbox and verify your email.";
                echo "<script>
                    window.onload = function() {
                        alert('✓ SUCCESS: Profile updated!\\n\\nA verification email has been sent to:\\n" . addslashes($new_email) . "\\n\\nPlease check your inbox and click the verification link to activate your new email address.');
                    }
                </script>";
            } else {
                $feedback = "<i class='fas fa-exclamation-triangle' style='color:#fbc02d;'></i> Profile updated but <strong>failed to send verification email</strong>. Please check your email address or contact support.";
                echo "<script>
                    window.onload = function() {
                        alert('⚠ WARNING: Profile updated but email verification failed.\\n\\nPlease contact support or try updating your email again.');
                    }
                </script>";
            }
        } else {
            // No email change → update directly
            $sql = "UPDATE residents 
                    SET surname=?, first_name=?, middle_name=?, civil_status=?, place_of_birth=?, education=?, 
                        occupation_skills=?, skill_description=?, profile_image=? 
                    WHERE unique_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssssss", 
                $surname, $first_name, $middle_name, $civil_status, $place_of_birth, $education,
                $occupation_skills, $skill_description, $profile_image, $userid
            );
            $stmt->execute();

            $feedback = "<i class='fas fa-check-circle' style='color:#15b300;'></i> Profile updated successfully.";
        }

        // Refresh resident data after update
        $stmt = $conn->prepare("SELECT * FROM residents WHERE unique_id=?");
        $stmt->bind_param("s", $userid);
        $stmt->execute();
        $resident = $stmt->get_result()->fetch_assoc();
    }

    /* ===========================
       Handle Jobfinder skills update
    ============================ */
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_jobfinder_skills'])) {
        $occupation_skills = trim($_POST['occupation_skills']);
        $skill_description = trim($_POST['skill_description']);

        $sql = "UPDATE residents 
                SET occupation_skills=?, skill_description=? 
                WHERE unique_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $occupation_skills, $skill_description, $userid);
        $stmt->execute();

        $feedback = "<i class='fas fa-check-circle' style='color:#15b300;'></i> Skills updated successfully.";

        // Refresh resident data after update
        $stmt = $conn->prepare("SELECT * FROM residents WHERE unique_id=?");
        $stmt->bind_param("s", $userid);
        $stmt->execute();
        $resident = $stmt->get_result()->fetch_assoc();
    }

    /* ===========================
       Handle Jobfinder active/deactivate toggle
    ============================ */
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['toggle_jobfinder_active'])) {
        $current_status = isset($resident['jobfinder_active']) ? $resident['jobfinder_active'] : 1;
        $new_status = $current_status == 1 ? 0 : 1;

        $sql = "UPDATE residents SET jobfinder_active=? WHERE unique_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $new_status, $userid);
        $stmt->execute();

        $feedback = $new_status == 1 
            ? "<i class='fas fa-check-circle' style='color:#15b300;'></i> Jobfinder profile activated. You will now appear in Jobfinder listings."
            : "<i class='fas fa-info-circle' style='color:#fbbf24;'></i> Jobfinder profile deactivated. You will not appear in Jobfinder listings.";

        // Refresh resident data after update
        $stmt = $conn->prepare("SELECT * FROM residents WHERE unique_id=?");
        $stmt->bind_param("s", $userid);
        $stmt->execute();
        $resident = $stmt->get_result()->fetch_assoc();
    }
}

$userid = $_SESSION['userid'];
$stmt = $conn->prepare("SELECT * FROM residents WHERE unique_id=?");
$stmt->bind_param("s", $userid);
$stmt->execute();
$resident = $stmt->get_result()->fetch_assoc();

// Handle image upload (only when editing)
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_FILES['profile_image']) &&
    isset($_FILES['profile_image']['tmp_name']) &&
    $_FILES['profile_image']['error'] === UPLOAD_ERR_OK &&
    !empty($_FILES['profile_image']['tmp_name'])
) {
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
    $target_file = $target_dir . $userid . "_" . basename($_FILES["profile_image"]["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $check = getimagesize($_FILES["profile_image"]["tmp_name"]);
    if ($check !== false && ($imageFileType == "jpg" || $imageFileType == "jpeg" || $imageFileType == "png")) {
        move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file);
        $stmt = $conn->prepare("UPDATE residents SET profile_image=? WHERE unique_id=?");
        $stmt->bind_param("ss", $target_file, $userid);
        $stmt->execute();
        $resident['profile_image'] = $target_file;
    }
}

// Handle profile update (add your own update logic here)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_profile'])) {
    // Example: update first_name, middle_name, surname, etc.
    // Add your own validation and update logic here
}
?>
<?php
// Language switch logic
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'] === 'tl' ? 'tl' : 'en';
}
$lang = $_SESSION['lang'] ?? 'en';
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $lang === 'tl' ? 'Profile ng Gumagamit' : 'User Profile' ?></title>
    <link rel="stylesheet" href="styles.css">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
       body {
    background: #f5f7fa;
    margin: 0;
    font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
}

       .container-flex {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    padding: 20px 12px; /* ensure some breathing room on small screens */
}

/* Profile card */
.profile-card-panel {
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 6px 24px rgba(0, 0, 0, 0.08);
    padding: 20px;
    max-width: 1050px;   /* responsive: allow shrinking on smaller screens */
    width: 100%;
    min-height: 650px;
    display: flex;
    gap: 20px;
    animation: fadeScale 0.4s ease;
}

@keyframes fadeScale {
    from { transform: scale(0.96); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

/* Sidebar */
.sidebar {
    width: 200px;
    background: #fff;
    border-radius: 16px;
    padding: 20px 10px;
    display: flex;
    flex-direction: column;
    justify-content: space-between; /* pushes logout to bottom */
    box-shadow: 0 4px 16px rgba(0,0,0,0.06);
}

/* Top section inside sidebar */
.sidebar-top {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}

.sidebar .icon-top {
    font-size: 28px;
    color: #15b300;
    margin-bottom: 20px;
}

.sidebar-btn {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    min-height: 44px; /* touch-friendly */
    border: none;
    border-radius: 12px;
    font-size: 0.95rem;
    font-weight: 500;
    background: #f3f4f6;
    color: #333;
    cursor: pointer;
    transition: all 0.2s ease;
}

.sidebar-btn:hover {
    background: rgba(21, 179, 0, 0.08);
    color: #15b300;
    transform: translateX(4px);
}

.sidebar-btn.active {
    background: linear-gradient(135deg, #15b300 0%, #0e7c00 100%);
    color: #fff;
    box-shadow: 0 4px 12px rgba(21, 179, 0, 0.3);
}

    /* Mobile-only logout button (hidden on desktop) */
    .mobile-logout { display: none; }
    .mobile-home { display: none; }

/* Logout button (always bottom) */
.sidebar-bottom .sidebar-btn {
    background: #fef2f2;
    color: #b91c1c;
}
.sidebar-bottom .sidebar-btn:hover {
    background: #fee2e2;
}

/* Main content */
.main-content {
    flex: 1;
    padding: 20px;
}

.content-section {
    display: none;
    height: 100%; /* fills fixed card height */
    animation: fadeIn 0.3s ease;
}
.content-section.active {
    display: block;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); } /* semicolon */
}

/* Profile avatar */
.profile-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    overflow: hidden;
    margin: 0 auto 20px auto;
    box-shadow: 0 4px 12px rgba(52,168,83,0.12);
    transition: transform 0.3s ease;
}
.profile-avatar:hover {
    transform: scale(1.05);
}

/* Profile details grid */
.profile-details-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px 32px;
    max-width: 900px;
    margin: 0 auto;
}

.profile-details-item {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.profile-details-item .label {
    font-weight: 600;
    color: #15b300;
    margin-bottom: 0;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.profile-details-item .value,
.profile-details-item input,
.profile-details-item select {
    font-size: 0.95rem;
    background: transparent;
    border: none;
    border-bottom: 2px solid #e8e8e8;
    padding: 8px 2px 6px 2px;
    color: #2c2c2c;
    font-weight: 400;
    transition: all 0.2s ease;
    border-radius: 0;
}

.profile-details-item .value {
    display: block;
    padding: 8px 2px 6px 2px;
    min-height: 20px;
    line-height: 1.3;
}

.profile-details-item input:focus,
.profile-details-item select:focus {
    outline: none;
    border-bottom-color: #15b300;
    background: transparent;
}

.profile-details-item:hover .value {
    border-bottom-color: #b8e6b8;
}

/* Buttons */
.edit-btn {
    background: linear-gradient(135deg, #15b300 0%, #0e7c00 100%);
    color: #fff;
    border: none;
    border-radius: 12px;
    padding: 10px 18px;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(21, 179, 0, 0.3);
}
.edit-btn:hover {
    background: linear-gradient(135deg, #0e7c00 0%, #0a5a00 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(21, 179, 0, 0.4);
}

.edit-image-btn {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: linear-gradient(135deg, #15b300 0%, #0e7c00 100%);
    border: none;
    border-radius: 50%;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(21, 179, 0, 0.3);
    cursor: pointer;
    transition: all 0.2s ease;
    z-index: 2;
}
.edit-image-btn:hover {
    background: linear-gradient(135deg, #0e7c00 0%, #0a5a00 100%);
    transform: translate(-50%, -50%) scale(1.1);
    box-shadow: 0 6px 16px rgba(21, 179, 0, 0.4);
}

    /* Edit form: mobile-friendly adjustments */
    .edit-form .profile-avatar { width: 84px; height: 84px; margin-bottom: 12px; }
    .edit-form input[type="text"], .edit-form input[type="email"], .edit-form input[type="date"], .edit-form input[type="number"], .edit-form select {
        width: 100%;
        padding: 12px 10px;
        font-size: 1rem;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
        background: #fff;
        box-sizing: border-box;
    }
    .edit-form .profile-details-grid { gap: 12px; }

    /* Bottom action bar on small screens */
    .edit-action-bar { display: none; }

/* Language Switch Toggle CSS */
.switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 28px;
}
.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}
.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #e53935;
    -webkit-transition: .4s;
    transition: .4s;
    border-radius: 34px;
}
.slider:before {
    position: absolute;
    content: "";
    height: 22px;
    width: 22px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    -webkit-transition: .4s;
    transition: .4s;
    border-radius: 50%;
    box-shadow: 0 2px 8px rgba(52,168,83,0.18);
}
input:checked + .slider {
    background-color: #15b300;
}
input:checked + .slider:before {
    transform: translateX(22px);
}

/* Comments scrollbar styling */
.comments-container::-webkit-scrollbar {
    width: 6px;
}
.comments-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}
.comments-container::-webkit-scrollbar-thumb {
    background: #15b300;
    border-radius: 10px;
}
.comments-container::-webkit-scrollbar-thumb:hover {
    background: #0e7c00;
}

/* Verified badge positioning fix */
.verified-badge-container {
    position: relative;
    width: 100px !important;
    height: 100px !important;
    margin: 0 auto 12px !important;
    display: block !important;
}

.verified-badge {
    position: absolute !important;
    bottom: -3px !important;
    right: -3px !important;
    background: #10b981 !important;
    color: #fff !important;
    border-radius: 50% !important;
    width: 32px !important;
    height: 32px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 18px !important;
    font-weight: bold !important;
    border: 3px solid #fff !important;
    box-shadow: 0 2px 10px rgba(16,185,129,0.4) !important;
}
    </style>
    <style>
    /* Responsive and off-canvas sidebar styles */
    .burger-btn {
        position: absolute;
        top: 18px;
        right: 18px;
        width: 44px;
        height: 44px;
        border-radius: 12px;
        border: none;
        background: linear-gradient(135deg, #15b300 0%, #0e7c00 100%);
        color: #fff;
        display: none;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 1200;
        box-shadow: 0 6px 18px rgba(21, 179, 0, 0.3);
        transition: all 0.2s ease;
    }
    .burger-btn:hover {
        background: linear-gradient(135deg, #0e7c00 0%, #0a5a00 100%);
        transform: scale(1.05);
        box-shadow: 0 8px 20px rgba(21, 179, 0, 0.4);
    }

    /* Overlay when sidebar is open on mobile */
    #sidebarOverlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.32);
        display: none;
        z-index: 1000;
    }

    /* Off-canvas behavior for sidebar */
    .profile-card-panel { position: relative; }
    .sidebar {
        transition: transform 0.28s ease, opacity 0.28s ease;
    }
    /* Hide mobile controls when sidebar is open */
    .mobile-controls-wrapper.hidden {
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.2s ease;
    }

    @media (max-width: 900px) {
        .container-flex { padding: 12px; }
        .profile-card-panel {
            width: 100%;
            min-height: 100vh;
            flex-direction: column;
            gap: 12px;
            padding: 16px;
        }
        .sidebar {
            position: fixed;
            top: 0;
            right: 0;
            height: 100vh;
            width: 280px;
            transform: translateX(110%);
            box-shadow: -6px 0 24px rgba(0,0,0,0.12);
            border-radius: 0 0 0 12px;
            background: #fff;
            z-index: 1100;
            padding-top: 24px;
        }
        .sidebar.open {
            transform: translateX(0);
        }
        .main-content {
            width: 100%;
            padding: 12px 8px 80px 8px;
            /* keep space at top for the burger + notification on mobile */
            padding-top: 64px;
        }
        .burger-btn { display: flex; }
        #backBtn { display:none; } /* hide large-screen home button to keep header clean */
        .sidebar-bottom { position: absolute; bottom: 24px; left: 0; right: 0; }
        .mobile-logout { display: flex; }
        .mobile-home { display: flex; }
        /* Position notification bell beside the burger (top-right) */
        /* Position the notification next to the burger without making it float.
           Use absolute positioning relative to the profile card panel so it moves
           with the page layout instead of being fixed to the viewport. */
        #nav-notification {
            position: absolute !important;
            top: 18px !important;
            right: calc(18px + 44px + 8px) !important; /* aligns left of burger */
            z-index: 1300 !important;
            display: flex !important;
            align-items: center;
            pointer-events: auto;
        }
        /* Align dropdown under the bell */
        #nav-notification #notif-dropdown {
            position: absolute !important;
            right: 0 !important;
            left: auto !important;
            top: 48px !important;
            transform: none !important;
            min-width: 220px !important;
        }
    }

    /* When small, stack the edit form into a single column and pin actions */
    @media (max-width: 520px) {
        .edit-form .profile-details-grid { grid-template-columns: 1fr !important; }
        .edit-form .profile-details-item { width: 100%; }
        .edit-form .profile-avatar { width: 84px; height:84px; }
        .edit-action-bar { display:flex; position: fixed; bottom: 12px; left: 12px; right: 12px; gap:12px; z-index: 1400; }
        .edit-action-bar button { flex:1; padding:12px 14px; font-size:1rem; border-radius:10px; }
        .edit-form .desktop-actions { display:none; }
    }

    @media (max-width: 768px) {
        .profile-details-grid { 
            grid-template-columns: repeat(1, 1fr) !important;
            gap: 24px;
        }
        .profile-details-item[style*="grid-column"] {
            grid-column: span 1 !important;
        }
        .profile-avatar { width: 130px !important; height: 130px !important; }
    }
    
    @media (max-width: 520px) {
        .profile-details-grid { 
            grid-template-columns: repeat(1, 1fr) !important;
            gap: 20px;
        }
        .profile-card-panel { padding: 12px; }
        .profile-avatar { width: 120px !important; height: 120px !important; }
        
        h1 {
            font-size: 1.5rem !important;
        }
        
        .profile-details-item .label {
            font-size: 0.7rem;
        }
        
        .profile-details-item .value {
            font-size: 1rem;
        }
    }
    </style>
    <script>
        function showSection(section) {
            document.querySelectorAll('.sidebar-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.content-section').forEach(sec => sec.classList.remove('active'));
            const btn = document.getElementById(section + 'Btn');
            const sec = document.getElementById(section + 'Section');
            if (btn) btn.classList.add('active');
            if (sec) sec.classList.add('active');
            
            // Show/hide Jobfinder info button based on section
            const jobfinderInfoBtn = document.getElementById('jobfinderInfoBtn');
            if (jobfinderInfoBtn) {
                if (section === 'jobfinder') {
                    jobfinderInfoBtn.style.display = 'flex';
                } else {
                    jobfinderInfoBtn.style.display = 'none';
                }
            }
        }
        function triggerImageUpload() {
            document.getElementById('profile_image').click();
        }
        function showPasswordModal() {
            showSection('password');
        }
        function hidePasswordModal() {
            showSection('profile');
        }
        function toggleJobfinderEdit(edit) {
            const viewMode = document.getElementById('jobfinderViewMode');
            const editMode = document.getElementById('jobfinderEditMode');
            if (edit) {
                viewMode.style.display = 'none';
                editMode.style.display = 'block';
            } else {
                viewMode.style.display = 'block';
                editMode.style.display = 'none';
            }
        }
        document.addEventListener('DOMContentLoaded', function() {
            // default active section
            showSection('profile');
        });
    </script>
</head>
<body>
<!-- Overlay for mobile sidebar -->
<div id="sidebarOverlay" onclick="toggleSidebar(false)" aria-hidden="true"></div>

<!-- Burger button (mobile) removed from top-level; it's inserted inside the profile card panel below so its positioning is relative to that panel. -->
<div class="container-flex">
    <div class="profile-card-panel">
        <!-- Mobile controls wrapper: places burger and notification in same positioned container -->
        <div class="mobile-controls-wrapper" id="mobileControls" style="position: absolute; top: 12px; right: 12px; display:flex; gap:8px; align-items:center; z-index:1400;">
            <div id="nav-notification" style="position: absolute; right: calc(18px + 44px + 8px); top: 18px; display:flex; align-items:center; gap:8px;">
                <button id="notif-bell" aria-label="Notifications" style="background:none; border:none; cursor:pointer; position:relative; padding:10px; border-radius:8px;">
                    <i class="fas fa-bell" style="font-size:20px; color:#007bff;"></i>
                    <span id="notif-badge" style="display:none; position:absolute; top:6px; right:6px; background:#e53935; color:#fff; border-radius:50%; padding:2px 6px; font-size:12px; font-weight:bold;">0</span>
                </button>
                <button id="jobfinderInfoBtn" onclick="showJobfinderInfoModal()" aria-label="Jobfinder Info" title="<?= $lang === 'tl' ? 'Paano Gamitin ang Jobfinder' : 'How to Use Jobfinder' ?>" style="display:none; background:linear-gradient(135deg, #2196f3 0%, #1976d2 100%); border:none; cursor:pointer; position:relative; padding:8px; border-radius:8px; width:36px; height:36px; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 8px rgba(33,150,243,0.3); transition:all 0.2s ease;">
                    <i class="fas fa-info-circle" style="font-size:16px; color:#fff;"></i>
                </button>
                <div id="notif-dropdown" style="display:none; position:absolute; right:8px; top:48px; background:#fff; box-shadow:0 6px 24px rgba(0,0,0,0.12); border-radius:10px; min-width:260px; z-index:999;">
                    <div style="padding:12px 16px; border-bottom:1px solid #eee; font-weight:bold; color:#007bff;">Notifications</div>
                    <div id="notif-list" style="max-height:300px; overflow-y:auto;"></div>
                    <div style="padding:8px 16px; text-align:right;"><button id="notif-close" style="background:none; border:none; color:#007bff; cursor:pointer;">Close</button></div>
                </div>
            </div>
            <button class="burger-btn" id="burgerBtn" aria-label="Open menu" title="Menu" onclick="toggleSidebar(true)" style="position: relative;">
                <svg width="20" height="14" viewBox="0 0 20 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="20" height="2" rx="1" fill="white"/>
                    <rect y="6" width="20" height="2" rx="1" fill="white"/>
                    <rect y="12" width="20" height="2" rx="1" fill="white"/>
                </svg>
            </button>
        </div>
        <!-- Sidebar -->
        <div class="sidebar">
    <div class="sidebar-top">
        <div class="edit-logo">
            <!-- Settings gear icon -->
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="#34a853" stroke-width="2" viewBox="0 0 24 24" width="40" height="40">
                <circle cx="12" cy="12" r="3" stroke="#34a853" stroke-width="2" fill="#e5e5e5"/>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 
                2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 
                0-1 1.51V21a2 2 0 0 1-4 0v-.09a1.65 1.65 0 0 
                0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 
                0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 
                0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 
                2 0 0 1 0-4h.09a1.65 1.65 0 0 
                0 1.51-1 1.65 1.65 0 0 
                0-.33-1.82l-.06-.06a2 2 0 1 
                1 2.83-2.83l.06.06a1.65 1.65 
                0 0 0 1.82.33h.09A1.65 1.65 
                0 0 0 9 3.09V3a2 2 0 0 
                1 4 0v.09a1.65 1.65 0 0 
                0 1 1.51h.09a1.65 1.65 0 
                0 0 1.82-.33l.06-.06a2 
                2 0 1 1 2.83 2.83l-.06.06a1.65 
                1.65 0 0 0-.33 1.82v.09a1.65 
                1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 
                1.65 0 0 0-1.51 1z"/>
            </svg>
        </div>
        <button id="backBtn" class="sidebar-btn" onclick="sessionStorage.setItem('internalNav', 'true'); window.location.href='index.php?lang=<?= $lang ?>'">
         <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 2px;">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
            <polyline points="9 22 9 12 15 12 15 22"></polyline>
        </svg><span id="homeLabel"></span>
        </button>

        <!-- Mobile-only Home inside sidebar for off-canvas menu -->
        <button id="mobileHomeBtn" class="sidebar-btn mobile-home" onclick="sessionStorage.setItem('internalNav', 'true'); window.location.href='index.php?lang=<?= $lang ?>'">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 2px;">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg> <span id="mobileHomeLabel"></span>
        </button>

    <button id="profileBtn" class="sidebar-btn" onclick="showSection('profile')">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 2px;">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
        </svg>
        <span id="profileLabel"></span>
    </button>
    <button id="editBtn" class="sidebar-btn" onclick="showSection('edit')">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 2px;">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
        </svg> <span id="editLabel"></span>
    </button>
    <button id="statusBtn" class="sidebar-btn" onclick="showSection('status')">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 2px;">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
            <line x1="16" y1="13" x2="8" y2="13"></line>
            <line x1="16" y1="17" x2="8" y2="17"></line>
            <polyline points="10 9 9 9 8 9"></polyline>
        </svg> <span id="statusLabel"></span>
    </button>
    <button id="passwordBtn" class="sidebar-btn" onclick="showSection('password')">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 2px;">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
        </svg> <span id="passwordLabel"></span>
    </button>
    <button id="jobfinderBtn" class="sidebar-btn" onclick="showSection('jobfinder')">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 2px;">
            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
        </svg> <span id="jobfinderLabel"></span>
    </button>
  
        <!-- Mobile-only logout inside sidebar for accessibility when off-canvas -->
        
    </div>

    <div class="sidebar-bottom">
       
     <div style="margin: 18px 0; display: flex; justify-content: center; align-items: center;">
                    
      <label class="switch">
        <input type="checkbox" id="langToggle" <?= $lang === 'tl' ? 'checked' : '' ?> />
        <span class="slider"></span>
      </label>
      <span id="langSwitchLabel" style="margin-left:10px; font-weight:600; color:#34a853;">Tagalog</span>
    </div>
    <button id="logoutBtn" class="sidebar-btn" onclick="window.location.href='logout.php'">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 2px;">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
            <polyline points="16 17 21 12 16 7"></polyline>
            <line x1="21" y1="12" x2="9" y2="12"></line>
        </svg> <span id="logoutLabel"></span>
    </button>
      <h1> </h1>

</div>

</div>

        <!-- Main Content -->
        <div class="main-content">
                <!-- Header row: title + notification (responsive) -->
                <div id="headerRow" style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:16px;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <button id="backBtnSmall" class="sidebar-btn" style="display:none;" onclick="sessionStorage.setItem('internalNav', 'true'); window.location.href='index.php?lang=<?= $lang ?>'"><i class="fas fa-home"></i></button>
                        <!--<h1 style="margin:0; font-size:1.2rem; color:#34a853;">Profile</h1>-->
                    </div>
                    <div id="nav-notification" style="position:relative; display:flex; align-items:center; gap:8px; visibility:hidden;">
                        <!-- notification moved into profile-card-panel for mobile; placeholder kept for desktop layout -->
                    </div>
                </div>
            <?php if (!empty($emailWarning)): ?>
            <script>
                window.onload = function() {
                    showSection('profile');
                    setTimeout(function() {
                        var popup = document.createElement('div');
                        popup.style.position = 'fixed';
                        popup.style.top = '50%';
                        popup.style.left = '50%';
                        popup.style.transform = 'translate(-50%, -50%)';
                        popup.style.background = '#fff8dc';
                        popup.style.border = '2px solid #fbc02d';
                        popup.style.borderRadius = '16px';
                        popup.style.boxShadow = '0 4px 24px rgba(0,0,0,0.12)';
                        popup.style.padding = '32px 40px';
                        popup.style.zIndex = '9999';
                        popup.style.textAlign = 'center';
                        popup.innerHTML = `<div style='font-size:2.2rem; margin-bottom:12px;'><i class="fas fa-exclamation-triangle" style="color:#fbc02d;"></i></div><div style='font-size:1.2rem; color:#d32f2f; font-weight:700;'>Your profile does not have an email address.<br>Please add an email.</div><button style='margin-top:24px; background:#34a853; color:#fff; border:none; border-radius:8px; padding:8px 24px; font-size:1rem; cursor:pointer;' onclick='this.parentNode.remove()'>OK</button>`;
                        document.body.appendChild(popup);
                    }, 400);
                }
            </script>
                <script>
                // Close sidebar when a navigation button is clicked (mobile)
                document.addEventListener('DOMContentLoaded', function() {
                    const sidebar = document.querySelector('.sidebar');
                    if (!sidebar) return;
                    // close on nav link click
                    sidebar.querySelectorAll('button.sidebar-btn').forEach(btn => {
                        btn.addEventListener('click', function() {
                            // if mobile, close sidebar after short delay to allow any action
                            if (window.innerWidth <= 900) {
                                toggleSidebar(false);
                            }
                        });
                    });

                    // Ensure language toggle routes through our setter and closes sidebar first
                    const langToggle = document.getElementById('langToggle');
                    if (langToggle) {
                        langToggle.addEventListener('change', function() {
                            // close off-canvas menu, then change language
                            toggleSidebar(false);
                            setTimeout(() => {
                                setLanguage(langToggle.checked ? 'tl' : 'en');
                            }, 260);
                        });
                    }
                });
                </script>
            <?php endif; ?>
            <!-- Profile Section -->
            <!-- Language buttons removed, now in sidebar -->


            <div id="profileSection" class="content-section">
                <!-- Profile Header - Clean & Centered -->
                <div style="text-align:center; margin-bottom:24px;">
                    <div class="profile-avatar" style="margin:0 auto 12px; width:120px; height:120px; border:3px solid #15b300;">
                        <?php if (!empty($resident['profile_image']) && file_exists($resident['profile_image'])): ?>
                            <img src="<?= htmlspecialchars($resident['profile_image']) ?>" alt="Profile Image" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                        <?php else: ?>
                            <div style="width:100%;height:100%;border-radius:50%;background:#f0f0f0;display:flex;align-items:center;justify-content:center;font-size:48px;color:#999;"><i class="fas fa-user" style="font-size:48px;"></i></div>
                        <?php endif; ?>
                    </div>
                    <h1 style="margin:0 0 4px 0; font-size:1.5rem; color:#15b300; font-weight:700;"><?= htmlspecialchars($resident['first_name'] . ' ' . $resident['middle_name'] . ' ' . $resident['surname']) ?></h1>
                    <p style="margin:0; color:#666; font-size:0.85rem; font-weight:500;">ID: <?= htmlspecialchars($resident['unique_id'] ?? '') ?></p>
                </div>

                <!-- Profile Information Grid -->
                <div class="profile-details-grid">
                    <!-- Row 1 -->
                    <div class="profile-details-item">
                        <span class="label" id="birthdateLabel">Birthdate</span>
                        <span class="value"><?= htmlspecialchars($resident['birthdate'] ?? '') ?></span>
                    </div>
                    <div class="profile-details-item">
                        <span class="label" id="ageLabel">Age</span>
                        <span class="value"><?= htmlspecialchars($resident['age'] ?? '') ?></span>
                    </div>
                    <div class="profile-details-item">
                        <span class="label" id="sexLabel">Sex</span>
                        <span class="value"><?= htmlspecialchars($resident['sex'] ?? '') ?></span>
                    </div>
                    
                    <!-- Row 2 -->
                    <div class="profile-details-item" style="grid-column: span 3;">
                        <span class="label" id="emailLabel">Email</span>
                        <input type="email" name="email" value="<?= htmlspecialchars($resident['email'] ?? '') ?>"
                            <?php if (empty($resident['email'])): ?>
                                style="background:#fff8dc; border-bottom:2px solid #fbc02d; color:#d32f2f;"
                                placeholder="No email provided"
                            <?php endif; ?>
                        >
                    </div>
                    
                    <!-- Row 3 -->
                    <div class="profile-details-item" style="grid-column: span 3;">
                        <span class="label" id="addressLabel">Address</span>
                        <span class="value"><?= htmlspecialchars($resident['address'] ?? '') ?></span>
                    </div>
                    
                    <!-- Row 4 -->
                    <div class="profile-details-item">
                        <span class="label" id="civilStatusLabel">Civil Status</span>
                        <span class="value"><?= htmlspecialchars($resident['civil_status'] ?? '') ?></span>
                    </div>
                    <div class="profile-details-item" style="grid-column: span 2;">
                        <span class="label" id="placeOfBirthLabel">Place of Birth</span>
                        <span class="value"><?= htmlspecialchars($resident['place_of_birth'] ?? '') ?></span>
                    </div>
                    
                    <!-- Row 5 -->
                    <div class="profile-details-item">
                        <span class="label" id="citizenshipLabel">Citizenship</span>
                        <span class="value"><?= htmlspecialchars($resident['citizenship'] ?? '') ?></span>
                    </div>
                    <div class="profile-details-item" style="grid-column: span 2;">
                        <span class="label" id="educationLabel">Education</span>
                        <span class="value"><?= htmlspecialchars($resident['education'] ?? '') ?></span>
                    </div>
                    
                    <!-- Row 6 -->
                    <div class="profile-details-item">
                        <span class="label" id="skillsLabel">Skills</span>
                        <span class="value"><?= htmlspecialchars($resident['occupation_skills'] ?? '') ?></span>
                    </div>
                    <div class="profile-details-item" style="grid-column: span 2;">
                        <span class="label" id="skillDescLabel">Work Description</span>
                        <span class="value"><?= htmlspecialchars($resident['skill_description'] ?? '') ?></span>
                    </div>
                </div>
            </div>
            <!-- Edit Section -->
            <div id="editSection" class="content-section">
                <form class="edit-form" method="post" enctype="multipart/form-data" autocomplete="off" name="edit_profile" id="editProfileForm">
                    <div class="profile-avatar" style="margin-bottom:18px; position:relative;">
                        <?php if (!empty($resident['profile_image']) && file_exists($resident['profile_image'])): ?>
                            <img src="<?= htmlspecialchars($resident['profile_image']) ?>" alt="Profile Image" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                        <?php else: ?>
                            <svg fill="none" stroke="#34a853" stroke-width="4" viewBox="0 0 64 64" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);">
                                <rect x="16" y="24" width="32" height="24" rx="6" fill="#e5e5e5" stroke="#34a853" stroke-width="2"/>
                                <circle cx="32" cy="36" r="7" fill="none" stroke="#34a853" stroke-width="2"/>
                                <rect x="26" y="18" width="12" height="8" rx="2" fill="#e5e5e5" stroke="#34a853" stroke-width="2"/>
                            </svg>
                        <?php endif; ?>
                        <button type="button" class="edit-image-btn" onclick="triggerImageUpload()" title="Change Profile Image">
                            <!-- Pen icon SVG -->
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="#fff" stroke="#34a853" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 20h9"/>
                                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>
                            </svg>
                        </button>
                    </div>
                    <input type="file" name="profile_image" id="profile_image" accept="image/png, image/jpeg" style="display:none;">
                    <div class="profile-details-grid">
                        <div class="profile-details-item">
                            <span class="label" id="editSurnameLabel"></span>
                            <input type="text" name="surname" value="<?= htmlspecialchars($resident['surname'] ?? '') ?>">
                        </div>
                        <div class="profile-details-item">
                            <span class="label" id="editFirstNameLabel"></span>
                            <input type="text" name="first_name" value="<?= htmlspecialchars($resident['first_name'] ?? '') ?>">
                        </div>
                        <div class="profile-details-item">
                            <span class="label" id="editMiddleNameLabel"></span>
                            <input type="text" name="middle_name" value="<?= htmlspecialchars($resident['middle_name'] ?? '') ?>">
                        </div>
                        <div class="profile-details-item">
                            <span class="label" id="editBirthdateLabel"></span>
                            <input type="date" name="birthdate" value="<?= htmlspecialchars($resident['birthdate'] ?? '') ?>" disabled>
                        </div>
                        <div class="profile-details-item">
                            <span class="label" id="editAgeLabel"></span>
                            <input type="number" name="age" value="<?= htmlspecialchars($resident['age'] ?? '') ?>" disabled>
                        </div>
                        <div class="profile-details-item">
                            <span class="label" id="editEmailLabel"></span>
                            <input type="email" name="email" value="<?= htmlspecialchars($resident['email'] ?? '') ?>">
                        </div>
                        <div class="profile-details-item" style="grid-column: span 2;">
                            <span class="label" id="editAddressLabel"></span>
                            <input type="text" name="address" value="<?= htmlspecialchars($resident['address'] ?? '') ?>" disabled>
                        </div>
                        <div class="profile-details-item">
                            <span class="label" id="editSexLabel"></span>
                            <select name="sex" disabled>
                                <option value="Male" <?= ($resident['sex'] ?? '') == 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= ($resident['sex'] ?? '') == 'Female' ? 'selected' : '' ?>>Female</option>
                                <option value="Other" <?= ($resident['sex'] ?? '') == 'Other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                        <div class="profile-details-item">
                            <span class="label" id="editPlaceOfBirthLabel"></span>
                            <input type="text" name="place_of_birth" value="<?= htmlspecialchars($resident['place_of_birth'] ?? '') ?>">
                        </div>
                        <div class="profile-details-item">
                            <span class="label" id="editCivilStatusLabel"></span>
                            <input type="text" name="civil_status" value="<?= htmlspecialchars($resident['civil_status'] ?? '') ?>">
                        </div>
                        <div class="profile-details-item">
                            <span class="label" id="editCitizenshipLabel"></span>
                            <input type="text" name="citizenship" value="<?= htmlspecialchars($resident['citizenship'] ?? '') ?>" disabled>
                        </div>
                        <div class="profile-details-item">
                            <span class="label" id="editEducationLabel"></span>
                            <input type="text" name="education" value="<?= htmlspecialchars($resident['education'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="desktop-actions" style="display: flex; gap: 32px; margin-top: 24px;">
                        <button type="submit" name="edit_profile" class="edit-btn" style="flex:1;">Save</button>
                        <button type="button" class="edit-btn" style="flex:1; background:#e53935;" onclick="showSection('profile')">Cancel</button>
                    </div>
                </form>

                <!-- Mobile fixed action bar -->
                
                </form>
            </div>


            <!-- Request Status Section -->
<div id="statusSection" class="content-section">
    <h2 style="margin-bottom:18px; color:#15b300;">📄 My Certificate Requests</h2>
    <table style="width:100%; border-collapse:collapse; margin-top:12px;">
        <thead>
            <tr style="background:linear-gradient(135deg, #15b300 0%, #0e7c00 100%); color:white; text-align:left;">
                <th style="padding:12px 8px;">Certificate Type</th>
                <th style="padding:12px 8px;">Date Requested</th>
                <th style="padding:12px 8px;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $statusStmt = $conn->prepare("SELECT certificate_type, created_at, status 
                                          FROM certificate_requests 
                                          WHERE resident_unique_id=? 
                                          ORDER BY created_at DESC");
            $statusStmt->bind_param("s", $userid);
            $statusStmt->execute();
            $statusResult = $statusStmt->get_result();

            $printedFound = false; // Track if any status is "Printed"

            if ($statusResult->num_rows > 0) {
                while ($row = $statusResult->fetch_assoc()) {
                    echo "<tr style='border-bottom:1px solid #e5e7eb;'>";
                    echo "<td style='padding:8px;'>" . htmlspecialchars($row['certificate_type']) . "</td>";
                    echo "<td style='padding:8px;'>" . htmlspecialchars($row['created_at']) . "</td>";

                    // Color-coded status
                    $statusColor = "#999";
                    if ($row['status'] == "Pending") $statusColor = "#f59e0b"; // yellow
                    if ($row['status'] == "Approved") $statusColor = "#34a853"; // green
                    if ($row['status'] == "Rejected") $statusColor = "#e53935"; // red
                    if ($row['status'] == "Printed") {
                        $statusColor = "#1976d2"; // blue
                        $printedFound = true;
                    }

                    echo "<td style='padding:8px; font-weight:600; color:$statusColor;'>" 
                         . htmlspecialchars($row['status']) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='3' style='padding:12px; text-align:center; color:#666;'>No requests found.</td></tr>";
            }
            ?>
        </tbody>
    </table>
  
</div>

            <!-- Jobfinder Profile Section -->
            <div id="jobfinderSection" class="content-section">
                <h2 style="margin-bottom:20px; color:#15b300; font-size:1.3rem;"> <span id="jobfinderTitle">Jobfinder Profile</span></h2>
                <div style="max-width:500px; margin:0 auto;">
                    
                    <?php if (isset($resident['blocked_from_jobfinder']) && $resident['blocked_from_jobfinder'] == 1): ?>
                    <!-- Blocked Warning -->
                    <div style="background:#fee2e2; border-left:4px solid #dc2626; border-radius:10px; padding:16px; margin-bottom:20px;">
                        <div style="display:flex; align-items:start; gap:12px;">
                            <div style="font-size:1.5rem;">🚫</div>
                            <div style="flex:1;">
                                <div style="font-weight:700; color:#dc2626; margin-bottom:4px; font-size:1rem;" id="jobfinderBlockedTitle">Account Blocked</div>
                                <div style="font-size:0.9rem; color:#991b1b; line-height:1.5;" id="jobfinderBlockedMsg">Your account has been blocked from Jobfinder by the administrator. You will not appear in the Jobfinder listings.</div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php 
                    // Check if jobfinder_active column exists, if not default to 1 (active)
                    $jobfinder_active = isset($resident['jobfinder_active']) ? $resident['jobfinder_active'] : 1;
                    $is_blocked = isset($resident['blocked_from_jobfinder']) && $resident['blocked_from_jobfinder'] == 1;
                    ?>
                    
                    <?php if (!$is_blocked): ?>
                    <!-- Active/Deactivate Toggle - Compact -->
                    <div style="background:linear-gradient(135deg, <?= $jobfinder_active == 1 ? '#d1fae5 0%, #a7f3d0 100%' : '#fef3c7 0%, #fde68a 100%' ?>); border-radius:10px; padding:12px 16px; margin-bottom:16px; box-shadow:0 2px 6px rgba(0,0,0,0.06); border:1px solid <?= $jobfinder_active == 1 ? '#10b981' : '#f59e0b' ?>;">
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
                            <div style="flex:1; min-width:180px;">
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <div style="width:28px; height:28px; border-radius:50%; background:<?= $jobfinder_active == 1 ? '#10b981' : '#f59e0b' ?>; display:flex; align-items:center; justify-content:center; font-size:14px; color:#fff; box-shadow:0 1px 4px rgba(0,0,0,0.1);">
                                        <?= $jobfinder_active == 1 ? '✓' : '⚠' ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:700; color:<?= $jobfinder_active == 1 ? '#047857' : '#b45309' ?>; font-size:0.9rem;">
                                            <?= $jobfinder_active == 1 ? 'Profile Active' : 'Profile Deactivated' ?>
                                        </div>
                                        <div style="font-size:0.75rem; color:<?= $jobfinder_active == 1 ? '#065f46' : '#92400e' ?>; line-height:1.3;">
                                            <?= $jobfinder_active == 1 ? 'Visible to employers' : 'Hidden from listings' ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <form method="post" style="margin:0;">
                                <button type="submit" name="toggle_jobfinder_active" 
                                        style="background:<?= $jobfinder_active == 1 ? 'linear-gradient(135deg, #dc2626 0%, #b91c1c 100%)' : 'linear-gradient(135deg, #15b300 0%, #0e7c00 100%)' ?>; color:#fff; border:none; border-radius:8px; padding:8px 16px; font-size:0.85rem; font-weight:600; cursor:pointer; transition:all 0.2s ease; box-shadow:0 2px 6px rgba(0,0,0,0.15); white-space:nowrap;"
                                        onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 10px rgba(0,0,0,0.2)';"
                                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 6px rgba(0,0,0,0.15)';">
                                    <?= $jobfinder_active == 1 ? 'Deactivate' : 'Activate' ?>
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Avatar & Name -->
                    <div style="text-align:center; margin-bottom:24px;">
                        <div style="display:inline-block; position:relative;">
                            <div class="verified-badge-container">
                                <div class="profile-avatar" style="width:100px; height:100px;">
                                    <?php if (!empty($resident['profile_image']) && file_exists($resident['profile_image'])): ?>
                                        <img src="<?= htmlspecialchars($resident['profile_image']) ?>" alt="Profile Image" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                                    <?php else: ?>
                                        <div style="width:100px;height:100px;border-radius:50%;background:#e5e5e5;"></div>
                                    <?php endif; ?>
                                </div>
                                <?php if (isset($resident['jobfinder_verified']) && $resident['jobfinder_verified'] == 1): ?>
                                    <div class="verified-badge" title="Verified by Admin">
                                        ✓
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <h3 style="margin:12px 0 0 0; font-size:1.3rem; color:#333;">
                            <?= htmlspecialchars(($resident['first_name'] ?? '') . ' ' . ($resident['surname'] ?? '')) ?>
                        </h3>
                    </div>
                    
                    <!-- View Mode - Compact -->
                    <div id="jobfinderViewMode">
                        <!-- Age Card -->
                        <div style="background:#fff; border-radius:8px; padding:10px 12px; margin-bottom:10px; box-shadow:0 1px 4px rgba(0,0,0,0.05); border-left:3px solid #15b300;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <div style="width:26px; height:26px; border-radius:50%; background:linear-gradient(135deg, #15b300 0%, #0e7c00 100%); display:flex; align-items:center; justify-content:center; font-size:14px; color:#fff; flex-shrink:0;">
                                    🎂
                                </div>
                                <div style="flex:1;">
                                    <div style="font-size:0.7rem; font-weight:600; color:#15b300; text-transform:uppercase; letter-spacing:0.3px; margin-bottom:2px;" id="jobfinderAgeLabel">Age</div>
                                    <div style="font-size:0.95rem; color:#333; font-weight:500;"><?= htmlspecialchars($resident['age'] ?? 'N/A') ?> years old</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Skills Card -->
                        <div style="background:#fff; border-radius:8px; padding:10px 12px; margin-bottom:10px; box-shadow:0 1px 4px rgba(0,0,0,0.05); border-left:3px solid #2196f3;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <div style="width:26px; height:26px; border-radius:50%; background:linear-gradient(135deg, #2196f3 0%, #1976d2 100%); display:flex; align-items:center; justify-content:center; font-size:14px; color:#fff; flex-shrink:0;">
                                    💼
                                </div>
                                <div style="flex:1;">
                                    <div style="font-size:0.7rem; font-weight:600; color:#2196f3; text-transform:uppercase; letter-spacing:0.3px; margin-bottom:2px;" id="jobfinderSkillsLabel">Skills</div>
                                    <div style="font-size:0.95rem; color:#333; font-weight:500; line-height:1.4;"><?= htmlspecialchars($resident['occupation_skills'] ?? 'No skills listed') ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Skill Description Card -->
                        <div style="background:#fff; border-radius:8px; padding:10px 12px; margin-bottom:14px; box-shadow:0 1px 4px rgba(0,0,0,0.05); border-left:3px solid #ff9800;">
                            <div style="display:flex; align-items:flex-start; gap:8px;">
                                <div style="width:26px; height:26px; border-radius:50%; background:linear-gradient(135deg, #ff9800 0%, #f57c00 100%); display:flex; align-items:center; justify-content:center; font-size:14px; color:#fff; flex-shrink:0;">
                                    📝
                                </div>
                                <div style="flex:1;">
                                    <div style="font-size:0.7rem; font-weight:600; color:#ff9800; text-transform:uppercase; letter-spacing:0.3px; margin-bottom:2px;" id="jobfinderSkillDescLabel">Description</div>
                                    <div style="font-size:0.85rem; color:#555; line-height:1.5;"><?= htmlspecialchars($resident['skill_description'] ?? 'No description provided') ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <div style="text-align:center; margin-top:16px;">
                            <button onclick="toggleJobfinderEdit(true)" style="background:linear-gradient(135deg, #15b300 0%, #0e7c00 100%); color:#fff; border:none; border-radius:8px; padding:10px 24px; font-size:0.9rem; font-weight:600; cursor:pointer; box-shadow:0 2px 8px rgba(21,179,0,0.25); transition:all 0.2s ease; display:inline-flex; align-items:center; gap:6px;"
                                    onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 12px rgba(21,179,0,0.35)';"
                                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(21,179,0,0.25)';">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                                <span id="jobfinderEditBtn">Edit Skills</span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Edit Mode -->
                    <div id="jobfinderEditMode" style="display:none;">
                        <form method="post" autocomplete="off" name="edit_jobfinder_skills">
                            <div style="background:#f9fafb; border-radius:10px; padding:20px;">
                                <div style="margin-bottom:16px;">
                                    <div style="font-size:0.85rem; color:#666; margin-bottom:4px;" id="jobfinderAgeLabel2">Age</div>
                                    <div style="font-size:1rem; color:#333;"><?= htmlspecialchars($resident['age'] ?? 'N/A') ?> years old</div>
                                </div>
                                
                                <div style="margin-bottom:16px;">
                                    <label style="font-size:0.85rem; color:#666; margin-bottom:4px; display:block;" id="jobfinderSkillsLabel2">Skills</label>
                                    <input type="text" name="occupation_skills" value="<?= htmlspecialchars($resident['occupation_skills'] ?? '') ?>" 
                                           style="width:100%; padding:10px; font-size:1rem; border-radius:8px; border:1px solid #ddd; box-sizing:border-box;">
                                </div>
                                
                                <div>
                                    <label style="font-size:0.85rem; color:#666; margin-bottom:4px; display:block;" id="jobfinderSkillDescLabel2">Skill Description</label>
                                    <textarea name="skill_description" rows="4" 
                                              style="width:100%; padding:10px; font-size:0.95rem; border-radius:8px; border:1px solid #ddd; box-sizing:border-box; resize:vertical;"><?= htmlspecialchars($resident['skill_description'] ?? '') ?></textarea>
                                </div>
                            </div>
                            
                            <div style="display:flex; gap:12px; margin-top:20px;">
                                <button type="submit" name="edit_jobfinder_skills" class="edit-btn" style="flex:1; padding:10px 24px;">
                                    <span id="jobfinderSaveBtn">Save</span>
                                </button>
                                <button type="button" onclick="toggleJobfinderEdit(false)" class="edit-btn" style="flex:1; padding:10px 24px; background:#e53935;">
                                    <span id="jobfinderCancelBtn">Cancel</span>
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Customer Comments Section (Only show if user has skills) -->
                    <?php if (!empty($resident['occupation_skills']) && trim($resident['occupation_skills']) !== ''): ?>
                    <?php
                    // Count total reviews
                    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM chat_ratings WHERE receiver_id = ? AND comment IS NOT NULL AND comment != ''");
                    $countStmt->bind_param("s", $userid);
                    $countStmt->execute();
                    $totalReviews = $countStmt->get_result()->fetch_assoc()['total'];
                    ?>
                    
                    <div style="margin-top:32px;">
                        <button onclick="openReviewsModal()" style="background:linear-gradient(135deg, #15b300 0%, #0e7c00 100%); color:white; border:none; padding:12px 24px; border-radius:25px; font-size:1rem; font-weight:600; cursor:pointer; box-shadow:0 4px 12px rgba(21,179,0,0.3); transition:all 0.3s ease; display:flex; align-items:center; gap:10px; width:100%; justify-content:center;">
                            <i class="fas fa-comments" style="font-size:1.2rem;"></i>
                            <span id="customerCommentsTitle">Customer Reviews</span>
                            <?php if ($totalReviews > 0): ?>
                            <span style="background:rgba(255,255,255,0.25); padding:4px 10px; border-radius:12px; font-size:0.9rem;">(<?= $totalReviews ?>)</span>
                            <?php endif; ?>
                        </button>
                    </div>
                    <?php endif; ?>

                    <!-- Reviews Modal -->
                    <div id="reviewsModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center;">
                        <div style="background:#fff; border-radius:16px; max-width:700px; width:90%; max-height:85vh; overflow:hidden; box-shadow:0 10px 40px rgba(0,0,0,0.3); animation:modalSlideIn 0.3s ease;">
                            <div style="background:linear-gradient(135deg, #15b300 0%, #0e7c00 100%); padding:20px 24px; display:flex; justify-content:space-between; align-items:center;">
                                <h3 style="color:white; margin:0; font-size:1.3rem; display:flex; align-items:center; gap:10px;">
                                    <i class="fas fa-comments"></i>
                                    <span>All Customer Reviews</span>
                                </h3>
                                <button onclick="closeReviewsModal()" style="background:rgba(255,255,255,0.2); border:none; color:white; width:36px; height:36px; border-radius:50%; cursor:pointer; font-size:20px; display:flex; align-items:center; justify-content:center; transition:all 0.2s;">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div id="modalReviewsContent" style="padding:24px; max-height:calc(85vh - 80px); overflow-y:auto;">
                                <?php
                                // Fetch ALL comments for modal
                                $allCommentsStmt = $conn->prepare("SELECT cr.comment, cr.rating, cr.created_at, r.first_name, r.surname 
                                                                FROM chat_ratings cr 
                                                                LEFT JOIN residents r ON cr.userid = r.unique_id 
                                                                WHERE cr.receiver_id = ? 
                                                                AND cr.comment IS NOT NULL 
                                                                AND cr.comment != '' 
                                                                ORDER BY cr.created_at DESC");
                                $allCommentsStmt->bind_param("s", $userid);
                                $allCommentsStmt->execute();
                                $allCommentsResult = $allCommentsStmt->get_result();
                                
                                if ($allCommentsResult->num_rows > 0):
                                    while ($comment = $allCommentsResult->fetch_assoc()):
                                ?>
                                <div style="background:#f9fafb; border-left:3px solid #15b300; border-radius:10px; padding:16px; margin-bottom:14px; transition:transform 0.2s;">
                                    <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:10px;">
                                        <div style="font-weight:600; color:#333; font-size:1rem;">
                                            <?= htmlspecialchars(($comment['first_name'] ?? 'Anonymous') . ' ' . ($comment['surname'] ?? '')) ?>
                                        </div>
                                        <div style="display:flex; gap:3px;">
                                            <?php 
                                            $rating = intval($comment['rating'] ?? 0);
                                            for ($i = 1; $i <= 5; $i++): 
                                            ?>
                                                <span style="color:<?= $i <= $rating ? '#fbbf24' : '#d1d5db' ?>; font-size:16px;">★</span>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <div style="color:#555; font-size:0.95rem; line-height:1.6; margin-bottom:8px;">
                                        <?= htmlspecialchars($comment['comment']) ?>
                                    </div>
                                    <div style="color:#999; font-size:0.8rem;">
                                        <i class="far fa-calendar"></i> <?= date('M d, Y', strtotime($comment['created_at'])) ?>
                                    </div>
                                </div>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                <div style="text-align:center; padding:40px; color:#999;">
                                    <div style="font-size:3rem; margin-bottom:12px;">💬</div>
                                    <div>No customer reviews yet</div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <style>
                    /* Customer Reviews Button Hover */
                    button[onclick="openReviewsModal()"]:hover {
                        background: linear-gradient(135deg, #0e7c00 0%, #0a5a00 100%);
                        transform: translateY(-2px);
                        box-shadow: 0 6px 16px rgba(21,179,0,0.4);
                    }
                    
                    button[onclick="openReviewsModal()"]:active {
                        transform: translateY(0);
                    }
                    
                    @keyframes modalSlideIn {
                        from {
                            opacity: 0;
                            transform: translateY(-30px) scale(0.95);
                        }
                        to {
                            opacity: 1;
                            transform: translateY(0) scale(1);
                        }
                    }
                    
                    #reviewsModal button:hover {
                        background: rgba(255,255,255,0.3);
                        transform: scale(1.05);
                    }
                    
                    #modalReviewsContent::-webkit-scrollbar {
                        width: 8px;
                    }
                    
                    #modalReviewsContent::-webkit-scrollbar-track {
                        background: #f1f1f1;
                        border-radius: 10px;
                    }
                    
                    #modalReviewsContent::-webkit-scrollbar-thumb {
                        background: #15b300;
                        border-radius: 10px;
                    }
                    
                    #modalReviewsContent::-webkit-scrollbar-thumb:hover {
                        background: #0e7c00;
                    }
                    
                    @media (max-width: 768px) {
                        #reviewsModal > div {
                            width: 95%;
                            max-height: 90vh;
                        }
                        
                        #modalReviewsContent {
                            padding: 16px;
                            max-height: calc(90vh - 70px);
                        }
                    }
                    </style>

                    <script>
                    function openReviewsModal() {
                        const modal = document.getElementById('reviewsModal');
                        if (modal) {
                            modal.style.display = 'flex';
                            document.body.style.overflow = 'hidden';
                        }
                    }
                    
                    function closeReviewsModal() {
                        const modal = document.getElementById('reviewsModal');
                        if (modal) {
                            modal.style.display = 'none';
                            document.body.style.overflow = '';
                        }
                    }
                    
                    // Close modal when clicking outside
                    document.addEventListener('click', function(e) {
                        const modal = document.getElementById('reviewsModal');
                        if (e.target === modal) {
                            closeReviewsModal();
                        }
                    });
                    
                    // Close modal with Escape key
                    document.addEventListener('keydown', function(e) {
                        if (e.key === 'Escape') {
                            closeReviewsModal();
                        }
                    });
                    </script>
                </div>
            </div>

            <!-- Password Change Section -->
            <div id="passwordSection" class="content-section" style="max-width:360px; margin:auto; padding:16px;">
    <form method="post" autocomplete="off" name="change_password" id="changePasswordForm">
        <h2 style="margin-bottom:12px; color:#34a853; font-size:18px;" id="changePasswordTitle"></h2>
        <div style="margin-bottom:8px;">
            <label for="old_password" style="font-weight:600; font-size:13px;" id="oldPasswordLabel"></label>
            <input type="password" name="old_password" id="old_password" required 
                   style="width:100%; padding:6px; font-size:13px; border-radius:6px; border:1px solid #ddd;">
        </div>
        <div style="margin-bottom:8px;">
            <label for="new_password" style="font-weight:600; font-size:13px;" id="newPasswordLabel"></label>
            <input type="password" name="new_password" id="new_password" required 
                   style="width:100%; padding:6px; font-size:13px; border-radius:6px; border:1px solid #ddd;">
        </div>
        <div style="margin-bottom:12px;">
            <label for="confirm_password" style="font-weight:600; font-size:13px;" id="confirmPasswordLabel"></label>
            <input type="password" name="confirm_password" id="confirm_password" required 
                   style="width:100%; padding:6px; font-size:13px; border-radius:6px; border:1px solid #ddd;">
        </div>
        <div style="display:flex; gap:10px; justify-content:center;">
            <button type="submit" name="change_password" class="edit-btn" 
                    style="flex:1; font-size:13px; padding:6px 0; border-radius:6px;" id="saveBtn"></button>
            <button type="button" class="edit-btn" 
                    style="flex:1; font-size:13px; padding:6px 0; border-radius:6px; background:#e53935;" 
                    onclick="hidePasswordModal()" id="cancelBtn"></button>
        </div>
    </form>
</div>

        </div>
    </div>
</div>
</body>
<script>
// Notification Bell Logic (copied from index.php)
document.addEventListener('DOMContentLoaded', function() {
    const notifBell = document.getElementById('notif-bell');
    const notifDropdown = document.getElementById('notif-dropdown');
    const notifBadge = document.getElementById('notif-badge');
    const notifList = document.getElementById('notif-list');
    const notifClose = document.getElementById('notif-close');

    let notifications = [];

    function fetchNotifications() {
        // Fetch notifications (certificates + admin chat)
        fetch('get_notifications.php')
            .then(res => res.json())
            .then(notifData => {
                notifications = [];
                if (Array.isArray(notifData)) {
                    notifData.forEach(n => {
                        if (n.type === 'admin_chat') {
                            notifications.push({
                                message: n.message,
                                date: n.date,
                                highlight: true
                            });
                        } else {
                            notifications.push({
                                message: n.message,
                                date: n.date,
                                highlight: false
                            });
                        }
                    });
                }
                // Fetch unread jobfinder messages (legacy)
                fetch('jobfinder.php?unread_count=1')
                    .then(res => res.json())
                    .then(msgData => {
                        if (msgData.count && msgData.count > 0) {
                            notifications.push({
                                message: `You have ${msgData.count} unread Job Finder message(s).`,
                                date: '',
                                highlight: true
                            });
                        }
                        updateNotifUI();
                    })
                    .catch(() => {
                        updateNotifUI();
                    });
            })
            .catch(() => {
                notifications = [];
                updateNotifUI();
            });
    }

    function updateNotifUI() {
        // Show total count (messages + certificates + admin chat)
        if (notifications.length > 0) {
            notifBadge.textContent = notifications.length;
            notifBadge.style.display = 'inline-block';
        } else {
            notifBadge.style.display = 'none';
        }
        notifList.innerHTML = '';
        if (notifications.length === 0) {
            notifList.innerHTML = '<div style="padding:16px; color:#888;">No new notifications.</div>';
        } else {
            notifications.forEach(n => {
                const item = document.createElement('div');
                item.style.padding = '12px 16px';
                item.style.borderBottom = '1px solid #eee';
                item.style.cursor = 'pointer';
                item.innerHTML = `<span style='${n.highlight ? "color:#e53935;font-weight:bold;" : "color:#34a853;"}'>${n.message}</span>` + (n.date ? `<br><span style='font-size:12px; color:#888;'>${n.date}</span>` : '');
                notifList.appendChild(item);
            });
        }
    }

    if (notifBell) {
        notifBell.addEventListener('click', function(e) {
            notifDropdown.style.display = notifDropdown.style.display === 'block' ? 'none' : 'block';
            if (notifDropdown.style.display === 'block') {
                fetchNotifications();
            }
            e.stopPropagation();
        });
    }
    if (notifClose) {
        notifClose.addEventListener('click', function() {
            notifDropdown.style.display = 'none';
        });
    }
    document.addEventListener('click', function(e) {
        if (notifDropdown && !notifDropdown.contains(e.target) && e.target !== notifBell) {
            notifDropdown.style.display = 'none';
        }
    });

    // Poll for notifications every 30 seconds
    setInterval(fetchNotifications, 30000);
    fetchNotifications();
});
</script>
<script>
// Mobile sidebar toggle logic
function toggleSidebar(open) {    const sidebar = document.querySelector('.sidebar');    const overlay = document.getElementById('sidebarOverlay');    const burger = document.getElementById('burgerBtn');    const mobileControls = document.getElementById('mobileControls');
    if (!sidebar) return;
    if (open) {
        sidebar.classList.add('open');
        if (mobileControls) mobileControls.classList.add('hidden');
        overlay.style.display = 'block';
        burger.setAttribute('aria-expanded', 'true');
        // trap focus briefly
        setTimeout(() => {
            const firstButton = sidebar.querySelector('button, a, input');
            if (firstButton) firstButton.focus();
        }, 220);
    } else {
        sidebar.classList.remove('open');
        if (mobileControls) mobileControls.classList.remove('hidden');
        overlay.style.display = 'none';
        burger.setAttribute('aria-expanded', 'false');
    }
}

// Close on ESC key
document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') toggleSidebar(false);
});

// On resize, ensure sidebar is visible normally on larger screens
window.addEventListener('resize', function(){
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (window.innerWidth > 900) {
        if (sidebar) sidebar.classList.remove('open');
        if (mobileControls) mobileControls.classList.remove('hidden');
        if (overlay) overlay.style.display = 'none';
        const burger = document.getElementById('burgerBtn');
        if (burger) burger.setAttribute('aria-expanded', 'false');
    }
});
</script>
<!-- notification positioning script removed so the bell isn't floating/fixed -->
<script>
// Language dictionary
const langDict = {
    en: {
        home: "Home",
        profile: "Profile",
        edit: "Edit",
        status: "Request Status",
        password: "Change Password",
        jobfinder: "Jobfinder Profile",
        logout: "Logout",
        jobfinderTitle: "Jobfinder Profile",
        jobfinderAge: "Age",
        jobfinderSkills: "Skills",
        jobfinderSkillDesc: "Skill Description",
        jobfinderEdit: "Edit Skills",
        jobfinderSave: "Save",
        jobfinderCancel: "Cancel",
        jobfinderBlockedTitle: "Account Blocked",
        jobfinderBlockedMsg: "Your account has been blocked from Jobfinder by the administrator. You will not appear in the Jobfinder listings.",
        surname: "Surname:",
        firstName: "First Name:",
        middleName: "Middle Name:",
        birthdate: "Birthdate:",
        age: "Age:",
        email: "Email:",
        address: "Address:",
        sex: "Sex:",
        placeOfBirth: "Place of Birth:",
        civilStatus: "Civil Status:",
        citizenship: "Citizenship:",
        skills: "Skills:",
        skillDesc: "Skill Description:",
        education: "Highest Educational Attainment:",
        changePasswordTitle: "Change Password",
        oldPassword: "Old Password:",
        newPassword: "New Password:",
        confirmPassword: "Confirm New Password:",
        save: "Save",
        cancel: "Cancel"
    },
    tl: {
        home: "Home",
        profile: "Profile",
        edit: "I-edit",
        status: "Status ng Request",
        password: "Palitan ang Password",
        jobfinder: "Jobfinder Profile",
        logout: "Logout",
        jobfinderTitle: "Jobfinder Profile",
        jobfinderAge: "Edad",
        jobfinderSkills: "Kasanayan",
        jobfinderSkillDesc: "Deskripsyon ng Kasanayan",
        jobfinderEdit: "I-edit ang Kasanayan",
        jobfinderSave: "I-save",
        jobfinderCancel: "Kanselahin",
        jobfinderBlockedTitle: "Naka-block ang Account",
        jobfinderBlockedMsg: "Ang iyong account ay na-block mula sa Jobfinder ng administrator. Hindi ka lalabas sa listahan ng Jobfinder.",
        surname: "Apelyido:",
        firstName: "Pangalan:",
        middleName: "Gitnang Pangalan:",
        birthdate: "Araw ng Kapanganakan:",
        age: "Edad:",
        email: "Email:",
        address: "Address:",
        sex: "Kasarian:",
        placeOfBirth: "Lugar ng Kapanganakan:",
        civilStatus: "Katayuan sa Sibil:",
        citizenship: "Pagkamamamayan:",
        skills: "Kasanayan:",
        skillDesc: "Deskripsyon ng Kasanayan:",
        education: "Pinakamataas na Natapos:",
        changePasswordTitle: "Palitan ang Password",
        oldPassword: "Lumang Password:",
        newPassword: "Bagong Password:",
        confirmPassword: "Kumpirmahin ang Bagong Password:",
        save: "I-save",
        cancel: "Kanselahin"
    }
};

function setLanguage(lang) {
    localStorage.setItem('lang', lang);
    window.location.href = window.location.pathname + '?lang=' + lang;
}

function updateLabels(lang) {
    const dict = langDict[lang] || langDict['en'];

    // Helper: set text only if element exists. If scoped=true, only set when element is inside one
    // of the profile-related sections (profile/edit/status/password) to avoid leaking text to other pages.
    function setText(id, text, scoped = false) {
        const el = document.getElementById(id);
        if (!el) return;
        if (scoped) {
            // Allowed profile scopes
            if (!el.closest('#profileSection') && !el.closest('#editSection') && !el.closest('#statusSection') && !el.closest('#passwordSection')) return;
        }
        el.textContent = text;
    }

    // Navigation / sidebar labels (global)
    setText('homeLabel', dict.home, false);
    setText('profileLabel', dict.profile, false);
    setText('editLabel', dict.edit, false);
    setText('statusLabel', dict.status, false);
    setText('passwordLabel', dict.password, false);
    setText('jobfinderLabel', dict.jobfinder, false);
    setText('logoutLabel', dict.logout, false);
    const mobileLogout = document.getElementById('mobileLogoutLabel');
    if (mobileLogout) mobileLogout.textContent = dict.logout;
    const mobileHome = document.getElementById('mobileHomeLabel');
    if (mobileHome) mobileHome.textContent = dict.home;
    setText('langSwitchLabel', lang === 'tl' ? 'Tagalog' : 'English', false);

    // Profile form labels (scoped to profile sections)
    setText('surnameLabel', dict.surname, true);
    setText('firstNameLabel', dict.firstName, true);
    setText('middleNameLabel', dict.middleName, true);
    setText('birthdateLabel', dict.birthdate, true);
    setText('ageLabel', dict.age, true);
    setText('emailLabel', dict.email, true);
    setText('addressLabel', dict.address, true);
    setText('sexLabel', dict.sex, true);
    setText('placeOfBirthLabel', dict.placeOfBirth, true);
    setText('civilStatusLabel', dict.civilStatus, true);
    setText('citizenshipLabel', dict.citizenship, true);
    setText('skillsLabel', dict.skills, true);
    setText('skillDescLabel', dict.skillDesc, true);
    setText('educationLabel', dict.education, true);

    // Edit section labels (scoped)
    setText('editSurnameLabel', dict.surname, true);
    setText('editFirstNameLabel', dict.firstName, true);
    setText('editMiddleNameLabel', dict.middleName, true);
    setText('editBirthdateLabel', dict.birthdate, true);
    setText('editAgeLabel', dict.age, true);
    setText('editEmailLabel', dict.email, true);
    setText('editAddressLabel', dict.address, true);
    setText('editSexLabel', dict.sex, true);
    setText('editPlaceOfBirthLabel', dict.placeOfBirth, true);
    setText('editCivilStatusLabel', dict.civilStatus, true);
    setText('editCitizenshipLabel', dict.citizenship, true);
    setText('editSkillsLabel', dict.skills, true);
    setText('editSkillDescLabel', dict.skillDesc, true);
    setText('editEducationLabel', dict.education, true);

    // Password/change labels (scoped)
    setText('changePasswordTitle', dict.changePasswordTitle, true);
    setText('oldPasswordLabel', dict.oldPassword, true);
    setText('newPasswordLabel', dict.newPassword, true);
    setText('confirmPasswordLabel', dict.confirmPassword, true);
    setText('saveBtn', dict.save, true);
    setText('cancelBtn', dict.cancel, true);

    // Jobfinder labels (scoped)
    setText('jobfinderTitle', dict.jobfinderTitle, true);
    setText('jobfinderAgeLabel', dict.jobfinderAge, true);
    setText('jobfinderSkillsLabel', dict.jobfinderSkills, true);
    setText('jobfinderSkillDescLabel', dict.jobfinderSkillDesc, true);
    setText('jobfinderEditBtn', dict.jobfinderEdit, true);
    setText('jobfinderAgeLabel2', dict.jobfinderAge, true);
    setText('jobfinderSkillsLabel2', dict.jobfinderSkills, true);
    setText('jobfinderSkillDescLabel2', dict.jobfinderSkillDesc, true);
    setText('jobfinderSaveBtn', dict.jobfinderSave, true);
    setText('jobfinderCancelBtn', dict.jobfinderCancel, true);
    setText('jobfinderBlockedTitle', dict.jobfinderBlockedTitle, true);
    setText('jobfinderBlockedMsg', dict.jobfinderBlockedMsg, true);
}

document.addEventListener('DOMContentLoaded', function() {
    let lang = '<?= $lang ?>';
    if (localStorage.getItem('lang')) lang = localStorage.getItem('lang');
    updateLabels(lang);
    // Toggle switch event
    const langToggle = document.getElementById('langToggle');
    langToggle.checked = lang === 'tl';
    langToggle.addEventListener('change', function() {
        setLanguage(langToggle.checked ? 'tl' : 'en');
    });
});
</script>
<script>
// Mobile save: submit the main edit form when mobile Save button is clicked
document.addEventListener('DOMContentLoaded', function() {
    const mobileSave = document.getElementById('mobileSaveBtn');
    if (!mobileSave) return;
    mobileSave.addEventListener('click', function() {
        const editForm = document.querySelector('form.edit-form');
        if (!editForm) return;
        // ensure any unsaved file input doesn't get lost: just submit the form
        editForm.submit();
    });
});
</script>

<!-- Jobfinder Info Modal -->
<div id="jobfinderInfoModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; padding:20px;">
    <div style="background:#fff; border-radius:16px; max-width:550px; width:100%; max-height:90vh; overflow-y:auto; box-shadow:0 10px 40px rgba(0,0,0,0.2); animation:modalFadeIn 0.3s ease;">
        <div style="background:linear-gradient(135deg, #2196f3 0%, #1976d2 100%); padding:20px; border-radius:16px 16px 0 0; position:relative;">
            <h3 style="margin:0; color:#fff; font-size:1.3rem; display:flex; align-items:center; gap:10px;">
                <span style="font-size:1.5rem;">ℹ️</span>
                <span id="modalJobfinderTitle"><?= $lang === 'tl' ? 'Paano Gamitin ang Jobfinder' : 'How to Use Jobfinder' ?></span>
            </h3>
            <button onclick="closeJobfinderInfoModal()" style="position:absolute; top:16px; right:16px; background:rgba(255,255,255,0.2); border:none; color:#fff; width:32px; height:32px; border-radius:50%; cursor:pointer; font-size:1.2rem; display:flex; align-items:center; justify-content:center; transition:all 0.2s ease;">
                ✕
            </button>
        </div>
        <div style="padding:24px;">
            <div style="font-size:0.95rem; color:#333; line-height:1.6; margin-bottom:20px;" id="modalJobfinderDesc">
                <?= $lang === 'tl' 
                    ? 'Ang Jobfinder ay tumutulong sa mga residente na makahanap ng trabaho sa loob ng barangay. Ipakita ang iyong mga kasanayan at makatanggap ng mga oportunidad mula sa mga employer.' 
                    : 'Jobfinder helps residents find job opportunities within the barangay. Showcase your skills and receive opportunities from employers.' 
                ?>
            </div>
            
            <div style="background:linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); border-radius:12px; padding:18px; margin-bottom:16px;">
                <div style="font-weight:700; color:#1565c0; margin-bottom:12px; font-size:1rem; display:flex; align-items:center; gap:8px;">
                    <span style="font-size:1.3rem;">✓</span>
                    <span id="modalVerifyTitle"><?= $lang === 'tl' ? 'Paano Makakuha ng Verification:' : 'How to Get Verified:' ?></span>
                </div>
                <ol style="margin:0; padding-left:20px; font-size:0.9rem; color:#0d47a1; line-height:1.8;">
                    <li id="modalStep1">
                        <strong><?= $lang === 'tl' ? 'Pumunta sa Barangay Hall' : 'Visit the Barangay Hall' ?></strong>
                        <div style="font-size:0.85rem; color:#1565c0; margin-top:4px;">
                            <?= $lang === 'tl' 
                                ? 'Magpunta sa opisina ng barangay para sa verification.' 
                                : 'Go to the barangay office for verification.' 
                            ?>
                        </div>
                    </li>
                    <li id="modalStep2" style="margin-top:10px;">
                        <strong><?= $lang === 'tl' ? 'Magpakita ng Katunayan' : 'Present Proof of Skills' ?></strong>
                        <div style="font-size:0.85rem; color:#1565c0; margin-top:4px;">
                            <?= $lang === 'tl' 
                                ? 'Dalhin ang mga sumusunod: Certificate, ID, Portfolio, o iba pang dokumento na nagpapatunay ng iyong skills.' 
                                : 'Bring any of the following: Certificate, ID, Portfolio, or other documents proving your skills.' 
                            ?>
                        </div>
                    </li>
                    <li id="modalStep3" style="margin-top:10px;">
                        <strong><?= $lang === 'tl' ? 'Admin Verification' : 'Admin Verification' ?></strong>
                        <div style="font-size:0.85rem; color:#1565c0; margin-top:4px;">
                            <?= $lang === 'tl' 
                                ? 'Ang admin ay susuriin ang iyong mga dokumento at mag-verify ng iyong profile.' 
                                : 'The admin will review your documents and verify your profile.' 
                            ?>
                        </div>
                    </li>
                    <li id="modalStep4" style="margin-top:10px;">
                        <strong><?= $lang === 'tl' ? 'Makakuha ng Verified Badge' : 'Get Verified Badge' ?></strong>
                        <div style="font-size:0.85rem; color:#1565c0; margin-top:4px;">
                            <?= $lang === 'tl' 
                                ? 'Kapag na-verify na, makikita mo ang green check mark (✓) sa iyong Jobfinder profile.' 
                                : 'Once verified, you will see a green check mark (✓) on your Jobfinder profile.' 
                            ?>
                        </div>
                    </li>
                </ol>
            </div>
            
            <div style="background:#fff3cd; border-left:4px solid #ffc107; border-radius:8px; padding:12px; margin-bottom:16px;">
                <div style="font-size:0.85rem; color:#856404; line-height:1.5;">
                    <strong>💡 <?= $lang === 'tl' ? 'Tip:' : 'Tip:' ?></strong>
                    <?= $lang === 'tl' 
                        ? 'Ang verified badge ay nagpapakita sa mga employer na ikaw ay tunay at may kakayahan. Mas mataas ang tsansa na makakuha ng trabaho!' 
                        : 'The verified badge shows employers that you are genuine and skilled. Higher chance of getting hired!' 
                    ?>
                </div>
            </div>
            
            <div style="text-align:center;">
                <button onclick="closeJobfinderInfoModal()" style="background:linear-gradient(135deg, #2196f3 0%, #1976d2 100%); color:#fff; border:none; border-radius:10px; padding:12px 32px; font-size:0.95rem; font-weight:600; cursor:pointer; box-shadow:0 4px 12px rgba(33,150,243,0.3); transition:all 0.2s ease;">
                    <?= $lang === 'tl' ? 'Naiintindihan Ko' : 'Got It' ?>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes modalFadeIn {
    from { transform: scale(0.9); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
#jobfinderInfoModal button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(33,150,243,0.4);
}
/* Info button hover effect */
#nav-notification button[aria-label="Jobfinder Info"]:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(33,150,243,0.5);
}
</style>

<script>
function showJobfinderInfoModal() {
    const modal = document.getElementById('jobfinderInfoModal');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeJobfinderInfoModal() {
    const modal = document.getElementById('jobfinderInfoModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
document.getElementById('jobfinderInfoModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeJobfinderInfoModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeJobfinderInfoModal();
    }
});
</script>

<!-- Success Modal -->
<div id="successModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px); z-index:10000; align-items:center; justify-content:center; animation:fadeIn 0.3s ease;">
    <div style="background:linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%); border-radius:20px; padding:2.5rem 2rem; max-width:420px; width:90%; text-align:center; box-shadow:0 20px 60px rgba(0,0,0,0.3); animation:zoomIn 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);">
        <div id="successIcon" style="width:80px; height:80px; background:linear-gradient(135deg, #15b300 0%, #0e7c00 100%); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1.5rem; box-shadow:0 8px 20px rgba(21,179,0,0.3); animation:bounceIn 0.6s ease;">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>
        <h3 style="color:#1a1a1a; margin-bottom:1rem; font-size:1.6rem; font-weight:700;">Success!</h3>
        <p id="successMessage" style="color:#6c757d; margin-bottom:2rem; font-size:1rem; line-height:1.6;"></p>
        <button onclick="closeSuccessModal()" style="background:linear-gradient(135deg, #15b300 0%, #0e7c00 100%); color:white; border:none; border-radius:25px; padding:12px 36px; font-weight:600; font-size:1rem; cursor:pointer; box-shadow:0 4px 12px rgba(21,179,0,0.3); transition:all 0.2s; letter-spacing:0.3px;">
            OK
        </button>
    </div>
</div>

<style>
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes zoomIn {
    from { transform: scale(0.8); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
@keyframes bounceIn {
    0% { transform: scale(0); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}
#successModal button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(21,179,0,0.4);
}
</style>

<!-- Automatic logout script for logged-in users -->
<?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
<script src="auto_logout.js"></script>
<?php endif; ?>

<script>
// Success Modal Functions
function showSuccessModal(message) {
    const modal = document.getElementById('successModal');
    const messageEl = document.getElementById('successMessage');
    messageEl.textContent = message;
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeSuccessModal() {
    const modal = document.getElementById('successModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    // Reload page to show updated data
    location.reload();
}

// Close modal when clicking outside
document.getElementById('successModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeSuccessModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('successModal').style.display === 'flex') {
        closeSuccessModal();
    }
});

// AJAX Form Handlers
document.addEventListener('DOMContentLoaded', function() {
    // Edit Profile AJAX
    const editProfileForm = document.querySelector('form[name="edit_profile"]');
    if (editProfileForm) {
        editProfileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            // Show loading indicator
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            }
            
            fetch('ajax_update_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Restore button
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
                
                if (data.success) {
                    showSuccessModal(data.message);
                } else {
                    alert('❌ ' + data.message);
                }
            })
            .catch(error => {
                // Restore button on error
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });
    }

    // Change Password AJAX
    const passwordForm = document.querySelector('form[name="change_password"], #passwordSection form');
    if (passwordForm) {
        console.log('Password form found:', passwordForm);
        passwordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            // Debug: Log form data
            console.log('Submitting password change...');
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + (pair[0].includes('password') ? '***' : pair[1]));
            }
            
            fetch('ajax_change_password.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    showSuccessModal(data.message);
                    this.reset();
                } else {
                    alert('❌ ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });
    } else {
        console.log('Password form NOT found!');
    }

    // Jobfinder Skills AJAX
    const jobfinderForm = document.querySelector('form[name="edit_jobfinder_skills"]');
    if (jobfinderForm) {
        jobfinderForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('ajax_update_jobfinder.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessModal(data.message);
                    toggleJobfinderEdit(false);
                } else {
                    alert('❌ ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });
    }

    // Jobfinder Toggle AJAX
    const toggleForm = document.querySelector('form button[name="toggle_jobfinder_active"]')?.closest('form');
    if (toggleForm) {
        toggleForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            fetch('ajax_toggle_jobfinder.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessModal(data.message);
                } else {
                    alert('❌ ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });
    }
});
</script>

</html>