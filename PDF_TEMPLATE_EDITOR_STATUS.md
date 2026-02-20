# PDF Template Editor System - Implementation Status

## ✅ Completed

### 1. Database Foundation
- **Migration 013**: Created `pdf_templates` and `pdf_template_assignments` tables
- **Migration 014**: Fixed notification type column (bug fix)
- **Migrations 011-012**: Fixed and working (pullsheet integration)

### 2. Template Management
- **pdf-templates/index.php**: Template listing page with cards
  - View all templates
  - Preview, edit, delete actions
  - Assignment count display
  - Create new template button

### 3. Database Schema

**pdf_templates table:**
```sql
- id (Primary Key)
- name (VARCHAR 255)
- description (TEXT)
- template_data (LONGTEXT) - JSON storage
- preview_image (VARCHAR 255)
- is_active (BOOLEAN)
- created_by (FK to users)
- created_at, updated_at (TIMESTAMPS)
```

**pdf_template_assignments table:**
```sql
- id (Primary Key)
- template_id (FK to pdf_templates)
- document_type (ENUM) - 8 types supported
- is_default (BOOLEAN)
- created_at (TIMESTAMP)
```

**Supported Document Types:**
1. pullsheet_created
2. change_order_created
3. order_picked
4. order_returned
5. partial_return
6. out_of_stock
7. repair_request
8. student_request

---

## 🚧 In Progress / To Be Completed

### Phase 1: Visual Editor Interface
**File to create:** `pdf-templates/editor.php`

**Required Components:**
1. **Canvas Area** - Drag-and-drop work surface
2. **Component Palette** - Sidebar with draggable elements:
   - Text block
   - Image placeholder
   - Table
   - Barcode
   - Line separator
   - Rectangle/Box
   - Company logo
   - Dynamic variables

3. **Properties Panel** - Right sidebar for editing selected element:
   - Position (X, Y)
   - Size (Width, Height)
   - Font settings (for text)
   - Alignment
   - Border/background
   - Data binding (variable selection)

4. **Top Toolbar:**
   - Save template
   - Preview PDF
   - Template name input
   - Page size selector (Letter, Legal, A4)
   - Orientation (Portrait, Landscape)

### Phase 2: Template Engine
**File to create:** `includes/pdf_template_engine.php`

**Required Functions:**
```php
function renderPDFFromTemplate($templateId, $data) {
    // Load template from database
    // Parse JSON template_data
    // Iterate through elements
    // Generate TCPDF code
    // Output PDF
}

function getTemplateForDocumentType($documentType) {
    // Query pdf_template_assignments
    // Return default template for document type
}

function parseTemplateElement($element, $data) {
    // Parse individual element
    // Substitute variables with data
    // Return TCPDF instructions
}
```

**Element Types to Support:**
- `text`: Static or dynamic text
- `image`: Logo, photos
- `table`: Rows of data (items, etc.)
- `barcode`: Generate barcode from data
- `line`: Separator
- `box`: Rectangle/border

### Phase 3: Assignment Management
**File to create:** `pdf-templates/assignments.php`

**Features:**
- List all document types
- Show currently assigned template for each
- Select/change template assignment
- Mark template as default for type
- Preview template

### Phase 4: Integration
**Files to modify:**
- `pullsheets/view.php` - Use template system
- `change-orders/view.php` - Use template system
- `operations/pick.php` - Generate receipt from template
- `operations/return.php` - Generate receipt from template
- `operations/partial-return.php` - Generate receipt from template

**Integration Pattern:**
```php
// Instead of hardcoded PDF generation:
$templateId = getTemplateForDocumentType('pullsheet_created');
if ($templateId) {
    $pdf = renderPDFFromTemplate($templateId, $pullsheetData);
} else {
    // Fallback to existing PDF generation
}
```

---

## 🎨 Visual Editor Implementation Guide

### JSON Template Format
```json
{
  "page": {
    "size": "Letter",
    "orientation": "Portrait",
    "margins": {
      "top": 10,
      "right": 10,
      "bottom": 10,
      "left": 10
    }
  },
  "elements": [
    {
      "type": "text",
      "id": "header",
      "x": 10,
      "y": 10,
      "width": 190,
      "height": 10,
      "content": "{{company_name}}",
      "fontSize": 18,
      "fontWeight": "bold",
      "align": "center"
    },
    {
      "type": "table",
      "id": "items_table",
      "x": 10,
      "y": 40,
      "width": 190,
      "headers": ["Item", "Barcode", "Quantity"],
      "dataSource": "{{items}}",
      "columns": [
        {"field": "name", "width": 100},
        {"field": "barcode", "width": 50},
        {"field": "quantity", "width": 40}
      ]
    }
  ]
}
```

### Drag-and-Drop Implementation
```javascript
// Component palette
const palette = document.querySelector('.component-palette');
palette.addEventListener('dragstart', (e) => {
  e.dataTransfer.setData('componentType', e.target.dataset.type);
});

// Canvas
const canvas = document.querySelector('.canvas');
canvas.addEventListener('drop', (e) => {
  e.preventDefault();
  const componentType = e.dataTransfer.getData('componentType');
  const x = e.offsetX;
  const y = e.offsetY;
  addElement(componentType, x, y);
});
```

### Variable System
Available variables depend on document type:

**pullsheet_created:**
- `{{company_name}}`
- `{{pullsheet_id}}`
- `{{show_name}}`
- `{{created_date}}`
- `{{items}}` - Array of items
- `{{total_quantity}}`

**change_order_created:**
- `{{company_name}}`
- `{{change_order_id}}`
- `{{show_name}}`
- `{{created_date}}`
- `{{type}}` - "Add" or "Remove"
- `{{items}}` - Array of items

---

## 📋 Next Steps

### Immediate Priority
1. Create `pdf-templates/editor.php` with basic drag-and-drop
2. Implement JSON save/load functionality
3. Create simple preview (HTML view)

### Secondary Priority
4. Build `pdf_template_engine.php`
5. Integrate with one document type (pullsheet)
6. Test end-to-end workflow

### Future Enhancements
7. Create default templates for all 8 document types
8. Add more component types
9. Add template duplication feature
10. Add template import/export
11. Add template versioning

---

## 🔧 Development Notes

### Required Libraries
- **TCPDF**: Already installed in `/includes/tcpdf/`
- **Bootstrap 5**: Already available in includes/header.php
- **Tabler Icons**: Already available

### No External Dependencies Needed
- Using native HTML5 drag-and-drop
- Using native JavaScript (no jQuery required)
- Using existing Bootstrap styling

### Estimated Remaining Work
- **Phase 1** (Editor UI): 6-8 hours
- **Phase 2** (Template Engine): 4-6 hours
- **Phase 3** (Assignments): 2-3 hours
- **Phase 4** (Integration): 3-4 hours

**Total**: 15-21 hours of development time

---

## 🚀 Quick Start Guide (When Complete)

1. Navigate to **Settings → PDF Templates** (once added to menu)
2. Click **"Create New Template"**
3. Drag components from palette to canvas
4. Configure properties in right panel
5. Save template
6. Go to **Template Assignments**
7. Assign template to document type
8. Test by generating that document type

---

## 📝 Implementation Checklist

### Database ✅
- [x] Migration 013 - PDF templates tables
- [x] Migration 014 - Fix notifications
- [x] Fix migrations 011-012 syntax errors

### UI Pages
- [x] pdf-templates/index.php - Template list
- [ ] pdf-templates/editor.php - Visual editor
- [ ] pdf-templates/create.php - New template form
- [ ] pdf-templates/assignments.php - Assignment management

### Backend
- [ ] includes/pdf_template_engine.php - Rendering engine
- [ ] Add template functions to includes/functions.php
- [ ] Create default templates (seed data)

### Integration
- [ ] Update pullsheet PDF generation
- [ ] Update change order PDF generation
- [ ] Add PDF to pick receipts
- [ ] Add PDF to return receipts
- [ ] Add PDF to partial return receipts

### Navigation
- [ ] Add "PDF Templates" to Settings menu
- [ ] Or add to main navigation under "Admin"

---

## 💡 Simplified Alternative

If full visual editor is too complex, consider:

**Option A: Form-Based Editor**
- HTML form with fields for each element
- Position with number inputs (X, Y)
- Preview button to see result
- Much simpler to implement (3-4 hours)

**Option B: Template Library**
- Provide 8 pre-built templates
- Allow customization via forms
- No drag-and-drop needed
- Quickest to implement (2-3 hours)

---

## Current Status Summary

✅ **Database**: 100% complete
✅ **Migrations**: 100% complete (all working)
✅ **Template Listing**: 100% complete
🚧 **Visual Editor**: 0% complete (foundation only)
🚧 **Template Engine**: 0% complete
🚧 **Assignments**: 0% complete
🚧 **Integration**: 0% complete

**Overall Progress**: ~25% complete

Continue in next session with visual editor implementation.
