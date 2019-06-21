<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用行为扩展定义文件
return [
    // 应用初始化
    'app_init'     => [],
    // 应用开始
    'app_begin'    => [],
    // 模块初始化
    'module_init'  => [],
    // 操作开始执行
    'action_begin' => [],
    // 视图内容过滤
    'view_filter'  => [],
    // 日志写入
    'log_write'    => [],
    // 应用结束
    'app_end'      => [],

//    订单
    'on_order_add'=>[//下单
        'app\model\Usercoupon',
    ],
    'on_order_pay'=>[//支付
        'app\model\Goods',
        'app\model\Ordergoods',
        'app\model\Dingtalk',
        'app\model\Sms',
        'app\model\Task',
        'app\model\Order',
    ],
//    'on_order_finish'=>[
//        'app\model\Leaderbill',
//        'app\model\Mercapdetails',
//    ],
    'on_order_finish'=>[
//        ['app\model\Leaderbill','onOrderFinish'],
//        ['app\model\Mercapdetails','onOrderFinish'],
    ],
    'on_order_cancel'=>[//取消
        'app\model\Ordergoods',
    ],
//    订单商品
    'on_ordergoods_added'=>[//下单
//        'app\model\Distributionrecord',
    ],
    'on_ordergoods_receive'=>[//团长收货
        ['app\model\Order','onOrdergoodsReceive'],
        ['app\model\Task','onOrdergoodsReceive'],
    ],
    'on_ordergoods_confirm'=>[//团长核销
        ['app\model\Order','onOrdergoodsConfirm'],
        ['app\model\Leaderbill','onOrdergoodsFinish'],
//        ['app\model\Distributionrecord','onOrdergoodsFinish'],
        ['app\model\Mercapdetails','onOrdergoodsFinish'],
    ],
//    优惠券
    'on_usercoupon_add'=>[//领取
        'app\model\Coupon',
    ],
//    商户
    'on_store_checked'=>[//审核
        'app\model\Storeleader',
    ],
    'on_store_deleted'=>[//删除
        'app\model\Storeleader',
    ],
//    团长
    'on_leader_checked'=>[//审核
        'app\model\Storeleader',
    ],
    'on_leader_deleted'=>[//删除
        'app\model\Storeleader',
    ],
//    团长账单
    'on_leaderbill_add'=>[
        'app\model\Leader',
    ],
//    分佣明细
//    'on_distributionrecord_finish'=>[
//        'app\model\Distribution',
//        'app\model\Task',
//    ],
];
