<?php

namespace app\console\com;

use app\console\Pzlife;
use cache\Phpredis;
use cache\PhpredisNew;
use Config;
use Env;
use Kafka\Produce;
use Kafka\Producer;
use Kafka\ProducerConfig;
use Monolog\Handler\StdoutHandler;
use Monolog\Logger;
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

            for ($i = 0; $i < 100; $i++) {
                $result = $producer->send(array(
                    array(
                        'topic' => 'test1',
                        'value' => 'test1....message.',
                        'key'   => '',
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
        try {
            $start_time = strtotime('2020-08-01');
            // $end_time = time();
            while (true) {
                $all_mobiles = [];
                $start_time  = $start_time;
                $end_time = $start_time + 86400;
                // echo "SELECT `uid`,`task_no`,`mobile` FROM yx_user_send_task_log WHERE `uid` IN (SELECT `id` FROM yx_users WHERE `pid` = 137) AND `create_time` >= '".$start_time."' AND `create_time` < '".$end_time."' GROUP BY `uid`,`task_no`,`mobile`  ";die;
                $mobile      = Db::query("SELECT `uid`,`task_no`,`mobile` FROM yx_user_send_task_log WHERE `uid` IN (SELECT `id` FROM yx_users WHERE `pid` = 137) AND `create_time` >= '" . $start_time . "' AND `create_time` < '" . $end_time . "' GROUP BY `uid`,`task_no`,`mobile`  ");
                $start_time = $end_time;
                foreach ($mobile as $key => $value) {
                    // print_r($value);die;
                    $time_key = mb_substr($value['task_no'], 3, 6);
                    // print_r($time_key);die;
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
                // print_r($all_mobiles);
                // die;
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
                            'timekey' => date('Ym', $start_time),
                        ];
                        // print_r($mobile_times);die;
                        $log = Db::query("SELECT `*` FROM yx_mobile_times WHERE `mobile` = '" . $ukey . "' AND `uid` = '" . $key . "' AND `timekey` = '" . $mobile_times['timekey'] . "' ");
                        // Db::table('yx_mobile_times')->where(['mobile' => $ukey, 'uid' => $key])->delete();
                        // print_r($log);die;

                        if ($log) {
                            $new_max_times = $log[0]['max_times'] + $mobile_times['max_times'];
                            $new_all_times = $log[0]['all_times'] + $mobile_times['all_times'];
                            $new_day_times = $log[0]['day_times'] + $mobile_times['day_times'];
                            Db::table('yx_mobile_times')->where(['id' => $log[0]['id']])->update([
                                'day_times' => $new_day_times,
                                'all_times' => $new_all_times,
                                'max_times' => $new_max_times,
                            ]);
                        } else {
                            Db::table('yx_mobile_times')->insert($mobile_times);
                        }
                    }
                }
            }
            die;
            $mobile      = Db::query("SELECT `uid`,`task_no`,`mobile` FROM yx_user_send_task_log WHERE `uid` IN (SELECT `id` FROM yx_users WHERE `pid` = 137) GROUP BY `uid`,`task_no`,`mobile`  ");
            $all_mobiles = [];
            foreach ($mobile as $key => $value) {
                // print_r($value);die;
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
            // print_r($all_mobiles);
            // die;
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
        } catch (\Exception $th) {
            //throw $th;
            exception($th);
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
                } else {
                    continue;
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
        $end_time   = $start_time + 86400;
        /* SELECT * FROM `yx_user_send_task` WHERE `uid` =205 AND `create_time` >= '1594310400' AND `create_time` <= '1594656000';
    SELECT SUM(`real_num`) FROM `yx_user_send_task` WHERE `uid` =205 AND `create_time` >= '1594310400' AND `create_time` <= '1594656000';
    SELECT SUM(`send_num`) FROM `yx_user_send_task` WHERE `uid` =205 AND `create_time` >= '1594310400' AND `create_time` <= '1594656000';
    SELECT COUNT(*) FROM `yx_user_send_task_log` WHERE `task_no` IN (SELECT task_no FROM `yx_user_send_task` WHERE `uid` =205 AND `create_time` >= '1594310400' AND `create_time` <= '1594656000') ;
    SELECT * FROM `yx_send_task_receipt` WHERE `task_id` IN (SELECT id FROM `yx_user_send_task` WHERE `uid` =205 AND `create_time` >= '1594310400' AND `create_time` <= '1594656000') ;
    SELECT id,task_no FROM `yx_user_send_task` WHERE `uid` =205 AND `create_time` >= '1594310400' AND `create_time` <= '1594656000' AND task_no NOT IN (SELECT task_no FROM `yx_user_send_task_log` WHERE `task_no` IN (SELECT task_no FROM `yx_user_send_task` WHERE `uid` =205 AND `create_time` >= '1594310400' AND `create_time` <= '1594656000')) */
    }

    public function businessSettlement()
    {
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        while (true) {
            $year_businessSettlement  = [];
            $month_businessSettlement = [];
            $day_businessSettlement   = [];
            $year_users               = [];
            $month_users              = [];
            $day_users                = [];
            // $start_time               = strtotime('-10 days');
            // print_r($start_time);die;
            $Received = updateReceivedForMessage();
            array_push($Received, 'DELIVRD');
            $Received_status = '';
            $or              = '';
            foreach ($Received as $key => $value) {
                $Received_status .= $or . "'" . $value . "'";
                $or = ',';
            }
            $start_time = (int) strtotime(date('2020-07-01'));
            while (true) {
                $end_time = $start_time + 86400;
                if ($end_time > time()) {
                    // break;
                    $end_time               = time();
                    $day_businessSettlement = [];
                    $day_users              = [];
                    $code_task              = [];
                    $code_task              = Db::query("SELECT * FROM yx_user_send_code_task WHERE `create_time` < " . $end_time . " AND `create_time` >= " . $start_time);
                    foreach ($code_task as $key => $value) {

                        $send_length = mb_strlen($value['task_content'], 'utf8');
                        $num         = 1;
                        if ($send_length > 70) {
                            $num = ceil($send_length / 67);
                        }
                        //计算成功 失败
                        $day = date('Ymd', $value['create_time']);
                        if (!array_key_exists($day, $day_users)) {
                            $day_users[$day] = [];
                        }
                        $task_log = Db::query("SELECT * FROM yx_user_send_code_task_log WHERE `task_no` = '" . $value['task_no'] . "' ");
                        // print_r($task_log);die;
                        $allnum       = count(explode(',', $value['mobile_content']));
                        $charging_num = $allnum * $num;
                        if (empty($task_log)) { //失败
                            $success_num = 0;
                            $default_num = $value['real_num'];
                            $unknown_num = 0;
                        } else {

                            $success_mobile_num = 0;
                            $default_mobile_num = 0;
                            $unknown_mobile_num = 0;
                            $success_mobile_num = count(Db::query("SELECT `task_id`,`mobile`,`status_message` FROM yx_send_code_task_receipt WHERE task_id = '" . $value['id'] . "' AND `mobile` IN (" . $value['mobile_content'] . ") AND `status_message` IN (" . $Received_status . ") GROUP BY `task_id`,`mobile`,`status_message` "));

                            if ($success_mobile_num < $allnum) {
                                $default_mobile_num = count(Db::query("SELECT `task_id`,`mobile`,`status_message` FROM yx_send_code_task_receipt WHERE task_id = '" . $value['id'] . "' AND `mobile` IN (" . $value['mobile_content'] . ") AND `status_message` NOT IN (" . $Received_status . ") GROUP BY `task_id`,`mobile`,`status_message` "));
                                // if () {}
                                $unknown_mobile_num = $allnum - $success_mobile_num - $default_mobile_num;
                            }
                            $success_num = $success_mobile_num * $num;
                            $default_num = $default_mobile_num * $num;
                            $unknown_num = $unknown_mobile_num * $num;

                            /*   print_r($success_num);
                            print_r($default_num);
                            print_r($unknown_num);
                            die; */
                            // echo "SELECT COUNT(`task_id`,`mobile`,`status_message`) FROM yx_send_code_task_receipt WHERE task_id = '".$value['id']."' AND `mobile` IN (".$value['mobile_content'].") AND `status_message` IN (".$Received_status.") GROUP BY `task_id`,`mobile`,`status_message`" ;die;
                        }

                        if (in_array($value['uid'], $day_users[$day])) {
                            $day_businessSettlement[$day][$value['uid']]['num'] += $charging_num;
                            $day_businessSettlement[$day][$value['uid']]['mobile_num'] += $allnum;
                            if (isset($day_businessSettlement[$day][$value['uid']]['success'])) {
                                $day_businessSettlement[$day][$value['uid']]['success'] += $success_num;
                            } else {
                                $day_businessSettlement[$day][$value['uid']]['success'] = $success_num;
                            }
                            if (isset($day_businessSettlement[$day][$value['uid']]['unknown'])) {
                                $day_businessSettlement[$day][$value['uid']]['unknown'] += $unknown_num;
                            } else {
                                $day_businessSettlement[$day][$value['uid']]['unknown'] = $unknown_num;
                            }
                            if (isset($day_businessSettlement[$day][$value['uid']]['default'])) {
                                $day_businessSettlement[$day][$value['uid']]['default'] += $default_num;
                            } else {
                                $day_businessSettlement[$day][$value['uid']]['default'] = $default_num;
                            }
                            /* if ($value['status_message'] == 'DELIVRD') {

                        } elseif (empty($value['status_message'])) {

                        } else {

                        // $day_businessSettlement[$day][$value['uid']]['default'] = $num;
                        } */
                        } else {
                            $day_users[$day][]                                         = $value['uid'];
                            $day_businessSettlement[$day][$value['uid']]['num']        = $charging_num;
                            $day_businessSettlement[$day][$value['uid']]['mobile_num'] = $allnum;
                            $day_businessSettlement[$day][$value['uid']]['success']    = $success_num;
                            $day_businessSettlement[$day][$value['uid']]['unknown']    = $unknown_num;
                            $day_businessSettlement[$day][$value['uid']]['default']    = $default_num;
                            /*  if ($value['status_message'] == 'DELIVRD') {

                        } elseif ($value['status_message'] == '') {

                        } else {

                        } */
                        }
                    }
                    Db::startTrans();
                    try {
                        foreach ($day_businessSettlement as $dkey => $d_value) {
                            foreach ($d_value as $key => $value) {
                                $success = isset($value['success']) ? $value['success'] : 0;
                                $num     = isset($value['num']) ? $value['num'] : 0;
                                if ($key == 47 && $dkey == 20200122) {
                                    $num = $num + 5784;
                                }
                                if ($key == 47 && $dkey == 20200125) {
                                    $num = $num + 289;
                                }
                                $day_user_settlement = [];
                                $day_user_settlement = [
                                    'timekey'     => $dkey,
                                    'uid'         => $key,
                                    'success'     => $success,
                                    'unknown'     => isset($value['unknown']) ? $value['unknown'] : 0,
                                    'default'     => isset($value['default']) ? $value['default'] : 0,
                                    'num'         => $num,
                                    'ratio'       => $success / $num * 100,
                                    'mobile_num'  => $value['mobile_num'],
                                    'business_id' => '6',
                                    'create_time' => time(),
                                    'update_time' => time(),
                                ];
                                $has = Db::query('SELECT * FROM `yx_statistics_day` WHERE `business_id` = 6 AND `timekey` = ' . $dkey . ' AND `uid` = ' . $key);
                                if ($has) {
                                    Db::table('yx_statistics_day')->where('id', $has[0]['id'])->update([
                                        'success'     => $success,
                                        'unknown'     => isset($value['unknown']) ? $value['unknown'] : 0,
                                        'default'     => isset($value['default']) ? $value['default'] : 0,
                                        'num'         => $num,
                                        'mobile_num'  => $value['mobile_num'],
                                        'ratio'       => $success / $num * 100,
                                        'update_time' => time(),
                                    ]);
                                } else {
                                    Db::table('yx_statistics_day')->insert($day_user_settlement);
                                }
                            }
                        }
                        Db::commit();
                    } catch (\Exception $e) {
                        Db::rollback();
                        exception($e);
                    }
                    break;
                } else {
                    $day_businessSettlement = [];
                    $day_users              = [];
                    $code_task              = [];
                    $code_task              = Db::query("SELECT * FROM yx_user_send_code_task WHERE `create_time` < " . $end_time . " AND `create_time` >= " . $start_time);
                    foreach ($code_task as $key => $value) {

                        $send_length = mb_strlen($value['task_content'], 'utf8');
                        $num         = 1;
                        if ($send_length > 70) {
                            $num = ceil($send_length / 67);
                        }
                        //计算成功 失败
                        $day = date('Ymd', $value['create_time']);
                        if (!array_key_exists($day, $day_users)) {
                            $day_users[$day] = [];
                        }
                        $task_log = Db::query("SELECT * FROM yx_user_send_code_task_log WHERE `task_no` = '" . $value['task_no'] . "' ");
                        // print_r($task_log);die;
                        $allnum       = count(explode(',', $value['mobile_content']));
                        $charging_num = $allnum * $num;
                        if (empty($task_log)) { //失败
                            $success_num = 0;
                            $default_num = $value['real_num'];
                            $unknown_num = 0;
                        } else {

                            $success_mobile_num = 0;
                            $default_mobile_num = 0;
                            $unknown_mobile_num = 0;
                            $success_mobile_num = count(Db::query("SELECT `task_id`,`mobile`,`status_message` FROM yx_send_code_task_receipt WHERE task_id = '" . $value['id'] . "' AND `mobile` IN (" . $value['mobile_content'] . ") AND `status_message` IN (" . $Received_status . ") GROUP BY `task_id`,`mobile`,`status_message` "));

                            if ($success_mobile_num < $allnum) {
                                $default_mobile_num = count(Db::query("SELECT `task_id`,`mobile`,`status_message` FROM yx_send_code_task_receipt WHERE task_id = '" . $value['id'] . "' AND `mobile` IN (" . $value['mobile_content'] . ") AND `status_message` NOT IN (" . $Received_status . ") GROUP BY `task_id`,`mobile`,`status_message` "));
                                // if () {}
                                $unknown_mobile_num = $allnum - $success_mobile_num - $default_mobile_num;
                            }
                            $success_num = $success_mobile_num * $num;
                            $default_num = $default_mobile_num * $num;
                            $unknown_num = $unknown_mobile_num * $num;

                            /*   print_r($success_num);
                            print_r($default_num);
                            print_r($unknown_num);
                            die; */
                            // echo "SELECT COUNT(`task_id`,`mobile`,`status_message`) FROM yx_send_code_task_receipt WHERE task_id = '".$value['id']."' AND `mobile` IN (".$value['mobile_content'].") AND `status_message` IN (".$Received_status.") GROUP BY `task_id`,`mobile`,`status_message`" ;die;
                        }

                        if (in_array($value['uid'], $day_users[$day])) {
                            $day_businessSettlement[$day][$value['uid']]['num'] += $charging_num;
                            $day_businessSettlement[$day][$value['uid']]['mobile_num'] += $allnum;
                            if (isset($day_businessSettlement[$day][$value['uid']]['success'])) {
                                $day_businessSettlement[$day][$value['uid']]['success'] += $success_num;
                            } else {
                                $day_businessSettlement[$day][$value['uid']]['success'] = $success_num;
                            }
                            if (isset($day_businessSettlement[$day][$value['uid']]['unknown'])) {
                                $day_businessSettlement[$day][$value['uid']]['unknown'] += $unknown_num;
                            } else {
                                $day_businessSettlement[$day][$value['uid']]['unknown'] = $unknown_num;
                            }
                            if (isset($day_businessSettlement[$day][$value['uid']]['default'])) {
                                $day_businessSettlement[$day][$value['uid']]['default'] += $default_num;
                            } else {
                                $day_businessSettlement[$day][$value['uid']]['default'] = $default_num;
                            }
                            /* if ($value['status_message'] == 'DELIVRD') {

                        } elseif (empty($value['status_message'])) {

                        } else {

                        // $day_businessSettlement[$day][$value['uid']]['default'] = $num;
                        } */
                        } else {
                            $day_users[$day][]                                         = $value['uid'];
                            $day_businessSettlement[$day][$value['uid']]['num']        = $charging_num;
                            $day_businessSettlement[$day][$value['uid']]['mobile_num'] = $allnum;
                            $day_businessSettlement[$day][$value['uid']]['success']    = $success_num;
                            $day_businessSettlement[$day][$value['uid']]['unknown']    = $unknown_num;
                            $day_businessSettlement[$day][$value['uid']]['default']    = $default_num;
                            /*  if ($value['status_message'] == 'DELIVRD') {

                        } elseif ($value['status_message'] == '') {

                        } else {

                        } */
                        }
                        print_r($day_businessSettlement);
                    }
                    Db::startTrans();
                    try {
                        foreach ($day_businessSettlement as $dkey => $d_value) {
                            foreach ($d_value as $key => $value) {
                                $success = isset($value['success']) ? $value['success'] : 0;
                                $num     = isset($value['num']) ? $value['num'] : 0;
                                if ($key == 47 && $dkey == 20200122) {
                                    $num = $num + 5784;
                                }
                                if ($key == 47 && $dkey == 20200125) {
                                    $num = $num + 289;
                                }
                                $day_user_settlement = [];
                                $day_user_settlement = [
                                    'timekey'     => $dkey,
                                    'uid'         => $key,
                                    'success'     => $success,
                                    'unknown'     => isset($value['unknown']) ? $value['unknown'] : 0,
                                    'default'     => isset($value['default']) ? $value['default'] : 0,
                                    'num'         => $num,
                                    'ratio'       => $success / $num * 100,
                                    'mobile_num'  => $value['mobile_num'],
                                    'business_id' => '6',
                                    'create_time' => time(),
                                    'update_time' => time(),
                                ];
                                $has = Db::query('SELECT * FROM `yx_statistics_day` WHERE `business_id` = 6 AND `timekey` = ' . $dkey . ' AND `uid` = ' . $key);
                                if ($has) {
                                    Db::table('yx_statistics_day')->where('id', $has[0]['id'])->update([
                                        'success'     => $success,
                                        'unknown'     => isset($value['unknown']) ? $value['unknown'] : 0,
                                        'default'     => isset($value['default']) ? $value['default'] : 0,
                                        'num'         => $num,
                                        'mobile_num'  => $value['mobile_num'],
                                        'ratio'       => $success / $num * 100,
                                        'update_time' => time(),
                                    ]);
                                } else {
                                    Db::table('yx_statistics_day')->insert($day_user_settlement);
                                }
                            }
                        }
                        Db::commit();
                    } catch (\Exception $e) {
                        Db::rollback();
                        exception($e);
                    }
                }

                // print_r($Received_status);die;

            }
            sleep(900);
        }
    }

    public function marketingSettlement()
    {
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        while (true) {
            $year_businessSettlement  = [];
            $month_businessSettlement = [];
            $day_businessSettlement   = [];
            $year_users               = [];
            $month_users              = [];
            $day_users                = [];
            // $start_time               = strtotime('-10 days');
            // print_r($start_time);die;
            $Received = updateReceivedForMessage();
            array_push($Received, 'DELIVRD');
            $Received_status = '';
            $or              = '';
            foreach ($Received as $key => $value) {
                $Received_status .= $or . "'" . $value . "'";
                $or = ',';
            }
            $start_time = (int) strtotime(date('2020-07-01'));
            while (true) {
                $end_time = $start_time + 86400;
                if ($end_time > time()) {
                    // break;
                    $end_time               = time();
                    $day_businessSettlement = [];
                    $day_users              = [];
                    $code_task              = [];
                    $code_task              = Db::query("SELECT * FROM yx_user_send_task WHERE `create_time` < " . $end_time . " AND `create_time` >= " . $start_time);
                    foreach ($code_task as $key => $value) {
                        print_r($value);
                        die;
                        $send_length = mb_strlen($value['task_content'], 'utf8');
                        $num         = 1;
                        if ($send_length > 70) {
                            $num = ceil($send_length / 67);
                        }
                        //计算成功 失败
                        $day = date('Ymd', $value['create_time']);
                        if (!array_key_exists($day, $day_users)) {
                            $day_users[$day] = [];
                        }
                        $task_log = Db::query("SELECT * FROM yx_user_send_task_log WHERE `task_no` = '" . $value['task_no'] . "' ");
                        // print_r($task_log);die;
                        $allnum       = count(explode(',', $value['mobile_content']));
                        $charging_num = $allnum * $num;
                        if (empty($task_log)) { //失败
                            $success_num = 0;
                            $default_num = $value['real_num'];
                            $unknown_num = 0;
                        } else {

                            $success_mobile_num = 0;
                            $default_mobile_num = 0;
                            $unknown_mobile_num = 0;
                            $success_mobile_num = count(Db::query("SELECT `task_id`,`mobile`,`status_message` FROM yx_send_task_receipt WHERE task_id = '" . $value['id'] . "' AND `mobile` IN (" . $value['mobile_content'] . ") AND `status_message` IN (" . $Received_status . ") GROUP BY `task_id`,`mobile`,`status_message` "));

                            if ($success_mobile_num < $allnum) {
                                $default_mobile_num = count(Db::query("SELECT `task_id`,`mobile`,`status_message` FROM yx_send_task_receipt WHERE task_id = '" . $value['id'] . "' AND `mobile` IN (" . $value['mobile_content'] . ") AND `status_message` NOT IN (" . $Received_status . ") GROUP BY `task_id`,`mobile`,`status_message` "));
                                // if () {}
                                $unknown_mobile_num = $allnum - $success_mobile_num - $default_mobile_num;
                            }
                            $success_num = $success_mobile_num * $num;
                            $default_num = $default_mobile_num * $num;
                            $unknown_num = $unknown_mobile_num * $num;

                            /*   print_r($success_num);
                            print_r($default_num);
                            print_r($unknown_num);
                            die; */
                            // echo "SELECT COUNT(`task_id`,`mobile`,`status_message`) FROM yx_send_code_task_receipt WHERE task_id = '".$value['id']."' AND `mobile` IN (".$value['mobile_content'].") AND `status_message` IN (".$Received_status.") GROUP BY `task_id`,`mobile`,`status_message`" ;die;
                        }

                        if (in_array($value['uid'], $day_users[$day])) {
                            $day_businessSettlement[$day][$value['uid']]['num'] += $charging_num;
                            $day_businessSettlement[$day][$value['uid']]['mobile_num'] += $allnum;
                            if (isset($day_businessSettlement[$day][$value['uid']]['success'])) {
                                $day_businessSettlement[$day][$value['uid']]['success'] += $success_num;
                            } else {
                                $day_businessSettlement[$day][$value['uid']]['success'] = $success_num;
                            }
                            if (isset($day_businessSettlement[$day][$value['uid']]['unknown'])) {
                                $day_businessSettlement[$day][$value['uid']]['unknown'] += $unknown_num;
                            } else {
                                $day_businessSettlement[$day][$value['uid']]['unknown'] = $unknown_num;
                            }
                            if (isset($day_businessSettlement[$day][$value['uid']]['default'])) {
                                $day_businessSettlement[$day][$value['uid']]['default'] += $default_num;
                            } else {
                                $day_businessSettlement[$day][$value['uid']]['default'] = $default_num;
                            }
                            /* if ($value['status_message'] == 'DELIVRD') {

                        } elseif (empty($value['status_message'])) {

                        } else {

                        // $day_businessSettlement[$day][$value['uid']]['default'] = $num;
                        } */
                        } else {
                            $day_users[$day][]                                         = $value['uid'];
                            $day_businessSettlement[$day][$value['uid']]['num']        = $charging_num;
                            $day_businessSettlement[$day][$value['uid']]['mobile_num'] = $allnum;
                            $day_businessSettlement[$day][$value['uid']]['success']    = $success_num;
                            $day_businessSettlement[$day][$value['uid']]['unknown']    = $unknown_num;
                            $day_businessSettlement[$day][$value['uid']]['default']    = $default_num;
                            /*  if ($value['status_message'] == 'DELIVRD') {

                        } elseif ($value['status_message'] == '') {

                        } else {

                        } */
                        }
                    }
                    Db::startTrans();
                    try {
                        foreach ($day_businessSettlement as $dkey => $d_value) {
                            foreach ($d_value as $key => $value) {
                                $success = isset($value['success']) ? $value['success'] : 0;
                                $num     = isset($value['num']) ? $value['num'] : 0;
                                if ($key == 47 && $dkey == 20200122) {
                                    $num = $num + 5784;
                                }
                                if ($key == 47 && $dkey == 20200125) {
                                    $num = $num + 289;
                                }
                                $day_user_settlement = [];
                                $day_user_settlement = [
                                    'timekey'     => $dkey,
                                    'uid'         => $key,
                                    'success'     => $success,
                                    'unknown'     => isset($value['unknown']) ? $value['unknown'] : 0,
                                    'default'     => isset($value['default']) ? $value['default'] : 0,
                                    'num'         => $num,
                                    'ratio'       => $success / $num * 100,
                                    'mobile_num'  => $value['mobile_num'],
                                    'business_id' => '5',
                                    'create_time' => time(),
                                    'update_time' => time(),
                                ];
                                $has = Db::query('SELECT * FROM `yx_statistics_day` WHERE `business_id` = 5 AND `timekey` = ' . $dkey . ' AND `uid` = ' . $key);
                                if ($has) {
                                    Db::table('yx_statistics_day')->where('id', $has[0]['id'])->update([
                                        'success'     => $success,
                                        'unknown'     => isset($value['unknown']) ? $value['unknown'] : 0,
                                        'default'     => isset($value['default']) ? $value['default'] : 0,
                                        'num'         => $num,
                                        'mobile_num'  => $value['mobile_num'],
                                        'ratio'       => $success / $num * 100,
                                        'update_time' => time(),
                                    ]);
                                } else {
                                    Db::table('yx_statistics_day')->insert($day_user_settlement);
                                }
                            }
                        }
                        Db::commit();
                    } catch (\Exception $e) {
                        Db::rollback();
                        exception($e);
                    }
                    break;
                } else {
                    $day_businessSettlement = [];
                    $day_users              = [];
                    $code_task              = [];
                    $code_task              = Db::query("SELECT * FROM yx_user_send_task WHERE `create_time` < " . $end_time . " AND `create_time` >= " . $start_time);
                    foreach ($code_task as $key => $value) {

                        $send_length = mb_strlen($value['task_content'], 'utf8');
                        $num         = 1;
                        if ($send_length > 70) {
                            $num = ceil($send_length / 67);
                        }
                        //计算成功 失败
                        $day = date('Ymd', $value['create_time']);
                        if (!array_key_exists($day, $day_users)) {
                            $day_users[$day] = [];
                        }
                        $task_log = Db::query("SELECT * FROM yx_user_send_task_log WHERE `task_no` = '" . $value['task_no'] . "' ");
                        // print_r($task_log);die;
                        $allnum       = count(explode(',', $value['mobile_content']));
                        $charging_num = $allnum * $num;
                        if (empty($task_log)) { //失败
                            $success_num = 0;
                            $default_num = $value['real_num'];
                            $unknown_num = 0;
                        } else {

                            $success_mobile_num = 0;
                            $default_mobile_num = 0;
                            $unknown_mobile_num = 0;
                            $success_mobile_num = count(Db::query("SELECT `task_id`,`mobile`,`status_message` FROM yx_send_task_receipt WHERE task_id = '" . $value['id'] . "' AND `mobile` IN (" . $value['mobile_content'] . ") AND `status_message` IN (" . $Received_status . ") GROUP BY `task_id`,`mobile`,`status_message` "));

                            if ($success_mobile_num < $allnum) {
                                $default_mobile_num = count(Db::query("SELECT `task_id`,`mobile`,`status_message` FROM yx_send_task_receipt WHERE task_id = '" . $value['id'] . "' AND `mobile` IN (" . $value['mobile_content'] . ") AND `status_message` NOT IN (" . $Received_status . ") GROUP BY `task_id`,`mobile`,`status_message` "));
                                // if () {}
                                $unknown_mobile_num = $allnum - $success_mobile_num - $default_mobile_num;
                            }
                            $success_num = $success_mobile_num * $num;
                            $default_num = $default_mobile_num * $num;
                            $unknown_num = $unknown_mobile_num * $num;

                            /*   print_r($success_num);
                            print_r($default_num);
                            print_r($unknown_num);
                            die; */
                            // echo "SELECT COUNT(`task_id`,`mobile`,`status_message`) FROM yx_send_code_task_receipt WHERE task_id = '".$value['id']."' AND `mobile` IN (".$value['mobile_content'].") AND `status_message` IN (".$Received_status.") GROUP BY `task_id`,`mobile`,`status_message`" ;die;
                        }

                        if (in_array($value['uid'], $day_users[$day])) {
                            $day_businessSettlement[$day][$value['uid']]['num'] += $charging_num;
                            $day_businessSettlement[$day][$value['uid']]['mobile_num'] += $allnum;
                            if (isset($day_businessSettlement[$day][$value['uid']]['success'])) {
                                $day_businessSettlement[$day][$value['uid']]['success'] += $success_num;
                            } else {
                                $day_businessSettlement[$day][$value['uid']]['success'] = $success_num;
                            }
                            if (isset($day_businessSettlement[$day][$value['uid']]['unknown'])) {
                                $day_businessSettlement[$day][$value['uid']]['unknown'] += $unknown_num;
                            } else {
                                $day_businessSettlement[$day][$value['uid']]['unknown'] = $unknown_num;
                            }
                            if (isset($day_businessSettlement[$day][$value['uid']]['default'])) {
                                $day_businessSettlement[$day][$value['uid']]['default'] += $default_num;
                            } else {
                                $day_businessSettlement[$day][$value['uid']]['default'] = $default_num;
                            }
                            /* if ($value['status_message'] == 'DELIVRD') {

                        } elseif (empty($value['status_message'])) {

                        } else {

                        // $day_businessSettlement[$day][$value['uid']]['default'] = $num;
                        } */
                        } else {
                            $day_users[$day][]                                         = $value['uid'];
                            $day_businessSettlement[$day][$value['uid']]['num']        = $charging_num;
                            $day_businessSettlement[$day][$value['uid']]['mobile_num'] = $allnum;
                            $day_businessSettlement[$day][$value['uid']]['success']    = $success_num;
                            $day_businessSettlement[$day][$value['uid']]['unknown']    = $unknown_num;
                            $day_businessSettlement[$day][$value['uid']]['default']    = $default_num;
                            /*  if ($value['status_message'] == 'DELIVRD') {

                        } elseif ($value['status_message'] == '') {

                        } else {

                        } */
                        }
                    }
                    // print_r($day_businessSettlement);
                    Db::startTrans();
                    try {
                        foreach ($day_businessSettlement as $dkey => $d_value) {
                            foreach ($d_value as $key => $value) {
                                $success = isset($value['success']) ? $value['success'] : 0;
                                $num     = isset($value['num']) ? $value['num'] : 0;
                                if ($key == 47 && $dkey == 20200122) {
                                    $num = $num + 5784;
                                }
                                if ($key == 47 && $dkey == 20200125) {
                                    $num = $num + 289;
                                }
                                $day_user_settlement = [];
                                $day_user_settlement = [
                                    'timekey'     => $dkey,
                                    'uid'         => $key,
                                    'success'     => $success,
                                    'unknown'     => isset($value['unknown']) ? $value['unknown'] : 0,
                                    'default'     => isset($value['default']) ? $value['default'] : 0,
                                    'num'         => $num,
                                    'ratio'       => $success / $num * 100,
                                    'mobile_num'  => $value['mobile_num'],
                                    'business_id' => '5',
                                    'create_time' => time(),
                                    'update_time' => time(),
                                ];
                                $has = Db::query('SELECT * FROM `yx_statistics_day` WHERE `business_id` = 5 AND `timekey` = ' . $dkey . ' AND `uid` = ' . $key);
                                if ($has) {
                                    Db::table('yx_statistics_day')->where('id', $has[0]['id'])->update([
                                        'success'     => $success,
                                        'unknown'     => isset($value['unknown']) ? $value['unknown'] : 0,
                                        'default'     => isset($value['default']) ? $value['default'] : 0,
                                        'num'         => $num,
                                        'mobile_num'  => $value['mobile_num'],
                                        'ratio'       => $success / $num * 100,
                                        'update_time' => time(),
                                    ]);
                                } else {
                                    Db::table('yx_statistics_day')->insert($day_user_settlement);
                                }
                            }
                        }
                        Db::commit();
                    } catch (\Exception $e) {
                        Db::rollback();
                        exception($e);
                    }
                }

                // print_r($Received_status);die;

            }
            sleep(900);
        }
    }

    private function getSendTask($id)
    {
        $getSendTaskSql = sprintf("select * from yx_user_send_task where delete_time=0 and id = %d", $id);
        $sendTask       = Db::query($getSendTaskSql);
        // print_r($sendTask);die;
        if (!$sendTask) {
            return [];
        }
        return $sendTask[0];
    }

    public function deDuctTest($id)
    {

        $time = microtime(true);
        //结果：1541053888.5911
        //在经过处理得到最终结果:
        $lastTime = (int) ($time * 1000);
        echo $lastTime;
        echo "\n";
        $this->redis   = Phpredis::getConn();
        $id            = 15939;
        $sendTask      = $this->getSendTask($id);
        $mobile_result = $this->mobilesFiltrate($sendTask['mobile_content'], $sendTask['uid'], 10);

        $time = microtime(true);
        //结果：1541053888.5911
        //在经过处理得到最终结果:
        $lastTime = (int) ($time * 1000);
        echo $lastTime;
        echo "\n";
        // print_r($mobile_result);
        die;
    }

    /*号码清洗 */
    public function mobilesFiltrate($mobile, $uid, $deduct)
    {
        $mobileredis = PhpredisNew::getConn();
        $this->redis = Phpredis::getConn();
        try {
            // $deduct = 0;
            $error_mobile     = []; //错号或者黑名单
            $real_send_mobile = []; //实际发送号码
            $deduct_mobile    = []; //扣量号码
            $true_mobile      = []; //实号号码
            $yidong_mobile    = []; //移动分区号码
            $liantong_mobile  = []; //联通分区号码
            $dianxin_mobile   = []; //电信分区号码
            $host_city_mobile = []; //省会城市号码包含深圳
            $cool_city_mobile = []; //二线城市号码
            $mobile           = str_replace('&quot;', '', $mobile);
            $mobile_data      = explode(',', $mobile);
            /* 10个号码之内不扣 */
            if (count($mobile_data) < 10) {
                $deduct = 0;
            }
            foreach ($mobile_data as $key => $value) {
                // print_r($value);die;
                if (!is_numeric($value)) {
                    unset($mobile_data[$key]);
                    continue;
                }
                if (checkMobile($value) == false) {

                    $error_mobile[] = $value;
                }
            }
            $mobile = join(',', $mobile_data);
            //白名单
            $white_mobiles = [];
            $white_mobile  = Db::query("SELECT `mobile` FROM `yx_whitelist` WHERE mobile IN (" . $mobile . ") GROUP BY `mobile` ");
            // print_r("SELECT `mobile` FROM `yx_whitelist` WHERE mobile IN (".$mobile.") ");
            if (!empty($white_mobile)) {
                foreach ($white_mobile as $key => $value) {
                    $white_mobiles[] = $value['mobile'];
                }
            }
            //黑名单
            $black_mobile = Db::query("SELECT `mobile` FROM `yx_blacklist` WHERE mobile IN (" . $mobile . ") GROUP BY `mobile` ");
            // print_r("SELECT `mobile` FROM `yx_whitelist` WHERE mobile IN (".$mobile.") ");
            if (!empty($black_mobile)) {
                foreach ($black_mobile as $key => $value) {
                    $error_mobile[] = $value['mobile'];
                }
            }
            //白名单发送
            foreach ($white_mobiles as $key => $value) {
                $prefix = substr(trim($value), 0, 7);

                // $res    = Db::query("SELECT `source`,`province_id`,`province` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                // $newres = array_shift($res);
                $newres = $this->redis->hget('index:mobile:source', $prefix);
                $newres = json_decode($newres, true);
                if ($newres) {
                    if ($newres['source'] == 1) { //移动
                        // $channel_id = $yidong_channel_id;
                        $yidong_mobile[] = $value;
                    } elseif ($newres['source'] == 2) { //联通
                        // $channel_id = $liantong_channel_id;
                        $liantong_mobile[] = $value;
                    } elseif ($newres['source'] == 3) { //电信
                        // $channel_id = $dianxin_channel_id;
                        $dianxin_mobile[] = $value;
                    }
                } else {
                    $yidong_mobile[] = $value;
                }
            }
            $real_send_mobile = array_diff($mobile_data, $error_mobile);
            $real_send_mobile = array_diff($real_send_mobile, $white_mobiles);

            // print_r($real_send_mobile);die;
            if (count($real_send_mobile) == 1) {

                $num = mt_rand(0, 100);
                if ($uid == 91) {
                    if ($num <= $deduct && !empty($real_send_mobile)) {
                        foreach ($real_send_mobile as $key => $value) {
                            $deduct_mobile[] = $value;
                        }
                    } else {
                        if (!empty($real_send_mobile)) {
                            foreach ($real_send_mobile as $key => $value) {
                                $prefix = substr(trim($value), 0, 7);

                                // $res    = Db::query("SELECT `source`,`province_id`,`province` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                                // $newres = array_shift($res);
                                $newres = $this->redis->hget('index:mobile:source', $prefix);
                                $newres = json_decode($newres, true);
                                if ($newres) {
                                    if ($newres['source'] == 1) { //移动
                                        // $channel_id = $yidong_channel_id;
                                        $yidong_mobile[] = $value;
                                    } elseif ($newres['source'] == 2) { //联通
                                        // $channel_id = $liantong_channel_id;
                                        $liantong_mobile[] = $value;
                                    } elseif ($newres['source'] == 3) { //电信
                                        // $channel_id = $dianxin_channel_id;
                                        $dianxin_mobile[] = $value;
                                    }
                                } else {
                                    $yidong_mobile[] = $value;
                                }
                            }
                        }
                    }
                } else {
                    if (!empty($real_send_mobile)) {
                        foreach ($real_send_mobile as $key => $value) {
                            $prefix = substr(trim($value), 0, 7);

                            // $res    = Db::query("SELECT `source`,`province_id`,`province` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                            // $newres = array_shift($res);
                            $newres = $this->redis->hget('index:mobile:source', $prefix);
                            $newres = json_decode($newres, true);
                            if ($newres) {
                                if ($newres['source'] == 1) { //移动
                                    // $channel_id = $yidong_channel_id;
                                    $yidong_mobile[] = $value;
                                } elseif ($newres['source'] == 2) { //联通
                                    // $channel_id = $liantong_channel_id;
                                    $liantong_mobile[] = $value;
                                } elseif ($newres['source'] == 3) { //电信
                                    // $channel_id = $dianxin_channel_id;
                                    $dianxin_mobile[] = $value;
                                }
                            } else {
                                $yidong_mobile[] = $value;
                            }
                        }
                    }
                }
                return ['error_mobile' => $error_mobile, 'yidong_mobile' => $yidong_mobile, 'liantong_mobile' => $liantong_mobile, 'dianxin_mobile' => $dianxin_mobile, 'deduct_mobile' => $deduct_mobile];
            } else {

                //去除黑名单后实际有效号码
                // echo count($real_send_mobile);die;
                // print_r($real_send_mobile);die;
                //扣量
                $the_month      = date('Ymd', time());
                $the_month_time = strtotime($the_month - 1);
                if ($deduct > 0 && count($real_send_mobile) > 0) {
                    //热门城市ID
                    $citys_id = [2, 20, 38, 241, 378, 500, 615, 694, 842, 860, 981, 1083, 1220, 1315, 1427, 1602, 1803, 1923, 2077, 2279, 2405, 2455, 2496, 2704, 2802, 2948, 3034, 3152, 3255, 3310, 3338, 2100];

                    $remaining_mobile  = $real_send_mobile;
                    $entity_mobiles    = []; //实号即能扣量号码
                    $need_check_mobile = []; //需要检测号码
                    foreach ($remaining_mobile as $key => $value) {
                        //判断是否为实号

                        $vacant = $mobileredis->hget("yx:mobile:real", $value); //实号
                        // print_r($vacant);die;
                        if (!empty($vacant)) {
                            $vacant = json_decode($vacant, true);
                            //判断检测时间在本月或者上月检测过，则不再检测
                            // print_r($vacant);die;
                            if (isset($vacant['update_time']) && $vacant['update_time'] >= $the_month_time) { //无效检测号码
                                $entity_mobiles[] = $value;
                                $mobile_info      = [];
                                $mobile_info      = [
                                    'mobile' => $value,
                                    'source' => $vacant['source'],
                                ];
                                if (isset($vacant['city_id']) && in_array($vacant['city_id'], $citys_id)) {
                                    //热门城市号码

                                    $host_city_mobile[] = $mobile_info;
                                } else {
                                    //冷门城市号码
                                    $cool_city_mobile[] = $mobile_info;
                                }
                            } else { //需要检测号码
                                $need_check_mobile[] = $value;
                            }
                        } else {
                            $entity = $mobileredis->hget("yx:mobile:empty", $value); //空号
                            $entity = json_decode($entity, true);
                            if (!empty($entity)) {
                                if (isset($vacant['update_time']) && $vacant['update_time'] >= $the_month_time) { //空号
                                    // $entity_mobiles[] = $value;
                                    //空号直接放入发送队列
                                    $prefix = substr(trim($value), 0, 7);
                                    // $res    = Db::query("SELECT `source`,`province_id`,`city_id` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                                    // $newres = array_shift($res);
                                    $newres = $this->redis->hget('index:mobile:source', $prefix);
                                    $newres = json_decode($newres, true);
                                    if ($newres) {
                                        if ($newres['source'] == 1) { //移动
                                            // $channel_id = $yidong_channel_id;
                                            $yidong_mobile[] = $value;
                                        } elseif ($newres['source'] == 2) { //联通
                                            // $channel_id = $liantong_channel_id;
                                            $liantong_mobile[] = $value;
                                        } elseif ($newres['source'] == 3) { //电信
                                            // $channel_id = $dianxin_channel_id;
                                            $dianxin_mobile[] = $value;
                                        }
                                    } else {
                                        $yidong_mobile[] = $value;
                                    }
                                } else { //需要检测号码
                                    $need_check_mobile[] = $value;
                                }
                            } else {
                                $need_check_mobile[] = $value;
                            }
                        }

                        // echo "实号";
                        // print_r($vacant);die;
                    }
                    /* echo count($error_mobile) + count($yidong_mobile)+ count($liantong_mobile)+ count($dianxin_mobile)+ count($entity_mobiles)+ count($need_check_mobile);
                    die; */
                    $check_result = [];
                    if (!empty($need_check_mobile)) {
                        $check_result = $this->secondCheckMobileApi($need_check_mobile);
                        // print_r($check_result);
                        // die;
                        // ['real_mobile' => $real_mobile, 'empty_mobile' => $empty_mobile]
                        $check_empty_mobile = [];
                        $check_empty_mobile = $check_result['empty_mobile']; //检测出来的空号
                        $check_real_mobile  = [];
                        $check_real_mobile  = $check_result['real_mobile']; //检测出来的实号
                        if (!empty($check_empty_mobile)) {
                            foreach ($check_empty_mobile as $key => $value) {
                                //划分运营商
                                $prefix = substr(trim($value), 0, 7);
                                // $res    = Db::query("SELECT `source`,`province_id`,`city_id` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                                // $newres = array_shift($res);
                                $newres = $this->redis->hget('index:mobile:source', $prefix);
                                $newres = json_decode($newres, true);
                                if ($newres) {
                                    if ($newres['source'] == 1) { //移动
                                        // $channel_id = $yidong_channel_id;
                                        $yidong_mobile[] = $value;
                                    } elseif ($newres['source'] == 2) { //联通
                                        // $channel_id = $liantong_channel_id;
                                        $liantong_mobile[] = $value;
                                    } elseif ($newres['source'] == 3) { //电信
                                        // $channel_id = $dianxin_channel_id;
                                        $dianxin_mobile[] = $value;
                                    }
                                } else {
                                    $yidong_mobile[] = $value;
                                }
                            }
                        }
                        if (!empty($check_real_mobile)) {
                            //区分热门和冷门
                            foreach ($check_real_mobile as $key => $value) {
                                $prefix = substr(trim($value), 0, 7);
                                // $res    = Db::query("SELECT `source`,`province_id`,`city_id` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                                // $newres = array_shift($res);
                                $newres = $this->redis->hget('index:mobile:source', $prefix);
                                $newres = json_decode($newres, true);

                                $mobile_info = [];
                                $mobile_info = [
                                    'mobile' => $value,
                                    'source' => $newres['source'],
                                ];
                                if (in_array($newres['city_id'], $citys_id)) {
                                    //热门城市号码
                                    $host_city_mobile[] = $mobile_info;
                                } else {
                                    //冷门城市号码
                                    $cool_city_mobile[] = $mobile_info;
                                }
                            }
                        }
                    }

                    $proportion = bcdiv(count($cool_city_mobile), count($real_send_mobile), 2);
                    // print_r($proportion); die;
                    if ($proportion * 100 > $deduct) {
                        //扣除部分
                        $section      = $proportion * 100;
                        $section_data = [];
                        $j            = 1;
                        for ($i = 0; $i < count($cool_city_mobile); $i++) {
                            $section_data[] = $cool_city_mobile[$i];
                            $j++;
                            if ($j > $section) {
                                $deduct_key = array_rand($section_data, $deduct);
                                foreach ($section_data as $key => $value) {
                                    if (in_array($key, $deduct_key)) {
                                        $deduct_mobile[] = $value['mobile'];
                                    } else {
                                        if ($value['source'] == 1) { //移动
                                            // $channel_id = $yidong_channel_id;
                                            $yidong_mobile[] = $value['mobile'];
                                        } elseif ($value['source'] == 2) { //联通
                                            // $channel_id = $liantong_channel_id;
                                            $liantong_mobile[] = $value['mobile'];
                                        } elseif ($value['source'] == 3) { //电信
                                            // $channel_id = $dianxin_channel_id;
                                            $dianxin_mobile[] = $value['mobile'];
                                        } else {
                                            $yidong_mobile[] = $value['mobile'];
                                        }
                                    }
                                }
                                $section_data = [];
                                $j            = 1;
                            }
                        }
                        if (!empty($section_data)) {
                            // print_r($section_data);die;
                            $deduct_key = array_rand($section_data, ceil($deduct / $section));
                            // print_r($deduct_key);die;

                            foreach ($section_data as $key => $value) {
                                if (is_array($deduct_key)) {
                                    if (in_array($key, $deduct_key)) {
                                        $deduct_mobile[] = $value['mobile'];
                                    } else {
                                        if ($value['source'] == 1) { //移动
                                            // $channel_id = $yidong_channel_id;
                                            $yidong_mobile[] = $value['mobile'];
                                        } elseif ($value['source'] == 2) { //联通
                                            // $channel_id = $liantong_channel_id;
                                            $liantong_mobile[] = $value['mobile'];
                                        } elseif ($value['source'] == 3) { //电信
                                            // $channel_id = $dianxin_channel_id;
                                            $dianxin_mobile[] = $value['mobile'];
                                        } else {
                                            $yidong_mobile[] = $value['mobile'];
                                        }
                                    }
                                } else {
                                    if ($key == $deduct_key) {
                                        $deduct_mobile[] = $value['mobile'];
                                    } else {
                                        if ($value['source'] == 1) { //移动
                                            // $channel_id = $yidong_channel_id;
                                            $yidong_mobile[] = $value['mobile'];
                                        } elseif ($value['source'] == 2) { //联通
                                            // $channel_id = $liantong_channel_id;
                                            $liantong_mobile[] = $value['mobile'];
                                        } elseif ($value['source'] == 3) { //电信
                                            // $channel_id = $dianxin_channel_id;
                                            $dianxin_mobile[] = $value['mobile'];
                                        } else {
                                            $yidong_mobile[] = $value['mobile'];
                                        }
                                    }
                                }
                            }
                        }

                        //不扣部分
                        foreach ($host_city_mobile as $key => $value) {
                            if ($value['source'] == 1) { //移动
                                // $channel_id = $yidong_channel_id;
                                $yidong_mobile[] = $value['mobile'];
                            } elseif ($value['source'] == 2) { //联通
                                // $channel_id = $liantong_channel_id;
                                $liantong_mobile[] = $value['mobile'];
                            } elseif ($value['source'] == 3) { //电信
                                // $channel_id = $dianxin_channel_id;
                                $dianxin_mobile[] = $value['mobile'];
                            } else {
                                $yidong_mobile[] = $value['mobile'];
                            }
                        }
                    } elseif ($proportion * 100 == $deduct) {
                        foreach ($cool_city_mobile as $key => $value) {
                            $deduct_mobile[] = $value['mobile'];
                        }
                        foreach ($host_city_mobile as $key => $value) {
                            if ($value['source'] == 1) { //移动
                                // $channel_id = $yidong_channel_id;
                                $yidong_mobile[] = $value['mobile'];
                            } elseif ($value['source'] == 2) { //联通
                                // $channel_id = $liantong_channel_id;
                                $liantong_mobile[] = $value['mobile'];
                            } elseif ($value['source'] == 3) { //电信
                                // $channel_id = $dianxin_channel_id;
                                $dianxin_mobile[] = $value['mobile'];
                            } else {
                                $yidong_mobile[] = $value['mobile'];
                            }
                        }
                    } else {
                        foreach ($cool_city_mobile as $key => $value) {
                            $deduct_mobile[] = $value['mobile'];
                        }
                        $host_proportion = $deduct - $proportion * 100;
                        // print_r($host_proportion);die;
                        $section      = 100;
                        $section_data = [];
                        $j            = 1;
                        for ($i = 0; $i < count($host_city_mobile); $i++) {
                            $section_data[] = $host_city_mobile[$i];
                            $j++;
                            if ($j > $section) {
                                $deduct_key = array_rand($section_data, $host_proportion);

                                foreach ($section_data as $key => $value) {
                                    if (in_array($key, $deduct_key)) {
                                        $deduct_mobile[] = $value['mobile'];
                                    } else {
                                        if ($value['source'] == 1) { //移动
                                            // $channel_id = $yidong_channel_id;
                                            $yidong_mobile[] = $value['mobile'];
                                        } elseif ($value['source'] == 2) { //联通
                                            // $channel_id = $liantong_channel_id;
                                            $liantong_mobile[] = $value['mobile'];
                                        } elseif ($value['source'] == 3) { //电信
                                            // $channel_id = $dianxin_channel_id;
                                            $dianxin_mobile[] = $value['mobile'];
                                        } else {
                                            $yidong_mobile[] = $value['mobile'];
                                        }
                                    }
                                }
                                $section_data = [];
                                $j            = 1;
                            }
                        }

                        if (!empty($section_data)) {
                            // print_r($section_data);die;
                            $deduct_key = array_rand($section_data, ceil($host_proportion / $section));
                            // print_r($deduct_key);die;
                            foreach ($section_data as $key => $value) {
                                if (is_array($deduct_key)) {
                                    if (in_array($key, $deduct_key)) {
                                        $deduct_mobile[] = $value['mobile'];
                                    } else {
                                        if ($value['source'] == 1) { //移动
                                            // $channel_id = $yidong_channel_id;
                                            $yidong_mobile[] = $value['mobile'];
                                        } elseif ($value['source'] == 2) { //联通
                                            // $channel_id = $liantong_channel_id;
                                            $liantong_mobile[] = $value['mobile'];
                                        } elseif ($value['source'] == 3) { //电信
                                            // $channel_id = $dianxin_channel_id;
                                            $dianxin_mobile[] = $value['mobile'];
                                        } else {
                                            $yidong_mobile[] = $value['mobile'];
                                        }
                                    }
                                } else {
                                    if ($key == $deduct_key) {
                                        $deduct_mobile[] = $value['mobile'];
                                    } else {
                                        if ($value['source'] == 1) { //移动
                                            // $channel_id = $yidong_channel_id;
                                            $yidong_mobile[] = $value['mobile'];
                                        } elseif ($value['source'] == 2) { //联通
                                            // $channel_id = $liantong_channel_id;
                                            $liantong_mobile[] = $value['mobile'];
                                        } elseif ($value['source'] == 3) { //电信
                                            // $channel_id = $dianxin_channel_id;
                                            $dianxin_mobile[] = $value['mobile'];
                                        } else {
                                            $yidong_mobile[] = $value['mobile'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                    // echo count($error_mobile) + count($yidong_mobile)+ count($liantong_mobile)+ count($dianxin_mobile)+ count($deduct_mobile);
                    // die;
                    return ['error_mobile' => $error_mobile, 'yidong_mobile' => $yidong_mobile, 'liantong_mobile' => $liantong_mobile, 'dianxin_mobile' => $dianxin_mobile, 'deduct_mobile' => $deduct_mobile];
                } else {
                    if (!empty($real_send_mobile)) {
                        foreach ($real_send_mobile as $key => $value) {
                            $prefix = substr(trim($value), 0, 7);
                            $res    = Db::query("SELECT `source`,`province_id`,`province` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                            $newres = array_shift($res);
                            if ($newres) {
                                if ($newres['source'] == 1) { //移动
                                    // $channel_id = $yidong_channel_id;
                                    $yidong_mobile[] = $value;
                                } elseif ($newres['source'] == 2) { //联通
                                    // $channel_id = $liantong_channel_id;
                                    $liantong_mobile[] = $value;
                                } elseif ($newres['source'] == 3) { //电信
                                    // $channel_id = $dianxin_channel_id;
                                    $dianxin_mobile[] = $value;
                                }
                            } else {
                                $yidong_mobile[] = $value;
                            }
                        }
                    }
                    return ['error_mobile' => $error_mobile, 'yidong_mobile' => $yidong_mobile, 'liantong_mobile' => $liantong_mobile, 'dianxin_mobile' => $dianxin_mobile, 'deduct_mobile' => $deduct_mobile];
                }
            }
        } catch (\Exception $th) {
            //throw $th;
            exception($th);
        }
    }

    public function secondCheckMobileApi($mobiledata = [])
    {
        $mobileredis  = PhpredisNew::getConn();
        $this->redis  = Phpredis::getConn();
        $real_mobile  = [];
        $empty_mobile = [];
        $secret_id    = '06FDC4A71F5E1FDE4C061DBA653DD2A5';
        $secret_key   = 'ef0587df-86dc-459f-ad82-41c6446b27a5';
        $api          = 'https://api.yunzhandata.com/api/deadnumber/v1.0/detect?sig=';
        $ts           = date("YmdHis", time());
        $sig          = sha1($secret_id . $secret_key . $ts);
        $api          = $api . $sig . "&sid=" . $secret_id . "&skey=" . $secret_key . "&ts=" . $ts;
        // $check_mobile = $this->decrypt('6C38881649F7003B910582D1095DA821',$secret_id);
        // print_r($check_mobile);die;
        $data              = [];
        $check_mobile_data = [];
        $j                 = 1;
        // echo count($mobiledata);die;
        foreach ($mobiledata as $key => $value) {
            $check_mobile_data[] = encrypt($value, $secret_id);
            $j++;
            if ($j > 2000) {
                $data = [
                    'mobiles' => $check_mobile_data,
                ];
                $headers = [
                    'Authorization:' . base64_encode($secret_id . ':' . $ts), 'Content-Type:application/json',
                ];
                // $result = $this->sendRequest2($api, 'post', $data, $headers);
                // print_r(json_decode($data),true);
                // print_r($data);
                //模拟请求
                foreach ($check_mobile_data as $ckey => $cvalue) {

                    if ($mobileredis->hget("yx:mobile:real", decrypt($cvalue, $secret_id))) {
                        $check_result = [];
                        $check_result = [
                            'mobileStatus' => 2,
                            'mobile'       => $cvalue,
                        ];
                    } else {
                        $check_result = [
                            'mobileStatus' => 0,
                            'mobile'       => $cvalue,
                        ];
                    }
                    $result['mobiles'][] = $check_result;
                }
                $result['code'] = 0;
                $result         = json_encode($result);

                $result = json_decode($result, true);
                if ($result['code'] == 0) { //接口请求成功
                    $mobiles = $result['mobiles'];
                    foreach ($mobiles as $mkey => $mvalue) {
                        $mobile       = decrypt($mvalue['mobile'], $secret_id);
                        $check_result = $mvalue['mobileStatus'];
                        $check_status = 2;
                        if ($check_result == 2) { //实号
                            /*  Db::table('yx_mobile')->where(['mobile' => $mobile])->delete();
                            Db::table('yx_real_mobile')->where(['mobile' => $mobile])->delete();
                            Db::table('yx_real_mobile')->insert([
                            'mobile' => $mobile,
                            'check_result' => 3,
                            'check_status' => $check_status,
                            'update_time' => time(),
                            'create_time' => time()
                            ]); */
                            $mobileredis->hdel('yx:mobile:empty', $mobile);
                            $prefix = substr(trim($mobile), 0, 7);
                            $newres = $this->redis->hget('index:mobile:source', $prefix);
                            $newres = json_decode($newres, true);
                            // {"source":1,"province_id":841,"city_id":842,"update_time":1591386721,"check_status":1,"check_result":1}
                            if (!empty($newres)) {
                                $mobileredis->hset('yx:mobile:real', $mobile, json_encode([
                                    'source'       => $newres['source'],
                                    'province_id'  => $newres['province_id'],
                                    'city_id'      => $newres['city_id'],
                                    'check_status' => 2,
                                    'check_result' => 3,
                                    'update_time'  => time(),
                                ]));
                            }
                            // return false;
                            $real_mobile[] = $mobile;
                        } else {
                            /*  Db::table('yx_real_mobile')->where(['mobile' => $mobile])->delete();
                            Db::table('yx_mobile')->where(['mobile' => $mobile])->delete();
                            Db::table('yx_mobile')->insert([
                            'mobile' => $mobile,
                            'check_result' => $check_result,
                            'check_status' => $check_status,
                            'update_time' => time(),
                            'create_time' => time()
                            ]); */
                            $mobileredis->hdel('yx:mobile:real', $mobile);
                            $prefix = substr(trim($mobile), 0, 7);
                            $newres = $this->redis->hget('index:mobile:source', $prefix);
                            $newres = json_decode($newres, true);
                            // {"source":1,"province_id":841,"city_id":842,"update_time":1591386721,"check_status":1,"check_result":1}
                            if (!empty($newres)) {
                                $mobileredis->hset('yx:mobile:empty', $mobile, json_encode([
                                    'source'       => $newres['source'],
                                    'province_id'  => $newres['province_id'],
                                    'city_id'      => $newres['city_id'],
                                    'check_status' => 2,
                                    'check_result' => $check_result,
                                    'update_time'  => time(),
                                ]));
                            }
                            $empty_mobile[] = $mobile;
                        }
                    }
                } else {
                    $empty_mobile = $mobiledata;
                }
                $check_mobile_data = [];
                $j                 = 1;
                $result            = [];
            }
        }
        if (!empty($check_mobile_data)) {
            $data = [
                'mobiles' => $check_mobile_data,
            ];
            $headers = [
                'Authorization:' . base64_encode($secret_id . ':' . $ts), 'Content-Type:application/json',
            ];
            // $result = $this->sendRequest2($api, 'post', $data, $headers);
            // print_r(json_decode($data),true);
            // print_r($data);
            //模拟请求
            foreach ($check_mobile_data as $ckey => $cvalue) {

                if ($mobileredis->hget("yx:mobile:real", decrypt($cvalue, $secret_id))) {
                    $check_result = [];
                    $check_result = [
                        'mobileStatus' => 2,
                        'mobile'       => $cvalue,
                    ];
                } else {
                    $check_result = [
                        'mobileStatus' => 0,
                        'mobile'       => $cvalue,
                    ];
                }
                $result['mobiles'][] = $check_result;
            }
            $result['code'] = 0;
            $result         = json_encode($result);
            $result         = json_decode($result, true);
            if ($result['code'] == 0) { //接口请求成功
                $mobiles = $result['mobiles'];
                foreach ($mobiles as $mkey => $mvalue) {
                    $mobile       = decrypt($mvalue['mobile'], $secret_id);
                    $check_result = $mvalue['mobileStatus'];
                    $check_status = 2;
                    if ($check_result == 2) { //实号
                        /*  Db::table('yx_mobile')->where(['mobile' => $mobile])->delete();
                        Db::table('yx_real_mobile')->where(['mobile' => $mobile])->delete();
                        Db::table('yx_real_mobile')->insert([
                        'mobile' => $mobile,
                        'check_result' => 3,
                        'check_status' => $check_status,
                        'update_time' => time(),
                        'create_time' => time()
                        ]); */
                        $mobileredis->hdel('yx:mobile:empty', $mobile);
                        $prefix = substr(trim($mobile), 0, 7);
                        $newres = $this->redis->hget('index:mobile:source', $prefix);
                        $newres = json_decode($newres, true);
                        // {"source":1,"province_id":841,"city_id":842,"update_time":1591386721,"check_status":1,"check_result":1}
                        if (!empty($newres)) {
                            $mobileredis->hset('yx:mobile:real', $mobile, json_encode([
                                'source'       => $newres['source'],
                                'province_id'  => $newres['province_id'],
                                'city_id'      => $newres['city_id'],
                                'check_status' => 2,
                                'check_result' => 3,
                                'update_time'  => time(),
                            ]));
                        }
                        // return false;
                        $real_mobile[] = $mobile;
                    } else {
                        /*  Db::table('yx_real_mobile')->where(['mobile' => $mobile])->delete();
                        Db::table('yx_mobile')->where(['mobile' => $mobile])->delete();
                        Db::table('yx_mobile')->insert([
                        'mobile' => $mobile,
                        'check_result' => $check_result,
                        'check_status' => $check_status,
                        'update_time' => time(),
                        'create_time' => time()
                        ]); */
                        $mobileredis->hdel('yx:mobile:real', $mobile);
                        $prefix = substr(trim($mobile), 0, 7);
                        $newres = $this->redis->hget('index:mobile:source', $prefix);
                        $newres = json_decode($newres, true);
                        // {"source":1,"province_id":841,"city_id":842,"update_time":1591386721,"check_status":1,"check_result":1}
                        if (!empty($newres)) {
                            $mobileredis->hset('yx:mobile:empty', $mobile, json_encode([
                                'source'       => $newres['source'],
                                'province_id'  => $newres['province_id'],
                                'city_id'      => $newres['city_id'],
                                'check_status' => 2,
                                'check_result' => $check_result,
                                'update_time'  => time(),
                            ]));
                        }
                        $empty_mobile[] = $mobile;
                    }
                }
            } else {
                $empty_mobile = $mobiledata;
            }
        }

        return ['real_mobile' => $real_mobile, 'empty_mobile' => $empty_mobile];
    }

    public function setMobile()
    {
        ini_set('memory_limit', '10240M'); // 临时设置最大内存占用为3G
        // $max_id = Db::query("SELECT `id` FROM yx_send_task_receipt ORDER BY `id` DESC limit 1 ");
        // // print_r($max_id);
        $mobileredis = PhpredisNew::getConn();
        $redis       = Phpredis::getConn();
        $mobile_data = [];
        try {
            $ALL_NUM = Db::query("SELECT `mobile`,`create_time` FROM yx_user_send_code_task_log WHERE `real_message` = 'DELIVRD' OR  `real_message` = 'DB:0141' OR `real_message` LIKE '%BLACK%' GROUP BY `mobile`,`create_time` ");
            foreach ($ALL_NUM as $key => $value) {
                $newres = $mobileredis->hget('yx:mobile:real', $value['mobile']);
                if ($newres) {
                    $newres = json_decode($newres, true);
                    if ($newres['update_time'] > $value['create_time']) {
                        continue;
                    } else {
                        $newres['update_time'] = $value['create_time'];
                        $mobileredis->hset('yx:mobile:real', $value['mobile'], json_encode($newres));
                    }
                } else {
                    $newres = $this->mobilecheckredis($value['mobile']);
                    if ($newres == false) {
                        continue;
                    }
                    $newres['update_time']  = $value['create_time'];
                    $newres['check_status'] = 1;
                    $newres['check_result'] = 1;
                    $mobileredis->hset('yx:mobile:real', $value['mobile'], json_encode($newres));
                }
            }

            $ALL_NUM = Db::query("SELECT `mobile`,`create_time` FROM yx_send_task_receipt WHERE `real_message` = 'DELIVRD' OR  `real_message` = 'DB:0141' OR `real_message` LIKE '%BLACK%' GROUP BY `mobile`,`create_time` ");
            /*  $max_num = $max_id[0]['id'];
            for ($i=0; $i < $max_num; $i++) {
            $receipts = Db::query('SELECT ');
            } */
            $i = 1;
            foreach ($ALL_NUM as $key => $value) {
                $newres = $mobileredis->hget('yx:mobile:real', $value['mobile']);
                if ($newres) {
                    $newres = json_decode($newres, true);
                    if ($newres['update_time'] > $value['create_time']) {
                        continue;
                    } else {
                        $newres['update_time'] = $value['create_time'];
                        $mobileredis->hset('yx:mobile:real', $value['mobile'], json_encode($newres));
                    }
                } else {
                    $newres = $this->mobilecheckredis($value['mobile']);
                    if ($newres == false) {
                        continue;
                    }
                    $newres['update_time']  = $value['create_time'];
                    $newres['check_status'] = 1;
                    $newres['check_result'] = 1;
                    $mobileredis->hset('yx:mobile:real', $value['mobile'], json_encode($newres));
                }
            }

            $ALL_NUM = Db::query("SELECT `mobile`,`create_time` FROM yx_send_code_task_receipt WHERE `real_message` = 'DELIVRD'  OR  `real_message` = 'DB:0141' OR `real_message` LIKE '%BLACK%' GROUP BY `mobile`,`create_time`");
            /*  $max_num = $max_id[0]['id'];
            for ($i=0; $i < $max_num; $i++) {
            $receipts = Db::query('SELECT ');
            } */
            // // echo count;
            foreach ($ALL_NUM as $key => $value) {
                $newres = $mobileredis->hget('yx:mobile:real', $value['mobile']);
                if ($newres) {
                    $newres = json_decode($newres, true);
                    if ($newres['update_time'] > $value['create_time']) {
                        continue;
                    } else {
                        $newres['update_time'] = $value['create_time'];
                        $mobileredis->hset('yx:mobile:real', $value['mobile'], json_encode($newres));
                    }
                } else {
                    $newres = $this->mobilecheckredis($value['mobile']);
                    if ($newres == false) {
                        continue;
                    }
                    $newres['update_time']  = $value['create_time'];
                    $newres['check_status'] = 1;
                    $newres['check_result'] = 1;
                    $mobileredis->hset('yx:mobile:real', $value['mobile'], json_encode($newres));
                }
            }

            $ALL_NUM = Db::query("SELECT `mobile_content`,`create_time` FROM yx_user_send_game_task WHERE `real_message` = 'DELIVRD' OR  `real_message` = 'DB:0141' OR `real_message` LIKE '%BLACK%' GROUP BY `mobile_content`,`create_time` ");
            foreach ($ALL_NUM as $key => $value) {
                $newres = $mobileredis->hget('yx:mobile:real', $value['mobile_content']);
                if ($newres) {
                    $newres = json_decode($newres, true);
                    if ($newres['update_time'] > $value['create_time']) {
                        continue;
                    } else {
                        $newres['update_time'] = $value['create_time'];
                        $mobileredis->hset('yx:mobile:real', $value['mobile_content'], json_encode($newres));
                    }
                } else {
                    $newres = $this->mobilecheckredis($value['mobile_content']);
                    if ($newres == false) {
                        continue;
                    }
                    $newres['update_time']  = $value['create_time'];
                    $newres['check_status'] = 1;
                    $newres['check_result'] = 1;
                    $mobileredis->hset('yx:mobile:real', $value['mobile_content'], json_encode($newres));
                }

                $ALL_NUM = Db::query("SELECT `mobile`,`create_time` FROM yx_user_send_task_log WHERE `real_message` = 'DELIVRD' OR  `real_message` = 'DB:0141' OR `real_message` LIKE '%BLACK%' GROUP BY `mobile`,`create_time` ");
                foreach ($ALL_NUM as $key => $value) {
                    $newres = $mobileredis->hget('yx:mobile:real', $value['mobile']);
                    if ($newres) {
                        $newres = json_decode($newres, true);
                        if ($newres['update_time'] > $value['create_time']) {
                            continue;
                        } else {
                            $newres['update_time'] = $value['create_time'];
                            $mobileredis->hset('yx:mobile:real', $value['mobile'], json_encode($newres));
                        }
                    } else {
                        $newres = $this->mobilecheckredis($value['mobile']);
                        if ($newres == false) {
                            continue;
                        }
                        $newres['update_time']  = $value['create_time'];
                        $newres['check_status'] = 1;
                        $newres['check_result'] = 1;
                        $mobileredis->hset('yx:mobile:real', $value['mobile'], json_encode($newres));
                    }
                }

                $ALL_NUM = Db::query("SELECT `mobile`,`create_time` FROM yx_user_multimedia_message_log WHERE `real_message` = 'DELIVRD' OR  `real_message` = 'DB:0141' OR `real_message` LIKE '%BLACK%' GROUP BY `mobile`,`create_time` ");
                foreach ($ALL_NUM as $key => $value) {
                    $newres = $mobileredis->hget('yx:mobile:real', $value['mobile']);
                    if ($newres) {
                        $newres = json_decode($newres, true);
                        if ($newres['update_time'] > $value['create_time']) {
                            continue;
                        } else {
                            $newres['update_time'] = $value['create_time'];
                            $mobileredis->hset('yx:mobile:real', $value['mobile'], json_encode($newres));
                        }
                    } else {
                        $newres = $this->mobilecheckredis($value['mobile']);
                        if ($newres == false) {
                            continue;
                        }
                        $newres['update_time']  = $value['create_time'];
                        $newres['check_status'] = 1;
                        $newres['check_result'] = 1;
                        $mobileredis->hset('yx:mobile:real', $value['mobile'], json_encode($newres));
                    }
                }

                $mysql_connect = Db::connect(Config::get('database.db_sflsftp'));
                $mysql_connect->query("set names utf8mb4");
                $ALL_NUM = $mysql_connect->query("SELECT `mobile`,`create_time` FROM yx_sfl_send_task_receipt2 WHERE `real_message` = 'DELIVRD' OR  `real_message` = 'DB:0141' OR `real_message` LIKE '%BLACK%' GROUP BY `mobile`,`create_time` ");
                foreach ($ALL_NUM as $key => $value) {
                    $newres = $mobileredis->hget('yx:mobile:real', $value['mobile']);
                    if ($newres) {
                        $newres = json_decode($newres, true);
                        if ($newres['update_time'] > $value['create_time']) {
                            continue;
                        } else {
                            $newres['update_time'] = $value['create_time'];
                            $mobileredis->hset('yx:mobile:real', $value['mobile'], json_encode($newres));
                        }
                    } else {
                        $newres = $this->mobilecheckredis($value['mobile']);
                        if ($newres == false) {
                            continue;
                        }
                        $newres['update_time']  = $value['create_time'];
                        $newres['check_status'] = 1;
                        $newres['check_result'] = 1;
                        $mobileredis->hset('yx:mobile:real', $value['mobile'], json_encode($newres));
                    }
                }

                $ALL_NUM = $mysql_connect->query("SELECT `mobile`,`create_time` FROM yx_sfl_send_task_receipt WHERE `real_message` = 'DELIVRD' OR  `real_message` = 'DB:0141' OR `real_message` LIKE '%BLACK%' GROUP BY `mobile`,`create_time` ");
                foreach ($ALL_NUM as $key => $value) {
                    $newres = $mobileredis->hget('yx:mobile:real', $value['mobile']);
                    if ($newres) {
                        $newres = json_decode($newres, true);
                        if ($newres['update_time'] > $value['create_time']) {
                            continue;
                        } else {
                            $newres['update_time'] = $value['create_time'];
                            $mobileredis->hset('yx:mobile:real', $value['mobile'], json_encode($newres));
                        }
                    } else {
                        $newres = $this->mobilecheckredis($value['mobile']);
                        if ($newres == false) {
                            continue;
                        }
                        $newres['update_time']  = $value['create_time'];
                        $newres['check_status'] = 1;
                        $newres['check_result'] = 1;
                        $mobileredis->hset('yx:mobile:real', $value['mobile'], json_encode($newres));
                    }
                }

                $mobile_data = [];
                $ALL_NUM     = $mysql_connect->query("SELECT `mobile`,`create_time` FROM yx_sfl_send_multimediatask_receipt WHERE `real_message` = 'DELIVRD' OR  `real_message` = 'DB:0141' OR `real_message` LIKE '%BLACK%' GROUP BY `mobile`,`create_time` ");
                foreach ($ALL_NUM as $key => $value) {
                    $newres = $mobileredis->hget('yx:mobile:real', $value['mobile']);
                    if ($newres) {
                        $newres = json_decode($newres, true);
                        if ($newres['update_time'] > $value['create_time']) {
                            continue;
                        } else {
                            $newres['update_time'] = $value['create_time'];
                            $mobileredis->hset('yx:mobile:real', $value['mobile'], json_encode($newres));
                        }
                    } else {
                        $newres = $this->mobilecheckredis($value['mobile']);
                        if ($newres == false) {
                            continue;
                        }
                        $newres['update_time']  = $value['create_time'];
                        $newres['check_status'] = 1;
                        $newres['check_result'] = 1;
                        $mobileredis->hset('yx:mobile:real', $value['mobile'], json_encode($newres));
                    }
                }
            }
        } catch (\Exception $th) {
            //throw $th;
            exception($th);
        }
    }

    public function mobilecheckredis($mobile)
    {
        $redis  = Phpredis::getConn();
        $prefix = substr(trim($mobile), 0, 7);
        // 13001001850
        $newres = $redis->hget('index:mobile:source', $prefix);
        $newres = json_decode($newres, true);

        if (in_array(substr(trim($mobile), 0, 3), ['141', '142', '143', '144', '145', '146', '148', '149', '154', '163', '169', '179', '196'])) {
            return false;
        }
        if (empty($newres)) {
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
            } elseif (substr(trim($mobile), 0, 3) == 166) {
                $newres = [
                    'source' => 2,
                ];
            } elseif (in_array(substr(trim($mobile), 0, 3), [170, 173, 178, 184, 191, 199, 162, 133, 149, 153, 173, 177, 180, 181, 189])) { //电信
                $newres = [
                    'source' => 3,
                ];
            } elseif (in_array(substr(trim($mobile), 0, 3), [171, 175, 176, 185, 167, 130, 131, 132, 145, 155, 156, 166, 186, 166])) { //联通
                $newres = [
                    'source' => 2,
                ];
            } elseif (in_array(substr(trim($mobile), 0, 3), [147, 172, 177, 187, 188, 195, 198, 165, 134, 135, 136, 137, 138, 139, 147, 150, 151, 152, 157, 158, 159, 1705, 178, 182, 183, 184, 187, 188, 198])) { //移动
                $newres = [
                    'source' => 1,
                ];
            } else {
                return false;
            }
        }
        return $newres;
    }

    // 行业计费核对
    public function checkSendStatusForBusiness()
    {
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        try {
            //code...
            while (true) {
                // $uids = Db::query("SELECT `id`,`pid` FROM yx_users WHERE `id` IN (SELECT id FROM `messagesend`.`yx_users` WHERE `pid` = '137') "); //道信核对
                $uids = Db::query("SELECT `id`,`pid` FROM yx_users WHERE `id` IN (279) "); //道信核对
                // $uids = Db::query("SELECT `id`,`pid` FROM yx_users "); //道信核对
                //行业
                foreach ($uids as $key => $value) {
                    // continue;
                    // $start_time = (int) strtotime('-4 days', strtotime(date('Y-m-d', time())));
                    $start_time = (int) strtotime('2020-09-01');
                    // echo $start_time;die;
                    if (!Db::query("SELECT `id`,`create_time` FROM yx_user_send_code_task WHERE uid  = " . $value['id'] . " AND `create_time` >= '" . $start_time . "' AND `create_time` <= '" . time() . "' ")) {
                        continue;
                    }
                    while (true) {

                        $day_business_result = [];
                        $end_time            = $start_time + 86400;
                        $timekey             = date('Ymd', $start_time);
                        // echo "uid:" . $value['id'] . "" . "timekey:" . $timekey;
                        // echo "\n";
                        $business_id = 6;
                        if ($end_time > time()) {
                            // break;
                            $end_time            = time();
                            $day_business_result = $this->selectSendResultForBusiness($value['id'], $value['pid'], $start_time, $end_time);
                            if ($day_business_result == false) {
                                break;
                            } else {
                                $day_business_result['uid']         = $value['id'];
                                $day_business_result['timekey']     = $timekey;
                                $day_business_result['business_id'] = $business_id;
                                $has                                = Db::query('SELECT * FROM `yx_statistics_day` WHERE `business_id` = 6 AND `timekey` = ' . $timekey . ' AND `uid` = ' . $value['id']);
                                if ($has) {
                                    Db::table('yx_statistics_day')->where('id', $has[0]['id'])->update([
                                        'success'     => $day_business_result['success'],
                                        'unknown'     => $day_business_result['unknown'],
                                        'default'     => $day_business_result['default'],
                                        'num'         => $day_business_result['num'],
                                        'mobile_num'  => $day_business_result['mobile_num'],
                                        'ratio'       => $day_business_result['ratio'],
                                        'update_time' => time(),
                                    ]);
                                } else {
                                    Db::table('yx_statistics_day')->insert($day_business_result);
                                }
                                break;
                            }
                            //

                        }
                        $day_business_result = $this->selectSendResultForBusiness($value['id'], $value['pid'], $start_time, $end_time);
                        if ($day_business_result == false) {
                            $start_time = $end_time;
                            continue;
                        }

                        // die;
                        $day_business_result['uid']         = $value['id'];
                        $day_business_result['timekey']     = $timekey;
                        $day_business_result['business_id'] = $business_id;
                        $day_business_result['create_time'] = time();
                        $day_business_result['update_time'] = time();
                        // print_r($day_business_result);
                        $has = Db::query('SELECT * FROM `yx_statistics_day` WHERE `business_id` = 6 AND `timekey` = ' . $timekey . ' AND `uid` = ' . $value['id']);
                        if ($has) {
                            Db::table('yx_statistics_day')->where('id', $has[0]['id'])->update([
                                'success'     => $day_business_result['success'],
                                'unknown'     => $day_business_result['unknown'],
                                'default'     => $day_business_result['default'],
                                'num'         => $day_business_result['num'],
                                'mobile_num'  => $day_business_result['mobile_num'],
                                'ratio'       => $day_business_result['ratio'],
                                'update_time' => time(),
                            ]);
                        } else {
                            Db::table('yx_statistics_day')->insert($day_business_result);
                        }
                        $start_time = $end_time;
                    }
                }
                //营销
                /*  foreach ($uids as $key => $value) {
                    // $start_time = (int) strtotime('-3 days', strtotime(date('Y-m-d', time())));
                    $start_time = (int) strtotime('2020-09-01');
                    if (!Db::query("SELECT `id`,`create_time` FROM yx_user_send_task WHERE uid  = " . $value['id'] . " AND `create_time` >= '" . $start_time . "' AND `create_time` <= '" . time() . "' ")) {
                        continue;
                    }
                    while (true) {
                        $end_time    = $start_time + 86400;
                        $timekey     = date('Ymd', $start_time);
                        $business_id = 5;
                        // echo "uid:" . $value['id'] . "" . "timekey:" . $timekey;
                        // echo "\n";
                        if ($end_time > time()) {
                            // break;
                            $end_time             = time();
                            $day_marketing_result = $this->selectSendResultForMarketing($value['id'], $value['pid'], $start_time, $end_time);
                            if ($day_marketing_result == false) {
                                break;
                            }
                            $day_marketing_result['uid']         = $value['id'];
                            $day_marketing_result['timekey']     = $timekey;
                            $day_marketing_result['business_id'] = $business_id;
                            $has                                 = Db::query('SELECT * FROM `yx_statistics_day` WHERE `business_id` = 5 AND `timekey` = ' . $timekey . ' AND `uid` = ' . $value['id']);
                            if ($has) {
                                Db::table('yx_statistics_day')->where('id', $has[0]['id'])->update([
                                    'success'     => $day_marketing_result['success'],
                                    'unknown'     => $day_marketing_result['unknown'],
                                    'default'     => $day_marketing_result['default'],
                                    'num'         => $day_marketing_result['num'],
                                    'mobile_num'  => $day_marketing_result['mobile_num'],
                                    'ratio'       => $day_marketing_result['ratio'],
                                    'update_time' => time(),
                                ]);
                            } else {
                                Db::table('yx_statistics_day')->insert($day_marketing_result);
                            }
                            break;
                            //
                        }

                        $day_marketing_result = $this->selectSendResultForMarketing($value['id'], $value['pid'], $start_time, $end_time);
                        if ($day_marketing_result == false) {
                            $start_time = $end_time;
                            continue;
                        }
                        $day_marketing_result['uid']         = $value['id'];
                        $day_marketing_result['timekey']     = $timekey;
                        $day_marketing_result['business_id'] = $business_id;
                        $day_marketing_result['create_time'] = time();
                        $day_marketing_result['update_time'] = time();
                        $has                                 = Db::query('SELECT * FROM `yx_statistics_day` WHERE `business_id` = 5 AND `timekey` = ' . $timekey . ' AND `uid` = ' . $value['id']);
                        if ($has) {
                            Db::table('yx_statistics_day')->where('id', $has[0]['id'])->update([
                                'success'     => $day_marketing_result['success'],
                                'unknown'     => $day_marketing_result['unknown'],
                                'default'     => $day_marketing_result['default'],
                                'num'         => $day_marketing_result['num'],
                                'mobile_num'  => $day_marketing_result['mobile_num'],
                                'ratio'       => $day_marketing_result['ratio'],
                                'update_time' => time(),
                            ]);
                        } else {
                            Db::table('yx_statistics_day')->insert($day_marketing_result);
                        }
                        // print_r($day_marketing_result);
                        // die;
                        $start_time = $end_time;
                    }
                } */
                sleep(900);
            }
        } catch (\Exceptixon $th) {
            //throw $th;
            print_r($day_business_result);
            exception($th);
        }
    }

    public function checkSendStatusForMarketing()
    {
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        try {
            //code...
            while (true) {
                // $uids = Db::query("SELECT `id`,`pid` FROM yx_users WHERE `id` IN (SELECT id FROM `messagesend`.`yx_users` WHERE `pid` = '137') "); //道信核对
                $uids = Db::query("SELECT `id`,`pid` FROM yx_users WHERE `id` IN (279) "); //道信核对
                // $uids = Db::query("SELECT `id`,`pid` FROM yx_users "); //道信核对

                //营销
                foreach ($uids as $key => $value) {
                    // $start_time = (int) strtotime('-3 days', strtotime(date('Y-m-d', time())));
                    $start_time = (int) strtotime('2020-09-01');
                    if (!Db::query("SELECT `id`,`create_time` FROM yx_user_send_task WHERE uid  = " . $value['id'] . " AND `create_time` >= '" . $start_time . "' AND `create_time` <= '" . time() . "' ")) {
                        continue;
                    }
                    while (true) {
                        $end_time    = $start_time + 86400;
                        $timekey     = date('Ymd', $start_time);
                        $business_id = 5;
                        // echo "uid:" . $value['id'] . "" . "timekey:" . $timekey;
                        // echo "\n";
                        if ($end_time > time()) {
                            // break;
                            $end_time             = time();
                            $day_marketing_result = $this->selectSendResultForMarketing($value['id'], $value['pid'], $start_time, $end_time);
                            if ($day_marketing_result == false) {
                                break;
                            }
                            $day_marketing_result['uid']         = $value['id'];
                            $day_marketing_result['timekey']     = $timekey;
                            $day_marketing_result['business_id'] = $business_id;
                            $has                                 = Db::query('SELECT * FROM `yx_statistics_day` WHERE `business_id` = 5 AND `timekey` = ' . $timekey . ' AND `uid` = ' . $value['id']);
                            if ($has) {
                                Db::table('yx_statistics_day')->where('id', $has[0]['id'])->update([
                                    'success'     => $day_marketing_result['success'],
                                    'unknown'     => $day_marketing_result['unknown'],
                                    'default'     => $day_marketing_result['default'],
                                    'num'         => $day_marketing_result['num'],
                                    'mobile_num'  => $day_marketing_result['mobile_num'],
                                    'ratio'       => $day_marketing_result['ratio'],
                                    'update_time' => time(),
                                ]);
                            } else {
                                Db::table('yx_statistics_day')->insert($day_marketing_result);
                            }
                            break;
                            //
                        }

                        $day_marketing_result = $this->selectSendResultForMarketing($value['id'], $value['pid'], $start_time, $end_time);
                        if ($day_marketing_result == false) {
                            $start_time = $end_time;
                            continue;
                        }
                        $day_marketing_result['uid']         = $value['id'];
                        $day_marketing_result['timekey']     = $timekey;
                        $day_marketing_result['business_id'] = $business_id;
                        $day_marketing_result['create_time'] = time();
                        $day_marketing_result['update_time'] = time();
                        $has                                 = Db::query('SELECT * FROM `yx_statistics_day` WHERE `business_id` = 5 AND `timekey` = ' . $timekey . ' AND `uid` = ' . $value['id']);
                        if ($has) {
                            Db::table('yx_statistics_day')->where('id', $has[0]['id'])->update([
                                'success'     => $day_marketing_result['success'],
                                'unknown'     => $day_marketing_result['unknown'],
                                'default'     => $day_marketing_result['default'],
                                'num'         => $day_marketing_result['num'],
                                'mobile_num'  => $day_marketing_result['mobile_num'],
                                'ratio'       => $day_marketing_result['ratio'],
                                'update_time' => time(),
                            ]);
                        } else {
                            Db::table('yx_statistics_day')->insert($day_marketing_result);
                        }
                        // print_r($day_marketing_result);
                        // die;
                        $start_time = $end_time;
                    }
                }
                sleep(900);
            }
        } catch (\Exceptixon $th) {
            //throw $th;
            // echo Db::getLastSQL();
            exception($th);
        }
    }



    public function checkSendStatusForBusinessChannel()
    {
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        try {
            //code...
            while (true) {
                $uids = Db::query("SELECT `id` FROM yx_sms_sending_channel  "); //道信核对
                // $uids = Db::query("SELECT `id`,`pid` FROM yx_users "); //道信核对
                //行业
                foreach ($uids as $key => $value) {
                    // continue;
                    // $start_time = (int) strtotime('-4 days', strtotime(date('Y-m-d', time())));
                    $start_time = (int) strtotime('2020-03-01');
                    // echo $start_time;die;
                    if (!Db::query("SELECT `id`,`create_time` FROM yx_user_send_code_task WHERE yidong_channel_id  = " . $value['id'] . " AND `create_time` >= '" . $start_time . "' AND `create_time` <= '" . time() . "' ")) {
                        continue;
                    }
                    while (true) {

                        $day_business_result = [];
                        $end_time            = $start_time + 86400;
                        $timekey             = date('Ymd', $start_time);
                        // echo "uid:" . $value['id'] . "" . "timekey:" . $timekey;
                        // echo "\n";
                        $business_id = 6;
                        if ($end_time > time()) {
                            // break;
                            $end_time            = time();
                            $day_business_result = $this->selectSendResultForBusinessChannel($value['id'], $start_time, $end_time);
                            if ($day_business_result == false) {
                                break;
                            } else {
                                $day_business_result['uid']         = $value['id'];
                                $day_business_result['timekey']     = $timekey;
                                $day_business_result['business_id'] = $business_id;
                                $has                                = Db::query('SELECT * FROM `yx_statistics_day_channel` WHERE `business_id` = 6 AND `timekey` = ' . $timekey . ' AND `uid` = ' . $value['id']);
                                if ($has) {
                                    Db::table('yx_statistics_day_channel')->where('id', $has[0]['id'])->update([
                                        'success'     => $day_business_result['success'],
                                        'unknown'     => $day_business_result['unknown'],
                                        'default'     => $day_business_result['default'],
                                        'num'         => $day_business_result['num'],
                                        'mobile_num'  => $day_business_result['mobile_num'],
                                        'ratio'       => $day_business_result['ratio'],
                                        'update_time' => time(),
                                    ]);
                                } else {
                                    Db::table('yx_statistics_day_channel')->insert($day_business_result);
                                }
                                break;
                            }
                            //

                        }
                        $day_business_result = $this->selectSendResultForBusinessChannel($value['id'],  $start_time, $end_time);
                        if ($day_business_result == false) {
                            $start_time = $end_time;
                            continue;
                        }

                        // die;
                        $day_business_result['channel_id']         = $value['id'];
                        $day_business_result['timekey']     = $timekey;
                        $day_business_result['business_id'] = $business_id;
                        $day_business_result['create_time'] = time();
                        $day_business_result['update_time'] = time();
                        // print_r($day_business_result);
                        $has = Db::query('SELECT * FROM `yx_statistics_day_channel` WHERE `business_id` = 6 AND `timekey` = ' . $timekey . ' AND `channel_id` = ' . $value['id']);
                        if ($has) {
                            Db::table('yx_statistics_day_channel')->where('id', $has[0]['id'])->update([
                                'success'     => $day_business_result['success'],
                                'unknown'     => $day_business_result['unknown'],
                                'default'     => $day_business_result['default'],
                                'num'         => $day_business_result['num'],
                                'mobile_num'  => $day_business_result['mobile_num'],
                                'ratio'       => $day_business_result['ratio'],
                                'update_time' => time(),
                            ]);
                        } else {
                            Db::table('yx_statistics_day_channel')->insert($day_business_result);
                        }
                        $start_time = $end_time;
                    }
                }

                sleep(900);
            }
        } catch (\Exceptixon $th) {
            //throw $th;
            print_r($day_business_result);
            exception($th);
        }
    }

    public function checkSendStatusForMarketingChannel()
    {
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        try {
            //code...
            while (true) {
                $uids = Db::query("SELECT `id` FROM yx_sms_sending_channel  "); //道信核对
                // $uids = Db::query("SELECT `id`,`pid` FROM yx_users "); //道信核对

                //营销
                foreach ($uids as $key => $value) {
                    // $start_time = (int) strtotime('-3 days', strtotime(date('Y-m-d', time())));
                    $start_time = (int) strtotime('2020-03-01');
                    if (!Db::query("SELECT `id`,`create_time` FROM yx_user_send_task WHERE `yidong_channel_id`   = " . $value['id'] . " AND `create_time` >= '" . $start_time . "' AND `create_time` <= '" . time() . "' ")) {
                        continue;
                    }
                    while (true) {
                        $end_time    = $start_time + 86400;
                        $timekey     = date('Ymd', $start_time);
                        $business_id = 5;
                        // echo "uid:" . $value['id'] . "" . "timekey:" . $timekey;
                        // echo "\n";
                        if ($end_time > time()) {
                            // break;
                            $end_time             = time();
                            $day_marketing_result = $this->selectSendResultForMarketingChannel($value['id'],  $start_time, $end_time);
                            if ($day_marketing_result == false) {
                                break;
                            }
                            $day_marketing_result['channel_id']         = $value['id'];
                            $day_marketing_result['timekey']     = $timekey;
                            $day_marketing_result['business_id'] = $business_id;
                            $has                                 = Db::query('SELECT * FROM `yx_statistics_day_channel  ` WHERE `business_id` = 5 AND `timekey` = ' . $timekey . ' AND `channel_id` = ' . $value['id']);
                            if ($has) {
                                Db::table('yx_statistics_day_channel')->where('id', $has[0]['id'])->update([
                                    'success'     => $day_marketing_result['success'],
                                    'unknown'     => $day_marketing_result['unknown'],
                                    'default'     => $day_marketing_result['default'],
                                    'num'         => $day_marketing_result['num'],
                                    'mobile_num'  => $day_marketing_result['mobile_num'],
                                    'ratio'       => $day_marketing_result['ratio'],
                                    'update_time' => time(),
                                ]);
                            } else {
                                Db::table('yx_statistics_day_channel')->insert($day_marketing_result);
                            }
                            break;
                            //
                        }

                        $day_marketing_result = $this->selectSendResultForMarketingChannel($value['id'], $start_time, $end_time);
                        if ($day_marketing_result == false) {
                            $start_time = $end_time;
                            continue;
                        }
                        $day_marketing_result['channel_id']         = $value['id'];
                        $day_marketing_result['timekey']     = $timekey;
                        $day_marketing_result['business_id'] = $business_id;
                        $day_marketing_result['create_time'] = time();
                        $day_marketing_result['update_time'] = time();
                        $has                                 = Db::query('SELECT * FROM `yx_statistics_day_channel` WHERE `business_id` = 5 AND `timekey` = ' . $timekey . ' AND `channel_id` = ' . $value['id']);
                        if ($has) {
                            Db::table('yx_statistics_day_channel')->where('id', $has[0]['id'])->update([
                                'success'     => $day_marketing_result['success'],
                                'unknown'     => $day_marketing_result['unknown'],
                                'default'     => $day_marketing_result['default'],
                                'num'         => $day_marketing_result['num'],
                                'mobile_num'  => $day_marketing_result['mobile_num'],
                                'ratio'       => $day_marketing_result['ratio'],
                                'update_time' => time(),
                            ]);
                        } else {
                            Db::table('yx_statistics_day_channel')->insert($day_marketing_result);
                        }
                        // print_r($day_marketing_result);
                        // die;
                        $start_time = $end_time;
                    }
                }
                sleep(900);
            }
        } catch (\Exceptixon $th) {
            //throw $th;
            print_r($day_marketing_result);
            exception($th);
        }
    }

    public function checkSendFreeForMonth()
    {
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        try {
            //code...
            while (true) {

                $uids = Db::query("SELECT `id`,`pid` FROM yx_users "); //道信核对
                //行业
                foreach ($uids as $key => $value) {
                    $i = 0;
                    // continue;
                    while (true) {
                        $start_time = (int) strtotime('-' . $i . ' month', strtotime(date('Y-m-d', time())));
                        $business_id = 5; //营销
                        $timekey = date('Ym', $start_time);
                        // echo $timekey;die;
                        $result = $this->checkDaySendResult($business_id, $timekey, $value['id']);
                        if ($result !== false) {
                            // print_r($result);die;
                            $has                                 = Db::query('SELECT * FROM `yx_statistics_month` WHERE `business_id` = ' . $business_id . ' AND `timekey` = ' . $timekey . ' AND `uid` = ' . $value['id']);
                            if ($has) {
                                Db::table('yx_statistics_day')->where('id', $has[0]['id'])->update([
                                    'success'     => $result['success'],
                                    'unknown'     => $result['unknown'],
                                    'default'     => $result['default'],
                                    'num'         => $result['num'],
                                    'mobile_num'  =>  $result['mobile_num'],
                                    'ratio'       => $result['ratio'],
                                    'update_time' => time(),
                                ]);
                            } else {
                                Db::table('yx_statistics_month')->insert($result);
                            }
                        }

                        // print_r($data);die;
                        // $mobile_num =$mobile_num[0]['mobile_num'];

                        $business_id = 6; //营销
                        $result = $this->checkDaySendResult($business_id, $timekey, $value['id']);
                        if ($result !== false) {
                            // print_r($result);die;
                            $has                                 = Db::query('SELECT * FROM `yx_statistics_month` WHERE `business_id` = ' . $business_id . ' AND `timekey` = ' . $timekey . ' AND `uid` = ' . $value['id']);
                            if ($has) {
                                Db::table('yx_statistics_day')->where('id', $has[0]['id'])->update([
                                    'success'     => $result['success'],
                                    'unknown'     => $result['unknown'],
                                    'default'     => $result['default'],
                                    'num'         => $result['num'],
                                    'mobile_num'  =>  $result['mobile_num'],
                                    'ratio'       => $result['ratio'],
                                    'update_time' => time(),
                                ]);
                            } else {
                                Db::table('yx_statistics_month')->insert($result);
                            }
                        }

                        $business_id = 8; //营销
                        $result = $this->checkDaySendResult($business_id, $timekey, $value['id']);
                        if ($result !== false) {
                            // print_r($result);die;
                            $has                                 = Db::query('SELECT * FROM `yx_statistics_month` WHERE `business_id` = ' . $business_id . ' AND `timekey` = ' . $timekey . ' AND `uid` = ' . $value['id']);
                            if ($has) {
                                Db::table('yx_statistics_day')->where('id', $has[0]['id'])->update([
                                    'success'     => $result['success'],
                                    'unknown'     => $result['unknown'],
                                    'default'     => $result['default'],
                                    'num'         => $result['num'],
                                    'mobile_num'  =>  $result['mobile_num'],
                                    'ratio'       => $result['ratio'],
                                    'update_time' => time(),
                                ]);
                            } else {
                                Db::table('yx_statistics_month')->insert($result);
                            }
                        }

                        $business_id = 11; //营销
                        $result = $this->checkDaySendResult($business_id, $timekey, $value['id']);
                        if ($result !== false) {
                            // print_r($result);die;
                            $has                                 = Db::query('SELECT * FROM `yx_statistics_month` WHERE `business_id` = ' . $business_id . ' AND `timekey` = ' . $timekey . ' AND `uid` = ' . $value['id']);
                            if ($has) {
                                Db::table('yx_statistics_day')->where('id', $has[0]['id'])->update([
                                    'success'     => $result['success'],
                                    'unknown'     => $result['unknown'],
                                    'default'     => $result['default'],
                                    'num'         => $result['num'],
                                    'mobile_num'  =>  $result['mobile_num'],
                                    'ratio'       => $result['ratio'],
                                    'update_time' => time(),
                                ]);
                            } else {
                                Db::table('yx_statistics_month')->insert($result);
                            }
                        }


                        $i++;
                        if ($i > 12) {
                            break;
                        }
                    }
                }


                sleep(86400);
            }
        } catch (\Exceptixon $th) {
            //throw $th;

            exception($th);
        }
    }


    private function checkDaySendResult($business_id, $timekey, $uid)
    {
        // $business_id = 11;//营销
        // $timekey = date('Ym',$start_time);
        // echo $timekey;die;
        $mobile_num = Db::query("SELECT SUM(`mobile_num`) as mobile_num FROM yx_statistics_day WHERE `timekey` LIKE '" . $timekey . "%' AND `uid` = '" . $uid . "' AND business_id = " . $business_id);
        // print_r($mobile_num);
        if (empty($mobile_num[0]['mobile_num'])) {
            // continue;
            return false;
        }
        $num = Db::query("SELECT SUM(`num`) as num FROM yx_statistics_day WHERE `timekey` LIKE '" . $timekey . "%' AND `uid` = '" . $uid . "' AND business_id = " . $business_id);
        $success = Db::query("SELECT SUM(`success`) as success FROM yx_statistics_day WHERE `timekey` LIKE '" . $timekey . "%' AND `uid` = '" . $uid . "' AND business_id = " . $business_id);
        $unknown = Db::query("SELECT SUM(`unknown`) as unknown FROM yx_statistics_day WHERE `timekey` LIKE '" . $timekey . "%' AND `uid` = '" . $uid . "' AND business_id = " . $business_id);
        $default_num = Db::query("SELECT SUM(`default`) as default_num FROM yx_statistics_day WHERE `timekey` LIKE '" . $timekey . "%' AND `uid` = '" . $uid . "' AND business_id = " . $business_id);
        // echo "\n";
        // print_r("SELECT SUM(`num`) as num FROM yx_statistics_day WHERE `timekey` LIKE '".$timekey."%' AND `uid` = '".$uid."' AND business_id = ".$business_id);
        $ratio =  $success[0]['success'] / $num[0]['num'] * 100;
        $data = [];
        $data = [
            'mobile_num' => $mobile_num[0]['mobile_num'],
            'num' => $num[0]['num'],
            'success' => $success[0]['success'],
            'unknown' => $unknown[0]['unknown'],
            'default' => $default_num[0]['default_num'],
            'timekey' => $timekey,
            'ratio' => $ratio,
            'business_id' => $business_id,
            'uid' => $uid,
            'create_time' => time(),
        ];
        return $data;
    }

    private function selectSendResultForBusiness($uid, $pid, $start_time, $end_time)
    {
        $all_num        = 0;
        $mobile_num     = 0;
        $success_num    = 0;
        $unknow_num     = 0;
        $default_num    = 0;
        $settlement_num = 1;
        //行业计费

        $max_len = Db::query("SELECT  send_length FROM `yx_user_send_code_task` WHERE `uid` = " . $uid . " AND yidong_channel_id <> 0  AND `create_time` >= '" . $start_time . "' AND `create_time` <= '" . $end_time . "'  ORDER BY `send_length` DESC LIMIT 1");
        if (empty($max_len)) {
            return false;
        } else {
            $max_len = $max_len[0]['send_length'];
            if ($max_len > 70) {
                $settlement_num = ceil($max_len / 67);
            }
        }
        for ($i = 0; $i < $settlement_num; $i++) {
            # code...
            if ($i == 0) {
                $min_length = 0;
                $max_length = 70;
            } else {
                $min_length = 67 * $i;
                if ($i == 1) {
                    $min_length = 70;
                }
                $max_length = 67 * ($i + 1);
            }
            $business_mobile_num = Db::query("SELECT SUM(`send_num`) AS send_num FROM `yx_user_send_code_task` WHERE `uid` = " . $uid . " AND yidong_channel_id <> 0 AND `create_time` >= '" . $start_time . "' AND `create_time` <= '" . $end_time . "' AND send_length > " . $min_length . " AND send_length <= " . $max_length)[0]['send_num'];
            if (!empty($business_mobile_num)) {
                if ($pid == 137) {
                    if (time() > 1598803200) {
                        $business_success_mobile_num = Db::query("SELECT `mobile`,`task_id` FROM `yx_send_code_task_receipt` WHERE `task_id` IN (SELECT `id` FROM `yx_user_send_code_task` WHERE  `uid` = " . $uid . " AND yidong_channel_id <> 0   AND `create_time` >= '" . $start_time . "' AND `create_time` < '" . $end_time . "' AND send_length > " . $min_length . " AND send_length <= " . $max_length . " ) AND status_message IN ('MK:1006','DELIVRD') GROUP BY `mobile`,`task_id`");
                        $business_default_mobile_num = Db::query("SELECT `mobile`,`task_id` FROM `yx_send_code_task_receipt` WHERE `task_id` IN (SELECT `id` FROM `yx_user_send_code_task` WHERE  `uid` = " . $uid . " AND yidong_channel_id <> 0   AND `create_time` >= '" . $start_time . "' AND `create_time` < '" . $end_time . "' AND send_length > " . $min_length . " AND send_length <= " . $max_length . " ) AND status_message NOT IN ('MK:1006','DELIVRD') GROUP BY `mobile`,`task_id`");
                    } else {
                        $business_success_mobile_num = Db::query("SELECT `mobile`,`task_id` FROM `yx_send_code_task_receipt` WHERE `task_id` IN (SELECT `id` FROM `yx_user_send_code_task` WHERE  `uid` = " . $uid . " AND yidong_channel_id <> 0   AND `create_time` >= '" . $start_time . "' AND `create_time` < '" . $end_time . "' AND send_length > " . $min_length . " AND send_length <= " . $max_length . " ) AND status_message IN ('REJECTD','REJECT','MA:0001','DB:0141','MA:0001','MK:100D','MK:100C','IC:0151','EXPIRED','-1012','-1013','4442','4446','4014','DELIVRD') GROUP BY `mobile`,`task_id`");
                        $business_default_mobile_num = Db::query("SELECT `mobile`,`task_id` FROM `yx_send_code_task_receipt` WHERE `task_id` IN (SELECT `id` FROM `yx_user_send_code_task` WHERE  `uid` = " . $uid . " AND yidong_channel_id <> 0   AND `create_time` >= '" . $start_time . "' AND `create_time` < '" . $end_time . "' AND send_length > " . $min_length . " AND send_length <= " . $max_length . " ) AND status_message NOT IN ('REJECTD','REJECT','MA:0001','DB:0141','MA:0001','MK:100D','MK:100C','IC:0151','EXPIRED','-1012','-1013','4442','4446','4014','DELIVRD') GROUP BY `mobile`,`task_id`");
                    }
                } else {
                    $business_success_mobile_num = Db::query("SELECT `mobile`,`task_id` FROM `yx_send_code_task_receipt` WHERE `task_id` IN (SELECT `id` FROM `yx_user_send_code_task` WHERE  `uid` = " . $uid . " AND yidong_channel_id <> 0   AND `create_time` >= '" . $start_time . "' AND `create_time` < '" . $end_time . "'  AND send_length > " . $min_length . " AND send_length <= " . $max_length . " ) AND status_message IN ('DELIVRD','REJECTD', 'REJECT', 'MA:0001', 'DB:0141') GROUP BY `mobile`,`task_id`");
                    $business_default_mobile_num = Db::query("SELECT `mobile`,`task_id` FROM `yx_send_code_task_receipt` WHERE `task_id` IN (SELECT `id` FROM `yx_user_send_code_task` WHERE  `uid` = " . $uid . " AND yidong_channel_id <> 0   AND `create_time` >= '" . $start_time . "' AND `create_time` < '" . $end_time . "' AND send_length > " . $min_length . " AND send_length <= " . $max_length . " ) AND status_message NOT IN ('DELIVRD','REJECTD', 'REJECT', 'MA:0001', 'DB:0141') GROUP BY `mobile`,`task_id`");
                }
                $mobile_num += $business_mobile_num;
                $success_num += count($business_success_mobile_num) * ($i + 1);
                // if ($start_time >= 1595692800) {
                if (time() - $start_time >= 259200) {
                    $success_num += ($business_mobile_num - count($business_success_mobile_num) - count($business_default_mobile_num)) * ($i + 1);
                    $unknow_num = 0;
                } else {
                    $unknow_num += ($business_mobile_num - count($business_success_mobile_num) - count($business_default_mobile_num)) * ($i + 1);
                }

                $default_num += count($business_default_mobile_num) * ($i + 1);
            }
        }
        $all_num = $success_num + $unknow_num + $default_num;
        $ratio   = $success_num / $all_num * 100;
        return ['mobile_num' => $mobile_num, 'num' => $all_num, 'success' => $success_num, 'unknown' => $unknow_num, 'default' => $default_num, 'ratio' => $ratio];
    }

    private function selectSendResultForBusinessChannel($uid, $start_time, $end_time)
    {
        $all_num        = 0;
        $mobile_num     = 0;
        $success_num    = 0;
        $unknow_num     = 0;
        $default_num    = 0;
        $settlement_num = 1;
        //行业计费

        $max_len = Db::query("SELECT  send_length FROM `yx_user_send_code_task` WHERE `yidong_channel_id` = " . $uid . " AND yidong_channel_id <> 0  AND `create_time` >= '" . $start_time . "' AND `create_time` <= '" . $end_time . "'  ORDER BY `send_length` DESC LIMIT 1");
        if (empty($max_len)) {
            return false;
        } else {
            $max_len = $max_len[0]['send_length'];
            if ($max_len > 70) {
                $settlement_num = ceil($max_len / 67);
            }
        }
        for ($i = 0; $i < $settlement_num; $i++) {
            # code...
            if ($i == 0) {
                $min_length = 0;
                $max_length = 70;
            } else {
                $min_length = 67 * $i;
                if ($i == 1) {
                    $min_length = 70;
                }
                $max_length = 67 * ($i + 1);
            }
            $business_mobile_num = Db::query("SELECT SUM(`send_num`) AS send_num FROM `yx_user_send_code_task` WHERE `yidong_channel_id` = " . $uid . " AND yidong_channel_id <> 0 AND `create_time` >= '" . $start_time . "' AND `create_time` <= '" . $end_time . "' AND send_length > " . $min_length . " AND send_length <= " . $max_length)[0]['send_num'];
            if (!empty($business_mobile_num)) {
                $business_success_mobile_num = Db::query("SELECT `mobile`,`task_id` FROM `yx_send_code_task_receipt` WHERE `task_id` IN (SELECT `id` FROM `yx_user_send_code_task` WHERE  `yidong_channel_id` = " . $uid . " AND yidong_channel_id <> 0   AND `create_time` >= '" . $start_time . "' AND `create_time` < '" . $end_time . "'  AND send_length > " . $min_length . " AND send_length <= " . $max_length . " ) AND status_message IN ('DELIVRD') GROUP BY `mobile`,`task_id`");
                $business_default_mobile_num = Db::query("SELECT `mobile`,`task_id` FROM `yx_send_code_task_receipt` WHERE `task_id` IN (SELECT `id` FROM `yx_user_send_code_task` WHERE  `yidong_channel_id` = " . $uid . " AND yidong_channel_id <> 0   AND `create_time` >= '" . $start_time . "' AND `create_time` < '" . $end_time . "' AND send_length > " . $min_length . " AND send_length <= " . $max_length . " ) AND status_message NOT IN ('DELIVRD') GROUP BY `mobile`,`task_id`");
                $mobile_num += $business_mobile_num;
                $success_num += count($business_success_mobile_num) * ($i + 1);
                // if ($start_time >= 1595692800) {
                $unknow_num += ($business_mobile_num - count($business_success_mobile_num) - count($business_default_mobile_num)) * ($i + 1);
                $default_num += count($business_default_mobile_num) * ($i + 1);
            }
        }

        $all_num = $success_num + $unknow_num + $default_num;
        if ($all_num == 0) {
            echo "SELECT  send_length FROM `yx_user_send_code_task` WHERE `yidong_channel_id` = " . $uid . " AND yidong_channel_id <> 0  AND `create_time` >= '" . $start_time . "' AND `create_time` <= '" . $end_time . "'  ORDER BY `send_length` DESC LIMIT 1";
            die;
        }
        $ratio   = $success_num / $all_num * 100;
        return ['mobile_num' => $mobile_num, 'num' => $all_num, 'success' => $success_num, 'unknown' => $unknow_num, 'default' => $default_num, 'ratio' => $ratio];
    }


    public function SendResultForMarketingTest()
    {
        $result = $this->selectSendResultForMarketing(156, 137, 1598889600, 1598976000);
    }

    private function selectSendResultForMarketing($uid, $pid, $start_time, $end_time)
    {
        $all_num        = 0;
        $mobile_num     = 0;
        $success_num    = 0;
        $unknow_num     = 0;
        $default_num    = 0;
        $settlement_num = 1;
        //行业计费

        $max_len = Db::query("SELECT  send_length FROM `yx_user_send_task` WHERE `uid` = " . $uid . " AND `create_time` >= '" . $start_time . "' AND `create_time` <= '" . $end_time . "'  ORDER BY `send_length` DESC LIMIT 1");
        // print_r($max_len);
        if (empty($max_len)) {
            return false;
        } else {
            $max_len = $max_len[0]['send_length'];

            if ($max_len > 70) {
                $settlement_num = ceil($max_len / 67);
            }
        }
        for ($i = 0; $i < $settlement_num; $i++) {
            # code...
            if ($i == 0) {
                $min_length = 0;
                $max_length = 70;
            } else {
                $min_length = 67 * $i;
                if ($i == 1) {
                    $min_length = 70;
                }
                $max_length = 67 * ($i + 1);
            }
            $business_mobile_num = 0;
            $business_success_mobile_num = 0;
            $business_default_mobile_num = 0;
            $business_mobile_num = Db::query("SELECT SUM(`send_num`) AS send_num FROM `yx_user_send_task` WHERE `uid` = " . $uid . " AND yidong_channel_id <> 0   AND `create_time` >= '" . $start_time . "' AND `create_time` <= '" . $end_time . "' AND send_length > " . $min_length . " AND send_length <= " . $max_length)[0]['send_num'];

            if (!empty($business_mobile_num)) {
                if ($pid == 137) {
                    if (time() > 1598803200) {
                        $business_success_mobile_num = Db::query("SELECT `mobile`,`task_id` FROM `yx_send_task_receipt` WHERE `task_id` IN (SELECT `id` FROM `yx_user_send_task` WHERE  `uid` = " . $uid . " AND yidong_channel_id <> 0   AND `create_time` >= '" . $start_time . "' AND `create_time` < '" . $end_time . "' AND send_length > " . $min_length . " AND send_length <= " . $max_length . " ) AND status_message IN ('MK:1006','DELIVRD') GROUP BY `mobile`,`task_id`");
                        $business_default_mobile_num = Db::query("SELECT `mobile`,`task_id` FROM `yx_send_task_receipt` WHERE `task_id` IN (SELECT `id` FROM `yx_user_send_task` WHERE  `uid` = " . $uid . " AND yidong_channel_id <> 0   AND `create_time` >= '" . $start_time . "' AND `create_time` < '" . $end_time . "' AND send_length > " . $min_length . " AND send_length <= " . $max_length . " ) AND status_message NOT IN ('MK:1006','DELIVRD') GROUP BY `mobile`,`task_id`");
                    } else {
                        $business_success_mobile_num = Db::query("SELECT `mobile`,`task_id` FROM `yx_send_task_receipt` WHERE `task_id` IN (SELECT `id` FROM `yx_user_send_task` WHERE  `uid` = " . $uid . " AND yidong_channel_id <> 0   AND `create_time` >= '" . $start_time . "' AND `create_time` < '" . $end_time . "' AND send_length > " . $min_length . " AND send_length <= " . $max_length . " ) AND status_message IN ('REJECTD','REJECT','MA:0001','DB:0141','MA:0001','MK:100D','MK:100C','IC:0151','EXPIRED','-1012','-1013','4442','4446','4014','DELIVRD') GROUP BY `mobile`,`task_id`");
                        $business_default_mobile_num = Db::query("SELECT `mobile`,`task_id` FROM `yx_send_task_receipt` WHERE `task_id` IN (SELECT `id` FROM `yx_user_send_task` WHERE  `uid` = " . $uid . " AND yidong_channel_id <> 0   AND `create_time` >= '" . $start_time . "' AND `create_time` < '" . $end_time . "' AND send_length > " . $min_length . " AND send_length <= " . $max_length . " ) AND status_message NOT IN ('REJECTD','REJECT','MA:0001','DB:0141','MA:0001','MK:100D','MK:100C','IC:0151','EXPIRED','-1012','-1013','4442','4446','4014','DELIVRD') GROUP BY `mobile`,`task_id`");
                    }
                } else {
                    $business_success_mobile_num = Db::query("SELECT `mobile`,`task_id` FROM `yx_send_task_receipt` WHERE `task_id` IN (SELECT `id` FROM `yx_user_send_task` WHERE  `uid` = " . $uid . " AND yidong_channel_id <> 0   AND `create_time` >= '" . $start_time . "' AND `create_time` < '" . $end_time . "'  AND send_length > " . $min_length . " AND send_length <= " . $max_length . " ) AND status_message IN ('DELIVRD','REJECTD', 'REJECT', 'MA:0001', 'DB:0141') GROUP BY `mobile`,`task_id`");
                    $business_default_mobile_num = Db::query("SELECT `mobile`,`task_id` FROM `yx_send_task_receipt` WHERE `task_id` IN (SELECT `id` FROM `yx_user_send_task` WHERE  `uid` = " . $uid . " AND yidong_channel_id <> 0   AND `create_time` >= '" . $start_time . "' AND `create_time` < '" . $end_time . "' AND send_length > " . $min_length . " AND send_length <= " . $max_length . " ) AND status_message NOT IN ('DELIVRD','REJECTD', 'REJECT', 'MA:0001', 'DB:0141') GROUP BY `mobile`,`task_id`");
                }
                $mobile_num += $business_mobile_num;
                $success_num += count($business_success_mobile_num) * ($i + 1);
                // $unknow_num += ($business_mobile_num - count($business_success_mobile_num) - count($business_default_mobile_num)) * ($i + 1);
                // if ($start_time >= 1595692800) {
                if (time() - $start_time >= 259200) {
                    $success_num += ($business_mobile_num - count($business_success_mobile_num) - count($business_default_mobile_num)) * ($i + 1);
                    $unknow_num = 0;
                } else {
                    $unknow_num += ($business_mobile_num - count($business_success_mobile_num) - count($business_default_mobile_num)) * ($i + 1);
                }
                $default_num += count($business_default_mobile_num) * ($i + 1);
                // echo count($business_success_mobile_num);
                // echo "\n";
                // echo count($business_default_mobile_num);
                // echo "\n";
                // die;
            }
        }
        $all_num = $success_num + $unknow_num + $default_num;
        // return ['mobile_num' => $mobile_num, 'all_num' => $all_num, 'success_num' => $success_num, 'unknow_num' => $unknow_num, 'default_num' => $default_num];
        $ratio = $success_num / $all_num * 100;
        return ['mobile_num' => $mobile_num, 'num' => $all_num, 'success' => $success_num, 'unknown' => $unknow_num, 'default' => $default_num, 'ratio' => $ratio];
    }


    private function selectSendResultForMarketingChannel($uid,  $start_time, $end_time)
    {
        $all_num        = 0;
        $mobile_num     = 0;
        $success_num    = 0;
        $unknow_num     = 0;
        $default_num    = 0;
        $settlement_num = 1;
        //行业计费

        $max_len = Db::query("SELECT  send_length FROM `yx_user_send_task` WHERE `yidong_channel_id` = " . $uid . " AND `create_time` >= '" . $start_time . "' AND `create_time` <= '" . $end_time . "'  ORDER BY `send_length` DESC LIMIT 1");
        // print_r($max_len);
        if (empty($max_len)) {
            return false;
        } else {
            $max_len = $max_len[0]['send_length'];

            if ($max_len > 70) {
                $settlement_num = ceil($max_len / 67);
            }
        }
        for ($i = 0; $i < $settlement_num; $i++) {
            # code...
            if ($i == 0) {
                $min_length = 0;
                $max_length = 70;
            } else {
                $min_length = 67 * $i;
                if ($i == 1) {
                    $min_length = 70;
                }
                $max_length = 67 * ($i + 1);
            }
            $business_mobile_num = 0;
            $business_success_mobile_num = 0;
            $business_default_mobile_num = 0;
            $business_mobile_num = Db::query("SELECT SUM(`send_num`) AS send_num FROM `yx_user_send_task` WHERE `yidong_channel_id` = " . $uid . " AND yidong_channel_id <> 0   AND `create_time` >= '" . $start_time . "' AND `create_time` <= '" . $end_time . "' AND send_length > " . $min_length . " AND send_length <= " . $max_length)[0]['send_num'];

            if (!empty($business_mobile_num)) {
                $business_success_mobile_num = Db::query("SELECT `mobile`,`task_id` FROM `yx_send_task_receipt` WHERE `task_id` IN (SELECT `id` FROM `yx_user_send_task` WHERE  `yidong_channel_id` = " . $uid . " AND yidong_channel_id <> 0   AND `create_time` >= '" . $start_time . "' AND `create_time` < '" . $end_time . "'  AND send_length > " . $min_length . " AND send_length <= " . $max_length . " ) AND real_message IN ('DELIVRD') GROUP BY `mobile`,`task_id`");
                $business_default_mobile_num = Db::query("SELECT `mobile`,`task_id` FROM `yx_send_task_receipt` WHERE `task_id` IN (SELECT `id` FROM `yx_user_send_task` WHERE  `yidong_channel_id` = " . $uid . " AND yidong_channel_id <> 0   AND `create_time` >= '" . $start_time . "' AND `create_time` < '" . $end_time . "' AND send_length > " . $min_length . " AND send_length <= " . $max_length . " ) AND real_message NOT IN ('DELIVRD') GROUP BY `mobile`,`task_id`");
                $mobile_num += $business_mobile_num;
                $success_num += count($business_success_mobile_num) * ($i + 1);
                // $unknow_num += ($business_mobile_num - count($business_success_mobile_num) - count($business_default_mobile_num)) * ($i + 1);
                // if ($start_time >= 1595692800) {
                $unknow_num += ($business_mobile_num - count($business_success_mobile_num) - count($business_default_mobile_num)) * ($i + 1);
                $default_num += count($business_default_mobile_num) * ($i + 1);
                // echo count($business_success_mobile_num);
                // echo "\n";
                // echo count($business_default_mobile_num);
                // echo "\n";
                // die;
            }
        }
        $all_num = $success_num + $unknow_num + $default_num;
        if ($all_num == 0) {
            echo "SELECT  send_length FROM `yx_user_send_code_task` WHERE `yidong_channel_id` = " . $uid . " AND yidong_channel_id <> 0  AND `create_time` >= '" . $start_time . "' AND `create_time` <= '" . $end_time . "'  ORDER BY `send_length` DESC LIMIT 1";
            die;
        }
        // return ['mobile_num' => $mobile_num, 'all_num' => $all_num, 'success_num' => $success_num, 'unknow_num' => $unknow_num, 'default_num' => $default_num];
        $ratio = $success_num / $all_num * 100;
        return ['mobile_num' => $mobile_num, 'num' => $all_num, 'success' => $success_num, 'unknown' => $unknow_num, 'default' => $default_num, 'ratio' => $ratio];
    }

    public function SendResultForMultimediaTest()
    {
        $result = $this->selectSendResultForMultimedia(91, 0, 1596211200, 1596297600);
    }

    public function selectSendResultForMultimedia($uid, $pid, $start_time, $end_time)
    {
        $all_num        = 0;
        $mobile_num     = 0;
        $success_num    = 0;
        $unknow_num     = 0;
        $default_num    = 0;
        $settlement_num = 1;
        $max_len = Db::query("SELECT id FROM `yx_user_multimedia_message` WHERE `uid` = " . $uid . "  AND yidong_channel_id <> 0   AND `create_time` >= '" . $start_time . "' AND `create_time` <= '" . $end_time . "' ");
        if (empty($max_len)) {
            return false;
        }
        $mul_success_mobile_num = Db::query("SELECT `mobile`,`task_no` FROM `yx_user_multimedia_message_log` WHERE `task_no` IN (SELECT `task_no` FROM `yx_user_multimedia_message` WHERE  `uid` = " . $uid . " AND yidong_channel_id <> 0   AND `create_time` >= '" . $start_time . "' AND `create_time` < '" . $end_time . "' ) AND status_message IN ('REJECTD','REJECT','MA:0001','DB:0141','MA:0001','MK:100D','MK:100C','IC:0151','EXPIRED','-1012','-1013','4442','4446','4014','DELIVRD') GROUP BY `mobile`,`task_no`");
        $mul_default_mobile_num = Db::query("SELECT `mobile`,`task_no` FROM `yx_user_multimedia_message_log` WHERE `task_no` IN (SELECT `task_no` FROM `yx_user_multimedia_message` WHERE  `uid` = " . $uid . " AND yidong_channel_id <> 0   AND `create_time` >= '" . $start_time . "' AND `create_time` < '" . $end_time . "' ) AND status_message NOT IN ('REJECTD','REJECT','MA:0001','DB:0141','MA:0001','MK:100D','MK:100C','IC:0151','EXPIRED','-1012','-1013','4442','4446','4014','DELIVRD') GROUP BY `mobile`,`task_no`");
        $mobile_num =  Db::query("SELECT SUM(`real_num`) AS all_num FROM `yx_user_multimedia_message` WHERE  `uid` = " . $uid . " AND `create_time` >= '" . $start_time . "' AND `create_time` < '" . $end_time . "' ");
        // print_r("SELECT SUM(`real_num`) AS all_num FROM `yx_user_multimedia_message` WHERE  `uid` = " . $uid . " AND `create_time` >= '" . $start_time . "' AND `create_time` < '" . $end_time . "' ");
        // echo "\n";
        // echo "SELECT `mobile`,`task_no` FROM `yx_user_multimedia_message_log` WHERE `task_no` IN (SELECT `task_no` FROM `yx_user_multimedia_message` WHERE  `uid` = " . $uid . " AND `create_time` >= '" . $start_time . "' AND `create_time` < '" . $end_time . "' ) AND status_message IN ('REJECTD','REJECT','MA:0001','DB:0141','MA:0001','MK:100D','MK:100C','IC:0151','EXPIRED','-1012','-1013','4442','4446','4014','DELIVRD') GROUP BY `mobile`,`task_no`";die;
        // print_r(count($mul_default_mobile_num));
        // die;
        $success_num = count($mul_success_mobile_num);
        $all_num = $mobile_num[0]['all_num'];
        if ($uid == 223) {
            $unknow_num = $all_num - count($mul_success_mobile_num) - count($mul_default_mobile_num);
        } else {
            $success_num += $all_num - count($mul_success_mobile_num) - count($mul_default_mobile_num);
            $unknow_num = 0;
        }
        $default_num = count($mul_default_mobile_num);
        $ratio = $success_num / $all_num * 100;
        return ['mobile_num' => $all_num, 'num' => $all_num, 'success' => $success_num, 'unknown' => $unknow_num, 'default' => $default_num, 'ratio' => $ratio];
    }

    public function checkMultimediaSendStatus()
    {
        try {
            while (true) {
                $uids = Db::query("SELECT `id`,`pid` FROM yx_users "); //道信核对
                //行业
                foreach ($uids as $key => $value) {
                    // continue;
                    // $start_time = (int) strtotime('-4 days', strtotime(date('Y-m-d', time())));
                    $start_time = (int) strtotime('2020-08-01');
                    if (!Db::query("SELECT `id`,`create_time` FROM yx_user_multimedia_message WHERE uid  = " . $value['id'] . " AND `create_time` >= '" . $start_time . "' AND `create_time` <= '" . time() . "' ")) {
                        continue;
                    }
                    while (true) {

                        $day_business_result = [];
                        $end_time            = $start_time + 86400;
                        $timekey             = date('Ymd', $start_time);
                        // echo "uid:" . $value['id'] . "" . "timekey:" . $timekey;
                        // echo "\n";
                        $business_id = 8;
                        if ($end_time > time()) {
                            // break;
                            $end_time            = time();
                            $day_business_result = $this->selectSendResultForMultimedia($value['id'], $value['pid'], $start_time, $end_time);
                            if ($day_business_result == false) {
                                break;
                            } else {
                                $day_business_result['uid']         = $value['id'];
                                $day_business_result['timekey']     = $timekey;
                                $day_business_result['business_id'] = $business_id;
                                $has                                = Db::query('SELECT * FROM `yx_statistics_day` WHERE `business_id` = 8 AND `timekey` = ' . $timekey . ' AND `uid` = ' . $value['id']);
                                if ($has) {
                                    Db::table('yx_statistics_day')->where('id', $has[0]['id'])->update([
                                        'success'     => $day_business_result['success'],
                                        'unknown'     => $day_business_result['unknown'],
                                        'default'     => $day_business_result['default'],
                                        'num'         => $day_business_result['num'],
                                        'mobile_num'  => $day_business_result['mobile_num'],
                                        'ratio'       => $day_business_result['ratio'],
                                        'update_time' => time(),
                                    ]);
                                } else {
                                    Db::table('yx_statistics_day')->insert($day_business_result);
                                }
                                break;
                            }
                            //

                        }
                        $day_business_result = $this->selectSendResultForMultimedia($value['id'], $value['pid'], $start_time, $end_time);
                        if ($day_business_result == false) {
                            $start_time = $end_time;
                            continue;
                        }

                        // die;
                        $day_business_result['uid']         = $value['id'];
                        $day_business_result['timekey']     = $timekey;
                        $day_business_result['business_id'] = $business_id;
                        $day_business_result['create_time'] = time();
                        $day_business_result['update_time'] = time();
                        // print_r($day_business_result);
                        $has = Db::query('SELECT * FROM `yx_statistics_day` WHERE `business_id` = 8 AND `timekey` = ' . $timekey . ' AND `uid` = ' . $value['id']);
                        if ($has) {
                            Db::table('yx_statistics_day')->where('id', $has[0]['id'])->update([
                                'success'     => $day_business_result['success'],
                                'unknown'     => $day_business_result['unknown'],
                                'default'     => $day_business_result['default'],
                                'num'         => $day_business_result['num'],
                                'mobile_num'  => $day_business_result['mobile_num'],
                                'ratio'       => $day_business_result['ratio'],
                                'update_time' => time(),
                            ]);
                        } else {
                            Db::table('yx_statistics_day')->insert($day_business_result);
                        }
                        $start_time = $end_time;
                    }
                }
                sleep(900);
            }
        } catch (\Exception $th) {
            //throw $th;
            exception($th);
        }
    }

    public function SendResultForSupmessageTest()
    {
        $result = $this->selectSendResultForSupMessage(91, 0, 1596211200, 1596297600);
    }

    public function selectSendResultForSupMessage($uid, $pid, $start_time, $end_time)
    {
        $all_num        = 0;
        $mobile_num     = 0;
        $success_num    = 0;
        $unknow_num     = 0;
        $default_num    = 0;
        $settlement_num = 1;
        $max_len = Db::query("SELECT id FROM `yx_user_sup_message`  WHERE `uid` = " . $uid . " AND yidong_channel_id <> 0  AND `create_time` >= '" . $start_time . "' AND `create_time` <= '" . $end_time . "' ");
        if (empty($max_len)) {
            return false;
        }
        $mul_success_mobile_num = Db::query("SELECT `mobile`,`task_no` FROM `yx_user_sup_message_log` WHERE `task_no` IN (SELECT `task_no` FROM `yx_user_sup_message` WHERE  `uid` = " . $uid . " AND yidong_channel_id <> 0   AND `create_time` >= '" . $start_time . "' AND `create_time` < '" . $end_time . "' ) AND status_message IN ('DELIVRD') GROUP BY `mobile`,`task_no`");
        $mul_default_mobile_num = Db::query("SELECT `mobile`,`task_no` FROM `yx_user_sup_message_log` WHERE `task_no` IN (SELECT `task_no` FROM `yx_user_sup_message` WHERE  `uid` = " . $uid . " AND yidong_channel_id <> 0   AND `create_time` >= '" . $start_time . "' AND `create_time` < '" . $end_time . "' ) AND status_message NOT IN ('DELIVRD') GROUP BY `mobile`,`task_no`");
        $mobile_num =  Db::query("SELECT SUM(`real_num`) AS all_num FROM `yx_user_sup_message` WHERE  `uid` = " . $uid . " AND `create_time` >= '" . $start_time . "' AND `create_time` < '" . $end_time . "' ");
        // print_r("SELECT SUM(`real_num`) AS all_num FROM `yx_user_multimedia_message` WHERE  `uid` = " . $uid . " AND `create_time` >= '" . $start_time . "' AND `create_time` < '" . $end_time . "' ");
        // echo "\n";
        // echo "SELECT `mobile`,`task_no` FROM `yx_user_multimedia_message_log` WHERE `task_no` IN (SELECT `task_no` FROM `yx_user_multimedia_message` WHERE  `uid` = " . $uid . " AND `create_time` >= '" . $start_time . "' AND `create_time` < '" . $end_time . "' ) AND status_message IN ('REJECTD','REJECT','MA:0001','DB:0141','MA:0001','MK:100D','MK:100C','IC:0151','EXPIRED','-1012','-1013','4442','4446','4014','DELIVRD') GROUP BY `mobile`,`task_no`";die;
        // print_r(count($mul_default_mobile_num));
        // die;
        $success_num = count($mul_success_mobile_num);
        $all_num = $mobile_num[0]['all_num'];
        if ($uid == 223) {
            $unknow_num = $all_num - count($mul_success_mobile_num) - count($mul_default_mobile_num);
        } else {
            $success_num += $all_num - count($mul_success_mobile_num) - count($mul_default_mobile_num);
            $unknow_num = 0;
        }
        $default_num = count($mul_default_mobile_num);
        $ratio = $success_num / $all_num * 100;
        return ['mobile_num' => $all_num, 'num' => $all_num, 'success' => $success_num, 'unknown' => $unknow_num, 'default' => $default_num, 'ratio' => $ratio];
    }

    public function checkSupMessageSendStatus()
    {
        try {
            while (true) {
                $uids = Db::query("SELECT `id`,`pid` FROM yx_users "); //道信核对
                //行业
                foreach ($uids as $key => $value) {
                    // continue;
                    // $start_time = (int) strtotime('-4 days', strtotime(date('Y-m-d', time())));
                    $start_time = (int) strtotime('2020-08-01');
                    if (!Db::query("SELECT `id`,`create_time` FROM yx_user_sup_message WHERE uid  = " . $value['id'] . " AND `create_time` >= '" . $start_time . "' AND `create_time` <= '" . time() . "' ")) {
                        continue;
                    }
                    while (true) {

                        $day_business_result = [];
                        $end_time            = $start_time + 86400;
                        $timekey             = date('Ymd', $start_time);
                        // echo "uid:" . $value['id'] . "" . "timekey:" . $timekey;
                        // echo "\n";
                        $business_id = 11;
                        if ($end_time > time()) {
                            // break;
                            $end_time            = time();
                            $day_business_result = $this->selectSendResultForSupMessage($value['id'], $value['pid'], $start_time, $end_time);
                            if ($day_business_result == false) {
                                break;
                            } else {
                                $day_business_result['uid']         = $value['id'];
                                $day_business_result['timekey']     = $timekey;
                                $day_business_result['business_id'] = $business_id;
                                $has                                = Db::query('SELECT * FROM `yx_statistics_day` WHERE `business_id` = 11 AND `timekey` = ' . $timekey . ' AND `uid` = ' . $value['id']);
                                if ($has) {
                                    Db::table('yx_statistics_day')->where('id', $has[0]['id'])->update([
                                        'success'     => $day_business_result['success'],
                                        'unknown'     => $day_business_result['unknown'],
                                        'default'     => $day_business_result['default'],
                                        'num'         => $day_business_result['num'],
                                        'mobile_num'  => $day_business_result['mobile_num'],
                                        'ratio'       => $day_business_result['ratio'],
                                        'update_time' => time(),
                                    ]);
                                } else {
                                    Db::table('yx_statistics_day')->insert($day_business_result);
                                }
                                break;
                            }
                            //

                        }
                        $day_business_result = $this->selectSendResultForSupMessage($value['id'], $value['pid'], $start_time, $end_time);
                        if ($day_business_result == false) {
                            $start_time = $end_time;
                            continue;
                        }

                        // die;
                        $day_business_result['uid']         = $value['id'];
                        $day_business_result['timekey']     = $timekey;
                        $day_business_result['business_id'] = $business_id;
                        $day_business_result['create_time'] = time();
                        $day_business_result['update_time'] = time();
                        // print_r($day_business_result);
                        $has = Db::query('SELECT * FROM `yx_statistics_day` WHERE `business_id` = 11 AND `timekey` = ' . $timekey . ' AND `uid` = ' . $value['id']);
                        if ($has) {
                            Db::table('yx_statistics_day')->where('id', $has[0]['id'])->update([
                                'success'     => $day_business_result['success'],
                                'unknown'     => $day_business_result['unknown'],
                                'default'     => $day_business_result['default'],
                                'num'         => $day_business_result['num'],
                                'mobile_num'  => $day_business_result['mobile_num'],
                                'ratio'       => $day_business_result['ratio'],
                                'update_time' => time(),
                            ]);
                        } else {
                            Db::table('yx_statistics_day')->insert($day_business_result);
                        }
                        $start_time = $end_time;
                    }
                }
                sleep(900);
            }
        } catch (\Exception $th) {
            //throw $th;
            exception($th);
        }
    }

    public function updateReceipt()
    {
        $nums = Db::query("SELECT COUNT(*) AS `num` FROM `yx_send_code_task_receipt`")[0]['num'];
        // print_r($nums);
        $page = ceil($nums / 100);
        // print_r($page);
        for ($i = 0; $i < $page; $i++) {
            # code...
            $ids = [];
            $receipts =  Db::query("SELECT * FROM `yx_send_code_task_receipt` LIMIT " . $i . ",100 ");
            foreach ($receipts as $key => $value) {

                $task = Db::query("SELECT `task_no` FROM `yx_user_send_code_task` WHERE `id` = " . $value['task_id']);
                if (empty($task)) {
                    continue;
                }
                Db::table('yx_user_send_code_task_log')->where(['task_no' => $task[0]['task_no'], 'mobile' => $value['mobile']])->update(['status_message' => $value['status_message'], 'real_message' => $value['real_message'], 'update_time' => $value['create_time']]);
                $ids[] = $value['id'];
            }
            $ids = join(',', $ids);
            Db::table('yx_send_code_task_receipt')->where("id in ($ids)")->delete();
        }
    }

    public function updateMobileForWhite()
    {
        $mobiles = Db::query("SELECT `mobile` FROM `messagesend`.`yx_mobile_times` WHERE `max_times` = 2 GROUP BY `mobile`");
        if (empty($mobiles)) {
            exit();
        }
        foreach ($mobiles as $key => $value) {
            if (Db::query("SELECT `mobile` FROM yx_whitelist WHERE `mobile` = '" . $value['mobile'] . "'")) {
                continue;
            }
            $insert_data = [];
            $insert_data = [
                'mobile' => $value['mobile'],
                'source' => 2,
                'remark' => '发送频次超过2次',
                'create_time' => time()
            ];
            Db::table('yx_whitelist')->insert($insert_data);
        }
    }

    public function balanceRemind()
    {
        try {
            $user_equities = Db::query('SELECT * FROM  yx_user_equities WHERE `uid` IN (SELECT `id` FROM yx_users WHERE `reservation_service` = 1) AND `balance` > `num_balance`');
            if (!empty($user_equities)) {
                foreach ($user_equities as $key => $value) {
                    $Content = '';
                    if ($value['business_id'] == 5) {
                    }
                    $data['uid']          = 295;
                    $data['source']       = '127.0.0.1';
                    $data['task_content'] = $Content;
                    $data['mobile_content'] =  $value['mobile'];
                    $data['send_num']       = count(explode(',', $value['mobile']));
                    $data['real_num']       = count(explode(',', $value['mobile']));
                    $data['send_length']    = mb_strlen($Content);
                    $data['free_trial']     = 2;
                    $data['send_status']     = 2;
                    $data['task_no']        = 'bus' . date('ymdHis') . substr(uniqid('', true), 15, 8);
                }
            }
        } catch (\Exception $th) {
            //throw $th;
            exception($th);
        }
    }
}
