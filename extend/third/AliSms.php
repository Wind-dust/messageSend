<?php

namespace third;

use Aliyun\Core\Config as AliyunConfig;
use Aliyun\Core\Profile\DefaultProfile;
use Aliyun\Core\DefaultAcsClient;
use Aliyun\Api\Sms\Request\V20170525\SendSmsRequest;
use Aliyun\Api\Sms\Request\V20170525\QuerySendDetailsRequest;
use Config;

class AliSms {
    // 阿里云Access Key ID和Access Key Secret 从 https://ak-console.aliyun.com 获取
    static $appKey;
    static $appSecret;

    // 短信签名 详见：https://dysms.console.aliyun.com/dysms.htm?spm=5176.2020520001.1001.3.psXEEJ#/sign
    static $signName;

    // 短信模板Code https://dysms.console.aliyun.com/dysms.htm?spm=5176.2020520001.1001.3.psXEEJ#/template
    static $templateCode;

    static $region;

    // 服务结点
    static $endPointName;

    // 短信中的替换变量json字符串
    static $json_string_param = '';

    //产品名称:云通信流量服务API产品,开发者无需替换
    static $product = "Dysmsapi";

    //产品域名,开发者无需替换
    static $domain = "dysmsapi.aliyuncs.com";

    static $acsClient;

    private function __clone() {
    }

    private function __construct() {
    }

    public static function conn() {
        // 初始化阿里云config
        AliyunConfig::load();
        if (isset(self::$acsClient)) {
            return;
        }
        self::loadConfig();
        // 初始化用户Profile实例
        $profile = DefaultProfile::getProfile(self::$region, self::$appKey, self::$appSecret);
        DefaultProfile::addEndpoint(self::$endPointName, self::$region, self::$product, self::$domain);
        $acsClient       = new DefaultAcsClient($profile);
        self::$acsClient = $acsClient;
    }

    /**
     * 发送一条短信
     * @param $phone
     * @param $data
     * @param int $tempIndex 短信文本模版
     * @return bool
     */
    public static function send($phone, $data, $tempIndex = 1) {
        self::conn();
        // 初始化SendSmsRequest实例用于设置发送短信的参数
        $request = new SendSmsRequest();
        // 必填，设置短信接收号码
        $request->setPhoneNumbers($phone);
        // 必填，设置签名名称
        $request->setSignName(self::$signName);
        // 必填，设置模板CODE
        $request->setTemplateCode(self::$templateCode[$tempIndex]);
        // 可选，设置模板参数
//        if (!empty(self::$json_string_param)) {
        $request->setTemplateParam(json_encode(
            ['code' => $data]
        ));
//        }

        // 可选，设置流水号
        // if($outId) {
        //     $request->setOutId($outId);
        // }
        // 发起请求
        $acsResponse = self::$acsClient->getAcsResponse($request);
        // 默认返回stdClass，通过返回值的Code属性来判断发送成功与否
        if ($acsResponse && strtolower($acsResponse->Code) == 'ok') {
            return true;
        }
        return false;
    }

    /**
     * 短信发送记录查询
     * @param $phone
     * @param $date
     * @param int $page
     * @param int $pagesize
     * @return mixed
     */
    public static function querySendDetails($phone, $date, $page = 1, $pagesize = 10) {
        self::conn();
        // 初始化QuerySendDetailsRequest实例用于设置短信查询的参数
        $request = new QuerySendDetailsRequest();
        // 必填，短信接收号码
        $request->setPhoneNumber($phone);
        // 必填，短信发送日期，格式Ymd，支持近30天记录查询
        $request->setSendDate($date);
        // 必填，分页大小
        $request->setPageSize($pagesize);
        // 必填，当前页码
        $request->setCurrentPage($page);
        // 选填，短信发送流水号
        //$request->setBizId("yourBizId");
        // 发起访问请求
        $acsResponse = self::$acsClient->getAcsResponse($request);
        return $acsResponse;
    }

    public static function loadConfig() {
        self::$appKey       = Config::get('sms.accessKey');
        self::$appSecret    = Config::get('sms.accessKeySecret');
        self::$templateCode = Config::get('sms.templateCode');
        self::$signName     = Config::get('sms.signName');
        self::$region       = Config::get('sms.region');
        self::$endPointName = Config::get('sms.endPointName');
    }
}