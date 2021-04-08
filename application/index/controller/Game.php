<?php

namespace app\index\controller;
use app\common\entity\Config;
use app\common\PHPMailer\Exception;
use redis\RedisCluster;
use think\cache\driver\Redis;
use think\Db;
use think\Image;
use think\Request;

class Game extends Base
{


    /**
     * 获取赛事详情
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getGameDetail(Request $request){
        $post = $request->post();
        $room_id = intval($post['room_id']);
        $uid = $this->userId;
        if(empty($room_id)){
            return _result(false,'请选择赛事');
        }
//        $where['status'] = ['<',5];
        $where['id'] = ['=',$room_id];
        $where['deleted'] = ['=',0];
        $room = Db::name('game_room')->where($where)->find();
        if(empty($room)){
            return _result(false,'该赛事不存在');
        }
        $roomgame_type = $this->getRoomClassify($room['roomgame_type']);
        $match_type = $this->getRoomClassify($room['match_type']);
//        $mobile_type = $this->getRoomClassify($room['mobile_type']);
        $room['surplus_time'] = ['readytime'=>$room['readytime'],'enrolltime'=>$room['enrolltime']];
        $room['enrolltime'] = date("m-d H:i",$room['enrolltime']);
        $room['readytime'] = date("m-d H:i",$room['readytime']);
        $room['starttime'] = date("m-d H:i",$room['starttime']);
        $room['pattern'] = [
            $roomgame_type['title'],
            $match_type['title'],
//            $mobile_type['title'],
        ];
        $room['reward'] = json_decode($room['reward'],true);
        $room['rule'] = htmlspecialchars_decode($room['rule']);
        $room['room_url'] = htmlspecialchars_decode($room['room_url']);
        if($room['game_type'] == 2){
            $user_participate_game_list = Db::name('user_participate_game_list')
                ->alias('l')
                ->join('user u','l.uid = u.id')
                ->field('u.nick_name,u.avatar')
                ->where('l.room_id',$room['id'])
                ->group('l.uid')
                ->order('l.id asc')
                ->limit(5)
                ->select();
        }elseif ($room['game_type'] == 1){
            $user_participate_game_list['teamred'] = Db::name('user_participate_game_list')
                ->alias('l')
                ->join('user u','l.uid = u.id')
                ->field('u.nick_name,u.avatar')
                ->where(['l.room_id'=>['=',$room['id']],'l.team_type'=>['=',1]])
                ->group('l.uid')
                ->order('l.id asc')
                ->limit(5)
                ->select();
            $user_participate_game_list['teamblue'] = Db::name('user_participate_game_list')
                ->alias('l')
                ->join('user u','l.uid = u.id')
                ->field('u.nick_name,u.avatar')
                ->where(['l.room_id'=>['=',$room['id']],'l.team_type'=>['=',2]])
                ->group('l.uid')
                ->order('l.id asc')
                ->limit(5)
                ->select();
        }
        $room['user_participate_game_list'] = $user_participate_game_list;
        $user['is_participate_game'] = Db::name('user_participate_game_list')->where(['uid'=>$uid,'room_id'=>$room_id,'status'=>0])->value('id') ? 1 : 0;
        return _result(true,'success',['room'=>$room,'user'=>$user]);
    }

    /**
     * 退票
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function cancelParticipateGame(Request $request){
        $uid = $this->userId;
        $participate_id = intval($request->post('participate_id'));
        $user_participate_game_list = Db::name('user_participate_game_list')
            ->alias('p')
            ->leftJoin('game_room g','g.id = p.room_id')
            ->field('p.*,g.status as rstatus')
            ->where(['p.uid'=>$uid,'p.id'=>$participate_id])
            ->find();
        if(empty($user_participate_game_list)){
            return _result(false,'该参赛记录不存在');
        }
        if($user_participate_game_list['rstatus'] > 2){
            return _result(false,'比赛已经开始不能退票');
        }
        try {
            Db::startTrans();
            $result = Db::name('user_game_ticket')->where(['uid'=>$uid,'id'=>$user_participate_game_list['ticket_id']])->update(['status'=>0]);
            if(!$result){
                throw new Exception('退票失败');
            }
            $result = Db::name('user_participate_game_list')->where('id',$user_participate_game_list['id'])->update(['status'=>-1]);
            if(!$result){
                throw new Exception('退票失败');
            }
            Db::commit();
            return _result(true,'退票成功');

        }catch (Exception $e){
            Db::rollback();
            return _result(false,$e->getMessage());

        }


    }

    /**
     * 参加比赛
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function participateGame(Request $request){
        $uid = $this->userId;
        $post = $request->post();
        $room_id = intval($post['room_id']);
        $team_type = intval($post['team_type']);
        $room_where = [
            'id'=>['=',$room_id],
            'status'=>['<=',1],
            'deleted'=>0
        ];
        $room = Db::name('game_room')->where($room_where)->find();
        if(empty($room)){
            return _result(false,'赛事不存在或已开始');
        }
        if ($room['house_full'] == 1){
            return _result(false,'赛事已爆满');
        }
        $user_game_account = Db::name('user_game_account')->where(['uid'=>$uid,'game_type'=>$room['game_type'],'mobile_type'=>$room['mobile_type']])->find();
        if(empty($user_game_account)){
            return _result(false,'请先绑定游戏帐号');
        }
        $user_game_ticket = Db::name('user_game_ticket')
            ->where(['ticket_id'=>$room['ticket_id'],'status'=>0,'uid'=>$uid])
            ->find();

        $game_ticket = Db::name('game_ticket')->where('id',$room['ticket_id'])->find();

        if(empty($user_game_ticket)){
            return _result(false,'请先购买门票('.$game_ticket['ticketname'].")");
        }
        if($user_game_ticket['createtime'] + (86400 * 30) < time()){
            return _result(false,'请先购买门票('.$game_ticket['ticketname'].")");
        }
        $user_buy_participate = Db::name('user_participate_game_list')->where(['uid'=>$uid,"room_id"=>$room_id,'status'=>0])->find();
        if(!empty($user_buy_participate)){
            return _result(false,'您已经参赛，请准时参加');
        }

        if(time() < $room['enrolltime']){
            return _result(false,'比赛还没到报名时间');
        }
        if(time() > $room['readytime']){
            return _result(false,'比赛报名已截止');
        }
        if($room['game_type'] == 1){
            $max_participate_num = 10;
        }elseif ($room['game_type'] == 2){
            $max_participate_num = 99;
        }
        $participate_game_num = Db::name('user_participate_game_list')->where(['room_id'=>$room_id,'status'=>0])->count();
        if($participate_game_num >= $max_participate_num){
            return _result(false,'参赛人数已满');
        }
        $data = [
            'uid'=>$uid,
            'room_id'=>$room['id'],
            'ticket_id'=>$user_game_ticket['id'],
            'game_type'=>$room['game_type'],
            'match_type'=>$room['match_type'],
            'game_starttime'=>$room['starttime'],
            'createtime'=>time(),
        ];
        if($room['game_type'] == 1){
            if(empty($team_type)){
                return _result(false,'请先选择队伍');
            }
            $team_type_num = Db::name('user_participate_game_list')->where(['room_id'=>$room_id,'status'=>0,"team_type"=>$team_type])->count();
            if($team_type_num >= 5){
                $team_type == 1 && $msg = "红队已满";
                $team_type == 2 && $msg = "蓝队已满";
                return _result(false,$msg);
            }
            $data['team_type'] = $team_type;
        }

        if($participate_game_num >= $max_participate_num - 1){
            Db::name('game_room')->where('id',$room_id)->update(['house_full'=>1]);
        }
        Db::startTrans();
        try {
            $result = Db::name('user_game_ticket')->where(['uid'=>$uid,'id'=>$user_game_ticket['id']])->update(['status'=>1]);
            if(!$result){
                throw new Exception('参赛失败');
            }
            $result = Db::name('user_participate_game_list')->insert($data);
            if(!$result){
                throw new Exception('参赛失败');

            }
            Db::commit();
            return _result(true,'参赛成功');
        }catch (Exception $e){
            Db::rollback();
            return _result(false,$e->getMessage());
        }

    }


    /**
     * 获取分类
     * @param $class_id
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function getRoomClassify($class_id){
        $where['id'] = $class_id;
        $where['status'] = 1;
        $where['deleted'] = 0;
        $roomclassify = Db::name('roomclassify')->where($where)->field('id,title')->find();
        return $roomclassify;
    }



}