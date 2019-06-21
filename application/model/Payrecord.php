<?php
namespace app\model;

use app\base\model\Base;

class Payrecord extends Base{
    protected static function init()
    {
        parent::init();

        self::beforeUpdate(function ($model){
            $old_info = self::get($model->id);
//            支付回调
            if (!$old_info['callback_time'] && $model['callback_time'] && $model['source_type']=='Order'){
                $order = new Order();
                $order->onPaycallback($model);
            }
        });
    }
}