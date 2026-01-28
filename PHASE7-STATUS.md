# Phase 7: Pick & Return Modes - Status Report

## Overview
Phase 7 implements fullscreen barcode scanning interfaces for picking and returning equipment. This is one of the most complex phases requiring extensive JavaScript, real-time UI updates, audio feedback, and signature capture.

## Current Status: 20% Complete

### ✅ Completed (20%)
1. **Helper Functions (15 functions)**
   - Order retrieval for picking/returning
   - Item listing with full details
   - Scan recording and validation
   - Completion checking
   - Signature saving
   - Session finalization
   - Stock restoration on return
   - Notification creation

2. **Directory Structure**
   - `/pick/` directory created
   - `/return/` directory created
   - `/uploads/signatures/` created for signature storage

### ⏳ Remaining Work (80%)

#### Pick Mode (35%)
**Pages Needed (6 files):**
1. `pick/index.php` (10KB) - Barcode scanning entry page
   - Scan-only interface (no search)
   - Validates order exists and is approved
   - Redirects to start.php with order
   
2. `pick/start.php` (25KB) - Main picking interface
   - Fullscreen layout
   - Order details header
   - Item cards grid with:
     - Item photo
     - Name and barcode
     - Quantity needed/picked
     - Color-coded status (red/green/yellow)
   - Barcode scan field (auto-focus)
   - Progress indicator
   - Draft save button
   - Finalize button

3. `pick/scan-item.php` (3KB) - AJAX endpoint
   - Accepts item barcode
   - Calls recordItemScan()
   - Returns JSON status
   - Updates quantity_picked

4. `pick/finalize.php` (8KB) - Finalization page
   - Checks completion status
   - Signature modal (for Production Audio)
   - Calls finalizePickSession()
   - Updates order status to 'picked'
   - Redirects with success message

5. `pick/api/check-status.php` (2KB) - AJAX status check
   - Returns current picking progress
   - Used for real-time updates

6. `pick/api/resolve-over.php` (3KB) - Handle over-picking
   - Interface to resolve yellow (over-picked) items
   - Options: accept extra, remove excess, etc.

#### Return Mode (30%)
**Pages Needed (5 files):**
1. `return/index.php` (10KB) - Barcode scanning entry
   - Similar to pick/index.php
   - Validates order is picked

2. `return/start.php` (25KB) - Main return interface
   - Similar to pick/start.php
   - Shows items currently out
   - Scans items being returned

3. `return/scan-item.php` (3KB) - AJAX endpoint
   - Records return scans
   - Same logic as pick scanning

4. `return/finalize.php` (8KB) - Finalization
   - Signature if needed
   - Calls finalizeReturnSession()
   - Restores stock quantities
   - Updates status to 'returned'

5. `return/api/check-status.php` (2KB) - Status check
   - Returns return progress

#### UI Components (20%)

**CSS Updates (assets/css/custom.css):**
```css
/* Fullscreen pick/return mode */
.fullscreen-mode {
    min-height: 100vh;
    background: var(--tblr-body-bg);
}

.fullscreen-header {
    padding: 1rem;
    background: var(--tblr-card-bg);
    border-bottom: 1px solid var(--tblr-border-color);
}

/* Item cards with color coding */
.pick-item-card {
    border-left: 4px solid transparent;
    transition: all 0.3s;
}

.pick-item-card.incomplete {
    border-left-color: var(--tblr-danger);
    background: rgba(255, 107, 107, 0.05);
}

.pick-item-card.complete {
    border-left-color: var(--tblr-success);
    background: rgba(76, 209, 55, 0.05);
}

.pick-item-card.over {
    border-left-color: var(--tblr-warning);
    background: rgba(255, 193, 7, 0.05);
}

/* Signature pad */
.signature-pad {
    border: 2px solid var(--tblr-border-color);
    border-radius: 8px;
    cursor: crosshair;
}

/* Scan field emphasis */
.scan-field {
    font-size: 1.5rem;
    padding: 1rem;
    text-align: center;
    font-family: monospace;
}
```

**JavaScript Updates (assets/js/app.js):**
- Barcode scanning detection (reuse from dashboard)
- Real-time card updates via AJAX
- Audio playback (success/error sounds)
- Signature pad library integration
- Progress calculation
- Auto-refresh status

#### Signature Pad (10%)

**Requirements:**
1. HTML5 Canvas element
2. Touch and mouse support
3. Clear button
4. Convert to base64 PNG
5. First/Last name fields
6. Validate signature not empty
7. Submit via AJAX

**Library Options:**
- Signature Pad.js (lightweight, no dependencies)
- Manual implementation with canvas API

#### Audio Files (5%)

**Needed:**
1. `assets/sounds/success.mp3` - Pleasant beep for correct scan
2. `assets/sounds/error.mp3` - Error tone for wrong scan

**Implementation:**
```javascript
function playSound(type) {
    const audio = new Audio(`/assets/sounds/${type}.mp3`);
    audio.play().catch(e => console.log('Audio play failed:', e));
}
```

## Technical Requirements

### Database Schema
**Already Complete ✅**
- `picked_by` field in pullsheets/change_orders
- `returned_by` field in pullsheets/change_orders
- `quantity_picked` field in items tables
- `serial_numbers_picked` field for serial tracking
- `signature_data` and `signature_name` fields

### Security Considerations
1. **Admin Users:**
   - No signature required
   - Full access to pick/return

2. **Production Audio Users:**
   - Signature required for finalization
   - Can pick and return
   - Admin must sign off

3. **Designer Users:**
   - View only (can't pick/return)

4. **Validation:**
   - Order must be approved before picking
   - Order must be picked before returning
   - Barcode-only entry (no search)
   - Item barcode validation

### User Experience Flow

**Pick Mode:**
1. User scans order barcode → redirects to picking interface
2. Fullscreen UI shows all items as red cards
3. User scans item barcodes
4. Each scan:
   - Plays success sound
   - Updates card color (red → green)
   - Increments quantity_picked
   - If over: card turns yellow
5. When all green: Finalize button enabled
6. If Production Audio: signature modal appears
7. Order marked as 'picked'
8. Creator gets notification

**Return Mode:**
1. User scans order barcode → return interface
2. Shows items currently on show
3. User scans items being returned
4. Same color coding logic
5. Finalize with signature if needed
6. Stock quantities restored
7. Order marked as 'returned'

## Estimated Effort

Based on complexity:
- **Pick Mode Pages:** 15-20 hours
- **Return Mode Pages:** 10-15 hours
- **CSS Styling:** 3-5 hours
- **JavaScript:** 8-12 hours
- **Signature Pad:** 3-5 hours
- **Testing:** 5-8 hours
- **Audio Files:** 1-2 hours

**Total:** 45-67 hours

## Implementation Priority

1. **High Priority:**
   - Pick mode (core functionality)
   - Basic scanning and status updates
   - Signature pad for compliance

2. **Medium Priority:**
   - Return mode
   - Audio feedback
   - Over-pick resolution

3. **Nice to Have:**
   - Real-time progress animations
   - Advanced error handling
   - Detailed pick/return reports

## Risks & Challenges

1. **Barcode Scanner Integration:**
   - Must work reliably with physical scanners
   - Different scanner models may behave differently
   - Solution: Use same detection as dashboard (rapid keyboard input)

2. **Signature Capture:**
   - Must work on touch and mouse
   - File size management
   - Solution: Use proven library, compress images

3. **Real-Time Updates:**
   - Multiple users picking simultaneously
   - Solution: AJAX polling or server-sent events

4. **Audio Playback:**
   - Browser autoplay policies
   - Solution: User gesture required for first play

## Testing Checklist

When implementation complete:
- [ ] Scan pull sheet barcode redirects correctly
- [ ] Item cards display with correct colors
- [ ] Barcode scanning updates quantities
- [ ] Success sound plays on correct scan
- [ ] Error sound plays on wrong scan
- [ ] Cards turn green when complete
- [ ] Over-picked items turn yellow
- [ ] Finalize button enables when done
- [ ] Signature pad works (touch & mouse)
- [ ] Production Audio requires signature
- [ ] Admin bypasses signature
- [ ] Order status updates to 'picked'
- [ ] Return mode mirrors pick mode
- [ ] Stock restored on return
- [ ] Notifications created
- [ ] Mobile responsive
- [ ] Works with physical scanners

## Next Steps

To complete Phase 7:
1. Create all pick mode files (6 files)
2. Create all return mode files (5 files)
3. Update CSS for fullscreen mode and card states
4. Extend JavaScript for scanning and signatures
5. Add audio files
6. Integrate signature pad library
7. Comprehensive testing with real barcode scanner
8. Documentation

## Notes

- This phase requires the most JavaScript of any phase
- Signature pad can be postponed if needed (bypass for testing)
- Audio files are optional (visual feedback sufficient)
- Consider building pick mode fully before return mode
- Reuse as much code as possible between pick and return
- Test with actual barcode scanners, not just keyboard

## Status Summary

**Phase 7 Foundation: COMPLETE ✅**
- Helper functions: 100%
- Directory structure: 100%

**Phase 7 Implementation: PENDING ⏳**
- Pick mode: 0%
- Return mode: 0%
- UI/UX: 0%
- Testing: 0%

**Overall Phase 7: ~20% Complete**

---

The foundation is solid. The remaining work is primarily UI development with JavaScript interactivity. This is a critical phase for the production workflow but represents significant development effort.
