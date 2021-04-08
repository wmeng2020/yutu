<?php
namespace app\socket\controller;
use app\index\controller\Base;
use app\index\model\User;
use think\Db;
use think\Exception;
use think\Request;

class Index extends Base
{
    public function index()
    {
    	return $this->fetch('worker');
    }

    /**
     * 获取登录Data
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getLogindata(){
        $uid = $this->userId;
        $user = Db::name('user')->where('id',$uid)->find();
        $data = [
            'type'=>"login",
            'uid'=>$uid,
            'client_avatar'=>$user['avatar'] ? $user['avatar'] : " ",
            'client_name'=>$user['nick_name'] ? $user['nick_name'] : "游客",
        ];
        return _result(true,'success',$data);
    }

    /**
     * 获取是否可以加入某个聊天室
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function canJoinRoom(Request $request){
        $room_id = intval($request->post('room_id'));
        if(empty($room_id)){
            return _result(false,"请选择聊天室");
        }
        $condition = ['deleted'=>0,'id'=>$room_id];
        $room = Db::name('chat_room')->where($condition)->find();
        if(empty($room)){
            return _result(false,"聊天室不存在");
        }
        $room_join_num = Db::name('user_join_room')->where(['deductiontime'=>['>=',time()],'room_id'=>$room_id])->count();
        if($room['limit_num'] <= $room_join_num){
            return _result(true,"该聊天室已满人",['is_can'=>0]);
        }
        return _result(true,"欢迎加入聊天室",['is_can'=>1]);
    }

    /**
     * 进入聊天室扣除钻石
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function deductionUserCredit1(Request $request){
        Db::startTrans();
        try {
            $room_id = $request->post('room_id');
            $uid = $this->userId;
            $room = Db::name('chat_room')->where('id',$room_id)->find();
            $user_model = new User();
            $commission = json_decode($room['commission'],true);
            if(!empty($commission)){
                $remarks = ['msg'=>"推广佣金",'uid'=>$uid];
                $agents = [];
                $user_model->getUserAgents($uid,$agents,3);
                if(!empty($agents)){
                    foreach ($agents as $key =>$value){
                        if(sprintf("%.2f",$commission[$key]) > 0){
                            $result = $user_model->setUserWallet($value['id'],"commission",9,2,sprintf("%.2f",$commission[$key]),$remarks);
                            if($result['code'] == 400){
                                throw new Exception($result['msg']);
                                break;
                            }
                        }
                    }
                    unset($value);
                }
            }
            if($room['credit1'] > 0){
                $result = $user_model->setUserWallet($uid,'credit1',8,1,$room['credit1'],'聊天室消耗钻石');
                if($result['code'] == 400){
                    throw new Exception($result['msg']);
                }
            }
            $times = time();
            $data = [
                'uid'=>$uid,
                'room_id'=>$room_id,
                'createtime'=>$times,
                'deductiontime'=>$times + ($room['duration'] * 60),
            ];
            $result = Db::name('user_join_room')->insert($data);
            if(!$result){
                throw new Exception("消费失败");
            }
            Db::commit();
            return _result(true,'消费成功');
        }catch (Exception $e){
            Db::rollback();
            return _result(false,$e->getMessage());

        }
    }

    /**
     * 获取房间列表
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getRommList(Request $request){
        $page = max(1,intval($request->post('page')));
        $keyword = trim($request->post('keyword'));
        $game_type = intval($request->post('game_type'));
        if(empty($game_type)){
            return _result(false,"请选择聊天室类型");
        }
        if(!in_array($game_type,[1,2])){
            return _result(false,"请选择聊天室类型");
        }

        $condition = ['deleted'=>0,'game_type'=>$game_type];
        if(!empty($keyword)){
            $condition['roomname'] = ['like',"%".$keyword."%"];
        }
        $psize = 10;
        $list = Db::name('chat_room')->where($condition)->order('displayorder desc,id desc')->page($page,$psize)->select();
        foreach ($list as $key => &$value){
            $value['createtime'] = date('Y-m-d H:i',$value['createtime']);
            $list[$key]['room'] = DB::table('user_join_room')->where('room_id',$value['id'])->group('uid')->where('deductiontime','>=',time())->where('createtime','<=',time())->count();
        }
        
        unset($value);
        return _result(true,'success',$list);
    }

    /**
     * 房间详情
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getRoomDetail(Request $request){
        $room_id = intval($request->post('room_id'));
        if(empty($room_id)){
            return _result(false,"请选择聊天室");
        }
        $condition = ['deleted'=>0,'id'=>$room_id];
        $list = Db::name('chat_room')->where($condition)->find();
        $list['content'] = htmlspecialchars_decode($list['content']);
        return _result(true,'success',$list);
    }

    /**
     * 获取用户扣费状态
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserConsumptionStatus(Request $request){
        $uid = $this->userId;
        $room_id = intval($request->post('room_id'));
        $user_join_room = Db::name('user_join_room')->where(['uid'=>$uid,'room_id'=>$room_id])->order('id desc ')->find();
        $is_deduction = 1;
        if(!empty($user_join_room)){
            $deductiontime = $user_join_room['deductiontime'];
            if(time() < $deductiontime){
                $is_deduction = 0;
            }
        }
        return _result(true,'success',['is_deduction'=>$is_deduction]);
    }




       
}
