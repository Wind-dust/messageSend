<?php
namespace app\index\controller;
use app\index\MyController;

class DownloadCenter extends MyController {

    /**
     * @api              {post} / 下载中心
     * @apiDescription   getDownloadCenter
     * @apiGroup         index_DownloadCenter
     * @apiName          getDownloadCenter
     * @apiParam (入参) {Number} [downloadCenter_id] 对应商品id
     * @apiParam (入参) {String} page 页码
     * @apiParam (入参) {String} pageNum 条数
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3002.type参数错误 / 3003.pid参数错误
     * @apiSampleRequest /index/downloadCenter/getDownloadCenter
     * @author rzc
     */
    public function getDownloadCenter() {
        $apiName  = classBasename($this) . '/' . __function__;
        $downloadCenter_id = trim($this->request->post('downloadCenter_id'));
        $page       = trim($this->request->post('page'));
        $pageNum    = trim($this->request->post('pageNum'));
        $page       = is_numeric($page) ? $page : 1;
        $pageNum    = is_numeric($pageNum) ? $pageNum : 10;
        $result   = $this->app->downloadCenter->getDownloadCenter($page, $pageNum,$downloadCenter_id);
        // $this->apiLog($apiName, [$downloadCenter_id, $source], $result['code'], '');
        return $result;
    }

}
