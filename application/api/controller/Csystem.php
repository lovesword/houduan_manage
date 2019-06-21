<?php
namespace app\api\controller;

use app\model\Ad;
use app\model\Announcement;
use app\model\Customize;
use app\model\Homepage;
use app\model\Pluginkey;
use app\model\Suspension;
use app\model\System;
use think\Db;
use app\base\controller\Api;

class Csystem extends Api
{
    public function getSetting(){
        $info = System::get_curr();
        success_withimg_json($info);
    }
    //TODO::图片地址
    public function getImgurl(){
        global $_W,$_GPC;
        $info['img_root'] = $_W['attachurl'];
        return_json('success',0,$info);
    }
    //TODO::菜单栏自定义图标
    public function getMenuicon(){
        global $_W,$_GPC;
        $cus=new Customize();
        $where['type']=2;
        $where['state']=1;
        $order['sort']='asc';

        $list=$cus->where($where)->order($order)->select();
        $info['img_root'] = $_W['attachurl'];
        return_json('success',0,$list,$info);
    }
    //TODO;:底部导航自定义图标
    public function getNavicon(){
        global $_W,$_GPC;
        $cus=new Customize();
        $where['type']=3;
        $where['state']=1;
        $order['sort']='asc';

        $list=$cus->where($where)->order($order)->select();
        $info['img_root'] = $_W['attachurl'];
        return_json('success',0,$list,$info);
    }
    //TODO::悬浮图标
    public function suspensionIcon(){
        global $_W;
        $sus=new Suspension();
        $info=$sus->get_curr();
        $imgroot['img_root'] = $_W['attachurl'];
        return_json('success',0,$info,$imgroot);
    }
    //TODO::首页自定义
    public function homepage(){
        global $_W;
//        $plug=new Pluginkey();
        $pluginfo=Db::name('pluginkey')->where('plugin_id',4)->find();
//        var_dump($pluginfo['id']);exit;
        $imgroot['img_root'] = $_W['attachurl'];
        if($pluginfo){
            $model=new Homepage();
            $info=$model->mfind(['uniacid'=>$_W['uniacid'],'is_default'=>1]);
            $info['index_value']=json_decode($info['index_value'],true);

            return_json('success',0,$info,$imgroot);
        }else{
            return_json('success',0,array(),$imgroot);
        }

    }
}
