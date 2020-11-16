<?php
namespace app\admin\validate;

use app\common\entity\User;
use think\Validate;

class CreateTask extends Validate
{
    protected $rule = [
        'sort_id'  =>  'require',
        'need_type_id' =>  'require',
        'task_url' =>  'require',
        'demand_side' =>  'require|checkDemand',
        'task_num' =>  'require',
//        'requirement' =>  'require',
        'task_price' =>  'require',
        'status' =>  'require',
    ];

    protected $message  =   [
        'sort_id.require' => '请输入任务分类名称',
        'need_type_id.require'     => '请输入需求分类名称',
        'task_url.require'     => '请输入任务链接',
        'demand_side.require'     => '请输入需求方',
        'task_num.require'     => '请输入任务总数量',
        'task_price.require'     => '请输入任务价格',
        'status.require'     => '请输入任务状态',
    ];
    public function checkDemand($val)
    {
        $res = User::where('mobile',$val)->find();
        if(!$res) return '请输入正确需求方';
        return true;
    }
}