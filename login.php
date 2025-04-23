<?php
require_once 'db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($email)) {
        $errors[] = "Email is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    // Authenticate user
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            // Redirect to dashboard
            echo "<script>window.location.href = 'dashboard.php';</script>";
            exit;
        } else {
            $errors[] = "Invalid email or password";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CodeHopper</title>
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
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        header {
            background-color: var(--primary-color);
            color: var(--light-text);
            padding: 1rem 2rem;
            box-shadow: var(--box-shadow);
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
            text-decoration: none;
            color: var(--light-text);
        }
        
        .logo-icon {
            margin-right: 10px;
            font-size: 2rem;
        }
        
        .main-content {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }
        
        .auth-container {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            width: 100%;
            max-width: 500px;
            padding: 2rem;
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .auth-header h1 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .auth-form .form-group {
            margin-bottom: 1.5rem;
        }
        
        .auth-form label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .auth-form input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .auth-form input:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .auth-form button {
            width: 100%;
            padding: 1rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .auth-form button:hover {
            background-color: var(--primary-dark);
        }
        
        .auth-footer {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .auth-footer a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .auth-footer a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
        }
        
        .alert-error {
            background-color: #FFEBEE;
            color: var(--error-color);
            border: 1px solid #FFCDD2;
        }
        
        footer {
            background-color: var(--background-dark);
            color: var(--light-text);
            padding: 1.5rem;
            text-align: center;
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
            
            .auth-container {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <a href="index.php" class="logo">
                <span class="logo-icon">🦗</span>
                <span>CodeHopper</span>
            </a>
        </div>
    </header>

    <div class="main-content">
        <div class="auth-container">
            <div class="auth-header">
                <h1>Welcome Back</h1>
                <p>Log in to continue your coding journey</p>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form class="auth-form" method="POST" action="">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit">Log In</button>
            </form>
            
            <div class="auth-footer">
                <p>Don't have an account? <a href="register.php">Sign Up</a></p>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> CodeHopper. All rights reserved.</p>
    </footer>
</body>
</html>
