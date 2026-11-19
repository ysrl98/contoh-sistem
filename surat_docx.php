<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';
requireLogin();

// Ambil daftar ID nasabah dari GET atau POST
$rawIds = $_REQUEST['ids'] ?? '';
$singleId = (int)($_REQUEST['id'] ?? 0);

$ids = [];
if (is_array($rawIds)) {
    foreach ($rawIds as $part) {
        $v = (int)$part;
        if ($v > 0) $ids[] = $v;
    }
} elseif (is_string($rawIds) && trim($rawIds) !== '') {
    foreach (explode(',', $rawIds) as $part) {
        $v = (int)trim($part);
        if ($v > 0) $ids[] = $v;
    }
} elseif ($singleId > 0) {
    $ids[] = $singleId;
}

$ids = array_values(array_unique($ids));
if (empty($ids)) {
    http_response_code(400);
    die('Pilih minimal 1 nasabah terlebih dahulu.');
}

// Ambil daftar key surat yang diminta (doc=... atau docs=...)
$rawDocs = $_REQUEST['docs'] ?? ($_REQUEST['doc'] ?? 'all');
$docKeys = BsnSuratService::normalizeDocKeys($rawDocs);
if (empty($docKeys)) {
    http_response_code(400);
    die('Pilih minimal 1 jenis surat BSN yang ingin dibuat.');
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
    http_response_code(404);
    die('Data nasabah tidak ditemukan di database.');
}

$overrides = [
    'tgl'             => trim((string)($_REQUEST['tgl'] ?? '')),
    'fs'              => trim((string)($_REQUEST['fs'] ?? '')),
    'sp3_no'          => trim((string)($_REQUEST['sp3_no'] ?? '')),
    'sp3_tgl'         => trim((string)($_REQUEST['sp3_tgl'] ?? '')),
    'tanggal_akad'    => trim((string)($_REQUEST['tanggal_akad'] ?? '')),
    'no_rek_tabungan' => trim((string)($_REQUEST['no_rek_tabungan'] ?? '')),
    'no_rek_kpr'      => trim((string)($_REQUEST['no_rek_kpr'] ?? '')),
];

$mode = strtolower(trim((string)($_REQUEST['mode'] ?? 'combined')));
$catalog = BsnSuratService::getCatalog();

// Mode 1: 1 Nasabah, banyak surat -> pecah jadi masing-masing file .docx di dalam 1 .zip
if (($mode === 'zip_surat' || ($mode === 'zip' && count($rows) === 1 && count($docKeys) > 1)) && count($rows) === 1) {
    $tmpZip = tempnam(sys_get_temp_dir(), 'bsn_zipsrt_') . '.zip';
    try {
        BsnSuratService::generateZipPerSurat($rows[0], $docKeys, $tmpZip, $pdo, $overrides);
        $safeNama = IdebDocxService::sanitizeFilename($rows[0]['nama_lengkap'] ?? 'Nasabah');
        $downloadName = 'Paket_' . count($docKeys) . '_Surat_BSN_' . $safeNama . '.zip';

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        header('Content-Length: ' . filesize($tmpZip));
        header('Cache-Control: no-store, no-cache, must-revalidate');
        readfile($tmpZip);
    } finally {
        @unlink($tmpZip);
    }
    exit;
}

// Mode 2: Banyak Nasabah -> pecah jadi 1 file .docx per Nasabah di dalam 1 .zip
if (($mode === 'zip' || $mode === 'zip_nasabah') && count($rows) > 1) {
    $tmpZip = tempnam(sys_get_temp_dir(), 'bsn_zipnas_') . '.zip';
    try {
        BsnSuratService::generateZipPerNasabah($rows, $docKeys, $tmpZip, $pdo, $overrides);
        $docSlug = count($docKeys) === 1
            ? IdebDocxService::sanitizeFilename($catalog[$docKeys[0]]['short_title'])
            : 'Bundel_' . count($docKeys) . '_Surat_BSN';
        $downloadName = $docSlug . '_' . count($rows) . '_Nasabah_' . date('Ymd_His') . '.zip';

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        header('Content-Length: ' . filesize($tmpZip));
        header('Cache-Control: no-store, no-cache, must-revalidate');
        readfile($tmpZip);
    } finally {
        @unlink($tmpZip);
    }
    exit;
}

// Mode 3 (Default): File Microsoft Word (.docx) tunggal atau gabungan siap cetak sekali jalan
$tmpDocx = tempnam(sys_get_temp_dir(), 'bsn_docx_') . '.docx';
try {
    BsnSuratService::generateDocx($rows, $docKeys, $tmpDocx, $pdo, $overrides);

    if (count($rows) === 1) {
        $safeNama = IdebDocxService::sanitizeFilename($rows[0]['nama_lengkap'] ?? 'Nasabah');
        if (count($docKeys) === 1) {
            $meta = $catalog[$docKeys[0]];
            $safeTitle = IdebDocxService::sanitizeFilename($meta['short_title']);
            $downloadName = $meta['code'] . '_' . $safeTitle . '_' . $safeNama . '.docx';
        } elseif (count($docKeys) === count($catalog)) {
            $downloadName = 'BUNDEL_LENGKAP_FLPP_BSN_' . $safeNama . '.docx';
        } else {
            $downloadName = 'PAKET_' . count($docKeys) . '_SURAT_BSN_' . $safeNama . '.docx';
        }
    } else {
        $docSlug = count($docKeys) === 1
            ? IdebDocxService::sanitizeFilename($catalog[$docKeys[0]]['short_title'])
            : 'Bundel_' . count($docKeys) . '_Surat_BSN';
        $downloadName = $docSlug . '_Gabungan_' . count($rows) . '_Nasabah_' . date('Ymd') . '.docx';
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Content-Length: ' . filesize($tmpDocx));
    header('Cache-Control: no-store, no-cache, must-revalidate');
    readfile($tmpDocx);
} finally {
    @unlink($tmpDocx);
}
exit;

