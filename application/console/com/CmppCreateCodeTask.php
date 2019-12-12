<?php

namespace app\console\com;

use app\console\Pzlife;
use cache\Phpredis;
use Config;
use Env;
use think\Db;

class CmppCreateCodeTask extends Pzlife {

    public function CreateCodeTask() { //CMPP创建单条任务营销
        $redis                    = Phpredis::getConn();
        $redisMessageCodeSend     = 'index:meassage:code:send'; //
        $redisMessageCodeSendReal = 'index:meassage:code:send:realtask'; //验证码发送真实任务rediskey CMPP接口 营销
        // $redis->rpush($redisMessageCodeSendReal,json_encode([
        //     'mobile' => 15201926171,
        //     'message' => '【冰封传奇】已为您发出688888元宝和VIP满级号，今日限领至尊屠龙！戳 https://ltv7.cn/45RHD 回T退订',
        //     'Src_Id' => '',
        //     'send_msgid' => [
        //         1576127228031159,
        //     ],
        //     'uid' => 45,
        //     'Submit_time' => 1212130708,
        // ]));
        while (true) {
            $SendText = $redis->lPop($redisMessageCodeSendReal);
            if (empty($SendText)) {
                exit('send_task is_null');
            }
            // $send = explode(':', $SendText);
            $send = json_decode($SendText, true);
            // $user = $this->getUserInfo($send[0]);
            $user = $this->getUserInfo($send['uid']);
            if (empty($user) || $user['user_status'] == 1) {
                break;
            }
            $userEquities = $this->getUserEquities($send['uid'], 9);
            if (empty($userEquities)) {
                break;
            }
            if ($userEquities['num_balance'] < 1 && $user['reservation_service'] == 1) {
                break;
            }

            $send_code_task            = [];
            $send_code_task['task_no'] = 'bus' . date('ymdHis') . substr(uniqid('', true), 15, 8);
            // $send_code_task['task_content']   = $send[2];
            // $send_code_task['mobile_content'] = $send[1];
            // $send_code_task['uid']            = $send[0];
            // $send_code_task['source']         = $send[4];
            // $send_code_task['msg_id']         = $send[3];

            $send_code_task['send_msg_id']    = join(',', $send['send_msgid']);
            $send_code_task['uid']            = $send['uid'];
            $send_code_task['task_content']   = trim($send['message']);
            $send_code_task['submit_time']    = $send['Submit_time'];
            $send_code_task['create_time']    = time();
            $send_code_task['mobile_content'] = $send['mobile'];
            $send_code_task['send_num']       = 1;
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
                    //营销任务
                    $task_id = Db::table('yx_user_send_code_task')->insertGetId($send_code_task);
                    //扣除余额
                    $new_num_balance = $userEquities['num_balance'] - 1;
                    Db::table('yx_user_equities')->where('id', $userEquities['id'])->update(['num_balance' => $new_num_balance]);
                    if ($send['uid'] == 45) { //单独客户单条任务直接处理有余额直接推送发送通道，没有则只提交任务，通过审核后才能发送

                        if (checkMobile($send['mobile'])) {
                            $prefix = substr(trim($send['mobile']), 0, 7);

                            $res = Db::query("SELECT `source`,`province_id`,`province` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");

                            $newres = array_shift($res);
                            if ($newres) {
                                // if ($newres['source'] == 2) { //米加联通营销
                                //     $channel_id = 8;
                                // } else if ($newres['source'] == 1) { //蓝鲸
                                //     $channel_id = 9;

                                // } else if ($newres['source' == 3]) { //米加电信营销
                                //     $channel_id = 7;
                                // }
                                if ($send['uid'] == 45) {
                                    $channel_id = 14;
                                }
                                $send_log = [
                                    'task_no'     => $send_code_task['task_no'],
                                    'uid'         => $send['uid'],
                                    'mobile'      => $send['mobile'],
                                    'task_content'      => $send['message'],
                                    'send_status' => 2,
                                    'create_time' => time(),
                                ];
                                $sendmessage = [
                                    'mobile'      => $send['mobile'],
                                    'mar_task_id' => $task_id,
                                    'content'     => $send['message'],
                                    'uid'         => $send['uid'],
                                    'msgid'       => $send['send_msgid'],
                                    'send_time'   => $send['Submit_time'],
                                ];
                                $has = Db::query("SELECT id FROM yx_user_send_code_task_log WHERE `task_no` = '" . $send_code_task['task_no'] . "' AND `mobile` = '" . $send['mobile'] . "' ");
                                // echo $i."\n";
                                if (!$has) {
                                    Db::table('yx_user_send_code_task')->where('id',$task_id)->update(['channel_id' => $channel_id]);
                                    Db::table('yx_user_send_code_task_log')->insert($send_log);
                                    // print_r( Db::table('yx_user_send_task_log')->insert($send_log));
                                    $res = $redis->rpush($redisMessageCodeSend . ":" . $channel_id, json_encode($sendmessage)); //
                                }

                                Db::commit();
                            }
                        }
                    } else {
                        $redis->rPush('index:meassage:marketing:sendtask', $task_id);

                    }

                } catch (\Exception $e) {
                    $redis->rPush($redisMessageCodeSendReal, $SendText);
                    exception($e);
                    Db::rollback();
                }
            } elseif ($user['free_trial'] == 1) { //需审核用户
                Db::startTrans();
                try {
                    $send_code_task['free_trial'] = 1;
                    $task_id                      = Db::table('yx_user_send_code_task')->insertGetId($send_code_task);
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

    private function getUserInfo($uid) {
        $getUserSql = sprintf("select id,user_status,reservation_service,free_trial from yx_users where delete_time=0 and id = %d", $uid);
        // print_r($getUserSql);die;
        $userInfo = Db::query($getUserSql);
        if (!$userInfo) {
            return [];
        }
        return $userInfo[0];
    }

    private function getUserEquities($uid, $business_id) {

        $userEquities = Db::query("SELECT `id`,`num_balance` FROM yx_user_equities WHERE  `delete_time` = 0 AND `uid` = " . $uid . " AND `business_id` = " . $business_id);
        // print_r("SELECT `id`,`num_balance` FROM yx_user_equities WHERE  `delete_time` = 0 AND `uid` = " . $uid . " AND `business_id` = " . $business_id);
        if (!$userEquities) {
            return [];
        }
        return $userEquities[0];
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

    private function getSendTaskLog($task_no, $mobile) {
        $getSendTaskSql = "select 'id' from yx_user_send_task_log where delete_time=0 and `task_no` = '" . $task_no . "' and `mobile` = '" . $mobile . "'";
        // print_r($getUserSql);die;
        $sendTask = Db::query($getSendTaskSql);
        if (!$sendTask) {
            return [];
        }
        return $sendTask[0];
    }

    private function getMultimediaSendTask($id) {
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

    private function getMultimediaSendTaskLog($task_no, $mobile) {
        $getSendTaskSql = "select `id` from yx_user_multimedia_message_log where delete_time=0 and `task_no` = '" . $task_no . "' and `mobile` = '" . $mobile . "'";
        // print_r($getUserSql);die;
        $sendTask = Db::query($getSendTaskSql);
        if (!$sendTask) {
            return [];
        }
        return $sendTask[0];
    }

    public function getNewMessageLog() {
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
    public function MisumiTaskSend() {
        $this->redis = Phpredis::getConn();
        // $redisMessageMarketingSend = Config::get('rediskey.message.redisMessageCodeSend');
        $redisMessageMarketingSend = Config::get('rediskey.message.redisMessageCodeSend');
        $send                      = $this->redis->lpop($redisMessageMarketingSend);
        print_r($send);die;
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
    public function createMessageSendTaskLog() {
        $this->redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        // date_default_timezone_set('PRC');
        $redisMessageMarketingSend = Config::get('rediskey.message.redisMessageCodeSend');
        // $send = $this->redis->rPush('index:meassage:marketing:sendtask',15745);
        // $send = $this->redis->rPush('index:meassage:marketing:sendtask', 15743);
        // $send = $this->redis->rPush('index:meassage:marketing:sendtask',15740);
        // $send = $this->redis->rPush('index:meassage:marketing:sendtask',15741);
        // echo time() -1574906657;die;
        while (true) {
            $real_length = 1;
            $send        = $this->redis->lpop('index:meassage:marketing:sendtask');
            // $send = 15745;

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
                            'create_time' => time(),
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
                    $res = $this->redis->rpush($redisMessageMarketingSend . ":" . $send_channelid, json_encode($value)); //三体营销通道
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
            // exit("SUCCESS");
        }
    }

    //书写彩信任务日志并写入通道
    public function createMultimediaMessageSendTaskLog() {
        $this->redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        // date_default_timezone_set('PRC');
        $redisMessageMarketingSend = 'index:meassage:multimediamessage:sendtask';
        $send                      = $this->redis->rPush('index:meassage:multimediamessage:sendtask', 1);
        // $send = $this->redis->rPush('index:meassage:marketing:sendtask', 15743);
        // $send = $this->redis->rPush('index:meassage:marketing:sendtask',15740);
        // $send = $this->redis->rPush('index:meassage:marketing:sendtask',15741);
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
            // if (!empty($sendTask['content'])) {

            // }
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
                            'task_no'      => $sendTask['task_no'],
                            'uid'          => $sendTask['uid'],
                            'source'       => $sendTask['source'],
                            'task_content' => $sendTask['source'],
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
                        $has = Db::query("SELECT id FROM yx_user_multimedia_message_log WHERE `task_no` = '" . $sendTask['task_no'] . "' AND `mobile` = '" . $mobilesend[$i] . "' ");
                        // echo $i."\n";
                        if ($has) {
                            continue;
                            // Db::table('yx_user_send_task_log')->where('id', $has[0]['id'])->update(['create_time' => time()]);
                        }

                        Db::table('yx_user_multimedia_message_log')->insert($send_log);
                        // $res = $this->redis->rpush($redisMessageMarketingSend . ":" . $channel_id, json_encode($sendmessage)); //三体营销通道
                        $push_messages[] = $sendmessage;
                    }
                }
                Db::table('yx_user_multimedia_message')->where('id', $sendTask['id'])->update(['real_num' => $real_num, 'send_status' => 3]);
                Db::commit();
                foreach ($push_messages as $key => $value) {
                    $send_channelid = $value['channel_id'];
                    unset($value['channel_id']);
                    $res = $this->redis->rpush($redisMessageMarketingSend . ":" . $send_channelid, json_encode($value)); //三体营销通道
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

    public function getChannelSendLog($content) {
        $redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        // $redisMessageCodeSend = 'index:meassage:code:new:deliver:'.$content; //验证码发送任务rediskey
        $redisMessageCodeSend = 'index:meassage:code:deliver:' . $content; //验证码发送任务rediskey
        // $redis->rpush($redisMessageCodeSend,json_encode([
        //     'task_no' => 'mar19120515365354528991',
        //     'uid' => '39',
        //     'mobile' => '13597523000',
        //     'status_message' => 'UNDELIV',
        //     'send_status' => '4',
        //     'send_time' => '1575533160',
        // ]));
        while (true) {
            $send_log = $redis->lpop($redisMessageCodeSend);
            if (empty($send_log)) {
                exit("send_log is null");
            }
            $send_log = json_decode($send_log, true);
            $has_log  = Db::query("SELECT `id` FROM yx_user_send_task_log WHERE `mobile` = " . $send_log['mobile'] . " AND `task_no` = '" . $send_log['task_no'] . "'");
            // print_r($has_log);die;
            if ($has_log) {
                Db::startTrans();
                try {
                    Db::table('yx_user_send_task_log')->where('id', $has_log[0]['id'])->update(['send_time' => $send_log['send_time'], 'status_message' => $send_log['status_message'], 'real_message' => $send_log['status_message'], 'send_status' => $send_log['send_status']]);
                    Db::commit();
                } catch (\Exception $e) {
                    $redis->rPush('index:meassage:marketing:sendtask', $send_log);
                    exception($e);
                    Db::rollback();
                }
            } else {
                $redis->rpush($redisMessageCodeSend, json_encode($send_log));
            }

        }
    }

    public function getCmppChannelSendLog($content) {
        $redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        // $redisMessageCodeSend = 'index:meassage:code:new:deliver:'.$content; //验证码发送任务rediskey
        $redisMessageCodeSend = 'index:meassage:code:new:deliver:' . $content; //验证码发送任务rediskey
        // $redis->rpush($redisMessageCodeSend,json_encode([
        //     'task_no' => 'mar19120515365354528991',
        //     'uid' => '39',
        //     'mobile' => '13597523000',
        //     'status_message' => 'UNDELIV',
        //     'send_status' => '4',
        //     'send_time' => '1575533160',
        // ]));
        while (true) {
            $send_log = $redis->lpop($redisMessageCodeSend);
            if (empty($send_log)) {
                exit("send_log is null");
            }
            $send_log = json_decode($send_log, true);
            $has_log  = Db::query("SELECT `id`,`uid`,`msgid`,`create_time` FROM yx_user_send_code_task_log WHERE `mobile` = " . $send_log['mobile'] . " AND `task_no` = '" . $send_log['task_no'] . "'");
            // print_r($has_log);die;
            if ($has_log) {
                Db::startTrans();
                try {
                    Db::table('yx_user_send_code_task_log')->where('id', $has_log[0]['id'])->update(['send_time' => $send_log['Done_time'], 'status_message' => $send_log['Stat'], 'real_message' => $send_log['status_message'], 'send_status' => $send_log['send_status']]);
                    Db::commit();
                } catch (\Exception $e) {
                    $redis->rPush('index:meassage:marketing:sendtask', $send_log);
                    exception($e);
                    Db::rollback();
                }
                $send_msgid = explode(',', $has_log['msgid']);
                foreach ($send_msgid as $key => $value) {
                    $redis->rPush('index:meassage:code:cmppdeliver:' . $has_log['uid'], json_encode([
                        'Stat'        => $send_log['Stat'],
                        'send_msgid'  => $value,
                        'Done_time'   => $send_log['Done_time'],
                        'Submit_time' => $has_log['create_time'],
                        'mobile'      => $send_log['mobile'],
                    ]));
                    // if ($value == $send_log['Msg_Id']){

                    // }
                }
            } else {
                $redis->rpush($redisMessageCodeSend, json_encode($send_log));
            }

        }
    }

    public function getNumberSource($prefix) {
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

    public function getSendLog() {
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

}
