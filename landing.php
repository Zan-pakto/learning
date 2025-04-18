<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PeerReview - Online Learning Peer Review System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <script src="https://unpkg.com/alpinejs" defer></script>
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
        .bounce-in {
            animation: bounceIn 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
        @keyframes bounceIn {
            0% { opacity: 0; transform: scale(0.3); }
            50% { opacity: 0.9; transform: scale(1.1); }
            80% { opacity: 1; transform: scale(0.89); }
            100% { opacity: 1; transform: scale(1); }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Hero Section -->
    <header class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <img src="assets/images/hero-pattern.svg" alt="" class="w-full h-full object-cover">
        </div>
        <nav class="max-w-7xl mx-auto px-4 py-4 relative">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <a href="/" class="text-3xl font-bold animate__animated animate__fadeIn">PeerReview</a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="index.php?action=login" class="text-white hover:text-indigo-200 transition-colors duration-200">Login</a>
                    <a href="index.php?action=register" class="bg-white text-indigo-600 px-4 py-2 rounded-lg hover:bg-indigo-100 transition-colors duration-200">Sign Up</a>
                </div>
            </div>
        </nav>
        
        <div class="max-w-7xl mx-auto px-4 py-20 relative">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                <div class="text-left">
                    <h1 class="text-5xl font-bold mb-6 animate__animated animate__fadeInDown">Transform Your Learning Experience</h1>
                    <p class="text-xl mb-8 max-w-3xl animate__animated animate__fadeInUp">Our peer review platform helps students learn from each other through constructive feedback and collaborative improvement.</p>
                    <div class="flex space-x-4 animate__animated animate__fadeInUp">
                        <a href="index.php?action=register" class="bg-white text-indigo-600 px-8 py-3 rounded-lg text-lg font-medium hover:bg-indigo-100 transition-colors duration-200">Get Started</a>
                        <a href="#features" class="bg-transparent border-2 border-white text-white px-8 py-3 rounded-lg text-lg font-medium hover:bg-white hover:text-indigo-600 transition-colors duration-200">Learn More</a>
                    </div>
                </div>
                <div class="hidden md:block animate__animated animate__fadeInRight">
                    <img src="assets/images/hero-illustration.svg" alt="Peer Review Illustration" class="w-full">
                </div>
            </div>
        </div>
    </header>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-12">How It Works</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                <div class="text-center bounce-in" style="animation-delay: 0.1s">
                    <div class="bg-indigo-100 w-32 h-32 rounded-full flex items-center justify-center mx-auto mb-6">
                        <img src="assets/images/submit-work.svg" alt="Submit Work" class="w-20 h-20">
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Submit Your Work</h3>
                    <p class="text-gray-600">Share your assignments, essays, or projects with your peers for review.</p>
                </div>
                
                <div class="text-center bounce-in" style="animation-delay: 0.3s">
                    <div class="bg-indigo-100 w-32 h-32 rounded-full flex items-center justify-center mx-auto mb-6">
                        <img src="assets/images/receive-feedback.svg" alt="Receive Feedback" class="w-20 h-20">
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Receive Feedback</h3>
                    <p class="text-gray-600">Get constructive feedback from your peers to help you improve your work.</p>
                </div>
                
                <div class="text-center bounce-in" style="animation-delay: 0.5s">
                    <div class="bg-indigo-100 w-32 h-32 rounded-full flex items-center justify-center mx-auto mb-6">
                        <img src="assets/images/grow-together.svg" alt="Grow Together" class="w-20 h-20">
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Grow Together</h3>
                    <p class="text-gray-600">Learn from others and help them improve through the peer review process.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-12">Benefits of Peer Review</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                <div class="bg-white p-8 rounded-xl shadow-lg slide-in">
                    <div class="flex items-center mb-6">
                        <img src="assets/images/student-benefits.svg" alt="Student Benefits" class="w-16 h-16 mr-4">
                        <h3 class="text-xl font-semibold">For Students</h3>
                    </div>
                    <ul class="space-y-3 text-gray-600">
                        <li class="flex items-start">
                            <span class="text-indigo-600 mr-2">✓</span>
                            Receive diverse perspectives on your work
                        </li>
                        <li class="flex items-start">
                            <span class="text-indigo-600 mr-2">✓</span>
                            Improve critical thinking and analytical skills
                        </li>
                        <li class="flex items-start">
                            <span class="text-indigo-600 mr-2">✓</span>
                            Build confidence through constructive feedback
                        </li>
                        <li class="flex items-start">
                            <span class="text-indigo-600 mr-2">✓</span>
                            Learn to give and receive feedback effectively
                        </li>
                    </ul>
                </div>
                
                <div class="bg-white p-8 rounded-xl shadow-lg slide-in" style="animation-delay: 0.2s">
                    <div class="flex items-center mb-6">
                        <img src="assets/images/educator-benefits.svg" alt="Educator Benefits" class="w-16 h-16 mr-4">
                        <h3 class="text-xl font-semibold">For Educators</h3>
                    </div>
                    <ul class="space-y-3 text-gray-600">
                        <li class="flex items-start">
                            <span class="text-indigo-600 mr-2">✓</span>
                            Foster collaborative learning environments
                        </li>
                        <li class="flex items-start">
                            <span class="text-indigo-600 mr-2">✓</span>
                            Reduce grading workload through peer assessment
                        </li>
                        <li class="flex items-start">
                            <span class="text-indigo-600 mr-2">✓</span>
                            Track student progress and engagement
                        </li>
                        <li class="flex items-start">
                            <span class="text-indigo-600 mr-2">✓</span>
                            Promote active learning and student responsibility
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-12">What Students Say</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-gray-50 p-6 rounded-xl shadow-md fade-in">
                    <div class="flex items-center mb-4">
                        <img src="assets/images/avatar-1.svg" alt="Sarah Johnson" class="w-12 h-12 rounded-full mr-4">
                        <div>
                            <h4 class="font-semibold">Sarah Johnson</h4>
                            <p class="text-sm text-gray-500">Computer Science Student</p>
                        </div>
                    </div>
                    <p class="text-gray-600">"The peer review system helped me improve my coding projects significantly. Getting feedback from classmates with different perspectives was invaluable."</p>
                </div>
                
                <div class="bg-gray-50 p-6 rounded-xl shadow-md fade-in" style="animation-delay: 0.2s">
                    <div class="flex items-center mb-4">
                        <img src="assets/images/avatar-2.svg" alt="Michael Chen" class="w-12 h-12 rounded-full mr-4">
                        <div>
                            <h4 class="font-semibold">Michael Chen</h4>
                            <p class="text-sm text-gray-500">English Literature Student</p>
                        </div>
                    </div>
                    <p class="text-gray-600">"I was hesitant about sharing my essays, but the anonymous review feature made it comfortable. The feedback I received helped me develop my writing style."</p>
                </div>
                
                <div class="bg-gray-50 p-6 rounded-xl shadow-md fade-in" style="animation-delay: 0.4s">
                    <div class="flex items-center mb-4">
                        <img src="assets/images/avatar-3.svg" alt="Emily Rodriguez" class="w-12 h-12 rounded-full mr-4">
                        <div>
                            <h4 class="font-semibold">Emily Rodriguez</h4>
                            <p class="text-sm text-gray-500">Business Administration Student</p>
                        </div>
                    </div>
                    <p class="text-gray-600">"As someone who struggles with public speaking, reviewing others' presentations helped me learn what works and what doesn't. It's been a game-changer for my confidence."</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-gradient-to-r from-indigo-600 to-purple-600 text-white relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <img src="assets/images/cta-pattern.svg" alt="" class="w-full h-full object-cover">
        </div>
        <div class="max-w-7xl mx-auto px-4 text-center relative">
            <h2 class="text-3xl font-bold mb-6">Ready to Transform Your Learning Experience?</h2>
            <p class="text-xl mb-8 max-w-3xl mx-auto">Join thousands of students who are already benefiting from our peer review platform.</p>
            <div class="flex justify-center space-x-4">
                <a href="index.php?action=register" class="bg-white text-indigo-600 px-8 py-3 rounded-lg text-lg font-medium hover:bg-indigo-100 transition-colors duration-200">Get Started Now</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-12">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4">PeerReview</h3>
                    <p class="text-gray-400">Transform your learning experience through peer feedback and collaboration.</p>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white transition-colors duration-200">Home</a></li>
                        <li><a href="#features" class="hover:text-white transition-colors duration-200">Features</a></li>
                        <li><a href="#" class="hover:text-white transition-colors duration-200">About Us</a></li>
                        <li><a href="#" class="hover:text-white transition-colors duration-200">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Resources</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white transition-colors duration-200">Blog</a></li>
                        <li><a href="#" class="hover:text-white transition-colors duration-200">Help Center</a></li>
                        <li><a href="#" class="hover:text-white transition-colors duration-200">FAQs</a></li>
                        <li><a href="#" class="hover:text-white transition-colors duration-200">Support</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Legal</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white transition-colors duration-200">Privacy Policy</a></li>
                        <li><a href="#" class="hover:text-white transition-colors duration-200">Terms of Service</a></li>
                        <li><a href="#" class="hover:text-white transition-colors duration-200">Cookie Policy</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; 2024 PeerReview. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html> 