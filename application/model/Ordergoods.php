<?php
namespace app\model;

use app\base\model\Base;
use think\Hook;

class Ordergoods extends Base
{
    protected static function init()
    {
        parent::init();

        self::beforeUpdate(function ($model) {
            $old_info = self::get($model->id);
//        开始配送
            if ($old_info['state'] == 2 && $model['state'] == 3) {
                $order = new Order();
                $order->onOrdergoodsSend($model);
//        团长收货
            } else if ($old_info['state'] == 3 && $model['state'] == 4) {
                Hook::listen('on_ordergoods_receive', $model);
//                $order = new Order();
//                $order->onOrdergoodsReceive($model);
//        团长核销
            } else if ($old_info['state'] == 4 && $model['state'] == 5) {
                Hook::listen('on_ordergoods_confirm', $model);
//                $order = new Order();
//                $order->onOrdergoodsConfirm($model);
            }
        });

        self::afterInsert(function ($model){
            Hook::listen('on_ordergoods_added', $model);
        });
    }

    public function onOrderPay($order)
    {
        $list = Ordergoods::where('order_id', $order->id)->select();
        foreach ($list as $item) {
            $goods = Goods::get($item->goods_id);
            $item->batch_no = $goods->batch_no;

            $item->state = 2;
//            $item->coupon_money = $order->coupon_money * $item->amount / $order->amount;
//            $item->pay_amount = $order->pay_amount * $item->amount / $order->amount;
            $item->save();
        }
    }

    public function onDeliveryordergoodsAdd($goods)
    {
        $num = $goods->num;
        $where = [
            'state' => 2,
            'store_id' => $goods->store_id,
            'goods_id' => $goods->goods_id,
            'batch_no' => $goods->batch_no,
        ];
        if ($goods->attr_ids) {
            $where['attr_ids'] = $goods->attr_ids;
        }

        $list = Ordergoods::where('leader_id', $goods->leader_id)
            ->where($where)
            ->limit($goods->num)
            ->order('create_time')
            ->select();

        foreach ($list as $item) {
            if ($item->num > $num) {
                continue;
            }
            $item->state = 3;
            $item->save();
            $num -= $item->num;
        }
    }

    public function orderby($field, $order = null){
        parent::order($field,$order);
    }
    public function order(){
        return $this->hasOne('Order','id','order_id')->bind([
            'order_no',
            'order_time'=>'create_time',
            'order_pay_amount'=>'pay_amount',
            'order_coupon_money'=>'coupon_money',
        ]);
    }
    public function user(){
        return $this->hasOne('User','id','user_id')->bind([
            'name',
            'img',
        ]);
    }
    public function leader(){
        return $this->hasOne('Leader','id','leader_id')->bind([
            'leader_name'=>'name',
            'leader_tel'=>'tel',
            'leader_address'=>'address',
        ]);
    }
    public function onOrderCancel($order){
        $ordergoodses = Ordergoods::where('order_id',$order->id)->select();
        foreach ($ordergoodses as $ordergoods) {
            $ordergoods->state = 6;
            $ordergoods->save();
        }
    }
    public function distributionrecords(){
        return $this->hasMany('Distributionrecord','goods_id','id');
    }
}