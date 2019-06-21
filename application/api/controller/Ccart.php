<?php
namespace app\api\controller;

use app\base\controller\Api;
use app\model\Cart;
use app\model\Goods;
use app\model\Goodsattrsetting;


class Ccart extends Api
{
//    加入购物车
//        传入：
//            user_id:用户id
//            leader_id:团长id
//            goods_id:商品id
//            attr_ids:商品规格ids
//            num:数量
    public function joinCart(){
        $user_id = input('request.user_id',0);
        $leader_id = input('request.leader_id',0);
        $goods_id = input('request.goods_id',0);
        $store_id = input('request.store_id',0);
        $attr_ids = input('request.attr_ids','');
        $attr_names = input('request.attr_names','');
        $num = input('request.num',1);

        $cart = Cart::get([
            'user_id'=>$user_id,
            'leader_id'=>$leader_id,
            'goods_id'=>$goods_id,
            'store_id'=>$store_id,
            'attr_ids'=>$attr_ids,
        ]);

        $num += $cart->num ?: 0 ;

        $goods = new Goods();
        $goods->checkStock($goods_id,$attr_ids,$num);

        if ($cart){
            $ret = $cart->save(['num'=>$num]);
        }else{
            $cart = new Cart();
            $ret = $cart->allowField(true)->save([
                'user_id'=>$user_id,
                'leader_id'=>$leader_id,
                'goods_id'=>$goods_id,
                'store_id'=>$store_id,
                'attr_ids'=>$attr_ids,
                'attr_names'=>$attr_names,
                'num'=>$num,
            ]);
        }

        if ($ret){
            success_json('加入购物车成功');
        }else{
            error_json('加入购物车失败');
        }
    }

//    修改购物车数量
//        传入：
//            cart_id:购物车id
//            num:数量
    public function updateCart(){
        $num = input('request.num');
        $id = input('request.cart_id');
        if (!$num){
            $ret = Cart::destroy($id);
            $msg = '删除';
        }else{
            $ret = false;
            $msg = '修改';

            $cart = Cart::get(input('request.cart_id',0));
            if ($cart){
                if ($cart->num < $num){
                    $goods = new Goods();
                    $goods->checkStock($cart->goods_id,$cart->attr_ids,$num);
                }

                $ret = $cart->save(['num'=>$num]);
            }
        }

        if ($ret){
            success_json($msg.'成功');
        }else{
            error_json($msg.'失败');
        }

    }

//    获取购物车列表
//        传入：
//            user_id:用户id
//            leader_id:团长id
    public function getCarts(){
        $user_id = input('request.user_id');
        $leader_id = input('request.leader_id');

        $list = Cart::where('t1.user_id',$user_id)
            ->where('t1.leader_id',$leader_id)
            ->alias('t1')
            ->join('goodsattrsetting t2','t2.goods_id = t1.goods_id and t2.attr_ids = t1.attr_ids','LEFT')
            ->join('goods t3','t3.id = t1.goods_id and t3.state = 1 and t3.end_time>'.time())
            ->field('t1.*,t3.name,coalesce(t2.price,t3.price) as price,case when t2.pic then t2.pic else t3.pic end pic,t3.pics,t3.use_attr')
            ->select();
        foreach ($list as &$item) {
            if (!$item['use_attr']){
                $pics = json_decode($item['pics'],true);
                $item['pic'] = count($pics)?$pics[0]:$item['pic'];
            }
        }

        success_withimg_json($list);
    }

//    批量删除购物车
    public function deleteCarts(){
        $cart_ids = input('request.cart_ids');
        $ret = Cart::destroy($cart_ids);
        if ($ret){
            success_json('删除成功');
        }else{
            error_json('删除失败');
        }
    }

    public function checkStock(){
        $cart_ids = input('request.cart_ids');
        $carts = Cart::where('id',['in',$cart_ids])->select();

        $ret = [];

        foreach ($carts as $cart) {
            $goods = Goods::get($cart->goods_id);

            $stock = $goods->stock;
            if ($cart->attr_ids){
                $setting = Goodsattrsetting::get([
                    'goods_id'=>$cart->goods_id,
                    'attr_ids'=>$cart->attr_ids,
                ]);
                $stock = $setting->stock;
            }
            if ($stock < $cart->num){
                $ret[] = [
                    'id'=>$cart->id,
                    'num'=>$stock,
                ];
            }
        }
        success_json($ret);
    }
}












