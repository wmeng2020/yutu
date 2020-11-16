<?php

namespace app\common\command;

use app\common\entity\ConfigTeamLevelModel;
use app\common\entity\User;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;



/**
 * 计算星级达人升级
 *
 * 3小时执行一次
 */
class TeamUpgrade extends Command
{

    //配置
    protected function configure()
    {

        $this->setName('team_upgrade')
            ->setDescription('计算团队升级');
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return int|void|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function execute(Input $input, Output $output)
    {
        set_time_limit(0);
        $PlatformSettingLogic = new ConfigTeamLevelModel();
        $mse = $PlatformSettingLogic;

        $start_time=strtotime(date("Y-m-d",time()));
        //当天结束之间
        $end_time=$start_time+60*60*24;
        Db('user')->where('id', '>', 0)
            ->field('id,star_upgrade_time')
            ->whereTime('star_upgrade_time','not between',[$start_time,$end_time])
            ->chunk(100,function ($all_user) use ($mse){
                foreach ($all_user as $item) {
                    $push = 0;
                    $team = false;
                    $query = new User();
                    //直推人数
                    $son = $item['invite_count'];

                    for ($x=1; $x<=8; $x++) {
                        $lv_push = $mse
                            ->where('id',$x)
                            ->value('team');
                        if($son >= $lv_push){
                            $push = $x;
                        }
                    }
//                    dump($push);
//                    die;
                    if($push){
                        //团队人数
                        $teamNum = $query->getTeamNum($item['id']);
                        $lv_push = $mse
                            ->where('id',$push)
                            ->value('team');
                        if($teamNum >= $lv_push){
                            $team = true;
                        }
                        if(!$team && $push > 1){
                            for ($y=1;$y<=$push-1;$y++){
                                $push1 = $push - $y;
                                $lv_push = $mse
                                    ->where('id',$push)
                                    ->value('team');
                                if($teamNum >= $lv_push){
                                    $team = true;
                                    break;
                                }
                            }
                        }
                    }
//                    dump($mse['lv'.$push.'_team']);
//                    dump($team);
//                    dump($push1);
//                    die;
                    if($team){
                        Db('user')
                            ->where('id',$item['id'])
                            ->update([
                                'star_level' => $push1
                            ]);
                    }
                    Db('user')
                        ->where('id',$item['id'])
                        ->update([
                            'star_upgrade_time' => time()
                        ]);
                    dump($item['id'].'完成');
                }
            },'star_upgrade_time','desc');

        $output->writeln('计算团队升级，执行完成');

    }



}
