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
     * @api              {post} / 分配用户通道
     * @apiDescription   distributeUserChannel
     * @apiGroup         admin_Administrator
     * @apiName          distributeUserChannel
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} channel_id 通道ID
     * @apiParam (入参) {String} user_phone 被设置用户手机号
     * @apiParam (入参) {String} priority 优先级:1,默认省网优先;2,非接入省网外优先
     * @apiSuccess (返回) {String} code 200:成功 / 3001:手机号格式错误 / 3002:channel_id格式错误 / 3003:非法的优先级  / 3004:该用户不存在
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
        $channel_id = trim($this->request->post('channel_id'));
        $user_phone = trim($this->request->post('user_phone'));
        $priority = trim($this->request->post('priority'));
        if (checkMobile($user_phone) === false) {
            return ['code' => '3001'];
        }
        if (empty($channel_id) || intval($channel_id) < 1 || !is_numeric($channel_id)) {
            return ['code' => '3002'];
        }
        if (!in_array($priority, [1, 2])) {
            return ['code' => '3003'];
        }
        $result  = $this->app->administrator->distributeUserChannel(intval($channel_id), intval($user_phone), intval($priority));
        return $result;
    }

    /**
     * @api              {post} / 修改用户该通道的优先级
     * @apiDescription   updateUserChannel
     * @apiGroup         admin_Administrator
     * @apiName          updateUserChannel
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id 设置ID
     * @apiParam (入参) {String} priority 优先级:1,默认省网优先;2,非接入省网外优先
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
        $priority = trim($this->request->post('priority'));

        if (empty($id) || intval($id) < 1 || !is_numeric($id)) {
            return ['code' => '3001'];
        }
        if (!in_array($priority, [1, 2])) {
            return ['code' => '3003'];
        }
        $result  = $this->app->administrator->updateUserChannel(intval($id), intval($priority));
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
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('pageNum'));
        $cmsConId = trim($this->request->post('cms_con_id'));
        $page     = is_numeric($page) ? $page : 1;
        $pageNum  = is_numeric($pageNum) ? $pageNum : 10;
        intval($page);
        intval($pageNum);
        $result = $this->app->administrator->getUserSendTask($page, $pageNum, $id);
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
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('pageNum'));
        $cmsConId = trim($this->request->post('cms_con_id'));
        $page     = is_numeric($page) ? $page : 1;
        $pageNum  = is_numeric($pageNum) ? $pageNum : 10;
        intval($page);
        intval($pageNum);
        $result = $this->app->administrator->getUserSendCodeTask($page, $pageNum, $id);
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
     * @apiParam (入参) {String} channel_id 通道ID
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
        $channel_id = trim($this->request->post('channel_id'));
        $business_id = trim($this->request->post('business_id'));

        $ids = explode(',', $id);
        $effective_id = [];
        foreach ($ids as $key => $value) {
            if (empty($value) || intval($value) < 1 || !is_numeric($value)) {
                continue;
            }
            $effective_id[] = $value;
        }
        if (empty($channel_id) || intval($channel_id) < 1 || !is_numeric($channel_id)) {
            return ['code' => '3002'];
        }
        if (empty($business_id) || intval($business_id) < 1 || !is_numeric($business_id)) {
            return ['code' => '3003'];
        }
        $result =  $this->app->administrator->distributionCodeTaskChannel($effective_id, intval($channel_id), intval($business_id));
        return $result;
    }

    /**
     * @api              {post} / 第三方彩信模板报备接口
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
}
