<?php
$pageTitle = 'Sinkronisasi Excel & Word Mail Merge';
$pageSubtitle = 'Ekspor ke format asli Lembar1 (Kolom A–BD) untuk Mail Merge Word, atau impor file Excel terbaru';

$totalDb = (int)$pdo->query("SELECT COUNT(*) FROM nasabah")->fetchColumn();

require __DIR__ . '/layout_header.php';
?>

<div class="card" style="background:linear-gradient(135deg,#ecfdf5 0%,#eff6ff 100%);border-color:#a7f3d0;">
    <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:16px 20px;">
        <div style="max-width:780px;">
            <div style="font-weight:800;font-size:14.5px;color:#065f46;margin-bottom:3px;">
                ✨ Pembuatan Otomatis Permohonan iDEB (SLIK OJK) Bank Syariah Nasional Aktif!
            </div>
            <div style="font-size:13px;color:#334155;">
                Untuk surat <strong>Permohonan iDEB (SLIK OJK)</strong>, Anda tidak perlu lagi melakukan Mail Merge manual di Word. Cukup klik tombol <strong>📥 SLIK (.docx)</strong> atau <strong>🖨️ Cetak</strong> pada baris nasabah (atau centang banyak nasabah sekaligus) untuk mengunduh file Word <code>.docx</code> siap pakai!
            </div>
        </div>
        <a href="index.php?page=nasabah" class="btn btn-emerald btn-sm">Buka Database Nasabah →</a>
    </div>
</div>

<div class="form-grid">
    <!-- EXPORT CARD -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">📥 Export ke Excel (.xlsx) untuk Cetak / Mail Merge</h3>
        </div>
        <div class="card-body">
            <p style="margin:0 0 14px;font-size:13.5px;color:#334155;">
                File yang dihasilkan memiliki nama sheet <strong><code>Lembar1</code></strong> dan urutan kolom <strong><code>A</code> sampai <code>BD</code> (56 kolom)</strong> yang <strong>100% sama persis</strong> dengan file <code>DATA_BASE_FLPP_UPDATE_27_Juli__2026.xlsm</code> Anda, dan seluruh NIK/KTP/HP otomatis diformat sebagai Teks agar tidak berubah menjadi rumus ilmiah (<code>6.37E+15</code>).
            </p>

            <div style="display:flex;flex-direction:column;gap:10px;">
                <a href="export.php" class="btn btn-primary" style="justify-content:flex-start;padding:12px 16px;">
                    📄 Download Seluruh Database Bersih (<?= $totalDb ?> Baris) — .xlsx
                </a>
                <a href="export.php?dots=1" class="btn btn-outline" style="justify-content:flex-start;padding:12px 16px;">
                    📝 Download Format Mail Merge (Isi Titik-Titik untuk BAST/Tanggal Kosong)
                </a>
            </div>

            <div style="margin-top:16px;padding:12px 14px;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0;font-size:12.5px;color:#475569;">
                <strong>Tips Cetak Cepat 1 Orang:</strong> Buka menu <a href="index.php?page=nasabah" style="color:#2563eb;font-weight:600;text-decoration:underline;">Database Nasabah</a>, centang 1 atau beberapa orang yang akan dicetak hari ini, lalu klik <strong>"Export Terpilih (.xlsx)"</strong>. Di Word Mail Merge Anda tidak perlu lagi pusing mencari baris ke-200!
            </div>
        </div>
    </div>

    <!-- IMPORT CARD -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">📤 Import File Excel (.xlsx / .xlsm)</h3>
        </div>
        <div class="card-body">
            <?php if (!isAdmin()): ?>
                <p style="color:#64748b;">Hanya akun Administrator yang dapat mengimpor file Excel.</p>
            <?php else: ?>
                <p style="margin:0 0 14px;font-size:13.5px;color:#334155;">
                    Unggah file Excel <code>.xlsm</code> atau <code>.xlsx</code> Anda. Sistem otomatis membersihkan baris kosong di bagian bawah, simbol <code>#NAME?</code>, serta tanda titik di depan NIK/No.HP.
                </p>

                <form method="post" action="index.php?page=do_import_excel" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                    <div class="form-group" style="margin-bottom:14px;">
                        <label class="form-label">Pilih File Excel (.xlsx atau .xlsm) <span class="req-star">*</span></label>
                        <input type="file" name="excel_file" accept=".xlsx,.xlsm" required class="form-control">
                    </div>

                    <div class="form-group" style="margin-bottom:16px;">
                        <label class="form-label">Mode Import</label>
                        <select name="import_mode" class="form-control">
                            <option value="append">Tambahkan ke bawah data yang sudah ada (Aman)</option>
                            <option value="replace">Ganti seluruh isi database dengan file Excel ini</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-emerald" onclick="return confirm('Lanjutkan proses import file Excel?');">
                        Mulai Import File Excel
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (isAdmin()): ?>
<!-- SMART AUTO-HEAL CARD -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">✨ Alat Bantu Rapikan Otomatis Data Lama</h3>
    </div>
    <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div style="max-width:760px;">
            <div style="font-weight:700;color:#0f172a;margin-bottom:4px;">Lengkapi Otomatis Kolom Direktur, Rekening & Alamat PT yang Kosong</div>
            <div style="font-size:13px;color:#475569;">
                Pada data manual sebelumnya, ada beberapa baris yang sudah tertulis nama Perumahan atau PT (misal <em>PT. Kalimantan Batara</em>), tetapi kolom Nama Direktur, NIK Direktur, Rekening PT, atau Alamat Direkturnya masih kosong. Klik tombol ini untuk mengisi kolom yang kosong tersebut secara otomatis sesuai Master PT & Perumahan (tanpa menimpa data yang sudah terisi).
            </div>
        </div>
        <form method="post" action="index.php?page=auto_heal_nasabah" style="margin:0;">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <button type="submit" class="btn btn-soft-blue">
                ⚡ Lengkapi Kolom PT/Direktur yang Kosong
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/layout_footer.php'; ?>

