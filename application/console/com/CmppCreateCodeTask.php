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
                print_r($send);die;
            }
        }
    }
}
