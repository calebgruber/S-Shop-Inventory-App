<?php
/**
 * Set Theme API
 * Saves user theme preference
 */

require_once dirname(__DIR__) . '/config/config.php';

header('Content-Type: application/json');

requireAuth();

$data = json_decode(file_get_contents('php://input'), true);
$theme = $data['theme'] ?? 'light';

if (!in_array($theme, ['light', 'dark'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid theme']);
    exit;
}

$_SESSION['theme'] = $theme;

echo json_encode(['success' => true]);
