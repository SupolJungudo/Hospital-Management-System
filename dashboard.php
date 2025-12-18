<!-- File: dashboard.php -->
<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['staff_id']) || !isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: index.php?error=login_required');
    exit();
}

// Database connection
require_once 'database.php';

$staff_id = $_SESSION['staff_id'];
$role = $_SESSION['role'];
$full_name = $_SESSION['full_name'];

// Get user statistics based on role
$stats = [];

if ($role == 'admin') {
    // Admin stats
    $stmt = $pdo->query("SELECT COUNT(*) as total_patients FROM patients");
    $stats['total_patients'] = $stmt->fetch()['total_patients'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_staff FROM staff WHERE is_active = 1");
    $stats['total_staff'] = $stmt->fetch()['total_staff'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as today_consultations FROM consultations WHERE DATE(consultation_date) = CURDATE()");
    $stats['today_consultations'] = $stmt->fetch()['today_consultations'];
} elseif ($role == 'doctor') {
    // Doctor stats
    $stmt = $pdo->prepare("SELECT COUNT(*) as pending_consultations FROM consultations WHERE doctor_id = ? AND status = 'pending'");
    $stmt->execute([$staff_id]);
    $stats['pending_consultations'] = $stmt->fetch()['pending_consultations'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as today_consultations FROM consultations WHERE doctor_id = ? AND DATE(consultation_date) = CURDATE()");
    $stmt->execute([$staff_id]);
    $stats['today_consultations'] = $stmt->fetch()['today_consultations'];
} elseif ($role == 'pharmacy') {
    // Pharmacy stats
    $stmt = $pdo->query("SELECT COUNT(*) as pending_prescriptions FROM prescriptions WHERE status = 'pending'");
    $stats['pending_prescriptions'] = $stmt->fetch()['pending_prescriptions'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as today_dispensed FROM pharmacy_logs WHERE DATE(dispensed_at) = CURDATE()");
    $stats['today_dispensed'] = $stmt->fetch()['today_dispensed'];
}

// Get recent activities
$recent_activities = [];
if ($role == 'admin') {
    $stmt = $pdo->query("SELECT 'Patient Registered' as activity, CONCAT(p.first_name, ' ', p.last_name) as details, p.created_at as timestamp 
                         FROM patients p ORDER BY p.created_at DESC LIMIT 5");
} elseif ($role == 'doctor') {
    $stmt = $pdo->prepare("SELECT 'Consultation' as activity, CONCAT('Patient: ', p.first_name, ' ', p.last_name) as details, c.consultation_date as timestamp 
                           FROM consultations c 
                           JOIN patients p ON c.patient_id = p.patient_id 
                           WHERE c.doctor_id = ? 
                           ORDER BY c.consultation_date DESC LIMIT 5");
    $stmt->execute([$staff_id]);
} elseif ($role == 'pharmacy') {
    $stmt = $pdo->query("SELECT 'Medicine Dispensed' as activity, CONCAT(p.medicine_name, ' to ', pt.first_name) as details, p.dispensed_at as timestamp 
                         FROM prescriptions p 
                         JOIN consultations c ON p.consultation_id = c.consultation_id 
                         JOIN patients pt ON c.patient_id = pt.patient_id 
                         WHERE p.status = 'dispensed' 
                         ORDER BY p.dispensed_at DESC LIMIT 5");
}

if (isset($stmt)) {
    $recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Metro Clinic HMS</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card h3 {
            font-size: 36px;
            color: #667eea;
            margin: 10px 0;
        }
        
        .stat-card p {
            color: #666;
            font-size: 16px;
        }
        
        .stat-card.admin {
            border-top: 4px solid #667eea;
        }
        
        .stat-card.doctor {
            border-top: 4px solid #4CAF50;
        }
        
        .stat-card.pharmacy {
            border-top: 4px solid #FF9800;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 25px 0;
        }
        
        .action-btn {
            background: white;
            border: none;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .action-btn:hover {
            background: #667eea;
            color: white;
            transform: translateY(-3px);
        }
        
        .action-btn i {
            font-size: 24px;
            margin-bottom: 10px;
            display: block;
        }
        
        .welcome-message {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 25px;
        }
        
        .role-badge {
            display: inline-block;
            padding: 5px 15px;
            background: rgba(255,255,255,0.2);
            border-radius: 20px;
            font-size: 14px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>Metro Clinic HMS</h3>
            </div>
            
            <div class="user-info">
                <h4><?php echo htmlspecialchars($full_name); ?></h4>
                <div class="role-badge"><?php echo ucfirst($role); ?></div>
                <p><?php echo date('d M Y, H:i'); ?></p>
            </div>
            
            <ul class="nav-menu">
                <li><a href="dashboard.php" class="active">🏠 Dashboard</a></li>
                
                <?php if(in_array($role, ['reception', 'admin', 'doctor'])): ?>
                <li><a href="register_patient.php">👤 Register Patient</a></li>
                <?php endif; ?>
                
                <?php if($role == 'doctor'): ?>
                <li><a href="consultant_dashboard.php">🩺 My Consultations</a></li>
                <?php endif; ?>
                
                <?php if($role == 'pharmacy'): ?>
                <li><a href="pharmacy_dashboard.php">💊 Pharmacy</a></li>
                <?php endif; ?>
                
                <?php if($role == 'admin'): ?>
                <li><a href="admin_dashboard.php">⚙️ Admin Panel</a></li>
                <li><a href="reports.php">📊 Reports</a></li>
                <?php endif; ?>
                
                <li><a href="patient_profile.php">📁 Patient Records</a></li>
                <li><a href="logout.php">🚪 Logout</a></li>
            </ul>
            
            <div class="sidebar-footer">
                <small>System v1.0</small>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Dashboard</h1>
                <div class="header-actions">
                    <button onclick="location.reload()" class="btn-small">🔄 Refresh</button>
                </div>
            </div>
            
            <!-- Welcome Message -->
            <div class="welcome-message">
                <h2>Welcome back, <?php echo htmlspecialchars($full_name); ?>!</h2>
                <p>Here's what's happening in your department today.</p>
            </div>
            
            <!-- Quick Statistics -->
            <div class="stats-grid">
                <?php if($role == 'admin'): ?>
                    <div class="stat-card admin">
                        <h3><?php echo $stats['total_patients'] ?? 0; ?></h3>
                        <p>Total Patients</p>
                    </div>
                    <div class="stat-card admin">
                        <h3><?php echo $stats['total_staff'] ?? 0; ?></h3>
                        <p>Active Staff</p>
                    </div>
                    <div class="stat-card admin">
                        <h3><?php echo $stats['today_consultations'] ?? 0; ?></h3>
                        <p>Today's Consultations</p>
                    </div>
                    
                <?php elseif($role == 'doctor'): ?>
                    <div class="stat-card doctor">
                        <h3><?php echo $stats['pending_consultations'] ?? 0; ?></h3>
                        <p>Pending Consultations</p>
                    </div>
                    <div class="stat-card doctor">
                        <h3><?php echo $stats['today_consultations'] ?? 0; ?></h3>
                        <p>Today's Consultations</p>
                    </div>
                    
                <?php elseif($role == 'pharmacy'): ?>
                    <div class="stat-card pharmacy">
                        <h3><?php echo $stats['pending_prescriptions'] ?? 0; ?></h3>
                        <p>Pending Prescriptions</p>
                    </div>
                    <div class="stat-card pharmacy">
                        <h3><?php echo $stats['today_dispensed'] ?? 0; ?></h3>
                        <p>Today Dispensed</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Quick Actions -->
            <div class="quick-actions">
                <?php if(in_array($role, ['reception', 'admin'])): ?>
                    <button class="action-btn" onclick="window.location.href='register_patient.php'">
                        👤 Register New Patient
                    </button>
                <?php endif; ?>
                
                <?php if($role == 'doctor'): ?>
                    <button class="action-btn" onclick="window.location.href='consultant_dashboard.php'">
                        🩺 Start Consultation
                    </button>
                <?php endif; ?>
                
                <?php if($role == 'pharmacy'): ?>
                    <button class="action-btn" onclick="window.location.href='pharmacy_dashboard.php'">
                        💊 View Prescriptions
                    </button>
                <?php endif; ?>
                
                <button class="action-btn" onclick="window.location.href='patient_profile.php'">
                    📁 Search Patient
                </button>
                
                <?php if($role == 'admin'): ?>
                    <button class="action-btn" onclick="window.location.href='admin_dashboard.php'">
                        ⚙️ Manage Staff
                    </button>
                <?php endif; ?>
            </div>
            
            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Recent Activity</h2>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Activity</th>
                                <th>Details</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($recent_activities)): ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; padding: 20px;">
                                        No recent activity to display
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($recent_activities as $activity): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($activity['activity']); ?></td>
                                        <td><?php echo htmlspecialchars($activity['details']); ?></td>
                                        <td><?php echo date('H:i', strtotime($activity['timestamp'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- System Status -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">System Status</h2>
                </div>
                <div class="status-grid">
                    <div class="status-item online">
                        <span class="status-dot"></span>
                        <span>Database: Online</span>
                    </div>
                    <div class="status-item online">
                        <span class="status-dot"></span>
                        <span>Server: Running</span>
                    </div>
                    <div class="status-item online">
                        <span class="status-dot"></span>
                        <span>Security: Active</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-refresh dashboard every 60 seconds
        setTimeout(function() {
            location.reload();
        }, 60000);
        
        // Update time every minute
        function updateTime() {
            const now = new Date();
            document.querySelector('.user-info p').textContent = 
                now.toLocaleDateString('en-GB', { 
                    day: '2-digit', 
                    month: 'short', 
                    year: 'numeric' 
                }) + ', ' + 
                now.toLocaleTimeString('en-GB', { 
                    hour: '2-digit', 
                    minute: '2-digit' 
                });
        }
        
        setInterval(updateTime, 60000);
    </script>
</body>
</html>