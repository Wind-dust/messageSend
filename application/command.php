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
    'curl' => $commandPath . 'Curl',
    'cmppnorm' => $commandPath . 'CmppNorm',
    'user' => $commandPath . 'User',
    'areas' => $commandPath . 'Areas',
    'cmpp' => $commandPath . 'Cmpp',
    'cmppsubmit' => $commandPath . 'Cmppsubmit',
    'localscript' => $commandPath . 'LocalScript',
    'kafka' => $commandPath . 'Kafka',
    'serversocket' => $commandPath . 'ServerSocket',
    'clientsocket' => $commandPath . 'ClientSocket',
    'officeexcel' => $commandPath . 'OfficeExcel',
    'cmpptest' => $commandPath . 'CmppTest',
    'cmppcreatecodetask' => $commandPath . 'CmppCreateCodeTask',
    'httpchannelsix' => $commandPath . 'HttpChannelSix',
    'httpchannelKeMengtushu' => $commandPath . 'HttpChannelKeMengTuShu',
    'httpchannelkemengzhuangxiu' => $commandPath . 'HttpChannelKeMengZhuangXiu',
    'httpchannelcaixinshanghailianlu' => $commandPath . 'HttpChannelCaiXinShangHaiLianLu',
    'httpchannelcaixinzhonglan' => $commandPath . 'HttpChannelCaiXinZhongLan',
    'httpchannelmodelcaixinzhonglan' => $commandPath . 'HttpChannelModelCaiXinZhongLan',
    'httpchannelmodelvarcaixinzhonglan' => $commandPath . 'HttpChannelModelVarCaiXinZhongLan',
    'httpchannelcaixinhuaxingtongxun' => $commandPath . 'HttpChannelCaiXinHuaxingtongxun',
    'httpchannelcaixinhangzhoumaiyuan' => $commandPath . 'HttpChannelCaiXinHangZhouMaiYuan',
    'httpchannelcaixinchuanglan' => $commandPath . 'HttpChannelCaiXinChuangLan',
    'httpchannelmodelsupmessageaiqi' => $commandPath . 'HttpChannelModelSupMessageAiQi',
    'httpchannelmodelsupmessagekuailewangshiyidong' => $commandPath . 'HttpChannelModelSupMessageKuaiLeWangShiYiDong',
    'httpchannelmodelsupmessagekuailewangshiliantong' => $commandPath . 'HttpChannelModelSupMessageKuaiLeWangShiLianTong',
    'httpchannelmodelsupmessagekuailewangshidianxin' => $commandPath . 'HttpChannelModelSupMessageKuaiLeWangShiDianXin',
    'httpchannelmodelsupmessagechuanglan' => $commandPath . 'HttpChannelModelSupMessageChuangLan',
    'httpchannelmodelsupmessagechuangshiliandian' => $commandPath . 'HttpChannelModelSupMessageChuangShiLianDian',
    'httpchannelmodelsupmessagejuheyun' => $commandPath . 'HttpChannelModelSupMessageJuHeYun',
    'httpchannelcaixinchuanglanlankouone' => $commandPath . 'HttpChannelCaiXinChuangLanLanKouOne',
    'httpchannelcaixinchuanglanlankoutwo' => $commandPath . 'HttpChannelCaiXinChuangLanLanKouTwo',
    'httpchannelcaixinchuanglanlankouthree' => $commandPath . 'HttpChannelCaiXinChuangLanLanKouThree',
    'httpchannelmodelcaixinchuanglan' => $commandPath . 'HttpChannelModelCaiXinChuangLan',
    'httpchannelcaixinmeilian' => $commandPath . 'HttpChannelCaiXinMeiLian', //美联软通彩信
    'httpchannelmodelcaixinweige' => $commandPath . 'HttpChannelModelCaiXinWeiGe',
    'httpchannelmodelsupmessagelingdao' => $commandPath . 'HttpChannelModelSupMessageLingDao', //领道视频短信移动通道
    'httpchannelmodelsupmessagelingdaoliandian' => $commandPath . 'HttpChannelModelSupMessageLingDaoLianDian', //领道视频短信联电通道
    'httpchannelmodelsupmessagesanti' => $commandPath . 'HttpChannelModelSupMessageSanTi',
    'httpchannelmodelsupmessagesantisfl' => $commandPath . 'HttpChannelModelSupMessageSanTiSFL',
    'httpchannelmodelsupmessageshilegao' => $commandPath . 'HttpChannelModelSupMessageShiLeGao',
    'httpchannelcaixinbangzhixinyidong' => $commandPath . 'HttpChannelCaiXinBangZhiXinYiDong', //邦之信移动彩信
    'httpchannelcaixinbangzhixinliandian' => $commandPath . 'HttpChannelCaiXinBangZhiXinLianDian', //邦之信移动彩信
    'httpchannelcaixinkuailewangshi' => $commandPath . 'HttpChannelCaiXinKuaiLeWangShi', //快乐网视移动彩信自定义通道
    'httpchannelcaixinkuailewangshimodelvar' => $commandPath . 'HttpChannelCaiXinKuaiLeWangShiModelVar', //快乐网视移动彩信模板变量
    'httpchannelcaixinkuailewangshiliandian' => $commandPath . 'HttpChannelCaiXinKuaiLeWangShiLianDian', //快乐网视联电彩信自定义通道
    'httpchannelcaixinkuailewangshimodelvarliandian' => $commandPath . 'HttpChannelCaiXinKuaiLeWangShiModelVarLianDian', //快乐网视联电彩信模板变量
    'httpchannelcaixinchuangshi' => $commandPath . 'HttpChannelCaiXinChuangShi', //创世华信信移动彩信
    'httpchannelcaixinchuangshimodelvar' => $commandPath . 'HttpChannelCaiXinChuangShiModelVar', //创世华信信移动彩信
    'httpchannelcaixinsftpchuanglan' => $commandPath . 'HttpChannelCaiXinSFTPChuangLan', //丝芙兰sftp创蓝彩信
    'httpchannelcaixinjumengliantong' => $commandPath . 'HttpChannelCaiXinJuMengLianTong', //聚梦联通彩信
    'serversocketshuhe' => $commandPath . 'ServerSocketShuHe',
    'ServerSocketjyy' => $commandPath . 'ServerSocketJYY',
    'ServerSocketjyylt' => $commandPath . 'ServerSocketJYYLT',
    'ServerSocketjyydx' => $commandPath . 'ServerSocketJYYDX',
    'serversocket' => $commandPath . 'ServerSocket',
    'serversocketjgz' => $commandPath . 'ServerSocketJGZ',
    'sflupload' => $commandPath . 'SflUpload',
    'apicenter' => $commandPath . 'ApiCenter',
];
