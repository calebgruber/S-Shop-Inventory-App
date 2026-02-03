<?php
/**
 * Pure PHP PDF Generator
 * Minimal PDF creation without external dependencies
 * Based on PDF 1.4 specification
 */

class SimplePDF {
    private $objects = [];
    private $pages = [];
    private $fonts = [];
    private $images = [];
    private $objectCount = 0;

    public function __construct() {
        $this->objectCount = 1;
    }

    public function addPage($width = 612, $height = 792) {
        $pageNum = count($this->pages) + 1;
        $this->pages[$pageNum] = [
            'width' => $width,
            'height' => $height,
            'content' => ''
        ];
        return $pageNum;
    }

    public function addText($page, $x, $y, $text, $size = 12, $font = 'Helvetica') {
        $this->pages[$page]['content'] .= sprintf(
            "BT /F1 %d Tf %d %d Td (%s) Tj ET\n",
            $size,
            $x,
            $y,
            $this->escapeText($text)
        );
    }

    public function addLine($page, $x1, $y1, $x2, $y2) {
        $this->pages[$page]['content'] .= sprintf(
            "%d %d m %d %d l S\n",
            $x1, $y1, $x2, $y2
        );
    }

    public function addRect($page, $x, $y, $width, $height, $fill = false) {
        $op = $fill ? 'f' : 'S';
        $this->pages[$page]['content'] .= sprintf(
            "%d %d %d %d re %s\n",
            $x, $y, $width, $height, $op
        );
    }

    public function addImage($page, $imageData, $x, $y, $width, $height) {
        $imageNum = count($this->images) + 1;
        $this->images[$imageNum] = $imageData;
        
        $this->pages[$page]['content'] .= sprintf(
            "q %d 0 0 %d %d %d cm /Im%d Do Q\n",
            $width, $height, $x, $y, $imageNum
        );
    }

    /**
     * Add a table cell with border (LightWright style)
     */
    public function addTableCell($page, $x, $y, $width, $height, $text, $size = 10, $align = 'left', $border = true) {
        // Draw cell border
        if ($border) {
            $this->addRect($page, $x, $y - $height, $width, $height, false);
        }
        
        // Calculate text position based on alignment
        $textX = $x + 3; // Default left padding
        if ($align == 'center') {
            $textX = $x + ($width / 2) - (strlen($text) * $size * 0.3);
        } elseif ($align == 'right') {
            $textX = $x + $width - (strlen($text) * $size * 0.6) - 3;
        }
        
        // Add text centered vertically in cell
        $textY = $y - ($height / 2) - ($size / 3);
        $this->addText($page, $textX, $textY, $text, $size);
    }

    /**
     * Add a table header row (LightWright style with gray background)
     */
    public function addTableHeader($page, $x, $y, $columns, $height = 20) {
        $currentX = $x;
        
        // Draw gray background for header
        $totalWidth = array_sum(array_column($columns, 'width'));
        $this->setGray($page, 0.9);
        $this->addRect($page, $x, $y - $height, $totalWidth, $height, true);
        $this->setGray($page, 0); // Reset to black
        
        // Draw header cells
        foreach ($columns as $col) {
            $this->addTableCell($page, $currentX, $y, $col['width'], $height, $col['label'], 10, $col['align'] ?? 'left', true);
            $currentX += $col['width'];
        }
        
        return $y - $height; // Return new Y position
    }

    /**
     * Add a table row (LightWright style)
     */
    public function addTableRow($page, $x, $y, $columns, $data, $height = 18) {
        $currentX = $x;
        
        foreach ($columns as $col) {
            $text = isset($data[$col['field']]) ? (string)$data[$col['field']] : '';
            // Truncate long text
            if (strlen($text) > $col['maxlen'] ?? 50) {
                $text = substr($text, 0, ($col['maxlen'] ?? 50) - 3) . '...';
            }
            $this->addTableCell($page, $currentX, $y, $col['width'], $height, $text, 9, $col['align'] ?? 'left', true);
            $currentX += $col['width'];
        }
        
        return $y - $height; // Return new Y position
    }

    /**
     * Set line/text color to gray
     */
    public function setGray($page, $level) {
        $this->pages[$page]['content'] .= sprintf("%s g %s G\n", $level, $level);
    }

    /**
     * Add professional header box (LightWright style)
     */
    public function addInfoBox($page, $x, $y, $width, $height, $title, $fields) {
        // Draw outer border
        $this->addRect($page, $x, $y - $height, $width, $height, false);
        
        // Add title bar with gray background
        $this->setGray($page, 0.9);
        $this->addRect($page, $x, $y - 20, $width, 20, true);
        $this->setGray($page, 0);
        
        $this->addText($page, $x + 5, $y - 14, $title, 11);
        $this->addLine($page, $x, $y - 20, $x + $width, $y - 20);
        
        // Add fields
        $currentY = $y - 35;
        foreach ($fields as $field) {
            $label = $field['label'] . ':';
            $value = $field['value'];
            $this->addText($page, $x + 5, $currentY, $label, 9);
            $this->addText($page, $x + 100, $currentY, $value, 9);
            $currentY -= 15;
        }
    }

    /**
     * Add signature line (LightWright style)
     */
    public function addSignatureLine($page, $x, $y, $width, $label) {
        $this->addLine($page, $x, $y, $x + $width, $y);
        $this->addText($page, $x, $y - 12, $label, 8);
    }

    /**
     * Add page number
     */
    public function addPageNumber($page, $pageNum, $totalPages) {
        $text = "Page $pageNum of $totalPages";
        $this->addText($page, 500, 30, $text, 8);
    }

    private function escapeText($text) {
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace('(', '\\(', $text);
        $text = str_replace(')', '\\)', $text);
        return $text;
    }

    public function output($name = 'document.pdf', $destination = 'I') {
        $pdf = $this->build();
        
        if ($destination == 'I') {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $name . '"');
            header('Content-Length: ' . strlen($pdf));
            echo $pdf;
        } elseif ($destination == 'D') {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $name . '"');
            header('Content-Length: ' . strlen($pdf));
            echo $pdf;
        } elseif ($destination == 'S') {
            return $pdf;
        } elseif ($destination == 'F') {
            file_put_contents($name, $pdf);
        }
    }

    private function build() {
        $offsets = [];
        $pdf = "%PDF-1.4\n";

        // Catalog
        $offsets[1] = strlen($pdf);
        $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        // Pages
        $offsets[2] = strlen($pdf);
        $pageRefs = '';
        for ($i = 1; $i <= count($this->pages); $i++) {
            $pageRefs .= (3 + ($i - 1) * 2) . ' 0 R ';
        }
        $pdf .= "2 0 obj\n<< /Type /Pages /Kids [$pageRefs] /Count " . count($this->pages) . " >>\nendobj\n";

        // Font
        $fontObj = 3 + (count($this->pages) * 2);
        $offsets[$fontObj] = strlen($pdf);
        $pdf .= "$fontObj 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";

        // Image XObjects (if any)
        $imageObjs = [];
        $nextObj = $fontObj + 1;
        foreach ($this->images as $imgNum => $imgData) {
            $imageObjs[$imgNum] = $nextObj;
            $offsets[$nextObj] = strlen($pdf);
            
            // Simple placeholder for PNG images - just reference as XObject
            // In production, this would decode PNG and embed properly
            $pdf .= "$nextObj 0 obj\n";
            $pdf .= "<< /Type /XObject /Subtype /Image ";
            $pdf .= "/Width 100 /Height 50 /ColorSpace /DeviceRGB /BitsPerComponent 8 ";
            $pdf .= "/Length " . strlen($imgData) . " >>\n";
            $pdf .= "stream\n" . $imgData . "\nendstream\nendobj\n";
            $nextObj++;
        }

        // Pages and content
        foreach ($this->pages as $num => $page) {
            $pageObj = 3 + (($num - 1) * 2);
            $contentObjNum = $pageObj + 1;

            // Page object
            $offsets[$pageObj] = strlen($pdf);
            $pdf .= "$pageObj 0 obj\n";
            $pdf .= "<< /Type /Page /Parent 2 0 R ";
            $pdf .= "/MediaBox [0 0 {$page['width']} {$page['height']}] ";
            $pdf .= "/Contents $contentObjNum 0 R ";
            
            // Build resources with fonts and images
            $pdf .= "/Resources << /Font << /F1 $fontObj 0 R >> ";
            if (!empty($imageObjs)) {
                $pdf .= "/XObject << ";
                foreach ($imageObjs as $imgNum => $objNum) {
                    $pdf .= "/Im$imgNum $objNum 0 R ";
                }
                $pdf .= ">> ";
            }
            $pdf .= ">> ";
            $pdf .= ">>\nendobj\n";

            // Content stream
            $offsets[$contentObjNum] = strlen($pdf);
            $content = $page['content'];
            $pdf .= "$contentObjNum 0 obj\n";
            $pdf .= "<< /Length " . strlen($content) . " >>\n";
            $pdf .= "stream\n$content\nendstream\nendobj\n";
        }

        // Cross-reference table
        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($offsets) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        // Trailer
        $pdf .= "trailer\n<< /Size " . (count($offsets) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n$xrefOffset\n%%EOF";

        return $pdf;
    }
}
