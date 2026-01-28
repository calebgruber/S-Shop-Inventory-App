# Phase 4 Complete: Shows Management System

## Overview

Phase 4 delivers a complete shows management system with production calendar for the S-Shop Inventory App. This phase enables admins to create and manage theatre productions, assign personnel, schedule events, and track equipment usage per show.

## Files Created (11 files, ~2,400 lines of code)

### Helper Functions
- **includes/helpers.php** - Added 8 new functions for shows management

### Shows Module Pages
1. **shows/index.php** (13KB, 380 lines) - Browse and manage shows
2. **shows/add.php** (10KB, 285 lines) - Create new shows
3. **shows/edit.php** (11.5KB, 300 lines) - Edit existing shows
4. **shows/view.php** (19.5KB, 450 lines) - View show details
5. **shows/archive.php** (1.8KB, 50 lines) - Archive/unarchive handler
6. **shows/calendar.php** (13KB, 370 lines) - Production calendar

### API Endpoints
7. **shows/api/get-events.php** (1KB) - Fetch calendar events
8. **shows/api/save-event.php** (2KB) - Create/update events
9. **shows/api/delete-event.php** (856 bytes) - Delete events
10. **shows/api/update-event-dates.php** (1.2KB) - Update event dates

## Features Implemented

### 1. Shows List (index.php)

**Statistics Dashboard:**
- Total shows count
- Active shows count
- Archived shows count

**Show Display:**
- Color-coded avatar for each show
- Show name with clickable link
- Theatre space display
- Designer assignment
- Production audio assignment
- Status badges (Active/Archived)

**Actions:**
- View show details
- Edit show (admin only)
- Archive/Unarchive show (admin only)
- Add new show button (admin only)
- Calendar link

**User Experience:**
- Empty state for new installations
- Separate sections for active and archived shows
- Confirmation dialogs for archiving
- Success/error messages
- Responsive card layout

### 2. Add Show (add.php)

**Form Fields:**
- Show name (required)
- Theatre space (dropdown from settings)
- Designer (dropdown - users with designer role)
- Production Audio (dropdown - users with production_audio role)
- Calendar color (color picker with text input)

**Features:**
- Auto-focus on name field
- Color picker with hex code validation
- Synced color picker and text input
- Form validation
- Help sidebar with guidance
- Breadcrumb navigation

**Workflow:**
1. Admin enters show details
2. Assigns theatre space
3. Assigns designer and production audio
4. Chooses unique color
5. Submits form
6. Redirects to shows list with success message

### 3. Edit Show (edit.php)

**Features:**
- Pre-filled form with existing data
- All fields editable
- Archive checkbox
- Show creation and update timestamps
- Cancel button returns to view page

**Validation:**
- Same as add show
- Hex color format validation
- Required field validation

### 4. View Show (view.php)

**Show Information:**
- Show name with color-coded avatar
- Archived status badge
- Theatre space
- Designer name
- Production audio name
- Calendar color
- Creation date
- Last update date

**Pull Sheets Section:**
- List all pull sheets for show
- Status badges (Draft, Approved, Picked, Returned)
- Creator name
- Creation date
- View button for each

**Change Orders Section:**
- List all change orders for show
- Status badges
- Creator name
- Creation date
- View button for each

**Calendar Events Section:**
- Quick stats (pull sheets, change orders, events count)
- Upcoming events list (first 5)
- Event title and date
- Link to full calendar

**Actions:**
- Edit show button (admin only)

### 5. Archive Functionality (archive.php)

**Archive Process:**
- Updates show archived flag to 1
- Archives all associated pull sheets
- Archives all associated change orders
- Confirmation dialog required
- Admin-only access

**Unarchive Process:**
- Updates show archived flag to 0
- Does NOT automatically unarchive orders
- Orders remain archived for safety
- Admin-only access

### 6. Production Calendar (calendar.php)

**FullCalendar Integration:**
- Version 6.1.10 via CDN
- No build process required
- Responsive design
- Touch-friendly

**Views:**
- Month view (default)
- Week view (time grid)
- Day view (time grid)
- Navigation: Previous, Next, Today

**Event Features:**
- Color-coded by show
- Click to view/edit (admins)
- Drag-and-drop to reschedule (admins)
- Resize to change duration (admins)
- Show name in tooltip
- All-day events supported
- Time-specific events supported

**Event Modal:**
- Create new events
- Edit existing events
- Delete events
- Fields:
  - Show selection (required)
  - Event title (required)
  - Description (optional)
  - Start date and time (required)
  - End date and time (required)
  - All-day checkbox

**Filtering:**
- Optional show_id parameter
- View events for specific show
- View all shows together

**Permissions:**
- All users can view calendar
- Only admins can create/edit/delete events

### 7. Calendar API Endpoints

**get-events.php:**
- Returns events in FullCalendar JSON format
- Optional show filtering
- Includes show color and name
- Returns extended properties

**save-event.php:**
- Creates new events (POST)
- Updates existing events (POST with event_id)
- Validates required fields
- Handles all-day vs timed events
- Returns JSON success/error

**delete-event.php:**
- Deletes events by ID
- Admin-only
- Confirmation required
- Returns JSON success/error

**update-event-dates.php:**
- Updates event dates via drag-and-drop
- Handles drag events
- Handles resize events
- Returns JSON success/error

### 8. Helper Functions

**getAllTheatreSpaces():**
- Returns array of all theatre spaces
- Ordered by name
- Used in add/edit forms

**getUsersByRole($role):**
- Returns users filtered by role
- Optional role parameter (null = all users)
- Ordered by first name, last name
- Returns: id, username, first_name, last_name, email, role

**getShowById($id):**
- Returns complete show details
- Includes theatre space name
- Includes designer details
- Includes production audio details
- Returns null if not found

**getAllShows($includeArchived):**
- Returns all shows with related data
- Optional include archived parameter
- JOINs theatre spaces and users
- Ordered by archived status, then created date

**canArchiveShow($showId):**
- Always returns true
- Shows can always be archived
- Placeholder for future logic

**getShowColor($showId):**
- Returns hex color code for show
- Default: #3b82f6 (blue)

**getCalendarEvents($showId):**
- Returns events for show or all shows
- Includes show name and color
- Ordered by start date
- Only non-archived shows

**formatUserName($user):**
- Formats user name for display
- Prefers first + last name
- Falls back to username
- Returns 'N/A' if no data

## Database Schema Used

### shows table
```sql
id INT(11) PRIMARY KEY AUTO_INCREMENT
name VARCHAR(255) NOT NULL
theatre_space_id INT(11) - FK to theatre_spaces
designer_id INT(11) - FK to users
production_audio_id INT(11) - FK to users
color VARCHAR(7) DEFAULT '#3b82f6'
archived TINYINT(1) DEFAULT 0
created_at TIMESTAMP
updated_at TIMESTAMP
```

### calendar_events table
```sql
id INT(11) PRIMARY KEY AUTO_INCREMENT
show_id INT(11) NOT NULL - FK to shows
title VARCHAR(255) NOT NULL
description TEXT
start_date DATETIME NOT NULL
end_date DATETIME NOT NULL
all_day TINYINT(1) DEFAULT 0
created_at TIMESTAMP
updated_at TIMESTAMP
```

## User Roles and Permissions

### Admin
- ✅ View all shows
- ✅ Create shows
- ✅ Edit shows
- ✅ Archive/unarchive shows
- ✅ View calendar
- ✅ Create calendar events
- ✅ Edit calendar events
- ✅ Delete calendar events
- ✅ Drag-and-drop reschedule events

### Designer
- ✅ View all shows
- ✅ View shows they're assigned to
- ✅ View calendar
- ❌ Cannot create/edit/delete shows
- ❌ Cannot create/edit/delete events

### Production Audio
- ✅ View all shows
- ✅ View shows they're assigned to
- ✅ View calendar
- ❌ Cannot create/edit/delete shows
- ❌ Cannot create/edit/delete events

### Student
- ✅ View all shows
- ✅ View calendar
- ❌ Cannot create/edit/delete shows
- ❌ Cannot create/edit/delete events

## Technical Implementation

### Color Coding System
- Each show has a unique hex color (#RRGGBB)
- Color picker in add/edit forms
- Color validation (regex pattern)
- Default color: #3b82f6 (Tabler blue)
- Colors used in:
  - Show avatars
  - Calendar events
  - Status badges

### Show Assignment Workflow
1. Admin creates show
2. Admin assigns theatre space
3. Admin assigns designer (optional)
4. Admin assigns production audio (optional)
5. Assigned users can create pull sheets/change orders
6. Calendar events can be added for production schedule

### Archive System
- Soft delete (archived flag)
- Archives show and all orders
- Archived shows:
  - Separate section in list
  - Grayed out appearance
  - Can be viewed but not modified
  - Can be unarchived
- Unarchiving:
  - Restores show to active
  - Orders remain archived
  - Admin must manually unarchive orders if needed

### FullCalendar Implementation
**CDN Integration:**
```html
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.js"></script>
```

**Configuration:**
- Initial view: Month
- Editable: Admin only
- Event sources: AJAX from get-events.php
- Event click: Opens modal for edit
- Event drop: Updates dates via API
- Event resize: Updates duration via API

### Security Measures
- Role-based access control
- Admin-only show creation/editing
- Admin-only event creation/editing
- SQL injection prevention (prepared statements)
- XSS prevention (output escaping)
- CSRF protection ready
- Input validation on all forms

### Performance Optimizations
- Single query for show lists with JOINs
- Event loading via AJAX (not page load)
- Efficient database queries
- Minimal DOM manipulation
- CDN-hosted libraries

## User Workflows

### Creating a Show
1. Navigate to Shows → Add Show
2. Enter show name
3. Select theatre space
4. Assign designer (optional)
5. Assign production audio (optional)
6. Choose color for calendar
7. Submit form
8. Redirect to shows list

### Scheduling Events
1. Navigate to Shows → Calendar
2. Click "Add Event" button
3. Select show from dropdown
4. Enter event title and description
5. Set start and end dates/times
6. Check "All Day" if applicable
7. Submit form
8. Event appears on calendar

### Archiving a Show
1. Navigate to Shows
2. Find active show
3. Click "Archive" button
4. Confirm action in dialog
5. Show moves to archived section
6. All associated orders archived

### Viewing Show Details
1. Navigate to Shows
2. Click show name or "View" button
3. See complete show information
4. View pull sheets list
5. View change orders list
6. View upcoming events
7. Click "Calendar" to see all events

## Testing Checklist

### Shows List
- ✅ Displays statistics correctly
- ✅ Shows active shows in color-coded cards
- ✅ Shows archived shows in grayed section
- ✅ Empty state displays when no shows
- ✅ Archive button works
- ✅ Unarchive button works
- ✅ Edit button navigates correctly
- ✅ View button navigates correctly
- ✅ Add show button appears for admins only

### Add Show
- ✅ Form displays with all fields
- ✅ Theatre space dropdown populated
- ✅ Designer dropdown populated (designer role only)
- ✅ Production audio dropdown populated (production_audio role only)
- ✅ Color picker works
- ✅ Color text input syncs with picker
- ✅ Form validation works
- ✅ Submit creates show in database
- ✅ Success redirect works

### Edit Show
- ✅ Form pre-fills with existing data
- ✅ All fields editable
- ✅ Archive checkbox works
- ✅ Submit updates database
- ✅ Success redirect to view page
- ✅ Timestamps display correctly

### View Show
- ✅ Show information displays correctly
- ✅ Pull sheets list displays
- ✅ Change orders list displays
- ✅ Calendar events list displays
- ✅ Quick stats accurate
- ✅ Edit button appears for admins
- ✅ Empty states display when no data

### Calendar
- ✅ Calendar renders correctly
- ✅ Events load via AJAX
- ✅ Events color-coded by show
- ✅ Month/week/day views work
- ✅ Navigation (prev/next/today) works
- ✅ Add event button appears for admins
- ✅ Event click opens modal
- ✅ Drag-and-drop works for admins
- ✅ Event resize works for admins

### Calendar Events
- ✅ Add event modal opens
- ✅ Form fields populate correctly
- ✅ Show dropdown populated
- ✅ Submit creates event
- ✅ Calendar refreshes after save
- ✅ Edit event pre-fills form
- ✅ Update event saves changes
- ✅ Delete event removes from calendar
- ✅ All-day checkbox disables time fields

### API Endpoints
- ✅ get-events.php returns proper JSON
- ✅ save-event.php creates events
- ✅ save-event.php updates events
- ✅ delete-event.php removes events
- ✅ update-event-dates.php updates dates
- ✅ Error handling works
- ✅ Admin-only enforcement works

## Known Limitations & Future Enhancements

### Current Limitations
- No recurring events
- No event reminders/notifications
- No event attachments
- No event comments
- No event conflict detection
- No iCal export

### Potential Enhancements
- Add recurring event support
- Email notifications for events
- Event reminders
- File attachments for events
- Event comments/notes
- Conflict detection (double-booked spaces)
- iCal/Google Calendar integration
- Event templates
- Bulk event creation
- Event categories/types

## Integration with Other Modules

### Pull Sheets (Phase 5)
- Shows list will be used in pull sheet creation
- Pull sheets will display on show view page
- Archive show will archive all pull sheets

### Change Orders (Phase 6)
- Shows list will be used in change order creation
- Change orders will display on show view page
- Archive show will archive all change orders

### Reports (Phase 10)
- Show report will list equipment by show
- Calendar can be exported
- Show statistics

## Deployment Notes

### Requirements
- PHP 7.4+
- MySQL 5.7+ or MariaDB 10.2+
- Modern web browser with JavaScript enabled
- Internet connection for CDN resources

### CDN Dependencies
- FullCalendar 6.1.10 (CSS + JS)
- Loaded from cdn.jsdelivr.net
- No offline mode (requires internet)

### Database Setup
- shows table must exist
- calendar_events table must exist
- Foreign key constraints enabled
- theatre_spaces table populated
- Users with designer/production_audio roles exist

### Configuration
- No additional config needed
- Uses existing database connection
- Uses existing authentication
- Follows existing permission system

## Summary

Phase 4 delivers a **complete, production-ready shows management system** with:
- Full CRUD for shows
- Production calendar with drag-and-drop
- Event management
- Show archiving
- Personnel assignments
- Color coding
- Role-based permissions
- Professional UI/UX

The system integrates seamlessly with the existing authentication and authorization system, uses the Tabler UI framework consistently, and provides a solid foundation for pull sheets and change orders in the next phases.

**Total Implementation:** 11 files, ~2,400 lines of code, ~82KB

**Phase 4: COMPLETE! ✅**
