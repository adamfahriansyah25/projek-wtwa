<?php
    require_once 'config.php';
    requireLogin();

    $user = getUserById($_SESSION['user_id']);
    if (!$user) {
        session_unset();
        session_destroy();
        redirectWithMessage('login.php', 'error', 'Akun tidak ditemukan. Silakan login kembali.');
    }

    // --- MODIFIKASI DIMULAI DI SINI ---

    // Fetch user stats: Mengambil ringkasan statistik langsung dari tabel game_stats
    $stmt = $conn->prepare("SELECT total_games, wins, losses, draws FROM game_stats WHERE user_id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    // Gunakan nama variabel baru untuk menghindari konflik
    $user_stats_db = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Inisialisasi variabel dengan data dari game_stats
    $user_total = intval($user_stats_db['total_games'] ?? 0);
    $user_wins = intval($user_stats_db['wins'] ?? 0);
    $user_draws = intval($user_stats_db['draws'] ?? 0);
    // Ambil losses langsung dari DB, tidak perlu dihitung
    $user_losses = intval($user_stats_db['losses'] ?? 0); 
    
    // --- MODIFIKASI UNTUK STATS RINGKASAN SELESAI ---
    
    $avatar_url = (!empty($user['avatar_url'])) ? 'uploads/' . $user['avatar_url'] : 'https://ui-avatars.com/api/?name=' . urlencode($user['username'] ?? 'User') . '&background=d97706&color=fff';

    if ($user_total === 0) {
        $pct_win = $pct_draw = $pct_loss = 0;
        $win_rate_text = '—';
    } else {
        $pct_win = intval(round(($user_wins / $user_total) * 100));
        $pct_draw = intval(round(($user_draws / $user_total) * 100));
        // Hitung pct_loss dari sisa 100% untuk memastikan total 100%
        $pct_loss = 100 - $pct_win - $pct_draw; 
        $win_rate_text = $pct_win . '%';
    }

    // Recent games: Ganti query dari table 'games' ke table 'game_history'
    // Memilih kolom yang relevan dari game_history (opponent, result, moves_count, played_at)
    $recent_stmt = $conn->prepare("SELECT id, opponent, result, moves_count, played_at FROM game_history WHERE user_id = ? ORDER BY played_at DESC LIMIT 10");
    $recent_stmt->bind_param('i', $_SESSION['user_id']);
    $recent_stmt->execute();
    $recent_history_db = $recent_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $recent_stmt->close();
    
    // Map data dari game_history ke array $recent_games
    // agar kompatibel dengan loop ELO changes dan tampilan HTML
    $recent_games = [];
    foreach ($recent_history_db as $game_hist) {
        // Kolom 'result' dari game_history sudah berisi: 'win', 'loss', atau 'draw'
        $game_status = strtolower($game_hist['result']); 
        
        $game_hist['game_status'] = $game_status;
        $game_hist['moves'] = $game_hist['moves_count']; // Map moves_count ke moves
        $game_hist['created_at'] = $game_hist['played_at']; // Map played_at ke created_at

        // Tentukan 'winner_id' untuk logic ELO change:
        if ($game_status === 'win') {
            $game_hist['winner_id'] = $_SESSION['user_id']; // Saya menang
        } elseif ($game_status === 'loss') {
            $game_hist['winner_id'] = 999; // ID dummy untuk menandakan kekalahan (bukan user)
        } else { // draw
            $game_hist['winner_id'] = NULL; // Draw
        }

        $recent_games[] = $game_hist;
    }


    // ELO changes
    $elo_changes = [];
    $current_elo = intval($user['elo_rating'] ?? 1500);
    // Loop ELO sekarang menggunakan data yang sudah di-map dari $recent_games
    foreach ($recent_games as $game) {
        // game_status sudah berisi 'win', 'loss', atau 'draw'
        $game_status = $game['game_status'];
        
        // Tentukan tipe result
        if ($game_status === 'win') {
            $type = 'win';
            $elo_change = 15;
        } elseif ($game_status === 'loss') {
            $type = 'loss';
            $elo_change = -10;
        } else { // draw
            $type = 'draw';
            $elo_change = -2;
        }
        
        $elo_changes[] = [
            'change' => $elo_change,
            'type' => $type,
            'days_ago' => ceil((time() - strtotime($game['created_at'])) / 86400),
            'opponent_name' => $game['opponent'] ?? 'Lawan' // Tambahkan nama lawan
        ];
    }
    // --- MODIFIKASI PHP END ---
    ?>
    <!DOCTYPE html>
    <html class="dark" lang="id">
    <head>
        <meta charset="utf-8"/><meta content="width=device-width, initial-scale=1.0" name="viewport"/>
        <title>Statistik - ChessApp</title>
        <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;900&display=swap" rel="stylesheet"/>
        <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
        <style>
            body { font-family: 'Inter', sans-serif; }
            @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
            .animate-fade-in { animation: fadeIn 0.5s ease-out forwards; }
        </style>
        <script>
            tailwind.config = { darkMode: "class", theme: { extend: { colors: { "primary": "#d97706", "background-light": "#f3f4f6", "background-dark": "#111827", "win": "#22c55e", "loss": "#ef4444", "draw": "#f59e0b" }, fontFamily: { "display": ["Inter", "sans-serif"] } } } }
        </script>
    </head>
    <body class="bg-background-light dark:bg-background-dark font-display">
        <div class="relative flex h-auto min-h-screen w-full flex-col overflow-x-hidden">
            <div class="layout-container flex h-full grow flex-col">
                <div class="flex flex-1 justify-center py-5 lg:px-10 xl:px-40">
                    <div class="layout-content-container flex flex-col w-full max-w-[960px] flex-1">
                        <header class="flex items-center justify-between whitespace-nowrap border-b border-gray-200 dark:border-gray-700 px-4 sm:px-10 py-3">
                            <div class="flex items-center gap-4">
                                <div class="size-6 text-primary">
                                    <svg fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_6_535)"><path clip-rule="evenodd" d="M47.2426 24L24 47.2426L0.757355 24L24 0.757355L47.2426 24ZM12.2426 21H35.7574L24 9.24264L12.2426 21Z" fill="currentColor" fill-rule="evenodd"></path></g></svg>
                                </div>
                                <h2 class="text-gray-900 dark:text-white text-lg font-bold">ChessApp</h2>
                            </div>
                            <div class="hidden md:flex flex-1 justify-end items-center gap-8">
                                <div class="flex items-center gap-9">
                                    <a class="text-gray-600 dark:text-gray-300 text-sm font-medium hover:text-primary" href="dashboard.php">Dashboard</a>
                                    <a class="text-gray-600 dark:text-gray-300 text-sm font-medium hover:text-primary" href="play.php">Main</a>
                                    <a class="text-primary text-sm font-medium" href="statistik.php">Statistik</a>
                                </div>
                                <a href="logout.php" class="flex items-center justify-center h-10 px-4 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg">
                                    <span class="material-symbols-outlined text-lg">logout</span>
                                </a>
                                <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" style='background-image: url("<?php echo htmlspecialchars($avatar_url); ?>");'></div>
                            </div>
                        </header>

                        <main class="flex flex-col gap-6 p-4 sm:p-6 md:p-8">
                            <div class="flex p-4">
                                <div class="flex w-full flex-col gap-4 sm:flex-row sm:justify-between sm:items-center">
                                    <div class="flex gap-4 items-center">
                                        <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full h-24 w-24 sm:h-32 sm:w-32" style='background-image: url("<?php echo htmlspecialchars($avatar_url); ?>");'></div>
                                        <div class="flex flex-col justify-center">
                                            <p class="text-gray-900 dark:text-white text-[22px] sm:text-3xl font-bold"><?php echo htmlspecialchars($user['username'] ?? 'User'); ?></p>
                                            <p class="text-gray-500 dark:text-gray-400 text-base">ELO Rating: <span class="font-bold text-primary"><?php echo $current_elo; ?></span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-4">
                                <div class="flex min-w-[158px] flex-1 flex-col gap-2 rounded-xl p-6 border bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                                    <p class="text-gray-600 dark:text-gray-300 text-base font-medium">Total Permainan</p>
                                    <p class="text-gray-900 dark:text-white text-2xl font-bold"><?php echo $user_total; ?></p>
                                </div>
                                <div class="flex min-w-[158px] flex-1 flex-col gap-2 rounded-xl p-6 border bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                                    <p class="text-gray-600 dark:text-gray-300 text-base font-medium">Menang</p>
                                    <p class="text-win text-2xl font-bold"><?php echo $user_wins; ?></p>
                                </div>
                                <div class="flex min-w-[158px] flex-1 flex-col gap-2 rounded-xl p-6 border bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                                    <p class="text-gray-600 dark:text-gray-300 text-base font-medium">Kalah</p>
                                    <p class="text-loss text-2xl font-bold"><?php echo $user_losses; ?></p>
                                </div>
                                <div class="flex min-w-[158px] flex-1 flex-col gap-2 rounded-xl p-6 border bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                                    <p class="text-gray-600 dark:text-gray-300 text-base font-medium">Seri</p>
                                    <p class="text-draw text-2xl font-bold"><?php echo $user_draws; ?></p>
                                </div>
                            </div>

                            <div class="flex flex-col animate-fade-in">
                                <h2 class="text-gray-900 dark:text-white text-[22px] font-bold px-4 pb-3 pt-5">Riwayat Rating ELO</h2>
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                    <div class="lg:col-span-1 flex flex-1 flex-col gap-2 rounded-xl border bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 p-6">
                                        <p class="text-gray-900 dark:text-white text-base font-medium">Grafik Rating Anda</p>
                                        <p class="text-primary text-[32px] font-bold"><?php echo $current_elo; ?></p>
                                        <div class="flex gap-2 items-center">
                                            <p class="text-gray-500 dark:text-gray-400 text-base">Stabil</p>
                                        </div>
                                        <div class="flex min-h-[180px] flex-1 flex-col gap-8 py-4">
                                            <svg class="w-full h-full" viewBox="0 0 472 150" preserveAspectRatio="none"><path d="M0 109C18.1538 109 18.1538 21 36.3077 21C54.4615 21 54.4615 41 72.6154 41C90.7692 41 90.7692 93 108.923 93C127.077 93 127.077 33 145.231 33C163.385 33 163.385 101 181.538 101C199.692 101 199.692 61 217.846 61C236 61 236 45 254.154 45C272.308 45 272.308 121 290.462 121C308.615 121 308.615 149 326.769 149C344.923 149 344.923 1 363.077 1C381.231 1 381.231 81 399.385 81C417.538 81 417.538 129 435.692 129C453.846 129 453.846 25 472 25V149H0V109Z" fill="url(#paint0)" opacity="0.3"></path><path d="M0 109C18.1538 109 18.1538 21 36.3077 21C54.4615 21 54.4615 41 72.6154 41C90.7692 41 90.7692 93 108.923 93C127.077 93 127.077 33 145.231 33C163.385 33 163.385 101 181.538 101C199.692 101 199.692 61 217.846 61C236 61 236 45 254.154 45C272.308 45 272.308 121 290.462 121C308.615 121 308.615 149 326.769 149C344.923 149 344.923 1 363.077 1C381.231 1 381.231 81 399.385 81C417.538 81 417.538 129 435.692 129C453.846 129 453.846 25 472 25" stroke="#d97706" stroke-width="3" stroke-linecap="round"></path><defs><linearGradient id="paint0" x1="236" x2="236" y1="1" y2="149"><stop stop-color="#d97706" stop-opacity="0.4"></stop><stop offset="1" stop-color="#d97706" stop-opacity="0"></stop></linearGradient></defs></svg>
                                        </div>
                                    </div>

                                    <div class="lg:col-span-1 flex flex-col rounded-xl border bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                                        <div class="p-6">
                                            <p class="text-gray-900 dark:text-white text-base font-medium">Perubahan Terakhir</p>
                                            <p class="text-gray-500 dark:text-gray-400 text-sm">Setelah setiap permainan</p>
                                        </div>
                                        <div class="flex-1 overflow-y-auto max-h-[320px]">
                                            <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                                                <?php if (count($elo_changes) === 0): ?>
                                                    <li class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Belum ada permainan</li>
                                                <?php else: ?>
                                                    <?php foreach (array_slice($elo_changes, 0, 4) as $change): ?>
                                                        <li class="flex items-center justify-between p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                                            <div class="flex flex-col">
                                                                <span class="font-medium text-gray-900 dark:text-white">vs. <?php echo htmlspecialchars($change['opponent_name'] ?? 'Lawan'); ?></span>
                                                                <span class="text-sm text-gray-500 dark:text-gray-400"><?php echo $change['days_ago']; ?> hari lalu</span>
                                                            </div>
                                                            <div class="flex items-center gap-2">
                                                                <span class="font-semibold <?php echo $change['type'] === 'win' ? 'text-win' : ($change['type'] === 'loss' ? 'text-loss' : 'text-draw'); ?>">
                                                                    <?php echo ($change['change'] > 0 ? '+' : '') . $change['change']; ?>
                                                                </span>
                                                                <span class="material-symbols-outlined text-lg <?php echo $change['type'] === 'win' ? 'text-win' : ($change['type'] === 'loss' ? 'text-loss' : 'text-draw'); ?>">
                                                                    <?php echo $change['type'] === 'draw' ? 'remove' : ($change['type'] === 'win' ? 'arrow_upward' : 'arrow_downward'); ?>
                                                                </span>
                                                            </div>
                                                        </li>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col animate-fade-in">
                                <h2 class="text-gray-900 dark:text-white text-[22px] font-bold px-4 pb-0 pt-5">Visualisasi Tren</h2>
                                <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
                                    <div class="lg:col-span-3 flex min-w-72 flex-1 flex-col gap-2 rounded-xl border bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 p-6">
                                        <p class="text-gray-900 dark:text-white text-base font-medium">Distribusi Hasil</p>
                                        <p class="text-gray-500 dark:text-gray-400 text-sm">Semua Waktu</p>
                                        <div class="flex items-center justify-center min-h-[180px] flex-1 p-4">
                                            <div class="relative w-48 h-48">
                                                <svg class="w-full h-full" viewBox="0 0 36 36">
                                                    <circle class="stroke-current text-loss" cx="18" cy="18" fill="none" r="15.9154943092" stroke-width="3.8" stroke-dasharray="<?php echo $pct_loss; ?>, <?php echo 100 - $pct_loss; ?>"></circle>
                                                    <circle class="stroke-current text-draw" cx="18" cy="18" fill="none" r="15.9154943092" stroke-dasharray="<?php echo $pct_draw; ?>, <?php echo 100 - $pct_draw; ?>" stroke-dashoffset="-38.7" stroke-width="3.8"></circle>
                                                    <circle class="stroke-current text-win" cx="18" cy="18" fill="none" r="15.9154943092" stroke-dasharray="<?php echo $pct_win; ?>, <?php echo 100 - $pct_win; ?>" stroke-dashoffset="-38.7" stroke-width="3.8"></circle>
                                                </svg>
                                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                                    <span class="text-3xl font-bold text-gray-900 dark:text-white"><?php echo $win_rate_text; ?></span>
                                                    <span class="text-sm text-gray-500 dark:text-gray-400">Win Rate</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex justify-around items-center pt-2">
                                            <div class="flex items-center gap-2">
                                                <div class="w-3 h-3 rounded-full bg-win"></div><span class="text-sm text-gray-700 dark:text-gray-300">Menang (<?php echo $pct_win; ?>%)</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <div class="w-3 h-3 rounded-full bg-loss"></div><span class="text-sm text-gray-700 dark:text-gray-300">Kalah (<?php echo $pct_loss; ?>%)</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <div class="w-3 h-3 rounded-full bg-draw"></div><span class="text-sm text-gray-700 dark:text-gray-300">Seri (<?php echo $pct_draw; ?>%)</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="lg:col-span-2 flex flex-col">
                                        <h3 class="text-gray-900 dark:text-white text-base font-medium px-4 pb-3 pt-5">Statistik Cepat</h3>
                                        <div class="grid grid-cols-1 gap-4">
                                            <div class="flex flex-col gap-3 p-4 rounded-xl border bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                                                <div class="flex justify-between items-center">
                                                    <span class="text-gray-900 dark:text-white font-medium text-sm">Winrate</span>
                                                    <span class="text-sm font-semibold text-primary"><?php echo $win_rate_text; ?></span>
                                                </div>
                                            </div>
                                            <div class="flex flex-col gap-3 p-4 rounded-xl border bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                                                <div class="flex justify-between items-center">
                                                    <span class="text-gray-900 dark:text-white font-medium text-sm">Total Permainan</span>
                                                    <span class="text-sm font-semibold text-primary"><?php echo $user_total; ?></span>
                                                </div>
                                            </div>
                                            <div class="flex flex-col gap-3 p-4 rounded-xl border bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                                                <div class="flex justify-between items-center">
                                                    <span class="text-gray-900 dark:text-white font-medium text-sm">Rating ELO</span>
                                                    <span class="text-sm font-semibold text-primary"><?php echo $current_elo; ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col animate-fade-in">
                                <h2 class="text-gray-900 dark:text-white text-[22px] font-bold px-4 pb-3 pt-5">Permainan Terakhir</h2>
                                <div class="rounded-xl border bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 overflow-x-auto">
                                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                        <thead class="text-xs text-gray-700 dark:text-gray-300 uppercase bg-gray-50 dark:bg-gray-700">
                                            <tr>
                                                <th class="px-6 py-3">Lawan</th>
                                                <th class="px-6 py-3">Hasil</th>
                                                <th class="px-6 py-3 text-center">Langkah</th>
                                                <th class="px-6 py-3 text-right">Tanggal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($recent_games) === 0): ?>
                                                <tr>
                                                    <td class="px-6 py-4" colspan="4" style="text-align:center;">Belum ada data permainan</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($recent_games as $game): 
                                                    // game_status sudah berisi 'win', 'loss', atau 'draw'
                                                    $game_status = $game['game_status'];
                                                    $is_draw = ($game_status === 'draw');
                                                    $is_win = ($game_status === 'win');
                                                    
                                                    $result = $is_draw ? 'Seri' : ($is_win ? 'Menang' : 'Kalah');
                                                    $result_color = $is_draw ? 'text-draw' : ($is_win ? 'text-win' : 'text-loss');
                                                    // Ambil moves_count dari kolom yang baru (moves di-map dari moves_count)
                                                    $moves_count = $game['moves'] ?? 0;
                                                ?>
                                                <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                                    <th class="px-6 py-4 font-medium whitespace-nowrap text-gray-900 dark:text-white"><?php echo htmlspecialchars($game['opponent'] ?? 'Lawan'); ?></th>
                                                    <td class="px-6 py-4"><span class="<?php echo $result_color; ?> font-semibold"><?php echo $result; ?></span></td>
                                                    <td class="px-6 py-4 text-center"><?php echo $moves_count; ?></td>
                                                    <td class="px-6 py-4 text-right"><?php echo date('j M Y', strtotime($game['created_at'])); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </main>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>