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
    $occupation_skills = trim($_POST['occupation_skills'] ?? '');
    $skill_description = trim($_POST['skill_description'] ?? '');

    $sql = "UPDATE residents 
            SET occupation_skills=?, skill_description=? 
            WHERE unique_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $occupation_skills, $skill_description, $userid);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Skills updated successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update skills']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
