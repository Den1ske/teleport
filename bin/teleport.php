<?php
$start = microtime(true);

if (PHP_SAPI !== 'cli') {
    echo 'fatal: teleport should be invoked via the CLI version of PHP; you are using the ' . PHP_SAPI . ' SAPI' . PHP_EOL;
    exit(E_USER_ERROR);
}

$debug = (array_search('--debug', $argv, true) !== false);

if ($debug) error_reporting(-1);

if (function_exists('ini_set')) {
    @ini_set('display_errors', 1);

    $memoryLimit = trim(ini_get('memory_limit'));

    if ($memoryLimit != -1) {
        $memoryInBytes = function ($value) {
            $unit = strtolower(substr($value, -1, 1));
            $value = (int)$value;
            switch ($unit) {
                case 'g':
                    $value *= 1024;
                case 'm':
                    $value *= 1024;
                case 'k':
                    $value *= 1024;
            }
            return $value;
        };

        // Increase memory_limit if it is lower than 512M
        if ($memoryInBytes($memoryLimit) < 512 * 1024 * 1024) {
            @ini_set('memory_limit', '512M');
        }
        unset($memoryInBytes);
    }
    unset($memoryLimit);
}

try {
    require_once __DIR__ . '/../src/bootstrap.php';

    define('TELEPORT_BASE_PATH', rtrim(getcwd(), '/') . DIRECTORY_SEPARATOR);

    $options = array(
        'debug' => $debug
    );
    if (is_readable('config.php')) {
        $options = include 'config.php';
    }

    $teleport = \Teleport\Teleport::instance($options);
    $request = $teleport->getRequest();

    array_shift($argv);

    $request->handle($argv);
    $results = implode(PHP_EOL, $request->getResults());
    echo trim($results) . PHP_EOL;
    if ($debug) {
        printf("execution finished with exit code 0 in %2.4f seconds" . PHP_EOL, microtime(true) - $start);
    }
    exit(0);
} catch (\Exception $e) {
    echo $e->getMessage() . PHP_EOL;
    if ($debug) {
        printf("execution failed with exit code {$e->getCode()} in %2.4f seconds" . PHP_EOL, microtime(true) - $start);
    }
    exit($e->getCode());
}
