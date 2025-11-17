
<?php
require_once 'config.php';
requireLogin();

// Get user data
$user = getUserById($_SESSION['user_id']);
$mode = $_GET['mode'] ?? 'computer';

// Get avatar URL
$avatar_url = $user['avatar_url'] ? 'uploads/' . $user['avatar_url'] : 'https://ui-avatars.com/api/?name=' . urlencode($user['username']) . '&background=d97706&color=fff';
?>
<!DOCTYPE html>
<html class="dark" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Bermain Catur - ChessLearn</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <link href="style.css" rel="stylesheet"/>
    
    <!-- Chessboard Dependencies -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/chessboard-js/1.0.0/chessboard-1.0.0.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/chess.js/0.10.3/chess.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/chessboard-js/1.0.0/chessboard-1.0.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js"></script>
    
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#d97706",
                        "background-light": "#f3f4f6",
                        "background-dark": "#111827",
                        "surface-light": "#ffffff",
                        "surface-dark": "#1f2937",
                        "surface-dark-secondary": "#374151",
                        "text-light-primary": "#111827",
                        "text-light-secondary": "#6b7280",
                        "text-dark-primary": "#f9fafb",
                        "text-dark-secondary": "#d1d5db",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                },
            },
        }
    </script>
    <style>
        .chessboard {
            width: 100% !important;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .move-row {
            display: grid;
            grid-template-columns: 40px 1fr 1fr;
            gap: 8px;
            padding: 4px 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .dark .move-row {
            border-bottom-color: #374151;
        }
        
        .move-number {
            color: #6b7280;
            font-weight: 500;
        }
        
        .dark .move-number {
            color: #9ca3af;
        }
        
        .move {
            padding: 2px 6px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .move:hover {
            background-color: #f3f4f6;
        }
        
        .dark .move:hover {
            background-color: #374151;
        }
        
        .white-move.active, .black-move.active {
            background-color: #d97706;
            color: white;
        }
        
        .player-card.active {
            border-color: #d97706;
            background-color: rgba(217, 119, 6, 0.1);
        }
        
        #moves-container {
            max-height: 300px;
            overflow-y: auto;
        }
        
        /* Chessboard square colors */
        .white-1e1d7 { background-color: #f0d9b5 !important; }
        .black-3c85d { background-color: #b58863 !important; }
        
        .highlight-white {
            box-shadow: inset 0 0 3px 3px yellow;
        }
        
        .highlight-black {
            box-shadow: inset 0 0 3px 3px blue;
        }
    </style>
    <style>
        /* Game end overlay styles */
        .game-end-overlay {
            position: fixed;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0,0,0,0.6);
            z-index: 9999;
            opacity: 0;
            pointer-events: none;
            transition: opacity 220ms ease;
        }

        .game-end-overlay.show {
            opacity: 1;
            pointer-events: all;
        }

        .game-end-card {
            background: linear-gradient(180deg, #0f172a, #111827);
            color: white;
            padding: 28px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.6);
            text-align: center;
            max-width: 420px;
            transform: translateY(12px) scale(0.98);
            transition: transform 260ms cubic-bezier(.2,.9,.2,1), opacity 260ms;
        }

        .game-end-overlay.show .game-end-card {
            transform: translateY(0) scale(1);
        }

        .game-end-winner {
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 6px;
            color: #ffd54f;
        }

        .game-end-sub {
            font-size: 14px;
            color: #d1d5db;
            margin-bottom: 16px;
        }

        .btn-end-action {
            background: #f59e0b;
            color: #071224;
            padding: 10px 14px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            margin: 6px;
        }

        .lose-shake {
            animation: shake 650ms ease-in-out both;
        }

        @keyframes shake {
            0% { transform: translateX(0); }
            20% { transform: translateX(-8px); }
            40% { transform: translateX(8px); }
            60% { transform: translateX(-6px); }
            80% { transform: translateX(6px); }
            100% { transform: translateX(0); }
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display">
    <div class="relative flex h-auto min-h-screen w-full flex-col overflow-x-hidden">
        <!-- Header -->
        <header class="flex items-center justify-between whitespace-nowrap border-b border-gray-200 dark:border-surface-dark px-6 md:px-10 py-3 bg-surface-light dark:bg-background-dark">
            <div class="flex items-center gap-4 text-text-light-primary dark:text-text-dark-primary">
                <div class="size-6 text-primary">
                    <svg fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                        <g clip-path="url(#clip0_6_535)">
                            <path clip-rule="evenodd" d="M47.2426 24L24 47.2426L0.757355 24L24 0.757355L47.2426 24ZM12.2426 21H35.7574L24 9.24264L12.2426 21Z" fill="currentColor" fill-rule="evenodd"></path>
                        </g>
                    </svg>
                </div>
                <h2 class="text-text-light-primary dark:text-text-dark-primary text-lg font-bold leading-tight tracking-[-0.015em]">ChessLearn</h2>
            </div>
            <div class="hidden md:flex flex-1 justify-end gap-8">
                <div class="flex items-center gap-9">
                    <a class="text-text-light-secondary dark:text-text-dark-secondary text-sm font-medium leading-normal hover:text-primary" href="dashboard.php">Dashboard</a>
                    <a class="text-text-light-secondary dark:text-text-dark-secondary text-sm font-medium leading-normal hover:text-primary" href="statistik.php">Statistik</a>
                    <a class="text-primary text-sm font-medium leading-normal" href="play.php">Main</a>
                </div>
                <div class="flex gap-2">
                    <a href="logout.php" class="flex max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 bg-gray-100 dark:bg-surface-dark text-text-light-secondary dark:text-text-dark-secondary gap-2 text-sm font-bold leading-normal tracking-[0.015em] min-w-0 px-2.5">
                        <span class="material-symbols-outlined text-xl">logout</span>
                    </a>
                </div>
                <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" style='background-image: url("<?php echo $avatar_url; ?>");'></div>
            </div>
        </header>

        <!-- Main Game Content -->
        <main class="flex flex-col lg:flex-row flex-1 p-4 md:p-6 gap-6 max-w-7xl mx-auto w-full">
            <!-- Left Column - Game Board -->
            <div class="flex-1 flex flex-col items-center">
                <!-- Game Info -->
                <div class="w-full max-w-2xl mb-4">
                    <div class="flex flex-col sm:flex-row p-4 bg-surface-light dark:bg-surface-dark rounded-lg w-full items-center justify-between gap-4">
                        <div class="flex gap-4 items-center">
                            <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-12" style='background-image: url("https://ui-avatars.com/api/?name=AI&background=6b7280&color=fff&size=100");'></div>
                            <div class="flex flex-col justify-center">
                                <p class="text-text-light-primary dark:text-text-dark-primary text-lg font-bold leading-tight tracking-[-0.015em]">
                                    <?php echo $mode === 'friend' ? 'Teman' : 'Komputer (Level 2)'; ?>
                                </p>
                                <p class="text-text-light-secondary dark:text-text-dark-secondary text-sm font-normal leading-normal">Rating: 1500</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 text-text-light-secondary dark:text-text-dark-secondary">
                            <span class="text-2xl">♙ ♙ ♙ ♙ ♙</span>
                        </div>
                        <div class="flex h-12 w-32 items-center justify-center rounded-lg px-3 bg-background-light dark:bg-background-dark border-2 border-gray-300 dark:border-gray-600">
                            <p id="blackTime" class="text-text-light-primary dark:text-text-dark-primary text-2xl font-bold leading-tight tracking-tighter">10:00</p>
                        </div>
                    </div>
                </div>

                <!-- Chess Board -->
                <div class="w-full aspect-square max-w-2xl bg-surface-light dark:bg-surface-dark rounded-lg p-4 shadow-2xl">
                    <div id="chessboard" class="chessboard"></div>
                </div>

                <!-- Current Player Info -->
                <div class="w-full max-w-2xl mt-4">
                    <div id="whitePlayer" class="player-card relative flex flex-col sm:flex-row p-4 bg-surface-light dark:bg-surface-dark rounded-lg w-full items-center justify-between gap-4 border-2 border-primary animate-pulse">
                        <div class="flex gap-4 items-center">
                            <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-12 border-2 border-primary" style='background-image: url("<?php echo $avatar_url; ?>");'></div>
                            <div class="flex flex-col justify-center">
                                <p class="text-text-light-primary dark:text-text-dark-primary text-lg font-bold leading-tight tracking-[-0.015em]"><?php echo htmlspecialchars($user['username']); ?></p>
                                <p class="text-text-light-secondary dark:text-text-dark-secondary text-sm font-normal leading-normal">Rating: <?php echo $user['elo_rating']; ?></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 text-text-light-secondary dark:text-text-dark-secondary">
                            <span class="text-2xl">♙ ♙</span>
                        </div>
                        <div class="flex h-12 w-32 items-center justify-center rounded-lg px-3 bg-background-light dark:bg-background-dark border-2 border-primary">
                            <p id="whiteTime" class="text-text-light-primary dark:text-text-dark-primary text-2xl font-bold leading-tight tracking-tighter">10:00</p>
                        </div>
                    </div>
                </div>

                <!-- Game Status -->
                <div class="w-full max-w-2xl mt-4 text-center">
                    <p id="status" class="text-text-light-primary dark:text-text-dark-primary text-lg font-semibold">Putih giliran</p>
                </div>
            </div>

            <!-- Right Column - Game Controls -->
            <div class="w-full lg:w-80 flex flex-col gap-6">
                <!-- Moves History -->
                <div class="bg-surface-light dark:bg-surface-dark rounded-lg p-4 flex flex-col gap-4">
                    <h3 class="text-text-light-primary dark:text-text-dark-primary font-bold text-lg">Riwayat Langkah</h3>
                    <div id="moves-container" class="flex-grow bg-background-light dark:bg-background-dark rounded-md p-3 overflow-y-auto max-h-64">
                        <div id="moves" class="text-sm">
                            <!-- Moves will be populated by JavaScript -->
                        </div>
                    </div>
                </div>

                <!-- Game Controls -->
                <div class="bg-surface-light dark:bg-surface-dark rounded-lg p-4 flex flex-col gap-4">
                    <h3 class="text-text-light-primary dark:text-text-dark-primary font-bold text-lg">Kontrol Permainan</h3>
                    <div class="flex flex-col gap-3">
                        <div class="grid grid-cols-2 gap-3">
                            <button id="resignBtn" class="flex w-full items-center justify-center gap-2 rounded-lg bg-red-500 hover:bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors">
                                <span class="material-symbols-outlined text-lg">flag</span>
                                Serah
                            </button>
                            <button id="drawBtn" class="flex w-full items-center justify-center gap-2 rounded-lg bg-yellow-500 hover:bg-yellow-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors">
                                <span class="material-symbols-outlined text-lg">handshake</span>
                                Tawar Seri
                            </button>
                        </div>
                        <button id="newGameBtn" class="flex w-full items-center justify-center gap-2 rounded-lg bg-primary hover:bg-primary/90 px-4 py-2.5 text-sm font-semibold text-white transition-colors">
                            <span class="material-symbols-outlined text-lg">add_circle</span>
                            Permainan Baru
                        </button>
                        <button id="analyzeBtn" class="flex w-full items-center justify-center gap-2 rounded-lg bg-blue-500 hover:bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors">
                            <span class="material-symbols-outlined text-lg">analytics</span>
                            Analisis
                        </button>
                    </div>
                </div>

                <!-- Game Settings -->
                <div class="bg-surface-light dark:bg-surface-dark rounded-lg p-4 flex flex-col gap-4">
                    <h3 class="text-text-light-primary dark:text-text-dark-primary font-bold text-lg">Pengaturan</h3>
                    <div class="flex flex-col gap-3">
                        <div>
                            <label class="text-text-light-secondary dark:text-text-dark-secondary text-sm font-medium">Mode Permainan</label>
                            <select id="gameMode" class="w-full mt-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-surface-dark-secondary p-2 text-sm">
                                <option value="computer" <?php echo $mode === 'computer' ? 'selected' : ''; ?>>Lawan Komputer</option>
                                <option value="friend" <?php echo $mode === 'friend' ? 'selected' : ''; ?>>Tantang Teman</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-text-light-secondary dark:text-text-dark-secondary text-sm font-medium">Level Kesulitan</label>
                            <select id="difficulty" class="w-full mt-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-surface-dark-secondary p-2 text-sm">
                                <option value="1">Pemula</option>
                                <option value="2" selected>Menengah</option>
                                <option value="3">Lanjutan</option>
                                <option value="4">Expert</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-text-light-secondary dark:text-text-dark-secondary text-sm font-medium">Waktu (menit)</label>
                            <select id="timeControl" class="w-full mt-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-surface-dark-secondary p-2 text-sm">
                                <option value="1">1 menit</option>
                                <option value="3">3 menit</option>
                                <option value="5">5 menit</option>
                                <option value="10" selected>10 menit</option>
                                <option value="15">15 menit</option>
                                <option value="30">30 menit</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- JavaScript -->
    <script>
        class ChessGame {
            constructor() {
                this.board = null;
                this.game = new Chess();
                this.boardEl = document.getElementById('chessboard');
                this.statusEl = document.getElementById('status');
                this.movesEl = document.getElementById('moves');
                this.whitePlayerEl = document.getElementById('whitePlayer');
                this.blackPlayerEl = document.getElementById('blackPlayer');
                this.whiteTimeEl = document.getElementById('whiteTime');
                this.blackTimeEl = document.getElementById('blackTime');
                
                this.gameMode = document.getElementById('gameMode').value;
                this.difficulty = parseInt(document.getElementById('difficulty').value);
                this.timeControl = parseInt(document.getElementById('timeControl').value);
                
                this.whiteTime = this.timeControl * 60;
                this.blackTime = this.timeControl * 60;
                this.timer = null;
                this.currentPlayer = 'w';
                
                this.init();
            }

            init() {
                this.initBoard();
                this.initTimers();
                this.updateStatus();
                this.setupEventListeners();
            }

            initBoard() {
                const config = {
                    draggable: true,
                    position: 'start',
                    onDragStart: this.onDragStart.bind(this),
                    onDrop: this.onDrop.bind(this),
                    onSnapEnd: this.onSnapEnd.bind(this),
                    onMouseoutSquare: this.onMouseoutSquare.bind(this),
                    onMouseoverSquare: this.onMouseoverSquare.bind(this),
                    pieceTheme: 'https://chessboardjs.com/img/chesspieces/wikipedia/{piece}.png',
                    orientation: 'white'
                };
                
                this.board = Chessboard('chessboard', config);
            }

            setupEventListeners() {
                document.getElementById('resignBtn').addEventListener('click', () => this.resign());
                document.getElementById('drawBtn').addEventListener('click', () => this.offerDraw());
                document.getElementById('newGameBtn').addEventListener('click', () => this.newGame());
                document.getElementById('analyzeBtn').addEventListener('click', () => this.analyzeGame());
                
                document.getElementById('gameMode').addEventListener('change', (e) => {
                    this.gameMode = e.target.value;
                    this.newGame();
                });
                
                document.getElementById('difficulty').addEventListener('change', (e) => {
                    this.difficulty = parseInt(e.target.value);
                });
                
                document.getElementById('timeControl').addEventListener('change', (e) => {
                    this.timeControl = parseInt(e.target.value);
                    this.newGame();
                });
            }

            initTimers() {
                this.updateTimers();
                this.timer = setInterval(() => {
                    if (this.currentPlayer === 'w') {
                        this.whiteTime--;
                    } else {
                        this.blackTime--;
                    }
                    this.updateTimers();
                    
                    if (this.whiteTime <= 0 || this.blackTime <= 0) {
                        this.handleTimeout();
                    }
                }, 1000);
            }

            updateTimers() {
                this.whiteTimeEl.textContent = this.formatTime(this.whiteTime);
                this.blackTimeEl.textContent = this.formatTime(this.blackTime);
            }

            formatTime(seconds) {
                const mins = Math.floor(seconds / 60);
                const secs = seconds % 60;
                return `${mins}:${secs.toString().padStart(2, '0')}`;
            }

            onDragStart(source, piece, position, orientation) {
                if (this.game.game_over()) return false;

                if ((this.game.turn() === 'w' && piece.search(/^b/) !== -1) ||
                    (this.game.turn() === 'b' && piece.search(/^w/) !== -1)) {
                    return false;
                }
                
                // Highlight possible moves
                const moves = this.game.moves({
                    square: source,
                    verbose: true
                });
                
                moves.forEach(move => {
                    const square = document.querySelector(`[data-square="${move.to}"]`);
                    if (square) {
                        square.classList.add('highlight-white');
                    }
                });
            }

            onDrop(source, target) {
                // Remove highlights
                this.removeHighlights();
                
                const move = this.game.move({
                    from: source,
                    to: target,
                    promotion: 'q'
                });

                if (move === null) return 'snapback';

                this.updateStatus();
                this.playMoveSound();
                
                // Computer move if playing against computer
                if (this.gameMode === 'computer' && !this.game.game_over() && this.game.turn() === 'b') {
                    setTimeout(() => this.makeComputerMove(), 500);
                } else {
                    this.switchPlayer();
                }
            }

            onSnapEnd() {
                this.board.position(this.game.fen());
            }

            onMouseoverSquare(square, piece) {
                if (this.game.game_over()) return;
                
                const moves = this.game.moves({
                    square: square,
                    verbose: true
                });
                
                if (moves.length === 0) return;
                
                moves.forEach(move => {
                    const squareEl = document.querySelector(`[data-square="${move.to}"]`);
                    if (squareEl) {
                        squareEl.classList.add('highlight-white');
                    }
                });
            }

            onMouseoutSquare(square, piece) {
                this.removeHighlights();
            }

            removeHighlights() {
                const squares = document.querySelectorAll('.highlight-white, .highlight-black');
                squares.forEach(square => {
                    square.classList.remove('highlight-white', 'highlight-black');
                });
            }

            makeComputerMove() {
                const moves = this.game.moves();
                if (moves.length === 0) return;
                
                // Simple AI based on difficulty
                let move;
                if (this.difficulty === 1) {
                    // Random moves for beginner
                    move = moves[Math.floor(Math.random() * moves.length)];
                } else {
                    // Slightly better AI for higher levels
                    const goodMoves = moves.filter(m => 
                        m.includes('x') || // Captures
                        m.includes('+') || // Checks
                        m === 'O-O' || m === 'O-O-O' // Castling
                    );
                    
                    move = goodMoves.length > 0 ? 
                        goodMoves[Math.floor(Math.random() * goodMoves.length)] : 
                        moves[Math.floor(Math.random() * moves.length)];
                }
                
                this.game.move(move);
                this.board.position(this.game.fen());
                this.updateStatus();
                this.playMoveSound();
                this.switchPlayer();
            }

            updateStatus() {
                let status = '';
                let moveColor = 'Putih';

                if (this.game.turn() === 'b') {
                    moveColor = 'Hitam';
                }

                if (this.game.in_checkmate()) {
                    status = 'Permainan berakhir, ' + moveColor + ' skakmat!';
                    // winner is opposite of the side to move
                    const winner = this.game.turn() === 'w' ? 'Hitam' : 'Putih';
                    this.endGame(winner);
                } else if (this.game.in_draw()) {
                    status = 'Permainan berakhir, seri!';
                    this.endGame('Seri');
                } else {
                    status = moveColor + ' giliran';

                    if (this.game.in_check()) {
                        status += ', ' + moveColor + ' dalam skak';
                    }
                }

                this.statusEl.textContent = status;
                this.updateMoveHistory();
            }

            updateMoveHistory() {
                const moves = this.game.history();
                this.movesEl.innerHTML = '';
                
                for (let i = 0; i < moves.length; i += 2) {
                    const moveNumber = Math.floor(i / 2) + 1;
                    const whiteMove = moves[i];
                    const blackMove = moves[i + 1] || '';
                    
                    const row = document.createElement('div');
                    row.className = 'move-row';
                    row.innerHTML = `
                        <span class="move-number">${moveNumber}.</span>
                        <span class="move white-move ${i === moves.length - 1 && this.game.turn() === 'b' ? 'active' : ''}">${whiteMove}</span>
                        <span class="move black-move ${i + 1 === moves.length - 1 && this.game.turn() === 'w' ? 'active' : ''}">${blackMove}</span>
                    `;
                    this.movesEl.appendChild(row);
                }
                
                this.movesEl.scrollTop = this.movesEl.scrollHeight;
            }

            switchPlayer() {
                this.currentPlayer = this.game.turn();
                
                if (this.currentPlayer === 'w') {
                    this.whitePlayerEl.classList.add('active');
                    this.blackPlayerEl.classList.remove('active');
                } else {
                    this.blackPlayerEl.classList.add('active');
                    this.whitePlayerEl.classList.remove('active');
                }
            }

            handleTimeout() {
                clearInterval(this.timer);
                const winner = this.currentPlayer === 'w' ? 'Hitam' : 'Putih';
                this.statusEl.textContent = `Waktu habis! ${winner} menang!`;
                this.endGame(winner);
            }

            endGame(winner = null) {
                clearInterval(this.timer);
                this.board.orientation('white');
                // Show end-game animation/overlay
                // save to server first (async), then show animation
                this.saveGameToServer(winner).finally(() => {
                    this.showEndAnimation(winner);
                });
            }

            async saveGameToServer(winner) {
                try {
                    const payload = {
                        mode: this.gameMode || 'computer',
                        difficulty: this.difficulty || 0,
                        timeControl: this.timeControl || 0,
                        pgn: this.game.pgn(),
                        fen: this.game.fen(),
                        moves: this.game.history(),
                        winner: winner || null
                    };

                    console.log('Saving game:', payload);

                        const res = await fetch('save_game.php', {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(payload)
                        });

                        const text = await res.text();
                        console.log('Raw save response text:', text);

                        let data = null;
                        try {
                            data = JSON.parse(text);
                        } catch (parseErr) {
                            console.error('Failed to parse JSON from save_game.php response', parseErr);
                            // Show raw response to user so they can copy/paste for debugging
                            alert('Error: Could not save game - server returned non-JSON response:\n' + text);
                            throw new Error('Non-JSON response from server');
                        }

                        console.log('Save response (JSON):', data);

                        if (!data.success) {
                            console.error('Failed to save game:', data);
                            alert('Error: ' + (data.message || 'Unknown error saving game'));
                        } else {
                            console.log('Game saved successfully, id:', data.game_id);
                        }
                        return data;
                } catch (err) {
                    console.error('Error saving game:', err);
                    alert('Error: Could not save game - ' + err.message);
                    throw err;
                }
            }

            showEndAnimation(winner) {
                const self = this;
                let overlay = document.querySelector('.game-end-overlay');
                if (!overlay) {
                    overlay = document.createElement('div');
                    overlay.className = 'game-end-overlay';
                    overlay.innerHTML = `
                        <div class="game-end-card" role="dialog" aria-modal="true">
                            <div class="game-end-winner">--</div>
                            <div class="game-end-sub">Hasil akhir pertandingan</div>
                            <div>
                                <button id="endNewBtn" class="btn-end-action">Permainan Baru</button>
                                <button id="endCloseBtn" class="btn-end-action" style="background:#374151;color:#fff;">Tutup</button>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(overlay);
                }

                const card = overlay.querySelector('.game-end-card');
                const title = overlay.querySelector('.game-end-winner');

                if (winner && winner !== 'Seri') {
                    title.textContent = `${winner} menang!`;
                    // confetti celebration
                    if (typeof confetti === 'function') {
                        confetti({ particleCount: 160, spread: 140, origin: { y: 0.6 } });
                        setTimeout(() => confetti({ particleCount: 120, spread: 120, origin: { y: 0.6 } }), 300);
                    }
                } else {
                    title.textContent = 'Permainan seri';
                    // slight shake to indicate no winner
                    card.classList.add('lose-shake');
                    setTimeout(() => card.classList.remove('lose-shake'), 700);
                }

                overlay.classList.add('show');

                const newBtn = overlay.querySelector('#endNewBtn');
                const closeBtn = overlay.querySelector('#endCloseBtn');

                newBtn.onclick = function () {
                    overlay.classList.remove('show');
                    setTimeout(() => overlay.remove(), 300);
                    // start a new game
                    self.newGame();
                };

                closeBtn.onclick = function () {
                    overlay.classList.remove('show');
                    setTimeout(() => overlay.remove(), 300);
                };
            }

            playMoveSound() {
                // Simple move sound simulation
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                
                oscillator.frequency.value = 800;
                oscillator.type = 'sine';
                
                gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);
                
                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.1);
            }

            // Game controls
            resign() {
                if (confirm('Apakah Anda yakin ingin menyerah?')) {
                    clearInterval(this.timer);
                    const winner = this.currentPlayer === 'w' ? 'Hitam' : 'Putih';
                    this.statusEl.textContent = `Permainan berakhir! ${winner} menang karena lawan menyerah!`;
                    this.endGame(winner);
                }
            }

            offerDraw() {
                if (confirm('Tawarkan seri kepada lawan?')) {
                    if (this.gameMode === 'computer') {
                        if (Math.random() > 0.5) {
                            this.statusEl.textContent = 'Komputer menerima tawaran seri! Permainan berakhir seri!';
                            this.endGame('Seri');
                        } else {
                            alert('Komputer menolak tawaran seri. Permainan dilanjutkan.');
                        }
                    } else {
                        alert('Tawaran seri telah dikirim ke lawan.');
                    }
                }
            }

            newGame() {
                if (confirm('Mulai permainan baru?')) {
                    clearInterval(this.timer);
                    this.game = new Chess();
                    this.whiteTime = this.timeControl * 60;
                    this.blackTime = this.timeControl * 60;
                    this.currentPlayer = 'w';
                    this.board.position('start');
                    this.initTimers();
                    this.updateStatus();
                    this.switchPlayer();
                    this.removeHighlights();
                }
            }

            analyzeGame() {
                const fen = this.game.fen();
                const pgn = this.game.pgn();
                
                let analysis = `Posisi saat ini:\nFEN: ${fen}\n\n`;
                analysis += `PGN: ${pgn || 'Tidak ada riwayat langkah'}\n\n`;
                analysis += `Status: ${this.statusEl.textContent}\n`;
                analysis += `Giliran: ${this.game.turn() === 'w' ? 'Putih' : 'Hitam'}\n`;
                analysis += `Dalam skak: ${this.game.in_check() ? 'Ya' : 'Tidak'}\n`;
                analysis += `Skakmat: ${this.game.in_checkmate() ? 'Ya' : 'Tidak'}\n`;
                analysis += `Seri: ${this.game.in_draw() ? 'Ya' : 'Tidak'}`;
                
                alert('Analisis Posisi:\n\n' + analysis);
            }
        }

        // Initialize game when page loads
        document.addEventListener('DOMContentLoaded', function() {
            window.chessGame = new ChessGame();
        });

        // Theme toggle
        function toggleTheme() {
            const html = document.documentElement;
            html.classList.toggle('dark');
            localStorage.setItem('theme', html.classList.contains('dark') ? 'dark' : 'light');
        }

        // Load saved theme
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            document.documentElement.classList.toggle('dark', savedTheme === 'dark');
        });
    </script>
</body>
</html>
