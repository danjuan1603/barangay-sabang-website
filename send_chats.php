<?php
session_start();
include 'config.php';

// ✅ Ensure user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['userid'])) {
    header('Content-Type: application/json');
    echo json_encode(["status" => "unauthorized"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userid = intval($_SESSION['userid']);
    $message = trim($_POST['message'] ?? '');

    if ($message !== '') {
        // ✅ Prevent accidental double-submit
        if (!isset($_SESSION['last_message']) || $_SESSION['last_message'] !== $message) {
            // Check if chatbot can answer first
            include_once 'chatbot_lookup.php';
            $foundResponse = lookup_chatbot_response($conn, $message);
            
            // Set is_read based on whether chatbot can answer
            // is_read = 1 if chatbot answers, is_read = 0 if chatbot cannot answer (admin needs to respond)
            $user_is_read = $foundResponse ? 1 : 0;
            
            $stmt = $conn->prepare("INSERT INTO admin_chats (userid, sender, message, created_at, is_read) VALUES (?, 'user', ?, NOW(), ?)");
            if ($stmt) {
                $stmt->bind_param("isi", $userid, $message, $user_is_read);
                if ($stmt->execute()) {
                    $_SESSION['last_message'] = $message; // ✅ Save last sent
                    
                    // Insert chatbot response
                    if ($foundResponse) {
                        $ainsert = $conn->prepare("INSERT INTO admin_chats (userid, sender, message, created_at, is_read) VALUES (?, 'admin', ?, NOW(), 1)");
                        if ($ainsert) {
                            $ainsert->bind_param('is', $userid, $foundResponse);
                            $ainsert->execute();
                            $ainsert->close();
                        }
                    } else {
                        $fallback = "Sorry, I don't have the answer to that. Please wait for the admin to respond.";
                        $finsert = $conn->prepare("INSERT INTO admin_chats (userid, sender, message, created_at, is_read) VALUES (?, 'admin', ?, NOW(), 0)");
                        if ($finsert) {
                            $finsert->bind_param('is', $userid, $fallback);
                            $finsert->execute();
                            $finsert->close();
                        }
                    }

                    header('Content-Type: application/json');
                    echo json_encode(["status" => "success"]);
                } else {
                    header('Content-Type: application/json');
                    echo json_encode(["status" => "error", "msg" => $stmt->error]);
                }
                $stmt->close();
            } else {
                header('Content-Type: application/json');
                echo json_encode(["status" => "dberror"]);
            }
        } else {
            // ✅ Message same as last one (possible double-click / auto resend)
            header('Content-Type: application/json');
            echo json_encode(["status" => "duplicate"]);
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(["status" => "empty"]);
    }
}

$conn->close();
