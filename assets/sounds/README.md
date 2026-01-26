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
- Length: 0.5-1 second recommended
- Sample rate: 44.1kHz or 48kHz

## Uploading to cPanel

1. Log into cPanel
2. Open File Manager
3. Navigate to `assets/sounds/`
4. Upload `success.mp3` and `error.mp3`
5. Set permissions to 644 (readable)

## Testing

After uploading, test the sounds by:
1. Going to Pick Mode
2. Scanning an item (should play success.mp3)
3. Scanning extra items (should play error.mp3)

If sounds don't play, check:
- Files are named exactly: success.mp3 and error.mp3 (lowercase)
- Files are in assets/sounds/ directory
- Browser allows audio autoplay
- File permissions are correct (644)
