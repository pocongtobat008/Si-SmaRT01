<?php
session_start();
require_once 'config/database.php';

// Jika sudah login sebagai penjual, lempar ke ruang_penjual
if (isset($_SESSION['penjual_id'])) {
    header("Location: ruang_penjual.php");
    exit();
}

$error = "";

// Proses Login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';

    if ($user && $pass) {
        $stmt = $pdo->prepare("SELECT * FROM pasar_penjual WHERE username = ?");
        $stmt->execute([$user]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && password_verify($pass, $row['password'])) {
            if ($row['status'] == 'Nonaktif') {
                $error = "Akun toko Anda sedang dinonaktifkan oleh Admin.";
            } else {
                $_SESSION['penjual_id'] = $row['id'];
                $_SESSION['penjual_nama_toko'] = $row['nama_toko'];
                header("Location: ruang_penjual.php");
                exit();
            }
        } else {
            $error = "Username atau password salah.";
        }
    } else {
        $error = "Mohon isi semua bidang.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Penjual UMKM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-[2.5rem] shadow-2xl p-8 md:p-10 relative overflow-hidden border border-slate-100">
        <a href="pasar.php" class="absolute top-6 right-6 w-10 h-10 bg-slate-50 text-slate-400 rounded-xl flex items-center justify-center hover:bg-emerald-50 hover:text-emerald-500 transition-all">
            <i class="fas fa-times"></i>
        </a>
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-gradient-to-br from-emerald-500 to-teal-600 text-white rounded-[1.5rem] flex items-center justify-center mx-auto mb-6 text-3xl shadow-lg shadow-emerald-200">
                <i class="fas fa-store"></i>
            </div>
            <h2 class="text-3xl font-black text-slate-800 tracking-tight">Login Toko</h2>
            <p class="text-slate-500 font-medium text-sm mt-2">Masuk ke Ruang Penjual UMKM Warga</p>
        </div>

        <?php if($error): ?>
        <div class="bg-red-50 border border-red-100 text-red-500 text-xs font-bold p-4 rounded-2xl mb-6 flex items-center gap-3">
            <i class="fas fa-exclamation-circle text-lg"></i> <?= $error ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="space-y-5">
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Username Toko</label>
                <div class="relative">
                    <i class="fas fa-user absolute left-5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="username" required class="w-full bg-slate-50 border border-slate-100 rounded-[1.25rem] py-4 pl-12 pr-4 font-bold text-slate-800 focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all text-sm" placeholder="username_toko">
                </div>
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Password</label>
                <div class="relative">
                    <i class="fas fa-lock absolute left-5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="password" name="password" required class="w-full bg-slate-50 border border-slate-100 rounded-[1.25rem] py-4 pl-12 pr-4 font-bold text-slate-800 focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all text-sm" placeholder="••••••••">
                </div>
            </div>
            <button type="submit" class="w-full bg-emerald-600 text-white font-black text-sm py-4 rounded-[1.25rem] shadow-xl shadow-emerald-200 hover:bg-emerald-700 transition-all flex items-center justify-center gap-2 mt-4 active:scale-95">
                <i class="fas fa-sign-in-alt"></i> MASUK SEKARANG
            </button>
        </form>
    </div>
</body>
</html>