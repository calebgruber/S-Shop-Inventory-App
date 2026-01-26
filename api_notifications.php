<?php
require_once 'includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

requireLogin();

$db = getDB();
$currentUser = getCurrentUser();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'mark_read') {
    $notificationId = $_POST['notification_id'] ?? 0;
    markNotificationAsRead($notificationId, $currentUser['id']);
    echo json_encode(['success' => true]);
    
} elseif ($action === 'mark_all_read') {
    markAllNotificationsAsRead($currentUser['id']);
    echo json_encode(['success' => true]);
    
} elseif ($action === 'get_unread_count') {
    $count = getUnreadNotificationCount($currentUser['id']);
    echo json_encode(['count' => $count]);
    
} elseif ($action === 'get_notifications') {
    $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
    $notifications = getUserNotifications($currentUser['id'], $unreadOnly);
    echo json_encode(['notifications' => $notifications]);
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
