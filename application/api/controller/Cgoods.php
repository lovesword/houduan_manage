<?php
namespace app\api\controller;
use app\model\Category;
use app\model\Collection;
use app\model\Config;
use app\model\Goods;
use app\model\Leadergoods;
use app\model\Ordergoods;
use app\model\Storeleader;
use app\model\System;
use app\model\Goodsattrgroup;
use app\model\Goodsattrsetting;
use qcloudcos\Conf;
use think\Db;
use app\base\controller\Api;
class Cgoods extends Api
{
    public $goods;
    public $system;
    public $goodsattrgroup;
    public $goodsattrsetting;
    public function __construct()
    {
        $this->goods=new Goods();
        $this->system=new System();
        $this->goodsattrgroup=new Goodsattrgroup();
        $this->goodsattrsetting=new Goodsattrsetting();
    }
    //获取商品详情
    public function getGoods(){
        $goods_id=input('request.goods_id');

        $goods_model = new Goods();
        $goods = $goods_model
            ->with('attrgroups')
            ->field('id,name,state,unit,weight,store_id,pics,pic,price,original_price,stock,sales_num+virtual_num as sales_num,end_time as end_time2,service,details,use_attr')
            ->find($goods_id);

        $goods->pics=json_decode($goods->pics);
        $goods->is_outtime = $goods->state != 1 || $goods->end_time2 <= time();

        $goods['users'] = Ordergoods::where('t1.goods_id',$goods_id)
            ->whereNotIn('t1.state',[1,6])
            ->alias('t1')
            ->join('user t2','t2.id = t1.user_id')
            ->field('t2.img')
            ->distinct('t2.img')
            ->order('t1.create_time desc')
            ->limit(17)
            ->select();

        $leader_has_goods = true;
        while (true){
            $leader_id = input('request.leader_id',0);
            if ($leader_id){
//                判断商家选择团长
                $storeleader_count = Storeleader::where('leader_id',$leader_id)
                    ->where('store_id',$goods->store_id)
                    ->count();
                if (!$storeleader_count){
                    $leader_has_goods = false;
                    break;
                }

//                判断团长主动选择开关
                $leader_choosegoods_switch = Config::get_value('leader_choosegoods_switch',0);
                if (!$leader_choosegoods_switch){
                    break;
                }

//                判断团长选择商品
                $leadergoods_count = Leadergoods::where('leader_id',$leader_id)
                    ->where('goods_id',$goods_id)
                    ->count();
                if (!$leadergoods_count){
                    $leader_has_goods = false;
                }
            }

            break;
        }
        $goods['leader_has'] = $leader_has_goods;

        success_withimg_json($goods);
    }
    public function getGoodsUsers(){
        $goods = Goods::get(input('request.goods_id'));

        $list = Ordergoods::where('t1.goods_id',$goods->id)
            ->whereNotIn('t1.state',[1,6])
            ->alias('t1')
            ->join('user t2','t2.id = t1.user_id')
            ->field('t2.img')
            ->distinct('t2.img')
            ->order('t1.create_time desc')
            ->limit(17)
            ->select();

        success_json($list);
    }
//    获取分类商品
//    传入：
//          cat_id:分类id
//          page:第几页
//          limit:每页数据量
//          leader_id:团长id
    public function getGoodses(){
        //条件
        $query = function ($query){
            $leader_choosegoods_switch = Config::get_value('leader_choosegoods_switch',0);
            if (!$leader_choosegoods_switch){
                $store_ids = Storeleader::where('leader_id',input('request.leader_id'))->column('store_id');
                if (!count($store_ids)){
                    $query->where('1=2');
                    return;
                }
                $query->where('store_id',['in',$store_ids]);
            }else{
                $goods_ids = Leadergoods::where('leader_id',input('request.leader_id'))->column('goods_id');
                if (!count($goods_ids)){
                    $query->where('1=2');
                    return;
                }
                $query->where('id',['in',$goods_ids]);
            }



            $query->where('state',1);
            $query->where('check_state = 2 or store_id = 0');

            $t = time();
            $query->where("begin_time <= $t and end_time >= $t");

            $cat_id = input('request.cat_id',-1);
            switch ($cat_id){
                case 0:
                    break;
                case -1://热门推荐
                    $query->where('is_hot',1);
                    break;
                case -2://今日上新
                    $time1 = strtotime(date('Y-m-d',time()));//获取今天凌晨的时间戳
                    $time2 = strtotime(date("Y-m-d",strtotime("+1 day")));//获取明天凌晨的时间戳
                    $query->where("begin_time >= $time1 and begin_time < $time2");
                    break;
                default:
                    $query->where('cat_id',$cat_id);
            }
        };

//        查询数据
        $goods_model = new Goods();
        $goods_model->fill_order_limit();//分页，排序
        $list = $goods_model->with('attrgroups')->where($query)
            ->field('id,name,unit,store_id,price,original_price,stock,sales_num+virtual_num as sales_num,pic,end_time as end_time2,use_attr')
            ->order('store_id,index')
            ->select();

        success_withimg_json($list);
    }
    //获取选完规格后商品信息
    public function getGoodsAttrs(){
        $goods_id=input('request.goods_id');
        $attr_ids=input('request.attr_ids');

        $list = Goodsattrsetting::where('goods_id',$goods_id)
            ->where('attr_ids',$attr_ids)
            ->select();

        success_withimg_json($list);
    }
    //检测商品条件
    public function checkGoods(){
        global $_W;
        $gid=input('request.gid');
        $num=input('request.num');
        $attr_ids=input('request.attr_ids');
        $goods=$this->goods->get($gid);
        if(empty($goods)){
            $this->ajaxError('商品不存在');
        }
        if($goods['use_attr']==0){
            if($num>$goods['stock']){
                $this->ajaxError('库存不足');
            }
        }else if($goods['use_attr']==1){
            $goodsattrsetting=$this->goodsattrsetting->where(array('goods_id'=>$gid,'attr_ids'=>$attr_ids))->find();
            if($num>$goodsattrsetting['stock']){
                $this->ajaxError('库存不足');
            }
        }
        $this->ajaxSuccess('成功');
    }
}
