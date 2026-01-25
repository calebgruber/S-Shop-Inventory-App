# Assets Directory

## Icons

This directory should contain application icons for different platforms:

- **icon.svg** - Source SVG icon (provided)
- **icon.png** - 512x512 PNG icon for general use
- **icon.ico** - Windows icon (256x256, contains multiple sizes)
- **icon.icns** - macOS icon (512x512@2x recommended)

## Creating Icons

### From SVG to PNG
```bash
# Using Inkscape or similar tool
inkscape icon.svg --export-png=icon.png --export-width=512 --export-height=512
```

### From PNG to ICO (Windows)
```bash
# Using ImageMagick
convert icon.png -define icon:auto-resize=256,128,64,48,32,16 icon.ico
```

### From PNG to ICNS (macOS)
```bash
# Create iconset directory
mkdir icon.iconset

# Generate different sizes
sips -z 16 16     icon.png --out icon.iconset/icon_16x16.png
sips -z 32 32     icon.png --out icon.iconset/icon_16x16@2x.png
sips -z 32 32     icon.png --out icon.iconset/icon_32x32.png
sips -z 64 64     icon.png --out icon.iconset/icon_32x32@2x.png
sips -z 128 128   icon.png --out icon.iconset/icon_128x128.png
sips -z 256 256   icon.png --out icon.iconset/icon_128x128@2x.png
sips -z 256 256   icon.png --out icon.iconset/icon_256x256.png
sips -z 512 512   icon.png --out icon.iconset/icon_256x256@2x.png
sips -z 512 512   icon.png --out icon.iconset/icon_512x512.png
sips -z 1024 1024 icon.png --out icon.iconset/icon_512x512@2x.png

# Create icns file
iconutil -c icns icon.iconset

# Clean up
rm -rf icon.iconset
```

## Notes

The provided icon.svg is a placeholder. For production use, create a professional icon that represents the S-Shop Inventory application.

For now, electron-builder will use the SVG and convert it automatically, but providing proper icon files will result in better quality across platforms.
