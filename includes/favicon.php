<?php
/**
 * Favicon helper — outputs a <link rel="icon"> tag when a favicon is configured.
 * Safe to include in any page's <head> section; silently skipped on DB errors.
 */
try {
    if (function_exists('getSetting')) {
        $faviconPath = getSetting('favicon_path');
        if ($faviconPath) {
            $faviconFile    = basename($faviconPath);
            $faviconDiskPath = (defined('UPLOAD_DIR') ? UPLOAD_DIR : __DIR__ . '/../uploads/') . $faviconFile;
            if (file_exists($faviconDiskPath)) {
                echo '<link rel="icon" href="/uploads/' . htmlspecialchars($faviconFile) . '">' . "\n";
            }
        }
    }
} catch (Exception $e) {
    // Silently ignore — favicon is non-critical
}
