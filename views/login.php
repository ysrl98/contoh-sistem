<?php $flash = getFlash(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — SIM-FLPP Database & Pemberkasan</title>
    <link rel="stylesheet" href="assets/css/style.css?v=1.0">
</head>
<body>
<div class="login-page">
    <div class="login-card">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:22px;">
            <div class="brand-logo" style="width:46px;height:46px;font-size:19px;">FL</div>
            <div>
                <h1 style="margin:0;font-size:19px;font-weight:800;color:#0f172a;">SIM-FLPP Kantor</h1>
                <p style="margin:2px 0 0;font-size:12.5px;color:#64748b;">Sistem Administrasi & Cetak Berkas KPR FLPP</p>
            </div>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] === 'error' ? 'error' : 'success' ?>" style="margin-bottom:16px;">
                <span><?= e($flash['message']) ?></span>
            </div>
        <?php endif; ?>

        <form method="post" action="index.php?page=login">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

            <div class="form-group" style="margin-bottom:14px;">
                <label class="form-label" for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="Masukkan username" required autofocus value="<?= e($_POST['username'] ?? 'admin') ?>">
            </div>

            <div class="form-group" style="margin-bottom:20px;">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;padding:11px 16px;font-size:14px;">
                Masuk ke Aplikasi
            </button>
        </form>

        <div style="margin-top:22px;padding:13px 14px;border-radius:10px;background:#f8fafc;border:1px solid #e2e8f0;font-size:12px;color:#475569;">
            <div style="font-weight:700;color:#0f172a;margin-bottom:5px;">Akun Bawaan (Bisa diubah di menu Akun):</div>
            <div style="display:flex;justify-content:space-between;margin-bottom:3px;">
                <span>• <strong>Admin (Input & Edit):</strong></span>
                <code>admin</code> / <code>admin123</code>
            </div>
            <div style="display:flex;justify-content:space-between;">
                <span>• <strong>Monitoring (Read-Only):</strong></span>
                <code>atasan</code> / <code>atasan123</code>
            </div>
        </div>
    </div>
</div>
</body>
</html>

