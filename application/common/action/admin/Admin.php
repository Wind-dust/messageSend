<?php

namespace app\common\action\admin;

use app\common\action\notify\Note;
use app\facade\DbAdmin;
use app\facade\DbImage;
use cache\Phpredis;
use Config;
use Env;
use think\Db;
use third\PHPTree;

class Admin extends CommonIndex {
    private $cmsCipherUserKey = 'adminpass'; //用户密码加密key

    private function redisInit() {
        $this->redis = Phpredis::getConn();
//        $this->connect = Db::connect(Config::get('database.db_config'));
    }

    /**
     * @param $adminName
     * @param $passwd
     * @return array
     * @author zyr
     */
    public function login($adminName, $passwd) {
        $getPass   = $this->getPassword($passwd, $this->cmsCipherUserKey); //用户填写的密码
        $adminInfo = DbAdmin::getAdminInfo(['admin_name' => $adminName, 'status' => 1], 'id,passwd', true);
        if (empty($adminInfo)) {
            return ['code' => '3002']; //用户不存在
        }
        if ($adminInfo['passwd'] !== $getPass) {
            return ['code' => '3003']; //密码错误
        }
        $cmsConId = $this->createCmsConId();
        $this->redis->zAdd($this->redisCmsConIdTime, time(), $cmsConId);
        $conUid = $this->redis->hSet($this->redisCmsConIdUid, $cmsConId, $adminInfo['id']);
        if ($conUid === false) {
            return ['code' => '3004']; //登录失败
        }
        return ['code' => '200', 'cms_con_id' => $cmsConId];
    }

    /**
     * @param $cmsConId
     * @return array
     * @author zyr
     */
    public function getAdminInfo($cmsConId) {
        $adminId                 = $this->getUidByConId($cmsConId);
        $adminInfo               = DbAdmin::getAdminInfo(['id' => $adminId], 'admin_name,stype', true);
        $adminGroup              = DbAdmin::getAdminPermissionsGroup(['admin_id' => $adminId], 'group_id');
        $adminGroup              = array_column($adminGroup, 'group_id');
        $group                   = DbAdmin::getPermissionsGroup([['id', 'in', $adminGroup]], 'group_name');
        $group                   = array_column($group, 'group_name');
        $adminInfo['group_name'] = $group;
        return ['code' => '200', 'data' => $adminInfo];
    }

    /**
     * @return array
     * @author rzc
     */
    public function getAdminUsers() {
        $adminByGroup = DbAdmin::getAdminInfoByGroup([
            ['a.id', '<>', '1'],
        ], 'a.id as admin_id,pg.group_name');
        $adminGroup = [];
        foreach ($adminByGroup as $ag) {
            if (!isset($adminGroup[$ag['admin_id']])) {
                $adminGroup[$ag['admin_id']] = [$ag['group_name']];
                continue;
            }
            array_push($adminGroup[$ag['admin_id']], $ag['group_name']);
        }
        $adminInfo = DbAdmin::getAdminInfo([['id', '<>', 1]], 'id,admin_name,department,stype,status');
        foreach ($adminInfo as &$ai) {
            $ai['group'] = $adminGroup[$ai['id']] ?? [];
        }
        unset($ai);
        return ['code' => '200', 'data' => $adminInfo];
    }

    /**
     * @param $cmsConId
     * @param $adminName
     * @param $passwd
     * @param $stype
     * @return array
     * @author zyr
     */
    public function addAdmin($cmsConId, $adminName, $passwd, $stype) {
        $adminId = $this->getUidByConId($cmsConId);
//        $adminInfo = DbAdmin::getAdminInfo(['id' => $adminId], 'stype,status', true);
        //        if ($adminInfo['stype'] != '2') {
        //            return ['code' => '3005']; //没有操作权限
        //        }
        //        if ($stype == 2 && $adminId != 1) {
        //            return ['code' => '3003']; //只有root账户可以添加超级管理员
        //        }
        $newAdminInfo = DbAdmin::getAdminInfo(['admin_name' => $adminName], 'id', true);
        if (!empty($newAdminInfo)) {
            return ['code' => '3004']; //该账号已存在
        }
        Db::startTrans();
        try {
            DbAdmin::addAdmin([
                'admin_name' => $adminName,
                'passwd'     => $this->getPassword($passwd, $this->cmsCipherUserKey),
                'stype'      => $stype,
            ]);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3006']; //添加失败
        }
    }

    /**
     * @param $cmsConId
     * @param $passwd
     * @param $newPasswd
     * @return array
     * @author zyr
     */
    public function midifyPasswd($cmsConId, $passwd, $newPasswd) {
        $adminId   = $this->getUidByConId($cmsConId);
        $adminInfo = DbAdmin::getAdminInfo(['id' => $adminId], 'id,passwd,status', true);
        if ($adminInfo['passwd'] !== $this->getPassword($passwd, $this->cmsCipherUserKey)) {
            return ['code' => '3001'];
        }
        Db::startTrans();
        try {
            DbAdmin::updatePasswd($this->getPassword($newPasswd, $this->cmsCipherUserKey), $adminId);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3005']; //修改密码失败
        }
    }

    /**
     * 创建唯一conId
     * @author zyr
     */
    private function createCmsConId() {
        $cmsConId = uniqid(date('ymdHis'));
        $cmsConId = hash_hmac('ripemd128', $cmsConId, 'admin');
        return $cmsConId;
    }

    /**
     * @param $str 加密的内容
     * @param $key
     * @return string
     * @author zyr
     */
    private function getPassword($str, $key) {
        $algo   = Config::get('conf.cipher_algo');
        $md5    = hash_hmac('md5', $str, $key);
        $key2   = strrev($key);
        $result = hash_hmac($algo, $md5, $key2);
        return $result;
    }


    /**
     * cms左侧菜单
     * @param $cmsConId
     * @return array
     * @author zyr
     */
    public function cmsMenu($cmsConId) {
        $adminId = $this->getUidByConId($cmsConId);
        if ($adminId == 1) {
            $data = DbAdmin::getMenu([]);
        } else {
            $group     = DbAdmin::getAdminPermissionsGroup(['admin_id' => $adminId], 'group_id');
            $groupList = array_column($group, 'group_id');
            if (empty($groupList)) {
                return ['code' => '3000'];
            }
            $permissionsGroup = DbAdmin::getAdminPermissionsRelation([['group_id', 'in', $groupList]], 'menu_id');
            $meum             = array_unique(array_column($permissionsGroup, 'menu_id'));
            if (empty($meum)) {
                return ['code' => '3000'];
            }
            $pidMenu = DbAdmin::getMenuList([['id', 'in', $meum]], 'pid');
            $pidMenu = array_column($pidMenu, 'pid');
            $data    = DbAdmin::getMenu([['id', 'in', array_merge($meum, $pidMenu)]]);
        }
        $tree = new PHPTree($data);
        $tree->setParam("pk", "id");
        $tree->setParam("pid", "pid");
        $cate_tree = $tree->listTree();
        return ["code" => 200, "data" => $cate_tree];
    }

    /**
     * cms菜单详情
     * @param $cmsConId
     * @param $id
     * @return array
     * @author zyr
     */
    public function cmsMenuOne($cmsConId, $id) {
//        $adminId = $this->getUidByConId($cmsConId);
        $data = DbAdmin::getMenuList([['id', '=', $id]], 'name', true);
        return ["code" => 200, "data" => $data];
    }

    /**
     * 修改保存cms菜单
     * @param $cmsConId
     * @param $id
     * @param $name
     * @return array
     * @author zyr
     */
    public function editMenu($cmsConId, $id, $name) {
        $menu = DbAdmin::getMenuList([['id', '=', $id]], 'id', true);
        if (empty($menu)) {
            return ['code' => '3002'];
        }
        Db::startTrans();
        try {
            DbAdmin::editMenu(['name' => $name], $id);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3003']; //修改失败
        }
    }

    /**
     * 添加权限分组
     * @param $cmsConId
     * @param $groupName
     * @param $content
     * @return array
     * @author zyr
     */
    public function addPermissionsGroup($cmsConId, $groupName, $content) {
//        $adminId = $this->getUidByConId($cmsConId);
        $group = DbAdmin::getPermissionsGroup(['group_name' => $groupName], 'id', true);
        if (!empty($group)) {
            return ['code' => '3001'];
        }
        $data = [
            'group_name' => $groupName,
            'content'    => $content,
        ];
        Db::startTrans();
        try {
            DbAdmin::addPermissionsGroup($data);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3005']; //添加失败
        }
    }

    /**
     * 修改权限分组
     * @param $cmsConId
     * @param $groupId
     * @param $groupName
     * @param $content
     * @return array
     * @author zyr
     */
    public function editPermissionsGroup($cmsConId, $groupId, $groupName, $content) {
        $adminId = $this->getUidByConId($cmsConId);
        $data    = [
            'group_name' => $groupName,
            'content'    => $content,
        ];
        $admin = DbAdmin::getPermissionsGroup(['id' => $groupId], 'id', true);
        if (empty($admin)) {
            return ['code' => '3003'];
        }
        Db::startTrans();
        try {
            DbAdmin::editPermissionsGroup($data, $groupId);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3005']; //添加失败
        }
    }

    /**
     * 添加管理员到权限组
     * @param $cmsConId
     * @param $groupId
     * @param $addAdminId
     * @return array
     * @author zyr
     */
    public function addAdminPermissions($cmsConId, $groupId, $addAdminId) {
        $adminId = $this->getUidByConId($cmsConId);
        $group   = DbAdmin::getPermissionsGroup(['id' => $groupId], 'id', true);
        if (empty($group)) { //权限分组不存在
            return ['code' => '3003'];
        }
        $addAdmin = DbAdmin::getAdminInfo(['id' => $addAdminId, 'status' => 1], 'id');
        if (empty($addAdmin)) {
            return ['code' => '3004'];
        }
        $data = [
            'admin_id' => $addAdminId,
            'group_id' => $groupId,
        ];
        $adminGroup = DbAdmin::getAdminPermissionsGroup($data, 'id', true);
        if (!empty($adminGroup)) {
            return ['code' => '3006'];
        }
        Db::startTrans();
        try {
            DbAdmin::addAdminPermissionsGroup($data);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3007']; //添加失败
        }
    }

    /**
     * 添加接口权限列表
     * @param $cmsConId
     * @param $menuId
     * @param $apiName
     * @param $stype
     * @param $cnName
     * @param $content
     * @return array
     * @author zyr
     */
    public function addPermissionsApi($cmsConId, $menuId, $apiName, $stype, $cnName, $content) {
        $adminId = $this->getUidByConId($cmsConId);
        if ($adminId != '1') {
            return ['code' => '3008']; //只有root可以添加
        }
        $apiRes = DbAdmin::getPermissionsApi(['api_name' => $apiName], 'id', true);
        if (!empty($apiRes)) {
            return ['code' => '3005']; //接口已存在
        }
        $menu = DbAdmin::getMenuList(['id' => $menuId, 'level' => 2], 'id');
        if (empty($menu)) {
            return ['code' => '3006']; //菜单不存在
        }
        $data = [
            'menu_id'  => $menuId,
            'api_name' => $apiName,
            'stype'    => $stype,
            'cn_name'  => $cnName,
            'content'  => $content,
        ];
        Db::startTrans();
        try {
            DbAdmin::addPermissionsApi($data);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3007']; //添加失败
        }
    }

    /**
     * 修改接口权限名称和详情
     * @param $cmsConId
     * @param $id
     * @param $cnName
     * @param $content
     * @return array
     * @author zyr
     */
    public function editPermissionsApi($cmsConId, $id, $cnName, $content) {
        $adminId = $this->getUidByConId($cmsConId);
        $apiRes  = DbAdmin::getPermissionsApi(['id' => $id], 'id', true);
        if (empty($apiRes)) {
            return ['code' => '3005']; //接口不存在
        }
        $data = [
            'cn_name' => $cnName,
            'content' => $content,
        ];
        Db::startTrans();
        try {
            DbAdmin::editPermissionsApi($data, $id);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3007']; //添加失败
        }
    }

    /**
     * 为权限组添加菜单接口
     * @param $cmsConId
     * @param $groupId
     * @param $permissions
     * @return array
     * @author zyr
     */
    public function addPermissionsGroupPower($cmsConId, $groupId, $permissions) {
        $adminId = $this->getUidByConId($cmsConId);
        $group   = DbAdmin::getPermissionsGroup(['id' => $groupId], 'id', true);
        if (empty($group)) { //权限分组不存在
            return ['code' => '3003'];
        }
        $permissions = json_decode(htmlspecialchars_decode($permissions), true);
        if (!is_array($permissions)) {
            return ['code' => '3005'];
        }
        $menuIdList = array_keys($permissions); //修改后的菜单权限
        $menuList   = DbAdmin::getMenuList([['id', 'in', $menuIdList], ['level', '=', 2]], 'id');
        if (!empty(array_diff($menuIdList, array_column($menuList, 'id')))) {
            return ['code' => '3006']; //菜单不存在
        }
        $useMenu       = DbAdmin::getAdminPermissionsRelation(['group_id' => $groupId], 'id,menu_id,api_id'); //正在使用的权限
        $useMenuList   = [];
        $apiIdList     = [];
        $relMenuIdList = [];
        if (!empty($useMenu)) {
            $useMenuList = array_unique(array_column($useMenu, 'menu_id'));
            foreach ($useMenu as $um1) {
                if (!isset($relMenuIdList[$um1['menu_id']])) {
                    $relMenuIdList[$um1['menu_id']] = [$um1['id']];
                } else {
                    array_push($relMenuIdList[$um1['menu_id']], $um1['id']);
                }
                if (!isset($apiIdList[$um1['api_id']])) {
                    $apiIdList[$um1['api_id']] = [$um1['id']];
                    continue;
                } else {
                    array_push($apiIdList[$um1['api_id']], $um1['id']);
                }
            }
        }
        $delMenu    = array_diff($useMenuList, $menuIdList);
        $addMenu    = array_diff($menuIdList, $useMenuList);
        $updateMenu = array_intersect($useMenuList, $menuIdList);
        $perApi     = DbAdmin::getPermissionsApi([['menu_id', 'in', $menuIdList]], 'id,menu_id');
        $apiList    = array_column($perApi, 'menu_id', 'id');
        foreach ($permissions as $k => $p) {
            if (!is_array($p)) {
                return ['code' => '3005']; //permissions参数有误,接口权限不属于菜单
            }
            $mIds = array_keys($p);
            foreach ($mIds as $m) {
                if (!isset($apiList[$m]) || $apiList[$m] != $k) {
                    return ['code' => '3005'];
                }
            }
        }
        $delId = [];
        foreach ($delMenu as $dm) {
            $delId = array_merge($delId, $relMenuIdList[$dm]);
        }
        $addData = [];
        foreach ($permissions as $k => $p) {
            if (in_array($k, $addMenu)) {
                array_push($addData, ['group_id' => $groupId, 'menu_id' => $k]);
                foreach ($p as $kp => $pp) {
                    if ($pp == 1) {
                        array_push($addData, ['group_id' => $groupId, 'menu_id' => $k, 'api_id' => $kp]);
                    }
                }
            }
            if (in_array($k, $updateMenu)) {
                foreach ($p as $kp => $pp) {
                    if (key_exists($kp, $apiIdList)) {
                        if ($pp == 0) { //删除
                            $delId = array_merge($delId, $apiIdList[$kp]);
                        }
                    } else {
                        if ($pp == 1) { //添加
                            array_push($addData, ['group_id' => $groupId, 'menu_id' => $k, 'api_id' => $kp]);
                        }
                    }
                }
            }
        }
        Db::startTrans();
        try {
            if (!empty($delId)) {
                DbAdmin::deleteAdminPermissionsRelation($delId);
            }
            if (!empty($addData)) {
                DbAdmin::addAdminPermissionsRelation($addData);
            }
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3007']; //添加失败
        }
    }

    /**
     * 删除权限组的成员
     * @param $cmsConId
     * @param $groupId
     * @param $delAdminId
     * @return array
     * @author zyr
     */
    public function delAdminPermissions($cmsConId, $groupId, $delAdminId) {
        $adminId = $this->getUidByConId($cmsConId);
        $group   = DbAdmin::getPermissionsGroup(['id' => $groupId], 'id', true);
        if (empty($group)) { //权限分组不存在
            return ['code' => '3003'];
        }
        $delAdmin = DbAdmin::getAdminInfo(['id' => $delAdminId, 'status' => 1], 'id');
        if (empty($delAdmin)) {
            return ['code' => '3004'];
        }
        $where = [
            'admin_id' => $delAdminId,
            'group_id' => $groupId,
        ];
        $adminGroup = DbAdmin::getAdminPermissionsGroup($where, 'id', true);
        if (empty($adminGroup)) { //删除的管理员不存在
            return ['code' => '3006'];
        }
        $delId = $adminGroup['id'];
        Db::startTrans();
        try {
            DbAdmin::deleteAdminPermissionsGroup($delId);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3007']; //删除失败
        }
    }

    /**
     * 获取权限组下的管理员
     * @param $cmsConId
     * @param $groupId
     * @return array
     * @author zyr
     */
    public function getPermissionsGroupAdmin($cmsConId, $groupId) {
//        $adminId = $this->getUidByConId($cmsConId);
        $groupAdmin = DbAdmin::getAdminPermissionsGroup([['group_id', '=', $groupId]], 'admin_id');
        if (empty($groupAdmin)) {
            return ['code' => '3000'];
        }
        $groupAdminId = array_column($groupAdmin, 'admin_id');
        $admin        = DbAdmin::getAdminInfo([
            ['id', 'in', $groupAdminId],
            ['status', '=', '1'],
            ['id', '<>', '1'],
        ], 'id,admin_name');
        return ['code' => '200', 'data' => $admin];
    }

    /**
     * 获取用户或所有的权限组列表
     * @param $cmsConId
     * @param $getAdminId
     * @return array
     * @author zyr
     */
    public function getAdminGroup($cmsConId, $getAdminId) {
//        $adminId = $this->getUidByConId($cmsConId);
        if (empty($getAdminId)) {
            $group = DbAdmin::getPermissionsGroup([], 'id,group_name,content');
        } else {
            $adminGroup = DbAdmin::getAdminPermissionsGroup([['admin_id', '=', $getAdminId]], 'group_id');
            if (empty($adminGroup)) {
                return ['code' => '3000'];
            }
            $adminGroupId = array_column($adminGroup, 'group_id');
            $group        = DbAdmin::getPermissionsGroup([
                ['id', 'in', $adminGroupId],
            ], 'id,group_name,content');
        }
        return ['code' => '200', 'data' => $group];
    }

    public function getGroupInfo($cmsConId, $groupId) {
        $group = DbAdmin::getPermissionsGroup(['id' => $groupId], 'id,group_name,content', true);
        return ['code' => '200', 'data' => $group];
    }

    /**
     * 获取权限列表
     * @param $cmsConId
     * @param $groupId
     * @return array
     * @author zyr
     */
    public function getPermissionsList($cmsConId, $groupId) {
//        $adminId = $this->getUidByConId($cmsConId);
        $data = DbAdmin::getMenuList([], 'id,pid,name');
        $tree = new PHPTree($data);
        $tree->setParam("pk", "id");
        $tree->setParam("pid", "pid");
        $cate_tree = $tree->listTree();
        foreach ($cate_tree as &$ct) {
            foreach ($ct['_child'] as &$ch) {
                $apiRes  = DbAdmin::getPermissionsApi(['menu_id' => $ch['id']], 'id,cn_name,content');
                $useMenu = DbAdmin::getAdminPermissionsRelation([
                    ['group_id', '=', $groupId],
                    ['menu_id', '=', $ch['id']],
                ], 'api_id');
                $child        = [];
                $useMenu      = array_column($useMenu, 'api_id');
                $ch['status'] = 0;
                if (in_array(0, $useMenu)) {
                    $ch['status'] = '1';
                }
                foreach ($apiRes as $ar) {
                    $c = ['id' => $ar['id'], 'cn_name' => $ar['cn_name'], 'content' => $ar['content']];
                    if (in_array($ar['id'], $useMenu)) {
                        $c['status'] = 1;
                    } else {
                        $c['status'] = 0;
                    }
                    array_push($child, $c);
                }
                $ch['child'] = $child;
            }
        }
        unset($ct);
        unset($ch);
        return ['code' => '200', 'data' => $cate_tree];
    }

    /**
     * 权限验证
     * @param $cmsConId
     * @param $apiName
     * @return bool
     * @author zyr
     */
    public function checkPermissions($cmsConId, $apiName) {
        $adminId = $this->getUidByConId($cmsConId);
        if ($adminId == '1') {
            return true;
        }
        $checkApiId = DbAdmin::getPermissionsApi(['api_name' => $apiName], 'id', true);
        if (empty($checkApiId)) {
            return false;
        }
        $checkApiId = $checkApiId['id'];
        $groupId    = DbAdmin::getAdminPermissionsGroup([
            ['admin_id', '=', $adminId],
        ], 'group_id');
        $groupId = array_column($groupId, 'group_id');
        $apiId   = DbAdmin::getAdminPermissionsRelation([
            ['group_id', 'in', $groupId],
            ['api_id', '<>', 0],
        ], 'api_id');
        $apiId = array_column($apiId, 'api_id');
        if (in_array($checkApiId, $apiId)) {
            return true;
        }
        return false;
    }

    /**
     * 获取菜单接口权限列表
     * @param $cmsConId
     * @param $id
     * @return array
     * @author zyr
     */
    public function getPermissionsApi($cmsConId) {
        $data = DbAdmin::getMenuList([], 'id,pid,name');
        $tree = new PHPTree($data);
        $tree->setParam("pk", "id");
        $tree->setParam("pid", "pid");
        $cate_tree = $tree->listTree();
        foreach ($cate_tree as &$ct) {
            foreach ($ct['_child'] as &$ch) {
                $apiRes = DbAdmin::getPermissionsApi(['menu_id' => $ch['id']], 'id,cn_name,content');
                $child  = [];
                foreach ($apiRes as $ar) {
                    $c = ['api_id' => $ar['id'], 'cn_name' => $ar['cn_name'], 'content' => $ar['content']];
                    array_push($child, $c);
                }
                $ch['child'] = $child;
            }
        }
        unset($ct);
        unset($ch);
        return ['code' => '200', 'data' => $cate_tree];
    }

    /**
     * 获取接口权限详情
     * @param $cmsConId
     * @param $id
     * @return array
     * @author zyr
     */
    public function getPermissionsApiOne($cmsConId, $id) {
        $data = DbAdmin::getPermissionsApi([['id', '=', $id]], 'id,stype,cn_name,content', true);
        return ['code' => '200', 'data' => $data];
    }
}