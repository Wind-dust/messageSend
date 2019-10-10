<?php

namespace app\supadmin\controller;

use app\supadmin\SupAdminController;

class Promote extends SupAdminController {
    protected $beforeActionList = [
//        'isLogin', //所有方法的前置操作
        'isLogin' => ['except' => 'login'], //除去login其他方法都进行isLogin前置操作
        //        'three'   => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 推广活动报名列表
     * @apiDescription   getSupPromoteSignUp
     * @apiGroup         supadmin_promote
     * @apiName          getSupPromoteSignUp
     * @apiParam (入参) {String} sup_con_id
     * @apiParam (入参) {String} promote_id 活动ID
     * @apiParam (入参) {String} page 页数
     * @apiParam (入参) {String} [page_num] 每页条数(默认10)
     * @apiParam (入参) {String} [study_name] 姓名
     * @apiParam (入参) {String} [study_mobile] 电话
     * @apiParam (入参) {String} [sex] 性别 1男 2女
     * @apiParam (入参) {String} [start_time] 开始时间
     * @apiParam (入参) {String} [end_time] 结束时间
     * @apiSuccess (返回) {String} code 200:成功 / 3000:列表为空 / 3001:page错误 / 3002:promote_id错误 / 3003:时间格式错误 / 3004:性别格式错误
     * @apiSuccess (返回) {Array} data
     * @apiSuccess (data) {Int} id
     * @apiSuccess (data) {String} study_name 学员姓名
     * @apiSuccess (data) {String} study_mobile 学员手机号
     * @apiSuccess (data) {String} sex 性别 1男 2女
     * @apiSuccess (data) {String} age 年龄
     * @apiSuccess (data) {String} signinfo 报名内容
     * @apiSuccess (data) {String} create_time 报名时间
     * @apiSampleRequest /supadmin/promote/getSupPromoteSignUp
     * @return array
     * @author rzc
     */
    public function getSupPromoteSignUp() {
        $apiName      = classBasename($this) . '/' . __function__;
        $supConId     = trim($this->request->post('sup_con_id'));
        $promote_id   = trim($this->request->post('promote_id'));
        $page         = trim($this->request->post('page'));
        $pageNum      = trim($this->request->post('page_num'));
        $study_name   = trim($this->request->post('study_name'));
        $study_mobile = trim($this->request->post('study_mobile'));
        $sex          = trim($this->request->post('sex'));
        $start_time   = trim($this->request->post('start_time'));
        $end_time     = trim($this->request->post('end_time'));
        if (!is_numeric($page) || $page < 1) {
            return ['code' => '3001']; //page错误
        }
        if (!is_numeric($pageNum) || $pageNum < 1) {
            $pageNum = 10;
        }
        if (!is_numeric($promote_id) || $promote_id < 1) {
            return ['code' => 3002];
        }
        $page    = intval($page);
        $pageNum = intval($pageNum);
        $preg    = '/^([12]\d\d\d)-(0?[1-9]|1[0-2])-(0?[1-9]|[12]\d|3[0-1]) ([0-1]\d|2[0-4]):([0-5]\d)(:[0-5]\d)?$/';
        if (!empty($start_time)) {
            if (preg_match($preg, $start_time, $parts1)) {
                if (checkdate($parts1[2], $parts1[3], $parts1[1]) == false) {
                    return ['code' => '3003'];
                }
            } else {
                return ['code' => '3003'];
            }
            $start_time = strtotime($start_time);
        }
        if (!empty($end_time)) {
            if (preg_match($preg, $end_time, $parts2)) {
                if (checkdate($parts2[2], $parts2[3], $parts2[1]) == false) {
                    return ['code' => '3003'];
                }
            } else {
                return ['code' => '3003'];
            }
            $end_time = strtotime($end_time);
        }
        if (!empty($sex)) {
            if (!in_array($sex,[1,2])) {
                return ['code' => 3004];
            }
        }
        $result = $this->app->promote->getSupPromoteSignUp($promote_id, $page, $pageNum, $study_name, $study_mobile, $start_time, $end_time, $sex);
//        $this->apiLog($apiName, [$adminName, $passwd], $result['code'], '');
        return $result;
    }

    /**
     * @api              {post} / 提交活动详情和轮播图
     * @apiDescription   uploadPromoteImages
     * @apiGroup         supadmin_promote
     * @apiName          uploadPromoteImages
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} image_type 图片类型 1.详情图 2.轮播图
     * @apiParam (入参) {Number} promote_id 活动id
     * @apiParam (入参) {Array} images 图片集合
     * @apiSuccess (返回) {String} code 200:成功 / 3001:图片类型有误 / 3002:商品id只能是数字 / 3003:图片不能空 / 3004:商品id不存在 / 3005:图片没有上传过 / 3006:上传失败
     * @apiSampleRequest /supadmin/promote/uploadPromoteImages
     * @return array
     * @author RZC
     */
    public function uploadPromoteImages() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        // if ($this->checkPermissions($cmsConId, $apiName) === false) {
        //     return ['code' => '3100'];
        // }
        $imageTypeArr = [1, 2]; //1.详情图 2.轮播图
        $promote_id   = trim($this->request->post('promote_id'));
        $imageType    = trim($this->request->post('image_type'));
        $images       = $this->request->post('images');
        if (!is_numeric($imageType) || !in_array(intval($imageType), $imageTypeArr)) {
            return ['code' => '3001']; //图片类型有误
        }
        if (!is_numeric($promote_id)) {
            return ['code' => '3002']; //商品id只能是数字
        }
        if (empty($images)) {
            return ['code' => '3003']; //图片不能空
        }
        $result = $this->app->promote->uploadPromoteImages($promote_id, $imageType, $images);
        // $this->apiLog($apiName, [$cmsConId, $promote_id, $imageType, $images], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 删除商品详情和轮播图
     * @apiDescription   delPromoteImage
     * @apiGroup         supadmin_promote
     * @apiName          delPromoteImage
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} image_path 商品id
     * @apiSuccess (返回) {String} code 200:成功 / 3001:图片不能为空 / 3002:图片不存在 / 3003:上传失败
     * @apiSampleRequest /supadmin/promote/delPromoteImage
     * @return array
     * @author rzc
     */
    public function delPromoteImage() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        // if ($this->checkPermissions($cmsConId, $apiName) === false) {
        //     return ['code' => '3100'];
        // }
        $imagePath = trim($this->request->post('image_path'));
        if (empty($imagePath)) {
            return ['code' => '3001']; //图片不能为空
        }
        $result = $this->app->promote->delPromoteImage($imagePath);
        // $this->apiLog($apiName, [$cmsConId, $imagePath], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 对商品图进行排序
     * @apiDescription   sortPromoteimagedetail
     * @apiGroup         supadmin_promote
     * @apiName          sortImageDetail
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} image_path 商品id
     * @apiParam (入参) {Number} order_by 排序
     * @apiSuccess (返回) {String} code 200:成功 / 3001:图片不能为空 / 3002:图片不存在 / 3003:排序字段只能为数字或者排序最大为999 / 3004:修改失败
     * @apiSampleRequest /supadmin/promote/sortPromoteimagedetail
     * @return array
     * @author rzc
     */
    public function sortPromoteimagedetail() {
        $apiName   = classBasename($this) . '/' . __function__;
        $cmsConId  = trim($this->request->post('cms_con_id')); //操作管理员
        $imagePath = trim($this->request->post('image_path'));
        $orderBy   = trim($this->request->post('order_by'));
        if (empty($imagePath)) {
            return ['code' => '3001']; //图片不能为空
        }
        if (!is_numeric($orderBy)) {
            return ['code' => '3003']; //排序字段只能为数字
        }
        $result = $this->app->promote->sortPromoteimagedetail($imagePath, intval($orderBy));
        // $this->apiLog($apiName, [$cmsConId, $imagePath, $orderBy], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 活动图片详情
     * @apiDescription   getPromoteimagedetail
     * @apiGroup         supadmin_promote
     * @apiName          getPromoteimagedetail
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} promote_id 活动id
     * @apiSuccess (返回) {String} code 200:成功 / 3002:promote_id不存在
     * @apiSampleRequest /supadmin/promote/getPromoteimagedetail
     * @return array
     * @author rzc
     */
    public function getPromoteimagedetail() {
        $promote_id = trim($this->request->post('promote_id'));
        if (!is_numeric($promote_id) || $promote_id < 1) {
            return ['code' => 3002];
        }
        $result = $this->app->promote->getPromoteimagedetail($promote_id);
        return $result;
    }
}
