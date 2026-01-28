<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/includes/helpers.php';
requireAuth();

header('Content-Type: application/json');

$showId = isset($_GET['show_id']) ? intval($_GET['show_id']) : null;

// Get calendar events
$events = getCalendarEvents($showId);

// Format for FullCalendar
$formattedEvents = [];
foreach ($events as $event) {
    $formattedEvents[] = [
        'id' => $event['id'],
        'title' => $event['title'],
        'start' => $event['start_date'],
        'end' => $event['end_date'],
        'allDay' => (bool)$event['all_day'],
        'backgroundColor' => $event['show_color'],
        'borderColor' => $event['show_color'],
        'extendedProps' => [
            'description' => $event['description'],
            'show_id' => $event['show_id'],
            'show_name' => $event['show_name']
        ]
    ];
}

echo json_encode([
    'success' => true,
    'events' => $formattedEvents
]);
