<?php
return [
    'order'   => [
        'redisOrderBonus'          => 'index:order:bonus:list:', //计算分利的订单队列的key(普通商品)
        'redisMemberOrder'         => 'index:member:order:list:', //购买会员成功支付的订单队列
        'redisMemberShare'         => 'index:member:share:list:', //购买会员成功支付的上级分享者获利提示队列
        'redisDeliverOrderExpress' => 'cms:order:deliver:express:', //后台cms发货物流查询
        'redisDeliverExpressList'  => 'cms:order:deliver:list:', //后台cms发货物流单号及物流公司编码队列
    ],
    'user'    => [
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
    'message' => [
        'redisMessageCodeSend'       => 'index:meassage:code:send', //验证码发送队列
        'redisMessageCodeSequenceId' => 'index:meassage:code:sequence:id', //行业通知SequenceId
        'redisMessageCodeMsgId'      => 'index:meassage:code:msg:id', //行业通知MsgId
        'redisMessageCodeDeliver'      => 'index:meassage:code:deliver', //行业通知MsgId
        'redisMessageMarketingSend'  => 'index:meassage:marketing:send', //营销发送队列
    ],

];