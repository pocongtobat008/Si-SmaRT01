<?php
require_once 'config/database.php';

// Auto-create database table jika belum ada (Tanpa repot buka phpMyAdmin)
$pdo->exec("CREATE TABLE IF NOT EXISTS `pasar_produk` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_produk` varchar(255) NOT NULL,
  `deskripsi` text,
  `harga` decimal(15,2) NOT NULL DEFAULT 0,
  `foto` text,
  `penjual_nama` varchar(100) NOT NULL,
  `no_wa` varchar(20) NOT NULL,
  `kategori` varchar(50) DEFAULT 'Umum',
  `status` enum('Tersedia','Habis') DEFAULT 'Tersedia',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$pdo->exec("CREATE TABLE IF NOT EXISTS `pasar_profil` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_toko` varchar(255) NOT NULL,
  `no_wa` varchar(20) NOT NULL,
  `alamat` text,
  `deskripsi` text,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Ambil data produk
$search = $_GET['q'] ?? '';
$kategori = $_GET['kategori'] ?? '';

$limit = 25;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$queryBase = " FROM pasar_produk WHERE 1=1";
$params = [];
if($search) {
    $queryBase .= " AND (nama_produk LIKE ? OR deskripsi LIKE ? OR penjual_nama LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if($kategori && $kategori !== 'Terlaris') {
    $queryBase .= " AND kategori = ?";
    $params[] = $kategori;
}

// Hitung total produk
$stmtTotal = $pdo->prepare("SELECT COUNT(*)" . $queryBase);
$stmtTotal->execute($params);
$totalProduk = $stmtTotal->fetchColumn();
$totalPages = ceil($totalProduk / $limit);

// Ambil data produk dengan limit & offset
$query = "SELECT *" . $queryBase;
if($kategori === 'Terlaris') {
    $query .= " ORDER BY klik_beli DESC, created_at DESC";
} else {
    $query .= " ORDER BY status DESC, created_at DESC";
}
$query .= " LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$produk = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil data Slider Promosi dari Database
$stmtSlider = $pdo->query("SELECT * FROM pasar_slider ORDER BY urutan ASC");
$sliders = $stmtSlider->fetchAll(PDO::FETCH_ASSOC);

// Ambil setting untuk info RT
$stmtSet = $pdo->query("SELECT setting_key, setting_value FROM web_settings");
$settings = $stmtSet->fetchAll(PDO::FETCH_KEY_PAIR);
$web_nama = $settings['web_nama'] ?? 'Portal Warga';
$bg_overlay = $settings['web_hero_image_1'] ?? 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?q=80&w=1600';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UMKM & Pasar Warga - <?= htmlspecialchars($web_nama) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <!-- Swiper CSS untuk Slider Promosi -->
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: rgba(248, 250, 252, 0.85); }
        .hide-scroll::-webkit-scrollbar { display: none; }
        .hide-scroll { -ms-overflow-style: none; scrollbar-width: none; }
        
        /* Efek Latar Belakang Semi Transparan Menyesuaikan Tema Portal */
        .background-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1;
            background-image: url('<?= htmlspecialchars($bg_overlay) ?>');
            background-size: cover; background-position: center center; background-repeat: no-repeat;
            filter: grayscale(20%) blur(3px); opacity: 0.15;
        }

        /* Custom Swiper Pagination */
        .swiper-pagination-bullet-active { background-color: #10b981 !important; }

        /* Desain Kartu Produk Sesuai Permintaan */
        .product-card {
            --bg: #fff;
            --title-color: #fff;
            --title-color-hover: #1e293b;
            --text-color: #64748b;
            --button-color: #10b981;
            --button-color-hover: #059669;
            background: var(--bg);
            border-radius: 1.5rem;
            padding: 0.5rem;
            width: 100%;
            height: 320px; /* Tinggi dinamis responsif */
            overflow: hidden;
            position: relative;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            transition: all 0.4s ease;
            border: 1px solid #f1f5f9;
        }

        @media (min-width: 768px) {
            .product-card { height: 380px; }
        }

        /* Mobile specific adjustments to ensure it looks modern */
        @media (max-width: 640px) {
            .product-card {
                height: 280px;
                border-radius: 1.25rem;
            }
            .product-card .img-overlay h2 {
                font-size: 0.9rem;
            }
            .product-card .img-overlay .price {
                font-size: 1rem;
            }
            .product-card .btn-buy {
                padding: 0.5rem 0.8rem 0.5rem 1.8rem;
                font-size: 0.75rem;
            }
        }

        /* Glassmorphism bottom overlay */
        .product-card::before {
            content: "";
            position: absolute;
            width: calc(100% - 1rem);
            height: 45%;
            bottom: 0.5rem;
            left: 0.5rem;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            backdrop-filter: blur(2px);
            border-radius: 0 0 1rem 1rem;
            transition: transform 0.4s ease, opacity 0.4s ease;
            z-index: 1;
        }

        /* Glassmorphism Badge */
        .glass-badge {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px) saturate(180%);
            -webkit-backdrop-filter: blur(10px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            color: #fff;
            font-size: 10px;
            font-weight: 800;
            padding: 4px 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .productSwiper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: 50% 50%;
            border-radius: 1rem;
            display: block;
            transition: transform 0.4s ease;
        }

        /* Swiper Product Custom */
        .productSwiper { width: 100%; height: 100%; border-radius: 1rem; }
        .productSwiper .swiper-pagination-fraction {
            bottom: unset !important;
            top: 15px !important;
            left: 50% !important;
            transform: translateX(-50%);
            width: auto !important;
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.1);
            color: white;
            font-size: 9px;
            font-weight: 900;
            padding: 4px 12px;
            border-radius: 20px;
            z-index: 20;
        }

        /* Teks Nama & Harga di Dalam Foto Utama */
        .product-card .img-overlay {
            position: absolute;
            bottom: 1.5rem;
            left: 1.25rem;
            right: 1.25rem;
            z-index: 2;
            transition: transform 0.4s ease, opacity 0.4s ease;
            pointer-events: none;
        }

        .product-card .img-overlay h2 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--title-color);
            line-height: 1.3;
            text-shadow: 0 2px 4px rgba(0,0,0,0.8);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-card .img-overlay .price {
            display: inline-block;
            margin-top: 0.25rem;
            font-size: 1.15rem;
            font-weight: 900;
            color: #34d399; /* Emerald 400 */
            text-shadow: 0 2px 4px rgba(0,0,0,0.8);
        }

        /* Bagian Informasi yang Muncul Saat Hover */
        .product-card > section {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 45%;
            padding: 1rem 1.25rem;
            display: flex;
            flex-direction: column;
            z-index: 3;
            opacity: 0;
            transform: translateY(20px);
            transition: transform 0.4s ease, opacity 0.4s ease;
            pointer-events: none;
        }

        .product-card > section p {
            font-size: 0.8rem;
            line-height: 1.4;
            color: var(--text-color);
            margin: 0 0 auto 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-card > section .card-actions {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 0.5rem;
        }

        .product-card .tag {
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--button-color-hover);
            background: rgba(16, 185, 129, 0.1);
            padding: 4px 8px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        /* Tombol Beli (+ Plus to Checkmark Morphing) */
        .product-card .btn-buy {
            border: none;
            border-radius: 1.25rem 1.25rem 1.5rem 1.25rem;
            font-size: 0.8rem;
            font-weight: bold;
            padding: 0.6rem 1.2rem 0.6rem 2rem;
            background: var(--button-color);
            color: #fff;
            cursor: pointer;
            position: relative;
            transition: all 0.33s ease;
            box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
            text-decoration: none;
            pointer-events: auto;
        }

        .product-card .btn-buy::before,
        .product-card .btn-buy::after {
            content: "";
            background: #fff;
            position: absolute;
            border-radius: 1rem;
            transition: all 0.25s ease-out;
        }

        .product-card .btn-buy::before { width: 0.75rem; height: 0.1rem; top: 50%; left: 0.9rem; }
        .product-card .btn-buy::after { width: 0.75rem; height: 0.1rem; top: 50%; left: 0.9rem; rotate: 90deg; }

        .product-card .btn-buy:hover { background: var(--button-color-hover); transform: scale(1.05); }
        
        .product-card .btn-buy.added { background: var(--button-color-hover); }
        .product-card .btn-buy.added::before { width: 0.4rem; top: 50%; left: 0.8rem; rotate: 45deg; translate: 0 2px; }
        .product-card .btn-buy.added::after { width: 0.7rem; top: 52%; left: 1.0rem; rotate: -45deg; translate: 0 -1px; }

        /* HOVER DYNAMICS */
        .product-card:hover {
            box-shadow: 0 20px 25px -5px rgba(16, 185, 129, 0.15);
            border-color: #10b981;
        }
        .product-card:hover::before { transform: translateY(100%); opacity: 0; }
        .product-card:hover .img-overlay { transform: translateY(-20px); opacity: 0; }
        .product-card:hover > section { transform: translateY(0); opacity: 1; pointer-events: auto; }

        /* --- STACKING CARDS CSS FOR PASAR --- */
        .stack-area-pasar {
            margin-top: 2rem;
            margin-bottom: 4rem;
            position: relative;
        }

        .card-stack-pasar {
            height: 300px;
            position: sticky;
            top: 15vh;
            background: #111;
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 30px;
            padding: 30px;
            margin-bottom: 8vh;
            display: flex;
            align-items: center;
            justify-content: space-between;
            overflow: hidden;
            backdrop-filter: blur(15px);
            box-shadow: 0 -15px 40px rgba(0, 0, 0, 0.4);
            transform-origin: center top;
            transition: transform 0.3s ease-out, filter 0.3s ease-out;
            will-change: transform;
            z-index: 5;
        }

        .card-stack-pasar:nth-child(1) { background: linear-gradient(145deg, #111, #000); }
        .card-stack-pasar:nth-child(2) { background: linear-gradient(145deg, #1a1a1a, #080808); }
        .card-stack-pasar:nth-child(3) { background: linear-gradient(145deg, #222, #111); }

        .card-stack-pasar h2 {
            font-size: 2.2rem;
            font-weight: 800;
            color: #fff;
            margin: 0 0 10px 0;
            text-transform: uppercase;
            letter-spacing: -1px;
        }

        .card-stack-content-pasar { flex: 1.5; padding-right: 20px; }
        .card-stack-pasar p { font-size: 0.95rem; color: #94a3b8; line-height: 1.5; margin: 0; }
        .card-stack-pasar .badge-pasar { 
            display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 9px; 
            font-weight: 800; color: #fff; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 2px;
        }
        
        .card-stack-img-pasar {
            flex: 1; height: 100%; border-radius: 20px; overflow: hidden; position: relative;
        }
        .card-stack-img-pasar img { width: 100%; height: 100%; object-fit: cover; opacity: 0.8; transition: transform 0.6s ease; }
        .card-stack-pasar:hover .card-stack-img-pasar img { transform: scale(1.1); opacity: 1; }

        @media (max-width: 768px) {
            .card-stack-pasar { flex-direction: column; height: auto; min-height: 400px; top: 18vh; padding: 24px; }
            .card-stack-content-pasar { padding-right: 0; margin-bottom: 20px; flex: 0; }
            .card-stack-pasar h2 { font-size: 1.6rem; }
            .card-stack-img-pasar { height: 180px; width: 100%; }
        }
    </style>
</head>
<body class="pb-24 selection:bg-emerald-200">
    <div class="background-overlay"></div>
    
    <!-- Sticky Top Navigation & Filter -->
    <div class="sticky top-0 z-50">
        <header class="bg-white/95 backdrop-blur-xl border-b border-slate-100">
            <div class="container mx-auto px-4 py-3 md:py-4">
                <div class="flex items-center gap-3 mb-3 md:mb-4">
                    <a href="index.php" class="w-10 h-10 bg-slate-50 rounded-full flex items-center justify-center text-slate-600 hover:bg-slate-200 transition">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div class="flex-1">
                        <h1 class="text-xl md:text-2xl font-extrabold text-slate-800 tracking-tight leading-tight">Pasar Warga</h1>
                        <p class="text-[10px] md:text-xs uppercase tracking-widest text-emerald-600 font-bold">Dari Warga, Untuk Warga</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="ruang_penjual.php" class="px-4 py-2.5 bg-emerald-600 text-white rounded-xl font-bold text-[10px] md:text-sm hover:bg-emerald-700 transition-all flex items-center gap-2 shadow-lg shadow-emerald-200 border border-emerald-500">
                            <i class="fa-solid fa-shop"></i> <span class="block">Ruang Penjual</span>
                        </a>
                        <a href="app.php" class="w-10 h-10 md:w-auto md:px-4 md:py-2.5 bg-slate-100 text-slate-600 rounded-xl font-bold text-xs md:text-sm hover:bg-slate-200 transition-colors flex items-center justify-center md:gap-2 border border-slate-200">
                            <i class="fas fa-user-circle text-lg md:text-base"></i> <span class="hidden md:inline">Masuk</span>
                        </a>
                    </div>
                </div>
                
                <!-- Search Bar -->
                <div class="relative">
                    <form action="pasar.php" method="GET">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari makanan, jasa, barang..." class="w-full bg-slate-100 text-slate-800 rounded-2xl py-3 pl-11 pr-4 font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500/30 transition-all text-sm border border-transparent focus:bg-white focus:border-emerald-200 shadow-inner">
                    </form>
                </div>
            </div>
        </header>

        <!-- Horizontal Categories -->
        <div class="bg-white/95 backdrop-blur-xl shadow-sm border-b border-slate-100">
            <div class="container mx-auto px-4 py-3 md:py-4">
                <div class="flex overflow-x-auto gap-2 hide-scroll pb-1 snap-x">
                    <a href="pasar.php" class="snap-start whitespace-nowrap px-5 py-2 rounded-full text-xs font-bold transition-all <?= $kategori == '' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-200' : 'bg-slate-100 text-slate-500 hover:bg-slate-200 hover:text-slate-800' ?>">Semua</a>
                    <a href="pasar.php?kategori=Terlaris" class="snap-start whitespace-nowrap px-5 py-2 rounded-full text-xs font-bold transition-all <?= $kategori == 'Terlaris' ? 'bg-orange-500 text-white shadow-md shadow-orange-200' : 'bg-slate-100 text-slate-500 hover:bg-slate-200 hover:text-slate-800' ?>">🔥 Terlaris</a>
                    <a href="pasar.php?kategori=Makanan" class="snap-start whitespace-nowrap px-5 py-2 rounded-full text-xs font-bold transition-all <?= $kategori == 'Makanan' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-200' : 'bg-slate-100 text-slate-500 hover:bg-slate-200 hover:text-slate-800' ?>">🍲 Makanan</a>
                    <a href="pasar.php?kategori=Jasa" class="snap-start whitespace-nowrap px-5 py-2 rounded-full text-xs font-bold transition-all <?= $kategori == 'Jasa' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-200' : 'bg-slate-100 text-slate-500 hover:bg-slate-200 hover:text-slate-800' ?>">🔧 Jasa</a>
                    <a href="pasar.php?kategori=Barang" class="snap-start whitespace-nowrap px-5 py-2 rounded-full text-xs font-bold transition-all <?= $kategori == 'Barang' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-200' : 'bg-slate-100 text-slate-500 hover:bg-slate-200 hover:text-slate-800' ?>">📦 Barang</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Grid -->
    <main class="container mx-auto px-4 pt-6 md:pt-8">

        <!-- Header Media Promosi Stacking Cards -->
        <div class="stack-area-pasar">
            <?php if(!empty($sliders)): ?>
                <?php foreach($sliders as $sl): ?>
                <div class="card-stack-pasar js-passed-card">
                    <div class="card-stack-content-pasar">
                        <span class="badge-pasar bg-<?= htmlspecialchars($sl['theme_color']) ?>-600 shadow-lg shadow-<?= htmlspecialchars($sl['theme_color']) ?>-900/40">
                            <i class="fas <?= htmlspecialchars($sl['badge_icon']) ?> mr-1"></i> <?= htmlspecialchars($sl['badge_text']) ?>
                        </span>
                        <h2><?= htmlspecialchars($sl['title']) ?></h2>
                        <p><?= htmlspecialchars($sl['subtitle']) ?></p>
                    </div>
                    <div class="card-stack-img-pasar">
                        <img src="<?= htmlspecialchars($sl['image']) ?>" alt="<?= htmlspecialchars($sl['title']) ?>">
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Dummy Stack 1 -->
                <div class="card-stack-pasar js-passed-card">
                    <div class="card-stack-content-pasar">
                        <span class="badge-pasar bg-emerald-600">Promo Spesial</span>
                        <h2>Diskon UMKM Warga</h2>
                        <p>Dukung usaha tetangga, nikmati potongan harga khusus hari ini.</p>
                    </div>
                    <div class="card-stack-img-pasar">
                       <img src="https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?q=80&w=1200" alt="Promo">
                    </div>
                </div>
                <!-- Dummy Stack 2 -->
                <div class="card-stack-pasar js-passed-card">
                    <div class="card-stack-content-pasar">
                        <span class="badge-pasar bg-orange-600">Buka Toko</span>
                        <h2>Jualan Makin Laris</h2>
                        <p>Daftarkan usaha atau jasa Anda melalui aplikasi SmaRT sekarang juga.</p>
                    </div>
                    <div class="card-stack-img-pasar">
                       <img src="https://images.unsplash.com/photo-1472851294608-062f824d29cc?q=80&w=1200" alt="Toko">
                    </div>
                </div>
                <!-- Dummy Stack 3 -->
                <div class="card-stack-pasar js-passed-card">
                    <div class="card-stack-content-pasar">
                        <span class="badge-pasar bg-blue-600">Belanja Mudah</span>
                        <h2>Penuhi Kebutuhan Harian</h2>
                        <p>Cari sayur, lauk, hingga jasa servis cukup dari rumah saja.</p>
                    </div>
                    <div class="card-stack-img-pasar">
                       <img src="https://images.unsplash.com/photo-1542838132-92c53300491e?q=80&w=1200" alt="Belanja">
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if(empty($produk)): ?>
            <div class="text-center py-20 bg-white rounded-[2rem] border border-dashed border-slate-200 shadow-sm">
                <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-400 text-3xl">
                    <i class="fas fa-store-slash"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800">Belum ada produk</h3>
                <p class="text-slate-500 text-sm mt-2 font-medium">Coba cari kata kunci lain atau cek kembali nanti.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-6">
                <?php foreach($produk as $p): 
                    // Format & Bersihkan nomor WA
                    $wa = preg_replace('/[^0-9]/', '', $p['no_wa']);
                    if(str_starts_with($wa, '0')) $wa = '62' . substr($wa, 1);
                    
                    $msg = urlencode("Halo {$p['penjual_nama']}, saya tertarik dengan *{$p['nama_produk']}* (Rp " . number_format($p['harga'],0,',','.') . ") yang ada di Portal Warga. Apakah masih tersedia?");
                    
                    // Decode Foto JSON
                    $photos = [];
                    try { $photos = json_decode($p['foto'], true) ?: []; } catch(Exception $e) { if($p['foto']) $photos = [$p['foto']]; }
                    $foto = !empty($photos) ? $photos[0] : 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=400&q=80';
                ?>
                <!-- Product Card Premium -->
                <div class="product-card group">
                    <div class="img-container relative h-full">
                        <div class="swiper productSwiper">
                            <div class="swiper-wrapper">
                                <?php if(empty($photos)): ?>
                                    <div class="swiper-slide"><img src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=400&q=80"></div>
                                <?php else: ?>
                                    <?php foreach($photos as $ft): ?>
                                        <div class="swiper-slide"><img src="<?= htmlspecialchars($ft) ?>"></div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <?php if(count($photos) > 1): ?>
                                <div class="swiper-pagination"></div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Badge Status -->
                        <?php if($p['status'] == 'Habis'): ?>
                            <div class="absolute top-4 left-4 z-[10] bg-red-500/80 backdrop-blur-md text-white text-[9px] font-black uppercase tracking-widest px-3 py-1.5 rounded-lg shadow-md">Stok Habis</div>
                        <?php endif; ?>

                        <div class="absolute top-4 right-4 z-[10]">
                             <a href="toko.php?penjual=<?= urlencode($p['penjual_nama']) ?>" class="glass-badge hover:bg-emerald-600 hover:text-white transition-all">
                                Lihat Toko
                             </a>
                        </div>

                        <!-- Overlay Teks dalam Foto Utama -->
                        <div class="img-overlay">
                            <h2><?= htmlspecialchars($p['nama_produk']) ?></h2>
                            <span class="price">Rp <?= number_format($p['harga'],0,',','.') ?></span>
                        </div>
                    </div>

                    <!-- Bagian Detail yang Menggeser dari Bawah Saat Hover -->
                    <section>
                        <p class="line-clamp-3"><?= htmlspecialchars($p['deskripsi'] ?: 'Tidak ada deskripsi produk.') ?></p>
                        
                        <div class="card-actions">
                            <div class="flex flex-col gap-1 overflow-hidden">
                                <div><span class="tag"><i class="fas fa-tags text-[9px]"></i> <?= htmlspecialchars($p['kategori']) ?></span></div>
                                <span class="text-[0.65rem] font-bold text-slate-500 truncate"><i class="fas fa-store text-emerald-500 mr-1"></i> <?= htmlspecialchars($p['penjual_nama']) ?></span>
                            </div>
                            
                            <a href="https://wa.me/<?= $wa ?>?text=<?= $msg ?>" target="_blank" class="btn-buy" onclick="trackAlgoritmaBeli(<?= $p['id'] ?>); this.classList.add('added'); setTimeout(() => this.classList.remove('added'), 2000)">
                                Beli
                            </a>
                        </div>
                    </section>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination Controls -->
            <?php if ($totalPages > 1): ?>
            <div class="mt-12 flex justify-center items-center gap-2 pb-10">
                <?php if ($page > 1): ?>
                    <a href="pasar.php?page=<?= $page - 1 ?>&q=<?= urlencode($search) ?>&kategori=<?= urlencode($kategori) ?>" class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-emerald-50 hover:text-emerald-600 transition-all shadow-sm">
                        <i class="fas fa-chevron-left text-xs"></i>
                    </a>
                <?php endif; ?>

                <?php 
                $startRange = max(1, $page - 2);
                $endRange = min($totalPages, $page + 2);
                if ($startRange > 1) echo '<span class="text-slate-400 px-2 font-bold">...</span>';
                for ($i = $startRange; $i <= $endRange; $i++): ?>
                    <a href="pasar.php?page=<?= $i ?>&q=<?= urlencode($search) ?>&kategori=<?= urlencode($kategori) ?>" class="w-10 h-10 rounded-xl flex items-center justify-center text-xs font-black transition-all <?= $i == $page ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-200' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; 
                if ($endRange < $totalPages) echo '<span class="text-slate-400 px-2 font-bold">...</span>';
                ?>

                <?php if ($page < $totalPages): ?>
                    <a href="pasar.php?page=<?= $page + 1 ?>&q=<?= urlencode($search) ?>&kategori=<?= urlencode($kategori) ?>" class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-emerald-50 hover:text-emerald-600 transition-all shadow-sm">
                        <i class="fas fa-chevron-right text-xs"></i>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script>
        // Inisialisasi Slider Promo
        const swiper = new Swiper('.promoSwiper', {
            loop: true,
            autoplay: { delay: 4500, disableOnInteraction: false },
            pagination: { el: '.swiper-pagination', clickable: true },
        });

        // Inisialisasi Multi Carousel Produk
        const productSwiper = new Swiper('.productSwiper', {
            loop: true,
            pagination: {
                el: '.swiper-pagination',
                type: 'fraction',
            },
        });

        // Stacking Cards Scroll Listener for Pasar
        const passedCards = document.querySelectorAll('.js-passed-card');
        window.addEventListener('scroll', () => {
            const viewportHeight = window.innerHeight;
            const isMobile = window.innerWidth <= 768;
            const stickyTopOffset = isMobile ? viewportHeight * 0.18 : viewportHeight * 0.15;

            passedCards.forEach((card, index) => {
                const nextCard = passedCards[index + 1];
                if (nextCard) {
                    const nextRect = nextCard.getBoundingClientRect();
                    const distance = nextRect.top - stickyTopOffset;

                    if (distance < viewportHeight && distance > 0) {
                        const maxShrink = isMobile ? 0.95 : 0.90;
                        const factor = (1 - maxShrink) / viewportHeight;
                        const scale = 1 - ((viewportHeight - distance) * factor);
                        const finalScale = Math.max(maxShrink, Math.min(1, scale));
                        const brightness = Math.max(0.6, Math.min(1, scale));
                        card.style.transform = `scale(${finalScale})`;
                        card.style.filter = `brightness(${brightness})`;
                    } else if (distance <= 0) {
                        const maxShrink = isMobile ? 0.95 : 0.90;
                        card.style.transform = `scale(${maxShrink})`;
                        card.style.filter = `brightness(0.6)`;
                    } else {
                        card.style.transform = `scale(1)`;
                        card.style.filter = `brightness(1)`;
                    }
                }
            });
        });

        // Fungsi Algoritma Perekam Klik (Hit API Tanpa Reload)
        function trackAlgoritmaBeli(id) {
            const fd = new FormData(); fd.append('id', id);
            fetch('views/pages/track_beli.php', { method: 'POST', body: fd }).catch(e => console.log('Tracking failed', e));
        }
    </script>
</body>
</html>