<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/includes/helpers.php';
requireAuth();
requirePermission('admin'); // Only admins can update events

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$eventId = intval($input['event_id'] ?? 0);
$startDate = $input['start_date'] ?? '';
$endDate = $input['end_date'] ?? '';

if (!$eventId || !$startDate || !$endDate) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Convert to MySQL datetime format
$startDateTime = date('Y-m-d H:i:s', strtotime($startDate));
$endDateTime = date('Y-m-d H:i:s', strtotime($endDate));

$query = "UPDATE calendar_events SET start_date = ?, end_date = ? WHERE id = ?";
$result = executeQuery($query, [$startDateTime, $endDateTime, $eventId]);

if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
