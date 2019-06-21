<?php
namespace app\admin\controller;
use app\model\Deliveryorder;
use app\model\Deliveryordergoods;
use app\base\controller\Admin;
use app\model\Goods;

class Cdeliveryorder extends Admin
{
//    获取列表页数据
    public function get_list(){
        $getModel = function(){
            $key = input('get.key');
            $model = Deliveryorder::hasWhere('leader',['name'=>['like',"%$key%"]]);
            $model->where('store_id',$_SESSION['admin']['store_id']);

            $type = input('get.type',0);
            if ($type){
                $model->where('state',$type);
            }

            $begin_time = input('get.begin_time','');
            if ($begin_time){
                $model->where('Deliveryorder.create_time >= ' . strtotime($begin_time));
            }

            $end_time = input('get.end_time','');
            if ($end_time){
                $end_time = strtotime($end_time) + 24*60*60;
                $model->where('Deliveryorder.create_time <= ' . $end_time);
            }

            return $model;
        };

        $model = $getModel();

        //分页
        $page = input('request.page',1);
        $limit = input('request.limit',10);
        if($page){
            $model->limit($limit)->page($page);
        }
        //排序
        $order = input('request.orderfield');
        if($order){
            $model->order($order,strtolower(input('request.ordertype')) == "desc"?"DESC":"");
        }else{
            $model->order('create_time desc');
        }

        $list = $model->with('Leader')->select();

        return [
            'code'=>0,
            'count'=>$getModel()->count(),
            'data'=>$list,
            'msg'=>'',
        ];
    }

    public function save(){
        $data = input('request.');

        $goodses = $data['goodses'];

//        配送商品详情
        $details = [];
        foreach ($goodses as $goods) {
            $detail = $goods['goods_name'];
            if ($goods['attr_names']){
                $detail.= '['.$goods['attr_names'].']';
            }
            $detail .= ' * '.$goods['num'];
            $details[] = $detail;
        }
        $data['detail'] = implode(';',$details);

        $order_model = new Deliveryorder($data);
        $order_model->startTrans();
        $order_model->allowField(true)->save();

        foreach ($goodses as &$goods) {
            $goods['order_id'] = $order_model->id;
            $goods['leader_id'] = $order_model->leader_id;
            $goods['store_id'] = $order_model->store_id;
        }

        $goods_model = new Deliveryordergoods();
        $ret = $goods_model->allowField(true)->saveAll($goodses);
        if (!$ret){
            $order_model->rollback();
            error_json('生成失败，请重新提交');
        }

        $order_model->commit();
        success_json();
    }
    public function detail(){
        $info=$this->model->get(input('get.id'),['goodses','leader']);

        switch ($info['state']){
            case 3:
                $info['state_z']= '待收货';
                break;
            case 4:
                $info['state_z']= '已完成';
                break;
        }
        $this->view->info=$info;
        return view('edit');
    }
}
