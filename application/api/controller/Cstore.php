<?php
namespace app\api\controller;

use app\model\Accessrecord;
use app\model\Config;
use app\model\Mercapdetails;
use app\model\Store;
use app\model\Order;
use app\base\controller\Api;
use app\model\Withdrawset;

class Cstore extends Api
{
//    商户入驻申请
    public function applyStore(){
        $data = input('request.');
        $data['check_state'] = 1;
        $data['fail_reason'] = '';

        $id = input('request.id');
        $store_model = $id?Store::get($id):new Store();

        $store_model->allowField(true)->save($data);
        success_json($store_model);
    }
//    获取我的商家
    public function getMyStore(){
        $user_id = input('request.user_id');
        $store = Store::get(['user_id'=>$user_id]);

        if ($store){
            $time1 = strtotime(date('Y-m-d',time()));//获取今天凌晨的时间戳
            $time2 = strtotime(date("Y-m-d",strtotime("-1 day")));//获取昨天凌晨的时间戳
            $store['today_sum'] = Mercapdetails::where('store_id',$store['id'])
                ->where('create_time',['>=',$time1])
                ->sum('money');
            $store['yesterday_sum'] = Mercapdetails::where('store_id',$store['id'])
                ->where('create_time',['>=',$time2])
                ->where('create_time',['<',$time1])
                ->sum('money');
            $store['total_sum'] = Mercapdetails::where('store_id',$store['id'])
                ->where('create_time',['>=',$time1])
                ->sum('money');
            $wd_set = Withdrawset::get_curr();
            $store['withdraw_switch'] = $wd_set?($wd_set['is_open']?:0):0;
        }

        $detail = Config::get_value('mstore_apply_detail');

        success_json($store,['apply_detail'=>$detail]);
    }
    //    获取商户详情
    public function getStore(){
        $id=input('request.id');
        $data = Store::get($id);
        success_withimg_json($data);
    }
//    获取商户报表
    public function getStoreReport(){
        $ret = [];
        $store_id = input('request.store_id');
        $store_data = Store::get($store_id);
        if (!$store_id){
            $store_data = [
                'id'=>$store_id,
                'name'=>'平台',
            ];
        }
        $ret['storeinfo'] = $store_data;

        $begin_today = strtotime(date('Y-m-d',time()));//获取今天凌晨的时间戳
        $begin_yesterday=strtotime(date("Y-m-d",strtotime("-1 day")));//获取昨天凌晨的时间戳

        $order_model = new Order();
        $accessrecord_model = new Accessrecord();

//        今日订单
        $todayOrderCount = $order_model->where('store_id',$store_id)
            ->where('order_status',3)
            ->where('pay_time',['>=',$begin_today])
            ->count();

//        今日收入
        $todayAmountSum = $order_model->where('store_id',$store_id)
            ->where('order_status',3)
            ->where('pay_time',['>=',$begin_today])
            ->sum('goods_total_price');

//        昨日收入
        $yesterdayAmountSum = $order_model->where('store_id',$store_id)
            ->where('order_status',3)
            ->where('pay_time',[['>=',$begin_yesterday],['<',$begin_today]])
            ->sum('goods_total_price');
//        累计收入
        $amountSum = $order_model->where('store_id',$store_id)
            ->where('order_status',3)
            ->sum('goods_total_price');

//        今日访问量
        $todayAccessCount = $accessrecord_model->where('store_id',$store_id)
            ->where('create_time',['>=',$begin_today])
            ->count();

        $ret['report'] = [
            'todayOrderCount'=>$todayOrderCount,
            'todayAmountSum'=>$todayAmountSum,
            'todayAccessCount'=>$todayAccessCount,
            'yesterdayAmountSum'=>$yesterdayAmountSum,
            'amountSum'=>$amountSum,
            'money'=>$store_data['money'],
        ];

        success_withimg_json($ret);
    }
//    获取商户订单
    public function getStoreOrders(){
        //条件
        $query = function ($query){
            $query->where('store_id',input('request.store_id'));
            $state = input("request.state");
            if ($state){
                $query->where('order_status',$state)
                    ->where('pay_status',1);
            }
        };

//        查询数据
        $order_model = new Order();
        $order_model->fill_order_limit();//分页，排序
        $list = $order_model->where($query)->with('orderdetails')->order('create_time','desc')->select();
        $count = $order_model->where($query)->count();

        success_withimg_json($list,['count'=>$count]);
    }
}
