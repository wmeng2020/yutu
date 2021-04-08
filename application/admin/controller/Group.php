<?php
namespace app\admin\controller;

use app\admin\exception\AdminException;
use app\common\entity\ManageGroup;
use app\common\entity\ManageMenu;
use think\Db;
use think\Request;

class Group extends Admin
{
    /**
     * @power 权限管理|分组管理
     * @rank 2
     */
    public function index()
    {
        return $this->render('index', [
            'list' => ManageGroup::paginate(15)
        ]);
    }

    /**
     * @power 权限管理|分组管理@添加分组
     */
    public function create()
    {
        return $this->render('edit', [
            'auths' => ManageMenu::all()
        ]);
    }

    /**
     * @power 权限管理|分组管理@编辑分组
     */
    public function edit($id)
    {
        $info = ManageGroup::where('id', $id)->find();
        if (!$info) {
            return json(['code' => 1 ,'message' => '对象不存在']);
        }
        if ($info->isDefault()) {
            return json(['code' => 1 ,'message' => '默认分组不能编辑']);
        }
        return $this->render('edit', [
            'auths' => ManageMenu::all(),
            'info' => $info,
            'authIds' => $info->getAuthIds() != 'all' ? explode(',', $info->getAuthIds()) : []
        ]);
    }

    /**
     * @power 权限管理|分组管理@添加分组
     */
    public function save(Request $request)
    {
        if (\app\admin\service\rbac\Group\Service::checkName($request->post('name'))) {
            return json(['code' => 1 ,'message' => '分组名称已存在']);
        }

        $result = \app\admin\service\rbac\Group\Service::addGroup([
            'group_name' => $request->post('name'),
            'auth_ids' => implode(',', $request->post()['authIds'])
        ]);

        if ($result) {
            return json(['code' => 0, 'toUrl' => url('admin/group/index')]);
        }
        return json(['code' => 1 ,'message' => '保存失败']);
    }

    /**
     * @power 权限管理|分组管理@编辑分组
     */
    public function update(Request $request, $id)
    {
        $entity = $this->checkInfo($id);

        if (\app\admin\service\rbac\Group\Service::checkName($request->post('name'), $id)) {
            return json(['code' => 1 ,'message' => '分组名称已存在']);
        }

        $result = \app\admin\service\rbac\Group\Service::updateGroup([
            'group_name' => $request->post('name'),
            'auth_ids' => implode(',', $request->post()['authIds'])
        ], $entity);

        if (is_int($result)) {
            return json(['code' => 0, 'toUrl' => url('admin/group/index')]);
        }
        return json(['code' => 1 ,'message' => '保存失败']);
    }

    /**
     * @power 权限管理|分组管理@删除分组
     */
    public function delete($id)
    {
        $entity = $this->checkInfo($id);

        $userIds = \app\common\entity\ManageUserGroup::getUsersByGroupId($id);

        if (count($userIds) > 0) {
            return json(['code' => 1 ,'message' => '该分组下面还存在用户，不能删除']);
        }

        if (!$entity->delete()) {
            return json(['code' => 1 ,'message' => '删除失败']);
        }
        return json(['code' => 0, 'message' => 'success']);
    }

    /**
     * @power 权限管理|分组管理@强制删除分组
     * @method POST
     */
    public function force($id)
    {
        $entity = $this->checkInfo($id);

        Db::startTrans();

        try {
            $userIds = \app\common\entity\ManageUserGroup::getUsersByGroupId($id);

            \app\common\entity\ManageUserGroup::deleteBygroupId($id);

            if (!$entity->delete()) {
                return json(['code' => 1 ,'message' => '删除失败']);
            }

            if (!\app\common\entity\ManageUser::destroy($userIds)) {
                return json(['code' => 1 ,'message' => '删除失败']);
            }

            DB::commit();
            return json(['code' => 0, 'message' => 'success']);

        } catch (\Exception $e) {
            DB::rollback();
            throw new AdminException($e->getMessage());
        }

    }

    private function checkInfo($id)
    {
        $entity = ManageGroup::where(['id' => $id])->find();
        if (!$entity) {
            return json(['code' => 1 ,'message' => '对象不存在']);
        }
        if ($entity->isDefault()) {
            return json(['code' => 1 ,'message' => '默认用户不能编辑']);
        }

        return $entity;
    }
}