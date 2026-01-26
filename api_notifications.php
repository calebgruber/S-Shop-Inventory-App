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

$action = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
}

if ($action === 'mark_read' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $notificationId = $_POST['notification_id'] ?? 0;
    markNotificationAsRead($notificationId, $currentUser['id']);
    echo json_encode(['success' => true]);
    
} elseif ($action === 'mark_all_read' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    markAllNotificationsAsRead($currentUser['id']);
    echo json_encode(['success' => true]);
    
} elseif ($action === 'get_unread_count' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $count = getUnreadNotificationCount($currentUser['id']);
    echo json_encode(['count' => $count]);
    
} elseif ($action === 'get_notifications' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
    $notifications = getUserNotifications($currentUser['id'], $unreadOnly);
    echo json_encode(['notifications' => $notifications]);
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action or method']);
}
