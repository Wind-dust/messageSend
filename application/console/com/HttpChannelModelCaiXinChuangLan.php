<?php

namespace app\console\com;

use app\console\Pzlife;
use cache\Phpredis;
use Config;
use Env;
use Exception;
use think\Db;

//http 通道,通道编号10
class HttpChannelModelCaiXinChuangLan extends Pzlife
{

    //创蓝
    public function content($content = 59)
    {
        return [
            'account' => 'C0120120',
            'key' => 'OdJugXUcv99bca',
            'send_var_api'    => 'http://caixin.253.com/open/sendVarByTemplate', //模板变量发送地址
            'send_model_api'    => 'http://caixin.253.com/open/sendByTemplate', //模板非变量发送地址
            'call_api'    => 'http://api.1cloudsp.com/report/up', //上行地址
            'call_back'    => 'http://sendapidev.shyuxi.com/index/send/chuangLanMmsModelCallBack', //回执回调地址
            'overage_api' => '', //余额地址
            // 'receive_api' => 'http://api.1cloudsp.com/report/status', //回执，报告
        ];

        //'account'    => 'yuxi',
        // 'appid'    => '674',
    }

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
        $content                 = 103;
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
                $send_task    = [];
                $model_var_task = [];//模板变量彩信任务
                $model_task = [];//模板彩信任务
                $send_num     = [];
                $send_content = [];
                $send_title   = [];
                $receive_id   = [];
                $image_data = [];
                $roallback = [];
                
                // if (date('H') >= 18 || date('H') < 8) {
                //     exit("8点前,18点后通道关闭");
                // }
    
                do {
                    $send = $redis->lPop($redisMessageCodeSend);
                    // $redis->rpush($redisMessageCodeSend, $send);
                    $send_data = json_decode($send, true);
                    if ($send_data) {
                        $roallback[$send_data['mar_task_id']][] = $send;
                        // 判断是不是模板彩信
                        if (empty($send_data['template_id'])) {//非模板彩信
                            // $redis->rpush($redisMessageCodeSend, $send);
                            $redis->rpush('index:meassage:code:send' . ":" . 85, json_encode([
                                'mobile'      => 15201926171,
                                'content'     => "【钰晰科技】模板彩信通道编号[".$content."]获取到非模板彩信，请及时查看"
                            ])); //三体营销通道
                            //将模板全部发出去
                            if (!empty($model_var_task)) {
                                foreach ($model_var_task as $mkey => $mvalue) {
                                    $real_send = [];
                                    $time = time();
                                    $sign = '';
                                    $sign = "account=" . $user_info['account']  . "ext_id=" . $mkey . "templateId=".$mvalue['template_id']. "timestamp=" . $time .  "url=" . $user_info['call_back'] ."variable=".$mvalue['variable'];
                                    $sign = hash_hmac('sha256',$sign,$user_info['key']);
                                    $real_send = [
                                        'account'    => $user_info['account'],
                                        'ext_id'   =>  $mkey,
                                        'templateId'     => $mvalue['template_id'],
                                        'timestamp' => $time,
                                        'url' => $user_info['call_back'],
                                        'variable'   => $mvalue['variable'],
                                        'sign'   => $sign,
                                    ];
            
                                    $res = sendRequest($user_info['send_var_api'], 'post', $real_send);
                                    $result = json_decode($res, true);
                                    if ($result['code'] == 1) { //提交成功
                                        unset($model_var_task[$mkey]);
                                        unset($roallback[$mkey]);
                                    } else {
                                        //提交失败还有问题需要测试调整
                                        foreach ($roallback as $key => $value) {
                                            foreach ($value as $ne => $val) {
                                                $redis->rpush($redisMessageCodeSend, $val);
                                            }
                                        }
                                        $redis->rpush('index:meassage:code:send' . ":" . 85, json_encode([
                                            'mobile'      => 15201926171,
                                            'content'     => '【钰晰科技】通道编号'.$content.'提交失败，失败原因：'.$res
                                        ])); //三体营销通道
                                        exit(); //关闭通道
                                    }
                                    usleep(5000);
                                }
                                $model_var_task = [];
                            }
            
                            if (!empty($model_task)) {
                                foreach ($model_task as $mkey => $mvalue) {
                                    $real_send = [];
                                    $time = time();
                                    $sign = '';
                                    $sign = "account=" . $user_info['account']  . "ext_id=" . $mkey ."phones=".$mvalue['mobile'] ."templateId=".$send_data['template_id']. "timestamp=" . $time .  "url=" . $user_info['call_back'];
                                    $sign = hash_hmac('sha256',$sign,$user_info['key']);
                                        $real_send = [
                                            'account'    => $user_info['account'],
                                            'ext_id'   =>  $mkey,
                                            'templateId'     => $mvalue['template_id'],
                                            'timestamp' => $time,
                                            'url' => $user_info['call_back'],
                                            'phones'   => $mvalue['mobile'],
                                            'sign'   => $sign,
                                        ];
            
                                    $res = sendRequest($user_info['send_model_api'], 'post', $real_send);
                                    $result = json_decode($res, true);
                                    if ($result['code'] == 1) { //提交成功
                                        unset($model_task[$mkey]);
                                        unset($roallback[$mkey]);
                                    } else {
                                        //提交失败还有问题需要测试调整
                                        foreach ($roallback as $key => $value) {
                                            foreach ($value as $ne => $val) {
                                                $redis->rpush($redisMessageCodeSend, $val);
                                            }
                                        }
                                        $redis->rpush('index:meassage:code:send' . ":" . 85, json_encode([
                                            'mobile'      => 15201926171,
                                            'content'     => '【钰晰科技】通道编号'.$content.'提交失败，失败原因：'.$res
                                        ])); //三体营销通道
                                        exit(); //关闭通道
                                    }
                                    usleep(5000);
                                }
                                $model_task = [];
                            
                            }

                            exit();
                        }else{
                            // print_r($send_data);die;
                            //判断是否为模板变量彩信
                            if (!empty($send_data['variable'])) {//模板变量
                                
                                $send_var = [];
                                if (!empty($model_var_task[$send_data['mar_task_id']])) {
                                    $model_var_task[$send_data['mar_task_id']]['variable'] = $model_var_task[$send_data['mar_task_id']]['variable'].';'.$send_data['mobile'].','.$send_data['variable'];
                                    $model_var_task[$send_data['mar_task_id']]['send_num'] ++;
                                    if ($model_var_task[$send_data['mar_task_id']]['send_num'] > 2000) {//一个包大于2000
                                        $real_send = [];
                                        $time = time();
                                        $sign = '';
                                        $sign = "account=" . $user_info['account']  . "ext_id=" . $send_data['mar_task_id'] . "templateId=".$send_data['template_id']. "timestamp=" . $time .  "url=" . $user_info['call_back'] ."variable=".$model_var_task[$send_data['mar_task_id']]['variable'];
                                        $sign = hash_hmac('sha256',$sign,$user_info['key']);
                                        $real_send = [
                                            'account'    => $user_info['account'],
                                            'ext_id'   =>  $send_data['mar_task_id'],
                                            'templateId'     => $send_data['template_id'],
                                            'timestamp' => $time,
                                            'url' => $user_info['call_back'],
                                            'variable'   => $model_var_task[$send_data['mar_task_id']]['variable'],
                                            'sign'   => $sign,
                                        ];
            
                                        $res = sendRequest($user_info['send_var_api'], 'post', $real_send);
                                        $result = json_decode($res, true);
                                        if ($result['code'] == 1) { //提交成功
                                            unset($model_var_task[$send_data['mar_task_id']]);
                                            unset($roallback[$send_data['mar_task_id']]);
                                        } else {
                                            //提交失败还有问题需要测试调整
                                            foreach ($roallback as $key => $value) {
                                                foreach ($value as $ne => $val) {
                                                    $redis->rpush($redisMessageCodeSend, $val);
                                                }
                                            }
                                            $redis->rpush('index:meassage:code:send' . ":" . 85, json_encode([
                                                'mobile'      => 15201926171,
                                                'content'     => '【钰晰科技】通道编号'.$content.'提交失败，失败原因：'.$res
                                            ])); //三体营销通道
                                            exit(); //关闭通道
                                        }
                                        usleep(5000);
                                    }
                                }else{
                                   
                                    $model_var_task[$send_data['mar_task_id']]['template_id'] = $send_data['template_id'];
                                    $model_var_task[$send_data['mar_task_id']]['variable'] = $send_data['mobile'].','.$send_data['variable'];
                                    $model_var_task[$send_data['mar_task_id']]['send_num'] = 1;
                                }

                            }else{//普通模板
                                if (!empty($model_task[$send_data['mar_task_id']])) {
                                    $model_task[$send_data['mar_task_id']]['mobile'] =$model_task[$send_data['mar_task_id']]['mobile'].','.$send_data['mobile'];
                                    $model_task[$send_data['mar_task_id']]['send_num'] ++;
                                    if ($model_task[$send_data['mar_task_id']]['send_num'] > 2000) {
                                        $real_send = [];
                                        $time = time();
                                        $sign = '';
                                        $sign = "account=" . $user_info['account']  . "ext_id=" . $send_data['mar_task_id'] ."phones=".$model_task[$send_data['mar_task_id']]['mobile'] ."templateId=".$send_data['template_id']. "timestamp=" . $time .  "url=" . $user_info['call_back'];
                                        $sign = hash_hmac('sha256',$sign,$user_info['key']);
                                        $real_send = [
                                            'account'    => $user_info['account'],
                                            'ext_id'   =>  $send_data['mar_task_id'],
                                            'templateId'     => $send_data['template_id'],
                                            'timestamp' => $time,
                                            'url' => $user_info['call_back'],
                                            'phones'   => $model_task[$send_data['mar_task_id']]['mobile'],
                                            'sign'   => $sign,
                                        ];
            
                                        $res = sendRequest($user_info['send_model_api'], 'post', $real_send);
                                        $result = json_decode($res, true);
                                        if ($result['code'] == 1) { //提交成功
                                            unset($model_task[$send_data['mar_task_id']]);
                                            unset($roallback[$send_data['mar_task_id']]);
                                        } else {
                                            //提交失败还有问题需要测试调整
                                            foreach ($roallback as $key => $value) {
                                                foreach ($value as $ne => $val) {
                                                    $redis->rpush($redisMessageCodeSend, $val);
                                                }
                                            }
                                            $redis->rpush('index:meassage:code:send' . ":" . 85, json_encode([
                                                'mobile'      => 15201926171,
                                                'content'     => '【钰晰科技】通道编号'.$content.'提交失败，失败原因：'.$res
                                            ])); //三体营销通道
                                            exit(); //关闭通道
                                        }
                                        usleep(5000);
                                    }
                                }else{
                                    
                                    $model_task[$send_data['mar_task_id']]['mobile'] =$send_data['mobile'];
                                    $model_task[$send_data['mar_task_id']]['template_id'] =$send_data['template_id'];
                                    $model_task[$send_data['mar_task_id']]['send_num'] = 1;
                                }
                            }
                            
                            //超过10个任务包开始发送
                            if (count($model_var_task) > 10) {
                                foreach ($model_var_task as $mkey => $mvalue) {
                                    $real_send = [];
                                    $time = time();
                                    $sign = '';
                                    $sign = "account=" . $user_info['account']  . "ext_id=" . $mkey . "templateId=".$mvalue['template_id']. "timestamp=" . $time .  "url=" . $user_info['call_back'] ."variable=".$mvalue['variable'];
                                    $sign = hash_hmac('sha256',$sign,$user_info['key']);
                                    $real_send = [
                                        'account'    => $user_info['account'],
                                        'ext_id'   =>  $mkey,
                                        'templateId'     => $mvalue['template_id'],
                                        'timestamp' => $time,
                                        'url' => $user_info['call_back'],
                                        'variable'   => $mvalue['variable'],
                                        'sign'   => $sign,
                                    ];
        
                                    $res = sendRequest($user_info['send_var_api'], 'post', $real_send);
                                    $result = json_decode($res, true);
                                    if ($result['code'] == 1) { //提交成功
                                        unset($model_var_task[$mkey]);
                                        unset($roallback[$mkey]);
                                    } else {
                                        //提交失败还有问题需要测试调整
                                        foreach ($roallback as $key => $value) {
                                            foreach ($value as $ne => $val) {
                                                $redis->rpush($redisMessageCodeSend, $val);
                                            }
                                        }
                                        $redis->rpush('index:meassage:code:send' . ":" . 85, json_encode([
                                            'mobile'      => 15201926171,
                                            'content'     => '【钰晰科技】通道编号'.$content.'提交失败，失败原因：'.$res
                                        ])); //三体营销通道
                                        exit(); //关闭通道
                                    }
                                    usleep(5000);
                                }
                                $model_var_task = [];
                            }
                            if (count($model_task) > 10) {
                                foreach ($model_task as $mkey => $mvalue) {
                                    $real_send = [];
                                    $time = time();
                                    $sign = '';
                                    $sign = "account=" . $user_info['account']  . "ext_id=" . $mkey ."phones=".$mvalue['mobile'] ."templateId=".$send_data['template_id']. "timestamp=" . $time .  "url=" . $user_info['call_back'];
                                    $sign = hash_hmac('sha256',$sign,$user_info['key']);
                                        $real_send = [
                                            'account'    => $user_info['account'],
                                            'ext_id'   =>  $mkey,
                                            'templateId'     => $mvalue['template_id'],
                                            'timestamp' => $time,
                                            'url' => $user_info['call_back'],
                                            'phones'   => $mvalue['mobile'],
                                            'sign'   => $sign,
                                        ];
        
                                    $res = sendRequest($user_info['send_model_api'], 'post', $real_send);
                                    $result = json_decode($res, true);
                                    if ($result['code'] == 1) { //提交成功
                                        unset($model_task[$mkey]);
                                        unset($roallback[$mkey]);
                                    } else {
                                        //提交失败还有问题需要测试调整
                                        foreach ($roallback as $key => $value) {
                                            foreach ($value as $ne => $val) {
                                                $redis->rpush($redisMessageCodeSend, $val);
                                            }
                                        }
                                        $redis->rpush('index:meassage:code:send' . ":" . 85, json_encode([
                                            'mobile'      => 15201926171,
                                            'content'     => '【钰晰科技】通道编号'.$content.'提交失败，失败原因：'.$res
                                        ])); //三体营销通道
                                        exit(); //关闭通道
                                    }
                                    usleep(5000);
                                }
                                $model_task = [];
                            }
                            
                        }

                        
                    }
                } while ($send);
                //剩下的号码再做提交
                // print_r($model_var_task);
                // print_r($send_num);die;
                if (!empty($model_var_task)) {
                    foreach ($model_var_task as $mkey => $mvalue) {
                        $real_send = [];
                        $time = time();
                        $sign = '';
                        $sign = "account=" . $user_info['account']  . "ext_id=" . $mkey . "templateId=".$mvalue['template_id']. "timestamp=" . $time .  "url=" . $user_info['call_back'] ."variable=".$mvalue['variable'];
                        $sign = hash_hmac('sha256',$sign,$user_info['key']);
                        $real_send = [
                            'account'    => $user_info['account'],
                            'ext_id'   =>  $mkey,
                            'templateId'     => $mvalue['template_id'],
                            'timestamp' => $time,
                            'url' => $user_info['call_back'],
                            'variable'   => $mvalue['variable'],
                            'sign'   => $sign,
                        ];

                        $res = sendRequest($user_info['send_var_api'], 'post', $real_send);
                        $result = json_decode($res, true);
                        // print_r($result);
                        if ($result['code'] == 1) { //提交成功
                            unset($model_var_task[$mkey]);
                            unset($roallback[$mkey]);
                        } else {
                            //提交失败还有问题需要测试调整
                            foreach ($roallback as $key => $value) {
                                foreach ($value as $ne => $val) {
                                    $redis->rpush($redisMessageCodeSend, $val);
                                }
                            }
                            $redis->rpush('index:meassage:code:send' . ":" . 85, json_encode([
                                'mobile'      => 15201926171,
                                'content'     => '【钰晰科技】通道编号'.$content.'提交失败，失败原因：'.$res
                            ])); //三体营销通道
                            exit(); //关闭通道
                        }
                        usleep(5000);
                    }
                    $model_var_task = [];
                }

                if (!empty($model_task)) {
                    foreach ($model_task as $mkey => $mvalue) {
                        $real_send = [];
                        $time = time();
                        $sign = '';
                        $sign = "account=" . $user_info['account']  . "ext_id=" . $mkey ."phones=".$mvalue['mobile'] ."templateId=".$send_data['template_id']. "timestamp=" . $time .  "url=" . $user_info['call_back'];
                        $sign = hash_hmac('sha256',$sign,$user_info['key']);
                            $real_send = [
                                'account'    => $user_info['account'],
                                'ext_id'   =>  $mkey,
                                'templateId'     => $mvalue['template_id'],
                                'timestamp' => $time,
                                'url' => $user_info['call_back'],
                                'phones'   => $mvalue['mobile'],
                                'sign'   => $sign,
                            ];

                        $res = sendRequest($user_info['send_model_api'], 'post', $real_send);
                        $result = json_decode($res, true);
                        print_r($result);
                        if ($result['code'] == 1) { //提交成功
                            unset($model_task[$mkey]);
                            unset($roallback[$mkey]);
                        } else {
                            //提交失败还有问题需要测试调整
                            foreach ($roallback as $key => $value) {
                                foreach ($value as $ne => $val) {
                                    $redis->rpush($redisMessageCodeSend, $val);
                                }
                            }
                            $redis->rpush('index:meassage:code:send' . ":" . 85, json_encode([
                                'mobile'      => 15201926171,
                                'content'     => '【钰晰科技】通道编号'.$content.'提交失败，失败原因：'.$res
                            ])); //三体营销通道
                            exit(); //关闭通道
                        }
                        usleep(5000);
                    }
                    $model_task = [];
                
                }
                // unset($model_var_task);
                // $receive_id = [
                //     '866214' => '15745'
                // ];
                // print_r($receive_id);
                // die;
    
                // print_r($receive_data);die;
                sleep(10);
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
        
    }

    public function getSendTask($id)
    {
        $task = Db::query("SELECT `task_no`,`uid` FROM yx_user_multimedia_message WHERE `id` =" . $id);
        if ($task) {
            return $task[0];
        }
        return false;
    }
}
