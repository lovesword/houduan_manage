<?php
namespace app\admin\controller;

use app\model\System;
use dingtalk\DingTalk;
use dingtalk\MsgText;
use app\base\controller\Admin;
class Ctemplate extends Admin
{
    public function setting(){
        global $_W,$_GPC;
        $this->view->_W = $_W;
        $this->view->_GPC = $_GPC;
        $info = $this->model->get_curr();
        $this->view->info = $info;
        return view('setting');
    }

    public function save(){
        $info = $this->model;

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

    public function addTemplate(){
        $type = input('post.type',1);
        switch ($type){
//                支付通知
            case 1:
                $template_id = 'AT0048';
                $keyword_id_list = [1,2,5,3,4];
                break;
//                商品到货通知
            case 2:
                $template_id = 'AT0530';
                $keyword_id_list = [1,15,12,18,8,5,4,9];
                break;
//                发货通知
            case 3:
                $template_id = 'AT0007';
                $keyword_id_list = [20,21,22,19,26];
                break;
//                退款通知
            case 4:
                $template_id = 'AT2270';
                $keyword_id_list = [1,3];
                break;
        }
        $ret = $this->addMyTemplate($template_id,$keyword_id_list);
        if (!$ret['errcode']){
            return array(
                'code'=>0,
                'data'=>$ret['template_id'],
            );
        }else{
            return array(
                'code'=>1,
                'msg'=>$ret['errcode'].':=>'.$ret['errmsg'],
            );
        }
    }
    public function test(){
        $token = '0e16768a09c0e92fb102c426346dd9fdfb17b3409843e65fb1c707efbe0075e1';
        $msg = new MsgText('文本信息测试');
        $ding = new DingTalk();
        $ret = $ding->send($token,$msg);
        var_dump($ret);
    }
//---------------对接微信模板接口
//    模板接口测试
    public function templateTest(){
echo time();exit;
//        对账单测试
        function getSign($Obj) {
            foreach ($Obj as $k => $v) {
                $Parameters[$k] = $v;
            }
            //签名步骤一：按字典序排序参数
            ksort($Parameters);
            $String = formatBizQueryParaMap($Parameters, false);
            //签名步骤二：在string后加入KEY
            $String = $String . "&key=95e7bd8a5fd7fd609b6d36d68c06146f";
            //签名步骤三：MD5加密
            $String = md5($String);
            //签名步骤四：所有字符转为大写
            $result_ = strtoupper($String);
            return $result_;
        }
        ///作用：格式化参数，签名过程需要使用
        function formatBizQueryParaMap($paraMap, $urlencode) {
            $buff = "";
            ksort($paraMap);
            foreach ($paraMap as $k => $v) {
                if ($urlencode) {
                    $v = urlencode($v);
                }
                $buff .= $k . "=" . $v . "&";
            }
            $reqPar = '';
            if (strlen($buff) > 0) {
                $reqPar = substr($buff, 0, strlen($buff) - 1);
            }
            return $reqPar;
        }
        function xmlToArray($xml) {


            //禁止引用外部xml实体


            libxml_disable_entity_loader(true);


            $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);


            $val = json_decode(json_encode($xmlstring), true);


            return $val;
        }
        function arrayToXml($arr) {
            $xml = "<root>";
            foreach ($arr as $key => $val) {
                if (is_array($val)) {
                    $xml .= "<" . $key . ">" . arrayToXml($val) . "</" . $key . ">";
                } else {
                    $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
                }
            }
            $xml .= "</root>";
            return $xml;
        }
        function postXmlCurl($xml, $url, $second = 30)
        {
            $ch = curl_init();
            //设置超时
            curl_setopt($ch, CURLOPT_TIMEOUT, $second);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); //严格校验
            //设置header
            curl_setopt($ch, CURLOPT_HEADER, FALSE);
            //要求结果为字符串且输出到屏幕上
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            //post提交方式
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
            curl_setopt($ch, CURLOPT_TIMEOUT, 40);
            set_time_limit(0);


            //运行curl
            $data = curl_exec($ch);
            //返回结果
            if ($data) {
                curl_close($ch);
                return $data;
            } else {
                $error = curl_errno($ch);
                curl_close($ch);
                throw new WxPayException("curl出错，错误码:$error");
            }
        }
        function createNoncestr($length = 32) {
            $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
            $str = "";
            for ($i = 0; $i < $length; $i++) {
                $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
            }
            return $str;
        }

        $url = "https://api.mch.weixin.qq.com/pay/downloadbill";
        $data_post = [
            'appid'=>'wx9b28dec192dea8d4',
            'mch_id'=>'1510760691',
            'nonce_str'=>createNoncestr(),
            'bill_date'=>'20181212',
            'bill_type'=>'ALL',
        ];
        $data_post['sign'] = getSign($data_post);

        $xmlData = arrayToXml($data_post);
        $aa = xmlToArray(postXmlCurl($xmlData, $url, 60));
        echo json_encode($aa);exit;




        $data = [];
//        $data['getWXTemplates'] = $this->getWXTemplates();
        $data['getWXTemplate'] = $this->getWXTemplate('AT0530');//$data['getWXTemplates']['list'][0]['id']);
//        $data['getMyTemplates'] = $this->getMyTemplates();
//        $data['addMyTemplate'] = $this->addMyTemplate($data['getWXTemplates']['list'][0]['id']);
//        $data['delMyTemplate'] = $this->delMyTemplate($data['addMyTemplate']['template_id']);
        echo json_encode($data);exit;
        echo '<pre>';
        print_r($data);
        echo '</pre>';

    }

//    获取微信模板库
    public function getWXTemplates(){
        $token = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/wxopen/template/library/list?access_token=".$token;
        $data_post['offset'] = 60;
        $data_post['count'] = 20;
        return $this->post($url,$data_post);
    }
//    获取微信模板详情
    public function getWXTemplate($id){
        $token = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/wxopen/template/library/get?access_token=".$token;
        $data_post['id'] = $id;
        return $this->post($url,$data_post);
    }
//    添加我的模板
    public function addMyTemplate($id,$datas = [3,4,5]){
        $token = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/wxopen/template/add?access_token=".$token;
        $data_post['id'] = $id;
        $data_post['keyword_id_list'] = $datas;
        return $this->post($url,$data_post);
    }
//    获取我的所有模板
    public function getMyTemplates(){
        $token = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/wxopen/template/list?access_token=".$token;
        $data_post['offset'] = 0;
        $data_post['count'] = 2;
        return $this->post($url,$data_post);
    }
//    删除我的模板
    public function delMyTemplate($id){
        $token = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/wxopen/template/del?access_token=".$token;
        $data_post['id'] = $id;
        return $this->post($url,$data_post);
    }
//    获取 token
    public function getAccessToken()
    {
        $system = System::get_curr();
        if (!$system['appid']){
            throw new \ZhyException('appid 为空');
        }
        if (!$system['appsecret']){
            throw new \ZhyException('appsecret 为空');
        }

        $token_data = $_SESSION['access_token'];
        if ($token_data && $token_data['time']>=time()){
//            return $token_data['value'];
        }

        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $system['appid'] . "&secret=" . $system['appsecret'] . "";
        $data = $this->post($url);
        $token = $data['access_token'];
        if (!$token){
            throw new \ZhyException($data['errcode'].':=>'.$data['errmsg']);
        }
        $_SESSION['access_token'] = ['value'=>$token,'time'=>time()*7000];
        return $token;
    }
//    post
    public function post($url,$data=[]){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        //设置post方式提交
        curl_setopt($ch, CURLOPT_POST, 1);
        //设置post数据
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(json_decode(json_encode($data),true)));
        $data = curl_exec($ch);
        if(is_string($data)){
            $data = json_decode($data,true);
        }
        curl_close($ch);
        if($data['errcode']){
            throw new \ZhyException($data['errcode'].':=>'.$data['errmsg']);
        }
        return $data;
    }
}
