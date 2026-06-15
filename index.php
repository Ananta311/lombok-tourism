<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Lombok Tourism - Jelajahi Keindahan Lombok';
$db = getDB();

// Fetch featured wisata
$featured = $db->query("
    SELECT tw.*, wf.foto as foto_utama,
           COALESCE(AVG(r.rating), tw.rating_awal) as avg_rating,
           COUNT(DISTINCT r.id) as total_rating
    FROM tempat_wisata tw
    LEFT JOIN wisata_foto wf ON wf.wisata_id = tw.id AND wf.is_primary = 1
    LEFT JOIN rating r ON r.wisata_id = tw.id
    WHERE tw.is_featured = 1
    GROUP BY tw.id
    ORDER BY avg_rating DESC
    LIMIT 6
")->fetch_all(MYSQLI_ASSOC);

// Categories with count
$categories = $db->query("
    SELECT kategori, COUNT(*) as jumlah FROM tempat_wisata GROUP BY kategori ORDER BY jumlah DESC
")->fetch_all(MYSQLI_ASSOC);

$heroBg = getSetting('hero_bg');
$heroTitle = getSetting('hero_title') ?: 'Jelajahi Keindahan Lombok';
$heroSubtitle = getSetting('hero_subtitle') ?: 'Temukan destinasi wisata terbaik di Pulau Seribu Masjid';
$totalWisata = $db->query("SELECT COUNT(*) FROM tempat_wisata")->fetch_row()[0];
$totalUser = $db->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetch_row()[0];
$totalReview = $db->query("SELECT COUNT(*) FROM komentar")->fetch_row()[0];

include __DIR__ . '/includes/header.php';
?>

<!-- HERO -->
<section class="hero">
    <div class="hero-bg" id="heroBg" <?= $heroBg ? "style=\"background-image:url('uploads/" . htmlspecialchars($heroBg) . "')\"" : '' ?>></div>
    <div class="hero-particles" id="heroParticles"></div>
    <div class="hero-content">
        <div class="hero-badge">
            <i class="fas fa-star"></i>
            Destinasi Wisata Terbaik Indonesia
        </div>
        <h1 class="hero-title">
            <?= htmlspecialchars($heroTitle) ?>
            <span class="accent"><br>yang Menakjubkan</span>
        </h1>
        <p class="hero-subtitle"><?= htmlspecialchars($heroSubtitle) ?></p>
        <div class="hero-actions">
            <a href="wisata.php" class="btn btn-primary btn-lg">
                <i class="fas fa-compass"></i> Jelajahi Wisata
            </a>
            <?php if (!isLoggedIn()): ?>
                <a href="user/register.php" class="btn btn-secondary btn-lg">
                    <i class="fas fa-user-plus"></i> Bergabung
                </a>
            <?php endif; ?>
        </div>
        <form class="hero-search" action="wisata.php" method="GET">
            <i class="fas fa-search" style="color:var(--gray-400);flex-shrink:0"></i>
            <input type="text" name="q" placeholder="Cari pantai, gunung, budaya..." id="searchWisata">
            <button type="submit">Cari</button>
        </form>
        <div class="hero-stats">
            <div class="stat-item">
                <div class="stat-num" data-count="<?= $totalWisata ?>" data-suffix="+">0</div>
                <div class="stat-label">Destinasi Wisata</div>
            </div>
            <div class="stat-item">
                <div class="stat-num" data-count="<?= $totalUser ?>" data-suffix="+">0</div>
                <div class="stat-label">Wisatawan Terdaftar</div>
            </div>
            <div class="stat-item">
                <div class="stat-num" data-count="<?= $totalReview ?>" data-suffix="+">0</div>
                <div class="stat-label">Ulasan Ditulis</div>
            </div>
            <div class="stat-item">
                <div class="stat-num">4.8</div>
                <div class="stat-label">Rating Rata-rata</div>
            </div>
        </div>
    </div>
</section>

<!-- CATEGORIES -->
<section class="section section-sm" style="background:white;">
    <div class="container">
        <div class="section-header">
            <div class="section-eyebrow">Kategori Wisata</div>
            <h2 class="section-title">Temukan Berdasarkan Kategori</h2>
        </div>
        <div class="categories-grid">
            <?php
            $catIcons = [
                'Pantai' => ['icon' => 'fa-umbrella-beach', 'color' => '#0ea5e9', 'bg' => '#e0f2fe'],
                'Gunung' => ['icon' => 'fa-mountain', 'color' => '#10b981', 'bg' => '#d1fae5'],
                'Pulau'  => ['icon' => 'fa-water', 'color' => '#3b82f6', 'bg' => '#dbeafe'],
                'Budaya' => ['icon' => 'fa-landmark', 'color' => '#f59e0b', 'bg' => '#fef3c7'],
                'Alam'   => ['icon' => 'fa-leaf', 'color' => '#22c55e', 'bg' => '#dcfce7'],
                'Kuliner'=> ['icon' => 'fa-utensils', 'color' => '#ef4444', 'bg' => '#fee2e2'],
            ];
            foreach ($categories as $cat):
                $ci = $catIcons[$cat['kategori']] ?? ['icon' => 'fa-map-pin', 'color' => '#6366f1', 'bg' => '#e0e7ff'];
            ?>
            <a href="wisata.php?kategori=<?= urlencode($cat['kategori']) ?>" class="category-card" data-reveal>
                <div class="category-icon" style="background:<?= $ci['bg'] ?>;color:<?= $ci['color'] ?>">
                    <i class="fas <?= $ci['icon'] ?>"></i>
                </div>
                <div class="category-name"><?= htmlspecialchars($cat['kategori']) ?></div>
                <div class="text-muted" style="font-size:0.78rem;margin-top:4px"><?= $cat['jumlah'] ?> tempat</div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- FEATURED WISATA -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <div class="section-eyebrow">Pilihan Terbaik</div>
            <h2 class="section-title">Destinasi Unggulan Lombok</h2>
            <p class="section-desc">Destinasi wisata terpopuler yang wajib Anda kunjungi di Lombok</p>
        </div>
        <div class="cards-grid">
            <?php foreach ($featured as $i => $w): 
                $avgR = round($w['avg_rating'], 1);
                $stars = str_repeat('★', round($avgR)) . str_repeat('☆', 5 - round($avgR));
                $price = $w['harga_tiket'] > 0 ? 'Rp ' . number_format($w['harga_tiket'], 0, ',', '.') : 'Gratis';
            ?>
            <div class="wisata-card" data-reveal data-delay="<?= $i ?>">
                <div class="card-img-wrap">
                    <?php if ($w['foto_utama']): ?>
                        <img src="uploads/<?= htmlspecialchars($w['foto_utama']) ?>" alt="<?= htmlspecialchars($w['nama']) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="card-img-placeholder"><i class="fas fa-image"></i></div>
                    <?php endif; ?>
                    <div class="card-badge"><?= htmlspecialchars($w['kategori']) ?></div>
                    <div class="card-badge-featured">⭐ Unggulan</div>
                </div>
                <div class="card-body">
                    <h3 class="card-title"><?= htmlspecialchars($w['nama']) ?></h3>
                    <p class="card-desc"><?= htmlspecialchars($w['deskripsi']) ?></p>
                    <div class="card-meta">
                        <div class="card-rating">
                            <span class="stars"><?= $stars ?></span>
                            <span><?= $avgR ?></span>
                            <span class="rating-count">(<?= $w['total_rating'] ?>)</span>
                        </div>
                        <div class="card-price"><?= $price ?></div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="detail.php?id=<?= $w['id'] ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-eye"></i> Lihat Detail
                    </a>
                    <?php if ($w['link_lokasi']): ?>
                        <a href="<?= htmlspecialchars($w['link_lokasi']) ?>" target="_blank" class="btn btn-outline btn-sm">
                            <i class="fas fa-map-marker-alt"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-5">
            <a href="wisata.php" class="btn btn-outline btn-lg">
                <i class="fas fa-th-large"></i> Lihat Semua Wisata
            </a>
        </div>
    </div>
</section>

<!-- WHY LOMBOK BANNER -->
<section class="section" style="background:linear-gradient(135deg, var(--blue-900), var(--green-800)); color:white; position:relative; overflow:hidden;">
    <div style="position:absolute;inset:0;background:url(\"data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Ccircle cx='30' cy='30' r='20'/%3E%3C/g%3E%3C/svg%3E\");"></div>
    <div class="container" style="position:relative;">
        <div class="section-header">
            <div class="section-eyebrow" style="color:var(--blue-300)">Mengapa Lombok?</div>
            <h2 class="section-title" style="color:white">Keindahan yang Tak Tertandingi</h2>
            <p class="section-desc" style="color:rgba(255,255,255,0.7)">Lombok menawarkan pengalaman wisata yang otentik dan tak terlupakan</p>
        </div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:28px">
            <?php
            $reasons = [
                ['icon'=>'fa-umbrella-beach','title'=>'Pantai Eksotis','desc'=>'Pasir putih dan air biru jernih yang memukau di setiap sudut pantai'],
                ['icon'=>'fa-mountain','title'=>'Gunung Rinjani','desc'=>'Pendakian menantang dengan pemandangan danau kawah yang spektakuler'],
                ['icon'=>'fa-fish','title'=>'Bawah Laut Indah','desc'=>'Snorkeling dan diving di Gili dengan keanekaragaman hayati laut luar biasa'],
                ['icon'=>'fa-mosque','title'=>'Kaya Budaya','desc'=>'Warisan budaya Sasak yang unik dan autentik di setiap desa tradisional'],
            ];
            foreach ($reasons as $r): ?>
            <div style="text-align:center; padding:32px 24px; background:rgba(255,255,255,0.06); border-radius:var(--radius-lg); border:1px solid rgba(255,255,255,0.1); transition:var(--transition);" data-reveal>
                <div style="width:64px;height:64px;border-radius:var(--radius-md);background:rgba(255,255,255,0.12);display:flex;align-items:center;justify-content:center;font-size:1.6rem;margin:0 auto 18px;color:var(--blue-300)">
                    <i class="fas <?= $r['icon'] ?>"></i>
                </div>
                <h4 style="font-family:var(--font-display);font-size:1.1rem;font-weight:700;margin-bottom:8px;"><?= $r['title'] ?></h4>
                <p style="font-size:0.88rem;color:rgba(255,255,255,0.65);line-height:1.7"><?= $r['desc'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- LIGHTBOX -->
<div class="lightbox" id="lightbox">
    <div class="lightbox-inner">
        <button class="lightbox-close" id="lightboxClose"><i class="fas fa-times"></i></button>
        <img src="" id="lightboxImg" alt="Gambar">
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>