<?php
header('Content-Type: application/json');
include 'config.php';

// Fetch active questions from chatbot_responses table
$stmt = $conn->prepare("SELECT question FROM chatbot_responses WHERE is_active = 1 ORDER BY id ASC");

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit;
}

$stmt->execute();
$result = $stmt->get_result();

$questions = [];
while ($row = $result->fetch_assoc()) {
    if (!empty($row['question'])) {
        $questions[] = $row['question'];
    }
}

$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'questions' => $questions]);
?>
