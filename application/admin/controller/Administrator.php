<?php

namespace app\admin\controller;

use app\admin\AdminController;

class Administrator extends AdminController
{
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
    public function getBusiness()
    {
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
    public function addBusiness()
    {
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
    public function updateBusiness()
    {
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
    public function getUserQualificationRecord()
    {
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
     * @apiDescription   auditUserQualificationRecord
     * @apiGroup         admin_Administrator
     * @apiName          auditUserQualificationRecord
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id
     * @apiParam (入参) {String} status 审核状态:1,已提交;2,审核中;3,审核通过;4,审核不通过
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id不存在或者不为数字 / 3002:status码错误 / 3003:该资质已审核
     * @apiSampleRequest /admin/administrator/auditUserQualificationRecord
     * @return array
     * @author rzc
     */
    public function auditUserQualificationRecord()
    {
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
    public function getUserEquities()
    {
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
     * @apiParam (入参) {String} nick_name 充值账户
     * @apiParam (入参) {String} business_id 业务服务id
     * @apiParam (入参) {String} num 充值条数
     * @apiSuccess (返回) {String} code 200:成功 / 3001:手机号格式错误 / 3002:business_id不存在或者不为数字 / 3003:用户不存在 / 3003:price不能小于0 / 3004:该用户没有该服务，无法充值
     * @apiSampleRequest /admin/administrator/rechargeApplication
     * @return array
     * @author rzc
     */
    public function rechargeApplication()
    {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $nick_name      = trim($this->request->post('nick_name'));
        $business_id = trim($this->request->post('business_id'));
        $num         = trim($this->request->post('num'));
        // if (checkMobile($mobile) === false) {
        //     return ['code' => '3001'];
        // }
        if (empty($nick_name)) {
            return ['code' => '3001', 'msg' => '用户名不存在'];
        }
        if (empty($business_id) || intval($business_id) < 1 || !is_numeric($business_id)) {
            return ['code' => '3002'];
        }
        intval($num);
        $result = $this->app->administrator->rechargeApplication($cmsConId, $nick_name, $business_id, $num);
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
    public function getRechargeApplication()
    {
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
    public function aduitRechargeApplication()
    {
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
        if (!in_array($status, [2, 3])) {
            return ['code' => '3002'];
        }
        intval($status);
        $result  = $this->app->administrator->aduitRechargeApplication($status, $message, $id);
        // $this->apiLog($apiName, [$page, $pageNum], $result['code'], '');
        return $result;
    }

    /**
     * @api              {post} / 获取已接入通道
     * @apiDescription   getChannel
     * @apiGroup         admin_Administrator
     * @apiName          getChannel
     * @apiParam (入参) {String} cms_con_id
     * @apiSuccess (返回) {String} code 200:成功 
     * @apiSampleRequest /admin/administrator/getChannel
     * @return array
     * @author rzc
     */
    public function getChannel()
    {
        $cmsConId = trim($this->request->post('cms_con_id'));
        $result  = $this->app->administrator->getChannel();
        return $result;
    }

    /**
     * @api              {post} / 设置通道归属服务
     * @apiDescription   settingChannel
     * @apiGroup         admin_Administrator
     * @apiName          settingChannel
     * @apiParam (入参) {Nmuber} channel_id
     * @apiParam (入参) {Nmuber} business_id
     * @apiSuccess (返回) {String} code 200:成功  / 3001:channel_id错误 / 3002:business_id错误
     * @apiSampleRequest /admin/administrator/settingChannel
     * @return array
     * @author rzc
     */
    public function settingChannel()
    {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $channel_id      = trim($this->request->post('channel_id'));
        $business_id      = trim($this->request->post('business_id'));
        if (empty($channel_id) || intval($channel_id) < 1 || !is_numeric($channel_id)) {
            return ['code' => '3001'];
        }
        if (empty($business_id) || intval($business_id) < 1 || !is_numeric($business_id)) {
            return ['code' => '3001'];
        }
        $result = $this->app->administrator->settingChannel($channel_id, $business_id);
        return $result;
    }

    /**
     * @api              {post} / 设置用户免审通道
     * @apiDescription   distributeUserChannel
     * @apiGroup         admin_Administrator
     * @apiName          distributeUserChannel
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} yidong_channel_id 移动通道ID
     * @apiParam (入参) {String} liantong_channel_id 联通通道ID
     * @apiParam (入参) {String} dianxin_channel_id 电信通道ID
     * @apiParam (入参) {String} nick_name 用户名，用户名为唯一值
     * @apiParam (入参) {String} business_id 服务类型ID
     * @apiSuccess (返回) {String} code 200:成功 / 3001:手机号格式错误 / 3002:channel_id格式错误 / 3003:非法的业务服务ID  / 3004:该用户不存在
     * @apiSampleRequest /admin/administrator/distributeUserChannel
     * @return array
     * @author rzc
     */
    public function distributeUserChannel()
    {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $yidong_channel_id = trim($this->request->post('yidong_channel_id'));
        $liantong_channel_id = trim($this->request->post('liantong_channel_id'));
        $dianxin_channel_id = trim($this->request->post('dianxin_channel_id'));
        $nick_name = trim($this->request->post('nick_name'));
        $business_id = trim($this->request->post('business_id'));
       
        if (empty($yidong_channel_id) || intval($yidong_channel_id) < 1 || !is_numeric($yidong_channel_id)) {
            return ['code' => '3002'];
        }
        if (empty($liantong_channel_id) || intval($liantong_channel_id) < 1 || !is_numeric($liantong_channel_id)) {
            return ['code' => '3002'];
        }
        if (empty($dianxin_channel_id) || intval($dianxin_channel_id) < 1 || !is_numeric($dianxin_channel_id)) {
            return ['code' => '3002'];
        }
        if (!in_array($business_id, [5, 6, 7, 8, 9])) {
            return ['code' => '3003'];
        }
        $result  = $this->app->administrator->distributeUserChannel(intval($yidong_channel_id), intval($liantong_channel_id),intval($dianxin_channel_id),intval($business_id), strval($nick_name));
        return $result;
    }

    /**
     * @api              {post} / 获取用户免审通道
     * @apiDescription   getUserChannel
     * @apiGroup         admin_Administrator
     * @apiName          getUserChannel
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} uid 用户id
     * @apiParam (入参) {String} nick_name 用户名，用户名为唯一值
     * @apiParam (入参) {String} business_id 服务类型ID
     * @apiParam (入参) {String} page 页码 默认1
     * @apiParam (入参) {String} pageNum 条数 默认10
     * @apiSuccess (返回) {String} code 200:成功 / 3001:手机号格式错误 / 3002:channel_id格式错误 / 3003:非法的业务服务ID  / 3004:该用户不存在
     * @apiSampleRequest /admin/administrator/getUserChannel
     * @return array
     * @author rzc
     */
    public function getUserChannel(){
        $cmsConId = trim($this->request->post('cms_con_id'));
        $uid = trim($this->request->post('uid'));
        $nick_name = trim($this->request->post('nick_name'));
        $business_id = trim($this->request->post('business_id'));
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('pageNum'));
        $page     = is_numeric($page) ? $page : 1;
        $pageNum  = is_numeric($pageNum) ? $pageNum : 10;
        $result = $this->app->administrator->getUserChannel($uid, $nick_name, $business_id, $page, $pageNum);
        return $result;
    }

    /**
     * @api              {post} / 修改用户免审通道
     * @apiDescription   updateUserChannel
     * @apiGroup         admin_Administrator
     * @apiName          updateUserChannel
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id 设置ID
     * @apiParam (入参) {String} yidong_channel_id 移动通道ID
     * @apiParam (入参) {String} liantong_channel_id 联通通道ID
     * @apiParam (入参) {String} dianxin_channel_id 电信通道ID
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id格式错误 / 3003:非法的优先级
     * @apiSampleRequest /admin/administrator/updateUserChannel
     * @return array
     * @author rzc
     */
    public function updateUserChannel()
    {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $id = trim($this->request->post('id'));
        $yidong_channel_id = trim($this->request->post('yidong_channel_id'));
        $liantong_channel_id = trim($this->request->post('liantong_channel_id'));
        $dianxin_channel_id = trim($this->request->post('dianxin_channel_id'));
        // $priority = trim($this->request->post('priority'));

        if (empty($id) || intval($id) < 1 || !is_numeric($id)) {
            return ['code' => '3001'];
        }
        /* if (!in_array($priority, [1, 2])) {
            return ['code' => '3003'];
        } */
        if (empty($yidong_channel_id) || intval($yidong_channel_id) < 1 || !is_numeric($yidong_channel_id)) {
            return ['code' => '3002'];
        }
        if (empty($liantong_channel_id) || intval($liantong_channel_id) < 1 || !is_numeric($liantong_channel_id)) {
            return ['code' => '3002'];
        }
        if (empty($dianxin_channel_id) || intval($dianxin_channel_id) < 1 || !is_numeric($dianxin_channel_id)) {
            return ['code' => '3002'];
        }
        $result  = $this->app->administrator->updateUserChannel(intval($id), intval($yidong_channel_id), intval($liantong_channel_id) ,intval($dianxin_channel_id));
        return $result;
    }

    /**
     * @api              {post} / 取消用户使用该通道
     * @apiDescription   delUserChannel
     * @apiGroup         admin_Administrator
     * @apiName          delUserChannel
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id 设置ID
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id格式错误 
     * @apiSampleRequest /admin/administrator/delUserChannel
     * @return array
     * @author rzc
     */
    public function delUserChannel()
    {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $id = trim($this->request->post('id'));

        if (empty($id) || intval($id) < 1 || !is_numeric($id)) {
            return ['code' => '3001'];
        }
        $result  = $this->app->administrator->delUserChannel(intval($id));
        return $result;
    }

    /**
     * @api              {post} / 获取营销任务
     * @apiDescription   getUserSendTask
     * @apiGroup         admin_Administrator
     * @apiName          getUserSendTask
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id 任务id
     * @apiParam (入参) {String} free_trial 1:需要审核;2:审核通过;3:审核不通过
     * @apiParam (入参) {String} send_status 1：待发送,2:已发送;
     * @apiParam (入参) {String} page 页码 默认1
     * @apiParam (入参) {String} pageNum 条数 默认10
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id格式错误 
     * @apiSampleRequest /admin/administrator/getUserSendTask
     * @return array
     * @author rzc
     */
    public function getUserSendTask()
    {
        $id       = trim($this->request->post('id'));
        $free_trial       = trim($this->request->post('free_trial'));
        $uid       = trim($this->request->post('uid'));
        $send_status       = trim($this->request->post('send_status'));
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('pageNum'));
        $cmsConId = trim($this->request->post('cms_con_id'));
        $page     = is_numeric($page) ? $page : 1;
        $pageNum  = is_numeric($pageNum) ? $pageNum : 10;
        $free_trial  = is_numeric($free_trial) ? $free_trial : 0;
        $send_status  = is_numeric($send_status) ? $send_status : 0;
        intval($page);
        intval($pageNum);
        intval($free_trial);
        intval($free_trial);
        $result = $this->app->administrator->getUserSendTask($page, $pageNum, $id, $free_trial, $send_status, $uid);
        return $result;
    }

    /**
     * @api              {post} / 营销任务审核
     * @apiDescription   auditUserSendTask
     * @apiGroup         admin_Administrator
     * @apiName          auditUserSendTask
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id 任务id,多个用半角,分隔开,一次最多100
     * @apiParam (入参) {String} free_trial 审核状态 2:审核通过;3:审核不通过
     * @apiSuccess (返回) {String} code 200:成功 / 3001:有效任务ID为空或者不能超过100个 
     * @apiSampleRequest /admin/administrator/auditUserSendTask
     * @return array
     * @author rzc
     */
    public function auditUserSendTask()
    {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $id = trim($this->request->post('id'));
        $free_trial = trim($this->request->post('free_trial'));
        $ids = explode(',', $id);
        $effective_id = [];
        foreach ($ids as $key => $value) {
            if (empty($value) || intval($value) < 1 || !is_numeric($value)) {
                continue;
            }
            $effective_id[] = $value;
        }
        if (count($effective_id) > 100 || count($effective_id) < 1) {
            return ['code' => '3001'];
        }

        if (!in_array($free_trial, [2, 3])) {
            return ['code' => '3003'];
        }
        $result =  $this->app->administrator->auditUserSendTask($effective_id, $free_trial);
        return $result;
    }


    /**
     * @api              {post} / 分配营销任务通道
     * @apiDescription   distributionChannel
     * @apiGroup         admin_Administrator
     * @apiName          distributionChannel
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id 任务id,多个用半角,分隔开,一次最多100
     * @apiParam (入参) {String} business_id 业务服务id
     * @apiParam (入参) {String} yidong_channel_id 移动通道ID
     * @apiParam (入参) {String} liantong_channel_id 联通通道ID
     * @apiParam (入参) {String} dianxin_channel_id 电信通道ID
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id格式错误 / 3002:channel_id格式错误 / 3003:business_id格式错误
     * @apiSampleRequest /admin/administrator/distributionChannel
     * @return array
     * @author rzc
     */
    public function distributionChannel()
    {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $id = trim($this->request->post('id'));
        $yidong_channel_id = trim($this->request->post('yidong_channel_id'));
        $liantong_channel_id = trim($this->request->post('liantong_channel_id'));
        $dianxin_channel_id = trim($this->request->post('dianxin_channel_id'));
        $business_id = trim($this->request->post('business_id'));

        $ids = explode(',', $id);
        $effective_id = [];
        foreach ($ids as $key => $value) {
            if (empty($value) || intval($value) < 1 || !is_numeric($value)) {
                continue;
            }
            $effective_id[] = $value;
        }
        if (empty($yidong_channel_id) || intval($yidong_channel_id) < 1 || !is_numeric($yidong_channel_id)) {
            return ['code' => '3002'];
        }
        if (empty($liantong_channel_id) || intval($liantong_channel_id) < 1 || !is_numeric($liantong_channel_id)) {
            return ['code' => '3002'];
        }
        if (empty($dianxin_channel_id) || intval($dianxin_channel_id) < 1 || !is_numeric($dianxin_channel_id)) {
            return ['code' => '3002'];
        }
        if (empty($business_id) || intval($business_id) < 1 || !is_numeric($business_id)) {
            return ['code' => '3003'];
        }
        $result =  $this->app->administrator->distributionChannel($effective_id, intval($yidong_channel_id), intval($liantong_channel_id), intval($dianxin_channel_id), intval($business_id));
        return $result;
    }


    /**
     * @api              {post} / 获取行业任务
     * @apiDescription   getUserSendCodeTask
     * @apiGroup         admin_Administrator
     * @apiName          getUserSendCodeTask
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id 任务id
     * @apiParam (入参) {String} free_trial 1:需要审核;2:审核通过;3:审核不通过
     * @apiParam (入参) {String} channel_id 0 未分配通道 1 已分配通道
     * @apiParam (入参) {String} send_status 1：待发送,2:已发送
     * @apiParam (入参) {String} page 页码 默认1
     * @apiParam (入参) {String} pageNum 条数 默认10
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id格式错误 
     * @apiSampleRequest /admin/administrator/getUserSendCodeTask
     * @return array
     * @author rzc
     */
    public function getUserSendCodeTask()
    {
        $id       = trim($this->request->post('id'));
        $free_trial       = trim($this->request->post('free_trial'));
        $channel_id       = trim($this->request->post('channel_id'));
        $send_status       = trim($this->request->post('send_status'));
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('pageNum'));
        $cmsConId = trim($this->request->post('cms_con_id'));
        $uid       = trim($this->request->post('uid'));
        $page     = is_numeric($page) ? $page : 1;
        $pageNum  = is_numeric($pageNum) ? $pageNum : 10;
        $free_trial  = is_numeric($free_trial) ? $free_trial : 0;
        $channel_id  = is_numeric($channel_id) ? $channel_id : 0;
        $send_status  = is_numeric($send_status) ? $send_status : 1;
        intval($page);
        intval($pageNum);
        intval($free_trial);
        intval($channel_id);
        intval($send_status);
        $result = $this->app->administrator->getUserSendCodeTask($page, $pageNum, $id, $free_trial, $channel_id, $send_status, $uid);
        return $result;
    }

    /**
     * @api              {post} / 行业任务审核
     * @apiDescription   auditUserSendCodeTask
     * @apiGroup         admin_Administrator
     * @apiName          auditUserSendCodeTask
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id 任务id,多个用半角,分隔开,一次最多100
     * @apiParam (入参) {String} free_trial 审核状态 2:审核通过;3:审核不通过
     * @apiSuccess (返回) {String} code 200:成功 / 3001:有效任务ID为空或者不能超过100个 
     * @apiSampleRequest /admin/administrator/auditUserSendCodeTask
     * @return array
     * @author rzc
     */
    public function auditUserSendCodeTask()
    {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $id = trim($this->request->post('id'));
        $free_trial = trim($this->request->post('free_trial'));
        $ids = explode(',', $id);
        $effective_id = [];
        foreach ($ids as $key => $value) {
            if (empty($value) || intval($value) < 1 || !is_numeric($value)) {
                continue;
            }
            $effective_id[] = $value;
        }
        if (count($effective_id) > 100 || count($effective_id) < 1) {
            return ['code' => '3001'];
        }

        if (!in_array($free_trial, [2, 3])) {
            return ['code' => '3003'];
        }
        $result =  $this->app->administrator->auditUserSendCodeTask($effective_id, $free_trial);
        return $result;
    }


    /**
     * @api              {post} / 分配行业任务通道
     * @apiDescription   distributionCodeTaskChannel
     * @apiGroup         admin_Administrator
     * @apiName          distributionCodeTaskChannel
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id 任务id,多个用半角,分隔开,一次最多100
     * @apiParam (入参) {String} business_id 业务服务id
     * @apiParam (入参) {String} yidong_channel_id 移动通道ID
     * @apiParam (入参) {String} liantong_channel_id 联通通道ID
     * @apiParam (入参) {String} dianxin_channel_id 电信通道ID
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id格式错误 / 3002:channel_id格式错误 / 3003:business_id格式错误
     * @apiSampleRequest /admin/administrator/distributionCodeTaskChannel
     * @return array
     * @author rzc
     */
    public function distributionCodeTaskChannel()
    {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $id = trim($this->request->post('id'));
        $yidong_channel_id = trim($this->request->post('yidong_channel_id'));
        $liantong_channel_id = trim($this->request->post('liantong_channel_id'));
        $dianxin_channel_id = trim($this->request->post('dianxin_channel_id'));
        $business_id = trim($this->request->post('business_id'));

        $ids = explode(',', $id);
        $effective_id = [];
        foreach ($ids as $key => $value) {
            if (empty($value) || intval($value) < 1 || !is_numeric($value)) {
                continue;
            }
            $effective_id[] = $value;
        }
        if (empty($yidong_channel_id) || intval($yidong_channel_id) < 1 || !is_numeric($yidong_channel_id)) {
            return ['code' => '3002'];
        }
        if (empty($liantong_channel_id) || intval($liantong_channel_id) < 1 || !is_numeric($liantong_channel_id)) {
            return ['code' => '3002'];
        }
        if (empty($dianxin_channel_id) || intval($dianxin_channel_id) < 1 || !is_numeric($dianxin_channel_id)) {
            return ['code' => '3002'];
        }
        if (empty($business_id) || intval($business_id) < 1 || !is_numeric($business_id)) {
            return ['code' => '3003'];
        }
        $result =  $this->app->administrator->distributionCodeTaskChannel($effective_id, intval($yidong_channel_id), intval($liantong_channel_id), intval($dianxin_channel_id), intval($business_id));
        return $result;
    }

    /**
     * @api              {post} / 第三方彩信模板报备接口(普通彩信)
     * @apiDescription   thirdPartyMMSTemplateReport
     * @apiGroup         admin_Administrator
     * @apiName          thirdPartyMMSTemplateReport
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} template_id 模板id
     * @apiParam (入参) {String} channel_id 通道ID
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id格式错误 / 3002:channel_id格式错误 / 3003:business_id格式错误
     * @apiSampleRequest /admin/administrator/thirdPartyMMSTemplateReport
     * @return array
     * @author rzc
     */
    public function thirdPartyMMSTemplateReport(){
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $channel_id = trim($this->request->post('channel_id'));
        $template_id = trim($this->request->post('template_id'));
        if (empty($channel_id) || intval($channel_id) < 1 || !is_numeric($channel_id)) {
            return ['code' => '3001', 'msg' => '通道id为空'];
        }
        if (empty($template_id)) {
            return ['code' => '3002', 'msg' => '模板Id 为空'];
        }
        $result = $this->app->administrator->thirdPartyMMSTemplateReport($channel_id,$template_id);
        return $result;
    }

    /**
     * @api              {post} / 丝芙兰SFTP第三方彩信模板报备接口
     * @apiDescription   sflThirdPartyMMSTemplateReport
     * @apiGroup         admin_Administrator
     * @apiName          sflThirdPartyMMSTemplateReport
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} sfl_relation_id 模板id
     * @apiParam (入参) {String} channel_id 通道ID
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id格式错误 / 3002:channel_id格式错误 / 3003:business_id格式错误
     * @apiSampleRequest /admin/administrator/sflThirdPartyMMSTemplateReport
     * @return array
     * @author rzc
     */
    public function sflThirdPartyMMSTemplateReport(){
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $channel_id = trim($this->request->post('channel_id'));
        $sfl_relation_id = trim($this->request->post('sfl_relation_id'));
        if (empty($channel_id) || intval($channel_id) < 1 || !is_numeric($channel_id)) {
            return ['code' => '3001', 'msg' => '通道id为空'];
        }
        if (empty($sfl_relation_id)) {
            return ['code' => '3002', 'msg' => '模板Id 为空'];
        }
        $result = $this->app->administrator->sflThirdPartyMMSTemplateReport($channel_id,$sfl_relation_id);
        return $result;
    }

    /**
     * @api              {post} / 添加扣量过滤关键词
     * @apiDescription   addDeductWord
     * @apiGroup         admin_Administrator
     * @apiName          addDeductWord
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} word 关键词
     * @apiParam (入参) {String} uid 用户ID 不传用户id为全局变过滤
     * @apiParam (入参) {String} business_id 服务id
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id格式错误 / 3002:channel_id格式错误 / 3003:business_id格式错误
     * @apiSampleRequest /admin/administrator/addDeductWord
     * @return array
     * @author rzc
     */
    public function addDeductWord(){
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $uid = trim($this->request->post('uid'));
        $word = trim($this->request->post('word'));
        $business_id = trim($this->request->post('business_id'));
        if (empty($uid) || intval($uid) < 1 || !is_numeric($uid)) {
            return ['code' => '3001', 'msg' => 'uid为空'];
        }
        if (empty($business_id) || intval($business_id) < 1 || !is_numeric($business_id)) {
            return ['code' => '3002', 'msg' => 'business_id 为空'];
        }
        if (empty($word)) {
            return ['code' => '3003', 'msg' => '关键词不能为空'];
        }
        $result = $this->app->administrator->addDeductWord($business_id, $uid, $word);
        return $result;
    }

    /**
     * @api              {post} / 添加扣量过滤关键词
     * @apiDescription   getDeductWord
     * @apiGroup         admin_Administrator
     * @apiName          getDeductWord
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} page 页码 默认1
     * @apiParam (入参) {String} pageNum 条数 默认10
     * @apiParam (入参) {String} business_id 服务id
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id格式错误 / 3002:channel_id格式错误 / 3003:business_id格式错误
     * @apiSampleRequest /admin/administrator/getDeductWord
     * @return array
     * @author rzc
     */
    public function getDeductWord(){
        $business_id = trim($this->request->post('business_id'));
        if (empty($business_id) || intval($business_id) < 1 || !is_numeric($business_id)) {
            return ['code' => '3001', 'msg' => 'business_id为空'];
        }
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('pageNum'));
        $page     = is_numeric($page) ? $page : 1;
        $pageNum  = is_numeric($pageNum) ? $pageNum : 10;
        intval($page);
        intval($pageNum);
        $result = $this->app->administrator->getDeductWord($business_id, $page, $pageNum);
        return $result;
    }

    /**
     * @api              {post} / 修改扣量过滤关键词
     * @apiDescription   updateDeductWord
     * @apiGroup         admin_Administrator
     * @apiName          updateDeductWord
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id id
     * @apiParam (入参) {String} word 关键词
     * @apiParam (入参) {String} business_id 服务id
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id格式错误 / 3002:channel_id格式错误 / 3003:business_id格式错误
     * @apiSampleRequest /admin/administrator/updateDeductWord
     * @return array
     * @author rzc
     */
    public function updateDeductWord(){
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $id = trim($this->request->post('id'));
        $uid = trim($this->request->post('uid'));
        $word = trim($this->request->post('word'));
        $business_id = trim($this->request->post('business_id'));
        if (empty($id) || intval($id) < 1 || !is_numeric($id)) {
            return ['code' => '3001', 'msg' => 'id为空'];
        }
        /* if (empty($business_id) || intval($business_id) < 1 || !is_numeric($business_id)) {
            return ['code' => '3002', 'msg' => 'business_id 为空'];
        }
        if (empty($word)) {
            return ['code' => '3003', 'msg' => '关键词不能为空'];
        } */
        $result = $this->app->administrator->updateDeductWord($id,$business_id, $uid, $word);
        return $result;
    }

    /**
     * @api              {post} / 添加通道
     * @apiDescription   addSmsSendingChannel
     * @apiGroup         admin_Administrator
     * @apiName          addSmsSendingChannel
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} title 通道名称
     * @apiParam (入参) {String} channel_type 通道类型 1.http 2.cmpp 
     * @apiParam (入参) {String} channel_host 通道连接主机或者域名
     * @apiParam (入参) {String} [channel_port] 连接端口,若无端口则不填
     * @apiParam (入参) {String} channel_source 通道归属:1,中国移动;2,中国联通;3,中国电信;4,三网通;5,移动联通;6,移动电信;7,联通电信
     * @apiParam (入参) {String} business_id 业务服务id 与平台提供服务相对应
     * @apiParam (入参) {String} [channel_price] 通道单价（单位：元）
     * @apiParam (入参) {String} [channel_postway] http请求方式:1,get;2,post;CMPP接口不填
     * @apiParam (入参) {String} channel_source_addr 企业id,企业代码(账户)
     * @apiParam (入参) {String} channel_shared_secret 网关登录密码
     * @apiParam (入参) {String} channel_service_id 业务代码,一般情况下与企业代码一致
     * @apiParam (入参) {String} [channel_template_id] 模板id
     * @apiParam (入参) {String} [channel_dest_id] 短信接入码 短信端口号
     * @apiParam (入参) {String} [channel_flow_velocity] 通道最大流速/秒
     * @apiSuccess (返回) {String} code 200:成功 / 3001:cmpp类接口必填端口 / 3002:通道名称为空 / 3003:地址为空 / 3004:必须确认通道支持运营商范围 / 3005:必须确认通道支持服务/ 3006:企业id,企业代码(账户)不能为空 / 3007:密码不能为空 / 3008:名称不能重复 / 3009:业务代码不能为空
     * @apiSampleRequest /admin/administrator/addSmsSendingChannel
     * @return array
     * @author rzc
     */
    public function addSmsSendingChannel(){
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $title = trim($this->request->post('title'));
        $channel_type = trim($this->request->post('channel_type'));
        $channel_host = trim($this->request->post('channel_host'));
        $channel_port = trim($this->request->post('channel_port'));
        $channel_source = trim($this->request->post('channel_source'));
        $business_id = trim($this->request->post('business_id'));
        $channel_price = trim($this->request->post('channel_price'));
        $channel_postway = trim($this->request->post('channel_postway'));
        $channel_source_addr = trim($this->request->post('channel_source_addr'));
        $channel_shared_secret = trim($this->request->post('channel_shared_secret'));
        $channel_service_id = trim($this->request->post('channel_service_id'));
        $channel_template_id = trim($this->request->post('channel_template_id'));
        $channel_dest_id = trim($this->request->post('channel_dest_id'));
        $channel_flow_velocity = trim($this->request->post('channel_flow_velocity'));
        if ($channel_type == 2 && empty($channel_port)) {
            return ['code' => '3001', 'msg' => 'cmpp类接口必填端口'];
        }
        if (empty($title)) {
             return ['code' => '3002', 'msg' => '通道名称为空' ];
        }
        if (empty($channel_host)) {
            return ['code' => '3003', 'msg' => '地址为空' ];
        }
        if (empty($channel_source)) {
            return ['code' => '3004', 'msg' => '必须确认通道支持运营商范围' ];
        }
        if (empty($business_id)) {
            return ['code' => '3005', 'msg' => '必须确认通道支持服务' ];
        }
        if (empty($channel_source_addr)) {
            return ['code' => '3006', 'msg' => '企业id,企业代码(账户)不能为空' ];
        }
        if (empty($channel_shared_secret)) {
            return ['code' => '3007', 'msg' => '密码不能为空' ];
        }
        if (empty($channel_service_id)) {
            return ['code' => '3009', 'msg' => '业务代码不能为空' ];
        }
        $result = $this->app->administrator->addSmsSendingChannel($title,$channel_type, $channel_host, $channel_port,$channel_source,$business_id, $channel_price, $channel_postway, $channel_source_addr, $channel_shared_secret, $channel_service_id, $channel_template_id, $channel_dest_id, $channel_flow_velocity);
        return $result;
    }

    /**
     * @api              {post} / 获取通道信息
     * @apiDescription   getSmsSendingChannel
     * @apiGroup         admin_Administrator
     * @apiName          getSmsSendingChannel
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} title 通道名称
     * @apiParam (入参) {String} channel_type 通道类型 1.http 2.cmpp 
     * @apiParam (入参) {String} channel_host 通道连接主机或者域名
     * @apiParam (入参) {String} channel_port 连接端口,若无端口则不填
     * @apiParam (入参) {String} channel_source 通道归属:1,中国移动;2,中国联通;3,中国电信;4,三网通;5,移动联通;6,移动电信;7,联通电信
     * @apiParam (入参) {String} business_id 业务服务id 与平台提供服务相对应
     * @apiParam (入参) {String} channel_price 通道单价（单位：元）
     * @apiParam (入参) {String} channel_postway http请求方式:1,get;2,post;CMPP接口不填
     * @apiParam (入参) {String} channel_source_addr 企业id,企业代码(账户)
     * @apiParam (入参) {String} channel_shared_secret 网关登录密码
     * @apiParam (入参) {String} channel_service_id 业务代码,一般情况下与企业代码一致
     * @apiParam (入参) {String} channel_template_id 模板id
     * @apiParam (入参) {String} channel_dest_id 短信接入码 短信端口号
     * @apiParam (入参) {String} channel_flow_velocity 通道最大流速/秒
     * @apiParam (入参) {String} page 页码 默认1
     * @apiParam (入参) {String} pageNum 条数 默认10
     * @apiSuccess (返回) {String} code 200:成功 / 3001:cmpp类接口必填端口 / 3002:通道名称为空 / 3003:地址为空 / 3004:必须确认通道支持运营商范围 / 3005:必须确认通道支持服务/ 3006:企业id,企业代码(账户)不能为空 / 3007:密码不能为空 / 3008:名称不能重复 / 3009:业务代码不能为空
     * @apiSuccess (返回) {Array} channel_list 
     * @apiSuccess (channel_list) {String} title 通道名称
     * @apiSuccess (channel_list) {String} channel_type 通道类型 1.http 2.cmpp 
     * @apiSuccess (channel_list) {String}  channel_host 通道连接主机或者域名
     * @apiSuccess (channel_list) {String}  channel_port 连接端口,若无端口则不填
     * @apiSuccess (channel_list) {String}  channel_source 通道归属:1,中国移动;2,中国联通;3,中国电信;4,三网通;5,移动联通;6,移动电信;7,联通电信
     * @apiSuccess (channel_list) {String} business_id 业务服务id 与平台提供服务相对应
     * @apiSuccess (channel_list) {Number} channel_price 通道单价（单位：元）
     * @apiSuccess (channel_list) {Number} channel_postway http请求方式:1,get;2,post;CMPP接口不填
     * @apiSuccess (channel_list) {String} channel_source_addr 企业id,企业代码(账户)
     * @apiSuccess (channel_list) {String} channel_shared_secret 网关登录密码
     * @apiSuccess (channel_list) {String} channel_service_id 业务代码,一般情况下与企业代码一致
     * @apiSuccess (channel_list) {String} channel_template_id 模板id
     * @apiSuccess (channel_list) {String} channel_dest_id 短信接入码 短信端口号
     * @apiSuccess (channel_list) {Int} channel_flow_velocity 通道最大流速/秒
     * @apiSuccess (channel_list) {Int} channel_status 通道状态:1,空闲;2,正常;3,忙碌;4,停止使用
     * @apiSampleRequest /admin/administrator/getSmsSendingChannel
     * @return array
     * @author rzc
     */
    public function getSmsSendingChannel(){
        $cmsConId = trim($this->request->post('cms_con_id'));
        $title = trim($this->request->post('title'));
        $channel_type = trim($this->request->post('channel_type'));
        $channel_host = trim($this->request->post('channel_host'));
        $channel_port = trim($this->request->post('channel_port'));
        $channel_source = trim($this->request->post('channel_source'));
        $business_id = trim($this->request->post('business_id'));
        $channel_price = trim($this->request->post('channel_price'));
        $channel_postway = trim($this->request->post('channel_postway'));
        $channel_source_addr = trim($this->request->post('channel_source_addr'));
        $channel_shared_secret = trim($this->request->post('channel_shared_secret'));
        $channel_service_id = trim($this->request->post('channel_service_id'));
        $channel_template_id = trim($this->request->post('channel_template_id'));
        $channel_dest_id = trim($this->request->post('channel_dest_id'));
        $channel_flow_velocity = trim($this->request->post('channel_flow_velocity'));

        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('pageNum'));
        $page     = is_numeric($page) ? $page : 1;
        $pageNum  = is_numeric($pageNum) ? $pageNum : 10;
        intval($page);
        intval($pageNum);
        $result = $this->app->administrator->getSmsSendingChannel($title,$channel_type, $channel_host, $channel_port,$channel_source,$business_id, $channel_price, $channel_postway, $channel_source_addr, $channel_shared_secret, $channel_service_id, $channel_template_id, $channel_dest_id, $channel_flow_velocity, $page, $pageNum);
        return $result;
    }

    /**
     * @api              {post} / 修改通道信息,修改后通道需重启否则不生效
     * @apiDescription   editSmsSendingChannel
     * @apiGroup         admin_Administrator
     * @apiName          editSmsSendingChannel
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id 通道id
     * @apiParam (入参) {String} title 通道名称
     * @apiParam (入参) {String} channel_type 通道类型 1.http 2.cmpp 
     * @apiParam (入参) {String} channel_host 通道连接主机或者域名
     * @apiParam (入参) {String} [channel_port] 连接端口,若无端口则不填
     * @apiParam (入参) {String} channel_source 通道归属:1,中国移动;2,中国联通;3,中国电信;4,三网通;5,移动联通;6,移动电信;7,联通电信
     * @apiParam (入参) {String} business_id 业务服务id 与平台提供服务相对应
     * @apiParam (入参) {String} [channel_price] 通道单价（单位：元）
     * @apiParam (入参) {String} [channel_postway] http请求方式:1,get;2,post;CMPP接口不填
     * @apiParam (入参) {String} channel_source_addr 企业id,企业代码(账户)
     * @apiParam (入参) {String} channel_shared_secret 网关登录密码
     * @apiParam (入参) {String} channel_service_id 业务代码,一般情况下与企业代码一致
     * @apiParam (入参) {String} [channel_template_id] 模板id
     * @apiParam (入参) {String} [channel_dest_id] 短信接入码 短信端口号
     * @apiParam (入参) {String} [channel_flow_velocity] 通道最大流速/秒
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id格式错误 
     * @apiSampleRequest /admin/administrator/editSmsSendingChannel
     * @return array
     * @author rzc
     */
    public function editSmsSendingChannel(){
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $id = trim($this->request->post('id'));
        $title = trim($this->request->post('title'));
        $channel_type = trim($this->request->post('channel_type'));
        $channel_host = trim($this->request->post('channel_host'));
        $channel_port = trim($this->request->post('channel_port'));
        $channel_source = trim($this->request->post('channel_source'));
        $business_id = trim($this->request->post('business_id'));
        $channel_price = trim($this->request->post('channel_price'));
        $channel_postway = trim($this->request->post('channel_postway'));
        $channel_source_addr = trim($this->request->post('channel_source_addr'));
        $channel_shared_secret = trim($this->request->post('channel_shared_secret'));
        $channel_service_id = trim($this->request->post('channel_service_id'));
        $channel_template_id = trim($this->request->post('channel_template_id'));
        $channel_dest_id = trim($this->request->post('channel_dest_id'));
        $channel_flow_velocity = trim($this->request->post('channel_flow_velocity'));
        if (empty($id) || !is_numeric($id) || $id < 1) {
            return  ['code' => '3001', 'msg' => 'id格式错误'];
        }
        $result = $this->app->administrator->editSmsSendingChannel($id,$title,$channel_type, $channel_host, $channel_port,$channel_source,$business_id, $channel_price, $channel_postway, $channel_source_addr, $channel_shared_secret, $channel_service_id, $channel_template_id, $channel_dest_id, $channel_flow_velocity);
        return $result;
    }

    /**
     * @api              {post} / 配置客户侧CMPP账户
     * @apiDescription   setUserAccountForCmpp
     * @apiGroup         admin_Administrator
     * @apiName          setUserAccountForCmpp
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} uid 用户id
     * @apiParam (入参) {String} cmpp_name cmpp账户名
     * @apiParam (入参) {String} [yidong_channel_id] 移动通道ID
     * @apiParam (入参) {String} [liantong_channel_id] 联通通道ID
     * @apiParam (入参) {String} [dianxin_channel_id] 电信通道ID
     * @apiParam (入参) {String} account_host 客户ip
     * @apiSuccess (返回) {String} code 200:成功 / 3001:uid格式错误 / 3002:name不能为空 / 3003:account_host不能为空 / 3004:至少分配一条有效通道 / 3005:channel_source格式错误 / 3006:用户不存在
     * @apiSampleRequest /admin/administrator/setUserAccountForCmpp
     * @return array
     * @author rzc
     */
    public function setUserAccountForCmpp(){
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $uid = trim($this->request->post('uid'));
        $cmpp_name = trim($this->request->post('cmpp_name'));
        $yidong_channel_id = trim($this->request->post('yidong_channel_id'));
        $liantong_channel_id = trim($this->request->post('liantong_channel_id'));
        $dianxin_channel_id = trim($this->request->post('dianxin_channel_id'));
        $account_source = trim($this->request->post('account_source'));
        $account_host = trim($this->request->post('account_host'));
        if (empty($uid) || !is_numeric($uid) || $uid < 1) {
            return  ['code' => '3001', 'msg' => 'uid格式错误'];
        }
        if (empty($cmpp_name)) {
            return  ['code' => '3002', 'msg' => 'name不能为空'];
        }
        if (empty($account_host)) {
            return  ['code' => '3003', 'msg' => 'account_host不能为空'];
        }
        if (empty($yidong_channel_id) && empty($liantong_channel_id) && empty($dianxin_channel_id)) {
            return  ['code' => '3004', 'msg' => '至少分配一条有效通道'];
        }
       /*  if (empty($account_source) || !in_array($account_source,[1,2,3,4,5,6,7])) {
            return  ['code' => '3005', 'msg' => 'channel_source格式错误'];
        } */
        $result = $this->app->administrator->setUserAccountForCmpp($uid, $cmpp_name, $yidong_channel_id, $liantong_channel_id, $dianxin_channel_id, $account_host);
        return $result;
    }
}
