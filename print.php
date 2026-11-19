<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';
requireLogin();

$ids = [];
if (!empty($_GET['ids'])) {
    foreach (explode(',', (string)$_GET['ids']) as $part) {
        $v = (int)trim($part);
        if ($v > 0) {
            $ids[] = $v;
        }
    }
} elseif (!empty($_GET['id'])) {
    $v = (int)$_GET['id'];
    if ($v > 0) {
        $ids[] = $v;
    }
}
$ids = array_values(array_unique($ids));
if (empty($ids)) {
    die('Data nasabah tidak ditemukan.');
}

$tpl = $_GET['tpl'] ?? 'ideb';
if (!in_array($tpl, ['ideb', 'biodata', 'bast', 'tandaterima'], true)) {
    $tpl = 'ideb';
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("
    SELECT *
    FROM nasabah
    WHERE id IN ($placeholders)
    ORDER BY CAST(no_urut AS INTEGER) ASC, id ASC
");
$stmt->execute($ids);
$rows = $stmt->fetchAll();

if (empty($rows)) {
    die('Data nasabah tidak ditemukan.');
}

$settings      = Database::getSettings($pdo);
$defaultKota   = trim((string)($settings['default_kota_surat'] ?? 'Banjar'));
$todayIndo     = tanggalIndoSekarang($defaultKota);
$customTanggal = trim((string)($_GET['tgl'] ?? ''));
$namaFs        = trim((string)($_GET['fs'] ?? ''));
$catalog = BsnSuratService::getCatalog();
$settings = Database::getSettings($pdo);
$defaultKota = trim((string)($settings['default_kota_surat'] ?? 'Banjar'));
$todayIndo = tanggalIndoSekarang($defaultKota);

$bsnLogoRel       = IdebDocxService::ensureBsnLogoAsset();
$danantaraLogoRel = file_exists(__DIR__ . '/assets/img/ideb_image2.png') ? 'assets/img/ideb_image2.png' : '';
$overrides = [
    'tgl'             => trim((string)($_GET['tgl'] ?? '')),
    'fs'              => trim((string)($_GET['fs'] ?? '')),
    'sp3_no'          => trim((string)($_GET['sp3_no'] ?? '')),
    'sp3_tgl'         => trim((string)($_GET['sp3_tgl'] ?? '')),
    'tanggal_akad'    => trim((string)($_GET['tanggal_akad'] ?? '')),
    'no_rek_tabungan' => trim((string)($_GET['no_rek_tabungan'] ?? '')),
    'no_rek_kpr'      => trim((string)($_GET['no_rek_kpr'] ?? '')),
];

// Resolusi mode template: mendukung ?docs=k1,k2 | ?tpl=<key_surat_bsn> | alias lama (ideb, bast) | ringkasan internal (biodata, tandaterima)
$rawDocsParam = trim((string)($_GET['docs'] ?? ''));
$rawTplParam  = trim((string)($_GET['tpl'] ?? ''));

$isInternalSummary = false;
$activeDocKeys = [];
$switcherValue = '';

if ($rawDocsParam !== '') {
    $activeDocKeys = BsnSuratService::normalizeDocKeys($rawDocsParam);
    if ($rawDocsParam === 'all' || count($activeDocKeys) === count($catalog)) {
        $switcherValue = 'docs:all';
    } elseif ($rawDocsParam === 'wajib') {
        $switcherValue = 'docs:wajib';
    } elseif (count($activeDocKeys) === 1) {
        $switcherValue = 'tpl:' . $activeDocKeys[0];
    } else {
        $switcherValue = 'docs:custom';
    }
} else {
    if ($rawTplParam === '' || $rawTplParam === 'ideb') {
        $rawTplParam = 'ideb_slik';
    } elseif ($rawTplParam === 'bast') {
        $rawTplParam = 'bast_tapak';
    }

    if (in_array($rawTplParam, ['biodata', 'tandaterima'], true)) {
        $isInternalSummary = true;
        $switcherValue = 'internal:' . $rawTplParam;
    } elseif ($rawTplParam === 'all') {
        $activeDocKeys = BsnSuratService::normalizeDocKeys('all');
        $switcherValue = 'docs:all';
    } elseif ($rawTplParam === 'wajib') {
        $activeDocKeys = BsnSuratService::normalizeDocKeys('wajib');
        $switcherValue = 'docs:wajib';
    } elseif (isset($catalog[$rawTplParam])) {
        $activeDocKeys = [$rawTplParam];
        $switcherValue = 'tpl:' . $rawTplParam;
    } else {
        $activeDocKeys = ['ideb_slik'];
        $switcherValue = 'tpl:ideb_slik';
    }
}

$idQueryParam = count($rows) === 1
    ? 'id=' . (int)$rows[0]['id']
    : 'ids=' . implode(',', array_map(fn($item) => (int)$item['id'], $rows));

$firstPrepared = IdebDocxService::prepareData($rows[0], $defaultKota, $customTanggal, $namaFs);
$initialDateInput = $customTanggal !== ''
    ? $customTanggal
    : (count($rows) === 1 ? $firstPrepared['tanggal_surat'] : $todayIndo);
$firstResolved = BsnSuratService::resolveNasabahData($rows[0], $pdo, $overrides);
$initialDateInput = $overrides['tgl'] !== ''
    ? $overrides['tgl']
    : (count($rows) === 1 ? $firstResolved['tanggal_surat'] : $todayIndo);

$hasIdebSheet = !$isInternalSummary && in_array('ideb_slik', $activeDocKeys, true);

function pVal(?string $val, string $fallback = '............................................'): string {
    $v = trim((string)$val);
    return $v !== '' ? e($v) : $fallback;
}

$titleLabel = count($rows) === 1
    ? $rows[0]['nama_lengkap']
    : (count($rows) . ' Nasabah Terpilih');

if ($isInternalSummary) {
    $docHeaderLabel = $rawTplParam === 'tandaterima' ? 'Tanda Terima Berkas' : 'Biodata Pengajuan FLPP';
} elseif (count($activeDocKeys) === 1) {
    $docHeaderLabel = $catalog[$activeDocKeys[0]]['short_title'];
} else {
    $docHeaderLabel = 'Paket ' . count($activeDocKeys) . ' Surat KPR BSN';
}

$docsCsvForDownload = implode(',', $activeDocKeys);
$extraQsParts = [];
foreach ($overrides as $ok => $ov) {
    if ($ov !== '') {
        $extraQsParts[] = urlencode($ok) . '=' . urlencode($ov);
    }
}
$extraQs = !empty($extraQsParts) ? '&' . implode('&', $extraQsParts) : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak <?= $tpl === 'ideb' ? 'Permohonan iDEB (SLIK OJK)' : 'Berkas' ?> — <?= e($titleLabel) ?></title>
    <title>Cetak <?= e($docHeaderLabel) ?> — <?= e($titleLabel) ?></title>
    <style>
        @page {
            size: A4 portrait;
            margin: 14mm 18mm;
            margin: 13mm 16mm;
        }
        body {
            margin: 0;
            padding: 0;
            background: #cbd5e1;
            font-family: 'Times New Roman', Times, serif;
            color: #000;
            font-size: 12pt;
            line-height: 1.5;
            font-size: 11pt;
            line-height: 1.42;
        }
        .print-toolbar {
            position: sticky;
            top: 0;
            background: #0f172a;
            color: #fff;
            padding: 10px 20px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 13px;
            z-index: 50;
            box-shadow: 0 4px 14px rgba(0,0,0,0.25);
            box-shadow: 0 4px 14px rgba(0,0,0,0.28);
        }
        .toolbar-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        .toolbar-btns {
            display: flex;
            gap: 7px;
            flex-wrap: wrap;
            align-items: center;
        }
        .t-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 7px 12px;
            border-radius: 7px;
            border: 1px solid rgba(255,255,255,0.2);
            background: rgba(255,255,255,0.08);
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            font-size: 12px;
            line-height: 1.2;
            font-family: inherit;
        }
        .t-btn:hover {
            background: rgba(255,255,255,0.16);
        }
        .t-btn.active {
            background: #2563eb;
            border-color: #3b82f6;
        }
        .t-btn.docx-btn {
            background: #1d4ed8;
            border-color: #60a5fa;
        }
        .t-btn.print-main {
            background: #059669;
            border-color: #10b981;
        }
        .t-select {
            padding: 6.5px 10px;
            border-radius: 7px;
            border: 1px solid #475569;
            background: #1e293b;
            color: #fff;
            font-size: 12.5px;
            font-weight: 600;
            max-width: 370px;
            cursor: pointer;
        }
        .ideb-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            padding-top: 8px;
            border-top: 1px solid rgba(255,255,255,0.12);
            font-size: 12px;
        }
        .ideb-controls label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #cbd5e1;
            font-weight: 600;
        }
        .ideb-input {
            padding: 5px 9px;
            border-radius: 6px;
            border: 1px solid rgba(255,255,255,0.25);
            background: #1e293b;
            color: #fff;
            font-size: 12px;
            min-width: 185px;
            min-width: 175px;
        }

        /* ====== KERTAS A4 ====== */
        .paper-badge {
            width: 210mm;
            margin: 18px auto -14px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 11.5px;
            font-weight: 700;
            color: #334155;
            display: flex;
            justify-content: space-between;
            padding: 0 4px;
            box-sizing: border-box;
        }
        .paper {
            width: 210mm;
            min-height: 297mm;
            margin: 22px auto;
            margin: 20px auto;
            background: #fff;
            padding: 20mm 22mm;
            padding: 18mm 20mm;
            box-sizing: border-box;
            box-shadow: 0 10px 30px rgba(0,0,0,0.22);
        }
        .paper.paper-tight {
            padding: 9mm 13mm;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8.3pt;
            line-height: 1.18;
        }
        .paper.paper-ideb {
            padding: 18mm 22mm 16mm;
            font-family: 'Arial Narrow', Arial, Helvetica, sans-serif;
            font-size: 10.5pt;
            line-height: 1.32;
            color: #000;
        }

        /* ====== ELEMEN SURAT OOXML BSN ====== */
        .bsn-p {
            margin: 0 0 4.5pt;
        }
        .paper-tight .bsn-p {
            margin: 0 0 1.5pt;
        }
        .bsn-spacer {
            height: 7pt;
        }
        .paper-tight .bsn-spacer {
            height: 2pt;
        }
        .bsn-empty-line {
            min-height: 10pt;
            line-height: 1;
        }
        .paper-tight .bsn-empty-line {
            min-height: 3pt;
        }
        .bsn-kv-line {
            display: flex;
            align-items: flex-start;
            margin: 0 0 2.5pt;
        }
        .bsn-kv-lbl {
            width: 48mm;
            flex-shrink: 0;
        }
        .bsn-kv-sep {
            width: 5mm;
            flex-shrink: 0;
            text-align: center;
        }
        .bsn-kv-val {
            flex: 1;
            min-width: 0;
        }
        .bsn-list-item {
            display: flex;
            align-items: flex-start;
            gap: 6px;
            margin: 0 0 4.5pt;
        }
        .bsn-list-num {
            width: 6mm;
            flex-shrink: 0;
        }
        .bsn-list-body {
            flex: 1;
            min-width: 0;
        }
        .bsn-meterai-box {
            display: inline-block;
            border: 1px dashed #334155;
            padding: 6px 10px;
            font-size: 8.5pt;
            line-height: 1.25;
            text-align: center;
            color: #334155;
            margin: 4px auto;
            min-width: 22mm;
        }
        table.bsn-tbl {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin: 3pt 0 6pt;
        }
        table.bsn-tbl td {
            vertical-align: top;
            word-wrap: break-word;
        }
        table.bsn-tbl-bordered td {
            border: 1px solid #000;
            padding: 3.5px 6px;
        }
        .paper-tight table.bsn-tbl-bordered td {
            padding: 1.5px 4px;
        }
        table.bsn-tbl-plain td {
            border: none;
            padding: 1.5px 3px;
        }

        /* ====== STYLE KHUSUS PERMOHONAN iDEB (SLIK OJK) BSN ====== */
        .ideb-logo-bar {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 8mm;
            min-height: 22mm;
        }
        .ideb-logo-left {
            width: 47mm;
            height: auto;
            object-fit: contain;
            margin-top: 2mm;
        }
        .ideb-logo-right {
            width: 33mm;
            height: auto;
            object-fit: contain;
        }
        .ideb-main-title {
            text-align: center;
            font-size: 18pt;
            font-weight: 700;
            margin: 0 0 8mm;
            letter-spacing: 0.2px;
        }
        .ideb-intro {
            margin: 0 0 5mm;
            font-size: 10.5pt;
        }
        .ideb-sec-label {
            font-size: 10.5pt;
            text-decoration: underline;
            margin: 4mm 0 2.5mm;
            font-weight: 400;
        }
        table.ideb-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 4.5mm;
        }
        table.ideb-table td {
            border: 1px solid #000;
            padding: 4.5px 7px;
            font-size: 10.5pt;
            vertical-align: middle;
            word-wrap: break-word;
        }
        table.ideb-table td.i-lbl {
            width: 34.4%;
            font-weight: 700;
            padding-bottom: 9px;
        }
        table.ideb-table td.i-sep {
            width: 3.1%;
            text-align: center;
            font-weight: 700;
        }
        table.ideb-table td.i-val {
            width: 62.5%;
        }
        table.ideb-table tr.row-alamat td.i-lbl {
            padding-top: 12px;
            padding-bottom: 14px;
        }
        table.ideb-table tr.row-alamat td.i-val {
            vertical-align: middle;
        }
        .ideb-closing p {
            margin: 0 0 2.5mm;
            font-size: 10.5pt;
        }
        .ideb-date-line {
            text-align: right;
            margin-top: 7mm;
            margin-bottom: 18mm;
            font-size: 10.5pt;
        }
        table.ideb-sign-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 4mm;
        }
        table.ideb-sign-table td {
            border: none;
            text-align: center;
            font-size: 10.5pt;
            padding: 1px 4px;
            vertical-align: top;
        }
        .ideb-footnote {
            font-size: 7pt;
            font-weight: 700;
            text-transform: uppercase;
            line-height: 1.35;
            margin-top: 2mm;
        }

        /* ====== STYLE DOKUMEN LAINNYA (BIODATA, BAST, TANDA TERIMA) ====== */
        /* ====== STYLE RINGKASAN INTERNAL (BIODATA & TANDA TERIMA) ====== */
        .doc-header {
            text-align: center;
            border-bottom: 3px double #000;
            padding-bottom: 10px;
            margin-bottom: 18px;
        }
        .doc-header h1 {
            margin: 0;
            font-size: 15pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .doc-header p {
            margin: 3px 0 0;
            font-size: 10.5pt;
        }
        .doc-title {
            text-align: center;
            margin: 14px 0 18px;
        }
        .doc-title h2 {
            margin: 0;
            font-size: 13.5pt;
            text-decoration: underline;
            text-transform: uppercase;
        }
        .doc-title div {
            font-size: 11pt;
            margin-top: 2px;
        }
        table.tbl-info {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        table.tbl-info td {
            padding: 3.5px 4px;
            vertical-align: top;
            font-size: 11.5pt;
            font-size: 11pt;
        }
        table.tbl-info td.lbl {
            width: 34%;
        }
        table.tbl-info td.sep {
            width: 3%;
            text-align: center;
        }
        table.tbl-info td.lbl { width: 34%; }
        table.tbl-info td.sep { width: 3%; text-align: center; }
        table.tbl-bordered {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.tbl-bordered th, table.tbl-bordered td {
            border: 1px solid #000;
            padding: 7px 10px;
            font-size: 11pt;
            font-size: 10.5pt;
            text-align: left;
        }
        table.tbl-bordered th {
            background: #f1f5f9;
        }
        table.tbl-bordered th { background: #f1f5f9; }
        .section-sub {
            font-weight: bold;
            font-size: 11.5pt;
            font-size: 11pt;
            margin: 14px 0 6px;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 2px;
        }
        .sign-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            margin-top: 32px;
            text-align: center;
        }
        .sign-space {
            height: 72px;
        }
        .sign-space { height: 72px; }

        [contenteditable="true"]:focus {
            outline: 2px dashed #3b82f6;
            background: #f8fafc;
        }

        @media print {
            body {
                background: #fff;
            }
            .print-toolbar {
            .print-toolbar, .paper-badge {
                display: none !important;
            }
            .paper, .paper.paper-ideb {
            .paper, .paper.paper-ideb, .paper.paper-tight {
                margin: 0;
                box-shadow: none;
                width: 100%;
                min-height: auto;
                padding: 4mm 4mm;
                padding: 3mm 4mm;
                page-break-after: always;
                break-after: page;
            }
            .paper.paper-tight {
                padding: 0mm 2mm;
                font-size: 8pt;
                line-height: 1.14;
            }
            .paper:last-of-type {
                page-break-after: auto;
                break-after: auto;
            }
        }
    </style>
</head>
<body>

<div class="print-toolbar">
    <div class="toolbar-row">
        <div>
            <strong>Preview Cetak Berkas:</strong> <?= e($titleLabel) ?>
            <span style="opacity:0.75;margin-left:8px;font-size:12px;">(Klik langsung pada kertas bila ingin mengedit teks sebelum Print / PDF)</span>
            <strong>Preview Cetak:</strong> <?= e($titleLabel) ?>
            <span style="opacity:0.75;margin-left:8px;font-size:12px;">(Teks di kertas bisa diklik & diedit langsung sebelum Print / PDF)</span>
        </div>

        <div class="toolbar-btns">
            <a href="print.php?<?= e($idQueryParam) ?>&tpl=ideb" class="t-btn <?= $tpl === 'ideb' ? 'active' : '' ?>">1. SLIK OJK (iDEB BSN)</a>
            <a href="print.php?<?= e($idQueryParam) ?>&tpl=biodata" class="t-btn <?= $tpl === 'biodata' ? 'active' : '' ?>">2. Biodata FLPP</a>
            <a href="print.php?<?= e($idQueryParam) ?>&tpl=bast" class="t-btn <?= $tpl === 'bast' ? 'active' : '' ?>">3. Draft BAST</a>
            <a href="print.php?<?= e($idQueryParam) ?>&tpl=tandaterima" class="t-btn <?= $tpl === 'tandaterima' ? 'active' : '' ?>">4. Tanda Terima</a>
            <?php if (count($rows) === 1): ?>
                <a href="index.php?page=surat&id=<?= (int)$rows[0]['id'] ?>" class="t-btn" title="Buka halaman pemilihan lengkap 18 surat BSN">
                    📑 Menu 18 Surat BSN
                </a>
            <?php endif; ?>

            <select class="t-select" onchange="switchPrintTemplate(this.value)" title="Ganti jenis surat yang ditampilkan">
                <?php if ($switcherValue === 'docs:custom'): ?>
                    <option value="docs:custom" selected>📦 Paket Pilihan (<?= count($activeDocKeys) ?> Surat BSN)</option>
                <?php endif; ?>
                <optgroup label="Paket Bundel KPR BSN">
                    <option value="docs:wajib" <?= $switcherValue === 'docs:wajib' ? 'selected' : '' ?>>📚 Paket Utama Pemberkasan (16 Surat Wajib)</option>
                    <option value="docs:all" <?= $switcherValue === 'docs:all' ? 'selected' : '' ?>>📚 Seluruh Bundel Lengkap (18 Dokumen BSN)</option>
                </optgroup>
                <optgroup label="Masing-Masing Surat Resmi FLPP BSN (01–18)">
                    <?php foreach ($catalog as $dKey => $dMeta): ?>
                        <option value="tpl:<?= e($dKey) ?>" <?= $switcherValue === 'tpl:' . $dKey ? 'selected' : '' ?>>
                            <?= e($dMeta['code'] . '. ' . $dMeta['short_title']) ?><?= !empty($dMeta['is_conditional']) ? ' (Kondisional)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
                <optgroup label="Ringkasan Administrasi Internal">
                    <option value="internal:biodata" <?= $switcherValue === 'internal:biodata' ? 'selected' : '' ?>>📄 Biodata Lengkap Pengajuan FLPP</option>
                    <option value="internal:tandaterima" <?= $switcherValue === 'internal:tandaterima' ? 'selected' : '' ?>>📋 Lembar Kendali & Tanda Terima</option>
                </optgroup>
            </select>

            <button type="button" onclick="window.print()" class="t-btn print-main">🖨️ Print / Simpan PDF</button>
        </div>
    </div>

    <?php if ($tpl === 'ideb'): ?>
    <div class="ideb-controls">
    <?php if (!$isInternalSummary): ?>
    <form method="get" action="print.php" class="ideb-controls" style="margin:0;">
        <?php if (count($rows) === 1): ?>
            <input type="hidden" name="id" value="<?= (int)$rows[0]['id'] ?>">
        <?php else: ?>
            <input type="hidden" name="ids" value="<?= e(implode(',', array_map(fn($x) => (int)$x['id'], $rows))) ?>">
        <?php endif; ?>

        <?php if ($rawDocsParam !== ''): ?>
            <input type="hidden" name="docs" value="<?= e($rawDocsParam) ?>">
        <?php else: ?>
            <input type="hidden" name="tpl" value="<?= e($activeDocKeys[0] ?? 'ideb_slik') ?>">
        <?php endif; ?>

        <label>
            Kota & Tanggal Surat:
            <input type="text" id="ctrlTanggalIdeb" class="ideb-input" value="<?= e($initialDateInput) ?>" placeholder="Contoh: Banjar, 30 Oktober 2026">
            <input type="text" name="tgl" id="ctrlTanggalSurat" class="ideb-input" value="<?= e($initialDateInput) ?>" placeholder="Contoh: Banjar, 31 Oktober 2026">
        </label>
        <button type="button" class="t-btn" onclick="setTodayIdebDate('<?= e(addslashes($todayIndo)) ?>')" title="Isi tanggal hari ini">
        <button type="button" class="t-btn" onclick="document.getElementById('ctrlTanggalSurat').value='<?= e(addslashes($todayIndo)) ?>';this.form.submit();">
            Hari Ini
        </button>

        <?php if ($hasIdebSheet): ?>
        <label>
            Nama Petugas FS (Opsional):
            <input type="text" id="ctrlNamaFs" class="ideb-input" value="<?= e($namaFs) ?>" placeholder="Kosongkan jika tulis tangan">
            Petugas FS (SLIK):
            <input type="text" name="fs" id="ctrlNamaFs" class="ideb-input" value="<?= e($overrides['fs']) ?>" placeholder="Opsional">
        </label>
        <?php endif; ?>

        <button type="submit" class="t-btn">🔄 Terapkan ke Kertas</button>

        <div style="margin-left:auto;display:flex;gap:8px;flex-wrap:wrap;">
            <a id="btnDownloadDocx" href="ideb_docx.php?<?= e($idQueryParam) ?>" class="t-btn docx-btn" title="Download file Microsoft Word (.docx) sesuai template asli BSN">
            <a id="btnDownloadWordSurat"
               href="surat_docx.php?<?= e($idQueryParam) ?>&docs=<?= e(urlencode($docsCsvForDownload)) ?><?= e($extraQs) ?>"
               class="t-btn docx-btn"
               title="Download file Microsoft Word (.docx) sesuai tampilan surat ini">
                📥 Download File Word (.docx)
            </a>
            <?php if (count($rows) > 1): ?>
                <a id="btnDownloadZip" href="ideb_docx.php?<?= e($idQueryParam) ?>&mode=zip" class="t-btn" title="Download masing-masing nasabah sebagai file Word terpisah di dalam ZIP">
                    🗂️ Download Per Orang (.zip)
            <?php if (count($rows) === 1 && count($activeDocKeys) > 1): ?>
                <a href="surat_docx.php?<?= e($idQueryParam) ?>&docs=<?= e(urlencode($docsCsvForDownload)) ?>&mode=zip_surat<?= e($extraQs) ?>"
                   class="t-btn"
                   title="Download ZIP berisi file .docx terpisah untuk masing-masing surat">
                    🗂️ ZIP Per Surat
                </a>
            <?php elseif (count($rows) > 1): ?>
                <a href="surat_docx.php?<?= e($idQueryParam) ?>&docs=<?= e(urlencode($docsCsvForDownload)) ?>&mode=zip<?= e($extraQs) ?>"
                   class="t-btn"
                   title="Download ZIP berisi file .docx terpisah untuk masing-masing nasabah">
                    🗂️ ZIP Per Nasabah
                </a>
            <?php endif; ?>
        </div>
    </div>
    </form>
    <?php endif; ?>
</div>

<?php foreach ($rows as $r):
    $ideb = IdebDocxService::prepareData($r, $defaultKota, $customTanggal, $namaFs);
?>
<div class="paper <?= $tpl === 'ideb' ? 'paper-ideb' : '' ?>" contenteditable="true" spellcheck="false">
<?php if ($tpl === 'ideb'): ?>
    <!-- TEMPLATE UTAMA: PERMOHONAN iDEB (SLIK OJK) BANK SYARIAH NASIONAL -->
    <div class="ideb-logo-bar">
        <div>
            <?php if ($danantaraLogoRel !== ''): ?>
                <img src="<?= e($danantaraLogoRel) ?>" alt="Danantara Indonesia" class="ideb-logo-left">
            <?php else: ?>
                <strong style="font-size:13pt;">Danantara Indonesia</strong>
            <?php endif; ?>
        </div>
        <div style="text-align:right;">
            <?php if ($bsnLogoRel !== ''): ?>
                <img src="<?= e($bsnLogoRel) ?>" alt="Bank Syariah Nasional" class="ideb-logo-right">
            <?php else: ?>
                <strong style="font-size:13pt;color:#0f3d5e;">BSN • Bank Syariah Nasional</strong>
            <?php endif; ?>
        </div>
    </div>
<?php if ($isInternalSummary): ?>
    <?php foreach ($rows as $r): ?>
    <div class="paper" contenteditable="true" spellcheck="false">
        <?php if ($rawTplParam === 'tandaterima'): ?>
            <div class="doc-header">
                <h1>LEMBAR KENDALI & TANDA TERIMA BERKAS KPR FLPP</h1>
                <p>No. Urut Database: #<?= e($r['no_urut'] ?: $r['id']) ?> • Status: <?= e($r['status_proses']) ?></p>
            </div>

    <div class="ideb-main-title">PERMOHONAN iDEB (SLIK OJK)</div>
            <table class="tbl-info">
                <tr><td class="lbl">Nama Pemohon</td><td class="sep">:</td><td><strong><?= pVal($r['nama_lengkap']) ?></strong></td></tr>
                <tr><td class="lbl">NIK KTP / No. HP</td><td class="sep">:</td><td><?= pVal($r['no_ktp']) ?> / <?= pVal($r['no_hp']) ?></td></tr>
                <tr><td class="lbl">Nama Pasangan</td><td class="sep">:</td><td><?= pVal($r['nama_pasangan'], '-') ?></td></tr>
                <tr><td class="lbl">Perumahan & Blok</td><td class="sep">:</td><td><?= pVal($r['nama_perumahan']) ?> — Blok/No: <?= pVal($r['no_rumah']) ?></td></tr>
                <tr><td class="lbl">Pengembang (PT)</td><td class="sep">:</td><td><?= pVal($r['pengembang']) ?></td></tr>
            </table>

    <p class="ideb-intro">
        Mohon dapat diterbitkan Ideb (SLIK OJK) sebagai syarat pengajuan pembiayaan di Bank Syariah Nasional dengan data sebagai berikut :
    </p>
            <table class="tbl-bordered">
                <thead>
                    <tr>
                        <th style="width:40px;">No</th>
                        <th>Nama Dokumen / Kelengkapan Berkas</th>
                        <th style="width:120px;">Status</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>1</td><td>Fotokopi / Scan KTP Pemohon (& Pasangan)</td><td><?= (!empty($r['doc_ktp_check']) || !empty($r['file_ktp'])) ? '✓ Ada' : '☐ Belum' ?></td><td>NIK: <?= e($r['no_ktp'] ?: '-') ?></td></tr>
                    <tr><td>2</td><td>NPWP Pemohon</td><td><?= (!empty($r['npwp']) || !empty($r['no_ktp'])) ? '✓ Ada' : '☐ Belum' ?></td><td><?= e($r['npwp'] ?: $r['no_ktp'] ?: '-') ?></td></tr>
                    <tr><td>3</td><td>Pas Foto Berwarna</td><td><?= (!empty($r['doc_pas_foto_check']) || !empty($r['pas_foto'])) ? '✓ Ada' : '☐ Belum' ?></td><td></td></tr>
                    <tr><td>4</td><td>Foto Tempat Kerja / Usaha</td><td><?= (!empty($r['doc_foto_kerja_check']) || !empty($r['foto_tempat_kerja'])) ? '✓ Ada' : '☐ Belum' ?></td><td></td></tr>
                    <tr><td>5</td><td>Share Lokasi Tempat Kerja</td><td><?= (!empty($r['doc_lokasi_kerja_check']) || !empty($r['share_lokasi_kerja'])) ? '✓ Ada' : '☐ Belum' ?></td><td></td></tr>
                    <tr><td>6</td><td>Nomor Sertifikat SHGB & IMB/PBG</td><td><?= (!empty($r['no_shgb']) || !empty($r['no_imb'])) ? '✓ Ada' : '☐ Menyusul' ?></td><td>SHGB: <?= e($r['no_shgb'] ?: '-') ?></td></tr>
                    <tr><td>7</td><td>Dokumen PPJB</td><td><?= !empty($r['ppjb']) ? '✓ Ada' : '☐ Menyusul' ?></td><td><?= e($r['ppjb'] ?: '-') ?></td></tr>
                    <tr><td>8</td><td>Berita Acara Serah Terima (BAST)</td><td><?= getBastState($r['no_bast']) === 'lengkap' ? '✓ Lengkap' : '☐ Menyusul' ?></td><td><?= e($r['no_bast'] ?: '-') ?></td></tr>
                </tbody>
            </table>

    <div class="ideb-sec-label">PEMOHON</div>
    <table class="ideb-table">
        <tr>
            <td class="i-lbl">Nama</td>
            <td class="i-sep">:</td>
            <td class="i-val"><strong><?= e($ideb['nama_lengkap']) ?></strong></td>
        </tr>
        <tr>
            <td class="i-lbl">No.KTP/Identitas</td>
            <td class="i-sep">:</td>
            <td class="i-val"><?= e($ideb['no_ktp']) ?></td>
        </tr>
        <tr>
            <td class="i-lbl">NPWP</td>
            <td class="i-sep">:</td>
            <td class="i-val"><?= e($ideb['npwp']) ?></td>
        </tr>
        <tr>
            <td class="i-lbl">Tempat/tanggal/bulan/tahun lahir</td>
            <td class="i-sep">:</td>
            <td class="i-val"><?= e($ideb['ttl']) ?></td>
        </tr>
        <tr class="row-alamat">
            <td class="i-lbl">Alamat lengkap</td>
            <td class="i-sep">:</td>
            <td class="i-val"><?= e($ideb['alamat']) ?></td>
        </tr>
    </table>
            <?php if (!empty($r['catatan'])): ?>
                <p style="margin-top:14px;"><strong>Catatan Pemberkasan:</strong> <?= e($r['catatan']) ?></p>
            <?php endif; ?>

    <div class="ideb-sec-label">PASANGAN</div>
    <table class="ideb-table">
        <tr>
            <td class="i-lbl">Nama</td>
            <td class="i-sep">:</td>
            <td class="i-val"><strong><?= e($ideb['nama_pasangan']) ?></strong></td>
        </tr>
        <tr>
            <td class="i-lbl">No.KTP/Identitas</td>
            <td class="i-sep">:</td>
            <td class="i-val"><?= e($ideb['ktp_pasangan']) ?></td>
        </tr>
        <tr>
            <td class="i-lbl">NPWP</td>
            <td class="i-sep">:</td>
            <td class="i-val"><?= e($ideb['npwp_pasangan']) ?></td>
        </tr>
        <tr>
            <td class="i-lbl">Tempat/tanggal/bulan/tahun lahir</td>
            <td class="i-sep">:</td>
            <td class="i-val"><?= e($ideb['ttl_pasangan']) ?></td>
        </tr>
        <tr class="row-alamat">
            <td class="i-lbl">Alamat lengkap</td>
            <td class="i-sep">:</td>
            <td class="i-val"><?= e($ideb['alamat_pasangan']) ?></td>
        </tr>
    </table>
            <div class="sign-grid">
                <div>
                    <div>Yang Menyerahkan,</div>
                    <div class="sign-space"></div>
                    <div><strong>( <?= pVal($r['nama_lengkap'], '...........................') ?> )</strong></div>
                </div>
                <div>
                    <div><?= pVal($r['tgl_berkas'] ?: $r['tempat_tgl_surat'], $todayIndo) ?></div>
                    <div>Petugas Penerima Berkas,</div>
                    <div class="sign-space"></div>
                    <div><strong>( ...................................... )</strong></div>
                </div>
            </div>
        <?php else: ?>
            <div class="doc-header">
                <h1>DATA ADMINISTRASI PENGAJUAN KPR FLPP</h1>
                <p>No. Urut Database: #<?= e($r['no_urut'] ?: $r['id']) ?> • <?= pVal($r['pengembang'], 'Database Administrasi FLPP') ?></p>
            </div>

    <div class="ideb-closing">
        <p>Terlampir kami sampaikan photo copy KTP pemohon dan pasangan pemohon.</p>
        <p>Demikian disampaikan atas kerjasamanya diucapkan terima kasih</p>
    </div>
            <div class="section-sub">A. IDENTITAS PEMOHON</div>
            <table class="tbl-info">
                <tr><td class="lbl">Nama Lengkap</td><td class="sep">:</td><td><strong><?= pVal($r['nama_lengkap']) ?></strong></td></tr>
                <tr><td class="lbl">Nomor KTP (NIK)</td><td class="sep">:</td><td><?= pVal($r['no_ktp']) ?></td></tr>
                <tr><td class="lbl">Nomor NPWP</td><td class="sep">:</td><td><?= pVal($r['npwp'] ?: $r['no_ktp']) ?></td></tr>
                <tr><td class="lbl">Tempat, Tanggal Lahir</td><td class="sep">:</td><td><?= pVal($r['ttl']) ?></td></tr>
                <tr><td class="lbl">Pekerjaan & Penghasilan</td><td class="sep">:</td><td><?= pVal($r['pekerjaan'], '-') ?> (<?= pVal($r['penghasilan'] ?: $r['penghasilan_pemohon'], '-') ?>)</td></tr>
                <tr><td class="lbl">Nomor HP / WhatsApp</td><td class="sep">:</td><td><?= pVal($r['no_hp']) ?></td></tr>
                <tr><td class="lbl">Alamat Sesuai KTP</td><td class="sep">:</td><td><?= pVal($r['alamat']) ?></td></tr>
            </table>

    <div class="ideb-date-line">
        <span class="js-ideb-date"><?= e($ideb['tanggal_surat']) ?></span>
    </div>
            <div class="section-sub">B. IDENTITAS PASANGAN (SUAMI / ISTRI)</div>
            <table class="tbl-info">
                <tr><td class="lbl">Nama Pasangan</td><td class="sep">:</td><td><?= pVal($r['nama_pasangan'], '-') ?></td></tr>
                <tr><td class="lbl">NIK KTP / NPWP Pasangan</td><td class="sep">:</td><td><?= pVal($r['ktp_pasangan'], '-') ?> / <?= pVal($r['npwp_pasangan'] ?: $r['ktp_pasangan'], '-') ?></td></tr>
                <tr><td class="lbl">Tempat, Tanggal Lahir</td><td class="sep">:</td><td><?= pVal($r['ttl_pasangan'], '-') ?></td></tr>
                <tr><td class="lbl">Pekerjaan & No. HP</td><td class="sep">:</td><td><?= pVal($r['pekerjaan_pasangan'], '-') ?> / <?= pVal($r['hp_pasangan'], '-') ?></td></tr>
                <tr><td class="lbl">Alamat Pasangan</td><td class="sep">:</td><td><?= pVal($r['alamat_pasangan'], '-') ?></td></tr>
            </table>

    <table class="ideb-sign-table">
        <tr>
            <td style="width:32%;" class="js-ideb-fs-bracket"><?= e($ideb['fs_bracket']) ?></td>
            <td style="width:36%;">(                                       )</td>
            <td style="width:32%;">(                           )</td>
        </tr>
        <tr>
            <td>Financing Service</td>
            <td><strong><?= e($ideb['nama_lengkap']) ?></strong></td>
            <td><strong><?= e($ideb['nama_pasangan']) ?></strong></td>
        </tr>
    </table>
            <div class="section-sub">C. DATA UNIT RUMAH, PENGEMBANG & PEMBIAYAAN KPR</div>
            <table class="tbl-info">
                <tr><td class="lbl">Nama Perumahan / Komplek</td><td class="sep">:</td><td><strong><?= pVal($r['nama_perumahan'] ?: $r['nama_komplek']) ?></strong></td></tr>
                <tr><td class="lbl">Nomor Rumah / Blok</td><td class="sep">:</td><td><strong><?= pVal($r['no_rumah']) ?></strong></td></tr>
                <tr><td class="lbl">Luas Tanah / Tipe Rumah</td><td class="sep">:</td><td><?= pVal($r['luas_tanah'], '-') ?> / Tipe <?= pVal($r['luas_lantai'], '36') ?></td></tr>
                <tr><td class="lbl">Lokasi Perumahan</td><td class="sep">:</td><td><?= pVal($r['lokasi_perumahan']) ?></td></tr>
                <tr><td class="lbl">PT Pengembang & Direktur</td><td class="sep">:</td><td><?= pVal($r['pengembang']) ?> (Direktur: <?= pVal($r['nama_direktur'], '-') ?>)</td></tr>
                <tr><td class="lbl">No. Rekening PT</td><td class="sep">:</td><td><?= pVal($r['no_rek_pt'], '-') ?></td></tr>
                <tr><td class="lbl">Harga Jual Rumah</td><td class="sep">:</td><td><strong><?= pVal($r['harga_jual_rumah']) ?></strong></td></tr>
                <tr><td class="lbl">Total Uang Muka / SBUM</td><td class="sep">:</td><td><?= pVal($r['uang_muka']) ?> (SBUM: <?= pVal($r['sbum']) ?> • UM Nasabah: <?= pVal($r['uang_muka_nasabah']) ?>)</td></tr>
                <tr><td class="lbl">Plafon KPR FLPP</td><td class="sep">:</td><td><strong><?= pVal($r['kpr']) ?></strong></td></tr>
            </table>

    <div class="ideb-footnote">
        *DENGAN DI TANDA TANGANINYA PERMOHONAN INI OLEH PEMOHON DAN PASANGAN (CALON NASABAH BANK SYARIAH NASIONAL) MAKA NASABAH MENGIZINKAN UNTUK DI CEK RIWAYAT DAN DATA PINJAMANNYA OLEH BANK SYARIAH NASIONAL*
            <div class="section-sub">D. PENYURATAN & LEGALITAS</div>
            <table class="tbl-info">
                <tr><td class="lbl">Nomor BAST</td><td class="sep">:</td><td><?= pVal($r['no_bast']) ?></td></tr>
                <tr><td class="lbl">Tanggal Serah Terima</td><td class="sep">:</td><td><?= pVal($r['tanggal_serah_terima']) ?></td></tr>
                <tr><td class="lbl">Nomor & Tanggal PPJB</td><td class="sep">:</td><td><?= pVal($r['ppjb']) ?> / <?= pVal($r['tanggal_ppjb'], '-') ?></td></tr>
                <tr><td class="lbl">Nomor SHGB / IMB</td><td class="sep">:</td><td>SHGB: <?= pVal($r['no_shgb'], '-') ?> • IMB: <?= pVal($r['no_imb'], '-') ?></td></tr>
            </table>
        <?php endif; ?>
    </div>

<?php elseif ($tpl === 'bast'): ?>
    <!-- TEMPLATE 3: DRAFT BERITA ACARA SERAH TERIMA (BAST) -->
    <div class="doc-header">
        <h1><?= pVal($r['pengembang'], 'PT. PENGEMBANG PERUMAHAN') ?></h1>
        <p><?= pVal($r['tempat_akad'] ?: $r['lokasi_perumahan'], 'Alamat Kantor Pengembang') ?></p>
    </div>

    <div class="doc-title">
        <h2>BERITA ACARA SERAH TERIMA RUMAH (BAST)</h2>
        <div>Nomor: <?= pVal($r['no_bast'], '......... / BAST / ' . bulanRomawi() . '-' . ($r['tahun'] ?: date('Y'))) ?></div>
    </div>

    <p style="text-align:justify;">
        Pada hari ini, tanggal <?= pVal($r['tanggal_serah_terima'] ?: $r['tempat_tgl_surat'], '............................') ?>, kami yang bertanda tangan di bawah ini:
    </p>

    <table class="tbl-info">
        <tr><td class="lbl">1. Nama</td><td class="sep">:</td><td><strong><?= pVal($r['nama_direktur']) ?></strong></td></tr>
        <tr><td class="lbl">&nbsp;&nbsp;&nbsp;&nbsp;NIK</td><td class="sep">:</td><td><?= pVal($r['nik_direktur']) ?></td></tr>
        <tr><td class="lbl">&nbsp;&nbsp;&nbsp;&nbsp;Jabatan</td><td class="sep">:</td><td><?= pVal($r['jabatan_direktur'] ?: 'Direktur') ?> <?= pVal($r['nama_pengembang'] ?: $r['pengembang'], '') ?></td></tr>
        <tr><td class="lbl">&nbsp;&nbsp;&nbsp;&nbsp;Alamat</td><td class="sep">:</td><td><?= pVal($r['alamat_direktur']) ?></td></tr>
    </table>
    <p style="margin:4px 0 12px;">Dalam hal ini bertindak untuk dan atas nama <strong><?= pVal($r['nama_pengembang'] ?: $r['pengembang']) ?></strong>, selanjutnya disebut sebagai <strong>PIHAK PERTAMA (PENGEMBANG)</strong>.</p>

    <table class="tbl-info">
        <tr><td class="lbl">2. Nama Lengkap</td><td class="sep">:</td><td><strong><?= pVal($r['nama_lengkap']) ?></strong></td></tr>
        <tr><td class="lbl">&nbsp;&nbsp;&nbsp;&nbsp;NIK KTP / NPWP</td><td class="sep">:</td><td><?= pVal($r['no_ktp']) ?> / <?= pVal($r['npwp'] ?: $r['no_ktp']) ?></td></tr>
        <tr><td class="lbl">&nbsp;&nbsp;&nbsp;&nbsp;Pekerjaan</td><td class="sep">:</td><td><?= pVal($r['pekerjaan']) ?></td></tr>
        <tr><td class="lbl">&nbsp;&nbsp;&nbsp;&nbsp;Alamat</td><td class="sep">:</td><td><?= pVal($r['alamat']) ?></td></tr>
    </table>
    <p style="margin:4px 0 12px;">Selanjutnya disebut sebagai <strong>PIHAK KEDUA (PEMBELI / DEBITUR)</strong>.</p>

    <p style="text-align:justify;">
        PIHAK PERTAMA dengan ini menyerahkan kepada PIHAK KEDUA, dan PIHAK KEDUA menerima penyerahan 1 (satu) unit bangunan rumah tapak beserta fasilitasnya dalam keadaan baik dan layak huni dengan rincian sebagai berikut:
    </p>

    <table class="tbl-info">
        <tr><td class="lbl">Nama Perumahan / Komplek</td><td class="sep">:</td><td><strong><?= pVal($r['nama_perumahan'] ?: $r['nama_komplek']) ?></strong></td></tr>
        <tr><td class="lbl">Nomor Rumah / Blok</td><td class="sep">:</td><td><strong><?= pVal($r['no_rumah']) ?></strong></td></tr>
        <tr><td class="lbl">Lokasi Perumahan</td><td class="sep">:</td><td><?= pVal($r['lokasi_perumahan']) ?></td></tr>
        <tr><td class="lbl">Tipe Bangunan / Luas Tanah</td><td class="sep">:</td><td>Tipe <?= pVal($r['luas_lantai'], '36') ?> / LT: <?= pVal($r['luas_tanah']) ?></td></tr>
        <tr><td class="lbl">Nomor Sertifikat (SHGB)</td><td class="sep">:</td><td><?= pVal($r['no_shgb']) ?></td></tr>
        <tr><td class="lbl">Nomor IMB / PBG</td><td class="sep">:</td><td><?= pVal($r['no_imb']) ?></td></tr>
    </table>

    <p style="text-align:justify;">
        Demikian Berita Acara Serah Terima ini dibuat dengan sebenarnya dalam rangkap secukupnya untuk dapat dipergunakan sebagaimana mestinya.
    </p>

    <div style="text-align:right;margin-top:18px;">
        <?= pVal($r['tempat_tgl_surat'], $todayIndo) ?>
    </div>

    <div class="sign-grid">
        <div>
            <div><strong>PIHAK KEDUA</strong></div>
            <div>Pembeli / Penerima,</div>
            <div class="sign-space"></div>
            <div><strong>( <?= pVal($r['nama_lengkap'], '...........................') ?> )</strong></div>
    <?php endforeach; ?>
<?php else: ?>
    <?php
    $totalSheetsShown = count($rows) * count($activeDocKeys);
    foreach ($rows as $r):
        $sheets = BsnSuratService::renderSlicesToHtml($r, $activeDocKeys, $pdo, $overrides);
        foreach ($sheets as $sheet):
            $paperCls = match ($sheet['sect_type']) {
                'tight' => 'paper paper-tight',
                'ideb'  => 'paper paper-ideb',
                default => 'paper',
            };
    ?>
        <?php if ($totalSheetsShown > 1): ?>
            <div class="paper-badge">
                <span>SURAT #<?= e($sheet['code']) ?> — <?= e($sheet['title']) ?></span>
                <span>Nasabah: <?= e($r['nama_lengkap']) ?></span>
            </div>
        <?php endif; ?>
        <div class="<?= $paperCls ?>" contenteditable="true" spellcheck="false">
            <?= $sheet['html'] ?>
        </div>
        <div>
            <div><strong>PIHAK PERTAMA</strong></div>
            <div><?= pVal($r['nama_pengembang'] ?: $r['pengembang'], 'Pengembang') ?>,</div>
            <div class="sign-space"></div>
            <div><strong>( <?= pVal($r['nama_direktur'], '...........................') ?> )</strong></div>
            <div style="font-size:10.5pt;"><?= pVal($r['jabatan_direktur'] ?: 'Direktur', '') ?></div>
        </div>
    </div>

<?php elseif ($tpl === 'tandaterima'): ?>
    <!-- TEMPLATE 4: TANDA TERIMA & CHECKLIST BERKAS -->
    <div class="doc-header">
        <h1>LEMBAR KENDALI & TANDA TERIMA BERKAS KPR FLPP</h1>
        <p>No. Urut Database: #<?= e($r['no_urut'] ?: $r['id']) ?> • Status: <?= e($r['status_proses']) ?></p>
    </div>

    <table class="tbl-info">
        <tr><td class="lbl">Nama Pemohon</td><td class="sep">:</td><td><strong><?= pVal($r['nama_lengkap']) ?></strong></td></tr>
        <tr><td class="lbl">NIK KTP / No. HP</td><td class="sep">:</td><td><?= pVal($r['no_ktp']) ?> / <?= pVal($r['no_hp']) ?></td></tr>
        <tr><td class="lbl">Nama Pasangan</td><td class="sep">:</td><td><?= pVal($r['nama_pasangan'], '-') ?></td></tr>
        <tr><td class="lbl">Perumahan & Blok</td><td class="sep">:</td><td><?= pVal($r['nama_perumahan']) ?> — Blok/No: <?= pVal($r['no_rumah']) ?></td></tr>
        <tr><td class="lbl">Pengembang (PT)</td><td class="sep">:</td><td><?= pVal($r['pengembang']) ?></td></tr>
    </table>

    <table class="tbl-bordered">
        <thead>
            <tr>
                <th style="width:40px;">No</th>
                <th>Nama Dokumen / Kelengkapan Berkas</th>
                <th style="width:120px;">Status</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <tr><td>1</td><td>Fotokopi / Scan KTP Pemohon (& Pasangan)</td><td><?= (!empty($r['doc_ktp_check']) || !empty($r['file_ktp'])) ? '✓ Ada' : '☐ Belum' ?></td><td>NIK: <?= e($r['no_ktp'] ?: '-') ?></td></tr>
            <tr><td>2</td><td>NPWP Pemohon</td><td><?= (!empty($r['npwp']) || !empty($r['no_ktp'])) ? '✓ Ada' : '☐ Belum' ?></td><td><?= e($r['npwp'] ?: $r['no_ktp'] ?: '-') ?></td></tr>
            <tr><td>3</td><td>Pas Foto Berwarna</td><td><?= (!empty($r['doc_pas_foto_check']) || !empty($r['pas_foto'])) ? '✓ Ada' : '☐ Belum' ?></td><td></td></tr>
            <tr><td>4</td><td>Foto Tempat Kerja / Usaha</td><td><?= (!empty($r['doc_foto_kerja_check']) || !empty($r['foto_tempat_kerja'])) ? '✓ Ada' : '☐ Belum' ?></td><td></td></tr>
            <tr><td>5</td><td>Share Lokasi Tempat Kerja</td><td><?= (!empty($r['doc_lokasi_kerja_check']) || !empty($r['share_lokasi_kerja'])) ? '✓ Ada' : '☐ Belum' ?></td><td></td></tr>
            <tr><td>6</td><td>Nomor Sertifikat SHGB & IMB/PBG</td><td><?= (!empty($r['no_shgb']) || !empty($r['no_imb'])) ? '✓ Ada' : '☐ Menyusul' ?></td><td>SHGB: <?= e($r['no_shgb'] ?: '-') ?></td></tr>
            <tr><td>7</td><td>Dokumen PPJB</td><td><?= !empty($r['ppjb']) ? '✓ Ada' : '☐ Menyusul' ?></td><td><?= e($r['ppjb'] ?: '-') ?></td></tr>
            <tr><td>8</td><td>Berita Acara Serah Terima (BAST)</td><td><?= getBastState($r['no_bast']) === 'lengkap' ? '✓ Lengkap' : '☐ Menyusul' ?></td><td><?= e($r['no_bast'] ?: '-') ?></td></tr>
        </tbody>
    </table>

    <?php if (!empty($r['catatan'])): ?>
        <p style="margin-top:14px;"><strong>Catatan Pemberkasan:</strong> <?= e($r['catatan']) ?></p>
    <?php endif; ?>

    <div class="sign-grid">
        <div>
            <div>Yang Menyerahkan,</div>
            <div class="sign-space"></div>
            <div><strong>( <?= pVal($r['nama_lengkap'], '...........................') ?> )</strong></div>
        </div>
        <div>
            <div><?= pVal($r['tgl_berkas'] ?: $r['tempat_tgl_surat'], $todayIndo) ?></div>
            <div>Petugas Penerima Berkas,</div>
            <div class="sign-space"></div>
            <div><strong>( ...................................... )</strong></div>
        </div>
    </div>

<?php else: ?>
    <!-- TEMPLATE 2: BIODATA LENGKAP PENGAJUAN FLPP -->
    <div class="doc-header">
        <h1>DATA ADMINISTRASI PENGAJUAN KPR FLPP</h1>
        <p>No. Urut Database: #<?= e($r['no_urut'] ?: $r['id']) ?> • <?= pVal($r['pengembang'], 'Database Administrasi FLPP') ?></p>
    </div>

    <div class="section-sub">A. IDENTITAS PEMOHON</div>
    <table class="tbl-info">
        <tr><td class="lbl">Nama Lengkap</td><td class="sep">:</td><td><strong><?= pVal($r['nama_lengkap']) ?></strong></td></tr>
        <tr><td class="lbl">Nomor KTP (NIK)</td><td class="sep">:</td><td><?= pVal($r['no_ktp']) ?></td></tr>
        <tr><td class="lbl">Nomor NPWP</td><td class="sep">:</td><td><?= pVal($r['npwp'] ?: $r['no_ktp']) ?></td></tr>
        <tr><td class="lbl">Tempat, Tanggal Lahir</td><td class="sep">:</td><td><?= pVal($r['ttl']) ?></td></tr>
        <tr><td class="lbl">Pekerjaan & Penghasilan</td><td class="sep">:</td><td><?= pVal($r['pekerjaan'], '-') ?> (<?= pVal($r['penghasilan'] ?: $r['penghasilan_pemohon'], '-') ?>)</td></tr>
        <tr><td class="lbl">Nomor HP / WhatsApp</td><td class="sep">:</td><td><?= pVal($r['no_hp']) ?></td></tr>
        <tr><td class="lbl">Alamat Sesuai KTP</td><td class="sep">:</td><td><?= pVal($r['alamat']) ?></td></tr>
    </table>

    <div class="section-sub">B. IDENTITAS PASANGAN (SUAMI / ISTRI)</div>
    <table class="tbl-info">
        <tr><td class="lbl">Nama Pasangan</td><td class="sep">:</td><td><?= pVal($r['nama_pasangan'], '-') ?></td></tr>
        <tr><td class="lbl">NIK KTP / NPWP Pasangan</td><td class="sep">:</td><td><?= pVal($r['ktp_pasangan'], '-') ?> / <?= pVal($r['npwp_pasangan'] ?: $r['ktp_pasangan'], '-') ?></td></tr>
        <tr><td class="lbl">Tempat, Tanggal Lahir</td><td class="sep">:</td><td><?= pVal($r['ttl_pasangan'], '-') ?></td></tr>
        <tr><td class="lbl">Pekerjaan & No. HP</td><td class="sep">:</td><td><?= pVal($r['pekerjaan_pasangan'], '-') ?> / <?= pVal($r['hp_pasangan'], '-') ?></td></tr>
        <tr><td class="lbl">Alamat Pasangan</td><td class="sep">:</td><td><?= pVal($r['alamat_pasangan'], '-') ?></td></tr>
    </table>

    <div class="section-sub">C. DATA UNIT RUMAH, PENGEMBANG & PEMBIAYAAN KPR</div>
    <table class="tbl-info">
        <tr><td class="lbl">Nama Perumahan / Komplek</td><td class="sep">:</td><td><strong><?= pVal($r['nama_perumahan'] ?: $r['nama_komplek']) ?></strong></td></tr>
        <tr><td class="lbl">Nomor Rumah / Blok</td><td class="sep">:</td><td><strong><?= pVal($r['no_rumah']) ?></strong></td></tr>
        <tr><td class="lbl">Luas Tanah / Tipe Rumah</td><td class="sep">:</td><td><?= pVal($r['luas_tanah'], '-') ?> / Tipe <?= pVal($r['luas_lantai'], '36') ?></td></tr>
        <tr><td class="lbl">Lokasi Perumahan</td><td class="sep">:</td><td><?= pVal($r['lokasi_perumahan']) ?></td></tr>
        <tr><td class="lbl">PT Pengembang & Direktur</td><td class="sep">:</td><td><?= pVal($r['pengembang']) ?> (Direktur: <?= pVal($r['nama_direktur'], '-') ?>)</td></tr>
        <tr><td class="lbl">No. Rekening PT</td><td class="sep">:</td><td><?= pVal($r['no_rek_pt'], '-') ?></td></tr>
        <tr><td class="lbl">Harga Jual Rumah</td><td class="sep">:</td><td><strong><?= pVal($r['harga_jual_rumah']) ?></strong></td></tr>
        <tr><td class="lbl">Total Uang Muka / SBUM</td><td class="sep">:</td><td><?= pVal($r['uang_muka']) ?> (SBUM: <?= pVal($r['sbum']) ?> • UM Nasabah: <?= pVal($r['uang_muka_nasabah']) ?>)</td></tr>
        <tr><td class="lbl">Plafon KPR FLPP</td><td class="sep">:</td><td><strong><?= pVal($r['kpr']) ?></strong></td></tr>
    </table>

    <div class="section-sub">D. PENYURATAN & LEGALITAS</div>
    <table class="tbl-info">
        <tr><td class="lbl">Nomor BAST</td><td class="sep">:</td><td><?= pVal($r['no_bast']) ?></td></tr>
        <tr><td class="lbl">Tanggal Serah Terima</td><td class="sep">:</td><td><?= pVal($r['tanggal_serah_terima']) ?></td></tr>
        <tr><td class="lbl">Nomor & Tanggal PPJB</td><td class="sep">:</td><td><?= pVal($r['ppjb']) ?> / <?= pVal($r['tanggal_ppjb'], '-') ?></td></tr>
        <tr><td class="lbl">Nomor SHGB / IMB</td><td class="sep">:</td><td>SHGB: <?= pVal($r['no_shgb'], '-') ?> • IMB: <?= pVal($r['no_imb'], '-') ?></td></tr>
    </table>
    <?php
        endforeach;
    endforeach;
    ?>
<?php endif; ?>
</div>
<?php endforeach; ?>

<?php if ($tpl === 'ideb'): ?>
<script>
(function () {
    const baseQuery = <?= json_encode($idQueryParam) ?>;
    const inputTgl = document.getElementById('ctrlTanggalIdeb');
    const inputFs  = document.getElementById('ctrlNamaFs');
    const btnDocx  = document.getElementById('btnDownloadDocx');
    const btnZip   = document.getElementById('btnDownloadZip');
function switchPrintTemplate(val) {
    if (!val || val === 'docs:custom') return;
    const baseIdQs = <?= json_encode($idQueryParam) ?>;
    const extraQs  = <?= json_encode($extraQs) ?>;

    function syncIdebControls() {
        const tglVal = inputTgl ? inputTgl.value.trim() : '';
        const fsVal  = inputFs ? inputFs.value.trim() : '';

        if (tglVal !== '') {
            document.querySelectorAll('.js-ideb-date').forEach(el => {
                el.textContent = tglVal;
            });
        }
        document.querySelectorAll('.js-ideb-fs-bracket').forEach(el => {
            el.textContent = fsVal !== '' ? '(  ' + fsVal + '  )' : '(                           )';
        });

        let qs = baseQuery;
        if (tglVal !== '') qs += '&tgl=' + encodeURIComponent(tglVal);
        if (fsVal !== '')  qs += '&fs=' + encodeURIComponent(fsVal);

        if (btnDocx) btnDocx.href = 'ideb_docx.php?' + qs;
        if (btnZip)  btnZip.href  = 'ideb_docx.php?' + qs + '&mode=zip';
    if (val.startsWith('docs:')) {
        const d = val.substring(5);
        window.location.href = 'print.php?' + baseIdQs + '&docs=' + encodeURIComponent(d) + extraQs;
    } else if (val.startsWith('tpl:')) {
        const t = val.substring(4);
        window.location.href = 'print.php?' + baseIdQs + '&tpl=' + encodeURIComponent(t) + extraQs;
    } else if (val.startsWith('internal:')) {
        const it = val.substring(9);
        window.location.href = 'print.php?' + baseIdQs + '&tpl=' + encodeURIComponent(it);
    }

    window.setTodayIdebDate = function (todayStr) {
        if (inputTgl) {
            inputTgl.value = todayStr;
            syncIdebControls();
        }
    };

    if (inputTgl) inputTgl.addEventListener('input', syncIdebControls);
    if (inputFs)  inputFs.addEventListener('input', syncIdebControls);
    syncIdebControls();
})();
}
</script>
<?php endif; ?>

</body>
</html>
