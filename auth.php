<?php
session_start();

$dbFile = 'messages.db';
$db = null;

try {
    $db = new PDO('sqlite:' . $dbFile);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $db->exec("CREATE TABLE IF NOT EXISTS messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL,
        message TEXT NOT NULL,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $db = null;
}

function registerUser($username, $password, $terms_agree) {
    global $db;

    if (empty($username) || empty($password)) {
        $_SESSION['error_message'] = 'Username and password cannot be empty.';
        return false;
    }

    if (!$terms_agree) {
        $_SESSION['error_message'] = 'You must agree to the Terms of Service to register.';
        return false;
    }

    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['error_message'] = 'Username already taken. Please choose a different one.';
        return false;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $db->prepare("INSERT INTO users (username, password) VALUES (:username, :password)");
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $hashedPassword);

    if ($stmt->execute()) {
        $_SESSION['username'] = $username;
        return true;
    } else {
        $_SESSION['error_message'] = 'Registration failed. Please try again.';
        return false;
    }
}

function loginUser($username, $password) {
    global $db;

    if (empty($username) || empty($password)) {
        $_SESSION['error_message'] = 'Username and password cannot be empty.';
        return false;
    }

    $stmt = $db->prepare("SELECT password FROM users WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['username'] = $username;
        return true;
    } else {
        $_SESSION['error_message'] = 'Invalid username or password.';
        return false;
    }
}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            $action = $_POST['action'];
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $terms_agree = isset($_POST['terms_agree']) && $_POST['terms_agree'] === 'on';

            if ($action === 'register') {
                $success = registerUser($username, $password, $terms_agree);
            } elseif ($action === 'login') {
                $success = loginUser($username, $password);
            } else {
                $_SESSION['error_message'] = 'Invalid action.';
                $success = false;
            }
        } else {
            $_SESSION['error_message'] = 'No action specified.';
            $success = false;
        }
        if ($success) {
            header('Location: index.php');
            exit;
        } else {
            header('Location: index.php');
            exit;
        }
    }
}
?>
