<?php

/**
 * Site configuration constants
 */

// Load .env file if it exists
if (file_exists(__DIR__ . '/../../.env')) {
    $envLines = file(__DIR__ . '/../../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        putenv(sprintf('%s=%s', $name, $value));
        $_ENV[$name] = $value;
    }
}

// Site name - can be overridden by environment variable
define('SITE_NAME', getenv('SITE_NAME') ?: 'Fault Reporter');

// Domain - automatically from server or environment fallback
$domain = getenv('DOMAIN') ?: (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost');
if (isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME']) {
    $domain = $_SERVER['SERVER_NAME'];
}
define('DOMAIN', $domain);

?>
