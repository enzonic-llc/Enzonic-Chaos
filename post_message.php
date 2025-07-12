<?php
require_once 'auth.php';

header('Content-Type: application/json');

$nsfwKeywords = [
    'nsfw_keyword1', 'nsfw_keyword2', 'nsfw_keyword3',
    'adult', 'porn', 'sex', 'xxx', 'nude', 'naked', 'erotic', 'explicit',
    'gambling', 'violence', 'hate speech', 'racism', 'discrimination'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $username = $_SESSION['username'] ?? null;

        if (!$username) {
            echo json_encode(['success' => false, 'message' => 'User not logged in to perform typing actions.']);
            http_response_code(401);
            exit;
        }

        if ($action === 'typing') {
            if (!isset($_SESSION['typing_users'])) {
                $_SESSION['typing_users'] = [];
            }
            if (!in_array($username, $_SESSION['typing_users'])) {
                $_SESSION['typing_users'][] = $username;
            }
            echo json_encode(['success' => true, 'message' => 'Typing status updated.']);
        } elseif ($action === 'stop_typing') {
            if (isset($_SESSION['typing_users'])) {
                $_SESSION['typing_users'] = array_filter($_SESSION['typing_users'], function($user) use ($username) {
                    return $user !== $username;
                });
            }
            echo json_encode(['success' => true, 'message' => 'Stop typing status updated.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
            http_response_code(400);
        }
        exit;
    }

    if (!isset($_SESSION['username'])) {
        echo json_encode(['success' => false, 'message' => 'User not logged in to send messages.']);
        http_response_code(401);
        exit;
    }

    try {
        $username = $_SESSION['username'];
        $message = $_POST['message'];

        $isNSFW = false;
        foreach ($nsfwKeywords as $keyword) {
            if (stripos($message, $keyword) !== false) {
                $isNSFW = true;
                break;
            }
        }

        if ($isNSFW) {
            echo json_encode(['success' => false, 'message' => 'Your message contains inappropriate content and cannot be sent.']);
            http_response_code(400);
            exit;
        }

        $sanitizedMessage = htmlspecialchars($message);

        if (!empty($sanitizedMessage)) {
            $stmt = $db->prepare("INSERT INTO messages (username, message) VALUES (:username, :message)");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':message', $sanitizedMessage);
            $stmt->execute();

            echo json_encode(['success' => true, 'message' => 'Message sent successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Message cannot be empty.']);
            http_response_code(400);
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error.']);
        http_response_code(500);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    http_response_code(400);
}
?>
