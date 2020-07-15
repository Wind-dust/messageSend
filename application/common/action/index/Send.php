<?php

namespace app\common\action\index;

use app\common\action\index\Owncmpp;
use app\facade\DbAdmin;
use app\facade\DbAdministrator;
use app\facade\DbImage;
use app\facade\DbMobile;
use app\facade\DbProvinces;
use app\facade\DbSendMessage;
use cache\Phpredis;
use app\facade\DbUser;
use Config;
use Env;
use think\Db;

class Send extends CommonIndex
{

    public function smsBatch($Username, $Password, $Content, $Mobiles, $Dstime, $ip)
    {
        // $Password = md5($Password);
        $user = DbUser::getUserOne(['appid' => $Username], 'id,appkey,user_type,user_status,reservation_service,free_trial', true);
        if (empty($user)) {
            return -1;
        }
        if ($Password != $user['appkey']) {
            return -1;
        }
        $effective_mobile = [];
        foreach ($Mobiles as $key => $value) {
            if (checkMobile(($value))) {
                $effective_mobile[] = $value;
            }
        }
        if (empty($effective_mobile)) {
            return 2;
        }
        // $Content           = $this->dbc2Sbc($Content);
        $send_num          = count($Mobiles);
        $data              = [];
        $data['uid']       = $user['id'];
        $data['source']    = $ip;
        $data['task_name'] = $Content;
        $start             = mb_strpos($Content, '【');
        if ($start != 0) {
            $length     = mb_strpos($Content, '】') - mb_strpos($Content, '【') + 1;
            $all_length = mb_strlen($Content, 'utf8');
            $remain     = mb_substr($Content, 0, $all_length - $length);
            $Content    = '【米思米】' . $remain;
        }
        // echo $Content;die;
        $data['task_content']      = $Content;
        $data['send_length']       = mb_strlen($Content);
        $data['mobile_content']    = join(',', $effective_mobile);
        $data['send_num']          = $send_num;
        $data['free_trial']        = 1;
        $data['task_no']           = 'mar' . date('ymdHis') . substr(uniqid('', true), 15, 8);
        $id                        = DbAdministrator::addUserSendTask($data);
        $redisMessageMarketingSend = Config::get('rediskey.message.redisMessageMarketingSend');
        // foreach ($effective_mobile as $key => $value) {
        //     $this->redis->rpush($redisMessageMarketingSend.":2",$value.":".$id.":".$Content); //三体营销通道
        //     // $this->redis->hset($redisMessageMarketingSend.":2",$value,$id.":".$Content); //三体营销通道
        // }
        $result = 1;
        $result = $result . ',' . $data['task_no'];
        return $result;
        /*         if ($send_num > 1) { //多条号码认定为营销

} else { //行业
//将行业短信写入任务并写入缓存
$data['task_no'] = 'bus' . date('ymdHis') . substr(uniqid('',true),15,8);
$id = DbAdministrator::addUserSendCodeTask($data);
$redisMessageCodeSend = Config::get('rediskey.message.redisMessageCodeSend');
foreach ($effective_mobile as $key => $value) {
// $this->redis->hset($redisMessageCodeSend.":1",$value,$id.":".$Content); //三体行业通道
// $this->redis->rpush($redisMessageCodeSend.":1",$value.":".$id.":".$Content); //三体行业通道
}
$result = "1,".$data['task_no'];
return $result;
} */
    }

    public function getBalanceSmsBatch($Username, $Password)
    {
        // $Password = md5($Password);
        $user = DbUser::getUserOne(['appid' => $Username], 'id,appkey,user_type,user_status,reservation_service,free_trial', true);
        // print_r($Username);die;
        if (empty($user)) {
            return -1;
        }
        if ($Password != $user['appkey']) {
            return -1;
        }
        $result = DbAdministrator::getUserEquities(['uid' => $user['id']], 'business_id,num_balance', true);

        return $result['num_balance'];
    }

    public function getReceiveSmsBatch($Username, $Password)
    {
        // $Password = md5($Password);
        $user = DbUser::getUserOne(['appid' => $Username], 'id,appkey,user_type,user_status,reservation_service', true);
        if (empty($user)) {
            return -1;
        }
        if ($Password != $user['appkey']) {
            return -1;
        }
        $log = DbAdministrator::getUserSendTaskLog(['uid' => $user['id']], 'id,mobile,send_status,create_time', false, ['id' => 'desc'], 50);
        if (!empty($log)) {
            $e      = '';
            $result = '';
            foreach ($log as $key => $value) {
                $result .= join(',', $value) . $e;
                $e = ';';
            }
            return $result;
        }
        return 0;
    }

    public function getSmsMarketingTask($Username, $Password, $Content, $Mobiles, $Dstime, $ip, $task_name, $signature_id = '', $develop_no = '', $msg_id = '')
    {
        $Mobiles = array_unique(array_filter($Mobiles));
        // $Password = md5($Password);
        $user = DbUser::getUserOne(['appid' => $Username], 'id,pid,appkey,user_type,user_status,reservation_service,marketing_free_trial,marketing_free_credit,market_deduct', true);
        if (empty($user)) {
            return ['code' => '3000'];
        }
        if ($Password != $user['appkey']) {
            return ['code' => '3000'];
        }
        $userEquities = DbAdministrator::getUserEquities(['uid' => $user['id'], 'business_id' => 5], 'id,agency_price,num_balance', true);
        if (empty($userEquities)) {
            return ['code' => '3008'];
        }
        if ($user['user_status'] != 2) {
            return ['code' => '3009'];
        }
        $send_num = count(array_filter($Mobiles));

        $effective_mobile = [];
        foreach ($Mobiles as $key => $value) {
            if (checkMobile($value) == true) {
                $effective_mobile[] = $value;
            }
        }

        if (!empty($signature_id)) {
            $signature =  DbSendMessage::getUserSignature(['uid' => $user['id'], 'signature_id' => $signature_id], '*', true);
            if (empty($signature)) {
                return ['code' => '3008'];
            }
            if ($signature['status'] != 2) {
                return ['code' => '3010'];
            }
            $Content = $signature['title'] . $Content;
        }

        // print_r($signature);die;
        if (empty($effective_mobile)) {
            return ['code' => '3010', 'msg' => '有效手机号为空'];
        }
        if (mb_strlen($Content) > 70) {
            $real_num = ceil(mb_strlen($Content) / 67) * count($effective_mobile);
        } else {
            $real_num = count($effective_mobile);
        }
        if ($real_num > $userEquities['num_balance'] && $user['reservation_service'] != 2) {
            return ['code' => '3007'];
        }
        // $Content = $this->dbc2Sbc($Content);
        $data                 = [];
        if (!empty($develop_no)) {
            $has_bind = Dbuser::getUserDevelopCode(['develop_no' => $develop_no, 'business_id' => 5, 'uid' => $user['id']], 'id,uid,business_id,source', true);
            if (empty($has_bind)) {
                return ['code' => '3011'];
            }
            $data['develop_no'] = $develop_no;
        }
        if ($user['pid'] == 137) {
            $develop_no_mes = Dbuser::getUserDevelopCode(['business_id' => 5, 'uid' => $user['id']], 'id,uid,business_id,source,develop_no', true);
            if (!empty($develop_no_mes)) {
                $data['develop_no'] = $develop_no_mes['develop_no'];
            }
        }
        $data['uid']          = $user['id'];
        $data['source']       = $ip;
        $data['task_content'] = $Content;

        $data['mobile_content'] = join(',', $Mobiles);
        $data['task_name']      = $task_name;
        $data['real_num']       = $real_num;
        $data['send_num']       = $send_num;
        $data['send_length']    = mb_strlen($Content);
        $data['free_trial']     = 1;
        $data['task_no']        = 'mar' . date('ymdHis') . substr(uniqid('', true), 15, 8);
        if (!empty($Dstime)) {
            $data['appointment_time'] = strtotime($Dstime);
        }
        if (!empty($msg_id)) {
            $data['send_msg_id'] = $msg_id;
        }
        if ($user['marketing_free_trial'] == 2) {
            $data['free_trial'] = 2;
            if (strpos($Content,'道信')) {
                $data['free_trial'] = 1;
            }
            if ($user['marketing_free_credit'] > 0) {
                if (count($effective_mobile) >= $user['marketing_free_credit']) {
                    $data['free_trial'] = 1;
                }
            }
        }
        /* if ($real_num > 100) {
            $data['free_trial'] = 1;
        } */
        if ($data['free_trial'] == 2) {
            // $data['free_trial'] = 2;
            $data['yidong_channel_id'] = 18;
            $data['liantong_channel_id'] = 19;
            $data['dianxin_channel_id'] = 19;
            if ($user['id'] == '185') {
                $data['yidong_channel_id'] = 107;
                $data['liantong_channel_id'] = 107;
                $data['dianxin_channel_id'] = 107;
            }
            if ($user['id'] == '187') {
                $data['yidong_channel_id'] = 107;
                $data['liantong_channel_id'] = 107;
                $data['dianxin_channel_id'] = 107;
            }
            if ($user['id'] == '206') {
                $data['yidong_channel_id'] = 107;
                $data['liantong_channel_id'] = 107;
                $data['dianxin_channel_id'] = 107;
            }
        }
        if (!empty($msg_id)) {
            $data['send_msg_id'] = $msg_id;
        }
        Db::startTrans();
        try {
            DbAdministrator::modifyBalance($userEquities['id'], $real_num, 'dec');
            $id = DbAdministrator::addUserSendTask($data);

            Db::commit();
            if (!empty($msg_id)) {
                return ['code' => '200', 'task_no' => $data['task_no'], 'msg_id' => $msg_id];
            }
            if ($data['free_trial'] == 2) {
                $res = $this->redis->rpush("index:meassage:marketing:sendtask", json_encode(['id' => $id, 'send_time' => 0, 'deduct' => $user['market_deduct']]));
            }
            return ['code' => '200', 'task_no' => $data['task_no']];
        } catch (\Exception $e) {
            // exception($e);
            Db::rollback();
            return ['code' => '3012'];
        }
        // $redisMessageMarketingSend = Config::get('rediskey.message.redisMessageMarketingSend');
        // foreach ($effective_mobile as $key => $value) {
        //     $this->redis->rpush($redisMessageMarketingSend.":2",$value.":".$id.":".$Content); //三体营销通道
        //     // $this->redis->hset($redisMessageMarketingSend.":2",$value,$id.":".$Content); //三体营销通道
        // }
        return ['code' => '200', 'task_no' => $data['task_no']];
    }

    public function getSmsBuiness($Username, $Password, $Content, $Mobiles, $ip, $signature_id = '', $develop_no = '', $msg_id = '')
    {
        // log::write("系统日志!",'error');
        // log::write("系统日志!",'info');
        $this->redis = Phpredis::getConn();
        // print_r($this->redis);
        // die;
        $Mobiles = array_unique(array_filter($Mobiles));
        $user    = DbUser::getUserOne(['appid' => $Username], 'id,pid,appkey,user_type,user_status,reservation_service,free_trial,pid,business_deduct,business_free_credit', true);
        if (empty($user)) {
            return ['code' => '3000'];
        }
        if ($user['user_status'] != 2) {
            return ['code' => '3004'];
        }
        if ($Password != $user['appkey']) {
            return ['code' => '3000'];
        }
        // $prefix = substr($Mobiles, 0, 7);
        // $res    = DbProvinces::getNumberSource(['mobile' => $prefix], 'source,province_id,province', true);

        // $redisMessageMarketingSend = Config::get('rediskey.message.redisMessageCodeSend');

        // print_r($res);die;
        $user_equities = DbAdministrator::getUserEquities(['uid' => $user['id'], 'business_id' => 6], 'id,num_balance', true);
        if (empty($user_equities)) {
            return ['code' => '3006'];
        }
        // if ($user_equities['num_balance'] < 1 && $user['reservation_service'] == 1) {
        //     return ['code' => '3006'];
        // }
        if (!empty($signature_id)) {
            // $user['id'] = 91;
            $signature =  DbSendMessage::getUserSignature(['uid' => $user['id'], 'signature_id' => $signature_id], '*', true);
            // echo Db::getLastSQL();
            // die;
            if (empty($signature)) {
                return ['code' => '3008'];
            }
            if ($signature['status'] != 2) {
                return ['code' => '3010'];
            }
            $Content = $signature['title'] . $Content;
        }

        $effective_mobile = [];

        foreach ($Mobiles as $key => $value) {
            if (count($Mobiles) > 1 && !in_array($user['id'], [47, 49, 52, 51, 55])) {
                if (checkMobile($value) != false) {
                    $effective_mobile[] = $value;
                }
            } else {
                if (checkMobile($value) != false) {
                    $effective_mobile[] = $value;
                }
            }
        }
        $send_num = count($effective_mobile);
        if (empty($effective_mobile)) {
            return ['code' => '3001'];
        }
        if (mb_strlen($Content) > 70) {
            $real_num = ceil(mb_strlen($Content) / 67) * count($effective_mobile);
        } else {
            $real_num = count($effective_mobile);
        }
        if ($real_num > $user_equities['num_balance'] && $user['reservation_service'] != 2) {
            return ['code' => '3007'];
        }
        // $Content = $this->dbc2Sbc($Content);
        $data                 = [];
        if (!empty($develop_no)) {
            $has_bind = Dbuser::getUserDevelopCode(['develop_no' => $develop_no, 'business_id' => 6, 'uid' => $user['id']], 'id,uid,business_id,source', true);
            if (empty($has_bind)) {
                return ['code' => '3011'];
            }
            $data['develop_no'] = $develop_no;
        }
        if ($user['pid'] == 137) {
            $develop_no_mes = Dbuser::getUserDevelopCode(['business_id' => 6, 'uid' => $user['id']], 'id,uid,business_id,source,develop_no', true);
            if (!empty($develop_no_mes)) {
                $data['develop_no'] = $develop_no_mes['develop_no'];
            }
        }
        $data['uid']          = $user['id'];
        $data['source']       = $ip;
        $data['task_content'] = $Content;

        $data['mobile_content'] = join(',', $effective_mobile);
        $data['task_name']      = $Content;
        $data['send_num']       = $send_num;
        $data['real_num']       = $real_num;
        $data['send_length']    = mb_strlen($Content);
        $data['free_trial']     = 1;
        $data['task_no']        = 'bus' . date('ymdHis') . substr(uniqid('', true), 15, 8);
        if ($user['free_trial'] == 2) {
            $data['free_trial'] = 2;
            if (strpos($Content,'道信')) {
                $data['free_trial'] = 1;
            }
            if ($user['business_free_credit'] > 0) {
                if ($real_num >= $user['business_free_credit']) {
                    $data['free_trial'] = 1;
                }
            }
        }
        if ($data['free_trial'] == 2) {
            // $data['free_trial'] = 2;
            if ($user['pid'] == 10) {
                $data['yidong_channel_id'] = 60;
                $data['liantong_channel_id'] = 62;
                $data['dianxin_channel_id'] = 61;
            } elseif ($user['id'] == 91) {
                $data['yidong_channel_id'] = 85;
                $data['liantong_channel_id'] = 85;
                $data['dianxin_channel_id'] = 85;
                // $data['yidong_channel_id'] = 9;
                // $data['liantong_channel_id'] = 9;
                // $data['dianxin_channel_id'] = 9;
            } elseif ($user['id'] == 110) { //快递
                $data['yidong_channel_id'] = 85;
                $data['liantong_channel_id'] = 85;
                $data['dianxin_channel_id'] = 85;
                // $data['yidong_channel_id'] = 9;
                // $data['liantong_channel_id'] = 9;
                // $data['dianxin_channel_id'] = 9;
            } elseif ($user['pid'] == 137) {
                if ($user['id'] == 133) {
                    $data['yidong_channel_id'] = 60;
                    $data['liantong_channel_id'] = 62;
                    $data['dianxin_channel_id'] = 61;
                } elseif ($user['id'] == 134) {
                    $data['yidong_channel_id'] = 85;
                    $data['liantong_channel_id'] = 85;
                    $data['dianxin_channel_id'] = 85;
                } else {
                    $data['yidong_channel_id'] = 85;
                    $data['liantong_channel_id'] = 85;
                    $data['dianxin_channel_id'] = 85;
                }
                if ($user['id'] == 187) {
                    $data['yidong_channel_id'] = 95;
                    $data['liantong_channel_id'] = 95;
                    $data['dianxin_channel_id'] = 95;
                }
                if ($user['id'] == 200) {
                    $data['yidong_channel_id'] = 95;
                    $data['liantong_channel_id'] = 95;
                    $data['dianxin_channel_id'] = 95;
                }
                if ($user['id'] == 217) {
                    $data['yidong_channel_id'] = 95;
                    $data['liantong_channel_id'] = 95;
                    $data['dianxin_channel_id'] = 95;
                }
                if ($user['id'] == 218) {
                    $data['yidong_channel_id'] = 95;
                    $data['liantong_channel_id'] = 95;
                    $data['dianxin_channel_id'] = 95;
                }
                // $data['yidong_channel_id'] = 9;
                // $data['liantong_channel_id'] = 9;
                // $data['dianxin_channel_id'] = 9;
            } else {
                if (strpos($Content, '亲爱的美田会员') !== false) {
                    $data['yidong_channel_id'] = 60;
                    $data['liantong_channel_id'] = 62;
                    $data['dianxin_channel_id'] = 61;
                } elseif (strpos($Content, '问卷') !== false) {
                    $data['yidong_channel_id'] = 60;
                    $data['liantong_channel_id'] = 62;
                    $data['dianxin_channel_id'] = 61;
                } else {
                    $data['yidong_channel_id'] = 85;
                    $data['liantong_channel_id'] = 85;
                    $data['dianxin_channel_id'] = 85;
                }
            }
            if ($user['id'] == 213) {
                $data['yidong_channel_id'] = 60;
                $data['liantong_channel_id'] = 62;
                $data['dianxin_channel_id'] = 61;
            }
        }

        if (!empty($msg_id)) {
            $data['send_msg_id'] = $msg_id;
        }
        Db::startTrans();
        try {
            DbAdministrator::modifyBalance($user_equities['id'], $real_num, 'dec');
            $bId = DbAdministrator::addUserSendCodeTask($data); //
            Db::commit();
            if ($data['free_trial'] == 2) {
                $res = $this->redis->rpush("index:meassage:business:sendtask", json_encode(['id' => $bId, 'deduct' => $user['business_deduct']]));
            }
            if (!empty($msg_id)) {
                return ['code' => '200', 'task_no' => $data['task_no'], 'msg_id' => $msg_id];
            }
            return ['code' => '200', 'task_no' => $data['task_no']];
        } catch (\Exception $e) {
            Db::rollback();
            exception($e);
            return ['code' => '3009'];
        }
    }

    public function readFileContent($filename)
    {
        $filename = filtraImage(Config::get('qiniu.exceldomain'), $filename);
        $logfile  = DbImage::getLogFile($filename); //判断时候有未完成的图片
        if (empty($logfile)) { //图片不存在
            return ['code' => '3002']; //图片没有上传过
        }
        $file = Config::get('qiniu.exceldomain') . '/' . $filename;
        ini_set('memory_limit', '3072M');
    }

    //回执接口
    public function marketingReceive($appid, $appkey, $page, $pagenum)
    {
        $user = DbUser::getUserOne(['appid' => $appid], 'id,appkey,user_type,user_status,reservation_service,free_trial', true);
        if (empty($user)) {
            return ['code' => '3000'];
        }
        if ($appkey != $user['appkey']) {
            return ['code' => '3000'];
        }
        $offset = ($page - 1) * $pagenum;
        $result = DbAdministrator::getUserSendTaskLog(['uid' => $user['id']], 'task_no,status_message,mobile,send_time', $row = false, '', $offset . ',' . $pagenum);
        foreach ($result as $key => $value) {
            $result[$key]['sendtime'] = date("Y-m-d H:i:s", $value['send_time']);
            unset($result[$key]['send_time']);
        }
        $total = DbAdministrator::countUserSendTaskLog(['uid' => $user['id']]);
        return ['code' => '200', 'data' => $result];
    }

    public function businessReceive($appid, $appkey, $page, $pagenum)
    {
        $user = DbUser::getUserOne(['appid' => $appid], 'id,appkey,user_type,user_status,reservation_service,free_trial', true);
        if (empty($user)) {
            return ['code' => '3000'];
        }
        if ($appkey != $user['appkey']) {
            return ['code' => '3000'];
        }
        // $offset = ($page - 1) * $pagenum;
        /* $result = DbAdministrator::getUserSendCodeTaskLog(['uid' => $user['id']], 'task_no,status_message,mobile,send_time', $row = false, '', $offset . ',' . $pagenum);
        foreach ($result as $key => $value) {
            $result[$key]['sendtime'] = date("Y-m-d H:i:s", $value['send_time']);
            unset($result[$key]['send_time']);
        }
        $total = DbAdministrator::countUserSendCodeTaskLog(['uid' => $user['id']]); */
        $result = [];
        $this->redis = Phpredis::getConn();
        $i = 0;
        while ($i <= 100) {
            $userstat = $this->redis->lpop('index:meassage:code:user:receive:' . $user['id']);
            $userstat = json_decode($userstat, true);
            if (empty($userstat)) {
                break;
            }
            $result[] = $userstat;
        }
        /* if ($user['id'] == 56) {
            $result = [
                [
                    'task_no' => 'bus19123116241454641192',
                    'status_message' => 'DELIVRD',
                    'mobile' => '13607258586',
                    'send_time'  => '2019-12-31 16:46:51',
                ],
                [
                    'task_no' => 'bus19123116241454641192',
                    'status_message' => 'DELIVRD',
                    'mobile' => '15827039444',
                    'send_time'  => '2019-12-31 16:46:51',
                ],
                [
                    'task_no' => 'bus19123117464942817710',
                    'status_message' => 'DELIVRD',
                    'mobile' => '18971710960',
                    'send_time'  => '2019-12-31 17:46:51',
                ],
                [
                    'task_no' => 'bus19123117531218006443',
                    'status_message' => 'DELIVRD',
                    'mobile' => '18971710960',
                    'send_time'  => '2019-12-31 17:53:15',
                ],
                [
                    'task_no' => 'bus20010313092729763059',
                    'status_message' => 'DELIVRD',
                    'mobile' => '18971710960',
                    'send_time'  => '2020-01-03 13:10:04',
                ],
                [
                    'task_no' => 'bus20010314542660675864',
                    'status_message' => 'DELIVRD',
                    'mobile' => '18971710960',
                    'send_time'  => '2020-01-03 14:55:04',
                ],
            ];
        } */
        return ['code' => '200', 'data' => $result];
    }

    public function balanceEnquiry($appid, $appkey)
    {
        $user = DbUser::getUserOne(['appid' => $appid], 'id,appkey,user_type,user_status,reservation_service,free_trial', true);
        if (empty($user)) {
            return ['code' => '3000'];
        }
        if ($appkey != $user['appkey']) {
            return ['code' => '3000'];
        }
        $user_equities = DbAdministrator::getUserEquities(['uid' => $user['id']], '*', false);
        if (empty($user_equities)) {
            return ['code' => '200', 'userEquities' => []];
        }
        foreach ($user_equities as $key => $equitise) {
            $user_equities[$key]['business_name'] = DbAdministrator::getBusiness(['id' => $equitise['business_id']], 'title', true)['title'];
            unset($user_equities[$key]['id']);
            unset($user_equities[$key]['uid']);
            unset($user_equities[$key]['business_id']);
            unset($user_equities[$key]['update_time']);
            unset($user_equities[$key]['create_time']);
            unset($user_equities[$key]['delete_time']);
            unset($user_equities[$key]['price']);
            unset($user_equities[$key]['agency_price']);
        }
        return ['code' => '200', 'userEquities' => $user_equities];
    }

    public function getMobilesDetail($appid, $appkey, $phone_data)
    {
        $user = DbUser::getUserOne(['appid' => $appid], 'id,appkey,user_type,user_status,reservation_service,free_trial', true);
        if (empty($user)) {
            return ['code' => '3000'];
        }
        if ($appkey != $user['appkey']) {
            return ['code' => '3000'];
        }
        $submit_num    = count($phone_data);
        $mobile_num    = 0; //移动
        $unicom_num    = 0; //联通
        $telecom_num   = 0; //电信
        $virtual_num   = 0; //虚拟
        $unknown_num   = 0; //未知
        $default_num = 0;
        $error_phone   = [];
        $mobile_phone  = [];
        $real_mobile  = [];
        $unicom_phone  = [];
        $telecom_phone = [];
        $virtual_phone = [];
        foreach ($phone_data as $key => $value) {
            if (checkMobile($value)) { //手机号码符合规则
                $mobile_Source = DbMobile::getNumberSource(['mobile' => substr($value, 0, 7)], 'source', true);
                if (isset($mobile_Source['source'])) {
                    if ($mobile_Source['source'] == 1) { //移动
                        $mobile_num++;
                        $mobile_phone[] = $value;
                    } elseif ($mobile_Source['source'] == 2) { //联通
                        $unicom_num++;
                        $unicom_phone[] = $value;
                    } elseif ($mobile_Source['source'] == 3) { //电信
                        $telecom_num++;
                        $telecom_phone[] = $value;
                    } elseif ($mobile_Source['source'] == 4) { //虚拟
                        $virtual_num++;
                        $virtual_phone[] = $value;
                    }
                } else {
                    $unknown_num++;
                }
                $real_mobile[] = $value;
            } else {
                $error_phone[] = $value;
            }
        }
        $real_mobile = array_unique(array_filter($real_mobile));
        $phone    = join(',', $real_mobile);
        $real_num = count($real_mobile);
        return ['code' => '200', 'submit_num' => $submit_num, 'real_num' => $real_num, 'default_num' => bcsub($submit_num, $real_num), 'mobile_num' => $mobile_num, 'unicom_num' => $unicom_num, 'telecom_num' => $telecom_num, 'virtual_num' => $virtual_num, 'unknown_num' => $unknown_num, 'mobile_phone' => $mobile_phone, 'unicom_phone' => $unicom_phone, 'telecom_phone' => $telecom_phone, 'virtual_phone' => $virtual_phone, 'phone' => $phone, 'error_phone' => $error_phone];
    }

    /**
     * 全角转半角
     * @param string $str
     * @return string
     **/
    // function sbc2Dbc($str){
    //     return preg_replace('/[\x{3000}\x{ff01}-\x{ff5f}]/ue', '($unicode=char2Unicode(\'\0\')) == 0x3000 ? " " : (($code=$unicode-0xfee0) > 256 ? unicode2Char($code) : chr($code))', $str);
    // }

    /**
     * 半角转全角
     * @param string $str
     * @return string
     **/
    // function dbc2Sbc($str){
    //     return preg_replace('/[\x{0020}\x{0020}-\x{7e}]/ue','($unicode=char2Unicode(\'\0\')) == 0x0020 ? unicode2Char（0x3000） : (($code=$unicode+0xfee0) > 256 ? unicode2Char($code) : chr($code))', $str);
    // }
    public function getSmsMultimediaMessageTask($appid, $appkey, $content_data, $mobile_content, $send_time, $ip, $title, $signature_id = '', $msg_id = '')
    {
        $this->redis = Phpredis::getConn();
        $user = DbUser::getUserOne(['appid' => $appid], 'id,appkey,user_type,user_status,reservation_service,free_trial,mul_free_trial,multimedia_deduct,multimeda_free_credit', true);
        if (empty($user)) {
            return ['code' => '3000'];
        }
        if ($appkey != $user['appkey']) {
            return ['code' => '3000'];
        }
        $user_equities = DbAdministrator::getUserEquities(['uid' => $user['id'], 'business_id' => 8], 'id,num_balance', true); //彩信
        if (empty($user_equities)) {
            return ['code' => '3005'];
        }
        if (!empty($signature_id)) {
            $signature =  DbSendMessage::getUserSignature(['uid' => $user['id'], 'signature_id' => $signature_id], '*', true);
            if (empty($signature)) {
                return ['code' => '3012'];
            }
            if ($signature['status'] != 2) {
                return ['code' => '3013'];
            }
        }
        $content_data             = array_filter($content_data);
        $multimedia_message_frame = [];
        $content_length           = 0;
        $max_length               = 102400; //最大字节长度
        $free_trial = 1;
            $yidong_channel_id = 0;
            $liantong_channel_id = 0;
            $dianxin_channel_id = 0;
            if ($user['mul_free_trial'] == 2) {
                $free_trial = 2;
            }
            if (strpos($title,'道信')) {
                $free_trial = 1;
            }
        foreach ($content_data as $key => $value) {
            $frame = [];
            if (empty($value['image_path']) && empty($value['content'])) {
                return ['code' => '3010'];
            }
            if (!isset($value['content'])) {
                $frame['content'] = '';
                // if (!empty($signature)) {
                //     $frame['content'] = '' . $signature['title'];
                // }
            } else {
                if (!empty($signature)) {
                    $frame['content'] = $signature['title'] . $value['content'];
                    unset($signature);
                } else {
                    $frame['content'] = $value['content'];
                }
                if (strpos($value['content'],'道信')) {
                    $free_trial = 1;
                }
                // $content_length+= strlen($value['content']);

            }

            $content_length += (strlen($frame['content']) / 8);
            if (!isset($value['image_path']) || empty($value['image_path'])) {
                $frame['image_path'] = '';
            } else {
                stream_context_set_default([
                    'ssl' => [
                        'verify_peer'      => false,
                        'verify_peer_name' => false,
                    ],
                ]);
                $head = get_headers($value['image_path'], 1);
                if ($head['Content-Type'] == 'image/jpeg') {
                    $frame['image_type'] = 'jpg';
                } elseif ($head['Content-Type'] == 'image/gif ') {
                    $frame['image_type'] = 'gif';
                }
                if (!isset($head['Content-Type']) || !in_array($head['Content-Type'], ['image/gif', 'image/jpeg', 'image/png'])) {
                    return ['code' => '3008'];
                }
                $filename = filtraImage(Config::get('qiniu.domain'), $value['image_path']);
                // print_r($value['image_path']);die;
                $logfile  = DbImage::getLogImageAll($filename); //判断时候有未完成的图片
                if (empty($logfile)) { //图片不存在
                    return ['code' => '3010']; //图片没有上传过
                }
                $content_length += $head['Content-Length'];
                $frame['image_path'] = $value['image_path'];
            }
            $frame['num'] = $value['num'];
            $frame['name'] = $value['name'];
            $multimedia_message_frame[] = $frame;
        }
        if ($content_length > $max_length) {
            return ['code' => '3009'];
        }
        $mobile_content = array_filter($mobile_content);
        $real_mobile    = [];
        foreach ($mobile_content as $key => $value) {
            if (checkMobile($value)) {
                $real_mobile[] = $value;
            }
        }
        $send_num = count($mobile_content);
        $real_num = count($real_mobile); //真实发送数量
        if ($send_num > $user_equities['num_balance'] && $user['reservation_service'] != 2) {
            return ['code' => '3001'];
        }
       /*  $channel_id = 0;
        $free_trial = 1;
 */

        $SmsMultimediaMessageTask = [];
        $SmsMultimediaMessageTask = [
            'task_no'        => 'mul' . date('ymdHis') . substr(uniqid('', true), 15, 8),
            'uid'            => $user['id'],
            'title'          => $title,
            'mobile_content' => join(',', $mobile_content),
            'source'         => $ip,
            'send_num'       => $send_num,
            'real_num'       => $real_num,
            'channel_id'     => $channel_id,
            // 'yidong_channel_id'     => $yidong_channel_id,
            // 'liantong_channel_id'     => $liantong_channel_id,
            // 'dianxin_channel_id'     => $dianxin_channel_id,
        ];
        if (!empty($send_time)) {
            $SmsMultimediaMessageTask['appointment_time'] = strtotime($send_time);
        }
            
        if ($free_trial == 2) {
            $yidong_channel_id = 59;
            $liantong_channel_id = 59;
            $dianxin_channel_id = 59;
            if ($user['id'] == 221) {
                if ($user['multimeda_free_credit'] > 0 && $real_num <= $user['multimeda_free_credit']) {
                    $free_trial = 2;
                    $yidong_channel_id = 108;
                    $liantong_channel_id = 108;
                    $dianxin_channel_id = 108;
                }else{
                    $free_trial = 1;
                    $yidong_channel_id = 0;
                    $liantong_channel_id = 0;
                    $dianxin_channel_id = 0;
                }
            }
            if ($user['id'] == 219) {
                if ($user['multimeda_free_credit'] > 0 && $real_num <= $user['multimeda_free_credit']) {
                    $free_trial = 2;
                    $yidong_channel_id = 109;
                    $liantong_channel_id = 109;
                    $dianxin_channel_id = 109;
                }else{
                    $free_trial = 1;
                    $yidong_channel_id = 0;
                    $liantong_channel_id = 0;
                    $dianxin_channel_id = 0;
                }
            }
            if ($user['id'] == 220) {
                if ($user['multimeda_free_credit'] > 0 && $real_num <= $user['multimeda_free_credit']) {
                    $free_trial = 2;
                    $yidong_channel_id = 110;
                    $liantong_channel_id = 110;
                    $dianxin_channel_id = 110;
                }else{
                    $free_trial = 1;
                    $yidong_channel_id = 0;
                    $liantong_channel_id = 0;
                    $dianxin_channel_id = 0;
                }
            }
            // $channel_id = 59;
        }
        $SmsMultimediaMessageTask['free_trial'] = $free_trial;
        $SmsMultimediaMessageTask['yidong_channel_id'] = $yidong_channel_id;
        $SmsMultimediaMessageTask['liantong_channel_id'] = $liantong_channel_id;
        $SmsMultimediaMessageTask['dianxin_channel_id'] = $dianxin_channel_id;
        if (!empty($msg_id)) {
            $SmsMultimediaMessageTask['send_msg_id'] = $msg_id;
        }

        Db::startTrans();
        try {
            DbAdministrator::modifyBalance($user_equities['id'], $send_num, 'dec');
            $bId = DbSendMessage::addUserMultimediaMessage($SmsMultimediaMessageTask); //添加后的商品id
            if ($bId) {
                foreach ($multimedia_message_frame as $key => $frame) {
                    $frame['multimedia_message_id'] = $bId;
                    $frame['image_path'] = filtraImage(Config::get('qiniu.domain'), $frame['image_path']);
                    DbSendMessage::addUserMultimediaMessageFrame($frame); //添加后的商品id
                }
            }
            Db::commit();
            if ($free_trial == 2) {
                $this->redis->rpush("index:meassage:multimediamessage:sendtask", json_encode(['id' => $bId, 'deduct' => $user['multimedia_deduct']]));
            }
            if (!empty($msg_id)) {
                return ['code' => '200', 'task_no' => $SmsMultimediaMessageTask['task_no'], 'msg_id' => $msg_id];
            }
            return ['code' => '200', 'task_no' => $SmsMultimediaMessageTask['task_no']];
        } catch (\Exception $e) {
            Db::rollback();
            // exception($e);
            return ['code' => '3011'];
        }
    }

    public function getSmsMultimediaMessageTaskLog($appid, $appkey, $page, $pageNum, $task_no, $mobile = '', $status = '')
    {
        $user = DbUser::getUserOne(['appid' => $appid], 'id,appkey,user_type,user_status,reservation_service,free_trial', true);
        if (empty($user)) {
            return ['code' => '3000'];
        }
        if ($appkey != $user['appkey']) {
            return ['code' => '3000'];
        }
        $offset = ($page - 1) * $pageNum;
        $where  = [];
        array_push($where, ['task_no', '=', $task_no]);
        if (!empty($mobile)) {
            array_push($where, ['mobile', '=', $mobile]);
        }
        if (!empty($status)) {
            array_push($where, ['status', '=', $status]);
        }
        $result = DbSendMessage::getUserMultimediaMessageLog($where, '*', false, '', $offset . ',' . $pageNum);
        $total = DbSendMessage::countUserMultimediaMessageLog($where);
        return ['code' => '200', 'total' => $total, 'data' => $result];
    }

    public function getSmsMultimediaMessageTaskStatus($appid, $appkey)
    {
        $user = DbUser::getUserOne(['appid' => $appid], 'id,appkey,user_type,user_status,reservation_service,free_trial', true);
        if (empty($user)) {
            return ['code' => '3000'];
        }
        if ($appkey != $user['appkey']) {
            return ['code' => '3000'];
        }
        $result = DbSendMessage::getUserMultimediaMessageLog([['uid', '=', $user['id']], ['user_query_status', '=', 1], ['status_message', '<>', '']], 'id,task_no,mobile,status_message,update_time', false, '', 200);
        $update_log = [];
        foreach ($result as $key => $value) {
            $update_value['id'] = $value['id'];
            $update_value['user_query_status'] = 2;
            $update_log[] = $update_value;
            unset($result[$key]['id']);
        }
        Db::startTrans();
        try {
            DbSendMessage::saveAllUserMultimediaMessageLog($update_log);
            Db::commit();
            return ['code' => '200', 'data' => $result];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3009'];
        }
    }

    public function textTemplateSignatureReport($appid, $appkey, $type, $title, $content)
    {
        $user = DbUser::getUserOne(['appid' => $appid], 'id,appkey,user_type,user_status,reservation_service,free_trial', true);
        if (empty($user)) {
            return ['code' => '3000'];
        }
        if ($appkey != $user['appkey']) {
            return ['code' => '3000'];
        }
        $user_equities = DbAdministrator::getUserEquities(['uid' => $user['id'], 'business_id' => $type], 'id', true);
        if (empty($user_equities)) {
            return ['code' => '3003'];
        }
        // $template_id = getRandomString(8);
        do {
            $template_id = getRandomString(8);
            $has = DbSendMessage::getUserModel(['template_id' => $template_id], 'id', true);
        } while ($has);
        $variable_len = substr_count($content, "{{var");
        $user_model = [];
        $user_model = [
            'uid' => $user['id'],
            'template_id' => $template_id,
            'business_id' => $type,
            'title' => $title,
            'content' => $content,
            'variable_len' => $variable_len,
            'status' => 1,
        ];
        Db::startTrans();
        try {
            DbSendMessage::addUserModel($user_model);
            Db::commit();
            return ['code' => '200', 'template_id' => $template_id];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3009'];
        }
    }

    public function submitBatchCustomBusiness($appid, $appkey, $template_id = '', $connect, $ip, $signature_id = '', $msg_id = '')
    {
        // $connect = str_replace('&amp;','&',$connect);

        $this->redis = Phpredis::getConn();
        $user = DbUser::getUserOne(['appid' => $appid], 'id,pid,appkey,user_type,user_status,reservation_service,free_trial,business_deduct', true);
        if (empty($user)) {
            return ['code' => '3000'];
        }
        // print_r($user);die;
        if ($appkey != $user['appkey']) {
            return ['code' => '3000'];
        }
        $user_equities = DbAdministrator::getUserEquities(['uid' => $user['id'], 'business_id' => 6], 'id,num_balance', true);
        if (empty($user_equities)) {
            return ['code' => '3002'];
        }
        if (!empty($template_id)) {
            if (!empty($template_id)) {
                $template =  DbSendMessage::getUserModel(['template_id' => $template_id, 'uid' => $user['id']], '*', true);
                if (empty($template) || $template['status'] != 3) {
                    return ['code' => '3003'];
                }
            }
        }
        if (!empty($signature_id)) {
            $signature =  DbSendMessage::getUserSignature(['uid' => $user['id'], 'signature_id' => $signature_id], '*', true);
            if (empty($signature)) {
                return ['code' => '3008'];
            }
            if ($signature['status'] != 2) {
                return ['code' => '3010'];
            }
        }
        $develop_no = '';
        if ($user['pid'] == 137) {
            $develop_no_mes = Dbuser::getUserDevelopCode(['business_id' => 6, 'uid' => $user['id']], 'id,uid,business_id,source,develop_no', true);
            if (!empty($develop_no_mes)) {
                $develop_no = $develop_no_mes['develop_no'];
            }
        }
        $connect_data = explode(';', $connect);
        $connect_data = array_filter($connect_data);
        $send_data = [];
        $send_data_mobile = [];
        $submit_num = count($connect_data);
        foreach ($connect_data as $key => $data) {
            $send_text = explode(':', $data);
            if (!empty($template)) {
                $replace_data = explode(',', $send_text[0]);
                $real_text = $template['content'];
                if (!empty($signature)) {
                    $real_text = $signature['title'] . $template['content'];
                }
                //有变量
                if ($template['variable_len'] > 0) {
                    if (empty($replace_data)) {
                        return ['code' => '3005']; //未获取到变量内容
                    }
                    for ($i = 1; $i <= $template['variable_len']; $i++) {
                        // $var_num = $i + 1;
                        // $real_text = str_replace("{{var" . $i . "}}", base64_decode($replace_data[$i - 1]), $real_text); //内容
                        $real_text = str_replace("{{var" . $i . "}}", urldecode($replace_data[$i - 1]), $real_text); //内容
                    }
                }
                if (checkMobile($send_text[1]) == false) {
                    continue;
                }
                if (in_array($real_text, $send_data)) {
                    $send_data_mobile[array_search($real_text, $send_data)][] = $send_text[1];
                } else {
                    $send_data[] = $real_text;
                    $send_data_mobile[array_search($real_text, $send_data)][] = $send_text[1];
                }
            } else {
                if (!empty($signature_id)) {
                    // $real_text = $signature['title'] .  base64_decode($send_text[0]);
                    $real_text = $signature['title'] .  urldecode($send_text[0]);
                } else {
                    // $real_text = base64_decode($send_text[0]);
                    $real_text = urldecode($send_text[0]);
                }
                if (checkMobile($send_text[1]) == false) {
                    continue;
                }
                if (in_array($real_text, $send_data)) {
                    $send_data_mobile[array_search($real_text, $send_data)][] = $send_text[1];
                } else {
                    $send_data[] = $real_text;
                    $send_data_mobile[array_search($real_text, $send_data)][] = $send_text[1];
                }
            }
        }
        if (empty($send_data_mobile)) {
            return ['code' => '3005'];
        }
        $free_taskno = [];
        $trial = []; //需审核
        //组合任务包
        $real_num = 0;

        $all_task_no = [];
        $task_no_mobile = [];
        foreach ($send_data as $key => $value) {
            if (empty($send_data_mobile[$key])) {
                continue;
            }
            $send_task = [];
            $task_no = 'bus' . date('ymdHis') . substr(uniqid('', true), 15, 8);
            $send_task = [
                'task_no' => $task_no,
                'uid'     => $user['id'],
                'task_content' => $value,
                'develop_no' => $develop_no,
                'mobile_content' => join(',', $send_data_mobile[$key]),
                'source'         => $ip,
                'send_length'       => mb_strlen($value),
                'send_num'       => count($send_data_mobile[$key]),
            ];
            if (!empty($msg_id)) {
                $send_task['send_msg_id'] = $msg_id;
            }
            if (mb_strlen($value) > 70) {
                $real_num += ceil(mb_strlen($value) / 67) * count($send_data_mobile[$key]);
                $send_task['real_num'] =  ceil(mb_strlen($value) / 67) * count($send_data_mobile[$key]);
            } else {
                $real_num += count($send_data_mobile[$key]);
                $send_task['real_num'] =  count($send_data_mobile[$key]);
            }
            if ($user['free_trial'] == 2) {
                $send_task['free_trial'] = 2;
            }else{
                $send_task['free_trial'] = 1;
            }
           
            if (count($send_data_mobile[$key]) > 30) {
                $send_task['free_trial'] = 1;
            }
            if ($send_task['free_trial'] == 2) {
                //短信内容分词
                $search_analyze = $this->search_analyze($value);
                $search_result = json_decode($search_analyze, true);
                $words = [];
                if ($search_result['code'] == 20000) {
                    $words = $search_result['data']['tokens'];
                }
                if (!empty($words)) { //敏感词
                    $analyze_value = DbSendMessage::getSensitiveWord([['word', 'IN', join(',', $words)]], 'id', false);
                    if (!empty($analyze_value)) {
                        // array_push($trial, $send_task);
                        $send_task['free_trial'] = 1;
                        $send_task['yidong_channel_id'] = 0;
                        $send_task['liantong_channel_id'] = 0;
                        $send_task['dianxin_channel_id'] = 0;
                    } else {
                        // array_push($task_no, $free_taskno);
                        $send_task['free_trial'] = 2;
                        if ($user['pid'] == 137 || $user['id'] == 110) {
                            $send_task['yidong_channel_id'] = 85;
                            $send_task['liantong_channel_id'] = 85;
                            $send_task['dianxin_channel_id'] = 85;
                        } elseif ($user['id'] == 134) {
                            $send_task['yidong_channel_id'] = 85;
                            $send_task['liantong_channel_id'] = 85;
                            $send_task['dianxin_channel_id'] = 85;
                        } else {
                            $send_task['yidong_channel_id'] = 85;
                            $send_task['liantong_channel_id'] = 85;
                            $send_task['dianxin_channel_id'] = 85;
                        }
                        if ($user['id'] == 187) {
                            $send_task['yidong_channel_id'] = 95;
                            $send_task['liantong_channel_id'] = 95;
                            $send_task['dianxin_channel_id'] = 95;
                        }
                        if ($user['id'] == 200) {
                            $send_task['yidong_channel_id'] = 95;
                            $send_task['liantong_channel_id'] = 95;
                            $send_task['dianxin_channel_id'] = 95;
                        }
                        if ($user['id'] == 217) {
                            $send_task['yidong_channel_id'] = 95;
                            $send_task['liantong_channel_id'] = 95;
                            $send_task['dianxin_channel_id'] = 95;
                        }
                        if ($user['id'] == 218) {
                            $send_task['yidong_channel_id'] = 95;
                            $send_task['liantong_channel_id'] = 95;
                            $send_task['dianxin_channel_id'] = 95;
                        }
                        $free_taskno[] = $task_no;
                        // array_push($free_trial, $send_task);
                    }
                } else {
                    if (!empty($value)) {
                        $free_taskno[] = $task_no;
                        $send_task['free_trial'] = 2;
                        if ($user['pid'] == 137) {

                            $send_task['yidong_channel_id'] = 85;
                            $send_task['liantong_channel_id'] = 85;
                            $send_task['dianxin_channel_id'] = 85;
                            if ($user['id'] == 187) {
                                $send_task['yidong_channel_id'] = 95;
                                $send_task['liantong_channel_id'] = 95;
                                $send_task['dianxin_channel_id'] = 95;
                            }
                            if ($user['id'] == 200) {
                                $send_task['yidong_channel_id'] = 95;
                                $send_task['liantong_channel_id'] = 95;
                                $send_task['dianxin_channel_id'] = 95;
                            }
                            if ($user['id'] == 217) {
                                $send_task['yidong_channel_id'] = 95;
                                $send_task['liantong_channel_id'] = 95;
                                $send_task['dianxin_channel_id'] = 95;
                            }
                            if ($user['id'] == 218) {
                                $send_task['yidong_channel_id'] = 95;
                                $send_task['liantong_channel_id'] = 95;
                                $send_task['dianxin_channel_id'] = 95;
                            }
                        } else {
                            if ($user['id'] == 110) {
                                $send_task['yidong_channel_id'] = 85;
                                $send_task['liantong_channel_id'] = 85;
                                $send_task['dianxin_channel_id'] = 85;
                            } else {
                                $send_task['yidong_channel_id'] = 85;
                                $send_task['liantong_channel_id'] = 85;
                                $send_task['dianxin_channel_id'] = 85;
                            }
                        }
                        // array_push($free_trial, $send_task);
                    }
                }
            } else {
                $send_task['free_trial'] = 1;
                $send_task['yidong_channel_id'] = 0;
                $send_task['liantong_channel_id'] = 0;
                $send_task['dianxin_channel_id'] = 0;
            }
            array_push($trial, $send_task);
            $all_task_no[] = $task_no;
            $task_no_mobile[$key] = $send_data_mobile[$key];
        }
        $task_as_mobile = [];
        foreach ($task_no_mobile as $key => $value) {
            $as_value = [];
            $as_value['task_no'] = $all_task_no[$key];
            $as_value['mobiles'] = $value;
            $task_as_mobile[] = $as_value;
        }
        // print_r($trial);
        // die;
        if ($real_num > $user_equities['num_balance'] && $user['reservation_service'] != 2) {
            return ['code' => '3004'];
        }
        Db::startTrans();
        try {
            // $save = DbAdministrator::saveUserSendCodeTask($trial);
            $free_ids = [];
            foreach ($trial as $key => $value) {
                # code...
                $id = DbAdministrator::addUserSendCodeTask($value);
                if ($value['free_trial'] == 2) {
                    // $res = $this->redis->rpush("index:meassage:business:sendtask", json_encode(['id' => $id, 'deduct' => $user['business_deduct']]));
                    $free_ids[] = $id;
                }
            }
            DbAdministrator::modifyBalance($user_equities['id'], $real_num, 'dec');
            Db::commit();
            if (!empty($free_ids)) {
                foreach ($free_ids as $key => $value) {
                    $res = $this->redis->rpush("index:meassage:business:sendtask", json_encode(['id' => $value, 'deduct' => $user['business_deduct']]));
                }
            }
            
           /*  if ($save) {
                DbAdministrator::modifyBalance($user_equities['id'], $real_num, 'dec');
                Db::commit();
                if (!empty($free_taskno)) {
                    //免审
                    $free_ids = DbAdministrator::getUserSendCodeTask([['task_no', 'IN', join(',', $free_taskno)]], 'id', false);
                    foreach ($free_ids as $key => $value) {
                        $res = $this->redis->rpush("index:meassage:business:sendtask", json_encode(['id' => $value['id'], 'deduct' => $user['business_deduct']]));
                    }
                }
            } */

            if (!empty($msg_id)) {
                return ['code' => '200', 'msg_id' => $msg_id, 'task_no' => $all_task_no, 'task_no_mobile' => $task_as_mobile];
            }
            return ['code' => '200', 'task_no' => $all_task_no, 'task_no_mobile' => $task_as_mobile];
        } catch (\Exception $e) {
            Db::rollback();
            // exception($e);
            return ['code' => '3009'];
        }
    }

    private function search_analyze($value)
    {
        $client_id = '10000001';
        $secret = 'VPNDYgDb7mTv2KuDTwWkAwRnDQtWj97E';
        $nonce = getRandomString(8);
        $time = time();

        $sign = md5('{"client_id":' . $client_id . ',"nonce":"' . $nonce . '","secret":"VPNDYgDb7mTv2KuDTwWkAwRnDQtWj97E","timestamp":' . $time . '}');
        $jy_token = base64_encode('{"client_id":' . $client_id . ',"nonce":"' . $nonce . '","sign":"' . $sign . '","timestamp":' . $time . '}');
        $request_url = 'https://api-sit.itingluo.com/apiv1/openapi/search/analyze?text=' . $value;
        $header  = array(
            'client_id:' . $client_id,
            'secret:' . $secret,
            'nonce:' . $nonce,
            'timestamp:' . $time,
            'jy-token:' . $jy_token,
            'Content-Type:' . 'application/x-www-form-urlencoded; charset=UTF-8'
        );
        return $this->http_request($request_url, '', $header);
    }

    private function http_request($url, $data = null, $header = null)
    {

        $curl = curl_init();
        if (!empty($header)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
            curl_setopt($curl, CURLOPT_HEADER, 0); //返回response头部信息
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_HTTPGET, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

    public function submitBatchCustomMarketing($appid, $appkey, $template_id = '', $connect, $ip, $signature_id = '', $msg_id = '')
    {
        $this->redis = Phpredis::getConn();
        $user = DbUser::getUserOne(['appid' => $appid], 'id,pid,appkey,user_type,user_status,reservation_service,marketing_free_trial,market_deduct', true);
        if (empty($user)) {
            return ['code' => '3000'];
        }
        if ($appkey != $user['appkey']) {
            return ['code' => '3000'];
        }
        $user_equities = DbAdministrator::getUserEquities(['uid' => $user['id'], 'business_id' => 5], 'id,num_balance', true);
        if (empty($user_equities)) {
            return ['code' => '3002'];
        }
        if (!empty($template_id)) {
            $template =  DbSendMessage::getUserModel(['template_id' => $template_id, 'uid' => $user['id']], '*', true);
            if (empty($template) || $template['status'] != 3) {
                return ['code' => '3003'];
            }
        }
        if (!empty($signature_id)) {
            $signature =  DbSendMessage::getUserSignature(['uid' => $user['id'], 'signature_id' => $signature_id], '*', true);

            if (empty($signature)) {
                return ['code' => '3008'];
            }
            if ($signature['status'] != 2) {
                return ['code' => '3010'];
            }
        }
        $develop_no  = '';
        if ($user['pid'] == 137) {
            $develop_no_mes = Dbuser::getUserDevelopCode(['business_id' => 5, 'uid' => $user['id']], 'id,uid,business_id,source,develop_no', true);
            if (!empty($develop_no_mes)) {
                $develop_no = $develop_no_mes['develop_no'];
            }
        }
        $connect_data = explode(';', $connect);
        $connect_data = array_filter($connect_data);
        $send_data = [];
        $send_data_mobile = [];
        foreach ($connect_data as $key => $data) {
            $send_text = explode(':', $data);
            if (!empty($template)) {
                $replace_data = explode(',', $send_text[0]);

                $real_text = $template['content'];
                if (!empty($signature)) {
                    $real_text = $signature['title'] . $template['content'];
                }
                //有变量
                if ($template['variable_len'] > 0) {
                    if (empty($replace_data)) {
                        return ['code' => '3005']; //未获取到变量内容
                    }
                    for ($i = 1; $i <= $template['variable_len']; $i++) {

                        // $var_num = $i + 1;
                        // $real_text = str_replace("{{var" . $i . "}}", base64_decode($replace_data[$i - 1]), $real_text); //内容
                        $real_text = str_replace("{{var" . $i . "}}", urldecode($replace_data[$i - 1]), $real_text); //内容
                    }
                }
                if (checkMobile($send_text[1]) == false) {
                    continue;
                }
                if (in_array($real_text, $send_data)) {
                    $send_data_mobile[array_search($real_text, $send_data)][] = $send_text[1];
                } else {
                    $send_data[] = $real_text;
                    $send_data_mobile[array_search($real_text, $send_data)][] = $send_text[1];
                }
            } else {
                if (checkMobile($send_text[1]) == false) {
                    continue;
                }
                if (!empty($signature_id)) {
                    // $real_text = $signature['title'] .  base64_decode($send_text[0]);
                    $real_text = $signature['title'] .  urldecode($send_text[0]);
                } else {
                    // $real_text = base64_decode($send_text[0]);
                    $real_text = urldecode($send_text[0]);
                }
                if (in_array($real_text, $send_data)) {
                    $send_data_mobile[array_search($real_text, $send_data)][] = $send_text[1];
                } else {
                    $send_data[] = $real_text;
                    $send_data_mobile[array_search($real_text, $send_data)][] = $send_text[1];
                }
            }
        }
        // print_r($send_data_mobile);die;
        $free_taskno = [];
        $trial = []; //需审核
        //组合任务包
        $real_num = 0;

        $all_task_no = [];
        $task_no_mobile = [];
        if (empty($send_data_mobile)) {
            return ['code' => '3005'];
        }
        foreach ($send_data as $key => $value) {
            $send_task = [];
            $task_no = 'mar' . date('ymdHis') . substr(uniqid('', true), 15, 8);
            if (empty($send_data_mobile[$key])) {
                continue;
            }
            $send_task = [
                'task_no' => $task_no,
                'uid'     => $user['id'],
                'task_content' => $value,
                'develop_no' => $develop_no,
                'mobile_content' => join(',', $send_data_mobile[$key]),
                'source'         => $ip,
                'send_length'       => mb_strlen($value),
                'send_num'       => count($send_data_mobile[$key]),
            ];
            if (!empty($msg_id)) {
                $send_task['send_msg_id'] = $msg_id;
            }
            if (mb_strlen($value) > 70) {
                $real_num += ceil(mb_strlen($value) / 67) * count($send_data_mobile[$key]);
                $send_task['real_num'] =  ceil(mb_strlen($value) / 67) * count($send_data_mobile[$key]);
            } else {
                $real_num += count($send_data_mobile[$key]);
                $send_task['real_num'] =  count($send_data_mobile[$key]);
            }
            // echo count($send_data_mobile[$key]);die;
            if ($user['marketing_free_trial'] == 2) {
                $send_task['free_trial'] = 2;
            }else{
                $send_task['free_trial'] = 1;
            }
            if (count($send_data_mobile[$key]) > 30) {
                // $user['marketing_free_trial'] = 1;
                $send_task['free_trial'] = 1;
            }
            // $send_task['free_trial'] = 1;
            if ($send_task['free_trial'] == 2) {
                //短信内容分词
                $search_analyze = $this->search_analyze($value);
                $search_result = json_decode($search_analyze, true);
                $words = [];
                if ($search_result['code'] == 20000) {
                    $words = $search_result['data']['tokens'];
                }
                if (!empty($words)) { //敏感词
                    $analyze_value = DbSendMessage::getSensitiveWord([['word', 'IN', join(',', $words)]], 'id', false);
                    if (!empty($analyze_value)) {
                        // array_push($trial, $send_task);
                        $send_task['free_trial'] = 1;
                        $send_task['yidong_channel_id'] = 0;
                        $send_task['liantong_channel_id'] = 0;
                        $send_task['dianxin_channel_id'] = 0;
                    } else {
                        // array_push($task_no, $free_taskno);
                        $send_task['free_trial'] = 2;
                        if ($user['id'] == 133) {
                            $send_task['yidong_channel_id'] = 73;
                            $send_task['liantong_channel_id'] = 75;
                            $send_task['dianxin_channel_id'] = 76;
                        } elseif ($user['id'] == 185) {
                            $send_task['yidong_channel_id'] = 107;
                            $send_task['liantong_channel_id'] = 107;
                            $send_task['dianxin_channel_id'] = 107;
                        } elseif ($user['id'] == 187) {
                            $send_task['yidong_channel_id'] = 107;
                            $send_task['liantong_channel_id'] = 107;
                            $send_task['dianxin_channel_id'] = 107;
                        } elseif ($user['id'] == 206) {
                            $send_task['yidong_channel_id'] = 107;
                            $send_task['liantong_channel_id'] = 107;
                            $send_task['dianxin_channel_id'] = 107;
                        } else {
                            $send_task['yidong_channel_id'] = 18;
                            $send_task['liantong_channel_id'] = 19;
                            $send_task['dianxin_channel_id'] = 19;
                        }
                        $free_taskno[] = $task_no;
                        // array_push($free_trial, $send_task);
                    }
                } else {
                    if (!empty($value)) {
                        $free_taskno[] = $task_no;
                        $send_task['free_trial'] = 2;
                        if ($user['id'] == 133) {
                            $send_task['yidong_channel_id'] = 73;
                            $send_task['liantong_channel_id'] = 75;
                            $send_task['dianxin_channel_id'] = 76;
                        } else {
                            $send_task['yidong_channel_id'] = 18;
                            $send_task['liantong_channel_id'] = 19;
                            $send_task['dianxin_channel_id'] = 19;
                        }
                        if ($user['id'] == 185) {
                            $send_task['yidong_channel_id'] = 107;
                            $send_task['liantong_channel_id'] = 107;
                            $send_task['dianxin_channel_id'] = 107;
                        }
                        if ($user['id'] == 187) {
                            $send_task['yidong_channel_id'] = 107;
                            $send_task['liantong_channel_id'] = 107;
                            $send_task['dianxin_channel_id'] = 107;
                        }
                        if ($user['id'] == 206) {
                            $send_task['yidong_channel_id'] = 107;
                            $send_task['liantong_channel_id'] = 107;
                            $send_task['dianxin_channel_id'] = 107;
                        }
                        // array_push($free_trial, $send_task);
                    }
                }
            } else {
                $send_task['free_trial'] = 1;
                $send_task['yidong_channel_id'] = 0;
                $send_task['liantong_channel_id'] = 0;
                $send_task['dianxin_channel_id'] = 0;
            }
            array_push($trial, $send_task);
            $all_task_no[] = $task_no;
            $task_no_mobile[$key] = $send_data_mobile[$key];
        }
        $task_as_mobile = [];
        foreach ($task_no_mobile as $key => $value) {
            $as_value = [];
            $as_value['task_no'] = $all_task_no[$key];
            $as_value['mobiles'] = $value;
            $task_as_mobile[] = $as_value;
        }
        if ($real_num > $user_equities['num_balance'] && $user['reservation_service'] != 2) {
            return ['code' => '3004'];
        }
        // print_r($free_taskno);die;
        Db::startTrans();
        try {
            /* $save = DbAdministrator::saveUserSendTask($trial);
            if ($save) {
                DbAdministrator::modifyBalance($user_equities['id'], $real_num, 'dec');
                Db::commit();
                if (!empty($free_taskno)) {
                    //免审
                    $free_ids = DbAdministrator::getUserSendTask([['task_no', 'IN', join(',', $free_taskno)]], 'id', false);
                    foreach ($free_ids as $key => $value) {
                        $res = $this->redis->rpush("index:meassage:marketing:sendtask", json_encode(['id' => strval($value['id']), 'send_time' => 0, 'deduct' => $user['market_deduct']]));
                    }
                    // echo Db::getLastSQL();die;
                }
            } */
            $free_ids = [];
            foreach ($trial as $key => $value) {
                $id = DbAdministrator::addUserSendTask($value);
                if ($value['free_trial'] == 2) {
                    $free_ids[] = $id;
                }
            }
            DbAdministrator::modifyBalance($user_equities['id'], $real_num, 'dec');
            Db::commit();
            if (!empty($free_ids)) {
                foreach ($free_ids as $key => $value) {
                    $this->redis->rpush("index:meassage:marketing:sendtask", json_encode(['id' => strval($value), 'send_time' => 0, 'deduct' => $user['market_deduct']]));
                }
            }
            if (!empty($msg_id)) {
                return ['code' => '200', 'msg_id' => $msg_id, 'task_no' => $all_task_no, 'task_no_mobile' => $task_as_mobile];
            }
            return ['code' => '200', 'task_no' => $all_task_no, 'task_no_mobile' => $task_as_mobile];
        } catch (\Exception $e) {
            Db::rollback();
            // exception($e);
            return ['code' => '3009'];
        }
    }

    public function  SignatureReport($appid, $appkey, $type, $title)
    {
        $user = DbUser::getUserOne(['appid' => $appid], 'id,appkey,user_type,user_status,reservation_service,free_trial', true);
        if (empty($user)) {
            return ['code' => '3000'];
        }
        if ($appkey != $user['appkey']) {
            return ['code' => '3000'];
        }
        $user_equities = DbAdministrator::getUserEquities(['uid' => $user['id'], 'business_id' => $type], 'id', true);
        if (empty($user_equities)) {
            return ['code' => '3003'];
        }
        $signature_id = getRandomString(8);
        do {
            $signature_id = getRandomString(8);
            $has = DbSendMessage::getUserSignature(['signature_id' => $signature_id], 'id', true);
        } while ($has);

        $user_model = [];
        $user_model = [
            'uid' => $user['id'],
            'signature_id' => $signature_id,
            'business_id' => $type,
            'title' => $title,
            'status' => 1,
        ];
        Db::startTrans();
        try {
            DbSendMessage::addUserSignature($user_model);
            Db::commit();
            return ['code' => '200', 'signature_id' => $signature_id];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3009'];
        }
    }

    public function multimediaTemplateSignatureReport($appid, $appkey, $content_data, $title, $name)
    {
        $user = DbUser::getUserOne(['appid' => $appid], 'id,appkey,user_type,user_status,reservation_service,free_trial', true);
        if (empty($user)) {
            return ['code' => '3000'];
        }
        if ($appkey != $user['appkey']) {
            return ['code' => '3000'];
        }
        $content_data             = array_filter($content_data);
        $multimedia_message_frame = [];
        $content_length           = 0;
        $max_length               = 102400; //最大字节长度

        foreach ($content_data as $key => $value) {
            $frame = [];
            if (!isset($value['content'])) {
                $frame['content'] = '';
            } else {
                $frame['content'] = $value['content'];
                $frame['variable_len'] = substr_count($value['content'], "{{var");

                // $content_length+= strlen($value['content']);
            }
            $content_length += (strlen($frame['content']) / 8);
            if (!isset($value['image_path']) || empty($value['image_path'])) {
                $frame['image_path'] = '';
            } else {
                stream_context_set_default([
                    'ssl' => [
                        'verify_peer'      => false,
                        'verify_peer_name' => false,
                    ],
                ]);
                $head = get_headers($value['image_path'], 1);
                if ($head['Content-Type'] == 'image/jpeg') {
                    $frame['image_type'] = 'jpg';
                } elseif ($head['Content-Type'] == 'image/gif ') {
                    $frame['image_type'] = 'gif';
                }
                if (!isset($head['Content-Type']) || !in_array($head['Content-Type'], ['image/gif', 'image/jpeg', 'image/png'])) {
                    return ['code' => '3004'];
                }
                $filename = filtraImage(Config::get('qiniu.domain'), $value['image_path']);
                // print_r($value['image_path']);die;
                $logfile  = DbImage::getLogImageAll($filename); //判断时候有未完成的图片
                if (empty($logfile)) { //图片不存在
                    return ['code' => '3005']; //图片没有上传过
                }
                $content_length += $head['Content-Length'];
                $frame['image_path'] = $value['image_path'];
            }
            $frame['name'] = $value['name'];
            $frame['num'] = $value['num'];
            $multimedia_message_frame[] = $frame;
        }
        if ($content_length > $max_length) {
            return ['code' => '3006'];
        }
        do {
            $template_id = getRandomString(8);
            $has = DbSendMessage::getUserMultimediaTemplate(['template_id' => $template_id], 'id', true);
        } while ($has);
        $SmsMultimediaMessageTask = [];
        $SmsMultimediaMessageTask = [
            'template_id'        => $template_id,
            'uid'            => $user['id'],
            'title'          => $title,
            'name'          => $name,
        ];

        Db::startTrans();
        try {
            $bId = DbSendMessage::addUserMultimediaTemplate($SmsMultimediaMessageTask); //添加后的商品id
            if ($bId) {
                foreach ($multimedia_message_frame as $key => $frame) {
                    $frame['multimedia_template_id'] = $bId;
                    $frame['image_path'] = filtraImage(Config::get('qiniu.domain'), $frame['image_path']);
                    DbSendMessage::addUserMultimediaTemplateFrame($frame); //添加后的商品id
                }
            }
            Db::commit();
            return ['code' => '200', 'template_id' => $template_id];
        } catch (\Exception $e) {
            Db::rollback();
            // exception($e);
            return ['code' => '3007'];
        }
    }

    public function submitBatchCustomMultimediaMessage($appid, $appkey, $template_id, $connect, $ip, $msg_id = '', $signature_id = '')
    {
        $user = DbUser::getUserOne(['appid' => $appid], 'id,appkey,user_type,user_status,reservation_service,mul_free_trial,multimedia_deduct,multimeda_free_credit', true);
        if (empty($user)) {
            return ['code' => '3000'];
        }
        if ($appkey != $user['appkey']) {
            return ['code' => '3000'];
        }
        $user_equities = DbAdministrator::getUserEquities(['uid' => $user['id'], 'business_id' => 8], 'id,num_balance', true);
        if (empty($user_equities)) {
            return ['code' => '3002'];
        }
        $template =  DbSendMessage::getUserMultimediaTemplate(['template_id' => $template_id], '*', true);
        /* if (empty($template) || $template['status'] != 2) {
            return ['code' => '3003'];
        } */
        if (empty($template)) {
            return ['code' => '3003'];
        }
        $template['multimedia_frame'] = DbSendMessage::getUserMultimediaTemplateFrame(['multimedia_template_id' => $template['id']], 'num,name,content,image_path,image_type,variable_len', false, ['num' => 'asc']);
        if (!empty($signature_id)) {
            $signature =  DbSendMessage::getUserSignature(['uid' => $user['uid'], 'signature_id' => $signature_id], '*', true);
            if (empty($signature)) {
                return ['code' => '3008'];
            }
            if ($signature['status'] != 2) {
                return ['code' => '3010'];
            }
        }
        // print_r($connect);die;
        // $connect_data = explode(';', $connect);
        // $connect_data = array_filter($connect_data);
        // $send_data = [];
        $send_data_mobile = [];

        
        //有模板目前只支持有模板进行提交
        $MMS_data = [];
        $send_num = 0;
        // 变量,变量:手机号;变量,变量:手机号;变量:手机号;
        foreach ($connect as $key => $data) {

            // $send_text = explode(':', $data);
            if (empty($data['mobile'])) {
                continue;
            }
            if (checkMobile($data['mobile']) == false || strlen($data['mobile']) != 11) {
                continue;
            }
            $son_MMS_data = [];
            $son_MMS_data = [
                'title' => $template['title']
            ];
            
            $send_num++;
            // $the_frame = explode(',', $send_text[0]);
            foreach ($template['multimedia_frame'] as $mf => $mula) {
                /*  for ($i = 0; $i < count($the_frame); $i++) {
                        $var_num = $i + 1;
                        $mula['content'] = str_replace("{{var" . $var_num . "}}", $the_frame[$i], $mula['content']); //内容
                    } */
                if (!empty($data['{{var1}}'])) {
                    $mula['content'] = str_replace("{{var1}}", $data['{{var1}}'], $mula['content']); //内容
                }
                if (!empty($data['{{var2}}'])) {
                    $mula['content'] = str_replace("{{var2}}", $data['{{var2}}'], $mula['content']); //内2
                }
                if (!empty($data['{{var3}}'])) {
                    $mula['content'] = str_replace("{{var3}}", $data['{{var3}}'], $mula['content']); //内2
                }
                if (!empty($data['{{var4}}'])) {
                    $mula['content'] = str_replace("{{var4}}", $data['{{var4}}'], $mula['content']); //内2
                }
                if (!empty($data['{{var5}}'])) {
                    $mula['content'] = str_replace("{{var5}}", $data['{{var5}}'], $mula['content']); //内2
                }
                if (!empty($data['{{var6}}'])) {
                    $mula['content'] = str_replace("{{var6}}", $data['{{var6}}'], $mula['content']); //内2
                }
                if (!empty($data['{{var7}}'])) {
                    $mula['content'] = str_replace("{{var7}}", $data['{{var7}}'], $mula['content']); //内2
                }
                if (!empty($data['{{var8}}'])) {
                    $mula['content'] = str_replace("{{var8}}", $data['{{var8}}'], $mula['content']); //内2
                }
                if (!empty($data['{{var9}}'])) {
                    $mula['content'] = str_replace("{{var9}}", $data['{{var9}}'], $mula['content']); //内2
                }
                if (!empty($data['{{var10}}'])) {
                    $mula['content'] = str_replace("{{var10}}", $data['{{var10}}'], $mula['content']); //内2
                }
                $the_mula['content'] = $mula['content'];
                $the_mula['num'] = $mula['num'];
                $the_mula['name'] = $mula['name'];
                $the_mula['image_path'] = $mula['image_path'];
                $the_mula['image_type'] = $mula['image_type'];
                $son_MMS_data['multimedia_frame'][] = $the_mula;
            }
            
           
           /*  $the_mula['content'] = $mula['content'];
            $the_mula['num'] = $mula['num'];
            $the_mula['name'] = $mula['name'];
            $the_mula['image_path'] = $mula['image_path'];
            $the_mula['image_type'] = $mula['image_type'];
            $son_MMS_data['multimedia_frame'][] = $the_mula; */

            /* if (in_array($son_MMS_data, $MMS_data)) {
                $send_data_mobile[array_search($son_MMS_data, $MMS_data)] = $data;
            } else {
                $MMS_data[] = $son_MMS_data;
                $send_data_mobile[array_search($son_MMS_data, $MMS_data)] = $data;
            } */
            if (in_array($son_MMS_data, $MMS_data)) {
                $send_data_mobile[] = $data;
            } else {
                $MMS_data[] = $son_MMS_data;
                $send_data_mobile[] = $data;
            }
        }
        // print_r($send_data_mobile);die;
        // echo $send_num;die;
        $free_taskno = [];
        $trial = []; //需审核
        //组合任务包
        $real_num = 0;
        // $max_length               = 81920; //最大字节长度80Kb
        $max_length               = 92160; //最大字节长度80Kb
        $all_task_no = [];
        $task_no_mobile = [];
        $beyond = [];
        foreach ($MMS_data as $key => $value) {
            $content_length = 0;
            foreach ($value['multimedia_frame'] as $ke => $mf) {
                $frame = [];
                if (!isset($mf['content'])) {
                    $frame['content'] = '';
                } else {
                    $frame['content'] = $mf['content'];
                    // $content_length+= strlen($value['content']);
                }
                $content_length += (strlen($frame['content']) / 8);
                if (!empty($mf['image_path'])) {
                    stream_context_set_default([
                        'ssl' => [
                            'verify_peer'      => false,
                            'verify_peer_name' => false,
                        ],
                    ]);

                    $head = get_headers($mf['image_path'], 1);
                    $content_length += $head['Content-Length'];
                } else {
                    $frame['image_path'] = '';
                }
            }
            $send_task['multimedia_frame'] = $value['multimedia_frame'];
            // $content_length  = 102402;
            // print_r($content_length);die;
            if ($content_length <= $max_length) {
            } else {
                $beyond[] = $send_data_mobile[$key];
                unset($send_data_mobile[$key]);
            }
        }
        /* 开放免审需检测是否在免审默认通道中报备过模板，否则强制审核 */
        if (!empty($send_data_mobile)) {
            $task_as_mobile = [];
            foreach ($task_no_mobile as $key => $value) {
                $as_value = [];
                $as_value['task_no'] = $all_task_no[$key];
                $as_value['mobiles'] = $value;
                $task_as_mobile[] = $as_value;
            }
    
            $real_num =$send_num;
            if ($real_num > $user_equities['num_balance'] && $user['reservation_service'] != 2) {
                return ['code' => '3004'];
            }
            // print_r($send_data_mobile);die;
            $send_task = [];
            $task_no = 'mul' . date('ymdHis') . substr(uniqid('', true), 15, 8);
            $send_task = [
                'task_no' => $task_no,
                'template_id' => $template_id,
                'uid'     => $user['id'],
                'title' => $value['title'],
                'submit_content' => json_encode($send_data_mobile),
                'source'         => $ip,
                'send_num'       => $send_num,
                'real_num'       => $real_num,
            ];
            if (!empty($msg_id)) {
                $send_task['send_msg_id'] = $msg_id;
            }
            // print_r($template['multimedia_frame']);die;
            $free_trial = 1;
            $yidong_channel_id = 0;
            $liantong_channel_id = 0;
            $dianxin_channel_id = 0;
            if  ($user['mul_free_trial'] == 2){
                $free_trial = 2;
            }
            $third_template = DbAdministrator::getUserMultimediaTemplateThirdReport(['channel_id'=> 103,'template_id' => $template_id],'id',true);
            if (empty($third_template)) {
                $free_trial = 1;
            }
            if ($free_trial == 2) {
                // $free_trial = 2;
                $yidong_channel_id = 103;
                $liantong_channel_id = 103;
                $dianxin_channel_id = 103;
                if ($user['id'] == 221) {
                    if ($user['multimeda_free_credit'] > 0 && $real_num <= $user['multimeda_free_credit']) {
                        $free_trial = 2;
                        $yidong_channel_id = 108;
                        $liantong_channel_id = 108;
                        $dianxin_channel_id = 108;
                    }else{
                        $free_trial = 1;
                        $yidong_channel_id = 0;
                        $liantong_channel_id = 0;
                        $dianxin_channel_id = 0;
                    }
                }
                if ($user['id'] == 219) {
                    if ($user['multimeda_free_credit'] > 0 && $real_num <= $user['multimeda_free_credit']) {
                        $free_trial = 2;
                        $yidong_channel_id = 109;
                        $liantong_channel_id = 109;
                        $dianxin_channel_id = 109;
                    }else{
                        $free_trial = 1;
                        $yidong_channel_id = 0;
                        $liantong_channel_id = 0;
                        $dianxin_channel_id = 0;
                    }
                }
                if ($user['id'] == 220) {
                    if ($user['multimeda_free_credit'] > 0 && $real_num <= $user['multimeda_free_credit']) {
                        $free_trial = 2;
                        $yidong_channel_id = 110;
                        $liantong_channel_id = 110;
                        $dianxin_channel_id = 110;
                    }else{
                        $free_trial = 1;
                        $yidong_channel_id = 0;
                        $liantong_channel_id = 0;
                        $dianxin_channel_id = 0;
                    }
                }
            }
            // $send_task['free_trial'] = 1;
            $send_task['free_trial'] = $free_trial;
            $send_task['yidong_channel_id'] = $yidong_channel_id;
            $send_task['liantong_channel_id'] = $liantong_channel_id;
            $send_task['dianxin_channel_id'] = $dianxin_channel_id;
        }else{
            return ["code" => '3005', 'beyond' =>  $beyond,'msg' => '本次提交有效彩信失败'];
        }
       
        // print_r($send_task);die;
        Db::startTrans();
        try {
            DbAdministrator::modifyBalance($user_equities['id'], $real_num, 'dec');
            $bId = DbSendMessage::addUserMultimediaMessage($send_task); //添加后的商品id
            if ($bId) {
                foreach ($template['multimedia_frame'] as $key => $frame) {
                    $frame['multimedia_message_id'] = $bId;
                    $frame['image_path'] = filtraImage(Config::get('qiniu.domain'), $frame['image_path']);
                    DbSendMessage::addUserMultimediaMessageFrame($frame); //添加后的商品id
                }
            }
            /* foreach ($trial as $tr => $tal) {
                $son = [];
                $son = $tal['multimedia_frame'];
                unset($tal['multimedia_frame']);
                // print_r($tal);
               
                if ($bId) {
                    foreach ($son as $key => $frame) {
                        $frame['multimedia_message_id'] = $bId;
                        $frame['image_path'] = filtraImage(Config::get('qiniu.domain'), $frame['image_path']);
                        DbSendMessage::addUserMultimediaMessageFrame($frame); //添加后的商品id
                    }
                }
            }
            */
            Db::commit();
           
            if (!empty($msg_id)) {
                return ['code' => '200', 'task_no' => $task_no, 'msg_id' => $msg_id, 'beyond' =>  $beyond];
            }
            return ['code' => '200', 'task_no' => $task_no, 'beyond' => $beyond];
        } catch (\Exception $e) {
            Db::rollback();
            exception($e);
            return ['code' => '3009'];
        }
    }

    public function multimediaReceive($appid, $appkey)
    {
        $user = DbUser::getUserOne(['appid' => $appid], 'id,appkey,user_type,user_status,reservation_service,free_trial', true);
        if (empty($user)) {
            return ['code' => '3000'];
        }
        if ($appkey != $user['appkey']) {
            return ['code' => '3000'];
        }
        // $offset = ($page - 1) * $pagenum;
        /* $result = DbAdministrator::getUserSendCodeTaskLog(['uid' => $user['id']], 'task_no,status_message,mobile,send_time', $row = false, '', $offset . ',' . $pagenum);
        foreach ($result as $key => $value) {
            $result[$key]['sendtime'] = date("Y-m-d H:i:s", $value['send_time']);
            unset($result[$key]['send_time']);
        }
        $total = DbAdministrator::countUserSendCodeTaskLog(['uid' => $user['id']]); */
        $result = [];
        $this->redis = Phpredis::getConn();
        $i = 0;
        while ($i < 100) {
            $userstat = $this->redis->lpop('index:meassage:code:user:mulreceive:' . $user['id']);
            $userstat = json_decode($userstat, true);
            if (empty($userstat)) {
                break;
            }
            $result[] = $userstat;
        }
        return ['code' => '200', 'data' => $result];
    }

    public function upGoing($appid, $appkey)
    {
        $user = DbUser::getUserOne(['appid' => $appid], 'id,appkey,user_type,user_status,reservation_service,free_trial', true);
        if (empty($user)) {
            return ['code' => '3000'];
        }
        if ($appkey != $user['appkey']) {
            return ['code' => '3000'];
        }
        $result = [];
        $this->redis = Phpredis::getConn();
        $i = 0;
        while ($i < 100) {
            $userstat = $this->redis->lpop('index:message:upriver:' . $user['id']);
            $userstat = json_decode($userstat, true);
            if (empty($userstat)) {
                break;
            }
            $result[] = $userstat;
        }
        return ['code' => '200', 'upGoing' => $result];
    }

    public function submitTemplateMultimediaMessage($appid, $appkey, $template_id, $mobile_content, $ip, $msg_id = '')
    {
        $user = DbUser::getUserOne(['appid' => $appid], 'id,appkey,user_type,user_status,reservation_service,free_trial', true);
        if (empty($user)) {
            return ['code' => '3000'];
        }
        if ($appkey != $user['appkey']) {
            return ['code' => '3000'];
        }
        $template =  DbSendMessage::getUserMultimediaTemplate(['template_id' => $template_id], '*', true);
        /* if ($template['status'] != 2 || empty($template)) {
            return ['code' => '3003'];
        } */
        if (empty($template)) {
            return ['code' => '3003'];
        } 
        $multimedia_message_frame = DbSendMessage::getUserMultimediaTemplateFrame(['multimedia_template_id' => $template['id']], 'num,name,content,image_path,image_type,variable_len', false, ['num' => 'asc']);
        foreach ($multimedia_message_frame as $key => $value) {
            if ($value['variable_len'] > 0) {
                return ['code' => '3006'];
            }
            unset($multimedia_message_frame[$key]['variable_len']);
        }
        $user_equities = DbAdministrator::getUserEquities(['uid' => $user['id'], 'business_id' => 8], 'id,num_balance', true); //彩信
        if (empty($user_equities)) {
            return ['code' => '3004'];
        }
        $mobile_content = array_filter($mobile_content);
        $real_mobile    = [];
        foreach ($mobile_content as $key => $value) {
            if (checkMobile($value)) {
                $real_mobile[] = $value;
            }
        }

        $send_num = count($mobile_content);
        $real_num = count($real_mobile); //真实发送数量
        if ($send_num > $user_equities['num_balance'] && $user['reservation_service'] != 2) {
            return ['code' => '3005'];
        }
        $SmsMultimediaMessageTask = [];
        $SmsMultimediaMessageTask = [
            'task_no'        => 'mul' . date('ymdHis') . substr(uniqid('', true), 15, 8),
            'uid'            => $user['id'],
            'title'          => $template['title'],
            'mobile_content' => join(',', $mobile_content),
            'source'         => $ip,
            'send_num'       => $send_num,
            'real_num'       => $real_num,
            'free_trial'     => 1,
            'template_id'     => $template_id,
        ];
        if (!empty($msg_id)) {
            $SmsMultimediaMessageTask['send_msg_id'] = $msg_id;
        }
        $free_trial = 1;
        $yidong_channel_id = 0;
        $liantong_channel_id = 0;
        $dianxin_channel_id = 0;
        if ($user['mul_free_trial'] == 2) {
            $free_trial = 2;
            $yidong_channel_id = 59;
            $liantong_channel_id = 59;
            $dianxin_channel_id = 59;
            if ($user['id'] == 221) {
                if ($user['multimeda_free_credit'] > 0 && $real_num <= $user['multimeda_free_credit']) {
                    $free_trial = 2;
                    $yidong_channel_id = 108;
                    $liantong_channel_id = 108;
                    $dianxin_channel_id = 108;
                }else{
                    $free_trial = 1;
                    $yidong_channel_id = 0;
                    $liantong_channel_id = 0;
                    $dianxin_channel_id = 0;
                }
            }
            if ($user['id'] == 219) {
                if ($user['multimeda_free_credit'] > 0 && $real_num <= $user['multimeda_free_credit']) {
                    $free_trial = 2;
                    $yidong_channel_id = 109;
                    $liantong_channel_id = 109;
                    $dianxin_channel_id = 109;
                }else{
                    $free_trial = 1;
                    $yidong_channel_id = 0;
                    $liantong_channel_id = 0;
                    $dianxin_channel_id = 0;
                }
            }
            if ($user['id'] == 220) {
                if ($user['multimeda_free_credit'] > 0 && $real_num <= $user['multimeda_free_credit']) {
                    $free_trial = 2;
                    $yidong_channel_id = 110;
                    $liantong_channel_id = 110;
                    $dianxin_channel_id = 110;
                }else{
                    $free_trial = 1;
                    $yidong_channel_id = 0;
                    $liantong_channel_id = 0;
                    $dianxin_channel_id = 0;
                }
            }
        }
        // $send_task['free_trial'] = 1;
        $SmsMultimediaMessageTask['free_trial'] = $free_trial;
        $SmsMultimediaMessageTask['yidong_channel_id'] = $yidong_channel_id;
        $SmsMultimediaMessageTask['liantong_channel_id'] = $liantong_channel_id;
        $SmsMultimediaMessageTask['dianxin_channel_id'] = $dianxin_channel_id;
        Db::startTrans();
        try {
            DbAdministrator::modifyBalance($user_equities['id'], $send_num, 'dec');
            $bId = DbSendMessage::addUserMultimediaMessage($SmsMultimediaMessageTask);
            if ($bId) {
                foreach ($multimedia_message_frame as $key => $frame) {
                    $frame['multimedia_message_id'] = $bId;
                    $frame['image_path'] = filtraImage(Config::get('qiniu.domain'), $frame['image_path']);
                    DbSendMessage::addUserMultimediaMessageFrame($frame); //添加后的商品id
                }
            }
            Db::commit();
            if (!empty($msg_id)) {
                return ['code' => '200', 'task_no' => $SmsMultimediaMessageTask['task_no'], 'msg_id' => $msg_id];
            }
            return ['code' => '200', 'task_no' => $SmsMultimediaMessageTask['task_no']];
        } catch (\Exception $e) {
            Db::rollback();
            exception($e);
            return ['code' => '3007'];
        }
    }

    public function chuangLanMmsCallBack($code, $desc, $task_id, $phone)
    {
        $task = DbSendMessage::getUserMultimediaMessage(['id' => $task_id], 'uid,task_no', true);
        if (!empty($task)) {
            $redisMessageCodeDeliver = 'index:meassage:multimediamessage:deliver:59'; //创蓝彩信回执通道
            $redis = Phpredis::getConn();
            $send_task_log = [];
            if ($code == '30') {
                $code = 'DELIVRD';
                $send_status = 3;
            } else {
                $code = $desc;
                $send_status = 4;
            }
            $send_task_log = [
                'task_no'        => $task['task_no'],
                'uid'            => $task['uid'],
                'mobile'         => $phone,
                'status_message' => $code,
                'send_status'    => $send_status,
                'send_time'      => time(),
            ];
            $redis->rpush($redisMessageCodeDeliver, json_encode($send_task_log));
            return 'OK';
        } else {
            return 'error';
        }
    }

    public function chuangLanMmsSftpCallBack($code, $desc, $task_id, $phone)
    {
        $task = DbSendMessage::getSflMultimediaMessage(['id' => $task_id], 'mseeage_id', true);
        if (!empty($task)) {
            $redisMessageCodeDeliver = 'index:meassage:multimediamessage:deliver:94'; //创蓝彩信回执通道
            $redis = Phpredis::getConn();
            $send_task_log = [];
            if ($code == '30') {
                $code = 'DELIVRD';
                $send_status = 3;
            } else {
                $code = $desc;
                $send_status = 4;
            }
            $send_task_log = [
                'mseeage_id'        => $task['mseeage_id'],
                'mobile'         => $phone,
                'status_message' => $code,
                'send_status'    => $send_status,
                'send_time'      => time(),
            ];
            $redis->rpush($redisMessageCodeDeliver, json_encode($send_task_log));
            return 'OK';
        } else {
            return 'error';
        }
    }

    public function numberDetection($appid, $appkey, $mobile)
    {

        $check_mobile_result = [];
        $user = DbUser::getUserOne(['appid' => $appid], 'id,appkey,user_type,user_status,reservation_service,free_trial', true);
        if (empty($user)) {
            return ['code' => '3000'];
        }
        if ($appkey != $user['appkey']) {
            return ['code' => '3000'];
        }
        $userEquities = DbAdministrator::getUserEquities(['uid' => $user['id'], 'business_id' => 10], 'id,agency_price,num_balance', true);
        if (empty($userEquities)) {
            return ['code' => '3001'];
        }
        if ($userEquities['num_balance'] < 0) {
            return ['code' => '3002'];
        }
        $true_mobile = [];
        $secret_id = '06FDC4A71F5E1FDE4C061DBA653DD2A5';
        $secret_key = 'ef0587df-86dc-459f-ad82-41c6446b27a5';
        $api = 'https://api.yunzhandata.com/api/deadnumber/v1.0/detect?sig=';
        $ts = date("YmdHis", time());
        $sig = sha1($secret_id . $secret_key . $ts);
        // echo $sig;
        // $mobile = '15201926171';
        // return $this->encrypt($mobile, $secret_id);
        $mobile_data = explode(',', $mobile);
        if (count($mobile_data) > $userEquities['num_balance']) {
            return ['code' => '3002'];
        }
        if (count($mobile_data) > 2000) {
            return ['code' => '3003'];
        }
        /* 实号数据库查询 */
        $entity_mobile = Db::query("SELECT `mobile` FROM `yx_real_mobile` WHERE mobile IN (" . join(',', $mobile_data) . ") GROUP BY `mobile` ");
        $entity_mobiles = [];
        if (!empty($entity_mobile)) {
            foreach ($entity_mobile as $key => $value) {
                $chech_mobile = [];
                $chech_mobile = [
                    'mobile' => $value['mobile'],
                    'check_result' => 2
                ];
                $check_mobile_result[] = $chech_mobile;
                $entity_mobiles[] = $value['mobile'];
            }
        }
        $had_mobile = array_diff($mobile_data, $entity_mobiles);

        $empty_mobile = Db::query("SELECT `mobile`,`check_result`,`update_time` FROM `yx_mobile` WHERE mobile IN (" . join(',', $had_mobile) . ") ");
        $empty_mobiles = [];
        if (!empty($empty_mobile)) {
            foreach ($empty_mobile as $key => $value) {
                if (date("Ymd", $value['update_time']) < date('Ymd', time())) {
                } else {
                    $chech_mobile = [];
                    $chech_mobile = [
                        'mobile' => $value['mobile'],
                        'check_result' => $value['check_result']
                    ];
                    $check_mobile_result[] = $chech_mobile;
                    $empty_mobiles[] = $value['mobile'];
                }
            }
        }

        $need_check_mobile = array_diff($had_mobile, $empty_mobiles);


        foreach ($need_check_mobile as $key => $value) {

            if (checkMobile($value) != false) {
                if (strlen($value) == 11) {
                    $true_mobile[] = $this->encrypt($value, $secret_id);
                } else {
                    $chech_mobile = [];
                    $chech_mobile = [
                        'mobile' => $value,
                        'check_result' => 0
                    ];
                    $check_mobile_result[] = $chech_mobile;
                }
            } else {
                $chech_mobile = [];
                $chech_mobile = [
                    'mobile' => $value,
                    'check_result' => 0
                ];
                $check_mobile_result[] = $chech_mobile;
            }
        }
        // print_r($check_mobile_result);die;
        if (!empty($true_mobile)) {
            $api = $api . $sig . "&sid=" . $secret_id . "&skey=" . $secret_key . "&ts=" . $ts;

            $data = [];
            $data = [
                // 'sig' => $sig,
                // 'sid' => $secret_id,
                // 'skey' => $secret_key,
                // 'ts' => $ts,
                'mobiles' => $true_mobile
            ];
            $headers = [
                'Authorization:' . base64_encode($secret_id . ':' . $ts), 'Content-Type:application/json'
            ];
            // echo base64_decode('MDZGREM0QTcxRjVFMUZERTRDMDYxREJBNjUzREQyQTU6MTU5MTAwNzE5Ng==');

            $data = $this->sendRequest2($api, 'post', $data, $headers);
            // print_r(json_decode($data),true);die;
            // print_r($data);die;
            $result = json_decode($data, true);
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
                    }
                    $chech_mobile = [];
                    $chech_mobile = [
                        'mobile' => $mobile,
                        'check_result' => $check_result
                    ];
                    $check_mobile_result[] = $chech_mobile;
                }
            } else {
                return ['code' => '3004'];
            }
        }
        // $en_mobile = $this->encrypt($mobile, $secret_id);
        // echo $en_mobile;

        return ['code' => '200', 'check_result' => $check_mobile_result];
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

    /**
     *
     * @param string $string 需要加密的字符串
     * @param string $key 密钥
     * @return string
     */
    public static function encrypt($string, $key)
    {
        // 对接java，服务商做的AES加密通过SHA1PRNG算法（只要password一样，每次生成的数组都是一样的），Java的加密源码翻译php如下：
        $key = substr(openssl_digest(openssl_digest($key, 'sha1', true), 'sha1', true), 0, 16);

        // openssl_encrypt 加密不同Mcrypt，对秘钥长度要求，超出16加密结果不变
        $data = openssl_encrypt($string, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);

        $data = strtoupper(bin2hex($data));
        // print_r($data);
        return $data;
    }

    public function upMmsGoing($appid, $appkey)
    {
        $user = DbUser::getUserOne(['appid' => $appid], 'id,appkey,user_type,user_status,reservation_service,free_trial', true);
        if (empty($user)) {
            return ['code' => '3000'];
        }
        if ($appkey != $user['appkey']) {
            return ['code' => '3000'];
        }
        $result = [];
        $this->redis = Phpredis::getConn();
        $i = 0;
        while ($i < 100) {
            $userstat = $this->redis->lpop('index:message:Mmsupriver:' . $user['id']);
            $userstat = json_decode($userstat, true);
            if (empty($userstat)) {
                break;
            }
            $result[] = $userstat;
        }
        return ['code' => '200', 'upGoing' => $result];
    }

    public function upGoingForChuangLan($account, $phone, $msg, $moTime, $extendCode)
    {
        //C4786051
        //C0120120
        $this->redis = Phpredis::getConn();
        if ($account == 'C0120120') {
            $user = DbSendMessage::getUserMultimediaMessageLog(['mobile' => $phone], 'task_no,uid', true, ['id' => 'desc']);
            $upgoing = [];
            $upgoing = [
                'mobile' => $phone,
                'message_info' => $msg,
                'get_time' => $moTime,
            ];
            $this->redis->rPush('index:message:Mmsupriver:' . $user['uid'], json_encode($upgoing));
            $insert_data = [];
            $insert_data = [
                'uid' => $user['uid'],
                'task_no' => $user['task_no'],
                'mobile' => $phone,
                'message_info' => $msg,
                'create_time' => strtotime($moTime),
                'business_id' => 8,
            ];
            Db::startTrans();
            try {
                DbSendMessage::addUserUpriver($insert_data);
                Db::commit();
                return 'OK';
            } catch (\Exception $e) {
                Db::rollback();
                exception($e);
                return ['code' => '3007'];
            }
        } elseif ($account == 'C4786051') { //sftp
            $prefix = substr(trim($phone), 0, 7);
            // $res    = Db::query("SELECT `source`,`province_id`,`province` FROM yx_number_source WHERE `mobile` = '" . $prefix . "' LIMIT 1 ");
            $numberSource = DbSendMessage::getNumberSource(['mobile' => $prefix], 'source_name,city', true);
            $insert_data = [];

            $insert_data = [
                'from' => 'sfl',
                'mobile' => $phone,
                'type' => 'MMS',
                'message_info' => $msg,
                'receive_time' => $moTime,
                'source_name' => $numberSource['source_name'],
                'city' => $numberSource['city'],
            ];
            $this->redis->rPush('sftp:upriver:chuanglan', json_encode($insert_data));
            return 'OK';
        }elseif ($account == 'C2431630_C4786051') {
            $user = DbSendMessage::getUserMultimediaMessageLog(['mobile' => $phone,'uid' => 221], 'task_no,uid', true, ['id' => 'desc']);
            $upgoing = [];
            $upgoing = [
                'mobile' => $phone,
                'message_info' => $msg,
                'get_time' => $moTime,
            ];
            $this->redis->rPush('index:message:Mmsupriver:221', json_encode($upgoing));
            $insert_data = [];
            $insert_data = [
                'uid' => $user['uid'],
                'task_no' => $user['task_no'],
                'mobile' => $phone,
                'message_info' => $msg,
                'create_time' => strtotime($moTime),
                'business_id' => 8,
            ];
            Db::startTrans();
            try {
                DbSendMessage::addUserUpriver($insert_data);
                Db::commit();
                return 'OK';
            } catch (\Exception $e) {
                Db::rollback();
                exception($e);
                return ['code' => '3007'];
            }
        } elseif ($account == 'C5304745_C4786051') {
            $user = DbSendMessage::getUserMultimediaMessageLog(['mobile' => $phone,'uid' => 219], 'task_no,uid', true, ['id' => 'desc']);
            $upgoing = [];
            $upgoing = [
                'mobile' => $phone,
                'message_info' => $msg,
                'get_time' => $moTime,
            ];
            $this->redis->rPush('index:message:Mmsupriver:219', json_encode($upgoing));
            $insert_data = [];
            $insert_data = [
                'uid' => $user['uid'],
                'task_no' => $user['task_no'],
                'mobile' => $phone,
                'message_info' => $msg,
                'create_time' => strtotime($moTime),
                'business_id' => 8,
            ];
            Db::startTrans();
            try {
                DbSendMessage::addUserUpriver($insert_data);
                Db::commit();
                return 'OK';
            } catch (\Exception $e) {
                Db::rollback();
                exception($e);
                return ['code' => '3007'];
            }
        } elseif ($account == 'C5427166_C4786051') {
            $user = DbSendMessage::getUserMultimediaMessageLog(['mobile' => $phone,'uid' => 220], 'task_no,uid', true, ['id' => 'desc']);
            $upgoing = [];
            $upgoing = [
                'mobile' => $phone,
                'message_info' => $msg,
                'get_time' => $moTime,
            ];
            $this->redis->rPush('index:message:Mmsupriver:220', json_encode($upgoing));
            $insert_data = [];
            $insert_data = [
                'uid' => $user['uid'],
                'task_no' => $user['task_no'],
                'mobile' => $phone,
                'message_info' => $msg,
                'create_time' => strtotime($moTime),
                'business_id' => 8,
            ];
            Db::startTrans();
            try {
                DbSendMessage::addUserUpriver($insert_data);
                Db::commit();
                return 'OK';
            } catch (\Exception $e) {
                Db::rollback();
                exception($e);
                return ['code' => '3007'];
            }
        } 
    }
}
