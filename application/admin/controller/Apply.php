<?php

namespace app\admin\controller;


use app\common\entity\ManageUser;
use app\common\entity\MyWallet;
use app\common\entity\RechargeModel;
use app\common\entity\UserOtherModel;
use app\common\entity\WithdrawalModel;
use app\common\service\Task\Service;
use app\common\entity\Export;
use think\Db;
use think\Request;

class Apply extends Admin {
    /**
     * 充值申请列表
     */
    public function recharge(Request $request)
    {
        $uid = session('mysite_admin')['id'];
        $left_uid = ManageUser::where('id',$uid)->value('left_uid');
        $next_id = $this->getNext($left_uid);
        $entity = RechargeModel::alias('r')
            ->leftJoin('user u','r.uid = u.id')
            ->field('r.*,u.nick_name,u.mobile');
        if ( $keyword = $request->get('keyword') ) {
            $type = $request->get('type');
            switch ($type) {
                case 'nick_name':
                    $entity->where('u.nick_name','like','%'. $keyword.'%');
                    break;
                case 'mobile':
                    $entity->where('u.mobile','like','%'. $keyword.'%');
                    break;
                case 'orderNo':
                    $entity->where('r.orderNo','like','%'. $keyword.'%');
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }
        if($status = $request->get('status')){
            $entity->where('r.status', $status);
            $map['status'] = $status;
        }
        if($types = $request->get('types')){
            $entity->where('r.types', $types);
            $map['types'] = $types;
        }
        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        if($startTime){
            $entity->where('r.create_time', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
        }
        if($endTime){
            $entity->where('r.create_time', '<', strtotime($endTime));
            $map['endTime'] = $endTime;
        }
        if($left_uid){
            $entity->whereIn('u.id',$next_id);
        }
        $orderStr = 'r.create_time DESC';
        $list = $entity
            ->order($orderStr)
            ->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ]);

        return $this->render('recharge', [
            'list' => $list,
            'allStatus' => RechargeModel::getAllStatus(),
            'allTypes' => RechargeModel::getAllTypes(),
            'queryStr' => isset($map) ? http_build_query($map) : '',
        ]);
    }
    public function exportRecharge(Request $request)
    {
        $page = $request->get('page')? $request->get('page'):0;
        $export = new Export();
        $entity = RechargeModel::alias('r')
            ->leftJoin('user u','r.uid = u.id')
            ->field('r.*,u.nick_name,u.mobile');
        if ( $keyword = $request->get('keyword') ) {
            $type = $request->get('type');
            switch ($type) {
                case 'nick_name':
                    $entity->where('u.nick_name','like','%'. $keyword.'%');
                    break;
                case 'mobile':
                    $entity->where('u.mobile','like','%'. $keyword.'%');
                    break;
                case 'orderNo':
                    $entity->where('r.orderNo','like','%'. $keyword.'%');
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }
        if($status = $request->get('status')){
            $entity->where('r.status', $status);
            $map['status'] = $status;
        }
        if($types = $request->get('types')){
            $entity->where('r.types', $types);
            $map['types'] = $types;
        }
        $orderStr = 'r.create_time DESC';
        $list = $entity
            ->page($page)
            ->order($orderStr)
            ->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ]);
        $allStatus = RechargeModel::getAllStatus();
        $allTypes = RechargeModel::getAllTypes();
        $url = $request->domain();
        foreach ($list as $v){
            $v['status_info'] =   $allStatus[$v->status];
            $v['types_info'] =   $allTypes[$v->types];
            $v['proof_info'] =   $url.$v->proof;
        }
        $filename = '充值申请列表';
        $header = array('ID', '手机号', '订单号', '充值方式', '充值金额', '充值凭证', '审核状态', '时间');
        $index = array('id', 'mobile', 'orderNo', 'types_info', 'total', 'proof_info', 'status_info', 'create_time');
        $export->createtable($list, $filename, $header, $index);
    }
    /**
     * 通过充值申请|拒绝充值申请
     */
    public function examineRecharge(Request $request)
    {
        $id = $request->param('id');
        $types = $request->param('types');
        if(!RechargeModel::checkExist($id)){
            return json()->data(['code' => 1, 'message' => '非法操作']);
        }
        if($types == 'pass'){//通过申请

            $info = RechargeModel::where('id',$id)->find();
            $way = RechargeModel::getAllTypes()[$info['types']];
            Db::startTrans();
            try {

                $data = [
                    'num'  => $info['total'],
                    'uid'  => $info['uid'],
                    'remark'  => '会员'.$way.'充值',
                ];
                $query = new MyWallet();
                $res = $query->RechargeLog($query,$data);
                if(!$res){
                    Db::rollback();
                    return json()->data(['code' => 1, 'message' => '审核失败']);
                }
                RechargeModel::where('id',$id)->update([
                    'status' => 2,
                    'update_time' => time(),
                ]);
                $userModel = new \app\common\entity\User();
                $all_user = $userModel->getParents($info['uid'],3);
                $all_user[] = $info['uid'];
                $service = new Service();
                $aa = $service->upgrade($all_user);
                Db::commit();
                return json(['code' => 0, 'toUrl' => url('/admin/Apply/recharge')]);
            }catch (\Exception $e){
                Db::rollback();
                return json()->data(['code' => 1, 'message' => '审核失败']);
            }
        }elseif ($types == 'refuse'){//拒绝申请
            $res = RechargeModel::where('id',$id)->update([
                'status' => 3,
                'update_time' => time(),
            ]);
            if($res){
                return json(['code' => 0, 'toUrl' => url('/admin/Apply/recharge')]);
            }
            return json()->data(['code' => 1, 'message' => '审核失败']);
        }
    }
    /**
     * 删除产品
     */
    public function deleteRecharge(Request $request)
    {
        $id = $request->param('id');
        if(!RechargeModel::checkExist($id)){
            return json()->data(['code' => 1, 'message' => '非法操作']);
        }
        $res = RechargeModel::where('id',$id)->delete();
        if($res){
            return json(['code' => 0, 'toUrl' => url('/admin/Apply/recharge')]);
        }
        return json()->data(['code' => 1, 'message' => '删除失败']);
    }
    /**
     * 提现申请列表
     */
    public function withdrawal(Request $request)
    {
        $uid = session('mysite_admin')['id'];
        $left_uid = ManageUser::where('id',$uid)->value('left_uid');
        $next_id = $this->getNext($left_uid);
        $entity = WithdrawalModel::alias('w')
            ->leftJoin('user u','w.uid = u.id')
            ->field('w.*,u.nick_name,u.mobile');
        if ( $keyword = $request->get('keyword') ) {
            $type = $request->get('type');
            switch ($type) {
                case 'nick_name':
                    $entity->where('u.nick_name','like','%'. $keyword.'%');
                    break;
                case 'mobile':
                    $entity->where('u.mobile','like','%'. $keyword.'%');
                    break;
                case 'orderNo':
                    $entity->where('w.orderNo','like','%'. $keyword.'%');
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }
        if($status = $request->get('status')){
            $entity->where('w.status', $status);
            $map['status'] = $status;
        }
        if($types = $request->get('types')){
            $entity->where('w.types', $types);
            $map['types'] = $types;
        }
        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        if($startTime){
            $entity->where('w.create_time', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
        }
        if($endTime){
            $entity->where('w.create_time', '<', strtotime($endTime));
            $map['endTime'] = $endTime;
        }
        if($left_uid){
            $entity->whereIn('u.id',$next_id);
        }
        $orderStr = 'w.create_time DESC';
        $list = $entity
            ->order($orderStr)
            ->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ]);

        return $this->render('withdrawal', [
            'list' => $list,
            'allStatus' => WithdrawalModel::getAllStatus(),
            'allTypes' => WithdrawalModel::getAllTypes(),
            'queryStr' => isset($map) ? http_build_query($map) : '',
        ]);
    }
    public function exportWithdrawal(Request $request){
        $page = $request->get('page',0);
        $export = new Export();
        $entity = WithdrawalModel::alias('w')
            ->leftJoin('user u','w.uid = u.id')
            ->field('w.*,u.mobile');
        if ( $keyword = $request->get('keyword') ) {
            $type = $request->get('type');
            switch ($type) {
                case 'nick_name':
                    $entity->where('u.nick_name','like','%'. $keyword.'%');
                    break;
                case 'mobile':
                    $entity->where('u.mobile','like','%'. $keyword.'%');
                    break;
                case 'orderNo':
                    $entity->where('w.orderNo','like','%'. $keyword.'%');
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }
        if($status = $request->get('status')){
            $entity->where('w.status', $status);
            $map['status'] = $status;
        }
        if($types = $request->get('types')){
            $entity->where('w.types', $types);
            $map['types'] = $types;
        }
        $orderStr = 'w.create_time DESC';
        $list = $entity
            ->order($orderStr)
            ->page($page)
            ->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ])
            ->toArray();
        $allTypes = WithdrawalModel::getAllTypes();
        $allStatus = WithdrawalModel::getAllStatus();
        $data =[];
        foreach ($list['data'] as $k=>$v){
            $data[$k]['id'] = $v['id'];
            $data[$k]['mobile'] = $v['mobile'];
            $data[$k]['types_info'] = $allTypes[$v['types']];
            $data[$k]['money'] = $v['money'];
            $data[$k]['realmoney'] = $v['realmoney'];
            $data[$k]['bank_user_name'] = $v['bank_user_name'];
            $data[$k]['bank_name'] = $v['bank_name'];
            $data[$k]['bank_card'] = $v['bank_card'];
            $data[$k]['alipay_name'] = $v['alipay_name'];
            $data[$k]['alipay_account'] = $v['alipay_account'];
            $data[$k]['status_info'] = $allStatus[$v['status']];
            $data[$k]['create_time'] = $v['create_time'];
        }
        $filename = '提现申请列表';
        $header = array('ID', '手机号', '提现方式', '提现金额', '实际到账金额', '开户人', '开户行','银行卡号','支付宝姓名','支付宝账号','审核状态','时间');
        $export->exportExcel($header,$data,'Sheet1',$filename);
    }
    /**
     * 通过提现申请|拒绝提现申请
     */
    public function examineWithdrawal(Request $request)
    {
        $id = $request->param('id');
        $types = $request->param('types');
        if(!WithdrawalModel::checkExist($id)){
            return json()->data(['code' => 1, 'message' => '非法操作']);
        }
        if($types == 'pass'){//通过申请
            $res = WithdrawalModel::where('id',$id)->update([
                'status' => 2,
                'update_time' => time(),
            ]);
            if($res) {
                return json(['code' => 0, 'toUrl' => url('/admin/Apply/withdrawal')]);
            }
            return json()->data(['code' => 1, 'message' => '审核失败']);
        }elseif ($types == 'refuse'){//拒绝申请
            $info = WithdrawalModel::where('id',$id)->find();
            $way = WithdrawalModel::getAllTypes()[$info['types']];
            Db::startTrans();
            try {
                $data = [
                    'num'  => $info['total'],
                    'uid'  => $info['uid'],
                    'remark'  => '拒绝会员'.$way.'提现，退还',
                ];
                $query = new MyWallet();
                $res = $query->refuseWithdrawal($query,$data);
                if(!$res){
                    Db::rollback();
                    return json()->data(['code' => 1, 'message' => '审核失败']);
                }
                WithdrawalModel::where('id',$id)->update([
                    'status' => 3,
                    'update_time' => time(),
                ]);
                Db::commit();
                return json(['code' => 0, 'toUrl' => url('/admin/Apply/withdrawal')]);
            }catch (\Exception $e){
                Db::rollback();
                return json()->data(['code' => 1, 'message' => '审核失败']);
            }
        }
    }
    /**
     * 删除提现申请
     */
    public function deleteWithdrawal(Request $request)
    {
        $id = $request->param('id');
        if(!WithdrawalModel::checkExist($id)){
            return json()->data(['code' => 1, 'message' => '非法操作']);
        }
        $res = WithdrawalModel::where('id',$id)->delete();
        if($res){
            return json(['code' => 0, 'toUrl' => url('/admin/Apply/withdrawal')]);
        }
        return json()->data(['code' => 1, 'message' => '删除失败']);
    }
    /**
     * 抖音快手审核
     */
    public function other(Request $request)
    {
        $uid = session('mysite_admin')['id'];
        $left_uid = ManageUser::where('id',$uid)->value('left_uid');
        $next_id = $this->getNext($left_uid);
        $entity = UserOtherModel::alias('uo')
            ->leftJoin('user u','uo.uid = u.id')
            ->field('uo.*,u.nick_name,u.mobile');
        if ( $keyword = $request->get('keyword') ) {
            $type = $request->get('type');
            switch ($type) {
                case 'nick_name':
                    $entity->where('u.nick_name','like','%'. $keyword.'%');
                    break;
                case 'mobile':
                    $entity->where('u.mobile','like','%'. $keyword.'%');
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }
        if($status = $request->get('status')){
            $entity->where('uo.status', $status);
            $map['status'] = $status;
        }
        if($types = $request->get('types')){
            $entity->where('uo.types', $types);
            $map['types'] = $types;
        }
        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        if($startTime){
            $entity->where('uo.create_time', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
        }
        if($endTime){
            $entity->where('uo.create_time', '<', strtotime($endTime));
            $map['endTime'] = $endTime;
        }
        if($left_uid){
            $entity->whereIn('u.id',$next_id);
        }
        $orderStr = 'uo.create_time DESC';
        $list = $entity
            ->order($orderStr)
            ->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ]);
        return $this->render('other', [
            'list' => $list,
            'allStatus' => UserOtherModel::getAllStatus(),
            'allTypes' => UserOtherModel::getAllTypes(),
            'queryStr' => isset($map) ? http_build_query($map) : '',
        ]);
    }
    /**
     * 抖音快手审核
     */
    public function examineOther(Request $request)
    {
        $id = $request->param('id');
        $types = $request->param('types');
        if(!UserOtherModel::checkExist($id)){
            return json()->data(['code' => 1, 'message' => '非法操作']);
        }
        $otherInfo = UserOtherModel::where('id',$id)->find();
        if($otherInfo['types'] == 1){//抖音
            $field = 'tiktok_status';
        }elseif ($otherInfo['types'] == 2){//快手
            $field = 'kwaifu_status';
        }
        if($types == 'pass'){//通过申请
            $res = UserOtherModel::where('id',$id)->update([
                'status' => 2,
                'examine_time' => time(),
            ]);
            if(!$res) {
                return json()->data(['code' => 1, 'message' => '审核失败']);
            }
            $res = \app\common\entity\User::where('id',$otherInfo['uid'])
                ->setField($field,3);
            if(!$res) {
                return json()->data(['code' => 1, 'message' => '审核失败']);
            }
            return json(['code' => 0, 'toUrl' => url('/admin/Apply/other')]);
        }elseif ($types == 'refuse'){//拒绝申请
            $res = UserOtherModel::where('id',$id)->update([
                'status' => 3,
                'examine_time' => time(),
            ]);
            if(!$res) {
                return json()->data(['code' => 1, 'message' => '审核失败']);
            }
            $res = \app\common\entity\User::where('id',$otherInfo['uid'])
                ->setField($field,1);
            if(!$res) {
                return json()->data(['code' => 1, 'message' => '审核失败']);
            }
            return json(['code' => 0, 'toUrl' => url('/admin/Apply/other')]);
        }
    }
}
