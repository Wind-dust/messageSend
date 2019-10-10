<?php
namespace app\index\controller;
use app\index\MyController;

class ApplicationCase extends MyController {

    /**
     * @api              {post} / 应用案例
     * @apiDescription   getApplicationCase
     * @apiGroup         index_ApplicationCase
     * @apiName          getApplicationCase
     * @apiParam (入参) {Number} [applicationCase_id] 对应商品id
     * @apiParam (入参) {String} page 页码
     * @apiParam (入参) {String} pageNum 条数
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3002.type参数错误 / 3003.pid参数错误
     * @apiSampleRequest /index/applicationCase/getApplicationCase
     * @author rzc
     */
    public function getApplicationCase() {
        $apiName  = classBasename($this) . '/' . __function__;
        $applicationCase_id = trim($this->request->post('applicationCase_id'));
        $page       = trim($this->request->post('page'));
        $pageNum    = trim($this->request->post('pageNum'));
        $page       = is_numeric($page) ? $page : 1;
        $pageNum    = is_numeric($pageNum) ? $pageNum : 10;
        $result   = $this->app->applicationCase->getApplicationCase($page, $pageNum,$applicationCase_id);
        // $this->apiLog($apiName, [$applicationCase_id, $source], $result['code'], '');
        return $result;
    }

}
