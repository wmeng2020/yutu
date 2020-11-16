<?php
namespace app\common\command;


use app\common\entity\ConfigUserLevelModel;
use app\common\entity\MyWallet;
use app\common\entity\MyWalletLog;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;


/**
 * 计算团队奖平级奖
 *
 * 每天02:00定时执行
 */
class TeamReward extends Command
{
    protected function configure()
    {
        $this->setName('team_reward')
            ->setDescription('任务团队奖');
    }
    protected function execute(Input $input, Output $output)
    {
        $h = date('H');
//        $h = 1;
        if ($h != 2){
            echo "结算时间未到";
            die;
        }
        set_time_limit(0);

        $user_level =  new ConfigUserLevelModel();
        Db('user')
            ->where('star_level','>',0)
            ->chunk(100,function ($data) use ($user_level){
                $rate = 0;
                foreach ($data as $k =>$v){
                    $rate = $user_level
                        ->where('id',$v['star_level'])
                        ->value('team_profit');


                    //是否已发放
                    $is_send = MyWalletLog::where('uid',$v['id'])
                        ->where('types',9)
                        ->where('status',1)
                        ->where('money_type',1)
                        ->whereTime('create_time','today')
                        ->find();
                    //获取团队业绩
                    $team_total_user = $this->getChildsInfoTotal($v['id']);
                    $team_total = $this->getTeamTotal($team_total_user);
                    $commission_money = $team_total * $rate / 100;
                    $user = MyWallet::where('uid',$v['id'])
                        ->find();
                    $real_gold = $user['gold'] - round($commission_money);
                    if($commission_money > 0 && !$is_send && $real_gold >= 0){
                        $data = [];
                        $data['number'] = $v['user_money'] + $commission_money;
                        $data['gold'] = $real_gold;
                        MyWallet::where('id',$v['id'])
                            ->update($data);
                        $insert['uid'] = $v['id'];
                        $insert['number'] = $commission_money;
                        $insert['old'] = $user['number'];
                        $insert['new'] = $data['number'];
                        $insert['remark'] = '任务团队奖';
                        $insert['types'] = 9;
                        $insert['status'] = 1;
                        $insert['money_type'] = 1;
                        $insert['createtime'] = time();
                        Db('my_wallet_log')->insertGetId($insert);
                        $insertData = [];
                        $insertData['uid'] = $v['id'];
                        $insertData['number'] = $commission_money;
                        $insertData['old'] = $user['gold'];
                        $insertData['new'] = $data['gold'];
                        $insertData['remark'] = '任务团队奖扣金豆';
                        $insertData['types'] = 9;
                        $insertData['status'] = 2;
                        $insertData['money_type'] = 2;
                        $insertData['createtime'] = time();
                        Db('my_wallet_log')->insertGetId($insertData);
                    }
                }
            },'star_level','desc');
        $output->writeln('任务团队奖，执行完成');
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
        $child = DB('user')->whereIn('pid', $uid)->field('id,pid')->select();
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
        $total = DB('task_order')
            ->whereIn('uid',$arr)
            ->where('status',2)
//            ->whereTime('examinetime','between',[1600963200,1601049600])
            ->whereTime('examinetime','yesterday')
            ->sum('realprice');
        return $total;
    }
}