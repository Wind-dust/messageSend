<?php

namespace app\console\com;

use app\console\Pzlife;
use cache\Phpredis;
use Config;
use Env;
use Exception;
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
                'Source_Addr'   => "101161", //企业id  企业代码
                'Shared_secret' => '5hsey6u9', //网关登录密码
                'Service_Id'    => "217062",
                'Dest_Id'       => "106928080159", //短信接入码 短信端口号
                'Sequence_Id'   => 1,
                'SP_ID'         => "",
                'master_num'    => 300,
            ];
        } elseif ($content == 2) { //三体行业
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
        } else if ($content == 3) { // 三体营销
            return [
                'host'          => "116.62.88.162", //服务商ip
                'port'          => "8592", //短连接端口号   17890长连接端口号
                'Source_Addr'   => "101162", //企业id  企业代码
                'Shared_secret' => 'uc338qd7', //网关登录密码
                'Service_Id'    => "101162", //业务代码
                'template_id'   => "217800", //模板id
                'Dest_Id'       => "106928080158", //短信接入码 短信端口号 服务代码
                'Sequence_Id'   => 1,
                'SP_ID'         => "",
                'master_num'    => 300,
            ];
        } else if ($content == 4) { //青年科技移动营销
            return [
                'host'          => "47.96.157.156", //服务商ip
                'port'          => "7890", //短连接端口号   17890长连接端口号
                'Source_Addr'   => "997476", //企业id  企业代码
                'Shared_secret' => '47TtFd', //网关登录密码
                'Service_Id'    => "997476", //业务代码
                'template_id'   => "", //模板id
                'Dest_Id'       => "1069030", //短信接入码 短信端口号 服务代码
                'Sequence_Id'   => 1,
                'SP_ID'         => "",
                'master_num'    => 300,
            ];
        } else if ($content == 5) { //青年科技移动联通营销
            return [
                'host'          => "47.96.157.156", //服务商ip
                'port'          => "7890", //短连接端口号   17890长连接端口号
                'Source_Addr'   => "997475", //企业id  企业代码
                'Shared_secret' => 'SiC67Z', //网关登录密码
                'Service_Id'    => "997475", //业务代码
                'template_id'   => "", //模板id
                'Dest_Id'       => "1069029", //短信接入码 短信端口号 服务代码
                'Sequence_Id'   => 1,
                'SP_ID'         => "",
                'master_num'    => 200,
            ];
        } else if ($content == 6) { //青年科技三网行业
            return [
                'host'          => "47.96.157.156", //服务商ip
                'port'          => "7890", //短连接端口号   17890长连接端口号
                'Source_Addr'   => "997474", //企业id  企业代码
                'Shared_secret' => 'Yhdbbn', //网关登录密码
                'Service_Id'    => "997474", //业务代码
                'template_id'   => "", //模板id
                'Dest_Id'       => "1069024", //短信接入码 短信端口号 服务代码
                'Sequence_Id'   => 1,
                'SP_ID'         => "",
                'master_num'    => 500,
            ];
        }
    }

    public function SocketClientLong($content) {
        // $this->redisInit();
        $redis = Phpredis::getConn();
        // $a_time = 0;

        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G

        // $redisMessageCodeSend = Config::get('rediskey.message.redisMessageCodeSend');
        // $code   = '短信发送测试';

        // echo $code;
        // die;
        // print_r($redisMessageCodeSend);die;
        // print_r(json_encode(['mobile' => $mobile,'code' => $code]));die;
        // $redis->rpush($redisMessageCodeSend,json_encode(['mobile' => $mobile,'code' => $code]));
        $socket   = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
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
            date_default_timezone_set('PRC');
            // socket_read($socket,3072);
            // die;//关闭socket连接，清除缓存数据
            $i           = 1;
            $Sequence_Id = 1;
            do {
                $time                = 0;
                $Version             = 0x20; //CMPP版本 0x20 2.0版本 0x30 3.0版本
                $Timestamp           = date('mdHis');
                $AuthenticatorSource = md5($Source_Addr . pack("a9", "") . $Shared_secret . $Timestamp, true);
                if ($i == 1) {
                    $bodyData   = pack("a6a16CN", $Source_Addr, $AuthenticatorSource, $Version, $Timestamp);
                    $Command_Id = 0x00000001;
                    // $Total_Length        = strlen($bodyData) + 12;

                    // $headData            = pack("NNN", $Total_Length, $Command_Id, $Sequence_Id);
                    // print_r($headData);die;
                    // socket_write($socket, $headData . $bodyData, $Total_Length);
                    
                } else {
                    //当有号码发送需求时 进行提交
                    /* redis 读取需要发送的数据 */
                    // $send = $this->redis->lPop($redisMessageCodeSend);
                    // $send = [];

                    // if ($i == 2) { //测试判断语句
                        
                    if ($i) {//正式使用从缓存中读取数据
                        // $send = json_decode($send,true);
                        // $mobile = $send['mobile'];
                        // $code = $send['code'];
                        $mobile = 15201926171;
                        $code   = '【气象祝福】阳光眷顾，天空展颜一片蔚蓝，但昼夜温差较大，极易发生感冒，请注意增减衣服保暖防寒，祝您身体健康。 '; //带签名
                        // $code   = '短信发送测试'; //带签名
                        // print_r($code);die;
                        $code = mb_convert_encoding($code, 'GBK', 'UTF-8');
                        // $Timestamp = date('mdHis');
                        $uer_num = 1; //本批接受信息的用户数量（一般小于100个用户，不同通道承载能力不同）
                        // $Msg_Id = rand(1, 100);
                        // $Msg_Id   = '';
                        // $Msg_Id   = time().$mobile;
                        $Msg_Id   = strval(time()) . $i;
                        $bodyData = pack("a8", $Msg_Id);
                        // $bodyData = (pack('I',pack("a8", $Msg_Id))); //Msg_Id |Unsigned Integer |8 | 信息标识，由 SP 侧短信网关本身产生， 本处填空
                        $bodyData = $bodyData . pack('C', 1); //Pk_total |Unsigned Integer |1 |相同 Msg_Id 的信息总条数，从 1 开始
                        $bodyData = $bodyData . pack('C', 1); //Pk_number |Unsigned Integer |1 |相同 Msg_Id 的信息序号，从 1 开始
                        $bodyData = $bodyData . pack('C', 1); //Registered_Delivery |Unsigned Integer| 1| 是否要求返回状态确认报告： 0：不需要 1：需要 2：产生 SMC 话单 （该类型短信仅供网关计费使用，不发 送给目的终端)
                        $bodyData = $bodyData . pack('C', ''); //Msg_level |Unsigned Integer| 1 |信息级别
                        // $bodyData .= pack("a10", $Source_Addr); //可以为企业代码
                        $bodyData = $bodyData . pack("a10", $Service_Id); //Service_Id |Octet String| 10 |业务类型，是数字、字母和符号的组合。
                        $bodyData = $bodyData . pack('C', ''); //Fee_UserType  |Unsigned Integer | 1|计费用户类型字段 0：对目的终端 MSISDN 计费； 1：对源终端 MSISDN 计费； 2：对 SP 计费; 3：表示本字段无效，对谁计费参见 Fee_terminal_Id 字段。

                        $bodyData = $bodyData . pack("a21", $mobile); //Fee_terminal_Id |21 Unsigned Integer |被计费用户的号码（如本字节填空，则表 示本字段无效，对谁计费参见 Fee_UserType 字段，本字段与 Fee_UserType 字段互斥）
                        $bodyData = $bodyData . pack("C", 0); //TP_pId |1 |Unsigned Integer |GSM协议类型。详细是解释请参考 GSM03.40 中的 9.2.3.9

                        /**
                         * TP_udhi ：0代表内容体里不含有协议头信息
                         * 1代表内容含有协议头信息（长短信，push短信等都是在内容体上含有头内容的）当设置内容体包含协议头
                         * ，需要根据协议写入相应的信息，长短信协议头有两种：
                         * 6位协议头格式：05 00 03 XX MM NN
                         * byte 1 : 05, 表示剩余协议头的长度
                         * byte 2 : 00, 这个值在GSM 03.40规范9.2.3.24.1中规定，表示随后的这批超长短信的标识位长度为1（格式中的XX值）。
                         * byte 3 : 03, 这个值表示剩下短信标识的长度
                         * byte 4 : XX，这批短信的唯一标志，事实上，SME(手机或者SP)把消息合并完之后，就重新记录，所以这个标志是否唯 一并不是很 重要。
                         * byte 5 : MM, 这批短信的数量。如果一个超长短信总共5条，这里的值就是5。
                         * byte 6 : NN, 这批短信的数量。如果当前短信是这批短信中的第一条的值是1，第二条的值是2。
                         * 例如：05 00 03 39 02 01
                         *
                         * 7 位的协议头格式：06 08 04 XX XX MM NN
                         * byte 1 : 06, 表示剩余协议头的长度
                         * byte 2 : 08, 这个值在GSM 03.40规范9.2.3.24.1中规定，表示随后的这批超长短信的标识位长度为2（格式中的XX值）。
                         * byte 3 : 04, 这个值表示剩下短信标识的长度
                         * byte 4-5 : XX
                         * XX，这批短信的唯一标志，事实上，SME(手机或者SP)把消息合并完之后，就重新记录，所以这个标志是否唯一并不是很重要。
                         * byte 6 : MM, 这批短信的数量。如果一个超长短信总共5条，这里的值就是5。
                         * byte 7 : NN, 这批短信的数量。如果当前短信是这批短信中的第一条的值是1，第二条的值是2。
                         * 例如：06 08 04 00 39 02 01
                         **/
                        /* 字符串长度（包括中文）超出70字 为长短信 超过70字的，拆成多条发送，一般使用6位协议头，每条短信除去6字节协议头，剩余134字节存放剩余内容 */
                        /* 一般在发长短信的时候，tp_udhi设置为1，然后短信内容需要拆分成多条，每条内容之前，加上协议头 */
                        $bodyData = $bodyData . pack("C", 0); //TP_udhi |1 |Unsigned |Integer |GSM协议类型。详细是解释请参考 GSM03.40 中的 9.2.3.23,仅使用 1 位，右 对齐
                        $bodyData = $bodyData . pack("C", 15); //Msg_Fmt |1 |Unsigned |Integer |信息格式   0：ASCII 串   3：短信写卡操作   4：二进制信息   8：UCS2 编码 15：含 GBK 汉字(GBK编码内容与Msg_Fmt一致)

                        $bodyData = $bodyData . pack("a6", $Source_Addr); //Msg_src |6 |Octet String |信息内容来源(账号)
                        $bodyData = $bodyData . pack("a2", 02);
                        //FeeType |2 |Octet String |资费类别 01：对“计费用户号码”免费 02：对“计费用户号码”按条计信息费 03：对“计费用户号码”按包月收取信息 费 04：对“计费用户号码”的信息费封顶 05：对“计费用户号码”的收费是由 SP 实现
                        $bodyData = $bodyData . pack("a6", ''); // FeeCode |6 |Octet String |资费代码（以分为单位）
                        $bodyData = $bodyData . pack("a17", ''); //ValId_Time |17 |Octet |String 存活有效期，格式遵循 SMPP3.3 协议
                        $bodyData = $bodyData . pack("a17", ''); //At_Time |17 |Octet String |定时发送时间，格式遵循 SMPP3.3 协议
                        $bodyData = $bodyData . pack("a21", $Dest_Id); //Src_Id |21 |Octet String |源号码 SP 的服务代码或前缀为服务代码的长号 码, 网关将该号码完整的填到 SMPP 协议 Submit_SM消息相应的source_addr字段， 该号码最终在用户手机上显示为短消息 的主叫号码 (接入码)
                        $bodyData = $bodyData . pack("C", $uer_num); //DestUsr_tl |1 |Unsigned Integer |接收信息的用户数量(小于 100 个用户)
                        $p_n      = 21 * $uer_num;
                        $bodyData = $bodyData . pack("a" . $p_n, $mobile); //Dest_terminal_Id | 21*DestUsr_tl |Octet String |接收短信的 MSISDN 号码
                        $len      = strlen($code);
                        $bodyData = $bodyData . pack("C", $len); //Msg_Length |1 |Unsigned Integer |信息长度(Msg_Fmt 值为 0 时：<160 个字 节；其它<=140 个字节)
                        $bodyData = $bodyData . pack("a" . $len, $code); // Msg_Content |Msg_length |Octet String |信息内容
                        $bodyData = $bodyData . pack("a8", '');
                        // $bodyData = $bodyData . pack('I',pack("a8", '')); //Reserve |8 |Octet String |保留

                        // $bodyData = pack("a8", $Msg_Id);
                        /*    $bodyData = pack("N", $Msg_Id) . pack("N", "00000000");
                        $bodyData .= pack("C", 1) . pack("C", 1);
                        $bodyData .= pack("C", 0) . pack("C", 0);
                        $bodyData .= pack("a10", $Service_Id);
                        $bodyData .= pack("C", 0) . pack("a32", "") . pack("C", 0) . pack("C", 0) . pack("C", 0) . pack("C", 0) . pack("a6", $SP_ID) . pack("a2", "02") . pack("a6", "") . pack("a17", "") . pack("a17", "") . pack("a21", $Dest_Id) . pack("C", 1);
                        $bodyData .= pack("a32", $mobile);
                        $bodyData .= pack("C", 0);
                        $len = strlen($code);
                        $bodyData .= pack("C", $len);
                        $bodyData .= pack("a" . $len, $code);
                        $bodyData .= pack("a20", "00000000000000000000"); */
                        // print_r($bodyData)."\n";
                        // send($bodyData, "CMPP_SUBMIT", $Msg_Id);
                        
                        $Command_Id = 0x00000004; // 短信发送
                        if ($Msg_Id != 0) {
                            $Sequence_Id = $Msg_Id;
                        } else {
                            if ($Sequence_Id < pow(2, 16) - 1) {
                                $Sequence_Id = $Sequence_Id;
                            } else {
                                $Sequence_Id = 1;
                            }
                            $Sequence_Id = $Sequence_Id + 1;
                        }
                        $time = 0;
                        if ($i > $security_master) {
                            $time = 1;
                            $i    = 0;
                        }
                        // echo $Command_Id;die;
                        // print_r(strlen($bodyData));die;
                    } else {
                        $bodyData    = pack("a6a16CN", $Source_Addr, $AuthenticatorSource, $Version, $Timestamp);
                        $Command_Id  = 0x00000008; //保持连接
                        $Sequence_Id = $Sequence_Id + 1;
                        $time        = 15;
                    }
                    //没有号码发送时 发送连接请求
                }
                $Total_Length = strlen($bodyData) + 12;
                $headData     = pack("NNN", $Total_Length, $Command_Id, $Sequence_Id);
                // if ($i == 2) {
                    // echo $headData.$bodyData."\n";
                // }
                // echo $headData.$bodyData;
                // echo strlen($headData);die;
                if (socket_write($socket, $headData . $bodyData, $Total_Length) == false) { //写入失败，还原发送信息并关闭端口
                    echo 'fail to write' . socket_strerror(socket_last_error());
                } else {
                    echo 'client write success:' . PHP_EOL . print(bin2hex($headData . $bodyData) . "\n");
                    // if ($i == 2) {
                    //     die;
                    // }
                    //读取服务端返回来的套接流信息
                    $headData = socket_read($socket, 12);
                    if ($headData != false) {
                        $head = unpack("NTotal_Length/NCommand_Id/NSequence_Id", $headData);
                        print_r($head) ;
                        $bodyData = socket_read($socket, $head['Total_Length'] - 12);
                        print_r($bodyData);
                        echo "\n";
                        //错误处理机制
          /*               try
                        {
                            $head = unpack("NTotal_Length/NCommand_Id/NSequence_Id", $headData);
                            if ($head['Command_Id'] & 0x80000001) {
                                // echo "接收到连接应答"."\n";
                                $bodyData = socket_read($socket, $head['Total_Length'] - 12);
                                $body     = unpack("CStatus/a16AuthenticatorSource/CVersion", $bodyData);
                                print_r($body) ;
                                switch ($body['Status']) {
                                case 0:
                                    echo "通道连接通过" . "\n";
                                    break;
                                case 1:
                                    echo "消息结构错" . "\n";
                                    $error_msg = "消息结构错";
                                    break;
                                case 2:
                                    echo "非法源地址" . "\n";
                                    $error_msg = "非法源地址";
                                    break;
                                case 3:
                                    echo "认证错误" . "\n";
                                    $error_msg = "认证错误";
                                    break;
                                case 4:
                                    echo "版本错误" . "\n";
                                    $error_msg = "版本错误";
                                    break;
                                default:
                                    echo "其他错误" . "\n";
                                    $error_msg = "其他错误";
                                    break;
                                }
                                //通道断口处理
                                if ($body['Status'] != 0) {
                                    socket_close($socket);
                                    Db::startTrans();
                                    try {
                                        // Db::table('yx_sms_sending_channel')->update(['error_msg' => $error_msg,'channel_status' => 4])->where('id',$id);
                                        // 提交事务
                                        Db::commit();
                                    } catch (\Exception $e) {
                                        // 回滚事务
                                        // exception($e);
                                        // die;
                                        Db::rollback();
                                       
                                    }
                                }
                              
                            }
                        }
                        //捕获异常
                         catch (Exception $e) {
                            //关闭工作流并修改通道状态
                            socket_close($socket);
                            exception($e);
                        } */
                    }
                   
                    
                }
                // if ($i > 1) {
                //     die;
                // }
                
                echo $i . "\n";
                $i++;
                // sleep($time); //等待时间，进行下一次操作
                // sleep(1); //等待时间，进行下一次操作
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
}
