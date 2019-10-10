<?php

namespace app\common\action\notify;

//use third\AliSms;
use third\Zthy;

/**
 * 短信发送
 * @package app\common\notify
 */
class Note {
    private $sign = '【776品质生活广场】';
    private $end = ',退订回T提交';//营销内容结尾

    public function sendSms($phone, $content) {
        $zt       = new Zthy(1);
        $data     = [
            'content' => $this->sign . $content,//短信内容
            'mobile'  => $phone,//手机号码
//            'xh'      => '111'//小号
        ];
        $zt->data = $data;
        $res      = $zt->sendSMS(1);
        return $res;
    }

    public function sendContent($phone, $content) {
        $zt       = new Zthy(2);
        $data     = [
            'content' => $this->sign . $content . $this->end,//短信内容
            'mobile'  => $phone,//手机号码
        ];
        $zt->data = $data;
        $res      = $zt->sendSMS(2);
        return $res;
    }


//    public function sendSms($phone, $code) {
//        $sendRes = AliSms::send($phone, $code, 4);
//        if ($sendRes) {
//            return ['code' => 200];
//        }
//        return ['code' => 3000];
//    }

//    public function getSms($phone, $date) {
//        $result = AliSms::querySendDetails($phone, $date);
//        $result = $result->SmsSendDetailDTOs->SmsSendDetailDTO;
//        return ['code' => 200, 'data' => $result];
//    }
}