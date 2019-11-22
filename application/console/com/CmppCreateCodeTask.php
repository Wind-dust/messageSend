<?php

namespace app\console\com;

use app\console\Pzlife;
use cache\Phpredis;
use Config;
use Env;
use think\Db;

class CmppCreateCodeTask extends Pzlife {

    public function CreateCodeTask() {
        $redis                = Phpredis::getConn();
        $redisMessageCodeSend = 'index:meassage:code:send:task'; //验证码发送任务rediskey
        while (true) {
            $SendText = $redis->lPop($redisMessageCodeSend);
            if (empty($SendText)) {
                break;
            }
            $send = explode(':', $SendText);
            $user = $this->getUserInfo($send[0]);
            if (empty($user) || $user['user_status'] == 1) {
                break;
            }
            $send_code_task                   = [];
            $send_code_task['task_no']        = 'bus' . date('ymdHis') . substr(uniqid('', true), 15, 8);
            $send_code_task['task_content']   = $send[2];
            $send_code_task['mobile_content'] = $send[1];
            $send_code_task['uid']            = $send[0];
            $send_code_task['source']         = $send[4];
            $send_code_task['msg_id']         = $send[3];

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
            } elseif ($user['free_trial'] == 1) { //需审核用户
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

    public function getMessageLog() {
        $redis                = Phpredis::getConn();
        $redisMessageCodeSend = 'index:meassage:code:deliver:'; //验证码发送任务rediskey
        for ($i = 0; $i < 5; $i++) {
            $new_redisMessageCodeSend = $redisMessageCodeSend . $i;
            $send                     = $redis->lPop($new_redisMessageCodeSend);

            while ($send) {
                // $redis->rPush($new_redisMessageCodeSend);
                // $test = "13861218631:846:【米思米】尊敬的客户，已收到货款56.96元。请将需要下订的报价，在WOS报价订购平台操作到等待付款，或发送米思米报价单/PO单至我司。我司会根据贵司的入帐信息完成下订。贵司已在我司登录联系邮箱，同样的内容也会发送到该邮箱 ，负责人员如有变更或将有变更，请发送邮件至cs@misumi.sh.cn:DELIVRD";
                $sendData = [];
                // print_r($send);
                // echo "\n";
                // continue;
                $sendData = explode(':', $send);
                $sendlog  = [];
                if ($sendData[3] == 'DELIVRD') {
                    $status = 2;
                }
                $sendtask = $this->getSendTask($sendData[1]);
                $sendlog  = [
                    'task_no'        => $sendtask['task_no'],
                    'uid'            => $sendtask['uid'],
                    'mobile'         => $sendData[0],
                    'status_message' => $sendData[3],
                    'send_status'    => $status,
                    'send_time'      => time(),
                ];
                if (Db::query("SELECT `id` FROM yx_user_send_task_log WHERE `task_no` = '" . $sendlog['task_no'] . "' AND `uid` = " . $sendlog['uid'] . " AND `mobile` = " . $sendlog['mobile'] . " AND `status_message` = '" . $sendlog['status_message'] . "'")) {
                    continue;
                }
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

    private function getSendTask($id) {
        $getSendTaskSql = sprintf("select * from yx_user_send_task where delete_time=0 and id = %d", $id);
        // print_r($getUserSql);die;
        $sendTask = Db::query($getSendTaskSql);
        if (!$sendTask) {
            return [];
        }
        return $sendTask[0];
    }

    private function getSendTaskLog($task_no,$mobile) {
        $getSendTaskSql = "select 'id' from yx_user_send_task_log where delete_time=0 and `task_no` = '".$task_no."' and `mobile` = '".$mobile."'";
        // print_r($getUserSql);die;
        $sendTask = Db::query($getSendTaskSql);
        if (!$sendTask) {
            return [];
        }
        return $sendTask[0];
    }

    public function getNewMessageLog(){
        $redis                = Phpredis::getConn();
        $redisMessageCodeSend = 'index:meassage:code:new:deliver:'; //验证码发送任务rediskey
        $content = 2;
        $redisMessageCodeSequenceId = 'index:meassage:code:sequence:id:' . $content; //行业通知SequenceId
        $sequence = $redis->hget($redisMessageCodeSequenceId,2);
        $sequence = json_decode($sequence,true);
        $sendTask = $this->getSendTask($sequence['mar_task_id']);
        $send_log = $this->getSendTaskLog($sendTask['task_no'], $sequence['mobile']);
        
        // $msgid = $body['Msg_Id1'].$body['Msg_Id2'];
        $msgid = 155153131;
        // print_r($send_log);die;
        Db::startTrans();
                try {
                    if (empty($send_log)) {
                        Db::table('yx_user_send_task_log')->insert([
                            'task_no' => $sendTask['task_no'],
                            'mobile' => $sequence['mobile'],
                            'msgid' =>$msgid,
                            'send_status' =>2,
                            'create_time' =>time( )
                            ]);
            
                    }else{
                        Db::table('yx_user_send_task_log')->where('id',$send_log['id'])->update(['msgid' => $msgid]);
                    }
                    Db::commit();
                    $sequence = $redis->hdel($redisMessageCodeSequenceId,2);
        } catch (\Exception $e) {
            exception($e);
            Db::rollback();
                    
        }
        
        for ($i = 0; $i < 5; $i++) {
            $new_redisMessageCodeSend = $redisMessageCodeSend . $i;
            $send                     = $redis->lPop($new_redisMessageCodeSend);
            while ($send) {
                $newsend = json_decode($send);
                
            }
        }
    }

    //免审任务客户
    public function MisumiTaskSend() {
        $this->redis                = Phpredis::getConn();
        // $redisMessageMarketingSend = Config::get('rediskey.message.redisMessageCodeSend');
        $redisMessageMarketingSend = Config::get('rediskey.message.redisMessageCodeSend');
        $send = $this->redis->lpop($redisMessageMarketingSend);
        print_r($send);die;
        do {
            $sendtask = Db::query("SELECT * FROM yx_user_send_task WHERE  `uid` IN (4,6) AND `free_trial` = 1 AND `id` > 3291 LIMIT 1");
            // print_r($sendtask);die;
            $theSend = [];
            if ($sendtask) {
                $theSend = $sendtask[0];
                $num = 0;
                $mobilesend       = explode(',', $theSend['mobile_content']);
                $send_length     = mb_strlen($theSend['task_content'], 'utf8');
                $effective_mobile = [];
                foreach ($mobilesend as $key => $value) {
                    $num += ceil($send_length / 65) * $theSend['send_num'];
                    if (checkMobile($value)) {
                        $effective_mobile[] = $value;
                    }
                }
                // $num = ceil($send_length / 65) * $theSend['send_num'];
                if ($theSend['uid'] == 4) {
                    $user_equities = Db::query("SELECT * FROM yx_user_equities WHERE  `uid` =4 AND `business_id` = 5")[0];
                    // print_r($user_equities);die;
                    $channel_id = 2;
                }elseif($theSend['uid'] == 6) {
                    $user_equities = Db::query("SELECT * FROM yx_user_equities WHERE  `uid` =6 AND `business_id` = 6")[0];
                    // print_r($user_equities);die;
                    $channel_id = 1;
                }
                $had_num = $user_equities['num_balance'] - $num;
                // print_r($theSend['id']);die;
                Db::startTrans();
                try {
                    
                    Db::table('yx_user_send_task')->where('id',$theSend['id']) ->update(['free_trial' => 2,'channel_id' =>$channel_id ]);
                    Db::table('yx_user_equities')->where('id',$user_equities['id']) ->update(['num_balance' => $had_num]);

                    Db::commit();
                    $redisMessageMarketingSend = Config::get('rediskey.message.redisMessageCodeSend');
                    foreach ($effective_mobile as $key => $value) {
                        $res = $this->redis->rpush($redisMessageMarketingSend . ":" . $channel_id, $value . ":" . $theSend['id'] . ":" . $theSend['task_content']); //三体营销通道
                       
                        // $this->redis->hset($redisMessageMarketingSend.":2",$value,$id.":".$Content); //三体营销通道
                    }
                } catch (\Exception $e) {
                    exception($e);
                    Db::rollback();
                    
                }
            }
        } while ($theSend);
        echo "SUCCESS";
    }

    public function createMessageSendTaskLog(){
        $this->redis                = Phpredis::getConn();
        $redisMessageMarketingSend = Config::get('rediskey.message.redisMessageCodeSend');
        // $send = $this->redis->rPush('index:meassage:marketing:sendtask',15738);
        
        while (true) {
            $real_length = 1;
            $send = $this->redis->lpop('index:meassage:marketing:sendtask');
            if (empty($send)) {
                exit('taskId_is_null');
            }
            $sendTask = $this->getSendTask($send);
            $mobilesend       = explode(',', $sendTask['mobile_content']);
            $send_length     = mb_strlen($sendTask['task_content'], 'utf8');
            if ($send_length > 70) {
                $real_length = ceil($send_length / 67);
            }
            $real_num = 0;
            $real_num += $real_length* $sendTask['send_num'];
            $channel_id = 0;
            $channel_id = $sendTask['channel_id'];
            
            
            foreach ($mobilesend as $key => $kvalue) {
                $prefix = substr($kvalue, 0, 7);
                if (in_array($channel_id,[2,6,7,8])){
                    $res    = $this->getNumberSource($prefix);
                    print_r($res['source'])."\n";
                    if ($res['source'] == 2) { //米加联通营销
                        $channel_id = 8; 
                    } else if ($res['source'] == 1) { //移动
                        $channel_id = 2; 
                       
                    }else if ($res['source' == 3]) {//米加电信营销
                        $channel_id = 7; 
                    }
                }
                $send_log = [];
                $send_log = [
                    'task_no' => $sendTask['task_no'],
                    'uid' => $sendTask['uid'],
                    'mobile' => $kvalue,
                    'send_status' => 2,
                    'create_time' => time(),
                ];
                $sendmessage = [
                    'mobile' => $kvalue, 
                    'mar_task_id' => $sendTask['id'], 
                    'content' => $sendTask['task_content'], 
                ];
                if (Db::query("SELECT id FROM yx_user_send_task_log WHERE `task_no` = '".$sendTask['task_no']."' AND `mobile` = '".$kvalue."' ")) {
                    continue;
                }
                Db::startTrans();
                try {   
                    Db::table('yx_user_send_task_log')->insert($send_log);
                    $res = $this->redis->rpush($redisMessageMarketingSend . ":" . $channel_id,json_encode($sendmessage)); //三体营销通道
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                }
            }
            Db::startTrans();
                try {   
                    Db::table('yx_user_send_task')->where('id',$sendTask['id'])->update(['real_num'=> $real_num, 'send_status' =>3]);
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                }
        }
    }

    public function getNumberSource($prefix){
        $getSendTaskSql = "select source,province_id,province from yx_number_source where delete_time=0 and `mobile` = '".$prefix."'";
        // print_r($getUserSql);die;
        $sendTask = Db::query($getSendTaskSql);
        if (!$sendTask) {
            return [];
        }
        return $sendTask[0];
    }
}
