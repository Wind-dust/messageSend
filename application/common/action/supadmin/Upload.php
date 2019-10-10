<?php

namespace app\common\action\supadmin;

use Config;
use upload\Imageupload;
use app\facade\DbImage;

class Upload extends CommonIndex {
    private $upload;

    public function __construct() {
        $this->upload = new Imageupload();
    }

    /**
     * 单个上传图片
     * @param $fileInfo
     * @return array
     */
    public function uploadFile($fileInfo) {
        /* 文件名重命名 */
        $filename    = date('Ymd') . '/' . $this->upload->getNewName($fileInfo['name']);
        $uploadimage = $this->upload->uploadFile($fileInfo['tmp_name'], $filename);
        if ($uploadimage) {//上传成功
            $result = DbImage::saveLogImage($filename);
            if ($result) {
                return ['code' => '200', 'image_path' => Config::get('qiniu.domain') . '/' . $filename];
            } else {
                $this->delImg($filename);//删除上传的图片
            }
        }
        return ['code' => '3003'];//上传失败
    }

    /**
     * 批量删除图片
     * @param $filenameArr
     */
    private function delImg($filenameArr) {
        if (!is_array($filenameArr)) {
            $this->upload->deleteImage($filenameArr);//删除上传的图片
        } else {
            foreach ($filenameArr as $v) {
                $this->upload->deleteImage($v);//删除上传的图片
            }
        }
    }
}