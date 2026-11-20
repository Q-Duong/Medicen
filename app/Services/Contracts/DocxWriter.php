<?php

namespace App\Services\Contracts;

use RuntimeException;
use ZipArchive;

/**
 * Tạo file Word (.docx) đơn giản không cần thư viện: đoạn văn, gạch đầu dòng, bảng, khối chữ ký.
 * Định dạng theo mẫu hợp đồng của công ty: Times New Roman 13, A4, lề trên / dưới 2 cm, trái / phải 2,2 cm, số trang ở chân trang.
 * Chữ in đậm trong đoạn: bọc bằng **…**; in nghiêng cả đoạn: tùy chọn italic.
 */
class DocxWriter
{
    private array $body = [];

    /** Đoạn văn. $o: align (left|center|right|both), bold, italic, size (pt), indent (twips), first (thụt dòng đầu, twips), after (pt), before (pt), keep */
    public function p(string $text, array $o = []): static
    {
        $this->body[] = $this->para($text, $o);

        return $this;
    }

    /** Dòng gạch đầu dòng "- …" (thụt lề treo) */
    public function dash(string $text, array $o = []): static
    {
        // Như hợp đồng gốc: gạch ở 0,5 cm, chữ ngay sau, dòng sau quay về lề
        $o['tab'] = [567, ...(array) ($o['tab'] ?? [])];

        return $this->p('-' . "\t" . $text, $o + ['first' => 284]);
    }

    /** Dòng chấm tròn • (danh sách hình thức trả kết quả) */
    public function bullet(string $text, array $o = []): static
    {
        return $this->p('•' . "\t" . $text, $o + ['indent' => 851, 'hanging' => 284, 'tab' => 851]);
    }

    /**
     * Bảng có viền. $rows: mảng hàng, mỗi ô là chuỗi (nhiều dòng phân cách "\n") hoặc ['text' => …, 'bold' => true, 'align' => 'center'].
     * $widths: độ rộng cột (twips, tổng ≈ 9413). Hàng đầu in đậm, căn giữa nếu $header.
     */
    public function table(array $rows, array $widths, bool $header = true): static
    {
        $grid = implode('', array_map(fn ($w) => '<w:gridCol w:w="' . (int) $w . '"/>', $widths));
        $xml = '<w:tbl><w:tblPr><w:tblW w:w="' . array_sum($widths) . '" w:type="dxa"/><w:tblBorders>'
            . collect(['top', 'left', 'bottom', 'right', 'insideH', 'insideV'])->map(fn ($b) => '<w:' . $b . ' w:val="single" w:sz="4" w:space="0" w:color="000000"/>')->implode('')
            . '</w:tblBorders><w:tblLayout w:type="fixed"/><w:tblCellMar><w:left w:w="100" w:type="dxa"/><w:right w:w="100" w:type="dxa"/></w:tblCellMar></w:tblPr><w:tblGrid>' . $grid . '</w:tblGrid>';
        foreach ($rows as $i => $row) {
            $xml .= '<w:tr>';
            foreach ($row as $j => $cell) {
                $cell = is_array($cell) ? $cell : ['text' => $cell];
                $bold = $cell['bold'] ?? ($header && $i === 0);
                $align = $cell['align'] ?? ($header && $i === 0 ? 'center' : 'left');
                $xml .= '<w:tc><w:tcPr><w:tcW w:w="' . (int) $widths[$j] . '" w:type="dxa"/><w:vAlign w:val="' . ($cell['valign'] ?? 'center') . '"/></w:tcPr>';
                foreach (explode("\n", (string) $cell['text']) as $line) {
                    $xml .= $this->para($line, ['align' => $align, 'bold' => $bold, 'after' => 2, 'before' => 2, 'line' => 1.15]);
                }
                $xml .= '</w:tc>';
            }
            $xml .= '</w:tr>';
        }
        $this->body[] = $xml . '</w:tbl>';

        return $this;
    }

    /** Khối ký tên hai cột không viền: ĐẠI DIỆN BÊN A | ĐẠI DIỆN BÊN B, chừa chỗ ký, tên in đậm */
    public function signatures(string $nameA, string $nameB): static
    {
        $cell = fn ($title, $name) => '<w:tc><w:tcPr><w:tcW w:w="4706" w:type="dxa"/></w:tcPr>' . $this->para('**' . $title . '**', ['align' => 'center'])
            . str_repeat($this->para('', ['after' => 0]), 4) . $this->para('**' . $name . '**', ['align' => 'center']) . '</w:tc>';
        $this->body[] = '<w:tbl><w:tblPr><w:tblW w:w="9412" w:type="dxa"/><w:tblBorders>'
            . collect(['top', 'left', 'bottom', 'right', 'insideH', 'insideV'])->map(fn ($b) => '<w:' . $b . ' w:val="nil"/>')->implode('')
            . '</w:tblBorders><w:tblLayout w:type="fixed"/></w:tblPr><w:tblGrid><w:gridCol w:w="4706"/><w:gridCol w:w="4706"/></w:tblGrid><w:tr><w:cantSplit/>'
            . $cell('ĐẠI DIỆN BÊN A', $nameA) . $cell('ĐẠI DIỆN BÊN B', $nameB) . '</w:tr></w:tbl>';

        return $this;
    }

    /** Đường kẻ ngắn dưới quốc hiệu */
    public function rule(): static
    {
        $this->body[] = '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="0" w:after="200"/></w:pPr><w:r><w:rPr><w:sz w:val="26"/></w:rPr><w:t xml:space="preserve">'
            . str_repeat('–', 1) . '––––––––––––––––––</w:t></w:r></w:p>';

        return $this;
    }

    public function save(string $path, string $title = 'Hợp đồng'): void
    {
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Không tạo được file Word.');
        }
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            . '<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
            . '<Override PartName="/word/settings.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.settings+xml"/>'
            . '<Override PartName="/word/footer1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>');
        $zip->addFromString('word/_rels/document.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/settings" Target="settings.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/footer" Target="footer1.xml"/></Relationships>');
        $zip->addFromString('word/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:docDefaults><w:rPrDefault><w:rPr>'
            . '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:eastAsia="Times New Roman" w:cs="Times New Roman"/><w:sz w:val="26"/><w:szCs w:val="26"/>'
            . '<w:lang w:val="vi-VN" w:eastAsia="en-US" w:bidi="ar-SA"/></w:rPr></w:rPrDefault><w:pPrDefault><w:pPr><w:spacing w:after="80" w:line="300" w:lineRule="auto"/></w:pPr></w:pPrDefault></w:docDefaults>'
            . '<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/><w:qFormat/></w:style>'
            . '<w:style w:type="table" w:default="1" w:styleId="TableNormal"><w:name w:val="Normal Table"/><w:tblPr><w:tblInd w:w="0" w:type="dxa"/>'
            . '<w:tblCellMar><w:top w:w="0" w:type="dxa"/><w:left w:w="108" w:type="dxa"/><w:bottom w:w="0" w:type="dxa"/><w:right w:w="108" w:type="dxa"/></w:tblCellMar></w:tblPr></w:style>'
            . '<w:style w:type="paragraph" w:styleId="Footer"><w:name w:val="footer"/><w:basedOn w:val="Normal"/></w:style></w:styles>');
        $zip->addFromString('word/settings.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:settings xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:defaultTabStop w:val="720"/><w:characterSpacingControl w:val="doNotCompress"/>'
            . '<w:compat><w:compatSetting w:name="compatibilityMode" w:uri="http://schemas.microsoft.com/office/word" w:val="15"/></w:compat></w:settings>');
        $zip->addFromString('word/footer1.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:ftr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:p><w:pPr><w:pStyle w:val="Footer"/><w:jc w:val="right"/></w:pPr>'
            . '<w:r><w:fldChar w:fldCharType="begin"/></w:r><w:r><w:instrText xml:space="preserve"> PAGE </w:instrText></w:r><w:r><w:fldChar w:fldCharType="separate"/></w:r>'
            . '<w:r><w:t>1</w:t></w:r><w:r><w:fldChar w:fldCharType="end"/></w:r></w:p></w:ftr>');
        $now = gmdate('Y-m-d\TH:i:s\Z');
        $zip->addFromString('docProps/core.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" '
            . 'xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:title>' . $this->esc($title) . '</dc:title>'
            . '<dc:creator>Medicen</dc:creator><dcterms:created xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:created>'
            . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:modified></cp:coreProperties>');
        $zip->addFromString('docProps/app.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"><Application>Medicen</Application></Properties>');
        $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><w:body>'
            . implode('', $this->body)
            . '<w:sectPr><w:footerReference w:type="default" r:id="rId3"/><w:pgSz w:w="11907" w:h="16840" w:code="9"/>'
            . '<w:pgMar w:top="1134" w:right="1247" w:bottom="1134" w:left="1247" w:header="720" w:footer="284" w:gutter="0"/><w:pgNumType w:start="1"/></w:sectPr>'
            . '</w:body></w:document>');
        $zip->close();
    }

    // ------------------------------------------------------------------

    private function para(string $text, array $o): string
    {
        $ppr = '<w:pPr>';
        if (!empty($o['keep'])) {
            $ppr .= '<w:keepNext/>';
        }
        if (!empty($o['tab'])) {
            $ppr .= '<w:tabs>' . implode('', array_map(fn ($t) => '<w:tab w:val="left" w:pos="' . (int) $t . '"/>', array_unique((array) $o['tab']))) . '</w:tabs>';
        }
        $ppr .= '<w:spacing w:before="' . (int) round(($o['before'] ?? 0) * 20) . '" w:after="' . (int) round(($o['after'] ?? 4) * 20) . '" w:line="'
            . (int) round(240 * ($o['line'] ?? 1.25)) . '" w:lineRule="auto"/>';
        if (isset($o['indent']) || isset($o['first']) || isset($o['hanging'])) {
            $ppr .= '<w:ind w:left="' . (int) ($o['indent'] ?? 0) . '"' . (isset($o['hanging']) ? ' w:hanging="' . (int) $o['hanging'] . '"' : '')
                . (isset($o['first']) ? ' w:firstLine="' . (int) $o['first'] . '"' : '') . '/>';
        }
        $ppr .= '<w:jc w:val="' . ($o['align'] ?? 'both') . '"/></w:pPr>';

        // **đậm** trong đoạn
        $runs = '';
        foreach (preg_split('/(\*\*.+?\*\*)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $part) {
            $bold = !empty($o['bold']) || (str_starts_with($part, '**') && str_ends_with($part, '**') && mb_strlen($part) > 4);
            $part = preg_replace('/^\*\*(.+)\*\*$/us', '$1', $part);
            $rpr = '<w:rPr>' . ($bold ? '<w:b/><w:bCs/>' : '') . (!empty($o['italic']) ? '<w:i/><w:iCs/>' : '') . (!empty($o['underline']) ? '<w:u w:val="single"/>' : '')
                . (isset($o['size']) ? '<w:sz w:val="' . (int) ($o['size'] * 2) . '"/><w:szCs w:val="' . (int) ($o['size'] * 2) . '"/>' : '') . '</w:rPr>';
            foreach (explode("\t", $part) as $k => $seg) {
                $runs .= ($k ? '<w:r>' . $rpr . '<w:tab/></w:r>' : '') . ($seg !== '' ? '<w:r>' . $rpr . '<w:t xml:space="preserve">' . $this->esc($seg) . '</w:t></w:r>' : '');
            }
        }

        return '<w:p>' . $ppr . $runs . '</w:p>';
    }

    private function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
