<!-- File: includes/auth_check.php -->
<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Simple authentication check
function require_auth() {
    // Check if user is logged in
    if (!isset($_SESSION['staff_id']) || !isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
        // Redirect to login with error message
        header('Location: ../index.php?error=session_expired');
        exit();
    }
    
    // Check session age (optional, can be commented out if causing issues)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
        // Session is older than 1 hour
        session_unset();
        session_destroy();
        header('Location: ../index.php?error=session_timeout');
        exit();
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
}
?>