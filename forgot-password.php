<?php
include 'config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

$message = "";

// Mail Server Settings
$mailHost = "smtp.gmail.com";
$mailUser = "mikronario@gmail.com";
$mailPass = "tzjg mxoh rgek vnuy"; // Gmail App Password
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Password Reset - Barangay Sabang</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: "Inter", sans-serif;
      min-height: 100vh;
      background: linear-gradient(135deg, #f7faf7 0%, #e8f5e9 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      position: relative;
    }

    .reset-container {
      display: flex;
      max-width: 1000px;
      width: 100%;
      margin: 0 auto;
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 10px 40px rgba(21, 179, 0, 0.15);
      overflow: hidden;
      animation: fadeInUp 0.6s ease;
    }

    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .reset-form-side {
      flex: 1;
      padding: 48px 40px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      position: relative;
    }

    .back-btn {
      position: absolute;
      top: 20px;
      right: 20px;
      background: #f5f5f5;
      border: none;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      color: #333;
      font-size: 20px;
    }

    .back-btn:hover {
      background: #15b300;
      color: #fff;
      transform: rotate(90deg);
    }

    .reset-header {
      margin-bottom: 32px;
    }

    .reset-logo {
      width: 60px;
      height: 60px;
      margin-bottom: 20px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(21, 179, 0, 0.2);
    }

    .reset-title {
      font-size: 2rem;
      font-weight: 700;
      color: #15b300;
      margin-bottom: 8px;
      line-height: 1.2;
    }

    .reset-subtitle {
      color: #666;
      font-size: 1rem;
      line-height: 1.5;
    }

    .reset-form {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .form-label {
      font-weight: 600;
      color: #15b300;
      font-size: 0.95rem;
    }

    .input-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }

    .input-icon {
      position: absolute;
      left: 16px;
      color: #15b300;
      font-size: 1.1rem;
      pointer-events: none;
    }

    .form-input {
      width: 100%;
      padding: 14px 16px 14px 48px;
      border: 2px solid #e0e0e0;
      border-radius: 10px;
      font-size: 1rem;
      transition: all 0.3s ease;
      background: #fafafa;
    }

    .form-input:focus {
      outline: none;
      border-color: #15b300;
      background: #fff;
      box-shadow: 0 0 0 4px rgba(21, 179, 0, 0.1);
    }

    .form-input::placeholder {
      color: #999;
    }

    .input-hint {
      font-size: 0.875rem;
      color: #666;
      margin-left: 4px;
    }

    .form-submit-btn {
      width: 100%;
      background: linear-gradient(135deg, #15b300 0%, #0e7c00 100%);
      color: #fff;
      font-weight: 600;
      padding: 14px;
      border: none;
      border-radius: 10px;
      font-size: 1.1rem;
      cursor: pointer;
      transition: all 0.3s ease;
      margin-top: 8px;
      box-shadow: 0 4px 12px rgba(21, 179, 0, 0.3);
    }

    .form-submit-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(21, 179, 0, 0.4);
    }

    .form-submit-btn:active {
      transform: translateY(0);
    }

    .alert {
      padding: 14px 18px;
      border-radius: 10px;
      font-weight: 500;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
      animation: slideIn 0.4s ease;
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateX(-20px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    .alert-success {
      background: #e8f5e9;
      color: #2e7d32;
      border-left: 4px solid #15b300;
    }

    .alert-danger {
      background: #ffebee;
      color: #c62828;
      border-left: 4px solid #c62828;
    }

    .alert a {
      color: inherit;
      font-weight: 700;
      text-decoration: underline;
    }

    .reset-image-side {
      flex: 1;
      background: linear-gradient(135deg, #eafbe6 0%, #d4f1d0 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow: hidden;
      padding: 40px;
    }

    .reset-bg-image {
      width: 100%;
      height: 100%;
      object-fit: cover;
      opacity: 0.9;
    }

    .reset-illustration {
      max-width: 100%;
      height: auto;
      filter: drop-shadow(0 10px 30px rgba(0, 0, 0, 0.1));
    }

    /* Responsive Design */
    @media (max-width: 992px) {
      .reset-container {
        flex-direction: column;
        max-width: 500px;
      }

      .reset-image-side {
        display: none;
      }

      .reset-form-side {
        padding: 40px 32px;
      }

      .reset-title {
        font-size: 1.75rem;
      }
    }

    @media (max-width: 576px) {
      body {
        padding: 16px;
      }

      .reset-container {
        border-radius: 16px;
      }

      .reset-form-side {
        padding: 32px 24px;
      }

      .reset-logo {
        width: 50px;
        height: 50px;
      }

      .reset-title {
        font-size: 1.5rem;
      }

      .reset-subtitle {
        font-size: 0.9rem;
      }

      .form-input {
        padding: 12px 14px 12px 44px;
        font-size: 0.95rem;
      }

      .form-submit-btn {
        padding: 12px;
        font-size: 1rem;
      }

      .back-btn {
        width: 36px;
        height: 36px;
        font-size: 18px;
      }
    }
  </style>
</head>
<body>
<div class="reset-container">
  <div class="reset-form-side">
    <a href="index.php" class="back-btn" title="Back to Home">✕</a>
    
    <div class="reset-header">
      <img src="logo.jpg" alt="Barangay Logo" class="reset-logo">

<?php
if (isset($_GET['uid'], $_GET['token'])) {
    $uid = intval($_GET['uid']);
    $token = $_GET['token'];

    $stmt = $conn->prepare("SELECT reset_expires FROM useraccounts WHERE userid=? AND reset_token=?");
    $stmt->bind_param("is", $uid, $token);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result && strtotime($result['reset_expires']) > time()) {
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_password'])) {
            $new_password = $_POST['new_password'];
            $update = $conn->prepare("UPDATE useraccounts 
                                      SET password=?, reset_token=NULL, reset_expires=NULL 
                                      WHERE userid=?");
            $update->bind_param("si", $new_password, $uid);
            $update->execute();
            $message = "<div class='alert alert-success'>✓ Password reset successful. <a href='index.php'>Login here</a>.</div>";
        }
        echo "<h2 class='reset-title'>Reset Your Password</h2>";
        echo "<p class='reset-subtitle'>Enter your new password below</p>";
        echo "</div>"; // close reset-header
        if ($message) echo $message;
        ?>
        <form method="POST" class="reset-form">
          <div class="form-group">
            <label class="form-label">New Password</label>
            <div class="input-wrapper">
              <span class="input-icon">🔒</span>
              <input type="password" class="form-input" name="new_password" required placeholder="Enter new password" minlength="8">
            </div>
            <small class="input-hint">💡 Must be at least 8 characters</small>
          </div>
          <button type="submit" class="form-submit-btn">Reset Password</button>
        </form>
        <?php
    } else {
        echo "<h2 class='reset-title'>Invalid Link</h2>";
        echo "</div>"; // close reset-header
        echo "<div class='alert alert-danger'>⚠ Invalid or expired reset link. Please request a new one.</div>";
    }
} else {
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['userid'], $_POST['email'])) {
        $userid = intval($_POST['userid']); 
        $email  = strtolower(trim($_POST['email']));

        $stmt = $conn->prepare("SELECT userid, TRIM(LOWER(email)) AS email 
                                FROM useraccounts 
                                WHERE userid=? AND TRIM(LOWER(email))=TRIM(LOWER(?))");
        $stmt->bind_param("is", $userid, $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows > 0) {
            $result = $res->fetch_assoc();
            $uid = $result['userid'];
            $token = bin2hex(random_bytes(16));
            $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

            $update = $conn->prepare("UPDATE useraccounts SET reset_token=?, reset_expires=? WHERE userid=?");
            $update->bind_param("ssi", $token, $expiry, $uid);
            $update->execute();

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = $mailHost;
                $mail->SMTPAuth = true;
                $mail->Username = $mailUser;
                $mail->Password = $mailPass;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom($mailUser, 'Password Reset');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = "Password Reset Request";
                $reset_link = "http://localhost/Webs/forgot-password.php?uid=" . urlencode($uid) . "&token=" . urlencode($token);
                $mail->Body = "Click this link to reset your password: <a href='$reset_link'>$reset_link</a><br>This link expires in 1 hour.";

                $mail->send();
                $message = "<div class='alert alert-success'>✓ A reset link has been sent to your email.</div>";
            } catch (Exception $e) {
                $message = "<div class='alert alert-danger'>⚠ Email could not be sent. Mailer Error: {$mail->ErrorInfo}</div>";
            }
        } else {
            $message = "<div class='alert alert-danger'>⚠ No account found. Please check your User ID and Email, and try again.</div>";
        }
    }
  ?>
  <h2 class="reset-title">Forgot Password</h2>
  <p class="reset-subtitle">Enter your User ID and email to receive a password reset link</p>
  </div> <!-- close reset-header -->
  
  <?php if ($message) echo $message; ?>
  
  <form method="POST" class="reset-form">
      <div class="form-group">
        <label class="form-label">User ID</label>
        <div class="input-wrapper">
          <span class="input-icon">👤</span>
          <input type="number" class="form-input" name="userid" required placeholder="Enter your User ID">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Email Address</label>
        <div class="input-wrapper">
          <span class="input-icon">✉️</span>
          <input type="email" class="form-input" name="email" required placeholder="Enter your email">
        </div>
        <small class="input-hint">💡 Enter the email linked to your account</small>
      </div>
      <button type="submit" class="form-submit-btn">Send Reset Link</button>
    </form>
    <?php
}
?>
  </div> <!-- close reset-form-side -->
  
  <div class="reset-image-side">
    <img src="logo.jpg" alt="Barangay Sabang" class="reset-illustration">
  </div>
</div> <!-- close reset-container -->
</body>
</html>
