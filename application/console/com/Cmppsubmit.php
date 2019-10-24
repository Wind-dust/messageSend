<?php

namespace app\console\com;

use app\console\Pzlife;
use Config;
use Env;
use think\Db;
use cache\Phpredis;

class Cmppsubmit extends Pzlife {
    // 设置项
    public $host          = "121.199.15.87"; //服务商ip
    public $port          = "7890"; //短连接端口号   17890长连接端口号
    public $Source_Addr   = "992174"; //企业id  企业代码
    public $Shared_secret = 'shyx11'; //网关登录密码
    public $Dest_Id       = "1069999999"; //短信接入码 短信端口号
    public $SP_ID         = "";
    public $SP_CODE       = "";
    public $Service_Id    = ""; //业务代码   这个是业务代码
    public $deliver;
    private $socket;
    private $Sequence_Id = 1;
    private $bodyData;
    private $AuthenticatorSource;
    public $CMPP_CONNECT          = 0x00000001; // 请求连接
    public $CMPP_CONNECT_RESP     = 0x80000001; // 请求连接
    public $CMPP_SUBMIT           = 0x00000004; // 短信发送
    public $CMPP_SUBMIT_RESP      = 0x80000004; // 发送短信应答
    public $CMPP_DELIVER          = 0x00000005; // 短信下发
    public $CMPP_DELIVER_RESP     = 0x80000005; // 下发短信应答
    public $CMPP_ACTIVE_TEST      = 0x00000008; // 激活测试
    public $CMPP_ACTIVE_TEST_RESP = 0x80000008; // 激活测试应答
    public $msgid                 = 1;
    public $tomsisdn              = '';
    public $contents              = '';

    public function __construct($argv1, $argv2) {
        if ($argv1) {
            $this->tomsisdn = $argv1;
            $this->contents = $argv2;
        } else {
            $this->log("has no canshu");exit;
        }
    }

    public function createSocket() {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!$this->socket) {echo "can't creat socket";exit;}
        $result = socket_connect($this->socket, $this->host, $this->port) or die(socket_strerror(socket_last_error()));
        $this->cmppConnect();
    }

    public function cmppConnect() {
        date_default_timezone_set('PRC');
        $Source_Addr = $this->Source_Addr;
        $Version     = 0x30;
        $Timestamp   = date('mdHis');
        //echo $Timestamp;
        $AuthenticatorSource       = $this->createAS($Timestamp);
        $bodyData                  = pack("a6a16CN", $Source_Addr, $AuthenticatorSource, $Version, $Timestamp);
        $this->AuthenticatorSource = $AuthenticatorSource;
        $this->send($bodyData, "CMPP_CONNECT");
    }

    public function cmppConnectResp() {
        echo "CMPP_CONNECT_RESP success \n";
        $body = unpack("CStatus/a16AuthenticatorISMG/CVersion", $this->bodyData);
        $this->cmppSubmit();
    }

    public function send($bodyData, $Command, $Sequence = 0) {
        $Command_Id = 0x00000001;
        if ($Command == "CMPP_CONNECT") { //cmpp连接
            $Command_Id = 0x00000001;
        } elseif ($Command == "CMPP_DELIVER_RESP") { //下发应答
            $Command_Id = 0x80000005;
        } elseif ($Command == "CMPP_ACTIVE_TEST_RESP") { //数据链路应答
            $Command_Id = 0x80000008;
        } elseif ($Command == "CMPP_SUBMIT") {
            $Command_Id = 0x00000004;
        }
        $Total_Length = strlen($bodyData) + 12;
        if ($Sequence == 0) {
            if ($this->Sequence_Id < 10) {
                $Sequence_Id = $this->Sequence_Id;
            } else {
                $Sequence_Id       = 1;
                $this->Sequence_Id = 1;
            }
            $this->Sequence_Id = $this->Sequence_Id + 1;
        } else {
            $Sequence_Id = $Sequence;
        }

        $headData = pack("NNN", $Total_Length, $Command_Id, $Sequence_Id);
        // 发送消息
        $this->log("send $Command_Id");
        socket_write($this->socket, $headData . $bodyData, $Total_Length);
        $this->listen($Sequence_Id);
    }

    public function listen($Sequence_Id) {
        // 处理头
        $headData = socket_read($this->socket, 12);
        if (empty($headData)) {
            $this->log("0000");
            return;
        }
        $head = unpack("NTotal_Length/NCommand_Id/NSequence_Id", $headData);
        $this->log("get " . ($head['Command_Id'] & 0x0fffffff));
        $Sequence_Id = $head['Sequence_Id'];
        // 处理body
        $this->bodyData = socket_read($this->socket, $head['Total_Length'] - 12);
        //var_dump($this->bodyData);
        switch ($head['Command_Id'] & 0x0fffffff) {
        case 0x00000001:
            $this->cmppConnectResp();
            break;
        // case 0x00000005:
        //     $this->CMPP_DELIVER($head['Total_Length'],$Sequence_Id);
        //     break;
        // case 0x80000005:
        //     $this->CMPP_DELIVER($head['Total_Length'],$Sequence_Id);
        //     break;
        case 0x00000008:
            $bodyData = pack("C", 1);
            $this->send($bodyData, "CMPP_ACTIVE_TEST_RESP", $Sequence_Id);
            break;
        case 0x00000004:
            $this->cmppSubmitResp();
            break;
        // case 0x80000004:
        //     $this->CMPP_SUBMIT_RESP();
        //     break;
        default:
            $bodyData = pack("C", 1);
            $this->send($bodyData, "CMPP_ACTIVE_TEST_RESP", $Sequence_Id);
            break;
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
            mysql_connect('localhost', '', '');
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
            }
            mysql_close();
            //echo $Msg_Id."\n";
            echo $data . "\n";
            echo $msgidzz . "\n";
            echo $Sequence_Id . "\n";
            $this->cmppDeliverResp($msgidzz, $Msg_Idfu, $Sequence_Id);
        }
    }

    // N打包只有4位
    public function cmppDeliverResp($Msg_Id, $Msg_Idfu, $Sequence_Id) {
        $sendda2  = 0x00;
        $bodyData = pack("NNN", $Msg_Id, $Msg_Idfu, $sendda2);
        $this->send($bodyData, "CMPP_DELIVER_RESP", $Sequence_Id);
    }

    public function cmppSubmit() {
        $Msg_Id = rand(1, 100);
        //$bodyData = pack("a8", $Msg_Id);
        $bodyData = pack("N", $Msg_Id) . pack("N", "00000000");
        $bodyData .= pack("C", 1) . pack("C", 1);
        $bodyData .= pack("C", 0) . pack("C", 0);
        $bodyData .= pack("a10", $this->Service_Id);
        $bodyData .= pack("C", 0) . pack("a32", "") . pack("C", 0) . pack("C", 0) . pack("C", 0) . pack("C", 0) . pack("a6", $this->SP_ID) . pack("a2", "02") . pack("a6", "") . pack("a17", "") . pack("a17", "") . pack("a21", $this->Dest_Id) . pack("C", 1);
        $bodyData .= pack("a32", $this->tomsisdn);
        $bodyData .= pack("C", 0);
        $len = strlen($this->contents);
        $bodyData .= pack("C", $len);
        $bodyData .= pack("a" . $len, $this->contents);
        $bodyData .= pack("a20", "00000000000000000000");
        //echo '内容长度:包总长度-183='.(strlen($bodyData)-183)."字节\n";
        $this->send($bodyData, "CMPP_SUBMIT", $Msg_Id);
    }

    public function cmppSubmitResp() {
        echo "CMPP_SUBMIT_RESP success" . "\n";
        $body = unpack("N2Msg_Id/NResult", $this->bodyData);
        print_r($body);
        socket_close($this->socket);
    }

    /**AuthenticatorSource = MD5(Source_Addr+9 字节的0 +shared secret+timestamp) */
    public function createAS($Timestamp) {
        $temp = $this->Source_Addr . pack("a9", "") . $this->Shared_secret . $Timestamp;
        return md5($temp, true);
    }

    public function log($data, $line = null) {
        if ($line) {
            $data = $line . " : " . $data;
        }
        file_put_contents("./cmpp1.log", print_r($data, true) . PHP_EOL, FILE_APPEND);
    }
}
