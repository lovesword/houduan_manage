<?php
namespace app\model;

use app\base\model\Base;
use think\Db;

class Pinorder extends Base
{
    /**
     * 获取购买次数
    */
    public function buyNum($goods_id,$user_id){
        $num=$this->where(['goods_id'=>$goods_id,'user_id'=>$user_id,'is_del'=>0])->count();
        return $num;
    }
    /**
     * 单购/团长支付成功回调
     */
    public function notify($data){
        $attach=json_decode($data['attach'],true);
        $oid=$attach['oid'];
        //修改订单信息
        $orderinfo=$this->mfind(['id'=>$oid]);
        if($orderinfo['heads_id']>0){
            $log['order_status']=1;
        }else{
            $log['order_status']=2;
        }
        $log['out_trade_no']=$data['out_trade_no'];
        $log['transaction_id']=$data['transaction_id'];
        $log['is_pay']=1;
        $log['pay_type']=1;
        $log['pay_time']=time();
        $this->where(['id'=>$oid])->update($log);
        //修改团长信息
        //删除支付任务
        $task=new Task();
        $task->where(['type'=>'pinpay','value'=>$oid])->delete();
        if($orderinfo['is_head']>0){
            $goodsinfo=Pingoods::get(['id'=>$orderinfo['goods_id']]);
            $exper=$goodsinfo['group_time']*60*60+time();
            if($orderinfo['heads_id']>0){
                $head=new Pinheads();
                $head->save(['status'=>1,'expire_time'=>$exper],['id'=>$orderinfo['heads_id']]);
                //添加成团倒计时任务
                $task=array(
                    'uniacid'=>$orderinfo['uniacid'],
                    'type'=>'pinopen',
                    'state'=>0,
                    'level'=>1,
                    'value'=>$oid,
                    'create_time'=>time(),
                    'execute_time'=>$exper-5,
                    'execute_times'=>1
                );
                Db::name('task')->insert($task);
//                $this->timingTask(2,$oid);
            }
        }

        echo 'SUCCESS';
    }
    /**
     * 团员支付成功回调
    */
    public function joinNotify($data){
        $attach=json_decode($data['attach'],true);
        $oid=$attach['oid'];
        //修改订单信息
        $log['out_trade_no']=$data['out_trade_no'];
        $log['transaction_id']=$data['transaction_id'];
        $log['order_status']=1;
        $log['is_pay']=1;
        $log['pay_type']=1;
        $log['pay_time']=time();
        $this->where(['id'=>$oid])->update($log);
        //判断人数 ，是否拼团成功
        $heads=new Pinheads();

        $orderinfo=Db::name('pinorder')->where(['id'=>$oid])->find();
        $heads->checkNum($orderinfo['heads_id'],$oid);
//        $heads_id=$orderinfo['heads_id'];
//        $ord=new Pinorder();
//        $nowmun=$ord->allpayNum($heads_id);
//        $headsinfo=self::get($heads_id);
//        $headsinfo=  Db::name('pinheads')->where(['id'=>$heads_id])->find();
//
//        Db::name('baowen')->insert(['xml'=>$nowmun.'ssss'.$headsinfo['groupnum']]);
////        var_dump($nowmun,$headsinfo['groupnum']);exit;
//        if($nowmun==$headsinfo['groupnum']){
//            //删除成团倒计时任务
//            $task=new Task();
//            $task->where(['type'=>'pinopen','value'=>$oid])->delete();
//            Db::name('baowen')->insert(['xml'=>111]);
//            //拼团成功
//            Db::name('pinheads')->where(['id'=>$heads_id])->update(['status'=>2]);
////            $this->save(['status'=>2],['id'=>$heads_id]);
//            //修改订单成团状态
////            $ord->save(['order_status'=>2],['heads_id'=>$heads_id]);
//            Db::name('pinorder')->where(['heads_id'=>$heads_id])->update(['order_status'=>2]);
//        }else{
//            Db::name('baowen')->insert(['xml'=>222]);
//        }

        //删除任务
        $task=new Task();
        $task->where(['type'=>'pinpay','value'=>$oid])->delete();
        echo 'SUCCESS';
    }
    /**
     * 团员列表
    */
    public function grouplist($heads_id){
        global $_W;
        $list=$this->where(['uniacid'=>$_W['uniacid'],'heads_id'=>$heads_id,'is_del'=>0])->order(['is_head'=>'desc','create_time'=>'asc'])->select();
        foreach ($list as $key =>$value){
            $list[$key]['userinfo']=User::get(['id'=>$value['user_id']]);
        }
        return $list;
    }
    /**
     * 获取团员总人数
    */
    public function allNum($heads_id){
        global $_W;
        $num=$this->where(['uniacid'=>$_W['uniacid'],'heads_id'=>$heads_id,'is_del'=>0])->field('id')->count();
        return $num;
    }
    /**
     * 获取已支付团员总人数
     */
    public function allpayNum($heads_id){
        global $_W;
        $num=$this->where(['heads_id'=>$heads_id,'is_del'=>0,'is_pay'=>1])->field('id')->count();
        return $num;
    }
    /**
     * 添加计时任务
    */
    public function timingTask($type,$oid){
        $orderinfo=$this->mfind(['id'=>$oid]);
        //1.支付倒计时
        if($type==1){
            $task=array(
                'uniacid'=>$orderinfo['uniacid'],
                'type'=>'pinpay',
                'state'=>0,
                'level'=>1,
                'value'=>$oid,
                'create_time'=>time(),
                'execute_time'=>$orderinfo['expire_time']-5,
                'execute_times'=>1
            );
        }elseif ($type==2){
            //2.开团倒计时
            $head=new Pinheads();
            $headinfo=$head->mfind(['id'=>$orderinfo['heads_id']]);
            $task=array(
                'uniacid'=>$orderinfo['uniacid'],
                'type'=>'pinopen',
                'state'=>0,
                'level'=>1,
                'value'=>$oid,
                'create_time'=>time(),
                'execute_time'=>$headinfo['expire_time']-5,
                'execute_times'=>1
            );
        }
        Db::name('task')->insert($task);
//        $mod=new Task();
//        var_dump($task);exit;
//        $mod->save($task);
    }
    /**
     * 支付过期
    */
    public  function payOverdue($value){
        $oid=intval($value);
        $orderinfo=$this->mfind(['id'=>$oid,'is_del'=>0]);
        if($orderinfo['is_pay']==0){
            //返回库存，销量
            $goods=new Pingoods();
            $goods->updateNum($orderinfo['goods_id'],$orderinfo['num'],$orderinfo['attr_ids']);
            //删除订单
            $this->save(['is_del'=>1],['id'=>$oid]);
            return true ;
        }else{
            return true ;
        }
    }
    /**
     * 拼团过期
    */
    public function openOverdue($value){
        $oid=intval($value);
        $heads=new Pinheads();
        $headsinfo=$heads->mfind(['oid'=>$oid]);
        if($headsinfo['status']==1){
            $heads->save(['status'=>3],['id'=>$headsinfo['id']]);
            //退款、返库存、减销量
            $orderinfo=$this->mfind(['id'=>$oid]);
            $pin=new Pingoods();
            $pin->updateNum($orderinfo['goods_id'],$orderinfo['num'],$orderinfo['attr_ids']);
            //获取所有已支付订单列表
            $paylist=$this->where(['uniacid'=>$orderinfo['uniacid'],'heads_id'=>$orderinfo['heads_id'],'is_del'=>0,'is_pay'=>1])->select();
            foreach ($paylist as $key =>$value){
                $refund=new Pinrefund();
                $refund->refund($oid);
            }
            return true;
        }else{
            return true;
        }
    }

}