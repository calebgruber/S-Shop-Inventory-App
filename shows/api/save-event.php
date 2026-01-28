<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/includes/helpers.php';
requireAuth();
requirePermission('admin'); // Only admins can save events

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$eventId = !empty($_POST['event_id']) ? intval($_POST['event_id']) : null;
$showId = intval($_POST['show_id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$startDate = $_POST['start_date'] ?? '';
$startTime = $_POST['start_time'] ?? '';
$endDate = $_POST['end_date'] ?? '';
$endTime = $_POST['end_time'] ?? '';
$allDay = isset($_POST['all_day']) ? 1 : 0;

// Validate
if (!$showId || !$title || !$startDate || !$endDate) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Build datetime strings
if ($allDay || empty($startTime)) {
    $startDateTime = $startDate . ' 00:00:00';
    $endDateTime = $endDate . ' 23:59:59';
} else {
    $startDateTime = $startDate . ' ' . $startTime . ':00';
    $endDateTime = $endDate . ' ' . ($endTime ?: $startTime) . ':00';
}

if ($eventId) {
    // Update existing event
    $query = "UPDATE calendar_events 
              SET show_id = ?, title = ?, description = ?, start_date = ?, end_date = ?, all_day = ?
              WHERE id = ?";
    $result = executeQuery($query, [$showId, $title, $description, $startDateTime, $endDateTime, $allDay, $eventId]);
} else {
    // Insert new event
    $query = "INSERT INTO calendar_events (show_id, title, description, start_date, end_date, all_day) 
              VALUES (?, ?, ?, ?, ?, ?)";
    $result = executeQuery($query, [$showId, $title, $description, $startDateTime, $endDateTime, $allDay]);
}

if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
