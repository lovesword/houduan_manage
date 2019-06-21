<?php
namespace app\model;

use app\base\model\Base;

class Config extends Base{
    public static function full_id($key,$value){
        $data = [
            'key'=>$key,
            'value'=>$value,
        ];
        $config = self::get(['key'=>$key]);
        $id = $config['id']?:0;
        if ($id){
            $data['id'] = $id;
        }
        return $data;
    }
    public static function get_value($key,$default=''){
        $data = self::get(['key'=>$key]);
        return $data&&$data['value']?$data['value']:$default;
    }
}