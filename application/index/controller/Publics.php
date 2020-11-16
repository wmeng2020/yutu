<?php

namespace app\index\controller;

use app\common\entity\Config;
use app\common\entity\MyWalletLog;
use app\common\entity\OperationCenterModel;
use app\common\entity\User;
use app\common\service\Users\Service;
use app\index\model\SendCode;
use app\index\model\SendCode1;
use app\index\model\SiteAuth;
use app\index\validate\RegisterForm;
use think\Controller;
use think\Db;
use think\Request;


class Publics extends Controller
{
    public function dowmApp()
    {
        $dowm = Config::getValue('appdownload');
        return json(['code' => 0, 'msg' => '请求成功','info'=>$dowm]);
    }
    //sadasdadsadasdasda
    /**
     * 处理登录
     */
    public function login(Request $request)
    {
        $result = $this->validate($request->post(), 'app\index\validate\LoginForm');
        if ($result !== true) {
            return json(['code' => 1, 'msg' => $result]);
        }
        $model = new \app\index\model\User();
        $result = $model->doLogin($request->post('mobile'), $request->post('password'));
        if ($result !== true) {
            if ($result == '用户名或密码错误') {
                return json(['code' => 1, 'msg' => $result]);
            }
            return json(['code' => 1, 'msg' => $result]);
        }
        User::where('mobile', $request->post('mobile'))
            ->update([
                'login_time' => session_id(),
                'login_ip' => $request->ip(),
                'last_date'=> date('Y-m-d H:i:s',time()),
            ]);
        User::where('mobile', $request->post('mobile'))->setInc('login_number');
        $userInfo = User::alias('u')
            ->field('u.id')
            ->leftJoin('my_wallet mw', 'mw.uid = u.id')
            ->where('u.mobile', $request->post('mobile'))
            ->find();
        return json(['code' => 0, 'msg' => '登录成功', 'info' => $userInfo]);
    }

    /**
     * 忘记密码
     */
    public function forget(Request $request)
    {
        $validate = $this->validate($request->post(), '\app\index\validate\Forget');
        if ($validate !== true) {
            return json(['code' => 1, 'msg' => $validate]);
        }
        $info = User::where('mobile', $request->post('mobile'))->find();
        if (!$info) {
            return json(['code' => 1, 'msg' => '用户不存在']);
        }
        $form = new RegisterForm();
        $msg_checking = Config::getValue('msg_checking');//开启短信验证
        if($msg_checking){
            if (!$form->checkCode($request->post('code'), $request->post('mobile'))) {
               return json(['code' => 1, 'msg' => '验证码输入错误']);
            }
        }
        $model = new Service();
        $res = $model->updatePwd($info, $request->post());
        if (is_int($res)) {
            return json(['code' => 0, 'msg' => '新密码设置成功']);
        }
        return json(['code' => 1, 'msg' => '系统错误']);
    }

    /**
     * 注册接口
     */
    public function doRegister(Request $request)
    {
        $model = new \app\index\model\User();
        if (!$model->checkIp()) {
//            return json(['code' => 1, 'msg' => '该IP已注册太多用户']);
        }
        $form = new RegisterForm();
        $msg_checking = Config::getValue('msg_checking');//开启短信验证
        if($msg_checking){
            if (!$form->checkCode($request->post('code'), $request->post('mobile'))) {

                return json(['code' => 1, 'msg' => '验证码输入错误']);
            }
        }

        $validate = $this->validate($request->post(), '\app\index\validate\RegisterForm');
        if ($validate !== true) {
            return json(['code' => 1, 'msg' => $validate]);
        }
        $add_data = $request->post();
        $result = $model->doRegister($add_data);
        if ($result) {
            return json(['code' => 0, 'msg' => '注册成功']);
        }
        return json(['code' => 1, 'msg' => '注册失败']);
    }

    /**
     * 发送注册验证码
     */
    public function send(Request $request)
    {
        if ($request->isPost()) {
            $mobile = $request->post('mobile');
            //检验手机号码
            if (!preg_match('#^1\d{10}$#', $mobile)) {
                return json(['code' => 1, 'msg' => '手机号码格式不正确', 'info' => $mobile]);
            }
            $model = new SendCode($mobile, 'register');
            $res = $model->send();
            if ($res === true) {
                return json(['code' => 0, 'msg' => '你的验证码发送成功']);
            }
            return json(['code' => 1, 'msg' => '发送失败']);
        }
    }




    #获取行情定时
    public function getRatio()
    {
        $model = new WalletRatio();
        //USDT cny价格
        $usdt_cnyx = $this->curl_get_https('https://data.gateio.co/api2/1/ticker/usdt_cnyx');
        $data = [
            'volume_usdt' => $usdt_cnyx['baseVolume'],
            'usdt' => 1,
            'cny' => $usdt_cnyx['last'],
            'change' => $usdt_cnyx['percentChange'],
            'create_time' => time()
        ];
        $model->where('money_name', 'usdt')->update($data);

        //BTC cny usdt价格
        $btc_usdt = $this->curl_get_https('https://data.gateio.co/api2/1/ticker/btc_usdt');
        $data = [
            'volume_usdt' => $btc_usdt['baseVolume'],
            'usdt' => $btc_usdt['last'],
            'cny' => $btc_usdt['last'] * $usdt_cnyx['last'],
            'change' => $btc_usdt['percentChange'],
            'create_time' => time()
        ];

        $model->where('money_name', 'btc')->update($data);

        //ETH cny usdt价格
        $eth_usdt = $this->curl_get_https('https://data.gateio.co/api2/1/ticker/eth_usdt');
        $data = [
            'volume_usdt' => $eth_usdt['baseVolume'],
            'usdt' => $eth_usdt['last'],
            'cny' => $eth_usdt['last'] * $usdt_cnyx['last'],
            'change' => $eth_usdt['percentChange'],
            'create_time' => time()
        ];
        $model->where('money_name', 'eth')->update($data);

        //XRP cny usdt价格
        $xrp_usdt = $this->curl_get_https('https://data.gateio.co/api2/1/ticker/xrp_usdt');
        $data = [
            'volume_usdt' => $xrp_usdt['baseVolume'],
            'usdt' => $xrp_usdt['last'],
            'cny' => $xrp_usdt['last'] * $usdt_cnyx['last'],
            'change' => $xrp_usdt['percentChange'],
            'create_time' => time()
        ];
        $model->where('money_name', 'xrp')->update($data);

        //LTC cny usdt价格
        $ltc_usdt = $this->curl_get_https('https://data.gateio.co/api2/1/ticker/ltc_usdt');
        $data = [
            'volume_usdt' => $ltc_usdt['baseVolume'],
            'usdt' => $ltc_usdt['last'],
            'cny' => $ltc_usdt['last'] * $usdt_cnyx['last'],
            'change' => $ltc_usdt['percentChange'],
            'create_time' => time()
        ];
        $model->where('money_name', 'ltc')->update($data);

        //BCH cny usdt价格
        $bch_usdt = $this->curl_get_https('https://data.gateio.co/api2/1/ticker/bch_usdt');
        $data = [
            'volume_usdt' => $bch_usdt['baseVolume'],
            'usdt' => $bch_usdt['last'],
            'cny' => $bch_usdt['last'] * $usdt_cnyx['last'],
            'change' => $bch_usdt['percentChange'],
            'create_time' => time()
        ];
        $model->where('money_name', 'bch')->update($data);

        return 'ok';

    }

    public function curl_get_https($url)
    {
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        //curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, true);  // 从证书中检查SSL加密算法是否存在
        $tmpInfo = curl_exec($curl);     //返回api的json对象
        //关闭URL请求
        curl_close($curl);
        $res = json_decode($tmpInfo, true);
        return $res;    //返回json对象

    }

}
