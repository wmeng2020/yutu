<?php

namespace app\common\service\Task;


use app\common\entity\Config;
use app\common\entity\ConfigTeamLevelModel;
use app\common\entity\ManageUser;
use app\common\entity\MyWallet;
use app\common\entity\MyWalletLog;
use app\common\entity\TaskModel;
use app\common\entity\TaskOrderModel;
use app\common\entity\User;
use think\Db;
use think\Exception;

class Service
{
    /**
     * 完成任务
     */
    public function doTask($uid)
    {
        if(!$uid) return false;
        $this->doFirst($uid);
    }
    public function doFirst($uid)
    {
        //一共可领取任务数
        $level = \app\common\entity\User::where('id',$uid)
            ->value('star_level');
        if($level > 0){
            $tasks_num = ConfigTeamLevelModel::where('id',$level)
                ->value('task_num');
        }else{
            $tasks_num = 0;
        }
        //已领取任务数
        $has_task_num = TaskOrderModel::where('uid',$uid)
            ->whereTime('receivetime','today')
            ->count();
//        dump($uid);
//        dump($has_task_num);
//        dump($tasks_num);
        if($has_task_num >= $tasks_num){
            $deposit =  Db('deposit')
                ->where('uid',$uid)
                ->where('status',1)
                ->find();
            //修改托管状态
            Db('deposit')
                ->where('uid',$uid)
                ->where('status',1)
                ->update([
                'status' => 2
            ]);
            //结算任务佣金
            $autoTaskTotal = TaskOrderModel::where('uid',$uid)
                ->whereTime('receivetime','today')
                ->where('types',2)
                ->where('status',2)
                ->sum('realprice');
            $this->sendTaskTotal($uid,$autoTaskTotal);
            //记录分佣表
            Db('reward_user')->insert([
                'uid' => $uid,
                'create_time' => time(),
            ]);
            return json(['code' => 1, 'msg' => '可接任务数量不足']);
        }
        $surplus = $tasks_num - $has_task_num;
        $number = (int)Config::where('key','deposit_space')
            ->value('value');
        if($number > $surplus){
            $realNum = $surplus;
        }else{
            $realNum = $number;
        }

        for ($x=1; $x<=$realNum; $x++) {
            $data[$x] = $this->doOther($uid);
        }
        $model = new TaskOrderModel();
        $model->insertAll($data);

    }

    /**
     * 建立任务数据
     */
    public function doOther($uid)
    {
        $task_info = TaskModel::where('status',1)
            ->where('task_num','>',0)
            ->orderRaw('rand()')
            ->find();
        $data = [
            'task_id' => $task_info['id'],
            'uid' => $uid,
            'realprice' => $task_info['task_price'],
            'status' => 2,
            'types' => 2,
            'receivetime' => time(),
            'submittime' => time(),
            'examinetime' => time(),
        ];
        return $data;
    }

    /**
     * 结算任务佣金
     */
    public function sendTaskTotal($uid,$autoTaskTotal)
    {
        $model = new MyWallet();
        $data = [
            'uid' => $uid,
            'number' => $autoTaskTotal,
        ];
        $model->taskMoney($model,$data);
    }
    public function addLog($task_info,$uid)
    {
        $user = MyWallet::where('uid',$uid)->find();
        $total_money = $task_info['task_price'] + $user['number'];
        $insert = [];
        $insert['uid'] = $uid;
        $insert['number'] = $task_info['task_price'];
        $insert['old'] = $user['number'];
        $insert['new'] = $total_money;
        $insert['remark'] = '任务佣金';
        $insert['types'] = 5;
        $insert['status'] = 1;
        $insert['money_type'] = 2;
        $insert['create_time'] = time();
        $result = Db('my_wallet_log')->insertGetId($insert);
    }

    /**
     * 分销
     */
    public function retailStore($uid)
    {
        $user = User::where('id',$uid)->find();
        if($user){
            if($user['star_level'] > 0){
                $config = ConfigTeamLevelModel::where('id',$user['star_level'])
                    ->find();
                //已做任务
                $has_task = TaskOrderModel::where('uid',$uid)
                    ->where('status',2)
                    ->whereTime('examinetime','today')
                    ->count();

                if($has_task == $config['task_num']){
                    //三级分销
                   $prizeData = $this->findPrize($uid,$has_task);
                   if($prizeData){
                       foreach ($prizeData as $v){
                           $data = [
                                'number' => $v['prize'],
                                'uid' => $v['uid'],
                                'from' => $uid,
                           ];
                           $this->sendRetailStore($data);
                       }
                   }
                   //代理佣金
                    $agentData = $this->findAgent($uid,$has_task);
                    if($agentData){
                        foreach ($agentData as $val){
                            $data = [
                                'number' => $val['prize'],
                                'uid' => $val['uid'],
                                'from' => $uid,
                            ];
                            $this->sendAgentStore($data);
                        }
                    }
                }else{
                    return '今日任务未全部完成';
                }
            }
        }else{
            return '未购买保证金套餐';
        }
    }
    /**
     * 查询奖励人和奖励金额
     */
    public function findPrize($uid,$money)
    {
        //查询上三级
        $model = new User();
        $upIdArr = [];
        $model->get_superiors($uid,$upIdArr);
        if($upIdArr){
            $prize = [];
            foreach ($upIdArr as $k=>$v){
                if($v['star_level'] > 0){
                    if($k == 0){
                        $prize[] = [
                            'prize' => $money * $v['one_level'] * 0.01,
                            'uid' => $v['id'],
                        ];
                    }
                    if($k == 1){
                        $prize[] = [
                            'prize' => $money * $v['two_level'] * 0.01,
                            'uid' => $v['id'],
                        ];
                    }
                    if($k == 2){
                        $prize[] = [
                            'prize' => $money * $v['three_level'] * 0.01,
                            'uid' => $v['id'],
                        ];
                    }
                }
            }
            return $prize;
        }
    }
    /**
     * 查询奖励代理人和奖励金额
     */
    public function findAgent($uid,$money=100)
    {
        //查询上十二层级
        $model = new User();
        $upIdArr = $model->getParents($uid,12);
        $rate = Config::where('key','agent_profit')
            ->value('value');
//        dump($upIdArr);
        $prize = [];
        foreach ($upIdArr as $v){
            $is_agent = ManageUser::where('left_uid',$v)->find();
            if($is_agent){
                $prize[] = [
                    'prize' => $money * $rate * 0.01,
                    'uid' => $v,
                ];
            }
        }
        return $prize;
    }

    /**
     *  发放下级任务佣金
     */
    public function sendRetailStore($data)
    {
        $oldInfo = MyWallet::where('uid',$data['uid'])->find();
        Db::startTrans();
        try {
            $edit_data['number'] = $oldInfo['number'] + $data['number'];
            $old_number = $oldInfo['number'];
            $edit_data['update_time']  = time();
            $res = MyWallet::where('uid',$data['uid'])->update($edit_data);
            if (!$res) {
                throw new Exception();
            }
            $create_data = [
                'uid' => $data['uid'],
                'number' => $data['number'],
                'old' => $old_number,
                'new' => $old_number + $data['number'],
                'remark' => '下级任务佣金',
                'from' => $data['from'],
                'types' => 6,
                'status' => 1,
                'money_type' => 2,
                'create_time' => time(),
            ];
            $res = MyWalletLog::insert($create_data);
            if (!$res) {
                throw new Exception();
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
        }
    }

    public function sendAgentStore($data)
    {
        $oldInfo = MyWallet::where('uid',$data['uid'])->find();
        Db::startTrans();
        try {
            $edit_data['agent'] = $oldInfo['agent'] + $data['number'];
            $old_number = $oldInfo['number'];
            $edit_data['update_time']  = time();
            $res = MyWallet::where('uid',$data['uid'])->update($edit_data);
            if (!$res) {
                throw new Exception();
            }
            $create_data = [
                'uid' => $data['uid'],
                'number' => $data['number'],
                'old' => $old_number,
                'new' => $old_number + $data['number'],
                'remark' => '代理任务佣金',
                'from' => $data['from'],
                'types' => 7,
                'status' => 1,
                'money_type' => 3,
                'create_time' => time(),
            ];
            $res = MyWalletLog::insert($create_data);
            if (!$res) {
                throw new Exception();
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
        }
    }
}