<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';
requireLogin();

$idsParam   = trim((string)($_GET['ids'] ?? ''));
$fillDots   = !empty($_GET['dots']);
$q          = trim((string)($_GET['q'] ?? ''));
$ptFilter   = trim((string)($_GET['pt'] ?? ''));
$perumFilter= trim((string)($_GET['perum'] ?? ''));
$bastFilter = trim((string)($_GET['bast'] ?? ''));
$statFilter = trim((string)($_GET['status'] ?? ''));
$unitFilter = trim((string)($_GET['filter_unit'] ?? ''));

$where = [];
$params = [];

if ($idsParam !== '') {
    $idList = array_values(array_filter(array_map('intval', explode(',', $idsParam)), fn($v) => $v > 0));
    if (!empty($idList)) {
        $inPlaceholders = implode(',', array_fill(0, count($idList), '?'));
        $where[] = "id IN ($inPlaceholders)";
        $params = array_merge($params, $idList);
    }
} else {
    if ($q !== '') {
        $where[] = "(nama_lengkap LIKE ? OR no_ktp LIKE ? OR npwp LIKE ? OR no_hp LIKE ? OR nama_pasangan LIKE ? OR nama_perumahan LIKE ? OR nama_komplek LIKE ? OR no_rumah LIKE ? OR no_bast LIKE ? OR no_shgb LIKE ? OR alamat LIKE ?)";
        $like = '%' . $q . '%';
        for ($i = 0; $i < 11; $i++) $params[] = $like;
    }
    if ($ptFilter === '__KOSONG__') {
        $where[] = "TRIM(pengembang) = ''";
    } elseif ($ptFilter !== '') {
        $where[] = "UPPER(TRIM(pengembang)) LIKE ?";
        $params[] = '%' . strtoupper($ptFilter) . '%';
    }
    if ($perumFilter !== '') {
        $where[] = "(UPPER(TRIM(nama_perumahan)) LIKE ? OR UPPER(TRIM(nama_komplek)) LIKE ?)";
        $params[] = '%' . strtoupper($perumFilter) . '%';
        $params[] = '%' . strtoupper($perumFilter) . '%';
    }
    if ($statFilter !== '') {
        $where[] = "status_proses = ?";
        $params[] = $statFilter;
    }
    if ($unitFilter === 'ada') {
        $where[] = "(TRIM(pengembang) != '' OR TRIM(nama_perumahan) != '')";
    } elseif ($unitFilter === 'kosong') {
        $where[] = "(TRIM(pengembang) = '' AND TRIM(nama_perumahan) = '')";
    }
    if ($bastFilter === 'ada') {
        $where[] = "(TRIM(no_bast) != '' OR TRIM(tanggal_serah_terima) != '')";
    } elseif ($bastFilter === 'kosong') {
        $where[] = "(TRIM(no_bast) = '' AND TRIM(tanggal_serah_terima) = '')";
    }
}

$whereSql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';
$stmt = $pdo->prepare("SELECT * FROM nasabah $whereSql ORDER BY CAST(no_urut AS INTEGER) ASC, id ASC");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$tmpFile = __DIR__ . '/storage/export_' . uniqid('', true) . '.xlsx';
ExcelService::exportWorkbook($rows, $tmpFile, $fillDots);

$filename = 'DATABASE_FLPP_' . date('Y-m-d_His') . ($idsParam !== '' ? '_TERPILIH' : '') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($tmpFile));
header('Cache-Control: max-age=0');

readfile($tmpFile);
@unlink($tmpFile);
exit;

