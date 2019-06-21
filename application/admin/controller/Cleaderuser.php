<?php
namespace app\admin\controller;

use app\base\controller\Admin;
use app\model\Leaderuser;
use app\model\Storeleader;

class Cleaderuser extends Admin
{

//    获取列表页数据
    public function get_list(){
        $getModel = function (){
            //关键字搜索
            $key = input('get.key');
            if ($key){
                $model = Leaderuser::hasWhere('user',['name','like',"%{$key}%"]);
            }else{
                $model = new Leaderuser();
            }

            $leader_id = input('get.leader_id');
            if ($leader_id){
                $model->where('leader_id',$leader_id);
            }

            $leader_ids = Storeleader::where('store_id',$_SESSION['admin']['store_id'])
                ->column('leader_id');
            if (!count($leader_ids)){
                $model->where('1=2');
            }else{
                $model->where('leader_id','in',$leader_ids);
            }

            $model->page(input('request.page',1));
            $model->limit(input('request.limit',10));

            return $model;
        };


        $list = $getModel()->with('leader,user')->select();

        return [
            'code'=>0,
            'count'=>$getModel()->count(),
            'data'=>$list,
            'msg'=>'',
        ];
    }
}
