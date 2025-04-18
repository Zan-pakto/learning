<?php
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'learn';
$username = 'root';
$password = '';

try {
    // First connect without database
    $conn = new PDO("mysql:host=$host", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $conn->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    
    // Connect to the database
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create users table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100),
        bio TEXT,
        profile_picture VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    
    // Create submissions table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS submissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        file_path VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    
    // Create reviews table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        submission_id INT NOT NULL,
        reviewer_id INT NOT NULL,
        rating INT NOT NULL,
        comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE CASCADE,
        FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle success/error messages from delete operation
if (isset($_GET['success']) && $_GET['success'] === 'deleted') {
    $success = 'Submission deleted successfully!';
}
if (isset($_GET['error']) && $_GET['error'] === 'delete_failed') {
    $error = 'Failed to delete submission. Please try again.';
}

// Handle new submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'submit') {
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $file_path = null;
        
        // Handle file upload
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['pdf', 'doc', 'docx', 'txt'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $file_name = uniqid() . '_' . $_FILES['file']['name'];
                $target_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['file']['tmp_name'], $target_path)) {
                    $file_path = $target_path;
                } else {
                    $error = 'Error uploading file. Please try again.';
                }
            } else {
                $error = 'Invalid file type. Allowed types: PDF, DOC, DOCX, TXT';
            }
        }
        
        if (empty($title) || empty($content)) {
            $error = 'Please fill in all fields';
        } else {
            try {
                $stmt = $conn->prepare("INSERT INTO submissions (user_id, title, content, file_path) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $title, $content, $file_path]);
                $_SESSION['success'] = 'Submission added successfully!';
                header('Location: submissions.php');
                exit();
            } catch(PDOException $e) {
                $error = 'Error adding submission: ' . $e->getMessage();
            }
        }
    } elseif ($_POST['action'] === 'review') {
        $submission_id = $_POST['submission_id'] ?? '';
        $rating = $_POST['rating'] ?? '';
        $comment = $_POST['comment'] ?? '';
        
        if (empty($submission_id) || empty($rating) || empty($comment)) {
            $error = 'Please fill in all review fields';
        } else {
            try {
                $stmt = $conn->prepare("INSERT INTO reviews (submission_id, reviewer_id, rating, comment) VALUES (?, ?, ?, ?)");
                $stmt->execute([$submission_id, $user_id, $rating, $comment]);
                $success = 'Review added successfully!';
            } catch(PDOException $e) {
                $error = 'Error adding review: ' . $e->getMessage();
            }
        }
    }
}

// Get success message from session if it exists
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// Get user's submissions
$stmt = $conn->prepare("
    SELECT s.*, 
           (SELECT COUNT(*) FROM reviews WHERE submission_id = s.id) as review_count,
           (SELECT AVG(rating) FROM reviews WHERE submission_id = s.id) as avg_rating,
           (SELECT COUNT(*) FROM reviews WHERE submission_id = s.id AND reviewer_id = ?) as has_reviewed
    FROM submissions s
    WHERE s.user_id = ?
    ORDER BY s.created_at DESC
");
$stmt->execute([$user_id, $user_id]);
$my_submissions = $stmt->fetchAll();

// Get other users' submissions for review
$stmt = $conn->prepare("
    SELECT s.*, u.name as author_name,
           (SELECT COUNT(*) FROM reviews WHERE submission_id = s.id) as review_count,
           (SELECT AVG(rating) FROM reviews WHERE submission_id = s.id) as avg_rating,
           (SELECT COUNT(*) FROM reviews WHERE submission_id = s.id AND reviewer_id = ?) as has_reviewed
    FROM submissions s
    JOIN users u ON s.user_id = u.id
    WHERE s.user_id != ?
    ORDER BY s.created_at DESC
");
$stmt->execute([$user_id, $user_id]);
$other_submissions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submissions - Peer Review System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <style>
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .slide-in {
            animation: slideIn 0.5s ease-out;
        }
        @keyframes slideIn {
            from { transform: translateX(-100%); }
            to { transform: translateX(0); }
        }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .input-focus {
            transition: all 0.3s ease;
        }
        .input-focus:focus {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .btn-hover {
            transition: all 0.3s ease;
        }
        .btn-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .pattern-bg {
            background-color: #f3f4f6;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%239C92AC' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        @keyframes floating {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        .nav-link {
            position: relative;
            transition: all 0.3s ease;
        }
        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -2px;
            left: 0;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transition: width 0.3s ease;
        }
        .nav-link:hover::after {
            width: 100%;
        }
        .nav-icon {
            width: 16px;
            height: 16px;
            margin-right: 6px;
            vertical-align: middle;
        }
        .logo-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .submission-card {
            transition: all 0.3s ease;
            border-radius: 1rem;
            overflow: hidden;
        }
        .submission-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .stats-badge {
            background: rgba(99, 102, 241, 0.1);
            color: #4F46E5;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
        }
        .stats-badge svg {
            width: 14px;
            height: 14px;
            margin-right: 4px;
        }
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 1rem 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .empty-state {
            text-align: center;
            padding: 3rem 0;
        }
        .empty-state-icon {
            font-size: 4rem;
            color: #9CA3AF;
            margin-bottom: 1rem;
        }
        .file-upload {
            border: 2px dashed #E5E7EB;
            border-radius: 0.5rem;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .file-upload:hover {
            border-color: #4F46E5;
            background-color: rgba(79, 70, 229, 0.05);
        }
        .file-upload-icon {
            font-size: 2rem;
            color: #9CA3AF;
            margin-bottom: 0.5rem;
        }
        .success-message {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #047857;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }
        .success-message svg {
            width: 20px;
            height: 20px;
            margin-right: 0.75rem;
            color: #059669;
        }
        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #B91C1C;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }
        .error-message svg {
            width: 20px;
            height: 20px;
            margin-right: 0.75rem;
            color: #DC2626;
        }
        .delete-btn {
            color: #EF4444;
            transition: all 0.3s ease;
        }
        .delete-btn:hover {
            color: #B91C1C;
            transform: scale(1.1);
        }
        .submit-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="pattern-bg min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="flex items-center">
                        <svg class="h-8 w-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="ml-2 text-2xl font-bold logo-text">PeerReview</span>
                    </a>
                </div>
                <div class="flex items-center space-x-6">
                    <a href="submissions.php" class="nav-link text-indigo-600 font-medium transition-colors duration-300 flex items-center">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        My Submissions
                    </a>
                    <a href="reviews.php" class="nav-link text-gray-700 hover:text-indigo-600 transition-colors duration-300 flex items-center">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                        </svg>
                        Review Submissions
                    </a>
                    <a href="profile.php" class="nav-link text-gray-700 hover:text-indigo-600 transition-colors duration-300 flex items-center">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        Profile
                    </a>
                    <a href="index.php?action=logout" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition-colors duration-300 flex items-center">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 py-8">
        <div class="page-header fade-in">
            <div class="max-w-7xl mx-auto px-4">
                <h1 class="text-3xl font-bold">My Submissions</h1>
                <p class="mt-2 text-indigo-100">Create and manage your submissions for peer review</p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <?php if (count($my_submissions) === 0): ?>
                    <div class="empty-state glass-effect rounded-lg p-8 fade-in">
                        <div class="empty-state-icon">📝</div>
                        <h3 class="text-xl font-semibold text-gray-700">No submissions yet</h3>
                        <p class="mt-2 text-gray-500">Create your first submission to get started with peer review.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-6">
                        <?php foreach ($my_submissions as $submission): ?>
                            <div class="submission-card glass-effect p-6 shadow-md fade-in">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="text-xl font-semibold text-gray-900"><?php echo htmlspecialchars($submission['title']); ?></h3>
                                        <div class="mt-2 flex items-center space-x-2">
                                            <span class="text-sm text-gray-500"><?php echo date('M d, Y', strtotime($submission['created_at'])); ?></span>
                                            <span class="text-sm text-gray-500">•</span>
                                            <span class="stats-badge">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                                                </svg>
                                                <?php echo $submission['review_count']; ?> reviews
                                            </span>
                                            <?php if ($submission['avg_rating']): ?>
                                                <span class="stats-badge">
                                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path>
                                                    </svg>
                                                    <?php echo number_format($submission['avg_rating'], 1); ?> avg rating
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="flex space-x-2">
                                        <a href="edit_submission.php?id=<?php echo $submission['id']; ?>" class="text-indigo-600 hover:text-indigo-800 transition-colors duration-300">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </a>
                                        <a href="delete_submission.php?id=<?php echo $submission['id']; ?>" class="text-red-600 hover:text-red-800 transition-colors duration-300" onclick="return confirm('Are you sure you want to delete this submission?');">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="mt-4 prose max-w-none text-gray-600">
                                    <?php echo nl2br(htmlspecialchars($submission['content'])); ?>
                                </div>
                                
                                <?php if ($submission['file_path']): ?>
                                    <div class="mt-4">
                                        <a href="<?php echo htmlspecialchars($submission['file_path']); ?>" target="_blank" class="inline-flex items-center text-indigo-600 hover:text-indigo-800 transition-colors duration-300">
                                            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            View Attachment
                                        </a>
                                    </div>
                                <?php endif; ?>

                                <?php if ($submission['review_count'] > 0): ?>
                                    <div class="mt-6 border-t border-gray-200 pt-6">
                                        <h4 class="text-lg font-medium text-gray-900 mb-4">Feedback Comments</h4>
                                        <div class="space-y-4">
                                            <?php 
                                            $review_stmt = $conn->prepare("
                                                SELECT r.*, u.name as reviewer_name 
                                                FROM reviews r 
                                                JOIN users u ON r.reviewer_id = u.id 
                                                WHERE r.submission_id = ? 
                                                ORDER BY r.created_at DESC
                                            ");
                                            $review_stmt->execute([$submission['id']]);
                                            $reviews = $review_stmt->fetchAll(PDO::FETCH_ASSOC);
                                            
                                            if (!empty($reviews)): 
                                                foreach ($reviews as $review): 
                                            ?>
                                                <div class="bg-gray-50 rounded-lg p-4">
                                                    <div class="flex items-center justify-between mb-2">
                                                        <div class="flex items-center">
                                                            <span class="font-medium text-gray-900">Anonymous</span>
                                                            <div class="ml-2 flex">
                                                                <?php for($i = 1; $i <= 5; $i++): ?>
                                                                    <span class="text-yellow-400"><?php echo $i <= $review['rating'] ? '★' : '☆'; ?></span>
                                                                <?php endfor; ?>
                                                            </div>
                                                        </div>
                                                        <span class="text-sm text-gray-500"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                                                    </div>
                                                    <p class="text-gray-600"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                                </div>
                                            <?php 
                                                endforeach; 
                                            else: 
                                            ?>
                                                <p class="text-gray-500">No reviews yet.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="lg:col-span-1">
                <div class="glass-effect p-6 rounded-lg shadow-md fade-in">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Create New Submission</h2>
                    <form method="POST" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="action" value="submit">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
                            <input type="text" id="title" name="title" required
                                   class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 input-focus">
                        </div>
                        
                        <div>
                            <label for="content" class="block text-sm font-medium text-gray-700">Content</label>
                            <textarea id="content" name="content" rows="5" required
                                      class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 input-focus"></textarea>
                        </div>
                        
                        <div>
                            <label for="file" class="block text-sm font-medium text-gray-700">Attachment (Optional)</label>
                            <div class="file-upload mt-1" onclick="document.getElementById('file').click()">
                                <div class="file-upload-icon">📎</div>
                                <p class="text-sm text-gray-500">Click to upload a file</p>
                                <p class="text-xs text-gray-400 mt-1">PDF, DOC, DOCX, TXT (max 5MB)</p>
                                <input type="file" id="file" name="file" class="hidden" accept=".pdf,.doc,.docx,.txt">
                            </div>
                            <p id="file-name" class="mt-2 text-sm text-gray-500"></p>
                        </div>
                        
                        <div>
                            <button type="submit" class="w-full bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors duration-300 btn-hover">
                                Submit
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
        // File upload display
        document.getElementById('file').addEventListener('change', function() {
            const fileName = this.files[0] ? this.files[0].name : '';
            document.getElementById('file-name').textContent = fileName ? `Selected file: ${fileName}` : '';
        });
    </script>
</body>
</html> 