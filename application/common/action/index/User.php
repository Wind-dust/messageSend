<?php

namespace app\common\action\index;

use app\common\action\notify\Note;
use app\facade\DbAdmin;
use app\facade\DbAdministrator;
use app\facade\DbImage;
use app\facade\DbProvinces;
use app\facade\DbUser;
use Config;
use Env;
use think\Db;

class User extends CommonIndex {
    private $cipherUserKey = 'userpass'; //用户密码加密key
    // private $userRedisKey = 'index:user:'; //用户密码加密key
    private $note;

    public function __construct() {
        parent::__construct();
        $this->note = new Note();
    }

    /**
     * 账号密码登录
     * @param $mobile
     * @param $password
     * @param $buid
     * @return array
     * @author zyr
     */
    public function login($mobile, $password) {
        $user = DbUser::getUserOne(['mobile' => $mobile], 'id,passwd');
        if (empty($user)) {
            return ['code' => '3002'];
        }
        $uid = $user['id'];
        if (empty($uid)) {
            return ['code' => '3002'];
        }
        $cipherPassword = $this->getPassword($password, $this->cipherUserKey); //加密后的password
        if ($cipherPassword != $user['passwd']) {
            return ['code' => '3003'];
        }
        $conId          = $this->createConId();
        $userCon        = DbUser::getUserCon(['uid' => $uid], 'id,con_id', true);

        Db::startTrans();
        try {
            if (empty($userCon)) { //第一次登录，未生成过con_id
                $data = [
                    'uid'    => $uid,
                    'con_id' => $conId,
                ];
                DbUser::addUserCon($data);
            } else {
                DbUser::updateUserCon(['con_id' => $conId], $userCon['id']);
                $this->redis->hDel($this->redisConIdUid, $userCon['con_id']);
                // $this->redis->zDelete($this->redisConIdTime, $userCon['con_id']);
                $this->redis->zRem($this->redisConIdTime, $userCon['con_id']);
            }
            
            $this->redis->zAdd($this->redisConIdTime, time(), $conId);
            $conUid = $this->redis->hSet($this->redisConIdUid, $conId, $uid);
            if ($conUid === false) {
                $this->redis->zDelete($this->redisConIdTime, $conId);
                $this->redis->hDel($this->redisConIdUid, $conId);
            }
            Db::commit();
            return ['code' => '200', 'con_id' => $conId];
        } catch (\Exception $e) {
            exception($e);
            Db::rollback();
            return ['code' => '3004'];

        }
    }


    /**
     * 重置密码
     * @param $mobile
     * @param $vercode
     * @param $password
     * @return array
     * @author zyr
     */
    public function resetPassword($mobile, $vercode, $password) {
        $stype = 2;
        $uid   = $this->checkAccount($mobile);
        if (empty($uid)) {
            return ['code' => '3002'];
        }
        if ($this->checkVercode($stype, $mobile, $vercode) === false) {
            return ['code' => '3006']; //验证码错误
        }
        $cipherPassword = $this->getPassword($password, $this->cipherUserKey); //加密后的password
        $result         = DbUser::updateUser(['passwd' => $cipherPassword], $uid);
        if ($result) {
            $this->redis->del($this->redisKey . 'vercode:' . $mobile . ':' . $stype); //成功后删除验证码
            return ['code' => '200'];
        }
        return ['code' => '3003'];
    }


    /**
     * 验证用户是否存在
     * @param $mobile
     * @return bool
     * @author zyr
     */
    private function checkAccount($mobile) {
        $user = DbUser::getUserOne(['mobile' => $mobile], 'id');
        if (!empty($user)) {
            return $user['id'];
        }
        return 0;
    }

    /**
     * 生成并发送验证码
     * @param $mobile
     * @param $stype
     * @return array
     * @author zyr
     */
    public function sendVercode($mobile, $stype) {
        $redisKey   = $this->redisKey . 'vercode:' . $mobile . ':' . $stype;
        $timeoutKey = $this->redisKey . 'vercode:timeout:' . $mobile . ':' . $stype;
        $code       = $this->createVercode($redisKey, $timeoutKey);
        if (empty($code)) { //已发送过验证码
            return ['code' => '3003']; //一分钟内不能重复发送
        }
        if ($stype == 5) {
            $content = getVercodeContent($code, 5); //短信内容
        } else {
            $content = getVercodeContent($code); //短信内容
        }
        $result = $this->note->sendSms($mobile, $content); //发送短信
        if ($result['code'] != '200') {
            $this->redis->del($timeoutKey);
            $this->redis->del($redisKey);
            return ['code' => '3004']; //短信发送失败
        }
        DbUser::addLogVercode(['stype' => $stype, 'code' => $code, 'mobile' => $mobile]);
        return ['code' => '200'];
    }

    /**
     * 验证提交的验证码是否正确
     * @param $stype
     * @param $mobile
     * @param $vercode
     * @return bool
     * @author zyr
     */
    private function checkVercode($stype, $mobile, $vercode) {
        $redisKey  = $this->redisKey . 'vercode:' . $mobile . ':' . $stype;
        $redisCode = $this->redis->get($redisKey); //服务器保存的验证码
        if ($redisCode == $vercode) {
            return true;
        }
        return false;
    }

    /**
     * 生成并保存验证码
     * @param $redisKey
     * @param $timeoutKey
     * @return string
     * @author zyr
     */
    private function createVercode($redisKey, $timeoutKey) {
        if (!$this->redis->setNx($timeoutKey, 1)) {
            return '0'; //一分钟内不能重复发送
        }
        $this->redis->setTimeout($timeoutKey, 60); //60秒自动过期
        $code = randCaptcha(6); //生成验证码
        if ($this->redis->setEx($redisKey, 600, $code)) { //不重新发送酒10分钟过期
            return $code;
        }
        return '0';
    }


    /**
     * 获取用户信息
     * @param $conId
     * @return array
     * @author zyr
     */
    public function getUser($conId) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3003'];
        }
        if ($this->redis->exists($this->redisKey . 'userinfo:' . $uid)) {
            $res = $this->redis->hGetAll($this->redisKey . 'userinfo:' . $uid);
        } else {
            $res = DbUser::getUser(['id' => $uid]);
            if (empty($res)) {
                return ['code' => '3000'];
            }
            $res['uid'] = enUid($res['id']);
            unset($res['id']);
            $this->saveUser($uid, $res);
        }
        if (empty($res)) {
            return ['code' => '3000'];
        }
        unset($res['id']);
        return ['code' => 200, 'data' => $res];
    }



    /**
     * 保存用户信息(记录到缓存)
     * @param $id
     * @param $user
     * @author zyr
     */
    private function saveUser($id, $user) {
        $saveTime = 300; //保存5分钟
        $this->redis->hMSet($this->redisKey . 'userinfo:' . $id, $user);
        $this->redis->expireAt($this->redisKey . 'userinfo:' . $id, bcadd(time(), $saveTime, 0)); //设置过期
    }

    /**
     * 密码加密
     * @param $str
     * @param $key
     * @return string
     * @author zyr
     */
    private function getPassword($str, $key) {
        $algo   = Config::get('conf.cipher_algo');
        $md5    = hash_hmac('md5', $str, $key);
        $key2   = strrev($key);
        $result = hash_hmac($algo, $md5, $key2);
        return $result;
    }

    /**
     * 创建唯一conId
     * @author zyr
     */
    private function createConId() {
        $conId = uniqid(date('ymdHis'));
        $conId = hash_hmac('ripemd128', $conId, '');
        return $conId;
    }

    /**
     * 生成二维码
     * @param $link
     * @return string
     * @author rzc
     */
    public function getQrcode($conId, $page, $scene, $stype) {
        $uid    = $this->getUidByConId($conId);
        $Upload = new Upload;
        if (empty($uid)) {
            return ['code' => '3000'];
        }
        // 先查询是否有已存在图片
        $has_QrImage = DbImage::getUserImage('*', ['uid' => $uid, 'stype' => $stype], true);
        if (!empty($has_QrImage)) {
            $Qrcode = $has_QrImage['image'];
            return ['code' => '200', 'Qrcode' => $Qrcode];
        }
        $result = $this->createQrcode($scene, $page);
        // print_r(strlen($result));die;
        // print_r (imagecreatefromstring($result));die;
        if (strlen($result) > 100) {
            // $img_file = 'd:/test.png';
            $file = fopen(Config::get('conf.image_path') . $conId . '.png', "w"); //打开文件准备写入
            fwrite($file, $result); //写入
            fclose($file); //关闭
            // 开始上传,调用上传方法
            $upload = $Upload->uploadUserImage($conId . '.png');
            if ($upload['code'] == 200) {
                $logImage = DbImage::getLogImage($upload, 2); //判断时候有未完成的图片
                // print_r($logImage);die;
                if (empty($logImage)) { //图片不存在
                    return ['code' => '3010']; //图片没有上传过
                }
                $upUserInfo          = [];
                $upUserInfo['uid']   = $uid;
                $upUserInfo['stype'] = $stype;
                $upUserInfo['image'] = $upload['image_path'];
                Db::startTrans();
                try {
                    $save = DbImage::saveUserImage($upUserInfo);
                    if (!$save) {
                        return ['code' => '3011'];
                    }
                    DbImage::updateLogImageStatus($logImage, 1); //更新状态为已完成
                    $new_Qrcode = Config::get('qiniu.domain') . '/' . $upload['image_path'];
                    Db::commit();
                    return ['code' => '200', 'Qrcode' => $new_Qrcode];
                } catch (\Exception $e) {
                    print_r($e);
                    Db::rollback();
                    return ['code' => '3011']; //添加失败
                }
            } else {
                return ['code' => '3009'];
            }
            // echo $result;die;
        } else {
            $result = json_decode($result,true);
            return ['code' => $result['errcode'],'errmsg' => $result['errmsg']];

        }
    }

    function sendRequest2($requestUrl, $data = []) {
        $curl = curl_init();
        $data = json_encode($data);
        curl_setopt($curl, CURLOPT_URL, $requestUrl);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8', 'Content-Length:' . strlen($data)]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    }

    public function createQrcode($scene, $page) {
        $access_token = $this->getWeiXinAccessToken();
        if (!$access_token) {
            return ['code' => '3005'];
        }
        $requestUrl = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=' . $access_token;
        // print_r($link);die;
        $result = $this->sendRequest2($requestUrl, ['scene' => $scene, 'page' => $page]);
        return $result;
    }


    /**
     * 微信授权
     * @param $code
     * @param $redirect_uri
     * @return array
     * @author rzc
     */

    public function wxaccredit($redirect_uri) {
        $appid = Env::get('weixin.weixin_appid');
        // $appid         = 'wx1771b2e93c87e22c';
        $secret = Env::get('weixin.weixin_secret');
        // $secret        = '1566dc764f46b71b33085ba098f58317';
        $requestUrl = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $appid . '&redirect_uri=' . $redirect_uri . '&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect';
        return ['code' => 200, 'requestUrl' => $requestUrl];

    }

    private function getaccessToken($code) {
        $appid = Env::get('weixin.weixin_appid');
        // $appid         = 'wx1771b2e93c87e22c';
        $secret = Env::get('weixin.weixin_secret');
        // $secret        = '1566dc764f46b71b33085ba098f58317';
        $get_token_url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $appid . '&secret=' . $secret . '&code=' . $code . '&grant_type=authorization_code';
        $res           = sendRequest($get_token_url);
        $result        = json_decode($res, true);
        if (empty($result['openid'])) {
            return false;
        }
        return $result;
    }

    private function getunionid($openid, $access_token) {
        $appid = Env::get('weixin.weixin_appid');
        // $appid         = 'wx1771b2e93c87e22c';
        $secret = Env::get('weixin.weixin_secret');
        // $secret        = '1566dc764f46b71b33085ba098f58317';
        $get_token_url = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $access_token . '&openid=' . $openid . '&lang=zh_CN';
        $res           = sendRequest($get_token_url);
        $result        = json_decode($res, true);
        if (empty($result['openid'])) {
            return false;
        }
        return $result;
    }

    public function userRegistered($nick_name, $user_type, $passwd, $mobile, $email, $vercode){

        $stype = 1;
        if ($this->checkVercode($stype, $mobile, $vercode) === false) {
            return ['code' => '3004']; //验证码错误
        }

        if (!empty($this->checkAccount($mobile))) {
            return ['code' => '3005'];//该手机号已注册
        }
        $cipherPassword = $this->getPassword($passwd, $this->cipherUserKey); //加密后的password

        $data = [
            'mobile'    => $mobile,
            'passwd'    => $cipherPassword,
            'nick_name' => $nick_name,
            'user_type' => $user_type,
            'email'     => $email,
        ];

        Db::startTrans();
        try {
            $uid = DbUser::addUser($data); //添加后生成的uid
            $conId = $this->createConId();
            DbUser::addUserCon(['uid' => $uid, 'con_id' => $conId]);
            $this->redis->zAdd($this->redisConIdTime, time(), $conId);
            $conUid = $this->redis->hSet($this->redisConIdUid, $conId, $uid);
            if ($conUid === false) {
                $this->redis->zDelete($this->redisConIdTime, $conId);
                $this->redis->hDel($this->redisConIdUid, $conId);
                Db::rollback();
            }
            $this->redis->del($this->redisKey . 'vercode:' . $mobile . ':' . $stype); //成功后删除验证码
            Db::commit();
            return ['code' => '200', 'con_id' => $conId];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3009'];
        }
    }

    public function quickLogin($mobile, $vercode){
        $stype = 3;
        if ($this->checkVercode($stype, $mobile, $vercode) === false) {
            return ['code' => '3006']; //验证码错误
        }
        $uid        = $this->checkAccount($mobile); //通过手机号获取uid
        if (empty($uid)) {
            return ['code' => '3005'];//该手机号未注册
        }
        $userCon = [];
        $userCon = DbUser::getUserCon(['uid' => $uid], 'id,con_id', true);
        Db::startTrans();
        try {
            $conId = $this->createConId();
            
            if (!empty($userCon)) {
                DbUser::updateUserCon(['con_id' => $conId], $userCon['id']);
            } else {
                DbUser::addUserCon(['uid' => $uid, 'con_id' => $conId]);
            }
            if (!empty($userCon)) {
                $this->redis->hDel($this->redisConIdUid, $userCon['con_id']);
                $this->redis->zDelete($this->redisConIdTime, $userCon['con_id']);
            }
            $this->redis->zAdd($this->redisConIdTime, time(), $conId);
            $conUid = $this->redis->hSet($this->redisConIdUid, $conId, $uid);
            if ($conUid === false) {
                $this->redis->zDelete($this->redisConIdTime, $conId);
                $this->redis->hDel($this->redisConIdUid, $conId);
                Db::rollback();
            }
            $this->redis->del($this->redisKey . 'vercode:' . $mobile . ':' . $stype);
            DbUser::updateUser(['last_time' => time()], $uid);
            
            Db::commit();
            return ['code' => '200', 'con_id' => $conId];
        } catch (\Exception $e) {
            Db::rollback();
            Db::table('pz_log_error')->insert(['title' => '/user/quickLogin/quickLogin', 'data' => $e]);
            return ['code' => '3007'];
        }
    }

    public function apportionSonUser($conId, $nick_name, $user_type, $passwd, $mobile, $email){
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) { //用户不存在
            return ['code' => '3003'];
        }
        if (!empty($this->checkAccount($mobile))) {
            return ['code' => '3005'];//该手机号已注册
        }
        $cipherPassword = $this->getPassword($passwd, $this->cipherUserKey); //加密后的password
        $data = [
            'pid'       => $uid,
            'mobile'    => $mobile,
            'passwd'    => $cipherPassword,
            'nick_name' => $nick_name,
            'user_type' => $user_type,
            'email'     => $email,
        ];

        Db::startTrans();
        try {
            $uid = DbUser::addUser($data); //添加后生成的uid
            Db::commit();
            return ['code' => '200', 'con_id' => $conId];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3009'];
        }
    }

    public function recordUserQualification($conId,$data){
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) { //用户不存在
            return ['code' => '3003'];
        }

        Db::startTrans();
        try {
            $uid = DbAdministrator::addUserQualificationRecord($data); //添加后生成的uid
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3009'];
        }
    }

    public function seetingUserEquities($conId, $mobile, $business_id, $agency_price){
        $business = DbAdministrator::getBusiness(['id' => $business_id],'*',true);
        if (empty($business)) {
            return ['code' => '3007'];
        }
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) { //用户不存在
            return ['code' => '3003'];
        }
        $user_equities = DbAdministrator::getUserEquities(['business_id' => $business_id,'uid' => $uid],'*',true);
        if (empty($user_equities)) {
            return ['code' => '3003'];
        }
        if ($agency_price < $user_equities['agency_price']){
            return ['code' => '3004'];
        }
        $son_user = DbUser::getUserOne(['mobile' => $mobile], 'id,pid');
        if (empty($son_user) || $uid != $son_user['pid']) {
            return ['code' => '3008'];
        }
        if (DbAdministrator::getUserEquities(['uid' => $son_user['id'], 'business_id' => $business_id],'id',true)) {
            return ['code' => '3005'];
        }
        $data = [];
        $data = [
            'business_id' => $business_id,
            'num_balance' => $business['donate_num'],
            'uid'         => $uid,
        ];
        if ($agency_price){
            if ($agency_price < $business['price']){
                return ['code' => '3004'];
            }
            $data['agency_price'] = $agency_price;
        }else {
            $data['agency_price'] = $business['price'];
        }
        Db::startTrans();
        try {
            DbUser::addUserEquities($data);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3009']; //修改失败
        }
    }
}