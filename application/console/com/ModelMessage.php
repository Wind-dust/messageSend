<?php

namespace app\console\com;

use app\console\Pzlife;
use cache\Phpredis;
use Config;
use Env;
use think\Db;

class ModelMessage extends Pzlife {
    private $redis;

    public function __construct() {
        parent::__construct();
        $this->redis = Phpredis::getConn();
    }

    // public function test(){
    //     echo date('Y-m-d H:i:s',-19987200);
    //     exit;
    // }

    /**
     * 营销短信
     * 10分钟执行1次
     *
     */
    public function MarketingActivity() {
        $redisListKey = Config::get('redisKey.modelmessage.redisMarketingActivity');
        $new_marketingactivityList = [];
        while (true) {
            // $this->redis->rPush($redisListKey, 18);
            $marketingactivityId = $this->redis->lPop($redisListKey); //购买会员的订单id
            if (empty($marketingactivityId)) {
                break;
            }
            $getMessageTask = $this->getMessageTask($marketingactivityId);
            if (empty($getMessageTask)) {
                break;
            }
            if ($getMessageTask['start_time']> time()) {
                $new_marketingactivityList[] = $marketingactivityId;
                break;
            }
            if ($getMessageTask['mt_type'] == 1) {//全部人群
                $user = $this->getUserInfo(1);
            }else{
                $user = $this->getUserInfo($getMessageTask['mt_type']);
            }
            $phones = '';
            $t = '';
            foreach ($user as $key => $phone) {
                $phones = $phones.$t.$phone['mobile'];
                $t = ',';
            }
           
            // print_r($getMessageTask['template']);die;
            // $send = $Note->sendContent($phones,$getMessageTask['template']);
            
            $send = sendRequest(Env::get('host.notifyHost').'/note/sendcontent','post',['phones'=> $phones,'content' => $getMessageTask['template']]);
            if ($send) {
                $send = json_decode($send,true);
                // print_r($send);die;
                if ($send['code'] == 200) {
                    Db::table('pz_message_task')->where('id',$getMessageTask['id'])->update(['status' => 4]) ;
                }
            }
            

        }
        foreach ($new_marketingactivityList as $key => $value) {
            $this->redis->rPush($redisListKey, $value);
        }
        exit('ok!!');
    }

    private function getMessageTask($id) {
        $getMessageTaskSql = sprintf("SELECT `mt`.`id`, `mt`.`type` AS `mt_type`, `mt`.`wtype`, `mt`.`mt_id`, `mt`.`trigger_id`, `mt`.`status` AS `mt_status`, `t`.`status` AS `t_status`, `t`.`start_time`, `t`.`stop_time`, `metp`.`template`, `metp`.`type` AS `metp_type`,  `metp`.`status` AS `metp_status`
        FROM
        pz_message_task AS mt
        LEFT JOIN pz_trigger AS t ON `mt`.`trigger_id` = `t`.`id`
        LEFT JOIN pz_message_template AS metp ON `mt`.`mt_id` = `metp`.`id`
        WHERE
        `mt`.`status` = 2 
        AND `mt`.`id` = %d", $id);
        // print_r($getUserSql);die;
        $getMessageTask = Db::query($getMessageTaskSql);
        if (!$getMessageTask) {
            return [];
        }
        return $getMessageTask[0];
    }

    /**
     * @param $uid
     */
    private function getUserInfo($user_identity) {
        if ($user_identity == 1) {
            $get_identity = '(1,2,3,4)';
        }else{
            $get_identity = $user_identity - 1;
            $get_identity = '(1,'.$get_identity.')';
        }
        $getUserSql ="select id,user_type,user_identity,mobile from pz_users where delete_time=0 and mobile <> "."''". " and user_identity in ".$get_identity;
        // print_r($getUserSql);die;
        $userInfo = Db::query($getUserSql);
        if (!$userInfo) {
            return [];
        }
        return $userInfo;
    }

}
