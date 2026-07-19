<?php
if (!defined('PW_BRAND_FAVICON_RENDERED')) {
    define('PW_BRAND_FAVICON_RENDERED', true);
    $pwScriptPath = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/SGSST-APP/index.php'));
    $pwScriptDir = rtrim(str_replace('\\', '/', dirname($pwScriptPath)), '/');
    if (basename($pwScriptDir) === 'admin') {
        $pwScriptDir = rtrim(str_replace('\\', '/', dirname($pwScriptDir)), '/');
    }
    if ($pwScriptDir === '' || $pwScriptDir === '.') {
        $pwScriptDir = '/SGSST-APP';
    }
    $pwFaviconUrl = $pwScriptDir . '/assets/favicon.svg?v=20260716';
    ?>
    <link rel="icon" type="image/svg+xml" href="<?php echo htmlspecialchars($pwFaviconUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="shortcut icon" href="<?php echo htmlspecialchars($pwFaviconUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="theme-color" content="#173b8f">
    <?php
}
?>
