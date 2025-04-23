<?php
require_once 'db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirectToLogin();
}

// Get user data
$userId = $_SESSION['user_id'];

// Get all lessons with progress
$stmt = $conn->prepare("
    SELECT l.*, 
           COUNT(c.id) as total_challenges,
           COUNT(up.id) as completed_challenges
    FROM lessons l
    JOIN challenges c ON l.id = c.lesson_id
    LEFT JOIN user_progress up ON c.id = up.challenge_id AND up.user_id = ? AND up.completed = 1
    GROUP BY l.id
    ORDER BY l.lesson_order
");
$stmt->execute([$userId]);
$lessons = $stmt->fetchAll();

// Get specific lesson if ID is provided
$selectedLesson = null;
$challenges = [];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $lessonId = $_GET['id'];
    
    // Get lesson details
    $stmt = $conn->prepare("SELECT * FROM lessons WHERE id = ?");
    $stmt->execute([$lessonId]);
    $selectedLesson = $stmt->fetch();
    
    if ($selectedLesson) {
        // Get challenges for this lesson
        $stmt = $conn->prepare("
            SELECT c.*, 
                   CASE WHEN up.completed = 1 THEN 1 ELSE 0 END as is_completed
            FROM challenges c
            LEFT JOIN user_progress up ON c.id = up.challenge_id AND up.user_id = ?
            WHERE c.lesson_id = ?
            ORDER BY c.challenge_order
        ");
        $stmt->execute([$userId, $lessonId]);
        $challenges = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lessons - CodeHopper</title>
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .page-header {
            margin-bottom: 2rem;
        }
        
        .page-header h1 {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }
        
        .lessons-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .lesson-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .lesson-card:hover {
            transform: translateY(-5px);
        }
        
        .lesson-image {
            height: 160px;
            background-color: var(--primary-light);
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 3rem;
        }
        
        .lesson-content {
            padding: 1.5rem;
        }
        
        .lesson-title {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .lesson-difficulty {
            display: inline-block;
            padding: 0.2rem 0.5rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 500;
        }
        
        .difficulty-beginner {
            background-color: #E8F5E9;
            color: var(--primary-color);
        }
        
        .difficulty-intermediate {
            background-color: #E3F2FD;
            color: var(--secondary-color);
        }
        
        .difficulty-advanced {
            background-color: #FFEBEE;
            color: var(--error-color);
        }
        
        .lesson-description {
            margin-bottom: 1rem;
            color: #666;
        }
        
        .lesson-progress {
            margin-bottom: 1rem;
        }
        
        .progress-bar {
            height: 8px;
            background-color: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background-color: var(--primary-color);
            border-radius: 4px;
        }
        
        .progress-text {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.3rem;
            text-align: right;
        }
        
        .lesson-actions {
            display: flex;
            justify-content: space-between;
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
        
        .lesson-detail {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 2rem;
        }
        
        .lesson-detail-header {
            margin-bottom: 2rem;
        }
        
        .lesson-detail-title {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
            display: flex;
            align-items: center;
        }
        
        .lesson-detail-title .lesson-difficulty {
            margin-left: 1rem;
        }
        
        .lesson-detail-description {
            margin-bottom: 2rem;
            color: #666;
        }
        
        .challenges-list {
            list-style: none;
        }
        
        .challenge-item {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            transition: transform 0.3s ease;
        }
        
        .challenge-item:hover {
            transform: translateY(-3px);
        }
        
        .challenge-status {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 1.5rem;
            flex-shrink: 0;
        }
        
        .status-completed {
            background-color: var(--primary-light);
            color: var(--primary-dark);
        }
        
        .status-incomplete {
            background-color: #e0e0e0;
            color: #666;
        }
        
        .challenge-info {
            flex: 1;
        }
        
        .challenge-title {
            font-size: 1.2rem;
            margin-bottom: 0.3rem;
            font-weight: 500;
        }
        
        .challenge-description {
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .challenge-meta {
            font-size: 0.85rem;
            color: #888;
        }
        
        .challenge-action {
            margin-left: 1rem;
        }
        
        .lesson-sidebar {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 1.5rem;
            height: fit-content;
        }
        
        .sidebar-section {
            margin-bottom: 2rem;
        }
        
        .sidebar-section:last-child {
            margin-bottom: 0;
        }
        
        .sidebar-title {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        .stat-card {
            background-color: var(--primary-light);
            padding: 1rem;
            border-radius: var(--border-radius);
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 0.3rem;
            color: var(--primary-dark);
        }
        
        .stat-label {
            font-size: 0.8rem;
            color: var(--text-color);
        }
        
        .other-lessons {
            list-style: none;
        }
        
        .other-lesson-item {
            padding: 0.8rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .other-lesson-item:last-child {
            border-bottom: none;
        }
        
        .other-lesson-link {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: var(--text-color);
            transition: color 0.3s ease;
        }
        
        .other-lesson-link:hover {
            color: var(--primary-color);
        }
        
        .other-lesson-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background-color: var(--primary-light);
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 0.8rem;
            font-size: 0.8rem;
            color: var(--primary-dark);
            flex-shrink: 0;
        }
        
        .other-lesson-title {
            flex: 1;
            font-size: 0.9rem;
        }
        
        .other-lesson-progress {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 0.7rem;
            font-weight: 500;
        }
        
        .progress-complete {
            background-color: var(--primary-light);
            color: var(--primary-dark);
        }
        
        .progress-partial {
            background-color: #E3F2FD;
            color: var(--secondary-color);
        }
        
        .progress-none {
            background-color: #e0e0e0;
            color: #666;
        }
        
        footer {
            background-color: var(--background-dark);
            color: var(--light-text);
            padding: 1.5rem;
            text-align: center;
            margin-top: 2rem;
        }
        
        /* Responsive styles */
        @media (max-width: 992px) {
            .lesson-detail {
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
            
            .lessons-grid {
                grid-template-columns: 1fr;
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
        <?php if ($selectedLesson): ?>
            <!-- Lesson Detail View -->
            <div class="lesson-detail">
                <div class="lesson-main">
                    <div class="lesson-detail-header">
                        <div class="lesson-detail-title">
                            <?php echo htmlspecialchars($selectedLesson['title']); ?>
                            <span class="lesson-difficulty difficulty-<?php echo strtolower($selectedLesson['difficulty']); ?>">
                                <?php echo $selectedLesson['difficulty']; ?>
                            </span>
                        </div>
                        <div class="lesson-detail-description">
                            <?php echo htmlspecialchars($selectedLesson['description']); ?>
                        </div>
                    </div>
                    
                    <h2>Challenges</h2>
                    
                    <?php if (empty($challenges)): ?>
                        <p>No challenges available for this lesson yet.</p>
                    <?php else: ?>
                        <ul class="challenges-list">
                            <?php foreach ($challenges as $challenge): ?>
                                <li class="challenge-item">
                                    <div class="challenge-status <?php echo $challenge['is_completed'] ? 'status-completed' : 'status-incomplete'; ?>">
                                        <?php echo $challenge['is_completed'] ? '✓' : ($challenge['challenge_order']); ?>
                                    </div>
                                    <div class="challenge-info">
                                        <div class="challenge-title"><?php echo htmlspecialchars($challenge['title']); ?></div>
                                        <div class="challenge-description"><?php echo htmlspecialchars($challenge['description']); ?></div>
                                        <div class="challenge-meta">
                                            <span><?php echo $challenge['points']; ?> points</span>
                                        </div>
                                    </div>
                                    <div class="challenge-action">
                                        <a href="challenge.php?id=<?php echo $challenge['id']; ?>" class="btn <?php echo $challenge['is_completed'] ? 'btn-outline' : ''; ?>">
                                            <?php echo $challenge['is_completed'] ? 'Review' : 'Start'; ?>
                                        </a>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                
                <div class="lesson-sidebar">
                    <div class="sidebar-section">
                        <h3 class="sidebar-title">Lesson Progress</h3>
                        <?php
                            $completedCount = 0;
                            foreach ($challenges as $challenge) {
                                if ($challenge['is_completed']) {
                                    $completedCount++;
                                }
                            }
                            $totalChallenges = count($challenges);
                            $progressPercentage = $totalChallenges > 0 ? round(($completedCount / $totalChallenges) * 100) : 0;
                        ?>
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $completedCount; ?>/<?php echo $totalChallenges; ?></div>
                                <div class="stat-label">Challenges</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $progressPercentage; ?>%</div>
                                <div class="stat-label">Completion</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="sidebar-section">
                        <h3 class="sidebar-title">Other Lessons</h3>
                        <ul class="other-lessons">
                            <?php foreach ($lessons as $lesson): ?>
                                <?php if ($lesson['id'] != $selectedLesson['id']): ?>
                                    <li class="other-lesson-item">
                                        <a href="lessons.php?id=<?php echo $lesson['id']; ?>" class="other-lesson-link">
                                            <div class="other-lesson-icon">
                                                <?php echo substr($lesson['title'], 0, 1); ?>
                                            </div>
                                            <div class="other-lesson-title">
                                                <?php echo htmlspecialchars($lesson['title']); ?>
                                            </div>
                                            <?php
                                                $lessonProgress = $lesson['total_challenges'] > 0 ? round(($lesson['completed_challenges'] / $lesson['total_challenges']) * 100) : 0;
                                                $progressClass = $lessonProgress == 100 ? 'progress-complete' : ($lessonProgress > 0 ? 'progress-partial' : 'progress-none');
                                            ?>
                                            <div class="other-lesson-progress <?php echo $progressClass; ?>">
                                                <?php echo $lessonProgress; ?>%
                                            </div>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <div class="sidebar-section">
                        <a href="dashboard.php" class="btn btn-outline" style="width: 100%;">Back to Dashboard</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Lessons List View -->
            <div class="page-header">
                <h1>Lessons</h1>
                <p>Choose a lesson to start or continue your learning journey.</p>
            </div>
            
            <?php if (empty($lessons)): ?>
                <p>No lessons available yet. Check back soon!</p>
            <?php else: ?>
                <div class="lessons-grid">
                    <?php foreach ($lessons as $lesson): ?>
                        <div class="lesson-card">
                            <div class="lesson-image">
                                <?php echo substr($lesson['title'], 0, 1); ?>
                            </div>
                            <div class="lesson-content">
                                <div class="lesson-title">
                                    <?php echo htmlspecialchars($lesson['title']); ?>
                                    <span class="lesson-difficulty difficulty-<?php echo strtolower($lesson['difficulty']); ?>">
                                        <?php echo $lesson['difficulty']; ?>
                                    </span>
                                </div>
                                <div class="lesson-description">
                                    <?php echo htmlspecialchars($lesson['description']); ?>
                                </div>
                                <div class="lesson-progress">
                                    <?php
                                        $progressPercentage = $lesson['total_challenges'] > 0 ? round(($lesson['completed_challenges'] / $lesson['total_challenges']) * 100) : 0;
                                    ?>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $progressPercentage; ?>%"></div>
                                    </div>
                                    <div class="progress-text">
                                        <?php echo $lesson['completed_challenges']; ?> of <?php echo $lesson['total_challenges']; ?> challenges completed
                                    </div>
                                </div>
                                <div class="lesson-actions">
                                    <a href="lessons.php?id=<?php echo $lesson['id']; ?>" class="btn">
                                        <?php echo $progressPercentage > 0 ? 'Continue' : 'Start'; ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> CodeHopper. All rights reserved.</p>
    </footer>
</body>
</html>
