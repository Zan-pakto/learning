<?php
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'learn';
$username = 'root';
$password = '';

try {
    // Connect to the database
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submission_id'])) {
    $submission_id = $_POST['submission_id'];
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : null;
    $comment = $_POST['comment'] ?? '';
    
    if ($rating === null || $rating < 1 || $rating > 5) {
        $error = "Please select a valid rating (1-5 stars)";
    } else {
        try {
            // First check if the submission exists
            $check_stmt = $conn->prepare("SELECT id, user_id FROM submissions WHERE id = ?");
            $check_stmt->execute([$submission_id]);
            $submission = $check_stmt->fetch();
            
            if (!$submission) {
                $error = "Invalid submission";
            } else if ($submission['user_id'] == $user_id) {
                $error = "You cannot review your own submission";
            } else {
                // Check if user has already reviewed this submission
                $stmt = $conn->prepare("SELECT id FROM reviews WHERE submission_id = ? AND reviewer_id = ?");
                $stmt->execute([$submission_id, $user_id]);
                $existing_review = $stmt->fetch();
                
                if ($existing_review) {
                    // Update existing review
                    $stmt = $conn->prepare("UPDATE reviews SET rating = ?, comment = ?, created_at = CURRENT_TIMESTAMP WHERE submission_id = ? AND reviewer_id = ?");
                    $stmt->execute([$rating, $comment, $submission_id, $user_id]);
                    $success = "Review updated successfully!";
                } else {
                    // Create new review
                    $stmt = $conn->prepare("INSERT INTO reviews (submission_id, reviewer_id, rating, comment) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$submission_id, $user_id, $rating, $comment]);
                    $success = "Review submitted successfully!";
                }
            }
        } catch(PDOException $e) {
            $error = "Error submitting review: " . $e->getMessage();
        }
    }
}

// Get submissions that need review (excluding user's own submissions)
$stmt = $conn->prepare("
    SELECT s.*, u.name as author_name, 
           (SELECT COUNT(*) FROM reviews WHERE submission_id = s.id) as review_count,
           (SELECT AVG(rating) FROM reviews WHERE submission_id = s.id) as avg_rating,
           (SELECT id FROM reviews WHERE submission_id = s.id AND reviewer_id = ?) as user_review_id,
           (SELECT rating FROM reviews WHERE submission_id = s.id AND reviewer_id = ?) as user_rating,
           (SELECT comment FROM reviews WHERE submission_id = s.id AND reviewer_id = ?) as user_comment
    FROM submissions s
    JOIN users u ON s.user_id = u.id
    WHERE s.user_id != ?
    ORDER BY s.created_at DESC
");
$stmt->execute([$user_id, $user_id, $user_id, $user_id]);
$submissions = $stmt->fetchAll();

// Get all reviews for each submission
foreach ($submissions as &$submission) {
    $stmt = $conn->prepare("
        SELECT r.*, u.name as reviewer_name 
        FROM reviews r 
        JOIN users u ON r.reviewer_id = u.id 
        WHERE r.submission_id = ? 
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$submission['id']]);
    $submission['reviews'] = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Submissions - Peer Review System</title>
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
        .pattern-bg {
            background-color: #f3f4f6;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%239C92AC' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        .rating-text {
            font-size: 0.875rem;
            color: #6B7280;
            margin-top: 0.5rem;
            transition: all 0.3s ease;
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
            width: 20px;
            height: 20px;
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
        .author-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .star-rating {
            display: inline-flex;
            align-items: center;
        }
        .star-rating .star {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .star-rating .star:hover {
            transform: scale(1.2);
        }
        .review-form {
            border-top: 1px solid #e5e7eb;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
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
    </style>
</head>
<body class="pattern-bg min-h-screen">
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
                    <a href="submissions.php" class="nav-link text-gray-700 hover:text-indigo-600 transition-colors duration-300 flex items-center">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        My Submissions
                    </a>
                    <a href="reviews.php" class="nav-link text-indigo-600 font-medium transition-colors duration-300 flex items-center">
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
        <div class="page-header fade-in">
            <div class="max-w-7xl mx-auto px-4">
                <h1 class="text-3xl font-bold">Review Submissions</h1>
                <p class="mt-2 text-indigo-100">Provide feedback and ratings for other users' submissions</p>
            </div>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4 animate__animated animate__fadeIn">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if (count($submissions) === 0): ?>
            <div class="empty-state glass-effect rounded-lg p-8 fade-in">
                <div class="empty-state-icon">📝</div>
                <h3 class="text-xl font-semibold text-gray-700">No submissions to review</h3>
                <p class="mt-2 text-gray-500">There are currently no submissions available for review.</p>
                <a href="submissions.php" class="mt-4 inline-block bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors duration-300 btn-hover">
                    Create a Submission
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 gap-6">
                <?php foreach ($submissions as $submission): ?>
                    <div class="submission-card glass-effect p-6 shadow-md fade-in">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-xl font-semibold text-gray-900"><?php echo htmlspecialchars($submission['title']); ?></h3>
                                <div class="mt-2 flex items-center space-x-2">
                                    <span class="author-badge">
                                        <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                        <?php echo htmlspecialchars($submission['author_name']); ?>
                                    </span>
                                    <span class="text-sm text-gray-500">•</span>
                                    <span class="text-sm text-gray-500"><?php echo date('M d, Y', strtotime($submission['created_at'])); ?></span>
                                    <span class="text-sm text-gray-500">•</span>
                                    <span class="text-sm text-gray-500"><?php echo $submission['review_count']; ?> reviews</span>
                                    <?php if ($submission['avg_rating']): ?>
                                        <span class="text-sm text-gray-500">•</span>
                                        <div class="star-rating">
                                            <?php for($i = 1; $i <= 5; $i++): ?>
                                                <span class="text-yellow-400"><?php echo $i <= round($submission['avg_rating']) ? '★' : '☆'; ?></span>
                                            <?php endfor; ?>
                                            <span class="ml-1 text-sm text-gray-500">(<?php echo number_format($submission['avg_rating'], 1); ?>)</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
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
                        
                        <div class="review-form">
                            <h4 class="text-lg font-medium text-gray-900 mb-4">Your Review</h4>
                            <form method="POST" class="space-y-4">
                                <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
                                    <div class="flex space-x-2">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <label class="cursor-pointer">
                                                <input type="radio" name="rating" value="<?php echo $i; ?>" 
                                                       <?php echo ($submission['user_rating'] == $i) ? 'checked' : ''; ?>
                                                       class="hidden star-rating" data-submission-id="<?php echo $submission['id']; ?>">
                                                <span class="text-2xl <?php echo ($submission['user_rating'] >= $i) ? 'text-yellow-400' : 'text-gray-300'; ?> hover:text-yellow-400 transition-colors star-icon star" data-rating="<?php echo $i; ?>" data-submission-id="<?php echo $submission['id']; ?>">
                                                    ★
                                                </span>
                                            </label>
                                        <?php endfor; ?>
                                    </div>
                                    <div id="rating-text-<?php echo $submission['id']; ?>" class="rating-text">
                                        <?php if ($submission['user_rating']): ?>
                                            You rated this submission <?php echo $submission['user_rating']; ?> out of 5 stars
                                        <?php else: ?>
                                            Click on a star to rate
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div>
                                    <label for="comment_<?php echo $submission['id']; ?>" class="block text-sm font-medium text-gray-700">Comment</label>
                                    <textarea id="comment_<?php echo $submission['id']; ?>" name="comment" rows="3" 
                                              class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 input-focus"><?php echo htmlspecialchars($submission['user_comment'] ?? ''); ?></textarea>
                                </div>
                                
                                <div>
                                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors duration-300 btn-hover">
                                        <?php echo $submission['user_review_id'] ? 'Update Review' : 'Submit Review'; ?>
                                    </button>
                                </div>
                            </form>
                        </div>

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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get all star icons
            const starIcons = document.querySelectorAll('.star-icon');
            
            // Add event listeners to each star icon
            starIcons.forEach(star => {
                star.addEventListener('click', function() {
                    const rating = this.getAttribute('data-rating');
                    const submissionId = this.getAttribute('data-submission-id');
                    const ratingText = document.getElementById(`rating-text-${submissionId}`);
                    
                    // Update the rating text
                    ratingText.textContent = `You rated this submission ${rating} out of 5 stars`;
                    
                    // Update the radio button
                    const radioButton = document.querySelector(`input[data-submission-id="${submissionId}"][value="${rating}"]`);
                    if (radioButton) {
                        radioButton.checked = true;
                    }
                    
                    // Update star colors
                    const stars = document.querySelectorAll(`.star-icon[data-submission-id="${submissionId}"]`);
                    stars.forEach(s => {
                        const starRating = s.getAttribute('data-rating');
                        if (starRating <= rating) {
                            s.classList.remove('text-gray-300');
                            s.classList.add('text-yellow-400');
                        } else {
                            s.classList.remove('text-yellow-400');
                            s.classList.add('text-gray-300');
                        }
                    });
                });
                
                // Add hover effect
                star.addEventListener('mouseenter', function() {
                    const rating = this.getAttribute('data-rating');
                    const submissionId = this.getAttribute('data-submission-id');
                    const stars = document.querySelectorAll(`.star-icon[data-submission-id="${submissionId}"]`);
                    
                    stars.forEach(s => {
                        const starRating = s.getAttribute('data-rating');
                        if (starRating <= rating) {
                            s.classList.remove('text-gray-300');
                            s.classList.add('text-yellow-400');
                        }
                    });
                });
                
                star.addEventListener('mouseleave', function() {
                    const submissionId = this.getAttribute('data-submission-id');
                    const stars = document.querySelectorAll(`.star-icon[data-submission-id="${submissionId}"]`);
                    const selectedRating = document.querySelector(`input[data-submission-id="${submissionId}"]:checked`);
                    
                    if (selectedRating) {
                        const rating = selectedRating.value;
                        stars.forEach(s => {
                            const starRating = s.getAttribute('data-rating');
                            if (starRating <= rating) {
                                s.classList.remove('text-gray-300');
                                s.classList.add('text-yellow-400');
                            } else {
                                s.classList.remove('text-yellow-400');
                                s.classList.add('text-gray-300');
                            }
                        });
                    } else {
                        stars.forEach(s => {
                            s.classList.remove('text-yellow-400');
                            s.classList.add('text-gray-300');
                        });
                    }
                });
            });
        });
    </script>
</body>
</html> 