<?php
/**
 * Notifications API
 * Returns user notifications
 */

require_once dirname(__DIR__) . '/config/config.php';

header('Content-Type: application/json');

requireAuth();

$currentUser = getCurrentUser();
$userId = $currentUser['id'];

// Get unread notifications
$result = executeQuery(
    'SELECT * FROM notifications WHERE user_id = ? AND `read` = 0 ORDER BY created_at DESC LIMIT 10',
    [$userId],
    'i'
);

$notifications = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
}

echo json_encode([
    'success' => true,
    'notifications' => $notifications,
    'unread_count' => count($notifications)
]);
