<?php
requireAdmin();

$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $editId > 0;
$settings = Database::getSettings($pdo);

$masterPt = $pdo->query("SELECT * FROM master_pengembang ORDER BY pengembang ASC")->fetchAll();
$masterPerum = $pdo->query("
    SELECT mp.*, pt.pengembang AS pt_label, pt.nama_direktur AS dir_label
    FROM master_perumahan mp
    LEFT JOIN master_pengembang pt ON pt.id = mp.pengembang_id
    ORDER BY mp.nama_perumahan ASC
")->fetchAll();

if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM nasabah WHERE id = ?");
    $stmt->execute([$editId]);
    $data = $stmt->fetch();
    if (!$data) {
        setFlash('error', 'Data nasabah tidak ditemukan.');
        header('Location: index.php?page=nasabah');
        exit;
    }
    $pageTitle = 'Edit Data Nasabah: ' . $data['nama_lengkap'];
    $pageSubtitle = 'Perbarui biodata, perumahan, atau susulkan nomor BAST / PPJB / SHGB kapan saja';
} else {
    $maxNo = (int)$pdo->query("SELECT COALESCE(MAX(CAST(no_urut AS INTEGER)), 0) FROM nasabah")->fetchColumn();
    $kotaDefault = $settings['default_kota_surat'] ?? 'Banjar';
    $data = [
        'no_urut'              => (string)($maxNo + 1),
        'status_proses'        => 'Pemberkasan',
        'nama_lengkap'         => '',
        'no_ktp'               => '',
        'npwp'                 => '',
        'no_hp'                => '',
        'ttl'                  => '',
        'pekerjaan'            => $settings['default_pekerjaan'] ?? 'Swasta',
        'alamat'               => '',
        'penghasilan'          => '',
        'penghasilan_pemohon'  => '',
        'nama_pasangan'        => '',
        'ktp_pasangan'         => '',
        'npwp_pasangan'        => '',
        'hp_pasangan'          => '',
        'ttl_pasangan'         => '',
        'pekerjaan_pasangan'   => '',
        'penghasilan_pasangan' => '',
        'alamat_pasangan'      => '',
        'pengembang'           => '',
        'nama_pengembang'      => '',
        'nama_direktur'        => '',
        'jabatan_direktur'     => 'Direktur',
        'nik_direktur'         => '',
        'alamat_direktur'      => '',
        'no_rek_pt'            => '',
        'nama_perumahan'       => '',
        'nama_komplek'         => '',
        'no_rumah'             => '',
        'luas_tanah'           => '',
        'luas_lantai'          => $settings['default_luas_lantai'] ?? '36',
        'lokasi_perumahan'     => '',
        'tempat_akad'          => '',
        'harga_jual_rumah'     => $settings['default_harga_jual'] ?? 'Rp. 182.000.000',
        'uang_muka'            => $settings['default_uang_muka'] ?? 'Rp. 6.000.000',
        'besaran_uang_muka'    => $settings['default_uang_muka'] ?? 'Rp. 6.000.000',
        'sbum'                 => $settings['default_sbum'] ?? 'Rp. 4.000.000',
        'uang_muka_nasabah'    => $settings['default_um_nasabah'] ?? 'Rp. 2.000.000',
        'kpr'                  => $settings['default_kpr'] ?? 'Rp. 176.000.000',
        'tempat_tgl_surat'     => tanggalIndoSekarang($kotaDefault),
        'tanggal_akad'         => '',
        'tahun'                => $settings['default_tahun'] ?? date('Y'),
        'no_bast'              => '',
        'ppjb'                 => '',
        'tanggal_ppjb'         => '',
        'tanggal_serah_terima' => '',
        'no_shgb'              => '',
        'no_imb'               => '',
        'tgl_berkas'           => tanggalIndoSekarang($kotaDefault),
        'foto_tempat_kerja'    => '',
        'share_lokasi_kerja'   => '',
        'pas_foto'             => '',
        'file_ktp'             => '',
        'doc_ktp_check'        => 0,
        'doc_pas_foto_check'   => 0,
        'doc_foto_kerja_check' => 0,
        'doc_lokasi_kerja_check'=> 0,
        'catatan'              => '',
    ];
    $pageTitle = 'Pendaftaran / Input Nasabah Baru';
    $pageSubtitle = 'Cukup isi 4 Data Utama KTP (Nama, NIK, Tempat/Tgl Lahir, Alamat). Data lainnya bisa dilengkapi kapan saja.';
}

$syncNpwpChecked = ($data['npwp'] === '' || $data['npwp'] === $data['no_ktp']);
$syncNpwpPasChecked = ($data['npwp_pasangan'] === '' || $data['npwp_pasangan'] === $data['ktp_pasangan']);

require __DIR__ . '/layout_header.php';
?>

<script>
window.MASTER_PENGEMBANG = <?= json_encode($masterPt, JSON_UNESCAPED_UNICODE) ?>;
window.MASTER_PERUMAHAN  = <?= json_encode($masterPerum, JSON_UNESCAPED_UNICODE) ?>;
window.CURRENT_KODE_BAST = 'SDJ';
</script>

<datalist id="listPekerjaan">
    <option value="Swasta">
    <option value="Wiraswasta">
    <option value="PNS / ASN">
    <option value="Karyawan BUMN / BUMD">
    <option value="Mengurus Rumah Tangga">
    <option value="Pedagang">
    <option value="Buruh / Harian Lepas">
</datalist>

<datalist id="listPenghasilan">
    <option value="Rp. 3.000.000">
    <option value="Rp. 3.500.000">
    <option value="Rp. 4.000.000">
    <option value="Rp. 4.500.000">
    <option value="Rp. 5.000.000">
    <option value="Rp. 5.500.000">
    <option value="Rp. 6.000.000">
</datalist>

<form method="post" action="index.php?page=save_nasabah" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="id" value="<?= (int)$editId ?>">

    <!-- Top Sticky Action Bar -->
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            <a href="<?= $isEdit ? 'index.php?page=nasabah_detail&id=' . (int)$editId : 'index.php?page=nasabah' ?>" class="btn btn-outline btn-sm">← Kembali</a>
            <a href="daftar.php" target="_blank" class="btn btn-soft-blue btn-sm" title="Buka link form pendaftaran mandiri yang bisa dikirim ke WhatsApp nasabah">
                🔗 Buka Link Form untuk Nasabah
            </a>
        </div>
        <div style="display:flex;gap:8px;">
            <?php if (!$isEdit): ?>
                <button type="submit" name="save_and_new" value="1" class="btn btn-outline">Simpan & Daftarkan Lagi</button>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Simpan Data Nasabah
            </button>
        </div>
    </div>

    <!-- INFORMASI UTAMA PENDAFTARAN NASABAH -->
    <div style="background:#ffffff;border:1.5px solid #3b82f6;border-left:6px solid #2563eb;border-radius:12px;padding:15px 18px;margin-bottom:18px;box-shadow:0 2px 8px rgba(37,99,235,0.07);">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            <div>
                <div style="font-weight:800;font-size:14px;color:#1e3a8a;margin-bottom:5px;display:flex;align-items:center;gap:7px;">
                    <span>ℹ️ Informasi Pendaftaran Nasabah</span>
                </div>
                <div style="font-size:13px;color:#334155;line-height:1.55;">
                    Untuk mendaftarkan nasabah baru, <strong>data utamanya cukup 4 kolom sesuai KTP</strong>:
                    <span class="badge badge-blue" style="margin:0 2px;">1. Nama Lengkap</span>
                    <span class="badge badge-blue" style="margin:0 2px;">2. NIK (No. KTP)</span>
                    <span class="badge badge-blue" style="margin:0 2px;">3. Tempat & Tanggal Lahir</span>
                    <span class="badge badge-blue" style="margin:0 2px;">4. Alamat KTP</span>
                    <br>
                    <span style="color:#475569;font-size:12.5px;">Seluruh data lainnya (No. HP, Pasangan, Pilihan Perumahan/PT, Blok Rumah, BAST, PPJB, SHGB, hingga Foto Berkas) bersifat <strong>opsional dan bisa dilengkapi kapan saja</strong>.</span>
                </div>
            </div>
        </div>
    </div>

    <!-- SMART AUTO-FILL BOX (PT + DIREKTUR + PERUMAHAN) -->
    <div class="smart-box">
        <div class="smart-box-title" style="justify-content:space-between;flex-wrap:wrap;">
            <span style="display:inline-flex;align-items:center;gap:7px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                Isi Otomatis Perumahan, PT Pengembang & Direktur (1 Klik)
            </span>
            <span class="badge badge-emerald">Opsional — Bisa Dipilih Kapan Saja</span>
        </div>
        <p class="smart-box-desc">
            Jika unit perumahan sudah diketahui, pilih di bawah ini agar Nama Direktur (misal <em>PT. Kalimantan Batara → Aries Setiyo Nugroho, S.Kom</em>), NIK Direktur, Alamat PT, No. Rekening PT, dan Lokasi langsung terisi otomatis. Jika belum tahu unitnya, lewati saja dulu.
        </p>
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">1. Pilih Cepat Perumahan / Komplek (Otomatis Isi PT + Direktur + Lokasi)</label>
                <select id="preset_perumahan" class="form-control" style="background:#fff;border-color:#93c5fd;">
                    <option value="">-- Lewati dulu jika belum pilih Perumahan (Bisa dilengkapi nanti) --</option>
                    <?php foreach ($masterPerum as $mp): ?>
                        <option value="<?= (int)$mp['id'] ?>">
                            <?= e($mp['nama_perumahan']) ?> — <?= e($mp['pt_label'] ?? '') ?> (Dir: <?= e($mp['dir_label'] ?? '-') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">2. Atau Pilih Pengembang (PT) Saja</label>
                <select id="preset_pengembang" class="form-control" style="background:#fff;border-color:#93c5fd;">
                    <option value="">-- Lewati dulu jika belum pilih PT --</option>
                    <?php foreach ($masterPt as $pt): ?>
                        <option value="<?= (int)$pt['id'] ?>">
                            <?= e($pt['pengembang']) ?> — Dir: <?= e($pt['nama_direktur']) ?> (Rek: <?= e($pt['no_rek_pt']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- TABBED FORM CARD -->
    <div class="card">
        <div class="tabs-nav">
            <button type="button" class="tab-btn active" data-tab-group="nasabahForm" data-tab-btn="tabPemohon">
                1. Data Utama KTP & Biodata
            </button>
            <button type="button" class="tab-btn" data-tab-group="nasabahForm" data-tab-btn="tabProperti">
                2. Unit Rumah, PT & KPR (Opsional)
            </button>
            <button type="button" class="tab-btn" data-tab-group="nasabahForm" data-tab-btn="tabSurat">
                3. BAST, PPJB & SHGB (Menyusul)
            </button>
            <button type="button" class="tab-btn" data-tab-group="nasabahForm" data-tab-btn="tabBerkas">
                4. Dokumen & Catatan (Opsional)
            </button>
        </div>

        <div class="card-body">
            <!-- TAB 1: PEMOHON & PASANGAN -->
            <div class="tab-pane active" data-tab-group="nasabahForm" data-tab-pane="tabPemohon">
                <!-- HIGHLIGHTED 4 CORE KTP FIELDS -->
                <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:18px;margin-bottom:24px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:14px;flex-wrap:wrap;">
                        <h3 style="margin:0;font-size:15px;font-weight:800;color:#1e3a8a;">
                            ★ Data Utama Pendaftaran Nasabah (Sesuai KTP)
                        </h3>
                        <span style="font-size:12px;color:#1d4ed8;font-weight:600;">Keempat kolom utama ini wajib diisi saat mendaftarkan nasabah</span>
                    </div>

                    <div class="form-grid-3">
                        <div class="form-group">
                            <label class="form-label">
                                <span>1. Nama Lengkap Pemohon <span class="req-star">* Utama</span></span>
                            </label>
                            <input type="text" name="nama_lengkap" class="form-control" style="background:#fff;" required value="<?= e($data['nama_lengkap']) ?>" placeholder="Nama lengkap sesuai KTP">
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <span>2. NIK / No. KTP <span class="req-star">* Utama</span></span>
                                <span id="nikDigitCounter" style="font-size:11px;font-weight:600;color:#64748b;"><?= strlen(preg_replace('/\D/', '', $data['no_ktp'])) ?>/16 digit</span>
                            </label>
                            <input type="text" name="no_ktp" id="f_no_ktp" class="form-control" style="background:#fff;" required value="<?= e($data['no_ktp']) ?>" placeholder="16 digit NIK KTP (otomatis mengisi NPWP)">
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <span>3. Tempat, Tanggal Lahir <span class="req-star">* Utama</span></span>
                            </label>
                            <input type="text" name="ttl" class="form-control" style="background:#fff;" required value="<?= e($data['ttl']) ?>" placeholder="Contoh: Banjarmasin, 13-05-1998">
                        </div>

                        <div class="form-group col-span-full">
                            <label class="form-label">
                                <span>4. Alamat Lengkap Sesuai KTP <span class="req-star">* Utama</span></span>
                            </label>
                            <textarea name="alamat" id="f_alamat" class="form-control" style="background:#fff;min-height:66px;" required placeholder="Jl. ..., RT/RW ..., Kelurahan ..., Kecamatan ..., Kota/Kabupaten ..."><?= e($data['alamat']) ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- OPTIONAL ADDITIONAL PEMOHON FIELDS -->
                <h3 style="margin:0 0 14px;font-size:15px;font-weight:700;color:#0f172a;border-bottom:1px solid #f1f5f9;padding-bottom:8px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                    <span>Data Pelengkap Pemohon</span>
                    <span class="opt-tag">Opsional — Bisa Dilengkapi Kapan Saja</span>
                </h3>
                <div class="form-grid-3" style="margin-bottom:24px;">
                    <div class="form-group">
                        <label class="form-label">No. HP / WhatsApp <span class="opt-tag">Bisa Menyusul</span></label>
                        <input type="text" name="no_hp" class="form-control" value="<?= e($data['no_hp']) ?>" placeholder="Contoh: 0813 5155 4071">
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <span>NPWP Pemohon</span>
                            <label style="font-size:11px;font-weight:500;color:#2563eb;cursor:pointer;display:inline-flex;align-items:center;gap:4px;">
                                <input type="checkbox" id="sync_npwp_ktp" <?= $syncNpwpChecked ? 'checked' : '' ?>> Otomatis = NIK
                            </label>
                        </label>
                        <input type="text" name="npwp" id="f_npwp" class="form-control" value="<?= e($data['npwp']) ?>" placeholder="Otomatis menyalin dari NIK KTP">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Pekerjaan <span class="opt-tag">Default: Swasta</span></label>
                        <input type="text" name="pekerjaan" list="listPekerjaan" class="form-control" value="<?= e($data['pekerjaan']) ?>" placeholder="Swasta / Wiraswasta">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Penghasilan Utama <span class="opt-tag">Bisa Menyusul</span></label>
                        <input type="text" name="penghasilan" id="f_penghasilan" list="listPenghasilan" class="form-control" value="<?= e($data['penghasilan']) ?>" placeholder="Contoh: Rp. 4.500.000">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status Proses Saat Ini</label>
                        <select name="status_proses" class="form-control">
                            <?php foreach (['Pemberkasan', 'Proses Bank', 'Siap Akad', 'Sudah Akad / BAST'] as $st): ?>
                                <option value="<?= e($st) ?>" <?= ($data['status_proses'] ?? '') === $st ? 'selected' : '' ?>><?= e($st) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">No. Urut Database</label>
                        <input type="text" name="no_urut" class="form-control" value="<?= e($data['no_urut']) ?>">
                    </div>
                </div>

                <!-- DATA PASANGAN -->
                <div style="display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #f1f5f9;padding-bottom:8px;margin-bottom:14px;flex-wrap:wrap;gap:8px;">
                    <h3 style="margin:0;font-size:15px;font-weight:700;color:#0f172a;">
                        B. Data Pasangan (Suami / Istri) <span class="opt-tag">Kosongkan Jika Belum Menikah / Lajang</span>
                    </h3>
                    <button type="button" id="btnCopyAlamatPasangan" class="btn btn-soft-blue btn-xs">
                        Salin Alamat dari Pemohon
                    </button>
                </div>

                <div class="form-grid-3">
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap Pasangan <span class="opt-tag">Opsional</span></label>
                        <input type="text" name="nama_pasangan" class="form-control" value="<?= e($data['nama_pasangan']) ?>" placeholder="Kosongkan jika lajang">
                    </div>

                    <div class="form-group">
                        <label class="form-label">No. KTP Pasangan <span class="opt-tag">Opsional</span></label>
                        <input type="text" name="ktp_pasangan" id="f_ktp_pasangan" class="form-control" value="<?= e($data['ktp_pasangan']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <span>NPWP Pasangan</span>
                            <label style="font-size:11px;font-weight:500;color:#2563eb;cursor:pointer;display:inline-flex;align-items:center;gap:4px;">
                                <input type="checkbox" id="sync_npwp_pasangan" <?= $syncNpwpPasChecked ? 'checked' : '' ?>> Sama dgn NIK
                            </label>
                        </label>
                        <input type="text" name="npwp_pasangan" id="f_npwp_pasangan" class="form-control" value="<?= e($data['npwp_pasangan']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tempat, Tanggal Lahir Pasangan <span class="opt-tag">Opsional</span></label>
                        <input type="text" name="ttl_pasangan" class="form-control" value="<?= e($data['ttl_pasangan']) ?>" placeholder="Contoh: Banjarmasin, 11-01-1997">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Pekerjaan Pasangan <span class="opt-tag">Opsional</span></label>
                        <input type="text" name="pekerjaan_pasangan" list="listPekerjaan" class="form-control" value="<?= e($data['pekerjaan_pasangan']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">No. HP Pasangan <span class="opt-tag">Opsional</span></label>
                        <input type="text" name="hp_pasangan" class="form-control" value="<?= e($data['hp_pasangan']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Penghasilan Pemohon (Kolom G) <span class="opt-tag">Opsional</span></label>
                        <input type="text" name="penghasilan_pemohon" id="f_penghasilan_pemohon" list="listPenghasilan" class="form-control" value="<?= e($data['penghasilan_pemohon']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Penghasilan Pasangan (Kolom H) <span class="opt-tag">Opsional</span></label>
                        <input type="text" name="penghasilan_pasangan" list="listPenghasilan" class="form-control" value="<?= e($data['penghasilan_pasangan']) ?>">
                    </div>

                    <div class="form-group col-span-full">
                        <label class="form-label">Alamat Pasangan <span class="opt-tag">Opsional</span></label>
                        <textarea name="alamat_pasangan" id="f_alamat_pasangan" class="form-control" placeholder="Klik tombol 'Salin Alamat dari Pemohon' di atas jika sama"><?= e($data['alamat_pasangan']) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- TAB 2: PROPERTI, PT & KPR -->
            <div class="tab-pane" data-tab-group="nasabahForm" data-tab-pane="tabProperti">
                <h3 style="margin:0 0 14px;font-size:15px;font-weight:700;color:#0f172a;border-bottom:1px solid #f1f5f9;padding-bottom:8px;">
                    A. Unit Perumahan & Lokasi (Terisi Otomatis dari Pilihan Perumahan di Atas)
                </h3>
                <div class="form-grid-3" style="margin-bottom:24px;">
                    <div class="form-group">
                        <label class="form-label">Nama Perumahan (Kolom AD) <span class="opt-tag">Otomatis / Opsional</span></label>
                        <input type="text" name="nama_perumahan" id="f_nama_perumahan" class="form-control" value="<?= e($data['nama_perumahan']) ?>" placeholder="Contoh: Komplek Kalimantan Batara 2">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nama Komplek (Kolom AS) <span class="opt-tag">Otomatis / Opsional</span></label>
                        <input type="text" name="nama_komplek" id="f_nama_komplek" class="form-control" value="<?= e($data['nama_komplek']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nomor Rumah / Blok <span class="opt-tag">Bisa Menyusul</span></label>
                        <input type="text" name="no_rumah" id="f_no_rumah" class="form-control" value="<?= e($data['no_rumah']) ?>" placeholder="Contoh: NO.07 Blok B / E48">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Luas Tanah (Kolom AF) <span class="opt-tag">Otomatis / Opsional</span></label>
                        <input type="text" name="luas_tanah" id="f_luas_tanah" class="form-control" value="<?= e($data['luas_tanah']) ?>" placeholder="Contoh: 120 / 98m2">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Luas Lantai / Tipe Rumah (Kolom AG)</label>
                        <input type="text" name="luas_lantai" id="f_luas_lantai" class="form-control" value="<?= e($data['luas_lantai']) ?>" placeholder="36">
                    </div>

                    <div class="form-group col-span-full">
                        <label class="form-label">Lokasi Perumahan (Kolom AH) <span class="opt-tag">Otomatis</span></label>
                        <input type="text" name="lokasi_perumahan" id="f_lokasi_perumahan" class="form-control" value="<?= e($data['lokasi_perumahan']) ?>">
                    </div>

                    <div class="form-group col-span-full">
                        <label class="form-label">Tempat / Alamat Kantor PT (Kolom W) <span class="opt-tag">Otomatis</span></label>
                        <input type="text" name="tempat_akad" id="f_tempat_akad" class="form-control" value="<?= e($data['tempat_akad']) ?>">
                    </div>
                </div>

                <h3 style="margin:0 0 14px;font-size:15px;font-weight:700;color:#0f172a;border-bottom:1px solid #f1f5f9;padding-bottom:8px;">
                    B. Data PT Pengembang & Direktur (Otomatis Terisi Saat Pilih Perumahan / PT)
                </h3>
                <div class="form-grid-3" style="margin-bottom:24px;">
                    <div class="form-group">
                        <label class="form-label">Pengembang Huruf Besar (Kolom V)</label>
                        <input type="text" name="pengembang" id="f_pengembang" class="form-control" value="<?= e($data['pengembang']) ?>" placeholder="PT. KALIMANTAN BATARA">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nama Pengembang Surat (Kolom AJ)</label>
                        <input type="text" name="nama_pengembang" id="f_nama_pengembang" class="form-control" value="<?= e($data['nama_pengembang']) ?>" placeholder="PT. Kalimantan Batara">
                    </div>

                    <div class="form-group">
                        <label class="form-label">No. Rekening PT (Kolom B)</label>
                        <input type="text" name="no_rek_pt" id="f_no_rek_pt" class="form-control" value="<?= e($data['no_rek_pt']) ?>" placeholder="7151001467">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nama Direktur (Kolom AI)</label>
                        <input type="text" name="nama_direktur" id="f_nama_direktur" class="form-control" value="<?= e($data['nama_direktur']) ?>" placeholder="Aries Setiyo Nugroho, S.Kom">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Jabatan Direktur (Kolom D)</label>
                        <input type="text" name="jabatan_direktur" id="f_jabatan_direktur" class="form-control" value="<?= e($data['jabatan_direktur']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">NIK Direktur (Kolom E)</label>
                        <input type="text" name="nik_direktur" id="f_nik_direktur" class="form-control" value="<?= e($data['nik_direktur']) ?>">
                    </div>

                    <div class="form-group col-span-full">
                        <label class="form-label">Alamat Direktur (Kolom F)</label>
                        <input type="text" name="alamat_direktur" id="f_alamat_direktur" class="form-control" value="<?= e($data['alamat_direktur']) ?>">
                    </div>
                </div>

                <h3 style="margin:0 0 14px;font-size:15px;font-weight:700;color:#0f172a;border-bottom:1px solid #f1f5f9;padding-bottom:8px;">
                    C. Rincian Harga Rumah & KPR FLPP (Otomatis Terisi & Menghitung Sendiri)
                </h3>
                <div class="form-grid-3">
                    <div class="form-group">
                        <label class="form-label">Harga Jual Rumah (Kolom Q)</label>
                        <input type="text" name="harga_jual_rumah" id="f_harga_jual_rumah" class="form-control" value="<?= e($data['harga_jual_rumah']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Total Uang Muka (Kolom R)</label>
                        <input type="text" name="uang_muka" id="f_uang_muka" class="form-control" value="<?= e($data['uang_muka']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Subsidi Bantuan Uang Muka / SBUM (Kolom S)</label>
                        <input type="text" name="sbum" id="f_sbum" class="form-control" value="<?= e($data['sbum']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Uang Muka Nasabah (Kolom T) <span class="opt-tag">UM - SBUM</span></label>
                        <input type="text" name="uang_muka_nasabah" id="f_uang_muka_nasabah" class="form-control" value="<?= e($data['uang_muka_nasabah']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Plafon KPR (Kolom U) <span class="opt-tag">Harga - UM</span></label>
                        <input type="text" name="kpr" id="f_kpr" class="form-control" value="<?= e($data['kpr']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Besaran Uang Muka (Kolom AK)</label>
                        <input type="text" name="besaran_uang_muka" id="f_besaran_uang_muka" class="form-control" value="<?= e($data['besaran_uang_muka']) ?>">
                    </div>
                </div>
            </div>

            <!-- TAB 3: PENYURATAN, BAST, PPJB, SHGB, IMB -->
            <div class="tab-pane" data-tab-group="nasabahForm" data-tab-pane="tabSurat">
                <div class="alert alert-info" style="margin-bottom:18px;">
                    <span>💡 <strong>Semua kolom di tab ini bersifat Opsional.</strong> Karena BAST, PPJB, SHGB, dan Tanggal Serah Terima sering keluar belakangan, Anda bisa mengosongkannya sekarang dan mengisinya kapan saja lewat tombol <em>"Isi BAST"</em> di tabel.</span>
                </div>

                <div class="form-grid-3">
                    <div class="form-group">
                        <label class="form-label">Tempat & Tanggal Surat (Kolom C)</label>
                        <input type="text" name="tempat_tgl_surat" class="form-control" value="<?= e($data['tempat_tgl_surat']) ?>" placeholder="Contoh: Banjar, 29 Oktober 2026">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tanggal Berkas (Kolom AW) <span class="opt-tag">Opsional</span></label>
                        <input type="text" name="tgl_berkas" class="form-control" value="<?= e($data['tgl_berkas']) ?>" placeholder="Contoh: Banjar, 29 Oktober 2026">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tahun Akad / BAST (Kolom Y)</label>
                        <input type="text" name="tahun" id="f_tahun" class="form-control" value="<?= e($data['tahun']) ?>" placeholder="2026">
                    </div>

                    <div class="form-group col-span-full">
                        <label class="form-label">
                            <span>Nomor BAST (Kolom Z) <span class="opt-tag">Bisa Diisi Kapan Saja</span></span>
                            <button type="button" id="btnGenerateDraftBast" class="btn btn-soft-blue btn-xs">Buat Format Draft BAST Otomatis</button>
                        </label>
                        <input type="text" name="no_bast" id="f_no_bast" class="form-control" value="<?= e($data['no_bast']) ?>" placeholder="Contoh: 015/KB2/BAST/X-2026 (Kosongkan jika belum ada)">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tanggal Surat / Akad (Kolom X) <span class="opt-tag">Opsional</span></label>
                        <input type="text" name="tanggal_akad" class="form-control" value="<?= e($data['tanggal_akad']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nomor PPJB (Kolom AA) <span class="opt-tag">Opsional</span></label>
                        <input type="text" name="ppjb" class="form-control" value="<?= e($data['ppjb']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tanggal PPJB (Kolom AB) <span class="opt-tag">Opsional</span></label>
                        <input type="text" name="tanggal_ppjb" class="form-control" value="<?= e($data['tanggal_ppjb']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tanggal Serah Terima (Kolom AC) <span class="opt-tag">Opsional</span></label>
                        <input type="text" name="tanggal_serah_terima" class="form-control" value="<?= e($data['tanggal_serah_terima']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nomor SHGB (Kolom AU) <span class="opt-tag">Opsional</span></label>
                        <input type="text" name="no_shgb" class="form-control" value="<?= e($data['no_shgb']) ?>" placeholder="Contoh: 07034">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nomor IMB / PBG (Kolom AV) <span class="opt-tag">Opsional</span></label>
                        <input type="text" name="no_imb" class="form-control" value="<?= e($data['no_imb']) ?>" placeholder="Contoh: 125.5.53/IMB-K/BKPMP2T/2016">
                    </div>
                </div>
            </div>

            <!-- TAB 4: CHECKLIST DOKUMEN & LAMPIRAN -->
            <div class="tab-pane" data-tab-group="nasabahForm" data-tab-pane="tabBerkas">
                <h3 style="margin:0 0 14px;font-size:15px;font-weight:700;color:#0f172a;">
                    Checklist Kelengkapan Lampiran & Foto (Opsional)
                </h3>

                <div class="form-grid" style="margin-bottom:20px;">
                    <div style="padding:14px;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;">
                        <label style="display:flex;align-items:center;gap:8px;font-weight:700;cursor:pointer;margin-bottom:8px;">
                            <input type="checkbox" name="doc_ktp_check" value="1" <?= !empty($data['doc_ktp_check']) ? 'checked' : '' ?>>
                            Foto / Scan KTP Sudah Ada
                        </label>
                        <input type="file" name="upload_file_ktp" accept="image/*,.pdf" class="form-control" style="font-size:12px;">
                        <?php if (!empty($data['file_ktp'])): ?>
                            <div class="form-help" style="margin-top:5px;">File tersimpan: <a href="<?= e($data['file_ktp']) ?>" target="_blank" style="color:#2563eb;text-decoration:underline;"><?= e(basename($data['file_ktp'])) ?></a></div>
                        <?php endif; ?>
                    </div>

                    <div style="padding:14px;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;">
                        <label style="display:flex;align-items:center;gap:8px;font-weight:700;cursor:pointer;margin-bottom:8px;">
                            <input type="checkbox" name="doc_pas_foto_check" value="1" <?= !empty($data['doc_pas_foto_check']) ? 'checked' : '' ?>>
                            Pas Foto Pemohon Sudah Ada
                        </label>
                        <input type="file" name="upload_pas_foto" accept="image/*,.pdf" class="form-control" style="font-size:12px;">
                        <?php if (!empty($data['pas_foto'])): ?>
                            <div class="form-help" style="margin-top:5px;">File tersimpan: <a href="<?= e($data['pas_foto']) ?>" target="_blank" style="color:#2563eb;text-decoration:underline;"><?= e(basename($data['pas_foto'])) ?></a></div>
                        <?php endif; ?>
                    </div>

                    <div style="padding:14px;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;">
                        <label style="display:flex;align-items:center;gap:8px;font-weight:700;cursor:pointer;margin-bottom:8px;">
                            <input type="checkbox" name="doc_foto_kerja_check" value="1" <?= !empty($data['doc_foto_kerja_check']) ? 'checked' : '' ?>>
                            Foto Tempat Kerja Sudah Ada
                        </label>
                        <input type="file" name="upload_foto_tempat_kerja" accept="image/*,.pdf" class="form-control" style="font-size:12px;">
                        <?php if (!empty($data['foto_tempat_kerja'])): ?>
                            <div class="form-help" style="margin-top:5px;">File tersimpan: <a href="<?= e($data['foto_tempat_kerja']) ?>" target="_blank" style="color:#2563eb;text-decoration:underline;"><?= e(basename($data['foto_tempat_kerja'])) ?></a></div>
                        <?php endif; ?>
                    </div>

                    <div style="padding:14px;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;">
                        <label style="display:flex;align-items:center;gap:8px;font-weight:700;cursor:pointer;margin-bottom:8px;">
                            <input type="checkbox" name="doc_lokasi_kerja_check" value="1" <?= !empty($data['doc_lokasi_kerja_check']) ? 'checked' : '' ?>>
                            Share Lokasi Tempat Kerja Sudah Ada
                        </label>
                        <input type="text" name="share_lokasi_kerja" class="form-control" value="<?= e($data['share_lokasi_kerja']) ?>" placeholder="Tempel Link Google Maps atau koordinat (Opsional)">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Catatan Internal Pemberkasan <span class="opt-tag">Opsional</span></label>
                    <textarea name="catatan" class="form-control" placeholder="Catatan kekurangan berkas, jadwal wawancara bank, jadwal akad, dll."><?= e($data['catatan']) ?></textarea>
                </div>
            </div>
        </div>

        <div style="padding:16px 20px;background:#f8fafc;border-top:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;border-bottom-left-radius:12px;border-bottom-right-radius:12px;flex-wrap:wrap;gap:10px;">
            <span style="font-size:12.5px;color:#64748b;">Perubahan otomatis tersimpan ke database & siap diekspor ke Excel Mail Merge kapan saja.</span>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <?php if (!$isEdit): ?>
                    <button type="submit" name="save_and_new" value="1" class="btn btn-outline">Simpan & Input Lagi</button>
                <?php endif; ?>
                <button type="submit" name="save_and_ideb" value="1" class="btn btn-emerald" title="Simpan data lalu langsung buka Permohonan iDEB (SLIK OJK) siap cetak / download Word">
                    📄 Simpan & Buat SLIK OJK (iDEB)
                </button>
                <button type="submit" class="btn btn-primary">Simpan Data Nasabah</button>
            </div>
        </div>
    </div>
</form>

<?php require __DIR__ . '/layout_footer.php'; ?>

