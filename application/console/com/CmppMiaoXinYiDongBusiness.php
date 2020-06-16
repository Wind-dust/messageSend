<?php

namespace app\console\com;

use app\console\Pzlife;
use cache\Phpredis;
use Config;
use Env;
use Exception;
use think\Db;

class CmppMiaoXinYiDongBusiness extends Pzlife
{

    // protected $redis;

    private function clientSocketInit()
    {
        $this->redis = Phpredis::getConn();
        //        $this->connect = Db::connect(Config::get('database.db_config'));
    }
    //秒信移动行业
    public function content($content)
    {
        // // print_r($content);die;
        // if ($content == 0) {
        //     return [
        //         // 'host'          => "47.103.200.251", //服务商ip
        //         'host'          => "127.0.0.1", //服务商ip
        //         'port'          => "7890", //短连接端口号   17890长连接端口号
        //         'Source_Addr'   => "101102", //企业id  企业代码
        //         'Shared_secret' => 'Jyy123456', //网关登录密码
        //         'Service_Id'    => "",
        //         'Dest_Id'       => "10692054963", //短信接入码 短信端口号
        //         'Sequence_Id'   => 1,
        //         'SP_ID'         => "",
        //         'bin_ip'        => ["221.228.217.57"], //客户端绑定IP
        //         'free_trial'    => 2,
        //         'master_num'    => 300,
        //         'uid'           => 45,
        //     ];
        // }
        return [
            'host'          => "120.133.40.140", //服务商ip
            'port'          => "7890", //短连接端口号   17890长连接端口号
            'Source_Addr'   => "1000z1", //企业id  企业代码
            'Shared_secret' => 'i045r9sjv4', //网关登录密码
            'Service_Id'    => "X109813039", //业务代码
            'template_id'   => "", //模板id
            'Dest_Id'       => "10692313", //短信接入码 短信端口号 服务代码
            'Sequence_Id'   => 1,
            'SP_ID'         => "",
            'master_num'    => 300,
        ];
    }

    public function Send($content)
    {
        // $this->clientSocketInit();
        $redis = Phpredis::getConn();
        date_default_timezone_set('PRC');
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        $content                    = 99;
        $redisMessageCodeSend       = 'index:meassage:code:send:' . $content; //验证码发送任务rediskey
        $redisMessageCodeSequenceId = 'index:meassage:code:sequence:id:' . $content; //行业通知SequenceId
        $redisMessageCodeMsgId      = 'index:meassage:code:msg:id:' . $content; //行业通知SequenceId
        // $redisMessageCodeDeliver    = 'index:meassage:code:deliver:' . $content; //行业通知MsgId
        $redisMessageCodeDeliver = 'index:meassage:code:new:deliver:' . $content; //行业通知MsgId
        $redisMessageUnKownDeliver = 'index:meassage:code:unknow:deliver:' . $content; //行业通知MsgId
        $redisMessageUpRiver       = 'index:message:code:upriver:' . $content; //上行队列
        // print_r($redis);die;
        /*          $send = $redis->rPush($redisMessageCodeSend, json_encode([
            'mobile'      => '15172413692',
            'mar_task_id' => '',
            'content'     => '【美丽田园】尊敬的顾客您好！即日起非会员只需支付212元即可尊享指定护理一折体验，每月前20位体验顾客加赠精美化妆包1个，10/22-12/31日我和万象城有个约会，万象城全体员工恭候您的体验，竭诚为您的皮肤保驾护航！详询：021-54700816 回T退订',
        ]));
        $send = $redis->rPush($redisMessageCodeSend, json_encode([
            'mobile'      => '15821193682',
            'mar_task_id' => '',
            'content'     => '【美丽田园】尊敬的顾客您好！即日起非会员只需支付212元即可尊享指定护理一折体验，每月前20位体验顾客加赠精美化妆包1个，10/22-12/31日我和万象城有个约会，万象城全体员工恭候您的体验，竭诚为您的皮肤保驾护航！详询：021-54700816 回T退订',
        ])); */
        //     $send = $redis->rPush($redisMessageCodeSend, json_encode([
        //     'mobile'      => '15201926171',
        //     'mar_task_id' => '',
        //     // 'content'     => '【沙驰服饰】“衬”现在，全身心去爱。沙驰衬衫节👔礼献父亲节，充值赠好礼，全场VIP折上8折起，快来表达你对爸爸👨的爱吧😄。退订回T',
        //     'content'     => '【钰晰科技】😄您本次登录的验证码为0518，回复QX取消本次登录',
        // ]));
        // $code = '【钰晰科技】😄您本次登录的验证码为0518，回复QX取消本次登录';
        // $code = mb_convert_encoding('【钰晰科技】😄您本次登录的验证码为0518，回复QX取消本次登录', 'UCS-2', 'UTF-8');
        // $code =iconv("UTF-8","UCS-2",$code);
        // $code =iconv("UCS-2","UTF-8",$code);
        // print_r($code);die;
        $socket   = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $log_path = realpath("") . "/error/".$content.".log";
        $myfile = fopen($log_path, 'a+');
        fwrite($myfile, date('Y-m-d H:i:s', time()) . "\n");
        fwrite($myfile, " Begin" . "\n");
        fclose($myfile);
        if ($socket == false) {
            $this->error_log("create");
            die;
        }

        // $content = 0;
        $contdata = $this->content($content);
        // // print_r($contdata);die;
        $host                 = $contdata['host']; //服务商ip
        $port                 = $contdata['port']; //短连接端口号   17890长连接端口号
        $Source_Addr          = $contdata['Source_Addr']; //企业id  企业代码
        $Shared_secret        = $contdata['Shared_secret']; //网关登录密码
        $Service_Id           = $contdata['Service_Id'];
        $Dest_Id              = $contdata['Dest_Id']; //短信接入码 短信端口号
        $Sequence_Id          = $contdata['Sequence_Id'];
        $SP_ID                = $contdata['SP_ID'];
        $master_num           = $contdata['master_num']; //通道最大提交量
        $security_coefficient = 0.8; //通道饱和系数
        $security_master      = $master_num * $security_coefficient;

        $log_path = realpath("") . "/error/".$content.".log";
        $myfile = fopen($log_path, 'a+');
        fwrite($myfile, date('Y-m-d H:i:s', time()) . "\n");
        fwrite($myfile, " host:" . $host . " port:" . $port . "\n");
        fclose($myfile);

        if (socket_connect($socket, $host, $port) == false) {
            // echo 'connect fail massege:' . socket_strerror(socket_last_error());
        } else {
            socket_set_nonblock($socket); //设置非阻塞模式
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
                            $error_msg = "其他错误";
                        } else {
                        }
                    } else if ($head['Command_Id'] == 0x00000005) { //收到短信下发应答,需回复应答，应答Command_Id = 0x80000005
                        $Result = 0;
                        $contentlen = $head['Total_Length'] - 65 - 12;
                        $body        = unpack("N2Msg_Id/a21Dest_Id/a10Service_Id/CTP_pid/CTP_udhi/CMsg_Fmt/a21Src_terminal_Id/CRegistered_Delivery/CMsg_Length/a" . $contentlen . "Msg_Content/", $bodyData);
                        $stalen = $body['Msg_Length'] - 20 - 8 - 21 - 4;
                        $Msg_Content = unpack("N2Msg_Id/a" . $stalen . "Stat/a10Submit_time/a10Done_time/a21Dest_terminal_Id/NSMSC_sequence ", $body['Msg_Content']);

                        $mesage = $redis->hget($redisMessageCodeMsgId, $Msg_Content['Msg_Id1'] . $Msg_Content['Msg_Id2']);


                        // print_r($body);
                        // print_r($Msg_Content);
                        if ($mesage) {
                            $redis->hdel($redisMessageCodeMsgId, $body['Msg_Id1'] . $body['Msg_Id2']);
                            // $redis->rpush($redisMessageCodeDeliver,$mesage.":".$Msg_Content['Stat']);
                            $mesage                = json_decode($mesage, true);
                            $mesage['Stat']        = $Msg_Content['Stat'];
                            // $mesage['Msg_Id']        = $Msg_Content['Msg_Id1'] . $Msg_Content['Msg_Id2'];
                            $mesage['Submit_time'] = $Msg_Content['Submit_time'];
                            $mesage['Done_time']   = $Msg_Content['Done_time'];
                            $redis->rpush($redisMessageCodeDeliver, json_encode($mesage));
                        } else { //不在记录中的回执存入缓存，
                            $mesage['Stat']        = $Msg_Content['Stat'];
                            $mesage['Submit_time'] = $Msg_Content['Submit_time'];
                            $mesage['Done_time']   = $Msg_Content['Done_time'];
                            $mesage['Msg_Id']   = $Msg_Content['Msg_Id1'] . $Msg_Content['Msg_Id2'];
                            // $mesage['mobile']      = $body['Dest_Id '];//手机号
                            $mesage['mobile']   = trim($Msg_Content['Dest_terminal_Id']);
                            $mesage['receive_time'] = time(); //回执时间戳
                            $redis->rPush($redisMessageUnKownDeliver, json_encode($mesage));
                        }
                        // print_r($mesage);
                        $callback_Command_Id = 0x80000005;

                        $new_body         = pack("N", $body['Msg_Id1']) . pack("N", $body['Msg_Id2']) . pack("C", $Result);
                        $new_Total_Length = strlen($new_body) + 12;
                        $new_headData     = pack("NNN", $new_Total_Length, $callback_Command_Id, $head['Sequence_Id']);
                        socket_write($socket, $new_headData . $new_body, $new_Total_Length);
                    } else if ($head['Command_Id'] == 0x00000008) {
                        // // echo "心跳维持中" . "\n"; //激活测试,无消息体结构
                        $callback_Command_Id = 0x80000008;

                        $new_body         =  pack("C", 0);
                        $new_Total_Length = strlen($new_body) + 12;
                        $new_headData     = pack("NNN", $new_Total_Length, $callback_Command_Id, $head['Sequence_Id']);
                        socket_write($socket, $new_headData . $new_body, $new_Total_Length);
                        $receive = 2;
                    } else if ($head['Command_Id'] == 0x80000008) {
                        // // echo "激活测试应答" . "\n"; //激活测试,无消息体结构
                    } else {
                        // // echo "未声明head['Command_Id']:" . $head['Command_Id'];
                    }
                }
                if ($verify_status == 0) { //验证成功并且所有信息已读完可进行发送操作
                    while (true) {

                        // echo $Sequence_Id . "\n";
                        try {
                            $receive = 1;
                            //先接收
                            while (true) {
                                $headData = socket_read($socket, 12);
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
                                        $body        = unpack("N2Msg_Id/a21Dest_Id/a10Service_Id/CTP_pid/CTP_udhi/CMsg_Fmt/a21Src_terminal_Id/CRegistered_Delivery/CMsg_Length/a" . $contentlen . "Msg_Content/", $bodyData);
                                        $Registered_Delivery = trim($body['Registered_Delivery']);
                                        // print_r($body);
                                        $develop_len = strlen($Dest_Id);
                                        $receive_develop_no = mb_substr(trim($body['Dest_Id']),$develop_len);
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
                                        $receive = 2;
                                    } else if ($head['Command_Id'] == 0x00000008) {
                                        // // echo "心跳维持中" . "\n"; //激活测试,无消息体结构
                                        $callback_Command_Id = 0x80000008;

                                        $new_body         =  pack("C", 0);
                                        $new_Total_Length = strlen($new_body) + 12;
                                        $new_headData     = pack("NNN", $new_Total_Length, $callback_Command_Id, $head['Sequence_Id']);
                                        socket_write($socket, $new_headData . $new_body, $new_Total_Length);
                                        $receive = 2;
                                    } else if ($head['Command_Id'] == 0x80000008) {
                                        // // echo "激活测试应答" . "\n"; //激活测试,无消息体结构
                                    } else {
                                        // // echo "未声明head['Command_Id']:" . $head['Command_Id'];
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
                                // $code = mb_convert_encoding($code, 'UCS-2', 'UTF-8');
                                $code =iconv("UTF-8","UCS-2",$code);
                                // iconv("UTF-8","gbk",$code);
                                // $redis->rPush($redisMessageCodeSend, json_encode($send_data));
                                // // print_r($code);die;
                                if (strlen($code) > 140) {
                                    $pos          = 0;
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
                                        $udh     = pack("cccccc", 5, 0, 3, $Sequence_Id, $num_messages, $j + 1);
                                        $newcode = $udh . substr($code, $j * $max_len, $max_len);
                                        $len     = strlen($newcode);
                                        $bodyData .= pack("C", $len);
                                        $bodyData .= pack("a" . $len, $newcode);
                                        $bodyData .= pack("a8", '');
                                        $Command_Id = 0x00000004; // 短信发送
                                        $Total_Length = strlen($bodyData) + 12;
                                        $headData     = pack("NNN", $Total_Length, $Command_Id, $Sequence_Id);
                                        $send_data['my_submit_time'] = time(); //发送时间戳
                                        $redis->hset($redisMessageCodeSequenceId, $Sequence_Id, json_encode($send_data));
                                        usleep(5);
                                        socket_write($socket, $headData . $bodyData, $Total_Length);
                                        $send_status = 2;
                                        ++$i;
                                    }
                                    ++$Sequence_Id;
                                    if ($Sequence_Id > 65536) {
                                        $Sequence_Id = 1;
                                    }
                                    if ($i > $security_master) {
                                        $i    = 0;
                                    }
                                    usleep(50);
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
                                    usleep(150);
                                }
                            } else { //心跳
                                $Command_Id  = 0x00000008; //保持连接
                                $Total_Length = 12;
                                $headData     = pack("NNN", $Total_Length, $Command_Id, $Sequence_Id);
                                if ( $receive != 2) {
                                    socket_write($socket, $headData, $Total_Length);
                                }
                                sleep(1);
                            }

                            ++$i;
                            ++$Sequence_Id;
                            if ($Sequence_Id > 65536) {
                                $Sequence_Id = 1;
                            }
                        }
                        //捕获异常
                        catch (Exception $e) {
                            if ($send_status == 1) {
                                $redis->push($redisMessageCodeSend, $redisMessageCodeSend);
                                $redis->hset($redisMessageCodeSequenceId, $Sequence_Id);
                            }
                            socket_close($socket);
                            $redis->rpush('index:meassage:code:send' . ":" . 85, json_encode([
                                'mobile'      => 15201926171,
                                'content'     => "【钰晰科技】通道编号[" . $content . "] 出现故障,连接服务商失败，请紧急处理解决或者切换！！！",
                            ])); //易信行业通道
                            $log_path = realpath("") . "/error/".$content.".log";
                            $myfile = fopen($log_path, 'a+');
                            fwrite($myfile, date('Y-m-d H:i:s', time()) . "\n");
                            fwrite($myfile, $e . "\n");
                            fclose($myfile);
                            //  exception($e);
                            sleep(20);
                            //重新创建连接
                            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
                            if (socket_connect($socket, $host, $port) == false) {
                                $myfile = fopen($log_path, 'a+');
                                fwrite($myfile, date('Y-m-d H:i:s', time()) . "\n");
                                fwrite($myfile,  "通道延迟5秒后再次连接失败，请联系通道方检查原因\n");
                                fclose($myfile);
                                $redis->rpush('index:meassage:code:send' . ":" . 1, json_encode([
                                    'mobile'      => 15201926171,
                                    'content'     => "【钰晰科技】通道编号[" . $content . "] 出现故障,连接服务商失败，请紧急处理解决或者切换！！！",
                                ])); //三体营销通道
                                $redis->rpush('index:meassage:code:send' . ":" . 9, json_encode([
                                    'mobile'      => 15201926171,
                                    'content'     => "【钰晰科技】通道编号[" . $content . "] 出现故障,连接服务商失败，请紧急处理解决或者切换！！！",
                                ])); //三体营销通道
                               
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
                                    $redis->rpush('index:meassage:code:send' . ":" . 1, json_encode([
                                        'mobile'      => 15201926171,
                                        'content'     => "【钰晰科技】通道编号[" . $content . "] 出现故障,写入socket失败，请紧急处理解决或者切换！！！",
                                    ])); //三体营销通道
                                    $redis->rpush('index:meassage:code:send' . ":" . 9, json_encode([
                                        'mobile'      => 15201926171,
                                        'content'     => "【钰晰科技】通道编号[" . $content . "] 出现故障,写入socket失败，请紧急处理解决或者切换！！！",
                                    ])); //三体营销通道
                                    $redis->rpush('index:meassage:code:send' . ":" . 24, json_encode([
                                        'mobile'      => 15201926171,
                                        'content'     => "【钰晰科技】通道编号[" . $content . "] 出现故障,写入socket失败，请紧急处理解决或者切换！！！",
                                    ])); //易信行业通道
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

    public function error_log($error_type)
    {
        $log_path = realpath("") . "/error/16.log";
        $myfile = fopen($log_path, 'a+');
        fwrite($myfile, date('Y-m-d H:i:s', time()) . "\n");
        fwrite($myfile, $error_type . " fail massaege:" . socket_strerror(socket_last_error()) . "\n");
        fclose($myfile);
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
