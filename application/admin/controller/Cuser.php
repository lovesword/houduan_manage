<?php
namespace app\admin\controller;

use app\base\controller\Admin;
use app\model\Memberconf;
use app\model\Userbalancerecord;

class Cuser extends Admin
{
    public function get_list(){
        $model = $this->model;

        //条件
        $query = function ($query){
            //关键字搜索
            $key = input('get.key');
            if ($key){
                $query->where('name','like',"%$key%");
            }
        };

        //排序、分页
        $model->fill_order_limit();

        $list = $model->where($query)->select();

        return [
            'code'=>0,
            'count'=>$model->where($query)->count(),
            'data'=>$list,
            'msg'=>'',
        ];
    }
    public function outCSV(){
        $model = $this->model;

        //条件
        $query = function ($query){
            //关键字搜索
            $key = input('get.key');
            if ($key){
                $query->where('name','like',"%$key%");
            }
            $ids = input('get.ids');
            if($ids){
                $query->where('id','in',$ids);
            }
        };

        $list = $model->where($query)
            ->field('name,tel')
            ->select();

        $this->toCSV('用户表'.date('ymdhis').'.csv',['用户','电话'],json_decode(json_encode($list),true));
    }
}
