<?php

namespace app\common\command;


use app\common\entity\ConfigTeamLevelModel;
use app\common\entity\MyWallet;
use app\common\entity\MyWalletLog;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\Db;


/**
 * 任务平级奖  02：00 执行
 * 
 */
class OneClick extends Command
{

    protected function configure()
    {
        //设置参数
        $this->setName('one_click')
             ->setDescription('任务平级奖');
    }

    protected function execute(Input $input, Output $output)
    {
        $h = date('H');
        $h = 2;
        if ($h != 2){
            echo "结算时间未到";
            die;
        }
        set_time_limit(0);
        $PlatformSettingLogic = new ConfigTeamLevelModel();

        Db('user')
            ->where('star_level','>',0)
            ->chunk(100,function ($data) use ($PlatformSettingLogic){
                foreach ($data as $k =>$v){
                    //下级数组
                    // $v['id'] = 121;
                    //是否有直属下级平级
                    $downAlikeId = $this->alike($v['id'],$v['star_level']);
                    if($downAlikeId){
                        $rate = $PlatformSettingLogic->where('id',$v['star_level'])->value('level_profit');
                        $insert = [];
                        $insert['remark'] = $downAlikeId.'任务佣金平级奖励';
                        //获取团队业绩
                        $team_total_user = $this->getChildsInfoTotal($downAlikeId);
                        $team_total = $this->getTeamTotal($team_total_user);
//                        dump($team_total);
//                        dump($rate);
//                        dump($insert['name']);
//                        die;
                        $commission_money = $team_total * $rate / 100;
                        $is_send = MyWalletLog::where('uid',$v['id'])
                            ->where('types',10)
                            ->where('status',1)
                            ->whereTime('create_time','today')
                            ->find();
                        $user = MyWallet::where('uid',$v['id'])
                            ->find();
                        $real_gold = $user['gold'] - round($commission_money);
                        if($commission_money > 0 && $real_gold >= 0  && !$is_send){
                            $data = [];
                            $data['number'] = $user['number'] + $commission_money;
                            $data['gold'] = $real_gold;
                            MyWallet::where('uid',$v['id'])
                                ->update($data);
                            $insert['uid'] = $v['id'];
                            $insert['number'] = $commission_money;
                            $insert['old'] = $user['number'];
                            $insert['new'] = $data['number'];
                            $insert['types'] = 10;
                            $insert['status'] = 1;
                            $insert['money_type'] = 1;
                            $insert['createtime'] = time();
                            Db('my_wallet_log')->insertGetId($insert);
                            $insertData = [];
                            $insertData['uid'] = $v['id'];
                            $insertData['number'] = $commission_money;
                            $insertData['old'] = $user['gold'];
                            $insertData['new'] = $data['gold'];
                            $insertData['remark'] = '平级奖扣金豆';
                            $insertData['types'] = 10;
                            $insertData['status'] = 2;
                            $insertData['money_type'] = 2;
                            $insertData['createtime'] = time();
                            Db('my_wallet_log')->insertGetId($insertData);
                        }
                    }
                }
            },'star_level','desc');
        $output->writeln('任务平级奖，执行完成');
        
    }
    /**
     * 获取同级下级
     */
    protected function alike($uid,$level)
    {
        $cc = Db('user')
//            ->field('id,star_level')
            ->where('star_level',$level)
            ->where('pid', $uid)
            ->orderRaw('rand()')
            ->value('id');
        return $cc;
    }
    /**
     * 获取下级放在一个数组里
     */
    public function getChildsInfoTotal($uid, $num = 0, &$childs = '', &$level = 0)
    {
        $ids = [];
        if ($num) {
            if ($level == $num) {
                return $childs;
            }
        }
        $child = Db('user')->whereIn('pid', $uid)->field('id,pid')->select();
        if ($child) {
            $level++;
            foreach ($child as $v) {
                $childs .= $v['id'] . ',';
                array_push($ids, $v['id']);
            }
            $ids = array_unique($ids);

            return $this->getChildsInfoTotal($ids, $num, $childs, $level);
        }
        return $childs;
    }
    /**
     * 获取团队业绩
     */
    public function getTeamTotal($arr)
    {
        $total = Db('task_order')
            ->whereIn('uid',$arr)
            ->where('status',2)
//            ->whereTime('examinetime','between',[1600963200,1601049600])
            ->whereTime('examinetime','yesterday')
            ->sum('realprice');
        return $total;
    }
}