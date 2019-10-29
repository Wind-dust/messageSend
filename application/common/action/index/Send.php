<?php

namespace app\common\action\index;

use app\common\action\index\Cmpp30;
use app\common\action\index\Owncmpp;
use app\common\action\index\Cmppsubmit;
use app\facade\DbAdmin;
use app\facade\DbAdministrator;
use app\facade\DbImage;
use app\facade\DbProvinces;
use app\facade\DbUser;
use Config;
use Env;
use think\Db;

class Send extends CommonIndex {
    private $cipherUserKey = 'userpass'; //用户密码加密key
    // private $userRedisKey = 'index:user:'; //用户密码加密key
    private $cmpp;

    public function __construct() {
        parent::__construct();
        $this->cmpp = new Cmpp30();
        $this->Owncmpp = new Owncmpp();
    }

    /**
     * 账号密码登录
     * @param $mobile
     * @param $password
     * @param $buid
     * @return array
     * @author zyr
     */
    public function cmppSendTest($mobile, $code) {
        // $this->cmpp->Start("124.251.111.5",9000,"yxyx01","bMtHJY96","","","","");
        // $result = $this->cmpp->sendSms($mobile, $code); //发送短信
        // return $result;
        // die;
        // $cmpp = new Cmppsubmit($mobile,$code);
        // ;
        // $cmpp->createSocket();
        // $cmpp->CMPP_CONNECT();
        // print_r($cmpp->CMPP_SUBMIT());
        // $tomsisdn = $_POST["tomsisdn"];
       
        // $contents = $_POST["contents"];

        // $this->Owncmpp->Start("116.62.88.162", "8592", "101161", "5hsey6u9", "106928080159", "217062");
        // $result = $this->Owncmpp->cmppSubmit($mobile,$code);
        // die;
        $tomsisdn = $mobile;
        $contents = $code;
        // echo realpath("../");die;
        // $str = "php -f ".realpath("../")."/application/common/action/index/Cmppsubmit.php {$tomsisdn} {$contents}";
        // echo $str."\n";
        // exec($str, $out, $res);
        // print_r(exec($str, $out, $res));
        // if($res === 0)
        // echo $out[1];
        // print_r($out);
        // die;
        // return $result;


        $host          = "116.62.88.162"; //服务商ip
        $port          = "8592";//短连接端口号   17890长连接端口号
        $Source_Addr   = "101161"; //企业id  企业代码
        $Shared_secret = '5hsey6u9'; //网关登录密码
        $Dest_Id       = "106928080159"; //短信接入码 短信端口号
        
        $Sequence_Id       = 1;
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket < 0) {
            echo "socket_create() failed: reason: " . socket_strerror($socket) . "\n";
        }else {
            echo "OK.\n";
        }
        echo "试图连接 '$host' 端口 '$port'...\n";
        $result = socket_connect($socket, $host, $port);

        if ($result < 0) {
            echo "socket_connect() failed.\nReason: ($result) " . socket_strerror($result) . "\n";
        }else {
            echo "连接OK\n";
        }
        date_default_timezone_set('PRC');
        $Version     = 0x30;
        $Timestamp   = date('mdHis');
        $AuthenticatorSource = md5($Source_Addr . pack("a9", "") . $Shared_secret . $Timestamp, true);
        $bodyData                  = pack("a6a16CN", $Source_Addr, $AuthenticatorSource, $Version, $Timestamp);
        $Command_Id = 0x00000001;
        $Total_Length = strlen($bodyData) + 12;
        $headData = pack("NNN", $Total_Length, $Command_Id, $Sequence_Id);
        socket_write($socket, $headData . $bodyData, $Total_Length);
        $headData = socket_read($socket, 12);
        print_r($socket);die;
        // echo $AuthenticatorSource;
        print_r(socket_write($socket, $headData . $bodyData, $Total_Length));
        die;
    }


   
}