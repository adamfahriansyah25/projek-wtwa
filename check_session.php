<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Debug</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; border-radius: 10px; margin: 10px 0; }
        h2 { color: #667eea; }
        pre { background: #f0f0f0; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <h1>🔍 Session & Database Debug</h1>
    
    <div class="box">
        <h2>1. PHP Session Data</h2>
        <pre><?php print_r($_SESSION); ?></pre>
        <?php if (isset($_SESSION['user_id'])): ?>
            <p class="success">✅ User ID: <?= $_SESSION['user_id'] ?></p>
            <p class="success">✅ Username: <?= $_SESSION['username'] ?? 'N/A' ?></p>
        <?php else: ?>
            <p class="error">❌ NOT LOGGED IN!</p>
        <?php endif; ?>
    </div>
    
    <div class="box">
        <h2>2. Database Connection</h2>
        <?php
        require_once 'config.php';
        if ($conn) {
            echo '<p class="success">✅ Database connected!</p>';
            
            // Cek user exists
            if (isset($_SESSION['user_id'])) {
                $user_id = $_SESSION['user_id'];
                $stmt = $conn->prepare("SELECT id, username, email FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    echo '<p class="success">✅ User exists in database:</p>';
                    echo '<pre>';
                    print_r($result->fetch_assoc());
                    echo '</pre>';
                } else {
                    echo '<p class="error">❌ User ID not found in database!</p>';
                }
            }
            
            // Cek table structure
            echo '<h3>game_history table structure:</h3>';
            $result = $conn->query("DESCRIBE game_history");
            echo '<pre>';
            while ($row = $result->fetch_assoc()) {
                echo $row['Field'] . ' - ' . $row['Type'] . "\n";
            }
            echo '</pre>';
            
            // Cek last game
            if (isset($_SESSION['user_id'])) {
                echo '<h3>Last 5 games:</h3>';
                $user_id = $_SESSION['user_id'];
                $stmt = $conn->prepare("SELECT * FROM game_history WHERE user_id = ? ORDER BY id DESC LIMIT 5");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    echo '<pre>';
                    while ($row = $result->fetch_assoc()) {
                        print_r($row);
                    }
                    echo '</pre>';
                } else {
                    echo '<p>No games found.</p>';
                }
            }
            
        } else {
            echo '<p class="error">❌ Database connection failed!</p>';
        }
        ?>
    </div>
    
    <div class="box">
        <h2>3. Test Save Game</h2>
        <button onclick="testSave()">🧪 Test Save Game</button>
        <div id="testResult"></div>
    </div>
    
    <script>
    async function testSave() {
        const result = document.getElementById('testResult');
        result.innerHTML = '<p>⏳ Testing...</p>';
        
        try {
            const response = await fetch('save_game.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'opponent=TEST&result=win&game_type=ai&moves_count=99&duration=123'
            });
            
            const text = await response.text();
            result.innerHTML = '<h3>Response:</h3><pre>' + text + '</pre>';
            
            try {
                const json = JSON.parse(text);
                if (json.success) {
                    result.innerHTML += '<p class="success">✅ Save successful!</p>';
                } else {
                    result.innerHTML += '<p class="error">❌ Save failed: ' + json.message + '</p>';
                }
            } catch (e) {
                result.innerHTML += '<p class="error">❌ Not JSON response!</p>';
            }
        } catch (error) {
            result.innerHTML = '<p class="error">❌ Error: ' + error.message + '</p>';
        }
    }
    </script>
</body>
</html>