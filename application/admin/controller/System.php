<?php
namespace app\admin\controller;


use app\common\entity\ConfigPublishModel;
use app\common\entity\ConfigTeamLevelModel;
use app\common\entity\ConfigUserLevelModel;
use app\common\entity\PlatformSettingModel;
use think\Db;
use think\Request;

class System extends Admin
{
    /**
     *  系统设置|会员等级配置
     */
    public function index()
    {
        $list = ConfigUserLevelModel::select();
        return $this->render('index', [
            'list' => $list,
        ]);
    }
    /**
     *  系统设置|修改会员等级配置
     */
    public function editConfigUserLevel(Request $request)
    {
        $id = $request->post('id');
        $query = ConfigUserLevelModel::where('id',$id)->find();
        $res = $query->addNew($query,$request->post());
        if($res){
            return json()->data(['code' => 0,'message' => '修改成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     *  系统设置|团队等级配置
     */
    public function teamLevel()
    {
        $list = ConfigTeamLevelModel::select();
        return $this->render('teamLevel', [
            'list' => $list,
        ]);
    }
    /**
     *  系统设置|修改团队等级配置
     */
    public function editConfigTeamLevel(Request $request)
    {
        $id = $request->post('id');
        $query = ConfigTeamLevelModel::where('id',$id)->find();
        $res = $query->addNew($query,$request->post());
        if($res){
            return json()->data(['code' => 0,'message' => '修改成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     *  系统设置|付款方式配置
     */
    public function payWay(){

        $list = DB::table('config_money')
            ->where('id',1)
            ->find();

        return $this->render('payWay', [
            'list' => $list,
        ]);
    }
    /**
     *  系统设置|编辑付款方式配置
     */
    public function edit($id) {
        $entity = DB::table('config_money')
            ->where('id', $id)
            ->find();
        return $this->render('edit', [
            'info' => $entity,
        ]);
    }
    /**
     *  系统设置|编辑付款方式配置
     */
    public function update(Request $request, $id) {
        $post = $request->post();
        $res = DB::table('config_money')
            ->where('id',$id)
            ->update($post);
        if (!$res) {
            throw new AdminException('修改失败');
        }
        return json(['code' => 0,'message'=>'操作成功']);
    }
    /**
     *  系统设置|发布管理配置
     */
    public function publishSet()
    {
        $list = ConfigPublishModel::select();
        return $this->render('publishSet', [
            'list' => $list,
        ]);
    }
    /**
     *  系统设置|修改发布管理配置
     */
    public function editConfigPublish(Request $request)
    {
        $id = $request->post('id');
        $query = ConfigPublishModel::where('id',$id)->find();
        $res = $query->addNew($query,$request->post());
        if($res){
            return json()->data(['code' => 0,'message' => '修改成功']);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
}
