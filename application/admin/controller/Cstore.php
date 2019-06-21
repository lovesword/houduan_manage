<?php
namespace app\admin\controller;

use app\base\controller\Admin;
use app\model\Config;
use app\model\System;

class Cstore extends Admin
{
    public function setting(){
        global $_W,$_GPC;
        $this->view->_W = $_W;
        $this->view->_GPC = $_GPC;
        $info = [];

        $info['goods_insert_check'] = Config::get_value('goods_insert_check',0);
        $info['goods_update_check'] = Config::get_value('goods_update_check',0);
        $info['mstore_switch'] = Config::get_value('mstore_switch',0);
        $info['mstore_apply_detail'] = Config::get_value('mstore_apply_detail','');

        $this->view->info = $info;
        return view();
    }
    public function setting_save(){
        $info = new Config();

        $data = input('post.');

        $list = [];

        $list[] = Config::full_id('goods_insert_check',$data['goods_insert_check']);
        $list[] = Config::full_id('goods_update_check',$data['goods_update_check']);
        $list[] = Config::full_id('mstore_switch',$data['mstore_switch']);
        $list[] = Config::full_id('mstore_apply_detail',$data['mstore_apply_detail']);

        $ret = $info->allowField(true)->saveAll($list);

        if($ret){
            return array(
                'code'=>0,
            );
        }else{
            return array(
                'code'=>1,
                'msg'=>'保存失败',
            );
        }
    }
    //    数据保存（新增、编辑）
    public function save(){
        $info = $this->model;

        $data = input('post.');

        $id = input('post.id');
        if ($id){
            $info = $info->get($id);
        }

        if($_SESSION['admin']['store_id']){
            $data['check_state'] = 1;
            $data['fail_reason'] = '';
        }

        $ret = $info->allowField(true)->save($data);

        if($ret){
            return array(
                'code'=>0,
                'data'=>$info->id,
            );
        }else{
            return array(
                'code'=>1,
                'msg'=>'保存失败',
            );
        }
    }
    //    编辑页
    public function edit(){
        global $_W,$_GPC;
        $this->view->_W = $_W;
        $this->view->_GPC = $_GPC;
        $id = input('get.id');
        $info = $this->model->get($id);
        $this->view->info = $info;

        $this->view->system = System::get_curr();
        return view('edit');
    }
}
