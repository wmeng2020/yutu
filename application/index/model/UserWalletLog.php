<?php

namespace app\index\model;

use app\common\entity\Config;
use app\common\entity\Log;
use app\common\entity\MyWallet;
use app\common\entity\Orders;
use app\common\entity\Allaward;
use app\common\entity\UserInviteCode;
use app\common\entity\UserLevelConfigModel;
use app\common\entity\UserProduct;
use app\common\service\Users\Cache;
use app\common\service\Users\Identity;
use app\common\service\Users\Service;
use think\Db;
use think\Request;
use app\common\entity\Category;
use app\common\entity\Areaconfig;
use app\common\entity\Cityconfig;
use app\common\entity\Fansconfig;
use app\common\entity\Masterconfig;
use app\common\model\UserCurrency;

class UserWallet {

    public function getUserWallet($uid,$page = 1,$psize = 10){
        $UserWallet = Db::name('user_wallet_log')->where('uid',$uid)->order('id desc')->page($page,$psize)->select();
        foreach ($UserWallet as &$value){
            if($value['op_type'] == 1){
                $op_type = "消费";
            }elseif ($value['op_type'] == 2){
                $op_type = "充值";
            }
            $value['createtime'] = date('Y-m-d H:i',$value['createtime']);
            $value['change_money'] = $value['op_type'] == 1 ? "-".$value['change_money']: "+".$value['change_money'];
            $value['op_type_text'] = $op_type;
        }
        return $UserWallet;
    }

    public function setUserWalletLog($uid,$credit,$types,$op_type,$money,$remarks){
        $user_credit = Db::name('user_wallet')->where('uid', $uid)->value($credit);
        var_dump($user_credit);die;
        $data = [
            'uid'=>$uid,
            'change_money'=>$money,
            'original_money'=>$user_credit,
            'after_change_money'=>sprintf("%.2f",($user_credit + $money)),
            'types'=>$types,
            'op_type'=>$op_type,
            'remarks'=>$remarks,
            'createtime'=>time(),
        ];
        $result = Db::name('user_wallet_log')->insert($data);
        return $result;
    }



}
