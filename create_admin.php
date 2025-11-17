<?php
require_once 'config.php';

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Semua field harus diisi!';
    } elseif ($password !== $confirm_password) {
        $error = 'Password tidak cocok!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } else {
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Username atau email sudah terdaftar!';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            
            // Insert new admin user
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, elo_rating, is_admin, created_at) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)");
            $is_admin = 1;
            $elo = 1500;
            $stmt->bind_param("sssii", $username, $email, $hashed_password, $elo, $is_admin);
            
            if ($stmt->execute()) {
                $message = "Admin user '$username' berhasil dibuat!";
            } else {
                $error = 'Gagal membuat admin: ' . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Buat Admin - ChessLearn</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <link href="style.css" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#FFD700",
                        "background-dark": "#1A1A1A",
                        "surface-dark": "#2C2C2C",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                },
            },
        }
    </script>
</head>
<body class="font-display bg-background-dark text-white">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md bg-surface-dark rounded-lg p-8 border border-gray-700">
            <div class="flex items-center justify-center mb-6">
                <span class="material-symbols-outlined text-primary" style="font-size: 40px; font-variation-settings: 'FILL' 1;">security_admin</span>
            </div>
            
            <h1 class="text-2xl font-bold text-center text-primary mb-2">Buat Admin Account</h1>
            <p class="text-center text-gray-400 mb-6">Silakan isi form di bawah untuk membuat akun admin baru</p>
            
            <?php if ($error): ?>
                <div class="bg-red-500/20 border border-red-500 text-red-400 p-4 rounded mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($message): ?>
                <div class="bg-green-500/20 border border-green-500 text-green-400 p-4 rounded mb-4">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Username</label>
                    <input type="text" name="username" class="w-full px-4 py-2 rounded bg-gray-800 border border-gray-700 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary" placeholder="Masukkan username" required />
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Email</label>
                    <input type="email" name="email" class="w-full px-4 py-2 rounded bg-gray-800 border border-gray-700 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary" placeholder="Masukkan email" required />
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Password</label>
                    <input type="password" name="password" class="w-full px-4 py-2 rounded bg-gray-800 border border-gray-700 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary" placeholder="Minimal 6 karakter" required />
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Konfirmasi Password</label>
                    <input type="password" name="confirm_password" class="w-full px-4 py-2 rounded bg-gray-800 border border-gray-700 text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary" placeholder="Ulangi password" required />
                </div>
                
                <button type="submit" class="w-full bg-primary hover:bg-primary/90 text-black font-bold py-2 rounded transition-all duration-200">
                    Buat Admin Account
                </button>
            </form>
            
            <div class="mt-6 text-center">
                <p class="text-gray-400 text-sm">Sudah punya akun? <a href="login.php" class="text-primary hover:underline">Login di sini</a></p>
            </div>
        </div>
    </div>
</body>
</html>
