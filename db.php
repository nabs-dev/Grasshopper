<?php
// Database connection parameters
$host = 'localhost';
$dbname = 'dbmpgicrphzmj6';
$username = 'u8gr0sjr9p4p4';
$password = '9yxuqyo3mt85';

// Create connection
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Session start
session_start();

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirectToLogin() {
    echo "<script>window.location.href = 'login.php';</script>";
    exit;
}

function getUserData($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

function getUserProgress($userId) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT l.id as lesson_id, l.title as lesson_title, 
               COUNT(c.id) as total_challenges,
               COUNT(up.completed) as completed_challenges
        FROM lessons l
        JOIN challenges c ON l.id = c.lesson_id
        LEFT JOIN user_progress up ON c.id = up.challenge_id AND up.user_id = ? AND up.completed = 1
        GROUP BY l.id
        ORDER BY l.lesson_order
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function getUserBadges($userId) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT b.* FROM badges b
        JOIN user_badges ub ON b.id = ub.badge_id
        WHERE ub.user_id = ?
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function checkAndAwardBadges($userId) {
    global $conn;
    
    // Get user's current points
    $stmt = $conn->prepare("SELECT points FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    $userPoints = $user['points'];
    
    // Get badges the user doesn't have yet
    $stmt = $conn->prepare("
        SELECT b.* FROM badges b
        WHERE b.required_points <= ?
        AND NOT EXISTS (
            SELECT 1 FROM user_badges ub 
            WHERE ub.badge_id = b.id AND ub.user_id = ?
        )
    ");
    $stmt->execute([$userPoints, $userId]);
    $newBadges = $stmt->fetchAll();
    
    // Award new badges
    foreach ($newBadges as $badge) {
        $stmt = $conn->prepare("INSERT INTO user_badges (user_id, badge_id) VALUES (?, ?)");
        $stmt->execute([$userId, $badge['id']]);
    }
    
    return $newBadges;
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>
