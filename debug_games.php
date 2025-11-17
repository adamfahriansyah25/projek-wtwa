<?php
require_once 'config.php';
requireLogin();

echo "<h2>Debug Game Data</h2>";

// Check games table
$result = $conn->query("SELECT * FROM games WHERE white_player_id = {$_SESSION['user_id']} ORDER BY created_at DESC");
echo "<h3>Games Found: " . $result->num_rows . "</h3>";

while ($game = $result->fetch_assoc()) {
    echo "<pre>";
    print_r($game);
    echo "</pre>";
}
?>