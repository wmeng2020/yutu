<?php

namespace app\index\controller;


use app\common\entity\Config;
use app\common\entity\ManageUser;
use app\common\entity\MyWalletLog;
use app\common\entity\RechargeModel;
use app\common\entity\TaskOrderModel;
use app\common\entity\UserInviteCode;
use app\common\entity\UserOtherModel;
use app\common\service\Users\Identity;
use app\common\service\Users\Service;
use app\index\model\SendCode;
use app\index\validate\RegisterForm;
use think\Request;
use app\common\entity\User;
use think\Db;

class Member extends Base
{
    /**
     * 获取个人资料
     */
    public function index()
    {

        //获取缓存用户详细信息
        $userInfo = User::alias('u')
            ->leftJoin('my_wallet mw','u.id=mw.uid')
            ->leftJoin('config_team_level c','u.star_level=c.id')
            ->field('
                u.id,
                u.level,
                u.nick_name,
                u.mobile,
                u.avatar,
                u.star_level,
                mw.number,
                mw.bond,
                mw.agent,
                c.task_num
            ')
            ->where('u.id', $this->userId)
            ->find();
        if(!$userInfo['task_num']){
            $userInfo['task_num'] = Config::getValue('free_task_num');
        }
        switch ($userInfo['star_level']) {
            case 0:
                $userInfo['level_name'] = '普通会员';
            case 1:
                $userInfo['level_name'] =  'VIP1';
            case 2:
                $userInfo['level_name'] =  'VIP2';
            case 3:
                $userInfo['level_name'] =  'VIP3';
            case 4:
                $userInfo['level_name'] = 'VIP4';
            case 5:
                $userInfo['level_name'] =  'VIP5';
            case 6:
                $userInfo['level_name'] =  'VIP6';
            
        }
        //今日收益
        $today_profit = MyWalletLog::where('uid',$this->userId)
                ->where('types',5)
                ->where('status',1)
                ->where('money_type',1)
                ->whereTime('create_time','today')
                ->sum('number')
            + MyWalletLog::where('uid',$this->userId)
                ->whereIn('types',[11,8,9,10])
                ->where('status',1)
                ->where('money_type',1)
                ->whereTime('create_time','tomorrow')
                ->sum('number');
        //是否代理
        $is_agent = ManageUser::where('left_uid',$this->userId)->find();
        if($is_agent){
            $userInfo['is_agent'] = 1;
        }else{
            $userInfo['is_agent'] = 0;
        }
        return json(['code' => 0, 'msg' => '请求成功', 'info' => [
            'userInfo' => $userInfo,
            'today_profit' => $today_profit ,
        ]]);
    }
    /**
     * 编辑个人资料
     */
    public function set(Request $request)
    {
        $query = new User();
        $update_data = [];
        if($request->post('nick_name')){
            $info = User::where('nick_name',$request->post('nick_name'))
                ->find();
            if($info){
                return json(['code' => 1, 'msg' => '用户名已存在']);
            }
            $update_data['nick_name'] =  $request->post('nick_name');
        }
        if($request->post('avatar')){
            $update_data['avatar'] =  $request->post('avatar');
        }

        if($update_data){
            $res = $query->where('id', $this->userId)->update($update_data);
        }else{
            $userInfo = User::alias('u')
                ->leftJoin('my_wallet mw','u.id=mw.uid')
                ->field('
                u.id,
                u.level,
                u.nick_name,
                u.mobile,
                u.avatar,
                mw.number
             
            ')
                ->where('u.id', $this->userId)
                ->find();
            return json(['code' => 0, 'msg' => '修改成功', 'info' => $userInfo]);
        }
        $userInfo = User::alias('u')
            ->leftJoin('my_wallet mw','u.id=mw.uid')
            ->field('
                u.id,
                u.level,
                u.nick_name,
                u.mobile,
                u.avatar,
                mw.number
             
            ')
            ->where('u.id', $this->userId)
            ->find();
        if (is_int($res)) {
            return json(['code' => 0, 'msg' => '修改成功', 'info' => $userInfo]);
        }
        return json(['code' => 1, 'msg' => '修改失败']);
    }
    /**
     * 绑定抖音或快手
     */
    public function bindingOther(Request $request)
    {
        if($request->isGet()){
            $types = $request->get('types');
            if(!$types)  return json(['code' => 1, 'msg' => '参数错误']);
            $info = UserOtherModel::where('types',$types)
                ->where('uid',$this->userId)
                ->find();
            return json(['code' => 0, 'msg' => '请求成功','info'=>$info]);
        }
        if($request->isPost()) {
            $validate = $this->validate($request->post(), '\app\index\validate\BindingOther');
            if ($validate !== true) {
                return json(['code' => 1, 'msg' => $validate]);
            }
            $user = User::where('id', $this->userId)->find();
            if ($request->post('types') == 1) {//抖音
                if ($user['tiktok_status'] == 2) {
                    return json(['code' => 1, 'msg' => '审核中']);
                }
                if ($user['tiktok_status'] == 3) {
                    return json(['code' => 1, 'msg' => '已绑定']);
                }
            } elseif ($request->post('types') == 2) {//快手
                if ($user['kwaifu_status'] == 2) {
                    return json(['code' => 1, 'msg' => '审核中']);
                }
                if ($user['kwaifu_status'] == 3) {
                    return json(['code' => 1, 'msg' => '已绑定']);
                }
            }
            $add_data = $request->post();
            $add_data['uid'] = $this->userId;
            $model = new UserOtherModel();
            $res = $model->addNew($model, $add_data);
            if ($res) {
                if ($request->post('types') == 1) {//抖音
                    User::where('id', $this->userId)
                        ->setField('tiktok_status', 2);
                } elseif ($request->post('types') == 2) {//快手
                    User::where('id', $this->userId)
                        ->setField('kwaifu_status', 2);
                }
                return json(['code' => 0, 'msg' => '绑定成功']);
            }
            return json(['code' => 1, 'msg' => '绑定失败']);
        }
    }
    /**
     * 我要推广
     */
    public function spread()
    {
        $info = UserInviteCode::where('user_id', $this->userId)->value('invite_code');
        return json()->data(['code' => 0, 'msg' => '请求成功', 'info' => $info]);
    }
    /**
     * 忘记交易密码
     */
    public function forgetTrad(Request $request)
    {
        $validate = $this->validate($request->post(), '\app\index\validate\ForgetTrad');
        if ($validate !== true) {
            return json(['code' => 1, 'msg' => $validate]);
        }
        $info = User::where('id', $this->userId)->find();

        $form = new RegisterForm();
        $msg_checking = Config::getValue('msg_checking');//开启短信验证
        if($msg_checking){
            if (!$form->checkCode($request->post('code'), $info['mobile'])) {
                return json(['code' => 1, 'msg' => '验证码输入错误']);
            }
        }
        $service = new Service();
        $res = User::where("id", $this->userId)->update(["trad_password" => $service->getPassword($request->post('password'))]);
        if (is_int($res)) {
            return json(['code' => 0, 'msg' => '新密码设置成功']);
        }
        return json(['code' => 1, 'msg' => '系统错误']);
    }

    /**
     * 投诉
     */
    public function Complaint(Request $request)
    {
        $validate = $this->validate($request->post(), '\app\index\validate\Complaint');
        if ($validate !== true) {
            return json(['code' => 1, 'msg' => $validate]);
        }
        $model = new PersonService();
        $add_data = $request->post();
        $add_data['uid'] = $this->userId;
        $res = $model->addNew($model, $add_data);
        if ($res) {
            return json()->data(['code' => 0, 'msg' => '操作成功']);
        }
        return json()->data(['code' => 1, 'msg' => '操作失败']);
    }

    public function sendChange()
    {

        $mobile = User::where ('id', $this->userId)->value('mobile');
        //检验手机号码

        $model = new SendCode($mobile, 'change-password');
        if ($model->send()) {
            return json(['code' => 0, 'msg' => '你的验证码发送成功']);
        }
        return json(['code' => 1, 'msg' => '发送失败']);
    }

    /**
     * 修改密码
     */
    public function ChangePassword(Request $request)
    {
        $user = User::where("id", $this->userId)->find();
        $old_pwd = $request->post("old_pwd"); //旧密码
        $new_pwd = $request->post("new_pwd"); //新密码
//        $confirm_pwd = $request->post("confirm_pwd"); //确认密码
        $service = new Service();

        if ($service->getPassword($new_pwd) == $user->password) {
            return json(['code' => 1, 'msg' => '密码未修改']);
        }
        if (strlen($new_pwd) < 6) {
            return json(['code' => 1, 'msg' => '密码最少六位数']);
        }

        if($request->post('type') == 1){
            if (!$service->checkPassword($old_pwd, $user)) {
                return json(['code'=>1,'msg'=>'密码错误']);
            }
            $res = User::where("id", $this->userId)->update(["password" => $service->getPassword($new_pwd)]);
            $identity = new Identity();
            $identity->delCache();
            $msg = '登录密码修改成功，请重新登录';
        }elseif($request->post('type') == 2){
            if (!$service->checkSafePassword($old_pwd, $user)) {
                return json(['code'=>1,'msg'=>'密码错误']);
            }
            $res = User::where("id", $this->userId)->update(["trad_password" => $service->getPassword($new_pwd)]);
            $msg = '交易密码修改成功';
        }
        if ($res) {
            return json(['code' => 0, 'msg' => $msg]);
        } else {
            return json(['code' => 1, 'msg' => '修改失败']);
        }
    }

    /**
     * 清除缓存
     */
    public function delCache()
    {
        $identity = new Identity();
        $identity->delCache($this->userId);
    }
    /**
     * 验证交易密码
     */

    public function checkPass(Request $request)
    {
        $user_Info = User::alias('u')
            ->where('u.id',$this->userId)
            ->find();
        $model = new \app\common\service\Users\Service();
        if (!$model->checkSafePassword($request->post('trad_password'), $user_Info)) {
            return json(['code'=>1,'msg'=>'密码错误']);
        }
        return json(['code' => 0, 'msg' => '验证成功']);
    }
    /**
     * 退出登录
     */
    public function logout()
    {
        $service = new Identity();
        $service->logout();
        return json(['code' => 0, 'msg' => '退出成功']);
    }



    /**
     * 我的资产
     */
    public function asset(Request $request)
    {
        $number = \app\common\entity\MyWallet::where('uid', $this->userId)->value('number');
        $limit = $request->get('limit',15) ;
        $page = $request->get('page',1) ;
        $types = $request->get('types');
        $query = MyWalletLog::field('id,uid,number,old,new,remark,from,types,status,create_time');
        if ($types) {
            $query->where('types', $types);
        }
        $list = $query
            ->where('uid', $this->userId)
            ->order('create_time', 'desc')
            ->page($page)
            ->paginate($limit, false, [
                'query' => $request->param() ? $request->param() : []
            ]);
        $data = [
            'list' => $list,
            'number' => $number,
        ];
        return json(['code' => 0, 'msg' => '获取成功', 'info' => $data]);
    }

    /**
     * 我的资产
     */
    public function withdraw(Request $request)
    {
        if ($request->isGet()) {
            $info = RechargeConfig::where('status', 1)->find();
            $exchange = \app\common\entity\Exchange::find();
            return json()->data(['code' => 0, 'msg' => '请求成功', 'info' => $info, 'exchange' => $exchange]);
        }
        if ($request->isPost()) {
            $validate = $this->validate($request->post(), '\app\index\validate\Withdraw');
            if ($validate !== true) {
                return json(['code' => 1, 'msg' => $validate]);
            }
            $model = new \app\common\service\Users\Service();
            $user = User::where('id', $this->userId)->find();
            if (!$model->checkSafePassword($request->post('trad_password'), $user)) {
                return json()->data(['code' => 1, 'msg' => '交易密码错误']);
            }
            $info = RechargeConfig::where('status', 1)->find();
            $userInfo = \app\common\entity\MyWallet::where('uid', $this->userId)->find();
            if ($userInfo['number'] < $request->post('num')) {
                return json()->data(['code' => 1, 'msg' => '余额不足']);
            }
            if ($info) {
                if ($request->post('num') < $info['min']) {
                    return json()->data(['code' => 1, 'msg' => '提现金额太小']);
                }
                $service_charge = $request->post('num') * $info['service_charge'] * 0.01;
                $actual_sum = $request->post('num') - $service_charge;
            } else {
                $service_charge = 0;
                $actual_sum = $request->post('num');
            }
            $entry = new MyWalletLog();
            $result = $entry->addRechargeLog($this->userId, $request->post('num'), $request->post('types'), $request->post('address'));
            if ($result) {
                \app\common\entity\MyWallet::where('uid', $this->userId)->setDec('number', $request->post('num'));
            }

            $recharge_data = [
                'uid' => $this->userId,
                'num' => $request->post('num'),
                'service_charge' => $service_charge,
                'actual_sum' => $actual_sum,
                'types' => $request->post('types'),
                'address' => $request->post('address'),
                'pic' => $request->post('pic') ? $request->post('pic') : '',
                'bank_name' => $request->post('bank_name') ? $request->post('bank_name') : '',
                'bank_user' => $request->post('bank_user') ? $request->post('bank_user') : '',
            ];
            $query = new RechargeLog();
            $res = $query->addNew($query, $recharge_data);
            if (!$res) {
                return json()->data(['code' => 1, 'msg' => '申请失败']);
            }
            return json()->data(['code' => 0, 'msg' => '申请成功']);
        }
    }

    /**
     * 互转赠送
     */
    public function interturn(Request $request)
    {
        $mobile = $request->post('mobile');
        $uid = User::where('mobile', $mobile)->value('id');
        if (!$uid) {
            return json()->data(['code' => 1, 'msg' => '用户不存在，请检查手机号']);
        }
        $model = new \app\common\service\Users\Service();
        $userInfo = User::alias('u')
            ->field('u.*,mw.number')
            ->leftJoin('my_wallet mw', 'mw.uid = u.id')
            ->where('u.id', $this->userId)
            ->find();
        if (!$model->checkSafePassword($request->post('trad_password'), $userInfo)) {
            return json()->data(['code' => 1, 'msg' => '交易密码错误']);
        }
        $num = $request->post('num');
        if ($userInfo['number'] < $num) {
            return json()->data(['code' => 1, 'msg' => '余额不足']);
        }
        //添加转出记录
        $entry = new MyWalletLog();
        $out = $entry->addOutTransferLog($this->userId, $num, $mobile);
        if ($out) {
            \app\common\entity\MyWallet::where('uid', $this->userId)->setDec('number', $num);
        }
        //添加转入记录
        $query = new MyWalletLog();
        $in = $query->addEnterTransferLog($uid, $num, $userInfo['mobile']);
        if ($in) {
            \app\common\entity\MyWallet::where('uid', $uid)->setInc('number', $num);
        }
        return json()->data(['code' => 0, 'msg' => '转账成功']);
    }

    /**
     * 提现记录
     */
    public function withdrawLog(Request $request)
    {
        $limit = $request->get('limit') ? $request->get('limit') : 15;
        $page = $request->get('page') ? $request->get('page') : 1;
        $list = RechargeLog::field('status,num,service_charge,actual_sum,types,address,pic,bank_name,bank_user,create_time,update_time')
            ->where('uid', $this->userId)
            ->order('create_time', 'desc')
            ->page($page)
            ->paginate($limit, false, [
                'query' => $request->param() ? $request->param() : []
            ]);
        if ($list) {
            return json(['code' => 0, 'msg' => '获取成功', 'info' => $list]);
        }
        return json(['code' => 1, 'msg' => '获取失败']);
    }


    /*
     * 客服
     */
    public function getService()
    {
        $service_name = Config::getValue('service_name');
        $service_tel = Config::getValue('service_tel');
        return json(['code' => 0, 'msg' => '获取成功', 'info' => [
            'service_name' => $service_name,
            'service_tel' => $service_tel,
        ]]);
    }
    /**
     * 我的市场
     */
    public function getTeamInfo(Request $request)
    {
        $userModel = new User();
        $nextArr = $userModel->getChildsInfo1($this->userId,3);
        $nextArrId = [];
        $todayStrattime = strtotime(date('Y-m-d',\time())."00:00:00");
        $todayEndtime = strtotime(date('Y-m-d',\time())."23:59:59");
        $new = [];
        foreach ($nextArr as $k=>$v){
            foreach ($v as $key=>$val){
                $nextArrId[] = $val['id'];
                if(strtotime($val['register_time']) >= $todayStrattime && strtotime($val['register_time']) <= $todayEndtime){
                    $new[] = $val['id'];
                }
                $v[$key]['mobile'] = substr_replace($val['mobile'],'****',3,4);
            }
            $nextArr[$k] = $v;
        }

        //团队流水
        $team_stream = MyWalletLog::whereIn('uid',$nextArrId)
            ->where('types',4)
            ->where('money_type',1)
            ->where('status',1)
            ->sum('number');
        //团队总佣金
        $total_commission = MyWalletLog::whereIn('uid',$nextArrId)
            ->where('types',6)
            ->where('money_type',2)
            ->where('status',1)
            ->sum('number');
        //团队总提现
        $total_withdrawal = MyWalletLog::whereIn('uid',$nextArrId)
            ->where('types',2)
            ->where('money_type',2)
            ->where('status',2)
            ->sum('number');
        //首冲人数
        $first_flush = RechargeModel::whereIn('id',$nextArrId)
            ->where('status',2)
            ->count('uid');
        //直推人数
        $direct_push = User::where('pid',$this->userId)->count();
        //团队人数
        $team_total_people = count($nextArrId);
        //新增人数
        $new_subordinate = count($new);
        //团队资产
        $result['team_assets'] = [
            'team_stream' => $team_stream,//团队流水
            'total_commission' => $total_commission,//团队总佣金
            'total_withdrawal' => $total_withdrawal,//团队总提现
        ];
        //团队人数
        $result['team_num'] = [
            'first_flush' => $first_flush,//首冲人数
            'direct_push' => $direct_push,//直推人数
            'team_total_people' => $team_total_people,//团队人数
            'new_subordinate' => $new_subordinate,//新增人数
        ];
        $result['list'] = $nextArr;
        return json(['code' => 0, 'msg' => '获取成功', 'info' => $result]);
    }

    /**
     * 收益明细  MyWalletLog::
     *  '任务佣金奖励',  5
    '任务间推分销佣金奖励', 11
    '任务二级分销佣金奖励', 8
    '任务佣金团队奖励', 9
    '任务佣金平级奖励' 10
     */
    public function profit(Request $request)
    {
        //累计收益
        $all_profit = MyWalletLog::where('uid',$this->userId)
            ->where('status',1)
            ->whereIn('types',[5,11,8,9,10])
            ->sum('number');
        //本月收益
        $month_profit = MyWalletLog::where('uid',$this->userId)
            ->where('status',1)
            ->whereIn('types',[5,11,8,9,10])
            ->whereTime('create_time','month')
            ->sum('number');
        //收益明细
        $date = $this->dateInfo();
        $date1 = $this->dateInfo1();

        foreach ($date as $k=>$v){
            if($k<10){
                $v1 = date('Y-m-d', strtotime($v . ' +1 day'));
                $date_profit[$date1[$k]]['task'] = MyWalletLog::where('uid',$this->userId)
                    ->where('status',1)
                    ->where('types',5)
                    ->whereTime('create_time', 'between', [$date[$k+1],$v])
                    ->sum('number');
                $date_profit[$date1[$k]]['foster'] = MyWalletLog::where('uid',$this->userId)
                    ->where('status',1)
                    ->where('types',10)
//                    ->where('name', '任务佣金平级奖励')
                    ->whereTime('create_time', 'between', [$v, $v1])
                    ->sum('number');
                $date_profit[$date1[$k]]['share'] = MyWalletLog::where('uid',$this->userId)
                    ->where('status',1)
                    ->whereIn('types',[11,8])
                    ->whereTime('create_time', 'between', [$v, $v1])
//                    ->whereIn('name',[
//                        '任务间推分销佣金奖励',
//                        '任务二级分销佣金奖励',
//                    ])
                    ->sum('number');
                $date_profit[$date1[$k]]['team'] = MyWalletLog::where('uid',$this->userId)
                    ->where('status',1)
                    ->where('types',9)
//                    ->where('name', '任务佣金团队奖励')
                    ->whereTime('create_time', 'between', [$v, $v1])
                    ->sum('number');
                $date_profit[$date1[$k]]['all'] = $date_profit[$date1[$k]]['team'] +  $date_profit[$date1[$k]]['share'] + $date_profit[$date1[$k]]['foster'] + $date_profit[$date1[$k]]['task'];
            }

        }
        $yesday = date("Y-m-d",strtotime("-1 day"));
//        dump($yesday);
        $data = [
            'all' => $all_profit,
            'today' => $date_profit[date("Y-m-d")]['all'],
            'yesterday' => $date_profit[$yesday]['all'],
            'month' => $month_profit,
            'date_profit' => $date_profit,
        ];
        return json(['code' => 0, 'msg' => '获取成功', 'info' => $data]);
    }
    public function dateInfo($time = '', $format='Y-m-d'){
        $time = $time != '' ? $time : time();
        $date = array();
        for ($i=0; $i<=10; $i++){
            $date[0] = date($format ,strtotime( '+' . 1 .' days', $time));
            $date[$i+1] = date($format ,strtotime( '-' . $i .' days', $time));
        }
        return $date;
    }
    public function dateInfo1($time = '', $format='Y-m-d'){
        $time = $time != '' ? $time : time();
        $date = array();
        for ($i=0; $i<=10; $i++){
            $date[$i] = date($format ,strtotime( '-' . $i .' days', $time));
        }
        return $date;
    }
    public function spreadImage(Request $request)
    {
        $limit = $request->param('limit',3);
        $list = Db::table('spread_image')
            ->limit($limit)
            ->select();
        return json(['code' => 0, 'msg' => '获取成功', 'info' => $list]);
    }
}
