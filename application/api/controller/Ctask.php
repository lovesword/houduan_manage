<?php
namespace app\api\controller;

use app\base\controller\Api;
use app\model\Config;
use app\model\Distribution;
use app\model\Distributionrecord;
use app\model\Formid;
use app\model\Order;
use app\model\Orderdistribution;
use app\model\Ordergoods;
use app\model\Pinorder;
use app\model\Seckillgoods;
use app\model\Seckillorder;
use app\model\Task;
use app\model\Template;
use app\model\User;
use dingtalk\DingTalk;
use dingtalk\MsgText;
use think\Db;

class Ctask extends Api
{
//    递归异步调用
    public function run1(){
        global $_W;
        ignore_user_abort(true);//忽略客户端关闭
        set_time_limit(0);

        $config = Config::get(['key'=>'autotask','uniacid'=>$_W['uniacid']]);
        if (!$config->value){
            exit();
        }
        $config->value = time();
        $config->save();

        $task_model = new Task();
        $task_list = $task_model
            ->where('state',0)//待执行
            ->where('execute_time',['<=',time()])
            ->where('execute_times',['<=',5])
            ->order([
                'level'=>'desc',//等级越高，越早执行
                'execute_times'=>'asc',//尝试执行次数越小，越早执行
//                'create_time'=>'asc',//创建时间越小，越早执行
                'execute_time'=>'asc',//执行时间越小，越早执行
            ])
            ->limit(10)//每次查询数量 todo 以后调高点
            ->select();

        if (count($task_list)){
            foreach ($task_list as $task) {

                $config->value = time();
                $config->save();

                $ret = false;
                try{
                    switch ($task->type){
                        case "dingtalk":
                            $ret = $this->sendDingtalk($task->value);
                            break;
                        case "template":
                            $ret=$this->setTem($task->value);
                            break;
                        case "seckillorder":
                            $ret=$this->cancelSeckillorder($task->value);
                            break;
                        case "pinpay";
                            $pin=new Pinorder();
                            $ret=$pin->payOverdue($task->value);
                            break;
                        case "pinopen";
                            $pin=new Pinorder();
                            $ret=$pin->openOverdue($task->value);
                            break;
                        case "sendOrderPayTemplate":
                            $ret=$this->sendOrderPayTemplate($task->value);
                            break;
                        case "sendOrdergoodsReceiveTemplate":
                            $ret=$this->sendOrdergoodsReceiveTemplate($task->value);
                            break;
                        case "distribution_convert_money":
                            $ret=$this->distributionConvertMoney($task->value);
                            break;
                    }
                }catch (\Exception $e){
                    $ret = false;
                }
                $task->execute_times = $task->execute_times + 1;
                $task->execute_time = time()+($task->execute_times*$task->execute_times*10);
                $task->save();
                if ($ret === true){
                    $task->state = 1;
                    $task->save();
                }
            }
        }else{
            sleep(1);
        }
        runTask();
    }
//    死循环（废弃）
    public function run(){
        global $_W;
        ignore_user_abort(true);//忽略客户端关闭
        set_time_limit(0);
        while (true){
            $config = Config::get(['key'=>'autotask','uniacid'=>$_W['uniacid']]);
            if (!$config->value){
                exit();
            }

//            标识程序执行
//            $task1 = Task::get(6);
//            $task1->update_time = time();
//            $task1->save();

            $task_model = new Task();
            $task_list = $task_model
                ->where('state',0)//待执行
                ->order([
                    'level'=>'desc',//等级越高，越早执行
                    'create_time'=>'asc',//创建时间越小，越早执行
                ])
                ->limit(10)//每次查询数量 todo 以后调高点
                ->select();

            if (count($task_list)){
                foreach ($task_list as $task) {
                    $ret = false;
                    switch ($task->type){
                        case "dingtalk":
                            $ret = $this->sendDingtalk($task->value);
                            break;
                        case "template":
                            $ret=$this->setTem($task->value);
                                break;
                        case "convertDistribution":
                            $ret=$this->convertDistribution($task->value);
                            break;
                        case "pinpay";
                            $pin=new Pinorder();
                            $pin->payOverdue($task->value);
                            break;
                    }
                    if ($ret === true){
                        $task->state = 1;
                        $task->save();
                    }
                }
            }else{
                sleep(1);
            }
        }
    }
    public function sendDingTalkTest(){
        $content=input('request.content');
        $token='09dd52b120d00ef52c979b099545b5fd87e1c316fa3ca6c6f9addf88f07d10dd';
        $msg = new MsgText($content);
        $ding = new DingTalk();
        $ret = $ding->send($token,$msg);
        echo $ret;
    }

//    发送钉钉通知
    public function sendDingtalk($value){
        $token = '0e16768a09c0e92fb102c426346dd9fdfb17b3409843e65fb1c707efbe0075e1';
        $token = '87970ac38f8cdaa20978dd1e8a4f585e902d15930609edafed7b0b4a0df496f0';
        $token = 'd79dd9051926f372f56dad77a5f4643f85c9df7460bfb9042b4f0734cdf79fdf';
        $token='09dd52b120d00ef52c979b099545b5fd87e1c316fa3ca6c6f9addf88f07d10dd';
        $msg = new MsgText($value);
        $ding = new DingTalk();
        $ret = $ding->send($token,$msg);
        $ret = json_decode($ret);
        if ($ret->errcode){
            return false;
        }
        return true;
    }
    //发送模板消息
    public function setTem($value){
        global $_W;
        $id=intval($value);
        $content=Db::name('templatecontent')->find($id);
        $res=sendTemplate($content['touser'],$content['template_id'],$content['page'],$content['form_id'],json_decode($content['data'],1));
        Db::name('templatecontent')->update(array('id'=>$id,'result'=>json_encode($res)));
        return true;
    }
    public function convertDistribution($value){
        return Orderdistribution::convert($value);
    }

    public function cancelSeckillorder($value){
        return Seckillorder::cancel($value);
    }

    public function taskTest(){
        $task_id = input('id');
        $task = Task::get($task_id);
//        $this->cancelSeckillorder($task['value']);

        $ret = false;
        try{
            switch ($task->type){
                case "dingtalk":
                    $ret = $this->sendDingtalk($task->value);
                    break;
                case "template":
                    $ret=$this->setTem($task->value);
                    break;
                case "seckillorder":
                    $ret=$this->cancelSeckillorder($task->value);
                    break;
                case "pinpay";
                    $pin=new Pinorder();
                    $ret=$pin->payOverdue($task->value);
                    break;
                case "pinopen";
                    $pin=new Pinorder();
                    $ret=$pin->openOverdue($task->value);
                    break;
                case "sendOrderPayTemplate":
                    $ret=$this->sendOrderPayTemplate($task->value);
                    break;
                case "sendOrdergoodsReceiveTemplate":
                    $ret=$this->sendOrdergoodsReceiveTemplate($task->value);
                    break;
            }
        }catch (\Exception $e){
            $ret = false;
        }
        $task->execute_times = $task->execute_times + 1;
        $task->execute_time = time()+($task->execute_times*$task->execute_times*10);
        $task->save();
        if ($ret === true){
            $task->state = 1;
            $task->save();
            echo 'SUCCESS';
     }else{
            echo 'ERROR';
        }

    }

    public function sendOrderPayTemplate($value){
        $order = Order::get($value,'user');
        $tem = Template::get([]);
        $formid = Formid::get(['user_id'=>$order['user_id'],'time'=>['>',time()]]);
        Formid::destroy($formid->id);

        $goodses = Ordergoods::where('order_id',$value)
            ->select();
        $names = [];
        $num = 0;
        foreach ($goodses as $goods) {
            $names[] = $goods['goods_name'];
            $num += $goods['num'];
        }

        $data = [
            'keyword1'=>[
                'value'=>$order['order_no'],
                'color'=>'173177',
            ],
            'keyword2'=>[
                'value'=>implode('|',$names),
                'color'=>'173177',
            ],
            'keyword3'=>[
                'value'=>$num,
                'color'=>'173177',
            ],
            'keyword4'=>[
                'value'=>date("Y-m-d H:i:s",$order['pay_time']),
                'color'=>'173177',
            ],
            'keyword5'=>[
                'value'=>$order['pay_amount'],
                'color'=>'173177',
            ],
        ];
        $res=sendTemplate($order['openid'],$tem['tid1'],'',$formid['form_id'],$data);
        $res = json_decode($res,true);
        if ($res['errcode']){
            return false;
        }
        return true;
    }
    public function sendOrdergoodsReceiveTemplate($value){
        $ordergoods = Ordergoods::get($value,['order','leader']);

        $tem = Template::get([]);
        $formid = Formid::get(['user_id'=>$ordergoods['user_id'],'time'=>['>',time()]]);
        Formid::destroy($formid->id);

        $user = User::get($ordergoods->user_id);

        $data = [
            'keyword1'=>[
                'value'=>$ordergoods['order_no'],
                'color'=>'173177',
            ],
            'keyword2'=>[
                'value'=>$ordergoods->goods_name,
                'color'=>'173177',
            ],
            'keyword3'=>[
                'value'=>$ordergoods->num,
                'color'=>'173177',
            ],
            'keyword4'=>[
                'value'=>$ordergoods->leader_name,
                'color'=>'173177',
            ],
            'keyword5'=>[
                'value'=>$ordergoods->leader_tel,
                'color'=>'173177',
            ],
            'keyword6'=>[
                'value'=>$ordergoods->leader_address,
                'color'=>'173177',
            ],
            'keyword7'=>[
                'value'=>$ordergoods->update_time,
                'color'=>'173177',
            ],
            'keyword8'=>[
                'value'=>'提货请带上手机',
                'color'=>'173177',
            ],
        ];

        $res=sendTemplate($user['openid'],$tem['tid2'],'',$formid['form_id'],$data);
        $res = json_decode($res,true);
        if ($res['errcode']){
            return false;
        }
        return true;
    }
    public function distributionConvertMoney($value){
        return Distributionrecord::convert($value);
    }
}
