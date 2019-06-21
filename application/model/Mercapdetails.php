<?php
namespace app\model;

use app\base\model\Base;

class Mercapdetails extends Base
{
    protected static function init()
    {
        parent::init();

//        账单变动
        self::beforeInsert(function ($model) {
            $store = new Store();
            $store->onMercapdetailsAdd($model);
        });
    }

    public function onOrderFinish(&$order){
        $stores = Ordergoods::where('order_id',$order->id)
            ->distinct('store_id')
            ->field('store_id')
            ->select();
        foreach ($stores as $store) {
            $data = [
                'type'=>1,
                'store_id'=>$store->store_id,
                'money'=>Ordergoods::where('order_id',$order->id)
                    ->where('store_id',$store->store_id)
                    ->sum('amount'),
                'sign'=>1,
                'order_id'=>$order->id,
            ];
            $info = new Mercapdetails($data);
            $info->isUpdate(false)->allowField(true)->save();
        }
    }
    public function onOrdergoodsFinish(&$ordergoods){
        $data = [
            'type'=>1,
            'store_id'=>$ordergoods->store_id,
            'money'=>$ordergoods->pay_amount - $ordergoods->share_amount - $ordergoods->distribution_money,
            'sign'=>1,
            'order_id'=>$ordergoods->id,
        ];
        $info = new Mercapdetails($data);
        $info->isUpdate(false)->allowField(true)->save();
    }
}
