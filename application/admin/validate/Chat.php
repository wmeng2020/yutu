<?php
namespace app\admin\validate;

use think\Request;
use think\Validate;

class Chat extends Validate
{

    protected $rule = [
        'roomname' => 'require',
        'image' => 'require',
        'credit1' => 'require',
        'duration' => 'require',
        'game_type' => 'require',
        'limit_num' => 'require',
        'commission' => 'require',

    ];

    protected $message = [
        'roomname.require' => '房间名称不能为空',
        'image.require' => '房间图片不能为空',
        'credit1.require' => '钻石消耗不能为空',
        'duration.require' => '时长不能为空',
        'game_type.require' => '游戏类型不能为空',
        'limit_num.require' => '房间限制人数不能为空',
        'commission.require' => '佣金不能为空',
    ];


}