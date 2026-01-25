# Migration to Electron Forge

## What Changed

This project has been migrated from **electron-builder** to **Electron Forge** to simplify the development and build process, especially on Windows.

## Benefits of Electron Forge

✅ **Automatic Native Module Handling** - No more manual `electron-rebuild` commands
✅ **Better Windows Support** - Handles native compilation automatically in most cases
✅ **Simpler Configuration** - All config in one place (`package.json`)
✅ **Active Development** - Officially maintained by the Electron team
✅ **Better Developer Experience** - Clearer error messages and better tooling

## Key Differences

### Commands Changed

| Old (electron-builder) | New (Electron Forge) | Purpose |
|------------------------|----------------------|---------|
| `npm run dev` | `npm run dev` or `npm start` | Run in development |
| `npm run build` | `npm run make` | Create installer |
| `npm run build:win` | `npm run make` | Build for current platform |
| `npm run rebuild` | *automatic* | Rebuild native modules |
| *(none)* | `npm run package` | Package without installer |
| *(none)* | `npm run publish` | Publish to GitHub releases |

### Output Directories

| Type | Old Location | New Location |
|------|--------------|--------------|
| Windows Installer | `dist/S-Shop Inventory Setup.exe` | `out/make/squirrel.windows/x64/s_shop_inventory-1.0.0 Setup.exe` |
| macOS Archive | `dist/S-Shop Inventory-1.0.0.dmg` | `out/make/zip/darwin/x64/s-shop-inventory-darwin-x64-1.0.0.zip` |
| Packaged App | `dist/win-unpacked/` | `out/s-shop-inventory-win32-x64/` |

### Configuration

**Old:** Separate `build` section in `package.json`
```json
{
  "build": {
    "appId": "com.cmft.sshop-inventory",
    "win": { ... },
    "mac": { ... }
  }
}
```

**New:** Forge configuration in `config.forge` section
```json
{
  "config": {
    "forge": {
      "packagerConfig": { ... },
      "makers": [ ... ],
      "publishers": [ ... ]
    }
  }
}
```

## Installation Improvements

### Before (electron-builder)
- Windows users **REQUIRED** Visual Studio Build Tools
- Manual `electron-rebuild` needed after npm install
- Complex troubleshooting for native modules
- ~7GB download + 10 minute setup

### After (Electron Forge)
- Forge handles most native module rebuilding automatically
- Visual Studio Build Tools only needed in rare edge cases
- Better error messages when issues occur
- Simpler setup process

## What Stayed the Same

✅ All application code unchanged
✅ Database structure identical
✅ UI and features exactly the same
✅ Auto-update functionality preserved
✅ Cross-platform support maintained

## For Developers

If you were working with the old version:

1. **Pull latest changes**:
   ```bash
   git pull
   ```

2. **Clean old build artifacts**:
   ```bash
   rm -rf dist/ node_modules/ package-lock.json
   ```

3. **Reinstall dependencies**:
   ```bash
   npm install
   ```

4. **Run the app**:
   ```bash
   npm run dev
   ```

## Publishing Releases

### Old Process
1. Build: `npm run build`
2. Upload `dist/` files manually to GitHub releases

### New Process (Easier!)
1. Build: `npm run make`
2. Either:
   - **Automatic**: `npm run publish` (requires GITHUB_TOKEN)
   - **Manual**: Upload files from `out/make/` to GitHub releases

## Troubleshooting

### "Module not found" errors
Run `npm install` again - Forge will rebuild native modules

### Build fails on Windows
In rare cases, you may still need Visual Studio Build Tools. See README.md for instructions.

### Can't find output files
Check `out/make/` directory instead of `dist/`

## Questions?

See the updated documentation:
- **README.md** - Installation and usage
- **DEVELOPMENT.md** - Building and releasing
- **GETTING_STARTED.md** - Quick start guide
