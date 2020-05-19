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
    'cmpphainanshixinyidongbusiness'           => $commandPath . 'CmppHaiNanShiXinYiDongBusiness', //海南始新移动行业
    'cmpphainanshixinliantongbusiness'           => $commandPath . 'CmppHaiNanShiXinLianTongBusiness', //海南始新移动行业
    'cmpphainanshixindianxinbusiness'           => $commandPath . 'CmppHaiNanShiXinDianXinBusiness', //海南始新移动行业
    'cmpphainanshixinyidong'           => $commandPath . 'CmppHaiNanShiXinYiDong', //海南始新移动游戏挂机
    'cmpphainanshixinliantonggame'     => $commandPath . 'CmppHaiNanShiXinLianTongGame', //海南始新联通游戏挂机
    'cmpphainanshixindianxingame'      => $commandPath . 'CmppHaiNanShiXinDianXinGame', //海南始新电信游戏挂机
    'cmppjiangxijumengyidonggame'      => $commandPath . 'CmppJiangXIJuMengYiDongGame', //江西聚梦游戏挂机
    'cmpphainanshiXinyidongmarketing'  => $commandPath . 'CmppHaiNanShiXinYiDongMarketing', //海南始新移动电信营销通道
    'cmpphainanshiXinliantongmarketing'  => $commandPath . 'CmppHaiNanShiXinLianTongMarketing', //海南始新联通营销通道
    'cmpptestlocal'                    => $commandPath . 'CmppTestLocal',
    'cmppcreatecodetask'               => $commandPath . 'CmppCreateCodeTask',
    'clientSocketwangdai'              => $commandPath . 'ClientSocketWangDai',
    'httpchannelsix'                   => $commandPath . 'HttpChannelSix',
    'httpchannelKeMengtushu'           => $commandPath . 'HttpChannelKeMengTuShu',
    'httpchannelkemengzhuangxiu'       => $commandPath . 'HttpChannelKeMengZhuangXiu',
    'httpchannelcaixinhuaxingtongxun'  => $commandPath . 'HttpChannelCaiXinHuaxingtongxun',
    'httpchannelcaixinhangzhoumaiyuan' => $commandPath . 'HttpChannelCaiXinHangZhouMaiYuan',
    'httpchannelcaixinchuanglan' => $commandPath . 'HttpChannelCaiXinChuangLan',
    'httpchannelcaixinmeilian' => $commandPath .'HttpChannelCaiXinMeiLian', //美联软通彩信
    'cmppmeilianruantongyidonggame' => $commandPath .'CmppMeiLianRuanTongYiDongGame', //美联软通移动游戏
    'httpchannelcaixinbangzhixinyidong' => $commandPath .'HttpChannelCaiXinBangZhiXinYiDong', //邦之信移动彩信
    'httpchannelcaixinbangzhixinliandian' => $commandPath .'HttpChannelCaiXinBangZhiXinLianDian', //邦之信移动彩信
    'cmppmijiadianxinmarketing'        => $commandPath . 'CmppMiJiaDianXinMarketing',
    'cmppmijialiantongmarketing'       => $commandPath . 'CmppMiJiaLianTongMarketing',
    'cmppmijiayidongmarketing'         => $commandPath . 'CmppMiJiaYiDongMarketing',
    'cmppmijialiandianbusiness'        => $commandPath . 'CmppMiJiaLianDianBusiness',
    'cmppmijiayidongbusiness'          => $commandPath . 'CmppMiJiaYiDongBusiness',
    'cmppmijialiantongdianxinmarketing'        => $commandPath . 'CmppMiJiaLianTongDianXinMarketing', //米加联通电信营销
    'cmppjumengyidongmarketing'        => $commandPath . 'CmppJuMengYiDongMarketing',
    'cmppjumengliandianmarketing'      => $commandPath . 'CmppJuMengLianDianMarketing',
    'cmppjumengbusiness'      => $commandPath . 'CmppJuMengBusiness',
    'cmppyixinyidongbusiness'          => $commandPath . 'CmppYiXinYiDongBusiness', //易信移动行业通道
    'cmppyixindianxinbusiness'         => $commandPath . 'CmppYiXinDianxinBusiness', //易信移动行业通道
    'cmppyixinyidongmarketing'         => $commandPath . 'CmppYiXinYiDongMarketing', //易信移动营销通道
    'cmppyixinliantongdianxinmarketing'         => $commandPath . 'CmppYiXinLianTongDianXinMarketing', //易信电信联通营销通道
    'cmpplanjingmarketing'             => $commandPath . 'CmppLanJingMarketing',
    'cmpplanjingbusiness'              => $commandPath . 'CmppLanJingBusiness',
    'cmpprongheyidongbusiness'              => $commandPath . 'CmppRongHeYiDongBusiness', //融合移动行业
    'cmpprongheyidongmarketingreport'              => $commandPath . 'CmppRongHeYiDongMarketingReport', //融合移动营销报备
    'cmpprongheyidongmarketing'              => $commandPath . 'CmppRongHeYiDongMarketing', //融合移动营销
    'cmpprongheliantongbusiness'              => $commandPath . 'CmppRongHeLianTongBusiness', //融合联通行业
    'cmpprongheliantongmarketing'              => $commandPath . 'CmppRongHeLianTongMarketing', //融合联通营销
    'cmppronghedianXinBusiness'              => $commandPath . 'CmppRongHeDianXinBusiness', //融合电信行业
    'cmppronghedianXinmarketing'              => $commandPath . 'CmppRongHeDianXinMarketing', //融合电信营销
    'cmppbeijingbamimarketing'              => $commandPath . 'CmppBeiJingBaMiMarketing', //北京八米
    'cmppbeijingbamiusermarketing'              => $commandPath . 'CmppBeiJingBaMiUserMarketing', //北京八米会员营销
    'cmpplvchengyidongbusiness'          => $commandPath . 'CmppLvChengYiDongBusiness', //绿城移动行业通道
    'cmpplvchengdianxinbusiness'         => $commandPath . 'CmppLvChengDianxinBusiness', //绿城电信行业通道
    'cmpplvchengliantongbusiness'         => $commandPath . 'CmppLvChengLianTongBusiness', //绿城联通行业通道
    'cmpplvchengyidongmarketing'         => $commandPath . 'CmppLvChengYiDongMarketing', //绿城联通行业通道
    'cmpplvchengliantongmarketing'         => $commandPath . 'CmppLvChengLianTongMarketing', //绿城联通行业通道
    'cmpplvchengdianxinmarketing'         => $commandPath . 'CmppLvChengDianXinMarketing', //绿城联通行业通道
    'cmppsfljumengyidongmarketing'         => $commandPath . 'CmppSflJuMengYiDongMarketing', //丝芙兰聚梦
    'cmppsfljumengliandianmarketing'         => $commandPath . 'CmppSflJuMengLianDianMarketing', //丝芙兰聚梦
    'cmppsflrongheyidongmarketing'         => $commandPath . 'CmppSflRongHeYiDongMarketing', //丝芙兰融合
    'cmppsflronghedianxinmarketing'         => $commandPath . 'CmppSflRongHeDianXinMarketing', //丝芙兰融合
    'cmppsflrongheliantongmarketing'         => $commandPath . 'CmppSflRongHeLianTongMarketing', //丝芙兰融合
    'cmppbeijingmiaoxinbusiness'         => $commandPath . 'CmppBeiJingMiaoXinBusiness', //北京秒信行业
    'cmppbeijingmiaoxinmarketing'         => $commandPath . 'CmppBeiJingMiaoXinMarketing', //北京秒信会员营销
    'cmppbishangyidongbusiness'         => $commandPath . 'CmppBiShangYiDongBusiness',//比上移动行业
    'serversocketshuhe'                => $commandPath . 'ServerSocketShuHe',
    'ServerSocketjyy'                  => $commandPath . 'ServerSocketJYY',
    'ServerSocketjyylt'                  => $commandPath . 'ServerSocketJYYLT',
    'ServerSocketjyydx'                  => $commandPath . 'ServerSocketJYYDX',
    'serversocket'                  => $commandPath . 'ServerSocket',
    'serversocketjgz'   => $commandPath . 'ServerSocketJGZ',
    'sflupload'   => $commandPath . 'SflUpload',
];
