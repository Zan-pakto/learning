<?php
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'learn';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get submission ID from URL
$submission_id = $_GET['id'] ?? null;

if (!$submission_id) {
    header('Location: submissions.php');
    exit();
}

// Verify the submission belongs to the user
$stmt = $conn->prepare("SELECT id FROM submissions WHERE id = ? AND user_id = ?");
$stmt->execute([$submission_id, $user_id]);
$submission = $stmt->fetch();

if (!$submission) {
    header('Location: submissions.php');
    exit();
}

try {
    // Delete the submission (reviews will be automatically deleted due to ON DELETE CASCADE)
    $stmt = $conn->prepare("DELETE FROM submissions WHERE id = ? AND user_id = ?");
    $stmt->execute([$submission_id, $user_id]);
    
    header('Location: submissions.php?success=deleted');
} catch(PDOException $e) {
    header('Location: submissions.php?error=delete_failed');
}
exit(); 