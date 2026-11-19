<?php
declare(strict_types=1);

class ExcelService {
    /**
     * Exact 56-column mapping (A through BD) matching Lembar1 in DATA_BASE_FLPP_UPDATE_27_Juli__2026.xlsm
     */
    public const COLUMNS = [
        'A'  => ['field' => 'no_urut',              'header' => 'NO.',                           'clean_num_dot' => false],
        'B'  => ['field' => 'no_rek_pt',            'header' => 'NO.REK PT',                     'clean_num_dot' => true],
        'C'  => ['field' => 'tempat_tgl_surat',     'header' => 'TEMPAT & TGL',                  'clean_num_dot' => false],
        'D'  => ['field' => 'jabatan_direktur',     'header' => 'JABATAN DIREKTUR',              'clean_num_dot' => false],
        'E'  => ['field' => 'nik_direktur',         'header' => 'NIK DIREKTUR',                  'clean_num_dot' => true],
        'F'  => ['field' => 'alamat_direktur',      'header' => 'ALAMAT DIREKTUR',               'clean_num_dot' => false],
        'G'  => ['field' => 'penghasilan_pemohon',  'header' => 'PENGHASILAN PEMOHON',           'clean_num_dot' => false],
        'H'  => ['field' => 'penghasilan_pasangan', 'header' => 'PENGHASILAN PASANGAN',          'clean_num_dot' => false],
        'I'  => ['field' => 'nama_lengkap',         'header' => 'NAMA LENGKAP',                  'clean_num_dot' => false],
        'J'  => ['field' => 'no_hp',                'header' => 'NO.HP',                         'clean_num_dot' => true],
        'K'  => ['field' => 'no_ktp',               'header' => 'NO.KTP',                        'clean_num_dot' => true],
        'L'  => ['field' => 'npwp',                 'header' => 'NPWP',                          'clean_num_dot' => true],
        'M'  => ['field' => 'ttl',                  'header' => 'TEMPAT TANGGAL LAHIR',          'clean_num_dot' => false],
        'N'  => ['field' => 'pekerjaan',            'header' => 'PEKERJAAN',                     'clean_num_dot' => false],
        'O'  => ['field' => 'alamat',               'header' => 'ALAMAT',                        'clean_num_dot' => false],
        'P'  => ['field' => 'penghasilan',          'header' => 'PENGHASILAN',                   'clean_num_dot' => false],
        'Q'  => ['field' => 'harga_jual_rumah',     'header' => 'HARGA JUAL RUMAH',              'clean_num_dot' => false],
        'R'  => ['field' => 'uang_muka',            'header' => 'UANG MUKA',                     'clean_num_dot' => false],
        'S'  => ['field' => 'sbum',                 'header' => 'SBUM',                          'clean_num_dot' => false],
        'T'  => ['field' => 'uang_muka_nasabah',    'header' => 'UANG MUKA NASABAH',             'clean_num_dot' => false],
        'U'  => ['field' => 'kpr',                  'header' => 'KPR',                           'clean_num_dot' => false],
        'V'  => ['field' => 'pengembang',           'header' => 'PENGEMBANG',                    'clean_num_dot' => false],
        'W'  => ['field' => 'tempat_akad',          'header' => 'TEMPAT',                        'clean_num_dot' => false],
        'X'  => ['field' => 'tanggal_akad',         'header' => 'TANGGAL',                       'clean_num_dot' => false],
        'Y'  => ['field' => 'tahun',                'header' => 'TAHUN',                         'clean_num_dot' => false],
        'Z'  => ['field' => 'no_bast',              'header' => 'NO.BAST',                       'clean_num_dot' => false],
        'AA' => ['field' => 'ppjb',                 'header' => 'PPJB',                          'clean_num_dot' => false],
        'AB' => ['field' => 'tanggal_ppjb',         'header' => 'TANGGAL',                       'clean_num_dot' => false],
        'AC' => ['field' => 'tanggal_serah_terima', 'header' => 'TANGGAL SERAH TERIMA',          'clean_num_dot' => false],
        'AD' => ['field' => 'nama_perumahan',       'header' => 'NAMA PERUMAHAN',                'clean_num_dot' => false],
        'AE' => ['field' => 'no_rumah',             'header' => 'NO.RUMAH',                      'clean_num_dot' => false],
        'AF' => ['field' => 'luas_tanah',           'header' => 'LUAS TANAH ',                   'clean_num_dot' => false],
        'AG' => ['field' => 'luas_lantai',          'header' => 'L.LANTAI RUMAH',                'clean_num_dot' => false],
        'AH' => ['field' => 'lokasi_perumahan',     'header' => 'LOKASI PERUMAHAN',              'clean_num_dot' => false],
        'AI' => ['field' => 'nama_direktur',        'header' => 'NAMA DIREKTUR',                 'clean_num_dot' => false],
        'AJ' => ['field' => 'nama_pengembang',      'header' => 'NAMA PENGEMBANG',               'clean_num_dot' => false],
        'AK' => ['field' => 'besaran_uang_muka',    'header' => 'BESARAN UANG MUKA',             'clean_num_dot' => false],
        'AL' => ['field' => 'npwp_pasangan',        'header' => 'NO.NPWP PASANGAN',              'clean_num_dot' => true],
        'AM' => ['field' => 'ktp_pasangan',         'header' => 'NO.KTP PASANGAN',               'clean_num_dot' => true],
        'AN' => ['field' => 'nama_pasangan',        'header' => 'NAMA PASANGAN',                 'clean_num_dot' => false],
        'AO' => ['field' => 'hp_pasangan',          'header' => 'No.Hp PASANGAN',                'clean_num_dot' => true],
        'AP' => ['field' => 'alamat_pasangan',      'header' => 'ALAMAT PASANGAN',               'clean_num_dot' => false],
        'AQ' => ['field' => 'pekerjaan_pasangan',   'header' => 'PEKERJAAN PASANGAN',            'clean_num_dot' => false],
        'AR' => ['field' => 'ttl_pasangan',         'header' => 'TEMPAT TANGGAL LAHIR PASANGAN', 'clean_num_dot' => false],
        'AS' => ['field' => 'nama_komplek',         'header' => 'NAMA KOMPLEK NYA',              'clean_num_dot' => false],
        'AT' => ['field' => null,                   'header' => '',                              'clean_num_dot' => false],
        'AU' => ['field' => 'no_shgb',              'header' => 'NO.SHGB',                       'clean_num_dot' => false],
        'AV' => ['field' => 'no_imb',               'header' => 'NO.IMB',                        'clean_num_dot' => false],
        'AW' => ['field' => 'tgl_berkas',           'header' => 'TGL.BERKAS',                    'clean_num_dot' => false],
        'AX' => ['field' => 'foto_tempat_kerja',    'header' => 'FOTO TEMPAT KERJA',             'clean_num_dot' => false],
        'AY' => ['field' => null,                   'header' => '',                              'clean_num_dot' => false],
        'AZ' => ['field' => null,                   'header' => '',                              'clean_num_dot' => false],
        'BA' => ['field' => null,                   'header' => '',                              'clean_num_dot' => false],
        'BB' => ['field' => 'share_lokasi_kerja',   'header' => 'SHARE LOKASI TEMPAT KERJA',     'clean_num_dot' => false],
        'BC' => ['field' => 'pas_foto',             'header' => 'PAS FOTO',                      'clean_num_dot' => false],
        'BD' => ['field' => 'file_ktp',             'header' => 'KTP',                           'clean_num_dot' => false],
    ];

    /**
     * Parse an .xlsx or .xlsm file and return normalized array of nasabah records
     */
    public static function parseWorkbook(string $filePath): array {
        if (!file_exists($filePath)) {
            throw new RuntimeException("File Excel tidak ditemukan: $filePath");
        }
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new RuntimeException("Gagal membuka file Excel (.xlsx/.xlsm). Pastikan format file valid.");
        }

        try {
            // 1. Read shared strings
            $sharedStrings = [];
            $ssRaw = $zip->getFromName('xl/sharedStrings.xml');
            if ($ssRaw !== false) {
                $prev = libxml_use_internal_errors(true);
                $ssXml = simplexml_load_string($ssRaw);
                if ($ssXml !== false && isset($ssXml->si)) {
                    foreach ($ssXml->si as $si) {
                        if (isset($si->t)) {
                            $sharedStrings[] = (string)$si->t;
                        } elseif (isset($si->r)) {
                            $txt = '';
                            foreach ($si->r as $run) {
                                $txt .= (string)$run->t;
                            }
                            $sharedStrings[] = $txt;
                        } else {
                            $sharedStrings[] = '';
                        }
                    }
                }
                libxml_clear_errors();
                libxml_use_internal_errors($prev);
            }

            // 2. Locate Lembar1 or first sheet
            $sheetPath = self::resolveTargetSheetPath($zip);
            $sheetRaw = $zip->getFromName($sheetPath);
            if ($sheetRaw === false) {
                throw new RuntimeException("Worksheet tidak ditemukan di dalam file Excel.");
            }

            // 3. Stream rows with XMLReader
            $reader = new XMLReader();
            $reader->xml($sheetRaw);

            $records = [];
            while ($reader->read()) {
                if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'row') {
                    $rowNum = (int)$reader->getAttribute('r');
                    if ($rowNum <= 1) {
                        // Skip header row
                        continue;
                    }
                    $outer = $reader->readOuterXML();
                    if ($outer === '') continue;
                    $rowXml = @simplexml_load_string($outer);
                    if ($rowXml === false) continue;

                    $rawCols = [];
                    $rawFormulas = [];
                    foreach ($rowXml->c as $c) {
                        $ref = (string)$c['r'];
                        if (!preg_match('/^([A-Z]+)\d+$/', $ref, $m)) continue;
                        $colLetter = $m[1];
                        $type = (string)$c['t'];
                        $val = '';
                        if ($type === 's') {
                            $idx = (int)$c->v;
                            $val = $sharedStrings[$idx] ?? '';
                        } elseif ($type === 'inlineStr') {
                            $val = isset($c->is->t) ? (string)$c->is->t : '';
                        } else {
                            $val = isset($c->v) ? (string)$c->v : '';
                        }
                        $rawCols[$colLetter] = $val;
                        if (isset($c->f)) {
                            $rawFormulas[$colLetter] = (string)$c->f;
                        }
                    }

                    // Check if this row actually represents a person/record
                    $nama = cleanPlaceholder($rawCols['I'] ?? '');
                    $ktp  = cleanPlaceholder($rawCols['K'] ?? '', true);
                    if ($nama === '' && $ktp === '') {
                        continue;
                    }

                    $record = [];
                    foreach (self::COLUMNS as $col => $meta) {
                        if ($meta['field'] === null) continue;
                        $rawVal = $rawCols[$col] ?? '';
                        $cleaned = cleanPlaceholder($rawVal, $meta['clean_num_dot']);
                        $record[$meta['field']] = $cleaned;
                    }

                    // Normalize currency strings if purely numeric like "182000000" -> "Rp. 182.000.000"
                    foreach (['harga_jual_rumah', 'uang_muka', 'sbum', 'uang_muka_nasabah', 'kpr', 'besaran_uang_muka', 'penghasilan', 'penghasilan_pemohon', 'penghasilan_pasangan'] as $moneyField) {
                        if (!empty($record[$moneyField]) && preg_match('/^\d+$/', $record[$moneyField])) {
                            $record[$moneyField] = formatRupiahExcel($record[$moneyField]);
                        }
                    }

                    // Detect embedded Excel DISPIMG formulas on checklist columns (AX, BB, BC, BD)
                    $record['doc_foto_kerja_check']   = (!empty($rawFormulas['AX']) && str_contains($rawFormulas['AX'], 'DISPIMG')) || !empty($record['foto_tempat_kerja']) ? 1 : 0;
                    $record['doc_lokasi_kerja_check'] = (!empty($rawFormulas['BB']) && str_contains($rawFormulas['BB'], 'DISPIMG')) || !empty($record['share_lokasi_kerja']) ? 1 : 0;
                    $record['doc_pas_foto_check']     = (!empty($rawFormulas['BC']) && str_contains($rawFormulas['BC'], 'DISPIMG')) || !empty($record['pas_foto']) ? 1 : 0;
                    $record['doc_ktp_check']          = (!empty($rawFormulas['BD']) && str_contains($rawFormulas['BD'], 'DISPIMG')) || !empty($record['file_ktp']) ? 1 : 0;

                    // Infer initial status_proses
                    $bastState = getBastState($record['no_bast'] ?? '');
                    if ($bastState === 'lengkap' || !empty($record['tanggal_serah_terima'])) {
                        $record['status_proses'] = 'Sudah Akad / BAST';
                    } elseif ($bastState === 'draft' || !empty($record['no_shgb']) || !empty($record['no_rumah'])) {
                        $record['status_proses'] = 'Siap Akad';
                    } elseif (!empty($record['pengembang']) || !empty($record['nama_perumahan'])) {
                        $record['status_proses'] = 'Proses Bank';
                    } else {
                        $record['status_proses'] = 'Pemberkasan';
                    }

                    $records[] = $record;
                }
            }
            $reader->close();
            return $records;
        } finally {
            $zip->close();
        }
    }

    private static function resolveTargetSheetPath(ZipArchive $zip): string {
        $wbRaw = $zip->getFromName('xl/workbook.xml');
        $relsRaw = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($wbRaw !== false && $relsRaw !== false) {
            $wb = @simplexml_load_string($wbRaw);
            $rels = @simplexml_load_string($relsRaw);
            if ($wb !== false && $rels !== false) {
                $relMap = [];
                foreach ($rels->Relationship as $rel) {
                    $relMap[(string)$rel['Id']] = (string)$rel['Target'];
                }
                $firstTarget = null;
                foreach ($wb->sheets->sheet as $sheet) {
                    $name = trim((string)$sheet['name']);
                    $rId = (string)$sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
                    $target = $relMap[$rId] ?? '';
                    if ($target !== '') {
                        $normalized = str_starts_with($target, 'xl/') ? $target : 'xl/' . ltrim($target, '/');
                        if ($firstTarget === null) $firstTarget = $normalized;
                        if (strcasecmp($name, 'Lembar1') === 0 || strcasecmp($name, 'DATABASE') === 0) {
                            if ($zip->locateName($normalized) !== false) {
                                return $normalized;
                            }
                        }
                    }
                }
                if ($firstTarget !== null && $zip->locateName($firstTarget) !== false) {
                    return $firstTarget;
                }
            }
        }
        return 'xl/worksheets/sheet1.xml';
    }

    /**
     * Generate a valid .xlsx file with exact Lembar1 A..BD columns for Excel & Word Mail Merge
     */
    public static function exportWorkbook(array $rows, string $destPath, bool $fillDotsForMailMerge = false): void {
        if (file_exists($destPath)) {
            @unlink($destPath);
        }
        $zip = new ZipArchive();
        if ($zip->open($destPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Gagal membuat file ekspor Excel.");
        }

        // [Content_Types].xml
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>');

        // _rels/.rels
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>');

        // xl/_rels/workbook.xml.rels
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>');

        // xl/workbook.xml (Keep exact sheet name Lembar1 for Word Mail Merge compatibility!)
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="Lembar1" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>');

        // xl/styles.xml (Style 0 = normal text, Style 1 = bold header with soft navy/slate background)
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="2">
    <font><sz val="10"/><name val="Calibri"/></font>
    <font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
  </fonts>
  <fills count="3">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF1E3A5F"/><bgColor indexed="64"/></patternFill></fill>
  </fills>
  <borders count="2">
    <border><left/><right/><top/><bottom/><diagonal/></border>
    <border>
      <left style="thin"><color rgb="FFD1D5DB"/></left>
      <right style="thin"><color rgb="FFD1D5DB"/></right>
      <top style="thin"><color rgb="FFD1D5DB"/></top>
      <bottom style="thin"><color rgb="FFD1D5DB"/></bottom>
      <diagonal/>
    </border>
  </borders>
  <cellStyleXfs count="1">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
  </cellStyleXfs>
  <cellXfs count="2">
    <xf numFmtId="49" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>
    <xf numFmtId="49" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>
  </cellXfs>
</styleSheet>');

        // Build xl/worksheets/sheet1.xml using XMLWriter
        $xw = new XMLWriter();
        $xw->openMemory();
        $xw->startDocument('1.0', 'UTF-8', 'yes');
        $xw->startElement('worksheet');
        $xw->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        // Freeze top header row
        $xw->startElement('sheetViews');
        $xw->startElement('sheetView');
        $xw->writeAttribute('tabSelected', '1');
        $xw->writeAttribute('workbookViewId', '0');
        $xw->startElement('pane');
        $xw->writeAttribute('ySplit', '1');
        $xw->writeAttribute('topLeftCell', 'A2');
        $xw->writeAttribute('activePane', 'bottomLeft');
        $xw->writeAttribute('state', 'frozen');
        $xw->endElement();
        $xw->endElement();
        $xw->endElement();

        $xw->startElement('sheetData');

        // Row 1: Headers A..BD
        $xw->startElement('row');
        $xw->writeAttribute('r', '1');
        $xw->writeAttribute('ht', '22');
        $xw->writeAttribute('customHeight', '1');
        foreach (self::COLUMNS as $col => $meta) {
            $xw->startElement('c');
            $xw->writeAttribute('r', $col . '1');
            $xw->writeAttribute('s', '1');
            $xw->writeAttribute('t', 'inlineStr');
            $xw->startElement('is');
            $xw->startElement('t');
            if (str_ends_with($meta['header'], ' ')) {
                $xw->writeAttribute('xml:space', 'preserve');
            }
            $xw->text($meta['header']);
            $xw->endElement();
            $xw->endElement();
            $xw->endElement();
        }
        $xw->endElement(); // row 1

        // Data rows
        $dotFieldsForMailMerge = ['no_hp', 'tanggal_akad', 'no_bast', 'ppjb', 'tanggal_ppjb', 'tanggal_serah_terima', 'no_rumah'];
        $rIdx = 2;
        $seq = 1;
        foreach ($rows as $row) {
            $xw->startElement('row');
            $xw->writeAttribute('r', (string)$rIdx);
            foreach (self::COLUMNS as $col => $meta) {
                $field = $meta['field'];
                if ($field === null) continue;

                $val = '';
                if ($field === 'no_urut') {
                    $val = !empty($row['no_urut']) ? (string)$row['no_urut'] : (string)$seq;
                } elseif ($field === 'foto_tempat_kerja') {
                    $val = !empty($row['foto_tempat_kerja']) ? 'Ada (' . basename((string)$row['foto_tempat_kerja']) . ')' : (!empty($row['doc_foto_kerja_check']) ? 'Ada' : '');
                } elseif ($field === 'share_lokasi_kerja') {
                    $val = !empty($row['share_lokasi_kerja']) ? (string)$row['share_lokasi_kerja'] : (!empty($row['doc_lokasi_kerja_check']) ? 'Ada' : '');
                } elseif ($field === 'pas_foto') {
                    $val = !empty($row['pas_foto']) ? 'Ada (' . basename((string)$row['pas_foto']) . ')' : (!empty($row['doc_pas_foto_check']) ? 'Ada' : '');
                } elseif ($field === 'file_ktp') {
                    $val = !empty($row['file_ktp']) ? 'Ada (' . basename((string)$row['file_ktp']) . ')' : (!empty($row['doc_ktp_check']) ? 'Ada' : '');
                } else {
                    $val = (string)($row[$field] ?? '');
                }

                if ($val === '' && $fillDotsForMailMerge && in_array($field, $dotFieldsForMailMerge, true)) {
                    $val = '.......................';
                }

                // Sanitize invalid XML control chars just in case manual Excel had weird control characters
                $val = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $val) ?? '';

                $xw->startElement('c');
                $xw->writeAttribute('r', $col . $rIdx);
                $xw->writeAttribute('s', '0');
                if ($field === 'no_urut' && ctype_digit($val)) {
                    $xw->startElement('v');
                    $xw->text($val);
                    $xw->endElement();
                } else {
                    $xw->writeAttribute('t', 'inlineStr');
                    $xw->startElement('is');
                    $xw->startElement('t');
                    $xw->text($val);
                    $xw->endElement();
                    $xw->endElement();
                }
                $xw->endElement();
            }
            $xw->endElement(); // row
            $rIdx++;
            $seq++;
        }

        $xw->endElement(); // sheetData
        $xw->endElement(); // worksheet
        $xw->endDocument();

        $zip->addFromString('xl/worksheets/sheet1.xml', $xw->outputMemory());
        $zip->close();
    }
}

