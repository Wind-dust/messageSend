<?php

namespace app\common\db\index;

use app\common\model\Guestbook;
use think\Db;

class DbGuestbook {
    /**
     * @param $where
     * @param $field
     * @param $row
     * @param $orderBy
     * @param $limit
     * @return array
     * @author rzc
     */
    public function getGuestbook($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = Guestbook::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $limit);
    }

 
    public function addGuestbook($data) {
        $Guestbook = new Guestbook;
        $Guestbook->save($data);
        return $Guestbook->id;
    }

    public function editGuestbook($data,$id){
        $Guestbook = new Guestbook;
        return $Guestbook->save($data,['id' => $id]);
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