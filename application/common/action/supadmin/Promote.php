<?php

namespace app\common\action\supadmin;

use app\facade\DbGoods;
use app\facade\DbImage;
use app\facade\DbSup;
use Config;
use think\Db;

class Promote extends CommonIndex {

    /**
     * 报名列表
     * @param $promote_id
     * @param $page
     * @param $pageNum
     * @param $nick_name
     * @param $mobile
     * @param $start_time
     * @param $end_time
     * @return array
     * @author zyr
     */
    public function getSupPromoteSignUp($promote_id, $page, $pageNum, $study_name = '', $study_mobile = '', $start_time = '', $end_time = '', $sex = '') {
        $where = [];
        array_push($where, [['id', '=', $promote_id]]);

        $promote = DbSup::getSupPromote($where, 'id', true);
        if (empty($promote)) {
            return ['code' => '3002']; //推广活动不存在
        }
        $offset = ($page - 1) * $pageNum;
        if ($offset < 0) {
            return ['code' => '200', 'suppromotesignup' => []];
        }
        $where = [];
        array_push($where, [['promote_id', '=', $promote_id]]);
        if (!empty($study_name)) {
            array_push($where, [['study_name', 'LIKE', '%' . $study_name . '%']]);
        }
        if (!empty($study_mobile)) {
            array_push($where, [['study_mobile', 'LIKE', '%' . $study_mobile . '%']]);
        }
        if (!empty($start_time)) {
            array_push($where, [['create_time', '>=', $start_time]]);
        }
        if (!empty($end_time)) {
            array_push($where, [['create_time', '<=', $end_time]]);
        }
        if (!empty($sex)) {
            array_push($where, [['sex', '=', $sex]]);
        }
        $result = DbSup::getSupPromoteSignUp($where, 'id,study_name,study_mobile,sex,age,signinfo,create_time', false, ['create_time' => 'ASC'], $offset . ',' . $pageNum);
        $total  = DbSup::getSupPromoteSignUpCount($where);
        return ['code' => '200','total' => $total, 'suppromotesignup' => $result];
    }

     /**
     * 上传商品的轮播图和详情图
     * @param $promote_id
     * @param $imageType 1.详情图 2.轮播图
     * @param $images
     * @return array
     */
    public function uploadPromoteImages($promote_id, $imageType, $images) {
        $goods = DbSup::getSupPromote(['id' => $promote_id], 'id', true);
        if (empty($goods)) {
            return ['code' => '3004'];
        }
        $data    = [];
        $logData = [];
        $orderBy = 0;
        foreach ($images as $img) {
            $image    = filtraImage(Config::get('qiniu.domain'), $img);//去除域名
            $logImage = DbImage::getLogImage($image, 2);//判断时候有未完成的图片
            if (empty($logImage)) {//图片不存在
                return ['code' => '3005'];//图片没有上传过
            }
            $logImage['status'] = 1;//更新为完成状态
            $orderBy++;
            $row = [
                'promote_id'    => $promote_id,
                'source_type' => 4,
                'image_type'  => $imageType,
                'image_path'  => $image,
                'order_by'    => $orderBy,
            ];
            array_push($logData, $logImage);
            array_push($data, $row);
        }
//        print_r($data);die;
        Db::startTrans();
        try {
            DbSup::addPromoteImageList($data);
            DbImage::updateLogImageStatusList($logData);//更新状态为已完成
            Db::commit();
            return ["code" => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ["code" => "3006"];
        }
    }

    /**
     * 删除商品详情和轮播图
     * @param $imagePath
     * @return array
     */
    public function delPromoteImage($imagePath) {
        $imagePath  = filtraImage(Config::get('qiniu.domain'), $imagePath);//要删除的图片
        $goodsImage = DbSup::getOnePromoteImage(['image_path' => $imagePath], 'id');
        if (empty($goodsImage)) {
            return ['code' => '3002'];
        }
        $goodsImageId = array_column($goodsImage, 'id');
        $goodsImageId = $goodsImageId[0];
        $oldLogImage  = [];
        if (stripos($imagePath, 'http') === false) {//新版本图片
            $oldLogImage = DbImage::getLogImage($imagePath, 1);//之前在使用的图片日志
        }
        Db::startTrans();
        try {
            if (!empty($oldLogImage)) {
                DbImage::updateLogImageStatus($oldLogImage, 3);//更新状态为弃用
            }
            DbSup::delPromoteImage($goodsImageId);
            Db::commit();
            return ["code" => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ["code" => "3003"];
        }
    }

    /**
     * 对图片排序
     * @param $imagePath
     * @param $orderBy
     * @return array
     */
    public function sortPromoteimagedetail($imagePath, $orderBy) {
        $imagePath  = filtraImage(Config::get('qiniu.domain'), $imagePath);//要排序的图片
        $goodsImage = DbSup::getOnePromoteImage(['image_path' => $imagePath], 'id,order_by');
        if (empty($goodsImage)) {
            return ['code' => '3002'];
        }
        $goodsImageId = array_column($goodsImage, 'id');
        $goodsImageId = $goodsImageId[0];
        $oldOrderBy   = $goodsImage[0]['order_by'];
        if ($oldOrderBy == $orderBy) {//排序不改变无需更新
            return ["code" => '200'];
        }
        Db::startTrans();
        try {
            DbSup::updatePromoteImage(['order_by' => $orderBy], $goodsImageId);
            Db::commit();
            return ["code" => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ["code" => "3004"];
        }
    }

    public function getPromoteimagedetail($promote_id){
        $promote = DbSup::getSupPromote(['id' => $promote_id], 'id', true);
        if (empty($promote)) {
            return ['code' => '3002']; //推广活动不存在
        }
        $banner = DbSup::getOnePromoteImage(['promote_id' => $promote_id,'image_type' => 2],'*',['order_by' => 'desc']);
        $detail = DbSup::getOnePromoteImage(['promote_id' => $promote_id,'image_type' => 1],'*',['order_by' => 'desc']);
        return ['code' => '200','banner' => $banner,'detail' => $detail];
    }

}