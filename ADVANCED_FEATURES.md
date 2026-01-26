# Advanced Features Documentation

This document describes the three advanced features added to the S-Shop Inventory App.

## 1. Hotkey Manager

### Overview
The Hotkey Manager allows users to customize keyboard shortcuts for quick access to common actions throughout the application.

### Location
- Settings page: `user_settings.php`
- Access via user dropdown menu → "Settings"

### Database Schema
```sql
CREATE TABLE user_hotkeys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    hotkey VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_action (user_id, action),
    UNIQUE KEY unique_user_hotkey (user_id, hotkey)
);
```

### Available Actions
1. **Quick Lookup** - Focus search field or go to inventory
2. **Pick Mode** - Navigate to pick mode
3. **Return Mode** - Navigate to return mode
4. **Create Pullsheet** - Navigate to pullsheet creation
5. **Go to Inventory** - Navigate to inventory page
6. **Go to Reports** - Navigate to reports page
7. **Go to Shows** - Navigate to shows page

### Default Hotkeys
- Quick Lookup: `Ctrl+K`
- Pick Mode: `Ctrl+Shift+P`
- Return Mode: `Ctrl+Shift+R`
- Create Pullsheet: `Ctrl+Shift+N`
- Inventory: `Ctrl+I`
- Reports: `Ctrl+Shift+E`
- Shows: `Ctrl+Shift+S`

### Usage
1. Navigate to Settings from user dropdown
2. Customize hotkeys by clicking in the input field and pressing desired key combination
3. Save settings
4. Use hotkeys anywhere in the application (except when typing in text fields, with exception of Ctrl+K)

### Technical Implementation
- JavaScript hotkey detection in `includes/footer.php`
- User preferences stored per-user in database
- Hotkeys normalized to format: `Ctrl+Shift+K`
- Prevents conflicts with browser shortcuts

---

## 2. Production Calendar

### Overview
A visual calendar system for managing show-related events with color-coded shows and FullCalendar integration.

### Location
- Main page: `production_calendar.php`
- Access via navigation menu → "Calendar" (under Shows section)
- Requires `shows` permission

### Database Schema
```sql
CREATE TABLE show_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    show_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    description TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (show_id) REFERENCES shows(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

ALTER TABLE shows 
ADD COLUMN calendar_color VARCHAR(7) DEFAULT '#206bc4';
```

### Features
- **Calendar Views**: Month, Week, Day, and List views
- **Color Coding**: Each show has a unique color for easy identification
- **Event Management**: Create, edit, and delete events (Admin only)
- **Show Association**: All events are linked to specific shows
- **Color Management**: Admins can customize show colors via modal

### Event Types
Events can represent:
- Rehearsals
- Tech weeks
- Performances
- Load-in/Load-out
- Design meetings
- Production deadlines

### Admin Functions
1. **Add Event**: Click "Add Event" button
2. **Edit Event**: Click on existing event
3. **Delete Event**: Open event modal and click delete
4. **Manage Colors**: Click "Manage Colors" to set per-show colors

### Technical Implementation
- Uses FullCalendar v6.1.10
- AJAX-based event loading
- Color picker for show customization
- Responsive design with Tabler UI

---

## 3. Notifications System

### Overview
A comprehensive notification system that alerts users about important events and pending tasks in real-time.

### Location
- Notification bell icon in top navigation bar (next to user menu)
- Badge shows unread notification count
- API endpoint: `api_notifications.php`

### Database Schema
```sql
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('pending_pick', 'pending_return', 'student_request', 'repair_needed', 'general') NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255),
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created (created_at)
);
```

### Notification Types

#### 1. Pending Pick (`pending_pick`)
- **Triggered**: When a pullsheet is finalized
- **Recipients**: Admins and designers with pick_mode permission
- **Message**: "New pullsheet ready for picking: [Show Name]"
- **Link**: Direct link to pick mode with pullsheet pre-selected

#### 2. Pending Return (`pending_return`)
- **Triggered**: When a show is completed with outstanding items
- **Recipients**: Admins and designers
- **Message**: "Items need to be returned for: [Show Name]"
- **Link**: Return mode page

#### 3. Student Request (`student_request`)
- **Triggered**: When a student creates a new request
- **Recipients**: Admins and designers
- **Message**: "New student request: [Item Name] (Qty: X)"
- **Link**: Student requests page

#### 4. Repair Needed (`repair_needed`)
- **Triggered**: When an item is marked for repair
- **Recipients**: Admins and shop leads
- **Message**: "Item needs repair: [Item Name]"
- **Link**: Repairs page

### Features
- **Unread Badge**: Red badge shows count of unread notifications
- **Dropdown Menu**: Click bell icon to view recent notifications
- **Mark as Read**: Notifications marked read when clicked
- **Mark All Read**: Bulk action to clear all notifications
- **Time Display**: Shows relative time (e.g., "5 minutes ago")
- **Auto-refresh**: Badge updates on page navigation
- **Limit**: Shows last 50 notifications

### API Endpoints

#### Mark Single Notification as Read
```
POST api_notifications.php
action=mark_read&notification_id=123
```

#### Mark All as Read
```
POST api_notifications.php
action=mark_all_read
```

#### Get Unread Count
```
GET api_notifications.php?action=get_unread_count
```

#### Get Notifications
```
GET api_notifications.php?action=get_notifications&unread_only=true
```

### Creating Custom Notifications

#### For Specific User
```php
createNotification($userId, 'general', 'Your custom message', 'link.php');
```

#### For All Admins
```php
createNotificationForAdmins('general', 'System message', null);
```

#### For All Designers
```php
createNotificationForDesigners('general', 'Design team message', 'link.php');
```

### Technical Implementation
- JavaScript functions in `includes/footer.php`
- Helper functions in `includes/functions.php`
- Real-time badge updates
- Tabler UI dropdown styling
- Responsive design

---

## Installation

### 1. Run Database Migration
```bash
mysql -u [username] -p [database_name] < database/advanced_features_migration.sql
```

### 2. Verify Tables Created
Check that the following tables exist:
- `user_hotkeys`
- `show_events`
- `notifications`

### 3. Verify Column Added
Check that `shows` table has `calendar_color` column.

### 4. Test Features
1. Navigate to user settings and configure hotkeys
2. Visit production calendar and create a test event
3. Create a student request to generate a notification

---

## Browser Compatibility

All features tested and compatible with:
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

---

## Performance Considerations

### Notifications
- Notifications are lazy-loaded (only fetched on dropdown open)
- Limited to 50 most recent per user
- Indexed by user_id and is_read for fast queries
- Badge count cached in session

### Calendar
- Events loaded via AJAX on demand
- Color settings cached per page load
- Responsive to window resize

### Hotkeys
- Minimal overhead - single event listener
- No network requests
- Instant response time

---

## Security

### Permissions
- Hotkeys: All authenticated users
- Calendar: Users with `shows` permission
- Calendar editing: Admins only
- Notifications: User-specific, cannot view others' notifications

### Data Validation
- All user inputs sanitized
- SQL injection prevention via prepared statements
- XSS prevention via htmlspecialchars()
- CSRF protection via session validation

---

## Future Enhancements

### Potential Additions
1. **Push Notifications**: Browser push API for real-time alerts
2. **Email Notifications**: Optional email digest
3. **Calendar Export**: iCal/ICS export functionality
4. **Recurring Events**: Support for repeating calendar events
5. **Hotkey Conflicts**: Visual indicator for conflicting shortcuts
6. **Notification Preferences**: User-configurable notification types

---

## Troubleshooting

### Hotkeys Not Working
- Check browser console for JavaScript errors
- Verify user has hotkeys configured in database
- Ensure not typing in input field (except Ctrl+K)

### Notifications Not Appearing
- Verify `notifications` table exists
- Check user permissions
- Ensure notification generation code is in place
- Check browser console for API errors

### Calendar Not Loading
- Verify FullCalendar CDN is accessible
- Check for JavaScript errors in console
- Ensure `show_events` table exists
- Verify user has `shows` permission

---

## Support

For issues or questions:
1. Check browser console for errors
2. Verify database tables are created
3. Test in different browser
4. Review server error logs

---

**Version**: 1.0  
**Last Updated**: 2024  
**Author**: Caleb Gruber
