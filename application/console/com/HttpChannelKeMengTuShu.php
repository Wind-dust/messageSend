<?php

namespace app\console\com;

use app\console\Pzlife;
use cache\Phpredis;
use Config;
use Env;
use Exception;
use think\Db;

//http 通道,通道编号10
class HttpChannelKeMengTuShu extends Pzlife {

    //
    public function content($content = 10) {
        return [
            'username'    => '上海钰晰图书',
            'appid'    => '158',
            'password'    => 'sh@123456',
            'tockenid'    => '',
            'send_api'    => 'http://39.98.65.224:8088/v2sms.aspx?action=send',//下发地址
            'call_api'    => 'http://39.98.65.224:8088/v2callApi.aspx?action=query',//上行地址
            'overage_api' => 'http://39.98.65.224:8088/v2sms.aspx?action=overage',//余额地址
            'receive_api' => 'http://39.98.65.224:8088/v2statusApi.aspx?action=query',//回执，报告
        ];
    }

    public function Send() {
        $redis = Phpredis::getConn();
        // $a_time = 0;

        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
       
     
        
        $content              = 10;
        $redisMessageCodeSend = 'index:meassage:code:send:' . $content; //验证码发送任务rediskey
        $redisMessageCodeDeliver    = 'index:meassage:code:deliver:' . $content; //行业通知MsgId
        $user_info            = $this->content();
        // $send                 = $redis->rPush($redisMessageCodeSend, json_encode([
        //     'mar_task_id' => 15715, 
        //     'mobile' => '15201926171', 
        //     'content' =>'【已阅行知】新品大促！童书《DK幼儿艺术启蒙烧脑创意》原价68元，加官微shulixingzhi，直降25元！活动还有最后一天！！！退订回T', 
        // ]));
        //    $send                 = $redis->rPush($redisMessageCodeSend, json_encode([
        //     'mobile' => '15821193682', 
        //     'mar_task_id' => 15715, 
        //     'content' =>'【已阅行知】新品大促！童书《DK幼儿艺术启蒙烧脑创意》原价68元，加官微shulixingzhi，直降25元！活动还有最后一天！！！退订回T', 
        // ]));
        // $send                 = $redis->rPush($redisMessageCodeSend, json_encode([
        //     'mobile' => '15827039444', 
        //     'mar_task_id' => 15714, 
        //     'content' =>'【已阅行知】新品大促！童书《DK幼儿艺术启蒙烧脑创意》原价68元，加官微shulixingzhi，直降25元！活动还有最后一天！！！退订回T', 
        // ]));
        // $task_id      = $redis->hget('index:meassage:code:back_taskno:'.$content,866213);
        // print_r($task_id);die;
        while (true) {
            $send_task            = [];
            $send_num             = [];
            $send_content         = [];
            $receive_id           = [];
            if (date('H') >= 18 || date('H') < 8) {
                exit("8点前,18点后通道关闭");
            }
            do {
                $send                 = $redis->lPop($redisMessageCodeSend);
                $send_data = json_decode($send, true);
                if ($send_data) {
                    if (empty($send_task)) {
                        $send_task[]                             = $send_data['mar_task_id'];
                        $send_content[$send_data['mar_task_id']] = $send_data['content'];
                    }elseif (!in_array($send_data['mar_task_id'], $send_task)) {
                        $send_task[]                             = $send_data['mar_task_id'];
                        $send_content[$send_data['mar_task_id']] = $send_data['content'];
                    }
                    $send_num[$send_data['mar_task_id']][] = $send_data['mobile'];
                    foreach ($send_num as $send_taskid => $num) {
                        $new_num = array_unique($num);
                        if (count($new_num) >= 50000) { //超出5000条做一次提交
                            $real_send = [];
                            $real_send = [
                                'userid' => $user_info['appid'],
                                'timestamp' => date('YmdHis',time()),
                                'sign' => strtolower(md5($user_info['username'].$user_info['password'].date('YmdHis',time()))),
                                'mobile'   => join(',', $new_num),
                                'content'  => $send_content[$send_taskid],
                            ];
        
                            $res    = sendRequest($user_info['send_api'], 'post', $real_send);
                            $result = json_decode(json_encode(simplexml_load_string($res, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
                            if ($result['returnstatus'] == 'Success') { //成功
                                $receive_id[$result['taskID']] = $send_taskid;
                                $redis->hset('index:meassage:code:back_taskno:'.$content,$result['taskID'],$send_taskid);
                            } elseif ($result['returnstatus'] == 'Faild') { //失败
                                echo "error:" . $result['message'] . "\n";die;
                            }
                            // print_r($result);
                            unset($send_num[$send_taskid]);
                            sleep(1);
                        }
                    }
                }
                
            } while ($send);
            //剩下的号码再做提交
            if (!empty($send_num)) {
                foreach ($send_num as $send_taskid => $num) {
                    $new_num   = array_unique($num);
                    if (empty($new_num)) {
                        continue;
                    }
                    $real_send = [];
                    $real_send = [
                        'userid' => $user_info['appid'],
                        'timestamp' => date('YmdHis',time()),
                        // 'timestamp' => time(),
                        'sign' => strtolower(md5($user_info['username'].$user_info['password'].date('YmdHis',time()))),
                        // 'sign' => $user_info['appid'].$user_info['password'].date('YmdHis',time()),
                        // 'sign' => $user_info['username'].$user_info['password'].time(),
                        'mobile'   => join(',', $new_num),
                        'content'  => $send_content[$send_taskid],
                    ];
                    // print_r($real_send);
                    $res    = sendRequest($user_info['send_api'], 'post', $real_send);
                    $result = json_decode(json_encode(simplexml_load_string($res, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
                    print_r($result);
                    // $result = explode(',', $res);
                    if ($result['returnstatus'] == 'Success') { //成功
                        $receive_id[$result['taskID']] = $send_taskid;
                        $redis->hset('index:meassage:code:back_taskno:'.$content,$result['taskID'],$send_taskid);
                    } elseif ($result['returnstatus'] == 'Faild') { //失败
                        echo "error:" . $result['message'] . "\n";die;
                    }
                    unset($send_num[$send]);
                    sleep(1);
                }
            }
            // $receive_id = [
            //     '866213' => '15715'
            // ];
            // print_r($receive_id);
            // die;
            do {
                $receive      = sendRequest($user_info['receive_api'], 'post', ['userid' => $user_info['appid'], 'timestamp' => date('YmdHis',time()),'sign' => strtolower(md5($user_info['username'].$user_info['password'].date('YmdHis',time())))]);
                if (empty($receive)) {
                    sleep(60);
                    continue;
                }
                $receive_data = json_decode(json_encode(simplexml_load_string($receive, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
                print_r($receive_data);
                // $receive = '1016497,15201926171,DELIVRD,2019-11-21 17:39:42';
                // $receive_data = explode(';', $receive);
                if (isset($receive_data['statusbox'])) {
                    $real_receive_data = $receive_data['statusbox'];
                    foreach ($real_receive_data as $key => $value) {
                        // $receive_info = [];
                        // $receive_info = explode(',', $value);
                        // $task_id      = $receive_id[$value['taskid']];
                        $task_id      = $redis->hget('index:meassage:code:back_taskno:'.$content,$value['taskid']);
                        $task         = $this->getSendTask($task_id);
                        if ($task == false) {
                            echo "error task_id" . "\n";
                        }
                        $send_task_log = [];
                        if ($value['errorcode'] == '10') {
                            $send_status = 3;
                        }else{
                            $send_status = 4;
                        }
                        $send_task_log = [
                            'task_no'        => $task['task_no'],
                            'uid'            => $task['uid'],
                            'mobile'         => $value['mobile'],
                            'status_message' => $value['errorcode'],
                            'send_status'    => $send_status,
                            'send_time'      => strtotime($value['receivetime']),
                        ];
                        $redis->rpush($redisMessageCodeDeliver,json_encode($send_task_log));
                        // Db::startTrans();
                        // try {
                        //     Db::table('yx_user_send_task_log')->insert($send_task_log);
                        //     Db::commit();
                        // } catch (\Exception $e) {
                        //     Db::rollback();
                        //     return ['code' => '3009']; //修改失败
                        // }
                        unset($send_status);
                    }
                }
                // print_r($receive_data);die;
                sleep(60);
            } while ($receive);
            unset($send_num);
            unset($send_content);
            unset($receive_id);
            echo "success";
            sleep(60);
        }
       
    }

    public function getSendTask($id) {
        $task = Db::query("SELECT `task_no`,`uid` FROM yx_user_send_task WHERE `id` =" . $id);
        if ($task) {
            return $task[0];
        }
        return false;
    }

}
