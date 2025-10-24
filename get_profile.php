<?php

include 'config.php';
if (!isset($_GET['userid'])) {
    echo json_encode(['error' => 'No userid']);
    exit;
}
$userid = $_GET['userid'];
$stmt = $conn->prepare("SELECT unique_id, surname, first_name, age, occupation_skills, profile_image, skill_description, COALESCE(jobfinder_verified, 0) AS jobfinder_verified FROM residents WHERE unique_id=?");
$stmt->bind_param("s", $userid);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    echo json_encode($row);
} else {
    echo json_encode(['error' => 'Not found']);
}
$stmt->close();