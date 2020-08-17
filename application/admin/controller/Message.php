<?php

namespace app\admin\controller;

use app\admin\AdminController;
use think\Controller;

class Message extends AdminController {
    protected $beforeActionList = [
        'isLogin', //所有方法的前置操作
        //    'isLogin' => ['except' => 'exportReceiptReport'],//除去login其他方法都进行isLogin前置操作
        //        'three'   => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 获取彩信任务
     * @apiDescription   getMultimediaMessageTask
     * @apiGroup         admin_Message
     * @apiName          getMultimediaMessageTask
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id 任务id
     * @apiParam (入参) {String} free_trial 1:需要审核;2:审核通过;3:审核不通过
     * @apiParam (入参) {String} send_status 1：待发送,2:已发送
     * @apiParam (入参) {String} page 页码 默认1
     * @apiParam (入参) {String} pageNum 条数 默认10
     * @apiParam (入参) {String} [title] 任务名称
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id格式错误
     * @apiSampleRequest /admin/message/getMultimediaMessageTask
     * @return array
     * @author rzc
     */
    public function getMultimediaMessageTask() {
        $id       = trim($this->request->post('id'));
        $free_trial       = trim($this->request->post('free_trial'));
        $send_status       = trim($this->request->post('send_status'));
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('pageNum'));
        $cmsConId = trim($this->request->post('cms_con_id'));
        $title    = trim($this->request->post('title'));
        $page     = is_numeric($page) ? $page : 1;
        $pageNum  = is_numeric($pageNum) ? $pageNum : 10;
        $free_trial  = is_numeric($free_trial) ? $free_trial : 0;
        $send_status  = is_numeric($send_status) ? $send_status : 0;
        intval($page);
        intval($pageNum);
        intval($free_trial);
        intval($send_status);
        $result = $this->app->message->getMultimediaMessageTask($page, $pageNum, $id, $title, $free_trial, $send_status);
        return $result;
    }

    /**
     * @api              {post} / 彩信任务审核
     * @apiDescription   auditMultimediaMessageTask
     * @apiGroup         admin_Message
     * @apiName          auditMultimediaMessageTask
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id 任务id,多个用半角,分隔开,一次最多100
     * @apiParam (入参) {String} free_trial 审核状态 2:审核通过;3:审核不通过
     * @apiSuccess (返回) {String} code 200:成功 / 3001:有效任务ID为空或者不能超过100个
     * @apiSampleRequest /admin/message/auditMultimediaMessageTask
     * @return array
     * @author rzc
     */
    public function auditMultimediaMessageTask() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $id           = trim($this->request->post('id'));
        $free_trial   = trim($this->request->post('free_trial'));
        $ids          = explode(',', $id);
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
        $result = $this->app->message->auditMultimediaMessageTask($effective_id, $free_trial);
        return $result;
    }

    /**
     * @api              {post} / 分配任务通道
     * @apiDescription   distributionMultimediaChannel
     * @apiGroup         admin_Message
     * @apiName          distributionMultimediaChannel
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id 任务id,多个用半角,分隔开,一次最多100
     * @apiParam (入参) {String} business_id 业务服务id
     * @apiParam (入参) {String} yidong_channel_id 移动通道ID
     * @apiParam (入参) {String} liantong_channel_id 联通通道ID
     * @apiParam (入参) {String} dianxin_channel_id 电信通道ID
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id格式错误 / 3002:channel_id格式错误 / 3003:business_id格式错误
     * @apiSampleRequest /admin/message/distributionMultimediaChannel
     * @return array
     * @author rzc
     */
    public function distributionMultimediaChannel() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $id                  = trim($this->request->post('id'));
        $channel_id          = trim($this->request->post('channel_id'));
        $business_id         = trim($this->request->post('business_id'));
        $yidong_channel_id   = trim($this->request->post('yidong_channel_id'));
        $liantong_channel_id = trim($this->request->post('liantong_channel_id'));
        $dianxin_channel_id  = trim($this->request->post('dianxin_channel_id'));
        $ids                 = explode(',', $id);
        $effective_id        = [];
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
        $result = $this->app->message->distributionMultimediaChannel($effective_id, intval($yidong_channel_id), intval($liantong_channel_id), intval($dianxin_channel_id), intval($business_id));
        return $result;
    }

    /**
     * @api              {get} / 导出回执报告
     * @apiDescription   exportReceiptReport
     * @apiGroup         admin_Message
     * @apiName          exportReceiptReport
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id 任务id
     * @apiParam (入参) {String} business_id 业务服务id(服务类型)
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id格式错误 / 3002:business_id格式错误 / 3003:business_id格式错误
     * @apiSampleRequest /admin/message/exportReceiptReport
     * @return array
     * @author rzc
     */
    public function exportReceiptReport() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->get('cms_con_id'));
        // if ($this->checkPermissions($cmsConId, $apiName) === false) {
        //     return ['code' => '3100'];
        // }
        $id          = trim($this->request->get('id'));
        $business_id = trim($this->request->get('business_id'));
        if (empty($id) || intval($id) < 1 || !is_numeric($id)) {
            return ['code' => '3001'];
        }
        if (empty($business_id) || intval($business_id) < 1 || !is_numeric($business_id)) {
            return ['code' => '3002'];
        }
        $result = $this->app->message->exportReceiptReport(intval($id), intval($business_id));
        return $result;
    }

    /**
     * @api              {get} / 导出彩信回执报告
     * @apiDescription   exportMultimediaReceiptReport
     * @apiGroup         admin_Message
     * @apiName          exportMultimediaReceiptReport
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id 任务id
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id格式错误 / 3002:business_id格式错误 / 3003:business_id格式错误
     * @apiSampleRequest /admin/message/exportMultimediaReceiptReport
     * @return array
     * @author rzc
     */
    public function exportMultimediaReceiptReport() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->get('cms_con_id'));
        // if ($this->checkPermissions($cmsConId, $apiName) === false) {
        //     return ['code' => '3100'];
        // }
        $id = trim($this->request->get('id'));
        if (empty($id) || intval($id) < 1 || !is_numeric($id)) {
            return ['code' => '3001'];
        }
        $result = $this->app->message->exportMultimediaReceiptReport(intval($id));
        return $result;
    }

    /**
     * @api              {get} / 获取所有用户模板（非彩信）
     * @apiDescription   getUserModel
     * @apiGroup         admin_Message
     * @apiName          getUserModel
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id 任务id,多个用半角,分隔开,一次最多100
     * @apiParam (入参) {String} business_id 业务服务id(服务类型)
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id格式错误 / 3002:business_id格式错误 / 3003:business_id格式错误
     * @apiSampleRequest /admin/message/getUserModel
     * @return array
     * @author rzc
     */
    public function getUserModel() {
        $ConId   = trim($this->request->post('cms_con_id'));
        $page    = trim($this->request->post('page'));
        $pageNum = trim($this->request->post('pageNum'));
        $page    = is_numeric($page) ? $page : 1;
        $pageNum = is_numeric($pageNum) ? $pageNum : 10;
        intval($page);
        intval($pageNum);
        $result = $this->app->message->getUserModel($page, $pageNum);
        return $result;
    }

    /**
     * @api              {post} / 审核用户模板
     * @apiDescription   auditUserModel
     * @apiGroup         admin_Message
     * @apiName          auditUserModel
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id 模板Id
     * @apiParam (入参) {String} status 状态:3,审核通过;4,审核不通过;5,停用
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id格式错误 / 3002:审核状态码错误 /
     * @apiSampleRequest /admin/message/auditUserModel
     * @return array
     * @author rzc
     */
    public function auditUserModel() {
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
        if (!in_array($status, [3, 4, 5])) {
            return ['code' => '3002'];
        }
        $result = $this->app->message->auditUserModel(intval($id), $status);
        return $result;
    }

    /**
     * @api              {post} / 获取所有用户签名
     * @apiDescription   getUserSignature
     * @apiGroup         admin_Message
     * @apiName          getUserSignature
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id 任务id,多个用半角,分隔开,一次最多100
     * @apiParam (入参) {String} business_id 业务服务id(服务类型)
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id格式错误 / 3002:business_id格式错误 / 3003:business_id格式错误
     * @apiSampleRequest /admin/message/getUserSignature
     * @return array
     * @author rzc
     */
    public function getUserSignature() {
        $ConId   = trim($this->request->post('cms_con_id'));
        $page    = trim($this->request->post('page'));
        $pageNum = trim($this->request->post('pageNum'));
        $page    = is_numeric($page) ? $page : 1;
        $pageNum = is_numeric($pageNum) ? $pageNum : 10;
        intval($page);
        intval($pageNum);
        $result = $this->app->message->getUserSignature($page, $pageNum);
        return $result;
    }

    /**
     * @api              {post} / 审核用户签名
     * @apiDescription   auditUserSignature
     * @apiGroup         admin_Message
     * @apiName          auditUserSignature
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id 模板Id
     * @apiParam (入参) {String} status 状态:2,审核通过;3,审核不通过
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id格式错误 / 3002:审核状态码错误 /
     * @apiSampleRequest /admin/message/auditUserSignature
     * @return array
     * @author rzc
     */
    public function auditUserSignature() {
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
        if (!in_array($status, [2, 3])) {
            return ['code' => '3002'];
        }
        $result = $this->app->message->auditUserSignature(intval($id), $status);
        return $result;
    }

    /**
     * @api              {post} / 获取拓展码库
     * @apiDescription   getDevelopCode
     * @apiGroup         admin_Message
     * @apiName          getDevelopCode
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} [no_lenth] 长度
     * @apiParam (入参) {String} [develop_no] develop_no
     * @apiParam (入参) {String} page 页码 默认1
     * @apiParam (入参) {String} pageNum 条数 默认10
     * @apiParam (入参) {String} is_bind 是否绑定：1.未绑定；2.已绑定
     * @apiSuccess (返回) {String} code 200:成功 / 3001:no_lenth格式错误或者查取范围错误 / 3002:绑定状态码错误 /
     * @apiSampleRequest /admin/message/getDevelopCode
     * @return array
     * @author rzc
     */
    public function getDevelopCode() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $no_lenth   = trim($this->request->post('no_lenth'));
        $develop_no = trim($this->request->post('develop_no'));
        $is_bind    = trim($this->request->post('is_bind'));
        $pageNum    = trim($this->request->post('pageNum'));
        $page       = trim($this->request->post('page'));
        $page       = is_numeric($page) ? $page : 1;
        $pageNum    = is_numeric($pageNum) ? $pageNum : 10;
        intval($page);
        intval($pageNum);
        if (!empty($no_lenth) && (intval($no_lenth) < 2 || !is_numeric($no_lenth) || intval($no_lenth) > 6)) {
            return ['code' => '3001'];
        }
        strval($develop_no);
        if (!empty($is_bind) && !in_array($is_bind, [1, 2])) {
            return ['code' => '3002'];
        }
        $result = $this->app->message->getDevelopCode($page, $pageNum, $no_lenth, $develop_no, intval($is_bind));
        return $result;
    }

    /**
     * @api              {post} / 随机抽取一个未绑定的拓展码
     * @apiDescription   getOneRandomDevelopCode
     * @apiGroup         admin_Message
     * @apiName          getOneRandomDevelopCode
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} [no_lenth] 长度
     * @apiSuccess (返回) {String} code 200:成功 / 3001:no_lenth格式错误或者查取范围错误 / 3002:改号段已无空余拓展码 /
     * @apiSampleRequest /admin/message/getOneRandomDevelopCode
     * @return array
     * @author rzc
     */
    public function getOneRandomDevelopCode() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $no_lenth = trim($this->request->post('no_lenth'));
        if (!empty($no_lenth) && (intval($no_lenth) < 2 || !is_numeric($no_lenth) || intval($no_lenth) > 6)) {
            return ['code' => '3001'];
        }
        $result = $this->app->message->getOneRandomDevelopCode($no_lenth);
        return $result;
    }

    /**
     * @api              {post} / 验证拓展码是否被绑定过
     * @apiDescription   verifyDevelopCode
     * @apiGroup         admin_Message
     * @apiName          verifyDevelopCode
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} develop_no 扩展码号
     * @apiSuccess (返回) {String} code 200:未绑定 / 3001:no_lenth格式错误或者查取范围错误 / 3002:已绑定 /
     * @apiSampleRequest /admin/message/verifyDevelopCode
     * @return array
     * @author rzc
     */
    public function verifyDevelopCode() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $develop_no = trim($this->request->post('develop_no'));
        if (!empty($develop_no) && (strlen(intval($develop_no)) < 2 || !is_numeric($develop_no) || strlen(intval($develop_no)) > 6)) {
            return ['code' => '3001'];
        }
        $result = $this->app->message->verifyDevelopCode($develop_no);
        return $result;
    }

    /**
     * @api              {post} / 用户绑定拓展码
     * @apiDescription   userBindDevelopCode
     * @apiGroup         admin_Message
     * @apiName          userBindDevelopCode
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} develop_no 扩展码号
     * @apiParam (入参) {String} business_id 业务服务id
     * @apiParam (入参) {String} source 服务范围1移动；2电信；3联通；4三网；5移动电信；6移动联通；7联通电信
     * @apiParam (入参) {String} nick_name 用户昵称
     * @apiSuccess (返回) {String} code 200:成功 / 3001:no_lenth格式错误或者查取范围错误 / 3002:source码错误 / 3003:用户不存在或者未启用 / 3004：该扩展码不存在 / 3005:该扩展码已被其他用户绑定 / 3006:已添加过的服务范围拓展码 / 3007 异常的拓展号码
     * @apiSampleRequest /admin/message/userBindDevelopCode
     * @return array
     * @author rzc
     */
    public function userBindDevelopCode() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $develop_no = trim($this->request->post('develop_no'));
        $nick_name  = trim($this->request->post('nick_name'));

        $business_id = trim($this->request->post('business_id'));
        $source      = trim($this->request->post('source'));
        if (!empty($develop_no) && (strlen(intval($develop_no)) < 2 || !is_numeric($develop_no) || strlen(intval($develop_no)) > 6)) {
            return ['code' => '3001'];
        }
        if (!in_array($source, [1, 2, 3, 4, 5, 6, 7])) {
            return ['code' => '3002'];
        }
        $result = $this->app->message->userBindDevelopCode($develop_no, $nick_name, $business_id, $source);
        return $result;
    }

    /**
     * @api              {post} / 查看扩展码绑定关系
     * @apiDescription   getuserBindDevelopCode
     * @apiGroup         admin_Message
     * @apiName          getuserBindDevelopCode
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} develop_no 扩展码号
     * @apiSuccess (返回) {String} code 200:成功 / 3001:no_lenth格式错误或者查取范围错误
     * @apiSampleRequest /admin/message/getuserBindDevelopCode
     * @return array
     * @author rzc
     */
    public function getuserBindDevelopCode() {
        $develop_no = trim($this->request->post('develop_no'));
        if (!empty($develop_no) && (strlen(intval($develop_no)) < 2 || !is_numeric($develop_no) || strlen(intval($develop_no)) > 6)) {
            return ['code' => '3001'];
        }
        $result = $this->app->message->getuserBindDevelopCode($develop_no);
        return $result;
    }

    /**
     * @api              {post} / 解除拓展码绑定关系
     * @apiDescription   deluserBindDevelopCode
     * @apiGroup         admin_Message
     * @apiName          deluserBindDevelopCode
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id 绑定关系id
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id格式错误或者查取范围错误
     * @apiSampleRequest /admin/message/deluserBindDevelopCode
     * @return array
     * @author rzc
     */
    public function deluserBindDevelopCode() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $id = trim($this->request->post('id'));
        if (empty($id) || intval($id) < 1 || !is_numeric($id)) {
            return ['code' => '3001'];
        }
        $result = $this->app->message->deluserBindDevelopCode($id);
        return $result;
    }

    /**
     * @api              {post} / 获取所有用户模板（彩信）
     * @apiDescription   getUserMultimediaTemplate
     * @apiGroup         admin_Message
     * @apiName          getUserMultimediaTemplate
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id 任务id,多个用半角,分隔开,一次最多100
     * @apiParam (入参) {String} business_id 业务服务id(服务类型)
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id格式错误 / 3002:business_id格式错误 / 3003:business_id格式错误
     * @apiSampleRequest /admin/message/getUserMultimediaTemplate
     * @return array
     * @author rzc
     */
    public function getUserMultimediaTemplate() {
        $ConId   = trim($this->request->post('cms_con_id'));
        $page    = trim($this->request->post('page'));
        $pageNum = trim($this->request->post('pageNum'));
        $page    = is_numeric($page) ? $page : 1;
        $pageNum = is_numeric($pageNum) ? $pageNum : 10;
        intval($page);
        intval($pageNum);
        $result = $this->app->message->getUserMultimediaTemplate($page, $pageNum);
        return $result;
    }

    /**
     * @api              {post} / 审核用户彩信模板
     * @apiDescription   auditUserMultimediaTemplatel
     * @apiGroup         admin_Message
     * @apiName          auditUserMultimediaTemplatel
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id 模板Id
     * @apiParam (入参) {String} status 状态:2,审核通过;3,审核不通过;
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id格式错误 / 3002:审核状态码错误 /
     * @apiSampleRequest /admin/message/auditUserMultimediaTemplatel
     * @return array
     * @author rzc
     */
    public function auditUserMultimediaTemplatel() {
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
        if (!in_array($status, [2, 3])) {
            return ['code' => '3002'];
        }
        $result = $this->app->message->auditUserMultimediaTemplatel(intval($id), $status);
        return $result;
    }

    /**
     * @api              {post} / 获取丝芙兰SFTP营销任务
     * @apiDescription   getSflSendTask
     * @apiGroup         admin_Message
     * @apiName          getSflSendTask
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} [id] 任务id
     * @apiParam (入参) {String} [template_id] 模板id
     * @apiParam (入参) {String} [task_content] 发送内容
     * @apiParam (入参) {String} [mseeage_id] 丝芙兰彩信id
     * @apiParam (入参) {String} [mobile] 发送号码
     * @apiParam (入参) {String} [start_time] 开始时间
     * @apiParam (入参) {String} [end_time] 结束时间
     * @apiParam (入参) {String} page 页码 默认1
     * @apiParam (入参) {String} pageNum 条数 默认10
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id格式错误
     * @apiSampleRequest /admin/message/getSflSendTask
     * @return array
     * @author rzc
     */
    public function getSflSendTask() {
        $id           = trim($this->request->post('id'));
        $template_id  = trim($this->request->post('template_id'));
        $task_content = trim($this->request->post('task_content'));
        $mseeage_id   = trim($this->request->post('mseeage_id'));
        $mobile       = trim($this->request->post('mobile'));
        $page         = trim($this->request->post('page'));
        $pageNum      = trim($this->request->post('pageNum'));
        $cmsConId     = trim($this->request->post('cms_con_id'));
        $start_time   = trim($this->request->post('start_time'));
        $end_time     = trim($this->request->post('end_time'));
        if (!empty($start_time)) {
            if (strtotime($start_time) == false) {
                return ['code' => '3002'];
            }
        }
        if (!empty($end_time)) {
            if (strtotime($end_time) == false) {
                return ['code' => '3002'];
            }
        }
        $start_time = strtotime($start_time);
        $end_time   = strtotime($end_time);
        $page       = is_numeric($page) ? $page : 1;
        $pageNum    = is_numeric($pageNum) ? $pageNum : 10;
        intval($page);
        intval($pageNum);

        $result = $this->app->message->getSflSendTask($page, $pageNum, $id, $template_id, $task_content, $mseeage_id, $mobile, $start_time, $end_time);
        return $result;
    }

    /**
     * @api              {post} / 丝芙兰sftp营销任务模板批量审核
     * @apiDescription   auditSflSendTask
     * @apiGroup         admin_Message
     * @apiName          auditSflSendTask
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} template_id 任务id
     * @apiParam (入参) {String} start_time 开始时间
     * @apiParam (入参) {String} end_time 结束时间
     * @apiParam (入参) {String} free_trial 审核状态 2:审核通过;3:审核不通过
     * @apiSuccess (返回) {String} code 200:成功 / 3001:有效任务template_id为空 / 3002:时间格式错误 / 3003:审核状态错误
     * @apiSampleRequest /admin/message/auditUserSendTask
     * @return array
     * @author rzc
     */
    public function auditSflSendTask() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $template_id = trim($this->request->post('template_id'));
        $start_time  = trim($this->request->post('start_time'));
        $end_time    = trim($this->request->post('end_time'));
        $free_trial  = trim($this->request->post('free_trial'));
        if (empty($template_id)) {
            return ['code' => '3001'];
        }
        if (!in_array($free_trial, [2, 3])) {
            return ['code' => '3003'];
        }
        if (strtotime($start_time) == false || strtotime($end_time) == false) {
            return ['code' => '3002'];
        }
        $start_time = strtotime($start_time);
        $end_time   = strtotime($end_time);
        $result     = $this->app->message->auditSflSendTask($template_id, $free_trial, $start_time, $end_time);
        return $result;
    }

    /**
     * @api              {post} / 丝芙兰sftp营销任务模板批量分配通道
     * @apiDescription   distributionSflSendTaskChannel
     * @apiGroup         admin_Message
     * @apiName          distributionSflSendTaskChannel
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} template_id 任务id
     * @apiParam (入参) {String} start_time 开始时间
     * @apiParam (入参) {String} end_time 结束时间
     * @apiParam (入参) {String} yidong_channel_id 移动通道ID
     * @apiParam (入参) {String} liantong_channel_id 联通通道ID
     * @apiParam (入参) {String} dianxin_channel_id 电信通道ID
     * @apiSuccess (返回) {String} code 200:成功 / 3001:有效任务template_id为空 / 3002:时间格式错误 / 3003:通道id 错误
     * @apiSampleRequest /admin/message/distributionSflSendTaskChannel
     * @return array
     * @author rzc
     */
    public function distributionSflSendTaskChannel() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $template_id         = trim($this->request->post('template_id'));
        $start_time          = trim($this->request->post('start_time'));
        $end_time            = trim($this->request->post('end_time'));
        $yidong_channel_id   = trim($this->request->post('yidong_channel_id'));
        $liantong_channel_id = trim($this->request->post('liantong_channel_id'));
        $dianxin_channel_id  = trim($this->request->post('dianxin_channel_id'));
        if (empty($yidong_channel_id) || intval($yidong_channel_id) < 1 || !is_numeric($yidong_channel_id)) {
            return ['code' => '3003'];
        }
        if (empty($liantong_channel_id) || intval($liantong_channel_id) < 1 || !is_numeric($liantong_channel_id)) {
            return ['code' => '3003'];
        }
        if (empty($dianxin_channel_id) || intval($dianxin_channel_id) < 1 || !is_numeric($dianxin_channel_id)) {
            return ['code' => '3003'];
        }
        if (strtotime($start_time) == false || strtotime($end_time) == false) {
            return ['code' => '3002'];
        }
        $start_time = strtotime($start_time);
        $end_time   = strtotime($end_time);
        $result     = $this->app->message->distributionSflSendTaskChannel($template_id, intval($yidong_channel_id), intval($liantong_channel_id), intval($dianxin_channel_id), $start_time, $end_time);
        return $result;
    }

    /**
     * @api              {post} / 丝芙兰sftp营销任务单条审核
     * @apiDescription   auditOneSflSendTask
     * @apiGroup         admin_Message
     * @apiName          auditOneSflSendTask
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id 任务id
     * @apiParam (入参) {String} free_trial 审核状态 2:审核通过;3:审核不通过
     * @apiSuccess (返回) {String} code 200:成功 / 3001:有效任务ID为空 / 3002:时间格式错误 / 3003:审核状态错误
     * @apiSampleRequest /admin/message/auditOneSflSendTask
     * @return array
     * @author rzc
     */
    public function auditOneSflSendTask() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $free_trial = trim($this->request->post('free_trial'));
        $id         = trim($this->request->post('id'));
        if (!in_array($free_trial, [2, 3])) {
            return ['code' => '3003'];
        }
        $result = $this->app->message->auditOneSflSendTask($id, $free_trial);
        return $result;
    }

    /**
     * @api              {post} / 丝芙兰sftp营销任务单条分配通道
     * @apiDescription   distributionOneSflSendTaskChannel
     * @apiGroup         admin_Message
     * @apiName          distributionOneSflSendTaskChannel
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id 任务id
     * @apiParam (入参) {String} yidong_channel_id 移动通道ID
     * @apiParam (入参) {String} liantong_channel_id 联通通道ID
     * @apiParam (入参) {String} dianxin_channel_id 电信通道ID
     * @apiSuccess (返回) {String} code 200:成功 / 3001:有效任务template_id为空 / 3002:时间格式错误 / 3003:通道id 错误
     * @apiSampleRequest /admin/message/distributionOneSflSendTaskChannel
     * @return array
     * @author rzc
     */
    public function distributionOneSflSendTaskChannel() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $id         = trim($this->request->post('id'));
        $yidong_channel_id   = trim($this->request->post('yidong_channel_id'));
        $liantong_channel_id = trim($this->request->post('liantong_channel_id'));
        $dianxin_channel_id  = trim($this->request->post('dianxin_channel_id'));
        if (empty($yidong_channel_id) || intval($yidong_channel_id) < 1 || !is_numeric($yidong_channel_id)) {
            return ['code' => '3003'];
        }
        if (empty($liantong_channel_id) || intval($liantong_channel_id) < 1 || !is_numeric($liantong_channel_id)) {
            return ['code' => '3003'];
        }
        if (empty($dianxin_channel_id) || intval($dianxin_channel_id) < 1 || !is_numeric($dianxin_channel_id)) {
            return ['code' => '3003'];
        }
        $result = $this->app->message->distributionOneSflSendTaskChannel($id, intval($yidong_channel_id), intval($liantong_channel_id), intval($dianxin_channel_id));
        return $result;
    }

    /**
     * @api              {post} / 获取丝芙兰SFTP彩信任务
     * @apiDescription   getSflSendMulTask
     * @apiGroup         admin_Message
     * @apiName          getSflSendMulTask
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} [id] 任务id
     * @apiParam (入参) {String} [sfl_relation_id] 模板id
     * @apiParam (入参) {String} [mseeage_id] 丝芙兰彩信id
     * @apiParam (入参) {String} [mobile] 发送号码
     * @apiParam (入参) {String} [start_time] 开始时间
     * @apiParam (入参) {String} [end_time] 结束时间
     * @apiParam (入参) {String} page 页码 默认1
     * @apiParam (入参) {String} pageNum 条数 默认10
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id格式错误
     * @apiSampleRequest /admin/message/getSflSendMulTask
     * @return array
     * @author rzc
     */
    public function getSflSendMulTask() {
        $id              = trim($this->request->post('id'));
        $sfl_relation_id = trim($this->request->post('sfl_relation_id'));
        $mseeage_id      = trim($this->request->post('mseeage_id'));
        $mobile          = trim($this->request->post('mobile'));
        $page            = trim($this->request->post('page'));
        $pageNum         = trim($this->request->post('pageNum'));
        $cmsConId        = trim($this->request->post('cms_con_id'));
        $start_time      = trim($this->request->post('start_time'));
        $end_time        = trim($this->request->post('end_time'));
        if (!empty($start_time)) {
            if (strtotime($start_time) == false) {
                return ['code' => '3002'];
            }
        }
        if (!empty($end_time)) {
            if (strtotime($end_time) == false) {
                return ['code' => '3002'];
            }
        }
        $start_time = strtotime($start_time);
        $end_time   = strtotime($end_time);
        $page       = is_numeric($page) ? $page : 1;
        $pageNum    = is_numeric($pageNum) ? $pageNum : 10;
        intval($page);
        intval($pageNum);
        $result = $this->app->message->getSflSendMulTask($page, $pageNum, $id, $sfl_relation_id, $mseeage_id, $mobile, $start_time, $end_time);
        return $result;
    }

     /**
     * @api              {post} / 丝芙兰sftp彩信任务模板批量审核
     * @apiDescription   auditSflMulSendTask
     * @apiGroup         admin_Message
     * @apiName          auditSflMulSendTask
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} sfl_relation_id 任务id
     * @apiParam (入参) {String} start_time 开始时间
     * @apiParam (入参) {String} end_time 结束时间
     * @apiParam (入参) {String} free_trial 审核状态 2:审核通过;3:审核不通过
     * @apiSuccess (返回) {String} code 200:成功 / 3001:有效任务sfl_relation_id为空 / 3002:时间格式错误 / 3003:审核状态错误
     * @apiSampleRequest /admin/message/auditSflMulSendTask
     * @return array
     * @author rzc
     */
    public function auditSflMulSendTask() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $sfl_relation_id = trim($this->request->post('sfl_relation_id'));
        $start_time  = trim($this->request->post('start_time'));
        $end_time    = trim($this->request->post('end_time'));
        $free_trial  = trim($this->request->post('free_trial'));
        if (empty($sfl_relation_id)) {
            return ['code' => '3001'];
        }
        if (!in_array($free_trial, [2, 3])) {
            return ['code' => '3003'];
        }
        if (strtotime($start_time) == false || strtotime($end_time) == false) {
            return ['code' => '3002'];
        }
        $start_time = strtotime($start_time);
        $end_time   = strtotime($end_time);
        $result     = $this->app->message->auditSflMulSendTask($sfl_relation_id, $free_trial, $start_time, $end_time);
        return $result;
    }

     /**
     * @api              {post} / 丝芙兰sftp彩信任务模板批量分配通道
     * @apiDescription   distributionSflMulSendTaskChannel
     * @apiGroup         admin_Message
     * @apiName          distributionSflMulSendTaskChannel
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} sfl_relation_id 任务id
     * @apiParam (入参) {String} start_time 开始时间
     * @apiParam (入参) {String} end_time 结束时间
     * @apiParam (入参) {String} yidong_channel_id 移动通道ID
     * @apiParam (入参) {String} liantong_channel_id 联通通道ID
     * @apiParam (入参) {String} dianxin_channel_id 电信通道ID
     * @apiSuccess (返回) {String} code 200:成功 / 3001:有效任务sfl_relation_id为空 / 3002:时间格式错误 / 3003:通道id 错误
     * @apiSampleRequest /admin/message/distributionSflMulSendTaskChannel
     * @return array
     * @author rzc
     */
    public function distributionSflMulSendTaskChannel() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $sfl_relation_id         = trim($this->request->post('sfl_relation_id'));
        $start_time          = trim($this->request->post('start_time'));
        $end_time            = trim($this->request->post('end_time'));
        $yidong_channel_id   = trim($this->request->post('yidong_channel_id'));
        $liantong_channel_id = trim($this->request->post('liantong_channel_id'));
        $dianxin_channel_id  = trim($this->request->post('dianxin_channel_id'));
        if (empty($yidong_channel_id) || intval($yidong_channel_id) < 1 || !is_numeric($yidong_channel_id)) {
            return ['code' => '3003'];
        }
        if (empty($liantong_channel_id) || intval($liantong_channel_id) < 1 || !is_numeric($liantong_channel_id)) {
            return ['code' => '3003'];
        }
        if (empty($dianxin_channel_id) || intval($dianxin_channel_id) < 1 || !is_numeric($dianxin_channel_id)) {
            return ['code' => '3003'];
        }
        if (strtotime($start_time) == false || strtotime($end_time) == false) {
            return ['code' => '3002'];
        }
        $start_time = strtotime($start_time);
        $end_time   = strtotime($end_time);
        $result     = $this->app->message->distributionSflMulSendTaskChannel($sfl_relation_id, intval($yidong_channel_id), intval($liantong_channel_id), intval($dianxin_channel_id), $start_time, $end_time);
        return $result;
    }


    /**
     * @api              {post} / 丝芙兰sftp彩信任务单条审核
     * @apiDescription   auditOneSflSendTask
     * @apiGroup         admin_Message
     * @apiName          auditOneSflSendTask
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id 任务id
     * @apiParam (入参) {String} free_trial 审核状态 2:审核通过;3:审核不通过
     * @apiSuccess (返回) {String} code 200:成功 / 3001:有效任务ID为空 / 3002:时间格式错误 / 3003:审核状态错误
     * @apiSampleRequest /admin/message/auditOneSflSendTask
     * @return array
     * @author rzc
     */
    public function auditOneSflMulSendTask() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $free_trial = trim($this->request->post('free_trial'));
        $id         = trim($this->request->post('id'));
        if (!in_array($free_trial, [2, 3])) {
            return ['code' => '3003'];
        }
        $result = $this->app->message->auditOneSflMulSendTask($id, $free_trial);
        return $result;
    }

    /**
     * @api              {post} / 丝芙兰sftp彩信任务单条分配通道
     * @apiDescription   distributionOneSflSendTaskChannel
     * @apiGroup         admin_Message
     * @apiName          distributionOneSflSendTaskChannel
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id 任务id
     * @apiParam (入参) {String} yidong_channel_id 移动通道ID
     * @apiParam (入参) {String} liantong_channel_id 联通通道ID
     * @apiParam (入参) {String} dianxin_channel_id 电信通道ID
     * @apiSuccess (返回) {String} code 200:成功 / 3001:有效任务template_id为空 / 3002:时间格式错误 / 3003:通道id 错误
     * @apiSampleRequest /admin/message/distributionOneSflSendTaskChannel
     * @return array
     * @author rzc
     */
    public function distributionOneSflMulSendTaskChannel() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $id         = trim($this->request->post('id'));
        $yidong_channel_id   = trim($this->request->post('yidong_channel_id'));
        $liantong_channel_id = trim($this->request->post('liantong_channel_id'));
        $dianxin_channel_id  = trim($this->request->post('dianxin_channel_id'));
        if (empty($yidong_channel_id) || intval($yidong_channel_id) < 1 || !is_numeric($yidong_channel_id)) {
            return ['code' => '3003'];
        }
        if (empty($liantong_channel_id) || intval($liantong_channel_id) < 1 || !is_numeric($liantong_channel_id)) {
            return ['code' => '3003'];
        }
        if (empty($dianxin_channel_id) || intval($dianxin_channel_id) < 1 || !is_numeric($dianxin_channel_id)) {
            return ['code' => '3003'];
        }
        $result = $this->app->message->distributionOneSflMulSendTaskChannel($id, intval($yidong_channel_id), intval($liantong_channel_id), intval($dianxin_channel_id));
        return $result;
    }

    /**
     * @api              {post} / 空号检测接口
     * @apiDescription   numberDetection
     * @apiGroup         admin_Message
     * @apiName          numberDetection
     * @apiParam (入参) {String} phone 号码
     * @apiSuccess (返回) {String} code 200:成功  / 3000:用户名或密码错误
     * @apiSampleRequest /admin/message/numberDetection
     * @author rzc
     */
    public function numberDetection(){
        $mobile         = trim($this->request->post('mobile'));
       
        // return $this->encrypt($mobile, $secret_id);
        $result = $this->app->message->numberDetection($mobile);
        return $result;
    }

    /**
     * @api              {post} / 获取所有用户模板（视频短信）
     * @apiDescription   getUserSupMessageTemplate
     * @apiGroup         admin_Message
     * @apiName          getUserSupMessageTemplate
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id 任务id,多个用半角,分隔开,一次最多100
     * @apiParam (入参) {String} business_id 业务服务id(服务类型)
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id格式错误 / 3002:business_id格式错误 / 3003:business_id格式错误
     * @apiSampleRequest /admin/message/getUserSupMessageTemplate
     * @return array
     * @author rzc
     */
    public function getUserSupMessageTemplate() {
        $ConId   = trim($this->request->post('cms_con_id'));
        $page    = trim($this->request->post('page'));
        $pageNum = trim($this->request->post('pageNum'));
        $page    = is_numeric($page) ? $page : 1;
        $pageNum = is_numeric($pageNum) ? $pageNum : 10;
        intval($page);
        intval($pageNum);
        $result = $this->app->message->getUserSupMessageTemplate($page, $pageNum);
        return $result;
    }

    /**
     * @api              {post} / 审核用户彩信模板
     * @apiDescription   auditUserSupMessageTemplate
     * @apiGroup         admin_Message
     * @apiName          auditUserSupMessageTemplate
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id 模板Id
     * @apiParam (入参) {String} status 状态:2,审核通过;3,审核不通过;
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id格式错误 / 3002:审核状态码错误 /
     * @apiSampleRequest /admin/message/auditUserSupMessageTemplate
     * @return array
     * @author rzc
     */
    public function auditUserSupMessageTemplate() {
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
        if (!in_array($status, [2, 3])) {
            return ['code' => '3002'];
        }
        $result = $this->app->message->auditUserSupMessageTemplate(intval($id), $status);
        return $result;
    }

    /**
     * @api              {post} / 获取视频短信任务
     * @apiDescription   getSupMessageTask
     * @apiGroup         admin_Message
     * @apiName          getSupMessageTask
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id 任务id
     * @apiParam (入参) {String} free_trial 1:需要审核;2:审核通过;3:审核不通过
     * @apiParam (入参) {String} send_status 1：待发送,2:已发送
     * @apiParam (入参) {String} page 页码 默认1
     * @apiParam (入参) {String} pageNum 条数 默认10
     * @apiParam (入参) {String} [title] 任务名称
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id格式错误
     * @apiSampleRequest /admin/message/getSupMessageTask
     * @return array
     * @author rzc
     */
    public function getSupMessageTask() {
        $id       = trim($this->request->post('id'));
        $free_trial       = trim($this->request->post('free_trial'));
        $send_status       = trim($this->request->post('send_status'));
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('pageNum'));
        $cmsConId = trim($this->request->post('cms_con_id'));
        $title    = trim($this->request->post('title'));
        $page     = is_numeric($page) ? $page : 1;
        $pageNum  = is_numeric($pageNum) ? $pageNum : 10;
        $free_trial  = is_numeric($free_trial) ? $free_trial : 0;
        $send_status  = is_numeric($send_status) ? $send_status : 0;
        intval($page);
        intval($pageNum);
        intval($free_trial);
        intval($send_status);
        $result = $this->app->message->getSupMessageTask($page, $pageNum, $id, $title, $free_trial, $send_status);
        return $result;
    }

    /**
     * @api              {post} / 视频信任务审核
     * @apiDescription   auditSupMessageTask
     * @apiGroup         admin_Message
     * @apiName          auditSupMessageTask
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id 任务id,多个用半角,分隔开,一次最多100
     * @apiParam (入参) {String} free_trial 审核状态 2:审核通过;3:审核不通过
     * @apiSuccess (返回) {String} code 200:成功 / 3001:有效任务ID为空或者不能超过100个
     * @apiSampleRequest /admin/message/auditSupMessageTask
     * @return array
     * @author rzc
     */
    public function auditSupMessageTask() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $id           = trim($this->request->post('id'));
        $free_trial   = trim($this->request->post('free_trial'));
        $ids          = explode(',', $id);
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
        $result = $this->app->message->auditSupMessageTask($effective_id, $free_trial);
        return $result;
    }

    /**
     * @api              {post} / 分配任务通道
     * @apiDescription   distributionSupMessageChannel
     * @apiGroup         admin_Message
     * @apiName          distributionSupMessageChannel
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id 任务id,多个用半角,分隔开,一次最多100
     * @apiParam (入参) {String} business_id 业务服务id
     * @apiParam (入参) {String} yidong_channel_id 移动通道ID
     * @apiParam (入参) {String} liantong_channel_id 联通通道ID
     * @apiParam (入参) {String} dianxin_channel_id 电信通道ID
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id格式错误 / 3002:channel_id格式错误 / 3003:business_id格式错误
     * @apiSampleRequest /admin/message/distributionSupMessageChannel
     * @return array
     * @author rzc
     */
    public function distributionSupMessageChannel() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $id                  = trim($this->request->post('id'));
        $channel_id          = trim($this->request->post('channel_id'));
        $business_id         = trim($this->request->post('business_id'));
        $yidong_channel_id   = trim($this->request->post('yidong_channel_id'));
        $liantong_channel_id = trim($this->request->post('liantong_channel_id'));
        $dianxin_channel_id  = trim($this->request->post('dianxin_channel_id'));
        $ids                 = explode(',', $id);
        $effective_id        = [];
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
        $result = $this->app->message->distributionSupMessageChannel($effective_id, intval($yidong_channel_id), intval($liantong_channel_id), intval($dianxin_channel_id), intval($business_id));
        return $result;
    }
}
