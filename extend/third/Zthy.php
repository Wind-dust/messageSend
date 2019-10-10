<?php

namespace third;

use Config;

date_default_timezone_set('PRC');//设置时区
class Zthy {
    public $data;    //发送数据
    public $timeout = 30; //超时
    private $apiUrl;    //发送地址
    private $verifi;
    private $market;
    private $statusArr = [1, 2];
    private $user = [];
    private $urlList = [
        '1' => 'http://api.zthysms.com/sendSms.do',//单条验证码短信
        '2' => 'http://api.zthysms.com/sendSmsBatch.do',//营销群发短信
    ];

    function __construct($apiIndex) {
        $this->apiUrl  = $this->urlList[$apiIndex];
        $this->user[1] = ['username' => Config::get('sms.usernameVerifi'), 'password' => Config::get('sms.passwordVerifi')];
        $this->user[2] = ['username' => Config::get('sms.usernameMarket'), 'password' => Config::get('sms.passwordMarket')];
    }

    private function httpGet() {
        $url  = $this->apiUrl . '?' . http_build_query($this->data);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        $res = curl_exec($curl);
        if (curl_errno($curl)) {
            echo 'Error GET ' . curl_error($curl);
        }
        curl_close($curl);
        return $res;
    }

    private function httpPost() { // 模拟提交数据函数
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $this->apiUrl); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_POST, true); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($this->data)); // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, false); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // 获取的信息以文件流的形式返回
        $result = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
            echo 'Error POST' . curl_error($curl);
        }
        curl_close($curl); // 关键CURL会话
        return $result; // 返回数据
    }

    /**
     * @param $status 1.验证码 2.营销
     * @param $type |提交类型 POST/GET
     * @param $isTranscoding |是否需要转 $isTranscoding 是否需要转utf-8 默认 false
     * @return mixed
     */
    public function sendSMS(int $status, $type = 'POST', $isTranscoding = false) {
        if (!in_array($status, $this->statusArr)) {
            return ['code' => '3000'];//短信类型有误
        }
        $this->data['content']  = $isTranscoding === true ? mb_convert_encoding($this->data['content'], "UTF-8") : $this->data['content'];
        $this->data['username'] = $this->user[$status]['username'];
        $this->data['tkey']     = date('YmdHis');
        $this->data['password'] = md5(md5($this->user[$status]['password']) . $this->data['tkey']);
        $result                 = $type == "POST" ? $this->httpPost() : $this->httpGet();
        $res                    = explode(',', $result);
        if ($res[0] == 1) {
            return ['code' => 200, 'data' => $res[1]];
        }
        return ['code' => '3001', 'data' => $res[1]];

    }
}