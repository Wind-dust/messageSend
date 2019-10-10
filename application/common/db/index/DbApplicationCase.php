<?php

namespace app\common\db\index;

use app\common\model\ApplicationCase;
use think\Db;

class DbApplicationCase {
    /**
     * @param $where
     * @param $field
     * @param $row
     * @param $orderBy
     * @param $limit
     * @return array
     * @author rzc
     */
    public function getApplicationCase($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = ApplicationCase::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $limit);
    }

 
    public function addApplicationCase($data) {
        $ApplicationCase = new ApplicationCase;
        $ApplicationCase->save($data);
        return $ApplicationCase->id;
    }

    public function editApplicationCase($data,$id){
        $ApplicationCase = new ApplicationCase;
        return $ApplicationCase->save($data,['id' => $id]);
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