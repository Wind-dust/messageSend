<?php

namespace app\common\db;
class Db {
    public function __Call($name, $arguments) {
        // TODO: Implement __call() method.
        if (strpos($name, 'count') !== false) {
            $name = str_replace('count', '', $name);
            if (!class_exists('\app\\common\\model\\' . $name)) {
                return false;
            }
            $where = empty($arguments[0]) ? [] : $arguments[0];
            return $this->countNum($name, $where);
        }
        $name = str_replace('get', '', $name);
        if (!class_exists('\app\\common\\model\\' . $name)) {
            return false;
        }
        $where   = empty($arguments[0]) ? [] : $arguments[0];
        $field   = empty($arguments[1]) ? '*' : $arguments[1];
        $row     = empty($arguments[2]) ? false : $arguments[2];
        $orderBy = empty($arguments[3]) ? '' : $arguments[3];
        $limit   = empty($arguments[4]) ? '' : $arguments[4];
        return $this->getList($name, $where, $field, $row, $orderBy, $limit);
    }

    protected function getList($name, $where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = call_user_func_array(['app\\common\\model\\' . $name, 'field'], [$field]);
        $obj = $obj->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

    protected function countNum($name, $where) {
        $obj = call_user_func_array(['app\\common\\model\\' . $name, 'where'], [$where]);
        return $obj->count();
    }
}