<?php
// Shared helper to lookup chatbot response from DB.
// Returns answer string when found, or null when not.
function lookup_chatbot_response($conn, $query) {
    $q = trim($query);
    if ($q === '') return null;
    // normalization: lowercase, remove punctuation, collapse spaces
    $lowerQuery = mb_strtolower($q, 'UTF-8');
    $normQuery = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $lowerQuery); // strip punctuation
    $normQuery = preg_replace('/\s+/u', ' ', $normQuery);
    $normQuery = trim($normQuery);

    // Fetch active chatbot responses
    $stmt = $conn->prepare("SELECT question, answer, keywords FROM chatbot_responses WHERE is_active = 1");
    if (!$stmt) return null;

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $questionRaw = isset($row['question']) ? $row['question'] : '';
        $keywordsRaw = isset($row['keywords']) ? $row['keywords'] : '';

        $question = mb_strtolower($questionRaw, 'UTF-8');
        $normQuestion = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $question);
        $normQuestion = preg_replace('/\s+/u', ' ', $normQuestion);
        $normQuestion = trim($normQuestion);

        // direct normalized contains match (both ways)
        if ($normQuestion !== '' && (mb_strpos($normQuery, $normQuestion) !== false || mb_strpos($normQuestion, $normQuery) !== false)) {
            return $row['answer'];
        }

        // keywords matching (comma-separated) - normalize each keyword
        if ($keywordsRaw !== '') {
            $keywordArray = array_map('trim', explode(',', $keywordsRaw));
            foreach ($keywordArray as $keyword) {
                if ($keyword === '') continue;
                $k = mb_strtolower($keyword, 'UTF-8');
                $k = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $k);
                $k = preg_replace('/\s+/u', ' ', $k);
                $k = trim($k);
                if ($k === '') continue;
                if (mb_strpos($normQuery, $k) !== false) {
                    return $row['answer'];
                }
            }
        }
    }

    $stmt->close();
    return null;
}
