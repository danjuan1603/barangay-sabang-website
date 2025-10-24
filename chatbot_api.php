<?php
header('Content-Type: application/json');
include 'config.php';
include 'chatbot_lookup.php';

// Get user query
$query = isset($_GET['query']) ? trim($_GET['query']) : '';

if (empty($query)) {
    echo json_encode(['success' => false, 'message' => 'No query provided']);
    exit;
}

// Use shared lookup for matching
$foundResponse = lookup_chatbot_response($conn, $query);

if ($foundResponse) {
    echo json_encode([
        'success' => true,
        'response' => $foundResponse,
        'source' => 'database'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'response' => "Sorry, I don't have the answer to that. Please wait for the admin to respond.",
        'source' => 'fallback'
    ]);
}
?>
