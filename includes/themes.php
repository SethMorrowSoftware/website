<?php
/**
 * Theming Engine
 *
 * Provides a template override system and theme settings management.
 * Themes live in themes/<theme-name>/ with a theme.json manifest.
 *
 * Template Override Logic:
 *   When rendering a page, check if the active theme has an override file.
 *   If yes, use it. If not, fall back to the default template.
 *
 * Theme Settings:
 *   Each theme declares customizable variables in theme.json (colors, fonts,
 *   layout options). These are stored in the settings table and injected as
 *   CSS custom properties.
 */

// ============================================================
// Theme Directory & Discovery
// ============================================================

/**
 * Get the themes directory path.
 */
function getThemesDir(): string {
    return BASE_PATH . '/themes';
}

/**
 * Scan the themes directory and return all discovered theme manifests.
 *
 * @return array<string, array> Themes indexed by slug
 */
function discoverThemes(): array {
    $themesDir = getThemesDir();
    if (!is_dir($themesDir)) return [];

    $themes = [];
    $dirs = glob($themesDir . '/*/theme.json');
    if (!$dirs) return [];

    foreach ($dirs as $manifestPath) {
        $themeDir = dirname($manifestPath);
        $slug = basename($themeDir);

        $json = file_get_contents($manifestPath);
        $manifest = json_decode($json, true);
        if (!$manifest || !is_array($manifest)) {
            error_log("[THEME] Invalid manifest: $manifestPath");
            continue;
        }

        $manifest['slug'] = $slug;
        $manifest['path'] = $themeDir;
        $themes[$slug] = $manifest;
    }

    return $themes;
}

/**
 * Get the active theme slug.
 *
 * @return string The active theme slug, or 'default' if none set
 */
function getActiveTheme(): string {
    return getSetting('active_theme', 'default');
}

/**
 * Set the active theme.
 */
function setActiveTheme(string $slug): void {
    updateSetting('active_theme', $slug);
}

/**
 * Get the active theme's manifest data.
 *
 * @return array|null Theme manifest or null if not found
 */
function getActiveThemeManifest(): ?array {
    $slug = getActiveTheme();
    if ($slug === 'default') return null;

    $themes = discoverThemes();
    return $themes[$slug] ?? null;
}

// ============================================================
// Template Override System
// ============================================================

/**
 * Resolve a template path, checking the active theme for an override first.
 *
 * Usage:
 *   $template = resolveTemplate('pages/catalog.php');
 *   // Returns themes/<active>/pages/catalog.php if it exists,
 *   // otherwise returns the default pages/catalog.php
 *
 * @param string $relativePath Path relative to the project root (e.g. 'pages/home.php')
 * @return string Absolute path to the resolved template file
 */
function resolveTemplate(string $relativePath): string {
    $theme = getActiveTheme();

    if ($theme !== 'default') {
        $themePath = getThemesDir() . '/' . $theme . '/' . $relativePath;
        if (file_exists($themePath)) {
            return $themePath;
        }
    }

    // Fall back to the default template
    return BASE_PATH . '/' . $relativePath;
}

/**
 * Check if the active theme has an override for a given template.
 */
function themeHasOverride(string $relativePath): bool {
    $theme = getActiveTheme();
    if ($theme === 'default') return false;

    $themePath = getThemesDir() . '/' . $theme . '/' . $relativePath;
    return file_exists($themePath);
}

// ============================================================
// Theme Settings
// ============================================================

/**
 * Get a theme setting value.
 *
 * Theme settings are stored in the main settings table with the key format:
 * theme_<theme_slug>_<setting_key>
 *
 * @param string $themeSlug Theme identifier
 * @param string $key       Setting key
 * @param string $default   Default value
 * @return string
 */
function getThemeSetting(string $themeSlug, string $key, string $default = ''): string {
    return getSetting('theme_' . $themeSlug . '_' . $key, $default);
}

/**
 * Set a theme setting value.
 */
function setThemeSetting(string $themeSlug, string $key, string $value): void {
    updateSetting('theme_' . $themeSlug . '_' . $key, $value);
}

/**
 * Get all customizable settings for a theme from its manifest.
 *
 * Returns the settings schema (array of setting definitions) from theme.json.
 * Each setting has: key, label, type (color/text/select/number), default.
 *
 * @param array $manifest Theme manifest data
 * @return array Settings schema
 */
function getThemeSettingsSchema(array $manifest): array {
    return $manifest['settings'] ?? [];
}

/**
 * Get the current values for all theme settings, with defaults from the manifest.
 *
 * @param string $themeSlug Theme slug
 * @param array  $manifest  Theme manifest
 * @return array<string, string> Key-value pairs of current setting values
 */
function getThemeSettingValues(string $themeSlug, array $manifest): array {
    $schema = getThemeSettingsSchema($manifest);
    $values = [];

    foreach ($schema as $setting) {
        $key = $setting['key'] ?? '';
        if (!$key) continue;
        $default = $setting['default'] ?? '';
        $values[$key] = getThemeSetting($themeSlug, $key, $default);
    }

    return $values;
}

/**
 * Generate CSS custom properties from theme settings.
 *
 * Converts theme settings into CSS variables that can be injected
 * into the page's <style> block.
 *
 * @return string CSS custom property declarations (without :root wrapper)
 */
function generateThemeCssVariables(): string {
    $theme = getActiveTheme();
    if ($theme === 'default') return '';

    $manifest = getActiveThemeManifest();
    if (!$manifest) return '';

    $schema = getThemeSettingsSchema($manifest);
    if (empty($schema)) return '';

    $css = '';
    foreach ($schema as $setting) {
        $key = $setting['key'] ?? '';
        $cssVar = $setting['css_var'] ?? '';
        if (!$key || !$cssVar) continue;

        $default = $setting['default'] ?? '';
        $value = getThemeSetting($theme, $key, $default);
        if ($value !== '') {
            $css .= "    $cssVar: $value;\n";
        }
    }

    return $css;
}

/**
 * Get the theme's custom CSS file path if it exists.
 *
 * @return string|null URL to the theme's stylesheet, or null
 */
function getThemeStylesheet(): ?string {
    $theme = getActiveTheme();
    if ($theme === 'default') return null;

    $cssFile = getThemesDir() . '/' . $theme . '/style.css';
    if (file_exists($cssFile)) {
        return url('themes/' . $theme . '/style.css');
    }

    return null;
}

/**
 * Get layout class for the body tag based on theme settings.
 *
 * @return string CSS classes to add to the body tag
 */
function getThemeBodyClasses(): string {
    $theme = getActiveTheme();
    $classes = ['theme-' . $theme];

    if ($theme !== 'default') {
        $manifest = getActiveThemeManifest();
        if ($manifest) {
            // Check for layout variant setting
            $layout = getThemeSetting($theme, 'layout', $manifest['default_layout'] ?? 'default');
            if ($layout) {
                $classes[] = 'layout-' . $layout;
            }
        }
    }

    return implode(' ', $classes);
}
