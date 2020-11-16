<?php
namespace app\common\command;

use app\common\entity\MyWallet;
use app\common\entity\User;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\Db;


/**
 * 自动审核过30秒自动通过
 *
 * 30秒执行一次
 */
class UpdateTaskStatus extends Command
{

    //配置
    protected function configure()
    {

        $this->setName('update_task_status')
            ->addArgument('name', Argument::OPTIONAL, "your name")
            ->addOption('city', null, Option::VALUE_REQUIRED, 'city name')
            ->setDescription('任务自动审核');
    }
    //执行入口
    protected function execute(Input $input, Output $output)
    {

        $output->writeln("任务自动审核开始");
            $time = time();

            $where = [];
            $where['status'] = 1;

            $taskOrder = \db('task_order')->where($where)->select();
            Db::startTrans();
            try {
                foreach ($taskOrder as $item){
                    if($time >= $item['submittime'] + 60 && !empty($item['submittime'])){
                        $data = [];
                        $data = [
                            'status' => 2,
                            'examinetime' => $time,
                        ];
                        $result = Db('task_order')->where(['id'=>$item['id']])->update($data);
                        if($result){
                            $user = User::alias('u')
                                ->field('mw.gold,mw.number')
                                ->leftJoin('my_wallet mw','u.id = mw.uid')
                                ->where('u.id',$item['uid'])
                                ->find();

                            $total_money = $user['number'] + $item['realprice'];
                            $real_gold = $user['gold'] - round($item['realprice']);
                            if($real_gold > 0){
                                $update = [
                                    'gold' => $real_gold,
                                    'number' => $total_money,
                                ];
                                $result = MyWallet::where('uid',$item['uid'])
                                    ->update($update);
                                if(!$result){
                                    throw new \Exception();
                                }

                                $insert = [];
                                $insert['uid'] = $item['uid'];
                                $insert['number'] = $item['realprice'];
                                $insert['old'] = $user['number'];
                                $insert['new'] = $total_money;
                                $insert['remark'] = '任务佣金';
                                $insert['types'] = 5;
                                $insert['status'] = 1;
                                $insert['money_type'] = 1;
                                $insert['create_time'] = time();
                                $result = Db('my_wallet_log')->insertGetId($insert);
                                if (!$result) {
                                    throw new \Exception();
                                }
                                $insertData = [];
                                $insertData['uid'] =$item['uid'];
                                $insertData['number'] = $item['realprice'];
                                $insertData['old'] = $user['gold'];
                                $insertData['new'] = $real_gold;
                                $insertData['remark'] = '任务佣金扣金豆';
                                $insertData['types'] = 5;
                                $insertData['status'] = 2;
                                $insertData['money_type'] = 2;
                                $insertData['create_time'] = time();
                                $result = Db('my_wallet_log')->insertGetId($insertData);
                                if (!$result) {
                                    throw new \Exception();
                                }
                            }
                        }else{
                            throw new \Exception();
                        }
                    }
                }
                Db::commit();
            }catch (\Exception $e){
                Db::rollback();
                $output->writeln("任务自动审核失败");
            }

        $output->writeln("任务自动审核结束");
    }

}
