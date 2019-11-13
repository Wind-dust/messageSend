<?php

namespace app\common\db\other;

use app\common\model\Areas;
use app\common\model\NumberSource;

class DbProvinces {

    /**
     * 获取多条省市区信息
     * @param $field 查询字段
     * @param $where 条件
     * @return array
     */
    public function getAreaInfo($field, $where) {
        return Areas::where($where)->field($field)->select()->toArray();
    }

    /**
     * 获取单条省市区信息
     * @param $field 查询字段
     * @param $where 条件
     * @return array
     */
    public function getAreaOne($field,$where){
        return Areas::where($where)->field($field)->findOrEmpty()->toArray();
    }

    public function getAreaCount($field, $where){
        return Areas::where($where)->count();
    }

    public function getNumberSource($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = NumberSource::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }
}