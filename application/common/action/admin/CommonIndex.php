<?php

namespace app\common\action\admin;

use app\facade\DbAdmin;
use cache\Phpredis;
use Config;
use Env;

class CommonIndex {
    protected $redis;
    protected $redisCmsConIdTime;
    protected $redisCmsConIdUid;
    protected $redisAccessToken;
    protected $redisConIdTime; //conId到期时间的zadd
    protected $redisConIdUid; //conId和uid的hSet

    /**
     * user模块
     */
    protected $redisKey;

    public function __construct() {
        $this->redis             = Phpredis::getConn();
        $this->redisCmsConIdTime = Config::get('rediskey.user.redisCmsConIdTime');
        $this->redisCmsConIdUid  = Config::get('rediskey.user.redisCmsConIdUid');
        $this->redisAccessToken  = Config::get('redisKey.weixin.redisAccessToken');
        $this->redisConIdTime   = Config::get('rediskey.user.redisConIdTime');
        $this->redisConIdUid    = Config::get('rediskey.user.redisConIdUid');

    }
   

    /**
     * 判断是否登录
     * @param $cmsConId
     * @return array
     * @author zyr
     */
    public function isLogin($cmsConId) {
        if (empty($cmsConId)) {
            return ['code' => '5000'];
        }
        if (strlen($cmsConId) != 32) {
            return ['code' => '5000'];
        }
        $expireTime      = 172800; //2天过期
        $conIdCreatetime = $this->redis->zScore($this->redisCmsConIdTime, $cmsConId); //保存时间
        if (bcsub(time(), $conIdCreatetime, 0) <= $expireTime) { //已登录
            $this->redis->zAdd($this->redisCmsConIdTime, time(), $cmsConId); //更新时间
            $adminId = $this->redis->hGet($this->redisCmsConIdUid, $cmsConId);
            if (empty($adminId)) {
                $this->redis->zRem($this->redisCmsConIdTime, $cmsConId);
                $this->redis->hDel($this->redisCmsConIdUid, $cmsConId);
                return ['code' => '5000'];
            }
            $adminInfo = DbAdmin::getAdminInfo(['id' => $adminId], 'status', true);
            if (empty($adminInfo)) {
                $this->redis->zRem($this->redisCmsConIdTime, $cmsConId);
                $this->redis->hDel($this->redisCmsConIdUid, $cmsConId);
                return ['code' => '5000'];
            }
            if ($adminInfo['status'] == '2') {
                return ['code' => '5001']; //账号已停用
            }
            return ['code' => '200'];
        }
        $this->redis->zRem($this->redisCmsConIdTime, $cmsConId);
        $this->redis->hDel($this->redisCmsConIdUid, $cmsConId);
        return ['code' => '5000'];
    }

    /**
     * 通过cms_con_id获取admin_id
     * @param $cmsConId
     * @return int
     * @author zyr
     */
    protected function getUidByConId($cmsConId) {
//        $adminId         = 0;
        //        $expireTime      = 172800;//30天过期
        //        $subTime         = bcsub(time(), $expireTime, 0);
        //        $conIdCreatetime = $this->redis->zScore($this->redisCmsConIdTime, $cmsConId);//保存时间
        //        if ($subTime <= $conIdCreatetime) {//已登录
        $adminId = $this->redis->hGet($this->redisCmsConIdUid, $cmsConId);
//        }
        return $adminId;
    }

    /**
     * 获取微信access_token
     * @return array
     * @author rzc
     */
    public function getWeiXinAccessToken() {
        $access_token = $this->redis->get($this->redisAccessToken);
        if (empty($access_token)) {
            $appid = Config::get('conf.weixin_miniprogram_appid');
            // $appid         = 'wx1771b2e93c87e22c';
            $secret = Config::get('conf.weixin_miniprogram_appsecret');
            // $secret        = '1566dc764f46b71b33085ba098f58317';
            $requestUrl       = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appid . '&secret=' . $secret;
            $requsest_subject = json_decode(sendRequest($requestUrl), true);
            $access_token     = $requsest_subject['access_token'];
            if (!$access_token) {
                return false;
            }
            $this->redis->set($this->redisAccessToken, $access_token);
            $this->redis->expire($this->redisAccessToken, 6600);
        }

        return $access_token;
    }
}