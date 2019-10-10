<?php

namespace app\console\com;

use app\console\Pzlife;
use Config;
use Env;
use function Qiniu\json_decode;
use think\Db;
use cache\Phpredis;

class LocalScript extends Pzlife {
    private $redis;

    //    private $connect;
    
        private function orderInit() {
            $this->redis = Phpredis::getConn();
    //        $this->connect = Db::connect(Config::get('database.db_config'));
        }
/**
     * 获取微信素材接口
     * @return array
     * @author rzc
     */
    public function WxBatchgetMaterial() {

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
                $page ++;
            }
            
        } while (!$requsest_subject);
       
       foreach ($news as $n => $new) {
            $this->redis->Zadd($redisBatchgetMaterial,$n,json_encode($new));
       }
        // print_r($WxBatchgetMaterial);die;
        $redis_news = $this->redis->ZRANGE($redisBatchgetMaterial,0,10);
        print_r($redis_news);die;
    }

    function sendRequestWx($requestUrl, $data = []) {
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
    protected function getWeiXinAccessTokenTencent() {
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
            
            $this->redis->set($redisAccessTokenTencent,$access_token);
            $this->redis->expire($redisAccessTokenTencent, 6600);
        }
        
        return $access_token;
    }

}
