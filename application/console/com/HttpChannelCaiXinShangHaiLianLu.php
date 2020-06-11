<?php

namespace app\console\com;

use app\console\Pzlife;
use cache\Phpredis;
use Config;
use Env;
use Exception;
use think\Db;

//http 通道,通道编号10
class HttpChannelCaiXinShangHaiLianLu extends Pzlife
{

    //杭州迈远
    public function content($content = 13)
    {
        return [
            'account' => 'shyx',
            'password' => '147258',
            'userid' => '640',
            'send_api'    => 'http://139.224.17.45:8088/sendmms.aspx', //下发地址
            'call_api'    => 'http://139.224.17.45:8088/sendmms.aspx', //上行地址
            'overage_api' => '', //余额地址
            'receive_api' => 'http://139.224.17.45:8088/mmsStatusApi.aspx', //回执，报告
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
        // print_r($XML);die;
        // $image = imagecreatefromjpeg('http://imagesdev.shyuxi.com/20191209/6b97bc91cda37dfbde62dba15b447ca85dee1b09a5251.jpg');
        // print_r(base64_encode(file_get_contents('http://imagesdev.shyuxi.com/20191209/6b97bc91cda37dfbde62dba15b447ca85dee1b09a5251.jpg')));die;

        $content                 = 96;
        $redisMessageCodeSend    = 'index:meassage:code:send:' . $content; //彩信发送任务rediskey
        $redisMessageCodeDeliver = 'index:meassage:multimediamessage:deliver:' . $content; //彩信MsgId
        $user_info               = $this->content();
        /*    $send                 = $redis->rPush($redisMessageCodeSend, json_encode([
        'mar_task_id' => 1,
        'mobile' => '13476024461',
        'content' =>'【鼎业装饰】鼎礼相祝！跨年巨惠！定单送欧派智能晾衣架一套。选欧派产品可秒杀欧派智能马桶999元一个。终极预存大礼，来店给你个超大的惊喜！！！大到超乎您想象！一年只有这一次！电话3236788回T退订',
        ])); */
        while (true) {
            $send_task    = [];
            $send_num     = [];
            $send_content = [];
            $send_title   = [];
            $receive_id   = [];
            $roallback = [];
            $image_data   = [];
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
                        $real_send_content = '';
                        $vc                = '';
                        foreach ($send_data['content'] as $key => $value) {
                            $real_send_content .= $vc . $value['num'] . ',txt|' . base64_encode(mb_convert_encoding($value['content'], 'gb2312', 'utf8'));
                            // $real_send_content .= $vc . $value['num'] . ',txt|' . base64_encode($value['content']);
                            if (!empty($value['image_path'])) {
                                $real_send_content .= ',';
                                /* if ($value['image_type'] == 'jpg') {
                                    $real_send_content .= 'jpg|' . base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
                                } elseif ($value['image_type'] == 'gif') {
                                    $real_send_content .= 'gif|' . base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
                                } */
                                $md5 = md5(Config::get('qiniu.domain') . '/' . $value['image_path']);
                                if (isset($image_data[$md5])) {
                                    if ($value['image_type'] == 'jpg') {
                                        // $real_send_content .= 'jpg,' . base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
                                        $real_send_content .= 'jpg|' . $image_data[$md5];
                                    } elseif ($value['image_type'] == 'gif') {
                                        $real_send_content .= 'gif|' . $image_data[$md5];
                                        // $real_send_content .= 'gif,' . base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
                                    }
                                } else {
                                    $imagebase        = base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
                                    $image_data[$md5] = $imagebase;
                                    // $frame['content'] =$imagebase;
                                    if ($value['image_type'] == 'jpg') {
                                        // $real_send_content .= 'jpg,' . base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
                                        $real_send_content .= 'jpg|' . $image_data[$md5];
                                    } elseif ($value['image_type'] == 'gif') {
                                        $real_send_content .= 'gif|' . $image_data[$md5];
                                        // $real_send_content .= 'gif,' . base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
                                    }
                                }

                            }

                           
                            $vc = ';';
                        }
                        // $send_content[$send_data['mar_task_id']] = $send_data['content'];
                        $send_content[$send_data['mar_task_id']] = $real_send_content;
                    } elseif (!in_array($send_data['mar_task_id'], $send_task)) {
                        $send_task[]                           = $send_data['mar_task_id'];
                        $send_title[$send_data['mar_task_id']] = $send_data['title'];
                        //处理内容
                        // $send_content[$send_data['mar_task_id']] = $send_data['content'];
                        $real_send_content = '';
                        $vc                = '';
                        foreach ($send_data['content'] as $key => $value) {
                            $real_send_content .= $vc . $value['num'] . ',txt|' . base64_encode(mb_convert_encoding($value['content'], 'gb2312', 'utf8'));
                            // $real_send_content .= $vc . $value['num'] . ',txt|' . base64_encode($value['content']);
                            if (!empty($value['image_path'])) {
                                $real_send_content .= ',';
                                /* if ($value['image_type'] == 'jpg') {
                                    $real_send_content .= 'jpg|' . base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
                                } elseif ($value['image_type'] == 'gif') {
                                    $real_send_content .= 'gif|' . base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
                                } */
                                $md5 = md5(Config::get('qiniu.domain') . '/' . $value['image_path']);
                                if (isset($image_data[$md5])) {
                                    if ($value['image_type'] == 'jpg') {
                                        // $real_send_content .= 'jpg,' . base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
                                        $real_send_content .= 'jpg|' . $image_data[$md5];
                                    } elseif ($value['image_type'] == 'gif') {
                                        $real_send_content .= 'gif|' . $image_data[$md5];
                                        // $real_send_content .= 'gif,' . base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
                                    }
                                } else {
                                    $imagebase        = base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
                                    $image_data[$md5] = $imagebase;
                                    // $frame['content'] =$imagebase;
                                    if ($value['image_type'] == 'jpg') {
                                        // $real_send_content .= 'jpg,' . base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
                                        $real_send_content .= 'jpg|' . $image_data[$md5];
                                    } elseif ($value['image_type'] == 'gif') {
                                        $real_send_content .= 'gif|' . $image_data[$md5];
                                        // $real_send_content .= 'gif,' . base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
                                    }
                                }
                            }
                            $vc = ';';
                        }
                        $send_content[$send_data['mar_task_id']] = $real_send_content;
                    }
                    $send_num[$send_data['mar_task_id']][] = $send_data['mobile'];
                    foreach ($send_num as $send_taskid => $num) {
                        $new_num = array_unique($num);
                        if (count($new_num) >= 2000) { //超出2000条做一次提交
                            $real_send = [];
                            $real_send = [
                                'account'    => $user_info['account'],
                                'password'   => $user_info['password'],
                                'userid'=> $user_info['userid'],
                                // 'timestamp' => date('YmdHis',time()),
                                // 'sign' => strtolower(md5($user_info['username'].$user_info['password'].date('YmdHis',time()))),
                                'mobile'    => join(',', $new_num),
                                'starttime' => '',
                                'title'     => $send_title[$send_taskid],
                                // 'content'   => $send_content[$send_taskid], 
                                'content'   => $send_content[$send_taskid],
                            ];

                            $res    = sendRequest($user_info['send_api'], 'post', $real_send);
                            $result = json_decode($res, true);
                            print_r($result);
                            // $result['code'] = 2;
                            if ($result['code'] == 0) {
                                $receive_id[$result['batchId']] = $send_taskid;
                                $redis->hset('index:meassage:code:back_taskno:' . $content, $result['batchId'], $send_taskid);
                                unset($roallback[$send_taskid]);
                            } else {
                                foreach ($roallback as $key => $value) {
                                    foreach ($value as $ne => $val) {
                                        $redis->rpush($redisMessageCodeSend, $val);
                                    }
                                }
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
                            sleep(1);
                        }
                    }
                }
            } while ($send);
            //剩下的号码再做提交
            // print_r($send_num);die;
            if (!empty($send_num)) {
                foreach ($send_num as $send_taskid => $num) {
                    $new_num = array_unique($num);
                    if (empty($new_num)) {
                        continue;
                    }
                    $real_send = [];
                    $real_send = [
                        'action' => 'send',
                        'account'    => $user_info['account'],
                        'password'   => $user_info['password'],
                        'userid'=> $user_info['userid'],
                        // 'timestamp' => date('YmdHis',time()),
                        // 'sign' => strtolower(md5($user_info['username'].$user_info['password'].date('YmdHis',time()))),
                        'mobile'    => join(',', $new_num),
                        'starttime' => '',
                        'title'     => $send_title[$send_taskid],
                        // 'content'   => $send_content[$send_taskid],
                        'content'   => $send_content[$send_taskid],
                    ];
                    /* $log_path = realpath("") . "/sign.log";
                    $myfile = fopen($log_path, 'w');
            
                    foreach ($real_send as $key => $value) {
                        fwrite($myfile, $key . ":" . $value . "\n");
                    }
                    fclose($myfile); */
                    $res = sendRequest($user_info['send_api'], 'post', $real_send);
                    $result = explode(':',$res);
                    // print_r($res);die;
                    // $result['code'] = 2;
                    if (isset($result[1])) {
                        // $receive_id[$result['batchId']] = $send_taskid;
                        $redis->hset('index:meassage:code:back_taskno:' . $content, $result[1], $send_taskid);
                        unset($roallback[$send_taskid]);
                    } else {
                        foreach ($roallback as $key => $value) {
                            foreach ($value as $ne => $val) {
                                $redis->rpush($redisMessageCodeSend, $val);
                            }
                        }
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
                    sleep(1);
                }
            }
            // $receive_id = [
            //     '866214' => '15745'
            // ];
            // print_r($receive_id);
            // die;
            $receive = sendRequest($user_info['receive_api'], 'post', ['account'    => $user_info['account'],
            'password'   => $user_info['password'],
            'userid'=> $user_info['userid'],
            'action' => 'query']);
            if (empty($receive)) {
                sleep(60);
                continue;
            }
           /*  $receive = '<?xml version="1.0" encoding="utf-8" ?><returnsms>
            <statusbox>
            <mobile>15201926171</mobile>
            <taskid>9823</taskid>
            <status>10</status>
            <receivetime>2020/6/8 18:21:21</receivetime>
            <errorcode>1000</errorcode>
            <extno></extno>
            </statusbox>
            </returnsms>'; */

            $receive_data = json_decode(json_encode(simplexml_load_string($receive, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
            // $receive_data = json_decode($receive, true);
            print_r($receive_data);
            // $receive = '1016497,15201926171,DELIVRD,2019-11-21 17:39:42';
            // $receive_data = explode(';', $receive);
            /*   $receive_data = [
                    'code' => 0,
                    'msg'  => '',
                    'data' => [
                        [
                            'smUuid' => '26175_12_0_15172413692_0_tejrsVO_1',
                            'deliverTime' => '2019-12-10 17:27:16',
                            'mobile' => '15172413692',
                            'smUuid' => '26175_12_0_15172413692_0_tejrsVO_1',
                            'deliverResult' => 'REJECT',
                            'batchId' => 'o0ULmxE'
                        ],
                    ],
                ]; */
            $send_status = 2;
            if (isset($receive_data['statusbox'])) {
                foreach ($receive_data['statusbox'] as $key => $value) {
                    // $receive_info = [];
                    // $receive_info = explode(',', $value);
                    // $task_id      = $receive_id[$value['taskid']];
                    if (is_array($value)) {
                        $task_id = $redis->hget('index:meassage:code:back_taskno:' . $content, trim($value['taskid']));
                        $task    = $this->getSendTask($task_id);
                        if ($task == false) {
                            echo "error task_id" . "\n";
                        }
                        $stat          = $value['errorcode'];
                        $send_task_log = [];
                        if ($value['status'] == '10') {

                            $send_status = 3;
                            $stat        = 'DELIVRD';
                        } else {
                            $send_status = 4;
                        }
                        $send_task_log = [
                            'task_no'        => $task['task_no'],
                            'uid'            => $task['uid'],
                            'mobile'         => $value['mobile'],
                            'status_message' => $stat,
                            'send_status'    => $send_status,
                            'send_time'      => strtotime($value['receivetime']),
                        ];
                        $redis->rpush($redisMessageCodeDeliver, json_encode($send_task_log));
                        // Db::startTrans();
                        // try {
                        //     Db::table('yx_user_send_task_log')->insert($send_task_log);
                        //     Db::commit();
                        // } catch (\Exception $e) {
                        //     Db::rollback();
                        //     return ['code' => '3009']; //修改失败
                        // }
                        unset($send_status);
                    }else{
                        $task_id = $redis->hget('index:meassage:code:back_taskno:' . $content, trim($receive_data['statusbox']['taskid']));
                        $task    = $this->getSendTask($task_id);
                        if ($task == false) {
                            echo "error task_id" . "\n";
                            break;
                        }
                        $stat          = $receive_data['statusbox']['errorcode'];
                        $send_task_log = [];
                        if ($receive_data['statusbox']['status'] == '10') {
                            $send_status = 3;
                            $stat        = 'DELIVRD';
                        } else {
                            $send_status = 4;
                        }
                        $send_task_log = [
                            'task_no'        => $task['task_no'],
                            'uid'            => $task['uid'],
                            'mobile'         => $receive_data['statusbox']['mobile'],
                            'status_message' => $stat,
                            'send_status'    => $send_status,
                            'send_time'      => strtotime($receive_data['statusbox']['receivetime']),
                        ];
                        $redis->rpush($redisMessageCodeDeliver, json_encode($send_task_log));
                        break;
                    }
                   
                }
           
            } else {
                sleep(10);
            }
            // print_r($receive_data);die;
            sleep(60);

            unset($send_num);
            unset($send_content);
            unset($receive_id);
            echo "success";
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