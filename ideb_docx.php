<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';
requireLogin();

$ids = [];
if (!empty($_GET['ids'])) {
    foreach (explode(',', (string)$_GET['ids']) as $part) {
        $val = (int)trim($part);
        if ($val > 0) {
            $ids[] = $val;
        }
    }
} elseif (!empty($_GET['id'])) {
    $val = (int)$_GET['id'];
    if ($val > 0) {
        $ids[] = $val;
    }
}

$ids = array_values(array_unique($ids));
if (empty($ids)) {
    http_response_code(400);
    die('Parameter ID nasabah tidak valid.');
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
    die('Data nasabah tidak ditemukan.');
}

$settings      = Database::getSettings($pdo);
$defaultKota   = trim((string)($settings['default_kota_surat'] ?? 'Banjar'));
$customTanggal = trim((string)($_GET['tgl'] ?? ''));
$namaFs        = trim((string)($_GET['fs'] ?? ''));
$mode          = strtolower(trim((string)($_GET['mode'] ?? 'combined')));

if ($mode === 'zip' && count($rows) > 1) {
    $tmpZip = tempnam(sys_get_temp_dir(), 'ideb_zip_') . '.zip';
    try {
        IdebDocxService::generateBulkZip($rows, $tmpZip, $defaultKota, $customTanggal, $namaFs);
        $downloadName = 'PERMOHONAN_SLIK_OJK_' . count($rows) . '_Nasabah_' . date('Ymd_His') . '.zip';

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

$tmpDocx = tempnam(sys_get_temp_dir(), 'ideb_docx_') . '.docx';
try {
    IdebDocxService::generateDocx($rows, $tmpDocx, $defaultKota, $customTanggal, $namaFs);

    if (count($rows) === 1) {
        $safeNama = IdebDocxService::sanitizeFilename($rows[0]['nama_lengkap'] ?? 'Nasabah');
        $downloadName = 'PERMOHONAN_SLIK_OJK_' . $safeNama . '.docx';
    } else {
        $downloadName = 'PERMOHONAN_SLIK_OJK_Gabungan_' . count($rows) . '_Nasabah_' . date('Ymd') . '.docx';
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

