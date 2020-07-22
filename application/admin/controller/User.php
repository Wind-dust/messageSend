<?php

namespace app\admin\controller;

use app\admin\AdminController;
use think\Controller;

class User extends AdminController
{
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
    public function getUsers()
    {
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
     * @apiParam (入参) {Int} [free_trial] 行业发送审核 1:需要审核;2:无需审核
     * @apiParam (入参) {Int} [marketing_free_trial] 营销发送审核 1:需要审核;2:无需审核
     * @apiParam (入参) {Int} [mul_free_trial] 彩信发送审核 1:需要审核;2:无需审核
     * @apiParam (入参) {Int} [need_upriver_api] 是否需要从接口调用上行1:不需要;2:需要
     * @apiParam (入参) {Int} [need_receipt_api] 是否需要从接口调用回执1:不需要;2:需要
     * @apiParam (入参) {Int} [need_receipt_info] 是否开放回执状态信息1:不需要;2:需要
     * @apiSuccess (返回) {String} code 200:成功 / 3000:用户列表空 / 3001:user_status格式错误 / 3002:reservation_service格式错误 / 3003:uid格式错误 / 3004:free_trial格式错误 / 3005:need_upriver_api格式错误 / 3006:need_receipt_api格式错误 / 3007:need_receipt_info格式错误 / 3008:marketing_free_trial格式错误  / 3009:mul_free_trial格式错误  / 
     * @apiSampleRequest /admin/user/seetingUser
     * @author rzc
     */
    public function seetingUser()
    {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $uid                 = trim($this->request->post('uid'));
        $user_status         = trim($this->request->post('user_status'));
        $reservation_service = trim($this->request->post('reservation_service'));
        $free_trial = trim($this->request->post('free_trial'));
        $marketing_free_trial = trim($this->request->post('marketing_free_trial'));
        $mul_free_trial = trim($this->request->post('mul_free_trial'));
        $need_upriver_api = trim($this->request->post('need_upriver_api'));
        $need_receipt_api = trim($this->request->post('need_receipt_api'));
        $need_receipt_info = trim($this->request->post('need_receipt_info'));
        if (!in_array($user_status, [1, 2]) && !empty($user_status)) {
            return ['code' => '3001'];
        }
        if (empty($uid) || intval($uid) < 1 || !is_numeric($uid)) {
            return ['code' => '3003'];
        }
        if (!in_array($reservation_service, [1, 2]) && !empty($reservation_service)) {
            return ['code' => '3002'];
        }
        if (!in_array($free_trial, [1, 2]) && !empty($free_trial)) {
            return ['code' => '3004'];
        }
        if (!in_array($need_upriver_api, [1, 2]) && !empty($need_upriver_api)) {
            return ['code' => '3005'];
        }
        if (!in_array($need_receipt_api, [1, 2]) && !empty($need_receipt_api)) {
            return ['code' => '3006'];
        }
        if (!in_array($need_receipt_info, [1, 2]) && !empty($need_receipt_info)) {
            return ['code' => '3007'];
        }
        if (!in_array($marketing_free_trial, [1, 2]) && !empty($marketing_free_trial)) {
            return ['code' => '3008'];
        }
        if (!in_array($mul_free_trial, [1, 2]) && !empty($mul_free_trial)) {
            return ['code' => '3009'];
        }
        $result = $this->app->user->seetingUser($uid, $user_status, $reservation_service, $free_trial, $need_receipt_api, $need_upriver_api, $need_receipt_info, $marketing_free_trial, $mul_free_trial);
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
    public function seetingUserEquities()
    {
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

    /**
     * @api              {post} / 获取会员详情
     * @apiDescription   getUserInfo
     * @apiGroup         admin_Users
     * @apiName          getUserInfo
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} uid 账户id
     * @apiSuccess (返回) {String} code 200:成功 / 3001:用户不存在 / 3002:agency_price格式错误 / 3003:uid格式错误 / 3004:代理价格不能低于统一服务价 / 3005:该服务已添加 / 3006:子账户服务无法设置
     * @apiSampleRequest /admin/user/getUserInfo
     * @author rzc
     */
    public function getUserInfo()
    {
        $cmsConId = trim($this->request->post('cms_con_id'));
        $uid          = trim($this->request->post('uid'));
        if (empty($uid) || intval($uid) < 1 || !is_numeric($uid)) {
            return ['code' => '3001'];
        }
        $result = $this->app->user->getUserInfo($uid);
        return $result;
    }
        /**
         * @api              {post} / 设置丝芙兰报表显示数据
         * @apiDescription   setSflReportLog
         * @apiGroup         admin_Users
         * @apiName          setSflReportLog
         * @apiParam (入参) {String} cms_con_id
         * @apiParam (入参) {String} ym 当前这条报表所属年月
         * @apiParam (入参) {Int} total 总号码数
         * @apiParam (入参) {Int} jf 总计费数
         * @apiParam (入参) {Int} success 成功计费数
         * @apiParam (入参) {Int} fail 失败计费数
         * @apiParam (入参) {Int} unknown 未知数
         * @apiParam (入参) {Int} rate 成功率
         * @apiSuccess (返回) {String} code 200:成功 / 3001:参数错误 /3002:保存失败
         * @apiSampleRequest /admin/user/setSflReportLog
         * @author jackiwu
         */
    public function setSflReportLog()
    {
        $cmsConId = trim($this->request->post('cms_con_id'));
        $total = intval(trim($this->request->post('total')));
        $jf = intval(trim($this->request->post('jf')));
        $success = intval(trim($this->request->post('success')));
        $fail = intval(trim($this->request->post('fail')));
        $unknown = intval(trim($this->request->post('unknown')));
        $rate = trim($this->request->post('rate'));
        $ym = trim($this->request->post('ym'));
        $type = intval(trim($this->request->post('type')));
        if(!$ym){
            return ['code'=> 3001,'msg'=>'年月必填'];
        }
        if(empty($cmsConId) || !$total || !$jf || !$success || empty($rate) || !$type){
            return ['code' => 3001,'msg' => '不可为空'];
        }
        $result = $this->app->user->setSflReportLog($total,$jf,$success,$fail,$unknown,$rate,$ym,$type);
        return $result;
    }
        /**
         * @api              {post} / 获取丝芙兰报表显示数据
         * @apiDescription   getSflReportLog
         * @apiGroup         admin_Users
         * @apiName          getSflReportLog
         * @apiParam (入参) {String} cms_con_id
         * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 
         * @apiSampleRequest /admin/user/getSflReportLog
         * @author jackiwu
         */
    public function getSflReportLog(){
        $cmsConId = trim($this->request->post('cms_con_id'));
        $result = $this->app->user->getSflReportLog();
        return $result;
    }
        /**
         *
         * @api              {post} / 修改丝芙兰报表显示数据
         * @apiDescription   editSflReportLog
         * @apiGroup         admin_Users
         * @apiName          editSflReportLog
         * @apiParam (入参) {String} cms_con_id
         * @apiParam (入参) {String} id
         * @apiParam (入参) {String} ym 当前这条报表所属年月
         * @apiParam (入参) {Int} total 总号码数
         * @apiParam (入参) {Int} jf 总计费数
         * @apiParam (入参) {Int} success 成功计费数
         * @apiParam (入参) {Int} fail 失败计费数
         * @apiParam (入参) {Int} unknown 未知数
         * @apiParam (入参) {Int} rate 成功率
         * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 
         * @apiSampleRequest /admin/user/editSflReportLog
         * @author jackiwu
         */
    public function editSflReportLog(){
        $cmsConId = trim($this->request->post('cms_con_id'));
        $total = intval(trim($this->request->post('total')));
        $jf = intval(trim($this->request->post('jf')));
        $success = intval(trim($this->request->post('success')));
        $fail = intval(trim($this->request->post('fail')));
        $unknown = intval(trim($this->request->post('unknown')));
        $rate = trim($this->request->post('rate'));
        $ym = trim($this->request->post('ym'));
        $id = trim($this->request->post('id'));
        $type = intval(trim($this->request->post('type')));
        if(!$id){
            return ['code'=>3001,'msg'=>'id错误'];
        }
        if(!$ym){
            return ['code'=> 3001,'msg'=>'年月必填'];
        }
        if(empty($cmsConId) || !$total || !$jf || !$success || empty($rate) || !$type){
            return ['code' => 3001,'msg' => '不可为空'];
        }
        $result = $this->app->user->editSflReportLog($id,$total,$jf,$success,$fail,$unknown,$rate,$ym,$type);
        return $result;
    }

}
