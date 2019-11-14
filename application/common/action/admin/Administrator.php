<?php

namespace app\common\action\admin;

use app\common\action\notify\Note;
use app\facade\DbAdmin;
use app\facade\DbAdministrator;
use app\facade\DbUser;
use cache\Phpredis;
use Config;
use Env;
use think\Db;
use third\PHPTree;

class Administrator extends CommonIndex {
    private $cmsCipherUserKey = 'adminpass'; //用户密码加密key

    private function redisInit() {
        $this->redis = Phpredis::getConn();
//        $this->connect = Db::connect(Config::get('database.db_config'));
    }

    /**
     * @param $page
     * @param $pageNum
     * @return array
     * @author rzc
     */
    public function getBusiness($page, $pageNum, $id = 0, $getall) {
        $offset = ($page - 1) * $pageNum;
        if (!empty($id)) {
            $result = DbAdministrator::getBusiness(['id' => $id], '*', true);
        } else {
            if ($getall == 1) {
                $result = DbAdministrator::getBusiness([], '*', false);
            } else {
                $result = DbAdministrator::getBusiness([], '*', false, '', $offset . ',' . $pageNum);
            }
        }
        return ['code' => '200', 'Business' => $result];
    }

    public function addBusiness($title, $price, $donate_num = 0) {
        $data = [];
        $data = [
            'title'      => $title,
            'price'      => $price,
            'donate_num' => $donate_num,
        ];

        Db::startTrans();
        try {

            $bId = DbAdministrator::addBusiness($data); //添加后的商品id
            if ($bId === false) {
                Db::rollback();
                return ['code' => '3009']; //添加失败
            }
            Db::commit();
            return ['code' => '200', 'goods_id' => $bId];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3009'];
        }
    }

    public function updateBusiness($id, $title, $price, $donate_num = 0) {
        $Business = DbAdministrator::getBusiness(['id' => $id], 'id', true);
        if (empty($Business)) {
            return ['code' => '3001'];
        }
        if (!empty($title)) {
            $data['title'] = $title;
        }
        if (!empty($price)) {
            $data['price'] = $price;
        }
        if (!empty($donate_num)) {
            $data['donate_num'] = $donate_num;
        }
        Db::startTrans();
        try {
            $updateRes = DbAdministrator::editBusiness($data, $id);
            if ($updateRes) {
                Db::commit();
                return ['code' => '200'];
            }
            Db::rollback();
            return ['code' => '3009']; //修改失败
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3009']; //修改失败
        }
    }

    public function getUserQualificationRecord($page, $pageNum, $id) {
        $offset = ($page - 1) * $pageNum;
        if (!empty($id)) {
            $result = DbAdministrator::getUserQualificationRecord(['id' => $id], '*', true);
        } else {
            $result = DbAdministrator::getUserQualificationRecord([], '*', false, '', $offset . ',' . $pageNum);
        }
        return ['code' => '200', 'Business' => $result];
    }

    public function auditUserQualificationRecord($id, $status) {
        $record = DbAdministrator::getUserQualificationRecord(['id' => $id], '*', true);
        if (empty($record)) {
            return ['code' => '3001'];
        }
        if ($record['status'] > 2) {
            return ['code' => '3003'];
        }

        Db::startTrans();
        try {
            $updateRes = DbAdministrator::editUserQualificationRecord(['status' => $status], $id);
            if ($updateRes) {
                if ($status == 3) {
                    unset($record['id']);
                    unset($record['status']);
                    unset($record['update_time']);
                    unset($record['create_time']);
                    unset($record['delete_time']);
                    DbAdministrator::addUserQualification($record);
                    //开通账户使用权限
                    DbUser::updateUser(['user_status' => 2], $record['uid']);
                }
                Db::commit();
                return ['code' => '200'];
            }
            Db::rollback();
            return ['code' => '3009']; //修改失败
        } catch (\Exception $e) {
            // exception($e);
            Db::rollback();
            return ['code' => '3009']; //修改失败
        }
    }

    public function getUserEquities($mobile, $business_id) {
        $user = DbUser::getUserInfo(['mobile' => $mobile], 'id', true);
        if (empty($user)) {
            return ['code' => '3002'];
        }
        $business = DbAdministrator::getBusiness(['id' => $business_id], '*', true);
        if (empty($business)) {
            return ['code' => '3001'];
        }
        $result = DbAdministrator::getUserEquities(['uid' => $user['id'], 'business_id' => $business_id], '*', true);
        return ['code' => '200', 'userequities' => $result];
    }

    public function rechargeApplication($cmsConId, $mobile, $business_id, $num) {
        $adminId = $this->getUidByConId($cmsConId);
        // $adminInfo     = DbAdmin::getAdminInfo(['id' => $adminId], 'id,passwd,status', true);
        $user = DbUser::getUserInfo(['mobile' => $mobile], 'id', true);
        if (empty($user)) {
            return ['code' => '3003'];
        }
        $business = DbAdministrator::getBusiness(['id' => $business_id], '*', true);
        if (empty($business)) {
            return ['code' => '3002'];
        }
        if (!DbAdministrator::getUserEquities(['business_id' => $business_id, 'uid' => $user['id']], 'id')) {
            return ['code' => '3004'];
        }
        $data = [];
        $data = [
            'initiate_admin_id' => $adminId,
            'business_id'       => $business_id,
            'uid'               => $user['id'],
            'mobile'            => $mobile,
            'credit'            => $num,
            'status'            => 1,
        ];

        Db::startTrans();
        try {
            $updateRes = DbAdministrator::addAdminRemittance($data);
            Db::commit();
            return ['code' => '200'];

        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3009']; //修改失败
        }
    }

    public function getRechargeApplication($page, $pageNum, $id = 0, $getall) {
        $offset = ($page - 1) * $pageNum;
        if (!empty($id)) {
            $result = DbAdministrator::getAdminRemittance(['id' => $id], '*', true);
        } else {
            if ($getall == 1) {
                $result = DbAdministrator::getAdminRemittance([], '*', false);
            } else {
                $result = DbAdministrator::getAdminRemittance([], '*', false, '', $offset . ',' . $pageNum);
            }
        }
        return ['code' => '200', 'data' => $result];
    }

    public function aduitRechargeApplication($status, $message, $id) {
        $adminRemittance = DbAdministrator::getAdminRemittance(['id' => $id], '*', true);
        if (empty($adminRemittance)) {
            return ['code' => '3001'];
        }
        if ($adminRemittance['status'] > 1) {
            return ['code' => '3003'];
        }
        $userEquities = DbAdministrator::getUserEquities(['uid' => $adminRemittance['uid'], 'business_id' => $adminRemittance['business_id']], 'id,num_balance', true);
        Db::startTrans();
        try {
            $updateRes = DbAdministrator::editAdminRemittance(['status' => $status, 'message' => $message], $id);
            if ($status == 2) {
                $expenseLog = [];
                $expenseLog = [
                    'uid'         => $adminRemittance['uid'],
                    'business_id' => $adminRemittance['business_id'],
                    'money'       => $adminRemittance['credit'],
                    'befor_money' => $userEquities['num_balance'],
                    'after_money' => bcadd($userEquities['num_balance'], $adminRemittance['credit']),
                    'change_type' => 3,
                ];
                DbAdministrator::addServiceConsumptionLog($expenseLog);
                DbAdministrator::modifyBalance($userEquities['id'], $adminRemittance['credit'], 'inc');
            }
            Db::commit();
            return ['code' => '200'];

        } catch (\Exception $e) {
            exception($e);
            Db::rollback();
            return ['code' => '3009']; //修改失败
        }
    }

    public function getChannel() {
        $result = DbAdministrator::getSmsSendingChannel([], 'id,title', false);
        return ['code' => '200', 'channel_list' => $result];
    }

    public function settingChannel($channel_id, $business_id) {
        $channel = DbAdministrator::getSmsSendingChannel(['id' => $channel_id], 'id,channel_name', true);
        if (empty($channel)) {
            return ['code' => '3001'];
        }
        $business = DbAdministrator::getBusiness(['id' => $business_id], '*', true);
        if (empty($business)) {
            return ['code' => '3002'];
        }
        Db::startTrans();
        try {
            DbAdministrator::editSmsSendingChannel(['business_id' => $business_id], $channel_id);
            Db::commit();
            return ['code' => '200'];

        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3009']; //修改失败
        }
    }

    public function distributeUserChannel($channel_id, $user_phone, $priority) {
        $channel = DbAdministrator::getSmsSendingChannel(['id' => $channel_id], 'id', true);
        if (empty($channel)) {
            return ['code' => '3002'];
        }
        $user = DbUser::getUserInfo(['mobile' => $user_phone], 'id', true);
        if (empty($user)) {
            return ['code' => '3004'];
        }
        if (DbAdministrator::getUserChannel(['uid' => $user['id'], 'channel_id' => $channel_id], 'id', true)) {
            return ['code' => '3005'];
        }
        $data = [];
        $data = [
            'channel_id' => $channel_id,
            'uid'        => $user['id'],
            'priority'   => $priority,
        ];
        Db::startTrans();
        try {
            DbAdministrator::addUserChannel($data);
            Db::commit();
            return ['code' => '200'];

        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3009']; //修改失败
        }
    }

    public function updateUserChannel($id, $priority) {
        $userchannel = DbAdministrator::getUserChannel(['id' => $id], 'id', true);
        if (empty($userchannel)) {
            return ['code' => '3001'];
        }
        Db::startTrans();
        try {
            DbAdministrator::editUserChannel(['priority' => $priority], $id);
            Db::commit();
            return ['code' => '200'];

        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3009']; //修改失败
        }
    }

    public function delUserChannel($id) {
        $userchannel = DbAdministrator::getUserChannel(['id' => $id], 'id', true);
        if (empty($userchannel)) {
            return ['code' => '3001'];
        }
        Db::startTrans();
        try {
            DbAdministrator::delUserChannel($id);
            Db::commit();
            return ['code' => '200'];

        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3009']; //修改失败
        }
    }

    public function getUserSendTask($page, $pageNum, $id) {
        $offset = ($page - 1) * $pageNum;
        if (!empty($id)) {
            $result = DbAdministrator::getUserSendTask(['id' => $id], '*', true);
        } else {
            $result = DbAdministrator::getUserSendTask([], '*', false, '', $offset . ',' . $pageNum);
        }
        return ['code' => '200', 'data' => $result];
    }

    public function auditUserSendTask($id, $free_trial) {
        $userchannel = DbAdministrator::getUserSendTask(['id' => $id], 'id,mobile_content,free_trial', true);
        if (empty($userchannel)) {
            return ['code' => '3001'];
        }
        if ($userchannel['free_trial'] >1) {
            return ['code' => '3003'];
        }
        Db::startTrans();
        try {
            DbAdministrator::editUserSendTask(['free_trial' => $free_trial], $id);
            Db::commit();
            return ['code' => '200'];

        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3009']; //修改失败
        }
    }

    public function distributionChannel($id,$channel_id,$business_id){
        $channel = DbAdministrator::getSmsSendingChannel(['id' => $channel_id,'business_id' => $business_id], 'id,title,channel_price', true);
        if (empty($channel)) {
            return ['code' => '3002'];
        }
        $usertask = DbAdministrator::getUserSendTask(['id' => $id], 'id,uid,mobile_content,task_content,free_trial,send_num,channel_id', true);
        if (empty($usertask)) {
            return ['code' => '3001'];
        }
        $userEquities = DbAdministrator::getUserEquities(['uid' => $usertask['uid'], 'business_id' => $business_id], 'id,agency_price', true);
        if ($usertask['free_trial'] != 2 || $usertask['channel_id']) {
            return ['code' => '3003'];
        }
        $free_trial = 2;
        if ($userEquities['agency_price'] < $channel['channel_price']) {
            $free_trial = 4;
        }
        Db::startTrans();
        try {
            DbAdministrator::editUserSendTask(['free_trial' => $free_trial,'channel_id' => $channel_id], $id);
           
            if ($free_trial == 2) {
                $mobilesend = explode(',',$usertask['task_content']);
                $effective_mobile = [];
                $send_length    = mb_strlen($usertask['task_content'], 'utf8');
                $num = ceil($send_length/65) * $usertask['send_num'];
                DbAdministrator::modifyBalance($userEquities['id'],$num,'dec');
                foreach ($mobilesend as $key => $value) {
                    if (checkMobile(($value))) {
                        $effective_mobile[] = $value;
                    }
                }
                // $redisMessageMarketingSend = Config::get('rediskey.message.redisMessageMarketingSend');
                $redisMessageMarketingSend = Config::get('rediskey.message.redisMessageCodeSend');
                // print_r($redisMessageMarketingSend);die;
                foreach ($effective_mobile as $key => $value) {
                    $res = $this->redis->rpush($redisMessageMarketingSend.":".$channel_id,$value.":".$id.":".$usertask['task_content']); //三体营销通道
                    if ($res == false) {
                        Db::rollback();
                        return ['code' => '3009']; //修改失败
                    }
                    // $this->redis->hset($redisMessageMarketingSend.":2",$value,$id.":".$Content); //三体营销通道
                }
            }
            Db::commit();
            return ['code' => '200'];

        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3009']; //修改失败
        }
    }
}