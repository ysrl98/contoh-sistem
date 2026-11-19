<?php
$pageTitle = 'Database Nasabah FLPP';
$pageSubtitle = 'Cari, filter, cetak berkas, ekspor Mail Merge terpilih, atau isi nomor BAST/PPJB kapan saja';

$q          = trim((string)($_GET['q'] ?? ''));
$ptFilter   = trim((string)($_GET['pt'] ?? ''));
$perumFilter= trim((string)($_GET['perum'] ?? ''));
$bastFilter = trim((string)($_GET['bast'] ?? ''));
$statFilter = trim((string)($_GET['status'] ?? ''));
$unitFilter = trim((string)($_GET['filter_unit'] ?? ''));
$pageNum    = max(1, (int)($_GET['p'] ?? 1));
$perPage    = 25;

$where = [];
$params = [];

if ($q !== '') {
    $where[] = "(nama_lengkap LIKE ? OR no_ktp LIKE ? OR npwp LIKE ? OR no_hp LIKE ? OR nama_pasangan LIKE ? OR nama_perumahan LIKE ? OR nama_komplek LIKE ? OR no_rumah LIKE ? OR no_bast LIKE ? OR no_shgb LIKE ? OR alamat LIKE ?)";
    $like = '%' . $q . '%';
    for ($i = 0; $i < 11; $i++) $params[] = $like;
}

if ($ptFilter === '__KOSONG__') {
    $where[] = "TRIM(pengembang) = ''";
} elseif ($ptFilter !== '') {
    $where[] = "UPPER(TRIM(pengembang)) LIKE ?";
    $params[] = '%' . strtoupper($ptFilter) . '%';
}

if ($perumFilter !== '') {
    $where[] = "(UPPER(TRIM(nama_perumahan)) LIKE ? OR UPPER(TRIM(nama_komplek)) LIKE ?)";
    $params[] = '%' . strtoupper($perumFilter) . '%';
    $params[] = '%' . strtoupper($perumFilter) . '%';
}

if ($statFilter !== '') {
    $where[] = "status_proses = ?";
    $params[] = $statFilter;
}

if ($unitFilter === 'ada') {
    $where[] = "(TRIM(pengembang) != '' OR TRIM(nama_perumahan) != '')";
} elseif ($unitFilter === 'kosong') {
    $where[] = "(TRIM(pengembang) = '' AND TRIM(nama_perumahan) = '')";
}

if ($bastFilter === 'ada') {
    $where[] = "(TRIM(no_bast) != '' OR TRIM(tanggal_serah_terima) != '')";
} elseif ($bastFilter === 'kosong') {
    $where[] = "(TRIM(no_bast) = '' AND TRIM(tanggal_serah_terima) = '')";
}

$whereSql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM nasabah $whereSql");
$stmtCount->execute($params);
$totalRows = (int)$stmtCount->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
if ($pageNum > $totalPages) $pageNum = $totalPages;
$offset = ($pageNum - 1) * $perPage;

$stmtData = $pdo->prepare("
    SELECT *
    FROM nasabah
    $whereSql
    ORDER BY CAST(no_urut AS INTEGER) ASC, id ASC
    LIMIT $perPage OFFSET $offset
");
$stmtData->execute($params);
$rows = $stmtData->fetchAll();

// Distinct Perumahan options for dropdown
$perumOpts = $pdo->query("SELECT DISTINCT TRIM(nama_perumahan) AS p FROM nasabah WHERE TRIM(nama_perumahan) != '' ORDER BY p ASC")->fetchAll(PDO::FETCH_COLUMN);

function buildFilterUrl(array $overrides = []): string {
    $base = $_GET;
    $base['page'] = 'nasabah';
    foreach ($overrides as $k => $v) {
        if ($v === null || $v === '') {
            unset($base[$k]);
        } else {
            $base[$k] = $v;
        }
    }
    return 'index.php?' . http_build_query($base);
}

require __DIR__ . '/layout_header.php';
?>

<!-- Info Strip Panduan Pendaftaran -->
<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:11px 16px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;font-size:12.5px;color:#1e3a8a;">
    <div>
        <strong>ℹ️ Info Pendaftaran Nasabah:</strong> Data utama pendaftaran cukup <strong>Nama, NIK, Tempat/Tanggal Lahir, dan Alamat</strong>. Data perumahan, pasangan, BAST, dan berkas lainnya <strong>bisa dilengkapi kapan saja</strong>.
    </div>
    <?php if (isAdmin()): ?>
        <a href="index.php?page=pengajuan" style="font-weight:700;color:#1d4ed8;text-decoration:underline;white-space:nowrap;">🔗 Link Form untuk Nasabah →</a>
    <?php endif; ?>
</div>

<!-- Search & Multi-Filter Card -->
<div class="card">
    <div class="card-body" style="padding:16px 20px;">
        <form method="get" action="index.php">
            <input type="hidden" name="page" value="nasabah">
            <div class="form-grid-4" style="align-items:end;">
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Pencarian Cepat</label>
                    <input type="text" name="q" class="form-control" value="<?= e($q) ?>" placeholder="Cari Nama Pemohon, Pasangan, NIK KTP, Blok Rumah, SHGB, BAST...">
                </div>

                <div class="form-group">
                    <label class="form-label">Pengembang (PT)</label>
                    <select name="pt" class="form-control">
                        <option value="">-- Semua Pengembang --</option>
                        <option value="KALIMANTAN BATARA" <?= str_contains(strtoupper($ptFilter), 'KALIMANTAN BATARA') ? 'selected' : '' ?>>PT. Kalimantan Batara</option>
                        <option value="SINAR DINAR JAYA" <?= str_contains(strtoupper($ptFilter), 'SINAR DINAR') ? 'selected' : '' ?>>PT. Sinar Dinar Jaya</option>
                        <option value="SUMBER BERKAH NUR" <?= str_contains(strtoupper($ptFilter), 'SUMBER BERKAH') ? 'selected' : '' ?>>PT. Sumber Berkah Nur</option>
                        <option value="__KOSONG__" <?= $ptFilter === '__KOSONG__' ? 'selected' : '' ?>>(Belum Ditentukan PT)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Perumahan / Komplek</label>
                    <select name="perum" class="form-control">
                        <option value="">-- Semua Perumahan --</option>
                        <?php foreach ($perumOpts as $po): ?>
                            <option value="<?= e($po) ?>" <?= strcasecmp($perumFilter, (string)$po) === 0 ? 'selected' : '' ?>><?= e($po) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:14px;flex-wrap:wrap;">
                <div class="chip-bar">
                    <a href="<?= e(buildFilterUrl(['status' => '', 'bast' => '', 'filter_unit' => '', 'p' => 1])) ?>" class="chip <?= ($statFilter === '' && $bastFilter === '' && $unitFilter === '') ? 'active' : '' ?>">Semua Status</a>
                    <a href="<?= e(buildFilterUrl(['filter_unit' => 'kosong', 'status' => '', 'bast' => '', 'p' => 1])) ?>" class="chip <?= $unitFilter === 'kosong' ? 'active' : '' ?>">Baru Data Utama KTP (Belum Pilih Unit)</a>
                    <a href="<?= e(buildFilterUrl(['status' => 'Pemberkasan', 'bast' => '', 'filter_unit' => '', 'p' => 1])) ?>" class="chip <?= $statFilter === 'Pemberkasan' ? 'active' : '' ?>">Pemberkasan</a>
                    <a href="<?= e(buildFilterUrl(['status' => 'Proses Bank', 'bast' => '', 'filter_unit' => '', 'p' => 1])) ?>" class="chip <?= $statFilter === 'Proses Bank' ? 'active' : '' ?>">Proses Bank</a>
                    <a href="<?= e(buildFilterUrl(['status' => 'Siap Akad', 'bast' => '', 'filter_unit' => '', 'p' => 1])) ?>" class="chip <?= $statFilter === 'Siap Akad' ? 'active' : '' ?>">Siap Akad</a>
                    <a href="<?= e(buildFilterUrl(['status' => 'Sudah Akad / BAST', 'bast' => '', 'filter_unit' => '', 'p' => 1])) ?>" class="chip <?= $statFilter === 'Sudah Akad / BAST' ? 'active' : '' ?>">Sudah Akad / BAST</a>
                    <a href="<?= e(buildFilterUrl(['bast' => 'kosong', 'status' => '', 'filter_unit' => '', 'p' => 1])) ?>" class="chip <?= $bastFilter === 'kosong' ? 'active' : '' ?>">Belum Ada BAST</a>
                </div>

                <div style="display:flex;gap:8px;">
                    <?php if ($q !== '' || $ptFilter !== '' || $perumFilter !== '' || $bastFilter !== '' || $statFilter !== '' || $unitFilter !== ''): ?>
                        <a href="index.php?page=nasabah" class="btn btn-outline btn-sm">Reset Filter</a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary btn-sm">Terapkan Filter</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Bulk Action Bar (Appears when rows are checked) -->
<div id="bulkActionBar" class="card" style="display:none;align-items:center;justify-content:space-between;padding:12px 18px;background:#0f172a;color:#fff;border:none;position:sticky;top:72px;z-index:25;gap:12px;flex-wrap:wrap;">
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;font-size:13px;font-weight:600;">
        <div>
            <span id="bulkSelectedCount" style="background:#2563eb;padding:2px 8px;border-radius:99px;margin-right:6px;">0</span>
            Nasabah dipilih — Jenis Surat:
        </div>
        <select id="bulkSuratSelect" style="padding:6px 10px;border-radius:7px;border:1px solid #475569;background:#1e293b;color:#fff;font-size:12px;font-weight:600;max-width:300px;">
            <option value="ideb_slik">14. Permohonan SLIK OJK (iDEB BSN)</option>
            <option value="wajib">📚 Paket Utama Pemberkasan (16 Surat Wajib)</option>
            <option value="all">📚 Seluruh Bundel Lengkap (18 Surat BSN)</option>
            <?php foreach (BsnSuratService::getCatalog() as $bKey => $bMeta):
                if ($bKey === 'ideb_slik') continue;
            ?>
                <option value="<?= e($bKey) ?>"><?= e($bMeta['code'] . '. ' . $bMeta['short_title']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <button type="button" onclick="downloadSelectedSuratBsn('combined')" class="btn btn-emerald btn-sm" title="Download 1 file Word .docx berisi semua nasabah yang dicentang (siap Ctrl+P sekali jalan)">
            📥 Word Gabungan (.docx)
        </button>
        <button type="button" onclick="downloadSelectedSuratBsn('zip')" class="btn btn-primary btn-sm" title="Download ZIP berisi file Word .docx terpisah per nasabah">
            🗂️ ZIP Word Per Orang (.zip)
        </button>
        <button type="button" onclick="printSelectedSuratBsn()" class="btn btn-outline btn-sm" style="background:rgba(255,255,255,0.12);color:#fff;border-color:rgba(255,255,255,0.25);">
            🖨️ Preview / PDF
        </button>
        <button type="button" onclick="exportSelectedRows(false)" class="btn btn-outline btn-sm" style="background:rgba(255,255,255,0.08);color:#cbd5e1;border-color:rgba(255,255,255,0.18);">
            📊 Excel (.xlsx)
        </button>
    </div>
</div>

<!-- Results Card -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            Daftar Nasabah
            <span class="badge badge-slate"><?= number_format($totalRows) ?> data ditemukan</span>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a href="export.php?<?= e(http_build_query($_GET)) ?>" class="btn btn-outline btn-xs" title="Export hasil filter ini ke Excel Lembar1">
                Export Hasil Filter (.xlsx)
            </a>
            <?php if (isAdmin()): ?>
                <a href="index.php?page=nasabah_form" class="btn btn-primary btn-xs">+ Input Nasabah Baru</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($rows)): ?>
        <div class="card-body" style="text-align:center;padding:48px 20px;color:#64748b;">
            <div style="font-size:15px;font-weight:700;color:#1e293b;margin-bottom:4px;">Tidak ada data yang cocok</div>
            <div style="font-size:13px;margin-bottom:16px;">Coba ubah kata kunci pencarian atau reset filter di atas.</div>
            <a href="index.php?page=nasabah" class="btn btn-outline btn-sm">Tampilkan Semua Data</a>
        </div>
    <?php else: ?>
        <!-- Desktop Table -->
        <div class="table-wrap desktop-table-only">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:34px;"><input type="checkbox" id="checkAllRows" title="Pilih semua di halaman ini"></th>
                        <th style="width:48px;">No</th>
                        <th>Pemohon & Kontak</th>
                        <th>Perumahan & Unit</th>
                        <th>Pengembang & Direktur</th>
                        <th>Status & BAST</th>
                        <th style="text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r):
                        $wa = formatWaLink($r['no_hp']);
                    ?>
                    <tr>
                        <td><input type="checkbox" class="row-check" value="<?= (int)$r['id'] ?>"></td>
                        <td style="font-weight:700;color:#64748b;"><?= e($r['no_urut'] ?: $r['id']) ?></td>
                        <td>
                            <a href="index.php?page=nasabah_detail&id=<?= (int)$r['id'] ?>" style="font-weight:700;color:#0f172a;font-size:14px;display:block;">
                                <?= e($r['nama_lengkap']) ?>
                            </a>
                            <div style="font-size:12px;color:#64748b;margin-top:2px;">
                                NIK: <span style="font-family:monospace;color:#334155;"><?= e($r['no_ktp'] ?: '-') ?></span>
                            </div>
                            <?php if (!empty($r['nama_pasangan'])): ?>
                                <div style="font-size:11.5px;color:#64748b;">Pasangan: <?= e($r['nama_pasangan']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($r['no_hp'])): ?>
                                <div style="font-size:11.5px;margin-top:2px;">
                                    <?php if ($wa): ?>
                                        <a href="<?= e($wa) ?>" target="_blank" style="color:#059669;font-weight:600;">📱 <?= e($r['no_hp']) ?></a>
                                    <?php else: ?>
                                        <span style="color:#64748b;">📱 <?= e($r['no_hp']) ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($r['nama_perumahan'])): ?>
                                <div style="font-weight:600;color:#1e293b;"><?= e($r['nama_perumahan']) ?></div>
                                <div style="font-size:12px;color:#64748b;margin-top:2px;">
                                    Blok/No: <strong><?= e($r['no_rumah'] ?: 'Belum diisi') ?></strong>
                                    <?= !empty($r['luas_tanah']) ? ' • LT: ' . e($r['luas_tanah']) : '' ?>
                                </div>
                            <?php else: ?>
                                <span style="color:#64748b;font-size:12px;display:block;">✓ Data Utama KTP</span>
                                <span style="color:#94a3b8;font-style:italic;font-size:11.5px;">Unit & pelengkap menyusul</span>
                            <?php endif; ?>
                            <?php if (!empty($r['kpr'])): ?>
                                <div style="font-size:11.5px;color:#2563eb;font-weight:600;margin-top:2px;">KPR: <?= e($r['kpr']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($r['pengembang'])): ?>
                                <div style="font-weight:600;font-size:12.5px;color:#1e293b;"><?= e($r['pengembang']) ?></div>
                                <div style="font-size:11.5px;color:#64748b;">Dir: <?= e($r['nama_direktur'] ?: '-') ?></div>
                            <?php else: ?>
                                <span style="color:#94a3b8;font-size:12px;">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div><?= renderStatusBadge($r['status_proses']) ?></div>
                            <div style="margin-top:5px;"><?= renderBastBadge($r['no_bast']) ?></div>
                            <?php if (!empty($r['tanggal_serah_terima'])): ?>
                                <div style="font-size:11px;color:#059669;margin-top:2px;">Serah Terima: <?= e($r['tanggal_serah_terima']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right;white-space:nowrap;">
                            <div style="display:inline-flex;gap:5px;flex-wrap:wrap;justify-content:flex-end;">
                                <a href="index.php?page=surat&id=<?= (int)$r['id'] ?>" class="btn btn-soft-emerald btn-xs" title="Pilih & cetak masing-masing dari 18 surat resmi KPR BSN">📑 Buat Surat</a>
                                <a href="ideb_docx.php?id=<?= (int)$r['id'] ?>" class="btn btn-soft-blue btn-xs" title="Download langsung file Word PERMOHONAN iDEB (SLIK OJK)">📥 SLIK</a>
                                <a href="index.php?page=nasabah_detail&id=<?= (int)$r['id'] ?>" class="btn btn-outline btn-xs">Detail</a>
                                <?php if (isAdmin()): ?>
                                    <button type="button"
                                        class="btn btn-soft-amber btn-xs"
                                        onclick="openQuickBastModal(this)"
                                        data-id="<?= (int)$r['id'] ?>"
                                        data-nama="<?= e($r['nama_lengkap']) ?>"
                                        data-perumahan="<?= e($r['nama_perumahan']) ?>"
                                        data-status="<?= e($r['status_proses']) ?>"
                                        data-norumah="<?= e($r['no_rumah']) ?>"
                                        data-nobast="<?= e($r['no_bast']) ?>"
                                        data-ppjb="<?= e($r['ppjb']) ?>"
                                        data-tglppjb="<?= e($r['tanggal_ppjb']) ?>"
                                        data-tglserahterima="<?= e($r['tanggal_serah_terima']) ?>"
                                        data-shgb="<?= e($r['no_shgb']) ?>"
                                        data-imb="<?= e($r['no_imb']) ?>"
                                        data-catatan="<?= e($r['catatan']) ?>"
                                        title="Isi cepat Nomor BAST, PPJB, Blok Rumah, atau SHGB kapan saja">
                                        Isi BAST
                                    </button>
                                    <a href="index.php?page=nasabah_form&id=<?= (int)$r['id'] ?>" class="btn btn-outline btn-xs">Edit</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile Card List -->
        <div class="mobile-cards">
            <?php foreach ($rows as $r): ?>
            <div class="m-card">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:6px;">
                    <div>
                        <span style="font-size:11px;font-weight:700;color:#64748b;">#<?= e($r['no_urut'] ?: $r['id']) ?></span>
                        <a href="index.php?page=nasabah_detail&id=<?= (int)$r['id'] ?>" style="font-weight:700;font-size:15px;color:#0f172a;display:block;">
                            <?= e($r['nama_lengkap']) ?>
                        </a>
                        <div style="font-size:12px;color:#64748b;">NIK: <?= e($r['no_ktp'] ?: '-') ?></div>
                    </div>
                    <?= renderStatusBadge($r['status_proses']) ?>
                </div>
                <div style="font-size:12.5px;color:#334155;margin-bottom:6px;">
                    <strong><?= e($r['nama_perumahan'] ?: 'Perumahan belum dipilih') ?></strong>
                    <?= !empty($r['no_rumah']) ? ' • Rumah: ' . e($r['no_rumah']) : '' ?>
                </div>
                <div style="margin-bottom:10px;"><?= renderBastBadge($r['no_bast']) ?></div>
                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                    <a href="index.php?page=surat&id=<?= (int)$r['id'] ?>" class="btn btn-soft-emerald btn-xs">📑 Buat Surat</a>
                    <a href="ideb_docx.php?id=<?= (int)$r['id'] ?>" class="btn btn-soft-blue btn-xs">📥 SLIK</a>
                    <a href="index.php?page=nasabah_detail&id=<?= (int)$r['id'] ?>" class="btn btn-outline btn-xs">Detail</a>
                    <?php if (isAdmin()): ?>
                        <button type="button"
                            class="btn btn-soft-amber btn-xs"
                            onclick="openQuickBastModal(this)"
                            data-id="<?= (int)$r['id'] ?>"
                            data-nama="<?= e($r['nama_lengkap']) ?>"
                            data-perumahan="<?= e($r['nama_perumahan']) ?>"
                            data-status="<?= e($r['status_proses']) ?>"
                            data-norumah="<?= e($r['no_rumah']) ?>"
                            data-nobast="<?= e($r['no_bast']) ?>"
                            data-ppjb="<?= e($r['ppjb']) ?>"
                            data-tglppjb="<?= e($r['tanggal_ppjb']) ?>"
                            data-tglserahterima="<?= e($r['tanggal_serah_terima']) ?>"
                            data-shgb="<?= e($r['no_shgb']) ?>"
                            data-imb="<?= e($r['no_imb']) ?>"
                            data-catatan="<?= e($r['catatan']) ?>">
                            Isi BAST
                        </button>
                        <a href="index.php?page=nasabah_form&id=<?= (int)$r['id'] ?>" class="btn btn-outline btn-xs">Edit</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <div style="font-size:12.5px;color:#64748b;">
                Menampilkan <strong><?= $offset + 1 ?>–<?= min($offset + $perPage, $totalRows) ?></strong> dari <strong><?= $totalRows ?></strong> nasabah
            </div>
            <div class="page-links">
                <?php if ($pageNum > 1): ?>
                    <a class="page-link" href="<?= e(buildFilterUrl(['p' => $pageNum - 1])) ?>">‹ Sebelumnya</a>
                <?php endif; ?>
                <?php
                $startP = max(1, $pageNum - 2);
                $endP   = min($totalPages, $pageNum + 2);
                for ($i = $startP; $i <= $endP; $i++):
                ?>
                    <a class="page-link <?= $i === $pageNum ? 'active' : '' ?>" href="<?= e(buildFilterUrl(['p' => $i])) ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($pageNum < $totalPages): ?>
                    <a class="page-link" href="<?= e(buildFilterUrl(['p' => $pageNum + 1])) ?>">Berikutnya ›</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php if (isAdmin()): ?>
<!-- Quick-Update BAST / Surat / Unit Modal -->
<div class="modal-backdrop" id="quickBastModal">
    <div class="modal-box">
        <div class="modal-header">
            <div>
                <h3 style="margin:0;font-size:16px;font-weight:700;" id="qm_nama">Update Cepat BAST & Surat</h3>
                <div style="font-size:12px;color:#64748b;" id="qm_perumahan"></div>
            </div>
            <button type="button" onclick="closeModal('quickBastModal')" style="background:none;border:none;font-size:18px;cursor:pointer;color:#64748b;">✕</button>
        </div>
        <form method="post" action="index.php?page=quick_update_bast">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="id" id="qm_id">
            <input type="hidden" name="return_url" value="<?= e($_SERVER['REQUEST_URI'] ?? 'index.php?page=nasabah') ?>">

            <div class="modal-body">
                <p style="margin:0 0 14px;font-size:12.5px;color:#475569;background:#eff6ff;padding:10px 12px;border-radius:8px;border:1px solid #bfdbfe;">
                    Semua kolom bersifat <strong>opsional</strong>. Isi hanya data yang sudah keluar (misal Nomor BAST menyusul setelah akad).
                </p>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Status Proses</label>
                        <select name="status_proses" id="qm_status_proses" class="form-control">
                            <option value="Pemberkasan">Pemberkasan</option>
                            <option value="Proses Bank">Proses Bank</option>
                            <option value="Siap Akad">Siap Akad</option>
                            <option value="Sudah Akad / BAST">Sudah Akad / BAST</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">No. Rumah / Blok <span class="opt-tag">Opsional</span></label>
                        <input type="text" name="no_rumah" id="qm_no_rumah" class="form-control" placeholder="Contoh: NO.07 Blok B / E48">
                    </div>

                    <div class="form-group col-span-full">
                        <label class="form-label">Nomor BAST <span class="opt-tag">Bisa Diisi Kapan Saja</span></label>
                        <input type="text" name="no_bast" id="qm_no_bast" class="form-control" placeholder="Contoh: 012/KB2/BAST/X-2026">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nomor PPJB <span class="opt-tag">Opsional</span></label>
                        <input type="text" name="ppjb" id="qm_ppjb" class="form-control" placeholder="Nomor PPJB jika ada">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tanggal PPJB <span class="opt-tag">Opsional</span></label>
                        <input type="text" name="tanggal_ppjb" id="qm_tanggal_ppjb" class="form-control" placeholder="Contoh: 29 Oktober 2026">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tanggal Serah Terima <span class="opt-tag">Opsional</span></label>
                        <input type="text" name="tanggal_serah_terima" id="qm_tanggal_serah_terima" class="form-control" placeholder="Contoh: 29 Oktober 2026">
                    </div>

                    <div class="form-group">
                        <label class="form-label">No. SHGB <span class="opt-tag">Opsional</span></label>
                        <input type="text" name="no_shgb" id="qm_no_shgb" class="form-control" placeholder="Contoh: 07034">
                    </div>

                    <div class="form-group col-span-full">
                        <label class="form-label">No. IMB / PBG <span class="opt-tag">Opsional</span></label>
                        <input type="text" name="no_imb" id="qm_no_imb" class="form-control" placeholder="Contoh: 125.5.53/IMB-K/BKPMP2T/2016">
                    </div>

                    <div class="form-group col-span-full">
                        <label class="form-label">Catatan Internal <span class="opt-tag">Opsional</span></label>
                        <input type="text" name="catatan" id="qm_catatan" class="form-control" placeholder="Misal: Menunggu TTD Direktur / Jadwal akad Jumat">
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('quickBastModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/layout_footer.php'; ?>

