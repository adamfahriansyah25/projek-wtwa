<?php
session_start();
header('Content-Type: application/json');

// Enable error logging untuk debugging
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', 'save_game_debug.log');

// Log semua request
$log_data = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'post_data' => $_POST,
    'session' => $_SESSION
];
file_put_contents('save_game_debug.log', print_r($log_data, true) . "\n\n", FILE_APPEND);

// Koneksi database
require_once 'config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    $response = ['success' => false, 'message' => 'Unauthorized - Please login', 'session' => $_SESSION];
    echo json_encode($response);
    file_put_contents('save_game_debug.log', "UNAUTHORIZED\n\n", FILE_APPEND);
    exit;
}

$user_id = $_SESSION['user_id'];
file_put_contents('save_game_debug.log', "USER_ID: $user_id\n", FILE_APPEND);

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Ambil data dari JSON
     $input = json_decode(file_get_contents('php://input'), true);
        $opponent = $input['opponent'] ?? 'Unknown';
        $result = $input['result'] ?? 'draw'; 
        $game_type = $input['game_type'] ?? 'ai';
        $moves_count = intval($input['moves_count'] ?? 0);
        $duration = intval($input['duration'] ?? 0);
        file_put_contents('save_game_debug.log', "DATA: opponent=$opponent, result=$result, type=$game_type, moves=$moves_count, dur=$duration\n", FILE_APPEND);
        
        // Validasi result
        $valid_results = ['win', 'loss', 'draw'];
        if (!in_array($result, $valid_results)) {
            $result = 'draw';
        }
        
        // Validasi game_type
        $valid_types = ['ai', 'multiplayer'];
        if (!in_array($game_type, $valid_types)) {
            $game_type = 'ai';
        }
        
        // 1. Insert ke game_history
        $stmt = $conn->prepare("
            INSERT INTO game_history 
            (user_id, opponent, result, game_type, moves_count, duration, played_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        
        $stmt->bind_param("isssii", $user_id, $opponent, $result, $game_type, $moves_count, $duration);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $game_id = $conn->insert_id;
        file_put_contents('save_game_debug.log', "INSERTED game_id: $game_id\n", FILE_APPEND);
        $stmt->close();
        
        // 2. Update atau Insert game_stats
        $stmt = $conn->prepare("SELECT id FROM game_stats WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stats_exist = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        
        file_put_contents('save_game_debug.log', "STATS_EXIST: " . ($stats_exist ? 'YES' : 'NO') . "\n", FILE_APPEND);
        
        if (!$stats_exist) {
            // Insert record baru
            $stmt = $conn->prepare("
                INSERT INTO game_stats (user_id, total_games, wins, losses, draws) 
                VALUES (?, 0, 0, 0, 0)
            ");
            $stmt->bind_param("i", $user_id);
            if (!$stmt->execute()) {
                throw new Exception("Stats insert failed: " . $stmt->error);
            }
            $stmt->close();
            file_put_contents('save_game_debug.log', "CREATED new stats record\n", FILE_APPEND);
        }
        
        // Update statistik
        $updates = ["total_games = total_games + 1"];
        
        switch ($result) {
            case 'win':
                $updates[] = "wins = wins + 1";
                break;
            case 'loss':
                $updates[] = "losses = losses + 1";
                break;
            case 'draw':
                $updates[] = "draws = draws + 1";
                break;
        }
        
        $update_sql = "UPDATE game_stats SET " . implode(", ", $updates) . " WHERE user_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("i", $user_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Stats update failed: " . $stmt->error);
        }
        
        $affected = $stmt->affected_rows;
        file_put_contents('save_game_debug.log', "STATS UPDATED: $affected rows\n", FILE_APPEND);
        $stmt->close();
        
        // Success response
        $response = [
            'success' => true, 
            'message' => 'Game saved successfully',
            'game_id' => $game_id,
            'result' => $result,
            'debug' => [
                'user_id' => $user_id,
                'stats_updated' => $affected
            ]
        ];
        
        file_put_contents('save_game_debug.log', "SUCCESS\n\n", FILE_APPEND);
        echo json_encode($response);
        
    } catch (Exception $e) {
        // Error response
        $error_msg = $e->getMessage();
        file_put_contents('save_game_debug.log', "ERROR: $error_msg\n\n", FILE_APPEND);
        
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to save game: ' . $error_msg
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid request method: ' . $_SERVER['REQUEST_METHOD']
    ]);
}

$conn->close();
?>