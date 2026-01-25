# Getting Started with S-Shop Inventory

## You're Ready to Go! 🎉

The S-Shop Inventory application is **fully implemented and ready to use**. Here's what you can do now:

## Quick Start (5 minutes)

### ⚠️ Important for Windows Users

**Before you start**: If you're on Windows and don't have Visual Studio Build Tools installed, you'll encounter build errors. Follow these steps:

1. **Install Visual Studio Build Tools 2022** (one-time setup):
   - Download from: https://visualstudio.microsoft.com/downloads/
   - Look for "Build Tools for Visual Studio 2022"
   - During installation, select "Desktop development with C++"
   - This takes about 10 minutes

2. **Then proceed with installation below**

### Option 1: Use Pre-Built Installer (When Available)

> **Note**: Pre-built installers are not yet available since this is a new project. Skip to Option 2 for now.

Once releases are created:
1. Download the `.exe` for Windows or `.dmg` for macOS
2. Run it - done! No build tools needed.

### Option 2: Build from Source (Current Method)

**If you encounter errors during `npm install`**, you need Visual Studio Build Tools (see above).

### 1. Install Dependencies
```bash
npm install
```

If this fails with `gyp ERR! find VS`:
- You need to install Visual Studio Build Tools (see "Important for Windows Users" above)
- After installing VS Build Tools, restart your terminal and try again

### 2. Run the Application
```bash
npm run dev
```

The app will open and you can immediately start:
- ✅ Adding inventory items
- ✅ Creating shows
- ✅ Building pull sheets
- ✅ Scanning barcodes (press F1)
- ✅ Generating PDFs

## Installation Troubleshooting

**Getting the `gyp ERR! find VS` error?** This is NORMAL if you don't have Visual Studio Build Tools.

### The Error Message Explained

```
gyp ERR! find VS Could not find any Visual Studio installation to use
gyp ERR! find VS You need to install the latest version of Visual Studio
gyp ERR! find VS including the "Desktop development with C++" workload.
```

This means you need to install build tools so npm can compile the database library.

### Quick Fix for Windows (Required for Building from Source)

1. **Download & Install Visual Studio Build Tools 2022**:
   - Go to: https://visualstudio.microsoft.com/downloads/
   - Scroll to "All Downloads" → "Tools for Visual Studio"  
   - Download "Build Tools for Visual Studio 2022"
   - Run the installer
   - **Important**: Select "Desktop development with C++" checkbox
   - Click Install (~7GB download)

2. **After installation completes**:
   - Restart your command prompt/terminal
   - Navigate back to the project folder
   - Run `npm install` again
   - It should work now!

### Alternative: Wait for Pre-Built Releases

If you don't want to install Visual Studio Build Tools, wait for the first pre-built release to be created. Then you can just download and run the `.exe` installer with no build tools needed.

**To create a release yourself** (after installing VS Build Tools):
```bash
npm install
npm run build:win
# Installer will be in dist/S-Shop Inventory Setup.exe
```

### Still Having Issues?

## What You Can Do Right Now

### Add Your First Inventory Item
1. Click **Inventory** in the navigation
2. Click **Add Item**
3. Fill in the details:
   - Name (e.g., "Shure SM58 Microphone")
   - Category (e.g., "Microphones")
   - Barcode (scan or type)
   - Quantity or Serial Number
   - Location
4. Click **Save**

### Create Your First Show
1. Click **Shows** in the navigation
2. Click **Create Show**
3. Enter:
   - Show name (e.g., "Hamlet")
   - Theatre/Venue (e.g., "Main Stage")
   - Dates
4. Click **Save Show**

### Build a Pull Sheet
1. From a show, click **Pull Sheet**
2. Scan items with F1 or your barcode scanner
3. Generate PDF for printing
4. The PDF includes a unique barcode to recall the show later

## Barcode Scanner Setup

### Zebra DS-Series Scanner
1. Connect scanner via USB
2. Ensure it's in **keyboard wedge mode**
3. Test in notepad - scanning should type the barcode
4. In the app, press **F1** and scan any item

### No Scanner?
- No problem! Press **F1** and type barcodes manually
- Click the scan button in the navigation bar
- Use the inventory search instead

## Your Data is Safe

Everything is stored locally in:
- **Windows**: `%APPDATA%/s-shop-inventory-app/`
- **macOS**: `~/Library/Application Support/s-shop-inventory-app/`

The app automatically:
- ✅ Creates the database on first run
- ✅ Saves all changes immediately
- ✅ Logs all actions for accountability
- ✅ Works offline (no internet required)

## Building for Production

When you're ready to deploy:

```bash
# Build Windows installer
npm run build:win

# Build macOS installer  
npm run build:mac

# Build both
npm run build
```

Installers will be in the `dist/` directory.

## Deploying Updates

When you make changes and want to release an update:

1. Update version in `package.json`
2. Commit and tag:
   ```bash
   git commit -am "Release v1.1.0"
   git tag v1.1.0
   git push origin main --tags
   ```
3. Build installers
4. Create GitHub Release and upload installers
5. All installed apps will auto-update! 🚀

## Need Help?

📖 **Documentation:**
- `README.md` - Full user guide
- `ARCHITECTURE.md` - How it works
- `DEVELOPMENT.md` - How to modify it
- `SUMMARY.md` - Feature overview

💬 **Tips:**
- All features work offline
- Database is backed up automatically
- PDFs saved to user data directory
- F1 is your friend for scanning
- Keyboard shortcuts work everywhere

## What's Included

✅ **Complete inventory management**  
✅ **Barcode scanning workflows**  
✅ **Show and pull sheet management**  
✅ **PDF generation with barcodes**  
✅ **Change orders and returns**  
✅ **Shortage alerts**  
✅ **Activity logging**  
✅ **Auto-updates**  
✅ **Backup/restore**  
✅ **Cross-platform (Windows & macOS)**  

## Testing Checklist

Try these workflows:

- [ ] Add 5 inventory items with different categories
- [ ] Create a show for an upcoming production
- [ ] Build a pull sheet by scanning items
- [ ] Generate and view the pull sheet PDF
- [ ] Process a change order (add/remove items)
- [ ] Complete a return when show closes
- [ ] Check the reports page for shortages
- [ ] Create a database backup
- [ ] Export an inventory report PDF
- [ ] Test barcode scanning (F1 key)

## Production Deployment

### Optional Enhancements (before production):
1. **Create professional icons**
   - See `assets/README.md` for instructions
   - Replace the placeholder SVG with your design

2. **Code signing** (optional but recommended):
   - **Windows**: Purchase code signing certificate
   - **macOS**: Apple Developer account required
   - See `DEVELOPMENT.md` for details

3. **First release**:
   - Build installers
   - Create v1.0.0 release on GitHub
   - Upload installers
   - Share with your team!

## Support & Maintenance

This app is designed to **last for years** without requiring updates. It:
- Has no cloud dependencies
- Uses only standard, stable technologies
- Is fully documented for future developers
- Includes auto-update for when you do want to release changes

**You're all set!** 🎭🔊

Start by running `npm run dev` and adding your first inventory item.

---

Questions? Check the documentation files or review the inline code comments - everything is documented!
