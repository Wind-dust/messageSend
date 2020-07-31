<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 缓存设置
// +----------------------------------------------------------------------

//return [
//    // 驱动方式
//    'type'   => 'File',
//    // 缓存保存目录
//    'path'   => '',
//    // 缓存前缀
//    'prefix' => '',
//    // 缓存有效期 0表示永久缓存
//    'expire' => 0,
//];

return [
    // 选择模式
    'type' => 'complex',
    // 默认(文件缓存)
    'default' => [
        // 文件缓存
        'type' => 'File',
        // 缓存保存目录
        'path' => '',
        // 缓存前缀
        'prefix' => '',
        // 缓存有效期 0表示永久缓存
        'expire' => 0,
    ],

    // Redis缓存
    'redis' => [
        'type' => 'redis',
        'host' => Env::get('redis.hostname'),
        'port' => '6379',
        // 'password' => "as6d7g&h",
        'password' => Env::get('redisnew.password'),
        'timeout' => 3600,
        'select' => 0,
    ],
    'redisnew' => [
        'type' => 'redis',
        'host' => Env::get('redisnew.hostname'),
        'port' => '6379',
        'password' => Env::get('redisnew.password'),
        'timeout' => 3600,
        'select' => 0,
    ]

];
