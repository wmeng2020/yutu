<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

return [
    'app\common\command\OneClick',
    'app\common\command\VipOrder',
    'app\common\command\TeamUpgrade',
    'app\common\command\UpdateTaskStatus',
    'app\common\command\DistributionReward',
    'app\common\command\BetweenReward',
    'app\common\command\TeamReward',
    'app\common\command\Merits',// 第一步
    'app\common\command\MeritsSend',// 第二步
    'app\common\command\MeritsSendTo',// 第三步
    'app\common\command\SendTeamPrize',// 第四步
];

