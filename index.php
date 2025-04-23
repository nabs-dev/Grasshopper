<?php
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CodeHopper - Learn to Code Interactively</title>
    <style>
        :root {
            --primary-color: #4CAF50;
            --primary-dark: #388E3C;
            --primary-light: #A5D6A7;
            --secondary-color: #2196F3;
            --text-color: #333;
            --light-text: #fff;
            --background-light: #f9f9f9;
            --background-dark: #333;
            --success-color: #4CAF50;
            --error-color: #F44336;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: var(--background-light);
            color: var(--text-color);
            line-height: 1.6;
        }
        
        header {
            background-color: var(--primary-color);
            color: var(--light-text);
            padding: 1rem 2rem;
            box-shadow: var(--box-shadow);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }
        
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            display: flex;
            align-items: center;
        }
        
        .logo-icon {
            margin-right: 10px;
            font-size: 2rem;
        }
        
        nav ul {
            display: flex;
            list-style: none;
        }
        
        nav ul li {
            margin-left: 1.5rem;
        }
        
        nav ul li a {
            color: var(--light-text);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
        }
        
        nav ul li a:hover {
            background-color: var(--primary-dark);
        }
        
        .btn {
            display: inline-block;
            background-color: var(--secondary-color);
            color: var(--light-text);
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .btn:hover {
            background-color: #0b7dda;
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
        }
        
        .hero {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: var(--light-text);
            padding: 8rem 2rem 6rem;
            text-align: center;
        }
        
        .hero-content {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1.5rem;
        }
        
        .hero p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .features {
            padding: 5rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 3rem;
            font-size: 2.2rem;
            color: var(--primary-color);
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .feature-card {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--box-shadow);
            transition: transform 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
        }
        
        .feature-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .feature-card h3 {
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }
        
        .how-it-works {
            background-color: var(--primary-light);
            padding: 5rem 2rem;
        }
        
        .steps-container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .step {
            display: flex;
            align-items: center;
            margin-bottom: 3rem;
            background-color: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--box-shadow);
        }
        
        .step-number {
            background-color: var(--primary-color);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.5rem;
            font-weight: bold;
            margin-right: 2rem;
            flex-shrink: 0;
        }
        
        .step-content h3 {
            margin-bottom: 0.5rem;
            font-size: 1.5rem;
        }
        
        .testimonials {
            padding: 5rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .testimonial-card {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--box-shadow);
        }
        
        .testimonial-text {
            font-style: italic;
            margin-bottom: 1.5rem;
        }
        
        .testimonial-author {
            display: flex;
            align-items: center;
        }
        
        .author-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 1rem;
            object-fit: cover;
        }
        
        .cta {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: var(--light-text);
            padding: 5rem 2rem;
            text-align: center;
        }
        
        .cta-content {
            max-width: 700px;
            margin: 0 auto;
        }
        
        .cta h2 {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
        }
        
        .cta p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        footer {
            background-color: var(--background-dark);
            color: var(--light-text);
            padding: 3rem 2rem;
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
        }
        
        .footer-column h3 {
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
        }
        
        .footer-column ul {
            list-style: none;
        }
        
        .footer-column ul li {
            margin-bottom: 0.8rem;
        }
        
        .footer-column ul li a {
            color: var(--light-text);
            text-decoration: none;
            opacity: 0.8;
            transition: opacity 0.3s ease;
        }
        
        .footer-column ul li a:hover {
            opacity: 1;
        }
        
        .copyright {
            text-align: center;
            padding-top: 2rem;
            margin-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                padding: 1rem 0;
            }
            
            .logo {
                margin-bottom: 1rem;
            }
            
            nav ul {
                flex-direction: column;
                align-items: center;
            }
            
            nav ul li {
                margin: 0.5rem 0;
            }
            
            .hero h1 {
                font-size: 2.2rem;
            }
            
            .step {
                flex-direction: column;
                text-align: center;
            }
            
            .step-number {
                margin-right: 0;
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo">
                <span class="logo-icon">🦗</span>
                <span>CodeHopper</span>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="#features">Features</a></li>
                    <li><a href="#how-it-works">How It Works</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="register.php" class="btn">Sign Up</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <section class="hero">
        <div class="hero-content">
            <h1>Learn to Code the Fun Way</h1>
            <p>Master programming skills with interactive lessons, real-time feedback, and a gamified learning experience.</p>
            <a href="register.php" class="btn btn-primary">Start Coding Now</a>
        </div>
    </section>

    <section class="features" id="features">
        <h2 class="section-title">Why Choose CodeHopper?</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">📚</div>
                <h3>Interactive Lessons</h3>
                <p>Learn programming concepts through hands-on coding exercises that make learning engaging and effective.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🚀</div>
                <h3>Progress Tracking</h3>
                <p>Track your learning journey with detailed progress metrics and visualizations of your growth.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🏆</div>
                <h3>Earn Rewards</h3>
                <p>Collect badges and level up as you master new skills, keeping you motivated throughout your learning journey.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">💡</div>
                <h3>Instant Feedback</h3>
                <p>Get immediate feedback on your code to quickly identify and fix mistakes, accelerating your learning.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔄</div>
                <h3>Bite-sized Lessons</h3>
                <p>Learn at your own pace with short, focused lessons that fit into your busy schedule.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📱</div>
                <h3>Mobile Friendly</h3>
                <p>Practice coding anytime, anywhere with our responsive design that works on all devices.</p>
            </div>
        </div>
    </section>

    <section class="how-it-works" id="how-it-works">
        <h2 class="section-title">How It Works</h2>
        <div class="steps-container">
            <div class="step">
                <div class="step-number">1</div>
                <div class="step-content">
                    <h3>Create an Account</h3>
                    <p>Sign up for free and set up your personal learning profile to track your progress.</p>
                </div>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <div class="step-content">
                    <h3>Choose Your Path</h3>
                    <p>Select from various programming topics and start with beginner-friendly lessons.</p>
                </div>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-content">
                    <h3>Complete Challenges</h3>
                    <p>Solve interactive coding challenges that reinforce concepts and build your skills.</p>
                </div>
            </div>
            <div class="step">
                <div class="step-number">4</div>
                <div class="step-content">
                    <h3>Earn Rewards</h3>
                    <p>Collect badges, level up, and track your progress as you master new programming skills.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="testimonials">
        <h2 class="section-title">What Our Users Say</h2>
        <div class="testimonials-grid">
            <div class="testimonial-card">
                <p class="testimonial-text">"CodeHopper made learning to code fun and engaging. The interactive challenges helped me understand programming concepts much better than traditional tutorials."</p>
                <div class="testimonial-author">
                    <img src="/placeholder.svg?height=50&width=50" alt="User Avatar" class="author-avatar">
                    <div>
                        <h4>Sarah Johnson</h4>
                        <p>Web Developer</p>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <p class="testimonial-text">"I tried many coding platforms, but CodeHopper's gamified approach kept me motivated. I've earned 15 badges and can now build my own web applications!"</p>
                <div class="testimonial-author">
                    <img src="/placeholder.svg?height=50&width=50" alt="User Avatar" class="author-avatar">
                    <div>
                        <h4>Michael Chen</h4>
                        <p>Computer Science Student</p>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <p class="testimonial-text">"The bite-sized lessons fit perfectly into my busy schedule. I could learn a new concept in just 15 minutes during my lunch break. Highly recommended!"</p>
                <div class="testimonial-author">
                    <img src="/placeholder.svg?height=50&width=50" alt="User Avatar" class="author-avatar">
                    <div>
                        <h4>Emily Rodriguez</h4>
                        <p>Marketing Professional</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="cta">
        <div class="cta-content">
            <h2>Ready to Start Your Coding Journey?</h2>
            <p>Join thousands of learners who have transformed their careers through interactive coding education.</p>
            <a href="register.php" class="btn">Sign Up for Free</a>
        </div>
    </section>

    <footer>
        <div class="footer-content">
            <div class="footer-column">
                <h3>CodeHopper</h3>
                <p>Learn to code the fun and interactive way.</p>
            </div>
            <div class="footer-column">
                <h3>Resources</h3>
                <ul>
                    <li><a href="#">Documentation</a></li>
                    <li><a href="#">Blog</a></li>
                    <li><a href="#">Community</a></li>
                    <li><a href="#">Help Center</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Company</h3>
                <ul>
                    <li><a href="#">About Us</a></li>
                    <li><a href="#">Careers</a></li>
                    <li><a href="#">Contact</a></li>
                    <li><a href="#">Partners</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Legal</h3>
                <ul>
                    <li><a href="#">Terms of Service</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Cookie Policy</a></li>
                </ul>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; <?php echo date('Y'); ?> CodeHopper. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // JavaScript for smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>
