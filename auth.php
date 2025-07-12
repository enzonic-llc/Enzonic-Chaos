<?php
session_start();

// Function to generate CSRF token
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Function to validate CSRF token
function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

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

function registerUser($username, $password, $terms_agree) { // Changed parameter name back
    global $db;

    if (empty($username) || empty($password)) { // Changed variable name back
        $_SESSION['error_message'] = 'Username and password cannot be empty.'; // Updated message back
        return false;
    }

    // Removed 4-digit validation

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

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT); // Re-introduced password hashing

    $stmt = $db->prepare("INSERT INTO users (username, password) VALUES (:username, :password)");
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $hashedPassword); // Using hashed password

    if ($stmt->execute()) {
        $_SESSION['username'] = $username;
        return true;
    } else {
        $_SESSION['error_message'] = 'Registration failed. Please try again.';
        return false;
    }
}

function loginUser($username, $password) { // Changed parameter name back
    global $db;

    if (empty($username) || empty($password)) { // Changed variable name back
        $_SESSION['error_message'] = 'Username and password cannot be empty.'; // Updated message back
        return false;
    }

    // Removed 4-digit validation

    $stmt = $db->prepare("SELECT password FROM users WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Re-introduced password verification
    if ($user && password_verify($password, $user['password'])) { 
        $_SESSION['username'] = $username;
        return true;
    } else {
        $_SESSION['error_message'] = 'Invalid username or password.'; // Updated message back
        return false;
    }
}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    // Ensure CSRF token is generated and available in session
    generate_csrf_token();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            $action = $_POST['action'];
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? ''; // Changed from code to password back
            $terms_agree = isset($_POST['terms_agree']) && $_POST['terms_agree'] === 'on';
            $csrf_token = $_POST['csrf_token'] ?? ''; // Get the submitted token

            // Validate CSRF token for all actions
            if (!validate_csrf_token($csrf_token)) {
                $_SESSION['error_message'] = 'Invalid security token. Please try again.';
                header('Location: index.php');
                exit;
            }

            if ($action === 'register') {
                $success = registerUser($username, $password, $terms_agree); // Pass password
            } elseif ($action === 'login') {
                $success = loginUser($username, $password); // Pass password
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
