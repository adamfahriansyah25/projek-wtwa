<?php
require_once 'config.php';
requireAdmin();

// Get statistics
$stats = getTotalStats();

// Get recent logs
$logs = getRecentLogs(10);

// Get user data
$admin = getUserById($_SESSION['user_id']);

// Determine current section from GET parameter
$section = isset($_GET['section']) ? $_GET['section'] : 'dashboard';

// Fetch data for each section
$users = [];
$games = [];
$activities = [];

if ($section === 'users' || $section === 'dashboard') {
    $result = $conn->query("SELECT id, username, email, elo_rating, is_admin FROM users LIMIT 50");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
}

if ($section === 'games' || $section === 'dashboard') {
    $result = $conn->query("SELECT id, white_player_id, black_player_id, winner_id, game_status, move_count, created_at FROM games LIMIT 50");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $games[] = $row;
        }
    } else {
        // Tabel games mungkin belum ada
        $games = [];
    }
}

if ($section === 'activities' || $section === 'dashboard') {
    $result = $conn->query("SELECT * FROM system_logs ORDER BY created_at DESC LIMIT 50");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $activities[] = $row;
        }
    }
}

// Handle POST actions
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'delete_user' && isset($_POST['user_id'])) {
        $uid = intval($_POST['user_id']);
        if ($uid !== $_SESSION['user_id']) {
            if ($conn->query("DELETE FROM users WHERE id = $uid")) {
                $message = 'User berhasil dihapus.';
            }
        }
    }
    
    if ($action === 'update_user' && isset($_POST['user_id'])) {
        $uid = intval($_POST['user_id']);
        $username = $conn->real_escape_string($_POST['username']);
        $email = $conn->real_escape_string($_POST['email']);
        $elo = intval($_POST['elo_rating']);
        if ($conn->query("UPDATE users SET username='$username', email='$email', elo_rating=$elo WHERE id=$uid")) {
            $message = 'User berhasil diperbarui.';
        }
    }
    
    if ($action === 'toggle_admin' && isset($_POST['user_id'])) {
        $uid = intval($_POST['user_id']);
        if ($uid !== $_SESSION['user_id']) {
            if ($conn->query("UPDATE users SET is_admin = NOT is_admin WHERE id = $uid")) {
                $message = 'Status admin berhasil diubah.';
            }
        }
    }
    
    if ($action === 'delete_game' && isset($_POST['game_id'])) {
        $gid = intval($_POST['game_id']);
        if ($conn->query("DELETE FROM games WHERE id = $gid")) {
            $message = 'Pertandingan berhasil dihapus.';
        }
    }
    
    if ($action === 'update_game' && isset($_POST['game_id'])) {
        $gid = intval($_POST['game_id']);
        $status = $conn->real_escape_string($_POST['game_status']);
        if ($conn->query("UPDATE games SET game_status='$status' WHERE id=$gid")) {
            $message = 'Pertandingan berhasil diperbarui.';
        }
    }
    
    if ($action === 'delete_log' && isset($_POST['log_id'])) {
        $lid = intval($_POST['log_id']);
        if ($conn->query("DELETE FROM system_logs WHERE id = $lid")) {
            $message = 'Log berhasil dihapus.';
        }
    }
    
    if ($action === 'clear_logs') {
        if ($conn->query("DELETE FROM system_logs")) {
            $message = 'Semua log berhasil dihapus.';
        }
    }
    
    // Refresh data after action
    if (strpos($message, 'berhasil') !== false) {
        header('Location: admin.php?section=' . $section . '&success=1');
        exit();
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Admin Dashboard - ChessLearn</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <link href="style.css" rel="stylesheet"/>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#FFD700",
                        "primary-light": "#FFEB3B",
                        "primary-dark": "#DAA520",
                        "background-dark": "#1A1A1A",
                        "surface-dark": "#2C2C2C",
                        "border-dark": "#4A4A4A",
                        "text-main-dark": "#FFD700",
                        "text-secondary-dark": "#E0E0E0",
                        "green-status": "#2ECC71",
                        "red-status": "#E74C3C",
                        "yellow-status": "#F1C40F",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                },
            },
        }
    </script>
</head>
<body class="font-display bg-background-dark text-text-secondary-dark">
    <div class="relative flex min-h-screen w-full">
        <!-- SideNavBar -->
        <aside class="flex flex-col w-64 bg-surface-dark border-r border-border-dark p-4">
            <div class="flex items-center gap-3 mb-8">
                <span class="material-symbols-outlined text-primary text-4xl">chess</span>
                <h1 class="text-xl font-bold text-text-main-dark">ChessAdmin</h1>
            </div>
            <div class="flex flex-col justify-between flex-1">
                <div class="flex flex-col gap-2">
                    <a class="flex items-center gap-3 px-3 py-2 rounded-lg <?php echo ($section === 'dashboard') ? 'bg-primary/20 text-primary' : 'hover:bg-primary/10 hover:text-primary-light'; ?> transition-colors-transform" href="admin.php?section=dashboard">
                        <span class="material-symbols-outlined">dashboard</span>
                        <p class="text-sm font-medium">Dashboard</p>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2 rounded-lg <?php echo ($section === 'users') ? 'bg-primary/20 text-primary' : 'hover:bg-primary/10 hover:text-primary-light'; ?> transition-colors-transform" href="admin.php?section=users">
                        <span class="material-symbols-outlined">group</span>
                        <p class="text-sm font-medium">Pengguna</p>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2 rounded-lg <?php echo ($section === 'games') ? 'bg-primary/20 text-primary' : 'hover:bg-primary/10 hover:text-primary-light'; ?> transition-colors-transform" href="admin.php?section=games">
                        <span class="material-symbols-outlined">castle</span>
                        <p class="text-sm font-medium">Pertandingan</p>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2 rounded-lg <?php echo ($section === 'activities') ? 'bg-primary/20 text-primary' : 'hover:bg-primary/10 hover:text-primary-light'; ?> transition-colors-transform" href="admin.php?section=activities">
                        <span class="material-symbols-outlined">history</span>
                        <p class="text-sm font-medium">Aktivitas</p>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2 rounded-lg <?php echo ($section === 'logs') ? 'bg-primary/20 text-primary' : 'hover:bg-primary/10 hover:text-primary-light'; ?> transition-colors-transform" href="admin.php?section=logs">
                        <span class="material-symbols-outlined">bug_report</span>
                        <p class="text-sm font-medium">Log Sistem</p>
                    </a>
                </div>
                <div class="flex flex-col gap-1">
                    <a class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-primary/10 hover:text-primary-light transition-colors-transform" href="logout.php">
                        <span class="material-symbols-outlined">logout</span>
                        <p class="text-sm font-medium">Logout</p>
                    </a>
                </div>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="flex-1">
            <!-- Header Bar -->
            <header class="flex items-center justify-between p-4 border-b bg-surface-dark border-border-dark">
                <h1 class="text-2xl font-bold text-text-main-dark">
                    <?php
                    switch ($section) {
                        case 'users':
                            echo 'Manajemen Pengguna';
                            break;
                        case 'games':
                            echo 'Manajemen Pertandingan';
                            break;
                        case 'activities':
                            echo 'Aktivitas';
                            break;
                        case 'logs':
                            echo 'Log Sistem';
                            break;
                        default:
                            echo 'Dashboard';
                    }
                    ?>
                </h1>
                <div class="flex items-center gap-4">
                    <button class="relative text-text-secondary-dark hover:text-primary-light transition-colors-transform">
                        <span class="material-symbols-outlined">notifications</span>
                        <span class="absolute -top-1 -right-1 flex h-3 w-3">
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-red-status"></span>
                        </span>
                    </button>
                    <div class="flex items-center gap-3">
                        <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" style='background-image: url("<?php echo $admin['avatar_url'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($admin['username']) . '&background=FFD700&color=1A1A1A'; ?>");'></div>
                        <div class="flex-col hidden md:flex">
                            <h2 class="text-sm font-medium text-text-main-dark"><?php echo htmlspecialchars($admin['username']); ?></h2>
                            <p class="text-xs text-text-secondary-dark"><?php echo htmlspecialchars($admin['email']); ?></p>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Page Content -->
            <div class="p-6 space-y-6">
                <?php if ($message): ?>
                <div class="p-4 bg-green-status/20 border border-green-status text-green-status rounded-lg">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <?php if ($section === 'dashboard'): ?>
                <!-- Stats -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <div class="flex flex-col gap-2 p-6 border rounded-lg bg-surface-dark border-border-dark animate-fade-in">
                        <p class="text-base font-medium text-text-secondary-dark">Total Pengguna</p>
                        <p class="text-3xl font-bold tracking-tight text-primary"><?php echo number_format($stats['total_users']); ?></p>
                        <p class="text-sm font-medium text-green-status">Aktif</p>
                    </div>
                    <div class="flex flex-col gap-2 p-6 border rounded-lg bg-surface-dark border-border-dark animate-fade-in delay-100">
                        <p class="text-base font-medium text-text-secondary-dark">Pertandingan Aktif</p>
                        <p class="text-3xl font-bold tracking-tight text-primary"><?php echo number_format($stats['active_games']); ?></p>
                        <p class="text-sm font-medium text-green-status">Sedang Berlangsung</p>
                    </div>
                    <div class="flex flex-col gap-2 p-6 border rounded-lg bg-surface-dark border-border-dark animate-fade-in delay-200">
                        <p class="text-base font-medium text-text-secondary-dark">Pertandingan Selesai</p>
                        <p class="text-3xl font-bold tracking-tight text-primary"><?php echo number_format($stats['completed_games']); ?></p>
                        <p class="text-sm font-medium text-text-secondary-dark">Total</p>
                    </div>
                </div>
                
                <!-- Charts -->
                <div class="flex min-w-72 flex-1 flex-col gap-2 rounded-lg border bg-surface-dark border-border-dark p-6 animate-fade-in delay-300">
                    <p class="text-lg font-bold text-primary">Aktivitas Pengguna Baru</p>
                    <div class="flex items-baseline gap-2">
                        <p class="text-4xl font-bold leading-tight tracking-tighter truncate text-primary"><?php echo $stats['new_users_month']; ?></p>
                        <p class="text-base font-medium text-green-status">bulan ini</p>
                    </div>
                    <div class="flex min-h-[180px] flex-1 flex-col gap-8 py-4">
                        <svg fill="none" height="148" preserveAspectRatio="none" viewBox="-3 0 478 150" width="100%" xmlns="http://www.w3.org/2000/svg">
                            <path d="M0 109C18.1538 109 18.1538 21 36.3077 21C54.4615 21 54.4615 41 72.6154 41C90.7692 41 90.7692 93 108.923 93C127.077 93 127.077 33 145.231 33C163.385 33 163.385 101 181.538 101C199.692 101 199.692 61 217.846 61C236 61 236 45 254.154 45C272.308 45 272.308 121 290.462 121C308.615 121 308.615 149 326.769 149C344.923 149 344.923 1 363.077 1C381.231 1 381.231 81 399.385 81C417.538 81 417.538 129 435.692 129C453.846 129 453.846 25 472 25V149H326.769H0V109Z" fill="url(#paint0_linear_chart)"></path>
                            <path d="M0 109C18.1538 109 18.1538 21 36.3077 21C54.4615 21 54.4615 41 72.6154 41C90.7692 41 90.7692 93 108.923 93C127.077 93 127.077 33 145.231 33C163.385 33 163.385 101 181.538 101C199.692 101 199.692 61 217.846 61C236 61 236 45 254.154 45C272.308 45 272.308 121 290.462 121C308.615 121 308.615 149 326.769 149C344.923 149 344.923 1 363.077 1C381.231 1 381.231 81 399.385 81C417.538 81 417.538 129 435.692 129C453.846 129 453.846 25 472 25" stroke="#FFD700" stroke-linecap="round" stroke-width="3"></path>
                            <defs>
                                <linearGradient gradientUnits="userSpaceOnUse" id="paint0_linear_chart" x1="236" x2="236" y1="1" y2="149">
                                    <stop stop-color="#FFD700" stop-opacity="0.3"></stop>
                                    <stop offset="1" stop-color="#FFD700" stop-opacity="0"></stop>
                                </linearGradient>
                            </defs>
                        </svg>
                        <div class="flex justify-around">
                            <p class="text-xs font-bold tracking-wide text-text-secondary-dark">Minggu 1</p>
                            <p class="text-xs font-bold tracking-wide text-text-secondary-dark">Minggu 2</p>
                            <p class="text-xs font-bold tracking-wide text-text-secondary-dark">Minggu 3</p>
                            <p class="text-xs font-bold tracking-wide text-text-secondary-dark">Minggu 4</p>
                        </div>
                    </div>
                </div>

                <?php endif; ?>

                <?php if ($section === 'users'): ?>
                <!-- Users Management -->
                <div class="space-y-4">
                    <div class="overflow-x-auto bg-surface-dark border border-border-dark rounded-lg">
                        <table class="min-w-full text-sm text-left">
                            <thead class="text-xs uppercase bg-background-dark text-text-secondary-dark">
                                <tr>
                                    <th class="px-6 py-3">ID</th>
                                    <th class="px-6 py-3">Username</th>
                                    <th class="px-6 py-3">Email</th>
                                    <th class="px-6 py-3">ELO</th>
                                    <th class="px-6 py-3">Role</th>
                                    <th class="px-6 py-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="text-text-secondary-dark">
                                <?php foreach ($users as $u): ?>
                                <tr class="border-b border-border-dark hover:bg-primary/5 transition-colors-transform">
                                    <td class="px-6 py-4 font-medium"><?php echo $u['id']; ?></td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($u['username']); ?></td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td class="px-6 py-4"><?php echo $u['elo_rating']; ?></td>
                                    <td class="px-6 py-4"><?php echo $u['is_admin'] ? '<span class="text-yellow-status">Admin</span>' : '<span class="text-green-status">User</span>'; ?></td>
                                    <td class="px-6 py-4 space-x-2">
                                        <button onclick="openEditUser(<?php echo $u['id']; ?>,'<?php echo addslashes($u['username']); ?>','<?php echo addslashes($u['email']); ?>',<?php echo $u['elo_rating']; ?>)" class="text-blue-400 hover:text-blue-300">Edit</button>
                                        <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="toggle_admin" />
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>" />
                                            <button type="submit" class="text-yellow-400 hover:text-yellow-300"><?php echo $u['is_admin'] ? 'Revoke' : 'Make Admin'; ?></button>
                                        </form>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Yakin hapus user?');">
                                            <input type="hidden" name="action" value="delete_user" />
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>" />
                                            <button type="submit" class="text-red-400 hover:text-red-300">Hapus</button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php endif; ?>

                <?php if ($section === 'games'): ?>
                <!-- Games Management -->
                <div class="space-y-4">
                    <div class="overflow-x-auto bg-surface-dark border border-border-dark rounded-lg">
                        <table class="min-w-full text-sm text-left">
                            <thead class="text-xs uppercase bg-background-dark text-text-secondary-dark">
                                <tr>
                                    <th class="px-6 py-3">ID</th>
                                    <th class="px-6 py-3">Pemain Putih</th>
                                    <th class="px-6 py-3">Pemain Hitam</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3">Moves</th>
                                    <th class="px-6 py-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="text-text-secondary-dark">
                                <?php foreach ($games as $g): ?>
                                <tr class="border-b border-border-dark hover:bg-primary/5 transition-colors-transform">
                                    <td class="px-6 py-4 font-medium"><?php echo $g['id']; ?></td>
                                    <td class="px-6 py-4"><?php echo $g['white_player_id']; ?></td>
                                    <td class="px-6 py-4"><?php echo $g['black_player_id']; ?></td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($g['game_status'] ?? 'unknown'); ?></td>
                                    <td class="px-6 py-4"><?php echo intval($g['move_count'] ?? 0); ?></td>
                                    <td class="px-6 py-4 space-x-2">
                                        <button onclick="openEditGame(<?php echo $g['id']; ?>,'<?php echo addslashes($g['game_status']); ?>')" class="text-blue-400 hover:text-blue-300">Edit</button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Yakin hapus pertandingan?');">
                                            <input type="hidden" name="action" value="delete_game" />
                                            <input type="hidden" name="game_id" value="<?php echo $g['id']; ?>" />
                                            <button type="submit" class="text-red-400 hover:text-red-300">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php endif; ?>

                <?php if ($section === 'activities'): ?>
                <!-- Activities -->
                <div class="space-y-4">
                    <div class="overflow-x-auto bg-surface-dark border border-border-dark rounded-lg">
                        <table class="min-w-full text-sm text-left">
                            <thead class="text-xs uppercase bg-background-dark text-text-secondary-dark">
                                <tr>
                                    <th class="px-6 py-3">Timestamp</th>
                                    <th class="px-6 py-3">Tipe</th>
                                    <th class="px-6 py-3">Pesan</th>
                                    <th class="px-6 py-3">Pengguna</th>
                                </tr>
                            </thead>
                            <tbody class="text-text-secondary-dark">
                                <?php foreach ($activities as $a): ?>
                                <tr class="border-b border-border-dark hover:bg-primary/5 transition-colors-transform">
                                    <td class="px-6 py-4 font-medium"><?php echo date('Y-m-d H:i:s', strtotime($a['created_at'] ?? 'now')); ?></td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-status/20 text-blue-status">
                                            <?php echo htmlspecialchars($a['log_level'] ?? 'INFO'); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($a['message'] ?? ''); ?></td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($a['username'] ?? 'System'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php endif; ?>

                <?php if ($section === 'logs'): ?>
                <!-- System Logs -->
                <div class="space-y-4">
                    <div class="flex gap-2 mb-4">
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Yakin hapus semua log?');">
                            <input type="hidden" name="action" value="clear_logs" />
                            <button type="submit" class="px-4 py-2 bg-red-status rounded text-white hover:bg-red-status/80">Hapus Semua Log</button>
                        </form>
                    </div>
                    <div class="overflow-x-auto bg-surface-dark border border-border-dark rounded-lg">
                        <table class="min-w-full text-sm text-left">
                            <thead class="text-xs uppercase bg-background-dark text-text-secondary-dark">
                                <tr>
                                    <th class="px-6 py-3">Timestamp</th>
                                    <th class="px-6 py-3">Level</th>
                                    <th class="px-6 py-3">Pesan</th>
                                    <th class="px-6 py-3">Pengguna</th>
                                    <th class="px-6 py-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="text-text-secondary-dark">
                                <?php foreach ($logs as $log): ?>
                                <tr class="border-b border-border-dark hover:bg-primary/5 transition-colors-transform">
                                    <td class="px-6 py-4 font-medium"><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                                    <td class="px-6 py-4">
                                        <?php
                                        $levelClass = 'bg-green-status/20 text-green-status';
                                        if ($log['log_level'] === 'WARNING') $levelClass = 'bg-yellow-status/20 text-yellow-status';
                                        if ($log['log_level'] === 'ERROR') $levelClass = 'bg-red-status/20 text-red-status';
                                        ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $levelClass; ?>">
                                            <?php echo htmlspecialchars($log['log_level']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($log['message']); ?></td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></td>
                                    <td class="px-6 py-4">
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Yakin hapus log?');">
                                            <input type="hidden" name="action" value="delete_log" />
                                            <input type="hidden" name="log_id" value="<?php echo $log['id']; ?>" />
                                            <button type="submit" class="text-red-400 hover:text-red-300">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php endif; ?>
                
                <!-- SectionHeader & Data Table for Dashboard Logs -->
                <?php if ($section === 'dashboard'): ?>
                <div class="space-y-4 animate-fade-in delay-400">
                    <h2 class="text-[22px] font-bold leading-tight tracking-[-0.015em] text-primary">Log Sistem Terbaru</h2>
                    <div class="overflow-x-auto bg-surface-dark border border-border-dark rounded-lg">
                        <table class="min-w-full text-sm text-left">
                            <thead class="text-xs uppercase bg-background-dark text-text-secondary-dark">
                                <tr>
                                    <th class="px-6 py-3" scope="col">Timestamp</th>
                                    <th class="px-6 py-3" scope="col">Level</th>
                                    <th class="px-6 py-3" scope="col">Pesan</th>
                                    <th class="px-6 py-3" scope="col">Pengguna</th>
                                </tr>
                            </thead>
                            <tbody class="text-text-secondary-dark">
                                <?php foreach ($logs as $log): ?>
                                <tr class="border-b border-border-dark hover:bg-primary/5 transition-colors-transform">
                                    <td class="px-6 py-4 font-medium"><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                                    <td class="px-6 py-4">
                                        <?php
                                        $levelClass = '';
                                        switch ($log['log_level']) {
                                            case 'INFO':
                                                $levelClass = 'bg-green-status/20 text-green-status';
                                                break;
                                            case 'WARNING':
                                                $levelClass = 'bg-yellow-status/20 text-yellow-status';
                                                break;
                                            case 'ERROR':
                                                $levelClass = 'bg-red-status/20 text-red-status';
                                                break;
                                        }
                                        ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $levelClass; ?>">
                                            <?php echo htmlspecialchars($log['log_level']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($log['message']); ?></td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" style="display:none;" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-black/50">
        <div class="w-full max-w-md bg-surface-dark rounded-lg p-6">
            <h3 class="text-lg font-bold text-primary mb-4">Edit Pengguna</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update_user" />
                <input id="edit_user_id" type="hidden" name="user_id" />
                <div class="mb-3">
                    <label class="block text-sm text-text-secondary-dark mb-1">Username</label>
                    <input id="edit_user_username" name="username" class="w-full p-2 rounded bg-gray-800 text-white border border-gray-700" />
                </div>
                <div class="mb-3">
                    <label class="block text-sm text-text-secondary-dark mb-1">Email</label>
                    <input id="edit_user_email" name="email" class="w-full p-2 rounded bg-gray-800 text-white border border-gray-700" />
                </div>
                <div class="mb-3">
                    <label class="block text-sm text-text-secondary-dark mb-1">ELO Rating</label>
                    <input id="edit_user_elo" name="elo_rating" type="number" class="w-full p-2 rounded bg-gray-800 text-white border border-gray-700" />
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeEditUser()" class="px-3 py-2 bg-gray-700 rounded text-white hover:bg-gray-600">Batal</button>
                    <button type="submit" class="px-3 py-2 bg-primary rounded text-black font-medium hover:bg-primary-light">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Game Modal -->
    <div id="editGameModal" style="display:none;" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-black/50">
        <div class="w-full max-w-md bg-surface-dark rounded-lg p-6">
            <h3 class="text-lg font-bold text-primary mb-4">Edit Pertandingan</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update_game" />
                <input id="edit_game_id" type="hidden" name="game_id" />
                <div class="mb-3">
                    <label class="block text-sm text-text-secondary-dark mb-1">Status</label>
                    <select id="edit_game_status" name="game_status" class="w-full p-2 rounded bg-gray-800 text-white border border-gray-700">
                        <option value="active">active</option>
                        <option value="finished">finished</option>
                        <option value="draw">draw</option>
                        <option value="resigned">resigned</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeEditGame()" class="px-3 py-2 bg-gray-700 rounded text-white hover:bg-gray-600">Batal</button>
                    <button type="submit" class="px-3 py-2 bg-primary rounded text-black font-medium hover:bg-primary-light">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditUser(id, username, email, elo) {
            document.getElementById('edit_user_id').value = id;
            document.getElementById('edit_user_username').value = username;
            document.getElementById('edit_user_email').value = email;
            document.getElementById('edit_user_elo').value = elo;
            document.getElementById('editUserModal').style.display = 'flex';
        }
        function closeEditUser() {
            document.getElementById('editUserModal').style.display = 'none';
        }
        function openEditGame(id, status) {
            document.getElementById('edit_game_id').value = id;
            document.getElementById('edit_game_status').value = status;
            document.getElementById('editGameModal').style.display = 'flex';
        }
        function closeEditGame() {
            document.getElementById('editGameModal').style.display = 'none';
        }
    </script>
</body>
</html>