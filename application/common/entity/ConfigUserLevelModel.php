<?php

namespace app\common\entity;

use think\Model;

class ConfigUserLevelModel extends Model {


    /**
     * @var string 对应的数据表名
     */
    protected $table = 'config_user_level';


    protected $autoWriteTimestamp = false;

    /**
     * 检查等级大小是否存在
     */
    public static function checkExist($id)
    {
        return self::where('id', $id)->find();
    }
    /**
     * 添加新数据
     */
    public function addNew($query,$data)
    {
        $query->tasks_num = $data['tasks_num'];
        $query->profit = $data['profit'];
        $query->openin = $data['openin'];
        $query->gold_profit = $data['gold_profit'];
        return $query->save();
    }
}
