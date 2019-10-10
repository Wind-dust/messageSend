<?php

namespace app\common\db\index;

use app\common\model\Product;
use think\Db;

class DbProduct {
    /**
     * @param $where
     * @param $field
     * @param $row
     * @param $orderBy
     * @param $limit
     * @return array
     * @author rzc
     */
    public function getProduct($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = Product::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $limit);
    }

 
    public function addProduct($data) {
        $Product = new Product;
        $Product->save($data);
        return $Product->id;
    }

    public function editProduct($data,$id){
        $Product = new Product;
        return $Product->save($data,['id' => $id]);
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