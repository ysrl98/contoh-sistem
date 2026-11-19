<?php
$user = currentUser();
$currPage = $_GET['page'] ?? 'dashboard';
$flash = getFlash();
$totalNasabahNav = (int)$pdo->query("SELECT COUNT(*) FROM nasabah")->fetchColumn();
$pendingPengajuanNav = (int)$pdo->query("SELECT COUNT(*) FROM pengajuan_nasabah WHERE status_pengajuan = 'pending'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'SIM-FLPP') ?> — Sistem Database & Pemberkasan KPR FLPP</title>
    <link rel="stylesheet" href="assets/css/style.css?v=1.0">
</head>
<body>
<div class="app-shell">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="appSidebar">
        <div class="sidebar-brand">
            <div class="brand-logo">FL</div>
            <div>
                <div class="brand-title">SIM-FLPP Kantor</div>
                <div class="brand-subtitle">Database & Cetak Berkas KPR</div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section-label">Menu Utama</div>

            <a href="index.php?page=dashboard" class="nav-link <?= $currPage === 'dashboard' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
                <span>Dashboard Ringkasan</span>
            </a>

            <a href="index.php?page=nasabah" class="nav-link <?= in_array($currPage, ['nasabah', 'nasabah_detail'], true) ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span style="flex:1">Database Nasabah</span>
                <span style="background:rgba(255,255,255,0.14);padding:1px 7px;border-radius:99px;font-size:11px;"><?= $totalNasabahNav ?></span>
            </a>

            <?php if (isAdmin()): ?>
            <a href="index.php?page=nasabah_form" class="nav-link <?= $currPage === 'nasabah_form' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                <span>Input Nasabah Baru</span>
            </a>
            <?php endif; ?>

            <a href="index.php?page=pengajuan" class="nav-link <?= $currPage === 'pengajuan' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <span style="flex:1">Form Mandiri Nasabah</span>
                <?php if ($pendingPengajuanNav > 0): ?>
                    <span style="background:#f59e0b;color:#fff;font-weight:700;padding:1px 7px;border-radius:99px;font-size:11px;"><?= $pendingPengajuanNav ?></span>
                <?php endif; ?>
            </a>

            <div class="nav-section-label">Otomatisasi & Berkas</div>

            <a href="index.php?page=surat" class="nav-link <?= $currPage === 'surat' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 15l2 2 4-4"/></svg>
                <span style="flex:1">Pembuatan Surat BSN</span>
                <span style="background:rgba(16,185,129,0.22);color:#6ee7b7;font-weight:700;padding:1px 7px;border-radius:99px;font-size:11px;">18</span>
            </a>

            <a href="index.php?page=master" class="nav-link <?= $currPage === 'master' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/><path d="M9 9h.01"/><path d="M9 13h.01"/><path d="M9 17h.01"/></svg>
                <span>Master PT & Perumahan</span>
            </a>

            <a href="index.php?page=excel" class="nav-link <?= $currPage === 'excel' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                <span>Excel & Mail Merge</span>
            </a>

            <div class="nav-section-label">Sistem & Keamanan</div>

            <a href="index.php?page=users" class="nav-link <?= $currPage === 'users' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <span>Akun & Hak Akses</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="user-pill">
                <div class="user-avatar"><?= e(strtoupper(substr($user['full_name'] ?? 'U', 0, 1))) ?></div>
                <div class="user-meta">
                    <div class="user-name"><?= e($user['full_name'] ?? '') ?></div>
                    <div class="user-role">
                        <?= isAdmin() ? '● Administrator (Full Akses)' : '● Viewer (Lihat Saja)' ?>
                    </div>
                </div>
            </div>
            <form method="post" action="index.php?page=logout" style="margin:0;">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <button type="submit" class="btn btn-outline btn-sm" style="width:100%;background:rgba(255,255,255,0.06);border-color:rgba(255,255,255,0.14);color:#e2e8f0;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    Keluar (Logout)
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Area -->
    <div class="main-area">
        <header class="topbar">
            <div class="topbar-left">
                <button type="button" class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Buka Menu">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <div>
                    <h1 class="page-heading"><?= e($pageTitle ?? 'Dashboard') ?></h1>
                    <?php if (!empty($pageSubtitle)): ?>
                        <p class="page-subheading"><?= e($pageSubtitle) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="topbar-actions">
                <a href="export.php" class="btn btn-outline btn-sm" title="Download Excel (.xlsx) format asli Lembar1 untuk Word Mail Merge">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    <span>Download Excel</span>
                </a>
                <?php if (isAdmin()): ?>
                <a href="index.php?page=nasabah_form" class="btn btn-primary btn-sm">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span>Tambah Nasabah</span>
                </a>
                <?php endif; ?>
            </div>
        </header>

        <main class="content-wrap">
            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] === 'error' ? 'error' : ($flash['type'] === 'info' ? 'info' : 'success') ?>">
                    <span><?= e($flash['message']) ?></span>
                    <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;font-size:16px;color:inherit;">✕</button>
                </div>
            <?php endif; ?>

