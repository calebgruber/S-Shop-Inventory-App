# Easter Eggs Documentation

## Overview

The Theatre Sound Shop Inventory system includes several fun Easter eggs for entertainment and stress relief during busy production seasons. These hidden features are designed to be discoverable and add personality to the application.

## Easter Eggs List

### 1. Pink Mode 🐱💖

**Activation**: Triple-click the application logo in the navbar

**What Happens**:
- Entire UI transforms to pink with gradient backgrounds
- Floating hearts (💖) and cats (🐱) continuously appear and float upward across the screen
- Random meow sounds play at intervals (every 5 seconds to 5 minutes)
- All buttons, cards, and UI elements adopt pink gradient styling
- Logo gets a cat emoji overlay

**Deactivation**: Click the cat emoji (🐱) next to the logo

**Features**:
- **Visual Effects**:
  - Pink gradient backgrounds on all surfaces
  - Navbar: Linear gradient from hot pink to deep pink (#ff69b4 to #ff1493)
  - Cards: White with pink borders and pink box shadows
  - Links: Pink color scheme throughout
  - Fully readable and usable despite the pink overload
  
- **Animations**:
  - 15 floating emoji (mix of hearts and cats) spawn in waves
  - Emojis float from bottom to top of screen
  - Random horizontal positioning for variety
  - Continuous animation as long as pink mode is active
  
- **Sound Effects**:
  - Random meow sounds play during pink mode
  - Interval: 5 seconds to 5 minutes (completely random)
  - Requires `meow.mp3` in `assets/sounds/` directory
  - Volume: 50% to avoid being too loud
  
- **Persistence**:
  - Pink mode state saved to localStorage
  - Remains active across page reloads
  - Must be manually deactivated by clicking cat icon

**Technical Details**:
```javascript
// Pink mode CSS classes applied to body
body.pink-mode {
    background: linear-gradient(180deg, #ffe6f0 0%, #fff0f8 50%, #ffe6f0 100%);
}
```

**User Experience**:
- Designed to be fun and stress-relieving
- Still fully functional - not just cosmetic
- Easy to activate and deactivate
- Visual feedback makes it obvious when active

---

### 2. Random Guy Background 👨

**Activation**: Automatic - 5% chance on any page load

**What Happens**:
- The background of the entire page becomes a photo
- Image: `https://preview.tabler.io/static/avatars/000m.jpg`
- Background covers entire page with fixed attachment
- UI remains fully functional on top of the image

**Features**:
- **Probability**: 5% (1 in 20 page loads)
- **Background Properties**:
  - Size: Cover (fills entire screen)
  - Position: Center
  - Attachment: Fixed (doesn't scroll)
- **Console Log**: Announces "👨 Random guy appeared!" when activated

**Deactivation**: Reload the page (95% chance it won't appear again)

**Technical Details**:
```javascript
// Random activation
if (Math.random() < 0.05) {
    document.body.style.backgroundImage = 'url(https://preview.tabler.io/static/avatars/000m.jpg)';
    document.body.style.backgroundSize = 'cover';
    document.body.style.backgroundPosition = 'center';
    document.body.style.backgroundAttachment = 'fixed';
}
```

**User Experience**:
- Surprising and unexpected
- Doesn't interfere with functionality
- Rare enough to be special when it happens
- Adds humor to routine tasks

---

### 3. Cheeseburger Lookup 🍔

**Activation**: Scan or search for barcode `CHZ-BGR` anywhere in the application

**What Happens**:
- Whopper sound effect plays at maximum volume (300% gain using Web Audio API)
- Item lookup proceeds normally (if item exists)
- Special audio amplification for comedic effect

**Features**:
- **Trigger**: Barcode `CHZ-BGR` in any search field
- **Sound**: `whopper.mp3` from `assets/sounds/` directory
- **Volume**: 300% using Web Audio API gain node
- **Locations**: Works in:
  - Quick Lookup modal on dashboard
  - Item search on inventory page
  - Pullsheet/Change Order item search
  - Pick Mode scanning
  - Return Mode scanning

**Technical Details**:
```javascript
// Web Audio API for 300% volume
const audioContext = new (window.AudioContext || window.webkitAudioContext)();
const source = audioContext.createMediaElementSource(audio);
const gainNode = audioContext.createGain();
gainNode.gain.value = 3.0; // 300% volume
source.connect(gainNode);
gainNode.connect(audioContext.destination);
audio.play();
```

**User Experience**:
- Rewards users who scan the special barcode
- Can be used intentionally for fun
- Loud sound gets everyone's attention
- Inside joke for the theatre team

---

### 4. Cable Color Bonk 🔴

**Activation**: Click the red color block in the Cable Color Key on the dashboard

**What Happens**:
- "Bonk" sound effect plays
- Visual indication: Cursor changes to pointer on hover

**Features**:
- **Location**: Dashboard > Cable Color Key card > Red block
- **Sound**: `bonk.mp3` from `assets/sounds/` directory
- **Volume**: 100% (normal volume)
- **Visual**: Cursor becomes pointer when hovering over red block

**Cable Color Key Reference**:
- **Red**: 5' cables
- **Gray**: 10' cables
- **Purple**: 15' cables
- **Yellow**: 25' cables
- **Blue**: 50' cables
- **White**: 100' cables

**Technical Details**:
```javascript
document.getElementById('redCableBlock').addEventListener('click', function() {
    const audio = new Audio('assets/sounds/bonk.mp3');
    audio.volume = 1.0;
    audio.play();
});
```

**User Experience**:
- Tactile feedback for cable reference
- Fun way to memorize cable colors
- Doesn't interfere with main purpose
- Simple and quick interaction

---

## Sound File Requirements

All Easter eggs require specific sound files to be uploaded to the `assets/sounds/` directory.

### Required Files

1. **meow.mp3**
   - Used in: Pink Mode
   - Duration: 0.5-2 seconds
   - Format: MP3, 128kbps
   - Description: Cat meow sound
   - Timing: Plays randomly every 5 seconds to 5 minutes in pink mode

2. **whopper.mp3**
   - Used in: Cheeseburger Lookup
   - Duration: 1-3 seconds
   - Format: MP3, 128kbps
   - Description: Burger-related sound effect
   - Volume: Plays at 300% gain

3. **bonk.mp3**
   - Used in: Cable Color Bonk
   - Duration: 0.5-1 second
   - Format: MP3, 128kbps
   - Description: Bonk or thud sound
   - Volume: Plays at 100%

### Installation

1. Place sound files in: `/assets/sounds/`
2. Ensure file permissions: 644 (readable)
3. Verify file names match exactly (lowercase, .mp3 extension)
4. Test by activating each Easter egg

### Finding Sound Files

Free sound libraries:
- freesound.org
- zapsplat.com
- soundbible.com
- YouTube Audio Library

## Browser Compatibility

Easter eggs work best in:
- ✅ Chrome/Edge (Recommended)
- ✅ Firefox
- ✅ Safari
- ⚠️ Mobile browsers (may have autoplay restrictions)

### Known Limitations

- **Mobile Autoplay**: Some mobile browsers block autoplay
- **Sound Permissions**: User may need to interact with page before sounds play
- **Web Audio API**: Required for 300% volume in Cheeseburger Easter egg
- **LocalStorage**: Required for Pink Mode persistence

## Developer Notes

### Adding New Easter Eggs

To add a new Easter egg:

1. **Choose Trigger**: Click, keyboard shortcut, specific input, etc.
2. **Implement Logic**: Add JavaScript in `footer.php` or page-specific JS
3. **Add Sound (if applicable)**: Place MP3 in `assets/sounds/`
4. **Test Thoroughly**: Ensure doesn't break functionality
5. **Document Here**: Add to this file for future reference

### Best Practices

- ✅ Keep Easter eggs non-intrusive
- ✅ Ensure they don't break core functionality
- ✅ Make them easy to deactivate if needed
- ✅ Test on multiple browsers
- ✅ Consider accessibility (sound alternatives, visual-only modes)
- ❌ Don't make them too frequent or annoying
- ❌ Don't use large file sizes (affects performance)

### Code Locations

- **Pink Mode**: `includes/footer.php` (JavaScript section)
- **Random Guy**: `includes/footer.php` (JavaScript section)
- **Cheeseburger**: `index.php` (Quick Lookup script section)
- **Cable Bonk**: `index.php` (Dashboard script section)

## Troubleshooting

### Sounds Not Playing

**Problem**: Sound effects don't play when Easter egg is activated

**Solutions**:
1. Check sound files exist in `assets/sounds/`
2. Verify file names match exactly (case-sensitive)
3. Check browser console for errors
4. Try clicking on page first (enables autoplay)
5. Check browser autoplay settings
6. Verify file permissions (644)

### Pink Mode Won't Activate

**Problem**: Triple-clicking logo doesn't activate pink mode

**Solutions**:
1. Check console for JavaScript errors
2. Ensure logo element has correct class (`.navbar-brand`)
3. Clear localStorage and try again
4. Try clicking slowly (not too fast)
5. Check if theme toggle is interfering

### Pink Mode Stuck On

**Problem**: Can't exit pink mode

**Solutions**:
1. Click the cat emoji (🐱) next to logo
2. Clear localStorage: `localStorage.removeItem('pinkMode')`
3. Use browser console: `document.body.classList.remove('pink-mode')`
4. Refresh page after clearing localStorage

### Cheeseburger Not Working

**Problem**: Scanning CHZ-BGR doesn't play sound

**Solutions**:
1. Check `whopper.mp3` exists in `assets/sounds/`
2. Verify Web Audio API support in browser
3. Check console for audio errors
4. Try clicking page first to enable audio
5. Check browser autoplay policy

## Fun Facts

- **Pink Mode Origins**: Inspired by theatre crew's love of pink gaff tape
- **Random Guy**: A reference to Tabler UI's default avatars
- **Cheeseburger**: Inside joke about late-night tech rehearsals
- **Cable Colors**: Actual industry-standard cable color coding

## Future Easter Eggs (Ideas)

Potential future additions:
- Konami Code activation for special admin mode
- Sound effects for successful operations
- Seasonal themes (Halloween, Christmas)
- Achievement system for using certain features
- Hidden stats tracking (items scanned, orders completed)
- Confetti animation on show completion
- Dark mode with fireflies or stars
- "Matrix" mode with falling code characters

## Feedback

Easter eggs are meant to bring joy to daily work. If you have ideas for new Easter eggs or improvements to existing ones, please share them!

Remember: Easter eggs should enhance the experience, not detract from functionality. Keep them fun, safe, and reversible!
