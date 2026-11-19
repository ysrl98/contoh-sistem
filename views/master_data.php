<?php
$pageTitle = 'Master PT Pengembang, Perumahan & Standar Harga';
$pageSubtitle = 'Atur data otomatis (PT, Direktur, Rekening, Komplek, dan Standar Harga KPR FLPP) agar tidak perlu ketik berulang';

$settings = Database::getSettings($pdo);
$listPt = $pdo->query("SELECT * FROM master_pengembang ORDER BY id ASC")->fetchAll();
$listPerum = $pdo->query("
    SELECT mp.*, pt.pengembang AS pt_nama, pt.nama_direktur AS pt_dir
    FROM master_perumahan mp
    LEFT JOIN master_pengembang pt ON pt.id = mp.pengembang_id
    ORDER BY mp.id ASC
")->fetchAll();

$editPtId = isset($_GET['edit_pt']) ? (int)$_GET['edit_pt'] : 0;
$editPtRow = null;
if ($editPtId > 0) {
    foreach ($listPt as $item) {
        if ((int)$item['id'] === $editPtId) $editPtRow = $item;
    }
}

$editPerumId = isset($_GET['edit_perum']) ? (int)$_GET['edit_perum'] : 0;
$editPerumRow = null;
if ($editPerumId > 0) {
    foreach ($listPerum as $item) {
        if ((int)$item['id'] === $editPerumId) $editPerumRow = $item;
    }
}

require __DIR__ . '/layout_header.php';
?>

<div class="card">
    <div class="tabs-nav">
        <button type="button" class="tab-btn <?= $editPerumRow ? '' : 'active' ?>" data-tab-group="masterTabs" data-tab-btn="mPt">
            1. Master PT & Direktur (<?= count($listPt) ?>)
        </button>
        <button type="button" class="tab-btn <?= $editPerumRow ? 'active' : '' ?>" data-tab-group="masterTabs" data-tab-btn="mPerum">
            2. Master Perumahan / Komplek (<?= count($listPerum) ?>)
        </button>
        <button type="button" class="tab-btn" data-tab-group="masterTabs" data-tab-btn="mDefault">
            3. Standar Nilai Otomatis KPR FLPP
        </button>
    </div>

    <div class="card-body">
        <!-- TAB 1: MASTER PT & DIREKTUR -->
        <div class="tab-pane <?= $editPerumRow ? '' : 'active' ?>" data-tab-group="masterTabs" data-tab-pane="mPt">
            <div class="table-wrap" style="margin-bottom:24px;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Kode BAST</th>
                            <th>Nama PT Pengembang</th>
                            <th>Nama Direktur & NIK</th>
                            <th>No. Rekening PT</th>
                            <th>Alamat Direktur</th>
                            <?php if (isAdmin()): ?><th style="text-align:right;">Aksi</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($listPt as $pt): ?>
                        <tr>
                            <td><span class="badge badge-blue"><?= e($pt['kode_pt'] ?: '-') ?></span></td>
                            <td>
                                <div style="font-weight:700;color:#0f172a;"><?= e($pt['pengembang']) ?></div>
                                <div style="font-size:12px;color:#64748b;">Format Surat: <?= e($pt['nama_pengembang']) ?></div>
                            </td>
                            <td>
                                <div style="font-weight:600;"><?= e($pt['nama_direktur']) ?></div>
                                <div style="font-size:12px;color:#64748b;"><?= e($pt['jabatan_direktur']) ?> • NIK: <?= e($pt['nik_direktur']) ?></div>
                            </td>
                            <td style="font-family:monospace;font-weight:600;"><?= e($pt['no_rek_pt']) ?></td>
                            <td style="font-size:12px;max-width:280px;"><?= e($pt['alamat_direktur']) ?></td>
                            <?php if (isAdmin()): ?>
                            <td style="text-align:right;white-space:nowrap;">
                                <a href="index.php?page=master&edit_pt=<?= (int)$pt['id'] ?>" class="btn btn-outline btn-xs">Edit</a>
                                <form method="post" action="index.php?page=delete_master_pt" style="display:inline;" onsubmit="return confirm('Hapus Master PT ini?');">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$pt['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-xs">Hapus</button>
                                </form>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (isAdmin()): ?>
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:18px;">
                <h3 style="margin:0 0 12px;font-size:14.5px;font-weight:700;">
                    <?= $editPtRow ? 'Edit Master PT: ' . e($editPtRow['pengembang']) : '+ Tambah Master PT Pengembang Baru' ?>
                </h3>
                <form method="post" action="index.php?page=save_master_pt">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="id" value="<?= (int)($editPtRow['id'] ?? 0) ?>">

                    <div class="form-grid-3">
                        <div class="form-group">
                            <label class="form-label">Nama PT (Huruf Kapital / Kolom V) <span class="req-star">*</span></label>
                            <input type="text" name="pengembang" class="form-control" required value="<?= e($editPtRow['pengembang'] ?? '') ?>" placeholder="Contoh: PT. KALIMANTAN BATARA">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nama PT di Surat (Kolom AJ)</label>
                            <input type="text" name="nama_pengembang" class="form-control" value="<?= e($editPtRow['nama_pengembang'] ?? '') ?>" placeholder="Contoh: PT. Kalimantan Batara">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Kode Singkatan BAST</label>
                            <input type="text" name="kode_pt" class="form-control" value="<?= e($editPtRow['kode_pt'] ?? '') ?>" placeholder="Contoh: KB2 / SDJ / SBN">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nama Direktur <span class="req-star">*</span></label>
                            <input type="text" name="nama_direktur" class="form-control" required value="<?= e($editPtRow['nama_direktur'] ?? '') ?>" placeholder="Contoh: Aries Setiyo Nugroho, S.Kom">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Jabatan Direktur</label>
                            <input type="text" name="jabatan_direktur" class="form-control" value="<?= e($editPtRow['jabatan_direktur'] ?? 'Direktur') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">NIK Direktur</label>
                            <input type="text" name="nik_direktur" class="form-control" value="<?= e($editPtRow['nik_direktur'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">No. Rekening PT</label>
                            <input type="text" name="no_rek_pt" class="form-control" value="<?= e($editPtRow['no_rek_pt'] ?? '') ?>">
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label class="form-label">Alamat Direktur</label>
                            <input type="text" name="alamat_direktur" class="form-control" value="<?= e($editPtRow['alamat_direktur'] ?? '') ?>">
                        </div>
                    </div>
                    <div style="margin-top:14px;display:flex;gap:8px;">
                        <button type="submit" class="btn btn-primary btn-sm"><?= $editPtRow ? 'Simpan Perubahan PT' : 'Simpan Master PT' ?></button>
                        <?php if ($editPtRow): ?>
                            <a href="index.php?page=master" class="btn btn-outline btn-sm">Batal Edit</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <!-- TAB 2: MASTER PERUMAHAN -->
        <div class="tab-pane <?= $editPerumRow ? 'active' : '' ?>" data-tab-group="masterTabs" data-tab-pane="mPerum">
            <div class="table-wrap" style="margin-bottom:24px;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nama Perumahan / Komplek</th>
                            <th>PT Pengembang & Direktur Otomatis</th>
                            <th>Lokasi Perumahan</th>
                            <th>Default LT / Tipe</th>
                            <?php if (isAdmin()): ?><th style="text-align:right;">Aksi</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($listPerum as $pr): ?>
                        <tr>
                            <td>
                                <div style="font-weight:700;color:#0f172a;"><?= e($pr['nama_perumahan']) ?></div>
                                <?php if ($pr['nama_komplek'] && $pr['nama_komplek'] !== $pr['nama_perumahan']): ?>
                                    <div style="font-size:11.5px;color:#64748b;">Komplek: <?= e($pr['nama_komplek']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-weight:600;color:#1d4ed8;"><?= e($pr['pt_nama'] ?: '-') ?></div>
                                <div style="font-size:12px;color:#64748b;">Dir: <?= e($pr['pt_dir'] ?: '-') ?></div>
                            </td>
                            <td style="font-size:12px;max-width:300px;"><?= e($pr['lokasi_perumahan']) ?></td>
                            <td><?= e($pr['luas_tanah'] ?: '-') ?> / Tipe <?= e($pr['luas_lantai'] ?: '36') ?></td>
                            <?php if (isAdmin()): ?>
                            <td style="text-align:right;white-space:nowrap;">
                                <a href="index.php?page=master&edit_perum=<?= (int)$pr['id'] ?>" class="btn btn-outline btn-xs">Edit</a>
                                <form method="post" action="index.php?page=delete_master_perum" style="display:inline;" onsubmit="return confirm('Hapus Master Perumahan ini?');">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$pr['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-xs">Hapus</button>
                                </form>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (isAdmin()): ?>
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:18px;">
                <h3 style="margin:0 0 12px;font-size:14.5px;font-weight:700;">
                    <?= $editPerumRow ? 'Edit Master Perumahan: ' . e($editPerumRow['nama_perumahan']) : '+ Tambah Master Perumahan / Komplek Baru' ?>
                </h3>
                <form method="post" action="index.php?page=save_master_perum">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="id" value="<?= (int)($editPerumRow['id'] ?? 0) ?>">

                    <div class="form-grid-3">
                        <div class="form-group">
                            <label class="form-label">PT Pengembang Pemilik Proyek <span class="req-star">*</span></label>
                            <select name="pengembang_id" class="form-control" required>
                                <option value="">-- Pilih PT Pengembang --</option>
                                <?php foreach ($listPt as $pt): ?>
                                    <option value="<?= (int)$pt['id'] ?>" <?= (int)($editPerumRow['pengembang_id'] ?? 0) === (int)$pt['id'] ? 'selected' : '' ?>>
                                        <?= e($pt['pengembang']) ?> (Dir: <?= e($pt['nama_direktur']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Nama Perumahan (Kolom AD) <span class="req-star">*</span></label>
                            <input type="text" name="nama_perumahan" class="form-control" required value="<?= e($editPerumRow['nama_perumahan'] ?? '') ?>" placeholder="Contoh: Komplek Sapta Marga Permai">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Nama Komplek (Kolom AS)</label>
                            <input type="text" name="nama_komplek" class="form-control" value="<?= e($editPerumRow['nama_komplek'] ?? '') ?>" placeholder="Kosongkan jika sama dgn Nama Perumahan">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Default Luas Tanah</label>
                            <input type="text" name="luas_tanah" class="form-control" value="<?= e($editPerumRow['luas_tanah'] ?? '120') ?>" placeholder="Contoh: 120 / 98m2">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Default Tipe / Luas Lantai</label>
                            <input type="text" name="luas_lantai" class="form-control" value="<?= e($editPerumRow['luas_lantai'] ?? '36') ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Kode BAST Perumahan</label>
                            <input type="text" name="kode_bast" class="form-control" value="<?= e($editPerumRow['kode_bast'] ?? '') ?>" placeholder="Contoh: KB2 / SDJ / SBN">
                        </div>

                        <div class="form-group col-span-full">
                            <label class="form-label">Lokasi Perumahan (Kolom AH)</label>
                            <input type="text" name="lokasi_perumahan" class="form-control" value="<?= e($editPerumRow['lokasi_perumahan'] ?? '') ?>">
                        </div>

                        <div class="form-group col-span-full">
                            <label class="form-label">Tempat / Alamat Kantor PT (Kolom W)</label>
                            <input type="text" name="tempat_akad" class="form-control" value="<?= e($editPerumRow['tempat_akad'] ?? '') ?>">
                        </div>
                    </div>

                    <div style="margin-top:14px;display:flex;gap:8px;">
                        <button type="submit" class="btn btn-primary btn-sm"><?= $editPerumRow ? 'Simpan Perubahan Perumahan' : 'Simpan Master Perumahan' ?></button>
                        <?php if ($editPerumRow): ?>
                            <a href="index.php?page=master" class="btn btn-outline btn-sm">Batal Edit</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <!-- TAB 3: GLOBAL DEFAULT FLPP VALUES -->
        <div class="tab-pane" data-tab-group="masterTabs" data-tab-pane="mDefault">
            <p style="margin:0 0 16px;font-size:13px;color:#475569;">
                Nilai di bawah ini akan <strong>otomatis terisi setiap kali Anda menginput Nasabah Baru</strong> karena nilainya selalu sama untuk standar rumah subsidi FLPP (dan tetap bisa diedit per nasabah jika ada perbedaan).
            </p>
            <form method="post" action="index.php?page=save_settings">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <div class="form-grid-3">
                    <div class="form-group">
                        <label class="form-label">Default Harga Jual Rumah</label>
                        <input type="text" name="default_harga_jual" class="form-control" value="<?= e($settings['default_harga_jual'] ?? 'Rp. 182.000.000') ?>" <?= isAdmin() ? '' : 'readonly' ?>>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Default Total Uang Muka</label>
                        <input type="text" name="default_uang_muka" class="form-control" value="<?= e($settings['default_uang_muka'] ?? 'Rp. 6.000.000') ?>" <?= isAdmin() ? '' : 'readonly' ?>>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Default SBUM</label>
                        <input type="text" name="default_sbum" class="form-control" value="<?= e($settings['default_sbum'] ?? 'Rp. 4.000.000') ?>" <?= isAdmin() ? '' : 'readonly' ?>>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Default Uang Muka Nasabah</label>
                        <input type="text" name="default_um_nasabah" class="form-control" value="<?= e($settings['default_um_nasabah'] ?? 'Rp. 2.000.000') ?>" <?= isAdmin() ? '' : 'readonly' ?>>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Default Plafon KPR</label>
                        <input type="text" name="default_kpr" class="form-control" value="<?= e($settings['default_kpr'] ?? 'Rp. 176.000.000') ?>" <?= isAdmin() ? '' : 'readonly' ?>>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Default Tipe / Luas Lantai</label>
                        <input type="text" name="default_luas_lantai" class="form-control" value="<?= e($settings['default_luas_lantai'] ?? '36') ?>" <?= isAdmin() ? '' : 'readonly' ?>>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Default Kota Surat</label>
                        <input type="text" name="default_kota_surat" class="form-control" value="<?= e($settings['default_kota_surat'] ?? 'Banjar') ?>" <?= isAdmin() ? '' : 'readonly' ?>>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Default Tahun</label>
                        <input type="text" name="default_tahun" class="form-control" value="<?= e($settings['default_tahun'] ?? date('Y')) ?>" <?= isAdmin() ? '' : 'readonly' ?>>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Default Pekerjaan</label>
                        <input type="text" name="default_pekerjaan" class="form-control" value="<?= e($settings['default_pekerjaan'] ?? 'Swasta') ?>" <?= isAdmin() ? '' : 'readonly' ?>>
                    </div>
                </div>
                <?php if (isAdmin()): ?>
                    <div style="margin-top:16px;">
                        <button type="submit" class="btn btn-primary">Simpan Standar Nilai Otomatis</button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>

