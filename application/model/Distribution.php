<?php

namespace app\model;

use app\base\model\Base;

class Distribution extends Base
{
    public function user(){
        return $this->hasOne('User','id','user_id')->bind(array(
            'img'=>'img',
        ));
    }
    public function getCheckTimeAttr($value)
    {
        if (!$value){
            return '';
        }
        return date('Y-m-d H:i:s',$value);
    }
    public function onDistributionrecordFinish($record){
        $distribution = Distribution::where([
            'user_id'=>$record->user_id,
        ]);

        $distribution->setInc('amount',$record['money']);
        $distribution->setInc('money_future',$record['money']);
        $distribution->setInc('amount_order',$record['amount']);
    }
}
