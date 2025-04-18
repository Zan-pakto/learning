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

// Create uploads directory if it doesn't exist
$uploads_dir = 'uploads/profile_pictures';
if (!file_exists($uploads_dir)) {
    mkdir($uploads_dir, 0777, true);
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $bio = $_POST['bio'] ?? '';
    
    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_picture']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_filename = uniqid() . '.' . $ext;
            $upload_path = $uploads_dir . '/' . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                $stmt->execute([$upload_path, $user_id]);
            }
        }
    }
    
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, bio = ? WHERE id = ?");
    $stmt->execute([$full_name, $bio, $user_id]);
    $success = "Profile updated successfully!";
}

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get user's submissions count
$stmt = $conn->prepare("SELECT COUNT(*) FROM submissions WHERE user_id = ?");
$stmt->execute([$user_id]);
$submissions_count = $stmt->fetchColumn();

// Get user's reviews count
$stmt = $conn->prepare("SELECT COUNT(*) FROM reviews WHERE reviewer_id = ?");
$stmt->execute([$user_id]);
$reviews_count = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Peer Review System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <style>
        .profile-container {
            animation: fadeIn 0.5s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
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
    </style>
</head>
<body class="pattern-bg min-h-screen">
    <nav class="bg-white shadow-lg">
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
                    <a href="submissions.php" class="nav-link text-gray-700 hover:text-indigo-600 transition-colors duration-300 flex items-center">
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

    <main class="max-w-7xl mx-auto px-4 py-8">
        <div class="profile-container bg-white rounded-xl shadow-lg p-8">
            <?php if (isset($success)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4 animate__animated animate__fadeIn">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <div class="flex flex-col md:flex-row gap-8">
                <div class="md:w-1/3">
                    <div class="flex flex-col items-center mb-8">
                        <div class="relative group">
                            <div class="w-40 h-40 rounded-full overflow-hidden border-4 border-indigo-500 shadow-lg transition-transform duration-300 group-hover:scale-105">
                                <?php if (!empty($user['profile_picture'])): ?>
                                    <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                                         alt="Profile Picture" 
                                         class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full gradient-bg flex items-center justify-center">
                                        <span class="text-5xl text-white font-bold">
                                            <?php echo strtoupper(substr($user['name'] ?? '', 0, 1)); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <form action="profile.php" method="post" enctype="multipart/form-data" class="absolute bottom-0 right-0">
                                <label for="profile_picture" class="cursor-pointer">
                                    <div class="bg-indigo-600 text-white p-3 rounded-full shadow-lg hover:bg-indigo-700 transition-colors duration-300">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                    </div>
                                </label>
                                <input type="file" 
                                       id="profile_picture" 
                                       name="profile_picture" 
                                       accept="image/*" 
                                       class="hidden"
                                       onchange="this.form.submit()">
                            </form>
                        </div>
                        <h2 class="mt-4 text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($user['full_name'] ?? $user['name'] ?? ''); ?></h2>
                        <p class="text-gray-600"><?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
                    </div>

                    <div class="mt-8 grid grid-cols-2 gap-4 text-center">
                        <div class="glass-effect p-4 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300">
                            <div class="text-3xl font-bold text-indigo-600"><?php echo $submissions_count; ?></div>
                            <div class="text-gray-600">Submissions</div>
                        </div>
                        <div class="glass-effect p-4 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300">
                            <div class="text-3xl font-bold text-indigo-600"><?php echo $reviews_count; ?></div>
                            <div class="text-gray-600">Reviews</div>
                        </div>
                    </div>
                </div>

                <div class="md:w-2/3">
                    <form method="POST" class="space-y-6">
                        <div>
                            <label for="full_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                            <input type="text" id="full_name" name="full_name" 
                                   value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>"
                                   class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 input-focus">
                        </div>

                        <div>
                            <label for="bio" class="block text-sm font-medium text-gray-700">Bio</label>
                            <textarea id="bio" name="bio" rows="4"
                                class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 input-focus"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        </div>

                        <div>
                            <button type="submit"
                                class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white gradient-bg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200 btn-hover">
                                Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white mt-16">
        <div class="max-w-7xl mx-auto px-4 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="space-y-4">
                    <h3 class="text-2xl font-bold">PeerReview</h3>
                    <p class="text-indigo-100">Empowering collaboration through peer review and feedback.</p>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="/" class="text-indigo-100 hover:text-white transition-colors">Home</a></li>
                        <li><a href="/submissions.php" class="text-indigo-100 hover:text-white transition-colors">Submissions</a></li>
                        <li><a href="/profile.php" class="text-indigo-100 hover:text-white transition-colors">Profile</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Resources</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-indigo-100 hover:text-white transition-colors">Guidelines</a></li>
                        <li><a href="#" class="text-indigo-100 hover:text-white transition-colors">FAQ</a></li>
                        <li><a href="#" class="text-indigo-100 hover:text-white transition-colors">Support</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Connect</h4>
                    <div class="flex space-x-4">
                        <a href="#" class="text-indigo-100 hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84"></path>
                            </svg>
                        </a>
                        <a href="#" class="text-indigo-100 hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd"></path>
                            </svg>
                        </a>
                        <a href="#" class="text-indigo-100 hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill-rule="evenodd" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10c5.51 0 10-4.48 10-10S17.51 2 12 2zm6.605 4.61a8.502 8.502 0 011.93 5.314c-.281-.054-3.101-.629-5.943-.271-.065-.141-.12-.293-.184-.445a25.416 25.416 0 00-.564-1.236c3.145-1.28 4.577-3.124 4.761-3.362zM12 3.475c2.17 0 4.154.813 5.662 2.148-.152.216-1.443 1.941-4.48 3.08-1.399-2.57-2.95-4.675-3.189-5A8.687 8.687 0 0112 3.475zm-3.633.803a53.896 53.896 0 013.167 4.935c-3.992 1.063-7.517 1.04-7.896 1.04a8.581 8.581 0 014.729-5.975zM3.453 12.01v-.26c.37.01 4.512.065 8.775-1.215.25.477.477.965.694 1.453-.109.033-.228.065-.336.098-4.404 1.42-6.747 5.303-6.942 5.629a8.522 8.522 0 01-2.19-5.705zM12 20.547a8.482 8.482 0 01-5.239-1.8c.152-.315 1.888-3.656 6.703-5.337.022-.01.033-.01.054-.022a35.318 35.318 0 011.823 6.475 8.4 8.4 0 01-3.341.684zm4.761-1.465c-.086-.52-.542-3.015-1.659-6.084 2.679-.423 5.022.271 5.314.369a8.468 8.468 0 01-3.655 5.715z" clip-rule="evenodd"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
            <div class="mt-8 pt-8 border-t border-indigo-500">
                <p class="text-center text-indigo-100">© 2024 PeerReview. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html> 