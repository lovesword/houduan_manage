<?php
namespace app\admin\controller;

use app\base\controller\Admin;
use app\model\Config;
use app\model\Goodsattr;
use app\model\Goodsattrgroup;
use app\model\Goodsattrsetting;
use app\model\Store;

class Cgoods extends Admin
{
    public function get_list(){
        $model = $this->model;

        //条件
        $query = function ($query){
            $query->where('store_id',$_SESSION['admin']['store_id']);
            //关键字搜索
            $key = input('get.key');
            if ($key){
                $query->where('name','like',"%$key%");
            }
            $cat_id = input('get.cat_id');
            if ($cat_id){
                $query->where('cat_id',"$cat_id");
            }
        };

        //排序、分页
        $model->fill_order_limit();

        $list = $model->with(['category'])->where($query)->select();

        return [
            'code'=>0,
            'count'=>$model->where($query)->count(),
            'data'=>$list,
            'msg'=>'',
        ];
    }
    public function see(){
        global $_W,$_GPC;
        $this->view->_W = $_W;
        $this->view->_GPC = $_GPC;
        $id = input('get.id');

        //        获取规格设置
        $attrsetting_model = new Goodsattrsetting();
        $attrsetting_list = $attrsetting_model->where('goods_id',$id)->select();

//        获取规格分组信息
        $attrgroup_model = new Goodsattrgroup();
        $attrgroup_list = $attrgroup_model->with('attrs')->where('goods_id',$id)->select();
        foreach ($attrgroup_list as &$item) {
            $attrs = [];
            foreach ($item['attrs'] as $attr) {
//                处理规格设置
                foreach ($attrsetting_list as &$attrsetting) {
                    if(strpos($attrsetting['key'],",{$attr['name']},") !== false){
                        $attrsetting[$item['name']] = $attr['name'];
                    }
                }

                $attrs[] = $attr['name'];
            }
        }
        $this->view->attrgroup_list = $attrgroup_list;


        $this->view->attrsetting_list = $attrsetting_list;

        $info = $this->model->get($id,['category']);
        $info['pics'] = json_decode($info['pics']);
        $this->view->info = $info;
        return view();
    }

    //    新增页
    public function add(){
        global $_W,$_GPC;
        $this->view->_W = $_W;
        $this->view->_GPC = $_GPC;

        $this->view->attrgroup_list = [];
        $this->view->attrsetting_list = [];

        $info = $this->model;
        $info['details'] = '';
        $info['use_attr'] = 0;
        $info['leader_draw_type'] = isset($info['leader_draw_type']) && $info['leader_draw_type']?$info['leader_draw_type']:0;
        $this->view->info = $info;

        //        获取团长抽成
        $rate = 0;
        $money = 0;

        $store = Store::get($_SESSION['admin']['store_id']);
        if ($store && $store->leader_draw_type == 1){
            $rate = $store->leader_draw_rate;
        }elseif ($store && $store->leader_draw_type == 2){
            $money = $store->leader_draw_money;
        }else{
            $type = Config::get_value('leader_draw_type',1);
            if ($type == 1) {
                $rate = Config::get_value('leader_draw_rate',0);
            }else{
                $money = Config::get_value('leader_draw_money',0);
            }
        }

        $this->view->leader_rate = $rate;
        $this->view->leader_money= $money;
        return view('edit');
    }
    //    编辑页
    public function edit(){
        global $_W,$_GPC;
        $this->view->_W = $_W;
        $this->view->_GPC = $_GPC;
        $id = input('get.id');

        //        获取规格设置
        $attrsetting_model = new Goodsattrsetting();
        $attrsetting_list = $attrsetting_model->where('goods_id',$id)->select();

//        获取规格分组信息
        $attrgroup_model = new Goodsattrgroup();
        $attrgroup_list = $attrgroup_model->with('attrs')->where('goods_id',$id)->select();
        foreach ($attrgroup_list as &$item) {
            $attrs = [];
            foreach ($item['attrs'] as $attr) {
//                处理规格设置
                foreach ($attrsetting_list as &$attrsetting) {
                    if(strpos($attrsetting['key'],",{$attr['name']},") !== false){
                        $attrsetting[$item['name']] = $attr['name'];
                    }
                }

                $attrs[] = $attr['name'];
            }
        }
        $this->view->attrgroup_list = $attrgroup_list;


        $this->view->attrsetting_list = $attrsetting_list;

        $info = $this->model->get($id);
        $info['pics'] = json_decode($info['pics']);
        $info['leader_draw_type'] = $info['leader_draw_type']?:0;
        $this->view->info = $info;

//        获取团长抽成
        $rate = 0;
        $money = 0;

        $store = Store::get($_SESSION['admin']['store_id']);
        if ($store && $store->leader_draw_type == 1){
            $rate = $store->leader_draw_rate;
        }elseif ($store && $store->leader_draw_type == 2){
            $money = $store->leader_draw_money;
        }else{
            $type = Config::get_value('leader_draw_type',1);
            if ($type == 1) {
                $rate = Config::get_value('leader_draw_rate',0);
            }else{
                $money = Config::get_value('leader_draw_money',0);
            }
        }

        $this->view->leader_rate = $rate;
        $this->view->leader_money= $money;

        return view('edit');
    }
    //    复制编辑页
    public function copy(){
        global $_W,$_GPC;
        $this->view->_W = $_W;
        $this->view->_GPC = $_GPC;
        $id = input('get.id');

        //        获取规格设置
        $attrsetting_model = new Goodsattrsetting();
        $attrsetting_list = $attrsetting_model->where('goods_id',$id)->select();

//        获取规格分组信息
        $attrgroup_model = new Goodsattrgroup();
        $attrgroup_list = $attrgroup_model->with('attrs')->where('goods_id',$id)->select();
        foreach ($attrgroup_list as &$item) {
            $attrs = [];
            foreach ($item['attrs'] as $attr) {
//                处理规格设置
                foreach ($attrsetting_list as &$attrsetting) {
                    if(strpos($attrsetting['key'],",{$attr['name']},") !== false){
                        $attrsetting[$item['name']] = $attr['name'];
                    }
                }

                $attrs[] = $attr['name'];
            }
        }
        $this->view->attrgroup_list = $attrgroup_list;


        $this->view->attrsetting_list = $attrsetting_list;


        $info = $this->model->get($id,['category','platform_category']);
        $info['pics'] = json_decode($info['pics']);
        unset($info->id);
        $this->view->info = $info;
        return view('edit');
    }
    //    数据保存（新增、编辑）
    public function save(){
        $info = $this->model;

        $id = input('post.id');
        if ($id) {
            $info = $info->get($id, 'category');
        }

        $data = input('post.');
//        补充商户id信息
        if (!$data['store_id']){
            $data['store_id'] = $_SESSION['admin']['store_id'];
        }

        $data['check_state'] = 2;
//        判断是否需要重新审核
        if ($data['store_id']){
            if($id){
                $config = Config::get_value('goods_update_check',0);
            }else{
                $config = Config::get_value('goods_insert_check',0);
            }
            if ($config){
                $data['check_state'] = 1;
                $data['fail_reason'] = '';
            }
        }

        $data['pics'] = json_encode($data['pics']);
        $ret = $info->allowField(true)->save($data);

        if($ret){
            if($info->use_attr){
//            修改分组信息的 goods_id
                $attrs_data = $data['attrs_data'];
                $attrs_data = json_decode($attrs_data);

                $attrgroup_model = new Goodsattrgroup();
                $group_list = [];
                foreach ($attrs_data as $key => $attr_group) {
                    if (!$attr_group){
                        continue;
                    }
                    $group_data = [];
                    $group_data['id'] = $key;
                    $group_data['goods_id'] = $info->id;
                    $group_list[] = $group_data;
                }
                $attrgroup_model->saveAll($group_list);

//              保存规格设置
                $attrsettings = $data['attr'];
                $attrsettings = json_decode($attrsettings);

                $attrsetting_model = new Goodsattrsetting();
                $attrsetting_model->where('goods_id',$info->id)->delete();

                $num = 0;
                foreach ($attrsettings as $attrsetting) {
                    $attrsetting = (array)$attrsetting;
                    $num += $attrsetting['stock'];
                    $attrsetting_model = new Goodsattrsetting();
                    $attrsetting = (array)$attrsetting;
                    $attrsetting['goods_id'] = $info->id;

//                name
                    $names = [];
                    foreach ($attrs_data as $key => $attr_group) {
                        if (!$attr_group){
                            continue;
                        }
                        $attr_group = (array)$attr_group;
                        $names[] = $attrsetting[$attr_group['name']];
                    }
                    $attrsetting['key'] = ','.implode(',',$names).',';
                    $attrsetting['attr_ids'] = $attrsetting['ids']? ','.implode(',',$attrsetting['ids']).',':$attrsetting['attr_ids'];

                    $attrsetting_model->allowField(true)->save($attrsetting);
                }
                $info->stock = $num;
                $info->save();
            }

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
    //    审核商品列表页
    public function index2()
    {
        global $_W,$_GPC;
        $this->view->_W = $_W;
        $this->view->_GPC = $_GPC;
        return view();
    }
    public function get_list2(){
        $model = $this->model;

        //条件
        $query = function ($query){
            $query->where('store_id',['<>',0]);
//            $query->where('check_state',1);
            //关键字搜索
            $key = input('get.key');
            if ($key){
                $query->where('name','like',"%$key%");
            }
        };

        //排序、分页
        $model->fill_order_limit();

        $list = $model->with(['store','category'])->where($query)->order('check_state')->select();

        return [
            'code'=>0,
            'count'=>$model->where($query)->count(),
            'data'=>$list,
            'msg'=>'',
        ];
    }
}
