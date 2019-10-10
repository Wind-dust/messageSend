<?php

namespace app\common\action\admin;

use app\common\action\notify\Note;
use app\facade\DbApplicationCase;
use cache\Phpredis;
use Config;
use Env;
use think\Db;
use third\PHPTree;

class ApplicationCase extends CommonIndex {
    private $cmsCipherUserKey = 'adminpass'; //用户密码加密key

    private function redisInit() {
        $this->redis = Phpredis::getConn();
//        $this->connect = Db::connect(Config::get('database.db_config'));
    }

    /**
     * @param $page
     * @param $pageNum
     * @return array
     * @author rzc
     */
    public function getApplicationCase($page, $pageNum, $id = 0) {
        $offset = ($page - 1) * $pageNum;
        if (!empty($id)) {
            $result = DbApplicationCase::getApplicationCase(['id' => $id], '*', true);
        } else {
            $result = DbApplicationCase::getApplicationCase([], '*', false, '', $offset . ',' . $pageNum);
        }
        
        return ['code' => '200', 'ApplicationCase' => $result];
    }

    public function addApplicationCase($title, $image_path, $type = 1, $jump_content = '', $order = 0, $content) {
        $data = [];
        $data = [
            'title'        => $title,
            'image_path'   => $image_path,
            'type'    => $type,
            'jump_content' => $jump_content,
            'order'        => $order,
            'content'      => $content,
        ];
        $logImage = [];
        $image    = filtraImage(Config::get('qiniu.domain'), $data['image']);
        $logImage = DbImage::getLogImage($image, 2); //判断时候有未完成的图片
        if (empty($logImage)) { //图片不存在
            return ['code' => '3010']; //图片没有上传过
        }
        Db::startTrans();
        try {
            $data['image'] = $image;
            DbImage::updateLogImageStatus($logImage, 1); //更新状态为已完成
            $bId = DbApplicationCase::addApplicationCase($data); //添加后的商品id
            if ($bId === false) {
                Db::rollback();
                return ['code' => '3009']; //添加失败
            }
            Db::commit();
            return ['code' => '200', 'goods_id' => $bId];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3009'];
        }
    }

    public function updateApplicationCase($id, $title = '', $image_path = '', $type = 1, $jump_content = '', $order = 0, $content = '') {
        $ApplicationCase = DbApplicationCase::getApplicationCase(['id' => $id], 'id,image_path', true);
        if (empty($ApplicationCase)) {
            return ['code' => '3001'];
        }
        if (!empty($title)) {
            $data['title'] = $title;
        }
        if (!empty($image_path)) {
            $data['image_path'] = $image_path;
        }
        if (!empty($type)) {
            $data['type'] = $type;
        }
        if (!empty($jump_content)) {
            $data['jump_content'] = $jump_content;
        }
        if (!empty($order)) {
            $data['order'] = $order;
        }
        $logImage    = [];
        $oldLogImage = [];
        if (!empty($data['image'])) { //提交了图片
            $image    = filtraImage(Config::get('qiniu.domain'), $data['image']);
            $logImage = DbImage::getLogImage($image, 2); //判断时候有未完成的图片
            if (empty($logImage)) { //图片不存在
                return ['code' => '3010']; //图片没有上传过
            }
            $oldImage = $ApplicationCase['image'];
            $oldImage = filtraImage(Config::get('qiniu.domain'), $oldImage);
            if (!empty($oldImage)) { //之前有图片
                if (stripos($oldImage, 'http') === false) { //新版本图片
                    $oldLogImage = DbImage::getLogImage($oldImage, 1); //之前在使用的图片日志
                }
            }
            $data['image'] = $image;
        }
        Db::startTrans();
        try {
            $updateRes = DbApplicationCase::editApplicationCase($data, $id);
            if (!empty($logImage)) {
                DbImage::updateLogImageStatus($logImage, 1); //更新状态为已完成
            }
            if (!empty($oldLogImage)) {
                DbImage::updateLogImageStatus($oldLogImage, 3); //更新状态为弃用
            }
            if ($updateRes) {
                Db::commit();
                return ['code' => '200'];
            }
            Db::rollback();
            return ['code' => '3009']; //修改失败
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3009']; //修改失败
        }
    }
}