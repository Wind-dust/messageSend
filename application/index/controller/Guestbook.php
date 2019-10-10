<?php
namespace app\index\controller;
use app\index\MyController;

class Guestbook extends MyController {

    /**
     * @api              {post} / 用户留言
     * @apiDescription   addGuestbook
     * @apiGroup         index_Guestbook
     * @apiName          addGuestbook
     * @apiParam (入参) {String} name 姓名
     * @apiParam (入参) {String} unit 单位
     * @apiParam (入参) {String} mobile 手机
     * @apiParam (入参) {String} phone 座机
     * @apiParam (入参) {String} qq QQ
     * @apiParam (入参) {String} type 产品线:1,短信验证码；2，行业手机彩信，3，语言验证，4行业营销短信，5企业流量 6国际业务
     * @apiParam (入参) {String} email 邮箱
     * @apiParam (入参) {String} [message] 留言
     * @apiSuccess (返回) {String} code 200:成功 / 3001:手机号码错误 / 3000:未获取到数据 / 3002.type参数错误 / 3003.qq格式错误 / 3004:邮箱校验错误 / 3005:名称为空或者长度超出30个字符 / 3006:单位为空或者长度超出50个字符
     * @apiSampleRequest /index/guestbook/addGuestbook
     * @author rzc
     */
    public function addGuestbook() {
        $apiName  = classBasename($this) . '/' . __function__;
        $name       = trim($this->request->post('name'));
        $unit    = trim($this->request->post('unit'));
        $mobile    = trim($this->request->post('mobile'));
        $phone    = trim($this->request->post('phone'));
        $qq    = trim($this->request->post('qq'));
        $type    = trim($this->request->post('type'));
        $email    = trim($this->request->post('email'));
        $message    = trim($this->request->post('message'));
        if (checkMobile($mobile) === false) {
            return ['code' => '3001'];
        }
        if (!is_numeric($qq) || strlen($qq) >10) {
            return ['code' => '3003'];
        }
        if (!in_array($type,[1,2,3,4,5,6])){
            return ['code' => '3002'];
        }
        if (checkEmail($email) === false){
            return ['code' => '3004'];
        }
        if (empty($name) || mb_strlen($name,'utf8') > 30){
            return ['code' => '3005'];
        }
        if (empty($unit) || mb_strlen($unit,'utf8') > 50){
            return ['code' => '3006'];
        }
        $data = [
            'name' => $name,
            'unit' => $unit,
            'mobile' => $mobile,
            'phone' => $phone,
            'qq' => $qq,
            'message' => $message,
            'type' => $type,
            'email' => $email,
        ];
        $result   = $this->app->guestbook->addGuestbook($data);
        // $this->apiLog($apiName, [$Guestbook_id, $source], $result['code'], '');
        return $result;
    }

}
