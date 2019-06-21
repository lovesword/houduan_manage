<?php

namespace app\model;

use app\base\model\Base;
use think\Exception;

class Goods extends Base
{
    protected static function init()
    {
        parent::init();

        self::beforeInsert(function ($model){
            $model->batch_no = date("YmdHis") .rand(11111, 99999);
        });
    }
    //转换开始时间显示
    public function getBeginTimeAttr($value)
    {
        return $value?date('Y-m-d H:i:d',$value):'';
    }
    public function getEndTimeAttr($value)
    {
        return $value?date('Y-m-d H:i:d',$value):'';
    }
    public function setBeginTimeAttr($value)
    {
        return strtotime($value);
    }
    public function setEndTimeAttr($value)
    {
        return strtotime($value);
    }

    public function category(){
        return $this->hasOne('Category','id','cat_id')->bind(array(
            'cat_name'=>'name',
        ));
    }

    public function store(){
        return $this->hasOne('Store','id','store_id')->bind(array(
            'store_name'=>'name',
            'store_leader_draw_type'=>'leader_draw_type',
            'store_leader_draw_rate'=>'leader_draw_rate',
            'store_leader_draw_money'=>'leader_draw_money',
        ));
    }
    public function attrgroups()
    {
        return $this->hasMany('Goodsattrgroup','goods_id','id')->with('attrs');
    }

    public function ordergoodses(){
        return $this->hasMany('Ordergoods','goods_id','id');
    }

    public function onOrderPay($order){
        $ordergoodses = Ordergoods::where('order_id',$order->id)->select();

        foreach ($ordergoodses as $ordergoods) {
            $goods = Goods::get($ordergoods->goods_id);

            $this->checkStock($ordergoods->goods_id,$ordergoods->attr_ids,$ordergoods->num);

            if ($ordergoods->attr_ids){
                $setting = Goodsattrsetting::get([
                    'goods_id'=>$ordergoods->goods_id,
                    'attr_ids'=>$ordergoods->attr_ids,
                ]);
                $setting->stock -= $ordergoods->num;
                $setting->save();
            }else{
                $goods->stock -= $ordergoods->num;
            }


            $goods->sales_num += $ordergoods->num;
            $goods->save();
        }
    }

    public function onDeliveryordergoodsAdd($deliveryordergoods){
        $goods = Goods::get($deliveryordergoods->goods_id);
        $goods->batch_no = date("YmdHis") .rand(11111, 99999);
        $goods->save();
    }

    public function checkStock($goods_id,$attr_ids,$num){
        $goods = Goods::get($goods_id);

        $stock = $goods->stock;
        if ($attr_ids){
            $setting = Goodsattrsetting::get([
                'goods_id'=>$goods_id,
                'attr_ids'=>$attr_ids,
            ]);
            $stock = $setting->stock;
        }
        if ($stock < $num){
            error_json('库存不足');
        }
    }

    public function checkState($goods_id){
        $goods = Goods::get($goods_id);
        if (!$goods->state || $goods->getData('end_time') < time()){
            error_json('商品已结束');
        }
    }
}
