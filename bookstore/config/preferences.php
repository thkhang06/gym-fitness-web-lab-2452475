<?php
/**
 * Client Preferences & Cookie Management Helper (Lab 2 Task 5.2)
 * Handles client-side persistent cookies for UI theme and user preferences
 * 
 * SECURITY MANDATE:
 * Absolutely NO passwords, tokens, or sensitive user data are stored in cookies.
 */

// Cookie Names & Lifespans
const COOKIE_THEME            = 'apex_theme';
const COOKIE_THEME_LIFESPAN   = 86400 * 30; // 30 Days

const COOKIE_RECENT_CLASS     = 'apex_recent_class';
const COOKIE_RECENT_TITLE     = 'apex_recent_title';
const COOKIE_FAV_CAT          = 'apex_fav_category';
const COOKIE_FAV_CAT_NAME     = 'apex_fav_category_name';
const COOKIE_PREF_LIFESPAN    = 86400 * 7;  // 7 Days

/**
 * Get the current active UI theme
 * 
 * @return string 'light' or 'dark'
 */
function getActiveTheme(): string {
    $theme = $_COOKIE[COOKIE_THEME] ?? 'light';
    return in_array($theme, ['light', 'dark'], true) ? $theme : 'light';
}

/**
 * Store UI theme preference into client cookie (30 days lifespan)
 * 
 * @param string $theme
 * @return void
 */
function setThemePreference(string $theme): void {
    $theme = in_array($theme, ['light', 'dark'], true) ? $theme : 'light';
    setcookie(
        COOKIE_THEME,
        $theme,
        time() + COOKIE_THEME_LIFESPAN,
        '/',
        '',
        false,
        true // HttpOnly for defense-in-depth
    );
    $_COOKIE[COOKIE_THEME] = $theme;
}

/**
 * Store the most recently viewed fitness class in cookie (7 days lifespan)
 * 
 * @param int $classId
 * @param string $title
 * @return void
 */
function trackRecentlyViewedClass(int $classId, string $title): void {
    $expiry = time() + COOKIE_PREF_LIFESPAN;
    setcookie(COOKIE_RECENT_CLASS, (string)$classId, $expiry, '/', '', false, true);
    setcookie(COOKIE_RECENT_TITLE, $title, $expiry, '/', '', false, true);
    $_COOKIE[COOKIE_RECENT_CLASS] = (string)$classId;
    $_COOKIE[COOKIE_RECENT_TITLE] = $title;
}

/**
 * Retrieve recently viewed class from cookie
 * 
 * @return array|null
 */
function getRecentlyViewedClass(): ?array {
    if (isset($_COOKIE[COOKIE_RECENT_CLASS]) && !empty($_COOKIE[COOKIE_RECENT_CLASS])) {
        return [
            'id'    => (int)$_COOKIE[COOKIE_RECENT_CLASS],
            'title' => $_COOKIE[COOKIE_RECENT_TITLE] ?? 'Viewed Class'
        ];
    }
    return null;
}

/**
 * Store user's favorite / last-filtered training category in cookie (7 days lifespan)
 * 
 * @param int $categoryId
 * @param string $categoryName
 * @return void
 */
function trackFavoriteCategory(int $categoryId, string $categoryName): void {
    $expiry = time() + COOKIE_PREF_LIFESPAN;
    setcookie(COOKIE_FAV_CAT, (string)$categoryId, $expiry, '/', '', false, true);
    setcookie(COOKIE_FAV_CAT_NAME, $categoryName, $expiry, '/', '', false, true);
    $_COOKIE[COOKIE_FAV_CAT] = (string)$categoryId;
    $_COOKIE[COOKIE_FAV_CAT_NAME] = $categoryName;
}

/**
 * Retrieve favorite category from cookie
 * 
 * @return array|null
 */
function getFavoriteCategory(): ?array {
    if (isset($_COOKIE[COOKIE_FAV_CAT]) && !empty($_COOKIE[COOKIE_FAV_CAT])) {
        return [
            'id'   => (int)$_COOKIE[COOKIE_FAV_CAT],
            'name' => $_COOKIE[COOKIE_FAV_CAT_NAME] ?? 'Selected Category'
        ];
    }
    return null;
}

/**
 * Clear all custom Apex Fitness cookies (Reset Preferences)
 * 
 * @return void
 */
function clearAllPreferences(): void {
    $cookieList = [
        COOKIE_THEME,
        COOKIE_RECENT_CLASS,
        COOKIE_RECENT_TITLE,
        COOKIE_FAV_CAT,
        COOKIE_FAV_CAT_NAME
    ];

    foreach ($cookieList as $name) {
        if (isset($_COOKIE[$name])) {
            setcookie($name, '', time() - 3600, '/');
            unset($_COOKIE[$name]);
        }
    }
}

/**
 * Generate inspection data for Task 5.2 demonstration in dashboard
 * 
 * @return array
 */
function getCookiesInspectorData(): array {
    return [
        [
            'name'        => COOKIE_THEME,
            'value'       => $_COOKIE[COOKIE_THEME] ?? 'Not Set (Default: light)',
            'purpose'     => 'Persists user interface theme (Light vs Dark Gym Mode)',
            'lifespan'    => '30 Days (86400 * 30)',
            'security'    => 'HttpOnly, Non-sensitive'
        ],
        [
            'name'        => COOKIE_RECENT_CLASS,
            'value'       => isset($_COOKIE[COOKIE_RECENT_CLASS]) ? "#{$_COOKIE[COOKIE_RECENT_CLASS]} - " . ($_COOKIE[COOKIE_RECENT_TITLE] ?? '') : 'None yet',
            'purpose'     => 'Remembers the last visited fitness class for quick access',
            'lifespan'    => '7 Days (86400 * 7)',
            'security'    => 'HttpOnly, Non-sensitive ID'
        ],
        [
            'name'        => COOKIE_FAV_CAT,
            'value'       => isset($_COOKIE[COOKIE_FAV_CAT]) ? "#{$_COOKIE[COOKIE_FAV_CAT]} - " . ($_COOKIE[COOKIE_FAV_CAT_NAME] ?? '') : 'None yet',
            'purpose'     => 'Remembers preferred workout category from catalog filtering',
            'lifespan'    => '7 Days (86400 * 7)',
            'security'    => 'HttpOnly, Non-sensitive ID'
        ],
        [
            'name'        => session_name(),
            'value'       => session_id() ?: 'Active Session Token',
            'purpose'     => 'PHP Session Cookie maintaining server-side authenticated state',
            'lifespan'    => 'Session (Expires on browser close or logout)',
            'security'    => 'HttpOnly, Session Managed'
        ]
    ];
}
