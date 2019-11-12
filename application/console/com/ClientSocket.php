<?php

namespace app\console\com;

use app\console\Pzlife;
use cache\Phpredis;
use Config;
use Env;
use Exception;
use think\Db;

class ClientSocket extends Pzlife {

    // protected $redis;

    private function clientSocketInit() {
        $this->redis = Phpredis::getConn();
        //        $this->connect = Db::connect(Config::get('database.db_config'));
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
        // $this->clientSocketInit();
        $redis    = Phpredis::getConn();
        // $a_time = 0;
        
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G

        $redisMessageCodeSend = 'index:meassage:code:send:'.$content;//验证码发送任务rediskey
        $redisMessageCodeSequenceId = 'index:meassage:code:sequence:id:'.$content;//行业通知SequenceId
        $redisMessageCodeMsgId = 'index:meassage:code:msg:id:'.$content;//行业通知SequenceId
        $redisMessageCodeDeliver = 'index:meassage:code:deliver:'.$content;//行业通知MsgId
        // echo $redisMessageCodeSend;
        // $send = Phpredis::getConn()->lPop($redisMessageCodeSend);
        // print_r($send);die;
        // $code   = '短信发送测试';
        // print_r($redisMessageCodeSend);die;
        // echo $redisMessageCodeSend;die;
        // $send = $redis->lPop("index:meassage:code:send:1");
        // $send = $redis->rPush($redisMessageCodeSend,"15555555555:12:【品质生活】祝您生活愉快");
       
        // echo $code;
        // die;
        //  echo 0x80000008;
        //  die;
        // print_r('3049152064' & 0x0fffffff );die;
        // $v = base_convert(time(), 10, 16)."\n";
        // $a = pack("a8",$v);
        // echo $v."\n";
        // echo $a."\n";
        // print_r( unpack("a8",$a));
        // echo $v;
        // $str = "´&´'pӄELIVRD1911080943191108094315201926171Ȕ26";
        // $new = substr($str,0,8);
        // $new = base_convert($new, 16, 2);
        // echo $new;die;

        // // $arr = unpack("N2Msg_Id/a7Stat/a10Submit_time/a10Done_time/","´&´'pӄELIVRD1911080943191108094315201926171Ȕ26");
        // $arr = unpack("N2Msg_Id/a7Stat/a10Submit_time/a10Done_time/","´6h󿾧>gDELIVRD1911081338191108134415201926171&b");
        // $arr = unpack("I2Msg_Id/a7Stat/a10Submit_time/a10Done_time/","µ»'sDELIVRD1911111456191111150615201926171e韚");
        // print_r($arr['Msg_Id1'] & 0x0fffffff);die;
        // // echo 0x00000010;
        // die;

        // print_r(json_encode(['mobile' => $mobile,'code' => $code]));die;
        // $redis->rpush($redisMessageCodeSend,json_encode(['mobile' => $mobile,'code' => $code]));
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

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
            // socket_clear_error($socket);
            // socket_close($socket);
            // die;//关闭socket连接，清除缓存数据
            socket_set_nonblock($socket); //设置非阻塞模式
            $i           = 1;
            $Sequence_Id = 1;
            do {
                echo $i . "\n";
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
                    // $send = $redis->lPop($redisMessageCodeSend);
                    // $send = [];
                    // print_r($send);die;
                    // $send = $this->getSendCodeTask();
                    if ($i == 2) { //测试判断语句

                        // if ($send) { //正式使用从缓存中读取数据
                        $senddata = [];
                        // $senddata = explode(":",$send);
                        // $mobile = $senddata['mobile_content'];
                        // $mobile = $send['mobile_content'];

                        $mobile = 15599011983;
                        // $code   = $senddata['task_content']; //带签名
                        // $code   = $send['task_content']; //带签名
                        $code   = '【米思米】安全围栏标准组件上市！不用设计，不用外发喷涂，不用组装！低至363.95元，第五天出货！赶紧过来下单吧。https://www.misumi.com.cn/mail/chn-gc19057-ml03/转发无效,详询021-52559388*6197,回T退订。 '; 
                        //带签名
                        // $code   = '短信发送测试'; //带签名
                        // print_r($code);die;
                        $code = mb_convert_encoding($code, 'GBK', 'UTF-8');
                        echo strlen($code);die;
                        // $Timestamp = date('mdHis');
                        $uer_num = 1; //本批接受信息的用户数量（一般小于100个用户，不同通道承载能力不同）
                        // $Msg_Id = rand(1, 100);
                        // $Msg_Id   = '';
                        // $Msg_Id   = time().$mobile;
                        // $Msg_Id   = strval(time()) . $i;
                        // $bodyData = pack("a8", $Msg_Id);
                        $timestring = time();
                        $num1 = substr($timestring,0,8);
                        $num2 = substr($timestring,8).$this->combination($i);
                        
                        $bodyData = pack("N",$num1) . pack("N", $num2);
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
                        $bodyData = $bodyData . pack("a8", ''); //Reserve | 8 | Octet String | 保留
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
                        // $Sequence_Id = $i;
                        $time = 0;
                        // Db::startTrans();
                        // try {
                        //     Db::table('yx_user_send_code_task')->update(['send_status' => 2])->where('id',$send['id']);
                        //     // 提交事务
                        //     Db::commit();
                        // } catch (\Exception $e) {
                        //     // 回滚事务
                        //     // exception($e);
                        //     // die;
                        //     Db::rollback();

                        // }
                        if ($i > $security_master) {
                            $time = 1;
                            $i    = 0;
                        }
                        // echo $Command_Id;die;
                        // print_r(strlen($bodyData));die;
                        $redis->hset($redisMessageCodeSequenceId,$Sequence_Id,$senddata[0].":".$senddata[1].":".$senddata[2]);
                    } else {
                        $bodyData    = pack("a6a16CN", $Source_Addr, $AuthenticatorSource, $Version, $Timestamp);
                        $Command_Id  = 0x00000008; //保持连接
                        $Sequence_Id = $i;
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
                    // echo 'client write success:' . PHP_EOL . print(bin2hex($headData . $bodyData) . "\n");
                    // if ($i == 2) {
                    //     die;
                    // }
                    //读取服务端返回来的套接流信息
                    $headData = socket_read($socket, 12);
                    if ($headData != false) {
                        $head = unpack("NTotal_Length/NCommand_Id/NSequence_Id", $headData);
                        // print_r($head);
                        $bodyData = socket_read($socket, $head['Total_Length'] - 12);
                        // print_r($bodyData);
                        // echo "\n";
                        //错误处理机制
                        try
                        {
                            // $head = unpack("NTotal_Length/NCommand_Id/NSequence_Id", $headData);
                            // switch ($head['Command_Id'] & 0x0fffffff) {
                            // case 0x80000001:
                            //     // echo "接收到连接应答"."\n";
                            //     // $bodyData = socket_read($socket, $head['Total_Length'] - 12);
                                
                            //     break;
                            // case 0x80000004:
                                

                            //     break;
                            // case 0x00000005:
                                
                            //     break;
                            // case 0x00000008:
                            //     echo "心跳维持中" . "\n"; //激活测试,无消息体结构
                            //     // $body = unpack("C",$bodyData);
                            //     break;
                            // default:
                            //     echo "未声明head['Command_Id']:".$head['Command_Id'];
                            //     break;
                            // }
                            if ($head['Command_Id'] == 0x80000001) {
                                $body = unpack("CStatus/a16AuthenticatorSource/CVersion", $bodyData);
                                // print_r($body) ;
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
                                    die;
                                }
                            } else if ($head['Command_Id'] == 0x80000004) {
                                $body = unpack("N2Msg_Id/CResult", $bodyData);
                                print_r($body);
                                $sequence = $redis->hget($redisMessageCodeSequenceId,$head['Sequence_Id']);
                                if ($sequence) {
                                    $redis->hdel($redisMessageCodeSequenceId,$head['Sequence_Id']);
                                    $redis->hset($redisMessageCodeMsgId,$body['Msg_Id1'].$body['Msg_Id2'],$sequence);
                                }
                                // echo "get_CMPP_SUBMIT_RESP"."\n";
                                // echo "提交的Sequence_Id:".$head['Sequence_Id'].",解析的Msg_Id:".$body['Msg_Id1'].$body['Msg_Id2']."\n";
                                // print_r($body);
                                //状态为0 ，消息发送成功
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
                                }else {
                                    // Db::startTrans();
                                    // try {
                                    //     Db::table('yx_user_send_code_task')->update(['send_status' => 2])->where('id',$send['id']);
                                    //     // 提交事务
                                    //     Db::commit();
                                    // } catch (\Exception $e) {
                                    //     // 回滚事务
                                    //     // exception($e);
                                    //     // die;
                                    //     Db::rollback();

                                    // }
                                }
                            } else if ($head['Command_Id'] == 0x00000005) { //收到短信下发应答,需回复应答，应答Command_Id = 0x80000005
                                $Result     = 0;
                                // print_r($head['Total_Length']);
                                // $contentlen = $head['Total_Length'] - 73-12;
                                $contentlen = $head['Total_Length'] - 65-12;
                                // $body       = unpack("N2Msg_Id/a21Dest_Id/a10Service_Id/CTP_pid/CTP_udhi/CMsg_Fmt/a21Src_terminal_Id/CRegistered_Delivery/CMsg_Length/a" . $contentlen . "Msg_Content/a8Reserved", $bodyData);
                                $body       = unpack("N2Msg_Id/a21Dest_Id/a10Service_Id/CTP_pid/CTP_udhi/CMsg_Fmt/a21Src_terminal_Id/CRegistered_Delivery/CMsg_Length/a" . $contentlen . "Msg_Content/", $bodyData);
                                $Msg_Content = unpack("N2Msg_Id/a7Stat/a10Submit_time/a10Done_time/",$body['Msg_Content']);
                                // $Msg_Content = unpack("a".$body['Msg_Length'],);

                                $mesage = $redis->hget($redisMessageCodeMsgId,$Msg_Content['Msg_Id1'].$Msg_Content['Msg_Id2']);
                                if ($mesage) {
                                    $redis->hdel($redisMessageCodeMsgId,$body['Msg_Id1'].$body['Msg_Id2']);
                                    $redis->rpush($redisMessageCodeDeliver,$mesage.":".$Msg_Content['Stat']);
                                }
                                print_r($Msg_Content);
                                // echo "返回发送成功的Msg_Id:".$body['Msg_Id1'].$body['Msg_Id2'];
                                // echo "CMPP_DELIVER:" . base_convert($bodyData, 16, 2) . "\n";
                                $callback_Command_Id = 0x80000005;

                                $new_body         = pack("N", $body['Msg_Id1']). pack("N", $body['Msg_Id2']). pack("C", $Result);
                                $new_Total_Length = strlen($new_body) + 12;
                                $new_headData     = pack("NNN", $Total_Length, $callback_Command_Id, $body['Msg_Id2']);
                                // socket_write($socket, $new_headData . $new_body, $new_Total_Length);
                            }else if ($head['Command_Id'] == 0x00000008) {
                                echo "心跳维持中" . "\n"; //激活测试,无消息体结构
                            }else if ($head['Command_Id'] ==0x80000008) {
                                echo "激活测试应答" . "\n"; //激活测试,无消息体结构
                            }else {
                                echo "未声明head['Command_Id']:".$head['Command_Id'];
                                // break;
                            }
                        }
                        //捕获异常
                         catch (Exception $e) {
                            //关闭工作流并修改通道状态
                            socket_close($socket);
                            exception($e);
                        }
                    }

                }
                // if ($i > 1) {
                //     die;
                // }

                $i++;
                $Sequence_Id++;
                if ($Sequence_Id > 65536) {
                    $Sequence_Id = 1;
                }
                // sleep($time); //等待时间，进行下一次操作
                sleep(1); //等待时间，进行下一次操作
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

    public function decodeString(){
        // echo strlen("³½'¹ ");
        $timestring = time();
        $num1 = substr($timestring,0,8);
        $num2 = substr($timestring,8).$this->combination(rand(1,240));
        echo $num1;
        echo "\n";
        echo $num2;
        
        $a = pack("N",$num1) . pack("N", $num2);
        echo $a."\n";
        print_r(unpack("N2Msg_Id",$a));
       
        die;
       $arr = unpack("N2Msg_Id/a7Stat/a10Submit_time/a10Done_time/","³f󿾧©¬DELIVRD1911071650191107165515201926171AG");
       
    }

    /**
     * 6位数字补齐
     * @param string $pdu
     * @return string
     */
    function combination($num) {
        $num = intval($num);
        $num = strval($num);
        $new_num = '';
        switch (strlen($num)) {
            case 0:
                $new_num = "000000";
                break;
            case 1:
                $new_num = "00000".$num;
                break;
            case 2:
                $new_num = "0000".$num;
                break;
            case 3:
                $new_num = "000".$num;
                break;
            case 4:
                $new_num = "00".$num;
                break;
            case 5:
                $new_num = "0".$num;
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
    function decode($str, $prefix="&#") {
        $str = str_replace($prefix, "", $str);
        $a = explode(";", $str);
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

    public function getSendCodeTask(){
        $task = Db::query("SELECT * FROM yx_user_send_code_task WHERE `send_status` = 1 ORDER BY id ASC LIMIT 1");
        if ($task) {
            return $task[0];
        }
        return [];
    }
}
