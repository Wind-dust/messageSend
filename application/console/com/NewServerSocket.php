<?php

namespace app\console\com;

use app\console\Pzlife;
use cache\Phpredis;
use Config;
use Env;
use function Qiniu\json_decode;
use think\Db;

class NewServerSocket extends Pzlife
{

    // private $bodyData;

    public function Service($content)
    {
        $contdata = $this->content($content);
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G

        $host          = $contdata['host']; //服务商ip
        $port          = $contdata['port']; //短连接端口号   17890长连接端口号
        $Source_Addr   = $contdata['Source_Addr']; //企业id  企业代码
        $Shared_secret = $contdata['Shared_secret']; //网关登录密码
        $Service_Id    = $contdata['Service_Id'];
        $Dest_Id       = $contdata['Dest_Id']; //短信接入码 短信端口号
        $Sequence_Id   = $contdata['Sequence_Id'];
        $SP_ID         = $contdata['SP_ID'];
        $bin_ip        = $contdata['bin_ip']; //客户端绑定IP

        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1); #可以重复使用端口号
        /*绑定接收的套接流主机和端口,与客户端相对应*/
        if (socket_bind($socket, '127.0.0.1', 8888) == false) {
            echo 'server bind fail:' . socket_strerror(socket_last_error());
            /*这里的127.0.0.1是在本地主机测试，你如果有多台电脑，可以写IP地址*/
        }
        //监听套接流
        if (socket_listen($socket, 4) == false) {
            echo 'server listen fail:' . socket_strerror(socket_last_error());
        }
        while (true) {
            print("******等待新客户端的到来*******\r\n");
            $clien = socket_accept($socket) or die("error:" . socket_strerror(socket_last_error()));
            $pid = pcntl_fork();
            #posix_setsid();
            if ($pid == -1) {
                die('fork failed');
            } else if ($pid == 0) {
                $this->hanld_seesion($clien);
            } else {
                #pcntl_wait($status);
            }
        }
    }

    function hanld_seesion($accept_resource)
    {
        socket_getpeername($accept_resource, $ip, $port);
        $id = posix_getpid();
        // print("进程ID:$id == 客户端:".$ip.":".$port."已连接\r\n");
        $redis                    = Phpredis::getConn();
        // $content                  = 9; //绑定通道
        $redisMessageCodeSend     = 'index:meassage:game:send:task'; //游戏发送任务rediskey
        $redisMessageCodeSendReal = 'index:meassage:game:send:realtask'; //游戏真实发送任务rediskey
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G

        /*  $contdata                 = $this->content($content);
        $host          = $contdata['host']; //服务商ip
        $port          = $contdata['port']; //短连接端口号   17890长连接端口号
        $Source_Addr   = $contdata['Source_Addr']; //企业id  企业代码
        $Shared_secret = $contdata['Shared_secret']; //网关登录密码
        $Service_Id    = $contdata['Service_Id'];
        $Dest_Id       = $contdata['Dest_Id']; //短信接入码 短信端口号
        $Sequence_Id   = $contdata['Sequence_Id'];
        $SP_ID         = $contdata['SP_ID'];
        $bin_ip        = $contdata['bin_ip']; //客户端绑定IP
        $free_trial    = $contdata['free_trial']; //是否需要审核 1:需要审核;2:无需审核
        $master_num    = $contdata['master_num']; //通道最大提交量
        $uid           = $contdata['uid']; //通道最大提交量 */
        while (true) {
            /*  $data = socket_read($clien,1024);
            if(mb_strlen($data) == 0){
                // print("进程ID:$id == 客户端:".$ip.":".$port."断开连接\r\n");
                socket_close($clien);
                exit();
            } */
            // print($ip.":".$port.">>:".$data."\r\n");
            $Timestamp = date('mdHis');
            if ($accept_resource !== false) {
                $headData = socket_read($accept_resource, 12);
                if ($headData != false) {
                    $head = unpack("NTotal_Length/NCommand_Id/NSequence_Id", $headData);
                    // $bodyData = socket_read($accept_resource, $head['Total_Length'] - 12);
                    // print_r($head);
                    // print_r($bodyData);
                    // echo "\n";
                    //获取请求源ip
                    socket_getpeername($accept_resource, $addr, $por);
                    // echo $addr;die;

                    try {
                        // $head = unpack("NTotal_Length/NCommand_Id/NSequence_Id", $headData);
                        // print_r($head);
                        if ($head['Command_Id'] == 0x00000001) { //请求链接
                            $bodyData = socket_read($accept_resource, $head['Total_Length'] - 12);
                            $status       = 0;
                            $new_bodyData = pack("C", 0); //status | 1 | Unsigned Integer |状态 0：正确 1：消息结构错  2：非法源地址  3：认证错  4：版本太高   5~ ：其他错误
                            try {
                                // $bodyData = socket_read($accept_resource, $head['Total_Length'] - 12);
                                $body = unpack("a6Source_Addr/a16AuthenticatorSource/CVersion/NTimestamp", $bodyData);
                                //ip地址绑定
                                if (!in_array($addr, $bin_ip)) {
                                    $status       = 2;
                                    $new_bodyData = pack("C", 2); //status | 1 | Unsigned Integer |状态 0：正确 1：消息结构错  2：非法源地址  3：认证错  4：版本太高   5~ ：其他错误
                                }
                                if ($body['Version'] != 0x20) { //验证版本
                                    $status       = 4;
                                    $new_bodyData = pack("C", 4); //status | 1 | Unsigned Integer |状态 0：正确 1：消息结构错  2：非法源地址  3：认证错  4：版本太高   5~ ：其他错误
                                }
                            } catch (Exception $e) {
                                $status       = 1;
                                $new_bodyData = pack("C", 1); //status | 1 | Unsigned Integer |状态 0：正确 1：消息结构错  2：非法源地址  3：认证错  4：版本太高   5~ ：其他错误
                            }

                            $back_Command_Id = 0x80000001; //连接应答
                            // echo $status;
                            $AuthenticatorISMG = pack("a16", ''); //AuthenticatorISMG | 16 | Octet String | ISMG 认证码，用于鉴别 ISMG。 其值通过单向 MD5 hash 计算得出， 表示如下： AuthenticatorISMG =MD5 （Status+AuthenticatorSource+shared secret），Shared secret 由中国移动 与源地址实体事先商定， AuthenticatorSource 为源地址实体 发送给 ISMG 的对应消息 CMPP_Connect 中的值。  认证出错时，此项为空。
                            if ($status != 3) {
                                $AuthenticatorISMG = pack("a16", md5($status . $bodyData . $Shared_secret, true));
                            }
                            $new_bodyData = $new_bodyData . $AuthenticatorISMG . pack("C", 0x20);
                            // echo $new_bodyData;die;
                            $Total_Length = strlen($new_bodyData) + 12;
                            $new_headData = pack("NNN", $Total_Length, $back_Command_Id, $head['Sequence_Id']);

                            socket_write($accept_resource, $new_headData . $new_bodyData, $Total_Length);
                            // socket_write的作用是向socket_create的套接流写入信息，或者向socket_accept的套接流写入信息
                            if ($status != 0) {
                                socket_close($accept_resource);
                            }
                        } else if ($head['Command_Id'] == 0x00000004) {
                            // $contentlen = $head['Total_Length'] - 12 - 116;
                            $bodyData  = socket_read($accept_resource, 117);
                            $body      = unpack("N2Msg_Id/CPk_total/CPk_number/CRegistered_Delivery/CMsg_level/a10Service_Id/CFee_UserType/a21Fee_terminal_Id/CTP_pId/CTP_udhi/CMsg_Fmt/a6Msg_src/a2FeeType/a6FeeCode/a17ValId_Time/a17At_Time/a21Src_Id/CDestUsr_tl", $bodyData);
                            $Pk_total  = $body['Pk_total']; //相同 Msg_Id 的信息总条数
                            $Pk_number = $body['Pk_number']; //相同 Msg_Id 的信息总条数
                            /*               if (strlen($body['Src_Id']) > 17){
                               $status = 9;
                               $timestring = time();
                               $back_Command_Id = 0x80000004; //发送应答
                               $num1            = substr($timestring, 0, 8);
                               $num2            = substr($timestring, 8) . $this->combination($i);
                               $new_bodyData    = pack("N", $num1) . pack("N", $num2);
                               $new_bodyData    = $new_bodyData . pack('C', $status);
                               $Total_Length = strlen($new_bodyData) + 12;
                               $new_headData = pack("NNN", $Total_Length, $back_Command_Id, $head['Sequence_Id']);
                               // socket_write($socket, $headData . $bodyData, $Total_Length);

                               // print_r($back_Command_Id);
                               // 向socket_accept的套接流写入信息，也就是回馈信息给socket_bind()所绑定的主机客户端
                               // echo $new_headData . $new_bodyData."\n";
                               // echo $back_Command_Id."\n";
                               socket_write($accept_resource, $new_headData . $new_bodyData, $Total_Length);
                               continue;
                           }else{
                               if (substr($body['Src_Id'],0,10) != $Dest_Id) {
                                   $status = 9;
                                   $timestring = time();
                                   $back_Command_Id = 0x80000004; //发送应答
                                   $num1            = substr($timestring, 0, 8);
                                   $num2            = substr($timestring, 8) . $this->combination($i);
                                   $new_bodyData    = pack("N", $num1) . pack("N", $num2);
                                   $new_bodyData    = $new_bodyData . pack('C', $status);
                                   $Total_Length = strlen($new_bodyData) + 12;
                                   $new_headData = pack("NNN", $Total_Length, $back_Command_Id, $head['Sequence_Id']);
                                   // socket_write($socket, $headData . $bodyData, $Total_Length);

                                   // print_r($back_Command_Id);
                                   // 向socket_accept的套接流写入信息，也就是回馈信息给socket_bind()所绑定的主机客户端
                                   // echo $new_headData . $new_bodyData."\n";
                                   // echo $back_Command_Id."\n";
                                   socket_write($accept_resource, $new_headData . $new_bodyData, $Total_Length);
                                   continue;
                               }
                           } */

                            print_r($body);
                            if ($body['Pk_total'] > 1) { //长短信

                                //DestUsr_tl接收用户数量
                                $Dest_terminal_Id = 21 * $body['DestUsr_tl']; // Dest_terminal_Id接收短信的 MSISDN 号码
                                $c_length         = $Dest_terminal_Id + 1;
                                $bodyData1        = socket_read($accept_resource, $c_length);
                                $body1            = unpack("a" . $Dest_terminal_Id . "Dest_terminal_Id/CMsg_length", $bodyData1);

                                $mobile      = $body1['Dest_terminal_Id'];
                                $Msg_length  = $body1['Msg_length'];
                                $bodyData2   = socket_read($accept_resource, $Msg_length);
                                //    print_r($bodyData2);die;
                                echo "\n";
                                $Msg_Content = unpack("a" . $Msg_length . "Msg_Content", $bodyData2);
                                $Msg_Content['Msg_Content'] = strval($Msg_Content['Msg_Content']);
                                // print_r($Msg_Content);die;
                                $udh      = unpack('c/c/c/c/c/c', $Msg_Content['Msg_Content']);
                                $message  = substr($Msg_Content['Msg_Content'], 6, 140);
                                $sendData = [];
                                if ($body['Msg_Fmt'] == 15) {
                                    $message = mb_convert_encoding($message, 'UTF-8', 'GBK');
                                } elseif ($body['Msg_Fmt'] == 0) {
                                    $message = $this->decode($message);
                                    // $de_ascii = mb_convert_encoding($de_ascii, 'UTF-8', 'GBK');

                                    //    $message = mb_convert_encoding($message, 'UTF-8', 'ASCII');
                                    $encode = mb_detect_encoding($message, array('ASCII', 'GB2312', 'GBK', 'UTF-8'));
                                    if ($encode != 'UTF-8') {
                                        $message = mb_convert_encoding($message, 'UTF-8', $encode);
                                    }
                                } elseif ($body['Msg_Fmt'] == 8) {
                                    $message = mb_convert_encoding($message, 'UTF-8', 'UCS-2');
                                }

                                $sendData = [
                                    'mobile'  => trim($mobile),
                                    'message' => $message,
                                    'Src_Id' => $body['Src_Id'], //拓展码
                                    'Service_Id' => $body['Service_Id'], //业务服务ID（企业代码）
                                    'Source_Addr' => $body['Msg_src'], //业务服务ID（企业代码）
                                ];
                                // print_r($sendData);
                                $residue = $head['Total_Length'] - 12 - 117 - $c_length - $Msg_length;
                                if ($residue > 0) {
                                    socket_read($accept_resource, $residue);
                                }
                                // die;
                            } else {
                                $Dest_terminal_Id = 21 * $body['DestUsr_tl']; //接收用户数量
                                $c_length         = $Dest_terminal_Id + 1;

                                $bodyData1 = socket_read($accept_resource, $c_length);
                                $body1     = unpack("a" . $Dest_terminal_Id . "Dest_terminal_Id/CMsg_length", $bodyData1);
                                $mobile      = $body1['Dest_terminal_Id'];
                                $Msg_length  = $body1['Msg_length'];
                                $bodyData2   = socket_read($accept_resource, $Msg_length);
                                //    print_r($bodyData2);die;
                                echo "\n";
                                $Msg_Content = unpack("a" . $Msg_length . "Msg_Content", $bodyData2);
                                $sendData    = [];
                                $message     = strval($Msg_Content['Msg_Content']);
                                if ($body['Msg_Fmt'] == 15) {
                                    $message = mb_convert_encoding($message, 'UTF-8', 'GBK');
                                } elseif ($body['Msg_Fmt'] == 0) { //ASCII进制码
                                    // $message = $this->decode($message);
                                    // $de_ascii = mb_convert_encoding($de_ascii, 'UTF-8', 'GBK');

                                    //    $message = mb_convert_encoding($message, 'UTF-8', 'ASCII');
                                    $encode = mb_detect_encoding($message, array('ASCII', 'GB2312', 'GBK', 'UTF-8'));
                                    // print_r($encode);die;
                                    if ($encode != 'UTF-8') {
                                        $message = mb_convert_encoding($message, 'UTF-8', $encode);
                                    }
                                } elseif ($body['Msg_Fmt'] == 8) { //USC2
                                    $message = mb_convert_encoding($message, 'UTF-8', 'UCS-2');
                                }
                                $sendData = [
                                    'mobile'  => trim($mobile),
                                    'message' => $message,
                                    'Src_Id' => $body['Src_Id'], //拓展码
                                    'Service_Id' => $body['Service_Id'], //业务服务ID（企业代码）
                                    'Source_Addr' => $body['Msg_src'], //业务服务ID（企业代码）
                                ];
                                // print_r($sendData);
                                $residue = $head['Total_Length'] - 12 - 117 - $c_length - $Msg_length;
                                if ($residue > 0) {
                                    socket_read($accept_resource, $residue);
                                }
                            }
                            $timestring = time();

                            $back_Command_Id = 0x80000004; //发送应答
                            $num1            = substr($timestring, 0, 8);
                            $num2            = substr($timestring, 8) . $this->combination($i);
                            $new_bodyData    = pack("N", $num1) . pack("N", $num2);
                            $new_bodyData    = $new_bodyData . pack('C', 0);
                            // $Total_Length = strlen($CMPP_SUBMIT_RESP) + 12;
                            // $RESP_headData     = pack("NNN", $Total_Length, $back_Command_Id, $head['Sequence_Id']);
                            // socket_write($accept_resource, $RESP_headData . $CMPP_SUBMIT_RESP, $Total_Length);
                            // print_r($sendData['mobile'].":".$id.":".$sendData['message'].":".$num1.$num2);die;
                            // $redis->rpush($redisMessageCodeSend,$uid.":".$sendData['mobile'].":".$sendData['message'].":".$num1.$num2.":".$addr); //三体营销通道
                            $sendData['send_msgid'][] = $num1 . $num2;
                            $sendData['uid']          = $uid;
                            $sendData['Submit_time']  = date('mdHis');
                            // $redis->rpush($redisMessageCodeSend.":1",json_encode($sendData)); //三体营销通道
                            $has_message = $redis->hget($redisMessageCodeSend . ":1", $head['Sequence_Id']);
                            if ($has_message) {
                                $has_message = json_decode($has_message, true);
                                $has_message['message'] .= $sendData['message'];
                                $has_message['send_msgid'][] = $num1 . $num2;
                                if ($Pk_total == $Pk_number) {
                                    $redis->hdel($redisMessageCodeSend . ":1", $head['Sequence_Id']);
                                    $redis->rpush($redisMessageCodeSendReal, json_encode($has_message));
                                } else {
                                    //三体营销通道
                                    $redis->hset($redisMessageCodeSend . ":1", $head['Sequence_Id'], json_encode($has_message));
                                }
                            } else {
                                if ($Pk_total == $Pk_number) {
                                    $redis->hdel($redisMessageCodeSend . ":1", $head['Sequence_Id']);
                                    $redis->rpush($redisMessageCodeSendReal, json_encode($sendData));
                                } else {
                                    //三体营销通道
                                    $redis->hset($redisMessageCodeSend . ":1", $head['Sequence_Id'], json_encode($sendData));
                                }
                                // $redis->hset($redisMessageCodeSend.":1",$head['Sequence_Id'],json_encode($sendData)); //三体营销通道
                            }
                            print_r($sendData);
                            $Total_Length = strlen($new_bodyData) + 12;
                            $new_headData = pack("NNN", $Total_Length, $back_Command_Id, $head['Sequence_Id']);
                            // socket_write($socket, $headData . $bodyData, $Total_Length);

                            // print_r($back_Command_Id);
                            // 向socket_accept的套接流写入信息，也就是回馈信息给socket_bind()所绑定的主机客户端
                            // echo $new_headData . $new_bodyData."\n";
                            // echo $back_Command_Id."\n";
                            socket_write($accept_resource, $new_headData . $new_bodyData, $Total_Length);
                        } else if ($head['Command_Id'] == 0x00000008) { //激活测试
                            $bodyData        = socket_read($accept_resource, $head['Total_Length'] - 12);
                            $new_bodyData    = $new_bodyData    = pack("a1", '');
                            $back_Command_Id = 0x80000008;
                            $Total_Length    = strlen($new_bodyData) + 12;
                            $new_headData    = pack("NNN", $Total_Length, $back_Command_Id, $head['Sequence_Id']);
                            // socket_write($socket, $headData . $bodyData, $Total_Length);

                            // print_r($back_Command_Id);
                            // 向socket_accept的套接流写入信息，也就是回馈信息给socket_bind()所绑定的主机客户端
                            // echo $new_headData . $new_bodyData."\n";
                            // echo $back_Command_Id."\n";
                            socket_write($accept_resource, $new_headData . $new_bodyData, $Total_Length);
                        } else { //其他
                            $bodyData        = socket_read($accept_resource, $head['Total_Length'] - 12);
                            $new_bodyData    = $new_bodyData    = pack("a1", '');
                            $back_Command_Id = 0x80000008;
                            $Total_Length    = strlen($new_bodyData) + 12;
                            $new_headData    = pack("NNN", $Total_Length, $back_Command_Id, $head['Sequence_Id']);
                            // socket_write($socket, $headData . $bodyData, $Total_Length);

                            // print_r($back_Command_Id);
                            // 向socket_accept的套接流写入信息，也就是回馈信息给socket_bind()所绑定的主机客户端
                            // echo $new_headData . $new_bodyData."\n";
                            // echo $back_Command_Id."\n";
                            socket_write($accept_resource, $new_headData . $new_bodyData, $Total_Length);
                        }
                        // socket_close($socket);

                        if ($status == 0) {
                            // $deliver = [];
                            // $deliver = [
                            //     'Stat'        => 'DELIVRD',
                            //     'Submit_time' => date('YMDHM', time()),
                            //     'Done_time'   => date('YMDHM', time()),
                            //     'mobile'      => '15201926171',
                            //     'send_msgid'  => [
                            //         "1574938367000004", "1574938367000006",
                            //     ],
                            // ];
                            // $redis->rPush('index:meassage:code:cmppdeliver:'.$uid,json_encode($deliver));
                            $deliver = $redis->lpop('index:meassage:game:cmppdeliver:45'); //取出用户发送任务
                            if (!empty($deliver)) {
                                $deliver            = json_decode($deliver, true);
                                $deliver_timestring = time();
                                $deliver_num1       = substr($deliver_timestring, 0, 8);
                                $deliver_num2       = substr($deliver_timestring, 8) . $this->combination($i);
                                $deliver_bodyData   = pack("N", $deliver_num1) . pack("N", $deliver_num2);
                                $deliver_bodyData .= pack('a21', '');
                                $deliver_bodyData .= pack('a10', $Service_Id);
                                $deliver_bodyData .= pack('C', 0);
                                $deliver_bodyData .= pack('C', 0);
                                $deliver_bodyData .= pack('C', 0); //Msg_Fmt
                                $deliver_bodyData .= pack('a21', $deliver['mobile']);
                                $deliver_bodyData .= pack('C', 1);
                                if (isset($deliver['send_msgid'])) {
                                    foreach ($deliver['send_msgid'] as $key => $value) {
                                        // print_r(substr($value,8,8));
                                        $send1 = substr($value, 0, 8);
                                        $send2 = substr($value, 8, 8);
                                        $deliver_Msg_Content = '';
                                        $deliver_Msg_Content = pack("N", $send1) . pack("N", $send2);
                                        $deliver_Msg_Content .= pack("a7", $deliver['Stat']);
                                        $deliver_Msg_Content .= pack("a10", $deliver['Submit_time']);
                                        $deliver_Msg_Content .= pack("a10", $deliver['Done_time']);
                                        $deliver_Msg_Content .= pack("a21", $deliver['mobile']);
                                        $deliver_Msg_Content .= pack("N", '');
                                        $deliver_Msg_Content_len = strlen($deliver_Msg_Content);
                                        $deliver_bodyData .= pack("C", $deliver_Msg_Content_len);
                                        $deliver_bodyData .= pack("a" . $deliver_Msg_Content_len, $deliver_Msg_Content);
                                        $deliver_bodyData .= pack("a8", '');
                                        $Total_Length = 0;
                                        $new_headData = '';
                                        $Total_Length = strlen($deliver_bodyData) + 12;

                                        $new_headData = pack("NNN", $Total_Length, 0x00000005, $Sequence_Id);
                                        // socket_write($socket, $headData . $bodyData, $Total_Length);

                                        // print_r($back_Command_Id);
                                        // 向socket_accept的套接流写入信息，也就是回馈信息给socket_bind()所绑定的主机客户端
                                        // echo $new_headData . $new_bodyData."\n";
                                        // echo $back_Command_Id."\n";
                                        socket_write($accept_resource, $new_headData . $deliver_bodyData, $Total_Length);
                                        // unset($deliver_Msg_Content);
                                    }
                                }
                            }
                            // die;


                        }
                    }
                    //捕获异常
                    catch (Exception $e) {
                        // exception($e);
                        $new_bodyData = pack("C", 1); //status | 1 | Unsigned Integer |状态 0：正确 1：消息结构错  2：非法源地址  3：认证错  4：版本太高   5~ ：其他错误
                        $Total_Length = strlen($new_bodyData) + 12;
                        $new_headData = pack("NNN", $Total_Length, 0x00000002, 1);
                        echo $new_headData . $new_bodyData . "\n";
                        echo 0x00000002 . "\n";
                        socket_write($accept_resource, $new_headData . $new_bodyData, $Total_Length);
                        socket_close($accept_resource);
                    }
                }
            }
            $i++;
            $Sequence_Id++;
            if ($i > 65536) {
                $time = 1;
                $i    = 1;
                $Sequence_Id    = 1;
            } else {
                $time = 0;
            }
            usleep(1100); //等待时间，进行下一次操作
        }
    }

    public function content($content)
    {
        if ($content == 1) { //本机测试
            return [
                'host'          => "127.0.0.1", //服务商ip
                'port'          => "8888", //短连接端口号   17890长连接端口号
                'Source_Addr'   => "101161", //企业id  企业代码
                'Shared_secret' => '5hsey6u9', //网关登录密码
                'Service_Id'    => "217062",
                'Dest_Id'       => "106928080159", //短信接入码 短信端口号
                'Sequence_Id'   => 1,
                'SP_ID'         => "",
                'bin_ip'        => ["127.0.0.1"], //客户端绑定IP
            ];
        }
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

    public function checkContent($bodyData, $commamd)
    {
        if ($commamd == 'CMPP_CONNECT') {
            $body = unpack("a6Source_Addr/a16AuthenticatorSource/CVersion/NTimestamp", $bodyData);
            print_r($body);
        }
    }

    function string2bytes($str)
    {
        $bytes = array();
        for ($i = 0; $i < strlen($str); $i++) {
            $tmp     = substr($str, $i, 1);
            $bytes[] = bin2hex($tmp);
        }
        return $bytes;
    }
}
