<?php

namespace app\console\com;

use app\console\Pzlife;
use cache\Phpredis;
use cache\PhpredisNew;
use Config;
use Env;
use Kafka\Producer;
use Kafka\Produce;
use Kafka\ProducerConfig;
use Monolog\Logger;
use Monolog\Handler\StdoutHandler;
use think\Db;

class LocalScript extends Pzlife
{
    private $redis;

    //    private $connect;

    private function orderInit()
    {
        $this->redis = Phpredis::getConn();
        //        $this->connect = Db::connect(Config::get('database.db_config'));
    }

    public function kafkaTest()
    {
        // $produce = Producer::getInstance('localhost:2181', 3000);
        try {
            
            date_default_timezone_set('PRC');
            // Create the logger
            // $logger = new Logger('my_logger');
            $config = \Kafka\ProducerConfig::getInstance();
            $config->setMetadataRefreshIntervalMs(10000);
            $config->setMetadataBrokerList('139.224.119.119:9000');
            $config->setBrokerVersion('0.9.0.1');
            $config->setRequiredAck(1);
            $config->setIsAsyn(false);
            $config->setProduceInterval(500);
            $producer = new \Kafka\Producer();
            // $producer->setLogger($logger);

            for($i = 0; $i < 100; $i++) {
                    $result = $producer->send(array(
                            array(
                                    'topic' => 'test1',
                                    'value' => 'test1....message.',
                                    'key' => '',
                            ),
                    ));
                    var_dump($result);
            }
            // $logger = new Logger('my_logger');
            // // Now add some handlers
            // $logger->pushHandler(new StdoutHandler());
            
            /* $config = \Kafka\ConsumerConfig::getInstance();
            $config->setMetadataRefreshIntervalMs(10000);
            $config->setMetadataBrokerList('139.224.119.119:9000');
            $config->setGroupId('test');
            $config->setBrokerVersion('0.9.0.1');
            $config->setTopics(array('test'));
            //$config->setOffsetReset('earliest');
            $consumer = new \Kafka\Consumer();
            // $consumer->setLogger($logger);
            $consumer->start(function($topic, $part, $message) {
                var_dump($message);
            }); */
        } catch (\Exception $th) {
            //throw $th;
            exception($th);
        }
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
        $news                  = [];
        $requestUrl            = 'https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token=' . $access_token;
        $type                  = "news";
        $requestData           = [];
        $redisBatchgetMaterial = Config::get('redisKey.weixin.redisBatchgetMaterial');
        $count                 = 20;
        $page                  = 1;
        $offset                = ($page - 1) * $count;
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
        // // print_r($WxBatchgetMaterial);die;
        $redis_news = $this->redis->ZRANGE($redisBatchgetMaterial, 0, 10);
        // print_r($redis_news);
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
        $access_token            = $this->redis->get($redisAccessTokenTencent);
        if (empty($access_token)) {
            // $appid = Env::get('weixin.weixin_appid');
            $appid = 'wx112088ff7b4ab5f3';
            // $secret = Env::get('weixin.weixin_secret');
            $secret           = 'db7915c4a840421683be99c6d798757f';
            $requestUrl       = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appid . '&secret=' . $secret;
            $requsest_subject = json_decode(sendRequest($requestUrl), true);
            if (!isset($requsest_subject['access_token'])) {
                return false;
            }
            $access_token = $requsest_subject['access_token'];

            $this->redis->set($redisAccessTokenTencent, $access_token);
            $this->redis->expire($redisAccessTokenTencent, 6600);
        }

        return $access_token;
    }

    public function numberDetection()
    {
        $secret_id  = '06FDC4A71F5E1FDE4C061DBA653DD2A5';
        $secret_key = 'ef0587df-86dc-459f-ad82-41c6446b27a5';
        $api        = 'https://api.yunzhandata.com/api/deadnumber/v1.0/detect?sig=';
        $ts         = date("YmdHis", time());
        $sig        = sha1($secret_id . $secret_key . $ts);
        // // echo $sig;
        $mobile = '15201926171';
        // return $this->encrypt($mobile, $secret_id);
        $en_mobile = $this->encrypt($mobile, $secret_id);
        // // echo $en_mobile;
        $api = $api . $sig . "&sid=" . $secret_id . "&skey=" . $secret_key . "&ts=" . $ts;

        $data = [];
        $data = [
            // 'sig' => $sig,
            // 'sid' => $secret_id,
            // 'skey' => $secret_key,
            // 'ts' => $ts,
            'mobiles' => [
                $en_mobile,
            ],
        ];
        $headers = [
            'Authorization:' . base64_encode($secret_id . ':' . $ts), 'Content-Type:application/json',
        ];
        // // echo base64_decode('MDZGREM0QTcxRjVFMUZERTRDMDYxREJBNjUzREQyQTU6MTU5MTAwNzE5Ng==');
        // print_r($api);
        // echo "\n";
        // print_r($headers);
        // echo "\n";
        // print_r($data);
        $data = $this->sendRequest2($api, 'post', $data, $headers);
        // // print_r(json_decode($data),true);
        // print_r($data);
    }

    function sendRequest2($requestUrl, $method = 'get', $data = [], $headers)
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
        // // print_r($data);
        return $data;
    }

    public function hadMobile()
    {
        ini_set('memory_limit', '4096M'); // 临时设置最大内存占用为3G
        $max_id = Db::query("SELECT `id` FROM yx_send_task_receipt ORDER BY `id` DESC limit 1 ");
        // // print_r($max_id);

        $mobile_data = [];
        $ALL_NUM     = Db::query("SELECT `mobile`,`real_message` FROM yx_send_task_receipt WHERE (`real_message` LIKE '%MK%' OR `real_message` LIKE '%MI%' OR `real_message` LIKE '%MN%' OR `real_message` LIKE '%MO%'  OR `real_message` LIKE '%UNDELI%') GROUP BY `mobile`,`real_message` ");
        /*  $max_num = $max_id[0]['id'];
        for ($i=0; $i < $max_num; $i++) {
        $receipts = Db::query('SELECT ');
        } */
        $i = 1;
        foreach ($ALL_NUM as $key => $value) {
            // // print_r($value['mobile']);die;
            // $mobile = [];
            // $mobile = [
            //     'mobile' => $value['mobile'],
            //     'update_time' => time(),
            //     'create_time' => time(),
            // ];
            $mobile_data[] = $value['mobile'];
        }
        $ALL_NUM = Db::query("SELECT `mobile`,`real_message` FROM yx_send_code_task_receipt WHERE (`real_message` LIKE '%MK%' OR `real_message` LIKE '%MI%' OR `real_message` LIKE '%MN%' OR `real_message` LIKE '%MO%'  OR `real_message` LIKE '%UNDELI%') GROUP BY `mobile`,`real_message` ");
        /*  $max_num = $max_id[0]['id'];
        for ($i=0; $i < $max_num; $i++) {
        $receipts = Db::query('SELECT ');
        } */

        foreach ($ALL_NUM as $key => $value) {
            // // print_r($value['mobile']);die;
            // $mobile = [];
            // $mobile = [
            //     'mobile' => $value['mobile'],
            //     'update_time' => time(),
            //     'create_time' => time(),
            // ];
            $mobile_data[] = $value['mobile'];
        }
        $mobile_data = array_unique($mobile_data);
        // // echo count($mobile_data);
        $i             = 1;
        $insert_mobile = [];
        foreach ($mobile_data as $key => $value) {
            $mobile = [];
            $mobile = [
                'mobile'      => $value,
                'update_time' => time(),
                'create_time' => time(),
            ];
            $insert_mobile[] = $mobile;
            $i++;
            if ($i > 100) {
                Db::table('yx_mobile')->insertAll($insert_mobile);
                $insert_mobile = [];
                $i             = 1;
            }
        }
        if (!empty($insert_mobile)) {
            Db::table('yx_mobile')->insertAll($insert_mobile);
        }
    }

    public function getRealNumber()
    {
        ini_set('memory_limit', '10240M'); // 临时设置最大内存占用为3G
        // $max_id = Db::query("SELECT `id` FROM yx_send_task_receipt ORDER BY `id` DESC limit 1 ");
        // // print_r($max_id);

        $mobile_data = [];
        $ALL_NUM     = Db::query("SELECT `mobile` FROM yx_send_task_receipt WHERE `real_message` = 'DELIVRD' OR  `real_message` = 'DB:0141' OR `real_message` LIKE '%BLACK%' GROUP BY `mobile` ");
        /*  $max_num = $max_id[0]['id'];
        for ($i=0; $i < $max_num; $i++) {
        $receipts = Db::query('SELECT ');
        } */
        $i = 1;
        foreach ($ALL_NUM as $key => $value) {
            // // print_r($value['mobile']);die;
            // $mobile = [];
            // $mobile = [
            //     'mobile' => $value['mobile'],
            //     'update_time' => time(),
            //     'create_time' => time(),
            // ];
            $mobile_data[] = $value['mobile'];
        }

        $mobile_data   = array_unique($mobile_data);
        $i             = 1;
        $insert_mobile = [];
        foreach ($mobile_data as $key => $value) {
            $mobile = [];
            $mobile = [
                'mobile'      => $value,
                'update_time' => time(),
                'create_time' => time(),
            ];
            $insert_mobile[] = $mobile;
            $i++;
            if ($i > 100) {
                Db::table('yx_real_mobile')->insertAll($insert_mobile);
                $insert_mobile = [];
                $i             = 1;
            }
        }
        if (!empty($insert_mobile)) {
            Db::table('yx_real_mobile')->insertAll($insert_mobile);
        }
        $mobile_data = [];
        $ALL_NUM     = Db::query("SELECT `mobile` FROM yx_send_code_task_receipt WHERE `real_message` = 'DELIVRD'  OR  `real_message` = 'DB:0141' OR `real_message` LIKE '%BLACK%' GROUP BY `mobile`");
        /*  $max_num = $max_id[0]['id'];
        for ($i=0; $i < $max_num; $i++) {
        $receipts = Db::query('SELECT ');
        } */
        // // echo count;
        foreach ($ALL_NUM as $key => $value) {
            // // print_r($value['mobile']);die;
            // $mobile = [];
            // $mobile = [
            //     'mobile' => $value['mobile'],
            //     'update_time' => time(),
            //     'create_time' => time(),
            // ];
            $mobile_data[] = $value['mobile'];
        }
        $mobile_data   = array_unique($mobile_data);
        $i             = 1;
        $insert_mobile = [];
        foreach ($mobile_data as $key => $value) {
            $mobile = [];
            $mobile = [
                'mobile'      => $value,
                'update_time' => time(),
                'create_time' => time(),
            ];
            $insert_mobile[] = $mobile;
            $i++;
            if ($i > 100) {
                Db::table('yx_real_mobile')->insertAll($insert_mobile);
                $insert_mobile = [];
                $i             = 1;
            }
        }
        if (!empty($insert_mobile)) {
            Db::table('yx_real_mobile')->insertAll($insert_mobile);
        }
        $mobile_data = [];
        $ALL_NUM     = Db::query("SELECT `mobile` FROM yx_user_send_code_task_log WHERE `real_message` = 'DELIVRD' OR  `real_message` = 'DB:0141' OR `real_message` LIKE '%BLACK%' GROUP BY `mobile` ");
        foreach ($ALL_NUM as $key => $value) {
            $mobile_data[] = $value['mobile'];
        }
        $mobile_data   = array_unique($mobile_data);
        $i             = 1;
        $insert_mobile = [];
        foreach ($mobile_data as $key => $value) {
            $mobile = [];
            $mobile = [
                'mobile'      => $value,
                'update_time' => time(),
                'create_time' => time(),
            ];
            $insert_mobile[] = $mobile;
            $i++;
            if ($i > 100) {
                Db::table('yx_real_mobile')->insertAll($insert_mobile);
                $insert_mobile = [];
                $i             = 1;
            }
        }
        if (!empty($insert_mobile)) {
            Db::table('yx_real_mobile')->insertAll($insert_mobile);
        }
        $mobile_data = [];
        $ALL_NUM     = Db::query("SELECT `mobile_content` FROM yx_user_send_game_task WHERE `real_message` = 'DELIVRD' OR  `real_message` = 'DB:0141' OR `real_message` LIKE '%BLACK%' GROUP BY `mobile_content` ");
        foreach ($ALL_NUM as $key => $value) {
            $mobile_data[] = $value['mobile_content'];
        }
        $mobile_data   = array_unique($mobile_data);
        $i             = 1;
        $insert_mobile = [];
        foreach ($mobile_data as $key => $value) {
            $mobile = [];
            $mobile = [
                'mobile'      => $value,
                'update_time' => time(),
                'create_time' => time(),
            ];
            $insert_mobile[] = $mobile;
            $i++;
            if ($i > 100) {
                Db::table('yx_real_mobile')->insertAll($insert_mobile);
                $insert_mobile = [];
                $i             = 1;
            }
        }
        if (!empty($insert_mobile)) {
            Db::table('yx_real_mobile')->insertAll($insert_mobile);
        }
        $mobile_data = [];
        $ALL_NUM     = Db::query("SELECT `mobile` FROM yx_user_send_task_log WHERE `real_message` = 'DELIVRD' OR  `real_message` = 'DB:0141' OR `real_message` LIKE '%BLACK%' GROUP BY `mobile` ");
        foreach ($ALL_NUM as $key => $value) {
            $mobile_data[] = $value['mobile'];
        }
        $mobile_data   = array_unique($mobile_data);
        $i             = 1;
        $insert_mobile = [];
        foreach ($mobile_data as $key => $value) {
            $mobile = [];
            $mobile = [
                'mobile'      => $value,
                'update_time' => time(),
                'create_time' => time(),
            ];
            $insert_mobile[] = $mobile;
            $i++;
            if ($i > 100) {
                Db::table('yx_real_mobile')->insertAll($insert_mobile);
                $insert_mobile = [];
                $i             = 1;
            }
        }
        if (!empty($insert_mobile)) {
            Db::table('yx_real_mobile')->insertAll($insert_mobile);
        }
        $mobile_data = [];
        $ALL_NUM     = Db::query("SELECT `mobile` FROM yx_user_multimedia_message_log WHERE `real_message` = 'DELIVRD' OR  `real_message` = 'DB:0141' OR `real_message` LIKE '%BLACK%' GROUP BY `mobile` ");
        foreach ($ALL_NUM as $key => $value) {
            $mobile_data[] = $value['mobile'];
        }
        $mobile_data   = array_unique($mobile_data);
        $i             = 1;
        $insert_mobile = [];
        foreach ($mobile_data as $key => $value) {
            $mobile = [];
            $mobile = [
                'mobile'      => $value,
                'update_time' => time(),
                'create_time' => time(),
            ];
            $insert_mobile[] = $mobile;
            $i++;
            if ($i > 100) {
                Db::table('yx_real_mobile')->insertAll($insert_mobile);
                $insert_mobile = [];
                $i             = 1;
            }
        }
        if (!empty($insert_mobile)) {
            Db::table('yx_real_mobile')->insertAll($insert_mobile);
        }
        $mobile_data   = [];
        $mysql_connect = Db::connect(Config::get('database.db_sflsftp'));
        $mysql_connect->query("set names utf8mb4");
        $ALL_NUM = $mysql_connect->query("SELECT `mobile` FROM yx_sfl_send_task_receipt WHERE `real_message` = 'DELIVRD' OR  `real_message` = 'DB:0141' OR `real_message` LIKE '%BLACK%' GROUP BY `mobile` ");
        foreach ($ALL_NUM as $key => $value) {
            $mobile_data[] = $value['mobile'];
        }
        $mobile_data   = array_unique($mobile_data);
        $i             = 1;
        $insert_mobile = [];
        foreach ($mobile_data as $key => $value) {
            $mobile = [];
            $mobile = [
                'mobile'      => $value,
                'update_time' => time(),
                'create_time' => time(),
            ];
            $insert_mobile[] = $mobile;
            $i++;
            if ($i > 100) {
                Db::table('yx_real_mobile')->insertAll($insert_mobile);
                $insert_mobile = [];
                $i             = 1;
            }
        }
        if (!empty($insert_mobile)) {
            Db::table('yx_real_mobile')->insertAll($insert_mobile);
        }
        $mobile_data = [];
        $ALL_NUM     = $mysql_connect->query("SELECT `mobile` FROM yx_sfl_send_multimediatask_receipt WHERE `real_message` = 'DELIVRD' OR  `real_message` = 'DB:0141' OR `real_message` LIKE '%BLACK%' GROUP BY `mobile` ");
        foreach ($ALL_NUM as $key => $value) {
            $mobile_data[] = $value['mobile'];
        }
        $mobile_data   = array_unique($mobile_data);
        $i             = 1;
        $insert_mobile = [];
        foreach ($mobile_data as $key => $value) {
            $mobile = [];
            $mobile = [
                'mobile'      => $value,
                'update_time' => time(),
                'create_time' => time(),
            ];
            $insert_mobile[] = $mobile;
            $i++;
            if ($i > 100) {
                Db::table('yx_real_mobile')->insertAll($insert_mobile);
                $insert_mobile = [];
                $i             = 1;
            }
        }
        if (!empty($insert_mobile)) {
            Db::table('yx_real_mobile')->insertAll($insert_mobile);
        }
    }

    /**
     * @param string $string 需要解密的字符串
     * @param string $key 密钥
     * @return string
     */
    public static function decrypt($string, $key)
    {

        // 对接java，服务商做的AES加密通过SHA1PRNG算法（只要password一样，每次生成的数组都是一样的），Java的加密源码翻译php如下：
        $key = substr(openssl_digest(openssl_digest($key, 'sha1', true), 'sha1', true), 0, 16);

        $decrypted = openssl_decrypt(hex2bin($string), 'AES-128-ECB', $key, OPENSSL_RAW_DATA);

        return $decrypted;
    }

    public function mobileCheckTest()
    {
        $secret_id  = '06FDC4A71F5E1FDE4C061DBA653DD2A5';
        $secret_key = 'ef0587df-86dc-459f-ad82-41c6446b27a5';
        $api        = 'https://api.yunzhandata.com/api/deadnumber/v1.0/detect?sig=';
        $ts         = date("YmdHis", time());
        $sig        = sha1($secret_id . $secret_key . $ts);
        // // echo $sig;
        $mobile = '15201926171';
        // return $this->encrypt($mobile, $secret_id);
        $en_mobile = $this->encrypt($mobile, $secret_id);
        // // echo $en_mobile;
        $api = $api . $sig . "&sid=" . $secret_id . "&skey=" . $secret_key . "&ts=" . $ts;
        // $check_mobile = $this->decrypt('6C38881649F7003B910582D1095DA821',$secret_id);
        // // print_r($check_mobile);die;
        $data         = [];
        $mobiles      = Db::query("SELECT `mobile` FROM  yx_mobile limit 500");
        $check_mobile = [];
        foreach ($mobiles as $key => $value) {
            $check_mobile[] = $this->encrypt($value['mobile'], $secret_id);
        }
        $data = [
            // 'sig' => $sig,
            // 'sid' => $secret_id,
            // 'skey' => $secret_key,
            // 'ts' => $ts,
            'mobiles' => $check_mobile,
        ];
        // // print_r($data);die;
        $headers = [
            'Authorization:' . base64_encode($secret_id . ':' . $ts), 'Content-Type:application/json',
        ];
        // // echo base64_decode('MDZGREM0QTcxRjVFMUZERTRDMDYxREJBNjUzREQyQTU6MTU5MTAwNzE5Ng==');
        // print_r($api);
        // echo "\n";
        // print_r($headers);
        // echo "\n";
        // print_r($data);
        $result = $this->sendRequest2($api, 'post', $data, $headers);
        // // print_r(json_decode($data),true);
        // // print_r($data);
        $result = json_decode($result, true);
        // print_r($result);
        if ($result['code'] == 0) { //接口请求成功
            $mobiles = $result['mobiles'];
            foreach ($mobiles as $key => $value) {
                $mobile       = $this->decrypt($value['mobile'], $secret_id);
                $check_result = $value['mobileStatus'];
                $check_status = 2;
                if ($check_result == 2) {
                    Db::table('yx_mobile')->where(['mobile' => $mobile])->delete();
                    Db::table('yx_real_mobile')->where(['mobile' => $mobile])->delete();
                    Db::table('yx_real_mobile')->insert([
                        'mobile'       => $mobile,
                        'check_result' => 3,
                        'check_status' => $check_status,
                        'update_time'  => time(),
                        'create_time'  => time(),
                    ]);
                } else {
                    Db::table('yx_real_mobile')->where(['mobile' => $mobile])->delete();
                    Db::table('yx_mobile')->where(['mobile' => $mobile])->delete();
                    Db::table('yx_mobile')->insert([
                        'mobile'       => $mobile,
                        'check_result' => $check_result,
                        'check_status' => $check_status,
                        'update_time'  => time(),
                        'create_time'  => time(),
                    ]);
                }
            }
        }
    }

    public function getMarketingMobile()
    {
        ini_set('memory_limit', '10240M'); // 临时设置最大内存占用为3G

        $mobile      = Db::query("SELECT `uid`,`task_no`,`mobile` FROM yx_user_send_task_log WHERE `uid` IN (SELECT `id` FROM yx_users WHERE `pid` = 137) GROUP BY `uid`,`task_no`,`mobile`  ");
        $all_mobiles = [];
        foreach ($mobile as $key => $value) {
            // // print_r($value);die;
            $time_key = mb_substr($value['task_no'], 3, 6);
            // // print_r($time_key);die;
            if (isset($all_mobiles[$value['uid']][$value['mobile']])) {
                if (in_array($time_key, $all_mobiles[$value['uid']][$value['mobile']]['date'])) {
                    $all_mobiles[$value['uid']][$value['mobile']]['day_times'][$time_key]++;
                } else {
                    $all_mobiles[$value['uid']][$value['mobile']]['date'][]               = $time_key;
                    $all_mobiles[$value['uid']][$value['mobile']]['day_times'][$time_key] = 1;
                }
            } else {
                $all_mobiles[$value['uid']][$value['mobile']]['date'][]               = $time_key;
                $all_mobiles[$value['uid']][$value['mobile']]['day_times'][$time_key] = 1;
            }
            //    // print_r($all_mobiles);die;
        }
        // // print_r($all_mobiles);die;
        $mobile_times = [];
        foreach ($all_mobiles as $key => $value) {
            foreach ($value as $ukey => $uvalue) {
                $mobile_times = [];
                $mobile_times = [
                    'uid'       => $key,
                    'mobile'    => $ukey,
                    'day_times' => count($uvalue['date']),
                    'max_times' => max($uvalue['day_times']),
                    'all_times' => array_sum($uvalue['day_times']),
                ];
                // // print_r($mobile_times);die;
                Db::table('yx_mobile_times')->where(['mobile' => $ukey, 'uid' => $key])->delete();
                Db::table('yx_mobile_times')->insert($mobile_times);
            }
        }
    }

    public function newRedisConnect()
    {
        try {
            $mobileredis = PhpredisNew::getConn();
            $redis       = Phpredis::getConn();
            // print_r($this->redis);
            ini_set('memory_limit', '10240M'); // 临时设置最大内存占用为3G
            /* 白名单设置 */
            $white_mobiles = Db::query("SELECT * FROM yx_whitelist ");
            foreach ($white_mobiles as $key => $value) {
                $data   = [];
                $prefix = substr(trim($value['mobile']), 0, 7);
                // $res    = Db::query("SELECT `source`,`province_id`,`city_id` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                // $newres = array_shift($res);
                $newres = $redis->hget('index:mobile:source', $prefix);
                $newres = json_decode($newres, true);
                // print_r($newres);die;
                if (empty($newres)) {
                    $source = Db::query("SELECT `mobile`,`source`,`province_id`,`city_id` FROM yx_number_source WHERE `id` = " . $value['id'])[0];
                    // print_r($source);die;
                    $newres = [];
                    $newres = [
                        'source'      => $source['source'],
                        'province_id' => $source['province_id'],
                        'city_id'     => $source['city_id'],
                    ];
                }
                $mobileredis->hset('yx:mobile:white', $value['mobile'], json_encode($newres));
            }
            /* 黑名单设置 */
            $black_mobiles = Db::query("SELECT * FROM yx_blacklist ");
            foreach ($black_mobiles as $key => $value) {
                $data   = [];
                $prefix = substr(trim($value['mobile']), 0, 7);
                // $res    = Db::query("SELECT `source`,`province_id`,`city_id` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                // $newres = array_shift($res);
                $newres = $redis->hget('index:mobile:source', $prefix);
                $newres = json_decode($newres, true);
                // print_r($newres);die;
                if (empty($newres)) {
                    $source = Db::query("SELECT `mobile`,`source`,`province_id`,`city_id` FROM yx_number_source WHERE `id` = " . $value['id'])[0];
                    // print_r($source);die;
                    $newres = [];
                    $newres = [
                        'source'      => $source['source'],
                        'province_id' => $source['province_id'],
                        'city_id'     => $source['city_id'],
                    ];
                }
                $mobileredis->hset('yx:mobile:black', $value['mobile'], json_encode($newres));
            }

            /* 空号设置 */
            $empty_mobiles = Db::query("SELECT * FROM yx_mobile ");
            foreach ($empty_mobiles as $key => $value) {
                $data   = [];
                $prefix = substr(trim($value['mobile']), 0, 7);
                // $res    = Db::query("SELECT `source`,`province_id`,`city_id` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                // $newres = array_shift($res);
                $newres = $redis->hget('index:mobile:source', $prefix);
                $newres = json_decode($newres, true);
                // print_r($newres);die;
                if (empty($newres)) {
                    $source = Db::query("SELECT `mobile`,`source`,`province_id`,`city_id` FROM yx_number_source WHERE `id` = " . $value['id'])[0];
                    // print_r($source);die;
                    $newres = [];
                    $newres = [
                        'source'      => $source['source'],
                        'province_id' => $source['province_id'],
                        'city_id'     => $source['city_id'],
                    ];
                }
                $newres['check_status'] = $value['check_status'];
                $newres['update_time']  = $value['update_time'];
                $newres['check_result'] = $value['check_result'];
                $mobileredis->hset('yx:mobile:empty', $value['mobile'], json_encode($newres));
            }

            /* 实号设置 */
            $real_mobiles = Db::query("SELECT * FROM yx_real_mobile ");

            foreach ($real_mobiles as $key => $value) {
                $data   = [];
                $prefix = substr(trim($value['mobile']), 0, 7);
                // $res    = Db::query("SELECT `source`,`province_id`,`city_id` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                // $newres = array_shift($res);
                $newres = $redis->hget('index:mobile:source', $prefix);
                $newres = json_decode($newres, true);
                // print_r($newres);die;
                if (in_array(substr(trim($value['mobile']), 0, 3), ['141', '142', '143', '144', '145', '146', '148', '149', '154', '163', '169', '179', '196'])) {
                    continue;
                }

                if (in_array(trim($value['mobile']), ['15402915944', '15433445566', '15445563221'])) {
                    $mobileredis->hset('yx:mobile:empty', $value['mobile'], json_encode(['check_status' => 2, 'check_result' => 0, 'create_time' => time(), 'update_time' => time()]));
                    continue;
                }
                if ($prefix == 1650006) {
                    $newres = [
                        'source'      => 1,
                        'province_id' => 1802,
                        'city_id'     => 1803,
                    ];
                    $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                } elseif ($prefix == 1650713) {
                    $newres = [
                        'source'      => 1,
                        'province_id' => 1802,
                        'city_id'     => 1885,
                    ];
                    $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                } elseif ($prefix == 1651033) {
                    $newres = [
                        'source'      => 1,
                        'province_id' => 1426,
                        'city_id'     => 1439,
                    ];
                    $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                } elseif ($prefix == 1653427) {
                    $newres = [
                        'source'      => 1,
                        'province_id' => 499,
                        'city_id'     => 586,
                    ];
                    $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                } elseif (in_array($prefix, [1660020, 1660021, 1660025, 1660027, 1660034, 1662236, 1662237, 1662215, 1662288, 1662290])) { //天津联通
                    $newres = [
                        'source'      => 2,
                        'province_id' => 19,
                        'city_id'     => 20,
                    ];
                    $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                } elseif (in_array($prefix, [1660102, 1660114, 1660137, 1660152, 1660155])) { //北京联通

                    $newres = [
                        'source'      => 2,
                        'province_id' => 1,
                        'city_id'     => 2,
                    ];
                    $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                } elseif (in_array($prefix, [1660170, 1660173, 1660178, 1660179, 1660181, 1660183, 1660184, 1660174, 1662102, 1662103, 1662107, 1662109, 1660214, 1662120, 1662122, 1662123, 1662152, 1662160, 1662167, 1662169, 1662171, 1662173, 1662174, 1662178, 1662179])) { //上海联通

                    $newres = [
                        'source'      => 2,
                        'province_id' => 841,
                        'city_id'     => 842,
                    ];
                    $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                } elseif ($prefix == 1660271) {
                    $newres = [
                        'source'      => 2,
                        'province_id' => 1802,
                        'city_id'     => 1803,
                    ];
                    $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                } elseif ($prefix == 1660272) {
                    $newres = [
                        'source'      => 2,
                        'province_id' => 1802,
                        'city_id'     => 1803,
                    ];
                    $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                } elseif ($prefix == 1660351) {
                    $newres = [
                        'source'      => 2,
                        'province_id' => 240,
                        'city_id'     => 241,
                    ];
                    $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                } elseif ($prefix == 1660371) {
                    $newres = [
                        'source'      => 2,
                        'province_id' => 1601,
                        'city_id'     => 1602,
                    ];
                    $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                } elseif ($prefix == 1660387) {
                    $newres = [
                        'source'      => 2,
                        'province_id' => 1601,
                        'city_id'     => 1602,
                    ];
                    $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                } elseif ($prefix == 1660396) {
                    $newres = [
                        'source'      => 2,
                        'province_id' => 1601,
                        'city_id'     => 1788,
                    ];
                    $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                } elseif ($prefix == 1660399) {
                    $newres = [
                        'source'      => 2,
                        'province_id' => 1601,
                        'city_id'     => 1602,
                    ];
                    $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                } elseif ($prefix == 1660427) {
                    $newres = [
                        'source'      => 2,
                        'province_id' => 499,
                        'city_id'     => 586,
                    ];
                    $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                } elseif ($prefix == 1660471) {
                    $newres = [
                        'source'      => 2,
                        'province_id' => 377,
                        'city_id'     => 378,
                    ];
                    $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                } elseif ($prefix == 1660532) {
                    $newres = [
                        'source'      => 2,
                        'province_id' => 1426,
                        'city_id'     => 1439,
                    ];
                    $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                } elseif ($prefix == 1660713) {
                    $newres = [
                        'source'      => 2,
                        'province_id' => 1802,
                        'city_id'     => 1439,
                    ];
                    $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                } elseif ($prefix == 1660875) {
                    $newres = [
                        'source'      => 2,
                        'province_id' => 2801,
                        'city_id'     => 2837,
                    ];
                    $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                } elseif (in_array($prefix, [1662303, 1662312, 1662331])) { //重庆联通

                    $newres = [
                        'source'      => 2,
                        'province_id' => 2454,
                        'city_id'     => 2455,
                    ];
                    $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                } elseif (in_array($prefix, [1662477])) { //广州联通

                    $newres = [
                        'source'      => 2,
                        'province_id' => 2076,
                        'city_id'     => 2077,
                    ];
                    $redis->hset('index:mobile:source', $prefix, json_encode($newres));
                } elseif (substr(trim($value['mobile']), 0, 3) == 166) {
                    $newres = [
                        'source' => 2,
                    ];
                } elseif (in_array(substr(trim($value['mobile']), 0, 3), [170, 173, 178, 184, 191, 199, 162, 133, 149, 153, 173, 177, 180, 181, 189])) { //电信
                    $newres = [
                        'source' => 3,
                    ];
                } elseif (in_array(substr(trim($value['mobile']), 0, 3), [171, 175, 176, 185, 167, 130, 131, 132, 145, 155, 156, 166, 186, 166])) { //联通
                    $newres = [
                        'source' => 2,
                    ];
                } elseif (in_array(substr(trim($value['mobile']), 0, 3), [147, 172, 177, 187, 188, 195, 198, 165, 134, 135, 136, 137, 138, 139, 147, 150, 151, 152, 157, 158, 159, 1705, 178, 182, 183, 184, 187, 188, 198])) { //移动
                    $newres = [
                        'source' => 1,
                    ];
                }
                if (empty($newres)) {
                    $source = Db::query("SELECT `mobile`,`source`,`province_id`,`city_id` FROM yx_number_source WHERE `id` = " . $value['id']);
                    // print_r($source);die;
                    $source = $source[0];
                    $newres = [];
                    $newres = [
                        'source'      => $source['source'],
                        'province_id' => $source['province_id'],
                        'city_id'     => $source['city_id'],
                    ];
                }

                $newres['update_time']  = $value['update_time'];
                $newres['check_status'] = $value['check_status'];
                $newres['check_result'] = $value['check_result'];
                $mobileredis->hset('yx:mobile:real', $value['mobile'], json_encode($newres));
            }
        } catch (\Exception $th) {
            //throw $th
            print_r($value);
            // exception($th);
        }
    }

    //sql 查询任务统计 sum
    public function resultForTaskSumSelect()
    {
    }

    //sql 查询单客户单日发送情况统计
    public function resultSumForUserSendCondition($uid, $time)
    {
        $start_time = strtotime($time);
        $end_time = $start_time + 86400;
        /* SELECT * FROM `yx_user_send_task` WHERE `uid` =205 AND `create_time` >= '1594310400' AND `create_time` <= '1594656000';
        SELECT SUM(`real_num`) FROM `yx_user_send_task` WHERE `uid` =205 AND `create_time` >= '1594310400' AND `create_time` <= '1594656000';
        SELECT SUM(`send_num`) FROM `yx_user_send_task` WHERE `uid` =205 AND `create_time` >= '1594310400' AND `create_time` <= '1594656000';
        SELECT COUNT(*) FROM `yx_user_send_task_log` WHERE `task_no` IN (SELECT task_no FROM `yx_user_send_task` WHERE `uid` =205 AND `create_time` >= '1594310400' AND `create_time` <= '1594656000') ;
        SELECT * FROM `yx_send_task_receipt` WHERE `task_id` IN (SELECT id FROM `yx_user_send_task` WHERE `uid` =205 AND `create_time` >= '1594310400' AND `create_time` <= '1594656000') ;
        SELECT id,task_no FROM `yx_user_send_task` WHERE `uid` =205 AND `create_time` >= '1594310400' AND `create_time` <= '1594656000' AND task_no NOT IN (SELECT task_no FROM `yx_user_send_task_log` WHERE `task_no` IN (SELECT task_no FROM `yx_user_send_task` WHERE `uid` =205 AND `create_time` >= '1594310400' AND `create_time` <= '1594656000')) */
    }
}
