# Metronic to Bootstrap Migration Guide

## 🎯 **Migration Overview**
This guide helps you remove all Metronic dependencies and migrate to a clean Bootstrap 5 setup.

## 📋 **Pre-Migration Steps**

1. **Backup your current setup:**
   ```bash
   git checkout -b backup-before-metronic-removal
   git add .
   git commit -m "Backup before Metronic removal"
   ```

2. **Create new branch for migration:**
   ```bash
   git checkout -b bootstrap-migration
   ```

## 🔄 **Migration Steps**

### Step 1: Install Bootstrap Dependencies
```bash
# Remove node_modules and reinstall with new package.json
rm -rf node_modules package-lock.json
cp package.bootstrap.json package.json
npm install
```

### Step 2: Replace Build Configuration
```bash
# Backup current webpack.mix.js
mv webpack.mix.js webpack.mix.metronic.js
# Use new Bootstrap build config
mv webpack.bootstrap.mix.js webpack.mix.js
```

### Step 3: Update Helper Functions
```php
// In composer.json, update autoload files:
"files": [
    "app/Helpers/BootstrapHelpers.php",
    // Remove "app/Helpers/Helpers.php" or keep both during transition
]

// Run composer dump-autoload
composer dump-autoload
```

### Step 4: Update Layouts
Replace layout references in your Blade views:
```php
// Change from:
@extends('layout.master')
// To:
@extends('layout.bootstrap-master')

// Or use the app layout:
@extends('layout.bootstrap-app')
```

### Step 5: Build Assets
```bash
npm run dev
# Or for production:
npm run prod
```

## 🎨 **View Migration Checklist**

### Replace Metronic Classes & Attributes:

#### CSS Classes:
- `kt-*` → Remove or replace with Bootstrap equivalents
- `keenthemes-*` → Remove
- Custom Metronic utilities → Use Bootstrap utilities

#### HTML Attributes:
- `data-kt-scrolltop="true"` → Use custom scroll-to-top implementation
- `data-kt-menu-trigger="click"` → `data-bs-toggle="dropdown"`
- `data-kt-menu="true"` → `class="dropdown-menu"`
- `data-kt-*` → Replace with `data-bs-*` Bootstrap equivalents

#### Icons:
```php
// Replace Metronic icons:
{!! getIcon('arrow-up', 'svg-icon-2') !!}
// With Bootstrap icons:
{!! getIcon('arrow-up', 'fs-5') !!}
```

### Common Icon Mappings:
- `svg-icon-*` → `fs-*` (font-size utilities)
- Custom SVG icons → Bootstrap Icons or FontAwesome

## 🔧 **JavaScript Migration**

### Replace KT Functions:
```javascript
// Old Metronic:
KT.modal.open('#myModal');
// New Bootstrap:
var modal = new bootstrap.Modal(document.getElementById('myModal'));
modal.show();

// Old Metronic scrolltop:
KT.scrolltop.init();
// New: Already handled in app.js

// Old Metronic menu:
KT.menu.init();
// New: Bootstrap dropdowns work automatically
```

## 📁 **Files to Remove After Migration**

### Large Directories:
```bash
# Remove Metronic core files (AFTER migration is complete)
rm -rf resources/_keenthemes/
rm -rf resources/mix/

# Remove old build files
rm -rf public/assets/media/keenicons/
rm -rf public/assets/css/style.bundle.css
rm -rf public/assets/js/scripts.bundle.js
```

### Old Files:
- `app/Core/Theme.php` (replace with `app/Core/BootstrapTheme.php`)
- `webpack.mix.metronic.js`
- `package-lock.json` (will be regenerated)

## ✅ **Validation Steps**

1. **Test Key Functionality:**
   - [ ] Page loads without errors
   - [ ] Navigation works
   - [ ] Forms submit properly
   - [ ] DataTables work
   - [ ] Modals and dropdowns function
   - [ ] Icons display correctly
   - [ ] Responsive design works

2. **Check Browser Console:**
   - [ ] No JavaScript errors
   - [ ] No missing CSS/JS files (404s)
   - [ ] No Metronic-related errors

3. **Performance Check:**
   - [ ] Page load times improved
   - [ ] Smaller bundle sizes
   - [ ] Only necessary CSS/JS loaded

## 🎉 **Benefits After Migration**

- ✅ **Clean codebase** - No proprietary dependencies
- ✅ **Easier upgrades** - Standard Bootstrap updates
- ✅ **Better performance** - Smaller bundle sizes
- ✅ **More flexibility** - Standard web development patterns
- ✅ **Community support** - Bootstrap has huge community
- ✅ **Cost savings** - No Metronic license needed

## ⚠️ **Common Issues & Solutions**

### Issue: Missing Icons
**Solution:** Update icon references to use Bootstrap Icons or FontAwesome

### Issue: Layout Breaks
**Solution:** Replace custom Metronic CSS with Bootstrap utilities

### Issue: JavaScript Errors
**Solution:** Replace KT.* calls with Bootstrap equivalents

### Issue: Form Validation Not Working
**Solution:** Update validation to use standard HTML5 or custom implementation

## 📞 **Support**

If you encounter issues during migration:
1. Check the browser console for specific errors
2. Compare old vs new file structures
3. Refer to Bootstrap 5 documentation
4. Use git diff to see what changed

## 🔄 **Rollback Plan**

If you need to rollback:
```bash
git checkout backup-before-metronic-removal
```

Your original Metronic setup will be preserved in that branch.
