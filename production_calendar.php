<?php
require_once 'includes/functions.php';

// Only admins can view production calendar
requireRole('admin');

$db = getDB();
$currentUser = getCurrentUser();

// Handle event operations BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    try {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'create_event') {
            $show_id = $_POST['show_id'] ?? 0;
            $title = trim($_POST['title'] ?? '');
            $start_date = $_POST['start_date'] ?? '';
            $end_date = $_POST['end_date'] ?? '';
            $description = trim($_POST['description'] ?? '');
            
            $errors = [];
            if (empty($title)) $errors[] = 'title';
            if (empty($start_date)) $errors[] = 'start_date';
            if (empty($end_date)) $errors[] = 'end_date';
            if (empty($show_id)) $errors[] = 'show_id';
            
            if (!empty($errors)) {
                logMessage("Calendar event creation failed - missing fields: " . implode(', ', $errors), 'WARNING');
                echo json_encode([
                    'success' => false, 
                    'message' => 'Missing required fields: ' . implode(', ', $errors)
                ]);
                exit;
            }
            
            $result = $db->query(
                "INSERT INTO show_events (show_id, title, start_date, end_date, description, created_by) 
                 VALUES (?, ?, ?, ?, ?, ?)",
                [$show_id, $title, $start_date, $end_date, $description, $currentUser['id']]
            );
            
            $eventId = $db->lastInsertId();
            logMessage("Calendar event created: ID=$eventId, Show=$show_id, Title=$title", 'INFO');
            
            echo json_encode(['success' => true, 'event_id' => $eventId]);
            exit;
        } elseif ($action === 'update_event') {
            $event_id = $_POST['event_id'] ?? 0;
            $title = trim($_POST['title'] ?? '');
            $start_date = $_POST['start_date'] ?? '';
            $end_date = $_POST['end_date'] ?? '';
            $description = trim($_POST['description'] ?? '');
            
            $db->query(
                "UPDATE show_events SET title = ?, start_date = ?, end_date = ?, description = ? WHERE id = ?",
                [$title, $start_date, $end_date, $description, $event_id]
            );
            
            logMessage("Calendar event updated: ID=$event_id", 'INFO');
            
            echo json_encode(['success' => true]);
            exit;
        } elseif ($action === 'delete_event') {
            $event_id = $_POST['event_id'] ?? 0;
            
            $db->query("DELETE FROM show_events WHERE id = ?", [$event_id]);
            
            logMessage("Calendar event deleted: ID=$event_id", 'INFO');
            
            echo json_encode(['success' => true]);
            exit;
        } elseif ($action === 'update_show_color') {
            $show_id = $_POST['show_id'] ?? 0;
            $color = $_POST['color'] ?? '#206bc4';
            
            $db->query("UPDATE shows SET calendar_color = ? WHERE id = ?", [$color, $show_id]);
            
            logMessage("Show calendar color updated: ShowID=$show_id, Color=$color", 'INFO');
            
            echo json_encode(['success' => true]);
            exit;
        } elseif ($action === 'get_events') {
            // Fetch all events with show colors
            $events = $db->fetchAll(
                "SELECT e.*, s.name as show_name, COALESCE(s.calendar_color, '#206bc4') as calendar_color 
                 FROM show_events e 
                 JOIN shows s ON e.show_id = s.id 
                 ORDER BY e.start_date"
            );
            
            $calendarEvents = [];
            foreach ($events as $event) {
                $calendarEvents[] = [
                    'id' => $event['id'],
                    'title' => $event['title'],
                    'start' => $event['start_date'],
                    'end' => $event['end_date'],
                    'backgroundColor' => $event['calendar_color'],
                    'borderColor' => $event['calendar_color'],
                    'extendedProps' => [
                        'show_name' => $event['show_name'],
                        'show_id' => $event['show_id'],
                        'description' => $event['description']
                    ]
                ];
            }
            
            echo json_encode($calendarEvents);
            exit;
        }
    } catch (Exception $e) {
        logException($e, "Calendar operation error");
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred: ' . $e->getMessage()
        ]);
        exit;
    }
}

$pageTitle = 'Production Calendar';
require_once 'includes/header.php';

// Get all shows with colors
$shows = $db->fetchAll(
    "SELECT id, name, calendar_color, status FROM shows ORDER BY name"
);
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Production Calendar</h3>
                <?php if (isAdmin()): ?>
                <div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#eventModal" onclick="openEventModal()">
                        <i class="ti ti-plus me-2"></i>
                        Add Event
                    </button>
                    <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#colorModal">
                        <i class="ti ti-palette me-2"></i>
                        Manage Colors
                    </button>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div id="calendar"></div>
            </div>
        </div>
    </div>
</div>

<!-- Event Modal -->
<div class="modal fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eventModalTitle">Add Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="eventForm">
                    <input type="hidden" id="event_id" name="event_id">
                    <input type="hidden" id="event_action" name="action" value="create_event">
                    
                    <div class="mb-3">
                        <label class="form-label required">Show</label>
                        <select class="form-select" id="event_show_id" name="show_id" required>
                            <option value="">Select Show</option>
                            <?php foreach ($shows as $show): ?>
                            <option value="<?php echo $show['id']; ?>">
                                <?php echo htmlspecialchars($show['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label required">Event Title</label>
                        <input type="text" class="form-control" id="event_title" name="title" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required">Start Date & Time</label>
                            <input type="datetime-local" class="form-control" id="event_start_date" name="start_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label required">End Date & Time</label>
                            <input type="datetime-local" class="form-control" id="event_end_date" name="end_date" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" id="event_description" name="description" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="deleteEventBtn" style="display:none;" onclick="deleteEvent()">
                    <i class="ti ti-trash me-2"></i>Delete
                </button>
                <button type="button" class="btn btn-primary" onclick="saveEvent()">Save Event</button>
            </div>
        </div>
    </div>
</div>

<!-- Color Management Modal -->
<div class="modal fade" id="colorModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manage Show Colors</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th>Show</th>
                                <th>Color</th>
                                <th>Preview</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($shows as $show): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($show['name']); ?></td>
                                <td>
                                    <input 
                                        type="color" 
                                        class="form-control form-control-color" 
                                        value="<?php echo htmlspecialchars($show['calendar_color'] ?? '#206bc4'); ?>"
                                        data-show-id="<?php echo $show['id']; ?>"
                                        onchange="updateShowColor(this)"
                                    >
                                </td>
                                <td>
                                    <span class="badge" style="background-color: <?php echo htmlspecialchars($show['calendar_color'] ?? '#206bc4'); ?>">
                                        Preview
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<!-- Note: Consider hosting FullCalendar locally for production use or verify SRI hash matches the file -->
<script>
let calendar;
let currentEventId = null;

document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    
    calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
        },
        height: 'auto',
        events: function(info, successCallback, failureCallback) {
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=get_events'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP error ' + response.status);
                }
                return response.json();
            })
            .then(data => successCallback(data))
            .catch(error => {
                console.error('Error loading events:', error);
                failureCallback(error);
            });
        },
        eventClick: function(info) {
            <?php if (isAdmin()): ?>
            openEventModal(info.event);
            <?php else: ?>
            alert('Event: ' + info.event.title + '\nShow: ' + info.event.extendedProps.show_name + '\n\n' + (info.event.extendedProps.description || 'No description'));
            <?php endif; ?>
        },
        eventDidMount: function(info) {
            info.el.title = info.event.extendedProps.show_name + ': ' + info.event.title;
        }
    });
    
    calendar.render();
});

function openEventModal(event = null) {
    if (event) {
        // Edit mode
        currentEventId = event.id;
        document.getElementById('eventModalTitle').textContent = 'Edit Event';
        document.getElementById('event_id').value = event.id;
        document.getElementById('event_action').value = 'update_event';
        document.getElementById('event_show_id').value = event.extendedProps.show_id;
        document.getElementById('event_title').value = event.title;
        document.getElementById('event_start_date').value = formatDateForInput(event.start);
        document.getElementById('event_end_date').value = formatDateForInput(event.end || event.start);
        document.getElementById('event_description').value = event.extendedProps.description || '';
        document.getElementById('deleteEventBtn').style.display = 'inline-block';
    } else {
        // Create mode
        currentEventId = null;
        document.getElementById('eventModalTitle').textContent = 'Add Event';
        document.getElementById('event_action').value = 'create_event';
        document.getElementById('eventForm').reset();
        document.getElementById('deleteEventBtn').style.display = 'none';
    }
}

function formatDateForInput(date) {
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day}T${hours}:${minutes}`;
}

function saveEvent() {
    const form = document.getElementById('eventForm');
    const formData = new FormData(form);
    
    fetch('', {
        method: 'POST',
        body: new URLSearchParams(formData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP error ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            window.location.href = window.location.href; // Reload to show changes
            calendar.refetchEvents();
            const modalEl = document.getElementById('eventModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            }
            // Reload the page to show updated events
            setTimeout(() => {
                window.location.href = window.location.href;
            }, 500);
        } else {
            alert(data.message || 'Error saving event');
        }
    })
    .catch(error => {
        console.error('Error saving event:', error);
        alert('Error saving event');
    });
}

function deleteEvent() {
    if (!confirm('Delete this event?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_event');
    formData.append('event_id', currentEventId);
    
    fetch('', {
        method: 'POST',
        body: new URLSearchParams(formData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP error ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            window.location.href = window.location.href; // Reload to show changes
            calendar.refetchEvents();
            bootstrap.Modal.getInstance(document.getElementById('eventModal')).hide();
            showAlert('Event deleted successfully', 'success');
        } else {
            showAlert('Error deleting event', 'danger');
        }
    })
    .catch(error => {
        console.error('Error deleting event:', error);
        showAlert('Error deleting event', 'danger');
    });
}

function updateShowColor(input) {
    const showId = input.dataset.showId;
    const color = input.value;
    
    const formData = new FormData();
    formData.append('action', 'update_show_color');
    formData.append('show_id', showId);
    formData.append('color', color);
    
    fetch('', {
        method: 'POST',
        body: new URLSearchParams(formData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP error ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Update preview badge
            const badge = input.closest('tr').querySelector('.badge');
            badge.style.backgroundColor = color;
            calendar.refetchEvents();
            showAlert('Color updated successfully', 'success');
        }
    })
    .catch(error => {
        console.error('Error updating show color:', error);
        showAlert('Error updating color', 'danger');
    });
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible`;
    alertDiv.innerHTML = `
        <div>${message}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.querySelector('.page-body .container-xl').insertBefore(alertDiv, document.querySelector('.row'));
    setTimeout(() => alertDiv.remove(), 3000);
}
</script>

<?php require_once 'includes/footer.php'; ?>
