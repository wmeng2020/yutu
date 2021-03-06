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
use app\common\PHPMailer\Exception;
use app\common\service\Users\Cache;
use app\common\service\Users\Identity;
use app\common\service\Users\Service;
use app\index\model\UserWalletLog;
use think\Db;
use think\Request;
use app\common\entity\Category;
use app\common\entity\Areaconfig;
use app\common\entity\Cityconfig;
use app\common\entity\Fansconfig;
use app\common\entity\Masterconfig;
use app\common\model\UserCurrency;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\LabelAlignment;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Response\QrCodeResponse;



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
        Db::startTrans();
        try {
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
                $result = $inviteCode->saveCode($entity->id);
                $entity->save();
                if(!$result){
                    throw new Exception();
                }

                //??????????????????
                if($parentId != 0){
                    $result = $this->UpgradeCommssionLevel($parentId);
                    if(!$result){
                        throw new Exception();
                    }
                    $result = $this->inviteGive($parentId);

                    if(!$result){
                        throw new Exception();
                    }
                    \app\common\entity\User::where('id', $parentId)->setInc('invite_count');

                    //??????????????????
                }
                //            $give_money = db('config')
                //                ->where('key','give_money')
                //                ->value('value');
                //            $wallet_data = [
                //                'uid' => $entity->id,
                //                'number' => $give_money,
                //                'update_time' => time(),
                //            ];
                $wallet_data = [
                    'uid'=>$entity->id,
                    'createtime'=>time(),
                ];

                //            $bounty_list_1 = [
                //                'uid'=>$entity->id,
                //                'game_type'=>1,
                //                'createtime'=>time(),
                //            ];
                //            $bounty_list_2 = [
                //                'uid'=>$entity->id,
                //                'game_type'=>2,
                //                'createtime'=>time(),
                //            ];
                //            //???????????????
                //            Db::name('bounty_list')->insert($bounty_list_1);
                //            Db::name('bounty_list')->insert($bounty_list_2);
                //??????????????????
                $result = $this->addUserWallet($wallet_data);

                if(!$result){
                    throw new Exception();
                }
                Db::commit();
                return true;
            }
        }catch (Exception $e){
            Db::rollback();
            return false;
        }

        return false;
    }

    /**
     * ??????????????????
     * @param $uid
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function inviteGive($uid){
        $user = Db::name('user')->where('id',$uid)->field('id,invite_count')->find();
        if(($user['invite_count'] + 1) % 3 == 0){
            $config = Db::name('config')
                ->alias('c')
                ->leftJoin('game_ticket t','t.id = c.value')
                ->where('c.key','Invite_give')
                ->field('t.id as tid,t.price')
                ->find();
            $times = time();
            if(!empty($config)){
                $data = [
                    'uid'=>$uid,
                    'ticket_id'=>$config['tid'],
                    'price'=>$config['price'],
                    'status'=>0,
                    'is_give'=>1,
                    'createtime'=>$times,
                    'orvertime'=>$times + (86400 * 30),
                ];
                $log = [
                    'uid'=>$uid,
                    'ticket_id'=>$config['tid'],
                    'num'=>1,
                    'op_type'=>2,
                    'createtime'=>time(),
                ];
                $result = Db::name('user_give_ticket_log')->insert($log);
                if(!$result){
                    return false;
                }
                $result = Db::name('user_game_ticket')->insert($data);
                if(!$result){
                    return false;
                }

            }
        }
        return true;
    }

    /**
     * ????????????????????????
     * @param $uid
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function UpgradeCommssionLevel($uid){
        $user = Db::name('user')->where('id',$uid)->field('id,commission_level')->find();
        $commission_level = Db::name('commission_level')->select();
        $parent_team = $this->getTeam($uid);
        $parent_team_num = count($parent_team);
        foreach ($commission_level as $value){
            if($parent_team_num >= $value['team_num']){
                $level = $value['id'];
            }
        }
        if($level > $user['commission_level']){
            $result = Db::name('user')->where('id',$uid)->update(['commission_level'=>$level]);
            if(!$result){
                return false;
            }
        }
        return true;

    }


    public function addUserWallet($data){
        return Db::name('user_wallet')->insert($data);
    }

    //????????????
    public function addWallet($data)
    {
        (new MyWallet())->insert($data);
    }
    //????????????
    public function sendRegisterReward($user) {
        //???????????????????????????????????????
        $registerReward = Config::getValue('register_send_produc');
        if (!$registerReward) {
            return true;
        }
        $number = Config::getValue('register_send_product_num');
        if ($number < 1) {
            return true;
        }

        //?????????
        $model = new UserProduct();
        for ($i = 0; $i < $number; $i++) {
            $result = $model->addInfo($user->id, 1, UserProduct::TYPE_CERTIFICATION);

            if (!$result) {
                Log::addLog(Log::TYPE_PRODUCT, '?????????????????????', [
                    'user_id' => $user->id,
                    'mobile' => $user->mobile
                ]);
            }
        }
    }

    /**
     * ???????????????????????????
     */
    public function getInfo($id) {
        return \app\common\entity\User::where('id', $id)->find();
    }

    /**
     * ???????????? ????????? ??????????????? ??????
     */
    public function checkMsg($type, $account, $id = '') {
        return \app\common\entity\User::where("$type", $account)->where('id', '<>', $id)->find();
    }

    public function doLogin($account, $password) {
        $user = \app\common\entity\User::where('mobile', $account)->find();
        if (!$user) {
            return '????????????????????????';
        }
        $model = new \app\common\service\Users\Service();
        if (!$model->checkPassword($password, $user)) {
            return '????????????????????????';
        }
        if ($user->status == \app\common\entity\User::STATUS_FORBIDDED) {
            return '??????????????????';
        }
        //??????session
        $identity = new Identity();
        $identity->saveSession($user);
        return true;
    }
    //????????????????????????
    public function domobile($mobile) {
        $user = \app\common\entity\User::where('mobile',$mobile)->find();
        if ($user) {
            return false;
        }
        return true;
    }

    //????????????????????????
    public function doEmail($mobile) {
        $user = \app\common\entity\User::where('email',$mobile)->find();
        if ($user) {
            return false;
        }
        return true;
    }

    /**
     * @param $uid ??????ID
     * @param $credit ????????????
     * @param $types  ?????????1????????????2????????????3????????????4??????????????????5??????????????????????????????????????????6????????????????????????????????????7???????????????????????????????????????
     * @param $op_type ???1??????2??????
     * @param $money ????????????
     * @param $remarks ??????
     * @return array
     * @throws \think\Exception
     */
    public function setUserWallet($uid,$credit,$types,$op_type,$money,$remarks){
        $user_credit = $this->getUserWallet($uid,$credit);
        $money = sprintf("%.2f",$money);
        if($op_type == 1){
            $user_orther_money = sprintf("%.2f",$user_credit - $money);
            if($user_orther_money < 0){
                return ['code'=>400,'msg'=>'??????????????????'];
            }
        }
        $UserWallet = new UserWalletLog();
        $result = $UserWallet->setUserWalletLog($uid,$credit,$types,$op_type,$money,$remarks);
        if($result){
            if($op_type == 1){
                $result = Db::name('user_wallet')->where('uid',$uid)->setDec($credit,$money);
            }elseif ($op_type == 2){
                $result = Db::name('user_wallet')->where('uid',$uid)->setInc($credit,$money);
            }
        }
        if($result){
            return ['code'=>200,'msg'=>'success'];
        }
        return ['code'=>400,'msg'=>'????????????'];
    }

    /**
     * ?????????????????????????????????
     * @param $uid
     * @param $credit
     * @return mixed
     */
    public function getUserWallet($uid,$credit){
        $UserWallet = Db::name('user_wallet')->where('uid', $uid)->value($credit);
        return $UserWallet;
    }

    /**
     * ??????????????????
     * @param $uid
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserWallets($uid){
        $UserWallets = Db::name('user_wallet')->where('uid', $uid)->find();
        return $UserWallets;

    }

    /**
     * ????????????VIP
     * @param $uid
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserVip($uid){
        $userVip = Db::name('user')->where('id', $uid)->value('vip');
        return $userVip;
    }

    #z????????????logo?????????
    public function getQrCode($code_content, $code_name, $code_size = 200, $code_logo = '', $code_logo_width = 20, $code_font = null)
    {
        // ???????????????
        $qr_code = new QrCode($code_content);
        // ???????????????
        $qr_code->setSize($code_size);
        // ????????????
        $qr_code->setMargin(5);
        // ????????????
        $qr_code->setWriterByName('png');
        // ????????????
        $qr_code->setEncoding('UTF-8');
        // ?????????????????????L???M???Q???H??????
        $qr_code->setErrorCorrectionLevel(ErrorCorrectionLevel::HIGH()); //???????????????????????????????????????low???medium???quartile???hign???????????????
        // ????????????????????????????????????(????????????)
        $qr_code->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0]);
        $qr_code->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0]);
        // ???????????????
        if ($code_font) {
            $qr_code->setLabel('Scan the Code ', 16, __DIR__ . '????????????', LabelAlignment::CENTER);
        }
        // logo??????
        if ($code_logo) {
            $qr_code->setLogoPath($code_logo);
            // logo??????
            $qr_code->setLogoWidth($code_logo_width);
            // ????????????
            $code_path = '../public/upload/' . $code_name . '.png';
            $qr_code->writeFile($code_path);
        } else {
            // ????????????
            $code_path = '../public/upload/' . $code_name . '.png';
            $qr_code->writeFile($code_path);
        }
        // ????????????
        // header('Content-Type: ' . $qr_code->getContentType());
        // $qr_code->writeString();
        return '/upload/' . $code_name . '.png';
    }

    /**
     * ??????????????????
     * @param $occupation
     * @return mixed
     */
    public function getUserOccupation($occupation){
        if(!in_array($occupation,[1,2,3,4,5,6])){
            return "";
        }
        $user_occupation = [
            1=>"?????????",
            2=>"?????????",
            3=>"?????????",
            4=>"?????????",
            5=>"??????",
            6=>"?????????",
        ];
        return $user_occupation[$occupation];
    }

    /**
     * ??????????????????
     * @param $gender
     * @return string
     */
    public function getUserGender($gender){
        if(!in_array($gender,[1,0])){
            return [];
        }
        $gender == 1 && $userGender = "???";
        $gender == 0 && $userGender = "???";
        return $userGender;
    }

    /**
     * ????????????(???????????????????????????)
     * @param $uid ??????ID
     * @param $data ????????????
     * @param int $layer ????????????
     * @param int $i
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getTeam($uid,&$data = [],$layer = 0,$i = 0){
        $user = Db::name('user')->where('pid','in',$uid)->field('id')->select();
        $ids = "";
        if($layer > 0){
            if($layer < $i){
                return $data;
            }
        }
        $i++;
        if(!empty($user)){
            foreach ($user as $value){
                $ids .= $value['id'].",";
                $value['layer'] = $i;
                $data[] = $value;
            }
            $ids = trim($ids,',');
            $this->getTeam($ids,$data,$layer,$i);
        }

        return $data;
    }

    /**
     * ????????????(???????????????????????????)
     * @param $uid ??????ID
     * @param $data ????????????
     * @param int $layer ????????????
     * @param int $num ????????????
     * @param int $i
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getLayerTeam($uid,&$data,$layer = 0,$num = 10,$i = 0){
        if($layer <= 0){
            return false;
        }
        if(count($data) >= $num){
            return $data;
        }
        $user = Db::name('user')->where('pid','in',$uid)->field('id,avatar,nick_name,register_time')->select();
        $ids = "";
        if(!empty($user)){
            $i++;
            if($layer == $i){
                foreach ($user as $value){
                    $value['layer'] = $i;
                    $data[] = $value;
                }
                return $data;
            }else{
                foreach ($user as $value){
                    $ids .= $value['id'].",";
                }
                $ids = trim($ids,',');
                $this->getLayerTeam($ids,$data,$layer,$num,$i);
            }
        }

        return $data;
    }

    /**
     * ?????????????????????????????????????????????
     * @param $uid ??????ID
     * @param $data ????????????
     * @param int $layer ?????????????????????0?????????
     * @param int $i
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserAgents($uid,&$data,$layer = 0,$i = 0){
        if($layer > 0){
            if($i > $layer){
                return $data;
            }
        }
        $user = Db::name('user')->where('id',$uid)->field('id,avatar,nick_name,register_time,pid,commission_level')->find();
        $i++;
        if(!empty($user)){
            if($i > 1){
                $data[] = $user;
            }
            $this->getUserAgents($user['pid'],$data,$layer,$i);
        }
        return $data;
    }


}
