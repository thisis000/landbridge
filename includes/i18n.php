<?php
/**
 * Bilingual translation layer. Session + cookie persistence.
 */

function current_lang(): string
{
    if (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], ['bn', 'en'], true)) {
        return $_COOKIE['lang'];
    }
    if (isset($_SESSION['lang']) && in_array($_SESSION['lang'], ['bn', 'en'], true)) {
        return $_SESSION['lang'];
    }
    return 'bn';
}

function set_lang(string $lang): void
{
    $lang = ($lang === 'en') ? 'en' : 'bn';
    $_SESSION['lang'] = $lang;
    if (!headers_sent()) {
        setcookie('lang', $lang, [
            'expires'  => time() + 60 * 60 * 24 * 365,
            'path'     => '/',
            'samesite' => 'Lax',
        ]);
    }
}

/**
 * Translate a key. Supports {placeholder} interpolation.
 * Fallback chain: current language → English → key itself.
 */
function t(string $key, array $replace = []): string
{
    global $__translations;
    $lang = current_lang();
    $text = $__translations[$lang][$key]
        ?? $__translations['en'][$key]
        ?? $key;
    foreach ($replace as $k => $v) {
        $text = str_replace('{' . $k . '}', (string)$v, $text);
    }
    return $text;
}

/** Build a language-switch link that keeps the current query string. */
function lang_link(string $lang): string
{
    $params = $_GET;
    $params['lang'] = $lang;
    return 'index.php?' . http_build_query($params);
}