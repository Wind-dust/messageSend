<?php

namespace app\common\action\admin;

use app\common\action\notify\Note;
use app\facade\DbAdmin;
use app\facade\DbSendMessage;
use app\facade\DbAdministrator;
use app\facade\DbUser;
use cache\Phpredis;
use Config;
use Env;
use Exception;
use think\Db;
use third\PHPTree;

class Administrator extends CommonIndex
{
    private $cmsCipherUserKey = 'adminpass'; //用户密码加密key

    private function redisInit()
    {
        $this->redis = Phpredis::getConn();
        //        $this->connect = Db::connect(Config::get('database.db_config'));
    }

    /**
     * @param $page
     * @param $pageNum
     * @return array
     * @author rzc
     */
    public function getBusiness($page, $pageNum, $id = 0, $getall)
    {
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

    public function addBusiness($title, $price, $donate_num = 0)
    {
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

    public function updateBusiness($id, $title, $price, $donate_num = 0)
    {
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

    public function getUserQualificationRecord($page, $pageNum, $id)
    {
        $offset = ($page - 1) * $pageNum;
        if (!empty($id)) {
            $result = DbAdministrator::getUserQualificationRecord(['id' => $id], '*', true);
        } else {
            $result = DbAdministrator::getUserQualificationRecord([], '*', false, '', $offset . ',' . $pageNum);
        }
        return ['code' => '200', 'Business' => $result];
    }

    public function auditUserQualificationRecord($id, $status)
    {
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

    public function getUserEquities($mobile, $business_id)
    {
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

    public function rechargeApplication($cmsConId, $nick_name, $business_id, $num)
    {
        $adminId = $this->getUidByConId($cmsConId);
        // $adminInfo     = DbAdmin::getAdminInfo(['id' => $adminId], 'id,passwd,status', true);
        $user = DbUser::getUserInfo(['nick_name' => $nick_name], 'id,mobile', true);
        if (empty($user)) {
            return ['code' => '3003'];
        }
        $business = DbAdministrator::getBusiness(['id' => $business_id], '*', true);
        if (empty($business)) {
            return ['code' => '3002'];
        }
        // print_r(DbAdministrator::getUserEquities(['business_id' => $business_id, 'uid' => $user['id']], 'id'));
        // echo Db::getLastSQL();
        // die;
        if (!DbAdministrator::getUserEquities(['business_id' => $business_id, 'uid' => $user['id']], 'id')) {
            return ['code' => '3004'];
        }
        $data = [];
        $data = [
            'initiate_admin_id' => $adminId,
            'business_id'       => $business_id,
            'uid'               => $user['id'],
            'mobile'            => $user['mobile'],
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

    public function getRechargeApplication($page, $pageNum, $id = 0, $getall)
    {
        $offset = ($page - 1) * $pageNum;
        $total = 0;
        if (!empty($id)) {
            $result = DbAdministrator::getAdminRemittance(['id' => $id], '*', true);
        } else {
            if ($getall == 1) {
                $result = DbAdministrator::getAdminRemittance([], '*', false);
            } else {
                $result = DbAdministrator::getAdminRemittance([], '*', false, '', $offset . ',' . $pageNum);
                $total = DbAdministrator::countAdminRemittance([]);
            }
        }
        return ['code' => '200', 'total' => $total, 'data' => $result];
    }

    public function aduitRechargeApplication($status, $message, $id)
    {
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

    public function getChannel()
    {
        $result = DbAdministrator::getSmsSendingChannel([], 'id,title', false);
        return ['code' => '200', 'channel_list' => $result];
    }

    public function settingChannel($channel_id, $business_id)
    {
        $channel = DbAdministrator::getSmsSendingChannel(['id' => $channel_id], 'id,title', true);
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

    public function distributeUserChannel($channel_id, $user_phone, $priority)
    {
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

    public function updateUserChannel($id, $priority)
    {
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

    public function delUserChannel($id)
    {
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

    public function getUserSendTask($page, $pageNum, $id)
    {
        $offset = ($page - 1) * $pageNum;
        if (!empty($id)) {
            $result = DbAdministrator::getUserSendTask(['id' => $id], '*', true);
        } else {
            $result = DbAdministrator::getUserSendTask([], '*', false, '', $offset . ',' . $pageNum);
        }
        $total = DbAdministrator::countUserSendTask([]);
        return ['code' => '200', 'total' => $total, 'data' => $result];
    }

    public function auditUserSendTask($effective_id = [], $free_trial)
    {
        // print_r($effective_id);die;
        $userchannel = DbAdministrator::getUserSendTask([['id', 'in', join(',', $effective_id)]], 'id,uid,mobile_content,task_content,free_trial,real_num', false);

        if (empty($userchannel)) {
            return ['code' => '3001'];
        }
        $real_effective_id = [];
        $user_ids = [];
        $uids = [];
        // print_r($userchannel);die;
        $billing  = [];
        foreach ($userchannel as $key => $value) {
            if ($value['free_trial'] > 1) {
                continue;
            }
            $real_effective_id[] = $value['id'];
            if (!in_array($value['uid'], $uids)) {
                $uids[] = $value['uid'];
            }

            if (array_key_exists($value['uid'], $user_ids)) {
                $user_ids[$value['uid']][] = $value['id'];
            } else {
                $user_ids[$value['uid']][] = $value['id'];
            }

            if (array_key_exists($value['uid'],$billing)) {
                $billing[$value['uid']] += $value['real_num'];
            }else{
                $billing[$value['uid']] = $value['real_num'];
            }
        }
        
        $where_equitise = [
            ['uid', 'IN', join(',',$uids)],['business_id', '=', 5]
        ];

        
        $user_equities = DbAdministrator::getUserEquities($where_equitise, 'id,uid,num_balance', false);
        


        Db::startTrans();
        try {
            foreach ($real_effective_id as $real => $efid) {
                DbAdministrator::editUserSendTask(['free_trial' => $free_trial], $efid);
            }
            //审核失败退回
            if ($free_trial == 3) {
                foreach ($user_equities as $key => $value) {
                    DbAdministrator::modifyBalance($value['id'], $billing[$value['uid']], 'inc');
                }
            }
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3009']; //修改失败
        }
    }

    public function distributionChannel($effective_id = [], $yidong_channel_id, $liantong_channel_id, $dianxin_channel_id, $business_id)
    {
        $channel = DbAdministrator::getSmsSendingChannel(['id' => $yidong_channel_id], 'id,title,channel_price', true);
        if (empty($channel)) {
            return ['code' => '3002'];
        }
        $channel = DbAdministrator::getSmsSendingChannel(['id' => $liantong_channel_id], 'id,title,channel_price', true);
        if (empty($channel)) {
            return ['code' => '3011'];
        }
        $channel = DbAdministrator::getSmsSendingChannel(['id' => $dianxin_channel_id], 'id,title,channel_price', true);
        if (empty($channel)) {
            return ['code' => '3012'];
        }
        $usertask = DbAdministrator::getUserSendTask([['id', 'in', join(',', $effective_id)]], 'id,uid,mobile_content,task_content,free_trial,send_num,yidong_channel_id,liantong_channel_id,dianxin_channel_id,appointment_time', false);
        if (empty($usertask)) {
            return ['code' => '3001'];
        }
        $num               = 0;
        $uids              = [];
        $real_effective_id = [];
        $real_usertask     = [];
        foreach ($usertask as $key => $value) {
            if (empty($uids)) {
                $uids[] = $value['uid'];
            } elseif (!in_array($value['uid'], $uids)) {
                $uids[] = $value['uid'];
            }
            // print_r($value);
            if ($value['free_trial'] == 2 && (!$value['yidong_channel_id'] || !$value['liantong_channel_id'] || !$value['dianxin_channel_id'])) {
                $real_length = 1;
                $real_usertask[] = $value;
                $mobilesend       = explode(',', $value['mobile_content']);
                $send_length     = mb_strlen($value['task_content'], 'utf8');
                if ($send_length > 70) {
                    $real_length = ceil($send_length / 67);
                }
                $num += ($real_length * $value['send_num']);
                // foreach ($mobilesend as $key => $kvalue) {

                // }
            }
        }
        // die;
        // print_r($uids);die;
        if (count($uids) > 1) {
            return ['code' => '3008', 'msg' => '一批只能同时分配一个用户的营销任务'];
        }
        if (empty($real_usertask)) {
            return ['code' => '3010', 'msg' => '待分配的批量任务未空（提交了一批未审核的批量任务）'];
        }
        $userEquities = DbAdministrator::getUserEquities(['uid' => $uids[0], 'business_id' => $business_id], 'id,agency_price,num_balance', true);
        if (empty($userEquities)) {
            return ['code' => '3005'];
        }

        $user = DbUser::getUserInfo(['id' => $uids[0]], 'id,reservation_service,user_status', true);
        if ($user['user_status'] != 2) {
            return ['code' => '3006'];
        }
        // print_r($num);die;
        // if ($num > $userEquities['num_balance'] && $user['reservation_service'] != 2) {
        //     return ['code' => '3007'];
        // }
        $free_trial = 2;
        if ($userEquities['agency_price'] < $channel['channel_price']) {
            $free_trial = 4;
        }
        Db::startTrans();
        try {

            // DbAdministrator::modifyBalance($userEquities['id'], $num, 'dec');
            foreach ($real_usertask as $key => $value) {
                DbAdministrator::editUserSendTask(['free_trial' => $free_trial, 'yidong_channel_id' => $yidong_channel_id, 'liantong_channel_id' => $liantong_channel_id, 'dianxin_channel_id' => $dianxin_channel_id], $value['id']);
            }
            foreach ($real_usertask as $real => $usertask) {
                // $res = $this->redis->rpush("index:meassage:marketing:sendtask",$usertask['id']); 
                if (isset($usertask['appointment_time']) && $usertask['appointment_time'] > 0) {
                    $res = $this->redis->rpush("index:meassage:marketingtiming:sendtask", json_encode(['id' => $usertask['id'], 'send_time' => $usertask['appointment_time']]));//定时
                }else{
                    $res = $this->redis->rpush("index:meassage:marketing:sendtask", json_encode(['id' => $usertask['id'],'send_time' => 0]));//非定时
                }
                // marketing
            }
            /*  if ($free_trial == 2) {
                foreach ($real_usertask as $real => $usertask) {
                    $mobilesend       = explode(',', $usertask['mobile_content']);
                    $effective_mobile = [];
                    if (substr_count($usertask['task_content'], '【米思米】') > 1) {
                        $usertask['task_content'] = mb_substr($usertask['task_content'], mb_strpos($usertask['task_content'], '】') + 1, mb_strlen($usertask['task_content']));
                    }

                    foreach ($mobilesend as $key => $value) {
                        if (checkMobile($value)) {
                            $effective_mobile[] = $value;
                        }
                    }
                    // $redisMessageMarketingSend = Config::get('rediskey.message.redisMessageMarketingSend');
                    $redisMessageMarketingSend = Config::get('rediskey.message.redisMessageCodeSend');
                    // print_r($redisMessageMarketingSend);die;
                    if (in_array($channel_id,[2,6,7,8])) {//组合通道
                        foreach ($effective_mobile as $key => $value) {
                            $prefix = substr($value, 0, 7);
                            $res    = DbProvinces::getNumberSource(['mobile' => $prefix], 'source,province_id,province', true);
                            if ($res['source'] == 2) { //米加联通营销
                                $channel_id = 8; 
                            } else if ($res['source'] == 1) { //移动
                                $channel_id = 2; 
                               
                            }else if ($res['source' == 3]) {//米加电信营销
                                $channel_id = 2; 
                            }
                            $send = [];
                            $send = [
                                'mobile' => $value, 
                                'mar_task_id' => $usertask['id'], 
                                'content' => $usertask['task_content'], 
                            ];
                            $res = $this->redis->rpush($redisMessageMarketingSend . ":" . $channel_id,json_encode($send)); //三体营销通道
                            $res = $this->redis->rpush("index:meassage:marketing:sendtask",$usertask['id']); //三体营销通道
                            // $res = $this->redis->rpush($redisMessageMarketingSend . ":" . $channel_id, $value . ":" . $usertask['id'] . ":" . $usertask['task_content']); //三体营销通道
                            if ($res == false) {
                                Db::rollback();
                                return ['code' => '3009']; //修改失败
                            }
                        }
                    }else{
                        foreach ($effective_mobile as $key => $value) {
                            $res = $this->redis->rpush($redisMessageMarketingSend . ":" . $channel_id, $value . ":" . $usertask['id'] . ":" . $usertask['task_content']); //三体营销通道
                            if ($res == false) {
                                Db::rollback();
                                return ['code' => '3009']; //修改失败
                            }
                            // $this->redis->hset($redisMessageMarketingSend.":2",$value,$id.":".$Content); //三体营销通道
                        }
                    }
                    
                }

            } */

            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            exception($e);
            Db::rollback();
            return ['code' => '3009']; //修改失败
        }
    }

    public function getUserSendCodeTask($page, $pageNum, $id)
    {
        $offset = ($page - 1) * $pageNum;
        if (!empty($id)) {
            $result = DbAdministrator::getUserSendCodeTask(['id' => $id], '*', true);
        } else {
            $result = DbAdministrator::getUserSendCodeTask([], '*', false, '', $offset . ',' . $pageNum);
        }
        $total = DbAdministrator::countUserSendCodeTask([]);
        return ['code' => '200', 'total' => $total, 'data' => $result];
    }

    public function auditUserSendCodeTask($effective_id = [], $free_trial)
    {
        // print_r($effective_id);die;
        $userchannel = DbAdministrator::getUserSendCodeTask([['id', 'in', join(',', $effective_id)]], 'id,uid,real_num,mobile_content,free_trial', false);

        if (empty($userchannel)) {
            return ['code' => '3001'];
        }
        $real_effective_id = [];
        // print_r($userchannel);die;
        $real_effective_id = [];
        $user_ids = [];
        $uids = [];
        // print_r($userchannel);die;
        $billing  = [];
        foreach ($userchannel as $key => $value) {
            if ($value['free_trial'] > 1) {
                continue;
            }
            $real_effective_id[] = $value['id'];
            if (!in_array($value['uid'], $uids)) {
                $uids[] = $value['uid'];
            }

            if (array_key_exists($value['uid'], $user_ids)) {
                $user_ids[$value['uid']][] = $value['id'];
            } else {
                $user_ids[$value['uid']][] = $value['id'];
            }

            if (array_key_exists($value['uid'],$billing)) {
                $billing[$value['uid']] += $value['real_num'];
            }else{
                $billing[$value['uid']] = $value['real_num'];
            }
        }
        $where_equitise = [
            ['uid', 'IN', join(',',$uids)],['business_id', '=', 6]
        ];

        
        $user_equities = DbAdministrator::getUserEquities($where_equitise, 'id,uid,num_balance', false);
        Db::startTrans();
        try {
            foreach ($real_effective_id as $real => $efid) {
                DbAdministrator::editUserSendCodeTask(['free_trial' => $free_trial], $efid);
            }
            //审核失败退回
            if ($free_trial == 3) {
                foreach ($user_equities as $key => $value) {
                    DbAdministrator::modifyBalance($value['id'], $billing[$value['uid']], 'inc');
                }
            }
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3009']; //修改失败
        }
    }

    public function distributionCodeTaskChannel($effective_id = [],$yidong_channel_id, $liantong_channel_id, $dianxin_channel_id,  $business_id)
    {
        $channel = DbAdministrator::getSmsSendingChannel(['id' => $yidong_channel_id], 'id,title,channel_price', true);
        if (empty($channel)) {
            return ['code' => '3002'];
        }
        $channel = DbAdministrator::getSmsSendingChannel(['id' => $liantong_channel_id], 'id,title,channel_price', true);
        if (empty($channel)) {
            return ['code' => '3011'];
        }
        $channel = DbAdministrator::getSmsSendingChannel(['id' => $dianxin_channel_id], 'id,title,channel_price', true);
        if (empty($channel)) {
            return ['code' => '3012'];
        }
        $usertask = DbAdministrator::getUserSendCodeTask([['id', 'in', join(',', $effective_id)]], 'id,uid,mobile_content,task_content,free_trial,send_num,yidong_channel_id,liantong_channel_id,dianxin_channel_id', false);
        if (empty($usertask)) {
            return ['code' => '3001'];
        }
        $num               = 0;
        $uids              = [];
        $real_effective_id = [];
        $real_usertask     = [];
        foreach ($usertask as $key => $value) {
            if (empty($uids)) {
                $uids[] = $value['uid'];
            } elseif (!in_array($value['uid'], $uids)) {
                $uids[] = $value['uid'];
            }
            // print_r($value);
            if ($value['free_trial'] == 2 && !$value['yidong_channel_id']) {
                $real_length = 1;
                $real_usertask[] = $value;
                $mobilesend       = explode(',', $value['mobile_content']);
                $send_length     = mb_strlen($value['task_content'], 'utf8');
                if ($send_length > 70) {
                    $real_length = ceil($send_length / 67);
                }
                $num += ($real_length * $value['send_num']);
                // foreach ($mobilesend as $key => $kvalue) {

                // }
            }
        }
        
        // die;
        // print_r($uids);die;
        if (count($uids) > 1) {
            return ['code' => '3008', 'msg' => '一批只能同时分配一个用户的营销任务'];
        }
        if (empty($real_usertask)) {
            return ['code' => '3010', 'msg' => '待分配的批量任务未空（提交了一批未审核的批量任务）'];
        }
        $userEquities = DbAdministrator::getUserEquities(['uid' => $uids[0], 'business_id' => $business_id], 'id,agency_price,num_balance', true);
        if (empty($userEquities)) {
            return ['code' => '3005'];
        }

        $user = DbUser::getUserInfo(['id' => $uids[0]], 'id,reservation_service,user_status', true);
        if ($user['user_status'] != 2) {
            return ['code' => '3006'];
        }
        // print_r($num);die;
       /*  if ($num > $userEquities['num_balance'] && $user['reservation_service'] != 2) {
            return ['code' => '3007'];
        } */
        $free_trial = 2;
        if ($userEquities['agency_price'] < $channel['channel_price']) {
            $free_trial = 4;
        }
        Db::startTrans();
        try {

            // DbAdministrator::modifyBalance($userEquities['id'], $num, 'dec');
            foreach ($real_usertask as $key => $value) {
                DbAdministrator::editUserSendCodeTask(['free_trial' => $free_trial,  'yidong_channel_id' => $yidong_channel_id, 'liantong_channel_id' => $liantong_channel_id, 'dianxin_channel_id' => $dianxin_channel_id], $value['id']);
            }
            foreach ($real_usertask as $real => $usertask) {
                $res = $this->redis->rpush("index:meassage:business:sendtask", $usertask['id']);
            }
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            exception($e);
            Db::rollback();
            return ['code' => '3009']; //修改失败
        }
    }

    public function thirdPartyMMSTemplateReport($channel_id,$template_id){
        $template =  DbSendMessage::getUserMultimediaTemplate(['template_id' => $template_id], '*', true);
        if ($template['status'] != 2 || empty($template)) {
            return ['code' => '3003', 'msg' => '模板未审核通过或者该模板不存在'];
        }
        $multimedia_message_frame = DbSendMessage::getUserMultimediaTemplateFrame(['multimedia_template_id' => $template['id']], 'num,name,content,image_path,image_type', false, ['num' => 'asc']);
        $channel = DbAdministrator::getSmsSendingChannel(['id' => $channel_id], 'id,title,business_id,channel_price', true);
        if (empty($channel)) {
            return ['code' => '3002'];
        }
        if ($channel['business_id'] != 8) {
            return ['code' => '3004', '非彩信通道不能使用此接口'];
        }
        if ($channel_id == 58) {
            $report_api = 'http://mms.mms-sender.cn:8080/mmsServer/sendMms';
            $data = [];
            $data['id'] = '200401';
            $data['pwd']  = 'zd1403';
            $data['subject']= bin2hex($template['title']);
            // $subject  = $template['title'];
           /*  $desubject = hex2bin($subject);
            echo $subject."\n";
            echo $desubject."\n";
            die; */
            $tdata = [];
            $tvdata = [];
            $pdata = [];
            $pvdata = [];
           
            foreach ($multimedia_message_frame as $key => $value) {
                if (!empty($value['content'])) {
                    $tdata[] = 'tt'.$value['num'].'=txt';
                    $tvdata[] = 'tv'.$value['num'].'='.bin2hex($value['content']);
                    $data['tt'.$value['num']] = 'txt';
                    $data['tv'.$value['num']] = bin2hex($value['content']);
                }
                if (!empty($value['image_path'])) {
                    $pdata[] = 'pt'.$value['num'].'='.$value['image_type'];
                    $pvdata[] = 'pv'.$value['num'].'='.bin2hex($value['image_path']);
                    $data['pt'.$value['num']] = $value['image_type'];
                    $data['pv'.$value['num']] = bin2hex(file_get_contents($value['image_path']));
                }
            }
            print_r($data);die;
            $result = sendRequest($report_api, 'post', $data);
            print_r($result);die;
        }
    }

    public function sflThirdPartyMMSTemplateReport($channel_id,$sfl_relation_id){
        $mul      = DbSendMessage::getSflMultimediaTemplate(['sfl_relation_id' => $sfl_relation_id], '*', true);
        $fram     = DbSendMessage::getSflMultimediaTemplateFrame(['sfl_multimedia_template_id' => $mul['id'], 'sfl_model_id' => $mul['sfl_model_id']], '*', false);
        $channel = DbAdministrator::getSmsSendingChannel(['id' => $channel_id], 'id,title,business_id,channel_price', true);
        if (empty($channel)) {
            return ['code' => '3002'];
        }
        if ($channel_id == 99) {

        }
        $account = 'C4786051';
        $title = $mul['title'];
        $notifyUrl = '';
        $timestamp = time();
        foreach ($fram as $key => $value) {
            # code...
        }
        // C4786051
        // 38gHTjrzh
    }
}
