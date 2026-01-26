# Testing Guide for S-Shop Inventory App

## Pre-Testing Setup

### 1. Database Setup
```bash
mysql -u voxelnodes_sshop -p < database_schema.sql
```
Password: `).sBi.*B=}rp`

### 2. Verify Directory Permissions
```bash
chmod 755 uploads pdfs assets
ls -la uploads pdfs assets
```

### 3. Access the Application
Navigate to your configured URL (e.g., `http://localhost/`)

## Testing Workflow

### Phase 1: Initial Setup

#### Test 1.1: Settings Configuration
1. Navigate to **Settings**
2. Upload a logo (PNG/JPG)
3. Verify categories are listed (6 default categories)
4. Add a new category (e.g., "Test Category")
5. Verify theatre spaces are listed (4 default spaces)
6. Add a new theatre space (e.g., "Test Space")
7. Toggle dark/light mode and verify it persists

**Expected Result**: Settings save successfully, logo uploads, categories and spaces are manageable.

#### Test 1.2: Add Inventory Items
1. Navigate to **Inventory**
2. Click **Create New Item**
3. Add an item:
   - Name: "Test Microphone"
   - Select Category: "Microphones"
   - Tracking Type: "Quantity"
   - Total Quantity: 10
   - Available Quantity: 10
   - Description: "Test item for verification"
4. Submit and verify barcode is generated automatically
5. Add 3-5 more items with different categories and quantities

**Expected Result**: Items are created with unique barcodes, quantities are tracked correctly.

### Phase 2: Show Management

#### Test 2.1: Create a Show
1. Navigate to **Shows**
2. Click **Create New Show**
3. Enter:
   - Show Name: "Test Production"
   - Designer: "John Designer"
   - Shop Lead: "Jane Lead"
   - Theatre Space: Select "Main Stage"
4. Submit and verify show appears in list

**Expected Result**: Show is created and visible in the shows list.

#### Test 2.2: View Show Details
1. Click on the created show
2. Verify all details are displayed correctly
3. Verify "Create Pull Sheet" and "Create Change Order" buttons are present

**Expected Result**: Show details page displays correctly with action buttons.

### Phase 3: Pull Sheet Workflow

#### Test 3.1: Create Pull Sheet (Draft)
1. From show details, click **Create Pull Sheet**
2. In the search box, type an item name or scan barcode
3. Select an item from search results
4. In the modal, enter quantity needed (e.g., 5)
5. Verify stock availability is shown
6. Add item to pull sheet
7. Add 2-3 more items
8. Click **Save as Draft**

**Expected Result**: Pull sheet is saved as draft, items are listed.

#### Test 3.2: Edit and Finalize Pull Sheet
1. Navigate to **Pull Sheets**
2. Find and click **Edit** on the draft pull sheet
3. Add or remove items as needed
4. Click **Build Show** to finalize
5. Verify:
   - Status changes to "Finalized"
   - PDF417 barcode is generated
   - PDF is available for download
   - Items are marked as RESERVED in inventory

**Expected Result**: Pull sheet is finalized with unique barcode, PDF generated, inventory updated.

### Phase 4: Pick Mode

#### Test 4.1: Start Pick Mode
1. Click **Pick Mode** from dashboard or navigation
2. Enter picker name in the modal (e.g., "Test Picker")
3. Scan or enter the pull sheet barcode
4. Verify:
   - Fullscreen mode activates
   - Items are displayed as red cards
   - Each card shows 0 / X (quantity needed)

**Expected Result**: Pick mode starts successfully with all items in red.

#### Test 4.2: Scan Items
1. Scan or enter an item barcode from the pull sheet
2. Verify:
   - Success sound plays
   - Card turns yellow (partial)
   - Counter updates (e.g., 1 / 5)
3. Continue scanning the same item until quantity is met
4. Verify card turns green when complete
5. Scan one extra item
6. Verify:
   - Error sound plays
   - Card turns yellow
   - Warning message appears

**Expected Result**: Items change color based on status, audio feedback works, exact quantities are enforced.

#### Test 4.3: Complete Pick
1. Scan all items until all cards are green
2. Click **Done**
3. Confirm completion
4. Verify:
   - Items are marked as checked out
   - Items are linked to theatre space
   - Pull sheet status updates to "Picked"
   - Items appear in theatre in reports

**Expected Result**: Pick completes successfully, inventory locations updated.

### Phase 5: Change Orders

#### Test 5.1: Create Change Order
1. Navigate to a show with a completed pull sheet
2. Click **Create Change Order**
3. Add items to increase quantity (action: "add")
4. Add items to return (action: "remove")
5. Finalize the change order
6. Verify unique PDF417 barcode is generated

**Expected Result**: Change order created with barcode, ready for processing.

#### Test 5.2: Process Change Order (Pick)
1. Go to **Pick Mode**
2. Scan the change order barcode
3. Pick items marked for "add"
4. Verify inventory updates correctly

**Expected Result**: Change order items are picked successfully.

#### Test 5.3: Process Change Order (Return)
1. Go to **Return Mode**
2. Scan the change order barcode
3. Scan items marked for "remove"
4. Verify items return to shop inventory

**Expected Result**: Items are returned to shop, inventory updated.

### Phase 6: Return Mode

#### Test 6.1: Return Pull Sheet Items
1. Click **Return Mode**
2. Scan a completed pull sheet barcode
3. Scan items one by one
4. Verify:
   - Items are checked back into shop
   - Available quantity increases
   - Items removed from theatre location

**Expected Result**: All items return to shop successfully.

### Phase 7: Reports

#### Test 7.1: All Items Report
1. Navigate to **Reports**
2. Select "All Items"
3. Click **Generate Report**
4. Verify all items are listed with quantities
5. Click **Download PDF**
6. Verify PDF includes logo and item list

**Expected Result**: Report displays correctly, PDF downloads with logo.

#### Test 7.2: Items by Show
1. Select "By Show"
2. Choose a show from dropdown
3. Generate report
4. Verify only items for that show are listed

**Expected Result**: Filtered report shows only items for selected show.

#### Test 7.3: Items by Theatre Space
1. Select "By Theatre Space"
2. Choose a theatre space
3. Generate report
4. Verify only items in that space are listed

**Expected Result**: Filtered report shows only items in selected space.

#### Test 7.4: Barcode Labels (Avery 8195)
1. In Reports, select "Print Barcode Labels"
2. Select items to print
3. Click **Generate Labels**
4. Verify:
   - Labels use Avery 8195 format (2.75" x 1.75", 3x4 grid)
   - Code128 barcodes are displayed
   - Item names and barcodes are readable

**Expected Result**: Labels print in correct format for Avery 8195 sheets.

### Phase 8: Dashboard

#### Test 8.1: Pending Tasks
1. Navigate to **Dashboard**
2. Verify pending tasks section shows:
   - Incomplete pull sheets
   - Incomplete change orders
3. Click quick action buttons to start tasks

**Expected Result**: Dashboard shows accurate pending tasks and stats.

### Phase 9: Auto-Focus Testing

#### Test 9.1: Auto-Focus on Search Fields
1. Navigate to any page with search/scan fields:
   - Pull Sheet Create
   - Pick Mode
   - Return Mode
   - Reports
2. Verify cursor is automatically in the search/barcode field
3. Start typing immediately without clicking

**Expected Result**: All search/scan fields auto-focus on page load.

### Phase 10: Edge Cases

#### Test 10.1: Stock Validation
1. Try to add more items to pull sheet than available in stock
2. Verify warning message appears
3. Verify cannot exceed available quantity

**Expected Result**: Stock validation prevents over-allocation.

#### Test 10.2: Duplicate Barcodes
1. Try to create items with duplicate barcodes
2. Verify error handling

**Expected Result**: System prevents duplicate barcodes.

#### Test 10.3: Empty Pull Sheets
1. Try to finalize an empty pull sheet
2. Verify appropriate error message

**Expected Result**: Cannot finalize empty pull sheets.

## Bug Reporting Template

When you find issues, document them as:

```markdown
**Issue**: Brief description
**Steps to Reproduce**:
1. Step 1
2. Step 2
3. ...

**Expected**: What should happen
**Actual**: What actually happens
**Severity**: Low/Medium/High/Critical
**Screenshots**: If applicable
```

## Performance Testing

### Database Performance
- Test with 100+ items
- Test with 10+ shows
- Test with 50+ pull sheets
- Verify search remains fast

### Concurrent Users
- Test multiple users picking simultaneously
- Verify no race conditions on inventory updates

## Browser Compatibility

Test on:
- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- Mobile browsers

## Security Testing

- Verify SQL injection prevention (try: `' OR '1'='1`)
- Test XSS protection (try: `<script>alert('XSS')</script>`)
- Verify file upload restrictions
- Test authentication (if added)

## Success Criteria

✅ All core workflows complete without errors
✅ Barcodes generate and scan correctly
✅ Inventory tracking is accurate
✅ PDFs generate with correct layout
✅ Audio feedback works
✅ Dark/light mode toggles
✅ Auto-focus works on all pages
✅ Reports filter correctly
✅ No security vulnerabilities
✅ Mobile responsive

## Notes

- Test data can be reset by re-running `database_schema.sql`
- Always test on a non-production database first
- Document any customizations needed for your environment
