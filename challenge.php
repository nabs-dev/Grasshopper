<?php
require_once 'db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirectToLogin();
}

// Get user data
$userId = $_SESSION['user_id'];

// Check if challenge ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>window.location.href = 'lessons.php';</script>";
    exit;
}

$challengeId = $_GET['id'];

// Get challenge details
$stmt = $conn->prepare("
    SELECT c.*, l.title as lesson_title, l.id as lesson_id
    FROM challenges c
    JOIN lessons l ON c.lesson_id = l.id
    WHERE c.id = ?
");
$stmt->execute([$challengeId]);
$challenge = $stmt->fetch();

if (!$challenge) {
    echo "<script>window.location.href = 'lessons.php';</script>";
    exit;
}

// Get user progress for this challenge
$stmt = $conn->prepare("
    SELECT * FROM user_progress 
    WHERE user_id = ? AND challenge_id = ?
");
$stmt->execute([$userId, $challengeId]);
$progress = $stmt->fetch();

$isCompleted = $progress && $progress['completed'] == 1;

// Handle code submission
$message = '';
$messageType = '';
$userCode = $challenge['initial_code'];
$output = '';
$showHint = false;
$showSolution = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'run') {
        // Run code
        $userCode = $_POST['code'] ?? '';
        
        // Simple PHP code execution (with safety measures)
        ob_start();
        try {
            // Create a temporary file with the user's code
            $tempFile = tempnam(sys_get_temp_dir(), 'php_');
            file_put_contents($tempFile, $userCode);
            
            // Execute the code in a controlled environment
            include $tempFile;
            
            // Clean up
            unlink($tempFile);
            
            $output = ob_get_clean();
            
            // Check if output matches expected output
            if (trim($output) === trim($challenge['expected_output'])) {
                $message = 'Congratulations! Your code produced the expected output.';
                $messageType = 'success';
                
                // Update user progress if not already completed
                if (!$isCompleted) {
                    if ($progress) {
                        $stmt = $conn->prepare("
                            UPDATE user_progress 
                            SET completed = 1, score = ?, completed_at = NOW()
                            WHERE user_id = ? AND challenge_id = ?
                        ");
                        $stmt->execute([$challenge['points'], $userId, $challengeId]);
                    } else {
                        $stmt = $conn->prepare("
                            INSERT INTO user_progress (user_id, lesson_id, challenge_id, completed, score, completed_at)
                            VALUES (?, ?, ?, 1, ?, NOW())
                        ");
                        $stmt->execute([$userId, $challenge['lesson_id'], $challengeId, $challenge['points']]);
                    }
                    
                    // Update user points
                    $stmt = $conn->prepare("
                        UPDATE users 
                        SET points = points + ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$challenge['points'], $userId]);
                    
                    // Check for level up (every 50 points)
                    $stmt = $conn->prepare("
                        UPDATE users 
                        SET level = FLOOR(points / 50) + 1
                        WHERE id = ?
                    ");
                    $stmt->execute([$userId]);
                    
                    // Check for badges
                    $newBadges = checkAndAwardBadges($userId);
                    
                    if (!empty($newBadges)) {
                        $badgeNames = array_map(function($badge) {
                            return $badge['name'];
                        }, $newBadges);
                        
                        $message .= ' You earned new badge(s): ' . implode(', ', $badgeNames);
                    }
                    
                    $isCompleted = true;
                }
            } else {
                $message = 'Your code ran successfully, but the output does not match the expected result.';
                $messageType = 'error';
            }
        } catch (Throwable $e) {
            $output = "Error: " . $e->getMessage();
            $message = 'Your code produced an error.';
            $messageType = 'error';
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'hint') {
        // Show hint
        $showHint = true;
        $userCode = $_POST['code'] ?? '';
    } elseif (isset($_POST['action']) && $_POST['action'] === 'solution') {
        // Show solution
        $showSolution = true;
        $userCode = $challenge['solution'];
    }
}

// Get next challenge
$stmt = $conn->prepare("
    SELECT id FROM challenges 
    WHERE lesson_id = ? AND challenge_order > ?
    ORDER BY challenge_order
    LIMIT 1
");
$stmt->execute([$challenge['lesson_id'], $challenge['challenge_order']]);
$nextChallenge = $stmt->fetch();

// Get previous challenge
$stmt = $conn->prepare("
    SELECT id FROM challenges 
    WHERE lesson_id = ? AND challenge_order < ?
    ORDER BY challenge_order DESC
    LIMIT 1
");
$stmt->execute([$challenge['lesson_id'], $challenge['challenge_order']]);
$prevChallenge = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($challenge['title']); ?> - CodeHopper</title>
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
            --editor-bg: #282c34;
            --editor-text: #abb2bf;
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
            height: 100vh;
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
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-dark);
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 10px;
            font-weight: bold;
        }
        
        .main-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            width: 100%;
        }
        
        .challenge-header {
            margin-bottom: 2rem;
        }
        
        .challenge-title {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }
        
        .challenge-meta {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            color: #666;
        }
        
        .challenge-meta span {
            margin-right: 1.5rem;
            display: flex;
            align-items: center;
        }
        
        .challenge-meta span:before {
            content: '';
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 0.5rem;
        }
        
        .challenge-lesson:before {
            background-color: var(--primary-color);
        }
        
        .challenge-difficulty:before {
            background-color: var(--secondary-color);
        }
        
        .challenge-points:before {
            background-color: #FFC107;
        }
        
        .challenge-description {
            margin-bottom: 1.5rem;
        }
        
        .challenge-instructions {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--box-shadow);
        }
        
        .challenge-instructions h3 {
            margin-bottom: 1rem;
            color: var(--primary-color);
        }
        
        .challenge-workspace {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            flex: 1;
        }
        
        .code-editor {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .editor-header {
            background-color: var(--primary-color);
            color: white;
            padding: 0.8rem 1.5rem;
            font-weight: 500;
        }
        
        .editor-container {
            flex: 1;
            position: relative;
        }
        
        .editor-textarea {
            width: 100%;
            height: 300px;
            padding: 1rem;
            font-family: 'Courier New', Courier, monospace;
            font-size: 1rem;
            line-height: 1.5;
            border: none;
            resize: none;
            background-color: var(--editor-bg);
            color: var(--editor-text);
        }
        
        .editor-textarea:focus {
            outline: none;
        }
        
        .editor-actions {
            display: flex;
            padding: 1rem;
            background-color: #f5f5f5;
            border-top: 1px solid #eee;
        }
        
        .btn {
            display: inline-block;
            background-color: var(--primary-color);
            color: var(--light-text);
            padding: 0.7rem 1.2rem;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
        }
        
        .btn-secondary:hover {
            background-color: #0b7dda;
        }
        
        .btn-outline {
            background-color: transparent;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }
        
        .btn-outline:hover {
            background-color: var(--primary-light);
        }
        
        .btn + .btn {
            margin-left: 1rem;
        }
        
        .output-panel {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .output-header {
            background-color: var(--secondary-color);
            color: white;
            padding: 0.8rem 1.5rem;
            font-weight: 500;
        }
        
        .output-container {
            flex: 1;
            padding: 1rem;
            overflow: auto;
        }
        
        .output-content {
            font-family: 'Courier New', Courier, monospace;
            white-space: pre-wrap;
            line-height: 1.5;
        }
        
        .expected-output {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }
        
        .expected-output h4 {
            margin-bottom: 0.5rem;
            color: var(--secondary-color);
        }
        
        .hint-panel, .solution-panel {
            background-color: #FFF8E1;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-top: 2rem;
            border-left: 4px solid #FFC107;
        }
        
        .hint-panel h3, .solution-panel h3 {
            color: #F57F17;
            margin-bottom: 1rem;
        }
        
        .solution-panel {
            background-color: #E8F5E9;
            border-left-color: var(--primary-color);
        }
        
        .solution-panel h3 {
            color: var(--primary-dark);
        }
        
        .solution-code {
            font-family: 'Courier New', Courier, monospace;
            background-color: #f5f5f5;
            padding: 1rem;
            border-radius: var(--border-radius);
            white-space: pre-wrap;
        }
        
        .challenge-navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #eee;
        }
        
        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background-color: #E8F5E9;
            color: var(--success-color);
            border: 1px solid #C8E6C9;
        }
        
        .alert-error {
            background-color: #FFEBEE;
            color: var(--error-color);
            border: 1px solid #FFCDD2;
        }
        
        .completed-badge {
            display: inline-block;
            background-color: var(--primary-light);
            color: var(--primary-dark);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            margin-left: 1rem;
        }
        
        footer {
            background-color: var(--background-dark);
            color: var(--light-text);
            padding: 1.5rem;
            text-align: center;
        }
        
        /* Responsive styles */
        @media (max-width: 992px) {
            .challenge-workspace {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                padding: 1rem 0;
            }
            
            .logo {
                margin-bottom: 1rem;
            }
            
            nav ul {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            nav ul li {
                margin: 0.5rem;
            }
            
            .challenge-meta {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .challenge-meta span {
                margin-bottom: 0.5rem;
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
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="lessons.php">Lessons</a></li>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
            <div class="user-info">
                <div class="user-avatar">
                    <?php 
                        $user = getUserData($userId);
                        echo strtoupper(substr($user['username'], 0, 1)); 
                    ?>
                </div>
                <span><?php echo htmlspecialchars($user['username']); ?></span>
            </div>
        </div>
    </header>

    <div class="main-container">
        <div class="challenge-header">
            <div class="challenge-title">
                <?php echo htmlspecialchars($challenge['title']); ?>
                <?php if ($isCompleted): ?>
                    <span class="completed-badge">Completed</span>
                <?php endif; ?>
            </div>
            <div class="challenge-meta">
                <span class="challenge-lesson">Lesson: <?php echo htmlspecialchars($challenge['lesson_title']); ?></span>
                <span class="challenge-difficulty">Difficulty: <?php echo htmlspecialchars($challenge['difficulty'] ?? 'Beginner'); ?></span>
                <span class="challenge-points">Points: <?php echo $challenge['points']; ?></span>
            </div>
            <div class="challenge-description">
                <?php echo htmlspecialchars($challenge['description']); ?>
            </div>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="challenge-instructions">
            <h3>Instructions</h3>
            <p><?php echo htmlspecialchars($challenge['instructions']); ?></p>
        </div>
        
        <div class="challenge-workspace">
            <div class="code-editor">
                <div class="editor-header">
                    Code Editor
                </div>
                <div class="editor-container">
                    <form method="POST" action="">
                        <textarea name="code" class="editor-textarea" spellcheck="false"><?php echo htmlspecialchars($userCode); ?></textarea>
                        <div class="editor-actions">
                            <button type="submit" name="action" value="run" class="btn">Run Code</button>
                            <?php if (!$isCompleted): ?>
                                <button type="submit" name="action" value="hint" class="btn btn-outline">Get Hint</button>
                            <?php endif; ?>
                            <?php if ($isCompleted): ?>
                                <button type="submit" name="action" value="solution" class="btn btn-outline">View Solution</button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="output-panel">
                <div class="output-header">
                    Output
                </div>
                <div class="output-container">
                    <div class="output-content">
                        <?php if (!empty($output)): ?>
                            <strong>Your Output:</strong>
                            <pre><?php echo htmlspecialchars($output); ?></pre>
                        <?php else: ?>
                            <p>Run your code to see the output here.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="expected-output">
                        <h4>Expected Output:</h4>
                        <pre><?php echo htmlspecialchars($challenge['expected_output']); ?></pre>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($showHint): ?>
            <div class="hint-panel">
                <h3>Hint</h3>
                <p><?php echo htmlspecialchars($challenge['hints']); ?></p>
            </div>
        <?php endif; ?>
        
        <?php if ($showSolution): ?>
            <div class="solution-panel">
                <h3>Solution</h3>
                <pre class="solution-code"><?php echo htmlspecialchars($challenge['solution']); ?></pre>
            </div>
        <?php endif; ?>
        
        <div class="challenge-navigation">
            <?php if ($prevChallenge): ?>
                <a href="challenge.php?id=<?php echo $prevChallenge['id']; ?>" class="btn btn-outline">Previous Challenge</a>
            <?php else: ?>
                <a href="lessons.php?id=<?php echo $challenge['lesson_id']; ?>" class="btn btn-outline">Back to Lesson</a>
            <?php endif; ?>
            
            <?php if ($nextChallenge): ?>
                <a href="challenge.php?id=<?php echo $nextChallenge['id']; ?>" class="btn">Next Challenge</a>
            <?php else: ?>
                <a href="lessons.php" class="btn">All Lessons</a>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> CodeHopper. All rights reserved.</p>
    </footer>
</body>
</html>
