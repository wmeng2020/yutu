<?php
namespace app\common\command;


use app\common\entity\ConfigUserLevelModel;
use app\common\entity\MyWallet;
use app\common\entity\MyWalletLog;
use app\common\service\Task\Service;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;


/**
 * 计算三级分佣奖励
 *
 * 每天01:00定时执行
 */
class TeamReward extends Command
{
    protected function configure()
    {
        $this->setName('team_reward')
            ->setDescription('任务三级分佣奖励');
    }
    protected function execute(Input $input, Output $output)
    {
        $h = date('H');
//        $h = 1;
        if ($h != 1){
            echo "结算时间未到";
            die;
        }
        set_time_limit(0);
        Db('user')
            ->where('star_level','>',0)
            ->where('level','>',0)
            ->chunk(100,function ($data){
                foreach ($data as $k =>$v){
                    $query = new Service();
                    $query->retailStore($v['id']);
                }
            },'star_level','desc');
        $output->writeln('任务三级分佣奖励，执行完成');
    }

}