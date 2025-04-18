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
$success = '';
$error = '';

// Get submission ID from URL
$submission_id = $_GET['id'] ?? null;

if (!$submission_id) {
    header('Location: submissions.php');
    exit();
}

// Get submission details
$stmt = $conn->prepare("SELECT * FROM submissions WHERE id = ? AND user_id = ?");
$stmt->execute([$submission_id, $user_id]);
$submission = $stmt->fetch();

if (!$submission) {
    header('Location: submissions.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    
    if (empty($title) || empty($content)) {
        $error = 'Please fill in all fields';
    } else {
        try {
            $stmt = $conn->prepare("UPDATE submissions SET title = ?, content = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$title, $content, $submission_id, $user_id]);
            $success = 'Submission updated successfully!';
            
            // Refresh submission data
            $stmt = $conn->prepare("SELECT * FROM submissions WHERE id = ? AND user_id = ?");
            $stmt->execute([$submission_id, $user_id]);
            $submission = $stmt->fetch();
        } catch(PDOException $e) {
            $error = 'Error updating submission: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Submission - Peer Review System</title>
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
<body class="bg-gray-100 min-h-screen">
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
                    <a href="submissions.php" class="text-indigo-600 font-medium transition-colors duration-300 flex items-center">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        My Submissions
                    </a>
                    <a href="reviews.php" class="text-gray-700 hover:text-indigo-600 transition-colors duration-300 flex items-center">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                        </svg>
                        Review Submissions
                    </a>
                    <a href="profile.php" class="text-gray-700 hover:text-indigo-600 transition-colors duration-300 flex items-center">
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
    <main class="max-w-4xl mx-auto px-4 py-8">
        <div class="page-header gradient-bg text-white p-6 rounded-lg shadow-lg mb-6 fade-in">
            <h1 class="text-3xl font-bold">Edit Submission</h1>
            <p class="mt-2 text-indigo-100">Update your submission details</p>
        </div>

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4 animate__animated animate__fadeIn">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 animate__animated animate__fadeIn">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="glass-effect p-6 rounded-lg shadow-md fade-in">
            <form method="POST" class="space-y-4">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
                    <input type="text" id="title" name="title" required
                           value="<?php echo htmlspecialchars($submission['title']); ?>"
                           class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 input-focus">
                </div>
                
                <div>
                    <label for="content" class="block text-sm font-medium text-gray-700">Content</label>
                    <textarea id="content" name="content" rows="5" required
                              class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 input-focus"><?php echo htmlspecialchars($submission['content']); ?></textarea>
                </div>
                
                <div class="flex space-x-4">
                    <button type="submit" class="flex-1 bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors duration-300 btn-hover">
                        Update Submission
                    </button>
                    <a href="submissions.php" class="flex-1 bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors duration-300 btn-hover text-center">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </main>
</body>
</html> 