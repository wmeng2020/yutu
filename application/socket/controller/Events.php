<?php
namespace app\socket\controller;


/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
//declare(ticks=1);

/**
 * 聊天主逻辑
 * 主要是处理 onMessage onClose 
 */

use app\socket\controller\Task;
use \GatewayWorker\Lib\Gateway;
use think\Controller;
use redis\RedisCluster;
use think\Db;
use Workerman\Lib\Timer;

class Events extends Controller
{
    const ROOMID = "chat_room_id";
    const USERID = "chat_room_uid";
    const TIMER = "chat_room_timer";


   /**
    * 有消息时
    * @param int $client_id
    * @param mixed $message
    */
   public static function onMessage($client_id, $message)
   {

       // debug
        echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} gateway:{$_SERVER['GATEWAY_ADDR']}:{$_SERVER['GATEWAY_PORT']}  client_id:$client_id session:".json_encode($_SESSION)." onMessage:".$message."\n";
       $room_key = md5(self::ROOMID);
       $user_key = md5(self::USERID);
       $timer_key = md5(self::TIMER);

        // 客户端传递的是json数据
        $message_data = json_decode($message, true);
        if(!$message_data)
        {
            return ;
        }
       $RedisCluster = new RedisCluster();
       $redis = $RedisCluster->getRedis();
        // 根据类型执行不同的业务
        switch($message_data['type'])
        {
            // 客户端回应服务端的心跳
            case 'pong':
                return;

            case 'renew':
                $room_id = $_SESSION[$room_key];
                $room = Db::name('chat_room')->where('id',$room_id)->find();
                $uid = $_SESSION[$user_key];
                $timer_time = ($room['duration'] * 60);
                if(!isset($_SESSION[$timer_key])){
                    $_SESSION[$timer_key] = 0;
                }
                $last_timer_id = $_SESSION[$timer_key];
                if($last_timer_id){
                    Timer::del($last_timer_id);
                    $_SESSION[$timer_key] = "";
                }
                $timer_id = Timer::add($timer_time, function() use($room_id,$uid){
                    Gateway::sendToGroup($room_id,json_encode(['type'=>'is_time','uid'=>$uid]));
                }, array(), false);
                $_SESSION[$timer_key] = $timer_id;
//                Gateway::sendToCurrentClient(json_encode(['type'=>'renew','uid'=>$uid]));
                return;
            // 客户端登录 message格式: {type:login, name:xx, room_id:1} ，添加到客户端，广播给所有客户端xx进入聊天室
            case 'login':
                // 判断是否有房间号
                if(!isset($message_data['room_id']))
                {
                    throw new \Exception("\$message_data['room_id'] not set. client_ip:{$_SERVER['REMOTE_ADDR']} \$message:$message");
                }
                // 判断是否有uid
                if(!isset($message_data['uid']))
                {
                    throw new \Exception("\$message_data['uid'] not set. client_ip:{$_SERVER['REMOTE_ADDR']} \$message:$message");
                }
                // 把房间号昵称放到session中
                $room_id = $message_data['room_id'];
                $uid = $message_data['uid'];
                $client_avatar = $message_data['client_avatar'];
                $is_deduction = $message_data['is_deduction'];
                $room = Db::name('chat_room')->where('id',$room_id)->find();
                $user = Db::name('user')->where('id',$uid)->find();
                if(empty($room)){
                    throw new \Exception("\$room not set. client_ip:{$_SERVER['REMOTE_ADDR']} \$message:$message");
                }
                if(empty($user)){
                    throw new \Exception("\$user not set. client_ip:{$_SERVER['REMOTE_ADDR']} \$message:$message");
                }
                $client_name = htmlspecialchars($message_data['client_name']);

                $_SESSION[$room_key] = $room_id;
                $_SESSION[$user_key] = $uid;
                $_SESSION['client_name'] = $client_name;
                $_SESSION['client_avatar'] = $client_avatar;
                $chat_record = $redis->get(md5($room_id.'chat_record'));
                // 获取房间内所有用户列表
                $clients_list = Gateway::getClientSessionsByGroup($room_id);
                if(!empty($clients_list)){
                    foreach($clients_list as $tmp_client_id=>$item)
                    {
                        $clients_list[$tmp_client_id] = $item['client_name'];
                    }
                    $clients_list[$client_id] = $client_name;
                }

                
                // 转播给当前房间的所有客户端，xx进入聊天室 message {type:login, client_id:xx, name:xx} 
                $new_message = array('type'=>$message_data['type'],'uid'=>$uid, 'client_id'=>$client_id, 'client_avatar'=>$client_avatar,'client_name'=>htmlspecialchars($client_name),'chat_record'=>$chat_record, 'time'=>date('Y-m-d H:i:s'));
                Gateway::sendToGroup($room_id, json_encode($new_message));
                Gateway::joinGroup($client_id, $room_id);

                // 给当前用户发送用户列表
                $new_message['client_list'] = $clients_list;
                Gateway::sendToGroup($room_id,json_encode($new_message));
//                Gateway::sendToCurrentClient(json_encode($new_message));
                if($is_deduction){
                    //进程启动开启定时任务
                    $timer_time = ($room['duration'] * 60);
                    if(!isset($_SESSION[$timer_key])){
                        $_SESSION[$timer_key] = 0;
                    }
                    $last_timer_id = $_SESSION[$timer_key];
                    if($last_timer_id){
                        Timer::del($last_timer_id);
                        $_SESSION[$timer_key] = "";
                    }
                    $timer_id = Timer::add($timer_time, function() use($room_id,$uid){
                        Gateway::sendToGroup($room_id,json_encode(['type'=>'is_time','uid'=>$uid]));
                    }, array(), false);
                    $_SESSION[$timer_key] = $timer_id;
                }

                return;
            case 'image':
                // 非法请求
                if(!isset($_SESSION[$room_key]))
                {
                    throw new \Exception("\$_SESSION['room_id'] not set. client_ip:{$_SERVER['REMOTE_ADDR']}");
                }
                $room_id = $_SESSION[$room_key];
                $client_name = $_SESSION['client_name'];
                $client_avatar = $_SESSION['client_avatar'];
                $uid = $_SESSION[$user_key];
                $img_path = $message_data['img_path'];
                $new_message = array(
                    'type'=>'image',
                    'from_client_id'=>$client_id,
                    'uid'=>$uid,
                    'from_client_name' =>$client_name,
                    'from_client_avatar' =>$client_avatar,
                    'to_client_id'=>'all',
                    'content'=>$img_path,
                    'time'=>date('Y-m-d H:i:s',time()),
                );
                $chat_record = $redis->get(md5($room_id.'chat_record'));
                $is_expire = false;
                if(!empty($chat_record)){
                    $chat_record = json_decode($chat_record,true);
                    $is_expire = true;
                }else{
                    $chat_record = [];
                }
                $chat_record[] = $new_message;
                $chat_record = json_encode($chat_record);
                $redis->set(md5($room_id.'chat_record'),$chat_record);
                if($is_expire){
                    $redis->expire(md5($room_id.'chat_record'),7200);
                }
                return Gateway::sendToGroup($room_id ,json_encode($new_message));

                
            // 客户端发言 message: {type:say, to_client_id:xx, content:xx}
            case 'say':
                // 非法请求
                if(!isset($_SESSION[$room_key]))
                {
                    throw new \Exception("\$_SESSION['room_id'] not set. client_ip:{$_SERVER['REMOTE_ADDR']}");
                }
                $room_id = $_SESSION[$room_key];
                $client_name = $_SESSION['client_name'];
                $client_avatar = $_SESSION['client_avatar'];
                $uid = $_SESSION[$user_key];

                // 私聊
                if($message_data['to_client_id'] != 'all')
                {
                    $new_message = array(
                        'type'=>'say',
                        'from_client_id'=>$client_id, 
                        'from_client_name' =>$client_name,
                        'to_client_id'=>$message_data['to_client_id'],
                        'content'=>"<b>对你说: </b>".nl2br(htmlspecialchars($message_data['content'])),
                        'time'=>date('Y-m-d H:i:s',time()),
                    );
                    Gateway::sendToClient($message_data['to_client_id'], json_encode($new_message));
                    $new_message['content'] = "<b>你对".htmlspecialchars($message_data['to_client_name'])."说: </b>".nl2br(htmlspecialchars($message_data['content']));
                    return Gateway::sendToCurrentClient(json_encode($new_message));
                }
                
                $new_message = array(
                    'type'=>'say', 
                    'from_client_id'=>$client_id,
                    'uid'=>$uid,
                    'from_client_name' =>$client_name,
                    'from_client_avatar' =>$client_avatar,
                    'to_client_id'=>'all',
                    'content'=>nl2br(htmlspecialchars($message_data['content'])),
                    'time'=>date('Y-m-d H:i:s',time()),
                );
                $chat_record = $redis->get(md5($room_id.'chat_record'));
                $is_expire = false;
                if(!empty($chat_record)){
                    $chat_record = json_decode($chat_record,true);
                    $is_expire = true;
                }else{
                    $chat_record = [];
                }
                $chat_record[] = $new_message;
                $chat_record = json_encode($chat_record);
                $redis->set(md5($room_id.'chat_record'),$chat_record);
                if($is_expire){
                    $redis->expire(md5($room_id.'chat_record'),7200);
                }
                return Gateway::sendToGroup($room_id ,json_encode($new_message));
        }
   }
   
   /**
    * 当客户端断开连接时
    * @param integer $client_id 客户端id
    */
   public static function onClose($client_id)
   {
       // debug
       echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} gateway:{$_SERVER['GATEWAY_ADDR']}:{$_SERVER['GATEWAY_PORT']}  client_id:$client_id onClose:''\n";
       $room_key = md5(self::ROOMID);
       // 从房间的客户端列表中删除
       if(isset($_SESSION[$room_key]))
       {
           echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} gateway:{$_SERVER['GATEWAY_ADDR']}:{$_SERVER['GATEWAY_PORT']}  client_id:$client_id session:".json_encode($_SESSION)." onMessage:"."\n";
           $room_id = $_SESSION[$room_key];
           $new_message = array('type'=>'logout', 'from_client_id'=>$client_id, 'from_client_name'=>$_SESSION['client_name'], 'time'=>date('Y-m-d H:i:s'));
           Gateway::sendToGroup($room_id, json_encode($new_message));
       }
   }
  
}
