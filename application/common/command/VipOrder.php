<?php

namespace app\common\command;


use app\common\entity\LevelUpLogModel;
use app\common\entity\LootVipModel;
use app\common\entity\MyWallet;
use think\console\Command;
use think\console\Input;
use think\console\Output;



/**
 * 会员预约升级  每分钟执行
 *
 */
class VipOrder extends Command
{

    protected function configure()
    {
        //设置参数
        $this->setName('vip_order')
            ->setDescription('会员预约升级');
    }

    protected function execute(Input $input, Output $output)
    {
        set_time_limit(0);
        $guide = Db('config')
            ->where('key','open_vip_num')
            ->value('value');
        Db('loot_vip')
            ->where('open_time',null)
            ->chunk(100,function ($list) use ($guide){
                foreach ($list as $v){
                    $num = Db('loot_vip')
                        ->whereTime('open_time','today')
                        ->count();
                    if($num < $guide){

                        Db('user')->where('id',$v['uid'])
                            ->update([
                                'level' => $v['level'],
                            ]);
                        $user = MyWallet::where('uid',$v['uid'])
                            ->find();
                        MyWallet::where('uid',$v['uid'])
                            ->setInc('gold',$v['gold']);
                        $insertData = [];
                        $insertData['uid'] = $v['id'];
                        $insertData['number'] = $v['gold'];
                        $insertData['old'] = $user['gold'];
                        $insertData['new'] = $user['gold'] + $v['gold'];
                        $insertData['remark'] = '获得VIP增加金豆';
                        $insertData['types'] = 7;
                        $insertData['status'] = 2;
                        $insertData['money_type'] = 2;
                        $insertData['create_time'] = time();
                        Db('my_wallet_log')->insertGetId($insertData);
                        LootVipModel::where('id',$v['id'])
                            ->setField('open_time',time());
                        $add_data = [
                            'uid' => $v['uid'],
                            'level' => $v['level'],
                            'status' => 2,
                        ];
                        $entry = new LevelUpLogModel();
                        $entry->addNew($entry,$add_data);
                    }


                }
            },'create_time','asc');
        $output->writeln('会员预约升级，执行完成');

    }

}