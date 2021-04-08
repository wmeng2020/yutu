<?php

namespace app\index\controller;
use app\index\model\Alipay;
use think\Controller;
use think\Db;
use think\Image;
use think\Request;
use think\Session;

class Pay extends Controller
{
    public function alipay(){
        $pay = new Alipay();
        $pay->notify();
    }

    public function vipAlipay(){
        $pay = new Alipay();
        $pay->vipNotify();
    }

}