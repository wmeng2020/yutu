<?php
use Workerman\Worker;
ini_set('opcache.revalidate_freq',0);
define('APP_PATH', __DIR__ . '/application/');
define('BIND_MODULE','socket/Worker');
define('API',  '/app/');
ini_set('display_errors', 'on');

if(strpos(strtolower(PHP_OS), 'win') === 0)
{
    exit("start.php not support windows, please use start_for_win.bat\n");
}

// 检查扩展
if(!extension_loaded('pcntl'))
{
    exit("Please install pcntl extension. See http://doc3.workerman.net/appendices/install-extension.html\n");
}

if(!extension_loaded('posix'))
{
    exit("Please install posix extension. See http://doc3.workerman.net/appendices/install-extension.html\n");
}

// 标记是全局启动
define('GLOBAL_START', 1);

//加载composer autoload文件
require __DIR__ . '/../vendor/autoload.php';

// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';

Worker::runAll();

