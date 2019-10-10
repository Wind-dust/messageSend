<?php
namespace app\index\controller;
use app\index\MyController;

class Product extends MyController {

    /**
     * @api              {post} / 产品中心
     * @apiDescription   getProduct
     * @apiGroup         index_Product
     * @apiName          getProduct
     * @apiParam (入参) {Number} [product_id] 对应商品id
     * @apiParam (入参) {String} page 页码
     * @apiParam (入参) {String} pageNum 条数
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3002.type参数错误 / 3003.pid参数错误
     * @apiSampleRequest /index/product/getProduct
     * @author rzc
     */
    public function getProduct() {
        $apiName  = classBasename($this) . '/' . __function__;
        $product_id = trim($this->request->post('product_id'));
        $page       = trim($this->request->post('page'));
        $pageNum    = trim($this->request->post('pageNum'));
        $page       = is_numeric($page) ? $page : 1;
        $pageNum    = is_numeric($pageNum) ? $pageNum : 10;
        $result   = $this->app->product->getProduct($page, $pageNum,$product_id);
        // $this->apiLog($apiName, [$Product_id, $source], $result['code'], '');
        return $result;
    }

}
