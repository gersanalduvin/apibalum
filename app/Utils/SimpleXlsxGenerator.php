<?php

namespace App\Utils;

class SimpleXlsxGenerator
{
    public static function generate(array $headings, array $rows): string
    {
        return self::generateWithMeta([], $headings, $rows);
    }

    public static function generateWithMeta(array $metaRows, array $headings, array $rows, array $merges = []): string
    {
        return self::generateMultiSheet([
            [
                'name' => 'Sheet1',
                'meta' => $metaRows,
                'headings' => $headings,
                'rows' => $rows,
                'merges' => $merges
            ]
        ]);
    }

    public static function generateMultiSheet(array $sheets): string
    {
        // sheets structure: [['name' => 'Sheet1', 'meta' => [], 'headings' => [], 'rows' => [], 'merges' => []], ...]
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', self::contentTypes(count($sheets)));
        $zip->addFromString('_rels/.rels', self::relsRels());
        $zip->addFromString('xl/workbook.xml', self::workbookXml($sheets));
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRels(count($sheets)));
        $zip->addFromString('xl/styles.xml', self::stylesXml());

        foreach ($sheets as $index => $sheet) {
            $sheetId = $index + 1;
            $meta = $sheet['meta'] ?? [];
            $headings = $sheet['headings'] ?? [];
            $rows = $sheet['rows'] ?? [];
            $merges = $sheet['merges'] ?? [];
            $zip->addFromString("xl/worksheets/sheet{$sheetId}.xml", self::sheetXmlWithMeta($meta, $headings, $rows, $merges));
        }
        $zip->close();

        $data = file_get_contents($tmp);
        @unlink($tmp);
        return $data;
    }

    private static function esc($v): string
    {
        return htmlspecialchars((string)$v, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private static function contentTypes(int $count): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';

        for ($i = 1; $i <= $count; $i++) {
            $xml .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        $xml .= '</Types>';
        return $xml;
    }

    private static function relsRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private static function workbookXml(array $sheets): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>';

        foreach ($sheets as $index => $sheet) {
            $id = $index + 1;
            $rId = 'rId' . $id;
            $name = self::esc(mb_substr($sheet['name'] ?? "Sheet{$id}", 0, 31)); // Excel limit 31 chars
            $xml .= '<sheet name="' . $name . '" sheetId="' . $id . '" r:id="' . $rId . '"/>';
        }

        $xml .= '</sheets></workbook>';
        return $xml;
    }

    private static function workbookRels(int $count): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';

        // Sheets
        for ($i = 1; $i <= $count; $i++) {
            $xml .= '<Relationship Id="rId' . $i . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
        }

        // Styles
        $stylesId = 'rId' . ($count + 1);
        $xml .= '<Relationship Id="' . $stylesId . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        $xml .= '</Relationships>';
        return $xml;
    }

    private static function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            . '<font><sz val="11"/><name val="Calibri"/></font>' // 0: Normal
            . '<font><b/><sz val="11"/><name val="Calibri"/></font>' // 1: Bold
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFFFCCCC"/></patternFill></fill>' // 2: Light Red
            . '</fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>' // 0: None
            . '<border>' // 1: All Borders
            . '<left style="thin"><color auto="1"/></left>'
            . '<right style="thin"><color auto="1"/></right>'
            . '<top style="thin"><color auto="1"/></top>'
            . '<bottom style="thin"><color auto="1"/></bottom>'
            . '<diagonal/>'
            . '</border>'
            . '</borders>'
            . '<cellStyleXfs count="1">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>'
            . '</cellStyleXfs>'
            . '<cellXfs count="8">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' // 0: Default
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>' // 1: Bold
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>' // 2: Bordered
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1"/>' // 3: Bold + Bordered
            . '<xf numFmtId="0" fontId="0" fillId="2" borderId="1" xfId="0" applyFill="1" applyBorder="1"/>' // 4: Red + Bordered
            . '<xf numFmtId="4" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>' // 5: Bordered + Number
            . '<xf numFmtId="4" fontId="0" fillId="2" borderId="1" xfId="0" applyNumberFormat="1" applyFill="1" applyBorder="1"/>' // 6: Red + Bordered + Number
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment wrapText="1" vertical="top"/></xf>' // 7: Bordered + WrapText
            . '</cellXfs>'
            . '</styleSheet>';
    }

    private static function sheetXmlWithMeta(array $metaRows, array $headings, array $rows, array $merges = []): string
    {
        // 1. Calculate Columns Widths
        $widths = [];

        // Check meta rows
        foreach ($metaRows as $r) {
            foreach ($r as $k => $v) {
                $len = mb_strlen((string)$v);
                if (!isset($widths[$k])) $widths[$k] = 0;
                if ($len > $widths[$k]) $widths[$k] = $len;
            }
        }

        // Check headers
        foreach ($headings as $k => $v) {
            $len = mb_strlen((string)$v);
            if (!isset($widths[$k])) $widths[$k] = 0;
            if ($len > $widths[$k]) $widths[$k] = $len;
        }

        // Check rows
        foreach ($rows as $r) {
            $rowData = $r;
            if (isset($r['data']) && is_array($r['data'])) {
                $rowData = $r['data'];
            }
            foreach ($rowData as $k => $v) {
                $lines = explode("\n", (string)$v);
                $maxLineLen = 0;
                foreach ($lines as $line) {
                    $l = mb_strlen($line);
                    if ($l > $maxLineLen) $maxLineLen = $l;
                }
                if (!isset($widths[$k])) $widths[$k] = 0;
                if ($maxLineLen > $widths[$k]) $widths[$k] = $maxLineLen;
            }
        }

        // Generate XML
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<cols>';

        foreach ($widths as $k => $w) {
            $w = min($w + 2, 60);
            $colIdx = $k + 1;
            $xml .= '<col min="' . $colIdx . '" max="' . $colIdx . '" width="' . $w . '" customWidth="1"/>';
        }
        $xml .= '</cols><sheetData>';

        $rowIndex = 1;

        // Meta Rows
        foreach ($metaRows as $mr) {
            $xml .= '<row r="' . $rowIndex . '">';
            foreach ($mr as $colIndex => $v) {
                $col = self::colLetter($colIndex + 1) . $rowIndex;
                // Detect label
                $s = (substr((string)$v, -1) === ':') ? '1' : '0';
                $xml .= '<c r="' . $col . '" t="inlineStr" s="' . $s . '"><is><t>' . self::esc($v) . '</t></is></c>';
            }
            $xml .= '</row>';
            $rowIndex++;
        }

        // Headers
        if (!empty($headings)) {
            $xml .= '<row r="' . $rowIndex . '">';
            foreach ($headings as $colIndex => $h) {
                $col = self::colLetter($colIndex + 1) . $rowIndex;
                $xml .= '<c r="' . $col . '" t="inlineStr" s="3"><is><t>' . self::esc($h) . '</t></is></c>';
            }
            $xml .= '</row>';
        }

        // Data Rows
        foreach ($rows as $r) {
            $rowIndex++;
            $xml .= '<row r="' . $rowIndex . '">';

            $rowData = $r;
            $rowStyle = 2; // Default bordered

            if (isset($r['data']) && is_array($r['data'])) {
                $rowData = $r['data'];
                if (isset($r['style'])) {
                    $rowStyle = $r['style'];
                }
            }

            foreach ($rowData as $colIndex => $v) {
                $col = self::colLetter($colIndex + 1) . $rowIndex;

                $cellType = 'inlineStr';
                $cellStyle = $rowStyle;
                $cellValue = $v;

                if (is_int($v) || is_float($v)) {
                    $cellType = 'n';
                    // Promote style to numeric if it's currently text-bordered
                    if ($rowStyle === 2) $cellStyle = 5; // Bordered -> Bordered Number
                    if ($rowStyle === 4) $cellStyle = 6; // Red -> Red Number
                }

                $xml .= '<c r="' . $col . '" t="' . $cellType . '" s="' . $cellStyle . '">';

                if ($cellType === 'n') {
                    $xml .= '<v>' . $v . '</v>';
                } else {
                    $xml .= '<is><t>' . self::esc($v) . '</t></is>';
                }

                $xml .= '</c>';
            }
            $xml .= '</row>';
        }
        $xml .= '</sheetData>';

        if (!empty($merges)) {
            $xml .= '<mergeCells count="' . count($merges) . '">';
            foreach ($merges as $m) {
                $xml .= '<mergeCell ref="' . $m . '"/>';
            }
            $xml .= '</mergeCells>';
        }

        $xml .= '</worksheet>';
        return $xml;
    }

    private static function colLetter(int $num): string
    {
        $letters = '';
        while ($num > 0) {
            $num--;
            $letters = chr(65 + ($num % 26)) . $letters;
            $num = intdiv($num, 26);
        }
        return $letters;
    }
}
