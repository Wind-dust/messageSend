<?php

namespace app\common\action\supadmin;

use app\facade\DbGoods;
use cache\Phpredis;
use Config;
use Env;

class CommonIndex {
    protected $redis;
    protected $redisSupConIdTime;
    protected $redisSupConIdUid;
    protected $redisAccessToken;
    /**
     * user模块
     */
    protected $redisKey;

    public function __construct() {
        $this->redis             = Phpredis::getConn();
        $this->redisSupConIdTime = Config::get('rediskey.user.redisSupConIdTime');
        $this->redisSupConIdUid  = Config::get('rediskey.user.redisSupConIdUid');
    }

    /**
     * 判断是否登录
     * @param $supConId
     * @return array
     * @author zyr
     */
    public function isLogin($supConId) {
        if (empty($supConId)) {
            return ['code' => '5000'];
        }
        if (strlen($supConId) != 32) {
            return ['code' => '5000'];
        }
        $expireTime      = 172800; //2天过期
        $conIdCreatetime = $this->redis->zScore($this->redisSupConIdTime, $supConId); //保存时间
        if (bcsub(time(), $conIdCreatetime, 0) <= $expireTime) { //已登录
            $this->redis->zAdd($this->redisSupConIdTime, time(), $supConId); //更新时间
            $supAdminId = $this->redis->hGet($this->redisSupConIdUid, $supConId);
            if (empty($supAdminId)) {
                $this->redis->zDelete($this->redisSupConIdTime, $supConId);
                $this->redis->hDel($this->redisSupConIdUid, $supConId);
                return ['code' => '5000'];
            }
            $supAdmin = DbGoods::getSupAdmin(['id' => $supAdminId], 'id,status', true);
            if (empty($supAdmin)) {
                $this->redis->zDelete($this->redisSupConIdTime, $supConId);
                $this->redis->hDel($this->redisSupConIdUid, $supConId);
                return ['code' => '5000'];
            }
            if ($supAdmin['status'] == '2') {
                return ['code' => '5001']; //账号已停用
            }
            return ['code' => '200'];
        }
        $this->redis->zDelete($this->redisSupConIdTime, $supConId);
        $this->redis->hDel($this->redisSupConIdUid, $supConId);
        return ['code' => '5000'];
    }

    /**
     * 通过sup_con_id获取sup_admin_id
     * @param $supConId
     * @return int
     * @author zyr
     */
    protected function getUidByConId($supConId) {
        $supAdminId = $this->redis->hGet($this->redisSupConIdUid, $supConId);
        return $supAdminId;
    }
}