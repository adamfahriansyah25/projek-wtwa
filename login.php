<?php
require_once 'config.php';

$error = '';
$success = '';

// Check for flash messages
$flashMessage = getFlashMessage();
if ($flashMessage) {
    if ($flashMessage['type'] === 'success') {
        $success = $flashMessage['message'];
    } else {
        $error = $flashMessage['message'];
    }
}

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        redirectTo('admin.php');
    } else {
        redirectTo('dashboard.php');
    }
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_email = sanitize($_POST['username_email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username_email) || empty($password)) {
        $error = 'Semua field harus diisi!';
    } else {
        // Check if user exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username_email, $username_email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['elo_rating'] = $user['elo_rating'];
                $_SESSION['is_admin'] = intval($user['is_admin']); // Ensure it's an integer
                
                // Update last login
                updateLastLogin($user['id']);
                
                // Log activity
                logSystem('INFO', "User '{$user['username']}' logged in successfully.", $user['id'], $user['username']);
                
                // Redirect based on role
                if (intval($user['is_admin']) === 1) {
                    redirectTo('admin.php');
                } else {
                    redirectTo('dashboard.php');
                }
            } else {
                $error = 'Password salah!';
                logSystem('WARNING', "Failed login attempt for user '{$username_email}'", null, null);
            }
        } else {
            $error = 'Username atau email tidak ditemukan!';
        }
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Login - ChessLearn</title>
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
                        "primary": "#F59E0B",
                        "background-light": "#F5F5F5",
                        "background-dark": "#1E1E1E",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                },
            },
        }
    </script>
</head>
<body class="font-display">
    <div class="relative flex min-h-screen w-full flex-col items-center justify-center bg-background-light dark:bg-background-dark group/design-root overflow-x-hidden p-4">
        <div class="absolute inset-0 bg-cover bg-center opacity-5 dark:opacity-[0.03]" style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuAIEmhEojzIUquqrAqaJOuNM_bo56gSMskXtIuQIsM_vEQgrJ34FEbFaUuItsYwcjyzKC3mHoihTiZbalUD8jZ2ManbAXGumkpdXn-FAhYEE0ZqufEt6VFjm8HYOAAWkuXJp3QRsJs9FPD1xudb735CCXu8ZvgBcughH-wkYRBJnaPv1Q1n-8QCZp8PSGW84WxYN-YmJLzAAqNysQV18X2mQRu9ACP-6RmtiErQH5_tkR_b31OrWm-cpafoKf8BZeGCJuZkbmBNmKo');"></div>
        
        <div class="relative layout-container flex h-full w-full max-w-md grow flex-col justify-center">
            <form method="POST" class="flex flex-col items-center">
                <div class="mb-4">
                    <span class="material-symbols-outlined text-primary" style="font-size: 48px; font-variation-settings: 'FILL' 1;">chess</span>
                </div>
                
                <h1 class="text-slate-900 dark:text-white text-3xl font-bold leading-tight tracking-tight text-center pb-2">Selamat Datang Kembali</h1>
                <p class="text-slate-500 dark:text-slate-400 text-base font-normal text-center mb-8">Login ke Akun Anda</p>
                
                <?php if ($error): ?>
                    <div class="alert alert-error w-full mb-4">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success w-full mb-4">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <div class="w-full flex flex-col gap-4">
                    <div class="flex flex-wrap items-end gap-4 w-full">
                        <label class="flex flex-col min-w-40 flex-1">
                            <p class="text-slate-700 dark:text-white text-sm font-medium leading-normal pb-2">Email atau Nama Pengguna</p>
                            <input name="username_email" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-900 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:border-primary h-12 placeholder:text-slate-400 dark:placeholder:text-slate-500 p-3 text-base font-normal leading-normal transition-all duration-300" placeholder="Masukkan email atau nama pengguna Anda" required/>
                        </label>
                    </div>
                    
                    <div class="flex flex-wrap items-end gap-4 w-full">
                        <label class="flex flex-col min-w-40 flex-1">
                            <p class="text-slate-700 dark:text-white text-sm font-medium leading-normal pb-2">Kata Sandi</p>
                            <div class="flex w-full flex-1 items-stretch rounded-lg">
                                <input id="password" name="password" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-l-lg text-slate-900 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:border-primary h-12 placeholder:text-slate-400 dark:placeholder:text-slate-500 p-3 rounded-r-none border-r-0 text-base font-normal leading-normal transition-all duration-300" placeholder="Masukkan kata sandi Anda" type="password" required/>
                                <div onclick="togglePassword()" class="cursor-pointer text-slate-400 dark:text-slate-500 flex border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 items-center justify-center px-3 rounded-r-lg border-l-0">
                                    <span id="toggleIcon" class="material-symbols-outlined text-xl">visibility_off</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
                
                <div class="w-full text-right mt-3">
                    <a class="text-primary hover:underline text-sm font-medium leading-normal" href="#">Lupa Kata Sandi?</a>
                </div>
                
                <div class="flex py-6 w-full">
                    <button type="submit" class="btn-submit flex min-w-[84px] w-full cursor-pointer items-center justify-center overflow-hidden rounded-lg h-12 px-5 flex-1 bg-primary hover:bg-primary/90 transition-all duration-300 ease-in-out hover:scale-105 active:scale-100 text-white text-base font-bold leading-normal tracking-[0.015em]">
                        <span class="truncate">Login</span>
                    </button>
                </div>
                
                <div class="flex justify-center items-center gap-2 pt-4">
                    <p class="text-slate-500 dark:text-slate-400 text-sm font-normal">Pengguna baru?</p>
                    <a class="text-primary hover:underline text-sm font-bold" href="register.php">Buat Akun</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.textContent = 'visibility';
            } else {
                passwordInput.type = 'password';
                toggleIcon.textContent = 'visibility_off';
            }
        }
    </script>
</body>
</html>
