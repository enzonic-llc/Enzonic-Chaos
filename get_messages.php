<?php
require_once 'auth.php';

header('Content-Type: application/json');

try {
    $messages = [];
    if (isset($_GET['last_id'])) {
        $lastId = (int)$_GET['last_id'];
        $stmt = $db->prepare("SELECT id, username, message, timestamp FROM messages WHERE id > :last_id ORDER BY id ASC");
        $stmt->bindParam(':last_id', $lastId, PDO::PARAM_INT);
        $stmt->execute();
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $db->query("SELECT id, username, message, timestamp FROM messages ORDER BY id ASC");
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Sanitize messages before sending to prevent XSS
foreach ($messages as &$msg) {
    $msg['message'] = htmlspecialchars($msg['message']);
}
unset($msg); // Unset the reference to avoid potential side effects

$response = [
    'messages' => $messages,
    'typing_users' => $_SESSION['typing_users'] ?? []
];
echo json_encode($response);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['error' => 'Database error.']);
    http_response_code(500);
    exit;
}
?>
