<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin('../user/login.php');

$db    = getDB();
$msg   = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama        = sanitize($_POST['nama']        ?? '');
    $deskripsi   = trim($_POST['deskripsi']       ?? '');
    $harga       = floatval($_POST['harga_tiket'] ?? 0);
    $jam_buka    = sanitize($_POST['jam_buka']    ?? '');
    $jam_tutup   = sanitize($_POST['jam_tutup']   ?? '');
    $rating_awal = floatval($_POST['rating_awal'] ?? 0);
    $link_lokasi = sanitize($_POST['link_lokasi'] ?? '');
    $kategori    = sanitize($_POST['kategori']    ?? 'Wisata Alam');
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $adminId     = intval($_SESSION['user_id']);

    if (strlen($nama) < 3) {
        $error = 'Nama wisata minimal 3 karakter.';
    } elseif (strlen($deskripsi) < 10) {
        $error = 'Deskripsi minimal 10 karakter.';
    } else {
        // ── INSERT wisata (satu query bersih) ──────────────────────────
        // Tipe: s s d s s d s s i i
        //       nama desk harga jam_buka jam_tutup rating link kat featured adminId
        $ins = $db->prepare(
            "INSERT INTO tempat_wisata
                (nama, deskripsi, harga_tiket, jam_buka, jam_tutup,
                 rating_awal, link_lokasi, kategori, is_featured, admin_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $ins->bind_param(
            "ssdssdsiii",
            $nama, $deskripsi, $harga,
            $jam_buka, $jam_tutup, $rating_awal,
            $link_lokasi, $kategori, $is_featured, $adminId
        );

        if (!$ins->execute()) {
            $error = 'Gagal menyimpan wisata: ' . $ins->error;
        } else {
            $wisataId  = $db->insert_id;
            $isPrimary = 1; // foto pertama = foto utama

            // ── Upload foto-foto ──────────────────────────────────────
            if (!empty($_FILES['fotos']['name'][0])) {
                // Pastikan folder ada
                $uploadDir = UPLOAD_DIR . 'wisata/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }

                $captions = $_POST['captions'] ?? [];
                foreach ($_FILES['fotos']['tmp_name'] as $k => $tmp) {
                    if (empty($tmp) || $_FILES['fotos']['error'][$k] !== UPLOAD_ERR_OK) continue;

                    $file = [
                        'name'     => $_FILES['fotos']['name'][$k],
                        'type'     => $_FILES['fotos']['type'][$k],
                        'tmp_name' => $tmp,
                        'size'     => $_FILES['fotos']['size'][$k],
                        'error'    => $_FILES['fotos']['error'][$k],
                    ];
                    $up = uploadFile($file, 'wisata');
                    if (isset($up['success'])) {
                        $fn      = $up['filename'];
                        $caption = sanitize($captions[$k] ?? '');
                        $ps = $db->prepare(
                            "INSERT INTO wisata_foto (wisata_id, foto, caption, is_primary)
                             VALUES (?, ?, ?, ?)"
                        );
                        $ps->bind_param("issi", $wisataId, $fn, $caption, $isPrimary);
                        $ps->execute();
                        $isPrimary = 0; // hanya foto pertama yang jadi utama
                    }
                }
            }

            header('Location: wisata.php?msg=added');
            exit;
        }
    }
}

$pageTitle = 'Tambah Wisata - Admin';
include __DIR__ . '/admin_header.php';
$categories = ['Pantai','Gunung','Pulau','Budaya','Alam','Kuliner','Lainnya'];
?>

<div class="admin-content">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
        <div>
            <h1 class="admin-page-title">Tambah Wisata Baru</h1>
            <p class="admin-page-subtitle">Isi semua informasi destinasi wisata</p>
        </div>
        <a href="wisata.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start">

            <!-- Main form -->
            <div>
                <div class="form-card" style="margin-bottom:20px">
                    <h3 style="font-family:var(--font-display);font-size:1rem;font-weight:700;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--gray-100)">
                        <i class="fas fa-info-circle" style="color:var(--blue-400)"></i> Informasi Dasar
                    </h3>

                    <div class="form-group">
                        <label class="form-label">Nama Wisata <span>*</span></label>
                        <input type="text" name="nama" class="form-control"
                               value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>"
                               placeholder="Contoh: Pantai Kuta Lombok" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Deskripsi <span>*</span></label>
                        <textarea name="deskripsi" class="form-control" rows="6"
                                  placeholder="Tulis deskripsi lengkap tentang wisata ini..." required><?= htmlspecialchars($_POST['deskripsi'] ?? '') ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Kategori <span>*</span></label>
                            <select name="kategori" class="form-control" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat ?>"
                                            <?= ($_POST['kategori'] ?? '') === $cat ? 'selected' : '' ?>>
                                        <?= $cat ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Harga Tiket (Rp)</label>
                            <input type="number" name="harga_tiket" class="form-control" min="0" step="1000"
                                   value="<?= htmlspecialchars($_POST['harga_tiket'] ?? '0') ?>"
                                   placeholder="0 = Gratis">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Jam Buka</label>
                            <input type="time" name="jam_buka" class="form-control"
                                   value="<?= htmlspecialchars($_POST['jam_buka'] ?? '08:00') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Jam Tutup</label>
                            <input type="time" name="jam_tutup" class="form-control"
                                   value="<?= htmlspecialchars($_POST['jam_tutup'] ?? '17:00') ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Rating Awal (0–5)</label>
                            <input type="number" name="rating_awal" class="form-control"
                                   min="0" max="5" step="0.1"
                                   value="<?= htmlspecialchars($_POST['rating_awal'] ?? '0') ?>"
                                   placeholder="Opsional">
                            <p class="form-hint">Rating sebelum ada review user</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Link Google Maps</label>
                            <input type="url" name="link_lokasi" class="form-control"
                                   value="<?= htmlspecialchars($_POST['link_lokasi'] ?? '') ?>"
                                   placeholder="https://maps.google.com/...">
                        </div>
                    </div>
                </div>

                <!-- Photo upload -->
                <div class="form-card">
                    <h3 style="font-family:var(--font-display);font-size:1rem;font-weight:700;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--gray-100)">
                        <i class="fas fa-images" style="color:var(--blue-400)"></i> Foto-foto Wisata
                    </h3>
                    <p style="font-size:.85rem;color:var(--gray-500);margin-bottom:16px">
                        Upload minimal 1 foto. Foto pertama akan dijadikan foto utama. Maks 5MB per foto.
                    </p>

                    <div class="file-upload-area" id="photoDropzone">
                        <input type="file" name="fotos[]" id="photoInput" multiple accept="image/*">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p><strong>Klik atau seret foto ke sini</strong></p>
                        <p style="font-size:.8rem;color:var(--gray-400);margin-top:4px">JPG, PNG, WebP – maks 5MB per file</p>
                    </div>
                    <div class="preview-grid" id="photoPreviewGrid"></div>
                </div>
            </div>

            <!-- Sidebar options -->
            <div>
                <div class="form-card" style="margin-bottom:16px">
                    <h3 style="font-family:var(--font-display);font-size:1rem;font-weight:700;margin-bottom:16px">
                        <i class="fas fa-cog" style="color:var(--blue-400)"></i> Opsi Publikasi
                    </h3>

                    <div class="form-group">
                        <label class="form-check">
                            <input type="checkbox" name="is_featured" value="1"
                                   <?= isset($_POST['is_featured']) ? 'checked' : '' ?>>
                            <span>Jadikan Wisata Unggulan ⭐</span>
                        </label>
                        <p class="form-hint">Wisata unggulan tampil di halaman beranda</p>
                    </div>
                </div>

                <div class="form-card" style="background:var(--blue-50);border:1px solid var(--blue-100)">
                    <h4 style="font-size:.88rem;font-weight:700;color:var(--blue-800);margin-bottom:12px">
                        <i class="fas fa-lightbulb"></i> Tips Pengisian
                    </h4>
                    <ul style="font-size:.82rem;color:var(--blue-700);line-height:1.9;padding-left:16px">
                        <li>Gunakan nama yang mudah dikenali</li>
                        <li>Deskripsi yang detail menarik lebih banyak pengunjung</li>
                        <li>Upload foto berkualitas tinggi (min. 800×600px)</li>
                        <li>Isi link Maps agar user mudah menemukan lokasi</li>
                        <li>Foto pertama = foto utama yang tampil di kartu</li>
                    </ul>
                </div>

                <div style="margin-top:20px;display:flex;flex-direction:column;gap:10px">
                    <button type="submit" class="btn btn-primary btn-lg" style="justify-content:center">
                        <i class="fas fa-plus-circle"></i> Simpan Wisata
                    </button>
                    <a href="wisata.php" class="btn btn-outline" style="justify-content:center">
                        Batal
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<?php include __DIR__ . '/admin_footer.php'; ?>