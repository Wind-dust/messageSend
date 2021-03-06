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

// 应用容器绑定定义
return [
    'administrator' => app\common\action\admin\Administrator::class,
    'admin'         => app\common\action\admin\Admin::class,
    'adminLog'      => app\common\action\admin\AdminLog::class,
    'user'          => app\common\action\admin\User::class,
    'provinces'     => app\common\action\admin\Provinces::class,
    'upload'        => app\common\action\admin\Upload::class,
    'message'       => app\common\action\admin\Message::class,
];
