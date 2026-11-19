<?php
declare(strict_types=1);

class Database {
    private static ?PDO $pdo = null;

    public static function getConnection(): PDO {
        if (self::$pdo !== null) {
            return self::$pdo;
        }
        $storageDir = dirname(__DIR__) . '/storage';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0775, true);
        }
        $dbPath = $storageDir . '/database.sqlite';
        $isNew = !file_exists($dbPath);

        $pdo = new PDO('sqlite:' . $dbPath, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA journal_mode = WAL;');
        $pdo->exec('PRAGMA foreign_keys = ON;');

        self::$pdo = $pdo;
        self::migrate($pdo);

        if ($isNew || (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() === 0) {
            self::seedDefaults($pdo);
        }

        // Auto-import initial Excel on first setup if nasabah table is empty
        if ((int)$pdo->query("SELECT COUNT(*) FROM nasabah")->fetchColumn() === 0) {
            $initialExcel = $storageDir . '/source_initial.xlsm';
            if (file_exists($initialExcel)) {
                try {
                    $rows = ExcelService::parseWorkbook($initialExcel);
                    self::importNasabahRows($pdo, $rows, false);
                } catch (Throwable $e) {
                    // Fail gracefully without crashing app startup
                }
            }
        }

        return $pdo;
    }

    private static function migrate(PDO $pdo): void {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                full_name TEXT NOT NULL,
                role TEXT NOT NULL DEFAULT 'admin',
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS settings (
                setting_key TEXT PRIMARY KEY,
                setting_value TEXT NOT NULL DEFAULT ''
            );

            CREATE TABLE IF NOT EXISTS master_pengembang (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                kode_pt TEXT NOT NULL DEFAULT '',
                pengembang TEXT NOT NULL,
                nama_pengembang TEXT NOT NULL DEFAULT '',
                nama_direktur TEXT NOT NULL DEFAULT '',
                jabatan_direktur TEXT NOT NULL DEFAULT 'Direktur',
                nik_direktur TEXT NOT NULL DEFAULT '',
                alamat_direktur TEXT NOT NULL DEFAULT '',
                no_rek_pt TEXT NOT NULL DEFAULT ''
            );

            CREATE TABLE IF NOT EXISTS master_perumahan (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                pengembang_id INTEGER NULL,
                nama_perumahan TEXT NOT NULL,
                nama_komplek TEXT NOT NULL DEFAULT '',
                lokasi_perumahan TEXT NOT NULL DEFAULT '',
                tempat_akad TEXT NOT NULL DEFAULT '',
                luas_tanah TEXT NOT NULL DEFAULT '',
                luas_lantai TEXT NOT NULL DEFAULT '36',
                kode_bast TEXT NOT NULL DEFAULT '',
                FOREIGN KEY (pengembang_id) REFERENCES master_pengembang(id) ON DELETE SET NULL
            );

            CREATE TABLE IF NOT EXISTS nasabah (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                no_urut TEXT NOT NULL DEFAULT '',
                no_rek_pt TEXT NOT NULL DEFAULT '',
                tempat_tgl_surat TEXT NOT NULL DEFAULT '',
                jabatan_direktur TEXT NOT NULL DEFAULT '',
                nik_direktur TEXT NOT NULL DEFAULT '',
                alamat_direktur TEXT NOT NULL DEFAULT '',
                penghasilan_pemohon TEXT NOT NULL DEFAULT '',
                penghasilan_pasangan TEXT NOT NULL DEFAULT '',
                nama_lengkap TEXT NOT NULL DEFAULT '',
                no_hp TEXT NOT NULL DEFAULT '',
                no_ktp TEXT NOT NULL DEFAULT '',
                npwp TEXT NOT NULL DEFAULT '',
                ttl TEXT NOT NULL DEFAULT '',
                pekerjaan TEXT NOT NULL DEFAULT '',
                alamat TEXT NOT NULL DEFAULT '',
                penghasilan TEXT NOT NULL DEFAULT '',
                harga_jual_rumah TEXT NOT NULL DEFAULT '',
                uang_muka TEXT NOT NULL DEFAULT '',
                sbum TEXT NOT NULL DEFAULT '',
                uang_muka_nasabah TEXT NOT NULL DEFAULT '',
                kpr TEXT NOT NULL DEFAULT '',
                pengembang TEXT NOT NULL DEFAULT '',
                tempat_akad TEXT NOT NULL DEFAULT '',
                tanggal_akad TEXT NOT NULL DEFAULT '',
                tahun TEXT NOT NULL DEFAULT '',
                no_bast TEXT NOT NULL DEFAULT '',
                ppjb TEXT NOT NULL DEFAULT '',
                tanggal_ppjb TEXT NOT NULL DEFAULT '',
                tanggal_serah_terima TEXT NOT NULL DEFAULT '',
                nama_perumahan TEXT NOT NULL DEFAULT '',
                no_rumah TEXT NOT NULL DEFAULT '',
                luas_tanah TEXT NOT NULL DEFAULT '',
                luas_lantai TEXT NOT NULL DEFAULT '',
                lokasi_perumahan TEXT NOT NULL DEFAULT '',
                nama_direktur TEXT NOT NULL DEFAULT '',
                nama_pengembang TEXT NOT NULL DEFAULT '',
                besaran_uang_muka TEXT NOT NULL DEFAULT '',
                npwp_pasangan TEXT NOT NULL DEFAULT '',
                ktp_pasangan TEXT NOT NULL DEFAULT '',
                nama_pasangan TEXT NOT NULL DEFAULT '',
                hp_pasangan TEXT NOT NULL DEFAULT '',
                alamat_pasangan TEXT NOT NULL DEFAULT '',
                pekerjaan_pasangan TEXT NOT NULL DEFAULT '',
                ttl_pasangan TEXT NOT NULL DEFAULT '',
                nama_komplek TEXT NOT NULL DEFAULT '',
                no_shgb TEXT NOT NULL DEFAULT '',
                no_imb TEXT NOT NULL DEFAULT '',
                tgl_berkas TEXT NOT NULL DEFAULT '',
                foto_tempat_kerja TEXT NOT NULL DEFAULT '',
                share_lokasi_kerja TEXT NOT NULL DEFAULT '',
                pas_foto TEXT NOT NULL DEFAULT '',
                file_ktp TEXT NOT NULL DEFAULT '',
                doc_ktp_check INTEGER NOT NULL DEFAULT 0,
                doc_pas_foto_check INTEGER NOT NULL DEFAULT 0,
                doc_foto_kerja_check INTEGER NOT NULL DEFAULT 0,
                doc_lokasi_kerja_check INTEGER NOT NULL DEFAULT 0,
                status_proses TEXT NOT NULL DEFAULT 'Pemberkasan',
                catatan TEXT NOT NULL DEFAULT '',
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );

            CREATE INDEX IF NOT EXISTS idx_nasabah_nama ON nasabah(nama_lengkap);
            CREATE INDEX IF NOT EXISTS idx_nasabah_ktp ON nasabah(no_ktp);
            CREATE INDEX IF NOT EXISTS idx_nasabah_perumahan ON nasabah(nama_perumahan);
            CREATE INDEX IF NOT EXISTS idx_nasabah_pengembang ON nasabah(pengembang);
            CREATE INDEX IF NOT EXISTS idx_nasabah_status ON nasabah(status_proses);

            CREATE TABLE IF NOT EXISTS pengajuan_nasabah (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama_lengkap TEXT NOT NULL DEFAULT '',
                no_ktp TEXT NOT NULL DEFAULT '',
                npwp TEXT NOT NULL DEFAULT '',
                ttl TEXT NOT NULL DEFAULT '',
                alamat TEXT NOT NULL DEFAULT '',
                no_hp TEXT NOT NULL DEFAULT '',
                pekerjaan TEXT NOT NULL DEFAULT '',
                penghasilan TEXT NOT NULL DEFAULT '',
                perumahan_id INTEGER NULL,
                nama_perumahan TEXT NOT NULL DEFAULT '',
                status_nikah TEXT NOT NULL DEFAULT 'Lajang',
                nama_pasangan TEXT NOT NULL DEFAULT '',
                ktp_pasangan TEXT NOT NULL DEFAULT '',
                ttl_pasangan TEXT NOT NULL DEFAULT '',
                pekerjaan_pasangan TEXT NOT NULL DEFAULT '',
                hp_pasangan TEXT NOT NULL DEFAULT '',
                alamat_pasangan TEXT NOT NULL DEFAULT '',
                file_ktp TEXT NOT NULL DEFAULT '',
                pas_foto TEXT NOT NULL DEFAULT '',
                foto_tempat_kerja TEXT NOT NULL DEFAULT '',
                share_lokasi_kerja TEXT NOT NULL DEFAULT '',
                catatan_nasabah TEXT NOT NULL DEFAULT '',
                status_pengajuan TEXT NOT NULL DEFAULT 'pending',
                created_at TEXT NOT NULL
            );
        ");
    }

    private static function seedDefaults(PDO $pdo): void {
        $now = date('Y-m-d H:i:s');

        // 1. Default Users (Admin & Read-Only Viewer)
        $stmtUser = $pdo->prepare("INSERT OR IGNORE INTO users (username, password_hash, full_name, role, created_at) VALUES (?, ?, ?, ?, ?)");
        $stmtUser->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT), 'Admin Pemberkasan FLPP', 'admin', $now]);
        $stmtUser->execute(['atasan', password_hash('atasan123', PASSWORD_DEFAULT), 'Pimpinan / Monitoring (View Only)', 'viewer', $now]);

        // 2. Default Global Settings (Same values auto-filled on new entry)
        $defaults = [
            'default_harga_jual'  => 'Rp. 182.000.000',
            'default_uang_muka'   => 'Rp. 6.000.000',
            'default_sbum'        => 'Rp. 4.000.000',
            'default_um_nasabah'  => 'Rp. 2.000.000',
            'default_kpr'         => 'Rp. 176.000.000',
            'default_luas_lantai' => '36',
            'default_kota_surat'  => 'Banjar',
            'default_tahun'       => date('Y'),
            'default_pekerjaan'   => 'Swasta',
        ];
        $stmtSet = $pdo->prepare("INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
        foreach ($defaults as $k => $v) {
            $stmtSet->execute([$k, $v]);
        }

        // 3. Seed Master Pengembang (PT + Direktur + NIK + Alamat + Rekening) from actual Excel data
        if ((int)$pdo->query("SELECT COUNT(*) FROM master_pengembang")->fetchColumn() === 0) {
            $stmtPt = $pdo->prepare("
                INSERT INTO master_pengembang
                (kode_pt, pengembang, nama_pengembang, nama_direktur, jabatan_direktur, nik_direktur, alamat_direktur, no_rek_pt)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            // PT 1: PT. KALIMANTAN BATARA -> Aries Setiyo Nugroho, S.Kom
            $stmtPt->execute([
                'KB2',
                'PT. KALIMANTAN BATARA',
                'PT. Kalimantan Batara',
                'Aries Setiyo Nugroho, S.Kom',
                'Direktur',
                '6304020204910001',
                'Jl. Pemanjatan Komplek Rumbia Mas 2 Jalur 6 No. , RT.007, RW.003, Kel. Gambut, Kec. Gambut',
                '7151001467'
            ]);
            $idBatara = (int)$pdo->lastInsertId();

            // PT 2: PT. SINAR DINAR JAYA -> M. Nurruddin Muchtar
            $stmtPt->execute([
                'SDJ',
                'PT. SINAR DINAR JAYA',
                'PT. Sinar Dinar Jaya',
                'M. Nurruddin Muchtar',
                'Direktur',
                '6303031204730003',
                'Jl. Pemanjatan Komplek Dinar mas 2, RT.009, RW.000, Kelurahan Gambut, Kecamatan Gambut, Banjar.',
                '7151002928'
            ]);
            $idSinar = (int)$pdo->lastInsertId();

            // PT 3: PT. SUMBER BERKAH NUR -> Fathurrahman
            $stmtPt->execute([
                'SBN',
                'PT. SUMBER BERKAH NUR',
                'PT. Sumber Berkah Nur',
                'Fathurrahman',
                'Direktur',
                '6303031204730003',
                'Jl. Pemanjatan Komplek Rumbia Mas 2 Jalur 6 No. , RT.007, RW.003, Kel. Gambut, Kec. Gambut',
                '7151001467'
            ]);
            $idSumber = (int)$pdo->lastInsertId();

            // 4. Seed Master Perumahan linked to Master Pengembang
            $stmtPerum = $pdo->prepare("
                INSERT INTO master_perumahan
                (pengembang_id, nama_perumahan, nama_komplek, lokasi_perumahan, tempat_akad, luas_tanah, luas_lantai, kode_bast)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $perumahanList = [
                // Kalimantan Batara
                [$idBatara, 'Komplek Sapta Marga Permai', 'Komplek Sapta Marga Permai', 'Jl. Gubernur Syarkawi, Abumbun Jaya, Sungai Tabuk, Kabupaten Banjar', 'Jl. Gubernur Syarkawi, Abumbun Jaya, Sungai Tabuk, Kabupaten Banjar', '98m2', '36', 'KB2'],
                [$idBatara, 'Komplek Kalimantan Batara 2', 'Komplek Kalimantan Batara 2', 'Jl. Martapura Lama Km.10,500, Kelurahan Gudang Hirang, Kec.Sungai Tabuk, Kab. Banjar', 'Jl. Martapura Lama Km.10,500, Kelurahan Gudang Hirang, Kec.Sungai Tabuk, Kab. Banjar', '98m2', '36', 'KB2'],

                // Sinar Dinar Jaya
                [$idSinar, 'Komplek Permata Dinar', 'Komplek Permata Dinar', 'Komplek Permata Dinar, Jl. Pemanjatan, Gambut Baru, Gambut, Banjar.', 'Jl. Pemanjatan Km.1,5 RT.07 Pondok Rumbia Mas 1 (Ruko) Kel. Gambut.', '120', '36', 'SDJ'],
                [$idSinar, 'Komplek Permata Dinar Tahap 4', 'Komplek Permata Dinar Tahap 4', 'Komplek Permata Dinar, Jl. Pemanjatan, Gambut Baru, Gambut, Banjar.', 'Jl. Pemanjatan Km.1,5 RT.07 Pondok Rumbia Mas 1 (Ruko) Kel. Gambut.', '120', '36', 'SDJ'],
                [$idSinar, 'Komplek Dinar Mas 3', 'Komplek Dinar Mas 3', 'Jl. Pemanjatan Km.1,7, Komp. Dinar Mas 3', 'Jl. Pemanjatan Km.1,5 RT.07 Pondok Rumbia Mas 1 (Ruko) Kel. Gambut.', '120m2', '36', 'SDJ'],
                [$idSinar, 'Komplek Dinar Mas 3 Tahap 2', 'Komplek Dinar Mas 3 Tahap 2', 'Komplek Dinar Mas 3, Tahap 2, Jl. Pemanjatan, Gambut Baru, Gambut, Banjar.', 'Jl. Pemanjatan Km.1,5 RT.07 Pondok Rumbia Mas 1 (Ruko) Kel. Gambut.', '120', '36', 'SDJ'],
                [$idSinar, 'Komp.Golden Golf Residence', 'Komplek Golden Golf Residence', 'Jl. Golf Gg. Hijrah RT.13 Rw.003 Banjarbaru', 'Jl. Golf Gg. Hijrah RT.13 Rw.003 Banjarbaru', '140M2', '36', 'SDJ'],
                [$idSinar, 'PONDOK RUMBIAS MAS 2 TAHAP 4', 'KOMPLEK PONDOK RUMBIA MAS 2 TAHAP 4', 'Jl. Pemanjatan Km.1,7, Komp. Pondok Rumbia Mas 2 Tahap 4', 'Jl. Pemanjatan Km.1,5 RT.07 Pondok Rumbia Mas 1 (Ruko) Kel. Gambut.', '120m2', '36', 'SDJ'],
                [$idSinar, 'Komplek Pondok Pinang Raya', 'Komplek Pondok Pinang Raya', 'Jl. Pemanjatan Km.1,7, Komp. Dinar Mas 3', 'Jl. Pemanjatan Km.1,5 RT.07 Pondok Rumbia Mas 1 (Ruko) Kel. Gambut.', '120m2', '36', 'SDJ'],

                // Sumber Berkah Nur
                [$idSumber, 'Komp.Berkah Al-Fatih Residence 2', 'Komp.Berkah Al-Fatih Residence 2', 'Jl. Tatah Pemangkih Laut RT.002, Desa Tatah Pemangkih Laut, Kec.Kertak Hanyar, Banjar', 'Jl. Tatah Pemangkih Laut RT.002, Desa Tatah Pemangkih Laut, Kec.Kertak Hanyar, Banjar', '100m2', '36', 'SBN'],
                [$idSumber, 'Komp.Berkah Al-Fatih Residence, Tahap 3', 'Komp.Berkah Al-Fatih Residence, Tahap 3', 'Jl. Tatah Pemangkih Laut RT.002, Desa Tatah Pemangkih Laut, Kec.Kertak Hanyar, Banjar', 'Jl. Tatah Pemangkih Laut RT.002, Desa Tatah Pemangkih Laut, Kec.Kertak Hanyar, Banjar', '100m2', '36', 'SBN'],
            ];
            foreach ($perumahanList as $p) {
                $stmtPerum->execute($p);
            }
        }
    }

    public static function getSettings(PDO $pdo): array {
        $rows = $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
        $out = [];
        foreach ($rows as $r) {
            $out[$r['setting_key']] = $r['setting_value'];
        }
        return $out;
    }

    /**
     * Bulk insert nasabah rows from ExcelService::parseWorkbook
     */
    public static function importNasabahRows(PDO $pdo, array $rows, bool $replaceAll = false): int {
        $fields = [];
        foreach (ExcelService::COLUMNS as $meta) {
            if ($meta['field'] !== null) {
                $fields[] = $meta['field'];
            }
        }
        $extraFields = ['doc_ktp_check', 'doc_pas_foto_check', 'doc_foto_kerja_check', 'doc_lokasi_kerja_check', 'status_proses', 'catatan', 'created_at', 'updated_at'];
        $allFields = array_merge($fields, $extraFields);

        $placeholders = implode(', ', array_fill(0, count($allFields), '?'));
        $colNames = implode(', ', $allFields);

        $pdo->beginTransaction();
        try {
            if ($replaceAll) {
                $pdo->exec("DELETE FROM nasabah");
            }
            $stmt = $pdo->prepare("INSERT INTO nasabah ($colNames) VALUES ($placeholders)");
            $now = date('Y-m-d H:i:s');
            $count = 0;

            foreach ($rows as $idx => $r) {
                // If nama_perumahan is empty but nama_komplek is filled, sync it gently for easier filtering
                if (empty($r['nama_perumahan']) && !empty($r['nama_komplek'])) {
                    $r['nama_perumahan'] = $r['nama_komplek'];
                }

                $vals = [];
                foreach ($fields as $f) {
                    if ($f === 'no_urut' && empty($r[$f])) {
                        $vals[] = (string)($idx + 1);
                    } else {
                        $vals[] = (string)($r[$f] ?? '');
                    }
                }
                $vals[] = (int)($r['doc_ktp_check'] ?? 0);
                $vals[] = (int)($r['doc_pas_foto_check'] ?? 0);
                $vals[] = (int)($r['doc_foto_kerja_check'] ?? 0);
                $vals[] = (int)($r['doc_lokasi_kerja_check'] ?? 0);
                $vals[] = (string)($r['status_proses'] ?? 'Pemberkasan');
                $vals[] = (string)($r['catatan'] ?? '');
                $vals[] = $now;
                $vals[] = $now;

                $stmt->execute($vals);
                $count++;
            }

            $pdo->commit();
            return $count;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}

