<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------
$commandPath = \think\facade\Config::get('console.command_path');
return [
    'curl'                  => $commandPath . 'Curl',
    'user'                  => $commandPath . 'User',
    'areas'                 => $commandPath . 'Areas',
    'order'                 => $commandPath . 'Order',
    'temporaryscript'       => $commandPath . 'Temporaryscript',
    'localscript'           => $commandPath . 'LocalScript',
    'modelmessage'          => $commandPath . 'ModelMessage',
    'wechatgraphicmaterial' => $commandPath . 'WeChatGraphicMaterial',
];
