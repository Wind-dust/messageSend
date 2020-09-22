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
                    $yidong_channel_id   = 0;
                    $yidong_channel_id   = $sendTask['yidong_channel_id'];
                    $liantong_channel_id = 0;
                    $liantong_channel_id = $sendTask['liantong_channel_id'];
                    $dianxin_channel_id  = 0;
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
        $task_id = Db::query("SELECT `id`,`uid` FROM yx_user_multimedia_message WHERE  `id` >= 365214  ");
        foreach ($task_id as $key => $value) {
            $this->redis->rpush("index:meassage:multimediamessage:sendtask", json_encode(['id' => $value['id'], 'deduct' => 35]));
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
                    $yidong_channel_id   = 0;
                    $yidong_channel_id   = $sendTask['yidong_channel_id'];
                    $liantong_channel_id = 0;
                    $liantong_channel_id = $sendTask['liantong_channel_id'];
                    $dianxin_channel_id  = 0;
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
                    $yidong_channel_id   = 0;
                    $yidong_channel_id   = $sendTask['yidong_channel_id'];
                    $liantong_channel_id = 0;
                    $liantong_channel_id = $sendTask['liantong_channel_id'];
                    $dianxin_channel_id  = 0;
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
        $redis->rpush($redisMessageCodeSend, '{"mobile":"15201926171","mar_task_id":"1","content":"Hi, \u4eb2\u7231\u7684\u4f1a\u5458\uff0c\u597d\u4e45\u4e0d\u89c1\uff0c\u60a8\u5df2\u7ecf\u6709\u4e09\u4e2a\u6708\u6ca1\u6765\u62a4\u7406\u4e86\uff0c\u79cb\u51ac\u5df2\u8fd1\uff0c\u6362\u5b63\u5f53\u524d\uff0c\u5728\u808c\u80a4\u9700\u8981\u201c\u8fdb\u8865\u201d\u7684\u5b63\u8282\u91cc\uff0c\u6765\u7f8e\u7530\u5373\u523b\u5f00\u542f\u6df1\u5ea6\u8865\u6c34\u6a21\u5f0f\u5427\uff01\u8054\u7cfb\u60a8\u8eab\u8fb9\u7684\u4e13\u5c5e\u5ba2\u6237\u7ecf\u7406\u6216\u62e8\u6253\u9884\u7ea6\u70ed\u7ebf 400-820-6142 \u56deT\u9000\u8ba2\u3010\u7f8e\u4e3d\u7530\u56ed\u3011","my_submit_time":1597248000,"Msg_Id":"2059229824357040145","Stat":"REJECTD","Submit_time":"2007211521","Done_time":"2007211521","receive_time":1595316110,"from":"yx_user_send_task","uid":"1","send_msg_id":"J343300020200731100217169012"}');
        $redis->rpush($redisMessageCodeSend, '{"mobile":"15201926171","mar_task_id":"1","content":"Hi, \u4eb2\u7231\u7684\u4f1a\u5458\uff0c\u597d\u4e45\u4e0d\u89c1\uff0c\u60a8\u5df2\u7ecf\u6709\u4e09\u4e2a\u6708\u6ca1\u6765\u62a4\u7406\u4e86\uff0c\u79cb\u51ac\u5df2\u8fd1\uff0c\u6362\u5b63\u5f53\u524d\uff0c\u5728\u808c\u80a4\u9700\u8981\u201c\u8fdb\u8865\u201d\u7684\u5b63\u8282\u91cc\uff0c\u6765\u7f8e\u7530\u5373\u523b\u5f00\u542f\u6df1\u5ea6\u8865\u6c34\u6a21\u5f0f\u5427\uff01\u8054\u7cfb\u60a8\u8eab\u8fb9\u7684\u4e13\u5c5e\u5ba2\u6237\u7ecf\u7406\u6216\u62e8\u6253\u9884\u7ea6\u70ed\u7ebf 400-820-6142 \u56deT\u9000\u8ba2\u3010\u7f8e\u4e3d\u7530\u56ed\u3011","my_submit_time":1597248000,"Msg_Id":"2059229824357040146","Stat":"DELIVRD","Submit_time":"2007211521","Done_time":"2007211521","receive_time":1595316110,"from":"yx_user_send_task","uid":"1","send_msg_id":"J343300020200731100217169012"}');
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
        $task_log = Db::query("SELECT * FROM `yx_user_send_code_task` WHERE `id` >= '338053 ' AND `id` <=  '338371' AND `real_num` > '1' AND `task_content` NOT LIKE '%4日9时%' AND `task_content` NOT LIKE '%4日10时%' AND `task_content` NOT LIKE '%4日11时%' AND `task_content` NOT LIKE '%4日12时%' ");
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

            for ($i = 0; $i < $id_num; $i++) {
                $this_id = $i + 1;
                $task_code_receipt = Db::query("SELECT * FROM yx_send_code_task_receipt WHERE `id` = " . $this_id);
                $task = Db::query("SELECT `task_no` FROM yx_user_send_code_task WHERE `id` = " . $task_code_receipt[0]['task_id']);
                if (!empty($task)) {
                    $task_log = Db::query("SELECT * FROM  yx_user_send_code_task_log WHERE `task_no` = '" . $task[0]['task_no'] . "'");
                    if (!empty($task_log)) {
                        if (strpos($task_log[0]['status_message'], 'DB:0141') !== false || strpos($task_log[0]['status_message'], 'MBBLACK') !== false || strpos($task_log[0]['status_message'], 'BLACK') !== false) {
                            $message_info = '黑名单';
                        } else if (trim($task_log[0]['status_message'] == 'DELIVRD')) {
                            $message_info = '发送成功';
                        } else if (in_array(trim($task_log[0]['status_message']), ['REJECTD', 'REJECT', 'MA:0001'])) {
                            $message_info = '发送成功';
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
                        $del_ids[] = $i;
                    }
                }
            }
            die;
            $ids = join(',', $del_ids);
            Db::table('yx_user_send_code_task_log')->where("id in ($ids)")->delete();
        } catch (\Exception $th) {
            exception($th);
        }
    }

    public function Bufa()
    {

        $this->redis = Phpredis::getConn();
        /*  $res = $this->redis->rpush('index:meassage:code:send' . ":" . 62, json_encode([
            'mobile'      => 13278700191,
                                        'title'       => '【优裹徒】快递员13678779299提醒您，请凭567241到D座28号格取件，免费存放24小时',
                                        'mar_task_id' => 1429116,
                                        'content'     => '【优裹徒】快递员13678779299提醒您，请凭567241到D座28号格取件，免费存放24小时',
                                        'from'        => 'yx_user_send_code_task',
        ]));  */
        try {
            $bufa = [
                [
                    'title'       => '【UGG】[测试]亲爱的会员，UGG北京朝北大悦城店即日起至9/9将闭店进行店铺升级改造！在此期间，您可关注UGG官方微信公众号联系在线客服咨询售后或其他相关事宜。店铺将于9/10升级完成后重新开业，届时将有开业活动等着您哦！感谢您对UGG的支持，我们将一如既往的为您提供高品质产品和温馨服务！回TD退订',
                    'content'     => '【UGG】[测试]亲爱的会员，UGG北京朝北大悦城店即日起至9/9将闭店进行店铺升级改造！在此期间，您可关注UGG官方微信公众号联系在线客服咨询售后或其他相关事宜。店铺将于9/10升级完成后重新开业，届时将有开业活动等着您哦！感谢您对UGG的支持，我们将一如既往的为您提供高品质产品和温馨服务！回TD退订',
                    'from'        => 'yx_user_send_task',
                    'mar_task_id' => 359957,
                    'develop_no' => '5241',
                    'msg_id'            => 'J979400020200831113353169747',
                    'mobile_content'      => '13795443695'
                ],
                [
                    'title'       => '【UGG】[测试]亲爱的会员，UGG北京朝北大悦城店即日起至9/9将闭店进行店铺升级改造！在此期间，您可关注UGG官方微信公众号联系在线客服咨询售后或其他相关事宜。店铺将于9/10升级完成后重新开业，届时将有开业活动等着您哦！感谢您对UGG的支持，我们将一如既往的为您提供高品质产品和温馨服务！回TD退订',
                    'content'     => '【UGG】[测试]亲爱的会员，UGG北京朝北大悦城店即日起至9/9将闭店进行店铺升级改造！在此期间，您可关注UGG官方微信公众号联系在线客服咨询售后或其他相关事宜。店铺将于9/10升级完成后重新开业，届时将有开业活动等着您哦！感谢您对UGG的支持，我们将一如既往的为您提供高品质产品和温馨服务！回TD退订',
                    'from'        => 'yx_user_send_task',
                    'mar_task_id' => 359959,
                    'develop_no' => '5241',
                    'msg_id'            => 'J979400020200831113353169749',
                    'mobile_content'      => '18116268711'
                ],
                [
                    'title'       => '【UGG】[测试]亲爱的会员，UGG北京朝北大悦城店即日起至9/9将闭店进行店铺升级改造！在此期间，您可关注UGG官方微信公众号联系在线客服咨询售后或其他相关事宜。店铺将于9/10升级完成后重新开业，届时将有开业活动等着您哦！感谢您对UGG的支持，我们将一如既往的为您提供高品质产品和温馨服务！回TD退订',
                    'content'     => '【UGG】[测试]亲爱的会员，UGG北京朝北大悦城店即日起至9/9将闭店进行店铺升级改造！在此期间，您可关注UGG官方微信公众号联系在线客服咨询售后或其他相关事宜。店铺将于9/10升级完成后重新开业，届时将有开业活动等着您哦！感谢您对UGG的支持，我们将一如既往的为您提供高品质产品和温馨服务！回TD退订',
                    'from'        => 'yx_user_send_task',
                    'mar_task_id' => 359962,
                    'develop_no' => '5241',
                    'msg_id'            => 'J979400020200831113353169752',
                    'mobile_content'      => '18516132556'
                ],
                /*    [
                    'title'       => '【罗氏轻糖俱乐部】亲爱的糖友，免费试纸来袭！请搜索并关注微信公众号“罗氏血糖仪”，免费领取试纸!回TD退订',
                    'content'     => '【罗氏轻糖俱乐部】亲爱的糖友，免费试纸来袭！请搜索并关注微信公众号“罗氏血糖仪”，免费领取试纸!回TD退订',
                    'from'        => 'yx_user_send_task',
                    'mar_task_id' => 299010,
                    'develop_no' => '6258',
                    'msg_id'            => 'J881400020200811102051169019',
                    'mobile_content'      => '15531136888, 15699909009, 17659605091, 13153182708, 18643990707, 13302222686, 13040345654, 13756264351, 13621036936, 13803646013, 13815123918, 13643167581, 18704462975, 13470436894, 13903063311, 18244128583, 13653659461, 15942589568, 13711278213, 18771233835, 13546637893, 18246189235, 13787236138, 13820086594, 13643453671, 13942781496, 15001318270, 15006417551, 15843127981, 13994206193, 13728728446, 18234152129, 13796612388, 15961782995, 13670681472, 13888183835, 13936094058, 15928550693, 13695317029, 13574546324, 13681807578, 13717603221, 13940801399, 13776861292, 15852847266, 13588638787, 15850171838, 13842528899, 13664260119, 18351037019, 15141676447, 13861731103, 15975022584, 18255430002, 13921115697, 13844780364, 13961926699, 13905100037, 13734039890, 18832048410, 13966499864, 15765696869, 13842506164, 13831519293, 13877833288, 13940907893, 13901504717, 15257506830, 15157576201, 13771181628, 15841669522, 13907741188, 13901541534, 13814276810, 13898901770, 13555350096, 15995227264, 13770001772, 13961756403, 13998473862, 13812044233, 13736168087, 13962316053, 15281106003, 15949205321, 13934468289, 15942559555, 13506182335, 13842501568, 13898288807, 13954888597, 15110667732, 18746722291, 15077941685, 13709613272, 13771568085, 13470594321, 13863675814, 13812015776, 15925323596, 15998502443, 15044453332, 13771636251, 18803419520, 15944080666, 13959190797, 18820032928, 13521996860, 13991290690, 13908058828, 13604806982, 15026812686, 13902249383, 15977675007, 13546380643, 13877132591, 13882050906, 13601111857, 13609020395, 15901607086, 13601139908, 13818717994, 15261159111, 13808884299, 13680412686, 15135115616, 13904810311, 13801722703, 13802749775, 13703519638, 13832876660, 13616209112, 15071860000, 13710374517, 13711147704, 13660138388, 13995475678, 15931104715, 13678947295, 15855125304, 13428803328, 13602824864, 13679007061, 13925129181, 13845100906, 15914511968, 13701359333, 13502403684, 18350088768, 13661121265, 13918211222, 13803419365, 13521467863, 18273192698, 15801408169, 15896464479, 13922404733, 15044336666, 13521831600, 15881094888, 15827156242, 15992480595, 13521742827, 13945666676, 13540499320, 13983123148, 13540261553, 15844007770, 13834582538, 18843162234, 13998997782, 13810460476, 13916842287, 13828893980, 15134588375, 15245038391, 13570716416, 13910808994, 13711259653, 13934518718, 18810860636, 18345483279, 13932116681, 13660698321, 13805514623, 13718879213, 13593160358, 13615514031, 18482170630, 13895755378, 15172348979, 13661629986, 13510881033, 13946026349, 13570979579, 13609647686, 13913133674, 13580428736, 13735446052, 13936008003, 15110168009, 13946076219, 13609717215, 13916651433, 13804573275, 15122024005, 13726733061, 13929581505, 13936410355, 18743009157, 18392469605, 13527864278, 13935173285, 13710505478, 13834551569, 13689021298, 13662308860, 18846153192, 13946143200, 15110178956, 18846761553, 13719048898, 13826030577, 15901068025, 13719432976, 13922272919, 13989808418, 13547980252, 13878153359, 18745680618, 13805517568, 13935183868, 13717607373, 13802959230, 15856398788, 13641996938, 13804562092, 15001179975, 13653406815, 13966666696, 15004663127, 13808812125, 13658018268, 13682186168, 13501392948, 13805694378, 15846579586, 13681220096, 18309258508, 13503663831, 13702032928, 13920648129, 13813912861, 18785013157, 13520426985, 13877199185, 13601290118, 13869103595, 13977133217, 13655987207, 13521528879, 18222537725, 13711275174, 13801850733, 13752735092, 13552132008, 13810065842, 13936671000, 13885179177, 13691219431, 13633310287, 13901610873, 13969096197, 15278018379, 13864187200, 13945157912, 13506406969, 13520581551, 13883088176, 13406949462, 13621244960, 13651342966, 13866102119, 18785130689, 13701203578, 13703616180, 15114574153, 13905510216, 13936166281, 18246067546, 13611211165, 13705981676, 18359100257, 13615030399, 13799950233, 13844687531, 18280091810, 13180176414, 15504021245, 13478797216, 13630406618, 13600632637, 13913131754, 13422601833, 18217772325, 19969579224'
                ],
                [
                    'title'       => '【罗氏轻糖俱乐部】亲爱的糖友，免费试纸来袭！请搜索并关注微信公众号“罗氏血糖仪”，免费领取试纸!回TD退订',
                    'content'     => '【罗氏轻糖俱乐部】亲爱的糖友，免费试纸来袭！请搜索并关注微信公众号“罗氏血糖仪”，免费领取试纸!回TD退订',
                    'from'        => 'yx_user_send_task',
                    'mar_task_id' => 299011,
                    'develop_no' => '6258',
                    'msg_id'            => 'J881400020200811102051169021',
                    'mobile_content'      => '18610020250, 13552379541, 13404427201, 13945691915, 13966652444, 18747527527, 15803410771, 13708113225, 15895348009, 13735367750, 13672374225, 13614773113, 13966691803, 13802926783, 13808320933, 13813446110, 15961849967, 13438876742, 13582938575, 13729869280, 13921211367, 13678544168, 15028994938, 15130560808, 15234660888, 13464760288, 13608997867, 13528996933, 13945185077, 13961863236, 13540016608, 13806330996, 15078637899, 13832060273, 13941758652, 13828087111, 15231525569, 13862149758, 18855457503, 13500282363, 13924681925, 15135874447, 13402414567, 15195120123, 13529713526, 15941532309, 13651028352, 13606573456, 18761350399, 13735367877, 13803431292, 13612269984, 13739887777, 13932586888, 15861605723, 13981188844, 13649656857, 13841291567, 13936578180, 15215921162, 13771000312, 13861711526, 15868801601, 15061508500, 13702288562, 15876522081, 15902850992, 13966454910, 15161984161, 13771155988, 13771513666, 13905346610, 13935899768, 13898752728, 13820489209, 15990379691, 13691290082, 15800461168, 13706166229, 13760500279, 13506161257, 18232516013, 13887212540, 15042916006, 15820292154, 13535222459, 13506168579, 13711629111, 15828299936, 15022549790, 15034013818, 13466320299, 13453189963, 18746527527, 13509739088, 13708732465, 13764400002, 13552559890, 15910838050, 13669284191, 13835812810, 13808015256, 13980831666, 13816013257, 15935129796, 13522724602, 13814227706, 15941500185, 15261533962, 13904293727, 13963871977, 13601910251, 13903617572, 13688618450, 13466818052, 13994163200, 13974982959, 13802541174, 18734900100, 13650975589, 13802908670, 13602841373, 15904613443, 13651628819, 13977122254, 18823885679, 13888071916, 18792776108, 13811152610, 13965975257, 13754880102, 15801027382, 13691090562, 13535383903, 15982440612, 13810838683, 13991232888, 13407092408, 13945081010, 13601079960, 15940408987, 18889239999, 18866895672, 13434122178, 13980988522, 13840507060, 13719016879, 13811371565, 13885020067, 15112288456, 13989903897, 13601869441, 13911403666, 13920093101, 13811952547, 13535262639, 13501533899, 13717777688, 13701000813, 13810783633, 13682266816, 13984074013, 13609645997, 13660009334, 13611023012, 13683588912, 13980565508, 13880501006, 13621732726, 13911267572, 13522979902, 13811129991, 15001109661, 13810790393, 15802936690, 15022165777, 13701836293, 13892866776, 13803458799, 13691488722, 13439194350, 15010172683, 13903465718, 13984808705, 13693105892, 15859270980, 13985011950, 13577175269, 15877189696, 13911083881, 18396850673, 13803402495, 13621290776, 13688896159, 15066686677, 15142578988, 18822856615, 15201365121, 13515965739, 13608198208, 15701569725, 13950220093, 15959178007, 15910466366, 13803499103, 13981819545, 15024474341, 13886042189, 18801972105, 15264196222, 15977718442, 18240498777, 13796068787, 13501077700, 15170028297, 13922242148, 18747970221, 18346008121, 13834639298, 18786122057, 13601034639, 13902239694, 13796667888, 15901134925, 13466749430, 15801099611, 13501096002, 13607711272, 13755050849, 13666103949, 15808862733, 18304630678, 18222808680, 13621213178, 13555896089, 18285187574, 15805315705, 13718079625, 18432486131, 13718005062, 13618032336, 13801216659, 15011229693, 13658069745, 13991392698, 13540111097, 15855518351, 13720838328, 13702055110, 13801299032, 13681478141, 13834155458, 18805913889, 15859045148, 13713869800, 13524566170, 13953166849, 15928412075, 13699002938, 13507911345, 13901239784, 13518107270, 13911997229, 13693335940, 13682225246, 13611155621, 13879167599, 18345005886, 13517003730, 15245034596, 13856973534, 18818850868, 15905417766, 13801678691, 15145053455, 13552347728, 13503092257, 13629425624, 13885118290, 18332118786, 13661003989, 13920958269, 13910418125, 15102834676, 13910049419, 13672480739, 13678189692, 18798809748, 18226637988, 13636332135, 13705697253, 15911055780, 13808096916, 13953116166, 13845090965, 13503510478, 13609517856, 15959184854, 15542866511, 18605927819, 18989096650, 15386735678, 17730568893, 18903404999, 15333664678, 15988272687, 15601033541, 13101119011'
                ],
                [
                    'title'       => '【罗氏轻糖俱乐部】亲爱的糖友，免费试纸来袭！请搜索并关注微信公众号“罗氏血糖仪”，免费领取试纸!回TD退订',
                    'content'     => '【罗氏轻糖俱乐部】亲爱的糖友，免费试纸来袭！请搜索并关注微信公众号“罗氏血糖仪”，免费领取试纸!回TD退订',
                    'from'        => 'yx_user_send_task',
                    'mar_task_id' => 299012,
                    'develop_no' => '6258',
                    'msg_id'            => 'J881400020200811102051169022',
                    'mobile_content'      => '13146125273, 14918234166, 15683170885, 13376609111, 15608743511, 17640465156, 13910614073, 13962540372, 13619684910, 15855141227, 13916926209, 15124290099, 13651350075, 15184595521, 13701143471, 15069139708, 15251566345, 15067890186, 13909906777, 13683197323, 13770048886, 13961795897, 13905548830, 18898907988, 15100557296, 15861588342, 15934611057, 13870690232, 13966655689, 16653125599, 15963268717, 13808115288, 15906192707, 13702608335, 13702823042, 13915216218, 13803441229, 13814200312, 13932606078, 13842588018, 13861827306, 18342055360, 18820503888, 13770079318, 13771446163, 13961723072, 18841704191, 13771480820, 15805106050, 15296648125, 15842557008, 13815503366, 15161631383, 13834869891, 15255423255, 13841517028, 15148889169, 13989152169, 13814252584, 15961938099, 13555564853, 13858423735, 13500778802, 13890298906, 13887996611, 15050678195, 13701514960, 13861731111, 13522005622, 13979480922, 13656188666, 13584130333, 13710723630, 13725130638, 13945980589, 13831016825, 13970062144, 13807786681, 18330580645, 13529468016, 13464163526, 13902597748, 13612259635, 15183229402, 18316470137, 13470016823, 13771060044, 13815582898, 13805318655, 13881958669, 13933885575, 13603259866, 18410201769, 13956041726, 13998459287, 13771052187, 13977156505, 13879246212, 13605541333, 18264463700, 18341713171, 13851054592, 18701113891, 18818155857, 13710178620, 13576953617, 13898927508, 13550006285, 13708921182, 15942956969, 13915202803, 13816568986, 13708939164, 13961775609, 13640301727, 13502093459, 13956641688, 13861815572, 13688047711, 13606180787, 13584119941, 15835118642, 13943368355, 19803308899, 18855186276, 13809552634, 13906017886, 13908011230, 13631451636, 13609742322, 13501106826, 13683121810, 13624072270, 13693340954, 13928729335, 13521388032, 13904051895, 13610143486, 13683022626, 13826455252, 13533884187, 13908012800, 13935127772, 13910755208, 13856977576, 13882265663, 13668292521, 13901716651, 13693590745, 15010336327, 13926138828, 18842962228, 13679092983, 13826490213, 15108243999, 13704518646, 13902276143, 13618067786, 13818642608, 18764120622, 13832335959, 15022545476, 13511911789, 13901680939, 13891873467, 15951938367, 13935115550, 13905511876, 18807915288, 13761942973, 13801024326, 13621899758, 13931898474, 13755167199, 13810230105, 13623674885, 13822152289, 13533028228, 13718770992, 13840428577, 15803410711, 13668283533, 13901175319, 13552674364, 13870964331, 13922156136, 13623667684, 13928750005, 13933052992, 13602060058, 13803083962, 15200011760, 15907717609, 13901056757, 13711382468, 13695659316, 15288244732, 13700546058, 18201066781, 15064135119, 15811223231, 13466817234, 13687913176, 13980061106, 13520251629, 15970696421, 18201285122, 13920216421, 18203511083, 13901629832, 13980580796, 13910980387, 13910325061, 13802770997, 15013309257, 13501359470, 18734826831, 13643608031, 13693135936, 13875965355, 13725190289, 13601396830, 15940439687, 13855176657, 13984836565, 13708076222, 18853146813, 13808002566, 18227651232, 13816355446, 15933316783, 13700033888, 13992871125, 13902992914, 13681849658, 13978862476, 13803402055, 13980676936, 18803415557, 13501501779, 15834019758, 13660027234, 13655609890, 13660590613, 13936600695, 13801217818, 13903053092, 14739337500, 13450434029, 13920192376, 13836023006, 18776883866, 13522456874, 13538796290, 13864049366, 15866755117, 13924008713, 13730858131, 18842579528, 13668251778, 13682283250, 13660713979, 13621387217, 13940055181, 15979002160, 15918709244, 15017584501, 15011458869, 13621699265, 13459228089, 13533287683, 13539730682, 13903510479, 13517887168, 15810342300, 13955126288, 13908225899, 13436865338, 13860190913, 15159280252, 13520140979, 18745692820, 13806096506, 13960373556, 13665012827, 13860473334, 13799266415, 13502112693, 13720837256, 15060677685, 15959009377, 13705037878, 13799940536, 13339735981, 13332838033, 17645130482'
                ],
                [
                    'title'       => '【罗氏轻糖俱乐部】亲爱的糖友，免费试纸来袭！请搜索并关注微信公众号“罗氏血糖仪”，免费领取试纸!回TD退订',
                    'content'     => '【罗氏轻糖俱乐部】亲爱的糖友，免费试纸来袭！请搜索并关注微信公众号“罗氏血糖仪”，免费领取试纸!回TD退订',
                    'from'        => 'yx_user_send_task',
                    'mar_task_id' => 299013,
                    'develop_no' => '6258',
                    'msg_id'            => 'J881400020200811102051169020',
                    'mobile_content'      => '13600048665, 13934012963, 13558722677, 13873036185, 13422067851, 13671173782, 15987338289, 13961897603, 13708250839, 15006195071, 13614692166, 13784815788, 13571029609, 13466460629, 18746532160, 13671174986, 13981191057, 13456568618, 13568269556, 13961671627, 13772067980, 13908989788, 13862502920, 13609018420, 15246780962, 13808898910, 13660681803, 13552389748, 18351556200, 13473764632, 13955442538, 13659513009, 13812198360, 15900410420, 15283218082, 13956016580, 13403576200, 13934135775, 15862096170, 13674666838, 13771285920, 13668120760, 13861718327, 13549266683, 13505518108, 18360497088, 13981413009, 18351184149, 15995787874, 13610190137, 13913523965, 13901107777, 15288293889, 13702278015, 15955444521, 13989948242, 13846754376, 18348676333, 13513332880, 18896556698, 13989289760, 13936346078, 13584857942, 13961892660, 13611078287, 13626234573, 13717510888, 15109263843, 13631430722, 13901241606, 13829276653, 15104678084, 13771546088, 13841684952, 13772717637, 13825093083, 15974890348, 13955455546, 18895691156, 15120041505, 18345036111, 13418110622, 13885111000, 13994267216, 13961783671, 13500735959, 15131525158, 13910871443, 13678127591, 13702200053, 13608074563, 13861702721, 13691500015, 13981589377, 15071349259, 13511925081, 15951201059, 15145066110, 18311285718, 13584854016, 13691358273, 13785519998, 18835598985, 13760657610, 13861685365, 15018495879, 18841580899, 13811675931, 13464725531, 13618980369, 13934239288, 13909018808, 18818398369, 13516077288, 13908997979, 15104472437, 13809770198, 13821987223, 13695546570, 13500457826, 13601523388, 18200376103, 13662177378, 13582856502, 15852711695, 13582651524, 13701213579, 13835173356, 18725650293, 18281886485, 13725470354, 18384706049, 15005510605, 13831558966, 18701492995, 15807711330, 13961591857, 13908949008, 13989902333, 13684501928, 15086991666, 13836128757, 13552108150, 13942520704, 13711759188, 13804017852, 15923505078, 13662373013, 13645543828, 13989946671, 15061550918, 13583167061, 18734833000, 13671218645, 13898542855, 13541504371, 13980054816, 13961829829, 13982083181, 13565466966, 13402110898, 13661329229, 15834156289, 13626227531, 13882974788, 15861610016, 13991969877, 18834830127, 18326070512, 13554518736, 18734896333, 13570961752, 13662501809, 13867560212, 13711185691, 13513697843, 13701685610, 13878185855, 13581989695, 13430271790, 13981115991, 13803468312, 13901143675, 13969078089, 13921119589, 13644644121, 15961619354, 13752048810, 13918013307, 13845061487, 15822168864, 13981936561, 13832988869, 13457947888, 13985165780, 13545106126, 13913740161, 13503548952, 13546464777, 15097508561, 13636394305, 18888351403, 13834628266, 13696522660, 15103405918, 18822227777, 13576105656, 13946162663, 13505197609, 13911062631, 13602850821, 13708879722, 13614057948, 13853106886, 18235159803, 13754883649, 13955130398, 13633418682, 13682194276, 13931972583, 13430363192, 13716420283, 13903003485, 13901383556, 13936321919, 13794314655, 13977101263, 13527685413, 13651959247, 13946199136, 15856900581, 18249991233, 13601020229, 13796708565, 15843023251, 13520476689, 15031198318, 15232180278, 13468755048, 15066656888, 13934624023, 15810813060, 13908625767, 13673169974, 15129182601, 13980897482, 13910082157, 18801060803, 18435149885, 13905519753, 15953178769, 13648915953, 13521889538, 13903066653, 13754880282, 13619316702, 15156686692, 13893260354, 13826267539, 13600067236, 13645653045, 15801039715, 18302212576, 15045635630, 15866622177, 15835128842, 13820761388, 15245156875, 13617684757, 13660368183, 15803216251, 18244045151, 13522509579, 15918408708, 13798141818, 13422394231, 13817224427, 13678041581, 13651200555, 13767180744, 13911792603, 13840109430, 15045622770, 13880796275, 13910817316, 13969089522, 18334778940, 15920884233, 13982098033, 13681181121, 13965116602, 13540185670, 13613644515, 13716025819, 15120047182, 18289016667, 13730670481, 13509183558, 13826203426, 13505315562, 13882229809, 13985051308, 13684512806, 13980993225, 13883263008, 13695014885, 18326143162, 15711550021, 15980806667, 13705976501, 13675086577, 18750114976, 13774659161, 18359532442, 13515028170, 13606054016, 13380980078, 13309280641, 13174326165, 17709897407, 15327125317, 18101461759, 13720839945, 15504657377, 13294514426'
                ],
                [
                    'title'       => '【罗氏轻糖俱乐部】亲爱的糖友，免费试纸来袭！请搜索并关注微信公众号“罗氏血糖仪”，免费领取试纸!回TD退订',
                    'content'     => '【罗氏轻糖俱乐部】亲爱的糖友，免费试纸来袭！请搜索并关注微信公众号“罗氏血糖仪”，免费领取试纸!回TD退订',
                    'from'        => 'yx_user_send_task',
                    'mar_task_id' => 299014,
                    'develop_no' => '6258',
                    'msg_id'            => 'J881400020200811102052169023',
                    'mobile_content'      => '18606750382, 13141261372, 18536669843, 13075940637, 18691653770, 18986120931, 18986056162, 18986836789, 13353433761, 13912767798, 15885006581, 15828807790, 13640001721, 15811538680, 15124554821, 13935110833, 13601722761, 18741515673, 13940524232, 13921254112, 13793610586, 13666134959, 13400033048, 13405765300, 18807701818, 13884701976, 13818881892, 13832880069, 13784800138, 13842584358, 13812020882, 13756212693, 18341028640, 18346527972, 15025455011, 13626202308, 13801855677, 13609833821, 15161615713, 13718216216, 13638177777, 13633660880, 13911257397, 13683451809, 15055926966, 15244672899, 13704652005, 13961890030, 13500036682, 13806253709, 15961829244, 15102277028, 13700334200, 13771590522, 13665107007, 15010680975, 13760856119, 13404153675, 15196839507, 15034111347, 13808022056, 13614657580, 13604799951, 15855776876, 13855424455, 13989075558, 13892977607, 13946025757, 18304590691, 15081531665, 15941580748, 13862501861, 13941544632, 13901254996, 13610025898, 13596954156, 13625311266, 13632287178, 15011388758, 13601232202, 13861727822, 13901501952, 13940168673, 13691009967, 13621948735, 13817719928, 15135340013, 13936336575, 15810867562, 13522792969, 18853168087, 15804502763, 13955459217, 13962548980, 13767973999, 18855430995, 13945057613, 13904503973, 13865920156, 13505604811, 15001040696, 13802751698, 13981936663, 15100550816, 13865028508, 13836006289, 15161633130, 15941594254, 13922667037, 13785505511, 13895740196, 13961289417, 13904809437, 13832980668, 13638518646, 13895788622, 13552346055, 13865002603, 13750304246, 13840041565, 13907154167, 13827730090, 18795616480, 15046066886, 13998195575, 13842584297, 13784806356, 13832920585, 13610029688, 15153130820, 13931434516, 18786654939, 13508901619, 13929039996, 13402534810, 13914700123, 15841505784, 13955169148, 13812064735, 15176580000, 15755290033, 15898082611, 13622025008, 15050291078, 13956379387, 13845573329, 15103402446, 13966050122, 13962552833, 13584151960, 13835106425, 18201124184, 13602764903, 15852573290, 13855237566, 18731509032, 15041509234, 13771778000, 15048975111, 15804501814, 13464500235, 15840388845, 13908933919, 13581706071, 13601790742, 15715525133, 15832025957, 18245101830, 15046491957, 13604899567, 13965060608, 13556184705, 13812559253, 13913699969, 13940466406, 13702278677, 13603683001, 15901692127, 13840598195, 13706758421, 13610327793, 15952497979, 13933330918, 13521279245, 15045622193, 13902264599, 15216692095, 15802485016, 13985446318, 13903059866, 13801330508, 13811127512, 13515319306, 13971618517, 13589080455, 15210662006, 13946035099, 13621258563, 13644506401, 13940029099, 13908714770, 13683375279, 18844594222, 13880801006, 13683030231, 13704815000, 13940098139, 13767091237, 13911980416, 13427609213, 13956069446, 13822298165, 18710129558, 15145029931, 13835166538, 13437801191, 13505157983, 13901123133, 13693027967, 15927633966, 18714609124, 13817098828, 13611297990, 13439837799, 15945191192, 13717973710, 13503025525, 13521415277, 13541006470, 13909690586, 13681180433, 13936362211, 15822812459, 15114680723, 13924236171, 13936407001, 13662455922, 13936529620, 18245129017, 13705609197, 15945173786, 13550211616, 15810114473, 18766146417, 15855136803, 13808061848, 13682123325, 13703642529, 13834166545, 13593159745, 13753122338, 13488661965, 13805690686, 13694550777, 13689076164, 13620427707, 13999977720, 13936528589, 15180810550, 13637998197, 13880495583, 13966691796, 13488805010, 15035163727, 13717831841, 13909697348, 13998330070, 13904815391, 13970991766, 13840429716, 13709716638, 13666211387, 13843153668, 15864012631, 15000601648, 15705319166, 15035199958, 13693160369, 13866727528, 13804547128, 13488690047, 15712863004, 13719120481, 15110322796, 13834111796, 13994212245, 13485367496, 13596189110, 15104568218, 15940139398, 13703643100, 15260097311, 13950001516, 13856008880, 13683242537, 13507881781, 13840167763, 13520506376, 13521699602, 13552608724, 13998173102, 13958166797, 13889826070, 13604811116, 15104009991, 13560004561, 13920478936, 13801180423, 13945133236, 13501129288, 13552033130, 13770798666, 13940284773, 13870869516, 19912311055, 13703649238, 13704805305, 15168867122, 15806020483, 13155279966, 15998300608, 15070852296, 13910009566, 15275185875, 18401250874, 13815262658, 13956451629, 13936139317, 13019808954'
                ],
                [
                    'title'       => '【罗氏轻糖俱乐部】亲爱的糖友，免费试纸来袭！请搜索并关注微信公众号“罗氏血糖仪”，免费领取试纸!回TD退订',
                    'content'     => '【罗氏轻糖俱乐部】亲爱的糖友，免费试纸来袭！请搜索并关注微信公众号“罗氏血糖仪”，免费领取试纸!回TD退订',
                    'from'        => 'yx_user_send_task',
                    'mar_task_id' => 299015,
                    'develop_no' => '6258',
                    'msg_id'            => 'J881400020200811102052169024',
                    'mobile_content'      => '13204119098, 18603419861, 13177922847, 18002288831, 13900056787, 15833575059, 15895303058, 13640892229, 13509718325, 13513636386, 13400003083, 13791974520, 13771606957, 13754365063, 13904288169, 13575588188, 13961833956, 13533338351, 13969032712, 15941267714, 13568676666, 13587300187, 13941515698, 13702583534, 13915266779, 13785579259, 13515149005, 15187033129, 13771100032, 13702289914, 13735334798, 18820877027, 13771192353, 13842916279, 13601394346, 15140334765, 13855743838, 13898943033, 13611214040, 13842641625, 13933147287, 13637111961, 15045980421, 15995290687, 13814986886, 15073409027, 15840857167, 13568765643, 13844788358, 13992953198, 18242979999, 13544996075, 13901525282, 13855491522, 15206182950, 15156634044, 13674553033, 15020981977, 13754558012, 13905545867, 13894270113, 13616753324, 13632076229, 13834386687, 13644550800, 15151494143, 15855402748, 13504103192, 13909731593, 13602385997, 13624848576, 13644950321, 15923133778, 13812031401, 13488809182, 13803500821, 13585061914, 15821411032, 13770008232, 13841430878, 13904484497, 13972482209, 15850080368, 13501162257, 13683567367, 13596059309, 13438169383, 13601729405, 13946085956, 13807658198, 13991576282, 18342585188, 15920317470, 15863179737, 13907173117, 13910890505, 13965079911, 15124505877, 13778168878, 13881068777, 15052209017, 13436892971, 18245100009, 13804017545, 13897862525, 13522781615, 15190208603, 13546248532, 15004689927, 18330176638, 13737154733, 13668922883, 13708432898, 13601071935, 13903073381, 13904636783, 13932589045, 13770126558, 13771144999, 15930853336, 13946036633, 13946068249, 13970818325, 13851180290, 13804511310, 13901329380, 13547699539, 13918035828, 13642682037, 15164671765, 13601125417, 13552768931, 13810906718, 13544351112, 13651036176, 13550002598, 13512779550, 15801299587, 13982272772, 13650781243, 13765647682, 15251581037, 13603562965, 13844993974, 13555377768, 15165152936, 13691191725, 13683337624, 13918358379, 13705314911, 13898989951, 13759161962, 18352470727, 13808881700, 13711390195, 15882331228, 13701622227, 13903493311, 15034144338, 13660755016, 13760806869, 13853132811, 13908081611, 13513647508, 15004291392, 13529439220, 18866861866, 15124543437, 18753132055, 15201994759, 15934048621, 13593247768, 13866661746, 15982005455, 13403510958, 13901653184, 13701922128, 13811404457, 13521049512, 15804516467, 13710509299, 13804512284, 13804577396, 18701969835, 13833199035, 13717828025, 13835197111, 15910335807, 15168881992, 13922743796, 13765134924, 15045988219, 13601123141, 13503680337, 13660637283, 15974152713, 13543448022, 13917975988, 15913141795, 13611009861, 13766816616, 15904338590, 13882081201, 13826448364, 13817883526, 13835178368, 15022778770, 13661113348, 13878181381, 13651890000, 13607866688, 15198883216, 13910508187, 13570372339, 13426319375, 15810318275, 13618099739, 15920568920, 13560329007, 13650708237, 13600076289, 15102803245, 13453149259, 13679091855, 13919005281, 13844858886, 13909238223, 13898891989, 13801060706, 13908078398, 13956094855, 13701276887, 13653685700, 15201077202, 18246053798, 13509485065, 13571918097, 13845182888, 13550075975, 15802291118, 15185013420, 13418582869, 13835112086, 13675113616, 13617654581, 13691134045, 13977166037, 13926025181, 13689039906, 13716124665, 13701318580, 13642638865, 13802031984, 13517666665, 13834243133, 13908074588, 13754820248, 15910829316, 13901787870, 13700505500, 13808196668, 13701168190, 18798020960, 13901054036, 13552611861, 13901195619, 13935188392, 18764177791, 13930428187, 13665418772, 13703623183, 13834567161, 13473960000, 13485321805, 13922468076, 13503545199, 13823318163, 13820993690, 13683535371, 13701124886, 13668227869, 15209841115, 13880876448, 13521021158, 13935169636, 13661115365, 13653418530, 13603580799, 13651291137, 18765316898, 13608046297, 13903651133, 15253139090, 15959228828, 15260128715, 15005030976, 15205003693, 15060119079, 13805009132, 13850163236, 15080007797, 13665068230, 13950192423, 13599518183, 15880106710, 18995645802, 18241240583, 13922499953, 13934158556, 13601385603, 13661192131, 13907815801, 13621381525, 13920817962, 18246004900, 13960523977, 13779929923, 15504293602, 15663639573'
                ],
                [
                    'title'       => '【罗氏轻糖俱乐部】亲爱的糖友，免费试纸来袭！请搜索并关注微信公众号“罗氏血糖仪”，免费领取试纸!回TD退订',
                    'content'     => '【罗氏轻糖俱乐部】亲爱的糖友，免费试纸来袭！请搜索并关注微信公众号“罗氏血糖仪”，免费领取试纸!回TD退订',
                    'from'        => 'yx_user_send_task',
                    'mar_task_id' => 299016,
                    'develop_no' => '6258',
                    'msg_id'            => 'J881400020200811102052169025',
                    'mobile_content'      => '15120026675, 13969041516, 13903468015, 18718808595, 13661074191, 15901447057, 13505755886, 18297906016, 13710932182, 15022519161, 18468094310, 13641220190, 13928933293, 13835192561, 13759443185, 18701227936, 13718047282, 13801516380, 13672904037, 13934192999, 13573078143, 13910590206, 13656188802, 13584289841, 15975787472, 13921187462, 18799395739, 13793353797, 15206173465, 13622244733, 13908881769, 18855273677, 13716052496, 13988750075, 18842981688, 13921353382, 13914013950, 18855471543, 13862581991, 13730731888, 15918099696, 15231998086, 13845906786, 15834245236, 13672961975, 15150268968, 18205855056, 13955414016, 13787505757, 13955458311, 15922000636, 13913700394, 15082853000, 13422773626, 13616195098, 13921217719, 15241893179, 15262345458, 13936834411, 13835705711, 13470343047, 15852533095, 13769000651, 15735008639, 18355428898, 13771097302, 15102419030, 13887801146, 13793803788, 13575508029, 13631894055, 13759388799, 13987935582, 13470088478, 18780593655, 13575588502, 13822423119, 13962162220, 13841550977, 13877418233, 15895118728, 13626238165, 13861862155, 13431784500, 15850211966, 13836895727, 18260151141, 13644187416, 13722680034, 15046794222, 13812088296, 13913709183, 18211148385, 13962168278, 13922419290, 13880881557, 13934645505, 13988742093, 13645690560, 13951563958, 13903519311, 13678264337, 13925006506, 15235170175, 15862692866, 13682209922, 15077195016, 13771977000, 13913229128, 13804258756, 13814896728, 15873720549, 13810165588, 13877957272, 13521573092, 13616281636, 15889981038, 13567553273, 13711276668, 13661938038, 13956910052, 13916782883, 15921730988, 15803517775, 13570443345, 15808853065, 13822252881, 18217285981, 15010566824, 13711602998, 13853160665, 13802977627, 13842557991, 18835120918, 13501762297, 13708877302, 13834204629, 13928722086, 15156018866, 13759431211, 13439267200, 13802763083, 13708796491, 13544951277, 13969853967, 15910880929, 18707710825, 15034141141, 13801392634, 13585681573, 13929588468, 13521669540, 15010249381, 13693197327, 13920851128, 13640257823, 13503540259, 13888751564, 13888535200, 13802987222, 13888496609, 13922239978, 18702875375, 15925195955, 13631400126, 18855180926, 13546470321, 18326166866, 13787018697, 13705513844, 13902401776, 13708891991, 13458568861, 13629674796, 13533018963, 13925083407, 13602703812, 15102900001, 13457072779, 15034063096, 18818397663, 13803455772, 13439084212, 13711181435, 13603566251, 13951805864, 13522643216, 15810192208, 13701053896, 13568823795, 13922733169, 13660420738, 13888735496, 13922773536, 15217453328, 13711553242, 15201053659, 13878890011, 13426167807, 13651692977, 13791041617, 18328688351, 13518719185, 13955179631, 13978692913, 18787056973, 13700655978, 15853199638, 13925016414, 13522586119, 15811523153, 13660758917, 13716675515, 13691373231, 13911932079, 13909717008, 15062270958, 13908193859, 13980726095, 13725221207, 18825222240, 13675603868, 15210359291, 13934207833, 13671094539, 13802789364, 13633459102, 13602127977, 13953188208, 13905602706, 15977112260, 13699172527, 15928510606, 13926002161, 15851829849, 15982073305, 15834050639, 18284565849, 13533500919, 13802953195, 13903410078, 13708454272, 13621150182, 18756026126, 13946050996, 15834167310, 13662454843, 13605603299, 13621098740, 13513513034, 13546362746, 13716582093, 15198724701, 13556194520, 13759409116, 13934115906, 13802915109, 13501202998, 13980636011, 13503032011, 13466629563, 13934521981, 13878108168, 13702105862, 13829721143, 13922175218, 13518100255, 13718003323, 13966666614, 13611309986, 13611090275, 13708477995, 13769172881, 18705609008, 13660266396, 13820844860, 15035102911, 13610262329, 13817950725, 13808005223, 13753196578, 13691044839, 13434349097, 15878170874, 13919004400, 13908840047, 13520307920, 13546116802, 18310826923, 18234086559, 13623676104, 15043074647, 13611459863, 18255118370, 13551009083, 13966687488, 13472785768, 13517719890, 18340037836, 13693416669, 13436305033, 15882419943, 13661082579, 13672435558, 13801157729, 13835120212, 13916792366, 13882245447, 13959245960, 13960753061, 13675035395, 15005913324, 13950300697, 18515835967, 18035410666, 18922007469, 18103517308, 18078853472'
                ],
                [
                    'title'       => '【罗氏轻糖俱乐部】亲爱的糖友，免费试纸来袭！请搜索并关注微信公众号“罗氏血糖仪”，免费领取试纸!回TD退订',
                    'content'     => '【罗氏轻糖俱乐部】亲爱的糖友，免费试纸来袭！请搜索并关注微信公众号“罗氏血糖仪”，免费领取试纸!回TD退订',
                    'from'        => 'yx_user_send_task',
                    'mar_task_id' => 299017,
                    'develop_no' => '6258',
                    'msg_id'            => 'J881400020200811102052169026',
                    'mobile_content'      => '13213142323, 18026399373, 18035133827, 18617189233, 18641580245, 18018778857, 18916340808, 18185122944, 15584648976, 18000425137, 13385113807, 13672941098, 13915209905, 13568262163, 13771132658, 13841564637, 18297977862, 13819586838, 13870878895, 13719306603, 18242937580, 13802609156, 13887469398, 13700352170, 17824777066, 13934610358, 15834969999, 13833391110, 15033157777, 15883319118, 13841556745, 13528313003, 13756285748, 18854825877, 15041564675, 18741556747, 13961665651, 15950424990, 13631855328, 15917881901, 13909896472, 13932566655, 13858422333, 13432226094, 15815627305, 15841567375, 15163359280, 15809002685, 13812574797, 13668927144, 13508129503, 13881256663, 15176616727, 15141558521, 13642702784, 13464595673, 15167519270, 13841506076, 13907376965, 15804263048, 15941504049, 18258538266, 13439568669, 13572567958, 13832848280, 15862970536, 13918045402, 15104506511, 13831182636, 13632057499, 15041530175, 13809220035, 13808816057, 13994056358, 13719194737, 15852589007, 15963119600, 13990194913, 15190253787, 13680446795, 13812039379, 13842940672, 13403440199, 15905103389, 13904299080, 15958734605, 17875710863, 13906176110, 15950428053, 13654156403, 13680767606, 13905695100, 13812588529, 15114266866, 13955417897, 13998957852, 13861829383, 15042948701, 13699065958, 13660304898, 13861618870, 13867557371, 13889535601, 15104150812, 15925164792, 15121387668, 15852835363, 13992119157, 13956923410, 13623642572, 15169181139, 13577152469, 13934520452, 15915813682, 13845068815, 13994250392, 13602812997, 13832111727, 13613477592, 13513629161, 18785105511, 13453402445, 18302087778, 15874170261, 13408652228, 13820884673, 13620404311, 13403696570, 13611073560, 13610231873, 13611288502, 13711451088, 13760736029, 13683015881, 13541330846, 13956945456, 15005516709, 13653385026, 13994215211, 18346122201, 13970866786, 13585597620, 13619627535, 13818095736, 13990478369, 13660823129, 13668177770, 13613609800, 13540735112, 13711284384, 15920533903, 15911693307, 13980091991, 13805603850, 13903071284, 13719377388, 15828077785, 18745194519, 13701824635, 18208530315, 13488918977, 15902856308, 13834140199, 13710720500, 13980417945, 18392423149, 15142875484, 13980952055, 13879190712, 13535008928, 13956930022, 13596474127, 13911605601, 15020016100, 13980995913, 13651357376, 13841127311, 13533556630, 13936581006, 13501389565, 13936399797, 13691359600, 15821686669, 13503014498, 15901089032, 13992899208, 15176990624, 13611361360, 13969128803, 13521066205, 13602782293, 13701374710, 15045016618, 13718045279, 13728000779, 13597001566, 18740016136, 13505518026, 13521412355, 13888093450, 15096677859, 15902843268, 13711288061, 13901041103, 13551025377, 13934920117, 13905541027, 15807319869, 15212786069, 15776606886, 13985152136, 13520493014, 13701092158, 15204506562, 15046078393, 13604847027, 13636345416, 13910848509, 13504516990, 13538954038, 13834149921, 13678955521, 13811801084, 13982033818, 13801986061, 13608069327, 13987148602, 13969007875, 13549008703, 13856992360, 13880241360, 13870691637, 15104643661, 13855441687, 18795660061, 13980666176, 13974910278, 13501546219, 13621898299, 13614514608, 13898521465, 13661084499, 13888716482, 13822141389, 13908918267, 13985164538, 13426346888, 13945198855, 13651305397, 13905514044, 13466634247, 15056906490, 13691694210, 18805317555, 15118881434, 13966658297, 13711129305, 13593154206, 13601795633, 13582007667, 13527802838, 13621019383, 13709040990, 17835613168, 13984120415, 13881729662, 13510856111, 13801046760, 18889799362, 13622288027, 13678819430, 13808099023, 13580500010, 13922141307, 15887800103, 13956055886, 18366111385, 13503037709, 13641173420, 13520350202, 15820283133, 15234086345, 13488187366, 13701152059, 13878116565, 13633669383, 13529378680, 13980434501, 13616517618, 13865972717, 13611043297, 13845079495, 15180533686, 13558880503, 13894879754, 13836112917, 13955008855, 13981903228, 15735174769, 13840165672, 15985809606, 18250759877, 13615059082, 15959293938, 13960701083, 13950043489, 18750221156, 13609555721, 15859866233, 15805910999, 15259179875, 15980659040, 13606022582, 13515048698, 13763832933, 13779968517, 15980969607, 15880013265, 13720822081, 13559343806, 15960099969, 15995195188, 13941576039, 15913153228, 13992836189, 13708413760, 18521268086'
                ],
                [
                    'title'       => '【罗氏轻糖俱乐部】亲爱的糖友，免费试纸来袭！请搜索并关注微信公众号“罗氏血糖仪”，免费领取试纸!回TD退订',
                    'content'     => '【罗氏轻糖俱乐部】亲爱的糖友，免费试纸来袭！请搜索并关注微信公众号“罗氏血糖仪”，免费领取试纸!回TD退订',
                    'from'        => 'yx_user_send_task',
                    'mar_task_id' => 299018,
                    'develop_no' => '6258',
                    'msg_id'            => 'J881400020200811102052169027',
                    'mobile_content'      => '13901983084, 15114678383, 13867558680, 15114644788, 13668933762, 13483513127, 13926221269, 13655545824, 15041599053, 13602099165, 15941564379, 13570945568, 13981187342, 13902881835, 13921120174, 15948089122, 18846186789, 13621784054, 13956028736, 13901714247, 18435813666, 13936314395, 18776140678, 15818835930, 13568468293, 15152208874, 13826284618, 13855130878, 13809401932, 13901824690, 13530458123, 13717293877, 13841580009, 13427207972, 13912786423, 15911958567, 13845137016, 13989592262, 13775191001, 15846385498, 13654507074, 13773278847, 13424907140, 18372673788, 18704505846, 13608027155, 15010977138, 13936555540, 13861808519, 13826215746, 13802602428, 13771719118, 13861664127, 13801696585, 13691107518, 13936732389, 15944339321, 15135088656, 13503652008, 13998990017, 15834143249, 13942980388, 13953177750, 13506200768, 15896488325, 13841500794, 13608278369, 13400008871, 13880550326, 13845113392, 13771032333, 13944871915, 15104290526, 15040991711, 13608709916, 13813666023, 13626217598, 13628006080, 15928184702, 13796185111, 13901898811, 13765986245, 13801370462, 18280353722, 15852528188, 13500573933, 13894810701, 13806111492, 18200157000, 13936628651, 15950952172, 13808178916, 13808119989, 13711277872, 15861577807, 13866180418, 13801511989, 13501609679, 13450174602, 13516281251, 13908713990, 13936973957, 18444193333, 15145111111, 15856691221, 13914329252, 13942939858, 13834401120, 13982292255, 15141952726, 13611309678, 13691460237, 13768416909, 13758556414, 13411060755, 13921536661, 18235155459, 13915894324, 13908474588, 13546295557, 13615316895, 13924028398, 13918747970, 13982195136, 15246574111, 13470689472, 13707712668, 15098706512, 13553158969, 13946992165, 13700511113, 13640811599, 15704338882, 13901169120, 13560079087, 13771084994, 13693212970, 15034459046, 13934149817, 13945926797, 15901010289, 13934514748, 13771457721, 13957508999, 13529595188, 15913614388, 13903010663, 13814836317, 13841526673, 13842529430, 15161508353, 13857532601, 13524897877, 15054362637, 13708192680, 13981108718, 13552735112, 13905607462, 18360423943, 13520751404, 15852805819, 13945954495, 13555555040, 13640218011, 13945065375, 13834674688, 13527613386, 13984091412, 15089747761, 13710202323, 13558787913, 15855416669, 13982128882, 13805315629, 13402624513, 15280939999, 13432224276, 13805546288, 13942992053, 13739229158, 13842991023, 15001814294, 18845873538, 13405778689, 15043335033, 15835320306, 13918209743, 13683276704, 18345084225, 13978652611, 13808009650, 13943329900, 13661376491, 13904439738, 13908021805, 13921296857, 13711400008, 13621295068, 13908926777, 13861761683, 13632211069, 13955120899, 13685232056, 18328556501, 13978122589, 15026499575, 13505105273, 13662369295, 13982063758, 13994206477, 13771999652, 15917365256, 15822792537, 15904763558, 18704156625, 13882087218, 13901152034, 13869187768, 13842930737, 18358505235, 13861620663, 13550262536, 15161977787, 13663611048, 13842914565, 13470659393, 13961621578, 13407718348, 13965076388, 13700540573, 13819500888, 13438029054, 13927619006, 13672499198, 13610182375, 13936325157, 13836000230, 13955495088, 13754826573, 13729830934, 13719087893, 13805744767, 13703687618, 13796657099, 13501923851, 13703515464, 15168821510, 15877166423, 13612141456, 13821851431, 13651886853, 13722875005, 13500027279, 13601081199, 13585635574, 13785163088, 13902238023, 13910879340, 13669269643, 13803437206, 18246186992, 13524557333, 13845099053, 13691034087, 13632255244, 13889367816, 15834054956, 18721529851, 13991287626, 13636387165, 13691166184, 15853152297, 13969121607, 13964065808, 13533005040, 13544482525, 13826212250, 13888701258, 15960846787, 18750940534, 15880106949, 13506003119, 13489118318, 13950184261, 13578666546, 18208885295, 13974883158, 13901632259, 18853117088, 18745685624, 13840141976, 13752589662, 13934233701, 13901117196, 13977163398, 13717975804, 15196689925, 13718206059, 13668808848, 15528797779, 13318809361, 13114263316, 13365918430, 18649651169, 18927581877, 13894338993'
                ], */
            ];
            foreach ($bufa as $key => $value) {
                # code...
                $mobile_content = explode(',', $value['mobile_content']);

                foreach ($mobile_content as $mkey => $mvalue) {
                    $prefix = '';
                    $prefix = substr(trim($mvalue), 0, 7);
                    $res    = Db::query("SELECT `source`,`province_id`,`province` FROM `yx_number_source` WHERE `mobile` = '" . $prefix . "'");
                    // print_r($res);
                    $channel_id = 0;
                    if ($res) {
                        $newres = array_shift($res);
                        if ($newres['source'] == 1) {
                            $channel_id = 132;
                        } elseif ($newres['source'] == 2) {
                            $channel_id = 132;
                        } elseif ($newres['source'] == 3) {
                            $channel_id = 132;
                        }
                    } else {
                        $channel_id = 18;
                    }
                    $sendmessage = [];
                    $sendmessage = [
                        'msg_id'      => $value['msg_id'],
                        'title'      => $value['title'],
                        'mobile'      => $mvalue,
                        'mar_task_id' => $value['mar_task_id'],
                        'content'     => $value['content'],
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
            $mysql_connect->table('yx_sfl_send_task')->where([['create_time', '>', $tody_time]])->update(['free_trial' => 2, 'yidong_channel_id' => 156, 'liantong_channel_id' => 157, 'dianxin_channel_id' => 157, 'update_time' => time()]);
            // $mysql_connect->table('yx_sfl_send_task')->where([['template_id', '=', '100183639']])->update(['free_trial' => 2, 'yidong_channel_id' => 83, 'liantong_channel_id' => 84, 'dianxin_channel_id' => 84, 'update_time' => time()]);
            // $mysql_connect->table('yx_sfl_send_task')->where([['template_id', '=', '100183187']])->update(['free_trial' => 2, 'yidong_channel_id' => 83, 'liantong_channel_id' => 84, 'dianxin_channel_id' => 84, 'update_time' => time()]);
            /* $where = [];
            $where = [['create_time','>',$tody_time],['template_id', '<>','100150821']];
            $mysql_connect->table('yx_sfl_send_task')->where($where)->update(['free_trial' => 2, 'yidong_channel_id' => 86, 'liantong_channel_id' => 88, 'dianxin_channel_id' => 87]);*/
            $sendid = $mysql_connect->query("SELECT `id` FROM yx_sfl_send_task WHERE `create_time` >  '" . $tody_time . "' AND `template_id` NOT IN ('100184476','100184475')  ");
            // $sendid = $mysql_connect->query("SELECT `id` FROM yx_sfl_send_task WHERE `template_id` = '100184231'   ");
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
        $deduct = 1; //1扣量,2不扣
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
                if (in_array(trim($value['mobile']), $white_list)) {
                    continue;
                }
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
            $Received =  [
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
            ];
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
                $redis->rpush('index:meassage:code:send:85', json_encode([
                    'mobile'  => 15201926171,
                    'content' => "【钰晰科技】客户[future]回执推送失败请紧急查看并协调解决！！！时间" . date("Y-m-d H:i:s", time())
                ]));
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
                        $receipt = $redis->lpop('index:meassage:code:user:receive:' . $value['id']);
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
                if ($redis->LLEN('index:meassage:code:receive_for_future_default') > 0) {
                    $redis->rpush('index:meassage:code:send:85', json_encode([
                        'mobile'  => 15201926171,
                        'content' => "【钰晰科技】客户[future]回执推送失败请紧急查看并协调解决！！！时间" . date("Y-m-d H:i:s", time())
                    ]));
                }
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
                /*  $base_receipt = Db::query("SELECT * FROM yx_user_multimedia_message_log WHERE `uid` = 223 ");
                foreach ($base_receipt as $bkey => $bvalue) {
                    $receipt = [];
                    if (in_array($bvalue['status_message'], $Received)) {
                        $receipt['status_message'] = 'DELIVRD';
                        $receipt['message_info'] = '发送成功';
                    } elseif ($bvalue['status_message'] == 'DELIVRD') {
                        $receipt['status_message'] = 'DELIVRD';
                        $receipt['message_info'] = '发送成功';
                    } elseif (!empty($bvalue['status_message'])) {
                        $receipt['status_message'] = $bvalue['status_message'];
                        $receipt['message_info'] = '发送失败';
                    } else {
                        continue;
                    }
                    $mul_task = Db::query("SELECT `send_msg_id` FROM yx_user_multimedia_message WHERE `task_no` = '" . $bvalue['task_no'] . "' ")[0];
                    $receipt['msg_id'] = $mul_task['send_msg_id'];
                    $receipt['mobile'] = $bvalue['mobile'];
                    $receipt['send_time'] = date('Y-m-d H:i:s', $bvalue['update_time']);
                    $receipt['smsCount'] = 1;
                    $receipt['smsIndex'] = 1;
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
                            }
                        }
                        $all_report = '';
                        $receipt_report = [];
                        $j = 1;
                    }
                } */

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
                if ($redis->LLEN('index:meassage:code:receive_for_future_default') > 0) {
                    $redis->rpush('index:meassage:code:send:85', json_encode([
                        'mobile'  => 15201926171,
                        'content' => "【钰晰科技】客户[future]回执推送失败请紧急查看并协调解决！！！时间" . date("Y-m-d H:i:s", time())
                    ]));
                }
                sleep(1);
            }
        } catch (\Throwable $th) {
            //throw $th;
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
                // $start_time = strtotime('2020-02-05 0:00:00');

                $start_time   = strtotime("-3 day");
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
                    $code_task_log = Db::query("SELECT * FROM yx_user_send_task_log WHERE `uid` IN (SELECT `id` FROM yx_users WHERE `pid` = 137) AND `status_message` = '' AND `create_time` >= '" . $start_time . "' AND  `create_time` <= '" . $end_time . "' LIMIT 1 ");
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
                    if ($redis->LLEN('index:meassage:code:receive_for_future_default') > 0) {
                        $redis->rpush('index:meassage:code:send:85', json_encode([
                            'mobile'  => 15201926171,
                            'content' => "【钰晰科技】客户[future]回执推送失败请紧急查看并协调解决！！！时间" . date("Y-m-d H:i:s", time())
                        ]));
                    }
                    // echo 'Over' . "\n";
                    sleep(10);
                }
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
                $user = Db::query("SELECT `pid`,`need_receipt_cmpp` FROM yx_users WHERE `id` = " . $task[0]['uid']);
                if (Db::query("SELECT `id` FROM yx_send_task_receipt WHERE  `mobile` = '" . $mobile . "' AND `task_id` = '" . $task[0]['id'] . "' ")) {
                    continue;
                }
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
}
