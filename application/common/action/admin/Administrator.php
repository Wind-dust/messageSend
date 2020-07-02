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
        $time = strtotime('-4 days',time());
        // echo $time;die;
        $offset = ($page - 1) * $pageNum;
        if (!empty($id)) {
            $result = DbAdministrator::getUserSendTask(['id' => $id], '*', true);
        } else {
            $result = DbAdministrator::getUserSendTask([['create_time' ,'>=', $time]], '*', false, ['free_trial' => 'asc'], $offset . ',' . $pageNum);
        }
        $total = DbAdministrator::countUserSendTask([['create_time' ,'>=', $time]]);
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

            if (array_key_exists($value['uid'], $billing)) {
                $billing[$value['uid']] += $value['real_num'];
            } else {
                $billing[$value['uid']] = $value['real_num'];
            }
        }

        $where_equitise = [
            ['uid', 'IN', join(',', $uids)], ['business_id', '=', 5]
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

        $user = DbUser::getUserInfo(['id' => $uids[0]], 'id,reservation_service,user_status,market_deduct', true);
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
                    $res = $this->redis->rpush("index:meassage:marketingtiming:sendtask", json_encode(['id' => $usertask['id'], 'send_time' => $usertask['appointment_time'],'deduct' => $user['market_deduct']])); //定时
                } else {
                    $res = $this->redis->rpush("index:meassage:marketing:sendtask", json_encode(['id' => $usertask['id'], 'send_time' => 0,'deduct' => $user['market_deduct']])); //非定时
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
        $time = strtotime('-4 days',time());
        $offset = ($page - 1) * $pageNum;
        if (!empty($id)) {
            $result = DbAdministrator::getUserSendCodeTask(['id' => $id], '*', true);
        } else {
            $result = DbAdministrator::getUserSendCodeTask([['create_time' ,'>=', $time]], '*', false, ['free_trial' => 'asc'], $offset . ',' . $pageNum);
        }
        $total = DbAdministrator::countUserSendCodeTask([['create_time' ,'>=', $time]]);
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

            if (array_key_exists($value['uid'], $billing)) {
                $billing[$value['uid']] += $value['real_num'];
            } else {
                $billing[$value['uid']] = $value['real_num'];
            }
        }
        $where_equitise = [
            ['uid', 'IN', join(',', $uids)], ['business_id', '=', 6]
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

    public function distributionCodeTaskChannel($effective_id = [], $yidong_channel_id, $liantong_channel_id, $dianxin_channel_id,  $business_id)
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

        $user = DbUser::getUserInfo(['id' => $uids[0]], 'id,reservation_service,user_status,business_deduct', true);
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
                $res = $this->redis->rpush("index:meassage:business:sendtask", json_encode(['id' => $usertask['id'], 'deduct' => $user['business_deduct']]));
            }
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            exception($e);
            Db::rollback();
            return ['code' => '3009']; //修改失败
        }
    }

    public function thirdPartyMMSTemplateReport($channel_id, $template_id)
    {
        $template =  DbSendMessage::getUserMultimediaTemplate(['template_id' => $template_id], '*', true);
        if ($template['status'] != 2 || empty($template)) {
            return ['code' => '3003', 'msg' => '模板未审核通过或者该模板不存在'];
        }
        $multimedia_message_frame = DbSendMessage::getUserMultimediaTemplateFrame(['multimedia_template_id' => $template['id']], 'num,name,content,image_path,image_type', false, ['num' => 'asc']);
        $model_val = 0; //模板类型 0，普通彩信, 1 模板变量彩信
        foreach ($multimedia_message_frame as $key => $value) {
            if (strpos($value['content'], '{{var') != false) {
                $model_val = 1;
                break;
            }
        }
        $channel = DbAdministrator::getSmsSendingChannel(['id' => $channel_id], 'id,title,business_id,channel_price', true);
        if (empty($channel)) {
            return ['code' => '3002'];
        }
        if ($channel['business_id'] != 8) {
            return ['code' => '3004', '非彩信通道不能使用此接口'];
        }
        //创蓝
        if ($channel_id == 63) {
            $report_api = 'http://mms.mms-sender.cn:8080/mmsServer/sendMms';
            $data = [];
            $data['id'] = '200401';
            $data['pwd']  = 'zd1403';
            $data['subject'] = bin2hex($template['title']);
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
                    $tdata[] = 'tt' . $value['num'] . '=txt';
                    $tvdata[] = 'tv' . $value['num'] . '=' . bin2hex($value['content']);
                    $data['tt' . $value['num']] = 'txt';
                    $data['tv' . $value['num']] = bin2hex($value['content']);
                }
                if (!empty($value['image_path'])) {
                    $pdata[] = 'pt' . $value['num'] . '=' . $value['image_type'];
                    $pvdata[] = 'pv' . $value['num'] . '=' . bin2hex($value['image_path']);
                    $data['pt' . $value['num']] = $value['image_type'];
                    $data['pv' . $value['num']] = bin2hex(file_get_contents($value['image_path']));
                }
            }
            print_r($data);
            die;
            $result = sendRequest($report_api, 'post', $data);
            print_r($result);
            die;
        }elseif ($channel_id == 104){//联麓彩信批量通道
            if ($model_val == 1) {
                return ['code' => '3005','msg' => '该通道不支持模板变量报备'];
            }
            $appid = '350304';
            $timestamp = time();
            $time = microtime(true);
            //结果：1541053888.5911
            //在经过处理得到最终结果:
            $lastTime = (int)($time * 1000);
            $appkey = '50e075b4883e49d69c4d08a5b210537d';
            $sign = md5($appkey.$appid.$lastTime.$appkey);
            $report_api = 'http://47.110.199.86:8081/api/v2/mms/create?timestamp='.$lastTime.'&appid='.$appid.'&sign='.$sign;
            $data = [];
            $data['mms_title'] = $template['title'];
            $data['mms_type'] = 'multipart/mixed';
            $data['mmstemplate'] = 1;
            
            // print_r($multimedia_message_frame);die;
            $mmsbody = [];
            foreach ($multimedia_message_frame as $key => $value) {
                # code...
                $content_data = [];
                if (!empty($value['content'])) {
                    $content_data = [
                        'content_data' => $value['content'],
                        'content_type' => 'text/plain',
                    ];
                    $mmsbody[] = $content_data;
                }
                $content_data = [];
                if (!empty($value['image_path'])) {

                    $value['image_path']=filtraImage(Config::get('qiniu.domain'), $value['image_path']);
                    $type = explode('.', $value['image_path']);

                   
                    $content_data = [
                        'content_data' => base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path'])),
                        'content_type' => 'image/'.$type[1],
                    ];
                    $mmsbody[] = $content_data;
                }
            }
            $data['mmsbody'] = $mmsbody;
            $headers = [];
            $headers = [
                'Content-Type:text/plain'
            ];
            $result = $this->sendRequest2($report_api,'post',$data,$headers);
            // $result = '{"msg":"成功","code":"T","data":{"mms_id":"60226","status":"R"}}';
           
            if (!empty($result)) {
                $result = json_decode($result,true);
                if ($result['msg'] == '成功') {
                    $report_msg_id = $result['data']['mms_id'];
                    $had_report = DbAdministrator::getUserMultimediaTemplateThirdReport(['channel_id'=> $channel_id,'template_id' => $template_id],'id',true);
                    if (!empty($had_report)) {
                        return ['code' => '3007','msg' => '该模板已在该通道报备过'];
                    }
                    $report_data = [];
                    $report_data = [
                        'channel_id'=> $channel_id,
                        'template_id'=> $template_id,
                        'third_template_id'=> $report_msg_id,
                    ];
                    Db::startTrans();
                    try {
                        DbAdministrator::addUserMultimediaTemplateThirdReport($report_data);
                        Db::commit();
                        return ['code' => '200'];
                    } catch (\Exception $th) {
                        exception($th);
                        Db::rollback();
                        return ['code' => '3009']; //修改失败
                    }
                }
                return ['code' => '3006','msg' => '该通道报备失败'];
            }else{
                return ['code' => '3006','msg' => '该通道报备失败'];
            }
        }elseif ($channel_id == 103) {
            $report_api = 'http://caixin.253.com/open/saveTemplate';
            $data = [];
           
            $msg = [];
            foreach ($multimedia_message_frame as $key => $value) {
                $frame = [];
                if (!empty($value['content'])) {
                    $frame['frame'] = $value['num'];
                    $frame['part'] = 1;
                    $frame['type'] = 1;
                    // $frame['content'] = $value['content'];
                    // if (strpos($value['content'],'{{}}')) {}
                    $value['content'] = str_replace('{{var1}}','{s1}',$value['content']);
                    $value['content'] = str_replace('{{var2}}','{s2}',$value['content']);
                    $value['content'] = str_replace('{{var3}}','{s3}',$value['content']);
                    $value['content'] = str_replace('{{var4}}','{s4}',$value['content']);
                    $value['content'] = str_replace('{{var5}}','{s5}',$value['content']);
                    $value['content'] = str_replace('{{var6}}','{s6}',$value['content']);
                    $value['content'] = str_replace('{{var7}}','{s7}',$value['content']);
                    $value['content'] = str_replace('{{var8}}','{s8}',$value['content']);
                    $value['content'] = str_replace('{{var9}}','{s9}',$value['content']);
                    $value['content'] = str_replace('{{var10}}','{s10}',$value['content']);
                    $frame['content'] = base64_encode($value['content']);
                    $msg[] = $frame;
                }

                if (!empty($value['image_path'])) {
                    $frame = [];
                    $value['image_path']=filtraImage(Config::get('qiniu.domain'), $value['image_path']);
                    $type = explode('.', $value['image_path']);

                    $frame['frame'] = $value['num'];
                    $frame['part'] = 1;
                    if ($type[1] == 'jpg') {
                        $frame['type'] = 2;
                    } elseif ($type[1] == 'jpeg') {
                        $frame['type'] = 2;
                    } elseif ($type[1] == 'png') {
                        $frame['type'] = 3;
                    } elseif ($type[1] == 'gif') {
                        $frame['type'] = 4;
                    } elseif ($type[1] == 'gif') {
                        $frame['type'] = 4;
                    } elseif ($type[1] == 'wbmp') {
                        $frame['type'] = 5;
                    } elseif ($type[1] == 'bmp') {
                        $frame['type'] = 5;
                    } elseif ($type[1] == 'amr') {
                        $frame['type'] = 6;
                    } elseif ($type[1] == 'midi') {
                        $frame['type'] = 7;
                    }
                    $imagebase = base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
                    $frame['content'] =$imagebase;
                    // $frame['content'] = base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
                    $msg[] = $frame;
                }
            }
            $msg = json_encode($msg);
            $notifyUrl = '';
            $remark = '';
            $timestamp = time();
            $data['account'] = 'C0120120';
            $key = 'OdJugXUcv99bca';
            $data['title'] = $template['title'];
            // $sign = "account=" . $data['account']  . "msg=".$msg."notifyUrl=".$notifyUrl."remark=".$remark. "timestamp=" . $timestamp . "title=" .$data['title']. "key=" . $data['key'];
            $sign = "account=" . $data['account']  . "msg=".$msg."remark=".$remark. "timestamp=" . $timestamp . "title=" .$data['title'];
            $sign = hash_hmac('sha256',$sign,$key);
            // print_r($sign);die;
            $data['msg'] = $msg;
            // $data['notifyUrl'] = $notifyUrl;
            $data['remark'] = $remark;
            $data['timestamp'] = $timestamp;
            $data['sign'] = $sign;
            $res = sendRequest($report_api, 'post', $data);
            $result = json_decode($res, true);
            if (!empty($result)) {
                if ($result['message'] == '提交成功') {
                    $report_msg_id = $result['data']['templateId'];
                    $had_report = DbAdministrator::getUserMultimediaTemplateThirdReport(['channel_id'=> $channel_id,'template_id' => $template_id],'id',true);
                    if (!empty($had_report)) {
                        return ['code' => '3007','msg' => '该模板已在该通道报备过'];
                    }
                    $report_data = [];
                    $report_data = [
                        'channel_id'=> $channel_id,
                        'template_id'=> $template_id,
                        'third_template_id'=> $report_msg_id,
                    ];
                    Db::startTrans();
                    try {
                        DbAdministrator::addUserMultimediaTemplateThirdReport($report_data);
                        Db::commit();
                        return ['code' => '200'];
                    } catch (\Exception $th) {
                        exception($th);
                        Db::rollback();
                        return ['code' => '3009']; //修改失败
                    }
                }
                return ['code' => '3006','msg' => '该通道报备失败'];
            }else{
                return ['code' => '3006','msg' => '该通道报备失败'];
            }
            
        }
    }

    public function sflThirdPartyMMSTemplateReport($channel_id, $sfl_relation_id)
    {
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

    public function addDeductWord($business_id, $uid = 0, $word){
        if (!empty($uid)) {
            $user =  DbUser::getUserInfo(['id' => $uid], 'id,reservation_service,user_status,business_deduct', true);
            if (empty($user)) {
                return ['code' => '3003', 'msg' => '该用户不存在'];
            }
            $word = DbAdministrator::getUserDeductWord(['word' => $word, 'business_id' => $business_id],'*',true);
            if (!empty($word)) {
                if ($word['uid'] == $uid) {
                    return ['code' => '3004', 'msg' => '该用户已设置敏感词'];
                }
                if ($word['uid'] == 0) {
                    return ['code' => '3005', 'msg' => '已添加过全局关键词'];
                }
            }
            $data = [];
            $data = [
                'word' => $word,
                'business_id' => $business_id,
            ];
            if (!empty($uid)) {
                $data['uid'] = $uid;
            }
            Db::startTrans();
            try {
                // DbAdministrator::modifyBalance($userEquities['id'], $num, 'dec');
                DbAdministrator::addUserDeductWord($data);
                Db::commit();
                return ['code' => '200'];
            } catch (\Exception $e) {
                Db::rollback();
                exception($e);
                return ['code' => '3009']; //修改失败
            }

        }
    }

    public function getDeductWord($business_id, $page, $pageNum){
        $offset = ($page - 1) * $pageNum;
        $result = DbAdministrator::getUserDeductWord(['business_id' => $business_id],'*',false,'',$page.','.$offset);
        $total = DbAdministrator::countUserDeductWord(['business_id' => $business_id]);
        return ['code' => '200', 'total' => $total,'result' => $result];
    }

    public function updateDeductWord($id,$business_id, $uid, $word){
        $word = DbAdministrator::getUserDeductWord(['id' => $id],'*',true);
            if (empty($word)) {
               return ['code' => '3003','msg' => '该记录不存在'];
            }
            $data = [];
          if ($business_id) {
              $data['business_id'] = $business_id;
          }
          if ($uid) {
            $data['uid'] = $uid;
        }
        if ($word) {
            $data['word'] = $word;
        }
        if (!empty($data)) {
            Db::startTrans();
            try {
                // DbAdministrator::modifyBalance($userEquities['id'], $num, 'dec');
                DbAdministrator::editUserDeductWord($data,$id);
                Db::commit();
                return ['code' => '200'];
            } catch (\Exception $e) {
                Db::rollback();
                exception($e);
                return ['code' => '3009']; //修改失败
            }
        }
        return ['code' => '3004','msg' =>'没有需要修改的类目'];
    }

    function sendRequest2($requestUrl, $method = 'get', $data = [],$headers)
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
            curl_setopt($curl, CURLOPT_POSTFIELDS, base64_encode(json_encode($data)));
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
}
