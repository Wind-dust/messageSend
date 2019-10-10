<?php

namespace app\common\db\index;

use app\common\model\Aboutus;
use think\Db;

class DbAboutus {
    /**
     * @param $where
     * @param $field
     * @param $row
     * @param $orderBy
     * @param $limit
     * @return array
     * @author rzc
     */
    public function getAboutus($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = Aboutus::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $limit);
    }

 
    public function addAboutus($data) {
        $Aboutus = new Aboutus;
        $Aboutus->save($data);
        return $Aboutus->id;
    }

    public function editAboutus($data,$id){
        $Aboutus = new Aboutus;
        return $Aboutus->save($data,['id' => $id]);
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