<!-- File: register_patient.php -->
<?php
session_start();
require_once 'includes/auth_check.php';
require_once 'database.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Generate patient ID
        $lastPatient = $pdo->query("SELECT patient_id FROM patients ORDER BY patient_id DESC LIMIT 1")->fetch();
        if($lastPatient) {
            $lastNum = intval(substr($lastPatient['patient_id'], 6));
            $patient_id = 'METRO-' . str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $patient_id = 'METRO-0001';
        }
        
        // Insert patient
        $stmt = $pdo->prepare("INSERT INTO patients (patient_id, first_name, last_name, dob, gender, phone, address, email, emergency_contact, blood_group, allergies, created_by) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $patient_id,
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['dob'],
            $_POST['gender'],
            $_POST['phone'],
            $_POST['address'],
            $_POST['email'],
            $_POST['emergency_contact'],
            $_POST['blood_group'],
            $_POST['allergies'],
            $_SESSION['staff_id']
        ]);
        
        $success = "Patient registered successfully! Patient ID: <strong>$patient_id</strong>";
        
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Patient - Metro Clinic HMS</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="dashboard">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header">
                <h1>Register New Patient</h1>
            </div>
            
            <?php if(isset($success)): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <form method="POST" id="patient-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Patient ID (Auto-generated)</label>
                            <input type="text" value="<?php echo isset($patient_id) ? $patient_id : ''; ?>" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label>First Name *</label>
                            <input type="text" name="first_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Last Name *</label>
                            <input type="text" name="last_name" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Date of Birth *</label>
                            <input type="date" name="dob" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Gender *</label>
                            <select name="gender" required>
                                <option value="">Select</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Phone Number *</label>
                            <input type="tel" name="phone" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" rows="3"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email">
                        </div>
                        
                        <div class="form-group">
                            <label>Emergency Contact</label>
                            <input type="tel" name="emergency_contact">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Blood Group</label>
                            <select name="blood_group">
                                <option value="">Select</option>
                                <option value="A+">A+</option>
                                <option value="A-">A-</option>
                                <option value="B+">B+</option>
                                <option value="B-">B-</option>
                                <option value="O+">O+</option>
                                <option value="O-">O-</option>
                                <option value="AB+">AB+</option>
                                <option value="AB-">AB-</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Allergies</label>
                            <input type="text" name="allergies" placeholder="List any allergies">
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Register Patient</button>
                        <button type="reset" class="btn-secondary">Clear Form</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="js/script.js"></script>
</body>
</html>