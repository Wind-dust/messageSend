<?php
namespace app\index\controller;
use app\index\MyController;

class Solution extends MyController {

    /**
     * @api              {post} / 解决方案
     * @apiDescription   getSolution
     * @apiGroup         index_Solution
     * @apiName          getSolution
     * @apiParam (入参) {Number} [solution_id] 对应商品id
     * @apiParam (入参) {String} page 页码
     * @apiParam (入参) {String} pageNum 条数
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3002.type参数错误 / 3003.pid参数错误
     * @apiSampleRequest /index/solution/getSolution
     * @author rzc
     */
    public function getSolution() {
        $apiName  = classBasename($this) . '/' . __function__;
        $solution_id = trim($this->request->post('solution_id'));
        $page       = trim($this->request->post('page'));
        $pageNum    = trim($this->request->post('pageNum'));
        $page       = is_numeric($page) ? $page : 1;
        $pageNum    = is_numeric($pageNum) ? $pageNum : 10;
        $result   = $this->app->solution->getSolution($page, $pageNum,$solution_id);
        // $this->apiLog($apiName, [$Solution_id, $source], $result['code'], '');
        return $result;
    }

}
