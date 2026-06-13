<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin('../user/login.php');

$db      = getDB();
$userId  = intval($_SESSION['user_id']);
$msg     = '';
$error   = '';

/* ── Handle POST ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* Update profile */
    if ($action === 'update_profile') {
        $fullName = sanitize($_POST['full_name'] ?? '');
        $bio      = sanitize($_POST['bio']       ?? '');
        $phone    = sanitize($_POST['phone']     ?? '');
        $location = sanitize($_POST['location']  ?? '');

        // Upload photo
        $fotoCol = '';
        if (!empty($_FILES['foto_profile']['name'])) {
            $up = uploadFile($_FILES['foto_profile'], 'profile');
            if (isset($up['error'])) {
                $error = $up['error'];
            } else {
                // Delete old photo
                $old = $db->query("SELECT foto_profile FROM profile WHERE user_id=$userId")->fetch_assoc();
                if ($old && $old['foto_profile']) {
                    $oldPath = UPLOAD_DIR . $old['foto_profile'];
                    if (file_exists($oldPath)) unlink($oldPath);
                }
                $fotoCol = ", foto_profile = '{$up['filename']}'";
            }
        }

        if (!$error) {
            $stmt = $db->prepare(
                "UPDATE profile SET full_name=?, bio=?, phone=?, location=? $fotoCol WHERE user_id=?"
            );
            $stmt->bind_param("ssssi", $fullName, $bio, $phone, $location, $userId);
            $stmt->execute();
            $msg = 'Profil berhasil diperbarui!';
        }
    }

    /* Change password */
    if ($action === 'change_password') {
        $old  = $_POST['old_password']  ?? '';
        $new  = $_POST['new_password']  ?? '';
        $conf = $_POST['confirm_password'] ?? '';

    $user = $db->query("SELECT password FROM users WHERE id=$userId")->fetch_assoc();
    if (!password_verify($old, $user['password'])) {
        $error = 'Password lama tidak benar.';
    } elseif (strlen($new) < 6) {
        $error = 'Password baru minimal 6 karakter.';
    } elseif ($new !== $conf) {
        $error = 'Konfirmasi password tidak cocok.';
        } else {
            $hash = password_hash($new, PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE users SET password=? WHERE id=?");
             $stmt->bind_param("si", $hash, $userId);
             
             if ($stmt->execute()) {
                $msg = 'Password berhasil diubah!';
                
                } else {
                    $error = 'Gagal mengubah password: ' . $stmt->error;
                    
                    }
                }
            }

    header('Location: profile.php?msg=' . urlencode($msg) . '&err=' . urlencode($error));
    exit;
}

if (isset($_GET['msg'])) $msg   = $_GET['msg'];
if (isset($_GET['err'])) $error = $_GET['err'];

/* ── Load data ── */
$currentUser = getCurrentUser();

$myComments = $db->query("
    SELECT k.*, tw.nama as wisata_nama, tw.id as wisata_id,
           COUNT(kf.id) as foto_count
    FROM komentar k
    JOIN tempat_wisata tw ON tw.id = k.wisata_id
    LEFT JOIN komentar_foto kf ON kf.komentar_id = k.id
    WHERE k.user_id = $userId
    GROUP BY k.id
    ORDER BY k.created_at DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

$myRatings = $db->query("
    SELECT r.*, tw.nama as wisata_nama, tw.id as wisata_id
    FROM rating r
    JOIN tempat_wisata tw ON tw.id = r.wisata_id
    WHERE r.user_id = $userId
    ORDER BY r.created_at DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Profil Saya - Lombok Tourism';
include __DIR__ . '/../includes/header.php';
?>

<style>
.tab-btn{padding:10px 22px;border:none;background:transparent;font-size:.9rem;font-weight:600;color:var(--gray-500);cursor:pointer;border-bottom:3px solid transparent;transition:var(--transition)}
.tab-btn.active{color:var(--blue-600);border-color:var(--blue-500)}
.tab-content{display:none}.tab-content.active{display:block}
</style>

<div style="min-height:100vh;background:var(--gray-50);padding-top:72px">
<div style="max-width:960px;margin:0 auto;padding:32px 24px">

    <?php if ($msg): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Profile Card -->
    <div class="profile-card" data-reveal>
        <div class="profile-cover"></div>
        <div class="profile-body">
            <div class="profile-avatar-wrap">
                <?php if (!empty($currentUser['foto_profile'])): ?>
                    <img src="<?= BASE_URL ?>uploads/<?= htmlspecialchars($currentUser['foto_profile']) ?>"
                         class="profile-avatar" alt="Avatar" id="avatarPreview">
                <?php else: ?>
                    <div class="profile-avatar-placeholder" id="avatarPlaceholder">
                        <?= strtoupper(substr($currentUser['username'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
                <div style="padding-bottom:8px">
                    <h2 class="profile-name">
                        <?= htmlspecialchars($currentUser['full_name'] ?: $currentUser['username']) ?>
                    </h2>
                    <p class="profile-username">@<?= htmlspecialchars($currentUser['username']) ?></p>
                    <span class="badge <?= $currentUser['role']==='admin' ? 'badge-gold':'badge-blue' ?>" style="margin-top:8px">
                        <i class="fas fa-<?= $currentUser['role']==='admin'?'crown':'user' ?>"></i>
                        <?= ucfirst($currentUser['role']) ?>
                    </span>
                </div>
            </div>

            <?php if (!empty($currentUser['bio'])): ?>
                <p class="profile-bio"><?= nl2br(htmlspecialchars($currentUser['bio'])) ?></p>
            <?php endif; ?>

            <div class="profile-meta">
                <?php if (!empty($currentUser['location'])): ?>
                    <span class="profile-meta-item"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($currentUser['location']) ?></span>
                <?php endif; ?>
                <?php if (!empty($currentUser['phone'])): ?>
                    <span class="profile-meta-item"><i class="fas fa-phone"></i> <?= htmlspecialchars($currentUser['phone']) ?></span>
                <?php endif; ?>
                <span class="profile-meta-item">
                    <i class="fas fa-envelope"></i> <?= htmlspecialchars($currentUser['email']) ?>
                </span>
                <span class="profile-meta-item">
                    <i class="fas fa-calendar"></i>
                    Bergabung <?= date('M Y', strtotime($currentUser['created_at'])) ?>
                </span>
            </div>

            <!-- Stats row -->
            <div style="display:flex;gap:24px;margin-top:20px;padding-top:20px;border-top:1px solid var(--gray-100);flex-wrap:wrap">
                <div style="text-align:center">
                    <div style="font-family:var(--font-display);font-size:1.4rem;font-weight:800;color:var(--gray-900)"><?= count($myComments) ?></div>
                    <div style="font-size:.78rem;color:var(--gray-400)">Komentar</div>
                </div>
                <div style="text-align:center">
                    <div style="font-family:var(--font-display);font-size:1.4rem;font-weight:800;color:var(--gray-900)"><?= count($myRatings) ?></div>
                    <div style="font-size:.78rem;color:var(--gray-400)">Rating Diberikan</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div style="background:white;border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);border:1px solid var(--gray-100);overflow:hidden;margin-top:24px" data-reveal>
        <div style="display:flex;border-bottom:1px solid var(--gray-100);padding:0 24px;gap:4px;overflow-x:auto">
            <button class="tab-btn active" onclick="switchTab('edit')"><i class="fas fa-edit"></i> Edit Profil</button>
            <button class="tab-btn" onclick="switchTab('password')"><i class="fas fa-lock"></i> Ubah Password</button>
            <button class="tab-btn" onclick="switchTab('activity')"><i class="fas fa-history"></i> Aktivitas</button>
        </div>

        <!-- Tab: Edit Profile -->
        <div class="tab-content active" id="tab-edit" style="padding:32px">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_profile">

                <!-- Photo upload -->
                <div style="margin-bottom:28px;display:flex;align-items:center;gap:20px;flex-wrap:wrap">
                    <div style="position:relative">
                        <img id="photoPreviewImg"
                             src="<?= !empty($currentUser['foto_profile']) ? BASE_URL.'uploads/'.htmlspecialchars($currentUser['foto_profile']) : 'data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22><rect fill=%22%23e2e8f0%22 width=%22100%22 height=%22100%22/><text y=%2250%22 x%2250%22 text-anchor=%22middle%22 dominant-baseline=%22middle%22 font-size=%2240%22>👤</text></svg>' ?>"
                             style="width:90px;height:90px;border-radius:50%;object-fit:cover;border:4px solid var(--blue-100)">
                        <label for="fotoInput"
                               style="position:absolute;bottom:0;right:0;width:28px;height:28px;background:var(--blue-500);border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;border:2px solid white">
                            <i class="fas fa-camera" style="color:white;font-size:.65rem"></i>
                        </label>
                        <input type="file" id="fotoInput" name="foto_profile" accept="image/*" style="display:none"
                               onchange="previewPhoto(this)">
                    </div>
                    <div>
                        <p style="font-weight:600;color:var(--gray-800);margin-bottom:4px">Foto Profil</p>
                        <p style="font-size:.82rem;color:var(--gray-400)">JPG, PNG, WebP – maks. 5MB</p>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="full_name" class="form-control"
                               value="<?= htmlspecialchars($currentUser['full_name'] ?? '') ?>"
                               placeholder="Nama lengkap Anda">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nomor Telepon</label>
                        <input type="text" name="phone" class="form-control"
                               value="<?= htmlspecialchars($currentUser['phone'] ?? '') ?>"
                               placeholder="+62 xxx-xxxx-xxxx">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Lokasi / Kota</label>
                    <input type="text" name="location" class="form-control"
                           value="<?= htmlspecialchars($currentUser['location'] ?? '') ?>"
                           placeholder="Contoh: Mataram, NTB">
                </div>

                <div class="form-group">
                    <label class="form-label">Bio</label>
                    <textarea name="bio" class="form-control" rows="4"
                              placeholder="Ceritakan sedikit tentang diri Anda..."><?= htmlspecialchars($currentUser['bio'] ?? '') ?></textarea>
                    <p class="form-hint">Maksimal 500 karakter</p>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
            </form>
        </div>

        <!-- Tab: Change Password -->
        <div class="tab-content" id="tab-password" style="padding:32px">
            <form method="POST" style="max-width:480px">
                <input type="hidden" name="action" value="change_password">
                <div class="form-group">
                    <label class="form-label">Password Lama <span>*</span></label>
                    <div class="input-group">
                        <input type="password" name="old_password" class="form-control" required>
                        <button type="button" class="input-toggle"><i class="fas fa-eye"></i></button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Password Baru <span>*</span></label>
                    <div class="input-group">
                        <input type="password" name="new_password" class="form-control" required minlength="6">
                        <button type="button" class="input-toggle"><i class="fas fa-eye"></i></button>
                    </div>
                    <p class="form-hint">Minimal 6 karakter</p>
                </div>
                <div class="form-group">
                    <label class="form-label">Konfirmasi Password Baru <span>*</span></label>
                    <div class="input-group">
                        <input type="password" name="confirm_password" class="form-control" required>
                        <button type="button" class="input-toggle"><i class="fas fa-eye"></i></button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-key"></i> Ubah Password
                </button>
            </form>
        </div>

        <!-- Tab: Activity -->
        <div class="tab-content" id="tab-activity" style="padding:32px">
            <h4 style="font-family:var(--font-display);font-size:1.05rem;font-weight:700;margin-bottom:20px">Komentar Saya</h4>
            <?php if (empty($myComments)): ?>
                <p style="color:var(--gray-400);text-align:center;padding:30px">
                    <i class="fas fa-comment-slash" style="font-size:2rem;display:block;margin-bottom:10px"></i>
                    Belum ada komentar
                </p>
            <?php else: ?>
                <?php foreach ($myComments as $c): ?>
                <div style="padding:16px;border:1px solid var(--gray-100);border-radius:var(--radius-md);margin-bottom:12px">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;flex-wrap:wrap;gap:6px">
                        <a href="../detail.php?id=<?= $c['wisata_id'] ?>" style="font-weight:700;color:var(--blue-600);font-size:.9rem">
                            <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($c['wisata_nama']) ?>
                        </a>
                        <div style="display:flex;align-items:center;gap:8px">
                            <?php if ($c['foto_count']): ?>
                                <span class="badge badge-blue"><i class="fas fa-camera"></i> <?= $c['foto_count'] ?> foto</span>
                            <?php endif; ?>
                            <span style="font-size:.78rem;color:var(--gray-400)"><?= date('d M Y', strtotime($c['created_at'])) ?></span>
                        </div>
                    </div>
                    <p style="color:var(--gray-700);font-size:.88rem;line-height:1.6"><?= htmlspecialchars(mb_substr($c['komentar'], 0, 180)) ?><?= mb_strlen($c['komentar'])>180?'…':'' ?></p>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <h4 style="font-family:var(--font-display);font-size:1.05rem;font-weight:700;margin:28px 0 16px">Rating Saya</h4>
            <?php if (empty($myRatings)): ?>
                <p style="color:var(--gray-400);text-align:center;padding:30px">
                    <i class="fas fa-star" style="font-size:2rem;display:block;margin-bottom:10px"></i>
                    Belum ada rating
                </p>
            <?php else: ?>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px">
                    <?php foreach ($myRatings as $r): ?>
                    <div style="padding:16px;border:1px solid var(--gray-100);border-radius:var(--radius-md);background:var(--gray-50)">
                        <a href="../detail.php?id=<?= $r['wisata_id'] ?>" style="font-weight:700;color:var(--blue-600);font-size:.9rem;display:block;margin-bottom:8px">
                            <?= htmlspecialchars($r['wisata_nama']) ?>
                        </a>
                        <div style="color:var(--gold);font-size:1rem">
                            <?= str_repeat('★', $r['rating']) ?><?= str_repeat('☆', 5-$r['rating']) ?>
                        </div>
                        <div style="font-size:.78rem;color:var(--gray-400);margin-top:4px"><?= date('d M Y', strtotime($r['created_at'])) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>
</div>

<!-- Lightbox -->
<div class="lightbox" id="lightbox">
    <div class="lightbox-inner">
        <button class="lightbox-close" id="lightboxClose"><i class="fas fa-times"></i></button>
        <img src="" id="lightboxImg" alt="">
    </div>
</div>

<script>
function switchTab(name) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    event.target.classList.add('active');
}
function previewPhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('photoPreviewImg').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>