<?php

namespace app\index\controller;




use app\common\entity\ConfigPublishModel;
use app\common\entity\ConfigUserLevelModel;
use app\common\entity\MyWalletLog;
use app\common\entity\TaskModel;
use app\common\entity\TaskNeedTypeModel;
use app\common\entity\TaskOrderModel;
use app\common\entity\TaskTypeModel;
use think\Request;

class Task extends Base
{
    /**
     * 任务列表
     */
    public function index(Request $request)
    {
        $limit = $request->post('limit') ? $request->post('limit') : 15;
        $page = $request->post('page') ? $request->post('page') : 1;
        $sort_id = $request->post('sort_id');
        $need_type_id = $request->post('need_type_id');
        $query = TaskModel::alias('t')
            ->field('t.id,t.task_url,t.demand_side,t.task_num,t.task_price,n.need_name,tt.type_name')
            ->leftJoin('task_need_type n','n.id = t.need_type_id')
            ->leftJoin('task_type tt','tt.id = t.sort_id');
        //本人已领取任务ID
        $received_task = TaskOrderModel::where('uid',$this->userId)
            ->whereIn('status',[0,1])
            ->column('task_id');
        if($received_task){
            $query->whereNotIn('t.id',$received_task);
        }
        if($sort_id){

            $query->where('sort_id',$sort_id);
        }
        if($need_type_id){
            $query->where('need_type_id',$need_type_id);
        }
        $list = $query->where('status',1)
            ->page($page)
            ->paginate($limit);

        $icon_src = TaskTypeModel::where('id',$sort_id)->value('type_icon');
        $data = [
            'list' => $list,
            'icon' => $icon_src?$request->domain().$icon_src:'',
        ];
//        $list['icon'] = $icon_src?$request->domain().$icon_src:'';
        return json(['code' => 0, 'msg' => '获取成功', 'info' => $data]);
    }
    /**
     * 任务分类
     */
    public function getTaskType(Request $request)
    {
        $list = TaskTypeModel::field('id,type_name,type_icon')
            ->select();
        foreach ($list as $v){
            $v['type_icon'] = $request->domain().$v['type_icon'];
        }
        return json(['code' => 0, 'msg' => '获取成功', 'info' => $list]);
    }
    /**
     * 领取任务
     */
    public function createTaskOrder(Request $request)
    {
        $id = $request->post('id');
        if(!$id){
            return json(['code' => 1, 'msg' => '非法操作']);
        }
        $task_start_time = $this->getConfigValue('task_start_time');
        $task_end_time = $this->getConfigValue('task_end_time');
        if(time() < strtotime($task_start_time) || time() > strtotime($task_end_time)){
            return json(['code' => 1, 'msg' => '未开始']);
        }
        $res = TaskOrderModel::where('task_id',$id)
            ->where('uid',$this->userId)
            ->whereIn('status',[0,1])
            ->find();
        if($res)  return json(['code' => 1, 'msg' => '已领取']);
        $info = TaskModel::where('id',$id)->find();
        if($info['task_num'] < 1){
            return json(['code' => 1, 'msg' => '任务数量不足']);
        }
        //一共可领取任务数
        $level = \app\common\entity\User::where('id',$this->userId)
            ->value('level');

        $tasks_num = ConfigUserLevelModel::where('id',$level+1)
            ->value('tasks_num');
        //已领取任务数
        $has_task_num = TaskOrderModel::where('uid',$this->userId)
            ->whereTime('receivetime','today')
            ->count();

        if($has_task_num >= $tasks_num){
            return json(['code' => 1, 'msg' => '可接任务数量不足']);
        }
        $user = \app\common\entity\User::where('id',$this->userId)
            ->find();
        if($user['tiktok_status'] != 3){
            return json(['code' => 1, 'msg' => '请绑定抖音账号','toUrl'=>1]);
        }elseif ($user['kwaifu_status'] != 3){
            return json(['code' => 1, 'msg' => '请绑定快手账号','toUrl'=>2]);
        }
        $data = [
            'task_id' => $id,
            'uid' => $this->userId,
            'realprice' => $info['task_price'],
            'status' => 0,
            'receivetime' => time(),
        ];
        $model = new TaskOrderModel();
        $result = $model->addNew($model,$data);
        if ($result) {
            TaskModel::where('id',$id)->setDec('task_num');
            return json(['code' => 0, 'msg' => '领取成功']);
        }
        return json(['code' => 1, 'msg' => '领取失败']);
    }
    /**
     * 已接任务列表
     */
    public function receivedTaskList(Request $request)
    {
        $limit = $request->post('limit',15) ;
        $page = $request->post('page',1);
        $status = $request->post('status');
        $query = new TaskOrderModel();
        if(isset($status)){
            $query->where('to.status',$status);
        }
        $list = $query->alias('to')
            ->field('to.id,to.realprice,to.status,tt.type_icon,tnt.need_name,t.demand_side,examinetime')
            ->leftJoin('task t','t.id = to.task_id')
            ->leftJoin('task_type tt','tt.id = t.sort_id')
            ->leftJoin('task_need_type tnt','tnt.id = t.need_type_id')
            ->where('to.uid',$this->userId)
            ->order('to.receivetime','desc')
            ->page($page)
            ->paginate($limit);
        foreach ($list as $v){
            $v['examinetime'] = date('Y-m-d H:i:s',$v['examinetime']);
        }

        return json(['code' => 0, 'msg' => '获取成功', 'info' => $list]);
    }
    /**
     * 提交任务
     */
    public function submitTask(Request $request)
    {
        $id = $request->post('id');
        $image_id = $request->post('image_id');
        if(!$id || !$image_id){
            return json(['code' => 1, 'msg' => '非法操作']);
        }
        $task_info = TaskOrderModel::where('id',$id)->find();
        if($task_info['status'] !== 0 ){
            return json(['code' => 1, 'msg' => '请勿重复操作']);
        }
        if($task_info['uid'] != $this->userId){
            return json(['code' => 1, 'msg' => '非法操作']);
        }
        $res = TaskOrderModel::where('id',$id)
            ->update([
                'status' => 1,
                'image_id' => $image_id,
                'submittime' => time(),
            ]);
        if ($res) {
            return json(['code' => 0, 'msg' => '提交成功']);
        }
        return json(['code' => 1, 'msg' => '提交失败']);
    }
    /**
     * 获取需求类型
     */
    public function getNeedType()
    {
        $list = TaskNeedTypeModel::field('id,need_name')
            ->select();
        return json(['code' => 0, 'msg' => '获取成功', 'info' => $list]);
    }
    /**
     * 获取发布管理
     */
    public function getPublish()
    {
        $list = ConfigPublishModel::select();
        return json(['code' => 0, 'msg' => '获取成功', 'info' => $list]);
    }
    /**
     * 发布任务
     */
    public function createTask(Request $request)
    {
        $validate = $this->validate($request->post(), '\app\index\validate\CreateTask');
        if ($validate !== true) {
            return json(['code' => 1, 'msg' => $validate]);
        }
        $post_data = $request->post();
        //发布套餐信息
        $user_info = \app\common\entity\User::alias('u')
            ->field('u.id,mw.number')
            ->leftJoin('my_wallet mw','u.id = mw.uid')
            ->where('u.id',$this->userId)
            ->find();
        if($post_data['task_price'] > $user_info['number']){
            return json(['code' => 1, 'msg' => '余额不足']);
        }
        $model = new \app\common\entity\MyWallet();
        $model_data = [
            'uid' => $user_info['id'],
            'num' => $post_data['task_price'],
            'remark' => '发布任务',
        ];
        $take_money_res = $model->publishTask($model,$model_data);
        if($take_money_res){
            $post_data['status'] = 1;
            $post_data['requirement'] = TaskNeedTypeModel::where('id',$post_data['need_type_id'])->value('need_name');
            $query = new TaskModel();
            $res = $query->addNew($query,$post_data);
            if ($res) {
                return json(['code' => 0, 'msg' => '发布成功']);
            }
            return json(['code' => 1, 'msg' => '发布失败']);
        }
    }
    /**
     * 已发布任务列表
     */
    public function getPublicshList(Request $request)
    {
        $limit = $request->post('limit',15) ;
        $page = $request->post('page',1);
        $status = $request->post('status',1);
        $mobile = \app\common\entity\User::where('id',$this->userId)
            ->value('mobile');
        $query = TaskModel::alias('t')
            ->field('t.id,t.task_url,t.demand_side,t.task_num,t.task_price,n.need_name,tt.type_name,tt.type_icon,t.create_time')
            ->leftJoin('task_need_type n','n.id = t.need_type_id')
            ->leftJoin('task_type tt','tt.id = t.sort_id');
        if(!$status){
            $query->where('task_num',0);
        }
        $list = $query
            ->where('t.demand_side',$mobile)
            ->order('t.create_time','desc')
            ->page($page)
            ->paginate($limit);
        foreach ($list as $v){
            $v['type_icon'] = $request->domain().$v['type_icon'];
        }
        return json(['code' => 0, 'msg' => '获取成功', 'info' => $list]);
    }
    private function getConfigValue($key, $value='value')
    {
        return db('config')
            ->where('key',$key)
            ->value($value);
    }

}
