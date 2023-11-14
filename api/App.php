<?php
require_once __DIR__ . '/vendor/autoload.php';

use Workerman\Protocols\Http;
use Webman\App as WebmanApp;
use Webman\Config;
use Webman\Route;
use Webman\Middleware;
use Dotenv\Dotenv;
use support\Request;
use support\bootstrap\Log;
use support\bootstrap\Container;
use Workerman\Worker;

class App
{

    protected static $booted = false;
    protected static $worker;

    public static function boot($envFile)
    {
        if (self::$booted) {
            return true;
        }
        self::loadConfig($envFile);
        self::includeFiles();
        self::loadBootstrap();
        if ($timezone = config('app.default_timezone')) {
            date_default_timezone_set($timezone);
        }
        self::$worker = $worker = new Worker();

        $app = new WebmanApp($worker, Container::instance(), Log::channel('default'), app_path(), public_path());

        Route::load(config_path() . '/route.php');
        Middleware::load(config('middleware', []));
        Middleware::load(['__static__' => config('static.middleware', [])]);
        Http::requestClass(Request::class);

        return $app;
    }

    protected static function loadConfig($envFile)
    {
        Dotenv::createMutable(base_path(), $envFile)->load();
        Config::load(config_path(), ['route', 'container']);
    }

    protected static function includeFiles()
    {
        foreach (config('autoload.files', []) as $file) {
            include_once $file;
        }
    }

    protected static function loadBootstrap()
    {
        foreach (config('bootstrap', []) as $class_name) {
            /** @var \Webman\Bootstrap $class_name */
            $class_name::start(self::$worker);
        }
    }

}