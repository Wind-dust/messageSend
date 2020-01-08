<?php

namespace app\common\db\other;

use app\common\model\LogVideo;

class DbVideo
{
    private $LogVideo;

    public function __construct()
    {
        $this->LogVideo = new LogVideo();
    }

    /**
     * 写入文件上传日志
     * @param $video_path
     * @param string $username
     * @param int $stype
     * @return int
     * @author zyr
     */
    public function saveLogVideo($video_path, $username = '', $stype = 2)
    {
        $data = [
            'username'   => $username,
            'video_path' => $video_path,
            'stype'      => $stype,
        ];
        return $this->LogVideo->save($data);
    }


    /**
     * 批量写入上传日志
     * @param $videoPathList
     * @param string $username
     * @return \think\Collection
     * @throws \Exception
     */
    public function saveLogVideoList($videoPathList, $username = '')
    {
        $data = [];
        foreach ($videoPathList as $val) {
            array_push($data, [
                'username'   => $username,
                'video_path' => $val,
            ]);
        }
        return $this->logvideo->saveAll($data);
    }
    /**
     * 批量写入上传日志
     * @param $videoPathList
     * @param string $username
     * @return \think\Collection
     * @throws \Exception
     */
    public function saveLogFileList($videoPathList, $username = '')
    {
        $data = [];
        foreach ($videoPathList as $val) {
            array_push($data, [
                'username'   => $username,
                'video_path' => $val,
            ]);
        }
        return $this->LogFile->saveAll($data);
    }

    /**
     * 查找该视频是否有未完成的
     * @param $name
     * @param $status
     * @return array
     * @author zyr
     */
    public function getLogVideo($name, $status)
    {
        return LogVideo::field('id')->where(['video_path' => $name, 'status' => $status])->findOrEmpty()->toArray();
    }

    public function getLogVideoAll($name)
    {
        return LogVideo::field('id')->where(['video_path' => $name])->findOrEmpty()->toArray();
    }

    /**
     * 查找视频日志
     * @param $where
     * @param $field
     * @return array
     * @author zyr
     */
    public function getLogVideoList($where, $field)
    {
        return LogVideo::field($field)->where($where)->select()->toArray();
    }


    /**
     * 更新状态
     * @param $id
     * @param $status
     * @return bool
     */
    public function updateLogVideoStatus($id, $status)
    {
        return $this->LogVideo->save(['status' => $status], $id);
    }

    public function updateLogVideoStatusList($data)
    {
        return $this->LogVideo->saveAll($data);
    }
}
