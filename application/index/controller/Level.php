<?php

namespace app\index\controller;

use app\common\entity\ConfigTeamLevelModel;
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
       $user_info = \app\common\entity\User::alias('u')
           ->field('u.id,u.mobile,l.level_name,u.avatar')
           ->leftJoin('config_user_level l','l.id = u.level')
           ->where('u.id',$this->userId)
           ->find();

        $list = ConfigTeamLevelModel::select();
        $data = [
            'user' => $user_info,
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
    * 购买保证金套餐
    */
   public function buyVip(Request $request)
   {
       $id = $request->post('id');
       if(!$id){
           return json(['code' => 1, 'msg' => '请检查参数']);
       }
       $team_info = ConfigTeamLevelModel::where('id',$id)
           ->find();
       $money = \app\common\entity\MyWallet::where('uid',$this->userId)
           ->value('number');

       if($money < $team_info['assure_money']){
           return json(['code' => 1, 'msg' => '余额不足']);
       }
       //自己等级
       $star_level = \app\common\entity\User::where('id',$this->userId)
           ->value('star_level');
       if($star_level >= $id){
           return json(['code' => 1, 'msg' => '您已购买，无需重复购买']);
       }

       $model = new \app\common\entity\MyWallet();
       $model_data = [
           'uid' => $this->userId,
           'num' => $team_info['assure_money'],
           'remark' => '购买保证金套餐',
       ];
       $take_money_res = $model->lootVipMoney($model,$model_data);

       if($take_money_res){
           //修改会员等级
           $entry = new LevelUpLogModel();
           $entry->lootVipLog([
               'uid' => $this->userId,
               'level' => $id,
               'status' => 1,
           ]);
           return json(['code' => 0, 'msg' => '购买成功']);
       }

       return json(['code' => 0, 'msg' => '预买失败']);
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
    /**
     * 购买最新信息
     */
    public function newList(Request $request)
    {
        $limit = $request->param('limit',5);
        $list = LevelUpLogModel::alias('l')
            ->field('u.avatar,u.mobile,c.assure_money,l.*')
            ->leftJoin('user u','u.star_level = l.level')
            ->leftJoin('config_team_level c','c.id = l.level')
            ->limit($limit)
            ->select();
        foreach ($list as $v){
            $v['mobile'] = substr_replace($v['mobile'],'****',3,4);
        }
        return json(['code' => 0, 'msg' => '请求成功','info' => $list]);
    }
}
