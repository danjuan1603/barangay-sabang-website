<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'config.php';

$uid = $_GET['uid'] ?? '';
$token = $_GET['token'] ?? '';

// Debug logging
error_log("Verification attempt - UID: $uid, Token: $token");

if (!$uid || !$token) {
    die("<!DOCTYPE html>
    <html><head><title>Invalid Link</title>
    <style>body{font-family:Arial,sans-serif;text-align:center;padding:50px;background:#f5f7fa;}
    .container{background:#fff;padding:40px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.1);max-width:500px;margin:0 auto;}
    h2{color:#e53935;}</style></head><body>
    <div class='container'>
    <h2>Invalid verification link</h2>
    <p>Missing user ID or token.</p>
    </div></body></html>");
}

// Find resident with matching token
$stmt = $conn->prepare("SELECT * FROM residents WHERE unique_id=? AND verify_token=?");
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    die("Database error: " . $conn->error);
}

$stmt->bind_param("ss", $uid, $token);
$stmt->execute();
$res = $stmt->get_result();

error_log("Query result rows: " . $res->num_rows);

// Debug: If no match found, check if user exists with different token
if ($res->num_rows === 0) {
    $debugStmt = $conn->prepare("SELECT unique_id, verify_token, pending_email FROM residents WHERE unique_id=?");
    $debugStmt->bind_param("s", $uid);
    $debugStmt->execute();
    $debugRes = $debugStmt->get_result();
    if ($debugRes->num_rows > 0) {
        $debugData = $debugRes->fetch_assoc();
        error_log("User exists but token mismatch - DB Token: " . ($debugData['verify_token'] ?? 'NULL') . ", Provided Token: $token");
        error_log("Pending email in DB: " . ($debugData['pending_email'] ?? 'NULL'));
    } else {
        error_log("User ID not found in database: $uid");
    }
}

if ($res->num_rows === 1) {
    $resident = $res->fetch_assoc();
    $pending_email = $resident['pending_email'];
    
    error_log("Found resident - Pending email: " . ($pending_email ?? 'NULL'));
    
    // Check if there's a pending email to verify
    if (empty($pending_email)) {
        echo "<!DOCTYPE html>
        <html><head><title>Verification Failed</title>
        <style>body{font-family:Arial,sans-serif;text-align:center;padding:50px;background:#f5f7fa;}
        .container{background:#fff;padding:40px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.1);max-width:500px;margin:0 auto;}
        h2{color:#e53935;}</style></head><body>
        <div class='container'>
        <h2>Verification failed</h2>
        <p>No pending email found for verification.</p>
        <p><small>This link may have already been used.</small></p>
        </div></body></html>";
        exit;
    }

    // Update email in residents
    $updateResident = $conn->prepare("UPDATE residents SET email=?, pending_email=NULL, is_verified=1, verify_token=NULL WHERE unique_id=?");
    $updateResident->bind_param("ss", $pending_email, $uid);
    
    if ($updateResident->execute()) {
        error_log("Email verified successfully for: " . $pending_email);
        
        // Update email in useraccounts
        $updateAccount = $conn->prepare("UPDATE useraccounts SET email=? WHERE userid=?");
        $updateAccount->bind_param("ss", $pending_email, $uid);
        $updateAccount->execute();

        echo "<!DOCTYPE html>";
        echo "<html><head><title>Email Verified</title>";
        echo "<style>body{font-family:Arial,sans-serif;text-align:center;padding:50px;background:#f5f7fa;}";
        echo ".container{background:#fff;padding:40px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.1);max-width:500px;margin:0 auto;}";
        echo "h2{color:#15b300;}a{display:inline-block;margin-top:20px;padding:12px 24px;background:#15b300;color:#fff;text-decoration:none;border-radius:8px;}";
        echo "a:hover{background:#0e7c00;}</style></head><body>";
        echo "<div class='container'>";
        echo "<h2>✓ Email Verified!</h2>";
        echo "<p>Your email <b>" . htmlspecialchars($pending_email) . "</b> is now active on your profile.</p>";
        echo "<p style='margin-top:20px;color:#666;'>You can now close this window.</p>";
        echo "</div></body></html>";
    } else {
        echo "<h2>Database Error</h2>";
        echo "<p>Failed to update your email. Please try again or contact support.</p>";
        echo "<p>Error: " . htmlspecialchars($conn->error) . "</p>";
    }
} else {
    echo "<!DOCTYPE html>";
    echo "<html><head><title>Verification Failed</title>";
    echo "<style>body{font-family:Arial,sans-serif;text-align:center;padding:50px;background:#f5f7fa;}";
    echo ".container{background:#fff;padding:40px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.1);max-width:500px;margin:0 auto;}";
    echo "h2{color:#e53935;}a{display:inline-block;margin-top:20px;padding:12px 24px;background:#15b300;color:#fff;text-decoration:none;border-radius:8px;}";
    echo "a:hover{background:#0e7c00;}</style></head><body>";
    echo "<div class='container'>";
    echo "<h2>✗ Verification Failed</h2>";
    echo "<p>This verification link is invalid or has already been used.</p>";
    echo "<p><small>User ID: " . htmlspecialchars($uid) . "</small></p>";
    echo "</div></body></html>";
}
?>