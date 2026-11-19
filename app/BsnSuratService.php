<?php
declare(strict_types=1);

/**
 * Layanan Pembuatan 18 Dokumen / Surat Resmi KPR Bersubsidi Bank Syariah Nasional (BSN KPR Sejahtera / FLPP)
 * berbasis pemotongan node asli (OOXML slicing) dari template bundel Word `template_bundel_bsn.docx`
 * serta perenderan pratinjau HTML/PDF ukuran A4 siap cetak.
 */
class BsnSuratService {
    private const W_NS    = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
    private const WP_NS   = 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing';
    private const PIC_NS  = 'http://schemas.openxmlformats.org/drawingml/2006/picture';
    private const WP14_NS = 'http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing';
    private const WPS_NS  = 'http://schemas.microsoft.com/office/word/2010/wordprocessingShape';
    private const MC_NS   = 'http://schemas.openxmlformats.org/markup-compatibility/2006';
    private const V_NS    = 'urn:schemas-microsoft-com:vml';
    private const XML_NS  = 'http://www.w3.org/XML/1998/namespace';

    /**
     * Katalog lengkap seluruh 18 dokumen/lembar resmi di dalam FLPP_BSN.docx
     * diurutkan sesuai urutan halaman asli bundel bank.
     */
    public const DOCUMENTS = [
        'checklist_bsn' => [
            'key'               => 'checklist_bsn',
            'num'               => 1,
            'code'              => '01',
            'title'             => 'Checklist Kelengkapan Dokumen KPR Bersubsidi BSN',
            'short_title'       => 'Checklist Dokumen BSN',
            'category'          => 'Analisa & Lapangan',
            'description'       => 'Lembar kendali kelengkapan dokumen A–H (Check by FS & FA) beserta kolom paraf pejabat bank.',
            'start'             => 2,
            'end'               => 6,
            'skip_nodes'        => [],
            'sect_type'         => 'tight',
            'date_in_tglberkas' => false,
            'is_conditional'    => false,
            'default_checked'   => true,
        ],
        'persetujuan_flpp' => [
            'key'               => 'persetujuan_flpp',
            'num'               => 2,
            'code'              => '02',
            'title'             => 'Surat Pernyataan Persetujuan Penyaluran KPR Sejahtera FLPP',
            'short_title'       => 'Persetujuan Penyaluran FLPP',
            'category'          => 'Pernyataan Pemohon',
            'description'       => 'Pernyataan pemohon, pasangan & direktur developer terkait persetujuan penyaluran KPR FLPP & blokir dana SBUM.',
            'start'             => 8,
            'end'               => 32,
            'skip_nodes'        => [],
            'sect_type'         => 'normal',
            'date_in_tglberkas' => false,
            'is_conditional'    => false,
            'default_checked'   => true,
        ],
        'pernyataan_developer' => [
            'key'               => 'pernyataan_developer',
            'num'               => 3,
            'code'              => '03',
            'title'             => 'Surat Pernyataan Developer',
            'short_title'       => 'Pernyataan Developer',
            'category'          => 'Developer & BAST',
            'description'       => 'Surat pernyataan Direktur PT Pengembang mengenai akad KPR debitur & mekanisme pencairan SBUM.',
            'start'             => 48,
            'end'               => 65,
            'skip_nodes'        => [],
            'sect_type'         => 'normal',
            'date_in_tglberkas' => false,
            'is_conditional'    => false,
            'default_checked'   => true,
        ],
        'pernyataan_internal' => [
            'key'               => 'pernyataan_internal',
            'num'               => 4,
            'code'              => '04',
            'title'             => 'Surat Pernyataan Pemohon BSN KPR Bersubsidi (Format Internal)',
            'short_title'       => 'Pernyataan Format Internal',
            'category'          => 'Pernyataan Pemohon',
            'description'       => 'Format Internal Bank BSN: pernyataan calon debitur & pasangan terkait SP3, aktivasi QR Code, pajak, dan pakta integritas.',
            'start'             => 85,
            'end'               => 121,
            'skip_nodes'        => [],
            'sect_type'         => 'normal',
            'date_in_tglberkas' => false,
            'is_conditional'    => false,
            'default_checked'   => true,
        ],
        'pernyataan_eksternal' => [
            'key'               => 'pernyataan_eksternal',
            'num'               => 5,
            'code'              => '05',
            'title'             => 'Surat Pernyataan Pemohon BSN KPR Sejahtera iB (Format Eksternal / PUPR)',
            'short_title'       => 'Pernyataan Format Eksternal',
            'category'          => 'Pernyataan Pemohon',
            'description'       => 'Format Kementerian PUPR: pernyataan penghasilan, belum memiliki rumah, harga jual rumah & pengembang.',
            'start'             => 136,
            'end'               => 181,
            'skip_nodes'        => [139, 140, 141, 142, 143, 144],
            'sect_type'         => 'normal',
            'date_in_tglberkas' => false,
            'is_conditional'    => false,
            'default_checked'   => true,
        ],
        'pernyataan_spt' => [
            'key'               => 'pernyataan_spt',
            'num'               => 6,
            'code'              => '06',
            'title'             => 'Surat Pernyataan Penyerahan SPT PPh',
            'short_title'       => 'Pernyataan SPT PPh',
            'category'          => 'Pernyataan Pemohon',
            'description'       => 'Komitmen penyerahan dokumen SPT PPh tahun berikutnya bagi pemohon dengan usia NPWP di bawah 1 tahun.',
            'start'             => 199,
            'end'               => 208,
            'skip_nodes'        => [],
            'sect_type'         => 'normal',
            'date_in_tglberkas' => false,
            'is_conditional'    => false,
            'default_checked'   => true,
        ],
        'pernyataan_penghunian' => [
            'key'               => 'pernyataan_penghunian',
            'num'               => 7,
            'code'              => '07',
            'title'             => 'Surat Pernyataan Penghunian Rumah Subsidi',
            'short_title'       => 'Pernyataan Penghunian Rumah',
            'category'          => 'Pernyataan Pemohon',
            'description'       => 'Pernyataan kesanggupan menghuni sendiri rumah subsidi maksimal 1 tahun sejak serah terima.',
            'start'             => 224,
            'end'               => 241,
            'skip_nodes'        => [],
            'sect_type'         => 'normal',
            'date_in_tglberkas' => false,
            'is_conditional'    => false,
            'default_checked'   => true,
        ],
        'bast_tapak' => [
            'key'               => 'bast_tapak',
            'num'               => 8,
            'code'              => '08',
            'title'             => 'Berita Acara Serah Terima Rumah Umum Tapak (BAST)',
            'short_title'       => 'BAST Rumah Tapak',
            'category'          => 'Developer & BAST',
            'description'       => 'Berita acara resmi serah terima unit perumahan, nomor rumah, luas tanah/lantai & kelaikan sarana prasarana.',
            'start'             => 244,
            'end'               => 263,
            'skip_nodes'        => [],
            'sect_type'         => 'normal',
            'date_in_tglberkas' => false,
            'is_conditional'    => false,
            'default_checked'   => true,
        ],
        'kuasa_sbum' => [
            'key'               => 'kuasa_sbum',
            'num'               => 9,
            'code'              => '09',
            'title'             => 'Surat Kuasa Pemindahan Dana SBUM',
            'short_title'       => 'Kuasa Pemindahan SBUM',
            'category'          => 'SBUM & Kuasa Bank',
            'description'       => 'Surat kuasa khusus pencairan & pemindahbukuan dana SBUM Rp 4.000.000 ke rekening PT Pengembang.',
            'start'             => 270,
            'end'               => 295,
            'skip_nodes'        => [],
            'sect_type'         => 'normal',
            'date_in_tglberkas' => false,
            'is_conditional'    => false,
            'default_checked'   => true,
        ],
        'kuasa_debet' => [
            'key'               => 'kuasa_debet',
            'num'               => 10,
            'code'              => '10',
            'title'             => 'Surat Kuasa Pendebetan Dana',
            'short_title'       => 'Kuasa Pendebetan Dana',
            'category'          => 'SBUM & Kuasa Bank',
            'description'       => 'Surat kuasa pendebetan rekening tabungan BSN atas biaya yang timbul apabila ketentuan subsidi dilanggar.',
            'start'             => 318,
            'end'               => 327,
            'skip_nodes'        => [],
            'sect_type'         => 'normal',
            'date_in_tglberkas' => false,
            'is_conditional'    => false,
            'default_checked'   => true,
        ],
        'standing_instruction' => [
            'key'               => 'standing_instruction',
            'num'               => 11,
            'code'              => '11',
            'title'             => 'Surat Perintah Pemindahbukuan Dana SBUM (Standing Instruction)',
            'short_title'       => 'Standing Instruction SBUM',
            'category'          => 'SBUM & Kuasa Bank',
            'description'       => 'Instruksi resmi pemindahbukuan pencairan dana SBUM ke rekening giro PT Pengembang.',
            'start'             => 334,
            'end'               => 341,
            'skip_nodes'        => [],
            'sect_type'         => 'normal',
            'date_in_tglberkas' => false,
            'is_conditional'    => false,
            'default_checked'   => true,
        ],
        'permohonan_sbum' => [
            'key'               => 'permohonan_sbum',
            'num'               => 12,
            'code'              => '12',
            'title'             => 'Surat Permohonan Subsidi Bantuan Uang Muka (SBUM)',
            'short_title'       => 'Permohonan SBUM',
            'category'          => 'SBUM & Kuasa Bank',
            'description'       => 'Permohonan resmi bantuan uang muka perumahan kepada Kepala Satker Ditjen Pembiayaan Infrastruktur PUPR.',
            'start'             => 345,
            'end'               => 369,
            'skip_nodes'        => [],
            'sect_type'         => 'normal',
            'date_in_tglberkas' => false,
            'is_conditional'    => false,
            'default_checked'   => true,
        ],
        'pengakuan_kurang_um' => [
            'key'               => 'pengakuan_kurang_um',
            'num'               => 13,
            'code'              => '13',
            'title'             => 'Surat Pengakuan Kekurangan Bayar Uang Muka',
            'short_title'       => 'Pengakuan Kekurangan UM',
            'category'          => 'SBUM & Kuasa Bank',
            'description'       => 'Pernyataan pembayaran uang muka nasabah & sisa kekurangan uang muka yang disetujui Direktur PT Pengembang.',
            'start'             => 376,
            'end'               => 388,
            'skip_nodes'        => [],
            'sect_type'         => 'normal',
            'date_in_tglberkas' => true,
            'is_conditional'    => false,
            'default_checked'   => true,
        ],
        'ideb_slik' => [
            'key'               => 'ideb_slik',
            'num'               => 14,
            'code'              => '14',
            'title'             => 'Permohonan iDEB (SLIK OJK) Bank Syariah Nasional',
            'short_title'       => 'Permohonan SLIK OJK (iDEB)',
            'category'          => 'Analisa & Lapangan',
            'description'       => 'Formulir resmi pengecekan SLIK OJK berlogo Danantara & BSN lengkap untuk Pemohon dan Pasangan.',
            'start'             => 400,
            'end'               => 427,
            'skip_nodes'        => [],
            'sect_type'         => 'normal',
            'date_in_tglberkas' => false,
            'is_conditional'    => false,
            'default_checked'   => true,
        ],
        'denah_lokasi' => [
            'key'               => 'denah_lokasi',
            'num'               => 15,
            'code'              => '15',
            'title'             => 'Denah Lokasi Rumah & Denah Tempat Bekerja',
            'short_title'       => 'Denah Rumah & Kerja',
            'category'          => 'Analisa & Lapangan',
            'description'       => 'Lembar kotak gambar denah lokasi tempat tinggal & lokasi tempat bekerja/usaha pemohon.',
            'start'             => 433,
            'end'               => 436,
            'skip_nodes'        => [],
            'sect_type'         => 'normal',
            'date_in_tglberkas' => false,
            'is_conditional'    => false,
            'default_checked'   => true,
        ],
        'data_keluarga_kontak' => [
            'key'               => 'data_keluarga_kontak',
            'num'               => 16,
            'code'              => '16',
            'title'             => 'Form Data Keluarga Tidak Serumah & Kontak Pekerjaan',
            'short_title'       => 'Data Keluarga & Kontak Kerja',
            'category'          => 'Analisa & Lapangan',
            'description'       => 'Formulir isian 2 kontak keluarga sedarah tidak serumah serta nomor telepon Atasan/HRD/Kantor.',
            'start'             => 438,
            'end'               => 467,
            'skip_nodes'        => [],
            'sect_type'         => 'normal',
            'date_in_tglberkas' => false,
            'is_conditional'    => false,
            'default_checked'   => true,
        ],
        'pernyataan_tidak_bekerja' => [
            'key'               => 'pernyataan_tidak_bekerja',
            'num'               => 17,
            'code'              => '17',
            'title'             => 'Surat Pernyataan Tidak Bekerja (Pasangan)',
            'short_title'       => 'Pernyataan Tidak Bekerja',
            'category'          => 'Kondisional / Tambahan',
            'description'       => 'Khusus apabila pasangan (suami/istri) tidak bekerja / mengurus rumah tangga, diketahui Lurah/Kepala Desa.',
            'start'             => 477,
            'end'               => 498,
            'skip_nodes'        => [],
            'sect_type'         => 'normal',
            'date_in_tglberkas' => true,
            'is_conditional'    => true,
            'default_checked'   => false,
        ],
        'pernyataan_tanpa_rekening' => [
            'key'               => 'pernyataan_tanpa_rekening',
            'num'               => 18,
            'code'              => '18',
            'title'             => 'Surat Pernyataan Tidak Memiliki Rekening Tabungan di Bank Manapun',
            'short_title'       => 'Pernyataan Tanpa Rekening',
            'category'          => 'Kondisional / Tambahan',
            'description'       => 'Khusus apabila pemohon belum memiliki rekening tabungan di bank manapun saat pengajuan awal.',
            'start'             => 508,
            'end'               => 527,
            'skip_nodes'        => [],
            'sect_type'         => 'normal',
            'date_in_tglberkas' => true,
            'is_conditional'    => true,
            'default_checked'   => false,
        ],
    ];

    public static function getBundleTemplatePath(): string {
        return dirname(__DIR__) . '/storage/templates/template_bundel_bsn.docx';
    }

    public static function getCatalog(): array {
        return self::DOCUMENTS;
    }

    /**
     * Normalisasi daftar key dokumen agar selalu valid dan berurutan sesuai bundel asli.
     */
    public static function normalizeDocKeys(string|array $raw): array {
        if (is_string($raw)) {
            $trim = strtolower(trim($raw));
            if ($trim === '' || $trim === 'all' || $trim === 'semua') {
                return array_keys(self::DOCUMENTS);
            }
            if ($trim === 'wajib' || $trim === 'utama') {
                return array_keys(array_filter(self::DOCUMENTS, fn($d) => !empty($d['default_checked'])));
            }
            $parts = array_map('trim', explode(',', $raw));
        } else {
            $parts = array_map(fn($v) => trim((string)$v), $raw);
        }

        $selectedSet = array_flip($parts);
        $ordered = [];
        foreach (self::DOCUMENTS as $key => $_meta) {
            if (isset($selectedSet[$key])) {
                $ordered[] = $key;
            }
        }
        return $ordered;
    }

    /**
     * Lengkapi & bersihkan seluruh data nasabah (termasuk pengayaan otomatis Master PT / Perumahan & Default Settings)
     * agar pembuatan surat apa pun tetap rapi walaupun nasabah baru mengisi 4 data utama KTP.
     */
    public static function resolveNasabahData(array $r, PDO $pdo, array $overrides = []): array {
        static $cachedSettings = null;
        static $cachedPerums   = null;
        static $cachedPts      = null;

        if ($cachedSettings === null) {
            $cachedSettings = Database::getSettings($pdo);
        }
        if ($cachedPerums === null) {
            $cachedPerums = $pdo->query("
                SELECT mp.*, pt.pengembang AS pt_upper, pt.nama_pengembang AS pt_surat,
                       pt.nama_direktur AS pt_dir, pt.jabatan_direktur AS pt_jab,
                       pt.nik_direktur AS pt_nik, pt.alamat_direktur AS pt_alm, pt.no_rek_pt AS pt_rek
                FROM master_perumahan mp
                LEFT JOIN master_pengembang pt ON pt.id = mp.pengembang_id
            ")->fetchAll();
        }
        if ($cachedPts === null) {
            $cachedPts = $pdo->query("SELECT * FROM master_pengembang")->fetchAll();
        }

        $defaultKota = trim((string)($overrides['kota'] ?? ($cachedSettings['default_kota_surat'] ?? 'Banjar')));

        // 1. Data Utama Pemohon
        $nama      = cleanPlaceholder($r['nama_lengkap'] ?? '');
        $ktp       = cleanPlaceholder($r['no_ktp'] ?? '', true);
        $npwp      = cleanPlaceholder($r['npwp'] ?? '', true) ?: $ktp;
        $ttl       = cleanPlaceholder($r['ttl'] ?? '');
        $alamat    = cleanPlaceholder($r['alamat'] ?? '');
        $noHp      = cleanPlaceholder($r['no_hp'] ?? '', true);
        $pekerjaan = cleanPlaceholder($r['pekerjaan'] ?? '') ?: trim((string)($cachedSettings['default_pekerjaan'] ?? 'Swasta'));
        $penghasilanRaw = cleanPlaceholder($r['penghasilan'] ?? '') ?: cleanPlaceholder($r['penghasilan_pemohon'] ?? '');

        // 2. Data Pasangan
        $namaPas      = cleanPlaceholder($r['nama_pasangan'] ?? '');
        $ktpPas       = cleanPlaceholder($r['ktp_pasangan'] ?? '', true);
        $hasPasangan  = ($namaPas !== '' || $ktpPas !== '');
        $npwpPas      = $hasPasangan ? (cleanPlaceholder($r['npwp_pasangan'] ?? '', true) ?: $ktpPas) : '';
        $ttlPas       = $hasPasangan ? cleanPlaceholder($r['ttl_pasangan'] ?? '') : '';
        $pekerjaanPas = $hasPasangan ? cleanPlaceholder($r['pekerjaan_pasangan'] ?? '') : '';
        $alamatPas    = $hasPasangan ? (cleanPlaceholder($r['alamat_pasangan'] ?? '') ?: $alamat) : '';
        $pasanganTidakBekerja = $hasPasangan && (
            $pekerjaanPas === '' ||
            $pekerjaanPas === '-' ||
            preg_match('/(ibu rumah tangga|\birt\b|tidak bekerja|mengurus rumah)/iu', $pekerjaanPas) === 1
        );

        // 3. Data Perumahan & Pengembang (Auto-enrich dari Master jika sebagian kolom masih kosong)
        $namaPerum   = cleanPlaceholder($r['nama_perumahan'] ?? '') ?: cleanPlaceholder($r['nama_komplek'] ?? '');
        $noRumah     = cleanPlaceholder($r['no_rumah'] ?? '');
        $luasTanah   = cleanPlaceholder($r['luas_tanah'] ?? '');
        $luasLantai  = cleanPlaceholder($r['luas_lantai'] ?? '');
        $lokasiPerum = cleanPlaceholder($r['lokasi_perumahan'] ?? '');
        $pengembang  = cleanPlaceholder($r['pengembang'] ?? '');
        $namaPtSurat = cleanPlaceholder($r['nama_pengembang'] ?? '');
        $namaDir     = cleanPlaceholder($r['nama_direktur'] ?? '');
        $jabDir      = cleanPlaceholder($r['jabatan_direktur'] ?? '');
        $nikDir      = cleanPlaceholder($r['nik_direktur'] ?? '', true);
        $almDir      = cleanPlaceholder($r['alamat_direktur'] ?? '');
        $noRekPt     = cleanPlaceholder($r['no_rek_pt'] ?? '', true);

        if ($namaPerum !== '') {
            foreach ($cachedPerums as $mp) {
                if (strcasecmp($namaPerum, (string)$mp['nama_perumahan']) === 0 || strcasecmp($namaPerum, (string)$mp['nama_komplek']) === 0) {
                    if ($lokasiPerum === '') $lokasiPerum = trim((string)$mp['lokasi_perumahan']);
                    if ($luasTanah === '')   $luasTanah   = trim((string)$mp['luas_tanah']);
                    if ($luasLantai === '')  $luasLantai  = trim((string)$mp['luas_lantai']);
                    if ($pengembang === '')  $pengembang  = trim((string)($mp['pt_upper'] ?? ''));
                    if ($namaPtSurat === '') $namaPtSurat = trim((string)($mp['pt_surat'] ?? ''));
                    if ($namaDir === '')     $namaDir     = trim((string)($mp['pt_dir'] ?? ''));
                    if ($jabDir === '')      $jabDir      = trim((string)($mp['pt_jab'] ?? ''));
                    if ($nikDir === '')      $nikDir      = cleanPlaceholder((string)($mp['pt_nik'] ?? ''), true);
                    if ($almDir === '')      $almDir      = trim((string)($mp['pt_alm'] ?? ''));
                    if ($noRekPt === '')     $noRekPt     = cleanPlaceholder((string)($mp['pt_rek'] ?? ''), true);
                    break;
                }
            }
        }
        if ($pengembang !== '' || $namaPtSurat !== '') {
            $lookupPt = $pengembang ?: $namaPtSurat;
            foreach ($cachedPts as $pt) {
                if (strcasecmp($lookupPt, (string)$pt['pengembang']) === 0 || strcasecmp($lookupPt, (string)$pt['nama_pengembang']) === 0) {
                    if ($pengembang === '')  $pengembang  = trim((string)$pt['pengembang']);
                    if ($namaPtSurat === '') $namaPtSurat = trim((string)$pt['nama_pengembang']);
                    if ($namaDir === '')     $namaDir     = trim((string)$pt['nama_direktur']);
                    if ($jabDir === '')      $jabDir      = trim((string)$pt['jabatan_direktur']);
                    if ($nikDir === '')      $nikDir      = cleanPlaceholder((string)$pt['nik_direktur'], true);
                    if ($almDir === '')      $almDir      = trim((string)$pt['alamat_direktur']);
                    if ($noRekPt === '')     $noRekPt     = cleanPlaceholder((string)$pt['no_rek_pt'], true);
                    break;
                }
            }
        }
        if ($namaPtSurat === '' && $pengembang !== '') {
            $namaPtSurat = $pengembang;
        }
        if ($pengembang === '' && $namaPtSurat !== '') {
            $pengembang = $namaPtSurat;
        }
        if ($jabDir === '' && $namaDir !== '') {
            $jabDir = 'Direktur';
        }
        if ($luasLantai === '') {
            $luasLantai = trim((string)($cachedSettings['default_luas_lantai'] ?? '36'));
        }
        if ($luasTanah !== '' && preg_match('/^\d+$/', $luasTanah)) {
            $luasTanah .= ' m2';
        }

        // 4. Nilai Pembiayaan KPR & Uang Muka
        $hargaJual = cleanPlaceholder($r['harga_jual_rumah'] ?? '') ?: trim((string)($cachedSettings['default_harga_jual'] ?? 'Rp. 182.000.000'));
        $uangMuka  = cleanPlaceholder($r['uang_muka'] ?? '') ?: cleanPlaceholder($r['besaran_uang_muka'] ?? '') ?: trim((string)($cachedSettings['default_uang_muka'] ?? 'Rp. 6.000.000'));
        $sbum      = cleanPlaceholder($r['sbum'] ?? '') ?: trim((string)($cachedSettings['default_sbum'] ?? 'Rp. 4.000.000'));
        $umNasabah = cleanPlaceholder($r['uang_muka_nasabah'] ?? '') ?: trim((string)($cachedSettings['default_um_nasabah'] ?? 'Rp. 2.000.000'));
        $kpr       = cleanPlaceholder($r['kpr'] ?? '') ?: trim((string)($cachedSettings['default_kpr'] ?? 'Rp. 176.000.000'));

        if ($hargaJual !== '' && parseRupiahNumber($hargaJual) > 0) {
            $hargaJual = formatRupiahExcel($hargaJual);
        }
        if ($uangMuka !== '' && parseRupiahNumber($uangMuka) > 0) {
            $uangMuka = formatRupiahExcel($uangMuka);
        }
        $penghasilanFormatted = '';
        if ($penghasilanRaw !== '') {
            $pNum = parseRupiahNumber($penghasilanRaw);
            $penghasilanFormatted = $pNum > 0 ? formatRupiahExcel($pNum) : $penghasilanRaw;
        }

        // 5. Tanggal Surat, BAST, PPJB & Parameter Tambahan Opsional
        $customTanggal = trim((string)($overrides['tgl'] ?? ''));
        if ($customTanggal !== '') {
            $tanggalSurat = $customTanggal;
        } else {
            $candidateDate = cleanPlaceholder($r['tempat_tgl_surat'] ?? '') ?: cleanPlaceholder($r['tgl_berkas'] ?? '');
            if ($candidateDate !== '') {
                if (!str_contains($candidateDate, ',') && preg_match('/^\d/', $candidateDate) && $defaultKota !== '') {
                    $tanggalSurat = $defaultKota . ', ' . $candidateDate;
                } else {
                    $tanggalSurat = $candidateDate;
                }
            } else {
                $tanggalSurat = tanggalIndoSekarang($defaultKota);
            }
        }

        $ppjb             = cleanPlaceholder($r['ppjb'] ?? '');
        $tanggalPpjb      = cleanPlaceholder($r['tanggal_ppjb'] ?? '');
        $tglSerahTerima   = cleanPlaceholder($r['tanggal_serah_terima'] ?? '');
        $noBast           = cleanPlaceholder($r['no_bast'] ?? '');
        $tanggalAkad      = trim((string)($overrides['tanggal_akad'] ?? '')) ?: cleanPlaceholder($r['tanggal_akad'] ?? '');

        $namaFs           = trim((string)($overrides['fs'] ?? ''));
        $sp3No            = trim((string)($overrides['sp3_no'] ?? ''));
        $sp3Tgl           = trim((string)($overrides['sp3_tgl'] ?? ''));
        $noRekTabungan    = trim((string)($overrides['no_rek_tabungan'] ?? ''));
        $noRekKpr         = trim((string)($overrides['no_rek_kpr'] ?? ''));

        $dotsLong = '............................................';
        $dotsMed  = '........................';

        $mergeBase = [
            // Stray MERGEFIELD NO wajib selalu kosong agar nomor urut tidak bocor ke alamat/kalimat
            'NO'                            => '',
            // Pemohon
            'NAMA_LENGKAP'                  => $nama ?: $dotsLong,
            'NOKTP'                         => $ktp ?: $dotsMed,
            'NPWP'                          => $npwp ?: $dotsMed,
            'TEMPAT_TANGGAL_LAHIR'          => $ttl ?: $dotsLong,
            'PEKERJAAN'                     => $pekerjaan ?: $dotsMed,
            'ALAMAT'                        => $alamat ?: $dotsLong,
            'NOHP'                          => $noHp ?: '-',
            'PENGHASILAN'                   => $penghasilanFormatted ?: '........................',
            // Pasangan
            'NAMA_PASANGAN'                 => $hasPasangan ? $namaPas : '',
            'NOKTP_PASANGAN'                => $hasPasangan ? $ktpPas : '',
            'NONPWP_PASANGAN'               => $hasPasangan ? $npwpPas : '',
            'TEMPAT_TANGGAL_LAHIR_PASANGAN' => $hasPasangan ? $ttlPas : '',
            'PEKERJAAN_PASANGAN'            => $hasPasangan ? ($pekerjaanPas ?: '-') : '',
            'ALAMAT_PASANGAN'               => $hasPasangan ? $alamatPas : '',
            // Perumahan & Developer
            'PENGEMBANG'                    => $pengembang ?: $dotsLong,
            'NAMA_PENGEMBANG'               => $namaPtSurat ?: $dotsLong,
            'NAMA_DIREKTUR'                 => $namaDir ?: '……………………………….',
            'NIK_DIREKTUR'                  => $nikDir ?: $dotsMed,
            'JABATAN_DIREKTUR'              => $jabDir ?: 'Direktur',
            'ALAMAT_DIREKTUR'               => $almDir ?: $dotsLong,
            'NOREK_PT'                      => $noRekPt ?: $dotsMed,
            'NAMA_PERUMAHAN'                => $namaPerum ?: $dotsLong,
            'NORUMAH'                       => $noRumah ?: '..........',
            'LUAS_TANAH_'                   => $luasTanah ?: '...... m2',
            'LLANTAI_RUMAH'                 => $luasLantai ?: '36',
            'LOKASI_PERUMAHAN'              => $lokasiPerum ?: $dotsLong,
            // Harga & Uang Muka
            'HARGA_JUAL_RUMAH'              => $hargaJual ?: $dotsMed,
            'UANG_MUKA'                     => $uangMuka ?: $dotsMed,
            // Tanggal default (disesuaikan per-slice saat render)
            'TEMPAT__TGL'                   => $tanggalSurat,
            'TGLBERKAS'                     => '',
        ];

        return [
            'id'                     => (int)($r['id'] ?? 0),
            'no_urut'                => cleanPlaceholder($r['no_urut'] ?? ''),
            'nama_lengkap'           => $nama,
            'no_ktp'                 => $ktp,
            'npwp'                   => $npwp,
            'ttl'                    => $ttl,
            'pekerjaan'              => $pekerjaan,
            'alamat'                 => $alamat,
            'no_hp'                  => $noHp,
            'penghasilan'            => $penghasilanFormatted,
            'has_pasangan'           => $hasPasangan,
            'pasangan_tidak_bekerja' => $pasanganTidakBekerja,
            'nama_pasangan'          => $namaPas,
            'ktp_pasangan'           => $ktpPas,
            'npwp_pasangan'          => $npwpPas,
            'ttl_pasangan'           => $ttlPas,
            'pekerjaan_pasangan'     => $pekerjaanPas,
            'alamat_pasangan'        => $alamatPas,
            'nama_perumahan'         => $namaPerum,
            'no_rumah'               => $noRumah,
            'luas_tanah'             => $luasTanah,
            'luas_lantai'            => $luasLantai,
            'lokasi_perumahan'       => $lokasiPerum,
            'pengembang'             => $pengembang,
            'nama_pengembang'        => $namaPtSurat,
            'nama_direktur'          => $namaDir,
            'jabatan_direktur'       => $jabDir,
            'nik_direktur'           => $nikDir,
            'alamat_direktur'        => $almDir,
            'no_rek_pt'              => $noRekPt,
            'harga_jual_rumah'       => $hargaJual,
            'uang_muka'              => $uangMuka,
            'sbum'                   => $sbum,
            'uang_muka_nasabah'      => $umNasabah,
            'kpr'                    => $kpr,
            'no_bast'                => $noBast,
            'ppjb'                   => $ppjb,
            'tanggal_ppjb'           => $tanggalPpjb,
            'tanggal_serah_terima'   => $tglSerahTerima,
            'tanggal_akad'           => $tanggalAkad,
            'tanggal_surat'          => $tanggalSurat,
            'nama_fs'                => $namaFs,
            'fs_bracket'             => $namaFs !== '' ? '(  ' . $namaFs . '  )' : '(                           )',
            'sp3_no'                 => $sp3No,
            'sp3_tgl'                => $sp3Tgl,
            'no_rek_tabungan'        => $noRekTabungan,
            'no_rek_kpr'             => $noRekKpr,
            'merge'                  => $mergeBase,
        ];
    }

    /**
     * Buat file Microsoft Word (.docx) berisi 1 surat, paket beberapa surat, atau seluruh bundel
     * untuk 1 atau banyak nasabah sekaligus.
     */
    public static function generateDocx(
        array $nasabahRows,
        array $docKeys,
        string $destPath,
        PDO $pdo,
        array $overrides = []
    ): void {
        if (empty($nasabahRows)) {
            throw new InvalidArgumentException('Data nasabah kosong.');
        }
        $normalizedKeys = self::normalizeDocKeys($docKeys);
        if (empty($normalizedKeys)) {
            throw new InvalidArgumentException('Pilih minimal 1 jenis surat yang ingin dibuat.');
        }

        $tplPath = self::getBundleTemplatePath();
        if (!file_exists($tplPath)) {
            throw new RuntimeException('Template bundel BSN tidak ditemukan di storage/templates/template_bundel_bsn.docx');
        }
        if (!copy($tplPath, $destPath)) {
            throw new RuntimeException('Gagal menyiapkan file .docx sementara.');
        }

        $zip = new ZipArchive();
        if ($zip->open($destPath) !== true) {
            @unlink($destPath);
            throw new RuntimeException('Gagal membuka arsip .docx.');
        }

        try {
            $docXmlRaw = $zip->getFromName('word/document.xml');
            if ($docXmlRaw === false) {
                throw new RuntimeException('word/document.xml tidak ditemukan pada template.');
            }

            // 1. Bersihkan koneksi SQL Mail Merge lama agar Word membuka file tanpa popup error
            $settingsRaw = $zip->getFromName('word/settings.xml');
            if ($settingsRaw !== false) {
                $zip->addFromString('word/settings.xml', self::stripMailMergeSettings($settingsRaw));
            }
            if ($zip->locateName('word/_rels/settings.xml.rels') !== false) {
                $zip->deleteName('word/_rels/settings.xml.rels');
            }

            // 2. Muat template sumber & ambil 2 konfigurasi sectPr (tight untuk Checklist, normal untuk Surat 2..18)
            $srcDom = new DOMDocument();
            $srcDom->preserveWhiteSpace = true;
            $srcDom->formatOutput = false;
            if (!@$srcDom->loadXML($docXmlRaw)) {
                throw new RuntimeException('Gagal membaca struktur XML template_bundel_bsn.docx.');
            }
            $srcBody = $srcDom->getElementsByTagNameNS(self::W_NS, 'body')->item(0);
            if (!$srcBody instanceof DOMElement) {
                throw new RuntimeException('Elemen w:body tidak ditemukan dalam template.');
            }
            $srcElements = self::getElementChildren($srcBody);
            [$tightSectPr, $normalSectPr] = self::extractSectionPrototypes($srcElements);

            // 3. Siapkan dokumen tujuan dengan mengosongkan isi <w:body> dari klon template
            $outDom = new DOMDocument();
            $outDom->preserveWhiteSpace = true;
            $outDom->formatOutput = false;
            $outDom->loadXML($docXmlRaw);
            $outBody = $outDom->getElementsByTagNameNS(self::W_NS, 'body')->item(0);
            if (!$outBody instanceof DOMElement) {
                throw new RuntimeException('Gagal menginisialisasi w:body output.');
            }
            while ($outBody->firstChild) {
                $outBody->removeChild($outBody->firstChild);
            }

            // 4. Rakit seluruh pasangan (Nasabah x Dokumen)
            $plan = [];
            foreach (array_values($nasabahRows) as $row) {
                $resolved = self::resolveNasabahData($row, $pdo, $overrides);
                foreach ($normalizedKeys as $dKey) {
                    $plan[] = [
                        'resolved' => $resolved,
                        'doc'      => self::DOCUMENTS[$dKey],
                    ];
                }
            }

            $totalSlices = count($plan);
            foreach ($plan as $sliceIdx => $item) {
                $docMeta  = $item['doc'];
                $resolved = $item['resolved'];
                $isLast   = ($sliceIdx === $totalSlices - 1);

                $sliceNodes = self::buildPopulatedSliceNodes(
                    $outDom,
                    $srcElements,
                    $docMeta,
                    $resolved
                );

                foreach ($sliceNodes as $sNode) {
                    $outBody->appendChild($sNode);
                }

                $sectProto = ($docMeta['sect_type'] === 'tight') ? $tightSectPr : $normalSectPr;
                $sectClone = $outDom->importNode($sectProto, true);

                if ($isLast) {
                    $outBody->appendChild($sectClone);
                } else {
                    $brkP   = $outDom->createElementNS(self::W_NS, 'w:p');
                    $brkPPr = $outDom->createElementNS(self::W_NS, 'w:pPr');
                    $brkPPr->appendChild($sectClone);
                    $brkP->appendChild($brkPPr);
                    $outBody->appendChild($brkP);
                }
            }

            // 5. Sanitasi global bookmark & penomoran ulang ID objek gambar/shape agar bebas konflik OOXML
            self::sanitizeFinalDocumentDom($outDom);

            $finalXml = $outDom->saveXML();
            if ($finalXml !== false) {
                $zip->addFromString('word/document.xml', $finalXml);
            }
        } finally {
            $zip->close();
        }
    }

    /**
     * Buat arsip .zip berisi masing-masing surat sebagai file .docx terpisah untuk 1 Nasabah
     * (contoh: 01_Checklist_Dokumen_BSN_Riyan.docx, 02_Persetujuan_Penyaluran_FLPP_Riyan.docx, dst.)
     */
    public static function generateZipPerSurat(
        array $nasabahRow,
        array $docKeys,
        string $zipDestPath,
        PDO $pdo,
        array $overrides = []
    ): void {
        $normalizedKeys = self::normalizeDocKeys($docKeys);
        if (empty($normalizedKeys)) {
            throw new InvalidArgumentException('Pilih minimal 1 surat.');
        }

        if (file_exists($zipDestPath)) {
            @unlink($zipDestPath);
        }
        $zip = new ZipArchive();
        if ($zip->open($zipDestPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Gagal membuat arsip ZIP.');
        }

        $safeNama = IdebDocxService::sanitizeFilename($nasabahRow['nama_lengkap'] ?? 'Nasabah');
        $tempFiles = [];

        try {
            foreach ($normalizedKeys as $dKey) {
                $meta = self::DOCUMENTS[$dKey];
                $tmpDocx = tempnam(sys_get_temp_dir(), 'bsn_srt_') . '.docx';
                self::generateDocx([$nasabahRow], [$dKey], $tmpDocx, $pdo, $overrides);
                $tempFiles[] = $tmpDocx;

                $safeTitle = IdebDocxService::sanitizeFilename($meta['short_title']);
                $entryName = $meta['code'] . '_' . $safeTitle . '_' . $safeNama . '.docx';
                $zip->addFile($tmpDocx, $entryName);
            }
        } finally {
            $zip->close();
            foreach ($tempFiles as $tf) {
                @unlink($tf);
            }
        }
    }

    /**
     * Buat arsip .zip berisi 1 file .docx per Nasabah (untuk mode cetak massal multi-nasabah).
     */
    public static function generateZipPerNasabah(
        array $nasabahRows,
        array $docKeys,
        string $zipDestPath,
        PDO $pdo,
        array $overrides = []
    ): void {
        $normalizedKeys = self::normalizeDocKeys($docKeys);
        if (empty($normalizedKeys)) {
            throw new InvalidArgumentException('Pilih minimal 1 surat.');
        }

        if (file_exists($zipDestPath)) {
            @unlink($zipDestPath);
        }
        $zip = new ZipArchive();
        if ($zip->open($zipDestPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Gagal membuat arsip ZIP.');
        }

        $prefixDoc = count($normalizedKeys) === 1
            ? IdebDocxService::sanitizeFilename(self::DOCUMENTS[$normalizedKeys[0]]['short_title'])
            : 'Bundel_' . count($normalizedKeys) . '_Surat_BSN';

        $tempFiles = [];
        $usedNames = [];

        try {
            foreach ($nasabahRows as $idx => $row) {
                $tmpDocx = tempnam(sys_get_temp_dir(), 'bsn_nas_') . '.docx';
                self::generateDocx([$row], $normalizedKeys, $tmpDocx, $pdo, $overrides);
                $tempFiles[] = $tmpDocx;

                $safeNama = IdebDocxService::sanitizeFilename($row['nama_lengkap'] ?? ('Nasabah_' . ($idx + 1)));
                $noUrut   = preg_replace('/[^0-9A-Za-z]/', '', (string)($row['no_urut'] ?? ''));
                $urutPart = $noUrut !== '' ? sprintf('%03s_', $noUrut) : '';
                $baseName = $urutPart . $prefixDoc . '_' . $safeNama;
                $entryName = $baseName . '.docx';

                if (isset($usedNames[$entryName])) {
                    $usedNames[$entryName]++;
                    $entryName = $baseName . '_' . $usedNames[$entryName] . '.docx';
                } else {
                    $usedNames[$entryName] = 1;
                }

                $zip->addFile($tmpDocx, $entryName);
            }
        } finally {
            $zip->close();
            foreach ($tempFiles as $tf) {
                @unlink($tf);
            }
        }
    }

    /**
     * Render daftar lembar HTML siap cetak A4 untuk 1 Nasabah dan 1..18 surat terpilih.
     * Setiap item mengembalikan ['key', 'code', 'title', 'sect_type', 'html'].
     */
    public static function renderSlicesToHtml(
        array $nasabahRow,
        array $docKeys,
        PDO $pdo,
        array $overrides = []
    ): array {
        $normalizedKeys = self::normalizeDocKeys($docKeys);
        if (empty($normalizedKeys)) {
            return [];
        }

        $tplPath = self::getBundleTemplatePath();
        if (!file_exists($tplPath)) {
            throw new RuntimeException('Template bundel BSN tidak ditemukan.');
        }

        $zip = new ZipArchive();
        if ($zip->open($tplPath) !== true) {
            throw new RuntimeException('Gagal membaca template bundel BSN.');
        }

        try {
            $docXmlRaw = $zip->getFromName('word/document.xml');
            $numXmlRaw = $zip->getFromName('word/numbering.xml') ?: '';
        } finally {
            $zip->close();
        }

        if ($docXmlRaw === false) {
            throw new RuntimeException('word/document.xml tidak ditemukan.');
        }

        $srcDom = new DOMDocument();
        $srcDom->preserveWhiteSpace = true;
        @$srcDom->loadXML($docXmlRaw);
        $srcBody = $srcDom->getElementsByTagNameNS(self::W_NS, 'body')->item(0);
        if (!$srcBody instanceof DOMElement) {
            return [];
        }
        $srcElements = self::getElementChildren($srcBody);
        $numMap      = self::parseNumberingFormats($numXmlRaw);
        $resolved    = self::resolveNasabahData($nasabahRow, $pdo, $overrides);

        $bsnLogoRel       = IdebDocxService::ensureBsnLogoAsset();
        $danantaraLogoRel = file_exists(dirname(__DIR__) . '/assets/img/ideb_image2.png') ? 'assets/img/ideb_image2.png' : '';

        $workDom = new DOMDocument();
        $workDom->preserveWhiteSpace = true;
        @$workDom->loadXML('<w:document xmlns:w="' . self::W_NS . '"><w:body/></w:document>');

        $sheets = [];
        foreach ($normalizedKeys as $dKey) {
            $meta = self::DOCUMENTS[$dKey];

            if ($dKey === 'ideb_slik') {
                $sheets[] = [
                    'key'       => $dKey,
                    'code'      => $meta['code'],
                    'title'     => $meta['title'],
                    'sect_type' => 'ideb',
                    'html'      => self::renderIdebSheetHtml($resolved, $bsnLogoRel, $danantaraLogoRel),
                ];
                continue;
            }

            $nodes = self::buildPopulatedSliceNodes($workDom, $srcElements, $meta, $resolved);
            $counters = [];
            $parts = [];
            foreach ($nodes as $node) {
                if (!$node instanceof DOMElement) continue;
                if ($node->namespaceURI === self::W_NS && $node->localName === 'p') {
                    $parts[] = self::renderParagraphToHtml($node, $numMap, $counters);
                } elseif ($node->namespaceURI === self::W_NS && $node->localName === 'tbl') {
                    $parts[] = self::renderTableToHtml($node, $numMap, $counters, $dKey);
                }
            }

            $sheets[] = [
                'key'       => $dKey,
                'code'      => $meta['code'],
                'title'     => $meta['title'],
                'sect_type' => $meta['sect_type'],
                'html'      => implode("\n", $parts),
            ];
        }

        return $sheets;
    }

    // =========================================================================
    // INTERNAL OOXML SLICING & QUIRK-FIX ENGINE
    // =========================================================================

    /**
     * @return DOMElement[]
     */
    private static function getElementChildren(DOMNode $parent): array {
        $list = [];
        foreach ($parent->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $list[] = $child;
            }
        }
        return $list;
    }

    /**
     * @param DOMElement[] $srcElements
     * @return array{0: DOMElement, 1: DOMElement}
     */
    private static function extractSectionPrototypes(array $srcElements): array {
        $tight = null;
        $normal = null;

        if (isset($srcElements[7])) {
            $sList = $srcElements[7]->getElementsByTagNameNS(self::W_NS, 'sectPr');
            if ($sList->length > 0 && $sList->item(0) instanceof DOMElement) {
                $tight = $sList->item(0);
            }
        }

        for ($i = count($srcElements) - 1; $i >= 0; $i--) {
            $el = $srcElements[$i];
            if ($el->namespaceURI === self::W_NS && $el->localName === 'sectPr') {
                $normal = $el;
                break;
            }
        }

        if (!$normal && $tight) $normal = $tight;
        if (!$tight && $normal) $tight  = $normal;

        if (!$tight || !$normal) {
            throw new RuntimeException('Konfigurasi halaman (w:sectPr) tidak ditemukan di dalam template.');
        }

        return [$tight, $normal];
    }

    /**
     * Potong rentang elemen milik 1 surat, isi seluruh MERGEFIELD, dan perbaiki semua anomali template manual.
     *
     * @param DOMElement[] $srcElements
     * @return DOMElement[]
     */
    private static function buildPopulatedSliceNodes(
        DOMDocument $targetDom,
        array $srcElements,
        array $docMeta,
        array $resolved
    ): array {
        $docKey    = $docMeta['key'];
        $start     = (int)$docMeta['start'];
        $end       = (int)$docMeta['end'];
        $skipNodes = array_flip($docMeta['skip_nodes'] ?? []);

        // Siapkan peta MERGEFIELD spesifik untuk slice ini
        $mergeMap = $resolved['merge'];
        if (!empty($docMeta['date_in_tglberkas'])) {
            $mergeMap['TGLBERKAS']   = $resolved['tanggal_surat'];
            $mergeMap['TEMPAT__TGL'] = $resolved['tanggal_surat'];
        } else {
            $mergeMap['TEMPAT__TGL'] = $resolved['tanggal_surat'];
            $mergeMap['TGLBERKAS']   = '';
        }

        // Khusus Surat Pernyataan Tidak Bekerja (Pasangan): bila nama pasangan belum diisi di DB, tampilkan titik-titik rapi
        if ($docKey === 'pernyataan_tidak_bekerja' && !$resolved['has_pasangan']) {
            $mergeMap['NAMA_PASANGAN']                 = '……………………………….';
            $mergeMap['NOKTP_PASANGAN']                = '................................';
            $mergeMap['TEMPAT_TANGGAL_LAHIR_PASANGAN'] = '............................................';
            $mergeMap['PEKERJAAN_PASANGAN']            = 'Ibu Rumah Tangga';
            $mergeMap['ALAMAT_PASANGAN']               = $resolved['alamat'] ?: '............................................';
        }

        // Gunakan container sementara agar mudah memanipulasi urutan node slice (misal tabel TTD iDEB)
        $holder = $targetDom->createElementNS(self::W_NS, 'w:sliceHolder');
        $indexByNode = [];

        for ($idx = $start; $idx <= $end; $idx++) {
            if (isset($skipNodes[$idx]) || !isset($srcElements[$idx])) {
                continue;
            }
            $cloned = $targetDom->importNode($srcElements[$idx], true);
            if ($cloned instanceof DOMElement) {
                $cloned->setAttribute('data-orig-idx', (string)$idx);
                $holder->appendChild($cloned);
                $indexByNode[$idx] = $cloned;
            }
        }

        // 1. Hapus page-break pembuka di elemen pertama slice agar tidak memicu halaman kosong ganda
        if ($holder->firstElementChild instanceof DOMElement) {
            self::stripPageBreaksInElement($holder->firstElementChild);
        }

        // 2. Khusus ideb_slik (#400..#427): ubah paragraf TTD spasi manual (#425..#426) menjadi tabel 3 kolom rapi
        if ($docKey === 'ideb_slik') {
            self::upgradeIdebSignatureToTable($targetDom, $holder, $resolved);
        }

        // 3. Ganti seluruh MERGEFIELD kompleks di semua paragraf dalam slice (termasuk di dalam tabel)
        $allParagraphs = [];
        foreach ($holder->childNodes as $ch) {
            if (!$ch instanceof DOMElement) continue;
            if ($ch->namespaceURI === self::W_NS && $ch->localName === 'p') {
                $allParagraphs[] = $ch;
            } else {
                foreach ($ch->getElementsByTagNameNS(self::W_NS, 'p') as $subP) {
                    if ($subP instanceof DOMElement) {
                        $allParagraphs[] = $subP;
                    }
                }
            }
        }

        foreach ($allParagraphs as $p) {
            self::replaceComplexFieldsInParagraph($targetDom, $p, $mergeMap);
        }

        // 4. Perbaikan & pengisian cerdas teks statis / titik-titik pada node tertentu
        self::applySmartNodeEnhancements($targetDom, $holder, $indexByNode, $docKey, $resolved);

        // 5. Bersihkan atribut bantu data-orig-idx & pangkas paragraf kosong berlebih di akhir slice
        $result = [];
        foreach (iterator_to_array($holder->childNodes) as $ch) {
            if ($ch instanceof DOMElement) {
                $ch->removeAttribute('data-orig-idx');
                $result[] = $ch;
            }
        }

        while (count($result) > 1) {
            $last = $result[count($result) - 1];
            if (self::isPureEmptyParagraph($last)) {
                array_pop($result);
            } else {
                break;
            }
        }

        return $result;
    }

    /**
     * Ganti seluruh MERGEFIELD kompleks (w:fldChar begin..separate..end) di dalam 1 <w:p>
     * dengan otomatis mencegah:
     * - Duplikasi MERGEFIELD kembar berurutan (misal NIK_DIREKTURx2, NAMA_PERUMAHANx2, ALAMAT_PASANGANx2)
     * - Penumpukan "Rp Rp." pada penghasilan & harga jual rumah
     * - Tanda kurung kosong "()" bila pasangan/direktur belum terisi
     * serta mewarisi format font (<w:rPr>) asli dari masing-masing surat.
     */
    private static function replaceComplexFieldsInParagraph(DOMDocument $dom, DOMElement $p, array $mergeMap): void {
        $children = iterator_to_array($p->childNodes);
        $n = count($children);
        $i = 0;

        $prevFieldKey = null;
        $textSincePrevField = '';

        while ($i < $n) {
            $node = $children[$i];
            if ($node instanceof DOMElement && $node->namespaceURI === self::W_NS && $node->localName === 'r') {
                $fldType = self::getRunFldCharType($node);
                if ($fldType === 'begin') {
                    $depth = 1;
                    $group = [$node];
                    $instr = '';
                    $inSeparate = false;
                    $displayRPr = null;
                    $beginRPr   = self::findFirstRPr($node);
                    $j = $i + 1;

                    while ($j < $n && $depth > 0) {
                        $cur = $children[$j];
                        $group[] = $cur;
                        if ($cur instanceof DOMElement && $cur->namespaceURI === self::W_NS && $cur->localName === 'r') {
                            $t = self::getRunFldCharType($cur);
                            if ($t === 'begin') {
                                $depth++;
                            } elseif ($t === 'separate' && $depth === 1) {
                                $inSeparate = true;
                            } elseif ($t === 'end') {
                                $depth--;
                            } elseif ($depth === 1) {
                                if (!$inSeparate) {
                                    foreach ($cur->getElementsByTagNameNS(self::W_NS, 'instrText') as $inNode) {
                                        $instr .= $inNode->textContent;
                                    }
                                    if (!$beginRPr) {
                                        $beginRPr = self::findFirstRPr($cur);
                                    }
                                } else {
                                    if (!$displayRPr) {
                                        $displayRPr = self::findFirstRPr($cur);
                                    }
                                }
                            }
                        }
                        $j++;
                    }

                    if ($depth === 0 && preg_match('/MERGEFIELD\s+"?([A-Za-z0-9_]+)"?/i', $instr, $m)) {
                        $fieldKey = strtoupper($m[1]);

                        // Cegah duplikasi MERGEFIELD kembar berurutan tanpa pemisah teks (quirk #53, #252, #486)
                        $isDuplicateBackToBack = (
                            $fieldKey !== 'NO' &&
                            $prevFieldKey === $fieldKey &&
                            trim($textSincePrevField) === ''
                        );

                        $val = $isDuplicateBackToBack ? '' : (string)($mergeMap[$fieldKey] ?? '');

                        // Konteks teks sebelum & sesudah field di paragraf yang sama
                        $textBefore = self::collectParagraphTextBeforeNode($p, $group[0]);
                        $textAfter  = self::collectParagraphTextAfterNode($p, $group[count($group) - 1]);

                        // Cegah "Rp Rp. 182.000.000" apabila sebelum field sudah tertulis "Rp " (#156, #162)
                        if ($val !== '' && preg_match('/\bRp\.?\s*$/iu', $textBefore)) {
                            $val = preg_replace('/^Rp\.?\s*/iu', '', $val) ?? $val;
                        }

                        // Apabila berada di dalam tanda kurung tanda tangan "( MERGEFIELD )" namun nilainya kosong, isi titik-titik
                        if (
                            !$isDuplicateBackToBack &&
                            $val === '' &&
                            in_array($fieldKey, ['NAMA_PASANGAN', 'NAMA_DIREKTUR', 'NAMA_LENGKAP'], true) &&
                            preg_match('/\(\s*$/u', $textBefore) &&
                            preg_match('/^\s*\)/u', $textAfter)
                        ) {
                            $val = ' ………………………………. ';
                        }

                        $styleRPr = $displayRPr ?? $beginRPr;
                        if ($val !== '') {
                            $newRun = self::createRunWithClonedStyle($dom, $val, $styleRPr);
                            $p->insertBefore($newRun, $group[0]);
                        }

                        foreach ($group as $gNode) {
                            if ($gNode->parentNode === $p) {
                                $p->removeChild($gNode);
                            }
                        }

                        if ($fieldKey !== 'NO') {
                            $prevFieldKey = $fieldKey;
                            $textSincePrevField = '';
                        }

                        $i = $j;
                        continue;
                    }
                } else {
                    $textSincePrevField .= $node->textContent;
                }
            }
            $i++;
        }
    }

    /**
     * Pengisian otomatis titik-titik statis (Cluster, Blok, Rekening PT, PPJB, BAST, SP3, Rekening Tabungan, Tanggal SLIK)
     * apabila datanya sudah tersedia di database / toolbar opsional.
     *
     * @param array<int, DOMElement> $indexByNode
     */
    private static function applySmartNodeEnhancements(
        DOMDocument $dom,
        DOMElement $holder,
        array $indexByNode,
        string $docKey,
        array $resolved
    ): void {
        // #28 (persetujuan_flpp): Cluster ..., Blok/No ..., yang dikembangkan oleh ...
        if (isset($indexByNode[28])) {
            self::transformParagraphPlainText($dom, $indexByNode[28], function (string $txt) use ($resolved): string {
                $txt = str_replace('SejahteraFLPP', 'Sejahtera FLPP', $txt);
                if ($resolved['nama_perumahan'] !== '') {
                    $txt = preg_replace('/Cluster\s+[.…]+/u', 'Cluster ' . $resolved['nama_perumahan'], $txt, 1) ?? $txt;
                }
                if ($resolved['no_rumah'] !== '') {
                    $txt = preg_replace('/Blok\/No\s+[.…]+/u', 'Blok/No ' . $resolved['no_rumah'], $txt, 1) ?? $txt;
                }
                if ($resolved['pengembang'] !== '') {
                    $txt = preg_replace('/dikembangkan oleh\s+[.…;]+/u', 'dikembangkan oleh ' . $resolved['pengembang'] . ';', $txt, 1) ?? $txt;
                }
                return $txt;
            });
        }

        // #29 (persetujuan_flpp): Nomor Rekening:... atas nama ...
        if (isset($indexByNode[29])) {
            self::transformParagraphPlainText($dom, $indexByNode[29], function (string $txt) use ($resolved): string {
                if ($resolved['no_rek_pt'] !== '') {
                    $txt = preg_replace('/Nomor\s+Rekening\s*:\s*[.…]+/u', 'Nomor Rekening: ' . $resolved['no_rek_pt'], $txt, 1) ?? $txt;
                }
                if ($resolved['pengembang'] !== '') {
                    $txt = preg_replace('/atas\s+nama\s+[.…]+/u', 'atas nama ' . $resolved['pengembang'], $txt, 1) ?? $txt;
                }
                return $txt;
            });
        }

        // #90 (pernyataan_internal): No SP3 & Tanggal SP3 bila diisi
        if (isset($indexByNode[90]) && ($resolved['sp3_no'] !== '' || $resolved['sp3_tgl'] !== '')) {
            self::transformParagraphPlainText($dom, $indexByNode[90], function (string $txt) use ($resolved): string {
                if ($resolved['sp3_no'] !== '') {
                    $txt = preg_replace('/\(SP3\)\s*No[.…\s]+(?=tanggal)/iu', '(SP3) No. ' . $resolved['sp3_no'] . ' ', $txt, 1) ?? $txt;
                }
                if ($resolved['sp3_tgl'] !== '') {
                    $txt = preg_replace('/tanggal\s*[.…]+/iu', 'tanggal ' . $resolved['sp3_tgl'], $txt, 1) ?? $txt;
                }
                return $txt;
            });
        }

        // #247 (bast_tapak): PPJB No, Tanggal PPJB, Tanggal Serah Terima, Pengembang
        if (isset($indexByNode[247])) {
            self::transformParagraphPlainText($dom, $indexByNode[247], function (string $txt) use ($resolved): string {
                if ($resolved['ppjb'] !== '') {
                    $txt = preg_replace('/No\.\s*[.…]+/u', 'No. ' . $resolved['ppjb'], $txt, 1) ?? $txt;
                }
                if ($resolved['tanggal_ppjb'] !== '') {
                    $txt = preg_replace('/tanggal\s*:\s*[.…\s]+202[0-9….]*/iu', 'tanggal : ' . $resolved['tanggal_ppjb'], $txt, 1) ?? $txt;
                }
                if ($resolved['tanggal_serah_terima'] !== '') {
                    $txt = preg_replace('/pada\s+tanggal\s*[.…\s]+202[0-9….]*/iu', 'pada tanggal ' . $resolved['tanggal_serah_terima'], $txt, 1) ?? $txt;
                }
                if ($resolved['pengembang'] !== '') {
                    $txt = preg_replace('/dari\s+Pengembang\s+[.…]+/iu', 'dari Pengembang ' . $resolved['pengembang'], $txt, 1) ?? $txt;
                }
                return $txt;
            });
        }

        // #275 (kuasa_sbum <tbl>): isi baris Nomor Rekening Tabungan, No. SP3K, Tanggal Akad, Nomor Rekening KPR bila tersedia
        if (isset($indexByNode[275])) {
            self::fillIdentityTableExtraRows($dom, $indexByNode[275], [
                'Nomor Rekening Tabungan'       => $resolved['no_rek_tabungan'],
                'No. SP3K'                      => $resolved['sp3_no'],
                'Tanggal Akad KPR Bersubsidi'   => $resolved['tanggal_akad'],
                'Nomor Rekening KPR Bersubsidi' => $resolved['no_rek_kpr'],
            ]);
        }

        // #324 (kuasa_debet): Nomor Rekening Tabungan BSN bila tersedia
        if (isset($indexByNode[324]) && $resolved['no_rek_tabungan'] !== '') {
            self::transformParagraphPlainText($dom, $indexByNode[324], function (string $txt) use ($resolved): string {
                return preg_replace('/nomor\s*:\s*[.…]+/iu', 'nomor : ' . $resolved['no_rek_tabungan'], $txt, 1) ?? $txt;
            });
        }

        // #362 (permohonan_sbum): disetujui oleh <Nama Direktur>
        if (isset($indexByNode[362]) && $resolved['nama_direktur'] !== '') {
            self::transformParagraphPlainText($dom, $indexByNode[362], function (string $txt) use ($resolved): string {
                return preg_replace('/disetujui\s+oleh\s*[.…]+/iu', 'disetujui oleh ' . $resolved['nama_direktur'], $txt, 1) ?? $txt;
            });
        }

        // #380 (pengakuan_kurang_um): sesuaikan nominal uang muka nasabah & kekurangan SBUM bila berbeda dari default 2jt / 4jt
        if (isset($indexByNode[380])) {
            $umNasInt = parseRupiahNumber($resolved['uang_muka_nasabah']);
            $sbumInt  = parseRupiahNumber($resolved['sbum']);
            if (($umNasInt > 0 && $umNasInt !== 2000000) || ($sbumInt > 0 && $sbumInt !== 4000000)) {
                self::transformParagraphPlainText($dom, $indexByNode[380], function (string $txt) use ($umNasInt, $sbumInt): string {
                    if ($umNasInt > 0 && $umNasInt !== 2000000) {
                        $fmt = number_format($umNasInt, 0, ',', '.');
                        $terbilang = ucwords(self::terbilangIndo($umNasInt)) . ' Rupiah';
                        $txt = preg_replace('/sebesar\s+Rp\.?\s*2\.000\.000,-?\s*\([^)]+\)/iu', 'sebesar Rp. ' . $fmt . ',- (' . $terbilang . ')', $txt, 1) ?? $txt;
                    }
                    if ($sbumInt > 0 && $sbumInt !== 4000000) {
                        $fmt = number_format($sbumInt, 0, ',', '.');
                        $terbilang = strtoupper(self::terbilangIndo($sbumInt)) . ' RUPIAH';
                        $txt = preg_replace('/sebesar\s+Rp\.?\s*4\.000\.000\s*\([^)]+\)/iu', 'sebesar Rp. ' . $fmt . ' (' . $terbilang . ')', $txt, 1) ?? $txt;
                    }
                    return $txt;
                });
            }
        }

        // #419 (ideb_slik): "Banjar, 2" "2" " Oktober " "2026" terpecah di 4 run -> ganti bersih ke tanggal surat
        if ($docKey === 'ideb_slik') {
            foreach ($holder->childNodes as $ch) {
                if ($ch instanceof DOMElement && $ch->namespaceURI === self::W_NS && $ch->localName === 'p') {
                    if (trim($ch->textContent) === 'Banjar, 22 Oktober 2026') {
                        self::transformParagraphPlainText($dom, $ch, fn() => $resolved['tanggal_surat']);
                    }
                }
            }
        }

        // #483 (pernyataan_tidak_bekerja): hapus titik "." liar di ujung NIK pasangan ("6371054511970000.")
        if (isset($indexByNode[483])) {
            self::transformParagraphPlainText($dom, $indexByNode[483], function (string $txt): string {
                return preg_replace('/(\d)\.$/u', '$1', rtrim($txt)) ?? $txt;
            });
        }
    }

    private static function fillIdentityTableExtraRows(DOMDocument $dom, DOMElement $tbl, array $labelValues): void {
        foreach ($tbl->getElementsByTagNameNS(self::W_NS, 'tr') as $tr) {
            if (!$tr instanceof DOMElement) continue;
            $cells = [];
            foreach ($tr->childNodes as $c) {
                if ($c instanceof DOMElement && $c->namespaceURI === self::W_NS && $c->localName === 'tc') {
                    $cells[] = $c;
                }
            }
            if (count($cells) < 3) continue;
            $lbl = preg_replace('/\s+/u', ' ', trim($cells[0]->textContent)) ?? '';
            foreach ($labelValues as $targetLbl => $val) {
                if ($val !== '' && strcasecmp($lbl, $targetLbl) === 0) {
                    $valP = $cells[2]->getElementsByTagNameNS(self::W_NS, 'p')->item(0);
                    if ($valP instanceof DOMElement && trim($valP->textContent) === '') {
                        $refRPr = self::findFirstRPr($cells[0]);
                        $valP->appendChild(self::createRunWithClonedStyle($dom, $val, $refRPr));
                    }
                }
            }
        }
    }

    /**
     * Ubah baris tanda tangan spasi manual pada slice ideb_slik (#425 & #426) menjadi tabel 3 kolom rapi tanpa border.
     */
    private static function upgradeIdebSignatureToTable(DOMDocument $dom, DOMElement $holder, array $resolved): void {
        $pNodes = [];
        foreach ($holder->childNodes as $ch) {
            if ($ch instanceof DOMElement && $ch->namespaceURI === self::W_NS && $ch->localName === 'p') {
                $pNodes[] = $ch;
            }
        }

        $bracketP = null;
        $labelP   = null;
        $cnt = count($pNodes);
        for ($i = 0; $i < $cnt; $i++) {
            $txt = trim($pNodes[$i]->textContent);
            if (preg_match('/^\(\s*\)\s*\(\s*\)\s*\(\s*\)$/u', $txt)) {
                $bracketP = $pNodes[$i];
                if ($i + 1 < $cnt && stripos($pNodes[$i + 1]->textContent, 'Financing Service') !== false) {
                    $labelP = $pNodes[$i + 1];
                }
                break;
            }
        }

        if (!$bracketP) {
            return;
        }

        $tbl = $dom->createElementNS(self::W_NS, 'w:tbl');
        $tblPr = $dom->createElementNS(self::W_NS, 'w:tblPr');

        $tblW = $dom->createElementNS(self::W_NS, 'w:tblW');
        $tblW->setAttributeNS(self::W_NS, 'w:w', '9134');
        $tblW->setAttributeNS(self::W_NS, 'w:type', 'dxa');
        $tblPr->appendChild($tblW);

        $tblBorders = $dom->createElementNS(self::W_NS, 'w:tblBorders');
        foreach (['top', 'left', 'bottom', 'right', 'insideH', 'insideV'] as $side) {
            $b = $dom->createElementNS(self::W_NS, 'w:' . $side);
            $b->setAttributeNS(self::W_NS, 'w:val', 'none');
            $b->setAttributeNS(self::W_NS, 'w:sz', '0');
            $b->setAttributeNS(self::W_NS, 'w:space', '0');
            $b->setAttributeNS(self::W_NS, 'w:color', 'auto');
            $tblBorders->appendChild($b);
        }
        $tblPr->appendChild($tblBorders);

        $tblLayout = $dom->createElementNS(self::W_NS, 'w:tblLayout');
        $tblLayout->setAttributeNS(self::W_NS, 'w:type', 'fixed');
        $tblPr->appendChild($tblLayout);
        $tbl->appendChild($tblPr);

        $colWidths = [2900, 3334, 2900];
        $tblGrid = $dom->createElementNS(self::W_NS, 'w:tblGrid');
        foreach ($colWidths as $cw) {
            $gc = $dom->createElementNS(self::W_NS, 'w:gridCol');
            $gc->setAttributeNS(self::W_NS, 'w:w', (string)$cw);
            $tblGrid->appendChild($gc);
        }
        $tbl->appendChild($tblGrid);

        $row1 = [
            $resolved['fs_bracket'],
            '(                                       )',
            '(                           )',
        ];
        $tr1 = $dom->createElementNS(self::W_NS, 'w:tr');
        foreach ($colWidths as $idx => $cw) {
            $tr1->appendChild(self::createIdebSignCell($dom, $cw, $row1[$idx], false, '0', '20'));
        }
        $tbl->appendChild($tr1);

        $row2 = [
            ['text' => 'Financing Service',       'bold' => false],
            ['text' => $resolved['nama_lengkap'], 'bold' => true],
            ['text' => $resolved['has_pasangan'] ? $resolved['nama_pasangan'] : '', 'bold' => true],
        ];
        $tr2 = $dom->createElementNS(self::W_NS, 'w:tr');
        foreach ($colWidths as $idx => $cw) {
            $tr2->appendChild(self::createIdebSignCell($dom, $cw, $row2[$idx]['text'], $row2[$idx]['bold'], '20', '120'));
        }
        $tbl->appendChild($tr2);

        $holder->replaceChild($tbl, $bracketP);
        if ($labelP && $labelP->parentNode === $holder) {
            $holder->removeChild($labelP);
        }
    }

    private static function createIdebSignCell(
        DOMDocument $dom,
        int $widthDxa,
        string $text,
        bool $bold,
        string $spaceBefore,
        string $spaceAfter
    ): DOMElement {
        $tc = $dom->createElementNS(self::W_NS, 'w:tc');
        $tcPr = $dom->createElementNS(self::W_NS, 'w:tcPr');
        $tcW = $dom->createElementNS(self::W_NS, 'w:tcW');
        $tcW->setAttributeNS(self::W_NS, 'w:w', (string)$widthDxa);
        $tcW->setAttributeNS(self::W_NS, 'w:type', 'dxa');
        $tcPr->appendChild($tcW);
        $tc->appendChild($tcPr);

        $p = $dom->createElementNS(self::W_NS, 'w:p');
        $pPr = $dom->createElementNS(self::W_NS, 'w:pPr');
        $jc = $dom->createElementNS(self::W_NS, 'w:jc');
        $jc->setAttributeNS(self::W_NS, 'w:val', 'center');
        $pPr->appendChild($jc);

        $spacing = $dom->createElementNS(self::W_NS, 'w:spacing');
        $spacing->setAttributeNS(self::W_NS, 'w:before', $spaceBefore);
        $spacing->setAttributeNS(self::W_NS, 'w:after', $spaceAfter);
        $pPr->appendChild($spacing);
        $p->appendChild($pPr);

        if ($text !== '') {
            $r = $dom->createElementNS(self::W_NS, 'w:r');
            $rPr = $dom->createElementNS(self::W_NS, 'w:rPr');
            $rFonts = $dom->createElementNS(self::W_NS, 'w:rFonts');
            $rFonts->setAttributeNS(self::W_NS, 'w:ascii', 'Arial Narrow');
            $rFonts->setAttributeNS(self::W_NS, 'w:hAnsi', 'Arial Narrow');
            $rPr->appendChild($rFonts);
            if ($bold) {
                $rPr->appendChild($dom->createElementNS(self::W_NS, 'w:b'));
                $rPr->appendChild($dom->createElementNS(self::W_NS, 'w:bCs'));
            }
            $sz = $dom->createElementNS(self::W_NS, 'w:sz');
            $sz->setAttributeNS(self::W_NS, 'w:val', '20');
            $rPr->appendChild($sz);
            $szCs = $dom->createElementNS(self::W_NS, 'w:szCs');
            $szCs->setAttributeNS(self::W_NS, 'w:val', '20');
            $rPr->appendChild($szCs);
            $r->appendChild($rPr);

            $t = $dom->createElementNS(self::W_NS, 'w:t');
            $t->setAttributeNS(self::XML_NS, 'xml:space', 'preserve');
            $t->appendChild($dom->createTextNode($text));
            $r->appendChild($t);
            $p->appendChild($r);
        }

        $tc->appendChild($p);
        return $tc;
    }

    /**
     * Transformasi teks pada <w:p> biasa (tanpa drawing/field) dengan mempertahankan <w:pPr> dan <w:rPr> pertama.
     */
    private static function transformParagraphPlainText(DOMDocument $dom, DOMElement $p, callable $transform): void {
        if (
            $p->getElementsByTagNameNS(self::W_NS, 'drawing')->length > 0 ||
            $p->getElementsByTagNameNS(self::W_NS, 'fldChar')->length > 0
        ) {
            return;
        }

        $fullText = '';
        $firstRPr = null;
        $runsToRemove = [];

        foreach ($p->childNodes as $ch) {
            if ($ch instanceof DOMElement && $ch->namespaceURI === self::W_NS && $ch->localName === 'r') {
                if (!$firstRPr) {
                    $firstRPr = self::findFirstRPr($ch);
                }
                foreach ($ch->childNodes as $rChild) {
                    if ($rChild instanceof DOMElement && $rChild->namespaceURI === self::W_NS) {
                        if ($rChild->localName === 't') {
                            $fullText .= $rChild->textContent;
                        } elseif ($rChild->localName === 'tab') {
                            $fullText .= "\t";
                        }
                    }
                }
                $runsToRemove[] = $ch;
            }
        }

        $newText = (string)$transform($fullText);
        if ($newText === $fullText) {
            return;
        }

        foreach ($runsToRemove as $rNode) {
            $p->removeChild($rNode);
        }

        if ($newText !== '') {
            $parts = explode("\t", $newText);
            foreach ($parts as $idx => $part) {
                if ($idx > 0) {
                    $tabRun = $dom->createElementNS(self::W_NS, 'w:r');
                    if ($firstRPr) {
                        $tabRun->appendChild($firstRPr->cloneNode(true));
                    }
                    $tabRun->appendChild($dom->createElementNS(self::W_NS, 'w:tab'));
                    $p->appendChild($tabRun);
                }
                if ($part !== '') {
                    $p->appendChild(self::createRunWithClonedStyle($dom, $part, $firstRPr));
                }
            }
        }
    }

    private static function stripPageBreaksInElement(DOMElement $el): void {
        foreach (iterator_to_array($el->getElementsByTagNameNS(self::W_NS, 'br')) as $br) {
            if ($br instanceof DOMElement) {
                $type = $br->getAttributeNS(self::W_NS, 'type') ?: $br->getAttribute('w:type');
                if ($type === 'page' && $br->parentNode) {
                    $br->parentNode->removeChild($br);
                }
            }
        }
        foreach (iterator_to_array($el->getElementsByTagNameNS(self::W_NS, 'lastRenderedPageBreak')) as $lrb) {
            if ($lrb->parentNode) {
                $lrb->parentNode->removeChild($lrb);
            }
        }
    }

    private static function isPureEmptyParagraph(DOMElement $el): bool {
        if ($el->namespaceURI !== self::W_NS || $el->localName !== 'p') {
            return false;
        }
        if (trim($el->textContent) !== '') {
            return false;
        }
        if (
            $el->getElementsByTagNameNS(self::W_NS, 'drawing')->length > 0 ||
            $el->getElementsByTagNameNS(self::W_NS, 'pict')->length > 0 ||
            $el->getElementsByTagNameNS(self::W_NS, 'sectPr')->length > 0
        ) {
            return false;
        }
        return true;
    }

    private static function findFirstRPr(DOMElement $context): ?DOMElement {
        if ($context->namespaceURI === self::W_NS && $context->localName === 'r') {
            foreach ($context->childNodes as $ch) {
                if ($ch instanceof DOMElement && $ch->namespaceURI === self::W_NS && $ch->localName === 'rPr') {
                    return $ch;
                }
            }
            return null;
        }
        $list = $context->getElementsByTagNameNS(self::W_NS, 'rPr');
        return ($list->length > 0 && $list->item(0) instanceof DOMElement) ? $list->item(0) : null;
    }

    private static function createRunWithClonedStyle(DOMDocument $dom, string $text, ?DOMElement $rPrSource): DOMElement {
        $r = $dom->createElementNS(self::W_NS, 'w:r');
        if ($rPrSource) {
            $clonedRPr = $dom->importNode($rPrSource, true);
            $r->appendChild($clonedRPr);
        }
        $t = $dom->createElementNS(self::W_NS, 'w:t');
        if ($text !== trim($text) || str_contains($text, '  ')) {
            $t->setAttributeNS(self::XML_NS, 'xml:space', 'preserve');
        }
        $t->appendChild($dom->createTextNode($text));
        $r->appendChild($t);
        return $r;
    }

    private static function collectParagraphTextBeforeNode(DOMElement $p, DOMNode $stopNode): string {
        $buf = '';
        foreach ($p->childNodes as $ch) {
            if ($ch === $stopNode) break;
            if ($ch instanceof DOMElement && $ch->namespaceURI === self::W_NS && $ch->localName === 'r') {
                foreach ($ch->getElementsByTagNameNS(self::W_NS, 't') as $tNode) {
                    $buf .= $tNode->textContent;
                }
            }
        }
        return $buf;
    }

    private static function collectParagraphTextAfterNode(DOMElement $p, DOMNode $afterNode): string {
        $buf = '';
        $found = false;
        foreach ($p->childNodes as $ch) {
            if ($ch === $afterNode) {
                $found = true;
                continue;
            }
            if ($found && $ch instanceof DOMElement && $ch->namespaceURI === self::W_NS && $ch->localName === 'r') {
                if (self::getRunFldCharType($ch) !== null) {
                    break;
                }
                foreach ($ch->getElementsByTagNameNS(self::W_NS, 't') as $tNode) {
                    $buf .= $tNode->textContent;
                }
            }
        }
        return $buf;
    }

    private static function getRunFldCharType(DOMElement $r): ?string {
        foreach ($r->getElementsByTagNameNS(self::W_NS, 'fldChar') as $fc) {
            if ($fc instanceof DOMElement) {
                $t = $fc->getAttributeNS(self::W_NS, 'fldCharType') ?: $fc->getAttribute('w:fldCharType');
                if ($t !== '') {
                    return $t;
                }
            }
        }
        return null;
    }

    private static function sanitizeFinalDocumentDom(DOMDocument $dom): void {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', self::W_NS);
        $xpath->registerNamespace('wp', self::WP_NS);
        $xpath->registerNamespace('pic', self::PIC_NS);
        $xpath->registerNamespace('wps', self::WPS_NS);
        $xpath->registerNamespace('v', self::V_NS);

        foreach (iterator_to_array($xpath->query('//w:bookmarkStart | //w:bookmarkEnd | //w:lastRenderedPageBreak')) as $bm) {
            if ($bm->parentNode) {
                $bm->parentNode->removeChild($bm);
            }
        }

        $nextId = 1;
        foreach (iterator_to_array($xpath->query('//wp:docPr')) as $el) {
            if ($el instanceof DOMElement) {
                $el->setAttribute('id', (string)$nextId++);
            }
        }
        foreach (iterator_to_array($xpath->query('//pic:cNvPr')) as $el) {
            if ($el instanceof DOMElement) {
                $el->setAttribute('id', (string)$nextId++);
            }
        }
        foreach (iterator_to_array($xpath->query('//wps:cNvPr')) as $el) {
            if ($el instanceof DOMElement) {
                $el->setAttribute('id', (string)$nextId++);
            }
        }

        $shapeIdx = 1;
        foreach (iterator_to_array($xpath->query('//v:shape')) as $vShape) {
            if ($vShape instanceof DOMElement && $vShape->hasAttribute('id')) {
                $vShape->setAttribute('id', 'BSNShape_' . $shapeIdx++);
            }
        }

        $drawIdx = 1;
        foreach (iterator_to_array($xpath->query('//wp:anchor | //wp:inline')) as $drawEl) {
            if ($drawEl instanceof DOMElement) {
                if ($drawEl->hasAttributeNS(self::WP14_NS, 'anchorId')) {
                    $drawEl->setAttributeNS(self::WP14_NS, 'wp14:anchorId', sprintf('%08X', 0x21000000 + $drawIdx));
                }
                if ($drawEl->hasAttributeNS(self::WP14_NS, 'editId')) {
                    $drawEl->setAttributeNS(self::WP14_NS, 'wp14:editId', sprintf('%08X', 0x31000000 + $drawIdx));
                }
                $drawIdx++;
            }
        }
    }

    private static function stripMailMergeSettings(string $settingsXml): string {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;
        if (!@$dom->loadXML($settingsXml)) {
            return $settingsXml;
        }
        foreach (iterator_to_array($dom->getElementsByTagNameNS(self::W_NS, 'mailMerge')) as $node) {
            if ($node->parentNode) {
                $node->parentNode->removeChild($node);
            }
        }
        return $dom->saveXML() ?: $settingsXml;
    }

    // =========================================================================
    // HTML / PDF PREVIEW RENDERER (OOXML -> CLEAN A4 HTML)
    // =========================================================================

    private static function parseNumberingFormats(string $numXml): array {
        if ($numXml === '') return [];
        $dom = new DOMDocument();
        if (!@$dom->loadXML($numXml)) return [];

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', self::W_NS);

        $abstracts = [];
        foreach ($xpath->query('//w:abstractNum') as $abs) {
            if (!$abs instanceof DOMElement) continue;
            $absId = $abs->getAttributeNS(self::W_NS, 'abstractNumId') ?: $abs->getAttribute('w:abstractNumId');
            $lvls = [];
            foreach ($xpath->query('w:lvl', $abs) as $lvl) {
                if (!$lvl instanceof DOMElement) continue;
                $ilvl = (int)($lvl->getAttributeNS(self::W_NS, 'ilvl') ?: $lvl->getAttribute('w:ilvl') ?: 0);
                $fmtNode = $xpath->query('w:numFmt', $lvl)->item(0);
                $txtNode = $xpath->query('w:lvlText', $lvl)->item(0);
                $fmt = $fmtNode instanceof DOMElement ? ($fmtNode->getAttributeNS(self::W_NS, 'val') ?: $fmtNode->getAttribute('w:val')) : 'decimal';
                $txt = $txtNode instanceof DOMElement ? ($txtNode->getAttributeNS(self::W_NS, 'val') ?: $txtNode->getAttribute('w:val')) : '%1.';
                $lvls[$ilvl] = ['fmt' => $fmt, 'txt' => $txt];
            }
            $abstracts[$absId] = $lvls;
        }

        $numMap = [];
        foreach ($xpath->query('//w:num') as $num) {
            if (!$num instanceof DOMElement) continue;
            $numId = $num->getAttributeNS(self::W_NS, 'numId') ?: $num->getAttribute('w:numId');
            $absRef = $xpath->query('w:abstractNumId', $num)->item(0);
            if ($absRef instanceof DOMElement) {
                $absId = $absRef->getAttributeNS(self::W_NS, 'val') ?: $absRef->getAttribute('w:val');
                if (isset($abstracts[$absId])) {
                    $numMap[$numId] = $abstracts[$absId];
                }
            }
        }
        return $numMap;
    }

    private static function formatListMarker(array $numMap, array &$counters, string $numId, int $ilvl): string {
        if (!isset($counters[$numId])) {
            $counters[$numId] = [];
        }
        for ($k = $ilvl + 1; $k <= 5; $k++) {
            unset($counters[$numId][$k]);
        }
        $counters[$numId][$ilvl] = ($counters[$numId][$ilvl] ?? 0) + 1;
        $n = $counters[$numId][$ilvl];

        $cfg = $numMap[$numId][$ilvl] ?? ['fmt' => 'decimal', 'txt' => '%1.'];
        $fmt = $cfg['fmt'];
        if ($fmt === 'bullet') {
            return '•';
        }
        if ($fmt === 'lowerLetter') {
            $chr = chr(96 + (($n - 1) % 26) + 1);
            return str_replace('%' . ($ilvl + 1), $chr, $cfg['txt']);
        }
        if ($fmt === 'upperLetter') {
            $chr = chr(64 + (($n - 1) % 26) + 1);
            return str_replace('%' . ($ilvl + 1), $chr, $cfg['txt']);
        }
        return $n . '.';
    }

    private static function renderParagraphToHtml(DOMElement $p, array $numMap, array &$counters, bool $inTable = false): string {
        $align = '';
        $numId = '';
        $ilvl  = 0;
        $indentLeftPt = 0.0;

        foreach ($p->childNodes as $ch) {
            if ($ch instanceof DOMElement && $ch->namespaceURI === self::W_NS && $ch->localName === 'pPr') {
                foreach ($ch->childNodes as $pp) {
                    if (!$pp instanceof DOMElement || $pp->namespaceURI !== self::W_NS) continue;
                    if ($pp->localName === 'jc') {
                        $jc = $pp->getAttributeNS(self::W_NS, 'val') ?: $pp->getAttribute('w:val');
                        $align = match ($jc) {
                            'center' => 'center',
                            'right', 'end' => 'right',
                            'both', 'distribute' => 'justify',
                            default => 'left',
                        };
                    } elseif ($pp->localName === 'numPr') {
                        foreach ($pp->childNodes as $np) {
                            if (!$np instanceof DOMElement || $np->namespaceURI !== self::W_NS) continue;
                            if ($np->localName === 'numId') {
                                $numId = $np->getAttributeNS(self::W_NS, 'val') ?: $np->getAttribute('w:val');
                            } elseif ($np->localName === 'ilvl') {
                                $ilvl = (int)($np->getAttributeNS(self::W_NS, 'val') ?: $np->getAttribute('w:val') ?: 0);
                            }
                        }
                    } elseif ($pp->localName === 'ind') {
                        $leftDxa = (int)($pp->getAttributeNS(self::W_NS, 'left') ?: $pp->getAttribute('w:left') ?: 0);
                        $hangDxa = (int)($pp->getAttributeNS(self::W_NS, 'hanging') ?: $pp->getAttribute('w:hanging') ?: 0);
                        $netDxa = max(0, $leftDxa - $hangDxa);
                        if ($netDxa > 0 && $numId === '') {
                            $indentLeftPt = round($netDxa / 20, 1);
                        }
                    }
                }
            }
        }

        // Deteksi apakah paragraf berisi kotak Meterai shape
        $hasMeteraiShape = self::paragraphHasMeteraiDrawing($p);
        $inlineHtml = self::renderParagraphRunsHtml($p);

        // Deteksi baris identitas bertab ("Nama Lengkap \t: Riyan" atau "NIK: 6371...") di luar tabel
        if (!$inTable && $numId === '' && !$hasMeteraiShape && $align !== 'center' && $align !== 'right') {
            $rawPlain = trim(self::extractCleanParagraphPlainText($p));
            if (preg_match('/^(Nama Lengkap|Nama Developer|Nama|No\.?\s*KTP|NIK|Tempat,?\s*Tanggal Lahir|Tempat\/tgl lahir|Pekerjaan|Jabatan|Alamat Perumahan|Alamat)\s*:\s*(.*)$/iu', $rawPlain, $km)) {
                $lbl = trim($km[1]);
                $val = trim($km[2]);
                $valStrong = in_array(strtoupper($lbl), ['NAMA LENGKAP', 'NAMA', 'NAMA DEVELOPER'], true) && !str_starts_with($val, '.') && !str_starts_with($val, '…');
                $valHtml = $valStrong ? '<strong>' . e($val) . '</strong>' : e($val);
                return '<div class="bsn-kv-line"><span class="bsn-kv-lbl">' . e($lbl) . '</span><span class="bsn-kv-sep">:</span><span class="bsn-kv-val">' . $valHtml . '</span></div>';
            }
        }

        // Jika di luar tabel terdapat baris penuh spasi kiri untuk tanggal/TTD (misal #262, #521, #527)
        if (!$inTable && $align === '' && preg_match('/^(?:&nbsp;|\s){25,}(.+)$/su', $inlineHtml, $sm)) {
            $align = 'right';
            $inlineHtml = trim($sm[1]);
        }

        if ($hasMeteraiShape) {
            $meteraiBox = '<div class="bsn-meterai-box">Meterai<br>Rp 10.000,-</div>';
            $cleanInline = trim(str_replace('&nbsp;', ' ', strip_tags($inlineHtml)));
            $inlineHtml = $cleanInline === '' ? $meteraiBox : ($meteraiBox . '<div>' . $inlineHtml . '</div>');
        }

        if (trim(str_replace('&nbsp;', '', strip_tags($inlineHtml))) === '' && !$hasMeteraiShape) {
            return $inTable ? '<div class="bsn-empty-line">&nbsp;</div>' : '<div class="bsn-spacer"></div>';
        }

        $styles = [];
        if ($align !== '') {
            $styles[] = 'text-align:' . $align;
        }
        if ($indentLeftPt > 0 && !$inTable) {
            $styles[] = 'margin-left:' . $indentLeftPt . 'pt';
        }
        $styleAttr = !empty($styles) ? ' style="' . implode(';', $styles) . '"' : '';

        if ($numId !== '' && $numId !== '0') {
            $marker = self::formatListMarker($numMap, $counters, $numId, $ilvl);
            $padLeft = $ilvl > 0 ? ' style="margin-left:' . ($ilvl * 16) . 'px;"' : '';
            return '<div class="bsn-list-item"' . $padLeft . '><span class="bsn-list-num">' . e($marker) . '</span><div class="bsn-list-body"' . $styleAttr . '>' . $inlineHtml . '</div></div>';
        }

        return '<p class="bsn-p"' . $styleAttr . '>' . $inlineHtml . '</p>';
    }

    private static function renderParagraphRunsHtml(DOMElement $p): string {
        $out = '';
        foreach ($p->childNodes as $ch) {
            if (!$ch instanceof DOMElement) continue;
            if ($ch->namespaceURI === self::W_NS && $ch->localName === 'r') {
                $out .= self::renderSingleRunHtml($ch);
            } elseif ($ch->namespaceURI === self::W_NS && $ch->localName === 'hyperlink') {
                foreach ($ch->childNodes as $hc) {
                    if ($hc instanceof DOMElement && $hc->namespaceURI === self::W_NS && $hc->localName === 'r') {
                        $out .= self::renderSingleRunHtml($hc);
                    }
                }
            }
        }
        return $out;
    }

    private static function renderSingleRunHtml(DOMElement $r): string {
        $bold = false;
        $italic = false;
        $underline = false;
        $strike = false;
        $szHalfPt = 0;

        foreach ($r->childNodes as $ch) {
            if ($ch instanceof DOMElement && $ch->namespaceURI === self::W_NS && $ch->localName === 'rPr') {
                foreach ($ch->childNodes as $rp) {
                    if (!$rp instanceof DOMElement || $rp->namespaceURI !== self::W_NS) continue;
                    $ln = $rp->localName;
                    $val = $rp->getAttributeNS(self::W_NS, 'val') ?: $rp->getAttribute('w:val');
                    if ($ln === 'b' && $val !== '0' && $val !== 'false') $bold = true;
                    if ($ln === 'i' && $val !== '0' && $val !== 'false') $italic = true;
                    if ($ln === 'u' && $val !== 'none' && $val !== '0') $underline = true;
                    if ($ln === 'strike' && $val !== '0' && $val !== 'false') $strike = true;
                    if ($ln === 'sz' && (int)$val > 0) $szHalfPt = (int)$val;
                }
            }
        }

        $chunk = '';
        foreach ($r->childNodes as $ch) {
            if (!$ch instanceof DOMElement) continue;
            if ($ch->namespaceURI === self::W_NS) {
                if ($ch->localName === 't') {
                    $escaped = e($ch->textContent);
                    // Pertahankan spasi ganda jika ada
                    $escaped = preg_replace_callback('/ {2,}/', fn($m) => str_repeat('&nbsp;', strlen($m[0])), $escaped) ?? $escaped;
                    $chunk .= $escaped;
                } elseif ($ch->localName === 'tab') {
                    $chunk .= '&emsp;';
                } elseif ($ch->localName === 'br') {
                    $chunk .= '<br>';
                }
            }
        }

        if ($chunk === '') {
            return '';
        }

        if ($strike)    $chunk = '<s>' . $chunk . '</s>';
        if ($underline) $chunk = '<u>' . $chunk . '</u>';
        if ($italic)    $chunk = '<em>' . $chunk . '</em>';
        if ($bold)      $chunk = '<strong>' . $chunk . '</strong>';

        if ($szHalfPt >= 28) {
            $pt = round($szHalfPt / 2, 1);
            $chunk = '<span style="font-size:' . $pt . 'pt;">' . $chunk . '</span>';
        } elseif ($szHalfPt > 0 && $szHalfPt <= 16) {
            $pt = round($szHalfPt / 2, 1);
            $chunk = '<span style="font-size:' . $pt . 'pt;">' . $chunk . '</span>';
        }

        return $chunk;
    }

    private static function extractCleanParagraphPlainText(DOMElement $p): string {
        $buf = '';
        foreach ($p->childNodes as $ch) {
            if ($ch instanceof DOMElement && $ch->namespaceURI === self::W_NS && $ch->localName === 'r') {
                foreach ($ch->childNodes as $rc) {
                    if ($rc instanceof DOMElement && $rc->namespaceURI === self::W_NS) {
                        if ($rc->localName === 't') $buf .= $rc->textContent;
                        elseif ($rc->localName === 'tab') $buf .= ' ';
                    }
                }
            }
        }
        return $buf;
    }

    private static function paragraphHasMeteraiDrawing(DOMElement $p): bool {
        $hasDraw = (
            $p->getElementsByTagNameNS(self::W_NS, 'drawing')->length > 0 ||
            $p->getElementsByTagNameNS(self::MC_NS, 'AlternateContent')->length > 0 ||
            $p->getElementsByTagNameNS(self::W_NS, 'pict')->length > 0
        );
        if (!$hasDraw) return false;
        return preg_match('/m[ae]terai/iu', $p->textContent) === 1;
    }

    private static function renderTableToHtml(DOMElement $tbl, array $numMap, array &$counters, string $docKey): string {
        // 1. Hitung proporsi lebar kolom dari <w:tblGrid>
        $colWidths = [];
        foreach ($tbl->childNodes as $ch) {
            if ($ch instanceof DOMElement && $ch->namespaceURI === self::W_NS && $ch->localName === 'tblGrid') {
                foreach ($ch->childNodes as $gc) {
                    if ($gc instanceof DOMElement && $gc->namespaceURI === self::W_NS && $gc->localName === 'gridCol') {
                        $w = (int)($gc->getAttributeNS(self::W_NS, 'w') ?: $gc->getAttribute('w:w') ?: 0);
                        $colWidths[] = max(1, $w);
                    }
                }
            }
        }
        $totalGridW = array_sum($colWidths) ?: 1;

        // 2. Cek apakah tabel memiliki garis border
        $tblHasBorder = self::elementHasVisibleBorders($tbl, 'tblPr', 'tblBorders');
        if (in_array($docKey, ['checklist_bsn', 'denah_lokasi'], true)) {
            $tblHasBorder = true;
        }

        // 3. Kumpulkan matriks baris & sel untuk menghitung vMerge -> rowspan
        $rowsData = [];
        foreach ($tbl->childNodes as $tr) {
            if (!$tr instanceof DOMElement || $tr->namespaceURI !== self::W_NS || $tr->localName !== 'tr') {
                continue;
            }
            $trHeightPt = 0.0;
            foreach ($tr->childNodes as $trCh) {
                if ($trCh instanceof DOMElement && $trCh->namespaceURI === self::W_NS && $trCh->localName === 'trPr') {
                    foreach ($trCh->childNodes as $tp) {
                        if ($tp instanceof DOMElement && $tp->namespaceURI === self::W_NS && $tp->localName === 'trHeight') {
                            $hVal = (int)($tp->getAttributeNS(self::W_NS, 'val') ?: $tp->getAttribute('w:val') ?: 0);
                            if ($hVal > 0) {
                                $trHeightPt = round($hVal / 20, 1);
                            }
                        }
                    }
                }
            }
            if ($docKey === 'denah_lokasi' && $trHeightPt < 180) {
                // Pastikan kotak gambar DENAH RUMAH & DENAH TEMPAT BEKERJA tinggi proporsional di A4
                $cellCount = $tr->getElementsByTagNameNS(self::W_NS, 'tc')->length;
                if ($cellCount === 1 && trim($tr->textContent) === '') {
                    $trHeightPt = 250.0;
                }
            }

            $cells = [];
            $colCursor = 0;
            foreach ($tr->childNodes as $tc) {
                if (!$tc instanceof DOMElement || $tc->namespaceURI !== self::W_NS || $tc->localName !== 'tc') {
                    continue;
                }
                $gridSpan = 1;
                $vMerge = null; // null | 'restart' | 'continue'
                $cellBorder = self::elementHasVisibleBorders($tc, 'tcPr', 'tcBorders');

                foreach ($tc->childNodes as $tcCh) {
                    if ($tcCh instanceof DOMElement && $tcCh->namespaceURI === self::W_NS && $tcCh->localName === 'tcPr') {
                        foreach ($tcCh->childNodes as $tcp) {
                            if (!$tcp instanceof DOMElement || $tcp->namespaceURI !== self::W_NS) continue;
                            if ($tcp->localName === 'gridSpan') {
                                $gridSpan = max(1, (int)($tcp->getAttributeNS(self::W_NS, 'val') ?: $tcp->getAttribute('w:val') ?: 1));
                            } elseif ($tcp->localName === 'vMerge') {
                                $vmVal = $tcp->getAttributeNS(self::W_NS, 'val') ?: $tcp->getAttribute('w:val');
                                $vMerge = ($vmVal === 'restart') ? 'restart' : 'continue';
                            }
                        }
                    }
                }

                $cells[] = [
                    'tc'         => $tc,
                    'col_start'  => $colCursor,
                    'colspan'    => $gridSpan,
                    'vmerge'     => $vMerge,
                    'rowspan'    => 1,
                    'skip'       => ($vMerge === 'continue'),
                    'has_border' => $tblHasBorder || $cellBorder,
                ];
                $colCursor += $gridSpan;
            }

            $rowsData[] = [
                'height_pt' => $trHeightPt,
                'cells'     => $cells,
            ];
        }

        // Hitung rowspan untuk vMerge=continue
        $rCount = count($rowsData);
        for ($r = 0; $r < $rCount; $r++) {
            foreach ($rowsData[$r]['cells'] as $cIdx => &$cell) {
                if ($cell['skip']) continue;
                if ($cell['vmerge'] === 'restart') {
                    $span = 1;
                    for ($nr = $r + 1; $nr < $rCount; $nr++) {
                        $matched = false;
                        foreach ($rowsData[$nr]['cells'] as $nCell) {
                            if ($nCell['col_start'] === $cell['col_start'] && $nCell['vmerge'] === 'continue') {
                                $span++;
                                $matched = true;
                                break;
                            }
                        }
                        if (!$matched) break;
                    }
                    $cell['rowspan'] = $span;
                }
            }
            unset($cell);
        }

        $tblClass = $tblHasBorder ? 'bsn-tbl bsn-tbl-bordered' : 'bsn-tbl bsn-tbl-plain';
        $html = '<table class="' . $tblClass . '">';
        if (!empty($colWidths)) {
            $html .= '<colgroup>';
            foreach ($colWidths as $cw) {
                $pct = round(($cw / $totalGridW) * 100, 2);
                $html .= '<col style="width:' . $pct . '%;">';
            }
            $html .= '</colgroup>';
        }
        $html .= '<tbody>';

        foreach ($rowsData as $row) {
            $trStyle = $row['height_pt'] > 0 ? ' style="height:' . $row['height_pt'] . 'pt;"' : '';
            $html .= '<tr' . $trStyle . '>';
            foreach ($row['cells'] as $cell) {
                if ($cell['skip']) continue;
                $attrs = '';
                if ($cell['colspan'] > 1) $attrs .= ' colspan="' . $cell['colspan'] . '"';
                if ($cell['rowspan'] > 1) $attrs .= ' rowspan="' . $cell['rowspan'] . '"';

                $tdStyles = [];
                if ($cell['has_border'] && !$tblHasBorder) {
                    $tdStyles[] = 'border:1px solid #000';
                    $tdStyles[] = 'padding:4px 6px';
                }
                if ($row['height_pt'] >= 150) {
                    $tdStyles[] = 'height:' . $row['height_pt'] . 'pt';
                }
                if (!empty($tdStyles)) {
                    $attrs .= ' style="' . implode(';', $tdStyles) . '"';
                }

                $cellInner = '';
                foreach ($cell['tc']->childNodes as $tcChild) {
                    if (!$tcChild instanceof DOMElement) continue;
                    if ($tcChild->namespaceURI === self::W_NS && $tcChild->localName === 'p') {
                        $cellInner .= self::renderParagraphToHtml($tcChild, $numMap, $counters, true);
                    } elseif ($tcChild->namespaceURI === self::W_NS && $tcChild->localName === 'tbl') {
                        $cellInner .= self::renderTableToHtml($tcChild, $numMap, $counters, $docKey);
                    }
                }
                $html .= '<td' . $attrs . '>' . $cellInner . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        return $html;
    }

    private static function elementHasVisibleBorders(DOMElement $el, string $prName, string $bordersName): bool {
        foreach ($el->childNodes as $ch) {
            if ($ch instanceof DOMElement && $ch->namespaceURI === self::W_NS && $ch->localName === $prName) {
                foreach ($ch->childNodes as $bNode) {
                    if ($bNode instanceof DOMElement && $bNode->namespaceURI === self::W_NS && $bNode->localName === $bordersName) {
                        foreach ($bNode->childNodes as $side) {
                            if ($side instanceof DOMElement && $side->namespaceURI === self::W_NS) {
                                $val = $side->getAttributeNS(self::W_NS, 'val') ?: $side->getAttribute('w:val');
                                if ($val !== '' && $val !== 'none' && $val !== 'nil') {
                                    return true;
                                }
                            }
                        }
                    }
                }
            }
        }
        return false;
    }

    /**
     * Render khusus HTML Permohonan iDEB (SLIK OJK) lengkap dengan logo Danantara & Bank Syariah Nasional.
     */
    private static function renderIdebSheetHtml(array $d, string $bsnLogoRel, string $danantaraLogoRel): string {
        $leftLogo = $danantaraLogoRel !== ''
            ? '<img src="' . e($danantaraLogoRel) . '" alt="Danantara Indonesia" class="ideb-logo-left">'
            : '<strong style="font-size:13pt;">Danantara Indonesia</strong>';
        $rightLogo = $bsnLogoRel !== ''
            ? '<img src="' . e($bsnLogoRel) . '" alt="Bank Syariah Nasional" class="ideb-logo-right">'
            : '<strong style="font-size:13pt;color:#0f3d5e;">BSN • Bank Syariah Nasional</strong>';

        $namaPas   = $d['has_pasangan'] ? e($d['nama_pasangan']) : '';
        $ktpPas    = $d['has_pasangan'] ? e($d['ktp_pasangan']) : '';
        $npwpPas   = $d['has_pasangan'] ? e($d['npwp_pasangan']) : '';
        $ttlPas    = $d['has_pasangan'] ? e($d['ttl_pasangan']) : '';
        $alamatPas = $d['has_pasangan'] ? e($d['alamat_pasangan']) : '';

        return '
        <div class="ideb-logo-bar">
            <div>' . $leftLogo . '</div>
            <div style="text-align:right;">' . $rightLogo . '</div>
        </div>
        <div class="ideb-main-title">PERMOHONAN iDEB (SLIK OJK)</div>
        <p class="ideb-intro">Mohon dapat diterbitkan Ideb (SLIK OJK) sebagai syarat pengajuan pembiayaan di Bank Syariah Nasional dengan data sebagai berikut :</p>
        <div class="ideb-sec-label">PEMOHON</div>
        <table class="ideb-table">
            <tr><td class="i-lbl">Nama</td><td class="i-sep">:</td><td class="i-val"><strong>' . e($d['nama_lengkap']) . '</strong></td></tr>
            <tr><td class="i-lbl">No.KTP/Identitas</td><td class="i-sep">:</td><td class="i-val">' . e($d['no_ktp']) . '</td></tr>
            <tr><td class="i-lbl">NPWP</td><td class="i-sep">:</td><td class="i-val">' . e($d['npwp']) . '</td></tr>
            <tr><td class="i-lbl">Tempat/tanggal/bulan/tahun lahir</td><td class="i-sep">:</td><td class="i-val">' . e($d['ttl']) . '</td></tr>
            <tr class="row-alamat"><td class="i-lbl">Alamat lengkap</td><td class="i-sep">:</td><td class="i-val">' . e($d['alamat']) . '</td></tr>
        </table>
        <div class="ideb-sec-label">PASANGAN</div>
        <table class="ideb-table">
            <tr><td class="i-lbl">Nama</td><td class="i-sep">:</td><td class="i-val"><strong>' . $namaPas . '</strong></td></tr>
            <tr><td class="i-lbl">No.KTP/Identitas</td><td class="i-sep">:</td><td class="i-val">' . $ktpPas . '</td></tr>
            <tr><td class="i-lbl">NPWP</td><td class="i-sep">:</td><td class="i-val">' . $npwpPas . '</td></tr>
            <tr><td class="i-lbl">Tempat/tanggal/bulan/tahun lahir</td><td class="i-sep">:</td><td class="i-val">' . $ttlPas . '</td></tr>
            <tr class="row-alamat"><td class="i-lbl">Alamat lengkap</td><td class="i-sep">:</td><td class="i-val">' . $alamatPas . '</td></tr>
        </table>
        <div class="ideb-closing">
            <p>Terlampir kami sampaikan photo copy KTP pemohon dan pasangan pemohon.</p>
            <p>Demikian disampaikan atas kerjasamanya diucapkan terima kasih</p>
        </div>
        <div class="ideb-date-line"><span class="js-surat-date">' . e($d['tanggal_surat']) . '</span></div>
        <table class="ideb-sign-table">
            <tr>
                <td style="width:32%;" class="js-ideb-fs-bracket">' . e($d['fs_bracket']) . '</td>
                <td style="width:36%;">(                                       )</td>
                <td style="width:32%;">(                           )</td>
            </tr>
            <tr>
                <td>Financing Service</td>
                <td><strong>' . e($d['nama_lengkap']) . '</strong></td>
                <td><strong>' . $namaPas . '</strong></td>
            </tr>
        </table>
        <div class="ideb-footnote">*DENGAN DI TANDA TANGANINYA PERMOHONAN INI OLEH PEMOHON DAN PASANGAN (CALON NASABAH BANK SYARIAH NASIONAL) MAKA NASABAH MENGIZINKAN UNTUK DI CEK RIWAYAT DAN DATA PINJAMANNYA OLEH BANK SYARIAH NASIONAL*</div>';
    }

    /**
     * Konversi angka Rupiah ke teks terbilang bahasa Indonesia.
     */
    public static function terbilangIndo(int $angka): string {
        $angka = abs($angka);
        $baca = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
        if ($angka < 12) {
            return $baca[$angka];
        }
        if ($angka < 20) {
            return self::terbilangIndo($angka - 10) . ' belas';
        }
        if ($angka < 100) {
            return trim(self::terbilangIndo((int)floor($angka / 10)) . ' puluh ' . self::terbilangIndo($angka % 10));
        }
        if ($angka < 200) {
            return trim('seratus ' . self::terbilangIndo($angka - 100));
        }
        if ($angka < 1000) {
            return trim(self::terbilangIndo((int)floor($angka / 100)) . ' ratus ' . self::terbilangIndo($angka % 100));
        }
        if ($angka < 2000) {
            return trim('seribu ' . self::terbilangIndo($angka - 1000));
        }
        if ($angka < 1000000) {
            return trim(self::terbilangIndo((int)floor($angka / 1000)) . ' ribu ' . self::terbilangIndo($angka % 1000));
        }
        if ($angka < 1000000000) {
            return trim(self::terbilangIndo((int)floor($angka / 1000000)) . ' juta ' . self::terbilangIndo($angka % 1000000));
        }
        return trim(self::terbilangIndo((int)floor($angka / 1000000000)) . ' miliar ' . self::terbilangIndo($angka % 1000000000));
    }
}