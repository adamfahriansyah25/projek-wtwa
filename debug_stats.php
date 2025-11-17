<?php
require_once 'config.php';
requireLogin();

$user_id = $_SESSION['user_id'];

echo "<h2>Debug: Game History untuk User ID " . $user_id . "</h2>";

// Check game_history table
$result = $conn->query("SELECT * FROM game_history WHERE user_id = $user_id ORDER BY played_at DESC LIMIT 10");

if ($result) {
    echo "<h3>Data di game_history:</h3>";
    echo "<table border='1' cellpadding='10'>";
    
    if ($result->num_rows > 0) {
        // Header
        $first_row = $result->fetch_assoc();
        echo "<tr>";
        foreach ($first_row as $key => $value) {
            echo "<th>" . htmlspecialchars($key) . "</th>";
        }
        echo "</tr>";
        
        // Row pertama
        echo "<tr>";
        foreach ($first_row as $value) {
            echo "<td>" . htmlspecialchars($value) . "</td>";
        }
        echo "</tr>";
        
        // Rows berikutnya
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='10'>Tidak ada data game_history</td></tr>";
    }
    
    echo "</table>";
} else {
    echo "<p style='color:red;'>Error: " . $conn->error . "</p>";
}

// Check game_stats
echo "<h3>Data di game_stats:</h3>";
$stats = $conn->query("SELECT * FROM game_stats WHERE user_id = $user_id");
if ($stats && $stats->num_rows > 0) {
    $stat_row = $stats->fetch_assoc();
    echo "<pre>";
    print_r($stat_row);
    echo "</pre>";
} else {
    echo "<p>Tidak ada record di game_stats</p>";
}

// Check tabel yang ada
echo "<h3>Struktur tabel game_history:</h3>";
$cols = $conn->query("SHOW COLUMNS FROM game_history");
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while ($col = $cols->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $col['Field'] . "</td>";
    echo "<td>" . $col['Type'] . "</td>";
    echo "<td>" . $col['Null'] . "</td>";
    echo "<td>" . $col['Key'] . "</td>";
    echo "<td>" . $col['Default'] . "</td>";
    echo "</tr>";
}
echo "</table>";
?>
