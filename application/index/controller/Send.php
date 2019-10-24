<?php
namespace app\index\controller;
use app\index\MyController;

class Send extends MyController {
    protected $beforeActionList = [
        //        'isLogin',//所有方法的前置操作
        'isLogin' => ['except' => 'cmppSendTest'], //除去getFirstCate其他方法都进行second前置操作
        //        'three'  => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 发送短信测试
     * @apiDescription   cmppSendTest
     * @apiGroup         index_send
     * @apiName          cmppSendTest
     * @apiParam (入参) {Number} phone 手机号
     * @apiParam (入参) {Number} code 验证码
     * @apiParam (入参) {String} vercode 验证码
     * @apiSuccess (返回) {String} code 200:成功 / 3000:手机号格式错误 / 3002:passwd密码强度不够 / 3003:邮箱格式错误 / 3004:验证码错误 / 3005:该手机号已注册用户 / 3006:用户类型错误 / 3007:nick_name不能为空
     * @apiSampleRequest /index/send/cmppSendTest
     * @author rzc
     */
    public function cmppSendTest() {
        $apiName   = classBasename($this) . '/' . __function__;
        $phone = trim($this->request->post('phone'));//手机号
        if (!checkMobile($phone)) {
            return ['code' => 3001];
        }
        $code   = trim($this->request->post('code'));//验证码
        $result = $this->app->send->cmppSendTest($phone, $code);
        // $this->apiLog($apiName, [$Banner_id, $source], $result['code'], '');
        return $result;
    }

 
}
