<?php
/**
 * Internationalization & Localization (i18n/l10n)
 *
 * Provides a translation function __() that looks up translations from language files.
 * When no translation exists, returns the original string (English identity).
 *
 * Language files: languages/<locale>.php returning associative array of translations.
 * Active locale stored in the 'locale' setting.
 *
 * Usage:
 *   echo __('Add to Cart');          // Returns translated string or 'Add to Cart'
 *   echo __('Price: %s', '$9.99');   // With sprintf-style replacements
 *   echo _n('%d item', '%d items', $count); // Plural forms
 */

/** @var array<string, string> Loaded translations */
$_i18n_strings = [];

/** @var string Current locale */
$_i18n_locale = 'en';

/** @var bool Whether translations have been loaded */
$_i18n_loaded = false;

/**
 * Get the active locale.
 */
function getLocale(): string {
    global $_i18n_locale;
    return $_i18n_locale;
}

/**
 * Set the active locale and load its language file.
 */
function setI18nLocale(string $locale): void {
    global $_i18n_locale, $_i18n_strings, $_i18n_loaded;

    $locale = preg_replace('/[^a-zA-Z0-9_-]/', '', $locale);
    $_i18n_locale = $locale;
    $_i18n_loaded = false;
    $_i18n_strings = [];

    loadTranslations();
}

/**
 * Load translations for the current locale.
 */
function loadTranslations(): void {
    global $_i18n_strings, $_i18n_locale, $_i18n_loaded;

    if ($_i18n_loaded) return;
    $_i18n_loaded = true;

    if ($_i18n_locale === 'en') return; // English is the identity — no file needed

    $langFile = BASE_PATH . '/languages/' . $_i18n_locale . '.php';
    if (file_exists($langFile)) {
        $translations = require $langFile;
        if (is_array($translations)) {
            $_i18n_strings = $translations;
        }
    }
}

/**
 * Translate a string.
 *
 * @param string $text    Original English string (used as key)
 * @param mixed  ...$args Optional sprintf-style arguments
 * @return string Translated (or original) string
 */
function __(string $text, mixed ...$args): string {
    global $_i18n_strings;
    loadTranslations();

    $translated = $_i18n_strings[$text] ?? $text;

    if (!empty($args)) {
        return sprintf($translated, ...$args);
    }

    return $translated;
}

/**
 * Translate and echo a string.
 */
function _e(string $text, mixed ...$args): void {
    echo __($text, ...$args);
}

/**
 * Pluralized translation.
 *
 * @param string $singular English singular form
 * @param string $plural   English plural form
 * @param int    $count    Count to determine which form to use
 * @return string Translated plural form with count substituted
 */
function _n(string $singular, string $plural, int $count): string {
    global $_i18n_strings;
    loadTranslations();

    // Look for plural key format: "singular|plural"
    $key = "$singular|$plural";
    if (isset($_i18n_strings[$key])) {
        $forms = explode('|', $_i18n_strings[$key]);
        $text = ($count === 1) ? ($forms[0] ?? $singular) : ($forms[1] ?? $plural);
    } else {
        $text = ($count === 1) ? $singular : $plural;
    }

    return sprintf($text, $count);
}

/**
 * Initialize the i18n system from the site settings.
 */
function initI18n(): void {
    try {
        $locale = getSetting('locale', 'en');
    } catch (\Throwable $e) {
        $locale = 'en';
    }
    setI18nLocale($locale);
}

/**
 * Get all available locales (discovered from language files + 'en').
 *
 * @return array<string, string> locale code => display name
 */
function getAvailableLocales(): array {
    $locales = ['en' => 'English'];

    $langDir = BASE_PATH . '/languages';
    if (!is_dir($langDir)) return $locales;

    $files = glob($langDir . '/*.php');
    if (!$files) return $locales;

    foreach ($files as $file) {
        $code = basename($file, '.php');
        if ($code === 'en') continue;

        // Try to get the language name from the file's _language_name key
        $translations = require $file;
        if (is_array($translations) && isset($translations['_language_name'])) {
            $locales[$code] = $translations['_language_name'];
        } else {
            $locales[$code] = strtoupper($code);
        }
    }

    return $locales;
}

/**
 * Check if the current locale is RTL.
 */
function isRtlLocale(): bool {
    $rtlLocales = ['ar', 'he', 'fa', 'ur', 'yi', 'ps', 'sd'];
    return in_array(getLocale(), $rtlLocales, true);
}

/**
 * Get the HTML dir attribute for the current locale.
 */
function getTextDirection(): string {
    return isRtlLocale() ? 'rtl' : 'ltr';
}

/**
 * Format a price for display using the current locale settings.
 */
function formatPrice(float $amount): string {
    $symbol = getSetting('currency_symbol', '$');
    $code = getSetting('currency_code', 'USD');

    // Use intl extension if available for locale-aware formatting
    if (class_exists('NumberFormatter')) {
        $locale = getLocale();
        $fmt = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
        return $fmt->formatCurrency($amount, $code);
    }

    // Fallback: simple formatting
    return $symbol . number_format($amount, 2);
}

/**
 * Format a date for display using the current locale settings.
 */
function formatLocalizedDate(string $date, string $format = 'medium'): string {
    $timestamp = strtotime($date);
    if ($timestamp === false) return $date;

    // Use intl extension if available
    if (class_exists('IntlDateFormatter')) {
        $locale = getLocale();
        $dateType = match ($format) {
            'short' => \IntlDateFormatter::SHORT,
            'long' => \IntlDateFormatter::LONG,
            'full' => \IntlDateFormatter::FULL,
            default => \IntlDateFormatter::MEDIUM,
        };
        $fmt = new \IntlDateFormatter($locale, $dateType, \IntlDateFormatter::SHORT);
        return $fmt->format($timestamp);
    }

    // Fallback
    return date('M j, Y g:i A', $timestamp);
}
