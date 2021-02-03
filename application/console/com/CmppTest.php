<?php

namespace app\console\com;

use app\console\Pzlife;
use cache\Phpredis;
use Exception;
use think\Db;

class CmppTest extends Pzlife
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
            /*  return [
            // 'channel_host' => "47.103.200.251", //服务商ip
            'channel_host' => "127.0.0.1", //服务商ip
            'channel_port' => "7891", //短连接端口号   17890长连接端口号
            'channel_source_addr' => "C54164", //企业id  企业代码
            'channel_shared_secret' => '3teOpxBK', //网关登录密码
            'channel_service_id' => "C54164",
            'channel_dest_id' => "1066", //短信接入码 短信端口号
            'Sequence_Id' => 1,
            'SP_ID' => "",
            'bin_ip' => ["127.0.0.1", "101.91.60.115"], //客户端绑定IP
            'free_trial' => 2,
            'channel_flow_velocity' => 300,
            'uid' => 1,
            'title' => '本地测试样例',
            ];
             */
            return [
                // 'channel_host' => "47.103.200.251", //服务商ip
                'channel_host' => "47.100.55.62", //服务商ip
                // 'channel_host' => "47.101.75.174", //服务商ip
                // 'channel_host' => "127.0.0.1", //服务商ip
                'channel_port' => "7890", //短连接端口号   17890长连接端口号
                // 'channel_port' => "7890", //短连接端口号   17890长连接端口号
                'channel_source_addr' => "900003", //企业id  企业代码
                'channel_shared_secret' => '888888', //网关登录密码
                'channel_service_id' => "900003",
                'channel_dest_id' => "10694406674719", //短信接入码 短信端口号
                'Sequence_Id' => 1,
                'SP_ID' => "",
                'bin_ip' => ["127.0.0.1", "47.103.200.251"], //客户端绑定IP
                'free_trial' => 2,
                'channel_flow_velocity' => 300,
                'uid' => 1,
                'title' => '本地测试样例',
            ];
        } elseif ($content == 'C54164') {
            return [
                'channel_host' => "47.103.200.251", //服务商ip
                // 'channel_host' => "127.0.0.1", //服务商ip
                'channel_port' => "7890", //短连接端口号   17890长连接端口号
                'channel_source_addr' => "C54164", //企业id  企业代码
                'channel_shared_secret' => '3teOpxBK', //网关登录密码
                'channel_service_id' => "C54164",
                'channel_dest_id' => "1066", //短信接入码 短信端口号
                'Sequence_Id' => 1,
                'SP_ID' => "",
                'bin_ip' => ["127.0.0.1", "101.91.60.115"], //客户端绑定IP
                'free_trial' => 2,
                'channel_flow_velocity' => 300,
                'uid' => 1,
                'title' => '本地测试样例',
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
        /*  $code = '【施华洛世奇】亲爱的会员，感谢您一路以来的支持！您已获得2020年会员周年礼券，购买正价商品满1999元即可获得闪耀玫瑰金色简约吊坠一条，请于2020年10月19日前使用。可前往“施华洛世奇会员中心”小程序查看该券。详询4006901078。 回TD退订'; //带签名
        $code = mb_convert_encoding($code, 'UCS-2', 'UTF-8');
        $udh     = pack("cccccc", 5, 0, 3, 1, 2, 1);
        $newcode = $udh . substr($code, 0 * 134, 134);
        print_r(substr($code, 0 * 134, 134));
        // print_r($newcode);
        die; */

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
        $redisMessageCodeSend = 'index:meassage:code:send:' . $content; //验证码发送任务rediskey
        $redisMessageCodeSequenceId = 'index:meassage:code:sequence:id:' . $content; //行业通知SequenceId
        $redisMessageCodeMsgId = 'index:meassage:code:msg:id:' . $content; //行业通知SequenceId
        // $redisMessageCodeDeliver    = 'index:meassage:code:deliver:' . $content; //行业通知MsgId
        $redisMessageCodeDeliver = 'index:meassage:code:new:deliver:' . $content; //行业通知MsgId
        $redisMessageUnKownDeliver = 'index:meassage:code:unknow:deliver:' . $content; //行业通知MsgId
        $redisMessageUpRiver = 'index:message:code:upriver:' . $content; //上行队列

        /* for ($i = 0; $i < 500; $i++) {
            // $send = $redis->rPush($redisMessageCodeSend, '{"mobile":"15201926171","mar_task_id":1667143,"content":"\u3010\u5bcc\u6cf7\u79d1\u6280\u3011\u672c\u6b21\u767b\u5f55\u9a8c\u8bc1\u78011512303210","from":"yx_user_send_task","send_msg_id":"","uid":257,"send_num":1,"task_no":"mar21011510264477582316","isneed_receipt":1,"need_receipt_type":1,"is_have_selected":2,"develop_code":"5969"}');
            $send = $redis->rPush($redisMessageCodeSend, '{"mobile":"15201926171","mar_task_id":1667143,"content":"【丝芙兰】1张9折券已飞奔向您！亲爱的王浩翰会员，您所获赠的九折券自2021-01-14起生效，有效期截止2021-07-14，请在有效期间内前往丝芙兰官网sephora.cn、App、小程序或门店选购。(在官网购物时需与官网账号绑定。累积消费积分1500分或四次不同日消费即自动兑换1张九折劵)/回T退订","from":"yx_user_send_task","send_msg_id":"","uid":257,"send_num":1,"task_no":"mar21011510264477582316","isneed_receipt":1,"need_receipt_type":1,"is_have_selected":2,"develop_code":"5969"}');
        } */
        
        // die;
        //已发送数组
        $had_sendmeaages = [];
        //回复数组
        $get_summitresp_sendmeaages = [];
        $get_resp_num = 0;
        //回执数组
        $get_deliver_messages = [];
        $get_deliver_num = 0;
        $get_deliver_num_sum = 0;
        //上行数组
        $get_upriver_messages = [];
        $get_upriver_num = 0;
        // die;
        // $channel_total_data = [];
        $channel_total_data = $redis->get('index:message:channel:' . $content . '_' . date('Ymd'));
        if (!$channel_total_data) {
            $channel_total_data = [
                'update_time' => time(),
                'flow_rate' => 0,
                'all_num' => 0,
                'success_num' => 0,
                'default_num' => 0,
                'error_data' => [],
            ];
            $redis->set('index:message:channel:' . $content . '_' . date('Ymd'), json_encode($channel_total_data));
        } else {
            $channel_total_data = json_decode($channel_total_data, true);
            if (time() - $channel_total_data['update_time'] > 60) {
                $channel_total_data['update_time'] = time();
                $redis->set('index:message:channel:' . $content . '_' . date('Ymd'), json_encode($channel_total_data));
            }
        }

        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $log_path = realpath("") . "/error/" . $content . ".log";
        $myfile = fopen($log_path, 'a+');
        fwrite($myfile, date('Y-m-d H:i:s', time()) . "\n");
        fwrite($myfile, " Begin" . "\n");
        fclose($myfile);

        // $content = 0;

        // // print_r($contdata);die;
        $host = $contdata['channel_host']; //服务商ip
        $port = $contdata['channel_port']; //短连接端口号   17890长连接端口号
        $Source_Addr = $contdata['channel_source_addr']; //企业id  企业代码
        $Shared_secret = $contdata['channel_shared_secret']; //网关登录密码
        $Service_Id = $contdata['channel_service_id'];
        $Dest_Id = $contdata['channel_dest_id']; //短信接入码 短信端口号
        $Sequence_Id = 1;
        // $SP_ID                = $contdata['SP_ID'];
        $master_num = isset($contdata['channel_flow_velocity']) ? $contdata['channel_flow_velocity'] : 300; //通道最大提交量
        $security_coefficient = 1; //通道饱和系数
        $security_master = $master_num * $security_coefficient;
        $miao = 1000000;
        $redis->set('channel_' . $content, $Sequence_Id);
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
            // $pos          = 0;
            $i = 1;
            // $Sequence_Id = $redis->get('channel_' . $content);
            // if (empty($Sequence_Id)) {
            //     $Sequence_Id = 1;
            // }
            // $redis->set('channel_' . $content, $Sequence_Id + 1);
            //先进行连接验证
            date_default_timezone_set('PRC');
            $time = 0;
            $Version = 0x20; //CMPP版本 0x20 2.0版本 0x30 3.0版本
            $Timestamp = date('mdHis');
            $AuthenticatorSource = md5($Source_Addr . pack("a9", "") . $Shared_secret . $Timestamp, true);
            $bodyData = pack("a6a16CN", $Source_Addr, $AuthenticatorSource, $Version, $Timestamp);
            $Command_Id = 0x00000001;
            $Total_Length = strlen($bodyData) + 12;
            $headData = pack("NNN", $Total_Length, $Command_Id, $Sequence_Id);
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
                    echo "开始登陆..." . "\n";
                    $head = unpack("NTotal_Length/NCommand_Id/NSequence_Id", $headData);
                    // print_r($head);
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
                            $sequence = json_decode($sequence, true);
                            $msgid = $body['Msg_Id1'] . $body['Msg_Id2'];
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
                        $body = unpack("N2Msg_Id/a21Dest_Id/a10Service_Id/CTP_pid/CTP_udhi/CMsg_Fmt/a21Src_terminal_Id/CRegistered_Delivery/CMsg_Length/a" . $contentlen . "Msg_Content/", $bodyData);
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
                                $mesage = json_decode($mesage, true);
                                $mesage['Stat'] = $Msg_Content['Stat'];
                                // $mesage['Msg_Id']        = $Msg_Content['Msg_Id1'] . $Msg_Content['Msg_Id2'];
                                $mesage['Submit_time'] = isset($Msg_Content['Submit_time']) ? $Msg_Content['Submit_time'] : date('ymdHis', $mesage['my_submit_time']);
                                $mesage['Done_time'] = isset($Msg_Content['Done_time']) ? $Msg_Content['Done_time'] : date('ymdHis', time());
                                $mesage['receive_time'] = time(); //回执时间戳
                                $redis->rpush($redisMessageCodeDeliver, json_encode($mesage));
                            } else { //不在记录中的回执存入缓存，
                                $mesage['Stat'] = isset($Msg_Content['Stat']) ? $Msg_Content['Stat'] : 'UNKNOWN';
                                $mesage['Submit_time'] = trim(isset($Msg_Content['Submit_time']) ? $Msg_Content['Submit_time'] : date('ymdHis', time()));
                                $mesage['Done_time'] = trim(isset($Msg_Content['Done_time']) ? $Msg_Content['Done_time'] : date('ymdHis', time()));
                                // $mesage['mobile']      = $body['Dest_Id '];//手机号
                                $mesage['mobile'] = isset($Msg_Content['Dest_terminal_Id']) ? $Msg_Content['Dest_terminal_Id'] : '';
                                $mesage['receive_time'] = time(); //回执时间戳
                                $mesage['Msg_Id'] = $Msg_Content['Msg_Id1'] . $Msg_Content['Msg_Id2'];
                                $redis->rPush($redisMessageUnKownDeliver, json_encode($mesage));
                            }
                        }
                        // print_r($mesage);
                        $callback_Command_Id = 0x80000005;

                        $new_body = pack("N", $body['Msg_Id1']) . pack("N", $body['Msg_Id2']) . pack("C", $Result);
                        $new_Total_Length = strlen($new_body) + 12;
                        $new_headData = pack("NNN", $new_Total_Length, $callback_Command_Id, $head['Sequence_Id']);
                        socket_write($socket, $new_headData . $new_body, $new_Total_Length);
                    } else if ($head['Command_Id'] == 0x00000008) {
                        // echo "心跳维持中" . "\n"; //激活测试,无消息体结构
                        $Command_Id = 0x80000008; //保持连接
                        $Total_Length = 12;
                        $headData = pack("NNN", $Total_Length, $Command_Id, $Sequence_Id);
                        socket_write($socket, $headData, $Total_Length);
                        $receive = 2;
                    } else if ($head['Command_Id'] == 0x80000008) {
                        // echo "激活测试应答" . "\n"; //激活测试,无消息体结构
                    } else if ($head['Command_Id'] == 0x00000002) {
                        // echo "未声明head['Command_Id']:" . $head['Command_Id'];
                        $Command_Id = 0x80000002; //关闭连接
                        $Total_Length = 12;
                        $headData = pack("NNN", $Total_Length, $Command_Id, $Sequence_Id);
                        socket_write($socket, $headData, $Total_Length);
                        socket_close($socket);
                        $this->writeToRobot($content, '通道方关闭当前链接，通道关闭', $contdata['title']);
                        exit("通道方关闭当前链接，通道关闭");
                        $receive = 2;
                    }
                }
                $Sequence_Id++;

                if ($verify_status == 0) { //验证成功并且所有信息已读完可进行发送操作
                    //
                    // sleep(1);
                    // echo "登陆成功时间:" . date('Y-m-d H:i:s', time()) . "\n";
                    $pos = $redis->get('channel_pos_' . $content);
                    $pos = isset($pos) ? $pos : 0;
                    while (true) {
                        // echo $Sequence_Id . "\n";
                        usleep(5000);
                        // $Sequence_Id = $redis->get('channel_' . $content);
                        // $redis->set('channel_' . $content, $Sequence_Id + 1);

                        // print_r($Sequence_Id);
                        try {
                            $receive = 1;
                            //先接收
                            while (true) {
                                $headData = socket_read($socket, 12);
                                // if (strlen($headData) < 12) {
                                //     continue;
                                // }
                                if ($headData != false) {
                                    // echo $headData;
                                    // echo strlen($headData);
                                    // echo "\n";
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
                                        if (isset($had_sendmeaages[$head['Sequence_Id']])) {
                                            $send_messages = [];
                                            $send_messages = $had_sendmeaages[$head['Sequence_Id']];
                                            $msgid = $body['Msg_Id1'] . $body['Msg_Id2'];
                                            $send_messages['Msg_Id'] = $msgid;
                                            $get_summitresp_sendmeaages[] = $send_messages;
                                            unset($had_sendmeaages[$head['Sequence_Id']]);
                                            $get_resp_num++;
                                            // echo count($get_summitresp_sendmeaages);
                                            if ($get_resp_num > 500) {
                                                //写入数据库
                                                $table_name = $this->createChanenelMsgidTable($content);
                                                Db::table($table_name)->insertAll($get_summitresp_sendmeaages);
                                                $get_summitresp_sendmeaages = [];
                                                $get_resp_num = 0;
                                            }
                                        }
                                        // $sequence = $redis->hget($redisMessageCodeSequenceId, $head['Sequence_Id']);
                                        // if ($sequence) {
                                        //     $sequence = json_decode($sequence, true);
                                        //     $msgid = $body['Msg_Id1'] . $body['Msg_Id2'];
                                        //     $sequence['Msg_Id'] = $msgid;
                                        //     $redis->hdel($redisMessageCodeSequenceId, $head['Sequence_Id']);
                                        //     $redis->hset($redisMessageCodeMsgId, $body['Msg_Id1'] . $body['Msg_Id2'], json_encode($sequence));
                                        // }

                                        switch (trim($body['Result'])) {
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
                                        if (intval(trim($body['Result'])) != 0) { //消息发送失败
                                            // echo "发送失败" . "\n";
                                            $error_msg = "其他错误";
                                            $mesage = [];
                                            $mesage['Msg_Id'] = $msgid;
                                            $mesage['Stat'] = $body['Result'];
                                            // $mesage['Msg_Id']        = $Msg_Content['Msg_Id1'] . $Msg_Content['Msg_Id2'];
                                            $mesage['Submit_time'] = date('ymdHis', time());
                                            $mesage['Done_time'] = date('ymdHis', time());
                                            $mesage['receive_time'] = time(); //回执时间戳
                                            // $mesage['develop_no'] = $receive_develop_no; //回执时间戳
                                            $get_deliver_messages[] = $mesage;
                                            $get_deliver_num++;
                                            if ($get_deliver_num > 500) {
                                                $table_name = $this->createDeliverInfoMsgidForChannel($content);
                                                Db::table($table_name)->insertAll($get_deliver_messages);
                                                $get_deliver_messages = [];
                                                $get_deliver_num = 0;
                                            }
                                        } else {

                                        }
                                    } else if ($head['Command_Id'] == 0x00000005) { //收到短信下发应答,需回复应答，应答Command_Id = 0x80000005
                                        $get_deliver_num_sum++;
                                        // echo "收到回执结构体数量:" . $get_deliver_num_sum . "\n";
                                        $Result = 0;
                                        $contentlen = $head['Total_Length'] - 65 - 12;
                                        if (strlen($bodyData) < $head['Total_Length'] - 12) {
                                            $this->writeToRobot($content, '回执获取到长度错误消息体：' . $headData . $bodyData, $contdata['title']);
                                            continue;
                                        }
                                        $body = unpack("N2Msg_Id/a21Dest_Id/a10Service_Id/CTP_pid/CTP_udhi/CMsg_Fmt/a21Src_terminal_Id/CRegistered_Delivery/CMsg_Length/a" . $contentlen . "Msg_Content/", $bodyData);
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
                                                'mobile' => trim($body['Src_terminal_Id']),
                                                'message_info' => trim($body['Msg_Content']),
                                                'develop_code' => $receive_develop_no,
                                            ];

                                            $get_upriver_messages[] = $up_message;
                                            $get_upriver_num++;
                                            if ($get_upriver_num > 500) {
                                                $table_name = $this->createUpriverForChannel($content);
                                                Db::table($table_name)->insertAll($get_upriver_messages);
                                                $get_upriver_messages = [];
                                                $get_upriver_num = 0;
                                            }
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
                                            $mesage = [];
                                            $mesage['Msg_Id'] = $message_id;
                                            $mesage['mobile'] = trim($Msg_Content['Dest_terminal_Id']);
                                            $mesage['Stat'] = $Msg_Content['Stat'];
                                            // $mesage['Msg_Id']        = $Msg_Content['Msg_Id1'] . $Msg_Content['Msg_Id2'];
                                            $mesage['Submit_time'] = isset($Msg_Content['Submit_time']) ? $Msg_Content['Submit_time'] : date('ymdHis', time());
                                            $mesage['Done_time'] = isset($Msg_Content['Done_time']) ? $Msg_Content['Done_time'] : date('ymdHis', time());
                                            $mesage['receive_time'] = time(); //回执时间戳
                                            // $mesage['develop_no'] = $receive_develop_no; //回执时间戳
                                            $get_deliver_messages[] = $mesage;
                                            $get_deliver_num++;
                                            if ($get_deliver_num > 500) {
                                                $table_name = $this->createDeliverInfoMsgidForChannel($content);
                                                Db::table($table_name)->insertAll($get_deliver_messages);
                                                $get_deliver_messages = [];
                                                $get_deliver_num = 0;
                                            }
                                        }
                                        // print_r($mesage);
                                        $callback_Command_Id = 0x80000005;

                                        $new_body = pack("N", $body['Msg_Id1']) . pack("N", $body['Msg_Id2']) . pack("C", $Result);
                                        $new_Total_Length = strlen($new_body) + 12;
                                        $new_headData = pack("NNN", $new_Total_Length, $callback_Command_Id, $head['Sequence_Id']);
                                        socket_write($socket, $new_headData . $new_body, $new_Total_Length);
                                        $receive = 2;
                                        // usleep(5);
                                    } else if ($head['Command_Id'] == 0x00000008) {
                                        // echo "接收到心跳" . "\n"; //激活测试,无消息体结构
                                        $Command_Id = 0x80000008; //保持连接
                                        $Total_Length = 12;

                                        // socket_write($socket, $headData, $Total_Length);

                                        $new_body = pack("a1", " ");
                                        $new_Total_Length = strlen($new_body) + 12;
                                        $headData = pack("NNN", $new_Total_Length, $Command_Id, $Sequence_Id);
                                        socket_write($socket, $headData . $new_body, $new_Total_Length);

                                        $receive = 2;
                                    } else if ($head['Command_Id'] == 0x80000008) {
                                        // echo "激活测试应答" . "\n"; //激活测试,无消息体结构
                                    } else if ($head['Command_Id'] == 0x00000002) {
                                        // echo "未声明head['Command_Id']:" . $head['Command_Id'];
                                        $Command_Id = 0x80000002; //关闭连接
                                        $Total_Length = 12;
                                        $headData = pack("NNN", $Total_Length, $Command_Id, $Sequence_Id);
                                        socket_write($socket, $headData, $Total_Length);
                                        socket_close($socket);
                                        $this->writeToRobot($content, '通道方关闭当前链接，通道关闭', $contdata['title']);
                                        exit('通道方关闭当前链接，通道关闭');
                                        $receive = 2;
                                    }
                                } else {
                                    break;
                                }
                            }
                            //在发送

                            $send = $redis->lPop($redisMessageCodeSend);
                            if (!empty($send)) { //正式使用从缓存中读取数据并且有待发送数据
                                // echo "发送短信\n";
                                $send_status = 1;
                                $send_data = [];
                                $send_data = json_decode($send, true);
                                // $mobile = $senddata['mobile_content'];
                                $mobile = $send_data['mobile'];
                                $txt_head = 6;
                                $txt_len = 140;
                                $max_len = $txt_len - $txt_head;
                                $code = $send_data['content']; //带签名
                                $uer_num = 1; //本批接受信息的用户数量（一般小于100个用户，不同通道承载能力不同）
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

                                    /* $redis->set('channel_pos_' . $content, $pos + 1);
                                    if ($pos + 1 > 100) {
                                    $redis->set('channel_pos_' . $content, 0);
                                    } */
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
                                        // echo pack("C", $uer_num);
                                        $bodyData .= pack("C", $uer_num);
                                        $p_n = 21 * $uer_num;
                                        $bodyData .= pack("a" . $p_n, $mobile);
                                        $udh = pack("cccccc", 5, 0, 3, $pos, $num_messages, $j + 1);
                                        $newcode = $udh . substr($code, $j * $max_len, $max_len);
                                        $len = strlen($newcode);
                                        $bodyData .= pack("C", $len);
                                        $bodyData .= pack("a" . $len, $newcode);
                                        $bodyData .= pack("a8", '');
                                        $Command_Id = 0x00000004; // 短信发送
                                        // print_r($udh);
                                        $Total_Length = strlen($bodyData) + 12;
                                        $headData = pack("NNN", $Total_Length, $Command_Id, $Sequence_Id);
                                        $send_data['my_submit_time'] = time(); //发送时间戳
                                        // $redis->hset($redisMessageCodeSequenceId, $Sequence_Id, json_encode($send_data));
                                        // usleep(300);
                                        socket_write($socket, $headData . $bodyData, $Total_Length);
                                        $had_sendmeaages[$Sequence_Id] = $send_data;
                                        $send_status = 2;
                                        ++$i;
                                        // $Sequence_Id = $redis->get('channel_' . $content);
                                        // $redis->set('channel_' . $content, $Sequence_Id + 1);
                                        $Sequence_Id++;
                                        if ($Sequence_Id + 1 > 65536) {
                                            $Sequence_Id = 0;
                                            // $redis->set('channel_' . $content, $Sequence_Id);
                                        }
                                        $channel_total_data['all_num']++;
                                        if (time() - $channel_total_data['update_time'] <= 60) {
                                            $channel_total_data['flow_rate']++;
                                        } else {
                                            $redis->set('index:message:channel:' . $content . '_' . date('Ymd'), json_encode($channel_total_data));
                                            $channel_total_data['update_time'] = time();
                                            $channel_total_data['flow_rate'] = 1;
                                        }

                                    }
                                    if ($i > $security_master) {
                                        $i = 0;
                                    }
                                    $pos++;
                                    if ($pos + 1 > 100) {
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
                                    $p_n = 21 * $uer_num;
                                    $bodyData .= pack("a" . $p_n, $mobile);
                                    $len = strlen($code);
                                    $bodyData .= pack("C", $len);
                                    $bodyData .= pack("a" . $len, $code);
                                    $bodyData .= pack("a8", '');
                                    $Command_Id = 0x00000004; // 短信发送
                                    $time = 0;
                                    if ($i > $security_master) {
                                        $time = 1;
                                        $i = 0;
                                    }
                                    $send_data['my_submit_time'] = time(); //发送时间戳
                                    $Total_Length = strlen($bodyData) + 12;
                                    $headData = pack("NNN", $Total_Length, $Command_Id, $Sequence_Id);
                                    socket_write($socket, $headData . $bodyData, $Total_Length);
                                    $had_sendmeaages[$Sequence_Id] = $send_data;
                                    // $redis->hset($redisMessageCodeSequenceId, $Sequence_Id, json_encode($send_data));
                                    $send_status = 2;
                                    // usleep(2500);
                                }
                                if ($Sequence_Id + 1 > 65536) {
                                    $Sequence_Id = 0;
                                    // $redis->set('channel_' . $content, $Sequence_Id);
                                }

                                unset($send_status);
                            } else { //心跳
                                // print_r($had_sendmeaages);
                                // print_r($get_summitresp_sendmeaages);
                                // die;
                                // echo count($get_deliver_messages);
                                if (!empty($get_summitresp_sendmeaages)) { //提交回复
                                    $table_name = $this->createChanenelMsgidTable($content);
                                    Db::table($table_name)->insertAll($get_summitresp_sendmeaages);
                                    $get_summitresp_sendmeaages = [];
                                    $get_resp_num = 0;
                                }
                                //回执
                                if (!empty($get_deliver_messages)) {
                                    $table_name = $this->createDeliverInfoMsgidForChannel($content);
                                    Db::table($table_name)->insertAll($get_deliver_messages);
                                    $get_deliver_messages = [];
                                    $get_deliver_num = 0;
                                }
                                //上行

                                if (!empty($get_upriver_messages)) {
                                    $table_name = $this->createUpriverForChannel($content);
                                    Db::table($table_name)->insertAll($get_upriver_messages);
                                    $get_upriver_messages = [];
                                    $get_upriver_num = 0;
                                }

                                $Command_Id = 0x00000008; //保持连接
                                $Total_Length = 12;
                                // $headData = pack("NNN", $Total_Length, $Command_Id, $Sequence_Id);
                                // $body = pack('c', $Sequence_Id);
                                // $Total_Length = strlen($body) + 12;
                                $headData = pack("NNN", $Total_Length, $Command_Id, $Sequence_Id);

                                if ($receive != 2) {
                                    // socket_write($socket, $headData, $Total_Length);
                                    socket_write($socket, $headData, 12);
                                    // echo "发送心跳时间:" . date("Y-m-d H:i:s");
                                    // echo "\n";
                                }
                                usleep(998600);
                            }

                            ++$i;
                            $Sequence_Id++;
                            // $redis->set('channel_'.$content,$Sequence_Id);
                            if ($Sequence_Id > 65536) {
                                $Sequence_Id = 1;
                                // $redis->set('channel_' . $content, $Sequence_Id);
                            }
                        }
                        //捕获异常
                         catch (Exception $e) {
                            if (isset($send_status) && $send_status == 1) {
                                $redis->rpush($redisMessageCodeSend, $redisMessageCodeSend);
                                if (isset($had_sendmeaages[$Sequence_Id])) {
                                    unset($had_sendmeaages[$Sequence_Id]);
                                }
                            }
                            if (!empty($get_summitresp_sendmeaages)) { //提交回复
                                $table_name = $this->createChanenelMsgidTable($content);
                                Db::table($table_name)->insertAll($get_summitresp_sendmeaages);
                                $get_summitresp_sendmeaages = [];
                                $get_resp_num = 0;
                            }
                            //回执
                            if (!empty($get_deliver_messages)) {
                                $table_name = $this->createDeliverInfoMsgidForChannel($content);
                                Db::table($table_name)->insertAll($get_deliver_messages);
                                $get_deliver_messages = [];
                                $get_deliver_num = 0;
                            }
                            //上行

                            if (!empty($get_upriver_messages)) {
                                $table_name = $this->createUpriverForChannel($content);
                                Db::table($table_name)->insertAll($get_upriver_messages);
                                $get_upriver_messages = [];
                                $get_upriver_num = 0;
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
                                fwrite($myfile, "通道延迟5秒后再次连接失败，请联系通道方检查原因\n");
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
                                $Version = 0x20; //CMPP版本 0x20 2.0版本 0x30 3.0版本
                                $Timestamp = date('mdHis');
                                $AuthenticatorSource = md5($Source_Addr . pack("a9", "") . $Shared_secret . $Timestamp, true);
                                $bodyData = pack("a6a16CN", $Source_Addr, $AuthenticatorSource, $Version, $Timestamp);
                                $Command_Id = 0x00000001;
                                $Total_Length = strlen($bodyData) + 12;
                                $headData = pack("NNN", $Total_Length, $Command_Id, $Sequence_Id);
                                // socket_write($socket, $headData . $bodyData, $Total_Length);
                                if (socket_write($socket, $headData . $bodyData, $Total_Length) == false) {
                                    // // echo 'write_verify fail massege:' . socket_strerror(socket_last_error());
                                    $myfile = fopen($log_path, 'a+');
                                    fwrite($myfile, date('Y-m-d H:i:s', time()) . "\n");
                                    fwrite($myfile, "通道延迟5秒后写入socket失败，请联系通道方检查原因\n");
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
                echo "登录失败";
            }
        }
    }

    public function writeToRobot($content, $error_data, $title)
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
            'Content-Type:application/json',
        ];
        $this->sendRequest2($api, 'post', $check_data, $headers);
    }

    public function sendRequest2($requestUrl, $method = 'get', $data = [], $headers)
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
    public function StrToBin($str)
    {
        //1.列出每个字符
        $arr = preg_split('/(?<!^)(?!$)/u', $str);
        //2.unpack字符
        foreach ($arr as &$v) {
            $temp = unpack('H*', $v);
            $v = base_convert($temp[1], 16, 2);
            unset($temp);
        }

        return join('', $arr);
    }

    public function decodeString()
    {
        // // echo strlen("³½'¹ ");
        $timestring = time();
        $num1 = substr($timestring, 0, 8);
        $num2 = substr($timestring, 8) . $this->combination(rand(1, 240));
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
    public function combination($num)
    {
        $num = intval($num);
        $num = strval($num);
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
    public function decode($str, $prefix = "&#")
    {
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

    //创建表
    public function createChanenelMsgidTable($content)
    {
        $table_name = '';
        $table_name = 'yx_task_send_msgid_forchannel_' . $content;
        if (in_array($content,[156,157])) {
            $sql = "CREATE TABLE IF NOT EXISTS  `" . $table_name . "`  (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `send_msg_id` varchar(500) NOT NULL DEFAULT '' COMMENT '客户消息id',
                `mseeage_id` varchar(30) NOT NULL DEFAULT '' COMMENT '丝芙兰客户消息id',
                `template_id` varchar(20) NOT NULL DEFAULT '' COMMENT '丝芙兰模板id',
                `mar_task_id` varchar(10) NOT NULL DEFAULT '' COMMENT '短信任务id',
                `content` text NOT NULL COMMENT '短信内容',
                `from` varchar(50) NOT NULL DEFAULT '' COMMENT '短信来源表',
                `mobile` char(21) NOT NULL DEFAULT '' COMMENT '手机号',
                `uid` varchar(10) NOT NULL DEFAULT '' COMMENT '用户id',
                `send_num` varchar(10) NOT NULL DEFAULT '' COMMENT '发送数量',
                `task_no` char(23) NOT NULL DEFAULT '' COMMENT '任务编号',
                `my_submit_time` varchar(10) NOT NULL DEFAULT '' COMMENT '短信提交时间戳',
                `Msg_Id` varchar(20) NOT NULL DEFAULT '' COMMENT '短信提交消息id',
                `develop_code` varchar(20) NOT NULL DEFAULT '' COMMENT '扩展码',
                `isneed_receipt` char(1) NOT NULL DEFAULT '' COMMENT '是否需要回执消息',
                `need_receipt_type` char(1) NOT NULL DEFAULT '' COMMENT '回执消息类型',
                `is_have_selected` char(1) NOT NULL DEFAULT '' COMMENT '是否需要查询明细',
                PRIMARY KEY (`id`) USING BTREE,
                UNIQUE KEY `Msg_Id` (`Msg_Id`) USING BTREE
            ) ENGINE = InnoDB  CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '短信提交通道返回msgid存储表' ROW_FORMAT = Dynamic;
            ";
        }else{
            $sql = "CREATE TABLE IF NOT EXISTS  `" . $table_name . "`  (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `send_msg_id` varchar(500) NOT NULL DEFAULT '' COMMENT '客户消息id',
                `mar_task_id` varchar(10) NOT NULL DEFAULT '' COMMENT '短信任务id',
                `content` text NOT NULL COMMENT '短信内容',
                `from` varchar(50) NOT NULL DEFAULT '' COMMENT '短信来源表',
                `mobile` char(21) NOT NULL DEFAULT '' COMMENT '手机号',
                `uid` varchar(10) NOT NULL DEFAULT '' COMMENT '用户id',
                `send_num` varchar(10) NOT NULL DEFAULT '' COMMENT '发送数量',
                `task_no` char(23) NOT NULL DEFAULT '' COMMENT '任务编号',
                `my_submit_time` varchar(10) NOT NULL DEFAULT '' COMMENT '短信提交时间戳',
                `Msg_Id` varchar(20) NOT NULL DEFAULT '' COMMENT '短信提交消息id',
                `develop_code` varchar(20) NOT NULL DEFAULT '' COMMENT '扩展码',
                `isneed_receipt` char(1) NOT NULL DEFAULT '' COMMENT '是否需要回执消息',
                `need_receipt_type` char(1) NOT NULL DEFAULT '' COMMENT '回执消息类型',
                `is_have_selected` char(1) NOT NULL DEFAULT '' COMMENT '是否需要查询明细',
                PRIMARY KEY (`id`) USING BTREE,
                UNIQUE KEY `Msg_Id` (`Msg_Id`) USING BTREE
            ) ENGINE = InnoDB  CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '短信提交通道返回msgid存储表' ROW_FORMAT = Dynamic;
            ";
        }
       
        Db::execute($sql);
        return $table_name;
    }

    public function createDeliverInfoMsgidForChannel($content)
    {
        $table_name = '';
        $table_name = 'yx_deliver_info_msgid_forchannel_' . $content;
        $sql = "CREATE TABLE IF NOT EXISTS  `" . $table_name . "`  (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `Msg_Id` varchar(20) NOT NULL DEFAULT '' COMMENT '短信提交消息id',
            `receive_time` varchar(10) NOT NULL DEFAULT '' COMMENT '回执时间戳',
            `Stat` varchar(10) NOT NULL DEFAULT '' COMMENT '客户消息id',
            `mobile` char(21) NOT NULL DEFAULT '' COMMENT '手机号',
            `Submit_time` varchar(12) NOT NULL DEFAULT '' COMMENT '回执包解析发送时间',
            `Done_time` varchar(12) NOT NULL DEFAULT '' COMMENT '回执包解析完成时间',
            PRIMARY KEY (`id`) USING BTREE,
            UNIQUE KEY `Msg_Id` (`Msg_Id`) USING BTREE
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='通道回执返回msgid存储表';
        ";
        Db::execute($sql);
        return $table_name;
    }

    public function createUpriverForChannel($content)
    {
        $table_name = '';
        $table_name = 'yx_upriver_info_msgid_forchannel_' . $content;
        $sql = "CREATE TABLE IF NOT EXISTS  `" . $table_name . "`  (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `mobile` char(21) NOT NULL DEFAULT '' COMMENT '手机号',
            `develop_code` varchar(20) NOT NULL DEFAULT '' COMMENT '扩展码',
            `message_info` varchar(255) NOT NULL DEFAULT '' COMMENT '回执内容',
            PRIMARY KEY (`id`) USING BTREE,
            KEY `mobile` (`mobile`) USING BTREE
          ) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='通道回执返回msgid存储表';
        ";
        Db::execute($sql);
        return $table_name;
    }
}
