
<?php
require_once 'config.php';
requireLogin();

// Get user data
$user = getUserById($_SESSION['user_id']);

// If user not found in DB, clear session and redirect to login
if (!$user) {
    // clear potentially invalid session and ask user to login again
    session_unset();
    session_destroy();
    redirectWithMessage('login.php', 'error', 'Akun tidak ditemukan. Silakan login kembali.');
}

// Get user stats
$stmt = $conn->prepare("SELECT 
    COUNT(*) as total_games,
    SUM(CASE WHEN winner_id = ? THEN 1 ELSE 0 END) as games_won,
    SUM(CASE WHEN game_status = 'draw' AND (white_player_id = ? OR black_player_id = ?) THEN 1 ELSE 0 END) as games_draw
FROM games 
WHERE (white_player_id = ? OR black_player_id = ?) AND game_status != 'active'");
$stmt->bind_param("iiiii", $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

$games_won = $stats['games_won'] ?? 0;
$games_draw = $stats['games_draw'] ?? 0;
$total_games = $stats['total_games'] ?? 0;
$games_lost = $total_games - $games_won - $games_draw;

// Get avatar URL (use safe defaults and avoid passing null to urlencode)
$username_for_avatar = isset($user['username']) && $user['username'] !== null ? $user['username'] : 'User';
$avatar_url = (!empty($user['avatar_url'])) ? 'uploads/' . $user['avatar_url'] : 'https://ui-avatars.com/api/?name=' . urlencode($username_for_avatar) . '&background=f59e0b&color=fff';
?>
<!DOCTYPE html>
<html class="dark" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Dashboard - ChessLearn</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <link href="style.css" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": {
                            DEFAULT: "#f59e0b",
                            light: "#fbbf24",
                            dark: "#d97706",
                        },
                        "background-light": "#f8fafc",
                        "background-dark": "#0f172a",
                        "surface-dark": "#1e293b",
                        "muted": "#94a3b8",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                },
            },
        }
    </script>
</head>
<body class="font-display bg-background-light dark:bg-background-dark text-slate-100">
    <div class="relative flex min-h-screen w-full flex-col overflow-x-hidden">
        <div class="layout-container flex h-full grow flex-col">
            <div class="flex flex-1 justify-center p-4 sm:p-5 lg:px-40">
                <div class="layout-content-container flex w-full max-w-[960px] flex-1 flex-col">
                    <header class="flex items-center justify-between whitespace-nowrap border-b border-solid border-slate-700 px-4 md:px-10 py-4">
                        <div class="flex items-center gap-4 text-white">
                            <div class="size-6 text-primary">
                                <svg fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93s3.05-7.44 7-7.93v15.86zm2-15.86c1.03.13 2 .45 2.87.93H13v-.93zM13 7h5.24c.25.31.48.65.68 1H13V7zm0 3h6.74c.08.33.15.66.19 1H13v-1zm0 3h6.09c-.2.71-.52 1.38-.93 2H13v-2z"></path>
                                </svg>
                            </div>
                            <h2 class="text-white text-lg font-bold leading-tight tracking-[-0.015em]">ChessLearn</h2>
                        </div>
                        <div class="flex flex-1 items-center justify-end gap-4">
                            <a href="statistik.php" class="flex items-center gap-2 text-slate-300 hover:text-white transition-colors">
                                <span class="material-symbols-outlined">analytics</span>
                                <span class="hidden sm:inline">Statistik</span>
                            </a>
                            <a href="logout.php" class="flex h-10 w-10 cursor-pointer items-center justify-center overflow-hidden rounded-full bg-surface-dark text-slate-300 hover:bg-slate-600 transition-colors">
                                <span class="material-symbols-outlined">logout</span>
                            </a>
                            <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10 border-2 border-primary" style='background-image: url("<?php echo $avatar_url; ?>");'></div>
                        </div>
                    </header>
                    
                    <main class="flex-1">
                        <div class="px-4 pt-8 pb-1">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                <div>
                                    <h1 class="text-white tracking-tight text-[32px] font-bold leading-tight">Selamat datang kembali, <?php echo htmlspecialchars($user['username']); ?>!</h1>
                                    <p class="text-muted text-base font-normal leading-normal pt-1">Siap untuk langkah selanjutnya? Pilih mode permainanmu.</p>
                                </div>
                                <div class="flex animate-pulse items-center gap-3 rounded-lg bg-primary-dark py-3 px-4 mt-4 sm:mt-0 border border-primary">
                                    <span class="material-symbols-outlined text-white !text-3xl">trending_up</span>
                                    <div>
                                        <p class="text-sm font-medium text-amber-200">Rating Elo</p>
                                        <p class="text-2xl font-bold text-white"><?php echo $user['elo_rating']; ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-4 mt-4">
                            <a href="play.php" class="group flex cursor-pointer flex-col gap-4 rounded-xl border border-slate-700 bg-surface-dark p-6 transition-all hover:border-primary hover:bg-primary-dark hover:shadow-lg hover:shadow-primary/20 hover:-translate-y-1 active:animate-pop">
                                <div class="text-primary-light group-hover:text-white transition-colors">
                                    <span class="material-symbols-outlined !text-4xl">computer</span>
                                </div>
                                <div>
                                    <p class="text-white text-lg font-medium leading-normal">Lawan Komputer</p>
                                    <p class="text-muted text-sm font-normal leading-normal mt-1 group-hover:text-amber-200 transition-colors">Asah strategimu melawan AI dengan berbagai tingkat kesulitan.</p>
                                </div>
                            </a>
                            
                            <a href="play.php?mode=friend" class="group flex cursor-pointer flex-col gap-4 rounded-xl border border-slate-700 bg-surface-dark p-6 transition-all hover:border-primary hover:bg-primary-dark hover:shadow-lg hover:shadow-primary/20 hover:-translate-y-1 active:animate-pop">
                                <div class="text-primary-light group-hover:text-white transition-colors">
                                    <span class="material-symbols-outlined !text-4xl">groups</span>
                                </div>
                                <div>
                                    <p class="text-white text-lg font-medium leading-normal">Tantang Teman</p>
                                    <p class="text-muted text-sm font-normal leading-normal mt-1 group-hover:text-amber-200 transition-colors">Bermain dengan teman secara online atau di satu perangkat.</p>
                                </div>
                            </a>
                            
                            <a href="index.html" class="group flex cursor-pointer flex-col gap-4 rounded-xl border border-slate-700 bg-surface-dark p-6 transition-all hover:border-primary hover:bg-primary-dark hover:shadow-lg hover:shadow-primary/20 hover:-translate-y-1 active:animate-pop">
                                <div class="text-primary-light group-hover:text-white transition-colors">
                                    <span class="material-symbols-outlined !text-4xl">school</span>
                                </div>
                                <div>
                                    <p class="text-white text-lg font-medium leading-normal">Belajar Catur</p>
                                    <p class="text-muted text-sm font-normal leading-normal mt-1 group-hover:text-amber-200 transition-colors">Pelajari dasar-dasar, taktik, dan pembukaan catur.</p>
                                </div>
                            </a>
                        </div>
                        
                        <h2 class="text-white text-[22px] font-bold leading-tight tracking-[-0.015em] px-4 pb-3 pt-10">Statistik Cepat</h2>
                        <div class="p-4">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div class="rounded-lg border border-slate-700 bg-surface-dark p-4">
                                    <p class="text-sm text-muted">Menang</p>
                                    <p class="text-2xl font-bold text-white"><?php echo $games_won; ?></p>
                                </div>
                                <div class="rounded-lg border border-slate-700 bg-surface-dark p-4">
                                    <p class="text-sm text-muted">Kalah</p>
                                    <p class="text-2xl font-bold text-white"><?php echo $games_lost; ?></p>
                                </div>
                                <div class="rounded-lg border border-slate-700 bg-surface-dark p-4">
                                    <p class="text-sm text-muted">Seri</p>
                                    <p class="text-2xl font-bold text-white"><?php echo $games_draw; ?></p>
                                </div>
                            </div>
                        </div>
                    </main>
                    
                    <footer class="mt-12 p-4 text-center text-muted text-sm border-t border-solid border-slate-700">
                        <p>© 2024 ChessLearn. All rights reserved.</p>
                        <div class="flex justify-center gap-4 mt-2">
                            <a class="hover:text-primary-light transition-colors" href="#">Tentang Kami</a>
                            <a class="hover:text-primary-light transition-colors" href="#">Bantuan/FAQ</a>
                        </div>
                    </footer>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
