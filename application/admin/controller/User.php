<?php

namespace app\admin\controller;

use app\admin\AdminController;
use think\Controller;

class User extends AdminController {
    protected $beforeActionList = [
        'isLogin', //所有方法的前置操作
        //        'isLogin' => ['except' => 'login'],//除去login其他方法都进行isLogin前置操作
        //        'three'   => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 获取会员列表
     * @apiDescription   getUsers
     * @apiGroup         admin_Users
     * @apiName          getUsers
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} [mobile] 手机号
     * @apiParam (入参) {Number} page 页码
     * @apiParam (入参) {Number} pagenum 查询条数
     * @apiSuccess (返回) {String} code 200:成功 / 3000:用户列表空 / 3001:手机号格式错误 / 3002:页码和查询条数只能是数字
     * @apiSuccess (返回) {String} totle 总结果条数
     * @apiSuccess (data) {object_array} data 结果
     * @apiSuccess (data) {String} id 用户ID
     * @apiSuccess (data) {String} user_type 用户类型1.普通账户2.总店账户
     * @apiSuccess (data) {String} user_identity 用户身份1.普通,2.钻石会员3.创业店主4.boss合伙人
     * @apiSuccess (data) {String} sex 用户性别 1.男 2.女 3.未确认
     * @apiSuccess (data) {String} nick_name 微信昵称
     * @apiSuccess (data) {String} true_name 真实姓名
     * @apiSuccess (data) {String} brithday 生日
     * @apiSuccess (data) {String} avatar 微信头像
     * @apiSuccess (data) {String} mobile 手机号
     * @apiSuccess (data) {String} email email
     * @apiSampleRequest /admin/User/getUsers
     * @apiParamExample (data) {Array} 返回用户列表
     * [
     * "code":"200",返回code码
     * "totle":"82",总记录条数
     *  {"id":9,"tel":15502123212,
     *   "name":"喜蓝葡萄酒",
     *   "status":"1",
     *   "image":"","title":"",
     *   "desc":"江浙沪皖任意2瓶包邮，其他地区参考实际支付运费"
     *  },
     * ]
     * @author rzc
     */
    public function getUsers() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        $page     = trim($this->request->post('page'));
        $pagenum  = trim($this->request->post('pagenum'));
        $mobile   = trim($this->request->post('mobile'));
        if (!empty($mobile)) {
            if (checkMobile($mobile) == false) {
                return ['code' => '3001'];
            }
        }
        $result = $this->app->user->getUsers($page, $pagenum, $mobile);
        $this->apiLog($apiName, [$cmsConId, $page, $pagenum], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 设置用户信息
     * @apiDescription   seetingUser
     * @apiGroup         admin_Users
     * @apiName          seetingUser
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} uid 账户id
     * @apiParam (入参) {Int} [user_status] 账户服务状态 1停止服务 2启用服务
     * @apiParam (入参) {Int} [reservation_service] 可否预用服务 1不可 2可以
     * @apiSuccess (返回) {String} code 200:成功 / 3000:用户列表空 / 3001:user_status格式错误 / 3002:reservation_service格式错误 / 3003:uid格式错误
     * @apiSampleRequest /admin/user/seetingUser
     * @author rzc
     */
    public function seetingUser() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $uid                 = trim($this->request->post('uid'));
        $user_status         = trim($this->request->post('user_status'));
        $reservation_service = trim($this->request->post('reservation_service'));
        if (!in_array($user_status, [1, 2]) && !empty($user_status)) {
            return ['code' => '3001'];
        }
        if (empty($uid) || intval($uid) < 1 || !is_numeric($uid)) {
            return ['code' => '3003'];
        }
        if (!in_array($reservation_service, [1, 2]) && !empty($reservation_service)) {
            return ['code' => '3002'];
        }
        $result = $this->app->user->seetingUser($uid, $user_status, $reservation_service);
        $this->apiLog($apiName, [$cmsConId, $uid, $user_status, $reservation_service], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 设置用户服务项目
     * @apiDescription   seetingUserEquities
     * @apiGroup         admin_Users
     * @apiName          seetingUserEquities
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} uid 账户id
     * @apiParam (入参) {Int} business_id 服务id
     * @apiParam (入参) {Int} [agency_price] 代理价格，默认统一服务价格
     * @apiSuccess (返回) {String} code 200:成功 / 3001:business_id格式错误或者不存在 / 3002:agency_price格式错误 / 3003:uid格式错误 / 3004:代理价格不能低于统一服务价 / 3005:该服务已添加 / 3006:子账户服务无法设置
     * @apiSampleRequest /admin/user/seetingUserEquities
     * @author rzc
     */
    public function seetingUserEquities() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $uid          = trim($this->request->post('uid'));
        $business_id  = trim($this->request->post('business_id'));
        $agency_price = trim($this->request->post('agency_price'));
        if (!empty($agency_price) && !is_numeric($agency_price)) {
            return ['code' => '3002'];
        }
        $agency_price = floatval($agency_price);
        if (empty($uid) || intval($uid) < 1 || !is_numeric($uid)) {
            return ['code' => '3003'];
        }
        if (empty($business_id) || intval($business_id) < 1 || !is_numeric($business_id)) {
            return ['code' => '3001'];
        }
        $result = $this->app->user->seetingUserEquities($uid, $business_id, $agency_price);
        $this->apiLog($apiName, [$cmsConId, $uid, $business_id, $agency_price], $result['code'], $cmsConId);
        return $result;
    }
}
