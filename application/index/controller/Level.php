<?php

namespace app\index\controller;

use app\common\entity\ConfigUserLevelModel;
use app\common\entity\LevelUpLogModel;
use app\common\entity\LootVipModel;
use think\Request;

class Level extends Base
{
   /**
    * 会员升级套餐
    */
   public function getLevelList()
   {
       $user_info = \app\common\entity\User::field('id,mobile,level')
           ->where('id',$this->userId)
           ->find();
       $loot_start = $this->getConfigValue('loot_start_time');
       $open_vip_num = $this->getConfigValue('open_vip_num');
       $loot_vip_num = $this->getConfigValue('loot_vip_num');
       $sell_num = LevelUpLogModel::whereTime('create_time','today')
           ->where('types',1)
           ->count();
       $surplus = $loot_vip_num - $sell_num;
        $list = ConfigUserLevelModel::select();
        $data = [
            'user' => $user_info,
            'loot_start' => $loot_start,
            'surplus' => $surplus,
            'open_vip_num' => $open_vip_num,
            'list' => $list,
        ];
        return json(['code' => 0, 'msg' => '获取成功', 'info' => $data]);
   }
   private function getConfigValue($key, $value='value')
   {
       return db('config')
           ->where('key',$key)
           ->value($value);
   }
   /**
    * 抢购VIP
    */
   public function lootBuyVip(Request $request)
   {
       $level_id = $request->post('level_id');
       if(!$level_id){
           return json(['code' => 1, 'msg' => '请检查参数']);
       }
       $level_info = ConfigUserLevelModel::where('id',$level_id)
           ->find();
       $money = \app\common\entity\MyWallet::where('uid',$this->userId)
           ->value('number');

       $start_time = $this->getConfigValue('loot_start_time');
       if(time() < strtotime($start_time)){
           return json(['code' => 1, 'msg' => '抢购未开始']);
       }

       if($money < $level_info['openin']){
           return json(['code' => 1, 'msg' => '余额不足']);
       }
       //今日已售出名额
       $sell_num = LevelUpLogModel::whereTime('create_time','today')
           ->where('types',1)
           ->count();
       //今日开放名额
       $loot_vip_num = $this->getConfigValue('loot_vip_num');
       if($sell_num >= $loot_vip_num){
           return json(['code' => 1, 'msg' => '名额已售空']);
       }
       //自己等级
       $user_level = \app\common\entity\User::where('id',$this->userId)
           ->value('level');

       if($user_level == 0) {
           $model = new \app\common\entity\MyWallet();
           $model_data = [
               'uid' => $this->userId,
               'num' => $level_info['openin'],
               'remark' => '抢购VIP',
           ];
           $take_money_res = $model->lootVipMoney($model, $model_data);
           if ($take_money_res) {
               //修改会员等级
               $entry = new LevelUpLogModel();
               $entry->lootVipLog([
                   'uid' => $this->userId,
                   'level' => $level_id,
                   'types' => 1,
                   'status' => 2,
               ]);
               return json(['code' => 0, 'msg' => '抢购成功']);
           }
       }else{
           $model = new \app\common\entity\MyWallet();
           $model_data = [
               'uid' => $this->userId,
               'num' => $level_info['openin'],
               'gold' => $level_info['gold_profit'],
               'remark' => '购买VIP',
           ];
           $take_money_res = $model->lootVipMoney($model, $model_data);
           if ($take_money_res) {
               //修改会员等级
               $entry = new LevelUpLogModel();
               $entry->lootVipLog([
                   'uid' => $this->userId,
                   'level' => $level_id,
                   'status' => 2,
               ]);
               return json(['code' => 0, 'msg' => '抢购成功']);
           }
       }
       return json(['code' => 1, 'msg' => '抢购失败']);
   }
   /**
    * 预约VIP
    */
   public function orderVip(Request $request)
   {
       $level_id = $request->post('level_id');
       if(!$level_id){
           return json(['code' => 1, 'msg' => '请检查参数']);
       }
       $level_info = ConfigUserLevelModel::where('id',$level_id)
           ->find();
       $money = \app\common\entity\MyWallet::where('uid',$this->userId)
           ->value('number');
       if($money < $level_info['openin']){
           return json(['code' => 1, 'msg' => '余额不足']);
       }
       //自己等级
       $user_level = \app\common\entity\User::where('id',$this->userId)
           ->value('level');
       if($user_level > $level_id - 1){
           return json(['code' => 1, 'msg' => '您的等级高于该等级，无需预约']);
       }

        $order_res = LootVipModel::where('uid',$this->userId)
            ->where('open_time',null)
            ->find();
       if($order_res){
           return json(['code' => 1, 'msg' => '您已预约']);
       }
       if($user_level > 0){
           return json(['code' => 0, 'msg' => '无需预约']);
       }
       $model = new \app\common\entity\MyWallet();
       $model_data = [
           'uid' => $this->userId,
           'num' => $level_info['openin'],
           'remark' => '预约VIP',
       ];
       $take_money_res = $model->lootVipMoney($model,$model_data);
       if($user_level > 0){
           if($take_money_res){
               //修改会员等级
               $entry = new LevelUpLogModel();
               $entry->lootVipLog([
                   'uid' => $this->userId,
                   'level' => $level_id,
                   'status' => 2,
               ]);
               return json(['code' => 0, 'msg' => '购买成功']);
           }
       }else{
           if($take_money_res){
               //修改会员等级
               $entry = new LootVipModel();
               $loot_data = [
                   'uid' => $this->userId,
                   'level' => $level_id - 1,
                   'price' =>  $level_info['openin'],
                   'gold' =>  $level_info['gold_profit'],
               ];
               $entry->addNew($entry,$loot_data);
               return json(['code' => 0, 'msg' => '预约成功']);
           }
       }
       return json(['code' => 0, 'msg' => '预约失败']);
   }
   /**
    * 查看预约
    */
   public function lookOrder(Request $request)
   {
       $limit = $request->post('limit',15) ;
       $page = $request->post('page',1);
       $list = LootVipModel::alias('lv')
           ->leftJoin('user u','u.id = lv.uid')
           ->field('lv.level,u.mobile,lv.create_time,lv.uid')
           ->where('lv.open_time',null)
           ->order('lv.create_time','asc')
           ->page($page)
           ->paginate($limit);
       $my = 0;
       foreach ($list as $k=>$v){
           $v['mobile'] = substr_replace($v['mobile'],'****',3,4);
           if($v['uid'] == $this->userId){
               $my = $k + 1;
           }
       }
       $result = [
           'my' => $my,
           'result' => $list,
       ];
       return json(['code' => 0, 'msg' => '请求成功','info' => $result]);
   }
    /**
     * 取消预约
     */
    public function delOrderVip(Request $request)
    {
        $info = LootVipModel::where('uid',$this->userId)
            ->where('open_time',null)
            ->find();
        if(!$info) return json(['code' => 1, 'msg' => '请稍后再试']);
        $model = new \app\common\entity\MyWallet();
        $model_data = [
            'uid' => $this->userId,
            'num' => $info['price'],
            'remark' => '取消预约VIP',
        ];
        $take_money_res = $model->delOrderVip($model,$model_data);
        if($take_money_res){
            LootVipModel::where('uid',$this->userId)
                ->where('open_time',null)
                ->delete();
            return json(['code' => 0, 'msg' => '取消成功']);
        }
        return json(['code' => 1, 'msg' => '操作失败']);
    }
}
