<?php
require_once __DIR__ . '/vendor/autoload.php';

use Webman\App as WebmanApp;
use Webman\Config;
use Webman\Util;
use Dotenv\Dotenv;
use Workerman\Worker;

class App extends WebmanApp
{

    protected static $booted = false;
    protected static $worker;

    public static function run()
    {
        if (self::$booted) {
            return true;
        }
        ini_set('display_errors', 'on');
        error_reporting(E_ALL);

        if (class_exists(Dotenv::class) && file_exists(run_path('.env'))) {
            if (method_exists(Dotenv::class, 'createUnsafeImmutable')) {
                Dotenv::createUnsafeImmutable(run_path())->load();
            } else {
                Dotenv::createMutable(run_path())->load();
            }
        }

        static::loadAllConfig(['route', 'container']);

        $errorReporting = config('app.error_reporting');
        if (isset($errorReporting)) {
            error_reporting($errorReporting);
        }
        if ($timezone = config('app.default_timezone')) {
            date_default_timezone_set($timezone);
        }

        $runtimeLogsPath = runtime_path() . DIRECTORY_SEPARATOR . 'logs';
        if (!file_exists($runtimeLogsPath) || !is_dir($runtimeLogsPath)) {
            if (!mkdir($runtimeLogsPath, 0777, true)) {
                throw new RuntimeException("Failed to create runtime logs directory. Please check the permission.");
            }
        }

        $runtimeViewsPath = runtime_path() . DIRECTORY_SEPARATOR . 'views';
        if (!file_exists($runtimeViewsPath) || !is_dir($runtimeViewsPath)) {
            if (!mkdir($runtimeViewsPath, 0777, true)) {
                throw new RuntimeException("Failed to create runtime views directory. Please check the permission.");
            }
        }

        Worker::$onMasterReload = function () {
            if (function_exists('opcache_get_status')) {
                if ($status = opcache_get_status()) {
                    if (isset($status['scripts']) && $scripts = $status['scripts']) {
                        foreach (array_keys($scripts) as $file) {
                            opcache_invalidate($file, true);
                        }
                    }
                }
            }
        };
        // Windows does not support custom processes.
        if (DIRECTORY_SEPARATOR === '/') {
            foreach (config('process', []) as $processName => $config) {
                worker_start($processName, $config);
            }
            foreach (config('plugin', []) as $firm => $projects) {
                foreach ($projects as $name => $project) {
                    if (!is_array($project)) {
                        continue;
                    }
                    foreach ($project['process'] ?? [] as $processName => $config) {
                        worker_start("plugin.$firm.$name.$processName", $config);
                    }
                }
                foreach ($projects['process'] ?? [] as $processName => $config) {
                    worker_start("plugin.$firm.$processName", $config);
                }
            }
        }
    }

    /**
     * LoadAllConfig.
     * @param array $excludes
     * @return void
     */
    public static function loadAllConfig(array $excludes = [])
    {
        Config::load(config_path(), $excludes);
        $directory = base_path() . '/plugin';
        foreach (Util::scanDir($directory, false) as $name) {
            $dir = "$directory/$name/config";
            if (is_dir($dir)) {
                Config::load($dir, $excludes, "plugin.$name");
            }
        }
    }

}