<?php

namespace app\console\com;

use app\console\Pzlife;
use cache\Phpredis;
use Config;
use Env;
use Exception;
use think\Db;

//http 通道,通道编号6
class HttpChannelKeMeng extends Pzlife {

    //
    public function content($content = 10) {
        return [
            'username'    => '上海钰晰图书',
            'appid'    => '158',
            'password'    => 'D3888377BA4805E84DDEF434FA733211',
            'tockenid'    => 'jdt91x14',
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
        // $send                 = $redis->rPush($redisMessageCodeSend, json_encode([
        //     'mobile' => '15201926171', 
        //     'mar_task_id' => 15715, 
        //     'content' =>'【中山口腔】5周年庆，11月23-30日，黄石三店同庆，全线诊疗项目 8 折让利回馈、消费就送青花瓷礼盒！39.9元购洁牙卡送食用油。详情询:0714-6268188 回T退订', 
        // ]));
        // $send                 = $redis->rPush($redisMessageCodeSend, json_encode([
        //     'mobile' => '15201926175', 
        //     'mar_task_id' => 15715, 
        //     'content' =>'【中山口腔】5周年庆，11月23-30日，黄石三店同庆，全线诊疗项目 8 折让利回馈、消费就送青花瓷礼盒！39.9元购洁牙卡送食用油。详情询:0714-6268188 回T退订', 
        // ]));
        // $send                 = $redis->rPush($redisMessageCodeSend, json_encode([
        //     'mobile' => '15201926175', 
        //     'mar_task_id' => 15714, 
        //     'content' =>'【中山口腔】5周年庆，11月23-30日，黄石三店同庆，全线诊疗项目 8 折让利回馈、消费就送青花瓷礼盒！39.9元购洁牙卡送食用油。详情询:0714-6268188 回T退订', 
        // ]));
        
        $content              = 10;
        $redisMessageCodeSend = 'index:meassage:code:send:' . $content; //验证码发送任务rediskey
        $user_info            = $this->content();
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
                            'username' => $user_info['username'],
                            'password' => $user_info['password'],
                            'tockenid' => $user_info['tockenid'],
                            'mobile'   => join(',', $new_num),
                            'message'  => $send_content[$send],
                        ];
    
                        $res    = sendRequest($user_info['send_api'], 'post', $real_send);
                        $result = explode(',', $res);
                        if ($result[0] == 'success') { //成功
                            $receive_id[$send][] = $result[1];
                        } elseif ($result[0] == 'error') { //失败
                            echo "error" . $result[1] . "\n";die;
                        }
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
                    'username' => $user_info['username'],
                    'password' => $user_info['password'],
                    'tockenid' => $user_info['tockenid'],
                    'mobile'   => join(',', $new_num),
                    'message'  => $send_content[$send],
                ];
    
                $res    = sendRequest($user_info['send_api'], 'post', $real_send);
                $result = explode(',', $res);
                if ($result[0] == 'success') { //成功
                    $receive_id[$result[1]] = $send;
                } elseif ($result[0] == 'error') { //失败
                    echo "error:" . $result[1] . "\n";die;
                }
                unset($send_num[$send]);
                sleep(1);
            }
        }
        $receive_id = [
            '1016497' => '15715'
        ];
        do {
            $receive      = trim(sendRequest($user_info['receive_api'], 'post', ['username' => $user_info['username'], 'password' => $user_info['password']]));
            
            // $receive = '1016497,15201926171,DELIVRD,2019-11-21 17:39:42';
            $receive_data = explode(';', $receive);
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
