<?php

namespace app\console\com;

use app\console\Pzlife;
use cache\Phpredis;
use Config;
use Env;
use think\Db;

class ClientSocket extends Pzlife {

    protected $redis;

    private function redisInit() {
        $this->redis = Phpredis::getConn();
//        $this->connect = Db::connect(Config::get('database.db_config'));
    }


    public function Client($content) {
        //创建一个socket套接流
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        /****************设置socket连接选项，这两个步骤你可以省略*************/
        //接收套接流的最大超时时间1秒，后面是微秒单位超时时间，设置为零，表示不管它
        // socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array("sec" => 1, "usec" => 0));
        //发送套接流的最大超时时间为6秒
        // socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array("sec" => 6, "usec" => 0));
        /****************设置socket连接选项，这两个步骤你可以省略*************/
        $contdata = $this->content($content);
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        // print_r($contdata);die;
        $host          = $contdata['host']; //服务商ip
        $port          = $contdata['port']; //短连接端口号   17890长连接端口号
        $Source_Addr   = $contdata['Source_Addr']; //企业id  企业代码
        $Shared_secret = $contdata['Shared_secret']; //网关登录密码
        $Service_Id    = $contdata['Service_Id'];
        $Dest_Id       = $contdata['Dest_Id']; //短信接入码 短信端口号
        $Sequence_Id   = $contdata['Sequence_Id'];
        $SP_ID         = $contdata['SP_ID'];
        //连接服务端的套接流，这一步就是使客户端与服务器端的套接流建立联系
        // $host          = "116.62.88.162"; //服务商ip
        // $port          = "8592"; //短连接端口号   17890长连接端口号
        // $Source_Addr   = "101161"; //企业id  企业代码
        // $Shared_secret = '5hsey6u9'; //网关登录密码
        // $Service_Id    = "217062";
        // $Dest_Id       = "106928080159"; //短信接入码 短信端口号

        // $Sequence_Id   = 1;
        // $SP_ID         = "";
        // $host          = "127.0.0.1"; //服务商ip
        // $port          = "8888"; //短连接端口号   17890长连接端口号
        // $Source_Addr   = ""; //企业id  企业代码
        // $Shared_secret = ''; //网关登录密码
        // $Service_Id    = "";
        // $Dest_Id       = ""; //短信接入码 短信端口号
        $mobile = 15201926171;
        $code   = '短信发送测试';
        if (socket_connect($socket, $host, $port) == false) {
            echo 'connect fail massege:' . socket_strerror(socket_last_error());
        } else {
            // $message = 'l love you 我爱你';
            //转为GBK编码，处理乱码问题，这要看你的编码情况而定，每个人的编码都不同
            // $message = mb_convert_encoding($message, 'GBK', 'UTF-8');
            //向服务端写入字符串信息
            date_default_timezone_set('PRC');
            $Version             = 0x20;
            $Timestamp           = date('mdHis');
            $AuthenticatorSource = md5($Source_Addr . pack("a9", "") . $Shared_secret . $Timestamp, true);
            $bodyData            = pack("a6a16CN", $Source_Addr, $AuthenticatorSource, $Version, $Timestamp);
            $Command_Id          = 0x00000001;
            $Total_Length        = strlen($bodyData) + 12;
            $headData            = pack("NNN", $Total_Length, $Command_Id, $Sequence_Id);
            // print_r($headData);die;
            // socket_write($socket, $headData . $bodyData, $Total_Length);
            if (socket_write($socket, $headData . $bodyData, $Total_Length) == false) {
                echo 'fail to write' . socket_strerror(socket_last_error());
            } else {
                echo 'client write success' . PHP_EOL;
                //读取服务端返回来的套接流信息
                $headData = socket_read($socket, 1024);
                echo 'server return message is:' . PHP_EOL . $headData;
                // $headData = $callback;
                $head        = unpack("NTotal_Length/NCommand_Id/NSequence_Id", $headData);
                $Sequence_Id = $head['Sequence_Id'];
                $bodyData    = socket_read($socket, $head['Total_Length'] - 12);
                echo "CMPP_CONNECT_RESP success \n";
                // $body   = unpack("CStatus/a16AuthenticatorISMG/CVersion", $bodyData);
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
                $Command_Id   = 0x00000004; // 短信发送
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
                $socket   = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
                socket_connect($socket, $host, $port);
                socket_write($socket, $headData . $bodyData, $Total_Length);
                $headData = socket_read($socket, 1024);

                // do {
                //     $headData = socket_read($socket, 1024);
                // } while ($headData);
                echo 'client write success' . PHP_EOL . $headData;
                // socket_close($socket);//工作完毕，关闭套接流
                // $head = unpack("NTotal_Length/NCommand_Id/NSequence_Id", $headData);
                print_r($headData);
                // echo 1;
                // echo 'server return message is:' . PHP_EOL . $headData;
                // $headData = socket_read($socket, 1024);
                // print_r($headData);
                // print_r($head['Command_Id'] & 0x0fffffff);
                // switch ($head['Command_Id'] & 0x0fffffff) {
                // case 0x00000001:
                //     echo 1;
                //     break;
                // case 0x00000008:
                //     echo 2;
                //     $bodyData = pack("C", 1);
                //     break;
                // case 0x00000004:
                //     echo 3;
                //     break;
                // default:
                //     echo 4;
                //     $bodyData = pack("C", 1);
                //     break;
                // }

                // print_r($bodyData);
                // while ($headData = socket_read($socket, 12)) {
                // $headData = socket_read($socket, 1024);
                // echo 'server return message is:' . PHP_EOL . $headData;

                // }
            }
            // if (socket_write($socket, $message, strlen($message)) == false) {
            //     echo 'fail to write' . socket_strerror(socket_last_error());

            // } else {
            //     echo 'client write success' . PHP_EOL;
            //     //读取服务端返回来的套接流信息
            //     while ($callback = socket_read($socket, 1024)) {
            //         echo 'server return message is:' . PHP_EOL . $callback;
            //     }
            // }
        }
        $i = 1;
        do {
            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            socket_connect($socket, $host, $port);
            $AuthenticatorSource = md5($Source_Addr . pack("a9", "") . $Shared_secret . date('mdHis'), true);
            $bodyData            = pack("a6a16CN", $Source_Addr, $AuthenticatorSource, $Version, date('mdHis'));
            $Total_Length        = strlen($bodyData) + 12;
            $headData            = pack("NNN", $Total_Length, 0x80000001, 1);
            socket_write($socket, $headData . $bodyData, $Total_Length);
            //$i = $i-1;
            sleep(15); //等待时间，进行下一次操作
            echo 1;
        } while ($i > 0);
        // socket_close($socket);//工作完毕，关闭套接流

    }

    public function content($content) {
        if ($content == 1) { //测试
            return [
                'host'          => "127.0.0.1", //服务商ip
                'port'          => "8888", //短连接端口号   17890长连接端口号
                'Source_Addr'   => "", //企业id  企业代码
                'Shared_secret' => '', //网关登录密码
                'Service_Id'    => "",
                'Dest_Id'       => "", //短信接入码 短信端口号
                'Sequence_Id'   => 1,
                'SP_ID'         => "",
            ];
        } elseif ($content == 2) { //三体
            return [
                'host'          => "116.62.88.162", //服务商ip
                'port'          => "8592", //短连接端口号   17890长连接端口号
                'Source_Addr'   => "101161", //企业id  企业代码
                'Shared_secret' => '5hsey6u9', //网关登录密码
                'Service_Id'    => "217062",
                'Dest_Id'       => "106928080159", //短信接入码 短信端口号
                'Sequence_Id'   => 1,
                'SP_ID'         => "",
            ];

        }
    }

    public function SocketClientLong($content) {
        // $this->redisInit();
        $this->redis = Phpredis::getConn();
        $socket   = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
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

        $redisMessageCodeSend = Config::get('rediskey.message.redisMessageCodeSend');
        
        $mobile = 15201926171;
        $code   = '短信发送测试';
        $this->redis->rpush($redisMessageCodeSend,json_encode(['mobile' => $mobile,'code' => $code]));
        die;
        // $send = $this->redis->lPop($redisMessageCodeSend);
        // print_r($send);
        // die;
        if (socket_connect($socket, $host, $port) == false) {
            echo 'connect fail massege:' . socket_strerror(socket_last_error());
        } else {
            date_default_timezone_set('PRC');
            $i = 1;
            do {
                $Version             = 0x20;
                $Timestamp           = date('mdHis');
                $AuthenticatorSource = md5($Source_Addr . pack("a9", "") . $Shared_secret . $Timestamp, true);
                if ($i == 1) {
                    $bodyData            = pack("a6a16CN", $Source_Addr, $AuthenticatorSource, $Version, $Timestamp);
                    $Command_Id          = 0x00000001;
                    // $Total_Length        = strlen($bodyData) + 12;
                    $Sequence_Id         = 1;
                    // $headData            = pack("NNN", $Total_Length, $Command_Id, $Sequence_Id);
                    // print_r($headData);die;
                    // socket_write($socket, $headData . $bodyData, $Total_Length);
                }else{
                    //当有号码发送需求时 进行提交
                    $send = $this->redis->lPop($redisMessageCodeSend);
                    if ($send) {
                        $send = json_decode($send,true);
                        $mobile = $send['mobile'];
                        $code = $send['code'];
                        $Timestamp           = date('mdHis');
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
                        $Command_Id   = 0x00000004; // 短信发送
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
                    } else {
                        $bodyData            = pack("a6a16CN", $Source_Addr, $AuthenticatorSource, $Version, $Timestamp);
                        $Command_Id          = 0x80000001;
                        $Sequence_Id         = 1;
                    }
                    //没有号码发送时 发送连接请求
                }
                $Total_Length        = strlen($bodyData) + 12;
                $headData            = pack("NNN", $Total_Length, $Command_Id, 1);
                if (socket_write($socket, $headData . $bodyData, $Total_Length) == false) {
                    echo 'fail to write' . socket_strerror(socket_last_error());
                } else {
                    echo 'client write success' . PHP_EOL;
                    //读取服务端返回来的套接流信息
                    $headData = socket_read($socket, 1024);
                    echo 'server return message is:' . PHP_EOL . $headData;
                }
                $i++;
                echo $i."\n";
            } while (true);
            // while (true) {
                
            // }
        }

    }
}
