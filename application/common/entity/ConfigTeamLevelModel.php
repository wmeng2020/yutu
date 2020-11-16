<?php

namespace app\common\entity;

use think\Model;

class ConfigTeamLevelModel extends Model {


    /**
     * @var string 对应的数据表名
     */
    protected $table = 'config_team_level';


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
        $query->push = $data['push'];
        $query->team = $data['team'];
        $query->team_profit = $data['team_profit'];
        $query->level_profit = $data['level_profit'];
        return $query->save();
    }
}
