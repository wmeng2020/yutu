<?php
namespace app\socket\controller;

use Events;
use GatewayWorker\Lib\Gateway;
use Workerman\Lib\Timer;
use \Workerman\Worker;
class Task
{
    public function start($uid)
    {
        $task = new Worker();
        // 开启多少个进程运行定时任务，注意多进程并发问题
        $task->count = 1;
        $this->timeTask($uid);
        // 运行worker
        Worker::runAll();
    }

    public function timeTask($uid){
        //定时任务
        //参数1 循环时间（秒）一次
        //参数2 命名空间到类
        //参数三 任务方法
        //参数四  方法传入参数
        //参数五 是否循环一次就停止此定时器   true 一直循环  false 循环一次就停止
        Timer::add(2, array($this, 'check'), [$uid], false);
        //定时任务
        //参数1 循环时间（秒）一次
        //参数2 命名空间到类
        //参数三 任务方法
        //参数四  方法传入参数
        //参数五 是否循环一次就停止此定时器   true 一直循环  false 循环一次就停止
//        Timer::add(2, array($this, 'check'), [$uid], false);
    }

    public function check($uid){
        echo "12312312312sdfsd";
        Gateway::sendToCurrentClient(json_encode(['type'=>'is_time','uid'=>$uid]));
    }
}


