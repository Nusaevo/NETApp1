<?php

/**
 * Bootstrap Theme Helper Functions
 * Replacement for Metronic helpers
 */

if (!function_exists('theme')) {
    function theme()
    {
        return app(App\Core\BootstrapTheme::class);
    }
}

if (!function_exists('getIcon')) {
    /**
     * Get Bootstrap or FontAwesome icon
     */
    function getIcon($iconName, $classNames = '', $type = 'bootstrap')
    {
        return theme()->getIcon($iconName, $classNames, $type);
    }
}

if (!function_exists('addHtmlAttribute')) {
    function addHtmlAttribute($scope, $name, $value)
    {
        theme()->addHtmlAttribute($scope, $name, $value);
    }
}

if (!function_exists('addHtmlClass')) {
    function addHtmlClass($scope, $value)
    {
        theme()->addHtmlClass($scope, $value);
    }
}

if (!function_exists('printHtmlAttributes')) {
    function printHtmlAttributes($scope)
    {
        return theme()->printHtmlAttributes($scope);
    }
}

if (!function_exists('printHtmlClasses')) {
    function printHtmlClasses($scope, $full = true)
    {
        return theme()->printHtmlClasses($scope, $full);
    }
}

if (!function_exists('includeFavicon')) {
    function includeFavicon()
    {
        return theme()->includeFavicon();
    }
}

if (!function_exists('includeFonts')) {
    function includeFonts()
    {
        return theme()->includeFonts();
    }
}

if (!function_exists('getGlobalAssets')) {
    function getGlobalAssets($type = 'css')
    {
        // With Vite, return empty array as assets are handled by @vite directive
        return [];
    }
}

if (!function_exists('getVendors')) {
    function getVendors($type = 'css')
    {
        // Return empty array as vendors are handled via CDN or Vite
        return [];
    }
}

if (!function_exists('getCustomCss')) {
    function getCustomCss()
    {
        return [];
    }
}

if (!function_exists('getCustomJs')) {
    function getCustomJs()
    {
        return [];
    }
}

// Simplified versions of other helpers
if (!function_exists('addVendor')) {
    function addVendor($vendor) {
        // Not needed for Bootstrap approach
    }
}

if (!function_exists('addVendors')) {
    function addVendors($vendors) {
        // Not needed for Bootstrap approach
    }
}

// Keep existing helper functions that don't depend on Metronic
if (!function_exists('getName')) {
    function getName()
    {
        return 'Laravel Bootstrap App';
    }
}

if (!function_exists('getDescription')) {
    function getDescription()
    {
        return '';
    }
}

if (!function_exists('getKeywords')) {
    function getKeywords()
    {
        return '';
    }
}

if (!function_exists('getVersion')) {
    function getVersion()
    {
        return '1.0.0';
    }
}

if (!function_exists('isDarkModeEnabled')) {
    function isDarkModeEnabled()
    {
        return false;
    }
}

if (!function_exists('isRtlDirection')) {
    function isRtlDirection()
    {
        return false;
    }
}

if (!function_exists('extendCssFilename')) {
    function extendCssFilename($path)
    {
        return $path;
    }
}
