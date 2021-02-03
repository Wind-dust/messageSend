<?php

namespace app\console\com;

use app\console\Pzlife;
use cache\Phpredis;
use Config;
use think\Db;

//http 通道,通道编号10
class HttpChannelCaiXinKuaiLeWangShiModelVarLianDian extends Pzlife
{

    //杭州迈远
    public function content($content = 13)
    {
        return [
            'userid' => '446',
            'account' => 'flldcx',
            'password' => 'flldcx123',
            'send_api' => 'http://120.77.10.34:8088/sendmms.aspx', //下发地址
            'call_api' => '', //上行地址
            'overage_api' => '', //余额地址
            'receive_api' => '', //回执，报告
        ];

        //'account'    => 'yuxi',
        // 'appid'    => '674',
    }

    public function Send()
    {
        $redis = Phpredis::getConn();
        // $a_time = 0;

        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G

        /*         $XML = '<?xml version="1.0" encoding="utf-8" ?>
        <returnsms>
        <statusbox>
        <mobile>15023239810</mobile>-------------对应的手机号码
        <taskid>1212</taskid>-------------同一批任务ID
        <status>10</status>---------状态报告----10：发送成功，20：发送失败
        <receivetime>2011-12-02 22:12:11</receivetime>-------------接收时间
        <errorcode>DELIVRD</errorcode>-上级网关返回值，不同网关返回值不同，仅作为参考
        <extno>01</extno>--子号，即自定义扩展号
        </statusbox>
        <statusbox>
        <mobile>15023239811</mobile>
        <taskid>1212</taskid>
        <status>20</status>
        <receivetime>2011-12-02 22:12:11</receivetime>
        <errorcode>2</errorcode>
        <extno></extno>
        </statusbox>
        </returnsms>
        ';
        $XML = json_decode(json_encode(simplexml_load_string($XML, 'SimpleXMLElement', LIBXML_NOCDATA)), true); */
        // // print_r($XML);die;
        // $image = imagecreatefromjpeg('http://imagesdev.shyuxi.com/20191209/6b97bc91cda37dfbde62dba15b447ca85dee1b09a5251.jpg');
        // // print_r(base64_encode(file_get_contents('http://imagesdev.shyuxi.com/20191209/6b97bc91cda37dfbde62dba15b447ca85dee1b09a5251.jpg')));die;

        $content = 190;
        $redisMessageCodeSend = 'index:meassage:code:send:' . $content; //彩信发送任务rediskey
        $redisMessageCodeDeliver = 'index:meassage:multimediamessage:deliver:' . $content; //彩信MsgId
        $user_info = $this->content();
        /*    $send                 = $redis->rPush($redisMessageCodeSend, json_encode([
        'mar_task_id' => 1,
        'mobile' => '13476024461',
        'content' =>'【鼎业装饰】鼎礼相祝！跨年巨惠！定单送欧派智能晾衣架一套。选欧派产品可秒杀欧派智能马桶999元一个。终极预存大礼，来店给你个超大的惊喜！！！大到超乎您想象！一年只有这一次！电话3236788回T退订',
        ])); */
        $image_data = [];
        try {
            while (true) {
                // if (date('H') >= 18 || date('H') < 8) {
                //     exit("8点前,18点后通道关闭");
                // }
                if (count($image_data) > 100) {
                    $image_data = [];
                }

                while (true) {
                    $send = $redis->lPop($redisMessageCodeSend);
                    // $redis->rpush($redisMessageCodeSend, $send);
                    $send_data = json_decode($send, true);
                    // print_r($send_data);die;
                    if (empty($send_data)) {
                        sleep(1);
                        break;
                    }
                    if (!isset($send_data['variable'])) {
                        $redis->rpush($redisMessageCodeSend, $send);
                        $this->writeToRobot($content, "检测到非模板变量的彩信提交到本通道", '创世彩信模板变量通道');
                        exit("检测到非模板变量的彩信提交到本通道");
                    }
                    // $roallback[] = $send;
                    // $send_task[] = $send_data['mar_task_id'];
                    // $send_title[$send_data['mar_task_id']] = $send_data['title'];
                    //处理内容
                    // $send_content[$send_data['mar_task_id']] = $send_data['content'];
                    $real_send_content = '';
                    $vc = '';
                    foreach ($send_data['content'] as $key => $value) {
                        
                        if (!empty($value['content'])) {
                            if (isset($send_data['variable']['{{var1}}'])) {
                                $value['content'] = str_replace('{{var1}}', $send_data['variable']['{{var1}}'], $value['content']);
                            }
                            if (isset($send_data['variable']['{{var2}}'])) {
                                $value['content'] = str_replace('{{var2}}', $send_data['variable']['{{var2}}'], $value['content']);
                            }
                            if (isset($send_data['variable']['{{var3}}'])) {
                                $value['content'] = str_replace('{{var3}}', $send_data['variable']['{{var3}}'], $value['content']);
                            }
                            if (isset($send_data['variable']['{{var4}}'])) {
                                $value['content'] = str_replace('{{var4}}', $send_data['variable']['{{var4}}'], $value['content']);
                            }
                            if (isset($send_data['variable']['{{var5}}'])) {
                                $value['content'] = str_replace('{{var5}}', $send_data['variable']['{{var5}}'], $value['content']);
                            }
                            if (isset($send_data['variable']['{{var6}}'])) {
                                $value['content'] = str_replace('{{var6}}', $send_data['variable']['{{var6}}'], $value['content']);
                            }
                            if (isset($send_data['variable']['{{var7}}'])) {
                                $value['content'] = str_replace('{{var7}}', $send_data['variable']['{{var7}}'], $value['content']);
                            }
                            if (isset($send_data['variable']['{{var8}}'])) {
                                $value['content'] = str_replace('{{var8}}', $send_data['variable']['{{var8}}'], $value['content']);
                            }
                            if (isset($send_data['variable']['{{var9}}'])) {
                                $value['content'] = str_replace('{{var9}}', $send_data['variable']['{{var9}}'], $value['content']);
                            }
                            if (isset($send_data['variable']['{{var10}}'])) {
                                $value['content'] = str_replace('{{var10}}', $send_data['variable']['{{var10}}'], $value['content']);
                            }
                            // print_r($value['content']);die;
                            $real_send_content .= $vc . $value['num'] . ',txt|' . base64_encode(mb_convert_encoding($value['content'], 'gb2312', 'utf8'));
                        }
                        // $real_send_content .= $vc . $value['num'] . ',txt|' . base64_encode($value['content']);
                        if (!empty($value['image_path'])) {
                            $real_send_content .= ',';

                            $type = explode('.', $value['image_path']);
                            // $real_send_content .= $type[1].'|' . base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
                            $md5 = md5(Config::get('qiniu.domain') . '/' . $value['image_path']);
                            if (isset($image_data[$md5])) {
                                $real_send_content .= $type[1] . '|' . $image_data[$md5];
                                // $frame['content'] = $image_data[$md5];
                            } else {
                                $imagebase = base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
                                $image_data[$md5] = $imagebase;
                                $real_send_content .= $type[1] . '|' . $imagebase;
                            }
                        }
                        $vc = ';';
                    }

                    $real_send = [];
                    $real_send = [
                        'userid' => $user_info['userid'],
                        'account' => $user_info['account'],
                        'password' => $user_info['password'],
                        // 'timestamp' => date('YmdHis',time()),
                        // 'sign' => strtolower(md5($user_info['username'].$user_info['password'].date('YmdHis',time()))),
                        'mobile' => $send_data['mobile'],
                        'starttime' => '',
                        'title' => $send_data['title'],
                        // 'content'   => $send_content[$send_taskid],
                        'content' => $real_send_content,
                        'extno' => isset($send_data['develop_code']) ? $send_data['develop_code'] : "",
                        'action' => 'send',
                    ];

                    $res = sendRequest($user_info['send_api'], 'post', $real_send);
                    $result = explode(':', $res);
                    // $result['code'] = 2;
                    if (isset($result[1])) {
                        // $receive_id[$result['batchId']] = $send_taskid;
                        $redis->hset('index:meassage:code:back_taskno:kuailewangshi_multimediamessage', $result[1], $send_data['mar_task_id']);
                        // unset($roallback[$send_taskid]);
                    } else {
                        $redis->rpush($redisMessageCodeSend, $send);
                        $this->writeToRobot($content, "彩信提交失败，失败原因" . $res, '快乐网视彩信模板变量通道');
                        exit("彩信提交失败，失败原因" . $res); //关闭通道
                    }

                }
               
            }
        } catch (\Exception $th) {
            //throw $th;
            $redis->rpush($redisMessageCodeSend, $send);
            $this->writeToRobot($content, "彩信通道报错,错误原因" . $th, '快乐网视彩信模板变量通道');
            exception($th);
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
}
