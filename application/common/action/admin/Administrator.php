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

    // distributeUserChannel(intval($yidong_channel_id), intval($liantong_channel_id),intval($dianxin_channel_id),intval($business_id), strval($nick_name))
    public function distributeUserChannel($yidong_channel_id, $liantong_channel_id, $dianxin_channel_id, $business_id, $nick_name)
    {
        $yd_channel = DbAdministrator::getSmsSendingChannel(['id' => $yidong_channel_id, 'business_id' => $business_id], 'id', true);
        if (empty($yd_channel)) {
            return ['code' => '3002'];
        }
        $lt_channel = DbAdministrator::getSmsSendingChannel(['id' => $liantong_channel_id, 'business_id' => $business_id], 'id', true);
        if (empty($lt_channel)) {
            return ['code' => '3002'];
        }
        $dx_channel = DbAdministrator::getSmsSendingChannel(['id' => $dianxin_channel_id, 'business_id' => $business_id], 'id', true);
        if (empty($dx_channel)) {
            return ['code' => '3002'];
        }
        $user = DbUser::getUserInfo(['nick_name' => $nick_name], 'id', true);
        if (empty($user)) {
            return ['code' => '3004'];
        }

        if (DbAdministrator::getUserChannel(['uid' => $user['id'], 'business_id' => $business_id], 'id', true)) {
            return ['code' => '3005'];
        }
        $data = [];
        $data = [
            'yidong_channel_id' => $yidong_channel_id,
            'liantong_channel_id' => $liantong_channel_id,
            'dianxin_channel_id' => $dianxin_channel_id,
            'uid'        => $user['id'],
            'nick_name'        => $nick_name,
            'business_id'   => $business_id,
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

    public function updateUserChannel($id, $yidong_channel_id, $liantong_channel_id, $dianxin_channel_id)
    {
        $userchannel = DbAdministrator::getUserChannel(['id' => $id], 'id', true);
        if (empty($userchannel)) {
            return ['code' => '3001'];
        }
        Db::startTrans();
        try {
            DbAdministrator::editUserChannel(['yidong_channel_id' => $yidong_channel_id, 'liantong_channel_id' => $liantong_channel_id, 'dianxin_channel_id' => $dianxin_channel_id], $id);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3009']; //修改失败
        }
    }

    public function getUserChannel($uid = 0, $nick_name = '', $business_id = 0, $page, $pageNum)
    {
        $offset = ($page - 1) * $pageNum;
        $where = [];
        if (!empty($uid)) {
            array_push($where, ['uid', '=', $uid]);
        }
        if (!empty($nick_name)) {
            array_push($where, ['nick_name', 'like', '%' . $nick_name . '%']);
        }
        if (!empty($business_id)) {
            array_push($where, ['business_id', '=', $business_id]);
        }
        $result = DbAdministrator::getUserChannel($where, '*', false, '', $offset . ',' . $pageNum);
        $total = DbAdministrator::countUserChannel($where);
        return ['code' => 200, 'total' => $total, 'user_channel' => $result];
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

    public function getUserSendTask($page, $pageNum, $id, $free_trial = 0, $send_status = 0, $uid = 0, $channel_id = 0)
    {
        $time = strtotime('-4 days', time());
        // echo $time;die;
        $where = [];
        array_push($where, ['create_time', '>=', $time]);
        $offset = ($page - 1) * $pageNum;
        if ($free_trial) {
            array_push($where, ['free_trial', '=', $free_trial]);
        }
        if ($send_status) {
            array_push($where, ['send_status', '=', $send_status]);
        }
        if ($uid) {
            array_push($where, ['uid', '=', $uid]);
        }
        if ($channel_id == 0) {
            array_push($where, ['yidong_channel_id', '=', 0]);
        }
        if (!empty($id)) {
            $result = DbAdministrator::getUserSendTask(['id' => $id], '*', true);
        } else {
            $result = DbAdministrator::getUserSendTask($where, '*', false, ['id' => 'desc',], $offset . ',' . $pageNum);
        }
        $total = DbAdministrator::countUserSendTask($where);
        return ['code' => '200', 'total' => $total, 'data' => $result];
    }

    public function auditUserSendTask($effective_id = [], $free_trial)
    {
        // print_r($effective_id);die;
        $userchannel = DbAdministrator::getUserSendTask([['id', 'in', join(',', $effective_id)]], 'task_no,id,uid,send_msg_id,mobile_content,task_content,free_trial,real_num', false);

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
            $mobiles = explode(',', $value['mobile_content']);
            if ($free_trial == 3) {
                foreach ($mobiles as $mkey => $mvalue) {
                    $res = $this->redis->rpush("index:meassage:code:user:mulreceive:" . $value['uid'], json_encode(['task_no' => $value['task_no'], 'msg_id' => $value['send_msg_id'], "status_message" => "INTERCEPT", "message_info" => "驳回", "send_time" => date("Y-m-d H:i:s", time()), 'mobile' => $mvalue]));
                }
            }
        }

        if (empty($real_effective_id)) {
            return ['code' => '3002', 'msg' => '没有需要审核的任务'];
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
        /* if (count($uids) > 1) {
            return ['code' => '3008', 'msg' => '一批只能同时分配一个用户的营销任务'];
        } */
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
                DbAdministrator::editUserSendTask(['free_trial' => $free_trial, 'yidong_channel_id' => $yidong_channel_id, 'liantong_channel_id' => $liantong_channel_id, 'dianxin_channel_id' => $dianxin_channel_id, 'send_status' => 2], $value['id']);
            }
            foreach ($real_usertask as $real => $usertask) {
                // $res = $this->redis->rpush("index:meassage:marketing:sendtask",$usertask['id']); 
                if (isset($usertask['appointment_time']) && $usertask['appointment_time'] > 0) {
                    $res = $this->redis->rpush("index:meassage:marketingtiming:sendtask", json_encode(['id' => $usertask['id'], 'send_time' => $usertask['appointment_time'], 'deduct' => $user['market_deduct']])); //定时
                } else {
                    $res = $this->redis->rpush("index:meassage:marketing:sendtask", json_encode(['id' => $usertask['id'], 'send_time' => 0, 'deduct' => $user['market_deduct']])); //非定时
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

    public function getUserSendCodeTask($page, $pageNum, $id, $free_trial = 0, $channel_id = 0, $send_status, $uid = 0)
    {
        $time = strtotime('-4 days', time());
        // echo $time;die;
        $where = [];
        array_push($where, ['create_time', '>=', $time]);
        $offset = ($page - 1) * $pageNum;
        if ($free_trial) {
            array_push($where, ['free_trial', '=', $free_trial]);
        }
        if ($channel_id == 0) {
            array_push($where, ['yidong_channel_id', '=', 0]);
        }
        if ($send_status) {
            array_push($where, ['send_status', '=', $send_status]);
        }
        if ($uid) {
            array_push($where, ['uid', '=', $uid]);
        }
        $offset = ($page - 1) * $pageNum;
        if (!empty($id)) {
            $result = DbAdministrator::getUserSendCodeTask(['id' => $id], '*', true);
        } else {
            $result = DbAdministrator::getUserSendCodeTask($where, '*', false, ['id' => 'desc'], $offset . ',' . $pageNum);
        }
        $total = DbAdministrator::countUserSendCodeTask($where);
        return ['code' => '200', 'total' => $total, 'data' => $result];
    }

    public function auditUserSendCodeTask($effective_id = [], $free_trial)
    {
        // print_r($effective_id);die;
        $userchannel = DbAdministrator::getUserSendCodeTask([['id', 'in', join(',', $effective_id)]], 'task_no,id,uid,real_num,mobile_content,free_trial', false);

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
            $mobiles = explode(',', $value['mobile_content']);
            if ($free_trial == 3) {
                foreach ($mobiles as $mkey => $mvalue) {
                    $res = $this->redis->rpush("index:meassage:code:user:mulreceive:" . $value['uid'], json_encode(['task_no' => $value['task_no'], 'msg_id' => $value['send_msg_id'], "status_message" => "INTERCEPT", "message_info" => "驳回", "send_time" => date("Y-m-d H:i:s", time()), 'mobile' => $mvalue]));
                }
            }
        }
        if (empty($real_effective_id)) {
            return ['code' => '3002', 'msg' => '没有需要审核的任务'];
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
                DbAdministrator::editUserSendCodeTask(['free_trial' => $free_trial,  'yidong_channel_id' => $yidong_channel_id, 'liantong_channel_id' => $liantong_channel_id, 'dianxin_channel_id' => $dianxin_channel_id, 'send_status' => 2], $value['id']);
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
        $had_report = DbAdministrator::getUserMultimediaTemplateThirdReport(['channel_id' => $channel_id, 'template_id' => $template_id], 'id', true);
        if (!empty($had_report)) {
            return ['code' => '3007', 'msg' => '该模板已在该通道报备过'];
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
        } elseif ($channel_id == 104) { //联麓彩信批量通道
            if ($model_val == 1) {
                return ['code' => '3005', 'msg' => '该通道不支持模板变量报备'];
            }
            $appid = '350304';
            $timestamp = time();
            $time = microtime(true);
            //结果：1541053888.5911
            //在经过处理得到最终结果:
            $lastTime = (int)($time * 1000);
            $appkey = '50e075b4883e49d69c4d08a5b210537d';
            $sign = md5($appkey . $appid . $lastTime . $appkey);
            $report_api = 'http://47.110.199.86:8081/api/v2/mms/create?timestamp=' . $lastTime . '&appid=' . $appid . '&sign=' . $sign;
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

                    $value['image_path'] = filtraImage(Config::get('qiniu.domain'), $value['image_path']);
                    $type = explode('.', $value['image_path']);


                    $content_data = [
                        'content_data' => base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path'])),
                        'content_type' => 'image/' . $type[1],
                    ];
                    $mmsbody[] = $content_data;
                }
            }
            $data['mmsbody'] = $mmsbody;
            $headers = [];
            $headers = [
                'Content-Type:text/plain'
            ];
            $result = $this->sendRequest2($report_api, 'post', $data, $headers);
            // $result = '{"msg":"成功","code":"T","data":{"mms_id":"60226","status":"R"}}';

            if (!empty($result)) {
                $result = json_decode($result, true);
                if ($result['msg'] == '成功') {
                    $report_msg_id = $result['data']['mms_id'];
                    $report_data = [];
                    $report_data = [
                        'channel_id' => $channel_id,
                        'template_id' => $template_id,
                        'third_template_id' => $report_msg_id,
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
                return ['code' => '3006', 'msg' => '该通道报备失败'];
            } else {
                return ['code' => '3006', 'msg' => '该通道报备失败'];
            }
        } elseif ($channel_id == 103) {
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
                    $value['content'] = str_replace('{{var1}}', '{s1}', $value['content']);
                    $value['content'] = str_replace('{{var2}}', '{s2}', $value['content']);
                    $value['content'] = str_replace('{{var3}}', '{s3}', $value['content']);
                    $value['content'] = str_replace('{{var4}}', '{s4}', $value['content']);
                    $value['content'] = str_replace('{{var5}}', '{s5}', $value['content']);
                    $value['content'] = str_replace('{{var6}}', '{s6}', $value['content']);
                    $value['content'] = str_replace('{{var7}}', '{s7}', $value['content']);
                    $value['content'] = str_replace('{{var8}}', '{s8}', $value['content']);
                    $value['content'] = str_replace('{{var9}}', '{s9}', $value['content']);
                    $value['content'] = str_replace('{{var10}}', '{s10}', $value['content']);
                    $frame['content'] = base64_encode($value['content']);
                    $msg[] = $frame;
                }

                if (!empty($value['image_path'])) {
                    $frame = [];
                    $value['image_path'] = filtraImage(Config::get('qiniu.domain'), $value['image_path']);
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
                    $frame['content'] = $imagebase;
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
            $sign = "account=" . $data['account']  . "msg=" . $msg . "remark=" . $remark . "timestamp=" . $timestamp . "title=" . $data['title'];
            $sign = hash_hmac('sha256', $sign, $key);
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
                    $report_data = [];
                    $report_data = [
                        'channel_id' => $channel_id,
                        'template_id' => $template_id,
                        'third_template_id' => $report_msg_id,
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
                return ['code' => '3006', 'msg' => '该通道报备失败'];
            } else {
                return ['code' => '3006', 'msg' => '该通道报备失败'];
            }
        } elseif ($channel_id == 122) {

            $appid = '350171'; //appid由企业彩信平台提供 是
            $appkey = 'bac3a3c6ea6649f68ba1389d5f688aa9';
            // $timestamp =  //时间戳访问接口时间 单位：毫秒 是

            $timestamp = time();
            $time = microtime(true);
            //结果：1541053888.5911
            //在经过处理得到最终结果:
            $lastTime = (int)($time * 1000);
            $sign = md5($appkey . $appid . $lastTime . $appkey); //数字签名参考sign生成规则 是
            $report_api = 'http://47.110.195.237:8081/api/v2/mms/create?timestamp=' . $lastTime . '&appid=' . $appid . '&sign=' . $sign . '&mmstemplate=1';
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
                    if (strpos($value['content'],'{{var') == false) {
                        return ['code' => '3010','msg' => '该通道无法报备非变量模板'];
                    }
                    $value['content'] = str_replace('{{var1}}', '{1}', $value['content']);
                    $value['content'] = str_replace('{{var2}}', '{2}', $value['content']);
                    $value['content'] = str_replace('{{var3}}', '{3}', $value['content']);
                    $value['content'] = str_replace('{{var4}}', '{4}', $value['content']);
                    $value['content'] = str_replace('{{var5}}', '{5}', $value['content']);
                    $value['content'] = str_replace('{{var6}}', '{6}', $value['content']);
                    $value['content'] = str_replace('{{var7}}', '{7}', $value['content']);
                    $value['content'] = str_replace('{{var8}}', '{8}', $value['content']);
                    $value['content'] = str_replace('{{var9}}', '{9}', $value['content']);
                    $value['content'] = str_replace('{{var10}}', '{10}', $value['content']);
                    $content_data = [
                        'content_data' => trim($value['content']),
                        'content_type' => 'text/plain',
                    ];
                    $mmsbody[] = $content_data;
                }
                $content_data = [];
                if (!empty($value['image_path'])) {

                    $value['image_path'] = filtraImage(Config::get('qiniu.domain'), $value['image_path']);
                    $type = explode('.', $value['image_path']);


                    $content_data = [
                        'content_data' => base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path'])),
                        'content_type' => 'image/' . $type[1],
                    ];
                    $mmsbody[] = $content_data;
                }
            }
            $data['mmsbody'] = $mmsbody;
            $headers = [];
            $headers = [
                'Content-Type:text/plain'
            ];
            $result = $this->sendRequest2($report_api, 'post', $data, $headers);
            // $result = '{"msg":"成功","code":"T","data":{"mms_id":"60226","status":"R"}}';

            if (!empty($result)) {
                $result = json_decode($result, true);
                if ($result['msg'] == '成功') {
                    $report_msg_id = $result['data']['mms_id'];

                    $report_data = [];
                    $report_data = [
                        'channel_id' => $channel_id,
                        'template_id' => $template_id,
                        'third_template_id' => $report_msg_id,
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
                return ['code' => '3006', 'msg' => '该通道报备失败'];
            } else {
                return ['code' => '3006', 'msg' => '该通道报备失败'];
            }
        }elseif($channel_id == 123){
            $appid = '350171'; //appid由企业彩信平台提供 是
            $appkey = 'bac3a3c6ea6649f68ba1389d5f688aa9';
            // $timestamp =  //时间戳访问接口时间 单位：毫秒 是

            $timestamp = time();
            $time = microtime(true);
            //结果：1541053888.5911
            //在经过处理得到最终结果:
            $lastTime = (int)($time * 1000);
            $sign = md5($appkey . $appid . $lastTime . $appkey); //数字签名参考sign生成规则 是
            $report_api = 'http://47.110.195.237:8081/api/v2/mms/create?timestamp=' . $lastTime . '&appid=' . $appid . '&sign=' . $sign . '&mmstemplate=1';
            $data = [];
            $data['mms_title'] = $template['title'];
            $data['mms_type'] = 'multipart/mixed';
            $data['mmstemplate'] = 0;

            // print_r($multimedia_message_frame);die;
            $mmsbody = [];
            foreach ($multimedia_message_frame as $key => $value) {
                # code...
                $content_data = [];
                if (!empty($value['content'])) {
                    if (strpos($value['content'],'{{var') != false) {
                        return ['code' => '3008', 'msg' => '该通道无法报备变量模板'];
                    }
                    $content_data = [
                        'content_data' => trim($value['content']),
                        'content_type' => 'text/plain',
                    ];
                    $mmsbody[] = $content_data;
                }
                $content_data = [];
                if (!empty($value['image_path'])) {

                    $value['image_path'] = filtraImage(Config::get('qiniu.domain'), $value['image_path']);
                    $type = explode('.', $value['image_path']);


                    $content_data = [
                        'content_data' => base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path'])),
                        'content_type' => 'image/' . $type[1],
                    ];
                    $mmsbody[] = $content_data;
                }
            }
            $data['mmsbody'] = $mmsbody;
            $headers = [];
            $headers = [
                'Content-Type:text/plain'
            ];
            $result = $this->sendRequest2($report_api, 'post', $data, $headers);
            // $result = '{"msg":"成功","code":"T","data":{"mms_id":"60226","status":"R"}}';

            if (!empty($result)) {
                $result = json_decode($result, true);
                if ($result['msg'] == '成功') {
                    $report_msg_id = $result['data']['mms_id'];

                    $report_data = [];
                    $report_data = [
                        'channel_id' => $channel_id,
                        'template_id' => $template_id,
                        'third_template_id' => $report_msg_id,
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
                return ['code' => '3006', 'msg' => '该通道报备失败'];
            } else {
                return ['code' => '3006', 'msg' => '该通道报备失败'];
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

    public function addDeductWord($business_id, $uid = 0, $word)
    {
        if (!empty($uid)) {
            $user =  DbUser::getUserInfo(['id' => $uid], 'id,reservation_service,user_status,business_deduct', true);
            if (empty($user)) {
                return ['code' => '3003', 'msg' => '该用户不存在'];
            }
            $word = DbAdministrator::getUserDeductWord(['word' => $word, 'business_id' => $business_id], '*', true);
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

    public function getDeductWord($business_id, $page, $pageNum)
    {
        $offset = ($page - 1) * $pageNum;
        $result = DbAdministrator::getUserDeductWord(['business_id' => $business_id], '*', false, '', $page . ',' . $offset);
        $total = DbAdministrator::countUserDeductWord(['business_id' => $business_id]);
        return ['code' => '200', 'total' => $total, 'result' => $result];
    }

    public function updateDeductWord($id, $business_id, $uid, $word)
    {
        $word = DbAdministrator::getUserDeductWord(['id' => $id], '*', true);
        if (empty($word)) {
            return ['code' => '3003', 'msg' => '该记录不存在'];
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
                DbAdministrator::editUserDeductWord($data, $id);
                Db::commit();
                return ['code' => '200'];
            } catch (\Exception $e) {
                Db::rollback();
                exception($e);
                return ['code' => '3009']; //修改失败
            }
        }
        return ['code' => '3004', 'msg' => '没有需要修改的类目'];
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

    public function addSmsSendingChannel($title, $channel_type, $channel_host, $channel_port = '', $channel_source, $business_id, $channel_price = 0, $channel_postway = 1, $channel_source_addr, $channel_shared_secret, $channel_service_id, $channel_template_id = '', $channel_dest_id = '', $channel_flow_velocity = 0)
    {
        if (DbAdministrator::getSmsSendingChannel(['title' => $title], 'id', true)) {
            return ['code' => '3008', '名称重复，请另外命名'];
        }
        $data = [];
        $data = [
            'title' => $title,
            'channel_type' => $channel_type,
            'channel_host' => $channel_host,
            'channel_port' => $channel_port,
            'channel_source' => $channel_source,
            'business_id' => $business_id,
            'channel_price' => $channel_price,
            'channel_postway' => $channel_postway,
            'channel_source_addr' => $channel_source_addr,
            'channel_shared_secret' => $channel_shared_secret,
            'channel_service_id' => $channel_service_id,
            'channel_template_id' => $channel_template_id,
            'channel_dest_id' => $channel_dest_id,
            'channel_flow_velocity' => $channel_flow_velocity,
        ];

        Db::startTrans();
        try {
            // DbAdministrator::modifyBalance($userEquities['id'], $num, 'dec');
            DbAdministrator::addSmsSendingChannel($data);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            exception($e);
            return ['code' => '3009']; //修改失败
        }
    }

    public function getSmsSendingChannel($title = '', $channel_type = 0, $channel_host = '', $channel_port = '', $channel_source = 0, $business_id = 0, $channel_price = 0, $channel_postway = 0, $channel_source_addr = '', $channel_shared_secret = '', $channel_service_id = '', $channel_template_id = '', $channel_dest_id = '', $channel_flow_velocity = 0, $page, $pageNum)
    {
        $where = [];
        if (!empty($title)) {
            array_push($where, ['title', 'like', '%' . $title . '%']);
        }
        if (!empty($channel_type)) {
            array_push($where, ['channel_type', '=', $channel_type]);
        }
        if (!empty($channel_host)) {
            array_push($where, ['channel_host', '=', $channel_host]);
        }
        if (!empty($channel_port)) {
            array_push($where, ['channel_port', '=', $channel_port]);
        }
        if (!empty($channel_source)) {
            array_push($where, ['channel_source', '=', $channel_source]);
        }
        if (!empty($business_id)) {
            array_push($where, ['business_id', '=', $business_id]);
        }
        if (!empty($channel_price)) {
            array_push($where, ['channel_price', '=', $channel_price]);
        }
        if (!empty($channel_postway)) {
            array_push($where, ['channel_postway', '=', $channel_postway]);
        }
        if (!empty($channel_source_addr)) {
            array_push($where, ['channel_source_addr', '=', $channel_source_addr]);
        }
        if (!empty($channel_shared_secret)) {
            array_push($where, ['channel_shared_secret', '=', $channel_shared_secret]);
        }
        if (!empty($channel_service_id)) {
            array_push($where, ['channel_service_id', '=', $channel_service_id]);
        }
        if (!empty($channel_template_id)) {
            array_push($where, ['channel_template_id', '=', $channel_template_id]);
        }
        if (!empty($channel_dest_id)) {
            array_push($where, ['channel_dest_id', '=', $channel_dest_id]);
        }
        if (!empty($channel_flow_velocity)) {
            array_push($where, ['channel_flow_velocity', '=', $channel_flow_velocity]);
        }
        $offset = ($page - 1) * $pageNum;
        $result = DbAdministrator::getSmsSendingChannel($where, '*', false, '', $offset . ',' . $pageNum);
        $total = DbAdministrator::countSmsSendingChannel($where);
        return ['code' => '200', 'total' => $total, 'channel_list' => $result];
    }

    public function editSmsSendingChannel($id, $title = '', $channel_type = 0, $channel_host = '', $channel_port = '', $channel_source = 0, $business_id = 0, $channel_price = 0, $channel_postway = 0, $channel_source_addr = '', $channel_shared_secret = '', $channel_service_id = '', $channel_template_id = '', $channel_dest_id = '', $channel_flow_velocity = 0)
    {
        $data = [];
        if (!empty($title)) {
            $data['title'] = $title;
        }
        if (!empty($channel_type)) {
            $data['channel_type'] = $channel_type;
        }
        if (!empty($channel_host)) {
            $data['channel_host'] = $channel_host;
        }
        if (!empty($channel_port)) {
            $data['channel_port'] = $channel_port;
        }
        if (!empty($channel_source)) {
            $data['channel_source'] = $channel_source;
        }
        if (!empty($business_id)) {
            $data['business_id'] = $business_id;
        }
        if (!empty($channel_price)) {
            $data['channel_price'] = $channel_price;
        }
        if (!empty($channel_postway)) {
            $data['channel_postway'] = $channel_postway;
        }
        if (!empty($channel_source_addr)) {
            $data['channel_source_addr'] = $channel_source_addr;
        }
        if (!empty($channel_shared_secret)) {
            $data['channel_shared_secret'] = $channel_shared_secret;
        }
        if (!empty($channel_service_id)) {
            $data['channel_service_id'] = $channel_service_id;
        }
        if (!empty($channel_template_id)) {
            $data['channel_template_id'] = $channel_template_id;
        }
        if (!empty($channel_dest_id)) {
            $data['channel_dest_id'] = $channel_dest_id;
        }
        if (!empty($channel_flow_velocity)) {
            $data['channel_flow_velocity'] = $channel_flow_velocity;
        }
        if (empty($data)) {
            return ['code' => '3002', 'msg' => '修改内容为空'];
        }
        $channel = DbAdministrator::getSmsSendingChannel(['id' => $id], '*', true);
        if (empty($channel)) {
            return ['code' => '3003', 'msg' => '该通道不存在'];
        }
        Db::startTrans();
        try {
            // DbAdministrator::modifyBalance($userEquities['id'], $num, 'dec');
            DbAdministrator::editSmsSendingChannel($data, $id);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            exception($e);
            return ['code' => '3009']; //修改失败
        }
    }

    //账户归属:1,中国移动;2,中国联通;3,中国电信;4,三网通;5,移动联通;6,移动电信;7,联通电信
    public function setUserAccountForCmpp($pid, $cmpp_name, $account_host, $cmpp_dest_id, $business_id)
    {

        $user = DbUser::getUserInfo(['id' => $pid], 'id,nick_name', true);
        if (empty($user)) {
            return ['code' => '3006', 'msg' => '该用户不存在'];
        }
        // $cmpp_account = DbAdministrator::getUserCmppAccount(['cmpp_name' => $cmpp_name], 'id', true);
        // if (!empty($cmpp_account)) {
        //     return ['code' => '3007', 'msg' => '命名重复'];
        // }
       /*  if (!empty($yidong_channel_id)) {
            $yd_channel = DbAdministrator::getSmsSendingChannel(['id' => $yidong_channel_id], '*', true);
            if (empty($yd_channel)) {
                return ['code' => '3008', 'msg' => '该通道不存在'];
            }
            if (!in_array($yd_channel['channel_source'], [1, 4, 5, 6])) {
                return ['code' => '3009', 'msg' => '分配的通道不支持移动号段'];
            }
        }
        if (!empty($liantong_channel_id)) {
            $lt_channel = DbAdministrator::getSmsSendingChannel(['id' => $liantong_channel_id], '*', true);
            if (empty($lt_channel)) {
                return ['code' => '3008', 'msg' => '该通道不存在'];
            }
            if (!in_array($lt_channel['channel_source'], [2, 4, 5, 7])) {
                return ['code' => '3010', 'msg' => '分配的通道不支持联通号段'];
            }
        }
        if (!empty($dianxin_channel_id)) {
            $dx_channel = DbAdministrator::getSmsSendingChannel(['id' => $dianxin_channel_id], '*', true);
            if (empty($dx_channel)) {
                return ['code' => '3008', 'msg' => '该通道不存在'];
            }
            if (!in_array($dx_channel['channel_source'], [3, 4, 6, 7])) {
                return ['code' => '3011', 'msg' => '分配的通道不支持电信号段'];
            }
        } */
        do {
            $nick_name = "C" . mt_rand(10000, 99999);
            $cmpp_user = DbUser::getUserInfo(['nick_name' => $nick_name], 'id', true);
        } while ($cmpp_user);
        $cmpp_password = getRandomString(8);
        $data = [];
        $data = [
            'pid' => $pid,
            'nick_name' => $nick_name,
            'company_name' => $cmpp_name,
            'account_host' => $account_host,
            'cmpp_dest_id' => $cmpp_dest_id,
            'cmpp_password' => $cmpp_password,
            'business_id' => $business_id,
            'appid'     => uniqid(''),
            'appkey'     => md5(uniqid('')),
            'user_type' => 3,
        ];
        Db::startTrans();
        try {
            // DbAdministrator::modifyBalance($userEquities['id'], $num, 'dec');
            $uid = DbUser::addUser($data); //添加后生成的uid
            Dbuser::updateUser(['user_type' => 2], $pid);
            $conId = $this->createConId();
            DbUser::addUserCon(['uid' => $uid, 'con_id' => $conId]);
            $this->redis->zAdd($this->redisConIdTime, time(), $conId);
            $conUid = $this->redis->hSet($this->redisConIdUid, $conId, $uid);
            if ($conUid === false) {
                $this->redis->zRem($this->redisConIdTime, $conId);
                $this->redis->hDel($this->redisConIdUid, $conId);
                Db::rollback();
            }
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            exception($e);
            return ['code' => '3009']; //修改失败
        }
    }

    /**
     * 创建唯一conId
     * @author zyr
     */
    private function createConId()
    {
        $conId = uniqid(date('ymdHis'));
        $conId = hash_hmac('ripemd128', $conId, '');
        return $conId;
    }

    public function thirdPartySupMessageTemplateReport($channel_id, $template_id)
    {
        $template =  DbSendMessage::getUserSupMessageTemplate(['template_id' => $template_id], '*', true);
        if ($template['status'] != 2 || empty($template)) {
            return ['code' => '3003', 'msg' => '模板未审核通过或者该模板不存在'];
        }
        $multimedia_message_frame = DbSendMessage::getUserSupMessageTemplateFrame(['multimedia_template_id' => $template['id']], 'num,content,type,content_type', false, ['num' => 'asc']);
        $model_val = 0; //模板类型 0，普通彩信, 1 模板变量彩信
       /*  foreach ($multimedia_message_frame as $key => $value) {
            if (strpos($value['content'], '{{var') != false) {
                $model_val = 1;
                break;
            }
        } */
        $channel = DbAdministrator::getSmsSendingChannel(['id' => $channel_id], 'id,title,business_id,channel_price', true);
        if (empty($channel)) {
            return ['code' => '3002'];
        }
        if ($channel['business_id'] != 11) {
            return ['code' => '3004', '非视频短信通道不能使用此接口'];
        }
        $had_report = DbAdministrator::getUserSupMessageTemplateThirdReport(['channel_id' => $channel_id, 'template_id' => $template_id], 'id', true);
        if (!empty($had_report)) {
            return ['code' => '3007', 'msg' => '该模板已在该通道报备过'];
        }
        //创蓝
        if ($channel_id == 133) {//上海领道
           $appid = '350393';
           $apikey = 'c538bea5c5f141a0ba07965564bf723c';
            $time = microtime(true);
            //结果：1541053888.5911
            //在经过处理得到最终结果:
            $lastTime = (int)($time * 1000);
            $sign = md5($apikey . $appid . $lastTime . $apikey); //数字签名参考sign生成规则 是
            $report_api = 'http://47.101.30.221:8081/api/v2/mms/create?timestamp=' . $lastTime . '&appid=' . $appid . '&sign=' . $sign . '&mmstemplate=1';
            $data = [];
            $data['mms_title'] = $template['title'];
            $data['mmsSign'] = $template['signature'];
            $data['mms_type'] = 'multipart/related';
            $data['mmstemplate'] = 0;

            // print_r($multimedia_message_frame);die;
            $mmsbody = [];
            foreach ($multimedia_message_frame as $key => $value) {
                # code...
               /*  $content_data = [];
                if (!empty($value['content'])) {
                    if (strpos($value['content'],'{{var') == false) {
                        return ['code' => '3010','msg' => '该通道无法报备非变量模板'];
                    }
                    $value['content'] = str_replace('{{var1}}', '{1}', $value['content']);
                    $value['content'] = str_replace('{{var2}}', '{2}', $value['content']);
                    $value['content'] = str_replace('{{var3}}', '{3}', $value['content']);
                    $value['content'] = str_replace('{{var4}}', '{4}', $value['content']);
                    $value['content'] = str_replace('{{var5}}', '{5}', $value['content']);
                    $value['content'] = str_replace('{{var6}}', '{6}', $value['content']);
                    $value['content'] = str_replace('{{var7}}', '{7}', $value['content']);
                    $value['content'] = str_replace('{{var8}}', '{8}', $value['content']);
                    $value['content'] = str_replace('{{var9}}', '{9}', $value['content']);
                    $value['content'] = str_replace('{{var10}}', '{10}', $value['content']);
                   
                }
                $content_data = [];
                if (!empty($value['image_path'])) {

                    $value['image_path'] = filtraImage(Config::get('qiniu.domain'), $value['image_path']);
                    $type = explode('.', $value['image_path']);


                    $content_data = [
                        'content_data' => base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path'])),
                        'content_type' => 'image/' . $type[1],
                    ];
                    $mmsbody[] = $content_data;
                } */
                if ($value['type'] == 1) {
                    $content_data = [
                        'content_data' => trim($value['content']),
                        'content_type' => $value['content_type'],
                    ];
                }elseif($value['type'] == 2){
                    $content_data = [
                        'content_data' =>  base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['content'])),
                        'content_type' => $value['content_type'],
                    ];
                }else{
                    $content_data = [
                        'content_data' =>  base64_encode(file_get_contents(Config::get('qiniu.videodomain') . '/' . $value['content'])),
                        'content_type' => $value['content_type'],
                    ];
                }
               
                $mmsbody[] = $content_data;
            }
            $data['mmsbody'] = $mmsbody;
            $headers = [];
            $headers = [
                'Content-Type:text/plain'
            ];
            $result = $this->sendRequest2($report_api, 'post', $data, $headers);
            // $result = '{"msg":"成功","code":"T","data":{"mms_id":"60226","status":"R"}}';
            // print_r($result);die;
            if (!empty($result)) {
                $result = json_decode($result, true);
                if ($result['msg'] == '成功') {
                    $report_msg_id = $result['data']['mms_id'];

                    $report_data = [];
                    $report_data = [
                        'channel_id' => $channel_id,
                        'template_id' => $template_id,
                        'third_template_id' => $report_msg_id,
                    ];
                    Db::startTrans();
                    try {
                        DbAdministrator::addUserSupMessageTemplateThirdReport($report_data);
                        Db::commit();
                        return ['code' => '200'];
                    } catch (\Exception $th) {
                        exception($th);
                        Db::rollback();
                        return ['code' => '3009']; //修改失败
                    }
                }
                return ['code' => '3006', 'msg' => '该通道报备失败'];
            } else {
                return ['code' => '3006', 'msg' => '该通道报备失败'];
            }
        } elseif ($channel_id == 134) { //创蓝视频短信通道
            $appId = 'OutaQ7XImf';
            $appSecret = 'YDM7FY8uCYhbYg'; 
            $autoCheck = true;
            $templateName = $template['title'];
            $sign = '【'.$template['signature'].'】';
            $mmsbody = [];
            foreach ($multimedia_message_frame as $key => $value) {
                $type = explode('.',$value['content']);
                // print_r($type);die;
                if ($value['type'] == 1) {
                    $content_data = [
                        'content' => trim($value['content']),
                        'type' => 'text',
                        'exType' => 'txt',
                        'name' => '文本',
                        'sort' => $value['num']
                    ];
                }elseif($value['type'] == 2){
                    $content_data = [
                        'content' =>  base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['content'])),
                        'type' => 'image',
                        'exType' => $type[1],
                        'name' => '图片',
                        'sort' => $value['num']
                    ];
                }elseif($value['type'] == 3){
                    $content_data = [
                        'content' =>  base64_encode(file_get_contents(Config::get('qiniu.videodomain') . '/' . $value['content'])),
                        'type' => 'audio',
                        'exType' => $type[1],
                        'name' => '音频',
                        'sort' => $value['num']
                    ];
                }elseif($value['type'] == 4){
                    $content_data = [
                        'content' =>  base64_encode(file_get_contents(Config::get('qiniu.videodomain') . '/' . $value['content'])),
                        'type' => 'video',
                        'exType' => $type[1],
                        'name' => '视频',
                        'sort' => $value['num']
                    ];
                }
                $context[] = $content_data;
            }
            // print_r($context);die;
            /* 
            {
                "appId": "ZaVfxkGf5Q",  
                "appSecret": "rXQpfGYXmoSOqA",
                "autoCheck": false,
                "body": [{"content":"","extType":"jpg","name":"API图片","sort":1,"type":"image"}],
                "templateName": "视频短信测试",
                "sign":"【上海赛豪文化】"
            }

 */
            $headers = [];
            $headers = [
                'Content-Type:application/json'
            ];
            
            $data = [
                'appId' => $appId,
                'appSecret' => $appSecret,
                'templateName' => $templateName,
                'autoCheck' => $autoCheck,
                'body' => $context,
                'sign' => $sign,
            ];
            // print_r(json_encode($data));die;
            // $res = sendRequest('https://rcs.253.com/rcs/api/template/addVideo','post',$data);
            $res = $this->sendRequest4('https://rcs.253.com/rcs/api/template/addVideo', 'post', $data, $headers);
            if (!empty($res)) {
                $result = json_decode($res, true);
                if ($result['code'] == 102000) {
                    $report_msg_id = $result['data']['templateId'];

                    $report_data = [];
                    $report_data = [
                        'channel_id' => $channel_id,
                        'template_id' => $template_id,
                        'third_template_id' => $report_msg_id,
                    ];
                    Db::startTrans();
                    try {
                        DbAdministrator::addUserSupMessageTemplateThirdReport($report_data);
                        Db::commit();
                        return ['code' => '200'];
                    } catch (\Exception $th) {
                        exception($th);
                        Db::rollback();
                        return ['code' => '3009']; //修改失败
                    }
                }
                return ['code' => '3006', 'msg' => '该通道报备失败'];
            } else {
                return ['code' => '3006', 'msg' => '该通道报备失败'];
            }

        } elseif ($channel_id == 135) {//三体视频彩信通道
            $appId = '12232';
            $apikey = '42f66a29eb';
            $title = $template['title'];
            $name = $template['name'];
            // $sign = md5('appId='.$appId.'&mobile=15821193682&'.'&apikey='.$apikey);
            // $signature = $template['signature'];
            $signature = '【'.$template['signature'].'】';
            $context = [];
            foreach ($multimedia_message_frame as $key => $value) {
                if ($value['type'] == 1) {
                    if (!empty($signature)) {
                        $content_data = [
                            'content' => $signature.trim($value['content']),
                            'type' => 'text',
                        ];
                        unset($signature);
                    }else{
                        $content_data = [
                            'content' => trim($value['content']),
                            'type' => 'text',
                        ];
                    }
                }elseif($value['type'] == 2){
                    $content_data = [
                        'content' =>  Config::get('qiniu.domain') . '/' . $value['content'],
                        'type' => 'image',
                    ];
                }elseif($value['type'] == 3){
                    $content_data = [
                        'content' =>  Config::get('qiniu.videodomain') . '/' . $value['content'],
                        'type' => 'audio',
                    ];
                }elseif($value['type'] == 4){
                    $content_data = [
                        'content' =>  Config::get('qiniu.videodomain') . '/' . $value['content'],
                        'type' => 'video',
                    ];
                }
                $context[] = $content_data;
            }
            array_push($context,['content' => '此信息免流', 'type' => 'text']);
            // print_r($context);die;
            $timestamp = time();
            $sign = md5(urlencode($apikey.$appId.$name.$title.json_encode($context).$timestamp));
            $data = [];
            $data = [
                'appId' => $appId,
                'title' => $title,
                'name' => $name,
                'context' => json_encode($context),
                'timestamp' => $timestamp,
                'sign' => $sign,
            ];
            $result = $this->sendRequest3('http://api.santiyun.com/api/vsmsMode/addMode', 'post', $data);
            // print_r($result);die;
            if (!empty($result)) {
                $result = json_decode($result, true);
                if ($result['code'] == 0) {
                    $report_msg_id = $result['rets']['modeId'];

                    $report_data = [];
                    $report_data = [
                        'channel_id' => $channel_id,
                        'template_id' => $template_id,
                        'third_template_id' => $report_msg_id,
                    ];
                    Db::startTrans();
                    try {
                        DbAdministrator::addUserSupMessageTemplateThirdReport($report_data);
                        Db::commit();
                        return ['code' => '200'];
                    } catch (\Exception $th) {
                        exception($th);
                        Db::rollback();
                        return ['code' => '3009']; //修改失败
                    }
                }
                return ['code' => '3006', 'msg' => '该通道报备失败'];
            } else {
                return ['code' => '3006', 'msg' => '该通道报备失败'];
            }
            
        } elseif ($channel_id == 136) {
            $appid = '350394';
            $apikey = 'c89c00a99999432faf35893786c10a48';
             $time = microtime(true);
             //结果：1541053888.5911
             //在经过处理得到最终结果:
             $lastTime = (int)($time * 1000);
             $sign = md5($apikey . $appid . $lastTime . $apikey); //数字签名参考sign生成规则 是
             $report_api = 'http://47.101.30.221:8081/api/v2/mms/create?timestamp=' . $lastTime . '&appid=' . $appid . '&sign=' . $sign . '&mmstemplate=1';
             $data = [];
             $data['mms_title'] = $template['title'];
             $data['mmsSign'] = $template['signature'];
             $data['mms_type'] = 'multipart/related';
             $data['mmstemplate'] = 0;
 
             // print_r($multimedia_message_frame);die;
             $mmsbody = [];
             foreach ($multimedia_message_frame as $key => $value) {
                 if ($value['type'] == 1) {
                     $content_data = [
                         'content_data' => trim($value['content']),
                         'content_type' => $value['content_type'],
                     ];
                 }elseif($value['type'] == 2){
                     $content_data = [
                         'content_data' =>  base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['content'])),
                         'content_type' => $value['content_type'],
                     ];
                 }else{
                     $content_data = [
                         'content_data' =>  base64_encode(file_get_contents(Config::get('qiniu.videodomain') . '/' . $value['content'])),
                         'content_type' => $value['content_type'],
                     ];
                 }
                
                 $mmsbody[] = $content_data;
             }
             $data['mmsbody'] = $mmsbody;
             $headers = [];
             $headers = [
                 'Content-Type:text/plain'
             ];
             $result = $this->sendRequest2($report_api, 'post', $data, $headers);
             // $result = '{"msg":"成功","code":"T","data":{"mms_id":"60226","status":"R"}}';
             // print_r($result);die;
             if (!empty($result)) {
                 $result = json_decode($result, true);
                 if ($result['msg'] == '成功') {
                     $report_msg_id = $result['data']['mms_id'];
 
                     $report_data = [];
                     $report_data = [
                         'channel_id' => $channel_id,
                         'template_id' => $template_id,
                         'third_template_id' => $report_msg_id,
                     ];
                     Db::startTrans();
                     try {
                         DbAdministrator::addUserSupMessageTemplateThirdReport($report_data);
                         Db::commit();
                         return ['code' => '200'];
                     } catch (\Exception $th) {
                         exception($th);
                         Db::rollback();
                         return ['code' => '3009']; //修改失败
                     }
                 }
                 return ['code' => '3006', 'msg' => '该通道报备失败'];
             } else {
                 return ['code' => '3006', 'msg' => '该通道报备失败'];
             }
           
        }
    }


    function sendRequest3($requestUrl, $method = 'get', $data = [])
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
            curl_setopt($curl, CURLOPT_POSTFIELDS,json_encode($data));
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

    function sendRequest4($requestUrl, $method = 'get', $data = [], $headers)
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

    public function getThirdPartySupMessageTemplateReportInfo($template_id, $page, $pageNum){
        $template =  DbSendMessage::getUserSupMessageTemplate(['template_id' => $template_id], '*', true);
        if ($template['status'] != 2 || empty($template)) {
            return ['code' => '3003', 'msg' => '模板未审核通过或者该模板不存在'];
        }
        $offect = ($page - 1) * $pageNum;
        if ($offect < 0) {
            return ['code' => 200, 'total' => 0, 'result' =>[]];
        }
        $had_report = DbAdministrator::getUserSupMessageTemplateThirdReport(['template_id' => $template_id], '*', false,'',$offect.','.$pageNum);
        foreach ($had_report as $key => $value) {
            $had_report[$key]['channel_name'] = DbAdministrator::getSmsSendingChannel(['id' => $value['channel_id']], 'id,title,business_id,channel_price', true)['title'];
        }
        $total = DbAdministrator::countUserSupMessageTemplateThirdReport(['template_id' => $template_id]);
        return ['code' => 200, 'total' => $total, 'result' =>$had_report];
    }

    public function setThirdPartySupMessageTemplateReportInfo($id, $yd_report_status = 0, $lt_report_status = 0, $dx_report_status = 0){
        $had_report =  DbAdministrator::getUserSupMessageTemplateThirdReport(['id' => $id], 'id,template_id', true);
        if (empty($had_report)){
            return ['code' => '3000', '该报备记录不存在'];
        }
        $template =  DbSendMessage::getUserSupMessageTemplate(['template_id' => $had_report['template_id']], 'id', true);
        $data = [
            'yd_report_status' => $yd_report_status,
            'lt_report_status' => $lt_report_status,
            'dx_report_status' => $dx_report_status,
        ];
        /* 获取全网报备状态 */
        $templage_report_status = 1;
        if ($yd_report_status == 2) {
            $had_lt_report =  DbAdministrator::getUserSupMessageTemplateThirdReport(['lt_report_status' => 2,'template_id' => $had_report['template_id']], 'id,template_id', true);
            $had_dx_report =  DbAdministrator::getUserSupMessageTemplateThirdReport(['dx_report_status' => 2,'template_id' => $had_report['template_id']], 'id,template_id', true);
            if (!empty($had_lt_report) && !empty($had_dx_report)) {
                $templage_report_status = 2;
            }
        }elseif($lt_report_status == 2) {
            $had_yd_report =  DbAdministrator::getUserSupMessageTemplateThirdReport(['yd_report_status' => 2,'template_id' => $had_report['template_id']], 'id,template_id', true);
            $had_dx_report =  DbAdministrator::getUserSupMessageTemplateThirdReport(['dx_report_status' => 2,'template_id' => $had_report['template_id']], 'id,template_id', true);
            if (!empty($had_yd_report) && !empty($had_dx_report)) {
                $templage_report_status = 2;
            }
        }elseif($dx_report_status == 2) {
            $had_yd_report =  DbAdministrator::getUserSupMessageTemplateThirdReport(['yd_report_status' => 2,'template_id' => $had_report['template_id']], 'id,template_id', true);
            $had_lt_report =  DbAdministrator::getUserSupMessageTemplateThirdReport(['lt_report_status' => 2,'template_id' => $had_report['template_id']], 'id,template_id', true);
            if (!empty($had_yd_report) && !empty($had_lt_report)) {
                $templage_report_status = 2;
            }
        }
        Db::startTrans();
        try {
            DbAdministrator::editUserSupMessageTemplateThirdReport($data,$id);
            if ($templage_report_status == 2) {
                DbSendMessage::editUserSupMessageTemplate(['report_status'=>$templage_report_status],$template['id']);
            }
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $th) {
            exception($th);
            Db::rollback();
            return ['code' => '3009']; //修改失败
        }
    }
}
