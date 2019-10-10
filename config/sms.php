<?php
/**
 * 阿里云信短信配置文件
 */
//return [
//    // 阿里云Access Key ID和Access Key Secret 从 https://ak-console.aliyun.com 获取
//    'accessKey'       => Env::get('aliyun.accessKey'),
//    'accessKeySecret' => Env::get('aliyun.accessKeySecret'),
//
//    // 短信模板Code https://dysms.console.aliyun.com/dysms.htm?spm=5176.2020520001.1001.3.psXEEJ#/template
//    'templateCode'    => [
//        1 => 'SMS_143055046',//短信验证码         案例:您的验证码${code}，该验证码5分钟内有效，请勿泄漏于他人！
//        2 => 'SMS_134845020',//身份验证验证码      案例:验证码${code}，您正在进行身份验证，打死不要告诉别人哦！
//        3 => 'SMS_134845019',//登录确认验证码      案例:验证码${code}，您正在登录，若非本人操作，请勿泄露。
//        4 => 'SMS_134845018',//登录异常验证码      案例:验证码${code}，您正尝试异地登录，若非本人操作，请勿泄露。
//        5 => 'SMS_134845017',//用户注册验证码      案例:验证码${code}，您正在注册成为新用户，感谢您的支持！
//        6 => 'SMS_134845016',//修改密码验证码      案例:验证码${code}，您正在尝试修改登录密码，请妥善保管账户信息。
//        7 => 'SMS_134845015',//信息变更验证码      案例:验证码${code}，您正在尝试变更重要信息，请妥善保管账户信息。
//    ],
//
//    // 短信签名 详见：https://dysms.console.aliyun.com/dysms.htm?spm=5176.2020520001.1001.3.psXEEJ#/sign
//    'signName'        => '品质生活广场',
//
//    // 暂时不支持多Region
//    'region'          => 'cn-hangzhou',
//
//    // 服务结点
//    'endPointName'    => 'cn-hangzhou',
//];

/**
 * 助通短信配置
 */
return [
    'usernameVerifi'=>Env::get('zthy.usernameVerifi'),
    'passwordVerifi'=>Env::get('zthy.passwordVerifi'),
    'usernameMarket'=>Env::get('zthy.usernameMarket'),
    'passwordMarket'=>Env::get('zthy.passwordMarket'),
];