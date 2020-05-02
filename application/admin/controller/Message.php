<?php

namespace app\admin\controller;

use app\admin\AdminController;
use think\Controller;

class Message extends AdminController
{
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
     * @apiParam (入参) {String} page 页码 默认1
     * @apiParam (入参) {String} pageNum 条数 默认10
     * @apiParam (入参) {String} [title] 任务名称
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id格式错误 
     * @apiSampleRequest /admin/message/getMultimediaMessageTask
     * @return array
     * @author rzc
     */
    public function getMultimediaMessageTask()
    {
        $id       = trim($this->request->post('id'));
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('pageNum'));
        $cmsConId = trim($this->request->post('cms_con_id'));
        $title    = trim($this->request->post('title'));
        $page     = is_numeric($page) ? $page : 1;
        $pageNum  = is_numeric($pageNum) ? $pageNum : 10;
        intval($page);
        intval($pageNum);
        $result = $this->app->message->getMultimediaMessageTask($page, $pageNum, $id, $title);
        return $result;
    }

    /**
     * @api              {post} / 营销任务审核
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
    public function auditMultimediaMessageTask()
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
        $result =  $this->app->message->auditMultimediaMessageTask($effective_id, $free_trial);
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
    public function distributionMultimediaChannel()
    {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $id = trim($this->request->post('id'));
        $channel_id = trim($this->request->post('channel_id'));
        $business_id = trim($this->request->post('business_id'));
        $yidong_channel_id = trim($this->request->post('yidong_channel_id'));
        $liantong_channel_id = trim($this->request->post('liantong_channel_id'));
        $dianxin_channel_id = trim($this->request->post('dianxin_channel_id'));
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
        $result =  $this->app->message->distributionMultimediaChannel($effective_id, intval($yidong_channel_id), intval($liantong_channel_id), intval($dianxin_channel_id), intval($business_id));
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
    public function exportReceiptReport()
    {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->get('cms_con_id'));
        // if ($this->checkPermissions($cmsConId, $apiName) === false) {
        //     return ['code' => '3100'];
        // }
        $id = trim($this->request->get('id'));
        $business_id = trim($this->request->get('business_id'));
        if (empty($id) || intval($id) < 1 || !is_numeric($id)) {
            return ['code' => '3001'];
        }
        if (empty($business_id) || intval($business_id) < 1 || !is_numeric($business_id)) {
            return ['code' => '3002'];
        }
        $result =  $this->app->message->exportReceiptReport(intval($id), intval($business_id));
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
    public function exportMultimediaReceiptReport()
    {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->get('cms_con_id'));
        // if ($this->checkPermissions($cmsConId, $apiName) === false) {
        //     return ['code' => '3100'];
        // }
        $id = trim($this->request->get('id'));
        if (empty($id) || intval($id) < 1 || !is_numeric($id)) {
            return ['code' => '3001'];
        }
        $result =  $this->app->message->exportMultimediaReceiptReport(intval($id));
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
    public function getUserModel()
    {
        $ConId = trim($this->request->post('cms_con_id'));
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('pageNum'));
        $page     = is_numeric($page) ? $page : 1;
        $pageNum  = is_numeric($pageNum) ? $pageNum : 10;
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
    public function auditUserModel()
    {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $id = trim($this->request->post('id'));
        $status = trim($this->request->post('status'));
        if (empty($id) || intval($id) < 1 || !is_numeric($id)) {
            return ['code' => '3001'];
        }
        if (!in_array($status, [3, 4, 5])) {
            return ['code' => '3002'];
        }
        $result =  $this->app->message->auditUserModel(intval($id), $status);
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
    public function getUserSignature()
    {
        $ConId = trim($this->request->post('cms_con_id'));
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('pageNum'));
        $page     = is_numeric($page) ? $page : 1;
        $pageNum  = is_numeric($pageNum) ? $pageNum : 10;
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
    public function auditUserSignature()
    {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $id = trim($this->request->post('id'));
        $status = trim($this->request->post('status'));
        if (empty($id) || intval($id) < 1 || !is_numeric($id)) {
            return ['code' => '3001'];
        }
        if (!in_array($status, [2, 3])) {
            return ['code' => '3002'];
        }
        $result =  $this->app->message->auditUserSignature(intval($id), $status);
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
    public function getDevelopCode()
    {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $no_lenth = trim($this->request->post('no_lenth'));
        $develop_no = trim($this->request->post('develop_no'));
        $is_bind = trim($this->request->post('is_bind'));
        $pageNum  = trim($this->request->post('pageNum'));
        $page     = trim($this->request->post('page'));
        $page     = is_numeric($page) ? $page : 1;
        $pageNum  = is_numeric($pageNum) ? $pageNum : 10;
        intval($page);
        intval($pageNum);
        if (!empty($no_lenth) && (intval($no_lenth) < 2 || !is_numeric($no_lenth) || intval($no_lenth) > 6)) {
            return ['code' => '3001'];
        }
        strval($develop_no);
        if (!empty($is_bind) && !in_array($is_bind, [1, 2])) {
            return ['code' => '3002'];
        }
        $result =  $this->app->message->getDevelopCode($page, $pageNum, $no_lenth, $develop_no, intval($is_bind));
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
    public function getOneRandomDevelopCode()
    {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $no_lenth = trim($this->request->post('no_lenth'));
        if (!empty($no_lenth) && (intval($no_lenth) < 2 || !is_numeric($no_lenth) || intval($no_lenth) > 6)) {
            return ['code' => '3001'];
        }
        $result =  $this->app->message->getOneRandomDevelopCode($no_lenth);
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
    public function verifyDevelopCode()
    {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $develop_no = trim($this->request->post('develop_no'));
        if (!empty($develop_no) && (strlen(intval($develop_no)) < 2 || !is_numeric($develop_no) || strlen(intval($develop_no)) > 6)) {
            return ['code' => '3001'];
        }
        $result =  $this->app->message->verifyDevelopCode($develop_no);
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
    public function userBindDevelopCode()
    {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $develop_no = trim($this->request->post('develop_no'));
        $nick_name = trim($this->request->post('nick_name'));

        $business_id = trim($this->request->post('business_id'));
        $source = trim($this->request->post('source'));
        if (!empty($develop_no) && (strlen(intval($develop_no)) < 2 || !is_numeric($develop_no) || strlen(intval($develop_no)) > 6)) {
            return ['code' => '3001'];
        }
        if (!in_array($source, [1, 2, 3, 4, 5, 6, 7])) {
            return ['code' => '3002'];
        }
        $result =  $this->app->message->userBindDevelopCode($develop_no, $nick_name, $business_id, $source);
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
    public function getuserBindDevelopCode()
    {
        $develop_no = trim($this->request->post('develop_no'));
        if (!empty($develop_no) && (strlen(intval($develop_no)) < 2 || !is_numeric($develop_no) || strlen(intval($develop_no)) > 6)) {
            return ['code' => '3001'];
        }
        $result =  $this->app->message->getuserBindDevelopCode($develop_no);
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
    public function deluserBindDevelopCode()
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
        $result =  $this->app->message->deluserBindDevelopCode($id);
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
    public function getUserMultimediaTemplate()
    {
        $ConId = trim($this->request->post('cms_con_id'));
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('pageNum'));
        $page     = is_numeric($page) ? $page : 1;
        $pageNum  = is_numeric($pageNum) ? $pageNum : 10;
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
    public function auditUserMultimediaTemplatel()
    {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $id = trim($this->request->post('id'));
        $status = trim($this->request->post('status'));
        if (empty($id) || intval($id) < 1 || !is_numeric($id)) {
            return ['code' => '3001'];
        }
        if (!in_array($status, [2, 3])) {
            return ['code' => '3002'];
        }
        $result =  $this->app->message->auditUserMultimediaTemplatel(intval($id), $status);
        return $result;
    }
}
