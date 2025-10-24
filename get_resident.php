<?php
session_start();
include 'config.php';

// ✅ Ensure user is logged in
if (!isset($_SESSION['userid'])) {
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$userid = intval($_SESSION['userid']); // stored in session

try {
    $sql = "SELECT unique_id, surname, first_name, middle_name, age, sex, address, birthdate, citizenship 
            FROM residents 
            WHERE unique_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            "id"          => $row['unique_id'],
            "surname"     => $row['surname'],
            "first_name"  => $row['first_name'],
            "middle_name" => $row['middle_name'],
            "age"         => $row['age'],
            "sex"         => $row['sex'],
            "address"     => $row['address'],
            "birthdate"   => $row['birthdate'],
            "citizenship" => $row['citizenship']
        ]);
    } else {
        echo json_encode(["error" => "Resident not found"]);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode(["error" => "Unexpected error: " . $e->getMessage()]);
}
