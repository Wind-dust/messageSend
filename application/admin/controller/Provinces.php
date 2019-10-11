<?php

namespace app\admin\controller;

use app\admin\AdminController;
use Config;
use Env;

class Provinces extends AdminController {
    protected $beforeActionList = [
        'isLogin', //所有方法的前置操作
//        'isLogin' => ['except' => 'login'],//除去login其他方法都进行isLogin前置操作
//        'three'   => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 省市列表
     * @apiDescription   getProvinceCity
     * @apiGroup         admin_provinces
     * @apiName          getProvinceCity
     * @apiParam (入参) {String} cms_con_id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:省市区列表为空
     * @apiSuccess (返回) {Array} data 结果
     * @apiSuccess (data) {String} area_name 名称
     * @apiSuccess (data) {Number} pid 父级id
     * @apiSampleRequest /admin/provinces/getProvinceCity
     * @author zyr
     */
    public function getProvinceCity() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        $result = $this->app->provinces->getProvinceCity();
        $this->apiLog($apiName, [$cmsConId], $result['code'], $cmsConId);
//        $this->addLog($result['code'],__function__);//接口请求日志
        return $result;
    }

    /**
     * @api              {post} / 获取市级列表
     * @apiDescription   getCity
     * @apiGroup         admin_provinces
     * @apiName          getCity
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} provinceId 省级id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:市列表空 / 3001:省级id不存在 / 3002:省级id只能是数字
     * @apiSuccess (返回) {Array} data 结果
     * @apiSuccess (data) {String} area_name 名称
     * @apiSuccess (data) {Number} pid 父级id
     * @apiSampleRequest /admin/provinces/getCity
     * @author zyr
     */
    public function getCity() {
        $apiName    = classBasename($this) . '/' . __function__;
        $cmsConId   = trim($this->request->post('cms_con_id'));
        $provinceId = trim($this->request->post('provinceId'));
        if (!is_numeric($provinceId)) {
            return ['3002'];
        }
        $provinceId = intval($provinceId);
        $result     = $this->app->provinces->getArea($provinceId, 2);
        $this->apiLog($apiName, [$cmsConId, $provinceId], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 获取区级列表
     * @apiDescription   getArea
     * @apiGroup         admin_provinces
     * @apiName          getArea
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} cityId 市级id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:区列表空 / 3001:市级id不存在 / 3002:市级id只能是数字
     * @apiSuccess (返回) {String} data 结果
     * @apiSuccess (data) {String} area_name 名称
     * @apiSuccess (data) {Number} pid 父级id
     * @apiSampleRequest /admin/provinces/getArea
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
        $this->apiLog($apiName, [$cmsConId, $cityId], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 获取运费模版的剩余可选省市列表
     * @apiDescription   getProvinceCityByfreight
     * @apiGroup         admin_provinces
     * @apiName          getProvinceCityByfreight
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} freight_id 运费模版详情id
     * @apiParam (入参) {Number} freight_detail_id 运费模版价格详情id
     * @apiSuccess (返回) {String} code 200:成功 / 3001:运费模版Id必须为数字 / 3002:运费模版价格详情id必须为数字
     * @apiSuccess (返回) {Array} data 结果
     * @apiSuccess (data) {String} area_name 名称
     * @apiSuccess (data) {Number} pid 父级id
     * @apiSuccess (data) {Number} status 1.可选的 2.已选的
     * @apiSampleRequest /admin/provinces/getprovincecitybyfreight
     * @author zyr
     */
    public function getProvinceCityByFreight() {
        $apiName   = classBasename($this) . '/' . __function__;
        $cmsConId  = trim($this->request->post('cms_con_id'));
        $freightId = $this->request->post('freight_id');
        if (!is_numeric($freightId)) {
            return ['code' => '3001'];
        }
        $freightDetailId = $this->request->post('freight_detail_id');
        if (!is_numeric($freightDetailId)) {
            return ['code' => '3002'];
        }
        $result = $this->app->provinces->getProvinceCityByFreight(intval($freightId), intval($freightDetailId));
        $this->apiLog($apiName, [$cmsConId, $freightId, $freightDetailId], $result['code'], $cmsConId);
        return $result;
    }
}