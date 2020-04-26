<?php

namespace app\console\com;

use app\console\Pzlife;
use cache\Phpredis;
use Config;
use Env;
use Exception;
use think\Db;

//http 通道,通道编号10
class HttpChannelCaiXinChuangLan extends Pzlife
{

    //创蓝
    public function content($content = 59)
    {
        return [
            'account' => 'C0120120',
            'key' => 'OdJugXUcv99bca',
            'send_api'    => 'http://caixin.253.com/api/send', //正式发送地址
            'test_api'    => 'http://115.28.174.119:8080/api/send', //正式发送地址
            'call_api'    => 'http://api.1cloudsp.com/report/up', //上行地址
            'call_back'    => 'http://sendapidev.shyuxi.com/index/send/chuangLanMmsCallBack', //回执回调地址
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
        $content                 = 59;
        $redisMessageCodeSend    = 'index:meassage:code:send:' . $content; //彩信发送任务rediskey
        $redisMessageCodeDeliver = 'index:meassage:multimediamessage:deliver:' . $content; //彩信MsgId
        $user_info               = $this->content();
        /*    $send                 = $redis->rPush($redisMessageCodeSend, json_encode([
        'mar_task_id' => 1,
        'mobile' => '13476024461',
        'content' =>'【鼎业装饰】鼎礼相祝！跨年巨惠！定单送欧派智能晾衣架一套。选欧派产品可秒杀欧派智能马桶999元一个。终极预存大礼，来店给你个超大的惊喜！！！大到超乎您想象！一年只有这一次！电话3236788回T退订',
        ])); */
        try {
            ini_set('user_agent','Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 2.0.50727; .NET CLR 3.0.04506.30; GreenBrowser)');
            while (true) {
                $send_task    = [];
                $send_num     = [];
                $send_content = [];
                $send_title   = [];
                $receive_id   = [];
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
                        if (empty($send_task)) {
                            $send_task[]                           = $send_data['mar_task_id'];
                            $send_title[$send_data['mar_task_id']] = $send_data['title'];
                            //处理内容
                            $real_send_content = [];
                            foreach ($send_data['content'] as $key => $value) {
                                // print_r($value);die;
                                $frame = [];
                                if (!empty($value['content'])) {
                                    $frame['frame'] = $value['num'];
                                    $frame['part'] = 1;
                                    $frame['type'] = 1;
                                    // $frame['content'] = $value['content'];
                                    $frame['content'] = base64_encode($value['content']);
                                    $real_send_content[] = $frame;
                                }
    
                                if (!empty($value['image_path'])) {
                                    $frame = [];
                                    $type = explode('.', $value['image_path']);
    
                                    $frame['frame'] = $value['num'];
                                    $frame['part'] = 1;
                                    if ($type[1] == 'jpg') {
                                        $frame['type'] = 2;
                                    } elseif ($type[1] == 'jpeg') {
                                        $frame['type'] = 2;
                                    } elseif ($type[1] == 'png') {
                                        $frame['type'] = 3;
                                    } elseif ($type[1] == 'gif') {
                                        $frame['type'] = 4;
                                    } elseif ($type[1] == 'gif') {
                                        $frame['type'] = 4;
                                    } elseif ($type[1] == 'wbmp') {
                                        $frame['type'] = 5;
                                    } elseif ($type[1] == 'bmp') {
                                        $frame['type'] = 5;
                                    } elseif ($type[1] == 'amr') {
                                        $frame['type'] = 6;
                                    } elseif ($type[1] == 'midi') {
                                        $frame['type'] = 7;
                                    }
                                    $frame['content'] = base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
                                    $real_send_content[] = $frame;
                                }
                            }
                            // $send_content[$send_data['mar_task_id']] = $send_data['content'];
                            $send_content[$send_data['mar_task_id']] = json_encode($real_send_content);
                        } elseif (!in_array($send_data['mar_task_id'], $send_task)) {
                            $send_task[]                           = $send_data['mar_task_id'];
                            $send_title[$send_data['mar_task_id']] = $send_data['title'];
                            //处理内容
                            $real_send_content = [];
                            foreach ($send_data['content'] as $key => $value) {
                                // print_r($value);die;
                                $frame = [];
                                if (!empty($value['content'])) {
                                    $frame['frame'] = $value['num'];
                                    $frame['part'] = 1;
                                    $frame['type'] = 1;
                                    $frame['content'] = base64_encode($value['content']);
                                    $real_send_content[] = $frame;
                                }
                                $frame = [];
                                if (!empty($value['image_path'])) {
                                    $type = explode('.', $value['image_path']);
    
                                    $frame['frame'] = $value['num'];
                                    $frame['part'] = 1;
                                    if ($type[1] == 'jpg') {
                                        $frame['type'] = 2;
                                    } elseif ($type[1] == 'jpeg') {
                                        $frame['type'] = 2;
                                    } elseif ($type[1] == 'png') {
                                        $frame['type'] = 3;
                                    } elseif ($type[1] == 'gif') {
                                        $frame['type'] = 4;
                                    } elseif ($type[1] == 'gif') {
                                        $frame['type'] = 4;
                                    } elseif ($type[1] == 'wbmp') {
                                        $frame['type'] = 5;
                                    } elseif ($type[1] == 'bmp') {
                                        $frame['type'] = 5;
                                    } elseif ($type[1] == 'amr') {
                                        $frame['type'] = 6;
                                    } elseif ($type[1] == 'midi') {
                                        $frame['type'] = 7;
                                    }
                                    $frame['content'] = base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
                                    $real_send_content[] = $frame;
                                }
                            }
                            // $send_content[$send_data['mar_task_id']] = $send_data['content'];
                            $send_content[$send_data['mar_task_id']] = json_encode($real_send_content);
                        }
                        $send_num[$send_data['mar_task_id']][] = $send_data['mobile'];
                        foreach ($send_num as $send_taskid => $num) {
                            $new_num = array_unique($num);
                            if (count($new_num) >= 500) { //超出500条做一次提交
                                //单条测试
                                $real_send_content = [];
                                $real_send = [];
                                $time = time();
                                $sign = '';
                                $sign = "account=" . $user_info['account']  . "ext_id=" . $send_taskid . "msg=" . $send_content[$send_taskid] . "phones=" . join(',', $new_num) . "timestamp=" . $time . "title=" . $send_title[$send_taskid] . "url=" . $user_info['call_back'] . "key=" . $user_info['key'];
                                $sign = md5($sign);
                                $real_send = [
                                    'account'    => $user_info['account'],
                                    'timestamp' => $time,
                                    'url' => $user_info['call_back'],
                                    'phones'    => join(',', $new_num),
                                    'title'     => $send_title[$send_taskid],
                                    'msg'   => $send_content[$send_taskid],
                                    'ext_id'   => $send_taskid,
                                    'sign'   => $sign,
                                ];
    
                                $res = sendRequest($user_info['send_api'], 'post', $real_send);
                                $result = json_decode($res, true);
                                // $result['code'] = 2;
                                if ($result['code'] == 1) { //提交成功
                                    unset($roallback[$send_taskid]);
                                } else {
                                    foreach ($roallback as $key => $value) {
                                        foreach ($value as $ne => $val) {
                                            $redis->rpush($redisMessageCodeSend, $val);
                                        }
                                    }
                                    print_r($result);
                                    $redis->rpush('index:meassage:code:send' . ":" . 22, json_encode([
                                        'mobile'      => 15201926171,
                                        'content'     => $res
                                    ])); //三体营销通道
                                    exit(); //关闭通道
                                }
                                /*  $result = json_decode(json_encode(simplexml_load_string($res, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
                                    if ($result['returnstatus'] == 'Success') { //成功
                                        $receive_id[$result['taskID']] = $send_taskid;
                                        $redis->hset('index:meassage:code:back_taskno:' . $content, $result['taskID'], $send_taskid);
                                    } elseif ($result['returnstatus'] == 'Faild') { //失败
                                        echo "error:" . $result['message'] . "\n";die;
                                    } */
                                // print_r($result);
                                unset($send_num[$send_taskid]);
                                usleep(12500);
                            }
                        }
                    }
                } while ($send);
                //剩下的号码再做提交
                // print_r($send_content);
                // print_r($send_num);die;
                if (!empty($send_num)) {
                    foreach ($send_num as $send_taskid => $num) {
                        $new_num = array_unique($num);
                        if (empty($new_num)) {
                            continue;
                        }
                        $real_send = [];
                        $sign = '';
                        $time = time();
                        $sign = "account=" . $user_info['account']  . "ext_id=" . $send_taskid . "msg=" . $send_content[$send_taskid] . "phones=" . join(',', $new_num) . "timestamp=" . $time . "title=" . $send_title[$send_taskid] . "url=" . $user_info['call_back'] . "key=" . $user_info['key'];
                        $sign = md5($sign);
                        $real_send = [
                            'account'    => $user_info['account'],
                            'timestamp' => $time,
                            'url' => $user_info['call_back'],
                            // 'sign' => strtolower(md5($user_info['username'].$user_info['password'].date('YmdHis',time()))),
                            'phones'    => join(',', $new_num),
                            'title'     => $send_title[$send_taskid],
                            'msg'   => $send_content[$send_taskid],
                            'ext_id'   => $send_taskid,
                            'sign'   => $sign,
                        ];
                        $res = sendRequest($user_info['send_api'], 'post', $real_send);
                        $result = json_decode($res, true);
                        // print_r($result);
                        // $result['code'] = 2;
                        if ($result['code'] == 1) {
                            unset($roallback[$send_taskid]);
                        } else {
                            foreach ($roallback as $key => $value) {
                                foreach ($value as $ne => $val) {
                                    $redis->rpush($redisMessageCodeSend, $val);
                                }
                            }
                            print_r($result);
                            // $redis->rpush('index:meassage:code:send' . ":" . 22, json_encode([
                            //     'mobile'      => 15201926171,
                            //     'content'     => $res
                            // ])); //三体营销通道
                            exit(); //关闭通道
                        }
                        // print_r($res);
    
                        // $result = explode(',', $res);
                        // if ($result['returnstatus'] == 'Success') { //成功
                        //     $receive_id[$result['taskID']] = $send_taskid;
                        //     $redis->hset('index:meassage:code:back_taskno:' . $content, $result['taskID'], $send_taskid);
                        // } elseif ($result['returnstatus'] == 'Faild') { //失败
                        //     echo "error:" . $result['message'] . "\n";die;
                        // }
                        unset($send_num[$send_taskid]);
                        usleep(12500);
                    }
                }
                // $receive_id = [
                //     '866214' => '15745'
                // ];
                // print_r($receive_id);
                // die;
    
                // print_r($receive_data);die;
                sleep(10);
    
                unset($send_num);
                unset($send_content);
                unset($receive_id);
                echo "success";
            }
        } catch (\Exception $th) {
            //throw $th;
            foreach ($roallback as $key => $value) {
                foreach ($value as $ne => $val) {
                    $redis->rpush($redisMessageCodeSend, $val);
                }
            }

            $log_path = realpath("") . "/error/59.log";
            $myfile = fopen($log_path, 'a+');
            fwrite($myfile, date('Y-m-d H:i:s', time()) . "\n");
            fwrite($myfile, $th . "\n");
            fclose($myfile);

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
