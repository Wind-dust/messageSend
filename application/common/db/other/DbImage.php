<?php

namespace app\common\db\other;

use app\common\model\LogImage;
use app\common\model\LogFile;
use app\common\model\UserImage;

class DbImage {
    private $logImage;

    public function __construct() {
        $this->logImage = new LogImage();
        $this->LogFile = new LogFile();
    }

    /**
     * 写入文件上传日志
     * @param $image_path
     * @param string $username
     * @param int $stype
     * @return int
     * @author zyr
     */
    public function saveLogImage($image_path, $username = '', $stype = 2) {
        $data = [
            'username'   => $username,
            'image_path' => $image_path,
            'stype'      => $stype,
        ];
        return $this->logImage->save($data);
    }

    /**
     * 写入文件上传日志
     * @param $image_path
     * @param string $username
     * @param int $stype
     * @return int
     * @author zyr
     */
    public function saveLogFile($image_path, $username = '', $stype = 2) {
        $data = [
            'username'   => $username,
            'image_path' => $image_path,
            'stype'      => $stype,
        ];
        return $this->LogFile->save($data);
    }

    /**
     * 批量写入上传日志
     * @param $imagePathList
     * @param string $username
     * @return \think\Collection
     * @throws \Exception
     */
    public function saveLogImageList($imagePathList, $username = '') {
        $data = [];
        foreach ($imagePathList as $val) {
            array_push($data, [
                'username'   => $username,
                'image_path' => $val,
            ]);
        }
        return $this->logImage->saveAll($data);
    }
        /**
     * 批量写入上传日志
     * @param $imagePathList
     * @param string $username
     * @return \think\Collection
     * @throws \Exception
     */
    public function saveLogFileList($imagePathList, $username = '') {
        $data = [];
        foreach ($imagePathList as $val) {
            array_push($data, [
                'username'   => $username,
                'image_path' => $val,
            ]);
        }
        return $this->LogFile->saveAll($data);
    }

    /**
     * 查找该图片是否有未完成的
     * @param $name
     * @param $status
     * @return array
     * @author zyr
     */
    public function getLogImage($name, $status) {
        return LogImage::field('id')->where(['image_path' => $name, 'status' => $status])->findOrEmpty()->toArray();
    }

    /**
     * 查找该图片是否有未完成的
     * @param $name
     * @param $status
     * @return array
     * @author zyr
     */
    public function getLogFile($name) {
        return LogFile::field('id')->where(['image_path' => $name])->findOrEmpty()->toArray();
    }

    /**
     * 查找图片日志
     * @param $where
     * @param $field
     * @return array
     * @author zyr
     */
    public function getLogImageList($where, $field) {
        return LogImage::field($field)->where($where)->select()->toArray();
    }

    /**
     * 查找图片日志
     * @param $where
     * @param $field
     * @return array
     * @author zyr
     */
    public function getLogFileList($where, $field) {
        return LogFile::field($field)->where($where)->select()->toArray();
    }


    /**
     * 更新状态
     * @param $id
     * @param $status
     * @return bool
     */
    public function updateLogImageStatus($id, $status) {
        return $this->logImage->save(['status' => $status], $id);
    }

    public function updateLogImageStatusList($data) {
        return $this->logImage->saveAll($data);
    }

        /**
     * 更新状态
     * @param $id
     * @param $status
     * @return bool
     */
    public function updateLogFileStatus($id, $status) {
        return $this->LogFile->save(['status' => $status], $id);
    }

    public function updateLogFileStatusList($data) {
        return $this->LogFile->saveAll($data);
    }


    /**
     * 查询用户图片
     * @param $id
     * @param $status
     * @return bool
     */
    public function getUserImage($file,$where,$row = false, $orderBy = '', $sc = '', $limit = '') {
        $obj = UserImage::field($file)->where($where);
        if (!empty($orderBy) && !empty($sc)) {
            $obj = $obj->order($orderBy, $sc);
        }
        if (!empty($limit)) {
            $obj = $obj->limit($limit);
        }
        if ($row === true) {
            $obj = $obj->findOrEmpty();
        } else {
            $obj = $obj->select();
        }
        return $obj->toArray();
    }

    /**
     * 保存用户图片
     * @param $data
     * @return bool
     */
    public function saveUserImage($data){
        $UserImage = new UserImage;
        return $UserImage->save($data);
    }

}