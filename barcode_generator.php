<?php
require_once 'config.php';

// Get parameters
$type = $_GET['type'] ?? 'code128'; // code128 or pdf417
$data = $_GET['data'] ?? '';
$download = isset($_GET['download']) && $_GET['download'] == '1';

if (empty($data)) {
    die('No data provided');
}

// For simplicity, we'll use a barcode library. Install via composer: composer require picqer/php-barcode-generator
// For this implementation, we'll create a simple placeholder that generates SVG barcodes

function generateCode128SVG($data) {
    // Simple Code128 representation (simplified)
    $width = 300;
    $height = 80;
    $barWidth = 2;
    
    $svg = '<?xml version="1.0" encoding="UTF-8"?>';
    $svg .= '<svg width="' . $width . '" height="' . $height . '" xmlns="http://www.w3.org/2000/svg">';
    $svg .= '<rect width="100%" height="100%" fill="white"/>';
    
    // Draw bars (simplified pattern)
    $x = 10;
    $dataLen = strlen($data);
    for ($i = 0; $i < $dataLen * 5; $i++) {
        if ($i % 2 === 0) {
            $svg .= '<rect x="' . $x . '" y="10" width="' . $barWidth . '" height="50" fill="black"/>';
        }
        $x += $barWidth;
    }
    
    // Add text
    $svg .= '<text x="' . ($width/2) . '" y="' . ($height - 10) . '" text-anchor="middle" font-family="monospace" font-size="12">' . htmlspecialchars($data) . '</text>';
    $svg .= '</svg>';
    
    return $svg;
}

function generatePDF417SVG($data) {
    // Simple PDF417 representation (simplified as a 2D barcode placeholder)
    $width = 300;
    $height = 100;
    $cellSize = 3;
    
    $svg = '<?xml version="1.0" encoding="UTF-8"?>';
    $svg .= '<svg width="' . $width . '" height="' . $height . '" xmlns="http://www.w3.org/2000/svg">';
    $svg .= '<rect width="100%" height="100%" fill="white"/>';
    
    // Draw 2D pattern (simplified)
    $rows = 20;
    $cols = 80;
    $pattern = str_split(md5($data));
    
    for ($r = 0; $r < $rows; $r++) {
        for ($c = 0; $c < $cols; $c++) {
            $index = ($r * $cols + $c) % count($pattern);
            $hexVal = hexdec($pattern[$index]);
            if ($hexVal > 7) {
                $x = 10 + $c * $cellSize;
                $y = 10 + $r * $cellSize;
                $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $cellSize . '" height="' . $cellSize . '" fill="black"/>';
            }
        }
    }
    
    // Add text
    $svg .= '<text x="' . ($width/2) . '" y="' . ($height - 5) . '" text-anchor="middle" font-family="monospace" font-size="10">' . htmlspecialchars($data) . '</text>';
    $svg .= '</svg>';
    
    return $svg;
}

// Generate barcode
if ($type === 'pdf417') {
    $svg = generatePDF417SVG($data);
} else {
    $svg = generateCode128SVG($data);
}

// Output
if ($download) {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="barcode-' . preg_replace('/[^a-zA-Z0-9]/', '-', $data) . '.svg"');
} else {
    header('Content-Type: image/svg+xml');
}

echo $svg;
