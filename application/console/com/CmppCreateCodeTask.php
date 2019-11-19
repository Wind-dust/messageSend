<?php

namespace app\console\com;

use app\console\Pzlife;
use Config;
use Env;
use think\Db;
use cache\Phpredis;

class CmppCreateCodeTask extends Pzlife {

    public function CreateCodeTask(){
        $redis = Phpredis::getConn();
        $redisMessageCodeSend       = 'index:meassage:code:send:task'; //验证码发送任务rediskey
        while (true) {
            $SendText = $redis->lPop($redisMessageCodeSend);
            if (empty($SendText)) {
                break;
            }
            $send = explode(':',$SendText);
            $user = $this->getUserInfo($send[0]);
            if (empty($user) || $user['user_status'] == 1) {
                break;
            }
            $send_code_task = [];
            $send_code_task['task_no']        = 'bus' . date('ymdHis') . substr(uniqid('', true), 15, 8);
            $send_code_task['task_content']        = $send[2];
            $send_code_task['mobile_content']        = $send[1];
            $send_code_task['uid']        = $send[0];
            $send_code_task['source']        = $send[4];
            $send_code_task['msg_id']        = $send[3];
            
            //免审用户
            if ($user['free_trial'] == 2) {
                Db::startTrans();
                try {
                    //如果是行业
                    $task_id = Db::table('yx_user_send_code_task')->insertGetId($send_code_task);

                    Db::commit();
                
                } catch (\Exception $e) {
                    Db::rollback();
                }
            }elseif ($user['free_trial'] == 1) {//需审核用户
                Db::startTrans();
                try {
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                }
            }
            print_r($user);die;
        }
    }

    private function getUserInfo($uid) {
        $getUserSql = sprintf("select id,user_status,reservation_service,free_trial from yx_users where delete_time=0 and id = %d", $uid);
        // print_r($getUserSql);die;
        $userInfo = Db::query($getUserSql);
        if (!$userInfo) {
            return [];
        }
        return $userInfo[0];
    }

    public function getMessageLog(){
        $redis = Phpredis::getConn();
        $redisMessageCodeSend       = 'index:meassage:code:deliver:'; //验证码发送任务rediskey
        for ($i=0; $i < 5; $i++) { 
            $new_redisMessageCodeSend = $redisMessageCodeSend.$i;
            $send = $redis->lPop($new_redisMessageCodeSend);
            
            while ($send) {
                $redis->rPush($new_redisMessageCodeSend);
                // $test = "13861218631:846:【米思米】尊敬的客户，已收到货款56.96元。请将需要下订的报价，在WOS报价订购平台操作到等待付款，或发送米思米报价单/PO单至我司。我司会根据贵司的入帐信息完成下订。贵司已在我司登录联系邮箱，同样的内容也会发送到该邮箱 ，负责人员如有变更或将有变更，请发送邮件至cs@misumi.sh.cn:DELIVRD";
                $sendData = [];
                // print_r($send);
                // echo "\n";
                // continue;
                $sendData = explode(':',$send);
                $sendlog = [];
                if ($sendData[3] == 'DELIVRD') {
                    $status = 2;
                }
                $sendtask = $this->getSendTask($sendData[1]);
                $sendlog = [
                    'task_no' => $sendtask['task_no'],
                    'uid' => $sendtask['uid'],
                    'mobile' => $sendData[0],
                    'status_message' => $sendData[3],
                    'send_status' => $status,
                    'send_time' => time(),
                ];
                Db::startTrans();
                try {
                    //如果是行业
                    $task_id = Db::table('yx_user_send_task_log')->insertGetId($sendlog);

                    Db::commit();
                
                } catch (\Exception $e) {
                    Db::rollback();
                }
            }
        }
        echo "secuss";
    }

    private function getSendTask($id){
        $getSendTaskSql = sprintf("select id,uid,task_no from yx_user_send_task where delete_time=0 and id = %d", $id);
        // print_r($getUserSql);die;
        $sendTask = Db::query($getSendTaskSql);
        if (!$sendTask) {
            return [];
        }
        return $sendTask[0];
    }
}
