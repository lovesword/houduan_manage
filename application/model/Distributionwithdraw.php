<?php

namespace app\model;

use app\base\model\Base;
use think\Db;
use think\Loader;

class Distributionwithdraw extends Base
{
    protected static function init()
    {
        //新增前-修改分销商可提已提金额
        self::beforeInsert(function ($model){
            $distribution = Distribution::get(['user_id'=>$model['user_id']]);
            Db::name('distribution')->where('id',$distribution['id'])->setDec('money',$model['amount']);
            Db::name('distribution')->where('id',$distribution['id'])->setInc('money_ing',$model['amount']);

            $model['distribution_id'] = $distribution['id'];
            $model['no'] = date("YmdHis") .rand(10000, 99999);
        });
//        新增后-判断是否免审核
        self::afterInsert(function ($model){
            $withdraw_noapplymoney = Config::get_value('distribution_withdraw_noapplymoney',0);
            if ($model['amount']<=$withdraw_noapplymoney){
                $model->checked($model['id']);
            }
        });
        parent::init();
    }

    public function distribution(){
        return $this->hasOne('Distribution','id','distribution_id')->bind(array(
            'distribution_name'=>'name',
        ));
    }
//    打款
    public static function take($id){
        $distributionwithdraw = Distributionwithdraw::get($id);
        $ret = [
            'code'=>0
        ];

        //        提现到微信
        if ($distributionwithdraw['wd_type'] == 1){
            $system = System::get_curr();
            $user = User::get($distributionwithdraw['user_id']);

            Loader::import('wxtake.wxtake');
            $wxtake = new \WeixinTake($system['appid'],$user['openid'],$system['mchid'],$system['wxkey'],$distributionwithdraw['no'],100*$distributionwithdraw['money']);
            $ret = $wxtake->take();

            if (!$ret['code']){
                Db::name('distributionwithdraw')->where('id',$id)->update(['state'=>1,'tx_time'=>time()]);
                Db::name('distribution')->where('user_id',$user['id'])->setDec('money_ing',$distributionwithdraw['amount']);
                Db::name('distribution')->where('user_id',$user['id'])->setInc('money_old',$distributionwithdraw['amount']);
            }else{
                Db::name('distributionwithdraw')->where('id',$id)->update(['state'=>2]);
//                Db::name('distribution')->where('user_id',$user['id'])->setDec('money_ing',$distributionwithdraw['amount']);
//                Db::name('distribution')->where('user_id',$user['id'])->setInc('money',$distributionwithdraw['amount']);
            }
        }

        return $ret;
    }
//    审核通过
    public static function checked($id){
        $take_ret = self::take($id);
        if (!$take_ret['code']){
            $ret = Db::name('distributionwithdraw')->where('id',$id)->update(['check_state'=>1,'check_time'=>time()]);
        }
        return $take_ret ?: $ret;
    }
//    审核失败
    public static function checkedfail($id,$reason){
//        退回金额
        $distributionwithdraw = Distributionwithdraw::get($id);
        $distribution = Distribution::get($distributionwithdraw['distribution_id']);
        Db::name('distribution')->where('id',$distribution['id'])->setInc('money',$distributionwithdraw['amount']);
        Db::name('distribution')->where('id',$distribution['id'])->setDec('money_ing',$distributionwithdraw['amount']);

        return Db::name('distributionwithdraw')->where('id',$id)->update(['check_state'=>2,'fail_reason'=>$reason]);
    }
}
