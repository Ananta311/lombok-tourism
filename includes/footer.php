<?php
// includes/footer.php
$siteName = getSetting('site_name') ?: 'Lombok Tourism';
?>
<footer class="footer">
    <div class="footer-wave">
        <svg viewBox="0 0 1200 120" preserveAspectRatio="none">
            <path d="M985.66,92.83C906.67,72,823.78,31,743.84,14.19c-82.26-17.34-168.06-16.33-250.45.39-57.84,11.73-114,31.07-172,41.86A600.21,600.21,0,0,1,0,27.35V120H1200V95.8C1132.19,118.92,1055.71,111.31,985.66,92.83Z" fill="currentColor"></path>
        </svg>
    </div>
    <div class="footer-content">
        <div class="footer-grid">
            <div class="footer-brand">
                <div class="footer-logo">
                    <i class="fas fa-compass"></i>
                    <span><?= htmlspecialchars($siteName) ?></span>
                </div>
                <p>Jelajahi keindahan Lombok bersama kami. Temukan destinasi wisata terbaik, budaya unik, dan pengalaman tak terlupakan di Pulau Seribu Masjid.</p>
                <div class="footer-social">
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                    <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                    <a href="#" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
                </div>
            </div>
            <div class="footer-links">
                <h4>Destinasi</h4>
                <ul>
                    <li><a href="<?= BASE_URL ?>wisata.php?kategori=Pantai">Pantai</a></li>
                    <li><a href="<?= BASE_URL ?>wisata.php?kategori=Gunung">Gunung</a></li>
                    <li><a href="<?= BASE_URL ?>wisata.php?kategori=Pulau">Pulau & Gili</a></li>
                    <li><a href="<?= BASE_URL ?>wisata.php?kategori=Budaya">Wisata Budaya</a></li>
                    <li><a href="<?= BASE_URL ?>wisata.php?kategori=Alam">Wisata Alam</a></li>
                </ul>
            </div>
            <div class="footer-links">
                <h4>Akun</h4>
                <ul>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="<?= BASE_URL ?>user/profile.php">Profil Saya</a></li>
                        <li><a href="<?= BASE_URL ?>user/logout.php">Keluar</a></li>
                    <?php else: ?>
                        <li><a href="<?= BASE_URL ?>user/login.php">Masuk</a></li>
                        <li><a href="<?= BASE_URL ?>user/register.php">Daftar</a></li>
                    <?php endif; ?>
                    <?php if (isAdmin()): ?>
                        <li><a href="<?= BASE_URL ?>admin/dashboard.php">Dashboard Admin</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="footer-contact">
                <h4>Kontak</h4>
                <p><i class="fas fa-map-marker-alt"></i> Mataram, Nusa Tenggara Barat</p>
                <p><i class="fas fa-envelope"></i> info@lomboktravel.com</p>
                <p><i class="fas fa-phone"></i> +62 812-3456-7890</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($siteName) ?>. Dibuat dengan <i class="fas fa-heart" style="color:#ef4444"></i> untuk Lombok.</p>
        </div>
    </div>
</footer>

<div class="back-to-top" id="backToTop">
    <i class="fas fa-arrow-up"></i>
</div>

<script src="<?= BASE_URL ?>assets/js/main.js"></script>
</body>
</html>