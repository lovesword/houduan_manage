<?php

namespace app\model;

use app\base\model\Base;

class User extends Base
{
//    判断是不是分销商
//      如果是分销商，则返回对应id
//      否则，返回 false
    public static function isDistribution($id){
        if (!$id){
            return false;
        }
        if (!pdo_tableexists('sqtg_sun_distribution')) {
            return false;
        }
        $distribution = Distribution::get(['user_id'=>$id,'check_state'=>2]);
        return !!$distribution ? $distribution['id'] : false;
    }
}
