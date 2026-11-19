<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$page = $_GET['page'] ?? (isLoggedIn() ? 'dashboard' : 'login');

/**
 * Helper to handle optional attachment upload safely
 */
function handleAttachmentUpload(string $inputName, string $existingPath = ''): string {
    if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] === UPLOAD_ERR_NO_FILE) {
        return $existingPath;
    }
    if ($_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
        return $existingPath;
    }
    $origName = (string)$_FILES[$inputName]['name'];
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
    if (!in_array($ext, $allowed, true)) {
        return $existingPath;
    }
    $uploadDir = __DIR__ . '/storage/uploads';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }
    $safeName = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $uploadDir . '/' . $safeName;
    if (move_uploaded_file($_FILES[$inputName]['tmp_name'], $dest)) {
        return 'storage/uploads/' . $safeName;
    }
    return $existingPath;
}

// ============================================================================
// AUTH ROUTES
// ============================================================================
if ($page === 'login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrf();
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $userRow = $stmt->fetch();

        if ($userRow && password_verify($password, $userRow['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id'        => (int)$userRow['id'],
                'username'  => $userRow['username'],
                'full_name' => $userRow['full_name'],
                'role'      => $userRow['role'],
            ];
            header('Location: index.php?page=dashboard');
            exit;
        }
        setFlash('error', 'Username atau password salah.');
    }
    if (isLoggedIn()) {
        header('Location: index.php?page=dashboard');
        exit;
    }
    require __DIR__ . '/views/login.php';
    exit;
}

if ($page === 'logout') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrf();
    }
    $_SESSION = [];
    session_destroy();
    header('Location: index.php?page=login');
    exit;
}

// Require login for all subsequent routes
requireLogin();

// ============================================================================
// POST ACTION ROUTES
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    switch ($page) {
        case 'save_nasabah':
            requireAdmin();
            $id = (int)($_POST['id'] ?? 0);
            $nama   = trim((string)($_POST['nama_lengkap'] ?? ''));
            $ktp    = trim((string)($_POST['no_ktp'] ?? ''));
            $ttl    = trim((string)($_POST['ttl'] ?? ''));
            $alamat = trim((string)($_POST['alamat'] ?? ''));

            if ($nama === '' || $ktp === '' || $ttl === '' || $alamat === '') {
                setFlash('error', 'Mohon lengkapi 4 Data Utama pendaftaran nasabah: Nama Lengkap, NIK (No. KTP), Tempat/Tanggal Lahir, dan Alamat. Kolom lainnya boleh dikosongkan dan dilengkapi kapan saja.');
                header('Location: index.php?page=nasabah_form' . ($id > 0 ? '&id=' . $id : ''));
                exit;
            }

            $existing = [];
            if ($id > 0) {
                $st = $pdo->prepare("SELECT * FROM nasabah WHERE id = ?");
                $st->execute([$id]);
                $existing = $st->fetch() ?: [];
            }

            $fileKtp  = handleAttachmentUpload('upload_file_ktp', (string)($existing['file_ktp'] ?? ''));
            $pasFoto  = handleAttachmentUpload('upload_pas_foto', (string)($existing['pas_foto'] ?? ''));
            $fotoKerja= handleAttachmentUpload('upload_foto_tempat_kerja', (string)($existing['foto_tempat_kerja'] ?? ''));
            $shareLok = trim((string)($_POST['share_lokasi_kerja'] ?? ''));

            $docKtp   = (!empty($_POST['doc_ktp_check']) || $fileKtp !== '') ? 1 : 0;
            $docPas   = (!empty($_POST['doc_pas_foto_check']) || $pasFoto !== '') ? 1 : 0;
            $docKerja = (!empty($_POST['doc_foto_kerja_check']) || $fotoKerja !== '') ? 1 : 0;
            $docLok   = (!empty($_POST['doc_lokasi_kerja_check']) || $shareLok !== '') ? 1 : 0;

            $textColumns = [
                'no_urut', 'no_rek_pt', 'tempat_tgl_surat', 'jabatan_direktur', 'nik_direktur',
                'alamat_direktur', 'penghasilan_pemohon', 'penghasilan_pasangan', 'nama_lengkap',
                'no_hp', 'no_ktp', 'npwp', 'ttl', 'pekerjaan', 'alamat', 'penghasilan',
                'harga_jual_rumah', 'uang_muka', 'sbum', 'uang_muka_nasabah', 'kpr', 'pengembang',
                'tempat_akad', 'tanggal_akad', 'tahun', 'no_bast', 'ppjb', 'tanggal_ppjb',
                'tanggal_serah_terima', 'nama_perumahan', 'no_rumah', 'luas_tanah', 'luas_lantai',
                'lokasi_perumahan', 'nama_direktur', 'nama_pengembang', 'besaran_uang_muka',
                'npwp_pasangan', 'ktp_pasangan', 'nama_pasangan', 'hp_pasangan', 'alamat_pasangan',
                'pekerjaan_pasangan', 'ttl_pasangan', 'nama_komplek', 'no_shgb', 'no_imb',
                'tgl_berkas', 'status_proses', 'catatan'
            ];

            $payload = [];
            foreach ($textColumns as $col) {
                $payload[$col] = trim((string)($_POST[$col] ?? ''));
            }
            if ($payload['npwp'] === '' && $payload['no_ktp'] !== '') {
                $payload['npwp'] = $payload['no_ktp'];
            }
            if ($payload['nama_komplek'] === '' && $payload['nama_perumahan'] !== '') {
                $payload['nama_komplek'] = $payload['nama_perumahan'];
            }
            if ($payload['nama_perumahan'] === '' && $payload['nama_komplek'] !== '') {
                $payload['nama_perumahan'] = $payload['nama_komplek'];
            }
            if ($payload['nama_pengembang'] === '' && $payload['pengembang'] !== '') {
                $payload['nama_pengembang'] = $payload['pengembang'];
            }
            // Auto-upgrade status to Sudah Akad / BAST if complete BAST number is entered
            if (getBastState($payload['no_bast']) === 'lengkap' && $payload['status_proses'] === 'Pemberkasan') {
                $payload['status_proses'] = 'Sudah Akad / BAST';
            }

            $payload['foto_tempat_kerja']  = $fotoKerja;
            $payload['share_lokasi_kerja'] = $shareLok;
            $payload['pas_foto']           = $pasFoto;
            $payload['file_ktp']           = $fileKtp;
            $payload['doc_ktp_check']      = $docKtp;
            $payload['doc_pas_foto_check'] = $docPas;
            $payload['doc_foto_kerja_check']  = $docKerja;
            $payload['doc_lokasi_kerja_check']= $docLok;

            $now = date('Y-m-d H:i:s');

            if ($id > 0) {
                $payload['updated_at'] = $now;
                $sets = [];
                $vals = [];
                foreach ($payload as $k => $v) {
                    $sets[] = "$k = ?";
                    $vals[] = $v;
                }
                $vals[] = $id;
                $sql = "UPDATE nasabah SET " . implode(', ', $sets) . " WHERE id = ?";
                $pdo->prepare($sql)->execute($vals);
                setFlash('success', "Data nasabah '{$payload['nama_lengkap']}' berhasil diperbarui.");
                if (!empty($_POST['save_and_ideb'])) {
                    header('Location: print.php?id=' . $id . '&tpl=ideb');
                } else {
                    header('Location: index.php?page=nasabah_detail&id=' . $id);
                }
                exit;
            } else {
                $payload['created_at'] = $now;
                $payload['updated_at'] = $now;
                $cols = array_keys($payload);
                $ph   = implode(', ', array_fill(0, count($cols), '?'));
                $sql  = "INSERT INTO nasabah (" . implode(', ', $cols) . ") VALUES ($ph)";
                $pdo->prepare($sql)->execute(array_values($payload));
                $newId = (int)$pdo->lastInsertId();

                setFlash('success', "Data nasabah baru '{$payload['nama_lengkap']}' berhasil ditambahkan.");
                if (!empty($_POST['save_and_ideb'])) {
                    header('Location: print.php?id=' . $newId . '&tpl=ideb');
                } elseif (!empty($_POST['save_and_new'])) {
                    header('Location: index.php?page=nasabah_form');
                } else {
                    header('Location: index.php?page=nasabah_detail&id=' . $newId);
                }
                exit;
            }

        case 'quick_update_bast':
            requireAdmin();
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $statusProses = trim((string)($_POST['status_proses'] ?? 'Pemberkasan'));
                $noBast       = trim((string)($_POST['no_bast'] ?? ''));
                $tglSerah     = trim((string)($_POST['tanggal_serah_terima'] ?? ''));
                if ((getBastState($noBast) === 'lengkap' || $tglSerah !== '') && $statusProses === 'Pemberkasan') {
                    $statusProses = 'Sudah Akad / BAST';
                }

                $stmt = $pdo->prepare("
                    UPDATE nasabah
                    SET status_proses = ?, no_rumah = ?, no_bast = ?, ppjb = ?,
                        tanggal_ppjb = ?, tanggal_serah_terima = ?, no_shgb = ?, no_imb = ?,
                        catatan = ?, updated_at = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $statusProses,
                    trim((string)($_POST['no_rumah'] ?? '')),
                    $noBast,
                    trim((string)($_POST['ppjb'] ?? '')),
                    trim((string)($_POST['tanggal_ppjb'] ?? '')),
                    $tglSerah,
                    trim((string)($_POST['no_shgb'] ?? '')),
                    trim((string)($_POST['no_imb'] ?? '')),
                    trim((string)($_POST['catatan'] ?? '')),
                    date('Y-m-d H:i:s'),
                    $id
                ]);
                setFlash('success', 'Data BAST / Penyuratan berhasil diperbarui.');
            }
            $ret = (string)($_POST['return_url'] ?? 'index.php?page=nasabah');
            if (!str_starts_with($ret, 'index.php') && !str_starts_with($ret, '/')) {
                $ret = 'index.php?page=nasabah';
            }
            header('Location: ' . $ret);
            exit;

        case 'delete_nasabah':
            requireAdmin();
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $pdo->prepare("DELETE FROM nasabah WHERE id = ?")->execute([$id]);
                setFlash('success', 'Data nasabah berhasil dihapus.');
            }
            header('Location: index.php?page=nasabah');
            exit;

        case 'save_master_pt':
            requireAdmin();
            $id = (int)($_POST['id'] ?? 0);
            $pengembang = trim((string)($_POST['pengembang'] ?? ''));
            $namaPengembang = trim((string)($_POST['nama_pengembang'] ?? '')) ?: $pengembang;
            $kodePt = strtoupper(trim((string)($_POST['kode_pt'] ?? '')));
            $namaDir = trim((string)($_POST['nama_direktur'] ?? ''));
            $jabDir = trim((string)($_POST['jabatan_direktur'] ?? 'Direktur'));
            $nikDir = trim((string)($_POST['nik_direktur'] ?? ''));
            $almDir = trim((string)($_POST['alamat_direktur'] ?? ''));
            $rekPt  = trim((string)($_POST['no_rek_pt'] ?? ''));

            if ($id > 0) {
                $pdo->prepare("
                    UPDATE master_pengembang
                    SET kode_pt=?, pengembang=?, nama_pengembang=?, nama_direktur=?, jabatan_direktur=?, nik_direktur=?, alamat_direktur=?, no_rek_pt=?
                    WHERE id=?
                ")->execute([$kodePt, $pengembang, $namaPengembang, $namaDir, $jabDir, $nikDir, $almDir, $rekPt, $id]);
                setFlash('success', 'Master PT Pengembang berhasil diperbarui.');
            } else {
                $pdo->prepare("
                    INSERT INTO master_pengembang (kode_pt, pengembang, nama_pengembang, nama_direktur, jabatan_direktur, nik_direktur, alamat_direktur, no_rek_pt)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ")->execute([$kodePt, $pengembang, $namaPengembang, $namaDir, $jabDir, $nikDir, $almDir, $rekPt]);
                setFlash('success', 'Master PT Pengembang baru berhasil ditambahkan.');
            }
            header('Location: index.php?page=master');
            exit;

        case 'delete_master_pt':
            requireAdmin();
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $pdo->prepare("DELETE FROM master_pengembang WHERE id = ?")->execute([$id]);
                setFlash('success', 'Master PT berhasil dihapus.');
            }
            header('Location: index.php?page=master');
            exit;

        case 'save_master_perum':
            requireAdmin();
            $id = (int)($_POST['id'] ?? 0);
            $ptId = (int)($_POST['pengembang_id'] ?? 0) ?: null;
            $namaPerum = trim((string)($_POST['nama_perumahan'] ?? ''));
            $namaKomp  = trim((string)($_POST['nama_komplek'] ?? '')) ?: $namaPerum;
            $lokPerum  = trim((string)($_POST['lokasi_perumahan'] ?? ''));
            $tempatAkad= trim((string)($_POST['tempat_akad'] ?? '')) ?: $lokPerum;
            $lt        = trim((string)($_POST['luas_tanah'] ?? ''));
            $lb        = trim((string)($_POST['luas_lantai'] ?? '36'));
            $kodeBast  = strtoupper(trim((string)($_POST['kode_bast'] ?? '')));

            if ($id > 0) {
                $pdo->prepare("
                    UPDATE master_perumahan
                    SET pengembang_id=?, nama_perumahan=?, nama_komplek=?, lokasi_perumahan=?, tempat_akad=?, luas_tanah=?, luas_lantai=?, kode_bast=?
                    WHERE id=?
                ")->execute([$ptId, $namaPerum, $namaKomp, $lokPerum, $tempatAkad, $lt, $lb, $kodeBast, $id]);
                setFlash('success', 'Master Perumahan berhasil diperbarui.');
            } else {
                $pdo->prepare("
                    INSERT INTO master_perumahan (pengembang_id, nama_perumahan, nama_komplek, lokasi_perumahan, tempat_akad, luas_tanah, luas_lantai, kode_bast)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ")->execute([$ptId, $namaPerum, $namaKomp, $lokPerum, $tempatAkad, $lt, $lb, $kodeBast]);
                setFlash('success', 'Master Perumahan baru berhasil ditambahkan.');
            }
            header('Location: index.php?page=master');
            exit;

        case 'delete_master_perum':
            requireAdmin();
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $pdo->prepare("DELETE FROM master_perumahan WHERE id = ?")->execute([$id]);
                setFlash('success', 'Master Perumahan berhasil dihapus.');
            }
            header('Location: index.php?page=master');
            exit;

        case 'save_settings':
            requireAdmin();
            $keys = [
                'default_harga_jual', 'default_uang_muka', 'default_sbum', 'default_um_nasabah',
                'default_kpr', 'default_luas_lantai', 'default_kota_surat', 'default_tahun', 'default_pekerjaan'
            ];
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON CONFLICT(setting_key) DO UPDATE SET setting_value = excluded.setting_value");
            foreach ($keys as $k) {
                $stmt->execute([$k, trim((string)($_POST[$k] ?? ''))]);
            }
            setFlash('success', 'Standar nilai otomatis KPR FLPP berhasil disimpan.');
            header('Location: index.php?page=master');
            exit;

        case 'do_import_excel':
            requireAdmin();
            if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
                setFlash('error', 'Gagal mengunggah file Excel.');
                header('Location: index.php?page=excel');
                exit;
            }
            $mode = ($_POST['import_mode'] ?? 'append') === 'replace';
            try {
                $parsed = ExcelService::parseWorkbook($_FILES['excel_file']['tmp_name']);
                $cnt = Database::importNasabahRows($pdo, $parsed, $mode);
                setFlash('success', "Berhasil mengimpor $cnt baris data nasabah dari file Excel.");
            } catch (Throwable $e) {
                setFlash('error', 'Gagal membaca file Excel: ' . $e->getMessage());
            }
            header('Location: index.php?page=excel');
            exit;

        case 'auto_heal_nasabah':
            requireAdmin();
            $masterPts = $pdo->query("SELECT * FROM master_pengembang")->fetchAll();
            $masterPerums = $pdo->query("
                SELECT mp.*, pt.pengembang AS pt_upper, pt.nama_pengembang AS pt_surat,
                       pt.nama_direktur, pt.jabatan_direktur, pt.nik_direktur, pt.alamat_direktur, pt.no_rek_pt
                FROM master_perumahan mp
                LEFT JOIN master_pengembang pt ON pt.id = mp.pengembang_id
            ")->fetchAll();

            $allRows = $pdo->query("SELECT * FROM nasabah")->fetchAll();
            $updatedCount = 0;

            $updStmt = $pdo->prepare("
                UPDATE nasabah
                SET pengembang=?, nama_pengembang=?, nama_direktur=?, jabatan_direktur=?,
                    nik_direktur=?, alamat_direktur=?, no_rek_pt=?, lokasi_perumahan=?, tempat_akad=?, updated_at=?
                WHERE id=?
            ");

            foreach ($allRows as $row) {
                $changed = false;
                $curPt     = trim($row['pengembang']);
                $curPtSrt  = trim($row['nama_pengembang']);
                $curDir    = trim($row['nama_direktur']);
                $curJab    = trim($row['jabatan_direktur']);
                $curNikDir = trim($row['nik_direktur']);
                $curAlmDir = trim($row['alamat_direktur']);
                $curRek    = trim($row['no_rek_pt']);
                $curLok    = trim($row['lokasi_perumahan']);
                $curTmp    = trim($row['tempat_akad']);
                $curPerum  = trim($row['nama_perumahan'] ?: $row['nama_komplek']);

                // Match by Perumahan first if available
                if ($curPerum !== '') {
                    foreach ($masterPerums as $mp) {
                        if (strcasecmp($curPerum, $mp['nama_perumahan']) === 0 || strcasecmp($curPerum, $mp['nama_komplek']) === 0) {
                            if ($curPt === '' && !empty($mp['pt_upper'])) { $curPt = $mp['pt_upper']; $changed = true; }
                            if ($curPtSrt === '' && !empty($mp['pt_surat'])) { $curPtSrt = $mp['pt_surat']; $changed = true; }
                            if ($curDir === '' && !empty($mp['nama_direktur'])) { $curDir = $mp['nama_direktur']; $changed = true; }
                            if ($curJab === '' && !empty($mp['jabatan_direktur'])) { $curJab = $mp['jabatan_direktur']; $changed = true; }
                            if ($curNikDir === '' && !empty($mp['nik_direktur'])) { $curNikDir = $mp['nik_direktur']; $changed = true; }
                            if ($curAlmDir === '' && !empty($mp['alamat_direktur'])) { $curAlmDir = $mp['alamat_direktur']; $changed = true; }
                            if ($curRek === '' && !empty($mp['no_rek_pt'])) { $curRek = $mp['no_rek_pt']; $changed = true; }
                            if ($curLok === '' && !empty($mp['lokasi_perumahan'])) { $curLok = $mp['lokasi_perumahan']; $changed = true; }
                            if ($curTmp === '' && !empty($mp['tempat_akad'])) { $curTmp = $mp['tempat_akad']; $changed = true; }
                            break;
                        }
                    }
                }

                // Match by PT name if still empty fields
                if ($curPt !== '') {
                    foreach ($masterPts as $pt) {
                        if (strcasecmp($curPt, $pt['pengembang']) === 0 || strcasecmp($curPt, $pt['nama_pengembang']) === 0) {
                            if ($curPtSrt === '') { $curPtSrt = $pt['nama_pengembang']; $changed = true; }
                            if ($curDir === '') { $curDir = $pt['nama_direktur']; $changed = true; }
                            if ($curJab === '') { $curJab = $pt['jabatan_direktur']; $changed = true; }
                            if ($curNikDir === '') { $curNikDir = $pt['nik_direktur']; $changed = true; }
                            if ($curAlmDir === '') { $curAlmDir = $pt['alamat_direktur']; $changed = true; }
                            if ($curRek === '') { $curRek = $pt['no_rek_pt']; $changed = true; }
                            break;
                        }
                    }
                }

                if ($changed) {
                    $updStmt->execute([$curPt, $curPtSrt, $curDir, $curJab, $curNikDir, $curAlmDir, $curRek, $curLok, $curTmp, date('Y-m-d H:i:s'), $row['id']]);
                    $updatedCount++;
                }
            }

            setFlash('success', "Selesai! Sebanyak $updatedCount baris nasabah berhasil dilengkapi kolom PT/Direktur/Lokasinya yang kosong.");
            header('Location: index.php?page=excel');
            exit;

        case 'change_password':
            $me = currentUser();
            $fullName = trim((string)($_POST['full_name'] ?? ''));
            $newPass  = (string)($_POST['new_password'] ?? '');
            if ($fullName === '') {
                setFlash('error', 'Nama tampilan tidak boleh kosong.');
                header('Location: index.php?page=users');
                exit;
            }
            if ($newPass !== '') {
                if (strlen($newPass) < 5) {
                    setFlash('error', 'Password baru minimal 5 karakter.');
                    header('Location: index.php?page=users');
                    exit;
                }
                $pdo->prepare("UPDATE users SET full_name = ?, password_hash = ? WHERE id = ?")
                    ->execute([$fullName, password_hash($newPass, PASSWORD_DEFAULT), $me['id']]);
            } else {
                $pdo->prepare("UPDATE users SET full_name = ? WHERE id = ?")
                    ->execute([$fullName, $me['id']]);
            }
            $_SESSION['user']['full_name'] = $fullName;
            setFlash('success', 'Profil & password Anda berhasil diperbarui.');
            header('Location: index.php?page=users');
            exit;

        case 'add_user':
            requireAdmin();
            $uname = trim((string)($_POST['username'] ?? ''));
            $pass  = (string)($_POST['password'] ?? '');
            $fname = trim((string)($_POST['full_name'] ?? ''));
            $role  = ($_POST['role'] ?? 'viewer') === 'admin' ? 'admin' : 'viewer';
            if ($uname === '' || strlen($pass) < 5 || $fname === '') {
                setFlash('error', 'Pastikan semua kolom terisi dan password minimal 5 karakter.');
                header('Location: index.php?page=users');
                exit;
            }
            try {
                $pdo->prepare("INSERT INTO users (username, password_hash, full_name, role, created_at) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$uname, password_hash($pass, PASSWORD_DEFAULT), $fname, $role, date('Y-m-d H:i:s')]);
                setFlash('success', "Akun '$uname' berhasil ditambahkan.");
            } catch (Throwable $e) {
                setFlash('error', "Username '$uname' sudah digunakan.");
            }
            header('Location: index.php?page=users');
            exit;

        case 'delete_user':
            requireAdmin();
            $id = (int)($_POST['id'] ?? 0);
            $me = currentUser();
            if ($id > 0 && $id !== (int)($me['id'] ?? 0)) {
                $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
                setFlash('success', 'Akun pengguna berhasil dihapus.');
            }
            header('Location: index.php?page=users');
            exit;

        case 'approve_pengajuan':
            requireAdmin();
            $pId = (int)($_POST['id'] ?? 0);
            $stP = $pdo->prepare("SELECT * FROM pengajuan_nasabah WHERE id = ? AND status_pengajuan = 'pending'");
            $stP->execute([$pId]);
            $pRow = $stP->fetch();
            if (!$pRow) {
                setFlash('error', 'Data pengajuan tidak ditemukan atau sudah diproses.');
                header('Location: index.php?page=pengajuan');
                exit;
            }

            $settings = Database::getSettings($pdo);
            $kotaDef  = $settings['default_kota_surat'] ?? 'Banjar';
            $maxNo    = (int)$pdo->query("SELECT COALESCE(MAX(CAST(no_urut AS INTEGER)), 0) FROM nasabah")->fetchColumn();

            $auto = [
                'nama_perumahan'   => $pRow['nama_perumahan'],
                'nama_komplek'     => $pRow['nama_perumahan'],
                'lokasi_perumahan' => '',
                'tempat_akad'      => '',
                'luas_tanah'       => '',
                'luas_lantai'      => $settings['default_luas_lantai'] ?? '36',
                'pengembang'       => '',
                'nama_pengembang'  => '',
                'nama_direktur'    => '',
                'jabatan_direktur' => 'Direktur',
                'nik_direktur'     => '',
                'alamat_direktur'  => '',
                'no_rek_pt'        => '',
            ];

            if (!empty($pRow['perumahan_id'])) {
                $stMp = $pdo->prepare("
                    SELECT mp.*, pt.pengembang AS pt_upper, pt.nama_pengembang AS pt_surat,
                           pt.nama_direktur, pt.jabatan_direktur, pt.nik_direktur, pt.alamat_direktur, pt.no_rek_pt
                    FROM master_perumahan mp
                    LEFT JOIN master_pengembang pt ON pt.id = mp.pengembang_id
                    WHERE mp.id = ?
                ");
                $stMp->execute([(int)$pRow['perumahan_id']]);
                $mp = $stMp->fetch();
                if ($mp) {
                    $auto['nama_perumahan']   = $mp['nama_perumahan'];
                    $auto['nama_komplek']     = $mp['nama_komplek'] ?: $mp['nama_perumahan'];
                    $auto['lokasi_perumahan'] = $mp['lokasi_perumahan'];
                    $auto['tempat_akad']      = $mp['tempat_akad'] ?: $mp['lokasi_perumahan'];
                    $auto['luas_tanah']       = $mp['luas_tanah'];
                    $auto['luas_lantai']      = $mp['luas_lantai'] ?: ($settings['default_luas_lantai'] ?? '36');
                    $auto['pengembang']       = (string)($mp['pt_upper'] ?? '');
                    $auto['nama_pengembang']  = (string)($mp['pt_surat'] ?? '');
                    $auto['nama_direktur']    = (string)($mp['nama_direktur'] ?? '');
                    $auto['jabatan_direktur'] = (string)($mp['jabatan_direktur'] ?? 'Direktur');
                    $auto['nik_direktur']     = (string)($mp['nik_direktur'] ?? '');
                    $auto['alamat_direktur']  = (string)($mp['alamat_direktur'] ?? '');
                    $auto['no_rek_pt']        = (string)($mp['no_rek_pt'] ?? '');
                }
            }

            $now = date('Y-m-d H:i:s');
            $ins = $pdo->prepare("
                INSERT INTO nasabah (
                    no_urut, nama_lengkap, no_ktp, npwp, ttl, alamat,
                    no_hp, pekerjaan, penghasilan, penghasilan_pemohon,
                    nama_pasangan, ktp_pasangan, npwp_pasangan, ttl_pasangan, pekerjaan_pasangan, hp_pasangan, alamat_pasangan,
                    nama_perumahan, nama_komplek, lokasi_perumahan, tempat_akad, luas_tanah, luas_lantai,
                    pengembang, nama_pengembang, nama_direktur, jabatan_direktur, nik_direktur, alamat_direktur, no_rek_pt,
                    harga_jual_rumah, uang_muka, besaran_uang_muka, sbum, uang_muka_nasabah, kpr,
                    tempat_tgl_surat, tgl_berkas, tahun,
                    file_ktp, pas_foto, foto_tempat_kerja, share_lokasi_kerja,
                    doc_ktp_check, doc_pas_foto_check, doc_foto_kerja_check, doc_lokasi_kerja_check,
                    status_proses, catatan, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?
                )
            ");

            $ins->execute([
                (string)($maxNo + 1),
                $pRow['nama_lengkap'],
                $pRow['no_ktp'],
                $pRow['npwp'] ?: $pRow['no_ktp'],
                $pRow['ttl'],
                $pRow['alamat'],
                $pRow['no_hp'],
                $pRow['pekerjaan'] ?: ($settings['default_pekerjaan'] ?? 'Swasta'),
                $pRow['penghasilan'],
                $pRow['penghasilan'],
                $pRow['nama_pasangan'],
                $pRow['ktp_pasangan'],
                $pRow['ktp_pasangan'],
                $pRow['ttl_pasangan'],
                $pRow['pekerjaan_pasangan'],
                $pRow['hp_pasangan'],
                $pRow['alamat_pasangan'],
                $auto['nama_perumahan'],
                $auto['nama_komplek'],
                $auto['lokasi_perumahan'],
                $auto['tempat_akad'],
                $auto['luas_tanah'],
                $auto['luas_lantai'],
                $auto['pengembang'],
                $auto['nama_pengembang'],
                $auto['nama_direktur'],
                $auto['jabatan_direktur'],
                $auto['nik_direktur'],
                $auto['alamat_direktur'],
                $auto['no_rek_pt'],
                $settings['default_harga_jual'] ?? 'Rp. 182.000.000',
                $settings['default_uang_muka'] ?? 'Rp. 6.000.000',
                $settings['default_uang_muka'] ?? 'Rp. 6.000.000',
                $settings['default_sbum'] ?? 'Rp. 4.000.000',
                $settings['default_um_nasabah'] ?? 'Rp. 2.000.000',
                $settings['default_kpr'] ?? 'Rp. 176.000.000',
                tanggalIndoSekarang($kotaDef),
                tanggalIndoSekarang($kotaDef),
                $settings['default_tahun'] ?? date('Y'),
                $pRow['file_ktp'],
                $pRow['pas_foto'],
                $pRow['foto_tempat_kerja'],
                $pRow['share_lokasi_kerja'],
                $pRow['file_ktp'] !== '' ? 1 : 0,
                $pRow['pas_foto'] !== '' ? 1 : 0,
                $pRow['foto_tempat_kerja'] !== '' ? 1 : 0,
                $pRow['share_lokasi_kerja'] !== '' ? 1 : 0,
                $auto['nama_perumahan'] !== '' ? 'Proses Bank' : 'Pemberkasan',
                $pRow['catatan_nasabah'],
                $now,
                $now,
            ]);

            $newNasabahId = (int)$pdo->lastInsertId();
            $pdo->prepare("UPDATE pengajuan_nasabah SET status_pengajuan = 'approved' WHERE id = ?")->execute([$pId]);

            setFlash('success', "Pengajuan '{$pRow['nama_lengkap']}' telah disetujui dan masuk ke Database Nasabah. Data lainnya dapat dilengkapi kapan saja.");
            header('Location: index.php?page=nasabah_detail&id=' . $newNasabahId);
            exit;

        case 'delete_pengajuan':
            requireAdmin();
            $pId = (int)($_POST['id'] ?? 0);
            if ($pId > 0) {
                $pdo->prepare("DELETE FROM pengajuan_nasabah WHERE id = ?")->execute([$pId]);
                setFlash('success', 'Pengajuan berhasil dihapus.');
            }
            header('Location: index.php?page=pengajuan');
            exit;
    }
}

// ============================================================================
// GET VIEW ROUTES
// ============================================================================
switch ($page) {
    case 'dashboard':
        require __DIR__ . '/views/dashboard.php';
        break;
    case 'nasabah':
        require __DIR__ . '/views/nasabah_list.php';
        break;
    case 'nasabah_form':
        require __DIR__ . '/views/nasabah_form.php';
        break;
    case 'nasabah_detail':
        require __DIR__ . '/views/nasabah_detail.php';
        break;
    case 'pengajuan':
        require __DIR__ . '/views/pengajuan_list.php';
        break;
    case 'surat':
        require __DIR__ . '/views/surat_maker.php';
        break;
    case 'master':
        require __DIR__ . '/views/master_data.php';
        break;
    case 'excel':
        require __DIR__ . '/views/import_export.php';
        break;
    case 'users':
        require __DIR__ . '/views/users.php';
        break;
    default:
        require __DIR__ . '/views/dashboard.php';
        break;
}

