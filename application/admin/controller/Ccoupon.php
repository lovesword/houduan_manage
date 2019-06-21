<?php
namespace app\admin\controller;

use app\base\controller\Admin;
use app\model\Config;

class Ccoupon extends Admin
{
//    复制编辑页
    public function copy(){
        global $_W,$_GPC;
        $this->view->_W = $_W;
        $this->view->_GPC = $_GPC;
        $id = input('get.id');
        $info = $this->model->get($id);
        $info->left_num = $info->num;
        unset($info->id);
        $this->view->info = $info;
        return view('edit');
    }
    public function setting(){
        global $_W,$_GPC;
        $this->view->_W = $_W;
        $this->view->_GPC = $_GPC;
        $info = [];

        $configs = [
            'coupon_index_switch'=>0,
            'coupon_window_switch'=>0,
        ];
        foreach ($configs as $key => $value) {
            $info[$key] = Config::get_value($key,$value);
        }

        $this->view->info = $info;
        return view();
    }
    public function setting_save(){
        $info = new Config();

        $data = input('post.');

        $list = [];
        $configs = [
            'coupon_index_switch'=>0,
            'coupon_window_switch'=>0,
        ];
        foreach ($configs as $key => $value) {
            $list[] = Config::full_id($key,$data[$key]);
        }

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
}
