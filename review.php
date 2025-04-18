<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$submission_id = $_GET['id'] ?? null;

if (!$submission_id) {
    header('Location: submissions.php');
    exit();
}

// Get submission details
$stmt = $conn->prepare("
    SELECT s.*, u.username, u.full_name
    FROM submissions s
    JOIN users u ON s.user_id = u.id
    WHERE s.id = ?
");
$stmt->execute([$submission_id]);
$submission = $stmt->fetch();

if (!$submission) {
    header('Location: submissions.php');
    exit();
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = $_POST['rating'];
    $feedback = $_POST['feedback'];
    $is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;
    
    $stmt = $conn->prepare("INSERT INTO reviews (submission_id, reviewer_id, rating, feedback, is_anonymous) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$submission_id, $user_id, $rating, $feedback, $is_anonymous]);
    
    header('Location: submissions.php');
    exit();
}

// Get existing reviews
$stmt = $conn->prepare("
    SELECT r.*, u.username, u.full_name
    FROM reviews r
    JOIN users u ON r.reviewer_id = u.id
    WHERE r.submission_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$submission_id]);
$reviews = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Submission - Peer Review System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <style>
        .review-container {
            animation: fadeIn 0.5s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .star-rating {
            display: inline-flex;
            flex-direction: row-reverse;
        }
        .star-rating input {
            display: none;
        }
        .star-rating label {
            cursor: pointer;
            font-size: 1.5rem;
            color: #ddd;
            transition: color 0.2s;
        }
        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input:checked ~ label {
            color: #fbbf24;
        }
    </style>
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="text-2xl font-bold text-indigo-600">PeerReview</a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="submissions.php" class="text-gray-700 hover:text-indigo-600">Back to Submissions</a>
                    <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 py-8">
        <div class="review-container">
            <!-- Submission Details -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                <h2 class="text-2xl font-bold mb-4"><?php echo htmlspecialchars($submission['title']); ?></h2>
                <p class="text-gray-600 mb-4">By <?php echo htmlspecialchars($submission['full_name']); ?></p>
                <div class="prose max-w-none">
                    <?php echo nl2br(htmlspecialchars($submission['content'])); ?>
                </div>
                <?php if ($submission['file_path']): ?>
                    <div class="mt-4">
                        <a href="<?php echo htmlspecialchars($submission['file_path']); ?>" 
                           class="text-indigo-600 hover:text-indigo-800"
                           target="_blank">
                            Download Attachment
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Review Form -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                <h3 class="text-xl font-bold mb-6">Submit Your Review</h3>
                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
                        <div class="star-rating">
                            <?php for($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
                                <label for="star<?php echo $i; ?>">★</label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div>
                        <label for="feedback" class="block text-sm font-medium text-gray-700">Feedback</label>
                        <textarea id="feedback" name="feedback" rows="6" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="Provide detailed feedback about the submission..."></textarea>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" id="is_anonymous" name="is_anonymous" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="is_anonymous" class="ml-2 block text-sm text-gray-700">
                            Submit anonymously
                        </label>
                    </div>

                    <button type="submit"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                        Submit Review
                    </button>
                </form>
            </div>

            <!-- Existing Reviews -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-xl font-bold mb-6">Previous Reviews</h3>
                <div class="space-y-6">
                    <?php foreach ($reviews as $review): ?>
                        <div class="border-b border-gray-200 pb-6 last:border-b-0 last:pb-0">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <span class="font-medium">
                                        <?php echo $review['is_anonymous'] ? 'Anonymous Reviewer' : htmlspecialchars($review['full_name']); ?>
                                    </span>
                                    <div class="flex items-center mt-1">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <span class="text-yellow-400"><?php echo $i <= $review['rating'] ? '★' : '☆'; ?></span>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <span class="text-sm text-gray-500">
                                    <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                </span>
                            </div>
                            <p class="text-gray-600"><?php echo nl2br(htmlspecialchars($review['feedback'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html> 