<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
requireAuth();
requirePermission('admin'); // Only admins can archive/unarchive shows

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$showId = intval($_POST['show_id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!$showId || !in_array($action, ['archive', 'unarchive'])) {
    $_SESSION['error_message'] = 'Invalid request.';
    header('Location: index.php');
    exit;
}

// Get show details
$show = getShowById($showId);

if (!$show) {
    $_SESSION['error_message'] = 'Show not found.';
    header('Location: index.php');
    exit;
}

if ($action === 'archive') {
    // Archive the show
    $query = "UPDATE shows SET archived = 1 WHERE id = ?";
    $result = executeQuery($query, [$showId]);
    
    if ($result) {
        // Also archive all associated pull sheets and change orders
        executeQuery("UPDATE pullsheets SET archived = 1 WHERE show_id = ?", [$showId]);
        executeQuery("UPDATE change_orders SET archived = 1 WHERE show_id = ?", [$showId]);
        
        $_SESSION['success_message'] = 'Show "' . $show['name'] . '" has been archived.';
    } else {
        $_SESSION['error_message'] = 'Failed to archive show.';
    }
} else {
    // Unarchive the show
    $query = "UPDATE shows SET archived = 0 WHERE id = ?";
    $result = executeQuery($query, [$showId]);
    
    if ($result) {
        // Note: We don't automatically unarchive orders - those remain archived
        $_SESSION['success_message'] = 'Show "' . $show['name'] . '" has been unarchived.';
    } else {
        $_SESSION['error_message'] = 'Failed to unarchive show.';
    }
}

header('Location: index.php');
exit;
