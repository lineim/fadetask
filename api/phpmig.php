<?php

use \Phpmig\Adapter;
use Webman\Config;

require_once __DIR__ . '/App.php';

$app = new App();
$app->boot('.env');

$container = new ArrayObject();

$connection = Config::get('database.default');
$config = Config::get('database.connections.' . $connection);
$dsn = sprintf('mysql:dbname=%s;host=%s;port=%d', $config['database'], $config['host'], $config['port']);
$dbh = new PDO($dsn, $config['username'], $config['password']);
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$container['db'] = $dbh;
$container['phpmig.adapter'] = new Adapter\PDO\Sql($container['db'], 'migrations'); 
$container['phpmig.migrations_path'] = __DIR__ . DIRECTORY_SEPARATOR . 'migrations';

return $container;