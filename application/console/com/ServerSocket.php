<?php

namespace app\console\com;

use app\console\Pzlife;
use cache\Phpredis;
use Config;
use Env;
use function Qiniu\json_decode;
use think\Db;

class ServerSocket extends Pzlife {

    // private $bodyData;

    public function Service($content) {
        $contdata = $this->content($content);
        $redis = Phpredis::getConn();
        $content = 4;//绑定通道
        $redisMessageCodeSend       = 'index:meassage:code:send:task'; //验证码发送任务rediskey
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
        $free_trial    = $contdata['free_trial']; //是否需要审核 1:需要审核;2:无需审核
        $master_num    = $contdata['master_num']; //通道最大提交量
        $uid           = $contdata['uid']; //通道最大提交量
        $security_coefficient = 0.8; //通道饱和系数
        $security_master      = $master_num * $security_coefficient;
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        date_default_timezone_set('PRC');
        /*绑定接收的套接流主机和端口,与客户端相对应*/
        if (socket_bind($socket, $host, $port) == false) {
            echo 'server bind fail:' . socket_strerror(socket_last_error());
            /*这里的127.0.0.1是在本地主机测试，你如果有多台电脑，可以写IP地址*/
        }
        //监听套接流
        if (socket_listen($socket, 4) == false) {
            echo 'server listen fail:' . socket_strerror(socket_last_error());
        }
        /*接收客户端传过来的信息*/
        $accept_resource = socket_accept($socket);
        socket_set_nonblock($accept_resource); //设置非阻塞模式
        /*socket_accept的作用就是接受socket_bind()所绑定的主机发过来的套接流*/

        if ($accept_resource !== false) {
            $headData = socket_read($accept_resource, 12);
            if ($headData != false) {
                $head = unpack("NTotal_Length/NCommand_Id/NSequence_Id", $headData);
                // $bodyData = socket_read($accept_resource, $head['Total_Length'] - 12);
                print_r($head);
                // print_r($bodyData);
                // echo "\n";
                //获取请求源ip
                socket_getpeername($accept_resource, $addr, $por);
                // echo $addr;die;
                $bodyData = socket_read($accept_resource, $head['Total_Length'] - 12);
                try
                {
                    // $head = unpack("NTotal_Length/NCommand_Id/NSequence_Id", $headData);
                    // print_r($head);
                    if ($head['Command_Id'] == 0x00000001) { //请求链接
                        $status       = 0;
                        $new_bodyData = pack("C", 0); //status | 1 | Unsigned Integer |状态 0：正确 1：消息结构错  2：非法源地址  3：认证错  4：版本太高   5~ ：其他错误
                        try
                        {
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
                            //加密验证
                            $Timestamp = date('mdHis');
                            if ($body['AuthenticatorSource'] != md5($Source_Addr . pack("a9", "") . $Shared_secret . $Timestamp, true)) {
                                $status       = 3;
                                $new_bodyData = pack("C", 3); //status | 1 | Unsigned Integer |状态 0：正确 1：消息结构错  2：非法源地址  3：认证错  4：版本太高   5~ ：其他错误
                            }
                            // echo $status;
                            // print_r($head);
                            // print_r($body);
                            // die;
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
                        // socket_write($socket, $headData . $bodyData, $Total_Length);

                        // print_r($back_Command_Id);
                        // 向socket_accept的套接流写入信息，也就是回馈信息给socket_bind()所绑定的主机客户端
                        // echo $new_headData . $new_bodyData."\n";
                        // echo $back_Command_Id."\n";
                        socket_write($accept_resource, $new_headData . $new_bodyData, $Total_Length);
                        // socket_write的作用是向socket_create的套接流写入信息，或者向socket_accept的套接流写入信息
                    }
                    // socket_close($socket);

                    if ($status == 0) {
                        $i = 1;
                        $time = 0;
                        do {
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

                                if ($head['Command_Id'] == 0x00000001) {
                                    $bodyData       = socket_read($accept_resource, $head['Total_Length'] - 12);
                                    $connect_status = 0;
                                    $new_bodyData   = pack("C", 0); //status | 1 | Unsigned Integer |状态 0：正确 1：消息结构错  2：非法源地址  3：认证错  4：版本太高   5~ ：其他错误
                                    $new_bodyData   = pack("C", 0); //status | 1 | Unsigned Integer |状态 0：正确 1：消息结构错  2：非法源地址  3：认证错  4：版本太高   5~ ：其他错误
                                    try
                                    {
                                        // $bodyData = socket_read($accept_resource, $head['Total_Length'] - 12);
                                        $body = unpack("a6Source_Addr/a16AuthenticatorSource/CVersion/NTimestamp", $bodyData);
                                        //ip地址绑定
                                        if (!in_array($addr, $bin_ip)) {
                                            $connect_status = 2;
                                            $new_bodyData   = pack("C", 2); //status | 1 | Unsigned Integer |状态 0：正确 1：消息结构错  2：非法源地址  3：认证错  4：版本太高   5~ ：其他错误
                                        }
                                        if ($body['Version'] != 0x20) { //验证版本
                                            $connect_status = 4;
                                            $new_bodyData   = pack("C", 4); //status | 1 | Unsigned Integer |状态 0：正确 1：消息结构错  2：非法源地址  3：认证错  4：版本太高   5~ ：其他错误
                                        }
                                        //加密验证
                                        $Timestamp = date('mdHis');
                                        if ($body['AuthenticatorSource'] != md5($Source_Addr . pack("a9", "") . $Shared_secret . $Timestamp, true)) {
                                            $connect_status = 3;
                                            $new_bodyData   = pack("C", 3); //status | 1 | Unsigned Integer |状态 0：正确 1：消息结构错  2：非法源地址  3：认证错  4：版本太高   5~ ：其他错误
                                        }
                                        // echo $status;
                                        // print_r($head);
                                        // print_r($body);
                                        // die;
                                    } catch (Exception $e) {
                                        $connect_status = 1;
                                        $new_bodyData   = pack("C", 1); //status | 1 | Unsigned Integer |状态 0：正确 1：消息结构错  2：非法源地址  3：认证错  4：版本太高   5~ ：其他错误
                                    }

                                    $back_Command_Id   = 0x80000001; //连接应答
                                    $AuthenticatorISMG = pack("a16", ''); //AuthenticatorISMG | 16 | Octet String | ISMG 认证码，用于鉴别 ISMG。 其值通过单向 MD5 hash 计算得出， 表示如下： AuthenticatorISMG =MD5 （Status+AuthenticatorSource+shared secret），Shared secret 由中国移动 与源地址实体事先商定， AuthenticatorSource 为源地址实体 发送给 ISMG 的对应消息 CMPP_Connect 中的值。  认证出错时，此项为空。
                                    if ($status != 3) {
                                        $AuthenticatorISMG = pack("a16", md5($status . $bodyData . $Shared_secret, true));
                                    }
                                    $new_bodyData = $new_bodyData . $AuthenticatorISMG . pack("C", 0x20);
                                    // echo $new_bodyData;die;

                                    if ($connect_status != 0) {
                                        socket_close($accept_resource); //验证失败 关闭连接
                                    }
                                } else if ($head['Command_Id'] == 0x00000004) {
                                    // $contentlen = $head['Total_Length'] - 12 - 116;
                                    $bodyData = socket_read($accept_resource, 117);
                                    $body     = unpack("N2Msg_Id/CPk_total/CPk_number/CRegistered_Delivery/CMsg_level/a10Service_Id/CFee_UserType/a21Fee_terminal_Id/CTP_pId/CTP_udhi/CMsg_Fmt/a6Msg_src/a2FeeType/a6FeeCode/a17ValId_Time/a17At_Time/a21Src_Id/CDestUsr_tl", $bodyData);

                                    if ($body['Pk_total'] > 1) { //长短信
                                        print_r($body);
                                    } else {
                                        // print_r($body);die;
                                        $Dest_terminal_Id = 21 * $body['DestUsr_tl'];
                                        $c_length         = $Dest_terminal_Id + 1;

                                        $bodyData1 = socket_read($accept_resource, $c_length);
                                        $body1     = unpack("a" . $Dest_terminal_Id . "Dest_terminal_Id/CMsg_length", $bodyData1);

                                        $mobile      = $body1['Dest_terminal_Id'];
                                        $Msg_length  = $body1['Msg_length'];
                                        $bodyData2   = socket_read($accept_resource, $Msg_length);
                                        $Msg_Content = unpack("a" . $Msg_length . "Msg_Content", $bodyData2);
                                        $sendData    = [];
                                        $message     = $Msg_Content['Msg_Content'];
                                        if ($body['Msg_Fmt'] == 15){
                                            $message = mb_convert_encoding($message, 'UTF-8', 'GBK');
                                        }
                                        $sendData = [
                                            'mobile'  => trim($mobile),
                                            'message' => $message,
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
                                        $redis->rpush($redisMessageCodeSend,$uid.":".$sendData['mobile'].":".$sendData['message'].":".$num1.$num2); //三体营销通道
                                        $Total_Length = strlen($new_bodyData) + 12;
                                        $new_headData = pack("NNN", $Total_Length, $back_Command_Id, $head['Sequence_Id']);
                                        // socket_write($socket, $headData . $bodyData, $Total_Length);
            
                                        // print_r($back_Command_Id);
                                        // 向socket_accept的套接流写入信息，也就是回馈信息给socket_bind()所绑定的主机客户端
                                        // echo $new_headData . $new_bodyData."\n";
                                        // echo $back_Command_Id."\n";
                                        socket_write($accept_resource, $new_headData . $new_bodyData, $Total_Length);
                                } else if ($head['Command_Id'] == 0x00000008) {
                                    $bodyData        = socket_read($accept_resource, $head['Total_Length'] - 12);
                                    $new_bodyData    = $new_bodyData    = pack("a1", '');
                                    $back_Command_Id = 0x80000008;
                                    $Total_Length = strlen($new_bodyData) + 12;
                                    $new_headData = pack("NNN", $Total_Length, $back_Command_Id, $head['Sequence_Id']);
                                    // socket_write($socket, $headData . $bodyData, $Total_Length);
        
                                    // print_r($back_Command_Id);
                                    // 向socket_accept的套接流写入信息，也就是回馈信息给socket_bind()所绑定的主机客户端
                                    // echo $new_headData . $new_bodyData."\n";
                                    // echo $back_Command_Id."\n";
                                    socket_write($accept_resource, $new_headData . $new_bodyData, $Total_Length);
                                } else {
                                    $bodyData        = socket_read($accept_resource, $head['Total_Length'] - 12);
                                    $new_bodyData    = $new_bodyData    = pack("a1", '');
                                    $back_Command_Id = 0x80000008;
                                    $Total_Length = strlen($new_bodyData) + 12;
                                    $new_headData = pack("NNN", $Total_Length, $back_Command_Id, $head['Sequence_Id']);
                                    // socket_write($socket, $headData . $bodyData, $Total_Length);
        
                                    // print_r($back_Command_Id);
                                    // 向socket_accept的套接流写入信息，也就是回馈信息给socket_bind()所绑定的主机客户端
                                    // echo $new_headData . $new_bodyData."\n";
                                    // echo $back_Command_Id."\n";
                                    socket_write($accept_resource, $new_headData . $new_bodyData, $Total_Length);
                                }
                                

                            }
                          
                            $i++;
                            if ($i > $security_master) {
                                $time = 1;
                                $i    = 1;
                            }
                            // sleep($time); //等待时间，进行下一次操作
                        } while (true);
                    }
                }
                //捕获异常
                 catch (Exception $e) {
                    exception($e);
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
        //让服务器无限获取客户端传过来的信息
        /*   do {

        if ($accept_resource !== false) {
        // 读取客户端传过来的资源，并转化为字符串
        $headData = socket_read($accept_resource, 12);
        // socket_read的作用就是读出socket_accept()的资源并把它转化为字符串
        // $v = base_convert($string, 16, 2);
        // echo $headData."\n";
        // $head = unpack("NTotal_Length/NCommand_Id/NSequence_Id", $headData);
        // $bodyData = socket_read($accept_resource, $head['Total_Length'] - 12);
        // echo $bodyData."\n";
        // continue;
        // echo 'server receive is :' . $v . PHP_EOL; //PHP_EOL为php的换行预定义常量
        if ($headData != false) {
        $head = unpack("NTotal_Length/NCommand_Id/NSequence_Id", $headData);
        // $bodyData = socket_read($accept_resource, $head['Total_Length'] - 12);
        print_r($head);
        // print_r($bodyData);
        // echo "\n";
        //获取请求源ip
        socket_getpeername($accept_resource, $addr, $por);
        // echo $addr;die;
        $bodyData = socket_read($accept_resource, $head['Total_Length'] - 12);
        //错误处理机制
        try
        {
        // $head = unpack("NTotal_Length/NCommand_Id/NSequence_Id", $headData);
        // print_r($head);
        if ($head['Command_Id'] == 0x00000001) {
        $status = 0;
        $new_bodyData = pack("C",0);//status | 1 | Unsigned Integer |状态 0：正确 1：消息结构错  2：非法源地址  3：认证错  4：版本太高   5~ ：其他错误

        }
        switch ($head['Command_Id'] & 0x0fffffff) {
        case 0x00000001:
        // $body = unpack("CStatus/a16AuthenticatorISMG/CVersion", $bodyData);//收到连接请求

        // print_r($bodyData);die;
        // $this->checkContent($body, "CMPP_CONNECT");
        $status = 0;
        $new_bodyData = pack("C",0);//status | 1 | Unsigned Integer |状态 0：正确 1：消息结构错  2：非法源地址  3：认证错  4：版本太高   5~ ：其他错误
        try
        {
        // $bodyData = socket_read($accept_resource, $head['Total_Length'] - 12);
        $body     = unpack("a6Source_Addr/a16AuthenticatorSource/CVersion/NTimestamp", $bodyData);
        //ip地址绑定
        if (!in_array($addr, $bin_ip)) {
        $status = 2;
        $new_bodyData = pack("C",2);//status | 1 | Unsigned Integer |状态 0：正确 1：消息结构错  2：非法源地址  3：认证错  4：版本太高   5~ ：其他错误
        }
        if ($body['Version'] !== 0x20) { //验证版本
        $status = 4;
        $new_bodyData = pack("C",4);//status | 1 | Unsigned Integer |状态 0：正确 1：消息结构错  2：非法源地址  3：认证错  4：版本太高   5~ ：其他错误
        }
        //加密验证
        $Timestamp = date('mdHis');
        if ($body['AuthenticatorSource'] != md5($Source_Addr . pack("a9", "") . $Shared_secret . $Timestamp, true)) {
        $status = 3;
        $new_bodyData = pack("C",3);//status | 1 | Unsigned Integer |状态 0：正确 1：消息结构错  2：非法源地址  3：认证错  4：版本太高   5~ ：其他错误
        }
        // echo $status;
        // print_r($head);
        // print_r($body);
        // die;
        } catch (Exception $e) {
        $status = 1;
        $new_bodyData = pack("C",1);//status | 1 | Unsigned Integer |状态 0：正确 1：消息结构错  2：非法源地址  3：认证错  4：版本太高   5~ ：其他错误
        }

        $back_Command_Id = 0x80000001; //连接应答
        // echo $status;
        $AuthenticatorISMG = pack("a16",'');//AuthenticatorISMG | 16 | Octet String | ISMG 认证码，用于鉴别 ISMG。 其值通过单向 MD5 hash 计算得出， 表示如下： AuthenticatorISMG =MD5 （Status+AuthenticatorSource+shared secret），Shared secret 由中国移动 与源地址实体事先商定， AuthenticatorSource 为源地址实体 发送给 ISMG 的对应消息 CMPP_Connect 中的值。  认证出错时，此项为空。
        if ($status !=3 ) {
        $AuthenticatorISMG = pack("a16",md5($status.$bodyData.$Shared_secret,true));
        }
        $new_bodyData = $new_bodyData.$AuthenticatorISMG.pack("C",0x20);
        // echo $new_bodyData;die;
        break;
        case 0x00000004;
        // $new_bodyData        = pack("C", 1);
        $contentlen = $head['Total_Length'] - 12 - 116;
        $body     = unpack("a8Msg_Id/CPk_total/CPk_number/CRegistered_Delivery/CMsg_level/a10Service_Id/CFee_UserType/a21Fee_terminal_Id/CTP_pId/CTP_udhi/CMsg_Fmt/a6Msg_src/a2FeeType/a6FeeCode/a17ValId_Time/a17At_Time/a21Src_Id/CDestUsr_tl/a21".$contentlen."/CMsg_Length/a8Reserve", $bodyData);
        print_r($body);die;
        $back_Command_Id = 0x80000004; //发送应答

        break;
        case 0x00000008; //保持连接
        $new_bodyData        = pack("C", 1);
        $back_Command_Id = 0x80000008; //连接应答

        break;
        default:
        $new_bodyData        = pack("C", 1);
        $back_Command_Id = 0x80000008; //连接应答
        break;
        }
        // socket_close($socket);
        $Total_Length = strlen($new_bodyData) + 12;
        $new_headData     = pack("NNN", $Total_Length, $back_Command_Id, $head['Sequence_Id']);
        // socket_write($socket, $headData . $bodyData, $Total_Length);

        // print_r($back_Command_Id);
        // 向socket_accept的套接流写入信息，也就是回馈信息给socket_bind()所绑定的主机客户端
        // echo $new_headData . $new_bodyData."\n";
        // echo $back_Command_Id."\n";
        socket_write($accept_resource, $new_headData . $new_bodyData, $Total_Length);
        // socket_write的作用是向socket_create的套接流写入信息，或者向socket_accept的套接流写入信息
        }
        //捕获异常
        catch (Exception $e) {
        exception($e);
        $new_bodyData = pack("C",1);//status | 1 | Unsigned Integer |状态 0：正确 1：消息结构错  2：非法源地址  3：认证错  4：版本太高   5~ ：其他错误
        $Total_Length = strlen($new_bodyData) + 12;
        $new_headData     = pack("NNN", $Total_Length, 0x00000002, 1);
        echo $new_headData . $new_bodyData."\n";
        echo 0x00000002."\n";
        socket_write($accept_resource, $new_headData . $new_bodyData, $Total_Length);
        socket_close($accept_resource);
        }

        } else {
        echo 'socket_read is fail'."\n";
        }
        // socket_close的作用是关闭socket_create()或者socket_accept()所建立的套接流
        // socket_close($accept_resource);
        }
        } while (true); */
        // socket_close($socket);
    }

    public function content($content) {//客户绑定ip
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
                'free_trial'    => 2,
                'master_num'    => 300,
                'uid'           => 1,
            ];
        }
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

    public function checkContent($bodyData, $commamd) {
        if ($commamd == 'CMPP_CONNECT') {
            $body = unpack("a6Source_Addr/a16AuthenticatorSource/CVersion/NTimestamp", $bodyData);
            print_r($body);
        }
    }

    function string2bytes($str) {
        $bytes = array();
        for ($i = 0; $i < strlen($str); $i++) {
            $tmp     = substr($str, $i, 1);
            $bytes[] = bin2hex($tmp);
        }
        return $bytes;
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
}
