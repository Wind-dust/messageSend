<?php

namespace app\console\com;

use app\console\Pzlife;
use cache\Phpredis;
use Config;
use Env;
use Exception;
use think\Db;

//http 通道,通道编号10
class HttpChannelModelCaiXinWeiGe extends Pzlife
{

    //创蓝
    public function content($content = 59)
    {
        return [
            'account' => 'C0120120',
            'key' => 'OdJugXUcv99bca',
            'channel_dest_id' => '10690456',//接入码
            // 'send_var_api'    => 'http://caixin.253.com/open/sendVarByTemplate', //模板变量发送地址老接口地址
            'send_var_api'    => 'http://47.110.195.237:8081/api/v2/mms/templateSend?timestamp=', //新模板变量发送地址
            // 'send_model_api'    => 'http://caixin.253.com/open/sendByTemplate', //模板非变量发送地址
            'call_api'    => 'http://47.110.195.237:8081/api/v2/sms/moquery?', //上行地址
            'call_back'    => 'http://sendapidev.shyuxi.com/index/send/chuangLanMmsCallBack', //回执回调地址
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

        /*  $sign = '';
        $user_info               = $this->content();
        $time = time();
        $send_data['title'] = '【恭喜您已升级为SEPHORA黑卡会员，快来领取您的专属黑卡权益！】';
        // $send_data['title'] = '大金空调健康购惠州站';
        $send_data['mar_task_id'] = 107;
        echo Config::get('qiniu.domain') . '/' . "20200408/f1a62696f90cb8560db0cd6351174bfd5e8d904c2a736.gif";
        $real_send_content = [
            [
                "frame" => 4,
                "part" => 1, "type" => 1, "content" => base64_encode("【丝芙兰】亲爱的彪1会员：\n\n恭喜您已升级成Sephora黑卡会员！并同时获得1张九折购物券！\n\n黑卡会员8折特卖、生日礼物等更多黑卡独享惊喜等着您！\n\n至任一门店，出示您的白卡和此短信，我们会为您奉上九折券，您马上就能使用，九折券有效期至2020-05-09。\n\n若您还未给我们留下您的生日及通信地址，请快去 www.sephora.cn 会员俱乐部登录后更新您的会员信息，以收到我们的生日礼物及其它黑卡优惠。\n\n－－－－－－－－－－－－ \nSEPHORA客服热线400-670-0055 \n\n编辑短信TD回复至本号码，即可取消赠阅  [SEPHORA]"),
            ],
            [
                "frame" => 4,
                "part" => 1, "type" => 4, "content" => base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . "20200408/f1a62696f90cb8560db0cd6351174bfd5e8d904c2a736.gif")),
            ]
        ]; */
        /*  $real_send_content = [
            [
                "frame" => 4,
                "part" => 1, "type" => 1, "content" => base64_encode('【大金中国】尊敬的用户，您好！为了便于我们及时跟进您的安装进度，并提供丰富的产品资讯及优惠活动，请扫码关注大金官方微信公众号，或微信直接搜索“大金空调中国”关注公众号并回复“AZJD”填写金制家中用户安装进度选项表。退订回T'),
            ],
            [
                "frame" => 4,
                "part" => 1, "type" => 4, "content" => base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . "20200408/f1a62696f90cb8560db0cd6351174bfd5e8d904c2a736.gif")),
            ]
        ]; */
        // $sign = "account=" . $user_info['account'] . "timestamp=" . $time . "url=" . $user_info['call_back'] . "phones=15201926171" . "title=" . $send_data['title'] . "msg=" . json_encode($real_send_content) . "ext_id=" . $send_data['mar_task_id'] . "key=" . $user_info['key'];
        /*    $sign = "account=" . $user_info['account']  . "ext_id=" . $send_data['mar_task_id'] . "msg=" . json_encode($real_send_content) . "phones=15201926171" . "timestamp=" . $time . "title=" . $send_data['title'] . "url=" . $user_info['call_back'] . "key=" . $user_info['key'];
        $sign = md5($sign);
        // print_r($sign);
        // die;
        $real_send = [
            'account'    => $user_info['account'],
            'timestamp' => $time,
            'url' => $user_info['call_back'],
            'phones'    => 15201926171,
            'title'     => $send_data['title'],
            'msg'   => json_encode($real_send_content),
            'ext_id'   => $send_data['mar_task_id'],
            'sign'   => $sign,
        ];
        // 参数写入文件
        $log_path = realpath("") . "/sign.log";
        $myfile = fopen($log_path, 'w');

        foreach ($real_send as $key => $value) {
            fwrite($myfile, $key . ":" . $value . "\n");
        }
        fclose($myfile);
        $res = sendRequest($user_info['send_api'], 'post', $real_send);
        $result = json_decode($res, true);
        print_r($result);
        die;
 */
        $content                 = 122;
        $redisMessageCodeSend    = 'index:meassage:code:send:' . $content; //彩信发送任务rediskey
        $redisMessageCodeDeliver = 'index:meassage:multimediamessage:deliver:' . $content; //彩信MsgId
        $user_info               = $this->content();
        /*    $send                 = $redis->rPush($redisMessageCodeSend, json_encode([
        'mar_task_id' => 1,
        'mobile' => '13476024461',
        'content' =>'【鼎业装饰】鼎礼相祝！跨年巨惠！定单送欧派智能晾衣架一套。选欧派产品可秒杀欧派智能马桶999元一个。终极预存大礼，来店给你个超大的惊喜！！！大到超乎您想象！一年只有这一次！电话3236788回T退订',
        ])); */
        /* 模板方式接口 */
        try {
            ini_set('user_agent','Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 2.0.50727; .NET CLR 3.0.04506.30; GreenBrowser)');
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
                
                // if (date('H') >= 18 || date('H') < 8) {
                //     exit("8点前,18点后通道关闭");
                // }
                $send = $redis->lPop($redisMessageCodeSend);
                // $redis->rpush($redisMessageCodeSend, $send);
                $send_data = [];
                $send_data = json_decode($send, true);
                if ($send_data) {
                    $roallback[$send_data['mar_task_id']][] = $send;
                    // 判断是不是模板变量彩信
                    if (empty($send_data['variable'])) {//非模板变量彩信
                        $redis->rpush($redisMessageCodeSend, $send);
                        $redis->rpush('index:meassage:code:send' . ":" . 85, json_encode([
                            'mobile'      => 15201926171,
                            'content'     => "【钰晰科技】模板彩信通道编号[".$content."]获取到非模板彩信，请及时查看"
                        ])); //三体营销通道
                        //将模板全部发出去
                        exit();
                    }else{
                        // print_r($send_data);die;
                       /*  $template_title[$send_data['template_id']] = $send_data['title'];
                        // print_r($send_variable);die;
                        $model_var_task[$send_data['mar_task_id']]['template_id'] = $send_data['template_id'];
                        $model_var_task[$send_data['mar_task_id']]['templateparam'][] =join(',',$send_data['variable']);
                        // $model_var_task[$send_data['mar_task_id']]['variable'] = $send_data['mobile'].','.$send_data['variable'];
                        $model_var_task[$send_data['mar_task_id']]['develop_code'] = $send_data['develop_code'];
                        $model_var_task[$send_data['mar_task_id']]['phones'][] =$send_data['mobile']; */
                        $real_send = [];
                        $appid = '350171'; //appid由企业彩信平台提供 是
                        $appkey = 'bac3a3c6ea6649f68ba1389d5f688aa9';
                        // $timestamp =  //时间戳访问接口时间 单位：毫秒 是
                    
                        $timestamp = time();
                        $time = microtime(true);
                        //结果：1541053888.5911
                        //在经过处理得到最终结果:
                        $lastTime = (int)($time * 1000);
                        $templateparam = [];
                        $sign = md5($appkey.$appid.$lastTime.$appkey);//数字签名参考sign生成规则 是
                        $report_api = $user_info['send_var_api'].$lastTime.'&appid='.$appid.'&sign='.$sign;
                        foreach ($send_data['variable'] as $key => $value) {
                            # code...
                            $templateparam[] = $value;
                        }
                        
                        // print_r($templateparam);
                        // die;
                        $data = [];
                        $data = [
                            'mms_from' => $send_data['develop_code'],
                            'mms_id' => $send_data['template_id'],
                            'phones' => $send_data['mobile'],
                            'templateparam' => $templateparam
                        ];
                        
                        $headers = [];
                        $headers = [
                            'Content-Type:text/plain'
                        ];
                        $res = $this->sendRequest2($report_api,'post',$data,$headers);
                       
                       
                        // print_r($res);
                        
                        $result = json_decode($res, true);
                        if ($result['code'] == 'T') { //提交成功
                            $redis->hset('index:meassage:code:back_taskno:' . $content, $result['data'], $send_data['mar_task_id']);
                            /* unset($model_var_task[$mkey]);
                            unset($roallback[$mkey]); */
                        } else {
                            //提交失败还有问题需要测试调整
                            $redis->rpush($redisMessageCodeSend, $send);
                            $redis->rpush('index:meassage:code:send' . ":" . 85, json_encode([
                                'mobile'      => 15201926171,
                                'content'     => '【钰晰科技】通道编号'.$content.'提交失败，失败原因：'.$res
                            ])); //三体营销通道
                            exit(); //关闭通道
                        }
                    }

                    
                }else {
                    # code...
                    //获取上行
                    // $real_send = [];
                        $appid = '350171'; //appid由企业彩信平台提供 是
                        $appkey = 'bac3a3c6ea6649f68ba1389d5f688aa9';
                        // $timestamp =  //时间戳访问接口时间 单位：毫秒 是
                    
                        $timestamp = time();
                        $time = microtime(true);
                        //结果：1541053888.5911
                        //在经过处理得到最终结果:
                        $lastTime = (int)($time * 1000);
                        $sign = md5($appid.$lastTime.$appkey);//数字签名参考sign生成规则 是
                        $report_api = $user_info['call_api'].'userid='.$appid.'&ts='.$lastTime.'&sign='.$sign;
                        $headers = [];
                        $headers = [
                            'Content-Type:text/plain'
                        ];
                        $res = $this->sendRequest2($report_api,'get',[],$headers);
                        
                        
                        $result = json_decode($res, true);
                       
                        if (!empty($result['data']))  {
                            print_r($result);
                        }
                        sleep(1);
                }
               
                
            }
        } catch (\Exception $th) {
            //throw $th;
            foreach ($roallback as $key => $value) {
                foreach ($value as $ne => $val) {
                    $redis->rpush($redisMessageCodeSend, $val);
                }
            }

            $log_path = realpath("") . "/error/".$content.".log";
            $myfile = fopen($log_path, 'a+');
            fwrite($myfile, date('Y-m-d H:i:s', time()) . "\n");
            fwrite($myfile, $th . "\n");
            fclose($myfile);
            $redis->rpush('index:meassage:code:send' . ":" . 22, json_encode([
                'mobile'      => 15201926171,
                'content'     => "【钰晰科技】创蓝彩信通道出现异常"
            ])); //三体营销通道

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
}
