<?php

namespace app\console\com;

use app\console\Pzlife;
use cache\Phpredis;
use Config;
use Env;
use Exception;
use think\Db;

class CmppNorm extends Pzlife
{

    // protected $redis;

    private function clientSocketInit()
    {
        $this->redis = Phpredis::getConn();
        //        $this->connect = Db::connect(Config::get('database.db_config'));
    }
    //融合移动行业
    public function content($content)
    {
        /* return [
            'host'          => "39.98.238.240", //服务商ip
            'port'          => "7890", //短连接端口号   17890长连接端口号
            'Source_Addr'   => "189273", //企业id  企业代码
            'Shared_secret' => '783328', //网关登录密码
            'Service_Id'    => "189273", //业务代码
            'template_id'   => "", //模板id
            'Dest_Id'       => "", //短信接入码 短信端口号 服务代码
            'Sequence_Id'   => 1,
            'SP_ID'         => "",
            'master_num'    => 160,
        ]; */
        if ($content == 'test') { //本机测试
            return [
                'channel_host'          => "127.0.0.1", //服务商ip
                'channel_port'          => "7890", //短连接端口号   17890长连接端口号
                'channel_source_addr'   => "C48515", //企业id  企业代码
                'channel_shared_secret' => 'c6S2ENJj', //网关登录密码
                'channel_service_id'    => "C48515",
                'channel_dest_id'       => "10694406674719", //短信接入码 短信端口号
                'Sequence_Id'   => 1,
                'SP_ID'         => "",
                'bin_ip'        => ["127.0.0.1", "47.103.200.251"], //客户端绑定IP
                'free_trial'    => 2,
                'channel_flow_velocity'    => 300,
                'uid'           => 1,
                'title' => '本地测试样例'
            ];
        } else {
            $channel = Db::query("SELECT * FROM yx_sms_sending_channel WHERE `id` = " . $content . " AND channel_type = 2");
            if (empty($channel)) {
                return false;
            }
            return $channel[0];
        }
    }

    public function Send($content)
    {
        // $this->clientSocketInit();
        $contdata = $this->content($content);
        // print_r($contdata);die;
        if (empty($contdata)) {
            exit("CHANNEL IS NOT SET !");
        }
        $redis = Phpredis::getConn();
        date_default_timezone_set('PRC');
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        // $content                    = 73;
        $redisMessageCodeSend       = 'index:meassage:code:send:' . $content; //验证码发送任务rediskey
        $redisMessageCodeSequenceId = 'index:meassage:code:sequence:id:' . $content; //行业通知SequenceId
        $redisMessageCodeMsgId      = 'index:meassage:code:msg:id:' . $content; //行业通知SequenceId
        // $redisMessageCodeDeliver    = 'index:meassage:code:deliver:' . $content; //行业通知MsgId
        $redisMessageCodeDeliver = 'index:meassage:code:new:deliver:' . $content; //行业通知MsgId
        $redisMessageUnKownDeliver = 'index:meassage:code:unknow:deliver:' . $content; //行业通知MsgId
        $redisMessageUpRiver       = 'index:message:code:upriver:' . $content; //上行队列
        /*   $send = $redis->rPush($redisMessageCodeSend, json_encode([
            'mobile'      => '15271120197',
            'mar_task_id' => '',
            // 'content'     => '感谢您对于CellCare的信赖和支持，为了给您带来更好的服务体验，特邀您针对本次服务进行评价https://www.wenjuan.com/s/6rqIZz/ ，请您在24小时内提交此问卷，谢谢配合。期待您的反馈！如需帮助，敬请致电400-8206-142【美丽田园】',
            'content'     => '您的验证码是：8791【美丽田园】',

        ])); */

        /*  $send = $redis->rPush($redisMessageCodeSend, json_encode([
            'mobile'      => '15201926171',
            'mar_task_id' => '',
            'content'     => '【施华洛世奇】亲爱的会员，感谢您一路以来的支持！您已获得2020年会员周年礼券，购买正价商品满1999元即可获得闪耀玫瑰金色简约吊坠一条，请于2020年10月19日前使用。可前往“施华洛世奇会员中心”小程序查看该券。详询4006901078。 回TD退订',
            // 'content'     => '【长阳广电】尊敬的用户，您的有线宽带电视即将到期，我们可为您线上办理各项电视业务，如有需要，可致电5321383，我们将竭诚为您服务。',
        ])); */
        /*  $send = $redis->rPush($redisMessageCodeSend, json_encode([
            'mobile'      => '15201926171',
            'mar_task_id' => '',
            'content'     => '【丝芙兰】1张9折券已飞奔向您！亲爱的于思佳会员，您所获赠的九折券自2020-09-01起生效，有效期截止2021-03-01，请在有效期间内前往丝芙兰官网sephora.cn、App、小程序或门店选购。(在官网购物时需与官网账号绑定。累积消费积分1500分或四次不同日消费即自动兑换1张九折劵)/回T退订',
            // 'content'     => '【长阳广电】尊敬的用户，您的有线宽带电视即将到期，我们可为您线上办理各项电视业务，如有需要，可致电5321383，我们将竭诚为您服务。',
        ])); */
        $socket   = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $log_path = realpath("") . "/error/" . $content . ".log";
        $myfile = fopen($log_path, 'a+');
        fwrite($myfile, date('Y-m-d H:i:s', time()) . "\n");
        fwrite($myfile, " Begin" . "\n");
        fclose($myfile);
        
        // $content = 0;

        // // print_r($contdata);die;
        $host                 = $contdata['channel_host']; //服务商ip
        $port                 = $contdata['channel_port']; //短连接端口号   17890长连接端口号
        $Source_Addr          = $contdata['channel_source_addr']; //企业id  企业代码
        $Shared_secret        = $contdata['channel_shared_secret']; //网关登录密码
        $Service_Id           = $contdata['channel_service_id'];
        $Dest_Id              = $contdata['channel_dest_id']; //短信接入码 短信端口号
        $Sequence_Id          = 1;
        // $SP_ID                = $contdata['SP_ID'];
        $master_num           = isset($contdata['channel_flow_velocity']) ? $contdata['channel_flow_velocity']: 300; //通道最大提交量
        $security_coefficient = 1; //通道饱和系数
        $security_master      = $master_num * $security_coefficient;
        $miao = 1000000;
        // echo $miao- $miao * 0.0012;die;
        $sleep_time = ceil($miao / $security_master);
        // echo $sleep_time;die;
        $log_path = realpath("") . "/error/" . $content . ".log";
        $myfile = fopen($log_path, 'a+');
        fwrite($myfile, date('Y-m-d H:i:s', time()) . "\n");
        fwrite($myfile, " host:" . $host . " port:" . $port . "\n");
        fclose($myfile);

        if (socket_connect($socket, $host, $port) == false) {
            // echo 'connect fail massege:' . socket_strerror(socket_last_error());
        } else {
            socket_set_nonblock($socket); //设置非阻塞模式
            $pos          = 0;
            $i           = 1;
            $Sequence_Id = 1;
            //先进行连接验证
            date_default_timezone_set('PRC');
            $time                = 0;
            $Version             = 0x20; //CMPP版本 0x20 2.0版本 0x30 3.0版本
            $Timestamp           = date('mdHis');
            $AuthenticatorSource = md5($Source_Addr . pack("a9", "") . $Shared_secret . $Timestamp, true);
            $bodyData   = pack("a6a16CN", $Source_Addr, $AuthenticatorSource, $Version, $Timestamp);
            $Command_Id = 0x00000001;
            $Total_Length = strlen($bodyData) + 12;
            $headData     = pack("NNN", $Total_Length, $Command_Id, $Sequence_Id);
            // ;
            if (socket_write($socket, $headData . $bodyData, $Total_Length) == false) {
                // echo 'write_verify fail massege:' . socket_strerror(socket_last_error());
            } else {
                sleep(1);
                $verify_status = 5; //默认失败
                // $headData = socket_read($socket, 12);
                // echo $Sequence_Id . "\n";
                // echo "认证连接中..." . "\n";
                $headData = socket_read($socket, 12);
                if ($headData != false) {
                    // echo "连接成功..." . "\n";
                    $head = unpack("NTotal_Length/NCommand_Id/NSequence_Id", $headData);
                    $bodyData = socket_read($socket, $head['Total_Length'] - 12);
                    if ($head['Command_Id'] == 0x80000001) {
                        $body = unpack("CStatus/a16AuthenticatorSource/CVersion", $bodyData);
                        $verify_status = $body['Status'];
                        switch ($body['Status']) {
                            case 0:
                                break;
                            case 1:
                                $error_msg = "消息结构错";
                                break;
                            case 2:
                                $error_msg = "非法源地址";
                                break;
                            case 3:
                                $error_msg = "认证错误";
                                break;
                            case 4:
                                $error_msg = "版本错误";
                                break;
                            default:
                                $error_msg = "其他错误";
                                break;
                        }
                        //通道断口处理
                        if ($body['Status'] != 0) {
                            exit("其他错误，错误代码：【" . $body['Status'] . "】\n");
                        }
                    } else if ($head['Command_Id'] == 0x80000004) {
                        $body = unpack("N2Msg_Id/CResult", $bodyData);
                        // // print_r($body);
                        $sequence = $redis->hget($redisMessageCodeSequenceId, $head['Sequence_Id']);
                        if ($sequence) {
                            $sequence           = json_decode($sequence, true);
                            $msgid              = $body['Msg_Id1'] . $body['Msg_Id2'];
                            $sequence['Msg_Id'] = $msgid;
                            $redis->hdel($redisMessageCodeSequenceId, $head['Sequence_Id']);
                            $redis->hset($redisMessageCodeMsgId, $body['Msg_Id1'] . $body['Msg_Id2'], json_encode($sequence));
                        }

                        switch ($body['Result']) {
                            case 0:
                                // echo "发送成功" . "\n";
                                break;
                            case 1:
                                // echo "消息结构错" . "\n";
                                $error_msg = "消息结构错";
                                break;
                            case 2:
                                // echo "命令字错" . "\n";
                                $error_msg = "命令字错";
                                break;
                            case 3:
                                // echo "消息序号重复" . "\n";
                                $error_msg = "消息序号重复";
                                break;
                            case 4:
                                // echo "消息长度错" . "\n";
                                $error_msg = "消息长度错";
                                break;
                            case 5:
                                // echo "资费代码错" . "\n";
                                $error_msg = "资费代码错";
                                break;
                            case 6:
                                // echo "超过最大信息长" . "\n";
                                $error_msg = "超过最大信息长";
                                break;
                            case 7:
                                // echo "业务代码错" . "\n";
                                $error_msg = "业务代码错";
                                break;
                            case 8:
                                // echo "流量控制错" . "\n";
                                $error_msg = "业务代码错";
                                break;
                            default:
                                // echo "其他错误" . "\n";
                                $error_msg = "其他错误";
                                break;
                        }
                        if ($body['Result'] != 0) { //消息发送失败
                            // echo "发送失败" . "\n";
                            $error_msg = "其他错误，错误代码：【" . $body['Result'] . "】\n";
                        } else {
                        }
                    } else if ($head['Command_Id'] == 0x00000005) { //收到短信下发应答,需回复应答，应答Command_Id = 0x80000005
                        $Result = 0;
                        $contentlen = $head['Total_Length'] - 65 - 12;
                        $body        = unpack("N2Msg_Id/a21Dest_Id/a10Service_Id/CTP_pid/CTP_udhi/CMsg_Fmt/a21Src_terminal_Id/CRegistered_Delivery/CMsg_Length/a" . $contentlen . "Msg_Content/", $bodyData);
                        $Registered_Delivery = trim($body['Registered_Delivery']);
                        // print_r($body);
                        $develop_len = strlen($Dest_Id);
                        $receive_develop_no = mb_substr(trim($body['Dest_Id']), $develop_len);
                        if ($Registered_Delivery == 0) { //上行
                            // if ($mesage) { //

                            // }else{

                            // }
                            if ($body['Msg_Fmt'] == 15) {
                                $body['Msg_Content'] = mb_convert_encoding($body['Msg_Content'], 'UTF-8', 'GBK');
                            } elseif ($body['Msg_Fmt'] == 0) { //ASCII进制码
                                $encode = mb_detect_encoding($body['Msg_Content'], array('ASCII', 'GB2312', 'GBK', 'UTF-8'));
                                if ($encode != 'UTF-8') {
                                    $body['Msg_Content'] = mb_convert_encoding($body['Msg_Content'], 'UTF-8', $encode);
                                }
                            } elseif ($body['Msg_Fmt'] == 8) { //USC2
                                $body['Msg_Content'] = mb_convert_encoding($body['Msg_Content'], 'UTF-8', 'UCS-2');
                            }
                            $up_message = [];
                            $up_message = [
                                'mobile' => trim($body['Src_terminal_Id']),
                                'message_info' => trim($body['Msg_Content']),
                                'develop_code' => $receive_develop_no,
                            ];
                            $redis->rpush($redisMessageUpRiver, json_encode($up_message));
                        } elseif ($Registered_Delivery == 1) { //回执报告

                            $stalen = $body['Msg_Length'] - 20 - 8 - 21 - 4;
                            if (strlen($body['Msg_Content']) < 60) {
                                $Msg_Content = unpack("N2Msg_Id/a" . $stalen . "Stat", $body['Msg_Content']);
                            } else {
                                $Msg_Content = unpack("N2Msg_Id/a" . $stalen . "Stat/a10Submit_time/a10Done_time/a21Dest_terminal_Id/NSMSC_sequence", $body['Msg_Content']);
                            }
                            // print_r($Msg_Content);
                            $mesage = $redis->hget($redisMessageCodeMsgId, $Msg_Content['Msg_Id1'] . $Msg_Content['Msg_Id2']);
                            if ($mesage) {
                                $redis->hdel($redisMessageCodeMsgId, $body['Msg_Id1'] . $body['Msg_Id2']);
                                // $redis->rpush($redisMessageCodeDeliver,$mesage.":".$Msg_Content['Stat']);
                                $mesage                = json_decode($mesage, true);
                                $mesage['Stat']        = $Msg_Content['Stat'];
                                // $mesage['Msg_Id']        = $Msg_Content['Msg_Id1'] . $Msg_Content['Msg_Id2'];
                                $mesage['Submit_time'] = isset($Msg_Content['Submit_time']) ? $Msg_Content['Submit_time'] : date('ymdHis', $mesage['my_submit_time']);
                                $mesage['Done_time']   = isset($Msg_Content['Done_time']) ? $Msg_Content['Done_time'] : date('ymdHis', time());
                                $mesage['receive_time'] = time(); //回执时间戳
                                $redis->rpush($redisMessageCodeDeliver, json_encode($mesage));
                            } else { //不在记录中的回执存入缓存，
                                $mesage['Stat']        = isset($Msg_Content['Stat']) ? $Msg_Content['Stat'] : 'UNKNOWN';
                                $mesage['Submit_time'] = trim(isset($Msg_Content['Submit_time']) ? $Msg_Content['Submit_time'] : date('ymdHis', time()));
                                $mesage['Done_time']   = trim(isset($Msg_Content['Done_time']) ? $Msg_Content['Done_time'] : date('ymdHis', time()));
                                // $mesage['mobile']      = $body['Dest_Id '];//手机号
                                $mesage['mobile']   = isset($Msg_Content['Dest_terminal_Id']) ? $Msg_Content['Dest_terminal_Id'] : '';
                                $mesage['receive_time'] = time(); //回执时间戳
                                $mesage['Msg_Id']   = $Msg_Content['Msg_Id1'] . $Msg_Content['Msg_Id2'];
                                $redis->rPush($redisMessageUnKownDeliver, json_encode($mesage));
                            }
                        }
                        // print_r($mesage);
                        $callback_Command_Id = 0x80000005;

                        $new_body         = pack("N", $body['Msg_Id1']) . pack("N", $body['Msg_Id2']) . pack("C", $Result);
                        $new_Total_Length = strlen($new_body) + 12;
                        $new_headData     = pack("NNN", $new_Total_Length, $callback_Command_Id, $head['Sequence_Id']);
                        socket_write($socket, $new_headData . $new_body, $new_Total_Length);
                        usleep(250);
                    } else if ($head['Command_Id'] == 0x00000008) {
                        // echo "心跳维持中" . "\n"; //激活测试,无消息体结构
                        $Command_Id  = 0x80000008; //保持连接
                        $Total_Length = 12;
                        $headData     = pack("NNN", $Total_Length, $Command_Id, $Sequence_Id);
                        socket_write($socket, $headData, $Total_Length);
                        $receive = 2;
                        
                    } else if ($head['Command_Id'] == 0x80000008) {
                        // echo "激活测试应答" . "\n"; //激活测试,无消息体结构
                    } else if ($head['Command_Id'] == 0x00000002) {
                        // echo "未声明head['Command_Id']:" . $head['Command_Id'];
                        $Command_Id  = 0x80000002; //关闭连接
                        $Total_Length = 12;
                        $headData     = pack("NNN", $Total_Length, $Command_Id, $Sequence_Id);
                        socket_write($socket, $headData, $Total_Length);
                        socket_close($socket);
                        $this->writeToRobot($content, '通道方关闭当前链接，通道关闭', $contdata['title']);
                        exit;
                        $receive = 2;
                    }
                }
                if ($verify_status == 0) { //验证成功并且所有信息已读完可进行发送操作
                    while (true) {
                        echo microtime(true);
                        echo "\n";
                        // echo $Sequence_Id . "\n";
                        try {
                            $receive = 1;
                            //先接收
                            while (true) {
                                $headData = socket_read($socket, 12);
                                /* if (strlen($headData) < 12) {
                                    continue;
                                } */
                                if ($headData != false) {
                                    $head = unpack("NTotal_Length/NCommand_Id/NSequence_Id", $headData);
                                    $bodyData = socket_read($socket, $head['Total_Length'] - 12);
                                    if ($head['Command_Id'] == 0x80000001) {
                                        $body = unpack("CStatus/a16AuthenticatorSource/CVersion", $bodyData);
                                        $verify_status = $body['Status'];
                                        switch ($body['Status']) {
                                            case 0:
                                                break;
                                            case 1:
                                                $error_msg = "消息结构错";
                                                break;
                                            case 2:
                                                $error_msg = "非法源地址";
                                                break;
                                            case 3:
                                                $error_msg = "认证错误";
                                                break;
                                            case 4:
                                                $error_msg = "版本错误";
                                                break;
                                            default:
                                                $error_msg = "其他错误";
                                                break;
                                        }
                                        //通道断口处理
                                        if ($body['Status'] != 0) {
                                            exit($error_msg);
                                        }
                                    } else if ($head['Command_Id'] == 0x80000004) {
                                        $body = unpack("N2Msg_Id/CResult", $bodyData);
                                        // print_r($body);
                                        $sequence = $redis->hget($redisMessageCodeSequenceId, $head['Sequence_Id']);
                                        if ($sequence) {
                                            $sequence           = json_decode($sequence, true);
                                            $msgid              = $body['Msg_Id1'] . $body['Msg_Id2'];
                                            $sequence['Msg_Id'] = $msgid;
                                            $redis->hdel($redisMessageCodeSequenceId, $head['Sequence_Id']);
                                            $redis->hset($redisMessageCodeMsgId, $body['Msg_Id1'] . $body['Msg_Id2'], json_encode($sequence));
                                        }

                                        switch ($body['Result']) {
                                            case 0:
                                                // echo "发送成功" . "\n";
                                                break;
                                            case 1:
                                                // echo "消息结构错" . "\n";
                                                $error_msg = "消息结构错";
                                                break;
                                            case 2:
                                                // echo "命令字错" . "\n";
                                                $error_msg = "命令字错";
                                                break;
                                            case 3:
                                                // echo "消息序号重复" . "\n";
                                                $error_msg = "消息序号重复";
                                                break;
                                            case 4:
                                                // echo "消息长度错" . "\n";
                                                $error_msg = "消息长度错";
                                                break;
                                            case 5:
                                                // echo "资费代码错" . "\n";
                                                $error_msg = "资费代码错";
                                                break;
                                            case 6:
                                                // echo "超过最大信息长" . "\n";
                                                $error_msg = "超过最大信息长";
                                                break;
                                            case 7:
                                                // echo "业务代码错" . "\n";
                                                $error_msg = "业务代码错";
                                                break;
                                            case 8:
                                                // echo "流量控制错" . "\n";
                                                $error_msg = "业务代码错";
                                                break;
                                            default:
                                                // echo "其他错误" . "\n";
                                                $error_msg = "其他错误";
                                                break;
                                        }
                                        if ($body['Result'] != 0) { //消息发送失败
                                            // echo "发送失败" . "\n";
                                            $error_msg = "其他错误";
                                        } else {
                                        }
                                    } else if ($head['Command_Id'] == 0x00000005) { //收到短信下发应答,需回复应答，应答Command_Id = 0x80000005
                                        $Result = 0;
                                        $contentlen = $head['Total_Length'] - 65 - 12;
                                        if (strlen($bodyData) < $head['Total_Length'] - 12) {
                                            $this->writeToRobot($content, '回执获取到长度错误消息体：' . $headData . $bodyData, $contdata['title']);
                                            continue;
                                        }
                                        $body        = unpack("N2Msg_Id/a21Dest_Id/a10Service_Id/CTP_pid/CTP_udhi/CMsg_Fmt/a21Src_terminal_Id/CRegistered_Delivery/CMsg_Length/a" . $contentlen . "Msg_Content/", $bodyData);
                                        $Registered_Delivery = trim($body['Registered_Delivery']);
                                        // print_r($body);
                                        $develop_len = strlen($Dest_Id);
                                        $receive_develop_no = mb_substr(trim($body['Dest_Id']), $develop_len);
                                        // // echo "拓展码:".$receive_develop_no;
                                        // // echo "\n";  
                                        if ($Registered_Delivery == 0) { //上行
                                            if ($body['Msg_Fmt'] == 15) {
                                                $body['Msg_Content'] = mb_convert_encoding($body['Msg_Content'], 'UTF-8', 'GBK');
                                            } elseif ($body['Msg_Fmt'] == 0) { //ASCII进制码
                                                $encode = mb_detect_encoding($body['Msg_Content'], array('ASCII', 'GB2312', 'GBK', 'UTF-8'));
                                                if ($encode != 'UTF-8') {
                                                    $body['Msg_Content'] = mb_convert_encoding($body['Msg_Content'], 'UTF-8', $encode);
                                                }
                                            } elseif ($body['Msg_Fmt'] == 8) { //USC2
                                                $body['Msg_Content'] = mb_convert_encoding($body['Msg_Content'], 'UTF-8', 'UCS-2');
                                            }
                                            $up_message = [];
                                            $up_message = [
                                                'mobile'       => trim($body['Src_terminal_Id']),
                                                'message_info' => trim($body['Msg_Content']),
                                                'develop_code' => $receive_develop_no,
                                            ];
                                            $redis->rpush($redisMessageUpRiver, json_encode($up_message));
                                        } elseif ($Registered_Delivery == 1) { //回执报告

                                            $stalen = $body['Msg_Length'] - 20 - 8 - 21 - 4;
                                            if (strlen($body['Msg_Content']) < 60) {
                                                $Msg_Content = unpack("N2Msg_Id/a" . $stalen . "Stat", $body['Msg_Content']);
                                                $Result = 1;
                                            } else {
                                                $Msg_Content = unpack("N2Msg_Id/a" . $stalen . "Stat/a10Submit_time/a10Done_time/a21Dest_terminal_Id/NSMSC_sequence", $body['Msg_Content']);
                                            }
                                            // print_r($Msg_Content);
                                            $message_id = '';
                                            $message_id = strval($Msg_Content['Msg_Id1']) . strval($Msg_Content['Msg_Id2']);
                                            // $mesage = $redis->hget($redisMessageCodeMsgId, $Msg_Content['Msg_Id1'] . $Msg_Content['Msg_Id2']);
                                            $mesage = $redis->hget($redisMessageCodeMsgId, $message_id);
                                            if ($mesage) {
                                                // $redis->rpush($redisMessageCodeDeliver,$mesage.":".$Msg_Content['Stat']);
                                                $mesage                = json_decode($mesage, true);
                                                $mesage['Stat']        = $Msg_Content['Stat'];
                                                // $mesage['Msg_Id']        = $Msg_Content['Msg_Id1'] . $Msg_Content['Msg_Id2'];
                                                $mesage['Submit_time'] = isset($Msg_Content['Submit_time']) ? $Msg_Content['Submit_time'] : date('ymdHis', $mesage['my_submit_time']);
                                                $mesage['Done_time']   = isset($Msg_Content['Done_time']) ? $Msg_Content['Done_time'] : date('ymdHis', time());
                                                $mesage['receive_time'] = time(); //回执时间戳
                                                $mesage['develop_no'] = $receive_develop_no; //回执时间戳
                                                $redis->rpush($redisMessageCodeDeliver, json_encode($mesage));
                                                // $redis->hdel($redisMessageCodeMsgId, $body['Msg_Id1'] . $body['Msg_Id2']);
                                                $redis->hdel($redisMessageCodeMsgId, $message_id);
                                            } else { //不在记录中的回执存入缓存，
                                                $Result = 9;
                                                $mesage['Stat']        = isset($Msg_Content['Stat']) ? $Msg_Content['Stat'] : 'UNKNOWN';
                                                $mesage['Submit_time'] = trim(isset($Msg_Content['Submit_time']) ? $Msg_Content['Submit_time'] : date('ymdHis', time()));
                                                $mesage['Done_time']   = trim(isset($Msg_Content['Done_time']) ? $Msg_Content['Done_time'] : date('ymdHis', time()));
                                                // $mesage['mobile']      = $body['Dest_Id '];//手机号
                                                $mesage['mobile']   = isset($Msg_Content['Dest_terminal_Id']) ? $Msg_Content['Dest_terminal_Id'] : '';
                                                $mesage['receive_time'] = time(); //回执时间戳
                                                $mesage['Msg_Id']   = $Msg_Content['Msg_Id1'] . $Msg_Content['Msg_Id2'];
                                                // $redis->rPush($redisMessageUnKownDeliver, json_encode($mesage));
                                            }
                                        }
                                        // print_r($mesage);
                                        $callback_Command_Id = 0x80000005;

                                        $new_body         = pack("N", $body['Msg_Id1']) . pack("N", $body['Msg_Id2']) . pack("C", $Result);
                                        $new_Total_Length = strlen($new_body) + 12;
                                        $new_headData     = pack("NNN", $new_Total_Length, $callback_Command_Id, $head['Sequence_Id']);
                                        socket_write($socket, $new_headData . $new_body, $new_Total_Length);
                                        $receive = 2;
                                        usleep(50);
                                    } else if ($head['Command_Id'] == 0x00000008) {
                                        // echo "心跳维持中" . "\n"; //激活测试,无消息体结构
                                        $Command_Id  = 0x80000008; //保持连接
                                        $Total_Length = 12;
                                        $headData     = pack("NNN", $Total_Length, $Command_Id, $Sequence_Id);
                                        socket_write($socket, $headData, $Total_Length);
                                        $receive = 2;
                                        
                                    } else if ($head['Command_Id'] == 0x80000008) {
                                        // echo "激活测试应答" . "\n"; //激活测试,无消息体结构
                                    } else if ($head['Command_Id'] == 0x00000002) {
                                        // echo "未声明head['Command_Id']:" . $head['Command_Id'];
                                        $Command_Id  = 0x80000002; //关闭连接
                                        $Total_Length = 12;
                                        $headData     = pack("NNN", $Total_Length, $Command_Id, $Sequence_Id);
                                        socket_write($socket, $headData, $Total_Length);
                                        socket_close($socket);
                                        $this->writeToRobot($content, '通道方关闭当前链接，通道关闭', $contdata['title']);
                                        exit;
                                        $receive = 2;
                                    }
                                } else {
                                    break;
                                }
                            }
                            //在发送

                            $send = $redis->lPop($redisMessageCodeSend);
                            if (!empty($send)) { //正式使用从缓存中读取数据并且有待发送数据

                                $send_status = 1;
                                $send_data = [];
                                $send_data = json_decode($send, true);
                                // $mobile = $senddata['mobile_content'];
                                $mobile   = $send_data['mobile'];
                                $txt_head = 6;
                                $txt_len  = 140;
                                $max_len  = $txt_len - $txt_head;
                                $code = $send_data['content']; //带签名
                                $uer_num    = 1; //本批接受信息的用户数量（一般小于100个用户，不同通道承载能力不同）
                                $timestring = time();
                                // echo "发送时间：" . date("Y-m-d H:i:s", time()) . "\n";
                                $num1 = substr($timestring, 0, 8);
                                $num2 = substr($timestring, 8) . $this->combination($i);
                                // $code = mb_convert_encoding($code, 'GBK', 'UTF-8');
                                $code = mb_convert_encoding($code, 'UCS-2', 'UTF-8');
                                // iconv("UTF-8","gbk",$code);
                                // $redis->rPush($redisMessageCodeSend, json_encode($send_data));
                                // // print_r($code);die;
                                if (strlen($code) > 140) {
                                    $num_messages = ceil(strlen($code) / $max_len);
                                    for ($j = 0; $j < $num_messages; $j++) {
                                        $bodyData = pack("N", $num1) . pack("N", $num2);
                                        $bodyData .= pack('C', $num_messages);
                                        $bodyData .= pack('C', $j + 1);
                                        $bodyData .= pack('C', 1);
                                        $bodyData .= pack('C', '');
                                        $bodyData .= pack("a10", $Service_Id);
                                        $bodyData .= pack('C', '');
                                        $bodyData .= pack("a21", $mobile);
                                        $bodyData .= pack("C", 0);
                                        $bodyData .= pack("C", 1);
                                        // $bodyData.= pack("C", 15); 
                                        $bodyData .= pack("C", 8);
                                        $bodyData .= pack("a6", $Source_Addr);
                                        $bodyData .= pack("a2", 02);
                                        $bodyData .= pack("a6", '');
                                        $bodyData .= pack("a17", '');
                                        $bodyData .= pack("a17", '');
                                        if (isset($send_data['develop_code'])) {
                                            $bodyData .= pack("a21", $Dest_Id . $send_data['develop_code']);
                                        } else {
                                            $bodyData .= pack("a21", $Dest_Id);
                                        }
                                        $bodyData .= pack("C", $uer_num);
                                        $p_n      = 21 * $uer_num;
                                        $bodyData .= pack("a" . $p_n, $mobile);
                                        $udh     = pack("cccccc", 5, 0, 3, $pos, $num_messages, $j + 1);
                                        $newcode = $udh . substr($code, $j * $max_len, $max_len);
                                        $len     = strlen($newcode);
                                        $bodyData .= pack("C", $len);
                                        $bodyData .= pack("a" . $len, $newcode);
                                        $bodyData .= pack("a8", '');
                                        $Command_Id = 0x00000004; // 短信发送
                                        // print_r($udh);
                                        $Total_Length = strlen($bodyData) + 12;
                                        $headData     = pack("NNN", $Total_Length, $Command_Id, $Sequence_Id);
                                        $send_data['my_submit_time'] = time(); //发送时间戳
                                        $redis->hset($redisMessageCodeSequenceId, $Sequence_Id, json_encode($send_data));
                                        // usleep(300);
                                        socket_write($socket, $headData . $bodyData, $Total_Length);
                                        $send_status = 2;
                                        ++$i;
                                        ++$Sequence_Id;
                                        if ($Sequence_Id > 65536) {
                                            $Sequence_Id = 1;
                                        }
                                    }
                                    if ($i > $security_master) {
                                        $i    = 0;
                                    }
                                    $pos++;
                                    if ($pos > 100) {
                                        $pos = 0;
                                    }
                                    // usleep(2500);
                                    continue;
                                } else { //单条短信

                                    $bodyData = pack("N", $num1) . pack("N", $num2);
                                    $bodyData .= pack('C', 1);
                                    $bodyData .= pack('C', 1);
                                    $bodyData .= pack('C', 1);
                                    $bodyData .= pack('C', '');
                                    $bodyData .= pack("a10", $Service_Id);
                                    $bodyData .= pack('C', '');
                                    $bodyData .= pack("a21", $mobile);
                                    $bodyData .= pack("C", 0);
                                    $bodyData .= pack("C", 0);
                                    // $bodyData.= pack("C", 15);
                                    $bodyData .= pack("C", 8);
                                    $bodyData .= pack("a6", $Source_Addr);
                                    $bodyData .= pack("a2", 02);
                                    $bodyData .= pack("a6", '');
                                    $bodyData .= pack("a17", '');
                                    $bodyData .= pack("a17", '');
                                    if (isset($send_data['develop_code'])) {
                                        $bodyData .= pack("a21", $Dest_Id . $send_data['develop_code']);
                                    } else {
                                        $bodyData .= pack("a21", $Dest_Id);
                                    }
                                    $bodyData .= pack("C", $uer_num);
                                    $p_n      = 21 * $uer_num;
                                    $bodyData .= pack("a" . $p_n, $mobile);
                                    $len      = strlen($code);
                                    $bodyData .= pack("C", $len);
                                    $bodyData .= pack("a" . $len, $code);
                                    $bodyData .= pack("a8", '');
                                    $Command_Id = 0x00000004; // 短信发送
                                    $time = 0;
                                    if ($i > $security_master) {
                                        $time = 1;
                                        $i    = 0;
                                    }
                                    $send_data['my_submit_time'] = time(); //发送时间戳
                                    $redis->hset($redisMessageCodeSequenceId, $Sequence_Id, json_encode($send_data));
                                    $Total_Length = strlen($bodyData) + 12;
                                    $headData     = pack("NNN", $Total_Length, $Command_Id, $Sequence_Id);
                                    socket_write($socket, $headData . $bodyData, $Total_Length);

                                    $send_status = 2;
                                    // usleep(2500);
                                }
                                unset($send_status);
                            } else { //心跳
                                $Command_Id  = 0x00000008; //保持连接
                                $Total_Length = 12;
                                $headData     = pack("NNN", $Total_Length, $Command_Id, $Sequence_Id);
                                if ($receive != 2) {
                                    socket_write($socket, $headData, $Total_Length);
                                }
                                usleep(998600);
                            }

                            ++$i;
                            ++$Sequence_Id;
                            if ($Sequence_Id > 65536) {
                                $Sequence_Id = 1;
                            }
                        }
                        //捕获异常
                        catch (Exception $e) {
                            if (isset($send_status) && $send_status == 1) {
                                $redis->push($redisMessageCodeSend, $redisMessageCodeSend);
                                $redis->hset($redisMessageCodeSequenceId, $Sequence_Id);
                            }
                            socket_close($socket);

                            $log_path = realpath("") . "/error/" . $content . ".log";
                            $myfile = fopen($log_path, 'a+');
                            fwrite($myfile, date('Y-m-d H:i:s', time()) . "\n");
                            fwrite($myfile, $e . "\n");
                            fclose($myfile);
                            /*  $api = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=fa1c9682-f617-45f9-a6a3-6b65f671b457';
                            $check_data = [];
                            $check_data = [
                                'msgtype' => "text",
                                'text' => [
                                    "content" => "Hi，错误提醒机器人\n您有一条通道出现故障\n通道编号【".$content."】\n通道名称【".$contdata['title']."】",
                                ],
                            ];
                            $headers = [
                                'Content-Type:application/json'
                            ];
                            $audit_api =   $this->sendRequest2($api,'post',$check_data,$headers); */
                            $this->writeToRobot($content, $e, $contdata['title']);
                            exception($e);

                            //重新创建连接
                            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
                            if (socket_connect($socket, $host, $port) == false) {
                                $myfile = fopen($log_path, 'a+');
                                fwrite($myfile, date('Y-m-d H:i:s', time()) . "\n");
                                fwrite($myfile,  "通道延迟5秒后再次连接失败，请联系通道方检查原因\n");
                                fclose($myfile);
                                /*  $redis->rpush('index:meassage:code:send' . ":" . 1, json_encode([
                                    'mobile'      => 15201926171,
                                    'content'     => "【钰晰科技】通道编号[" . $content . "] 出现故障,连接服务商失败，请紧急处理解决或者切换！！！",
                                ])); //三体营销通道
                                $redis->rpush('index:meassage:code:send' . ":" . 24, json_encode([
                                    'mobile'      => 15201926171,
                                    'content'     => "【钰晰科技】通道编号[" . $content . "] 出现故障,连接服务商失败，请紧急处理解决或者切换！！！",
                                ])); //易信行业通道*/

                                exit();
                            } else {
                                $Version             = 0x20; //CMPP版本 0x20 2.0版本 0x30 3.0版本
                                $Timestamp           = date('mdHis');
                                $AuthenticatorSource = md5($Source_Addr . pack("a9", "") . $Shared_secret . $Timestamp, true);
                                $bodyData   = pack("a6a16CN", $Source_Addr, $AuthenticatorSource, $Version, $Timestamp);
                                $Command_Id = 0x00000001;
                                $Total_Length = strlen($bodyData) + 12;
                                $headData     = pack("NNN", $Total_Length, $Command_Id, $Sequence_Id);
                                // socket_write($socket, $headData . $bodyData, $Total_Length);
                                if (socket_write($socket, $headData . $bodyData, $Total_Length) == false) {
                                    // // echo 'write_verify fail massege:' . socket_strerror(socket_last_error());
                                    $myfile = fopen($log_path, 'a+');
                                    fwrite($myfile, date('Y-m-d H:i:s', time()) . "\n");
                                    fwrite($myfile,  "通道延迟5秒后写入socket失败，请联系通道方检查原因\n");
                                    fclose($myfile);
                                    /*  $redis->rpush('index:meassage:code:send' . ":" . 1, json_encode([
                                        'mobile'      => 15201926171,
                                        'content'     => "【钰晰科技】通道编号[" . $content . "] 出现故障,写入socket失败，请紧急处理解决或者切换！！！",
                                    ])); //三体营销通道
                                    $redis->rpush('index:meassage:code:send' . ":" . 24, json_encode([
                                        'mobile'      => 15201926171,
                                        'content'     => "【钰晰科技】通道编号[" . $content . "] 出现故障,写入socket失败，请紧急处理解决或者切换！！！",
                                    ])); //易信行业通道
                                    $redis->rpush('index:meassage:code:send' . ":" . 22, json_encode([
                                        'mobile'      => 15201926171,
                                        'content'     => "【钰晰科技】通道编号[" . $content . "] 出现故障,写入socket失败，请紧急处理解决或者切换！！！",
                                    ])); //易信行业通道 */
                                    exit();
                                }
                                ++$i;
                                ++$Sequence_Id;
                            }
                        }
                    }
                }
            }
        }
    }

    function writeToRobot($content, $error_data, $title)
    {
        $api = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=fa1c9682-f617-45f9-a6a3-6b65f671b457';
        // $api = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=693a91f6-7xxx-4bc4-97a0-0ec2sifa5aaa';
        $check_data = [];
        $check_data = [
            'msgtype' => "text",
            'text' => [
                "content" => "Hi，错误提醒机器人\n您有一条通道出现故障\n通道编号【" . $content . "】\n【错误信息】：" . $error_data . "\n通道名称【" . $title . "】",
            ],
        ];
        $headers = [
            'Content-Type:application/json'
        ];
        $this->sendRequest2($api, 'post', $check_data, $headers);
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

    //16进制转2进制
    function StrToBin($str)
    {
        //1.列出每个字符
        $arr = preg_split('/(?<!^)(?!$)/u', $str);
        //2.unpack字符
        foreach ($arr as &$v) {
            $temp = unpack('H*', $v);
            $v    = base_convert($temp[1], 16, 2);
            unset($temp);
        }

        return join('', $arr);
    }

    public function decodeString()
    {
        // // echo strlen("³½'¹ ");
        $timestring = time();
        $num1       = substr($timestring, 0, 8);
        $num2       = substr($timestring, 8) . $this->combination(rand(1, 240));
        // echo $num1;
        // echo "\n";
        // echo $num2;

        $a = pack("N", $num1) . pack("N", $num2);
        // echo $a . "\n";
        // print_r(unpack("N2Msg_Id", $a));

        die;
        $arr = unpack("N2Msg_Id/a7Stat/a10Submit_time/a10Done_time/", "³f󿾧©¬DELIVRD1911071650191107165515201926171AG");
    }

    /**
     * 6位数字补齐
     * @param string $pdu
     * @return string
     */
    function combination($num)
    {
        $num     = intval($num);
        $num     = strval($num);
        $new_num = '';
        switch (strlen($num)) {
            case 0:
                $new_num = "000000";
                break;
            case 1:
                $new_num = "00000" . $num;
                break;
            case 2:
                $new_num = "0000" . $num;
                break;
            case 3:
                $new_num = "000" . $num;
                break;
            case 4:
                $new_num = "00" . $num;
                break;
            case 5:
                $new_num = "0" . $num;
                break;
        }
        return $new_num;
    }

    /**
     * PDU数据包转化ASCII数字
     * @param string $pdu
     * @return string
     */
    public function pduord($pdu)
    {
        $ord_pdu = '';
        for ($i = 0; $i < strlen($pdu); $i++) {
            $ord_pdu .= sprintf("%02x", ord($pdu[$i])) . ' ';
        }

        if ($ord_pdu) {
            $ord_pdu = substr($ord_pdu, 0, -1);
        }

        return $ord_pdu;
    }

    /**
     * 将ascii码转为字符串
     * @param type $str 要解码的字符串
     * @param type $prefix 前缀，默认:&#
     * @return type
     */
    function decode($str, $prefix = "&#")
    {
        $str = str_replace($prefix, "", $str);
        $a   = explode(";", $str);
        $utf = '';
        foreach ($a as $dec) {
            if ($dec < 128) {
                $utf .= chr($dec);
            } else if ($dec < 2048) {
                $utf .= chr(192 + (($dec - ($dec % 64)) / 64));
                $utf .= chr(128 + ($dec % 64));
            } else {
                $utf .= chr(224 + (($dec - ($dec % 4096)) / 4096));
                $utf .= chr(128 + ((($dec % 4096) - ($dec % 64)) / 64));
                $utf .= chr(128 + ($dec % 64));
            }
        }
        return $utf;
    }

    public function getSendCodeTask()
    {
        $task = Db::query("SELECT * FROM yx_user_send_code_task WHERE `send_status` = 1 ORDER BY id ASC LIMIT 1");
        if ($task) {
            return $task[0];
        }
        return [];
    }

    private function getSendTask($id)
    {
        $getSendTaskSql = sprintf("select * from yx_user_send_task where delete_time=0 and id = %d", $id);
        // // print_r($getUserSql);die;
        $sendTask = Db::query($getSendTaskSql);
        if (!$sendTask) {
            return [];
        }
        return $sendTask[0];
    }

    private function getSendTaskLog($task_no, $mobile)
    {
        $getSendTaskSql = "select 'id' from yx_user_send_task_log where delete_time=0 and `task_no` = '" . $task_no . "' and `mobile` = '" . $mobile . "'";
        // // print_r($getUserSql);die;
        $sendTask = Db::query($getSendTaskSql);
        if (!$sendTask) {
            return [];
        }
        return $sendTask[0];
    }

    private function getSendTaskLogByMsgid($msgid)
    {
        $getSendTaskSql = "select 'id' from yx_user_send_task_log where delete_time=0 and `msgid` = '" . $msgid . "'";
        // // print_r($getUserSql);die;
        $sendTask = Db::query($getSendTaskSql);
        if (!$sendTask) {
            return [];
        }
        return $sendTask[0];
    }
}
