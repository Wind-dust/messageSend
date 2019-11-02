<?php
// namespace CMPP30;
namespace app\console\com;

use app\console\Pzlife;

date_default_timezone_set("PRC");

define('CMPP_CONNECT', 0x00000001); // 请求连接
define('CMPP_CONNECT_RESP', 0x80000001); // 请求连接应答
define('CMPP_TERMINATE', 0x00000002); // 终止连接
define('CMPP_TERMINATE_RESP', 0x80000002); // 终止连接应答
define('CMPP_SUBMIT', 0x00000004); // 提交短信
define('CMPP_SUBMIT_RESP', 0x80000004); // 提交短信应答
define('CMPP_DELIVER', 0x00000005); // 短信下发
define('CMPP_DELIVER_RESP', 0x80000005); // 下发短信应答
define('CMPP_QUERY', 0x00000006); // 发送短信状态查询
define('CMPP_QUERY_RESP', 0x80000006); // 发送短信状态查询应答
define('CMPP_CANCEL', 0x00000007); // 删除短信
define('CMPP_CANCEL_RESP', 0x80000007); // 删除短信应答
define('CMPP_ACTIVE_TEST', 0x00000008); // 激活测试
define('CMPP_ACTIVE_TEST_RESP', 0x80000008); // 激活测试应答

class Cmpp extends Pzlife {
    protected $_normal_service_id   = "";
    protected $_template_service_id = "";
    protected $_msg_src             = "";
    protected $_serv_addr           = "";
    /**
     * Socket连接
     * @var resource
     */
    protected $_socket = NULL;

    /**
     * 消息流水号
     * 顺序累加,步长为1,循环使用（一对请求和应答消息的流水号必须相同）
     * @var int
     */
    protected $_sequence_number = 1;

    protected $_message_sequence = 1;

    /**
     * 发送的数据
     * 已经排除access_token值
     * @var mixed
     */
    protected $_sendData = NULL;

    /**
     * 收到的数据
     * @var string
     */
    protected $_responseData = NULL;

    public function __construct() {

        $this->_socket           = NULL;
        $this->_sequence_number  = 1;
        $this->_message_sequence = rand(1, 255);
    }

    protected function _log($message) {
        printf("%s\n", $message);
    }
    /**
     * PDU数据包转化ASCII数字
     * @param string $pdu
     * @return string
     */
    public static function pduord($pdu) {
        $ord_pdu = '';
        for ($i = 0; $i < strlen($pdu); $i++) {
            $ord_pdu .= sprintf("%02x", ord($pdu[$i])) . ' ';
        }

        if ($ord_pdu) {
            $ord_pdu = substr($ord_pdu, 0, -1);
        }

        return $ord_pdu;
    }

    //主机 端口 用户名 密码
    // template_service_id  template_service_id as  Service_Id 业务标识，是数字、字母和符号的组合。
    //$msg_src as  msg_src信息内容来源（SP_Id，SP的企业代码）
    //$serv_addr as Src_Id  源号码
    public function Start($host, $port, $username, $password, $normal_service_id, $template_service_id, $msg_src, $serv_addr) {
        $this->_normal_service_id   = $normal_service_id;
        $this->_template_service_id = $template_service_id;
        $this->_serv_addr           = $serv_addr;
        $this->_msg_src             = $msg_src;

        $this->_socket = fsockopen($host, $port, $errno, $errstr, 600 * 1200);
        if (!$this->_socket) {
            $this->_log("Error opening CMPP session.");
            $this->_log("Error was: $errstr.");
            return false;
        }
        socket_set_timeout($this->_socket, 600 * 1200);
        $status = $this->_CMPP_CONNECT($username, $password);
        if (false === $status) {
            $this->_log("Error Connect to CMPP server. Invalid credentials?");
        }

        return $status;
    }
    public function End() {
        if (!$this->_socket) {
            // not connected
            return;
        }
        $status = $this->_CMPP_TERMINATE();
        if (false == $status) {
            $this->_log("CMPP Server returned error $status");
            return false;
        }
        fclose($this->_socket);
        $this->_socket = NULL;
        return $status;
    }

    protected function _SendPDU($command_id, $pdu, $requestd) {
        $length = strlen($pdu) + 12;
        $header = pack("NNN", $length, $command_id, $this->_sequence_number);
        $this->_log("Sending PDU, len == $length");
        $this->_log("Sending PDU, header-len == " . strlen($header));
        $this->_log("Sending PDU, command_id == " . $command_id);
        $this->_log("Sending PDU, sequence_number == " . $this->_sequence_number);
        $this->_log("Sending PDU, header == " . self::pduord($header));

        $this->_sendData = $requestd;

        fwrite($this->_socket, $header . $pdu, $length);
        usleep(3000);
        $status                 = $this->_ExpectPDU($this->_sequence_number, $requestd);
        $this->_sequence_number = $this->_sequence_number + 1;
        return $status;
    }
    protected function _ExpectPDU($our_sequence_number, $requestd = NULL) {
        do {
            $this->_log("Trying to read PDU.");
            if (feof($this->_socket)) {
                $this->_log("Socket was closed.!!");
                return false;
            }
            $elength = fread($this->_socket, 4);
            if (empty($elength)) {
                $this->_log("Connection lost.");
                return false;
            }
            extract(unpack("Nlength", $elength));
            $this->_log("Reading PDU     : $length bytes.");
            $stream = fread($this->_socket, $length - 4);
            $this->_log("Stream len      : " . strlen($stream));
            extract(unpack("Ncommand_id/Nsequence_number", $stream));
            $command_id &= 0x0fffffff;
            $this->_log("Command id      : $command_id");
            $this->_log("sequence_number : $sequence_number");
            $pdu = substr($stream, 8);

            $data = NULL;
            switch ($command_id) {
            case CMPP_CONNECT:
                $this->_log("Got CMPP_CONNECT_RESP.");
                $data = $this->_CMPP_CONNECT_RESP($pdu, $requestd);
                break;
            case CMPP_SUBMIT:
                $this->_log("Got CMPP_SUBMIT_RESP.");
                $data = $this->_CMPP_SUBMIT_RESP($pdu, $requestd);
                break;
            case CMPP_TERMINATE:
                $this->_log("Got CMPP_TERMINATE_RESP.");
                break;
            case CMPP_ACTIVE_TEST:
                $this->_log("Got CMPP_ACTIVE_TEST.");
                break;
            default:
                $this->_log("Got unknown CMPP pdu.");
                break;
            }

            $this->_log("Received PDU: " . self::pduord(pack('N', $elength) . $stream));

        } while ($sequence_number != $our_sequence_number);

        $this->_responseData = $data;
        $command_status      = false === $data ? false : true;

        return $command_status;
    }
    protected function _CMPP_CONNECT($Source_Addr, $Shared_Secret, $Version = 0x20) {
        $data = array();
        // 源地址，此处为SP_Id，即SP的企业代码。
        $data['Source_Addr']         = $Source_Addr;
        $Source_Addr_len             = 6;
        $data['Shared_Secret']       = $Shared_Secret;
        $data['Timestamp']           = date('mdHis');
        $data['Version']             = $Version;
        $AuthenticatorSource_ori     = $data['Source_Addr'] . pack('a9', '') . $data['Shared_Secret'] . $data['Timestamp'];
        $data['AuthenticatorSource'] = md5($AuthenticatorSource_ori, true);
        $AuthenticatorSource_len     = 16;

        $format = "a{$Source_Addr_len}a{$AuthenticatorSource_len}CN";
        $pdu    = pack($format, $data['Source_Addr'], $data['AuthenticatorSource'], $data['Version'], $data['Timestamp']);

        $debug_data                        = $data;
        $debug_data['AuthenticatorSource'] = self::pduord($debug_data['AuthenticatorSource']);

        $this->_log("CMPP_CONNECT PDU: " . self::pduord($pdu));
        $this->_log("CMPP_CONNECT SPEC: " . $format);
        $this->_log("CMPP_CONNECT DATA: " . print_r($debug_data, true));
        unset($debug_data);

        return $this->_SendPDU(CMPP_CONNECT, $pdu, $data);
    }

    protected function _CMPP_CONNECT_RESP($pdu, $requestd) {
        $format = "CStatus/a16AuthenticatorISMG/CVersion";
        $data   = unpack($format, $pdu);

        $this->_log("CMPP_CONNECT_RESP PDU: " . self::pduord($pdu));
        $this->_log("CMPP_CONNECT_RESP SPEC: " . $format);
        $this->_log("CMPP_CONNECT_RESP DATA: " . print_r($data, true));

        $this->_log("AuthenticatorISMG: " . self::pduord($data['AuthenticatorISMG']));

        $status = intval($data['Status']);
        if (strcasecmp('0', $status)) {
            return self::cmppConnectRespStatusError($status);

        } elseif (0 == strlen($data['AuthenticatorISMG'])) {
            return 'ISMG认证码为空';
        }

        $checkAuthenticatorISMG = md5($data['Status'] . $requestd['AuthenticatorSource'] . $requestd['Shared_Secret'], true);
        if (strcmp($checkAuthenticatorISMG, $data['AuthenticatorISMG'])) {
            return 'ISMG认证码校验失败';
        }

        return $data;
    }

    protected function _CMPP_TERMINATE() {
        $data = "";
        $pdu  = "";
        $this->_log("CMPP_TERMINATE PDU: " . self::pduord($pdu));
        return $this->_SendPDU(CMPP_TERMINATE, $pdu, $data);
    }

    public static function cmppConnectRespStatusError($status) {
        $errors = array(
            0 => '正确',
            1 => '消息结构错',
            2 => '非法源地址',
            3 => '认证错',
            4 => '版本太高',
        );

        if ($status >= 0) {
            if ($status >= 5) {
                return '其他错误';
            } else {
                return $errors[$status];
            }
        } else {
            return '未知错误';
        }
    }

    protected function _CMPP_SUBMIT($data) {
        $Dest_terminal_Id_len = 21 * $data['DestUsr_tl'];
        $Msg_Content_len      = strlen($data['Msg_Content']);

        //$format = "N2CCCCa10CC21CCCa6a2a6a17a17a21Ca{$Dest_terminal_Id_len}Ca{$Msg_Content_len}a8";
        //$format = "a8CCCCa10Ca32CCCCa6a2a6a17a17a21Ca32CCa140a20";
        $format = "a8CCCCa10Ca32CCCCa6a2a6a17a17a21Ca32CCa{$Msg_Content_len}a20";
        $pdu    = pack($format
            , $data['Msg_Id']
            , $data['Pk_total'], $data['Pk_number'], $data['Registered_Delivery'], $data['Msg_level']
            , $data['Service_Id']
            , $data['Fee_UserType']
            , $data['Fee_terminal_Id'], $data['Fee_terminal_Type']
            , $data['TP_pId'], $data['TP_udhi'], $data['Msg_Fmt']
            , $data['Msg_src']
            , $data['FeeType']
            , $data['FeeCode']
            , $data['ValId_Time'], $data['At_Time']
            , $data['Src_Id']
            , $data['DestUsr_tl']
            , $data['Dest_terminal_Id'], $data['Dest_terminal_Type']
            , $data['Msg_Length']
            , $data['Msg_Content']
            , $data['Reserve']
        );

        $this->_log("CMPP_SUBMIT PDU: " . self::pduord($pdu));
        $this->_log("CMPP_SUBMIT SPEC: " . $format);
        $this->_log("CMPP_SUBMIT DATA: " . print_r($data, true));

        return $this->_SendPDU(CMPP_SUBMIT, $pdu, $data);
    }

    protected function _CMPP_SUBMIT_RESP($pdu, $requestd) {
        $format = "N2Msg_Id/NResult";
        //$format = "C8Msg_Id/CResult";
        $data = unpack($format, $pdu);

        $this->_log("CMPP_SUBMIT_RESP PDU: " . self::pduord($pdu));
        $this->_log("CMPP_SUBMIT_RESP SPEC: " . $format);
        $this->_log("CMPP_SUBMIT_RESP DATA: " . print_r($data, true));

        $result = intval($data['Result']);
        if (strcasecmp('0', $result)) {
            return self::cmppConnectRespStatusError($result);
        }

        return $data;
    }

    public static function cmppSubmitRespResultError($result) {
        $errors = array(
            0 => '正确',
            1 => '消息结构错',
            2 => '命令字错',
            3 => '消息序号重复',
            4 => '消息长度错',
            5 => '资费代码错',
            6 => '超过最大信息长',
            7 => '业务代码错',
            8 => '流量控制错',
        );

        if ($result >= 0) {
            if ($result >= 9) {
                return '其他错误';
            } else {
                return $errors[$result];
            }
        } else {
            return '未知错误';
        }
    }

    public function SendSms($mobile, $context, $text_encoding = 'UTF-8') {
        $data = Array();

        $data['Msg_Id']              = 0;
        $data['Registered_Delivery'] = 0;
        $data['Msg_level']           = 0;
        $data['Service_Id']          = $this->_normal_service_id;
        $data['Fee_UserType']        = 3;
        $data['Fee_terminal_Id']     = $mobile;
        $data['Fee_terminal_Type']   = 0;
        $data['TP_pId']              = 0;
        $data['TP_udhi']             = 1;
        $data['Msg_Fmt']             = 8;
        $data['Msg_src']             = $this->_msg_src;
        $data['FeeType']             = "01";
        $data['FeeCode']             = "000000";
        $data['ValId_Time']          = "";
        $data['At_Time']             = "";
        $data['Src_Id']              = $this->_serv_addr;
        $data['DestUsr_tl']          = 1;
        $data['Dest_terminal_Id']    = $mobile;
        $data['Dest_terminal_Type']  = 0;

        $txt_head     = 6;
        $txt_len      = 140;
        $max_len      = $txt_len - $txt_head;
        $Msg_Content  = mb_convert_encoding($context, "UCS-2BE", $text_encoding);
        $msg_sequence = $this->_message_sequence++;

        if (strlen($Msg_Content) < $max_len) {
            $data['Pk_total']    = 1;
            $data['Pk_number']   = 1;
            $udh                 = pack("cccccc", 5, 0, 3, $msg_sequence, 1, 1);
            $data['Msg_Content'] = $udh . $Msg_Content;
            $data['Msg_Length']  = strlen($data['Msg_Content']);
            $data['Reserve']     = '';
            $this->_log("CMPP_SUBMIT_DATA PDU: " . self::pduord($data['Msg_Content']));
            $status = $this->_CMPP_SUBMIT($data);
            $this->_log($status);
            return;
        }
        $pos          = 0;
        $num_messages = ceil(strlen($Msg_Content) / $max_len);
        $this->_log($num_messages);
        for ($i = 0; $i < $num_messages; $i++) {
            $data['Pk_total']    = $num_messages;
            $data['Pk_number']   = $i + 1;
            $udh                 = pack("cccccc", 5, 0, 3, $msg_sequence, $num_messages, $i + 1);
            $data['Msg_Content'] = $udh . substr($Msg_Content, $i * $max_len, $max_len);
            $data['Msg_Length']  = strlen($data['Msg_Content']);
            $data['Reserve']     = '';
            $this->_log("CMPP_SUBMIT_DATA PDU: " . self::pduord($data['Msg_Content']));
            $status = $this->_CMPP_SUBMIT($data);
            $this->_log($status);
        }
    }

    public function SendSmsT($mobile, $strKey, $param, $text_encoding = 'UTF-8') {
        $data = Array();

        $data['Msg_Id']              = 0;
        $data['Pk_total']            = 1;
        $data['Pk_number']           = 1;
        $data['Registered_Delivery'] = 0;
        $data['Msg_level']           = 0;
        $data['Service_Id']          = $this->_template_service_id;
        $data['Fee_UserType']        = 3;
        $data['Fee_terminal_Id']     = $mobile;
        $data['Fee_terminal_Type']   = 0;
        $data['TP_pId']              = 0;
        $data['TP_udhi']             = 0;
        $data['Msg_Fmt']             = 8;
        $data['Msg_src']             = $this->_msg_src;
        $data['FeeType']             = "01";
        $data['FeeCode']             = "000000";
        $data['ValId_Time']          = "";
        $data['At_Time']             = "";
        $data['Src_Id']              = $this->_serv_addr;
        $data['DestUsr_tl']          = 1;
        $data['Dest_terminal_Id']    = $mobile;
        $data['Dest_terminal_Type']  = 0;

        if (!is_array($param)) {$this->_log("Your params none!");return;}

        $context = "<cmppTemplate><template>{$strKey}</template>";
        for ($i = 0; $i < count($param); $i++) {
            $context .= sprintf("<node%d>%s</node%d>", $i + 1, $param[$i], $i + 1);
        }
        $context .= "</cmppTemplate>";
        $this->_log($context);

        $data['Msg_Content'] = mb_convert_encoding($context, "UCS-2BE", $text_encoding);
        $data['Msg_Length']  = strlen($data['Msg_Content']);
        $data['Reserve']     = '';

        $status = $this->_CMPP_SUBMIT($data);
        $this->_log($status);

    }

    public function Active() {
        $data = "";
        $this->_SendPDU(CMPP_ACTIVE_TEST, "", $data);
    }

    //当有号码发送需求时 进行提交
    /* redis 读取需要发送的数据 */
    // $send = $this->redis->lPop($redisMessageCodeSend);

    //每秒最大发送条数

    function sendtest($content) {
        //发送链接请求
        $redis = Phpredis::getConn();
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        $master               = 300; //通道最大提交量
        $security_coefficient = 0.8; //通道饱和系数
        $security_master      = $master * $security_coefficient;
        $socket   = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $contdata = $this->content($content);

        $host          = $contdata['host']; //服务商ip
        $port          = $contdata['port']; //短连接端口号   17890长连接端口号
        $Source_Addr   = $contdata['Source_Addr']; //企业id  企业代码
        $Shared_secret = $contdata['Shared_secret']; //网关登录密码
        $Service_Id    = $contdata['Service_Id'];
        $Dest_Id       = $contdata['Dest_Id']; //短信接入码 短信端口号
        $Sequence_Id   = $contdata['Sequence_Id'];
        $SP_ID         = $contdata['SP_ID'];

        $Version             = 0x20; //CMPP版本 0x20 2.0版本 0x30 3.0版本
        $Timestamp           = date('mdHis');
        $AuthenticatorSource = md5($Source_Addr . pack("a9", "") . $Shared_secret . $Timestamp, true);
        $bodyData            = pack("a6a16CN", $Source_Addr, $AuthenticatorSource, $Version, $Timestamp);
        $Command_Id          = 0x00000001;
        // $Total_Length        = strlen($bodyData) + 12;
        $Sequence_Id  = 1;
        $Total_Length = strlen($bodyData) + 12;
        $headData     = pack("NNN", $Total_Length, $Command_Id, $Sequence_Id);
        if (socket_write($socket, $headData . $bodyData, $Total_Length) == false) {
            echo 'fail to write' . socket_strerror(socket_last_error());die; //通道连接失败
        }

        $redisMessageCodeSend = Config::get('rediskey.message.redisMessageCodeSend');

        $time = 15;
        while (true) {

            sleep($time);
        }
        do {
            $i    = 1;
           
           
                do {
                    $send = $this->redis->lPop($redisMessageCodeSend);
                    if ($send) {
                        $send   = json_decode($send, true);
                        $mobile = $send['mobile'];
                        $code   = $send['code'];
                        $code   = mb_convert_encoding($code, 'GBK', 'UTF-8');
                        $i++;
                    }
                    
                    echo $i . "\n";
                } while ($i <= $security_master);
                $time = 1;
            
            sleep($time);
        } while (true);

    }

}