<?php
namespace app\model;
use think\Loader;
use think\Db;
use app\base\model\Base;

class Dingtalk extends Base
{
    public function onOrderPay($order){
        $store_ids = Ordergoods::where('order_id',$order->id)
            ->distinct('store_id')
            ->field('store_id')
            ->select();

        foreach ($store_ids as $store_id) {
            $this->sendtalk($store_id->store_id,0);
        }
    }
    /*
     * 发送消息
     * @param  int  $type [发送类型0 下订单 1申请退款]
     */
    public function sendtalk($store_id,$type){
        global $_W;
        $dingtalk=Db::name('dingtalk')->where(['uniacid'=>$_W['uniacid'],'store_id'=>$store_id])->find();
        if($type==0){
            $content=$dingtalk['content'];
        }else{
            $content=$dingtalk['contentrefund'];
        }
        if($dingtalk['is_open']==1){
            $this->sendDingtalk($dingtalk['token'],$content);
        }
    }
    //钉钉机器人发送消息
    public function sendDingtalk($token,$content){
        Loader::import('dingtalks.DingTalk');
        Loader::import('dingtalks.MsgText');
        $msg = new \MsgText($content);
        $ding = new \DingTalk();
        $ding->send($token,$msg);
    }


}
