<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

requireAuth();

// Only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    header('Location: index.php?error=' . urlencode('Invalid pull sheet ID'));
    exit;
}

$pullsheetId = (int)$_POST['id'];
$pullsheet = getPullSheetById($pullsheetId);

if (!$pullsheet) {
    header('Location: index.php?error=' . urlencode('Pull sheet not found'));
    exit;
}

$userId = $_SESSION['user']['id'];
$userRole = $_SESSION['user']['role'];

// Check permissions: only owner or admin can delete drafts
if ($pullsheet['status'] !== 'draft') {
    header('Location: view.php?id=' . $pullsheetId . '&error=' . urlencode('Only draft pull sheets can be deleted'));
    exit;
}

if ($userRole !== 'admin' && $pullsheet['created_by'] != $userId) {
    header('Location: view.php?id=' . $pullsheetId . '&error=' . urlencode('You do not have permission to delete this pull sheet'));
    exit;
}

// Release any reserved items (though drafts shouldn't have reserved items)
releaseItemsForPullSheet($pullsheetId);

// Delete the pull sheet (cascade will delete items)
$conn = getDbConnection();
$query = "DELETE FROM pullsheets WHERE id = ?";
$result = executeQuery($conn, $query, [$pullsheetId]);

if ($result) {
    // Delete PDF if exists
    if (!empty($pullsheet['pdf_path']) && file_exists($pullsheet['pdf_path'])) {
        @unlink($pullsheet['pdf_path']);
    }
    
    header('Location: index.php?success=' . urlencode('Pull sheet deleted successfully'));
} else {
    header('Location: view.php?id=' . $pullsheetId . '&error=' . urlencode('Failed to delete pull sheet'));
}
exit;
