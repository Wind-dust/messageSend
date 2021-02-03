<?php

namespace app\console\com;

use app\console\Pzlife;
use cache\Phpredis;
use Config;
use Env;
use Exception;
use think\Db;

//http 通道,通道编号10
class HttpChannelModelSupMessageShiLeGao extends Pzlife
{

    //创蓝
    public function content($content = 133)
    {
        return [
            'loginName' => 'V12815',
            'password' => '47115dca-b13e-435d-bf11-ca5a9891995c',
            'channel_dest_id' => '1',//接入码
            // 'send_var_api'    => 'http://caixin.253.com/open/sendVarByTemplate', //模板变量发送地址老接口地址
            // http://ip:port/api/v2/mms/send?appid=&timestamp=&sign= 
            'send_var_api'    => 'http://api.smsway.com.cn/api/vms/sendVms?', //新模板变量发送地址
            // 'send_model_api'    => 'http://caixin.253.com/open/sendByTemplate', //模板非变量发送地址
            'call_api'    => '', //上行地址
            'call_back'    => '', //回执回调地址
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
        $content                 = 195;
        $redisMessageCodeSend    = 'index:meassage:code:send:' . $content; //彩信发送任务rediskey
        $redisMessageCodeDeliver = 'index:meassage:multimediamessage:deliver:' . $content; //彩信MsgId
        $user_info               = $this->content();
        // $redis->rpush($redisMessageCodeSend,'{"mobile":"15821193682","mar_task_id":5,"from":"yx_user_multimedia_message","send_msg_id":"2020052815400000","uid":1,"template_id":"60461"}');
        // $redis->rpush($redisMessageCodeSend,'{"mobile":"15601607386 ","mar_task_id":5,"from":"yx_user_sup_message","send_msg_id":"2020052815400000","uid":1,"template_id":"3006"}');
        /* 模板方式接口 */
        try {
            ini_set('user_agent','Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 2.0.50727; .NET CLR 3.0.04506.30; GreenBrowser)');
            $send_task    = [];
            $j = 1;
            while (true) {
               
               
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
                $roallback = []; */
                $roallback = [];
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
                        $send_task[$send_data['mar_task_id']]['mms_from'] = 1;
                        $send_task[$send_data['mar_task_id']]['templateId'] = $send_data['template_id'];
                        $send_task[$send_data['mar_task_id']]['mobiles'][] = $send_data['mobile'];
                        $j ++;
                       
                    }else{
                        $send_task[$send_data['mar_task_id']]['mobiles'][] = $send_data['mobile'];
                        $j ++;
                       
                    }
                    // print_r($roallback);
                    if ($j > 10000) {
                        foreach ($send_task as $key => $value) {
                            $request_data = [];
                            $request_data = [
                                'mms_from' => 1,
                                'loginName' => $user_info['loginName'],
                                'password' => $user_info['password'],
                                'templateId' => $value['templateId'],
                                'mobiles' => join(',',$value['mobiles']),
                                'smsId' => $key
                            ];
                            // $timestamp =  //时间戳访问接口时间 单位：毫秒 是
                        
                            $timestamp = time();
                            $time = microtime(true);
                            //结果：1541053888.5911
                            //在经过处理得到最终结果:
                            $lastTime = (int)($time * 1000);
                            // $report_api = $user_info['send_var_api'].$lastTime.'&appid='.$appid.'&sign='.$sign;
                            $headers = [];
                            $headers = [
                                'Content-Type:application/x-www-form-urlencoded;charset=utf-8;'
                            ];
                            // print_r($j);die;
                            $res = $this->sendRequest2($user_info['send_var_api'],'post',$request_data,$headers);
                            $result = json_decode($res,true);
                            if ( isset($result['status'] ) && $result['status'] == 0){
                                $redis->hset('index:meassage:code:back_taskno:silegao', $result['data'], $key); 
                                unset($send_task[$key]);
                                $j = 1;
                            }else{
                                
                                foreach ($roallback as $rkey => $rvalue) {
                                    foreach ($rvalue as $rvkey => $rvalue) {
                                        $redis->rpush($redisMessageCodeSend, $rvalue);
                                    }
                                }
                                $this->writeToRobot($content, $res, '思乐高视频短信通道');
                                exit();
                            }
                            // print_r(base64_encode(json_encode($request_data)));die;
                            // print_r($result);die;
                        }
                    }
                    // print_r($send_data);die;

                    
                }else {
                    # code...
                    //获取上行
                    // $real_send = [];
                    
                        if (!empty($send_task)) {
                            foreach ($send_task as $key => $value) {
                                $request_data = [
                                    'mms_from' => 1,
                                    'loginName' => $user_info['loginName'],
                                    'password' => $user_info['password'],
                                    'templateId' => $value['templateId'],
                                    'mobiles' => join(',',$value['mobiles']),
                                    'smsId' => $key
                                ];
                                // $timestamp =  //时间戳访问接口时间 单位：毫秒 是
                            
                                $timestamp = time();
                                $time = microtime(true);
                                //结果：1541053888.5911
                                //在经过处理得到最终结果:
                                $lastTime = (int)($time * 1000);
                                // $report_api = $user_info['send_var_api'].$lastTime.'&appid='.$appid.'&sign='.$sign;
                                $headers = [];
                                $headers = [
                                    'Content-Type:application/x-www-form-urlencoded;charset=utf-8;'
                                ];
                              
                                $res = $this->sendRequest2($user_info['send_var_api'],'post',$request_data,$headers);
                                //   print_r($res);die;
                                $result = json_decode($res,true);
                                if ( isset($result['status'] ) && $result['status'] == 0){
                                    $redis->hset('index:meassage:code:back_taskno:silegao', $result['data'], $key); 
                                    unset($send_task[$key]);
                                    $j = 1;
                                }else{
                                    
                                    foreach ($roallback as $rkey => $rvalue) {
                                        foreach ($rvalue as $rvkey => $rvalue) {
                                            $redis->rpush($redisMessageCodeSend, $rvalue);
                                        }
                                    }
                                 
                                    $this->writeToRobot($content, $res, '思乐高视频短信通道');
                                    exit();
                                }
                                // print_r(base64_encode(json_encode($request_data)));die;
                               
                            }
                        }
                        sleep(1);
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
            

            $log_path = realpath("") . "/error/".$content.".log";
            $myfile = fopen($log_path, 'a+');
            fwrite($myfile, date('Y-m-d H:i:s', time()) . "\n");
            fwrite($myfile, $th . "\n");
            fclose($myfile);
            $this->writeToRobot($content, $th, '领道视频短信联电通道');
           /*  $redis->rpush('index:meassage:code:send' . ":" . 22, json_encode([
                'mobile'      => 15201926171,
                'content'     => "【钰晰科技】微格彩信通道出现异常"
            ])); //三体营销通道 */

        }
        exception($th);
        
    }

    public function getSendTask($id)
    {
        $task = Db::query("SELECT `task_no`,`uid` FROM yx_user_multimedia_message WHERE `id` =" . $id);
        if ($task) {
            return $task[0];
        }
        return false;
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
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
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

    function writeToRobot($content, $error_data, $title)
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
            'Content-Type:application/json'
        ];
        $this->sendRequest3($api, 'post', $check_data, $headers);
    }

    function sendRequest3($requestUrl, $method = 'get', $data = [], $headers)
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
