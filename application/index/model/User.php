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

class User {

    public function checkRegisterOpen() {
        $registerOpen = Config::getValue('register_open');
        if ($registerOpen) {
            return true;
        }
        return false;
    }

    public function checkIp() {
        $ipTotal = Config::getValue('register_ip');
        $request = Request::instance();
        $ip = $request->ip();
        $total = \app\common\entity\User::where('register_ip', $ip)->count();
        if ($ipTotal > $total) {
            return true;
        }
        return false;
    }
    public function doRegister($data) {
        $entity = new \app\common\entity\User();
        $service = new Service();

        $result = UserInviteCode::getUserIdByCode(isset($data['invite_code'])?$data['invite_code']:'');
        $request = Request::instance();
        $parentId = $result?$result:0;
        $entity->mobile = $data['mobile'];
        $entity->nick_name = $data['mobile'];
//        $entity->center_id = isset($data['center_id'])?$data['center_id']:0;
        $entity->password = $service->getPassword($data['password']);
        $entity->trad_password = $service->getPassword($data['password']);
        $entity->register_time = time();
        $entity->level = 0;
        $entity->register_ip = $request->ip();
        $entity->status = \app\common\entity\User::STATUS_DEFAULT;
        $entity->pid = $parentId;
//        ();

        if ($entity->save()) {
            $inviteCode = new UserInviteCode();

            $inviteCode->saveCode($entity->id);
            $entity->save();

            //增加邀请人数
            if($parentId != 0){
                \app\common\entity\User::where('id', $parentId)->setInc('invite_count');
            }
            $give_money = db('config')
                ->where('key','give_money')
                ->value('value');
            $wallet_data = [
                'uid' => $entity->id,
                'number' => $give_money,
                'update_time' => time(),
            ];
            $this->addWallet($wallet_data);
            return true;
        }

        return false;
    }

    //添加钱包
    public function addWallet($data)
    {
        (new MyWallet())->insert($data);
    }
    //注册赠送
    public function sendRegisterReward($user) {
        //判断后台是否开启注册送矿机
         $registerReward = Config::getValue('register_send_produc');
         if (!$registerReward) {
             return true;
         }
        $number = Config::getValue('register_send_product_num');
        if ($number < 1) {
            return true;
        }

        //送矿机
        $model = new UserProduct();
        for ($i = 0; $i < $number; $i++) {
            $result = $model->addInfo($user->id, 1, UserProduct::TYPE_CERTIFICATION);

            if (!$result) {
                Log::addLog(Log::TYPE_PRODUCT, '认证送矿机失败', [
                    'user_id' => $user->id,
                    'mobile' => $user->mobile
                ]);
            }
        }
    }

    /**
     * 得到用户的详细信息
     */
    public function getInfo($id) {
        return \app\common\entity\User::where('id', $id)->find();
    }

    /**
     * 银行卡号 微信号 支付宝账号 唯一
     */
    public function checkMsg($type, $account, $id = '') {
        return \app\common\entity\User::where("$type", $account)->where('id', '<>', $id)->find();
    }

    public function doLogin($account, $password) {
        $user = \app\common\entity\User::where('mobile', $account)->find();
        if (!$user) {
            return '账号或者密码错误';
        }
        $model = new \app\common\service\Users\Service();
        if (!$model->checkPassword($password, $user)) {
            return '账号或者密码错误';
        }
        if ($user->status == \app\common\entity\User::STATUS_FORBIDDED) {
            return '账号已被禁用';
        }
        //保存session
        $identity = new Identity();
        $identity->saveSession($user);
        return true;
    }
    //判断有没有注册过
    public function domobile($mobile) {
        $user = \app\common\entity\User::where('mobile',$mobile)->find();
        if ($user) {
            return false;
        }
        return true;
    }

    //判断有没有注册过
    public function doEmail($mobile) {
        $user = \app\common\entity\User::where('email',$mobile)->find();
        if ($user) {
            return false;
        }
        return true;
    }

    
}
