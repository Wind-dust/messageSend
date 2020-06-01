<?php

namespace app\console\com;

use app\console\Pzlife;
use Config;
use Env;
use function Qiniu\json_decode;
use think\Db;
use cache\Phpredis;

class LocalScript extends Pzlife
{
    private $redis;

    //    private $connect;

    private function orderInit()
    {
        $this->redis = Phpredis::getConn();
        //        $this->connect = Db::connect(Config::get('database.db_config'));
    }
    /**
     * 获取微信素材接口
     * @return array
     * @author rzc
     */
    public function WxBatchgetMaterial()
    {

        //获取微信公众号access_token
        $access_token = $this->getWeiXinAccessTokenTencent();
        if ($access_token === false) {
            return ['code' => '4001'];
        }
        //接口POST请求方法
        $news        = [];
        $requestUrl  = 'https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token=' . $access_token;
        $type        = "news";
        $requestData = [];
        $redisBatchgetMaterial = Config::get('redisKey.weixin.redisBatchgetMaterial');
        $count = 20;
        $page = 1;
        $offset = ($page - 1) * $count;
        do {
            $requestData = [
                'type'   => $type,
                'offset' => $offset,
                'count'  => $count,
            ];
            $requsest_subject = json_decode($this->sendRequestWx($requestUrl, $requestData), true);
            if (!isset($requsest_subject['item'])) {
                $requsest_subject = false;
            }

            $WxBatchgetMaterial = $requsest_subject['item'];
            if (!empty($WxBatchgetMaterial)) {
                foreach ($WxBatchgetMaterial as $wx => $BatchgetMaterial) {
                    $news_item = $BatchgetMaterial['content']['news_item'];
                    foreach ($news_item as $key => $value) {
                        // unset($WxBatchgetMaterial[$wx]['content']['news_item'][$key]['content']);
                        unset($value['content']);
                        $value['create_time'] = date("Y-m-d H:i:s", $BatchgetMaterial['content']['create_time']);
                        $value['update_time'] = date("Y-m-d H:i:s", $BatchgetMaterial['content']['update_time']);
                        $news[]               = $value;
                    }
                }
                $page++;
            }
        } while (!$requsest_subject);

        foreach ($news as $n => $new) {
            $this->redis->Zadd($redisBatchgetMaterial, $n, json_encode($new));
        }
        // print_r($WxBatchgetMaterial);die;
        $redis_news = $this->redis->ZRANGE($redisBatchgetMaterial, 0, 10);
        print_r($redis_news);
        die;
    }

    function sendRequestWx($requestUrl, $data = [])
    {
        $curl = curl_init();
        $data = json_encode($data);
        curl_setopt($curl, CURLOPT_URL, $requestUrl);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8', 'Content-Length:' . strlen($data)]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    }

    /**
     * 获取微信公众号access_token
     * @return array
     * @author rzc
     */
    protected function getWeiXinAccessTokenTencent()
    {
        $this->orderInit();
        $redisAccessTokenTencent = Config::get('redisKey.weixin.redisAccessTokenTencent');
        $access_token = $this->redis->get($redisAccessTokenTencent);
        if (empty($access_token)) {
            // $appid = Env::get('weixin.weixin_appid');
            $appid         = 'wx112088ff7b4ab5f3';
            // $secret = Env::get('weixin.weixin_secret');
            $secret        = 'db7915c4a840421683be99c6d798757f';
            $requestUrl = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appid . '&secret=' . $secret;
            $requsest_subject = json_decode(sendRequest($requestUrl), true);
            if (!isset($requsest_subject['access_token'])) {
                return false;
            }
            $access_token     = $requsest_subject['access_token'];

            $this->redis->set($redisAccessTokenTencent, $access_token);
            $this->redis->expire($redisAccessTokenTencent, 6600);
        }

        return $access_token;
    }

    public function numberDetection()
    {
        $secret_id = '06FDC4A71F5E1FDE4C061DBA653DD2A5';
        $secret_key = 'ef0587df-86dc-459f-ad82-41c6446b27a5';
        $api = 'https://api.yunzhandata.com/api/deadnumber/v1.0/detect?sig=';
        $ts =date("YmdHis",time());
        $sig = sha1($secret_id . $secret_key . $ts);
        // echo $sig;
        $mobile = '15201926171';
        // return $this->encrypt($mobile, $secret_id);
        $en_mobile = $this->encrypt($mobile, $secret_id);
        // echo $en_mobile;
        $api = $api.$sig."&sid=" .$secret_id."&skey=" .$secret_key."&ts=".$ts;

        $data = [];
        $data = [
            // 'sig' => $sig,
            // 'sid' => $secret_id,
            // 'skey' => $secret_key,
            // 'ts' => $ts,
            'mobiles' => [
                $en_mobile
            ]
        ];
        $headers = [
            'Authorization:'.base64_encode($secret_id.':'.$ts),'Content-Type:application/json'
        ];
        // echo base64_decode('MDZGREM0QTcxRjVFMUZERTRDMDYxREJBNjUzREQyQTU6MTU5MTAwNzE5Ng==');
        print_r($api);
        echo "\n";
        print_r($headers);
        echo "\n";
        print_r($data);
        $data = $this->sendRequest2($api,'post',$data,$headers);
        // print_r(json_decode($data),true);
        print_r($data);
    }

    function sendRequest2($requestUrl, $method = 'get', $data = [],$headers)
    {
        $methonArr = ['get', 'post'];
        if (!in_array(strtolower($method), $methonArr)) {
            return [];
        }
        if ($method == 'post') {
            if (!is_array($data) || empty($data)) {
                return [];
            }
        }
        $curl = curl_init(); // 初始化一个 cURL 对象
        curl_setopt($curl, CURLOPT_URL, $requestUrl); // 设置你需要抓取的URL
        curl_setopt($curl, CURLOPT_HEADER, 0); // 设置header 响应头是否输出
        if ($method == 'post') {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 60);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_USERAGENT, 'Chrome/53.0.2785.104 Safari/537.36 Core/1.53.2372.400 QQBrowser/9.5.10548.400'); // 模拟用户使用的浏览器
        }
        // 设置cURL 参数，要求结果保存到字符串中还是输出到屏幕上。
        // 1如果成功只将结果返回，不自动输出任何内容。如果失败返回FALSE
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        $res = curl_exec($curl); // 运行cURL，请求网页
        curl_close($curl); // 关闭URL请求
        return $res; // 显示获得的数据
    }

   /**
    *
    * @param string $string 需要加密的字符串
    * @param string $key 密钥
    * @return string
    */
    public static function encrypt($string, $key)
    {
        // 对接java，服务商做的AES加密通过SHA1PRNG算法（只要password一样，每次生成的数组都是一样的），Java的加密源码翻译php如下：
        $key = substr(openssl_digest(openssl_digest($key, 'sha1', true), 'sha1', true), 0, 16);

        // openssl_encrypt 加密不同Mcrypt，对秘钥长度要求，超出16加密结果不变
        $data = openssl_encrypt($string, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);

        $data = strtoupper(bin2hex($data));
        // print_r($data);
        return $data;
    }
}
