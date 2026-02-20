# Merge Change Orders into Shop Orders Table - Implementation Plan

## Objective
Display change orders within the shop orders table, clearly marked as subordinate to their parent shop order.

## Current State
- **Shop Orders Page**: `/pullsheets/index.php` - displays all shop orders
- **Change Orders Page**: `/change-orders/index.php` - displays all change orders
- Both have similar table structures with filters and actions

## Target State
- **Single Unified Page**: `/pullsheets/index.php`
- Shop orders displayed as parent rows
- Change orders displayed as child rows immediately under their parent
- Clear visual hierarchy (indentation, icons, styling)
- Mobile responsive with nested cards

## Implementation Details

### 1. Data Query Modifications

**Location**: `pullsheets/index.php` around lines 90-110

**Current**: Only queries `pullsheets` table
**New**: Query both `pullsheets` and `change_orders`, group by parent

```php
// After getting $pullsheets array, add:

// Get all change orders linked to these pullsheets
$pullsheetIds = array_column($pullsheets, 'id');
if (!empty($pullsheetIds)) {
    $placeholders = implode(',', array_fill(0, count($pullsheetIds), '?'));
    $changeOrders = getDB()->fetchAll("
        SELECT co.*, 
               s.name as show_name,
               p.barcode as pullsheet_barcode
        FROM change_orders co
        LEFT JOIN pullsheets p ON co.pullsheet_id = p.id
        LEFT JOIN shows s ON p.show_id = s.id
        WHERE co.pullsheet_id IN ($placeholders)
        ORDER BY co.pullsheet_id, co.created_at DESC
    ", $pullsheetIds);
    
    // Group by pullsheet_id
    $changeOrdersByPullsheet = [];
    foreach ($changeOrders as $co) {
        $changeOrdersByPullsheet[$co['pullsheet_id']][] = $co;
    }
} else {
    $changeOrdersByPullsheet = [];
}
```

### 2. Desktop Table Display

**Location**: Around line 200-300 (desktop table section)

**Current Structure**:
```html
<table>
  <tr>Shop Order 1</tr>
  <tr>Shop Order 2</tr>
</table>
```

**New Structure**:
```html
<table>
  <tr class="shop-order-row">Shop Order 1</tr>
  <tr class="change-order-row">└─ Change Order 1-1</tr>
  <tr class="change-order-row">└─ Change Order 1-2</tr>
  <tr class="shop-order-row">Shop Order 2</tr>
</table>
```

**Implementation**:
```php
<?php foreach ($pullsheets as $pullsheet): ?>
    <!-- Shop Order Row -->
    <tr class="shop-order-row">
        <td><?= htmlspecialchars($pullsheet['barcode']) ?></td>
        <!-- ... other columns ... -->
    </tr>
    
    <!-- Change Orders for this Shop Order -->
    <?php if (isset($changeOrdersByPullsheet[$pullsheet['id']])): ?>
        <?php foreach ($changeOrdersByPullsheet[$pullsheet['id']] as $changeOrder): ?>
            <tr class="change-order-row">
                <td class="ps-5">
                    <i class="ti ti-corner-down-right me-2 text-muted"></i>
                    <?= htmlspecialchars($changeOrder['barcode']) ?>
                    <span class="badge badge-sm bg-info ms-2">Change Order</span>
                </td>
                <td><?= htmlspecialchars($changeOrder['show_name'] ?? 'N/A') ?></td>
                <td>
                    <?php
                    $statusColors = [
                        'draft' => 'secondary',
                        'pending' => 'warning',
                        'finalized' => 'success'
                    ];
                    $badgeColor = $statusColors[$changeOrder['status']] ?? 'secondary';
                    ?>
                    <span class="badge bg-<?= $badgeColor ?>">
                        <?= ucfirst($changeOrder['status']) ?>
                    </span>
                </td>
                <td><?= htmlspecialchars($changeOrder['created_by']) ?></td>
                <td><?= date('M d, Y', strtotime($changeOrder['created_at'])) ?></td>
                <td>
                    <?php if ($changeOrder['status'] === 'finalized'): ?>
                        <a href="/change-orders/view?id=<?= $changeOrder['id'] ?>" class="btn btn-sm btn-info">
                            <i class="ti ti-eye"></i> View
                        </a>
                    <?php else: ?>
                        <a href="/change-orders/edit?id=<?= $changeOrder['id'] ?>" class="btn btn-sm btn-primary">
                            <i class="ti ti-edit"></i> Edit
                        </a>
                    <?php endif; ?>
                    
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this change order?');">
                        <input type="hidden" name="delete_change_order_id" value="<?= $changeOrder['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="ti ti-trash"></i> Delete
                        </button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
<?php endforeach; ?>
```

### 3. Mobile Card Display

**Location**: Around line 400-500 (mobile cards section)

```php
<?php foreach ($pullsheets as $pullsheet): ?>
    <!-- Shop Order Card -->
    <div class="card mb-3">
        <div class="card-body">
            <!-- Shop order details -->
        </div>
    </div>
    
    <!-- Change Orders for this Shop Order -->
    <?php if (isset($changeOrdersByPullsheet[$pullsheet['id']])): ?>
        <?php foreach ($changeOrdersByPullsheet[$pullsheet['id']] as $changeOrder): ?>
            <div class="card mb-3 ms-4 border-start border-info border-3">
                <div class="card-body bg-light">
                    <div class="d-flex align-items-center mb-2">
                        <i class="ti ti-corner-down-right me-2 text-muted"></i>
                        <span class="badge bg-info">Change Order</span>
                    </div>
                    <!-- Change order details -->
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
<?php endforeach; ?>
```

### 4. CSS Styling

**Add to page or global CSS**:

```css
/* Shop Order rows - normal appearance */
.shop-order-row {
    background-color: #fff;
}

/* Change Order rows - indented and lighter */
.change-order-row {
    background-color: #f8f9fa;
}

.change-order-row:hover {
    background-color: #e9ecef;
}

/* Mobile - indented cards */
@media (max-width: 768px) {
    .ms-4 {
        margin-left: 2rem !important;
    }
}
```

### 5. Delete Handler for Change Orders

**Add to top of file** (around line 10):

```php
// Handle delete change order request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_change_order_id'])) {
    try {
        $db = getDB();
        $deleteId = (int)$_POST['delete_change_order_id'];
        
        // Get change order details
        $changeOrder = getChangeOrderById($deleteId);
        if (!$changeOrder) {
            throw new Exception('Change order not found');
        }
        
        // Check permission
        if (($isDesigner || $isProductionAudio) && !canAccessShow($currentUser['id'], $changeOrder['show_id'])) {
            throw new Exception('You do not have permission to delete this change order');
        }
        
        // Use existing delete logic from change-orders/index.php
        // ... (copy the delete logic) ...
        
        setAlert('Change order deleted successfully');
        redirect();
    } catch (Exception $e) {
        logException($e, 'Error deleting change order');
        setAlert('Error: ' . $e->getMessage(), 'danger');
    }
}
```

### 6. Optional: Remove Standalone Change Orders Page

**After implementation**, consider:
- Removing `/change-orders/index.php` file
- Updating navigation to not link to it
- All change order management done from shop orders page

## Benefits

1. **Single Source of Truth**: All orders in one place
2. **Clear Hierarchy**: Visual parent-child relationship
3. **Better Context**: See change orders in context of their shop order
4. **Simplified Navigation**: One page instead of two
5. **Easier Management**: Create, view, edit, delete all from one location

## Testing Checklist

- [ ] Shop orders display correctly
- [ ] Change orders display under correct parent
- [ ] Indentation and styling look good
- [ ] Status badges are correct colors
- [ ] Edit/View buttons work for change orders
- [ ] Delete works for both shop orders and change orders
- [ ] Filters work correctly
- [ ] Search works for both types
- [ ] Mobile view displays hierarchy correctly
- [ ] No orphaned change orders (without pullsheet_id)

## Estimated Time
3-4 hours for complete implementation and testing

## Next Steps
1. Implement data query changes
2. Update desktop table display
3. Update mobile card display
4. Add CSS styling
5. Test thoroughly
6. Consider removing standalone change orders page
