<?php

namespace app\supadmin\controller;

use app\supadmin\SupAdminController;

class Upload extends SupAdminController {
    protected $beforeActionList = [
        'isLogin', //所有方法的前置操作
//        'isLogin' => ['except' => 'login'],//除去login其他方法都进行isLogin前置操作
//        'three'   => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 上传单个图片
     * @apiDescription   uploadFile
     * @apiGroup         supadmin_upload
     * @apiName          uploadFilee
     * @apiParam (入参) {String} sup_con_id
     * @apiParam (入参) {file} image 图片
     * @apiSuccess (返回) {String} code 200:成功  / 3001:上传的不是图片 / 3002:上传图片不能超过2M / 3003:上传失败 / 3004:上传文件不能为空
     * @apiSuccess (data) {Array} data 结果
     * @apiSampleRequest /supadmin/upload/uploadfile
     * @author zyr
     */
    public function uploadFile() {
//        $apiName  = classBasename($this) . '/' . __function__;
//        $supConId = trim($this->request->post('sup_con_id'));
        $image    = $this->request->file('image');
        if (empty($image)) {
            return ['code' => '3004'];
        }
        $fileInfo = $image->getInfo();
        $fileType = explode('/', $fileInfo['type']);
        if ($fileType[0] != 'image') {
            return ['3001'];//上传的不是图片
        }
        if ($fileInfo['size'] > 1024 * 1024 * 2) {
            return ['3002'];//上传图片不能超过2M
        }
        $result = $this->app->upload->uploadFile($fileInfo);
//        $this->apiLog($apiName, [$cmsConId, $image], $result['code'], $cmsConId);
        return $result;
    }
}