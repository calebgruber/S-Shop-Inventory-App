<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
requireAuth();

$pageName = 'Production Calendar';
$showId = isset($_GET['show_id']) ? intval($_GET['show_id']) : null;

// Get all shows for filtering
$shows = getAllShows(false); // Only active shows

$selectedShow = null;
if ($showId) {
    $selectedShow = getShowById($showId);
}

include dirname(__DIR__) . '/includes/header.php';
?>

<style>
/* Additional styles for FullCalendar */
.fc-event {
    cursor: pointer;
}
</style>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Production Management</div>
                <h2 class="page-title">Production Calendar</h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <?php if (hasPermission('admin')): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#eventModal" onclick="openEventModal()">
                    <i class="ti ti-plus icon"></i>
                    Add Event
                </button>
                <?php endif; ?>
                <a href="index.php" class="btn btn-secondary">
                    <i class="ti ti-award icon"></i>
                    Shows List
                </a>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <?php if ($selectedShow): ?>
        <div class="alert alert-info mb-3">
            <i class="ti ti-info-circle icon"></i>
            Showing events for: <strong><?php echo htmlspecialchars($selectedShow['name']); ?></strong>
            <a href="calendar.php" class="btn btn-sm btn-secondary ms-2">View All Shows</a>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div id="calendar"></div>
            </div>
        </div>
    </div>
</div>

<!-- Event Modal -->
<div class="modal modal-blur fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="eventForm" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventModalTitle">Add Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="event_id" id="event_id">
                    
                    <div class="mb-3">
                        <label class="form-label required">Show</label>
                        <select class="form-select" name="show_id" id="show_id" required>
                            <option value="">-- Select Show --</option>
                            <?php foreach ($shows as $show): ?>
                            <option value="<?php echo $show['id']; ?>" 
                                    <?php echo ($showId == $show['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($show['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">Event Title</label>
                        <input type="text" class="form-control" name="title" id="title" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="description" rows="3"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required">Start Date</label>
                                <input type="date" class="form-control" name="start_date" id="start_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Start Time</label>
                                <input type="time" class="form-control" name="start_time" id="start_time">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required">End Date</label>
                                <input type="date" class="form-control" name="end_date" id="end_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">End Time</label>
                                <input type="time" class="form-control" name="end_time" id="end_time">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-check">
                            <input class="form-check-input" type="checkbox" name="all_day" id="all_day">
                            <span class="form-check-label">All Day Event</span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger me-auto" id="deleteEventBtn" onclick="deleteEvent()" style="display:none;">
                        <i class="ti ti-trash icon"></i>
                        Delete
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-check icon"></i>
                        Save Event
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- FullCalendar CSS and JS -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.js"></script>

<script>
let calendar;
const showFilterId = <?php echo $showId ?? 'null'; ?>;

document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    
    calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        editable: <?php echo hasPermission('admin') ? 'true' : 'false'; ?>,
        eventStartEditable: <?php echo hasPermission('admin') ? 'true' : 'false'; ?>,
        eventDurationEditable: <?php echo hasPermission('admin') ? 'true' : 'false'; ?>,
        events: function(info, successCallback, failureCallback) {
            fetch('api/get-events.php' + (showFilterId ? '?show_id=' + showFilterId : ''))
                .then(response => response.json())
                .then(data => {
                    successCallback(data.events || []);
                })
                .catch(error => {
                    console.error('Error loading events:', error);
                    failureCallback(error);
                });
        },
        eventClick: function(info) {
            <?php if (hasPermission('admin')): ?>
            openEventModal(info.event);
            <?php else: ?>
            // Show event details in readonly mode
            alert(info.event.title + '\n' + (info.event.extendedProps.description || ''));
            <?php endif; ?>
        },
        eventDrop: function(info) {
            updateEventDates(info.event);
        },
        eventResize: function(info) {
            updateEventDates(info.event);
        }
    });
    
    calendar.render();

    // Handle form submission
    document.getElementById('eventForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveEvent();
    });

    // Handle all-day checkbox
    document.getElementById('all_day').addEventListener('change', function() {
        const timeInputs = ['start_time', 'end_time'];
        timeInputs.forEach(id => {
            document.getElementById(id).disabled = this.checked;
            if (this.checked) {
                document.getElementById(id).value = '';
            }
        });
    });
});

function openEventModal(event = null) {
    const modal = document.getElementById('eventModal');
    const form = document.getElementById('eventForm');
    const title = document.getElementById('eventModalTitle');
    const deleteBtn = document.getElementById('deleteEventBtn');
    
    // Reset form
    form.reset();
    
    if (event) {
        // Edit mode
        title.textContent = 'Edit Event';
        deleteBtn.style.display = 'block';
        
        document.getElementById('event_id').value = event.id;
        document.getElementById('show_id').value = event.extendedProps.show_id;
        document.getElementById('title').value = event.title;
        document.getElementById('description').value = event.extendedProps.description || '';
        
        const startDate = new Date(event.start);
        const endDate = new Date(event.end || event.start);
        
        document.getElementById('start_date').value = startDate.toISOString().split('T')[0];
        document.getElementById('end_date').value = endDate.toISOString().split('T')[0];
        
        if (!event.allDay) {
            document.getElementById('start_time').value = startDate.toTimeString().slice(0, 5);
            document.getElementById('end_time').value = endDate.toTimeString().slice(0, 5);
        } else {
            document.getElementById('all_day').checked = true;
            document.getElementById('start_time').disabled = true;
            document.getElementById('end_time').disabled = true;
        }
    } else {
        // Add mode
        title.textContent = 'Add Event';
        deleteBtn.style.display = 'none';
        document.getElementById('event_id').value = '';
    }
}

function saveEvent() {
    const form = document.getElementById('eventForm');
    const formData = new FormData(form);
    
    fetch('api/save-event.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('eventModal')).hide();
            // Reload calendar
            calendar.refetchEvents();
            // Show success message (you can add a toast notification here)
        } else {
            alert('Error: ' + (data.message || 'Failed to save event'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while saving the event');
    });
}

function deleteEvent() {
    if (!confirm('Are you sure you want to delete this event?')) {
        return;
    }
    
    const eventId = document.getElementById('event_id').value;
    
    fetch('api/delete-event.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ event_id: eventId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('eventModal')).hide();
            // Reload calendar
            calendar.refetchEvents();
        } else {
            alert('Error: ' + (data.message || 'Failed to delete event'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the event');
    });
}

function updateEventDates(event) {
    const startDate = event.start.toISOString();
    const endDate = (event.end || event.start).toISOString();
    
    fetch('api/update-event-dates.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            event_id: event.id,
            start_date: startDate,
            end_date: endDate
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert('Error: Failed to update event');
            calendar.refetchEvents(); // Revert on error
        }
    })
    .catch(error => {
        console.error('Error:', error);
        calendar.refetchEvents(); // Revert on error
    });
}
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
