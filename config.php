
<?php
// Enable verbose error reporting for local debugging (remove in production)
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'chesslearn');
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create uploads directory if not exists
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

// Database Connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// Helper Functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && intval($_SESSION['is_admin']) === 1;
}

function redirectTo($page) {
    header("Location: $page");
    exit();
}

function redirectWithMessage($page, $type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
    redirectTo($page);
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

function sanitize($data) {
    global $conn;
    return $conn->real_escape_string(trim($data));
}

function getUserById($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function logSystem($level, $message, $user_id = null, $username = null) {
    global $conn;
    // Validate user_id exists if provided
    if ($user_id) {
        $check = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $check->bind_param("i", $user_id);
        $check->execute();
        $result = $check->get_result();
        if ($result->num_rows === 0) {
            $user_id = null; // Set to NULL if user doesn't exist
        }
    }
    $stmt = $conn->prepare("INSERT INTO system_logs (log_level, message, user_id, username) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssis", $level, $message, $user_id, $username);
    $stmt->execute();
}

// Check if user is logged in for protected pages
function requireLogin() {
    if (!isLoggedIn()) {
        redirectTo('login.php');
    }
}

// Check if user is admin for admin pages
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        redirectTo('dashboard.php');
    }
}

// Update user's last login
function updateLastLogin($user_id) {
    global $conn;
    $stmt = $conn->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
}

// Handle file upload
function handleFileUpload($file, $user_id) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return null;
    }
    
    // Check file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        return null;
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'avatar_' . $user_id . '_' . time() . '.' . $extension;
    $filepath = UPLOAD_DIR . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    }
    
    return null;
}

// Update user avatar
function updateUserAvatar($user_id, $avatar_filename) {
    global $conn;
    $stmt = $conn->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
    $stmt->bind_param("si", $avatar_filename, $user_id);
    return $stmt->execute();
}

// Get total stats for admin dashboard
function getTotalStats() {
    global $conn;
    
    $stats = [];
    
    // Total users
    $result = $conn->query("SELECT COUNT(*) as total FROM users");
    $stats['total_users'] = $result->fetch_assoc()['total'];
    
    // Active games
    $result = $conn->query("SELECT COUNT(*) as total FROM games WHERE game_status = 'active'");
    $stats['active_games'] = $result->fetch_assoc()['total'];
    
    // Completed games
    $result = $conn->query("SELECT COUNT(*) as total FROM games WHERE game_status = 'completed'");
    $stats['completed_games'] = $result->fetch_assoc()['total'];
    
    // New users this month
    $result = $conn->query("SELECT COUNT(*) as total FROM users WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
    $stats['new_users_month'] = $result->fetch_assoc()['total'];
    
    return $stats;
}

// Get recent system logs
function getRecentLogs($limit = 10) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM system_logs ORDER BY created_at DESC LIMIT ?");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

?>
