<?php

namespace app\console\com;

use app\console\Pzlife;
use cache\Phpredis;
use Config;
use Env;
use Exception;
use think\Db;

class ClientSocketSantiBusiness extends Pzlife {

    // protected $redis;

    private function clientSocketInit() {
        $this->redis = Phpredis::getConn();
        //        $this->connect = Db::connect(Config::get('database.db_config'));
    }
//三体行业
    public function content($content) {
        return [
            'host'          => "116.62.88.162", //服务商ip
            'port'          => "8592", //短连接端口号   17890长连接端口号
            'Source_Addr'   => "101161", //企业id  企业代码
            'Shared_secret' => '5hsey6u9', //网关登录密码
            'template_id'   => "217062", //模板id
            'Service_Id'    => "101161", //业务代码
            'Dest_Id'       => "106928080159", //短信接入码 短信端口号
            'Sequence_Id'   => 1,
            'SP_ID'         => "",
            'master_num'    => 300,
        ];
     
    }

    public function SocketClientLong($content) {
        // $this->clientSocketInit();
        $redis = Phpredis::getConn();
        // $a_time = 0;
        date_default_timezone_set('PRC');
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        $content                    = 1;
        $redisMessageCodeSend       = 'index:meassage:code:send:' . $content; //验证码发送任务rediskey
        $redisMessageCodeSequenceId = 'index:meassage:code:sequence:id:' . $content; //行业通知SequenceId
        $redisMessageCodeMsgId      = 'index:meassage:code:msg:id:' . $content; //行业通知SequenceId
        $redisMessageCodeDeliver    = 'index:meassage:code:new:deliver:' . $content; //行业通知MsgId
        // $redisMessageCodeSend       = 'index:meassage:marketing:send:' . $content; //营销发送任务rediskey
        // $redisMessageCodeSequenceId = 'index:meassage:marketing:sequence:id:' . $content; //营销行业通知SequenceId
        // $redisMessageCodeMsgId      = 'index:meassage:marketing:msg:id:' . $content; //营销行业通知SequenceId
        // $redisMessageCodeDeliver    = 'index:meassage:marketing:deliver:' . $content; //营销行业通知MsgId
        // echo $redisMessageCodeSend;die;
        // do {
        //     $send = $redis ->lPop($redisMessageCodeSend);
        //     print_r($send);
        // } while ($send);
        // $send = $redis ->lPop($redisMessageCodeSend);
        // $send = $redis->rPush($redisMessageCodeSend, json_encode([
        //     'mobile'      => '15201926171',
        //     'mar_task_id' => 15745,
        //     'uid'         => 38,
        //     'content'     => '【宝洁中国】风倍清去味除菌喷雾~懒人清洁神器，一喷清新！付几套送几套，限量送加湿器 http://weu.me/_4BbkA 回QX退订',
        //     'Submit_time' => date('mdHis', time()),
        // ]));
        // $send = $redis->lPop($redisMessageCodeSend);
        // $send_data = json_decode($send, true);
        // $code = $send_data['content']; //带签名
        // $code = strval($this->ascii_encode($code));//UTF-8 转ASCII
        // print_r($code);die;
        $socket   = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $content  = 1;
        $contdata = $this->content($content);

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
        // echo $security_master;die;
        // die;
        // $send = $this->redis->lPop($redisMessageCodeSend);
        // print_r($send);
        // die;
        if (socket_connect($socket, $host, $port) == false) {
            // echo 'connect fail massege:' . socket_strerror(socket_last_error());
        } else {
            socket_set_nonblock($socket); //设置非阻塞模式
            $i           = 1;
            $Sequence_Id = 1;
            do {
                try
                {
                    date_default_timezone_set('PRC');
                    echo $Sequence_Id . "\n";
                    $time                = 0;
                    $Version             = 0x20; //CMPP版本 0x20 2.0版本 0x30 3.0版本
                    $Timestamp           = date('mdHis');
                    $AuthenticatorSource = md5($Source_Addr . pack("a9", "") . $Shared_secret . $Timestamp, true);
                    if ($i == 1) {
                        $bodyData   = pack("a6a16CN", $Source_Addr, $AuthenticatorSource, $Version, $Timestamp);
                        $Command_Id = 0x00000001;
                        $Total_Length = strlen($bodyData) + 12;
                        $headData     = pack("NNN", $Total_Length, $Command_Id, $Sequence_Id);
                        socket_write($socket, $headData . $bodyData, $Total_Length);
                    } else {
                        //当有号码发送需求时 进行提交
                        /* redis 读取需要发送的数据 */
                        $send = $redis->lPop($redisMessageCodeSend);
                        if ($send) { //正式使用从缓存中读取数据
                            
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
                            echo "发送时间：" . date("Y-m-d H:i:s", time()) . "\n";
                            $num1 = substr($timestring, 0, 8);
                            $num2 = substr($timestring, 8) . $this->combination($i);
                            $code = mb_convert_encoding($code, 'GBK', 'UTF-8');
                            if (strlen($code) > 140) {
                                $pos          = 0;
                                $num_messages = ceil(strlen($code) / $max_len);
                                for ($j = 0; $j < $num_messages; $j++) {
                                    $bodyData = pack("N", $num1) . pack("N", $num2);
                                    $bodyData.= pack('C', $num_messages);
                                    $bodyData.= pack('C', $j + 1); 
                                    $bodyData.= pack('C', 1);
                                    $bodyData.= pack('C', ''); 
                                    $bodyData.= pack("a10", $Service_Id);
                                    $bodyData.= pack('C', ''); 
                                    $bodyData.= pack("a21", $mobile);
                                    $bodyData.= pack("C", 0); 
                                    $bodyData.= pack("C", 1);
                                    $bodyData.= pack("C", 15); 
                                    $bodyData.= pack("a6", $Source_Addr);
                                    $bodyData.= pack("a2", 02);
                                    $bodyData.= pack("a6", ''); 
                                    $bodyData.= pack("a17", '');
                                    $bodyData.= pack("a17", ''); 
                                    $bodyData.= pack("a21", $Dest_Id);
                                    $bodyData.= pack("C", $uer_num);
                                    $p_n      = 21 * $uer_num;
                                    $bodyData.= pack("a" . $p_n, $mobile);
                                    $udh     = pack("cccccc", 5, 0, 3, $Sequence_Id, $num_messages, $j + 1);
                                    $newcode = $udh . substr($code, $j * $max_len, $max_len);
                                    $len     = strlen($newcode);
                                    $bodyData.= pack("C", $len);
                                    $bodyData.= pack("a" . $len, $newcode);
                                    $bodyData.= pack("a8", '');
                                    $Command_Id = 0x00000004; // 短信发送
                                    $Total_Length = strlen($bodyData) + 12;
                                    $headData     = pack("NNN", $Total_Length, $Command_Id, $Sequence_Id);
                                    $redis->hset($redisMessageCodeSequenceId, $Sequence_Id, $send);
                                    usleep(300);
                                    socket_write($socket, $headData . $bodyData, $Total_Length);
                                    $send_status = 2;
                                    $headData = socket_read($socket, 12);
                                    if ($headData != false) {
                                        $head = unpack("NTotal_Length/NCommand_Id/NSequence_Id", $headData);
                                        $bodyData = socket_read($socket, $head['Total_Length'] - 12);
                                        if ($head['Command_Id'] == 0x80000001) {
                                            $body = unpack("CStatus/a16AuthenticatorSource/CVersion", $bodyData);
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
                                                echo "发送成功" . "\n";
                                                break;
                                            case 1:
                                                echo "消息结构错" . "\n";
                                                $error_msg = "消息结构错";
                                                break;
                                            case 2:
                                                echo "命令字错" . "\n";
                                                $error_msg = "命令字错";
                                                break;
                                            case 3:
                                                echo "消息序号重复" . "\n";
                                                $error_msg = "消息序号重复";
                                                break;
                                            case 4:
                                                echo "消息长度错" . "\n";
                                                $error_msg = "消息长度错";
                                                break;
                                            case 5:
                                                echo "资费代码错" . "\n";
                                                $error_msg = "资费代码错";
                                                break;
                                            case 6:
                                                echo "超过最大信息长" . "\n";
                                                $error_msg = "超过最大信息长";
                                                break;
                                            case 7:
                                                echo "业务代码错" . "\n";
                                                $error_msg = "业务代码错";
                                                break;
                                            case 8:
                                                echo "流量控制错" . "\n";
                                                $error_msg = "业务代码错";
                                                break;
                                            default:
                                                echo "其他错误" . "\n";
                                                $error_msg = "其他错误";
                                                break;
                                            }
                                            if ($body['Result'] != 0) { //消息发送失败
                                                echo "发送失败" . "\n";
                                                $error_msg = "其他错误";
                                            } else {

                                            }
                                        } else if ($head['Command_Id'] == 0x00000005) { //收到短信下发应答,需回复应答，应答Command_Id = 0x80000005
                                            $Result = 0;
                                            $contentlen = $head['Total_Length'] - 65 - 12;
                                            $body        = unpack("N2Msg_Id/a21Dest_Id/a10Service_Id/CTP_pid/CTP_udhi/CMsg_Fmt/a21Src_terminal_Id/CRegistered_Delivery/CMsg_Length/a" . $contentlen . "Msg_Content/", $bodyData);
                                            $Msg_Content = unpack("N2Msg_Id/a7Stat/a10Submit_time/a10Done_time/", $body['Msg_Content']);

                                            $mesage = $redis->hget($redisMessageCodeMsgId, $Msg_Content['Msg_Id1'] . $Msg_Content['Msg_Id2']);
                                            if ($mesage) {
                                                $redis->hdel($redisMessageCodeMsgId, $body['Msg_Id1'] . $body['Msg_Id2']);
                                                // $redis->rpush($redisMessageCodeDeliver,$mesage.":".$Msg_Content['Stat']);
                                                $mesage                = json_decode($mesage, true);
                                                $mesage['Stat']        = $Msg_Content['Stat'];
                                                // $mesage['Msg_Id']        = $Msg_Content['Msg_Id1'] . $Msg_Content['Msg_Id2'];
                                                $mesage['Submit_time'] = $Msg_Content['Submit_time'];
                                                $mesage['Done_time']   = $Msg_Content['Done_time'];
                                                $redis->rpush($redisMessageCodeDeliver, json_encode($mesage));

                                            }
                                            $callback_Command_Id = 0x80000005;

                                            $new_body         = pack("N", $body['Msg_Id1']) . pack("N", $body['Msg_Id2']) . pack("C", $Result);
                                            $new_Total_Length = strlen($new_body) + 12;
                                            $new_headData     = pack("NNN", $Total_Length, $callback_Command_Id, $body['Msg_Id2']);
                                            socket_write($socket, $new_headData . $new_body, $new_Total_Length);
                                        } else if ($head['Command_Id'] == 0x00000008) {
                                            echo "心跳维持中" . "\n"; //激活测试,无消息体结构
                                        } else if ($head['Command_Id'] == 0x80000008) {
                                            echo "激活测试应答" . "\n"; //激活测试,无消息体结构
                                        } else {
                                            echo "未声明head['Command_Id']:" . $head['Command_Id'];
                                        }

                                    }

                                    ++$i;
                                }
                                ++$Sequence_Id;
                                if ($Sequence_Id > 65536) {
                                    $Sequence_Id = 1;
                                }
                                if ($i > $security_master) {
                                    $i    = 0;
                                }
                                continue;
                            } else { //单条短信

                                $bodyData = pack("N", $num1) . pack("N", $num2);
                                $bodyData.= pack('C', 1);
                                $bodyData.= pack('C', 1);
                                $bodyData.= pack('C', 1);
                                $bodyData.= pack('C', '');
                                $bodyData.= pack("a10", $Service_Id);
                                $bodyData.= pack('C', '');
                                $bodyData.= pack("a21", $mobile); 
                                $bodyData.= pack("C", 0);
                                $bodyData.= pack("C", 0);
                                $bodyData.= pack("C", 15);
                                $bodyData.= pack("a6", $Source_Addr);
                                $bodyData.= pack("a2", 02);
                                $bodyData.= pack("a6", '');
                                $bodyData.= pack("a17", '');
                                $bodyData.= pack("a17", '');
                                $bodyData.= pack("a21", $Dest_Id);
                                $bodyData.= pack("C", $uer_num);
                                $p_n      = 21 * $uer_num;
                                $bodyData.= pack("a" . $p_n, $mobile);
                                $len      = strlen($code);
                                $bodyData.= pack("C", $len); 
                                $bodyData.= pack("a" . $len, $code);
                                $bodyData.= pack("a8", ''); 
                                $Command_Id = 0x00000004; // 短信发送
                                $time = 0;
                            }
                            if ($i > $security_master) {
                                $time = 1;
                                $i    = 0;
                            }
                            $redis->hset($redisMessageCodeSequenceId, $Sequence_Id, $send);
                            $Total_Length = strlen($bodyData) + 12;
                            $headData     = pack("NNN", $Total_Length, $Command_Id, $Sequence_Id);
                            socket_write($socket, $headData . $bodyData, $Total_Length);
                            
                             $send_status = 2;
                             usleep(300);
                        } else {//没有号码发送时 发送连接请求
                            // $bodyData    = pack("a6a16CN", $Source_Addr, $AuthenticatorSource, $Version, $Timestamp);
                            $Command_Id  = 0x00000008; //保持连接
                            $Total_Length = 12;
                            $headData     = pack("NNN", $Total_Length, $Command_Id, $Sequence_Id);
                            socket_write($socket, $headData , $Total_Length);
                            sleep(1);
                        }
                    }
                    
                    $headData = socket_read($socket, 12);
                    if ($headData != false) {
                        $head = unpack("NTotal_Length/NCommand_Id/NSequence_Id", $headData);
                        // print_r($head);
                        $bodyData = socket_read($socket, $head['Total_Length'] - 12);
                        // print_r($bodyData);
                        // echo "\n";

                        if ($head['Command_Id'] == 0x80000001) {
                            $body = unpack("CStatus/a16AuthenticatorSource/CVersion", $bodyData);
                            // print_r($body) ;
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
                                echo $error_msg . "\n";
                                // die;
                            }
                        } else if ($head['Command_Id'] == 0x80000004) {
                            $body = unpack("N2Msg_Id/CResult", $bodyData);
                            print_r($body);
                            $sequence = $redis->hget($redisMessageCodeSequenceId, $head['Sequence_Id']);
                            if ($sequence) {
                                $sequence           = json_decode($sequence, true);
                                $sequence['Msg_Id'] = $body['Msg_Id1'] . $body['Msg_Id2'];

                                $redis->hdel($redisMessageCodeSequenceId, $head['Sequence_Id']);
                                $redis->hset($redisMessageCodeMsgId, $body['Msg_Id1'] . $body['Msg_Id2'], json_encode($sequence));
                            }
                            switch ($body['Result']) {
                            case 0:
                                break;
                            case 1:
                                $error_msg = "消息结构错";
                                break;
                            case 2:
                                $error_msg = "命令字错";
                                break;
                            case 3:
                                $error_msg = "消息序号重复";
                                break;
                            case 4:
                                $error_msg = "消息长度错";
                                break;
                            case 5:
                                $error_msg = "资费代码错";
                                break;
                            case 6:
                                $error_msg = "超过最大信息长";
                                break;
                            case 7:
                                $error_msg = "业务代码错";
                                break;
                            case 8:
                                $error_msg = "业务代码错";
                                break;
                            default:
                                $error_msg = "其他错误";
                                break;
                            }
                            if ($body['Result'] != 0) { //消息发送失败
                                echo "发送失败" . "\n";
                                echo $error_msg . "\n";
                            }
                        } else if ($head['Command_Id'] == 0x00000005) { //收到短信下发应答,需回复应答，应答Command_Id = 0x80000005
                            $Result = 0;
                            $contentlen = $head['Total_Length'] - 65 - 12;
                            $body        = unpack("N2Msg_Id/a21Dest_Id/a10Service_Id/CTP_pid/CTP_udhi/CMsg_Fmt/a21Src_terminal_Id/CRegistered_Delivery/CMsg_Length/a" . $contentlen . "Msg_Content/", $bodyData);
                            $Msg_Content = unpack("N2Msg_Id/a7Stat/a10Submit_time/a10Done_time/", $body['Msg_Content']);
                            
                            $mesage = $redis->hget($redisMessageCodeMsgId, $Msg_Content['Msg_Id1'] . $Msg_Content['Msg_Id2']);
                            if ($mesage) {
                                $redis->hdel($redisMessageCodeMsgId, $body['Msg_Id1'] . $body['Msg_Id2']);
                                // $redis->rpush($redisMessageCodeDeliver,$mesage.":".$Msg_Content['Stat']);
                                $mesage                = json_decode($mesage, true);
                                // $mesage['Msg_Id']        = strval($Msg_Content['Msg_Id1']) . strval($Msg_Content['Msg_Id2']);
                                $mesage['Stat']        = $Msg_Content['Stat'];
                                $mesage['Submit_time'] = $Msg_Content['Submit_time'];
                                $mesage['Done_time']   = $Msg_Content['Done_time'];
                                $redis->rpush($redisMessageCodeDeliver, json_encode($mesage));

                            }
                            print_r($mesage);
                            $callback_Command_Id = 0x80000005;

                            $new_body         = pack("N", $body['Msg_Id1']) . pack("N", $body['Msg_Id2']) . pack("C", $Result);
                            $new_Total_Length = strlen($new_body) + 12;
                            $new_headData     = pack("NNN", $Total_Length, $callback_Command_Id, $body['Msg_Id2']);
                            // socket_write($socket, $new_headData . $new_body, $new_Total_Length);
                        } else if ($head['Command_Id'] == 0x00000008) {
                            echo "心跳维持中" . "\n"; //激活测试,无消息体结构

                        } else if ($head['Command_Id'] == 0x80000008) {
                            echo "激活测试应答" . "\n"; //激活测试,无消息体结构
                        } else {
                            echo "未声明head['Command_Id']:" . $head['Command_Id'];
                            // break;
                        }

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
                        $redis->push($redisMessageCodeSend,$redisMessageCodeSend);
                        $redis->hset($redisMessageCodeSequenceId,$Sequence_Id);
                     }
                     exception($e);
                    socket_close($socket);
                    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
                    socket_connect($socket, $host, $port);
                    $Version             = 0x20; //CMPP版本 0x20 2.0版本 0x30 3.0版本
                    $Timestamp           = date('mdHis');
                    $AuthenticatorSource = md5($Source_Addr . pack("a9", "") . $Shared_secret . $Timestamp, true);
                    $bodyData   = pack("a6a16CN", $Source_Addr, $AuthenticatorSource, $Version, $Timestamp);
                    $Command_Id = 0x00000001;
                    $Total_Length = strlen($bodyData) + 12;
                    $headData     = pack("NNN", $Total_Length, $Command_Id, $Sequence_Id);
                    socket_write($socket, $headData . $bodyData, $Total_Length);
                    ++$i;
                    ++$Sequence_Id;
                }
            } while (true);

        }
    }

    public function cmppDeliver($Total_Length, $Sequence_Id) { //Msg_Id直接用N解析不行
        $contentlen = $Total_Length - 109;
        $body       = unpack("N2Msg_Id/a21Dest_Id/a10Service_Id/CTP_pid/CTP_udhi/CMsg_Fmt/a32Src_terminal_Id/CSrc_terminal_type/CRegistered_Delivery/CMsg_Length/a" . $contentlen . "Msg_Content/a20LinkID", $this->bodyData);
        var_dump($body);
        if ($body['Msg_Length'] > 0) {
            $data = $body['Msg_Content'];
            //$Msg_Id = $body['Msg_Id'];
            $Msg_Id   = ($body['Msg_Id1'] & 0x0fffffff);
            $Msg_Idfu = $body['Msg_Id2'];
            $msgidz   = unpack("N", substr($this->bodyData, 0, 8));
            $msgidzz  = '0000' . $msgidz[1];
            //操作数据库(原方法)
            /* mysql_connect('localhost', '', '');
            mysql_select_db('');
            mysql_query('set names utf8');
            $data    = trim($data);
            $sql1    = "select id from socket_yd where msgid='" . $Msg_Id . "'";
            $chongfu = mysql_query($sql1);
            $arrs    = array();
            while ($arr = mysql_fetch_assoc($chongfu)) {
            $arrs[] = $arr;
            }
            if ($arrs == array() || $arrs[0] == null) {
            $sql = "insert into socket_yd set msgid='" . $Msg_Id . "', content='" . addslashes($data) . "', add_time='" . date('Y-m-d H:i:s') . "'";
            mysql_query($sql);
            } */
            // mysql_close();
            //echo $Msg_Id."\n";
            echo $data . "\n";
            echo $msgidzz . "\n";
            echo $Sequence_Id . "\n";
            $this->cmppDeliverResp($msgidzz, $Msg_Idfu, $Sequence_Id);
        }
    }

    //16进制转2进制
    function StrToBin($str) {
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

    public function decodeString() {
        // echo strlen("³½'¹ ");
        $timestring = time();
        $num1       = substr($timestring, 0, 8);
        $num2       = substr($timestring, 8) . $this->combination(rand(1, 240));
        echo $num1;
        echo "\n";
        echo $num2;

        $a = pack("N", $num1) . pack("N", $num2);
        echo $a . "\n";
        print_r(unpack("N2Msg_Id", $a));

        die;
        $arr = unpack("N2Msg_Id/a7Stat/a10Submit_time/a10Done_time/", "³f󿾧©¬DELIVRD1911071650191107165515201926171AG");

    }

    /**
     * ascii 转换
     * @param $c
     * @param string $prefix
     * @return string
     */
    function ascii_encode($c, $prefix="&#") {
        $len = strlen($c);
        $a = 0;
        $scill = '';
        while ($a < $len) {
            $ud = 0;
            if (ord($c{$a}) >= 0 && ord($c{$a}) <= 127) {
                $ud = ord($c{$a});
                $a += 1;
            } else if (ord($c{$a}) >= 192 && ord($c{$a}) <= 223) {
                $ud = (ord($c{$a}) - 192) * 64 + (ord($c{$a + 1}) - 128);
                $a += 2;
            } else if (ord($c{$a}) >= 224 && ord($c{$a}) <= 239) {
                $ud = (ord($c{$a}) - 224) * 4096 + (ord($c{$a + 1}) - 128) * 64 + (ord($c{$a + 2}) - 128);
                $a += 3;
            } else if (ord($c{$a}) >= 240 && ord($c{$a}) <= 247) {
                $ud = (ord($c{$a}) - 240) * 262144 + (ord($c{$a + 1}) - 128) * 4096 + (ord($c{$a + 2}) - 128) * 64 + (ord($c{$a + 3}) - 128);
                $a += 4;
            } else if (ord($c{$a}) >= 248 && ord($c{$a}) <= 251) {
                $ud = (ord($c{$a}) - 248) * 16777216 + (ord($c{$a + 1}) - 128) * 262144 + (ord($c{$a + 2}) - 128) * 4096 + (ord($c{$a + 3}) - 128) * 64 + (ord($c{$a + 4}) - 128);
                $a += 5;
            } else if (ord($c{$a}) >= 252 && ord($c{$a}) <= 253) {
                $ud = (ord($c{$a}) - 252) * 1073741824 + (ord($c{$a + 1}) - 128) * 16777216 + (ord($c{$a + 2}) - 128) * 262144 + (ord($c{$a + 3}) - 128) * 4096 + (ord($c{$a + 4}) - 128) * 64 + (ord($c{$a + 5}) - 128);
                $a += 6;
            } else if (ord($c{$a}) >= 254 && ord($c{$a}) <= 255) { //error
                $ud = false;
            }
            $scill .= $prefix.$ud.";";
        }
        return $scill;
    }

    /**
     * 6位数字补齐
     * @param string $pdu
     * @return string
     */
    function combination($num) {
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
    public function pduord($pdu) {
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
    function decode($str, $prefix = "&#") {
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

    public function getSendCodeTask() {
        $task = Db::query("SELECT * FROM yx_user_send_code_task WHERE `send_status` = 1 ORDER BY id ASC LIMIT 1");
        if ($task) {
            return $task[0];
        }
        return [];
    }
}
