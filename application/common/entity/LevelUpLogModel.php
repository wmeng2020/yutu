<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class LevelUpLogModel extends Model {


    /**
     * @var string 对应的数据表名
     */
    protected $table = 'level_up_log';

    protected $createTime = 'create_time';

    protected $autoWriteTimestamp = false;

    /**
     * 检查等级大小是否存在
     */
    public static function checkExist($id)
    {
        return self::where('id', $id)->find();
    }
    /**
     * 抢购成功，添加升级记录
     */
    public function lootVipLog($data)
    {

        $res = $this->addNew($this,$data);
        if($res){
            $level_info = ConfigUserLevelModel::where('id',$data['level'])
                ->find();
            Db::startTrans();
            try {
               User::where('id', $data['uid'])
                    ->update([
                        'level' => (int)$data['level'] - 1,
                    ]);
                $result = MyWallet::where('uid', $data['uid'])
                    ->setInc('gold', $level_info['gold_profit']);
                if(!$result) {
                    Db::rollback();
                    return false;
                }
                Db::commit();
                return true;
            }catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return false;
            }
        }
    }
    /**
     * 添加新数据
     */
    public function addNew($query,$data)
    {
        $query->uid = $data['uid'];
        $query->level = $data['level'];
        if(!isset($data['status'])){
            $query->status = 1;
        }else{
            $query->status = $data['status'];
        }
        if(!isset($data['types'])){
            $query->types = 0;
        }else{
            $query->types = $data['types'];
        }
        if(!isset($data['create_time'])){
            $query->create_time = time();
        }else{
            $query->create_time = $data['create_time'];
        }
        return $query->save();
    }
}
