<?php

namespace app\model;

use app\base\model\Base;
use think\Hook;

class Leader extends Base
{
    public $order = 'create_time desc,check_state';//默认排序
    protected static function init()
    {
        parent::init();

        self::beforeUpdate(function ($model){
            $old_info = self::get($model->id);
//            审核
            if ($old_info['check_state'] == 1 && $model['check_state'] == 2){
                Hook::listen('on_leader_checked',$model);
            }

        });
        self::beforeDelete(function ($model){
            Hook::listen('on_leader_deleted',$model);
        });
    }
    public function user(){
        return $this->hasOne('User','id','user_id')->bind(array(
            'openid'=>'openid',
            'pic'=>'img',
        ));
    }
    public function onLeaderbillAdd($bill){
        $leader = Leader::get($bill->leader_id);
        $leader->money += $bill->money;
        $leader->save();
    }
    public function onLeaderwithdrawAdd($info){
        $leader = Leader::get($info->leader_id);
        $leader->money -= $info->amount;
        $leader->save();
    }
    public function onLeaderwithdrawFail(&$info){
        $leader = Leader::get($info->leader_id);
        $leader->money += $info->amount;
        $leader->save();
    }
}
