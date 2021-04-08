<?php
namespace app\socket\controller;

use Workerman\Worker;
use GatewayWorker\Register;

class Sregister{

	public function __construct(){
		// register 服务必须是text协议
		$register = new Register('text://127.0.0.1:5659');
		
		// 如果不是在根目录启动，则运行runAll方法
		if(!defined('GLOBAL_START'))
		{
			Worker::runAll();
		}
	}
}
