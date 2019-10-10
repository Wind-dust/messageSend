<?php
return [
    'order_out_time' => 1800,//订单过期时间
    'platform_conf'  => ['production' => 1, 'development' => 2],
    'bonus_days'     => Env::get('conf.bonusDays', 15),//付款后多少天分利正式给到账户

    'env_protocol' => Env::get('host.envProtocol', 'http'),
    'api_host'     => Env::get('host.apiHost'),
    'pay_host'     => Env::get('host.payHost'),

    'weixin_miniprogram_appid'     => Env::get('weixin.weixin_miniprogram_appid'),
    'weixin_miniprogram_appsecret' => Env::get('weixin.weixin_miniprogram_appsecret'),

//    'weixin_appid' => 'wxeead1475c05cde84',
//    'weixin_appsecret' =>'e688545400add6d33a2ee7321a904999',

    /**
     * =======【支付基本信息设置】=====================================
     * TODO: 修改这里配置为您自己申请的商户信息
     * 微信公众号信息配置
     * APPID：绑定支付的APPID（必须配置，开户邮件中可查看）
     * MCHID：商户号（必须配置，开户邮件中可查看）
     * KEY：商户支付密钥，参考开户邮件设置（必须配置，登录商户平台自行设置）
     * 设置地址：https://pay.weixin.qq.com/index.php/account/api_cert
     * APPSECRET：公众帐号secert（仅JSAPI支付的时候需要配置， 登录公众平台，进入开发者中心可设置），
     * 获取地址：https://mp.weixin.qq.com/advanced/advanced?action=dev&t=advanced/dev&token=2005451881&lang=zh_CN
     */
//    'wx_pay_appid'                 => Env::get('wxpay.wxPayAppid'),
//    'wx_pay_appsecret'             => Env::get('wxpay.wxPayAppsecret'),
    'wx_pay_mchid'                 => Env::get('weixin.wxPayMchid'),
    'wx_pay_key'                   => Env::get('weixin.wxPayKey'),
    'report_levenl'                => Env::get('weixin.reportLevenl'),
    //=======【curl代理设置】===================================
    /**
     * TODO：这里设置代理机器，只有需要代理的时候才设置，不需要代理，请设置为0.0.0.0和0
     * 本例程通过curl使用HTTP POST方法，此处可修改代理服务器，
     * 默认CURL_PROXY_HOST=0.0.0.0和CURL_PROXY_PORT=0，此时不开启代理（如有需要才设置）
     * @var unknown_type
     */
    'curl_proxy_host'              => Env::get('weixin.curlProxyHost'),
    'curl_proxy_port'              => Env::get('weixin.curlProxyPort'),

    /**
     * 二维码写入地址
     * @var unknown_type
     */
    'image_path'              => Env::get('conf.imagePath'),

    /**
     * 提现比率
     * @var unknown_type
     */
    'has_invoice'             => Env::get('proportion.has_invoice'),
    'no_invoice'              => Env::get('proportion.no_invoice'),

    /**
     * 模板消息
     * @var unknown_type
     */
    'deliver_goods_template_id' => Env::get('modelmessage.deliver_goods_template_id'),
];