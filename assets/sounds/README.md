# Sound Files Directory

This directory should contain the following MP3 files for the application:

## Required Files

### success.mp3
- Plays when an item is successfully scanned in Pick Mode or Return Mode
- Should be a pleasant, short "ding" or "beep" sound (0.5-1 second)
- Example: a bell sound, success chime, or positive beep

### error.mp3
- Plays when there's an error (wrong item, extra item, or scan failure)
- Should be a distinct error sound (0.5-1 second)
- Example: a buzzer, error tone, or negative beep

### meow.mp3
- Plays at random intervals during Pink Mode easter egg
- Should be a cat meow sound (0.5-2 seconds)
- Plays randomly for up to 5 minutes

### whopper.mp3
- Plays when the cheese burger item (CHZ-BGR) is scanned
- Easter egg sound effect
- Can be any humorous or related sound effect

### bonk.mp3
- Plays when the red color block in the Cable Color Key is clicked
- Should be a short "bonk" or "thud" sound (0.5-1 second)

## Getting Sound Files

You can:
1. Record your own sounds
2. Use free sound libraries like:
   - freesound.org
   - zapsplat.com
   - soundbible.com
3. Convert from other formats using online tools

## File Format

- Format: MP3
- Quality: 128kbps is sufficient
- Length: 0.5-2 seconds recommended (meow can be longer)
- Sample rate: 44.1kHz or 48kHz

## Uploading to cPanel

1. Log into cPanel
2. Open File Manager
3. Navigate to `assets/sounds/`
4. Upload all MP3 files
5. Set permissions to 644 (readable)

## Testing

After uploading, test the sounds by:
1. Going to Pick Mode and scanning items (success.mp3 and error.mp3)
2. Triple-clicking the logo to enter Pink Mode (meow.mp3)
3. Scanning the CHZ-BGR barcode (whopper.mp3)
4. Clicking the red cable color block on dashboard (bonk.mp3)

If sounds don't play, check:
- Files are named exactly as listed above (lowercase, .mp3 extension)
- Files are in assets/sounds/ directory
- Browser allows audio autoplay
- File permissions are correct (644)
