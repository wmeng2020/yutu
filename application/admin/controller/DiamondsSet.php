<?php

namespace app\admin\controller;

use app\admin\exception\AdminException;
use app\common\entity\Export;
use think\Db;
use think\Request;
use app\common\entity\User;

class DiamondsSet extends Admin {

    #|绑定帐号步骤
    public function index(){
        $list = Db::table('diamonds_set')->select();
        return $this->render('index',[
            'list' => $list
        ]);
    }

    /**
     * @power 内容管理|文章管理@添加文章
     */
    public function create() {
        return $this->render('edit', [
        ]);
    }

    /**
     * @power 内容管理|文章管理@编辑文章
     */
    public function edit($id) {
        $entity = Db::name('diamonds_set')->where('id',$id)->find();
        if (!$entity) {
            $this->error('用户对象不存在');
        }

        return $this->render('edit', [
            'info' => $entity,
        ]);
    }

    /**
     * @power 内容管理|文章管理@添加文章
     */
    public function save(Request $request) {


        $money = $request->post('money');
        $num = $request->post('num');
        $op_type = intval($request->post('op_type'));
        if(empty($money)){
            return json()->data(['code' => 1, 'message' => "请填写金额"]);
        }
        if(empty($num)){
            return json()->data(['code' => 1, 'message' => "请填写钻石数量"]);
        }
        Db::name('diamonds_set')->insert(['num'=>$num,'money'=>$money,'op_type'=>$op_type,'createtime'=>time()]);

        return json(['code' => 0, 'toUrl' => url('/admin/diamonds_set/index')]);
    }

    /**
     * @power 内容管理|文章管理@编辑文章
     */
    public function update(Request $request, $id) {
        $entity = Db::name('diamonds_set')->where('id',$id)->find();
        if(empty($entity)){
            return json()->data(['code' => 1, 'message' => "修改对象不存在"]);
        }
        $money = $request->post('money');
        $num = $request->post('num');
        $op_type = intval($request->post('op_type'));
        if(empty($money)){
            return json()->data(['code' => 1, 'message' => "请填写金额"]);
        }
        if(empty($num)){
            return json()->data(['code' => 1, 'message' => "请填写钻石数量"]);
        }
        $data = ['num'=>$num,'money'=>$money,'op_type'=>$op_type];
        Db::name('diamonds_set')->where('id',$id)->update($data);

        return json(['code' => 0, 'toUrl' => url('/admin/diamonds_set/index')]);
    }

    /**
     * 导出留言
     */
    public function exportMessage(Request $request) {
        $export = new Export();
        $entity = \app\common\entity\Message::field('m.*,u.mobile, u.nick_name')->alias('m');
        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'mobile':
                    $entity->where('u.mobile', $keyword);
                    break;
                case 'nick_name':
                    $entity->where('u.nick_name', $keyword);
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }
        $list = $entity->leftJoin("user u", 'm.user_id = u.id')
            ->order('m.create_time', 'desc')
            ->select();
        $filename = '留言列表';
        $header = array('会员昵称', '会员账号', '内容', '提交时间');
        $index = array('nick_name', 'mobile', 'content', 'create_time');
        $export->createtable($list, $filename, $header, $index);
    }

    /**
     * @power 内容管理|文章管理@删除文章
     */
    public function delete(Request $request, $id) {
        $entity = Db::name('diamonds_set')->where('id',$id)->find();

        if (!$entity) {
            throw new AdminException('对象不存在');
        }
        $result = Db::name('diamonds_set')->where('id',$id)->delete();
        if(!$result){
            return json(['code' => 1, 'message' => '删除失败']);
        }

        return json(['code' => 0, 'message' => 'success']);
    }

    private function checkInfo($id) {
        $entity = Db::name('diamonds_set')->where('id',$id)->find();

        if (!$entity) {
            throw new AdminException('对象不存在');
        }

        return $entity;
    }

    /**
     * 视频添加
     */
    public function videoadd()
    {
        $info = Video::find();
        return $this->render('videoadd',[
            'info' => $info,
        ]);
    }
    /**
     * 视频保存
     */
    public function videoSave(Request $request)
    {
        $photo = $request->post('photo');
        $add_data = [
            'src' => $photo,
            'create_time' => time(),
        ];
        if(!$photo) return json(['code' => 1, 'message' => '请选择视频']);
        $list = Video::select();
        foreach ($list as $v){

            if( file_exists('.'.$v['src'])){
                unlink('.'.$v['src']);
            }
            Video::where('id',$v['id'])->delete();
        }

        $res = Video::insert($add_data);
        if($res){
            return json(['code' => 0, 'message' => '添加成功']);
        }
        return json(['code' => 1, 'message' => '添加失败']);

    }


    #内容管理|图片编辑
    public function imageedit(Request $request){
        $id = $request->param('id');
        $list = Db::table('spread_image')->where('id',$id)->find();

        return $this->render('imageedit',[
            'info' => $list
        ]);
    }

    #图片修改
    public function updimage(Request $request)
    {
        $id = $request->param('id');
        $title = $request->post('title');

        $photo = $request->post('photo');
        $sort = $request->post('sort');

        $data = [
            'pic' => $photo,
            'sort' => $sort,
            'title' => $title,
            'update_time' => time()
        ];

        $updphoto = Db::table('spread_image')->where('id',$id)->update($data);
        if ($updphoto){

            return json(['code' => 0, 'message' => '修改成功','toUrl'=>url('image')]);

        }

        return json(['code' => 1, 'message' => '修改失败']);

    }

    #内容管理|图片添加
    public function imageadd(){
        return $this->render('imageedit');
    }

    #图片添加
    public function saveimage(Request $request){

        $photo = $request->post('photo');
        $title = $request->post('title');
        $sort = $request->post('sort');

        $data = [
            'pic' => $photo,
            'title' => $title,
            'sort' => $sort,
            'create_time' => time()
        ];

        $insphoto = Db::table('spread_image')->insert($data);

        if ($insphoto){

            return json(['code' => 0, 'message' => '添加成功','toUrl' => url('image')]);

        }

        return json(['code' => 1, 'message' => '添加失败']);

    }

    #图片删除
    public function imagedel(Request $request){

        $uid = $request->param('id');

        $del = Db::table('spread_image')->where('id',$uid)->delete();

        if ($del){

            return json(['code' => 0, 'message' => '删除成功']);

        }

        return json(['code' => 1, 'message' => '删除失败']);

    }



}
