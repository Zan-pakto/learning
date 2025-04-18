<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            header('Location: index.php');
            exit();
        } else {
            $error = 'Invalid email or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Peer Review System</title>
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
            background: linear-gradient(135deg, #6366f1 0%, #818cf8 100%);
        }
        .gradient-text {
            background: linear-gradient(135deg, #6366f1 0%, #818cf8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .input-focus {
            transition: all 0.3s ease;
        }
        .input-focus:focus {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .input-box {
            position: relative;
            margin-bottom: 1rem;
        }
        .input-box input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            background: rgba(255, 255, 255, 0.9);
            transition: all 0.3s ease;
        }
        .input-box input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            outline: none;
        }
        .input-box label {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            padding: 0 0.25rem;
            color: #6b7280;
            transition: all 0.2s ease;
            pointer-events: none;
        }
        .input-box input:focus + label,
        .input-box input:not(:placeholder-shown) + label {
            top: 0;
            transform: translateY(-50%) scale(0.9);
            background: white;
            color: #6366f1;
        }
        .input-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            transition: all 0.3s ease;
        }
        .input-box input:focus ~ .input-icon {
            color: #6366f1;
        }
        .btn-hover {
            transition: all 0.3s ease;
        }
        .btn-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        @keyframes floating {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        .bg-pattern {
            background-color: #f3f4f6;
            background-image: 
                radial-gradient(at 47% 33%, hsl(162.00, 77%, 40%) 0, transparent 59%), 
                radial-gradient(at 82% 65%, hsl(218.00, 39%, 11%) 0, transparent 55%);
            background-attachment: fixed;
            background-size: cover;
            min-height: 100vh;
        }
        .decoration-blob {
            position: fixed;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.4;
            z-index: 0;
            pointer-events: none;
        }
        .blob-1 {
            background: #4f46e5;
            top: -100px;
            right: -100px;
        }
        .blob-2 {
            background: #818cf8;
            bottom: -100px;
            left: -100px;
        }
        .content-wrapper {
            position: relative;
            z-index: 10;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
    </style>
</head>
<body class="bg-pattern">
    <div class="decoration-blob blob-1"></div>
    <div class="decoration-blob blob-2"></div>
    
    <div class="content-wrapper">
        <div class="max-w-md w-full space-y-8 fade-in">
            <div class="glass-effect rounded-2xl p-8 shadow-xl">
                <div class="text-center">
                    <div class="floating inline-block mb-4">
                        <svg class="h-16 w-16 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h2 class="mt-6 text-4xl font-extrabold text-gray-900">
                        Welcome <span class="gradient-text">Back</span>
                    </h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Sign in to your account to continue
                    </p>
                </div>
                
                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-4 animate__animated animate__shakeX">
                        <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>
                
                <form class="mt-8 space-y-6" method="POST">
                    <div class="space-y-4">
                        <div class="input-box">
                            <input id="email" name="email" type="email" required placeholder=" "
                                   class="appearance-none w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm input-focus" 
                                   placeholder=" ">
                            <label for="email">Email address</label>
                            <div class="input-icon">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                </svg>
                            </div>
                        </div>
                        <div class="input-box">
                            <input id="password" name="password" type="password" required placeholder=" "
                                   class="appearance-none w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm input-focus" 
                                   placeholder=" ">
                            <label for="password">Password</label>
                            <div class="input-icon">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input id="remember-me" name="remember-me" type="checkbox" 
                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded transition-colors duration-200">
                            <label for="remember-me" class="ml-2 block text-sm text-gray-900">
                                Remember me
                            </label>
                        </div>

                        <div class="text-sm">
                            <a href="#" class="font-medium text-indigo-600 hover:text-indigo-500 transition-colors duration-200">
                                Forgot your password?
                            </a>
                        </div>
                    </div>

                    <div>
                        <button type="submit" 
                                class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white gradient-bg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200 btn-hover">
                            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                <svg class="h-5 w-5 text-indigo-300 group-hover:text-indigo-200 transition-colors duration-200" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                            Sign in
                        </button>
                    </div>
                </form>
                
                <div class="text-center mt-6">
                    <p class="text-sm text-gray-600">
                        Don't have an account? 
                        <a href="register.php" class="font-medium text-indigo-600 hover:text-indigo-500 transition-colors duration-200">
                            Sign up
                        </a>
                    </p>
                </div>
                
                <div class="mt-8 text-center">
                    <a href="index.php" class="text-sm text-gray-500 hover:text-gray-700 transition-colors duration-200">
                        ← Back to home
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 