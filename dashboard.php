<?php
require_once 'db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirectToLogin();
}

// Get user data
$userId = $_SESSION['user_id'];
$user = getUserData($userId);
$progress = getUserProgress($userId);
$badges = getUserBadges($userId);

// Calculate completion percentage
$totalLessons = count($progress);
$completedLessons = 0;
$totalChallenges = 0;
$completedChallenges = 0;

foreach ($progress as $lesson) {
    $totalChallenges += $lesson['total_challenges'];
    $completedChallenges += $lesson['completed_challenges'];
    
    if ($lesson['total_challenges'] > 0 && $lesson['completed_challenges'] == $lesson['total_challenges']) {
        $completedLessons++;
    }
}

$lessonCompletionPercentage = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;
$challengeCompletionPercentage = $totalChallenges > 0 ? round(($completedChallenges / $totalChallenges) * 100) : 0;

// Get recent activity
$stmt = $conn->prepare("
    SELECT c.title as challenge_title, l.title as lesson_title, up.completed_at
    FROM user_progress up
    JOIN challenges c ON up.challenge_id = c.id
    JOIN lessons l ON up.lesson_id = l.id
    WHERE up.user_id = ? AND up.completed = 1
    ORDER BY up.completed_at DESC
    LIMIT 5
");
$stmt->execute([$userId]);
$recentActivity = $stmt->fetchAll();

// Get next recommended lessons
$stmt = $conn->prepare("
    SELECT l.*, 
           COUNT(c.id) as total_challenges,
           COUNT(up.id) as completed_challenges
    FROM lessons l
    JOIN challenges c ON l.id = c.lesson_id
    LEFT JOIN user_progress up ON c.id = up.challenge_id AND up.user_id = ? AND up.completed = 1
    GROUP BY l.id
    HAVING completed_challenges < total_challenges
    ORDER BY l.lesson_order
    LIMIT 3
");
$stmt->execute([$userId]);
$recommendedLessons = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CodeHopper</title>
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
        
        .dashboard-header {
            margin-bottom: 2rem;
        }
        
        .dashboard-header h1 {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 2rem;
        }
        
        .main-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .card-header h2 {
            font-size: 1.5rem;
            color: var(--primary-color);
        }
        
        .card-icon {
            font-size: 1.8rem;
            color: var(--primary-color);
        }
        
        .progress-container {
            margin-bottom: 1.5rem;
        }
        
        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .progress-bar {
            height: 10px;
            background-color: #e0e0e0;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background-color: var(--primary-color);
            border-radius: 5px;
            transition: width 0.5s ease;
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
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: var(--primary-dark);
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: var(--text-color);
        }
        
        .lesson-list {
            list-style: none;
        }
        
        .lesson-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #eee;
            transition: background-color 0.3s ease;
        }
        
        .lesson-item:last-child {
            border-bottom: none;
        }
        
        .lesson-item:hover {
            background-color: #f5f5f5;
        }
        
        .lesson-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-light);
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 1rem;
            font-size: 1.2rem;
            color: var(--primary-dark);
        }
        
        .lesson-info {
            flex: 1;
        }
        
        .lesson-title {
            font-weight: 500;
            margin-bottom: 0.2rem;
        }
        
        .lesson-meta {
            font-size: 0.85rem;
            color: #666;
        }
        
        .lesson-progress {
            width: 60px;
            text-align: right;
            font-weight: 500;
            color: var(--primary-color);
        }
        
        .badge-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 1rem;
        }
        
        .badge-item {
            text-align: center;
        }
        
        .badge-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: var(--primary-light);
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 0.5rem;
            font-size: 1.5rem;
            color: var(--primary-dark);
        }
        
        .badge-name {
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .activity-list {
            list-style: none;
        }
        
        .activity-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-title {
            font-weight: 500;
            margin-bottom: 0.2rem;
        }
        
        .activity-meta {
            font-size: 0.85rem;
            color: #666;
        }
        
        .btn {
            display: inline-block;
            background-color: var(--primary-color);
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
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
        }
        
        .btn-secondary:hover {
            background-color: #0b7dda;
        }
        
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        
        .sidebar .card {
            margin-bottom: 0;
        }
        
        .recommended-lesson {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #eee;
            transition: background-color 0.3s ease;
        }
        
        .recommended-lesson:last-child {
            border-bottom: none;
        }
        
        .recommended-lesson:hover {
            background-color: #f5f5f5;
        }
        
        .lesson-difficulty {
            display: inline-block;
            padding: 0.2rem 0.5rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 500;
            margin-left: 0.5rem;
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
        
        footer {
            background-color: var(--background-dark);
            color: var(--light-text);
            padding: 1.5rem;
            text-align: center;
            margin-top: 2rem;
        }
        
        /* Responsive styles */
        @media (max-width: 992px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                order: -1;
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
            
            .main-content {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
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
                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                </div>
                <span><?php echo htmlspecialchars($user['username']); ?></span>
            </div>
        </div>
    </header>

    <div class="main-container">
        <div class="dashboard-header">
            <h1>Welcome back, <?php echo htmlspecialchars($user['username']); ?>!</h1>
            <p>Continue your coding journey where you left off.</p>
        </div>
        
        <div class="dashboard-grid">
            <div class="main-content">
                <div class="card">
                    <div class="card-header">
                        <h2>Your Progress</h2>
                        <div class="card-icon">📊</div>
                    </div>
                    
                    <div class="progress-container">
                        <div class="progress-label">
                            <span>Lessons Completed</span>
                            <span><?php echo $completedLessons; ?> / <?php echo $totalLessons; ?></span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $lessonCompletionPercentage; ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="progress-container">
                        <div class="progress-label">
                            <span>Challenges Completed</span>
                            <span><?php echo $completedChallenges; ?> / <?php echo $totalChallenges; ?></span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $challengeCompletionPercentage; ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $user['level']; ?></div>
                            <div class="stat-label">Current Level</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $user['points']; ?></div>
                            <div class="stat-label">Total Points</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo count($badges); ?></div>
                            <div class="stat-label">Badges Earned</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $challengeCompletionPercentage; ?>%</div>
                            <div class="stat-label">Completion</div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2>Your Lessons</h2>
                        <div class="card-icon">📚</div>
                    </div>
                    
                    <?php if (empty($progress)): ?>
                        <p>No lessons available yet. Start your journey by exploring our lessons!</p>
                        <div style="margin-top: 1rem;">
                            <a href="lessons.php" class="btn">Browse Lessons</a>
                        </div>
                    <?php else: ?>
                        <ul class="lesson-list">
                            <?php foreach ($progress as $lesson): ?>
                                <li class="lesson-item">
                                    <div class="lesson-icon">
                                        <?php echo $lesson['completed_challenges'] == $lesson['total_challenges'] ? '✓' : ($lesson['completed_challenges'] > 0 ? '⋯' : '!'); ?>
                                    </div>
                                    <div class="lesson-info">
                                        <div class="lesson-title"><?php echo htmlspecialchars($lesson['lesson_title']); ?></div>
                                        <div class="lesson-meta">
                                            <?php echo $lesson['completed_challenges']; ?> of <?php echo $lesson['total_challenges']; ?> challenges completed
                                        </div>
                                    </div>
                                    <div class="lesson-progress">
                                        <?php 
                                            $percentage = $lesson['total_challenges'] > 0 ? round(($lesson['completed_challenges'] / $lesson['total_challenges']) * 100) : 0;
                                            echo $percentage . '%';
                                        ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div style="margin-top: 1.5rem; text-align: center;">
                            <a href="lessons.php" class="btn">View All Lessons</a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2>Your Badges</h2>
                        <div class="card-icon">🏆</div>
                    </div>
                    
                    <?php if (empty($badges)): ?>
                        <p>You haven't earned any badges yet. Complete challenges to earn badges!</p>
                    <?php else: ?>
                        <div class="badge-grid">
                            <?php foreach ($badges as $badge): ?>
                                <div class="badge-item" title="<?php echo htmlspecialchars($badge['description']); ?>">
                                    <div class="badge-icon">🏅</div>
                                    <div class="badge-name"><?php echo htmlspecialchars($badge['name']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="sidebar">
                <div class="card">
                    <div class="card-header">
                        <h2>Continue Learning</h2>
                        <div class="card-icon">🚀</div>
                    </div>
                    
                    <?php if (empty($recommendedLessons)): ?>
                        <p>Great job! You've completed all available lessons.</p>
                    <?php else: ?>
                        <?php foreach ($recommendedLessons as $lesson): ?>
                            <div class="recommended-lesson">
                                <div class="lesson-icon">
                                    <?php echo substr($lesson['title'], 0, 1); ?>
                                </div>
                                <div class="lesson-info">
                                    <div class="lesson-title">
                                        <?php echo htmlspecialchars($lesson['title']); ?>
                                        <span class="lesson-difficulty difficulty-<?php echo strtolower($lesson['difficulty']); ?>">
                                            <?php echo $lesson['difficulty']; ?>
                                        </span>
                                    </div>
                                    <div class="lesson-meta">
                                        <?php 
                                            $percentage = $lesson['total_challenges'] > 0 ? round(($lesson['completed_challenges'] / $lesson['total_challenges']) * 100) : 0;
                                            echo $percentage . '% completed';
                                        ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div style="margin-top: 1rem; text-align: center;">
                            <a href="lessons.php?id=<?php echo $recommendedLessons[0]['id']; ?>" class="btn">Continue</a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2>Recent Activity</h2>
                        <div class="card-icon">📝</div>
                    </div>
                    
                    <?php if (empty($recentActivity)): ?>
                        <p>No recent activity. Start completing challenges to see your activity here!</p>
                    <?php else: ?>
                        <ul class="activity-list">
                            <?php foreach ($recentActivity as $activity): ?>
                                <li class="activity-item">
                                    <div class="activity-title">
                                        Completed: <?php echo htmlspecialchars($activity['challenge_title']); ?>
                                    </div>
                                    <div class="activity-meta">
                                        <span>Lesson: <?php echo htmlspecialchars($activity['lesson_title']); ?></span>
                                        <span> • </span>
                                        <span><?php echo date('M j, Y', strtotime($activity['completed_at'])); ?></span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> CodeHopper. All rights reserved.</p>
    </footer>
</body>
</html>
