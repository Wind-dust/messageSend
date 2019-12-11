<?php

namespace app\common\action\admin;

use app\facade\DbAdministrator;
use app\facade\DbSendMessage;
use think\Db;

class Message extends CommonIndex {
     /**
     * @param $page
     * @param $pageNum
     * @return array
     * @author rzc
     */
    public function  getMultimediaMessageTask($page, $pageNum, $id = 0, $title = ''){
        $offset = ($page - 1) * $pageNum;
        $where = [];
        if (!empty($id)) {
            $result = DbSendMessage::getUserMultimediaMessage(['id' => $id], '*', true);
            $result['content'] = DbSendMessage::getUserMultimediaMessageFrame(['multimedia_message_id' => $id],'*',false,['num' => 'asc']);
        } else {
            if (empty($title)) {
                array_push($where,['title', 'like', '%'.$title.'%']);
            }
            $result = DbSendMessage::getUserMultimediaMessage($where, '*', false, '', $offset . ',' . $pageNum);
            foreach ($result as $key => $value) {
                $result[$key]['content'] = DbSendMessage::getUserMultimediaMessageFrame(['multimedia_message_id' => $value['id']],'*',false,['num' => 'asc']);
            }
        }
        $total = DbSendMessage::countUserMultimediaMessage($where);
        if ($id) {
            $total = 1;
        }
        
        return ['code' => '200', 'data' => $result];
    }

    public function auditMultimediaMessageTask($effective_id = [], $free_trial) {
        // print_r($effective_id);die;
        $userchannel = DbSendMessage::getUserMultimediaMessage([['id', 'in', join(',', $effective_id)]], 'id,mobile_content,free_trial', false);

        if (empty($userchannel)) {
            return ['code' => '3001'];
        }
        $real_effective_id = [];
        // print_r($userchannel);die;
        foreach ($userchannel as $key => $value) {
            if ($value['free_trial'] > 1) {
                continue;
            }
            $real_effective_id[] = $value['id'];
        }

        Db::startTrans();
        try {
            foreach ($real_effective_id as $real => $efid) {
                DbSendMessage::editUserMultimediaMessage(['free_trial' => $free_trial], $efid);
            }
            Db::commit();
            return ['code' => '200'];

        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3009']; //修改失败
        }
    }

    public function distributionChannel($effective_id = [], $channel_id, $business_id) {
        $channel = DbAdministrator::getSmsSendingChannel(['id' => $channel_id, 'business_id' => $business_id], 'id,title,channel_price', true);
        if (empty($channel)) {
            return ['code' => '3002'];
        }
        $usertask = DbSendMessage::getUserMultimediaMessage([['id', 'in', join(',', $effective_id)]], 'id,uid,mobile_content,task_content,free_trial,send_num,channel_id', false);
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
            }elseif (!in_array($value['uid'], $uids)) {
                $uids[] = $value['uid'];
            }
            // print_r($value);
            if ($value['free_trial'] == 2 && !$value['channel_id']) {
                $real_length = 1;
                $real_usertask[] = $value;
                $mobilesend       = explode(',', $value['mobile_content']);
                // $send_length     = mb_strlen($value['task_content'], 'utf8');
                // if ($send_length > 70) {
                //     $real_length = ceil($send_length / 67);
                // }
                $num += ($real_length* $value['send_num']);
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
            return ['code' => '3010','msg' => '待分配的批量任务未空（提交了一批未审核的批量任务）'];
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
        if ($num > $userEquities['num_balance'] && $user['reservation_service'] != 2) {
            return ['code' => '3007'];
        }
        $free_trial = 2;
        if ($userEquities['agency_price'] < $channel['channel_price']) {
            $free_trial = 4;
        }
        Db::startTrans();
        try {

            // DbAdministrator::modifyBalance($userEquities['id'], $num, 'dec');
            foreach ($real_usertask as $key => $value) {
                DbSendMessage::editUserMultimediaMessage(['free_trial' => $free_trial, 'channel_id' => $channel_id], $value['id']);
            }
            if ($free_trial == 2) {
                foreach ($real_usertask as $real => $usertask) {
                    $res = $this->redis->rpush("index:meassage:multimediamessage:sendtask",$usertask['id']); 
                }
            }
  
            Db::commit();
            return ['code' => '200'];

        } catch (\Exception $e) {
            exception($e);
            Db::rollback();
            return ['code' => '3009']; //修改失败
        }
    }
}