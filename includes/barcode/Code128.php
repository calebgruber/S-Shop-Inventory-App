<?php
/**
 * Pure PHP Code128 Barcode Generator
 * No external dependencies required
 */

class Code128 {
    private $encoding = [
        ' ' => '11011001100', '!' => '11001101100', '"' => '11001100110', '#' => '10010011000',
        '$' => '10010001100', '%' => '10001001100', '&' => '10011001000', "'" => '10011000100',
        '(' => '10001100100', ')' => '11001001000', '*' => '11001000100', '+' => '11000100100',
        ',' => '10110011100', '-' => '10011011100', '.' => '10011001110', '/' => '10111001100',
        '0' => '10011101100', '1' => '10011100110', '2' => '11001110010', '3' => '11001011100',
        '4' => '11001001110', '5' => '11011100100', '6' => '11001110100', '7' => '11101101110',
        '8' => '11101001100', '9' => '11100101100', ':' => '11100100110', ';' => '11101100100',
        '<' => '11100110100', '=' => '11100110010', '>' => '11011011000', '?' => '11011000110',
        '@' => '11000110110', 'A' => '10100011000', 'B' => '10001011000', 'C' => '10001000110',
        'D' => '10110001000', 'E' => '10001101000', 'F' => '10001100010', 'G' => '11010001000',
        'H' => '11000101000', 'I' => '11000100010', 'J' => '10110111000', 'K' => '10110001110',
        'L' => '10001101110', 'M' => '10111011000', 'N' => '10111000110', 'O' => '10001110110',
        'P' => '11101110110', 'Q' => '11010001110', 'R' => '11000101110', 'S' => '11011101000',
        'T' => '11011100010', 'U' => '11011101110', 'V' => '11101011000', 'W' => '11101000110',
        'X' => '11100010110', 'Y' => '11101101000', 'Z' => '11101100010', '[' => '11100011010',
        '\\' => '11101111010', ']' => '11001000010', '^' => '11110001010', '_' => '10100110000',
        '`' => '10100001100', 'a' => '10010110000', 'b' => '10010000110', 'c' => '10000101100',
        'd' => '10000100110', 'e' => '10110010000', 'f' => '10110000100', 'g' => '10011010000',
        'h' => '10011000010', 'i' => '10000110100', 'j' => '10000110010', 'k' => '11000010010',
        'l' => '11001010000', 'm' => '11110111010', 'n' => '11000010100', 'o' => '10001111010',
        'p' => '10100111100', 'q' => '10010111100', 'r' => '10010011110', 's' => '10111100100',
        't' => '10011110100', 'u' => '10011110010', 'v' => '11110100100', 'w' => '11110010100',
        'x' => '11110010010', 'y' => '11011011110', 'z' => '11011110110', '{' => '11110110110',
        '|' => '10101111000', '}' => '10100011110', '~' => '10001011110'
    ];

    private $startCode = '11010000100';
    private $stopCode = '1100011101011';

    public function generate($text, $widthFactor = 2, $height = 50) {
        // Calculate checksum
        $checksum = ord($text[0]) - 32;
        for ($i = 1; $i < strlen($text); $i++) {
            $checksum += ($i * (ord($text[$i]) - 32));
        }
        $checksum = $checksum % 103;
        $checksumChar = chr($checksum + 32);

        // Build barcode pattern
        $pattern = $this->startCode;
        for ($i = 0; $i < strlen($text); $i++) {
            if (isset($this->encoding[$text[$i]])) {
                $pattern .= $this->encoding[$text[$i]];
            }
        }
        if (isset($this->encoding[$checksumChar])) {
            $pattern .= $this->encoding[$checksumChar];
        }
        $pattern .= $this->stopCode;

        // Calculate image dimensions
        $width = strlen($pattern) * $widthFactor;
        $totalHeight = $height + 20; // Add space for text

        // Create image
        $image = imagecreate($width, $totalHeight);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefill($image, 0, 0, $white);

        // Draw bars
        $xPos = 0;
        for ($i = 0; $i < strlen($pattern); $i++) {
            if ($pattern[$i] == '1') {
                imagefilledrectangle($image, $xPos, 0, $xPos + $widthFactor - 1, $height, $black);
            }
            $xPos += $widthFactor;
        }

        // Add text
        $fontSize = 3;
        $textWidth = imagefontwidth($fontSize) * strlen($text);
        $textX = ($width - $textWidth) / 2;
        imagestring($image, $fontSize, $textX, $height + 2, $text, $black);

        return $image;
    }

    public function generatePNG($text, $widthFactor = 2, $height = 50) {
        $image = $this->generate($text, $widthFactor, $height);
        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);
        return $imageData;
    }

    public function generateSVG($text, $widthFactor = 2, $height = 50) {
        // Calculate checksum
        $checksum = ord($text[0]) - 32;
        for ($i = 1; $i < strlen($text); $i++) {
            $checksum += ($i * (ord($text[$i]) - 32));
        }
        $checksum = $checksum % 103;
        $checksumChar = chr($checksum + 32);

        // Build barcode pattern
        $pattern = $this->startCode;
        for ($i = 0; $i < strlen($text); $i++) {
            if (isset($this->encoding[$text[$i]])) {
                $pattern .= $this->encoding[$text[$i]];
            }
        }
        if (isset($this->encoding[$checksumChar])) {
            $pattern .= $this->encoding[$checksumChar];
        }
        $pattern .= $this->stopCode;

        $width = strlen($pattern) * $widthFactor;
        $totalHeight = $height + 20;

        $svg = '<?xml version="1.0" encoding="UTF-8"?>';
        $svg .= '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $totalHeight . '" viewBox="0 0 ' . $width . ' ' . $totalHeight . '">';
        $svg .= '<rect width="' . $width . '" height="' . $totalHeight . '" fill="white"/>';

        $xPos = 0;
        for ($i = 0; $i < strlen($pattern); $i++) {
            if ($pattern[$i] == '1') {
                $svg .= '<rect x="' . $xPos . '" y="0" width="' . $widthFactor . '" height="' . $height . '" fill="black"/>';
            }
            $xPos += $widthFactor;
        }

        $textX = $width / 2;
        $svg .= '<text x="' . $textX . '" y="' . ($height + 15) . '" text-anchor="middle" font-family="monospace" font-size="12">' . htmlspecialchars($text) . '</text>';
        $svg .= '</svg>';

        return $svg;
    }
}
