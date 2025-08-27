# Laravel & Bootstrap Upgrade Guide 🚀

## 📊 Current vs Target Versions

| Component | Current | Target | Status |
|-----------|---------|--------|---------|
| Laravel | 11.33.2 | 11.35+ | ✅ Minor Update |
| Bootstrap | 5.3.2 | 5.3.3 | ✅ Patch Update |
| Build Tool | Laravel Mix | Vite | 🔄 **Major Change** |
| Node.js Deps | Mixed | Latest | 🔄 Updates Available |

## 🎯 **Upgrade Benefits**

### **Vite vs Laravel Mix:**
- ⚡ **3-5x faster** hot reload during development
- 🚀 **Better performance** with ES modules
- 📦 **Smaller bundle sizes** with tree-shaking
- 🔧 **Modern tooling** with TypeScript support
- 🎨 **Better CSS processing** with PostCSS

### **Bootstrap 5.3.3:**
- 🐛 Bug fixes and security patches
- 🎨 Enhanced CSS utilities
- ♿ Better accessibility features

## 🛠 **Step-by-Step Upgrade Process**

### **Phase 1: Backup Current Setup**
```bash
# Create backup branch
git checkout -b pre-upgrade-backup
git add .
git commit -m "Backup before Laravel + Bootstrap upgrade"

# Create upgrade branch
git checkout -b laravel-bootstrap-upgrade
```

### **Phase 2: Update Package Dependencies**

#### **Node.js Dependencies:**
```bash
# Remove old node modules
rm -rf node_modules package-lock.json

# Replace package.json with upgraded version
cp package.upgraded.json package.json

# Install new dependencies
npm install
```

#### **Composer Dependencies:**
```bash
# Update Composer packages
cp composer.upgraded.json composer.json
composer update

# Update autoloader
composer dump-autoload
```

### **Phase 3: Migrate from Laravel Mix to Vite**

#### **Replace Build Configuration:**
```bash
# Remove old build files
rm webpack.mix.js webpack.bootstrap.mix.js

# Vite config is already created: vite.config.js
```

#### **Update Asset Structure:**
```bash
# Create Vite-compatible JS files
# Files created:
# - resources/bootstrap/plugins-vite.js
# - resources/bootstrap/app-vite.js
```

#### **Update Scripts in package.json:**
- `npm run dev` → Uses Vite dev server
- `npm run build` → Vite production build
- `npm run hot` → Vite HMR with network access

### **Phase 4: Update Templates for Vite**

The master layout has been updated to use `@vite()` directive instead of manual asset includes:

```blade
<!-- Old Laravel Mix approach -->
@foreach(getGlobalAssets('css') as $path)
    <link rel="stylesheet" href="{{ asset($path) }}">
@endforeach

<!-- New Vite approach -->
@vite(['resources/bootstrap/plugins.scss', 'resources/bootstrap/app.scss', 'resources/bootstrap/plugins.js', 'resources/bootstrap/app.js'])
```

### **Phase 5: Test & Validate**

#### **Development Testing:**
```bash
# Start Vite dev server
npm run dev

# In another terminal, start Laravel
php artisan serve
```

#### **Production Testing:**
```bash
# Build for production
npm run build

# Check generated files
ls -la public/build/
```

## 🔧 **Manual Updates Required**

### **1. Update Blade Templates**
Replace old layout extends in your views:
```blade
<!-- Change from: -->
@extends('layout.master')

<!-- To: -->
@extends('layout.bootstrap-master')
```

### **2. Update Asset References**
Any hardcoded asset paths need updating:
```blade
<!-- Old -->
<link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet">

<!-- New - handled by @vite() -->
<!-- Remove manual asset includes -->
```

### **3. Update Helper Registration**
In `composer.json`, the autoload has been updated to use `BootstrapHelpers.php`:
```json
"files": [
    "app/Helpers/BootstrapHelpers.php",
    // ... other helpers
]
```

## 📁 **New File Structure**

```
resources/
├── bootstrap/
│   ├── plugins.scss          # Bootstrap + plugin styles
│   ├── app.scss              # Custom app styles
│   ├── plugins-vite.js       # Vite-compatible plugins
│   ├── app-vite.js          # Vite-compatible app JS
│   └── custom/
│       └── app-init.js       # Bootstrap initialization
├── views/
│   └── layout/
│       ├── bootstrap-master.blade.php  # Vite-enabled master
│       └── bootstrap-app.blade.php     # App layout
└── ...

public/
└── build/                    # Vite build output (replaces assets/)
    ├── assets/
    └── manifest.json
```

## ⚠️ **Breaking Changes & Migration Notes**

### **Asset URLs:**
- **Old:** `asset('assets/css/style.bundle.css')`
- **New:** Handled automatically by `@vite()` directive

### **JavaScript Globals:**
- jQuery, Bootstrap still available globally
- Custom `AppUtils` namespace maintained
- All existing functionality preserved

### **Development Workflow:**
- **Old:** `npm run watch`
- **New:** `npm run dev` (with HMR!)

### **Production Build:**
- **Old:** `npm run production`
- **New:** `npm run build`

## ✅ **Validation Checklist**

After upgrade, verify these work:
- [ ] Pages load without console errors
- [ ] Bootstrap components (modals, dropdowns) work
- [ ] DataTables render properly
- [ ] Forms submit correctly
- [ ] Icons display properly
- [ ] Custom JavaScript functions work
- [ ] Livewire components work
- [ ] Mobile responsiveness works
- [ ] Production build completes successfully

## 🚀 **Performance Improvements Expected**

### **Development:**
- ⚡ Hot reload: **3-5x faster**
- 🔧 Build time: **2-3x faster**
- 💾 Memory usage: **Reduced**

### **Production:**
- 📦 Bundle size: **10-20% smaller**
- ⏱ Load time: **Faster initial load**
- 🎯 Code splitting: **Better caching**

## 🔄 **Execution Commands**

Ready to upgrade? Run these commands:

```bash
# 1. Backup
git checkout -b laravel-bootstrap-upgrade

# 2. Update dependencies
rm -rf node_modules package-lock.json
cp package.upgraded.json package.json
npm install

# 3. Update Composer
cp composer.upgraded.json composer.json
composer update
composer dump-autoload

# 4. Build assets
npm run dev

# 5. Test the application
php artisan serve
```

## 🆘 **Rollback Plan**

If issues occur:
```bash
# Return to backup
git checkout pre-upgrade-backup

# Restore old dependencies
npm install
composer install
```

## 📞 **Post-Upgrade Support**

After upgrade:
1. Monitor browser console for errors
2. Test all major features
3. Check production build works
4. Update any custom JavaScript as needed

**Upgrade is ready to execute! 🎉**
