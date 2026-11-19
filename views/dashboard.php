<?php
$pageTitle = 'Dashboard & Ringkasan FLPP';
$pageSubtitle = 'Ringkasan data pengajuan KPR FLPP, perumahan, dan status pemberkasan BAST/PPJB';

$totalAll = (int)$pdo->query("SELECT COUNT(*) FROM nasabah")->fetchColumn();
$totalAdaPt = (int)$pdo->query("SELECT COUNT(*) FROM nasabah WHERE TRIM(pengembang) != '' OR TRIM(nama_perumahan) != ''")->fetchColumn();

// Count BAST states accurately
$allBastRows = $pdo->query("SELECT no_bast, tanggal_serah_terima, status_proses FROM nasabah")->fetchAll();
$bastLengkap = 0;
$bastDraft = 0;
$bastKosong = 0;
foreach ($allBastRows as $br) {
    $st = getBastState($br['no_bast']);
    if ($st === 'lengkap' || trim($br['tanggal_serah_terima']) !== '' || $br['status_proses'] === 'Sudah Akad / BAST') {
        $bastLengkap++;
    } elseif ($st === 'draft') {
        $bastDraft++;
    } else {
        $bastKosong++;
    }
}

$rekapPt = $pdo->query("
    SELECT
        CASE
            WHEN UPPER(TRIM(pengembang)) LIKE '%SINAR DINAR%' THEN 'PT. SINAR DINAR JAYA'
            WHEN UPPER(TRIM(pengembang)) LIKE '%KALIMANTAN BATARA%' THEN 'PT. KALIMANTAN BATARA'
            WHEN UPPER(TRIM(pengembang)) LIKE '%SUMBER BERKAH%' THEN 'PT. SUMBER BERKAH NUR'
            WHEN TRIM(pengembang) = '' THEN '(Belum Ditentukan PT)'
            ELSE TRIM(pengembang)
        END AS pt_label,
        COUNT(*) AS jumlah
    FROM nasabah
    GROUP BY pt_label
    ORDER BY jumlah DESC
")->fetchAll();

$rekapPerumahan = $pdo->query("
    SELECT
        TRIM(nama_perumahan) AS perum,
        COUNT(*) AS jumlah
    FROM nasabah
    WHERE TRIM(nama_perumahan) != ''
    GROUP BY UPPER(TRIM(nama_perumahan))
    ORDER BY jumlah DESC
    LIMIT 8
")->fetchAll();

$recentNasabah = $pdo->query("
    SELECT id, no_urut, nama_lengkap, no_ktp, no_hp, pengembang, nama_perumahan, no_rumah, no_bast, status_proses
    FROM nasabah
    ORDER BY id DESC
    LIMIT 8
")->fetchAll();

require __DIR__ . '/layout_header.php';
?>

<!-- Quick Search Bar on Dashboard -->
<div class="card" style="background:linear-gradient(135deg,#0f172a 0%,#1e3a8a 100%);color:#fff;border:none;padding:22px 24px;">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div style="max-width:560px;">
            <h2 style="margin:0 0 6px;font-size:18px;font-weight:800;">Cari Cepat Nasabah / Blok Rumah / Nomor KTP</h2>
            <p style="margin:0;font-size:13px;color:#cbd5e1;">Langsung temukan data pemohon untuk cetak berkas, cek kelengkapan, atau isi nomor BAST & PPJB kapan saja.</p>
        </div>
        <form method="get" action="index.php" style="display:flex;gap:8px;flex:1;min-width:260px;max-width:460px;">
            <input type="hidden" name="page" value="nasabah">
            <input type="text" name="q" class="form-control" placeholder="Ketik Nama, NIK KTP, Perumahan, atau No. Rumah..." style="border:none;padding:11px 14px;">
            <button type="submit" class="btn btn-primary" style="background:#3b82f6;padding:11px 18px;">Cari</button>
        </form>
    </div>
</div>

<!-- KPI Stat Cards -->
<div class="stats-grid">
    <a href="index.php?page=nasabah" class="stat-card">
        <div>
            <div class="stat-label">Total Nasabah Database</div>
            <div class="stat-value"><?= number_format($totalAll) ?></div>
            <div class="stat-hint">Seluruh data pengajuan FLPP</div>
        </div>
        <div class="stat-icon blue">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
    </a>

    <a href="index.php?page=nasabah&filter_unit=ada" class="stat-card">
        <div>
            <div class="stat-label">Sudah Terhubung PT / Komplek</div>
            <div class="stat-value"><?= number_format($totalAdaPt) ?></div>
            <div class="stat-hint">Sudah ada data PT / Perumahan</div>
        </div>
        <div class="stat-icon indigo">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/></svg>
        </div>
    </a>

    <a href="index.php?page=nasabah&bast=ada" class="stat-card">
        <div>
            <div class="stat-label">BAST Lengkap / Draft</div>
            <div class="stat-value"><?= number_format($bastLengkap + $bastDraft) ?></div>
            <div class="stat-hint"><?= $bastLengkap ?> selesai • <?= $bastDraft ?> draft nomor</div>
        </div>
        <div class="stat-icon emerald">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
    </a>

    <a href="index.php?page=nasabah&bast=kosong" class="stat-card">
        <div>
            <div class="stat-label">Menunggu Nomor BAST</div>
            <div class="stat-value"><?= number_format($bastKosong) ?></div>
            <div class="stat-hint">Opsional — bisa diisi kapan saja</div>
        </div>
        <div class="stat-icon amber">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
    </a>
</div>

<!-- Breakdown Grid: PT & Perumahan -->
<div class="form-grid" style="margin-bottom:20px;">
    <div class="card" style="margin-bottom:0;">
        <div class="card-header">
            <h3 class="card-title">Distribusi per Pengembang (PT)</h3>
            <a href="index.php?page=master" class="btn btn-outline btn-xs">Kelola Master PT</a>
        </div>
        <div class="card-body" style="padding:14px 20px;">
            <?php foreach ($rekapPt as $rp):
                $pct = $totalAll > 0 ? round(($rp['jumlah'] / $totalAll) * 100) : 0;
                $qPt = $rp['pt_label'] === '(Belum Ditentukan PT)' ? '' : $rp['pt_label'];
            ?>
            <div style="margin-bottom:13px;">
                <div style="display:flex;justify-content:space-between;font-size:13px;font-weight:600;margin-bottom:4px;">
                    <a href="index.php?page=nasabah&pt=<?= urlencode($qPt) ?>" style="color:#1e293b;">
                        <?= e($rp['pt_label']) ?>
                    </a>
                    <span style="color:#475569;"><?= (int)$rp['jumlah'] ?> nasabah (<?= $pct ?>%)</span>
                </div>
                <div style="height:8px;background:#f1f5f9;border-radius:99px;overflow:hidden;">
                    <div style="height:100%;width:<?= max(3, $pct) ?>%;background:<?= $qPt === '' ? '#94a3b8' : '#2563eb' ?>;border-radius:99px;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card" style="margin-bottom:0;">
        <div class="card-header">
            <h3 class="card-title">Perumahan / Komplek Terbanyak</h3>
            <a href="index.php?page=nasabah" class="btn btn-outline btn-xs">Lihat Semua</a>
        </div>
        <div class="card-body" style="padding:12px 20px;">
            <?php if (empty($rekapPerumahan)): ?>
                <p style="color:#94a3b8;font-size:13px;">Belum ada data perumahan.</p>
            <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <?php foreach ($rekapPerumahan as $pm): ?>
                    <a href="index.php?page=nasabah&perum=<?= urlencode($pm['perum']) ?>" style="display:flex;align-items:center;justify-content:space-between;padding:8px 11px;border-radius:8px;background:#f8fafc;border:1px solid #e2e8f0;">
                        <span style="font-weight:600;color:#1e293b;font-size:13px;"><?= e($pm['perum']) ?></span>
                        <span class="badge badge-blue"><?= (int)$pm['jumlah'] ?> unit</span>
                    </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Latest / Recently Added Nasabah -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Data Nasabah Terakhir di Database</h3>
        <div style="display:flex;gap:8px;">
            <?php if (isAdmin()): ?>
                <a href="index.php?page=nasabah_form" class="btn btn-primary btn-xs">+ Input Baru</a>
            <?php endif; ?>
            <a href="index.php?page=nasabah" class="btn btn-outline btn-xs">Buka Semua Data (<?= $totalAll ?>) →</a>
        </div>
    </div>

    <div class="table-wrap desktop-table-only">
        <table class="data-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Pemohon & NIK</th>
                    <th>Perumahan & Blok</th>
                    <th>Pengembang (PT)</th>
                    <th>Status / BAST</th>
                    <th style="text-align:right;">Aksi Cepat</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentNasabah as $r): ?>
                <tr>
                    <td style="font-weight:700;color:#64748b;"><?= e($r['no_urut'] ?: $r['id']) ?></td>
                    <td>
                        <a href="index.php?page=nasabah_detail&id=<?= (int)$r['id'] ?>" style="font-weight:700;color:#0f172a;display:block;">
                            <?= e($r['nama_lengkap']) ?>
                        </a>
                        <span style="font-size:12px;color:#64748b;">NIK: <?= e($r['no_ktp'] ?: '-') ?></span>
                    </td>
                    <td>
                        <?php if ($r['nama_perumahan']): ?>
                            <div style="font-weight:600;"><?= e($r['nama_perumahan']) ?></div>
                            <div style="font-size:12px;color:#64748b;">Rumah: <?= e($r['no_rumah'] ?: 'Belum diisi') ?></div>
                        <?php else: ?>
                            <span style="color:#94a3b8;font-style:italic;font-size:12.5px;">Belum dipilih</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $r['pengembang'] ? e($r['pengembang']) : '<span style="color:#94a3b8;">-</span>' ?></td>
                    <td>
                        <div><?= renderStatusBadge($r['status_proses']) ?></div>
                        <div style="margin-top:4px;"><?= renderBastBadge($r['no_bast']) ?></div>
                    </td>
                    <td style="text-align:right;white-space:nowrap;">
                        <a href="index.php?page=surat&id=<?= (int)$r['id'] ?>" class="btn btn-soft-emerald btn-xs" title="Buat masing-masing dari 18 surat KPR BSN">📑 Buat Surat</a>
                        <a href="ideb_docx.php?id=<?= (int)$r['id'] ?>" class="btn btn-soft-blue btn-xs" title="Download Word Permohonan SLIK OJK (.docx)">📥 SLIK</a>
                        <a href="index.php?page=nasabah_detail&id=<?= (int)$r['id'] ?>" class="btn btn-outline btn-xs">Detail</a>
                        <?php if (isAdmin()): ?>
                            <a href="index.php?page=nasabah_form&id=<?= (int)$r['id'] ?>" class="btn btn-outline btn-xs">Edit</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="mobile-cards">
        <?php foreach ($recentNasabah as $r): ?>
        <div class="m-card">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px;">
                <div>
                    <a href="index.php?page=nasabah_detail&id=<?= (int)$r['id'] ?>" style="font-weight:700;font-size:14.5px;color:#0f172a;"><?= e($r['nama_lengkap']) ?></a>
                    <div style="font-size:12px;color:#64748b;">NIK: <?= e($r['no_ktp'] ?: '-') ?></div>
                </div>
                <?= renderStatusBadge($r['status_proses']) ?>
            </div>
            <div style="font-size:12.5px;color:#334155;margin-bottom:8px;">
                <?= e($r['nama_perumahan'] ?: 'Perumahan belum dipilih') ?>
                <?= $r['no_rumah'] ? ' • Blok/No: ' . e($r['no_rumah']) : '' ?>
            </div>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                <a href="ideb_docx.php?id=<?= (int)$r['id'] ?>" class="btn btn-soft-emerald btn-xs">📥 SLIK (.docx)</a>
                <a href="print.php?id=<?= (int)$r['id'] ?>&tpl=ideb" target="_blank" class="btn btn-soft-blue btn-xs">🖨️ Cetak</a>
                <a href="index.php?page=nasabah_detail&id=<?= (int)$r['id'] ?>" class="btn btn-outline btn-xs">Detail</a>
                <?php if (isAdmin()): ?>
                    <a href="index.php?page=nasabah_form&id=<?= (int)$r['id'] ?>" class="btn btn-outline btn-xs">Edit</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>

