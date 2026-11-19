document.addEventListener('DOMContentLoaded', function () {
    // 1. Mobile Sidebar Drawer
    const sidebar = document.getElementById('appSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const menuBtn = document.getElementById('mobileMenuBtn');

    if (menuBtn && sidebar && overlay) {
        menuBtn.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('open');
        });
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('open');
        });
    }

    // 2. Tabs Switcher
    document.querySelectorAll('[data-tab-btn]').forEach(btn => {
        btn.addEventListener('click', () => {
            const group = btn.getAttribute('data-tab-group') || 'default';
            const target = btn.getAttribute('data-tab-btn');

            document.querySelectorAll(`[data-tab-btn][data-tab-group="${group}"]`).forEach(b => b.classList.remove('active'));
            document.querySelectorAll(`[data-tab-pane][data-tab-group="${group}"]`).forEach(p => p.classList.remove('active'));

            btn.classList.add('active');
            const pane = document.querySelector(`[data-tab-pane="${target}"][data-tab-group="${group}"]`);
            if (pane) pane.classList.add('active');
        });
    });

    // 3. Helper to set value and flash green highlight
    window.setAutoValue = function (fieldId, value, onlyIfEmpty = false) {
        const el = document.getElementById(fieldId);
        if (!el) return;
        if (onlyIfEmpty && el.value.trim() !== '') return;
        if (value === undefined || value === null) return;
        el.value = value;
        el.classList.add('autofilled');
        setTimeout(() => el.classList.remove('autofilled'), 1400);
    };

    // 4. Smart Auto-Fill for Master Pengembang & Master Perumahan on Nasabah Form
    const selectPerumahan = document.getElementById('preset_perumahan');
    const selectPengembang = document.getElementById('preset_pengembang');

    if (selectPengembang && window.MASTER_PENGEMBANG) {
        selectPengembang.addEventListener('change', function () {
            const ptId = parseInt(this.value, 10);
            const pt = window.MASTER_PENGEMBANG.find(item => parseInt(item.id, 10) === ptId);
            if (!pt) return;

            setAutoValue('f_pengembang', pt.pengembang);
            setAutoValue('f_nama_pengembang', pt.nama_pengembang || pt.pengembang);
            setAutoValue('f_nama_direktur', pt.nama_direktur);
            setAutoValue('f_jabatan_direktur', pt.jabatan_direktur || 'Direktur');
            setAutoValue('f_nik_direktur', pt.nik_direktur);
            setAutoValue('f_alamat_direktur', pt.alamat_direktur);
            setAutoValue('f_no_rek_pt', pt.no_rek_pt);
            window.CURRENT_KODE_BAST = pt.kode_pt || 'FLPP';
        });
    }

    if (selectPerumahan && window.MASTER_PERUMAHAN) {
        selectPerumahan.addEventListener('change', function () {
            const perumId = parseInt(this.value, 10);
            const perum = window.MASTER_PERUMAHAN.find(item => parseInt(item.id, 10) === perumId);
            if (!perum) return;

            setAutoValue('f_nama_perumahan', perum.nama_perumahan);
            setAutoValue('f_nama_komplek', perum.nama_komplek || perum.nama_perumahan);
            setAutoValue('f_lokasi_perumahan', perum.lokasi_perumahan);
            setAutoValue('f_tempat_akad', perum.tempat_akad || perum.lokasi_perumahan);
            if (perum.luas_tanah) setAutoValue('f_luas_tanah', perum.luas_tanah);
            if (perum.luas_lantai) setAutoValue('f_luas_lantai', perum.luas_lantai);
            window.CURRENT_KODE_BAST = perum.kode_bast || 'FLPP';

            // Also auto-fill linked Pengembang (PT + Direktur + Rekening + Alamat)
            if (perum.pengembang_id && window.MASTER_PENGEMBANG) {
                const pt = window.MASTER_PENGEMBANG.find(item => parseInt(item.id, 10) === parseInt(perum.pengembang_id, 10));
                if (pt) {
                    if (selectPengembang) selectPengembang.value = String(pt.id);
                    setAutoValue('f_pengembang', pt.pengembang);
                    setAutoValue('f_nama_pengembang', pt.nama_pengembang || pt.pengembang);
                    setAutoValue('f_nama_direktur', pt.nama_direktur);
                    setAutoValue('f_jabatan_direktur', pt.jabatan_direktur || 'Direktur');
                    setAutoValue('f_nik_direktur', pt.nik_direktur);
                    setAutoValue('f_alamat_direktur', pt.alamat_direktur);
                    setAutoValue('f_no_rek_pt', pt.no_rek_pt);
                    if (!perum.kode_bast && pt.kode_pt) {
                        window.CURRENT_KODE_BAST = pt.kode_pt;
                    }
                }
            }
        });
    }

    // 5. Auto-Sync NIK -> NPWP (Pemohon & Pasangan) + Digit Counter
    const fKtp = document.getElementById('f_no_ktp');
    const fNpwp = document.getElementById('f_npwp');
    const chkSyncNpwp = document.getElementById('sync_npwp_ktp');
    const nikCounter = document.getElementById('nikDigitCounter');
    if (fKtp) {
        fKtp.addEventListener('input', function () {
            if (fNpwp && chkSyncNpwp && chkSyncNpwp.checked) {
                fNpwp.value = this.value;
            }
            if (nikCounter) {
                const digits = this.value.replace(/\D/g, '').length;
                nikCounter.textContent = digits + '/16 digit';
                nikCounter.style.color = digits === 16 ? '#059669' : '#64748b';
            }
        });
    }

    const fKtpPas = document.getElementById('f_ktp_pasangan');
    const fNpwpPas = document.getElementById('f_npwp_pasangan');
    const chkSyncNpwpPas = document.getElementById('sync_npwp_pasangan');
    if (fKtpPas && fNpwpPas && chkSyncNpwpPas) {
        fKtpPas.addEventListener('input', function () {
            if (chkSyncNpwpPas.checked) {
                fNpwpPas.value = this.value;
            }
        });
    }

    // 6. Auto-Sync Penghasilan -> Penghasilan Pemohon
    const fPenghasilan = document.getElementById('f_penghasilan');
    const fPenghasilanPemohon = document.getElementById('f_penghasilan_pemohon');
    if (fPenghasilan && fPenghasilanPemohon) {
        fPenghasilan.addEventListener('blur', function () {
            if (this.value.trim() !== '' && fPenghasilanPemohon.value.trim() === '') {
                fPenghasilanPemohon.value = this.value;
            }
        });
    }

    // 7. Auto-Calculate KPR & Uang Muka Nasabah
    function parseRp(str) {
        if (!str) return 0;
        const d = String(str).replace(/[^0-9]/g, '');
        return d ? parseInt(d, 10) : 0;
    }
    function formatRp(num) {
        if (!num || num <= 0) return 'Rp. 0';
        return 'Rp. ' + num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    const fHarga = document.getElementById('f_harga_jual_rumah');
    const fUm = document.getElementById('f_uang_muka');
    const fSbum = document.getElementById('f_sbum');
    const fUmNasabah = document.getElementById('f_uang_muka_nasabah');
    const fKpr = document.getElementById('f_kpr');
    const fBesaranUm = document.getElementById('f_besaran_uang_muka');

    function recalcKpr() {
        const harga = parseRp(fHarga ? fHarga.value : '');
        const um = parseRp(fUm ? fUm.value : '');
        const sbum = parseRp(fSbum ? fSbum.value : '');

        if (harga > 0) {
            if (fHarga) fHarga.value = formatRp(harga);
            const kpr = Math.max(0, harga - um);
            if (fKpr) setAutoValue('f_kpr', formatRp(kpr));
        }
        if (um > 0) {
            if (fUm) fUm.value = formatRp(um);
            if (fBesaranUm) setAutoValue('f_besaran_uang_muka', formatRp(um));
            const umNas = Math.max(0, um - sbum);
            if (fUmNasabah) setAutoValue('f_uang_muka_nasabah', formatRp(umNas));
        }
        if (sbum > 0 && fSbum) {
            fSbum.value = formatRp(sbum);
        }
    }

    [fHarga, fUm, fSbum].forEach(el => {
        if (el) el.addEventListener('change', recalcKpr);
    });

    // 8. Copy Alamat Pemohon -> Pasangan button
    const btnCopyAlamat = document.getElementById('btnCopyAlamatPasangan');
    if (btnCopyAlamat) {
        btnCopyAlamat.addEventListener('click', function () {
            const asal = document.getElementById('f_alamat');
            if (asal) setAutoValue('f_alamat_pasangan', asal.value);
        });
    }

    // 9. Generate Draft BAST Number button
    const btnGenBast = document.getElementById('btnGenerateDraftBast');
    if (btnGenBast) {
        btnGenBast.addEventListener('click', function () {
            const kode = window.CURRENT_KODE_BAST || 'SDJ';
            const romawi = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'][new Date().getMonth()];
            const yr = document.getElementById('f_tahun')?.value || new Date().getFullYear();
            setAutoValue('f_no_bast', `........./${kode}/BAST/${romawi}-${yr}`);
        });
    }

    // 10. Bulk Checkboxes on Nasabah List for Selective Mail-Merge Export
    const chkAll = document.getElementById('checkAllRows');
    const rowChecks = document.querySelectorAll('.row-check');
    const bulkBar = document.getElementById('bulkActionBar');
    const bulkCountSpan = document.getElementById('bulkSelectedCount');

    function updateBulkBar() {
        if (!bulkBar) return;
        const checked = document.querySelectorAll('.row-check:checked');
        if (checked.length > 0) {
            bulkBar.style.display = 'flex';
            if (bulkCountSpan) bulkCountSpan.textContent = checked.length;
        } else {
            bulkBar.style.display = 'none';
        }
    }

    if (chkAll) {
        chkAll.addEventListener('change', function () {
            rowChecks.forEach(c => { c.checked = chkAll.checked; });
            updateBulkBar();
        });
    }
    rowChecks.forEach(c => c.addEventListener('change', updateBulkBar));
});

// Modal Helpers
window.openModal = function (id) {
    const m = document.getElementById(id);
    if (m) m.classList.add('open');
};

window.closeModal = function (id) {
    const m = document.getElementById(id);
    if (m) m.classList.remove('open');
};

// Open Quick-Update BAST / Surat Modal on Nasabah List
window.openQuickBastModal = function (btn) {
    const d = btn.dataset;
    document.getElementById('qm_id').value = d.id || '';
    document.getElementById('qm_nama').textContent = d.nama || '';
    document.getElementById('qm_perumahan').textContent = d.perumahan || 'Perumahan belum dipilih';
    document.getElementById('qm_status_proses').value = d.status || 'Pemberkasan';
    document.getElementById('qm_no_rumah').value = d.norumah || '';
    document.getElementById('qm_no_bast').value = d.nobast || '';
    document.getElementById('qm_ppjb').value = d.ppjb || '';
    document.getElementById('qm_tanggal_ppjb').value = d.tglppjb || '';
    document.getElementById('qm_tanggal_serah_terima').value = d.tglserahterima || '';
    document.getElementById('qm_no_shgb').value = d.shgb || '';
    document.getElementById('qm_no_imb').value = d.imb || '';
    document.getElementById('qm_catatan').value = d.catatan || '';
    openModal('quickBastModal');
};

// Export Selected Checkboxes to Excel
window.exportSelectedRows = function (fillDots) {
    const ids = Array.from(document.querySelectorAll('.row-check:checked')).map(c => c.value);
    if (ids.length === 0) return;
    const url = 'export.php?ids=' + encodeURIComponent(ids.join(',')) + (fillDots ? '&dots=1' : '');
    window.location.href = url;
};

// Download Selected Checkboxes as Word Surat BSN (.docx combined or .zip per person)
window.downloadSelectedSuratBsn = function (mode) {
    const ids = Array.from(document.querySelectorAll('.row-check:checked')).map(c => c.value);
    if (ids.length === 0) return;
    const sel = document.getElementById('bulkSuratSelect');
    const docsVal = sel ? sel.value : 'ideb_slik';
    const m = mode === 'zip' ? '&mode=zip' : '&mode=combined';
    window.location.href = 'surat_docx.php?ids=' + encodeURIComponent(ids.join(',')) + '&docs=' + encodeURIComponent(docsVal) + m;
};

// Open Print / PDF Preview for Selected Checkboxes (Any of 18 Surat BSN or Bundle)
window.printSelectedSuratBsn = function () {
    const ids = Array.from(document.querySelectorAll('.row-check:checked')).map(c => c.value);
    if (ids.length === 0) return;
    const sel = document.getElementById('bulkSuratSelect');
    const docsVal = sel ? sel.value : 'ideb_slik';
    if (docsVal === 'all' || docsVal === 'wajib') {
        window.open('print.php?ids=' + encodeURIComponent(ids.join(',')) + '&docs=' + encodeURIComponent(docsVal), '_blank');
    } else {
        window.open('print.php?ids=' + encodeURIComponent(ids.join(',')) + '&tpl=' + encodeURIComponent(docsVal), '_blank');
    }
};

// Backward-compatible aliases
window.downloadSelectedIdebDocx = window.downloadSelectedSuratBsn;
window.printSelectedIdeb = window.printSelectedSuratBsn;

