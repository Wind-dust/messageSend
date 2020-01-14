<?php

namespace cache;

use Config;
use \Redis;

class Phpredis
{
    private static $conn;    // 单例redis连接
    private $redis_config;   // 配置config/redis.php

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    // 连接redis
    private function connect()
    {
        if (class_exists('Redis')) {
            $this->loadConfig();
            $redis = new Redis();
            $redis->connect($this->redis_config["host"], $this->redis_config["port"]);
            if (!empty($this->redis_config['password'])) {
                $redis->auth($this->redis_config["password"]);
            }
            // if (!empty($this->redis_config['select'])) {
            //     $redis->select($this->redis_config['select']);
            // }
            self::$conn = $redis;
        }
    }

    // 获取已存在的redis连接
    static public function getConn()
    {
        if (!self::$conn instanceof Redis) {
            $phpredis = new self();
            $phpredis->connect();
        }
        return self::$conn;
    }

    // 载入配置
    private function loadConfig()
    {
        $this->redis_config = Config::get('cache.redis');
    }
}
