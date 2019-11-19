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
    'user'              => app\common\action\index\User::class,
    'provinces'         => app\common\action\admin\Provinces::class,
    'send'              => app\common\action\index\Send::class,
    'indexLog'          => app\common\action\index\IndexLog::class,
    'upload'            => app\common\action\index\Upload::class,
    // 'solution'        => app\common\action\index\Solution::class,
    // 'product'         => app\common\action\index\Product::class,
    // 'applicationcase' => app\common\action\index\ApplicationCase::class,
    // 'downloadcenter'  => app\common\action\index\DownloadCenter::class,
    // 'aboutus'         => app\common\action\index\Aboutus::class,
    // 'guestbook'         => app\common\action\index\Guestbook::class,
];
