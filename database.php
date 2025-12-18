<!-- File: database.php -->
<?php
$host = 'sql301.infinityfree.com';
$dbname = 'if0_40567311_clinic';
$username = 'if0_40567311'; 
$password = 'jsixxNNg0K6iMY'; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>