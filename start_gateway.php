<?php
ini_set('opcache.revalidate_freq',0);

define('APP_PATH', __DIR__ . '/application/');
define('BIND_MODULE','socket/Sgateway');
// 加载框架引导文件
require __DIR__ . '/thinkphp/start.php';