<?php

namespace app\console\com;

use app\console\Pzlife;
use cache\Phpredis;
use Exception;
use think\Db;

//http 通道,通道编号10
class HttpChannelModelSupMessageKuaiLeWangShiLianTong extends Pzlife
{

    //创蓝
    public function content($content = 133)
    {
        return [
            'CpName' => 'flltsp',
            "uid" => 1081,
            'Password' => 'flltsp123',
            'ApiPassword' => '9488c43c',
            'channel_dest_id' => '1', //接入码
            // 'send_var_api'    => 'http://caixin.253.com/open/sendVarByTemplate', //模板变量发送地址老接口地址
            // http://ip:port/api/v2/mms/send?appid=&timestamp=&sign=
            'send_var_api' => 'http://wqvb.cn:9420/send.do', //新模板变量发送地址
            // 'send_model_api'    => 'http://caixin.253.com/open/sendByTemplate', //模板非变量发送地址
            // 'call_api'    => 'http://47.101.30.221:8081/api/v2/sms/moquery?', //上行地址
            'call_back' => '', //回执回调地址
            'overage_api' => '', //余额地址
            // 'receive_api' => 'http://api.1cloudsp.com/report/status', //回执，报告
        ];

        //'account'    => 'yuxi',
        // 'appid'    => '674',
    }
    // ：http://ip:port/api/v2/mms/templateSend?appid=&timestamp=&sign=
    public function Send()
    {
        $redis = Phpredis::getConn();
        // $a_time = 0;

        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        $content = 187;
        $redisMessageCodeSend = 'index:meassage:code:send:' . $content; //彩信发送任务rediskey
        $user_info = $this->content();
        /*  $redis->rpush($redisMessageCodeSend,'{"mobile":"15821193682","mar_task_id":5,"from":"yx_user_multimedia_message","send_msg_id":"2020052815400000","uid":1,"template_id":"60461"}');
        $redis->rpush($redisMessageCodeSend,'{"mobile":"15172413692","mar_task_id":5,"from":"yx_user_multimedia_message","send_msg_id":"2020052815400000","uid":1,"template_id":"60461"}'); */
        /* 模板方式接口 */
        try {
            ini_set('user_agent', 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 2.0.50727; .NET CLR 3.0.04506.30; GreenBrowser)');
            $send_task = [];
            $roallback = [];
            while (true) {
                $j = 1;

                /*  $send_task    = [];
                $model_var_task = [];//模板变量彩信任务
                $model_task = [];//模板彩信任务
                $template_info = [];
                $template_title = [];
                $send_num     = [];
                $send_content = [];
                $send_title   = [];
                $receive_id   = [];
                $image_data = [];
                 */
               
                // if (date('H') >= 18 || date('H') < 8) {
                //     exit("8点前,18点后通道关闭");
                // }
                $send = $redis->lPop($redisMessageCodeSend);
                // $redis->rpush($redisMessageCodeSend, $send);
                $send_data = [];
                $send_data = json_decode($send, true);
                if (!empty($send_data)) {
                    $roallback[$send_data['mar_task_id']][] = $send;
                    // 判断是不是模板变量彩信

                    if (!isset($send_task[$send_data['mar_task_id']])) {
                        $send_task[$send_data['mar_task_id']]['develop_no'] = $send_data['develop_no'];
                        $send_task[$send_data['mar_task_id']]['mms_from'] = 1;
                        $send_task[$send_data['mar_task_id']]['Content'] = $send_data['template_id'];
                        $send_task[$send_data['mar_task_id']]['phones'][] = $send_data['mobile'];
                        $j++;
                    } else {
                        $send_task[$send_data['mar_task_id']]['phones'][] = $send_data['mobile'];
                        $j++;
                    }
                    // print_r($roallback);
                    if ($j > 2000) {
                        foreach ($send_task as $key => $value) {
                            $request_data = [];
                        $uid = $user_info['uid']; //appid由企业彩信平台提供 是
                        $CpPassword = $user_info['ApiPassword'];
                        $tm = $this->getMillisecond();
                        $request_data = [
                            // 'mms_from' => 1,
                            'uid' => $uid,
                            'password' => md5($CpPassword . $tm),
                            'tm' => $tm,
                            'tid' => $value['Content'],
                            'mobile' => $value['phones'],
                            'extend' => $value['develop_no'],
                        ];
                        
                        $res = $this->http_post_json($user_info['send_var_api'], json_encode( $request_data));
                        $result = json_decode($res, true);
                        if (isset($result['code']) && $result['code'] == 0) {
                            $redis->hset('index:meassage:code:back_taskno:kuailewangshi', $result['msgid'], $key);
                            unset($send_task[$key]);
                            $j = 1;
                        } else {

                            foreach ($roallback as $rkey => $rvalue) {
                                foreach ($rvalue as $rvkey => $rvalue) {
                                    $redis->rpush($redisMessageCodeSend, $rvalue);
                                }
                            }
                            $this->writeToRobot($content, $res, '快乐网视视频短信通道');
                            exit();
                        }
                            // print_r(base64_encode(json_encode($request_data)));die;
                            // print_r($result);die;
                        }
                    }
                    // print_r($send_data);die;

                } else {
                    # code...
                    //获取上行
                    // $real_send = [];

                    foreach ($send_task as $key => $value) {
                        $request_data = [];
                        $uid = $user_info['uid']; //appid由企业彩信平台提供 是
                        $CpPassword = $user_info['ApiPassword'];
                        $tm = $this->getMillisecond();
                        $request_data = [
                            // 'mms_from' => 1,
                            'uid' => $uid,
                            'password' => md5($CpPassword . $tm),
                            'tm' => $tm,
                            'tid' => $value['Content'],
                            'mobile' => $value['phones'],
                            'extend' => $value['develop_no'],
                        ];
                        
                        $res = $this->http_post_json($user_info['send_var_api'], json_encode( $request_data));
                        $result = json_decode($res, true);
                        if (isset($result['code']) && $result['code'] == 0) {
                            $redis->hset('index:meassage:code:back_taskno:kuailewangshi', $result['msgid'], $key);
                            unset($send_task[$key]);
                            $j = 1;
                        } else {

                            foreach ($roallback as $rkey => $rvalue) {
                                foreach ($rvalue as $rvkey => $rvalue) {
                                    $redis->rpush($redisMessageCodeSend, $rvalue);
                                }
                            }
                            $this->writeToRobot($content, $res, '快乐网视视频短信通道');
                            exit();
                        }
                    }
                }
            }
        } catch (\Exception $th) {
            //throw $th;
            if (!empty($roallback)) {
                foreach ($roallback as $key => $value) {
                    foreach ($value as $ne => $val) {
                        $redis->rpush($redisMessageCodeSend, $val);
                    }
                }
            }

            $log_path = realpath("") . "/error/" . $content . ".log";
            $myfile = fopen($log_path, 'a+');
            fwrite($myfile, date('Y-m-d H:i:s', time()) . "\n");
            fwrite($myfile, $th . "\n");
            fclose($myfile);
            $this->writeToRobot($content, $th, '艾麒视频短信移动通道');
            /*  $redis->rpush('index:meassage:code:send' . ":" . 22, json_encode([
        'mobile'      => 15201926171,
        'content'     => "【钰晰科技】微格彩信通道出现异常"
        ])); //三体营销通道 */
        }
        exception($th);
    }

    private function getMillisecond() {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
    }

    public function getSendTask($id)
    {
        $task = Db::query("SELECT `task_no`,`uid` FROM yx_user_multimedia_message WHERE `id` =" . $id);
        if ($task) {
            return $task[0];
        }
        return false;
    }

        /**
 * PHP发送Json对象数据
 *
 * @param $url 请求url
 * @param $jsonStr 发送的json字符串
 * @return array
 */
function http_post_json($url, $jsonStr)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length: ' . strlen($jsonStr)
        )
    );
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
 
    return $response;
}

    public function sendRequest2($requestUrl, $method = 'get', $data = [], $headers)
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
            curl_setopt($curl, CURLOPT_POSTFIELDS, base64_encode(json_encode($data)));
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

    public function writeToRobot($content, $error_data, $title)
    {
        $api = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=fa1c9682-f617-45f9-a6a3-6b65f671b457';
        // $api = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=693a91f6-7xxx-4bc4-97a0-0ec2sifa5aaa';
        $check_data = [];
        $check_data = [
            'msgtype' => "text",
            'text' => [
                "content" => "Hi，错误提醒机器人\n您有一条通道出现故障\n通道编号【" . $content . "】\n【错误信息】：" . $error_data . "\n通道名称【" . $title . "】",
            ],
        ];
        $headers = [
            'Content-Type:application/json',
        ];
        $this->sendRequest3($api, 'post', $check_data, $headers);
    }

    public function sendRequest3($requestUrl, $method = 'get', $data = [], $headers)
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
}
