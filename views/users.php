<?php
$pageTitle = 'Manajemen Akun & Hak Akses';
$pageSubtitle = 'Atur password akun Anda serta kelola akun Administrator (Input/Edit) dan Viewer (Lihat Saja)';

$usersList = $pdo->query("SELECT id, username, full_name, role, created_at FROM users ORDER BY id ASC")->fetchAll();
$me = currentUser();

require __DIR__ . '/layout_header.php';
?>

<div class="form-grid">
    <!-- Change Own Password -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">🔐 Ganti Password Saya (<?= e($me['username'] ?? '') ?>)</h3>
        </div>
        <div class="card-body">
            <form method="post" action="index.php?page=change_password">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <div class="form-group" style="margin-bottom:14px;">
                    <label class="form-label">Nama Tampilan</label>
                    <input type="text" name="full_name" class="form-control" required value="<?= e($me['full_name'] ?? '') ?>">
                </div>
                <div class="form-group" style="margin-bottom:14px;">
                    <label class="form-label">Password Baru <span class="opt-tag">Minimal 5 karakter</span></label>
                    <input type="password" name="new_password" class="form-control" placeholder="Kosongkan jika hanya mengubah nama tampilan">
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Simpan Profil & Password</button>
            </form>
        </div>
    </div>

    <!-- Add New User (Admin Only) -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">👤 Tambah Akun Pengguna Baru</h3>
        </div>
        <div class="card-body">
            <?php if (!isAdmin()): ?>
                <p style="color:#64748b;font-size:13px;">Hanya akun Administrator yang dapat menambahkan pengguna baru.</p>
            <?php else: ?>
                <form method="post" action="index.php?page=add_user">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <div class="form-grid" style="margin-bottom:14px;">
                        <div class="form-group">
                            <label class="form-label">Username Login <span class="req-star">*</span></label>
                            <input type="text" name="username" class="form-control" required placeholder="Contoh: staf_flpp">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Password Awal <span class="req-star">*</span></label>
                            <input type="password" name="password" class="form-control" required placeholder="Minimal 5 karakter">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nama Lengkap <span class="req-star">*</span></label>
                            <input type="text" name="full_name" class="form-control" required placeholder="Nama pengguna">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Hak Akses (Role)</label>
                            <select name="role" class="form-control">
                                <option value="admin">Administrator (Bisa Input, Edit & Hapus)</option>
                                <option value="viewer">Viewer / Read-Only (Hanya Lihat & Cetak)</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-emerald btn-sm">+ Tambah Pengguna</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Users List -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar Akun Terdaftar</h3>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Nama Lengkap</th>
                    <th>Hak Akses</th>
                    <th>Dibuat</th>
                    <?php if (isAdmin()): ?><th style="text-align:right;">Aksi</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usersList as $u): ?>
                <tr>
                    <td style="font-family:monospace;font-weight:700;"><?= e($u['username']) ?></td>
                    <td><?= e($u['full_name']) ?></td>
                    <td>
                        <?php if ($u['role'] === 'admin'): ?>
                            <span class="badge badge-blue">Administrator (Full Akses)</span>
                        <?php else: ?>
                            <span class="badge badge-slate">Viewer (Read-Only)</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px;color:#64748b;"><?= e($u['created_at']) ?></td>
                    <?php if (isAdmin()): ?>
                    <td style="text-align:right;">
                        <?php if ((int)$u['id'] !== (int)($me['id'] ?? 0)): ?>
                            <form method="post" action="index.php?page=delete_user" style="display:inline;" onsubmit="return confirm('Hapus akun <?= e(addslashes($u['username'])) ?>?');">
                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-xs">Hapus</button>
                            </form>
                        <?php else: ?>
                            <span style="font-size:11.5px;color:#94a3b8;">Akun Aktif</span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>

