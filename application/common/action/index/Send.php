<?php

namespace app\common\action\index;

use app\common\action\index\Cmpp30;
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
        $this->cmpp->Start("124.251.111.5",17890,"yxyx01","bMtHJY96","","","","");
        $result = $this->cmpp->sendSms($mobile, $code); //发送短信
        return $result;
        // die;
        // $cmpp = new Cmppsubmit($mobile,$code);
        // ;
        // $cmpp->createSocket();
        // $cmpp->CMPP_CONNECT();
        // print_r($cmpp->CMPP_SUBMIT());
        // $tomsisdn = $_POST["tomsisdn"];
        $tomsisdn = $mobile;
        // $contents = $_POST["contents"];
        
        $contents = $code;
        // echo realpath("../");die;
        $str = "php -f ".realpath("../")."/application/common/action/index/Cmppsubmit.php {$tomsisdn} {$contents}";
        echo $str."\n";
        exec($str, $out, $res);
        // print_r(exec($str, $out, $res));
        if($res === 0)
        // echo $out[1];
        print_r($out);
        die;
        // return $result;
    }


   
}