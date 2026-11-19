<?php
$pageTitle = 'Form Mandiri & Pengajuan Masuk Nasabah';
$pageSubtitle = 'Bagikan link form pendaftaran ke WhatsApp nasabah, lalu setujui pengajuan masuk dalam 1 klik';

$pendingRows = $pdo->query("SELECT * FROM pengajuan_nasabah WHERE status_pengajuan = 'pending' ORDER BY id DESC")->fetchAll();
$historyRows = $pdo->query("SELECT * FROM pengajuan_nasabah WHERE status_pengajuan != 'pending' ORDER BY id DESC LIMIT 20")->fetchAll();

// Build current public URL dynamically
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$dir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/kantor/index.php')), '/');
$publicFormUrl = $scheme . '://' . $host . $dir . '/daftar.php';

require __DIR__ . '/layout_header.php';
?>

<!-- SHAREABLE LINK CARD -->
<div class="card" style="background:linear-gradient(135deg,#eff6ff 0%,#f0fdf4 100%);border-color:#93c5fd;">
    <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div style="max-width:660px;">
            <div style="font-weight:800;font-size:15px;color:#1e3a8a;margin-bottom:4px;">
                🔗 Link Formulir Pendaftaran Mandiri Nasabah
            </div>
            <div style="font-size:13px;color:#334155;">
                Pada form ini nasabah diberi panduan jelas bahwa <strong>data utamanya cukup 4 kolom KTP (Nama, NIK, Tempat/Tgl Lahir, Alamat)</strong>, sedangkan sisanya opsional & bisa dilengkapi kapan saja.
            </div>
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;flex:1;min-width:260px;justify-content:flex-end;">
            <input type="text" id="publicLinkInput" readonly class="form-control" value="<?= e($publicFormUrl) ?>" style="max-width:320px;background:#fff;font-family:monospace;font-size:12.5px;">
            <button type="button" onclick="navigator.clipboard?.writeText(document.getElementById('publicLinkInput').value); this.textContent='✓ Tersalin!'; setTimeout(()=>this.textContent='Salin Link', 1800);" class="btn btn-outline btn-sm">Salin Link</button>
            <a href="daftar.php" target="_blank" class="btn btn-primary btn-sm">Buka Form ↗</a>
        </div>
    </div>
</div>

<!-- PENDING SUBMISSIONS -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            Kotak Masuk Pengajuan Baru
            <span class="badge <?= count($pendingRows) > 0 ? 'badge-amber' : 'badge-slate' ?>"><?= count($pendingRows) ?> menunggu verifikasi</span>
        </div>
    </div>

    <?php if (empty($pendingRows)): ?>
        <div class="card-body" style="text-align:center;padding:42px 20px;color:#64748b;">
            <div style="font-weight:700;font-size:14.5px;color:#1e293b;margin-bottom:4px;">Belum ada pengajuan baru dari link form nasabah</div>
            <div style="font-size:13px;">Saat nasabah mengisi <code>daftar.php</code>, datanya akan muncul di sini sebelum masuk ke database utama.</div>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Waktu Masuk</th>
                        <th>4 Data Utama KTP</th>
                        <th>Data Pelengkap (Opsional)</th>
                        <th>Lampiran</th>
                        <?php if (isAdmin()): ?><th style="text-align:right;">Verifikasi Admin</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingRows as $p): ?>
                    <tr>
                        <td style="font-size:12px;color:#64748b;white-space:nowrap;"><?= e($p['created_at']) ?></td>
                        <td>
                            <div style="font-weight:800;color:#0f172a;font-size:14px;"><?= e($p['nama_lengkap']) ?></div>
                            <div style="font-size:12px;font-family:monospace;color:#1d4ed8;">NIK: <?= e($p['no_ktp']) ?></div>
                            <div style="font-size:12px;color:#334155;">TTL: <?= e($p['ttl']) ?></div>
                            <div style="font-size:12px;color:#475569;max-width:290px;">Alamat: <?= e($p['alamat']) ?></div>
                        </td>
                        <td style="font-size:12.5px;">
                            <div>Perumahan: <strong><?= e($p['nama_perumahan'] ?: 'Belum dipilih') ?></strong></div>
                            <div>HP: <?= e($p['no_hp'] ?: '-') ?> • Kerja: <?= e($p['pekerjaan'] ?: '-') ?></div>
                            <?php if ($p['status_nikah'] === 'Menikah' && !empty($p['nama_pasangan'])): ?>
                                <div style="color:#059669;">Pasangan: <?= e($p['nama_pasangan']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($p['catatan_nasabah'])): ?>
                                <div style="color:#b45309;font-size:12px;">Catatan: <?= e($p['catatan_nasabah']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:12px;">
                            <?php if ($p['file_ktp']): ?><a href="<?= e($p['file_ktp']) ?>" target="_blank" class="badge badge-emerald" style="margin:2px;">KTP ↗</a><?php endif; ?>
                            <?php if ($p['pas_foto']): ?><a href="<?= e($p['pas_foto']) ?>" target="_blank" class="badge badge-emerald" style="margin:2px;">Pas Foto ↗</a><?php endif; ?>
                            <?php if ($p['foto_tempat_kerja']): ?><a href="<?= e($p['foto_tempat_kerja']) ?>" target="_blank" class="badge badge-emerald" style="margin:2px;">Foto Kerja ↗</a><?php endif; ?>
                            <?php if (!$p['file_ktp'] && !$p['pas_foto'] && !$p['foto_tempat_kerja']): ?>
                                <span style="color:#94a3b8;">Menyusul</span>
                            <?php endif; ?>
                        </td>
                        <?php if (isAdmin()): ?>
                        <td style="text-align:right;white-space:nowrap;">
                            <form method="post" action="index.php?page=approve_pengajuan" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                <button type="submit" class="btn btn-emerald btn-xs">✓ Terima ke Database</button>
                            </form>
                            <form method="post" action="index.php?page=delete_pengajuan" style="display:inline;" onsubmit="return confirm('Hapus pengajuan ini?');">
                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-xs">Tolak</button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($historyRows)): ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Riwayat Pengajuan yang Sudah Disetujui</h3>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Waktu Pengajuan</th>
                    <th>Nama Nasabah</th>
                    <th>NIK KTP</th>
                    <th>Perumahan Pilihan</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($historyRows as $h): ?>
                <tr>
                    <td style="font-size:12px;color:#64748b;"><?= e($h['created_at']) ?></td>
                    <td style="font-weight:700;"><?= e($h['nama_lengkap']) ?></td>
                    <td style="font-family:monospace;"><?= e($h['no_ktp']) ?></td>
                    <td><?= e($h['nama_perumahan'] ?: '-') ?></td>
                    <td><span class="badge badge-emerald">✓ Sudah Masuk Database</span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/layout_footer.php'; ?>

