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

    /**
     * 账号密码登录
     * @param $mobile
     * @param $password
     * @param $buid
     * @return array
     * @author zyr
     */
    public function cmppSendTest($mobile, $code)
    {

        //设置参数，并且转换成16进制数字显示
        $time   = time();
        $i      = 1;
        $a_time = 0;
        do {
            echo $i . "\n";
            $i++;
            $a_time = time();
        } while ($a_time < $time);

        print(bin2hex(pack("C", 1)) . "\n");
        // echo $mobile."\n";die;
        //时间格式二进制转换
        //   echo (string) decbin(date("m",time())).decbin(date("d",time())).decbin(date("H",time())).decbin(date("i",time())).decbin(date("s",time())).decbin(101161);

        // print_r(strlen(decbin($mobile)));
        die;

        // $this->cmpp->Start("124.251.111.5",9000,"yxyx01","bMtHJY96","","","","");
        // $result = $this->cmpp->sendSms($mobile, $code); //发送短信
        // return $result;
        // die;
        // $cmpp = new Cmppsubmit($mobile,$code);
        // ;
        // $cmpp->createSocket();
        // $cmpp->CMPP_CONNECT();
        // print_r($cmpp->CMPP_SUBMIT());
        // $tomsisdn = $_POST["tomsisdn"];

        // $contents = $_POST["contents"];

        // $this->Owncmpp->Start("116.62.88.162", "8592", "101161", "5hsey6u9", "106928080159", "217062");
        // $result = $this->Owncmpp->cmppSubmit($mobile,$code);
        // die;
        $tomsisdn = $mobile;
        $contents = $code;
        // echo realpath("../");die;
        // $str = "php -f ".realpath("../")."/application/common/action/index/Cmppsubmit.php {$tomsisdn} {$contents}";
        // echo $str."\n";
        // exec($str, $out, $res);
        // print_r(exec($str, $out, $res));
        // if($res === 0)
        // echo $out[1];
        // print_r($out);
        // die;
        // return $result;

        $host          = "116.62.88.162"; //服务商ip
        $port          = "8592"; //短连接端口号   17890长连接端口号
        $Source_Addr   = "101161"; //企业id  企业代码
        $Shared_secret = '5hsey6u9'; //网关登录密码
        $Dest_Id       = "106928080159"; //短信接入码 短信端口号
        $Service_Id    = "217062";
        $Sequence_Id   = 1;
        $SP_ID         = "";
        $socket        = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket < 0) {
            // echo "socket_create() failed: reason: " . socket_strerror($socket) . "\n";
        } else {
            // echo "OK.\n";
        }
        // echo "试图连接 '$host' 端口 '$port'...\n";
        $result = socket_connect($socket, $host, $port);

        if ($result < 0) {
            // echo "socket_connect() failed.\nReason: ($result) " . socket_strerror($result) . "\n";
        } else {
            // echo "连接OK\n";
        }
        date_default_timezone_set('PRC');
        $Version             = 0x20;
        $Timestamp           = date('mdHis');
        $AuthenticatorSource = md5($Source_Addr . pack("a9", "") . $Shared_secret . $Timestamp, true);
        $bodyData            = pack("a6a16CN", $Source_Addr, $AuthenticatorSource, $Version, $Timestamp);
        $Command_Id          = 0x00000001;
        $Total_Length        = strlen($bodyData) + 12;
        $headData            = pack("NNN", $Total_Length, $Command_Id, $Sequence_Id);
        socket_write($socket, $headData . $bodyData, $Total_Length);
        $headData = socket_read($socket, 1024);
        // print_r($headData);die;
        // echo $AuthenticatorSource;
        // print_r(socket_write($socket, $headData . $bodyData, $Total_Length));die;
        $head = unpack("NTotal_Length/NCommand_Id/NSequence_Id", $headData);
        // print_r($head);die;
        $Sequence_Id = $head['Sequence_Id'];
        $bodyData    = socket_read($socket, $head['Total_Length'] - 12);
        switch ($head['Command_Id'] & 0x0fffffff) {
            case 0x00000001:
                $body   = unpack("CStatus/a16AuthenticatorISMG/CVersion", $bodyData);
                $Msg_Id = rand(1, 100);
                //$bodyData = pack("a8", $Msg_Id);
                $bodyData = pack("N", $Msg_Id) . pack("N", "00000000");
                $bodyData .= pack("C", 1) . pack("C", 1);
                $bodyData .= pack("C", 0) . pack("C", 0);
                $bodyData .= pack("a10", $Service_Id);
                $bodyData .= pack("C", 0) . pack("a32", "") . pack("C", 0) . pack("C", 0) . pack("C", 0) . pack("C", 0) . pack("a6", $SP_ID) . pack("a2", "02") . pack("a6", "") . pack("a17", "") . pack("a17", "") . pack("a21", $Dest_Id) . pack("C", 1);
                $bodyData .= pack("a32", $mobile);
                $bodyData .= pack("C", 0);
                $len = strlen($code);
                $bodyData .= pack("C", $len);
                $bodyData .= pack("a" . $len, $code);
                $bodyData .= pack("a20", "00000000000000000000");
                // send($bodyData, "CMPP_SUBMIT", $Msg_Id);
                $Command_Id   = 0x00000004;
                $Total_Length = strlen($bodyData) + 12;
                if ($Msg_Id != 0) {
                    $Sequence_Id = $Msg_Id;
                } else {
                    if ($Sequence_Id < 10) {
                        $Sequence_Id = $Sequence_Id;
                    } else {
                        $Sequence_Id = 1;
                    }
                    $Sequence_Id = $Sequence_Id + 1;
                }
                $headData = pack("NNN", $Total_Length, $Command_Id, $Sequence_Id);
                // print_r(socket_write($socket, $headData . $bodyData, $Total_Length));die;
                print_r($socket);
                die;
                socket_write($socket, $headData . $bodyData, $Total_Length);
                $headData = socket_read($socket, 12);
                print_r($headData);
                die;
                if (empty($headData)) {
                    // $this->log();
                    $code = 0000;
                }
                // echo 1;
                break;
                // case 0x00000005:
                //     $this->CMPP_DELIVER($head['Total_Length'],$Sequence_Id);
                //     break;
                // case 0x80000005:
                //     $this->CMPP_DELIVER($head['Total_Length'],$Sequence_Id);
                //     break;
            case 0x00000008:
                echo 2;
                $bodyData = pack("C", 1);
                // $this->send($bodyData, "CMPP_ACTIVE_TEST_RESP", $Sequence_Id);
                break;
            case 0x00000004:
                // $this->cmppSubmitResp();
                echo 3;
                break;
                // case 0x80000004:
                //     $this->CMPP_SUBMIT_RESP();
                //     break;
            default:
                echo 4;
                $bodyData = pack("C", 1);
                // $this->send($bodyData, "CMPP_ACTIVE_TEST_RESP", $Sequence_Id);
                break;
        }
        // print_r($head['Command_Id']);
        // print_r($bodyData);
        if ($code == 0000) {
            return ['code' => '3002', 'msg' => '发送内容为空'];
        }
        die;
    }

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

    public function getSmsMarketingTask($Username, $Password, $Content, $Mobiles, $Dstime, $ip, $task_name, $signature_id)
    {
        $Mobiles = array_unique(array_filter($Mobiles));
        // $Password = md5($Password);
        $user = DbUser::getUserOne(['appid' => $Username], 'id,appkey,user_type,user_status,reservation_service,free_trial', true);
        if (empty($user)) {
            return ['code' => '3000'];
        }
        if ($Password != $user['appkey']) {
            return ['code' => '3000'];
        }
        $userEquities = DbAdministrator::getUserEquities(['uid' => $user['id'], 'business_id' => 5], 'id,agency_price,num_balance', true);
        if (empty($userEquities)) {
            return ['code' => '3005'];
        }
        if ($user['user_status'] != 2) {
            return ['code' => '3006'];
        }
        $send_num = count(array_filter($Mobiles));

        $effective_mobile = [];
        foreach ($Mobiles as $key => $value) {
            if (checkMobile($value) == true) {
                $effective_mobile[] = $value;
            }
        }

        if (!empty($signature)) {
            $signature =  DbSendMessage::getUserSignature(['uid' => $user['uid'], 'signature_id' => $signature_id], '*', true);
            if (empty($signature)) {
                return ['code' => '3008'];
            }
            if ($signature['status'] != 2) {
                return ['code' => '3010'];
            }
            $Content = $signature['title'] . $Content;
        }

        // print_r($effective_mobile);die;
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

        Db::startTrans();
        try {
            DbAdministrator::modifyBalance($userEquities['id'], $real_num, 'dec');
            $id = DbAdministrator::addUserSendTask($data);

            Db::commit();
            return ['code' => '200', 'task_no' => $data['task_no']];
        } catch (\Exception $e) {
            // exception($e);
            Db::rollback();
            return ['code' => '3009'];
        }
        // $redisMessageMarketingSend = Config::get('rediskey.message.redisMessageMarketingSend');
        // foreach ($effective_mobile as $key => $value) {
        //     $this->redis->rpush($redisMessageMarketingSend.":2",$value.":".$id.":".$Content); //三体营销通道
        //     // $this->redis->hset($redisMessageMarketingSend.":2",$value,$id.":".$Content); //三体营销通道
        // }
        return ['code' => '200', 'task_no' => $data['task_no']];
    }

    public function getSmsBuiness($Username, $Password, $Content, $Mobiles, $ip, $signature_id = '')
    {
        $this->redis = Phpredis::getConn();
        // print_r($this->redis);
        // die;
        $Mobiles = array_unique(array_filter($Mobiles));
        $user    = DbUser::getUserOne(['appid' => $Username], 'id,appkey,user_type,user_status,reservation_service,free_trial', true);
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

        $redisMessageMarketingSend = Config::get('rediskey.message.redisMessageCodeSend');

        // print_r($res);die;
        $user_equities = DbAdministrator::getUserEquities(['uid' => $user['id'], 'business_id' => 6], 'id,num_balance', true);
        if (empty($user_equities)) {
            return ['code' => '3006'];
        }
        // if ($user_equities['num_balance'] < 1 && $user['reservation_service'] == 1) {
        //     return ['code' => '3006'];
        // }
        if (!empty($signature)) {
            $signature =  DbSendMessage::getUserSignature(['uid' => $user['uid'], 'signature_id' => $signature_id], '*', true);
            if (empty($signature)) {
                return ['code' => '3008'];
            }
            if ($signature['status'] != 2) {
                return ['code' => '3010'];
            }
            $Content = $signature['title'] . $Content;
        }


        $send_num = count($Mobiles);

        $effective_mobile = [];
        foreach ($Mobiles as $key => $value) {
            if (checkMobile(($value))) {
                $effective_mobile[] = $value;
            }
        }
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
        $data['uid']          = $user['id'];
        $data['source']       = $ip;
        $data['task_content'] = $Content;

        $data['mobile_content'] = join(',', $Mobiles);
        $data['task_name']      = $Content;
        $data['send_num']       = $send_num;
        $data['real_num']       = $real_num;
        $data['send_length']    = mb_strlen($Content);
        $data['free_trial']     = 1;
        $data['task_no']        = 'bus' . date('ymdHis') . substr(uniqid('', true), 15, 8);
        if ($user['free_trial'] == 2) {
            $data['free_trial'] = 2;
            if ($user['id'] == 56) {
                $data['channel_id'] = 22;
            } elseif ($user['id'] == 50) {
                $data['channel_id'] = 22;
            } else {
                $data['channel_id'] = 1; //三体
            }
        }
        Db::startTrans();
        try {
            DbAdministrator::modifyBalance($user_equities['id'], $real_num, 'dec');
            $bId = DbAdministrator::addUserSendCodeTask($data); //
            Db::commit();
            if ($data['free_trial'] == 2) {
                $res = $this->redis->rpush("index:meassage:business:sendtask", $bId);
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
        $error_phone   = [];
        $mobile_phone  = [];
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
                    } elseif ($mobile_Source['source'] == 3) { //联通
                        $telecom_num++;
                        $telecom_phone[] = $value;
                    } elseif ($mobile_Source['source'] == 4) { //联通
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
        $phone    = join(',', $real_mobile);
        $real_num = count($real_mobile);
        return ['code' => '200', 'submit_num' => $submit_num, 'real_num' => $real_num, 'mobile_num' => $mobile_num, 'unicom_num' => $unicom_num, 'telecom_num' => $telecom_num, 'virtual_num' => $virtual_num, 'unknown_num' => $unknown_num, 'mobile_phone' => $mobile_phone, 'unicom_phone' => $unicom_phone, 'unicom_phone' => $unicom_phone, 'virtual_phone' => $virtual_phone, 'phone' => $phone, 'error_phone' => $error_phone];
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
    public function getSmsMultimediaMessageTask($appid, $appkey, $content_data, $mobile_content, $send_time, $ip, $title)
    {
        $user = DbUser::getUserOne(['appid' => $appid], 'id,appkey,user_type,user_status,reservation_service,free_trial', true);
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
                // $content_length+= strlen($value['content']);
            }
            $content_length += (strlen($frame['content']) / 8);
            if (!isset($value['image_path'])) {
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
                if (!isset($head['Content-Type']) || !in_array($head['Content-Type'], ['image/gif', 'image/jpeg'])) {
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
            if (checkMobile($real_mobile)) {
                $real_mobile[] = $value;
            }
        }
        $send_num = count($mobile_content);
        $real_num = count($real_mobile); //真实发送数量
        if ($send_num > $user_equities['num_balance'] && $user['reservation_service'] != 2) {
            return ['code' => '3007'];
        }

        $SmsMultimediaMessageTask = [];
        $SmsMultimediaMessageTask = [
            'task_no'        => 'mul' . date('ymdHis') . substr(uniqid('', true), 15, 8),
            'uid'            => $user['id'],
            'title'          => $title,
            'mobile_content' => join(',', $mobile_content),
            'source'         => $ip,
            'send_num'       => $send_num,
            'real_num'       => $real_num,
            'free_trial'     => 1,
        ];

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
        $result = DbSendMessage::getUserUserMultimediaMessageLog($where, '*', false, '', $offset . ',' . $pageNum);
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
        $result = DbSendMessage::getUserUserMultimediaMessageLog([['uid', '=', $user['id']], ['user_query_status', '=', 1], ['status_message', '<>', '']], 'id,task_no,mobile,status_message,update_time', false, '', 200);
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
        $template_id = getRandomString(8);
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

    public function submitBatchCustomBusiness($appid, $appkey, $template_id = '', $connect, $ip, $signature_id = '')
    {
        $this->redis = Phpredis::getConn();
        $user = DbUser::getUserOne(['appid' => $appid], 'id,appkey,user_type,user_status,reservation_service,free_trial', true);
        if (empty($user)) {
            return ['code' => '3000'];
        }
        if ($appkey != $user['appkey']) {
            return ['code' => '3000'];
        }
        $user_equities = DbAdministrator::getUserEquities(['uid' => $user['id'], 'business_id' => 6], 'id,num_balance', true);
        if (empty($user_equities)) {
            return ['code' => '3002'];
        }
        if (!empty($template_id)) {
            $template =  DbSendMessage::getUserModel(['template_id' => $template_id], '*', true);
            if ($template['status'] != 3) {
                return ['code' => '3003'];
            }
        }
        if (!empty($signature_id)) {
            $signature =  DbSendMessage::getUserSignature(['uid' => $user['uid'], 'signature_id' => $signature_id], '*', true);
            if (empty($signature)) {
                return ['code' => '3008'];
            }
            if ($signature['status'] != 2) {
                return ['code' => '3010'];
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
                    for ($i = 0; $i < $template['variable_len']; $i++) {
                        $var_num = $i + 1;
                        $real_text = str_replace("{{var" . $var_num . "}}", $replace_data[$i], $template['content']); //内容
                    }
                }

                if (in_array($real_text, $send_data)) {
                    $send_data_mobile[array_search($real_text, $send_data)][] = $send_text[1];
                } else {
                    $send_data[] = $real_text;
                    $send_data_mobile[array_search($real_text, $send_data)][] = $send_text[1];
                }
            } else {
                $real_text = $send_text[0];
                if (in_array($real_text, $send_data)) {
                    $send_data_mobile[array_search($real_text, $send_data)][] = $send_text[1];
                } else {
                    $send_data[] = $real_text;
                    $send_data_mobile[array_search($real_text, $send_data)][] = $send_text[1];
                }
            }
        }

        $free_taskno = [];
        $trial = []; //需审核
        //组合任务包
        $real_num = 0;

        $all_task_no = [];
        $task_no_mobile = [];
        foreach ($send_data as $key => $value) {
            $send_task = [];
            $task_no = 'bus' . date('ymdHis') . substr(uniqid('', true), 15, 8);
            $send_task = [
                'task_no' => $task_no,
                'uid'     => $user['id'],
                'task_content' => $value,
                'mobile_content' => join(',', $send_data_mobile[$key]),
                'source'         => $ip,
                'send_length'       => mb_strlen($value),
                'send_num'       => count($send_data_mobile[$key]),
            ];
            if (mb_strlen($value) > 70) {
                $real_num += ceil(mb_strlen($value) / 67) * count($send_data_mobile[$key]);
            } else {
                $real_num += count($send_data_mobile[$key]);
            }
            $send_task['free_trial'] = 1;
            if ($user['free_trial'] == 2) {
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
                    } else {
                        // array_push($task_no, $free_taskno);
                        $send_task['free_trial'] = 2;
                        $send_task['channel_id'] = 22;
                        $free_taskno[] = $task_no;
                        // array_push($free_trial, $send_task);
                    }
                } else {
                    if (!empty($value)) {
                        $free_taskno[] = $task_no;
                        $send_task['free_trial'] = 2;
                        $send_task['channel_id'] = 22;
                        // array_push($free_trial, $send_task);
                    }
                }
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
            $save = DbAdministrator::saveUserSendCodeTask($trial);
            if ($save) {
                if (!empty($free_taskno)) {

                    DbAdministrator::modifyBalance($user_equities['id'], $real_num, 'dec');
                    //免审
                    $free_ids = DbAdministrator::getUserSendCodeTask([['task_no', 'IN', join(',', $free_taskno)]], 'id', false);
                    foreach ($free_ids as $key => $value) {
                        $res = $this->redis->rpush("index:meassage:business:sendtask", $value['id']);
                    }
                }
            }
            Db::commit();
            return ['code' => '200', 'task_no' => $all_task_no, 'task_no_mobile' => $task_as_mobile];
        } catch (\Exception $e) {
            Db::rollback();
            exception($e);
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

    public function submitBatchCustomMarketing($appid, $appkey, $template_id = '', $connect, $ip, $signature_id = '')
    {
        $this->redis = Phpredis::getConn();
        $user = DbUser::getUserOne(['appid' => $appid], 'id,appkey,user_type,user_status,reservation_service,free_trial', true);
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
            $template =  DbSendMessage::getUserModel(['template_id' => $template_id], '*', true);
            if ($template['status'] != 3) {
                return ['code' => '3003'];
            }
        }
        if (!empty($signature_id)) {
            $signature =  DbSendMessage::getUserSignature(['uid' => $user['uid'], 'signature_id' => $signature_id], '*', true);
            if (empty($signature)) {
                return ['code' => '3008'];
            }
            if ($signature['status'] != 2) {
                return ['code' => '3010'];
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
                    for ($i = 0; $i < $template['variable_len']; $i++) {
                        $var_num = $i + 1;
                        $real_text = str_replace("{{var" . $var_num . "}}", $replace_data[$i], $template['content']); //内容
                    }
                }

                if (in_array($real_text, $send_data)) {
                    $send_data_mobile[array_search($real_text, $send_data)][] = $send_text[1];
                } else {
                    $send_data[] = $real_text;
                    $send_data_mobile[array_search($real_text, $send_data)][] = $send_text[1];
                }
            } else {
                $real_text = $send_text[0];
                if (in_array($real_text, $send_data)) {
                    $send_data_mobile[array_search($real_text, $send_data)][] = $send_text[1];
                } else {
                    $send_data[] = $real_text;
                    $send_data_mobile[array_search($real_text, $send_data)][] = $send_text[1];
                }
            }
        }

        $free_taskno = [];
        $trial = []; //需审核
        //组合任务包
        $real_num = 0;

        $all_task_no = [];
        $task_no_mobile = [];
        foreach ($send_data as $key => $value) {
            $send_task = [];
            $task_no = 'mar' . date('ymdHis') . substr(uniqid('', true), 15, 8);
            $send_task = [
                'task_no' => $task_no,
                'uid'     => $user['id'],
                'task_content' => $value,
                'mobile_content' => join(',', $send_data_mobile[$key]),
                'source'         => $ip,
                'send_length'       => mb_strlen($value),
                'send_num'       => count($send_data_mobile[$key]),
            ];
            if (mb_strlen($value) > 70) {
                $real_num += ceil(mb_strlen($value) / 67) * count($send_data_mobile[$key]);
            } else {
                $real_num += count($send_data_mobile[$key]);
            }
            $send_task['free_trial'] = 1;
            if ($user['free_trial'] == 2) {
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
                    } else {
                        // array_push($task_no, $free_taskno);
                        $send_task['free_trial'] = 2;
                        $send_task['channel_id'] = 17;
                        $free_taskno[] = $task_no;
                        // array_push($free_trial, $send_task);
                    }
                } else {
                    if (!empty($value)) {
                        $free_taskno[] = $task_no;
                        $send_task['free_trial'] = 2;
                        $send_task['channel_id'] = 17;
                        // array_push($free_trial, $send_task);
                    }
                }
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
        Db::startTrans();
        try {
            $save = DbAdministrator::saveUserSendTask($trial);
            if ($save) {
                if (!empty($free_taskno)) {

                    DbAdministrator::modifyBalance($user_equities['id'], $real_num, 'dec');
                    //免审
                    $free_ids = DbAdministrator::getUserSendCodeTask([['task_no', 'IN', join(',', $free_taskno)]], 'id', false);
                    foreach ($free_ids as $key => $value) {
                        $res = $this->redis->rpush("index:meassage:marketing:sendtask", $value['id']);
                    }
                }
            }
            Db::commit();
            return ['code' => '200', 'task_no' => $all_task_no, 'task_no_mobile' => $task_as_mobile];
        } catch (\Exception $e) {
            Db::rollback();
            exception($e);
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
}
