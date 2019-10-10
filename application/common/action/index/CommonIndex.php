<?php

namespace app\common\action\index;

use app\facade\DbUser;
use cache\Phpredis;
use Config;
use Env;

class CommonIndex {
    protected $redis;
    /**
     * user模块
     */
    protected $redisKey;
    protected $redisConIdTime; //conId到期时间的zadd
    protected $redisConIdUid; //conId和uid的hSet

    public function __construct() {
        $this->redis            = Phpredis::getConn();
        $this->redisKey         = Config::get('rediskey.user.redisKey');
        $this->redisConIdTime   = Config::get('rediskey.user.redisConIdTime');
        $this->redisConIdUid    = Config::get('rediskey.user.redisConIdUid');
        $this->redisAccessToken = Config::get('redisKey.weixin.redisAccessToken');
        $this->redisAccessTokenTencent = Config::get('redisKey.weixin.redisAccessTokenTencent');
        $this->redisTicketTencent = Config::get('redisKey.weixin.redisTicketTencent');
    }

    /**
     * 通过con_id获取uid
     * @param $conId
     * @return int
     * @author zyr
     */
    protected function getUidByConId($conId) {
        $uid             = 0;
        $expireTime      = 2592000; //30天过期
        $subTime         = bcsub(time(), $expireTime, 0);
        $conIdCreatetime = $this->redis->zScore($this->redisConIdTime, $conId); //保存时间
        if ($subTime <= $conIdCreatetime) { //已登录
            $uid = $this->redis->hGet($this->redisConIdUid, $conId);
        }
        if (empty($uid)) {
            $userCon = DbUser::getUserCon([['con_id', '=', $conId], ['update_time', '>=', $subTime]], 'uid', true);
            if (!empty($userCon)) {
                $uid = $userCon['uid'];
            }
        }
        return $uid;
    }

    /**
     * 判断是否登录
     * @param $conId
     * @return array
     * @author zyr
     */
    public function isLogin($conId) {
        $expireTime      = 2592000; //30天过期
        $conIdCreatetime = $this->redis->zScore($this->redisConIdTime, $conId); //保存时间
        if (bcsub(time(), $conIdCreatetime, 0) <= $expireTime) { //已登录
            $this->redis->zAdd($this->redisConIdTime, time(), $conId); //更新时间
            return ['code' => '200'];
        } else {
            if ($conIdCreatetime === false) { //con_id不存在
                if ($this->updateConId($conId) === true) { //查询数据库更新redis
                    return ['code' => '200'];
                }
            }
            $this->redis->zDelete($this->redisConIdTime, $conId);
            $this->redis->hDel($this->redisConIdUid, $conId);
        }
        return ['code' => '5000'];
    }

    /**
     * 更新缓存登录时间
     * @param $conId
     * @return bool
     * @author zyr
     */
    protected function updateConId($conId) {
        $expireTime = 2592000; //30天过期
        $subTime    = bcsub(time(), $expireTime, 0);
        $userCon    = DbUser::getUserCon([['con_id', '=', $conId], ['update_time', '>=', $subTime]], 'id,uid', true);
        if (!empty($userCon)) {
            $this->redis->zAdd($this->redisConIdTime, time(), $conId); //更新时间
            $this->redis->hSet($this->redisConIdUid, $conId, $userCon['uid']);
            if (DbUser::updateUserCon(['con_id' => $conId], $userCon['id'])) {
                return true;
            }
            return false;
        }
        return false;
    }

    protected function resetUserInfo($uid) {
        $user     = DbUser::getUser(['id' => $uid]);
        $saveTime = 300; //保存5分钟
        $this->redis->hMSet($this->redisKey . 'userinfo:' . $uid, $user);
        $this->redis->expireAt($this->redisKey . 'userinfo:' . $uid, bcadd(time(), $saveTime, 0)); //设置过期
    }

    /**
     * 获取微信access_token
     * @return array
     * @author rzc
     */
    protected function getWeiXinAccessToken() {
        $access_token = $this->redis->get($this->redisAccessToken);
        if (empty($access_token)) {
            $appid = Config::get('conf.weixin_miniprogram_appid');
            // $appid         = 'wx1771b2e93c87e22c';
            $secret = Config::get('conf.weixin_miniprogram_appsecret');
            // $secret        = '1566dc764f46b71b33085ba098f58317';
            $requestUrl = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appid . '&secret=' . $secret;
            $requsest_subject = json_decode(sendRequest($requestUrl), true);
            $access_token     = $requsest_subject['access_token'];
            if (!$access_token) {
                return false;
            }
            $this->redis->set($this->redisAccessToken,$access_token);
            $this->redis->expire($this->redisAccessToken, 6600);
        }
        
        return $access_token;
    }

    /**
     * 获取微信公众号access_token
     * @return array
     * @author rzc
     */
    protected function getWeiXinAccessTokenTencent() {
        $access_token = $this->redis->get($this->redisAccessTokenTencent);
        if (empty($access_token)) {
            $appid = Env::get('weixin.weixin_appid');
            $appid         = 'wx112088ff7b4ab5f3';
            $secret = Env::get('weixin.weixin_secret');
            $secret        = 'db7915c4a840421683be99c6d798757f';
            $requestUrl = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appid . '&secret=' . $secret;
            $requsest_subject = json_decode(sendRequest($requestUrl), true);
            if (!isset($requsest_subject['access_token'])) {
                return false;
            }
            $access_token     = $requsest_subject['access_token'];
            
            $this->redis->set($this->redisAccessTokenTencent,$access_token);
            $this->redis->expire($this->redisAccessTokenTencent, 6600);
        }
        
        return $access_token;
    }

    /**
     * 获取微信公众号微信jsapi_ticket
     * @return array
     * @author rzc
     */
    public function getTicketTencent($access_token){
        $TicketTencent = $this->redis->get($this->redisTicketTencent);
        if (empty($TicketTencent)) {
            $requestUrl = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token='.$access_token.'&type=jsapi';
            $requsest_subject = json_decode(sendRequest($requestUrl), true);
            if ($requsest_subject['errcode'] != 0) {
            } else {
                $TicketTencent = $requsest_subject['ticket'];
            }
            if (!$TicketTencent) {
                return false;
            }
            $this->redis->set($this->redisTicketTencent,$TicketTencent);
            $this->redis->expire($this->redisTicketTencent, 6600);
        }
        
        return $TicketTencent;
    }
}