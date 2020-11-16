<?php
namespace app\common\command;


use app\common\entity\Config;
use app\common\entity\MyWallet;
use app\common\entity\MyWalletLog;
use app\common\entity\User;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;


/**
 * 计算任务二级分销结算
 *
 * 每天01:00定时执行
 */
class DistributionReward extends Command
{
    protected function configure()
    {
        $this->setName('distribution_reward')
            ->setDescription('任务二级分销结算');
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
        $task_users = Db('task_order')->where('status',2)
            ->whereTime('examinetime','yesterday')
            ->column('uid');

        $PlatformSettingLogic = new Config();
        $commissiom_task_1 = $PlatformSettingLogic
            ->where('key','commissiom_task_1')
            ->value('value');
        $commissiom_task_2 = $PlatformSettingLogic
            ->where('key','commissiom_task_2')
            ->value('value');

        $commission = [$commissiom_task_1,$commissiom_task_2];
        Db('user')->whereIn('id',$task_users)

            ->chunk(1,function($data) use($commission){
                foreach ($data as $k => $v){

                        $query = new User();
                        $superiors = [];
                        $query->get_superiors($v['id'],$superiors);
                        //任务总金额
                        $total = Db('task_order')->where('status',2)
                            ->whereTime('examinetime','yesterday')
                            ->where('uid',$v['id'])
                            ->sum('realprice');
//                    dump($total);
                        foreach ($superiors as $key => $item){
                            $is_send = MyWalletLog::where('from',$v['id'])
                                ->where('types',8)
                                ->where('status',1)
                                ->where('money_type',1)
                                ->whereTime('create_time','today')
                                ->find();

                            if(!$is_send) {
                                $commission_money = $total * $commission[$key] / 100;
                                $user = MyWallet::where('uid',$item['id'])
                                    ->find();
                                $real_gold = $user['gold'] - round($commission_money);
                                if($commission_money > 0 && $real_gold >= 0){
                                    $data = [];
                                    $data['number'] = $user['number'] + $commission_money;
                                    $data['gold'] = $real_gold;
                                    MyWallet::where('uid',$item['id'])
                                        ->update($data);
                                    $insert = [];
                                    $insert['uid'] = $item['id'];
                                    $insert['number'] = $commission_money;
                                    $insert['old'] = $user['number'];
                                    $insert['new'] = $data['number'];
                                    $insert['types'] = 8;
                                    $insert['remark'] = '任务直推奖';
                                    $insert['status'] = 1;
                                    $insert['money_type'] = 1;
                                    $insert['from'] = $v['id'];
                                    $insert['createtime'] = time();
                                    Db('my_wallet_log')->insertGetId($insert);
                                    $insertData = [];
                                    $insertData['uid'] = $item['id'];
                                    $insertData['number'] = $commission_money;
                                    $insertData['old'] = $user['gold'];
                                    $insertData['new'] = $data['gold'];
                                    $insertData['remark'] = '任务直推奖扣金豆';
                                    $insertData['types'] = 8;
                                    $insertData['status'] = 2;
                                    $insertData['money_type'] = 2;
                                    $insertData['createtime'] = time();
                                    Db('my_wallet_log')->insertGetId($insertData);

                                }
                            }
                        }

                }
            });
        $output->writeln('任务二级分销结算，执行完成');
    }
}