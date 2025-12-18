<?php
// Simple session start
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['staff_id']) && isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    header('Location: dashboard.php');
    exit();
}

// Database connection
require_once 'database.php';

$error = '';
$success = '';

// Handle login form submission
if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Basic validation
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            // Prepare SQL statement to prevent SQL injection
            $stmt = $pdo->prepare("SELECT * FROM staff WHERE username = ? AND is_active = 1 LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Set session variables
                    $_SESSION['staff_id'] = $user['staff_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['authenticated'] = true;
                    $_SESSION['login_time'] = time();
                    $_SESSION['last_activity'] = time();
                    
                    // Redirect to dashboard
                    header('Location: dashboard.php');
                    exit();
                } else {
                    $error = 'Invalid username or password.';
                }
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            $error = 'Database error. Please try again later.';
            error_log("Login error: " . $e->getMessage());
        }
    }
}

// Display error messages from redirects
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'session_expired':
            $error = 'Your session has expired. Please login again.';
            break;
        case 'session_timeout':
            $error = 'Session timeout. Please login again.';
            break;
        case 'login_required':
            $error = 'Please login to access the system.';
            break;
        case 'unauthorized':
            $error = 'You do not have permission to access that page.';
            break;
        default:
            $error = 'An error occurred. Please try again.';
    }
}

// Display success messages
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'logged_out':
            $success = 'You have been successfully logged out.';
            break;
        case 'password_changed':
            $success = 'Password changed successfully.';
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Metro Clinic HMS - Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            overflow: hidden;
        }
        
        .clinic-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        
        .clinic-header h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .clinic-header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .login-form {
            padding: 30px;
        }
        
        .login-form h2 {
            color: #444;
            margin-bottom: 25px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            border-color: #667eea;
            outline: none;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 14px;
            width: 100%;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
            text-align: center;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
            text-align: center;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
        
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .login-container {
                border-radius: 10px;
            }
            
            .clinic-header {
                padding: 20px 15px;
            }
            
            .clinic-header h1 {
                font-size: 24px;
            }
            
            .login-form {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="clinic-header">
            <h1>Metro Clinic HMS</h1>
            <p>Hospital Management System</p>
        </div>
        
        <div class="login-form">
            <h2>Staff Login</h2>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required 
                           placeholder="Enter your username" 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Enter your password">
                </div>
                
                <button type="submit" name="login" class="btn-login">Login</button>
            </form>
            
            <div class="login-footer">
                <p>Contact System Administrator for Access Issues</p>
                <p style="font-size: 12px; margin-top: 5px; color: #999;">
                </p>
            </div>
        </div>
    </div>
</body>
</html>