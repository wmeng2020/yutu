<?php

namespace app\index\controller;
use think\Db;
use think\Image;
use think\Request;

class Index extends Base
{
    public function getIndexData(Request $request){
        //轮播图
        $Carousel = Db::name('image')->order('`sort` asc,id desc')->select();

    }


}