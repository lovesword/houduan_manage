<?php
namespace app\admin\controller;

use app\base\controller\Admin;
use app\model\Ordergoods;

class Cordergoods extends Admin
{
//    团长明细
    public function index2()
    {
        global $_W,$_GPC;
        $this->view->_W = $_W;
        $this->view->_GPC = $_GPC;
        return view();
    }
    //    用户订单明细：获取列表页数据
    public function get_list(){
        $getModel = function (){
            $key = input('get.key','');

            $model = Ordergoods::where('t1.store_id',$_SESSION['admin']['store_id'])
                ->alias('t1')
                ->join('order t2','t2.id = t1.order_id')
                ->join('user t3','t3.id = t1.user_id')
                ->join('leader t4','t4.id = t1.leader_id')
                ->where('t1.goods_name|t1.attr_names|t2.order_no|t3.name|t4.name','like',"%$key%");

            $state = input('get.state',0);
            if($state){
                $model->where('t1.state',$state);
            }else{
                $model->whereNotIn('t1.state',[-10]);
            }

            $user_id = input('get.user_id',0);
            if($user_id){
                $model->where('t1.user_id',$user_id);
            }

            $leader_id = input('get.leader_id',0);
            if($leader_id){
                $model->where('t1.leader_id',$leader_id);
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
            $model->order('t1.'.$order,strtolower(input('request.ordertype')) == "desc"?"DESC":"");
        }else{
            $model->order('t1.create_time desc');
        }

        $list = $model
            ->field('t1.*,t4.name as leader_name,t3.name,t3.img,t2.order_no as order_no,FROM_UNIXTIME(t2.create_time,\'%Y-%m-%d %T\') as order_time,t2.pay_amount as order_pay_amount,t2.coupon_money as order_coupon_money')
            ->select();

        return [
            'code'=>0,
            'count'=>$getModel()->count(),
            'data'=>$list,
            'msg'=>'',
        ];
    }
    public function outCSV(){
        $getModel = function (){
            $key = input('get.key','');

            $model = Ordergoods::where('t1.store_id',$_SESSION['admin']['store_id'])
                ->alias('t1')
                ->join('order t2','t2.id = t1.order_id')
                ->join('user t3','t3.id = t1.user_id')
                ->join('leader t4','t4.id = t1.leader_id')
                ->where('t1.goods_name|t1.attr_names|t2.order_no|t3.name|t4.name','like',"%$key%");

            $state = input('get.state',0);
            if($state){
                $model->where('t1.state',$state);
            }

            $user_id = input('get.user_id',0);
            if($user_id){
                $model->where('t1.user_id',$user_id);
            }

            $leader_id = input('get.leader_id',0);
            if($leader_id){
                $model->where('t1.leader_id',$leader_id);
            }

            $ids = input('get.ids');
            if($ids){
                $model->where('t1.id','in',$ids);
            }

            //排序
            $order = input('request.orderfield');
            if($order){
                $model->order('t1.'.$order,strtolower(input('request.ordertype')) == "desc"?"DESC":"");
            }else{
                $model->order('t1.create_time desc');
            }

            return $model;
        };

        $list = $getModel()
            ->field('t2.order_no as order_no,t3.name,t1.goods_name,t4.name as leader_name,t2.pay_amount as order_pay_amount,t2.coupon_money as order_coupon_money,t1.pay_amount,t1.coupon_money,t1.attr_names,t1.state,t1.num,t2.create_time as order_time')
            ->select();

        foreach ($list as &$item) {
            $item->order_time = date('Y-m-d H:i:s', $item->order_time);
            $item->order_no = '\''.$item->order_no;
            switch ($item->state){
                case 1:
                    $item->state = '待支付';
                    break;
                case 2:
                    $item->state = '待配送';
                    break;
                case 3:
                    $item->state = '配送中';
                    break;
                case 4:
                    $item->state = '待自提';
                    break;
                case 5:
                    $item->state = '已完成';
                    break;
                case 6:
                    $item->state = '已取消';
                    break;
            }
        }

        $this->toCSV('用户订单明细表'.date('ymdhis').'.csv',['订单号','用户','商品名称','团长','订单支付金额','订单优惠金额','商品支付金额','商品优惠金额','规格','状态','数量','下单时间'],json_decode(json_encode($list),true));
    }
    //    团长明细
    public function get_list2(){
        $getModel = function (){
            $key = input('get.key','');

            $model = Ordergoods::where('t1.store_id',$_SESSION['admin']['store_id'])
                ->alias('t1')
                ->join('leader t2','t2.id = t1.leader_id')
                ->where('t2.name','like',"%$key%");

            $model->whereNotIn('state',[1,-10]);
            $state = input('get.state');
            if($state){
                $model->where('state',$state);
            }

            $leader_id = input('get.leader_id',0);
            if($leader_id){
                $model->where('t1.leader_id',$leader_id);
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
            $model->order('t1.'.$order,strtolower(input('request.ordertype')) == "desc"?"DESC":"");
        }else{
            $model->order('t1.create_time desc');
        }

        $list = $model
            ->group('leader_name,t1.goods_id,t1.goods_name,t1.attr_ids,t1.attr_names,t1.state')
            ->field('t2.name as leader_name,t1.goods_id,t1.goods_name,t1.attr_ids,t1.attr_names,t1.state,sum(t1.num)as num')
            ->select();

        $count = $getModel()
            ->group('leader_name,t1.goods_id,t1.goods_name,t1.attr_ids,t1.attr_names,t1.state')
            ->field('t2.name as leader_name,t1.goods_id,t1.goods_name,t1.attr_ids,t1.attr_names,t1.state,sum(t1.num)as num')
            ->count();

        return [
            'code'=>0,
            'count'=>$count,
            'data'=>$list,
            'msg'=>'',
        ];
    }
    public function outCSV2(){
        $model = $this->model;

        //条件
        $query = function ($query){
            //关键字搜索
            $key = input('get.key');
            if ($key){
                $query->where("goods_name like '%{$key}%' or attr_names like '%{$key}%'");
            }
            $query->where('state <> 1');

            $state = input('get.state');
            if($state){
                $query->where('state',$state);
            }

            $leader_id = input('get.leader_id',0);
            if($leader_id){
                $query->where('leader_id',$leader_id);
            }

            $query->where('store_id',$_SESSION['admin']['store_id']);
        };

        //排序
        $order = input('request.orderfield');
        if($order){
            $model->orderby($order,strtolower(input('request.ordertype')) == "desc"?"DESC":"");
        }else{
            $model->orderby('create_time desc');
        }

        $list = $model->where($query)->with('leader')
            ->group('leader_id,goods_id,goods_name,attr_ids,attr_names,state')
            ->field('leader_id,goods_id,goods_name,attr_ids,attr_names,state,sum(num)as num')
            ->select();

        foreach ($list as &$item) {
            switch ($item->state){
                case 1:
                    $item->state = '待支付';
                    break;
                case 2:
                    $item->state = '待配送';
                    break;
                case 3:
                    $item->state = '配送中';
                    break;
                case 4:
                    $item->state = '待自提';
                    break;
                case 5:
                    $item->state = '已完成';
                    break;
                case 6:
                    $item->state = '已取消';
                    break;
            }

            unset($item->leader_id);
            unset($item->goods_id);
            unset($item->attr_ids);
        }

        $this->toCSV('团长订单明细表'.date('ymdhis').'.csv',['商品名称','规格','状态','数量','团长'],json_decode(json_encode($list),true));
    }
    //    获取choose页数据
    public function get_list3(){
        $getModel = function (){
            $model = new Ordergoods();
            $model->alias('t1')
                ->join('goods t2','t2.id = t1.goods_id and (t2.state = 0 || t2.end_time <= '.time().')');

            //关键字搜索
            $key = input('get.key');
            if ($key){
                $model->where('t1.goods_name','like',"%$key%");
            }
            $leader_id = input('request.leader_id');
            if ($leader_id){
                $model->where('t1.leader_id',$leader_id);
            }
            $model->where('t1.state',2);
            $model->where('t1.store_id',$_SESSION['admin']['store_id']);

            $model->group('t1.goods_id,t1.goods_name,t1.batch_no,t1.attr_ids,t1.attr_names')
                ->field('t1.goods_id,t1.goods_name,t1.batch_no,t1.attr_ids,t1.attr_names,sum(t1.num) as num');

            return $model;
        };

        $model = $getModel();

        //排序、分页
//        $model->fill_order_limit();
        //分页
        $page = input('request.page',1);
        $limit = input('request.limit',10);
        if($page){
            $model->limit($limit)->page($page);
        }
        //排序
        $order = input('request.orderfield');
        if($order){
            $model->orderby('t1.'.$order,strtolower(input('request.ordertype')) == "desc"?"DESC":"");
        }else{
            $model->orderby('t1.create_time desc');
        }

        $list = $model->select();

        $model = $getModel();
        return [
            'code'=>0,
            'count'=>$model->count(),
            'data'=>$list,
            'msg'=>'',
        ];
    }

}
