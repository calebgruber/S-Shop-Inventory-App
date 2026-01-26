<?php
require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $theme = $data['theme'] ?? 'light';
    
    if (in_array($theme, ['light', 'dark'])) {
        setSetting('theme_mode', $theme);
        echo json_encode(['success' => true, 'theme' => $theme]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid theme']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
