<?php
$catalog = BsnSuratService::getCatalog();
$settings = Database::getSettings($pdo);
$defaultKota = trim((string)($settings['default_kota_surat'] ?? 'Banjar'));
$todayIndo = tanggalIndoSekarang($defaultKota);

$allNasabah = $pdo->query("
    SELECT id, no_urut, nama_lengkap, no_ktp, nama_pasangan, pekerjaan_pasangan,
           nama_perumahan, no_rumah, pengembang, no_bast, status_proses
    FROM nasabah
    ORDER BY CAST(no_urut AS INTEGER) ASC, id ASC
")->fetchAll();

$selectedId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($selectedId <= 0 && !empty($allNasabah)) {
    $selectedId = (int)$allNasabah[0]['id'];
}

$activeRow = null;
$resolved = null;
if ($selectedId > 0) {
    $st = $pdo->prepare("SELECT * FROM nasabah WHERE id = ?");
    $st->execute([$selectedId]);
    $activeRow = $st->fetch() ?: null;
    if ($activeRow) {
        $resolved = BsnSuratService::resolveNasabahData($activeRow, $pdo);
    }
}

$pageTitle = 'Pembuatan Surat KPR Bersubsidi BSN';
$pageSubtitle = 'Buat masing-masing dari 18 dokumen resmi bundel FLPP BSN (.docx asli maupun Cetak PDF) sesuai data nasabah';

$categories = ['Semua', 'Analisa & Lapangan', 'Pernyataan Pemohon', 'SBUM & Kuasa Bank', 'Developer & BAST', 'Kondisional / Tambahan'];

require __DIR__ . '/layout_header.php';
?>

<!-- Step 1: Pilih Nasabah & Ringkasan Kesiapan Data -->
<div class="card" style="border-color:#bfdbfe;">
    <div class="card-header" style="background:linear-gradient(135deg,#eff6ff 0%,#f8fafc 100%);">
        <div>
            <h2 class="card-title" style="color:#1e3a8a;">
                <span>1. Pilih Nasabah yang Akan Dibuatkan Surat</span>
                <?php if ($activeRow): ?>
                    <span class="badge badge-blue">Aktif: #<?= e($activeRow['no_urut'] ?: $activeRow['id']) ?> — <?= e($activeRow['nama_lengkap']) ?></span>
                <?php endif; ?>
            </h2>
            <div style="font-size:12px;color:#475569;margin-top:2px;">
                Seluruh kolom nama, NIK, pasangan, alamat, harga KPR, perumahan, PT pengembang, dan direktur otomatis ditarik dari database.
            </div>
        </div>
        <?php if ($activeRow): ?>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a href="index.php?page=nasabah_detail&id=<?= (int)$activeRow['id'] ?>" class="btn btn-outline btn-xs">Detail Nasabah</a>
            <?php if (isAdmin()): ?>
                <a href="index.php?page=nasabah_form&id=<?= (int)$activeRow['id'] ?>" class="btn btn-primary btn-xs">✏️ Lengkapi / Edit Data</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="card-body" style="padding:16px 20px;">
        <?php if (empty($allNasabah)): ?>
            <div class="alert alert-info" style="margin:0;">
                <span>Belum ada data nasabah di database. Silakan tambahkan nasabah terlebih dahulu.</span>
                <a href="index.php?page=nasabah_form" class="btn btn-primary btn-sm">+ Tambah Nasabah</a>
            </div>
        <?php else: ?>
            <form method="get" action="index.php" id="nasabahPickerForm" style="display:grid;grid-template-columns:minmax(220px,1fr) minmax(260px,2fr) auto;gap:12px;align-items:end;">
                <input type="hidden" name="page" value="surat">
                <div class="form-group">
                    <label class="form-label">Filter Cepat Nama / NIK / Komplek</label>
                    <input type="text" id="filterNasabahInput" class="form-control" placeholder="Ketik nama atau NIK untuk menyaring daftar...">
                </div>
                <div class="form-group">
                    <label class="form-label">Pilih Nasabah (<?= count($allNasabah) ?> terdaftar)</label>
                    <select name="id" id="selectNasabahId" class="form-control" onchange="this.form.submit()">
                        <?php foreach ($allNasabah as $n):
                            $noLbl = $n['no_urut'] ?: $n['id'];
                            $perumLbl = trim((string)$n['nama_perumahan']) !== '' ? $n['nama_perumahan'] : 'Unit belum dipilih';
                            $optText = '#' . $noLbl . ' — ' . $n['nama_lengkap'] . ' (' . ($n['no_ktp'] ?: 'NIK kosong') . ') • ' . $perumLbl;
                        ?>
                            <option value="<?= (int)$n['id'] ?>" <?= (int)$n['id'] === (int)$selectedId ? 'selected' : '' ?>>
                                <?= e($optText) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary">Muat Data</button>
                </div>
            </form>

            <?php if ($resolved): ?>
            <!-- Ringkasan Data Otomatis yang Akan Masuk ke Surat -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:10px;margin-top:14px;padding-top:14px;border-top:1px dashed #cbd5e1;">
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:9px;padding:10px 12px;">
                    <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Pemohon Utama</div>
                    <div style="font-weight:700;color:#0f172a;font-size:13.5px;margin-top:2px;"><?= e($resolved['nama_lengkap'] ?: '-') ?></div>
                    <div style="font-size:11.5px;color:#475569;">NIK: <span style="font-family:monospace;"><?= e($resolved['no_ktp'] ?: '-') ?></span></div>
                    <div style="font-size:11.5px;color:#64748b;"><?= e($resolved['pekerjaan']) ?><?= $resolved['penghasilan'] ? ' • ' . e($resolved['penghasilan']) : '' ?></div>
                </div>

                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:9px;padding:10px 12px;">
                    <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Data Pasangan</div>
                    <?php if ($resolved['has_pasangan']): ?>
                        <div style="font-weight:700;color:#0f172a;font-size:13.5px;margin-top:2px;"><?= e($resolved['nama_pasangan']) ?></div>
                        <div style="font-size:11.5px;color:#475569;">NIK: <span style="font-family:monospace;"><?= e($resolved['ktp_pasangan'] ?: '-') ?></span></div>
                        <div style="font-size:11.5px;color:<?= $resolved['pasangan_tidak_bekerja'] ? '#b45309' : '#64748b' ?>;">
                            Pekerjaan: <?= e($resolved['pekerjaan_pasangan'] ?: 'Tidak Bekerja / IRT') ?>
                            <?= $resolved['pasangan_tidak_bekerja'] ? ' (Surat #17 relevan)' : '' ?>
                        </div>
                    <?php else: ?>
                        <div style="font-weight:600;color:#64748b;font-size:13px;margin-top:2px;">Lajang / Belum Diisi</div>
                        <div style="font-size:11.5px;color:#94a3b8;">Kolom pasangan otomatis dikosongkan rapi</div>
                    <?php endif; ?>
                </div>

                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:9px;padding:10px 12px;">
                    <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Unit & PT Pengembang</div>
                    <?php if ($resolved['nama_perumahan'] !== '' || $resolved['pengembang'] !== ''): ?>
                        <div style="font-weight:700;color:#0f172a;font-size:13px;margin-top:2px;">
                            <?= e($resolved['nama_perumahan'] ?: '-') ?>
                            <?= $resolved['no_rumah'] !== '' ? ' (No. ' . e($resolved['no_rumah']) . ')' : '' ?>
                        </div>
                        <div style="font-size:11.5px;color:#475569;"><?= e($resolved['pengembang'] ?: '-') ?></div>
                        <div style="font-size:11.5px;color:#64748b;">Dir: <?= e($resolved['nama_direktur'] ?: '-') ?> • Rek: <?= e($resolved['no_rek_pt'] ?: '-') ?></div>
                    <?php else: ?>
                        <div style="font-weight:600;color:#b45309;font-size:12.5px;margin-top:2px;">Unit / PT Belum Dipilih</div>
                        <div style="font-size:11.5px;color:#64748b;">Surat tetap bisa dicetak dengan titik-titik isian rapi</div>
                    <?php endif; ?>
                </div>

                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:9px;padding:10px 12px;">
                    <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Nilai KPR & BAST</div>
                    <div style="font-weight:700;color:#059669;font-size:13px;margin-top:2px;">Harga: <?= e($resolved['harga_jual_rumah']) ?></div>
                    <div style="font-size:11.5px;color:#475569;">UM: <?= e($resolved['uang_muka']) ?> • SBUM: <?= e($resolved['sbum']) ?></div>
                    <div style="font-size:11.5px;color:#64748b;">PPJB/BAST: <?= e($resolved['no_bast'] ?: ($resolved['ppjb'] ?: 'Menyusul (Opsional)')) ?></div>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php if ($activeRow && $resolved): ?>
<!-- Step 2: Parameter Tambahan Opsional (Tanggal Surat, Petugas FS, Rekening Tabungan, SP3) -->
<div class="card">
    <div class="card-header" style="padding:13px 20px;">
        <div class="card-title" style="font-size:14px;">
            <span>2. Pengaturan Tanggal & Isian Tambahan Surat</span>
            <span class="opt-tag">Opsional — Boleh Dikosongkan</span>
        </div>
        <button type="button" class="btn btn-outline btn-xs" onclick="document.getElementById('extraSuratParams').style.display = document.getElementById('extraSuratParams').style.display === 'none' ? 'grid' : 'none';">
            ⚙️ Tampilkan / Sembunyikan Kolom Bank (SP3 & Rekening)
        </button>
    </div>
    <div class="card-body" style="padding:14px 20px;">
        <div class="form-grid-3" style="align-items:end;">
            <div class="form-group">
                <label class="form-label">
                    <span>Kota & Tanggal Surat</span>
                    <button type="button" onclick="document.getElementById('s_tgl').value='<?= e(addslashes($todayIndo)) ?>';syncAllSuratLinks();" style="background:none;border:none;color:#2563eb;font-size:11px;font-weight:700;cursor:pointer;padding:0;">Isi Hari Ini</button>
                </label>
                <input type="text" id="s_tgl" class="form-control js-surat-param" value="<?= e($resolved['tanggal_surat']) ?>" placeholder="Contoh: Banjar, 31 Oktober 2026">
            </div>

            <div class="form-group">
                <label class="form-label">Nama Petugas Financing Service (SLIK #14) <span class="opt-tag">Opsional</span></label>
                <input type="text" id="s_fs" class="form-control js-surat-param" value="" placeholder="Kosongkan jika tanda tangan tulis tangan">
            </div>

            <div class="form-group">
                <label class="form-label">No. Rekening Tabungan BSN Nasabah (#09 & #10) <span class="opt-tag">Opsional</span></label>
                <input type="text" id="s_rek_tab" class="form-control js-surat-param" value="" placeholder="Isi jika rekening BSN sudah keluar">
            </div>
        </div>

        <div id="extraSuratParams" class="form-grid-3" style="display:none;margin-top:12px;padding-top:12px;border-top:1px dashed #e2e8f0;">
            <div class="form-group">
                <label class="form-label">Nomor SP3 / SP3K (#04 & #09) <span class="opt-tag">Opsional</span></label>
                <input type="text" id="s_sp3_no" class="form-control js-surat-param" value="" placeholder="Nomor Surat Persetujuan Pembiayaan">
            </div>
            <div class="form-group">
                <label class="form-label">Tanggal SP3 (#04) <span class="opt-tag">Opsional</span></label>
                <input type="text" id="s_sp3_tgl" class="form-control js-surat-param" value="" placeholder="Contoh: 31 Oktober 2026">
            </div>
            <div class="form-group">
                <label class="form-label">Nomor Rekening KPR Bersubsidi (#09) <span class="opt-tag">Opsional</span></label>
                <input type="text" id="s_rek_kpr" class="form-control js-surat-param" value="<?= e($resolved['tanggal_akad'] ? '' : '') ?>" placeholder="Opsional saat akad">
            </div>
        </div>
    </div>
</div>

<!-- Step 3: Action Bar Paket Terpilih & Preset Cepat -->
<div class="card" style="background:#0f172a;color:#fff;border:none;position:sticky;top:68px;z-index:24;box-shadow:0 10px 25px rgba(15,23,42,0.22);">
    <div class="card-body" style="padding:14px 20px;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            <div>
                <div style="font-size:14px;font-weight:700;display:flex;align-items:center;gap:8px;">
                    <span id="selectedDocsBadge" style="background:#2563eb;color:#fff;padding:2px 10px;border-radius:99px;font-size:12.5px;">0</span>
                    <span>Dokumen dipilih untuk <strong><?= e($resolved['nama_lengkap']) ?></strong></span>
                </div>
                <div style="font-size:11.5px;color:#94a3b8;margin-top:2px;">
                    Bisa cetak/download <strong>1 surat saja</strong> pada kartu di bawah, atau gabungkan beberapa surat sekaligus di sini:
                </div>
            </div>

            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <button type="button" onclick="triggerBulkSuratAction('docx')" class="btn btn-emerald btn-sm" title="Download 1 file Word (.docx) gabungan seluruh surat yang dicentang (siap Ctrl+P sekali jalan)">
                    📥 Download Gabungan 1 Word (.docx)
                </button>
                <button type="button" onclick="triggerBulkSuratAction('zip')" class="btn btn-primary btn-sm" title="Download file .zip berisi masing-masing surat terpisah (01_..., 02_..., dst)">
                    🗂️ Download Per Surat Terpisah (.zip)
                </button>
                <button type="button" onclick="triggerBulkSuratAction('print')" class="btn btn-outline btn-sm" style="background:rgba(255,255,255,0.12);color:#fff;border-color:rgba(255,255,255,0.28);" title="Buka tampilan layar A4 siap Print / Save as PDF">
                    🖨️ Preview & Cetak / PDF Terpilih
                </button>
            </div>
        </div>

        <!-- Baris Tombol Preset Paket Cepat -->
        <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-top:12px;padding-top:10px;border-top:1px solid rgba(255,255,255,0.12);font-size:12px;">
            <span style="color:#94a3b8;font-weight:600;margin-right:4px;">Pilih Cepat:</span>
            <button type="button" class="btn btn-xs" style="background:#1e293b;color:#e2e8f0;border-color:#334155;" onclick="selectSuratPreset('wajib')">✅ Paket Utama (16 Wajib<?= $resolved['pasangan_tidak_bekerja'] ? ' + #17' : '' ?>)</button>
            <button type="button" class="btn btn-xs" style="background:#1e293b;color:#e2e8f0;border-color:#334155;" onclick="selectSuratPreset('all')">📚 Semua 18 Dokumen</button>
            <button type="button" class="btn btn-xs" style="background:#1e293b;color:#e2e8f0;border-color:#334155;" onclick="selectSuratPreset('Analisa & Lapangan')">🔍 Analisa, SLIK & Denah (4)</button>
            <button type="button" class="btn btn-xs" style="background:#1e293b;color:#e2e8f0;border-color:#334155;" onclick="selectSuratPreset('Pernyataan Pemohon')">📝 Pernyataan Pemohon (5)</button>
            <button type="button" class="btn btn-xs" style="background:#1e293b;color:#e2e8f0;border-color:#334155;" onclick="selectSuratPreset('SBUM & Kuasa Bank')">💰 SBUM & Kuasa Bank (5)</button>
            <button type="button" class="btn btn-xs" style="background:#1e293b;color:#e2e8f0;border-color:#334155;" onclick="selectSuratPreset('Developer & BAST')">🏗️ Developer & BAST (2)</button>
            <button type="button" class="btn btn-xs" style="background:#1e293b;color:#fca5a5;border-color:#334155;" onclick="selectSuratPreset('none')">✕ Kosongkan</button>
        </div>
    </div>
</div>

<!-- Filter Tab Kategori Tampilan Kartu -->
<div class="chip-bar" style="margin-bottom:16px;">
    <?php foreach ($categories as $idx => $cat): ?>
        <button type="button" class="chip js-cat-filter <?= $idx === 0 ? 'active' : '' ?>" data-cat="<?= e($cat) ?>" onclick="filterSuratCardsByCategory('<?= e($cat) ?>', this)">
            <?= e($cat) ?>
        </button>
    <?php endforeach; ?>
</div>

<!-- Grid Masing-Masing 18 Surat -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:14px;">
    <?php foreach ($catalog as $key => $doc):
        $recommendedCond = ($key === 'pernyataan_tidak_bekerja' && $resolved['pasangan_tidak_bekerja']);
        $isChecked = !empty($doc['default_checked']) || $recommendedCond;
        $catBadgeClass = match ($doc['category']) {
            'Analisa & Lapangan'     => 'badge-blue',
            'Pernyataan Pemohon'     => 'badge-emerald',
            'SBUM & Kuasa Bank'      => 'badge-amber',
            'Developer & BAST'       => 'badge-slate',
            default                  => 'badge-rose',
        };
    ?>
    <div class="card js-surat-card"
         data-doc-key="<?= e($key) ?>"
         data-category="<?= e($doc['category']) ?>"
         data-wajib="<?= !empty($doc['default_checked']) ? '1' : '0' ?>"
         data-recommended="<?= $recommendedCond ? '1' : '0' ?>"
         style="margin-bottom:0;display:flex;flex-direction:column;justify-content:space-between;transition:border-color 0.15s ease,box-shadow 0.15s ease;<?= $isChecked ? 'border-color:#93c5fd;' : '' ?>">

        <div style="padding:15px 16px 12px;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:8px;">
                <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;user-select:none;">
                    <input type="checkbox"
                           class="js-doc-check"
                           value="<?= e($key) ?>"
                           <?= $isChecked ? 'checked' : '' ?>
                           onchange="onSuratCheckChange()"
                           style="width:17px;height:17px;cursor:pointer;accent-color:#2563eb;">
                    <span style="font-weight:800;font-size:12px;background:#0f172a;color:#fff;padding:2px 8px;border-radius:6px;">
                        SURAT #<?= e($doc['code']) ?>
                    </span>
                </label>

                <div style="display:flex;gap:5px;flex-wrap:wrap;justify-content:flex-end;">
                    <span class="badge <?= $catBadgeClass ?>"><?= e($doc['category']) ?></span>
                    <?php if ($recommendedCond): ?>
                        <span class="badge badge-amber" title="Pasangan terdeteksi belum bekerja / IRT">Disarankan</span>
                    <?php elseif (!empty($doc['is_conditional'])): ?>
                        <span class="badge badge-slate">Opsional</span>
                    <?php endif; ?>
                </div>
            </div>

            <h3 style="margin:0 0 6px;font-size:14.5px;font-weight:700;color:#0f172a;line-height:1.35;">
                <?= e($doc['title']) ?>
            </h3>
            <p style="margin:0;font-size:12px;color:#64748b;line-height:1.45;">
                <?= e($doc['description']) ?>
            </p>
        </div>

        <div style="padding:10px 16px;background:#f8fafc;border-top:1px solid #e2e8f0;border-bottom-left-radius:12px;border-bottom-right-radius:12px;display:flex;align-items:center;justify-content:space-between;gap:8px;">
            <a href="surat_docx.php?id=<?= (int)$activeRow['id'] ?>&doc=<?= e($key) ?>"
               class="btn btn-soft-emerald btn-sm js-single-docx"
               data-doc="<?= e($key) ?>"
               style="flex:1;"
               title="Download surat ini saja dalam format Microsoft Word (.docx) asli">
                📥 Word (.docx)
            </a>
            <a href="print.php?id=<?= (int)$activeRow['id'] ?>&tpl=<?= e($key) ?>"
               target="_blank"
               class="btn btn-soft-blue btn-sm js-single-print"
               data-doc="<?= e($key) ?>"
               style="flex:1;"
               title="Buka pratinjau A4 surat ini untuk langsung Print atau Simpan sebagai PDF">
                🖨️ Preview / PDF
            </a>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<script>
(function () {
    const nasabahId = <?= (int)$activeRow['id'] ?>;

    // 1. Live filter pada dropdown Nasabah
    const filterInput = document.getElementById('filterNasabahInput');
    const selectEl = document.getElementById('selectNasabahId');
    if (filterInput && selectEl) {
        const allOptions = Array.from(selectEl.options).map(o => ({
            value: o.value,
            text: o.text,
            selected: o.selected
        }));

        filterInput.addEventListener('input', function () {
            const kw = this.value.trim().toLowerCase();
            const currentVal = selectEl.value;
            selectEl.innerHTML = '';
            allOptions.forEach(item => {
                if (kw === '' || item.text.toLowerCase().includes(kw) || item.value === currentVal) {
                    const opt = document.createElement('option');
                    opt.value = item.value;
                    opt.text = item.text;
                    if (item.value === currentVal) opt.selected = true;
                    selectEl.appendChild(opt);
                }
            });
        });
    }

    // 2. Bangun query string parameter tambahan opsional
    function buildExtraParamsQs() {
        const map = {
            tgl: document.getElementById('s_tgl')?.value.trim() || '',
            fs: document.getElementById('s_fs')?.value.trim() || '',
            no_rek_tabungan: document.getElementById('s_rek_tab')?.value.trim() || '',
            sp3_no: document.getElementById('s_sp3_no')?.value.trim() || '',
            sp3_tgl: document.getElementById('s_sp3_tgl')?.value.trim() || '',
            no_rek_kpr: document.getElementById('s_rek_kpr')?.value.trim() || ''
        };
        const parts = [];
        Object.keys(map).forEach(k => {
            if (map[k] !== '') {
                parts.push(encodeURIComponent(k) + '=' + encodeURIComponent(map[k]));
            }
        });
        return parts.length > 0 ? '&' + parts.join('&') : '';
    }

    // 3. Sinkronkan link tombol per-kartu saat input tanggal / FS / SP3 diubah
    window.syncAllSuratLinks = function () {
        const extra = buildExtraParamsQs();
        document.querySelectorAll('.js-single-docx').forEach(a => {
            const doc = a.getAttribute('data-doc');
            a.href = 'surat_docx.php?id=' + nasabahId + '&doc=' + encodeURIComponent(doc) + extra;
        });
        document.querySelectorAll('.js-single-print').forEach(a => {
            const doc = a.getAttribute('data-doc');
            a.href = 'print.php?id=' + nasabahId + '&tpl=' + encodeURIComponent(doc) + extra;
        });
    };

    document.querySelectorAll('.js-surat-param').forEach(inp => {
        inp.addEventListener('input', window.syncAllSuratLinks);
    });

    // 4. Hitung jumlah centang & highlight kartu
    window.onSuratCheckChange = function () {
        const checks = document.querySelectorAll('.js-doc-check');
        let checkedCount = 0;
        checks.forEach(chk => {
            const card = chk.closest('.js-surat-card');
            if (chk.checked) {
                checkedCount++;
                if (card) card.style.borderColor = '#93c5fd';
            } else {
                if (card) card.style.borderColor = '#e2e8f0';
            }
        });
        const badge = document.getElementById('selectedDocsBadge');
        if (badge) badge.textContent = String(checkedCount);
    };

    // 5. Tombol Preset Pilihan Cepat
    window.selectSuratPreset = function (preset) {
        document.querySelectorAll('.js-surat-card').forEach(card => {
            const chk = card.querySelector('.js-doc-check');
            if (!chk) return;
            const cat = card.getAttribute('data-category');
            const wajib = card.getAttribute('data-wajib') === '1';
            const rec = card.getAttribute('data-recommended') === '1';

            if (preset === 'all') {
                chk.checked = true;
            } else if (preset === 'none') {
                chk.checked = false;
            } else if (preset === 'wajib') {
                chk.checked = (wajib || rec);
            } else {
                chk.checked = (cat === preset);
            }
        });
        window.onSuratCheckChange();
    };

    // 6. Filter Kategori Kartu Tampilan
    window.filterSuratCardsByCategory = function (cat, btnEl) {
        document.querySelectorAll('.js-cat-filter').forEach(b => b.classList.remove('active'));
        if (btnEl) btnEl.classList.add('active');

        document.querySelectorAll('.js-surat-card').forEach(card => {
            if (cat === 'Semua' || card.getAttribute('data-category') === cat) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    };

    // 7. Eksekusi Download Gabungan / ZIP / Print untuk seluruh surat yang dicentang
    window.triggerBulkSuratAction = function (action) {
        const keys = Array.from(document.querySelectorAll('.js-doc-check:checked')).map(c => c.value);
        if (keys.length === 0) {
            alert('Silakan centang minimal 1 surat terlebih dahulu.');
            return;
        }
        const extra = buildExtraParamsQs();
        const docsParam = encodeURIComponent(keys.join(','));

        if (action === 'docx') {
            window.location.href = 'surat_docx.php?id=' + nasabahId + '&docs=' + docsParam + extra;
        } else if (action === 'zip') {
            window.location.href = 'surat_docx.php?id=' + nasabahId + '&docs=' + docsParam + '&mode=zip_surat' + extra;
        } else if (action === 'print') {
            window.open('print.php?id=' + nasabahId + '&docs=' + docsParam + extra, '_blank');
        }
    };

    window.syncAllSuratLinks();
    window.onSuratCheckChange();
})();
</script>
<?php endif; ?>

<?php require __DIR__ . '/layout_footer.php'; ?>

