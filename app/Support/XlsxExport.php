<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * Escribe un libro .xlsx de una sola hoja con encabezado fijo, sin dependencias externas.
 */
class XlsxExport
{
    /**
     * @param  list<string>  $headers
     * @param  iterable<array<int, string|int|float|null>>  $rows
     */
    public static function store(string $path, string $sheetName, array $headers, iterable $rows): void
    {
        $headers = array_values($headers);
        $data = [];
        $widths = array_map(fn (string $header): int => strlen($header) + 2, $headers);

        foreach ($rows as $row) {
            $row = array_values($row);

            foreach ($row as $index => $value) {
                $widths[$index] = max($widths[$index] ?? 10, min(60, strlen((string) $value) + 2));
            }

            $data[] = $row;
        }

        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo crear el archivo de Excel.');
        }

        $zip->addFromString('[Content_Types].xml', self::contentTypes());
        $zip->addFromString('_rels/.rels', self::packageRelationships());
        $zip->addFromString('xl/workbook.xml', self::workbook($sheetName));
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRelationships());
        $zip->addFromString('xl/styles.xml', self::styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', self::sheet($headers, $data, $widths));
        $zip->close();
    }

    /**
     * @param  list<string>  $headers
     * @param  list<array<int, string|int|float|null>>  $data
     * @param  array<int, int>  $widths
     */
    private static function sheet(array $headers, array $data, array $widths): string
    {
        $columns = '';

        foreach ($widths as $index => $width) {
            $number = $index + 1;
            $columns .= '<col min="'.$number.'" max="'.$number.'" width="'.$width.'" customWidth="1"/>';
        }

        $rows = self::row(1, $headers, 1);

        foreach ($data as $index => $row) {
            $rows .= self::row($index + 2, $row);
        }

        $lastCell = self::column(count($headers)).(count($data) + 1);

        return self::declaration()
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0">'
            .'<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>'
            .'</sheetView></sheetViews>'
            .'<cols>'.$columns.'</cols>'
            .'<sheetData>'.$rows.'</sheetData>'
            .'<autoFilter ref="A1:'.$lastCell.'"/>'
            .'</worksheet>';
    }

    /**
     * @param  array<int, string|int|float|null>  $values
     */
    private static function row(int $number, array $values, int $style = 0): string
    {
        $cells = '';
        $styleAttribute = $style > 0 ? ' s="'.$style.'"' : '';

        foreach (array_values($values) as $index => $value) {
            $reference = self::column($index + 1).$number;

            if ($value === null || $value === '') {
                continue;
            }

            if (is_int($value) || is_float($value)) {
                $cells .= '<c r="'.$reference.'"'.$styleAttribute.'><v>'.$value.'</v></c>';

                continue;
            }

            $cells .= '<c r="'.$reference.'" t="inlineStr"'.$styleAttribute.'><is><t xml:space="preserve">'
                .htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8')
                .'</t></is></c>';
        }

        return '<row r="'.$number.'">'.$cells.'</row>';
    }

    private static function column(int $number): string
    {
        $letters = '';

        while ($number > 0) {
            $number--;
            $letters = chr(65 + $number % 26).$letters;
            $number = intdiv($number, 26);
        }

        return $letters;
    }

    private static function declaration(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    }

    private static function contentTypes(): string
    {
        return self::declaration()
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    private static function packageRelationships(): string
    {
        return self::declaration()
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private static function workbook(string $sheetName): string
    {
        $name = htmlspecialchars($sheetName, ENT_QUOTES | ENT_XML1, 'UTF-8');

        return self::declaration()
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            .' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="'.$name.'" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private static function workbookRelationships(): string
    {
        return self::declaration()
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private static function styles(): string
    {
        return self::declaration()
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2">'
            .'<font><sz val="11"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="11"/><name val="Calibri"/></font>'
            .'</fonts>'
            .'<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            .'<borders count="1"><border/></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="2">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            .'</cellXfs>'
            .'</styleSheet>';
    }
}
