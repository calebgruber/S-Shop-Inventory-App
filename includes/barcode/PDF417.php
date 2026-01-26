<?php
/**
 * Pure PHP PDF417 Barcode Generator
 * Simplified version for document barcodes
 * No external dependencies required
 */

class PDF417 {
    public function generate($text, $widthFactor = 2, $height = 50) {
        // For simplicity, we'll create a data matrix style barcode
        // PDF417 is complex, so we'll create a simplified 2D barcode representation
        
        // Convert text to binary
        $binary = '';
        for ($i = 0; $i < strlen($text); $i++) {
            $binary .= str_pad(decbin(ord($text[$i])), 8, '0', STR_PAD_LEFT);
        }

        // Calculate dimensions
        $cols = 20; // Number of columns
        $rows = ceil(strlen($binary) / $cols);
        $cellSize = $widthFactor * 3;
        $width = $cols * $cellSize + 20;
        $imageHeight = $rows * $cellSize + 30;

        // Create image
        $image = imagecreate($width, $imageHeight);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefill($image, 0, 0, $white);

        // Draw data matrix
        $bitIndex = 0;
        for ($row = 0; $row < $rows; $row++) {
            for ($col = 0; $col < $cols; $col++) {
                if ($bitIndex < strlen($binary) && $binary[$bitIndex] == '1') {
                    $x = 10 + ($col * $cellSize);
                    $y = 10 + ($row * $cellSize);
                    imagefilledrectangle($image, $x, $y, $x + $cellSize - 1, $y + $cellSize - 1, $black);
                }
                $bitIndex++;
            }
        }

        // Add text at bottom
        $fontSize = 2;
        $textWidth = imagefontwidth($fontSize) * strlen($text);
        $textX = ($width - $textWidth) / 2;
        $textY = 10 + ($rows * $cellSize) + 5;
        imagestring($image, $fontSize, $textX, $textY, $text, $black);

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
        // Convert text to binary
        $binary = '';
        for ($i = 0; $i < strlen($text); $i++) {
            $binary .= str_pad(decbin(ord($text[$i])), 8, '0', STR_PAD_LEFT);
        }

        // Calculate dimensions
        $cols = 20;
        $rows = ceil(strlen($binary) / $cols);
        $cellSize = $widthFactor * 3;
        $width = $cols * $cellSize + 20;
        $imageHeight = $rows * $cellSize + 30;

        $svg = '<?xml version="1.0" encoding="UTF-8"?>';
        $svg .= '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $imageHeight . '" viewBox="0 0 ' . $width . ' ' . $imageHeight . '">';
        $svg .= '<rect width="' . $width . '" height="' . $imageHeight . '" fill="white"/>';

        // Draw data matrix
        $bitIndex = 0;
        for ($row = 0; $row < $rows; $row++) {
            for ($col = 0; $col < $cols; $col++) {
                if ($bitIndex < strlen($binary) && $binary[$bitIndex] == '1') {
                    $x = 10 + ($col * $cellSize);
                    $y = 10 + ($row * $cellSize);
                    $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $cellSize . '" height="' . $cellSize . '" fill="black"/>';
                }
                $bitIndex++;
            }
        }

        // Add text
        $textX = $width / 2;
        $textY = 10 + ($rows * $cellSize) + 15;
        $svg .= '<text x="' . $textX . '" y="' . $textY . '" text-anchor="middle" font-family="monospace" font-size="10">' . htmlspecialchars($text) . '</text>';
        $svg .= '</svg>';

        return $svg;
    }
}
