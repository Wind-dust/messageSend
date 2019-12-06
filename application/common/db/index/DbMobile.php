<?php

namespace app\common\db\index;

use app\common\model\NumberSource;
use think\Db;

class DbMobile extends Db  {

    /**
     * 获取三网号码归属省份及运营商
     * @param $where
     * @return array
     */
    public function getNumberSource($where, $field, $row = false, $orderBy = '', $limit = '', $sc = ''){
        $obj = NumberSource::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

}