<!-- File: includes/security.php -->
<?php
// Security configuration that works on both HTTP and HTTPS
function secure_session_start() {
    $session_name = 'METROCLINIC_SESSION';
    
    // Use cookies only if available
    if (ini_set('session.use_only_cookies', 1) === FALSE) {
        // If we can't set use_only_cookies, continue anyway but log it
        error_log("Could not set session.use_only_cookies");
    }
    
    // Get current cookie params
    $cookieParams = session_get_cookie_params();
    
    // Set more secure cookie parameters
    session_set_cookie_params([
        'lifetime' => $cookieParams["lifetime"],
        'path' => $cookieParams["path"],
        'domain' => $cookieParams["domain"],
        // Only set secure flag if we're on HTTPS
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    
    session_name($session_name);
    
    // Start session
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Store session creation time
    if (!isset($_SESSION['CREATED'])) {
        $_SESSION['CREATED'] = time();
    } 
    // Regenerate session ID every 30 minutes
    elseif (time() - $_SESSION['CREATED'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['CREATED'] = time();
    }
    
    // Store IP and User Agent for validation (but not on shared hosting where IP changes)
    if (!isset($_SESSION['USER_AGENT'])) {
        $_SESSION['USER_AGENT'] = md5($_SERVER['HTTP_USER_AGENT'] ?? '');
    } else {
        // Only validate user agent, not IP (for shared hosting compatibility)
        $currentUserAgent = md5($_SERVER['HTTP_USER_AGENT'] ?? '');
        if ($_SESSION['USER_AGENT'] !== $currentUserAgent) {
            // Possible session hijacking
            session_destroy();
            header('Location: ../index.php?error=session_invalid');
            exit();
        }
    }
}

// Generate CSRF token
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Input sanitization
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return $data;
}

// Role-based access control (simplified for now)
function check_role_access($allowed_roles) {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
        $_SESSION['error'] = 'You do not have permission to access this page.';
        header('Location: dashboard.php');
        exit();
    }
}

// Password hashing and verification
function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['staff_id']) && isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
}

// Redirect if not logged in
function require_login() {
    if (!is_logged_in()) {
        header('Location: ../index.php?error=login_required');
        exit();
    }
}

// Set security headers
function set_security_headers() {
    // Remove or modify these headers if causing issues
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('X-Content-Type-Options: nosniff');
    
    // Only set CSP if it doesn't break your site
    // header("Content-Security-Policy: default-src 'self'");
    
    // Remove or comment out Referrer-Policy if causing issues
    // header('Referrer-Policy: strict-origin-when-cross-origin');
}

// Logout function
function logout() {
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
}
?>