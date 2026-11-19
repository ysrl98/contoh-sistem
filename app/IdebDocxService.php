<?php
declare(strict_types=1);

/**
 * Service untuk membuat dokumen PERMOHONAN iDEB (SLIK OJK) Bank Syariah Nasional
 * secara otomatis sesuai data per Nasabah (baik file Word .docx asli maupun data HTML/PDF).
 */
class IdebDocxService {
    private const W_NS    = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
    private const WP_NS   = 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing';
    private const PIC_NS  = 'http://schemas.openxmlformats.org/drawingml/2006/picture';
    private const WP14_NS = 'http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing';
    private const XML_NS  = 'http://www.w3.org/XML/1998/namespace';

    public static function getTemplatePath(): string {
        return dirname(__DIR__) . '/storage/templates/template_ideb_bsn.docx';
    }

    /**
     * Pastikan logo Bank Syariah Nasional (hasil crop presisi sesuai <a:srcRect> di template Word)
     * tersedia di assets/img/logo_bsn.png untuk tampilan cetak HTML / PDF di browser.
     */
    public static function ensureBsnLogoAsset(): string {
        $relTarget = 'assets/img/logo_bsn.png';
        $absTarget = dirname(__DIR__) . '/' . $relTarget;
        if (file_exists($absTarget) && filesize($absTarget) > 100) {
            return $relTarget;
        }

        $absSrc = dirname(__DIR__) . '/assets/img/ideb_image1.png';
        if (!file_exists($absSrc) || !function_exists('imagecreatefrompng')) {
            return '';
        }

        $src = @imagecreatefrompng($absSrc);
        if (!$src) {
            return '';
        }

        $w = imagesx($src);
        $h = imagesy($src);
        // Koordinat <a:srcRect l="85633" t="12029" r="1453" b="67620"/> dari word/document.xml
        $cropX = (int)round($w * 0.85633);
        $cropY = (int)round($h * 0.12029);
        $cropW = max(1, (int)round($w * (1.0 - 0.85633 - 0.01453)));
        $cropH = max(1, (int)round($h * (1.0 - 0.12029 - 0.67620)));

        $dst = imagecreatetruecolor($cropW, $cropH);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $cropW, $cropH, $white);
        imagecopy($dst, $src, 0, 0, $cropX, $cropY, $cropW, $cropH);
        @imagefilter($dst, IMG_FILTER_CONTRAST, -22);

        $dir = dirname($absTarget);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        @imagepng($dst, $absTarget);
        imagedestroy($src);
        imagedestroy($dst);

        return file_exists($absTarget) ? $relTarget : '';
    }

    /**
     * Siapkan & bersihkan seluruh nilai field iDEB (SLIK OJK) untuk 1 nasabah.
     * Menangani otomatis:
     * - Fallback NPWP -> NIK KTP bila NPWP belum diisi terpisah
     * - Pengosongan bersih tabel Pasangan bila nasabah lajang / belum ada data pasangan
     * - Format kota & tanggal surat (misal: "Banjar, 30 Oktober 2026")
     */
    public static function prepareData(
        array $r,
        string $defaultKota = 'Banjar',
        string $customTanggal = '',
        string $namaFs = ''
    ): array {
        $nama   = cleanPlaceholder($r['nama_lengkap'] ?? '');
        $ktp    = cleanPlaceholder($r['no_ktp'] ?? '', true);
        $npwp   = cleanPlaceholder($r['npwp'] ?? '', true);
        if ($npwp === '' && $ktp !== '') {
            $npwp = $ktp;
        }
        $ttl    = cleanPlaceholder($r['ttl'] ?? '');
        $alamat = cleanPlaceholder($r['alamat'] ?? '');

        $namaPas = cleanPlaceholder($r['nama_pasangan'] ?? '');
        $ktpPas  = cleanPlaceholder($r['ktp_pasangan'] ?? '', true);
        $hasPasangan = ($namaPas !== '' || $ktpPas !== '');

        $npwpPas   = $hasPasangan ? (cleanPlaceholder($r['npwp_pasangan'] ?? '', true) ?: $ktpPas) : '';
        $ttlPas    = $hasPasangan ? cleanPlaceholder($r['ttl_pasangan'] ?? '') : '';
        $alamatPas = $hasPasangan ? cleanPlaceholder($r['alamat_pasangan'] ?? '') : '';

        // Resolusi Tanggal Surat
        $customTanggal = trim($customTanggal);
        if ($customTanggal !== '') {
            $tanggalSurat = $customTanggal;
        } else {
            $tb = cleanPlaceholder($r['tgl_berkas'] ?? '');
            if ($tb !== '') {
                if (!str_contains($tb, ',') && preg_match('/^\d/', $tb) && $defaultKota !== '') {
                    $tanggalSurat = $defaultKota . ', ' . $tb;
                } else {
                    $tanggalSurat = $tb;
                }
            } else {
                $tanggalSurat = tanggalIndoSekarang($defaultKota);
            }
        }

        $namaFsClean = trim($namaFs);
        $fsBracket = $namaFsClean !== ''
            ? '(  ' . $namaFsClean . '  )'
            : '(                           )';

        return [
            'id'              => (int)($r['id'] ?? 0),
            'no_urut'         => cleanPlaceholder($r['no_urut'] ?? ''),
            'nama_lengkap'    => $nama,
            'no_ktp'          => $ktp,
            'npwp'            => $npwp,
            'ttl'             => $ttl,
            'alamat'          => $alamat,
            'has_pasangan'    => $hasPasangan,
            'nama_pasangan'   => $namaPas,
            'ktp_pasangan'    => $ktpPas,
            'npwp_pasangan'   => $npwpPas,
            'ttl_pasangan'    => $ttlPas,
            'alamat_pasangan' => $alamatPas,
            'tanggal_surat'   => $tanggalSurat,
            'nama_fs'         => $namaFsClean,
            'fs_bracket'      => $fsBracket,
            'merge'           => [
                'NAMA_LENGKAP'                  => $nama,
                'NOKTP'                         => $ktp,
                'NPWP'                          => $npwp,
                'TEMPAT_TANGGAL_LAHIR'          => $ttl,
                'ALAMAT'                        => $alamat,
                'NAMA_PASANGAN'                 => $namaPas,
                'NOKTP_PASANGAN'                => $ktpPas,
                'NONPWP_PASANGAN'               => $npwpPas,
                'TEMPAT_TANGGAL_LAHIR_PASANGAN' => $ttlPas,
                'ALAMAT_PASANGAN'               => $alamatPas,
                'TGLBERKAS'                     => '',
            ],
        ];
    }

    /**
     * Buat file Word (.docx) siap pakai untuk 1 atau banyak nasabah sekaligus (gabungan multi-halaman).
     * Menghapus link SQL Mail Merge lama agar saat dibuka di Microsoft Word langsung bersih tanpa popup error.
     */
    public static function generateDocx(
        array $nasabahRows,
        string $destPath,
        string $defaultKota = 'Banjar',
        string $customTanggal = '',
        string $namaFs = ''
    ): void {
        if (empty($nasabahRows)) {
            throw new InvalidArgumentException('Data nasabah kosong.');
        }

        $tplPath = self::getTemplatePath();
        if (!file_exists($tplPath)) {
            throw new RuntimeException('File template Word SLIK OJK tidak ditemukan di storage/templates/template_ideb_bsn.docx');
        }

        if (!copy($tplPath, $destPath)) {
            throw new RuntimeException('Gagal menyiapkan file Word sementara.');
        }

        $zip = new ZipArchive();
        if ($zip->open($destPath) !== true) {
            @unlink($destPath);
            throw new RuntimeException('Gagal membuka arsip .docx.');
        }

        try {
            $docXmlRaw = $zip->getFromName('word/document.xml');
            if ($docXmlRaw === false) {
                throw new RuntimeException('Struktur word/document.xml tidak ditemukan dalam template.');
            }

            // 1. Bersihkan koneksi Mail Merge di word/settings.xml agar Word tidak memunculkan dialog SQL
            $settingsRaw = $zip->getFromName('word/settings.xml');
            if ($settingsRaw !== false) {
                $cleanSettings = self::stripMailMergeSettings($settingsRaw);
                $zip->addFromString('word/settings.xml', $cleanSettings);
            }
            if ($zip->locateName('word/_rels/settings.xml.rels') !== false) {
                $zip->deleteName('word/_rels/settings.xml.rels');
            }

            // 2. Render dokumen untuk 1 atau banyak nasabah
            $total = count($nasabahRows);
            $masterDom = null;
            $masterBody = null;

            foreach (array_values($nasabahRows) as $idx => $row) {
                $prepared = self::prepareData($row, $defaultKota, $customTanggal, $namaFs);
                $pageDom  = self::renderSingleDocumentDom($docXmlRaw, $prepared, $idx);

                if ($idx === 0) {
                    $masterDom = $pageDom;
                    $masterBody = $masterDom->getElementsByTagNameNS(self::W_NS, 'body')->item(0);
                    if (!$masterBody) {
                        throw new RuntimeException('Elemen w:body tidak ditemukan.');
                    }

                    // Jika lebih dari 1 nasabah, ubah sectPr halaman pertama menjadi section break di akhir halaman 1
                    if ($total > 1) {
                        self::convertBodySectPrToParagraphBreak($masterDom, $masterBody);
                    }
                } else {
                    $pageBody = $pageDom->getElementsByTagNameNS(self::W_NS, 'body')->item(0);
                    if (!$pageBody || !$masterDom || !$masterBody) {
                        continue;
                    }
                    $isLast = ($idx === $total - 1);
                    if (!$isLast) {
                        self::convertBodySectPrToParagraphBreak($pageDom, $pageBody);
                    }

                    foreach (iterator_to_array($pageBody->childNodes) as $child) {
                        $imported = $masterDom->importNode($child, true);
                        $masterBody->appendChild($imported);
                    }
                }
            }

            if ($masterDom !== null) {
                $finalXml = $masterDom->saveXML();
                if ($finalXml !== false) {
                    $zip->addFromString('word/document.xml', $finalXml);
                }
            }
        } finally {
            $zip->close();
        }
    }

    /**
     * Buat arsip .zip berisi masing-masing file .docx terpisah per nasabah
     */
    public static function generateBulkZip(
        array $nasabahRows,
        string $zipDestPath,
        string $defaultKota = 'Banjar',
        string $customTanggal = '',
        string $namaFs = ''
    ): void {
        if (file_exists($zipDestPath)) {
            @unlink($zipDestPath);
        }
        $zip = new ZipArchive();
        if ($zip->open($zipDestPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Gagal membuat arsip ZIP.');
        }

        $tempFiles = [];
        $usedNames = [];

        try {
            foreach ($nasabahRows as $idx => $row) {
                $tmpDocx = tempnam(sys_get_temp_dir(), 'ideb_item_') . '.docx';
                self::generateDocx([$row], $tmpDocx, $defaultKota, $customTanggal, $namaFs);
                $tempFiles[] = $tmpDocx;

                $safeName = self::sanitizeFilename($row['nama_lengkap'] ?? ('Nasabah_' . ($idx + 1)));
                $noUrut   = preg_replace('/[^0-9A-Za-z]/', '', (string)($row['no_urut'] ?? ''));
                $prefix   = $noUrut !== '' ? sprintf('%03s_', $noUrut) : '';
                $entryName = 'PERMOHONAN_SLIK_OJK_' . $prefix . $safeName . '.docx';

                if (isset($usedNames[$entryName])) {
                    $usedNames[$entryName]++;
                    $entryName = 'PERMOHONAN_SLIK_OJK_' . $prefix . $safeName . '_' . $usedNames[$entryName] . '.docx';
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

    public static function sanitizeFilename(string $raw): string {
        $clean = preg_replace('/[^A-Za-z0-9_\-]+/', '_', trim($raw)) ?? 'Nasabah';
        $clean = trim($clean, '_');
        return $clean !== '' ? substr($clean, 0, 60) : 'Nasabah';
    }

    private static function stripMailMergeSettings(string $settingsXml): string {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;
        if (!@$dom->loadXML($settingsXml)) {
            return $settingsXml;
        }
        $nodes = $dom->getElementsByTagNameNS(self::W_NS, 'mailMerge');
        foreach (iterator_to_array($nodes) as $node) {
            if ($node->parentNode) {
                $node->parentNode->removeChild($node);
            }
        }
        return $dom->saveXML() ?: $settingsXml;
    }

    /**
     * Ubah elemen penutup <w:sectPr> di akhir <w:body> menjadi <w:p><w:pPr><w:sectPr>...</w:sectPr></w:pPr></w:p>
     * agar halaman berikutnya mulai di halaman A4 baru dengan posisi logo anchor yang presisi.
     */
    private static function convertBodySectPrToParagraphBreak(DOMDocument $dom, DOMElement $body): void {
        $sectPr = null;
        for ($n = $body->lastChild; $n !== null; $n = $n->previousSibling) {
            if ($n instanceof DOMElement && $n->namespaceURI === self::W_NS && $n->localName === 'sectPr') {
                $sectPr = $n;
                break;
            }
        }
        if (!$sectPr) {
            return;
        }

        // Hapus beberapa paragraf kosong berlebih tepat sebelum sectPr agar section break tidak melompat ke halaman kosong
        while ($sectPr->previousSibling instanceof DOMElement) {
            $prev = $sectPr->previousSibling;
            if ($prev->namespaceURI === self::W_NS && $prev->localName === 'p' && trim($prev->textContent) === '' && $prev->getElementsByTagName('drawing')->length === 0) {
                $body->removeChild($prev);
            } else {
                break;
            }
        }

        $body->removeChild($sectPr);
        $p = $dom->createElementNS(self::W_NS, 'w:p');
        $pPr = $dom->createElementNS(self::W_NS, 'w:pPr');
        $pPr->appendChild($sectPr);
        $p->appendChild($pPr);
        $body->appendChild($p);
    }

    private static function renderSingleDocumentDom(string $docXmlRaw, array $prepared, int $pageIndex): DOMDocument {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;
        if (!@$dom->loadXML($docXmlRaw)) {
            throw new RuntimeException('Gagal memproses XML dokumen Word.');
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', self::W_NS);
        $xpath->registerNamespace('wp', self::WP_NS);
        $xpath->registerNamespace('pic', self::PIC_NS);

        // 1. Hapus bookmark kosong (OLE_LINK1/2) agar tidak bentrok ID pada mode gabungan
        foreach (iterator_to_array($xpath->query('//w:bookmarkStart | //w:bookmarkEnd')) as $bm) {
            if ($bm->parentNode) {
                $bm->parentNode->removeChild($bm);
            }
        }

        // 2. Pastikan ID gambar unik per halaman untuk kepatuhan OOXML
        $idBase = ($pageIndex * 10) + 1;
        $k = 0;
        foreach (iterator_to_array($xpath->query('//wp:docPr')) as $docPr) {
            if ($docPr instanceof DOMElement) {
                $docPr->setAttribute('id', (string)($idBase + $k));
                $k++;
            }
        }
        $k = 0;
        foreach (iterator_to_array($xpath->query('//pic:cNvPr')) as $cNvPr) {
            if ($cNvPr instanceof DOMElement) {
                $cNvPr->setAttribute('id', (string)($idBase + $k));
                $k++;
            }
        }
        $k = 0;
        foreach (iterator_to_array($xpath->query('//wp:anchor | //wp:inline')) as $drawEl) {
            if ($drawEl instanceof DOMElement) {
                if ($drawEl->hasAttributeNS(self::WP14_NS, 'anchorId')) {
                    $drawEl->setAttributeNS(self::WP14_NS, 'wp14:anchorId', sprintf('%08X', 0x20000000 + ($pageIndex * 16) + $k));
                }
                if ($drawEl->hasAttributeNS(self::WP14_NS, 'editId')) {
                    $drawEl->setAttributeNS(self::WP14_NS, 'wp14:editId', sprintf('%08X', 0x30000000 + ($pageIndex * 16) + $k));
                }
                $k++;
            }
        }

        // 3. Ganti baris tanda tangan spasi manual menjadi tabel 3 kolom rapi tanpa garis (anti-geser untuk nama panjang)
        self::replaceSignatureParagraphsWithTable($dom, $xpath, $prepared);

        // 4. Update baris tanggal surat ("Banjar, 22 Oktober 2026" -> tanggal sesuai nasabah / hari ini)
        foreach (iterator_to_array($xpath->query('//w:t')) as $tNode) {
            if ($tNode instanceof DOMElement && trim($tNode->textContent) === 'Banjar, 22 Oktober 2026') {
                while ($tNode->firstChild) {
                    $tNode->removeChild($tNode->firstChild);
                }
                $tNode->appendChild($dom->createTextNode($prepared['tanggal_surat']));
            }
        }

        // 5. Ganti seluruh MERGEFIELD kompleks (w:fldChar begin..end) dengan teks bersih
        foreach (iterator_to_array($xpath->query('//w:p')) as $pNode) {
            if ($pNode instanceof DOMElement) {
                self::replaceComplexFieldsInParagraph($dom, $pNode, $prepared['merge']);
            }
        }

        return $dom;
    }

    private static function replaceSignatureParagraphsWithTable(DOMDocument $dom, DOMXPath $xpath, array $prepared): void {
        $paragraphs = iterator_to_array($xpath->query('/w:document/w:body/w:p'));
        $bracketP = null;
        $labelP   = null;

        $count = count($paragraphs);
        for ($i = 0; $i < $count; $i++) {
            $p = $paragraphs[$i];
            if (!$p instanceof DOMElement) continue;
            $txt = trim($p->textContent);
            if (preg_match('/^\(\s*\)\s*\(\s*\)\s*\(\s*\)$/u', $txt)) {
                $bracketP = $p;
                if ($i + 1 < $count && $paragraphs[$i + 1] instanceof DOMElement) {
                    $nextTxt = $paragraphs[$i + 1]->textContent;
                    if (stripos($nextTxt, 'Financing Service') !== false) {
                        $labelP = $paragraphs[$i + 1];
                    }
                }
                break;
            }
        }

        if (!$bracketP || !$bracketP->parentNode) {
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

        // Baris 1: Tanda Kurung Tanda Tangan
        $row1Texts = [
            $prepared['fs_bracket'],
            '(                                       )',
            '(                           )',
        ];
        $tr1 = $dom->createElementNS(self::W_NS, 'w:tr');
        foreach ($colWidths as $idx => $cw) {
            $tr1->appendChild(self::createSignCell($dom, $cw, $row1Texts[$idx], false, '0', '20'));
        }
        $tbl->appendChild($tr1);

        // Baris 2: Label Jabatan / Nama Terang
        $row2Items = [
            ['text' => 'Financing Service',       'bold' => false],
            ['text' => $prepared['nama_lengkap'], 'bold' => true],
            ['text' => $prepared['nama_pasangan'],'bold' => true],
        ];
        $tr2 = $dom->createElementNS(self::W_NS, 'w:tr');
        foreach ($colWidths as $idx => $cw) {
            $tr2->appendChild(self::createSignCell($dom, $cw, $row2Items[$idx]['text'], $row2Items[$idx]['bold'], '20', '120'));
        }
        $tbl->appendChild($tr2);

        $bracketP->parentNode->replaceChild($tbl, $bracketP);
        if ($labelP && $labelP->parentNode) {
            $labelP->parentNode->removeChild($labelP);
        }
    }

    private static function createSignCell(
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
            $p->appendChild(self::createFormattedRun($dom, $text, $bold));
        }

        $tc->appendChild($p);
        return $tc;
    }

    private static function replaceComplexFieldsInParagraph(DOMDocument $dom, DOMElement $p, array $mergeMap): void {
        $children = iterator_to_array($p->childNodes);
        $n = count($children);
        $i = 0;

        while ($i < $n) {
            $node = $children[$i];
            if ($node instanceof DOMElement && $node->namespaceURI === self::W_NS && $node->localName === 'r') {
                $fldType = self::getRunFldCharType($node);
                if ($fldType === 'begin') {
                    $depth = 1;
                    $group = [$node];
                    $instr = '';
                    $j = $i + 1;

                    while ($j < $n && $depth > 0) {
                        $cur = $children[$j];
                        $group[] = $cur;
                        if ($cur instanceof DOMElement && $cur->namespaceURI === self::W_NS && $cur->localName === 'r') {
                            $t = self::getRunFldCharType($cur);
                            if ($t === 'begin') {
                                $depth++;
                            } elseif ($t === 'end') {
                                $depth--;
                            }
                            if ($depth > 0) {
                                foreach ($cur->getElementsByTagNameNS(self::W_NS, 'instrText') as $inNode) {
                                    $instr .= $inNode->textContent;
                                }
                            }
                        }
                        $j++;
                    }

                    if ($depth === 0 && preg_match('/MERGEFIELD\s+"?([A-Za-z0-9_]+)"?/i', $instr, $m)) {
                        $fieldKey = strtoupper($m[1]);
                        $val = (string)($mergeMap[$fieldKey] ?? '');
                        $isBold = in_array($fieldKey, ['NAMA_LENGKAP', 'NAMA_PASANGAN'], true);

                        if ($val !== '') {
                            $newRun = self::createFormattedRun($dom, $val, $isBold);
                            $p->insertBefore($newRun, $group[0]);
                        }
                        foreach ($group as $gNode) {
                            if ($gNode->parentNode === $p) {
                                $p->removeChild($gNode);
                            }
                        }
                        $i = $j;
                        continue;
                    }
                }
            }
            $i++;
        }
    }

    private static function getRunFldCharType(DOMElement $r): ?string {
        foreach ($r->getElementsByTagNameNS(self::W_NS, 'fldChar') as $fc) {
            if ($fc instanceof DOMElement) {
                $t = $fc->getAttributeNS(self::W_NS, 'fldCharType');
                if ($t === '') {
                    $t = $fc->getAttribute('w:fldCharType');
                }
                if ($t !== '') {
                    return $t;
                }
            }
        }
        return null;
    }

    private static function createFormattedRun(DOMDocument $dom, string $text, bool $bold = false): DOMElement {
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
        if ($text !== trim($text) || str_contains($text, '  ')) {
            $t->setAttributeNS(self::XML_NS, 'xml:space', 'preserve');
        }
        $t->appendChild($dom->createTextNode($text));
        $r->appendChild($t);

        return $r;
    }
}
