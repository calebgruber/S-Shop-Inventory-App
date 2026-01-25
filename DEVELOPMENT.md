# Development & Deployment Guide

## For Future Developers

This guide will help you set up, develop, and deploy updates to the S-Shop Inventory application.

## Development Setup

### Prerequisites
- Node.js 16.x or higher
- npm (comes with Node.js)
- Git
- **For Windows builds**: Windows 10/11
- **For macOS builds**: macOS 10.13+ (High Sierra or later)

### Initial Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/calebgruber/S-Shop-Inventory-App.git
   cd S-Shop-Inventory-App
   ```

2. **Install dependencies**
   ```bash
   npm install
   ```
   
   Electron Forge will automatically rebuild native modules for your platform.

3. **Run in development mode**
   ```bash
   npm run dev
   # or
   npm start
   ```
   This opens DevTools automatically for debugging.

## Making Changes

### Code Structure

- **Main Process**: `src/main.js` - Electron main process, handles window and IPC
- **Database**: `src/database/db.js` - All SQLite operations
- **UI**: `src/renderer/` - HTML, CSS, and JavaScript for the interface
- **Utilities**: `src/utils/` - PDF generation, logging, backups, auto-updates

### Common Development Tasks

**Adding a new inventory field:**
1. Update database schema in `src/database/db.js`
2. Modify `createItem()` and `updateItem()` methods
3. Add form field in `src/renderer/scripts/inventory.js`
4. Update UI rendering if needed

**Adding a new page:**
1. Create module in `src/renderer/scripts/yourpage.js`
2. Implement `render()` and `init()` functions
3. Register with `window.navigation.registerPage('yourpage', {...})`
4. Add navigation link in `src/renderer/index.html`

**Modifying database schema:**
```javascript
// In src/database/db.js init() method
this.db.exec(`
  ALTER TABLE items 
  ADD COLUMN new_field TEXT DEFAULT NULL
`);
```

## Testing

### Manual Testing

1. **Start the app**: `npm run dev`
2. **Test workflows**:
   - Add inventory items
   - Create shows
   - Generate pull sheets
   - Process returns
   - Generate PDFs
   - Test barcode scanning (F1 or scanner)

3. **Check database**: Located at `%APPDATA%/s-shop-inventory-app/inventory.db`

### Testing Barcode Scanner

1. Configure Zebra DS scanner to keyboard wedge mode
2. Test in notepad first to verify scanner output
3. Press F1 in app and scan items
4. Verify barcode lookup works

## Building for Distribution

### Creating Installers with Electron Forge

**Package the app (no installer):**
```bash
npm run package
```

**Create installers for your platform:**
```bash
npm run make
```

This creates platform-specific installers:
- **Windows**: Squirrel installer in `out/make/squirrel.windows/x64/`
- **macOS**: ZIP archive in `out/make/`
- **Linux**: DEB and RPM in `out/make/`

All dependencies are automatically bundled.

### Build Output Locations

- **Windows**: `out/make/squirrel.windows/x64/s_shop_inventory-1.0.0 Setup.exe`
- **macOS**: `out/make/zip/darwin/x64/s-shop-inventory-darwin-x64-1.0.0.zip`
- **Linux DEB**: `out/make/deb/x64/s-shop-inventory_1.0.0_amd64.deb`
- **Linux RPM**: `out/make/rpm/x64/s-shop-inventory-1.0.0-1.x86_64.rpm`
- **Packaged app**: `out/s-shop-inventory-<platform>-<arch>/`

### Code Signing (Optional but Recommended)

**Windows:**
- Purchase a code signing certificate
- Set environment variables:
  ```
  WINDOWS_CERTIFICATE_FILE=path/to/cert.pfx
  WINDOWS_CERTIFICATE_PASSWORD=your_password
  ```

**macOS:**
- Enroll in Apple Developer Program
- Create signing certificate in Xcode
- Set environment variables:
  ```
  APPLE_ID=your@email.com
  APPLE_PASSWORD=app-specific-password
  ```

### Cross-Platform Building

Electron Forge handles native modules automatically, but for best results:

**Recommendation:**
- Build macOS apps on macOS
- Build Windows apps on Windows
- Or use CI/CD (GitHub Actions) to build both

## Deploying Updates

### Version Management

1. **Update version** in `package.json`:
   ```json
   {
     "version": "1.1.0"
   }
   ```

2. **Document changes** in commit message

### Creating a Release

1. **Commit your changes**:
   ```bash
   git add .
   git commit -m "Release v1.1.0: Add feature XYZ"
   ```

2. **Tag the release**:
   ```bash
   git tag v1.1.0
   ```

3. **Push to GitHub**:
   ```bash
   git push origin main
   git push origin v1.1.0
   ```

4. **Build the installers**:
   ```bash
   npm run make
   ```

5. **Publish to GitHub** (automatic with Forge):
   ```bash
   npm run publish
   ```
   
   Or manually:
   - Go to https://github.com/calebgruber/S-Shop-Inventory-App/releases
   - Click "Draft a new release"
   - Select tag: `v1.1.0`
   - Title: `Version 1.1.0`
   - Describe changes in release notes
   - Upload installers from `out/make/`:
     - Windows: `squirrel.windows/x64/s_shop_inventory-1.1.0 Setup.exe`
     - macOS: `zip/darwin/x64/s-shop-inventory-darwin-x64-1.1.0.zip`
     - Linux: `deb/x64/` and `rpm/x64/` files
   - Click "Publish release"

6. **Auto-Update Notification**:
   - Windows and macOS apps will check for updates on startup
   - Users will be prompted to download and install
   - Update installs on next app restart

## Auto-Update System

### How It Works

1. App checks GitHub releases API on startup
2. Compares current version with latest release
3. If newer version found, prompts user to download
4. Downloads installer in background
5. Installs on app restart

### Configuration

Auto-updater settings in Forge config (`package.json`):
```json
"publishers": [
  {
    "name": "@electron-forge/publisher-github",
    "config": {
      "repository": {
        "owner": "calebgruber",
        "name": "S-Shop-Inventory-App"
  "releaseType": "release"
}
```

### Testing Updates Locally

You cannot test auto-updates in development mode. To test:

1. Build and install the app normally
2. Increment version in package.json
3. Create a real GitHub release
4. Open installed app and check for updates

## Database Migrations

When updating the schema, always:

1. **Use ALTER TABLE, not DROP**:
   ```javascript
   // Good
   this.db.exec(`ALTER TABLE items ADD COLUMN color TEXT DEFAULT NULL`);
   
   // Bad (loses data)
   this.db.exec(`DROP TABLE items; CREATE TABLE items (...)`);
   ```

2. **Provide defaults** for new columns
3. **Test with existing data** before releasing

## Debugging

### Viewing Logs

- **Development**: Open DevTools (F12) - Console tab
- **Production**: 
  - Main process: `%APPDATA%/s-shop-inventory-app/logs/main.log`
  - Auto-updater: Check electron-log output

### Common Issues

**Database locked**:
- Ensure only one instance is running
- WAL mode is already enabled

**Barcode scanner not working**:
- Verify keyboard wedge mode
- Check BARCODE_TIMEOUT in `src/renderer/scripts/barcode.js`

**PDF generation fails**:
- Check `%APPDATA%/s-shop-inventory-app/pdfs/` exists
- Verify file permissions

**Updates not working**:
- Requires published GitHub release
- Won't work in development mode
- Check network connectivity

## Performance Tips

1. **Database indexes** - Already configured for common queries
2. **Lazy loading** - Pages load content only when displayed
3. **Debounce search** - Reduce database queries
4. **Paginate large lists** - If inventory grows very large

## Security Considerations

1. **No network exposure** - Database is local only
2. **Input validation** - Always use parameterized queries
3. **XSS prevention** - Use `textContent` not `innerHTML` with user data
4. **File paths** - Validate before file operations

## Code Standards

- **Comments**: Explain WHY, not WHAT
- **Function names**: Clear and descriptive
- **Error handling**: Try-catch all async operations
- **Logging**: Log important actions for audit trail

## Maintenance Schedule

Recommended:
- **Check dependencies**: Quarterly (`npm outdated`)
- **Security updates**: Monthly (`npm audit`)
- **Test backups**: Monthly
- **Review logs**: As needed

## Handoff Checklist

When passing to next developer:

- [ ] Review this document together
- [ ] Walk through code structure
- [ ] Demonstrate common workflows
- [ ] Show how to build and deploy
- [ ] Share GitHub access for releases
- [ ] Provide test barcode scanner

## Getting Help

1. **Documentation**: See README.md and ARCHITECTURE.md
2. **Code comments**: Inline documentation throughout
3. **Electron docs**: https://www.electronjs.org/docs
4. **SQLite docs**: https://www.sqlite.org/docs.html
5. **Tabler UI**: https://tabler.io/docs

## Future Enhancement Ideas

- [ ] Serialized item instance tracking (individual records per serial)
- [ ] Maintenance calendar/scheduler
- [ ] Visual charts and graphs
- [ ] Multi-user support with permissions
- [ ] Optional cloud backup
- [ ] Mobile companion app
- [ ] Equipment photos
- [ ] QR code support
- [ ] Email notifications for shortages
- [ ] Integration with venue calendars

---

**Remember**: This app is designed to last for years. Make changes carefully, document thoroughly, and test with real data before releasing.

Good luck! 🎭🔊
