<?php

namespace app\common\action\index;

use Config;
use upload\Fileupload;
use upload\Imageupload;
use app\facade\DbImage;
use app\facade\DbUser;

class Upload extends CommonIndex {
    private $upload;

    public function __construct() {
        $this->upload = new Imageupload();
        // $this->fileupload = new Fileupload();
    }

    /**
     * 单个上传图片
     * @param $fileInfo
     * @return array
     */
    /**
     * 单个上传图片
     * @param $fileInfo
     * @return array
     */
    public function uploadFile($appid,$appkey,$fileInfo) {
        /* 文件名重命名 */
        $user = DbUser::getUserOne(['appid' => $appid], 'id,appkey,user_type,user_status,reservation_service,free_trial', true);
        if (empty($user)) {
            return ['code' => '3000'];
        }
        if ($appkey != $user['appkey']) {
            return ['code' => '3000'];
        }
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
     * 批量上传图片
     * @param $list
     * @return array
     */
    public function uploadMultiFile($list) {
        $filenameArr = [];
        $flag        = true;
        foreach ($list as $val) {
            $filename    = date('Ymd') . '/' . $this->upload->getNewName($val['name']);
            $uploadimage = $this->upload->uploadFile($val['tmp_name'], $filename);
            if (!$uploadimage) {//上传失败
                $flag = false;
                break;
            }
            array_push($filenameArr, $filename);
        }
        if (!empty($filenameArr) && $flag === false) {//批量上传失败需要将已上传的文件删除
            $this->delImg($filenameArr);
        }
        if ($flag) {
            try {
                DbImage::saveLogImageList($filenameArr);//全部上传成功后写如日志/**/
            } catch (\Exception $e) {
//                $this->delImg($filenameArr);
                return ['code' => '3003'];//上传失败
            }
            array_walk($filenameArr, function (&$value, $key) {//加上域名前缀
                $value = Config::get('qiniu.domain') . '/' . $value;
            });
            return ['code' => '200', 'data' => $filenameArr];
        } else {
            return ['code' => '3003'];//上传失败
        }
    }

    /**
     * 批量删除图片
     * @param $filenameArr
     */
    private function delFile($filenameArr) {
        if (!is_array($filenameArr)) {
            $this->upload->deleteFile($filenameArr);//删除上传的图片
        } else {
            foreach ($filenameArr as $v) {
                $this->upload->deleteFile($v);//删除上传的图片
            }
        }
    }

    public function uploadUserImage($filename) {
        if (empty($filename)) {
            return ['code' => '3001'];
        }
        $imagePath   = Config::get('conf.image_path');
        $image       = $imagePath . $filename;
        $newfilename = date('Ymd') . '/' . $this->upload->getNewName($filename);
        $uploadimage = $this->upload->uploadFile($image, $newfilename);
        if ($uploadimage) {//上传成功
            $result = DbImage::saveLogImage($newfilename, '', 1);
            if ($result) {
                unlink(Config::get('conf.image_path') . $filename);
                return ['code' => '200', 'image_path' => $newfilename];
            } else {
                $this->delImg($newfilename);//删除上传的图片
            }
        }
        return ['code' => '3002'];//上传失败
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