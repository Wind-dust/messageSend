<?php

namespace app\index\controller;
use app\index\MyController;
use Config;
use Env;

class Provinces extends MyController {
    protected $beforeActionList = [
        // 'isLogin', //所有方法的前置操作
//        'isLogin' => ['except' => 'login'],//除去login其他方法都进行isLogin前置操作
//        'three'   => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 省市列表
     * @apiDescription   getProvinceCity
     * @apiGroup         index_provinces
     * @apiName          getProvinceCity
     * @apiSuccess (返回) {String} code 200:成功 / 3000:省市区列表为空
     * @apiSuccess (返回) {Array} data 结果
     * @apiSuccess (data) {String} area_name 名称
     * @apiSuccess (data) {Number} pid 父级id
     * @apiSampleRequest /index/provinces/getProvinceCity
     * @author zyr
     */
    public function getProvinceCity() {
        $apiName  = classBasename($this) . '/' . __function__;
        $result = $this->app->provinces->getProvinceCity();
        // $this->apiLog($apiName, [$cmsConId], $result['code'], $cmsConId);
//        $this->addLog($result['code'],__function__);//接口请求日志
        return $result;
    }

    /**
     * @api              {post} / 获取市级列表
     * @apiDescription   getCity
     * @apiGroup         index_provinces
     * @apiName          getCity
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} provinceId 省级id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:市列表空 / 3001:省级id不存在 / 3002:省级id只能是数字
     * @apiSuccess (返回) {Array} data 结果
     * @apiSuccess (data) {String} area_name 名称
     * @apiSuccess (data) {Number} pid 父级id
     * @apiSampleRequest /index/provinces/getCity
     * @author zyr
     */
    public function getCity() {
        $apiName    = classBasename($this) . '/' . __function__;
        $provinceId = trim($this->request->post('provinceId'));
        if (!is_numeric($provinceId)) {
            return ['3002'];
        }
        $provinceId = intval($provinceId);
        $result     = $this->app->provinces->getArea($provinceId, 2);
        // $this->apiLog($apiName, [$cmsConId, $provinceId], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 获取区级列表
     * @apiDescription   getArea
     * @apiGroup         index_provinces
     * @apiName          getArea
     * @apiParam (入参) {Number} cityId 市级id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:区列表空 / 3001:市级id不存在 / 3002:市级id只能是数字
     * @apiSuccess (返回) {String} data 结果
     * @apiSuccess (data) {String} area_name 名称
     * @apiSuccess (data) {Number} pid 父级id
     * @apiSampleRequest /index/provinces/getArea
     * @author zyr
     */
    public function getArea() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        $cityId   = trim($this->request->post('cityId'));
        if (!is_numeric($cityId)) {
            return ['3002'];
        }
        $cityId = intval($cityId);
        $result = $this->app->provinces->getArea($cityId, 3);
        // $this->apiLog($apiName, [$cmsConId, $cityId], $result['code'], $cmsConId);
        return $result;
    }

}