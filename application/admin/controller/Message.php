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
    public function auditMultimediaMessageTask(){
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $id = trim($this->request->post('id'));
        $free_trial = trim($this->request->post('free_trial'));
        $ids = explode(',',$id);
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
        
        if (!in_array($free_trial,[2,3])) {
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
     * @apiParam (入参) {String} channel_id 通道ID
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id格式错误 / 3002:channel_id格式错误 / 3003:business_id格式错误
     * @apiSampleRequest /admin/message/distributionMultimediaChannel
     * @return array
     * @author rzc
     */
    public function distributionMultimediaChannel(){
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $id = trim($this->request->post('id'));
        $channel_id = trim($this->request->post('channel_id'));
        $business_id = trim($this->request->post('business_id'));
       
        $ids = explode(',',$id);
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
        $result =  $this->app->message->distributionMultimediaChannel($effective_id, intval($channel_id), intval($business_id));
        return $result;
    }


    /**
     * @api              {get} / 导出回执报告
     * @apiDescription   exportReceiptReport
     * @apiGroup         admin_Message
     * @apiName          exportReceiptReport
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id 任务id,多个用半角,分隔开,一次最多100
     * @apiParam (入参) {String} business_id 业务服务id(服务类型)
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id格式错误 / 3002:business_id格式错误 / 3003:business_id格式错误
     * @apiSampleRequest /admin/message/exportReceiptReport
     * @return array
     * @author rzc
     */
    public function exportReceiptReport(){
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
        $result =  $this->app->message->exportReceiptReport( intval($id), intval($business_id));
        return $result;
    }
}
