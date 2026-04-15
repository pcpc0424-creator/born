<?php
/**
 * 엑셀/CSV 파일 파싱 공통 함수
 */

if (!function_exists('parseUploadedFile')) {

/**
 * 업로드 파일 파싱 (CSV, HTML 테이블 xls, xlsx 지원)
 */
function parseUploadedFile(string $filePath, string $extension): array {
    $content = file_get_contents($filePath);
    if ($content === false) return [];

    // HTML 테이블 기반 xls 파일인지 확인
    if (stripos($content, '<table') !== false || stripos($content, '<html') !== false) {
        return parseHtmlTable($content);
    }

    // xlsx 파일 (ZIP 기반 XML)
    if ($extension === 'xlsx') {
        return parseXlsx($filePath);
    }

    // CSV / TSV
    $rows = [];
    $handle = fopen($filePath, 'r');
    if (!$handle) return [];

    // BOM 제거
    $bom = fread($handle, 3);
    if ($bom !== "\xEF\xBB\xBF") {
        rewind($handle);
    }

    // 첫 줄(헤더) 스킵
    $firstLine = fgets($handle);
    $delimiter = (substr_count($firstLine, "\t") > substr_count($firstLine, ",")) ? "\t" : ",";
    rewind($handle);
    if ($bom === "\xEF\xBB\xBF") fread($handle, 3);
    fgetcsv($handle, 0, $delimiter);

    $rowNum = 1;
    while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
        $rowNum++;
        $rows[$rowNum] = $row;
    }
    fclose($handle);
    return $rows;
}

/**
 * HTML 테이블 파싱
 */
function parseHtmlTable(string $html): array {
    $rows = [];
    if (!preg_match_all('/<tr[^>]*>(.*?)<\/tr>/si', $html, $trMatches)) {
        return [];
    }

    $rowNum = 0;
    foreach ($trMatches[1] as $tr) {
        $rowNum++;
        if ($rowNum === 1) continue;

        if (preg_match_all('/<t[dh][^>]*>(.*?)<\/t[dh]>/si', $tr, $tdMatches)) {
            $cells = array_map(function($cell) {
                return trim(strip_tags(html_entity_decode($cell, ENT_QUOTES, 'UTF-8')));
            }, $tdMatches[1]);
            $rows[$rowNum] = $cells;
        }
    }
    return $rows;
}

/**
 * xlsx 파일 파싱 (ZIP + XML)
 */
function parseXlsx(string $filePath): array {
    $rows = [];
    $zip = new ZipArchive();
    if ($zip->open($filePath) !== true) return [];

    $strings = [];
    $ssXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($ssXml) {
        $ss = new SimpleXMLElement($ssXml);
        foreach ($ss->si as $si) {
            $strings[] = (string)$si->t ?: (string)$si;
        }
    }

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    if (!$sheetXml) {
        $zip->close();
        return [];
    }

    $sheet = new SimpleXMLElement($sheetXml);
    $rowNum = 0;
    foreach ($sheet->sheetData->row as $row) {
        $rowNum++;
        if ($rowNum === 1) continue;

        $cells = [];
        foreach ($row->c as $cell) {
            $value = (string)$cell->v;
            if ((string)$cell['t'] === 's' && isset($strings[(int)$value])) {
                $value = $strings[(int)$value];
            }
            $cells[] = $value;
        }
        $rows[$rowNum] = $cells;
    }

    $zip->close();
    return $rows;
}

} // end if !function_exists
