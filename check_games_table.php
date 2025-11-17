<?php
require_once 'config.php';

// Check if games table exists and show structure
$result = $conn->query("SHOW COLUMNS FROM games");

if ($result) {
    echo "<h2>Struktur Tabel Games:</h2>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red;'>Error: Tabel games tidak ditemukan atau error query.</p>";
    echo "<p>" . $conn->error . "</p>";
}

// Show last 5 rows
echo "<h2>Data Terakhir di Tabel Games:</h2>";
$result = $conn->query("SELECT * FROM games ORDER BY id DESC LIMIT 5");
if ($result) {
    echo "<table border='1' cellpadding='10'>";
    $first = true;
    while ($row = $result->fetch_assoc()) {
        if ($first) {
            echo "<tr>";
            foreach ($row as $key => $value) {
                echo "<th>" . htmlspecialchars($key) . "</th>";
            }
            echo "</tr>";
            $first = false;
        }
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
}
?>
