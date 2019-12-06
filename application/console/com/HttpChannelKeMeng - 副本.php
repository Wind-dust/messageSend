<?php

namespace app\console\com;

use app\console\Pzlife;
use cache\Phpredis;
use Config;
use Env;
use Exception;
use think\Db;

//http 通道,通道编号10
class HttpChannelKeMeng extends Pzlife {

    //
    public function content($content = 11) {
        return [
            // 'username'    => '上海钰晰图书',
            'username'    => '上海钰晰装饰',
            // 'appid'    => '158',
            'appid'    => '159',
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
       
     
        
        $content              = 11;
        $redisMessageCodeSend = 'index:meassage:code:send:' . $content; //验证码发送任务rediskey
        $user_info            = $this->content();
        $send                 = $redis->rPush($redisMessageCodeSend, json_encode([
            'mar_task_id' => 15715, 
            'mobile' => '15201926171', 
            'content' =>'【鼎业装饰】鼎礼相祝！跨年巨惠！定单送欧派智能晾衣架一套。选欧派产品可秒杀欧派智能马桶999元一个。终极预存大礼，来店给你个超大的惊喜！！！大到超乎您想象！一年只有这一次！电话3236788', 
        ]));
           $send                 = $redis->rPush($redisMessageCodeSend, json_encode([
            'mobile' => '15821193682', 
            'mar_task_id' => 15715, 
            'content' =>'【鼎业装饰】鼎礼相祝！跨年巨惠！定单送欧派智能晾衣架一套。选欧派产品可秒杀欧派智能马桶999元一个。终极预存大礼，来店给你个超大的惊喜！！！大到超乎您想象！一年只有这一次！电话3236788', 
        ]));
        $send                 = $redis->rPush($redisMessageCodeSend, json_encode([
            'mobile' => '15827039444', 
            'mar_task_id' => 15714, 
            'content' =>'【鼎业装饰】鼎礼相祝！跨年巨惠！定单送欧派智能晾衣架一套。选欧派产品可秒杀欧派智能马桶999元一个。终极预存大礼，来店给你个超大的惊喜！！！大到超乎您想象！一年只有这一次！电话3236788', 
        ]));
        $send_task            = [];
        $send_num             = [];
        $send_content         = [];
        $receive_id           = [];
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
                foreach ($send_num as $send => $num) {
                    $new_num = array_unique($num);
                    if (count($new_num) >= 50000) { //超出5000条做一次提交
                        $real_send = [];
                        $real_send = [
                            'userid' => $user_info['appid'],
                            'timestamp' => date('YmdHis',time()),
                            'sign' => strtolower(md5($user_info['username'].$user_info['password'].date('YmdHis',time()))),
                            'mobile'   => join(',', $new_num),
                            'content'  => $send_content[$send],
                        ];
    
                        $res    = sendRequest($user_info['send_api'], 'post', $real_send);
                        $result = json_decode(json_encode(simplexml_load_string($res, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
                        if ($result['returnstatus'] == 'success') { //成功
                            $receive_id[$result['taskID']] = $send;
                        } elseif ($result['returnstatus'] == 'Faild') { //失败
                            echo "error:" . $result['message'] . "\n";die;
                        }
                        print_r($result);
                        unset($send_num[$send]);
                        sleep(1);
                    }
                }
            }
            
        } while ($send);
        //剩下的号码再做提交
        if (!empty($send_num)) {
            foreach ($send_num as $send => $num) {
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
                    'content'  => $send_content[$send],
                ];
                print_r($real_send);
                $res    = sendRequest($user_info['send_api'], 'post', $real_send);
                $result = json_decode(json_encode(simplexml_load_string($res, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
                // print_r($res);die;
                // $result = explode(',', $res);
                if ($result['returnstatus'] == 'success') { //成功
                    $receive_id[$result['taskID']] = $send;
                } elseif ($result['returnstatus'] == 'Faild') { //失败
                    echo "error:" . $result['message'] . "\n";die;
                }
                unset($send_num[$send]);
                sleep(1);
            }
        }
        // $receive_id = [
        //     '1016497' => '15715'
        // ];
        do {
            $receive      = sendRequest($user_info['receive_api'], 'post', ['userid' => $user_info['appid'], 'timestamp' => date('YmdHis',time()),'sign' => strtolower(md5($user_info['username'].$user_info['password'].date('YmdHis',time())))]);
            $receive_data = json_decode(json_encode(simplexml_load_string($receive, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
            print_r($receive_data);
            // $receive = '1016497,15201926171,DELIVRD,2019-11-21 17:39:42';
            // $receive_data = explode(';', $receive);
            foreach ($receive_data as $key => $value) {
                $receive_info = [];
                $receive_info = explode(',', $value);
                $task_id      = $receive_id[$receive_info[0]];
                $task         = $this->getSendTask($task_id);
                if ($task == false) {
                    echo "error task_id" . "\n";
                }
                $send_task_log = [];
                if ($receive_info[2] == 'DELIVRD') {
                    $send_status = 3;
                }
                $send_task_log = [
                    'task_no'        => $task['task_no'],
                    'uid'            => $task['uid'],
                    'mobile'         => $receive_info[1],
                    'status_message' => $receive_info[2],
                    'send_status'    => $send_status,
                    'send_time'      => strtotime($receive_info[3]),
                ];
                Db::startTrans();
                try {
                    Db::table('yx_user_send_task_log')->insert($send_task_log);
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    return ['code' => '3009']; //修改失败
                }
                unset($send_status);
            }
            
            // print_r($receive_data);die;
            sleep(60);
        } while ($receive);
        unset($send_num);
        unset($send_content);
        unset($receive_id);
        echo "success";

    }

    public function getSendTask($id) {
        $task = Db::query("SELECT `task_no`,`uid` FROM yx_user_send_task WHERE `id` =" . $id);
        if ($task) {
            return $task[0];
        }
        return false;
    }

}
