<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$masterPerum = $pdo->query("SELECT id, nama_perumahan, lokasi_perumahan FROM master_perumahan ORDER BY nama_perumahan ASC")->fetchAll();
$submitted = false;
$errorMsg = '';

function uploadPublicDoc(string $inputName): string {
    if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
        return '';
    }
    if ((int)$_FILES[$inputName]['size'] > 8 * 1024 * 1024) {
        return '';
    }
    $ext = strtolower(pathinfo((string)$_FILES[$inputName]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'pdf'], true)) {
        return '';
    }
    $uploadDir = __DIR__ . '/storage/uploads';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }
    $safeName = 'pub_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (move_uploaded_file($_FILES[$inputName]['tmp_name'], $uploadDir . '/' . $safeName)) {
        return 'storage/uploads/' . $safeName;
    }
    return '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $nama   = trim((string)($_POST['nama_lengkap'] ?? ''));
    $ktp    = preg_replace('/^[.\']+/', '', trim((string)($_POST['no_ktp'] ?? ''))) ?? '';
    $ttl    = trim((string)($_POST['ttl'] ?? ''));
    $alamat = trim((string)($_POST['alamat'] ?? ''));

    if ($nama === '' || $ktp === '' || $ttl === '' || $alamat === '') {
        $errorMsg = 'Mohon lengkapi 4 Data Utama sesuai KTP: Nama Lengkap, NIK, Tempat & Tanggal Lahir, serta Alamat.';
    } else {
        $perumId = (int)($_POST['perumahan_id'] ?? 0) ?: null;
        $namaPerum = '';
        if ($perumId) {
            foreach ($masterPerum as $mp) {
                if ((int)$mp['id'] === $perumId) {
                    $namaPerum = $mp['nama_perumahan'];
                    break;
                }
            }
        }

        $npwp = trim((string)($_POST['npwp'] ?? '')) ?: $ktp;
        $statusNikah = ($_POST['status_nikah'] ?? 'Lajang') === 'Menikah' ? 'Menikah' : 'Lajang';

        $fileKtp   = uploadPublicDoc('upload_ktp');
        $pasFoto   = uploadPublicDoc('upload_pas_foto');
        $fotoKerja = uploadPublicDoc('upload_foto_kerja');

        $stmt = $pdo->prepare("
            INSERT INTO pengajuan_nasabah (
                nama_lengkap, no_ktp, npwp, ttl, alamat,
                no_hp, pekerjaan, penghasilan, perumahan_id, nama_perumahan,
                status_nikah, nama_pasangan, ktp_pasangan, ttl_pasangan, pekerjaan_pasangan, hp_pasangan, alamat_pasangan,
                file_ktp, pas_foto, foto_tempat_kerja, share_lokasi_kerja, catatan_nasabah, status_pengajuan, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)
        ");

        $stmt->execute([
            $nama,
            $ktp,
            $npwp,
            $ttl,
            $alamat,
            trim((string)($_POST['no_hp'] ?? '')),
            trim((string)($_POST['pekerjaan'] ?? '')),
            trim((string)($_POST['penghasilan'] ?? '')),
            $perumId,
            $namaPerum,
            $statusNikah,
            $statusNikah === 'Menikah' ? trim((string)($_POST['nama_pasangan'] ?? '')) : '',
            $statusNikah === 'Menikah' ? trim((string)($_POST['ktp_pasangan'] ?? '')) : '',
            $statusNikah === 'Menikah' ? trim((string)($_POST['ttl_pasangan'] ?? '')) : '',
            $statusNikah === 'Menikah' ? trim((string)($_POST['pekerjaan_pasangan'] ?? '')) : '',
            $statusNikah === 'Menikah' ? trim((string)($_POST['hp_pasangan'] ?? '')) : '',
            $statusNikah === 'Menikah' ? trim((string)($_POST['alamat_pasangan'] ?? '')) : '',
            $fileKtp,
            $pasFoto,
            $fotoKerja,
            trim((string)($_POST['share_lokasi_kerja'] ?? '')),
            trim((string)($_POST['catatan_nasabah'] ?? '')),
            date('Y-m-d H:i:s'),
        ]);

        $submitted = true;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulir Pendaftaran Nasabah KPR FLPP</title>
    <link rel="stylesheet" href="assets/css/style.css?v=1.1">
    <style>
        body {
            background: linear-gradient(180deg, #eff6ff 0%, #f8fafc 320px);
        }
        .pub-container {
            max-width: 680px;
            margin: 0 auto;
            padding: 24px 16px 56px;
        }
        .pub-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
            color: #fff;
            border-radius: 16px;
            padding: 24px 22px;
            margin-bottom: 18px;
            box-shadow: 0 10px 25px -5px rgba(30, 58, 138, 0.28);
        }
        .section-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
        }
    </style>
</head>
<body>
<div class="pub-container">
    <div class="pub-hero">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
            <div class="brand-logo">FL</div>
            <div>
                <h1 style="margin:0;font-size:19px;font-weight:800;">Formulir Pendaftaran Nasabah FLPP</h1>
                <p style="margin:2px 0 0;font-size:12.5px;color:#cbd5e1;">Pendataan Awal Pengajuan Rumah Subsidi KPR FLPP</p>
            </div>
        </div>
    </div>

    <?php if ($submitted): ?>
        <div class="section-card" style="text-align:center;padding:36px 24px;border-color:#a7f3d0;">
            <div style="width:58px;height:58px;border-radius:50%;background:#ecfdf5;color:#059669;display:inline-flex;align-items:center;justify-content:center;font-size:28px;margin-bottom:12px;">✓</div>
            <h2 style="margin:0 0 8px;font-size:20px;color:#0f172a;">Data Pendaftaran Berhasil Dikirim!</h2>
            <p style="margin:0 0 20px;color:#475569;font-size:14px;">
                Terima kasih, data utama KTP Anda telah masuk ke sistem kami. Dokumen atau data pelengkap lainnya dapat dilengkapi menyusul bersama petugas admin kami.
            </p>
            <a href="daftar.php" class="btn btn-primary">Isi Formulir Baru</a>
        </div>
    <?php else: ?>
        <!-- PANDUAN JELAS 4 DATA UTAMA -->
        <div style="background:#ffffff;border:1.5px solid #3b82f6;border-left:6px solid #2563eb;border-radius:12px;padding:15px 18px;margin-bottom:18px;">
            <div style="font-weight:800;font-size:14px;color:#1e3a8a;margin-bottom:6px;">
                📌 Informasi Penting Pengisian Data
            </div>
            <div style="font-size:13px;color:#334155;line-height:1.6;">
                Untuk pendaftaran awal, <strong>yang utama wajib diisi cukup 4 data sesuai KTP Anda</strong>:
                <div style="margin:7px 0;display:flex;flex-wrap:wrap;gap:6px;">
                    <span class="badge badge-blue">1. Nama Lengkap</span>
                    <span class="badge badge-blue">2. NIK KTP (16 Digit)</span>
                    <span class="badge badge-blue">3. Tempat & Tanggal Lahir</span>
                    <span class="badge badge-blue">4. Alamat KTP</span>
                </div>
                <span style="color:#475569;font-size:12.5px;">Adapun data lainnya di bawah (seperti Pilihan Perumahan, Pekerjaan, Data Pasangan, maupun Foto Berkas) bersifat <strong>opsional dan bisa dilengkapi kapan saja</strong>.</span>
            </div>
        </div>

        <?php if ($errorMsg !== ''): ?>
            <div class="alert alert-error"><?= e($errorMsg) ?></div>
        <?php endif; ?>

        <form method="post" action="daftar.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

            <!-- BAGIAN 1: 4 DATA UTAMA WAJIB -->
            <div class="section-card" style="border-color:#93c5fd;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:6px;">
                    <h2 style="margin:0;font-size:15.5px;font-weight:800;color:#1e3a8a;">1. Data Utama Sesuai KTP (Wajib)</h2>
                    <span class="badge badge-blue">Wajib Diisi</span>
                </div>

                <div style="display:flex;flex-direction:column;gap:14px;">
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap Sesuai KTP <span class="req-star">* Wajib</span></label>
                        <input type="text" name="nama_lengkap" class="form-control" required value="<?= e($_POST['nama_lengkap'] ?? '') ?>" placeholder="Contoh: Ade Rahmi">
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <span>Nomor Induk Kependudukan / NIK KTP <span class="req-star">* Wajib</span></span>
                            <span id="pubNikCount" style="font-size:11px;color:#64748b;">0/16 digit</span>
                        </label>
                        <input type="text" name="no_ktp" id="pub_ktp" inputmode="numeric" class="form-control" required value="<?= e($_POST['no_ktp'] ?? '') ?>" placeholder="Masukkan 16 angka NIK pada KTP">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tempat & Tanggal Lahir <span class="req-star">* Wajib</span></label>
                        <input type="text" name="ttl" class="form-control" required value="<?= e($_POST['ttl'] ?? '') ?>" placeholder="Contoh: Banjarmasin, 13-05-1998">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Alamat Lengkap Sesuai KTP <span class="req-star">* Wajib</span></label>
                        <textarea name="alamat" id="pub_alamat" class="form-control" required placeholder="Nama Jalan/Gang, Nomor, RT/RW, Kelurahan/Desa, Kecamatan, Kota/Kabupaten"><?= e($_POST['alamat'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- BAGIAN 2: OPSIONAL PELENGKAP -->
            <div class="section-card">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:6px;">
                    <h2 style="margin:0;font-size:15px;font-weight:700;color:#0f172a;">2. Kontak, Pekerjaan & Perumahan</h2>
                    <span class="opt-tag">Opsional — Bisa Dilengkapi Kapan Saja</span>
                </div>

                <div class="form-grid" style="gap:14px;">
                    <div class="form-group col-span-full">
                        <label class="form-label">Perumahan yang Diminati <span class="opt-tag">Opsional</span></label>
                        <select name="perumahan_id" class="form-control">
                            <option value="">-- Pilih Perumahan (Boleh dikosongkan jika belum memilih) --</option>
                            <?php foreach ($masterPerum as $mp): ?>
                                <option value="<?= (int)$mp['id'] ?>" <?= (int)($_POST['perumahan_id'] ?? 0) === (int)$mp['id'] ? 'selected' : '' ?>>
                                    <?= e($mp['nama_perumahan']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nomor HP / WhatsApp Aktif <span class="opt-tag">Opsional</span></label>
                        <input type="text" name="no_hp" inputmode="tel" class="form-control" value="<?= e($_POST['no_hp'] ?? '') ?>" placeholder="Contoh: 0813 5155 4071">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nomor NPWP <span class="opt-tag">Kosongkan jika sama dgn NIK</span></label>
                        <input type="text" name="npwp" class="form-control" value="<?= e($_POST['npwp'] ?? '') ?>" placeholder="Otomatis menggunakan NIK jika kosong">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Pekerjaan <span class="opt-tag">Opsional</span></label>
                        <input type="text" name="pekerjaan" class="form-control" value="<?= e($_POST['pekerjaan'] ?? '') ?>" placeholder="Contoh: Swasta / Wiraswasta">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Perkiraan Penghasilan / Bulan <span class="opt-tag">Opsional</span></label>
                        <input type="text" name="penghasilan" class="form-control" value="<?= e($_POST['penghasilan'] ?? '') ?>" placeholder="Contoh: Rp. 4.500.000">
                    </div>
                </div>
            </div>

            <!-- BAGIAN 3: PASANGAN OPSIONAL -->
            <div class="section-card">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:6px;">
                    <h2 style="margin:0;font-size:15px;font-weight:700;color:#0f172a;">3. Status Pernikahan & Pasangan</h2>
                    <span class="opt-tag">Opsional — Bisa Menyusul</span>
                </div>

                <div class="form-group" style="margin-bottom:12px;">
                    <label class="form-label">Status Pernikahan</label>
                    <select name="status_nikah" id="pub_status_nikah" class="form-control">
                        <option value="Lajang" <?= ($_POST['status_nikah'] ?? '') !== 'Menikah' ? 'selected' : '' ?>>Belum Menikah / Lajang</option>
                        <option value="Menikah" <?= ($_POST['status_nikah'] ?? '') === 'Menikah' ? 'selected' : '' ?>>Sudah Menikah (Isi Data Suami/Istri)</option>
                    </select>
                </div>

                <div id="pub_pasangan_box" style="display:<?= ($_POST['status_nikah'] ?? '') === 'Menikah' ? 'block' : 'none' ?>;padding-top:8px;border-top:1px solid #f1f5f9;">
                    <div class="form-grid" style="gap:14px;">
                        <div class="form-group">
                            <label class="form-label">Nama Lengkap Suami/Istri</label>
                            <input type="text" name="nama_pasangan" class="form-control" value="<?= e($_POST['nama_pasangan'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">NIK KTP Suami/Istri</label>
                            <input type="text" name="ktp_pasangan" inputmode="numeric" class="form-control" value="<?= e($_POST['ktp_pasangan'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tempat & Tanggal Lahir Pasangan</label>
                            <input type="text" name="ttl_pasangan" class="form-control" value="<?= e($_POST['ttl_pasangan'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Pekerjaan Pasangan</label>
                            <input type="text" name="pekerjaan_pasangan" class="form-control" value="<?= e($_POST['pekerjaan_pasangan'] ?? '') ?>">
                        </div>
                        <div class="form-group col-span-full">
                            <label class="form-label">
                                <span>Alamat KTP Pasangan</span>
                                <button type="button" onclick="document.getElementById('pub_alamat_pas').value = document.getElementById('pub_alamat').value;" class="btn btn-soft-blue btn-xs">Samakan dgn Alamat Saya</button>
                            </label>
                            <textarea name="alamat_pasangan" id="pub_alamat_pas" class="form-control"><?= e($_POST['alamat_pasangan'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BAGIAN 4: UPLOAD DOKUMEN OPSIONAL -->
            <div class="section-card">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:6px;">
                    <h2 style="margin:0;font-size:15px;font-weight:700;color:#0f172a;">4. Lampiran Foto & Lokasi Kerja</h2>
                    <span class="opt-tag">Opsional — Boleh Menyusul</span>
                </div>

                <div class="form-grid" style="gap:14px;">
                    <div class="form-group">
                        <label class="form-label">Foto KTP <span class="opt-tag">Opsional</span></label>
                        <input type="file" name="upload_ktp" accept="image/*,.pdf" class="form-control" style="font-size:12px;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Pas Foto <span class="opt-tag">Opsional</span></label>
                        <input type="file" name="upload_pas_foto" accept="image/*,.pdf" class="form-control" style="font-size:12px;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Foto Tempat Kerja / Usaha <span class="opt-tag">Opsional</span></label>
                        <input type="file" name="upload_foto_kerja" accept="image/*,.pdf" class="form-control" style="font-size:12px;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Link Google Maps Tempat Kerja <span class="opt-tag">Opsional</span></label>
                        <input type="text" name="share_lokasi_kerja" class="form-control" value="<?= e($_POST['share_lokasi_kerja'] ?? '') ?>" placeholder="Tempel link Maps jika ada">
                    </div>
                    <div class="form-group col-span-full">
                        <label class="form-label">Catatan Tambahan <span class="opt-tag">Opsional</span></label>
                        <input type="text" name="catatan_nasabah" class="form-control" value="<?= e($_POST['catatan_nasabah'] ?? '') ?>" placeholder="Misal: Rencana ambil blok dekat depan / lainnya">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;padding:14px;font-size:15px;border-radius:12px;">
                ✓ Kirim Pendaftaran Nasabah
            </button>
        </form>
    <?php endif; ?>
</div>

<script>
const ktpEl = document.getElementById('pub_ktp');
const cntEl = document.getElementById('pubNikCount');
if (ktpEl && cntEl) {
    ktpEl.addEventListener('input', function () {
        const d = this.value.replace(/\D/g, '').length;
        cntEl.textContent = d + '/16 digit';
        cntEl.style.color = d === 16 ? '#059669' : '#64748b';
    });
}
const stNikah = document.getElementById('pub_status_nikah');
const boxPas = document.getElementById('pub_pasangan_box');
if (stNikah && boxPas) {
    stNikah.addEventListener('change', function () {
        boxPas.style.display = this.value === 'Menikah' ? 'block' : 'none';
    });
}
</script>
</body>
</html>

