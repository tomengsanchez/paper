<?php
define('ROOT', __DIR__);
spl_autoload_register(function ($class) {
    $file = ROOT . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// PHP error logging
$logDir = ROOT . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}
ini_set('log_errors', 1);
ini_set('error_log', $logDir . '/php_error.log');

\Core\Logger::init();
\Core\Database::init(require ROOT . '/config/database.php');
\Core\Auth::init();

$appConfig = file_exists(ROOT . '/config/app.php') ? require ROOT . '/config/app.php' : [];
define('BASE_URL', rtrim($appConfig['base_url'] ?? '', '/'));
