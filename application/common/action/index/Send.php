<?php

namespace app\common\action\index;

use app\common\action\index\Owncmpp;
use app\facade\DbAdmin;
use app\facade\DbAdministrator;
use app\facade\DbImage;
use app\facade\DbProvinces;
use app\facade\DbUser;
use Config;
use Env;
use think\Db;

class Send extends CommonIndex {

    /**
     * 账号密码登录
     * @param $mobile
     * @param $password
     * @param $buid
     * @return array
     * @author zyr
     */
    public function cmppSendTest($mobile, $code) {

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

    public function smsBatch($Username, $Password, $Content, $Mobiles, $Dstime, $ip) {
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

    public function getBalanceSmsBatch($Username, $Password) {
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

    public function getReceiveSmsBatch($Username, $Password) {
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

    public function getSmsMarketingTask($Username, $Password, $Content, $Mobiles, $Dstime, $ip, $task_name) {
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
        $send_num             = count(array_filter($Mobiles));

        if ($send_num > $userEquities['num_balance'] && $user['reservation_service'] != 2) {
            return ['code' => '3007'];
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
        $data                 = [];
        $data['uid']          = $user['id'];
        $data['source']       = $ip;
        $data['task_content'] = $Content;

        $data['mobile_content'] = join(',', $Mobiles);
        $data['task_name']      = $task_name;
        $data['send_num']       = $send_num;
        $data['send_length']    = mb_strlen($Content);
        $data['free_trial']     = 1;
        $data['task_no']        = 'mar' . date('ymdHis') . substr(uniqid('', true), 15, 8);

        Db::startTrans();
        try {
            DbAdministrator::modifyBalance($userEquities['id'], $send_num, 'dec');
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

    public function getSmsBuiness($Username, $Password, $Content, $Mobiles, $ip) {
        $Mobiles = array_unique(array_filter($Mobiles));
        $user = DbUser::getUserOne(['appid' => $Username], 'id,appkey,user_type,user_status,reservation_service,free_trial', true);
        if (empty($user)) {
            return ['code' => '3000'];
        }
        if ($user['user_status'] != 2) {
            return ['code' => '3004'];
        }
        if ($Password != $user['appkey']) {
            return ['code' => '3000'];
        }
        $prefix = substr($Mobile, 0, 7);
        $res    = DbProvinces::getNumberSource(['mobile' => $prefix], 'source,province_id,province', true);

        $redisMessageMarketingSend = Config::get('rediskey.message.redisMessageCodeSend');

        // print_r($res);die;
        $user_equities = DbAdministrator::getUserEquities(['uid' => $user['id'], 'business_id' => 6], 'id,num_balance', true);
        if (empty($user_equities)) {
            return ['code' => '3005'];
        }
        // if ($user_equities['num_balance'] < 1 && $user['reservation_service'] == 1) {
        //     return ['code' => '3006'];
        // }
        $send_num             = count($Mobiles);

        if ($send_num > $userEquities['num_balance'] && $user['reservation_service'] != 2) {
            return ['code' => '3007'];
        }
        $effective_mobile = [];
        foreach ($Mobiles as $key => $value) {
            if (checkMobile(($value))) {
                $effective_mobile[] = $value;
            }
        }
        if (empty($effective_mobile)) {
            return ['code' => '3001'];
        }
        
        $data                 = [];
        $data['uid']          = $user['id'];
        $data['source']       = $ip;
        $data['task_content'] = $Content;

        $data['mobile_content'] = join(',', $Mobiles);
        $data['task_name']      = $task_name;
        $data['send_num']       = $send_num;
        $data['send_length']    = mb_strlen($Content);
        $data['free_trial']     = 1;
        $data['task_no']        = 'mar' . date('ymdHis') . substr(uniqid('', true), 15, 8);
        Db::startTrans();
        try {
            DbAdministrator::modifyBalance($userEquities['id'], $send_num, 'dec');
            $bId = DbAdministrator::addUserSendCodeTask($data); //添加后的商品id
            Db::commit();
            return ['code' => '200', 'task_no' => $data['task_no']];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3009'];
        }

    }

    public function readFileContent($filename) {
        $filename = filtraImage(Config::get('qiniu.exceldomain'), $filename);
        $logfile  = DbImage::getLogFile($filename); //判断时候有未完成的图片
        if (empty($logfile)) { //图片不存在
            return ['code' => '3002']; //图片没有上传过
        }
        $file = Config::get('qiniu.exceldomain') . '/' . $filename;
        ini_set('memory_limit', '3072M');

    }

    public function marketingReceive($appid,$appkey,$page,$pagenum){
        $user = DbUser::getUserOne(['appid' => $appid], 'id,appkey,user_type,user_status,reservation_service,free_trial', true);
        if (empty($user)) {
            return ['code' => '3000'];
        }
        if ($appkey != $user['appkey']) {
            return ['code' => '3000'];
        }
        $offset = ($page -1) * $pagenum;
        $result =  DbAdministrator::getUserSendTaskLog(['uid' => $user['id']], 'task_no,status_message,mobile,send_time', $row = false, '', $offset.','.$pagenum);
        foreach ($result as $key => $value) {
            $result[$key]['sendtime'] = date("Y-m-d H:i:s",$value['send_time']);
            unset($result[$key]['send_time']);
        }
        $total =  DbAdministrator::countUserSendTaskLog(['uid' => $user['id']]);
        return ['code' => '200', 'data' => $result];
    }

    public function businessReceive($appid,$appkey,$page,$pagenum){
        $user = DbUser::getUserOne(['appid' => $appid], 'id,appkey,user_type,user_status,reservation_service,free_trial', true);
        if (empty($user)) {
            return ['code' => '3000'];
        }
        if ($appkey != $user['appkey']) {
            return ['code' => '3000'];
        }
        $offset = ($page -1) * $pagenum;
        $result =  DbAdministrator::getUserSendCodeTaskLog(['uid' => $user['id']], 'task_no,status_message,mobile,send_time', $row = false, '', $offset.','.$pagenum);
        foreach ($result as $key => $value) {
            $result[$key]['sendtime'] = date("Y-m-d H:i:s",$value['send_time']);
            unset($result[$key]['send_time']);
        }
        $total =  DbAdministrator::countUserSendCodeTaskLog(['uid' => $user['id']]);
        return ['code' => '200', 'data' => $result];
    }

    public function balanceEnquiry($appid,$appkey){
        $user = DbUser::getUserOne(['appid' => $appid], 'id,appkey,user_type,user_status,reservation_service,free_trial', true);
        if (empty($user)) {
            return ['code' => '3000'];
        }
        if ($appkey != $user['appkey']) {
            return ['code' => '3000'];
        }
        $user_equities = DbAdministrator::getUserEquities(['uid' => $user['id']],'*',false);
        if (empty($user_equities)) {
            return ['code' => '200', 'userEquities' => []];;
        }
        foreach ($user_equities as $key => $equitise) {
            $user_equities[$key]['business_name'] = DbAdministrator::getBusiness(['id' => $equitise['business_id']],'title',true)['title'];
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
}
