<?php

namespace app\common\db\index;

use app\common\model\Solution;
use think\Db;

class DbSolution {
    /**
     * @param $where
     * @param $field
     * @param $row
     * @param $orderBy
     * @param $limit
     * @return array
     * @author rzc
     */
    public function getSolution($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = Solution::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $limit);
    }

 
    public function addSolution($data) {
        $Solution = new Solution;
        $Solution->save($data);
        return $Solution->id;
    }

    public function editSolution($data,$id){
        $Solution = new Solution;
        return $Solution->save($data,['id' => $id]);
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