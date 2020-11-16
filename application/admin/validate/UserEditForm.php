<?php
namespace app\admin\validate;

use think\Request;
use think\Validate;

class UserEditForm extends Validate
{

    protected $rule = [
        'mobile' => 'min:6|isMobile',
        'password' => 'min:6',
        're_password' => 'min:6|confirm:password',
        'trad_password' => 'min:6',
        're_trad_password' => 'min:6|confirm:trad_password',

    ];

    protected $message = [
        'mobile.require' => '请输入手机号',
        'password.min' => '密码至少6位数',
        're_password.confirm' => '两次登录密码不一致',
        'trad_password.min' => '交易密码至少6位数',
        're_trad_password.confirm' => '两次交易密码不一致',
    ];
    protected function isMobile($value)
    {
        $rule = '/^0?(13|14|15|17|18)[0-9]{9}$/';
        $result = preg_match($rule, $value);
        if ($result) {
            return true;
        } else {
            return '请输入正确手机号';
        }
    }

    protected function checkMobile($value, $rule, $data = [])
    {
        if (\app\common\entity\User::checkMobile($value)) {
            return '此账号已被注册，请重新填写';
        }
        return true;
    }


}