<?php

namespace app\console\com;

use app\console\Pzlife;
use cache\Phpredis;
use Config;
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
            'send_api' => 'http://caixin.253.com/api/send', //正式发送地址
            'test_api' => 'http://115.28.174.119:8080/api/send', //正式发送地址
            'call_api' => 'http://api.1cloudsp.com/report/up', //上行地址
            'call_back' => 'http://sendapidev.shyuxi.com/index/send/chuangLanMmsCallBack', //回执回调地址
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
        // echo Config::get('qiniu.domain') . '/' . "20200408/f1a62696f90cb8560db0cd6351174bfd5e8d904c2a736.gif";
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
        // // print_r($sign);
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
        // print_r($result);
        die;
         */
        $content = 59;
        $redisMessageCodeSend = 'index:meassage:code:send:' . $content; //彩信发送任务rediskey
        $redisMessageCodeDeliver = 'index:meassage:multimediamessage:deliver:' . $content; //彩信MsgId
        $user_info = $this->content();
        $send = $redis->rPush($redisMessageCodeSend, '{"mobile":"13764272451","title":"\u6765\u81ea\u3010\u4e1d\u8299\u5170\u3011\uff1a\u4ef7\u503c699\u5143\u5723\u8bde\u9650\u5b9a\u793c\u5305\u9650\u65f6\u6ee1\u8d60\uff01\u3010test\u3011","mar_task_id":855738,"content":[{"id":1711304,"multimedia_message_id":"855738","num":1,"name":"\u7b2c1\u5e27","content":"","image_path":"http:\/\/imagesdev.shyuxi.com\/20201209\/e36fafb7392d10960b4487894834bc1c5fd06959c9557.jpg","image_type":"jpg","update_time":"2020-12-09 14:17:11","create_time":"2020-12-09 14:17:11","delete_time":null},{"id":1711305,"multimedia_message_id":"855738","num":2,"name":"\u7b2c2\u5e27","content":"\u3010\u4e1d\u8299\u5170\u3011\u4ef7\u503c699\u5143\u5723\u8bde\u9650\u5b9a\u793c\u5305\u9650\u65f6\u6ee1\u8d60\uff01\n\n\u65b0\u613f\uff0c\u5c31\u8000\u4e0d\u4e00\u6837\uff01\n\u5373\u65e5\u8d77\u81f312\/30\uff0c\u5168\u573a\u4efb\u610f\u8d2d\u4e70\u6ee11288\uff08\u542b\u4e00\u4ef6\u72ec\u5bb6\u4ea7\u54c1\uff09\uff0c\u5373\u53ef\u83b7\u8d60\u4ef7\u503c699\u5143\u4e1d\u8299\u5170\u5723\u8bde\u9650\u5b9a\u65b0\u613f\u5305\u548c\u968f\u884c\u5c0f\u68377\u4ef6\u5957\u3002\u9650\u91cf2\u4e07\u4efd\uff0c\u8d60\u5b8c\u5373\u6b62\u3002\n\uff0d\uff0d\uff0d\uff0d\uff0d\uff0d\uff0d\uff0d\uff0d\uff0d\n\u4e1d\u8299\u5170\u5ba2\u670d\u70ed\u7ebf\uff1a400-670-0055\n\u4e1d\u8299\u5170\u5b98\u7f51\uff1awww.sephora.cn\n\u7f16\u8f91\u77ed\u4fe1TD\u56de\u590d\u81f3\u672c\u53f7\u7801\uff0c\u53ef\u53d6\u6d88\u8d60\u9605","image_path":"","image_type":"","update_time":"2020-12-09 14:17:12","create_time":"2020-12-09 14:17:12","delete_time":null}],"from":"yx_user_multimedia_message","send_msg_id":"","uid":1}');
        try {
            ini_set('user_agent', 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 2.0.50727; .NET CLR 3.0.04506.30; GreenBrowser)');
            while (true) {
                $send_task = [];
                $send_num = [];
                $send_content = [];
                $send_title = [];
                $receive_id = [];
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
                        if (empty($send_task)) {
                            $send_task[] = $send_data['mar_task_id'];
                            $send_title[$send_data['mar_task_id']] = $send_data['title'];
                            //处理内容
                            $real_send_content = [];
                            foreach ($send_data['content'] as $key => $value) {
                                // // print_r($value);die;
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
                                    if (strpos($value['image_path'], 'shyuxi') == false) {
                                        // $value['image_path'] = file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']);
                                        // filtraImage(Config::get('qiniu.domain'), $value['image_path']);
                                    } else {
                                        $value['image_path'] = filtraImage(Config::get('qiniu.domain'), $value['image_path']);
                                    }
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

                                    // print_r($value['image_path']);die;
                                    $md5 = md5(Config::get('qiniu.domain') . '/' . $value['image_path']);
                                    if (isset($image_data[$md5])) {
                                        $frame['content'] = $image_data[$md5];
                                    } else {
                                        $imagebase = base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
                                        $image_data[$md5] = $imagebase;
                                        $frame['content'] = $imagebase;
                                    }
                                    // $frame['content'] = base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
                                    $real_send_content[] = $frame;
                                }
                            }
                            // $send_content[$send_data['mar_task_id']] = $send_data['content'];
                            $send_content[$send_data['mar_task_id']] = json_encode($real_send_content);
                        } elseif (!in_array($send_data['mar_task_id'], $send_task)) {
                            $send_task[] = $send_data['mar_task_id'];
                            $send_title[$send_data['mar_task_id']] = $send_data['title'];
                            //处理内容
                            $real_send_content = [];
                            foreach ($send_data['content'] as $key => $value) {
                                // // print_r($value);die;
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
                                    if (strpos($value['image_path'], 'shyuxi') == false) {
                                    } else {
                                        $value['image_path'] = filtraImage(Config::get('qiniu.domain'), $value['image_path']);
                                    }
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

                                    // print_r($value['image_path']);die;
                                    $md5 = md5(Config::get('qiniu.domain') . '/' . $value['image_path']);
                                    if (isset($image_data[$md5])) {
                                        $frame['content'] = $image_data[$md5];
                                    } else {
                                        $imagebase = base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
                                        $image_data[$md5] = $imagebase;
                                        $frame['content'] = $imagebase;
                                    }
                                    // $frame['content'] = base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
                                    $real_send_content[] = $frame;
                                    // sleep(1);
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
                                $sign = "account=" . $user_info['account'] . "ext_id=" . $send_taskid . "msg=" . $send_content[$send_taskid] . "phones=" . join(',', $new_num) . "timestamp=" . $time . "title=" . $send_title[$send_taskid] . "url=" . $user_info['call_back'] . "key=" . $user_info['key'];
                                $sign = md5($sign);
                                $real_send = [
                                    'account' => $user_info['account'],
                                    'timestamp' => $time,
                                    'url' => $user_info['call_back'],
                                    'phones' => join(',', $new_num),
                                    'title' => $send_title[$send_taskid],
                                    'msg' => $send_content[$send_taskid],
                                    'ext_id' => $send_taskid,
                                    'sign' => $sign,
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
                                    $this->writeToRobot($content, $res, '创蓝彩信通道');
                                    // print_r($result);
                                    // $redis->rpush('index:meassage:code:send' . ":" . 22, json_encode([
                                    //     'mobile'      => 15201926171,
                                    //     'content'     => $res
                                    // ])); //三体营销通道
                                    exit(); //关闭通道
                                }
                                /*  $result = json_decode(json_encode(simplexml_load_string($res, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
                                if ($result['returnstatus'] == 'Success') { //成功
                                $receive_id[$result['taskID']] = $send_taskid;
                                $redis->hset('index:meassage:code:back_taskno:' . $content, $result['taskID'], $send_taskid);
                                } elseif ($result['returnstatus'] == 'Faild') { //失败
                                // echo "error:" . $result['message'] . "\n";die;
                                } */
                                // // print_r($result);
                                unset($send_num[$send_taskid]);
                                usleep(12500);
                            }
                        }
                    }
                } while ($send);
                //剩下的号码再做提交
                // // print_r($send_content);
                // // print_r($send_num);die;
                if (!empty($send_num)) {
                    foreach ($send_num as $send_taskid => $num) {
                        $new_num = array_unique($num);
                        if (empty($new_num)) {
                            continue;
                        }
                        $real_send = [];
                        $sign = '';
                        $time = time();
                        $sign = "account=" . $user_info['account'] . "ext_id=" . $send_taskid . "msg=" . $send_content[$send_taskid] . "phones=" . join(',', $new_num) . "timestamp=" . $time . "title=" . $send_title[$send_taskid] . "url=" . $user_info['call_back'] . "key=" . $user_info['key'];
                        $sign = md5($sign);
                        $real_send = [
                            'account' => $user_info['account'],
                            'timestamp' => $time,
                            'url' => $user_info['call_back'],
                            // 'sign' => strtolower(md5($user_info['username'].$user_info['password'].date('YmdHis',time()))),
                            'phones' => join(',', $new_num),
                            'title' => $send_title[$send_taskid],
                            'msg' => $send_content[$send_taskid],
                            'ext_id' => $send_taskid,
                            'sign' => $sign,
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
                            // print_r($result);
                            $this->writeToRobot($content, $res, '创蓝彩信通道');
                            exit(); //关闭通道
                        }
                        // // print_r($res);

                        // $result = explode(',', $res);
                        // if ($result['returnstatus'] == 'Success') { //成功
                        //     $receive_id[$result['taskID']] = $send_taskid;
                        //     $redis->hset('index:meassage:code:back_taskno:' . $content, $result['taskID'], $send_taskid);
                        // } elseif ($result['returnstatus'] == 'Faild') { //失败
                        //     // echo "error:" . $result['message'] . "\n";die;
                        // }
                        unset($send_num[$send_taskid]);
                        usleep(12500);
                    }
                }
                // $receive_id = [
                //     '866214' => '15745'
                // ];
                // // print_r($receive_id);
                // die;

                // // print_r($receive_data);die;
                sleep(10);

                unset($send_num);
                unset($send_content);
                unset($receive_id);
                // echo "success";
            }
        } catch (\Exception $th) {
            //throw $th;
            foreach ($roallback as $key => $value) {
                foreach ($value as $ne => $val) {
                    $redis->rpush($redisMessageCodeSend, $val);
                }
            }

            /*  $log_path = realpath("") . "/error/59.log";
            $myfile = fopen($log_path, 'a+');
            fwrite($myfile, date('Y-m-d H:i:s', time()) . "\n");
            fwrite($myfile, $th . "\n");
            fclose($myfile);
            $redis->rpush('index:meassage:code:send' . ":" . 22, json_encode([
            'mobile'      => 15201926171,
            'content'     => "【钰晰科技】创蓝彩信通道出现异常"
            ])); //三体营销通道 */
            $this->writeToRobot($content, $th, '创蓝彩信通道');
            exception($th);

        }
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
        $this->sendRequest2($api, 'post', $check_data, $headers);
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

    public function getSendTask($id)
    {
        $task = Db::query("SELECT `task_no`,`uid` FROM yx_user_multimedia_message WHERE `id` =" . $id);
        if ($task) {
            return $task[0];
        }
        return false;
    }
}
