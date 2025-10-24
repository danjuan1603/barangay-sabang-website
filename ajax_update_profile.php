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
        
        error_log("Sending verification email - Link: $verify_link");
        
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
        
        $mail->AltBody = "Click this link to verify your new email: $verify_link\n\nIf you did not request this change, please ignore this email.";

        $mail->send();
        error_log("Verification email sent successfully to: " . $email);
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        error_log("Exception: " . $e->getMessage());
        return false;
    }
}

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$userid = $_SESSION['userid'];

// Fetch current resident data
$stmt = $conn->prepare("SELECT * FROM residents WHERE unique_id=?");
$stmt->bind_param("s", $userid);
$stmt->execute();
$resident = $stmt->get_result()->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $surname = trim($_POST['surname'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $new_email = trim($_POST['email'] ?? '');
    $civil_status = trim($_POST['civil_status'] ?? '');
    $place_of_birth = trim($_POST['place_of_birth'] ?? '');
    $education = trim($_POST['education'] ?? '');
    $occupation_skills = trim($_POST['occupation_skills'] ?? '');
    $skill_description = trim($_POST['skill_description'] ?? '');
    $profile_image = $resident['profile_image'];

    // Handle image upload if new image is provided
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK && !empty($_FILES['profile_image']['tmp_name'])) {
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

    // Check if email is being changed
    if (!empty($new_email) && $new_email !== $resident['email']) {
        // Email is being changed - require verification
        $verification_code = bin2hex(random_bytes(16));
        
        error_log("Email change detected - Old: " . ($resident['email'] ?? 'NULL') . ", New: $new_email");
        error_log("Verification token: $verification_code");
        
        $sql = "UPDATE residents 
                SET surname=?, first_name=?, middle_name=?, civil_status=?, place_of_birth=?, education=?, 
                    occupation_skills=?, skill_description=?, pending_email=?, verify_token=?, is_verified=0, profile_image=? 
                WHERE unique_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssssss", 
            $surname, $first_name, $middle_name, $civil_status, $place_of_birth, $education,
            $occupation_skills, $skill_description, $new_email, $verification_code, $profile_image, $userid
        );
        
        if ($stmt->execute()) {
            error_log("Database updated - Rows affected: " . $stmt->affected_rows);
            
            if (sendVerificationEmail($new_email, $userid, $verification_code)) {
                echo json_encode([
                    'success' => true, 
                    'message' => '✓ Profile updated successfully! A verification email has been sent to ' . $new_email . '. Please check your inbox and verify your email.',
                    'profile_image' => $profile_image,
                    'email_verification_sent' => true
                ]);
            } else {
                echo json_encode([
                    'success' => false, 
                    'message' => '⚠ Profile updated but failed to send verification email. Please try again or contact support.',
                    'profile_image' => $profile_image
                ]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
        }
    } else {
        // No email change - update normally
        $sql = "UPDATE residents 
                SET surname=?, first_name=?, middle_name=?, civil_status=?, place_of_birth=?, education=?, 
                    occupation_skills=?, skill_description=?, profile_image=? 
                WHERE unique_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssss", 
            $surname, $first_name, $middle_name, $civil_status, $place_of_birth, $education,
            $occupation_skills, $skill_description, $profile_image, $userid
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'message' => 'Profile updated successfully!',
                'profile_image' => $profile_image
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
