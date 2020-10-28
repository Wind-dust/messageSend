<?php

namespace app\console\com;

use app\console\Pzlife;
use cache\Phpredis;
use cache\PhpredisNew;
use app\facade\DbSendMessage;
use Config;
use Env;
use think\Db;

class CmppCreateCodeTask extends Pzlife
{
    //游戏任务创建function
    public function CreateTask()
    { //CMPP创建单条任务营销
        $redis                    = Phpredis::getConn();
        $redisMessageCodeSendReal = 'index:meassage:code:send:realtask'; //验证码发送真实任务rediskey CMPP接口 营销
        // echo date('Y-m-d H:i:s')."\n";
        /*  for ($i=0; $i < 100000; $i++) {
        
       
        } */
        //  $redis->rpush($redisMessageCodeSendReal,'{"mobile":"15821193682","messagetotal":2,"develop_no":"4719","Service_Id":"C48515","Source_Addr":"C48515","send_msgid":["1597212930000514","1597212931000515"],"message":"\u3010\u65bd\u534e\u6d1b\u4e16\u5947\u3011\u4eb2\u7231\u7684\u4f1a\u5458\uff0c\u611f\u8c22\u60a8\u4e00\u8def\u4ee5\u6765\u7684\u652f\u6301\uff01\u60a8\u5df2\u83b7\u5f972020\u5e74\u4f1a\u5458\u5468\u5e74\u793c\u5238\uff0c\u8d2d\u4e70\u6b63\u4ef7\u5546\u54c1\u6ee11999\u5143\u5373\u53ef\u83b7\u5f97\u95ea\u8000\u73ab\u7470\u91d1\u8272\u7b80\u7ea6\u540a\u5760\u4e00\u6761\uff0c\u8bf7\u4e8e2020\u5e7410\u670819\u65e5\u524d\u4f7f\u7528\u3002\u53ef\u524d\u5f80\u201c\u65bd\u534e\u6d1b\u4e16\u5947\u4f1a\u5458\u4e2d\u5fc3\u201d\u5c0f\u7a0b\u5e8f\u67e5\u770b\u8be5\u5238\u3002\u8be6\u8be24006901078\u3002 \u56deTD\u9000\u8ba2","Submit_time":1597212931}');
        // echo date('Y-m-d H:i:s')."\n";die;

        //写入失败加上机器人提醒
        while (true) {
            $SendText = $redis->lPop($redisMessageCodeSendReal);
            if (empty($SendText)) {
                // echo date('Y-m-d H:i:s')."\n";die;
                // exit('send_task is_null');
                sleep(1);
                continue;
            }
            // $send = explode(':', $SendText);

            $send = json_decode($SendText, true);
            // print_r($send);die;
            // $user = $this->getUserInfo($send[0]);
            $channel_id = 0;


            $user = Db::query("SELECT * FROM yx_users WHERE `nick_name` = '" . trim($send['Source_Addr']) . "' ");
            $user = $user[0];
            if (empty($user)) {
                continue;
            }
            if ($user['user_status'] == 1) {
                continue;
            }
            $uid = $user['id'];
            $business_id =  $user['business_id'];
            $userEquities = $this->getUserEquities($uid, $business_id); //普通营销
            if (empty($userEquities)) {
                /* foreach($send['send_msgid'] as $key => $value){
                   
                } */
                $redis->rPush('index:meassage:code:user:receive:' . $uid, json_encode(['Stat' => 'REJECTED', 'Submit_time' => date('YMDHM', time()), 'Done_time'   => date('YMDHM', time()), 'send_msgid'  => join(',', $send['send_msgid']), 'develop_no' =>  $send['develop_no']]));
                continue;
            }
            if ($userEquities['num_balance'] < 1 && $user['reservation_service'] == 1) {
                $redis->rPush('index:meassage:code:user:receive:' . $uid, json_encode(['Stat' => 'REJECTED', 'Submit_time' => date('YMDHM', time()), 'Done_time'   => date('YMDHM', time()), 'send_msgid'  => join(',', $send['send_msgid']), 'develop_no' =>  $send['develop_no']]));
                continue;
            }

            $send_code_task            = [];

            // $send_code_task['task_content']   = $send[2];
            // $send_code_task['mobile_content'] = $send[1];
            // $send_code_task['uid']            = $send[0];
            // $send_code_task['source']         = $send[4];
            // $send_code_task['msg_id']         = $send[3];
            $send_code_task['free_trial'] = 1;
            $send_code_task['send_msg_id']    = join(',', $send['send_msgid']);
            $send_code_task['uid']            = $uid;
            $send_code_task['task_content']   = trim($send['message']);
            $send_code_task['submit_time']    = $send['Submit_time'];
            $send_code_task['create_time']    = time();
            $send_code_task['mobile_content'] = $send['mobile'];
            $send_code_task['develop_no'] = $send['develop_no'];
            $send_code_task['send_num']       = 1;
            $send_code_task['update_time']       = time();

            $send_code_task['send_length']    = mb_strlen(trim($send['message']));
            if ($send_code_task['send_length'] > 70) {
                $send_code_task['real_num'] = ceil($send_code_task['send_length'] / 67);
            } else {
                $send_code_task['real_num'] = 1;
            }
            // $sendData['uid']          = 1;
            // $sendData['Submit_time']  = date('YMDHM', time());
            //免审用户

            // print_r($user);die;
            if ($user['marketing_free_trial'] == 2 && $business_id == 5) {
                if ($userEquities['num_balance'] < 1) {
                    $send_code_task['free_trial'] = 1;
                } else {
                    $send_code_task['free_trial'] = 2;
                }
                $send_code_task['task_no'] = 'mar' . date('ymdHis') . substr(uniqid('', true), 15, 8);
                $table = 'yx_user_send_task';
                $rediskey = 'index:meassage:marketing:sendtask';
            } elseif ($user['free_trial'] == 2 && $business_id == 6) {
                if ($userEquities['num_balance'] < 1) {
                    $send_code_task['free_trial'] = 1;
                } else {
                    $send_code_task['free_trial'] = 2;
                }
                $send_code_task['task_no'] = 'bus' . date('ymdHis') . substr(uniqid('', true), 15, 8);
                $table = 'yx_user_send_code_task';
                $rediskey = 'index:meassage:business:sendtask';
            } elseif ($business_id == 9) {
                $table = 'yx_user_send_game_task';
                $send_code_task['task_no'] = 'gam' . date('ymdHis') . substr(uniqid('', true), 15, 8);
                $rediskey = 'index:meassage:game:sendtask';
            }
            $channel = Db::query("SELECT * FROM yx_user_channel WHERE `uid` = " . $uid);
            if (empty($channel)) {
                $send_code_task['free_trial'] = 1;
            } else {
                $channel = $channel[0];
            }
            //  print_r($send_code_task);die;

            if ($send_code_task['free_trial'] == 2) {

                Db::startTrans();
                try {
                    // $send_code_task['free_trial'] = 2;
                    //游戏任务
                    $send_code_task['yidong_channel_id']     = $channel['yidong_channel_id'];
                    $send_code_task['liantong_channel_id']    =  $channel['liantong_channel_id'];
                    $send_code_task['dianxin_channel_id']     = $channel['dianxin_channel_id'];
                    /*  if ($send_code_task['free_trial'] == 2) {
                        
                    } */

                    /* $task_id = Db::table('yx_user_send_game_task')->insertGetId($send_code_task);
                    //扣除余额
                    $new_num_balance = $userEquities['num_balance'] - 1;
                    Db::table('yx_user_equities')->where('id', $userEquities['id'])->update(['num_balance' => $new_num_balance]);
                    Db::commit();
                    $redis->rPush('index:meassage:game:sendtask', $task_id); */
                    /*  $task_id = Db::table('yx_user_send_code_task')->insertGetId($send_code_task);
                    //扣除余额
                    $new_num_balance = $userEquities['num_balance'] - 1;
                    Db::table('yx_user_equities')->where('id', $userEquities['id'])->update(['num_balance' => $new_num_balance]);
                    Db::commit();
                    // ['id' => $value, 'deduct' => 0]
                    $redis->rPush('index:meassage:business:sendtask', json_encode(['id'=>$task_id,'deduct' => 0])); */
                    $task_id = Db::table($table)->insertGetId($send_code_task);
                    //扣除余额
                    $new_num_balance = $userEquities['num_balance'] - 1;
                    Db::table('yx_user_equities')->where('id', $userEquities['id'])->update(['num_balance' => $new_num_balance]);
                    Db::commit();
                    // ['id' => $value, 'deduct' => 0]

                    $redis->rPush($rediskey, json_encode(['id' => $task_id, 'send_time' => 0, 'deduct' => 0]));
                } catch (\Exception $e) {
                    $redis->rPush($redisMessageCodeSendReal, $SendText);
                    Db::rollback();
                    exception($e);
                }
            } elseif ($send_code_task['free_trial'] == 1) { //需审核用户
                Db::startTrans();
                try {
                    // $send_code_task['free_trial'] = 1;
                    // $task_id                      = Db::table('yx_user_send_game_task')->insertGetId($send_code_task);
                    $task_id                      = Db::table($table)->insertGetId($send_code_task);
                    //扣除余额
                    $new_num_balance = $userEquities['num_balance'] - 1;
                    Db::table('yx_user_equities')->where('id', $userEquities['id'])->update(['num_balance' => $new_num_balance]);
                    Db::commit();
                    if ($business_id == 5) {
                        $api = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=fa1c9682-f617-45f9-a6a3-6b65f671b457';
                        $check_data = [];
                        $check_data = [
                            'msgtype' => "text",
                            'text' => [
                                "content" => "Hi，审核机器人\n您有一条新的短信任务需要审核\n【任务类型】：营销短信\n【任务编号】:" . $send_code_task['task_no'] . " \n 【用户信息】：uid[" . $user['id'] . "]用户昵称[" . $user['nick_name'] . "]\n【任务信息】：" . $send_code_task['task_content'],
                            ],
                        ];
                        $headers = [
                            'Content-Type:application/json'
                        ];
                        $audit_api =   $this->sendRequestRebort($api, 'post', $check_data, $headers);
                    } elseif ($business_id == 6) {
                        $api = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=fa1c9682-f617-45f9-a6a3-6b65f671b457';
                        $check_data = [];
                        $check_data = [
                            'msgtype' => "text",
                            'text' => [
                                "content" => "Hi，审核机器人\n您有一条新的短信任务需要审核\n【任务类型】：行业短信\n【任务编号】:" . $send_code_task['task_no'] . " \n 【用户信息】：uid[" . $user['id'] . "]用户昵称[" . $user['nick_name'] . "]\n【任务信息】：" . $send_code_task['task_content'],
                            ],
                        ];
                        $headers = [
                            'Content-Type:application/json'
                        ];
                        $audit_api =   $this->sendRequestRebort($api, 'post', $check_data, $headers);
                    }
                } catch (\Exception $e) {
                    Db::rollback();
                    $redis->rPush($redisMessageCodeSendReal, $SendText);
                    exception($e);
                }
            }
            // print_r($user);die;
        }
    }

    private function getUserInfo($uid)
    {
        $getUserSql = sprintf("select id,user_status,reservation_service,free_trial,marketing_free_trial from yx_users where delete_time=0 and id = %d", $uid);
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
        if (!empty($sendTask['template_id'])) {
            $template_id = DbSendMessage::getUserMultimediaTemplate(['template_id' => $sendTask['template_id']], 'id', true);
            // $content_data = DbSendMessage::getUserMultimediaTemplateFrame(['multimedia_template_id' => $template_id['id']], 'num,name,content,image_path,image_type,variable_len', false, ['num' => 'asc']);
            $content_data = Db::query("select `num`,`name`,`content`,`image_path`,`image_type`,`variable_len` from yx_user_multimedia_template_frame  where delete_time=0 and `multimedia_template_id`  = " . $template_id['id'] . "  ORDER BY `num` ASC ");
        } else {
            $content_data             = Db::query("select `id`,`content`,`num`,`image_path`,`image_type` from yx_user_multimedia_message_frame where delete_time=0 and `multimedia_message_id` = " . $sendTask['id'] . "  ORDER BY `num` ASC ");
        }

        $sendTask['task_content'] = $content_data;
        return $sendTask;
    }

    private function getSupMessageSendTask($id)
    {
        $getSendTaskSql = sprintf("select * from yx_user_sup_message where delete_time=0 and id = %d", $id);
        // print_r($getUserSql);die;
        $sendTask = Db::query($getSendTaskSql);
        if (!$sendTask) {
            return [];
        }
        $sendTask                 = $sendTask[0];
        if (!empty($sendTask['template_id'])) {
            $template_id = DbSendMessage::getUserSupMessageTemplate(['template_id' => $sendTask['template_id']], 'id', true);
            // $content_data = DbSendMessage::getUserMultimediaTemplateFrame(['multimedia_template_id' => $template_id['id']], 'num,name,content,image_path,image_type,variable_len', false, ['num' => 'asc']);
            $content_data = Db::query("select `id`,`num`,`content`,`content_type`,`type` from yx_user_sup_message_template_frame  where delete_time=0 and `multimedia_template_id`  = " . $template_id['id'] . "  ORDER BY `num` ASC ");
        } else {
            $content_data             = Db::query("select `id`,`num`,`content`,`content_type`,`type` from yx_user_sup_message_frame where delete_time=0 and `multimedia_message_id` = " . $sendTask['id'] . "  ORDER BY `num` ASC ");
        }

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

    public function pushMarketingMessageSendTask()
    {
        $this->redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        /* 
                                    1321785 1322036
                                    */
        // $task_id = Db::query("SELECT `id`,`task_no` FROM yx_user_send_task WHERE  `create_time` >= '1596160800' AND `uid` IN (153,185) ");
        /*    $task_id = Db::query("SELECT `id`,`uid` FROM yx_user_send_task WHERE  `id` >= 168848  ");
        foreach ($task_id as $key => $value) {
            $this->redis->rpush("index:meassage:marketing:sendtask", json_encode(['id' => $value['id'], 'send_time' => 0, 'deduct' => 10]));
            // usleep(50000);
        } */
        // $task_id = [237014,237019,237020,237022,237023,237050,237051,237052,237053,237072,237073,237074,237077,237078,237079,237083,237085,237087,237103,237110,237113,237114,237115,237116,237117,237119,237122,237123,237124,237125,237126,237127,237800,237801,237802,237803,237804,237805,237806,237809,237810,237811,237812,237813,237817,237818,237819,237828,237830,237832,237834,237841,237843,237844,237845,238357];
        $task_id = [298874];
        foreach ($task_id as $key => $value) {
            /*  if (Db::query("SELECT `task_no` FROM yx_user_send_task_log WHERE `task_no` = '".$value['task_no']."' ")) {
                continue;
            } */
            $this->redis->rpush("index:meassage:marketing:sendtask", json_encode(['id' => $value, 'send_time' => 0, 'deduct' => 20]));
            // usleep(50000);
        }
    }

    //书写普通营销任务日志并写入通道
    public function createMessageSendTaskLog()
    {
        $this->redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        // date_default_timezone_set('PRC');
        $redisMessageMarketingSend = Config::get('rediskey.message.redisMessageCodeSend');
        // $send = $this->redis->rPush('index:meassage:marketing:sendtask', json_encode(['id' => 167053, 'send_time' => 0,'deduct' => 0]));
        // $send = $this->redis->rPush('index:meassage:marketing:sendtask',json_encode(['id' => 15823,'send_time' => 0]));
        // $send = $this->redis->rPush('index:meassage:marketing:sendtask',json_encode(['id' => 15824,'send_time' => 0]));
        // $send = $this->redis->rPush('index:meassage:marketing:sendtask',json_encode(['id' => 15825,'send_time' => 0]));
        // $send = $this->redis->rPush('index:meassage:marketing:sendtask',json_encode(['id' => 15826,'send_time' => 0]));
        // $send = $this->redis->rPush('index:meassage:marketing:sendtask',json_encode(['id' => 15827,'send_time' => 0]));
        // echo time() -1576290017;die;
        while (true) {
            // echo date('Y-m-d H:i:s');
            // echo "\n";
            try {
                while (true) {
                    $j = 1;

                    $rollback      = [];
                    $all_log       = [];
                    $true_log      = [];
                    $push_messages = [];
                    $real_length = 1;
                    $send        = $this->redis->lpop('index:meassage:marketing:sendtask');
                    // $send = 15753;
                    if (empty($send)) {
                        // print_r(date('Y-m-d H:i:s', time()));
                        // echo "\n";
                        // exit('taskId_is_null');
                        sleep(1);
                        break;
                    }
                    $real_send = json_decode($send, true);
                    // print_r($real_send);die;
                    if ($real_send['send_time'] > time()) {
                        $this->redis->rPush('index:meassage:marketing:sendtask', json_encode($real_send));
                        continue;
                    }
                    /*  $time = microtime(true);
                    //结果：1541053888.5911
                    //在经过处理得到最终结果:
                    $lastTime = (int)($time * 1000);
                    echo $lastTime;
                    echo "\n"; */
                    $sendTask = $this->getSendTask($real_send['id']);

                    // print_r($sendTask);die;
                    if (Db::query("SELECT `id` FROM `yx_user_send_task_log` WHERE `task_no` = '" . $sendTask['task_no'] . "' ")) {
                        continue;
                    }
                    if (empty($sendTask)) {
                        continue;
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
                    // $yidong_channel_id   = 0;
                    $yidong_channel_id   = $sendTask['yidong_channel_id'];
                    // $liantong_channel_id = 0;
                    $liantong_channel_id = $sendTask['liantong_channel_id'];
                    // $dianxin_channel_id  = 0;
                    $dianxin_channel_id  = $sendTask['dianxin_channel_id'];
                    // $error_mobile        = [];
                    if (strpos($sendTask['task_content'], '验证码')  !== false || strpos($sendTask['task_content'], '生日')) {
                        $real_send['deduct'] = 0;
                    }
                    $real_send['deduct'] = isset($real_send['deduct']) ? $real_send['deduct'] : 0;
                    $mobile_result = [];
                    $yidong_mobile = [];
                    $liantong_mobile = [];
                    $dianxin_mobile = [];
                    $error_mobile = [];
                    $deduct_mobile = [];
                    // $mobile_result = $this->mobilesFiltrate($sendTask['mobile_content'], $sendTask['uid'], $real_send['deduct']);
                    $mobile_result = $this->SecondMobilesFiltrate($sendTask['mobile_content'], $sendTask['uid'], $real_send['deduct']);

                    // print_r($mobile_result);die;
                    /*  return ['error_mobile' => $error_mobile, 'yidong_mobile' => $yidong_mobile,'liantong_mobile' => $liantong_mobile, 'dianxin_mobile' => $dianxin_mobile, 'deduct_mobile' => $deduct_mobile]; */
                    /* 实际发送号码 */
                    $yidong_mobile = $mobile_result['yidong_mobile'];
                    $liantong_mobile = $mobile_result['liantong_mobile'];
                    $dianxin_mobile = $mobile_result['dianxin_mobile'];
                    /* 错号和扣量号码 */
                    $error_mobile = $mobile_result['error_mobile'];
                    $deduct_mobile = $mobile_result['deduct_mobile'];
                    // print_r($mobile_result);die;
                    /* echo "黑名单:".count($error_mobile);
                    echo "扣量名单:".count($deduct_mobile);
                    echo "移动:".count($yidong_mobile);
                    echo "联通:".count($liantong_mobile);
                    echo "电信:".count($dianxin_mobile);
                    die; */
                    $j = 1;
                    if (!empty($yidong_mobile)) {
                        for ($i = 0; $i < count($yidong_mobile); $i++) {
                            $send_log = [
                                'task_no'      => $sendTask['task_no'],
                                'uid'          => $sendTask['uid'],
                                'source'       => $sendTask['source'],
                                'task_content' => $sendTask['task_content'],
                                'mobile'       => $yidong_mobile[$i],
                                'channel_id'   => $yidong_channel_id,
                                'send_length'  => $send_length,
                                'develop_no'  => $sendTask['develop_no'] ? $sendTask['develop_no'] : 1,
                                'send_status'  => 2,
                                'create_time'  => time(),
                            ];
                            $sendmessage = [
                                'mobile'      => $yidong_mobile[$i],
                                'mar_task_id' => $sendTask['id'],
                                'content'     => $sendTask['task_content'],
                                'channel_id'  => $yidong_channel_id,
                                'from'        => 'yx_user_send_task',
                                'send_msg_id'        => $sendTask['send_msg_id'],
                                'uid'          => $sendTask['uid'],
                                'send_num'          => $sendTask['send_num'],
                                'task_no'      => $sendTask['task_no'],
                            ];
                            if (!empty($sendTask['develop_no'])) {
                                $sendmessage['develop_code'] = $sendTask['develop_no'];
                            }

                            // fwrite($myfile, $txt);
                            $push_messages[] = $sendmessage;
                            $true_log[]      = $send_log;
                            $j++;
                            if ($j > 100) {
                                Db::startTrans();
                                try {
                                    Db::table('yx_user_send_task_log')->insertAll($true_log);

                                    Db::commit();
                                    foreach ($push_messages as $key => $value) {
                                        $send_channelid = $value['channel_id'];
                                        unset($value['channel_id']);
                                        $res = $this->redis->rpush('index:meassage:code:send' . ":" . $send_channelid, json_encode($value)); //三体营销通道
                                    }
                                } catch (\Exception $e) {
                                    // $this->redis->rPush('index:meassage:business:sendtask', $send);
                                    if (!empty($rollback)) {
                                        foreach ($rollback as $key => $value) {
                                            $this->redis->rPush('index:meassage:marketing:sendtask', $value);
                                        }
                                    }

                                    Db::rollback();
                                    exception($e);
                                }
                                $j = 1;
                                $push_messages = [];
                                $true_log = [];
                            }
                        }
                    }
                    if (!empty($liantong_mobile)) {
                        for ($i = 0; $i < count($liantong_mobile); $i++) {
                            $send_log = [
                                'task_no'      => $sendTask['task_no'],
                                'uid'          => $sendTask['uid'],
                                'source'       => $sendTask['source'],
                                'task_content' => $sendTask['task_content'],
                                'mobile'       => $liantong_mobile[$i],
                                'channel_id'   => $liantong_channel_id,
                                'send_length'  => $send_length,
                                'develop_no'  => $sendTask['develop_no'] ? $sendTask['develop_no'] : 1,
                                'send_status'  => 2,
                                'create_time'  => time(),
                            ];
                            $sendmessage = [
                                'mobile'      => $liantong_mobile[$i],
                                'mar_task_id' => $sendTask['id'],
                                'content'     => $sendTask['task_content'],
                                'channel_id'  => $liantong_channel_id,
                                'from'        => 'yx_user_send_task',
                                'send_msg_id'        => $sendTask['send_msg_id'],
                                'uid'          => $sendTask['uid'],
                                'send_num'          => $sendTask['send_num'],
                                'task_no'      => $sendTask['task_no'],
                            ];
                            if (!empty($sendTask['develop_no'])) {
                                $sendmessage['develop_code'] = $sendTask['develop_no'];
                            }

                            // fwrite($myfile, $txt);
                            $push_messages[] = $sendmessage;
                            $true_log[]      = $send_log;
                            $j++;
                            if ($j > 100) {
                                Db::startTrans();
                                try {
                                    Db::table('yx_user_send_task_log')->insertAll($true_log);

                                    Db::commit();
                                    foreach ($push_messages as $key => $value) {
                                        $send_channelid = $value['channel_id'];
                                        unset($value['channel_id']);
                                        $res = $this->redis->rpush('index:meassage:code:send' . ":" . $send_channelid, json_encode($value)); //三体营销通道
                                    }
                                } catch (\Exception $e) {
                                    // $this->redis->rPush('index:meassage:business:sendtask', $send);
                                    if (!empty($rollback)) {
                                        foreach ($rollback as $key => $value) {
                                            $this->redis->rPush('index:meassage:marketing:sendtask', $value);
                                        }
                                    }

                                    Db::rollback();
                                    exception($e);
                                }
                                $j = 1;
                                $push_messages = [];
                                $true_log = [];
                            }
                        }
                    }
                    if (!empty($dianxin_mobile)) {
                        for ($i = 0; $i < count($dianxin_mobile); $i++) {
                            $send_log = [
                                'task_no'      => $sendTask['task_no'],
                                'uid'          => $sendTask['uid'],
                                'source'       => $sendTask['source'],
                                'task_content' => $sendTask['task_content'],
                                'mobile'       => $dianxin_mobile[$i],
                                'channel_id'   => $dianxin_channel_id,
                                'send_length'  => $send_length,
                                'develop_no'  => $sendTask['develop_no'] ? $sendTask['develop_no'] : 1,
                                'send_status'  => 2,
                                'create_time'  => time(),
                            ];
                            $sendmessage = [
                                'mobile'      => $dianxin_mobile[$i],
                                'mar_task_id' => $sendTask['id'],
                                'content'     => $sendTask['task_content'],
                                'channel_id'  => $dianxin_channel_id,
                                'from'        => 'yx_user_send_task',
                                'send_msg_id'        => $sendTask['send_msg_id'],
                                'uid'          => $sendTask['uid'],
                                'send_num'          => $sendTask['send_num'],
                                'task_no'      => $sendTask['task_no'],
                            ];
                            if (!empty($sendTask['develop_no'])) {
                                $sendmessage['develop_code'] = $sendTask['develop_no'];
                            }

                            // fwrite($myfile, $txt);
                            $push_messages[] = $sendmessage;
                            $true_log[]      = $send_log;
                            $j++;
                            if ($j > 100) {
                                Db::startTrans();
                                try {
                                    Db::table('yx_user_send_task_log')->insertAll($true_log);

                                    Db::commit();
                                    foreach ($push_messages as $key => $value) {
                                        $send_channelid = $value['channel_id'];
                                        unset($value['channel_id']);
                                        $res = $this->redis->rpush('index:meassage:code:send' . ":" . $send_channelid, json_encode($value)); //三体营销通道
                                    }
                                } catch (\Exception $e) {
                                    // $this->redis->rPush('index:meassage:business:sendtask', $send);
                                    if (!empty($rollback)) {
                                        foreach ($rollback as $key => $value) {
                                            $this->redis->rPush('index:meassage:marketing:sendtask', $value);
                                        }
                                    }

                                    Db::rollback();
                                    exception($e);
                                }
                                $j = 1;
                                $push_messages = [];
                                $true_log = [];
                            }
                        }
                    }
                    if (!empty($true_log)) {
                        Db::startTrans();
                        try {
                            Db::table('yx_user_send_task_log')->insertAll($true_log);

                            Db::commit();
                            foreach ($push_messages as $key => $value) {
                                $send_channelid = $value['channel_id'];
                                unset($value['channel_id']);
                                $res = $this->redis->rpush('index:meassage:code:send' . ":" . $send_channelid, json_encode($value)); //三体营销通道
                            }
                            $j = 1;
                            $push_messages = [];
                            $true_log = [];
                        } catch (\Exception $e) {
                            // $this->redis->rPush('index:meassage:business:sendtask', $send);
                            if (!empty($rollback)) {
                                foreach ($rollback as $key => $value) {
                                    $this->redis->rPush('index:meassage:marketing:sendtask', $value);
                                }
                            }

                            Db::rollback();
                            exception($e);
                        }
                    }
                    /* 错号及扣量 */
                    // $error_mobile = $mobile_result['error_mobile'];
                    // $deduct_mobile = $mobile_result['deduct_mobile'];
                    if (!empty($deduct_mobile)) {
                        for ($i = 0; $i < count($deduct_mobile); $i++) {
                            $send_log = [
                                'task_no'        => $sendTask['task_no'],
                                'uid'            => $sendTask['uid'],
                                // 'title'          => $sendTask['task_name'],
                                'task_content'   => $sendTask['task_content'],
                                'source'         => $sendTask['source'],
                                'mobile'         => $deduct_mobile[$i],
                                'develop_no'  => $sendTask['develop_no'] ? $sendTask['develop_no'] : 1,
                                'send_status'    => 4,
                                'create_time'    => time(),
                                'send_length'    => $send_length,
                                'status_message' => 'DELIVRD', //无效号码
                                'real_message'   => 'DEDUCT:1',
                            ];
                            $all_log[] = $send_log;
                            $sendmessage = [
                                'task_no' => $sendTask['task_no'],
                                'mar_task_id' => $sendTask['id'],
                                'uid'            => $sendTask['uid'],
                                'msg_id'            => $sendTask['send_msg_id'],
                                'Stat' => 'DELIVRD',
                                'mobile' =>  $deduct_mobile[$i],
                                'content'   => $sendTask['task_content'],
                                'from'   => 'yx_user_send_task',
                                'send_msg_id'        => $sendTask['send_msg_id'],
                                'Submit_time'   => time(),
                            ];
                            // $this->redis->rpush('index:message:code:deduct:deliver', json_encode());

                            // fwrite($myfile, $txt);
                            $push_messages[] = $sendmessage;
                            $j++;
                            if ($j > 100) {
                                Db::startTrans();
                                try {
                                    Db::table('yx_user_send_task_log')->insertAll($all_log);

                                    Db::commit();
                                    foreach ($push_messages as $key => $value) {
                                        $res = $this->redis->rpush('index:message:code:deduct:deliver', json_encode($value)); //三体营销通道
                                    }
                                    $j = 1;
                                    $push_messages = [];
                                    $all_log = [];
                                } catch (\Exception $e) {
                                    // $this->redis->rPush('index:meassage:business:sendtask', $send);
                                    if (!empty($rollback)) {
                                        foreach ($rollback as $key => $value) {
                                            $this->redis->rPush('index:meassage:marketing:sendtask', $value);
                                        }
                                    }

                                    Db::rollback();
                                    exception($e);
                                }
                            }
                        }
                    }
                    if (!empty($error_mobile)) {
                        for ($i = 0; $i < count($error_mobile); $i++) {
                            $send_log = [
                                'task_no'        => $sendTask['task_no'],
                                'uid'            => $sendTask['uid'],
                                // 'title'          => $sendTask['task_name'],
                                'task_content'   => $sendTask['task_content'],
                                'source'         => $sendTask['source'],
                                'mobile'         => $error_mobile[$i],
                                'develop_no'  => $sendTask['develop_no'] ? $sendTask['develop_no'] : 1,
                                'send_status'    => 4,
                                'create_time'    => time(),
                                'send_length'    => $send_length,
                                'status_message' => 'DB:0101', //无效号码
                                'real_message'   => 'ERROR:1',
                            ];
                            $all_log[] = $send_log;
                            $sendmessage = [
                                'task_no' => $sendTask['task_no'],
                                'mar_task_id' => $sendTask['id'],
                                'uid'            => $sendTask['uid'],
                                'msg_id'            => $sendTask['send_msg_id'],
                                'Stat' => 'DB:0101',
                                'mobile' =>  $error_mobile[$i],
                                'content'   => $sendTask['task_content'],
                                'from'   => 'yx_user_send_task',
                                'Submit_time'   => time(),
                                'send_msg_id'        => $sendTask['send_msg_id'],
                            ];
                            // $this->redis->rpush('index:message:code:deduct:deliver', json_encode());

                            // fwrite($myfile, $txt);
                            $push_messages[] = $sendmessage;
                            $j++;
                            if ($j > 100) {
                                Db::startTrans();
                                try {
                                    Db::table('yx_user_send_task_log')->insertAll($all_log);

                                    Db::commit();
                                    foreach ($push_messages as $key => $value) {
                                        $res = $this->redis->rpush('index:message:code:deduct:deliver', json_encode($value)); //三体营销通道
                                    }
                                    $j = 1;
                                    $push_messages = [];
                                    $all_log = [];
                                } catch (\Exception $e) {
                                    // $this->redis->rPush('index:meassage:business:sendtask', $send);
                                    if (!empty($rollback)) {
                                        foreach ($rollback as $key => $value) {
                                            $this->redis->rPush('index:meassage:marketing:sendtask', $value);
                                        }
                                    }

                                    Db::rollback();
                                    exception($e);
                                }
                            }
                        }
                    }
                    if (!empty($all_log)) {
                        Db::startTrans();
                        try {
                            Db::table('yx_user_send_task_log')->insertAll($all_log);

                            Db::commit();
                            foreach ($push_messages as $key => $value) {
                                $res = $this->redis->rpush('index:message:code:deduct:deliver', json_encode($value)); //三体营销通道
                            }
                            $j = 1;
                            $push_messages = [];
                            $all_log = [];
                        } catch (\Exception $e) {
                            // $this->redis->rPush('index:meassage:business:sendtask', $send);
                            if (!empty($rollback)) {
                                foreach ($rollback as $key => $value) {
                                    $this->redis->rPush('index:meassage:marketing:sendtask', $value);
                                }
                            }

                            Db::rollback();
                            exception($e);
                        }
                    }
                    unset($all_log);
                    unset($true_log);
                    unset($push_messages);
                    unset($rollback);
                }
            } catch (\Exception $th) {
                //throw $th;
                $this->writeToRobot('cmppcreatecodetask', $th, 'createMessageSendTaskLog');
            }

            /*  $time = microtime(true);
                //结果：1541053888.5911
                //在经过处理得到最终结果:
                $lastTime = (int)($time * 1000);
                echo $lastTime;
                echo "\n";
 */
            sleep(1);
        }
    }

    //书写定时营销任务日志并写入通道
    public function createTimingMessageSendTaskLog()
    {
        $this->redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        // date_default_timezone_set('PRC');
        // $send = $this->redis->rPush('index:meassage:marketing:sendtask', json_encode(['id' => 15924, 'send_time' => 0]));
        // $send = $this->redis->rPush('index:meassage:marketing:sendtask',json_encode(['id' => 15823,'send_time' => 0]));
        // $send = $this->redis->rPush('index:meassage:marketing:sendtask',json_encode(['id' => 15824,'send_time' => 0]));
        // $send = $this->redis->rPush('index:meassage:marketing:sendtask',json_encode(['id' => 15825,'send_time' => 0]));
        // $send = $this->redis->rPush('index:meassage:marketing:sendtask',json_encode(['id' => 15826,'send_time' => 0]));
        //  $send = $this->redis->rPush('index:meassage:marketingtiming:sendtask',json_encode(['id' => 34388,'send_time' => 1588903200]));
        // echo time() -1576290017;die;
        while (true) {
            // echo date('Y-m-d H:i:s');
            // echo "\n";
            $j = 1;

            $rollback      = [];
            $all_log       = [];
            $true_log      = [];
            $push_messages = [];
            while (true) {
                $real_length = 1;
                $send        = $this->redis->lpop('index:meassage:marketingtiming:sendtask');
                // $send = 15753;
                if (empty($send)) {
                    // print_r(date('Y-m-d H:i:s', time()));
                    // echo "\n";
                    // exit('taskId_is_null');
                    sleep(1);
                    break;
                }
                $real_send = json_decode($send, true);
                if ($real_send['send_time'] > time()) {
                    $this->redis->rPush('index:meassage:marketingtiming:sendtask', json_encode($real_send));
                    sleep(1);
                    //  continue;
                } else {
                    $this->redis->rPush('index:meassage:marketing:sendtask', json_encode($real_send));
                }
            }
        }
    }

    public function pushMultimediaMessageSendTask()
    {
        $this->redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        /* 
                                    1321785 1322036
                                    */

        // $task_id = Db::query("SELECT `id` FROM yx_user_send_code_task WHERE  `uid` = 91 AND `create_time` >= 1591272000 ");
        $task_id = Db::query("SELECT `id`,`uid` FROM yx_user_multimedia_message WHERE  `id` = 92221  ");
        foreach ($task_id as $key => $value) {
            $this->redis->rpush("index:meassage:multimediamessage:sendtask", json_encode(['id' => $value['id'], 'deduct' => 0]));
        }
        // $this->redis->rpush("index:meassage:multimediamessage:sendtask", json_encode(['id' => 326561, 'deduct' => 0]));
        // $this->redis->rpush("index:meassage:multimediamessage:sendtask", json_encode(['id' => 287775, 'deduct' => 0]));
        // $this->redis->rpush("index:meassage:multimediamessage:sendtask", json_encode(['id' => 328078, 'deduct' => 0]));
        // $this->redis->rpush("index:meassage:multimediamessage:sendtask", json_encode(['id' => 150394, 'deduct' => 0]));
    }

    //书写彩信任务日志并写入通道
    public function createMultimediaMessageSendTaskLog($type = '')
    {
        $this->redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        // date_default_timezone_set('PRC');
        $redisMessageMarketingSend = 'index:meassage:multimediamessage:sendtask';
        // for ($i=22905; $i < 23085; $i++) { 
        //     $this->redis->rPush('index:meassage:multimediamessage:sendtask', $i);
        // }
        // $this->redis->rPush('index:meassage:multimediamessage:sendtask', 22886);
        // exit();
        // echo time() -1574906657;die;
        // $this->redis->rpush("index:meassage:multimediamessage:sendtask", json_encode(['id' => 137433, 'deduct' => 0]));
        while (true) {
            try {
                $j = 1;
                $rollback      = [];
                $all_log       = [];
                $true_log      = [];
                $push_messages = [];
                $send_task = [];
                while (true) {
                    $real_length = 1;
                    $send        = $this->redis->lpop('index:meassage:multimediamessage:sendtask');
                    // $send = 15745;
                    $real_send = json_decode($send, true);
                    $sendTask = $this->getMultimediaSendTask($real_send['id']);
                    // print_r($sendTask);die;
                    if (empty($sendTask)) {
                        break;
                    }
                    if ($type != 'test') {
                        if ($sendTask['uid'] == 91) {
                            if ((date("H", time()) >= 20 || date("H", time()) < 10)) {
                                $this->redis->rPush('index:meassage:multimediamessage:buffersendtask', $send); //缓存队列
                                continue;
                            }
                        }
                    }
                    $send_task[] = $send;
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
                    // $yidong_channel_id   = 0;
                    $yidong_channel_id   = $sendTask['yidong_channel_id'];
                    // $liantong_channel_id = 0;
                    $liantong_channel_id = $sendTask['liantong_channel_id'];
                    // $dianxin_channel_id  = 0;
                    $dianxin_channel_id  = $sendTask['dianxin_channel_id'];
                    $send_content  = '';
                    // if (file_exists(realpath("") . '/tasklog/multimedia/' . $sendTask['task_no'] . ".txt")) {
                    //     continue;
                    // }
                    // $myfile = fopen(realpath("") . '/tasklog/multimedia/' . $sendTask['task_no'] . ".txt", "w");
                    // if (!empty($sendTask['content'])) {

                    // }

                    if (strpos($sendTask['title'], '生日') !== false) {
                        $real_send['deduct'] = 0;
                    }

                    $real_send['deduct'] = isset($real_send['deduct']) ? $real_send['deduct'] : 0;
                    if (!empty($sendTask['template_id'])) {
                        $yidong_channel_template = Db::query("SELECT * FROM yx_user_multimedia_template_third_report WHERE `channel_id` = '" . $yidong_channel_id . "' AND `template_id` = '" . $sendTask['template_id'] . "'");
                        if (!empty($yidong_channel_template)) {
                            $yidong_channel_template_id = $yidong_channel_template[0]['third_template_id'];
                        }
                        $liantong_channel_template = Db::query("SELECT * FROM yx_user_multimedia_template_third_report WHERE `channel_id` = '" . $liantong_channel_id . "' AND `template_id` = '" . $sendTask['template_id'] . "'");
                        if (!empty($liantong_channel_template)) {
                            $liantong_channel_template_id = $liantong_channel_template[0]['third_template_id'];
                        }
                        $dianxin_channel_template = Db::query("SELECT * FROM yx_user_multimedia_template_third_report WHERE `channel_id` = '" . $dianxin_channel_id . "' AND `template_id` = '" . $sendTask['template_id'] . "'");
                        if (!empty($dianxin_channel_template)) {
                            $dianxin_channel_template_id = $dianxin_channel_template[0]['third_template_id'];
                        }
                    }
                    $mobile_relation = [];
                    if (!empty($sendTask['submit_content'])) { //变量模式
                        $submit_content = [];

                        // $submit_content = explode(';', $sendTask['submit_content']);
                        $submit_content = json_decode($sendTask['submit_content'], true);

                        $sendTask['mobile_content'] = [];

                        foreach ($submit_content as $key => $value) {
                            // $send_value = explode(':', $value);
                            $mobile = '';
                            $mobile = $value['mobile'];
                            $sendTask['mobile_content'][] = $mobile;
                            unset($value['mobile']);
                            $mobile_relation[$mobile] =  $value;
                        }

                        $sendTask['mobile_content'] = join(',', $sendTask['mobile_content']);
                    }
                    /*  print_r($sendTask);
                    print_r($mobile_relation);
                    die; */
                    $mobile_result = [];
                    $yidong_mobile = [];
                    $liantong_mobile = [];
                    $dianxin_mobile = [];
                    $error_mobile = [];
                    $deduct_mobile = [];
                    $mobile_result = $this->SecondMobilesFiltrate($sendTask['mobile_content'], $sendTask['uid'], $real_send['deduct']);
                    // print_r($mobile_result);

                    /*  return ['error_mobile' => $error_mobile, 'yidong_mobile' => $yidong_mobile,'liantong_mobile' => $liantong_mobile, 'dianxin_mobile' => $dianxin_mobile, 'deduct_mobile' => $deduct_mobile]; */
                    /* 实际发送号码 */
                    $yidong_mobile = $mobile_result['yidong_mobile'];
                    $liantong_mobile = $mobile_result['liantong_mobile'];
                    $dianxin_mobile = $mobile_result['dianxin_mobile'];

                    /* 错号和扣量号码 */
                    $error_mobile = $mobile_result['error_mobile'];
                    $deduct_mobile = $mobile_result['deduct_mobile'];

                    /* echo "黑名单:".count($error_mobile);
                    echo "扣量名单:".count($deduct_mobile);
                    echo "移动:".count($yidong_mobile);
                    echo "联通:".count($liantong_mobile);
                    echo "电信:".count($dianxin_mobile);
                    die; */

                    $j = 1;
                    if (!empty($yidong_mobile)) {
                        for ($i = 0; $i < count($yidong_mobile); $i++) {
                            $send_log = [
                                'task_no'      => $sendTask['task_no'],
                                'uid'          => $sendTask['uid'],
                                'source'       => $sendTask['source'],
                                'task_content' => $sendTask['title'],
                                'mobile'       => $yidong_mobile[$i],
                                'channel_id'   => $yidong_channel_id,
                                'send_status'  => 2,
                                'create_time'  => time(),
                                'develop_no' => $sendTask['develop_no'],
                            ];
                            $sendmessage = [
                                'mobile'      => $yidong_mobile[$i],
                                'title'       => $sendTask['title'],
                                'mar_task_id' => $sendTask['id'],
                                'content'     => $sendTask['task_content'],
                                'channel_id'  => $yidong_channel_id,
                                'from'        => 'yx_user_multimedia_message',
                                'send_msg_id'        => $sendTask['send_msg_id'],
                                'uid'          => $sendTask['uid'],
                            ];

                            if (!empty($yidong_channel_template_id)) {
                                $sendmessage['template_id'] = $yidong_channel_template_id;
                                if (!empty($mobile_relation)) {
                                    $sendmessage['variable'] = $mobile_relation[$yidong_mobile[$i]];
                                }
                            }
                            if (!empty($sendTask['develop_no'])) {
                                $sendmessage['develop_code'] = $sendTask['develop_no'];
                            }

                            // fwrite($myfile, $txt);
                            $push_messages[] = $sendmessage;
                            $true_log[]      = $send_log;
                            $j++;
                            if ($j > 100) {
                                Db::startTrans();
                                try {
                                    Db::table('yx_user_multimedia_message_log')->insertAll($true_log);

                                    Db::commit();
                                    foreach ($push_messages as $key => $value) {
                                        $send_channelid = $value['channel_id'];
                                        unset($value['channel_id']);
                                        $res = $this->redis->rpush('index:meassage:code:send' . ":" . $send_channelid, json_encode($value)); //三体营销通道
                                    }
                                    $j = 1;
                                    $push_messages = [];
                                    $true_log = [];
                                } catch (\Exception $e) {
                                    // $this->redis->rPush('index:meassage:business:sendtask', $send);
                                    if (!empty($rollback)) {
                                        foreach ($rollback as $key => $value) {
                                            $this->redis->rPush('index:meassage:marketing:sendtask', $value);
                                        }
                                    }

                                    Db::rollback();
                                    exception($e);
                                }
                            }
                        }
                    }
                    // print_r($liantong_mobile);die;
                    if (!empty($liantong_mobile)) {
                        for ($i = 0; $i < count($liantong_mobile); $i++) {
                            $send_log = [
                                'task_no'      => $sendTask['task_no'],
                                'uid'          => $sendTask['uid'],
                                'source'       => $sendTask['source'],
                                'task_content' => $sendTask['title'],
                                'mobile'       => $liantong_mobile[$i],
                                'channel_id'   => $liantong_channel_id,
                                'send_status'  => 2,
                                'create_time'  => time(),
                                'develop_no' => $sendTask['develop_no'],
                            ];
                            $sendmessage = [
                                'mobile'      => $liantong_mobile[$i],
                                'title'       => $sendTask['title'],
                                'mar_task_id' => $sendTask['id'],
                                'content'     => $sendTask['task_content'],
                                'channel_id'  => $liantong_channel_id,
                                'from'        => 'yx_user_multimedia_message',
                                'send_msg_id'        => $sendTask['send_msg_id'],
                                'uid'          => $sendTask['uid'],
                            ];
                            if (!empty($sendTask['develop_no'])) {
                                $sendmessage['develop_code'] = $sendTask['develop_no'];
                            }
                            if (!empty($liantong_channel_template_id)) {
                                $sendmessage['template_id'] = $liantong_channel_template_id;
                                if (!empty($mobile_relation)) {
                                    $sendmessage['variable'] = $mobile_relation[$liantong_mobile[$i]];
                                }
                            }
                            // fwrite($myfile, $txt);
                            $push_messages[] = $sendmessage;
                            $true_log[]      = $send_log;
                            $j++;
                            if ($j > 100) {
                                Db::startTrans();
                                try {
                                    Db::table('yx_user_multimedia_message_log')->insertAll($true_log);

                                    Db::commit();
                                    foreach ($push_messages as $key => $value) {
                                        $send_channelid = $value['channel_id'];
                                        unset($value['channel_id']);
                                        $res = $this->redis->rpush('index:meassage:code:send' . ":" . $send_channelid, json_encode($value)); //三体营销通道
                                    }
                                    $j = 1;
                                    $push_messages = [];
                                    $true_log = [];
                                } catch (\Exception $e) {
                                    // $this->redis->rPush('index:meassage:business:sendtask', $send);
                                    if (!empty($rollback)) {
                                        foreach ($rollback as $key => $value) {
                                            $this->redis->rPush('index:meassage:marketing:sendtask', $value);
                                        }
                                    }

                                    Db::rollback();
                                    exception($e);
                                }
                            }
                        }
                    }
                    if (!empty($dianxin_mobile)) {
                        for ($i = 0; $i < count($dianxin_mobile); $i++) {
                            $send_log = [
                                'task_no'      => $sendTask['task_no'],
                                'uid'          => $sendTask['uid'],
                                'source'       => $sendTask['source'],
                                'task_content' => $sendTask['title'],
                                'mobile'       => $dianxin_mobile[$i],
                                'channel_id'   => $dianxin_channel_id,
                                'send_status'  => 2,
                                'create_time'  => time(),
                                'develop_no' => $sendTask['develop_no'],
                            ];
                            $sendmessage = [
                                'mobile'      => $dianxin_mobile[$i],
                                'title'       => $sendTask['title'],
                                'mar_task_id' => $sendTask['id'],
                                'content'     => $sendTask['task_content'],
                                'channel_id'  => $dianxin_channel_id,
                                'from'        => 'yx_user_multimedia_message',
                                'send_msg_id'        => $sendTask['send_msg_id'],
                                'uid'          => $sendTask['uid'],
                            ];
                            if (!empty($sendTask['develop_no'])) {
                                $sendmessage['develop_code'] = $sendTask['develop_no'];
                            }
                            if (!empty($dianxin_channel_template_id)) {
                                $sendmessage['template_id'] = $dianxin_channel_template_id;
                                if (!empty($mobile_relation)) {
                                    $sendmessage['variable'] = $mobile_relation[$dianxin_mobile[$i]];
                                }
                            }
                            // fwrite($myfile, $txt);
                            $push_messages[] = $sendmessage;
                            $true_log[]      = $send_log;
                            $j++;
                            if ($j > 100) {
                                Db::startTrans();
                                try {
                                    Db::table('yx_user_multimedia_message_log')->insertAll($true_log);

                                    Db::commit();
                                    foreach ($push_messages as $key => $value) {
                                        $send_channelid = $value['channel_id'];
                                        unset($value['channel_id']);
                                        $res = $this->redis->rpush('index:meassage:code:send' . ":" . $send_channelid, json_encode($value)); //三体营销通道
                                    }
                                    $j = 1;
                                    $push_messages = [];
                                    $true_log = [];
                                } catch (\Exception $e) {
                                    // $this->redis->rPush('index:meassage:business:sendtask', $send);
                                    if (!empty($rollback)) {
                                        foreach ($rollback as $key => $value) {
                                            $this->redis->rPush('index:meassage:marketing:sendtask', $value);
                                        }
                                    }

                                    Db::rollback();
                                    exception($e);
                                }
                            }
                        }
                    }
                    if (!empty($true_log)) {
                        Db::startTrans();
                        try {
                            Db::table('yx_user_multimedia_message_log')->insertAll($true_log);

                            Db::commit();
                            foreach ($push_messages as $key => $value) {
                                $send_channelid = $value['channel_id'];
                                unset($value['channel_id']);
                                $res = $this->redis->rpush('index:meassage:code:send' . ":" . $send_channelid, json_encode($value)); //三体营销通道
                            }
                            $j = 1;
                            $push_messages = [];
                            $true_log = [];
                        } catch (\Exception $e) {
                            // $this->redis->rPush('index:meassage:business:sendtask', $send);
                            if (!empty($rollback)) {
                                foreach ($rollback as $key => $value) {
                                    $this->redis->rPush('index:meassage:marketing:sendtask', $value);
                                }
                            }

                            Db::rollback();
                            exception($e);
                        }
                    }

                    /* 错号及扣量 */
                    // $error_mobile = $mobile_result['error_mobile'];
                    // $deduct_mobile = $mobile_result['deduct_mobile'];
                    if (!empty($deduct_mobile)) {
                        for ($i = 0; $i < count($deduct_mobile); $i++) {
                            $send_log = [
                                'task_no'        => $sendTask['task_no'],
                                'uid'            => $sendTask['uid'],
                                'task_content'        => $sendTask['title'],
                                'mobile'         => $deduct_mobile[$i],
                                'send_status'    => 4,
                                'create_time'    => time(),
                                'status_message' => 'DELIVRD',
                                'real_message'   => 'DEDUCT:1',
                                'develop_no' => $sendTask['develop_no'],
                            ];
                            $all_log[] = $send_log;
                            $sendmessage = [
                                'task_no' => $sendTask['task_no'],
                                'mar_task_id' => $sendTask['id'],
                                'uid'            => $sendTask['uid'],
                                'msg_id'            => $sendTask['send_msg_id'],
                                'Stat' => 'DELIVRD',
                                'mobile' =>  $deduct_mobile[$i],
                                'content'   => $sendTask['task_content'],
                                'from'   => 'yx_user_multimedia_message',
                                'Submit_time'   => time(),
                                'send_msg_id'        => $sendTask['send_msg_id'],
                            ];
                            $push_messages[] = $sendmessage;
                            $j++;
                            if ($j > 100) {
                                Db::startTrans();
                                try {
                                    Db::table('yx_user_multimedia_message_log')->insertAll($all_log);

                                    Db::commit();
                                    foreach ($push_messages as $key => $value) {
                                        $res = $this->redis->rpush('index:message:code:deduct:deliver', json_encode($value)); //三体营销通道
                                    }
                                    $j = 1;
                                    $push_messages = [];
                                    $all_log = [];
                                } catch (\Exception $e) {
                                    // $this->redis->rPush('index:meassage:business:sendtask', $send);
                                    if (!empty($rollback)) {
                                        foreach ($rollback as $key => $value) {
                                            $this->redis->rPush('index:meassage:marketing:sendtask', $value);
                                        }
                                    }

                                    Db::rollback();
                                    exception($e);
                                }
                            }
                        }
                    }
                    if (!empty($error_mobile)) {
                        for ($i = 0; $i < count($error_mobile); $i++) {
                            $send_log = [
                                'task_no'        => $sendTask['task_no'],
                                'uid'            => $sendTask['uid'],
                                'task_content'        => $sendTask['title'],
                                'mobile'         => $error_mobile[$i],
                                'send_status'    => 4,
                                'create_time'    => time(),
                                'status_message' => 'DB:0101',
                                'real_message'   => 'ERROR:1',
                                'develop_no' => $sendTask['develop_no'],
                            ];
                            $all_log[] = $send_log;
                            $sendmessage = [
                                'task_no' => $sendTask['task_no'],
                                'mar_task_id' => $sendTask['id'],
                                'uid'            => $sendTask['uid'],
                                'msg_id'            => $sendTask['send_msg_id'],
                                'Stat' => 'DB:0101',
                                'mobile' =>  $error_mobile[$i],
                                'content'   => $sendTask['task_content'],
                                'from'   => 'yx_user_multimedia_message',
                                'Submit_time'   => time(),
                                'send_msg_id'        => $sendTask['send_msg_id'],
                            ];
                            $push_messages[] = $sendmessage;
                            $j++;
                            if ($j > 100) {
                                Db::startTrans();
                                try {
                                    Db::table('yx_user_multimedia_message_log')->insertAll($all_log);

                                    Db::commit();
                                    foreach ($push_messages as $key => $value) {
                                        $res = $this->redis->rpush('index:message:code:deduct:deliver', json_encode($value)); //三体营销通道
                                    }
                                    $j = 1;
                                    $push_messages = [];
                                    $all_log = [];
                                } catch (\Exception $e) {
                                    // $this->redis->rPush('index:meassage:business:sendtask', $send);
                                    if (!empty($rollback)) {
                                        foreach ($rollback as $key => $value) {
                                            $this->redis->rPush('index:meassage:marketing:sendtask', $value);
                                        }
                                    }

                                    Db::rollback();
                                    exception($e);
                                }
                            }
                        }
                    }
                    if (!empty($all_log)) {
                        Db::startTrans();
                        try {
                            Db::table('yx_user_multimedia_message_log')->insertAll($all_log);

                            Db::commit();
                            foreach ($push_messages as $key => $value) {
                                $res = $this->redis->rpush('index:message:code:deduct:deliver', json_encode($value)); //三体营销通道
                            }
                            $j = 1;
                            $push_messages = [];
                            $all_log = [];
                        } catch (\Exception $e) {
                            // $this->redis->rPush('index:meassage:business:sendtask', $send);
                            if (!empty($rollback)) {
                                foreach ($rollback as $key => $value) {
                                    $this->redis->rPush('index:meassage:marketing:sendtask', $value);
                                }
                            }

                            Db::rollback();
                            exception($e);
                        }
                    }

                    // for ($i = 0; $i < count($mobilesend); $i++) {
                    //     $send_log = [];
                    //     if (checkMobile(trim($mobilesend[$i])) == true) {
                    //         $whitelist = Db::query("SELECT `id` FROM yx_whitelist WHERE `mobile` = " . $mobilesend[$i]);
                    //         if (isset($real_send['deduct']) && $real_send['deduct'] > 0  && empty($whitelist)) {
                    //             $num = 0;
                    //             $num = mt_rand(0, 10000);
                    //             if ($this->mobileCheck($mobilesend[$i]) == true) { //空号

                    //                 $prefix = substr(trim($mobilesend[$i]), 0, 7);
                    //                 $res    = Db::query("SELECT `source`,`province_id`,`province` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                    //                 $newres = array_shift($res);
                    //                 if ($newres) {
                    //                     if ($newres['source'] == 1) {
                    //                         $channel_id = $yidong_channel_id;
                    //                     } elseif ($newres['source'] == 2) {
                    //                         $channel_id = $liantong_channel_id;
                    //                     } elseif ($newres['source'] == 3) {
                    //                         $channel_id = $dianxin_channel_id;
                    //                     }
                    //                 }
                    //                 $send_log = [
                    //                     'task_no'      => $sendTask['task_no'],
                    //                     'uid'          => $sendTask['uid'],
                    //                     'source'       => $sendTask['source'],
                    //                     'task_content' => $sendTask['title'],
                    //                     'mobile'       => $mobilesend[$i],
                    //                     'channel_id'   => $channel_id,
                    //                     'send_status'  => 2,
                    //                     'create_time'  => time(),
                    //                 ];
                    //                 $sendmessage = [
                    //                     'mobile'      => $mobilesend[$i],
                    //                     'title'       => $sendTask['title'],
                    //                     'mar_task_id' => $sendTask['id'],
                    //                     'content'     => $sendTask['task_content'],
                    //                     'channel_id'  => $channel_id,
                    //                     'from'        => 'yx_user_multimedia_message',
                    //                 ];
                    //                 if (!empty($sendTask['develop_no'])) {
                    //                     $sendmessage['develop_code'] = $sendTask['develop_no'];
                    //                 }

                    //                 // fwrite($myfile, $txt);
                    //                 $push_messages[] = $sendmessage;
                    //                 $true_log[]      = $send_log;

                    //                 /* 
                    //                 1321785 1322036
                    //                 */
                    //             } else {
                    //                 if ($num <= $real_send['deduct'] * 100) {

                    //                     $send_log = [
                    //                         'task_no'        => $sendTask['task_no'],
                    //                         'uid'            => $sendTask['uid'],
                    //                         'task_content'        => $sendTask['title'],
                    //                         'mobile'         => $mobilesend[$i],
                    //                         'send_status'    => 4,
                    //                         'create_time'    => time(),
                    //                         'status_message' => 'DELIVRD',
                    //                         'real_message'   => 'DEDUCT:1',
                    //                     ];
                    //                     $all_log[] = $send_log;
                    //                     $this->redis->rpush('index:message:code:deduct:deliver', json_encode([
                    //                         'task_no' => $sendTask['task_no'],
                    //                         'mar_task_id' => $sendTask['id'],
                    //                         'uid'            => $sendTask['uid'],
                    //                         'msg_id'            => $sendTask['send_msg_id'],
                    //                         'Stat' => 'DELIVRD',
                    //                         'mobile' =>  $mobilesend[$i],
                    //                         'content'   => $sendTask['task_content'],
                    //                         'from'   => 'yx_user_multimedia_message',
                    //                         'Submit_time'   => time(),
                    //                     ]));
                    //                 } else {
                    //                     $prefix = substr(trim($mobilesend[$i]), 0, 7);
                    //                     $res    = Db::query("SELECT `source`,`province_id`,`province` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                    //                     $newres = array_shift($res);
                    //                     if ($newres) {
                    //                         if ($newres['source'] == 1) {
                    //                             $channel_id = $yidong_channel_id;
                    //                         } elseif ($newres['source'] == 2) {
                    //                             $channel_id = $liantong_channel_id;
                    //                         } elseif ($newres['source'] == 3) {
                    //                             $channel_id = $dianxin_channel_id;
                    //                         }
                    //                     }
                    //                     $send_log = [
                    //                         'task_no'      => $sendTask['task_no'],
                    //                         'uid'          => $sendTask['uid'],
                    //                         'source'       => $sendTask['source'],
                    //                         'task_content' => $sendTask['title'],
                    //                         'mobile'       => $mobilesend[$i],
                    //                         'channel_id'   => $channel_id,
                    //                         'send_status'  => 2,
                    //                         'create_time'  => time(),
                    //                     ];
                    //                     $sendmessage = [
                    //                         'mobile'      => $mobilesend[$i],
                    //                         'title'       => $sendTask['title'],
                    //                         'mar_task_id' => $sendTask['id'],
                    //                         'content'     => $sendTask['task_content'],
                    //                         'channel_id'  => $channel_id,
                    //                     ];
                    //                     if (!empty($sendTask['develop_no'])) {
                    //                         $sendmessage['develop_code'] = $sendTask['develop_no'];
                    //                     }

                    //                     // fwrite($myfile, $txt);
                    //                     $push_messages[] = $sendmessage;
                    //                     $true_log[]      = $send_log;
                    //                 }
                    //             }
                    //         } else {
                    //             $prefix = substr(trim($mobilesend[$i]), 0, 7);
                    //             $res    = Db::query("SELECT `source`,`province_id`,`province` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                    //             $newres = array_shift($res);
                    //             if ($newres) {
                    //                 if ($newres['source'] == 1) {
                    //                     $channel_id = $yidong_channel_id;
                    //                 } elseif ($newres['source'] == 2) {
                    //                     $channel_id = $liantong_channel_id;
                    //                 } elseif ($newres['source'] == 3) {
                    //                     $channel_id = $dianxin_channel_id;
                    //                 }
                    //             }
                    //             $send_log = [
                    //                 'task_no'      => $sendTask['task_no'],
                    //                 'uid'          => $sendTask['uid'],
                    //                 'source'       => $sendTask['source'],
                    //                 'task_content' => $sendTask['title'],
                    //                 'mobile'       => $mobilesend[$i],
                    //                 'channel_id'   => $channel_id,
                    //                 'send_status'  => 2,
                    //                 'create_time'  => time(),
                    //             ];
                    //             $sendmessage = [
                    //                 'mobile'      => $mobilesend[$i],
                    //                 'title'       => $sendTask['title'],
                    //                 'mar_task_id' => $sendTask['id'],
                    //                 'content'     => $sendTask['task_content'],
                    //                 'channel_id'  => $channel_id,
                    //             ];

                    //             // $txt = json_encode($send_log) . "\n";
                    //             // fwrite($myfile, $txt);
                    //             // $res = $this->redis->rpush($redisMessageMarketingSend . ":" . $channel_id, json_encode($sendmessage)); //三体营销通道
                    //             $push_messages[] = $sendmessage;
                    //             $true_log[]      = $send_log;
                    //         }
                    //     } else {
                    //         $send_log = [
                    //             'task_no'        => $sendTask['task_no'],
                    //             'uid'            => $sendTask['uid'],
                    //             'task_content'        => $sendTask['title'],
                    //             'mobile'         => $mobilesend[$i],
                    //             'send_status'    => 4,
                    //             'create_time'    => time(),
                    //             'status_message' => 'DB:0101', //无效号码
                    //             'real_message'   => 'DB:0101',
                    //         ];
                    //         $all_log[] = $send_log;
                    //         // $txt = json_encode($send_log) . "\n";
                    //         // fwrite($myfile, $txt);
                    //     }

                    //     $j++;
                    //     if ($j > 100) {
                    //         $j = 1;
                    //         Db::startTrans();
                    //         try {
                    //             if (!empty($true_log)) {
                    //                 Db::table('yx_user_multimedia_message_log')->insertAll($true_log);
                    //             }
                    //             if (!empty($all_log)) {
                    //                 Db::table('yx_user_multimedia_message_log')->insertAll($all_log);
                    //             }
                    //             Db::commit();
                    //             if (!empty($push_messages)) {
                    //                 foreach ($push_messages as $key => $value) {
                    //                     $send_channelid = $value['channel_id'];
                    //                     unset($value['channel_id']);
                    //                     $res = $this->redis->rpush('index:meassage:code:send' . ":" . $send_channelid, json_encode($value)); //三体营销通道
                    //                 }
                    //             }
                    //         } catch (\Exception $e) {
                    //             // $this->redis->rPush('index:meassage:business:sendtask', $send);
                    //             if (!empty($rollback)) {
                    //                 foreach ($rollback as $key => $value) {
                    //                     $this->redis->rPush('index:meassage:multimediamessage:sendtask', $value);
                    //                 }
                    //             }

                    //             Db::rollback();
                    //             exception($e);
                    //         }
                    //         unset($all_log);
                    //         unset($true_log);
                    //         unset($push_messages);
                    //         // echo time() . "\n";
                    //         unset($rollback);
                    //     }
                    // }

                    /*   Db::startTrans();
                try {
                    Db::table('yx_user_multimedia_message')->where('id', $sendTask['id'])->update(['real_num' => $real_num, 'send_status' => 3, 'log_path' => realpath("") . '/tasklog/multimedia/' . $sendTask['task_no'] . ".txt"]);
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
                } */
                    // foreach ($mobilesend as $key => $kvalue) {
                    //     if (in_array($channel_id, [2, 6, 7, 8])) {
                    //         // $getSendTaskSql = "select source,province_id,province from yx_number_source where `mobile` = '".$prefix."' LIMIT 1";
                    //     }
                    // }
                    // exit("SUCCESS");
                }


                /*   Db::startTrans();
                try {
                    if (!empty($true_log)) {
                        Db::table('yx_user_multimedia_message_log')->insertAll($true_log);
                    }
                    if (!empty($all_log)) {
                        Db::table('yx_user_multimedia_message_log')->insertAll($all_log);
                    }
                    Db::commit();
                    if (!empty($push_messages)) {
                        foreach ($push_messages as $key => $value) {
                            $send_channelid = $value['channel_id'];
                            unset($value['channel_id']);
                            $res = $this->redis->rpush('index:meassage:code:send' . ":" . $send_channelid, json_encode($value)); //三体营销通道
                        }
                    }
                } catch (\Exception $e) {
                    // $this->redis->rPush('index:meassage:business:sendtask', $send);
                    if (!empty($rollback)) {
                        foreach ($rollback as $key => $value) {
                            $this->redis->rPush('index:meassage:multimediamessage:sendtask', $value);
                        }
                    }
                    Db::rollback();
                    exception($e);
                } */
                unset($all_log);
                unset($true_log);
                unset($push_messages);
                unset($rollback);

                /* Db::startTrans();
            try {
                Db::table('yx_user_multimedia_message')->where(['id',' in', join(',',$send_task)])->update(['send_status' => 3]);
               
                Db::commit();
               
            } catch (\Exception $e) {
                // $this->redis->rPush('index:meassage:business:sendtask', $send);
                if (!empty($rollback)) {
                    foreach ($rollback as $key => $value) {
                        $this->redis->rPush('index:meassage:multimediamessage:sendtask', $value);
                    }
                }
                Db::rollback();
                exception($e);
            } */
                sleep(1);
            } catch (\Exception $th) {
                //throw $th;
                /*  $this->redis->rpush('index:meassage:code:send' . ":" . 85, json_encode([
                    'mobile'  => 15201926171,
                    'content' => "【钰晰科技】创建彩信任务日志功能出现错误，请查看并解决！！！时间" . date("Y-m-d H:i:s", time())
                ])); //三体营销通道 */
                $this->writeToRobot('cmppcreatecodetask', $th, 'createMultimediaMessageSendTaskLog');
                $log_path = realpath("") . "/error/createMultimediaMessageSendTaskLog.log";
                $myfile = fopen($log_path, 'a+');
                fwrite($myfile, date('Y-m-d H:i:s', time()) . "\n");
                fwrite($myfile, $th . "\n");
                fclose($myfile);
                exception($th);
            }

            sleep(10);
            $j = 1;
        }
    }

    public function pushSupMessageSendTask()
    {
        $this->redis = Phpredis::getConn();
        $taskid = [12];
        foreach ($taskid as $key => $value) {
            $this->redis->rpush("index:meassage:supmessage:sendtask", json_encode(['id' => $value, 'deduct' => 0]));
        }
    }

    public function createSupMessageTaskLog($type)
    {
        $this->redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        while (true) {
            try {
                $real_length = 1;
                $send        = $this->redis->lpop('index:meassage:supmessage:sendtask');
                // $send = 15745;
                $real_send = json_decode($send, true);
                $sendTask = $this->getSupMessageSendTask($real_send['id']);
                // print_r($sendTask);die;
                if (empty($sendTask)) {
                    sleep(1);
                    continue;
                }
                if ($type != 'test') {
                    if ($sendTask['uid'] == 91) {
                        if ((date("H", time()) >= 20 || date("H", time()) < 10)) {
                            $this->redis->rPush('index:meassage:supmessage:buffersendtask', $send); //缓存队列
                            continue;
                        }
                    }
                }
                $send_task[] = $send;
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
                $yidong_channel_id   = 0;
                $yidong_channel_id   = $sendTask['yidong_channel_id'];
                $liantong_channel_id = 0;
                $liantong_channel_id = $sendTask['liantong_channel_id'];
                $dianxin_channel_id  = 0;
                $dianxin_channel_id  = $sendTask['dianxin_channel_id'];
                $send_content  = '';

                if (strpos($sendTask['title'], '生日') !== false) {
                    $real_send['deduct'] = 0;
                }

                $real_send['deduct'] = isset($real_send['deduct']) ? $real_send['deduct'] : 0;
                if (!empty($sendTask['template_id'])) {
                    $yidong_channel_template = Db::query("SELECT * FROM yx_user_sup_message_template_third_report WHERE `channel_id` = '" . $yidong_channel_id . "' AND `template_id` = '" . $sendTask['template_id'] . "'");
                    if (!empty($yidong_channel_template)) {
                        $yidong_channel_template_id = $yidong_channel_template[0]['third_template_id'];
                    }
                    $liantong_channel_template = Db::query("SELECT * FROM yx_user_sup_message_template_third_report WHERE `channel_id` = '" . $liantong_channel_id . "' AND `template_id` = '" . $sendTask['template_id'] . "'");
                    if (!empty($liantong_channel_template)) {
                        $liantong_channel_template_id = $liantong_channel_template[0]['third_template_id'];
                    }
                    $dianxin_channel_template = Db::query("SELECT * FROM yx_user_sup_message_template_third_report WHERE `channel_id` = '" . $dianxin_channel_id . "' AND `template_id` = '" . $sendTask['template_id'] . "'");
                    if (!empty($dianxin_channel_template)) {
                        $dianxin_channel_template_id = $dianxin_channel_template[0]['third_template_id'];
                    }
                }
                $mobile_relation = [];
                if (!empty($sendTask['submit_content'])) { //变量模式
                    $submit_content = [];

                    // $submit_content = explode(';', $sendTask['submit_content']);
                    $submit_content = json_decode($sendTask['submit_content'], true);

                    $sendTask['mobile_content'] = [];

                    foreach ($submit_content as $key => $value) {
                        // $send_value = explode(':', $value);
                        $mobile = '';
                        $mobile = $value['mobile'];
                        $sendTask['mobile_content'][] = $mobile;
                        unset($value['mobile']);
                        $mobile_relation[$mobile] =  $value;
                    }

                    $sendTask['mobile_content'] = join(',', $sendTask['mobile_content']);
                }
                /*  print_r($sendTask);
                    print_r($mobile_relation);
                    die; */
                $mobile_result = [];
                $yidong_mobile = [];
                $liantong_mobile = [];
                $dianxin_mobile = [];
                $error_mobile = [];
                $deduct_mobile = [];
                $mobile_result = $this->SecondMobilesFiltrate($sendTask['mobile_content'], $sendTask['uid'], $real_send['deduct']);
                // print_r($sendTask['template_id']);die;

                /*  return ['error_mobile' => $error_mobile, 'yidong_mobile' => $yidong_mobile,'liantong_mobile' => $liantong_mobile, 'dianxin_mobile' => $dianxin_mobile, 'deduct_mobile' => $deduct_mobile]; */
                /* 实际发送号码 */
                $yidong_mobile = $mobile_result['yidong_mobile'];
                $liantong_mobile = $mobile_result['liantong_mobile'];
                $dianxin_mobile = $mobile_result['dianxin_mobile'];

                /* 错号和扣量号码 */
                $error_mobile = $mobile_result['error_mobile'];
                $deduct_mobile = $mobile_result['deduct_mobile'];

                /* echo "黑名单:".count($error_mobile);
                    echo "扣量名单:".count($deduct_mobile);
                    echo "移动:".count($yidong_mobile);
                    echo "联通:".count($liantong_mobile);
                    echo "电信:".count($dianxin_mobile);
                    die; */

                $j = 1;
                if (!empty($yidong_mobile)) {
                    for ($i = 0; $i < count($yidong_mobile); $i++) {
                        $send_log = [
                            'task_no'      => $sendTask['task_no'],
                            'task_id'      => $sendTask['id'],
                            'uid'          => $sendTask['uid'],
                            'source'       => $sendTask['source'],
                            'title' => $sendTask['title'],
                            'mobile'       => $yidong_mobile[$i],
                            'channel_id'   => $yidong_channel_id,
                            'send_status'  => 2,
                            'create_time'  => time(),
                            'develop_no' => $sendTask['develop_no'],
                            'template_id' => $sendTask['template_id']
                        ];
                        $sendmessage = [
                            'mobile'      => $yidong_mobile[$i],
                            'mar_task_id' => $sendTask['id'],
                            'channel_id'  => $yidong_channel_id,
                            'from'        => 'yx_user_multimedia_message',
                            'send_msg_id'        => $sendTask['send_msg_id'],
                            'uid'          => $sendTask['uid'],
                        ];

                        if (!empty($yidong_channel_template_id)) {
                            $sendmessage['template_id'] = $yidong_channel_template_id;
                            if (!empty($mobile_relation)) {
                                $sendmessage['variable'] = $mobile_relation[$yidong_mobile[$i]];
                            }
                        }
                        if (!empty($sendTask['develop_no'])) {
                            $sendmessage['develop_code'] = $sendTask['develop_no'];
                        }

                        // fwrite($myfile, $txt);
                        $push_messages[] = $sendmessage;
                        $true_log[]      = $send_log;
                        $j++;
                        if ($j > 100) {
                            Db::startTrans();
                            try {
                                Db::table('yx_user_sup_message_log')->insertAll($true_log);

                                Db::commit();
                                foreach ($push_messages as $key => $value) {
                                    $send_channelid = $value['channel_id'];
                                    unset($value['channel_id']);
                                    $res = $this->redis->rpush('index:meassage:code:send' . ":" . $send_channelid, json_encode($value)); //三体营销通道
                                }
                                $j = 1;
                                $push_messages = [];
                                $true_log = [];
                            } catch (\Exception $e) {

                                Db::rollback();
                                exception($e);
                            }
                        }
                    }
                }
                // print_r($liantong_mobile);die;
                if (!empty($liantong_mobile)) {
                    for ($i = 0; $i < count($liantong_mobile); $i++) {
                        $send_log = [
                            'task_no'      => $sendTask['task_no'],
                            'task_id'      => $sendTask['id'],
                            'uid'          => $sendTask['uid'],
                            'source'       => $sendTask['source'],
                            'title' => $sendTask['title'],
                            'mobile'       => $liantong_mobile[$i],
                            'channel_id'   => $liantong_channel_id,
                            'send_status'  => 2,
                            'create_time'  => time(),
                            'develop_no' => $sendTask['develop_no'],
                            'template_id' => $sendTask['template_id']
                        ];
                        $sendmessage = [
                            'mobile'      => $liantong_mobile[$i],
                            'mar_task_id' => $sendTask['id'],
                            'channel_id'  => $liantong_channel_id,
                            'from'        => 'yx_user_multimedia_message',
                            'send_msg_id'        => $sendTask['send_msg_id'],
                            'uid'          => $sendTask['uid'],
                        ];
                        if (!empty($sendTask['develop_no'])) {
                            $sendmessage['develop_code'] = $sendTask['develop_no'];
                        }
                        if (!empty($liantong_channel_template_id)) {
                            $sendmessage['template_id'] = $liantong_channel_template_id;
                            if (!empty($mobile_relation)) {
                                $sendmessage['variable'] = $mobile_relation[$liantong_mobile[$i]];
                            }
                        }
                        // fwrite($myfile, $txt);
                        $push_messages[] = $sendmessage;
                        $true_log[]      = $send_log;
                        $j++;
                        if ($j > 100) {
                            Db::startTrans();
                            try {
                                Db::table('yx_user_sup_message_log')->insertAll($true_log);

                                Db::commit();
                                foreach ($push_messages as $key => $value) {
                                    $send_channelid = $value['channel_id'];
                                    unset($value['channel_id']);
                                    $res = $this->redis->rpush('index:meassage:code:send' . ":" . $send_channelid, json_encode($value)); //三体营销通道
                                }
                                $j = 1;
                                $push_messages = [];
                                $true_log = [];
                            } catch (\Exception $e) {

                                Db::rollback();
                                exception($e);
                            }
                        }
                    }
                }
                if (!empty($dianxin_mobile)) {
                    for ($i = 0; $i < count($dianxin_mobile); $i++) {
                        $send_log = [
                            'task_no'      => $sendTask['task_no'],
                            'task_id'      => $sendTask['id'],
                            'uid'          => $sendTask['uid'],
                            'source'       => $sendTask['source'],
                            'title' => $sendTask['title'],
                            'mobile'       => $dianxin_mobile[$i],
                            'channel_id'   => $dianxin_channel_id,
                            'send_status'  => 2,
                            'create_time'  => time(),
                            'develop_no' => $sendTask['develop_no'],
                            'template_id' => $sendTask['template_id']
                        ];
                        $sendmessage = [
                            'mobile'      => $dianxin_mobile[$i],
                            'mar_task_id' => $sendTask['id'],
                            'channel_id'  => $dianxin_channel_id,
                            'from'        => 'yx_user_multimedia_message',
                            'send_msg_id'        => $sendTask['send_msg_id'],
                            'uid'          => $sendTask['uid'],
                        ];
                        if (!empty($sendTask['develop_no'])) {
                            $sendmessage['develop_code'] = $sendTask['develop_no'];
                        }
                        if (!empty($dianxin_channel_template_id)) {
                            $sendmessage['template_id'] = $dianxin_channel_template_id;
                            if (!empty($mobile_relation)) {
                                $sendmessage['variable'] = $mobile_relation[$dianxin_mobile[$i]];
                            }
                        }
                        // fwrite($myfile, $txt);
                        $push_messages[] = $sendmessage;
                        $true_log[]      = $send_log;
                        $j++;
                        if ($j > 100) {
                            Db::startTrans();
                            try {
                                Db::table('yx_user_sup_message_log')->insertAll($true_log);

                                Db::commit();
                                foreach ($push_messages as $key => $value) {
                                    $send_channelid = $value['channel_id'];
                                    unset($value['channel_id']);
                                    $res = $this->redis->rpush('index:meassage:code:send' . ":" . $send_channelid, json_encode($value)); //三体营销通道
                                }
                                $j = 1;
                                $push_messages = [];
                                $true_log = [];
                            } catch (\Exception $e) {

                                Db::rollback();
                                exception($e);
                            }
                        }
                    }
                }
                // print_r($push_messages);die;
                if (!empty($true_log)) {
                    Db::startTrans();
                    try {
                        Db::table('yx_user_sup_message_log')->insertAll($true_log);

                        Db::commit();
                        foreach ($push_messages as $key => $value) {
                            $send_channelid = $value['channel_id'];
                            unset($value['channel_id']);
                            $res = $this->redis->rpush('index:meassage:code:send' . ":" . $send_channelid, json_encode($value)); //三体营销通道
                        }
                        $j = 1;
                        $push_messages = [];
                        $true_log = [];
                    } catch (\Exception $e) {

                        Db::rollback();
                        exception($e);
                    }
                }

                /* 错号及扣量 */
                // $error_mobile = $mobile_result['error_mobile'];
                // $deduct_mobile = $mobile_result['deduct_mobile'];
                if (!empty($deduct_mobile)) {
                    for ($i = 0; $i < count($deduct_mobile); $i++) {
                        $send_log = [
                            'task_no'      => $sendTask['task_no'],
                            'task_id'      => $sendTask['id'],
                            'uid'          => $sendTask['uid'],
                            'source'       => $sendTask['source'],
                            'title' => $sendTask['title'],
                            'mobile'         => $deduct_mobile[$i],
                            'send_status'    => 4,
                            'create_time'    => time(),
                            'status_message' => 'DELIVRD',
                            'real_message'   => 'DEDUCT:1',
                            'develop_no' => $sendTask['develop_no'],
                            'template_id' => $sendTask['template_id']
                        ];
                        $all_log[] = $send_log;
                        $sendmessage = [
                            'task_no' => $sendTask['task_no'],
                            'mar_task_id' => $sendTask['id'],
                            'uid'            => $sendTask['uid'],
                            'msg_id'            => $sendTask['send_msg_id'],
                            'Stat' => 'DELIVRD',
                            'mobile' =>  $deduct_mobile[$i],
                            'content'   => $sendTask['task_content'],
                            'from'   => 'yx_user_multimedia_message',
                            'Submit_time'   => time(),
                            'send_msg_id'        => $sendTask['send_msg_id'],
                        ];
                        $push_messages[] = $sendmessage;
                        $j++;
                        if ($j > 100) {
                            Db::startTrans();
                            try {
                                Db::table('yx_user_sup_message_log')->insertAll($true_log);

                                Db::commit();
                                foreach ($push_messages as $key => $value) {
                                    $res = $this->redis->rpush('index:message:code:deduct:deliver', json_encode($value)); //三体营销通道
                                }
                                $j = 1;
                                $push_messages = [];
                                $all_log = [];
                            } catch (\Exception $e) {

                                Db::rollback();
                                exception($e);
                            }
                        }
                    }
                }
                if (!empty($error_mobile)) {
                    for ($i = 0; $i < count($error_mobile); $i++) {
                        $send_log = [
                            'task_no'      => $sendTask['task_no'],
                            'task_id'      => $sendTask['id'],
                            'uid'          => $sendTask['uid'],
                            'source'       => $sendTask['source'],
                            'title' => $sendTask['title'],
                            'mobile'         => $error_mobile[$i],
                            'send_status'    => 4,
                            'create_time'    => time(),
                            'status_message' => 'DB:0101',
                            'real_message'   => 'ERROR:1',
                            'develop_no' => $sendTask['develop_no'],
                            'template_id' => $sendTask['template_id']
                        ];
                        $all_log[] = $send_log;
                        $sendmessage = [
                            'task_no' => $sendTask['task_no'],
                            'mar_task_id' => $sendTask['id'],
                            'uid'            => $sendTask['uid'],
                            'msg_id'            => $sendTask['send_msg_id'],
                            'Stat' => 'DB:0101',
                            'mobile' =>  $error_mobile[$i],
                            'content'   => $sendTask['task_content'],
                            'from'   => 'yx_user_multimedia_message',
                            'Submit_time'   => time(),
                            'send_msg_id'        => $sendTask['send_msg_id'],
                        ];
                        $push_messages[] = $sendmessage;
                        $j++;
                        if ($j > 100) {
                            Db::startTrans();
                            try {
                                Db::table('yx_user_multimedia_message_log')->insertAll($true_log);

                                Db::commit();
                                foreach ($push_messages as $key => $value) {
                                    $res = $this->redis->rpush('index:message:code:deduct:deliver', json_encode($value)); //三体营销通道
                                }
                                $j = 1;
                                $push_messages = [];
                                $all_log = [];
                            } catch (\Exception $e) {
                                // $this->redis->rPush('index:meassage:business:sendtask', $send);
                                Db::rollback();
                                exception($e);
                            }
                        }
                    }
                }
                if (!empty($all_log)) {
                    Db::startTrans();
                    try {
                        Db::table('yx_user_multimedia_message_log')->insertAll($true_log);

                        Db::commit();
                        foreach ($push_messages as $key => $value) {
                            $res = $this->redis->rpush('index:message:code:deduct:deliver', json_encode($value)); //三体营销通道
                        }
                        $j = 1;
                        $push_messages = [];
                        $all_log = [];
                    } catch (\Exception $e) {
                        // $this->redis->rPush('index:meassage:business:sendtask', $send);

                        Db::rollback();
                        exception($e);
                    }
                }
            } catch (\Exception $th) {
                //throw $th;

                $this->writeToRobot('cmppcreatecodetask', $th, 'createSupMessageTskLog');
                $log_path = realpath("") . "/error/createSupMessageTskLog.log";
                $myfile = fopen($log_path, 'a+');
                fwrite($myfile, date('Y-m-d H:i:s', time()) . "\n");
                fwrite($myfile, $th . "\n");
                fclose($myfile);
                exception($th);
            }
        }
    }



    /*号码清洗 */
    public function SecondMobilesFiltrate($mobile, $uid, $deduct, $is_cool_city = 0)
    {
        //is_cool_city 是否只扣冷门城市 默认 1否 2 是
        $mobileredis = PhpredisNew::getConn();
        $this->redis = Phpredis::getConn();
        try {
            // $deduct = 0;
            $error_mobile = []; //错号或者黑名单
            $real_send_mobile = []; //实际发送号码
            $deduct_mobile = []; //扣量号码
            $true_mobile = []; //实号号码
            $yidong_mobile = []; //移动分区号码
            $liantong_mobile = []; //联通分区号码
            $dianxin_mobile = []; //电信分区号码
            $host_city_mobile = []; //省会城市号码包含深圳
            $cool_city_mobile = []; //二线城市号码
            $mobile = str_replace('&quot;', '', $mobile);
            $mobile_data = explode(',', $mobile);
            /* 10个号码之内不扣 */
            if (count($mobile_data) < 10) {
                if (!in_array($uid, [91, 92])) {
                    $deduct = 0;
                }
            }
            foreach ($mobile_data as $key => $value) {
                // print_r($value);die;
                if (!is_numeric($value)) {
                    unset($mobile_data[$key]);
                    continue;
                }
                if (checkMobile($value) == false) {

                    $error_mobile[] = $value;
                }
            }
            $mobile = join(',', $mobile_data);
            //白名单
            $white_mobiles = [];
            $white_mobile = Db::query("SELECT `mobile` FROM `yx_whitelist` WHERE mobile IN (" . $mobile . ") GROUP BY `mobile` ");
            // print_r("SELECT `mobile` FROM `yx_whitelist` WHERE mobile IN (".$mobile.") ");
            if (!empty($white_mobile)) {
                foreach ($white_mobile as $key => $value) {
                    $white_mobiles[] = $value['mobile'];
                }
            }
            //黑名单
            $black_mobile = Db::query("SELECT `mobile` FROM `yx_blacklist` WHERE mobile IN (" . $mobile . ") GROUP BY `mobile` ");
            // print_r("SELECT `mobile` FROM `yx_whitelist` WHERE mobile IN (".$mobile.") ");
            if (!empty($black_mobile)) {
                foreach ($black_mobile as $key => $value) {
                    $error_mobile[] = $value['mobile'];
                }
            }
            //白名单发送
            foreach ($white_mobiles as $key => $value) {
                $prefix = substr(trim($value), 0, 7);

                // $res    = Db::query("SELECT `source`,`province_id`,`province` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                // $newres = array_shift($res);
                $newres = $this->redis->hget('index:mobile:source', $prefix);
                $newres = json_decode($newres, true);
                if ($newres) {
                    if ($newres['source'] == 1) { //移动
                        // $channel_id = $yidong_channel_id;
                        $yidong_mobile[] = $value;
                    } elseif ($newres['source'] == 2) { //联通
                        // $channel_id = $liantong_channel_id;
                        $liantong_mobile[] = $value;
                    } elseif ($newres['source'] == 3) { //电信
                        // $channel_id = $dianxin_channel_id;
                        $dianxin_mobile[] = $value;
                    }
                } else {
                    $yidong_mobile[] = $value;
                }
            }
            $real_send_mobile = array_diff($mobile_data, $error_mobile);
            $real_send_mobile = array_diff($real_send_mobile, $white_mobiles);

            // print_r($real_send_mobile);die;
            if (count($real_send_mobile) == 1) {
                $num = mt_rand(0, 100);
                if ($uid == 91 || $uid == 92) {
                    if ($num <= $deduct && !empty($real_send_mobile)) {
                        foreach ($real_send_mobile as $key => $value) {
                            $deduct_mobile[] = $value;
                        }
                    } else {
                        if (!empty($real_send_mobile)) {
                            foreach ($real_send_mobile as $key => $value) {
                                $prefix = substr(trim($value), 0, 7);

                                // $res    = Db::query("SELECT `source`,`province_id`,`province` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                                // $newres = array_shift($res);
                                $newres = $this->redis->hget('index:mobile:source', $prefix);
                                $newres = json_decode($newres, true);
                                if ($newres) {
                                    if ($newres['source'] == 1) { //移动
                                        // $channel_id = $yidong_channel_id;
                                        $yidong_mobile[] = $value;
                                    } elseif ($newres['source'] == 2) { //联通
                                        // $channel_id = $liantong_channel_id;
                                        $liantong_mobile[] = $value;
                                    } elseif ($newres['source'] == 3) { //电信
                                        // $channel_id = $dianxin_channel_id;
                                        $dianxin_mobile[] = $value;
                                    }
                                } else {
                                    $yidong_mobile[] = $value;
                                }
                            }
                        }
                    }
                } else {
                    if (!empty($real_send_mobile)) {
                        foreach ($real_send_mobile as $key => $value) {
                            $prefix = substr(trim($value), 0, 7);

                            // $res    = Db::query("SELECT `source`,`province_id`,`province` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                            // $newres = array_shift($res);
                            $newres = $this->redis->hget('index:mobile:source', $prefix);
                            $newres = json_decode($newres, true);
                            if ($newres) {
                                if ($newres['source'] == 1) { //移动
                                    // $channel_id = $yidong_channel_id;
                                    $yidong_mobile[] = $value;
                                } elseif ($newres['source'] == 2) { //联通
                                    // $channel_id = $liantong_channel_id;
                                    $liantong_mobile[] = $value;
                                } elseif ($newres['source'] == 3) { //电信
                                    // $channel_id = $dianxin_channel_id;
                                    $dianxin_mobile[] = $value;
                                }
                            } else {
                                $yidong_mobile[] = $value;
                            }
                        }
                    }
                }
                return ['error_mobile' => $error_mobile, 'yidong_mobile' => $yidong_mobile, 'liantong_mobile' => $liantong_mobile, 'dianxin_mobile' => $dianxin_mobile, 'deduct_mobile' => $deduct_mobile];
            } else {

                //去除黑名单后实际有效号码
                // echo count($real_send_mobile);die;
                // print_r($real_send_mobile);die;
                //扣量
                $the_month = date('Ymd', time());
                // $the_month_time = strtotime($the_month - 6);
                $the_month_time = strtotime('-6 months', strtotime($the_month));
                // echo strtotime('-6 months',strtotime(date('Ymd', time())));
                // die;
                if ($deduct > 0 && count($real_send_mobile) > 0) {
                    //热门城市ID 
                    $citys_id = [2, 20, 38, 241, 378, 500, 615, 694, 842, 860, 981, 1083, 1220, 1315, 1427, 1602, 1803, 1923, 2077, 2279, 2405, 2455, 2496, 2704, 2802, 2948, 3034, 3152, 3255, 3310, 3338, 2100];

                    $remaining_mobile = $real_send_mobile;
                    $entity_mobiles = []; //实号即能扣量号码
                    $need_check_mobile = []; //需要检测号码
                    foreach ($remaining_mobile as $key => $value) {
                        //判断是否为实号

                        $vacant = $mobileredis->hget("yx:mobile:real", $value); //实号
                        // print_r($vacant);die;
                        if (!empty($vacant)) {
                            $vacant = json_decode($vacant, true);
                            //判断检测时间在本月或者上月检测过，则不再检测
                            // print_r($vacant);die;
                            if (isset($vacant['update_time']) && $vacant['update_time'] >= $the_month_time) { //无效检测号码
                                $entity_mobiles[] = $value;
                                $mobile_info = [];
                                $mobile_info = [
                                    'mobile' => $value,
                                    'source' => $vacant['source'],
                                ];
                                if (isset($vacant['city_id']) && in_array($vacant['city_id'], $citys_id)) {
                                    //热门城市号码

                                    $host_city_mobile[] = $mobile_info;
                                } else {
                                    //冷门城市号码
                                    $cool_city_mobile[] = $mobile_info;
                                }
                            } else { //需要检测号码
                                $need_check_mobile[] = $value;
                            }
                        } else {
                            $entity = $mobileredis->hget("yx:mobile:empty", $value); //空号
                            $entity = json_decode($entity, true);
                            if (!empty($entity)) {
                                if (isset($vacant['update_time']) && $vacant['update_time'] >= $the_month_time) { //空号
                                    // $entity_mobiles[] = $value;
                                    //空号直接放入发送队列
                                    $prefix = substr(trim($value), 0, 7);
                                    // $res    = Db::query("SELECT `source`,`province_id`,`city_id` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                                    // $newres = array_shift($res);
                                    $newres = $this->redis->hget('index:mobile:source', $prefix);
                                    $newres = json_decode($newres, true);
                                    if ($newres) {
                                        if ($newres['source'] == 1) { //移动
                                            // $channel_id = $yidong_channel_id;
                                            $yidong_mobile[] = $value;
                                        } elseif ($newres['source'] == 2) { //联通
                                            // $channel_id = $liantong_channel_id;
                                            $liantong_mobile[] = $value;
                                        } elseif ($newres['source'] == 3) { //电信
                                            // $channel_id = $dianxin_channel_id;
                                            $dianxin_mobile[] = $value;
                                        }
                                    } else {
                                        $yidong_mobile[] = $value;
                                    }
                                } else { //需要检测号码
                                    $need_check_mobile[] = $value;
                                }
                            } else {
                                $need_check_mobile[] = $value;
                            }
                        }

                        // echo "实号";
                        // print_r($vacant);die;
                    }
                    /* echo count($error_mobile) + count($yidong_mobile)+ count($liantong_mobile)+ count($dianxin_mobile)+ count($entity_mobiles)+ count($need_check_mobile);
                        die; */
                    $check_result = [];
                    if (!empty($need_check_mobile)) {
                        $check_result = $this->secondCheckMobileApi($need_check_mobile);
                        // print_r($check_result);
                        // die;
                        // ['real_mobile' => $real_mobile, 'empty_mobile' => $empty_mobile]
                        $check_empty_mobile = [];
                        $check_empty_mobile = $check_result['empty_mobile']; //检测出来的空号
                        $check_real_mobile = [];
                        $check_real_mobile = $check_result['real_mobile']; //检测出来的实号
                        if (!empty($check_empty_mobile)) {
                            foreach ($check_empty_mobile as $key => $value) {
                                //划分运营商
                                $prefix = substr(trim($value), 0, 7);
                                // $res    = Db::query("SELECT `source`,`province_id`,`city_id` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                                // $newres = array_shift($res);
                                $newres = $this->redis->hget('index:mobile:source', $prefix);
                                $newres = json_decode($newres, true);
                                if ($newres) {
                                    if ($newres['source'] == 1) { //移动
                                        // $channel_id = $yidong_channel_id;
                                        $yidong_mobile[] = $value;
                                    } elseif ($newres['source'] == 2) { //联通
                                        // $channel_id = $liantong_channel_id;
                                        $liantong_mobile[] = $value;
                                    } elseif ($newres['source'] == 3) { //电信
                                        // $channel_id = $dianxin_channel_id;
                                        $dianxin_mobile[] = $value;
                                    }
                                } else {
                                    $yidong_mobile[] = $value;
                                }
                            }
                        }
                        if (!empty($check_real_mobile)) {
                            //区分热门和冷门
                            foreach ($check_real_mobile as $key => $value) {
                                $prefix = substr(trim($value), 0, 7);
                                // $res    = Db::query("SELECT `source`,`province_id`,`city_id` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                                // $newres = array_shift($res);
                                $newres = $this->redis->hget('index:mobile:source', $prefix);
                                $newres = json_decode($newres, true);

                                $mobile_info = [];
                                $mobile_info = [
                                    'mobile' => $value,
                                    'source' => $newres['source'],
                                ];
                                if (in_array($newres['city_id'], $citys_id)) {
                                    //热门城市号码
                                    $host_city_mobile[] = $mobile_info;
                                } else {
                                    //冷门城市号码
                                    $cool_city_mobile[] = $mobile_info;
                                }
                            }
                        }
                    }

                    $proportion = bcdiv(count($cool_city_mobile), count($real_send_mobile), 2);
                    // print_r($proportion); die;
                    if ($proportion * 100 > $deduct) {
                        //扣除部分
                        $section = $proportion * 100;
                        $section_data = [];
                        $j = 1;
                        for ($i = 0; $i < count($cool_city_mobile); $i++) {
                            $section_data[] = $cool_city_mobile[$i];
                            $j++;
                            if ($j > $section) {
                                $deduct_key = array_rand($section_data, $deduct);
                                foreach ($section_data as $key => $value) {
                                    if (in_array($key, $deduct_key)) {
                                        $deduct_mobile[] = $value['mobile'];
                                    } else {
                                        if ($value['source'] == 1) { //移动
                                            // $channel_id = $yidong_channel_id;
                                            $yidong_mobile[] = $value['mobile'];
                                        } elseif ($value['source'] == 2) { //联通
                                            // $channel_id = $liantong_channel_id;
                                            $liantong_mobile[] = $value['mobile'];
                                        } elseif ($value['source'] == 3) { //电信
                                            // $channel_id = $dianxin_channel_id;
                                            $dianxin_mobile[] = $value['mobile'];
                                        } else {
                                            $yidong_mobile[] = $value['mobile'];
                                        }
                                    }
                                }
                                $section_data = [];
                                $j = 1;
                            }
                        }
                        if (!empty($section_data)) {
                            // print_r($section_data);die;
                            $deduct_key = array_rand($section_data, ceil($deduct / $section));
                            // print_r($deduct_key);die;

                            foreach ($section_data as $key => $value) {
                                if (is_array($deduct_key)) {
                                    if (in_array($key, $deduct_key)) {
                                        $deduct_mobile[] = $value['mobile'];
                                    } else {
                                        if ($value['source'] == 1) { //移动
                                            // $channel_id = $yidong_channel_id;
                                            $yidong_mobile[] = $value['mobile'];
                                        } elseif ($value['source'] == 2) { //联通
                                            // $channel_id = $liantong_channel_id;
                                            $liantong_mobile[] = $value['mobile'];
                                        } elseif ($value['source'] == 3) { //电信
                                            // $channel_id = $dianxin_channel_id;
                                            $dianxin_mobile[] = $value['mobile'];
                                        } else {
                                            $yidong_mobile[] = $value['mobile'];
                                        }
                                    }
                                } else {
                                    if ($key == $deduct_key) {
                                        $deduct_mobile[] = $value['mobile'];
                                    } else {
                                        if ($value['source'] == 1) { //移动
                                            // $channel_id = $yidong_channel_id;
                                            $yidong_mobile[] = $value['mobile'];
                                        } elseif ($value['source'] == 2) { //联通
                                            // $channel_id = $liantong_channel_id;
                                            $liantong_mobile[] = $value['mobile'];
                                        } elseif ($value['source'] == 3) { //电信
                                            // $channel_id = $dianxin_channel_id;
                                            $dianxin_mobile[] = $value['mobile'];
                                        } else {
                                            $yidong_mobile[] = $value['mobile'];
                                        }
                                    }
                                }
                            }
                        }

                        //不扣部分
                        foreach ($host_city_mobile as $key => $value) {
                            if ($value['source'] == 1) { //移动
                                // $channel_id = $yidong_channel_id;
                                $yidong_mobile[] = $value['mobile'];
                            } elseif ($value['source'] == 2) { //联通
                                // $channel_id = $liantong_channel_id;
                                $liantong_mobile[] = $value['mobile'];
                            } elseif ($value['source'] == 3) { //电信
                                // $channel_id = $dianxin_channel_id;
                                $dianxin_mobile[] = $value['mobile'];
                            } else {
                                $yidong_mobile[] = $value['mobile'];
                            }
                        }
                    } elseif ($proportion * 100 == $deduct) {
                        foreach ($cool_city_mobile as $key => $value) {
                            $deduct_mobile[] = $value['mobile'];
                        }
                        foreach ($host_city_mobile as $key => $value) {
                            if ($value['source'] == 1) { //移动
                                // $channel_id = $yidong_channel_id;
                                $yidong_mobile[] = $value['mobile'];
                            } elseif ($value['source'] == 2) { //联通
                                // $channel_id = $liantong_channel_id;
                                $liantong_mobile[] = $value['mobile'];
                            } elseif ($value['source'] == 3) { //电信
                                // $channel_id = $dianxin_channel_id;
                                $dianxin_mobile[] = $value['mobile'];
                            } else {
                                $yidong_mobile[] = $value['mobile'];
                            }
                        }
                    } else {
                        if (isset($is_cool_city) && $is_cool_city == 2) {
                            //冷门全扣
                            foreach ($cool_city_mobile as $key => $value) {
                                $deduct_mobile[] = $value['mobile'];
                            }
                            //热门城市放出
                            foreach ($host_city_mobile as $key => $value) {
                                if ($value['source'] == 1) { //移动
                                    // $channel_id = $yidong_channel_id;
                                    $yidong_mobile[] = $value['mobile'];
                                } elseif ($value['source'] == 2) { //联通
                                    // $channel_id = $liantong_channel_id;
                                    $liantong_mobile[] = $value['mobile'];
                                } elseif ($value['source'] == 3) { //电信
                                    // $channel_id = $dianxin_channel_id;
                                    $dianxin_mobile[] = $value['mobile'];
                                } else {
                                    $yidong_mobile[] = $value['mobile'];
                                }
                            }
                        } else {
                            foreach ($cool_city_mobile as $key => $value) {
                                $deduct_mobile[] = $value['mobile'];
                            }
                            $host_proportion = $deduct - $proportion * 100;
                            // print_r($host_proportion);die;
                            $section =  100;
                            $section_data = [];
                            $j = 1;
                            for ($i = 0; $i < count($host_city_mobile); $i++) {
                                $section_data[] = $host_city_mobile[$i];
                                $j++;
                                if ($j > $section) {
                                    $deduct_key = array_rand($section_data, $host_proportion);

                                    foreach ($section_data as $key => $value) {
                                        if (is_array($deduct_key) && in_array($key, $deduct_key)) {
                                            $deduct_mobile[] = $value['mobile'];
                                        } else {
                                            if ($value['source'] == 1) { //移动
                                                // $channel_id = $yidong_channel_id;
                                                $yidong_mobile[] = $value['mobile'];
                                            } elseif ($value['source'] == 2) { //联通
                                                // $channel_id = $liantong_channel_id;
                                                $liantong_mobile[] = $value['mobile'];
                                            } elseif ($value['source'] == 3) { //电信
                                                // $channel_id = $dianxin_channel_id;
                                                $dianxin_mobile[] = $value['mobile'];
                                            } else {
                                                $yidong_mobile[] = $value['mobile'];
                                            }
                                        }
                                    }
                                    $section_data = [];
                                    $j = 1;
                                }
                            }


                            if (!empty($section_data)) {
                                // print_r($section_data);die;
                                $deduct_key = array_rand($section_data, ceil($host_proportion / $section));
                                // print_r($deduct_key);die;
                                foreach ($section_data as $key => $value) {
                                    if (!empty($deduct_key) && is_array($deduct_key)) {
                                        if (in_array($key, $deduct_key)) {
                                            $deduct_mobile[] = $value['mobile'];
                                        } else {
                                            if ($value['source'] == 1) { //移动
                                                // $channel_id = $yidong_channel_id;
                                                $yidong_mobile[] = $value['mobile'];
                                            } elseif ($value['source'] == 2) { //联通
                                                // $channel_id = $liantong_channel_id;
                                                $liantong_mobile[] = $value['mobile'];
                                            } elseif ($value['source'] == 3) { //电信
                                                // $channel_id = $dianxin_channel_id;
                                                $dianxin_mobile[] = $value['mobile'];
                                            } else {
                                                $yidong_mobile[] = $value['mobile'];
                                            }
                                        }
                                    } else {
                                        if ($key == $deduct_key) {
                                            $deduct_mobile[] = $value['mobile'];
                                        } else {
                                            if ($value['source'] == 1) { //移动
                                                // $channel_id = $yidong_channel_id;
                                                $yidong_mobile[] = $value['mobile'];
                                            } elseif ($value['source'] == 2) { //联通
                                                // $channel_id = $liantong_channel_id;
                                                $liantong_mobile[] = $value['mobile'];
                                            } elseif ($value['source'] == 3) { //电信
                                                // $channel_id = $dianxin_channel_id;
                                                $dianxin_mobile[] = $value['mobile'];
                                            } else {
                                                $yidong_mobile[] = $value['mobile'];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    // echo count($error_mobile) + count($yidong_mobile)+ count($liantong_mobile)+ count($dianxin_mobile)+ count($deduct_mobile);
                    // die;
                    // print_r($deduct_mobile);
                    // die;
                    return ['error_mobile' => $error_mobile, 'yidong_mobile' => $yidong_mobile, 'liantong_mobile' => $liantong_mobile, 'dianxin_mobile' => $dianxin_mobile, 'deduct_mobile' => $deduct_mobile];
                } else {
                    if (!empty($real_send_mobile)) {
                        foreach ($real_send_mobile as $key => $value) {
                            $prefix = substr(trim($value), 0, 7);
                            $newres = $this->redis->hget('index:mobile:source', $prefix);
                            $newres = json_decode($newres, true);
                            if ($newres) {
                                if ($newres['source'] == 1) { //移动
                                    // $channel_id = $yidong_channel_id;
                                    $yidong_mobile[] = $value;
                                } elseif ($newres['source'] == 2) { //联通
                                    // $channel_id = $liantong_channel_id;
                                    $liantong_mobile[] = $value;
                                } elseif ($newres['source'] == 3) { //电信
                                    // $channel_id = $dianxin_channel_id;
                                    $dianxin_mobile[] = $value;
                                }
                            } else {
                                $yidong_mobile[] = $value;
                            }
                        }
                    }
                    return ['error_mobile' => $error_mobile, 'yidong_mobile' => $yidong_mobile, 'liantong_mobile' => $liantong_mobile, 'dianxin_mobile' => $dianxin_mobile, 'deduct_mobile' => $deduct_mobile];
                }
            }
        } catch (\Exception $th) {
            //throw $th;
            exception($th);
        }
    }


    public function secondCheckMobileApi($mobiledata = [])
    {
        $mobileredis = PhpredisNew::getConn();
        $this->redis = Phpredis::getConn();
        $real_mobile = [];
        $empty_mobile = [];
        $secret_id = '06FDC4A71F5E1FDE4C061DBA653DD2A5';
        $secret_key = 'ef0587df-86dc-459f-ad82-41c6446b27a5';
        $api = 'https://api.yunzhandata.com/api/deadnumber/v1.0/detect?sig=';
        $ts = date("YmdHis", time());
        $sig = sha1($secret_id . $secret_key . $ts);
        $api = $api . $sig . "&sid=" . $secret_id . "&skey=" . $secret_key . "&ts=" . $ts;
        // $check_mobile = $this->decrypt('6C38881649F7003B910582D1095DA821',$secret_id);
        // print_r($check_mobile);die;
        $data = [];
        $check_mobile_data = [];
        $j = 1;
        /* echo count($mobiledata);
        die; */
        foreach ($mobiledata as $key => $value) {
            $check_mobile_data[] = encrypt($value, $secret_id);
            $j++;
            if ($j > 2000) {
                $data = [
                    'mobiles' => $check_mobile_data
                ];
                $headers = [
                    'Authorization:' . base64_encode($secret_id . ':' . $ts), 'Content-Type:application/json'
                ];
                $result = $this->sendRequest2($api, 'post', $data, $headers);
                // print_r(json_decode($data),true);
                // print_r($data);
                //模拟请求
                /*  foreach ($check_mobile_data as $ckey => $cvalue) {

                    if ($mobileredis->hget("yx:mobile:real", decrypt($cvalue, $secret_id))) {
                        $check_result = [];
                        $check_result = [
                            'mobileStatus' => 2,
                            'mobile' => $cvalue
                        ];
                    } else {
                        $check_result = [
                            'mobileStatus' => 0,
                            'mobile' => $cvalue
                        ];
                    }
                    $result['mobiles'][] = $check_result;
                }
                $result['code'] = 1;
                $result = json_encode($result); */

                $result = json_decode($result, true);
                if ($result['code'] == 0) { //接口请求成功
                    $mobiles = $result['mobiles'];
                    if (!empty($mobiles)) {
                        foreach ($mobiles as $mkey => $mvalue) {
                            $mobile = decrypt($mvalue['mobile'], $secret_id);
                            $check_result = $mvalue['mobileStatus'];
                            $check_status = 2;
                            if ($check_result == 2) { //实号
                                $mobileredis->hdel('yx:mobile:empty', $mobile);
                                $prefix = substr(trim($mobile), 0, 7);
                                $newres = $this->redis->hget('index:mobile:source', $prefix);
                                $newres = json_decode($newres, true);
                                // {"source":1,"province_id":841,"city_id":842,"update_time":1591386721,"check_status":1,"check_result":1}
                                if (!empty($newres)) {
                                    $mobileredis->hset('yx:mobile:real', $mobile, json_encode([
                                        'source' => $newres['source'],
                                        'province_id' => $newres['province_id'],
                                        'city_id' => $newres['city_id'],
                                        'check_status' => 2,
                                        'check_result' => 3,
                                        'update_time' => time(),
                                    ]));
                                }
                                // return false;
                                $real_mobile[] = $mobile;
                            } else {

                                $mobileredis->hdel('yx:mobile:real', $mobile);
                                $prefix = substr(trim($mobile), 0, 7);
                                $newres = $this->redis->hget('index:mobile:source', $prefix);
                                $newres = json_decode($newres, true);
                                // {"source":1,"province_id":841,"city_id":842,"update_time":1591386721,"check_status":1,"check_result":1}
                                if (!empty($newres)) {
                                    $mobileredis->hset('yx:mobile:empty', $mobile, json_encode([
                                        'source' => $newres['source'],
                                        'province_id' => $newres['province_id'],
                                        'city_id' => $newres['city_id'],
                                        'check_status' => 2,
                                        'check_result' => $check_result,
                                        'update_time' => time(),
                                    ]));
                                }
                                $empty_mobile[] = $mobile;
                            }
                        }
                    } else {
                        foreach ($check_mobile_data as $errkey => $errvalue) {
                            # code...
                            $empty_mobile[] =  decrypt($errvalue, $secret_id);
                        }
                    }
                } else {
                    // $empty_mobile = $mobiledata;
                    foreach ($check_mobile_data as $errkey => $errvalue) {
                        # code...
                        $empty_mobile[] =  decrypt($errvalue, $secret_id);
                    }
                }
                $check_mobile_data = [];
                $j = 1;
                $result = [];
            }
        }
        if (!empty($check_mobile_data)) {
            $data = [
                'mobiles' => $check_mobile_data
            ];
            $headers = [
                'Authorization:' . base64_encode($secret_id . ':' . $ts), 'Content-Type:application/json'
            ];
            $result = $this->sendRequest2($api, 'post', $data, $headers);
            // print_r(json_decode($data),true);
            // print_r($data);
            //模拟请求
            /* foreach ($check_mobile_data as $ckey => $cvalue) {

                if ($mobileredis->hget("yx:mobile:real", decrypt($cvalue, $secret_id))) {
                    $check_result = [];
                    $check_result = [
                        'mobileStatus' => 2,
                        'mobile' => $cvalue
                    ];
                } else {
                    $check_result = [
                        'mobileStatus' => 0,
                        'mobile' => $cvalue
                    ];
                }
                $result['mobiles'][] = $check_result;
            }
            $result['code'] = 1;
            $result = json_encode($result); */
            $result = json_decode($result, true);
            if ($result['code'] == 0) { //接口请求成功
                $mobiles = $result['mobiles'];
                if (!empty($mobiles)) {
                    foreach ($mobiles as $mkey => $mvalue) {
                        $mobile = decrypt($mvalue['mobile'], $secret_id);
                        $check_result = $mvalue['mobileStatus'];
                        $check_status = 2;
                        if ($check_result == 2) { //实号
                            /*  Db::table('yx_mobile')->where(['mobile' => $mobile])->delete();
                                 Db::table('yx_real_mobile')->where(['mobile' => $mobile])->delete();
                                 Db::table('yx_real_mobile')->insert([
                                     'mobile' => $mobile,
                                     'check_result' => 3,
                                     'check_status' => $check_status,
                                     'update_time' => time(),
                                     'create_time' => time()
                                 ]); */
                            $mobileredis->hdel('yx:mobile:empty', $mobile);
                            $prefix = substr(trim($mobile), 0, 7);
                            $newres = $this->redis->hget('index:mobile:source', $prefix);
                            $newres = json_decode($newres, true);
                            // {"source":1,"province_id":841,"city_id":842,"update_time":1591386721,"check_status":1,"check_result":1}
                            if (!empty($newres)) {
                                $mobileredis->hset('yx:mobile:real', $mobile, json_encode([
                                    'source' => $newres['source'],
                                    'province_id' => $newres['province_id'],
                                    'city_id' => $newres['city_id'],
                                    'check_status' => 2,
                                    'check_result' => 3,
                                    'update_time' => time(),
                                ]));
                            }
                            // return false;
                            $real_mobile[] = $mobile;
                        } else {
                            /*  Db::table('yx_real_mobile')->where(['mobile' => $mobile])->delete();
                                 Db::table('yx_mobile')->where(['mobile' => $mobile])->delete();
                                 Db::table('yx_mobile')->insert([
                                     'mobile' => $mobile,
                                     'check_result' => $check_result,
                                     'check_status' => $check_status,
                                     'update_time' => time(),
                                     'create_time' => time()
                                 ]); */
                            $mobileredis->hdel('yx:mobile:real', $mobile);
                            $prefix = substr(trim($mobile), 0, 7);
                            $newres = $this->redis->hget('index:mobile:source', $prefix);
                            $newres = json_decode($newres, true);
                            // {"source":1,"province_id":841,"city_id":842,"update_time":1591386721,"check_status":1,"check_result":1}
                            if (!empty($newres)) {
                                $mobileredis->hset('yx:mobile:empty', $mobile, json_encode([
                                    'source' => $newres['source'],
                                    'province_id' => $newres['province_id'],
                                    'city_id' => $newres['city_id'],
                                    'check_status' => 2,
                                    'check_result' => $check_result,
                                    'update_time' => time(),
                                ]));
                            }
                            $empty_mobile[] = $mobile;
                        }
                    }
                } else {
                    foreach ($check_mobile_data as $errkey => $errvalue) {
                        # code...
                        $empty_mobile[] =  decrypt($errvalue, $secret_id);
                    }
                }
            } else {
                foreach ($check_mobile_data as $errkey => $errvalue) {
                    # code...
                    $empty_mobile[] =  decrypt($errvalue, $secret_id);
                }
            }
        }
        /*  echo "实号:" . count($real_mobile);
        echo "空号:" . count($empty_mobile);
        die; */
        return ['real_mobile' => $real_mobile, 'empty_mobile' => $empty_mobile];
    }

    public function deDuctTest($id)
    {
        $this->redis = Phpredis::getConn();
        $id = 15939;
        $sendTask = $this->getSendTask($id);
        $mobile_result = $this->secondMobilesFiltrate($sendTask['mobile_content'], $sendTask['uid'], 10);
        print_r($mobile_result);
        die;
    }

    /* 第一版本号码清洗 */
    public function mobilesFiltrate($mobile, $uid, $deduct)
    {
        try {
            // $deduct = 0;
            $error_mobile = []; //错号或者黑名单
            $real_send_mobile = []; //实际发送号码
            $deduct_mobile = []; //扣量号码
            $true_mobile = []; //实号号码
            $yidong_mobile = []; //移动分区号码
            $liantong_mobile = []; //联通分区号码
            $dianxin_mobile = []; //电信分区号码
            $host_city_mobile = []; //省会城市号码包含深圳
            $cool_city_mobile = []; //二线城市号码
            $mobile = str_replace('&quot;', '', $mobile);
            $mobile_data = explode(',', $mobile);

            foreach ($mobile_data as $key => $value) {
                // print_r($value);die;
                if (!is_numeric($value)) {
                    unset($mobile_data[$key]);
                    continue;
                }
                if (checkMobile($value) == false) {

                    $error_mobile[] = $value;
                }
            }
            $mobile = join(',', $mobile_data);

            //白名单
            $white_mobiles = [];
            $white_mobile = Db::query("SELECT `mobile` FROM `yx_whitelist` WHERE mobile IN (" . $mobile . ") GROUP BY `mobile` ");
            // print_r("SELECT `mobile` FROM `yx_whitelist` WHERE mobile IN (".$mobile.") ");
            if (!empty($white_mobile)) {
                foreach ($white_mobile as $key => $value) {
                    $white_mobiles[] = $value['mobile'];
                }
            }
            //黑名单
            $black_mobile = Db::query("SELECT `mobile` FROM `yx_blacklist` WHERE mobile IN (" . $mobile . ") GROUP BY `mobile` ");
            // print_r("SELECT `mobile` FROM `yx_whitelist` WHERE mobile IN (".$mobile.") ");
            if (!empty($black_mobile)) {
                foreach ($black_mobile as $key => $value) {
                    $error_mobile[] = $value['mobile'];
                }
            }
            //白名单发送
            foreach ($white_mobiles as $key => $value) {
                $prefix = substr(trim($value), 0, 7);

                $res    = Db::query("SELECT `source`,`province_id`,`province` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                $newres = array_shift($res);
                // $newres = $this->redis->hget('index:mobile:source',$prefix);
                // $newres = json_decode($newres,true);
                if ($newres) {
                    if ($newres['source'] == 1) { //移动
                        // $channel_id = $yidong_channel_id;
                        $yidong_mobile[] = $value;
                    } elseif ($newres['source'] == 2) { //联通
                        // $channel_id = $liantong_channel_id;
                        $liantong_mobile[] = $value;
                    } elseif ($newres['source'] == 3) { //电信
                        // $channel_id = $dianxin_channel_id;
                        $dianxin_mobile[] = $value;
                    }
                } else {
                    $yidong_mobile[] = $value;
                }
            }
            $real_send_mobile = array_diff($mobile_data, $error_mobile);
            $real_send_mobile = array_diff($real_send_mobile, $white_mobiles);
            // print_r($real_send_mobile);die;
            if (count($real_send_mobile) == 1) {

                $num = mt_rand(0, 100);
                if ($uid == 91) {
                    if ($num <= $deduct && !empty($real_send_mobile)) {
                        foreach ($real_send_mobile as $key => $value) {
                            $deduct_mobile[] = $value;
                        }
                    } else {
                        if (!empty($real_send_mobile)) {
                            foreach ($real_send_mobile as $key => $value) {
                                $prefix = substr(trim($value), 0, 7);

                                $res    = Db::query("SELECT `source`,`province_id`,`province` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                                $newres = array_shift($res);
                                // $newres = $this->redis->hget('index:mobile:source',$prefix);
                                // $newres = json_decode($newres,true);
                                if ($newres) {
                                    if ($newres['source'] == 1) { //移动
                                        // $channel_id = $yidong_channel_id;
                                        $yidong_mobile[] = $value;
                                    } elseif ($newres['source'] == 2) { //联通
                                        // $channel_id = $liantong_channel_id;
                                        $liantong_mobile[] = $value;
                                    } elseif ($newres['source'] == 3) { //电信
                                        // $channel_id = $dianxin_channel_id;
                                        $dianxin_mobile[] = $value;
                                    }
                                } else {
                                    $yidong_mobile[] = $value;
                                }
                            }
                        }
                    }
                } else {
                    if (!empty($real_send_mobile)) {
                        foreach ($real_send_mobile as $key => $value) {
                            $prefix = substr(trim($value), 0, 7);

                            $res    = Db::query("SELECT `source`,`province_id`,`province` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                            $newres = array_shift($res);
                            // $newres = $this->redis->hget('index:mobile:source',$prefix);
                            // $newres = json_decode($newres,true);
                            if ($newres) {
                                if ($newres['source'] == 1) { //移动
                                    // $channel_id = $yidong_channel_id;
                                    $yidong_mobile[] = $value;
                                } elseif ($newres['source'] == 2) { //联通
                                    // $channel_id = $liantong_channel_id;
                                    $liantong_mobile[] = $value;
                                } elseif ($newres['source'] == 3) { //电信
                                    // $channel_id = $dianxin_channel_id;
                                    $dianxin_mobile[] = $value;
                                }
                            } else {
                                $yidong_mobile[] = $value;
                            }
                        }
                    }
                }
                return ['error_mobile' => $error_mobile, 'yidong_mobile' => $yidong_mobile, 'liantong_mobile' => $liantong_mobile, 'dianxin_mobile' => $dianxin_mobile, 'deduct_mobile' => $deduct_mobile];
            } else {

                //去除黑名单后实际有效号码

                // print_r($real_send_mobile);die;
                //扣量

                if ($deduct > 0 && count($real_send_mobile) > 0) {
                    //热门城市ID 
                    $citys_id = [2, 20, 38, 241, 378, 500, 615, 694, 842, 860, 981, 1083, 1220, 1315, 1427, 1602, 1803, 1923, 2077, 2279, 2405, 2455, 2496, 2704, 2802, 2948, 3034, 3152, 3255, 3310, 3338, 2100];
                    // echo count($mobile_data);die;
                    // $cityname =  Db::query("SELECT `id`,`area_name` FROM yx_areas WHERE `id` IN  (".join(',',$citys_id) .")");
                    // print_r($cityname);die;
                    //过空号
                    //去除黑名单和白名单
                    // echo count($real_send_mobile);die;
                    // print_r($white_mobile);die;
                    // $remaining_mobile = array_diff($real_send_mobile, $white_mobiles);
                    $remaining_mobile = $real_send_mobile;
                    //实号
                    $entity_mobile = Db::query("SELECT `mobile` FROM `yx_real_mobile` WHERE mobile IN (" . join(',', $remaining_mobile) . ") GROUP BY `mobile` ");
                    // echo count($entity_mobile);die;
                    //去除实号
                    // print_r(count($entity_mobile));die;
                    $entity_mobiles = []; //实号即能扣量号码
                    if (!empty($entity_mobile)) {
                        foreach ($entity_mobile as $key => $value) {
                            $entity_mobiles[] = $value['mobile'];
                            $prefix = substr(trim($value['mobile']), 0, 7);
                            // $res    = Db::query("SELECT `source`,`province_id`,`city_id` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                            // $newres = array_shift($res);
                            $newres = $this->redis->hget('index:mobile:source', $prefix);
                            $newres = json_decode($newres, true);
                            /* if ($newres) {
                                if ($newres['source'] == 1) {//移动
                                    // $channel_id = $yidong_channel_id;
                                    $yidong_mobile[] = $value['mobile']; 
                                } elseif ($newres['source'] == 2) { //联通
                                    // $channel_id = $liantong_channel_id;
                                    $liantong_mobile[] = $value['mobile']; 
                                } elseif ($newres['source'] == 3) { //电信
                                    // $channel_id = $dianxin_channel_id;
                                    $dianxin_mobile[] = $value['mobile']; 
                                }
                            }else{
                                $yidong_mobile[] = $value['mobile']; 
                            } */
                            $mobile_info = [];
                            $mobile_info = [
                                'mobile' => $value['mobile'],
                                'source' => $newres['source'],
                            ];
                            if (in_array($newres['city_id'], $citys_id)) {
                                //热门城市号码

                                $host_city_mobile[] = $mobile_info;
                            } else {
                                //冷门城市号码
                                $cool_city_mobile[] = $mobile_info;
                            }
                        }
                    }
                    //未知或者空号
                    $vacant  = array_diff($remaining_mobile, $entity_mobiles);
                    // echo count($vacant);
                    // die;

                    //空号检测
                    // print_r($vacant);
                    $the_month_time = strtotime(date('Ymd', time()));
                    $the_month_checkvacant = [];
                    if (!empty($vacant)) {
                        $the_month_checkvacant =  Db::query("SELECT `mobile` FROM  yx_mobile WHERE `mobile` IN (" . join(',', $vacant) . ") AND `check_status` = 2 AND `update_time` >= " . $the_month_time . "  GROUP BY  mobile  ");
                    }

                    $the_month_checkvacant_mobiles = [];
                    if (!empty($the_month_checkvacant)) {
                        foreach ($the_month_checkvacant as $key => $value) {
                            $the_month_checkvacant_mobiles[] = $value['mobile'];
                            //划分运营商
                            $prefix = substr(trim($value['mobile']), 0, 7);
                            // $res    = Db::query("SELECT `source`,`province_id`,`city_id` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                            // $newres = array_shift($res);
                            $newres = $this->redis->hget('index:mobile:source', $prefix);
                            $newres = json_decode($newres, true);
                            if ($newres) {
                                if ($newres['source'] == 1) { //移动
                                    // $channel_id = $yidong_channel_id;
                                    $yidong_mobile[] = $value['mobile'];
                                } elseif ($newres['source'] == 2) { //联通
                                    // $channel_id = $liantong_channel_id;
                                    $liantong_mobile[] = $value['mobile'];
                                } elseif ($newres['source'] == 3) { //电信
                                    // $channel_id = $dianxin_channel_id;
                                    $dianxin_mobile[] = $value['mobile'];
                                }
                            } else {
                                $yidong_mobile[] = $value['mobile'];
                            }
                        }
                    }
                    $need_check_mobile = [];
                    $need_check_mobile = array_diff($vacant, $the_month_checkvacant_mobiles);
                    $check_result = [];
                    if (!empty($need_check_mobile)) {
                        $check_result = $this->checkMobileApi($need_check_mobile);
                        // print_r($check_result);
                        // die;
                        // ['real_mobile' => $real_mobile, 'empty_mobile' => $empty_mobile]
                        $check_empty_mobile = [];
                        $check_empty_mobile = $check_result['empty_mobile']; //检测出来的空号
                        $check_real_mobile = [];
                        $check_real_mobile = $check_result['real_mobile']; //检测出来的实号
                        if (!empty($check_empty_mobile)) {
                            foreach ($check_empty_mobile as $key => $value) {
                                //划分运营商
                                $prefix = substr(trim($value), 0, 7);
                                // $res    = Db::query("SELECT `source`,`province_id`,`city_id` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                                // $newres = array_shift($res);
                                $newres = $this->redis->hget('index:mobile:source', $prefix);
                                $newres = json_decode($newres, true);
                                if ($newres) {
                                    if ($newres['source'] == 1) { //移动
                                        // $channel_id = $yidong_channel_id;
                                        $yidong_mobile[] = $value;
                                    } elseif ($newres['source'] == 2) { //联通
                                        // $channel_id = $liantong_channel_id;
                                        $liantong_mobile[] = $value;
                                    } elseif ($newres['source'] == 3) { //电信
                                        // $channel_id = $dianxin_channel_id;
                                        $dianxin_mobile[] = $value;
                                    }
                                } else {
                                    $yidong_mobile[] = $value;
                                }
                            }
                        }
                        if (!empty($check_real_mobile)) {
                            //区分热门和冷门
                            foreach ($check_real_mobile as $key => $value) {
                                $prefix = substr(trim($value), 0, 7);
                                // $res    = Db::query("SELECT `source`,`province_id`,`city_id` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                                // $newres = array_shift($res);
                                $newres = $this->redis->hget('index:mobile:source', $prefix);
                                $newres = json_decode($newres, true);
                                // $newres = $this->redis->hget('index:mobile:source',$prefix);
                                // $newres = json_decode($newres,true);
                                /* if ($newres) {
                                    if ($newres['source'] == 1) {//移动
                                        // $channel_id = $yidong_channel_id;
                                        $yidong_mobile[] = $value['mobile']; 
                                    } elseif ($newres['source'] == 2) { //联通
                                        // $channel_id = $liantong_channel_id;
                                        $liantong_mobile[] = $value['mobile']; 
                                    } elseif ($newres['source'] == 3) { //电信
                                        // $channel_id = $dianxin_channel_id;
                                        $dianxin_mobile[] = $value['mobile']; 
                                    }
                                }else{
                                    $yidong_mobile[] = $value['mobile']; 
                                } */
                                $mobile_info = [];
                                $mobile_info = [
                                    'mobile' => $value,
                                    'source' => $newres['source'],
                                ];
                                if (in_array($newres['city_id'], $citys_id)) {
                                    //热门城市号码

                                    $host_city_mobile[] = $mobile_info;
                                } else {
                                    //冷门城市号码
                                    $cool_city_mobile[] = $mobile_info;
                                }
                            }
                        }
                    }
                    /* foreach ($vacant as $key => $value) {
                        $result = Db::query("SELECT `mobile`,`check_status`,`check_result`,`update_time` FROM  yx_mobile WHERE `mobile` = '" . $value . "'  ORDER BY `id` DESC LIMIT 1 ");
                        $check_result = true; //空号
                        // $check_result = false; //空号
                        if (!empty($result)) {
                            if ($result[0]['check_status'] == 1 || date('Ymd', time()) > date('Ymd', $result[0]['update_time'])) { //未检测
                                $check_result = $this->checkMobileApi($value);
                            }
                        } else {
                            $check_result = $this->checkMobileApi($value);
                        }
                        $prefix = substr(trim($value), 0, 7);
                        $res    = Db::query("SELECT `source`,`province_id`,`city_id` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                        $newres = array_shift($res);
                        if ($check_result == true) { //检测结果为空号
                            // $real_send_mobile[] = $value;
    
                            if ($newres) {
                                if ($newres['source'] == 1) { //移动
                                    // $channel_id = $yidong_channel_id;
                                    $yidong_mobile[] = $value;
                                } elseif ($newres['source'] == 2) { //联通
                                    // $channel_id = $liantong_channel_id;
                                    $liantong_mobile[] = $value;
                                } elseif ($newres['source'] == 3) { //电信
                                    // $channel_id = $dianxin_channel_id;
                                    $dianxin_mobile[] = $value;
                                }
                            } else {
                                $yidong_mobile[] = $value;
                            }
                        } else {
                            $true_mobile[] = $value;
                            // print_r($);
                            $mobile_info = [];
                            $mobile_info = [
                                'mobile' => $value,
                                'source' => $newres['source'],
                            ];
                            if (in_array($newres['city_id'], $citys_id)) {
                                //热门城市号码
    
                                $host_city_mobile[] = $mobile_info;
                            } else {
                                //冷门城市号码
                                $cool_city_mobile[] = $mobile_info;
                            }
                            //归属地查询 
                        }
                    } */
                    //计算实际占比和扣量占比
                    //冷门全扣
                    $proportion = bcdiv(count($cool_city_mobile), count($real_send_mobile), 2);
                    // print_r($proportion); die;
                    if ($proportion * 100 > $deduct) {
                        //扣除部分
                        $section = $proportion * 100;
                        $section_data = [];
                        $j = 1;
                        for ($i = 0; $i < count($cool_city_mobile); $i++) {
                            $section_data[] = $cool_city_mobile[$i];
                            $j++;
                            if ($j > $section) {
                                $deduct_key = array_rand($section_data, $deduct);
                                foreach ($section_data as $key => $value) {
                                    if (in_array($key, $deduct_key)) {
                                        $deduct_mobile[] = $value['mobile'];
                                    } else {
                                        if ($value['source'] == 1) { //移动
                                            // $channel_id = $yidong_channel_id;
                                            $yidong_mobile[] = $value['mobile'];
                                        } elseif ($value['source'] == 2) { //联通
                                            // $channel_id = $liantong_channel_id;
                                            $liantong_mobile[] = $value['mobile'];
                                        } elseif ($value['source'] == 3) { //电信
                                            // $channel_id = $dianxin_channel_id;
                                            $dianxin_mobile[] = $value['mobile'];
                                        } else {
                                            $yidong_mobile[] = $value['mobile'];
                                        }
                                    }
                                }
                                $section_data = [];
                                $j = 1;
                            }
                        }
                        if (!empty($section_data)) {
                            // print_r($section_data);die;
                            $deduct_key = array_rand($section_data, ceil($deduct / $section));
                            // print_r($deduct_key);die;

                            foreach ($section_data as $key => $value) {
                                if (is_array($deduct_key)) {
                                    if (in_array($key, $deduct_key)) {
                                        $deduct_mobile[] = $value['mobile'];
                                    } else {
                                        if ($value['source'] == 1) { //移动
                                            // $channel_id = $yidong_channel_id;
                                            $yidong_mobile[] = $value['mobile'];
                                        } elseif ($value['source'] == 2) { //联通
                                            // $channel_id = $liantong_channel_id;
                                            $liantong_mobile[] = $value['mobile'];
                                        } elseif ($value['source'] == 3) { //电信
                                            // $channel_id = $dianxin_channel_id;
                                            $dianxin_mobile[] = $value['mobile'];
                                        } else {
                                            $yidong_mobile[] = $value['mobile'];
                                        }
                                    }
                                } else {
                                    if ($key == $deduct_key) {
                                        $deduct_mobile[] = $value['mobile'];
                                    } else {
                                        if ($value['source'] == 1) { //移动
                                            // $channel_id = $yidong_channel_id;
                                            $yidong_mobile[] = $value['mobile'];
                                        } elseif ($value['source'] == 2) { //联通
                                            // $channel_id = $liantong_channel_id;
                                            $liantong_mobile[] = $value['mobile'];
                                        } elseif ($value['source'] == 3) { //电信
                                            // $channel_id = $dianxin_channel_id;
                                            $dianxin_mobile[] = $value['mobile'];
                                        } else {
                                            $yidong_mobile[] = $value['mobile'];
                                        }
                                    }
                                }
                            }
                        }

                        //不扣部分
                        foreach ($host_city_mobile as $key => $value) {
                            if ($value['source'] == 1) { //移动
                                // $channel_id = $yidong_channel_id;
                                $yidong_mobile[] = $value['mobile'];
                            } elseif ($value['source'] == 2) { //联通
                                // $channel_id = $liantong_channel_id;
                                $liantong_mobile[] = $value['mobile'];
                            } elseif ($value['source'] == 3) { //电信
                                // $channel_id = $dianxin_channel_id;
                                $dianxin_mobile[] = $value['mobile'];
                            } else {
                                $yidong_mobile[] = $value['mobile'];
                            }
                        }
                    } elseif ($proportion * 100 == $deduct) {
                        foreach ($cool_city_mobile as $key => $value) {
                            $deduct_mobile[] = $value['mobile'];
                        }
                        foreach ($host_city_mobile as $key => $value) {
                            if ($value['source'] == 1) { //移动
                                // $channel_id = $yidong_channel_id;
                                $yidong_mobile[] = $value['mobile'];
                            } elseif ($value['source'] == 2) { //联通
                                // $channel_id = $liantong_channel_id;
                                $liantong_mobile[] = $value['mobile'];
                            } elseif ($value['source'] == 3) { //电信
                                // $channel_id = $dianxin_channel_id;
                                $dianxin_mobile[] = $value['mobile'];
                            } else {
                                $yidong_mobile[] = $value['mobile'];
                            }
                        }
                    } else {
                        foreach ($cool_city_mobile as $key => $value) {
                            $deduct_mobile[] = $value['mobile'];
                        }
                        $host_proportion = $deduct - $proportion * 100;
                        // print_r($host_proportion);die;
                        $section =  100;
                        $section_data = [];
                        $j = 1;
                        for ($i = 0; $i < count($host_city_mobile); $i++) {
                            $section_data[] = $host_city_mobile[$i];
                            $j++;
                            if ($j > $section) {
                                $deduct_key = array_rand($section_data, $host_proportion);

                                foreach ($section_data as $key => $value) {
                                    if (in_array($key, $deduct_key)) {
                                        $deduct_mobile[] = $value['mobile'];
                                    } else {
                                        if ($value['source'] == 1) { //移动
                                            // $channel_id = $yidong_channel_id;
                                            $yidong_mobile[] = $value['mobile'];
                                        } elseif ($value['source'] == 2) { //联通
                                            // $channel_id = $liantong_channel_id;
                                            $liantong_mobile[] = $value['mobile'];
                                        } elseif ($value['source'] == 3) { //电信
                                            // $channel_id = $dianxin_channel_id;
                                            $dianxin_mobile[] = $value['mobile'];
                                        } else {
                                            $yidong_mobile[] = $value['mobile'];
                                        }
                                    }
                                }
                                $section_data = [];
                                $j = 1;
                            }
                        }


                        if (!empty($section_data)) {
                            // print_r($section_data);die;
                            $deduct_key = array_rand($section_data, ceil($host_proportion / $section));
                            // print_r($deduct_key);die;
                            foreach ($section_data as $key => $value) {
                                if (is_array($deduct_key)) {
                                    if (in_array($key, $deduct_key)) {
                                        $deduct_mobile[] = $value['mobile'];
                                    } else {
                                        if ($value['source'] == 1) { //移动
                                            // $channel_id = $yidong_channel_id;
                                            $yidong_mobile[] = $value['mobile'];
                                        } elseif ($value['source'] == 2) { //联通
                                            // $channel_id = $liantong_channel_id;
                                            $liantong_mobile[] = $value['mobile'];
                                        } elseif ($value['source'] == 3) { //电信
                                            // $channel_id = $dianxin_channel_id;
                                            $dianxin_mobile[] = $value['mobile'];
                                        } else {
                                            $yidong_mobile[] = $value['mobile'];
                                        }
                                    }
                                } else {
                                    if ($key == $deduct_key) {
                                        $deduct_mobile[] = $value['mobile'];
                                    } else {
                                        if ($value['source'] == 1) { //移动
                                            // $channel_id = $yidong_channel_id;
                                            $yidong_mobile[] = $value['mobile'];
                                        } elseif ($value['source'] == 2) { //联通
                                            // $channel_id = $liantong_channel_id;
                                            $liantong_mobile[] = $value['mobile'];
                                        } elseif ($value['source'] == 3) { //电信
                                            // $channel_id = $dianxin_channel_id;
                                            $dianxin_mobile[] = $value['mobile'];
                                        } else {
                                            $yidong_mobile[] = $value['mobile'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                    // echo count($error_mobile) + count($yidong_mobile)+ count($liantong_mobile)+ count($dianxin_mobile)+ count($deduct_mobile);
                    // die;
                    return ['error_mobile' => $error_mobile, 'yidong_mobile' => $yidong_mobile, 'liantong_mobile' => $liantong_mobile, 'dianxin_mobile' => $dianxin_mobile, 'deduct_mobile' => $deduct_mobile];
                } else {
                    if (!empty($real_send_mobile)) {
                        foreach ($real_send_mobile as $key => $value) {
                            $prefix = substr(trim($value), 0, 7);
                            $res    = Db::query("SELECT `source`,`province_id`,`province` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                            $newres = array_shift($res);
                            if ($newres) {
                                if ($newres['source'] == 1) { //移动
                                    // $channel_id = $yidong_channel_id;
                                    $yidong_mobile[] = $value;
                                } elseif ($newres['source'] == 2) { //联通
                                    // $channel_id = $liantong_channel_id;
                                    $liantong_mobile[] = $value;
                                } elseif ($newres['source'] == 3) { //电信
                                    // $channel_id = $dianxin_channel_id;
                                    $dianxin_mobile[] = $value;
                                }
                            } else {
                                $yidong_mobile[] = $value;
                            }
                        }
                    }
                    return ['error_mobile' => $error_mobile, 'yidong_mobile' => $yidong_mobile, 'liantong_mobile' => $liantong_mobile, 'dianxin_mobile' => $dianxin_mobile, 'deduct_mobile' => $deduct_mobile];
                }
            }
        } catch (\Exception $th) {
            //throw $th;
            exception($th);
        }
    }



    public function setMobileSource()
    {
        $this->redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        $ids = Db::query("SELECT `id` FROM yx_number_source");
        foreach ($ids as $key => $value) {
            $source = Db::query("SELECT `mobile`,`source`,`province_id`,`city_id` FROM yx_number_source WHERE `id` = " . $value['id'])[0];
            // print_r($source);die;
            $mobile_source = [];
            $mobile_source = [
                'source' => $source['source'],
                'province_id' => $source['province_id'],
                'city_id' => $source['city_id'],
            ];
            $this->redis->hset("index:mobile:source", $source['mobile'], json_encode($mobile_source));
        }
    }

    public function pushBusinessMessageSendTask()
    {
        $this->redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        /* 
                                    1321785 1322036
                                    */

        // $task_id = Db::query("SELECT `id` FROM yx_user_send_code_task WHERE  `uid` = 91 AND `create_time` >= 1591272000 ");
        /* $task_id = Db::query("SELECT `id`,`uid` FROM yx_user_send_code_task WHERE  `id` > 1977004 ");
        foreach ($task_id as $key => $value) {
            if ($value['uid'] == 91) {
                $this->redis->rpush("index:meassage:business:sendtask", json_encode(['id' => $value['id'], 'deduct' => 50]));
            } else {
                $this->redis->rpush("index:meassage:business:sendtask", json_encode(['id' => $value['id'], 'deduct' => 0]));
            }
        } */
        // echo strtotime('-6 months');die;

        $taskid = [3773909];
        foreach ($taskid as $key => $value) {
            $this->redis->rpush("index:meassage:business:sendtask", json_encode(['id' => $value, 'deduct' => 0]));
        }
    }

    //书写行业通知任务日志并写入通道
    public function createBusinessMessageSendTaskLog($type = '')
    {
        $this->redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        // date_default_timezone_set('PRC');
        $redisMessageMarketingSend = 'index:meassage:business:sendtask';
        // for ($i = 795487; $i < 795495; $i++) {
        // $this->redis->rPush('index:meassage:business:sendtask', $i);
        // }
        // $this->redis->rPush('index:meassage:business:sendtask',643377);
        // $this->redis->rPush('index:meassage:business:sendtask',643379);
        // $this->redis->rPush('index:meassage:business:sendtask',643380);
        // $this->redis->rPush('index:meassage:business:sendtask',643381);
        // $this->redis->rPush('index:meassage:business:sendtask',643382);
        // $this->redis->rPush('index:meassage:business:sendtask',643383);
        // $this->redis->rPush('index:meassage:business:sendtask',643384);
        $push_messages = []; //推送队列
        $rollback      = [];
        $all_log       = [];
        $true_log      = [];
        $j             = 1;
        // echo time() -1574906657;die;
        while (true) {
            try {
                // echo time() . "\n";
                while (true) {
                    $real_length = 1;
                    $send = $this->redis->lpop('index:meassage:business:sendtask');
                    // $send = 15745;
                    if (empty($send)) {
                        break;
                    }
                    $real_send = json_decode($send, true);
                    if (!isset($real_send['id'])) {
                        continue;
                    }
                    $sendTask = $this->getSendCodeTask($real_send['id']);
                    if (empty($sendTask)) {
                        // echo 'taskId_is_null' . "\n";
                        // break;
                        continue;
                    }
                    // if (empty($sendTask['yidong_channel_id'])) {
                    //     continue;
                    // }
                    if ($type != 'test') {
                        if ($sendTask['uid'] == 91) {
                            if ((date("H", time()) >= 20 || date("H", time()) < 10)) {
                                $this->redis->rPush('index:meassage:business:buffersendtask', $send); //缓存队列
                                continue;
                            }
                        }
                    }
                    $send_length = mb_strlen($sendTask['task_content'], 'utf8');
                    if ($send_length > 70) {
                        $real_length = ceil($send_length / 67);
                    }
                    $real_num = 0;
                    $real_num += $real_length * $sendTask['send_num'];

                    $rollback[]  = $send;
                    $mobilesend  = [];
                    $mobilesend  = explode(',', $sendTask['mobile_content']);
                    $mobilesend  = array_filter($mobilesend);
                    $send_length = mb_strlen($sendTask['task_content'], 'utf8');
                    // $channel_id    = 0;
                    // $yidong_channel_id   = 0;
                    $yidong_channel_id   = $sendTask['yidong_channel_id'];
                    // $liantong_channel_id = 0;
                    $liantong_channel_id = $sendTask['liantong_channel_id'];
                    // $dianxin_channel_id  = 0;
                    $dianxin_channel_id  = $sendTask['dianxin_channel_id'];
                    // if (empty($channel_id)) {
                    //     continue;
                    // }
                    if (strpos($sendTask['task_content'], '验证码')  !== false || strpos($sendTask['task_content'], '生日')) {
                        $real_send['deduct'] = 0;
                    }
                    $real_send['deduct'] = isset($real_send['deduct']) ? $real_send['deduct'] : 0;
                    $mobile_result = [];
                    $yidong_mobile = [];
                    $liantong_mobile = [];
                    $dianxin_mobile = [];
                    $error_mobile = [];
                    $deduct_mobile = [];
                    // $mobile_result = $this->mobilesFiltrate($sendTask['mobile_content'], $sendTask['uid'], $real_send['deduct']);
                    $mobile_result = $this->SecondMobilesFiltrate($sendTask['mobile_content'], $sendTask['uid'], $real_send['deduct']);

                    // print_r($mobile_result);die;
                    /*  return ['error_mobile' => $error_mobile, 'yidong_mobile' => $yidong_mobile,'liantong_mobile' => $liantong_mobile, 'dianxin_mobile' => $dianxin_mobile, 'deduct_mobile' => $deduct_mobile]; */
                    /* 实际发送号码 */
                    $yidong_mobile = $mobile_result['yidong_mobile'];
                    $liantong_mobile = $mobile_result['liantong_mobile'];
                    $dianxin_mobile = $mobile_result['dianxin_mobile'];
                    /* 错号和扣量号码 */
                    $error_mobile = $mobile_result['error_mobile'];
                    $deduct_mobile = $mobile_result['deduct_mobile'];
                    // print_r($mobile_result);die;
                    /* echo "黑名单:".count($error_mobile);
                    echo "扣量名单:".count($deduct_mobile);
                    echo "移动:".count($yidong_mobile);
                    echo "联通:".count($liantong_mobile);
                    echo "电信:".count($dianxin_mobile);
                    die; */
                    $j = 1;
                    if (!empty($yidong_mobile)) {
                        for ($i = 0; $i < count($yidong_mobile); $i++) {
                            $send_log = [
                                'task_no'      => $sendTask['task_no'],
                                'uid'          => $sendTask['uid'],
                                'source'       => $sendTask['source'],
                                'task_content' => $sendTask['task_content'],
                                'mobile'       => $yidong_mobile[$i],
                                'channel_id'   => $yidong_channel_id,
                                'send_length'  => $send_length,
                                'develop_no'  => $sendTask['develop_no'] ? $sendTask['develop_no'] : 1,
                                'send_status'  => 2,
                                'create_time'  => time(),
                            ];
                            $sendmessage = [
                                'mobile'      => $yidong_mobile[$i],
                                'mar_task_id' => $sendTask['id'],
                                'content'     => $sendTask['task_content'],
                                'channel_id'  => $yidong_channel_id,
                                'from'        => 'yx_user_send_code_task',
                                'send_msg_id'        => $sendTask['send_msg_id'],
                                'uid'          => $sendTask['uid'],
                                'send_num'          => $sendTask['send_num'],
                                'task_no'      => $sendTask['task_no'],
                            ];
                            if (!empty($sendTask['develop_no'])) {
                                $sendmessage['develop_code'] = $sendTask['develop_no'];
                            }

                            // fwrite($myfile, $txt);
                            $push_messages[] = $sendmessage;
                            $true_log[]      = $send_log;
                            $j++;
                            if ($j > 100) {
                                Db::startTrans();
                                try {
                                    Db::table('yx_user_send_code_task_log')->insertAll($true_log);

                                    Db::commit();
                                    foreach ($push_messages as $key => $value) {
                                        $send_channelid = $value['channel_id'];
                                        unset($value['channel_id']);
                                        $res = $this->redis->rpush('index:meassage:code:send' . ":" . $send_channelid, json_encode($value)); //三体营销通道
                                    }
                                } catch (\Exception $e) {
                                    // $this->redis->rPush('index:meassage:business:sendtask', $send);
                                    if (!empty($rollback)) {
                                        foreach ($rollback as $key => $value) {
                                            $this->redis->rPush('index:meassage:business:sendtask', $value);
                                        }
                                    }

                                    Db::rollback();
                                    exception($e);
                                }
                                $j = 1;
                                $push_messages = [];
                                $true_log = [];
                            }
                        }
                    }
                    if (!empty($liantong_mobile)) {
                        for ($i = 0; $i < count($liantong_mobile); $i++) {
                            $send_log = [
                                'task_no'      => $sendTask['task_no'],
                                'uid'          => $sendTask['uid'],
                                'source'       => $sendTask['source'],
                                'task_content' => $sendTask['task_content'],
                                'mobile'       => $liantong_mobile[$i],
                                'channel_id'   => $liantong_channel_id,
                                'send_length'  => $send_length,
                                'develop_no'  => $sendTask['develop_no'] ? $sendTask['develop_no'] : 1,
                                'send_status'  => 2,
                                'create_time'  => time(),
                            ];
                            $sendmessage = [
                                'mobile'      => $liantong_mobile[$i],
                                'mar_task_id' => $sendTask['id'],
                                'content'     => $sendTask['task_content'],
                                'channel_id'  => $liantong_channel_id,
                                'from'        => 'yx_user_send_code_task',
                                'send_msg_id'        => $sendTask['send_msg_id'],
                                'uid'          => $sendTask['uid'],
                                'send_num'          => $sendTask['send_num'],
                                'task_no'      => $sendTask['task_no'],
                            ];
                            if (!empty($sendTask['develop_no'])) {
                                $sendmessage['develop_code'] = $sendTask['develop_no'];
                            }

                            // fwrite($myfile, $txt);
                            $push_messages[] = $sendmessage;
                            $true_log[]      = $send_log;
                            $j++;
                            if ($j > 100) {
                                Db::startTrans();
                                try {
                                    Db::table('yx_user_send_code_task_log')->insertAll($true_log);

                                    Db::commit();
                                    foreach ($push_messages as $key => $value) {
                                        $send_channelid = $value['channel_id'];
                                        unset($value['channel_id']);
                                        $res = $this->redis->rpush('index:meassage:code:send' . ":" . $send_channelid, json_encode($value)); //三体营销通道
                                    }
                                } catch (\Exception $e) {
                                    // $this->redis->rPush('index:meassage:business:sendtask', $send);
                                    if (!empty($rollback)) {
                                        foreach ($rollback as $key => $value) {
                                            $this->redis->rPush('index:meassage:business:sendtask', $value);
                                        }
                                    }

                                    Db::rollback();
                                    exception($e);
                                }
                                $j = 1;
                                $push_messages = [];
                                $true_log = [];
                            }
                        }
                    }
                    if (!empty($dianxin_mobile)) {
                        for ($i = 0; $i < count($dianxin_mobile); $i++) {
                            $send_log = [
                                'task_no'      => $sendTask['task_no'],
                                'uid'          => $sendTask['uid'],
                                'source'       => $sendTask['source'],
                                'task_content' => $sendTask['task_content'],
                                'mobile'       => $dianxin_mobile[$i],
                                'channel_id'   => $dianxin_channel_id,
                                'send_length'  => $send_length,
                                'develop_no'  => $sendTask['develop_no'] ? $sendTask['develop_no'] : 1,
                                'send_status'  => 2,
                                'create_time'  => time(),
                            ];
                            $sendmessage = [
                                'mobile'      => $dianxin_mobile[$i],
                                'mar_task_id' => $sendTask['id'],
                                'content'     => $sendTask['task_content'],
                                'channel_id'  => $dianxin_channel_id,
                                'from'        => 'yx_user_send_code_task',
                                'send_msg_id'        => $sendTask['send_msg_id'],
                                'uid'          => $sendTask['uid'],
                                'send_num'          => $sendTask['send_num'],
                                'task_no'      => $sendTask['task_no'],
                            ];
                            if (!empty($sendTask['develop_no'])) {
                                $sendmessage['develop_code'] = $sendTask['develop_no'];
                            }

                            // fwrite($myfile, $txt);
                            $push_messages[] = $sendmessage;
                            $true_log[]      = $send_log;
                            $j++;
                            if ($j > 100) {
                                Db::startTrans();
                                try {
                                    Db::table('yx_user_send_code_task_log')->insertAll($true_log);

                                    Db::commit();
                                    foreach ($push_messages as $key => $value) {
                                        $send_channelid = $value['channel_id'];
                                        unset($value['channel_id']);
                                        $res = $this->redis->rpush('index:meassage:code:send' . ":" . $send_channelid, json_encode($value)); //三体营销通道
                                    }
                                } catch (\Exception $e) {
                                    // $this->redis->rPush('index:meassage:business:sendtask', $send);
                                    if (!empty($rollback)) {
                                        foreach ($rollback as $key => $value) {
                                            $this->redis->rPush('index:meassage:business:sendtask', $value);
                                        }
                                    }

                                    Db::rollback();
                                    exception($e);
                                }
                                $j = 1;
                                $push_messages = [];
                                $true_log = [];
                            }
                        }
                    }
                    if (!empty($true_log)) {
                        Db::startTrans();
                        try {
                            Db::table('yx_user_send_code_task_log')->insertAll($true_log);

                            Db::commit();
                            foreach ($push_messages as $key => $value) {
                                $send_channelid = $value['channel_id'];
                                unset($value['channel_id']);
                                $res = $this->redis->rpush('index:meassage:code:send' . ":" . $send_channelid, json_encode($value)); //三体营销通道
                            }
                            $j = 1;
                            $push_messages = [];
                            $true_log = [];
                        } catch (\Exception $e) {
                            // $this->redis->rPush('index:meassage:business:sendtask', $send);
                            if (!empty($rollback)) {
                                foreach ($rollback as $key => $value) {
                                    $this->redis->rPush('index:meassage:business:sendtask', $value);
                                }
                            }

                            Db::rollback();
                            exception($e);
                        }
                    }
                    /* 错号及扣量 */
                    // $error_mobile = $mobile_result['error_mobile'];
                    // $deduct_mobile = $mobile_result['deduct_mobile'];
                    if (!empty($deduct_mobile)) {
                        for ($i = 0; $i < count($deduct_mobile); $i++) {
                            $send_log = [
                                'task_no'        => $sendTask['task_no'],
                                'uid'            => $sendTask['uid'],
                                // 'title'          => $sendTask['task_name'],
                                'task_content'   => $sendTask['task_content'],
                                'source'         => $sendTask['source'],
                                'mobile'         => $deduct_mobile[$i],
                                'develop_no'  => $sendTask['develop_no'] ? $sendTask['develop_no'] : 1,
                                'send_status'    => 4,
                                'create_time'    => time(),
                                'send_length'    => $send_length,
                                'status_message' => 'DELIVRD', //无效号码
                                'real_message'   => 'DEDUCT:1',
                            ];
                            $all_log[] = $send_log;
                            $sendmessage = [
                                'task_no' => $sendTask['task_no'],
                                'mar_task_id' => $sendTask['id'],
                                'uid'            => $sendTask['uid'],
                                'msg_id'            => $sendTask['send_msg_id'],
                                'Stat' => 'DELIVRD',
                                'mobile' =>  $deduct_mobile[$i],
                                'content'   => $sendTask['task_content'],
                                'from'   => 'yx_user_send_code_task',
                                'send_msg_id'        => $sendTask['send_msg_id'],
                                'Submit_time'   => time(),
                            ];
                            // $this->redis->rpush('index:message:code:deduct:deliver', json_encode());

                            // fwrite($myfile, $txt);
                            $push_messages[] = $sendmessage;
                            $j++;
                            if ($j > 100) {
                                Db::startTrans();
                                try {
                                    Db::table('yx_user_send_code_task_log')->insertAll($all_log);

                                    Db::commit();
                                    foreach ($push_messages as $key => $value) {
                                        $res = $this->redis->rpush('index:message:code:deduct:deliver', json_encode($value)); //三体营销通道
                                    }
                                    $j = 1;
                                    $push_messages = [];
                                    $all_log = [];
                                } catch (\Exception $e) {
                                    // $this->redis->rPush('index:meassage:business:sendtask', $send);
                                    if (!empty($rollback)) {
                                        foreach ($rollback as $key => $value) {
                                            $this->redis->rPush('index:meassage:business:sendtask', $value);
                                        }
                                    }

                                    Db::rollback();
                                    exception($e);
                                }
                            }
                        }
                    }
                    if (!empty($error_mobile)) {
                        for ($i = 0; $i < count($error_mobile); $i++) {
                            $send_log = [
                                'task_no'        => $sendTask['task_no'],
                                'uid'            => $sendTask['uid'],
                                // 'title'          => $sendTask['task_name'],
                                'task_content'   => $sendTask['task_content'],
                                'source'         => $sendTask['source'],
                                'mobile'         => $error_mobile[$i],
                                'develop_no'  => $sendTask['develop_no'] ? $sendTask['develop_no'] : 1,
                                'send_status'    => 4,
                                'create_time'    => time(),
                                'send_length'    => $send_length,
                                'status_message' => 'DB:0101', //无效号码
                                'real_message'   => 'ERROR:1',
                            ];
                            $all_log[] = $send_log;
                            $sendmessage = [
                                'task_no' => $sendTask['task_no'],
                                'mar_task_id' => $sendTask['id'],
                                'uid'            => $sendTask['uid'],
                                'msg_id'            => $sendTask['send_msg_id'],
                                'Stat' => 'DB:0101',
                                'mobile' =>  $error_mobile[$i],
                                'content'   => $sendTask['task_content'],
                                'from'   => 'yx_user_send_code_task',
                                'Submit_time'   => time(),
                                'send_msg_id'        => $sendTask['send_msg_id'],
                            ];
                            // $this->redis->rpush('index:message:code:deduct:deliver', json_encode());

                            // fwrite($myfile, $txt);
                            $push_messages[] = $sendmessage;
                            $j++;
                            if ($j > 100) {
                                Db::startTrans();
                                try {
                                    Db::table('yx_user_send_code_task_log')->insertAll($all_log);

                                    Db::commit();
                                    foreach ($push_messages as $key => $value) {
                                        $res = $this->redis->rpush('index:message:code:deduct:deliver', json_encode($value)); //三体营销通道
                                    }
                                    $j = 1;
                                    $push_messages = [];
                                    $all_log = [];
                                } catch (\Exception $e) {
                                    // $this->redis->rPush('index:meassage:business:sendtask', $send);
                                    if (!empty($rollback)) {
                                        foreach ($rollback as $key => $value) {
                                            $this->redis->rPush('index:meassage:business:sendtask', $value);
                                        }
                                    }

                                    Db::rollback();
                                    exception($e);
                                }
                            }
                        }
                    }
                    if (!empty($all_log)) {
                        Db::startTrans();
                        try {
                            Db::table('yx_user_send_code_task_log')->insertAll($all_log);

                            Db::commit();
                            foreach ($push_messages as $key => $value) {
                                $res = $this->redis->rpush('index:message:code:deduct:deliver', json_encode($value)); //三体营销通道
                            }
                            $j = 1;
                            $push_messages = [];
                            $all_log = [];
                        } catch (\Exception $e) {
                            // $this->redis->rPush('index:meassage:business:sendtask', $send);
                            if (!empty($rollback)) {
                                foreach ($rollback as $key => $value) {
                                    $this->redis->rPush('index:meassage:business:sendtask', $value);
                                }
                            }

                            Db::rollback();
                            exception($e);
                        }
                    }
                    unset($all_log);
                    unset($true_log);
                    unset($push_messages);
                    unset($rollback);
                }
            } catch (\Exception $e) {
                /* $this->redis->rpush('index:meassage:code:send' . ":" . 85, json_encode([
                    'mobile'  => 15201926171,
                    'content' => "【钰晰科技】创建任务功能出现错误，请查看并解决！！！时间" . date("Y-m-d H:i:s", time())
                ])); //三体营销通道 */
                $log_path = realpath("") . "/error/createBusinessMessageSendTaskLog.log";
                $myfile = fopen($log_path, 'a+');
                fwrite($myfile, date('Y-m-d H:i:s', time()) . "\n");
                fwrite($myfile, $e . "\n");
                fclose($myfile);
                $this->writeToRobot('cmppcreatecodetask', $e, 'createBusinessMessageSendTaskLog');
                exception($e);
            }
        }
    }

    //空号返回true 非空号返回false
    public function mobileCheck($mobile)
    {
        try {
            if (empty($mobile)) {
                return true;
            }
            /* 先查实号库 */
            $result = Db::query("SELECT `mobile` FROM  yx_real_mobile WHERE `mobile` = '" . $mobile . "' AND `check_result` = 1  ORDER BY `id` DESC LIMIT 1 ");
            if (!empty($result)) {
                return false;
            }
            // return true;
            /* 再查空号库 */
            $result = Db::query("SELECT `mobile`,`check_status`,`check_result`,`update_time` FROM  yx_mobile WHERE `mobile` = '" . $mobile . "'  ORDER BY `id` DESC LIMIT 1 ");
            if (!empty($result)) {
                if ($result[0]['check_status'] == 1 || date('Ymd', time()) > date('Ymd', $result[0]['update_time'])) { //未检测
                    return $this->checkMobileApiOne($mobile);
                } else {
                    return true;
                }
            }
            return $this->checkMobileApiOne($mobile);
        } catch (\Exception $th) {
            //throw $th;
            exception($th);
        }
    }

    public function checkMobileApiOne($mobile)
    {
        $secret_id = '06FDC4A71F5E1FDE4C061DBA653DD2A5';
        $secret_key = 'ef0587df-86dc-459f-ad82-41c6446b27a5';
        $api = 'https://api.yunzhandata.com/api/deadnumber/v1.0/detect?sig=';
        $ts = date("YmdHis", time());
        $sig = sha1($secret_id . $secret_key . $ts);
        $api = $api . $sig . "&sid=" . $secret_id . "&skey=" . $secret_key . "&ts=" . $ts;
        // $check_mobile = $this->decrypt('6C38881649F7003B910582D1095DA821',$secret_id);
        // print_r($check_mobile);die;
        $data = [];
        $data = [
            'mobiles' => [encrypt($mobile, $secret_id)]
        ];
        $headers = [
            'Authorization:' . base64_encode($secret_id . ':' . $ts), 'Content-Type:application/json'
        ];
        $result = $this->sendRequest2($api, 'post', $data, $headers);
        // print_r(json_decode($data),true);
        // print_r($data);
        $result = json_decode($result, true);
        if ($result['code'] == 0) { //接口请求成功
            $mobiles = $result['mobiles'];
            if (!is_array($mobiles)) {
                return false;
            }
            foreach ($mobiles as $key => $value) {
                $mobile = decrypt($value['mobile'], $secret_id);
                $check_result = $value['mobileStatus'];
                $check_status = 2;
                if ($check_result == 2) { //实号
                    Db::table('yx_mobile')->where(['mobile' => $mobile])->delete();
                    Db::table('yx_real_mobile')->where(['mobile' => $mobile])->delete();
                    Db::table('yx_real_mobile')->insert([
                        'mobile' => $mobile,
                        'check_result' => 3,
                        'check_status' => $check_status,
                        'update_time' => time(),
                        'create_time' => time()
                    ]);
                    return false;
                } else {
                    Db::table('yx_real_mobile')->where(['mobile' => $mobile])->delete();
                    Db::table('yx_mobile')->where(['mobile' => $mobile])->delete();
                    Db::table('yx_mobile')->insert([
                        'mobile' => $mobile,
                        'check_result' => $check_result,
                        'check_status' => $check_status,
                        'update_time' => time(),
                        'create_time' => time()
                    ]);
                    if ($check_result == 0) {
                        return true;
                    } else {
                        return false; //疑似空号
                    }
                }
            }
        }
        return false;
    }

    public function checkMobileApi($mobiledata = [])
    {
        $real_mobile = [];
        $empty_mobile = [];
        $secret_id = '06FDC4A71F5E1FDE4C061DBA653DD2A5';
        $secret_key = 'ef0587df-86dc-459f-ad82-41c6446b27a5';
        $api = 'https://api.yunzhandata.com/api/deadnumber/v1.0/detect?sig=';
        $ts = date("YmdHis", time());
        $sig = sha1($secret_id . $secret_key . $ts);
        $api = $api . $sig . "&sid=" . $secret_id . "&skey=" . $secret_key . "&ts=" . $ts;
        // $check_mobile = $this->decrypt('6C38881649F7003B910582D1095DA821',$secret_id);
        // print_r($check_mobile);die;
        $data = [];
        $check_mobile_data = [];
        $j = 1;
        foreach ($mobiledata as $key => $value) {
            $check_mobile_data[] = encrypt($value, $secret_id);
            $j++;
            if ($j > 2000) {
                $data = [
                    'mobiles' => $check_mobile_data
                ];
                $headers = [
                    'Authorization:' . base64_encode($secret_id . ':' . $ts), 'Content-Type:application/json'
                ];
                $result = $this->sendRequest2($api, 'post', $data, $headers);
                // print_r(json_decode($data),true);
                // print_r($data);
                $result = json_decode($result, true);
                if ($result['code'] == 0) { //接口请求成功
                    $mobiles = $result['mobiles'];
                    foreach ($mobiles as $key => $value) {
                        $mobile = decrypt($value['mobile'], $secret_id);
                        $check_result = $value['mobileStatus'];
                        $check_status = 2;
                        if ($check_result == 2) { //实号
                            Db::table('yx_mobile')->where(['mobile' => $mobile])->delete();
                            Db::table('yx_real_mobile')->where(['mobile' => $mobile])->delete();
                            Db::table('yx_real_mobile')->insert([
                                'mobile' => $mobile,
                                'check_result' => 3,
                                'check_status' => $check_status,
                                'update_time' => time(),
                                'create_time' => time()
                            ]);
                            // return false;
                            $real_mobile[] = $mobile;
                        } else {
                            Db::table('yx_real_mobile')->where(['mobile' => $mobile])->delete();
                            Db::table('yx_mobile')->where(['mobile' => $mobile])->delete();
                            Db::table('yx_mobile')->insert([
                                'mobile' => $mobile,
                                'check_result' => $check_result,
                                'check_status' => $check_status,
                                'update_time' => time(),
                                'create_time' => time()
                            ]);
                            $empty_mobile[] = $mobile;
                        }
                    }
                } else {
                    $empty_mobile = $mobiledata;
                }
                $check_mobile_data = [];
                $j = 1;
            }
        }
        if (!empty($check_mobile_data)) {
            $data = [
                'mobiles' => $check_mobile_data
            ];
            $headers = [
                'Authorization:' . base64_encode($secret_id . ':' . $ts), 'Content-Type:application/json'
            ];
            $result = $this->sendRequest2($api, 'post', $data, $headers);
            // print_r(json_decode($data),true);
            // print_r($data);
            $result = json_decode($result, true);
            if ($result['code'] == 0) { //接口请求成功
                $mobiles = $result['mobiles'];
                foreach ($mobiles as $key => $value) {
                    $mobile = decrypt($value['mobile'], $secret_id);
                    $check_result = $value['mobileStatus'];
                    $check_status = 2;
                    if ($check_result == 2) { //实号
                        Db::table('yx_mobile')->where(['mobile' => $mobile])->delete();
                        Db::table('yx_real_mobile')->where(['mobile' => $mobile])->delete();
                        Db::table('yx_real_mobile')->insert([
                            'mobile' => $mobile,
                            'check_result' => 3,
                            'check_status' => $check_status,
                            'update_time' => time(),
                            'create_time' => time()
                        ]);
                        // return false;
                        $real_mobile[] = $mobile;
                    } else {
                        Db::table('yx_real_mobile')->where(['mobile' => $mobile])->delete();
                        Db::table('yx_mobile')->where(['mobile' => $mobile])->delete();
                        Db::table('yx_mobile')->insert([
                            'mobile' => $mobile,
                            'check_result' => $check_result,
                            'check_status' => $check_status,
                            'update_time' => time(),
                            'create_time' => time()
                        ]);
                        $empty_mobile[] = $mobile;
                    }
                }
            } else {
                $empty_mobile = $mobiledata;
            }
        }

        return ['real_mobile' => $real_mobile, 'empty_mobile' => $empty_mobile];
    }

    function sendRequest2($requestUrl, $method = 'get', $data = [], $headers)
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

    //书写游戏任务日志并写入通道
    public function createGameMessageSendTaskLog()
    {
        $this->redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        // date_default_timezone_set('PRC');
        $redisMessageMarketingSend = 'index:meassage:game:sendtask';
        // for ($i = 6; $i < 6028; $i++) {
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
            $mobilesend  = explode(',', $sendTask['mobile_content']);
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
            // if (file_exists(realpath("") . '/tasklog/game/' . $sendTask['task_no'] . ".txt")) {
            //     continue;
            // }
            // $myfile = fopen(realpath("") . '/tasklog/game/' . $sendTask['task_no'] . ".txt", "w");
            // if (!empty($sendTask['content'])) {

            // }

            for ($i = 0; $i < count($mobilesend); $i++) {
                $send_log   = [];
                $channel_id = 0;
                $channel_id = 14;
                if (checkMobile(trim($mobilesend[$i])) == true) {
                    $prefix = substr(trim($mobilesend[$i]), 0, 7);
                    $res    = Db::query("SELECT `source`,`province_id`,`province` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                    $newres = array_shift($res);
                    //游戏通道分流
                    /*   if ($newres) {
                    if ($newres['source'] == 2) { //米加联通营销
                    $channel_id = 28;
                    } else if ($newres['source'] == 1) { //蓝鲸
                    $channel_id = 14;
                    } else if ($newres['source'] == 3) { //米加电信营销
                    $channel_id = 29;
                    }
                    } */

                    // print_r($newres);
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
                            'content'     => $sendTask['task_content'],
                            'Submit_time' => date('ymdHis', time()),
                            'mobile'      => $send_log['mobile'],
                            'uid'         => $sendTask['uid'],
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
                            'channel_id'   => $channel_id,
                            'create_time'  => time(),
                        ];
                        $sendmessage = [
                            'mobile'      => $mobilesend[$i],
                            'title'       => $sendTask['task_name'],
                            'mar_task_id' => $sendTask['id'],
                            'content'     => $sendTask['task_content'],
                            'channel_id'  => $channel_id,
                        ];
                        $push_messages[] = $sendmessage; //实际发送队列
                        /*           $min = 100 - floor(4.5 / 5.2 * 100);
                    $max = mt_rand($min - 1, $min + 1);
                    $num     = mt_rand(0, 100);
                    if ($num <= $max) { //扣量
                    if (in_array($mobilesend[$i], [18339998120, 13812895012])) {
                    $push_messages[] = $sendmessage; //实际发送队列
                    } else {
                    // $push_messages[] = $sendmessage; //实际发送队列
                    $channel_calculate =  $this->redis->get('index:meassage:calculate:' . $channel_id);
                    $channel_calculate = json_decode($channel_calculate, true);

                    if (isset($channel_calculate['status'])) {
                    $a = mt_rand(0, max($channel_calculate['status']));
                    asort($channel_calculate['status']);
                    foreach ($channel_calculate['status'] as $cal => $late) {
                    if ($a <= $late) {
                    $send_log['status_message'] = $cal; //推送到虚拟不发送队列
                    break;
                    }
                    }
                    $this->redis->rPush('index:meassage:game:waitcmppdeliver', json_encode([
                    'Stat'        => $send_log['status_message'],
                    'send_msgid'  => [$sendTask['send_msg_id']],
                    'Done_time'   => date('ymdHis', time() + mt_rand($channel_calculate['min_time'], $channel_calculate['max_time'])),
                    'content'     =>  $sendTask['task_content'],
                    'Submit_time' => date('ymdHis', time()),
                    'mobile'      => $send_log['mobile'],
                    'uid'         =>  $sendTask['uid'],
                    'mar_task_id' => $sendTask['id'],
                    ]));
                    } else {
                    $push_messages[] = $sendmessage; //实际发送队列
                    }
                    }
                    // die;
                    } else { //不扣量
                    $push_messages[] = $sendmessage; //实际发送队列
                    } */
                    }

                    // $txt = json_encode($send_log) . "\n";
                    // fwrite($myfile, $txt);
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
                    // fwrite($myfile, $txt);
                    $this->redis->rPush('index:meassage:game:waitcmppdeliver:' . $sendTask['uid'], json_encode([
                        'Stat'        => $send_log['status_message'],
                        'send_msgid'  => [$sendTask['send_msg_id']],
                        'Done_time'   => date('ymdHis', time()),
                        'content'     => $sendTask['task_content'],
                        'Submit_time' => date('ymdHis', time()),
                        'mobile'      => $send_log['mobile'],
                        'uid'         => $sendTask['uid'],
                        'mar_task_id' => $sendTask['id'],
                    ]));
                }
            }

            Db::startTrans();
            try {
                Db::table('yx_user_send_game_task')->where('id', $sendTask['id'])->update(['real_num' => $real_num, 'send_status' => 3]);
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
            // fclose($myfile);
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
                    $time     = strtotime(date("Y-m-d 0:00:00", time()));
                    $sql      = "SELECT `id`,`task_no`,`uid` FROM ";
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
                        // print_r($request_url);
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

    public function updateLogNew($channel_id)
    {
        $redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        $redisMessageCodeSend = 'index:meassage:code:new:deliver:' . $channel_id; //验证码发送任务rediskey
        if (empty($channel_id)) {
            exit;
        }
        $channel              = $this->getChannelinfo($channel_id);
        //  $redis->rpush($redisMessageCodeSend, '{"msg_id":"13000710020200925164103169853","title":"","mobile":"13236800231","mar_task_id":406591,"uid":291,"content":"\u3010\u9a70\u52a0\u6c7d\u8f66\u670d\u52a1\u4e2d\u5fc3\u3011\u5c0a\u656c\u7684\u9a70\u52a0\u4f1a\u5458\uff0c\u60a8\u7684299\u5143\u7f24\u7eb7\u62b5\u6263\u5238\u5305\u4e2d\u8fd8\u6709\u793c\u5238\u5c1a\u672a\u4f7f\u7528\u3002\u767b\u5f55\u5fae\u4fe1\u516c\u4f17\u53f7\u201c\u9a70\u52a0\u6c7d\u8f66\u670d\u52a1\u4e2d\u5fc3\u201d\u67e5\u770b\u793c\u5238\u8be6\u60c5\u3002\u70b9\u51fb http:\/\/mrw.so\/6r5hHO \u5373\u523b\u9884\u7ea6\u95e8\u5e97\uff0c\u9000\u8ba2\u56deTD","from":"yx_user_send_task","my_submit_time":1601031821,"Msg_Id":"26306304647135634","Stat":"REJECT\u0000","Submit_time":"0101010000","Done_time":"2009251904","receive_time":1601031842,"develop_no":""}');
        // $redis->rpush($redisMessageCodeSend, '{"mobile":"15201926171","mar_task_id":"1","content":"Hi, \u4eb2\u7231\u7684\u4f1a\u5458\uff0c\u597d\u4e45\u4e0d\u89c1\uff0c\u60a8\u5df2\u7ecf\u6709\u4e09\u4e2a\u6708\u6ca1\u6765\u62a4\u7406\u4e86\uff0c\u79cb\u51ac\u5df2\u8fd1\uff0c\u6362\u5b63\u5f53\u524d\uff0c\u5728\u808c\u80a4\u9700\u8981\u201c\u8fdb\u8865\u201d\u7684\u5b63\u8282\u91cc\uff0c\u6765\u7f8e\u7530\u5373\u523b\u5f00\u542f\u6df1\u5ea6\u8865\u6c34\u6a21\u5f0f\u5427\uff01\u8054\u7cfb\u60a8\u8eab\u8fb9\u7684\u4e13\u5c5e\u5ba2\u6237\u7ecf\u7406\u6216\u62e8\u6253\u9884\u7ea6\u70ed\u7ebf 400-820-6142 \u56deT\u9000\u8ba2\u3010\u7f8e\u4e3d\u7530\u56ed\u3011","my_submit_time":1597248000,"Msg_Id":"2059229824357040146","Stat":"DELIVRD","Submit_time":"2007211521","Done_time":"2007211521","receive_time":1595316110,"from":"yx_user_send_task","uid":"1","send_msg_id":"J343300020200731100217169012"}');
        if ($channel['channel_type'] != 2) {
            exit;
        }
        $time = strtotime('2020-08-15');
        try {
            while (true) {
                $send_log = $redis->lpop($redisMessageCodeSend);
                // $redis->rpush($redisMessageCodeSend, $send_log);
                $send_log = json_decode($send_log, true);
                // print_r($send_log);die;
                if (empty($send_log)) {
                    usleep(50000);
                    continue;
                }
                if (!isset($send_log['mar_task_id']) || empty($send_log['mar_task_id'])) {
                    continue;
                }
                if (!isset($send_log['task_no'])) {
                    // echo "SELECT `task_no` FROM '".$send_log['from']."' WHERE `id` =  ".$send_log['mar_task_id'];
                    $task = Db::query("SELECT `task_no` FROM " . $send_log['from'] . " WHERE `id` =  " . $send_log['mar_task_id']);

                    if (empty($task)) {
                        continue;
                    }
                    $send_log['task_no'] = $task[0]['task_no'];
                }
                $new_key = '';
                $send_log['from'] = isset($send_log['from']) ? $send_log['from'] : '';
                $new_key = $send_log['from'] . ":" . $send_log['mar_task_id'] . ":" . $send_log['mobile'];
                $strlen = 0;
                $strlen = mb_strlen($send_log['content']);
                if ($send_log['my_submit_time'] > $time) {
                    if ($strlen > 70) {
                        $allnum = 0;
                        $allnum = ceil($strlen / 67);
                        // echo $strlen;die;
                        $had_receipt = '';
                        $had_receipt = $redis->hget("index:message:receipt", $new_key);
                        $had_receipt = json_decode($had_receipt, true);

                        if (empty($had_receipt)) {
                            $had_receipt = [];
                            $had_receipt = [
                                'mobile' => $send_log['mobile'],
                                'task_no'        => trim($send_log['task_no']),
                                'uid' => $send_log['uid'],
                                'from' => $send_log['from'],
                                'mar_task_id' => $send_log['mar_task_id'],
                                'content' => $send_log['content'],
                                'my_submit_time' => $send_log['my_submit_time'],
                                'Submit_time' => $send_log['Submit_time'],
                                'Done_time' => $send_log['Done_time'],
                                'receive_time' => $send_log['receive_time'],
                                'develop_no' => isset($send_log['develop_no']) ? $send_log['develop_no'] : '',
                                'send_msg_id' => isset($send_log['send_msg_id']) ? $send_log['send_msg_id'] : '',
                                'Stat' => [trim($send_log['Stat'])],
                                'send_num'          => isset($send_log['send_num']) ? $send_log['send_num'] : 0,
                                'channel_id' => $channel_id
                            ];
                            $had_receipt = $redis->hset("index:message:receipt", $new_key, json_encode($had_receipt));
                        } else {
                            array_push($had_receipt['Stat'], trim($send_log['Stat']));
                            if (count($had_receipt['Stat']) == $allnum) {
                                $redis->rpush("index:message:receipt:" . $had_receipt['from'], json_encode($had_receipt));
                                $redis->hdel("index:message:receipt", $new_key);
                            } else {
                                $had_receipt = $redis->hset("index:message:receipt", $new_key, json_encode($had_receipt));
                            }
                        }
                    } else { //写入回执通道
                        $had_receipt = [];
                        $had_receipt = [
                            'mobile' => $send_log['mobile'],
                            'task_no'        => trim($send_log['task_no']),
                            'uid' => $send_log['uid'],
                            'from' => $send_log['from'],
                            'mar_task_id' => $send_log['mar_task_id'],
                            'content' => $send_log['content'],
                            'my_submit_time' => $send_log['my_submit_time'],
                            'Submit_time' => $send_log['Submit_time'],
                            'Done_time' => $send_log['Done_time'],
                            'receive_time' => $send_log['receive_time'],
                            'send_msg_id' => isset($send_log['send_msg_id']) ? $send_log['send_msg_id'] : '',
                            'Stat' => [trim($send_log['Stat'])],
                            'send_num'          => isset($send_log['send_num']) ? $send_log['send_num'] : 0,
                            'channel_id' => $channel_id
                        ];
                        $redis->rpush("index:message:receipt:" . $had_receipt['from'], json_encode($had_receipt));
                    }
                } else {
                    if ($strlen > 70) {
                        $allnum = 0;
                        $allnum = ceil($strlen / 67);
                        // echo $strlen;die;
                        $had_receipt = '';
                        $had_receipt = $redis->hget("index:message:receipt", $new_key);
                        $had_receipt = json_decode($had_receipt, true);

                        if (empty($had_receipt)) {
                            $had_receipt = [];
                            $had_receipt = [
                                'mobile' => $send_log['mobile'],
                                'task_no'        => isset($send_log['task_no']) ? $send_log['task_no'] : '',
                                'uid' => $send_log['uid'],
                                'from' => $send_log['from'],
                                'mar_task_id' => $send_log['mar_task_id'],
                                'content' => $send_log['content'],
                                'my_submit_time' => $send_log['my_submit_time'],
                                'Submit_time' => $send_log['Submit_time'],
                                'Done_time' => $send_log['Done_time'],
                                'receive_time' => $send_log['receive_time'],
                                'develop_no' => isset($send_log['develop_no']) ? $send_log['develop_no'] : '',
                                'send_msg_id' => isset($send_log['send_msg_id']) ? $send_log['send_msg_id'] : '',
                                'Stat' => [trim($send_log['Stat'])],
                                'send_num'          => isset($send_log['send_num']) ? $send_log['send_num'] : 0,
                                'channel_id' => $channel_id
                            ];
                            $redis->rpush("index:message:receipt:" . $had_receipt['from'], json_encode($had_receipt));
                        } else {
                            /*  array_push($had_receipt['Stat'], trim($send_log['Stat']));
                            if (count($had_receipt['Stat']) == $allnum) {
                                $redis->rpush("index:message:receipt:" . $had_receipt['from'], json_encode($had_receipt));
                                $redis->hdel("index:message:receipt", $new_key);
                            } else {
                                $had_receipt = $redis->hset("index:message:receipt", $new_key, json_encode($had_receipt));
                            } */
                        }
                    } else { //写入回执通道
                        $had_receipt = [];
                        $had_receipt = [
                            'mobile' => $send_log['mobile'],
                            'task_no'        => isset($send_log['task_no']) ? $send_log['task_no'] : '',
                            'uid' => $send_log['uid'],
                            'from' => $send_log['from'],
                            'mar_task_id' => $send_log['mar_task_id'],
                            'content' => $send_log['content'],
                            'my_submit_time' => $send_log['my_submit_time'],
                            'Submit_time' => $send_log['Submit_time'],
                            'Done_time' => $send_log['Done_time'],
                            'receive_time' => $send_log['receive_time'],
                            'send_msg_id' => isset($send_log['send_msg_id']) ? $send_log['send_msg_id'] : '',
                            'Stat' => [trim($send_log['Stat'])],
                            'send_num'          => isset($send_log['send_num']) ? $send_log['send_num'] : 0,
                            'channel_id' => $channel_id
                        ];
                        $redis->rpush("index:message:receipt:" . $had_receipt['from'], json_encode($had_receipt));
                    }
                }

                // print_r($send_log);die;
            }
        } catch (\Exception $th) {
            //throw $th;
            $redis->rpush($redisMessageCodeSend, json_encode($send_log));
            exception($th);
        }
    }

    public function receiptUpdateNew()
    {
        $redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        // $redis->hset('index:message:receipt', 'yx_user_send_task:406886:13505118288', '{"mobile":"13505118288","task_no":"mar20092516414017553850","uid":291,"from":"yx_user_send_task","mar_task_id":406886,"content":"\u3010\u9a70\u52a0\u6c7d\u8f66\u670d\u52a1\u4e2d\u5fc3\u3011\u5c0a\u656c\u7684\u9a70\u52a0\u4f1a\u5458\uff0c\u60a8\u7684299\u5143\u7f24\u7eb7\u62b5\u6263\u5238\u5305\u4e2d\u8fd8\u6709\u793c\u5238\u5c1a\u672a\u4f7f\u7528\u3002\u767b\u5f55\u5fae\u4fe1\u516c\u4f17\u53f7\u201c\u9a70\u52a0\u6c7d\u8f66\u670d\u52a1\u4e2d\u5fc3\u201d\u67e5\u770b\u793c\u5238\u8be6\u60c5\u3002\u70b9\u51fb http:\/\/mrw.so\/6r5hHO \u5373\u523b\u9884\u7ea6\u95e8\u5e97\uff0c\u9000\u8ba2\u56deTD","my_submit_time":1601026996,"Submit_time":"0101010000","Done_time":"2009251743","receive_time":1601026999,"develop_no":"","send_msg_id":"13000710020200925164140169184","Stat":["DELIVRD"],"send_num":100,"channel_id":"145"}');
        try {
            while (true) {
                $receipts = $redis->HGETALL('index:message:receipt');
                if (empty($receipts)) {
                    sleep(10);
                }
                foreach ($receipts as $key => $value) {
                    $receipt = json_decode($value, true);

                    if (time() - $receipt['my_submit_time'] > 1800) {
                        $strlen = mb_strlen($receipt['content']);
                        if ($strlen > 70) {
                            $allnum = 0;
                            $allnum = ceil($strlen / 67);

                            $receipt_stat = [];
                            for ($i = 0; $i < $allnum; $i++) {
                                array_push($receipt_stat, trim($receipt['Stat'][0]));
                            }
                            $had_receipt = [];
                            $had_receipt = [
                                'mobile' => $receipt['mobile'],
                                'task_no'        => trim($receipt['task_no']),
                                'uid' => $receipt['uid'],
                                'from' => $receipt['from'],
                                'mar_task_id' => $receipt['mar_task_id'],
                                'content' => $receipt['content'],
                                'my_submit_time' => $receipt['my_submit_time'],
                                'Submit_time' => $receipt['Submit_time'],
                                'Done_time' => $receipt['Done_time'],
                                'receive_time' => $receipt['receive_time'],
                                'send_msg_id' => isset($receipt['send_msg_id']) ? $receipt['send_msg_id'] : '',
                                'Stat' => $receipt_stat,
                                'send_num'          => isset($receipt['send_num']) ? $receipt['send_num'] : 0,
                                'channel_id' => $receipt['channel_id']
                            ];
                            // print_r($had_receipt);
                            $redis->rpush("index:message:receipt:" . $had_receipt['from'], json_encode($had_receipt));
                            $redis->hdel("index:message:receipt", $key);
                            // array_push($had_receipt['Stat'], trim($receipt['Stat']));
                        }
                    }
                }
                // die;
            }
        } catch (\Exception $th) {
            //throw $th;
            exception($th);
        }
    }

    public function insertInToTable()
    {
        $redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        /*  $redis->rpush('index:message:receipt:yx_user_send_task', '{"mobile":"15201926171","task_no":"mar20073110021507111729","uid":"1","from":"yx_user_send_task","mar_task_id":"1","content":"Hi, \u4eb2\u7231\u7684\u4f1a\u5458\uff0c\u597d\u4e45\u4e0d\u89c1\uff0c\u60a8\u5df2\u7ecf\u6709\u4e09\u4e2a\u6708\u6ca1\u6765\u62a4\u7406\u4e86\uff0c\u79cb\u51ac\u5df2\u8fd1\uff0c\u6362\u5b63\u5f53\u524d\uff0c\u5728\u808c\u80a4\u9700\u8981\u201c\u8fdb\u8865\u201d\u7684\u5b63\u8282\u91cc\uff0c\u6765\u7f8e\u7530\u5373\u523b\u5f00\u542f\u6df1\u5ea6\u8865\u6c34\u6a21\u5f0f\u5427\uff01\u8054\u7cfb\u60a8\u8eab\u8fb9\u7684\u4e13\u5c5e\u5ba2\u6237\u7ecf\u7406\u6216\u62e8\u6253\u9884\u7ea6\u70ed\u7ebf 400-820-6142 \u56deT\u9000\u8ba2\u3010\u7f8e\u4e3d\u7530\u56ed\u3011","my_submit_time":1597427516,"Submit_time":"2007211521","Done_time":"2007211521","receive_time":1595316110,"develop_no":"","send_msg_id":"J343300020200731100217169012","Stat":["DELIVRD","DELIVRD"],"send_num":0,"channel_id":"18"}'); */
        try {
            $time = strtotime('2020-08-15');
            while (true) {
                $receipt = $redis->lpop('index:message:receipt:yx_user_send_task');
                if (empty($receipt)) {
                    sleep(1);
                    continue;
                }

                $receipt = json_decode($receipt, true);
                if (isset($receipt['send_msg_id']) && !empty($receipt['send_msg_id'])) {

                    $strlen = 0;
                    $strlen = mb_strlen($receipt['content']);
                    if ($strlen > 70) {
                        $allnum = 0;
                        $allnum = ceil($strlen / 67);
                        if ($receipt['my_submit_time'] > $time) {

                            if ($receipt['send_num'] > 10) { //单批次超过10个号码
                                if (in_array('DELIVRD', $receipt['Stat'])) {
                                    for ($a = 0; $a < $allnum; $a++) {
                                        $redis->rpush('index:meassage:code:user:receive:' . $receipt['uid'], json_encode([
                                            'task_no'        => trim($receipt['task_no']),
                                            'status_message' => 'DELIVRD',
                                            'message_info'   => '发送成功',
                                            'mobile'         => trim($receipt['mobile']),
                                            'msg_id'         => trim($receipt['send_msg_id']),
                                            // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                                            'send_time'      => isset($receipt['receive_time']) ? date('Y-m-d H:i:s', trim($receipt['receive_time'])) : date('Y-m-d H:i:s', time()),
                                            'smsCount' => $allnum,
                                            'smsIndex' => $a + 1,
                                        ])); //写入用户带处理日志
                                    }
                                } else {
                                    for ($a = 0; $a < $allnum; $a++) {
                                        $redis->rpush('index:meassage:code:user:receive:' . $receipt['uid'], json_encode([
                                            'task_no'        => trim($receipt['task_no']),
                                            'status_message' => $receipt['Stat'][0],
                                            'message_info'   => '发送失败',
                                            'mobile'         => trim($receipt['mobile']),
                                            'msg_id'         => trim($receipt['send_msg_id']),
                                            // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                                            'send_time'      => isset($receipt['receive_time']) ? date('Y-m-d H:i:s', trim($receipt['receive_time'])) : date('Y-m-d H:i:s', time()),
                                            'smsCount' => $allnum,
                                            'smsIndex' => $a + 1,
                                        ])); //写入用户带处理日志
                                    }
                                }
                            } else {
                                $stat = array_unique($receipt['Stat']);
                                if (count($stat) > 1) { //多条不同回执
                                    //   print_r($stat);die;
                                    $stat = array_diff($stat, ['DELIVRD']);
                                    if ($stat[0] == 'MK:1006') {
                                        $stat[0] = 'DELIVRD';
                                        $message_info = '发送成功';
                                    } else {
                                        $message_info = '发送失败';
                                    }
                                    // print_r($stat);die;
                                    for ($a = 0; $a < $allnum; $a++) {
                                        $redis->rpush('index:meassage:code:user:receive:' . $receipt['uid'], json_encode([
                                            'task_no'        => trim($receipt['task_no']),
                                            'status_message' => $stat[0],
                                            'message_info'   => $message_info,
                                            'mobile'         => trim($receipt['mobile']),
                                            'msg_id'         => trim($receipt['send_msg_id']),
                                            // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                                            'send_time'      => isset($receipt['receive_time']) ? date('Y-m-d H:i:s', trim($receipt['receive_time'])) : date('Y-m-d H:i:s', time()),
                                            'smsCount' => $allnum,
                                            'smsIndex' => $a + 1,
                                        ])); //写入用户带处理日志
                                    }
                                } else {
                                    if ($stat[0] == 'DELIVRD') {
                                        $message_info = '发送成功';
                                    } else {
                                        $message_info = '发送失败';
                                    }
                                    for ($a = 0; $a < $allnum; $a++) {
                                        $redis->rpush('index:meassage:code:user:receive:' . $receipt['uid'], json_encode([
                                            'task_no'        => trim($receipt['task_no']),
                                            'status_message' => $stat[0],
                                            'message_info'   => $message_info,
                                            'mobile'         => trim($receipt['mobile']),
                                            'msg_id'         => trim($receipt['send_msg_id']),
                                            // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                                            'send_time'      => isset($receipt['receive_time']) ? date('Y-m-d H:i:s', trim($receipt['receive_time'])) : date('Y-m-d H:i:s', time()),
                                            'smsCount' => $allnum,
                                            'smsIndex' => $a + 1,
                                        ])); //写入用户带处理日志
                                    }
                                }
                            }
                        } else {
                            $strlen = 0;
                            $strlen = mb_strlen($receipt['content']);
                            $allnum = 1;
                            if ($strlen > 70) {
                                $allnum = 0;
                                $allnum = ceil($strlen / 67);
                            }
                            if (in_array('DELIVRD', $receipt['Stat'])) {
                                for ($a = 0; $a < $allnum; $a++) {
                                    $redis->rpush('index:meassage:code:user:receive:' . $receipt['uid'], json_encode([
                                        'task_no'        => trim($receipt['task_no']),
                                        'status_message' => 'DELIVRD',
                                        'message_info'   => '发送成功',
                                        'mobile'         => trim($receipt['mobile']),
                                        'msg_id'         => trim($receipt['send_msg_id']),
                                        // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                                        'send_time'      => isset($receipt['receive_time']) ? date('Y-m-d H:i:s', trim($receipt['receive_time'])) : date('Y-m-d H:i:s', time()),
                                        'smsCount' => $allnum,
                                        'smsIndex' => $a + 1,
                                    ])); //写入用户带处理日志
                                }
                            } else {
                                for ($a = 0; $a < $allnum; $a++) {
                                    $redis->rpush('index:meassage:code:user:receive:' . $receipt['uid'], json_encode([
                                        'task_no'        => trim($receipt['task_no']),
                                        'status_message' => $receipt['Stat'][0],
                                        'message_info'   => '发送失败',
                                        'mobile'         => trim($receipt['mobile']),
                                        'msg_id'         => trim($receipt['send_msg_id']),
                                        // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                                        'send_time'      => isset($receipt['receive_time']) ? date('Y-m-d H:i:s', trim($receipt['receive_time'])) : date('Y-m-d H:i:s', time()),
                                        'smsCount' => $allnum,
                                        'smsIndex' => $a + 1,
                                    ])); //写入用户带处理日志
                                }
                            }
                        }
                    } else {
                        if (in_array('DELIVRD', $receipt['Stat'])) {
                            $redis->rpush('index:meassage:code:user:receive:' . $receipt['uid'], json_encode([
                                'task_no'        => trim($receipt['task_no']),
                                'status_message' => 'DELIVRD',
                                'message_info'   => '发送成功',
                                'mobile'         => trim($receipt['mobile']),
                                'msg_id'         => trim($receipt['send_msg_id']),
                                // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                                'send_time'      => isset($receipt['receive_time']) ? date('Y-m-d H:i:s', trim($receipt['receive_time'])) : date('Y-m-d H:i:s', time()),
                                'smsCount' => 1,
                                'smsIndex' => 1,
                            ])); //写入用户带处理日志
                        } else {
                            $redis->rpush('index:meassage:code:user:receive:' . $receipt['uid'], json_encode([
                                'task_no'        => trim($receipt['task_no']),
                                'status_message' => 'DELIVRD',
                                'message_info'   => '发送成功',
                                'mobile'         => trim($receipt['mobile']),
                                'msg_id'         => trim($receipt['send_msg_id']),
                                // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                                'send_time'      => isset($receipt['receive_time']) ? date('Y-m-d H:i:s', trim($receipt['receive_time'])) : date('Y-m-d H:i:s', time()),
                                'smsCount' => 1,
                                'smsIndex' => 1,
                            ])); //写入用户带处理日志
                        }
                    }
                } else {
                    // in_array(trim($send_log['Stat']), ['REJECTD', 'REJECT', 'MA:0001', 'DB:0141'])
                    if (in_array('DELIVRD', $receipt['Stat']) || in_array('DELIVRD', $receipt['Stat'])) {
                        $redis->rpush('index:meassage:code:user:receive:' . $receipt['uid'], json_encode([
                            'task_no'        => trim($receipt['task_no']),
                            'status_message' => 'DELIVRD',
                            'message_info'   => '发送成功',
                            'mobile'         => trim($receipt['mobile']),
                            'msg_id'         => trim($receipt['send_msg_id']) ? trim($receipt['send_msg_id']) : '',
                            // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                            'send_time'      => isset($receipt['receive_time']) ? date('Y-m-d H:i:s', trim($receipt['receive_time'])) : date('Y-m-d H:i:s', time()),
                        ])); //写入用户带处理日志
                    } else {
                        $redis->rpush('index:meassage:code:user:receive:' . $receipt['uid'], json_encode([
                            'task_no'        => trim($receipt['task_no']),
                            'status_message' => 'DELIVRD',
                            'message_info'   => '发送成功',
                            'mobile'         => trim($receipt['mobile']),
                            'msg_id'         =>  trim($receipt['send_msg_id']) ? trim($receipt['send_msg_id']) : '',
                            // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                            'send_time'      => isset($receipt['receive_time']) ? date('Y-m-d H:i:s', trim($receipt['receive_time'])) : date('Y-m-d H:i:s', time()),
                        ])); //写入用户带处理日志
                    }
                }
                /*  {"mobile":"13564869264","title":"\u7f8e\u4e3d\u7530\u56ed\u8425\u9500\u77ed\u4fe1","mar_task_id":"1599","content":"\u611f\u8c22\u60a8\u5bf9\u4e8e\u7f8e\u4e3d\u7530\u56ed\u7684\u4fe1\u8d56\u548c\u652f\u6301\uff0c\u4e3a\u4e86\u7ed9\u60a8\u5e26\u6765\u66f4\u597d\u7684\u670d\u52a1\u4f53\u9a8c\uff0c\u7279\u9080\u60a8\u9488\u5bf9\u672c\u6b21\u670d\u52a1\u8fdb\u884c\u8bc4\u4ef7http:\/\/crmapp.beautyfarm.com.cn\/questionNaire1\/api\/qnnaire\/refct?id=534478\uff0c\u8bf7\u60a8\u572824\u5c0f\u65f6\u5185\u63d0\u4ea4\u6b64\u95ee\u5377\uff0c\u8c22\u8c22\u914d\u5408\u3002\u671f\u5f85\u60a8\u7684\u53cd\u9988\uff01\u5982\u9700\u5e2e\u52a9\uff0c\u656c\u8bf7\u81f4\u7535400-8206-142\uff0c\u56deT\u9000\u8ba2\u3010\u7f8e\u4e3d\u7530\u56ed\u3011","Msg_Id":"","Stat":"DELIVER","Submit_time":"191224164036","Done_time":"191224164236","from":"yx_user_send_code_task"} */
                $redis->rpush('index:meassage:code:cms:yx_user_send_task:deliver:', json_encode($receipt)); //写入通道处理日志
            }
        } catch (\Exception $th) {
            //throw $th;
            exception($th);
        }
    }

    public function deliverToReceiptForMarketing()
    {
        $redis = Phpredis::getConn();
        // $redis->rPush('index:meassage:code:cms:yx_user_send_task:deliver:','{"mobile":"13559319152","task_no":"mar20092516414541082566","uid":291,"from":"yx_user_send_task","mar_task_id":406942,"content":"\u3010\u9a70\u52a0\u6c7d\u8f66\u670d\u52a1\u4e2d\u5fc3\u3011\u5c0a\u656c\u7684\u9a70\u52a0\u4f1a\u5458\uff0c\u60a8\u7684299\u5143\u7f24\u7eb7\u62b5\u6263\u5238\u5305\u4e2d\u8fd8\u6709\u793c\u5238\u5c1a\u672a\u4f7f\u7528\u3002\u767b\u5f55\u5fae\u4fe1\u516c\u4f17\u53f7\u201c\u9a70\u52a0\u6c7d\u8f66\u670d\u52a1\u4e2d\u5fc3\u201d\u67e5\u770b\u793c\u5238\u8be6\u60c5\u3002\u70b9\u51fb http:\/\/mrw.so\/6r5hHO \u5373\u523b\u9884\u7ea6\u95e8\u5e97\uff0c\u9000\u8ba2\u56deTD","my_submit_time":1601026791,"Submit_time":"0101010000","Done_time":"2009251740","receive_time":1601026795,"develop_no":"","send_msg_id":"13000710020200925164146169245","Stat":["DELIVRD","DELIVRD"],"send_num":100,"channel_id":"145"}');
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        try {
            while (true) {
                $i = 1;
                $inserts = [];
                while (true) {
                    $deliver_message = $redis->lpop('index:meassage:code:cms:yx_user_send_task:deliver:');
                    if (empty($deliver_message)) {
                        break;
                    }
                    $deliver_message = json_decode($deliver_message, true);
                    foreach ($deliver_message['Stat'] as $key => $value) {
                        $data = [
                            'task_id'        => $deliver_message['mar_task_id'],
                            'mobile'         => $deliver_message['mobile'],
                            'real_message'   => $value,
                            'status_message' => $value,
                            'create_time'    => isset($deliver_message['receive_time']) ? $deliver_message['receive_time'] : time(),
                        ];
                        $inserts[] = $data;
                        $i++;
                        if ($i > 100) {
                            Db::table('yx_send_task_receipt')->insertAll($inserts);
                            $inserts = [];
                            $i = 1;
                        }
                    }
                }
                if (!empty($inserts)) {
                    Db::table('yx_send_task_receipt')->insertAll($inserts);
                    $inserts = [];
                    $i = 1;
                }
                sleep(1);
            }
        } catch (\Exception $th) {
            //throw $th;
            exception($th);
        }
    }

    public function insertInToTableBusiness()
    {
        $redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        /*  $redis->rpush('index:message:receipt:yx_user_send_task', '{"mobile":"15201926171","task_no":"mar20073110021507111729","uid":"1","from":"yx_user_send_task","mar_task_id":"1","content":"Hi, \u4eb2\u7231\u7684\u4f1a\u5458\uff0c\u597d\u4e45\u4e0d\u89c1\uff0c\u60a8\u5df2\u7ecf\u6709\u4e09\u4e2a\u6708\u6ca1\u6765\u62a4\u7406\u4e86\uff0c\u79cb\u51ac\u5df2\u8fd1\uff0c\u6362\u5b63\u5f53\u524d\uff0c\u5728\u808c\u80a4\u9700\u8981\u201c\u8fdb\u8865\u201d\u7684\u5b63\u8282\u91cc\uff0c\u6765\u7f8e\u7530\u5373\u523b\u5f00\u542f\u6df1\u5ea6\u8865\u6c34\u6a21\u5f0f\u5427\uff01\u8054\u7cfb\u60a8\u8eab\u8fb9\u7684\u4e13\u5c5e\u5ba2\u6237\u7ecf\u7406\u6216\u62e8\u6253\u9884\u7ea6\u70ed\u7ebf 400-820-6142 \u56deT\u9000\u8ba2\u3010\u7f8e\u4e3d\u7530\u56ed\u3011","my_submit_time":1597427516,"Submit_time":"2007211521","Done_time":"2007211521","receive_time":1595316110,"develop_no":"","send_msg_id":"J343300020200731100217169012","Stat":["DELIVRD","DELIVRD"],"send_num":0,"channel_id":"18"}'); */
        try {
            $time = strtotime('2020-08-15');
            while (true) {
                $receipt = $redis->lpop('index:message:receipt:yx_user_send_code_task');
                if (empty($receipt)) {
                    sleep(1);
                    continue;
                }

                $receipt = json_decode($receipt, true);
                if (isset($receipt['send_msg_id']) && !empty($receipt['send_msg_id'])) {

                    $strlen = 0;
                    $strlen = mb_strlen($receipt['content']);
                    if ($strlen > 70) {
                        $allnum = 0;
                        $allnum = ceil($strlen / 67);
                        if ($receipt['my_submit_time'] > $time) {

                            if ($receipt['send_num'] > 10) { //单批次超过10个号码
                                if (in_array('DELIVRD', $receipt['Stat'])) {
                                    for ($a = 0; $a < $allnum; $a++) {
                                        $redis->rpush('index:meassage:code:user:receive:' . $receipt['uid'], json_encode([
                                            'task_no'        => trim($receipt['task_no']),
                                            'status_message' => 'DELIVRD',
                                            'message_info'   => '发送成功',
                                            'mobile'         => trim($receipt['mobile']),
                                            'msg_id'         => trim($receipt['send_msg_id']),
                                            // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                                            'send_time'      => isset($receipt['receive_time']) ? date('Y-m-d H:i:s', trim($receipt['receive_time'])) : date('Y-m-d H:i:s', time()),
                                            'smsCount' => $allnum,
                                            'smsIndex' => $a + 1,
                                        ])); //写入用户带处理日志
                                    }
                                } else {
                                    for ($a = 0; $a < $allnum; $a++) {
                                        $redis->rpush('index:meassage:code:user:receive:' . $receipt['uid'], json_encode([
                                            'task_no'        => trim($receipt['task_no']),
                                            'status_message' => $receipt['Stat'][0],
                                            'message_info'   => '发送失败',
                                            'mobile'         => trim($receipt['mobile']),
                                            'msg_id'         => trim($receipt['send_msg_id']),
                                            // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                                            'send_time'      => isset($receipt['receive_time']) ? date('Y-m-d H:i:s', trim($receipt['receive_time'])) : date('Y-m-d H:i:s', time()),
                                            'smsCount' => $allnum,
                                            'smsIndex' => $a + 1,
                                        ])); //写入用户带处理日志
                                    }
                                }
                            } else {
                                $stat = array_unique($receipt['Stat']);
                                if (count($stat) > 1) { //多条不同回执
                                    //   print_r($stat);die;
                                    $stat = array_diff($stat, ['DELIVRD']);
                                    if ($stat[0] == 'MK:1006') {
                                        $stat[0] = 'DELIVRD';
                                        $message_info = '发送成功';
                                    } else {
                                        $message_info = '发送失败';
                                    }
                                    // print_r($stat);die;
                                    for ($a = 0; $a < $allnum; $a++) {
                                        $redis->rpush('index:meassage:code:user:receive:' . $receipt['uid'], json_encode([
                                            'task_no'        => trim($receipt['task_no']),
                                            'status_message' => $stat[0],
                                            'message_info'   => $message_info,
                                            'mobile'         => trim($receipt['mobile']),
                                            'msg_id'         => trim($receipt['send_msg_id']),
                                            // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                                            'send_time'      => isset($receipt['receive_time']) ? date('Y-m-d H:i:s', trim($receipt['receive_time'])) : date('Y-m-d H:i:s', time()),
                                            'smsCount' => $allnum,
                                            'smsIndex' => $a + 1,
                                        ])); //写入用户带处理日志
                                    }
                                } else {
                                    if ($stat[0] == 'DELIVRD') {
                                        $message_info = '发送成功';
                                    } else {
                                        $message_info = '发送失败';
                                    }
                                    for ($a = 0; $a < $allnum; $a++) {
                                        $redis->rpush('index:meassage:code:user:receive:' . $receipt['uid'], json_encode([
                                            'task_no'        => trim($receipt['task_no']),
                                            'status_message' => $stat[0],
                                            'message_info'   => $message_info,
                                            'mobile'         => trim($receipt['mobile']),
                                            'msg_id'         => trim($receipt['send_msg_id']),
                                            // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                                            'send_time'      => isset($receipt['receive_time']) ? date('Y-m-d H:i:s', trim($receipt['receive_time'])) : date('Y-m-d H:i:s', time()),
                                            'smsCount' => $allnum,
                                            'smsIndex' => $a + 1,
                                        ])); //写入用户带处理日志
                                    }
                                }
                            }
                        } else {
                            $strlen = 0;
                            $strlen = mb_strlen($receipt['content']);
                            $allnum = 1;
                            if ($strlen > 70) {
                                $allnum = 0;
                                $allnum = ceil($strlen / 67);
                            }
                            if (in_array('DELIVRD', $receipt['Stat'])) {
                                for ($a = 0; $a < $allnum; $a++) {
                                    $redis->rpush('index:meassage:code:user:receive:' . $receipt['uid'], json_encode([
                                        'task_no'        => trim($receipt['task_no']),
                                        'status_message' => 'DELIVRD',
                                        'message_info'   => '发送成功',
                                        'mobile'         => trim($receipt['mobile']),
                                        'msg_id'         => trim($receipt['send_msg_id']),
                                        // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                                        'send_time'      => isset($receipt['receive_time']) ? date('Y-m-d H:i:s', trim($receipt['receive_time'])) : date('Y-m-d H:i:s', time()),
                                        'smsCount' => $allnum,
                                        'smsIndex' => $a + 1,
                                    ])); //写入用户带处理日志
                                }
                            } else {
                                for ($a = 0; $a < $allnum; $a++) {
                                    $redis->rpush('index:meassage:code:user:receive:' . $receipt['uid'], json_encode([
                                        'task_no'        => trim($receipt['task_no']),
                                        'status_message' => $receipt['Stat'][0],
                                        'message_info'   => '发送失败',
                                        'mobile'         => trim($receipt['mobile']),
                                        'msg_id'         => trim($receipt['send_msg_id']),
                                        // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                                        'send_time'      => isset($receipt['receive_time']) ? date('Y-m-d H:i:s', trim($receipt['receive_time'])) : date('Y-m-d H:i:s', time()),
                                        'smsCount' => $allnum,
                                        'smsIndex' => $a + 1,
                                    ])); //写入用户带处理日志
                                }
                            }
                        }
                    } else {
                        if (in_array('DELIVRD', $receipt['Stat'])) {
                            $redis->rpush('index:meassage:code:user:receive:' . $receipt['uid'], json_encode([
                                'task_no'        => trim($receipt['task_no']),
                                'status_message' => 'DELIVRD',
                                'message_info'   => '发送成功',
                                'mobile'         => trim($receipt['mobile']),
                                'msg_id'         => trim($receipt['send_msg_id']),
                                // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                                'send_time'      => isset($receipt['receive_time']) ? date('Y-m-d H:i:s', trim($receipt['receive_time'])) : date('Y-m-d H:i:s', time()),
                                'smsCount' => 1,
                                'smsIndex' => 1,
                            ])); //写入用户带处理日志
                        } else {
                            $redis->rpush('index:meassage:code:user:receive:' . $receipt['uid'], json_encode([
                                'task_no'        => trim($receipt['task_no']),
                                'status_message' => 'DELIVRD',
                                'message_info'   => '发送成功',
                                'mobile'         => trim($receipt['mobile']),
                                'msg_id'         => trim($receipt['send_msg_id']),
                                // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                                'send_time'      => isset($receipt['receive_time']) ? date('Y-m-d H:i:s', trim($receipt['receive_time'])) : date('Y-m-d H:i:s', time()),
                                'smsCount' => 1,
                                'smsIndex' => 1,
                            ])); //写入用户带处理日志
                        }
                    }
                } else {
                    // in_array(trim($send_log['Stat']), ['REJECTD', 'REJECT', 'MA:0001', 'DB:0141'])
                    if (in_array('DELIVRD', $receipt['Stat']) || in_array('DELIVRD', $receipt['Stat'])) {
                        $redis->rpush('index:meassage:code:user:receive:' . $receipt['uid'], json_encode([
                            'task_no'        => trim($receipt['task_no']),
                            'status_message' => 'DELIVRD',
                            'message_info'   => '发送成功',
                            'mobile'         => trim($receipt['mobile']),
                            'msg_id'         => trim($receipt['send_msg_id']) ? trim($receipt['send_msg_id']) : '',
                            // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                            'send_time'      => isset($receipt['receive_time']) ? date('Y-m-d H:i:s', trim($receipt['receive_time'])) : date('Y-m-d H:i:s', time()),
                        ])); //写入用户带处理日志
                    } else {
                        $redis->rpush('index:meassage:code:user:receive:' . $receipt['uid'], json_encode([
                            'task_no'        => trim($receipt['task_no']),
                            'status_message' => 'DELIVRD',
                            'message_info'   => '发送成功',
                            'mobile'         => trim($receipt['mobile']),
                            'msg_id'         =>  trim($receipt['send_msg_id']) ? trim($receipt['send_msg_id']) : '',
                            // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                            'send_time'      => isset($receipt['receive_time']) ? date('Y-m-d H:i:s', trim($receipt['receive_time'])) : date('Y-m-d H:i:s', time()),
                        ])); //写入用户带处理日志
                    }
                }
                /*  {"mobile":"13564869264","title":"\u7f8e\u4e3d\u7530\u56ed\u8425\u9500\u77ed\u4fe1","mar_task_id":"1599","content":"\u611f\u8c22\u60a8\u5bf9\u4e8e\u7f8e\u4e3d\u7530\u56ed\u7684\u4fe1\u8d56\u548c\u652f\u6301\uff0c\u4e3a\u4e86\u7ed9\u60a8\u5e26\u6765\u66f4\u597d\u7684\u670d\u52a1\u4f53\u9a8c\uff0c\u7279\u9080\u60a8\u9488\u5bf9\u672c\u6b21\u670d\u52a1\u8fdb\u884c\u8bc4\u4ef7http:\/\/crmapp.beautyfarm.com.cn\/questionNaire1\/api\/qnnaire\/refct?id=534478\uff0c\u8bf7\u60a8\u572824\u5c0f\u65f6\u5185\u63d0\u4ea4\u6b64\u95ee\u5377\uff0c\u8c22\u8c22\u914d\u5408\u3002\u671f\u5f85\u60a8\u7684\u53cd\u9988\uff01\u5982\u9700\u5e2e\u52a9\uff0c\u656c\u8bf7\u81f4\u7535400-8206-142\uff0c\u56deT\u9000\u8ba2\u3010\u7f8e\u4e3d\u7530\u56ed\u3011","Msg_Id":"","Stat":"DELIVER","Submit_time":"191224164036","Done_time":"191224164236","from":"yx_user_send_code_task"} */
                $redis->rpush('index:meassage:code:cms:yx_user_send_task:deliver:', json_encode($receipt)); //写入通道处理日志
            }
        } catch (\Exception $th) {
            //throw $th;
            exception($th);
        }
    }

    public function updateLog($channel_id)
    {
        $redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        $redisMessageCodeSend = 'index:meassage:code:new:deliver:' . $channel_id; //验证码发送任务rediskey
        $channel              = $this->getChannelinfo($channel_id);
        // $redis->rpush($redisMessageCodeSend, '{"mobile":"15172413692","mar_task_id":3270165,"content":"\u3010\u5954\u9a70\u91d1\u878d\u3011\u5c0a\u656c\u7684\u5ba2\u6237\uff0c\u8bf7\u70b9\u51fb\u4ee5\u4e0b\u94fe\u63a5\u8fdb\u884c\u7533\u8bf7\u4fe1\u606f\u7684\u786e\u8ba4:\u00a0https:\/\/mbfs-jinrongtong-prod.i.daimler.com\/Mobile\/e-app\/#\/****\u00a0\u3002\u5982\u6709\u95ee\u9898\uff0c\u8bf7\u8054\u7cfb\u60a8\u7684\u91d1\u878d\u987e\u95ee\u5f20** 158********","from":"yx_user_send_task","send_msg_id":"","uid":1,"send_num":1,"task_no":"bus20090318033787230830","develop_code":"42219","my_submit_time":1599127445,"Msg_Id":"24458206221828038676","Stat":"DELIVRD","Submit_time":"2009031804","Done_time":"2009031804","receive_time":1599127450,"develop_no":"42219"}');

        // $request_url = 'http://116.228.60.189:15901/rtreceive?task_no=bus19123111560308152071&status_message=E:CHAN&mobile=18643198590&send_time=1912311333';
        // sendRequest($request_url);
        try {
            if ($channel['channel_type'] == 2) { //cmpp的
                while (true) {
                    $send_log = $redis->lpop($redisMessageCodeSend);
                    if (empty($send_log)) {
                        continue;
                    }
                    $stat = '';
                    $Received = updateReceivedForMessage();
                    // $redis->rpush($redisMessageCodeSend, $send_log);
                    $send_log = json_decode($send_log, true);
                    // print_r($Received);die;
                    //获取通道属性
                    if (!isset($send_log['mar_task_id']) || empty($send_log['mar_task_id'])) {
                        continue;
                    }
                    if (!empty($send_log['from']) && $send_log['from'] == 'yx_sfl_send_task') {
                        continue;
                    }
                    $sql = "SELECT `send_msg_id`,`task_no`,`uid`,`create_time` FROM ";
                    if ($channel['business_id'] == 5) { //营销
                        if (isset($send_log['from'])) {
                            $sql .= " " . $send_log['from'] . " ";
                        } else {
                            $sql .= " yx_user_send_task ";
                        }
                    } elseif ($channel['business_id'] == 6) { // 行业
                        if (isset($send_log['from'])) {
                            $sql .= " " . $send_log['from'] . " ";
                        } else {
                            $sql .= " yx_user_send_code_task ";
                        }
                        // $sql .= " yx_user_send_code_task ";
                    } elseif ($channel['business_id'] == 9) { //游戏
                        if (isset($send_log['from'])) {
                            $sql .= " " . $send_log['from'] . " ";
                        } else {
                            $sql .= " yx_user_send_game_task ";
                        }
                        // $sql .= " yx_user_send_game_task ";
                    }
                    $sql .= "WHERE `id` = " . $send_log['mar_task_id'];
                    $task = Db::query($sql);
                    // print_r($task);die;
                    if (empty($task)) {
                        $sql = "SELECT `send_msg_id`,`task_no`,`uid`,`create_time` FROM ";
                        if ($channel['business_id'] == 5) { //营销
                            $sql .= " yx_user_send_task ";
                        } elseif ($channel['business_id'] == 6) { // 行业
                            $sql .= " yx_user_send_code_task ";
                            // $sql .= " yx_user_send_code_task ";
                        } elseif ($channel['business_id'] == 9) { //游戏
                            $sql .= " yx_user_send_game_task ";
                            // $sql .= " yx_user_send_game_task ";
                        }
                        $sql .= "WHERE `id` = " . $send_log['mar_task_id'];
                        $task = Db::query($sql);
                        if (empty($task)) {
                            if (!empty($send_log['task_no'])) {
                                if (strpos($send_log['task_no'], 'bus') !== false) {
                                    $sql = "SELECT `send_msg_id`,`task_no`,`uid`,`create_time` FROM  yx_user_send_code_task WHERE `task_no` = '" . $send_log['mar_task_id'] . "'";
                                } elseif (strpos($send_log['task_no'], 'mar') !== false) {
                                    $sql = "SELECT `send_msg_id`,`task_no`,`uid`,`create_time` FROM  yx_user_send_task WHERE `task_no` = '" . $send_log['mar_task_id'] . "'";
                                }
                                $task = Db::query($sql);
                            } else {
                                // $redis->rpush($redisMessageCodeSend, json_encode($send_log));
                                continue;
                            }
                        }
                    }
                    // $redis->rpush($redisMessageCodeSend, json_encode($send_log));
                    // $request_url = "http://116.228.60.189:15902/rtreceive?";
                    // $request_url .= 'task_no=' . $task[0]['task_no'] . "&status_message=" . $send_log['Stat'] . "&mobile=" . $send_log['mobile'] . "&send_time=" . $send_log['Submit_time'];
                    if ($task[0]['uid'] == 47 || $task[0]['uid'] == 49 || $task[0]['uid'] == 51 || $task[0]['uid'] == 52 || $task[0]['uid'] == 53 || $task[0]['uid'] == 54 || $task[0]['uid'] == 55) { //推送给美丽田园
                        // https://zhidao.baidu.com/question/412076997.html
                        if (strpos($send_log['content'], '评价') !== false) {
                            $request_url = "http://116.228.60.189:15901/rtreceive?";
                            $request_url .= 'task_no=' . trim($task[0]['task_no']) . "&status_message=" . "DELIVRD" . "&mobile=" . trim($send_log['mobile']) . "&send_time=" . trim($send_log['Submit_time']);
                            $stat = 'DELIVRD';
                        } else {
                            $stat = trim($send_log['Stat']);
                            if (strpos($send_log['Stat'], 'DB:0141') !== false || strpos($send_log['Stat'], 'MBBLACK') !== false || strpos($send_log['Stat'], 'BLACK') !== false) {
                                $message_info = '黑名单';
                            } else if (trim($send_log['Stat'] == 'DELIVRD')) {
                                $message_info = '发送成功';
                            } else if (in_array(trim($send_log['Stat']), ['REJECTD', 'REJECT', 'MA:0001'])) {
                                $stat = 'DELIVRD';
                                $message_info = '发送成功';
                            } else {
                                $message_info = '发送失败';
                            }
                            $request_url = "http://116.228.60.189:15901/rtreceive?";
                            $request_url .= 'task_no=' . trim($task[0]['task_no']) . "&status_message=" . trim($stat) . "&mobile=" . trim($send_log['mobile']) . "&send_time=" . trim($send_log['Submit_time']);
                        }

                        // print_r($request_url);
                        sendRequest($request_url);

                        usleep(20000);
                    } else {
                        $stat = trim($send_log['Stat']);
                        if (strpos($send_log['Stat'], 'DB:0141') !== false || strpos($send_log['Stat'], 'MBBLACK') !== false || strpos($send_log['Stat'], 'BLACK') !== false) {
                            $message_info = '黑名单';
                        } else if (trim($send_log['Stat'] == 'DELIVRD')) {
                            $message_info = '发送成功';
                        }
                        /*  else if (in_array(trim($send_log['Stat']), ['REJECTD', 'REJECT', 'MA:0001', 'DB:0141'])) {
                            $stat = 'DELIVRD';
                            $message_info = '发送成功';
                        }  */ else {
                            $message_info = '发送失败';
                        }
                        /*  if (trim($send_log['mobile']) == '18616841500') {
                        $send_log['Stat'] = 'DELIVRD';
                        $message_info = '发送成功';
                        } */
                        if ($task[0]['uid'] == '91') {
                            if (strpos($send_log['Stat'], 'DB:0141') !== false) {
                                $stat = 'DELIVRD';
                                $message_info = '发送成功';
                            }
                        }
                        $user = Db::query("SELECT `pid`,`need_receipt_cmpp` FROM yx_users WHERE `id` = " . $task[0]['uid']);
                        if ($user[0]['pid'] == 137) {
                            // print_r($stat);die;
                            if (in_array($stat, $Received)) {
                                $stat = 'DELIVRD';
                            }
                            $send_len = 0;
                            $send_len = mb_strlen($send_log['content']);
                            $s_num = 1;
                            if ($send_len > 70) {
                                $s_num = ceil($send_len / 67);
                            }
                            for ($a = 0; $a < $s_num; $a++) {
                                $redis->rpush('index:meassage:code:user:receive:' . $task[0]['uid'], json_encode([
                                    'task_no'        => trim($task[0]['task_no']),
                                    'status_message' => $stat,
                                    'message_info'   => $message_info,
                                    'mobile'         => trim($send_log['mobile']),
                                    'msg_id'         => trim($task[0]['send_msg_id']),
                                    // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                                    'send_time'      => isset($send_log['receive_time']) ? date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                                    'smsCount' => $s_num,
                                    'smsIndex' => $a + 1,
                                ])); //写入用户带处理日志
                            }
                        } else {
                            if ($user[0]['need_receipt_cmpp'] == 2) {
                                $redis->rpush('index:meassage:code:user:receive:' . $task[0]['uid'], json_encode([
                                    'Stat'        => trim($task[0]['task_no']),
                                    'send_msgid'        => trim($task[0]['send_msg_id']),
                                    'status_message' => $stat,
                                    'mobile'         => trim($send_log['mobile']),
                                    'develop_no' => trim($send_log['develop_no']) ? $send_log['develop_no'] : '',
                                    // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                                    'Done_time'      => isset($send_log['receive_time']) ? date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                                    'Submit_time'      => isset($task[0]['create_time']) ? date('Y-m-d H:i:s', trim($task[0]['create_time'])) : date('Y-m-d H:i:s', time()),
                                ])); //写入用户带处理日志
                            } else {
                                $redis->rpush('index:meassage:code:user:receive:' . $task[0]['uid'], json_encode([
                                    'task_no'        => trim($task[0]['task_no']),
                                    'status_message' => $stat,
                                    'message_info'   => $message_info,
                                    'mobile'         => trim($send_log['mobile']),
                                    // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                                    'send_time'      => isset($send_log['receive_time']) ? date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                                ])); //写入用户带处理日志
                            }
                        }
                    }
                    $send_log['stat'] = $stat;
                    // print_r($send_log);
                    $redis->rpush('index:meassage:code:cms:deliver:' . $channel_id, json_encode($send_log)); //写入通道处理日志
                }
            }
        } catch (\Exception $th) {
            $redis->rpush($redisMessageCodeSend, json_encode($send_log));
            exception($th);
        }

        // try {
        //     //code...
        // } catch (\Exception $e) {
        //     //throw $th;
        // }
        // for ($i = 1; $i < 20; $i++) {

        // }

    }
    //'index:message:code:deduct:deliver'
    public function updateDeduct()
    {
        $redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        /* 'task_no' => $sendTask['task_no'],
        'uid'            => $sendTask['uid'],
        'msg_id'            => $sendTask['send_msg_id'],
        'Stat' => 'DELIVRD',
        'mobile' =>  $mobilesend[$i],
        'content'   => $sendTask['task_content'],
        'Submit_time'   => time(), */
        try {
            while (true) {
                sleep(3);

                $rollback = [];
                while (true) {
                    $deduct = $redis->lpop('index:message:code:deduct:deliver');
                    if (empty($deduct)) {
                        break;
                    }
                    $rollback[] = $deduct;
                    $deduct = json_decode($deduct, true);
                    if (strlen($deduct['mobile']) > 11) {
                        continue;
                    }
                    $data = [];
                    $data = [
                        'task_id'        => $deduct['mar_task_id'],
                        'mobile'         => intval(trim($deduct['mobile'])),
                        'real_message'   => 'DEDUCT:1',
                        'status_message' => $deduct['Stat'],
                        'create_time'    => $deduct['Submit_time'],
                    ];
                    if ($deduct['Stat'] == 'DELIVRD') {
                        $message_info = '发送成功';
                    } else {
                        $message_info = '发送失败';
                    }
                    if ($deduct['from'] == 'yx_user_send_task') {
                        Db::table('yx_send_task_receipt')->insert($data);
                    } else if ($deduct['from'] == 'yx_user_send_code_task') {
                        Db::table('yx_send_code_task_receipt')->insert($data);
                    } else if ($deduct['from'] == 'yx_user_send_game_task') {
                        Db::table('yx_user_send_game_task')->where(['id' => $deduct['mar_task_id'], 'mobile'         => $deduct['mobile']])->update(
                            [
                                'real_message'   => 'DEDUCT:1',
                                'status_message' => $deduct['Stat'],
                                'update_time'    => $deduct['Submit_time'],
                            ]
                        );
                    } elseif ($deduct['from'] == 'yx_user_multimedia_message') {
                        Db::table('yx_user_multimedia_message_log')->where(['task_id' => $deduct['mar_task_id'], 'mobile'         => $deduct['mobile']])->update(
                            [
                                'real_message'   => 'DEDUCT:1',
                                'status_message' => $deduct['Stat'],
                                'update_time'    => $deduct['Submit_time'],
                            ]
                        );
                    }
                    if (in_array($deduct['uid'], [47, 49, 51, 52, 53, 54, 55])) {
                        $request_url = "http://116.228.60.189:15901/rtreceive?";
                        $request_url .= 'task_no=' . trim($deduct['task_no']) . "&status_message=" . trim($deduct['Stat']) . "&mobile=" . trim($deduct['mobile']) . "&send_time=" . date('Y-m-d H:i:s', trim($deduct['Submit_time']));
                    } else {
                        $user = Db::query("SELECT `pid` FROM yx_users WHERE `id` = " . $deduct['uid']);
                        if ($user[0]['pid'] == 137) {
                            $send_len = 0;
                            $send_len = mb_strlen($deduct['content']);
                            $s_num = 1;
                            if ($send_len > 70) {
                                $s_num = ceil($send_len / 67);
                            }

                            for ($a = 0; $a < $s_num; $a++) {
                                $redis->rpush('index:meassage:code:user:receive:' . $deduct['uid'], json_encode([
                                    'task_no'        => trim($deduct['task_no']),
                                    'status_message' => $deduct['Stat'],
                                    'message_info'   => $message_info,
                                    'mobile'         => intval(trim($deduct['mobile'])),
                                    'msg_id'         => trim($deduct['msg_id']),
                                    // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                                    'send_time'      => date('Y-m-d H:i:s', $deduct['Submit_time'] + mt_rand(0, 2)),
                                    'smsCount' => $s_num,
                                    'smsIndex' => $a + 1,
                                ])); //写入用户带处理日志
                            }
                        } else {
                            if ($deduct['from'] == 'yx_user_multimedia_message') {
                                $redis->rpush('index:meassage:code:user:mulreceive:' . $deduct['uid'], json_encode([
                                    'task_no'        => trim($deduct['task_no']),
                                    'status_message' => $deduct['Stat'],
                                    'message_info'   => $message_info,
                                    'mobile'         => trim($deduct['mobile']),
                                    // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                                    'send_time'      => date('Y-m-d H:i:s', $deduct['Submit_time'] + mt_rand(0, 5)),
                                ])); //写入用户带处理日志
                            } else {
                                $redis->rpush('index:meassage:code:user:receive:' . $deduct['uid'], json_encode([
                                    'task_no'        => trim($deduct['task_no']),
                                    'status_message' => $deduct['Stat'],
                                    'message_info'   => $message_info,
                                    'mobile'         => trim($deduct['mobile']),
                                    // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                                    'send_time'      => date('Y-m-d H:i:s', $deduct['Submit_time'] + mt_rand(0, 5)),
                                ])); //写入用户带处理日志
                            }
                        }
                    }
                }
            }
        } catch (\Exception $th) {
            //throw $th;
            // echo Db::getLastSQL();
            exception($th);
        }
    }

    //处理通道消息队列中的回执日志
    public function updateCmppChannelLog($channel_id)
    {

        $redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        $redisMessageCodeSend = 'index:meassage:code:cms:deliver:' . $channel_id; //验证码发送任务rediskey
        $channel              = $this->getChannelinfo($channel_id);
        $task_status          = [];
        $task_mobile          = [];
        $i                    = 0;
        $callback             = [];
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
                    $send_log = $redis->lpop($redisMessageCodeSend);
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
                                                $log['send_status'] = 3;
                                            } else {
                                                $log['send_status'] = 4;
                                            }

                                            $log['send_time'] = date('Y-m-d H:i:s', trim($value[$log['mobile']]['Submit_time']));

                                            //  print_r($log);die;
                                        }
                                        if (is_numeric($log['create_time'])) {
                                            $log['create_time'] = date('Y-m-d H:i:s', $log['create_time']);
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
                                            $log['send_status'] = 3;
                                        } else {
                                            $log['send_status'] = 4;
                                        }

                                        //  print_r($log);die;
                                        $log['send_time'] = date('Y-m-d H:i:s', trim($value[$log['mobile']]['Submit_time']));
                                        if (is_numeric($log['create_time'])) {
                                            $log['create_time'] = date('Y-m-d H:i:s', $log['create_time']);
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

                    // print_r($request_url);
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
        /*      $redis->rpush($redisMessageCodeSend, json_encode([
        'mar_task_id' => '1',
        'uid' => '45',
        'Msg_Id' => '1577095013046269',
        'content' => '【超变大陆】已为您发出688888钻石和VIP15，今日限领至尊屠龙！戳 https://ltv7.cn/5CWSJ 回T退订',
        'mobile' => '13812895012',
        'Stat' => 'ID:0076',
        'Done_time' => '1912231821',
        'receive_time' => time() + 2,
        'my_submit_time' => time(),
        ]));
        $redis->rpush($redisMessageCodeSend, json_encode([
        'mar_task_id' => '2',
        'uid' => '45',
        'Msg_Id' => '1577096780057526',
        'content' => '【超变大陆】已为您发出6888888钻石和VIP15，今日限领至尊屠龙！戳 https://ltv7.cn/5CWSJ 回T退订',
        'mobile' => '13812895012',
        'Stat' => 'LIMIT',
        'Done_time' => '1912231828',
        'Done_time' => '1912231828',
        ]));
        $redis->rpush($redisMessageCodeSend, json_encode([
        'mar_task_id' => '1',
        'uid' => '45',
        'Msg_Id' => '12648757921059827739',
        'content' => '【冰封传奇】已为您发出688888元宝和VIP满级号，今日限领至尊屠龙！戳 https://ltv7.cn/45RHD 回T退订',
        'mobile' => '18339998120',
        'Stat' => 'MK:1008',
        'Done_time' => '1912121543',
        'Done_time' => '1912121543',
        ]));
        $redis->rpush($redisMessageCodeSend, json_encode([
        'mar_task_id' => '4',
        'uid' => '45',
        'Msg_Id' => '12648757921059827739',
        'content' => '【冰封传奇】已为您发出688888元宝和VIP满级号，今日限领至尊屠龙！戳 https://ltv7.cn/45RHD 回T退订',
        'mobile' => '18339998120',
        'Stat' => 'MK:1008',
        'Done_time' => '1912121543',
        'Done_time' => '1912121543',
        ]));
        $redis->hset('index:meassage:game:msg:id:14', 1, json_encode([
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
            $time_no  = time();
            //状态更新
            /*             $unknow_status = $redis->lpop('index:meassage:game:unknow:deliver:' . $content);
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
            } */

            if (!empty($send_log)) {
                // exit("send_log is null");
                // $redis->rpush($redisMessageCodeSend, json_encode($send_log));

                //未知
                // if (!isset($untime)){
                //     continue;
                // }
                $redis->rpush('index:meassage:game:cms:deliver:', json_encode($send_log)); //游戏通道实际码
                $send_log = json_decode($send_log, true);
                /*  if (!isset($untime)) {
                if (isset($send_log['receive_time']) && isset($send_log['my_submit_time'])) {
                $untime = $send_log['receive_time'] - $send_log['my_submit_time'];
                }
                } else {
                if (isset($send_log['receive_time']) && isset($send_log['my_submit_time'])) {
                $untime = $send_log['receive_time'] - $send_log['my_submit_time'] > $untime ? $send_log['receive_time'] - $send_log['my_submit_time'] : $untime;
                }
                } */
                $task = Db::query("SELECT * FROM yx_user_send_game_task WHERE `id` = '" . $send_log['mar_task_id'] . "'");
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
                    $stat       = $send_log['Stat'];
                    if (trim($stat) == 'ID:0076') {
                        $stat = 'DELIVRD';
                    }
                    foreach ($send_msgid as $key => $value) {
                        $redis->rPush('index:meassage:game:cmppdeliver:' . $task[0]['uid'], json_encode([
                            'Stat'        => $stat,
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
                    Db::table('yx_user_send_game_task')->where('id', $send_log['mar_task_id'])->update(['real_message' => $send_log['Stat'], 'status_message' => $stat, 'update_time' => isset($send_log['receive_time']) ? $send_log['receive_time'] : time()]);
                    Db::commit();
                } catch (\Exception $e) {
                    $redis->rpush($redisMessageCodeSend, json_encode($send_log));
                    Db::rollback();
                }
            }
            /*   $sendunknow = $redis->hgetall('index:meassage:game:msg:id:' . $content);
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
            } */

            //扣量
            $witenosend = $redis->lpop("index:meassage:game:waitcmppdeliver");
            if (!empty($witenosend)) {

                // continue;
                // sleep($untime);
                $witenosend_log  = json_decode($witenosend, true);
                $witenosend_task = Db::query("SELECT * FROM yx_user_send_game_task WHERE `id` = '" . $witenosend_log['mar_task_id'] . "'");
                if (empty($witenosend_task)) {
                    continue;
                }
                $send_msgid = explode(',', $witenosend_task[0]['send_msg_id']);
                $stat       = $witenosend_log['Stat'];
                if (trim($stat) == 'ID:0076') {
                    $stat = 'DELIVRD';
                }
                foreach ($send_msgid as $key => $value) {
                    $redis->rPush('index:meassage:game:cmppdeliver:' . $witenosend_task[0]['uid'], json_encode([
                        'Stat'        => $stat,
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
                    Db::table('yx_user_send_game_task')->where('id', $witenosend_log['mar_task_id'])->update(['status_message' => $stat]);
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
        $upnum_data  = [];
        $upnum_uid   = [];
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
                    $upnum_uid[]              = $task['uid'];
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
                $up_num        = $user_equities[0]['num_balance'] - $value;
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
        $time       = strtotime(date('Y-m-d 0:00:00', time()));
        $start_time = strtotime(date('Y-m-d 0:00:00', strtotime("-1 day")));
        $ids        = Db::query("SELECT `id` FROM  `yx_user_send_code_task` WHERE `create_time` < " . $time . " AND `create_time` >= " . $start_time . "  AND  `log_path` <> ''");
        $all_log    = [];
        $j          = 1;
        for ($i = 0; $i < count($ids); $i++) {
            $sendTask    = $this->getSendCodeTask($ids[$i]['id']);
            $mobilesend  = explode(',', $sendTask['mobile_content']);
            $send_length = mb_strlen($sendTask['task_content'], 'utf8');
            $real_length = 1;
            if ($send_length > 70) {
                $real_length = ceil($send_length / 67);
            }
            foreach ($mobilesend as $key => $value) {
                $send_log = [];
                $send_log = [
                    'uid'          => $sendTask['uid'],
                    'task_no'      => $sendTask['task_no'],
                    'task_content' => $sendTask['task_content'],
                    'mobile'       => $value,
                    'source'       => $sendTask['source'],
                    'send_length'  => $send_length,
                    'send_status'  => 2,
                    'free_trial'   => 2,
                    'create_time'  => $sendTask['create_time'],
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
        $time       = strtotime(date('Y-m-d 0:00:00', time()));
        $start_time = strtotime(date('Y-m-d 0:00:00', strtotime("-1 day")));
        // $ids = Db::query("SELECT `id` FROM  `yx_user_send_task` WHERE `create_time` < " . $time . " AND  `create_time` >= " . $start_time . "   AND  `log_path` <> ''");
        $ids     = Db::query("SELECT `id` FROM  `yx_user_send_task` WHERE  `id` > 15864   AND  `log_path` <> ''");
        $all_log = [];
        $j       = 1;
        // echo count($ids);
        // die;
        for ($i = 0; $i < count($ids); $i++) {
            $sendTask    = $this->getSendTask($ids[$i]['id']);
            $mobilesend  = explode(',', $sendTask['mobile_content']);
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
                    'uid'          => $sendTask['uid'],
                    'task_no'      => $sendTask['task_no'],
                    'task_content' => $sendTask['task_content'],
                    'mobile'       => $value,
                    'source'       => $sendTask['source'],
                    'send_length'  => $send_length,
                    'send_status'  => 2,
                    'free_trial'   => 2,
                    'create_time'  => $sendTask['create_time'],
                ];
                $all_log[] = $send_log;
                $j++;
                if ($j > 1000) {
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
                // exit('Send Log IS null');
                continue;
            }
            $send_log = json_decode($sendlog, true);

            if (!isset($send_log['mar_task_id'])) {
                continue;
            }
            if (isset($send_log['from']) && $send_log['from'] == 'yx_user_send_task') {
                $sendTask = $this->getSendTask($send_log['mar_task_id']);
            } else if (isset($send_log['from']) && $send_log['from'] == 'yx_user_send_code_task') {
                $sendTask = $this->getSendCodeTask($send_log['mar_task_id']);
            } else if (isset($send_log['from']) && $send_log['from'] == 'yx_user_send_game_task') {
                $sendTask = $this->getSendGameTask($send_log['mar_task_id']);
            } else {
                $sendTask = $this->getSendCodeTask($send_log['mar_task_id']);
            }

            if (empty($sendTask)) {
                print_r($send_log);
                $redis->rpush('index:meassage:code:cms:deliver:' . $channel_id, $sendlog);
                continue;
            }
            if (isset($send_log['from']) && $send_log['from'] == 'yx_user_send_task') {
                $sendtasklog = Db::query("SELECT `id`,`create_time` FROM `yx_user_send_task_log` WHERE `task_no` = '" . $sendTask['task_no'] . "' AND `mobile` = '" . $send_log['mobile'] . "' ");
            } else if (isset($send_log['from']) && $send_log['from'] == 'yx_user_send_code_task') {
                $sendtasklog = Db::query("SELECT `id`,`create_time` FROM `yx_user_send_code_task_log` WHERE `task_no` = '" . $sendTask['task_no'] . "' AND `mobile` = '" . $send_log['mobile'] . "' ");
            } else if (isset($send_log['from']) && $send_log['from'] == 'yx_user_send_game_task') {
                $sendtasklog = Db::query("SELECT `id`,`create_time` FROM `yx_user_send_game_task` WHERE `task_no` = '" . $sendTask['task_no'] . "' AND `mobile_content` = '" . $send_log['mobile'] . "' ");
            } else {
                $sendtasklog = Db::query("SELECT `id`,`create_time` FROM `yx_user_send_code_task_log` WHERE `task_no` = '" . $sendTask['task_no'] . "' AND `mobile` = '" . $send_log['mobile'] . "' ");
            }
            // die;
            if (empty($sendtasklog)) {
                // $redis->rpush('index:meassage:code:cms:deliver:' . $channel_id, $sendlog);
                if (strpos($send_log['content'], '问卷') !== false) {
                    $status_message = 'DELIVRD';
                } else {
                    $status_message = $send_log['Stat'];
                }
                Db::startTrans();
                try {
                    if (isset($send_log['from']) && $send_log['from'] == 'yx_user_send_task') {
                        Db::table('yx_user_send_task_log')->insert([
                            'uid'            => $sendTask['uid'],
                            'task_no'        => $sendTask['task_no'],
                            'task_content'   => $sendTask['task_content'],
                            'mobile'         => $send_log['mobile'],
                            'source'         => $sendTask['source'],
                            'send_length'    => $sendTask['send_length'],
                            'send_status'    => 2,
                            'free_trial'     => 2,
                            'create_time'    => $sendTask['create_time'],
                            'send_time'      => isset($send_log['Submit_time']) ? $send_log['Submit_time'] : $sendTask['create_time'],
                            'real_message'   => $send_log['Stat'],
                            'status_message' => $status_message,
                            'update_time'    => isset($send_log['receive_time']) ? $send_log['receive_time'] : time(),
                        ]);
                    } else if (isset($send_log['from']) && $send_log['from'] == 'yx_user_send_code_task') {
                        Db::table('yx_user_send_code_task_log')->insert([
                            'uid'            => $sendTask['uid'],
                            'task_no'        => $sendTask['task_no'],
                            'task_content'   => $sendTask['task_content'],
                            'mobile'         => $send_log['mobile'],
                            'source'         => $sendTask['source'],
                            'send_length'    => $sendTask['send_length'],
                            'send_status'    => 2,
                            'free_trial'     => 2,
                            'create_time'    => $sendTask['create_time'],
                            'send_time'      => isset($send_log['Submit_time']) ? $send_log['Submit_time'] : $sendTask['create_time'],
                            'real_message'   => $send_log['Stat'],
                            'status_message' => $status_message,
                            'update_time'    => isset($send_log['receive_time']) ? $send_log['receive_time'] : time(),
                        ]);
                    } else if (isset($send_log['from']) && $send_log['from'] == 'yx_user_send_game_task') {
                        /*   Db::table('yx_user_send_game_task')->insert([
                    'uid' => $sendTask['uid'],
                    'task_no' => $sendTask['task_no'],
                    'task_content' => $sendTask['task_content'],
                    'mobile_content' => $send_log['mobile'],
                    'source' => $sendTask['source'],
                    'send_length' => $sendTask['send_length'],
                    'send_status' => 2,
                    'free_trial' => 2,
                    'create_time' => $sendTask['create_time'],
                    'submit_time' => isset($send_log['Submit_time']) ? $send_log['submit_time'] : $sendTask['create_time'],
                    'real_message' => $send_log['Stat'],
                    'status_message' => $status_message,
                    'update_time'    => isset($send_log['receive_time']) ? $send_log['receive_time'] : time(),
                    ]); */
                    } else {
                        Db::table('yx_user_send_code_task_log')->insert([
                            'uid'            => $sendTask['uid'],
                            'task_no'        => $sendTask['task_no'],
                            'task_content'   => $sendTask['task_content'],
                            'mobile'         => $send_log['mobile'],
                            'source'         => $sendTask['source'],
                            'send_length'    => $sendTask['send_length'],
                            'send_status'    => 2,
                            'free_trial'     => 2,
                            'create_time'    => $sendTask['create_time'],
                            'send_time'      => isset($send_log['Submit_time']) ? $send_log['Submit_time'] : $sendTask['create_time'],
                            'real_message'   => $send_log['Stat'],
                            'status_message' => $status_message,
                            'update_time'    => isset($send_log['receive_time']) ? $send_log['receive_time'] : time(),
                        ]);
                    }

                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    exception($e);
                }
            } else {
                if (strpos($send_log['content'], '问卷') !== false) {
                    $status_message = 'DELIVRD';
                } else {
                    $status_message = $send_log['Stat'];
                }

                Db::startTrans();
                try {
                    if (isset($send_log['from']) && $send_log['from'] == 'yx_user_send_task') {
                        Db::table('yx_user_send_task_log')->where('id', $sendtasklog[0]['id'])->update(['real_message' => $send_log['Stat'], 'update_time' => isset($send_log['receive_time']) ? $send_log['receive_time'] : time(), 'status_message' => $status_message]);
                    } else if (isset($send_log['from']) && $send_log['from'] == 'yx_user_send_code_task') {
                        Db::table('yx_user_send_code_task_log')->where('id', $sendtasklog[0]['id'])->update(['real_message' => $send_log['Stat'], 'update_time' => isset($send_log['receive_time']) ? $send_log['receive_time'] : time(), 'status_message' => $status_message]);
                    } else if (isset($send_log['from']) && $send_log['from'] == 'yx_user_send_game_task') {
                        //暂时不做
                    } else {
                        Db::table('yx_user_send_code_task_log')->where('id', $sendtasklog[0]['id'])->update(['real_message' => $send_log['Stat'], 'update_time' => isset($send_log['receive_time']) ? $send_log['receive_time'] : time(), 'status_message' => $status_message]);
                    }

                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    exception($e);
                }
            }
            // if ($sendtasklog[0]['create_time'] > $time) {
            //     $redis->rpush('index:meassage:code:cms:deliver:' . $channel_id, $sendlog);
            //     exit('today is success');
            // }

        }
    }

    public function receiptMarketingToBase($channel_id)
    {
        // $redis->rpush('index:meassage:Buiness:cms:deliver:', json_encode($send_log));
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        $redis = Phpredis::getConn();
        // $redis->rpush('index:meassage:code:cms:deliver:' . $channel_id, json_encode($send_log)); //写入通道处理日志
        /*       $redis->rpush('index:meassage:code:cms:deliver:' . $channel_id, json_encode(array(
        'mobile' => '15045451231',
        'title' => '美丽田园营销短信',
        'mar_task_id' => '15850',
        'content' => '【DAPHNE】亲爱的会员：您的30元优惠券已到账，请前往DaphneFashion公众号-会员尊享-会员中心领取！退订回T',
        'Msg_Id' => '',
        'Stat' => 'DELIVER',
        'Submit_time' => '191224164036',
        'Done_time' => '191224164236',
        )));
        $time = strtotime(date('Y-m-d 0:00:00', time())); */
        $channel = $this->getChannelinfo($channel_id);

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
                    if (empty($sendtasklog)) { //插入
                        if (strpos($send_log['content'], '问卷') !== false) {
                            $status_message = 'DELIVRD';
                        } else {
                            $status_message = $send_log['Stat'];
                        }
                        Db::startTrans();
                        try {
                            Db::table('yx_user_send_task_log')->insert([
                                'uid'            => $sendTask['uid'],
                                'task_no'        => $sendTask['task_no'],
                                'task_content'   => $sendTask['task_content'],
                                'mobile'         => $send_log['mobile'],
                                'source'         => $sendTask['source'],
                                'send_length'    => $sendTask['send_length'],
                                'send_status'    => 2,
                                'free_trial'     => 2,
                                'create_time'    => $sendTask['update_time'],
                                'send_time'      => isset($send_log['Submit_time']) ? $send_log['Submit_time'] : $sendTask['update_time'],
                                'real_message'   => $send_log['Stat'],
                                'status_message' => $status_message,
                                'update_time'    => isset($send_log['Done_time']) ? $send_log['Done_time'] : time(),
                            ]);
                            Db::commit();
                        } catch (\Exception $e) {
                            Db::rollback();
                            exception($e);
                        }
                    } else {
                        // if ($sendtasklog[0]['create_time'] > $time) {
                        //     $redis->rpush('index:meassage:code:cms:deliver:' . $channel_id, $sendlog);
                        //     exit('today is success');
                        // }
                        if (strpos($send_log['content'], '问卷') !== false) {
                            $status_message = 'DELIVRD';
                        } else {
                            $status_message = $send_log['Stat'];
                        }

                        Db::startTrans();
                        try {
                            Db::table('yx_user_send_task_log')->where('id', $sendtasklog[0]['id'])->update(['real_message' => $send_log['Stat'], 'status_message' => $status_message]);
                            Db::commit();
                        } catch (\Exception $e) {
                            Db::rollback();
                            exception($e);
                        }
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
                        // $redis->rpush('index:meassage:code:cms:deliver:' . $channel_id, $sendlog);
                        if (strpos($send_log['content'], '问卷') !== false) {
                            $status_message = 'DELIVRD';
                        } else {
                            $status_message = $send_log['Stat'];
                        }
                        Db::startTrans();
                        try {
                            Db::table('yx_user_send_code_task_log')->insert([
                                'uid'            => $sendTask['uid'],
                                'task_no'        => $sendTask['task_no'],
                                'task_content'   => $sendTask['task_content'],
                                'mobile'         => $send_log['mobile'],
                                'source'         => $sendTask['source'],
                                'send_length'    => $sendTask['send_length'],
                                'send_status'    => 2,
                                'free_trial'     => 2,
                                'create_time'    => $sendTask['create_time'],
                                'send_time'      => isset($send_log['Submit_time']) ? $send_log['Submit_time'] : $sendTask['create_time'],
                                'real_message'   => $send_log['Stat'],
                                'status_message' => $status_message,
                                'update_time'    => isset($send_log['Done_time']) ? $send_log['Done_time'] : time(),
                            ]);
                            Db::commit();
                        } catch (\Exception $e) {
                            Db::rollback();
                            exception($e);
                        }
                    } else {
                        if (strpos($send_log['content'], '问卷') !== false) {
                            $status_message = 'DELIVRD';
                        } else {
                            $status_message = $send_log['Stat'];
                        }

                        Db::startTrans();
                        try {
                            Db::table('yx_user_send_code_task_log')->where('id', $sendtasklog[0]['id'])->update(['real_message' => $send_log['Stat'], 'status_message' => $status_message, 'update_time' => isset($send_log['Done_time']) ? $send_log['Done_time'] : time()]);
                            Db::commit();
                        } catch (\Exception $e) {
                            Db::rollback();
                            exception($e);
                        }
                    }
                    // if ($sendtasklog[0]['create_time'] > $time) {
                    //     $redis->rpush('index:meassage:code:cms:deliver:' . $channel_id, $sendlog);
                    //     exit('today is success');
                    // }

                } elseif ($channel['business_id'] == 9) { //游戏
                    $sql .= " yx_user_send_game_task ";
                }
            }
        }
    }

    //批量写入回执
    public function receiptMarketingTask($channel_id)
    {
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        $redis = Phpredis::getConn();
        if (empty($channel_id)) {
            exit("channel_id IS Null");
        }
        $redis->rpush('index:meassage:code:cms:deliver:' . $channel_id, '{"mobile":"15201926171","title":"\u3010\u4e1d\u8299\u5170\u3011\u60a8\u672c\u6b21\u9a8c\u8bc1\u7801\u4e3a0215","mar_task_id":3241123,"content":"\u3010\u4e1d\u8299\u5170\u3011\u60a8\u672c\u6b21\u9a8c\u8bc1\u7801\u4e3a0215","from":"yx_user_send_code_task","send_msg_id":"","uid":185,"send_num":1,"task_no":"bus20090210021910957383","develop_code":"90963","my_submit_time":1599012223,"Msg_Id":"24353329926634139","Stat":"DELIVRD","Submit_time":"2009021003","Done_time":"2009021003","receive_time":1599012227,"develop_no":"90963","stat":"DELIVRD"}'); //写入通道处理日志
        /* for ($i = 0; $i < 1650; $i++) {
        $redis->rpush('index:meassage:code:cms:deliver:' . $channel_id, json_encode(array(
        'mobile' => '15045451231',
        'title' => '美丽田园营销短信',
        'mar_task_id' => '15850',
        'content' => '【DAPHNE】亲爱的会员：您的30元优惠券已到账，请前往DaphneFashion公众号-会员尊享-会员中心领取！退订回T',
        'Msg_Id' => '',
        'Stat' => 'DELIVRD',
        'Submit_time' => '191224164036',
        'Done_time' => '191224164236',
        'receive_time' => '1583467981',
        'from' => 'yx_user_send_task',
        )));
        }
        for ($i = 0; $i < 3998; $i++) {
        $redis->rpush('index:meassage:code:cms:deliver:' . $channel_id, json_encode(array(
        'mobile' => '15045451231',
        'title' => '美丽田园营销短信',
        'mar_task_id' => '15850',
        'content' => '【DAPHNE】亲爱的会员：您的30元优惠券已到账，请前往DaphneFashion公众号-会员尊享-会员中心领取！退订回T',
        'Msg_Id' => '',
        'Stat' => 'DELIVRD',
        'Submit_time' => '191224164036',
        'Done_time' => '191224164236',
        'receive_time' => '1583467981',
        'from' => 'yx_user_send_code_task',
        )));
        }
        for ($i = 0; $i < 288; $i++) {
        $redis->rpush('index:meassage:code:cms:deliver:' . $channel_id, json_encode(array(
        'mobile' => '15045451231',
        'title' => '美丽田园营销短信',
        'mar_task_id' => '15850',
        'content' => '【DAPHNE】亲爱的会员：您的30元优惠券已到账，请前往DaphneFashion公众号-会员尊享-会员中心领取！退订回T',
        'Msg_Id' => '',
        'Stat' => 'DELIVRD',
        'Submit_time' => '191224164036',
        'Done_time' => '191224164236',
        'receive_time' => '1583467981',
        'from' => 'yx_user_send_game_task',
        )));
        }
        for ($i = 0; $i < 288; $i++) {
        $redis->rpush('index:meassage:code:cms:deliver:' . $channel_id, json_encode(array(
        'mobile' => '15045451231',
        'title' => '美丽田园营销短信',
        'mar_task_id' => '15850',
        'content' => '【DAPHNE】亲爱的会员：您的30元优惠券已到账，请前往DaphneFashion公众号-会员尊享-会员中心领取！退订回T',
        'Msg_Id' => '',
        'Stat' => 'DELIVRD',
        'Submit_time' => '191224164036',
        'Done_time' => '191224164236',
        'receive_time' => '1583467981',
        )));
        }
        $time = strtotime(date('Y-m-d 0:00:00', time())); */
        $channel = $this->getChannelinfo($channel_id);
        while (true) {
            $i            = 0;
            $receipt_data = [];
            while (true) {
                $sendlog = $redis->lpop('index:meassage:code:cms:deliver:' . $channel_id);
                if (empty($sendlog)) {

                    break;
                }
                $send_log = json_decode($sendlog, true);

                if (!isset($send_log['mar_task_id'])) {
                    break;
                }
                $data = [];
                if (strpos($send_log['content'], '问卷') !== false) {
                    $status_message = 'DELIVRD';
                } else {
                    $status_message = $send_log['Stat'];
                    if (in_array(trim($send_log['Stat']), ['REJECTD', 'REJECT', 'MA:0001', 'DB:0141'])) {
                        $status_message = 'DELIVRD';
                    }
                }
                if (checkMobile($send_log['mobile']) == false) {
                    continue;
                }
                $data = [
                    'task_id'        => $send_log['mar_task_id'],
                    'mobile'         => $send_log['mobile'],
                    'real_message'   => $send_log['Stat'],
                    'status_message' => $status_message,
                    'create_time'    => isset($send_log['receive_time']) ? $send_log['receive_time'] : time(),
                ];
                if (isset($send_log['from']) && $send_log['from'] == 'yx_user_send_task') {
                    $receipt_data['yx_send_task_receipt'][] = $data;
                } else if (isset($send_log['from']) && $send_log['from'] == 'yx_user_send_code_task') {
                    $receipt_data['yx_send_code_task_receipt'][] = $data;
                } else if (isset($send_log['from']) && $send_log['from'] == 'yx_user_send_game_task') {
                    $receipt_data['yx_send_game_task_receipt'][] = $data;
                } else if (isset($send_log['from']) && $send_log['from'] == 'yx_sfl_send_task') {
                    if (isset($send_log['template_id'])) {
                        $data['template_id'] = $send_log['template_id'];
                    }
                    if ($status_message == 'DELIVRD') {
                        $data['messageinfo'] = "发送成功";
                        $data['status_message'] = "SMS:1";
                    } else {
                        $data['messageinfo'] = "发送失败";
                        $data['status_message'] = "SMS:2";
                    }
                    if (strpos($send_log['status_message'], 'DB:0141') !== false || strpos($send_log['status_message'], 'MBBLACK') !== false || strpos($send_log['status_message'], 'BLACK') !== false) {
                        $data['messageinfo'] = '黑名单';
                        $data['status_message'] = 'SMS:4';
                    }
                    $receipt_data['yx_sfl_send_task_receipt'][] = $data;
                } else {
                    if ($channel['business_id'] == 5) { //营销{}
                        $receipt_data['yx_send_task_receipt'][] = $data;
                    } else if ($channel['business_id'] == 6) { //行业
                        $receipt_data['yx_send_code_task_receipt'][] = $data;
                    } elseif ($channel['business_id'] == 9) { //游戏
                        $receipt_data['yx_send_game_task_receipt'][] = $data;
                    }
                }
                // $receipt_data[] = $data;
                if ($i >= 100) {
                    Db::startTrans();
                    try {
                        /*  if ($channel['business_id'] == 5) { //营销{}
                        Db::table('yx_send_task_receipt')->insertAll($receipt_data);
                        } else if ($channel['business_id'] == 6) { //行业
                        Db::table('yx_send_code_task_receipt')->insertAll($receipt_data);
                        } elseif ($channel['business_id'] == 9) { //游戏
                        Db::table('yx_send_game_task_receipt')->insertAll($receipt_data);
                        } */
                        foreach ($receipt_data as $key => $value) {
                            Db::table($key)->insertAll($value);
                        }
                        Db::commit();
                    } catch (\Exception $e) {
                        Db::rollback();
                        exception($e);
                    }
                    unset($receipt_data);
                    $i = 0;
                }
                $i++;
            }
            if (!empty($receipt_data)) {
                Db::startTrans();
                try {
                    /*  if ($channel['business_id'] == 5) { //营销{}
                    Db::table('yx_send_task_receipt')->insertAll($receipt_data);
                    } else if ($channel['business_id'] == 6) { //行业
                    Db::table('yx_send_code_task_receipt')->insertAll($receipt_data);
                    } elseif ($channel['business_id'] == 9) { //游戏
                    Db::table('yx_send_game_task_receipt')->insertAll($receipt_data);
                    } */
                    foreach ($receipt_data as $key => $value) {
                        Db::table($key)->insertAll($value);
                    }

                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    exception($e);
                }
                unset($receipt_data);
            }
            sleep(10);
        }
    }

    public function errotRpush()
    {
        $redis                 = Phpredis::getConn();
        $redisMessageCodeMsgId = 'index:meassage:code:msg:id:1';

        $redisMessageCodeDeliver = 'index:meassage:code:new:deliver:1'; //行业通知MsgId
        // {"Stat":"DELIVRD","Submit_time":"2001161532","Done_time":"2001161534","mobile":"13739310156\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000","receive_time":1579160061,"Msg_Id":"406718912655530494"}
        exit('退出');
        $redis->rpush("index:meassage:code:unknow:deliver:24", json_encode([
            'Stat'         => 'DELIVRD',
            'Submit_time'  => '2001161532',
            'Done_time'    => '2001161534',
            'mobile'       => '13739310156',
            'receive_time' => '1579160061',
            'Msg_Id'       => '406718912655530494',
        ]));
        while (true) {
            $status     = $redis->lpop("index:meassage:code:unknow:deliver:24");
            $new_status = json_decode($status, true);
            $mesage     = $redis->hget($redisMessageCodeMsgId, $new_status['Msg_Id']);
            if ($mesage) {
                $redis->hdel($redisMessageCodeMsgId, $new_status['Msg_Id']);
                // $redis->rpush($redisMessageCodeDeliver,$mesage.":".$Msg_Content['Stat']);
                $mesage         = json_decode($mesage, true);
                $mesage['Stat'] = $new_status['Stat'];
                // $mesage['Msg_Id']        = $Msg_Content['Msg_Id1'] . $Msg_Content['Msg_Id2'];
                $mesage['Submit_time']  = $new_status['Submit_time'];
                $mesage['Done_time']    = $new_status['Done_time'];
                $mesage['receive_time'] = time(); //回执时间戳
                $redis->rpush($redisMessageCodeDeliver, json_encode($mesage));
            }
        }
    }

    public function pushUpRiver()
    {
        $redis = Phpredis::getConn();
        $redis->rpush('index:message:code:upriver:111', '{"mobile":"15201926171","message_info":"1","develop_code":"1503"}');
    }

    public function getUpRiver()
    {
        $redis = Phpredis::getConn();
        /* $redis->rpush('index:message:code:upriver:95', json_encode([
            'mobile' => 18652851494,
            'message_info' => '3',
            'develop_code' => '7195',
        ])); */
        /*  $redis->rpush('index:message:code:upriver:95', json_encode([
            'mobile' => 15618356476,
            'message_info' => '3',
            'develop_code' => '7195',
        ])); */
        // $redis->rpush('index:message:code:upriver:111','{"mobile":"15821193682","message_info":"1","develop_code":"1503"}');
        // $redis->rpush('index:message:code:upriver:111','{"mobile":"15821193682","message_info":"2","develop_code":"6594"}');
        // $redis->rpush('index:message:code:upriver:112','{"mobile":"15821193682","message_info":"3","develop_code":"2580"}');
        // $redis->rpush('index:message:code:upriver:111','{"mobile":"15821193682","message_info":"1","develop_code":"1750"}');
        // $redis->rpush('index:message:code:upriver:111', '{"mobile":"18917638640","message_info":"1","develop_code":"1503"}');
        try {
            while (true) {
                $channels = Db::query("SELECT * FROM yx_sms_sending_channel WHERE `delete_time` = 0 ");
                foreach ($channels as $key => $value) {
                    if (in_array($value['id'], [83, 84, 86, 87, 88, 94, 153, 154, 155, 156, 157])) {
                        continue;
                    }
                    $redisMessageUpRiver = 'index:message:code:upriver:' . $value['id'];
                    while (true) {
                        $messageupriver = $redis->lpop($redisMessageUpRiver);
                        if (empty($messageupriver)) {
                            break;
                        }
                        // print_r($value['id']);
                        $business_id          = 0;
                        $encodemessageupriver = json_decode($messageupriver, true);
                        $sql                  = '';
                        if (!empty($encodemessageupriver['develop_code']) && strlen($encodemessageupriver['develop_code']) <= 6) {
                            $sql = '';
                            $sql = "SELECT  `uid`,`id`,`task_no` FROM ";
                            if ($value['business_id'] == 5) { //营销
                                $sql .= " yx_user_send_task_log  WHERE `mobile` = '" . $encodemessageupriver['mobile'] . "'";
                                $business_id = 5;
                            } elseif ($value['business_id'] == 6) { // 行业
                                $sql .= " yx_user_send_code_task_log WHERE `mobile` = '" . $encodemessageupriver['mobile'] . "' ";
                                $business_id = 6;
                            } elseif ($value['business_id'] == 9) { //游戏
                                $sql .= " yx_user_send_game_task WHERE `mobile_content` = '" . $encodemessageupriver['mobile'] . "' ";
                                $business_id = 9;
                            } elseif ($value['business_id'] == 7) { //高投诉网贷
                                $sql .= " yx_user_send_task_log WHERE `mobile` = '" . $encodemessageupriver['mobile'] . "'";
                                $business_id = 7;
                            } elseif ($value['business_id'] == 8) { //彩信
                                $sql .= " yx_user_multimedia_message_log WHERE `mobile` = '" . $encodemessageupriver['mobile'] . "'";
                                $business_id = 8;
                            }
                            $sql .= " AND  `develop_no` = " . $encodemessageupriver['develop_code'] . " AND `channel_id` = " . $value['id'] . " ORDER BY `id` DESC LIMIT 1 ";
                            if ($value['id'] == 140) {
                                $sql                  = "SELECT `uid`,`id`,`task_no` FROM ";
                                if ($value['business_id'] == 5) { //营销
                                    $sql .= " yx_user_send_task_log  WHERE `mobile` = '" . $encodemessageupriver['mobile'] . "' AND `uid` = 270 ";
                                    $business_id = 5;
                                } elseif ($value['business_id'] == 6) { // 行业
                                    $sql .= " yx_user_send_code_task_log WHERE `mobile` = '" . $encodemessageupriver['mobile'] . "'  AND `uid` = 270  ";
                                    $business_id = 6;
                                }
                                $sql .= "  AND `channel_id` = " . $value['id'] . " ORDER BY `id` DESC LIMIT 1 ";
                            } elseif ($value['id'] == 150) {
                                $sql                  = "SELECT `uid`,`id`,`task_no` FROM ";
                                if ($value['business_id'] == 5) { //营销
                                    $sql .= " yx_user_send_task_log  WHERE `mobile` = '" . $encodemessageupriver['mobile'] . "' AND `uid` = 270 ";
                                    $business_id = 5;
                                } elseif ($value['business_id'] == 6) { // 行业
                                    $sql .= " yx_user_send_code_task_log WHERE `mobile` = '" . $encodemessageupriver['mobile'] . "'  AND `uid` = 270  ";
                                    $business_id = 6;
                                }
                                $sql .= "  AND `channel_id` = " . $value['id'] . " ORDER BY `id` DESC LIMIT 1 ";
                            }
                            $message = Db::query($sql);
                            // echo $sql;
                            // echo "\n";
                            if (!empty($message)) {
                                //上行入库

                                Db::table('yx_user_upriver')->insert(['mobile' => $encodemessageupriver['mobile'], 'uid' => $message[0]['uid'], 'task_no' => $message[0]['task_no'], 'message_info' => $encodemessageupriver['message_info'], 'create_time' => time(), 'business_id' => $business_id]);
                                //上行写入用户调用位置
                                $user = Db::query("SELECT `need_upriver_api`,`pid` FROM `yx_users` WHERE `id` = " . $message[0]['uid']);
                                if ($user && $user[0]['need_upriver_api'] == 2) {
                                    if ($user[0]['pid'] == 137) {
                                        if ($value['business_id'] == 5) { //营销
                                            $msg_id = Db::query("SELECT `send_msg_id` FROM yx_user_send_task WHERE `task_no` = '" . $message[0]['task_no'] . "'");
                                        } elseif ($value['business_id'] == 6) { // 行业
                                            $msg_id = Db::query("SELECT `send_msg_id` FROM yx_user_send_code_task WHERE `task_no` = '" . $message[0]['task_no'] . "'");
                                        }
                                        if ($user && $user[0]['need_upriver_api'] == 2) {
                                            $redis->rpush("index:message:upriver:" . $message[0]['uid'], json_encode([
                                                'mobile' => $encodemessageupriver['mobile'], 'message_info' => $encodemessageupriver['message_info'],
                                                'msg_id' => $msg_id[0]['send_msg_id'], 'business_id' => $business_id, 'get_time' => date('Y-m-d H:i:s', time())
                                            ]));
                                        }
                                    } else {
                                        $redis->rpush("index:message:upriver:" . $message[0]['uid'], json_encode(['mobile' => $encodemessageupriver['mobile'], 'message_info' => $encodemessageupriver['message_info'], 'business_id' => $business_id, 'get_time' => date('Y-m-d H:i:s', time()), 'develop_no' => $encodemessageupriver['develop_code']]));
                                    }
                                }
                            } else {
                                $sql = '';
                                $sql = "SELECT  `uid`,`id`,`task_no` FROM ";
                                if ($value['business_id'] == 5) { //营销
                                    $sql .= " yx_user_send_task_log  WHERE `mobile` = '" . $encodemessageupriver['mobile'] . "'";
                                    $business_id = 5;
                                } elseif ($value['business_id'] == 6) { // 行业
                                    $sql .= " yx_user_send_code_task_log WHERE `mobile` = '" . $encodemessageupriver['mobile'] . "' ";
                                    $business_id = 6;
                                } elseif ($value['business_id'] == 9) { //游戏
                                    $sql .= " yx_user_send_game_task WHERE `mobile_content` = '" . $encodemessageupriver['mobile'] . "' ";
                                    $business_id = 9;
                                } elseif ($value['business_id'] == 7) { //高投诉网贷
                                    $sql .= " yx_user_send_task_log WHERE `mobile` = '" . $encodemessageupriver['mobile'] . "'";
                                    $business_id = 7;
                                } elseif ($value['business_id'] == 8) { //彩信
                                    $sql .= " yx_user_multimedia_message_log WHERE `mobile` = '" . $encodemessageupriver['mobile'] . "'";
                                    $business_id = 8;
                                }
                                $sql .= "  AND `channel_id` = " . $value['id'] . " ORDER BY `id` DESC LIMIT 1 ";
                                // echo $sql;die;
                                $message = Db::query($sql);
                                if (!empty($message)) {
                                    //上行入库
                                    Db::table('yx_user_upriver')->insert(['mobile' => $encodemessageupriver['mobile'], 'uid' => $message[0]['uid'], 'task_no' => $message[0]['task_no'], 'message_info' => $encodemessageupriver['message_info'], 'create_time' => time(), 'business_id' => $business_id]);
                                    //上行写入用户调用位置

                                    $user = Db::query("SELECT `need_upriver_api`,`pid` FROM `yx_users` WHERE `id` = " . $message[0]['uid']);
                                    if ($user && $user[0]['need_upriver_api'] == 2) {
                                        if ($user[0]['pid'] == 137) {
                                            if ($value['business_id'] == 5) { //营销
                                                $msg_id = Db::query("SELECT `send_msg_id` FROM yx_user_send_task WHERE `task_no` = '" . $message[0]['task_no'] . "'");
                                            } elseif ($value['business_id'] == 6) { // 行业
                                                $msg_id = Db::query("SELECT `send_msg_id` FROM yx_user_send_code_task WHERE `task_no` = '" . $message[0]['task_no'] . "'");
                                            }
                                            $redis->rpush("index:message:upriver:" . $message[0]['uid'], json_encode(['mobile' => $encodemessageupriver['mobile'], 'message_info' => $encodemessageupriver['message_info'], 'msg_id' => $msg_id[0]['send_msg_id'], 'business_id' => $business_id, 'get_time' => date('Y-m-d H:i:s', time())]));
                                        } else {
                                            $redis->rpush("index:message:upriver:" . $message[0]['uid'], json_encode(['mobile' => $encodemessageupriver['mobile'], 'message_info' => $encodemessageupriver['message_info'], 'business_id' => $business_id, 'get_time' => date('Y-m-d H:i:s', time())]));
                                        }
                                    }
                                }
                            }
                        } else {
                            if ($value['id'] == 140) {
                                $sql                  = "SELECT `uid`,`id`,`task_no` FROM ";
                                if ($value['business_id'] == 5) { //营销
                                    $sql .= " yx_user_send_task_log  WHERE `mobile` = '" . $encodemessageupriver['mobile'] . "' AND `uid` = 270 ";
                                    $business_id = 5;
                                } elseif ($value['business_id'] == 6) { // 行业
                                    $sql .= " yx_user_send_code_task_log WHERE `mobile` = '" . $encodemessageupriver['mobile'] . "'  AND `uid` = 270  ";
                                    $business_id = 6;
                                }
                                $sql .= "  AND `channel_id` = " . $value['id'] . " ORDER BY `id` DESC LIMIT 1 ";
                            } elseif ($value['id'] == 150) {
                                $sql                  = "SELECT `uid`,`id`,`task_no` FROM ";
                                if ($value['business_id'] == 5) { //营销
                                    $sql .= " yx_user_send_task_log  WHERE `mobile` = '" . $encodemessageupriver['mobile'] . "' AND `uid` = 270 ";
                                    $business_id = 5;
                                } elseif ($value['business_id'] == 6) { // 行业
                                    $sql .= " yx_user_send_code_task_log WHERE `mobile` = '" . $encodemessageupriver['mobile'] . "'  AND `uid` = 270  ";
                                    $business_id = 6;
                                }
                                $sql .= "  AND `channel_id` = " . $value['id'] . " ORDER BY `id` DESC LIMIT 1 ";
                            } else {
                                $sql                  = "SELECT `uid`,`id`,`task_no` FROM ";
                                if ($value['business_id'] == 5) { //营销
                                    $sql .= " yx_user_send_task_log  WHERE `mobile` = '" . $encodemessageupriver['mobile'] . "'";
                                    $business_id = 5;
                                } elseif ($value['business_id'] == 6) { // 行业
                                    $sql .= " yx_user_send_code_task_log WHERE `mobile` = '" . $encodemessageupriver['mobile'] . "' ";
                                    $business_id = 6;
                                } elseif ($value['business_id'] == 9) { //游戏
                                    $sql .= " yx_user_send_game_task WHERE `mobile_content` = '" . $encodemessageupriver['mobile'] . "' ";
                                    $business_id = 9;
                                } elseif ($value['business_id'] == 7) { //高投诉网贷
                                    $sql .= " yx_user_send_task_log WHERE `mobile` = '" . $encodemessageupriver['mobile'] . "'";
                                    $business_id = 7;
                                } elseif ($value['business_id'] == 8) { //彩信
                                    $sql .= " yx_user_multimedia_message_log WHERE `mobile` = '" . $encodemessageupriver['mobile'] . "'";
                                    $business_id = 8;
                                }
                                $sql .= "  AND `channel_id` = " . $value['id'] . " ORDER BY `id` DESC LIMIT 1 ";
                            }

                            $message = Db::query($sql);
                            if (!empty($message)) {
                                //上行入库
                                Db::table('yx_user_upriver')->insert(['mobile' => $encodemessageupriver['mobile'], 'uid' => $message[0]['uid'], 'task_no' => $message[0]['task_no'], 'message_info' => $encodemessageupriver['message_info'], 'create_time' => time(), 'business_id' => $business_id]);
                                //上行写入用户调用位置
                                $user = Db::query("SELECT `need_upriver_api` FROM `yx_users` WHERE `id` = " . $message[0]['uid']);
                                if ($user && $user[0]['need_upriver_api'] == 2) {
                                    $redis->rpush("index:message:upriver:" . $message[0]['uid'], json_encode(['mobile' => $encodemessageupriver['mobile'], 'message_info' => $encodemessageupriver['message_info'], 'business_id' => $business_id, 'get_time' => date('Y-m-d H:i:s', time())]));
                                }
                            }
                        }
                    }
                }
                sleep(30);
            }
        } catch (\Exception $th) {
            exception($th);
        }
    }

    public function updateUpRiver()
    {
        $upriver = Db::query("SELECT * FROM yx_user_upriver");
        foreach ($upriver as $key => $value) {
            # code...
        }
    }

    public function verifyMobileSource()
    {
        $mobilesend = 15997595078;
        $prefix     = substr(trim($mobilesend), 0, 7);
        $res        = Db::query("SELECT `source`,`province_id`,`province` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
        $newres     = array_shift($res);
        print_r($newres);
        die;
    }

    /* public function refureTaskLog()
    {
    $redis = Phpredis::getConn();
    $j = 1;
    for ($i = 173993; $i < 174487; $i++) {
    $sendTask = $this->getSendCodeTask($i);
    if (empty($sendTask)) {

    continue;
    }
    if (empty($sendTask['channel_id'])) {
    continue;
    }
    $mobilesend = [];

    $mobilesend = explode(',', $sendTask['mobile_content']);
    $mobilesend = array_filter($mobilesend);

    $channel_id    = 0;
    $channel_id    = $sendTask['channel_id'];
    if (empty($channel_id)) {
    continue;
    }

    for ($n = 0; $n < count($mobilesend); $n++) {
    $send_log = [];
    $sendmessage = [];
    if (checkMobile(trim($mobilesend[$n])) == true) {
    $send_log = [
    'task_no'      => $sendTask['task_no'],
    'uid'          => $sendTask['uid'],
    'source'       => $sendTask['source'],
    'task_content' => $sendTask['task_content'],
    'mobile'       => $mobilesend[$n],
    'send_status'  => 2,
    'channel_id'  => $channel_id,
    'create_time'  => $sendTask['create_time'],
    ];

    $num = mt_rand(0, 1000);
    if ($num <= 20) {
    if ($num <= 6) {
    $send_log['status_message'] = 'MK:1008';
    } else {
    $send_log['status_message'] = 'MK:0001';
    }
    // $send_log['status_info'] = '发送失败';
    } else {
    // $send_log['status_info'] = '发送成功';
    $send_log['status_message'] = 'DELIVRD';
    }
    // if (!empty($sendTask['develop_no'])) {
    //     $sendmessage['develop_code'] = $sendTask['develop_no'];
    // }
    // $res = $this->redis->rpush($redisMessageMarketingSend . ":" . $channel_id, json_encode($sendmessage)); //三体营销通道

    $true_log[] = $send_log;
    } else {
    $send_log = [
    'task_no'        => $sendTask['task_no'],
    'uid'            => $sendTask['uid'],
    // 'title'          => $sendTask['task_name'],
    'task_content'        => $sendTask['task_content'],
    'source'       => $sendTask['source'],
    'mobile'         => $mobilesend[$n],
    'send_status'    => 4,
    'create_time'    => $sendTask['create_time'],
    'status_message' => 'DB:0101', //无效号码
    'real_message'   => 'DB:0101',
    ];
    $all_log[] = $send_log;
    }

    $j++;
    if ($j > 100) {
    $j = 1;
    Db::startTrans();
    try {
    Db::table('yx_user_send_code_task_log')->insertAll($true_log);
    if (!empty($all_log)) {
    Db::table('yx_user_send_code_task_log')->insertAll($all_log);
    }
    Db::commit();
    } catch (\Exception $e) {
    exception($e);
    }
    }

    if ($sendTask['uid'] == 47 || $sendTask['uid'] == 49 || $sendTask['uid'] == 51 || $sendTask['uid'] == 52 || $sendTask['uid'] == 53 || $sendTask['uid'] == 54 || $sendTask['uid'] == 55) { //推送给美丽田园
    // https://zhidao.baidu.com/question/412076997.html
    if (strpos($send_log['task_content'], '问卷') !== false) {
    $request_url = "http://116.228.60.189:15901/rtreceive?";
    $request_url .= 'task_no=' . trim($sendTask['task_no']) . "&status_message=" . "DELIVRD" . "&mobile=" . trim($send_log['mobile']) . "&send_time=" . trim(date('YmdHis', $send_log['create_time'] + $num));
    } else {
    $request_url = "http://116.228.60.189:15901/rtreceive?";
    $request_url .= 'task_no=' . trim($sendTask['task_no']) . "&status_message=" . trim($send_log['status_message']) . "&mobile=" . trim($send_log['mobile']) . "&send_time=" . trim(date('YmdHis', $send_log['create_time'] + $num));
    }

    // print_r($request_url);
    sendRequest($request_url);

    usleep(20000);
    } else {
    $redis->rpush('index:meassage:code:user:receive:' . $sendTask['uid'], json_encode([
    'task_no' =>  trim($sendTask['task_no']),
    'status_message' =>   trim($send_log['status_message']),
    'mobile' =>   trim($send_log['mobile']),
    // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
    'send_time' => trim(date('YmdHis', $send_log['create_time'] + $num)),
    ])); //写入用户带处理日志
    }
    }

    // foreach ($mobilesend as $key => $kvalue) {
    //     if (in_array($channel_id, [2, 6, 7, 8])) {
    //         // $getSendTaskSql = "select source,province_id,province from yx_number_source where `mobile` = '".$prefix."' LIMIT 1";
    //     }
    // }
    // exit("SUCCESS");
    }

    if (!empty($true_log)) {
    Db::startTrans();
    try {
    Db::table('yx_user_send_code_task_log')->insertAll($true_log);
    if (!empty($all_log)) {
    Db::table('yx_user_send_code_task_log')->insertAll($all_log);
    }
    Db::commit();
    } catch (\Exception $e) {
    // $this->redis->rPush('index:meassage:business:sendtask', $send);

    Db::rollback();
    exception($e);
    }
    }
    } */

    public function delRepetition()
    {
        $del_ids = [];
        //  for ($i = 158434; $i < 173332; $i++) {
        for ($i = 236983; $i < 238632; $i++) {
            $sendTask = $this->getSendTask($i);
            if (empty($sendTask)) {
                continue;
            }
            $task_no = $sendTask['task_no'];
            $mobile  = explode(',', $sendTask['mobile_content']);
            if (count($mobile) > 1) {
                // continue;
                foreach ($mobile as $key => $value) {
                    $log = Db::query("SELECT `id` FROM `yx_user_send_task_log` WHERE `task_no` = '" . $task_no . "' AND `mobile` = '" . $value . "'");

                    if (count($log) > 1) {
                        // print_r($sendTask['task_no']);
                        $has     = [];
                        $has[]   = $log[0]['id'];
                        $logs_id = array_column($log, 'id');

                        $del = array_diff($logs_id, $has);
                        foreach ($del as $key => $value) {
                            $del_ids[] = $value;
                        }
                        // print_r($logs_id);
                        // die;
                    }
                }
            } else {
                $log = Db::query("SELECT `id` FROM `yx_user_send_task_log` WHERE `task_no` = '" . $task_no . "'");
                if (count($log) > 1) {
                    $has     = [];
                    $has[]   = $log[0]['id'];
                    $logs_id = array_column($log, 'id');

                    $del = array_diff($logs_id, $has);
                    foreach ($del as $key => $value) {
                        $del_ids[] = $value;
                    }
                    // print_r($del_ids);
                    // die;
                }
            }
        }
        print_r($del_ids);
        // die;
        if ($del_ids) {
            $ids = join(',', $del_ids);
            Db::table('yx_user_send_task_log')->where("id in ($ids)")->delete();
        }
    }

    public function sendLengthUpdate()
    {
        for ($i = 104587; $i < 517807; $i++) {
            $log = Db::query("SELECT `send_length`,`task_content` FROM `yx_user_send_code_task_log` WHERE `id` = '" . $i . "'");
            if ($log) {
                if ($log[0]['send_length'] == 0) {
                    Db::table('yx_user_send_code_task_log')->where("id", $i)->update(['send_length' => mb_strlen($log[0]['task_content'])]);
                }
            }
        }
    }

    //日志写入到数据表中营销
    public function removeMultimediaTaskLog()
    {
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        $time = strtotime(date('Y-m-d 0:00:00', time()));
        // $start_time = strtotime(date('Y-m-d 0:00:00', strtotime("-3 day")));
        // $ids = Db::query("SELECT `id` FROM  `yx_user_send_task` WHERE `create_time` < " . $time . " AND  `create_time` >= " . $start_time . "   AND  `log_path` <> ''");
        $ids     = Db::query("SELECT `id` FROM  `yx_user_multimedia_message` WHERE `create_time` < " . $time . "   AND  `log_path` <> '' AND `id` > 52 ");
        $all_log = [];
        $j       = 1;
        // print_r($ids);
        // die;
        // echo count($ids);
        // die;
        for ($i = 0; $i < count($ids); $i++) {
            $sendTask   = $this->getMultimediaSendTask($ids[$i]['id']);
            $mobilesend = explode(',', $sendTask['mobile_content']);
            // print_r($sendTask);
            // die;
            foreach ($mobilesend as $key => $value) {
                $send_log = [];
                $send_log = [
                    'uid'         => $sendTask['uid'],
                    'task_no'     => $sendTask['task_no'],
                    'task_id'     => $ids[$i]['id'],
                    'mobile'      => trim($value),
                    'source'      => $sendTask['source'],
                    'send_status' => 2,
                    'create_time' => $sendTask['update_time'],
                ];
                $all_log[] = $send_log;
                $j++;
                if ($j > 100) {
                    Db::startTrans();
                    try {
                        Db::table('yx_user_multimedia_message_log')->insertAll($all_log);
                        Db::commit();
                    } catch (\Exception $e) {
                        Db::rollback();
                        exception($e);
                    }
                    $j = 1;
                    unset($all_log);
                }
            }
        }
        if (!empty($all_log)) {
            Db::startTrans();
            try {
                Db::table('yx_user_multimedia_message_log')->insertAll($all_log);
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                exception($e);
            }
        }
        exit('Success');
    }

    /* {"task_no":"mul20020515503481449866","uid":1,"mobile":"18616279075","status_message":"DELIVRD","send_status":3,"send_time":1580889993} */
    public function receiptMultimediaToBase($channel_id)
    {
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        $redis = Phpredis::getConn();
        /*         $redis->rpush('index:meassage:multimediamessage:deliver:' . $channel_id, json_encode(array(
        'task_no' => 'mul20040110342053330898',
        'uid' => '91',
        'mobile' => '15021417314',
        'status_message' => 'DELIVRD',
        'send_status' => 3,
        'send_time' => '1585710348',
        )));
        $redis->rpush('index:meassage:multimediamessage:deliver:' . $channel_id, json_encode(array(
        'task_no' => 'mul20040110490391162370',
        'uid' => '91',
        'mobile' => '13681834423',
        'status_message' => 'DELIVRD',
        'send_status' => 3,
        'send_time' => '1585709212',
        ))); */

        while (true) {
            try {
                $sendlog = $redis->lpop('index:meassage:multimediamessage:deliver:' . $channel_id);
                if (empty($sendlog)) {
                    // exit('Send Log IS null');
                    sleep(60);
                    continue;
                }
                $send_log    = json_decode($sendlog, true);
                $sendtasklog = Db::query("SELECT `id`,`create_time`,`uid`,`real_message`,`status_message` FROM `yx_user_multimedia_message_log` WHERE `task_no` = '" . $send_log['task_no'] . "' AND `mobile` = '" . $send_log['mobile'] . "' ");
                // die;
                $task = Db::query("SELECT `id`,`create_time`,`update_time`,`source`,`send_msg_id` FROM `yx_user_multimedia_message` WHERE `task_no` = '" . $send_log['task_no'] . "' ");
                if (empty($sendtasklog)) {
                    Db::startTrans();

                    Db::table('yx_user_multimedia_message_log')->insert([
                        'uid'            => $send_log['uid'],
                        'task_no'        => $send_log['task_no'],
                        'mobile'         => $send_log['mobile'],
                        'send_status'    => $send_log['send_status'],
                        'create_time'    => $task[0]['update_time'],
                        'update_time'    => $send_log['send_time'],
                        'real_message'   => $send_log['status_message'],
                        'status_message' => $send_log['status_message'],
                        'task_id'        => $task[0]['id'],
                        'source'         => $task[0]['source'],
                    ]);
                    Db::commit();

                    $redis->rpush('index:meassage:multimediamessage:deliver:' . $channel_id, json_encode($send_log));
                } else {
                    if (!empty($sendtasklog[0]['real_message'])) {
                        continue;
                    }
                    Db::startTrans();
                    Db::table('yx_user_multimedia_message_log')->where('id', $sendtasklog[0]['id'])->update(['real_message' => $send_log['status_message'], 'status_message' => $send_log['status_message'],  'send_status' => $send_log['send_status'], 'update_time' => $send_log['send_time']]);
                    Db::commit();
                    if (!empty($sendtasklog[0]['status_message'])) {
                        continue;
                    }
                }
                if (strpos($send_log['status_message'], 'DB:0141') !== false || strpos($send_log['status_message'], 'MBBLACK') !== false || strpos($send_log['status_message'], 'BLACK') !== false) {
                    $message_info = '黑名单';
                } else if ($send_log['status_message'] == 'DELIVRD') {
                    $message_info = '发送成功';
                } else if (in_array(trim($send_log['status_message']), ['REJECTD', 'REJECT', 'MA:0001', '4442'])) {
                    $send_log['status_message'] = 'DELIVRD';
                    $message_info = '发送成功';
                } else {
                    $message_info = '发送失败';
                }
                $user = Db::query("SELECT `pid` FROM yx_users WHERE `id` = " . $send_log['uid']);
                if ($user[0]['pid'] == 137) {
                    $redis->rpush('index:meassage:code:user:mulreceive:' . $send_log['uid'], json_encode([
                        'task_no'        => $send_log['task_no'],
                        'status_message' => trim($send_log['status_message']),
                        'msg_id'         => trim($task[0]['send_msg_id']),
                        'message_info'   => $message_info,
                        'mobile'         => trim($send_log['mobile']),
                        // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                        'send_time'      => isset($send_log['send_time']) ? date('Y-m-d H:i:s', trim($send_log['send_time'])) : date('Y-m-d H:i:s', time()),
                        'smsCount' => 1,
                        'smsIndex' => 1,
                    ])); //写入用户带处理日志
                } else {
                    $redis->rpush('index:meassage:code:user:mulreceive:' . $send_log['uid'], json_encode([
                        'task_no'        => $send_log['task_no'],
                        'status_message' => trim($send_log['status_message']),
                        'message_info'   => $message_info,
                        'mobile'         => trim($send_log['mobile']),
                        // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                        'send_time'      => isset($send_log['send_time']) ? date('Y-m-d H:i:s', trim($send_log['send_time'])) : date('Y-m-d H:i:s', time()),
                    ])); //写入用户带处理日志
                }
            } catch (\Exception $th) {
                Db::rollback();
                $redis->rpush('index:meassage:multimediamessage:deliver:' . $channel_id, json_encode($send_log));
                exception($th);
            }
        }
    }

    public function updateMultimediamessageReceipt()
    {
        try {
            ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
            $redis = Phpredis::getConn();
            // $redis->rpush('index:meassage:multimediamessage:deliver', '{"task_id":"210115","mobile":"15618356476","status_message":"DELIVRD","send_time":"20200807151956"}');
            while (true) {
                $Received = updateReceivedForMessage();
                $sendlog = $redis->lpop('index:meassage:multimediamessage:deliver');
                if (empty($sendlog)) {
                    // exit('Send Log IS null');
                    sleep(60);
                    continue;
                }
                //
                $sendlog = json_decode($sendlog, true);
                if (empty($sendlog['task_id'])) {
                    continue;
                }
                $task = Db::query("SELECT `task_no`,`send_msg_id`,`uid` FROM yx_user_multimedia_message WHERE `id` = " . $sendlog['task_id']);
                if (empty($task)) {
                    continue;
                }
                $task = $task[0];
                if (in_array(trim($sendlog['status_message']), $Received)) {
                    $stat = 'DELIVRD';
                } else {
                    $stat = $sendlog['status_message'];
                }
                if ($stat == 'DELIVRD') {
                    $message_info = '发送成功';
                    $send_status = 3;
                } else {
                    $message_info = '发送失败';
                    $send_status = 4;
                }

                Db::startTrans();
                Db::table('yx_user_multimedia_message_log')->where(['task_no' => $task['task_no'], 'mobile' => trim($sendlog['mobile'])])->update(['real_message' => $sendlog['status_message'], 'status_message' => $stat,  'send_status' => $send_status, 'update_time' => trim($sendlog['send_time'])]);
                Db::commit();
                $redis->rpush('index:meassage:code:user:mulreceive:' . $task['uid'], json_encode([
                    'task_no'        => $task['task_no'],
                    'status_message' => $stat,
                    'msg_id'         => trim($task['send_msg_id']),
                    'message_info'   => $message_info,
                    'mobile'         => trim($sendlog['mobile']),
                    // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                    'send_time'      => isset($sendlog['send_time']) ? date('Y-m-d H:i:s', trim($sendlog['send_time'])) : date('Y-m-d H:i:s', time()),
                    'smsCount' => 1,
                    'smsIndex' => 1,
                ])); //写入用户带处理日志
                // echo Db::getLastSQL();
                // echo "\n";
            }
        } catch (\Exception $th) {
            //throw $th;
            exception($th);
        }
    }

    public function updateSupMessageReceipt()
    {
        try {
            ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
            $redis = Phpredis::getConn();
            // $redis->rpush('index:meassage:multimediamessage:deliver', '{"task_id":false,"mobile":"15821193682","status_message":"DELIVRD","send_time":1598439923}');
            while (true) {
                $Received = updateReceivedForMessage();
                $sendlog = $redis->lpop('index:meassage:supmessage:deliver');
                if (empty($sendlog)) {
                    // exit('Send Log IS null');
                    sleep(60);
                    continue;
                }
                $sendlog = json_decode($sendlog, true);
                if (!is_numeric($sendlog['task_id'])) {
                    // $task = Db::query("SELECT `task_no`,`send_msg_id`,`uid` FROM yx_user_sup_message WHERE `id` = " . $sendlog['task_id']);
                    continue;
                } else {
                    $task = Db::query("SELECT `task_no`,`send_msg_id`,`uid` FROM yx_user_sup_message WHERE `id` = " . $sendlog['task_id']);
                }

                if (empty($task)) {
                    continue;
                }
                $task = $task[0];
                if (in_array(trim($sendlog['status_message']), $Received)) {
                    $stat = 'DELIVRD';
                } else {
                    $stat = $sendlog['status_message'];
                }
                if ($stat == 'DELIVRD') {
                    $message_info = '发送成功';
                    $send_status = 3;
                } else {
                    $message_info = '发送失败';
                    $send_status = 4;
                }
                Db::startTrans();
                Db::table('yx_user_sup_message_log')->where(['task_no' => $task['task_no'], 'mobile' => trim($sendlog['mobile'])])->update(['real_message' => $sendlog['status_message'], 'status_message' => $stat,  'send_status' => $send_status, 'update_time' => strtotime(trim($sendlog['send_time']))]);
                Db::commit();
                $redis->rpush('index:meassage:code:user:supreceive:' . $task['uid'], json_encode([
                    'task_no'        => $task['task_no'],
                    'status_message' => $stat,
                    'msg_id'         => trim($task['send_msg_id']),
                    'message_info'   => $message_info,
                    'mobile'         => trim($sendlog['mobile']),
                    // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                    'send_time'      => isset($sendlog['send_time']) ? date('Y-m-d H:i:s', strtotime(trim($sendlog['send_time']))) : date('Y-m-d H:i:s', time()),
                    'smsCount' => 1,
                    'smsIndex' => 1,
                ])); //写入用户带处理日志
            }
        } catch (\Exception $th) {
            //throw $th;
            exception($th);
        }
    }

    public function reciveSendMessageFoMlty()
    {
        while (true) {
            // $time = strtotime(date('Y-m-d 0:00:00', time()));
            $start_time = strtotime('2020-02-05 0:00:00');
            $end_time   = strtotime("-3 day");
            // echo $start_time;
            // die;
            $code_task_log = Db::query("SELECT * FROM yx_user_send_code_task_log WHERE `uid` IN (47,49,51,52,53,54,55) AND `status_message` = '' AND `create_time` >= '" . $start_time . "' AND  `create_time` <= '" . $end_time . "' LIMIT 1 ");

            if (!empty($code_task_log)) {
                $task         = Db::query("SELECT `id` FROM yx_user_send_code_task WHERE `task_no` = '" . $code_task_log[0]['task_no'] . "' ");
                $task_receipt = Db::query("SELECT * FROM yx_send_code_task_receipt WHERE `task_id` = '" . $task[0]['id'] . "' AND `mobile` = '" . $code_task_log[0]['mobile'] . "' ");
                if (empty($task_receipt) && empty($code_task_log[0]['status_message'])) {
                    $request_url = "http://116.228.60.189:15901/rtreceive?";
                    $request_url .= 'task_no=' . trim($code_task_log[0]['task_no']) . "&status_message=" . "DELIVRD" . "&mobile=" . trim($code_task_log[0]['mobile']) . "&send_time=" . trim(date('YmdHis', time() + mt_rand(0, 500)));
                    // print_r($request_url);
                    sendRequest($request_url);
                    Db::startTrans();
                    try {
                        Db::table('yx_user_send_code_task_log')->where('id', $code_task_log[0]['id'])->update(['status_message' => 'DELIVRD', 'send_status' => 3, 'update_time' => time()]);
                        Db::commit();
                    } catch (\Exception $e) {
                        Db::rollback();
                        exception($e);
                    }
                    usleep(20000);
                } else {
                    Db::startTrans();
                    try {
                        Db::table('yx_user_send_code_task_log')->where('id', $code_task_log[0]['id'])->update(['status_message' => $task_receipt[0]['status_message'], 'real_message' => $task_receipt[0]['real_message'], 'send_status' => 3, 'update_time' => $task_receipt[0]['create_time']]);
                        Db::commit();
                    } catch (\Exception $e) {
                        Db::rollback();
                        exception($e);
                    }
                }
            } else {
                echo 'Over' . "\n";
                sleep(120);
            }
        }
    }

    //回执补推方法
    public function receiptFillPush()
    {
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        $redis = Phpredis::getConn();
        // $redis->rpush('index:message:receipt:fillpush', json_encode(['uid' => '47', 'start_time' => '1587216387', 'end_time' => '1587219548', 'type' => 'business', 'channel_id' => 61]));
        while (true) {
            $real_fill_push = $redis->lpop('index:message:receipt:fillpush');
            if ($real_fill_push) {
                $real_fill_push = json_decode($real_fill_push, true);
                $sql            = '';
                if ($real_fill_push['type'] == 'business') { //行业
                    $sql = "SELECT * FROM yx_user_send_code_task_log ";
                } elseif ($real_fill_push['type'] == 'marketing') { //营销
                    $sql = "SELECT * FROM yx_user_send_task_log ";
                } elseif ($real_fill_push['type'] == 'multimedia') { //彩信
                    $sql = "SELECT * FROM yx_user_multimedia_message_log ";
                }
                $sql .= " WHERE `uid` = '" . $real_fill_push['uid'] . "' AND `create_time` >= '" . $real_fill_push['start_time'] . "' AND  `create_time` <= '" . $real_fill_push['end_time'] . "'";
                if (isset($real_fill_push['channel_id'])) {
                    $sql .= " AND `channel_id` = " . $real_fill_push['channel_id'];
                }
                $receipt_data = Db::query($sql);
                if ($receipt_data) {
                    foreach ($receipt_data as $key => $value) {
                        // echo $value['id'] . "\n";
                        // $value['status_message'] = 'UNDELIV';
                        if ($value['status_message']) {
                            $request_url = "http://116.228.60.189:15901/rtreceive?";
                            $request_url .= 'task_no=' . trim($value['task_no']) . "&status_message=" . trim($value['status_message']) . "&mobile=" . trim($value['mobile']) . "&send_time=" . trim(date('YmdHis', $value['create_time'] + mt_rand(0, 20)));
                            // print_r($request_url);
                            // die;
                            sendRequest($request_url);
                            usleep(20000);
                        }
                    }
                }
            } else {
                // sleep(120);
                exit("FINISH");
            }
        }
    }

    public function businessSettlement()
    {
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        while (true) {
            $year_businessSettlement  = [];
            $month_businessSettlement = [];
            $day_businessSettlement   = [];
            $year_users               = [];
            $month_users              = [];
            $day_users                = [];
            // $start_time               = strtotime('-10 days');
            // print_r($start_time);die;
            $start_time = (int) strtotime(date('2020-07-01'));
            $Received = updateReceivedForMessage();
            array_push($Received, 'DELIVRD');
            // echo join(',',$Received);die;
            // $end_time = $start_time + 86400;
            // echo $end_time;die;
            while (true) {
                $end_time = $start_time + 86400;
                if ($end_time > time()) {
                    // break;
                    $end_time = time();
                    $day_businessSettlement   = [];
                    $day_users                = [];
                    $code_task_log = [];
                    $code_task_log            = Db::query("SELECT * FROM yx_user_send_code_task_log WHERE `create_time` < " . $end_time . " AND `create_time` >= " . $start_time);
                    foreach ($code_task_log as $key => $value) {
                        $send_length = mb_strlen($value['task_content'], 'utf8');
                        $num         = 1;
                        if (empty($value['status_message']) && empty($value['real_message'])) {
                            $task = Db::query("SELECT id FROM yx_user_send_code_task WHERE `task_no` = '" . $value['task_no'] . "' LIMIT 1 ");
                            if (empty($task)) {
                                continue;
                            }
                            $receipt = Db::query("SELECT `status_message` FROM yx_send_code_task_receipt WHERE `task_id` = '" . $task[0]['id'] . "' AND `mobile` = '" . $value['mobile'] . "' LIMIT 1 ");
                            if (empty($receipt)) {
                                if ($value['create_time'] + 259200 < time()) {
                                    $value['status_message'] = 'DELIVRD';
                                }
                            } else {
                                $value['status_message'] = $receipt[0]['status_message'];
                            }
                        }
                        if (in_array(trim($value['status_message']), $Received)) {
                            $value['status_message'] = 'DELIVRD';
                        }
                        if ($send_length > 70) {
                            $num = ceil($send_length / 67);
                        }
                        $day   = date('Ymd', $value['create_time']);
                        if (!array_key_exists($day, $day_users)) {
                            $day_users[$day] = [];
                        }
                        if (in_array($value['uid'], $day_users[$day])) {
                            $day_businessSettlement[$day][$value['uid']]['num'] += $num;
                            $day_businessSettlement[$day][$value['uid']]['mobile_num'] += 1;
                            if ($value['status_message'] == 'DELIVRD') {
                                if (isset($day_businessSettlement[$day][$value['uid']]['success'])) {
                                    $day_businessSettlement[$day][$value['uid']]['success'] += $num;
                                } else {
                                    $day_businessSettlement[$day][$value['uid']]['success'] = $num;
                                }
                            } elseif (empty($value['status_message'])) {
                                if (isset($day_businessSettlement[$day][$value['uid']]['unknown'])) {
                                    $day_businessSettlement[$day][$value['uid']]['unknown'] += $num;
                                } else {
                                    $day_businessSettlement[$day][$value['uid']]['unknown'] = $num;
                                }
                            } else {
                                if (isset($day_businessSettlement[$day][$value['uid']]['default'])) {
                                    $day_businessSettlement[$day][$value['uid']]['default'] += $num;
                                } else {
                                    $day_businessSettlement[$day][$value['uid']]['default'] = $num;
                                }
                                // $day_businessSettlement[$day][$value['uid']]['default'] = $num;
                            }
                        } else {
                            $day_users[$day][]                                         = $value['uid'];
                            $day_businessSettlement[$day][$value['uid']]['num']        = $num;
                            $day_businessSettlement[$day][$value['uid']]['mobile_num'] = 1;
                            if ($value['status_message'] == 'DELIVRD') {
                                $day_businessSettlement[$day][$value['uid']]['success'] = $num;
                            } elseif ($value['status_message'] == '') {
                                $day_businessSettlement[$day][$value['uid']]['unknown'] = $num;
                            } else {
                                $value[$day][$value['uid']]['default'] = $num;
                            }
                        }
                    }
                    Db::startTrans();
                    try {
                        foreach ($day_businessSettlement as $dkey => $d_value) {
                            foreach ($d_value as $key => $value) {
                                $success = isset($value['success']) ? $value['success'] : 0;
                                $num     = isset($value['num']) ? $value['num'] : 0;
                                if ($key == 47 && $dkey == 20200122) {
                                    $num = $num + 5784;
                                }
                                if ($key == 47 && $dkey == 20200125) {
                                    $num = $num + 289;
                                }
                                $day_user_settlement = [];
                                $day_user_settlement = [
                                    'timekey'     => $dkey,
                                    'uid'         => $key,
                                    'success'     => $success,
                                    'unknown'     => isset($value['unknown']) ? $value['unknown'] : 0,
                                    'default'     => isset($value['default']) ? $value['default'] : 0,
                                    'num'         => $num,
                                    'ratio'       => $success / $num * 100,
                                    'mobile_num'  => $value['mobile_num'],
                                    'business_id' => '6',
                                    'create_time' => time(),
                                    'update_time' => time(),
                                ];
                                $has = Db::query('SELECT * FROM `yx_statistics_day` WHERE `business_id` = 6 AND `timekey` = ' . $dkey . ' AND `uid` = ' . $key);
                                if ($has) {
                                    Db::table('yx_statistics_day')->where('id', $has[0]['id'])->update([
                                        'success'     => $success,
                                        'unknown'     => isset($value['unknown']) ? $value['unknown'] : 0,
                                        'default'     => isset($value['default']) ? $value['default'] : 0,
                                        'num'         => $num,
                                        'mobile_num'  => $value['mobile_num'],
                                        'ratio'       => $success / $num * 100,
                                        'update_time' => time(),
                                    ]);
                                } else {
                                    Db::table('yx_statistics_day')->insert($day_user_settlement);
                                }
                            }
                        }
                        Db::commit();
                    } catch (\Exception $e) {
                        Db::rollback();
                        exception($e);
                    }
                    break;
                }
                $day_businessSettlement   = [];
                $day_users                = [];
                $code_task_log = [];
                $code_task_log            = Db::query("SELECT * FROM yx_user_send_code_task_log WHERE `create_time` < " . $end_time . " AND `create_time` >= " . $start_time);
                foreach ($code_task_log as $key => $value) {
                    $send_length = mb_strlen($value['task_content'], 'utf8');
                    $num         = 1;
                    if (empty($value['status_message']) && empty($value['real_message'])) {
                        $task = Db::query("SELECT id FROM yx_user_send_code_task WHERE `task_no` = '" . $value['task_no'] . "' LIMIT 1 ");
                        if (empty($task)) {
                            continue;
                        }
                        $receipt = Db::query("SELECT `status_message` FROM yx_send_code_task_receipt WHERE `task_id` = '" . $task[0]['id'] . "' AND `mobile` = '" . $value['mobile'] . "' LIMIT 1 ");
                        if (empty($receipt)) {
                            if ($value['create_time'] + 259200 < time()) {
                                $value['status_message'] = 'DELIVRD';
                            }
                        } else {
                            $value['status_message'] = $receipt[0]['status_message'];
                        }
                    }
                    if ($send_length > 70) {
                        $num = ceil($send_length / 67);
                    }
                    $day   = date('Ymd', $value['create_time']);
                    if (!array_key_exists($day, $day_users)) {
                        $day_users[$day] = [];
                    }
                    if (in_array($value['uid'], $day_users[$day])) {
                        $day_businessSettlement[$day][$value['uid']]['num'] += $num;
                        $day_businessSettlement[$day][$value['uid']]['mobile_num'] += 1;
                        if ($value['status_message'] == 'DELIVRD') {
                            if (isset($day_businessSettlement[$day][$value['uid']]['success'])) {
                                $day_businessSettlement[$day][$value['uid']]['success'] += $num;
                            } else {
                                $day_businessSettlement[$day][$value['uid']]['success'] = $num;
                            }
                        } elseif (empty($value['status_message'])) {
                            if (isset($day_businessSettlement[$day][$value['uid']]['unknown'])) {
                                $day_businessSettlement[$day][$value['uid']]['unknown'] += $num;
                            } else {
                                $day_businessSettlement[$day][$value['uid']]['unknown'] = $num;
                            }
                        } else {
                            if (isset($day_businessSettlement[$day][$value['uid']]['default'])) {
                                $day_businessSettlement[$day][$value['uid']]['default'] += $num;
                            } else {
                                $day_businessSettlement[$day][$value['uid']]['default'] = $num;
                            }
                            // $day_businessSettlement[$day][$value['uid']]['default'] = $num;
                        }
                    } else {
                        $day_users[$day][]                                         = $value['uid'];
                        $day_businessSettlement[$day][$value['uid']]['num']        = $num;
                        $day_businessSettlement[$day][$value['uid']]['mobile_num'] = 1;
                        if ($value['status_message'] == 'DELIVRD') {
                            $day_businessSettlement[$day][$value['uid']]['success'] = $num;
                        } elseif ($value['status_message'] == '') {
                            $day_businessSettlement[$day][$value['uid']]['unknown'] = $num;
                        } else {
                            $value[$day][$value['uid']]['default'] = $num;
                        }
                    }
                }
                Db::startTrans();
                try {
                    foreach ($day_businessSettlement as $dkey => $d_value) {
                        foreach ($d_value as $key => $value) {
                            $success = isset($value['success']) ? $value['success'] : 0;
                            $num     = isset($value['num']) ? $value['num'] : 0;
                            if ($key == 47 && $dkey == 20200122) {
                                $num = $num + 5784;
                            }
                            if ($key == 47 && $dkey == 20200125) {
                                $num = $num + 289;
                            }
                            $day_user_settlement = [];
                            $day_user_settlement = [
                                'timekey'     => $dkey,
                                'uid'         => $key,
                                'success'     => $success,
                                'unknown'     => isset($value['unknown']) ? $value['unknown'] : 0,
                                'default'     => isset($value['default']) ? $value['default'] : 0,
                                'num'         => $num,
                                'ratio'       => $success / $num * 100,
                                'mobile_num'  => $value['mobile_num'],
                                'business_id' => '6',
                                'create_time' => time(),
                                'update_time' => time(),
                            ];
                            $has = Db::query('SELECT * FROM `yx_statistics_day` WHERE `business_id` = 6 AND `timekey` = ' . $dkey . ' AND `uid` = ' . $key);
                            if ($has) {
                                Db::table('yx_statistics_day')->where('id', $has[0]['id'])->update([
                                    'success'     => $success,
                                    'unknown'     => isset($value['unknown']) ? $value['unknown'] : 0,
                                    'default'     => isset($value['default']) ? $value['default'] : 0,
                                    'num'         => $num,
                                    'mobile_num'  => $value['mobile_num'],
                                    'ratio'       => $success / $num * 100,
                                    'update_time' => time(),
                                ]);
                            } else {
                                Db::table('yx_statistics_day')->insert($day_user_settlement);
                            }
                        }
                    }
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    exception($e);
                }
                $start_time = $end_time;
            }

            sleep(900);
        }

        /* // $task_log                 = Db::query("SELECT * FROM yx_user_send_task_log WHERE `create_time` < " . time() . " AND `create_time` >= " . $start_time);
         $code_task_log            = Db::query("SELECT * FROM yx_user_send_code_task_log WHERE `create_time` < " . time() . " AND `create_time` >= " . $start_time);
         // print_r(count($code_task_log));
         // die;
         foreach ($code_task_log as $key => $value) {
             $send_length = mb_strlen($value['task_content'], 'utf8');
             $num         = 1;
             if (empty($value['status_message']) && empty($value['real_message'])) {
                 $task = Db::query("SELECT id FROM yx_user_send_code_task WHERE `task_no` = '" . $value['task_no'] . "' LIMIT 1 ");
                 if (empty($task)) {
                     continue;
                 }
                 $receipt = Db::query("SELECT `status_message` FROM yx_send_code_task_receipt WHERE `task_id` = '" . $task[0]['id'] . "' AND `mobile` = '" . $value['mobile'] . "' LIMIT 1 ");
                 if (empty($receipt)) {
                     if ($value['create_time'] + 259200 < time()) {
                         $value['status_message'] = 'DELIVRD';
                     }
                 } else {
                     $value['status_message'] = $receipt[0]['status_message'];
                 }
             }
             if ($send_length > 70) {
                 $num = ceil($send_length / 67);
             }
             $year  = date('Y', $value['create_time']);
             $month = date('Ym', $value['create_time']);
             $day   = date('Ymd', $value['create_time']);
             if (!array_key_exists($year, $year_users)) {
                 $year_users[$year] = [];
             }
             if (!array_key_exists($month, $month_users)) {
                 $month_users[$month] = [];
             }
             if (!array_key_exists($day, $day_users)) {
                 $day_users[$day] = [];
             }
             //年
             if (in_array($value['uid'], $year_users[$year])) {
                 $year_businessSettlement[$year][$value['uid']]['num'] += $num;
                 $year_businessSettlement[$year][$value['uid']]['mobile_num'] += 1;
                 if ($value['status_message'] == 'DELIVRD') {
                     if (isset($year_businessSettlement[$year][$value['uid']]['success'])) {
                         $year_businessSettlement[$year][$value['uid']]['success'] += $num;
                     } else {
                         $year_businessSettlement[$year][$value['uid']]['success'] = $num;
                     }
                 } elseif (empty($value['status_message'])) {
                     if (isset($year_businessSettlement[$year][$value['uid']]['unknown'])) {
                         $year_businessSettlement[$year][$value['uid']]['unknown'] += $num;
                     } else {
                         $year_businessSettlement[$year][$value['uid']]['unknown'] = $num;
                     }
                 } else {
                     if (isset($year_businessSettlement[$year][$value['uid']]['default'])) {
                         $year_businessSettlement[$year][$value['uid']]['default'] += $num;
                     } else {
                         $year_businessSettlement[$year][$value['uid']]['default'] = $num;
                     }
                     // $year_businessSettlement[$year][$value['uid']]['default'] = $num;
                 }
             } else {
                 $year_users[$year][]                                         = $value['uid'];
                 $year_businessSettlement[$year][$value['uid']]['num']        = $num;
                 $year_businessSettlement[$year][$value['uid']]['mobile_num'] = 1;
                 if ($value['status_message'] == 'DELIVRD') {
                     $year_businessSettlement[$year][$value['uid']]['success'] = $num;
                 } elseif ($value['status_message'] == '') {
                     $year_businessSettlement[$year][$value['uid']]['unknown'] = $num;
                 } else {
                     $year_businessSettlement[$year][$value['uid']]['default'] = $num;
                 }
             }
             //月
             if (in_array($value['uid'], $month_users[$month])) {
                 $month_businessSettlement[$month][$value['uid']]['num'] += $num;
                 $month_businessSettlement[$month][$value['uid']]['mobile_num'] += 1;
                 if ($value['status_message'] == 'DELIVRD') {
                     if (isset($month_businessSettlement[$month][$value['uid']]['success'])) {
                         $month_businessSettlement[$month][$value['uid']]['success'] += $num;
                     } else {
                         $month_businessSettlement[$month][$value['uid']]['success'] = $num;
                     }
                 } elseif (empty($value['status_message'])) {
                     if (isset($month_businessSettlement[$month][$value['uid']]['unknown'])) {
                         $month_businessSettlement[$month][$value['uid']]['unknown'] += $num;
                     } else {
                         $month_businessSettlement[$month][$value['uid']]['unknown'] = $num;
                     }
                 } else {
                     if (isset($month_businessSettlement[$month][$value['uid']]['default'])) {
                         $month_businessSettlement[$month][$value['uid']]['default'] += $num;
                     } else {
                         $month_businessSettlement[$month][$value['uid']]['default'] = $num;
                     }
                     // $month_businessSettlement[$month][$value['uid']]['default'] = $num;
                 }
             } else {
                 $month_users[$month][]                                         = $value['uid'];
                 $month_businessSettlement[$month][$value['uid']]['num']        = $num;
                 $month_businessSettlement[$month][$value['uid']]['mobile_num'] = 1;
                 if ($value['status_message'] == 'DELIVRD') {
                     $month_businessSettlement[$month][$value['uid']]['success'] = $num;
                 } elseif ($value['status_message'] == '') {
                     $month_businessSettlement[$month][$value['uid']]['unknown'] = $num;
                 } else {
                     $month_businessSettlement[$month][$value['uid']]['default'] = $num;
                 }
             }
             //日
             if (in_array($value['uid'], $day_users[$day])) {
                 $day_businessSettlement[$day][$value['uid']]['num'] += $num;
                 $day_businessSettlement[$day][$value['uid']]['mobile_num'] += 1;
                 if ($value['status_message'] == 'DELIVRD') {
                     if (isset($day_businessSettlement[$day][$value['uid']]['success'])) {
                         $day_businessSettlement[$day][$value['uid']]['success'] += $num;
                     } else {
                         $day_businessSettlement[$day][$value['uid']]['success'] = $num;
                     }
                 } elseif (empty($value['status_message'])) {
                     if (isset($day_businessSettlement[$day][$value['uid']]['unknown'])) {
                         $day_businessSettlement[$day][$value['uid']]['unknown'] += $num;
                     } else {
                         $day_businessSettlement[$day][$value['uid']]['unknown'] = $num;
                     }
                 } else {
                     if (isset($day_businessSettlement[$day][$value['uid']]['default'])) {
                         $day_businessSettlement[$day][$value['uid']]['default'] += $num;
                     } else {
                         $day_businessSettlement[$day][$value['uid']]['default'] = $num;
                     }
                     // $day_businessSettlement[$day][$value['uid']]['default'] = $num;
                 }
             } else {
                 $day_users[$day][]                                         = $value['uid'];
                 $day_businessSettlement[$day][$value['uid']]['num']        = $num;
                 $day_businessSettlement[$day][$value['uid']]['mobile_num'] = 1;
                 if ($value['status_message'] == 'DELIVRD') {
                     $day_businessSettlement[$day][$value['uid']]['success'] = $num;
                 } elseif ($value['status_message'] == '') {
                     $day_businessSettlement[$day][$value['uid']]['unknown'] = $num;
                 } else {
                     $value[$day][$value['uid']]['default'] = $num;
                 }
             }
         }
         Db::startTrans();
         try {
             //年度计费
             // foreach ($all_year_businessSettlement as $key => $value) {
             //     $has = Db::query('SELECT * FROM `yx_statistics_year` WHERE `` ');
             //     if ($has) {}else{

             //     }
             // }
             foreach ($year_businessSettlement as $ykey => $y_value) {
                 foreach ($y_value as $key => $value) {
                     $success = isset($value['success']) ? $value['success'] : 0;
                     $num     = isset($value['num']) ? $value['num'] : 0;
                     if ($key == 47 && $ykey == 2020) {
                         $num = $num + 5784 + 289;
                     }
                     $year_user_settlement = [];
                     $year_user_settlement = [
                         'timekey'     => $ykey,
                         'uid'         => $key,
                         'success'     => $success,
                         'unknown'     => isset($value['unknown']) ? $value['unknown'] : 0,
                         'default'     => isset($value['default']) ? $value['default'] : 0,
                         'num'         => $num,
                         'mobile_num'  => $value['mobile_num'],
                         'ratio'       => $success / $num * 100,
                         'business_id' => '6',
                         'create_time' => time(),
                         'update_time' => time(),
                     ];
                     $has = Db::query('SELECT * FROM `yx_statistics_year` WHERE `business_id` = 6 AND `timekey` = ' . $ykey . ' AND `uid` = ' . $key);
                     if ($has) {
                         Db::table('yx_statistics_year')->where('id', $has[0]['id'])->update([
                             'success'     => $success,
                             'unknown'     => isset($value['unknown']) ? $value['unknown'] : 0,
                             'default'     => isset($value['default']) ? $value['default'] : 0,
                             'num'         => $num,
                             'mobile_num'  => $value['mobile_num'],
                             'ratio'       => $success / $num * 100,
                             'update_time' => time(),
                         ]);
                     } else {
                         Db::table('yx_statistics_year')->insert($year_user_settlement);
                     }
                 }
             }
             foreach ($month_businessSettlement as $mkey => $m_value) {
                 foreach ($m_value as $key => $value) {
                     $success               = isset($value['success']) ? $value['success'] : 0;
                     $num                   = isset($value['num']) ? $value['num'] : 0;
                     $month_user_settlement = [];
                     if ($key == 47 && $mkey == 202001) {
                         $num = $num + 5784 + 289;
                     }
                     if ($key == 47 && $mkey == 202002) {
                         $value['default'] = 3431;
                     }
                     $month_user_settlement = [
                         'timekey'     => $mkey,
                         'uid'         => $key,
                         'success'     => $success,
                         'unknown'     => isset($value['unknown']) ? $value['unknown'] : 0,
                         'default'     => isset($value['default']) ? $value['default'] : 0,
                         'num'         => $num,
                         'ratio'       => $success / $num * 100,
                         'mobile_num'  => $value['mobile_num'],
                         'business_id' => '6',
                         'create_time' => time(),
                         'update_time' => time(),
                     ];
                     $has = Db::query('SELECT * FROM `yx_statistics_month` WHERE `business_id` = 6 AND `timekey` = ' . $mkey . ' AND `uid` = ' . $key);
                     if ($has) {
                         Db::table('yx_statistics_month')->where('id', $has[0]['id'])->update([
                             'success'     => $success,
                             'unknown'     => isset($value['unknown']) ? $value['unknown'] : 0,
                             'default'     => isset($value['default']) ? $value['default'] : 0,
                             'num'         => $num,
                             'mobile_num'  => $value['mobile_num'],
                             'ratio'       => $success / $num * 100,
                             'update_time' => time(),
                         ]);
                     } else {
                         Db::table('yx_statistics_month')->insert($month_user_settlement);
                     }
                 }
             }
             foreach ($day_businessSettlement as $dkey => $d_value) {
                 foreach ($d_value as $key => $value) {
                     $success = isset($value['success']) ? $value['success'] : 0;
                     $num     = isset($value['num']) ? $value['num'] : 0;
                     if ($key == 47 && $dkey == 20200122) {
                         $num = $num + 5784;
                     }
                     if ($key == 47 && $dkey == 20200125) {
                         $num = $num + 289;
                     }
                     $day_user_settlement = [];
                     $day_user_settlement = [
                         'timekey'     => $dkey,
                         'uid'         => $key,
                         'success'     => $success,
                         'unknown'     => isset($value['unknown']) ? $value['unknown'] : 0,
                         'default'     => isset($value['default']) ? $value['default'] : 0,
                         'num'         => $num,
                         'ratio'       => $success / $num * 100,
                         'mobile_num'  => $value['mobile_num'],
                         'business_id' => '6',
                         'create_time' => time(),
                         'update_time' => time(),
                     ];
                     $has = Db::query('SELECT * FROM `yx_statistics_day` WHERE `business_id` = 6 AND `timekey` = ' . $dkey . ' AND `uid` = ' . $key);
                     if ($has) {
                         Db::table('yx_statistics_day')->where('id', $has[0]['id'])->update([
                             'success'     => $success,
                             'unknown'     => isset($value['unknown']) ? $value['unknown'] : 0,
                             'default'     => isset($value['default']) ? $value['default'] : 0,
                             'num'         => $num,
                             'mobile_num'  => $value['mobile_num'],
                             'ratio'       => $success / $num * 100,
                             'update_time' => time(),
                         ]);
                     } else {
                         Db::table('yx_statistics_day')->insert($day_user_settlement);
                     }
                 }
             }
             Db::commit();
         } catch (\Exception $e) {
             Db::rollback();
             exception($e);
         } */

        // print_r($year_businessSettlement);
        // print_r($month_businessSettlement);
        // print_r($day_businessSettlement);
        // die;
        /*         foreach ($day_businessSettlement as $dkey => $d_value) {
    foreach ($d_value as $key => $value) {
    $success = isset($value['success']) ? $value['success'] : 0;
    $num = isset($value['num']) ? $value['num'] : 0;
    $day_user_settlement = [];
    $day_user_settlement = [
    'timekey' => $dkey,
    'uid' => $key,
    'success' => $success,
    'unknown' => isset($value['unknown']) ? $value['unknown'] : 0,
    'default' => isset($value['default']) ? $value['default'] : 0,
    'num' => $num,
    'ratio' => $success / $num,
    'business_id' => '6',
    ];
    $all_day_businessSettlement[] = $day_user_settlement;
    }
    } */
    }

    public function isTrueSettlemen()
    {
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        $code_task_log = Db::query("SELECT * FROM yx_user_send_code_task_log WHERE `uid` = 47 AND create_time >= 1580486400 AND create_time < 1582992000");
        $all_num       = 0;
        foreach ($code_task_log as $key => $value) {
            $send_length = mb_strlen($value['task_content'], 'utf8');
            $num         = 1;
            if ($send_length > 70) {
                $num = ceil($send_length / 67);
            }
            $all_num += $num;
        }
        echo $all_num;
    }

    public function MultimediaSettlement()
    {
        ini_set('memory_limit', '1024M'); // 临时设置最大内存占用为10G
        try {
            while (true) {
                $day_businessSettlement   = [];
                $day_users                = [];
                // $start_time               = strtotime('-10 days');
                // print_r($start_time);die;
                $start_time = (int) strtotime(date('2020-06-01'));
                // $end_time = $start_time + 86400;
                // echo $end_time;die;
                while (true) {
                    $end_time = $start_time + 86400;
                    if ($end_time > time()) {
                        break;
                    }
                    $day_businessSettlement   = [];
                    $day_users                = [];
                    $code_task_log = [];
                    $code_task_log            = Db::query("SELECT * FROM yx_user_multimedia_message_log WHERE `create_time` < " . $end_time . " AND `create_time` >= " . $start_time);
                    foreach ($code_task_log as $key => $value) {
                        $send_length = mb_strlen($value['task_content'], 'utf8');
                        $num         = 1;
                        if (empty($value['status_message']) && empty($value['real_message'])) {
                            $task = Db::query("SELECT id FROM yx_user_multimedia_message WHERE `task_no` = '" . $value['task_no'] . "' LIMIT 1 ");
                            if (empty($task)) {
                                continue;
                            }
                            if ($value['create_time'] + 259200 < time()) {
                                $value['status_message'] = 'DELIVRD';
                            }
                            /*  $receipt = Db::query("SELECT `status_message` FROM yx_sfl_send_multimediatask_receipt WHERE `task_id` = '" . $task[0]['id'] . "' AND `mobile` = '" . $value['mobile'] . "' LIMIT 1 ");
                            if (empty($receipt)) {
                                
                            } else {
                                $value['status_message'] = $receipt[0]['status_message'];
                            } */
                        }
                        $num         = 1;

                        $day   = date('Ymd', $value['create_time']);
                        if (!array_key_exists($day, $day_users)) {
                            $day_users[$day] = [];
                        }
                        if (in_array($value['uid'], $day_users[$day])) {
                            $day_businessSettlement[$day][$value['uid']]['num'] += $num;
                            $day_businessSettlement[$day][$value['uid']]['mobile_num'] += 1;
                            if ($value['status_message'] == 'DELIVRD') {
                                if (isset($day_businessSettlement[$day][$value['uid']]['success'])) {
                                    $day_businessSettlement[$day][$value['uid']]['success'] += $num;
                                } else {
                                    $day_businessSettlement[$day][$value['uid']]['success'] = $num;
                                }
                            } elseif (empty($value['status_message'])) {
                                if (isset($day_businessSettlement[$day][$value['uid']]['unknown'])) {
                                    $day_businessSettlement[$day][$value['uid']]['unknown'] += $num;
                                } else {
                                    $day_businessSettlement[$day][$value['uid']]['unknown'] = $num;
                                }
                            } else {
                                if (isset($day_businessSettlement[$day][$value['uid']]['default'])) {
                                    $day_businessSettlement[$day][$value['uid']]['default'] += $num;
                                } else {
                                    $day_businessSettlement[$day][$value['uid']]['default'] = $num;
                                }
                                // $day_businessSettlement[$day][$value['uid']]['default'] = $num;
                            }
                        } else {
                            $day_users[$day][]                                         = $value['uid'];
                            $day_businessSettlement[$day][$value['uid']]['num']        = $num;
                            $day_businessSettlement[$day][$value['uid']]['mobile_num'] = 1;
                            if ($value['status_message'] == 'DELIVRD') {
                                $day_businessSettlement[$day][$value['uid']]['success'] = $num;
                            } elseif ($value['status_message'] == '') {
                                $day_businessSettlement[$day][$value['uid']]['unknown'] = $num;
                            } else {
                                $value[$day][$value['uid']]['default'] = $num;
                            }
                        }
                    }
                    Db::startTrans();
                    try {
                        foreach ($day_businessSettlement as $dkey => $d_value) {
                            foreach ($d_value as $key => $value) {
                                $success             = isset($value['success']) ? $value['success'] : 0;
                                $num                 = isset($value['num']) ? $value['num'] : 0;
                                $day_user_settlement = [];
                                $day_user_settlement = [
                                    'timekey'     => $dkey,
                                    'uid'         => $key,
                                    'success'     => $success,
                                    'unknown'     => isset($value['unknown']) ? $value['unknown'] : 0,
                                    'default'     => isset($value['default']) ? $value['default'] : 0,
                                    'num'         => $num,
                                    'ratio'       => $success / $num * 100,
                                    'mobile_num'  => $value['mobile_num'],
                                    'business_id' => '8',
                                    'create_time' => time(),
                                    'update_time' => time(),
                                ];
                                $has = Db::query('SELECT * FROM `yx_statistics_day` WHERE `business_id` = 8 AND `timekey` = ' . $dkey . ' AND `uid` = ' . $key);
                                if ($has) {
                                    Db::table('yx_statistics_day')->where('id', $has[0]['id'])->update([
                                        'success'     => $success,
                                        'unknown'     => isset($value['unknown']) ? $value['unknown'] : 0,
                                        'default'     => isset($value['default']) ? $value['default'] : 0,
                                        'num'         => $num,
                                        'mobile_num'  => $value['mobile_num'],
                                        'ratio'       => $success / $num * 100,
                                        'update_time' => time(),
                                    ]);
                                } else {
                                    Db::table('yx_statistics_day')->insert($day_user_settlement);
                                }
                            }
                        }
                        Db::commit();
                    } catch (\Exception $e) {
                        Db::rollback();
                        exception($e);
                    }
                    $start_time = $end_time;
                }

                sleep(900);
            }
        } catch (\Exception $th) {
            exception($th);
        }

        die;
        $year_businessSettlement  = [];
        $month_businessSettlement = [];
        $day_businessSettlement   = [];
        $year_users               = [];
        $month_users              = [];
        $day_users                = [];
        $start_time               = strtotime("2020-04-01");
        // $start_time               = strtotime(date('Y-m', time()));
        $end_time               = strtotime("2020-05-01");
        $end_time               = time();


        $task_log                 = Db::query("SELECT * FROM yx_user_multimedia_message_log WHERE `create_time` <= '" . $end_time . "' AND `create_time` >= " . $start_time);
        // print_r(count($task_log));
        // die;
        try {
            foreach ($task_log as $key => $value) {
                // print_r($value);
                // die;
                if (empty($value['status_message']) && empty($value['real_message']) && $value['create_time'] + 259200 < time()) {
                    $value['status_message'] = 'DELIVRD';
                } else {
                    $value['status_message'] = $value['status_message'];
                }

                $send_length = mb_strlen($value['task_content'], 'utf8');
                $num         = 1;
                if ($send_length > 70) {
                    $num = ceil($send_length / 67);
                }
                $year  = date('Y', $value['create_time']);
                $month = date('Ym', $value['create_time']);
                $day   = date('Ymd', $value['create_time']);
                if (!array_key_exists($year, $year_users)) {
                    $year_users[$year] = [];
                }
                if (!array_key_exists($month, $month_users)) {
                    $month_users[$month] = [];
                }
                if (!array_key_exists($day, $day_users)) {
                    $day_users[$day] = [];
                }
                //年
                if (in_array($value['uid'], $year_users[$year])) {
                    $year_businessSettlement[$year][$value['uid']]['num'] += $num;
                    $year_businessSettlement[$year][$value['uid']]['mobile_num'] += 1;
                    if ($value['status_message'] == 'DELIVRD') {
                        if (isset($year_businessSettlement[$year][$value['uid']]['success'])) {
                            $year_businessSettlement[$year][$value['uid']]['success'] += $num;
                        } else {
                            $year_businessSettlement[$year][$value['uid']]['success'] = $num;
                        }
                    } elseif (empty($value['status_message'])) {
                        if (isset($year_businessSettlement[$year][$value['uid']]['unknown'])) {
                            $year_businessSettlement[$year][$value['uid']]['unknown'] += $num;
                        } else {
                            $year_businessSettlement[$year][$value['uid']]['unknown'] = $num;
                        }
                    } else {
                        if (isset($year_businessSettlement[$year][$value['uid']]['default'])) {
                            $year_businessSettlement[$year][$value['uid']]['default'] += $num;
                        } else {
                            $year_businessSettlement[$year][$value['uid']]['default'] = $num;
                        }
                        // $year_businessSettlement[$year][$value['uid']]['default'] = $num;
                    }
                } else {
                    $year_users[$year][]                                         = $value['uid'];
                    $year_businessSettlement[$year][$value['uid']]['num']        = $num;
                    $year_businessSettlement[$year][$value['uid']]['mobile_num'] = 1;
                    if ($value['status_message'] == 'DELIVRD') {
                        $year_businessSettlement[$year][$value['uid']]['success'] = $num;
                    } elseif ($value['status_message'] == '') {
                        $year_businessSettlement[$year][$value['uid']]['unknown'] = $num;
                    } else {
                        $year_businessSettlement[$year][$value['uid']]['default'] = $num;
                    }
                }
                //月
                if (in_array($value['uid'], $month_users[$month])) {
                    $month_businessSettlement[$month][$value['uid']]['num'] += $num;
                    $month_businessSettlement[$month][$value['uid']]['mobile_num'] += 1;
                    if ($value['status_message'] == 'DELIVRD') {
                        if (isset($month_businessSettlement[$month][$value['uid']]['success'])) {
                            $month_businessSettlement[$month][$value['uid']]['success'] += $num;
                        } else {
                            $month_businessSettlement[$month][$value['uid']]['success'] = $num;
                        }
                    } elseif (empty($value['status_message'])) {
                        if (isset($month_businessSettlement[$month][$value['uid']]['unknown'])) {
                            $month_businessSettlement[$month][$value['uid']]['unknown'] += $num;
                        } else {
                            $month_businessSettlement[$month][$value['uid']]['unknown'] = $num;
                        }
                    } else {
                        if (isset($month_businessSettlement[$month][$value['uid']]['default'])) {
                            $month_businessSettlement[$month][$value['uid']]['default'] += $num;
                        } else {
                            $month_businessSettlement[$month][$value['uid']]['default'] = $num;
                        }
                        // $month_businessSettlement[$month][$value['uid']]['default'] = $num;
                    }
                } else {
                    $month_users[$month][]                                         = $value['uid'];
                    $month_businessSettlement[$month][$value['uid']]['num']        = $num;
                    $month_businessSettlement[$month][$value['uid']]['mobile_num'] = 1;
                    if ($value['status_message'] == 'DELIVRD') {
                        $month_businessSettlement[$month][$value['uid']]['success'] = $num;
                    } elseif ($value['status_message'] == '') {
                        $month_businessSettlement[$month][$value['uid']]['unknown'] = $num;
                    } else {
                        $month_businessSettlement[$month][$value['uid']]['default'] = $num;
                    }
                }
                //日
                if (in_array($value['uid'], $day_users[$day])) {
                    $day_businessSettlement[$day][$value['uid']]['num'] += $num;
                    $day_businessSettlement[$day][$value['uid']]['mobile_num'] += 1;
                    if ($value['status_message'] == 'DELIVRD') {
                        if (isset($day_businessSettlement[$day][$value['uid']]['success'])) {
                            $day_businessSettlement[$day][$value['uid']]['success'] += $num;
                        } else {
                            $day_businessSettlement[$day][$value['uid']]['success'] = $num;
                        }
                    } elseif (empty($value['status_message'])) {
                        if (isset($day_businessSettlement[$day][$value['uid']]['unknown'])) {
                            $day_businessSettlement[$day][$value['uid']]['unknown'] += $num;
                        } else {
                            $day_businessSettlement[$day][$value['uid']]['unknown'] = $num;
                        }
                    } else {
                        if (isset($day_businessSettlement[$day][$value['uid']]['default'])) {
                            $day_businessSettlement[$day][$value['uid']]['default'] += $num;
                        } else {
                            $day_businessSettlement[$day][$value['uid']]['default'] = $num;
                        }
                        // $day_businessSettlement[$day][$value['uid']]['default'] = $num;
                    }
                } else {
                    $day_users[$day][]                                         = $value['uid'];
                    $day_businessSettlement[$day][$value['uid']]['num']        = $num;
                    $day_businessSettlement[$day][$value['uid']]['mobile_num'] = 1;
                    if ($value['status_message'] == 'DELIVRD') {
                        $day_businessSettlement[$day][$value['uid']]['success'] = $num;
                    } elseif ($value['status_message'] == '') {
                        $day_businessSettlement[$day][$value['uid']]['unknown'] = $num;
                    } else {
                        $value[$day][$value['uid']]['default'] = $num;
                    }
                }
            }
        } catch (\Exception $e) {
            exception($e);
        }

        Db::startTrans();
        try {
            //年度计费
            // foreach ($all_year_businessSettlement as $key => $value) {
            //     $has = Db::query('SELECT * FROM `yx_statistics_year` WHERE `` ');
            //     if ($has) {}else{

            //     }
            // }
            /* foreach ($year_businessSettlement as $ykey => $y_value) {
                foreach ($y_value as $key => $value) {
                    $success = isset($value['success']) ? $value['success'] : 0;
                    $num     = isset($value['num']) ? $value['num'] : 0;

                    $year_user_settlement = [];
                    $year_user_settlement = [
                        'timekey'     => $ykey,
                        'uid'         => $key,
                        'success'     => $success,
                        'unknown'     => isset($value['unknown']) ? $value['unknown'] : 0,
                        'default'     => isset($value['default']) ? $value['default'] : 0,
                        'num'         => $num,
                        'mobile_num'  => $value['mobile_num'],
                        'ratio'       => $success / $num * 100,
                        'business_id' => '8',
                        'create_time' => time(),
                        'update_time' => time(),
                    ];
                    $has = Db::query('SELECT * FROM `yx_statistics_year` WHERE `business_id` = 8 AND `timekey` = ' . $ykey . ' AND `uid` = ' . $key);
                    if ($has) {
                        Db::table('yx_statistics_year')->where('id', $has[0]['id'])->update([
                            'success'     => $success,
                            'unknown'     => isset($value['unknown']) ? $value['unknown'] : 0,
                            'default'     => isset($value['default']) ? $value['default'] : 0,
                            'num'         => $num,
                            'mobile_num'  => $value['mobile_num'],
                            'ratio'       => $success / $num * 100,
                            'update_time' => time(),
                        ]);
                    } else {
                        Db::table('yx_statistics_year')->insert($year_user_settlement);
                    }
                }
            } */
            foreach ($month_businessSettlement as $mkey => $m_value) {
                foreach ($m_value as $key => $value) {
                    $success               = isset($value['success']) ? $value['success'] : 0;
                    $num                   = isset($value['num']) ? $value['num'] : 0;
                    $month_user_settlement = [];

                    $month_user_settlement = [
                        'timekey'     => $mkey,
                        'uid'         => $key,
                        'success'     => $success,
                        'unknown'     => isset($value['unknown']) ? $value['unknown'] : 0,
                        'default'     => isset($value['default']) ? $value['default'] : 0,
                        'num'         => $num,
                        'ratio'       => $success / $num * 100,
                        'mobile_num'  => $value['mobile_num'],
                        'business_id' => '8',
                        'create_time' => time(),
                        'update_time' => time(),
                    ];
                    $has = Db::query('SELECT * FROM `yx_statistics_month` WHERE `business_id` = 8 AND `timekey` = ' . $mkey . ' AND `uid` = ' . $key);
                    if ($has) {
                        Db::table('yx_statistics_month')->where('id', $has[0]['id'])->update([
                            'success'     => $success,
                            'unknown'     => isset($value['unknown']) ? $value['unknown'] : 0,
                            'default'     => isset($value['default']) ? $value['default'] : 0,
                            'num'         => $num,
                            'mobile_num'  => $value['mobile_num'],
                            'ratio'       => $success / $num * 100,
                            'update_time' => time(),
                        ]);
                    } else {
                        Db::table('yx_statistics_month')->insert($month_user_settlement);
                    }
                }
            }
            foreach ($day_businessSettlement as $dkey => $d_value) {
                foreach ($d_value as $key => $value) {
                    $success             = isset($value['success']) ? $value['success'] : 0;
                    $num                 = isset($value['num']) ? $value['num'] : 0;
                    $day_user_settlement = [];
                    $day_user_settlement = [
                        'timekey'     => $dkey,
                        'uid'         => $key,
                        'success'     => $success,
                        'unknown'     => isset($value['unknown']) ? $value['unknown'] : 0,
                        'default'     => isset($value['default']) ? $value['default'] : 0,
                        'num'         => $num,
                        'ratio'       => $success / $num * 100,
                        'mobile_num'  => $value['mobile_num'],
                        'business_id' => '8',
                        'create_time' => time(),
                        'update_time' => time(),
                    ];
                    $has = Db::query('SELECT * FROM `yx_statistics_day` WHERE `business_id` = 8 AND `timekey` = ' . $dkey . ' AND `uid` = ' . $key);
                    if ($has) {
                        Db::table('yx_statistics_day')->where('id', $has[0]['id'])->update([
                            'success'     => $success,
                            'unknown'     => isset($value['unknown']) ? $value['unknown'] : 0,
                            'default'     => isset($value['default']) ? $value['default'] : 0,
                            'num'         => $num,
                            'mobile_num'  => $value['mobile_num'],
                            'ratio'       => $success / $num * 100,
                            'update_time' => time(),
                        ]);
                    } else {
                        Db::table('yx_statistics_day')->insert($day_user_settlement);
                    }
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            exception($e);
        }
    }

    public function marketingSettlement()
    {
        ini_set('memory_limit', '10240M'); // 临时设置最大内存占用为10G

        while (true) {
            $day_businessSettlement   = [];
            $day_users                = [];
            // $start_time               = strtotime('-10 days');
            // print_r($start_time);die;
            $start_time = (int) strtotime(date('2020-07-01'));
            $Received = updateReceivedForMessage();
            array_push($Received, 'DELIVRD');
            // $end_time = $start_time + 86400;
            // echo $end_time;die;
            while (true) {
                $end_time = $start_time + 86400;
                if ($end_time > time()) {
                    break;
                }
                $day_businessSettlement   = [];
                $day_users                = [];
                $code_task_log = [];
                $code_task_log            = Db::query("SELECT `id` FROM yx_user_send_task_log WHERE `create_time` < " . $end_time . " AND `create_time` >= " . $start_time);
                foreach ($code_task_log as $key => $value) {
                    $task_log = Db::query("SELECT `*` FROM yx_user_send_task_log WHERE `id` = " . $value['id']);
                    $send_length = mb_strlen($task_log[0]['task_content'], 'utf8');
                    $num         = 1;
                    if (empty($task_log[0]['status_message']) && empty($task_log[0]['real_message'])) {
                        $task = Db::query("SELECT id FROM yx_user_send_task WHERE `task_no` = '" . $task_log[0]['task_no'] . "' LIMIT 1 ");
                        if (empty($task)) {
                            continue;
                        }
                        $receipt = Db::query("SELECT `status_message` FROM yx_send_task_receipt WHERE `task_id` = '" . $task[0]['id'] . "' AND `mobile` = '" . $task_log[0]['mobile'] . "' LIMIT 1 ");
                        if (empty($receipt)) {
                            if ($task_log[0]['create_time'] + 259200 < time() && $task_log[0]['uid'] == 51) {
                                $task_log[0]['status_message'] = 'DELIVRD';
                            } elseif ($task_log[0]['create_time'] + 259200 < time() && $task_log[0]['create_time'] > 1595865600) {
                                $task_log[0]['status_message'] = 'DELIVRD';
                            }
                        } else {
                            $task_log[0]['status_message'] = $receipt[0]['status_message'];
                        }
                    }
                    // if () {}
                    if (in_array(trim($task_log[0]['status_message']), $Received)) {
                        $task_log[0]['status_message'] = 'DELIVRD';
                    }
                    $num         = 1;
                    if ($send_length > 70) {
                        $num = ceil($send_length / 67);
                    }
                    $day   = date('Ymd', $task_log[0]['create_time']);
                    if (!array_key_exists($day, $day_users)) {
                        $day_users[$day] = [];
                    }
                    if (in_array($task_log[0]['uid'], $day_users[$day])) {
                        $day_businessSettlement[$day][$task_log[0]['uid']]['num'] += $num;
                        $day_businessSettlement[$day][$task_log[0]['uid']]['mobile_num'] += 1;
                        if ($task_log[0]['status_message'] == 'DELIVRD') {
                            if (isset($day_businessSettlement[$day][$task_log[0]['uid']]['success'])) {
                                $day_businessSettlement[$day][$task_log[0]['uid']]['success'] += $num;
                            } else {
                                $day_businessSettlement[$day][$task_log[0]['uid']]['success'] = $num;
                            }
                        } elseif (empty($task_log[0]['status_message'])) {
                            if (isset($day_businessSettlement[$day][$task_log[0]['uid']]['unknown'])) {
                                $day_businessSettlement[$day][$task_log[0]['uid']]['unknown'] += $num;
                            } else {
                                $day_businessSettlement[$day][$task_log[0]['uid']]['unknown'] = $num;
                            }
                        } else {
                            if (isset($day_businessSettlement[$day][$task_log[0]['uid']]['default'])) {
                                $day_businessSettlement[$day][$task_log[0]['uid']]['default'] += $num;
                            } else {
                                $day_businessSettlement[$day][$task_log[0]['uid']]['default'] = $num;
                            }
                            // $day_businessSettlement[$day][$value['uid']]['default'] = $num;
                        }
                    } else {
                        $day_users[$day][]                                         = $task_log[0]['uid'];
                        $day_businessSettlement[$day][$task_log[0]['uid']]['num']        = $num;
                        $day_businessSettlement[$day][$task_log[0]['uid']]['mobile_num'] = 1;
                        if ($task_log[0]['status_message'] == 'DELIVRD') {
                            $day_businessSettlement[$day][$task_log[0]['uid']]['success'] = $num;
                        } elseif ($task_log[0]['status_message'] == '') {
                            $day_businessSettlement[$day][$task_log[0]['uid']]['unknown'] = $num;
                        } else {
                            $day_businessSettlement[$day][$task_log[0]['uid']]['default'] = $num;
                        }
                    }
                }
                Db::startTrans();
                try {
                    foreach ($day_businessSettlement as $dkey => $d_value) {
                        foreach ($d_value as $key => $value) {
                            $success             = isset($value['success']) ? $value['success'] : 0;
                            $num                 = isset($value['num']) ? $value['num'] : 0;
                            $day_user_settlement = [];
                            $day_user_settlement = [
                                'timekey'     => $dkey,
                                'uid'         => $key,
                                'success'     => $success,
                                'unknown'     => isset($value['unknown']) ? $value['unknown'] : 0,
                                'default'     => isset($value['default']) ? $value['default'] : 0,
                                'num'         => $num,
                                'ratio'       => $success / $num * 100,
                                'mobile_num'  => $value['mobile_num'],
                                'business_id' => '5',
                                'create_time' => time(),
                                'update_time' => time(),
                            ];
                            $has = Db::query('SELECT * FROM `yx_statistics_day` WHERE `business_id` = 5 AND `timekey` = ' . $dkey . ' AND `uid` = ' . $key);
                            if ($has) {
                                Db::table('yx_statistics_day')->where('id', $has[0]['id'])->update([
                                    'success'     => $success,
                                    'unknown'     => isset($value['unknown']) ? $value['unknown'] : 0,
                                    'default'     => isset($value['default']) ? $value['default'] : 0,
                                    'num'         => $num,
                                    'mobile_num'  => $value['mobile_num'],
                                    'ratio'       => $success / $num * 100,
                                    'update_time' => time(),
                                ]);
                            } else {
                                Db::table('yx_statistics_day')->insert($day_user_settlement);
                            }
                        }
                    }
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    exception($e);
                }
                $start_time = $end_time;
            }

            sleep(900);
        }
        die;
        $year_businessSettlement  = [];
        $month_businessSettlement = [];
        $day_businessSettlement   = [];
        $year_users               = [];
        $month_users              = [];
        $day_users                = [];
        $start_time               = strtotime(date('Y-m', time()));
        $task_log                 = Db::query("SELECT * FROM yx_user_send_task_log WHERE `create_time` < " . time() . " AND `create_time` >= " . $start_time);
        try {
            foreach ($task_log as $key => $value) {
                // print_r($value);
                // die;
                if (empty($value['status_message']) && empty($value['real_message'])) {
                    $task    = Db::query("SELECT id FROM yx_user_send_task WHERE `task_no` = '" . $value['task_no'] . "' LIMIT 1 ");
                    $receipt = Db::query("SELECT `status_message` FROM yx_send_task_receipt WHERE `task_id` = '" . $task[0]['id'] . "' AND `mobile` = '" . $value['mobile'] . "' LIMIT 1 ");
                    if (empty($receipt)) {
                        if ($value['create_time'] + 259200 < time()) {
                            $value['status_message'] = 'DELIVRD';
                        }
                    } else {
                        $value['status_message'] = $receipt[0]['status_message'];
                    }
                }
                $send_length = mb_strlen($value['task_content'], 'utf8');
                $num         = 1;
                if ($send_length > 70) {
                    $num = ceil($send_length / 67);
                }
                $year  = date('Y', $value['create_time']);
                $month = date('Ym', $value['create_time']);
                $day   = date('Ymd', $value['create_time']);
                if (!array_key_exists($year, $year_users)) {
                    $year_users[$year] = [];
                }
                if (!array_key_exists($month, $month_users)) {
                    $month_users[$month] = [];
                }
                if (!array_key_exists($day, $day_users)) {
                    $day_users[$day] = [];
                }
                //年
                if (in_array($value['uid'], $year_users[$year])) {
                    $year_businessSettlement[$year][$value['uid']]['num'] += $num;
                    $year_businessSettlement[$year][$value['uid']]['mobile_num'] += 1;
                    if ($value['status_message'] == 'DELIVRD') {
                        if (isset($year_businessSettlement[$year][$value['uid']]['success'])) {
                            $year_businessSettlement[$year][$value['uid']]['success'] += $num;
                        } else {
                            $year_businessSettlement[$year][$value['uid']]['success'] = $num;
                        }
                    } elseif (empty($value['status_message'])) {
                        if (isset($year_businessSettlement[$year][$value['uid']]['unknown'])) {
                            $year_businessSettlement[$year][$value['uid']]['unknown'] += $num;
                        } else {
                            $year_businessSettlement[$year][$value['uid']]['unknown'] = $num;
                        }
                    } else {
                        if (isset($year_businessSettlement[$year][$value['uid']]['default'])) {
                            $year_businessSettlement[$year][$value['uid']]['default'] += $num;
                        } else {
                            $year_businessSettlement[$year][$value['uid']]['default'] = $num;
                        }
                        // $year_businessSettlement[$year][$value['uid']]['default'] = $num;
                    }
                } else {
                    $year_users[$year][]                                         = $value['uid'];
                    $year_businessSettlement[$year][$value['uid']]['num']        = $num;
                    $year_businessSettlement[$year][$value['uid']]['mobile_num'] = 1;
                    if ($value['status_message'] == 'DELIVRD') {
                        $year_businessSettlement[$year][$value['uid']]['success'] = $num;
                    } elseif ($value['status_message'] == '') {
                        $year_businessSettlement[$year][$value['uid']]['unknown'] = $num;
                    } else {
                        $year_businessSettlement[$year][$value['uid']]['default'] = $num;
                    }
                }
                //月
                if (in_array($value['uid'], $month_users[$month])) {
                    $month_businessSettlement[$month][$value['uid']]['num'] += $num;
                    $month_businessSettlement[$month][$value['uid']]['mobile_num'] += 1;
                    if ($value['status_message'] == 'DELIVRD') {
                        if (isset($month_businessSettlement[$month][$value['uid']]['success'])) {
                            $month_businessSettlement[$month][$value['uid']]['success'] += $num;
                        } else {
                            $month_businessSettlement[$month][$value['uid']]['success'] = $num;
                        }
                    } elseif (empty($value['status_message'])) {
                        if (isset($month_businessSettlement[$month][$value['uid']]['unknown'])) {
                            $month_businessSettlement[$month][$value['uid']]['unknown'] += $num;
                        } else {
                            $month_businessSettlement[$month][$value['uid']]['unknown'] = $num;
                        }
                    } else {
                        if (isset($month_businessSettlement[$month][$value['uid']]['default'])) {
                            $month_businessSettlement[$month][$value['uid']]['default'] += $num;
                        } else {
                            $month_businessSettlement[$month][$value['uid']]['default'] = $num;
                        }
                        // $month_businessSettlement[$month][$value['uid']]['default'] = $num;
                    }
                } else {
                    $month_users[$month][]                                         = $value['uid'];
                    $month_businessSettlement[$month][$value['uid']]['num']        = $num;
                    $month_businessSettlement[$month][$value['uid']]['mobile_num'] = 1;
                    if ($value['status_message'] == 'DELIVRD') {
                        $month_businessSettlement[$month][$value['uid']]['success'] = $num;
                    } elseif ($value['status_message'] == '') {
                        $month_businessSettlement[$month][$value['uid']]['unknown'] = $num;
                    } else {
                        $month_businessSettlement[$month][$value['uid']]['default'] = $num;
                    }
                }
                //日
                if (in_array($value['uid'], $day_users[$day])) {
                    $day_businessSettlement[$day][$value['uid']]['num'] += $num;
                    $day_businessSettlement[$day][$value['uid']]['mobile_num'] += 1;
                    if ($value['status_message'] == 'DELIVRD') {
                        if (isset($day_businessSettlement[$day][$value['uid']]['success'])) {
                            $day_businessSettlement[$day][$value['uid']]['success'] += $num;
                        } else {
                            $day_businessSettlement[$day][$value['uid']]['success'] = $num;
                        }
                    } elseif (empty($value['status_message'])) {
                        if (isset($day_businessSettlement[$day][$value['uid']]['unknown'])) {
                            $day_businessSettlement[$day][$value['uid']]['unknown'] += $num;
                        } else {
                            $day_businessSettlement[$day][$value['uid']]['unknown'] = $num;
                        }
                    } else {
                        if (isset($day_businessSettlement[$day][$value['uid']]['default'])) {
                            $day_businessSettlement[$day][$value['uid']]['default'] += $num;
                        } else {
                            $day_businessSettlement[$day][$value['uid']]['default'] = $num;
                        }
                        // $day_businessSettlement[$day][$value['uid']]['default'] = $num;
                    }
                } else {
                    $day_users[$day][]                                         = $value['uid'];
                    $day_businessSettlement[$day][$value['uid']]['num']        = $num;
                    $day_businessSettlement[$day][$value['uid']]['mobile_num'] = 1;
                    if ($value['status_message'] == 'DELIVRD') {
                        $day_businessSettlement[$day][$value['uid']]['success'] = $num;
                    } elseif ($value['status_message'] == '') {
                        $day_businessSettlement[$day][$value['uid']]['unknown'] = $num;
                    } else {
                        $value[$day][$value['uid']]['default'] = $num;
                    }
                }
            }
        } catch (\Exception $e) {
            exception($e);
        }

        Db::startTrans();
        try {
            //年度计费
            // foreach ($all_year_businessSettlement as $key => $value) {
            //     $has = Db::query('SELECT * FROM `yx_statistics_year` WHERE `` ');
            //     if ($has) {}else{

            //     }
            // }
            /*          foreach ($year_businessSettlement as $ykey => $y_value) {
            foreach ($y_value as $key => $value) {
            $success = isset($value['success']) ? $value['success'] : 0;
            $num = isset($value['num']) ? $value['num'] : 0;

            $year_user_settlement = [];
            $year_user_settlement = [
            'timekey' => $ykey,
            'uid' => $key,
            'success' => $success,
            'unknown' => isset($value['unknown']) ? $value['unknown'] : 0,
            'default' => isset($value['default']) ? $value['default'] : 0,
            'num' => $num,
            'mobile_num' => $value['mobile_num'],
            'ratio' => $success / $num * 100,
            'business_id' => '5',
            'create_time' => time(),
            'update_time' => time(),
            ];
            $has = Db::query('SELECT * FROM `yx_statistics_year` WHERE `business_id` = 5 AND `timekey` = ' . $ykey . ' AND `uid` = ' . $key);
            if ($has) {
            Db::table('yx_statistics_year')->where('id', $has[0]['id'])->update([
            'success' => $success,
            'unknown' => isset($value['unknown']) ? $value['unknown'] : 0,
            'default' => isset($value['default']) ? $value['default'] : 0,
            'num' => $num,
            'mobile_num' => $value['mobile_num'],
            'ratio' => $success / $num * 100,
            'update_time' => time(),
            ]);
            } else {
            Db::table('yx_statistics_year')->insert($year_user_settlement);
            }
            }
            } */
            foreach ($month_businessSettlement as $mkey => $m_value) {
                foreach ($m_value as $key => $value) {
                    $success               = isset($value['success']) ? $value['success'] : 0;
                    $num                   = isset($value['num']) ? $value['num'] : 0;
                    $month_user_settlement = [];

                    $month_user_settlement = [
                        'timekey'     => $mkey,
                        'uid'         => $key,
                        'success'     => $success,
                        'unknown'     => isset($value['unknown']) ? $value['unknown'] : 0,
                        'default'     => isset($value['default']) ? $value['default'] : 0,
                        'num'         => $num,
                        'ratio'       => $success / $num * 100,
                        'mobile_num'  => $value['mobile_num'],
                        'business_id' => '5',
                        'create_time' => time(),
                        'update_time' => time(),
                    ];
                    $has = Db::query('SELECT * FROM `yx_statistics_month` WHERE `business_id` = 5 AND `timekey` = ' . $mkey . ' AND `uid` = ' . $key);
                    if ($has) {
                        Db::table('yx_statistics_month')->where('id', $has[0]['id'])->update([
                            'success'     => $success,
                            'unknown'     => isset($value['unknown']) ? $value['unknown'] : 0,
                            'default'     => isset($value['default']) ? $value['default'] : 0,
                            'num'         => $num,
                            'mobile_num'  => $value['mobile_num'],
                            'ratio'       => $success / $num * 100,
                            'update_time' => time(),
                        ]);
                    } else {
                        Db::table('yx_statistics_month')->insert($month_user_settlement);
                    }
                }
            }
            foreach ($day_businessSettlement as $dkey => $d_value) {
                foreach ($d_value as $key => $value) {
                    $success             = isset($value['success']) ? $value['success'] : 0;
                    $num                 = isset($value['num']) ? $value['num'] : 0;
                    $day_user_settlement = [];
                    $day_user_settlement = [
                        'timekey'     => $dkey,
                        'uid'         => $key,
                        'success'     => $success,
                        'unknown'     => isset($value['unknown']) ? $value['unknown'] : 0,
                        'default'     => isset($value['default']) ? $value['default'] : 0,
                        'num'         => $num,
                        'ratio'       => $success / $num * 100,
                        'mobile_num'  => $value['mobile_num'],
                        'business_id' => '5',
                        'create_time' => time(),
                        'update_time' => time(),
                    ];
                    $has = Db::query('SELECT * FROM `yx_statistics_day` WHERE `business_id` = 5 AND `timekey` = ' . $dkey . ' AND `uid` = ' . $key);
                    if ($has) {
                        Db::table('yx_statistics_day')->where('id', $has[0]['id'])->update([
                            'success'     => $success,
                            'unknown'     => isset($value['unknown']) ? $value['unknown'] : 0,
                            'default'     => isset($value['default']) ? $value['default'] : 0,
                            'num'         => $num,
                            'mobile_num'  => $value['mobile_num'],
                            'ratio'       => $success / $num * 100,
                            'update_time' => time(),
                        ]);
                    } else {
                        Db::table('yx_statistics_day')->insert($day_user_settlement);
                    }
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            exception($e);
        }
    }

    public function mulTaksLogUpdate()
    {
        $task = Db::query("SELECT * FROM yx_user_multimedia_message WHERE free_trial = 2 ");
        foreach ($task as $key => $value) {
            $mobile = explode(',', $value['mobile_content']);
            $mobile = array_filter($mobile);
            if ($mobile) {
                foreach ($mobile as $mkey => $ml) {
                    if (!Db::query("SELECT `id` FROM yx_user_multimedia_message_log WHERE `task_no` = '" . $value['task_no'] . "' AND `mobile` = '" . $ml . "' ")) {
                        /* print_r("SELECT `id` FROM yx_user_multimedia_message_log WHERE `task_no` = '" . $value['task_no'] . "' AND `mobile` = '" . $ml . "' ");
                        die; */
                        Db::table('yx_user_multimedia_message_log')->insert([
                            'task_no'        => $value['task_no'],
                            'task_id'        => $value['id'],
                            'uid'            => $value['uid'],
                            'mobile'         => $ml,
                            'channel_id'     => $value['channel_id'],
                            'source'         => $value['source'],
                            'send_status'    => 3,
                            'source_status'  => 3,
                            'status_message' => 'DELIVRD',
                            'create_time'    => $value['update_time'],
                        ]);
                    }
                }
            }
        }
    }

    public function BufaCodetaskLog()
    {
        $redis    = Phpredis::getConn();
        $task_log = Db::query("SELECT * FROM `yx_user_send_code_task` WHERE `id` >= '4010741' AND `uid` = '276' ");
        foreach ($task_log as $key => $value) {
            // # code...
            $sendmessage = [];
            $sendmessage = [
                'mobile'      => $value['mobile_content'],
                'title'       => $value['task_name'],
                'mar_task_id' => $value['id'],
                'content'     => $value['task_content'],
            ];
            if (!empty($value['develop_no'])) {
                $sendmessage['develop_code'] = $value['develop_no'];
            }
            $redis->rpush('index:meassage:code:send:22', json_encode($sendmessage));
        }
    }

    public function calculateGameTaskReceip()
    {
        $redis = Phpredis::getConn();
        /*        while (true) {
        $min = 100 - ceil(4.6 / 5.2 * 100);
        $max = mt_rand($min - 1, $min + 1);
        $num     = mt_rand(0, 100);
        if ($num <= $max) { //扣量
        $channel_calculate =  $redis->get('index:meassage:calculate:14');
        $channel_calculate = json_decode($channel_calculate, true);

        if (isset($channel_calculate['status'])) {
        $a = mt_rand(0, max($channel_calculate['status']));
        asort($channel_calculate['status']);
        foreach ($channel_calculate['status'] as $cal => $late) {
        if ($a <= $late) {
        $send_log['status_message'] = $cal; //推送到虚拟不发送队列
        print_r($send_log);
        break;
        }
        }
        }
        }
        } */

        $redis->set('index:calculate:StartTime', 1587052800);
        // $starttime = 1586880000;
        ini_set('memory_limit', '1024M'); // 临时设置最大内存占用为10G
        while (true) {
            $starttime = $redis->get('index:calculate:StartTime');
            if (time() - $starttime >= 300) {
                $all_task   = [];
                $all_status = [];
                $all_task   = Db::query("SELECT * FROM yx_user_send_game_task WHERE `update_time` >=  '" . $starttime . "' AND `update_time` <= '" . time() . "' AND `create_time` >= " . $starttime);
                // print("SELECT * FROM yx_user_send_game_task WHERE `update_time` >=  '" . $starttime . "' AND `update_time` <= '" . time() . "'");
                // die;
                // $all_num = count($all_task);
                if (!empty($all_task)) {
                    foreach ($all_task as $key => $value) {
                        // print_r($value);
                        if (isset($all_status[$value['channel_id']]['min_time'])) {
                            if ($value['create_time'] && $value['update_time']) {
                                $recive_time = $value['update_time'] - $value['create_time'];
                                if ($all_status[$value['channel_id']]['min_time'] >= $recive_time) {
                                    $all_status[$value['channel_id']]['min_time'] = $recive_time;
                                }
                            }
                        } else {
                            $all_status[$value['channel_id']]['min_time'] = 3;
                        }
                        if (isset($all_status[$value['channel_id']]['max_time'])) {
                            if ($value['create_time'] && $value['update_time']) {
                                $recive_time = $value['update_time'] - $value['create_time'];
                                if ($all_status[$value['channel_id']]['max_time'] <= $recive_time) {
                                    $all_status[$value['channel_id']]['max_time'] = $recive_time;
                                }
                            }
                        } else {
                            $all_status[$value['channel_id']]['max_time'] = 10;
                        }

                        if (isset($all_status[$value['channel_id']]['all_num'])) {
                            $all_status[$value['channel_id']]['all_num'] += 1;
                        } else {
                            $all_status[$value['channel_id']]['all_num'] = 1;
                        }
                        if ($value['real_message'] == '') {
                            // $value['real_message'] = 'UNKNOWN';
                            continue;
                        }
                        if (isset($all_status[$value['channel_id']])) {
                            if (isset($all_status[$value['channel_id']]['status'][$value['real_message']])) {
                                $all_status[$value['channel_id']]['status'][$value['real_message']] += 1;
                            } else {
                                $all_status[$value['channel_id']]['status'][$value['real_message']] = 1;
                            }
                        } else {
                            $all_status[$value['channel_id']]['status'][$value['real_message']] = 1;
                        }
                    }

                    // print_r($all_status);
                    foreach ($all_status as $all => $status) {
                        $redis->set('index:meassage:calculate:' . $all, json_encode($status));
                    }
                }

                // $redis->set('index:calculate:StartTime', time());
            }
            sleep(60);
        }
    }

    public function receiptCallBack()
    {
        $redis = Phpredis::getConn();
        $redis->rpush("index:message:task:callback", json_encode([
            'start_time' => 1586880000,
            'end_time'   => 1586966400,
            'type'       => 'game',
            'way'        => 'cmpp',
            'uid'        => 45,
        ]));
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为10G
        while (true) {
            $receiptmessage = $redis->lpop("index:message:task:callback");
            if (empty($receiptmessage)) {
                sleep('60');
                continue;
            }
            $receiptmessage = json_decode($receiptmessage, true);
            if ($receiptmessage['type'] == 'game') {
                $receipts = Db::query("SELECT mobile_content,send_msg_id,real_message,submit_time,real_message,create_time FROM yx_user_send_game_task WHERE `create_time` >= " . $receiptmessage['start_time'] . " AND `create_time` <= " . $receiptmessage['end_time'] . " AND `uid` = " . $receiptmessage['uid']);
                foreach ($receipts as $key => $value) {
                    if ($receiptmessage['way'] == 'cmpp') {
                        $send_msgid = explode(',', $value['send_msg_id']);
                        foreach ($send_msgid as $key => $svalue) {
                            $redis->rPush('index:meassage:game:cmppdeliver:' . $receiptmessage['uid'], json_encode([
                                'Stat'        => $value['real_message'],
                                'send_msgid'  => [$svalue],
                                'Done_time'   => $value['submit_time'],
                                'Submit_time' => $value['create_time'],
                                'mobile'      => $value['mobile_content'],
                            ]));
                            // if ($value == $send_log['Msg_Id']){

                            // }
                        }
                    }
                }
            }
        }
    }

    public function zlsUpdate()
    {
        $task     = Db::query("SELECT * FROM yx_user_send_code_task WHERE `uid` = 94");
        $j        = 1;
        $all_log  = [];
        $true_log = [];
        try {
            foreach ($task as $key => $value) {
                $send_length = mb_strlen($value['task_content'], 'utf8');
                $mobile      = explode(',', $value['mobile_content']);
                if (count($mobile) > 1) {
                    for ($i = 0; $i < count($mobile); $i++) {
                        // $channel_id    = 0;
                        if (Db::query("SELECT * FROM yx_user_send_code_task_log WHERE `task_no` = '" . $value['task_no'] . "' AND `mobile` = " . $mobile[$i])) {
                            continue;
                        }
                        $channel_id  = $value['channel_id'];
                        $send_log    = [];
                        $sendmessage = [];
                        if (checkMobile(trim($mobile[$i])) == true) {
                            $end_num = substr($mobile[$i], -6);
                            //按无效号码计算
                            if (!in_array($end_num, ['000000', '111111', '222222', '333333', '444444', '555555', '666666', '777777', '888888', '999999'])) {
                                $prefix = '';
                                $prefix = substr(trim($mobile[$i]), 0, 7);
                                $res    = Db::query("SELECT `source`,`province_id`,`province` FROM `yx_number_source` WHERE `mobile` = '" . $prefix . "'");
                                // print_r($res);
                                if ($res) {
                                    $newres = array_shift($res);
                                    if ($newres['source'] == 2 && $channel_id == 24) { //易信联通
                                        $channel_id = 26;
                                    } else if ($newres['source'] == 1 && $channel_id == 24) { //移动易信
                                        $channel_id = 24;
                                    } else if ($newres['source'] == 3 && $channel_id == 24) { //易信电信
                                        $channel_id = 26;
                                    } else if ($channel_id == 22 && $newres['source'] == 2 && $value['uid'] == 91) {
                                        $channel_id = 9; //蓝鲸营销
                                    }
                                }
                                $num = mt_rand(0, 1000);
                                if ($num <= 68) {
                                    if ($num <= 20) {
                                        $status_message = 'MK:1008';
                                    } elseif ($num <= 32 && $num > 20) {
                                        $status_message = '2:12';
                                    } else {
                                        $status_message = 'CE:0211';
                                    }
                                } else {
                                    $status_message = 'DELIVRD';
                                }
                                $send_log = [
                                    'task_no'        => $value['task_no'],
                                    'uid'            => $value['uid'],
                                    'source'         => $value['source'],
                                    'task_content'   => $value['task_content'],
                                    'mobile'         => $mobile[$i],
                                    'send_status'    => 2,
                                    'channel_id'     => $channel_id,
                                    'send_length'    => $send_length,
                                    'status_message' => $status_message,
                                    'create_time'    => $value['create_time'],
                                    'update_time'    => $value['create_time'] + mt_rand(0, 450),
                                ];
                                $true_log[] = $send_log;
                            } else {
                                $send_log = [
                                    'task_no'        => $value['task_no'],
                                    'uid'            => $value['uid'],
                                    // 'title'          => $sendTask['task_name'],
                                    'task_content'   => $value['task_content'],
                                    'source'         => $value['source'],
                                    'mobile'         => $value[$i],
                                    'send_length'    => $send_length,
                                    'send_status'    => 4,
                                    'create_time'    => $value['create_time'],
                                    'update_time'    => $value['create_time'] + mt_rand(0, 20),
                                    'status_message' => 'DB:0101', //无效号码
                                    'real_message'   => 'DB:0101',
                                ];
                                $all_log[] = $send_log;
                            }
                        } else {
                            $send_log = [
                                'task_no'        => $value['task_no'],
                                'uid'            => $value['uid'],
                                // 'title'          => $sendTask['task_name'],
                                'task_content'   => $value['task_content'],
                                'source'         => $value['source'],
                                'mobile'         => $mobile[$i],
                                'send_length'    => $send_length,
                                'send_status'    => 4,
                                'create_time'    => $value['create_time'],
                                'update_time'    => $value['create_time'] + mt_rand(0, 20),
                                'status_message' => 'DB:0101', //无效号码
                                'real_message'   => 'DB:0101',
                            ];

                            $all_log[] = $send_log;
                        }

                        $j++;
                        if ($j > 100) {
                            $j = 1;
                            Db::startTrans();
                            try {
                                Db::table('yx_user_send_code_task_log')->insertAll($true_log);
                                if (!empty($all_log)) {
                                    Db::table('yx_user_send_code_task_log')->insertAll($all_log);
                                }
                                Db::commit();
                            } catch (\Exception $e) {
                                // $this->redis->rPush('index:meassage:business:sendtask', $send);

                                Db::rollback();
                                exception($e);
                            }
                            unset($all_log);
                            unset($true_log);
                            unset($push_messages);
                            // echo time() . "\n";
                            unset($rollback);
                        }
                    }
                } else {
                    if (Db::query("SELECT * FROM yx_user_send_code_task_log WHERE `task_no` = '" . $value['task_no'] . "' AND `mobile` = " . $value['mobile_content'])) {
                        continue;
                    }
                    $channel_id  = $value['channel_id'];
                    $send_log    = [];
                    $sendmessage = [];
                    if (checkMobile(trim($value['mobile_content'])) == true) {
                        $end_num = substr($value['mobile_content'], -6);
                        //按无效号码计算
                        if (!in_array($end_num, ['000000', '111111', '222222', '333333', '444444', '555555', '666666', '777777', '888888', '999999'])) {
                            $prefix = '';
                            $prefix = substr(trim($value['mobile_content']), 0, 7);
                            $res    = Db::query("SELECT `source`,`province_id`,`province` FROM `yx_number_source` WHERE `mobile` = '" . $prefix . "'");
                            // print_r($res);
                            if ($res) {
                                $newres = array_shift($res);
                                if ($newres['source'] == 2 && $channel_id == 24) { //易信联通
                                    $channel_id = 26;
                                } else if ($newres['source'] == 1 && $channel_id == 24) { //移动易信
                                    $channel_id = 24;
                                } else if ($newres['source'] == 3 && $channel_id == 24) { //易信电信
                                    $channel_id = 26;
                                } else if ($channel_id == 22 && $newres['source'] == 2 && $value['uid'] == 91) {
                                    $channel_id = 9; //蓝鲸营销
                                }
                            }
                            $num = mt_rand(0, 1000);
                            if ($num <= 68) {
                                if ($num <= 20) {
                                    $status_message = 'MK:1008';
                                } elseif ($num <= 32 && $num > 20) {
                                    $status_message = '2:12';
                                } else {
                                    $status_message = 'CE:0211';
                                }
                            } else {
                                $status_message = 'DELIVRD';
                            }
                            $send_log = [
                                'task_no'        => $value['task_no'],
                                'uid'            => $value['uid'],
                                'source'         => $value['source'],
                                'task_content'   => $value['task_content'],
                                'mobile'         => $value['mobile_content'],
                                'send_status'    => 2,
                                'status_message' => $status_message,
                                'channel_id'     => $channel_id,
                                'send_length'    => $send_length,
                                'create_time'    => $value['create_time'],
                                'update_time'    => $value['create_time'] + mt_rand(0, 450),
                            ];
                            if (!empty($value['develop_no'])) {
                                $sendmessage['develop_code'] = $value['develop_no'];
                            }
                            // $res = $this->redis->rpush($redisMessageMarketingSend . ":" . $channel_id, json_encode($sendmessage)); //三体营销通道
                            $true_log[] = $send_log;
                        } else {
                            $send_log = [
                                'task_no'        => $value['task_no'],
                                'uid'            => $value['uid'],
                                // 'title'          => $sendTask['task_name'],
                                'task_content'   => $value['task_content'],
                                'source'         => $value['source'],
                                'mobile'         => $value[$i],
                                'send_length'    => $send_length,
                                'send_status'    => 4,
                                'create_time'    => $value['create_time'],
                                'update_time'    => $value['create_time'] + mt_rand(0, 20),
                                'status_message' => 'DB:0101', //无效号码
                                'real_message'   => 'DB:0101',
                            ];
                            $all_log[] = $send_log;
                        }
                    } else {
                        $send_log = [
                            'task_no'        => $value['task_no'],
                            'uid'            => $value['uid'],
                            // 'title'          => $sendTask['task_name'],
                            'task_content'   => $value['task_content'],
                            'source'         => $value['source'],
                            'mobile'         => $value['mobile_content'],
                            'send_length'    => $send_length,
                            'send_status'    => 4,
                            'create_time'    => $value['create_time'],
                            'update_time'    => $value['create_time'] + mt_rand(0, 20),
                            'status_message' => 'DB:0101', //无效号码
                            'real_message'   => 'DB:0101',
                        ];

                        $all_log[] = $send_log;
                    }

                    $j++;
                    if ($j > 100) {
                        $j = 1;
                        Db::startTrans();
                        try {
                            Db::table('yx_user_send_code_task_log')->insertAll($true_log);
                            if (!empty($all_log)) {
                                Db::table('yx_user_send_code_task_log')->insertAll($all_log);
                            }
                            Db::commit();
                        } catch (\Exception $e) {
                            // $this->redis->rPush('index:meassage:business:sendtask', $send);

                            Db::rollback();
                            exception($e);
                        }
                        unset($all_log);
                        unset($true_log);
                        unset($push_messages);
                        // echo time() . "\n";
                        unset($rollback);
                    }
                }
                //    if (Db::query("SELECT * FROM yx_user_send_code_task_log WHERE `task_no` = '".$value['task_no']."' ")) {}
            }
            if (!empty($true_log)) {
                Db::startTrans();
                try {
                    Db::table('yx_user_send_code_task_log')->insertAll($true_log);
                    if (!empty($all_log)) {
                        Db::table('yx_user_send_code_task_log')->insertAll($all_log);
                    }
                    Db::commit();
                } catch (\Exception $e) {
                    // $this->redis->rPush('index:meassage:business:sendtask', $send);

                    Db::rollback();
                    exception($e);
                }
                unset($all_log);
                unset($true_log);
            }
        } catch (\Exception $e) {
            exception($e);
        }
    }

    public function SFLpush()
    {
        // $start_time = strtotime("2020-04-22 19:59:00");
        // $end_time = strtotime("2020-04-23 10:01:00");
        $this->redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        // date_default_timezone_set('PRC');
        $redisMessageMarketingSend = 'index:meassage:business:buffersendtask';
        $redisMessagemulSend = 'index:meassage:multimediamessage:buffersendtask';
        /* for ($i = 215906; $i < 216942; $i++) {

        } */
        // $this->redis->rpush($redisMessageMarketingSend,674922);

        try {
            while (true) {
                $task_id = $this->redis->lpop($redisMessageMarketingSend);
                $task_id = json_decode($task_id, true);
                if ($task_id['id'] <= 92926) {
                    $this->redis->rpush("index:meassage:multimediamessage:sendtask", json_encode($task_id));
                }
                if (empty($task_id)) {
                    // exit('OVER');
                    $mul_task = $this->redis->lpop($redisMessagemulSend);
                    if (!empty($mul_task)) {
                        $this->redis->rpush("index:meassage:multimediamessage:sendtask", $mul_task);
                    } else {
                        // $task = Db::query("SELECT `id` FROM yx_user_send_code_task WHERE `uid` = 91 AND `id` >= 641731 AND `id` <= 643837 ");
                        // foreach ($task as $key => $value) {
                        //     Db::table('yx_user_send_code_task')->where('id',$value['id'])->update(['yidong_channel_id' => 9, 'liantong_channel_id' => 9, 'dianxin_channel_id' => 9]);
                        // }
                        exit('OVER');
                    }
                }
                // Db::table('yx_user_send_code_task')->where('id',$task_id)->update(['yidong_channel_id' => 9, 'liantong_channel_id' => 9, 'dianxin_channel_id' => 9]);
                // $task_id['deduct'] = 50; 
                $this->redis->rpush("index:meassage:business:sendtask", json_encode($task_id));
            }
        } catch (\exception $e) {
            exception($e);
        }
    }
    //彩信
    public function receiptMulForSFL()
    {
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        $this->redis = Phpredis::getConn();
        $start_time = strtotime('2020-06-04 20:00:00');
        $end_time   = strtotime("2020-07-11 20:00:00");
        $mul_task   = Db::query("SELECT `id`,`uid`,`mobile`,`status_message`,`task_no`,FROM_UNIXTIME(create_time),FROM_UNIXTIME(update_time),`create_time` FROM yx_user_multimedia_message_log WHERE `task_no`  IN (SELECT `task_no` FROM yx_user_multimedia_message WHERE `uid` = '91' AND `create_time` >= '" . $start_time . "' AND  `create_time` <= '" . $end_time . "') AND `status_message` = '' ");
        // echo "SELECT `id`,`uid`,`mobile`,`status_message`,`task_no`,FROM_UNIXTIME(create_time),FROM_UNIXTIME(update_time) FROM yx_user_multimedia_message_log WHERE `task_no`  IN (SELECT `task_no` FROM yx_user_multimedia_message WHERE `uid` = '91' AND `create_time` >= '".$start_time."' AND  `create_time` <= '".$end_time."') AND `status_message` = '' " ;die;
        // echo count($mul_task);die;
        // $num = count($mul_task) - 12;
        // $mul_task   = Db::query("SELECT `id`,`uid`,`mobile`,`status_message`,`task_no`,FROM_UNIXTIME(create_time),FROM_UNIXTIME(update_time) FROM yx_user_multimedia_message_log WHERE `task_no`  IN (SELECT `task_no` FROM yx_user_multimedia_message WHERE `uid` = '91' AND `create_time` >= '" . $start_time . "' AND  `create_time` <= '" . $end_time . "') AND `status_message` = '' ORDER BY rand() LIMIT  " . $num);
        // echo count($mul_task);die;
        foreach ($mul_task as $key => $value) {
            $num = max(0, 1000);
            $time =  time() - mt_rand(0, 57);
            // if ($num > 15) {
            if ($value['mobile'] == '15021417314') {
                /*  Db::table('yx_user_multimedia_message_log')->where('id', $value['id'])->update([
                        'send_status'    => 3,
                        'update_time'    => $time,
                        'status_message' => '-30',
                        'task_id'        => $value['id'],
                    ]);
                    Db::commit();
                    $this->redis->rpush('index:meassage:code:user:mulreceive:' . $value['uid'], json_encode([
                        'task_no'        => $value['task_no'],
                        'status_message' => "-30",
                        'message_info'   => '发送失败',
                        'mobile'         => $value['mobile'],
                        // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                        'send_time'      => date('Y-m-d H:i:s',$time),
                    ])); //写入用户带处理日志 */
                continue;
            }
            $day = date('Ymd', $value['create_time']);
            $sendday = 0;
            // echo $dayTime;die;
            $dayTime = $value['create_time'];

            if (date('H', $value['create_time']) >= 20) {
                $sendday = $day + 1;
                $dayTime = $sendday . '100000';
                // $send_time = 
                // $dayTime = strtotime($dayTime);
                $dayTime = strtotime($dayTime);
            }
            if (date('H', $value['create_time']) <= 10) {
                $sendday = $day;
                $dayTime = $sendday . '100000';
                $dayTime = strtotime($dayTime);
            }
            $dayTime = intval($dayTime) + mt_rand(10, 300);

            Db::startTrans();
            try {
                Db::table('yx_user_multimedia_message_log')->where('id', $value['id'])->update([
                    'send_status'    => 3,
                    'update_time'    => $time,
                    'status_message' => 'DELIVRD',
                    'task_id'        => $value['id'],
                ]);
                Db::commit();
                $this->redis->rpush('index:meassage:code:user:mulreceive:' . $value['uid'], json_encode([
                    'task_no'        => $value['task_no'],
                    'status_message' => "DELIVRD",
                    'message_info'   => '发送成功',
                    'mobile'         => $value['mobile'],
                    // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                    'send_time'      => date('Y-m-d H:i:s', $dayTime),
                ])); //写入用户带处理日志
            } catch (\Exception $e) {
                // $this->redis->rPush('index:meassage:business:sendtask', $send);

                Db::rollback();
                exception($e);
            }

            // }
        }
        exit;
    }

    public function receiptCodeTaskForSFL()
    {
        $this->redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        //  $end_time = strtotime('2020-04-23 20:00:00');
        $new_receipts = [];
        $yestarday_num = Db::query("SELECT COUNT(`id`) FROM yx_user_send_code_task WHERE `uid` = 91 AND `create_time` >= 1588680000 AND `create_time` <= 1588766400 ")[0]['COUNT(`id`)'];
        $yestarday_receipt = Db::query("SELECT `real_message` FROM yx_send_code_task_receipt WHERE `task_id` IN (SELECT `id` FROM  yx_user_send_code_task WHERE `uid` = 91 AND `create_time` >= 1588680000 AND `create_time` <= 1588766400 )");
        $receive_num = count($yestarday_receipt);
        // print_r($receive_num);die;
        $receipts['UNKNOWN'] = $yestarday_num - $receive_num;
        foreach ($yestarday_receipt as $key => $value) {
            if (isset($receipts[$value['real_message']])) {
                $receipts[$value['real_message']]++;
            } else {
                $receipts[$value['real_message']]  = 1;
            }
        }
        asort($receipts);
        $i = 0;
        foreach ($receipts as $key => $va) {
            $j = $i + $va;
            $new_receipts[$j] = $key;
            $i = $j;
        }
        // print_r($new_receipts);die;
        // echo $num;
        $start_time = strtotime("2020-05-06 20:00:00");

        $end_time   = strtotime("2020-05-07 20:00:00");;
        $mul_task   = Db::query("SELECT * FROM yx_user_send_code_task WHERE `uid` = 91 AND `create_time` >= '" . $start_time . "' AND  `create_time` <= '" . $end_time . "' ORDER BY id ASC ");
        print_r(count($mul_task));
        die;
        foreach ($mul_task as $key => $value) {
            $mobile_content = $value['mobile_content'];
            // $time = $value['update_time'];
            $time =  time() - mt_rand(0, 6000);
            /*  if ($time >= 1587470400 && $time <= 1587520800 && $value['uid']) {
                $time = '1587520801';
            }elseif ($time >= 1587556800 && $time <= 1587607200) {
                $time = '1587607201';
            }else{
                $time = $value['update_time'];
            } */
            $mobile_data = explode(',', $mobile_content);
            for ($i = 0; $i < count($mobile_data); $i++) {
                $task_log = Db::query("SELECT `id`,`status_message` FROM yx_send_code_task_receipt WHERE `task_id` = '" . $value['id'] . "' AND `mobile` = '" . $mobile_data[$i] . "'");
                $status_message = '';
                $message_info = '';
                $num = mt_rand(0, $yestarday_num);
                // echo $num."\n";
                foreach ($new_receipts as $nkey => $rval) {
                    if ($num <= $nkey) {
                        $status_message = $rval;
                        break;
                    }
                }
                if ($status_message == 'UNKNOWN') {
                    continue;
                } elseif ($status_message == 'DELIVRD') {
                    $message_info = '发送成功';
                } elseif ($status_message == 'DB:0141') {
                    $message_info = '黑名单';
                } else {
                    $message_info = '发送失败';
                }
                if (empty($task_log)) {
                    try {
                        Db::table('yx_send_code_task_receipt')->insert([
                            'mobile'         => $mobile_data[$i],
                            'create_time'    => time(),
                            'task_id'        => $value['id'],
                            // 'send_status'         => 3,
                            'status_message'         => $status_message,
                            // 'message_info'         => '发送成功',
                        ]);
                        Db::commit();
                        $this->redis->rpush('index:meassage:code:user:receive:' . $value['uid'], json_encode([
                            'task_no'        => $value['task_no'],
                            'status_message' => "DELIVRD",
                            'message_info'   => $message_info,
                            'mobile'         => $mobile_data[$i],
                            // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                            'send_time'      => date('Y-m-d H:i:s', $time),
                        ])); //写入用户带处
                        // $redis->rpush('index:meassage:code:user:receive:' . $task[0]['uid'], json_encode([
                        //     'task_no'        => trim($task[0]['task_no']),
                        //     'status_message' => $stat,
                        //     'message_info'   => $message_info,
                        //     'mobile'         => trim($send_log['mobile']),
                        //     // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                        //     'send_time'      => isset($send_log['receive_time']) ? date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                        // ])); //写入用户带
                    } catch (\Exception $e) {
                        exception($e);
                    }
                } else {
                    Db::table('yx_send_code_task_receipt')->where('id', $task_log[0]['id'])->update(
                        [
                            'create_time' => $time,
                            'status_message' => $status_message
                        ]
                    );
                }
            }
        }
    }

    public function receiptMulToBase()
    {
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        // $start_time = strtotime(date("Y-m-d", strtotime("-2 day")));
        $start_time = strtotime("2020-06-01");
        // $end_time = strtotime('2020-04-23 20:00:00');
        $end_time   = time();
        // $mul_task   = Db::query("SELECT * FROM yx_user_multimedia_message WHERE `uid` = '91' AND `create_time` >= '" . $start_time . "' AND  `create_time` <= '" . time() . "' ");
        $mul_task   = Db::query("SELECT * FROM yx_user_multimedia_message WHERE  `create_time` >= '" . $start_time . "' AND  `create_time` <= '" . $end_time . "' AND `free_trial` = 2 ORDER BY id ASC   ");
        // $mul_task   = Db::query("SELECT * FROM yx_user_multimedia_message_log WHERE `uid` = '91' AND `create_time` >= '" . $start_time . "' AND  `create_time` <= '" . time() . "' ");
        // print_r("SELECT * FROM yx_user_multimedia_message_log WHERE `uid` = '91' AND `create_time` >= '" . $start_time . "' AND  `create_time` <= '" . time() . "' ");die;
        foreach ($mul_task as $key => $value) {
            $mobile_content = $value['mobile_content'];
            $time = $value['update_time'];
            /*  if ($time >= 1587470400 && $time <= 1587520800 && $value['uid']) {
                $time = '1587520801';
            }elseif ($time >= 1587556800 && $time <= 1587607200) {
                $time = '1587607201';
            }else{
                $time = $value['update_time'];
            } */
            $mobile_data = explode(',', $mobile_content);
            for ($i = 0; $i < count($mobile_data); $i++) {
                $task_log = Db::query("SELECT `id`,`status_message` FROM yx_user_multimedia_message_log WHERE `task_no` = '" . $value['task_no'] . "' AND `mobile` = '" . $mobile_data[$i] . "'");
                if (empty($task_log)) {
                    try {
                        Db::table('yx_user_multimedia_message_log')->insert([
                            'uid'            => $value['uid'],
                            'task_no'        => $value['task_no'],
                            'mobile'         => $mobile_data[$i],
                            'send_status'    => 3,
                            'create_time'    => $time,
                            'update_time'    => time(),
                            'task_id'        => $value['id'],
                            'source'         => $value['source'],
                            // 'send_status'         => 3,
                            // 'status_message'         => 'DELIVRD',
                            // 'message_info'         => '发送成功',
                        ]);
                        Db::commit();
                    } catch (\Exception $e) {
                        exception($e);
                    }
                } else {
                    Db::table('yx_user_multimedia_message_log')->where('id', $task_log[0]['id'])->update(
                        [
                            'create_time' => $time,
                        ]
                    );
                }
            }
        }
    }

    public function writeBusinessLogToBase()
    {
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        // $start_time = strtotime(date("Y-m-d", strtotime("-2 day")));
        $start_time = strtotime("2020-06-01");
        // $end_time = strtotime('2020-04-23 20:00:00');
        $end_time   = time();
        // $mul_task   = Db::query("SELECT * FROM yx_user_multimedia_message WHERE `uid` = '91' AND `create_time` >= '" . $start_time . "' AND  `create_time` <= '" . time() . "' ");
        $code_task   = Db::query("SELECT * FROM yx_user_send_code_task WHERE  `create_time` >= '" . $start_time . "' AND  `create_time` <= '" . $end_time . "' AND `uid` = 91 AND `free_trial` = 2 ORDER BY id ASC   ");
        foreach ($code_task as $key => $value) {
            $mobile_content = $value['mobile_content'];
            $time = $value['update_time'];
            $mobile_data = explode(',', $mobile_content);
            for ($i = 0; $i < count($mobile_data); $i++) {
                $task_log = Db::query("SELECT `id`,`status_message` FROM yx_user_send_code_task_log WHERE `task_no` = '" . $value['task_no'] . "' AND `mobile` = '" . $mobile_data[$i] . "'");
                if (empty($task_log)) {
                    try {
                        Db::table('yx_user_send_code_task_log')->insert([
                            'uid'            => $value['uid'],
                            'task_no'        => $value['task_no'],
                            'mobile'         => $mobile_data[$i],
                            'task_content'         => $value['task_content'],
                            'send_status'    => 3,
                            'create_time'    => $time,
                            'send_length'    => mb_strlen($value['task_content'], 'utf8'),
                            'update_time'    => $time,
                            'source'         => $value['source'],
                            // 'send_status'         => 3,
                            // 'status_message'         => 'DELIVRD',
                            // 'message_info'         => '发送成功',
                        ]);
                        Db::commit();
                    } catch (\Exception $e) {
                        exception($e);
                    }
                }
            }
        }
    }

    public function taskReceipt()
    {
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        $num = Db::query("SELECT * FROM yx_send_task_receipt LIMIT 50000 ");
        // $id_num = $num[0]['count(`id`)'];
        // print_r(count($num));die;

        $del_ids = [];
        try {

            do {
                $del_ids = [];
                $num = Db::query("SELECT * FROM yx_send_task_receipt LIMIT 50000 ");
                foreach ($num as $key => $value) {
                    $task = Db::query("SELECT `task_no` FROM yx_user_send_task WHERE `id` = " . $value['task_id']);
                    if (!empty($task)) {
                        $task_log = Db::query("SELECT * FROM  yx_user_send_task_log WHERE `task_no` = '" . $task[0]['task_no'] . "'");
                        if (!empty($task_log)) {
                            if (strpos($value['real_message'], 'DB:0141') !== false || strpos($value['real_message'], 'MBBLACK') !== false || strpos($value['real_message'], 'BLACK') !== false) {
                                $message_info = '黑名单';
                            } else if (trim($value['real_message'] == 'DELIVRD')) {
                                $message_info = '发送成功';
                            } else if (in_array(trim($value['real_message']), ['REJECTD', 'REJECT', 'MA:0001'])) {
                                $message_info = '发送成功';
                            } else {
                                $message_info = '发送失败';
                            }
                            if ($task_log[0]['uid'] == '91') {
                                if (strpos($value['real_message'], 'DB:0141') !== false) {
                                    $message_info = '发送成功';
                                    $value['status_message'] = 'DELIVRD';
                                }
                            }
                            Db::table('yx_user_send_task_log')->where('id', $task_log[0]['id'])->update(
                                [
                                    'status_message' => $value['status_message'],
                                    'real_message' => $value['real_message'],
                                    'update_time' => $value['create_time'],
                                    'message_info' => $message_info,
                                ]
                            );
                            $del_ids[] = $value['id'];
                        }
                    }
                }
                $ids = join(',', $del_ids);
                Db::table('yx_user_send_code_task_log')->where("id in ($ids)")->delete();
                die;
            } while ($num);
        } catch (\Exception $th) {
            exception($th);
        }
    }

    public function taskCodeReceipt()
    {
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        $num = Db::query("SELECT count(`id`) FROM yx_send_code_task_receipt ");
        $id_num = $num[0]['count(`id`)'];
        // print_r($id_num);die;
        $del_ids = [];
        try {
            $j = 1;
            for ($i = 0; $i < $id_num; $i++) {
                $this_id = $i + 1;
                $task_code_receipt = Db::query("SELECT * FROM yx_send_code_task_receipt WHERE `id` = " . $this_id);
                if (empty($task_code_receipt)) {
                    continue;
                }
                $task = Db::query("SELECT `task_no` FROM yx_user_send_code_task WHERE `id` = " . $task_code_receipt[0]['task_id']);
                if (!empty($task)) {
                    $task_log = Db::query("SELECT * FROM  yx_user_send_code_task_log WHERE `task_no` = '" . $task[0]['task_no'] . "'");
                    if (!empty($task_log)) {
                        if (strpos($task_code_receipt[0]['status_message'], 'DB:0141') !== false || strpos($task_code_receipt[0]['status_message'], 'MBBLACK') !== false || strpos($task_code_receipt[0]['status_message'], 'BLACK') !== false) {
                            $message_info = '黑名单';
                        } else if (trim($task_code_receipt[0]['status_message'] == 'DELIVRD')) {
                            $message_info = '发送成功';
                        } else if (in_array(trim($task_code_receipt[0]['status_message']), ['REJECTD', 'REJECT', 'MA:0001'])) {
                            $message_info = '发送失败';
                        } else {
                            $message_info = '发送失败';
                        }
                        Db::table('yx_user_send_code_task_log')->where('id', $task_log[0]['id'])->update(
                            [
                                'status_message' => $task_code_receipt[0]['status_message'],
                                'real_message' => $task_code_receipt[0]['real_message'],
                                'update_time' => $task_code_receipt[0]['create_time'],
                                'message_info' => $message_info,
                            ]
                        );
                        /*  $del_ids[] = $i;
                        $j++;
                        if ($j > 100) {
                            $ids = join(',', $del_ids);
                            Db::table('yx_send_code_task_receipt')->where("id in ($ids)")->delete();
                            $j = 1;
                            $del_ids = [];
                        } */
                    }
                }
            }
            // die;
            /*   $ids = join(',', $del_ids);
            Db::table('yx_send_code_task_receipt')->where("id in ($ids)")->delete(); */
        } catch (\Exception $th) {
            exception($th);
        }
    }

    public function taskReceiptMarketing()
    {
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        $num = Db::query("SELECT count(`id`) FROM yx_send_task_receipt ");
        $id_num = $num[0]['count(`id`)'];
        // print_r($id_num);die;
        $del_ids = [];
        try {
            $j = 1;
            for ($i = 0; $i < $id_num; $i++) {
                $this_id = $i + 1;
                $task_code_receipt = Db::query("SELECT * FROM yx_send_task_receipt WHERE `id` = " . $this_id);
                // print_r($task_code_receipt);die;
                if (empty($task_code_receipt)) {
                    continue;
                }
                $task = Db::query("SELECT `task_no` FROM yx_user_send_task WHERE `id` = " . $task_code_receipt[0]['task_id']);
                if (!empty($task)) {
                    $task_log = Db::query("SELECT * FROM  yx_user_send_task_log WHERE `task_no` = '" . $task[0]['task_no'] . "'");
                    if (!empty($task_log)) {
                        $message_info = '';
                        if (strpos($task_code_receipt[0]['status_message'], 'DB:0141') !== false || strpos($task_code_receipt[0]['status_message'], 'MBBLACK') !== false || strpos($task_code_receipt[0]['status_message'], 'BLACK') !== false) {
                            $message_info = '黑名单';
                        } else if (trim($task_code_receipt[0]['status_message'] == 'DELIVRD')) {
                            $message_info = '发送成功';
                        } else if (in_array(trim($task_code_receipt[0]['status_message']), ['REJECTD', 'REJECT', 'MA:0001'])) {
                            $message_info = '发送成功';
                        } else {
                            $message_info = '发送失败';
                        }
                        Db::table('yx_user_send_task_log')->where('id', $task_log[0]['id'])->update(
                            [
                                'status_message' => $task_code_receipt[0]['status_message'],
                                'real_message' => $task_code_receipt[0]['real_message'],
                                'update_time' => $task_code_receipt[0]['create_time'],
                                'message_info' => $message_info,
                            ]
                        );
                        /*  $del_ids[] = $i;
                        $j++;
                        if ($j > 100) {
                            $ids = join(',', $del_ids);
                            Db::table('yx_send_task_receipt')->where("id in ($ids)")->delete();
                            $j = 1;
                            $del_ids = [];
                        } */
                    }
                }
            }
            // die;
            /*  $ids = join(',', $del_ids);
            Db::table('yx_send_task_receipt')->where("id in ($ids)")->delete(); */
        } catch (\Exception $th) {
            exception($th);
        }
    }

    public function Bufa()
    {
        ini_set('memory_limit', '10240M'); // 临时设置最大内存占用为3G
        /*  $black_error_path = realpath("./") . "/newc.txt";
        $black_error_file       = fopen($black_error_path, "r");
        $black_error_mobile = [];
        while (!feof($black_error_file)) {
            $cellVal = trim(fgets($black_error_file));
            if (!empty($cellVal)) {
                $cellVal = str_replace('"', '', $cellVal);
                $cellVal = trim($cellVal);
                // print_r($cellVal);die;
                $black_error_mobile[] = $cellVal;
            }
        }
        // print_r(count($black_error_mobile))
        fclose($black_error_file); */
        // print_r($black_error_mobile);die;
        $this->redis = Phpredis::getConn();
        /*  $res = $this->redis->rpush('index:meassage:code:send' . ":" . 145, json_encode([
            'mobile'      => 18335103753,
            'title'       => '【驰加汽车服务中心】尊敬的驰加会员，您的299元缤纷抵扣券包中还有礼券尚未使用。登录微信公众号“驰加汽车服务中心”查看礼券详情。点击 http://mrw.so/6r5hHO 即刻预约门店，退订回TD',
            'mar_task_id' => 406431,
            'content'     => '【驰加汽车服务中心】尊敬的驰加会员，您的299元缤纷抵扣券包中还有礼券尚未使用。登录微信公众号“驰加汽车服务中心”查看礼券详情。点击 http://mrw.so/6r5hHO 即刻预约门店，退订回TD',
            'msg_id'        => '13000710020200925111943169151',
            'from'        => 'yx_user_send_task',
        ])); */
        try {
            // $bufa = Db::query("SELECT * FROM `messagesend`.`yx_user_send_task` WHERE `uid` = '291' AND `id` >= '406453'  ");
            $bufa = Db::query("SELECT * FROM `messagesend`.`yx_user_send_task` WHERE `id` IN (SELECT `task_id` FROM `messagesend`.`yx_send_task_receipt` WHERE `task_id` IN (SELECT id FROM `messagesend`.`yx_user_send_task` WHERE `uid` = '191' AND `id` > '416765' AND `id` < '425670' ) AND `status_message` = 'DB:0107')");
            $ids = Db::query("SELECT `id` FROM `messagesend`.`yx_send_task_receipt` WHERE `task_id` IN (SELECT id FROM `messagesend`.`yx_user_send_task` WHERE `uid` = '191' AND `id` > '416765' AND `id` < '425670' ) AND `status_message` = 'DB:0107'");
            // print_r(count($bufa));
            // die;
            $newids = [];
            foreach ($ids as $key => $value) {
                $newids[] = $value['id'];
            }
            $ids = [];
            $ids = join(',', $newids);
            Db::table('yx_user_send_task')->where("id in ($ids)")->delete();
            foreach ($bufa as $key => $value) {
                # code...
                $mobile_content = explode(',', $value['mobile_content']);

                foreach ($mobile_content as $mkey => $mvalue) {
                    /* if (in_array($mvalue, $black_error_mobile)) {
                        
                    } */
                    $prefix = '';
                    $prefix = substr(trim($mvalue), 0, 7);
                    $res    = Db::query("SELECT `source`,`province_id`,`province` FROM `yx_number_source` WHERE `mobile` = '" . $prefix . "'");
                    // print_r($res);
                    $channel_id = 0;
                    if ($res) {
                        $newres = array_shift($res);
                        if ($newres['source'] == 1) {
                            $channel_id = 126;
                        } elseif ($newres['source'] == 2) {
                            $channel_id = 127;
                        } elseif ($newres['source'] == 3) {
                            $channel_id = 128;
                        }
                    } else {
                        $channel_id = 126;
                    }
                    $sendmessage = [];
                    $sendmessage = [
                        'msg_id'      => $value['send_msg_id'],
                        'title'      => $value['task_name'],
                        'mobile'      => $mvalue,
                        'mar_task_id' => $value['id'],
                        'uid' => $value['uid'],
                        'content'     => $value['task_content'],
                        'from'        => 'yx_user_send_task',
                    ];
                    $this->redis->rpush('index:meassage:code:send' . ":" . $channel_id, json_encode($sendmessage)); //三体营销通道 
                }
            }
        } catch (\EXception $th) {
            //throw $th;
            exception($th);
        }
    }

    /* SFL sftp 独立发送体系 */
    /* 短信模块 */
    public function SendSflTask()
    {
        $mysql_connect = Db::connect(Config::get('database.db_sflsftp'));
        $mysql_connect->query("set names utf8mb4");
        $this->redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        /*  for ($i = 90696; $i < 90719; $i++) {
            $this->redis->rpush('index:meassage:sflmessage:sendtask', $i);
        } */
        /* $this->redis->rpush('index:meassage:sflmessage:sendtask', 90624);
        $this->redis->rpush('index:meassage:sflmessage:sendtask', 90625);
        $this->redis->rpush('index:meassage:sflmessage:sendtask', 90633);
        $this->redis->rpush('index:meassage:sflmessage:sendtask', 90645);
        $this->redis->rpush('index:meassage:sflmessage:sendtask', 90655);
        $this->redis->rpush('index:meassage:sflmessage:sendtask', 90667);
        $this->redis->rpush('index:meassage:sflmessage:sendtask', 90669);
        $this->redis->rpush('index:meassage:sflmessage:sendtask', 90672);
        $this->redis->rpush('index:meassage:sflmessage:sendtask', 90679); */
        // $this->redis->rpush('index:meassage:sflmessage:sendtask', 90695);
        /* $this->redis->rpush('index:meassage:sflmessage:sendtask', 73735);
        $this->redis->rpush('index:meassage:sflmessage:sendtask', 73740);
        $this->redis->rpush('index:meassage:sflmessage:sendtask', 73754);
        $this->redis->rpush('index:meassage:sflmessage:sendtask', 73755);
        $this->redis->rpush('index:meassage:sflmessage:sendtask', 73764); */
        $white_list = [
            13918001944, 13023216322, 18616841500, 15021417314, 15000773110, 18217584060, 13585699417, 15800400970, 13472865840, 13611664019, 13636311653, 13701789119, 13764272451, 13801687321, 13816091848, 13817515864, 13818181256, 13916292097, 13917823241, 13918902911, 15000773110, 15800815262, 15921904656, 18800232095, 13918153000, 18817718456, 15000796805, 13681961185, 13681961185, 18817718456, 13918153000, 15000796805, 13162248755, 16621181441, 18501684687, 18521329177, 18521569417, 18621714497, 18621720742, 18618353064, 18618353064, 18013770122, 18019762207, 18121252120, 18918267758, 18918267758, 18817718456, 18618353064, 18602893299, 15099630574, 15150180286, 15105518868, 15852736815, 15189366366
        ];
        // echo "SELECT * FROM yx_sfl_send_task WHERE `mobile` IN (".join(',',$white_list).") ";die;
        // $tody_time = 1595491200;
        $tody_time = strtotime(date("Ymd", time()));
        // $tody_time = 1596189600;// 时间下午16点3条 已发第一条
        // $tody_time = 1596877200; // 时间下午17点20
        try {
            // $mysql_connect->table('yx_sfl_send_task')->where([['create_time', '>', $tody_time]])->update(['free_trial' => 2, 'yidong_channel_id' => 156, 'liantong_channel_id' => 157, 'dianxin_channel_id' => 157, 'update_time' => time()]);
            $mysql_connect->table('yx_sfl_send_task')->where([['template_id', '=', '100185486']])->update(['free_trial' => 2, 'yidong_channel_id' => 156, 'liantong_channel_id' => 157, 'dianxin_channel_id' => 157, 'update_time' => time()]);
            // $mysql_connect->table('yx_sfl_send_task')->where([['template_id', '=', '100183187']])->update(['free_trial' => 2, 'yidong_channel_id' => 83, 'liantong_channel_id' => 84, 'dianxin_channel_id' => 84, 'update_time' => time()]);
            /* $where = [];
            $where = [['create_time','>',$tody_time],['template_id', '<>','100150821']];
            $mysql_connect->table('yx_sfl_send_task')->where($where)->update(['free_trial' => 2, 'yidong_channel_id' => 86, 'liantong_channel_id' => 88, 'dianxin_channel_id' => 87]);*/
            // $sendid = $mysql_connect->query("SELECT `id` FROM yx_sfl_send_task WHERE `create_time` >  '" . $tody_time . "' AND `template_id` NOT IN ('100184476','100184475')  ");
            $sendid = $mysql_connect->query("SELECT `id` FROM yx_sfl_send_task WHERE `template_id` = '100185486'   ");
            // $sendid = $mysql_connect->query("SELECT `id` FROM yx_sfl_send_task WHERE `template_id`  IN ('100182791','100181685','100182168','100182172') AND  `create_time` >  '" . $tody_time . "' AND mobile IN (".join(',',$white_list).") ");
            // echo "SELECT * FROM yx_sfl_send_task WHERE `template_id`  IN ('100182791','100181685','100182168','100182172') AND  `create_time` >  '" . $tody_time . "' AND mobile IN (".join(',',$white_list).") ";die;
            // $sendid = $mysql_connect->query("SELECT `id` FROM yx_sfl_send_task WHERE `template_id`  IN ('100182166','100182575') AND  `create_time` >  '" . $tody_time . "' ");
            // echo "SELECT `id` FROM yx_sfl_send_task WHERE `template_id` = '100181593' AND `create_time` >  " . $tody_time;die;
            // $sendid = $mysql_connect->query("SELECT `id` FROM `sflsftp`.`yx_sfl_send_task` WHERE `template_id` IN ('100181864','100181869') ");
            // echo "SELECT `id` FROM yx_sfl_send_task WHERE `template_id` = '100180528' AND `create_time` >  " . $tody_time;die;
            foreach ($sendid as $key => $value) {
                $this->redis->rpush('index:meassage:sflmessage:sendtask', $value['id']);
            }
        } catch (\Exception $th) {
            exception($th);
        }
        // die;
        $deduct = 2; //1扣量,2不扣
        $rate = 60;

        $ids = [];
        $j = 1;
        $receipt = [];
        $send_msg = [];
        while (true) {
            $task_id = $this->redis->lpop('index:meassage:sflmessage:sendtask');
            if (empty($task_id)) {
                break;
            }
            $ids[] = $task_id;
            $j++;
            if ($j > 100) {
                $all_send_task = [];
                $all_send_task = $mysql_connect->query("SELECT *  FROM yx_sfl_send_task WHERE `id` IN (" . join(',', $ids) . ") ");
                foreach ($all_send_task as $key => $value) {
                     if (in_array(trim($value['mobile']), $white_list)) {
                        continue;
                    }
                    $sendmessage = [];
                    if (!$value['yidong_channel_id'] || !$value['liantong_channel_id'] || !$value['dianxin_channel_id']) {
                        continue;
                    }
                    if (checkMobile($value['mobile']) != false) {
                        $end_num = substr($value['mobile'], -6);
                        // $end_num = substr($mobilesend[$i], -6);
                        //按无效号码计算
                        if (in_array($end_num, ['000000', '111111', '222222', '333333', '444444', '555555', '666666', '777777', '888888', '999999'])) {
                            $rece = [];
                            $rece = [
                                'mseeage_id'      => $value['mseeage_id'],
                                'template_id'      => $value['template_id'],
                                'task_id' => $value['id'],
                                'mobile' => $value['mobile'],
                                'messageinfo' => '发送失败',
                                'status_message' => 'SMS:2',
                            ];
                            $receipt[] = $rece;
                        } else {
                            if ($deduct == 1) {
                                $rate = $rate;
                                $num = mt_rand(0, 100);
                                if (in_array($value['template_id'], ['514', '100183154', '100183155'])) { //生日不扣
                                    //strpos($value['task_content'], '生日') !== false ||
                                    // print_r($value['task_content']);die;
                                    $prefix = '';
                                    $prefix = substr(trim($value['mobile']), 0, 7);
                                    $res    = Db::query("SELECT `source`,`province_id`,`province` FROM `yx_number_source` WHERE `mobile` = '" . $prefix . "'");
                                    // print_r($res);
                                    if ($res) {
                                        $newres = array_shift($res);
                                        if ($newres['source'] == 1) {
                                            $channel_id = $value['yidong_channel_id'];
                                        } elseif ($newres['source'] == 2) {
                                            $channel_id = $value['liantong_channel_id'];
                                        } elseif ($newres['source'] == 3) {
                                            $channel_id = $value['dianxin_channel_id'];
                                        }
                                    }

                                    //正常发送
                                    /*  $sendmessage = [
                                            'mobile'      => $value['mobile'],
                                            'mar_task_id' => $value['id'],
                                            'content'     => $value['task_content'],
                                            'channel_id'  => $channel_id,
                                            'from'        => 'yx_sfl_send_task',
                                        ]; */
                                    $sendmessage = [
                                        'mseeage_id'      => $value['mseeage_id'],
                                        'template_id'      => $value['template_id'],
                                        'mobile'      => $value['mobile'],
                                        'mar_task_id' => $value['id'],
                                        'content'     => $value['task_content'],
                                        'from'        => 'yx_sfl_send_task',
                                        'channel_id'        => $channel_id,
                                    ];
                                    $send_msg[] = $sendmessage;
                                } else {
                                    //
                                    // echo "不含生日";
                                    // print_r($value['task_content']);die;
                                    /*  if (in_array(trim($value['mobile']), $white_list)) {
                                        continue;
                                    } */
                                    /*  if (in_array(trim($value['mobile']), $white_list)) {
                                        continue;
                                    } */
                                    /* if ($value['template_id'] == '100181315') {

                                        $rate = 60;
                                    } elseif ($value['template_id'] == '100181316') {
                                        if (in_array(trim($value['mobile']), $white_list)) {
                                            continue;
                                        }
                                        $rate = 40;
                                    } */
                                    if ($num >= $rate || in_array(trim($value['mobile']), $white_list)) {
                                        $prefix = '';
                                        $prefix = substr(trim($value['mobile']), 0, 7);
                                        $res    = Db::query("SELECT `source`,`province_id`,`province` FROM `yx_number_source` WHERE `mobile` = '" . $prefix . "'");
                                        // print_r($res);
                                        if ($res) {
                                            $newres = array_shift($res);
                                            if ($newres['source'] == 1) {
                                                $channel_id = $value['yidong_channel_id'];
                                            } elseif ($newres['source'] == 2) {
                                                $channel_id = $value['liantong_channel_id'];
                                            } elseif ($newres['source'] == 3) {
                                                $channel_id = $value['dianxin_channel_id'];
                                            }
                                        }

                                        //正常发送
                                        /*  $sendmessage = [
                                                'mobile'      => $value['mobile'],
                                                'mar_task_id' => $value['id'],
                                                'content'     => $value['task_content'],
                                                'channel_id'  => $channel_id,
                                                'from'        => 'yx_sfl_send_task',
                                            ]; */
                                        $sendmessage = [
                                            'mseeage_id'      => $value['mseeage_id'],
                                            'template_id'      => $value['template_id'],
                                            'mobile'      => $value['mobile'],
                                            'mar_task_id' => $value['id'],
                                            'content'     => $value['task_content'],
                                            'from'        => 'yx_sfl_send_task',
                                            'channel_id'        => $channel_id,
                                        ];
                                        $send_msg[] = $sendmessage;
                                    } else {
                                        $rece = [];
                                        $rece = [
                                            'task_id' => $value['id'],
                                            'mseeage_id'      => $value['mseeage_id'],
                                            'template_id'      => $value['template_id'],
                                            'mobile' => $value['mobile'],
                                            'messageinfo' => '发送成功',
                                            'status_message' => 'SMS:1',
                                        ];
                                        $receipt[] = $rece;
                                    }
                                }
                            } else {
                                $prefix = '';
                                $prefix = substr(trim($value['mobile']), 0, 7);
                                $res    = Db::query("SELECT `source`,`province_id`,`province` FROM `yx_number_source` WHERE `mobile` = '" . $prefix . "'");
                                // print_r($res);
                                if ($res) {
                                    $newres = array_shift($res);
                                    if ($newres['source'] == 1) {
                                        $channel_id = $value['yidong_channel_id'];
                                    } elseif ($newres['source'] == 2) {
                                        $channel_id = $value['liantong_channel_id'];
                                    } elseif ($newres['source'] == 3) {
                                        $channel_id = $value['dianxin_channel_id'];
                                    }
                                }

                                //正常发送
                                /*  $sendmessage = [
                                            'mobile'      => $value['mobile'],
                                            'mar_task_id' => $value['id'],
                                            'content'     => $value['task_content'],
                                            'channel_id'  => $channel_id,
                                            'from'        => 'yx_sfl_send_task',
                                        ]; */
                                $sendmessage = [
                                    'mseeage_id'      => $value['mseeage_id'],
                                    'template_id'      => $value['template_id'],
                                    'mobile'      => $value['mobile'],
                                    'mar_task_id' => $value['id'],
                                    'content'     => $value['task_content'],
                                    'from'        => 'yx_sfl_send_task',
                                    'channel_id'        => $channel_id,
                                ];
                                $send_msg[] = $sendmessage;
                            }
                        }
                        //按无效号码计算


                        // $res = $this->redis->rpush('index:meassage:code:send' . ":" . $channel_id, json_encode()); //三体营销通道
                    } else {
                        $rece = [];
                        $rece = [
                            'mseeage_id'      => $value['mseeage_id'],
                            'template_id'      => $value['template_id'],
                            'task_id' => $value['id'],
                            'mobile' => $value['mobile'],
                            'messageinfo' => '发送失败',
                            'status_message' => 'SMS:2',
                        ];
                        $receipt[] = $rece;
                        // $mysql_connect->table('yx_sfl_send_task_receipt')->insert();
                    }
                }
                if (!empty($receipt)) {
                    $mysql_connect->table('yx_sfl_send_task_receipt')->insertAll($receipt);
                }
                if (!empty($send_msg)) {
                    foreach ($send_msg as $skey => $svalue) {
                        $channel_id = $svalue['channel_id'];
                        unset($svalue['channel_id']);
                        $res = $this->redis->rpush('index:meassage:code:send' . ":" . $channel_id, json_encode($svalue)); //三体营销通道
                    }
                }


                unset($ids);
                unset($receipt);
                unset($send_msg);
                // unset($all_send_task);
            }
        }


        if (!empty($ids)) {
            $all_send_task = [];
            $all_send_task = $mysql_connect->query("SELECT *  FROM yx_sfl_send_task WHERE `id` IN (" . join(',', $ids) . ") ");
            foreach ($all_send_task as $key => $value) {
                $sendmessage = [];
                if (!$value['yidong_channel_id'] || !$value['liantong_channel_id'] || !$value['dianxin_channel_id']) {
                    continue;
                }
                /*   if (in_array(trim($value['mobile']), $white_list)) {
                    continue;
                } */
                if (checkMobile($value['mobile']) != false) {
                    $end_num = substr($value['mobile'], -6);
                    //按无效号码计算
                    //按无效号码计算
                    if (in_array($end_num, ['000000', '111111', '222222', '333333', '444444', '555555', '666666', '777777', '888888', '999999'])) {
                        $rece = [];
                        $rece = [
                            'mseeage_id'      => $value['mseeage_id'],
                            'template_id'      => $value['template_id'],
                            'task_id' => $value['id'],
                            'mobile' => $value['mobile'],
                            'messageinfo' => '发送失败',
                            'status_message' => 'SMS:2',
                        ];
                        $receipt[] = $rece;
                    } else {
                        if ($deduct == 1) { //扣量
                            if (in_array($value['template_id'],  ['514', '100183154', '100183155'])) { //生日不扣
                                // print_r($value['task_content']);die;
                                // strpos($value['task_content'], '生日') !== false ||
                                $prefix = '';
                                $prefix = substr(trim($value['mobile']), 0, 7);
                                $res    = Db::query("SELECT `source`,`province_id`,`province` FROM `yx_number_source` WHERE `mobile` = '" . $prefix . "'");
                                // print_r($res);
                                if ($res) {
                                    $newres = array_shift($res);
                                    if ($newres['source'] == 1) {
                                        $channel_id = $value['yidong_channel_id'];
                                    } elseif ($newres['source'] == 2) {
                                        $channel_id = $value['liantong_channel_id'];
                                    } elseif ($newres['source'] == 3) {
                                        $channel_id = $value['dianxin_channel_id'];
                                    }
                                }

                                //正常发送
                                /*  $sendmessage = [
                                        'mobile'      => $value['mobile'],
                                        'mar_task_id' => $value['id'],
                                        'content'     => $value['task_content'],
                                        'channel_id'  => $channel_id,
                                        'from'        => 'yx_sfl_send_task',
                                    ]; */
                                $sendmessage = [
                                    'mseeage_id'      => $value['mseeage_id'],
                                    'template_id'      => $value['template_id'],
                                    'mobile'      => $value['mobile'],
                                    'mar_task_id' => $value['id'],
                                    'content'     => $value['task_content'],
                                    'from'        => 'yx_sfl_send_task',
                                    'channel_id'        => $channel_id,
                                ];
                                $send_msg[] = $sendmessage;
                            } else {
                                $num = mt_rand(0, 100);
                                if ($value['template_id'] == '100181315') {
                                    if (in_array(trim($value['mobile']), $white_list)) {
                                        continue;
                                    }
                                    $rate = 60;
                                } elseif ($value['template_id'] == '100181316') {
                                    if (in_array(trim($value['mobile']), $white_list)) {
                                        continue;
                                    }
                                    $rate = 40;
                                }
                                if ($num >= $rate || in_array(trim($value['mobile']), $white_list)) {
                                    $prefix = '';
                                    $prefix = substr(trim($value['mobile']), 0, 7);
                                    $res    = Db::query("SELECT `source`,`province_id`,`province` FROM `yx_number_source` WHERE `mobile` = '" . $prefix . "'");
                                    // print_r($res);
                                    if ($res) {
                                        $newres = array_shift($res);
                                        if ($newres['source'] == 1) {
                                            $channel_id = $value['yidong_channel_id'];
                                        } elseif ($newres['source'] == 2) {
                                            $channel_id = $value['liantong_channel_id'];
                                        } elseif ($newres['source'] == 3) {
                                            $channel_id = $value['dianxin_channel_id'];
                                        }
                                    }

                                    //正常发送
                                    /*  $sendmessage = [
                                                'mobile'      => $value['mobile'],
                                                'mar_task_id' => $value['id'],
                                                'content'     => $value['task_content'],
                                                'channel_id'  => $channel_id,
                                                'from'        => 'yx_sfl_send_task',
                                            ]; */
                                    $sendmessage = [
                                        'mseeage_id'      => $value['mseeage_id'],
                                        'template_id'      => $value['template_id'],
                                        'mobile'      => $value['mobile'],
                                        'mar_task_id' => $value['id'],
                                        'content'     => $value['task_content'],
                                        'from'        => 'yx_sfl_send_task',
                                        'channel_id'        => $channel_id,
                                    ];
                                    $send_msg[] = $sendmessage;
                                } else {
                                    $rece = [];
                                    $rece = [
                                        'task_id' => $value['id'],
                                        'mseeage_id'      => $value['mseeage_id'],
                                        'template_id'      => $value['sfl_relation_id'],
                                        'mobile' => $value['mobile'],
                                        'messageinfo' => '发送成功',
                                        'status_message' => 'SMS:1',
                                    ];
                                    $receipt[] = $rece;
                                }
                            }

                            // $res = $this->redis->rpush('index:meassage:code:send' . ":" . $channel_id, json_encode()); //三体营销通道

                            // $mysql_connect->table('yx_sfl_send_task_receipt')->insert();
                        } else {
                            $prefix = '';
                            $prefix = substr(trim($value['mobile']), 0, 7);
                            $res    = Db::query("SELECT `source`,`province_id`,`province` FROM `yx_number_source` WHERE `mobile` = '" . $prefix . "'");
                            // print_r($res);
                            if ($res) {
                                $newres = array_shift($res);
                                if ($newres['source'] == 1) {
                                    $channel_id = $value['yidong_channel_id'];
                                } elseif ($newres['source'] == 2) {
                                    $channel_id = $value['liantong_channel_id'];
                                } elseif ($newres['source'] == 3) {
                                    $channel_id = $value['dianxin_channel_id'];
                                }
                            }

                            //正常发送
                            /*  $sendmessage = [
                                        'mobile'      => $value['mobile'],
                                        'mar_task_id' => $value['id'],
                                        'content'     => $value['task_content'],
                                        'channel_id'  => $channel_id,
                                        'from'        => 'yx_sfl_send_task',
                                    ]; */
                            $sendmessage = [
                                'mseeage_id'      => $value['mseeage_id'],
                                'template_id'      => $value['template_id'],
                                'mobile'      => $value['mobile'],
                                'mar_task_id' => $value['id'],
                                'content'     => $value['task_content'],
                                'from'        => 'yx_sfl_send_task',
                                'channel_id'        => $channel_id,
                            ];
                            $send_msg[] = $sendmessage;
                        }
                    }
                } else {
                    $rece = [];
                    $rece = [
                        'mseeage_id'      => $value['mseeage_id'],
                        'template_id'      => $value['template_id'],
                        'task_id' => $value['id'],
                        'mobile' => $value['mobile'],
                        'messageinfo' => '发送失败',
                        'status_message' => 'SMS:2',
                    ];
                    $receipt[] = $rece;
                }
            }
            if (!empty($receipt)) {
                $mysql_connect->table('yx_sfl_send_task_receipt')->insertAll($receipt);
            }
            if (!empty($send_msg)) {
                foreach ($send_msg as $skey => $svalue) {
                    $channel_id = $svalue['channel_id'];
                    unset($svalue['channel_id']);
                    $res = $this->redis->rpush('index:meassage:code:send' . ":" . $channel_id, json_encode($svalue)); //三体营销通道
                }
            }


            unset($ids);
            unset($receipt);
            unset($send_msg);
        }
    }


    /* 彩信模块 */
    public function SendSflMulTask()
    {
        $this->redis = Phpredis::getConn();
        $mysql_connect = Db::connect(Config::get('database.db_sflsftp'));
        $mysql_connect->query("set names utf8mb4");
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        /*   for ($i = 277491; $i < 277517; $i++) {
            $this->redis->rpush('index:meassage:sflmulmessage:sendtask', $i);
        } */
        // die;
        /*    $this->redis->rpush('index:meassage:sflmulmessage:sendtask', 3673);
        $this->redis->rpush('index:meassage:sflmulmessage:sendtask', 3674);
        $this->redis->rpush('index:meassage:sflmulmessage:sendtask', 3675); */
        // $this->redis->rpush('index:meassage:sflmulmessage:sendtask', 3676);
        /* $this->redis->rpush('index:meassage:sflmulmessage:sendtask', 3677);
        $this->redis->rpush('index:meassage:sflmulmessage:sendtask', 3678);
        $this->redis->rpush('index:meassage:sflmulmessage:sendtask', 3679); */
        // $this->redis->rpush('index:meassage:sflmulmessage:sendtask', 3680);
        /*  $bir = [];
        $all_path = realpath("./") . "/0529.txt";
        $file = fopen($all_path, "r");
        // $data = array();
        while (!feof($file)) {
            $cellVal = trim(fgets($file));
            $cellVal = str_replace('"', '', $cellVal);
            if (!empty($cellVal)) {
                array_push($bir, $cellVal);
            }
        } */
        // return $data;
        // print_r($data);die;
        $white_list = [
            13023216322,
            13918001944,
            18616841500,
            15021417314,
            15000773110,
            18217584060,
            13585699417,
            15800400970, 13472865840, 13611664019, 13636311653, 13701789119, 13764272451, 13801687321, 13816091848, 13817515864, 13818181256, 13916292097, 13917823241, 13918902911, 15000773110, 15800815262, 15921904656, 18800232095, 13918153000, 18817718456, 15000796805, 13681961185, 13681961185, 18817718456, 13918153000, 15000796805, 13162248755, 16621181441, 18501684687, 18521329177, 18521569417, 18621714497, 18621720742, 18618353064, 18618353064, 18013770122, 18019762207, 18121252120, 18918267758, 18918267758, 18817718456, 18618353064, 18602893299, 15099630574, 15150180286, 15105518868, 15852736815, 15189366366
        ];
        $tody_time = strtotime(date("Ymd", time()));
        // $tody_time = strtotime('2020-08-18 16:00');
        // $tody_time = 1594785600;
        try {
            /* $mysql_connect->query("UPDATE yx_sfl_multimedia_message SET `free_trial` = 2 AND `yidong_channel_id` = 94 AND `liantong_channel_id` = 94 AND `dianxin_channel_id` = 94 WHERE `create_time` >  ".$tody_time); */
            // $mysql_connect->table('yx_sfl_multimedia_message')->where([['create_time', '>', $tody_time],['sfl_relation_id','IN','100181558,100181556,100181563,100177398']])->update(['free_trial' => 2, 'yidong_channel_id' => 94, 'liantong_channel_id' => 94, 'dianxin_channel_id' => 94]);
            $mysql_connect->table('yx_sfl_multimedia_message')->where([['create_time', '>', $tody_time]])->update(['free_trial' => 2, 'yidong_channel_id' => 94, 'liantong_channel_id' => 94, 'dianxin_channel_id' => 94, 'update_time' => time()]);
            // $mysql_connect->table('yx_sfl_multimedia_message')->where([['sfl_relation_id', '=', '100184671']])->update(['free_trial' => 2, 'yidong_channel_id' => 94, 'liantong_channel_id' => 94, 'dianxin_channel_id' => 94, 'update_time' => time()]);
        } catch (\Exception $th) {
            exception($th);
        }
        $ids = [];
        $j = 1;
        $receipt = [];
        $send_msg = [];
        $deduct = 1; //1扣量,2不扣
        $rate = 65;
        /*    $all_task = 
        while (true) {
            $task_id = $this->redis->lpop('index:meassage:sflmulmessage:sendtask');
            if (empty($task_id)) {
                break;
            }
        } */
        // echo "SELECT `mobile` FROM yx_sfl_multimedia_message WHERE `sfl_relation_id` = '100181549' AND `mobile` = (".join(',',$white_list).") AND `create_time` >  " . $tody_time;die;
        try {
            $receipt_id = $mysql_connect->query("SELECT `id` FROM yx_sfl_send_multimediatask_receipt ORDER BY `id` DESC LIMIT 1  ")[0]['id'];
            $receipt_id++;
            // print_r($receipt_id);die;
            // $sendid = $mysql_connect->query("SELECT `id` FROM yx_sfl_multimedia_message WHERE   `create_time` >  " . $tody_time  . " AND `sfl_relation_id`  IN ('100181913','82301','82309','100125372')");
            // $sendid = $mysql_connect->query("SELECT `id` FROM yx_sfl_multimedia_message WHERE   `create_time` >  " . $tody_time  . " AND `sfl_relation_id` NOT IN ('100183548','100183549')");
            $sendid = $mysql_connect->query("SELECT `id` FROM yx_sfl_multimedia_message WHERE `create_time` >  " . $tody_time  . "  ");
            // $sendid = $mysql_connect->query("SELECT `id` FROM yx_sfl_multimedia_message WHERE  `sfl_relation_id`  = 100184671 ");
            foreach ($sendid as $key => $value) {
                $this->redis->rpush('index:meassage:sflmulmessage:sendtask', $value['id']);
            }

            while (true) {
                $task_id = $this->redis->lpop('index:meassage:sflmulmessage:sendtask');
                if (empty($task_id)) {
                    break;
                }
                $ids[] = $task_id;
                $j++;
                if ($j > 100) {
                    $all_send_task = [];
                    $all_send_task = $mysql_connect->query("SELECT *  FROM yx_sfl_multimedia_message WHERE `id` IN (" . join(',', $ids) . ") ");
                    foreach ($all_send_task as $key => $value) {
                        /* if (in_array(trim($value['mobile']), $white_list)) {
                            continue;
                        } */
                        if (!$value['yidong_channel_id'] || !$value['liantong_channel_id'] || !$value['dianxin_channel_id']) {
                            continue;
                        }
                        if (checkMobile($value['mobile']) != false) {
                            $end_num = substr($value['mobile'], -6);
                            //按无效号码计算
                            if (in_array($end_num, ['000000', '111111', '222222', '333333', '444444', '555555', '666666', '777777', '888888', '999999'])) {
                                $rece = [];
                                $rece = [
                                    'id' => $receipt_id,
                                    'mseeage_id'      => $value['mseeage_id'],
                                    'template_id'      => $value['sfl_relation_id'],
                                    'task_id' => $value['id'],
                                    'mobile' => $value['mobile'],
                                    'messageinfo' => '发送失败',
                                    'status_message' => 'MMS:2',
                                ];
                                $receipt[] = $rece;
                                $receipt_id++;
                            } else {
                                if ($deduct  == 1) {
                                    //按无效号码计算
                                    $num = mt_rand(0, 100);
                                    if ($value['sfl_relation_id'] == '100180028') {
                                        // print_r(1);die;

                                        if ($num >= 40 || in_array(trim($value['mobile']), $white_list) || $value['sfl_relation_id'] != '100180389') {
                                            $prefix = '';
                                            $prefix = substr(trim($value['mobile']), 0, 7);
                                            $res    = Db::query("SELECT `source`,`province_id`,`province` FROM `yx_number_source` WHERE `mobile` = '" . $prefix . "'");
                                            // print_r($res);
                                            if ($res) {
                                                $newres = array_shift($res);
                                                if ($newres['source'] == 1) {
                                                    $channel_id = $value['yidong_channel_id'];
                                                } elseif ($newres['source'] == 2) {
                                                    $channel_id = $value['liantong_channel_id'];
                                                } elseif ($newres['source'] == 3) {
                                                    $channel_id = $value['dianxin_channel_id'];
                                                }
                                            }
                                            $mul      = $mysql_connect->query("SELECT *  FROM yx_sfl_multimedia_template WHERE `sfl_relation_id` = '" . $value['sfl_relation_id'] . "' LIMIT 1");
                                            // $content_data             = $mysql_connect->query("select `id`,`content`,`num`,`image_path`,`image_type` from yx_user_multimedia_message_frame where delete_time=0 and `multimedia_message_id` = " . $sendTask['id'] . "  ORDER BY `num` ASC ");

                                            $fram     = $mysql_connect->query("SELECT `id`,`content`,`num`,`image_path`,`image_type` FROM yx_sfl_multimedia_template_frame WHERE `sfl_multimedia_template_id` = '" . $mul[0]['id'] . "'");
                                            $variable = json_decode($value['variable'], true);
                                            foreach ($fram as $fkey => $fvalue) {
                                                if (!empty($fvalue['content'])) {
                                                    foreach ($variable as $vkey => $val) {
                                                        $fram[$fkey]['content'] = str_replace($vkey, $val, $fram[$fkey]['content']);
                                                    }
                                                }
                                            }

                                            $sendmessage = [
                                                'mobile'      => $value['mobile'],
                                                'title'       => $mul[0]['title'],
                                                'mar_task_id' => $value['id'],
                                                'content'     => $fram,
                                                'channel_id'     => $channel_id,
                                            ];
                                            $send_msg[] = $sendmessage;
                                            // $res = $this->redis->rpush('index:meassage:code:send' . ":" . $channel_id, json_encode()); //三体营销通道
                                            //正常发送
                                        } else {
                                            $rece = [];
                                            $rece = [
                                                'id' => $receipt_id,
                                                'task_id' => $value['id'],
                                                'mseeage_id'      => $value['mseeage_id'],
                                                'template_id'      => $value['sfl_relation_id'],
                                                'mobile' => $value['mobile'],
                                                'messageinfo' => '发送成功',
                                                'status_message' => 'MMS:1',
                                            ];
                                            $receipt[] = $rece;
                                            $receipt_id++;
                                        }
                                    } else {
                                        //       print_r($num);die;
                                        /* if (in_array(trim($value['mobile']), $fault) || in_array(trim($value['mobile']), $bir)) {
                                            continue;
                                        } */
                                        if ($value['sfl_relation_id'] == '100182611' || $value['sfl_relation_id'] == '1' || $value['sfl_relation_id'] == '100182624' ||  $value['sfl_relation_id'] == '100183638') {
                                            $prefix = '';
                                            $prefix = substr(trim($value['mobile']), 0, 7);
                                            $res    = Db::query("SELECT `source`,`province_id`,`province` FROM `yx_number_source` WHERE `mobile` = '" . $prefix . "'");
                                            // print_r($res);
                                            if ($res) {
                                                $newres = array_shift($res);
                                                if ($newres['source'] == 1) {
                                                    $channel_id = $value['yidong_channel_id'];
                                                } elseif ($newres['source'] == 2) {
                                                    $channel_id = $value['liantong_channel_id'];
                                                } elseif ($newres['source'] == 3) {
                                                    $channel_id = $value['dianxin_channel_id'];
                                                }
                                            }
                                            $mul      = $mysql_connect->query("SELECT *  FROM yx_sfl_multimedia_template WHERE `sfl_relation_id` = '" . $value['sfl_relation_id'] . "' LIMIT 1");
                                            // $content_data             = $mysql_connect->query("select `id`,`content`,`num`,`image_path`,`image_type` from yx_user_multimedia_message_frame where delete_time=0 and `multimedia_message_id` = " . $sendTask['id'] . "  ORDER BY `num` ASC ");

                                            $fram     = $mysql_connect->query("SELECT `id`,`content`,`num`,`image_path`,`image_type` FROM yx_sfl_multimedia_template_frame WHERE `sfl_multimedia_template_id` = '" . $mul[0]['id'] . "'");
                                            $variable = json_decode($value['variable'], true);
                                            foreach ($fram as $fkey => $fvalue) {
                                                if (!empty($fvalue['content'])) {
                                                    foreach ($variable as $vkey => $val) {
                                                        $fram[$fkey]['content'] = str_replace($vkey, $val, $fram[$fkey]['content']);
                                                    }
                                                }
                                            }

                                            $sendmessage = [
                                                'mobile'      => $value['mobile'],
                                                'title'       => $mul[0]['title'],
                                                'mar_task_id' => $value['id'],
                                                'content'     => $fram,
                                                'channel_id'     => $channel_id,
                                            ];
                                            $send_msg[] = $sendmessage;
                                        } else {

                                            if ($num >= $rate || in_array(trim($value['mobile']), $white_list)) {
                                                $prefix = '';
                                                $prefix = substr(trim($value['mobile']), 0, 7);
                                                $res    = Db::query("SELECT `source`,`province_id`,`province` FROM `yx_number_source` WHERE `mobile` = '" . $prefix . "'");
                                                // print_r($res);
                                                if ($res) {
                                                    $newres = array_shift($res);
                                                    if ($newres['source'] == 1) {
                                                        $channel_id = $value['yidong_channel_id'];
                                                    } elseif ($newres['source'] == 2) {
                                                        $channel_id = $value['liantong_channel_id'];
                                                    } elseif ($newres['source'] == 3) {
                                                        $channel_id = $value['dianxin_channel_id'];
                                                    }
                                                }
                                                $mul      = $mysql_connect->query("SELECT *  FROM yx_sfl_multimedia_template WHERE `sfl_relation_id` = '" . $value['sfl_relation_id'] . "' LIMIT 1");
                                                // $content_data             = $mysql_connect->query("select `id`,`content`,`num`,`image_path`,`image_type` from yx_user_multimedia_message_frame where delete_time=0 and `multimedia_message_id` = " . $sendTask['id'] . "  ORDER BY `num` ASC ");

                                                $fram     = $mysql_connect->query("SELECT `id`,`content`,`num`,`image_path`,`image_type` FROM yx_sfl_multimedia_template_frame WHERE `sfl_multimedia_template_id` = '" . $mul[0]['id'] . "'");
                                                $variable = json_decode($value['variable'], true);
                                                foreach ($fram as $fkey => $fvalue) {
                                                    if (!empty($fvalue['content'])) {
                                                        foreach ($variable as $vkey => $val) {
                                                            $fram[$fkey]['content'] = str_replace($vkey, $val, $fram[$fkey]['content']);
                                                        }
                                                    }
                                                }

                                                $sendmessage = [
                                                    'mobile'      => $value['mobile'],
                                                    'title'       => $mul[0]['title'],
                                                    'mar_task_id' => $value['id'],
                                                    'content'     => $fram,
                                                    'channel_id'     => $channel_id,
                                                ];
                                                $send_msg[] = $sendmessage;
                                                // $res = $this->redis->rpush('index:meassage:code:send' . ":" . $channel_id, json_encode()); //三体营销通道
                                                //正常发送
                                            } else {
                                                $rece = [];
                                                $rece = [
                                                    'id' => $receipt_id,
                                                    'task_id' => $value['id'],
                                                    'mseeage_id'      => $value['mseeage_id'],
                                                    'template_id'      => $value['sfl_relation_id'],
                                                    'mobile' => $value['mobile'],
                                                    'messageinfo' => '发送成功',
                                                    'status_message' => 'MMS:1',
                                                ];
                                                $receipt[] = $rece;
                                                $receipt_id++;
                                            }
                                        }
                                    }
                                } else {
                                    $prefix = '';
                                    $prefix = substr(trim($value['mobile']), 0, 7);
                                    $res    = Db::query("SELECT `source`,`province_id`,`province` FROM `yx_number_source` WHERE `mobile` = '" . $prefix . "'");
                                    // print_r($res);
                                    if ($res) {
                                        $newres = array_shift($res);
                                        if ($newres['source'] == 1) {
                                            $channel_id = $value['yidong_channel_id'];
                                        } elseif ($newres['source'] == 2) {
                                            $channel_id = $value['liantong_channel_id'];
                                        } elseif ($newres['source'] == 3) {
                                            $channel_id = $value['dianxin_channel_id'];
                                        }
                                    }
                                    $mul      = $mysql_connect->query("SELECT *  FROM yx_sfl_multimedia_template WHERE `sfl_relation_id` = '" . $value['sfl_relation_id'] . "' LIMIT 1");
                                    // $content_data             = $mysql_connect->query("select `id`,`content`,`num`,`image_path`,`image_type` from yx_user_multimedia_message_frame where delete_time=0 and `multimedia_message_id` = " . $sendTask['id'] . "  ORDER BY `num` ASC ");

                                    $fram     = $mysql_connect->query("SELECT `id`,`content`,`num`,`image_path`,`image_type` FROM yx_sfl_multimedia_template_frame WHERE `sfl_multimedia_template_id` = '" . $mul[0]['id'] . "'");
                                    $variable = json_decode($value['variable'], true);
                                    foreach ($fram as $fkey => $fvalue) {
                                        if (!empty($fvalue['content'])) {
                                            foreach ($variable as $vkey => $val) {
                                                $fram[$fkey]['content'] = str_replace($vkey, $val, $fram[$fkey]['content']);
                                            }
                                        }
                                    }

                                    $sendmessage = [
                                        'mobile'      => $value['mobile'],
                                        'title'       => $mul[0]['title'],
                                        'mar_task_id' => $value['id'],
                                        'content'     => $fram,
                                        'channel_id'     => $channel_id,
                                    ];
                                    $send_msg[] = $sendmessage;
                                }
                            }
                        } else {
                            $rece = [];
                            $rece = [
                                'id' => $receipt_id,
                                'mseeage_id'      => $value['mseeage_id'],
                                'template_id'      => $value['sfl_relation_id'],
                                'task_id' => $value['id'],
                                'mobile' => $value['mobile'],
                                'messageinfo' => '发送失败',
                                'status_message' => 'MMS:2',
                            ];

                            $receipt[] = $rece;
                            $receipt_id++;
                            // $mysql_connect->table('yx_sfl_send_multimediatask_receipt')->insert();
                        }
                    }
                    if (!empty($receipt)) {
                        // print_r($receipt);
                        /*   foreach($receipt as $rkey => $rva){
                            // print_r($rva);
                            $mysql_connect->table('yx_sfl_send_multimediatask_receipt')->insert($rva);
                        } */
                        $mysql_connect->table('yx_sfl_send_multimediatask_receipt')->insertAll($receipt);
                    }
                    if (!empty($send_msg)) {
                        foreach ($send_msg as $skey => $svalue) {
                            $channel_id = $svalue['channel_id'];
                            unset($svalue['channel_id']);
                            $res = $this->redis->lpush('index:meassage:code:send' . ":" . $channel_id, json_encode($svalue)); //三体营销通道
                        }
                    }

                    unset($ids);
                    unset($receipt);
                    unset($send_msg);
                    // unset($all_send_task);
                }
            }

            if (!empty($ids)) {
                $all_send_task = $mysql_connect->query("SELECT *  FROM yx_sfl_multimedia_message WHERE `id` IN (" . join(',', $ids) . ") ");
                foreach ($all_send_task as $key => $value) {
                    if (!$value['yidong_channel_id'] || !$value['liantong_channel_id'] || !$value['dianxin_channel_id']) {
                        continue;
                    }
                    /* if (in_array(trim($value['mobile']), $white_list)) {
                        continue;
                    } */
                    if (checkMobile($value['mobile']) != false) {
                        //按无效号码计算
                        $end_num = substr($value['mobile'], -6);
                        //按无效号码计算
                        if (in_array($end_num, ['000000', '111111', '222222', '333333', '444444', '555555', '666666', '777777', '888888', '999999'])) {
                            $rece = [];
                            $rece = [
                                'id' => $receipt_id,
                                'mseeage_id'      => $value['mseeage_id'],
                                'template_id'      => $value['sfl_relation_id'],
                                'task_id' => $value['id'],
                                'mobile' => $value['mobile'],
                                'messageinfo' => '发送失败',
                                'status_message' => 'SMS:2',
                            ];
                            $receipt[] = $rece;
                        } else {
                            if ($deduct  == 1) {
                                $num = mt_rand(0, 100);
                                if ($value['sfl_relation_id'] == '100180028') {
                                    if ($num >= 40 || in_array(trim($value['mobile']), $white_list)) {
                                        $prefix = '';
                                        $prefix = substr(trim($value['mobile']), 0, 7);
                                        $res    = Db::query("SELECT `source`,`province_id`,`province` FROM `yx_number_source` WHERE `mobile` = '" . $prefix . "'");
                                        // print_r($res);
                                        if ($res) {
                                            $newres = array_shift($res);
                                            if ($newres['source'] == 1) {
                                                $channel_id = $value['yidong_channel_id'];
                                            } elseif ($newres['source'] == 2) {
                                                $channel_id = $value['liantong_channel_id'];
                                            } elseif ($newres['source'] == 3) {
                                                $channel_id = $value['dianxin_channel_id'];
                                            }
                                        }
                                        $mul      = $mysql_connect->query("SELECT *  FROM yx_sfl_multimedia_template WHERE `sfl_relation_id` = '" . $value['sfl_relation_id'] . "' LIMIT 1");
                                        // $content_data             = $mysql_connect->query("select `id`,`content`,`num`,`image_path`,`image_type` from yx_user_multimedia_message_frame where delete_time=0 and `multimedia_message_id` = " . $sendTask['id'] . "  ORDER BY `num` ASC ");

                                        $fram     = $mysql_connect->query("SELECT `id`,`content`,`num`,`image_path`,`image_type` FROM yx_sfl_multimedia_template_frame WHERE `sfl_multimedia_template_id` = '" . $mul[0]['id'] . "'");
                                        $variable = json_decode($value['variable'], true);
                                        foreach ($fram as $fkey => $fvalue) {
                                            if (!empty($fvalue['content'])) {
                                                foreach ($variable as $vkey => $val) {
                                                    $fram[$fkey]['content'] = str_replace($vkey, $val, $fram[$fkey]['content']);
                                                }
                                            }
                                        }

                                        $sendmessage = [
                                            'mobile'      => $value['mobile'],
                                            'title'       => $mul[0]['title'],
                                            'mar_task_id' => $value['id'],
                                            'content'     => $fram,
                                            'channel_id'     => $channel_id,
                                        ];
                                        $send_msg[] = $sendmessage;
                                        // $res = $this->redis->rpush('index:meassage:code:send' . ":" . $channel_id, json_encode()); //三体营销通道
                                        //正常发送
                                    } else {
                                        $rece = [];
                                        $rece = [
                                            'id' => $receipt_id,
                                            'task_id' => $value['id'],
                                            'mseeage_id'      => $value['mseeage_id'],
                                            'template_id'      => $value['sfl_relation_id'],
                                            'mobile' => $value['mobile'],
                                            'messageinfo' => '发送成功',
                                            'status_message' => 'MMS:1',
                                        ];
                                        $receipt[] = $rece;
                                    }
                                } else {
                                    if ($value['sfl_relation_id'] == '100182611' || $value['sfl_relation_id'] == '1' || $value['sfl_relation_id'] == '100182624'  ||  $value['sfl_relation_id'] == '100183638') {
                                        $prefix = '';
                                        $prefix = substr(trim($value['mobile']), 0, 7);
                                        $res    = Db::query("SELECT `source`,`province_id`,`province` FROM `yx_number_source` WHERE `mobile` = '" . $prefix . "'");
                                        // print_r($res);
                                        if ($res) {
                                            $newres = array_shift($res);
                                            if ($newres['source'] == 1) {
                                                $channel_id = $value['yidong_channel_id'];
                                            } elseif ($newres['source'] == 2) {
                                                $channel_id = $value['liantong_channel_id'];
                                            } elseif ($newres['source'] == 3) {
                                                $channel_id = $value['dianxin_channel_id'];
                                            }
                                        }
                                        $mul      = $mysql_connect->query("SELECT *  FROM yx_sfl_multimedia_template WHERE `sfl_relation_id` = '" . $value['sfl_relation_id'] . "' LIMIT 1");
                                        // $content_data             = $mysql_connect->query("select `id`,`content`,`num`,`image_path`,`image_type` from yx_user_multimedia_message_frame where delete_time=0 and `multimedia_message_id` = " . $sendTask['id'] . "  ORDER BY `num` ASC ");

                                        $fram     = $mysql_connect->query("SELECT `id`,`content`,`num`,`image_path`,`image_type` FROM yx_sfl_multimedia_template_frame WHERE `sfl_multimedia_template_id` = '" . $mul[0]['id'] . "'");
                                        $variable = json_decode($value['variable'], true);
                                        foreach ($fram as $fkey => $fvalue) {
                                            if (!empty($fvalue['content'])) {
                                                foreach ($variable as $vkey => $val) {
                                                    $fram[$fkey]['content'] = str_replace($vkey, $val, $fram[$fkey]['content']);
                                                }
                                            }
                                        }

                                        $sendmessage = [
                                            'mobile'      => $value['mobile'],
                                            'title'       => $mul[0]['title'],
                                            'mar_task_id' => $value['id'],
                                            'content'     => $fram,
                                            'channel_id'     => $channel_id,
                                        ];
                                        $send_msg[] = $sendmessage;
                                    } else {
                                        if ($num >= $rate || in_array(trim($value['mobile']), $white_list)) {
                                            $prefix = '';
                                            $prefix = substr(trim($value['mobile']), 0, 7);
                                            $res    = Db::query("SELECT `source`,`province_id`,`province` FROM `yx_number_source` WHERE `mobile` = '" . $prefix . "'");
                                            // print_r($res);
                                            if ($res) {
                                                $newres = array_shift($res);
                                                if ($newres['source'] == 1) {
                                                    $channel_id = $value['yidong_channel_id'];
                                                } elseif ($newres['source'] == 2) {
                                                    $channel_id = $value['liantong_channel_id'];
                                                } elseif ($newres['source'] == 3) {
                                                    $channel_id = $value['dianxin_channel_id'];
                                                }
                                            }
                                            $mul      = $mysql_connect->query("SELECT *  FROM yx_sfl_multimedia_template WHERE `sfl_relation_id` = '" . $value['sfl_relation_id'] . "' LIMIT 1");
                                            // $content_data             = $mysql_connect->query("select `id`,`content`,`num`,`image_path`,`image_type` from yx_user_multimedia_message_frame where delete_time=0 and `multimedia_message_id` = " . $sendTask['id'] . "  ORDER BY `num` ASC ");

                                            $fram     = $mysql_connect->query("SELECT `id`,`content`,`num`,`image_path`,`image_type` FROM yx_sfl_multimedia_template_frame WHERE `sfl_multimedia_template_id` = '" . $mul[0]['id'] . "'");
                                            $variable = json_decode($value['variable'], true);
                                            foreach ($fram as $fkey => $fvalue) {
                                                if (!empty($fvalue['content'])) {
                                                    foreach ($variable as $vkey => $val) {
                                                        $fram[$fkey]['content'] = str_replace($vkey, $val, $fram[$fkey]['content']);
                                                    }
                                                }
                                            }

                                            $sendmessage = [
                                                'mobile'      => $value['mobile'],
                                                'title'       => $mul[0]['title'],
                                                'mar_task_id' => $value['id'],
                                                'content'     => $fram,
                                                'channel_id'     => $channel_id,
                                            ];
                                            $send_msg[] = $sendmessage;
                                            // $res = $this->redis->rpush('index:meassage:code:send' . ":" . $channel_id, json_encode()); //三体营销通道
                                            //正常发送
                                        } else {
                                            $rece = [];
                                            $rece = [
                                                'id' => $receipt_id,
                                                'task_id' => $value['id'],
                                                'mseeage_id'      => $value['mseeage_id'],
                                                'template_id'      => $value['sfl_relation_id'],
                                                'mobile' => $value['mobile'],
                                                'messageinfo' => '发送成功',
                                                'status_message' => 'MMS:1',
                                            ];
                                            $receipt[] = $rece;
                                        }
                                    }
                                }
                                /*   if ($num >= $rate || in_array(trim($value['mobile']), $white_list)) {
                                    $prefix = '';
                                    $prefix = substr(trim($value['mobile']), 0, 7);
                                    $res    = Db::query("SELECT `source`,`province_id`,`province` FROM `yx_number_source` WHERE `mobile` = '" . $prefix . "'");
                                    // print_r($res);
                                    if ($res) {
                                        $newres = array_shift($res);
                                        if ($newres['source'] == 1) {
                                            $channel_id = $value['yidong_channel_id'];
                                        } elseif ($newres['source'] == 2) {
                                            $channel_id = $value['liantong_channel_id'];
                                        } elseif ($newres['source'] == 3) {
                                            $channel_id = $value['dianxin_channel_id'];
                                        }
                                    }
                                    $mul      = $mysql_connect->query("SELECT *  FROM yx_sfl_multimedia_template WHERE `sfl_relation_id` = '" . $value['sfl_relation_id'] . "' LIMIT 1");
                                    // $content_data             = $mysql_connect->query("select `id`,`content`,`num`,`image_path`,`image_type` from yx_user_multimedia_message_frame where delete_time=0 and `multimedia_message_id` = " . $sendTask['id'] . "  ORDER BY `num` ASC ");
    
                                    $fram     = $mysql_connect->query("SELECT `id`,`content`,`num`,`image_path`,`image_type` FROM yx_sfl_multimedia_template_frame WHERE `sfl_multimedia_template_id` = '" . $mul[0]['id'] . "'");
                                    $variable = json_decode($value['variable'], true);
                                    foreach ($fram as $fkey => $fvalue) {
                                        if (!empty($fvalue['content'])) {
                                            foreach ($variable as $vkey => $val) {
                                                $fram[$fkey]['content'] = str_replace($vkey, $val, $fram[$fkey]['content']);
                                            }
                                        }
                                    }
    
                                    $sendmessage = [
                                        'mobile'      => $value['mobile'],
                                        'title'       => $mul[0]['title'],
                                        'mar_task_id' => $value['id'],
                                        'content'     => $fram,
                                        'channel_id'     => $channel_id,
                                    ];
                                    $send_msg[] = $sendmessage;
                                    // $res = $this->redis->rpush('index:meassage:code:send' . ":" . $channel_id, json_encode()); //三体营销通道
                                    //正常发送
                                } else {
                                    $rece = [];
                                    $rece = [
                                        'task_id' => $value['id'],
                                        'mseeage_id'      => $value['mseeage_id'],
                                        'template_id'      => $value['sfl_relation_id'],
                                        'mobile' => $value['mobile'],
                                        'messageinfo' => '发送成功',
                                        'status_message' => 'SMS:1',
                                    ];
                                    $receipt[] = $rece;
                                } */
                            } else {
                                $prefix = '';
                                $prefix = substr(trim($value['mobile']), 0, 7);
                                $res    = Db::query("SELECT `source`,`province_id`,`province` FROM `yx_number_source` WHERE `mobile` = '" . $prefix . "'");
                                // print_r($res);
                                if ($res) {
                                    $newres = array_shift($res);
                                    if ($newres['source'] == 1) {
                                        $channel_id = $value['yidong_channel_id'];
                                    } elseif ($newres['source'] == 2) {
                                        $channel_id = $value['liantong_channel_id'];
                                    } elseif ($newres['source'] == 3) {
                                        $channel_id = $value['dianxin_channel_id'];
                                    }
                                }
                                $mul      = $mysql_connect->query("SELECT *  FROM yx_sfl_multimedia_template WHERE `sfl_relation_id` = '" . $value['sfl_relation_id'] . "' LIMIT 1");
                                // $content_data             = $mysql_connect->query("select `id`,`content`,`num`,`image_path`,`image_type` from yx_user_multimedia_message_frame where delete_time=0 and `multimedia_message_id` = " . $sendTask['id'] . "  ORDER BY `num` ASC ");

                                $fram     = $mysql_connect->query("SELECT `id`,`content`,`num`,`image_path`,`image_type` FROM yx_sfl_multimedia_template_frame WHERE `sfl_multimedia_template_id` = '" . $mul[0]['id'] . "'");
                                $variable = json_decode($value['variable'], true);
                                foreach ($fram as $fkey => $fvalue) {
                                    if (!empty($fvalue['content'])) {
                                        foreach ($variable as $vkey => $val) {
                                            $fram[$fkey]['content'] = str_replace($vkey, $val, $fram[$fkey]['content']);
                                        }
                                    }
                                }

                                $sendmessage = [
                                    'mobile'      => $value['mobile'],
                                    'title'       => $mul[0]['title'],
                                    'mar_task_id' => $value['id'],
                                    'content'     => $fram,
                                    'channel_id'     => $channel_id,
                                ];
                                $send_msg[] = $sendmessage;
                            }
                        }
                    } else {
                        $rece = [];
                        $rece = [
                            'id' => $receipt_id,
                            'mseeage_id'      => $value['mseeage_id'],
                            'template_id'      => $value['sfl_relation_id'],
                            'task_id' => $value['id'],
                            'mobile' => $value['mobile'],
                            'messageinfo' => '发送失败',
                            'status_message' => 'SMS:2',
                        ];
                        $receipt[] = $rece;
                        // $mysql_connect->table('yx_sfl_send_multimediatask_receipt')->insert();
                    }
                }
                if (!empty($receipt)) {
                    // print_r($receipt);die;
                    $mysql_connect->table('yx_sfl_send_multimediatask_receipt')->insertAll($receipt);
                }
                if (!empty($send_msg)) {
                    foreach ($send_msg as $skey => $svalue) {
                        $channel_id = $svalue['channel_id'];
                        unset($svalue['channel_id']);
                        $res = $this->redis->lpush('index:meassage:code:send' . ":" . $channel_id, json_encode($svalue)); //三体营销通道
                    }
                }
                unset($ids);
                unset($receipt);
                unset($send_msg);
            }
        } catch (\Exception $th) {
            exception($th);
        }
        /* while(true){
           
            sleep(10);
        } */
    }

    public function SFLmultask()
    {
        $this->redis = Phpredis::getConn();
        $mysql_connect = Db::connect(Config::get('database.db_sflsftp'));
        $mysql_connect->query("set names utf8mb4");
        $oppen_shop = [];
        $bir = [];
        $this->redis->rpush("index:meassage:code:send:94", '{"mobile":"15811252512","title":"\u6765\u81ea\u3010\u4e1d\u8299\u5170\u3011\uff1a\u795d\u60a8\u751f\u65e5\u5feb\u4e50\uff01\u5feb\u6765\u9886\u53d6\u4e09\u91cd\u751f\u65e5\u8c6a\u793c\uff0c\u4eab\u53d7\u751f\u65e5\u559c\u60a6\uff01","mar_task_id":215551,"content":[{"id":26,"content":null,"num":1,"image_path":"20200522\/36281dfe70ba464f5987fc0073025a105ec761bd5fdf8.jpg","image_type":""},{"id":27,"content":"\u3010\u4e1d\u8299\u5170\u3011\u795d\u60a8\u751f\u65e5\u5feb\u4e50\uff01\u5feb\u6765\u9886\u53d6\u4e09\u91cd\u751f\u65e5\u8c6a\u793c\uff0c\u4eab\u53d7\u751f\u65e5\u559c\u60a6\uff01\r\n\r\n\u5c0a\u8d35\u7684\u91d1\u5361\u4f1a\u5458\u674e\u654f\uff0c\r\n\r\n\u4e13\u5c5e\u5ba0\u7231\uff0c\u4e0d\u8d1f\u671f\u5f85\uff0c\u4e1d\u8299\u5170\u4e3a\u60a8\u4e0a\u6f14 \u201c\u751f\u65e5\u5c0a\u4eab\u793c\u201d\u4e09\u91cd\u594f\uff0c\u53ea\u4e3a\u6700\u7279\u522b\u7684\u60a8\u3002\r\n\r\n\u4e00\u91cd\u594f:\u3010\u4ef7\u503c320\u5143\u751f\u65e5\u793c\u76d2\u3011\u56db\u5927\u54c1\u724c\u793c\u7269\u4efb\u9009\u5176\u4e00\uff08\u4e24\u4e24\u3001\u6b27\u7f07\u4e3d\u3001\u851a\u84dd\u4e4b\u7f8e\u3001\u739b\u4e3d\u9edb\u4f73\uff09\u3002\r\n","num":2,"image_path":"","image_type":""},{"id":28,"content":null,"num":3,"image_path":"20200522\/86c75c26b9f32b45559807db9dc2adc25ec761e4486aa.jpg","image_type":""},{"id":29,"content":"\u4e8c\u91cd\u594f: \u3010\u4e1d\u8299\u5170100\u5143\u7535\u5b50\u5238\u3011\u6d88\u8d39\u6ee1101\u5143\u53ef\u7528\u3002\r\n\u4e09\u91cd\u594f:\u751f\u65e5\u6708\u8ba2\u5355\u4eab\u53d7\u4e00\u6b21\u53cc\u500d\u79ef\u5206\u793c\u9047\u3002\u751f\u65e5\u5927\u653e\u201c\u4ef7\u201d\uff0c\u7279\u6743\u6765\u88ad\uff0c\u4e0d\u5bb9\u9519\u8fc7\u3002\r\n\r\n\u8bf7\u4e8e2020-07-11\u524d\u4e1d\u8299\u5170\u95e8\u5e97\u548c\u5b98\u7f51 sephora.cn\u3001APP\u3001\u5c0f\u7a0b\u5e8f\u9886\u53d6\u5e76\u4f7f\u7528\u60a8\u7684\u4e13\u5c5e\u793c\u7269\u54e6\uff01\r\n\r\n\u4ee5\u4e0a\u4e09\u91cd\u751f\u65e5\u793c\uff0c\u7686\u4e0d\u4e0e\u5176\u4ed6\u4f18\u60e0\u53e0\u52a0\u4f7f\u7528\u3002\r\n\r\n\uff0d\uff0d\uff0d\uff0d\uff0d\uff0d\uff0d\uff0d\uff0d\uff0d\r\nSEPHORA\u5ba2\u670d\u70ed\u7ebf400-670-0055\r\nSEPHORA\u5b98\u7f51\uff1a www.sephora.cn\r\n\u7f16\u8f91\u77ed\u4fe1TD\u56de\u590d\u81f3\u672c\u53f7\u7801\uff0c\u5373\u53ef\u53d6\u6d88\u8d60\u9605\u3010SEPHORA\u3011\r\n","num":4,"image_path":"","image_type":""}]}');
        $all = [];
        try {
            while (true) {
                $mul_task = $this->redis->lpop("index:meassage:code:send:94");
                if (empty($mul_task)) {
                    break;
                }
                $all[] = $mul_task;
                $mul_send_task = json_decode($mul_task, true);
                print_r($mul_send_task);
                die;
                print_r($mul_task);
                if (!strpos('IAPM环贸广场店', $mul_send_task['title'])) {
                    $oppen_shop[] = $mul_send_task['mobile'];
                } else {
                    $bir[] = $mul_task;
                }
            }
            $all_path = realpath("./") . "/052901.txt";
            $file = fopen($all_path, "w");
            // $data = array();
            foreach ($oppen_shop as $key => $value) {
                fwrite($file, $value . "\n");
            }
            fclose($file);
            foreach ($bir as $key => $value) {
                $this->redis->rpush("index:meassage:code:send:94", $value);
            }
        } catch (\Exception $th) {
            foreach ($all as $key => $value) {
                $this->redis->rpush("index:meassage:code:send:94", $value);
            }
            exception($th);
        }
    }


    /* 丝芙兰未知补推 */
    public function SflUnknownReceipt()
    {

        $redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        while (true) {
            try {
                $end_time   = strtotime("-3 day");
                $taskcode = Db::query("SELECT `id` FROM yx_user_send_code_task WHERE `uid` = '91' AND `create_time` >= 1588262400 AND `create_time` <= " . $end_time);
                $ids = [];
                foreach ($taskcode as $key => $value) {
                    $ids[] = $value['id'];
                }
                $receipts_id = [];
                // echo "SELECT `task_id` FROM yx_send_code_task_receipt WHERE `task_id` IN (".join(',',$ids).") GROUP BY `task_id`";die;
                $task_receipt = Db::query("SELECT `task_id` FROM yx_send_code_task_receipt WHERE `task_id` IN (" . join(',', $ids) . ") GROUP BY `task_id`");
                foreach ($task_receipt as $key => $value) {
                    $receipts_id[] = $value['task_id'];
                }
                $unknow = array_diff($ids, $receipts_id);
                if (empty($unknow)) {
                    sleep(600);
                    continue;
                }
                echo "总数:" . count($ids) . "\n";
                echo "已回:" . count($receipts_id) . "\n";
                echo "未知:" . count($unknow) . "\n";
                $unknow_task = Db::query("SELECT `id`,`task_no`,`mobile_content`,`create_time` FROM yx_user_send_code_task WHERE `id` IN (" . join(',', $unknow) . ") ");
                foreach ($unknow_task as $key => $value) {
                    $task_receipt_log = [];
                    $receive_time = $value['create_time'] + 3600 * 72 - mt_rand(0, 1800);
                    $task_receipt_log = [
                        'task_id' => $value['id'],
                        'mobile' => $value['mobile_content'],
                        'status_message' => 'DELIVRD',
                        'create_time' => $receive_time,
                    ];
                    Db::table('yx_send_code_task_receipt')->insert($task_receipt_log);
                    $redis->rpush('index:meassage:code:user:receive:91', json_encode([
                        'task_no'        => trim($value['task_no']),
                        'status_message' => 'DELIVRD',
                        'message_info'   => '发送成功',
                        'mobile'         => trim($value['mobile_content']),
                        // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                        'send_time'      => date('Y-m-d H:i:s', $receive_time),
                    ])); //写入用户带处理日志
                }
            } catch (\Exception $th) {
                exception($th);
            }
        }
    }

    /* 美田回执推送 */
    public function MltyReceipt()
    {
        $task_log = Db::query("SELECT `*` FROM yx_user_send_code_task_log WHERE `channel_id` = '61' AND `uid` = '47' AND `create_time` >= 1589864233 AND `create_time` <= 1589876607 ");
        foreach ($task_log as $key => $value) {
            $request_url = "http://116.228.60.189:15901/rtreceive?";
            $request_url .= 'task_no=' . trim($value['task_no']) . "&status_message=" . "DELIVRD" . "&mobile=" . trim($value['mobile']) . "&send_time=" . trim(date('YmdHis', time() - mt_rand(0, 500)));
            // print_r($request_url);
            sendRequest($request_url);
            Db::startTrans();
            try {
                Db::table('yx_user_send_code_task_log')->where('id', $value['id'])->update(['status_message' => 'DELIVRD', 'send_status' => 3, 'update_time' => time()]);
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                exception($e);
            }
        }
    }

    public function sflSftpMulTaskReceipt()
    {
        $this->redis = Phpredis::getConn();
        $mysql_connect = Db::connect(Config::get('database.db_sflsftp'));
        $mysql_connect->query("set names utf8mb4");
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        $mul_receipt_key = 'index:meassage:multimediamessage:deliver:94';
        $j = 1;
        $commit_tobase = [];
        $back = [];
        while (true) {
            while (true) {
                $receipt = $this->redis->lpop($mul_receipt_key);
                if (empty($receipt)) {
                    break;
                }
                $receipts = json_decode($receipt, true);
                if (empty($receipts['mseeage_id'])) {
                    continue;
                }
                if (trim($receipts['mobile']) == '15201926171' || trim($receipts['mobile']) == '15821193682') {
                    continue;
                }
                $commit = [];
                $mul_task = $mysql_connect->query("SELECT `id`,`sfl_relation_id` FROM yx_sfl_multimedia_message WHERE `mseeage_id` =  " . trim($receipts['mseeage_id'] . " LIMIT 1"));
                $commit = [
                    'mseeage_id' => $receipts['mseeage_id'],
                    'mobile' => $receipts['mobile'],
                    'real_message' => $receipts['status_message'],
                    'task_id' => $mul_task[0]['id'],
                    'template_id' => $mul_task[0]['sfl_relation_id'],
                ];
                if ($receipts['status_message'] == 'DELIVRD') {
                    $commit['status_message'] = "MMS:1";
                    $commit['messageinfo'] = "发送成功";
                } else {
                    $commit['status_message'] = "MMS:2";
                    $commit['messageinfo'] = "发送失败";
                }
                $commit_tobase[] = $commit;
                $back[] = $receipt;
                $j++;
                if ($j > 100) {
                    $mysql_connect->startTrans();
                    try {
                        $mysql_connect->table('yx_sfl_send_multimediatask_receipt')->insertAll($commit_tobase);
                        $mysql_connect->commit();
                        unset($commit_tobase);
                        unset($back);
                        $j = 1;
                    } catch (\Exception $e) {
                        $mysql_connect->rollback();
                        if (!empty($back)) {
                            foreach ($back as $key => $value) {
                                $this->redis->rPush($mul_receipt_key, $value);
                            }
                        }

                        exception($e);
                    }
                }
            }
            if (!empty($commit_tobase)) {
                $mysql_connect->startTrans();
                try {
                    $mysql_connect->table('yx_sfl_send_multimediatask_receipt')->insertAll($commit_tobase);
                    $mysql_connect->commit();
                    unset($commit_tobase);
                    unset($back);
                    $j = 1;
                } catch (\Exception $e) {
                    $mysql_connect->rollback();
                    if (!empty($back)) {
                        foreach ($back as $key => $value) {
                            $this->redis->rPush($mul_receipt_key, $value);
                        }
                    }
                    exception($e);
                }
            }
            sleep(300);
        }
    }

    public function sflSftpTaskReceipt($content)
    {
        $this->redis = Phpredis::getConn();
        $mysql_connect = Db::connect(Config::get('database.db_sflsftp'));
        $mysql_connect->query("set names utf8mb4");
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        $task_receipt_key = 'index:meassage:code:new:deliver:' . $content;
        $j = 1;
        $commit_tobase = [];
        $back = [];
        $j = 1;
        $commit_tobase = [];
        $back = [];
        while (true) {
            while (true) {
                $receipt = $this->redis->lpop($task_receipt_key);
                if (empty($receipt)) {
                    break;
                }
                $receipts = json_decode($receipt, true);
                if (empty($receipts['mseeage_id'])) {
                    if ($receipts['from'] == 'yx_user_send_code_task') {
                        if ($content == '83') {
                            $this->redis->rpush('index:meassage:code:new:deliver:18', json_encode($receipts));
                        } else {
                            $this->redis->rpush('index:meassage:code:new:deliver:19', json_encode($receipts));
                        }
                    }
                    continue;
                }
                /* if (empty($receipts['mar_task_id'])) {
                    continue;
                } */
                if (trim($receipts['mobile']) == '15201926171' || trim($receipts['mobile']) == '15821193682') {
                    continue;
                }
                $commit = [];
                /*  $mul_task = $mysql_connect->query("SELECT `id`,`template_id` FROM yx_sfl_send_task WHERE `mseeage_id` =  ".trim($receipts['mseeage_id']." LIMIT 1")); */
                $commit = [
                    'mseeage_id' => $receipts['mseeage_id'],
                    'mobile' => $receipts['mobile'],
                    'real_message' => $receipts['Stat'],
                    // 'task_id' => $receipts['mar_task_id'],
                    'template_id' => $receipts['template_id'],
                ];
                $receipts['Stat'] = trim($receipts['Stat']);
                if ($receipts['Stat'] == 'DELIVRD' || $receipts['Stat'] == 'MK:100D' || $receipts['Stat'] == 'DB:0141') {
                    $commit['status_message'] = "SMS:1";
                    $commit['messageinfo'] = "发送成功";
                } elseif (strpos($receipts['Stat'], 'BLACK')) {
                    $commit['status_message'] = "SMS:4";
                    $commit['messageinfo'] = "黑名单";
                } else {
                    $commit['status_message'] = "SMS:2";
                    $commit['messageinfo'] = "发送失败";
                }
                $commit_tobase[] = $commit;
                $back[] = $receipt;
                $j++;
                if ($j > 100) {
                    $mysql_connect->startTrans();
                    try {
                        $mysql_connect->table('yx_sfl_send_task_receipt')->insertAll($commit_tobase);
                        $mysql_connect->commit();
                        unset($commit_tobase);
                        unset($back);
                        $j = 1;
                    } catch (\Exception $e) {
                        $mysql_connect->rollback();
                        if (!empty($back)) {
                            foreach ($back as $key => $value) {
                                $this->redis->rPush($task_receipt_key, $value);
                            }
                        }

                        exception($e);
                    }
                }
            }
            if (!empty($commit_tobase)) {
                $mysql_connect->startTrans();
                try {
                    $mysql_connect->table('yx_sfl_send_task_receipt')->insertAll($commit_tobase);
                    $mysql_connect->commit();
                    unset($commit_tobase);
                    unset($back);
                    $j = 1;
                } catch (\Exception $e) {
                    $mysql_connect->rollback();
                    if (!empty($back)) {
                        foreach ($back as $key => $value) {
                            $this->redis->rPush($task_receipt_key, $value);
                        }
                    }
                    exception($e);
                }
            }
            sleep(300);
        }
    }

    public function json()
    {
        $string = '{"mobile":"15801432227","title":"\u6765\u81ea\u3010\u4e1d\u8299\u5170\u3011\uff1aIAPM\u73af\u8d38\u5e7f\u573a\u5e975.30\u76db\u5927\u5f00\u4e1a\uff01","mar_task_id":25714,"content":[{"id":24,"content":null,"num":1,"image_path":"20200525\/993b12e0d2a0fff8c6547527de1a40a15ecb6bf27c90d.gif","image_type":""},{"id":25,"content":"\u3010\u4e1d\u8299\u5170\u3011IAPM\u73af\u8d38\u5e7f\u573a\u5e975.30\u76db\u5927\u5f00\u4e1a\uff01\n\n\u4e94\u91cd\u793c\u9047\uff0c\u9080\u60a8\u5c0a\u4eab\uff01\n\n1.\u5f00\u4e1a\u671f\u95f4\u4f1a\u5458\u5230\u5e97\u5373\u53ef\u83b7\u8d60\u60ca\u559c\u5f00\u4e1a\u793c\u76d2\uff08\u4ef7\u503c40\u5143\uff0c\u5171500\u4efd\uff0c\u9001\u5b8c\u5373\u6b62\uff09\n\n2.\u4f1a\u5458\u5230\u5e97\u4efb\u610f\u6d88\u8d39\uff0c\u5373\u53ef\u83b7\u8d60\u4e1d\u8299\u5170\u72ec\u5bb6\u54c1\u724c\u798f\u888b\uff08\u5185\u542b2\u4ef6\u4e1d\u8299\u5170\u72ec\u5bb6\u54c1\u724c\u851a\u84dd\u4e4b\u7f8e\u4e2d\u6837\uff0c\u4ef7\u503c60\u5143\uff0c\u9650\u91cf500\u4efd\uff0c\u9001\u5b8c\u5373\u6b62\uff09\n\n3.\u4efb\u610f\u6d88\u8d39\u6ee1688\u5143\uff0c\u5373\u53ef\u83b7\u8d60\u4e1d\u8299\u5170\u5927\u773c\u968f\u8eab\u773c\u5f71\u76d8\u6216\u4e1d\u8299\u5170\u67d3\u5507\u818f\u4e00\u4e2a\uff08\u4ef7\u503c99\u5143\uff0c\u9650\u91cf500\u4efd\uff0c\u793c\u54c1\u968f\u673a\uff0c\u9001\u5b8c\u5373\u6b62\uff09\n\n4.\u4efb\u610f\u6d88\u8d39\u6ee1888\u5143\uff0c\u5373\u53ef\u83b7\u8d60\u4e1d\u8299\u5170\u8461\u8404\u7c7d\u9c9c\u6d3b\u6ecb\u6da6\u55b7\u96fe\u4e00\u4efd\uff08\u4ef7\u503c139\u5143\uff0c\u9650\u91cf300\u4efd\uff0c\u9001\u5b8c\u5373\u6b62\uff09\n\n* \u5982\u4e0a\u4e24\u6863\u6ee1\u8d60\u4e0d\u540c\u4eab\u3002\n\n5. \u5f00\u4e1a\u8d7714\u5929\u5185\uff0c\u6ce8\u518c\u6210\u4e3a\u4e1d\u8299\u5170\u4f1a\u5458\uff0c\u5230\u5e97\u6d88\u8d39\u5c0a\u4eab\u53cc\u500d\u79ef\u5206\u3002\u8d2d\u6ee1750\u5143\u66f4\u53ef\u4f53\u9a8c\u9ed1\u5361\u793c\u9047\uff0c5.30-6.3\u9ed1\u5361\u4f1a\u5458\u9650\u65f6\u79c1\u4eab8\u6298\n\n\u5f00\u4e1a\u5f53\u5929\uff0c\u66f4\u63a8\u51fa\u7cbe\u5f69\u7684\u201c\u8ff7\u4f60\u5f69\u5986\u79c0\u201d\uff0c\u4e3a\u60a8\u5448\u73b0\u5f53\u5b63\u7f8e\u5986\u6d41\u884c\u8d8b\u52bf\u3002\n\n\u8bda\u9080\u60a8\u7684\u5149\u4e34\uff01 \n\u5730\u5740\uff1a\u4e0a\u6d77\u5e02\u5f90\u6c47\u533a\u6dee\u6d77\u4e2d\u8def999\u53f7\u4e0a\u6d77\u73af\u8d38\u5e7f\u573a\u4e00\u5c42\uff08L1\uff09136,137\u53ca139\u5ba4\n\n\uff0d\uff0d\uff0d\uff0d\uff0d\uff0d\uff0d\uff0d\uff0d\uff0d\n\nSEPHORA\u5ba2\u670d\u70ed\u7ebf400-670-0055 \n\nSEPHORA\u5b98\u7f51: www.sephora.cn\n\u7f16\u8f91\u77ed\u4fe1TD\u56de\u590d\u81f3\u672c\u53f7\u7801\uff0c\u5373\u53ef\u53d6\u6d88\u8ba2\u9605","num":2,"image_path":"","image_type":""}]}';
        print_r(json_decode($string, true));
    }

    public function SftpSflUpRiver()
    {
        $redis = Phpredis::getConn();
        $mysql_connect = Db::connect(Config::get('database.db_sflsftp'));
        $mysql_connect->query("set names utf8mb4");
        // $redis->rpush('index:message:code:upriver:83','{"mobile":"13661172555","message_info":"T"}');
        $all_upriver = [];
        try {
            while (true) {
                $channels = Db::query("SELECT * FROM yx_sms_sending_channel WHERE `delete_time` = 0 AND `id` IN (83, 84, 86, 87, 88, 94) ");

                foreach ($channels as $key => $value) {
                    $i = 1;
                    $redisMessageUpRiver = 'index:message:code:upriver:' . $value['id'];
                    while (true) {
                        $messageupriver = $redis->lpop($redisMessageUpRiver);
                        if (empty($messageupriver)) {
                            break;
                        }
                        $encodemessageupriver = json_decode($messageupriver, true);
                        $mobile = $encodemessageupriver['mobile'];
                        $prefix = '';
                        $prefix = substr(trim($mobile), 0, 7);
                        $res    = Db::query("SELECT `source_name`,`city` FROM `yx_number_source` WHERE `mobile` = '" . $prefix . "'");
                        // print_r($res);
                        $upriver = [];
                        $upriver = [
                            'from' => 'sfl',
                            'mobile' => $mobile,
                            'type' => 'SMS',
                            'message_info' => $encodemessageupriver['message_info'],
                        ];
                        $upriver['receive_time'] = date('Y-m-d H:i:s', time());
                        if ($res) {
                            $newres = array_shift($res);
                            $upriver['source_name'] = $newres['source_name'];
                            $upriver['city'] = $newres['city'];
                        } else {
                            $upriver['source_name'] = '未知';
                            $upriver['city'] = '未知';
                        }
                        $upriver['create_time'] = time();
                        $all_upriver[] = $upriver;
                        $i++;
                        if ($i > 100) {
                            $mysql_connect->table('yx_sftp_upriver')->insertAll($all_upriver);
                            unset($all_upriver);
                        }
                    }
                    if (!empty($all_upriver)) {
                        $mysql_connect->table('yx_sftp_upriver')->insertAll($all_upriver);
                        unset($all_upriver);
                    }
                }
                // $redis->rpush('sftp:upriver:chuanglan','{"from":"sfl","mobile":"13251428205","type":"MMS","message_info":"b\u00fc\u01d6bb","receive_time":"2020-07-06 23:45:48","source_name":"\u4e2d\u56fd\u8054\u901a","city":"\u91cd\u5e86\u5e02"}');
                while (true) {
                    $upriver = [];
                    $upriver =  $redis->lpop('sftp:upriver:chuanglan');
                    if (empty($upriver)) {
                        break;
                    }
                    $upriver = json_decode($upriver, true);
                    $all_upriver[] = $upriver;
                    $i++;
                    if ($i > 100) {
                        $mysql_connect->table('yx_sftp_upriver')->insertAll($all_upriver);
                        unset($all_upriver);
                    }
                }
                if (!empty($all_upriver)) {
                    $mysql_connect->table('yx_sftp_upriver')->insertAll($all_upriver);
                    unset($all_upriver);
                }
                sleep(300);
            }
        } catch (\Exception $th) {
            exception($th);
        }
    }

    public function dellBufa()
    {
        $task = Db::query("SELECT `id` FROM `messagesend`.`yx_user_send_task` WHERE `uid` = '139' AND `create_time` >= '1591891200' AND `yidong_channel_id` = 0 ");
        Db::table('yx_user_send_task')->where([['uid', '=', 139], ['yidong_channel_id', '=', 0], ['create_time', '>=', 1591891200]])->update(['free_trial' => 2, 'yidong_channel_id' => 83, 'liantong_channel_id' => 84, 'dianxin_channel_id' => 84]);
        // echo Db::getLastSQL();
        $redis = Phpredis::getConn();
        foreach ($task as $key => $value) {
            $redis->rpush();
        }
    }

    public function futureReceiveCallBack($date)
    {
        /* echo "SELECT `id` FROM yx_user_send_task WHERE `uid` IN (SELECT `id` FROM yx_users WHERE `pid` = 137) ";
        die; */
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        // $time = strtotime('2020-06-03 00:00:00');
        $time = strtotime($date);
        // $end_time = strtotime('2020-06-02 00:00:00');
        $end_time = $time + 86400;
        // echo $time;die;
        $redis = Phpredis::getConn();
        try {
            $all_report = '';
            $receipt_reports = [];
            $j = 1;
            /*  $Received =  [
                'REJECTD',
                'REJECT',
                'MA:0001',
                'DB:0141',
                'MA:0001',
                'MK:100D',
                'MK:100C',
                'IC:0151',
                'EXPIRED',
                '-1012',
                '-1013',
                '4442',
                '4446',
                '4014'
            ]; */
            $task_receipt = Db::query("SELECT `*` FROM `yx_send_task_receipt` WHERE `task_id` IN (SELECT `id` FROM yx_user_send_task WHERE `uid` IN (SELECT `id` FROM yx_users WHERE `pid` = 137) AND `create_time` >= " . $time . " AND `create_time` <= " . $end_time . ")  ORDER BY `task_id` DESC");
            /* echo "SELECT `*` FROM `yx_send_task_receipt` WHERE `task_id` IN (SELECT `id` FROM yx_user_send_task WHERE `uid` IN (SELECT `id` FROM yx_users WHERE `pid` = 137) AND `create_time` >= ".$time." AND `create_time` <= ".$end_time."  ORDER BY `id` DESC)";die; */
            foreach ($task_receipt as $key => $value) {
                $task = Db::query("SELECT `task_content`,`task_no`,`send_msg_id` FROM yx_user_send_task WHERE `id` = " . $value['task_id']);
                $send_len = 0;
                $send_len = mb_strlen($task[0]['task_content']);
                $s_num = 1;
                if ($send_len > 70) {
                    $s_num = ceil($send_len / 67);
                }
                $stat = trim($value['status_message']);
                if (strpos($stat, 'DB:0141') !== false || strpos($stat, 'MBBLACK') !== false || strpos($stat, 'BLACK') !== false) {
                    $message_info = '黑名单';
                } else if (trim($stat == 'DELIVRD')) {
                    $message_info = '发送成功';
                } else if (in_array(trim($stat), $Received)) {
                    $stat = 'DELIVRD';
                    $message_info = '发送成功';
                } else {
                    $message_info = '发送失败';
                }
                for ($a = 0; $a < $s_num; $a++) {
                    $receipt_report = [];
                    $receipt_report = [
                        'task_no'        => trim($task[0]['task_no']),
                        'status_message' => $stat,
                        'message_info'   => $message_info,
                        'mobile'         => trim($value['mobile']),
                        'msg_id'         => trim($task[0]['send_msg_id']),
                        // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                        'send_time'      => isset($value['create_time']) ? date('Y-m-d H:i:s', trim($value['create_time'])) : date('Y-m-d H:i:s', time()),
                        'smsCount' => $s_num,
                        'smsIndex' => $a + 1,
                    ];
                    $all_report = $all_report . json_encode($receipt_report) . "\n";
                    // print_r(json_encode($receipt_report));die;
                    $receipt_reports[] = $receipt_report;
                    $j++;
                    if ($j > 100) {
                        //  print_r($all_report);die;
                        $res = sendRequestText('https://www.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                        //推送失败
                        // print_r($res);
                        if ($res != 'SUCCESS') {
                            usleep(300);
                            $res = sendRequestText('https://www.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                            if ($res != 'SUCCESS') {
                                usleep(300);
                                $res = sendRequestText('https://www.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                                foreach ($receipt_reports as $akey => $avalue) {
                                    // print_r($avalue);die;
                                    $redis->rpush('index:meassage:code:receive_for_future_default', json_encode($avalue)); //写入用户带处理日志
                                }
                            }
                        }
                        $all_report = '';
                        $receipt_reports = [];
                        $j = 1;
                    }
                }
            }
            if (!empty($all_report)) {
                $res = sendRequestText('https://www.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                //推送失败
                if ($res != 'SUCCESS') {
                    usleep(300);
                    $res = sendRequestText('https://www.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                    if ($res != 'SUCCESS') {
                        usleep(300);
                        $res = sendRequestText('https://www.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                        foreach ($receipt_reports as $akey => $avalue) {
                            // # code...
                            // print_r($avalue);die;
                            $redis->rpush('index:meassage:code:receive_for_future_default', json_encode($avalue)); //写入用户带处理日志
                        }
                    }
                }
                $all_report = '';
                $receipt_report = [];
                $j = 1;
            }

            $task_receipt = Db::query("SELECT `*` FROM `yx_send_code_task_receipt` WHERE `task_id` IN (SELECT `id` FROM yx_user_send_code_task WHERE `uid` IN (SELECT `id` FROM yx_users WHERE `pid` = 137) AND `create_time` >= " . $time . " AND `create_time` <= " . $end_time . "  ) ORDER BY `task_id` DESC ");
            // echo count($task_receipt);die;
            foreach ($task_receipt as $key => $value) {
                $task = Db::query("SELECT `task_content`,`task_no`,`send_msg_id` FROM yx_user_send_code_task WHERE `id` = " . $value['task_id']);
                $send_len = 0;
                $send_len = mb_strlen($task[0]['task_content']);
                $s_num = 1;
                if ($send_len > 70) {
                    $s_num = ceil($send_len / 67);
                }
                $stat = trim($value['status_message']);
                if (strpos($stat, 'DB:0141') !== false || strpos($stat, 'MBBLACK') !== false || strpos($stat, 'BLACK') !== false) {
                    $message_info = '黑名单';
                } else if (trim($stat == 'DELIVRD')) {
                    $message_info = '发送成功';
                } else if (in_array(trim($stat), ['REJECTD', 'REJECT', 'MA:0001', 'DB:0141'])) {
                    $stat = 'DELIVRD';
                    $message_info = '发送成功';
                } else {
                    $message_info = '发送失败';
                }
                for ($a = 0; $a < $s_num; $a++) {
                    $receipt_report = [];
                    $receipt_report = [
                        'task_no'        => trim($task[0]['task_no']),
                        'status_message' => $stat,
                        'message_info'   => $message_info,
                        'mobile'         => trim($value['mobile']),
                        'msg_id'         => trim($task[0]['send_msg_id']),
                        // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                        'send_time'      => isset($value['create_time']) ? date('Y-m-d H:i:s', trim($value['create_time'])) : date('Y-m-d H:i:s', time()),
                        'smsCount' => $s_num,
                        'smsIndex' => $a + 1,
                    ];
                    $all_report = $all_report . json_encode($receipt_report) . "\n";
                    // print_r(json_encode($receipt_report));die;
                    $receipt_reports[] = $receipt_report;
                    $j++;
                    if ($j > 100) {
                        //  print_r($all_report);die;
                        $res = sendRequestText('https://www.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                        //推送失败
                        print_r($res);
                        if ($res != 'SUCCESS') {
                            usleep(300);
                            $res = sendRequestText('https://www.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                            if ($res != 'SUCCESS') {
                                usleep(300);
                                $res = sendRequestText('https://www.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                                foreach ($receipt_reports as $akey => $avalue) {
                                    // print_r($avalue);die;
                                    $redis->rpush('index:meassage:code:receive_for_future_default', json_encode($avalue)); //写入用户带处理日志
                                }
                            }
                        }
                        $all_report = '';
                        $receipt_reports = [];
                        $j = 1;
                    }
                }
            }
            if (!empty($all_report)) {
                $res = sendRequestText('https://www.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                //推送失败
                if ($res != 'SUCCESS') {
                    usleep(300);
                    $res = sendRequestText('https://www.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                    if ($res != 'SUCCESS') {
                        usleep(300);
                        $res = sendRequestText('https://www.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                        foreach ($receipt_reports as $akey => $avalue) {
                            // # code...
                            // print_r($avalue);die;
                            $redis->rpush('index:meassage:code:receive_for_future_default', json_encode($avalue)); //写入用户带处理日志
                        }
                    }
                }
                $all_report = '';
                $receipt_report = [];
                $j = 1;
            }

            if ($redis->LLEN('index:meassage:code:receive_for_future_default') > 0) {
                /* $redis->rpush('index:meassage:code:send:85', json_encode([
                    'mobile'  => 15201926171,
                    'content' => "【钰晰科技】客户[future]回执推送失败请紧急查看并协调解决！！！时间" . date("Y-m-d H:i:s", time())
                ])); */
                $api = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=fa1c9682-f617-45f9-a6a3-6b65f671b457';
                // $api = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=693a91f6-7xxx-4bc4-97a0-0ec2sifa5aaa';
                $check_data = [];
                $check_data = [
                    'msgtype' => "text",
                    'text' => [
                        "content" => "Hi，错误提醒机器人\n客户[future]回执推送失败请紧急查看并协调解决！！！时间" . date("Y-m-d H:i:s", time()),
                    ],
                ];
                $headers = [
                    'Content-Type:application/json'
                ];
                $this->sendRequestRebort($api, 'post', $check_data, $headers);
                die;
            }
            // echo count($task_receipt);die;
        } catch (\Exception $th) {
            //throw $th;
            exception($th);
        }
    }

    public function futureCallBackForRedis()
    {
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        // $time = strtotime('2020-06-27 00:00:00');
        // echo $time;die;
        $redis = Phpredis::getConn();
        // print_r($redis);die;
        // $receipt = $redis->rPush('index:meassage:code:user:receive:168','{"task_no":"bus20063022452104364246","status_message":"REJECT","message_info":"\u53d1\u9001\u6210\u529f","mobile":"15103230163","msg_id":"70000500020200630224527169053","send_time":"2020-06-30 22:45:28","smsCount":1,"smsIndex":1}');

        try {
            while (true) {
                $all_report = '';
                $receipt_report = [];
                $j = 1;
                $Received = updateReceivedForMessage();
                /*   $Received =  [
                    'REJECTD', 
                    'REJECT', 
                    'MA:0001', 
                    'DB:0141',
                    'MA:0001',
                    'MK:100D',
                    'MK:100C',
                    'IC:0151',
                    'EXPIRED',
                    '-1012',
                    '-1013',
                    '4442',
                    '4446',
                    '4014'
                ]; */
                $user = Db::query("SELECT `id` FROM yx_users WHERE `pid` = 137 ");
                foreach ($user as $key => $value) {
                    /* 短信部分 */
                    while (true) {
                        $receipt = $redis->rpop('index:meassage:code:user:receive:' . $value['id']);
                        if (empty($receipt)) {
                            break;
                        }
                        // updateReceivedForMessage
                        $receipt = json_decode($receipt, true);
                        if (in_array(trim($receipt['status_message']), $Received)) {
                            $receipt['status_message'] = 'DELIVRD';
                            $receipt['message_info'] = '发送成功';
                        }
                        $receipt = json_encode($receipt);
                        $all_report = $all_report . $receipt . "\n";
                        $receipt_report[] = $receipt;
                        $j++;
                        if ($j > 100) {
                            //  print_r($all_report);die;
                            $res = sendRequestText('https://www.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                            //推送失败
                            // print_r($res);
                            if ($res != 'SUCCESS') {
                                usleep(300);
                                $res = sendRequestText('https://www.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                                if ($res != 'SUCCESS') {
                                    usleep(300);
                                    $res = sendRequestText('https://www.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                                    foreach ($receipt_report as $akey => $avalue) {
                                        // # code...
                                        // print_r($avalue);die;
                                        $redis->rpush('index:meassage:code:receive_for_future_default', $avalue); //写入用户带处理日志
                                    }
                                }
                            }
                            $all_report = '';
                            $receipt_report = [];
                            $j = 1;
                        }
                    }
                    while (true) {
                        $receipt = $redis->lpop('index:meassage:code:user:mulreceive:' . $value['id']);
                        if (empty($receipt)) {
                            break;
                        }
                        // updateReceivedForMessage
                        $receipt = json_decode($receipt, true);
                        if (in_array($receipt['status_message'], $Received)) {
                            $receipt['status_message'] = 'DELIVRD';
                            $receipt['message_info'] = '发送成功';
                        }
                        $receipt = json_encode($receipt);
                        $all_report = $all_report . $receipt . "\n";
                        $receipt_report[] = $receipt;
                        $j++;
                        if ($j > 100) {
                            //  print_r($all_report);die;
                            $res = sendRequestText('https://www.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                            //推送失败
                            // print_r($res);
                            if ($res != 'SUCCESS') {
                                usleep(300);
                                $res = sendRequestText('https://www.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                                if ($res != 'SUCCESS') {
                                    usleep(300);
                                    $res = sendRequestText('https://www.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                                    foreach ($receipt_report as $akey => $avalue) {
                                        // # code...
                                        // print_r($avalue);die;
                                        $redis->rpush('index:meassage:code:receive_for_future_default', $avalue); //写入用户带处理日志
                                    }
                                }
                            }
                            $all_report = '';
                            $receipt_report = [];
                            $j = 1;
                        }
                    }
                }
                // print_r($receipt_report);die;
                if (!empty($all_report)) {
                    $res = sendRequestText('https://www.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                    //推送失败
                    if ($res != 'SUCCESS') {
                        usleep(300);
                        $res = sendRequestText('https://www.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                        if ($res != 'SUCCESS') {
                            usleep(300);
                            $res = sendRequestText('https://www.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                            foreach ($receipt_report as $akey => $avalue) {
                                // # code...
                                // print_r($avalue);die;
                                $redis->rpush('index:meassage:code:receive_for_future_default', json_encode($avalue)); //写入用户带处理日志
                            }
                        }
                    }
                    $all_report = '';
                    $receipt_report = [];
                    $j = 1;
                }
                /* if ($redis->LLEN('index:meassage:code:receive_for_future_default') > 0) {
                    // $redis->rpush('index:meassage:code:send:85', json_encode([
                    //     'mobile'  => 15201926171,
                    //     'content' => "【钰晰科技】客户[future]回执推送失败请紧急查看并协调解决！！！时间" . date("Y-m-d H:i:s", time())
                    // ]));
                    $api = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=fa1c9682-f617-45f9-a6a3-6b65f671b457';
                    // $api = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=693a91f6-7xxx-4bc4-97a0-0ec2sifa5aaa';
                    $check_data = [];
                    $check_data = [
                        'msgtype' => "text",
                        'text' => [
                            "content" => "Hi，错误提醒机器人\n客户[future]回执推送失败请紧急查看并协调解决！！！时间" . date("Y-m-d H:i:s", time()),
                        ],
                    ];
                    $headers = [
                        'Content-Type:application/json'
                    ];
                    $this->sendRequestRebort($api, 'post', $check_data, $headers);
                    die;
                } */
                sleep(1);
            }


            // print_r($user);
        } catch (\Exception $th) {
            //throw $th;
            exception($th);
        }
    }

    public function futureReceiptCallBackForRedis()
    {
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        // $time = strtotime('2020-06-27 00:00:00');
        // echo $time;die;
        // $receipt = $redis->rPush('index:meassage:code:user:receive:168','{"task_no":"bus20063022452104364246","status_message":"NOROUTE","message_info":"\u53d1\u9001\u6210\u529f","mobile":"15103230163","msg_id":"70000500020200630224527169053","send_time":"2020-06-30 22:45:28","smsCount":1,"smsIndex":1}');
        $redis = Phpredis::getConn();
        $uid = 223;
        try {
            //code...
            while (true) {
                $Received = updateReceivedForMessage();
                $all_report = '';
                $receipt_report = [];
                $j = 1;

                while (true) {
                    $receipt = $redis->lpop('index:meassage:code:user:mulreceive:' . $uid);
                    if (empty($receipt)) {
                        break;
                    }
                    // updateReceivedForMessage
                    $receipt = json_decode($receipt, true);
                    if (in_array($receipt['status_message'], $Received)) {
                        $receipt['status_message'] = 'DELIVRD';
                        $receipt['message_info'] = '发送成功';
                    }
                    $receipt = json_encode($receipt);
                    $all_report = $all_report . $receipt . "\n";
                    $receipt_report[] = $receipt;
                    $j++;
                    if ($j > 100) {
                        //  print_r($all_report);die;
                        $res = sendRequestText('http://test.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                        //推送失败
                        // print_r($res);
                        if ($res != 'SUCCESS') {
                            usleep(300);
                            $res = sendRequestText('http://test.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                            if ($res != 'SUCCESS') {
                                usleep(300);
                                $res = sendRequestText('http://test.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                                foreach ($receipt_report as $akey => $avalue) {
                                    // # code...
                                    // print_r($avalue);die;
                                    $redis->rpush('index:meassage:code:receive_for_future_default', $avalue); //写入用户带处理日志
                                }
                                $api = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=fa1c9682-f617-45f9-a6a3-6b65f671b457';
                                // $api = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=693a91f6-7xxx-4bc4-97a0-0ec2sifa5aaa';
                                $check_data = [];
                                $check_data = [
                                    'msgtype' => "text",
                                    'text' => [
                                        "content" => "Hi，错误提醒机器人\n客户[future]回执推送失败请紧急查看并协调解决！！！时间" . date("Y-m-d H:i:s", time()),
                                    ],
                                ];
                                $headers = [
                                    'Content-Type:application/json'
                                ];
                                $this->sendRequestRebort($api, 'post', $check_data, $headers);
                                die;
                            }
                        }
                        $all_report = '';
                        $receipt_report = [];
                        $j = 1;
                    }
                }
                if (!empty($all_report)) {
                    $res = sendRequestText('http://test.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                    //推送失败
                    if ($res != 'SUCCESS') {
                        usleep(300);
                        $res = sendRequestText('http://test.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                        if ($res != 'SUCCESS') {
                            usleep(300);
                            $res = sendRequestText('http://test.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                            foreach ($receipt_report as $akey => $avalue) {
                                // # code...
                                // print_r($avalue);die;
                                $redis->rpush('index:meassage:code:receive_for_future_default', json_encode($avalue)); //写入用户带处理日志
                            }
                            $api = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=fa1c9682-f617-45f9-a6a3-6b65f671b457';
                            // $api = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=693a91f6-7xxx-4bc4-97a0-0ec2sifa5aaa';
                            $check_data = [];
                            $check_data = [
                                'msgtype' => "text",
                                'text' => [
                                    "content" => "Hi，错误提醒机器人\n客户[future]回执推送失败请紧急查看并协调解决！！！时间" . date("Y-m-d H:i:s", time()),
                                ],
                            ];
                            $headers = [
                                'Content-Type:application/json'
                            ];
                            $this->sendRequestRebort($api, 'post', $check_data, $headers);
                            die;
                        }
                    }
                    print_r($res);
                    echo "\n";
                    print_r($all_report);
                    echo "\n";
                    print_r("http://test.futurersms.com/api/callback/xjy/report");
                    $all_report = '';
                    $receipt_report = [];
                    $j = 1;
                }
                /* if ($redis->LLEN('index:meassage:code:receive_for_future_default') > 0) {
                    $api = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=fa1c9682-f617-45f9-a6a3-6b65f671b457';
                    // $api = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=693a91f6-7xxx-4bc4-97a0-0ec2sifa5aaa';
                    $check_data = [];
                    $check_data = [
                        'msgtype' => "text",
                        'text' => [
                            "content" => "Hi，错误提醒机器人\n客户[future]回执推送失败请紧急查看并协调解决！！！时间" . date("Y-m-d H:i:s", time()),
                        ],
                    ];
                    $headers = [
                        'Content-Type:application/json'
                    ];
                    $this->sendRequestRebort($api, 'post', $check_data, $headers);
                    // die;
                } */
                sleep(1);
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    public function futureReceiptSendSecond()
    {
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        // $time = strtotime('2020-06-27 00:00:00');
        // echo $time;die;
        $redis = Phpredis::getConn();
        // $redis->rPush('index:meassage:code:receive_for_future_default','{"task_no":"mar20092516421310521553","status_message":"DELIVRD","message_info":"\u53d1\u9001\u6210\u529f","mobile":"13623658038","msg_id":"13000710020200925164213169499","send_time":"2020-09-25 17:35:55","smsCount":2,"smsIndex":1}');
        // $redis->rPush('index:meassage:code:receive_for_future_default','"{\"task_no\":\"mar20092516414401362237\",\"status_message\":\"DB:Blac\",\"message_info\":\"\\u53d1\\u9001\\u5931\\u8d25\",\"mobile\":\"13523728253\",\"msg_id\":\"13000710020200925164144169229\",\"send_time\":\"2020-09-25 17:44:12\",\"smsCount\":2,\"smsIndex\":2}"');
        // $redis = Phpredis::getConn();
        try {
            $i = 1;
            $receipt_report = [];
            $all_report = '';
            while (true) {
                $receipt = $redis->lpop('index:meassage:code:receive_for_future_default'); //写入用户带处理日志
                if (empty($receipt)) {
                    break;
                }
                // $receipt = json_encode($receipt);
                $new_receipt = json_decode($receipt, true);
                if (is_array($new_receipt)) {
                    $all_report = $all_report . $receipt . "\n";
                    $receipt_report[] = $receipt;
                    $i++;
                } else {
                    $all_report = $all_report . $new_receipt . "\n";
                    $receipt_report[] = $new_receipt;
                    $i++;
                }
                if ($i > 100) {
                    //  print_r($all_report);die;
                    $res = sendRequestText('http://test.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                    //推送失败
                    // print_r($res);
                    if ($res != 'SUCCESS') {
                        usleep(300);
                        $res = sendRequestText('http://test.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                        if ($res != 'SUCCESS') {
                            usleep(300);
                            $res = sendRequestText('http://test.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                            foreach ($receipt_report as $akey => $avalue) {
                                // # code...
                                // print_r($avalue);die;
                                $redis->rpush('index:meassage:code:receive_for_future_default', $avalue); //写入用户带处理日志
                            }
                            $api = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=fa1c9682-f617-45f9-a6a3-6b65f671b457';
                            // $api = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=693a91f6-7xxx-4bc4-97a0-0ec2sifa5aaa';
                            $check_data = [];
                            $check_data = [
                                'msgtype' => "text",
                                'text' => [
                                    "content" => "Hi，错误提醒机器人\n客户[future]回执推送失败请紧急查看并协调解决！！！时间" . date("Y-m-d H:i:s", time()),
                                ],
                            ];
                            $headers = [
                                'Content-Type:application/json'
                            ];
                            $this->sendRequestRebort($api, 'post', $check_data, $headers);
                            die;
                        }
                    }
                    $all_report = '';
                    $receipt_report = [];
                    $i = 1;
                }
            }
            // print_r($all_report);die;
            if (!empty($all_report)) {
                $res = sendRequestText('http://test.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                //推送失败
                if ($res != 'SUCCESS') {
                    usleep(300);
                    $res = sendRequestText('http://test.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                    if ($res != 'SUCCESS') {
                        usleep(300);
                        $res = sendRequestText('http://test.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                        foreach ($receipt_report as $akey => $avalue) {
                            // # code...
                            // print_r($avalue);die;
                            $redis->rpush('index:meassage:code:receive_for_future_default', json_encode($avalue)); //写入用户带处理日志
                        }
                        $api = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=fa1c9682-f617-45f9-a6a3-6b65f671b457';
                        // $api = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=693a91f6-7xxx-4bc4-97a0-0ec2sifa5aaa';
                        $check_data = [];
                        $check_data = [
                            'msgtype' => "text",
                            'text' => [
                                "content" => "Hi，错误提醒机器人\n客户[future]回执推送失败请紧急查看并协调解决！！！时间" . date("Y-m-d H:i:s", time()),
                            ],
                        ];
                        $headers = [
                            'Content-Type:application/json'
                        ];
                        $this->sendRequestRebort($api, 'post', $check_data, $headers);
                        die;
                    }
                }
                /* print_r($res);
                echo "\n";
                print_r($all_report);
                echo "\n";
                print_r("http://test.futurersms.com/api/callback/xjy/report"); */
                $all_report = '';
                $receipt_report = [];
                $j = 1;
            }
        } catch (\Exception $th) {
            //throw $th;
            exception($th);
        }
    }

    public function sflMulTaskLogCreate()
    {
        $redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M');
        try {
            //code...
            $start_time = strtotime("2020-06-27 20:00:00");
            $end_time = strtotime("2020-06-28 10:00:00");
            $task = Db::query("SELECT * FROM yx_user_multimedia_message WHERE `id` >= 122743 AND `id` <= 124273 ");
            foreach ($task as $key => $value) {
                $mobile_content = [];
                $mobile_content = explode(',', $value['mobile_content']);
                // echo date('Y-m-d H:i:s', $value['create_time']);
                // die;
                if ($value['create_time'] >= $start_time && $value['create_time'] <= $end_time) {
                    $send_time = $end_time + mt_rand(10, 300);
                } else {
                    $send_time = $value['create_time'] + mt_rand(10, 300);
                }
                for ($i = 0; $i < count($mobile_content); $i++) {
                    Db::table('yx_user_multimedia_message_log')->insert([
                        'task_no'      => $value['task_no'],
                        'uid'          => $value['uid'],
                        'source'       => $value['source'],
                        'task_content' => $value['title'],
                        'mobile'       => $mobile_content[$i],
                        'channel_id'   => $value['yidong_channel_id'],
                        'send_status'  => 2,
                        'status_message'  => 'DELIVRD',
                        'real_message'  => 'DELIVRD',
                        'create_time'  => time(),
                    ]);
                    $redis->rpush('index:meassage:code:user:mulreceive:' . $value['uid'], json_encode([
                        'task_no'        => $value['task_no'],
                        'status_message' => 'DELIVRD',
                        'message_info'   => '发送成功',
                        'mobile'         => $mobile_content[$i],
                        'send_time'      => isset($send_time) ? date('Y-m-d H:i:s', trim($send_time)) : date('Y-m-d H:i:s', time()),
                    ])); //写入用户带处理日志
                }

                /* print_r($value);
                die; */
            }
        } catch (\Exception $th) {
            //throw $th;
            exception($th);
        }
    }

    public function sflMulMessageCreate()
    {
        $redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M');
        // $redis->rpush("index:meassage:multimediamessage:buffersendtask", json_encode(['id' =>94348, 'deduct' => 10]));
        // $redis->rpush("index:meassage:multimediamessage:sendtask", '{"id":"412089","deduct":"35.00"}');
        // {"id":"412089","deduct":"35.00"}
        try {
            while (true) {
                $send = $redis->lpop('index:meassage:multimediamessage:buffersendtask');
                // $send = $redis->lpop('index:meassage:multimediamessage:sendtask');
                if (empty($send)) {
                    break;
                }
                $real_send = json_decode($send, true);
                $sendTask = $this->getMultimediaSendTask($real_send['id']);
                // print_r($sendTask);die;
                $day = date('Ymd', $sendTask['update_time']);
                $sendday = 0;
                // echo date('H', $sendTask['update_time']);die;
                $mobile_content = explode(',', $sendTask['mobile_content']);
                if (date('H', $sendTask['update_time']) >= 20) {
                    $sendday = $day + 1;
                    $dayTime = $sendday . '100000';
                    // $send_time = 
                    // $dayTime = strtotime($dayTime);
                } elseif (date('H', $sendTask['update_time']) <= 10) {
                    $sendday = $day;
                    $dayTime = $sendday . '100000';
                } else {
                    $dayTime = date('Y-m-d H:i:s', $sendTask['update_time']);
                }
                $dayTime = strtotime($dayTime);
                $dayTime = intval($dayTime) + mt_rand(10, 300);
                for ($i = 0; $i < count($mobile_content); $i++) {
                    if ($sendTask['uid'] == 91) {
                        Db::table('yx_user_multimedia_message_log')->insert([
                            'task_no'      => $sendTask['task_no'],
                            'uid'          => $sendTask['uid'],
                            'source'       => $sendTask['source'],
                            'task_content' => $sendTask['title'],
                            'mobile'       => $mobile_content[$i],
                            'channel_id'   => $sendTask['yidong_channel_id'],
                            'send_status'  => 2,
                            'status_message'  => 'DELIVRD',
                            'real_message'  => 'DEDUCT:1',
                            'create_time'  =>  $sendTask['create_time'],
                        ]);

                        $redis->rpush('index:meassage:code:user:mulreceive:' . $sendTask['uid'], json_encode([
                            'task_no'        => $sendTask['task_no'],
                            'status_message' => 'DELIVRD',
                            'message_info'   => '发送成功',
                            'mobile'         => $mobile_content[$i],
                            'send_time'      => isset($dayTime) ? date('Y-m-d H:i:s', trim($dayTime)) : date('Y-m-d H:i:s', time()),
                        ])); //写入用户带处理日志
                    } else {
                        Db::table('yx_user_multimedia_message_log')->insert([
                            'task_no'      => $sendTask['task_no'],
                            'uid'          => $sendTask['uid'],
                            'source'       => $sendTask['source'],
                            'task_content' => $sendTask['title'],
                            'mobile'       => $mobile_content[$i],
                            'channel_id'   => $sendTask['yidong_channel_id'],
                            'send_status'  => 2,
                            'status_message'  => '',
                            'real_message'  => 'DEDUCT:1',
                            'create_time'  =>  $sendTask['create_time'],
                        ]);
                    }
                }
            }
        } catch (\Exception $th) {
            //throw $th;
            exception($th);
        }
    }

    public function sflMessageCreate()
    {
        $redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M');
        $redis->rpush("index:meassage:business:buffersendtask", '{"id":"2617147","deduct":"60.00"}');
        try {
            while (true) {
                $send = $redis->lpop('index:meassage:business:buffersendtask');
                if (empty($send)) {
                    break;
                }
                $real_send = json_decode($send, true);
                if ($real_send['id'] <= 94348) {
                    $sendTask = $this->getMultimediaSendTask($real_send['id']);
                    // print_r($sendTask);die;
                    $day = date('Ymd', $sendTask['update_time']);
                    $sendday = 0;
                    // echo $dayTime;die;
                    $mobile_content = explode(',', $sendTask['mobile_content']);
                    if (date('H', $sendTask['update_time']) >= 20) {
                        $sendday = $day + 1;
                        $dayTime = $sendday . '100000';
                        // $send_time = 
                        // $dayTime = strtotime($dayTime);
                    }
                    if (date('H', $sendTask['update_time']) <= 10) {
                        $sendday = $day;
                        $dayTime = $sendday . '100000';
                    }
                    $dayTime = strtotime($dayTime);
                    $dayTime = intval($dayTime) + mt_rand(10, 300);
                    for ($i = 0; $i < count($mobile_content); $i++) {
                        Db::table('yx_user_multimedia_message_log')->insert([
                            'task_no'      => $sendTask['task_no'],
                            'uid'          => $sendTask['uid'],
                            'source'       => $sendTask['source'],
                            'task_content' => $sendTask['title'],
                            'mobile'       => $mobile_content[$i],
                            'channel_id'   => $sendTask['yidong_channel_id'],
                            'send_status'  => 2,
                            'status_message'  => 'DELIVRD',
                            'real_message'  => 'DELIVRD',
                            'create_time'  => $sendTask['create_time'],
                        ]);
                        $redis->rpush('index:meassage:code:user:mulreceive:' . $sendTask['uid'], json_encode([
                            'task_no'        => $sendTask['task_no'],
                            'status_message' => 'DELIVRD',
                            'message_info'   => '发送成功',
                            'mobile'         => $mobile_content[$i],
                            'send_time'      => isset($dayTime) ? date('Y-m-d H:i:s', trim($dayTime)) : date('Y-m-d H:i:s', time()),
                        ])); //写入用户带处理日志
                    }
                } else {
                    $sendTask = $this->getSendCodeTask($real_send['id']);
                    // print_r($sendTask);die;
                    $day = date('Ymd', $sendTask['update_time']);
                    $sendday = 0;

                    $mobile_content = explode(',', $sendTask['mobile_content']);
                    if (date('H', $sendTask['update_time']) >= 20) {
                        $sendday = $day + 1;
                        $dayTime = $sendday . '100000';
                        // $send_time = 
                        // $dayTime = strtotime($dayTime);
                    } else if (date('H', $sendTask['update_time']) <= 10) {
                        $sendday = $day;
                        $dayTime = $sendday . '100000';
                    } else {
                        $dayTime = $sendTask['update_time'];
                    }
                    $dayTime = strtotime($dayTime);
                    $dayTime = intval($dayTime) + mt_rand(10, 300);
                    for ($i = 0; $i < count($mobile_content); $i++) {
                        Db::table('yx_user_send_code_task_log')->insert([
                            'task_no'      => $sendTask['task_no'],
                            'uid'          => $sendTask['uid'],
                            'source'       => $sendTask['source'],
                            'task_content' => $sendTask['task_content'],
                            'mobile'       => $mobile_content[$i],
                            'channel_id'   => $sendTask['yidong_channel_id'],
                            'send_status'  => 2,
                            'status_message'  => 'DELIVRD',
                            'real_message'  => 'DELIVRD',
                            'create_time'  => time(),
                        ]);
                        $redis->rpush('index:meassage:code:user:mulreceive:' . $sendTask['uid'], json_encode([
                            'task_no'        => $sendTask['task_no'],
                            'status_message' => 'DELIVRD',
                            'message_info'   => '发送成功',
                            'mobile'         => $mobile_content[$i],
                            'send_time'      => isset($dayTime) ? date('Y-m-d H:i:s', trim($dayTime)) : date('Y-m-d H:i:s', time()),
                        ])); //写入用户带处理日志
                    }
                }
            }
        } catch (\Exception $th) {
            //throw $th;
            exception($th);
        }
    }

    public function callbackChannelStatus($id)
    {
        $redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M');
        while (true) {
            $send = $redis->lpop("index:meassage:code:send:" . $id);
            if (empty($send)) {
                break;
            }
            $send = json_decode($send, true);
            $task = Db::query("SELECT * FROM " . $send['from'] . " WHERE `id` =" . $send['mar_task_id']);
            if (empty($task)) {
                continue;
            }
            $task = $task[0];
            $send_task_log = [];
            $send_task_log = [
                'task_no' => $task['task_no'],
                'uid' => $task['uid'],
                'mobile' => $send['mobile'],
                'status_message' => '6150',
                'send_status' => '4',
                'send_time' => time(),
            ];
            $redis->rpush('index:meassage:multimediamessage:deliver:59', json_encode($send_task_log));
        }
    }

    //future未知补推
    public function reciveSendMessageFoFuture()
    {
        ini_set('memory_limit', '3072M');
        $redis = Phpredis::getConn();
        try {
            while (true) {
                // $time = strtotime(date('Y-m-d 0:00:00', time()));
                $start_time = strtotime('2020-09-14 0:00:00');

                // $start_time   = strtotime("-3 day");
                $start_time = $start_time + 60;
                $end_time = $start_time + 300;
                //行业
                $code_task_log = Db::query("SELECT * FROM yx_user_send_code_task_log WHERE `uid` IN (SELECT `id` FROM yx_users WHERE `pid` = 137) AND `status_message` = '' AND `create_time` >= '" . $start_time . "' AND  `create_time` <= '" . $end_time . "' LIMIT 1 ");
                // echo "SELECT * FROM yx_user_send_code_task_log WHERE `uid` IN (SELECT `id` FROM yx_users WHERE `pid` = 137) AND `status_message` = '' AND `create_time` >= '" . $start_time . "' AND  `create_time` <= '" . $end_time . "' LIMIT 1 ";die;
                if (!empty($code_task_log)) {
                    $task         = Db::query("SELECT `id`,`send_msg_id`,`task_no` FROM yx_user_send_code_task WHERE `task_no` = '" . $code_task_log[0]['task_no'] . "' ");
                    $task_receipt = Db::query("SELECT * FROM yx_send_code_task_receipt WHERE `task_id` = '" . $task[0]['id'] . "' AND `mobile` = '" . $code_task_log[0]['mobile'] . "' ");
                    /* $request_url = "http://116.228.60.189:15901/rtreceive?";
                    $request_url .= 'task_no=' . trim($code_task_log[0]['task_no']) . "&status_message=" . "DELIVRD" . "&mobile=" . trim($code_task_log[0]['mobile']) . "&send_time=" . trim(date('YmdHis', time() + mt_rand(0, 500)));
                    // print_r($request_url);
                    sendRequest($request_url); */
                    if (empty($task_receipt) && empty($code_task_log[0]['status_message'])) {
                        $receipt = [];
                        $receipt = [];
                        $send_len = 0;
                        $send_len = mb_strlen($code_task_log[0]['task_content']);
                        $s_num = 1;
                        if ($send_len > 70) {
                            $s_num = ceil($send_len / 67);
                        }
                        $stat = 'DELIVRD';
                        $message_info = '发送成功';
                        for ($a = 0; $a < $s_num; $a++) {
                            $receipt_report = [];
                            $receipt_report = [
                                'task_no'        => trim($task[0]['task_no']),
                                'status_message' => $stat,
                                'message_info'   => $message_info,
                                'mobile'         => trim($code_task_log[0]['mobile']),
                                'msg_id'         => trim($task[0]['send_msg_id']),
                                // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                                'send_time'      => date('YmdHis', time() - mt_rand(0, 500)),
                                'smsCount' => $s_num,
                                'smsIndex' => $a + 1,
                            ];
                            $all_report = json_encode($receipt_report);
                            // print_r(json_encode($receipt_report));die;
                            $receipt_reports[] = $receipt_report;
                            $res = sendRequestText('https://www.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                            //推送失败
                            // print_r($res);
                            if ($res != 'SUCCESS') {
                                usleep(300);
                                $res = sendRequestText('https://www.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                                if ($res != 'SUCCESS') {
                                    usleep(300);
                                    $res = sendRequestText('https://www.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                                    if ($res != 'SUCCESS') {
                                        $redis->rpush('index:meassage:code:receive_for_future_default', $all_report); //写入用户带处理日志
                                    }
                                }
                            }
                        }
                        // print_r($request_url);
                        // sendRequest($request_url);
                        // $res = sendRequestText('https://www.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                        Db::startTrans();
                        try {
                            Db::table('yx_user_send_code_task_log')->where('id', $code_task_log[0]['id'])->update(['status_message' => 'DELIVRD', 'send_status' => 3, 'update_time' => time()]);
                            Db::commit();
                        } catch (\Exception $e) {
                            Db::rollback();
                            exception($e);
                        }
                        // usleep(3);
                    }
                } else {
                    $code_task_log = Db::query("SELECT * FROM yx_user_send_task_log WHERE `uid` IN (SELECT `id` FROM yx_users WHERE `pid` = 137 AND `id` <> 278) AND `status_message` = '' AND `create_time` >= '" . $start_time . "' AND  `create_time` <= '" . $end_time . "' LIMIT 1 ");
                    if (!empty($code_task_log)) {
                        $task         = Db::query("SELECT `id`,`send_msg_id` FROM yx_user_send_task WHERE `task_no` = '" . $code_task_log[0]['task_no'] . "' ");
                        $task_receipt = Db::query("SELECT * FROM yx_send_task_receipt WHERE `task_id` = '" . $task[0]['id'] . "' AND `mobile` = '" . $code_task_log[0]['mobile'] . "' ");
                        /* $request_url = "http://116.228.60.189:15901/rtreceive?";
                        $request_url .= 'task_no=' . trim($code_task_log[0]['task_no']) . "&status_message=" . "DELIVRD" . "&mobile=" . trim($code_task_log[0]['mobile']) . "&send_time=" . trim(date('YmdHis', time() + mt_rand(0, 500)));
                        // print_r($request_url);
                        sendRequest($request_url); */
                        if (empty($task_receipt) && empty($code_task_log[0]['status_message'])) {
                            $send_len = 0;
                            $send_len = mb_strlen($code_task_log[0]['task_content']);
                            $s_num = 1;
                            if ($send_len > 70) {
                                $s_num = ceil($send_len / 67);
                            }
                            $stat = 'DELIVRD';
                            $message_info = '发送成功';
                            for ($a = 0; $a < $s_num; $a++) {
                                $receipt_report = [];
                                $receipt_report = [
                                    'task_no'        => trim($task[0]['task_no']),
                                    'status_message' => $stat,
                                    'message_info'   => $message_info,
                                    'mobile'         => trim($code_task_log[0]['mobile']),
                                    'msg_id'         => trim($task[0]['send_msg_id']),
                                    // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                                    'send_time'      => date('YmdHis', time() - mt_rand(0, 500)),
                                    'smsCount' => $s_num,
                                    'smsIndex' => $a + 1,
                                ];
                                $all_report = json_encode($receipt_report);
                                // print_r(json_encode($receipt_report));die;
                                // $receipt_reports[] = $receipt_report;
                                $res = sendRequestText('https://www.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                                //推送失败
                                // print_r($res);
                                if ($res != 'SUCCESS') {
                                    usleep(300);
                                    $res = sendRequestText('https://www.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                                    if ($res != 'SUCCESS') {
                                        usleep(300);
                                        $res = sendRequestText('https://www.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                                        if ($res != 'SUCCESS') {
                                            $redis->rpush('index:meassage:code:receive_for_future_default', $all_report); //写入用户带处理日志
                                        }
                                    }
                                }
                            }
                            // print_r($request_url);
                            // sendRequest($request_url);
                            // $res = sendRequestText('https://www.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                            Db::startTrans();
                            try {
                                Db::table('yx_user_send_code_task_log')->where('id', $code_task_log[0]['id'])->update(['status_message' => 'DELIVRD', 'send_status' => 3, 'update_time' => time()]);
                                Db::commit();
                            } catch (\Exception $e) {
                                Db::rollback();
                                exception($e);
                            }
                            // usleep(3);
                        }
                    }
                    /*  if ($redis->LLEN('index:meassage:code:receive_for_future_default') > 0) {
                        $redis->rpush('index:meassage:code:send:85', json_encode([
                            'mobile'  => 15201926171,
                            'content' => "【钰晰科技】客户[future]回执推送失败请紧急查看并协调解决！！！时间" . date("Y-m-d H:i:s", time())
                        ]));
                    } */
                    // echo 'Over' . "\n";
                    sleep(10);
                }
            }
        } catch (\Exception $th) {
            //throw $th;
            exception($th);
        }
    }

    public function newReciveSendMessageFoFuture()
    {
        ini_set('memory_limit', '3072M');
        $redis = Phpredis::getConn();
        try {
            $start_time = strtotime('2020-09-17 0:00:00');

            // $start_time   = strtotime("-3 day");
            $start_time = $start_time + 60;
            $end_time = $start_time + 300;
            //行业
            $code_task_log = Db::query("SELECT * FROM yx_user_send_code_task_log WHERE `uid` IN (SELECT `id` FROM yx_users WHERE `pid` = 137) AND `status_message` = '' AND `create_time` >= '" . $start_time . "' AND  `create_time` <= '" . $end_time . "' LIMIT 1 ");
            // echo "SELECT * FROM yx_user_send_code_task_log WHERE `uid` IN (SELECT `id` FROM yx_users WHERE `pid` = 137) AND `status_message` = '' AND `create_time` >= '" . $start_time . "' AND  `create_time` <= '" . $end_time . "' LIMIT 1 ";die;
            if (!empty($code_task_log)) {
                $task         = Db::query("SELECT `id`,`send_msg_id`,`task_no` FROM yx_user_send_code_task WHERE `task_no` = '" . $code_task_log[0]['task_no'] . "' ");
                $task_receipt = Db::query("SELECT * FROM yx_send_code_task_receipt WHERE `task_id` = '" . $task[0]['id'] . "' AND `mobile` = '" . $code_task_log[0]['mobile'] . "' ");
                /* $request_url = "http://116.228.60.189:15901/rtreceive?";
                    $request_url .= 'task_no=' . trim($code_task_log[0]['task_no']) . "&status_message=" . "DELIVRD" . "&mobile=" . trim($code_task_log[0]['mobile']) . "&send_time=" . trim(date('YmdHis', time() + mt_rand(0, 500)));
                    // print_r($request_url);
                    sendRequest($request_url); */
                if (empty($task_receipt) && empty($code_task_log[0]['status_message'])) {
                    $receipt = [];
                    $receipt = [];
                    $send_len = 0;
                    $send_len = mb_strlen($code_task_log[0]['task_content']);
                    $s_num = 1;
                    if ($send_len > 70) {
                        $s_num = ceil($send_len / 67);
                    }
                    $stat = 'DELIVRD';
                    $message_info = '发送成功';
                    for ($a = 0; $a < $s_num; $a++) {
                        $receipt_report = [];
                        $receipt_report = [
                            'task_no'        => trim($task[0]['task_no']),
                            'status_message' => $stat,
                            'message_info'   => $message_info,
                            'mobile'         => trim($code_task_log[0]['mobile']),
                            'msg_id'         => trim($task[0]['send_msg_id']),
                            // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                            'send_time'      => date('YmdHis', time() - mt_rand(0, 500)),
                            'smsCount' => $s_num,
                            'smsIndex' => $a + 1,
                        ];
                        $all_report = json_encode($receipt_report);
                        // print_r(json_encode($receipt_report));die;
                        $receipt_reports[] = $receipt_report;
                        $res = sendRequestText('https://www.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                        //推送失败
                        // print_r($res);
                        if ($res != 'SUCCESS') {
                            usleep(300);
                            $res = sendRequestText('https://www.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                            if ($res != 'SUCCESS') {
                                usleep(300);
                                $res = sendRequestText('https://www.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                                if ($res != 'SUCCESS') {
                                    $redis->rpush('index:meassage:code:receive_for_future_default', $all_report); //写入用户带处理日志
                                }
                            }
                        }
                    }
                    // print_r($request_url);
                    // sendRequest($request_url);
                    // $res = sendRequestText('https://www.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                    Db::startTrans();
                    try {
                        Db::table('yx_user_send_code_task_log')->where('id', $code_task_log[0]['id'])->update(['status_message' => 'DELIVRD', 'send_status' => 3, 'update_time' => time()]);
                        Db::commit();
                    } catch (\Exception $e) {
                        Db::rollback();
                        exception($e);
                    }
                    // usleep(3);
                }
            } else {
                $code_task_log = Db::query("SELECT * FROM yx_user_send_task_log WHERE `uid` IN (SELECT `id` FROM yx_users WHERE `pid` = 137 AND `id` <> 278) AND `status_message` = '' AND `create_time` >= '" . $start_time . "' AND  `create_time` <= '" . $end_time . "' LIMIT 1 ");
                if (!empty($code_task_log)) {
                    $task         = Db::query("SELECT `id`,`send_msg_id` FROM yx_user_send_task WHERE `task_no` = '" . $code_task_log[0]['task_no'] . "' ");
                    $task_receipt = Db::query("SELECT * FROM yx_send_task_receipt WHERE `task_id` = '" . $task[0]['id'] . "' AND `mobile` = '" . $code_task_log[0]['mobile'] . "' ");
                    /* $request_url = "http://116.228.60.189:15901/rtreceive?";
                        $request_url .= 'task_no=' . trim($code_task_log[0]['task_no']) . "&status_message=" . "DELIVRD" . "&mobile=" . trim($code_task_log[0]['mobile']) . "&send_time=" . trim(date('YmdHis', time() + mt_rand(0, 500)));
                        // print_r($request_url);
                        sendRequest($request_url); */
                    if (empty($task_receipt) && empty($code_task_log[0]['status_message'])) {
                        $send_len = 0;
                        $send_len = mb_strlen($code_task_log[0]['task_content']);
                        $s_num = 1;
                        if ($send_len > 70) {
                            $s_num = ceil($send_len / 67);
                        }
                        $stat = 'DELIVRD';
                        $message_info = '发送成功';
                        for ($a = 0; $a < $s_num; $a++) {
                            $receipt_report = [];
                            $receipt_report = [
                                'task_no'        => trim($task[0]['task_no']),
                                'status_message' => $stat,
                                'message_info'   => $message_info,
                                'mobile'         => trim($code_task_log[0]['mobile']),
                                'msg_id'         => trim($task[0]['send_msg_id']),
                                // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                                'send_time'      => date('YmdHis', time() - mt_rand(0, 500)),
                                'smsCount' => $s_num,
                                'smsIndex' => $a + 1,
                            ];
                            $all_report = json_encode($receipt_report);
                            // print_r(json_encode($receipt_report));die;
                            // $receipt_reports[] = $receipt_report;
                            $res = sendRequestText('https://www.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                            //推送失败
                            // print_r($res);
                            if ($res != 'SUCCESS') {
                                usleep(300);
                                $res = sendRequestText('https://www.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                                if ($res != 'SUCCESS') {
                                    usleep(300);
                                    $res = sendRequestText('https://www.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                                    if ($res != 'SUCCESS') {
                                        $redis->rpush('index:meassage:code:receive_for_future_default', $all_report); //写入用户带处理日志
                                    }
                                }
                            }
                        }
                        // print_r($request_url);
                        // sendRequest($request_url);
                        // $res = sendRequestText('https://www.futurersms.com/api/callback/xjy/report', 'post', $all_report);
                        Db::startTrans();
                        try {
                            Db::table('yx_user_send_code_task_log')->where('id', $code_task_log[0]['id'])->update(['status_message' => 'DELIVRD', 'send_status' => 3, 'update_time' => time()]);
                            Db::commit();
                        } catch (\Exception $e) {
                            Db::rollback();
                            exception($e);
                        }
                        // usleep(3);
                    }
                }
                /*  if ($redis->LLEN('index:meassage:code:receive_for_future_default') > 0) {
                        $redis->rpush('index:meassage:code:send:85', json_encode([
                            'mobile'  => 15201926171,
                            'content' => "【钰晰科技】客户[future]回执推送失败请紧急查看并协调解决！！！时间" . date("Y-m-d H:i:s", time())
                        ]));
                    } */
                // echo 'Over' . "\n";

            }
        } catch (\Exception $th) {
            //throw $th;
            exception($th);
        }
    }

    public function mmsReceipt()
    {
        $rediskey = 'index:meassage:multimediamessage:deliver';
        $redis = Phpredis::getConn();
        while (true) {
            $receipt = $redis->lpop($rediskey);
            if (empty($receipt)) {
            }
            sleep(1);
            continue;
            $receipt = json_decode($receipt, true);
        }
    }

    function writeToRobot($content, $error_data, $title)
    {
        $api = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=fa1c9682-f617-45f9-a6a3-6b65f671b457';
        // $api = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=693a91f6-7xxx-4bc4-97a0-0ec2sifa5aaa';
        $check_data = [];
        $check_data = [
            'msgtype' => "text",
            'text' => [
                "content" => "Hi，错误提醒机器人\n您有一个bug出现，请及时解决\n文件名称【" . $content . "】\n【错误信息】：" . $error_data . "\n错误的function【" . $title . "】",
            ],
        ];
        $headers = [
            'Content-Type:application/json'
        ];
        $this->sendRequestRebort($api, 'post', $check_data, $headers);
    }

    function sendRequestRebort($requestUrl, $method = 'get', $data = [], $headers)
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

    //16进制转2进制
    function StrToBin($str)
    {
        //1.列出每个字符
        $arr = preg_split('/(?<!^)(?!$)/u', $str);
        //2.unpack字符
        foreach ($arr as &$v) {
            $temp = unpack('H*', $v);
            $v    = base_convert($temp[1], 16, 2);
            unset($temp);
        }

        return join('', $arr);
    }

    public function zhonglanSmsUpGoing()
    {
        $redis = Phpredis::getConn();
        while (true) {
            $MinID = $redis->get('index:meassage:code:receipt:zhonglan:upriver:MinID');
            $MinID = $MinID ? $MinID : 0;
            // $MinID = 0;
            $receive = sendRequest('http://www.wemediacn.net/webservice/smsservice.asmx/QuerySMSUP', 'post', ['TokenID' => '7100455520709585', 'MinID' => $MinID, 'Count' => 0, 'externCode' => '']);
            $receive_data = json_decode(json_encode(simplexml_load_string($receive, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
            $codelen = strlen('106900294555');

            if (!empty($receive_data['result'])) {
                $MinID = $receive_data['@attributes']['nextID'];
                $redis->set('index:meassage:code:receipt:zhonglan:upriver:MinID', $MinID);
                foreach ($receive_data['result'] as $key => $value) {
                    $upgoing = [];
                    if (is_array($value)) {
                        $develop_code = mb_substr($value['DestNumber'], $codelen);
                        $mobile = $value['mobile'];
                        $message_info = $value['MsgContent'];
                        $get_time = $value['ReceiveTime'];
                        $upgoing = [
                            'mobile' => $mobile,
                            'message_info' => $message_info,
                            'get_time' => $get_time,
                        ];
                        $sql = "SELECT `uid`,`task_no` FROM yx_user_multimedia_message_log WHERE `mobile` = '" . $mobile . "' ";
                        if (!empty($develop_code)) {
                            $sql .= " AND `develop_no` = '" . $develop_code . "'";
                        }
                        $sql .= ' ORDER BY `id` DESC LIMIT 1';
                        $task_log = Db::query($sql);
                        // 
                        if (!empty($task_log)) {
                            $task_log = $task_log[0];
                            // print_r($task_log);die;
                            $redis->rPush('index:message:Mmsupriver:' . $task_log['uid'], json_encode($upgoing));
                            $insert_data = [];
                            $insert_data = [
                                'uid' => $task_log['uid'],
                                'task_no' => $task_log['task_no'],
                                'mobile' => $mobile,
                                'message_info' => $message_info,
                                'create_time' => strtotime($get_time),
                                'business_id' => 8,
                            ];
                            DB::table('yx_user_upriver')->insert($insert_data);
                        }
                        // print_r($codelen);
                    } else {
                        $develop_code = mb_substr($receive_data['result']['DestNumber'], $codelen);
                        $mobile = $receive_data['result']['mobile'];
                        $message_info = $receive_data['result']['MsgContent'];
                        $get_time = $receive_data['result']['ReceiveTime'];
                        $upgoing = [
                            'mobile' => $mobile,
                            'message_info' => $message_info,
                            'get_time' => $get_time,
                        ];
                        $sql = "SELECT `uid`,`task_no` FROM yx_user_multimedia_message_log WHERE `mobile` = '" . $mobile . "' ";
                        if (!empty($develop_code)) {
                            $sql .= " AND `develop_no` = '" . $develop_code . "'";
                        }
                        $sql .= ' ORDER BY `id` DESC LIMIT 1';
                        $task_log = Db::query($sql);
                        // 
                        if (!empty($task_log)) {
                            $task_log = $task_log[0];
                            // print_r($task_log);die;
                            $redis->rPush('index:message:Mmsupriver:' . $task_log['uid'], json_encode($upgoing));
                            $insert_data = [];
                            $insert_data = [
                                'uid' => $task_log['uid'],
                                'task_no' => $task_log['task_no'],
                                'mobile' => $mobile,
                                'message_info' => $message_info,
                                'create_time' => strtotime($get_time),
                                'business_id' => 8,
                            ];
                            DB::table('yx_user_upriver')->insert($insert_data);
                        }
                        break;
                    }
                    // print_r($develop_code);

                }
            }
            sleep(30);
        }
    }

    public function channelSendInfo($channel_id)
    {
        $redis = Phpredis::getConn();
        // $redis->rpush('index:meassage:code:send:147', '{"mobile":"18610956962","mar_task_id":3460842,"content":"\u3010\u5954\u9a70\u91d1\u878d\u3011\u60a8\u7684\u9a8c\u8bc1\u7801\u4e3a593000\uff0c\u8bf7\u60a8\u572820\u5206\u949f\u5185\u5b8c\u6210\u9a8c\u8bc1\u3002","from":"yx_user_send_code_task","send_msg_id":"21000630020200907112852169410","uid":264,"send_num":1,"task_no":"bus20090711284465711920","develop_code":"8662"}');
        while (true) {
            $message = $redis->lpop('index:meassage:code:send:' . $channel_id);
            if (empty($message)) {
                exit('退出');
            }
            $message = json_decode($message, true);
            /* foreach ($mobiles as $mkey => $mvalue) {
                $res = $this->redis->rpush("index:meassage:code:user:mulreceive:" . $value['uid'], json_encode(['task_no' => $value['task_no'], 'msg_id' => $value['send_msg_id'], "status_message" => "INTERCEPT", "message_info" => "驳回", "send_time" => date("Y-m-d H:i:s", time()), 'mobile' => $mvalue]));
            } */

            /*  $len = 0;
            $len = mb_strlen($message['content']);
            if ($len > 70) {
                $num = ceil($len / 67);
            }else{
                $num = 1;
            } */
            // print_r($num);die;
            /* for($i = 0; $i < $num; $i++){
                $res = $redis->rpush("index:meassage:code:user:receive:" . $message['uid'], json_encode(['task_no' => $message['task_no'], 'msg_id' => $message['send_msg_id'], "status_message" => "INTERCEPT", "message_info" => "驳回", "send_time" => date("Y-m-d H:i:s", time()), 'mobile' => $message['mobile']]));
            } */
            if ($message['uid'] == 190 || $message['uid'] == 191 || $message['uid'] == 287) {
                $message['Stat'] = 'INTERCEPT';
                $message['Done_time'] = '2009071224';
                $message['Done_time'] = '2009071224';
                $message['receive_time'] = time();
                $message['my_submit_time'] = '1599449679';
                $redis->rpush('index:meassage:code:new:deliver:' . $channel_id, json_encode($message));
            } else {
                $redis->rpush('index:meassage:code:send:' . $channel_id, json_encode($message));
            }
        }
    }

    public function unkonwnDeliver($channel_id)
    {
        try {
            //code...
            // $redis->rpush('index:meassage:code:send:147', '{"Stat":"DELIVRD","Submit_time":"0101010000","Done_time":"2009221424","mobile":"15290776560\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000","receive_time":1600755904,"Msg_Id":"260423712030751016"}');
            $redis = Phpredis::getConn();
            while (true) {
                $message = $redis->lpop('index:meassage:code:unknow:deliver:' . $channel_id);
                if (empty($message)) {
                    exit('退出');
                }
                $message = json_decode($message, true);
                $mobile = trim($message['mobile']);
                $task_log = Db::query("SELECT `task_no` FROM  `yx_user_send_task_log` WHERE `mobile` = '" . $mobile . "' ORDER BY `id` DESC LIMIT 1 ");
                if (empty($task_log)) {
                    continue;
                }
                $task_no = $task_log[0]['task_no'];
                $task =  Db::query("SELECT `*` FROM  `yx_user_send_task` WHERE `task_no` = '" . $task_no . "' LIMIT 1 ");
                if (Db::query("SELECT `id` FROM yx_send_task_receipt WHERE  `mobile` = '" . $mobile . "' AND `task_id` = '" . $task[0]['id'] . "' ")) {
                    continue;
                }
                $user = Db::query("SELECT `pid`,`need_receipt_cmpp` FROM yx_users WHERE `id` = " . $task[0]['uid']);

                $stat = $message['Stat'];
                if (trim($stat) == 'DELIVRD') {
                    $message_info = '发送成功';
                } else {
                    $message_info = '发送失败';
                }
                if ($user[0]['pid'] == 137) {
                    // print_r($stat);die;

                    $send_len = 0;
                    $send_len = mb_strlen($task[0]['task_content']);
                    $s_num = 1;
                    if ($send_len > 70) {
                        $s_num = ceil($send_len / 67);
                    }
                    for ($a = 0; $a < $s_num; $a++) {
                        $redis->rpush('index:meassage:code:user:receive:' . $task[0]['uid'], json_encode([
                            'task_no'        => trim($task[0]['task_no']),
                            'status_message' => $stat,
                            'message_info'   => $message_info,
                            'mobile'         => trim($message['mobile']),
                            'msg_id'         => trim($task[0]['send_msg_id']),
                            // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                            'send_time'      => isset($message['receive_time']) ? date('Y-m-d H:i:s', trim($message['receive_time'])) : date('Y-m-d H:i:s', time()),
                            'smsCount' => $s_num,
                            'smsIndex' => $a + 1,
                        ])); //写入用户带处理日志
                    }
                } else {
                    if ($user[0]['need_receipt_cmpp'] == 2) {
                        $redis->rpush('index:meassage:code:user:receive:' . $task[0]['uid'], json_encode([
                            'Stat'        => trim($task[0]['task_no']),
                            'send_msgid'        => trim($task[0]['send_msg_id']),
                            'status_message' => $stat,
                            'mobile'         => trim($message['mobile']),
                            'develop_no' => trim($task[0]['develop_no']) ? $task[0]['develop_no'] : '',
                            // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                            'Done_time'      => isset($message['receive_time']) ? date('Y-m-d H:i:s', trim($message['receive_time'])) : date('Y-m-d H:i:s', time()),
                            'Submit_time'      => isset($task[0]['create_time']) ? date('Y-m-d H:i:s', trim($task[0]['create_time'])) : date('Y-m-d H:i:s', time()),
                        ])); //写入用户带处理日志
                    } else {
                        $redis->rpush('index:meassage:code:user:receive:' . $task[0]['uid'], json_encode([
                            'task_no'        => trim($task[0]['task_no']),
                            'status_message' => $stat,
                            'message_info'   => $message_info,
                            'mobile'         => trim($message['mobile']),
                            // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                            'send_time'      => isset($message['receive_time']) ? date('Y-m-d H:i:s', trim($message['receive_time'])) : date('Y-m-d H:i:s', time()),
                        ])); //写入用户带处理日志
                    }
                }
                // $send_log['stat'] = $stat;
                // print_r($send_log);

                // {"mobile":"13637077496","mar_task_id":401771,"content":"\u3010\u5170\u853b\u4f1a\u5458\u5c0f\u52a9\u624b\u3011\u79cb\u5206\u65f6\u8282\uff0c\u7a7a\u6c14\u6108\u53d1\u5e72\u71e5\uff0c\u6ce8\u610f\u4fdd\u6e7f\u5f88\u91cd\u8981\uff01\n\u5c0f\u52a9\u624b\u6e29\u99a8\u63d0\u9192\uff0c\u4e0d\u4ec5\u6bcf\u5929\u8981\u559d8\u676f\u6c34\uff0c\u808c\u80a4\u4e5f\u8981\u6ce8\u610f\u8865\u6c34\u54e6~\u5170\u853b\u613f\u60a8\u62e5\u6709\u6c34\u6da6\u808c\u80a4\uff0c\u4e50\u4eab\u79cb\u5929\uff01","from":"yx_user_send_task","send_msg_id":"03000620020200922133406169555","uid":278,"send_num":100,"task_no":"mar20092213340439853926","develop_code":"3647","my_submit_time":1600760315,"Msg_Id":"260455910423723120","Stat":"DELIVRD","Submit_time":"0101010000","Done_time":"2009221538","receive_time":1600760321,"develop_no":"","stat":"DELIVRD"}
                $send_log = [];
                $send_log = [
                    'mobile' => trim($message['mobile']),
                    'mar_task_id' => $task[0]['id'],
                    'content' => $task[0]['task_content'],
                    'from' => "yx_user_send_task",
                    'send_msgid'        => $task[0]['send_msg_id'],
                    'uid'        => $task[0]['uid'],
                    'send_num'        => $task[0]['send_num'],
                    'task_no'        => $task[0]['task_no'],
                    'develop_code'        => $task[0]['develop_no'],
                    'my_submit_time'        => time() - mt_rand(0, 30),
                    'Msg_Id'        => $message['Msg_Id'],
                    'Stat'        => $message['Stat'],
                    'Submit_time'        => $message['Submit_time'],
                    'Done_time'        => $message['receive_time'],
                    'develop_no'        => $task[0]['develop_no'],
                    'stat'        => $message['Stat'],
                ];
                $redis->rpush('index:meassage:code:cms:deliver:' . $channel_id, json_encode($send_log)); //写入通道处理日志
            }
        } catch (\Exception $th) {
            //throw $th;
            $redis->rpush('index:meassage:code:unknow:deliver:' . $channel_id, json_encode($message));
            exception($th);
        }
    }

    public function unkonwnDeliverTest($channel_id)
    {
        try {
            ini_set('memory_limit', '3072M');
            //code...
            $redis = Phpredis::getConn();
            $i = 1;
            // $redis->rpush('index:meassage:code:unknow:deliver:145', '{"Stat":"DELIVRD","Submit_time":"0101010000","Done_time":"2009221445","mobile":"13810041198\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000","receive_time":1600757136,"Msg_Id":"260432473667660"}');
            $status = Db::query("SELECT `mobile`,`status_message` FROM `messagesend`.`yx_send_code_task_receipt` WHERE `task_id` IN (SELECT id FROM `messagesend`.`yx_user_send_code_task` WHERE `create_time` >= '1601049600' AND `uid` IN (SELECT id FROM `messagesend`.`yx_users` WHERE `pid` = '137' ) AND `create_time` <= '1601301600') GROUP BY `mobile`,`status_message`");
            // echo count($status);
            // die;

            $insert_data = [];
            // die;
            foreach ($status as $key => $value) {
                $data = [];
                $data = [
                    'mobile' => $value['mobile'],
                    'status_message' => $value['status_message'],
                ];
                $insert_data[] = $data;
                $i++;
                if ($i > 100) {
                    Db::table('yx_mobile_test')->insertAll($insert_data);
                    $i = 1;
                    $insert_data = [];
                }
            }
            if (!empty($insert_data)) {
                Db::table('yx_mobile_test')->insertAll($insert_data);
            }
            while (true) {
                $message = $redis->lpop('index:meassage:code:unknow:deliver:' . $channel_id);
                if (empty($message)) {
                    // exit('退出');
                    break;
                }
                $message = json_decode($message, true);
                $mobile = trim($message['mobile']);



                $stat = trim($message['Stat']);
                $data = [];
                $data = [
                    'mobile' => $mobile,
                    'status_message' => $stat,
                ];
                $insert_data[] = $data;
                $i++;
                if ($i > 100) {
                    Db::table('yx_mobile_test')->insertAll($insert_data);
                    $i = 1;
                    $insert_data = [];
                };
            }
            // print_r($insert_data);
            // die;
            if (!empty($insert_data)) {
                Db::table('yx_mobile_test')->insertAll($insert_data);
            }
        } catch (\Exception $th) {
            //throw $th;
            $redis->rpush('index:meassage:code:unknow:deliver:' . $channel_id, json_encode($message));
            exception($th);
        }
    }

    public function sendDeliverStatus($j)
    {
        try {
            ini_set('memory_limit', '10240M');
            $task = Db::query("SELECT * FROM `messagesend`.`yx_user_send_task` WHERE `uid` = '278' AND `id` >= '378548' LIMIT " . $j . ",5000");
            // $task = Db::query("SELECT * FROM `messagesend`.`yx_user_send_task` WHERE `uid` = '278' AND `id` >= '378548'");
            if (empty($task)) {
                exit();
            }
            $redis = Phpredis::getConn();
            $status = Db::query('SELECT `mobile`,`status_message` FROM yx_mobile_test GROUP BY `mobile`,`status_message`');
            $mobile = [];
            $mobile_status = [];
            foreach ($status as $key => $value) {
                $mobile[] = $value['mobile'];
                $mobile_status[$value['mobile']] = $value['status_message'];
            }


            foreach ($task as $key => $value) {
                $send_len = 0;
                $send_len = mb_strlen($value['task_content']);
                $s_num = 1;
                if ($send_len > 70) {
                    $s_num = ceil($send_len / 67);
                }
                $mobile_content = [];
                $mobile_content = explode(',', $value['mobile_content']);
                foreach ($mobile_content as $mkey => $mvalue) {
                    if (in_array($mvalue, $mobile)) {
                        /*  $num = mt_rand(0, 45);
                        if ($num < 2) {
                            continue;
                        } elseif ($num >= 2 && $num < 6) {
                            $stat = 'UNDELIV';
                            $message_info = '发送失败';
                        } else {
                            $stat = 'DELIVRD';
                            $message_info = '发送成功';
                        } */
                        $stat = $mobile_status[$mvalue];
                        if (trim($stat) == 'DELIVRD') {
                            $message_info = '发送成功';
                        } else {
                            $message_info = '发送失败';
                        }
                        for ($a = 0; $a < $s_num; $a++) {
                            $redis->rpush('index:meassage:code:user:receive:' . $value['uid'], json_encode([
                                'task_no'        => trim($value['task_no']),
                                'status_message' => $stat,
                                'message_info'   => $message_info,
                                'mobile'         => trim($mvalue),
                                'msg_id'         => trim($value['send_msg_id']),
                                // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                                'send_time'      =>  date('Y-m-d H:i:s', $value['create_time'] + mt_rand(20, 1800)),
                                'smsCount' => $s_num,
                                'smsIndex' => $a + 1,
                            ])); //写入用户带处理日志
                        }
                    } else {
                        $num = mt_rand(0, 45);
                        if ($num < 2) {
                            continue;
                        } elseif ($num >= 2 && $num < 6) {
                            $stat = 'UNDELIV';
                            $message_info = '发送失败';
                        } else {
                            $stat = 'DELIVRD';
                            $message_info = '发送成功';
                        }
                        for ($a = 0; $a < $s_num; $a++) {
                            $redis->rpush('index:meassage:code:user:receive:' . $value['uid'], json_encode([
                                'task_no'        => trim($value['task_no']),
                                'status_message' => $stat,
                                'message_info'   => $message_info,
                                'mobile'         => trim($mvalue),
                                'msg_id'         => trim($value['send_msg_id']),
                                // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                                'send_time'      =>  date('Y-m-d H:i:s', $value['create_time'] + mt_rand(20, 1800)),
                                'smsCount' => $s_num,
                                'smsIndex' => $a + 1,
                            ])); //写入用户带处理日志
                        }
                    }
                }
            }
        } catch (\Exception $th) {
            exception($th);
        }
    }

    public function updateRiverTest()
    {
        try {
            ini_set('memory_limit', '10240M');
            $redis = Phpredis::getConn();
            // $task = Db::query("SELECT * FROM `messagesend`.`yx_user_send_task` WHERE `uid` = '278' AND `id` >= '378548' ");
            $task = Db::query("SELECT * FROM  `messagesend`.`yx_user_send_code_task` WHERE `create_time` >= '1601049600' AND `uid` IN (SELECT id FROM `messagesend`.`yx_users` WHERE `pid` = '137' ) AND `create_time` <= '1601301600'");
            foreach ($task as $key => $value) {
                $mobile = explode(',', $value['mobile_content']);
                $data = [];
                $data['task_no'] = $value['task_no'];
                $data['msg_id'] = $value['send_msg_id'];
                $data['send_time'] = $value['create_time'];
                foreach ($mobile as $mkey => $mvalue) {
                    $data['mobile'] = $mvalue;
                    //    print_r($data);die;
                    $redis->rpush('index:meassage:code:mobile:msg_id:' . $value['uid'], json_encode($data));
                }
            }
        } catch (\Exception $th) {
            //throw $th;
            exception($th);
        }
    }

    public function sendDeliverStatusTest()
    {
        try {
            ini_set('memory_limit', '10240M');
            $redis = Phpredis::getConn();
            /*  $status = Db::query('SELECT `mobile`,`status_message` FROM yx_mobile_test GROUP BY `mobile`,`status_message`');
            $mobile = [];
            $mobile_status = [];
            foreach ($status as $key => $value) {
                // $mobile[] = $value['mobile'];
                // $mobile_status[$value['mobile']] = $value['status_message'];
                $redis->hset('index"mobile:status', $value['mobile'], $value['status_message']);
            }
            die; */
            $all_num = 2589797;
            $success = 0;
            $default = 0;
            $real_un_known = 0;
            $nuknown = 0;
            $nuknown_success = 0;
            $nuknown_default = 0;
            $users = Db::query("SELECT id FROM `messagesend`.`yx_users` WHERE `pid` = '137' ");
            foreach ($users as $key => $value) {
                while (true) {
                    $message = $redis->lpop('index:meassage:code:mobile:msg_id:' . $value['id']);
                    if (empty($message)) {
                        /*  $message = $redis->lpop('index:meassage:code:mobile:msg_id:276');
                        if (empty($message)) {
                            
                        } */
                        break;
                    }
                    $message = json_decode($message, true);
                    $s_num = 2;
                    $status = $redis->hget('index"mobile:status', $message['mobile']);
                    if ($status) {
                        $stat = $status;
                        if (trim($stat) == 'DELIVRD') {
                            $success++;
                            $message_info = '发送成功';
                        } else {
                            $message_info = '发送失败';
                            $default++;
                        }
                        for ($a = 0; $a < $s_num; $a++) {
                            $redis->rpush('index:meassage:code:user:receive_has:' . $value['id'], json_encode([
                                'task_no'        => trim($message['task_no']),
                                'status_message' => $stat,
                                'message_info'   => $message_info,
                                'mobile'         => trim($message['mobile']),
                                'msg_id'         => trim($message['msg_id']),
                                // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                                'send_time'      =>  date('Y-m-d H:i:s', $message['send_time'] + mt_rand(8, 13)),
                                'smsCount' => $s_num,
                                'smsIndex' => $a + 1,
                            ])); //写入用户带处理日志
                        }
                    } else {
                        $real_un_known++;
                        /*  $num = mt_rand(0, 45);
                        if ($num < 2) {
                            $nuknown++;
                            $message = $redis->rpush('index:meassage:code:mobile', json_encode(['mobile' => $message['mobile'], 'status_message' => 'UNKNOWN']));
                            // continue;
                        } elseif ($num >= 2 && $num < 6) {
                            $nuknown_default++;
                            $stat = 'UNDELIV';
                            $message_info = '发送失败';
                            $message = $redis->rpush('index:meassage:code:mobile', json_encode(['mobile' => $message['mobile'], 'status_message' => 'UNDELIV']));
                        } else {
                            $nuknown_success++;
                            $stat = 'DELIVRD';
                            $message_info = '发送成功';
                            $message = $redis->rpush('index:meassage:code:mobile', json_encode(['mobile' => $message['mobile'], 'status_message' => 'DELIVRD']));
                        } */
                        $stat = 'DELIVRD';
                        $message_info = '发送成功';
                        for ($a = 0; $a < $s_num; $a++) {
                            $redis->rpush('index:meassage:code:user:receive:' . $value['id'], json_encode([
                                'task_no'        => trim($message['task_no']),
                                'status_message' => $stat,
                                'message_info'   => $message_info,
                                'mobile'         => trim($message['mobile']),
                                'msg_id'         => trim($message['msg_id']),
                                // 'send_time' => isset(trim($send_log['receive_time'])) ?  date('Y-m-d H:i:s', trim($send_log['receive_time'])) : date('Y-m-d H:i:s', time()),
                                'send_time'      =>  date('Y-m-d H:i:s', $message['send_time'] + mt_rand(8, 13)),
                                'smsCount' => $s_num,
                                'smsIndex' => $a + 1,
                            ])); //写入用户带处理日志
                        }
                    }
                }
            }

            $data = [];
            $data = [
                'all_num' => $all_num,
                'success' => $success,
                'default' => $default,
                'real_un_known' => $real_un_known,
                'nuknown' => $nuknown,
                'nuknown_success' => $nuknown_success,
                'nuknown_default' => $nuknown_default,
            ];
            print_r($data);
        } catch (\Exception $th) {
            //throw $th;
            exception($th);
        }
    }

    public function sendChenckMobile()
    {
        ini_set('memory_limit', '3072M');
        $redis = Phpredis::getConn();
        // $redis->rpush('index:meassage:code:send:0','{"mobile":"13527862529","mar_task_id":3988226,"content":"\u3010\u5170\u853b\u4eac\u4e1c\u81ea\u8425\u65d7\u8230\u5e97\u3011\u611f\u8c22\u60a8\u9009\u62e9\u5170\u853b\u4eac\u4e1c\u81ea\u8425\u65d7\u8230\u5e97\uff01\u4e3a\u4e86\u7ed9\u60a8\u63d0\u4f9b\u66f4\u597d\u7684\u670d\u52a1\uff0c\u8bda\u9080\u60a8\u5bf9\u672c\u6b21\u8d2d\u7269\u4f53\u9a8c\u505a\u51fa\u8bc4\u4ef7\uff1a10\u6ee1\u610f 8\u4e00\u822c 6\u4e0d\u6ee1\u610f\uff1b\u56de\u590d\u6570\u5b57\u53ca\u5177\u4f53\u610f\u89c1\uff0c\u5373\u53ef\u53c2\u4e0e\u8bc4\u4ef7\u3002\n\n\u5173\u6ce8\u5170\u853b\u4eac\u4e1c\u81ea\u8425\u65d7\u8230\u5e97\uff0c\u66f4\u591a\u4e13\u5c5e\u6743\u76ca\u7b49\u4f60\u89e3\u9501\uff01\u56deTD\u9000\u8ba2","from":"yx_user_send_code_task","send_msg_id":"03000420020200923151533169599","uid":276,"send_num":100,"task_no":"bus20092315152892704859","develop_code":"2863"}');
        try {
            while (true) {
                $send = $redis->lpop('index:meassage:code:send:0');
                if (empty($send)) {
                    break;
                }
                $send = json_decode($send, true);
                if ($send['mar_task_id'] < 3988238) {
                    continue;
                }
                $prefix = substr(trim($send['mobile']), 0, 7);

                // $res    = Db::query("SELECT `source`,`province_id`,`province` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
                // $newres = array_shift($res);
                $newres = $redis->hget('index:mobile:source', $prefix);
                $newres = json_decode($newres, true);
                if ($newres) {
                    if ($newres['source'] == 1) { //移动
                        // $channel_id = $yidong_channel_id;
                        $redis->rpush('index:meassage:code:send:143', json_encode($send));
                    } elseif ($newres['source'] == 2) { //联通
                        // $channel_id = $liantong_channel_id;
                        $redis->rpush('index:meassage:code:send:144', json_encode($send));
                    } elseif ($newres['source'] == 3) { //电信
                        // $channel_id = $dianxin_channel_id;
                        $redis->rpush('index:meassage:code:send:144', json_encode($send));
                    }
                } else {
                    $redis->rpush('index:meassage:code:send:143', json_encode($send));
                }
            }
        } catch (\EXception $th) {
            //throw $th;
            exception($th);
        }
    }

    public function receiptCallBackTest()
    {
        ini_set('memory_limit', '3072M');
        $redis = Phpredis::getConn();
        $callback = [
            '{"task_no":"bus20102614293195827633","status_message":"DELIVRD","message_info":"\u53d1\u9001\u6210\u529f","mobile":"18096633693","send_time":"2020-10-26 14:29:38"}',
        ];
        foreach ($callback as $key => $value) {
            $redis->rpush('index:meassage:code:user:receive:190', $value);
        }
    }
}
