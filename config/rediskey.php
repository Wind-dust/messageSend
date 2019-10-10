<?php
return [
    'cart'         => [
        'redisCartUserKey' => 'index:cart:user:', //用户购物车信息
    ],
    'order'        => [
        'redisOrderBonus'          => 'index:order:bonus:list:', //计算分利的订单队列的key(普通商品)
        'redisMemberOrder'         => 'index:member:order:list:', //购买会员成功支付的订单队列
        'redisMemberShare'         => 'index:member:share:list:', //购买会员成功支付的上级分享者获利提示队列
        'redisDeliverOrderExpress' => 'cms:order:deliver:express:', //后台cms发货物流查询
        'redisDeliverExpressList'  => 'cms:order:deliver:list:', //后台cms发货物流单号及物流公司编码队列
    ],
    'user'         => [
        'redisKey'                => 'index:user:',
        'redisConIdTime'          => 'index:user:conId:expiration', //conId到期时间的zadd
        'redisConIdUid'           => 'index:user:conId:uid', //conId和uid的hSet
        'redisUserNextLevel'      => 'index:user:nextLevel:uid:', //用户关系下的所有关系网uid列表
        'redisUserNextLevelCount' => 'index:user:nextLevelCount:uid:', //boss用户关系下的总人数
        'redisCmsConIdTime'       => 'cms:user:cmsConId:expiration', //后台cmsConId到期时间的zadd
        'redisCmsConIdUid'        => 'cms:user:cmsConId:adminid', //后台cmsConId和adminid的hSet
        'redisUserOpenbossLock'   => 'index:user:openboss:lock:', //开通boss锁
        'redisSupConIdTime'       => 'sup:user:supConId:expiration', //供应商后台supConId到期时间的zadd
        'redisSupConIdUid'        => 'sup:user:supConId:adminid', //供应商后台supConId和supadminid的hSet
    ],
    'index'        => [
        'redisIndexShow'   => 'index:index:show',
        'redisGoodsDetail' => 'index:goods:goodsDetail:', //商品详情
    ],
    'label'        => [
        'redisLabelTransform'   => 'label:labelLibrary:transform', //标签库生成拼音标签后的对应关系
        'redisLabelLibrary'     => 'label:labelLibrary:list', //标签库缓存
        'redisLabelLibraryHeat' => 'label:labelLibrary:heat', //标签热度排序
    ],
    'manage'       => [
        'redisManageInvoice' => 'cms:manage:invoice', //后台CMS提现比率key
    ],
    'weixin'       => [
        'redisAccessToken' => 'weixin:accesstoken', //微信access_token
        'redisAccessTokenTencent' => 'weixin:accesstoken:tencent', //微信公众号access_token
        'redisTicketTencent' => 'weixin:ticket:tencent', //微信公众号ticket
        'redisBatchgetMaterial' => 'weixin:batchget:material', //微信公众号文章
    ],
    'modelmessage' => [
        'redisMarketingActivity' => 'cms:modelmessage:marketingactivity:list', //营销活动
        'redisTimedTask'         => 'cms:modelmessage:timedtask:list', //定时任务
    ],
    'active'=>[//活动
        'redisHdluckyDraw'=>'index:offlineActivities:luckyDraw',
    ]
];