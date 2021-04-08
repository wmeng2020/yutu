<?php
namespace app\socket\controller;

use Events;
use GatewayWorker\Lib\Gateway;
use think\Request;
use Workerman\Lib\Timer;
use \Workerman\Worker;

class CheckTime
{
    public function check($uid){
        Gateway::sendToCurrentClient(json_encode(['type'=>'is_time','uid'=>$uid]));
    }


}
