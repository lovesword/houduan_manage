<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/29
 * Time: 14:53
 */
namespace app\admin\controller;

use app\base\controller\Admin;
use app\model\Pinclassify;
use app\model\Pingoods;
use app\model\Pingoodsattr;
use app\model\Pingoodsattrgroup;
use app\model\Pingoodsattrsetting;
use app\model\Pinladder;
use app\model\Pinorder;
use app\model\Shop;
use think\Db;

class Cpingoods extends Admin
{
    public function get_goods_list(){
        global $_W;
        $model =new Pingoods();
        //排序、分页
        $model->fill_order_limit();
        $where['uniacid']=$_W['uniacid'];
        $where['is_del']=0;
        $where['store_id'] = $_SESSION['admin']['store_id']?$_SESSION['admin']['store_id']:0;
        //关键字搜索
        $key = input('get.key');
        if ($key){
            $where['name']=['like',"%$key%"];
        }
        $cat_id = input('get.cat_id');
        if ($cat_id){
            $where['cid']=$cat_id;
        }
        $order['create_time']='desc';
        $list = $model->where($where)->order($order)->select();
        return [
            'code'=>0,
            'count'=>$model->where($where)->count(),
            'data'=>$list,
            'msg'=>'',
        ];
    }
    /**
     * 所有分类
     */
    public function allcategory(){
        $model = new Pinclassify();
        $model->field("id,name as text");
        $list = $model->where('state',1)->select();
        return $list;
    }
    /**
     * 编辑
    */
    public function edit(){
        global $_W,$_GPC;
        $this->view->_W = $_W;
        $this->view->_GPC = $_GPC;
        $id = input('get.id');

        //        获取规格设置
        $attrsetting_model = new Pingoodsattrsetting();
        $attrsetting_list = $attrsetting_model->where('goods_id',$id)->select();

//        获取规格分组信息
        $attrgroup_model = new Pingoodsattrgroup();
        $attrgroup_list = $attrgroup_model->with('attrs')->where('goods_id',$id)->select();
//        var_dump($attrgroup_list);exit();
        foreach ($attrgroup_list as &$item) {
            $attrs = [];
            foreach ($item['attrs'] as $attr) {
//                处理规格设置
                foreach ($attrsetting_list as &$attrsetting) {
                    if(strpos($attrsetting['key'],",{$attr['name']},") !== false){
                        $attrsetting[$item['name']] = $attr['name'];
                    }
                }

                $attrs[] = $attr['name'];
            }
        }
        $this->view->attrgroup_list = $attrgroup_list;


        $this->view->attrsetting_list = $attrsetting_list;
//        $model=new Pingoods();
        $info =Pingoods::get(['id'=>$id]);
        $info['pics'] = json_decode($info['pics']);
        if($info['start_time']){
            $info['start_time']=date('Y-m-d H:i:s',$info['start_time']);
        }
        if($info['end_time']){
            $info['end_time']=date('Y-m-d H:i:s',$info['end_time']);
        }
        $this->view->info = $info;
        return view('edit');
    }
    /**
     * 删除
     */
    public function deletegoods(){
        $ids = input("post.ids");
        $ret = $this->model->where('id','in',$ids)->update(['is_del'=>1]);
        if($ret){
            return array(
                'code'=>0,
            );
        }else{
            return array(
                'code'=>1,
                'msg'=>'删除失败',
            );
        }
    }
    /**
     * 保存规格名称
    */
    public function savegroupname(){
        $info=new Pingoodsattrgroup();
//        $data=input('post.');
        $id = input('post.id');
        if ($id){
            $info = $info->get($id);
        }
        $ret = $info->allowField(true)->save(input('post.'));

        if($ret){
            return array(
                'code'=>0,
                'data'=>$info->id,
            );
        }else{
            return array(
                'code'=>1,
                'msg'=>'保存失败',
            );
        }
    }
    public function deletegoodsattrgroup(){
        $info=new Pingoodsattrgroup();
        $ids = input("post.ids");
        $ret =$info->where('id','in',$ids)->delete();
        if($ret){
            return array(
                'code'=>0,
            );
        }else{
            return array(
                'code'=>1,
                'msg'=>'删除失败',
            );
        }
    }

    public function savegroupvalue(){
        $info=new Pingoodsattr();
//        $data=input('post.');
        $id = input('post.id');
        if ($id){
            $info = $info->get($id);
        }
        $ret = $info->allowField(true)->save(input('post.'));

        if($ret){
            return array(
                'code'=>0,
                'data'=>$info->id,
            );
        }else{
            return array(
                'code'=>1,
                'msg'=>'保存失败',
            );
        }
    }
    public function deletegoodsattr(){
        $info=new Pingoodsattr();
        $ids = input("post.ids");
        $ret =$info->where('id','in',$ids)->delete();
        if($ret){
            return array(
                'code'=>0,
            );
        }else{
            return array(
                'code'=>1,
                'msg'=>'删除失败',
            );
        }
    }
    /**
     * 数据保存（新增、编辑）
    */
    public function saves(){
        global $_W;
        $info = $this->model;
        $id = input('post.id');
        if ($id) {
            $info = $info->get($id);
        }

        $data = input('post.');
//        var_dump($data);exit;
//        补充商户id信息
        if (!$data['store_id']){
            $data['store_id'] = $_SESSION['admin']['store_id'];
        }
        $data['check_state']=2;
//        var_dump($data);exit;

        if($data['store_id']>0){
            $conf_add=\app\model\Config::get_value('pin_add_check');
            $conf_update=\app\model\Config::get_value('pin_update_check');
            if(($conf_add['pin_add_check']==1)&&(!$id)){
                $data['check_state']=1;
            }elseif (($conf_update['pin_update_check']==1)&&($id)){
                $data['check_state']=1;
            }else{
                $data['check_state']=2;
            }
        }
        $data['start_time']=strtotime(input('post.start_time'));
        $data['end_time']=strtotime(input('post.end_time'));
        $data['pics'] = json_encode($data['pics']);
        $ret = $info->allowField(true)->save($data);
        if($ret){

            if(input('post.use_attr')==1){
//            修改分组信息的 goods_id
                $attrs_data = $data['attrs_data'];
                $attrs_data = json_decode($attrs_data);
                $attrgroup_model = new Pingoodsattrgroup();
                $group_list = [];
                foreach ($attrs_data as $key => $attr_group) {
                    if (!$attr_group){
                        continue;
                    }
                    $group_data = [];
                    $group_data['id'] = $key;
                    $group_data['goods_id'] = $info->id;
                    $group_list[] = $group_data;
                }
                $attrgroup_model->saveAll($group_list);

//              保存规格设置
                $attrsettings = $data['attr'];
                $attrsettings = json_decode($attrsettings);

                $attrsetting_model = new Pingoodsattrsetting();
                $attrsetting_model->where('goods_id',$info->id)->delete();

                $num = 0;
                foreach ($attrsettings as $attrsetting) {
                    $attrsetting = (array)$attrsetting;
                    $num += $attrsetting['stock'];
                    $attrsetting_model = new Pingoodsattrsetting();
                    $attrsetting = (array)$attrsetting;
                    $attrsetting['goods_id'] = $info->id;

//                name
                    $names = [];
                    foreach ($attrs_data as $key => $attr_group) {
                        if (!$attr_group){
                            continue;
                        }
                        $attr_group = (array)$attr_group;
                        $names[] = $attrsetting[$attr_group['name']];
                    }
                    $attrsetting['key'] = ','.implode(',',$names).',';
                    $attrsetting['attr_ids'] = $attrsetting['ids']? ','.implode(',',$attrsetting['ids']).',':$attrsetting['attr_ids'];

                    $attrsetting_model->allowField(true)->save($attrsetting);
                }
                $info->stock = $num;
                $info->save();
            }
            if(!$id&&(input('is_ladder')==1)){
                $ladder=json_decode(input('post.ladder_info'),true);
                $ladder_list=array();
                foreach ($ladder as $lkey=>$lval){
                    $ldata = [];
                    $ldata['groupnum'] = $lval['groupnum'];
                    $ldata['groupmoney'] = $lval['groupmoney'];
                    $ldata['goods_id'] = $info->id;
                    $ldata['uniacid']=$_W['uniacid'];
                    $ldata['create_time']=time();
                    $ladder_list[] = $ldata;
                }
               Db::name('pinladder')->insertAll($ladder_list);
            }
            return array(
                'code'=>0,
                'data'=>$info->id,
            );
        }else{
            return array(
                'code'=>1,
                'msg'=>'保存失败',
            );
        }
    }
    /**
     * 商品选择页
    */
    public function goodsselect(){
        return view('goodsselect');
    }
    public function select_goods_list(){
        global $_W;
        $model =new \app\model\Goods();
        //排序、分页
        $model->fill_order_limit();
        $where['uniacid']=$_W['uniacid'];
        $key=input('get.key');
        if($key){
            $where['name']=['like',"%$key%"];
        }
//        $where['check_state']=2;
        $where['state']=1;
        $store_id=$_SESSION['admin']['store_id']?$_SESSION['admin']['store_id']:0;
//        var_dump($store_id);exit;
        $where['store_id']=$store_id;
//        var_dump($where);exit;
        $list = $model->where($where)->select();
//        var_dump($list);exit;
        foreach ($list as $key =>$value){
            $list[$key]['pic']=$_W['attachurl'].$value['pic'];
        }
        return [
            'code'=>0,
            'count'=>$model->where($where)->count(),
            'data'=>$list,
            'msg'=>'',
        ];
    }

    /**
     * 拼团设置
    */
    public  function pinset(){
        global $_W,$_GPC;
        $this->view->_W = $_W;
        $this->view->_GPC = $_GPC;
        $info = [];

        $info['pin_add_check'] = \app\model\Config::get_value('pin_add_check',0);
        $info['pin_update_check'] = \app\model\Config::get_value('pin_update_check',0);
        $info['pin_rules'] = \app\model\Config::get_value('pin_rules',0);

        $this->view->info = $info;
        return view();
    }
    public function saveset(){
        $info = new \app\model\Config();

        $data = input('post.');

        $list = [];

        $list[] = \app\model\Config::full_id('pin_add_check',$data['pin_add_check']);
        $list[] = \app\model\Config::full_id('pin_update_check',$data['pin_update_check']);
        $list[] = \app\model\Config::full_id('pin_rules',$data['pin_rules']);

        $ret = $info->allowField(true)->saveAll($list);

        if($ret){
            return array(
                'code'=>0,
            );
        }else{
            return array(
                'code'=>1,
                'msg'=>'保存失败',
            );
        }
    }
    /**
     * 所有商品
     */
    public function allgoods(){
        global $_W;
        $model = new Pingoods();
        $model->field("id,name as text");
        $where['uniacid']=$_W['uniacid'];
        $where['is_del']=0;
        $where['check_state']=2;
        $store= $_SESSION['admin']['store_id']?$_SESSION['admin']['store_id']:0;
        $where['store_id'] =$store;
        $list = $model->where($where)->select();
        return $list;
    }
    /**
     * 订单列表
     */
    public function orderlist(){
        global $_W,$_GPC;
        $this->view->_W = $_W;
        $this->view->_GPC = $_GPC;
        return view('orderlist');
    }
    public function get_order_list(){
        global $_W;
        $model =new Pinorder();
        //排序、分页
        $model->fill_order_limit();
        $where['uniacid']=$_W['uniacid'];
        $where['is_del']=0;
        $where['store_id'] = $_SESSION['admin']['store_id']?$_SESSION['admin']['store_id']:0;
//        $where['goods_id']=input('get.id');
        //关键字搜索
        $key = input('get.key');
        if ($key){
            $where['out_trade_no']=['like',"%$key%"];
        }
        $goods_id = input('get.goods_id');
        if ($goods_id){
            $where['goods_id']=$goods_id;
        }
        $type=input('get.type');
        if($type>0){
            $where['order_status']=$type-0-1;
        }
        $order['id']='desc';
//        var_dump($where);exit;
        $list = $model->where($where)->order($order)->select();
        foreach ($list as $key =>$value){
//            $list[$key]['dh_time']=date('Y-m-d H:i:s',$value['dh_time']);
            $goods=new Pingoods();
            $list[$key]['gname']=$goods->mfind(['id'=>$value['goods_id']],'name')['name'];
        }
        return [
            'code'=>0,
            'count'=>$model->where($where)->count(),
            'data'=>$list,
            'msg'=>'',
        ];
    }
    /**
     * 查看订单详情
     */
    public function see(){
        global $_W,$_GPC;
        $this->view->_W = $_W;
        $this->view->_GPC = $_GPC;
        $model =new Pinorder();
        $id=input('get.id');
        if($id){
            $info =Pinorder::get($id);
            $info['username'] = \app\model\User::get(['id'=>$info['user_id']])['name'];
            $info['gname'] = Pingoods::get(['id'=>$info['goods_id']])['name'];
//            $info['dh_time'] = date('Y-m-d H:i:s',$info['dh_time']);
            $info['pay_status'] =$info['is_pay']?'已支付':'未支付';
            $info['pay_time'] = $info['pay_time']?date('Y-m-d H:i:s',$info['pay_time']):'';
            $info['send_time'] = $info['send_time']?date('Y-m-d H:i:s',$info['send_time']):'';
            $info['use_time'] = $info['use_time']?date('Y-m-d H:i:s',$info['use_time']):'';
            $info['refund_time'] = $info['refund_time']?date('Y-m-d H:i:s',$info['refund_time']):'';
            $info['group_time'] = $info['group_time']?date('Y-m-d H:i:s',$info['group_time']):'';
            $info['address']=$info['province'].$info['city'].$info['area'].$info['address'];
            $info['mdaddress']=Shop::get(['id'=>$info['shop_id']])['name'];
            $this->view->info=$info;

        }
        return view('see');
    }
    /**
     * 发货
     */
    public function send(){
        global $_W,$_GPC;
        $this->view->_W = $_W;
        $this->view->_GPC = $_GPC;
        $model =new Pinorder();
        $id=input('get.id');
        if($id){
            $info =Pinorder::get($id);
            $info['username'] = \app\model\User::get(['id'=>$info['user_id']])['name'];
            $info['gname'] = Pingoods::get(['id'=>$info['goods_id']])['name'];
//            $info['create_time'] = date('Y-m-d H:i:s',$info['dh_time']);
            $info['pay_status'] =$info['is_pay']?'已支付':'未支付';
            $info['pay_time'] = $info['pay_time']?date('Y-m-d H:i:s',$info['pay_time']):'';
            $info['send_time'] = $info['send_time']?date('Y-m-d H:i:s',$info['send_time']):'';
            $info['group_time'] = $info['group_time']?date('Y-m-d H:i:s',$info['group_time']):'';

            $this->view->info=$info;

        }
        return view('send');
    }
    public function savesend(){
        $oid=input('post.id');
        if($oid){
            $order=new Pinorder();
            $orderinfo=$order->mfind(['id'=>$oid,'is_del'=>0]);
            if($orderinfo['order_status']==2){
                if($orderinfo['sincetype']==1){
                    $data['express_delivery']=input('post.express_delivery');
                    $data['express_orderformid']=input('post.express_orderformid');
                    $data['order_status']=3;
                }else{
                    $data['order_status']=4;
                    $data['use_time']=time();
                }
                $data['send_time']=time();
                $res=$order->allowField(true)->save($data,['id'=>$oid]);
                if($res){
                    if($orderinfo['sincetype']==2){
                        if($orderinfo['store_id']>0){
                            $order=new \app\model\Order();
                            $order->confirmAddStoreMoney($orderinfo['store_id'],$orderinfo['order_amount'],2,$orderinfo['user_id'],$oid,$orderinfo['order_num'],$orderinfo['num']);
                        }
                    }
                    return array(
                        'code'=>0,
                    );
                }else{
                    return array(
                        'code'=>1,
                        'msg'=>'失败',
                    );
                }
            }else{
                return array(
                    'code'=>1,
                    'msg'=>'当前订单无法发货/取货',
                );
            }

        }
    }
    /**
     * 选择商品复制
    */
    public function copygoods(){
        $id=input('post.id',0);
        $info=\app\model\Goods::get($id);
        $data['name']=$info['name'];
        $data['pin_price']=$info['price'];
        $data['price']=$info['original_price'];
        $data['original_price']=$info['original_price'];
        $data['stock']=$info['stock'];
        $data['unit']=$info['unit'];
        $data['weight']=$info['weight'];
        $data['details']=$info['details'];
        $data['pic']=$info['pic'];
        $data['pics']=$info['pics'];
        $data['postagerules_id']=$info['postagerules_id'];
        $data['check_state']=1;
        $goods = new Pingoods();
        $res=$goods->allowField(true)->save($data);
//        var_dump($goods->id);exit;
        if($res){
            return array(
                'code'=>0,
                'data'=>$goods->id,
            );
        }else{
            return array(
                'code'=>1,
                'msg'=>'失败',
            );
        }
    }
    /**
     * 商品审核
    */
    public function checks(){
        return view('checks');
    }
    public function get_checkgoods_list(){
        global $_W;
        $model =new Pingoods();
        //排序、分页
        $model->fill_order_limit();
        $where['uniacid']=$_W['uniacid'];
        $where['is_del']=0;
        $where['check_state']=1;
        $store_id=$_SESSION['admin']['store_id'];
        if($store_id>0){
            $where['store_id']=$store_id;
        }
        //关键字搜索
        $key = input('get.key');
        if ($key){
            $where['name']=['like',"%$key%"];
        }
        $cat_id = input('get.cat_id');
        if ($cat_id){
            $where['cid']=$cat_id;
        }
        $order['create_time']='desc';
        $list = $model->where($where)->order($order)->select();
        return [
            'code'=>0,
            'count'=>$model->where($where)->count(),
            'data'=>$list,
            'msg'=>'',
        ];
    }
    public function checksee(){
        $id=input('get.id');
        if($id){
            $info =Pingoods::get($id);
            $info['pics'] = json_decode($info['pics']);
            if($info['start_time']){
                $info['start_time']=date('Y-m-d H:i:s',$info['start_time']);
            }
            if($info['end_time']){
                $info['end_time']=date('Y-m-d H:i:s',$info['end_time']);
            }
            $this->view->info=$info;
        }
        return view('checksee');
    }
}