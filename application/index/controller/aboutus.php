<?php
namespace app\index\controller;
use app\index\MyController;

class Aboutus extends MyController {

    /**
     * @api              {post} / 关于我们
     * @apiDescription   getAboutus
     * @apiGroup         index_Aboutus
     * @apiName          getAboutus
     * @apiParam (入参) {Number} [aboutus_id] 对应商品id
     * @apiParam (入参) {String} page 页码
     * @apiParam (入参) {String} pageNum 条数
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3002.type参数错误 / 3003.pid参数错误
     * @apiSampleRequest /index/aboutus/getAboutus
     * @author rzc
     */
    public function getAboutus() {
        $apiName  = classBasename($this) . '/' . __function__;
        $aboutus_id = trim($this->request->post('aboutus_id'));
        $page       = trim($this->request->post('page'));
        $pageNum    = trim($this->request->post('pageNum'));
        $page       = is_numeric($page) ? $page : 1;
        $pageNum    = is_numeric($pageNum) ? $pageNum : 10;
        $result   = $this->app->aboutus->getAboutus($page, $pageNum,$aboutus_id);
        // $this->apiLog($apiName, [$aboutus_id, $source], $result['code'], '');
        return $result;
    }

}
