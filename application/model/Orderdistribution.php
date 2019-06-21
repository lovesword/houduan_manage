<?php

namespace app\model;

use app\base\model\Base;
use think\Db;

class Orderdistribution extends Base
{
    public function user(){
        return $this->hasOne('User','id','user_id')->bind(array(
            'img'=>'img',
        ));
    }
//    新增订单分佣记录
    public static function insertDb($data){
        $data['create_time'] = time();
        Db::name('orderdistribution')->insert($data);
        return Db::name('orderdistribution')->getLastInsID();
    }
//    未结算分佣转可提现
    public static function convert($id){
        $data = self::get($id);
        $dis_id = User::isDistribution($data['user_id']);
        Db::name('distribution')->where(['id'=>$dis_id])->setInc('money',$data['money']);
        Db::name('distribution')->where(['id'=>$dis_id])->setDec('money_future',$data['money']);
        return true;
    }
//    分佣完成
    public static function finish($id){
        $data = Orderdistribution::get($id);
        $dis_id = User::isDistribution($data['user_id']);
        Db::name('distribution')->where(['id'=>$dis_id])->setInc('amount',$data['money']);
        Db::name('distribution')->where(['id'=>$dis_id])->setInc('money_future',$data['money']);
        Db::name('distribution')->where(['id'=>$dis_id])->setInc('amount_order',$data['amount']);

        $orderdistribution_id = Db::name('orderdistribution')->getLastInsID();
        $task_model = new Task();
        $time = Config::get_value('distribution_withdraw_time',0);
        $task_model->save([
            'type'=>'convert_money',
            'value'=>$orderdistribution_id,
            'execute_time'=>time() + $time*60*60,
        ]);
    }
}
