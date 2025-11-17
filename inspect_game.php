<?php
session_start();
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 Game Inspector</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="game-container">
        <!-- HEADER -->
        <div class="game-header">
            <div class="header-left">
                <h1>🔍 Game Inspector</h1>
                <span>Database & Session Monitoring Tool</span>
            </div>
            <div class="header-right">
                <button onclick="location.reload()" class="btn btn-secondary">🔄 Refresh</button>
                <a href="index.html" class="btn btn-primary">🎮 Back to Game</a>
            </div>
        </div>

        <!-- SESSION STATUS -->
        <div class="mode-selection">
            <h2>🔐 Session Status</h2>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="message success" style="display:block;">
                    ✅ USER LOGGED IN - ID: <?php echo $_SESSION['user_id']; ?> | Username: <?php echo $_SESSION['username'] ?? 'N/A'; ?>
                </div>
            <?php else: ?>
                <div class="message error" style="display:block;">
                    ❌ NOT LOGGED IN - Please login first
                </div>
            <?php endif; ?>
        </div>

        <!-- DATABASE STATUS -->
        <div class="mode-selection" style="margin-top:20px;">
            <h2>🗄️ Database Connection</h2>
            
            <?php if ($conn && !$conn->connect_error): ?>
                <div class="message success" style="display:block;">
                    ✅ DATABASE CONNECTED<br>
                    <small>Host: <?php echo DB_HOST; ?> | Database: <?php echo DB_NAME; ?> | Version: <?php echo $conn->server_info; ?></small>
                </div>
            <?php else: ?>
                <div class="message error" style="display:block;">
                    ❌ DATABASE CONNECTION FAILED
                </div>
            <?php endif; ?>
        </div>

        <!-- USER STATISTICS -->
        <?php if (isset($_SESSION['user_id']) && $conn): ?>
            <?php
            $user_id = $_SESSION['user_id'];
            
            // Get game stats
            $stmt = $conn->prepare("SELECT * FROM game_stats WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stats = $stmt->get_result()->fetch_assoc();
            ?>
            
            <?php if ($stats): ?>
                <div class="stats-cards" style="margin-top:20px; padding:0 20px;">
                    <div class="stat-card">
                        <h3><?php echo $stats['total_games']; ?></h3>
                        <p>Total Games</p>
                    </div>
                    <div class="stat-card win">
                        <h3><?php echo $stats['wins']; ?></h3>
                        <p>Wins</p>
                    </div>
                    <div class="stat-card loss">
                        <h3><?php echo $stats['losses']; ?></h3>
                        <p>Losses</p>
                    </div>
                    <div class="stat-card draw">
                        <h3><?php echo $stats['draws']; ?></h3>
                        <p>Draws</p>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- GAME HISTORY -->
        <?php if (isset($_SESSION['user_id']) && $conn): ?>
            <div class="history-container" style="background:rgba(255,255,255,0.95); padding:30px; border-radius:20px; margin-top:20px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
                <h3>📜 Game History</h3>
                
                <?php
                $stmt = $conn->prepare("
                    SELECT * FROM game_history 
                    WHERE user_id = ? 
                    ORDER BY played_at DESC 
                    LIMIT 20
                ");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $history = $stmt->get_result();
                ?>
                
                <?php if ($history->num_rows > 0): ?>
                    <?php while ($game = $history->fetch_assoc()): ?>
                        <div class="history-item">
                            <strong>
                                <?php if ($game['result'] === 'win'): ?>
                                    ✅ WIN
                                <?php elseif ($game['result'] === 'loss'): ?>
                                    ❌ LOSS
                                <?php else: ?>
                                    🤝 DRAW
                                <?php endif; ?>
                            </strong>
                            vs <?php echo $game['opponent']; ?> 
                            (<?php echo strtoupper($game['game_type']); ?>)
                            <br>
                            <small>
                                ID: <?php echo $game['id']; ?> | 
                                Moves: <?php echo $game['moves_count']; ?> | 
                                Duration: <?php echo gmdate("i:s", $game['duration']); ?> | 
                                <?php echo date('d/m/Y H:i', strtotime($game['played_at'])); ?>
                            </small>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="message" style="display:block; background:#fff3cd; color:#856404;">
                        ⚠️ No game history found
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- DATABASE TABLES -->
        <div class="mode-selection" style="margin-top:20px;">
            <h2>🗂️ Database Tables</h2>
            
            <?php if ($conn): ?>
                <?php
                $tables = ['users', 'game_stats', 'game_history', 'active_games'];
                foreach ($tables as $table):
                    $result = $conn->query("SHOW TABLES LIKE '$table'");
                    $exists = $result && $result->num_rows > 0;
                    
                    if ($exists):
                        $count = $conn->query("SELECT COUNT(*) as count FROM $table")->fetch_assoc()['count'];
                ?>
                        <div class="message success" style="display:block; margin:10px 0;">
                            ✅ <?php echo $table; ?> - <?php echo $count; ?> records
                        </div>
                <?php else: ?>
                        <div class="message error" style="display:block; margin:10px 0;">
                            ❌ <?php echo $table; ?> - Not found
                        </div>
                <?php 
                    endif;
                endforeach;
                ?>
            <?php endif; ?>
        </div>

        <!-- ACTIONS -->
        <div class="game-controls" style="margin-top:30px;">
            <button onclick="location.reload()" class="btn btn-secondary">🔄 Refresh Data</button>
            <a href="index.html" class="btn btn-primary">🎮 Back to Game</a>
            <a href="config.php"btn btn-secondary">🔧 Check Connection</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <button onclick="testSaveGame()" class="btn btn-primary">🧪 Test Save Game</button>
                <button onclick="if(confirm('Clear all history?')) clearHistory()" class="btn btn-danger">🗑️ Clear History</button>
            <?php endif; ?>
        </div>

        <div id="testResult" style="margin-top:20px;"></div>
    </div>

    <script>
        async function testSaveGame() {
            const result = document.getElementById('testResult');
            result.innerHTML = '<div class="message" style="display:block; background:#d1ecf1; color:#0c5460;">⏳ Testing save game...</div>';
            
            try {
                const response = await fetch('save_game.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'opponent=TEST_AI&result=win&game_type=ai&moves_count=42&duration=300'
                });
                
                const text = await response.text();
                console.log('Response:', text);
                
                try {
                    const json = JSON.parse(text);
                    if (json.success) {
                        result.innerHTML = '<div class="message success" style="display:block;">✅ Test successful! Game ID: ' + json.game_id + '</div>';
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        result.innerHTML = '<div class="message error" style="display:block;">❌ Test failed: ' + json.message + '</div>';
                    }
                } catch (e) {
                    result.innerHTML = '<div class="message error" style="display:block;">❌ Invalid JSON response</div>';
                }
            } catch (error) {
                result.innerHTML = '<div class="message error" style="display:block;">❌ Error: ' + error.message + '</div>';
            }
        }
        
        async function clearHistory() {
            const result = document.getElementById('testResult');
            result.innerHTML = '<div class="message" style="display:block; background:#fff3cd; color:#856404;">⏳ Clearing history...</div>';
            
            try {
                const response = await fetch('clear_history.php', { method: 'POST' });
                const data = await response.json();
                
                if (data.success) {
                    result.innerHTML = '<div class="message success" style="display:block;">✅ History cleared!</div>';
                    setTimeout(() => location.reload(), 1500);
                } else {
                    result.innerHTML = '<div class="message error" style="display:block;">❌ Failed: ' + data.message + '</div>';
                }
            } catch (error) {
                result.innerHTML = '<div class="message error" style="display:block;">❌ Error: ' + error.message + '</div>';
            }
        }
    </script>
</body>
</html>