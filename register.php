<?php
require_once 'config.php';

$error = '';
$success = '';

// Redirect if already logged in
if (isLoggedIn()) {
    redirectTo('dashboard.php');
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $elo_rating = isset($_POST['elo_rating']) && !empty($_POST['elo_rating']) ? intval($_POST['elo_rating']) : 1200;
    
    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Username, email, dan password harus diisi!';
    } elseif (strlen($username) < 3) {
        $error = 'Username minimal 3 karakter!';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } else {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Username sudah digunakan!';
        } else {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $error = 'Email sudah terdaftar!';
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $avatar_url = null;
                
                // Handle file upload if exists
                if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                    $avatar_filename = handleFileUpload($_FILES['avatar'], 0); // 0 for temp, will update after user creation
                }
                
                // Insert new user
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, elo_rating, avatar_url) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssis", $username, $email, $hashed_password, $elo_rating, $avatar_filename);
                
                if ($stmt->execute()) {
                    $user_id = $conn->insert_id;
                    
                    // If avatar was uploaded, update the filename with correct user_id
                    if (isset($avatar_filename)) {
                        $new_avatar_filename = 'avatar_' . $user_id . '_' . time() . '.' . pathinfo($avatar_filename, PATHINFO_EXTENSION);
                        $old_path = UPLOAD_DIR . $avatar_filename;
                        $new_path = UPLOAD_DIR . $new_avatar_filename;
                        
                        if (rename($old_path, $new_path)) {
                            updateUserAvatar($user_id, $new_avatar_filename);
                        }
                    }
                    
                    // Log activity
                    logSystem('INFO', "New user registered: '{$username}'", $user_id, $username);
                    
                    // Redirect to login with success message
                    redirectWithMessage('login.php', 'success', 'Akun berhasil dibuat! Silakan login.');
                } else {
                    $error = 'Terjadi kesalahan saat mendaftar. Silakan coba lagi.';
                }
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
    <title>Buat Akun Baru - ChessLearn</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <link href="style.css" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#D97706",
                        "primary-focus": "#B45309",
                        "background-light": "#FDFBF6",
                        "background-dark": "#1C1917",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                },
            },
        }
    </script>
</head>
<body class="font-display bg-background-light dark:bg-background-dark">
    <div class="relative flex min-h-screen w-full flex-col items-center justify-center bg-background-light dark:bg-background-dark overflow-x-hidden p-4 sm:p-6 lg:p-8">
        <div class="absolute inset-0 z-0">
            <img class="h-full w-full object-cover opacity-10 blur-sm" src="https://lh3.googleusercontent.com/aida-public/AB6AXuC01TADrMczkSvUlG4am6i8bKRabKD-ZDg-vngRsMhlDM7qVtNStzTUlMeBM-J0iSFoXtvmLPbfLmY5cAJoPFgcxSh2atnxg-10bPsMhuPv83_H2gftHKuik7jr08XLsbdn7Oe7dTDK8G8bpJ0BERNMvUOz3Fwq9tm5Etez1NjqEwnKS7n6bl_yv16raAahwz2c44UnnosfKYR2bli_ef_oeZPTTSpZTVyqxU_4cukf6r0Rax2jcNrRjAHnsqcQOV9-xSXbjg6DGoU"/>
            <div class="absolute inset-0 bg-gradient-to-t from-background-light dark:from-background-dark via-background-light/80 dark:via-background-dark/80 to-transparent"></div>
        </div>
        
        <div class="relative z-10 flex w-full max-w-md flex-col items-center rounded-xl bg-background-light/80 dark:bg-background-dark/80 backdrop-blur-lg border border-white/10 dark:border-white/10 p-6 sm:p-10 shadow-2xl">
            <form method="POST" enctype="multipart/form-data" class="w-full">
                <div class="flex w-full flex-col gap-2 text-center mb-8">
                    <p class="text-slate-900 dark:text-white text-3xl sm:text-4xl font-black leading-tight tracking-tighter font-display">Buat Akun Baru</p>
                    <p class="text-slate-600 dark:text-slate-400 text-base font-normal leading-normal font-display">Bergabunglah dengan komunitas catur kami untuk mulai bermain dan belajar.</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error w-full mb-4">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <div class="flex w-full flex-col gap-4">
                    <!-- Foto Profil -->
                    <div class="form-input-wrapper w-full">
                        <label class="flex flex-col w-full">
                            <p class="text-slate-900 dark:text-white text-base font-medium leading-normal pb-2 font-display">Foto Profil (Opsional)</p>
                            <div class="flex items-center gap-4">
                                <div class="relative">
                                    <img id="avatarPreview" src="https://ui-avatars.com/api/?name=User&background=d97706&color=fff&size=100" class="w-16 h-16 rounded-full object-cover border-2 border-slate-300 dark:border-slate-600">
                                    <input type="file" id="avatar" name="avatar" accept="image/*" class="hidden" onchange="previewAvatar(this)">
                                    <label for="avatar" class="absolute bottom-0 right-0 bg-primary text-white p-1 rounded-full cursor-pointer hover:bg-primary-focus transition-colors">
                                        <span class="material-symbols-outlined text-sm">edit</span>
                                    </label>
                                </div>
                                <div class="flex-1">
                                    <button type="button" onclick="document.getElementById('avatar').click()" class="text-slate-600 dark:text-slate-400 text-sm hover:text-primary dark:hover:text-primary transition-colors">
                                        Klik untuk upload foto
                                    </button>
                                    <p class="text-slate-500 dark:text-slate-500 text-xs mt-1">Format: JPG, PNG, GIF (Maks. 5MB)</p>
                                </div>
                            </div>
                        </label>
                    </div>
                    
                    <div class="form-input-wrapper w-full">
                        <label class="flex flex-col w-full">
                            <p class="text-slate-900 dark:text-white text-base font-medium leading-normal pb-2 font-display">Nama Pengguna</p>
                            <input name="username" class="flex w-full resize-none overflow-hidden rounded-lg text-slate-900 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-slate-300 dark:border-slate-700 bg-white/50 dark:bg-slate-900/50 focus:border-primary h-12 sm:h-14 placeholder:text-slate-500 dark:placeholder:text-slate-500 p-3 sm:p-4 text-base font-normal leading-normal font-display transition-shadow" placeholder="pilih_username_unik" required/>
                        </label>
                    </div>
                    
                    <div class="form-input-wrapper w-full">
                        <label class="flex flex-col w-full">
                            <p class="text-slate-900 dark:text-white text-base font-medium leading-normal pb-2 font-display">Alamat Email</p>
                            <input name="email" type="email" class="flex w-full resize-none overflow-hidden rounded-lg text-slate-900 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-slate-300 dark:border-slate-700 bg-white/50 dark:bg-slate-900/50 focus:border-primary h-12 sm:h-14 placeholder:text-slate-500 dark:placeholder:text-slate-500 p-3 sm:p-4 text-base font-normal leading-normal font-display transition-shadow" placeholder="contoh@email.com" required/>
                        </label>
                    </div>
                    
                    <div class="form-input-wrapper flex flex-col w-full">
                        <label class="flex flex-col">
                            <p class="text-slate-900 dark:text-white text-base font-medium leading-normal pb-2 font-display">Kata Sandi</p>
                            <div class="input-group flex w-full items-stretch rounded-lg border border-slate-300 dark:border-slate-700 bg-white/50 dark:bg-slate-900/50 focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/50">
                                <input id="password" name="password" class="flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-l-lg text-slate-900 dark:text-white focus:outline-0 focus:ring-0 border-0 bg-transparent h-12 sm:h-14 placeholder:text-slate-500 dark:placeholder:text-slate-500 p-3 sm:p-4 pr-2 text-base font-normal leading-normal font-display" placeholder="Masukkan kata sandi yang kuat" type="password" required/>
                                <button type="button" onclick="togglePassword()" aria-label="Toggle password visibility" class="text-slate-500 dark:text-slate-400 flex items-center justify-center px-3 sm:px-4 rounded-r-lg hover:bg-slate-200 dark:hover:bg-slate-800 transition-colors">
                                    <span id="toggleIcon" class="material-symbols-outlined text-2xl">visibility</span>
                                </button>
                            </div>
                        </label>
                        <p class="text-slate-600 dark:text-slate-400 text-sm font-normal leading-normal pt-2 font-display">Minimal 8 karakter dengan kombinasi huruf dan angka.</p>
                    </div>
                    
                    <div class="form-input-wrapper w-full">
                        <label class="flex flex-col w-full">
                            <div class="flex items-center gap-2 pb-2">
                                <p class="text-slate-900 dark:text-white text-base font-medium leading-normal font-display">Rating Elo Saat Ini (Opsional)</p>
                                <div class="tooltip">
                                    <span class="material-symbols-outlined text-slate-500 dark:text-slate-400 cursor-help" style="font-size: 20px;">help_outline</span>
                                    <span class="tooltiptext">Jika Anda memiliki rating FIDE atau dari platform lain, Anda dapat memasukkannya di sini.</span>
                                </div>
                            </div>
                            <input name="elo_rating" type="number" min="0" max="3000" class="flex w-full resize-none overflow-hidden rounded-lg text-slate-900 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-slate-300 dark:border-slate-700 bg-white/50 dark:bg-slate-900/50 focus:border-primary h-12 sm:h-14 placeholder:text-slate-500 dark:placeholder:text-slate-500 p-3 sm:p-4 text-base font-normal leading-normal font-display transition-shadow" placeholder="Contoh: 1500"/>
                        </label>
                    </div>
                </div>
                
                <div class="w-full mt-6 text-center text-sm text-slate-600 dark:text-slate-400 bg-amber-100/50 dark:bg-amber-900/30 p-3 rounded-lg font-display">
                    <p>Rating Elo awal Anda akan ditetapkan pada 1200 jika kolom di atas tidak diisi.</p>
                </div>
                
                <button type="submit" class="btn-submit flex w-full items-center justify-center rounded-lg bg-primary hover:bg-primary-focus h-12 sm:h-14 mt-8 text-white text-base font-bold leading-normal tracking-wide focus:outline-none focus:ring-2 focus:ring-primary/80 focus:ring-offset-2 focus:ring-offset-background-light dark:focus:ring-offset-background-dark font-display">
                    Buat Akun
                </button>
                
                <p class="mt-8 text-center text-base text-slate-600 dark:text-slate-400 font-display">
                    Sudah punya akun? <a class="font-medium text-primary hover:underline focus:outline-none focus:ring-2 focus:ring-primary/50 rounded-sm" href="login.php">Masuk di sini</a>
                </p>
            </form>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.textContent = 'visibility_off';
            } else {
                passwordInput.type = 'password';
                toggleIcon.textContent = 'visibility';
            }
        }
        
        function previewAvatar(input) {
            const preview = document.getElementById('avatarPreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Check file size before upload
        document.getElementById('avatar').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file && file.size > 5 * 1024 * 1024) { // 5MB
                alert('File terlalu besar! Maksimal 5MB.');
                this.value = '';
                document.getElementById('avatarPreview').src = 'https://ui-avatars.com/api/?name=User&background=d97706&color=fff&size=100';
            }
        });
    </script>
</body>
</html>
