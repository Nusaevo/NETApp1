<?php

namespace App\Core;

/**
 * Bootstrap-based Theme class
 * Replacement for Metronic Theme functionality
 */
class BootstrapTheme
{
    public static $htmlAttributes = [];
    public static $htmlClasses = [];
    public static $javascriptFiles = [];
    public static $cssFiles = [];

    /**
     * Get global CSS assets for Vite
     */
    public function getGlobalAssets($type = 'css')
    {
        // With Vite, we use the @vite directive in templates
        // Return empty array as assets are handled by Vite
        return [];
    }    /**
     * Add HTML attribute
     */
    public function addHtmlAttribute($scope, $name, $value)
    {
        self::$htmlAttributes[$scope][$name] = $value;
    }

    /**
     * Add HTML class
     */
    public function addHtmlClass($scope, $class)
    {
        if (!isset(self::$htmlClasses[$scope])) {
            self::$htmlClasses[$scope] = [];
        }
        self::$htmlClasses[$scope][] = $class;
    }

    /**
     * Print HTML attributes
     */
    public function printHtmlAttributes($scope)
    {
        if (!isset(self::$htmlAttributes[$scope])) {
            return '';
        }

        $attributes = [];
        foreach (self::$htmlAttributes[$scope] as $name => $value) {
            $attributes[] = $name . '="' . htmlspecialchars($value) . '"';
        }

        return implode(' ', $attributes);
    }

    /**
     * Print HTML classes
     */
    public function printHtmlClasses($scope, $full = true)
    {
        if (!isset(self::$htmlClasses[$scope])) {
            return '';
        }

        $classes = implode(' ', array_unique(self::$htmlClasses[$scope]));

        return $full ? 'class="' . $classes . '"' : $classes;
    }

    /**
     * Get Bootstrap icon (replacement for getSvgIcon)
     */
    public function getIcon($iconName, $classNames = '', $type = 'bootstrap')
    {
        $iconMap = [
            'arrow-up' => 'bi-arrow-up',
            'arrow-down' => 'bi-arrow-down',
            'arrow-left' => 'bi-arrow-left',
            'arrow-right' => 'bi-arrow-right',
            'plus' => 'bi-plus',
            'minus' => 'bi-dash',
            'edit' => 'bi-pencil',
            'delete' => 'bi-trash',
            'search' => 'bi-search',
            'save' => 'bi-check',
            'cancel' => 'bi-x',
            'menu' => 'bi-list',
            'user' => 'bi-person',
            'home' => 'bi-house',
            'settings' => 'bi-gear',
            'dots-square' => 'bi-three-dots'
        ];

        $iconClass = $iconMap[$iconName] ?? 'bi-question-circle';

        if ($type === 'fontawesome') {
            $faMap = [
                'arrow-up' => 'fas fa-arrow-up',
                'arrow-down' => 'fas fa-arrow-down',
                'edit' => 'fas fa-edit',
                'delete' => 'fas fa-trash',
                'plus' => 'fas fa-plus',
                'save' => 'fas fa-save'
            ];
            $iconClass = $faMap[$iconName] ?? 'fas fa-question-circle';
        }

        return '<i class="' . $iconClass . ' ' . $classNames . '"></i>';
    }

    /**
     * Include favicon
     */
    public function includeFavicon()
    {
        return '<link rel="shortcut icon" href="' . asset('favicon.ico') . '" />';
    }

    /**
     * Include fonts (Google Fonts)
     */
    public function includeFonts()
    {
        return '<link rel="preconnect" href="https://fonts.googleapis.com">
                <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
                <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">';
    }

    /**
     * Add vendor files (not needed for Bootstrap approach)
     */
    public function addVendor($vendor) {}
    public function addVendors($vendors) {}

    /**
     * Mode and direction methods (simplified)
     */
    public function setModeSwitch($flag) {}
    public function isModeSwitchEnabled() { return false; }
    public function setModeDefault($mode) {}
    public function getModeDefault() { return 'light'; }
    public function setDirection($direction) {}
    public function getDirection() { return 'ltr'; }
    public function isRtlDirection() { return false; }
    public function extendCssFilename($path) { return $path; }
}
