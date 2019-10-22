<?php

namespace app\common\db\user;

use app\common\model\LogVercode;
use app\common\model\UserCon;
use app\common\model\Users;
use think\Db;

class DbUser {

    /**
     * 获取一个用户信息
     * @param $where
     * @return array
     */
    public function getUser($where) {
        $field = ['passwd', 'delete_time'];
        $user  = Users::where($where)->field($field, true)->findOrEmpty()->toArray();
        return $user;
    }

    public function getUserOne($where, $field) {
        $user = Users::where($where)->field($field)->findOrEmpty()->toArray();
        return $user;
    }

    public function getUserInfo($where, $field, $row = false, $orderBy = '', $limit = '', $sc = '') {
        $obj = Users::field($field)->where($where);
        if (!empty($orderBy) && !empty($sc)) {
            $obj = $obj->order($orderBy, $sc);
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

    public function getUserInfoCount($where){
        return Users::where($where)->count();
    }

    /**
     * 添加验证码日志
     * @param $data
     * @return mixed
     * @author zyr
     */
    public function addLogVercode($data) {
        $logVercode = new LogVercode();
        $logVercode->save($data);
        return $logVercode->id;
    }

    /**
     * 获取一条验证码日志
     * @param $where
     * @param $field
     * @return array
     * @author zyr
     */
    public function getOneLogVercode($where, $field) {
        return LogVercode::where($where)->field($field)->findOrEmpty()->toArray();
    }

    /**
     * @param $obj
     * @param bool $row
     * @param string $orderBy
     * @param string $limit
     * @return mixed
     * @author zyr
     */
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

     /**
     * 添加一天con_id记录
     * @param $data
     * @return mixed
     * @author zyr
     */
    public function addUserCon($data) {
        $userCon = new UserCon();
        $userCon->save($data);
        return $userCon->id;
    }

    public function addUser($data) {
        $user = new Users();
        $user->save($data);
        return $user->id;
    }

    /**
     * 更新用户
     * @param $data
     * @param $uid
     * @return bool
     * @author zyr
     */
    public function updateUser($data, $uid) {
        $user = new Users();
        return $user->save($data, ['id' => $uid]);
    }
}