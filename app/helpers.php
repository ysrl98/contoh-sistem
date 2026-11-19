<?php
declare(strict_types=1);

/**
 * HTML-escape safely even for null or int values (PHP 8.3 safe)
 */
function e(mixed $val): string {
    if ($val === null) return '';
    return htmlspecialchars((string)$val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Clean manual Excel placeholders like "..........", "….......", "-", "#NAME?"
 * while preserving meaningful partial templates like "........./SBN/BAST/VIII-2025".
 */
function cleanPlaceholder(?string $val, bool $stripLeadingDotOnNumbers = false): string {
    if ($val === null) return '';
    $v = trim($val);
    if ($v === '' || strtoupper($v) === '#NAME?' || strtoupper($v) === '#REF!' || strtoupper($v) === '#VALUE!') {
        return '';
    }
    // Pure dots, ellipses, underscores, spaces, or single/multiple dashes with no letters/digits
    if (preg_match('/^[.\-…_\s]+$/u', $v)) {
        return '';
    }
    // E.g. "NO. ......." or "...E" -> keep "...E" since it has block E, clean "NO. ......."
    if (preg_match('/^no\.?\s*[.…_\s]+$/iu', $v)) {
        return '';
    }
    if ($stripLeadingDotOnNumbers) {
        // Strip leading dot used in Excel to prevent scientific notation: ".6303031204730003" or ".0896 5377"
        $v = preg_replace('/^[.\']+([0-9])/', '$1', $v) ?? $v;
        if ($v === '0') return '';
    }
    return trim($v);
}

/**
 * Parse Rupiah string ("Rp. 182.000.000" or "6000000") into integer
 */
function parseRupiahNumber(?string $val): int {
    if ($val === null || trim($val) === '') return 0;
    $digits = preg_replace('/[^0-9]/', '', $val);
    return $digits !== '' ? (int)$digits : 0;
}

/**
 * Format integer or string into "Rp. 182.000.000" matching original Excel style
 */
function formatRupiahExcel(int|string|null $val): string {
    if ($val === null || trim((string)$val) === '') return '';
    $num = is_int($val) ? $val : parseRupiahNumber((string)$val);
    if ($num <= 0) return '';
    return 'Rp. ' . number_format($num, 0, ',', '.');
}

/**
 * Format Indonesian date string: "Banjar, 29 Oktober 2026"
 */
function tanggalIndoSekarang(string $kota = 'Banjar'): string {
    $bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    $d = (int)date('j');
    $m = $bulan[(int)date('n')];
    $y = date('Y');
    return $kota !== '' ? "$kota, $d $m $y" : "$d $m $y";
}

/**
 * Current Roman month for BAST template
 */
function bulanRomawi(?int $month = null): string {
    $m = $month ?? (int)date('n');
    $map = [1=>'I',2=>'II',3=>'III',4=>'IV',5=>'V',6=>'VI',7=>'VII',8=>'VIII',9=>'IX',10=>'X',11=>'XI',12=>'XII'];
    return $map[$m] ?? 'I';
}

/**
 * Determine whether a BAST string is truly filled (not empty and not just dots before slash)
 */
function getBastState(?string $noBast): string {
    $b = trim((string)$noBast);
    if ($b === '' || preg_match('/^[.\-…_\s]+$/u', $b)) {
        return 'kosong';
    }
    // Check if starts with dots like "........./SBN/BAST/VIII-2025"
    if (preg_match('/^[.…_\s]{2,}\//u', $b)) {
        return 'draft';
    }
    return 'lengkap';
}

/**
 * Normalize WhatsApp link from Indonesian phone number
 */
function formatWaLink(?string $hp): string {
    $digits = preg_replace('/[^0-9]/', '', (string)$hp);
    if ($digits === '' || strlen($digits) < 8) return '';
    if (str_starts_with($digits, '0')) {
        $digits = '62' . substr($digits, 1);
    }
    return 'https://wa.me/' . $digits;
}

/**
 * Badge HTML for Status Proses
 */
function renderStatusBadge(?string $status): string {
    $s = trim((string)$status);
    return match ($s) {
        'Sudah Akad / BAST' => '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Sudah Akad / BAST</span>',
        'Siap Akad'         => '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200"><span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>Siap Akad</span>',
        'Proses Bank'       => '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Proses Bank</span>',
        default             => '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200"><span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>Pemberkasan</span>',
    };
}

/**
 * Badge HTML for BAST status
 */
function renderBastBadge(?string $noBast): string {
    $state = getBastState($noBast);
    if ($state === 'lengkap') {
        return '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200" title="' . e($noBast) . '">✓ ' . e($noBast) . '</span>';
    }
    if ($state === 'draft') {
        return '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200" title="Nomor urut BAST belum diisi: ' . e($noBast) . '">⏳ Draft (' . e($noBast) . ')</span>';
    }
    return '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-slate-50 text-slate-400 border border-slate-200">Belum ada BAST</span>';
}

/**
 * Flash message helper
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (!isset($_SESSION['flash'])) return null;
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
}

