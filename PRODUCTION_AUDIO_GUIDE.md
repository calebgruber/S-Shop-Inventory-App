# Production Audio Role and Approval Workflow Guide

## Overview

The Theatre Sound Shop Inventory system includes a comprehensive role-based permission system with four distinct user roles. This document focuses on the Production Audio role and the approval workflow for pullsheets and change orders.

## User Roles

### 1. Admin
- **Full Access**: Complete control over all system features
- **Permissions**:
  - Manage all inventory, shows, pullsheets, and change orders
  - Approve/reject pullsheets and change orders from other users
  - Assign designers and production audio staff to shows
  - Manage user accounts and permissions
  - Pick/return items without requiring signatures
  - Access all reports and settings
- **No Approval Required**: Admins' pullsheets and change orders are automatically approved

### 2. Designer
- **Read-Only Inventory**: Can view all inventory items and their details
- **Permissions**:
  - View inventory with images and stock levels
  - Create pullsheets for assigned shows
  - Create change orders for assigned shows
  - Submit student equipment requests (cannot approve)
  - Must be assigned to a show to work on its orders
- **Approval Required**: All pullsheets and change orders require admin approval before they can be picked

### 3. Student
- **Read-Only Inventory**: Can view all inventory items
- **Permissions**:
  - View inventory items
  - Create student equipment requests only
  - View status of their own requests
- **No Order Creation**: Cannot create pullsheets or change orders

### 4. Production Audio (New Role)
- **Read-Only Inventory**: Can view all inventory items
- **Permissions**:
  - View inventory with images and stock levels
  - Create pullsheets for assigned shows
  - Create change orders for assigned shows
  - Submit student equipment requests
  - Can be assigned to shows by admins
  - Can pick and return items
- **Approval Required**: All pullsheets and change orders require admin approval before they can be picked
- **Signature Required**: Final checkout requires admin signature (when implemented)

## Approval Workflow

### For Designers and Production Audio Users

1. **Create Pullsheet/Change Order**
   - User creates a pullsheet or change order as normal
   - Adds items to the order
   - Finalizes the order

2. **Automatic Submission for Approval**
   - Upon finalization, the order is marked as "Pending Approval"
   - Items are reserved/allocated but not yet checked out
   - Admin users receive a notification

3. **Admin Review**
   - Admin navigates to the pullsheet or change order view
   - Reviews the items and quantities
   - Can approve or reject the order

4. **Approval**
   - If approved: Order status changes to "Approved"
   - Order becomes available for picking in Pick Mode
   - User receives notification of approval

5. **Rejection**
   - If rejected: Order status changes to "Rejected"
   - Items are returned to available stock
   - User receives notification of rejection
   - User can edit and resubmit the order

### For Admin Users

1. **No Approval Required**
   - Admins' pullsheets and change orders are automatically approved
   - Can proceed directly to Pick Mode
   - No waiting for approval process

## Database Schema Changes

### New Fields in `pullsheets` Table
- `requires_approval` (BOOLEAN): Whether approval is needed
- `approved_by` (INT): Admin user ID who approved
- `approved_at` (TIMESTAMP): When approval was granted
- `approval_status` (ENUM): 'pending', 'approved', 'rejected'

### New Fields in `change_orders` Table
- `requires_approval` (BOOLEAN): Whether approval is needed
- `approved_by` (INT): Admin user ID who approved
- `approved_at` (TIMESTAMP): When approval was granted
- `approval_status` (ENUM): 'pending', 'approved', 'rejected'

### New Table: `signatures`
- `id` (INT): Primary key
- `user_id` (INT): User who signed
- `pullsheet_id` (INT): Related pullsheet (nullable)
- `change_order_id` (INT): Related change order (nullable)
- `signature_data` (TEXT): Base64-encoded signature image
- `first_name` (VARCHAR): Signer's first name
- `last_name` (VARCHAR): Signer's last name
- `created_at` (TIMESTAMP): Signature timestamp

### New Table: `show_assignments`
- `id` (INT): Primary key
- `show_id` (INT): Show being assigned to
- `user_id` (INT): User being assigned
- `role` (ENUM): 'designer' or 'production_audio'
- `created_at` (TIMESTAMP): Assignment date

## User Interface

### Pullsheet/Change Order View (Admin)
When viewing a pullsheet or change order that requires approval:

**Pending Approval**:
- Yellow/warning alert banner showing "Approval Status: Pending"
- Two buttons: "Approve" (green) and "Reject" (red)
- Clicking approve marks order as approved and notifies creator
- Clicking reject marks order as rejected and notifies creator

**Approved**:
- Green/success alert banner showing "Approval Status: Approved"
- Shows admin name and approval timestamp
- Order is now pickable in Pick Mode

**Rejected**:
- Red/danger alert banner showing "Approval Status: Rejected"
- Shows admin name and rejection timestamp
- Items returned to available stock

### Pick Mode
When scanning a barcode in Pick Mode:

**If Order Requires Approval and is Pending**:
- Error message: "This pullsheet/change order requires admin approval before it can be picked"
- Cannot proceed with picking
- User must wait for admin approval

**If Order is Approved or Admin Order**:
- Proceeds normally with picking process
- Items can be scanned and checked out

## Notifications

The system automatically sends notifications for:

1. **Pullsheet/Change Order Submitted for Approval**
   - Sent to: All admin users
   - Message: "[User]'s pullsheet/change order for [Show] needs approval"
   - Link: Direct link to view the order

2. **Pullsheet/Change Order Approved**
   - Sent to: Order creator
   - Message: "Your pullsheet/change order for [Show] has been approved"
   - Link: Direct link to view the approved order

3. **Pullsheet/Change Order Rejected**
   - Sent to: Order creator
   - Message: "Your pullsheet/change order for [Show] has been rejected"
   - Link: Direct link to edit the order

## Migration Instructions

To enable Production Audio role and approval workflow:

1. **Run Migration SQL**
   ```bash
   mysql -u [username] -p [database_name] < database/production_audio_migration.sql
   ```

2. **Verify Tables Updated**
   - Check `users` table has 'production_audio' role option
   - Check `pullsheets` table has approval fields
   - Check `change_orders` table has approval fields
   - Check `signatures` table exists
   - Check `show_assignments` table exists

3. **Create Production Audio Users**
   - Go to User Management
   - Add new user or edit existing user
   - Select "Production Audio" role
   - Save

4. **Assign Users to Shows**
   - Edit a show
   - Select Designer from user dropdown
   - Select Production Audio from user dropdown
   - Save show

## Best Practices

### For Admins
- Review approval requests promptly to avoid delays
- Check inventory levels before approving large orders
- Use rejection sparingly; communicate with users if issues arise
- Assign appropriate designers and production audio to each show

### For Designers and Production Audio
- Double-check item quantities before finalizing orders
- Ensure you're assigned to the show before creating orders
- Monitor notifications for approval status
- Plan ahead for approval delays (allow 24-48 hours)

### For Students
- Provide clear reason for equipment requests
- Be patient with approval process
- Contact admins if urgent needs arise

## Troubleshooting

### Order Stuck in Pending Approval
- **Cause**: Admin hasn't reviewed yet
- **Solution**: Contact admin or wait for review

### Cannot Pick Order
- **Cause**: Order not approved or rejected
- **Solution**: Check approval status, wait for admin action

### Lost Notification
- **Cause**: Notifications marked as read accidentally
- **Solution**: Check pullsheet/change order view directly

### Wrong User Role
- **Cause**: User assigned incorrect role
- **Solution**: Admin can change role in User Management

## Future Enhancements

### Signature Pad Implementation (Planned)
- HTML5 Canvas-based signature capture
- Touch and mouse input support
- Signature preview before submission
- Signature displayed on printed paperwork
- Digital signature verification

### Additional Features Under Consideration
- Approval delegation to specific users
- Multi-level approval workflow
- Approval comments and feedback
- Email notifications for approvals
- Approval history and audit trail

## API Functions

### Check if Approval Required
```php
requiresApproval($userId); // Returns boolean
```

### Approve Pullsheet
```php
approvePullsheet($pullsheetId, $adminId); // Returns boolean
```

### Reject Pullsheet
```php
rejectPullsheet($pullsheetId, $adminId); // Returns boolean
```

### Approve Change Order
```php
approveChangeOrder($changeOrderId, $adminId); // Returns boolean
```

### Reject Change Order
```php
rejectChangeOrder($changeOrderId, $adminId); // Returns boolean
```

### Save Signature
```php
saveSignature($userId, $signatureData, $firstName, $lastName, $pullsheetId, $changeOrderId); // Returns signature ID
```

### Check if Signature Required
```php
requiresSignature($userId); // Returns boolean
```

## Support

For questions or issues with the approval workflow or Production Audio role:
1. Check this documentation
2. Review the FEATURES.md file
3. Check the application logs in `/logs` directory
4. Contact your system administrator

## Version History

- **v2.0** (2024-01): Added Production Audio role and approval workflow
- **v1.0** (2023): Initial release with Admin, Designer, and Student roles
