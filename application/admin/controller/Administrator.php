<?php

namespace app\admin\controller;

use app\admin\AdminController;

class Administrator extends AdminController {
    protected $beforeActionList = [
        'isLogin', //所有方法的前置操作
        // 'isLogin' => ['except' => 'login'], //除去login其他方法都进行isLogin前置操作
        //        'three'   => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 获取服务类型
     * @apiDescription   getBusiness
     * @apiGroup         admin_Administrator
     * @apiName          getBusiness
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id
     * @apiParam (入参) {String} page 页码 默认1
     * @apiParam (入参) {String} pageNum 条数 默认10
     * @apiParam (入参) {String} getall 1 获取全部
     * @apiSuccess (返回) {String} code 200:成功 / 3001:页码不能为空 / 3002:用户不存在 / 3003:密码错误 / 3004:登录失败
     * @apiSampleRequest /admin/administrator/getBusiness
     * @return array
     * @author rzc
     */
    public function getBusiness() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        $id       = trim($this->request->post('id'));
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('pageNum'));
        $getall   = trim($this->request->post('getall'));
        $page     = is_numeric($page) ? $page : 1;
        $pageNum  = is_numeric($pageNum) ? $pageNum : 10;
        intval($page);
        intval($pageNum);
        intval($getall);
        $result = $this->app->administrator->getBusiness($page, $pageNum, $id, $getall);
        // $this->apiLog($apiName, [$page, $pageNum], $result['code'], '');
        return $result;
    }

    /**
     * @api              {post} / 添加服务类型
     * @apiDescription   addBusiness
     * @apiGroup         admin_Administrator
     * @apiName          addBusiness
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} title title
     * @apiParam (入参) {String} price 服务价格(最多保留小数点后5位)
     * @apiParam (入参) {Number} [donate_num] 赠送数量，默认0
     * @apiSuccess (返回) {String} code 200:成功 / 3001:标题为空 / 3002:price格式错误 / 3003:price不能小于0 / 3004:登录失败
     * @apiSampleRequest /admin/administrator/addBusiness
     * @return array
     * @author rzc
     */
    public function addBusiness() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $title      = trim($this->request->post('title'));
        $price      = trim($this->request->post('price'));
        $donate_num = trim($this->request->post('donate_num'));
        if (empty($title)) {
            return ['code' => '3001'];
        }
        if (empty($price) || !is_numeric($price)) {
            return ['code' => '3002'];
        }
        $price = floatval($price);
        if ($price < 0) {
            return ['code' => '3003'];
        }
        $donate_num = intval($donate_num);
        $result     = $this->app->administrator->addBusiness($title, $price, $donate_num);
        // $this->apiLog($apiName, [$page, $pageNum], $result['code'], '');
        return $result;
    }

    /**
     * @api              {post} / 修改服务类型
     * @apiDescription   updateBusiness
     * @apiGroup         admin_Administrator
     * @apiName          updateBusiness
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id
     * @apiParam (入参) {String} [title] 标题
     * @apiParam (入参) {String} [price] 服务价格(最多保留小数点后5位)
     * @apiParam (入参) {Number} [donate_num] 赠送数量，默认0
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id不存在或者不为数字 / 3002:price格式错误 / 3003:price不能小于0 / 3004:登录失败
     * @apiSampleRequest /admin/administrator/updateBusiness
     * @return array
     * @author rzc
     */
    public function updateBusiness() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $id         = trim($this->request->post('id'));
        $title      = trim($this->request->post('title'));
        $price      = trim($this->request->post('price'));
        $donate_num = trim($this->request->post('donate_num'));
        if (!empty($price) && !is_numeric($price)) {
            return ['code' => '3002'];
        }
        $price = floatval($price);
        if ($price < 0) {
            return ['code' => '3003'];
        }
        if (empty($id) || intval($id) < 1 || !is_numeric($id)) {
            return ['code' => '3001'];
        }
        $donate_num = intval($donate_num);
        $result     = $this->app->administrator->updateBusiness($id, $title, $price, $donate_num);
        // $this->apiLog($apiName, [$page, $pageNum], $result['code'], '');
        return $result;
    }

    /**
     * @api              {post} / 账户资质列表
     * @apiDescription   getUserQualificationRecord
     * @apiGroup         admin_Administrator
     * @apiName          getUserQualificationRecord
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id
     * @apiParam (入参) {String} page 页码 默认1
     * @apiParam (入参) {String} pageNum 条数 默认10
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id不存在或者不为数字 / 3002:price格式错误 / 3003:price不能小于0 / 3004:登录失败
     * @apiSampleRequest /admin/administrator/getUserQualificationRecord
     * @return array
     * @author rzc
     */
    public function getUserQualificationRecord() {
        $cmsConId = trim($this->request->post('cms_con_id'));
        $id       = trim($this->request->post('id'));
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('pageNum'));
        $page     = is_numeric($page) ? $page : 1;
        $pageNum  = is_numeric($pageNum) ? $pageNum : 10;
        intval($page);
        intval($pageNum);
        $result = $this->app->administrator->getUserQualificationRecord($page, $pageNum, $id);
        // $this->apiLog($apiName, [$page, $pageNum], $result['code'], '');
        return $result;
    }

    /**
     * @api              {post} / 账户资质审核
     * @apiDescription   auditUserQualification
     * @apiGroup         admin_Administrator
     * @apiName          auditUserQualification
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id
     * @apiParam (入参) {String} status 审核状态:1,已提交;2,审核中;3,审核通过;4,审核不通过
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id不存在或者不为数字 / 3002:status码错误 / 3003:该资质已审核
     * @apiSampleRequest /admin/administrator/auditUserQualification
     * @return array
     * @author rzc
     */
    public function auditUserQualificationRecord() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $id     = trim($this->request->post('id'));
        $status = trim($this->request->post('status'));
        if (empty($id) || intval($id) < 1 || !is_numeric($id)) {
            return ['code' => '3001'];
        }
        if (!in_array($status, [2, 3, 4])) {
            return ['code' => '3002'];
        }
        $result = $this->app->administrator->auditUserQualificationRecord($id, $status);
        // $this->apiLog($apiName, [$page, $pageNum], $result['code'], '');
        return $result;
    }

    /**
     * @api              {post} / 获取该用户该服务价格
     * @apiDescription   getUserEquities
     * @apiGroup         admin_Administrator
     * @apiName          getUserEquities
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} mobile 账户手机号
     * @apiParam (入参) {String} business_id 业务服务id
     * @apiSuccess (返回) {String} code 200:成功 / 3001:business_id不存在或者不是数字 / 3002:用户不存在 / 3003:price不能小于0 / 3004:登录失败
     * @apiSampleRequest /admin/administrator/getUserEquities
     * @return array
     * @author rzc
     */
    public function getUserEquities() {
        $cmsConId    = trim($this->request->post('cms_con_id'));
        $mobile      = trim($this->request->post('mobile'));
        $business_id = trim($this->request->post('business_id'));
        if (checkMobile($mobile) === false) {
            return ['code' => '3001'];
        }
        if (empty($business_id) || intval($business_id) < 1 || !is_numeric($business_id)) {
            return ['code' => '3001'];
        }
        $result = $this->app->administrator->getUserEquities($mobile, $business_id);
        // $this->apiLog($apiName, [$page, $pageNum], $result['code'], '');
        return $result;
    }

    /**
     * @api              {post} / 服务充值申请
     * @apiDescription   rechargeApplication
     * @apiGroup         admin_Administrator
     * @apiName          rechargeApplication
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} mobile 充值账户手机号
     * @apiParam (入参) {String} business_id 业务服务id
     * @apiParam (入参) {String} num 充值条数
     * @apiSuccess (返回) {String} code 200:成功 / 3001:手机号格式错误 / 3002:business_id不存在或者不为数字 / 3003:用户不存在 / 3003:price不能小于0 / 3004:该用户没有该服务，无法充值
     * @apiSampleRequest /admin/administrator/rechargeApplication
     * @return array
     * @author rzc
     */
    public function rechargeApplication() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $mobile      = trim($this->request->post('mobile'));
        $business_id = trim($this->request->post('business_id'));
        $num         = trim($this->request->post('num'));
        if (checkMobile($mobile) === false) {
            return ['code' => '3001'];
        }
        if (empty($business_id) || intval($business_id) < 1 || !is_numeric($business_id)) {
            return ['code' => '3002'];
        }
        intval($num);
        $result = $this->app->administrator->rechargeApplication($cmsConId, $mobile, $business_id, $num);
        // $this->apiLog($apiName, [$page, $pageNum], $result['code'], '');
        return $result;
    }

    /**
     * @api              {post} / 服务充值列表
     * @apiDescription   getRechargeApplication
     * @apiGroup         admin_Administrator
     * @apiName          getRechargeApplication
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id
     * @apiParam (入参) {String} page 页码 默认1
     * @apiParam (入参) {String} pageNum 条数 默认10
     * @apiParam (入参) {String} [getall] 传1获取全部 
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id不存在或者不为数字 / 3002:price格式错误 / 3003:price不能小于0 / 3004:登录失败
     * @apiSampleRequest /admin/administrator/getRechargeApplication
     * @return array
     * @author rzc
     */
    public function getRechargeApplication() {
        $cmsConId = trim($this->request->post('cms_con_id'));
        $id       = trim($this->request->post('id'));
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('pageNum'));
        $getall  = trim($this->request->post('getall'));
        $page     = is_numeric($page) ? $page : 1;
        $pageNum  = is_numeric($pageNum) ? $pageNum : 10;
        intval($page);
        intval($pageNum);
        $result = $this->app->administrator->getRechargeApplication($page, $pageNum, $id, $getall);
        // $this->apiLog($apiName, [$page, $pageNum], $result['code'], '');
        return $result;
    }

    /**
     * @api              {post} / 审核服务充值
     * @apiDescription   aduitRechargeApplication
     * @apiGroup         admin_Administrator
     * @apiName          aduitRechargeApplication
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id
     * @apiParam (入参) {String} status 状态 2.已审核 3.取消
     * @apiParam (入参) {String} message 审核留言
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id不存在或者不为数字 / 3002:status格式错误 / 3003:已审核 / 3004:登录失败
     * @apiSampleRequest /admin/administrator/aduitRechargeApplication
     * @return array
     * @author rzc
     */
    public function aduitRechargeApplication() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $id      = trim($this->request->post('id'));
        $status  = trim($this->request->post('status'));
        $message = trim($this->request->post('message'));
        if (empty($id) || intval($id) < 1 || !is_numeric($id)) {
            return ['code' => '3001'];
        }
        if (!in_array($status,[2,3])) {
            return ['code' => '3002'];
        }
        intval($status);
        $result  = $this->app->administrator->aduitRechargeApplication($status, $message, $id);
        // $this->apiLog($apiName, [$page, $pageNum], $result['code'], '');
        return $result;
    }
}