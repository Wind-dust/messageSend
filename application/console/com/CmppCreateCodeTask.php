<?php

namespace app\console\com;

use app\console\Pzlife;
use cache\Phpredis;
use Config;
use Env;
use think\Db;

class CmppCreateCodeTask extends Pzlife
{
    //游戏任务创建function
    public function CreateGameCodeTask()
    { //CMPP创建单条任务营销
        $redis                    = Phpredis::getConn();
        $redisMessageCodeSend     = 'index:meassage:game:sendtask'; //游戏日志待发送通道
        $redisMessageCodeSendReal = 'index:meassage:game:send:realtask'; //验证码发送真实任务rediskey CMPP接口 营销
        // echo date('Y-m-d H:i:s')."\n";
        /*  for ($i=0; $i < 100000; $i++) { 
            $redis->rpush($redisMessageCodeSendReal,json_encode([
                'mobile' => 15201926171,
                'message' => '【超变传奇】已为您发出688888元宝和VIP满级号，今日限领至尊屠龙！戳 https://ltv7.cn/3Ypm7 回T退订',
                'Src_Id' => '',//扩展码
                'Source_Addr' =>'101102',
                'send_msgid' => [
                    1576127228031159,
                ],
            'Service_Id' => '',//业务服务ID（企业代码）
            'Source_Addr' => 101102,//业务服务ID（企业代码）
                // 'uid' => 45,
                'Submit_time' => 1212130708,
            ]));
        } */

        // echo date('Y-m-d H:i:s')."\n";die;
        while (true) {
            $SendText = $redis->lPop($redisMessageCodeSendReal);
            if (empty($SendText)) {
                // echo date('Y-m-d H:i:s')."\n";die;
                // exit('send_task is_null');
                continue;
            }
            // $send = explode(':', $SendText);
            $send = json_decode($SendText, true);
            // $user = $this->getUserInfo($send[0]);
            $channel_id = 0;

            if ($send['Source_Addr'] == 101102) { //移动
                $uid = 45;
                $channel_id = 14;
            }
            if ($send['Source_Addr'] == 101103) { //联通
                $uid = 58;
                $channel_id = 28;
            }
            if ($send['Source_Addr'] == 101104) { //电信
                $uid = 59;
                $channel_id = 29;
            }
            $user = $this->getUserInfo($uid);
            if (empty($user) || $user['user_status'] == 1) {
                continue;
            }
            $userEquities = $this->getUserEquities($uid, 9); //游戏业务
            if (empty($userEquities)) {
                continue;
            }
            if ($userEquities['num_balance'] < 1 && $user['reservation_service'] == 1) {
                continue;
            }

            $send_code_task            = [];
            $send_code_task['task_no'] = 'gam' . date('ymdHis') . substr(uniqid('', true), 15, 8);
            // $send_code_task['task_content']   = $send[2];
            // $send_code_task['mobile_content'] = $send[1];
            // $send_code_task['uid']            = $send[0];
            // $send_code_task['source']         = $send[4];
            // $send_code_task['msg_id']         = $send[3];

            $send_code_task['send_msg_id']    = join(',', $send['send_msgid']);
            $send_code_task['uid']            = $uid;
            $send_code_task['task_content']   = trim($send['message']);
            $send_code_task['submit_time']    = $send['Submit_time'];
            $send_code_task['create_time']    = time();
            $send_code_task['mobile_content'] = $send['mobile'];
            $send_code_task['send_num']       = 1;
            $send_code_task['channel_id']       = $channel_id;
            $send_code_task['send_length']    = mb_strlen(trim($send['message']));
            // $sendData['uid']          = 1;
            // $sendData['Submit_time']  = date('YMDHM', time());
            //免审用户
            // print_r($send_code_task);die;
            // print_r($user);die;
            if ($user['free_trial'] == 2) {
                Db::startTrans();
                try {
                    $send_code_task['free_trial'] = 2;
                    if ($userEquities['num_balance'] < 1) {
                        $send_code_task['free_trial'] = 1;
                    }
                    //游戏任务
                    $task_id = Db::table('yx_user_send_game_task')->insertGetId($send_code_task);
                    //扣除余额
                    $new_num_balance = $userEquities['num_balance'] - 1;
                    Db::table('yx_user_equities')->where('id', $userEquities['id'])->update(['num_balance' => $new_num_balance]);
                    Db::commit();
                    $redis->rPush('index:meassage:game:sendtask', $task_id);
                } catch (\Exception $e) {
                    $redis->rPush($redisMessageCodeSendReal, $SendText);
                    exception($e);
                    Db::rollback();
                }
            } elseif ($user['free_trial'] == 1) { //需审核用户
                Db::startTrans();
                try {
                    $send_code_task['free_trial'] = 1;
                    $task_id                      = Db::table('yx_user_send_game_task')->insertGetId($send_code_task);
                    //扣除余额
                    $new_num_balance = $userEquities['num_balance'] - 1;
                    Db::table('yx_user_equities')->where('id', $userEquities['id'])->update(['num_balance' => $new_num_balance]);
                    Db::commit();
                } catch (\Exception $e) {
                    exception($e);
                    Db::rollback();
                }
            }
            // print_r($user);die;
        }
    }

    private function getUserInfo($uid)
    {
        $getUserSql = sprintf("select id,user_status,reservation_service,free_trial from yx_users where delete_time=0 and id = %d", $uid);
        // print_r($getUserSql);die;
        $userInfo = Db::query($getUserSql);
        if (!$userInfo) {
            return [];
        }
        return $userInfo[0];
    }

    private function getUserEquities($uid, $business_id)
    {

        $userEquities = Db::query("SELECT `id`,`num_balance` FROM yx_user_equities WHERE  `delete_time` = 0 AND `uid` = " . $uid . " AND `business_id` = " . $business_id);
        // print_r("SELECT `id`,`num_balance` FROM yx_user_equities WHERE  `delete_time` = 0 AND `uid` = " . $uid . " AND `business_id` = " . $business_id);
        if (!$userEquities) {
            return [];
        }
        return $userEquities[0];
    }

    public function getMessageLog()
    {
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
        echo "sucess";
    }

    private function getSendTask($id)
    {
        $getSendTaskSql = sprintf("select * from yx_user_send_task where delete_time=0 and id = %d", $id);
        $sendTask       = Db::query($getSendTaskSql);
        // print_r($sendTask);die;
        if (!$sendTask) {
            return [];
        }
        return $sendTask[0];
    }

    private function getSendCodeTask($id)
    {
        $getSendTaskSql = sprintf("select * from yx_user_send_code_task where delete_time=0 and id = %d", $id);
        $sendTask       = Db::query($getSendTaskSql);

        if (!$sendTask) {
            return [];
        }
        return $sendTask[0];
    }

    private function getSendGameTask($id)
    {
        $getSendTaskSql = sprintf("select * from yx_user_send_game_task where delete_time=0 and id = %d", $id);
        $sendTask       = Db::query($getSendTaskSql);
        // print_r($sendTask);die;
        if (!$sendTask) {
            return [];
        }
        return $sendTask[0];
    }

    private function getSendTaskLog($task_no, $mobile)
    {
        $getSendTaskSql = "select 'id' from yx_user_send_task_log where delete_time=0 and `task_no` = '" . $task_no . "' and `mobile` = '" . $mobile . "'";
        // print_r($getUserSql);die;
        $sendTask = Db::query($getSendTaskSql);
        if (!$sendTask) {
            return [];
        }
        return $sendTask[0];
    }

    private function getMultimediaSendTask($id)
    {
        $getSendTaskSql = sprintf("select * from yx_user_multimedia_message where delete_time=0 and id = %d", $id);
        // print_r($getUserSql);die;
        $sendTask = Db::query($getSendTaskSql);
        if (!$sendTask) {
            return [];
        }
        $sendTask                 = $sendTask[0];
        $content_data             = Db::query("select `id`,`content`,`num`,`image_path`,`image_type` from yx_user_multimedia_message_frame where delete_time=0 and `multimedia_message_id` = " . $sendTask['id'] . "  ORDER BY `num` ASC ");
        $sendTask['task_content'] = $content_data;
        return $sendTask;
    }

    private function getMultimediaSendTaskLog($task_no, $mobile)
    {
        $getSendTaskSql = "select `id` from yx_user_multimedia_message_log where delete_time=0 and `task_no` = '" . $task_no . "' and `mobile` = '" . $mobile . "'";
        // print_r($getUserSql);die;
        $sendTask = Db::query($getSendTaskSql);
        if (!$sendTask) {
            return [];
        }
        return $sendTask[0];
    }

    public function getNewMessageLog()
    {
        $redis                      = Phpredis::getConn();
        $redisMessageCodeSend       = 'index:meassage:code:new:deliver:'; //验证码发送任务rediskey
        $content                    = 2;
        $redisMessageCodeSequenceId = 'index:meassage:code:sequence:id:' . $content; //行业通知SequenceId
        $sequence                   = $redis->hget($redisMessageCodeSequenceId, 2);
        $sequence                   = json_decode($sequence, true);
        $sendTask                   = $this->getSendTask($sequence['mar_task_id']);
        $send_log                   = $this->getSendTaskLog($sendTask['task_no'], $sequence['mobile']);

        // $msgid = $body['Msg_Id1'].$body['Msg_Id2'];
        $msgid = 155153131;
        // print_r($send_log);die;
        Db::startTrans();
        try {
            if (empty($send_log)) {
                Db::table('yx_user_send_task_log')->insert([
                    'task_no'     => $sendTask['task_no'],
                    'mobile'      => $sequence['mobile'],
                    'msgid'       => $msgid,
                    'send_status' => 2,
                    'create_time' => time(),
                ]);
            } else {
                Db::table('yx_user_send_task_log')->where('id', $send_log['id'])->update(['msgid' => $msgid]);
            }
            Db::commit();
            $sequence = $redis->hdel($redisMessageCodeSequenceId, 2);
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
        exit("success");
    }

    //免审任务客户
    public function MisumiTaskSend()
    {
        $this->redis = Phpredis::getConn();
        // $redisMessageMarketingSend = Config::get('rediskey.message.redisMessageCodeSend');
        $redisMessageMarketingSend = Config::get('rediskey.message.redisMessageCodeSend');
        $send                      = $this->redis->lpop($redisMessageMarketingSend);
        print_r($send);
        die;
        do {
            $sendtask = Db::query("SELECT * FROM yx_user_send_task WHERE  `uid` IN (4,6) AND `free_trial` = 1 AND `id` > 3291 LIMIT 1");
            // print_r($sendtask);die;
            $theSend = [];
            if ($sendtask) {
                $theSend          = $sendtask[0];
                $num              = 0;
                $mobilesend       = explode(',', $theSend['mobile_content']);
                $send_length      = mb_strlen($theSend['task_content'], 'utf8');
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
                } elseif ($theSend['uid'] == 6) {
                    $user_equities = Db::query("SELECT * FROM yx_user_equities WHERE  `uid` =6 AND `business_id` = 6")[0];
                    // print_r($user_equities);die;
                    $channel_id = 1;
                }
                $had_num = $user_equities['num_balance'] - $num;
                // print_r($theSend['id']);die;
                Db::startTrans();
                try {

                    Db::table('yx_user_send_task')->where('id', $theSend['id'])->update(['free_trial' => 2, 'channel_id' => $channel_id]);
                    Db::table('yx_user_equities')->where('id', $user_equities['id'])->update(['num_balance' => $had_num]);

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

    //书写营销任务日志并写入通道
    public function createMessageSendTaskLog()
    {
        $this->redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        // date_default_timezone_set('PRC');
        $redisMessageMarketingSend = Config::get('rediskey.message.redisMessageCodeSend');
        // $send = $this->redis->rPush('index:meassage:marketing:sendtask',json_encode(['id' => 15850,'send_time' => 0]));
        // $send = $this->redis->rPush('index:meassage:marketing:sendtask',json_encode(['id' => 15823,'send_time' => 0]));
        // $send = $this->redis->rPush('index:meassage:marketing:sendtask',json_encode(['id' => 15824,'send_time' => 0]));
        // $send = $this->redis->rPush('index:meassage:marketing:sendtask',json_encode(['id' => 15825,'send_time' => 0]));
        // $send = $this->redis->rPush('index:meassage:marketing:sendtask',json_encode(['id' => 15826,'send_time' => 0]));
        // $send = $this->redis->rPush('index:meassage:marketing:sendtask',json_encode(['id' => 15827,'send_time' => 0]));
        // echo time() -1576290017;die;
        echo date('Y-m-d H:i:s');
        echo "\n";
        while (true) {
            $real_length = 1;
            $send        = $this->redis->lpop('index:meassage:marketing:sendtask');
            // $send = 15753;
            if (empty($send)) {
                exit('taskId_is_null');
            }
            $real_send = json_decode($send, true);
            if ($real_send['send_time'] > time()) {
                $this->redis->rPush('index:meassage:marketing:sendtask', json_encode($real_send));
                continue;
            }
            $sendTask = $this->getSendTask($real_send['id']);
            // print_r($sendTask);die;
            if (empty($sendTask)) {
                exit('task_is_null');
            }
            $mobilesend = [];
            // print_r($sendTask);die;
            $mobilesend  = explode(',', $sendTask['mobile_content']);
            $send_length = mb_strlen($sendTask['task_content'], 'utf8');
            if ($send_length > 70) {
                $real_length = ceil($send_length / 67);
            }
            $real_num = 0;
            $real_num += $real_length * $sendTask['send_num'];
            $channel_id    = 0;
            $channel_id    = $sendTask['channel_id'];
            $push_messages = [];
            $error_mobile = [];
            // print_r($sendTask);die;
            if (file_exists(realpath("") . '/tasklog/marketing/' . $sendTask['task_no'] . ".txt")) {
                continue;
            }
            $myfile = fopen(realpath("") . '/tasklog/marketing/' . $sendTask['task_no'] . ".txt", "w");
            // $myfile = fopen("testfile.txt", "w");
            // die;

            for ($i = 0; $i < count($mobilesend); $i++) {
                $send_log = [];
                if (checkMobile(trim($mobilesend[$i])) == true) {
                    $prefix = substr(trim($mobilesend[$i]), 0, 7);
                    $res    = Db::query("SELECT `source`,`province_id`,`province` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                    $newres = array_shift($res);
                    if ($newres) {
                        // if ($newres['source'] == 2 && in_array($sendTask['uid'],[47,49,51,52])) { //聚梦联通营销
                        //     $channel_id = 19;
                        // } else if ($newres['source'] == 1 && in_array($sendTask['uid'],[47,49,51,52])) { //聚梦移动
                        //     $channel_id = 18;

                        // } else if ($newres['source']== 3 && in_array($sendTask['uid'],[47,49,51,52])) { //聚梦电信营销
                        //     $channel_id = 7;
                        // }
                        if ($newres['source'] == 1) {
                            $channel_id = 17;
                        } elseif ($channel_id == 17 && $newres['source'] == 2) {
                            // $channel_id = 8;
                            $channel_id = 30;
                        } elseif ($channel_id == 17 && $newres['source'] == 3) {
                            // $channel_id = 7;
                            $channel_id = 17;
                        }
                    }
                    // print_r($newres);
                    $send_log = [
                        'task_no'     => $sendTask['task_no'],
                        'uid'         => $sendTask['uid'],
                        'title'       => $sendTask['task_name'],
                        'content'     => $sendTask['task_content'],
                        'mobile'      => $mobilesend[$i],
                        'send_status' => 2,
                        'create_time' => time(),
                    ];
                    $sendmessage = [
                        'mobile'      => $mobilesend[$i],
                        'mar_task_id' => $sendTask['id'],
                        'content'     => $sendTask['task_content'],
                        'channel_id'  => $channel_id,
                    ];
                    // $has = Db::query("SELECT id FROM yx_user_send_task_log WHERE `task_no` = '" . $sendTask['task_no'] . "' AND `mobile` = '" . $mobilesend[$i] . "' ");
                    // echo $i."\n";
                    // if ($has) {
                    //     continue;
                    //     // Db::table('yx_user_send_task_log')->where('id', $has[0]['id'])->update(['create_time' => time()]);
                    // }

                    // Db::table('yx_user_send_task_log')->insert($send_log);
                    // $res = $this->redis->rpush($redisMessageMarketingSend . ":" . $channel_id, json_encode($sendmessage)); //三体营销通道
                    $txt = json_encode($send_log) . "\n";
                    fwrite($myfile, $txt);
                    $push_messages[] = $sendmessage;
                } else {
                    $send_log = [
                        'task_no'        => $sendTask['task_no'],
                        'uid'            => $sendTask['uid'],
                        'title'          => $sendTask['task_name'],
                        'content'        => $sendTask['task_content'],
                        'mobile'         => $mobilesend[$i],
                        'send_status'    => 4,
                        'create_time'    => time(),
                        'status_message' => 'DB:0101', //无效号码
                        'real_message'   => 'DB:0101',
                    ];
                    $txt = json_encode($send_log) . "\n";
                    fwrite($myfile, $txt);
                    $error_mobile[] = $send_log;
                }
            }
            fclose($myfile);

            Db::startTrans();
            try {
                Db::table('yx_user_send_task')->where('id', $sendTask['id'])->update(['real_num' => $real_num, 'send_status' => 3, 'log_path' => realpath("") . '/tasklog/marketing/' . $sendTask['task_no'] . ".txt"]);
                Db::commit();
                foreach ($push_messages as $key => $value) {
                    $send_channelid = $value['channel_id'];
                    // $send_channelid =1;
                    unset($value['channel_id']);
                    $res = $this->redis->rpush($redisMessageMarketingSend . ":" . $send_channelid, json_encode($value)); //三体营销通道
                }
                foreach ($error_mobile as $key => $value) {
                    if ($value['uid'] == 47 || $value['uid'] == 51) {
                        $request_url = "http://116.228.60.189:25902/rtreceive?";
                        $request_url .= 'task_no=' . $value['task_no'] . "&status_message=" . $value['Stat'] . "&mobile=" . $value['mobile'] . "&send_time=" . $value['create_time'];
                        sendRequest($request_url);
                        print_r($request_url);
                    }
                }
            } catch (\Exception $e) {
                $this->redis->rPush('index:meassage:marketing:sendtask', $send);
                exception($e);
                Db::rollback();
            }
        }
        print_r(date('Y-m-d H:i:s', time()));
        echo "\n";
        exit("SUCCESS");
    }
    //  大批量扣量任务扣量
    public function createNoSendMessageSendTaskLog()
    {
        $this->redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        // date_default_timezone_set('PRC');
        $redisMessageMarketingSend = Config::get('rediskey.message.redisMessageCodeSend');
        // $send = $this->redis->rPush('index:meassage:marketing:sendtask',15751);
        // $send = $this->redis->rPush('index:meassage:marketing:sendtask', 15743);
        // $send = $this->redis->rPush('index:meassage:marketing:sendtask',15740);
        // $send = $this->redis->rPush('index:meassage:marketing:sendtask',15741);
        // echo time() -1576290017;die;
        while (true) {
            $real_length = 1;
            // $send        = $this->redis->lpop('index:meassage:marketing:sendtask');
            // $send = 15751;

            $sendTask = $this->getSendTask($send);
            if (empty($sendTask)) {
                exit('taskId_is_null');
            }
            $mobilesend = [];
            // print_r($sendTask);die;
            $mobilesend  = explode(',', $sendTask['mobile_content']);
            $send_length = mb_strlen($sendTask['task_content'], 'utf8');
            if ($send_length > 70) {
                $real_length = ceil($send_length / 67);
            }
            $real_num = 0;
            $real_num += $real_length * $sendTask['send_num'];
            $channel_id    = 0;
            $channel_id    = $sendTask['channel_id'];
            $push_messages = [];
            // print_r($sendTask);die;
            Db::startTrans();
            try {

                for ($i = 0; $i < count($mobilesend); $i++) {
                    $send_log = [];
                    if (checkMobile($mobilesend[$i]) == true) {
                        $prefix = substr(trim($mobilesend[$i]), 0, 7);
                        $res    = Db::query("SELECT `source`,`province_id`,`province` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                        $newres = array_shift($res);
                        // if ($newres) {
                        //     if ($newres['source'] == 2) { //米加联通营销
                        //         $channel_id = 8;
                        //     } else if ($newres['source'] == 1) { //蓝鲸
                        //         $channel_id = 2;

                        //     } else if ($newres['source' == 3]) { //米加电信营销
                        //         $channel_id = 7;
                        //     }

                        // }
                        // print_r($newres);
                        $send_log = [
                            'task_no'     => $sendTask['task_no'],
                            'uid'         => $sendTask['uid'],
                            'mobile'      => $mobilesend[$i],
                            'send_status' => 2,
                            'create_time' => time() - 5063,
                        ];
                        $sendmessage = [
                            'mobile'      => $mobilesend[$i],
                            'mar_task_id' => $sendTask['id'],
                            'content'     => $sendTask['task_content'],
                            'channel_id'  => $channel_id,
                        ];
                        $has = Db::query("SELECT id FROM yx_user_send_task_log WHERE `task_no` = '" . $sendTask['task_no'] . "' AND `mobile` = '" . $mobilesend[$i] . "' ");
                        // echo $i."\n";
                        if ($has) {
                            continue;
                            // Db::table('yx_user_send_task_log')->where('id', $has[0]['id'])->update(['create_time' => time()]);
                        }

                        Db::table('yx_user_send_task_log')->insert($send_log);
                        // $res = $this->redis->rpush($redisMessageMarketingSend . ":" . $channel_id, json_encode($sendmessage)); //三体营销通道
                        $push_messages[] = $sendmessage;
                    }
                }
                Db::table('yx_user_send_task')->where('id', $sendTask['id'])->update(['real_num' => $real_num, 'send_status' => 3]);
                Db::commit();
                foreach ($push_messages as $key => $value) {
                    $send_channelid = $value['channel_id'];
                    unset($value['channel_id']);
                    // $res = $this->redis->rpush($redisMessageMarketingSend . ":" . $send_channelid, json_encode($value)); //三体营销通道
                }
            } catch (\Exception $e) {
                $this->redis->rPush('index:meassage:marketing:sendtask', $send);
                exception($e);
                Db::rollback();
            }
            // foreach ($mobilesend as $key => $kvalue) {
            //     if (in_array($channel_id, [2, 6, 7, 8])) {
            //         // $getSendTaskSql = "select source,province_id,province from yx_number_source where `mobile` = '".$prefix."' LIMIT 1";
            //     }
            // }
            exit("SUCCESS");
        }
    }

    //书写彩信任务日志并写入通道
    public function createMultimediaMessageSendTaskLog()
    {
        $this->redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        // date_default_timezone_set('PRC');
        $redisMessageMarketingSend = 'index:meassage:multimediamessage:sendtask';
        // $send                      = $this->redis->rPush('index:meassage:multimediamessage:sendtask', 1);
        // echo time() -1574906657;die;
        while (true) {
            $real_length = 1;
            $send        = $this->redis->lpop('index:meassage:multimediamessage:sendtask');
            // $send = 15745;

            $sendTask = $this->getMultimediaSendTask($send);
            if (empty($sendTask)) {
                exit('taskId_is_null');
            }
            $mobilesend = [];
            // print_r($sendTask);die;
            $mobilesend = explode(',', $sendTask['mobile_content']);
            // $send_length = mb_strlen($sendTask['task_content'], 'utf8');
            $real_length = 1;
            // if ($send_length > 70) {
            //     $real_length = ceil($send_length / 67);
            // }
            $real_num = 0;
            $real_num += $real_length * $sendTask['send_num'];
            $channel_id    = 0;
            $channel_id    = $sendTask['channel_id'];
            $push_messages = [];
            $send_content  = '';
            if (file_exists(realpath("") . '/tasklog/multimedia/' . $sendTask['task_no'] . ".txt")) {
                continue;
            }
            $myfile = fopen(realpath("") . '/tasklog/multimedia/' . $sendTask['task_no'] . ".txt", "w");
            // if (!empty($sendTask['content'])) {

            // }

            for ($i = 0; $i < count($mobilesend); $i++) {
                $send_log = [];
                if (checkMobile(trim($mobilesend[$i])) == true) {
                    $prefix = substr(trim($mobilesend[$i]), 0, 7);
                    $res    = Db::query("SELECT `source`,`province_id`,`province` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                    $newres = array_shift($res);
                    // if ($newres) {
                    //     if ($newres['source'] == 2) { //米加联通营销
                    //         $channel_id = 8;
                    //     } else if ($newres['source'] == 1) { //蓝鲸
                    //         $channel_id = 2;

                    //     } else if ($newres['source' == 3]) { //米加电信营销
                    //         $channel_id = 7;
                    //     }

                    // }
                    // print_r($newres);
                    $send_log = [
                        'task_no'      => $sendTask['task_no'],
                        'uid'          => $sendTask['uid'],
                        'source'       => $sendTask['source'],
                        'task_content' => $sendTask['task_content'],
                        'mobile'       => $mobilesend[$i],
                        'send_status'  => 2,
                        'create_time'  => time(),
                    ];
                    $sendmessage = [
                        'mobile'      => $mobilesend[$i],
                        'title'       => $sendTask['title'],
                        'mar_task_id' => $sendTask['id'],
                        'content'     => $sendTask['task_content'],
                        'channel_id'  => $channel_id,
                    ];

                    $txt = json_encode($send_log) . "\n";
                    fwrite($myfile, $txt);
                    // $res = $this->redis->rpush($redisMessageMarketingSend . ":" . $channel_id, json_encode($sendmessage)); //三体营销通道
                    $push_messages[] = $sendmessage;
                } else {
                    $send_log = [
                        'task_no'        => $sendTask['task_no'],
                        'uid'            => $sendTask['uid'],
                        'title'          => $sendTask['task_name'],
                        'content'        => $sendTask['task_content'],
                        'mobile'         => $mobilesend[$i],
                        'send_status'    => 4,
                        'create_time'    => time(),
                        'status_message' => 'DB:0101', //无效号码
                        'real_message'   => 'DB:0101',
                    ];
                    $txt = json_encode($send_log) . "\n";
                    fwrite($myfile, $txt);
                }
            }

            Db::startTrans();
            try {
                Db::table('yx_user_multimedia_message')->where('id', $sendTask['id'])->update(['real_num' => $real_num, 'send_status' => 3, 'log_path' => realpath("") . '/tasklog/marketing/' . $sendTask['task_no'] . ".txt"]);
                Db::commit();
                foreach ($push_messages as $key => $value) {
                    $send_channelid = $value['channel_id'];
                    unset($value['channel_id']);
                    $res = $this->redis->rpush('index:meassage:code:send' . ":" . $send_channelid, json_encode($value)); //三体营销通道
                }
            } catch (\Exception $e) {
                $this->redis->rPush('index:meassage:multimediamessage:sendtask', $send);
                exception($e);
                Db::rollback();
            }
            // foreach ($mobilesend as $key => $kvalue) {
            //     if (in_array($channel_id, [2, 6, 7, 8])) {
            //         // $getSendTaskSql = "select source,province_id,province from yx_number_source where `mobile` = '".$prefix."' LIMIT 1";
            //     }
            // }
            // exit("SUCCESS");
        }
    }

    //书写行业通知任务日志并写入通道
    public function createBusinessMessageSendTaskLog()
    {
        $this->redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        // date_default_timezone_set('PRC');
        $redisMessageMarketingSend = 'index:meassage:business:sendtask';
        // for ($i = 20000; $i < 30000; $i++) {
        //     $this->redis->rPush('index:meassage:business:sendtask', $i);
        // }

        $push_messages = []; //推送队列
        $rollback = [];
        $all_log = [];
        $j = 1;
        // echo time() -1574906657;die;
        while (true) {
            // echo time() . "\n";
            while (true) {
                $send        = $this->redis->lpop('index:meassage:business:sendtask');
                // $send = 15745;
                if (empty($send)) {
                    break;
                }
                $rollback[] = $send;
                $sendTask = $this->getSendCodeTask($send);
                if (empty($sendTask)) {
                    // echo 'taskId_is_null' . "\n";
                    // break;
                    continue;
                }
                if (empty($sendTask['channel_id'])) {
                    continue;
                }
                $mobilesend = [];
                // print_r($sendTask);die;
                $mobilesend = explode(',', $sendTask['mobile_content']);
                $mobilesend = array_filter($mobilesend);
                /*  $send_length = mb_strlen($sendTask['task_content'], 'utf8');
                $real_length = 1;
                if ($send_length > 70) {
                    $real_length = ceil($send_length / 67);
                }
                $real_num = 0;
                $real_num += $real_length * $sendTask['send_num']; */
                $channel_id    = 0;
                $channel_id    = $sendTask['channel_id'];
                if (empty($channel_id)) {
                    continue;
                }
                //判断任务手机号数量,如果大批量就按任务记录文件夹否则按日期
                // if (count($mobilesend) > 10000) {//默认1万

                // }
                for ($i = 0; $i < count($mobilesend); $i++) {
                    $send_log = [];
                    $sendmessage = [];
                    if (checkMobile(trim($mobilesend[$i])) == true) {
                        // $prefix = substr(trim($mobilesend[$i]), 0, 7);
                        // $res    = Db::query("SELECT `source`,`province_id`,`province` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                        // $newres = array_shift($res);
                        //通道组分配
                        // if ($newres) {
                        //     if ($newres['source'] == 2) { //米加联通营销
                        //         $channel_id = 8;
                        //     } else if ($newres['source'] == 1) { //蓝鲸
                        //         $channel_id = 2;

                        //     } else if ($newres['source' == 3]) { //米加电信营销
                        //         $channel_id = 7;
                        //     }

                        // }
                        // print_r($newres);
                        $send_log = [
                            'task_no'      => $sendTask['task_no'],
                            'uid'          => $sendTask['uid'],
                            'source'       => $sendTask['source'],
                            'task_content' => $sendTask['task_content'],
                            'mobile'       => $mobilesend[$i],
                            'send_status'  => 2,
                            // 'channel_id'  => $channel_id,
                            'create_time'  => time(),
                        ];
                        $sendmessage = [
                            'mobile'      => $mobilesend[$i],
                            'title'       => $sendTask['task_name'],
                            'mar_task_id' => $sendTask['id'],
                            'content'     => $sendTask['task_content'],
                            'channel_id'  => $channel_id,
                        ];

                        // $res = $this->redis->rpush($redisMessageMarketingSend . ":" . $channel_id, json_encode($sendmessage)); //三体营销通道
                        $push_messages[] = $sendmessage;
                    } else {
                        $send_log = [
                            'task_no'        => $sendTask['task_no'],
                            'uid'            => $sendTask['uid'],
                            'title'          => $sendTask['task_name'],
                            'content'        => $sendTask['task_content'],
                            'mobile'         => $mobilesend[$i],
                            'send_status'    => 4,
                            'create_time'    => time(),
                            'status_message' => 'DB:0101', //无效号码
                            'real_message'   => 'DB:0101',
                        ];
                    }
                    $all_log[] = $send_log;
                    $j++;
                    if ($j > 500) {
                        $j = 1;
                        Db::startTrans();
                        try {
                            Db::table('yx_user_send_code_task_log')->insertAll($all_log);

                            foreach ($push_messages as $key => $value) {
                                $send_channelid = $value['channel_id'];
                                unset($value['channel_id']);
                                $res = $this->redis->rpush('index:meassage:code:send' . ":" . $send_channelid, json_encode($value)); //三体营销通道
                            }
                            Db::commit();
                        } catch (\Exception $e) {
                            // $this->redis->rPush('index:meassage:business:sendtask', $send);
                            foreach ($rollback as $key => $value) {
                                $this->redis->rPush('index:meassage:business:sendtask', $value);
                            }
                            Db::rollback();
                            exception($e);
                        }
                        unset($all_log);
                        unset($push_messages);
                        // echo time() . "\n";
                    }
                }

                // foreach ($mobilesend as $key => $kvalue) {
                //     if (in_array($channel_id, [2, 6, 7, 8])) {
                //         // $getSendTaskSql = "select source,province_id,province from yx_number_source where `mobile` = '".$prefix."' LIMIT 1";
                //     }
                // }
                // exit("SUCCESS");
            }

            if (!empty($all_log)) {
                Db::startTrans();
                try {
                    Db::table('yx_user_send_code_task_log')->insertAll($all_log);
                    Db::commit();
                    foreach ($push_messages as $key => $value) {
                        $send_channelid = $value['channel_id'];
                        unset($value['channel_id']);
                        $res = $this->redis->rpush('index:meassage:code:send' . ":" . $send_channelid, json_encode($value)); //三体营销通道
                    }
                } catch (\Exception $e) {
                    // $this->redis->rPush('index:meassage:business:sendtask', $send);
                    foreach ($rollback as $key => $value) {
                        $this->redis->rPush('index:meassage:business:sendtask', $value);
                    }
                    Db::rollback();
                    exception($e);
                }
                unset($all_log);
                unset($push_messages);
            }
            // echo time() . "\n";
            // exit('success');
        }
    }

    //书写游戏任务日志并写入通道
    public function createGameMessageSendTaskLog()
    {
        $this->redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        // date_default_timezone_set('PRC');
        $redisMessageMarketingSend = 'index:meassage:game:sendtask';
        // for ($i=6; $i < 49; $i++) { 
        //     $send                      = $this->redis->rPush('index:meassage:game:sendtask', $i);
        // }
        // echo time() -1574906657;die;
        while (true) {
            $real_length = 1;
            $send        = $this->redis->lpop('index:meassage:game:sendtask');
            // $send = 15745;

            $sendTask = $this->getSendGameTask($send);
            if (empty($sendTask)) {
                // exit('taskId_is_null');
                continue;
            }
            $mobilesend = [];
            // print_r($sendTask);die;
            $mobilesend = explode(',', $sendTask['mobile_content']);
            $send_length = mb_strlen($sendTask['task_content'], 'utf8');
            $real_length = 1;
            if ($send_length > 70) {
                $real_length = ceil($send_length / 67);
            }
            $real_num = 0;
            $real_num += $real_length * $sendTask['send_num'];
            $channel_id    = 0;
            $channel_id    = $sendTask['channel_id'];
            $push_messages = [];
            $send_content  = '';
            //判断任务手机号数量,如果大批量就按任务记录文件夹否则按日期
            // if (count($mobilesend) > 10000) {//默认1万

            // }
            if (file_exists(realpath("") . '/tasklog/game/' . $sendTask['task_no'] . ".txt")) {
                continue;
            }
            $myfile = fopen(realpath("") . '/tasklog/game/' . $sendTask['task_no'] . ".txt", "w");
            // if (!empty($sendTask['content'])) {

            // }

            for ($i = 0; $i < count($mobilesend); $i++) {
                $send_log = [];
                if (checkMobile(trim($mobilesend[$i])) == true) {
                    $prefix = substr(trim($mobilesend[$i]), 0, 7);
                    $res    = Db::query("SELECT `source`,`province_id`,`province` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                    $newres = array_shift($res);
                    //游戏通道分流
                    if ($newres) {
                        if ($newres['source'] == 2) { //米加联通营销
                            $channel_id = 28;
                        } else if ($newres['source'] == 1) { //蓝鲸
                            $channel_id = 14;
                        } else if ($newres['source' == 3]) { //米加电信营销
                            $channel_id = 29;
                        }
                    }
                    print_r($newres);
                    if (strpos($mobilesend[$i], '00000') || strpos($mobilesend[$i], '111111') || strpos($mobilesend[$i], '222222') || strpos($mobilesend[$i], '333333') || strpos($mobilesend[$i], '444444') || strpos($mobilesend[$i], '555555') || strpos($mobilesend[$i], '666666') || strpos($mobilesend[$i], '777777') || strpos($mobilesend[$i], '888888') || strpos($mobilesend[$i], '999999')) {
                        $send_log = [
                            'task_no'        => $sendTask['task_no'],
                            'uid'            => $sendTask['uid'],
                            'title'          => $sendTask['task_name'],
                            'content'        => $sendTask['task_content'],
                            'mobile'         => $mobilesend[$i],
                            'send_status'    => 4,
                            'create_time'    => time(),
                            'status_message' => 'DB:0101', //无效号码
                            'real_message'   => 'DB:0101',
                        ];
                        $this->redis->rPush('index:meassage:game:waitcmppdeliver', json_encode([
                            'Stat'        => $send_log['status_message'],
                            'send_msgid'  => [$sendTask['send_msg_id']],
                            'Done_time'   => date('ymdHis', time()),
                            'content'     =>  $sendTask['task_content'],
                            'Submit_time' => date('ymdHis', time()),
                            'mobile'      => $send_log['mobile'],
                            'uid'         =>  $sendTask['uid'],
                            'mar_task_id' => $sendTask['id'],
                        ]));
                    } else {

                        $send_log = [
                            'task_no'      => $sendTask['task_no'],
                            'uid'          => $sendTask['uid'],
                            'source'       => $sendTask['source'],
                            'task_content' => $sendTask['task_content'],
                            'mobile'       => $mobilesend[$i],
                            'send_status'  => 2,
                            'channel_id'  => $channel_id,
                            'create_time'  => time(),
                        ];
                        $sendmessage = [
                            'mobile'      => $mobilesend[$i],
                            'title'       => $sendTask['task_name'],
                            'mar_task_id' => $sendTask['id'],
                            'content'     => $sendTask['task_content'],
                            'channel_id'  => $channel_id,
                        ];
                        $max = mt_rand(9, 11);
                        $num     = mt_rand(0, 100);
                        if ($num <= $max) { //扣量
                            if (in_array($mobilesend[$i], [18339998120, 13812895012])) {
                                $push_messages[] = $sendmessage; //实际发送队列
                            }
                            $send_log['status_message'] = 'DELIVRD'; //推送到虚拟不发送队列
                            $push_messages[] = $sendmessage; //实际发送队列

                            // $this->redis->rPush('index:meassage:game:waitcmppdeliver', json_encode([
                            //     'Stat'        => $send_log['status_message'],
                            //     'send_msgid'  => [$sendTask['send_msg_id']],
                            //     'Done_time'   => date('ymdHis',time() + $max),
                            //     'content'     =>  $sendTask['task_content'],
                            //     'Submit_time' => date('ymdHis',time()),
                            //     'mobile'      => $send_log['mobile'],
                            //     'uid'         =>  $sendTask['uid'],
                            //     'mar_task_id' => $sendTask['id'],
                            // ]));
                            // die;
                        } else { //不扣量
                            $push_messages[] = $sendmessage; //实际发送队列
                        }
                    }

                    $txt = json_encode($send_log) . "\n";
                    fwrite($myfile, $txt);
                } else {
                    $send_log = [
                        'task_no'        => $sendTask['task_no'],
                        'uid'            => $sendTask['uid'],
                        'title'          => $sendTask['task_name'],
                        'content'        => $sendTask['task_content'],
                        'mobile'         => $mobilesend[$i],
                        'send_status'    => 4,
                        'create_time'    => time(),
                        'status_message' => 'DB:0101', //无效号码
                        'real_message'   => 'DB:0101',
                    ];
                    $txt = json_encode($send_log) . "\n";
                    fwrite($myfile, $txt);
                    $this->redis->rPush('index:meassage:game:waitcmppdeliver:' . $sendTask['uid'], json_encode([
                        'Stat'        => $send_log['status_message'],
                        'send_msgid'  => [$sendTask['send_msg_id']],
                        'Done_time'   => date('ymdHis', time()),
                        'content'     =>  $sendTask['task_content'],
                        'Submit_time' => date('ymdHis', time()),
                        'mobile'      => $send_log['mobile'],
                        'uid'         =>  $sendTask['uid'],
                        'mar_task_id' => $sendTask['id'],
                    ]));
                }
            }

            Db::startTrans();
            try {
                Db::table('yx_user_send_game_task')->where('id', $sendTask['id'])->update(['real_num' => $real_num, 'send_status' => 3, 'log_path' => realpath("") . '/tasklog/marketing/' . $sendTask['task_no'] . ".txt"]);
                Db::commit();
                foreach ($push_messages as $key => $value) {
                    $send_channelid = $value['channel_id'];
                    unset($value['channel_id']);
                    $res = $this->redis->rpush('index:meassage:game:send' . ":" . $send_channelid, json_encode($value)); //三体营销通道
                }
            } catch (\Exception $e) {
                $this->redis->rPush('index:meassage:game:sendtask', $send);
                exception($e);
                Db::rollback();
            }
            // foreach ($mobilesend as $key => $kvalue) {
            //     if (in_array($channel_id, [2, 6, 7, 8])) {
            //         // $getSendTaskSql = "select source,province_id,province from yx_number_source where `mobile` = '".$prefix."' LIMIT 1";
            //     }
            // }
            // exit("SUCCESS");
            fclose($myfile);
        }
    }

    //http通道日志
    public function getChannelSendLog($content)
    {
        $redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        // $redisMessageCodeSend = 'index:meassage:code:new:deliver:'.$content; //验证码发送任务rediskey
        $redisMessageCodeSend = 'index:meassage:code:deliver:' . $content; //验证码发送任务rediskey
        // $redis->rpush($redisMessageCodeSend,json_encode([
        //     'task_no' => 'mar19121715380521728861',
        //     'uid' => '39',
        //     'mobile' => '15897679999',
        //     'status_message' => 'DELIVRD',
        //     'send_status' => '4',
        //     'send_time' => '1576574460',
        // ]));
        $task_status = [];
        $task_mobile = [];
        $i           = 0;
        $callback    = [];
        // print_r($send_log);die;
        try {
            while (true) {
                $send       = $redis->lpop($redisMessageCodeSend);
                $callback[] = $send;
                if (!empty($send)) {
                    // exit("send_log is null");

                    $send_log = json_decode($send, true);
                    // print_r($send_log);
                    $task_status[$send_log['task_no']][$send_log['mobile']] = $send_log;
                    $task_mobile[$send_log['task_no']][]                    = $send_log['mobile'];
                    $i++;
                    if ($i >= 50000) {
                        foreach ($task_status as $key => $value) {
                            // print_r($task_mobile[$key]);die;
                            $task = Db::query("SELECT `log_path`,`update_time` from yx_user_send_task where delete_time=0 and task_no ='" . $key . "'");
                            // print_r("SELECT `log_path` from yx_user_send_task where delete_time=0 and id =".$id);die;
                            if (empty($task)) {
                                // continue;
                            }
                            $log_path = '';
                            $log_path = $task[0]['log_path'];
                            $file     = fopen($log_path, "r");
                            $data     = array();
                            $i        = 0;
                            // $phone = '';
                            // $j     = '';
                            while (!feof($file)) {
                                $cellVal = trim(fgets($file));
                                $log     = json_decode($cellVal, true);
                                // $phone .= $j . trim(fgets($file));//fgets()函数从文件指针中读取一行
                                // // print_r($phone);die;
                                // $j = ',';

                                // print_r($data);die;
                                if (isset($log['mobile'])) {
                                    if (in_array($log['mobile'], $task_mobile[$key])) {
                                        $log['status_message'] = $value[$log['mobile']]['status_message'];
                                        $log['send_status']    = $value[$log['mobile']]['send_status'];
                                        $log['send_time']      = $value[$log['mobile']]['send_time'];

                                        //  print_r($log);die;
                                    }
                                    $log['create_time'] = $task[0]['update_time'];
                                }
                                $data[] = $log;
                            }
                            fclose($file);
                            $myfile = fopen($log_path, "w");
                            for ($i = 0; $i < count($data); $i++) {
                                $txt = json_encode($data[$i]) . "\n";
                                fwrite($myfile, $txt);
                            }
                            fclose($myfile);
                        }
                        $i = 0;

                        unset($task_status);
                        unset($task_mobile);
                    }
                } else {
                    if (empty($task_status)) {
                        unset($callback);
                        exit("send_log is null");
                    }
                    //    print_r($task_status);die;
                    foreach ($task_status as $key => $value) { //key为任务编号
                        // print_r($task_mobile[$key]);die;
                        $task = Db::query("SELECT `log_path`,`update_time` from yx_user_send_task where delete_time=0 and task_no ='" . $key . "'");
                        // print_r("SELECT `log_path` from yx_user_send_task where delete_time=0 and id =".$id);die;
                        if (empty($task)) {
                            // continue;
                        }
                        $log_path = '';
                        $log_path = $task[0]['log_path'];
                        $file     = fopen($log_path, "r");
                        $data     = array();
                        $i        = 0;
                        // $phone = '';
                        // $j     = '';
                        while (!feof($file)) {
                            $cellVal = trim(fgets($file));
                            $log     = json_decode($cellVal, true);
                            // $phone .= $j . trim(fgets($file));//fgets()函数从文件指针中读取一行
                            // // print_r($phone);die;
                            // $j = ',';

                            if (isset($log['mobile'])) {
                                if (in_array($log['mobile'], $task_mobile[$key])) {
                                    $log['status_message'] = $value[$log['mobile']]['status_message'];
                                    $log['send_status']    = $value[$log['mobile']]['send_status'];
                                    $log['send_time']      = $value[$log['mobile']]['send_time'];
                                    $log['create_time']    = $task[0]['update_time'];
                                    //  print_r($log);die;
                                }
                            }
                            // print_r($data);die;
                            $data[] = $log;
                        }

                        // print_r($data);die;
                        fclose($file);
                        $myfile = fopen($log_path, "w");
                        for ($i = 0; $i < count($data); $i++) {
                            $txt = json_encode($data[$i]) . "\n";
                            fwrite($myfile, $txt);
                        }
                        fclose($myfile);

                        // print_r($data);die;
                    }
                    $i = 0;

                    unset($task_status);
                    unset($task_mobile);
                }
                unset($send);
            }
        } catch (\Exception $e) {
            foreach ($callback as $key => $value) {
                $send = $redis->rPush($redisMessageCodeSend, $value);
            }
        }
    }

    public function unknowLog($channel_id)
    {
        $redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        $redisMessageCodeSend = 'index:meassage:code:unknow:deliver:' . $channel_id; //验证码发送任务rediskey
        $channel              = $this->getChannelinfo($channel_id);
        // $mesage['Stat']        = $Msg_Content['Stat'];
        // $mesage['Submit_time'] = $Msg_Content['Submit_time'];
        // $mesage['Done_time']   = $Msg_Content['Done_time'];
        // // $mesage['mobile']      = $body['Dest_Id '];//手机号
        //     $mesage['mobile']   = trim($Msg_Content['Dest_terminal_Id']);
        //     $mesage['receive_time'] = time();//回执时间戳
        // $redis->rpush($redisMessageCodeSend, json_encode([
        //     'Stat' => 'DELIVRD',
        //     'mobile' => '13761273981',
        //     'Submit_time' => '1912301028',
        //     'Done_time' => '1912301058',
        //     'receive_time' => '1577672536',
        // ]));
        if ($channel['channel_type'] == 2) {
            while (true) {
                Db::startTrans();
                try {
                    $send_log = $redis->lpop($redisMessageCodeSend);
                    if (empty($send_log)) {
                        continue;
                    }
                    $send_log = json_decode($send_log, true);
                    $time = strtotime(date("Y-m-d 0:00:00", time()));
                    $sql = "SELECT `id`,`task_no`,`uid` FROM ";
                    if ($channel['business_id'] == 5) { //营销
                        $sql .= " yx_user_send_task ";
                    } elseif ($channel['business_id'] == 6) { // 行业
                        $sql .= " yx_user_send_code_task ";
                    } elseif ($channel['business_id'] == 9) { //游戏
                        $sql .= " yx_user_send_game_task ";
                    }
                    $sql .= "WHERE `mobile_content` = " . $send_log['mobile'] . " AND `real_message` = '' AND `status_message` = '' " . " AND `create_time` > " . $time;
                    // print_r($sql);die;
                    $task = Db::query($sql);
                    if (empty($task)) {
                        $redis->rpush($redisMessageCodeSend, json_encode($send_log));
                        continue;
                    }
                    if ($task[0]['uid'] == 47 || $task[0]['uid'] == 49 || $task[0]['uid'] == 51 || $task[0]['uid'] == 52 || $task[0]['uid'] == 53 || $task[0]['uid'] == 54 || $task[0]['uid'] == 55) { //推送给美丽田园
                        // https://zhidao.baidu.com/question/412076997.html
                        $request_url = "http://116.228.60.189:15901/rtreceive?";
                        $request_url .= 'task_no=' . $task[0]['task_no'] . "&status_message=" . $send_log['Stat'] . "&mobile=" . $send_log['mobile'] . "&send_time=" . $send_log['Submit_time'];
                        sendRequest($request_url);
                        print_r($request_url);
                    }
                    if ($channel['business_id'] == 5) { //营销
                        Db::table('yx_user_send_task')->where('id', $task[0]['id'])->update(['real_message' => $send_log['Stat'], 'status_message' => $send_log['Stat']]);
                    } elseif ($channel['business_id'] == 6) { // 行业
                        Db::table('yx_user_send_code_task')->where('id', $task[0]['id'])->update(['real_message' => $send_log['Stat'], 'status_message' => $send_log['Stat']]);
                    } elseif ($channel['business_id'] == 9) { //游戏
                        Db::table('yx_user_send_game_task')->where('id', $task[0]['id'])->update(['real_message' => $send_log['Stat'], 'status_message' => $send_log['Stat']]);
                    }

                    Db::commit();
                } catch (\Exception $e) {
                    $redis->rpush($redisMessageCodeSend, $send_log);
                    exception($e);
                    Db::rollback();
                }
            }
        }
    }

    public function updateLog($channel_id)
    {
        $redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        $redisMessageCodeSend = 'index:meassage:code:new:deliver:' . $channel_id; //验证码发送任务rediskey
        $channel              = $this->getChannelinfo($channel_id);
        /*                 $redis->rpush($redisMessageCodeSend, json_encode([
            'mobile' => '13564869264',
            'title' => '美丽田园营销短信',
            'mar_task_id' => '1599',
            'content' => '【美丽田园】电商圣诞节活动将至，感恩回馈！.【美丽田园】',
            'Msg_Id' => '',
            'Stat' => 'DELIVER',
            'Submit_time' => '191224164036',
            'Done_time' => '191224164236',
        ])); */

        // $request_url = 'http://116.228.60.189:15901/rtreceive?task_no=bus19123111560308152071&status_message=E:CHAN&mobile=18643198590&send_time=1912311333';
        // sendRequest($request_url);
        if ($channel['channel_type'] == 2) { //cmpp的
            while (true) {
                $send_log = $redis->lpop($redisMessageCodeSend);
                if (empty($send_log)) {
                    continue;
                }
                // $redis->rpush($redisMessageCodeSend, $send_log);
                // print_r($send_log);die;
                $send_log = json_decode($send_log, true);

                //获取通道属性
                if (!isset($send_log['mar_task_id']) || empty($send_log['mar_task_id'])) {
                    continue;
                }
                $sql = "SELECT `task_no`,`uid` FROM ";
                if ($channel['business_id'] == 5) { //营销
                    $sql .= " yx_user_send_task ";
                } elseif ($channel['business_id'] == 6) { // 行业
                    $sql .= " yx_user_send_code_task ";
                } elseif ($channel['business_id'] == 9) { //游戏
                    $sql .= " yx_user_send_game_task ";
                }
                $sql .= "WHERE `id` = " . $send_log['mar_task_id'];
                $task = Db::query($sql);
                // print_r($sql);die;
                if (empty($task)) {
                    $redis->rpush($redisMessageCodeSend, json_encode($send_log));
                    // continue;
                }
                // $redis->rpush($redisMessageCodeSend, json_encode($send_log));
                // $request_url = "http://116.228.60.189:15902/rtreceive?";
                // $request_url .= 'task_no=' . $task[0]['task_no'] . "&status_message=" . $send_log['Stat'] . "&mobile=" . $send_log['mobile'] . "&send_time=" . $send_log['Submit_time'];
                if ($task[0]['uid'] == 47 || $task[0]['uid'] == 49 || $task[0]['uid'] == 51 || $task[0]['uid'] == 52 || $task[0]['uid'] == 53 || $task[0]['uid'] == 54 || $task[0]['uid'] == 55) { //推送给美丽田园
                    // https://zhidao.baidu.com/question/412076997.html
                    if (strpos($send_log['content'], '问卷') !== false) {
                        $request_url = "http://116.228.60.189:15901/rtreceive?";
                        $request_url .= 'task_no=' . trim($task[0]['task_no']) . "&status_message=" . "DELIVRD" . "&mobile=" . trim($send_log['mobile']) . "&send_time=" . trim($send_log['Submit_time']);
                    } else {
                        $request_url = "http://116.228.60.189:15901/rtreceive?";
                        $request_url .= 'task_no=' . trim($task[0]['task_no']) . "&status_message=" . trim($send_log['Stat']) . "&mobile=" . trim($send_log['mobile']) . "&send_time=" . trim($send_log['Submit_time']);
                    }


                    print_r($request_url);
                    sendRequest($request_url);

                    usleep(20000);
                } else {
                    $redis->rpush('index:meassage:code:user:receive:' . $task[0]['uid'], json_encode([
                        'task_no' =>  trim($task[0]['task_no']),
                        'status_message' =>   trim($send_log['Stat']),
                        'mobile' =>   trim($send_log['mobile']),
                        // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                        'send_time' => isset($send_log['receive_time']) ? date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                    ])); //写入用户带处理日志
                }
                $redis->rpush('index:meassage:code:cms:deliver:' . $channel_id, json_encode($send_log)); //写入通道处理日志                
            }
        }
        // try {
        //     //code...
        // } catch (\Exception $e) {
        //     //throw $th;
        // }
        // for ($i = 1; $i < 20; $i++) {

        // }

    }

    //处理通道消息队列中的回执日志
    public function updateCmppChannelLog($channel_id)
    {

        $redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        $redisMessageCodeSend = 'index:meassage:code:cms:deliver:' . $channel_id; //验证码发送任务rediskey
        $channel              = $this->getChannelinfo($channel_id);
        $task_status = [];
        $task_mobile = [];
        $i           = 0;
        $callback    = [];
        // $redis->rpush($redisMessageCodeSend, json_encode([
        //     'mobile' => '13907989407',
        //     'title' => '达芙妮',
        //     'mar_task_id' => '15850',
        //     'content' => '【DAPHNE】亲爱的会员：您的30元优惠券已到账，请前往DaphneFashion公众号-会员尊享-会员中心领取！退订回T',
        //     'Msg_Id' => '',
        //     'Stat' => 'DELIVER',
        //     'Submit_time' => '1578239314',
        //     'Done_time' => '1578239314',
        //     'Done_time' => '1578239314',
        // ]));
        if ($channel['channel_type'] == 2) {
            try {
                while (true) {
                    $send_log       = $redis->lpop($redisMessageCodeSend);
                    $send_log = json_decode($send_log, true);

                    if (!empty($send_log)) {
                        $callback[] = $send_log;
                        // exit("send_log is null");


                        if (!isset($send_log['mar_task_id']) || empty($send_log['mar_task_id'])) {
                            continue;
                        }
                        // print_r($send_log);die;
                        $task_status[$send_log['mar_task_id']][$send_log['mobile']] = $send_log;
                        $task_mobile[$send_log['mar_task_id']][]                    = $send_log['mobile'];
                        $i++;
                        if ($i >= 50000) {
                            foreach ($task_status as $key => $value) {
                                // print_r($task_mobile[$key]);die;
                                $sql = "SELECT `task_no`,`uid`,`log_path` FROM ";
                                if ($channel['business_id'] == 5) { //营销
                                    $sql .= " yx_user_send_task ";
                                } elseif ($channel['business_id'] == 6) { // 行业
                                    $sql .= " yx_user_send_code_task ";
                                } elseif ($channel['business_id'] == 9) { //游戏
                                    $sql .= " yx_user_send_game_task ";
                                }
                                $sql .= "WHERE `id` = " . $key;
                                $task = Db::query($sql);
                                // print_r("SELECT `log_path` from yx_user_send_task where delete_time=0 and id =".$id);die;
                                // print_r($sql);die;
                                if (empty($task)) {
                                    // $redis->rpush($redisMessageCodeSend, json_encode($send_log));
                                    continue;
                                }
                                $log_path = '';
                                $log_path = $task[0]['log_path'];
                                $file     = fopen($log_path, "r");
                                $data     = array();
                                $i        = 0;
                                // $phone = '';
                                // $j     = '';
                                while (!feof($file)) {
                                    $cellVal = trim(fgets($file));
                                    $log     = json_decode($cellVal, true);
                                    // $phone .= $j . trim(fgets($file));//fgets()函数从文件指针中读取一行
                                    // // print_r($phone);die;
                                    // $j = ',';

                                    // print_r($data);die;
                                    if (isset($log['mobile'])) {
                                        if (in_array($log['mobile'], $task_mobile[$key])) {
                                            $log['status_message'] = $value[$log['mobile']]['Stat'];
                                            if ($value[$log['mobile']]['Stat'] != 'DELIVRD') {
                                                $log['send_status']    = 3;
                                            } else {
                                                $log['send_status']    = 4;
                                            }

                                            $log['send_time']      = date('Y-m-d H:i:s', trim($value[$log['mobile']]['Submit_time']));


                                            //  print_r($log);die;
                                        }
                                        if (is_numeric($log['create_time'])) {
                                            $log['create_time']    = date('Y-m-d H:i:s', $log['create_time']);
                                        }
                                    }
                                    $data[] = $log;
                                }
                                fclose($file);
                                $myfile = fopen($log_path, "w");
                                for ($i = 0; $i < count($data); $i++) {
                                    $txt = json_encode($data[$i]) . "\n";
                                    fwrite($myfile, $txt);
                                }
                                fclose($myfile);
                            }
                            $i = 0;

                            unset($task_status);
                            unset($task_mobile);
                        }
                    } else {
                        if (empty($task_status)) {
                            unset($callback);
                            exit("send_log is null");
                        }
                        //    print_r($task_status);die;
                        foreach ($task_status as $key => $value) { //key为任务编号
                            // print_r($task_mobile[$key]);die;
                            $task = Db::query("SELECT `log_path`,`update_time` from yx_user_send_task where delete_time=0 and id ='" . $key . "'");
                            // print_r("SELECT `log_path` from yx_user_send_task where delete_time=0 and id =".$id);die;
                            if (empty($task)) {
                                // continue;
                            }
                            $log_path = '';
                            $log_path = $task[0]['log_path'];
                            $file     = fopen($log_path, "r");
                            $data     = array();
                            $i        = 0;
                            // $phone = '';
                            // $j     = '';
                            while (!feof($file)) {
                                $cellVal = trim(fgets($file));
                                $log     = json_decode($cellVal, true);
                                // $phone .= $j . trim(fgets($file));//fgets()函数从文件指针中读取一行
                                // // print_r($phone);die;
                                // $j = ',';

                                if (isset($log['mobile'])) {
                                    if (in_array($log['mobile'], $task_mobile[$key])) {
                                        $log['status_message'] = $value[$log['mobile']]['Stat'];
                                        if ($value[$log['mobile']]['Stat'] != 'DELIVRD') {
                                            $log['send_status']    = 3;
                                        } else {
                                            $log['send_status']    = 4;
                                        }

                                        //  print_r($log);die;
                                        $log['send_time']      = date('Y-m-d H:i:s', trim($value[$log['mobile']]['Submit_time']));
                                        if (is_numeric($log['create_time'])) {
                                            $log['create_time']    = date('Y-m-d H:i:s', $log['create_time']);
                                        }
                                        // $log['create_time']    = date('Y-m-d H:i:s',$log['create_time']);
                                    }
                                }
                                // print_r($data);die;
                                $data[] = $log;
                            }

                            // print_r($data);die;
                            fclose($file);
                            $myfile = fopen($log_path, "w");
                            for ($i = 0; $i < count($data); $i++) {
                                $txt = json_encode($data[$i]) . "\n";
                                fwrite($myfile, $txt);
                            }
                            fclose($myfile);

                            // print_r($data);die;
                        }
                        $i = 0;

                        unset($task_status);
                        unset($task_mobile);
                    }
                    unset($send);
                }
            } catch (\Exception $e) {
                foreach ($callback as $key => $value) {
                    $redis->rPush($redisMessageCodeSend, json_encode($value));
                }

                exception($e);
            }
        }
    }


    public function supplyMessageAgain()
    { //行业重推
        $redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G

        // $redis->rpush('index:meassage:code:cms:deliver:', json_encode(Array
        // (
        //     'mobile' => '13564869264',
        //     'title' => '美丽田园营销短信',
        //     'mar_task_id' => '1599',
        //     'content' => '感谢您对于美丽田园的信赖和支持，为了给您带来更好的服务体验，特邀您针对本次服务进行评价http://crmapp.beautyfarm.com.cn/questionNaire1/api/qnnaire/refct?id=534478，请您在24小时内提交此问卷，谢谢配合。期待您的反馈！如需帮助，敬请致电400-8206-142，回T退订【美丽田园】',
        //     'Msg_Id' => '',
        //     'Stat' => 'DELIVER',
        //     'Submit_time' => '191224164036',
        //     'Done_time' => '191224164236',
        // )));
        while (true) {
            $send_log = $redis->lpop('index:meassage:code:cms:deliver:');
            if (empty($send_log)) {
                exit('Success');
            }
            // $redis->rpush('index:meassage:code:cms:deliver:', $send_log);
            $send_log = json_decode($send_log, true);
            if (trim($send_log['mar_task_id']) > 15763) { //营销
                //推回营销
                // $sql .= "WHERE `id` = " . $send_log['mar_task_id'];
                // $sql .= " yx_user_send_task ";
                // $task = Db::query($sql);
                $redis->rpush('index:meassage:Marketing:cms:deliver:', $send_log);
            } else { //行业

                $sql = "SELECT `task_no`,`uid`,`task_name` FROM ";
                $redis->rpush('index:meassage:Buiness:cms:deliver:', json_encode($send_log));
                $sql .= " yx_user_send_code_task ";
                $sql .= "WHERE `id` = " . $send_log['mar_task_id'];
                $task = Db::query($sql);
                if (strpos($send_log['content'], '问卷') !== false) {
                    Db::startTrans();
                    try {
                        Db::table('yx_user_send_code_task')->where('id', $send_log['mar_task_id'])->update(['status_message' => 'DELIVRD', 'real_message' => $send_log['Stat']]);
                        Db::commit();
                    } catch (\Exception $e) {
                        exception($e);
                        Db::rollback();
                    }
                    $send_log['Stat'] = 'DELIVRD';
                } else {
                    if (trim($send_log['Stat']) == 'E:CHAN') { //补发
                        $sendmessage = [
                            'mobile'      => $send_log['mobile'],
                            'title'       => $task[0]['task_name'],
                            'mar_task_id' => $send_log['mar_task_id'],
                            'content'     => $send_log['content'],
                        ];

                        $redis->rpush('index:meassage:game:send:1', json_encode($sendmessage)); //三体营销通道
                    }
                    Db::startTrans();
                    try {
                        Db::table('yx_user_send_code_task')->where('id', $task[0]['id'])->update(['status_message' => $send_log['Stat'], 'real_message' => $send_log['Stat']]);
                        Db::commit();
                    } catch (\Exception $e) {

                        Db::rollback();
                    }
                }
                if ($task[0]['uid'] == 47 || $task[0]['uid'] == 49 || $task[0]['uid'] == 51 || $task[0]['uid'] == 52 || $task[0]['uid'] == 53 || $task[0]['uid'] == 54 || $task[0]['uid'] == 55) { //推送给美丽田园
                    // https://zhidao.baidu.com/question/412076997.html
                    if (strpos($send_log['content'], '问卷') !== false) {
                        $request_url = "http://116.228.60.189:15901/rtreceive?";
                        $request_url .= 'task_no=' . trim($task[0]['task_no']) . "&status_message=" . "DELIVRD" . "&mobile=" . trim($send_log['mobile']) . "&send_time=" . trim($send_log['Submit_time']);
                    } else {
                        $request_url = "http://116.228.60.189:15901/rtreceive?";
                        $request_url .= 'task_no=' . trim($task[0]['task_no']) . "&status_message=" . trim($send_log['Stat']) . "&mobile=" . trim($send_log['mobile']) . "&send_time=" . trim($send_log['Submit_time']);
                    }


                    print_r($request_url);
                    // sendRequest($request_url);

                    usleep(20000);
                }
            }
        }
    }


    private function getChannelinfo($channel_id)
    {
        $channel = Db::query("SELECT * FROM yx_sms_sending_channel WHERE `id` = " . $channel_id);
        if (empty($channel)) {
            return false;
        } else {

            return $channel[0];
        }
    }

    //游戏日志通道
    public function getCmppChannelSendLog($content)
    {
        $redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        // $redisMessageCodeSend = 'index:meassage:code:new:deliver:'.$content; //验证码发送任务rediskey
        $redisMessageCodeSend = 'index:meassage:game:new:deliver:' . $content; //验证码发送任务rediskey
        /*         $redis->rpush($redisMessageCodeSend,json_encode([
            'mar_task_id' => '1',
            'uid' => '45',
            'Msg_Id' => '1577095013046269',
            'content' => '【超变大陆】已为您发出688888钻石和VIP15，今日限领至尊屠龙！戳 https://ltv7.cn/5CWSJ 回T退订',
            'mobile' => '13812895012',
            'Stat' => 'ID:0076',
            'Done_time' => '1912231821',
            'receive_time' => time()+2,
            'my_submit_time' => time(),
        ])); */
        // $redis->rpush($redisMessageCodeSend,json_encode([
        //     'mar_task_id' => '2',
        //     'uid' => '45',
        //     'Msg_Id' => '1577096780057526',
        //     'content' => '【超变大陆】已为您发出6888888钻石和VIP15，今日限领至尊屠龙！戳 https://ltv7.cn/5CWSJ 回T退订',
        //     'mobile' => '13812895012',
        //     'Stat' => 'LIMIT',
        //     'Done_time' => '1912231828',
        //     'Done_time' => '1912231828',
        // ]));
        // $redis->rpush($redisMessageCodeSend,json_encode([
        //     'mar_task_id' => '1',
        //     'uid' => '45',
        //     'Msg_Id' => '12648757921059827739',
        //     'content' => '【冰封传奇】已为您发出688888元宝和VIP满级号，今日限领至尊屠龙！戳 https://ltv7.cn/45RHD 回T退订',
        //     'mobile' => '18339998120',
        //     'Stat' => 'MK:1008',
        //     'Done_time' => '1912121543',
        //     'Done_time' => '1912121543',
        // ]));
        // $redis->rpush($redisMessageCodeSend,json_encode([
        //     'mar_task_id' => '4',
        //     'uid' => '45',
        //     'Msg_Id' => '12648757921059827739',
        //     'content' => '【冰封传奇】已为您发出688888元宝和VIP满级号，今日限领至尊屠龙！戳 https://ltv7.cn/45RHD 回T退订',
        //     'mobile' => '18339998120',
        //     'Stat' => 'MK:1008',
        //     'Done_time' => '1912121543',
        //     'Done_time' => '1912121543',
        // ]));
        /*         $redis->hset('index:meassage:game:msg:id:14',1,json_encode([
            'mar_task_id' => '1',
            'uid' => '45',
            'Msg_Id' => '1577095013046269',
            'content' => '【超变大陆】已为您发出688888钻石和VIP15，今日限领至尊屠龙！戳 https://ltv7.cn/5CWSJ 回T退订',
            'mobile' => '13812895012',
            'Stat' => 'ID:0076',
            'Done_time' => '1912231821',
            'my_submit_time' => time(),
        ])); */
        // $untime = 0;
        $i = 0;
        /*    $redis->rpush('index:meassage:game:unknow:deliver:14',json_encode([
            'mobile' => '13737139325',
            'Stat' => 'ID:0076',
            'Submit_time' => '1912231821',
            'Done_time' => '1912231821',
        ])); */
        while (true) {
            $send_log = $redis->lpop($redisMessageCodeSend);
            $time_no = time();
            //状态更新
            $unknow_status = $redis->lpop('index:meassage:game:unknow:deliver:' . $content);
            // print_r($unknow_status);
            // $redis->rpush('index:meassage:game:unknow:deliver:14',$unknow_status); die;
            if (!empty($unknow_status)) {
                $unknow_data = json_decode($unknow_status, true);
                if (!isset($unknow_data['mobile'])) {
                    continue;
                }
                if (!empty($unknow_data)) {
                    $gametask = Db::query("SELECT * FROM yx_user_send_game_task WHERE `mobile_content` = '" . $unknow_data['mobile'] . "' AND `status_message` ='' LIMIT 1 ");
                    if (!empty($gametask)) {
                        $send_msgid = explode(',', $gametask[0]['send_msg_id']);
                        foreach ($send_msgid as $key => $msgid) {
                            $redis->rPush('index:meassage:game:cmppdeliver:' . $gametask[0]['uid'], json_encode([
                                'Stat'        => $unknow_data['Stat'],
                                'send_msgid'  => [$msgid],
                                'Done_time'   => $unknow_data['Done_time'],
                                'Submit_time' => $unknow_data['Submit_time'],
                                'mobile'      => $unknow_data['mobile'],
                            ]));
                            // if ($value == $send_log['Msg_Id']){

                            // }
                        }
                        Db::startTrans();
                        try {
                            Db::table('yx_user_send_game_task')->where('id', $gametask[0]['id'])->update(['status_message' => $unknow_data['Stat'], 'real_message' => $unknow_data['Stat']]);
                            Db::commit();
                        } catch (\Exception $e) {

                            Db::rollback();
                        }
                        $i++;

                        // print_r($gametask);
                    }
                }
            }

            if (!empty($send_log)) {
                // exit("send_log is null");
                // $redis->rpush($redisMessageCodeSend, json_encode($send_log));

                //未知
                // if (!isset($untime)){
                //     continue;
                // }
                $redis->rpush('index:meassage:game:cms:deliver:', json_encode($send_log)); //游戏通道实际码
                $send_log = json_decode($send_log, true);
                if (!isset($untime)) {
                    if (isset($send_log['receive_time']) && isset($send_log['my_submit_time'])) {
                        $untime = $send_log['receive_time'] - $send_log['my_submit_time'];
                    }
                } else {
                    if (isset($send_log['receive_time']) && isset($send_log['my_submit_time'])) {
                        $untime = $send_log['receive_time'] - $send_log['my_submit_time'] > $untime ? $send_log['receive_time'] - $send_log['my_submit_time'] : $untime;
                    }
                }
                $task     = Db::query("SELECT * FROM yx_user_send_game_task WHERE `id` = '" . $send_log['mar_task_id'] . "'");
                if (empty($task)) {
                    continue;
                }
                if ($send_log['Stat'] != 'DELIVRD') {
                    $send_status = 4;
                } else {
                    $send_status = 3;
                }
                if (empty($task[0]['status_message'])) {
                    $send_msgid = explode(',', $task[0]['send_msg_id']);
                    foreach ($send_msgid as $key => $value) {
                        $redis->rPush('index:meassage:game:cmppdeliver:' . $task[0]['uid'], json_encode([
                            'Stat'        => $send_log['Stat'],
                            'send_msgid'  => [$value],
                            'Done_time'   => $send_log['Done_time'],
                            'Submit_time' => $task[0]['create_time'],
                            'mobile'      => $send_log['mobile'],
                        ]));
                        // if ($value == $send_log['Msg_Id']){

                        // }
                    }
                }

                Db::startTrans();
                try {
                    Db::table('yx_user_send_game_task')->where('id', $send_log['mar_task_id'])->update(['real_message' => $send_log['Stat'], 'status_message' => $send_log['Stat']]);
                    Db::commit();
                } catch (\Exception $e) {

                    Db::rollback();
                }
            }
            $sendunknow = $redis->hgetall('index:meassage:game:msg:id:' . $content);
            if (!empty($sendunknow)) {
                // sleep($untime);
                foreach ($sendunknow as $send => $value) {
                    $value = json_decode($value, true);
                    if (!isset($value['receive_time'])) {
                        if (time() - $value['my_submit_time'] >= 1800) {
                            $value_task     = Db::query("SELECT * FROM yx_user_send_game_task WHERE `id` = '" . $value['mar_task_id'] . "'");
                            if (empty($value_task)) {
                                continue;
                            }
                            $max = mt_rand(9, 11);
                            $num     = mt_rand(0, 100);
                            if ($num <= $max) {
                                $Stat = 'UNKNOWN';
                            } else {
                                $Stat = 'DELIVRD';
                            }
                            $send_msgid = explode(',', $value_task[0]['send_msg_id']);
                            foreach ($send_msgid as $key => $msgid) {
                                $redis->rPush('index:meassage:game:cmppdeliver:' . $value_task[0]['uid'], json_encode([
                                    'Stat'        => $Stat,
                                    'send_msgid'  => [$msgid],
                                    'Done_time'   => date('ymdHis', $value['my_submit_time'] + 10),
                                    'Submit_time' => date('ymdHis', $value['my_submit_time']),
                                    'mobile'      => $value['mobile'],
                                ]));
                                // if ($value == $send_log['Msg_Id']){

                                // }
                            }
                            Db::startTrans();
                            try {
                                Db::table('yx_user_send_game_task')->where('id', $value['mar_task_id'])->update(['status_message' => $Stat, 'real_message' => 'UNKNOWN']);
                                Db::commit();
                            } catch (\Exception $e) {

                                Db::rollback();
                            }
                            $redis->hdel('index:meassage:game:msg:id:14', $send);
                            break;
                        }
                    }
                }
            }




            //扣量
            $witenosend = $redis->lpop("index:meassage:game:waitcmppdeliver");
            if (!empty($witenosend)) {

                // continue;
                // sleep($untime);
                $witenosend_log = json_decode($witenosend, true);
                $witenosend_task     = Db::query("SELECT * FROM yx_user_send_game_task WHERE `id` = '" . $witenosend_log['mar_task_id'] . "'");
                if (empty($witenosend_task)) {
                    continue;
                }
                $send_msgid = explode(',', $witenosend_task[0]['send_msg_id']);
                foreach ($send_msgid as $key => $value) {
                    $redis->rPush('index:meassage:game:cmppdeliver:' . $witenosend_task[0]['uid'], json_encode([
                        'Stat'        => $witenosend_log['Stat'],
                        'send_msgid'  => [$value],
                        'Done_time'   => $send_log['Done_time'],
                        'Submit_time' => date('ymdHis', time()),
                        'mobile'      => $witenosend_log['mobile'],
                    ]));
                    // if ($value == $send_log['Msg_Id']){

                    // }
                }
                Db::startTrans();
                try {
                    Db::table('yx_user_send_game_task')->where('id', $witenosend_log['mar_task_id'])->update(['status_message' => $witenosend_log['Stat']]);
                    Db::commit();
                } catch (\Exception $e) {

                    Db::rollback();
                }
            }

            // print_r("SELECT `id`,`uid`,`msgid`,`create_time` FROM yx_user_send_code_task_log WHERE `mobile` = " . $send_log['mobile'] . " AND `task_no` = '" . $task[0]['task_no'] . "'");die;

            //状态回执慢

            /*      $day_time = strtotime(date('Y-m-d 0:00:00', time()));
            // $day_time = strtotime(date('2019-12-27 0:00:00',time()));
            $low = Db::query("SELECT * FROM yx_user_send_game_task WHERE `status_message` = '' AND `create_time` > " . $day_time . " ORDER BY ID ASC LIMIT 1");
            if (!empty($low)) {
                $rece_time = mt_rand(15, 18);
                if (time() - $low[0]['create_time'] > $rece_time) {
                    $send_msgid = explode(',', $low[0]['send_msg_id']);

                    $utime = mt_rand(8, 12);
                    $bounts = [
                        0 => 4,
                        1 => 5,
                        2 => $utime,
                        3 => 1000,
                    ];
                    $tatus_array = [
                        0 => 'FIBLACK',
                        1 => 'MK:0000',
                        2 => 'ID:0076',
                        3 => 'DELIVRD'
                    ];
                    $num = mt_rand(0, max($bounts));
                    foreach ($bounts as $b => $s) {
                        if ($num <= $s) {
                            $thisstatus = $tatus_array[$b];
                            break;
                        }
                    }
                    foreach ($send_msgid as $key => $value) {
                        $redis->rPush('index:meassage:game:cmppdeliver:' . $low[0]['uid'], json_encode([
                            'Stat'        => $thisstatus,
                            'send_msgid'  => [$value],
                            'Done_time'   => date('ymdHis', $low[0]['create_time'] + $utime),
                            'Submit_time' => date('ymdHis', $low[0]['create_time']),
                            'mobile'      => $low[0]['mobile_content'],
                        ]));
                        // if ($value == $send_log['Msg_Id']){

                        // }
                    }
                    Db::startTrans();
                    try {
                        Db::table('yx_user_send_game_task')->where('id', $low[0]['id'])->update(['status_message' => $thisstatus]);
                        Db::commit();
                    } catch (\Exception $e) {
                        exception($e);
                        Db::rollback();
                    }
                }
            } */
        }
    }

    public function getNumberSource($prefix)
    {
        $getSendTaskSql = "select source,province_id,province from yx_number_source where delete_time=0 and `mobile` = '" . $prefix . "'";

        // echo "\n";
        $NumberSource = Db::query($getSendTaskSql);
        if (empty($NumberSource)) {
            return false;
        } else {

            return $NumberSource[0];
        }
        // print_r($sendTask);
    }

    public function getSendLog()
    {
        $redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        // echo time()-1574480118;die;
        // date_default_timezone_set('PRC');
        // $send_task = $this->getSendTask(15743);
        // $mobile =array_filter(explode(',',$send_task['mobile_content'])) ;
        // $num = count($mobile);
        // print_r($num);die;

        /*         $redisMessageCodeSend = 'index:meassage:code:new:deliver:'; //验证码发送任务rediskey
for ($i = 0; $i < 10; $i++) {
$new_redisMessageCodeSend = $redisMessageCodeSend . $i;

// $redis->rPush($new_redisMessageCodeSend,'{"Stat":"DB:0141","Submit_time":"1911230919","Done_time":"1911230919"}');
// $j=4;
Db::startTrans();
try {
do {
$send                     = $redis->lPop($new_redisMessageCodeSend);
$send_data = json_decode($send,true);
if (!empty($send_data)){
$send_task = Db::table('yx_user_send_task')->where('id,task_no', $send_data['mar_task_id'])->find();
if (empty($send_task)) {
break;
}
$send_log = Db::query("SELECT `id` FROM yx_user_send_task_log WHERE `task_no` = ".$send_task['task_no']." AND `mobile` = ".$send_data['mobile']);
if ($send_log) {
if (isset($send_data['send_msgid'])) {//cmpp通道提交 推送到用户队列
$redis->rPush('index:meassage:code:cmppdeliver:'.$send_data['uid'],$send);
}
Db::table('yx_user_send_task_log')->where('id',$send_log[0]['id'])->update(['status_message' => $send_data['Stat'],'send_time' => $send_data['Done_time']]);
}else{
if (isset($send_data['send_msgid'])) {//cmpp通道提交 推送到用户队列
$redis->rPush('index:meassage:code:cmppdeliver:'.$send_data['uid'],$send);
}
$new_send_log = [];
$new_send_log = [
'task_no' => $send_task['task_no'],
'uid' => $send_data['uid'],
'mobile' => $send_data['mobile'],
'status_message' => $send_data['Stat'],
'real_message' => $send_data['real_message'],
// 'send_status' => $send_data['real_message'],
// 'send_time' => $send_data['real_message'],
'create_time' => time(),
];
Db::table('yx_user_send_task_log')->insert($new_send_log);
}

// $send_log = Db::table('yx_user_send_task_log')->where('id', $j)->find();
// $send_log = Db::table('yx_user_send_task_log')->where('id', $j)->find();
// $send_log = array_values(Db::query($getSendTaskSql));
// print_r($send_log);die;
// if (!empty($send_log)) {
// $send_log = $send_log['0'];
// if (in_array($send_log['mobile'],[15374535120,13597642198,15172090302,15072872678,15671228688,13597642198])) {
//     $send_data['Stat'] = 'DELIVRD';
// }
// Db::table('yx_user_send_task_log')->where('id',$j)->update(['status_message' => $send_data['Stat'],'send_time' => $send_data['Done_time']]);
// }
// $j++;
// die;
}
} while (!empty($send));

Db::commit();
} catch (\Exception $e) {

Db::rollback();
}

} */
        // echo time()-1574472176;die;
        /*  $error = Db::query("SELECT * FROM `yx_user_send_task_log` WHERE `create_time` > `send_time`");
        foreach ($error as $key => $value) {
        Db::table('yx_user_send_task_log')->where('id',$value['id'])->update(['send_time' => $value['create_time']+500]);
        } */
        /*    for ($i=5001; $i < 231222; $i++) {
        // $newtime = time()-284402;
        $send_log = Db::table('yx_user_send_task_log')->where('id', $i)->find();
        if ( $send_log['send_time'] <= $send_log['create_time']){
        Db::table('yx_user_send_task_log')->where('id',$i)->update(['send_time' => $send_log['create_time']+500]);
        }
        }
        die; */
        $send_status = [
            1 => 4669,
            2 => 45720,
            // 3 => 50000,
            // 4 => 200000,
        ];
        // $send_status_count = [
        //     1 => 'MBBLACK',
        //     2 => 'REJECTD',
        //     3 => 'DB:0141',
        //     4 => 'DELIVRD'
        // ];
        $send_status_count = [
            1 => 'DELIVRD',
            2 => 'MBBLACK',
            // 3 => 'DB:0141',
            // 4 => 'DELIVRD'
        ];
        asort($send_status);
        $max = max($send_status);
        // print_r($send_status);die;
        for ($n = 394579; $n < 440299; $n++) {

            $num     = mt_rand(1, $max);
            $sendNum = 0;
            foreach ($send_status as $sk => $sl) {
                if ($num <= $sl) {
                    $sendNum = $sk;
                    break;
                }
            }
            // print_r($sendNum);die;
            // $send_log = Db::query("SELECT * FROM yx_user_send_task_log WHERE `uid` = 10 AND id = ".$n);
            $send_log = Db::table('yx_user_send_task_log')->where('id', $n)->find();
            if (!empty($send_log)) {
                if (in_array($send_log['mobile'], [15374535120, 13597642198, 15172090302, 15072872678, 15671228688, 13597642198, 15827294990])) {
                    $send_data['Stat'] = 'DELIVRD';
                }
                $send_time         = $send_log['create_time'] + 80;
                $send_data['Stat'] = $send_status_count[$sendNum];
                Db::table('yx_user_send_task_log')->where('id', $n)->update(['status_message' => $send_data['Stat'], 'send_time' => $send_time]);
            }
            // $n++;
            // die;
        }
    }

    public function logReader()
    {
        $id = 15753;
        // while (true) {

        // }
        print_r(date('Y-m-d H:i:s', time()));
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        $task = Db::query("SELECT `log_path` from `yx_user_send_task` where delete_time=0 and id =" . $id);
        // print_r("SELECT `log_path` from yx_user_send_task where delete_time=0 and id =".$id);die;
        if (empty($task)) {
            // continue;
        }
        $log_path = $task[0]['log_path'];
        $file     = fopen($log_path, "r");
        $data     = array();
        $i        = 0;
        // $phone = '';
        // $j     = '';
        while (!feof($file)) {
            $cellVal = trim(fgets($file));
            $log     = json_decode($cellVal);
            // $phone .= $j . trim(fgets($file));//fgets()函数从文件指针中读取一行
            // // print_r($phone);die;
            // $j = ',';

            // print_r($data);die;
            $data[] = $log;
        }
        fclose($file);
        print_r(date('Y-m-d H:i:s', time()));
        echo count($data);
    }

    //使用数量更正
    public function businessTaskLogMove()
    {
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        $upnum_data = [];
        $upnum_uid = [];
        $up_real_num = [];
        for ($i = 1; $i < 60291; $i++) {
            $task = $this->getSendCodeTask($i);
            if (empty($task) || empty($task['log_path'])) {
                continue;
            }
            $send_length = mb_strlen($task['task_content'], 'utf8');
            $real_length = 1;
            if ($send_length > 70) {
                $real_length = ceil($send_length / 67);
            }
            $real_num = 0;
            $real_num += $real_length * $task['send_num'];
            if ($real_num != $task['real_num']) {
                $upnum = $real_num - $task['real_num'];
                if (!in_array($task['uid'], $upnum_uid)) {
                    $upnum_uid[] = $task['uid'];
                    $upnum_data[$task['uid']] = 0;
                    $upnum_data[$task['uid']] += $upnum;
                } else {
                    $upnum_data[$task['uid']] += $upnum;
                }
                $up_real_num[$task['id']] = $real_num;
            }
            // Db::table('yx_user_send_code_task')->where('id',$task['id'])->
        }
        // $up_equities = [];

        Db::startTrans();
        try {
            foreach ($up_real_num as $key => $value) {
                Db::table('yx_user_send_code_task')->where('id', $key)->update(['real_num' => $value]);
            }
            foreach ($upnum_data as $key => $value) {
                $user_equities = Db::query("SELECT id,num_balance FROM `yx_user_equities` WHERE `business_id` = '6' AND `uid` = " . $key);
                $up_num = $user_equities[0]['num_balance'] - $value;
                Db::table('yx_user_equities')->where('id', $user_equities[0]['id'])->update(['num_balance' => $up_num]);
            }
            Db::commit();
        } catch (\Exception $e) {
            exception($e);
            Db::rollback();
        }

        echo 'success';
    }

    //日志写入到数据表中行业
    public function removeCodeTaskLog()
    {
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        $time = strtotime(date('Y-m-d 0:00:00', time()));
        $start_time = strtotime(date('Y-m-d 0:00:00', strtotime("-1 day")));
        $ids = Db::query("SELECT `id` FROM  `yx_user_send_code_task` WHERE `create_time` < " . $time . " AND `create_time` >= " . $start_time . "  AND  `log_path` <> ''");
        $all_log = [];
        $j = 1;
        for ($i = 0; $i < count($ids); $i++) {
            $sendTask = $this->getSendCodeTask($ids[$i]['id']);
            $mobilesend = explode(',', $sendTask['mobile_content']);
            $send_length = mb_strlen($sendTask['task_content'], 'utf8');
            $real_length = 1;
            if ($send_length > 70) {
                $real_length = ceil($send_length / 67);
            }
            foreach ($mobilesend as $key => $value) {
                $send_log = [];
                $send_log = [
                    'uid' => $sendTask['uid'],
                    'task_no' => $sendTask['task_no'],
                    'task_content' => $sendTask['task_content'],
                    'mobile' => $value,
                    'source' => $sendTask['source'],
                    'send_length' => $send_length,
                    'send_status' => 2,
                    'free_trial' => 2,
                    'create_time' => $sendTask['create_time'],
                ];
                $all_log[] = $send_log;
                $j++;
            }
            if ($j > 5000) {
                Db::startTrans();
                try {
                    Db::table('yx_user_send_code_task_log')->insertAll($all_log);
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    exception($e);
                }
                $j = 1;
                unset($all_log);
            }
        }
        if (!empty($all_log)) {
            Db::startTrans();
            try {
                Db::table('yx_user_send_code_task_log')->insertAll($all_log);
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                exception($e);
            }
        }
        exit('Success');
    }

    //日志写入到数据表中营销
    public function removeMarketingTaskLog()
    {
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        $time = strtotime(date('Y-m-d 0:00:00', time()));
        $start_time = strtotime(date('Y-m-d 0:00:00', strtotime("-1 day")));
        $ids = Db::query("SELECT `id` FROM  `yx_user_send_task` WHERE `create_time` < " . $time . " AND  `create_time` >= " . $start_time . "   AND  `log_path` <> ''");
        $all_log = [];
        $j = 1;
        // echo count($ids);
        // die;
        for ($i = 0; $i < count($ids); $i++) {
            $sendTask = $this->getSendTask($ids[$i]['id']);
            $mobilesend = explode(',', $sendTask['mobile_content']);
            $send_length = mb_strlen($sendTask['task_content'], 'utf8');
            $real_length = 1;
            if ($send_length > 70) {
                $real_length = ceil($send_length / 67);
            }
            // print_r($sendTask);
            // die;
            foreach ($mobilesend as $key => $value) {
                $send_log = [];
                $send_log = [
                    'uid' => $sendTask['uid'],
                    'task_no' => $sendTask['task_no'],
                    'task_content' => $sendTask['task_content'],
                    'mobile' => $value,
                    'source' => $sendTask['source'],
                    'send_length' => $send_length,
                    'send_status' => 2,
                    'free_trial' => 2,
                    'create_time' => $sendTask['create_time'],
                ];
                $all_log[] = $send_log;
                $j++;
            }
            if ($j > 5000) {
                Db::startTrans();
                try {
                    Db::table('yx_user_send_task_log')->insertAll($all_log);
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    exception($e);
                }
                $j = 1;
                unset($all_log);
            }
        }
        if (!empty($all_log)) {
            Db::startTrans();
            try {
                Db::table('yx_user_send_task_log')->insertAll($all_log);
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                exception($e);
            }
        }
        exit('Success');
    }

    public function receiptBusinessToBase($channel_id)
    {
        // $redis->rpush('index:meassage:Buiness:cms:deliver:', json_encode($send_log));
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        $redis = Phpredis::getConn();
        // $redis->rpush('index:meassage:code:cms:deliver:' . $channel_id, json_encode($send_log)); //写入通道处理日志        
        /*   $redis->rpush('index:meassage:code:cms:deliver:' . $channel_id, json_encode(array(
            'mobile' => '18918508850',
            'title' => '美丽田园营销短信',
            'mar_task_id' => '1599',
            'content' => '感谢您对于美丽田园的信赖和支持，为了给您带来更好的服务体验，特邀您针对本次服务进行评价http://crmapp.beautyfarm.com.cn/questionNaire1/api/qnnaire/refct?id=534478，请您在24小时内提交此问卷，谢谢配合。期待您的反馈！如需帮助，敬请致电400-8206-142，回T退订【美丽田园】',
            'Msg_Id' => '',
            'Stat' => 'DELIVER',
            'Submit_time' => '191224164036',
            'Done_time' => '191224164236',
        ))); */
        $time = strtotime(date('Y-m-d 0:00:00', time()));
        while (true) {
            $sendlog = $redis->lpop('index:meassage:code:cms:deliver:' . $channel_id);
            if (empty($sendlog)) {
                exit('Send Log IS null');
            }
            $send_log = json_decode($sendlog, true);

            if (!isset($send_log['mar_task_id'])) {
                continue;
            }
            $sendTask = $this->getSendCodeTask($send_log['mar_task_id']);

            if (empty($sendTask)) {
                print_r($send_log);
                $redis->rpush('index:meassage:code:cms:deliver:' . $channel_id, $sendlog);
                continue;
            }
            $sendtasklog = Db::query("SELECT `id`,`create_time` FROM `yx_user_send_code_task_log` WHERE `task_no` = '" . $sendTask['task_no'] . "' AND `mobile` = '" . $send_log['mobile'] . "' ");
            // print_r($sendtasklog);
            // die;
            if (empty($sendtasklog)) {
                $redis->rpush('index:meassage:code:cms:deliver:' . $channel_id, $sendlog);
                exit;
            }
            // if ($sendtasklog[0]['create_time'] > $time) {
            //     $redis->rpush('index:meassage:code:cms:deliver:' . $channel_id, $sendlog);
            //     exit('today is success');
            // }
            if (strpos($send_log['content'], '问卷') !== false) {
                $status_message = 'DELIVRD';
            } else {
                $status_message =  $send_log['Stat'];
            }

            Db::startTrans();
            try {
                Db::table('yx_user_send_code_task_log')->where('id', $sendtasklog[0]['id'])->update(['real_message' => $send_log['Stat'], 'status_message' => $status_message]);
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                exception($e);
            }
        }
    }

    public function receiptMarketingToBase($channel_id)
    {
        // $redis->rpush('index:meassage:Buiness:cms:deliver:', json_encode($send_log));
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        $redis = Phpredis::getConn();
        // $redis->rpush('index:meassage:code:cms:deliver:' . $channel_id, json_encode($send_log)); //写入通道处理日志        
        /* $redis->rpush('index:meassage:code:cms:deliver:' . $channel_id, json_encode(array(
            'mobile' => '15045451231',
            'title' => '美丽田园营销短信',
            'mar_task_id' => '15850',
            'content' => '【DAPHNE】亲爱的会员：您的30元优惠券已到账，请前往DaphneFashion公众号-会员尊享-会员中心领取！退订回T',
            'Msg_Id' => '',
            'Stat' => 'DELIVER',
            'Submit_time' => '191224164036',
            'Done_time' => '191224164236',
        ))); */
        $time = strtotime(date('Y-m-d 0:00:00', time()));
        $channel              = $this->getChannelinfo($channel_id);

        while (true) {
            $sendlog = $redis->lpop('index:meassage:code:cms:deliver:' . $channel_id);
            if (empty($sendlog)) {
                exit('Send Log IS null');
            }
            $send_log = json_decode($sendlog, true);

            if (!isset($send_log['mar_task_id'])) {
                continue;
            }
            if ($channel['channel_type'] == 2) {
                if ($channel['business_id'] == 5) { //营销
                    $sendTask = $this->getSendTask($send_log['mar_task_id']);
                    if (empty($sendTask)) {
                        continue;
                    }
                    $sendtasklog = Db::query("SELECT `id`,`create_time` FROM `yx_user_send_task_log` WHERE `task_no` = '" . $sendTask['task_no'] . "' AND `mobile` = '" . $send_log['mobile'] . "' ");
                    // print_r($sendtasklog);
                    // die;
                    if (empty($sendtasklog)) {
                        print_r($send_log);
                        die;
                    }
                    if ($sendtasklog[0]['create_time'] > $time) {
                        $redis->rpush('index:meassage:code:cms:deliver:' . $channel_id, $sendlog);
                        exit('today is success');
                    }
                    if (strpos($send_log['content'], '问卷') !== false) {
                        $status_message = 'DELIVRD';
                    } else {
                        $status_message =  $send_log['Stat'];
                    }

                    Db::startTrans();
                    try {
                        Db::table('yx_user_send_task_log')->where('id', $sendtasklog[0]['id'])->update(['real_message' => $send_log['Stat'], 'status_message' => $status_message]);
                        Db::commit();
                    } catch (\Exception $e) {
                        Db::rollback();
                        exception($e);
                    }
                } elseif ($channel['business_id'] == 6) { // 行业
                    $sendTask = $this->getSendCodeTask($send_log['mar_task_id']);
                    if (empty($sendTask)) {
                        continue;
                    }
                    $sendtasklog = Db::query("SELECT `id`,`create_time` FROM `yx_user_send_code_task_log` WHERE `task_no` = '" . $sendTask['task_no'] . "' AND `mobile` = '" . $send_log['mobile'] . "' ");
                    // print_r($sendtasklog);
                    // die;
                    if (empty($sendtasklog)) {
                        print_r($send_log);
                        die;
                    }
                    if ($sendtasklog[0]['create_time'] > $time) {
                        $redis->rpush('index:meassage:code:cms:deliver:' . $channel_id, $sendlog);
                        exit('today is success');
                    }
                    if (strpos($send_log['content'], '问卷') !== false) {
                        $status_message = 'DELIVRD';
                    } else {
                        $status_message =  $send_log['Stat'];
                    }

                    Db::startTrans();
                    try {
                        Db::table('yx_user_send_code_task_log')->where('id', $sendtasklog[0]['id'])->update(['real_message' => $send_log['Stat'], 'status_message' => $status_message]);
                        Db::commit();
                    } catch (\Exception $e) {
                        Db::rollback();
                        exception($e);
                    }
                } elseif ($channel['business_id'] == 9) { //游戏
                    $sql .= " yx_user_send_game_task ";
                }
            }
        }
    }

    public function errotRpush()
    {
        $redis = Phpredis::getConn();
        $redisMessageCodeMsgId = 'index:meassage:code:msg:id:1';

        $redisMessageCodeDeliver    = 'index:meassage:code:new:deliver:1'; //行业通知MsgId
        // {"Stat":"DELIVRD","Submit_time":"2001161532","Done_time":"2001161534","mobile":"13739310156\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000","receive_time":1579160061,"Msg_Id":"406718912655530494"}
        $redis->rpush("index:meassage:code:unknow:deliver:24", json_encode([
            'Stat' => 'DELIVRD',
            'Submit_time' => '2001161532',
            'Done_time' => '2001161534',
            'mobile' => '13739310156',
            'receive_time' => '1579160061',
            'Msg_Id' => '406718912655530494',
        ]));
        while (true) {
            $status = $redis->lpop("index:meassage:code:unknow:deliver:24");
            $new_status = json_decode($status, true);
            $mesage = $redis->hget($redisMessageCodeMsgId, $new_status['Msg_Id']);
            if ($mesage) {
                $redis->hdel($redisMessageCodeMsgId, $new_status['Msg_Id']);
                // $redis->rpush($redisMessageCodeDeliver,$mesage.":".$Msg_Content['Stat']);
                $mesage                = json_decode($mesage, true);
                $mesage['Stat']        = $new_status['Stat'];
                // $mesage['Msg_Id']        = $Msg_Content['Msg_Id1'] . $Msg_Content['Msg_Id2'];
                $mesage['Submit_time'] = $new_status['Submit_time'];
                $mesage['Done_time']   = $new_status['Done_time'];
                $mesage['receive_time'] = time(); //回执时间戳
                $redis->rpush($redisMessageCodeDeliver, json_encode($mesage));
            }
        }
    }
}
