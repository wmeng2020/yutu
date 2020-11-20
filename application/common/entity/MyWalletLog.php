<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class MyWalletLog extends Model {


    /**
     * @var string 对应的数据表名
     */
    protected $table = 'my_wallet_log';

    protected $createTime = 'create_time';

    protected $autoWriteTimestamp = false;

    //获取状态
    public function getStatus($status)
    {
        switch ($status) {
            case 1:
                return '+';
            case 2:
                return '-';
            default:
                return '';
        }
    }
    //获取所有类型
    public static function getAllTypes()
    {
        return [
            1 => '充值',
            2 => '提现',
            3 => '提现拒绝，退还',
            4 => '转账',
            5 => '任务佣金',
            6 => '下级分佣',
            7 => '代理佣金',

        ];
    }
    //获取所有账户类型
    public static function getAllMoneyTypes()
    {
        return [
            1 => '保证金账户',
            2 => '佣金账户',
            3 => '代理账户',
        ];
    }
    //获取账户类型
    public function getMoneyType($type)
    {
        switch ($type) {
            case 1:
                return '保证金账户';
            case 2:
                return '佣金账户';
            case 3:
                return '代理账户';
            default:
                return '';
        }
    }
    //获取类型
    public function getType($type)
    {
        switch ($type) {
            case 1:
                return '充值';
            case 2:
                return '提现';
            case 3:
                return '提现拒绝，退还';
            case 4:
                return '转账';
            case 5:
                return '任务佣金';
            case 6:
                return '下级分佣';
            case 7:
                return '代理佣金';
            default:
                return '';
        }
    }

    //添加数据
    public function addNew($query,$data)
    {
        $query->uid = $data['uid'];
        $query->number = $data['number'];
        $query->old = $data['old'];
        $query->new = $data['new'];
        $query->remark = $data['remark'];
        if(isset($data['from'])){
            $query->from = $data['from'];
        }
        $query->types = $data['types'];
        $query->status = $data['status'];
        $query->create_time = time();
        return $query->save();
    }
    //添加捐赠余额支付记录
    public function addLog($uid,$num)
    {
        $walletInfo = MyWallet::where('uid',$uid)->find();
        $query = new self();
        $data = [
            'uid' => $uid,
            'number' => $num,
            'old' => $walletInfo['number'],
            'new' => $walletInfo['number'] - $num,
            'remark' => '捐赠',
            'types' => 2,
            'status' => 2,
        ];
        $result = $query->addNew($query,$data);
        if($result){
            return true;
        }
    }
    //添加购买门票余额支付记录
    public function addTicketLog($uid,$num)
    {
        $walletInfo = MyWallet::where('uid',$uid)->find();
        $query = new self();
        $data = [
            'uid' => $uid,
            'number' => $num,
            'old' => $walletInfo['number'],
            'new' => $walletInfo['number'] - $num,
            'remark' => '购买门票',
            'types' => 5,
            'status' => 2,
        ];
        $result = $query->addNew($query,$data);
        if($result){
            return true;
        }
    }
    //添加提现余额支付记录
    public function addRechargeLog($uid,$num,$types,$address)
    {
        $walletInfo = MyWallet::where('uid',$uid)->find();
        $query = new self();
        $data = [
            'uid' => $uid,
            'number' => $num,
            'old' => $walletInfo['number'],
            'new' => $walletInfo['number'] - $num,
            'remark' => '提现申请扣除',
            'from' => $this->getTypes($types).','.$address,
            'types' => 4,
            'status' => 2,
        ];
        $result = $query->addNew($query,$data);
        if($result){
            return true;
        }
    }
    //添加提现失败退回余额记录
    public function addRefuseRechargeLog($uid,$num)
    {
        $walletInfo = MyWallet::where('uid',$uid)->find();
        $query = new self();
        $data = [
            'uid' => $uid,
            'number' => $num,
            'old' => $walletInfo['number'],
            'new' => $walletInfo['number'] + $num,
            'remark' => '提现被拒退回',
            'types' => 6,
            'status' => 1,
        ];
        $result = $query->addNew($query,$data);
        if($result){
            return true;
        }
    }
    //添加转出余额支付记录
    public function addOutTransferLog($uid,$num,$from)
    {
        $walletInfo = MyWallet::where('uid',$uid)->find();
        $query = new self();
        $data = [
            'uid' => $uid,
            'number' => $num,
            'old' => $walletInfo['number'],
            'new' => $walletInfo['number'] - $num,
            'remark' => '转出',
            'from' => $from,
            'types' => 7,
            'status' => 2,
        ];
        $result = $query->addNew($query,$data);
        if($result){
            return true;
        }
    }
    //添加转入余额支付记录
    public function addEnterTransferLog($uid,$num,$from)
    {
        $walletInfo = MyWallet::where('uid',$uid)->find();
        $query = new self();
        $data = [
            'uid' => $uid,
            'number' => $num,
            'old' => $walletInfo['number'],
            'new' => $walletInfo['number'] + $num,
            'remark' => '转入',
            'from' => $from,
            'types' => 7,
            'status' => 1,
        ];
        $result = $query->addNew($query,$data);
        if($result){
            return true;
        }
    }

}
