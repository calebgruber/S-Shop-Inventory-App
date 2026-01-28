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

// Only admins can approve
if (!canApprovePullSheet($userRole)) {
    header('Location: view.php?id=' . $pullsheetId . '&error=' . urlencode('Only administrators can approve pull sheets'));
    exit;
}

// Must be pending approval
if ($pullsheet['status'] !== 'pending_approval' && $pullsheet['status'] !== 'draft') {
    header('Location: view.php?id=' . $pullsheetId . '&error=' . urlencode('This pull sheet cannot be approved'));
    exit;
}

// Validate stock availability
$validation = validatePullSheetStock($pullsheetId);
if (!$validation['valid']) {
    $errorMsg = 'Insufficient stock for the following items: ';
    $errorItems = [];
    foreach ($validation['errors'] as $err) {
        $errorItems[] = $err['item_name'] . ' (need ' . $err['needed'] . ', have ' . $err['available'] . ')';
    }
    $errorMsg .= implode(', ', $errorItems);
    header('Location: view.php?id=' . $pullsheetId . '&error=' . urlencode($errorMsg));
    exit;
}

// Reserve items (update stock)
if (!reserveItemsForPullSheet($pullsheetId)) {
    header('Location: view.php?id=' . $pullsheetId . '&error=' . urlencode('Failed to reserve items'));
    exit;
}

// Update pull sheet status
$conn = getDbConnection();
$query = "UPDATE pullsheets 
          SET status = 'approved', 
              approved_by = ?,
              updated_at = NOW()
          WHERE id = ?";
$result = executeQuery($conn, $query, [$userId, $pullsheetId]);

if ($result) {
    // Create notification for creator
    $notifQuery = "INSERT INTO notifications (user_id, type, message, related_id, created_at) 
                   VALUES (?, 'pullsheet_approved', ?, ?, NOW())";
    $notifMessage = 'Your pull sheet for "' . $pullsheet['show_name'] . '" has been approved';
    executeQuery($conn, $notifQuery, [$pullsheet['created_by'], $notifMessage, $pullsheetId]);
    
    header('Location: view.php?id=' . $pullsheetId . '&success=' . urlencode('Pull sheet approved successfully'));
} else {
    // Release items if approval failed
    releaseItemsForPullSheet($pullsheetId);
    header('Location: view.php?id=' . $pullsheetId . '&error=' . urlencode('Failed to approve pull sheet'));
}
exit;
