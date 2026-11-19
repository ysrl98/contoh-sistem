<?php
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM nasabah WHERE id = ?");
$stmt->execute([$id]);
$r = $stmt->fetch();

if (!$r) {
    setFlash('error', 'Data nasabah tidak ditemukan.');
    header('Location: index.php?page=nasabah');
    exit;
}

$pageTitle = 'Detail Nasabah: ' . $r['nama_lengkap'];
$pageSubtitle = 'No. Urut #' . ($r['no_urut'] ?: $r['id']) . ' • ' . ($r['nama_perumahan'] ?: 'Perumahan belum dipilih');
$wa = formatWaLink($r['no_hp']);

function kvItem(string $label, ?string $val, bool $full = false): string {
    $v = trim((string)$val);
    $cls = $full ? 'kv-item col-span-full' : 'kv-item';
    $disp = $v !== '' ? e($v) : '<span class="kv-empty">Belum diisi (Opsional)</span>';
    return '<div class="' . $cls . '"><div class="kv-label">' . e($label) . '</div><div class="kv-val">' . $disp . '</div></div>';
}

require __DIR__ . '/layout_header.php';
?>

<!-- Top Action Toolbar -->
<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:18px;flex-wrap:wrap;">
    <a href="index.php?page=nasabah" class="btn btn-outline btn-sm">← Kembali ke Daftar</a>

    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="index.php?page=surat&id=<?= (int)$r['id'] ?>" class="btn btn-emerald btn-sm" title="Pilih & cetak masing-masing dari 18 surat resmi KPR Bersubsidi BSN">
            📑 Buat Surat BSN (18 Dokumen)
        </a>
        <a href="surat_docx.php?id=<?= (int)$r['id'] ?>&docs=wajib" class="btn btn-soft-emerald btn-sm" title="Download langsung Paket 16 Surat Wajib BSN dalam 1 file Word (.docx)">
            📥 Paket Wajib (.docx)
        </a>
        <a href="ideb_docx.php?id=<?= (int)$r['id'] ?>" class="btn btn-soft-blue btn-sm" title="Download langsung file Word PERMOHONAN iDEB (SLIK OJK) Bank Syariah Nasional">
            📥 Word SLIK OJK
        </a>
        <a href="print.php?id=<?= (int)$r['id'] ?>&tpl=biodata" target="_blank" class="btn btn-outline btn-sm">
            📄 Biodata
        </a>
        <a href="export.php?ids=<?= (int)$r['id'] ?>" class="btn btn-outline btn-sm" title="Download Excel 1 baris ini saja untuk Word Mail Merge">
            📥 Excel (1 Orang)
        </a>
        <?php if (isAdmin()): ?>
            <a href="index.php?page=nasabah_form&id=<?= (int)$r['id'] ?>" class="btn btn-primary btn-sm">✏️ Edit Lengkap</a>
            <form method="post" action="index.php?page=delete_nasabah" style="margin:0;" onsubmit="return confirm('Yakin ingin menghapus data nasabah <?= e(addslashes($r['nama_lengkap'])) ?>?');">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Header Summary Banner -->
<div class="card" style="padding:20px 24px;">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div>
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:6px;">
                <span class="badge badge-slate">No. #<?= e($r['no_urut'] ?: $r['id']) ?></span>
                <?= renderStatusBadge($r['status_proses']) ?>
                <?= renderBastBadge($r['no_bast']) ?>
            </div>
            <h2 style="margin:4px 0;font-size:22px;font-weight:800;color:#0f172a;"><?= e($r['nama_lengkap']) ?></h2>
            <div style="font-size:13.5px;color:#475569;">
                NIK: <strong style="font-family:monospace;"><?= e($r['no_ktp'] ?: '-') ?></strong>
                • NPWP: <strong style="font-family:monospace;"><?= e($r['npwp'] ?: '-') ?></strong>
                <?php if (!empty($r['pekerjaan'])): ?>
                    • Pekerjaan: <strong><?= e($r['pekerjaan']) ?></strong>
                <?php endif; ?>
            </div>
        </div>

        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <?php if ($wa): ?>
                <a href="<?= e($wa) ?>" target="_blank" class="btn btn-emerald btn-sm">💬 Hubungi WhatsApp (<?= e($r['no_hp']) ?>)</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($r['catatan'])): ?>
        <div style="margin-top:14px;padding:10px 14px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;color:#92400e;font-size:13px;">
            <strong>Catatan Internal:</strong> <?= e($r['catatan']) ?>
        </div>
    <?php endif; ?>
</div>

<!-- Grouped Detail Tabs -->
<div class="card">
    <div class="tabs-nav">
        <button type="button" class="tab-btn active" data-tab-group="detailTab" data-tab-btn="dSurat">
            1. Status, Penyuratan & Dokumen
        </button>
        <button type="button" class="tab-btn" data-tab-group="detailTab" data-tab-btn="dProperti">
            2. Unit Perumahan, PT & Nilai KPR
        </button>
        <button type="button" class="tab-btn" data-tab-group="detailTab" data-tab-btn="dProfil">
            3. Profil Pemohon & Pasangan
        </button>
    </div>

    <div class="card-body">
        <!-- TAB 1 -->
        <div class="tab-pane active" data-tab-group="detailTab" data-tab-pane="dSurat">
            <div style="background:linear-gradient(135deg,#eff6ff 0%,#f0fdf4 100%);border:1px solid #bfdbfe;border-radius:12px;padding:15px 18px;margin-bottom:22px;">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:10px;">
                    <div>
                        <h3 style="margin:0;font-size:14.5px;font-weight:700;color:#1e3a8a;">📑 Cetak Cepat Masing-Masing Surat Resmi FLPP BSN (18 Dokumen)</h3>
                        <div style="font-size:12px;color:#475569;margin-top:2px;">Klik nama surat untuk download Word (.docx) langsung, atau buka halaman lengkap untuk kustomisasi paket & cetak PDF.</div>
                    </div>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                        <a href="surat_docx.php?id=<?= (int)$r['id'] ?>&docs=wajib" class="btn btn-emerald btn-xs">📥 Paket 16 Wajib (.docx)</a>
                        <a href="surat_docx.php?id=<?= (int)$r['id'] ?>&docs=all&mode=zip_surat" class="btn btn-primary btn-xs">🗂️ Semua 18 Terpisah (.zip)</a>
                        <a href="index.php?page=surat&id=<?= (int)$r['id'] ?>" class="btn btn-outline btn-xs">Buka Menu Lengkap →</a>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(235px,1fr));gap:7px;">
                    <?php foreach (BsnSuratService::getCatalog() as $sKey => $sDoc): ?>
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;background:#fff;border:1px solid #dbeafe;border-radius:8px;padding:6px 9px;font-size:12px;">
                        <a href="surat_docx.php?id=<?= (int)$r['id'] ?>&doc=<?= e($sKey) ?>" style="font-weight:600;color:#0f172a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;" title="Download Word: <?= e($sDoc['title']) ?>">
                            <span style="color:#2563eb;font-weight:800;"><?= e($sDoc['code']) ?>.</span> <?= e($sDoc['short_title']) ?>
                        </a>
                        <div style="display:flex;gap:4px;flex-shrink:0;">
                            <a href="surat_docx.php?id=<?= (int)$r['id'] ?>&doc=<?= e($sKey) ?>" style="padding:2px 6px;border-radius:5px;background:#ecfdf5;color:#047857;font-weight:700;font-size:11px;border:1px solid #a7f3d0;" title="Download Word (.docx)">.docx</a>
                            <a href="print.php?id=<?= (int)$r['id'] ?>&tpl=<?= e($sKey) ?>" target="_blank" style="padding:2px 6px;border-radius:5px;background:#eff6ff;color:#1d4ed8;font-weight:700;font-size:11px;border:1px solid #bfdbfe;" title="Preview / Cetak PDF">PDF</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <h3 style="margin:0 0 12px;font-size:14.5px;font-weight:700;">Data Penyuratan, BAST, PPJB & Sertifikat</h3>
            <div class="kv-grid" style="margin-bottom:24px;">
                <?= kvItem('Nomor BAST', $r['no_bast']) ?>
                <?= kvItem('Tanggal Serah Terima', $r['tanggal_serah_terima']) ?>
                <?= kvItem('Nomor PPJB', $r['ppjb']) ?>
                <?= kvItem('Tanggal PPJB', $r['tanggal_ppjb']) ?>
                <?= kvItem('Nomor SHGB', $r['no_shgb']) ?>
                <?= kvItem('Nomor IMB / PBG', $r['no_imb']) ?>
                <?= kvItem('Tempat & Tanggal Surat', $r['tempat_tgl_surat']) ?>
                <?= kvItem('Tanggal Berkas', $r['tgl_berkas']) ?>
                <?= kvItem('Tanggal Akad / Surat', $r['tanggal_akad']) ?>
                <?= kvItem('Tahun', $r['tahun']) ?>
            </div>

            <h3 style="margin:0 0 12px;font-size:14.5px;font-weight:700;">Checklist Lampiran Dokumen</h3>
            <div class="form-grid-4">
                <?php
                $docs = [
                    ['label' => 'KTP Pemohon', 'chk' => $r['doc_ktp_check'], 'file' => $r['file_ktp'], 'is_link' => false],
                    ['label' => 'Pas Foto', 'chk' => $r['doc_pas_foto_check'], 'file' => $r['pas_foto'], 'is_link' => false],
                    ['label' => 'Foto Tempat Kerja', 'chk' => $r['doc_foto_kerja_check'], 'file' => $r['foto_tempat_kerja'], 'is_link' => false],
                    ['label' => 'Share Lokasi Kerja', 'chk' => $r['doc_lokasi_kerja_check'], 'file' => $r['share_lokasi_kerja'], 'is_link' => true],
                ];
                foreach ($docs as $dc):
                    $ok = !empty($dc['chk']) || !empty($dc['file']);
                ?>
                <div style="padding:14px;border-radius:10px;border:1px solid <?= $ok ? '#a7f3d0' : '#e2e8f0' ?>;background:<?= $ok ? '#ecfdf5' : '#f8fafc' ?>;">
                    <div style="font-weight:700;font-size:13px;color:<?= $ok ? '#065f46' : '#475569' ?>;margin-bottom:4px;">
                        <?= $ok ? '✓ ' : '○ ' ?><?= e($dc['label']) ?>
                    </div>
                    <div style="font-size:12px;color:#64748b;">
                        <?php if (!empty($dc['file'])): ?>
                            <a href="<?= e($dc['file']) ?>" target="_blank" style="color:#2563eb;font-weight:600;text-decoration:underline;">
                                <?= $dc['is_link'] ? 'Buka Lokasi / Link ↗' : 'Lihat Lampiran ↗' ?>
                            </a>
                        <?php else: ?>
                            <?= $ok ? 'Sudah diceklis lengkap' : 'Belum dilampirkan' ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- TAB 2 -->
        <div class="tab-pane" data-tab-group="detailTab" data-tab-pane="dProperti">
            <h3 style="margin:0 0 12px;font-size:14.5px;font-weight:700;">Unit Rumah & Perumahan</h3>
            <div class="kv-grid" style="margin-bottom:22px;">
                <?= kvItem('Nama Perumahan', $r['nama_perumahan']) ?>
                <?= kvItem('Nama Komplek', $r['nama_komplek']) ?>
                <?= kvItem('Nomor Rumah / Blok', $r['no_rumah']) ?>
                <?= kvItem('Luas Tanah / Tipe Lantai', ($r['luas_tanah'] ?: '-') . ' / Tipe ' . ($r['luas_lantai'] ?: '-')) ?>
                <?= kvItem('Lokasi Perumahan', $r['lokasi_perumahan'], true) ?>
                <?= kvItem('Tempat / Alamat Kantor PT', $r['tempat_akad'], true) ?>
            </div>

            <h3 style="margin:0 0 12px;font-size:14.5px;font-weight:700;">Rincian Nilai KPR FLPP</h3>
            <div class="kv-grid" style="margin-bottom:22px;">
                <?= kvItem('Harga Jual Rumah', $r['harga_jual_rumah']) ?>
                <?= kvItem('Plafon KPR', $r['kpr']) ?>
                <?= kvItem('Total Uang Muka', $r['uang_muka']) ?>
                <?= kvItem('SBUM (Subsidi Uang Muka)', $r['sbum']) ?>
                <?= kvItem('Uang Muka Nasabah', $r['uang_muka_nasabah']) ?>
                <?= kvItem('Besaran Uang Muka', $r['besaran_uang_muka']) ?>
            </div>

            <h3 style="margin:0 0 12px;font-size:14.5px;font-weight:700;">Data PT Pengembang & Direktur</h3>
            <div class="kv-grid">
                <?= kvItem('Pengembang (Kolom V)', $r['pengembang']) ?>
                <?= kvItem('Nama Pengembang (Kolom AJ)', $r['nama_pengembang']) ?>
                <?= kvItem('Nama Direktur', $r['nama_direktur']) ?>
                <?= kvItem('Jabatan Direktur', $r['jabatan_direktur']) ?>
                <?= kvItem('NIK Direktur', $r['nik_direktur']) ?>
                <?= kvItem('No. Rekening PT', $r['no_rek_pt']) ?>
                <?= kvItem('Alamat Direktur', $r['alamat_direktur'], true) ?>
            </div>
        </div>

        <!-- TAB 3 -->
        <div class="tab-pane" data-tab-group="detailTab" data-tab-pane="dProfil">
            <h3 style="margin:0 0 12px;font-size:14.5px;font-weight:700;">Biodata Pemohon Utama</h3>
            <div class="kv-grid" style="margin-bottom:22px;">
                <?= kvItem('Nama Lengkap', $r['nama_lengkap']) ?>
                <?= kvItem('No. HP / WhatsApp', $r['no_hp']) ?>
                <?= kvItem('NIK KTP', $r['no_ktp']) ?>
                <?= kvItem('NPWP', $r['npwp']) ?>
                <?= kvItem('Tempat, Tanggal Lahir', $r['ttl']) ?>
                <?= kvItem('Pekerjaan', $r['pekerjaan']) ?>
                <?= kvItem('Penghasilan', $r['penghasilan']) ?>
                <?= kvItem('Penghasilan Pemohon (Kolom G)', $r['penghasilan_pemohon']) ?>
                <?= kvItem('Alamat KTP Pemohon', $r['alamat'], true) ?>
            </div>

            <h3 style="margin:0 0 12px;font-size:14.5px;font-weight:700;">Biodata Pasangan (Suami / Istri)</h3>
            <div class="kv-grid">
                <?= kvItem('Nama Pasangan', $r['nama_pasangan']) ?>
                <?= kvItem('No. HP Pasangan', $r['hp_pasangan']) ?>
                <?= kvItem('NIK KTP Pasangan', $r['ktp_pasangan']) ?>
                <?= kvItem('NPWP Pasangan', $r['npwp_pasangan']) ?>
                <?= kvItem('Tempat, Tanggal Lahir Pasangan', $r['ttl_pasangan']) ?>
                <?= kvItem('Pekerjaan Pasangan', $r['pekerjaan_pasangan']) ?>
                <?= kvItem('Penghasilan Pasangan', $r['penghasilan_pasangan']) ?>
                <?= kvItem('Alamat Pasangan', $r['alamat_pasangan'], true) ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>

