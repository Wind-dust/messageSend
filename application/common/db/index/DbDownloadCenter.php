<?php

namespace app\common\db\index;

use app\common\model\DownloadCenter;
use think\Db;

class DbDownloadCenter {
    /**
     * @param $where
     * @param $field
     * @param $row
     * @param $orderBy
     * @param $limit
     * @return array
     * @author rzc
     */
    public function getDownloadCenter($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = DownloadCenter::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $limit);
    }

 
    public function addDownloadCenter($data) {
        $DownloadCenter = new DownloadCenter;
        $DownloadCenter->save($data);
        return $DownloadCenter->id;
    }

    public function editDownloadCenter($data,$id){
        $DownloadCenter = new DownloadCenter;
        return $DownloadCenter->save($data,['id' => $id]);
    }
  
    private function getResult($obj, $row = false, $orderBy = '', $limit = '') {
        if (!empty($orderBy)) {
            $obj = $obj->order($orderBy);
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
}