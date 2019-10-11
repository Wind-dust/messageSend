<?php

namespace app\common\db\user;

use app\common\model\Admin;
use app\common\model\AdminPermissionsGroup;
use app\common\model\AdminPermissionsRelation;
use app\common\model\Menu;
use app\common\model\PermissionsApi;
use app\common\model\PermissionsGroup;
use app\common\model\AdminRemittance;
use app\common\model\User;
use think\Db;

class DbAdmin {

    public function getAdminInfo($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = Admin::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $limit);
    }

    public function getAdminInfoByGroup($where, $field) {
        array_push($where, ['a.delete_time', '=', '0']);
        array_push($where, ['apg.delete_time', '=', '0']);
        array_push($where, ['pg.delete_time', '=', '0']);
        return Db::table('yx_admin')
            ->alias('a')
            ->field($field)
            ->join(['yx_admin_permissions_group' => 'apg'], 'apg.admin_id=a.id')
            ->join(['yx_permissions_group' => 'pg'], 'apg.group_id=pg.id')
            ->where($where)
            ->select();
    }

    /**
     * @param $data
     * @return mixed
     * @author zyr
     */
    public function addAdmin($data) {
        $admin = new Admin();
        $admin->save($data);
        return $admin->id;
    }

    public function updatePasswd($newPasswd, $id) {
        $admin = new Admin();
        return $admin->save(['passwd' => $newPasswd], ['id' => $id]);
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
     * 添加充值记录
     * @param $data
     * @return mixed
     * @author rzc
     */
    public function addAdminRemittance($data) {
        $AdminRemittance = new AdminRemittance;
        $AdminRemittance->save($data);
        return $AdminRemittance->id;
    }

    /**
     * 修改充值记录
     * @param $data
     * @param $id
     * @return mixed
     * @author rzc
     */
    public function editRemittance($data, $id) {
        $AdminRemittance = new AdminRemittance;
        return $AdminRemittance->save($data, ['id' => $id]);
    }

    /**
     * 获取充值记录
     * @param $data
     * @param $id
     * @return mixed
     * @author rzc
     */
    public function getAdminRemittance($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = AdminRemittance::field($field)->with([
            'initiateadmin' => function ($query) {
                $query->field('id,admin_name,department,stype,status');
            }, 'auditadmin' => function ($query) {
                $query->field('id,admin_name,department,stype,status');
            }, 'user'       => function ($query) {
                $query->field('id,nick_name,user_identity,mobile');
            }
        ])->where($where);
        return $this->getResult($obj, $row, $orderBy, $limit);
    }

    /**
     * 获取充值记录条数
     * @param $where
     * @return mixed
     * @author rzc
     */
    public function getCountAdminRemittance($where) {
        return AdminRemittance::where($where)->count();
    }

    /**
     * 获取支持银行
     * @param $where
     * @param $field
     * @param $row
     * @param $orderBy
     * @param $limit
     * @return mixed
     * @author rzc
     */
    public function getAdminBank($where, $field, $row = false, $orderBy = '', $limit = '', $whereOr = false) {
        $obj = AdminBank::field($field);
        if ($whereOr === true) {
            $obj = $obj->whereOr($where);
        } else {
            $obj = $obj->where($where);
        }
        return $this->getResult($obj, $row, $orderBy, $limit);
    }

    /**
     * 获取记录条数
     * @param $where
     * @return number
     * @author rzc
     */
    public function getAdminBankCount($where) {
        return AdminBank::where($where)->count();
    }

    /**
     * 添加支持银行
     * @param $data
     * @return id
     * @author rzc
     */
    public function saveAdminBank($data) {
        $AdminBank = new AdminBank;
        $AdminBank->save($data);
        return $AdminBank->id;
    }

    /**
     * 修改支持银行
     * @param $data
     * @return id
     * @author rzc
     */
    public function editAdminBank($data, $id) {
        $AdminBank = new AdminBank;
        return $AdminBank->save($data, ['id' => $id]);
    }

    public function getMenu($where) {
        $obj = Menu::where($where)->select()->toArray();
        return $obj;
    }

    public function getMenuList($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = Menu::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $limit);
    }

    public function getAdminPermissionsGroup($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = AdminPermissionsGroup::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $limit);
    }

    public function getPermissionsGroup($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = PermissionsGroup::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $limit);
    }

    public function getAdminPermissionsRelation($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = AdminPermissionsRelation::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $limit);
    }

    public function getPermissionsApi($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = PermissionsApi::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $limit);
    }

    public function getPermissionsApiMenu($where) {
        array_push($where, ['pa.delete_time', '=', '0']);
        return Db::table('pz_permissions_api')
            ->alias('pa')
            ->field('pa.id,pa.stype,pa.cn_name,pa.content,m.name as menu_name')
            ->join(['pz_menu' => 'm'], 'pa.menu_id=m.id')
            ->where($where)
            ->select();
    }

    public function addPermissionsGroup($data) {
        $permissionsGroup = new PermissionsGroup();
        $permissionsGroup->save($data);
        return $permissionsGroup->id;
    }

    public function editPermissionsGroup($data, $id) {
        $permissionsGroup = new PermissionsGroup();
        return $permissionsGroup->save($data, ['id' => $id]);
    }

    public function addAdminPermissionsGroup($data) {
        $adminPermissionsGroup = new AdminPermissionsGroup();
        $adminPermissionsGroup->save($data);
        return $adminPermissionsGroup->id;
    }

    public function addAdminPermissionsRelation($data) {
        $adminPermissionsRelation = new AdminPermissionsRelation();
        return $adminPermissionsRelation->saveAll($data);
    }

    public function addPermissionsApi($data) {
        $permissionsApi = new PermissionsApi();
        $permissionsApi->save($data);
        return $permissionsApi->id;
    }

    public function editPermissionsApi($data, $id) {
        $permissionsApi = new PermissionsApi();
        return $permissionsApi->save($data, ['id' => $id]);
    }

    public function deleteAdminPermissionsRelation($ids) {
        return AdminPermissionsRelation::destroy($ids);
    }

    public function deleteAdminPermissionsGroup($id) {
        return AdminPermissionsGroup::destroy($id);
    }

    public function editMenu($data, $id) {
        $menu = new Menu();
        return $menu->save($data, ['id' => $id]);
    }
}