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
    'curl'                             => $commandPath . 'Curl',
    'user'                             => $commandPath . 'User',
    'areas'                            => $commandPath . 'Areas',
    'cmpp'                             => $commandPath . 'Cmpp',
    'cmppsubmit'                       => $commandPath . 'Cmppsubmit',
    'localscript'                      => $commandPath . 'LocalScript',
    'serversocket'                     => $commandPath . 'ServerSocket',
    'clientsocket'                     => $commandPath . 'ClientSocket',
    'officeexcel'                      => $commandPath . 'OfficeExcel',
    'cmpptest'                         => $commandPath . 'CmppTest',
    'cmppqingniankeji'                 => $commandPath . 'CmppQingNianKeJi',
    'clientsocketsantibusiness'        => $commandPath . 'ClientSocketSantiBusiness',
    'clientsocketjiujiaXintong'        => $commandPath . 'ClientSocketJiuJiaXinTong',
    'cmppsantimarketing'               => $commandPath . 'CmppSantiMarketing',
    'cmppsantimarketing2'              => $commandPath . 'CmppSantiMarketing2',
    'cmpphainanshixinyidong'              => $commandPath . 'CmppHaiNanShiXinYiDong',
    'cmpptestlocal'                    => $commandPath . 'CmppTestLocal',
    'cmppcreatecodetask'               => $commandPath . 'CmppCreateCodeTask',
    'clientSocketwangdai'              => $commandPath . 'ClientSocketWangDai',
    'httpchannelsix'                   => $commandPath . 'HttpChannelSix',
    'httpchannelkemengtushu'           => $commandPath . 'HttpChannelKeMengTuShu',
    'httpchannelkemengzhuangxiu'       => $commandPath . 'HttpChannelKeMengZhuangXiu',
    'httpchannelcaixinhuaxingtongxun'  => $commandPath . 'HttpChannelCaiXinHuaxingtongxun',
    'httpchannelcaixinhangzhoumaiyuan' => $commandPath . 'HttpChannelCaiXinHangZhouMaiYuan',
    'cmppmijiadianxinmarketing'        => $commandPath . 'CmppMiJiaDianXinMarketing',
    'cmppmijialiantongmarketing'       => $commandPath . 'CmppMiJiaLianTongMarketing',
    'cmpplanjingmarketing'             => $commandPath . 'CmppLanJingMarketing',
    'serversocketshuhe'                => $commandPath . 'ServerSocketShuHe',
];
