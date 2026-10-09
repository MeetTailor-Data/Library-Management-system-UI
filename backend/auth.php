<?php
/**
 * Smart Library Management System - Authentication Handler (SQL PDO)
 * Handles Login, Registration (Signup), Logout, and Session Verification.
 */

require_once __DIR__ . '/db.php';

$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'login':
        handle_login();
        break;

    case 'signup':
        handle_signup();
        break;

    case 'logout':
        handle_logout();
        break;

    case 'get_user':
        get_user_info();
        break;

    default:
        json_response(false, 'Invalid authentication action specified.', null, 400);
}

// 1. Process Login
function handle_login() {
    $userId = trim($_POST['user_id'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($userId) || empty($password)) {
        json_response(false, 'Please provide both User ID and Password.', null, 400);
    }

    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM users WHERE LOWER(user_id) = LOWER(?) OR LOWER(email) = LOWER(?) LIMIT 1");
    $stmt->execute([$userId, $userId]);
    $matchedUser = $stmt->fetch();

    if ($matchedUser) {
        // Verify password (plain text or password_hash)
        $passwordMatches = ($matchedUser['password'] === $password) || password_verify($password, $matchedUser['password']);

        if ($passwordMatches) {
            // Set PHP Session
            $_SESSION['user'] = [
                'id' => (int)$matchedUser['id'],
                'user_id' => $matchedUser['user_id'],
                'name' => $matchedUser['name'],
                'email' => $matchedUser['email'],
                'role' => $matchedUser['role']
            ];

            // Return JSON or redirect
            if (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                json_response(true, 'Login successful!', $_SESSION['user']);
            } else {
                $redirectUrl = ($matchedUser['role'] === 'admin') 
                    ? '../Pages/admin-dashboard.html' 
                    : '../Pages/student-dashboard.html';
                header("Location: " . $redirectUrl);
                exit();
            }
        }
    }

    json_response(false, 'Invalid User ID or Password. Please try again.', null, 401);
}

// 2. Process Registration / Signup
function handle_signup() {
    $name = trim($_POST['full_name'] ?? '');
    $studentId = strtoupper(trim($_POST['student_id'] ?? ''));
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmPass = trim($_POST['confirm_password'] ?? '');

    if (empty($name) || empty($studentId) || empty($email) || empty($password)) {
        json_response(false, 'All registration fields are required.', null, 400);
    }

    if ($password !== $confirmPass) {
        json_response(false, 'Passwords do not match.', null, 400);
    }

    $db = get_db();

    // Check for duplicate Student ID or Email
    $checkStmt = $db->prepare("SELECT user_id, email FROM users WHERE LOWER(user_id) = LOWER(?) OR LOWER(email) = LOWER(?) LIMIT 1");
    $checkStmt->execute([$studentId, $email]);
    $existing = $checkStmt->fetch();

    if ($existing) {
        if (strcasecmp($existing['user_id'], $studentId) === 0) {
            json_response(false, "Student ID '{$studentId}' is already registered.", null, 409);
        }
        if (strcasecmp($existing['email'], $email) === 0) {
            json_response(false, "Email '{$email}' is already registered.", null, 409);
        }
    }

    // Insert new user into SQL database
    $insertStmt = $db->prepare("INSERT INTO users (user_id, name, email, password, role, created_at) VALUES (?, ?, ?, ?, 'student', ?)");
    $createdDate = date('Y-m-d');
    $insertStmt->execute([$studentId, $name, $email, $password, $createdDate]);
    $newId = (int)$db->lastInsertId();

    $newUser = [
        'id' => $newId,
        'user_id' => $studentId,
        'name' => $name,
        'email' => $email,
        'role' => 'student'
    ];

    // Auto-login newly registered student
    $_SESSION['user'] = $newUser;

    if (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        json_response(true, 'Account created successfully!', $newUser);
    } else {
        header("Location: ../Pages/student-dashboard.html");
        exit();
    }
}

// 3. Process Logout
function handle_logout() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();

    if (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        json_response(true, 'Logged out successfully.');
    } else {
        header("Location: ../Pages/index.html");
        exit();
    }
}

// 4. Get Current User Info
function get_user_info() {
    $user = get_logged_in_user();
    if ($user) {
        json_response(true, 'User is authenticated.', $user);
    } else {
        json_response(false, 'No active session found.', null, 401);
    }
}
?>
