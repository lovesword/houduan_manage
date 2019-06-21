<?php
namespace app\api\controller;
use app\base\controller\Api;
use app\model\Deliveryordergoods;
use app\model\Goods;
use app\model\Leader;
use app\model\Leaderbill;
use app\model\Leadergoods;
use app\model\Leaderuser;
use app\model\Leaderwithdraw;
use app\model\Order;
use app\model\Ordergoods;
use app\model\Store;
use app\model\Storeleader;
use app\model\User;
use app\model\Usercode;
use app\model\Config;
use \think\Db;
use think\Exception;

class Cleader extends Api
{
//    获取附近团长列表
//    传入：
//          longitude:经度
//          latitude:纬度
//          page:第几页
//          limit:每页数据量
//          key:搜索关键字
    public function getNearLeaders(){
        global $_W;
        $key = input('request.key','');
        $longitude = input('request.longitude',0);
        $latitude = input('request.latitude',0);
        $page = input('request.page',1);
        $limit = input('request.limit',10);
        $start = ($page-1)*$limit;
//        所有需要返回的分类id
        $list = Db::query("
            select t1.id,t1.name,t1.address,t2.img as pic,t1.community,t1.tel
            ,convert(acos(cos($latitude*pi()/180 )*cos(t1.latitude*pi()/180)*cos($longitude*pi()/180 -t1.longitude*pi()/180)+sin($latitude*pi()/180 )*sin(t1.latitude*pi()/180))*6370996.81,decimal)  as distance 
            from ".tablename('sqtg_sun_leader')." t1
            left join ".tablename('sqtg_sun_user')." t2 on t2.id = t1.user_id
            where t1.check_state = 2
            and t1.uniacid = {$_W['uniacid']}
            and (t1.name like '%$key%' or t1.community like '%$key%')
            order by distance
            limit $start,$limit
        ");
        success_json($list);
    }
//    获取团长信息
//    传入：
//          longitude:经度
//          latitude:纬度
//          leader_id
    public function getLeader(){
        $longitude = input('request.longitude',0);
        $latitude = input('request.latitude',0);
        $leader_id = input('request.leader_id',0);

//        所有需要返回的分类id
        $list = Db::query("
            select t1.id,t1.name,t1.address,t2.img as pic,t1.community,t1.tel
            ,convert(acos(cos($latitude*pi()/180 )*cos(t1.latitude*pi()/180)*cos($longitude*pi()/180 -t1.longitude*pi()/180)+sin($latitude*pi()/180 )*sin(t1.latitude*pi()/180))*6370996.81,decimal)  as distance 
            from ".tablename('sqtg_sun_leader')." t1
            left join ".tablename('sqtg_sun_user')." t2 on t2.id = t1.user_id
            where t1.id = {$leader_id}
            order by distance
        ");
        success_json($list[0]);
    }

//    团长申请
    public function applyLeader(){
        global $_W;
        $data = input('request.');
        $data['check_state'] = 1;
        $data['fail_reason'] = '';

        $id = input('request.id');
        $leader_model = $id?Leader::get($id):new Leader();

        $ret = $leader_model->allowField(true)->save($data);

        if ($ret){
            success_json([
                'code'=>0,
                'data'=>$leader_model,
            ]);
        }else{
            error_json('保存失败，请重新提交申请');
        }

    }

//    获取我的团长信息
    public function getMyLeader(){
        $user_id = input('request.user_id');
        $data = Leader::get(['user_id'=>$user_id],'User');

        if ($data){
            $data['is_leader'] = 1;
        }else{
            $leader = Leaderuser::get(['user_id'=>$user_id]);
            $data = Leader::get($leader->leader_id,'User');
        }

        if ($data && $data['check_state'] == 2){
            $time1 = strtotime(date('Y-m-d',time()));//获取今天凌晨的时间戳
            $time2 = strtotime(date("Y-m-d",strtotime("-1 day")));//获取昨天凌晨的时间戳
            $data['today_sum'] = Leaderbill::where('leader_id',$data['id'])
                ->where('create_time',['>=',$time1])
                ->sum('money');
            $data['today_count'] = Order::where('leader_id',$data['id'])
                ->whereNotIn('state',[1,6])
                ->where('pay_time',['>=',$time1])
                ->count();
            $data['yesterday_sum'] = Leaderbill::where('leader_id',$data['id'])
                ->where('create_time',['>=',$time2])
                ->where('create_time',['<',$time1])
                ->sum('money');
            $data['yesterday_count'] = Order::where('leader_id',$data['id'])
                ->whereNotIn('state',[1,6])
                ->where('pay_time',['>=',$time2])
                ->where('pay_time',['<',$time1])
                ->count();
            $data['total_sum'] = Leaderbill::where('leader_id',$data['id'])
//                ->where('create_time',['>=',$time1])
                ->sum('money');
            $data['total_count'] = Order::where('leader_id',$data['id'])
                ->whereNotIn('state',[1,6])
//                ->where('pay_time',['>=',$time2])
//                ->where('pay_time',['<',$time1])
                ->count();
            $data['withdraw_switch'] = Config::get_value('leader_withdraw_switch',0);

            $data['order_count']=[
                'state2' => Ordergoods::where('leader_id',$data->id)
                    ->where('state',2)
                    ->count('distinct goods_id,batch_no'),
                'state3' => Ordergoods::where('leader_id',$data->id)
                    ->where('state',3)
                    ->count('distinct goods_id,batch_no'),
                'state4' => Ordergoods::where('leader_id',$data->id)
                    ->where('state',4)
                    ->count('distinct goods_id,batch_no'),
                'state5' => Ordergoods::where('leader_id',$data->id)
                    ->where('state',5)
                    ->count('distinct goods_id,batch_no'),
            ];

            $data['goodses'] = [
                'count' => Leadergoods::where('leader_id',$data->id)->count(),
            ];
            $data['users'] = [
                'count' => Leaderuser::where('leader_id',$data->id)->count(),
            ];

            $data['choosegoods_switch'] = Config::get_value('leader_choosegoods_switch',0);
        }

        $detail = Config::get_value('leader_apply_detail');

        success_withimg_json($data,['apply_detail'=>$detail]);
    }
//    获取订单状态列表
    public function getOrderStates(){
        $data = [
            [
                'state'=>2,
                'text'=>'待配送',
            ],
            [
                'state'=>3,
                'text'=>'配送中',
            ],
            [
                'state'=>4,
                'text'=>'待自提',
            ],
            [
                'state'=>5,
                'text'=>'已完成',
            ],
        ];
        success_json($data);
    }
//    获取订单列表
    public function getOrders(){
        $where = [];
        $where['leader_id'] = input('request.leader_id',0);
        $state = input('request.state',0);
        if ($state){
            $where['state'] = $state;
        }

//        Db::query('set sql_mode = 0;');
        $sqlmode = Db::query("select @@global.sql_mode;");
//        print_r($sqlmode);
        if(strpos($sqlmode["@@global.sql_mode"],'ONLY_FULL_GROUP_BY') !== false){
            Db::query("set @@global.sql_mode=(select replace(@@global.sql_mode,'ONLY_FULL_GROUP_BY','')); ");
        }

        $list = Ordergoods::where($where)
//            ->where('state',['<>',-10])
//            ->group('goods_id,batch_no')
            ->page(input('request.page',1))
            ->limit(input('request.limit',10))
            ->order('create_time desc,id')
            ->field('goods_id,batch_no')
            ->distinct('goods_id,batch_no')
            ->select();

        foreach ($list as &$item) {
            $ordergoodses = Ordergoods::where('goods_id',$item['goods_id'])
                ->where('batch_no',$item['batch_no'])
                ->where($where)
                ->select();

//            $item = Goods::get($item['goods_id']);
            $item = $ordergoodses[0];
            $item['name'] = $item['goods_name'];
            $item['id'] = $item['goods_id'];

            $users = [];
            $attrs = [];
            $sum = 0;
            foreach ($ordergoodses as $ordergoods) {
                if(!in_array($ordergoods['user_id'],$users)){
                    $users[] = $ordergoods['user_id'];
                }
                $sum += $ordergoods['num'];
                if(!in_array($ordergoods['attr_ids'],array_keys($attrs))){
                    $attrs[$ordergoods['attr_ids']]=[
                        'attr_names'=>$ordergoods['attr_names']?:'无',
                        'num'=>$ordergoods['num'],
                    ];
                }else{
                    $attrs[$ordergoods['attr_ids']]['num'] += $ordergoods['num'];
                }
            }

            $item['userCount'] = count($users);
            $item['goodsSum'] = $sum;
            $item['attrs'] = $attrs;
            $item['state'] = $state;
        }

        success_withimg_json($list ?: []);
    }
//    获取团员列表
    public function getOrder(){
        $where = [];
        $where['leader_id'] = input('request.leader_id',0);
        $state = input('request.state',0);
        if ($state){
            $where['state'] = $state;
        }
        $goods_id = input('request.goods_id',0);
        if ($goods_id){
            $where['goods_id'] = $goods_id;
        }
        $batch_no = input('request.batch_no','');
        if ($batch_no){
            $where['batch_no'] = $batch_no;
        }

//        查询数据
//        $goods = Goods::get($goods_id);

        $ordergoodses = Ordergoods::where('')
            ->where($where)
            ->select();

        $goods = $ordergoodses[0];
        $goods['name'] = $goods['goods_name'];
        $goods['id'] = $goods['goods_id'];

        $users = [];
        $attrs = [];
        $sum = 0;
        foreach ($ordergoodses as $ordergoods) {
            if(!in_array($ordergoods['user_id'],array_keys($users))){
                $user = User::get($ordergoods['user_id']);
                $users[$ordergoods['user_id']] = [
                    'user'=>$user,
                    'num'=>$ordergoods['num']
                ];
            }else{
                $users[$ordergoods['user_id']]['num'] += $ordergoods['num'];
            }
            $sum += $ordergoods['num'];
            if(!in_array($ordergoods['attr_ids'],array_keys($attrs))){
                $attrs[$ordergoods['attr_ids']]=[
                    'attr_names'=>$ordergoods['attr_names'],
                    'num'=>$ordergoods['num'],
                ];
            }else{
                $attrs[$ordergoods['attr_ids']]['num'] += $ordergoods['num'];
            }
        }

        $goods['userCount'] = count($users);
        $goods['goodsSum'] = $sum;
        $goods['attrs'] = $attrs;
        $goods['state'] = $state;
        $goods['users'] = $users;
        success_withimg_json($goods);
    }
//    确认商家配送
    public function receiveGoodses(){
        $data = input('request.');

        $leader_id = $data['leader_id'];
        $goodses = $data['goodses'];
        $goodses = json_decode($goodses,true);

        foreach ($goodses as $goods) {
            $where = [
                'goods_id'=>$goods['goods_id'],
                'state'=>3,
            ];
            if ($goods['attr_ids']){
                $where['attr_ids'] = $goods['attr_ids'];
            }
//            更新用户订单商品状态
            $num = $goods['num'];
            $list = Ordergoods::where('leader_id',$leader_id)
                ->where($where)
                ->limit($num)
                ->order('create_time')
                ->select();


            foreach ($list as $item) {
                if ($item->num > $num){
                    continue;
                }
                $item->state = 4;
                $item->save();
                $num -= $item->num;
            }

//            更新商家配送单商品状态
            $delivery_num = $goods['num'];
            $where = [
                'goods_id'=>$goods['goods_id'],
            ];
            if ($goods['attr_ids']){
                $where['attr_ids'] = $goods['attr_ids'];
            }

            $list = Deliveryordergoods::where('leader_id',$leader_id)
                ->where($where)
                ->where('num > receive_num')
                ->limit($delivery_num)
                ->order('create_time')
                ->select();

            foreach ($list as $item) {
                if (!$delivery_num){
                    continue;
                }
                $n = $item->num - $item->receive_num;
                $min_num = min($n,$delivery_num);

                $item->receive_num += $min_num;
                $item->save();

                $delivery_num -= $min_num;
            }
        }

        success_json();
    }
//    获取用户商品列表(核销)
    public function getUserGoodses(){
        $code = input('request.code');
        $leader_id = input('request.leader_id');

        $usercode = Usercode::get(['code'=>$code,'end_time'=>['>=',time()]]);
        if (!$usercode){
            error_json('核销码过期，请提示用户刷新核销码');
        }

        $list = Ordergoods::where('leader_id',$leader_id)
            ->with('order')
            ->where('user_id',$usercode['user_id'])
            ->where('state',4)
            ->select();

        success_withimg_json($list);
    }
//    确认用户提取商品(核销)
    public function confirmUserGoodses(){
        $leader_id = input('request.leader_id');
        $ids = input('request.ids');
        $list = Ordergoods::where('id',['in',$ids])
            ->where('state',4)
            ->where('leader_id',$leader_id)
            ->select();
        foreach ($list as $item) {
            $item->state = 5;
            $item->save();
        }
        success_json();
    }

//    获取我的团长提现信息、平台提现设置
//    传入
//        user_id: 用户id
    public function getWithdrawInfo(){
        $user_id = input('request.user_id');
        $data = [];

        $leader = Leader::get(['user_id'=>$user_id]);

//        提现设置
        $data['withdraw_min'] = Config::get_value('leader_withdraw_min',0);

        $data['withdraw_type'] = Config::get_value('leader_withdraw_type','1');
        $data['withdraw_type'] = explode(',',$data['withdraw_type']);

        $data['withdraw_wechatrate'] = Config::get_value('leader_withdraw_wechatrate',0);
        $data['withdraw_alipayrate'] = Config::get_value('leader_withdraw_alipayrate',0);
        $data['withdraw_bankrate'] = Config::get_value('leader_withdraw_bankrate',0);
        $data['withdraw_platformrate'] = Config::get_value('leader_withdraw_platformrate',0);
        $data['money'] = $leader['money'];

        success_withimg_json($data);
    }

//    申请提现
    public function addWithdraw(){
        $data = input("request.");

        $leader = Leader::get(['user_id'=>$data['user_id']]);

        if ($leader['money']<$data['money']){
            error_json('您的可提现金额不足，请核对后重新提交');
        }

        $data['leader_id'] = $leader->id;
        $withdraw_model = new Leaderwithdraw();
        $ret = $withdraw_model->allowField(true)->save($data);

        if ($ret){
            success_json($withdraw_model['id']);
        }else{
            error_json('提交失败');
        }
    }
//    获取团长商品列表
//    传入
//        leader_id: 团长id
//        state: 状态、1在售、2可选
    public function getGoodses(){
        $leader_id = input('request.leader_id',0);
        $state = input('request.state',1);
        if ($state == 1){
            $goods_ids = Leadergoods::where('leader_id',$leader_id)->column('goods_id');
            $store_ids = Storeleader::where('leader_id',$leader_id)->column('store_id');

            $list = Goods::where('check_state',2)
                ->where('state',1)
                ->where('end_time','>',time())
                ->whereIn('store_id',$store_ids)
                ->whereIn('id',$goods_ids)
                ->with('store')
                ->page(input('request.page',1))
                ->limit(input('request.limit',10))
                ->select();

            $leader_draw_type = Config::get_value('leader_draw_type',1);
            $leader_draw_rate = Config::get_value('leader_draw_rate',0);
            $leader_draw_money = Config::get_value('leader_draw_money',0);

            foreach ($list as &$item) {
                $rate = 0;
                $money = 0;

                if ($item->leader_draw_type == 1){
                    $rate = $item->leader_draw_rate;
                }elseif ($item->leader_draw_type == 2){
                    $money = $item->leader_draw_money;
                }elseif ($item->store_leader_draw_type == 1){
                    $rate = $item->store_leader_draw_rate;
                }elseif ($item->store_leader_draw_type == 2){
                    $money = $item->store_leader_draw_money;
                }elseif ($leader_draw_type == 1){
                    $rate = $leader_draw_rate;
                }elseif ($leader_draw_type == 2){
                    $money = $leader_draw_money;
                }

                $item->leader_money = sprintf("%.2f",$item->price*$rate/100 + $money);
            }
        }else{
            $goods_ids = Leadergoods::where('leader_id',$leader_id)->column('goods_id');
            $store_ids = Storeleader::where('leader_id',$leader_id)->column('store_id');

            $list = Goods::where('check_state',2)
                ->where('state',1)
                ->where('end_time','>',time())
                ->whereIn('store_id',$store_ids)
                ->whereNotIn('id',$goods_ids)
                ->with('store')
                ->page(input('request.page',1))
                ->limit(input('request.limit',10))
                ->select();

            $leader_draw_type = Config::get_value('leader_draw_type',1);
            $leader_draw_rate = Config::get_value('leader_draw_rate',0);
            $leader_draw_money = Config::get_value('leader_draw_money',0);

            foreach ($list as &$item) {
                $rate = 0;
                $money = 0;

                if ($item->leader_draw_type == 1){
                    $rate = $item->leader_draw_rate;
                }elseif ($item->leader_draw_type == 2){
                    $money = $item->leader_draw_money;
                }elseif ($item->store_leader_draw_type == 1){
                    $rate = $item->store_leader_draw_rate;
                }elseif ($item->store_leader_draw_type == 2){
                    $money = $item->store_leader_draw_money;
                }elseif ($leader_draw_type == 1){
                    $rate = $leader_draw_rate;
                }elseif ($leader_draw_type == 2){
                    $money = $leader_draw_money;
                }

                $item->leader_money = sprintf("%.2f",$item->price*$rate/100 + $money);
            }
        }
        success_withimg_json($list);
    }

    public function addGoods(){
        $leader_id = input('request.leader_id',0);
        $goods_ids = input('request.goods_ids','');

        $leader_goods_ids = Leadergoods::where('leader_id',$leader_id)->column('goods_id');
        $goodses = Goods::where('id','in',$goods_ids)
            ->whereNotIn('id',$leader_goods_ids)
            ->field('id,store_id')
            ->select();

        $num = 0;
        foreach ($goodses as $goods) {
            try{
                $ret = Leadergoods::create([
                    'leader_id'=>$leader_id,
                    'goods_id'=>$goods->id,
                    'store_id'=>$goods->store_id
                ]);
                if ($ret){
                    $num++;
                }
            }catch (Exception $e){}
        }
        if ($num){
            success_json('成功添加 '.$num.' 个');
        }
        error_json('添加失败');
    }
    public function deleteGoods(){
        $leader_id = input('request.leader_id',0);
        $goods_ids = input('request.goods_ids','');

        $ret = Leadergoods::where('leader_id',$leader_id)
            ->whereIn('goods_id',$goods_ids)
            ->delete();

        if ($ret){
            success_json('移除成功');
        }
        error_json('移除失败');
    }
//    获取团长核销员列表
//    传入
//        leader_id: 团长id
    public function getMyUsers(){
        $leader_id = input('request.leader_id',0);

        $list = Leaderuser::where('leader_id',$leader_id)
            ->with('user')
            ->page(input('request.page',1))
            ->limit(input('request.limit',10))
            ->select();

        success_withimg_json($list);
    }
    public function getUsers(){
        $key= input('request.key','');

        $leader_user_ids = Leader::where('')->column('user_id');
        $store_user_ids = Store::where('')->column('user_id');
        $leaderuser_user_ids = Leaderuser::where('')->column('user_id');

        $list = User::where('id|name|tel',"$key")
            ->whereNotIn('id',$leader_user_ids)
//            ->whereNotIn('id',$store_user_ids)
            ->whereNotIn('id',$leaderuser_user_ids)
            ->limit(10)
            ->select();

        success_json($list);
    }
    public function addUser(){
        $leader_id = input('request.leader_id',0);
        $user_id = input('request.user_id',0);

        $ret = Leaderuser::create([
            'leader_id'=>$leader_id,
            'user_id'=>$user_id,
        ]);

        if ($ret){
            success_json('成功添加');
        }
        error_json('添加失败');
    }
    public function deleteUser(){
        $leaderuser_id = input('request.leaderuser_id',0);

        $ret = Leaderuser::destroy($leaderuser_id);

        if ($ret){
            success_json('移除成功');
        }
        error_json('移除失败');
    }
}